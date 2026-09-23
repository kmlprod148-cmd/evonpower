<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PricingPlanResource extends JsonResource
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
            'rate_type' => $this->rate_type,
            'price_per_kwh' => $this->price_per_kwh,
            'price_per_minute' => $this->price_per_minute,
            'fixed_start_price' => $this->fixed_start_price,
            'tva_rate' => $this->tva_rate,
            'description' => $this->description,
            'currency' => $this->currency ?? 'EUR',
            'is_active' => $this->is_active,
            'is_public' => $this->is_public,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}