<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Http\Controllers\PaymentNotificationController;
use Carbon\Carbon;

class ProcessPaymentNotifications extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'payments:process-notifications {--type=all : Type de notification (success, failure, pending, reminder, all)} {--hours=24 : Nombre d\'heures à traiter}';

    /**
     * The console command description.
     */
    protected $description = 'Traiter les notifications de paiement en attente';

    protected $notificationController;

    public function __construct(PaymentNotificationController $notificationController)
    {
        parent::__construct();
        $this->notificationController = $notificationController;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        $hours = (int) $this->option('hours');
        $startTime = Carbon::now()->subHours($hours);

        $this->info("Traitement des notifications de paiement pour les {$hours} dernières heures...");

        try {
            switch ($type) {
                case 'success':
                    $this->processSuccessNotifications($startTime);
                    break;
                case 'failure':
                    $this->processFailureNotifications($startTime);
                    break;
                case 'pending':
                    $this->processPendingNotifications($startTime);
                    break;
                case 'reminder':
                    $this->processReminderNotifications($startTime);
                    break;
                case 'all':
                default:
                    $this->processAllNotifications($startTime);
                    break;
            }

            $this->info('Traitement des notifications terminé avec succès.');

        } catch (\Exception $e) {
            $this->error('Erreur lors du traitement des notifications: ' . $e->getMessage());
            Log::error('Payment notifications processing failed', [
                'error' => $e->getMessage(),
                'type' => $type,
                'hours' => $hours
            ]);
            return 1;
        }

        return 0;
    }

    /**
     * Traiter les notifications de succès
     */
    private function processSuccessNotifications(Carbon $startTime)
    {
        $this->info('Traitement des notifications de succès...');

        $orders = Order::where('status', 'completed')
            ->where('created_at', '>=', $startTime)
            ->whereNull('notification_sent_at')
            ->get();

        $this->info("Trouvé {$orders->count()} paiements réussis à notifier.");

        $sent = 0;
        foreach ($orders as $order) {
            if ($this->notificationController->sendPaymentSuccessNotification($order)) {
                $order->update(['notification_sent_at' => now()]);
                $sent++;
            }
        }

        $this->info("Notifications de succès envoyées: {$sent}/{$orders->count()}");
    }

    /**
     * Traiter les notifications d'échec
     */
    private function processFailureNotifications(Carbon $startTime)
    {
        $this->info('Traitement des notifications d\'échec...');

        $orders = Order::where('status', 'failed')
            ->where('created_at', '>=', $startTime)
            ->whereNull('notification_sent_at')
            ->get();

        $this->info("Trouvé {$orders->count()} paiements échoués à notifier.");

        $sent = 0;
        foreach ($orders as $order) {
            if ($this->notificationController->sendPaymentFailureNotification($order)) {
                $order->update(['notification_sent_at' => now()]);
                $sent++;
            }
        }

        $this->info("Notifications d'échec envoyées: {$sent}/{$orders->count()}");
    }

    /**
     * Traiter les notifications en attente
     */
    private function processPendingNotifications(Carbon $startTime)
    {
        $this->info('Traitement des notifications en attente...');

        $orders = Order::where('status', 'pending_payment')
            ->where('created_at', '>=', $startTime)
            ->whereNull('notification_sent_at')
            ->get();

        $this->info("Trouvé {$orders->count()} paiements en attente à notifier.");

        $sent = 0;
        foreach ($orders as $order) {
            if ($this->notificationController->sendPaymentPendingNotification($order)) {
                $order->update(['notification_sent_at' => now()]);
                $sent++;
            }
        }

        $this->info("Notifications en attente envoyées: {$sent}/{$orders->count()}");
    }

    /**
     * Traiter les notifications de rappel
     */
    private function processReminderNotifications(Carbon $startTime)
    {
        $this->info('Traitement des notifications de rappel...');

        // Rappels pour les paiements en attente depuis plus de 1 heure
        $reminderTime = Carbon::now()->subHour();
        
        $orders = Order::where('status', 'pending_payment')
            ->where('created_at', '<=', $reminderTime)
            ->where('created_at', '>=', $startTime)
            ->get();

        $this->info("Trouvé {$orders->count()} paiements nécessitant un rappel.");

        $sent = 0;
        foreach ($orders as $order) {
            if ($this->notificationController->sendPaymentReminderNotification($order)) {
                $sent++;
            }
        }

        $this->info("Rappels envoyés: {$sent}/{$orders->count()}");
    }

    /**
     * Traiter toutes les notifications
     */
    private function processAllNotifications(Carbon $startTime)
    {
        $this->info('Traitement de toutes les notifications...');

        $this->processSuccessNotifications($startTime);
        $this->processFailureNotifications($startTime);
        $this->processPendingNotifications($startTime);
        $this->processReminderNotifications($startTime);
    }
}
