<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\TransactionDetail;
use App\Services\ReservationTransactionService;

class ReservationDisplayService
{
    protected ReservationTransactionService $transactionService;

    public function __construct(ReservationTransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Obtenir les montants corrects pour l'affichage d'une réservation
     * 
     * @param Reservation $reservation
     * @return array
     */
    public function getDisplayAmounts(Reservation $reservation): array
    {
        $amounts = [
            'total_amount' => $reservation->estimated_cost ?? 0,
            'admin_fees' => 0,
            'integrator_fees' => 0,
            'operator_net' => 0,
            'fees_breakdown' => [],
            'calculation_method' => 'estimated'
        ];

        // Vérifier si la réservation a des détails de transaction
        $transactionDetail = TransactionDetail::whereHas('transaction', function($query) use ($reservation) {
            $query->where('reservation_id', $reservation->id);
        })->with('transaction')->first();

        if ($transactionDetail) {
            // Utiliser les montants calculés réels
            $amounts['admin_fees'] = (float) ($transactionDetail->admin_share_amount ?? 0);
            $amounts['integrator_fees'] = (float) ($transactionDetail->integrator_share_amount ?? 0);
            $amounts['operator_net'] = (float) ($transactionDetail->operator_share_amount ?? 0);
            $amounts['calculation_method'] = 'transaction_calculated';
            
            // NOUVELLE MÉTHODE : Utiliser la validation de cohérence du modèle
            $consistencyValidation = $transactionDetail->validateAmountConsistency();
            
            // Calculer la somme des parts pour validation
            $calculatedTotal = round($amounts['admin_fees'] + $amounts['integrator_fees'] + $amounts['operator_net'], 2);
            
            // PRIORITÉ 1 : Si les parts sont cohérentes et valides, les utiliser comme source de vérité
            if ($calculatedTotal > 0 && $consistencyValidation['is_consistent']) {
                // Tout est cohérent, utiliser le montant de la transaction ou la somme (identique)
                $amounts['total_amount'] = $consistencyValidation['transaction_amount'];
            }
            // PRIORITÉ 2 : Si incohérence détectée, utiliser la somme des parts (plus fiable)
            elseif ($calculatedTotal > 0 && !$consistencyValidation['is_consistent']) {
                // Les parts sont valides mais ne correspondent pas au montant de transaction
                // Corriger automatiquement si possible
                if ($consistencyValidation['difference'] < 0.50) {
                    // Petite différence : corriger automatiquement
                    $transactionDetail->fixAmountConsistency();
                    $amounts['total_amount'] = $calculatedTotal;
                    
                    \Log::info('Incohérence automatiquement corrigée dans ReservationDisplayService', [
                        'reservation_id' => $reservation->id,
                        'transaction_id' => $transactionDetail->transaction->id,
                        'ancien_montant' => $consistencyValidation['transaction_amount'],
                        'nouveau_montant' => $calculatedTotal,
                        'difference' => $consistencyValidation['difference']
                    ]);
                } else {
                    // Grande différence : utiliser les parts mais logger l'erreur
                    $amounts['total_amount'] = $calculatedTotal;
                    
                    \Log::warning('Incohérence importante détectée - Utilisation de la somme des parts', [
                        'reservation_id' => $reservation->id,
                        'transaction_id' => $transactionDetail->transaction->id,
                        'transaction_amount' => $consistencyValidation['transaction_amount'],
                        'calculated_total_from_parts' => $calculatedTotal,
                        'difference' => $consistencyValidation['difference'],
                        'action' => 'used_calculated_total_from_parts'
                    ]);
                }
            }
            // PRIORITÉ 3 : Si pas de parts mais montant transaction disponible
            elseif ($calculatedTotal <= 0 && $consistencyValidation['transaction_amount'] > 0) {
                $amounts['total_amount'] = $consistencyValidation['transaction_amount'];
                // Réinitialiser les parts à 0 car elles ne sont pas encore calculées
                $amounts['admin_fees'] = 0;
                $amounts['integrator_fees'] = 0;
                $amounts['operator_net'] = 0;
            }
            // PRIORITÉ 4 : Fallback sur estimated_cost
            elseif (($amounts['total_amount'] ?? 0) > 0) {
                // Garder l'estimated_cost initial (déjà défini ligne 27)
            } 
            // DERNIER RECOURS : Aucun montant disponible
            else {
                $amounts['total_amount'] = 0;
            }
            
            // Récupérer le breakdown détaillé si disponible
            if ($transactionDetail->calculation_details) {
                $details = $transactionDetail->calculation_details;
                $amounts['fees_breakdown'] = [
                    'admin_breakdown' => $details['admin_fees_breakdown'] ?? [],
                    'integrator_breakdown' => $details['integrator_fees_breakdown'] ?? [],
                    'business_profiles' => $details['business_profiles'] ?? []
                ];
            }
        } else {
                // Calculer les frais estimés selon la logique transactionnelle
                try {
                    $calculation = $this->calculateEstimatedFees($reservation);
                    $amounts['admin_fees'] = round((float) $calculation['admin_share'], 2);
                    $amounts['integrator_fees'] = round((float) $calculation['integrator_share'], 2);
                    $amounts['operator_net'] = round((float) $calculation['operator_share'], 2);
                    $amounts['fees_breakdown'] = $calculation['fees_breakdown'] ?? [];
                    $amounts['calculation_method'] = 'estimated_calculated';
                    
                    // CORRECTION : Utiliser la somme calculée comme source de vérité si les parts sont valides
                    $calculatedTotal = round($amounts['admin_fees'] + $amounts['integrator_fees'] + $amounts['operator_net'], 2);
                    
                    // Si les parts calculées donnent un total valide (> 0), l'utiliser
                    // Sinon, garder l'estimated_cost comme fallback pour éviter d'afficher 0
                    if ($calculatedTotal > 0) {
                        $amounts['total_amount'] = $calculatedTotal;
                    } else {
                        // Si le calcul donne 0 mais qu'on a un estimated_cost, l'utiliser comme fallback
                        // Cela peut arriver si les Business Profiles ne sont pas configurés correctement
                        if (($amounts['total_amount'] ?? 0) > 0) {
                            // Garder l'estimated_cost initial (déjà défini ligne 27)
                            // Les parts restent à 0 mais le total affiche l'estimation
                        } else {
                            // Vraiment aucun montant disponible
                            $amounts['total_amount'] = 0;
                        }
                    }
                } catch (\Exception $e) {
                    // En cas d'erreur, utiliser des valeurs par défaut
                    $amounts['calculation_method'] = 'default_fallback';
                    // Utiliser l'estimated_cost comme fallback au lieu de forcer à 0
                    // Le total_amount initial (estimated_cost) est déjà défini ligne 27
                    // Si estimated_cost est aussi 0, alors le total restera à 0 (cohérent)
                }
            }

        return $amounts;
    }

    /**
     * Calculer les frais estimés selon la logique transactionnelle
     * 
     * @param Reservation $reservation
     * @return array
     */
    protected function calculateEstimatedFees(Reservation $reservation): array
    {
        $totalAmount = $reservation->estimated_cost ?? 0;
        
        // Récupérer la hiérarchie pour calculer les frais
        $hierarchy = $this->transactionService->getCompleteHierarchy($reservation->charging_point_id);
        
        if (!$hierarchy) {
            // Valeurs par défaut si pas de hiérarchie
            return [
                'admin_share' => $totalAmount * 0.10, // 10% par défaut
                'integrator_share' => $totalAmount * 0.05, // 5% par défaut
                'operator_share' => $totalAmount * 0.85, // 85% par défaut
                'fees_breakdown' => []
            ];
        }

        // Récupérer les Business Profiles
        $businessProfiles = $this->transactionService->getBusinessProfilesForHierarchy($hierarchy);
        
        if (!$businessProfiles['admin_integrator'] || !$businessProfiles['integrator_operator']) {
            // Valeurs par défaut si pas de Business Profiles
            return [
                'admin_share' => $totalAmount * 0.10,
                'integrator_share' => $totalAmount * 0.05,
                'operator_share' => $totalAmount * 0.85,
                'fees_breakdown' => []
            ];
        }

        // Calculer les parts selon les Business Profiles
        $calculation = $this->transactionService->calculateSharesWithBusinessProfiles(
            $totalAmount,
            $businessProfiles,
            $hierarchy
        );

        return [
            'admin_share' => $calculation['admin_share'],
            'integrator_share' => $calculation['integrator_share'],
            'operator_share' => $calculation['operator_share'],
            'fees_breakdown' => [
                'admin_breakdown' => $calculation['admin_fees_breakdown'] ?? [],
                'integrator_breakdown' => $calculation['integrator_fees_breakdown'] ?? []
            ]
        ];
    }

    /**
     * Formater les montants pour l'affichage
     * 
     * @param array $amounts
     * @return array
     */
    public function formatAmountsForDisplay(array $amounts): array
    {
        return [
            'total_amount_formatted' => number_format($amounts['total_amount'], 2) . ' €',
            'admin_fees_formatted' => number_format($amounts['admin_fees'], 2) . ' €',
            'integrator_fees_formatted' => number_format($amounts['integrator_fees'], 2) . ' €',
            'operator_net_formatted' => number_format($amounts['operator_net'], 2) . ' €',
            'calculation_method' => $amounts['calculation_method'],
            'fees_breakdown' => $amounts['fees_breakdown']
        ];
    }

    /**
     * Vérifier si les montants sont cohérents
     * 
     * @param array $amounts
     * @param bool $autoCorrect Si true, corrige automatiquement les petites incohérences (< 0.10€)
     * @return array|bool Retourne true si cohérent, false si incohérent, ou array avec montants corrigés si autoCorrect=true
     */
    public function validateAmounts(array $amounts, bool $autoCorrect = false)
    {
        $total = (float) ($amounts['total_amount'] ?? 0);
        $adminFees = (float) ($amounts['admin_fees'] ?? 0);
        $integratorFees = (float) ($amounts['integrator_fees'] ?? 0);
        $operatorNet = (float) ($amounts['operator_net'] ?? 0);
        
        // Calculer la somme des parts avec arrondi à 2 décimales
        $calculatedTotal = round($adminFees + $integratorFees + $operatorNet, 2);
        $total = round($total, 2);
        
        // Si le total est 0, tous les montants doivent être 0
        if ($total == 0) {
            $allZero = ($adminFees == 0 && $integratorFees == 0 && $operatorNet == 0);
            if (!$allZero) {
                \Log::warning('Incohérence détectée: total à 0 mais parts non nulles', [
                    'total_amount' => $total,
                    'admin_fees' => $adminFees,
                    'integrator_fees' => $integratorFees,
                    'operator_net' => $operatorNet,
                    'calculated_total' => $calculatedTotal
                ]);
            }
            return $allZero;
        }
        
        // Vérifier la cohérence avec une tolérance de 0.02€ pour les arrondis de calcul en virgule flottante
        // (augmentée de 0.01€ à 0.02€ pour être plus tolérant avec les arrondis multiples)
        $difference = abs($total - $calculatedTotal);
        
        // Si différence <= tolérance d'arrondi (0.02€), les montants sont cohérents
        if ($difference <= 0.02) {
            return true;
        }
        
        // Si différence > 0.02€, il y a une incohérence
        // Si autoCorrect est activé et la différence est petite (< 0.50€), corriger automatiquement
        if ($autoCorrect && $difference < 0.50 && $calculatedTotal > 0) {
            // Corriger le total_amount pour correspondre à la somme des parts
            $amounts['total_amount'] = $calculatedTotal;
            
            \Log::info('Incohérence mineure corrigée automatiquement dans validateAmounts', [
                'total_amount_avant' => $total,
                'total_amount_apres' => $calculatedTotal,
                'difference' => $difference,
                'admin_fees' => $adminFees,
                'integrator_fees' => $integratorFees,
                'operator_net' => $operatorNet,
                'calculation_method' => $amounts['calculation_method'] ?? 'unknown'
            ]);
            
            // Retourner les montants corrigés si autoCorrect est activé
            return $amounts;
        }
        
        // Si la différence est plus importante mais que le total_amount et calculatedTotal sont tous deux > 0
        // et que la différence est raisonnable (< 1.00€), utiliser calculatedTotal comme source de vérité
        if ($autoCorrect && $difference < 1.00 && $calculatedTotal > 0 && $total > 0) {
            $amounts['total_amount'] = $calculatedTotal;
            
            \Log::warning('Incohérence modérée corrigée automatiquement dans validateAmounts', [
                'total_amount_avant' => $total,
                'total_amount_apres' => $calculatedTotal,
                'difference' => $difference,
                'admin_fees' => $adminFees,
                'integrator_fees' => $integratorFees,
                'operator_net' => $operatorNet,
                'calculation_method' => $amounts['calculation_method'] ?? 'unknown'
            ]);
            
            return $amounts;
        }
        
        // Logger l'incohérence pour le debugging (seulement si vraiment problématique)
        if ($difference >= 1.00) {
            \Log::warning('Incohérence importante détectée dans les montants de réservation', [
                'total_amount' => $total,
                'calculated_total' => $calculatedTotal,
                'difference' => $difference,
                'admin_fees' => $adminFees,
                'integrator_fees' => $integratorFees,
                'operator_net' => $operatorNet,
                'calculation_method' => $amounts['calculation_method'] ?? 'unknown',
                'auto_correct_enabled' => $autoCorrect,
                'can_auto_correct' => ($difference < 0.50 && $calculatedTotal > 0)
            ]);
        }
        
        return false;
    }
}
