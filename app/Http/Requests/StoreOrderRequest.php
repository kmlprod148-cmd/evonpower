<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Adjust authorization logic as needed
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'plan_id' => 'required|exists:pricing_plans,id',
            'charging_point_id' => 'required|exists:charging_points,id',
            'amount' => 'required|numeric|min:0',
            'commissions' => 'required|array',
            'commissions.admin' => 'required|numeric|min:0',
            'commissions.integrator' => 'required|numeric|min:0',
            'commissions.partner' => 'required|numeric|min:0',
            'price_energy' => 'nullable|numeric|min:0',
            'price_time' => 'nullable|numeric|min:0',
            'price_service' => 'nullable|numeric|min:0',
            'price_tax' => 'nullable|numeric|min:0',
            'currency' => 'required|string|max:3',
            'start_timestamp' => 'required|date',
            'stop_timestamp' => 'nullable|date|after_or_equal:start_timestamp',
            'meter_start' => 'required|numeric|min:0',
            'meter_stop' => 'nullable|numeric|min:0|gte:meter_start',
            'transaction_id' => 'nullable|string|max:255',
            'session_id' => 'nullable|string|max:255',
            'reason' => 'nullable|string|max:255',
            'energy_delivered' => 'nullable|numeric|min:0',
            'duration' => 'nullable|integer|min:0',
            'auth_method' => 'nullable|string|max:255',
            'auth_id' => 'nullable|string|max:255',
            'payment_status' => 'nullable|string|max:255',
            'payment_method' => 'nullable|string|max:255',
            'payment_id' => 'nullable|string|max:255',
            'business_profile_id' => 'nullable|exists:business_profiles,id',
            'commission_plan_id' => 'nullable|exists:commission_plans,id',
            'repartition_breakdown' => 'nullable|array',
            'commission_notes' => 'nullable|string',
        ];
    }
}