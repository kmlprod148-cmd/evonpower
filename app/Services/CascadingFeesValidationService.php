<?php

namespace App\Services;

use App\Models\User;
use App\Models\BusinessProfile;
use App\Models\ChargingPoint;
use App\Models\Integrator;
use App\Models\Partner;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Service de validation des frais en cascade
 * 
 * Ce service s'assure que les frais appliqués à un opérateur par son intégrateur
 * sont une partie des frais appliqués à l'intégrateur par l'admin.
 * 
 * LOGIQUE MÉTIER:
 * - Admin applique des frais à l'Intégrateur (ex: 10% admin, 5% intégrateur)
 * - Intégrateur applique des frais à l'Opérateur (ex: 3% admin, 2% intégrateur)
 * - Les frais de l'opérateur doivent être ≤ aux frais de l'intégrateur
 */
class CascadingFeesValidationService
{
    /**
     * Valider que les frais de l'opérateur sont cohérents avec ceux de l'intégrateur
     */
    public function validateOperatorFeesAgainstIntegratorFees(
        BusinessProfile $operatorProfile,
        User $operator,
        User $integrator
    ): array {
        $validation = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => [],
            'integrator_fees' => null,
            'operator_fees' => null,
            'recommendations' => []
        ];

        try {
            // 1. Récupérer les frais de l'intégrateur (appliqués par l'admin)
            $integratorFees = $this->getIntegratorFeesFromAdmin($integrator);
            $validation['integrator_fees'] = $integratorFees;

            // 2. Récupérer les frais de l'opérateur (appliqués par l'intégrateur)
            $operatorFees = $this->getOperatorFeesFromIntegrator($operator, $integrator);
            $validation['operator_fees'] = $operatorFees;

            // 3. Valider la cohérence des frais
            $this->validateFeesConsistency($integratorFees, $operatorFees, $validation);

            // 4. Générer des recommandations
            if ($integratorFees && $operatorFees) {
                $this->generateRecommendations($integratorFees, $operatorFees, $validation);
            }

        } catch (\Exception $e) {
            $validation['is_valid'] = false;
            $validation['errors'][] = "Erreur lors de la validation: " . $e->getMessage();
            Log::error('Erreur dans CascadingFeesValidationService', [
                'error' => $e->getMessage(),
                'operator_id' => $operator->id,
                'integrator_id' => $integrator->id
            ]);
        }

        return $validation;
    }

    /**
     * Récupérer les frais appliqués à l'intégrateur par l'admin
     */
    private function getIntegratorFeesFromAdmin(User $integrator): ?array
    {
        // Chercher l'intégrateur
        $integratorModel = Integrator::where('user_id', $integrator->id)->first();
        
        if (!$integratorModel || !$integratorModel->business_profile_id) {
            return null;
        }

        $businessProfile = BusinessProfile::find($integratorModel->business_profile_id);
        
        if (!$businessProfile) {
            return null;
        }

        return [
            'admin_fee_percentage' => $businessProfile->admin_fee_percentage ?? 0,
            'integrator_fee_percentage' => $businessProfile->integrator_fee_percentage ?? 0,
            'partner_fee_percentage' => $businessProfile->partner_fee_percentage ?? 0,
            'operator_commission' => $businessProfile->operator_commission ?? 0,
            'integrator_commission' => $businessProfile->integrator_commission ?? 0,
            'owner_commission' => $businessProfile->owner_commission ?? 0,
        ];
    }

    /**
     * Récupérer les frais appliqués à l'opérateur par l'intégrateur
     */
    private function getOperatorFeesFromIntegrator(User $operator, User $integrator): ?array
    {
        // Chercher le partenaire de l'opérateur
        $partner = Partner::where('user_id', $operator->id)->first();
        
        if (!$partner || !$partner->business_profile_id) {
            return null;
        }

        $businessProfile = BusinessProfile::find($partner->business_profile_id);
        
        if (!$businessProfile) {
            return null;
        }

        return [
            'admin_fee_percentage' => $businessProfile->admin_fee_percentage ?? 0,
            'integrator_fee_percentage' => $businessProfile->integrator_fee_percentage ?? 0,
            'partner_fee_percentage' => $businessProfile->partner_fee_percentage ?? 0,
            'operator_commission' => $businessProfile->operator_commission ?? 0,
            'integrator_commission' => $businessProfile->integrator_commission ?? 0,
            'owner_commission' => $businessProfile->owner_commission ?? 0,
        ];
    }

    /**
     * Valider la cohérence des frais
     */
    private function validateFeesConsistency(?array $integratorFees, ?array $operatorFees, array &$validation): void
    {
        if (!$integratorFees || !$operatorFees) {
            $validation['is_valid'] = false;
            $validation['errors'][] = "Impossible de récupérer les frais de l'intégrateur ou de l'opérateur";
            return;
        }

        // Vérifier que les frais de l'opérateur ne dépassent pas ceux de l'intégrateur
        $this->validateFeePercentage('admin_fee_percentage', $integratorFees, $operatorFees, $validation);
        $this->validateFeePercentage('integrator_fee_percentage', $integratorFees, $operatorFees, $validation);
        $this->validateFeePercentage('partner_fee_percentage', $integratorFees, $operatorFees, $validation);
        
        // Vérifier les commissions
        $this->validateCommission('operator_commission', $integratorFees, $operatorFees, $validation);
        $this->validateCommission('integrator_commission', $integratorFees, $operatorFees, $validation);
        $this->validateCommission('owner_commission', $integratorFees, $operatorFees, $validation);
    }

    /**
     * Valider un pourcentage de frais
     */
    private function validateFeePercentage(string $feeType, array $integratorFees, array $operatorFees, array &$validation): void
    {
        $integratorFee = $integratorFees[$feeType] ?? 0;
        $operatorFee = $operatorFees[$feeType] ?? 0;

        if ($operatorFee > $integratorFee) {
            $validation['is_valid'] = false;
            $validation['errors'][] = "Le {$feeType} de l'opérateur ({$operatorFee}%) ne peut pas dépasser celui de l'intégrateur ({$integratorFee}%)";
        } elseif ($operatorFee > 0 && $operatorFee < $integratorFee * 0.1) {
            $validation['warnings'][] = "Le {$feeType} de l'opérateur ({$operatorFee}%) est très faible par rapport à celui de l'intégrateur ({$integratorFee}%)";
        }
    }

    /**
     * Valider une commission
     */
    private function validateCommission(string $commissionType, array $integratorFees, array $operatorFees, array &$validation): void
    {
        $integratorCommission = $integratorFees[$commissionType] ?? 0;
        $operatorCommission = $operatorFees[$commissionType] ?? 0;

        if ($operatorCommission > $integratorCommission) {
            $validation['is_valid'] = false;
            $validation['errors'][] = "La {$commissionType} de l'opérateur ({$operatorCommission}%) ne peut pas dépasser celle de l'intégrateur ({$integratorCommission}%)";
        }
    }

    /**
     * Générer des recommandations
     */
    private function generateRecommendations(array $integratorFees, array $operatorFees, array &$validation): void
    {
        $totalIntegratorFees = ($integratorFees['admin_fee_percentage'] ?? 0) + 
                              ($integratorFees['integrator_fee_percentage'] ?? 0) + 
                              ($integratorFees['partner_fee_percentage'] ?? 0);
        
        $totalOperatorFees = ($operatorFees['admin_fee_percentage'] ?? 0) + 
                            ($operatorFees['integrator_fee_percentage'] ?? 0) + 
                            ($operatorFees['partner_fee_percentage'] ?? 0);

        if ($totalIntegratorFees > 0) {
            $percentage = ($totalOperatorFees / $totalIntegratorFees) * 100;
            
            if ($percentage < 50) {
                $validation['recommendations'][] = "Les frais de l'opérateur représentent seulement {$percentage}% des frais de l'intégrateur. Considérez augmenter les frais de l'opérateur.";
            } elseif ($percentage > 90) {
                $validation['recommendations'][] = "Les frais de l'opérateur représentent {$percentage}% des frais de l'intégrateur. L'intégrateur garde peu de marge.";
            }
        }
    }

    /**
     * Valider les frais lors de la création d'un charging point
     */
    public function validateChargingPointFees(ChargingPoint $chargingPoint): array
    {
        $validation = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => [],
            'charging_point_id' => $chargingPoint->id,
            'charging_point_name' => $chargingPoint->name
        ];

        try {
            // Récupérer l'opérateur (via partner_id)
            $partner = Partner::find($chargingPoint->partner_id);
            if (!$partner) {
                $validation['is_valid'] = false;
                $validation['errors'][] = "Partenaire non trouvé pour le charging point";
                return $validation;
            }

            $operator = User::find($partner->user_id);
            if (!$operator) {
                $validation['is_valid'] = false;
                $validation['errors'][] = "Opérateur non trouvé pour le partenaire";
                return $validation;
            }

            // Récupérer l'intégrateur
            $integrator = Integrator::find($chargingPoint->integrator_id);
            if (!$integrator) {
                $validation['is_valid'] = false;
                $validation['errors'][] = "Intégrateur non trouvé pour le charging point";
                return $validation;
            }

            $integratorUser = User::find($integrator->user_id);
            if (!$integratorUser) {
                $validation['is_valid'] = false;
                $validation['errors'][] = "Utilisateur intégrateur non trouvé";
                return $validation;
            }

            // Valider les frais en cascade
            $feesValidation = $this->validateOperatorFeesAgainstIntegratorFees(
                $chargingPoint->businessProfile ?? new BusinessProfile(),
                $operator,
                $integratorUser
            );

            $validation['fees_validation'] = $feesValidation;
            $validation['is_valid'] = $feesValidation['is_valid'];

        } catch (\Exception $e) {
            $validation['is_valid'] = false;
            $validation['errors'][] = "Erreur lors de la validation: " . $e->getMessage();
            Log::error('Erreur dans validateChargingPointFees', [
                'error' => $e->getMessage(),
                'charging_point_id' => $chargingPoint->id
            ]);
        }

        return $validation;
    }

    /**
     * Obtenir un résumé des frais en cascade
     */
    public function getCascadingFeesSummary(ChargingPoint $chargingPoint): array
    {
        $summary = [
            'charging_point_id' => $chargingPoint->id,
            'charging_point_name' => $chargingPoint->name,
            'admin_fees' => null,
            'integrator_fees' => null,
            'operator_fees' => null,
            'total_admin_share' => 0,
            'total_integrator_share' => 0,
            'total_operator_share' => 0
        ];

        try {
            // Frais de l'admin (appliqués à l'intégrateur)
            if ($chargingPoint->integrator_id) {
                $integrator = Integrator::find($chargingPoint->integrator_id);
                if ($integrator && $integrator->business_profile_id) {
                    $adminProfile = BusinessProfile::find($integrator->business_profile_id);
                    if ($adminProfile) {
                        $summary['admin_fees'] = [
                            'admin_fee_percentage' => $adminProfile->admin_fee_percentage ?? 0,
                            'integrator_fee_percentage' => $adminProfile->integrator_fee_percentage ?? 0,
                            'partner_fee_percentage' => $adminProfile->partner_fee_percentage ?? 0,
                        ];
                        $summary['total_admin_share'] = $adminProfile->admin_fee_percentage ?? 0;
                    }
                }
            }

            // Frais de l'intégrateur (appliqués à l'opérateur)
            if ($chargingPoint->partner_id) {
                $partner = Partner::find($chargingPoint->partner_id);
                if ($partner && $partner->business_profile_id) {
                    $integratorProfile = BusinessProfile::find($partner->business_profile_id);
                    if ($integratorProfile) {
                        $summary['integrator_fees'] = [
                            'admin_fee_percentage' => $integratorProfile->admin_fee_percentage ?? 0,
                            'integrator_fee_percentage' => $integratorProfile->integrator_fee_percentage ?? 0,
                            'partner_fee_percentage' => $integratorProfile->partner_fee_percentage ?? 0,
                        ];
                        $summary['total_integrator_share'] = $integratorProfile->integrator_fee_percentage ?? 0;
                    }
                }
            }

            // Frais de l'opérateur (business profile du charging point)
            if ($chargingPoint->business_profile_id) {
                $operatorProfile = BusinessProfile::find($chargingPoint->business_profile_id);
                if ($operatorProfile) {
                    $summary['operator_fees'] = [
                        'admin_fee_percentage' => $operatorProfile->admin_fee_percentage ?? 0,
                        'integrator_fee_percentage' => $operatorProfile->integrator_fee_percentage ?? 0,
                        'partner_fee_percentage' => $operatorProfile->partner_fee_percentage ?? 0,
                    ];
                    $summary['total_operator_share'] = $operatorProfile->partner_fee_percentage ?? 0;
                }
            }

        } catch (\Exception $e) {
            Log::error('Erreur dans getCascadingFeesSummary', [
                'error' => $e->getMessage(),
                'charging_point_id' => $chargingPoint->id
            ]);
        }

        return $summary;
    }
}
