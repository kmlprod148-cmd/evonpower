<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Services\ReservationTransactionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixMissingTransactionDetailsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:fix-details 
                            {--reservation= : ID de réservation spécifique}
                            {--all : Traiter toutes les réservations approuvées}
                            {--dry-run : Afficher ce qui serait fait sans modifier les données}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crée les TransactionDetail manquants pour les réservations approuvées';

    /**
     * Execute the console command.
     */
    public function handle(ReservationTransactionService $reservationTransactionService): int
    {
        $this->info('🔍 Recherche des TransactionDetail manquants...');
        $this->newLine();

        $reservationId = $this->option('reservation');
        $all = $this->option('all');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('⚠️  Mode DRY-RUN activé - Aucune modification ne sera effectuée');
            $this->newLine();
        }

        try {
            // Si une réservation spécifique est fournie
            if ($reservationId) {
                $reservation = Reservation::with(['transaction', 'transaction.transactionDetail'])
                    ->find($reservationId);
                
                if (!$reservation) {
                    $this->error("❌ Réservation #{$reservationId} non trouvée");
                    return Command::FAILURE;
                }

                return $this->processReservation($reservation, $reservationTransactionService, $dryRun);
            }

            // Trouver toutes les réservations approuvées sans TransactionDetail
            $query = Reservation::with(['transaction', 'transaction.transactionDetail'])
                ->whereIn('status', ['confirmed', 'completed']);

            if (!$all) {
                // Par défaut, seulement celles sans TransactionDetail
                $query->whereHas('transaction', function($q) {
                    $q->whereDoesntHave('transactionDetail');
                });
            }

            $reservations = $query->get();
            $totalReservations = $reservations->count();

            if ($totalReservations === 0) {
                $this->info('✅ Toutes les réservations approuvées ont déjà un TransactionDetail');
                return Command::SUCCESS;
            }

            $this->info("📊 {$totalReservations} réservation(s) à traiter");
            $this->newLine();

            $bar = $this->output->createProgressBar($totalReservations);
            $bar->start();

            $createdCount = 0;
            $updatedCount = 0;
            $skippedCount = 0;
            $errorCount = 0;
            $errors = [];

            foreach ($reservations as $reservation) {
                try {
                    $result = $this->processReservation($reservation, $reservationTransactionService, $dryRun, false);
                    
                    if ($result['action'] === 'created') {
                        $createdCount++;
                    } elseif ($result['action'] === 'updated') {
                        $updatedCount++;
                    } elseif ($result['action'] === 'skipped') {
                        $skippedCount++;
                    } else {
                        $errorCount++;
                        $errors[] = "Réservation #{$reservation->id}: {$result['error']}";
                    }
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = "Réservation #{$reservation->id}: {$e->getMessage()}";
                    Log::error('Erreur lors du traitement de la réservation', [
                        'reservation_id' => $reservation->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            // Afficher le résumé
            $this->info('📊 Résumé du traitement:');
            $this->table(
                ['Statut', 'Nombre'],
                [
                    ['✅ TransactionDetail créés', $createdCount],
                    ['🔄 TransactionDetail mis à jour', $updatedCount],
                    ['⏭️  Ignorés', $skippedCount],
                    ['❌ Erreurs', $errorCount],
                    ['📊 Total', $totalReservations],
                ]
            );

            if (!empty($errors)) {
                $this->newLine();
                $this->error('❌ Erreurs rencontrées:');
                foreach (array_slice($errors, 0, 10) as $error) {
                    $this->line("  - {$error}");
                }
                if (count($errors) > 10) {
                    $this->line("  ... et " . (count($errors) - 10) . " autres erreurs");
                }
            }

            if ($errorCount > 0) {
                return Command::FAILURE;
            }

            $this->newLine();
            $this->info('✅ Traitement terminé avec succès');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Erreur fatale: {$e->getMessage()}");
            Log::error('Erreur fatale lors de la correction des TransactionDetail', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Traiter une réservation
     */
    protected function processReservation(
        Reservation $reservation,
        ReservationTransactionService $reservationTransactionService,
        bool $dryRun = false,
        bool $verbose = true
    ): array {
        try {
            $totalAmount = $reservation->total_amount ?? $reservation->estimated_cost ?? 0;

            if ($totalAmount <= 0) {
                if ($verbose) {
                    $this->warn("   ⚠️  Réservation #{$reservation->id}: Montant invalide ({$totalAmount})");
                }
                return ['action' => 'skipped', 'reason' => 'Montant invalide'];
            }

            $transaction = $reservation->transaction;
            $transactionDetail = $transaction?->transactionDetail;

            if ($verbose) {
                $this->info("🔍 Traitement de la réservation #{$reservation->id}");
                $this->line("   - Montant: " . number_format($totalAmount, 2) . " EUR");
            }

            if (!$transaction) {
                if ($dryRun) {
                    if ($verbose) {
                        $this->warn("   🔄 Transaction serait créée");
                    }
                    return ['action' => 'created'];
                }

                DB::beginTransaction();
                try {
                    $result = $reservationTransactionService->processReservationTransaction($reservation);
                    if ($result['success']) {
                        DB::commit();
                        if ($verbose) {
                            $this->line("   ✅ Transaction créée: #{$result['main_transaction']->id}");
                        }
                        return ['action' => 'created'];
                    } else {
                        DB::rollBack();
                        return ['action' => 'error', 'error' => $result['error'] ?? 'Erreur inconnue'];
                    }
                } catch (\Exception $e) {
                    DB::rollBack();
                    return ['action' => 'error', 'error' => $e->getMessage()];
                }
            }

            if (!$transactionDetail) {
                if ($dryRun) {
                    if ($verbose) {
                        $this->warn("   🔄 TransactionDetail serait créé");
                    }
                    return ['action' => 'created'];
                }

                // Le TransactionDetail sera créé automatiquement par processReservationTransaction
                // mais la transaction existe déjà, donc on doit le créer manuellement
                DB::beginTransaction();
                try {
                    $hierarchy = $reservationTransactionService->getCompleteHierarchy($reservation->charging_point_id);
                    if (!$hierarchy) {
                        DB::rollBack();
                        return ['action' => 'error', 'error' => 'Hiérarchie incomplète'];
                    }

                    $calculation = $reservationTransactionService->calculateSharesWithBusinessProfiles($totalAmount, $hierarchy);
                    
                    $transactionDetail = TransactionDetail::create([
                        'transaction_id' => $transaction->id,
                        'admin_share_amount' => round($calculation['admin_share'], 4),
                        'integrator_share_amount' => round($calculation['integrator_share'], 4),
                        'operator_share_amount' => round($calculation['operator_share'], 4),
                        'admin_share_percentage' => round($calculation['admin_percentage'] ?? 0, 4),
                        'integrator_share_percentage' => round($calculation['integrator_percentage'] ?? 0, 4),
                        'admin_creator_id' => $hierarchy['admin']->id ?? null,
                        'integrator_creator_id' => $hierarchy['integrator']->user_id ?? $hierarchy['integrator']->id ?? null,
                        'operator_id' => $hierarchy['operator']->id ?? null,
                        'calculation_details' => [
                            'created_at' => now()->toIso8601String(),
                            'creation_reason' => 'Correction automatique via commande Artisan',
                            'calculation' => $calculation,
                        ]
                    ]);

                    DB::commit();
                    if ($verbose) {
                        $this->line("   ✅ TransactionDetail créé: ID #{$transactionDetail->id}");
                    }
                    return ['action' => 'created'];
                } catch (\Exception $e) {
                    DB::rollBack();
                    return ['action' => 'error', 'error' => $e->getMessage()];
                }
            }

            // Vérifier si les montants sont à 0 et doivent être recalculés
            $currentAdminShare = (float) ($transactionDetail->admin_share_amount ?? 0);
            if ($currentAdminShare <= 0) {
                if ($dryRun) {
                    if ($verbose) {
                        $this->warn("   🔄 TransactionDetail serait mis à jour (frais admin = 0)");
                    }
                    return ['action' => 'updated'];
                }

                DB::beginTransaction();
                try {
                    $hierarchy = $reservationTransactionService->getCompleteHierarchy($reservation->charging_point_id);
                    if (!$hierarchy) {
                        DB::rollBack();
                        return ['action' => 'error', 'error' => 'Hiérarchie incomplète'];
                    }

                    $calculation = $reservationTransactionService->calculateSharesWithBusinessProfiles($totalAmount, $hierarchy);
                    
                    $transactionDetail->update([
                        'admin_share_amount' => round($calculation['admin_share'], 4),
                        'integrator_share_amount' => round($calculation['integrator_share'], 4),
                        'operator_share_amount' => round($calculation['operator_share'], 4),
                    ]);

                    DB::commit();
                    if ($verbose) {
                        $this->line("   ✅ TransactionDetail mis à jour");
                    }
                    return ['action' => 'updated'];
                } catch (\Exception $e) {
                    DB::rollBack();
                    return ['action' => 'error', 'error' => $e->getMessage()];
                }
            }

            if ($verbose) {
                $this->line("   ✅ TransactionDetail existe déjà avec frais admin: " . number_format($currentAdminShare, 2) . " EUR");
            }
            return ['action' => 'skipped', 'reason' => 'Déjà à jour'];

        } catch (\Exception $e) {
            return ['action' => 'error', 'error' => $e->getMessage()];
        }
    }
}

