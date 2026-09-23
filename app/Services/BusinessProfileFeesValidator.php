<?php

namespace App\Services;

use App\Models\BusinessProfile;
use Illuminate\Support\Facades\Log;

class BusinessProfileFeesValidator
{
    /**
     * Valider que tous les frais des Business Profiles sont correctement calculés
     */
    public function validateFeesCalculation(BusinessProfile $profile, float $totalAmount, string $role, array $calculatedFees): array
    {
        $validation = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => [],
            'fees_breakdown' => [],
            'expected_calculations' => []
        ];

        // 1. Valider les frais d'activation
        $activationValidation = $this->validateActivationFees($profile, $calculatedFees);
        $validation['fees_breakdown']['activation'] = $activationValidation;

        // 2. Valider les frais de transaction
        $transactionValidation = $this->validateTransactionFees($profile, $totalAmount, $calculatedFees);
        $validation['fees_breakdown']['transaction'] = $transactionValidation;

        // 3. Valider les frais de recharge
        $chargeValidation = $this->validateChargeFees($profile, $totalAmount, $calculatedFees);
        $validation['fees_breakdown']['charge'] = $chargeValidation;

        // 4. Valider les frais spécifiques au rôle
        $roleValidation = $this->validateRoleFees($profile, $totalAmount, $role, $calculatedFees);
        $validation['fees_breakdown']['role'] = $roleValidation;

        // 5. Valider le total des frais (avec le rôle pour exclure activation_fee selon la logique)
        $totalValidation = $this->validateTotalFees($calculatedFees, $role);
        $validation['fees_breakdown']['total'] = $totalValidation;

        // 6. Compiler les erreurs et avertissements
        foreach ($validation['fees_breakdown'] as $feeType => $feeValidation) {
            if (!$feeValidation['is_valid']) {
                $validation['is_valid'] = false;
                $validation['errors'] = array_merge($validation['errors'], $feeValidation['errors']);
            }
            if (!empty($feeValidation['warnings'])) {
                $validation['warnings'] = array_merge($validation['warnings'], $feeValidation['warnings']);
            }
        }

        // 7. Logger la validation
        $this->logValidation($profile, $role, $validation);

        return $validation;
    }

    /**
     * Valider les frais d'activation
     */
    protected function validateActivationFees(BusinessProfile $profile, array $calculatedFees): array
    {
        $validation = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => [],
            'expected' => 0,
            'calculated' => 0
        ];

        $expectedActivationFee = (float) ($profile->base_fee_amount ?? 0);
        $calculatedActivationFee = (float) ($calculatedFees['activation_fee'] ?? 0);

        $validation['expected'] = $expectedActivationFee;
        $validation['calculated'] = $calculatedActivationFee;

        if (abs($expectedActivationFee - $calculatedActivationFee) > 0.01) {
            $validation['is_valid'] = false;
            $validation['errors'][] = "Frais d'activation incorrects. Attendu: {$expectedActivationFee}, Calculé: {$calculatedActivationFee}";
        }

        return $validation;
    }

    /**
     * Valider les frais de transaction
     */
    protected function validateTransactionFees(BusinessProfile $profile, float $totalAmount, array $calculatedFees): array
    {
        $validation = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => [],
            'expected' => 0,
            'calculated' => 0,
            'breakdown' => []
        ];

        $transactionConfig = is_string($profile->transaction_fee_config) 
            ? json_decode($profile->transaction_fee_config, true) 
            : $profile->transaction_fee_config;

        if ($transactionConfig && is_array($transactionConfig)) {
            $fixedAmount = (float) ($transactionConfig['fixed_amount'] ?? 0);
            $percentage = (float) ($transactionConfig['percentage'] ?? 0);
            
            $expectedTransactionFee = $fixedAmount + ($totalAmount * $percentage / 100);
            $calculatedTransactionFee = (float) ($calculatedFees['transaction_fee'] ?? 0);

            $validation['expected'] = $expectedTransactionFee;
            $validation['calculated'] = $calculatedTransactionFee;
            $validation['breakdown'] = [
                'fixed_amount' => $fixedAmount,
                'percentage' => $percentage,
                'formula' => "{$fixedAmount} + ({$totalAmount} × {$percentage}%)"
            ];

            if (abs($expectedTransactionFee - $calculatedTransactionFee) > 0.01) {
                $validation['is_valid'] = false;
                $validation['errors'][] = "Frais de transaction incorrects. Attendu: {$expectedTransactionFee}, Calculé: {$calculatedTransactionFee}";
            }
        } else {
            $validation['warnings'][] = "Configuration des frais de transaction manquante ou invalide";
        }

        return $validation;
    }

    /**
     * Valider les frais de recharge
     */
    protected function validateChargeFees(BusinessProfile $profile, float $totalAmount, array $calculatedFees): array
    {
        $validation = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => [],
            'expected' => 0,
            'calculated' => 0,
            'breakdown' => []
        ];

        $chargeConfig = is_string($profile->charge_fee_config) 
            ? json_decode($profile->charge_fee_config, true) 
            : $profile->charge_fee_config;

        if ($chargeConfig && is_array($chargeConfig)) {
            $fixedAmount = (float) ($chargeConfig['fixed_amount'] ?? 0);
            $percentage = (float) ($chargeConfig['percentage'] ?? 0);
            
            $expectedChargeFee = $fixedAmount + ($totalAmount * $percentage / 100);
            $calculatedChargeFee = (float) ($calculatedFees['charge_fee'] ?? 0);

            $validation['expected'] = $expectedChargeFee;
            $validation['calculated'] = $calculatedChargeFee;
            $validation['breakdown'] = [
                'fixed_amount' => $fixedAmount,
                'percentage' => $percentage,
                'formula' => "{$fixedAmount} + ({$totalAmount} × {$percentage}%)"
            ];

            if (abs($expectedChargeFee - $calculatedChargeFee) > 0.01) {
                $validation['is_valid'] = false;
                $validation['errors'][] = "Frais de recharge incorrects. Attendu: {$expectedChargeFee}, Calculé: {$calculatedChargeFee}";
            }
        } else {
            $validation['warnings'][] = "Configuration des frais de recharge manquante ou invalide";
        }

        return $validation;
    }

    /**
     * Valider les frais spécifiques au rôle
     * 
     * LOGIQUE CORRIGÉE :
     * - Pour l'admin : role_fee = admin_fee_fixed + admin_fee_percentage (INCLUS dans total_fees)
     * - Pour l'intégrateur : role_fee = 0 (les integrator_fee_* ne sont PAS inclus dans les part fees)
     */
    protected function validateRoleFees(BusinessProfile $profile, float $totalAmount, string $role, array $calculatedFees): array
    {
        $validation = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => [],
            'expected' => 0,
            'calculated' => 0,
            'breakdown' => []
        ];

        if ($role === 'admin') {
            $fixedAmount = (float) ($profile->admin_fee_fixed ?? 0);
            $percentage = (float) ($profile->admin_fee_percentage ?? 0);
            // Pour l'admin : role_fee doit être calculé et inclus dans total_fees
            $expectedRoleFee = $fixedAmount + ($totalAmount * $percentage / 100);
        } elseif ($role === 'integrator') {
            $fixedAmount = (float) ($profile->integrator_fee_fixed ?? 0);
            $percentage = (float) ($profile->integrator_fee_percentage ?? 0);
            // Pour l'intégrateur : role_fee doit être 0 car les integrator_fee_* ne sont PAS inclus dans les part fees
            $expectedRoleFee = 0;
        } else {
            $validation['warnings'][] = "Rôle non reconnu pour la validation des frais: {$role}";
            return $validation;
        }

        $calculatedRoleFee = (float) ($calculatedFees['role_fee'] ?? 0);

        $validation['expected'] = $expectedRoleFee;
        $validation['calculated'] = $calculatedRoleFee;
        $validation['breakdown'] = [
            'role' => $role,
            'fixed_amount' => $fixedAmount,
            'percentage' => $percentage,
            'formula' => $role === 'admin' 
                ? "{$fixedAmount} + ({$totalAmount} × {$percentage}%)" 
                : "0 (integrator_fee_* non inclus dans les part fees)",
            'note' => $role === 'integrator' 
                ? "Les integrator_fee_* ne sont pas inclus dans les part fees. Part fees = Config Transaction + Config Charge uniquement"
                : "Frais admin inclus dans total_fees"
        ];

        if (abs($expectedRoleFee - $calculatedRoleFee) > 0.01) {
            $validation['is_valid'] = false;
            $validation['errors'][] = "Frais {$role} incorrects. Attendu: {$expectedRoleFee}, Calculé: {$calculatedRoleFee}";
            if ($role === 'integrator') {
                $validation['errors'][] = "Pour l'intégrateur, role_fee doit être 0 car les integrator_fee_* ne sont pas inclus dans les part fees";
            }
        }

        return $validation;
    }

    /**
     * Valider le total des frais
     * 
     * LOGIQUE CORRIGÉE :
     * Les frais d'activation (activation_fee) sont exclus du calcul des parts hiérarchiques.
     * - Pour l'intégrateur : total_fees = transaction_fee + charge_fee (SANS activation_fee ni role_fee)
     * - Pour l'admin : total_fees = transaction_fee + charge_fee + role_fee (SANS activation_fee)
     */
    protected function validateTotalFees(array $calculatedFees, string $role = null): array
    {
        $validation = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => [],
            'expected' => 0,
            'calculated' => 0,
            'breakdown' => []
        ];

        $activationFee = (float) ($calculatedFees['activation_fee'] ?? 0);
        $transactionFee = (float) ($calculatedFees['transaction_fee'] ?? 0);
        $chargeFee = (float) ($calculatedFees['charge_fee'] ?? 0);
        $roleFee = (float) ($calculatedFees['role_fee'] ?? 0);

        // Calculer le total attendu selon la logique hiérarchique (sans activation_fee)
        if ($role === 'integrator') {
            // Pour l'intégrateur : FRAIS = CONFIG TRANSACTION + CONFIG CHARGE (SANS activation_fee ni role_fee)
            $expectedTotal = $transactionFee + $chargeFee;
            $formula = "{$transactionFee} + {$chargeFee}";
            $note = "Frais d'activation exclus du calcul des parts hiérarchiques";
        } elseif ($role === 'admin') {
            // Pour l'admin : FRAIS = CONFIG TRANSACTION + CONFIG CHARGE + FRAIS ADMIN (SANS activation_fee)
            $expectedTotal = $transactionFee + $chargeFee + $roleFee;
            $formula = "{$transactionFee} + {$chargeFee} + {$roleFee}";
            $note = "Frais d'activation exclus du calcul des parts hiérarchiques";
        } else {
            // Par défaut (sans rôle spécifique) : inclure tous les frais sauf activation pour compatibilité
            $expectedTotal = $transactionFee + $chargeFee + $roleFee;
            $formula = "{$transactionFee} + {$chargeFee} + {$roleFee}";
            $note = "Frais d'activation exclus du calcul des parts hiérarchiques";
        }

        $calculatedTotal = (float) ($calculatedFees['total_fees'] ?? 0);

        $validation['expected'] = $expectedTotal;
        $validation['calculated'] = $calculatedTotal;
        $validation['breakdown'] = [
            'activation_fee' => $activationFee,
            'transaction_fee' => $transactionFee,
            'charge_fee' => $chargeFee,
            'role_fee' => $roleFee,
            'role' => $role,
            'formula' => $formula,
            'note' => $note,
            'activation_fee_excluded' => true
        ];

        if (abs($expectedTotal - $calculatedTotal) > 0.01) {
            $validation['is_valid'] = false;
            $validation['errors'][] = "Total des frais incorrect. Attendu: {$expectedTotal}, Calculé: {$calculatedTotal}";
        }

        return $validation;
    }

    /**
     * Logger la validation
     */
    protected function logValidation(BusinessProfile $profile, string $role, array $validation): void
    {
        $logData = [
            'business_profile_id' => $profile->id,
            'business_profile_name' => $profile->name,
            'role' => $role,
            'is_valid' => $validation['is_valid'],
            'error_count' => count($validation['errors']),
            'warning_count' => count($validation['warnings']),
            'fees_breakdown' => $validation['fees_breakdown']
        ];

        if ($validation['is_valid']) {
            Log::info("Validation des frais Business Profile réussie - {$role}", $logData);
        } else {
            Log::error("Validation des frais Business Profile échouée - {$role}", $logData);
        }
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
            'fees_validated' => array_keys($validation['fees_breakdown']),
            'total_fees_calculated' => $validation['fees_breakdown']['total']['calculated'] ?? 0
        ];
    }
}
