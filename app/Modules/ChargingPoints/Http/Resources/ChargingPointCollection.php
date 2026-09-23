<?php

namespace App\Modules\ChargingPoints\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ChargingPointCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data' => ChargingPointResource::collection($this->collection),
            'meta' => [
                'total' => $this->total(),
                'per_page' => $this->perPage(),
                'current_page' => $this->currentPage(),
                'last_page' => $this->lastPage(),
                'from' => $this->firstItem(),
                'to' => $this->lastItem(),
                'links' => [
                    'prev' => $this->previousPageUrl(),
                    'next' => $this->nextPageUrl()
                ]
            ]
        ];
    }
}