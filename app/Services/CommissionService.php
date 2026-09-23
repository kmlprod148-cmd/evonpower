<?php

namespace App\Services;

use App\Models\CommissionPlan;
use App\Models\Transaction;
use App\Models\ChargingPoint;
use App\Models\Partner;
use App\Models\Integrator;
use App\Models\Group;
use Illuminate\Support\Collection;

class CommissionService
{
    /**
     * Détermine le plan de commission applicable à une transaction.
     *
     * @param Transaction $transaction
     * @return CommissionPlan|null
     */
    public function determineCommissionPlan(Transaction $transaction)
    {
        $chargingPoint = $transaction->chargingPoint;
        if (!$chargingPoint) {
            return CommissionPlan::getDefault();
        }

        $transactionValue = $transaction->price_total;
        $context = $this->getTransactionContext($transaction);

        // Récupérer tous les plans actifs
        $activePlans = CommissionPlan::active()->get();
        
        // Filtrer les plans applicables à cette transaction
        $applicablePlans = $activePlans->filter(function ($plan) use ($transactionValue, $context) {
            return $plan->isApplicable($transactionValue, $context);
        });

        // Si aucun plan applicable, retourner le plan par défaut
        if ($applicablePlans->isEmpty()) {
            return CommissionPlan::getDefault();
        }

        // Trouver le plan le plus spécifique selon la hiérarchie:
        // 1. Plans spécifiques à la borne
        // 2. Plans spécifiques au groupe
        // 3. Plans spécifiques au partenaire
        // 4. Plans spécifiques à l'intégrateur
        // 5. Plans globaux
        
        // Pour chaque niveau, prendre celui avec la priorité la plus élevée
        
        // 1. Plans spécifiques à la borne (via le groupe)
        if ($chargingPoint->group_id) {
            $groupPlan = $this->findHighestPriorityPlan($applicablePlans, 'group', $chargingPoint->group_id);
            if ($groupPlan) {
                return $groupPlan;
            }
        }
        
        // 2. Plans spécifiques au partenaire
        if ($chargingPoint->partner_id) {
            $partnerPlan = $this->findHighestPriorityPlan($applicablePlans, 'partner', $chargingPoint->partner_id);
            if ($partnerPlan) {
                return $partnerPlan;
            }
        }
        
        // 3. Plans spécifiques à l'intégrateur
        if ($chargingPoint->integrator_id) {
            $integratorPlan = $this->findHighestPriorityPlan($applicablePlans, 'integrator', $chargingPoint->integrator_id);
            if ($integratorPlan) {
                return $integratorPlan;
            }
        }
        
        // 4. Plans globaux (par priorité)
        $globalPlan = $applicablePlans
            ->where('applies_to_type', 'global')
            ->sortByDesc('priority')
            ->first();
            
        if ($globalPlan) {
            return $globalPlan;
        }
        
        // Si aucun plan trouvé malgré le filtrage, retourner le plan par défaut
        return CommissionPlan::getDefault();
    }
    
    /**
     * Retourne le plan de commission applicable à une transaction (alias pour compatibilité).
     *
     * @param Transaction $transaction
     * @return CommissionPlan|null
     */
    public function getApplicableCommissionPlan(Transaction $transaction)
    {
        return $this->determineCommissionPlan($transaction);
    }
    
    /**
     * Trouve le plan avec la priorité la plus élevée pour une entité spécifique.
     *
     * @param Collection $plans
     * @param string $type
     * @param int $id
     * @return CommissionPlan|null
     */
    protected function findHighestPriorityPlan(Collection $plans, string $type, int $id)
    {
        return $plans
            ->where('applies_to_type', $type)
            ->where('applies_to_id', $id)
            ->sortByDesc('priority')
            ->first();
    }
    
    /**
     * Obtient le contexte de la transaction pour l'évaluation des règles.
     *
     * @param Transaction $transaction
     * @return array
     */
    protected function getTransactionContext(Transaction $transaction)
    {
        $context = [
            'transaction_id' => $transaction->id,
            'transaction_value' => $transaction->price_total,
        ];
        
        // Ajouter des informations sur le plan tarifaire si disponible
        if (isset($transaction->price_details['pricing_plan_id'])) {
            $context['pricing_plan_id'] = $transaction->price_details['pricing_plan_id'];
            
            // Ajouter le type de plan tarifaire si disponible
            if (isset($transaction->price_details['pricing_plan_type'])) {
                $context['pricing_plan_type'] = $transaction->price_details['pricing_plan_type'];
            }
        }
        
        return $context;
    }
    
    /**
     * Calcule et enregistre les commissions pour une transaction.
     *
     * @param Transaction $transaction
     * @param CommissionPlan|null $plan
     * @return bool
     */
    public function calculateAndSaveCommissions(Transaction $transaction, CommissionPlan $plan = null)
    {
        // Si aucun plan n'est fourni, déterminer le plan applicable
        if (!$plan) {
            $plan = $this->determineCommissionPlan($transaction);
        }
        
        // Si aucun plan n'est trouvé, ne pas calculer de commissions
        if (!$plan) {
            return false;
        }
        
        // Calculer les commissions
        $commissions = $plan->calculateCommissions($transaction->price_total);
        
        // Mettre à jour la transaction avec les commissions calculées
        $transaction->commission_plan_id = $plan->id;
        $transaction->admin_commission = $commissions['admin'];
        $transaction->integrator_commission = $commissions['integrator'];
        $transaction->partner_commission = $commissions['partner'];
        
        // Enregistrer les modifications
        return $transaction->save();
    }
    
    /**
     * Recalcule les commissions pour un ensemble de transactions.
     *
     * @param Collection|array $transactions
     * @param CommissionPlan|null $plan Si fourni, ce plan sera utilisé pour toutes les transactions
     * @return int Nombre de transactions mises à jour
     */
    public function recalculateCommissions($transactions, CommissionPlan $plan = null)
    {
        $count = 0;
        
        foreach ($transactions as $transaction) {
            if ($this->calculateAndSaveCommissions($transaction, $plan)) {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Marque une commission comme payée.
     *
     * @param Transaction $transaction
     * @param string $type 'admin', 'integrator', ou 'partner'
     * @param string $requestedBy Type d'entité qui fait la demande ('admin', 'integrator', 'partner')
     * @param int|null $requestedById ID de l'entité qui fait la demande
     * @return array Résultat avec statut et message
     */
    public function markCommissionAsPaid(Transaction $transaction, string $type, string $requestedBy = 'admin', ?int $requestedById = null)
    {
        // Vérifier si un plan de commission est associé
        $plan = $transaction->commissionPlan;
        if (!$plan) {
            return [
                'success' => false,
                'message' => 'Aucun plan de commission associé à cette transaction.'
            ];
        }
        
        // Vérifier les permissions
        if (!$plan->hasTransactionPermission($requestedBy, $requestedById, 'mark_paid')) {
            return [
                'success' => false,
                'message' => 'Vous n\'avez pas la permission de marquer cette commission comme payée.'
            ];
        }
        
        // Vérifier si une approbation est nécessaire
        if ($plan->requiresApprovalFor('mark_paid')) {
            // Logique d'approbation à implémenter
            // Pour l'instant, on autorise si c'est l'admin qui fait la demande
            if ($requestedBy !== 'admin') {
                return [
                    'success' => false,
                    'message' => 'Cette action nécessite une approbation.'
                ];
            }
        }
        
        // Marquer comme payée
        $result = $transaction->markCommissionAsPaid($type);
        
        return [
            'success' => $result,
            'message' => $result ? 'Commission marquée comme payée avec succès.' : 'Erreur lors du marquage de la commission.'
        ];
    }
    
    /**
     * Marque plusieurs commissions comme payées.
     *
     * @param Collection|array $transactions
     * @param string $type 'admin', 'integrator', ou 'partner'
     * @param string $requestedBy Type d'entité qui fait la demande ('admin', 'integrator', 'partner')
     * @param int|null $requestedById ID de l'entité qui fait la demande
     * @return array Résultat avec nombre de succès, échecs et messages
     */
    public function markMultipleCommissionsAsPaid($transactions, string $type, string $requestedBy = 'admin', ?int $requestedById = null)
    {
        $successCount = 0;
        $failureCount = 0;
        $messages = [];
        
        foreach ($transactions as $transaction) {
            $result = $this->markCommissionAsPaid($transaction, $type, $requestedBy, $requestedById);
            
            if ($result['success']) {
                $successCount++;
            } else {
                $failureCount++;
                $messages[] = "Transaction #{$transaction->id}: {$result['message']}";
            }
        }
        
        return [
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'messages' => $messages
        ];
    }
}