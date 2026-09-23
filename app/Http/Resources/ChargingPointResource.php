<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ChargingPointResource extends JsonResource
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
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'power_output' => $this->power_output,
            'connector_type' => $this->connector_type,
            'accessibility' => $this->accessibility,
            'pricing_plan' => new PricingPlanResource($this->whenLoaded('pricingPlan')),
            'group' => new GroupResource($this->whenLoaded('group')),
            'integrator' => new IntegratorResource($this->whenLoaded('integrator')),
            'partner' => new PartnerResource($this->whenLoaded('partner')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}