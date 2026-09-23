<?php

namespace App\Http\Requests\Pricing;

use Illuminate\Foundation\Http\FormRequest;

class StorePlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'rate_type' => ['required', 'in:fixed,time,energy,kwh,minute'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'base_rate' => ['required', 'numeric', 'min:0'],
            'fixed_price' => ['nullable', 'numeric', 'min:0'],
            'price_per_kwh' => ['nullable', 'numeric', 'min:0'],
            'price_per_minute' => ['nullable', 'numeric', 'min:0'],
            'activation_fee' => ['nullable', 'numeric', 'min:0'],
            'weekend_price' => ['nullable', 'numeric', 'min:0'],
            'night_price' => ['nullable', 'numeric', 'min:0'],
            'has_weekend_pricing' => ['boolean'],
            'has_night_pricing' => ['boolean'],
            'night_start_time' => ['nullable', 'date_format:H:i'],
            'night_end_time' => ['nullable', 'date_format:H:i'],
            'vat_rate_id' => ['required', 'exists:vat_rates,id'],
            'priority' => ['nullable', 'integer', 'min:0'],
            'max_duration' => ['required', 'integer', 'min:20', 'max:120'],
            'is_active' => ['boolean'],
            'currency' => ['nullable', 'string', 'size:3'],
            'billing_interval' => ['nullable', 'in:session,hourly,daily,weekly,monthly,quarterly,semi-annually,annually,yearly'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'mobile_theme_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'additional_rates' => ['nullable', 'array'],
            'additional_rates.*.name' => ['required_with:additional_rates', 'string', 'max:255'],
            'additional_rates.*.price' => ['required_with:additional_rates', 'numeric', 'min:0'],
            'additional_rates.*.rate_type' => ['nullable', 'in:fixed,time,energy,kwh,minute'],
            'additional_rates.*.vat_rate_id' => ['required_with:additional_rates', 'exists:vat_rates,id'],
            // Enhanced pricing conditions
            'additional_rates.*.condition_type' => ['nullable', 'in:time,day,power,duration,all'],
            'additional_rates.*.time_start' => ['nullable', 'date_format:H:i'],
            'additional_rates.*.time_end' => ['nullable', 'date_format:H:i'],
            'additional_rates.*.days' => ['nullable', 'array'],
            'additional_rates.*.days.*' => ['integer', 'between:1,7'],
            'additional_rates.*.min_power' => ['nullable', 'numeric', 'min:0'],
            'additional_rates.*.max_power' => ['nullable', 'numeric', 'min:0'],
            'additional_rates.*.min_duration' => ['nullable', 'integer', 'min:0'],
            'additional_rates.*.max_duration' => ['nullable', 'integer', 'min:0'],
            'additional_rates.*.customer_segment' => ['nullable', 'string', 'max:100'],
            'additional_rates.*.location_zone' => ['nullable', 'string', 'max:100'],
            'additional_rates.*.quantity_min' => ['nullable', 'integer', 'min:1'],
            'additional_rates.*.quantity_max' => ['nullable', 'integer', 'min:1'],
            'additional_rates.*.is_percentage' => ['boolean'],
            'additional_rates.*.percentage_value' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'additional_rates.*.apply_type' => ['nullable', 'in:markup,discount,replace,add'],
            'additional_rates.*.applicable_dates' => ['nullable', 'array'],
            'additional_rates.*.applicable_dates.*' => ['date'],
            'additional_rates.*.excluded_dates' => ['nullable', 'array'],
            'additional_rates.*.excluded_dates.*' => ['date'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $rateType = $this->normalizeRateType($this->input('rate_type'));
        $price = $this->input('price');

        if ($price === null || $price === '') {
            $price = $this->input('base_rate');
        }

        $mainPrice = is_numeric($price) ? (float) $price : null;
        $merge = [
            'rate_type' => $rateType,
            'price' => $mainPrice,
            'base_rate' => $mainPrice,
            'fixed_price' => $rateType === 'fixed' ? $mainPrice : 0,
            'price_per_kwh' => $rateType === 'energy' ? $mainPrice : 0,
            'price_per_minute' => $rateType === 'time' ? $mainPrice : 0,
            'is_active' => $this->has('is_active') ? $this->boolean('is_active') : true,
            'has_weekend_pricing' => $this->boolean('has_weekend_pricing'),
            'has_night_pricing' => $this->boolean('has_night_pricing'),
            'priority' => $this->input('priority', 0),
            'currency' => $this->input('currency', 'EUR'),
            'billing_interval' => $this->input('billing_interval', 'session'),
            'activation_fee' => $this->input('activation_fee', 0),
            'weekend_price' => $this->input('weekend_price', 0),
            'night_price' => $this->input('night_price', 0),
            'night_start_time' => $this->input('night_start_time', '22:00'),
            'night_end_time' => $this->input('night_end_time', '06:00'),
        ];

        if ($this->exists('additional_rates')) {
            $merge['additional_rates'] = collect($this->input('additional_rates', []))
                ->filter(fn ($rate) => is_array($rate) && (filled($rate['name'] ?? null) || filled($rate['price'] ?? null)))
                ->map(function (array $rate): array {
                    $rate['rate_type'] = $this->normalizeRateType($rate['rate_type'] ?? 'fixed');
                    $rate['vat_rate_id'] = $rate['vat_rate_id'] ?? $this->input('vat_rate_id');
                    $rate['condition_type'] = $rate['condition_type'] ?? 'all';

                    return $rate;
                })
                ->values()
                ->all();
        }

        $this->merge($merge);
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'max_duration.required' => 'La durée maximale est obligatoire.',
            'max_duration.integer' => 'La durée maximale doit être un nombre entier.',
            'max_duration.min' => 'La durée maximale doit être d\'au moins 20 minutes.',
            'max_duration.max' => 'La durée maximale ne peut pas dépasser 120 minutes.',
        ];
    }

    private function normalizeRateType(?string $rateType): ?string
    {
        return match ($rateType) {
            'minute' => 'time',
            'kwh' => 'energy',
            default => $rateType,
        };
    }
}
