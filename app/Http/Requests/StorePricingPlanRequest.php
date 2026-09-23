<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePricingPlanRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'rate_type' => 'required|in:minute,kwh',
            'amount' => 'required|numeric|min:0',
            'tva' => 'nullable|numeric|min:0',
            'priority' => 'nullable|integer|min:0',
            'additional_amount' => 'nullable|numeric|min:0',
            'tariff_type' => 'required|in:standard,peak,free',
            'additional_rate_type' => 'nullable|required_if:tariff_type,peak,free|in:minute,kwh',
            'additional_tva' => 'nullable|required_if:tariff_type,peak,free|numeric|min:0',
            'peak_day' => 'nullable|required_if:tariff_type,peak|string|max:255',
            'peak_hour' => 'nullable|required_if:tariff_type,peak|string|max:255',
            'additional_name' => 'nullable|required_if:tariff_type,free|string|max:255',
            'is_active' => 'boolean',
            'description' => 'nullable|string|max:1000',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'min_kwh' => 'nullable|numeric|min:0',
            'max_kwh' => 'nullable|numeric|min:0|gte:min_kwh',
            'min_duration_minutes' => 'nullable|integer|min:0',
            'max_duration_minutes' => 'nullable|integer|min:0|gte:min_duration_minutes',
            'applicable_times' => 'nullable|array',
            'applicable_times.*.start_time' => 'required_with:applicable_times|date_format:H:i',
            'applicable_times.*.end_time' => 'required_with:applicable_times|date_format:H:i|after:applicable_times.*.start_time',
            'applicable_days' => 'nullable|array',
            'applicable_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
        ];
    }
}