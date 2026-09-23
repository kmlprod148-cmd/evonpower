<?php

namespace App\Services;

use App\Models\BusinessProfile;
use App\Models\ChargingPoint;
use App\Models\Group;
use App\Models\Partner;
use App\Models\Integrator;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class BusinessProfileHierarchyValidator
{
    /**
     * Valider que les Business Profiles sont appliqués selon la hiérarchie correcte
     */
    public function validateBusinessProfileHierarchy(array $hierarchy, array $businessProfiles): array
    {
        $validation = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => [],
            'hierarchy_validation' => [],
            'business_profile_validation' => []
        ];

        // 1. Valider la hiérarchie complète
        $hierarchyValidation = $this->validateHierarchyStructure($hierarchy);
        $validation['hierarchy_validation'] = $hierarchyValidation;

        if (!$hierarchyValidation['is_complete']) {
            $validation['is_valid'] = false;
            $validation['errors'] = array_merge($validation['errors'], $hierarchyValidation['errors']);
        }

        // 2. Valider les Business Profiles selon la hiérarchie
        $businessProfileValidation = $this->validateBusinessProfilesForHierarchy($hierarchy, $businessProfiles);
        $validation['business_profile_validation'] = $businessProfileValidation;

        if (!$businessProfileValidation['is_correct']) {
            $validation['is_valid'] = false;
            $validation['errors'] = array_merge($validation['errors'], $businessProfileValidation['errors']);
        }

        // 3. Valider la cohérence des frais
        $feesValidation = $this->validateFeesConsistency($businessProfiles);
        $validation['fees_validation'] = $feesValidation;

        if (!$feesValidation['is_consistent']) {
            $validation['warnings'] = array_merge($validation['warnings'], $feesValidation['warnings']);
        }

        return $validation;
    }

    /**
     * Valider la structure de hiérarchie
     */
    protected function validateHierarchyStructure(array $hierarchy): array
    {
        $validation = [
            'is_complete' => true,
            'errors' => [],
            'levels_present' => []
        ];

        // Vérifier chaque niveau de la hiérarchie
        $requiredLevels = ['charging_point', 'group', 'partner', 'integrator', 'admin', 'operator'];
        
        foreach ($requiredLevels as $level) {
            if (isset($hierarchy[$level]) && $hierarchy[$level] !== null) {
                $validation['levels_present'][$level] = true;
            } else {
                $validation['levels_present'][$level] = false;
                $validation['is_complete'] = false;
                $validation['errors'][] = "Niveau de hiérarchie manquant: {$level}";
            }
        }

        // Vérifier les relations entre les niveaux
        if ($hierarchy['group'] && $hierarchy['group']->partner_id !== $hierarchy['partner']->id) {
            $validation['is_complete'] = false;
            $validation['errors'][] = "Le groupe n'appartient pas au bon partenaire";
        }

        if ($hierarchy['partner'] && $hierarchy['partner']->integrator_id !== $hierarchy['integrator']->id) {
            $validation['is_complete'] = false;
            $validation['errors'][] = "Le partenaire n'appartient pas au bon intégrateur";
        }

        if ($hierarchy['integrator'] && $hierarchy['integrator']->created_by !== $hierarchy['admin']->id) {
            $validation['is_complete'] = false;
            $validation['errors'][] = "L'intégrateur n'a pas été créé par le bon admin";
        }

        return $validation;
    }

    /**
     * Valider que les Business Profiles correspondent à la hiérarchie
     */
    protected function validateBusinessProfilesForHierarchy(array $hierarchy, array $businessProfiles): array
    {
        $validation = [
            'is_correct' => true,
            'errors' => [],
            'admin_integrator_validation' => [],
            'integrator_operator_validation' => []
        ];

        // Valider Business Profile Admin → Intégrateur
        if ($businessProfiles['admin_integrator']) {
            $adminProfile = $businessProfiles['admin_integrator'];
            $adminValidation = $this->validateAdminIntegratorProfile($adminProfile, $hierarchy);
            $validation['admin_integrator_validation'] = $adminValidation;

            if (!$adminValidation['is_correct']) {
                $validation['is_correct'] = false;
                $validation['errors'] = array_merge($validation['errors'], $adminValidation['errors']);
            }
        } else {
            $validation['is_correct'] = false;
            $validation['errors'][] = "Business Profile Admin → Intégrateur manquant";
        }

        // Valider Business Profile Intégrateur → Opérateur
        if ($businessProfiles['integrator_operator']) {
            $integratorProfile = $businessProfiles['integrator_operator'];
            $integratorValidation = $this->validateIntegratorOperatorProfile($integratorProfile, $hierarchy);
            $validation['integrator_operator_validation'] = $integratorValidation;

            if (!$integratorValidation['is_correct']) {
                $validation['is_correct'] = false;
                $validation['errors'] = array_merge($validation['errors'], $integratorValidation['errors']);
            }
        } else {
            $validation['is_correct'] = false;
            $validation['errors'][] = "Business Profile Intégrateur → Opérateur manquant";
        }

        return $validation;
    }

    /**
     * Valider le Business Profile Admin → Intégrateur
     */
    protected function validateAdminIntegratorProfile(BusinessProfile $profile, array $hierarchy): array
    {
        $validation = [
            'is_correct' => true,
            'errors' => [],
            'checks' => []
        ];

        // Vérifier que le profil a été créé par l'admin
        if ($profile->created_by_id !== $hierarchy['admin']->id) {
            $validation['is_correct'] = false;
            $validation['errors'][] = "Le Business Profile Admin n'a pas été créé par le bon admin";
        }
        $validation['checks']['created_by_admin'] = $profile->created_by_id === $hierarchy['admin']->id;

        // Vérifier que le profil est associé au bon intégrateur
        if ($profile->integrator_id !== $hierarchy['integrator']->id) {
            $validation['is_correct'] = false;
            $validation['errors'][] = "Le Business Profile Admin n'est pas associé au bon intégrateur";
        }
        $validation['checks']['integrator_association'] = $profile->integrator_id === $hierarchy['integrator']->id;

        // Vérifier que le profil est actif
        if (!$profile->is_active) {
            $validation['is_correct'] = false;
            $validation['errors'][] = "Le Business Profile Admin n'est pas actif";
        }
        $validation['checks']['is_active'] = $profile->is_active;

        // Vérifier la présence de frais admin
        $hasAdminFees = ($profile->admin_fee_fixed > 0) || ($profile->admin_fee_percentage > 0);
        if (!$hasAdminFees) {
            $validation['errors'][] = "Le Business Profile Admin n'a pas de frais configurés";
        }
        $validation['checks']['has_admin_fees'] = $hasAdminFees;

        return $validation;
    }

    /**
     * Valider le Business Profile Intégrateur → Opérateur
     */
    protected function validateIntegratorOperatorProfile(BusinessProfile $profile, array $hierarchy): array
    {
        $validation = [
            'is_correct' => true,
            'errors' => [],
            'checks' => []
        ];

        // Vérifier que le profil a été créé par l'intégrateur
        if ($profile->created_by_id !== $hierarchy['integrator']->id) {
            $validation['is_correct'] = false;
            $validation['errors'][] = "Le Business Profile Intégrateur n'a pas été créé par le bon intégrateur";
        }
        $validation['checks']['created_by_integrator'] = $profile->created_by_id === $hierarchy['integrator']->id;

        // Vérifier que le profil est associé au bon partenaire
        if ($profile->partner_id !== $hierarchy['partner']->id) {
            $validation['is_correct'] = false;
            $validation['errors'][] = "Le Business Profile Intégrateur n'est pas associé au bon partenaire";
        }
        $validation['checks']['partner_association'] = $profile->partner_id === $hierarchy['partner']->id;

        // Vérifier que le profil est actif
        if (!$profile->is_active) {
            $validation['is_correct'] = false;
            $validation['errors'][] = "Le Business Profile Intégrateur n'est pas actif";
        }
        $validation['checks']['is_active'] = $profile->is_active;

        // Vérifier la présence de frais intégrateur
        $hasIntegratorFees = ($profile->integrator_fee_fixed > 0) || ($profile->integrator_fee_percentage > 0);
        if (!$hasIntegratorFees) {
            $validation['errors'][] = "Le Business Profile Intégrateur n'a pas de frais configurés";
        }
        $validation['checks']['has_integrator_fees'] = $hasIntegratorFees;

        return $validation;
    }

    /**
     * Valider la cohérence des frais entre les Business Profiles
     */
    protected function validateFeesConsistency(array $businessProfiles): array
    {
        $validation = [
            'is_consistent' => true,
            'warnings' => [],
            'fees_analysis' => []
        ];

        $adminProfile = $businessProfiles['admin_integrator'];
        $integratorProfile = $businessProfiles['integrator_operator'];

        if (!$adminProfile || !$integratorProfile) {
            return $validation;
        }

        // Analyser les frais de chaque profil
        $adminFees = $this->analyzeProfileFees($adminProfile);
        $integratorFees = $this->analyzeProfileFees($integratorProfile);

        $validation['fees_analysis'] = [
            'admin_profile' => $adminFees,
            'integrator_profile' => $integratorFees
        ];

        // Vérifier que les frais ne sont pas trop élevés
        $totalAdminFees = $adminFees['total_percentage'] + ($adminFees['total_fixed'] / 100); // Normaliser pour 100€
        $totalIntegratorFees = $integratorFees['total_percentage'] + ($integratorFees['total_fixed'] / 100);

        if ($totalAdminFees > 50) { // Plus de 50% du montant
            $validation['warnings'][] = "Les frais admin sont très élevés ({$totalAdminFees}%)";
        }

        if ($totalIntegratorFees > 30) { // Plus de 30% du montant
            $validation['warnings'][] = "Les frais intégrateur sont très élevés ({$totalIntegratorFees}%)";
        }

        if (($totalAdminFees + $totalIntegratorFees) > 80) { // Plus de 80% du montant total
            $validation['warnings'][] = "Les frais totaux sont très élevés (" . ($totalAdminFees + $totalIntegratorFees) . "%)";
        }

        return $validation;
    }

    /**
     * Analyser les frais d'un Business Profile
     */
    protected function analyzeProfileFees(BusinessProfile $profile): array
    {
        $analysis = [
            'total_percentage' => 0,
            'total_fixed' => 0,
            'breakdown' => []
        ];

        // Frais de base
        $baseFee = (float) ($profile->base_fee_amount ?? 0);
        $analysis['total_fixed'] += $baseFee;
        $analysis['breakdown']['base_fee'] = $baseFee;

        // Frais de transaction
        $transactionConfig = is_string($profile->transaction_fee_config) 
            ? json_decode($profile->transaction_fee_config, true) 
            : $profile->transaction_fee_config;

        if ($transactionConfig) {
            $transactionFixed = (float) ($transactionConfig['fixed_amount'] ?? 0);
            $transactionPercentage = (float) ($transactionConfig['percentage'] ?? 0);
            
            $analysis['total_fixed'] += $transactionFixed;
            $analysis['total_percentage'] += $transactionPercentage;
            $analysis['breakdown']['transaction_fee'] = [
                'fixed' => $transactionFixed,
                'percentage' => $transactionPercentage
            ];
        }

        // Frais de recharge
        $chargeConfig = is_string($profile->charge_fee_config) 
            ? json_decode($profile->charge_fee_config, true) 
            : $profile->charge_fee_config;

        if ($chargeConfig) {
            $chargeFixed = (float) ($chargeConfig['fixed_amount'] ?? 0);
            $chargePercentage = (float) ($chargeConfig['percentage'] ?? 0);
            
            $analysis['total_fixed'] += $chargeFixed;
            $analysis['total_percentage'] += $chargePercentage;
            $analysis['breakdown']['charge_fee'] = [
                'fixed' => $chargeFixed,
                'percentage' => $chargePercentage
            ];
        }

        // Frais spécifiques au rôle
        $roleFixed = (float) ($profile->admin_fee_fixed ?? $profile->integrator_fee_fixed ?? 0);
        $rolePercentage = (float) ($profile->admin_fee_percentage ?? $profile->integrator_fee_percentage ?? 0);
        
        $analysis['total_fixed'] += $roleFixed;
        $analysis['total_percentage'] += $rolePercentage;
        $analysis['breakdown']['role_fee'] = [
            'fixed' => $roleFixed,
            'percentage' => $rolePercentage
        ];

        return $analysis;
    }

    /**
     * Obtenir un résumé de validation
     */
    public function getValidationSummary(array $validation): array
    {
        return [
            'is_valid' => $validation['is_valid'],
            'has_errors' => count($validation['errors']) > 0,
            'has_warnings' => count($validation['warnings']) > 0,
            'error_count' => count($validation['errors']),
            'warning_count' => count($validation['warnings']),
            'hierarchy_complete' => $validation['hierarchy_validation']['is_complete'],
            'business_profiles_correct' => $validation['business_profile_validation']['is_correct'],
            'fees_consistent' => $validation['fees_validation']['is_consistent']
        ];
    }
}
