<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\RevenueShare;
use App\Models\BusinessProfile;
use App\Models\Integrator;
use App\Models\Partner;
use App\Models\CommissionPlan;
use App\Models\Account;
use App\Models\FinancialTransaction;

class RevenueDistributionService
{
    public function distributeRevenue(Transaction $transaction): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($transaction) {
            $transaction->loadMissing([
                'chargingPoint.station.owner.businessProfile.integrator',
                'chargingPoint.station.owner.businessProfile.partner',
            ]);

            // Defensive checks for the relationship chain
            if (!$transaction->chargingPoint) {
                \Illuminate\Support\Facades\Log::error("Transaction {$transaction->id} is missing the chargingPoint relationship.");
                return;
            }
            if (!$transaction->chargingPoint->station) {
                \Illuminate\Support\Facades\Log::error("Charging point {$transaction->chargingPoint->id} is missing the station relationship.");
                return;
            }
            if (!$transaction->chargingPoint->station->owner) {
                \Illuminate\Support\Facades\Log::error("Charging station {$transaction->chargingPoint->station->id} is missing the owner relationship.");
                return;
            }
            if (!$transaction->chargingPoint->station->owner->businessProfile) {
                \Illuminate\Support\Facades\Log::warning("Owner {$transaction->chargingPoint->station->owner->id} does not have a business profile. Revenue not distributed for transaction ID: {$transaction->id}.");
                return;
            }

            $businessProfile = $transaction->chargingPoint->station->owner->businessProfile;

            $totalRevenue = $transaction->price_total;
            $activationFee = $transaction->price_service ?? 0;
            $revenueToDistribute = $totalRevenue - $activationFee;

            $businessProfileAccount = $businessProfile->account()->firstOrCreate([
                'accountable_type' => BusinessProfile::class,
                'accountable_id' => $businessProfile->id,
            ], ['balance' => 0, 'currency' => 'EUR']);

            FinancialTransaction::create([
                'transaction_id' => $transaction->id,
                'payer_account_id' => null,
                'payee_account_id' => $businessProfileAccount->id,
                'amount' => $totalRevenue,
                'type' => 'revenue_deposit',
                'description' => "Initial revenue deposit for charging session {$transaction->id}",
            ]);
            $businessProfileAccount->increment('balance', $totalRevenue);

            $repartitionBreakdown = [
                'activation_fee' => $activationFee,
                'operator' => 0,
                'integrator' => 0,
                'partner' => 0,
                'owner' => 0,
            ];

            $remainingRevenueForDistribution = $revenueToDistribute;

            // Integrator Commission
            $integrator = $businessProfile->integrator;
            if ($integrator && $businessProfile->integrator_commission > 0) {
                $integratorCommission = round($revenueToDistribute * ($businessProfile->integrator_commission / 100), 2);
                if ($integratorCommission > 0) {
                    $this->payoutCommission($transaction, $businessProfileAccount, $integrator, $integratorCommission, 'integrator');
                    $repartitionBreakdown['integrator'] = $integratorCommission;
                    $remainingRevenueForDistribution -= $integratorCommission;
                }
            }

            // Partner Commission
            $partner = $businessProfile->partner;
            if ($partner && $businessProfile->partner_commission > 0) {
                $partnerCommission = round($revenueToDistribute * ($businessProfile->partner_commission / 100), 2);
                if ($partnerCommission > 0) {
                    $this->payoutCommission($transaction, $businessProfileAccount, $partner, $partnerCommission, 'partner');
                    $repartitionBreakdown['partner'] = $partnerCommission;
                    $remainingRevenueForDistribution -= $partnerCommission;
                }
            }

            // Owner Commission (if applicable and different from operator)
            if ($businessProfile->owner_commission > 0) {
                $ownerCommission = round($revenueToDistribute * ($businessProfile->owner_commission / 100), 2);
                if ($ownerCommission > 0) {
                    // Assuming owner's share is recorded but might not be a separate payout
                    // depending on the business logic (e.g., if owner IS the operator)
                    $repartitionBreakdown['owner'] = $ownerCommission;
                    $remainingRevenueForDistribution -= $ownerCommission;
                }
            }

            // Operator's share is the activation fee plus their share of the distributed revenue
            $operatorShare = $remainingRevenueForDistribution + $activationFee;
            $repartitionBreakdown['operator'] = $operatorShare;

            $transaction->repartition_breakdown = $repartitionBreakdown;
            $transaction->save();

            \Illuminate\Support\Facades\Log::info("Transaction {$transaction->id}: Repartition breakdown saved: " . json_encode($repartitionBreakdown));
        });
    }

    /**
     * Handles the financial transaction for paying out a commission.
     *
     * @param Transaction $transaction
     * @param Account $payerAccount
     * @param \App\Models\Integrator|\App\Models\Partner $payee
     * @param float $amount
     * @param string $type
     * @return void
     */
    protected function payoutCommission(Transaction $transaction, Account $payerAccount, $payee, float $amount, string $type): void
    {
        $payeeAccount = $payee->account()->firstOrCreate([
            'accountable_type' => get_class($payee),
            'accountable_id' => $payee->id,
        ], ['balance' => 0, 'currency' => 'EUR']);

        RevenueShare::create([
            'transaction_id' => $transaction->id,
            'business_profile_id' => $payerAccount->accountable_id,
            'integrator_id' => $type === 'integrator' ? $payee->id : null,
            'partner_id' => $type === 'partner' ? $payee->id : null,
            'amount' => $amount,
            'type' => "{$type}_commission",
        ]);

        FinancialTransaction::create([
            'transaction_id' => $transaction->id,
            'payer_account_id' => $payerAccount->id,
            'payee_account_id' => $payeeAccount->id,
            'amount' => $amount,
            'type' => "{$type}_commission_payout",
            'description' => "Commission for {$type} {$payee->name} from transaction {$transaction->id}",
        ]);

        $payerAccount->decrement('balance', $amount);
        $payeeAccount->increment('balance', $amount);

        \Illuminate\Support\Facades\Log::info("Transaction {$transaction->id}: {$type} commission of {$amount} paid to Account {$payeeAccount->id}.");
    }
}