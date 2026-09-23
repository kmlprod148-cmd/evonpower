<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Database\Connection;
use App\Models\User;
use App\Models\Group;
use App\Models\ChargingPoint;
use App\Models\Reservation;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\WalletTransaction;
use App\Observers\ReservationObserver;
use App\Observers\TransactionObserver;
use App\Observers\TransactionDetailObserver;
use App\Observers\WalletTransactionObserver;
use App\Observers\ChargingPointSteveObserver;
use App\Services\MoneyService;
use App\Services\DetailedFeeCalculationService;
use App\Services\CurrencyService;
use App\Services\BalanceSynchronizationService;
use App\Support\AppCurrency;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Replace the default SQLite connection with a compatibility version that uses
        // pragma_table_info() instead of pragma_table_xinfo(), fixing Schema::hasColumn()
        // and Schema::getColumnListing() on SQLite < 3.35.
        Connection::resolverFor('sqlite', function ($pdo, $database, $prefix, $config) {
            return new \App\Database\SQLiteConnection($pdo, $database, $prefix, $config);
        });

        // Enregistrer le Kernel personnalisé pour qu'il soit utilisé à la place du Kernel standard
        $this->app->singleton(
            \Illuminate\Contracts\Http\Kernel::class,
            \App\Http\Kernel::class
        );

        // Binding pour PartnerRepositoryInterface
        $this->app->bind(
            \App\Repositories\Interfaces\PartnerRepositoryInterface::class,
            \App\Repositories\Eloquent\EloquentPartnerRepository::class
        );

        // Binding pour TransactionRepositoryInterface
        $this->app->bind(
            \App\Repositories\Interfaces\TransactionRepositoryInterface::class,
            \App\Repositories\TransactionRepository::class
        );

        // Binding pour SubscriptionPlanService
        $this->app->singleton(
            \App\Services\SubscriptionPlanService::class,
            function ($app) {
                return new \App\Services\SubscriptionPlanService();
            }
        );

        // Binding pour WithdrawalRequestService
        $this->app->singleton(
            \App\Services\WithdrawalRequestService::class,
            function ($app) {
                return new \App\Services\WithdrawalRequestService(
                    $app->make(\App\Services\MoneyService::class)
                );
            }
        );

        // Binding pour RefundService
        $this->app->singleton(
            \App\Services\RefundService::class,
            function ($app) {
                return new \App\Services\RefundService();
            }
        );

        // Singleton pour ClientBalanceService (memoization cache per-request)
        $this->app->singleton(
            \App\Services\ClientBalanceService::class,
            function ($app) {
                return new \App\Services\ClientBalanceService(
                    $app->make(\App\Services\BalanceSynchronizationService::class)
                );
            }
        );

        // Binding pour WalletCollectionService
        $this->app->singleton(
            \App\Services\WalletCollectionService::class,
            function ($app) {
                return new \App\Services\WalletCollectionService();
            }
        );

        // Binding pour GroupRepositoryInterface
        $this->app->bind(
            \App\Repositories\Interfaces\GroupRepositoryInterface::class,
            \App\Repositories\GroupRepository::class
        );

        $this->app->bind(
            \App\Repositories\Interfaces\ChargingPointRepositoryInterface::class,
            \App\Repositories\ChargingPointRepository::class
        );

        $this->app->bind(
            \App\Repositories\Interfaces\PricingPlanRepositoryInterface::class,
            \App\Repositories\PricingPlanRepository::class
        );

        $this->app->bind(
            \App\Repositories\Interfaces\StationRepositoryInterface::class,
            \App\Repositories\StationRepository::class
        );

        $this->app->bind(
            \App\Repositories\Interfaces\ReservationRepositoryInterface::class,
            \App\Repositories\ReservationRepository::class
        );

        $this->app->bind(
            \App\Repositories\Interfaces\BusinessProfileRepositoryInterface::class,
            \App\Repositories\BusinessProfileRepository::class
        );

        // Binding pour DetailedFeeCalculationService
        $this->app->singleton(DetailedFeeCalculationService::class, function ($app) {
            return new DetailedFeeCalculationService();
        });

        // OCPP Services
        $this->app->singleton(\App\Services\Ocpp\OcppCommandOutboxService::class, function ($app) {
            return new \App\Services\Ocpp\OcppCommandOutboxService();
        });

        $this->app->singleton(\App\Services\Ocpp\OcppEventIngestionService::class, function ($app) {
            return new \App\Services\Ocpp\OcppEventIngestionService();
        });

        $this->app->singleton(\App\Services\Ocpp\ChargingSessionStateMachine::class, function ($app) {
            return new \App\Services\Ocpp\ChargingSessionStateMachine();
        });

        $this->app->singleton(\App\Services\Ocpp\StartTransactionCorrelator::class, function ($app) {
            return new \App\Services\Ocpp\StartTransactionCorrelator();
        });

        $this->app->singleton(\App\Services\Ocpp\ChargingSessionReconciliationService::class, function ($app) {
            return new \App\Services\Ocpp\ChargingSessionReconciliationService();
        });

        // Binding pour CurrencyService (singleton)
        $this->app->singleton(CurrencyService::class, function ($app) {
            return new CurrencyService();
        });

        // SMS / OTP — InfobipSmsClient has scalar config args, so wire it via the container.
        $this->app->singleton(\App\Services\Sms\InfobipSmsClient::class, function () {
            return \App\Services\Sms\InfobipSmsClient::fromConfig();
        });

        // CRITIQUE : Remplacer le Redirector par défaut par notre Redirector personnalisé
        // Cela empêche redirect()->intended() de rediriger vers des URLs externes ou CMI
        // Utiliser singleton pour s'assurer que notre binding prend le dessus
        $this->app->singleton('redirect', function ($app) {
            return new \App\Http\Redirector($app['url'], $app['session.store'] ?? null);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->forcePublicBasePathWhenNeeded();

        // CRITICAL FIX: Set locale from URL param or session EARLY (before middleware)
        // Priority: URL ?lang= > session (session may not be ready at boot, so URL is key)
        if ($this->app->runningInConsole() === false) {
            $locale = null;
            
            // Priority 1: URL parameter - works even when session not ready (mobile)
            if (request()->has('lang')) {
                $locale = trim(strtolower((string) request()->get('lang')));
            }
            
            // Priority 2: Session (when available)
            if (!$locale && session()->has('locale')) {
                $locale = trim(strtolower((string) session('locale')));
            }
            
            // Validate and apply (fallback locales if config cached incorrectly)
            $availableLocales = config('app.available_locales', ['fr', 'en', 'ar', 'es']);
            if (empty($availableLocales)) {
                $availableLocales = ['fr', 'en', 'ar', 'es'];
            }
            if ($locale && in_array($locale, $availableLocales)) {
                \Illuminate\Support\Facades\App::setLocale($locale);
                config(['app.locale' => $locale]);
                
                // Set direction for RTL languages
                $rtlLocales = config('app.rtl_locales', ['ar']);
                config(['app.direction' => in_array($locale, $rtlLocales) ? 'rtl' : 'ltr']);
            }
        }
        
        // Partager des variables globales avec toutes les vues
        view()->composer('*', function ($view) {
            $view->with('appCurrency', AppCurrency::code());
            $view->with('appCurrencySymbol', AppCurrency::symbol());

            // Support both web (admin/staff) and client guards.
            // When ClientChargingAuthMiddleware or auth:web,client sets
            // Auth::shouldUse('client'), Auth::check() already returns true.
            // As a safety net we also check the client guard explicitly so that
            // any route that forgets to call shouldUse still gets the right user.
            $webAuthenticated    = Auth::check();
            $clientAuthenticated = !$webAuthenticated && auth('client')->check();

            if ($webAuthenticated || $clientAuthenticated) {
                $user = $webAuthenticated ? Auth::user() : auth('client')->user();

                // Safe role check (ClientUser does not have Spatie getRoleNames)
                $role = 'user';
                if (method_exists($user, 'getRoleNames')) {
                    $role = $user->getRoleNames()->first() ?? 'user';
                } elseif ($user instanceof \App\Models\ClientUser) {
                    $role = 'client';
                }
                $view->with('user_role', $role);

                // Solde crédit via ClientBalanceService (source unique)
                try {
                    $balanceService = app(\App\Services\ClientBalanceService::class);
                    $view->with('user_balance', $balanceService->getBalance($user));
                    $view->with('user_formatted_balance', $balanceService->getFormatted($user));
                } catch (\Throwable $e) {
                    $view->with('user_balance', 0.0);
                    $view->with('user_formatted_balance', AppCurrency::zero());
                }
            } else {
                $view->with('user_role', 'guest');
                $view->with('user_balance', 0.0);
                $view->with('user_formatted_balance', AppCurrency::zero());
            }
            
            // Ajouter un titre par défaut si non défini
            $viewData = $view->getData();
            if (!isset($viewData['title']) || empty($viewData['title'])) {
                $view->with('title', config('app.name', 'EVON'));
            }
        });

        // Directive Blade personnalisée pour masquer les intégrateurs aux comptes d'intégrateurs
        Blade::directive('canViewIntegrators', function () {
            return "<?php if (auth()->user() && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('super_admin'))): ?>";
        });

        Blade::directive('endcanViewIntegrators', function () {
            return "<?php endif; ?>";
        });

        // Directive pour masquer les utilisateurs aux intégrateurs
        Blade::directive('canViewUsers', function () {
            return "<?php if (auth()->check() && !auth()->user()->hasRole('integrator')): ?>";
        });

        Blade::directive('endcanViewUsers', function () {
            return "<?php endif; ?>";
        });

        // Directive pour masquer les paramètres aux intégrateurs (mais permettre l'accès aux intégrateurs)
        Blade::directive('canViewSettings', function () {
            return "<?php if (auth()->check()): ?>";
        });

        Blade::directive('endcanViewSettings', function () {
            return "<?php endif; ?>";
        });

        // Directive pour masquer les groupes aux intégrateurs (mais permettre l'accès aux intégrateurs)
        Blade::directive('canViewGroups', function () {
            return "<?php if (auth()->check()): ?>";
        });

        Blade::directive('endcanViewGroups', function () {
            return "<?php endif; ?>";
        });

        // Register hierarchical validation observers avec vérification d'existence
        // Vérifier d'abord si le fichier existe avant d'essayer de charger la classe
        $observerPath = app_path('Observers/HierarchicalValidationObserver.php');
        $hierarchicalObserverClass = \App\Observers\HierarchicalValidationObserver::class;
        
        if (file_exists($observerPath)) {
            try {
                // S'assurer que la classe est chargée
                if (!class_exists($hierarchicalObserverClass, false)) {
                    require_once $observerPath;
                }
                
                if (class_exists($hierarchicalObserverClass)) {
                    User::observe($hierarchicalObserverClass);
                }
            } catch (\Throwable $e) {
                \Log::warning('Failed to register HierarchicalValidationObserver for User', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
        
        // Register UserObserver
        try {
            $userObserverClass = \App\Observers\UserObserver::class;
            if (class_exists($userObserverClass, false) || class_exists($userObserverClass)) {
                User::observe($userObserverClass);
            }
        } catch (\Throwable $e) {
            \Log::warning('Failed to register UserObserver', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        // Vérifier si le modèle Group existe avant d'enregistrer l'observer
        if (class_exists(\App\Models\Group::class)) {
            if (file_exists($observerPath)) {
                try {
                    if (!class_exists($hierarchicalObserverClass, false)) {
                        require_once $observerPath;
                    }
                    if (class_exists($hierarchicalObserverClass)) {
                        Group::observe($hierarchicalObserverClass);
                    }
                } catch (\Throwable $e) {
                    \Log::warning('Failed to register HierarchicalValidationObserver for Group', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
        }
        
        // Register HierarchicalValidationObserver for ChargingPoint
        if (file_exists($observerPath)) {
            try {
                if (!class_exists($hierarchicalObserverClass, false)) {
                    require_once $observerPath;
                }
                if (class_exists($hierarchicalObserverClass)) {
                    ChargingPoint::observe($hierarchicalObserverClass);
                }
            } catch (\Throwable $e) {
                \Log::warning('Failed to register HierarchicalValidationObserver for ChargingPoint', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
        
        // Register Reservation observer pour traitement automatique des réservations approuvées
        try {
            $reservationObserverClass = ReservationObserver::class;
            if (class_exists($reservationObserverClass, false) || class_exists($reservationObserverClass)) {
                Reservation::observe($reservationObserverClass);
            }
        } catch (\Throwable $e) {
            \Log::warning('Failed to register ReservationObserver', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        // Register ChargingPointSteveObserver pour synchronisation automatique avec Steve API
        try {
            $chargingPointSteveObserverClass = ChargingPointSteveObserver::class;
            if (class_exists($chargingPointSteveObserverClass, false) || class_exists($chargingPointSteveObserverClass)) {
                ChargingPoint::observe($chargingPointSteveObserverClass);
            }
        } catch (\Throwable $e) {
            \Log::warning('Failed to register ChargingPointSteveObserver', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        // Register Transaction observer pour création automatique des TransactionDetail
        try {
            $transactionObserverClass = TransactionObserver::class;
            if (class_exists($transactionObserverClass, false) || class_exists($transactionObserverClass)) {
                Transaction::observe($transactionObserverClass);
            }
        } catch (\Throwable $e) {
            \Log::warning('Failed to register TransactionObserver', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        // Register TransactionDetail observer pour synchronisation automatique des balances
        // CORRECTION : Synchronisation dynamique du solde admin après chaque création/mise à jour de TransactionDetail
        try {
            $transactionDetailObserverClass = TransactionDetailObserver::class;
            if (class_exists($transactionDetailObserverClass, false) || class_exists($transactionDetailObserverClass)) {
                TransactionDetail::observe($transactionDetailObserverClass);
            }
        } catch (\Throwable $e) {
            \Log::warning('Failed to register TransactionDetailObserver', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        // Register WalletTransaction observer pour ClientBalanceUpdated (solde stocké en DB, mise à jour instantanée)
        try {
            $walletTransactionObserverClass = WalletTransactionObserver::class;
            if (class_exists($walletTransactionObserverClass, false) || class_exists($walletTransactionObserverClass)) {
                WalletTransaction::observe($walletTransactionObserverClass);
            }
        } catch (\Throwable $e) {
            \Log::warning('Failed to register WalletTransactionObserver', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        // Helper Blade pour formater les montants en EUR de manière cohérente
        Blade::directive('formatEur', function ($expression) {
            return "<?php echo \App\Services\MoneyService::format({$expression}, \App\Support\AppCurrency::code(), true); ?>";
        });

        // Helper Blade pour obtenir le symbole EUR
        Blade::directive('eurSymbol', function () {
            return "<?php echo \App\Support\AppCurrency::symbol(); ?>";
        });

        // Multi-Currency Blade Directives
        // Format money with current currency
        Blade::directive('money', function ($expression) {
            return "<?php echo format_money({$expression}); ?>";
        });

        // Format money with specific currency
        Blade::directive('formatMoney', function ($expression) {
            return "<?php echo format_money({$expression}); ?>";
        });

        // Convert and format price from base currency
        Blade::directive('price', function ($expression) {
            return "<?php echo format_price({$expression}); ?>";
        });

        // Get current currency symbol
        Blade::directive('currencySymbol', function () {
            return "<?php echo currency_symbol(); ?>";
        });

        // Get current currency code
        Blade::directive('currencyCode', function () {
            return "<?php echo current_currency(); ?>";
        });
    }

    /**
     * Support deployments where the app is accessed through a `/public` URL prefix.
     * In that setup, Laravel can otherwise generate `/plans` instead of `/public/plans`.
     */
    private function forcePublicBasePathWhenNeeded(): void
    {
        $request = request();
        $currentUrlPath = trim((string) parse_url(config('app.url', ''), PHP_URL_PATH), '/');

        if ($currentUrlPath !== '') {
            return;
        }

        $requestUri = strtok((string) $request->server('REQUEST_URI', ''), '?') ?: '';
        $basePath = $this->extractPublicBasePath($requestUri);

        if ($basePath === null) {
            return;
        }

        $rootUrl = rtrim($request->getSchemeAndHttpHost(), '/') . $basePath;

        URL::forceRootUrl($rootUrl);
        config(['app.url' => $rootUrl]);
    }

    private function extractPublicBasePath(string $requestUri): ?string
    {
        if ($requestUri === '' || $requestUri === '/') {
            return null;
        }

        $normalizedUri = '/' . ltrim($requestUri, '/');

        if ($normalizedUri === '/public' || str_starts_with($normalizedUri, '/public/')) {
            return '/public';
        }

        $publicSegmentPosition = strpos($normalizedUri, '/public/');

        if ($publicSegmentPosition === false) {
            return str_ends_with($normalizedUri, '/public') ? $normalizedUri : null;
        }

        return rtrim(substr($normalizedUri, 0, $publicSegmentPosition + 7), '/');
    }
}
