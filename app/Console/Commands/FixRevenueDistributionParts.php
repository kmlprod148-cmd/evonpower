<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\ChargingPoint;
use App\Services\UnifiedHierarchicalTransactionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixRevenueDistributionParts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'revenue:fix-parts 
                            {--dry-run : Exécuter sans modifier la base de données}
                            {--charging-point-id= : ID spécifique de la borne à corriger}
                            {--transaction-id= : ID spécifique de la transaction à corriger}
                            {--limit= : Limiter le nombre de transactions à traiter}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculer et corriger les parts de répartition des revenus selon la nouvelle logique';

    protected $unifiedService;
    protected $dryRun = false;
    protected $stats = [
        'total' => 0,
        'updated' => 0,
        'errors' => 0,
        'skipped' => 0,
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->dryRun = $this->option('dry-run');
        $this->unifiedService = app(UnifiedHierarchicalTransactionService::class);

        if ($this->dryRun) {
            $this->warn('⚠️  Mode DRY-RUN activé - Aucune modification ne sera appliquée');
        }

        $this->info('🔄 Début de la correction des parts de répartition des revenus...');
        $this->newLine();

        try {
            // Récupérer les transactions à traiter
            $query = Transaction::whereHas('transactionDetail')
                ->with(['transactionDetail', 'chargingPoint']);

            if ($this->option('transaction-id')) {
                $query->where('id', $this->option('transaction-id'));
            } elseif ($this->option('charging-point-id')) {
                $query->where('charging_point_id', $this->option('charging-point-id'));
            }

            if ($this->option('limit')) {
                $query->limit((int) $this->option('limit'));
            }

            $transactions = $query->get();

            if ($transactions->isEmpty()) {
                $this->warn('Aucune transaction trouvée à traiter.');
                return 0;
            }

            $this->info("📊 {$transactions->count()} transaction(s) trouvée(s)");
            $this->newLine();

            $bar = $this->output->createProgressBar($transactions->count());
            $bar->start();

            foreach ($transactions as $transaction) {
                $this->stats['total']++;
                $this->processTransaction($transaction);
                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            // Afficher les statistiques
            $this->displayStatistics();

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Erreur lors du traitement : ' . $e->getMessage());
            Log::error('Erreur FixRevenueDistributionParts', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Traiter une transaction
     */
    protected function processTransaction(Transaction $transaction)
    {
        try {
            $transactionDetail = $transaction->transactionDetail;
            
            if (!$transactionDetail) {
                $this->stats['skipped']++;
                return;
            }

            $chargingPoint = $transaction->chargingPoint;
            
            if (!$chargingPoint) {
                $this->stats['skipped']++;
                Log::warning('Transaction sans borne associée', [
                    'transaction_id' => $transaction->id
                ]);
                return;
            }

            // Récupérer la hiérarchie
            $hierarchy = $this->unifiedService->getCompleteHierarchy($chargingPoint->id);
            
            if (!$hierarchy) {
                $this->stats['skipped']++;
                Log::warning('Hiérarchie incomplète pour la transaction', [
                    'transaction_id' => $transaction->id,
                    'charging_point_id' => $chargingPoint->id
                ]);
                return;
            }

            // Récupérer les Business Profiles
            $businessProfiles = $this->unifiedService->getBusinessProfilesForHierarchy($hierarchy);
            
            if (!$businessProfiles['admin_integrator'] || !$businessProfiles['integrator_operator']) {
                $this->stats['skipped']++;
                Log::warning('Business Profiles manquants pour la transaction', [
                    'transaction_id' => $transaction->id,
                    'charging_point_id' => $chargingPoint->id
                ]);
                return;
            }

            // Recalculer les parts avec la nouvelle logique
            $totalAmount = (float) $transaction->amount;
            $calculation = $this->unifiedService->calculateSharesWithBusinessProfiles(
                $totalAmount,
                $businessProfiles,
                $hierarchy
            );

            // Vérifier si des modifications sont nécessaires
            $needsUpdate = false;
            $changes = [];

            // Comparer les montants
            $currentAdminShare = (float) $transactionDetail->admin_share_amount;
            $newAdminShare = (float) $calculation['admin_share'];
            if (abs($currentAdminShare - $newAdminShare) > 0.01) {
                $needsUpdate = true;
                $changes['admin_share'] = [
                    'old' => $currentAdminShare,
                    'new' => $newAdminShare
                ];
            }

            $currentIntegratorShare = (float) $transactionDetail->integrator_share_amount;
            $newIntegratorShare = (float) $calculation['integrator_share'];
            if (abs($currentIntegratorShare - $newIntegratorShare) > 0.01) {
                $needsUpdate = true;
                $changes['integrator_share'] = [
                    'old' => $currentIntegratorShare,
                    'new' => $newIntegratorShare
                ];
            }

            $currentOperatorShare = (float) $transactionDetail->operator_share_amount;
            $newOperatorShare = (float) $calculation['operator_share'];
            if (abs($currentOperatorShare - $newOperatorShare) > 0.01) {
                $needsUpdate = true;
                $changes['operator_share'] = [
                    'old' => $currentOperatorShare,
                    'new' => $newOperatorShare
                ];
            }

            // Comparer les pourcentages
            $currentAdminPercentage = (float) $transactionDetail->admin_share_percentage;
            $newAdminPercentage = (float) $calculation['admin_percentage'];
            if (abs($currentAdminPercentage - $newAdminPercentage) > 0.01) {
                $needsUpdate = true;
                $changes['admin_percentage'] = [
                    'old' => $currentAdminPercentage,
                    'new' => $newAdminPercentage
                ];
            }

            $currentIntegratorPercentage = (float) $transactionDetail->integrator_share_percentage;
            $newIntegratorPercentage = (float) $calculation['integrator_percentage'];
            if (abs($currentIntegratorPercentage - $newIntegratorPercentage) > 0.01) {
                $needsUpdate = true;
                $changes['integrator_percentage'] = [
                    'old' => $currentIntegratorPercentage,
                    'new' => $newIntegratorPercentage
                ];
            }

            if ($needsUpdate) {
                if (!$this->dryRun) {
                    DB::beginTransaction();
                    
                    try {
                        // Mettre à jour TransactionDetail
                        $transactionDetail->update([
                            'admin_share_amount' => $newAdminShare,
                            'integrator_share_amount' => $newIntegratorShare,
                            'operator_share_amount' => $newOperatorShare,
                            'admin_share_percentage' => $newAdminPercentage,
                            'integrator_share_percentage' => $newIntegratorPercentage,
                            'calculation_details' => array_merge(
                                $transactionDetail->calculation_details ?? [],
                                [
                                    'recalculated_at' => now()->toISOString(),
                                    'recalculation_changes' => $changes,
                                    'calculation' => $calculation,
                                    'business_profiles' => [
                                        'admin_integrator_id' => $businessProfiles['admin_integrator']->id,
                                        'integrator_operator_id' => $businessProfiles['integrator_operator']->id,
                                    ]
                                ]
                            )
                        ]);

                        DB::commit();
                        $this->stats['updated']++;

                        Log::info('Parts recalculées pour la transaction', [
                            'transaction_id' => $transaction->id,
                            'charging_point_id' => $chargingPoint->id,
                            'changes' => $changes
                        ]);

                    } catch (\Exception $e) {
                        DB::rollBack();
                        throw $e;
                    }
                } else {
                    // Mode dry-run : juste compter
                    $this->stats['updated']++;
                }
            } else {
                $this->stats['skipped']++;
            }

        } catch (\Exception $e) {
            $this->stats['errors']++;
            Log::error('Erreur lors du traitement de la transaction', [
                'transaction_id' => $transaction->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Afficher les statistiques
     */
    protected function displayStatistics()
    {
        $this->info('📈 Statistiques de traitement :');
        $this->table(
            ['Statistique', 'Valeur'],
            [
                ['Total traité', $this->stats['total']],
                ['Mis à jour', $this->stats['updated']],
                ['Ignorés (déjà corrects)', $this->stats['skipped']],
                ['Erreurs', $this->stats['errors']],
            ]
        );

        if ($this->dryRun) {
            $this->warn('⚠️  Mode DRY-RUN : Aucune modification n\'a été appliquée');
            $this->info('Pour appliquer les modifications, exécutez la commande sans --dry-run');
        } else {
            $this->info('✅ Les modifications ont été appliquées avec succès');
        }
    }
}

