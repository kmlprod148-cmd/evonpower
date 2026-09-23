<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        \App\Events\ReservationCompleted::class => [
            \App\Listeners\ProcessHierarchicalTransactionsListener::class,
        ],
        \App\Events\PostpaidSessionStarted::class => [
            \App\Listeners\UpdatePostpaidSessionConsumptionListener::class,
        ],
        // Auto Remote Start: Déclencher le démarrage automatique des transactions OCPP
        \App\Events\ReservationApproved::class => [
            \App\Listeners\TriggerAutoRemoteStart::class,
        ],
        \App\Events\ReservationCreated::class => [
            \App\Listeners\TriggerAutoRemoteStart::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}