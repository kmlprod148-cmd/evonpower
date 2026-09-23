<?php

namespace App\Console\Commands\Billing;

use App\Models\BillingPlan;
use App\Services\BillingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateInvoicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:generate-invoices {--date=} {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Génère les factures pour une date donnée (par défaut aujourd\'hui)';

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
        $date = $this->option('date') ?? now()->format('Y-m-d');
        $dryRun = $this->option('dry-run');

        $this->info("🚀 Génération des factures pour le {$date}");

        if ($dryRun) {
            $this->warn('⚠️  Mode simulation activé - aucune facture ne sera créée');
        }

        try {
            $targetDate = \Carbon\Carbon::parse($date);
            
            // Récupérer tous les plans de facturation actifs
            $billingPlans = BillingPlan::active()
                ->where('start_date', '<=', $targetDate)
                ->where(function($query) use ($targetDate) {
                    $query->whereNull('end_date')
                          ->orWhere('end_date', '>=', $targetDate);
                })
                ->with(['billingCycle', 'billable'])
                ->get();

            if ($billingPlans->isEmpty()) {
                $this->info("✅ Aucun plan de facturation actif trouvé pour le {$date}");
                return;
            }

            $this->info("📋 {$billingPlans->count()} plan(s) de facturation trouvé(s)");

            $processedCount = 0;
            $skippedCount = 0;
            $errorCount = 0;

            foreach ($billingPlans as $plan) {
                try {
                    $this->line("🔄 Traitement du plan: {$plan->name}");

                    // Vérifier si une facture existe déjà pour cette période
                    $existingInvoice = $this->billingService->getExistingInvoiceForPeriod(
                        $plan,
                        $targetDate->startOfDay(),
                        $targetDate->endOfDay()
                    );

                    if ($existingInvoice) {
                        $this->warn("   ⚠️  Facture déjà existante: {$existingInvoice->invoice_number}");
                        $skippedCount++;
                        continue;
                    }

                    if ($dryRun) {
                        $this->info("   📄 Simulation: Facture pour {$plan->billable_type} #{$plan->billable_id}");
                        $processedCount++;
                        continue;
                    }

                    // Créer la facture
                    $invoice = $this->billingService->createInvoice($plan);
                    
                    if ($invoice) {
                        $this->info("   ✅ Facture créée: {$invoice->invoice_number} - {$invoice->total_amount} {$invoice->currency}");
                        $processedCount++;
                    }

                } catch (\Exception $e) {
                    $errorCount++;
                    $this->error("   ❌ Erreur lors du traitement du plan {$plan->name}: " . $e->getMessage());
                    Log::error("Erreur génération facture plan {$plan->id}", [
                        'plan_id' => $plan->id,
                        'date' => $date,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            $this->info("📊 Résumé de la génération:");
            $this->info("   ✅ Factures générées: {$processedCount}");
            $this->info("   ⏭️  Factures ignorées: {$skippedCount}");
            $this->info("   ❌ Erreurs: {$errorCount}");

            if ($dryRun) {
                $this->warn('⚠️  Mode simulation - aucune facture réelle créée');
            }

        } catch (\Exception $e) {
            $this->error("❌ Erreur générale lors de la génération: " . $e->getMessage());
            Log::error("Erreur générale génération factures", [
                'date' => $date,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
