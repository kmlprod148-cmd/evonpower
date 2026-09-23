<?php

namespace App\Http\Resources\ChargingPoint;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Connector\PublicConnectorResource;

class PublicChargingPointResource extends JsonResource
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
            'access_type' => $this->access_type,
            'accessibility' => $this->accessibility,
            'opening_hours' => $this->opening_hours,
            'last_updated' => $this->updated_at->toIso8601String(),
        ];
    }
}