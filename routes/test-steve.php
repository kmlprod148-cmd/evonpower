<?php

use Illuminate\Support\Facades\Route;
use App\Services\SteveService;

// Route de test pour getChargePoints (SANS authentification)
Route::get('/test-steve-get-charge-points', function () {
    $steveService = app(SteveService::class);
    
    try {
        $chargePoints = $steveService->getChargePoints();
        
        return response()->json([
            'success' => true,
            'chargePoints' => $chargePoints,
            'count' => is_array($chargePoints) ? count($chargePoints) : 0,
            'type' => gettype($chargePoints),
            'message' => 'Données récupérées depuis l\'API Steve'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});

// Route de test pour afficher la vue (SANS authentification)
Route::get('/test-steve-view', function () {
    try {
        $steveService = app(SteveService::class);
        $chargePoints = $steveService->getChargePoints();
        
        if ($chargePoints === null) {
            return view('steve-charging-points.index', [
                'chargePoints' => [],
                'error' => 'Impossible de récupérer les bornes depuis l\'API Steve. Veuillez vérifier la configuration de l\'API.'
            ]);
        }
        
        return view('steve-charging-points.index', [
            'chargePoints' => $chargePoints
        ]);
    } catch (\Exception $e) {
        return view('steve-charging-points.index', [
            'chargePoints' => [],
            'error' => 'Erreur: ' . $e->getMessage()
        ]);
    }
});

