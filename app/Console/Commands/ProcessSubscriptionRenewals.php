<?php

namespace App\Console\Commands;

use App\Models\UserSubscription;
use App\Enums\SubscriptionStatus;
use App\Services\UserSubscriptionService;
use App\Mail\SubscriptionRenewalReminderMail;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Process Subscription Renewals Command
 * 
 * This command handles:
 * 1. Sending renewal reminders 3 days before expiry
 * 2. Processing auto-renewals on expiry date
 * 3. Handling failed renewals
 * 
 * Run daily via scheduler: $schedule->command('subscriptions:renew')->daily();
 */
class ProcessSubscriptionRenewals extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'subscriptions:renew 
                            {--send-reminders : Send renewal reminder emails}
                            {--process-renewals : Process auto-renewals}
                            {--dry-run : Simulate without making changes}';

    /**
     * The console command description.
     */
    protected $description = 'Process subscription renewals and send reminders';

    protected UserSubscriptionService $subscriptionService;

    /**
     * Create a new command instance.
     */
    public function __construct(UserSubscriptionService $subscriptionService)
    {
        parent::__construct();
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $sendReminders = $this->option('send-reminders');
        $processRenewals = $this->option('process-renewals');

        // If no options specified, do everything
        if (!$sendReminders && !$processRenewals) {
            $sendReminders = true;
            $processRenewals = true;
        }

        $this->info('Processing subscription renewals...');
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        $results = [
            'reminders_sent' => 0,
            'reminders_failed' => 0,
            'renewals_processed' => 0,
            'renewals_failed' => 0,
        ];

        // Step 1: Send renewal reminders
        if ($sendReminders) {
            $results = array_merge($results, $this->processReminders($dryRun));
        }

        // Step 2: Process auto-renewals
        if ($processRenewals) {
            $results = array_merge($results, $this->processRenewals($dryRun));
        }

        // Display results
        $this->info('');
        $this->info('Results:');
        $this->info("  Reminders sent: {$results['reminders_sent']}");
        $this->info("  Reminders failed: {$results['reminders_failed']}");
        $this->info("  Renewals processed: {$results['renewals_processed']}");
        $this->info("  Renewals failed: {$results['renewals_failed']}");

        return 0;
    }

    /**
     * Send renewal reminder emails
     */
    protected function processReminders(bool $dryRun): array
    {
        $this->info('Sending renewal reminders...');

        // Find subscriptions expiring in 3 days with auto_renew enabled
        $reminderDate = Carbon::now()->addDays(3);
        
        $subscriptions = UserSubscription::where('status', SubscriptionStatus::ACTIVE)
            ->where('auto_renew', true)
            ->whereDate('end_date', $reminderDate)
            ->with(['user', 'subscriptionPlan'])
            ->get();

        $this->info("Found {$subscriptions->count()} subscriptions expiring in 3 days");

        $sent = 0;
        $failed = 0;

        foreach ($subscriptions as $subscription) {
            try {
                if (!$dryRun) {
                    Mail::to($subscription->user->email)
                        ->send(new SubscriptionRenewalReminderMail($subscription, 3));
                }

                $sent++;
                $this->line("  ✓ Reminder sent to user #{$subscription->user_id} for subscription #{$subscription->id}");

            } catch (\Exception $e) {
                $failed++;
                $this->error("  ✗ Failed to send reminder to user #{$subscription->user_id}: {$e->getMessage()}");
                
                Log::error('Failed to send subscription renewal reminder', [
                    'subscription_id' => $subscription->id,
                    'user_id' => $subscription->user_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'reminders_sent' => $sent,
            'reminders_failed' => $failed,
        ];
    }

    /**
     * Process auto-renewals
     */
    protected function processRenewals(bool $dryRun): array
    {
        $this->info('Processing auto-renewals...');

        // Find subscriptions that have expired today with auto_renew enabled
        $today = Carbon::today();
        
        $subscriptions = UserSubscription::where('status', SubscriptionStatus::ACTIVE)
            ->where('auto_renew', true)
            ->whereDate('end_date', '<=', $today)
            ->with(['user', 'subscriptionPlan'])
            ->get();

        $this->info("Found {$subscriptions->count()} expired subscriptions to renew");

        $processed = 0;
        $failed = 0;

        foreach ($subscriptions as $subscription) {
            try {
                if (!$dryRun) {
                    $success = $this->subscriptionService->renewSubscription($subscription);
                    
                    if ($success) {
                        $processed++;
                        $this->line("  ✓ Subscription #{$subscription->id} renewed successfully");
                    } else {
                        $failed++;
                        $this->warn("  ! Subscription #{$subscription->id} renewal failed");
                    }
                } else {
                    $processed++;
                    $this->line("  ✓ Would renew subscription #{$subscription->id}");
                }

            } catch (\Exception $e) {
                $failed++;
                $this->error("  ✗ Failed to renew subscription #{$subscription->id}: {$e->getMessage()}");
                
                Log::error('Failed to renew subscription', [
                    'subscription_id' => $subscription->id,
                    'user_id' => $subscription->user_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'renewals_processed' => $processed,
            'renewals_failed' => $failed,
        ];
    }
}
