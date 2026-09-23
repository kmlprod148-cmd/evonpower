<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChargingPointViewController;
use App\Http\Controllers\UnifiedChargingPointController;
use App\Http\Controllers\ChargingPointEnhancedController;

// Charging Points Routes - Consolidated
Route::prefix('charging-points')->name('charging-points.')->group(function () {
    // IMPORTANT: Specific routes must come BEFORE parameterized routes
    Route::get('/', [UnifiedChargingPointController::class, 'index'])->name('index')->middleware(\App\Http\Middleware\AdminRedirectMiddleware::class);
    Route::get('/create', [ChargingPointViewController::class, 'createStep1'])->name('create')->middleware(\App\Http\Middleware\AdminRedirectMiddleware::class);
    
    // Alternative create route using the same logic as simple-test
    Route::get('/create-alt', function () {
        return view('charging-points.create-step1-enhanced', [
            'operators' => collect([]),
            'businessProfiles' => collect([]),
            'groups' => collect([]),
            'pricingPlans' => collect([]),
        ]);
    })->name('create.alt');
    
    // Ultra-simple create route that bypasses all dependencies
    Route::get('/create-simple', function () {
        return '<h1>Test de création de point de charge</h1><p>Cette route fonctionne !</p>';
    })->name('create.simple');
    
    // Test route without any middleware
    Route::get('/create-test', function () {
        return response()->json([
            'message' => 'Route de test sans middleware',
            'user_id' => auth()->id(),
            'authenticated' => auth()->check(),
            'timestamp' => now()->toDateTimeString()
        ]);
    })->name('create.test');
    
    // Fallback route in case the main create route fails
    Route::get('/create-fallback', function () {
        return redirect()->route('charging-points.create.simple.test');
    })->name('create.fallback');
    
    // Multi-step form routes
    Route::prefix('create')->name('create.')->group(function () {
        // Step 1 routes
        Route::get('/step1', [ChargingPointViewController::class, 'createStep1'])->name('step1');
        Route::post('/step1', [ChargingPointViewController::class, 'storeStep1'])->name('store.step1');
        
        // Step 2 routes
        Route::get('/step2', [ChargingPointViewController::class, 'createStep2'])->name('step2');
        Route::post('/step2', [ChargingPointViewController::class, 'storeStep2'])->name('store.step2');
        
        // Step 3 routes
        Route::get('/step3', [ChargingPointViewController::class, 'createStep3'])->name('step3');
        Route::post('/step3', [ChargingPointViewController::class, 'storeStep3'])->name('store.step3');
        
        // Confirmation and final store
        Route::get('/confirm', [ChargingPointViewController::class, 'confirm'])->name('confirm');
        Route::post('/store', [ChargingPointViewController::class, 'store'])->name('store.final');
        Route::get('/success/{charging_point}', [ChargingPointViewController::class, 'success'])->name('success');
        
        // Debug routes (temporary)
        Route::get('/debug-session', [ChargingPointViewController::class, 'debugSession'])->name('debug.session');
        Route::get('/debug-database', [ChargingPointViewController::class, 'debugDatabase'])->name('debug.database');
        Route::get('/test-create', [ChargingPointViewController::class, 'testCreate'])->name('test.create');
        Route::get('/test-create-session', [ChargingPointViewController::class, 'testCreateWithSessionData'])->name('test.create.session');
        
        // Test route for debugging
        Route::get('/test-route', function () {
            $user = auth()->user();
            return response()->json([
                'message' => 'Test route works!',
                'user_id' => $user->id,
                'user_roles' => $user->getRoleNames(),
                'has_create_permission' => $user->can('create_charging_points'),
                'integrator_id' => $user->integrator_id ?? 'N/A',
                'partner_id' => $user->partner_id ?? 'N/A',
            ]);
        })->name('test.route');
        
        // Simple test route that returns a basic view
        Route::get('/simple-test', function () {
            return view('charging-points.create-step1-enhanced', [
                'operators' => collect([]),
                'businessProfiles' => collect([]),
                'groups' => collect([]),
                'pricingPlans' => collect([]),
            ]);
        })->name('simple.test');
    });

    // Interface améliorée (route séparée) - DOIT être avant les routes paramétrées
    Route::get('/enhanced/{charging_point}', [ChargingPointEnhancedController::class, 'showEnhanced'])->name('enhanced');
    
    // Routes pour l'interface améliorée
    Route::post('/enhanced/{charging_point}/generate-qr', [ChargingPointEnhancedController::class, 'generateQRCode'])
        ->name('enhanced.generate-qr')
        ->middleware(\App\Http\Middleware\UnifiedPermissionMiddleware::class . ':view_charging_points');
        
    Route::post('/enhanced/{charging_point}/calculate-estimation', [ChargingPointEnhancedController::class, 'calculateEstimation'])
        ->name('enhanced.calculate-estimation');
        
    Route::post('/enhanced/{charging_point}/connect-steve', [ChargingPointEnhancedController::class, 'connectToSteVe'])
        ->name('enhanced.connect-steve');
        
    Route::post('/enhanced/{charging_point}/execute-steve-action', [ChargingPointEnhancedController::class, 'executeSteVeAction'])
        ->name('enhanced.execute-steve-action');
    
    Route::post('/enhanced/{charging_point}/get-active-transaction', [ChargingPointEnhancedController::class, 'getActiveTransaction'])
        ->name('enhanced.get-active-transaction');
    
    // Parameterized routes must come AFTER specific routes
    Route::get('/{charging_point}', [UnifiedChargingPointController::class, 'show'])
        ->name('show')
        ->middleware(\App\Http\Middleware\UnifiedPermissionMiddleware::class . ':view_charging_points');
    Route::get('/{charging_point}/edit', [UnifiedChargingPointController::class, 'edit'])
        ->name('edit')
        ->middleware(\App\Http\Middleware\UnifiedPermissionMiddleware::class . ':edit_charging_points');
    Route::put('/{charging_point}', [UnifiedChargingPointController::class, 'update'])->name('update');
    Route::patch('/{charging_point}', [UnifiedChargingPointController::class, 'update'])->name('update-patch');
    Route::delete('/{charging_point}', [UnifiedChargingPointController::class, 'destroy'])->name('destroy');


    // Charging Point Actions
    Route::post('/{charging_point}/toggle-status', [UnifiedChargingPointController::class, 'toggleStatus'])
        ->name('toggle-status')
        ->middleware(\App\Http\Middleware\UnifiedPermissionMiddleware::class . ':edit_charging_points');
        
    Route::post('/{charging_point}/assign-pricing-plan', [UnifiedChargingPointController::class, 'assignPricingPlan'])
        ->name('assign-pricing-plan')
        ->middleware(\App\Http\Middleware\UnifiedPermissionMiddleware::class . ':edit_charging_points');
        
    Route::post('/{charging_point}/start-charging', [UnifiedChargingPointController::class, 'startCharging'])
        ->name('start-charging');
        
    Route::post('/{charging_point}/stop-charging', [UnifiedChargingPointController::class, 'stopCharging'])
        ->name('stop-charging');
        
    Route::post('/{charging_point}/generate-qr-code', [UnifiedChargingPointController::class, 'generateQrCode'])
        ->name('generate-qr-code')
        ->middleware(\App\Http\Middleware\UnifiedPermissionMiddleware::class . ':view_charging_points');
        
    Route::get('/{id}/business-profiles', [ChargingPointViewController::class, 'getBusinessProfiles'])
        ->name('business-profiles');
        
    Route::post('/{charging_point}/connect-steve', [UnifiedChargingPointController::class, 'connectToSteVe'])
        ->name('connect-steve')
        ->middleware(\App\Http\Middleware\UnifiedPermissionMiddleware::class . ':edit_charging_points');
        
    Route::post('/{charging_point}/test-steve-connection', [UnifiedChargingPointController::class, 'testSteVeConnection'])
        ->name('test-steve-connection')
        ->middleware(\App\Http\Middleware\UnifiedPermissionMiddleware::class . ':edit_charging_points');
        
    Route::post('/connect-by-id/{charging_point_id}', [UnifiedChargingPointController::class, 'connectById'])
        ->name('connect-by-id')
        ->middleware(\App\Http\Middleware\UnifiedPermissionMiddleware::class . ':edit_charging_points');
        
    Route::post('/{id}/offer/start', [UnifiedChargingPointController::class, 'startOffer'])
        ->name('offer.start');
        
    Route::get('/{charging_point}/qr-code', [UnifiedChargingPointController::class, 'qrCode'])
        ->name('qr-code');
        
    // Actions rapides basées sur SteVe API
    Route::post('/{charging_point}/start-charging', [\App\Http\Controllers\ChargingPointActionsController::class, 'startCharging'])
        ->name('start-charging');
    
    // Action distante "Start Charging Session" avec SteveService
    Route::post('/{charging_point}/action/start', [\App\Http\Controllers\ChargingPointActionController::class, 'start'])
        ->name('action.start');
    
    // Action distante "Stop Charging Session" avec SteveService
    Route::post('/{charging_point}/action/stop', [\App\Http\Controllers\ChargingPointActionController::class, 'stop'])
        ->name('action.stop');
    
    // Action distante "Reset Charging Point" avec SteveService
    Route::post('/{charging_point}/action/reset', [\App\Http\Controllers\ChargingPointActionController::class, 'reset'])
        ->name('action.reset');
    
    // Action distante "Update Configuration" avec SteveService
    Route::post('/{charging_point}/action/config', [\App\Http\Controllers\ChargingPointActionController::class, 'updateConfig'])
        ->name('action.updateConfig');
    
    // Action distante "Unlock Connector" avec SteveService
    Route::post('/{charging_point}/action/unlock', [\App\Http\Controllers\ChargingPointActionController::class, 'unlock'])
        ->name('action.unlock');
    
    // Action distante "Get Diagnostics" avec SteveService
    Route::post('/{charging_point}/action/diagnostic', [\App\Http\Controllers\ChargingPointActionController::class, 'diagnostic'])
        ->name('action.diagnostic');
    
    // Action distante "Get Logs" avec SteveService
    Route::post('/{charging_point}/action/logs', [\App\Http\Controllers\ChargingPointActionController::class, 'logs'])
        ->name('action.logs');
        
    Route::post('/{charging_point}/stop-charging', [\App\Http\Controllers\ChargingPointActionsController::class, 'stopCharging'])
        ->name('stop-charging');
        
    Route::get('/{charging_point}/status', [\App\Http\Controllers\ChargingPointActionsController::class, 'getStatus'])
        ->name('status');
        
    Route::get('/{charging_point}/sessions', [\App\Http\Controllers\ChargingPointActionsController::class, 'getActiveSessions'])
        ->name('sessions');
        
    Route::post('/{charging_point}/reservations', [\App\Http\Controllers\ChargingPointActionsController::class, 'createReservation'])
        ->name('create-reservation');
        
    Route::delete('/reservations/{reservation_id}', [\App\Http\Controllers\ChargingPointActionsController::class, 'cancelReservation'])
        ->name('cancel-reservation');
        
    Route::get('/test-steve-connection', [\App\Http\Controllers\ChargingPointActionsController::class, 'testConnection'])
        ->name('test-steve-connection-global');
        
    // Page de réservation
    Route::get('/reservation', function () {
        $pricingPlans = \App\Models\PricingPlan::where('is_active', true)->get();
        return view('charging-points.reservation', compact('pricingPlans'));
    })->name('reservation');
    
    // Connexion par ID
    Route::post('/connect-by-id/{id}', [\App\Http\Controllers\ChargingPointActionsController::class, 'connectById'])
        ->name('connect-by-id-actions');
        
    Route::post('/{charging_point}/disconnect', [\App\Http\Controllers\ChargingPointActionsController::class, 'disconnect'])
        ->name('disconnect');
        
    Route::get('/{charging_point}/connection-status', [\App\Http\Controllers\ChargingPointActionsController::class, 'getConnectionStatus'])
        ->name('connection-status');
        
    Route::post('/{charging_point}/test-connectivity', [\App\Http\Controllers\ChargingPointActionsController::class, 'testConnectivity'])
        ->name('test-connectivity');
        
    // Routes pour les fonctionnalités améliorées
    Route::post('/{charging_point}/connect-steve', [UnifiedChargingPointController::class, 'connectToSteVe'])
        ->name('connect-steve');
        
    Route::post('/{charging_point}/execute-steve-action', [UnifiedChargingPointController::class, 'executeSteVeAction'])
        ->name('execute-steve-action');
        
    Route::post('/{charging_point}/test-connectivity-enhanced', [UnifiedChargingPointController::class, 'testConnectivity'])
        ->name('test-connectivity-enhanced');
        
});
