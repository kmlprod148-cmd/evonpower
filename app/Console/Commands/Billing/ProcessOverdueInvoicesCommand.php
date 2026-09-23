<?php

namespace App\Console\Commands\Billing;

use App\Models\BillingInvoice;
use App\Services\BillingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessOverdueInvoicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:process-overdue {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Traite les factures en retard et envoie des notifications';

    protected BillingService $billingService;

    public function __construct(BillingService $billingService)
    {
        parent::__construct();
        $this->billingService = $billingService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $this->info("🚀 Traitement des factures en retard");

        if ($dryRun) {
            $this->warn('⚠️  Mode simulation activé - aucune action ne sera effectuée');
        }

        try {
            // Récupérer les factures en retard
            $overdueInvoices = BillingInvoice::overdueByDate()
                ->with(['billingPlan', 'billable'])
                ->get();

            if ($overdueInvoices->isEmpty()) {
                $this->info("✅ Aucune facture en retard trouvée");
                return;
            }

            $this->info("📋 {$overdueInvoices->count()} facture(s) en retard trouvée(s)");

            $processedCount = 0;
            $errorCount = 0;

            foreach ($overdueInvoices as $invoice) {
                try {
                    $this->line("🔄 Traitement de la facture: {$invoice->invoice_number}");

                    if ($dryRun) {
                        $this->info("   📄 Simulation: Marquer comme en retard - {$invoice->total_amount} {$invoice->currency}");
                        $processedCount++;
                        continue;
                    }

                    // Marquer la facture comme en retard
                    $invoice->markAsOverdue();
                    
                    // Ici, vous pouvez ajouter l'envoi de notifications
                    // $this->sendOverdueNotification($invoice);
                    
                    $this->info("   ✅ Facture marquée comme en retard");
                    $processedCount++;

                } catch (\Exception $e) {
                    $errorCount++;
                    $this->error("   ❌ Erreur lors du traitement de la facture {$invoice->invoice_number}: " . $e->getMessage());
                    Log::error("Erreur traitement facture en retard {$invoice->id}", [
                        'invoice_id' => $invoice->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            $this->info("📊 Résumé du traitement:");
            $this->info("   ✅ Factures traitées: {$processedCount}");
            $this->info("   ❌ Erreurs: {$errorCount}");

            if ($dryRun) {
                $this->warn('⚠️  Mode simulation - aucune action réelle effectuée');
            }

        } catch (\Exception $e) {
            $this->error("❌ Erreur générale lors du traitement: " . $e->getMessage());
            Log::error("Erreur générale traitement factures en retard", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Envoyer une notification de facture en retard
     */
    private function sendOverdueNotification(BillingInvoice $invoice): void
    {
        // Implémentation de l'envoi de notification
        // Par exemple, envoi d'email, notification push, etc.
        Log::info("Notification de facture en retard envoyée", [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number
        ]);
    }
}
