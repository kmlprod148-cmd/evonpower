<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    protected $chargingPoint;
    protected $pricingPlan;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Derive charging point and pricing plan from the route
        $this->chargingPoint = $this->route('chargingPoint');
        if ($this->chargingPoint) {
            $this->pricingPlan = $this->chargingPoint->getActivePricingPlan();

            if (!$this->pricingPlan) {
                $this->pricingPlan = \App\Models\PricingPlan::where('is_default', true)->first();
            }

            if ($this->pricingPlan) {
                $this->merge([
                    'charging_point_id' => $this->chargingPoint->id,
                    'pricing_plan_id'   => $this->pricingPlan->id,
                ]);
            } else {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'pricing_plan_id' => 'No active or default pricing plan found for this charging point.',
                ]);
            }
        }

        $reservationType = $this->input('reservation_type');
        $reservationValue = $this->input('reservation_value');

        if ($reservationType === 'minute' && $reservationValue !== null) {
            $this->merge(['duration_minutes' => $reservationValue]);
        } elseif ($reservationType === 'kwh' && $reservationValue !== null) {
            $this->merge(['energy_kwh' => $reservationValue]);
        }
        // The 'required_if' rules in the rules() method will handle the presence of duration_minutes or energy_kwh.
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $rules = [
            'charging_point_id' => 'required|exists:charging_points,id',
            'pricing_plan_id'   => 'required|exists:pricing_plans,id',
            'reservation_type'  => 'required|string|in:minute,kwh,both',
            'reservation_value' => 'required|numeric|min:0.1',
            'duration_minutes'  => 'nullable|required_if:reservation_type,minute,both|integer|min:1',
            'energy_kwh'        => 'nullable|required_if:reservation_type,kwh,both|numeric|min:0.1',
            'start_time'        => 'nullable|date_format:H:i',
            'guest_email'       => 'nullable|email',
            'guest_phone'       => 'nullable|string',
            'payment_type'      => 'required|string|in:cmi,offline',
        ];

        if ($this->pricingPlan && $this->pricingPlan->max_duration) {
            $rules['duration_minutes'] .= '|max:' . $this->pricingPlan->max_duration;
        }

        if ($this->pricingPlan && $this->pricingPlan->max_duration && $this->chargingPoint && $this->chargingPoint->power_output) {
            $maxEnergy = $this->chargingPoint->power_output * ($this->pricingPlan->max_duration / 60);
            $rules['energy_kwh'] .= '|max:' . $maxEnergy;
        }

        return $rules;
    }
}