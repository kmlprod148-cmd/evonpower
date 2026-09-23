<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BalanceCalculationService;
use App\Services\CommissionCalculationService;
use App\Models\User;
use App\Models\Transaction;
use App\Models\BusinessProfile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Commande pour traiter la facturation périodique
 * 
 * Cette commande gère :
 * - Calcul des balances périodiques
 * - Génération des rapports de commission
 * - Facturation des frais récurrents
 * - Notifications de paiement
 */
class ProcessPeriodicBilling extends Command
{
    protected $signature = 'billing:process 
                            {--type=all : Type de facturation (all, balances, commissions, fees)}
                            {--period=monthly : Période (daily, weekly, monthly)}
                            {--force : Forcer le traitement même si déjà exécuté}';

    protected $description = 'Traite la facturation périodique pour tous les utilisateurs';

    protected BalanceCalculationService $balanceService;
    protected ?CommissionCalculationService $commissionService = null;

    public function __construct(
        BalanceCalculationService $balanceService
    ) {
        parent::__construct();
        $this->balanceService = $balanceService;
    }

    /**
     * Obtient le service de commission (lazy loading)
     * 
     * @return CommissionCalculationService|null
     */
    protected function getCommissionService(): ?CommissionCalculationService
    {
        if ($this->commissionService === null) {
            try {
                if (class_exists(CommissionCalculationService::class)) {
                    $this->commissionService = app(CommissionCalculationService::class);
                } else {
                    Log::warning('CommissionCalculationService not available. Commission processing will be skipped.');
                    return null;
                }
            } catch (\Exception $e) {
                Log::error('Failed to resolve CommissionCalculationService', [
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        }
        
        return $this->commissionService;
    }

    public function handle()
    {
        $type = $this->option('type');
        $period = $this->option('period');
        $force = $this->option('force');

        $this->info("🚀 Début du traitement de facturation périodique");
        $this->info("Type: {$type}, Période: {$period}");

        $results = [
            'started_at' => now()->toISOString(),
            'type' => $type,
            'period' => $period,
            'processed' => [],
            'errors' => []
        ];

        try {
            switch ($type) {
                case 'balances':
                    $results['processed']['balances'] = $this->processBalances($force);
                    break;
                case 'commissions':
                    $results['processed']['commissions'] = $this->processCommissions($period, $force);
                    break;
                case 'fees':
                    $results['processed']['fees'] = $this->processFees($period, $force);
                    break;
                case 'all':
                default:
                    $results['processed']['balances'] = $this->processBalances($force);
                    $results['processed']['commissions'] = $this->processCommissions($period, $force);
                    $results['processed']['fees'] = $this->processFees($period, $force);
                    break;
            }

            $results['completed_at'] = now()->toISOString();
            $results['status'] = 'success';

            $this->info("✅ Traitement terminé avec succès");
            $this->displayResults($results);

        } catch (\Exception $e) {
            $results['errors'][] = $e->getMessage();
            $results['status'] = 'error';
            $results['completed_at'] = now()->toISOString();

            $this->error("❌ Erreur lors du traitement: " . $e->getMessage());
            Log::error('Erreur facturation périodique', $results);
        }

        return $results['status'] === 'success' ? 0 : 1;
    }

    /**
     * Traite les balances périodiques
     * 
     * @param bool $force
     * @return array
     */
    private function processBalances(bool $force): array
    {
        $this->info("📊 Traitement des balances...");

        $results = $this->balanceService->recalculateAllBalances();

        $this->info("Balances traitées:");
        $this->info("- Admins: {$results['admin_balances']}");
        $this->info("- Intégrateurs: {$results['integrator_balances']}");
        $this->info("- Opérateurs: {$results['operator_balances']}");

        if (!empty($results['errors'])) {
            $this->warn("Erreurs rencontrées: " . count($results['errors']));
            foreach ($results['errors'] as $error) {
                $this->warn("- {$error}");
            }
        }

        return $results;
    }

    /**
     * Traite les commissions périodiques
     * 
     * @param string $period
     * @param bool $force
     * @return array
     */
    private function processCommissions(string $period, bool $force): array
    {
        $this->info("💰 Traitement des commissions...");

        $results = [
            'processed_transactions' => 0,
            'commissions_calculated' => 0,
            'errors' => []
        ];

        try {
            // Récupérer les transactions non traitées selon la période
            $dateFrom = $this->getDateFromPeriod($period);
            
            $transactions = Transaction::where('created_at', '>=', $dateFrom)
                ->where('payment_status', 'paid')
                ->where(function ($query) {
                    $query->whereNull('admin_commission')
                          ->orWhere('admin_commission', 0);
                })
                ->with(['businessProfile', 'chargingPoint'])
                ->get();

            $commissionService = $this->getCommissionService();
            
            if (!$commissionService) {
                $this->warn("⚠️ Service de commission non disponible. Les commissions ne seront pas calculées.");
                $results['errors'][] = 'CommissionCalculationService not available';
                return $results;
            }

            foreach ($transactions as $transaction) {
                try {
                    if ($transaction->businessProfile) {
                        $commissions = $commissionService->calculateCommissions(
                            $transaction->businessProfile,
                            $transaction->price_total,
                            'corrected'
                        );

                        $transaction->update([
                            'admin_commission' => $commissions['admin_commission'],
                            'integrator_commission' => $commissions['integrator_commission'],
                            'partner_commission' => $commissions['operator_commission'] ?? 0
                        ]);

                        $results['commissions_calculated']++;
                    }

                    $results['processed_transactions']++;

                } catch (\Exception $e) {
                    $results['errors'][] = "Transaction {$transaction->id}: " . $e->getMessage();
                }
            }

            $this->info("Commissions traitées:");
            $this->info("- Transactions: {$results['processed_transactions']}");
            $this->info("- Commissions calculées: {$results['commissions_calculated']}");

        } catch (\Exception $e) {
            $results['errors'][] = $e->getMessage();
            $this->error("Erreur traitement commissions: " . $e->getMessage());
        }

        return $results;
    }

    /**
     * Traite les frais périodiques
     * 
     * @param string $period
     * @param bool $force
     * @return array
     */
    private function processFees(string $period, bool $force): array
    {
        $this->info("💳 Traitement des frais récurrents...");

        $results = [
            'fees_processed' => 0,
            'total_amount' => 0.0,
            'errors' => []
        ];

        try {
            // Traiter les frais de maintenance
            $maintenanceFees = $this->processMaintenanceFees($period);
            $results['fees_processed'] += $maintenanceFees['count'];
            $results['total_amount'] += $maintenanceFees['amount'];

            // Traiter les frais de terminal
            $terminalFees = $this->processTerminalFees($period);
            $results['fees_processed'] += $terminalFees['count'];
            $results['total_amount'] += $terminalFees['amount'];

            $this->info("Frais traités:");
            $this->info("- Nombre: {$results['fees_processed']}");
            $this->info("- Montant total: " . number_format($results['total_amount'], 2) . " EUR");

        } catch (\Exception $e) {
            $results['errors'][] = $e->getMessage();
            $this->error("Erreur traitement frais: " . $e->getMessage());
        }

        return $results;
    }

    /**
     * Traite les frais de maintenance
     * 
     * @param string $period
     * @return array
     */
    private function processMaintenanceFees(string $period): array
    {
        $count = 0;
        $amount = 0.0;

        // Récupérer les business profiles avec frais de maintenance
        $businessProfiles = BusinessProfile::where('maintenance_fee_amount', '>', 0)
            ->where('is_active', true)
            ->get();

        foreach ($businessProfiles as $profile) {
            // Créer une transaction de frais de maintenance
            $feeTransaction = Transaction::create([
                'user_id' => null, // Frais système
                'charging_point_id' => null,
                'business_profile_id' => $profile->id,
                'amount' => $profile->maintenance_fee_amount,
                'price_total' => $profile->maintenance_fee_amount,
                'status' => 'completed',
                'payment_status' => 'paid',
                'payment_method' => 'system',
                'transaction_type' => 'maintenance_fee',
                'transaction_category' => 'maintenance',
                'description' => "Frais de maintenance - {$profile->name}",
                'paid_at' => now()
            ]);

            $count++;
            $amount += $profile->maintenance_fee_amount;

            Log::info('Frais de maintenance créé', [
                'business_profile_id' => $profile->id,
                'amount' => $profile->maintenance_fee_amount
            ]);
        }

        return ['count' => $count, 'amount' => $amount];
    }

    /**
     * Traite les frais de terminal
     * 
     * @param string $period
     * @return array
     */
    private function processTerminalFees(string $period): array
    {
        $count = 0;
        $amount = 0.0;

        // Récupérer les business profiles avec frais de terminal
        $businessProfiles = BusinessProfile::where('terminal_fee_amount', '>', 0)
            ->where('is_active', true)
            ->get();

        foreach ($businessProfiles as $profile) {
            // Créer une transaction de frais de terminal
            $feeTransaction = Transaction::create([
                'user_id' => null, // Frais système
                'charging_point_id' => null,
                'business_profile_id' => $profile->id,
                'amount' => $profile->terminal_fee_amount,
                'price_total' => $profile->terminal_fee_amount,
                'status' => 'completed',
                'payment_status' => 'paid',
                'payment_method' => 'system',
                'transaction_type' => 'terminal_fee',
                'transaction_category' => 'terminal',
                'description' => "Frais de terminal - {$profile->name}",
                'paid_at' => now()
            ]);

            $count++;
            $amount += $profile->terminal_fee_amount;

            Log::info('Frais de terminal créé', [
                'business_profile_id' => $profile->id,
                'amount' => $profile->terminal_fee_amount
            ]);
        }

        return ['count' => $count, 'amount' => $amount];
    }

    /**
     * Obtient la date de début selon la période
     * 
     * @param string $period
     * @return string
     */
    private function getDateFromPeriod(string $period): string
    {
        switch ($period) {
            case 'daily':
                return now()->subDay()->startOfDay();
            case 'weekly':
                return now()->subWeek()->startOfDay();
            case 'monthly':
            default:
                return now()->subMonth()->startOfDay();
        }
    }

    /**
     * Affiche les résultats du traitement
     * 
     * @param array $results
     */
    private function displayResults(array $results): void
    {
        $this->info("\n📋 Résultats du traitement:");
        
        if (isset($results['processed']['balances'])) {
            $balances = $results['processed']['balances'];
            $this->info("Balances:");
            $this->info("  - Admins: {$balances['admin_balances']}");
            $this->info("  - Intégrateurs: {$balances['integrator_balances']}");
            $this->info("  - Opérateurs: {$balances['operator_balances']}");
        }

        if (isset($results['processed']['commissions'])) {
            $commissions = $results['processed']['commissions'];
            $this->info("Commissions:");
            $this->info("  - Transactions: {$commissions['processed_transactions']}");
            $this->info("  - Commissions calculées: {$commissions['commissions_calculated']}");
        }

        if (isset($results['processed']['fees'])) {
            $fees = $results['processed']['fees'];
            $this->info("Frais:");
            $this->info("  - Nombre: {$fees['fees_processed']}");
            $this->info("  - Montant: " . number_format($fees['total_amount'], 2) . " EUR");
        }

        if (!empty($results['errors'])) {
            $this->warn("\n⚠️ Erreurs rencontrées:");
            foreach ($results['errors'] as $error) {
                $this->warn("  - {$error}");
            }
        }
    }
}
