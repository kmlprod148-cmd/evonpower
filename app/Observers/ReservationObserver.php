<?php

namespace App\Observers;

use App\Models\Reservation;
use App\Services\ReservationTransactionService;
use App\Services\BalanceSynchronizationService;
use App\Services\TransactionStatusSyncService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ReservationObserver
{
    /**
     * Handle the Reservation "updated" event.
     *
     * 1. Lorsqu'une réservation passe à 'approved', créer automatiquement les transactions
     * 2. Lorsque payment_status devient PAID (prépayé ou postpayé), synchroniser le statut de la transaction
     */
    public function updated(Reservation $reservation)
    {
        // Vérifier si le statut vient de passer à 'confirmed' ou 'completed'
        $statusValue = $reservation->status instanceof \App\Enums\ReservationStatus
            ? $reservation->status->value
            : $reservation->status;

        if ($reservation->wasChanged('status') && in_array($statusValue, ['confirmed', 'completed'])) {
            $this->handleReservationApproved($reservation);
        }

        if ($reservation->wasChanged('status') && in_array($statusValue, ['canceled', 'cancelled'])) {
            try {
                app(\App\Services\ReservationParticipantService::class)
                    ->cancelReservation($reservation, 'status_changed');
            } catch (\Throwable $e) {
                Log::warning('ReservationObserver: participant cancel failed', [
                    'reservation_id' => $reservation->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Synchronisation automatique du statut transaction ET des soldes quand payment_status devient PAID
        // Prépayé : paiement immédiat → PAID
        // Postpayé : débit fin de session → PAID
        // Aucune intervention du propriétaire de la borne requise
        if ($reservation->wasChanged('payment_status')) {
            $paymentStatus = strtoupper((string) ($reservation->payment_status ?? ''));
            if (in_array($paymentStatus, ['PAID', 'PAYE'])) {
                $syncService = app(TransactionStatusSyncService::class);
                $syncService->syncForReservation($reservation);
                // Synchroniser les soldes admin/intégrateur/opérateur pour refléter la réservation payée
                $this->syncBalancesWhenPaymentConfirmed($reservation);
                try {
                    app(\App\Services\ReservationParticipantService::class)
                        ->handlePaymentConfirmation($reservation);
                } catch (\Throwable $e) {
                    Log::warning('ReservationObserver: participant payment confirmation failed', [
                        'reservation_id' => $reservation->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        if ($reservation->wasChanged('estimated_cost') || $reservation->wasChanged('actual_cost')) {
            try {
                app(\App\Services\ReservationParticipantService::class)
                    ->recalculateShares($reservation);
            } catch (\Throwable $e) {
                Log::warning('ReservationObserver: participant share recalculation failed', [
                    'reservation_id' => $reservation->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle the Reservation "created" event.
     * 
     * Si une réservation est créée directement avec status 'confirmed' ou 'completed',
     * traiter immédiatement
     */
    public function created(Reservation $reservation)
    {
        $statusValue = $reservation->status instanceof \App\Enums\ReservationStatus 
            ? $reservation->status->value 
            : $reservation->status;
            
        if (in_array($statusValue, ['confirmed', 'completed'])) {
            $this->handleReservationApproved($reservation);
        }
    }

    /**
     * Traiter une réservation approuvée
     * 
     * @param Reservation $reservation
     * @return void
     */
    protected function handleReservationApproved(Reservation $reservation): void
    {
        try {
            // Synchroniser le statut transaction si réservation payée (évite "En attente" pour PAID)
            $reservation->ensureTransactionConfirmedIfPaid();

            // Démarrer automatiquement la session de recharge si la borne est configurée
            $this->startChargingSessionIfNeeded($reservation);

            // Si une transaction existe déjà avec TransactionDetail, vérifier la cohérence
            if ($reservation->transaction && $reservation->transaction->transactionDetail) {
                Log::info('Transaction et TransactionDetail existent déjà pour la réservation', [
                    'reservation_id' => $reservation->id,
                    'transaction_id' => $reservation->transaction->id
                ]);
                
                // Vérifier la cohérence et synchroniser les balances
                $this->ensureConsistencyAndSync($reservation);
                return;
            }

            // Si une transaction existe mais n'a pas de TransactionDetail, le créer automatiquement
            if ($reservation->transaction && !$reservation->transaction->transactionDetail) {
                Log::info('Transaction existe mais TransactionDetail manquant - Création automatique', [
                    'reservation_id' => $reservation->id,
                    'transaction_id' => $reservation->transaction->id
                ]);

                try {
                    $reservationTransactionService = app(ReservationTransactionService::class);
                    $result = $reservationTransactionService->createRetroactiveTransactionDetail($reservation->transaction);

                    if ($result['success']) {
                        Log::info('TransactionDetail créé automatiquement pour transaction existante', [
                            'reservation_id' => $reservation->id,
                            'transaction_id' => $reservation->transaction->id,
                            'transaction_detail_id' => $result['transaction_detail']->id ?? null,
                            'admin_share' => $result['transaction_detail']->admin_share_amount ?? 0,
                            'integrator_share' => $result['transaction_detail']->integrator_share_amount ?? 0,
                            'operator_share' => $result['transaction_detail']->operator_share_amount ?? 0
                        ]);

                        // Synchroniser les balances après création
                        $this->synchronizeBalances([
                            'main_transaction' => $reservation->transaction,
                            'transaction_detail' => $result['transaction_detail'],
                            'hierarchy' => $this->getHierarchy($reservation)
                        ]);
                    } else {
                        Log::warning('Échec de la création automatique de TransactionDetail', [
                            'reservation_id' => $reservation->id,
                            'transaction_id' => $reservation->transaction->id,
                            'errors' => $result['errors'] ?? []
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Exception lors de la création automatique de TransactionDetail', [
                        'reservation_id' => $reservation->id,
                        'transaction_id' => $reservation->transaction->id,
                        'error' => $e->getMessage()
                    ]);
                }
                return;
            }

            // Vérifier que le montant est valide
            $totalAmount = $reservation->estimated_cost ?? $reservation->amount ?? $reservation->total_amount ?? 0;
            
            if ($totalAmount <= 0) {
                Log::warning('Réservation approuvée sans montant valide - Transaction non créée', [
                    'reservation_id' => $reservation->id,
                    'estimated_cost' => $reservation->estimated_cost,
                    'amount' => $reservation->amount,
                    'total_amount' => $reservation->total_amount
                ]);
                return;
            }

            Log::info('Réservation approuvée détectée - Création automatique des transactions', [
                'reservation_id' => $reservation->id,
                'total_amount' => $totalAmount,
                'status' => $reservation->status
            ]);

            // Utiliser le service centralisé pour créer toutes les transactions
            $reservationTransactionService = app(ReservationTransactionService::class);
            $result = $reservationTransactionService->processReservationTransaction($reservation);

            if ($result['success']) {
                Log::info('Transactions créées automatiquement pour réservation approuvée', [
                    'reservation_id' => $reservation->id,
                    'transaction_id' => $result['main_transaction']->id ?? null,
                    'transaction_detail_id' => $result['transaction_detail']->id ?? null,
                    'admin_share' => $result['transaction_detail']->admin_share_amount ?? 0,
                    'integrator_share' => $result['transaction_detail']->integrator_share_amount ?? 0,
                    'operator_share' => $result['transaction_detail']->operator_share_amount ?? 0
                ]);

                // Synchroniser les balances après création
                $this->synchronizeBalances($result);
                
                // CORRECTION : Synchroniser explicitement la balance admin pour chaque réservation approuvée
                // S'assurer que la balance admin est toujours synchronisée, même si la hiérarchie n'est pas correctement récupérée
                $this->ensureAdminBalanceSynchronized($result);
            } else {
                Log::error('Échec de la création automatique des transactions pour réservation approuvée', [
                    'reservation_id' => $reservation->id,
                    'error' => $result['error'] ?? 'Erreur inconnue'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Exception lors du traitement automatique de la réservation approuvée', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Vérifier la cohérence et synchroniser les balances pour une réservation existante
     * 
     * @param Reservation $reservation
     * @return void
     */
    protected function ensureConsistencyAndSync(Reservation $reservation): void
    {
        try {
            $transaction = $reservation->transaction;
            if (!$transaction || !$transaction->transactionDetail) {
                return;
            }

            $detail = $transaction->transactionDetail;
            $totalAmount = (float) ($transaction->price_total ?? $transaction->amount ?? 0);
            $adminShare = (float) ($detail->admin_share_amount ?? 0);
            $integratorShare = (float) ($detail->integrator_share_amount ?? 0);
            $operatorShare = (float) ($detail->operator_share_amount ?? 0);
            $sumOfShares = round($adminShare + $integratorShare + $operatorShare, 2);
            $totalAmountRounded = round($totalAmount, 2);
            $difference = abs($sumOfShares - $totalAmountRounded);

            $reservationTransactionService = app(ReservationTransactionService::class);

            // Si incohérence détectée, recalculer
            if ($difference > 0.01 && $totalAmount > 0) {
                Log::warning('Incohérence détectée dans TransactionDetail - Recalcul automatique', [
                    'reservation_id' => $reservation->id,
                    'transaction_id' => $transaction->id,
                    'difference' => $difference
                ]);

                $result = $reservationTransactionService->recalculateTransactionShares($transaction, true);

                if ($result['success']) {
                    Log::info('Parts recalculées et corrigées automatiquement', [
                        'reservation_id' => $reservation->id,
                        'transaction_id' => $transaction->id
                    ]);
                }
            }

            // IMPORTANT : Mettre à jour les wallets lors de l'approbation d'une réservation
            // Vérifier si la réservation vient d'être approuvée (confirmed ou completed)
            $statusValue = $reservation->status instanceof \App\Enums\ReservationStatus 
                ? $reservation->status->value 
                : $reservation->status;
                
            if (in_array($statusValue, ['confirmed', 'completed'])) {
                Log::info('Mise à jour des wallets pour transaction existante lors de l\'approbation de réservation', [
                    'reservation_id' => $reservation->id,
                    'transaction_id' => $transaction->id
                ]);

                // Mettre à jour les wallets avec les crédits/débits appropriés
                $walletUpdateResult = $reservationTransactionService->updateWalletsForExistingTransaction($transaction);

                if ($walletUpdateResult['success']) {
                    Log::info('Wallets mis à jour avec succès pour transaction existante', [
                        'reservation_id' => $reservation->id,
                        'transaction_id' => $transaction->id,
                        'wallet_ids' => $walletUpdateResult['wallet_ids']
                    ]);
                } else {
                    Log::warning('Échec de la mise à jour des wallets pour transaction existante', [
                        'reservation_id' => $reservation->id,
                        'transaction_id' => $transaction->id,
                        'errors' => $walletUpdateResult['errors'] ?? []
                    ]);
                }
            }

            // Synchroniser les balances
            $this->synchronizeBalances([
                'main_transaction' => $transaction,
                'transaction_detail' => $detail,
                'hierarchy' => $this->getHierarchy($reservation)
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la vérification de cohérence', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Synchroniser les balances après création/mise à jour des transactions
     * 
     * @param array $result
     * @return void
     */
    protected function synchronizeBalances(array $result): void
    {
        try {
            $transaction = $result['main_transaction'] ?? null;
            $hierarchy = $result['hierarchy'] ?? null;

            if (!$transaction || !$hierarchy) {
                // Essayer de récupérer la hiérarchie depuis la réservation
                $reservation = $transaction->reservation ?? null;
                if ($reservation) {
                    $hierarchy = $this->getHierarchy($reservation);
                }
            }

            if (!$hierarchy) {
                return;
            }

            $balanceSyncService = app(BalanceSynchronizationService::class);

            // Synchroniser l'admin
            if (isset($hierarchy['admin']) && $hierarchy['admin']) {
                try {
                    $balanceSyncService->synchronizeUserBalance($hierarchy['admin']);
                } catch (\Exception $e) {
                    Log::error('Erreur synchronisation balance admin', [
                        'user_id' => $hierarchy['admin']->id ?? null,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Synchroniser l'intégrateur
            if (isset($hierarchy['integrator'])) {
                $integratorUser = $hierarchy['integrator']->user ?? $hierarchy['integrator'];
                if ($integratorUser) {
                    try {
                        $balanceSyncService->synchronizeUserBalance($integratorUser);
                    } catch (\Exception $e) {
                        Log::error('Erreur synchronisation balance intégrateur', [
                            'user_id' => $integratorUser->id ?? null,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            // Synchroniser l'opérateur
            if (isset($hierarchy['operator']) && $hierarchy['operator']) {
                try {
                    $balanceSyncService->synchronizeUserBalance($hierarchy['operator']);
                } catch (\Exception $e) {
                    Log::error('Erreur synchronisation balance opérateur', [
                        'user_id' => $hierarchy['operator']->id ?? null,
                        'error' => $e->getMessage()
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('Erreur lors de la synchronisation des balances', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Synchroniser les soldes admin/intégrateur/opérateur quand payment_status devient PAID.
     * Garantit que les réservations payées par solde sont reflétées partout dans l'app.
     *
     * @param Reservation $reservation
     * @return void
     */
    protected function syncBalancesWhenPaymentConfirmed(Reservation $reservation): void
    {
        if (!$reservation->charging_point_id) {
            return;
        }

        try {
            $reservationTransactionService = app(ReservationTransactionService::class);
            $hierarchy = $reservationTransactionService->getCompleteHierarchy($reservation->charging_point_id);

            if (!$hierarchy) {
                Log::debug('syncBalancesWhenPaymentConfirmed: hiérarchie non trouvée', [
                    'reservation_id' => $reservation->id,
                    'charging_point_id' => $reservation->charging_point_id,
                ]);
                return;
            }

            $balanceSyncService = app(BalanceSynchronizationService::class);

            if (isset($hierarchy['admin']) && $hierarchy['admin'] instanceof \App\Models\User) {
                try {
                    $balanceSyncService->synchronizeUserBalance($hierarchy['admin']);
                    Log::info('Balance admin synchronisée (payment_status PAID)', [
                        'reservation_id' => $reservation->id,
                        'admin_id' => $hierarchy['admin']->id,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Erreur sync balance admin (payment_status PAID)', [
                        'reservation_id' => $reservation->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if (isset($hierarchy['integrator'])) {
                $integratorUser = $hierarchy['integrator']->user ?? $hierarchy['integrator'];
                if ($integratorUser instanceof \App\Models\User) {
                    try {
                        $balanceSyncService->synchronizeUserBalance($integratorUser);
                        Log::info('Balance intégrateur synchronisée (payment_status PAID)', [
                            'reservation_id' => $reservation->id,
                            'integrator_id' => $integratorUser->id,
                        ]);
                    } catch (\Exception $e) {
                        Log::warning('Erreur sync balance intégrateur (payment_status PAID)', [
                            'reservation_id' => $reservation->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            if (isset($hierarchy['operator']) && $hierarchy['operator'] instanceof \App\Models\User) {
                try {
                    $balanceSyncService->synchronizeUserBalance($hierarchy['operator']);
                    Log::info('Balance opérateur synchronisée (payment_status PAID)', [
                        'reservation_id' => $reservation->id,
                        'operator_id' => $hierarchy['operator']->id,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Erreur sync balance opérateur (payment_status PAID)', [
                        'reservation_id' => $reservation->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Erreur syncBalancesWhenPaymentConfirmed', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Garantir que la balance admin est synchronisée pour chaque réservation approuvée
     * 
     * @param array $result
     * @return void
     */
    protected function ensureAdminBalanceSynchronized(array $result): void
    {
        try {
            $transaction = $result['main_transaction'] ?? null;
            $transactionDetail = $result['transaction_detail'] ?? null;
            $hierarchy = $result['hierarchy'] ?? null;
            
            if (!$transaction || !$transactionDetail) {
                return;
            }
            
            $balanceSyncService = app(BalanceSynchronizationService::class);
            
            // MÉTHODE 1 : Utiliser la hiérarchie si disponible
            if ($hierarchy && isset($hierarchy['admin']) && $hierarchy['admin']) {
                $adminUser = $hierarchy['admin'];
                
                // Vérifier que c'est bien un User avec le rôle admin
                if ($adminUser instanceof \App\Models\User && $adminUser->hasRole(['admin', 'super_admin'])) {
                    try {
                        $adminSyncResult = $balanceSyncService->synchronizeUserBalance($adminUser);
                        Log::info('Balance admin synchronisée via hiérarchie (ReservationObserver)', [
                            'reservation_id' => $transaction->reservation_id,
                            'transaction_id' => $transaction->id,
                            'admin_user_id' => $adminUser->id,
                            'admin_share_amount' => $transactionDetail->admin_share_amount ?? 0,
                            'sync_result' => $adminSyncResult
                        ]);
                        return;
                    } catch (\Exception $e) {
                        Log::warning('Erreur synchronisation admin via hiérarchie, essai méthode alternative', [
                            'admin_user_id' => $adminUser->id ?? null,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
            
            // MÉTHODE 2 : Trouver l'admin via TransactionDetail si admin_creator_id est défini
            if ($transactionDetail->admin_creator_id) {
                $adminUser = \App\Models\User::find($transactionDetail->admin_creator_id);
                if ($adminUser && $adminUser->hasRole(['admin', 'super_admin'])) {
                    try {
                        $adminSyncResult = $balanceSyncService->synchronizeUserBalance($adminUser);
                        Log::info('Balance admin synchronisée via admin_creator_id (ReservationObserver)', [
                            'reservation_id' => $transaction->reservation_id,
                            'transaction_id' => $transaction->id,
                            'admin_user_id' => $adminUser->id,
                            'admin_share_amount' => $transactionDetail->admin_share_amount ?? 0,
                            'sync_result' => $adminSyncResult
                        ]);
                        return;
                    } catch (\Exception $e) {
                        Log::warning('Erreur synchronisation admin via admin_creator_id', [
                            'admin_user_id' => $adminUser->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
            
            // MÉTHODE 3 : Trouver tous les admins et synchroniser celui qui a des parts admin dans cette transaction
            if ($transactionDetail->admin_share_amount > 0) {
                $adminUsers = \App\Models\User::whereHas('roles', function($q) {
                    $q->whereIn('name', ['admin', 'super_admin']);
                })->get();
                
                foreach ($adminUsers as $adminUser) {
                    try {
                        $adminSyncResult = $balanceSyncService->synchronizeUserBalance($adminUser);
                        Log::info('Balance admin synchronisée via recherche globale (ReservationObserver)', [
                            'reservation_id' => $transaction->reservation_id,
                            'transaction_id' => $transaction->id,
                            'admin_user_id' => $adminUser->id,
                            'admin_share_amount' => $transactionDetail->admin_share_amount ?? 0,
                            'sync_result' => $adminSyncResult
                        ]);
                        // Synchroniser seulement le premier admin trouvé (généralement il n'y en a qu'un)
                        break;
                    } catch (\Exception $e) {
                        Log::warning('Erreur synchronisation admin via recherche globale', [
                            'admin_user_id' => $adminUser->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la garantie de synchronisation balance admin', [
                'transaction_id' => $result['main_transaction']->id ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Récupérer la hiérarchie depuis la réservation
     * 
     * @param Reservation $reservation
     * @return array|null
     */
    protected function getHierarchy(Reservation $reservation): ?array
    {
        try {
            $reservationTransactionService = app(ReservationTransactionService::class);
            $chargingPoint = $reservation->chargingPoint;
            
            if (!$chargingPoint) {
                return null;
            }

            // Utiliser la réflexion pour accéder à la méthode protected
            $reflection = new \ReflectionClass($reservationTransactionService);
            $method = $reflection->getMethod('getCompleteHierarchy');
            $method->setAccessible(true);
            
            return $method->invoke($reservationTransactionService, $chargingPoint->id);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération de la hiérarchie', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Démarrer automatiquement une session de recharge si les conditions sont réunies
     * 
     * @param Reservation $reservation
     * @return void
     */
    protected function startChargingSessionIfNeeded(Reservation $reservation): void
    {
        try {
            if (config('auto-remote-start.enabled')) {
                Log::info('ReservationObserver: AutoRemoteStart activé, démarrage géré par le scheduler', [
                    'reservation_id' => $reservation->id,
                    'start_mode' => config('auto-remote-start.mode.start_mode', 'auto')
                ]);
                return;
            }

            // Ne démarrer automatiquement que si :
            // 1. La réservation est confirmée ou approuvée
            // 2. Il y a une borne assignée
            // 3. La borne est configurée avec Steve
            // 4. Le paiement est validé (prépayé) ou le crédit est suffisant (postpayé)
            // 5. Il n'y a pas déjà de session active

            // Vérifier si une session existe déjà
            if ($reservation->charging_session_id) {
                Log::info('ReservationObserver: Session déjà existante, pas de démarrage automatique', [
                    'reservation_id' => $reservation->id,
                    'session_id' => $reservation->charging_session_id
                ]);
                return;
            }

            if ($reservation->start_time && !$reservation->canStartNow()) {
                Log::info('ReservationObserver: Heure de démarrage non atteinte', [
                    'reservation_id' => $reservation->id,
                    'start_time' => $reservation->start_time?->toISOString(),
                ]);
                return;
            }

            // Vérifier si la borne est disponible et configurée
            $chargingPoint = $reservation->chargingPoint;
            if (!$chargingPoint || empty($chargingPoint->steve_charging_point_id)) {
                Log::info('ReservationObserver: Borne non configurée avec Steve, pas de démarrage automatique', [
                    'reservation_id' => $reservation->id,
                    'charging_point_id' => $reservation->charging_point_id
                ]);
                return;
            }

            // Vérifier le statut de paiement
            if ($reservation->isPrepaid() && !$reservation->isWalletValidated()) {
                Log::info('ReservationObserver: Paiement prépayé non validé, pas de démarrage automatique', [
                    'reservation_id' => $reservation->id
                ]);
                return;
            }

            // Pour le postpayé, vérifier le solde
            if ($reservation->isPostpaid()) {
                $user = $reservation->user;
                $wallet = $user->getOrCreateWallet();
                $minThreshold = $reservation->min_threshold ?? config('charging.postpaid_min_threshold', 10.00);

                if (!$wallet->hasSufficientBalance($minThreshold)) {
                    Log::warning('ReservationObserver: Solde insuffisant pour démarrage automatique postpayé', [
                        'reservation_id' => $reservation->id,
                        'user_id' => $user->id,
                        'required' => $minThreshold,
                        'available' => $wallet->balance
                    ]);
                    return;
                }
            }

            // Démarrer la session de recharge
            $chargingSessionManager = app(\App\Services\ChargingSessionManager::class);
            $result = $chargingSessionManager->startChargingSession($reservation);

            if ($result['success']) {
                Log::info('ReservationObserver: Session de recharge démarrée automatiquement', [
                    'reservation_id' => $reservation->id,
                    'session_id' => $result['session']->id ?? null
                ]);
            } else {
                Log::warning('ReservationObserver: Échec du démarrage automatique de la session', [
                    'reservation_id' => $reservation->id,
                    'error' => $result['error'] ?? 'Unknown',
                    'message' => $result['message'] ?? 'No message'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('ReservationObserver: Exception lors du démarrage automatique de la session', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
