<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Services\ReservationTransactionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class FixAllPastTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:fix-all 
                            {--dry-run : Mode simulation sans modification}
                            {--limit= : Limiter le nombre de transactions à traiter}
                            {--from-id= : ID de transaction de départ}
                            {--skip-errors : Continuer même en cas d\'erreur}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corrige toutes les transactions passées en recalculant les parts selon les Business Profiles et en mettant à jour les balances';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $fromId = $this->option('from-id') ? (int) $this->option('from-id') : null;
        $skipErrors = $this->option('skip-errors');

        $this->info('🔧 Correction de toutes les transactions passées');
        $this->newLine();

        if ($dryRun) {
            $this->warn('⚠️  MODE SIMULATION : Aucune modification ne sera effectuée');
            $this->newLine();
        }

        // Récupérer les transactions à traiter
        $query = Transaction::whereNotNull('charging_point_id')
            ->where('price_total', '>', 0)
            ->with(['transactionDetail', 'chargingPoint'])
            ->orderBy('id');

        if ($fromId) {
            $query->where('id', '>=', $fromId);
        }

        if ($limit) {
            $query->limit($limit);
        }

        $totalTransactions = $query->count();
        $this->info("📊 Total de transactions à traiter : {$totalTransactions}");
        $this->newLine();

        if ($totalTransactions === 0) {
            $this->warn('Aucune transaction à traiter');
            return 0;
        }

        // Confirmation
        if (!$dryRun && !$this->confirm('Voulez-vous continuer ?', true)) {
            $this->info('Opération annulée');
            return 0;
        }

        $service = app(ReservationTransactionService::class);
        $progressBar = $this->output->createProgressBar($totalTransactions);
        $progressBar->start();

        $stats = [
            'total' => 0,
            'success' => 0,
            'errors' => 0,
            'skipped' => 0,
            'updated' => 0,
            'created' => 0,
            'errors_list' => []
        ];

        $query->chunk(50, function ($transactions) use ($service, &$stats, $dryRun, $skipErrors, $progressBar) {
            foreach ($transactions as $transaction) {
                $stats['total']++;

                try {
                    // Vérifier si la transaction a déjà un TransactionDetail avec des parts correctes
                    if ($transaction->transactionDetail) {
                        $adminShare = (float) ($transaction->transactionDetail->admin_share_amount ?? 0);
                        $integratorShare = (float) ($transaction->transactionDetail->integrator_share_amount ?? 0);
                        $operatorShare = (float) ($transaction->transactionDetail->operator_share_amount ?? 0);
                        $totalAmount = (float) ($transaction->price_total ?? $transaction->amount ?? 0);
                        
                        // Vérifier si les parts sont déjà cohérentes
                        $sumOfShares = round($adminShare + $integratorShare + $operatorShare, 2);
                        $totalRounded = round($totalAmount, 2);
                        $difference = abs($sumOfShares - $totalRounded);
                        
                        // Si les parts sont déjà cohérentes et que la transaction a été recalculée récemment, on peut la sauter
                        $calculationDetails = $transaction->transactionDetail->calculation_details ?? [];
                        $recalculatedAt = $calculationDetails['recalculated_at'] ?? null;
                        
                        if ($difference <= 0.01 && $recalculatedAt) {
                            $stats['skipped']++;
                            $progressBar->advance();
                            continue;
                        }
                    }

                    // Recalculer les parts
                    $result = $service->recalculateTransactionShares($transaction, true);

                    if ($result['success']) {
                        $stats['success']++;
                        
                        // Déterminer si c'est une création ou une mise à jour
                        $hadTransactionDetail = $transaction->transactionDetail !== null;
                        $transaction->refresh();
                        $transaction->load('transactionDetail');
                        $hasTransactionDetailNow = $transaction->transactionDetail !== null;
                        
                        if (!$hadTransactionDetail && $hasTransactionDetailNow) {
                            $stats['created']++;
                        } else {
                            $stats['updated']++;
                        }

                        // Mettre à jour la transaction principale avec les nouvelles parts
                        if (!$dryRun) {
                            DB::beginTransaction();
                            try {
                                $transaction->refresh();
                                $transaction->load('transactionDetail');
                                $transactionDetail = $transaction->transactionDetail;
                                
                                if (!$transactionDetail) {
                                    throw new Exception('TransactionDetail non trouvé après recalcul');
                                }
                                
                                // Mettre à jour les colonnes de parts dans la transaction principale
                                $transaction->update([
                                    'admin_share_amount' => round($transactionDetail->admin_share_amount ?? 0, 4),
                                    'integrator_share_amount' => round($transactionDetail->integrator_share_amount ?? 0, 4),
                                    'operator_share_amount' => round($transactionDetail->operator_share_amount ?? 0, 4),
                                    'admin_commission' => round($transactionDetail->admin_share_amount ?? 0, 4),
                                    'integrator_commission' => round($transactionDetail->integrator_share_amount ?? 0, 4),
                                    'partner_commission' => round($transactionDetail->operator_share_amount ?? 0, 4),
                                ]);

                                // Récupérer les wallets et mettre à jour les balances dans la transaction
                                // Note : On met à jour les balances affichées dans la transaction, mais on ne modifie pas les wallets eux-mêmes
                                // car ils reflètent déjà l'état réel des balances après toutes les opérations
                                if ($transaction->admin_wallet_id || $transaction->integrator_wallet_id || $transaction->operator_wallet_id) {
                                    $adminWallet = $transaction->admin_wallet_id ? \App\Models\Wallet::find($transaction->admin_wallet_id) : null;
                                    $integratorWallet = $transaction->integrator_wallet_id ? \App\Models\Wallet::find($transaction->integrator_wallet_id) : null;
                                    $operatorWallet = $transaction->operator_wallet_id ? \App\Models\Wallet::find($transaction->operator_wallet_id) : null;

                                    if ($adminWallet) {
                                        $adminWallet->refresh();
                                    }
                                    if ($integratorWallet) {
                                        $integratorWallet->refresh();
                                    }
                                    if ($operatorWallet) {
                                        $operatorWallet->refresh();
                                    }

                                    $transaction->update([
                                        'current_balance' => $operatorWallet ? (float) $operatorWallet->balance : null,
                                        'admin_current_balance' => $adminWallet ? (float) $adminWallet->balance : null,
                                        'integrator_current_balance' => $integratorWallet ? (float) $integratorWallet->balance : null,
                                        'operator_current_balance' => $operatorWallet ? (float) $operatorWallet->balance : null,
                                    ]);
                                }

                                DB::commit();

                                Log::info('Transaction corrigée avec succès', [
                                    'transaction_id' => $transaction->id,
                                    'admin_share' => $transactionDetail->admin_share_amount,
                                    'integrator_share' => $transactionDetail->integrator_share_amount,
                                    'operator_share' => $transactionDetail->operator_share_amount
                                ]);
                            } catch (Exception $e) {
                                DB::rollBack();
                                throw $e;
                            }
                        }
                    } else {
                        $stats['errors']++;
                        $errorMsg = 'Transaction #' . $transaction->id . ': ' . implode(', ', $result['errors']);
                        $stats['errors_list'][] = $errorMsg;
                        
                        Log::warning('Erreur lors de la correction de la transaction', [
                            'transaction_id' => $transaction->id,
                            'charging_point_id' => $transaction->charging_point_id,
                            'errors' => $result['errors']
                        ]);

                        if (!$skipErrors) {
                            $this->newLine();
                            $this->error("❌ Erreur : {$errorMsg}");
                            
                            if (!$this->confirm('Continuer avec les transactions suivantes ?', true)) {
                                break;
                            }
                        }
                    }
                } catch (Exception $e) {
                    $stats['errors']++;
                    $errorMsg = 'Transaction #' . $transaction->id . ': ' . $e->getMessage();
                    $stats['errors_list'][] = $errorMsg;
                    
                    Log::error('Erreur lors du traitement de la transaction', [
                        'transaction_id' => $transaction->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);

                    if (!$skipErrors) {
                        $this->newLine();
                        $this->error("❌ Erreur : {$errorMsg}");
                        
                        if (!$this->confirm('Continuer avec les transactions suivantes ?', true)) {
                            break;
                        }
                    }
                }

                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->newLine(2);

        // Afficher le rapport
        $this->info('📊 RAPPORT FINAL');
        $this->newLine();
        $this->table(
            ['Statut', 'Nombre'],
            [
                ['Total traité', $stats['total']],
                ['✅ Succès', $stats['success']],
                ['📝 TransactionDetail créé', $stats['created']],
                ['🔄 TransactionDetail mis à jour', $stats['updated']],
                ['⏭️  Ignoré (déjà correct)', $stats['skipped']],
                ['❌ Erreurs', $stats['errors']],
            ]
        );

        if ($stats['errors'] > 0 && !empty($stats['errors_list'])) {
            $this->newLine();
            $this->warn('⚠️  Liste des erreurs :');
            foreach (array_slice($stats['errors_list'], 0, 20) as $error) {
                $this->line("  • {$error}");
            }
            if (count($stats['errors_list']) > 20) {
                $remaining = count($stats['errors_list']) - 20;
                $this->line("  ... et {$remaining} autre(s) erreur(s)");
            }
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('⚠️  MODE SIMULATION : Aucune modification n\'a été effectuée');
            $this->info('Pour appliquer les modifications, relancez la commande sans --dry-run');
        } else {
            $this->newLine();
            $this->info('✅ Correction terminée !');
        }

        return 0;
    }
}
