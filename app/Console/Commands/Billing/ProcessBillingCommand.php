<?php

namespace App\Console\Commands\Billing;

use App\Models\BillingPlan;
use App\Models\BillingInvoice;
use App\Services\BillingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessBillingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:process {--frequency=} {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Traite la facturation automatique pour une fréquence donnée';

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
        $frequency = $this->option('frequency') ?? 'daily';
        $dryRun = $this->option('dry-run');

        $this->info("🚀 Début du traitement de la facturation pour la fréquence: {$frequency}");

        if ($dryRun) {
            $this->warn('⚠️  Mode simulation activé - aucune facture ne sera créée');
        }

        try {
            // Récupérer les plans de facturation actifs pour la fréquence donnée
            $billingPlans = BillingPlan::active()
                ->whereHas('billingCycle', function($query) use ($frequency) {
                    $query->where('frequency', $frequency)
                          ->where('is_active', true);
                })
                ->with(['billingCycle', 'billable'])
                ->get();

            if ($billingPlans->isEmpty()) {
                $this->info("✅ Aucun plan de facturation actif trouvé pour la fréquence: {$frequency}");
                return;
            }

            $this->info("📋 {$billingPlans->count()} plan(s) de facturation trouvé(s)");

            $processedCount = 0;
            $errorCount = 0;

            foreach ($billingPlans as $plan) {
                try {
                    $this->line("🔄 Traitement du plan: {$plan->name}");

                    if ($dryRun) {
                        $this->info("   📄 Simulation: Facture pour {$plan->billable_type} #{$plan->billable_id}");
                        $processedCount++;
                        continue;
                    }

                    // Vérifier si une facture existe déjà pour cette période
                    $existingInvoice = $this->billingService->getExistingInvoiceForPeriod(
                        $plan,
                        now()->startOfDay(),
                        now()->endOfDay()
                    );

                    if ($existingInvoice) {
                        $this->warn("   ⚠️  Facture déjà existante pour cette période");
                        continue;
                    }

                    // Créer la facture
                    $invoice = $this->billingService->createInvoice($plan);
                    
                    if ($invoice) {
                        $this->info("   ✅ Facture créée: {$invoice->invoice_number}");
                        $processedCount++;
                    }

                } catch (\Exception $e) {
                    $errorCount++;
                    $this->error("   ❌ Erreur lors du traitement du plan {$plan->name}: " . $e->getMessage());
                    Log::error("Erreur facturation plan {$plan->id}", [
                        'plan_id' => $plan->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            $this->info("📊 Résumé du traitement:");
            $this->info("   ✅ Factures traitées: {$processedCount}");
            $this->info("   ❌ Erreurs: {$errorCount}");

            if ($dryRun) {
                $this->warn('⚠️  Mode simulation - aucune facture réelle créée');
            }

        } catch (\Exception $e) {
            $this->error("❌ Erreur générale lors du traitement: " . $e->getMessage());
            Log::error("Erreur générale facturation", [
                'frequency' => $frequency,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
