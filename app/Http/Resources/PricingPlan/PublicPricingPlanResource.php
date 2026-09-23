<?php

namespace App\Http\Resources\PricingPlan;

use Illuminate\Http\Resources\Json\JsonResource;

class PublicPricingPlanResource extends JsonResource
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
        ];
    }
}