<?php

use Illuminate\Support\Facades\Log;

// App Controllers
use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\AdminReservationController;
use App\Http\Controllers\Admin\BusinessProfileController as AdminBusinessProfileController;
use App\Http\Controllers\Admin\OrderAdminController;
use App\Http\Controllers\AdminNotificationController;
use App\Http\Controllers\BusinessProfileController;
use App\Http\Controllers\ChargingPointController;
use App\Http\Controllers\CommissionController;
use App\Http\Controllers\CommissionPlanController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinancialTransactionController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\IntegratorController;
use App\Http\Controllers\IntegratorDashboardController;
use App\Http\Controllers\OperatorController;
use App\Http\Controllers\UnifiedChargingPointController;
use App\Http\Controllers\ChargingPointViewController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\PricingPlanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\RemoteControlController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\OperatorDashboardController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StationController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WithdrawalRequestController;
use App\Http\Controllers\HierarchicalTransactionController;
use App\Http\Controllers\TransactionHistoryController;
use App\Http\Controllers\QRCodeTestController;
use App\Http\Controllers\SteVeController;
use App\Http\Controllers\ChargingPointEnhancedController;
use App\Http\Controllers\SteVeMonitoringController;
use App\Http\Controllers\SteVeTestController;
use App\Http\Controllers\SteVeAutoStopController;
use App\Http\Controllers\SteVeAutoStopTestController;
use App\Http\Controllers\Dashboard\BalanceDashboardController;
use App\Http\Controllers\AdminConfigurationController;
// App Middleware
use App\Http\Middleware\UnifiedPermissionMiddleware;
// App Models
use App\Models\BusinessProfile;
use App\Models\ChargingPoint;
use App\Models\Reservation;
use App\Models\VatRate;
// Facades
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

// Authentication routes (Laravel Breeze/UI)
require __DIR__.'/auth.php';

// Routes de localisation
Route::prefix('language')->name('language.')->group(function () {
    Route::get('/change/{locale}', [App\Http\Controllers\LanguageController::class, 'change'])->name('change');
    Route::get('/current', [App\Http\Controllers\LanguageController::class, 'getCurrent'])->name('current');
    Route::get('/translations/{locale}', [App\Http\Controllers\LanguageController::class, 'getTranslations'])->name('translations');
});

// Route de test Inertia
Route::get('/inertia-test', function () {
    return \Inertia\Inertia::render('Dashboard/Index', [
        'stats' => [
            'charging_points' => [
                'online' => 15,
                'offline' => 3,
                'charging' => 2,
                'maintenance' => 1,
            ],
            'sessions' => [
                'active' => 2,
                'completed_today' => 45,
                'total_today' => 47,
            ],
            'revenue' => [
                'today' => 1250.50,
                'this_month' => 45000.00,
                'total' => 125000.00,
            ],
            'users' => [
                'total' => 150,
                'active_today' => 25,
            ],
        ],
        'topChargingPoints' => [
            [
                'id' => 1,
                'name' => 'Borne Centre Ville',
                'status' => 'online',
                'revenue' => 2500.00,
                'sessions_count' => 45,
            ],
            [
                'id' => 2,
                'name' => 'Borne Mall',
                'status' => 'online',
                'revenue' => 1800.00,
                'sessions_count' => 32,
            ],
        ],
        'revenueData' => [
            ['month' => 'Jan', 'revenue' => 12000],
            ['month' => 'Fév', 'revenue' => 15000],
            ['month' => 'Mar', 'revenue' => 18000],
            ['month' => 'Avr', 'revenue' => 22000],
            ['month' => 'Mai', 'revenue' => 25000],
            ['month' => 'Juin', 'revenue' => 30000],
        ],
        'sessionsData' => [
            ['day' => '01/01', 'sessions' => 25],
            ['day' => '02/01', 'sessions' => 30],
            ['day' => '03/01', 'sessions' => 28],
            ['day' => '04/01', 'sessions' => 35],
            ['day' => '05/01', 'sessions' => 40],
            ['day' => '06/01', 'sessions' => 38],
            ['day' => '07/01', 'sessions' => 42],
        ],
    ]);
})->name('inertia.test');

// Language switching routes (with web middleware to ensure SetLocale is applied)
Route::middleware(['web'])->group(function () {
    Route::get('/lang/{locale}', [LanguageController::class, 'switch'])->name('lang.switch');
    Route::get('/lang/ajax/{locale}', [LanguageController::class, 'setLocaleAjax'])->name('lang.ajax');
    Route::get('/lang-test', [LanguageController::class, 'testLanguage'])->name('lang.test');
    Route::get('/test-middleware', function () {
        return response()->json([
            'app_locale' => app()->getLocale(),
            'session_locale' => session('locale'),
            'config_locale' => config('app.locale'),
            'welcome_message' => __('messages.welcome'),
            'session_id' => session()->getId(),
            'middleware_executed' => true
        ]);
    })->name('test.middleware');
    
    // Force locale test route
    Route::get('/force-locale/{locale}', function ($locale) {
        // Force set the locale
        app()->setLocale($locale);
        session(['locale' => $locale]);
        
        return response()->json([
            'forced_locale' => $locale,
            'app_locale' => app()->getLocale(),
            'session_locale' => session('locale'),
            'welcome_message' => __('messages.welcome')
        ]);
    })->name('force.locale');
});

// Contact page for testing translations
// Route de démonstration des switches traduits
Route::get('/switch-demo', function () {
    return view('demo.switch-demo');
})->name('switch.demo');

Route::get('/contact', function () {
    return view('contact');
})->name('contact');

// Test route for language switching
Route::get('/test-lang', function () {
    return response()->json([
        'current_locale' => app()->getLocale(),
        'session_locale' => session('locale'),
        'config_locale' => config('app.locale'),
        'available_locales' => ['en', 'fr']
    ]);
})->name('test.lang');

// Debug endpoint for language switching
Route::get('/debug-lang/{locale}', function ($locale) {
    try {
        app()->setLocale($locale);
        session(['locale' => $locale]);
        
        return response()->json([
            'success' => true,
            'locale' => $locale,
            'app_locale' => app()->getLocale(),
            'session_locale' => session('locale'),
            'message' => __('messages.language_switched', ['locale' => $locale]),
            'direction' => $locale === 'ar' ? 'rtl' : 'ltr'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
})->name('debug.lang');

// Simple test route outside middleware
Route::get('/simple-debug-lang/{locale}', function ($locale) {
    // Force locale change and save session
    app()->setLocale($locale);
    session(['locale' => $locale]);
    session()->save(); // Force save session
    
    // Also set in config
    config(['app.locale' => $locale]);

    return response()->json([
        'success' => true,
        'locale' => $locale,
        'app_locale' => app()->getLocale(),
        'session_locale' => session('locale'),
        'config_locale' => config('app.locale'),
        'message' => "Language switched to {$locale}",
        'direction' => $locale === 'ar' ? 'rtl' : 'ltr',
        'timestamp' => now()->toDateTimeString()
    ]);
})->name('simple.debug.lang');

// Alternative language switch route with redirect
Route::get('/lang-switch/{locale}', function ($locale) {
    // Force locale change in ALL possible ways
    app()->setLocale($locale);
    session(['locale' => $locale]);
    session()->save();
    config(['app.locale' => $locale]);
    
    // Set cookie with high priority
    $cookie = cookie('locale', $locale, 60 * 24 * 30, '/', null, false, true); // 30 days, secure
    
    // Also set in request attributes to override middleware
    request()->attributes->set('locale', $locale);
    request()->attributes->set('force_locale', $locale);
    
    return redirect()->back()
        ->with('success', "Language switched to {$locale}")
        ->withCookie($cookie)
        ->header('X-Locale', $locale);
})->name('lang.switch.redirect');

// Direct language switch that bypasses middleware
Route::get('/direct-lang/{locale}', function ($locale) {
    // Force locale change in ALL possible ways
    app()->setLocale($locale);
    session(['locale' => $locale]);
    session()->save();
    config(['app.locale' => $locale]);
    
    // Set multiple cookies to ensure persistence
    $localeCookie = cookie('app_locale', $locale, 60 * 24 * 30, '/', null, false, true);
    $forceCookie = cookie('force_locale', $locale, 60 * 24 * 30, '/', null, false, true);
    $oldCookie = cookie()->forget('locale'); // Remove old cookie
    
    // Also set the standard locale cookie
    $standardCookie = cookie('locale', $locale, 60 * 24 * 30, '/', null, false, true);
    
    // Redirect to test page with locale in URL
    return redirect('/test-language-switcher?lang=' . $locale)
        ->withCookie($localeCookie)
        ->withCookie($forceCookie)
        ->withCookie($standardCookie)
        ->withCookie($oldCookie)
        ->with('success', "Language switched to {$locale}");
})->name('direct.lang');

// Debug route to check current locale state
Route::get('/debug-locale', function () {
    return response()->json([
        'app_locale' => app()->getLocale(),
        'session_locale' => session('locale'),
        'config_locale' => config('app.locale'),
        'cookie_locale' => request()->cookie('locale'),
        'cookie_app_locale' => request()->cookie('app_locale'),
        'cookie_force_locale' => request()->cookie('force_locale'),
        'url_lang' => request()->get('lang'),
        'session_id' => session()->getId(),
        'timestamp' => now()->toDateTimeString(),
        'welcome_message' => __('messages.welcome', [], app()->getLocale()),
        'all_cookies' => request()->cookies->all()
    ]);
})->name('debug.locale');

// Test route to verify language change
Route::get('/test-lang-change/{locale}', function ($locale) {
    app()->setLocale($locale);
    session(['locale' => $locale]);
    session()->save();
    
    return response()->json([
        'success' => true,
        'locale' => $locale,
        'app_locale' => app()->getLocale(),
        'welcome_message' => __('messages.welcome', [], $locale),
        'timestamp' => now()->toDateTimeString()
    ]);
})->name('test.lang.change');

// Test page for language switcher
Route::get('/test-language-switcher', function () {
    return view('test-language-switcher');
})->name('test.language.switcher');

// Simple language test route without middleware
Route::get('/simple-lang-test/{locale}', function ($locale) {
    // Set locale directly
    app()->setLocale($locale);
    session(['locale' => $locale]);
    session()->save();
    
    return response()->json([
        'success' => true,
        'locale' => $locale,
        'app_locale' => app()->getLocale(),
        'session_locale' => session('locale'),
        'message' => "Language set to {$locale}",
        'timestamp' => now()->toDateTimeString()
    ]);
})->name('simple.lang.test');

// Force language change route
Route::get('/force-lang/{locale}', function ($locale) {
    // Force locale change in ALL possible ways
    app()->setLocale($locale);
    session(['locale' => $locale]);
    session()->save();
    config(['app.locale' => $locale]);
    
    // Set cookies with immediate effect
    $response = response()->json([
        'success' => true,
        'locale' => $locale,
        'app_locale' => app()->getLocale(),
        'session_locale' => session('locale'),
        'config_locale' => config('app.locale'),
        'message' => "Language FORCED to {$locale}",
        'timestamp' => now()->toDateTimeString()
    ]);
    
    // Set multiple cookies
    $response->withCookie(cookie('app_locale', $locale, 60 * 24 * 30, '/', null, false, true));
    $response->withCookie(cookie('force_locale', $locale, 60 * 24 * 30, '/', null, false, true));
    $response->withCookie(cookie('locale', $locale, 60 * 24 * 30, '/', null, false, true));
    
    return $response;
})->name('force.lang');

// Minimal test page
Route::get('/test-minimal', function () {
    return view('test-minimal');
})->name('test.minimal');

// Simple language switcher test
Route::get('/test-language-switcher-simple', function () {
    return view('test-language-switcher-simple');
})->name('test.language.switcher.simple');

// Standalone test page for language switcher
Route::get('/standalone-test', function () {
    return view('standalone-test');
})->name('standalone.test');

// API route for getting translations
Route::get('/api/translations/{locale}', function ($locale) {
    if (!in_array($locale, ['en', 'fr', 'ar'])) {
        return response()->json(['error' => 'Invalid locale'], 400);
    }

    // Temporarily set locale to get translations
    $originalLocale = app()->getLocale();
    app()->setLocale($locale);

    $translations = [
        'welcome' => __('messages.welcome'),
        'language' => __('messages.language'),
        'dashboard' => __('messages.dashboard')
    ];

    // Restore original locale
    app()->setLocale($originalLocale);

    return response()->json($translations);
})->name('api.translations');

// 3D Model Test Routes
Route::prefix('test-3d')->name('test.3d.')->group(function () {
    Route::get('/', [App\Http\Controllers\Test3DController::class, 'index'])->name('index');
    Route::get('/check-model', [App\Http\Controllers\Test3DController::class, 'checkModel'])->name('check-model');
    Route::get('/test-component', [App\Http\Controllers\Test3DController::class, 'testComponent'])->name('component');
    Route::get('/test-webgl', [App\Http\Controllers\Test3DController::class, 'testWebGL'])->name('webgl');
    Route::get('/debug', [App\Http\Controllers\Test3DController::class, 'debug'])->name('debug');
});

// Simple test route with web middleware
Route::middleware(['web'])->get('/simple-test', function () {
    return response()->json([
        'app_locale' => app()->getLocale(),
        'session_locale' => session('locale'),
        'welcome_message' => __('messages.welcome')
    ]);
})->name('simple.test');


// Route d'accueil - redirige vers login
Route::get('/', function() {
    return redirect('/login');
})->name('home');

// Route dashboard principal avec Inertia
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('auth')->name('dashboard');

// Route de test pour diagnostiquer le problème de vue
Route::get('/dashboard-test-view', function() {
    try {
        return view('dashboard.index', [
            'totalRecharges' => 12,
            'rechargesActives' => 4,
            'abonnementsActifs' => 3,
            'bornesActives' => 16,
            'rechargesActivesList' => [],
            'hourlyData' => [
                'labels' => ['00h', '03h', '06h', '09h', '12h', '15h', '18h', '21h'],
                'data' => [10000, 8000, 15000, 20000, 28000, 25000, 20000, 25000]
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
    }
})->name('dashboard.test.view');

// Route temporaire pour le dashboard principal avec données de test - temporairement sans auth
Route::get('/dashboard-main', function() {
    return view('dashboard', [
        'stats' => [
            'totalRecharges' => 1250,
            'rechargesActives' => 8,
            'abonnementsActifs' => 45,
            'bornesActives' => 23
        ],
        'hourlyData' => [
            'labels' => ['00h', '03h', '06h', '09h', '12h', '15h', '18h', '21h'],
            'data' => [15, 8, 12, 25, 45, 38, 22, 18]
        ],
        'rechargesActivesList' => [
            ['name' => 'Morocco Mall 01', 'kwh' => 423],
            ['name' => 'Mall 02', 'kwh' => 387],
            ['name' => 'Centre Ville', 'kwh' => 298],
            ['name' => 'Aéroport', 'kwh' => 156]
        ]
    ]);
})->name('dashboard.main');

// SteVe API Routes
Route::middleware(['auth', 'hierarchical.permission:view-charging-points'])->prefix('steve')->name('steve.')->group(function () {
    // Test connection
    Route::get('/test-connection', [SteVeController::class, 'testConnection'])->name('test-connection');
    
    // Charging Point related routes
    Route::get('/charging-points/{chargingPointId}/transactions', [SteVeController::class, 'getChargingPointTransactions'])->name('charging-points.transactions');
    Route::get('/charging-points/{chargingPointId}/statistics', [SteVeController::class, 'getChargingPointStatistics'])->name('charging-points.statistics');
    Route::get('/charging-points/{chargingPointId}/tags', [SteVeController::class, 'getChargingPointTags'])->name('charging-points.tags');
    
    // OCPP Tag management
    Route::post('/ocpp-tags', [SteVeController::class, 'createOcppTag'])
        ->middleware('hierarchical.permission:create-ocpp-tags')
        ->name('ocpp-tags.create');
    Route::put('/ocpp-tags/{tagId}/toggle-block', [SteVeController::class, 'toggleTagBlock'])
        ->middleware('hierarchical.permission:edit-ocpp-tags')
        ->name('ocpp-tags.toggle-block');
    Route::delete('/ocpp-tags/{tagId}', [SteVeController::class, 'deleteOcppTag'])
        ->middleware('hierarchical.permission:delete-ocpp-tags')
        ->name('ocpp-tags.delete');
    
    // Remote Actions
    Route::post('/charging-points/{chargingPointId}/start-charging', [SteVeController::class, 'startCharging'])
        ->middleware('hierarchical.permission:control-charging-points')
        ->name('charging-points.start-charging');
    Route::post('/charging-points/{chargingPointId}/stop-charging', [SteVeController::class, 'stopCharging'])
        ->middleware('hierarchical.permission:control-charging-points')
        ->name('charging-points.stop-charging');
});

// Route pour le dashboard statique (sans JavaScript) - temporairement sans auth
Route::get('/dashboard-static', function() {
    return view('dashboard-static', [
        'stats' => [
            'totalRecharges' => 1250,
            'rechargesActives' => 8,
            'abonnementsActifs' => 45,
            'bornesActives' => 23
        ],
        'hourlyData' => [
            'labels' => ['00h', '03h', '06h', '09h', '12h', '15h', '18h', '21h'],
            'data' => [15, 8, 12, 25, 45, 38, 22, 18]
        ],
        'rechargesActivesList' => [
            ['name' => 'Morocco Mall 01', 'kwh' => 423],
            ['name' => 'Mall 02', 'kwh' => 387],
            ['name' => 'Centre Ville', 'kwh' => 298],
            ['name' => 'Aéroport', 'kwh' => 156]
        ]
    ]);
})->name('dashboard.static');

// Route pour le dashboard minimal (ultra-simple) - temporairement sans auth
Route::get('/dashboard-minimal', function() {
    return view('dashboard-minimal');
})->name('dashboard.minimal');

// Route pour le dashboard minimal sans authentification
Route::get('/dashboard-minimal-public', function() {
    return view('dashboard-minimal');
})->name('dashboard.minimal.public');

// Route de test pour le dashboard fonctionnel
Route::get('/dashboard-working', function() {
    return view('dashboard-minimal');
})->name('dashboard.working');

// Route de test pour le dashboard statique fonctionnel
Route::get('/dashboard-static-working', function() {
    return view('dashboard-static', [
        'stats' => [
            'totalRecharges' => 1250,
            'rechargesActives' => 8,
            'abonnementsActifs' => 45,
            'bornesActives' => 23
        ],
        'hourlyData' => [
            'labels' => ['00h', '03h', '06h', '09h', '12h', '15h', '18h', '21h'],
            'data' => [15, 8, 12, 25, 45, 38, 22, 18]
        ],
        'rechargesActivesList' => [
            ['name' => 'Morocco Mall 01', 'kwh' => 423],
            ['name' => 'Mall 02', 'kwh' => 387],
            ['name' => 'Centre Ville', 'kwh' => 298],
            ['name' => 'Aéroport', 'kwh' => 156]
        ]
    ]);
})->name('dashboard.static.working');

// Route principale de remplacement - SOLUTION DÉFINITIVE
Route::get('/home', function() {
    return view('dashboard-minimal');
})->name('home.alternative');

// Route dashboard de remplacement - SOLUTION DÉFINITIVE
Route::get('/main', function() {
    return view('dashboard-minimal');
})->name('main.dashboard');

// Route temporaire sans authentification pour tester le dashboard
Route::get('/dashboard-public', [DashboardController::class, 'index'])->name('dashboard.public');

// Route de test temporaire sans authentification
Route::get('/test-dashboard', [DashboardController::class, 'index'])->name('test.dashboard');

// Route de secours pour le dashboard - SOLUTION IMMÉDIATE
Route::get('/dashboard-fix', function() {
    return view('dashboard.index', [
        'totalRecharges' => 1250,
        'rechargesActives' => 8,
        'abonnementsActifs' => 45,
        'bornesActives' => 23,
        'rechargesActivesList' => [
            ['name' => 'Morocco Mall 01', 'kwh' => 423],
            ['name' => 'Mall 02', 'kwh' => 387],
            ['name' => 'Centre Ville', 'kwh' => 298],
            ['name' => 'Aéroport', 'kwh' => 156]
        ],
        'hourlyData' => [
            'labels' => ['00h', '03h', '06h', '09h', '12h', '15h', '18h', '21h'],
            'data' => [15, 8, 12, 25, 45, 38, 22, 18]
        ]
    ]);
})->name('dashboard.fix');

// Route de test simple pour vérifier que les routes fonctionnent
Route::get('/test-simple', function() {
    return response()->json(['status' => 'ok', 'message' => 'Routes working']);
})->name('test.simple');

// Route de test pour le dashboard sans authentification (temporaire)
Route::get('/dashboard-test', function() {
    return view('dashboard', [
        'stats' => [
            'totalRecharges' => 1250,
            'rechargesActives' => 8,
            'abonnementsActifs' => 45,
            'bornesActives' => 23
        ],
        'hourlyData' => [
            'labels' => ['00h', '03h', '06h', '09h', '12h', '15h', '18h', '21h'],
            'data' => [15, 8, 12, 25, 45, 38, 22, 18]
        ],
        'rechargesActivesList' => [
            ['name' => 'Morocco Mall 01', 'kwh' => 423],
            ['name' => 'Mall 02', 'kwh' => 387],
            ['name' => 'Centre Ville', 'kwh' => 298],
            ['name' => 'Aéroport', 'kwh' => 156]
        ]
    ]);
})->name('dashboard.test');

// Route de test pour le dashboard simplifié
Route::get('/dashboard-simple', function() {
    return view('dashboard-simple', [
        'stats' => [
            'totalRecharges' => 1250,
            'rechargesActives' => 8,
            'abonnementsActifs' => 45,
            'bornesActives' => 23
        ],
        'hourlyData' => [
            'labels' => ['00h', '03h', '06h', '09h', '12h', '15h', '18h', '21h'],
            'data' => [15, 8, 12, 25, 45, 38, 22, 18]
        ],
        'rechargesActivesList' => [
            ['name' => 'Morocco Mall 01', 'kwh' => 423],
            ['name' => 'Mall 02', 'kwh' => 387],
            ['name' => 'Centre Ville', 'kwh' => 298],
            ['name' => 'Aéroport', 'kwh' => 156]
        ]
    ]);
})->name('dashboard.simple');

// Test route
Route::get('/test', function () {
    return 'Test route works!';
})->name('test');

Route::get('/debug-test-route', function () {
    return 'Debug test route hit!';
});

// Test route outside middleware group
Route::get('/test-create-direct', function () {
    return response()->json([
        'message' => 'Route de test directe',
        'user_id' => auth()->id(),
        'authenticated' => auth()->check(),
        'timestamp' => now()->toDateTimeString()
    ]);
})->name('test.create.direct');

// Ultra-simple test route
Route::get('/test-simple', function () {
    return 'Test simple fonctionne !';
});

// Test route with HTML
Route::get('/test-html', function () {
    return '<h1>Test HTML</h1><p>Si vous voyez ceci, Laravel fonctionne !</p>';
});

// Temporary create route that should work
Route::get('/create-charging-point', function () {
    if (!auth()->check()) {
        return redirect()->route('login');
    }
    
    $user = auth()->user();
    if (!$user->can('create_charging_points')) {
        return response('Permission refusée', 403);
    }
    
    return view('charging-points.create-step1-enhanced', [
        'operators' => collect([]),
        'businessProfiles' => collect([]),
        'groups' => collect([]),
        'pricingPlans' => collect([]),
    ]);
})->name('create.charging.point.temp');

// Main Application Routes with Unified Permission Middleware
Route::middleware(['auth'])->group(function () {
    // Charging Points Routes (consolidated in charging-points.php)
    require __DIR__.'/charging-points.php';
    
    // Support contact route
    Route::get('/support/contact', function () {
        return view('contact');
    })->name('support.contact');
    
    // Public charging offer routes
    Route::prefix('public/charging-point/{charging_point_id}/offer')->name('public.charging-point.offer.')->group(function () {
        Route::post('/store-reservation', [ReservationController::class, 'store'])->name('store-reservation');
    });
    
    Route::get('/reservations/{reservation}', [ReservationController::class, 'show'])->name('reservations.show');
    Route::get('/operator/dashboard', [OperatorDashboardController::class, 'index'])->name('operator.dashboard');
    Route::get('/operator/profile', [OperatorController::class, 'profile'])->name('operator.profile');
    Route::get('/operator/profile/edit', [OperatorController::class, 'editProfile'])->name('operator.profile.edit');
    Route::put('/operator/profile/update', [OperatorController::class, 'updateProfile'])->name('operator.profile.update');
    Route::get('/operator/statistics', [OperatorController::class, 'statistics'])->name('operator.statistics');
    Route::get('/operator/activity', [OperatorController::class, 'activity'])->name('operator.activity');
    Route::get('/operator/settings', [OperatorController::class, 'settings'])->name('operator.settings');
    Route::put('/operator/settings/update', [OperatorController::class, 'updateSettings'])->name('operator.settings.update');
    Route::get('/integrator/dashboard', [IntegratorDashboardController::class, 'index'])->name('integrator.dashboard');

    // Payment Routes - Enhanced Secure Payment System
    Route::prefix('payment')->name('payment.')->group(function () {
        // Display payment choice page for a specific reservation
        Route::get('/{reservation}/choose', [\App\Http\Controllers\SecurePaymentController::class, 'showPaymentMethods'])
            ->name('choose')
            ->middleware('can:pay,reservation');

        // CMI Payment Routes (Enhanced)
        Route::post('/cmi/initiate', [\App\Http\Controllers\SecurePaymentController::class, 'initiateCmiPayment'])
            ->name('cmi.initiate');
        Route::post('/cmi/callback', [\App\Http\Controllers\SecurePaymentController::class, 'handleCmiCallback'])
            ->name('cmi.callback');
        Route::get('/cmi/success', [\App\Http\Controllers\SecurePaymentController::class, 'cmiSuccess'])
            ->name('cmi.success');
        Route::get('/cmi/failure', [\App\Http\Controllers\SecurePaymentController::class, 'cmiFailure'])
            ->name('cmi.failure');
        
        // Stripe Payment Routes (Enhanced)
        Route::post('/stripe/initiate', [\App\Http\Controllers\SecurePaymentController::class, 'initiateStripePayment'])
            ->name('stripe.initiate');
        Route::post('/stripe/webhook', [\App\Http\Controllers\SecurePaymentController::class, 'handleStripeWebhook'])
            ->name('stripe.webhook');
        Route::get('/stripe/success', [\App\Http\Controllers\SecurePaymentController::class, 'stripeSuccess'])
            ->name('stripe.success');
        Route::get('/stripe/cancel', [\App\Http\Controllers\SecurePaymentController::class, 'stripeCancel'])
            ->name('stripe.cancel');
        
        // Payment Result Pages
        Route::get('/success', [\App\Http\Controllers\SecurePaymentController::class, 'paymentSuccess'])
            ->name('success');
        Route::get('/failure', [\App\Http\Controllers\SecurePaymentController::class, 'paymentFailure'])
            ->name('failure');
        
        // Payment Analytics
        Route::get('/analytics', [\App\Http\Controllers\PaymentAnalyticsController::class, 'getPaymentStats'])
            ->name('analytics.stats');
        Route::get('/analytics/methods', [\App\Http\Controllers\PaymentAnalyticsController::class, 'getPaymentMethodStats'])
            ->name('analytics.methods');
        Route::get('/analytics/conversion', [\App\Http\Controllers\PaymentAnalyticsController::class, 'getConversionStats'])
            ->name('analytics.conversion');
        Route::get('/analytics/errors', [\App\Http\Controllers\PaymentAnalyticsController::class, 'getPaymentErrors'])
            ->name('analytics.errors');
    });

    // CMI server-to-server callback URL (should be publicly accessible but secured internally)
    // This route should NOT be under 'auth' middleware as CMI gateway will call it directly.
    // Security will be handled within the controller method (e.g., IP whitelisting, signature verification).
    Route::get('/dashboard/realtime-data', [DashboardController::class, 'realtimeData'])->name('dashboard.realtime-data');

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // General Settings routes (admin@evon.com only)
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/general-settings', [App\Http\Controllers\GeneralSettingsController::class, 'index'])->name('general-settings');
        Route::put('/general-settings', [App\Http\Controllers\GeneralSettingsController::class, 'update'])->name('general-settings.update');
        Route::post('/general-settings/reset', [App\Http\Controllers\GeneralSettingsController::class, 'reset'])->name('general-settings.reset');
        
        // Routes pour les configurations admin
        Route::prefix('configurations')->name('configurations.')->group(function () {
            Route::get('/', [AdminConfigurationController::class, 'index'])->name('index');
            Route::get('/stats', [AdminConfigurationController::class, 'stats'])->name('stats');
            Route::get('/{category}', [AdminConfigurationController::class, 'show'])->name('show');
            Route::put('/{category}/{key}', [AdminConfigurationController::class, 'update'])->name('update');
            Route::put('/', [AdminConfigurationController::class, 'updateMultiple'])->name('update-multiple');
            Route::post('/validate', [AdminConfigurationController::class, 'validateConfiguration'])->name('validate');
            Route::get('/export', [AdminConfigurationController::class, 'export'])->name('export');
            Route::post('/import', [AdminConfigurationController::class, 'import'])->name('import');
            Route::post('/reset', [AdminConfigurationController::class, 'reset'])->name('reset');
        });

        // Routes pour la gestion des clés API
        Route::prefix('api-keys')->name('api-keys.')->middleware('admin.api-keys')->group(function () {
            Route::get('/', [App\Http\Controllers\AdminApiKeysController::class, 'index'])->name('index');
            Route::put('/{key}', [App\Http\Controllers\AdminApiKeysController::class, 'updateApiKey'])->name('update');
            Route::put('/', [App\Http\Controllers\AdminApiKeysController::class, 'updateMultipleApiKeys'])->name('update-multiple');
            Route::post('/{key}/test', [App\Http\Controllers\AdminApiKeysController::class, 'testApiKey'])->name('test');
            Route::get('/{key}/mask', [App\Http\Controllers\AdminApiKeysController::class, 'maskApiKey'])->name('mask');
            Route::delete('/{key}/reset', [App\Http\Controllers\AdminApiKeysController::class, 'resetApiKey'])->name('reset');
        });
    });

    // Alternative route for general settings (using different path to avoid conflict)
    Route::prefix('parametres')->name('parametres.')->group(function () {
        Route::get('/generaux', [App\Http\Controllers\GeneralSettingsController::class, 'index'])->name('generaux');
        Route::put('/generaux', [App\Http\Controllers\GeneralSettingsController::class, 'update'])->name('generaux.update');
        Route::post('/generaux/reset', [App\Http\Controllers\GeneralSettingsController::class, 'reset'])->name('generaux.reset');
    });

    // Groups routes
    Route::prefix('groups')->name('groups.')->group(function () {
        Route::get('/', [GroupController::class, 'index'])->name('index');
        Route::get('/create', [GroupController::class, 'create'])->name('create');
        Route::get('/create/step1', [GroupController::class, 'create'])->name('create.step1');
        Route::post('/create/step1', [GroupController::class, 'storeStep1'])->name('store.step1');
        Route::get('/create/step2', [GroupController::class, 'createStep2'])->name('create.step2');
        Route::post('/create/step2', [GroupController::class, 'storeStep2'])->name('store.step2');
        Route::post('/', [GroupController::class, 'store'])->name('store');
        Route::get('/{group}', [GroupController::class, 'show'])->name('show');
        Route::get('/{group}/edit', [GroupController::class, 'edit'])->name('edit');
        Route::put('/{group}', [GroupController::class, 'update'])->name('update');
        Route::delete('/{group}', [GroupController::class, 'destroy'])->name('destroy');
    });

    // Admin routes manquantes
    Route::prefix('admin')->name('admin.')->group(function () {
        // Admin users routes
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\UserController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Admin\UserController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Admin\UserController::class, 'store'])->name('store');
            Route::get('/{user}', [App\Http\Controllers\Admin\UserController::class, 'show'])->name('show');
            Route::get('/{user}/edit', [App\Http\Controllers\Admin\UserController::class, 'edit'])->name('edit');
            Route::put('/{user}', [App\Http\Controllers\Admin\UserController::class, 'update'])->name('update');
            Route::delete('/{user}', [App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('destroy');
        });

        // Admin charging points routes
        Route::prefix('charging-points')->name('charging-points.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\ChargingPointController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Admin\ChargingPointController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Admin\ChargingPointController::class, 'store'])->name('store');
            Route::get('/{chargingPoint}', [App\Http\Controllers\Admin\ChargingPointController::class, 'show'])->name('show');
            Route::get('/{chargingPoint}/edit', [App\Http\Controllers\Admin\ChargingPointController::class, 'edit'])->name('edit');
            Route::put('/{chargingPoint}', [App\Http\Controllers\Admin\ChargingPointController::class, 'update'])->name('update');
            Route::delete('/{chargingPoint}', [App\Http\Controllers\Admin\ChargingPointController::class, 'destroy'])->name('destroy');
        });
    });

    // Routes manquantes pour les rapports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [App\Http\Controllers\ReportController::class, 'index'])->name('index');
    });

    // Routes manquantes pour les commissions
    Route::prefix('commissions')->name('commissions.')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\CommissionController::class, 'dashboard'])->name('dashboard');
    });

    // Charging Points routes are now in charging-points.php

    // Stations
    Route::resource('stations', StationController::class);
    Route::get('/stations/available-charging-points', [StationController::class, 'getAvailableChargingPoints'])->name('available-charging-points');

    // Partners
    Route::prefix('partners')->name('partners.')->group(function () {
        Route::get('/', [PartnerController::class, 'index'])
            ->name('index')
            ->middleware(\App\Http\Middleware\HierarchicalPermissionMiddleware::class . ':view_partners');

        Route::get('/create', [PartnerController::class, 'create'])
            ->name('create')
            ->middleware(\App\Http\Middleware\HierarchicalPermissionMiddleware::class . ':create_partners');

        Route::post('/', [PartnerController::class, 'store'])
            ->name('store')
            ->middleware(\App\Http\Middleware\HierarchicalPermissionMiddleware::class . ':create_partners');

        Route::get('/{partner}', [PartnerController::class, 'show'])
            ->name('show')
            ->middleware(\App\Http\Middleware\HierarchicalPermissionMiddleware::class . ':view_partners');

        Route::get('/{partner}/edit', [PartnerController::class, 'edit'])
            ->name('edit')
            ->middleware(\App\Http\Middleware\HierarchicalPermissionMiddleware::class . ':edit_partners');

        Route::put('/{partner}', [PartnerController::class, 'update'])
            ->name('update')
            ->middleware(\App\Http\Middleware\HierarchicalPermissionMiddleware::class . ':edit_partners');

        Route::delete('/{partner}', [PartnerController::class, 'destroy'])
            ->name('destroy')
            ->middleware(\App\Http\Middleware\HierarchicalPermissionMiddleware::class . ':delete_partners');
    });


    // Integrators
    Route::prefix('integrators')->name('integrators.')->middleware(\App\Http\Middleware\HierarchicalPermissionMiddleware::class . ':view_integrators')->group(function () {
        Route::get('/', [IntegratorController::class, 'index'])->name('index');
        Route::get('/export', [IntegratorController::class, 'export'])->name('export');
        Route::get('/create', [IntegratorController::class, 'create'])->name('create')->middleware(\App\Http\Middleware\HierarchicalPermissionMiddleware::class . ':create_integrators');
        Route::post('/', [IntegratorController::class, 'store'])->name('store')->middleware(\App\Http\Middleware\HierarchicalPermissionMiddleware::class . ':create_integrators');
        Route::get('/{integrator}', [IntegratorController::class, 'show'])->name('show');
        Route::get('/{integrator}/edit', [IntegratorController::class, 'edit'])->name('edit')->middleware(\App\Http\Middleware\HierarchicalPermissionMiddleware::class . ':edit_integrators');
        Route::put('/{integrator}', [IntegratorController::class, 'update'])->name('update')->middleware(\App\Http\Middleware\HierarchicalPermissionMiddleware::class . ':edit_integrators');
        Route::post('/{integrator}/activate', [IntegratorController::class, 'activate'])->name('activate')->middleware(\App\Http\Middleware\HierarchicalPermissionMiddleware::class . ':edit_integrators');
        Route::match(['POST', 'PATCH'], '/{integrator}/deactivate', [IntegratorController::class, 'deactivate'])->name('deactivate')->middleware(\App\Http\Middleware\HierarchicalPermissionMiddleware::class . ':edit_integrators');
        Route::delete('/{integrator}', [IntegratorController::class, 'destroy'])->name('destroy')->middleware(\App\Http\Middleware\HierarchicalPermissionMiddleware::class . ':delete_integrators');
    });

    // Transactions - Specific routes must come BEFORE the resource route
    Route::get('transactions/admin', [App\Http\Controllers\Admin\AdminTransactionController::class, 'index'])->name('transactions.admin');
    Route::get('transactions/activation-fees', [App\Http\Controllers\Admin\AdminTransactionController::class, 'activationFees'])->name('transactions.activation-fees');
    Route::get('transactions/statistics', [App\Http\Controllers\Admin\AdminTransactionController::class, 'statistics'])->name('transactions.statistics')
        ->middleware(\App\Http\Middleware\UnifiedPermissionMiddleware::class . ':view_admin_transactions');
    Route::get('transactions/client', [TransactionController::class, 'clientTransactions'])->name('transactions.client')
        ->middleware(\App\Http\Middleware\UnifiedPermissionMiddleware::class . ':view_transactions');
    Route::get('business-profiles/{businessProfile}/transactions', [TransactionController::class, 'businessProfileTransactions'])->name('transactions.business-profile')
        ->middleware(\App\Http\Middleware\UnifiedPermissionMiddleware::class . ':view_transactions');
    
    // Admin Transaction Actions
    Route::prefix('admin/transactions')->name('admin.transactions.')->middleware(['web', 'auth'])->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\AdminTransactionController::class, 'index'])->name('index');
        
        // Routes spécifiques AVANT la route générique {transaction}
        Route::get('/charging-point-creator-fees', [App\Http\Controllers\Admin\AdminTransactionController::class, 'chargingPointCreatorFees'])->name('charging-point-creator-fees');
        
        // Routes de correction des transactions
        Route::prefix('correction')->name('correction.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\TransactionCorrectionController::class, 'index'])->name('index');
            Route::post('/correct-all', [App\Http\Controllers\Admin\TransactionCorrectionController::class, 'correctAll'])->name('correct-all');
            Route::post('/correct-business-profile/{businessProfileId}', [App\Http\Controllers\Admin\TransactionCorrectionController::class, 'correctByBusinessProfile'])->name('correct-business-profile');
            Route::get('/report', [App\Http\Controllers\Admin\TransactionCorrectionController::class, 'generateReport'])->name('report');
        });

        Route::post('/calculate-fees', [App\Http\Controllers\Admin\AdminTransactionController::class, 'calculateFeesForChargingPoint'])->name('calculate-fees');
        
        // Routes POST
        Route::post('/create-debit', [App\Http\Controllers\Admin\AdminTransactionController::class, 'createAdminDebit'])->name('create-debit');
        Route::post('/export', [App\Http\Controllers\Admin\AdminTransactionController::class, 'export'])->name('export');
        Route::post('/reservations/{reservation}/approve', [App\Http\Controllers\Admin\AdminTransactionController::class, 'approveReservation'])->name('approve-reservation');
        
        // Route générique APRÈS les routes spécifiques
        Route::get('/{transaction}', [App\Http\Controllers\Admin\AdminTransactionController::class, 'show'])->name('show');
    });

    // Routes spécifiques pour les transactions (AVANT les routes resource)
    Route::get('transactions/history', [App\Http\Controllers\TransactionHistoryController::class, 'index'])->name('transactions.history');
    Route::get('transactions/hierarchical-summary', [App\Http\Controllers\TransactionHistoryController::class, 'getUserTransactionSummary'])->name('transactions.hierarchical-summary');
    Route::get('transactions/export', [App\Http\Controllers\TransactionHistoryController::class, 'export'])->name('transactions.export');
    Route::get('transactions/{transaction}/details', [TransactionController::class, 'showDetails'])->name('transactions.details');

    // Transaction resource routes (must come AFTER specific routes)
    Route::resource('transactions', TransactionController::class)
        ->middleware(\App\Http\Middleware\UnifiedPermissionMiddleware::class . ':view_transactions');

    // Reports
    Route::resource('reports', ReportController::class)->middleware(\App\Http\Middleware\UnifiedPermissionMiddleware::class . ':view_reports');

    // Remote Control - Commenté car redéfini plus bas
    // Route::resource('remote-control', RemoteControlController::class)->middleware('unified.permission' . ':manage_remote_control');

    // Accounts
    Route::resource('accounts', AccountController::class)->only(['index', 'show'])->middleware(\App\Http\Middleware\UnifiedPermissionMiddleware::class . ':view_accounts');

    // Financial Transactions
    Route::resource('financial-transactions', FinancialTransactionController::class)->only(['index', 'show'])->middleware(\App\Http\Middleware\UnifiedPermissionMiddleware::class . ':view_financial_transactions');

    // Reservation resource routes (authenticated)
    Route::resource('reservations', ReservationController::class)->except(['create', 'store', 'show']);

    // Custom reservation actions (authenticated)
    Route::middleware('auth')->group(function () {
        Route::post('reservations/{reservation}/end-charging', [ReservationController::class, 'endCharging'])->name('reservations.end-charging');
        Route::post('reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])->name('reservations.cancel');
        Route::post('reservations/{reservation}/confirm', [ReservationController::class, 'confirm'])->name('reservations.confirm');
        Route::post('reservations/{reservation}/start-charge', [ReservationController::class, 'startCharge'])->name('reservations.start-charge');
        Route::post('reservations/{reservation}/stop-charge', [ReservationController::class, 'stopCharge'])->name('reservations.stop-charge');
    });

    // Plans
    // Plan routes
    Route::get('/plans/{id}/activate', [PricingPlanController::class, 'activate'])->name('plans.activate');
    Route::prefix('plans')->name('plans.')->group(function () {
        Route::get('/', [PricingPlanController::class, 'index'])->name('index');
        Route::get('/create', [PricingPlanController::class, 'create'])->name('create');
        Route::post('/', [PricingPlanController::class, 'store'])->name('store');
        Route::get('/{plan}', [PricingPlanController::class, 'show'])->name('show');
        Route::get('/{plan}/edit', [PricingPlanController::class, 'edit'])->name('edit');
        Route::put('/{plan}', [PricingPlanController::class, 'update'])->name('update');
        Route::delete('/{plan}', [PricingPlanController::class, 'destroy'])->name('destroy');
        Route::post('/{plan}/apply-to-profiles', [PricingPlanController::class, 'applyToProfiles'])->name('apply-to-profiles');
    });

    // Temporary test route for pricing plan debugging
    Route::get('/test-pricing-plan', function () {
        return view('plans.test');
    })->name('plans.test');

    // Business Profiles
    Route::prefix('business-profiles')->name('business-profiles.')->middleware(\App\Http\Middleware\UnifiedPermissionMiddleware::class . ':view_business_profiles')->group(function () {
        Route::get('/', [BusinessProfileController::class, 'index'])->name('index');
        Route::get('/create', [BusinessProfileController::class, 'create'])->name('create')->middleware(\App\Http\Middleware\UnifiedPermissionMiddleware::class . ':create_business_profiles');
        Route::post('/', [BusinessProfileController::class, 'store'])->name('store')->middleware(\App\Http\Middleware\UnifiedPermissionMiddleware::class . ':create_business_profiles');
        Route::get('/{businessProfile}', [BusinessProfileController::class, 'show'])->name('show');
        Route::get('/{businessProfile}/edit', [BusinessProfileController::class, 'edit'])->name('edit')->middleware(\App\Http\Middleware\UnifiedPermissionMiddleware::class . ':edit_business_profiles');
        Route::put('/{businessProfile}', [BusinessProfileController::class, 'update'])->name('update')->middleware(\App\Http\Middleware\UnifiedPermissionMiddleware::class . ':edit_business_profiles');
        Route::delete('/{businessProfile}', [BusinessProfileController::class, 'destroy'])->name('destroy')->middleware(\App\Http\Middleware\UnifiedPermissionMiddleware::class . ':delete_business_profiles');
        Route::get('/{businessProfile}/manage-partner-rates', [BusinessProfileController::class, 'managePartnerRates'])->name('manage-partner-rates')->middleware(\App\Http\Middleware\UnifiedPermissionMiddleware::class . ':manage_partner_rates');
    });

    // Withdrawal Requests - Commenté car redéfini plus bas
    // Route::resource('withdrawal-requests', WithdrawalRequestController::class)->only(['index', 'show'])->middleware('unified.permission' . ':view_withdrawal_requests');

    // Refunds - Commenté car redéfini plus bas
    // Route::resource('refunds', RefundController::class)->only(['index', 'show'])->middleware('unified.permission' . ':view_refunds');

    // Commissions
    Route::prefix('commissions')->name('commissions.')->middleware(\App\Http\Middleware\UnifiedPermissionMiddleware::class . ':view_commission_settings')->group(function () {
        Route::get('/', [CommissionController::class, 'index'])->name('index');
        Route::get('/commission-dashboard', [CommissionController::class, 'dashboard'])->name('dashboard');
        Route::get('/reports', [CommissionController::class, 'reports'])->name('reports');
        Route::get('/export', [CommissionController::class, 'export'])->name('export');
        Route::post('/mark-multiple-paid', [CommissionController::class, 'markMultipleAsPaid'])->name('mark-multiple-paid');
        Route::get('/{transaction}', [CommissionController::class, 'show'])->name('show');
        Route::post('/{transaction}/recalculate', [CommissionController::class, 'recalculate'])->name('recalculate');
        Route::post('/{transaction}/mark-paid', [CommissionController::class, 'markAsPaid'])->name('mark-paid');
    });

    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::get('/general', [SettingsController::class, 'general'])->name('general');
        Route::put('/general', [SettingsController::class, 'updateGeneral'])->name('general.update');
        Route::get('/profile', [SettingsController::class, 'profile'])->name('profile');
        Route::put('/profile', [SettingsController::class, 'updateProfile'])->name('profile.update');
        Route::get('/security', [SettingsController::class, 'security'])->name('security');
        Route::put('/password', [SettingsController::class, 'updatePassword'])->name('password.update');
        Route::post('/two-factor', [SettingsController::class, 'toggleTwoFactor'])->name('two-factor.toggle');
        Route::post('/terminate-sessions', [SettingsController::class, 'terminateSessions'])->name('sessions.terminate');
        Route::post('/reset', [SettingsController::class, 'reset'])->name('reset');
        Route::get('/notifications', [SettingsController::class, 'notifications'])->name('notifications');
        Route::put('/notifications', [SettingsController::class, 'updateNotifications'])->name('notifications.update');
        Route::get('/api', [SettingsController::class, 'apiIndex'])->name('api.index');
        Route::get('/billing', [SettingsController::class, 'billing'])->name('billing');
        Route::post('/billing/{method}/default', [SettingsController::class, 'setDefaultPaymentMethod'])->name('billing.default');
        Route::get('/commission-plans', [SettingsController::class, 'commissionPlans'])->name('commission-plans');
        Route::get('/commission-dashboard', [SettingsController::class, 'commissionDashboard'])->name('commission-dashboard');
    });

    // Admin routes
    Route::prefix('admin')->name('admin.')->group(function () {        // Admin notifications
        Route::get('notifications/unread', [AdminNotificationController::class, 'getUnreadJson'])->name('notifications.unread');
        Route::post('notifications/{id}/mark-read', [AdminNotificationController::class, 'markAsRead'])->name('notifications.mark-read');
        Route::post('notifications/mark-all-read', [AdminNotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');

        // Admin Business Profiles
        Route::resource('business-profiles', AdminBusinessProfileController::class);
        Route::get('business-profiles/{businessProfile}/manage-partner-rates', [AdminBusinessProfileController::class, 'managePartnerRates'])->name('business-profiles.manage-partner-rates');

        // Users - Routes complètes avec noms corrects
        Route::prefix('users')->name('users.')->middleware(\App\Http\Middleware\UnifiedPermissionMiddleware::class . ':manage_users')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::get('/create', [UserController::class, 'create'])->name('create');
            Route::post('/', [UserController::class, 'store'])->name('store');
            Route::get('/{user}', [UserController::class, 'show'])->name('show');
            Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
            Route::put('/{user}', [UserController::class, 'update'])->name('update');
            Route::patch('/{user}', [UserController::class, 'update'])->name('patch');
            Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
        });
        
        // Commission Plans (Admin)
        Route::prefix('commission-plans')->name('commission-plans.')->group(function () {
            Route::get('/', [CommissionPlanController::class, 'index'])->name('index');
            Route::get('/create', [CommissionPlanController::class, 'create'])->name('create')->middleware(\App\Http\Middleware\UnifiedPermissionMiddleware::class . ':create_commission_plans');
            Route::post('/', [CommissionPlanController::class, 'store'])->name('store')->middleware(\App\Http\Middleware\UnifiedPermissionMiddleware::class . ':create_commission_plans');
            Route::get('/{commissionPlan}', [CommissionPlanController::class, 'show'])->name('show')->middleware(\App\Http\Middleware\UnifiedPermissionMiddleware::class . ':view_commission_settings');
            Route::get('/{commissionPlan}/edit', [CommissionPlanController::class, 'edit'])->name('edit')->middleware(\App\Http\Middleware\UnifiedPermissionMiddleware::class . ':edit_commission_plans');
            Route::put('/{commissionPlan}', [CommissionPlanController::class, 'update'])->name('update')->middleware(\App\Http\Middleware\UnifiedPermissionMiddleware::class . ':edit_commission_plans');
            Route::post('/{commissionPlan}/toggle-active', [CommissionPlanController::class, 'toggleActive'])->name('toggle-active')->middleware(\App\Http\Middleware\UnifiedPermissionMiddleware::class . ':edit_commission_plans');
            Route::post('/{commissionPlan}/set-default', [CommissionPlanController::class, 'setDefault'])->name('set-default')->middleware(\App\Http\Middleware\UnifiedPermissionMiddleware::class . ':edit_commission_plans');
            Route::post('/{commissionPlan}/recalculate', [CommissionPlanController::class, 'recalculateCommissions'])->name('recalculate')->middleware(\App\Http\Middleware\UnifiedPermissionMiddleware::class . ':edit_commission_plans');
            Route::delete('/{commissionPlan}', [CommissionPlanController::class, 'destroy'])->name('destroy')->middleware(\App\Http\Middleware\UnifiedPermissionMiddleware::class . ':delete_commission_plans');
        });
    });

    // Commandes de transaction (admin)
    Route::prefix('admin/orders')->name('admin.orders.')->group(function () {
        Route::get('/', [OrderAdminController::class, 'index'])->name('index');
        Route::patch('/{id}/confirm', [OrderAdminController::class, 'confirm'])->name('confirm');
        Route::patch('/{id}/cancel', [OrderAdminController::class, 'cancel'])->name('cancel');
        Route::get('/history', [OrderAdminController::class, 'history'])->name('history');
    
        // Admin Reservation Confirmation/Cancellation
    });

    // Admin Reservation Routes
    Route::prefix('admin/reservations')->name('admin.reservations.')->middleware(\App\Http\Middleware\UnifiedPermissionMiddleware::class . ':view_reservations')->group(function () {
        Route::get('/', [AdminReservationController::class, 'index'])->name('index');
        Route::get('/all', [AdminReservationController::class, 'index'])->name('all'); // Route manquante pour la sidebar
        Route::get('/create', [AdminReservationController::class, 'create'])->name('create');
        Route::post('/', [AdminReservationController::class, 'store'])->name('store');
        Route::get('/{reservation}', [AdminReservationController::class, 'show'])->name('show');
        Route::get('/{reservation}/edit', [AdminReservationController::class, 'edit'])->name('edit');
        Route::put('/{reservation}', [AdminReservationController::class, 'update'])->name('update');
        Route::get('/{reservation}/confirm', [AdminReservationController::class, 'showConfirmForm'])->name('confirm.show');
        Route::post('/{reservation}/confirm', [AdminReservationController::class, 'confirm'])->name('confirm')->middleware(\App\Http\Middleware\UnifiedPermissionMiddleware::class . ':confirm_reservations');
        Route::post('/{reservation}/confirm-with-custom-fees', [AdminReservationController::class, 'confirmWithCustomFees'])->name('confirm-with-custom-fees')->middleware(\App\Http\Middleware\UnifiedPermissionMiddleware::class . ':confirm_reservations');
        Route::post('/{reservation}/reject', [AdminReservationController::class, 'reject'])->name('reject')->middleware(\App\Http\Middleware\UnifiedPermissionMiddleware::class . ':manage_reservations');
        Route::get('/{reservation}/confirm-with-cost', [AdminReservationController::class, 'showConfirmWithCostForm'])->name('confirm-with-cost.show');
        Route::put('/{reservation}/confirm-with-cost', [AdminReservationController::class, 'confirmWithCost'])->name('confirm-with-cost.update')->middleware(\App\Http\Middleware\UnifiedPermissionMiddleware::class . ':confirm_reservations');
    });

    // Admin Transaction Fee Routes
    Route::prefix('admin/transaction-fees')->name('admin.transaction-fees.')->group(function () {
        Route::get('/transaction/{transaction}', [App\Http\Controllers\Admin\TransactionFeeController::class, 'showTransactionFees'])->name('transaction.show');
        Route::get('/reservation/{reservation}', [App\Http\Controllers\Admin\TransactionFeeController::class, 'showReservationFees'])->name('reservation.show');
        Route::get('/transaction/{transaction}/custom', [App\Http\Controllers\Admin\TransactionFeeController::class, 'showCustomFees'])->name('transaction.custom');
        Route::get('/transaction/{transaction}/export', [App\Http\Controllers\Admin\TransactionFeeController::class, 'exportFees'])->name('transaction.export');
        Route::get('/compare', [App\Http\Controllers\Admin\TransactionFeeController::class, 'compareFees'])->name('compare');
    });

    // Temporary debug route for permissions
    Route::get('/debug/check-permissions', function () {
        if (!auth()->check()) {
            return response()->json(['message' => 'User not authenticated.'], 401);
        }
        $user = auth()->user();
        return response()->json([
            'user_id' => $user->id,
            'user_email' => $user->email,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'can_manage_reservations' => $user->can('manage_reservations'),
        ]);
    })->name('debug.check-permissions');

    // Debug routes
    Route::get('/debug/user-permissions', function () {
        if (!auth()->check()) {
            return 'Please login first.';
        }
        $user = auth()->user();
        $permissions = $user->getAllPermissions();
        $roles = $user->getRoleNames();
        return response()->json([
            'user_id' => $user->id,
            'roles' => $roles,
            'permissions' => $permissions->pluck('name'),
        ]);
    });

    Route::get('/test-clean-urls', function () {
        $testUrls = [
            '/integrators',
            '/groups',
            '/partners',
            '/charging-points',
            '/integrators/1',
            '/groups/5',
        ];

        $results = [];
        foreach ($testUrls as $url) {
            $results[$url] = [
                'user_id' => auth()->id(),
                'is_admin' => auth()->check() && auth()->id() === 1,
                'route_exists' => Route::has(str_replace('/', '.', trim($url, '/'))),
                'would_redirect' => auth()->check() && auth()->id() === 1
            ];
        }

        if (auth()->check()) {
            $user = auth()->user();
            $results['user_permissions'] = $user->getAllPermissions()->pluck('name');
            $results['user_roles'] = $user->getRoleNames();
        } else {
            $results['user_permissions'] = [];
            $results['user_roles'] = [];
        }

        return response()->json($results, 200, [], JSON_PRETTY_PRINT);
    });

    // Temporary route to test BusinessProfile delete policy
    Route::get('/test-delete-policy/{businessProfile}', function (BusinessProfile $businessProfile) {
        $allowed = Gate::allows('delete', $businessProfile);

        Log::info('Test Delete Policy Route: Policy check result', [
            'user_id' => Auth::id(),
            'business_profile_id' => $businessProfile->id,
            'allowed' => $allowed
        ]);

        if ($allowed) {
            return "User is authorized to delete Business Profile ID {$businessProfile->id}. Check logs for details.";
        } else {
            return "User is NOT authorized to delete Business Profile ID {$businessProfile->id}. Check logs for details.";
        }
    })->name('test.delete.policy');

    // Temporary route to grant business profile view permission
    Route::get('/grant-business-profile-permission', function () {
        if (!auth()->check()) {
            return 'Please login first.';
        }
        $user = auth()->user();
        $permissionName = 'view_business_profiles';
        if (!$user->hasPermissionTo($permissionName)) {
            $user->givePermissionTo($permissionName);
            return "Permission '{$permissionName}' granted to user ID {$user->id}.";
        }
        return "User ID {$user->id} already has permission '{$permissionName}'.";
    })->name('grant.business-profile.permission');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::get('/debug/grant-edit-groups-permission', function () {
        if (!auth()->check()) {
            return 'Please login first.';
        }
        $user = auth()->user();
        $permissionName = 'edit_groups';
        if (!$user->hasPermissionTo($permissionName)) {
            $user->givePermissionTo($permissionName);
            return "Permission '{$permissionName}' granted to user ID {$user->id}.";
        }
        return "User ID {$user->id} already has permission '{$permissionName}'.";
    })->name('debug.grant-edit-groups-permission');
Route::get('/debug/user-roles-permissions', function () {
    if (!auth()->check()) {
        return 'Non connecté.';
    }
    $user = auth()->user();
    return response()->json([
        'user_id' => $user->id,
        'email' => $user->email,
        'roles' => $user->getRoleNames(),
        'permissions' => $user->getAllPermissions()->pluck('name'),
        'integrator_id' => $user->integrator_id ?? null,
        'partner_id' => $user->partner_id ?? null,
    ]);
})->name('debug.user-roles-permissions');

Route::get('/debug/partner-info/{partner}', function (\App\Models\Partner $partner) {
    if (!auth()->check()) {
        return 'Non connecté.';
    }
    return response()->json([
        'partner_id' => $partner->id,
        'partner_name' => $partner->name,
        'partner_integrator_id' => $partner->integrator_id,
    ]);
})->name('debug.partner-info');

// Temporary debug routes - remove after testing
Route::middleware(['auth'])->group(function () {
    Route::get('/debug/plan-creation', function () {
        $vatRates = VatRate::getActiveRates();
        $defaultVatRate = VatRate::getDefault();
        return view('plans.create', compact('vatRates', 'defaultVatRate'));
    })->name('debug.plan-creation');

    Route::post('/debug/plan-store', [PricingPlanController::class, 'store'])
        ->name('debug.plan-store');
});

// Temporary debug route to regenerate QR code
Route::middleware(['auth'])->group(function () {
    Route::get('/debug/regenerate-qrcode/{id}', function ($id) {
        try {
            $qrCodeService = app(\App\Services\ChargingPointQRCodeService::class);
            $result = $qrCodeService->regenerate($id);
            return response()->json(['status' => 'success', 'message' => 'QR code regenerated successfully.', 'data' => $result]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Failed to regenerate QR code: ' . $e->getMessage()], 500);
        }
    })->name('debug.regenerate-qrcode');
});

// Public routes (outside auth middleware)
Route::get('/charging-points/{id}/offer/view', [ChargingPointViewController::class, 'showOffer'])
    ->name('public.charging-point.offer.view');

// Public route with /public prefix for charging point offer view
Route::get('/public/charging-points/{id}/offer/view', [ChargingPointViewController::class, 'showOffer'])
    ->name('public.charging-point.offer.view.public');

Route::get('/public/charging-points/{chargingPoint}/qr-code', [ChargingPointViewController::class, 'qrCode'])
    ->name('public.charging-points.qr-code')
    ->middleware('auth');

Route::get('/offre/{id}', [OfferController::class, 'show'])->name('offre.show');

// Route publique pour l'offre de borne (SANS AUTH)
Route::get('/offer/{id}', [OfferController::class, 'show'])
    ->name('public.charging-point.offer')
    ->withoutMiddleware('auth'); // Explicitement retirer le middleware auth

// Route publique pour l'offre de borne avec le préfixe /public

// Route publique pour l'offre de réservation (SANS AUTH)
Route::get('/offer/{id}/reservation', [\App\Http\Controllers\ChargingPointOfferController::class, 'showReservationOfferPublic'])
    ->name('public.charging-point.offer.reservation')
    ->withoutMiddleware('auth'); // Explicitement retirer le middleware auth

// Route de test pour vérifier l'accès public (SANS AUTH)
Route::get('/test-public', function () {
    return view('layouts.public', ['content' => '<h1>Test Public Access</h1><p>Cette page est accessible sans authentification.</p>']);
})->name('test.public')
->withoutMiddleware('auth'); // Explicitement retirer le middleware auth

// Route de test pour la réservation sans authentification (SANS AUTH)
Route::get('/test-reservation/{id}', function ($id) {
    try {
        $chargingPoint = \App\Models\ChargingPoint::findOrFail($id);
        return view('layouts.public', [
            'content' => '<h1>Test Réservation</h1><p>Borne: ' . $chargingPoint->name . '</p><p>ID: ' . $chargingPoint->id . '</p>'
        ]);
    } catch (\Exception $e) {
        return view('layouts.public', [
            'content' => '<h1>Erreur</h1><p>Erreur: ' . $e->getMessage() . '</p>'
        ]);
    }
})->name('test.reservation')
->withoutMiddleware('auth'); // Explicitement retirer le middleware auth

// Public reservation creation routes (no authentication required) - SANS AUTH
Route::post('reservations/store/{chargingPoint}', [ReservationController::class, 'store'])->name('reservations.store')
->withoutMiddleware('auth');
Route::get('reservations/create/{chargingPoint}', [ReservationController::class, 'create'])->name('reservations.create')
->withoutMiddleware('auth');
Route::post('reservations/calculate-cost/{chargingPoint}', [ReservationController::class, 'calculateCost'])->name('reservations.calculate-cost')
->withoutMiddleware('auth');
Route::get('reservations/plan-limits/{chargingPoint}', [ReservationController::class, 'getPlanLimits'])->name('reservations.plan-limits')
->withoutMiddleware('auth');
Route::get('reservations/thank-you/{reservation}', [ReservationController::class, 'thankYou'])->name('reservations.thank-you')
->withoutMiddleware('auth');

// Public cost calculation route (no authentication required) - SANS AUTH
Route::post('/calculate-cost/{charging_point_id}', [OfferController::class, 'calculateCost'])->name('calculate-cost')
->withoutMiddleware('auth');

// Public reservation actions (for guest users) - SANS AUTH
Route::post('reservations/{reservation}/start-charging', [ReservationController::class, 'startCharging'])->name('reservations.start-charging')
->withoutMiddleware('auth');

// Routes pour le processus de recharge
// Route::prefix('charging')->name('charging.')->group(function () {
//     Route::get('/start/{chargingPointId}', [\App\Http\Controllers\ChargingSessionController::class, 'showStartForm'])->name('start.form');
//     Route::post('/start/{chargingPointId}', [\App\Http\Controllers\ChargingSessionController::class, 'startSession'])->name('start');
//     Route::get('/monitor/{sessionId}/{token}', [\App\Http\Controllers\ChargingSessionController::class, 'monitor'])->name('monitor');
//     Route::post('/stop/{sessionId}', [\App\Http\Controllers\ChargingSessionController::class, 'stopSession'])->name('stop');
//     Route::get('/receipt/{sessionId}', [\App\Http\Controllers\ChargingSessionController::class, 'receipt'])->name('receipt');
// });

Route::get('/test-leaflet', function () {
    return view('charging-points.test-leaflet');
});

Route::get('/test-notif', function () {
    $admin = \App\Models\User::role('admin')->first();
    $admin->notify(new \App\Notifications\SystemAdminNotification('Test', 'Ceci est un test'));
    return 'Notification envoyée';
});

// Debug route to test charging point existence
Route::get('/debug/charging-point/{id}', function ($id) {
    try {
        $chargingPoint = \App\Models\ChargingPoint::with(['pricingPlan', 'partner', 'integrator', 'group', 'station'])->find($id);
        if ($chargingPoint) {
            return response()->json([
                'exists' => true,
                'id' => $chargingPoint->id,
                'name' => $chargingPoint->name,
                'status' => $chargingPoint->status,
                'pricing_plan' => $chargingPoint->pricingPlan ? $chargingPoint->pricingPlan->name : 'No plan'
            ]);
        } else {
            return response()->json(['exists' => false, 'message' => 'Charging point not found']);
        }
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
});

// Routes publiques pour les notifications
Route::prefix('public/notifications')->name('public.notifications.')->group(function () {
    Route::get('/', [App\Http\Controllers\PublicNotificationController::class, 'index'])->name('index');
    Route::get('/{id}', [App\Http\Controllers\PublicNotificationController::class, 'show'])->name('show');
    Route::get('/system', [App\Http\Controllers\PublicNotificationController::class, 'getSystemNotifications'])->name('system');
    Route::get('/announcements', [App\Http\Controllers\PublicNotificationController::class, 'getAnnouncements'])->name('announcements');
    Route::post('/{id}/viewed', [App\Http\Controllers\PublicNotificationController::class, 'markAsViewed'])->name('viewed');
    Route::post('/webhook', [App\Http\Controllers\PublicNotificationController::class, 'webhook'])->name('webhook');
});

// Routes pour les utilisateurs du système
Route::middleware(['web', App\Http\Middleware\AuthenticateSystemUser::class])->prefix('system-users')->group(function () {
    Route::get('/', [App\Http\Controllers\SystemUserController::class, 'index'])->name('system-users.index');
    // Ajoutez ici d'autres routes CRUD pour SystemUser
});

// Routes pour les clients
Route::middleware(['web', App\Http\Middleware\AuthenticateClientUser::class])->prefix('client-users')->group(function () {
    Route::get('/', [App\Http\Controllers\ClientUserController::class, 'index'])->name('client-users.index');
    // Ajoutez ici d'autres routes CRUD pour ClientUser
});

// Language routes (with web middleware to ensure SetLocale is applied)
Route::middleware(['web'])->group(function () {
    Route::get('/language/{locale}', [App\Http\Controllers\LanguageController::class, 'switchLanguage'])->name('language.switch');
});

// Test route for language switching
Route::get('/test-lang-switch/{locale}', function($locale) {
    // Force set the locale
    app()->setLocale($locale);
    session(['locale' => $locale]);
    config(['app.locale' => $locale]);
    session()->save();
    
    return response()->json([
        'success' => true,
        'locale' => $locale,
        'app_locale' => app()->getLocale(),
        'session_locale' => session('locale'),
        'welcome_message' => __('messages.welcome'),
        'redirect_url' => url()->previous()
    ]);
})->name('test.lang.switch');

// Simple language switch with redirect
Route::get('/lang-switch/{locale}', function($locale) {
    return redirect(route('language.switch', ['locale' => $locale]));
})->name('lang.switch.simple.redirect');

// Test session persistence
Route::get('/test-session', function() {
    return response()->json([
        'app_locale' => app()->getLocale(),
        'session_locale' => session('locale'),
        'config_locale' => config('app.locale'),
        'session_id' => session()->getId(),
        'welcome_message' => __('messages.welcome'),
        'timestamp' => now()->toDateTimeString()
    ]);
})->name('test.session');

// Simple language test page
Route::get('/lang-test-page', function() {
    return view('lang-test-page');
})->name('lang.test.page');

// Translation test page
Route::get('/translation-test', function() {
    return view('translation-test');
})->name('translation.test');

// Simple language test route
Route::get('/test-lang/{locale}', function($locale) {
    try {
        echo "Testing language switch to: " . $locale . "\n";
        echo "Available locales: " . json_encode(config('app.available_locales')) . "\n";
        
        if (!in_array($locale, config('app.available_locales'))) {
            return response('Language not supported: ' . $locale, 400);
        }
        
        app()->setLocale($locale);
        session(['locale' => $locale]);
        
        echo "App locale: " . app()->getLocale() . "\n";
        echo "Session locale: " . session('locale') . "\n";
        echo "Welcome message: " . __('messages.welcome') . "\n";
        
        return response('Language switched successfully to ' . $locale);
    } catch (Exception $e) {
        return response('Error: ' . $e->getMessage(), 500);
    }
})->name('test.lang.simple');

// Simple language test page
Route::get('/simple-lang-test', function() {
    return view('simple-lang-test');
})->name('simple.lang.test');

// JavaScript language test page
Route::get('/js-lang-test', function() {
    return view('js-lang-test');
})->name('js.lang.test');

// Simple test page
Route::get('/simple-test', function() {
    return view('simple-test');
})->name('simple.test');

// Custom register page with 3D model
Route::get('/register-custom', function() {
    return view('auth.register-custom');
})->name('register.custom');

// Public offer routes (no authentication required)
Route::post('/offer/{id}/reserve', [ReservationController::class, 'store'])->name('offer.reserve');

Route::post('/offer/{id}/reservation', [App\Http\Controllers\PublicChargingOfferWebController::class, 'storeReservation'])
    ->name('public.charging-point.offer.store-reservation.web');

// Route publique pour le calcul du coût d'une réservation
// Route::post('/calculate-cost/{chargingPoint}', [App\Http\Controllers\ReservationController::class, 'calculateCost'])->name('calculate-cost');
// Guest reservation access routes (no authentication required)
Route::prefix('guest-reservations')->name('guest-reservations.')->group(function () {
    Route::get('/', [ReservationController::class, 'guestIndex'])->name('index');
    Route::post('/search', [ReservationController::class, 'guestSearch'])->name('search');
    Route::get('/access', [ReservationController::class, 'accessReservationsForm'])->name('access.form');
    Route::post('/access', [ReservationController::class, 'searchReservations'])->name('access.search');
});
Route::get('/payment/status/{orderId}', [\App\Http\Controllers\PaymentController::class, 'checkStatus']);

// Routes pour le contrôle à distance
Route::middleware(['auth'])->group(function () {
    Route::prefix('remote-control')->name('remote-control.')->group(function () {
        Route::get('/', [RemoteControlController::class, 'index'])->name('index');
        Route::get('/charging-points', [RemoteControlController::class, 'getChargingPoints'])->name('charging-points');
        Route::get('/{chargingPoint}', [RemoteControlController::class, 'show'])->name('show');
        
        // Actions OCPP
        Route::post('/{chargingPoint}/start-charging', [RemoteControlController::class, 'startCharging'])->name('start-charging');
        Route::post('/{chargingPoint}/stop-charging', [RemoteControlController::class, 'stopCharging'])->name('stop-charging');
        Route::post('/{chargingPoint}/unlock-connector', [RemoteControlController::class, 'unlockConnector'])->name('unlock-connector');
        Route::post('/{chargingPoint}/reset', [RemoteControlController::class, 'resetChargingPoint'])->name('reset');
        Route::post('/{chargingPoint}/update-config', [RemoteControlController::class, 'updateConfiguration'])->name('update-config');
        Route::post('/{chargingPoint}/diagnostics', [RemoteControlController::class, 'getDiagnostics'])->name('diagnostics');
        Route::post('/{chargingPoint}/logs', [RemoteControlController::class, 'getLogs'])->name('logs');
        Route::get('/{chargingPoint}/connection-status', [RemoteControlController::class, 'checkConnectionStatus'])->name('connection-status');
        Route::get('/{chargingPoint}/action-log', [RemoteControlController::class, 'getActionLog'])->name('action-log');
    });
});

// Routes pour les plans tarifaires améliorées
Route::middleware(['auth'])->group(function () {
    Route::prefix('pricing-plans')->name('pricing-plans.')->group(function () {
        Route::get('/', [PricingPlanController::class, 'index'])->name('index');
        Route::get('/create', [PricingPlanController::class, 'create'])->name('create');
        Route::post('/', [PricingPlanController::class, 'store'])->name('store');
        Route::get('/{pricingPlan}', [PricingPlanController::class, 'show'])->name('show');
        Route::get('/{pricingPlan}/edit', [PricingPlanController::class, 'edit'])->name('edit');
        Route::put('/{pricingPlan}', [PricingPlanController::class, 'update'])->name('update');
        Route::delete('/{pricingPlan}', [PricingPlanController::class, 'destroy'])->name('destroy');
        
        // Actions supplémentaires
        Route::post('/{pricingPlan}/duplicate', [PricingPlanController::class, 'duplicate'])->name('duplicate');
        Route::post('/{pricingPlan}/deactivate', [PricingPlanController::class, 'deactivate'])->name('deactivate');
        Route::post('/{pricingPlan}/calculate-price', [PricingPlanController::class, 'calculatePrice'])->name('calculate-price');
        Route::get('/{pricingPlan}/stats', [PricingPlanController::class, 'getStats'])->name('stats');
    });
});

// Routes pour les abonnements
Route::middleware(['auth'])->group(function () {
    Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
        Route::get('/', [SubscriptionController::class, 'index'])->name('index');
        Route::get('/create', [SubscriptionController::class, 'create'])->name('create');
        Route::post('/', [SubscriptionController::class, 'store'])->name('store');
        Route::get('/{subscription}', [SubscriptionController::class, 'show'])->name('show');
        Route::get('/{subscription}/edit', [SubscriptionController::class, 'edit'])->name('edit');
        Route::put('/{subscription}', [SubscriptionController::class, 'update'])->name('update');
        Route::delete('/{subscription}', [SubscriptionController::class, 'destroy'])->name('destroy');
        
        // Actions spécifiques aux abonnements
        Route::post('/{subscription}/activate', [SubscriptionController::class, 'activate'])->name('activate');
        Route::post('/{subscription}/deactivate', [SubscriptionController::class, 'deactivate'])->name('deactivate');
        Route::post('/{subscription}/renew', [SubscriptionController::class, 'renew'])->name('renew');
        Route::get('/{subscription}/usage', [SubscriptionController::class, 'getUsage'])->name('usage');
    });
});

// Routes pour les demandes de retrait
Route::middleware(['auth'])->group(function () {
    Route::prefix('withdrawal-requests')->name('withdrawal-requests.')->group(function () {
        Route::get('/', [WithdrawalRequestController::class, 'index'])->name('index');
        Route::get('/create', [WithdrawalRequestController::class, 'create'])->name('create');
        Route::post('/', [WithdrawalRequestController::class, 'store'])->name('store');
        Route::get('/{withdrawalRequest}', [WithdrawalRequestController::class, 'show'])->name('show');
        Route::put('/{withdrawalRequest}', [WithdrawalRequestController::class, 'update'])->name('update');
        
        // Actions d'approbation
        Route::post('/{withdrawalRequest}/approve', [WithdrawalRequestController::class, 'approve'])->name('approve');
        Route::post('/{withdrawalRequest}/reject', [WithdrawalRequestController::class, 'reject'])->name('reject');
        Route::post('/{withdrawalRequest}/process', [WithdrawalRequestController::class, 'process'])->name('process');
    });
});

// Routes pour les remboursements
Route::middleware(['auth'])->group(function () {
    Route::prefix('refunds')->name('refunds.')->group(function () {
        Route::get('/', [RefundController::class, 'index'])->name('index');
        Route::get('/create', [RefundController::class, 'create'])->name('create');
        Route::post('/', [RefundController::class, 'store'])->name('store');
        Route::get('/{refund}', [RefundController::class, 'show'])->name('show');
        Route::put('/{refund}', [RefundController::class, 'update'])->name('update');
        
        // Actions de remboursement
        Route::post('/{refund}/process', [RefundController::class, 'process'])->name('process');
        Route::post('/{refund}/cancel', [RefundController::class, 'cancel'])->name('cancel');
    });
});

// Route pour la correction automatique des business profiles
Route::post('/run-business-profile-fix', function () {
    try {
        // Exécuter le script de correction
        $output = shell_exec('php fix_business_profile_charging_points_complete.php 2>&1');
        
        // Vérifier si la commande s'est bien exécutée
        if ($output === null) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'exécution du script'
            ]);
        }
        
        // Analyser la sortie pour détecter les succès/erreurs
        $success = strpos($output, 'SUCCÈS') !== false || strpos($output, 'Transaction validée') !== false;
        
        return response()->json([
            'success' => $success,
            'message' => $success ? 'Correction terminée avec succès' : 'Erreur lors de la correction',
            'output' => $output
        ]);
        
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur : ' . $e->getMessage()
        ]);
    }
})->middleware('auth');

// Routes de test QR Code SVG (sans GD)
Route::get('/test-qr-code', [QRCodeTestController::class, 'testQRCode']);
Route::get('/qr-code/{id}', [QRCodeTestController::class, 'showQRCode']);

// Route de test pour le design restauré
Route::get('/test-app-design', function () {
    return view('test-app-design');
})->name('test.app.design');

// Route de test simple pour le design
Route::get('/test-simple', function () {
    return view('test-simple');
})->name('test.simple');

// Routes pour les transactions hiérarchiques
Route::middleware(['auth'])->group(function () {
    Route::prefix('hierarchical-transactions')->name('hierarchical-transactions.')->group(function () {
        Route::post('/reservation/{reservation}/create', [HierarchicalTransactionController::class, 'createForReservation'])
            ->name('create-for-reservation');
        Route::get('/reservation/{reservation}/summary', [HierarchicalTransactionController::class, 'getSummary'])
            ->name('get-summary');
        Route::post('/simulate', [HierarchicalTransactionController::class, 'simulate'])
            ->name('simulate');
    });
});

// Routes pour l'historique des transactions
Route::middleware(['auth'])->group(function () {
    // Routes Admin
    Route::prefix('admin')->name('admin.')->middleware(\App\Http\Middleware\AdminRoleMiddleware::class)->group(function () {
        // Charging Point routes for admins - Using same controllers as public routes
        Route::prefix('charging-points')->name('charging-points.')->group(function () {
            // Main routes using public controllers for consistency
            Route::get('/', [\App\Http\Controllers\UnifiedChargingPointController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\ChargingPointViewController::class, 'createStep1'])->name('create');
            Route::post('/', [\App\Http\Controllers\AdminChargingPointController::class, 'store'])->name('store');
            Route::get('/{chargingPoint}', [\App\Http\Controllers\UnifiedChargingPointController::class, 'show'])->name('show');
            Route::get('/{chargingPoint}/edit', [\App\Http\Controllers\UnifiedChargingPointController::class, 'edit'])->name('edit');
            Route::put('/{chargingPoint}', [\App\Http\Controllers\UnifiedChargingPointController::class, 'update'])->name('update');
            Route::delete('/{chargingPoint}', [\App\Http\Controllers\UnifiedChargingPointController::class, 'destroy'])->name('destroy');
            
            // Multi-step creation routes (same as public)
            Route::prefix('create')->name('create.')->group(function () {
                Route::get('/step1', [\App\Http\Controllers\ChargingPointViewController::class, 'createStep1'])->name('step1');
                Route::post('/step1', [\App\Http\Controllers\ChargingPointViewController::class, 'storeStep1'])->name('store.step1');
                Route::get('/step2', [\App\Http\Controllers\ChargingPointViewController::class, 'createStep2'])->name('step2');
                Route::post('/step2', [\App\Http\Controllers\ChargingPointViewController::class, 'storeStep2'])->name('store.step2');
                Route::get('/step3', [\App\Http\Controllers\ChargingPointViewController::class, 'createStep3'])->name('step3');
                Route::post('/step3', [\App\Http\Controllers\ChargingPointViewController::class, 'storeStep3'])->name('store.step3');
                Route::get('/confirm', [\App\Http\Controllers\ChargingPointViewController::class, 'confirm'])->name('confirm');
                Route::post('/store', [\App\Http\Controllers\ChargingPointViewController::class, 'store'])->name('store.final');
                Route::get('/success/{charging_point}', [\App\Http\Controllers\ChargingPointViewController::class, 'success'])->name('success');
            });
            
            // Admin-specific routes
            Route::post('/{chargingPoint}/toggle-status', [\App\Http\Controllers\AdminChargingPointController::class, 'toggleStatus'])->name('toggle-status');
            Route::post('/{chargingPoint}/assign', [\App\Http\Controllers\AdminChargingPointController::class, 'assign'])->name('assign');
            Route::get('/export/charging-points', [\App\Http\Controllers\AdminChargingPointController::class, 'export'])->name('export');
            Route::get('/statistics/charging-points', [\App\Http\Controllers\AdminChargingPointController::class, 'statistics'])->name('statistics');
        });
        Route::get('/transaction-history', [TransactionHistoryController::class, 'adminIndex'])
            ->name('transaction-history.index');
        Route::get('/transaction-history/export', [TransactionHistoryController::class, 'export'])
            ->name('transaction-history.export');
        Route::get('/transaction-history/{transaction}/details', [TransactionHistoryController::class, 'getTransactionDetails'])
            ->name('transaction-history.details');
    });
    
    // Routes Intégrateur
    Route::prefix('integrator')->name('integrator.')->group(function () {
        Route::get('/transaction-history', [TransactionHistoryController::class, 'integratorIndex'])
            ->name('transaction-history.index');
        Route::get('/transaction-history/export', [TransactionHistoryController::class, 'export'])
            ->name('transaction-history.export');
        Route::get('/transaction-history/{transaction}/details', [TransactionHistoryController::class, 'getTransactionDetails'])
            ->name('transaction-history.details');
        
        // Business Profile routes for integrators
        Route::prefix('business-profiles')->name('business-profiles.')->group(function () {
            Route::get('/', [\App\Http\Controllers\IntegratorBusinessProfileController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\IntegratorBusinessProfileController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\IntegratorBusinessProfileController::class, 'store'])->name('store');
            Route::get('/{businessProfile}', [\App\Http\Controllers\IntegratorBusinessProfileController::class, 'show'])->name('show');
            Route::get('/{businessProfile}/edit', [\App\Http\Controllers\IntegratorBusinessProfileController::class, 'edit'])->name('edit');
            Route::put('/{businessProfile}', [\App\Http\Controllers\IntegratorBusinessProfileController::class, 'update'])->name('update');
            Route::delete('/{businessProfile}', [\App\Http\Controllers\IntegratorBusinessProfileController::class, 'destroy'])->name('destroy');
        });
        
        // Group routes for integrators
        Route::prefix('groups')->name('groups.')->group(function () {
            Route::get('/', [\App\Http\Controllers\IntegratorGroupController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\IntegratorGroupController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\IntegratorGroupController::class, 'store'])->name('store');
            Route::get('/{group}', [\App\Http\Controllers\IntegratorGroupController::class, 'show'])->name('show');
            Route::get('/{group}/edit', [\App\Http\Controllers\IntegratorGroupController::class, 'edit'])->name('edit');
            Route::put('/{group}', [\App\Http\Controllers\IntegratorGroupController::class, 'update'])->name('update');
            Route::delete('/{group}', [\App\Http\Controllers\IntegratorGroupController::class, 'destroy'])->name('destroy');
            Route::post('/{group}/toggle-status', [\App\Http\Controllers\IntegratorGroupController::class, 'toggleStatus'])->name('toggle-status');
            Route::get('/api/groups', [\App\Http\Controllers\IntegratorGroupController::class, 'getGroups'])->name('api.groups');
        });
        
        // Operator routes for integrators
        Route::prefix('operators')->name('operators.')->group(function () {
            Route::get('/', [\App\Http\Controllers\IntegratorOperatorController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\IntegratorOperatorController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\IntegratorOperatorController::class, 'store'])->name('store');
            Route::get('/{operator}', [\App\Http\Controllers\IntegratorOperatorController::class, 'show'])->name('show');
            Route::get('/{operator}/edit', [\App\Http\Controllers\IntegratorOperatorController::class, 'edit'])->name('edit');
            Route::put('/{operator}', [\App\Http\Controllers\IntegratorOperatorController::class, 'update'])->name('update');
            Route::delete('/{operator}', [\App\Http\Controllers\IntegratorOperatorController::class, 'destroy'])->name('destroy');
            Route::post('/{operator}/toggle-status', [\App\Http\Controllers\IntegratorOperatorController::class, 'toggleStatus'])->name('toggle-status');
            Route::post('/{operator}/reset-password', [\App\Http\Controllers\IntegratorOperatorController::class, 'resetPassword'])->name('reset-password');
        });
    });
    
    // Routes Opérateur
    Route::prefix('operator')->name('operator.')->group(function () {
        Route::get('/transaction-history', [TransactionHistoryController::class, 'operatorIndex'])
            ->name('transaction-history.index');
        Route::get('/transaction-history/export', [TransactionHistoryController::class, 'export'])
            ->name('transaction-history.export');
        Route::get('/transaction-history/{transaction}/details', [TransactionHistoryController::class, 'getTransactionDetails'])
            ->name('transaction-history.details');
        
        // Charging Point routes for operators
        Route::prefix('charging-points')->name('charging-points.')->group(function () {
            Route::get('/', [\App\Http\Controllers\OperatorChargingPointController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\OperatorChargingPointController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\OperatorChargingPointController::class, 'store'])->name('store');
            Route::get('/{chargingPoint}', [\App\Http\Controllers\OperatorChargingPointController::class, 'show'])->name('show');
            Route::get('/{chargingPoint}/edit', [\App\Http\Controllers\OperatorChargingPointController::class, 'edit'])->name('edit');
            Route::put('/{chargingPoint}', [\App\Http\Controllers\OperatorChargingPointController::class, 'update'])->name('update');
            Route::delete('/{chargingPoint}', [\App\Http\Controllers\OperatorChargingPointController::class, 'destroy'])->name('destroy');
            Route::post('/{chargingPoint}/toggle-status', [\App\Http\Controllers\OperatorChargingPointController::class, 'toggleStatus'])->name('toggle-status');
        });
        
        // Pricing Plan routes for operators
        Route::prefix('pricing-plans')->name('pricing-plans.')->group(function () {
            Route::get('/', [\App\Http\Controllers\OperatorPricingPlanController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\OperatorPricingPlanController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\OperatorPricingPlanController::class, 'store'])->name('store');
            Route::get('/{pricingPlan}', [\App\Http\Controllers\OperatorPricingPlanController::class, 'show'])->name('show');
            Route::get('/{pricingPlan}/edit', [\App\Http\Controllers\OperatorPricingPlanController::class, 'edit'])->name('edit');
            Route::put('/{pricingPlan}', [\App\Http\Controllers\OperatorPricingPlanController::class, 'update'])->name('update');
            Route::delete('/{pricingPlan}', [\App\Http\Controllers\OperatorPricingPlanController::class, 'destroy'])->name('destroy');
            Route::post('/{pricingPlan}/toggle-status', [\App\Http\Controllers\OperatorPricingPlanController::class, 'toggleStatus'])->name('toggle-status');
            Route::get('/api/pricing-plans', [\App\Http\Controllers\OperatorPricingPlanController::class, 'getPricingPlans'])->name('api.pricing-plans');
        });
    });
    
    // Routes API communes
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/transaction-history', [TransactionHistoryController::class, 'getHistory'])
            ->name('transaction-history.get');
        Route::get('/transaction-history/stats', [TransactionHistoryController::class, 'getStats'])
            ->name('transaction-history.stats');
    });
});

// Route pour le dashboard moderne
Route::get('/dashboard-modern', function () {
    return view('dashboard-modern', [
        'totalRecharges' => 1250,
        'rechargesActives' => 8,
        'abonnementsActifs' => 45,
        'revenusTotaux' => 125000,
        'hourlyData' => [
            'labels' => ['00h', '03h', '06h', '09h', '12h', '15h', '18h', '21h'],
            'data' => [15, 8, 12, 25, 45, 38, 22, 18]
        ],
        'rechargesActivesList' => [
            ['name' => 'Morocco Mall 01', 'kwh' => 423],
            ['name' => 'Mall 02', 'kwh' => 387],
            ['name' => 'Centre Ville', 'kwh' => 298],
            ['name' => 'Aéroport', 'kwh' => 156]
        ],
        'activiteRecente' => [
            ['message' => 'Nouvelle session de recharge démarrée', 'time' => 'Il y a 2 minutes'],
            ['message' => 'Point de charge Morocco Mall 01 activé', 'time' => 'Il y a 5 minutes'],
            ['message' => 'Transaction terminée - 45 kWh', 'time' => 'Il y a 8 minutes'],
            ['message' => 'Maintenance planifiée pour demain', 'time' => 'Il y a 15 minutes']
        ]
    ]);
});
});

// Routes SteVe Monitoring
Route::middleware(['auth'])->prefix('steve')->name('steve.')->group(function () {
    // Interface de monitoring
    Route::get('/monitoring/dashboard', [SteVeMonitoringController::class, 'dashboard'])->name('monitoring.dashboard');
    Route::get('/monitoring/realtime', [SteVeMonitoringController::class, 'realTimeMonitor'])->name('monitoring.realtime');
    
    // API de monitoring
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/test-connectivity', [SteVeMonitoringController::class, 'testConnectivity'])->name('test-connectivity');
        Route::get('/real-time-status', [SteVeMonitoringController::class, 'getRealTimeStatus'])->name('real-time-status');
        Route::get('/charging-points-status', [SteVeMonitoringController::class, 'getChargingPointsStatus'])->name('charging-points-status');
        Route::get('/monitoring-stats', [SteVeMonitoringController::class, 'getMonitoringStats'])->name('monitoring-stats');
        
        // Commandes OCPP
        Route::post('/charging-points/{chargingPoint}/send-command', [SteVeMonitoringController::class, 'sendOcppCommand'])->name('send-command');
        Route::post('/charging-points/{chargingPoint}/start-charging', [SteVeMonitoringController::class, 'startCharging'])->name('start-charging');
        Route::post('/charging-points/{chargingPoint}/stop-charging', [SteVeMonitoringController::class, 'stopCharging'])->name('stop-charging');
        Route::post('/charging-points/{chargingPoint}/unlock-connector', [SteVeMonitoringController::class, 'unlockConnector'])->name('unlock-connector');
        Route::post('/charging-points/{chargingPoint}/reset', [SteVeMonitoringController::class, 'resetChargingPoint'])->name('reset');
        
        // Statut et logs
        Route::get('/charging-points/{chargingPoint}/connection-status', [SteVeMonitoringController::class, 'checkConnectionStatus'])->name('connection-status');
        Route::get('/charging-points/{chargingPoint}/action-log', [SteVeMonitoringController::class, 'getActionLog'])->name('action-log');
    });
});

// Routes de test SETEVE
Route::middleware(['auth'])->prefix('steve-test')->name('steve-test.')->group(function () {
    // Tests de connectivité
    Route::get('/connectivity', [SteVeTestController::class, 'testConnectivity'])->name('connectivity');
    
    // Tests de déclenchement de recharge
    Route::post('/charging-trigger', [SteVeTestController::class, 'testChargingTrigger'])->name('charging-trigger');
    Route::post('/charging-status', [SteVeTestController::class, 'testChargingStatus'])->name('charging-status');
    Route::post('/stop-charging', [SteVeTestController::class, 'testStopCharging'])->name('stop-charging');
    
    // Test complet du flux de paiement
    Route::post('/full-payment-flow', [SteVeTestController::class, 'testFullPaymentFlow'])->name('full-payment-flow');
    
    // Configuration et statistiques
    Route::get('/retry-stats', [SteVeTestController::class, 'getRetryStats'])->name('retry-stats');
    Route::post('/retry-config', [SteVeTestController::class, 'updateRetryConfig'])->name('retry-config');
});

// Routes d'arrêt automatique SETEVE
Route::middleware(['auth'])->prefix('steve-auto-stop')->name('steve-auto-stop.')->group(function () {
    // Vérification et arrêt automatique
    Route::post('/check-and-stop', [SteVeAutoStopController::class, 'checkAndStopSessions'])->name('check-and-stop');
    Route::post('/check-session', [SteVeAutoStopController::class, 'checkSpecificSession'])->name('check-session');
    Route::post('/force-stop', [SteVeAutoStopController::class, 'forceStopSession'])->name('force-stop');
    
    // Statistiques et monitoring
    Route::get('/stats', [SteVeAutoStopController::class, 'getSessionStats'])->name('stats');
    Route::get('/active-sessions', [SteVeAutoStopController::class, 'getActiveSessions'])->name('active-sessions');
    Route::get('/history', [SteVeAutoStopController::class, 'getAutoStopHistory'])->name('history');
    
    // Configuration
    Route::get('/config', [SteVeAutoStopController::class, 'getMonitoringConfig'])->name('config');
    Route::post('/config', [SteVeAutoStopController::class, 'updateMonitoringConfig'])->name('update-config');
});

// Routes de test pour l'arrêt automatique SETEVE
Route::middleware(['auth'])->prefix('steve-auto-stop-test')->name('steve-auto-stop-test.')->group(function () {
    // Tests de base
    Route::get('/check-sessions', [SteVeAutoStopTestController::class, 'testCheckAndStopSessions'])->name('check-sessions');
    Route::post('/check-session', [SteVeAutoStopTestController::class, 'testCheckSpecificSession'])->name('check-session');
    Route::get('/stats', [SteVeAutoStopTestController::class, 'testSessionStats'])->name('stats');
    
    // Tests avancés
    Route::get('/full-system-test', [SteVeAutoStopTestController::class, 'testFullAutoStopSystem'])->name('full-system-test');
    Route::post('/simulate-auto-stop', [SteVeAutoStopTestController::class, 'testSimulateAutoStop'])->name('simulate-auto-stop');
    Route::get('/artisan-command', [SteVeAutoStopTestController::class, 'testArtisanCommand'])->name('artisan-command');
});

// Routes Dashboard des Balances Hiérarchiques
Route::middleware(['auth'])->prefix('dashboard/balances')->name('dashboard.balances.')->group(function () {
    // Dashboard principal
    Route::get('/', [BalanceDashboardController::class, 'index'])->name('index');
    
    // Statistiques globales (admin seulement)
    Route::get('/statistics', [BalanceDashboardController::class, 'statistics'])
        ->middleware('role:admin')
        ->name('statistics');
    
    // Recalcul des balances (admin seulement)
    Route::get('/recalculate', [BalanceDashboardController::class, 'recalculate'])
        ->middleware('role:admin')
        ->name('recalculate');
    
    Route::post('/process-recalculation', [BalanceDashboardController::class, 'processRecalculation'])
        ->middleware('role:admin')
        ->name('process-recalculation');
});

// Routes Admin
// Include admin routes
require __DIR__.'/language.php';