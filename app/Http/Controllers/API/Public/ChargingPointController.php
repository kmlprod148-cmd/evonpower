<?php

namespace App\Http\Controllers\API\Public;

use App\Http\Controllers\Controller;
use App\Models\ChargingPoint;
use Illuminate\Http\Request;

class ChargingPointController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $chargingPoints = ChargingPoint::with(['pricingPlan', 'operator'])
                ->where('is_active', true)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $chargingPoints
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des bornes de recharge',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $chargingPoint = ChargingPoint::with(['pricingPlan', 'operator'])
                ->where('id', $id)
                ->where('is_active', true)
                ->first();

            if (!$chargingPoint) {
                return response()->json([
                    'success' => false,
                    'message' => 'Borne de recharge non trouvée'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $chargingPoint
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement de la borne de recharge',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
