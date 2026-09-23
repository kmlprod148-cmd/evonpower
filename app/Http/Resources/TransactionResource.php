<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        // Format the duration
        $duration = $this->duration;
        $hours = floor($duration / 3600);
        $minutes = floor(($duration - ($hours * 3600)) / 60);
        $seconds = $duration - ($hours * 3600) - ($minutes * 60);
        $formattedDuration = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

        return [
            'transaction_id' => $this->transaction_id,
            'charging_point' => [
                'id' => $this->charging_point_id,
                'name' => $this->chargingPoint->name ?? 'Point de recharge',
            ],
            'connector' => [
                'id' => $this->connector_id,
                'type' => $this->connector->type ?? 'Type inconnu',
            ],
            'start_time' => $this->start_timestamp,
            'end_time' => $this->stop_timestamp,
            'duration' => $formattedDuration,
            'energy_delivered' => round($this->energy_delivered, 2) . ' kWh',
            'price_total' => $this->price_total,
            'currency' => $this->currency ?? 'EUR',
            'status' => $this->status,
        ];
    }
}