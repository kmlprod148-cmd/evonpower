<?php

namespace App\Http\Controllers\API\Public;

use App\Http\Controllers\Controller;
use App\Models\ChargingPoint;
use Illuminate\Http\Request;

class PublicChargingOfferController extends Controller
{
    /**
     * Get charging offer for a specific charging point.
     */
    public function getOffer($id)
    {
        $chargingPoint = ChargingPoint::with(['station', 'pricingPlan'])
            ->where('id', $id)
            ->where('is_available', true)
            ->first();

        if (!$chargingPoint) {
            return response()->json([
                'success' => false,
                'message' => 'Charging point not found or not available'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'charging_point' => $chargingPoint,
                'offer' => [
                    'available' => $chargingPoint->is_available,
                    'pricing_plan' => $chargingPoint->pricingPlan,
                    'station' => $chargingPoint->station
                ]
            ]
        ]);
    }
}
