<?php

namespace App\Http\Resources\PricingPlan;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminPricingPlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price_per_kwh' => $this->price_per_kwh,
            'price_per_minute' => $this->price_per_minute,
            'currency' => $this->currency,
            'tax_rate' => $this->tax_rate,
            'is_active' => $this->is_active,
            'is_default' => $this->is_default,
            'priority' => $this->priority,
            'applies_to_type' => $this->applies_to_type,
            'applies_to_id' => $this->applies_to_id,
            'integrator' => $this->whenLoaded('integrator', function() {
                return [
                    'id' => $this->integrator->id,
                    'name' => $this->integrator->name,
                ];
            }),
            'partner' => $this->whenLoaded('partner', function() {
                return [
                    'id' => $this->partner->id,
                    'name' => $this->partner->name,
                ];
            }),
            'group' => $this->whenLoaded('group', function() {
                return [
                    'id' => $this->group->id,
                    'name' => $this->group->name,
                ];
            }),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}