<?php

use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Language Routes
|--------------------------------------------------------------------------
|
| Routes for language switching functionality
| Using LocaleController which is the correct controller for language operations
|
*/

// Language switching routes - ACTIVE AND CORRECTED
// NOTE: Order matters! Static routes MUST come before wildcard routes
// CRITICAL FIX: Wrap in 'web' middleware group to ensure SetLocaleMiddleware runs

Route::middleware(['web'])->group(function () {
    // AJAX language switch route (POST)
    Route::post('/language/set', [LocaleController::class, 'setAjax'])->name('language.set.ajax');

    // Get current locale (GET) - Must be before {locale} wildcard
    Route::get('/language/current', [LocaleController::class, 'current'])->name('language.current');

    // Main language switch route (GET with redirect)
    // Note: La contrainte where a été supprimée pour permettre la gestion des IDs numériques dans le contrôleur
    Route::get('/language/{locale}', [LocaleController::class, 'switch'])
        ->name('language.switch');
});
