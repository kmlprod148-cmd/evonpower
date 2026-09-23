<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\BusinessProfile;

class TransactionAmountCalculator
{
    /**
     * Calculate HT and TTC amounts for a transaction
     * 
     * Utilise le service TransactionHTTTCService pour garantir la cohérence
     * des calculs HT/TTC basés sur la TVA
     */
    public function calculateAmounts(Transaction $transaction): array
    {
        // Utiliser le service centralisé pour calculer HT/TTC correctement (basé sur TVA)
        try {
            $httcService = new TransactionHTTTCService();
            $httcAmounts = $httcService->calculateHTTTC($transaction);
            
            return [
                'ht' => $httcAmounts['ht'],
                'ttc' => $httcAmounts['ttc'],
                'fees' => $httcAmounts['vat_amount'], // Les "fees" ici représentent la TVA
            ];
        } catch (\Exception $e) {
            // Fallback en cas d'erreur
            $amount = $transaction->amount ?? $transaction->price_total ?? 0;
            return [
                'ht' => round($amount, 2),
                'ttc' => round($amount, 2),
                'fees' => 0,
            ];
        }
    }
    
    /**
     * Calculate amounts from business profile
     */
    private function calculateFromBusinessProfile(Transaction $transaction, BusinessProfile $businessProfile): array
    {
        $baseAmount = $transaction->amount;
        
        // Calculate admin fees
        $adminFeePercentage = (float) ($businessProfile->admin_fee_percentage ?? 0);
        $adminFeeFixed = (float) ($businessProfile->admin_fee_fixed ?? 0);
        $adminFees = $adminFeeFixed + ($baseAmount * $adminFeePercentage / 100);
        
        // Calculate integrator fees
        $integratorFeePercentage = (float) ($businessProfile->integrator_fee_percentage ?? 0);
        $integratorFeeFixed = (float) ($businessProfile->integrator_fee_fixed ?? 0);
        $integratorFees = $integratorFeeFixed + ($baseAmount * $integratorFeePercentage / 100);
        
        // Calculate partner fees
        $partnerFeePercentage = (float) ($businessProfile->partner_fee_percentage ?? 0);
        $partnerFeeFixed = (float) ($businessProfile->partner_fee_fixed ?? 0);
        $partnerFees = $partnerFeeFixed + ($baseAmount * $partnerFeePercentage / 100);
        
        // Calculate total fees
        $totalFees = $adminFees + $integratorFees + $partnerFees;
        
        return [
            'ht' => $baseAmount,
            'ttc' => $baseAmount + $totalFees,
            'admin_fees' => $adminFees,
            'integrator_fees' => $integratorFees,
            'partner_fees' => $partnerFees,
            'total_fees' => $totalFees,
        ];
    }
    
    /**
     * Get transaction type (prepaid/postpaid)
     */
    public function getTransactionType(Transaction $transaction): string
    {
        if ($transaction->metadata && isset($transaction->metadata['payment_type'])) {
            return $transaction->metadata['payment_type'];
        }
        
        // Default logic based on transaction characteristics
        if ($transaction->status === 'completed' && $transaction->amount > 0) {
            return 'postpayée';
        }
        
        return 'prépayée';
    }
    
    /**
     * Get payment method
     */
    public function getPaymentMethod(Transaction $transaction): string
    {
        if ($transaction->metadata && isset($transaction->metadata['payment_method'])) {
            return $transaction->metadata['payment_method'];
        }
        
        return 'N/A';
    }
    
    /**
     * Get comprehensive transaction summary
     * 
     * Utilise le service TransactionHTTTCService pour les montants HT/TTC
     */
    public function getTransactionSummary(Transaction $transaction): array
    {
        // Utiliser le service centralisé pour calculer HT/TTC
        try {
            $httcService = new TransactionHTTTCService();
            $httcAmounts = $httcService->calculateHTTTC($transaction);
            $amountHT = $httcAmounts['ht'];
            $amountTTC = $httcAmounts['ttc'];
            $vatAmount = $httcAmounts['vat_amount'];
        } catch (\Exception $e) {
            // Fallback
            $amountHT = $transaction->amount ?? $transaction->price_total ?? 0;
            $amountTTC = $transaction->amount ?? $transaction->price_total ?? 0;
            $vatAmount = 0;
        }
        
        return [
            'id' => $transaction->id,
            'external_id' => $transaction->transaction_id,
            'user' => $transaction->user->name ?? 'N/A',
            'user_email' => $transaction->user->email ?? 'N/A',
            'charging_point' => $transaction->chargingPoint->name ?? 'N/A',
            'charging_point_id' => $transaction->chargingPoint->id ?? 'N/A',
            'type' => $this->getTransactionType($transaction),
            'payment_method' => $this->getPaymentMethod($transaction),
            'status' => $transaction->status,
            'amount_ht' => $amountHT,
            'amount_ttc' => $amountTTC,
            'fees' => $vatAmount, // TVA, pas les frais de business profile
            'date' => $transaction->created_at,
            'pricing_data' => $transaction->pricing_data,
            'hierarchy_data' => $transaction->hierarchy_data,
            'metadata' => $transaction->metadata,
        ];
    }
}
