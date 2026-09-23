<?php

namespace App\Providers;

use App\Services\CommissionCalculationService;
use App\Services\Commission\Validation\CommissionValidationService;
use App\Services\Commission\Cache\CommissionCacheService;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider pour les services de commission
 * 
 * Enregistre les services de commission dans le conteneur IoC
 * avec configuration et injection de dépendances
 */
class CommissionServiceProvider extends ServiceProvider
{
    /**
     * Enregistre les services dans le conteneur
     */
    public function register(): void
    {
        // Vérifier l'existence des classes avant de les enregistrer
        if (!class_exists(CommissionValidationService::class)) {
            \Illuminate\Support\Facades\Log::error('CommissionValidationService class not found. Please run: composer dump-autoload');
            return;
        }

        if (!class_exists(CommissionCacheService::class)) {
            \Illuminate\Support\Facades\Log::error('CommissionCacheService class not found. Please run: composer dump-autoload');
            return;
        }

        if (!class_exists(CommissionCalculationService::class)) {
            \Illuminate\Support\Facades\Log::error('CommissionCalculationService class not found. Please run: composer dump-autoload');
            return;
        }

        // Service de validation
        $this->app->singleton(CommissionValidationService::class, function ($app) {
            return new CommissionValidationService();
        });

        // Service de cache
        $this->app->singleton(CommissionCacheService::class, function ($app) {
            return new CommissionCacheService();
        });

        // Service principal de calcul de commission
        $this->app->singleton(CommissionCalculationService::class, function ($app) {
            try {
                return new CommissionCalculationService(
                    $app->make(CommissionValidationService::class),
                    $app->make(CommissionCacheService::class)
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to create CommissionCalculationService', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw new \RuntimeException(
                    'Failed to create CommissionCalculationService: ' . $e->getMessage() .
                    '. Please run: composer dump-autoload && php artisan config:clear'
                );
            }
        });
    }

    /**
     * Démarre les services
     */
    public function boot(): void
    {
        // Configuration des services si nécessaire
        $this->configureServices();
    }

    /**
     * Configure les services de commission
     */
    private function configureServices(): void
    {
        // Configuration du cache si nécessaire
        if (config('cache.default') === 'redis') {
            // Configuration spécifique pour Redis
            $this->configureRedisCache();
        }
    }

    /**
     * Configure le cache Redis pour les commissions
     */
    private function configureRedisCache(): void
    {
        // Configuration spécifique pour Redis si nécessaire
        // Par exemple, configuration des tags de cache
    }
}
