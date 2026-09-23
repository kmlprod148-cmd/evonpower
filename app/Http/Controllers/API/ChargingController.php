<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\ApiController;
use App\Services\ChargingService;
use App\DTO\StartChargeDTO;
use Illuminate\Http\Request;

class ChargingController extends ApiController
{
    private ChargingService $chargingService;

    public function __construct(ChargingService $chargingService)
    {
        $this->chargingService = $chargingService;
    }

    public function startCharging(Request $request)
    {
    
        $validatedData = $request->validate([
            'charging_point_id' => [
                'required',
                'integer',
                'exists:charging_points,id'
            ],
            'connector_id' => [
                'required',
                'integer',
                'min:1'
            ],
            'auth_method' => [
                'nullable',
                'string',
                'in:rfid,app,credit_card,qr_code'
            ],
            'auth_id' => [
                'nullable',
                'string',
                'max:255',
                'required_with:auth_method'
            ],
            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id'
            ]
        ]);

        $dto = new \App\DTO\StartChargeDTO();
        $dto->fill($validatedData);

        try {
            $response = $this->chargingService->startCharging($dto);
            return $this->sendSuccess($response, 'Charging started successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

    public function stopCharging(Request $request)
    {
        $validatedData = $request->validate([
            'transaction_id' => [
                'required',
                'string',
                'exists:transactions,transaction_id'
            ],
            'stop_reason' => [
                'nullable',
                'string',
                'in:completed,user_stopped,error,timeout,payment_issue'
            ],
            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'meter_stop' => [
                'nullable',
                'numeric',
                'min:0',
                'required_if:stop_reason,completed'
            ]
        ]);

        $dto = new \App\DTO\StopChargeDTO();
        $dto->fill($validatedData);

        try {
            $response = $this->chargingService->stopCharging($dto);
            return $this->sendSuccess($response, 'Charging stopped successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }
}