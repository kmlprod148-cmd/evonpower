<?php

namespace App\Services;

use App\Models\BusinessProfile;
use App\Models\Transaction;
use App\Models\Reservation;
use Illuminate\Support\Facades\Log;

class TransactionManagementService
{
    /**
     * Get transaction management details for a business profile
     */
    public function getTransactionManagementDetails(BusinessProfile $businessProfile): array
    {
        return [
            'business_profile_id' => $businessProfile->id,
            'business_profile_name' => $businessProfile->name,
            'transaction_management' => $businessProfile->getTransactionManagementConfig(),
            'contacts' => [
                'admin' => $businessProfile->getAdminContact(),
                'integrator' => $businessProfile->getIntegratorContact(),
            ],
            'capabilities' => [
                'handles_admin_debits' => $businessProfile->handlesAdminDebits(),
                'handles_integrator_debits' => $businessProfile->handlesIntegratorDebits(),
                'handles_client_payments' => $businessProfile->handlesClientPayments(),
                'handles_wire_transfers' => $businessProfile->handlesWireTransfers(),
            ],
            'limits' => [
                'min_transaction_amount' => $businessProfile->min_transaction_amount,
                'max_transaction_amount' => $businessProfile->max_transaction_amount,
                'daily_limit' => $businessProfile->daily_limit,
                'monthly_limit' => $businessProfile->monthly_limit,
            ]
        ];
    }

    /**
     * Determine who should handle a specific transaction type
     */
    public function getTransactionHandler(Transaction $transaction, string $transactionType): ?array
    {
        $chargingPoint = $transaction->chargingPoint;
        if (!$chargingPoint) {
            return null;
        }

        $businessProfile = $chargingPoint->businessProfile;
        if (!$businessProfile) {
            return null;
        }

        $managementConfig = $businessProfile->getTransactionManagementConfig();

        switch ($transactionType) {
            case 'admin_debit':
                if ($managementConfig['admin_debits']['enabled']) {
                    return [
                        'type' => 'admin',
                        'contact' => $managementConfig['admin_debits']['contact'],
                        'bank_account' => $managementConfig['admin_debits']['bank_account'],
                        'business_profile_id' => $businessProfile->id,
                        'business_profile_name' => $businessProfile->name
                    ];
                }
                break;

            case 'integrator_debit':
                if ($managementConfig['integrator_debits']['enabled']) {
                    return [
                        'type' => 'integrator',
                        'contact' => $managementConfig['integrator_debits']['contact'],
                        'bank_account' => $managementConfig['integrator_debits']['bank_account'],
                        'business_profile_id' => $businessProfile->id,
                        'business_profile_name' => $businessProfile->name
                    ];
                }
                break;

            case 'client_payment':
                if ($managementConfig['client_payments']['enabled']) {
                    return [
                        'type' => 'operator',
                        'bank_account' => $managementConfig['client_payments']['bank_account'],
                        'business_profile_id' => $businessProfile->id,
                        'business_profile_name' => $businessProfile->name
                    ];
                }
                break;

            case 'wire_transfer':
                if ($managementConfig['wire_transfers']['enabled']) {
                    return [
                        'type' => 'wire_transfer',
                        'limits' => $managementConfig['wire_transfers']['limits'],
                        'business_profile_id' => $businessProfile->id,
                        'business_profile_name' => $businessProfile->name
                    ];
                }
                break;
        }

        return null;
    }

    /**
     * Generate transaction processing instructions
     */
    public function generateProcessingInstructions(Transaction $transaction): array
    {
        $instructions = [
            'transaction_id' => $transaction->id,
            'amount' => $transaction->price_total,
            'currency' => $transaction->currency ?? 'EUR',
            'processing_steps' => []
        ];

        // Determine handlers for different transaction types
        $adminHandler = $this->getTransactionHandler($transaction, 'admin_debit');
        $integratorHandler = $this->getTransactionHandler($transaction, 'integrator_debit');
        $clientHandler = $this->getTransactionHandler($transaction, 'client_payment');
        $wireTransferHandler = $this->getTransactionHandler($transaction, 'wire_transfer');

        // Generate processing steps
        if ($adminHandler) {
            $instructions['processing_steps'][] = [
                'step' => 'admin_debit',
                'description' => 'Débit Admin - Frais de transaction',
                'amount' => $transaction->admin_commission ?? 0,
                'handler' => $adminHandler,
                'action' => 'Debit admin account',
                'bank_account' => $adminHandler['bank_account'],
                'contact' => $adminHandler['contact']
            ];
        }

        if ($integratorHandler) {
            $instructions['processing_steps'][] = [
                'step' => 'integrator_debit',
                'description' => 'Débit Intégrateur - Commission',
                'amount' => $transaction->integrator_commission ?? 0,
                'handler' => $integratorHandler,
                'action' => 'Debit integrator account',
                'bank_account' => $integratorHandler['bank_account'],
                'contact' => $integratorHandler['contact']
            ];
        }

        if ($clientHandler) {
            $instructions['processing_steps'][] = [
                'step' => 'client_payment',
                'description' => 'Paiement Client - Revenu opérateur',
                'amount' => $transaction->price_total - ($transaction->admin_commission ?? 0) - ($transaction->integrator_commission ?? 0),
                'handler' => $clientHandler,
                'action' => 'Credit operator account',
                'bank_account' => $clientHandler['bank_account']
            ];
        }

        if ($wireTransferHandler) {
            $instructions['processing_steps'][] = [
                'step' => 'wire_transfer',
                'description' => 'Virement bancaire',
                'amount' => $transaction->price_total,
                'handler' => $wireTransferHandler,
                'action' => 'Process wire transfer',
                'limits' => $wireTransferHandler['limits']
            ];
        }

        return $instructions;
    }

    /**
     * Log transaction processing details
     */
    public function logTransactionProcessing(Transaction $transaction): void
    {
        $instructions = $this->generateProcessingInstructions($transaction);
        
        Log::info('Transaction Processing Instructions', [
            'transaction_id' => $transaction->id,
            'reservation_id' => $transaction->reservation_id,
            'charging_point_id' => $transaction->charging_point_id,
            'total_amount' => $transaction->price_total,
            'processing_steps' => $instructions['processing_steps'],
            'business_profile_info' => $this->getBusinessProfileInfo($transaction)
        ]);
    }

    /**
     * Get business profile information for a transaction
     */
    private function getBusinessProfileInfo(Transaction $transaction): ?array
    {
        $chargingPoint = $transaction->chargingPoint;
        if (!$chargingPoint || !$chargingPoint->businessProfile) {
            return null;
        }

        return $this->getTransactionManagementDetails($chargingPoint->businessProfile);
    }

    /**
     * Validate transaction limits
     */
    public function validateTransactionLimits(Transaction $transaction): array
    {
        $chargingPoint = $transaction->chargingPoint;
        if (!$chargingPoint || !$chargingPoint->businessProfile) {
            return ['valid' => true, 'message' => 'No business profile found'];
        }

        $businessProfile = $chargingPoint->businessProfile;
        $amount = $transaction->price_total;

        $validation = [
            'valid' => true,
            'violations' => []
        ];

        // Check minimum amount
        if ($businessProfile->min_transaction_amount && $amount < $businessProfile->min_transaction_amount) {
            $validation['valid'] = false;
            $validation['violations'][] = "Montant minimum non respecté: {$amount} < {$businessProfile->min_transaction_amount}";
        }

        // Check maximum amount
        if ($businessProfile->max_transaction_amount && $amount > $businessProfile->max_transaction_amount) {
            $validation['valid'] = false;
            $validation['violations'][] = "Montant maximum dépassé: {$amount} > {$businessProfile->max_transaction_amount}";
        }

        // TODO: Check daily and monthly limits (would require additional queries)

        return $validation;
    }
}
