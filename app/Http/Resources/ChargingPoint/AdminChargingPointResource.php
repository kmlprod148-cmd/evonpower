<?php

namespace App\Http\Resources\ChargingPoint;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminChargingPointResource extends JsonResource
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
            'serial_number' => $this->serial_number,
            'status' => $this->status,
            'location' => [
                'address' => $this->address,
                'city' => $this->city,
                'postal_code' => $this->postal_code,
                'country' => $this->country,
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],
            'manufacturer' => $this->manufacturer,
            'model' => $this->model,
            'firmware_version' => $this->firmware_version,
            'communication_protocol' => $this->communication_protocol,
            'installation_date' => $this->installation_date ? $this->installation_date->format('Y-m-d') : null,
            'last_maintenance_date' => $this->last_maintenance_date ? $this->last_maintenance_date->format('Y-m-d') : null,
            'next_maintenance_date' => $this->next_maintenance_date ? $this->next_maintenance_date->format('Y-m-d') : null,
            'power_output' => $this->power_output,
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
            'pricing_plan' => $this->whenLoaded('pricingPlan', function() {
                return [
                    'id' => $this->pricingPlan->id,
                    'name' => $this->pricingPlan->name,
                ];
            }),
            'qr_code' => [
                'path' => $this->qr_code_path,
                'generated_at' => $this->qr_code_generated_at,
            ],
            'public_access' => $this->public_access,
            'is_active' => $this->is_active,
            'access_type' => $this->access_type,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}