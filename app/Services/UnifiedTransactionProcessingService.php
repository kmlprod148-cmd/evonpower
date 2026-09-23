<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Reservation;
use App\Models\ChargingSession;
use App\Enums\TransactionType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service unifié pour le traitement des transactions
 * 
 * Ce service centralise l'appel aux services de transaction existants:
 * - ReservationTransactionService: appelé lors de la finalisation de toute réservation/session
 * - TransactionFinalizationService: traite et valide les transactions avant enregistrement définitif
 * 
 * Chaque transaction doit impérativement passer par ces deux services dans l'ordre établi.
 */
class UnifiedTransactionProcessingService
{
    protected ReservationTransactionService $reservationTransactionService;
    protected TransactionFinalizationService $transactionFinalizationService;
    protected TransactionService $transactionService;

    public function __construct(
        ReservationTransactionService $reservationTransactionService,
        TransactionFinalizationService $transactionFinalizationService,
        TransactionService $transactionService
    ) {
        $this->reservationTransactionService = $reservationTransactionService;
        $this->transactionFinalizationService = $transactionFinalizationService;
        $this->transactionService = $transactionService;
    }

    /**
     * Traiter une réservation et créer les transactions associées
     * 
     * @param Reservation $reservation
     * @param array $options Options supplémentaires
     * @return array
     */
    public function processReservation(Reservation $reservation, array $options = []): array
    {
        try {
            DB::beginTransaction();

            Log::info('UnifiedTransactionProcessingService: Début du traitement de la réservation', [
                'reservation_id' => $reservation->id,
                'options' => $options
            ]);

            // ÉTAPE 1: Appeler ReservationTransactionService pour créer les transactions
            $transactionResult = $this->reservationTransactionService->processReservationTransaction($reservation);

            if (!($transactionResult['success'] ?? false)) {
                throw new \Exception($transactionResult['error'] ?? 'Erreur lors du traitement de la transaction de réservation');
            }

            // Récupérer la transaction principale créée
            $transaction = $transactionResult['transaction'] ?? null;

            if (!$transaction) {
                // Essayer de récupérer depuis la réservation
                $transaction = $reservation->transaction;
            }

            // ÉTAPE 2: Valider et finaliser la transaction via TransactionFinalizationService
            if ($transaction) {
                $this->finalizeTransaction($transaction, $options);
            }

            DB::commit();

            Log::info('UnifiedTransactionProcessingService: Réservation traitée avec succès', [
                'reservation_id' => $reservation->id,
                'transaction_id' => $transaction?->id
            ]);

            return [
                'success' => true,
                'transaction' => $transaction,
                'transaction_result' => $transactionResult,
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('UnifiedTransactionProcessingService: Erreur lors du traitement de la réservation', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Traiter une session de charge et créer les transactions associées
     * 
     * @param ChargingSession $session
     * @param array $options Options supplémentaires
     * @return array
     */
    public function processChargingSession(ChargingSession $session, array $options = []): array
    {
        try {
            DB::beginTransaction();

            Log::info('UnifiedTransactionProcessingService: Début du traitement de la session de charge', [
                'session_id' => $session->id,
                'options' => $options
            ]);

            // ÉTAPE 1: Créer la transaction via TransactionService
            $transactionData = $this->prepareTransactionData($session, $options);
            $transaction = $this->transactionService->createTransaction($transactionData);

            // ÉTAPE 2: Finaliser la transaction via TransactionFinalizationService
            $this->finalizeTransaction($transaction, $options);

            DB::commit();

            Log::info('UnifiedTransactionProcessingService: Session de charge traitée avec succès', [
                'session_id' => $session->id,
                'transaction_id' => $transaction->id
            ]);

            return [
                'success' => true,
                'transaction' => $transaction,
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('UnifiedTransactionProcessingService: Erreur lors du traitement de la session', [
                'session_id' => $session->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Finaliser une transaction existante
     * 
     * @param Transaction $transaction
     * @param array $options
     * @return Transaction
     */
    public function finalizeTransaction(Transaction $transaction, array $options = []): Transaction
    {
        // Vérifier si la transaction est dans un état finalisable
        $finalizableStatuses = ['completed', 'approved', 'paid', 'confirmed'];
        
        if (!in_array($transaction->status, $finalizableStatuses)) {
            Log::info('Transaction non finalisable, statut: ' . $transaction->status, [
                'transaction_id' => $transaction->id
            ]);
            return $transaction;
        }

        // Appeler TransactionFinalizationService pour valider et compléter la transaction
        $repartition = $this->transactionFinalizationService->finalizeTransaction($transaction, $options);

        Log::info('Transaction finalisée avec succès', [
            'transaction_id' => $transaction->id,
            'repartition_id' => $repartition?->id
        ]);

        return $transaction->fresh();
    }

    /**
     * Préparer les données de transaction pour une session de charge
     * 
     * @param ChargingSession $session
     * @param array $options
     * @return array
     */
    protected function prepareTransactionData(ChargingSession $session, array $options = []): array
    {
        $transactionType = $options['transaction_type'] ?? TransactionType::TYPE_CHARGING_SESSION;
        $transactionCategory = TransactionType::getCategory($transactionType);

        return [
            'user_id' => $session->user_id,
            'charging_point_id' => $session->charging_point_id,
            'session_id' => $session->id,
            'amount' => $session->total_cost ?? 0,
            'price_total' => $session->total_cost ?? 0,
            'currency' => 'EUR',
            'status' => 'completed',
            'transaction_type' => $transactionType,
            'transaction_category' => $transactionCategory,
            'energy_delivered' => $session->energy_delivered ?? 0,
            'duration_minutes' => $session->duration ?? 0,
            'start_timestamp' => $session->start_time,
            'stop_timestamp' => $session->stop_time,
            'metadata' => [
                'source' => 'UnifiedTransactionProcessingService',
                'original_session_id' => $session->id,
            ],
        ];
    }

    /**
     * Mettre à jour le type et la catégorie d'une transaction existante
     * 
     * @param Transaction $transaction
     * @param string $type
     * @return Transaction
     */
    public function updateTransactionType(Transaction $transaction, string $type): Transaction
    {
        if (!TransactionType::isValidType($type)) {
            throw new \InvalidArgumentException("Type de transaction invalide: {$type}");
        }

        $category = TransactionType::getCategory($type);

        $transaction->update([
            'transaction_type' => $type,
            'transaction_category' => $category,
        ]);

        Log::info('Type de transaction mis à jour', [
            'transaction_id' => $transaction->id,
            'type' => $type,
            'category' => $category
        ]);

        return $transaction->fresh();
    }

    /**
     * Obtenir les statistiques de transactions par type
     * 
     * @param array $filters
     * @return array
     */
    public function getTransactionStats(array $filters = []): array
    {
        $query = Transaction::query();

        if (!empty($filters['transaction_type'])) {
            $query->where('transaction_type', $filters['transaction_type']);
        }

        if (!empty($filters['transaction_category'])) {
            $query->where('transaction_category', $filters['transaction_category']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return [
            'total_count' => $query->count(),
            'total_amount' => $query->sum('price_total'),
            'by_type' => $query->clone()
                ->selectRaw('transaction_type, COUNT(*) as count, SUM(price_total) as total')
                ->groupBy('transaction_type')
                ->get(),
            'by_category' => $query->clone()
                ->selectRaw('transaction_category, COUNT(*) as count, SUM(price_total) as total')
                ->groupBy('transaction_category')
                ->get(),
            'by_status' => $query->clone()
                ->selectRaw('status, COUNT(*) as count, SUM(price_total) as total')
                ->groupBy('status')
                ->get(),
        ];
    }

    /**
     * Traiter un lot de transactions
     * 
     * @param array $transactionIds
     * @param array $options
     * @return array
     */
    public function processBatch(array $transactionIds, array $options = []): array
    {
        $results = [];
        $successCount = 0;
        $errorCount = 0;

        foreach ($transactionIds as $transactionId) {
            try {
                $transaction = Transaction::find($transactionId);
                
                if (!$transaction) {
                    $results[$transactionId] = [
                        'success' => false,
                        'error' => 'Transaction non trouvée'
                    ];
                    $errorCount++;
                    continue;
                }

                $this->finalizeTransaction($transaction, $options);
                $results[$transactionId] = ['success' => true];
                $successCount++;

            } catch (\Exception $e) {
                $results[$transactionId] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
                $errorCount++;
            }
        }

        return [
            'total' => count($transactionIds),
            'success' => $successCount,
            'errors' => $errorCount,
            'results' => $results,
        ];
    }
}
