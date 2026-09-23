<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ChargingPoint;
use App\Services\SteveService;
use Illuminate\Http\JsonResponse;

class SteveController extends Controller
{
    public function __construct(private readonly SteveService $steve)
    {
    }

    public function test(int $chargingPointId): JsonResponse
    {
        $chargingPoint = ChargingPoint::findOrFail($chargingPointId);
        $result = $this->steve->testConnection($chargingPoint);
        return response()->json($result, ($result['success'] ?? false) ? 200 : 502);
    }
}


