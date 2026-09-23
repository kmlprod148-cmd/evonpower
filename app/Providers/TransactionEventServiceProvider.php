<?php

namespace App\Providers;

use App\Events\ReservationCreated;
use App\Events\RechargeRequested;
use App\Listeners\ProcessReservationTransactions;
use App\Listeners\ProcessRechargeTransactions;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class TransactionEventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        ReservationCreated::class => [
            ProcessReservationTransactions::class,
        ],
        RechargeRequested::class => [
            ProcessRechargeTransactions::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
