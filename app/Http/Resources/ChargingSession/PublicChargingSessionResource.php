<?php

namespace App\Http\Resources\ChargingSession;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ChargingPoint\PublicChargingPointResource;
use App\Http\Resources\Connector\PublicConnectorResource;
use App\Http\Resources\PricingPlan\PublicPricingPlanResource;

class PublicChargingSessionResource extends JsonResource
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
            'session_id' => $this->session_id,
            'charging_point' => PublicChargingPointResource::make($this->whenLoaded('chargingPoint')),
            'connector' => PublicConnectorResource::make($this->whenLoaded('connector')),
            'pricing_plan' => PublicPricingPlanResource::make($this->whenLoaded('pricingPlan')),
            'user_id' => $this->user_id, // Consider if user_id should be public
            'start_time' => $this->start_time->toIso8601String(),
            'end_time' => $this->end_time ? $this->end_time->toIso8601String() : null,
            'status' => $this->status,
            'energy_delivered_kwh' => $this->energy_delivered_kwh,
            'cost' => $this->cost,
            'currency' => $this->currency,
            'meter_start' => $this->meter_start,
            'meter_end' => $this->meter_end,
            'duration_minutes' => $this->duration_minutes,
            'payment_status' => $this->payment_status,
            'payment_intent_id' => $this->payment_intent_id,
            'meta_data' => $this->meta_data,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}