<?php

namespace App\Providers;

use App\Services\BalanceCalculationService;
use App\Services\HierarchicalBalanceService;
use App\Services\Cache\CacheService;
use Illuminate\Support\ServiceProvider;

class BalanceServiceProvider extends ServiceProvider
{
    /**
     * Enregistre les services dans le conteneur
     */
    public function register(): void
    {
        // Service de cache
        $this->app->singleton(CacheService::class, function ($app) {
            return new CacheService();
        });

        // Service de calcul de balance
        $this->app->singleton(BalanceCalculationService::class, function ($app) {
            return new BalanceCalculationService(
                $app->make(CacheService::class)
            );
        });

        // Service de calcul de balance hiérarchique
        $this->app->singleton(HierarchicalBalanceService::class, function ($app) {
            return new HierarchicalBalanceService(
                $app->make(CacheService::class)
            );
        });
    }

    /**
     * Démarre les services
     */
    public function boot(): void
    {
        //
    }
}
