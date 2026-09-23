<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AutoRemoteStartController;

/*
|--------------------------------------------------------------------------
| Auto Remote Start API Routes
|--------------------------------------------------------------------------
|
| Routes pour le système de démarrage automatique des transactions OCPP.
| Ces routes permettent de gérer, monitorer et contrôler les démarrages
| automatiques via API.
|
| Toutes les routes nécessitent une authentification (sanctum).
|
*/

Route::middleware(['auth:sanctum'])->prefix('auto-remote-start')->group(function () {

    // ====================================================================
    // TRAITEMENT ET ACTIONS
    // ====================================================================

    // Traiter toutes les réservations éligibles
    Route::match(['get', 'post'], '/process', [AutoRemoteStartController::class, 'processAll'])
        ->name('auto-remote-start.process-all');

    // Traiter une réservation spécifique
    Route::post('/reservation/{id}', [AutoRemoteStartController::class, 'processReservation'])
        ->name('auto-remote-start.process-reservation');

    // Mettre une réservation en queue
    Route::post('/queue/{id}', [AutoRemoteStartController::class, 'queueReservation'])
        ->name('auto-remote-start.queue-reservation');

    // Vérifier l'éligibilité d'une réservation
    Route::get('/check-eligibility/{id}', [AutoRemoteStartController::class, 'checkEligibility'])
        ->name('auto-remote-start.check-eligibility');

    // ====================================================================
    // STATISTIQUES ET MONITORING
    // ====================================================================

    // Statistiques globales
    Route::get('/stats', [AutoRemoteStartController::class, 'stats'])
        ->name('auto-remote-start.stats');

    // Dashboard avec vue d'ensemble
    Route::get('/dashboard', [AutoRemoteStartController::class, 'dashboard'])
        ->name('auto-remote-start.dashboard');

    // Health check
    Route::get('/health', [AutoRemoteStartController::class, 'health'])
        ->name('auto-remote-start.health');

    // ====================================================================
    // LOGS
    // ====================================================================

    // Liste des logs avec filtres et pagination
    Route::get('/logs', [AutoRemoteStartController::class, 'logs'])
        ->name('auto-remote-start.logs');

    // Détail d'un log spécifique
    Route::get('/logs/{id}', [AutoRemoteStartController::class, 'showLog'])
        ->name('auto-remote-start.show-log');

    // ====================================================================
    // CONFIGURATION
    // ====================================================================

    // Configuration actuelle (admin uniquement)
    Route::get('/config', [AutoRemoteStartController::class, 'config'])
        ->name('auto-remote-start.config');

    // Réservations éligibles actuelles
    Route::get('/eligible-reservations', [AutoRemoteStartController::class, 'eligibleReservations'])
        ->name('auto-remote-start.eligible-reservations');
});

