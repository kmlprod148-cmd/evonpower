<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\TransactionRepartition;
use App\Models\BusinessProfile;

class TransactionCalculator
{
    public function calculate(Transaction $tx): array
    {
        $totalPaid = $tx->amount ?? 0; // montant payé par le client
        
        // Ensure chargingPoint is loaded
        if (!$tx->relationLoaded('chargingPoint')) {
            $tx->load('chargingPoint');
        }
        
        // Ensure chargingPoint and businessProfile exist
        $chargingPoint = $tx->chargingPoint;
        $bp = null;
        
        if ($chargingPoint) {
            // Try to get businessProfile from chargingPoint
            if ($chargingPoint->relationLoaded('businessProfile')) {
                $bp = $chargingPoint->businessProfile;
            } elseif ($chargingPoint->business_profile_id) {
                $bp = $chargingPoint->businessProfile ?? BusinessProfile::find($chargingPoint->business_profile_id);
            }
        }
        
        // Fallback to new BusinessProfile if none found
        if (!$bp) {
            $bp = new BusinessProfile();
        }

        // Exemple: fees stored in BP
        $adminFees = ($bp->transaction_fee ?? 0) + ($bp->recharge_fee ?? 0) + ($bp->other_fees ?? 0);

        // admin share is percentage or fixed? (assume percentage stored e.g. admin_percentage)
        if (isset($bp->admin_percentage) && $bp->admin_percentage > 0) {
            $adminShare = $totalPaid * ($bp->admin_percentage / 100);
        } else {
            $adminShare = $adminFees > 0 ? $adminFees : ($totalPaid * 0.1); // Default 10%
        }

        // integrator share logic
        $integratorShare = 0;
        if ($tx->integrator) {
            // Ensure integrator's businessProfile exists
            $integratorBusinessProfile = $tx->integrator->businessProfile ?? new BusinessProfile();
            $integratorPercentage = $integratorBusinessProfile->integrator_percentage ?? 0;
            $integratorShare = $totalPaid * ($integratorPercentage / 100);
        }

        $operatorShare = $totalPaid - $adminShare - $integratorShare;
        
        // Ensure operator share is not negative
        if ($operatorShare < 0) {
            $operatorShare = 0;
            // Adjust admin share if needed
            $adminShare = max(0, $totalPaid - $integratorShare);
        }

        return [
            'total' => round($totalPaid, 2),
            'admin_amount' => round($adminShare, 2),
            'integrator_amount' => round($integratorShare, 2),
            'operator_amount' => round($operatorShare, 2),
        ];
    }

    /**
     * Calcule la répartition pour une transaction publique
     * 
     * @param Transaction $transaction
     * @return array
     */
    public function calculateForPublicTransaction(Transaction $transaction): array
    {
        // Utiliser la méthode calculate standard
        $result = $this->calculate($transaction);
        
        // Retourner dans le format attendu par createFromCalculation
        return [
            'admin_amount' => $result['admin_amount'] ?? 0,
            'integrator_amount' => $result['integrator_amount'] ?? 0,
            'operator_amount' => $result['operator_amount'] ?? 0,
        ];
    }

    /**
     * Crée ou met à jour la répartition pour une transaction
     * 
     * @param Transaction $transaction
     * @return TransactionRepartition|null
     */
    public function createRepartition(Transaction $transaction): ?TransactionRepartition
    {
        // Vérifier si une répartition existe déjà
        $existingRepartition = TransactionRepartition::where('transaction_id', $transaction->id)->first();
        
        if ($existingRepartition) {
            return $existingRepartition;
        }
        
        // Calculer la répartition
        $calculation = $this->calculate($transaction);
        
        // Créer la répartition
        try {
            return TransactionRepartition::createFromCalculation($transaction, [
                'admin_amount' => $calculation['admin_amount'] ?? 0,
                'integrator_amount' => $calculation['integrator_amount'] ?? 0,
                'operator_amount' => $calculation['operator_amount'] ?? 0,
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la création de la répartition', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Calcule les détails de la répartition avec informations supplémentaires
     * 
     * @param Transaction $transaction
     * @return array
     */
    public function calculateDetailed(Transaction $transaction): array
    {
        $calculation = $this->calculate($transaction);
        
        // Charger les relations nécessaires
        if (!$transaction->relationLoaded('chargingPoint')) {
            $transaction->load('chargingPoint.group.partner.integrator');
        }
        
        $chargingPoint = $transaction->chargingPoint;
        $group = $chargingPoint?->group;
        $partner = $group?->partner;
        $integrator = $partner?->integrator;
        
        // Calculer les pourcentages
        $total = $calculation['total'] ?? 0;
        $adminPercentage = $total > 0 ? round(($calculation['admin_amount'] / $total) * 100, 2) : 0;
        $integratorPercentage = $total > 0 ? round(($calculation['integrator_amount'] / $total) * 100, 2) : 0;
        $operatorPercentage = $total > 0 ? round(($calculation['operator_amount'] / $total) * 100, 2) : 0;
        
        return [
            'amounts' => [
                'total' => round($total, 2),
                'admin' => round($calculation['admin_amount'] ?? 0, 2),
                'integrator' => round($calculation['integrator_amount'] ?? 0, 2),
                'operator' => round($calculation['operator_amount'] ?? 0, 2),
            ],
            'percentages' => [
                'admin' => $adminPercentage,
                'integrator' => $integratorPercentage,
                'operator' => $operatorPercentage,
            ],
            'entities' => [
                'admin' => [
                    'name' => 'EVON Platform',
                    'type' => 'admin',
                ],
                'integrator' => [
                    'name' => $integrator?->name ?? 'N/A',
                    'id' => $integrator?->id,
                    'type' => 'integrator',
                ],
                'operator' => [
                    'name' => $partner?->name ?? $group?->user?->name ?? 'Opérateur',
                    'id' => $partner?->id ?? $group?->user_id,
                    'type' => 'operator',
                ],
            ],
            'charging_point' => [
                'id' => $chargingPoint?->id,
                'name' => $chargingPoint?->name ?? 'N/A',
            ],
            'transaction' => [
                'id' => $transaction->id,
                'status' => $transaction->status,
                'currency' => $transaction->currency ?? 'EUR',
                'created_at' => $transaction->created_at?->toIso8601String(),
            ],
        ];
    }
}