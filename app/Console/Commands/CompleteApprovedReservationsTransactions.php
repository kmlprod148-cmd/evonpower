<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reservation;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Services\ReservationTransactionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CompleteApprovedReservationsTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:complete-transactions 
                            {--dry-run : Afficher les réservations sans corriger}
                            {--limit=100 : Limiter le nombre de réservations à traiter}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Complète les transactions pour les réservations approuvées qui n\'ont pas de TransactionDetail avec toutes les parts, ou créent les transactions manquantes';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');

        $this->info('🔍 Recherche des réservations approuvées sans TransactionDetail complet ou sans transaction...');
        
        if ($dryRun) {
            $this->warn('⚠️  Mode DRY-RUN activé : aucune modification ne sera effectuée');
        }

        // Trouver les réservations approuvées avec transaction mais sans TransactionDetail complet
        // Note: La relation Reservation->Transaction utilise reservation_id sur transactions, pas transaction_id sur reservations
        $reservationsWithTransaction = Reservation::where('status', 'approved')
            ->whereHas('transaction', function($query) {
                $query->whereDoesntHave('transactionDetail');
            })
            ->with(['transaction'])
            ->limit($limit)
            ->get();

        // Trouver les réservations approuvées sans transaction du tout
        $reservationsWithoutTransaction = Reservation::where('status', 'approved')
            ->whereDoesntHave('transaction')
            ->with(['chargingPoint', 'pricingPlan'])
            ->limit($limit - $reservationsWithTransaction->count())
            ->get();

        // Combiner les deux listes
        $reservations = $reservationsWithTransaction->merge($reservationsWithoutTransaction);

        $total = $reservations->count();
        $completed = 0;
        $errors = [];

        $this->info("📊 Analyse de {$total} réservations...");
        
        if ($total === 0) {
            $this->info('✅ Toutes les réservations approuvées ont déjà leurs TransactionDetail complets !');
            return Command::SUCCESS;
        }

        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        $reservationTransactionService = app(ReservationTransactionService::class);

        foreach ($reservations as $reservation) {
            try {
                if (!$dryRun) {
                    DB::beginTransaction();

                    // S'assurer que la réservation a total_amount (nécessaire pour le calcul)
                    $totalAmount = $reservation->total_amount ?? $reservation->total_cost ?? $reservation->estimated_cost ?? 0;
                    
                    if ($totalAmount <= 0) {
                        throw new \Exception("Réservation #{$reservation->id} n'a pas de montant valide (total_amount, total_cost, estimated_cost sont tous nuls ou absents)");
                    }
                    
                    // Mettre à jour total_amount si nécessaire
                    if (!$reservation->total_amount) {
                        $reservation->update(['total_amount' => $totalAmount]);
                    }

                    // Si une transaction existe déjà mais n'a pas de TransactionDetail, 
                    // le service créera une nouvelle transaction (qui remplacera l'ancienne via la relation)
                    // Utiliser ReservationTransactionService pour créer toutes les parts
                    $result = $reservationTransactionService->processReservationTransaction($reservation);

                    if ($result['success']) {
                        $transactionDetail = $result['transaction_detail'];
                        
                        Log::info('Transaction complétée pour réservation approuvée', [
                            'reservation_id' => $reservation->id,
                            'transaction_id' => $result['main_transaction']->id,
                            'transaction_detail_id' => $transactionDetail->id,
                            'admin_share' => $transactionDetail->admin_share_amount,
                            'integrator_share' => $transactionDetail->integrator_share_amount,
                            'operator_share' => $transactionDetail->operator_share_amount
                        ]);

                        DB::commit();
                        $completed++;
                    } else {
                        DB::rollBack();
                        $errors[] = [
                            'reservation_id' => $reservation->id,
                            'error' => $result['error'] ?? 'Erreur inconnue'
                        ];
                    }
                } else {
                    // Mode dry-run : juste afficher
                    if ($reservation->transaction_id) {
                        $this->line("\n  📋 Réservation #{$reservation->id} - Transaction #{$reservation->transaction_id} - Manque TransactionDetail");
                    } else {
                        $this->line("\n  📋 Réservation #{$reservation->id} - Pas de transaction - Montant: " . ($reservation->total_cost ?? $reservation->estimated_cost ?? 'N/A') . " EUR");
                    }
                }
                
                $progressBar->advance();
            } catch (\Exception $e) {
                if (!$dryRun) {
                    DB::rollBack();
                }
                
                $errors[] = [
                    'reservation_id' => $reservation->id,
                    'error' => $e->getMessage()
                ];
                
                Log::error('Erreur lors de la complétion de transaction pour réservation', [
                    'reservation_id' => $reservation->id,
                    'error' => $e->getMessage()
                ]);
                
                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Afficher le résumé
        $this->info('📈 Résumé des résultats :');
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Réservations analysées', $total],
                ['Transactions complétées', $dryRun ? 0 : $completed],
                ['Erreurs', count($errors)],
            ]
        );

        if (!empty($errors)) {
            $this->newLine();
            $this->warn('⚠️  Erreurs rencontrées :');
            
            $errorData = array_map(function ($error) {
                return [
                    $error['reservation_id'],
                    substr($error['error'], 0, 80) . (strlen($error['error']) > 80 ? '...' : ''),
                ];
            }, array_slice($errors, 0, 10));
            
            $this->table(
                ['Réservation ID', 'Erreur'],
                $errorData
            );

            if (count($errors) > 10) {
                $this->info('... et ' . (count($errors) - 10) . ' autres erreurs');
            }
        }

        if ($dryRun && $total > 0) {
            $this->newLine();
            $this->info('💡 Pour compléter ces transactions, exécutez la commande sans l\'option --dry-run');
        }

        return Command::SUCCESS;
    }
}

