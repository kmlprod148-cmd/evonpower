<?php

namespace App\Services;

use App\Models\ChargingSession;
use App\Services\HierarchicalTransactionService;
use App\Services\GuestPostpaidService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class ChargingSessionCompletionService
{
    protected $hierarchicalTransactionService;
    protected $guestPostpaidService;

    public function __construct(
        HierarchicalTransactionService $hierarchicalTransactionService,
        ?GuestPostpaidService $guestPostpaidService = null
    ) {
        $this->hierarchicalTransactionService = $hierarchicalTransactionService;
        $this->guestPostpaidService = $guestPostpaidService;
    }

    /**
     * Traiter la fin d'une session de charge avec transaction hiérarchique
     */
    public function processSessionCompletion(ChargingSession $session): array
    {
        try {
            // Vérifier que la session est terminée
            if ($session->status !== 'completed' && $session->status !== 'stopped') {
                throw new \Exception('La session doit être terminée pour traiter la transaction hiérarchique');
            }

            // Vérifier que la transaction hiérarchique n'a pas déjà été traitée
            if ($session->hierarchical_transaction_processed) {
                return [
                    'success' => true,
                    'message' => 'Transaction hiérarchique déjà traitée',
                    'already_processed' => true
                ];
            }

            // Traiter la transaction hiérarchique
            $result = $this->hierarchicalTransactionService->processHierarchicalTransaction($session);

            if ($result['success']) {
                // Marquer la session comme traitée
                $session->update([
                    'hierarchical_transaction_processed' => true,
                    'hierarchical_transaction_processed_at' => now()
                ]);

                Log::info('Session de charge terminée avec transaction hiérarchique', [
                    'session_id' => $session->id,
                    'charging_point_id' => $session->charging_point_id,
                    'energy_delivered' => $session->energy_delivered,
                    'duration' => $session->duration,
                    'operator_amount' => $result['amounts']['operator'] ?? 0,
                    'integrator_amount' => $result['amounts']['integrator'] ?? 0
                ]);

                // Traiter le paiement postpaid pour les guests si nécessaire
                $postpaidResult = $this->processGuestPostpaidCapture($session);

                return [
                    'success' => true,
                    'message' => 'Transaction hiérarchique traitée avec succès',
                    'data' => $result,
                    'postpaid_capture' => $postpaidResult
                ];
            } else {
                Log::error('Échec de la transaction hiérarchique lors de la fin de session', [
                    'session_id' => $session->id,
                    'error' => $result['error']
                ]);

                return [
                    'success' => false,
                    'message' => 'Échec de la transaction hiérarchique',
                    'error' => $result['error']
                ];
            }

        } catch (\Exception $e) {
            Log::error('Erreur lors du traitement de la fin de session', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors du traitement de la fin de session',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Traiter automatiquement toutes les sessions terminées non traitées
     */
    public function processAllPendingSessions(): array
    {
        $pendingSessions = ChargingSession::whereIn('status', ['completed', 'stopped'])
            ->where('hierarchical_transaction_processed', false)
            ->where('created_at', '>=', now()->subDays(7)) // Seulement les 7 derniers jours
            ->get();

        $results = [
            'processed' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($pendingSessions as $session) {
            $result = $this->processSessionCompletion($session);
            
            if ($result['success']) {
                $results['processed']++;
            } else {
                $results['failed']++;
                $results['errors'][] = [
                    'session_id' => $session->id,
                    'error' => $result['error']
                ];
            }
        }

        Log::info('Traitement automatique des sessions terminées', $results);

        return $results;
    }

    /**
     * Vérifier les prérequis avant traitement
     */
    public function checkPrerequisites(ChargingSession $session): array
    {
        $issues = [];

        // Vérifier que la session a des données de consommation
        if (!$session->energy_delivered || $session->energy_delivered <= 0) {
            $issues[] = 'Aucune énergie délivrée enregistrée';
        }

        if (!$session->duration || $session->duration <= 0) {
            $issues[] = 'Aucune durée enregistrée';
        }

        // Vérifier que la borne existe et a une hiérarchie valide
        $hierarchy = $this->hierarchicalTransactionService->identifyHierarchy($session->charging_point_id);
        if (!$hierarchy) {
            $issues[] = 'Impossible d\'identifier la hiérarchie de la borne';
        }

        if ($hierarchy) {
            if (!$hierarchy['operator']) {
                $issues[] = 'Aucun opérateur associé à la borne';
            }

            if (!$hierarchy['integrator']) {
                $issues[] = 'Aucun intégrateur associé à la borne';
            }
        }

        return [
            'valid' => empty($issues),
            'issues' => $issues
        ];
    }

    /**
     * Obtenir les statistiques des transactions hiérarchiques
     */
    public function getTransactionStatistics(int $chargingPointId = null): array
    {
        $query = \App\Models\Transaction::where('type', 'hierarchical_debit');
        
        if ($chargingPointId) {
            $query->where('charging_point_id', $chargingPointId);
        }

        $transactions = $query->get();

        $statistics = [
            'total_transactions' => $transactions->count(),
            'total_amount' => $transactions->sum('amount'),
            'operator_transactions' => $transactions->where('metadata->role', 'operator')->count(),
            'integrator_transactions' => $transactions->where('metadata->role', 'integrator')->count(),
            'average_amount' => $transactions->avg('amount'),
            'last_transaction' => $transactions->sortByDesc('created_at')->first(),
            'by_status' => $transactions->groupBy('status')->map->count(),
            'by_date' => $transactions->groupBy(function($transaction) {
                return $transaction->created_at->format('Y-m-d');
            })->map->count()
        ];

        return $statistics;
    }

    /**
     * Traiter la capture du paiement postpaid pour les clients guests
     */
    protected function processGuestPostpaidCapture(ChargingSession $session): array
    {
        // Vérifier si la session nécessite une capture de paiement
        if (!$session->needsPaymentCapture()) {
            return [
                'processed' => false,
                'reason' => 'Session does not require postpaid capture',
                'is_postpaid' => $session->isPostpaid(),
                'has_payment_method' => !empty($session->guest_payment_method_id),
                'has_authorization' => !empty($session->authorization_hold_id),
                'capture_status' => $session->capture_status
            ];
        }

        // Si le service n'est pas disponible, journaliser l'erreur
        if (!$this->guestPostpaidService) {
            Log::warning('GuestPostpaidService non disponible pour la capture', [
                'session_id' => $session->id
            ]);

            return [
                'processed' => false,
                'error' => 'GuestPostpaidService not available'
            ];
        }

        try {
            // Mettre à jour le statut de capture
            $session->update([
                'capture_status' => ChargingSession::CAPTURE_STATUS_PROCESSING,
                'capture_attempted_at' => now()
            ]);

            // Calculer le montant à capturer (coût réel ou montant pré-autorisé)
            $captureAmount = $session->actual_cost ?? $session->authorization_hold_amount;

            if (!$captureAmount || $captureAmount <= 0) {
                Log::warning('Montant de capture invalide pour la session postpaid', [
                    'session_id' => $session->id,
                    'actual_cost' => $session->actual_cost,
                    'authorization_hold_amount' => $session->authorization_hold_amount
                ]);

                $session->update([
                    'capture_status' => ChargingSession::CAPTURE_STATUS_FAILED
                ]);

                return [
                    'processed' => false,
                    'error' => 'Invalid capture amount'
                ];
            }

            // Effectuer la capture
            $result = $this->guestPostpaidService->capturePayment(
                $session,
                $captureAmount
            );

            if ($result['success']) {
                // Marquer la session comme capturée
                $session->update([
                    'capture_status' => ChargingSession::CAPTURE_STATUS_CAPTURED,
                    'capture_transaction_id' => $result['transaction_id'] ?? null
                ]);

                Log::info('Capture payment postpaid réussie pour session guest', [
                    'session_id' => $session->id,
                    'amount' => $captureAmount,
                    'transaction_id' => $result['transaction_id'] ?? null
                ]);

                return [
                    'processed' => true,
                    'success' => true,
                    'amount' => $captureAmount,
                    'transaction_id' => $result['transaction_id'] ?? null
                ];
            } else {
                // Marquer la capture comme échouée
                $session->update([
                    'capture_status' => ChargingSession::CAPTURE_STATUS_FAILED
                ]);

                Log::error('Échec de la capture payment postpaid pour session guest', [
                    'session_id' => $session->id,
                    'amount' => $captureAmount,
                    'error' => $result['error'] ?? 'Unknown error'
                ]);

                return [
                    'processed' => false,
                    'success' => false,
                    'error' => $result['error'] ?? 'Capture failed'
                ];
            }

        } catch (\Exception $e) {
            Log::error('Exception lors de la capture payment postpaid', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $session->update([
                'capture_status' => ChargingSession::CAPTURE_STATUS_FAILED
            ]);

            return [
                'processed' => false,
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
