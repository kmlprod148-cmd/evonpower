<?php

namespace App\Http\Requests\ChargingSession;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\ChargingPoint;
use App\Models\PricingPlan;

class CreateChargingSessionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Implement authorization logic here if needed
        // For now, assuming any authenticated user can make a reservation
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
            'charging_point_id' => [
                'required',
                'integer',
                'exists:charging_points,id',
                function ($attribute, $value, $fail) {
                    $chargingPoint = ChargingPoint::find($value);
                    if (!$chargingPoint) {
                        $fail("The selected charging point is invalid.");
                        return;
                    }

                    // Check if the charging point has an active pricing plan
                    $pricingPlan = $chargingPoint->pricingPlan;
                    if (!$pricingPlan) {
                        $fail("The selected charging point does not have an active pricing plan.");
                        return;
                    }

                    // Store pricing plan for later use in after validation hook
                    $this->merge(['_pricing_plan' => $pricingPlan]);
                },
            ],
            'duration_minutes' => [
                'nullable',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) {
                    $pricingPlan = $this->input('_pricing_plan');

                    if ($pricingPlan && $pricingPlan->rate_type === 'by_minutes') {
                        if (is_null($value)) {
                            $fail("The duration in minutes is required for 'by minutes' pricing plans.");
                        } elseif ($pricingPlan->max_duration && $value > $pricingPlan->max_duration) {
                            $fail("The duration in minutes cannot exceed the maximum allowed by the pricing plan ({$pricingPlan->max_duration} minutes).");
                        }
                    }
                },
            ],
            // Add other relevant fields for reservation if necessary
            // e.g., 'start_time', 'end_time', 'user_id' (if not inferred from auth)
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // Any data manipulation before validation can go here
    }
}