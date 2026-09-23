<?php

use Illuminate\Support\Facades\Route;
use App\Services\SteveService;
use Illuminate\Support\Facades\Log;

Route::get('/steve-test-direct', function () {
    try {
        $steveService = app(SteveService::class);
        
        $chargePoints = $steveService->getChargePoints();
        
        if ($chargePoints === null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch charge points',
                'config' => [
                    'url' => config('services.steve.url'),
                    'user_configured' => !empty(config('services.steve.user')),
                ]
            ], 500);
        }
        
        return response()->json([
            'status' => 'success',
            'message' => 'API Steve opérationnelle',
            'count' => count($chargePoints),
            'charge_points' => $chargePoints,
            'timestamp' => now()->toDateTimeString()
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
})->name('steve.test.direct');

