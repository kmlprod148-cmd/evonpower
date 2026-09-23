<?php

namespace App\Http\Resources\ChargingSession;

use Illuminate\Http\Resources\Json\JsonResource;

class ChargingSessionResource extends JsonResource
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
            'charging_point_id' => $this->charging_point_id,
            'user_id' => $this->user_id,
            'pricing_plan_id' => $this->pricing_plan_id,
            'energy_consumed' => $this->energy_consumed,
            'status' => $this->status,
            'reserved_until' => $this->reserved_until,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'pricing_plan_rate_type' => $this->whenLoaded('pricingPlan', function () {
                return $this->pricingPlan->rate_type;
            }),
            'pricing_plan_max_duration' => $this->whenLoaded('pricingPlan', function () {
                return $this->pricingPlan->max_duration;
            }),
        ];
    }
}