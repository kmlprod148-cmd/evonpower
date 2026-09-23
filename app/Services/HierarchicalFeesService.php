<?php

namespace App\Services;

use App\Models\BusinessProfile;
use App\Models\User;
use App\Models\ChargingPoint;
use Illuminate\Support\Facades\Log;

/**
 * Service de gestion des frais hiérarchiques
 * 
 * Ce service implémente la logique hiérarchique de répartition des frais
 * basée sur les business profiles appliqués à chaque niveau de la hiérarchie.
 */
class HierarchicalFeesService
{
    /**
     * Calculer la répartition des frais pour un charging point
     *
     * @param ChargingPoint $chargingPoint
     * @param float $transactionAmount
     * @return array
     */
    public function calculateFeesDistribution(ChargingPoint $chargingPoint, float $transactionAmount): array
    {
        try {
            // Récupérer le business profile appliqué au charging point
            $businessProfile = $chargingPoint->businessProfile;
            
            if (!$businessProfile) {
                throw new \Exception("Aucun business profile appliqué au charging point");
            }

            // Récupérer l'opérateur du charging point
            $operator = $chargingPoint->user;
            
            if (!$operator) {
                throw new \Exception("Aucun opérateur associé au charging point");
            }

            // Calculer la répartition basée sur le business profile appliqué
            $distribution = $this->calculateDistributionFromBusinessProfile(
                $businessProfile,
                $transactionAmount,
                $operator
            );

            // Valider la cohérence hiérarchique
            $validation = $this->validateHierarchicalConsistency($businessProfile, $operator);

            return [
                'success' => true,
                'charging_point' => [
                    'id' => $chargingPoint->id,
                    'name' => $chargingPoint->name,
                    'serial_number' => $chargingPoint->serial_number,
                ],
                'business_profile' => [
                    'id' => $businessProfile->id,
                    'name' => $businessProfile->name,
                    'admin_fee_percentage' => $businessProfile->admin_fee_percentage,
                    'integrator_fee_percentage' => $businessProfile->integrator_fee_percentage,
                    'partner_fee_percentage' => $businessProfile->partner_fee_percentage,
                ],
                'operator' => [
                    'id' => $operator->id,
                    'name' => $operator->name,
                    'role' => $operator->role,
                ],
                'transaction' => [
                    'amount' => $transactionAmount,
                    'admin_fee' => $distribution['admin_fee'],
                    'integrator_fee' => $distribution['integrator_fee'],
                    'partner_fee' => $distribution['partner_fee'],
                    'operator_revenue' => $distribution['operator_revenue'],
                    'total_distributed' => $distribution['total_distributed'],
                ],
                'validation' => $validation,
                'calculated_at' => now()->toISOString(),
            ];

        } catch (\Exception $e) {
            Log::error('Erreur dans le calcul de la répartition des frais', [
                'charging_point_id' => $chargingPoint->id,
                'transaction_amount' => $transactionAmount,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'calculated_at' => now()->toISOString(),
            ];
        }
    }

    /**
     * Calculer la répartition basée sur le business profile
     *
     * @param BusinessProfile $businessProfile
     * @param float $transactionAmount
     * @param User $operator
     * @return array
     */
    private function calculateDistributionFromBusinessProfile(
        BusinessProfile $businessProfile,
        float $transactionAmount,
        User $operator
    ): array {
        // Calculer les frais selon le business profile appliqué
        $adminFee = ($transactionAmount * $businessProfile->admin_fee_percentage) / 100;
        $integratorFee = ($transactionAmount * $businessProfile->integrator_fee_percentage) / 100;
        $partnerFee = ($transactionAmount * $businessProfile->partner_fee_percentage) / 100;
        
        // Calculer le revenu de l'opérateur
        $operatorRevenue = $transactionAmount - $adminFee - $integratorFee - $partnerFee;
        
        // Calculer le total distribué
        $totalDistributed = $adminFee + $integratorFee + $partnerFee;

        return [
            'admin_fee' => $adminFee,
            'integrator_fee' => $integratorFee,
            'partner_fee' => $partnerFee,
            'operator_revenue' => $operatorRevenue,
            'total_distributed' => $totalDistributed,
        ];
    }

    /**
     * Valider la cohérence hiérarchique
     *
     * @param BusinessProfile $businessProfile
     * @param User $operator
     * @return array
     */
    private function validateHierarchicalConsistency(BusinessProfile $businessProfile, User $operator): array
    {
        $validation = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => [],
            'recommendations' => [],
        ];

        try {
            // Récupérer le business profile de l'intégrateur (niveau supérieur)
            $integratorBusinessProfile = $this->getIntegratorBusinessProfile($operator);
            
            if ($integratorBusinessProfile) {
                // Valider que les frais de l'opérateur sont ≤ aux frais de l'intégrateur
                if ($businessProfile->admin_fee_percentage > $integratorBusinessProfile->admin_fee_percentage) {
                    $validation['is_valid'] = false;
                    $validation['errors'][] = "Les frais Admin de l'opérateur ({$businessProfile->admin_fee_percentage}%) sont supérieurs à ceux de l'intégrateur ({$integratorBusinessProfile->admin_fee_percentage}%).";
                }

                if ($businessProfile->integrator_fee_percentage > $integratorBusinessProfile->integrator_fee_percentage) {
                    $validation['is_valid'] = false;
                    $validation['errors'][] = "Les frais Integrator de l'opérateur ({$businessProfile->integrator_fee_percentage}%) sont supérieurs à ceux de l'intégrateur ({$integratorBusinessProfile->integrator_fee_percentage}%).";
                }

                if ($businessProfile->partner_fee_percentage > $integratorBusinessProfile->partner_fee_percentage) {
                    $validation['is_valid'] = false;
                    $validation['errors'][] = "Les frais Partner de l'opérateur ({$businessProfile->partner_fee_percentage}%) sont supérieurs à ceux de l'intégrateur ({$integratorBusinessProfile->partner_fee_percentage}%).";
                }

                // Générer des recommandations
                $this->generateRecommendations($businessProfile, $integratorBusinessProfile, $validation);
            } else {
                $validation['warnings'][] = "Impossible de récupérer le business profile de l'intégrateur pour la validation hiérarchique.";
            }

        } catch (\Exception $e) {
            $validation['is_valid'] = false;
            $validation['errors'][] = "Erreur lors de la validation hiérarchique: " . $e->getMessage();
        }

        return $validation;
    }

    /**
     * Récupérer le business profile de l'intégrateur
     *
     * @param User $operator
     * @return BusinessProfile|null
     */
    private function getIntegratorBusinessProfile(User $operator): ?BusinessProfile
    {
        // Pour l'instant, on suppose qu'il y a un business_profile_id direct sur l'opérateur
        // Dans un système plus complexe, cela pourrait impliquer une BusinessProfileApplication
        if ($operator->business_profile_id) {
            return BusinessProfile::find($operator->business_profile_id);
        }

        // Alternativement, chercher une application de profil par l'intégrateur à cet opérateur
        $application = \App\Models\BusinessProfileApplication::where('applied_to_id', $operator->id)
            ->where('applied_to_type', User::class)
            ->where('application_type', 'integrator_to_operator')
            ->where('status', 'applied')
            ->latest()
            ->first();

        if ($application && $application->businessProfile) {
            return $application->businessProfile;
        }

        return null;
    }

    /**
     * Générer des recommandations
     *
     * @param BusinessProfile $operatorProfile
     * @param BusinessProfile $integratorProfile
     * @param array $validation
     * @return void
     */
    private function generateRecommendations(
        BusinessProfile $operatorProfile,
        BusinessProfile $integratorProfile,
        array &$validation
    ): void {
        $totalIntegratorFees = $integratorProfile->admin_fee_percentage +
                              $integratorProfile->integrator_fee_percentage +
                              $integratorProfile->partner_fee_percentage;

        $totalOperatorFees = $operatorProfile->admin_fee_percentage +
                            $operatorProfile->integrator_fee_percentage +
                            $operatorProfile->partner_fee_percentage;

        $validation['recommendations']['total_integrator_share'] = $totalIntegratorFees;
        $validation['recommendations']['total_operator_share'] = $totalOperatorFees;
        $validation['recommendations']['remaining_for_integrator'] = $totalIntegratorFees - $totalOperatorFees;

        if ($validation['recommendations']['remaining_for_integrator'] < 0) {
            $validation['warnings'][] = "La somme des frais de l'opérateur dépasse la somme des frais de l'intégrateur.";
        }

        // Recommandations pour optimiser les frais
        if ($operatorProfile->admin_fee_percentage < $integratorProfile->admin_fee_percentage * 0.5) {
            $validation['recommendations'][] = "Les frais Admin de l'opérateur sont très faibles par rapport à ceux de l'intégrateur.";
        }

        if ($operatorProfile->integrator_fee_percentage < $integratorProfile->integrator_fee_percentage * 0.5) {
            $validation['recommendations'][] = "Les frais Integrator de l'opérateur sont très faibles par rapport à ceux de l'intégrateur.";
        }
    }

    /**
     * Obtenir le résumé des frais hiérarchiques
     *
     * @param ChargingPoint $chargingPoint
     * @return array
     */
    public function getHierarchicalFeesSummary(ChargingPoint $chargingPoint): array
    {
        try {
            $businessProfile = $chargingPoint->businessProfile;
            $operator = $chargingPoint->user;

            if (!$businessProfile || !$operator) {
                throw new \Exception("Données manquantes pour le résumé des frais");
            }

            $integratorProfile = $this->getIntegratorBusinessProfile($operator);

            return [
                'success' => true,
                'charging_point' => [
                    'id' => $chargingPoint->id,
                    'name' => $chargingPoint->name,
                ],
                'operator_profile' => [
                    'id' => $businessProfile->id,
                    'name' => $businessProfile->name,
                    'admin_fee_percentage' => $businessProfile->admin_fee_percentage,
                    'integrator_fee_percentage' => $businessProfile->integrator_fee_percentage,
                    'partner_fee_percentage' => $businessProfile->partner_fee_percentage,
                ],
                'integrator_profile' => $integratorProfile ? [
                    'id' => $integratorProfile->id,
                    'name' => $integratorProfile->name,
                    'admin_fee_percentage' => $integratorProfile->admin_fee_percentage,
                    'integrator_fee_percentage' => $integratorProfile->integrator_fee_percentage,
                    'partner_fee_percentage' => $integratorProfile->partner_fee_percentage,
                ] : null,
                'hierarchical_consistency' => $this->validateHierarchicalConsistency($businessProfile, $operator),
                'calculated_at' => now()->toISOString(),
            ];

        } catch (\Exception $e) {
            Log::error('Erreur dans le résumé des frais hiérarchiques', [
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'calculated_at' => now()->toISOString(),
            ];
        }
    }
}
