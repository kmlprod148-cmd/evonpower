<?php

namespace App\Http\Requests\ChargingPoint;

use Illuminate\Foundation\Http\FormRequest;

class AssignPricingPlanRequest extends FormRequest
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
            'pricing_plan_id' => 'required|exists:pricing_plans,id',
            'apply_to_connectors' => 'boolean',
        ];
    }
}