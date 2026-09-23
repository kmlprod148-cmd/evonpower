<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BusinessProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_public' => ['boolean'],
            'is_active' => ['boolean'],
            'target_audience' => ['required', 'array'],
            'target_audience.*' => ['in:integrator,operator'],
            
            'partner_id' => ['nullable', 'integer'],
            'integrator_id' => ['nullable', 'integer'],
            
            'subscription_period' => ['nullable', Rule::in(['monthly', 'quarterly', 'yearly'])],
            'base_fee_amount' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'terminal_fee_amount' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'terminal_count' => ['nullable', 'integer', 'min:1'],
            
            'transaction_fee_type' => ['nullable', 'array'],
            'transaction_fee_type.*' => ['in:fixed,percentage'],
            'transaction_fee_fixed_amount' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'transaction_fee_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'charge_fee_fixed_amount' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'charge_fee_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $data = $validator->getData();

            // Validation for transaction fees
            $hasTransactionFeeType = !empty($data['transaction_fee_type']) && is_array($data['transaction_fee_type']);
            $hasFixedFee = $hasTransactionFeeType && in_array('fixed', $data['transaction_fee_type']);
            $hasPercentageFee = $hasTransactionFeeType && in_array('percentage', $data['transaction_fee_type']);

            if ($hasFixedFee && (empty($data['transaction_fee_fixed_amount']) || floatval($data['transaction_fee_fixed_amount']) <= 0)) {
                $validator->errors()->add(
                    'transaction_fee_fixed_amount',
                    'Le montant fixe des frais de transaction doit être supérieur à 0 quand le type fixe est sélectionné.'
                );
            }

            if ($hasPercentageFee && (empty($data['transaction_fee_percentage']) || floatval($data['transaction_fee_percentage']) <= 0)) {
                $validator->errors()->add(
                    'transaction_fee_percentage',
                    'Le pourcentage des frais de transaction doit être supérieur à 0 quand le type pourcentage est sélectionné.'
                );
            }

            // Validation for subscription fees
            $hasSubscriptionPeriod = !empty($data['subscription_period']);
            $hasBaseFee = !empty($data['base_fee_amount']) && floatval($data['base_fee_amount']) > 0;
            $hasTerminalFee = !empty($data['terminal_fee_amount']) && floatval($data['terminal_fee_amount']) > 0;

            if ($hasSubscriptionPeriod && !$hasBaseFee && !$hasTerminalFee) {
                $validator->errors()->add(
                    'base_fee_amount',
                    'Vous devez définir au moins un montant pour les frais d\'abonnement.'
                );
            }

            if (!$hasSubscriptionPeriod && ($hasBaseFee || $hasTerminalFee)) {
                $validator->errors()->add(
                    'subscription_period',
                    'Vous devez sélectionner une période de facturation pour les frais d\'abonnement.'
                );
            }

            // Ensure at least one pricing configuration
            $hasSubscription = $hasSubscriptionPeriod && ($hasBaseFee || $hasTerminalFee);
            $hasTransaction = ($hasFixedFee && !empty($data['transaction_fee_fixed_amount']) && floatval($data['transaction_fee_fixed_amount']) > 0) ||
                              ($hasPercentageFee && !empty($data['transaction_fee_percentage']) && floatval($data['transaction_fee_percentage']) > 0);
            $hasCharge = (!empty($data['charge_fee_fixed_amount']) && floatval($data['charge_fee_fixed_amount']) > 0) ||
                        (!empty($data['charge_fee_percentage']) && floatval($data['charge_fee_percentage']) > 0);

            if (!$hasSubscription && !$hasTransaction && !$hasCharge) {
                $validator->errors()->add(
                    'base_fee_amount',
                    'Vous devez définir au moins une configuration tarifaire.'
                );
            }
        });
    }

    public function attributes()
    {
        return [
            'name' => 'nom du profil',
            'description' => 'description',
            'is_public' => 'visibilité publique',
            'is_active' => 'statut actif',
            'target_audience' => 'audience',
            'partner_id' => 'partenaire',
            'integrator_id' => 'intégrateur',
            'subscription_period' => 'période de facturation',
            'base_fee_amount' => 'frais forfaitaires',
            'terminal_fee_amount' => 'frais par borne',
            'transaction_fee_type' => 'type de frais de transaction',
            'transaction_fee_fixed_amount' => 'montant fixe des frais de transaction',
            'transaction_fee_percentage' => 'pourcentage des frais de transaction',
            'charge_fee_fixed_amount' => 'montant fixe des frais de recharge',
            'charge_fee_percentage' => 'pourcentage des frais de recharge',
        ];
    }
}