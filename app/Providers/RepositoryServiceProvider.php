<?php

namespace App\Providers;

use App\Repositories\ChargingPointRepositoryInterface;
use App\Repositories\EloquentChargingPointRepository;
use App\Repositories\Interfaces\IntegratorRepositoryInterface;
use App\Repositories\Eloquent\EloquentIntegratorRepository;
use App\Repositories\Interfaces\PartnerRepositoryInterface;
use App\Repositories\PartnerRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(
            \App\Repositories\ChargingPointRepositoryInterface::class,
            \App\Repositories\EloquentChargingPointRepository::class
        );

        // Enregistrer le binding pour IntegratorRepositoryInterface
        $this->app->bind(
            IntegratorRepositoryInterface::class,
            EloquentIntegratorRepository::class
        );

        // Enregistrer le binding pour PartnerRepositoryInterface
        $this->app->bind(
            PartnerRepositoryInterface::class,
            \App\Repositories\Eloquent\EloquentPartnerRepository::class
        );
    }

    public function boot()
    {
        //
    }
}