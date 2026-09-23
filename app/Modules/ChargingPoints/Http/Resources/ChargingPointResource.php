<?php

namespace App\Modules\ChargingPoints\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ChargingPointResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'serial_number' => $this->serial_number,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'manufacturer' => $this->manufacturer,
            'model' => $this->model,
            'location' => $this->location,
            'address' => $this->full_address,
            'coordinates' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude
            ],
            'installation_date' => $this->installation_date,
            'last_activity' => $this->last_activity,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'relationships' => [
                'integrator' => $this->whenLoaded('integrator'),
                'partner' => $this->whenLoaded('partner'),
                'group' => $this->whenLoaded('group'),
                'pricing_plan' => $this->whenLoaded('pricingPlan')
            ]
        ];
    }
}