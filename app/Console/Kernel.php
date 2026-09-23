<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\SetupCommissionSystem::class,
        \App\Console\Commands\RecordActiveChargingPoints::class,
        \App\Console\Commands\TestSteveApi::class,
        \App\Console\Commands\RecalculateTransactionRevenueDistribution::class,
        \App\Console\Commands\FixTransactionPriceTotal::class,
        \App\Console\Commands\FixTransactionIssues::class,
        \App\Console\Commands\ApplyAllTransactionFees::class,
        \App\Console\Commands\SetupBusinessProfileFees::class,
        \App\Console\Commands\LinkChargingPointsToBusinessProfiles::class,
        \App\Console\Commands\DiagnoseAndFixBusinessProfileFees::class,
        \App\Console\Commands\TranslateLangCommand::class,
        \App\Console\Commands\ListPermissionsCommand::class,
        \App\Console\Commands\FixIntegratorPermissionsCommand::class,
        \App\Console\Commands\FixUser50Permissions::class,
        \App\Console\Commands\AddIntegratorChargingPointPermissions::class,
        \App\Console\Commands\RefreshIntegratorUserPermissions::class,
        \App\Console\Commands\AssignIntegratorRoleToUser::class,
        \App\Console\Commands\SyncAllIntegratorsPermissions::class,
        \App\Console\Commands\UpdateAllPostpaidSessions::class,
        \App\Console\Commands\CleanupOrphanedPostpaidSessions::class,
        \App\Console\Commands\SyncStevePostpaidCommand::class,
        \App\Console\Commands\EnforceChargingCommand::class,
        \App\Console\Commands\EnforceChargingLimits::class, // OCPP Enforcement
        \App\Console\Commands\AutoStartTransactionsCommand::class, // Auto Remote Start
        \App\Console\Commands\FixPaidReservationTransactions::class, // Sync statut transactions prépayé/postpayé
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Job de monitoring de santé des API et chargeurs - toutes les 5 minutes
        $schedule->job(\App\Jobs\MonitorApiStatusJob::class)
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('MonitorApiStatusJob scheduled successfully');
            })
            ->onFailure(function () {
                \Log::error('MonitorApiStatusJob scheduled failed');
            });

        // Nettoyage des anciens logs de statut - quotidiennement à 2h00
        $schedule->call(function () {
            \App\Models\StatusLog::cleanupOldLogs(30);
        })->dailyAt('02:00');

        // Nettoyage des anciens logs de monitoring API - quotidiennement à 3h00
        $schedule->call(function () {
            \App\Models\ApiMonitoring::cleanupOldData(30);
        })->dailyAt('03:00');

        // Monitoring des statuts des bornes Steve - toutes les 5 minutes
        $schedule->command('monitor:steve-charging-points')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('MonitorSteveChargingPoints scheduled successfully');
            })
            ->onFailure(function () {
                \Log::error('MonitorSteveChargingPoints scheduled failed');
            });

        // Synchronisation des points de charge depuis l'API Steve - toutes les heures
        $schedule->command('steve:sync-charge-points')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('SyncSteveChargePoints scheduled successfully');
            })
            ->onFailure(function () {
                \Log::error('SyncSteveChargePoints scheduled failed');
            });

        // Mise à jour de toutes les sessions postpayées en cours - toutes les 30 secondes
        $schedule->command('postpaid:update-all-sessions')
            ->everyThirtySeconds()
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                \Log::debug('UpdateAllPostpaidSessions scheduled successfully');
            })
            ->onFailure(function () {
                \Log::error('UpdateAllPostpaidSessions scheduled failed');
            });

        // Nettoyage des sessions postpayées orphelines - toutes les heures
        $schedule->command('postpaid:cleanup-orphaned')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('CleanupOrphanedPostpaidSessions scheduled successfully');
            })
            ->onFailure(function () {
                \Log::error('CleanupOrphanedPostpaidSessions scheduled failed');
            });

        // Slice C: generic ChargingSession reconciliation against SteVe.
        // Walks every non-terminal session with a steve_transaction_id, flips
        // local state to STOPPED when SteVe shows stopTimestamp, cascades the
        // linked Reservation to COMPLETED, and dispatches the postpaid wallet
        // debit when applicable. Supersedes SyncSteVePostpaidTransactionsJob
        // (which now thin-delegates to this job for backward compat).
        $schedule->job(new \App\Jobs\ReconcileChargingSessionsJob(50, 240))
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground()
            ->when(fn () => config('steve.reconcile_enabled', true));

        // Enforcement général (temps / kWh / solde) - toutes les 2 minutes
        $schedule->command('evon:enforce-charging')
            ->everyTwoMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // OCPP Enforcement (balance / temps / kWh) - toutes les 90 secondes
        // CRITIQUE: Surveille et arrête les sessions qui dépassent les limites
        $schedule->command('charging:enforce-limits')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                \Log::debug('EnforceChargingLimits scheduled successfully');
            })
            ->onFailure(function () {
                \Log::error('EnforceChargingLimits scheduled failed');
            });

        // Auto Remote Start - Démarrage automatique des transactions OCPP
        // CRITIQUE: Détecte et démarre automatiquement les réservations éligibles
        $frequency = config('auto-remote-start.scheduler_frequency', 'everyTwoMinutes');
        $schedule->command('ocpp:auto-start-transactions --queue')
            ->$frequency()
            ->withoutOverlapping()
            ->runInBackground()
            ->when(function () {
                return config('auto-remote-start.enabled', true);
            })
            ->onSuccess(function () {
                \Log::debug('AutoStartTransactions scheduled successfully');
            })
            ->onFailure(function () {
                \Log::error('AutoStartTransactions scheduled failed');
            });

        // Nettoyage des anciens logs de démarrage automatique - quotidiennement à 3h30
        $schedule->call(function () {
            $retentionDays = config('auto-remote-start.logging.retention_days', 30);
            \App\Models\AutoRemoteStartLog::cleanup($retentionDays);
            \Log::info('Auto Remote Start logs cleanup completed', ['retention_days' => $retentionDays]);
        })->dailyAt('03:30');

        // Synchronisation automatique des statuts de transactions (prépayé/postpayé) - toutes les heures
        // Corrige les transactions "En attente" dont la réservation est payée - sans intervention propriétaire
        $schedule->job(new \App\Jobs\TransactionStatusSyncJob())
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground();

        // OCPP Command Outbox - process pending commands every second
        $schedule->call(function () {
            app(\App\Services\Ocpp\OcppCommandOutboxService::class)->processPending();
        })->everySecond()->withoutOverlapping(10)->runInBackground();

        // OCPP Session Reconciliation - every 5 minutes
        $schedule->job(new \App\Jobs\ChargingSessionReconciliationJob())
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}