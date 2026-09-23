<?php

use App\Http\Controllers\CreditRequestController;
use Illuminate\Support\Facades\Route;

/**
 * Credit Request Management Routes
 * Accessible by all authenticated users
 */
Route::middleware(['auth'])->group(function () {
    
    // Credit Requests - Available for all users
    Route::resource('credit-requests', CreditRequestController::class)->except(['edit', 'update', 'destroy']);
    
    // Additional actions
    Route::post('credit-requests/{creditRequest}/approve', [CreditRequestController::class, 'approve'])
        ->name('credit-requests.approve');
    
    Route::post('credit-requests/{creditRequest}/reject', [CreditRequestController::class, 'reject'])
        ->name('credit-requests.reject');
    
    Route::post('credit-requests/{creditRequest}/cancel', [CreditRequestController::class, 'cancel'])
        ->name('credit-requests.cancel');
    
    // AJAX endpoints
    Route::get('api/credit-requests/available-owners', [CreditRequestController::class, 'getAvailableOwners'])
        ->name('credit-requests.available-owners');
    
    Route::get('api/credit-requests/owner-charging-points/{ownerId}', [CreditRequestController::class, 'getOwnerChargingPoints'])
        ->name('credit-requests.owner-charging-points');
});

