<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransactionStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Adjust based on your authorization needs
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Required fields for starting a transaction
            'transaction_id' => 'required|string|max:255|unique:transactions,transaction_id',
            'charging_point_id' => 'required|integer|exists:charging_points,id',
            'connector_id' => 'required|integer|min:1',
            'user_id' => 'nullable|integer|exists:users,id',

            // Timestamp fields
            'start_timestamp' => 'required|date',
            'stop_timestamp' => 'nullable|date|after:start_timestamp',

            // Meter readings
            'meter_start' => 'required|numeric|min:0',
            'meter_stop' => 'nullable|numeric|min:0|gte:meter_start',

            // Energy and pricing
            'energy_delivered' => 'nullable|numeric|min:0|decimal:4',
            'price_energy' => 'nullable|numeric|min:0|decimal:4',
            'price_time' => 'nullable|numeric|min:0|decimal:4',
            'price_tax' => 'nullable|numeric|min:0|decimal:4',
            'price_total' => 'nullable|numeric|min:0|decimal:4',
            'currency' => 'required|string|in:EUR,USD,GBP,CHF|size:3',

            // Status fields
            'status' => 'required|string|in:in_progress,completed,failed,cancelled',
            'payment_status' => 'nullable|string|in:pending,paid,failed,refunded',

            // Authentication
            'auth_method' => 'nullable|string|in:rfid,app,credit_card,qr_code',
            'auth_id' => 'nullable|string|max:255|required_with:auth_method',

            // Additional fields
            'stop_reason' => 'nullable|string|in:completed,user_stopped,error,timeout,payment_issue',
            'error_code' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:1000',

            // Commission and pricing plan
            'commission_plan_id' => 'nullable|exists:commission_plans,id',
            'pricing_plan_id' => 'nullable|exists:pricing_plans,id',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'transaction_id' => 'identifiant de transaction',
            'charging_point_id' => 'borne de recharge',
            'connector_id' => 'connecteur',
            'user_id' => 'utilisateur',
            'start_timestamp' => 'heure de début',
            'stop_timestamp' => 'heure de fin',
            'meter_start' => 'compteur de départ',
            'meter_stop' => 'compteur d\'arrêt',
            'energy_delivered' => 'énergie délivrée',
            'price_energy' => 'prix de l\'énergie',
            'price_time' => 'prix du temps',
            'price_tax' => 'taxe',
            'price_total' => 'prix total',
            'currency' => 'devise',
            'status' => 'statut',
            'payment_status' => 'statut de paiement',
            'auth_method' => 'méthode d\'authentification',
            'auth_id' => 'identifiant d\'authentification',
            'stop_reason' => 'raison d\'arrêt',
            'error_code' => 'code d\'erreur',
            'notes' => 'notes',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'transaction_id.unique' => 'Cet identifiant de transaction existe déjà.',
            'stop_timestamp.after' => 'L\'heure de fin doit être après l\'heure de début.',
            'meter_stop.gte' => 'Le compteur d\'arrêt doit être supérieur ou égal au compteur de départ.',
            'auth_id.required_with' => 'L\'identifiant d\'authentification est requis avec la méthode d\'authentification.',
            'currency.size' => 'Le code de devise doit contenir exactement 3 caractères.',
            'currency.required' => 'La devise est obligatoire.',
            'energy_delivered.decimal' => 'L\'énergie délivrée doit avoir au maximum :decimal décimales.',
            'price_energy.decimal' => 'Le prix de l\'énergie doit avoir au maximum :decimal décimales.',
            'price_time.decimal' => 'Le prix du temps doit avoir au maximum :decimal décimales.',
            'price_tax.decimal' => 'La taxe doit avoir au maximum :decimal décimales.',
            'price_total.decimal' => 'Le prix total doit avoir au maximum :decimal décimales.',
        ];
    }
}