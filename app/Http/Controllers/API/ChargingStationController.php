<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\ApiController;
use App\Http\Traits\ApiResponse; // Use the standardized ApiResponse trait
use App\Services\ChargingStationService;
use App\Http\Requests\ChargingStationStoreRequest;
use App\Http\Requests\ChargingStationUpdateRequest;
use App\Http\Resources\ChargingStationResource;

class ChargingStationController extends ApiController
{
    use ApiResponse; // Use the ApiResponse trait

    protected ChargingStationService $chargingStationService;

    public function __construct(ChargingStationService $chargingStationService)
    {
        $this->chargingStationService = $chargingStationService;
    }

    public function index()
    {
        $stations = $this->chargingStationService->getAllChargingStationsPaginated(10);

        // TODO: Use ChargingStationResource::collection() for consistent formatting
        return $this->sendSuccess($stations, 'Charging stations retrieved successfully');
    }

    public function store(ChargingStationStoreRequest $request)
    {
        $validatedData = $request->validated();

        $station = $this->chargingStationService->createChargingStation($validatedData);

        return $this->sendSuccess(new ChargingStationResource($station), 'Charging station created successfully', 201);
    }

    public function destroy(int $id)
    {
        $result = $this->chargingStationService->deleteChargingStation($id);

        if (!$result) {
            return $this->sendNotFound('Charging station not found');
        }
        
        return $this->sendSuccess(null, 'Charging station deleted successfully');
    }

    public function show(int $id)
    {
        $station = $this->chargingStationService->getChargingStationById($id);

        if (!$station) {
            return $this->sendNotFound('Charging station not found');
        }
        
        return $this->sendSuccess(new ChargingStationResource($station), 'Charging station retrieved successfully');
    }

    public function update(ChargingStationUpdateRequest $request, int $id)
    {
        $validatedData = $request->validated();

        $station = $this->chargingStationService->updateChargingStation($id, $validatedData);

        if (!$station) {
            return $this->sendNotFound('Charging station not found');
        }
        
        return $this->sendSuccess(new ChargingStationResource($station), 'Charging station updated successfully');
    }
}
