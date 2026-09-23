<?php

// Temporarily disabled API routes due to missing controllers
// return;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
// use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\API\ChargingPointController as ChargingPointApiController;
use App\Http\Controllers\API\GroupApiController;
use App\Http\Controllers\API\PartnerApiController;
use App\Http\Controllers\API\IntegratorApiController;
use App\Http\Controllers\API\TransactionController;
use App\Http\Controllers\API\UserBalanceController;
use App\Http\Controllers\API\ReservationParticipantController;
use App\Http\Controllers\TransactionViewController;
use App\Http\Controllers\API\Public\ChargingPointController as PublicChargingPointController;
// use App\Http\Controllers\Api\Public\PublicChargingOfferController;
use App\Http\Controllers\AdminNotificationController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\AdvancedFeeController;
use App\Http\Controllers\ImmediateStartController;
use App\Http\Controllers\API\PricingPlanController;
use App\Http\Controllers\API\PricingRuleConditionController;

// SteVe integration test endpoint
Route::middleware('auth:sanctum')->group(function () {
    // Route::get('/steve/test/{chargingPointId}', [\App\Http\Controllers\Api\SteveController::class, 'test'])->name('api.steve.test');
});

// Public API routes (no middleware)
Route::prefix('public/api/v1/public')->group(function () {
    Route::get('charging-points', [PublicChargingPointController::class, 'index']);
    Route::get('charging-points/{id}', [PublicChargingPointController::class, 'show']);
    // Route::get('charging-points/{id}/offer', [PublicChargingOfferController::class, 'getOffer']);
    // Route::post('reservations', [\App\Http\Controllers\Api\Public\ReservationController::class, 'create']);
});

Route::prefix('v1')->group(function () {
    // Authentication routes
    // Route::post('auth/login', [AuthController::class, 'login']);
    // Route::post('auth/register', [AuthController::class, 'register']);
    // Route::post('auth/logout', [AuthController::class, 'logout']);
    // Route::get('auth/profile', [AuthController::class, 'profile']);
    // Route::post('auth/refresh-token', [AuthController::class, 'refreshToken']);
    // Route::put('auth/profile', [AuthController::class, 'updateProfile']);

    // Protected routes group
    Route::middleware('auth:sanctum')->group(function () {
        // Charging Points API routes
        Route::prefix('charging-points')->group(function () {
            Route::get('/', [ChargingPointApiController::class, 'index']);
            Route::get('/{id}', [ChargingPointApiController::class, 'show']);
            Route::post('/{id}/pricing-plan', [ChargingPointApiController::class, 'assignPricingPlan']);
            Route::post('/pricing-plan/batch', [ChargingPointApiController::class, 'assignPricingPlanBatch']);
            Route::get('/{id}/compatible-pricing-plans', [ChargingPointApiController::class, 'getCompatiblePricingPlans']);
            // Route pour récupérer les business profiles d'un charging point
            Route::get('/{id}/business-profiles', [ChargingPointApiController::class, 'getBusinessProfiles']);
            // Route pour récupérer les business profiles appliqués
            Route::get('/{id}/applied-business-profiles', [ChargingPointApiController::class, 'getAppliedBusinessProfiles']);

            // SteVe OCPP Integration routes
            Route::get('/{id}/charge-box-id', [ChargingPointApiController::class, 'getChargeBoxId']);
            Route::post('/{id}/connect-steve', [ChargingPointApiController::class, 'connectToSteVe']);
            Route::post('/{id}/disconnect-steve', [ChargingPointApiController::class, 'disconnectFromSteVe']);
            Route::get('/{id}/steve-status', [ChargingPointApiController::class, 'getSteVeStatus']);

            // Connector API routes - Gestion des connecteurs SteVe
            Route::prefix('{id}/connectors')->whereNumber(['id', 'connectorId'])->group(function () {
                // Récupérer tous les connecteurs du point de charge
                Route::get('/', [\App\Http\Controllers\ChargingPointController::class, 'getConnectors']);
                // Récupérer le connecteur par défaut (connector_id = 1)
                Route::get('/default', [\App\Http\Controllers\ChargingPointController::class, 'getDefaultConnector']);
                // Synchroniser les connecteurs depuis SteVe
                Route::post('/sync', [\App\Http\Controllers\ChargingPointController::class, 'syncConnectors']);
                // Récupérer le statut d'un connecteur spécifique
                Route::get('/{connectorId}', [\App\Http\Controllers\ChargingPointController::class, 'getConnectorStatus']);
                // Vérifier la disponibilité d'un connecteur
                Route::get('/{connectorId}/availability', [\App\Http\Controllers\ChargingPointController::class, 'checkConnectorAvailability']);
                // Déverrouiller un connecteur
                Route::post('/{connectorId}/unlock', [\App\Http\Controllers\ChargingPointController::class, 'unlockConnector']);
            });
        });

        // OCPP Operations API routes - Commandes OCPP vers les bornes
        Route::prefix('ocpp/charging-points')->whereNumber('chargingPointId')->group(function () {
            // Availability operations for the whole charge point
            Route::post('/{chargingPointId}/availability', [\App\Http\Controllers\OcppOperationsController::class, 'changeChargePointAvailability']);
            Route::post('/{chargingPointId}/enable', [\App\Http\Controllers\OcppOperationsController::class, 'enableChargePoint']);
            Route::post('/{chargingPointId}/disable', [\App\Http\Controllers\OcppOperationsController::class, 'disableChargePoint']);
            
            // Other charge point operations (legacy)
            Route::post('/{chargingPointId}/reset-legacy', [\App\Http\Controllers\OcppOperationsController::class, 'resetChargePointLegacy']);
            Route::post('/{chargingPointId}/clear-cache', [\App\Http\Controllers\OcppOperationsController::class, 'clearCache']);
            
            // NEW DTO-based charge point operations
            Route::post('/{chargingPointId}/reset', [\App\Http\Controllers\OcppOperationsController::class, 'reset'])
                ->name('ocpp.charging-points.reset');
            Route::post('/{chargingPointId}/reboot', [\App\Http\Controllers\OcppOperationsController::class, 'reboot'])
                ->name('ocpp.charging-points.reboot');
            Route::post('/{chargingPointId}/remote-start', [\App\Http\Controllers\OcppOperationsController::class, 'remoteStart'])
                ->name('ocpp.charging-points.remote-start');
            Route::post('/{chargingPointId}/remote-stop', [\App\Http\Controllers\OcppOperationsController::class, 'remoteStop'])
                ->name('ocpp.charging-points.remote-stop');
            
            // Connector-specific operations
            Route::prefix('{chargingPointId}/connectors/{connectorId}')->whereNumber('connectorId')->group(function () {
                Route::post('/availability', [\App\Http\Controllers\OcppOperationsController::class, 'changeConnectorAvailability']);
                Route::post('/enable', [\App\Http\Controllers\OcppOperationsController::class, 'enableConnector']);
                Route::post('/disable', [\App\Http\Controllers\OcppOperationsController::class, 'disableConnector']);
                Route::post('/unlock-legacy', [\App\Http\Controllers\OcppOperationsController::class, 'unlockConnectorLegacy']);
                
                // DTO-based connector operations. /lock is an operational lock:
                // ChangeAvailability(Inoperative), not a physical cable lock.
                Route::post('/lock', [\App\Http\Controllers\OcppOperationsController::class, 'lockConnector'])
                    ->name('ocpp.connectors.lock');
                Route::post('/unlock', [\App\Http\Controllers\OcppOperationsController::class, 'unlockConnector'])
                    ->name('ocpp.connectors.unlock');
            });
        });

        // SteVe Manager REST passthrough (Slice 2 — canonical CRUD aligned to
        // SteVe 3.9.0-SNAPSHOT under /manager/api/v1/*).
        //
        //   GET    /api/v1/steve/charge-points
        //   POST   /api/v1/steve/charge-points
        //   GET    /api/v1/steve/charge-points/{chargePointPk}
        //   PUT    /api/v1/steve/charge-points/{chargePointPk}
        //   DELETE /api/v1/steve/charge-points/{chargePointPk}
        //   GET    /api/v1/steve/ocpp-tags         + POST/GET/PUT/DELETE for {ocppTagPk}
        //   GET    /api/v1/steve/connectors        (?chargeBoxId=…)
        //   GET    /api/v1/steve/connectors/all
        //   GET    /api/v1/steve/connectors/status (?chargeBoxId=…)
        Route::prefix('steve')->group(function () {
            Route::prefix('charge-points')->whereNumber('chargePointPk')->group(function () {
                // Batch endpoints first — they MUST come before {chargePointPk} so
                // `batch` doesn't shadow as a numeric PK if someone weakens the
                // whereNumber constraint later.
                Route::post('batch/show',   [\App\Http\Controllers\Steve\ChargePointController::class, 'batchShow'])
                    ->name('api.steve.charge-points.batch.show');
                Route::put('batch',         [\App\Http\Controllers\Steve\ChargePointController::class, 'batchUpdate'])
                    ->name('api.steve.charge-points.batch.update');
                Route::post('batch/delete', [\App\Http\Controllers\Steve\ChargePointController::class, 'batchDestroy'])
                    ->name('api.steve.charge-points.batch.delete');

                Route::get('/',                   [\App\Http\Controllers\Steve\ChargePointController::class, 'index'])
                    ->name('api.steve.charge-points.index');
                Route::post('/',                  [\App\Http\Controllers\Steve\ChargePointController::class, 'store'])
                    ->name('api.steve.charge-points.store');
                Route::get('{chargePointPk}',     [\App\Http\Controllers\Steve\ChargePointController::class, 'show'])
                    ->name('api.steve.charge-points.show');
                Route::put('{chargePointPk}',     [\App\Http\Controllers\Steve\ChargePointController::class, 'update'])
                    ->name('api.steve.charge-points.update');
                Route::delete('{chargePointPk}',  [\App\Http\Controllers\Steve\ChargePointController::class, 'destroy'])
                    ->name('api.steve.charge-points.destroy');

                // Nested status + transaction-history reads scoped to one CP.
                Route::get('{chargePointPk}/status',       [\App\Http\Controllers\Steve\ChargePointController::class, 'status'])
                    ->name('api.steve.charge-points.status');
                Route::get('{chargePointPk}/transactions', [\App\Http\Controllers\Steve\ChargePointController::class, 'transactions'])
                    ->name('api.steve.charge-points.transactions');
            });

            Route::prefix('ocpp-tags')->whereNumber('ocppTagPk')->group(function () {
                Route::get('/',                [\App\Http\Controllers\Steve\OcppTagController::class, 'index'])
                    ->name('api.steve.ocpp-tags.index');
                Route::post('/',               [\App\Http\Controllers\Steve\OcppTagController::class, 'store'])
                    ->name('api.steve.ocpp-tags.store');
                Route::get('{ocppTagPk}',      [\App\Http\Controllers\Steve\OcppTagController::class, 'show'])
                    ->name('api.steve.ocpp-tags.show');
                Route::put('{ocppTagPk}',      [\App\Http\Controllers\Steve\OcppTagController::class, 'update'])
                    ->name('api.steve.ocpp-tags.update');
                Route::delete('{ocppTagPk}',   [\App\Http\Controllers\Steve\OcppTagController::class, 'destroy'])
                    ->name('api.steve.ocpp-tags.destroy');
            });

            Route::prefix('connectors')->group(function () {
                Route::get('/all',     [\App\Http\Controllers\Steve\ConnectorController::class, 'all'])
                    ->name('api.steve.connectors.all');
                Route::get('/status',  [\App\Http\Controllers\Steve\ConnectorController::class, 'status'])
                    ->name('api.steve.connectors.status');
                Route::get('/',        [\App\Http\Controllers\Steve\ConnectorController::class, 'index'])
                    ->name('api.steve.connectors.index');
            });
        });

        // Charging Sessions API routes
        Route::post('charging-sessions/reserve', [\App\Http\Controllers\Api\ChargingSessionController::class, 'reserveSession'])->name('api.charging-sessions.reserve');

        // Transaction specific routes
        Route::post('transactions/start-charge', [TransactionController::class, 'startCharge']);
        Route::post('transactions/stop-charge', [TransactionController::class, 'stopCharge']);
        Route::get('transactions/active', [TransactionController::class, 'getActiveCharges']);
        Route::get('transactions/history', [TransactionController::class, 'getChargeHistory']);
        Route::get('transactions/{transactionId}/details', [TransactionController::class, 'getChargeDetails']);
        Route::get('/transactions/{transactionId}/repartition', [\App\Http\Controllers\API\TransactionController::class, 'showRepartition']);
        Route::prefix('transactions')->group(function () {
            Route::get('{transaction}/repartition', [\App\Http\Controllers\TransactionController::class, 'getRepartition'])
                ->name('api.transactions.repartition');
        });

        // Transaction Types and Reporting API routes (Phase 5)
        Route::prefix('transaction-types')->group(function () {
            Route::get('/', [\App\Http\Controllers\API\TransactionController::class, 'getTransactionTypes'])
                ->name('api.transaction-types.list');
            Route::get('/categories', [\App\Http\Controllers\API\TransactionController::class, 'getTransactionCategories'])
                ->name('api.transaction-types.categories');
        });

        // Transaction Reporting API routes
        Route::prefix('reporting')->group(function () {
            Route::get('/stats', [\App\Http\Controllers\API\TransactionController::class, 'getTransactionStats'])
                ->name('api.reporting.stats');
            Route::get('/dashboard/admin', [\App\Http\Controllers\API\TransactionController::class, 'getAdminDashboard'])
                ->name('api.reporting.dashboard.admin');
            Route::get('/dashboard/integrator', [\App\Http\Controllers\API\TransactionController::class, 'getIntegratorPartnerDashboard'])
                ->name('api.reporting.dashboard.integrator');
            Route::get('/transactions', [\App\Http\Controllers\API\TransactionController::class, 'getTransactions'])
                ->name('api.reporting.transactions');
            Route::get('/export/csv', [\App\Http\Controllers\API\TransactionController::class, 'exportCsv'])
                ->name('api.reporting.export.csv');
            Route::get('/export/excel', [\App\Http\Controllers\API\TransactionController::class, 'exportExcel'])
                ->name('api.reporting.export.excel');
        });

        // Update transaction type
        Route::put('transactions/{id}/type', [\App\Http\Controllers\API\TransactionController::class, 'updateTransactionType'])
            ->name('api.transactions.update-type');

        // Pricing Plans API routes
        Route::prefix('pricing-plans')->group(function () {
            Route::get('/', [PricingPlanController::class, 'index'])->name('api.pricing-plans.index');
            Route::get('/{id}', [PricingPlanController::class, 'show'])->name('api.pricing-plans.show');
            Route::post('/', [PricingPlanController::class, 'store'])->name('api.pricing-plans.store');
            Route::put('/{id}', [PricingPlanController::class, 'update'])->name('api.pricing-plans.update');
            Route::delete('/{id}', [PricingPlanController::class, 'destroy'])->name('api.pricing-plans.destroy');
            Route::get('/offers', [PricingPlanController::class, 'getOffers'])->name('api.pricing-plans.offers');
            Route::post('/calculate-price', [PricingPlanController::class, 'calculatePrice'])->name('api.pricing-plans.calculate-price');
            Route::post('/plan-for-charging-point', [PricingPlanController::class, 'getPlanForChargingPoint'])->name('api.pricing-plans.plan-for-charging-point');
        });

        // Pricing Rule Conditions API routes
        Route::prefix('pricing-plans/{planId}/conditions')->group(function () {
            Route::get('/', [PricingRuleConditionController::class, 'index'])->name('api.pricing-plans.conditions.index');
            Route::get('/{id}', [PricingRuleConditionController::class, 'show'])->name('api.pricing-plans.conditions.show');
            Route::post('/', [PricingRuleConditionController::class, 'store'])->name('api.pricing-plans.conditions.store');
            Route::put('/{id}', [PricingRuleConditionController::class, 'update'])->name('api.pricing-plans.conditions.update');
            Route::delete('/{id}', [PricingRuleConditionController::class, 'destroy'])->name('api.pricing-plans.conditions.destroy');
            Route::post('/{id}/test-match', [PricingRuleConditionController::class, 'testMatch'])->name('api.pricing-plans.conditions.test-match');
        });

        // Rule condition metadata routes (no planId required)
        Route::prefix('pricing-conditions')->group(function () {
            Route::get('/fields', [PricingRuleConditionController::class, 'getFields'])->name('api.pricing-conditions.fields');
            Route::get('/operators', [PricingRuleConditionController::class, 'getOperators'])->name('api.pricing-conditions.operators');
            Route::get('/apply-types', [PricingRuleConditionController::class, 'getApplyTypes'])->name('api.pricing-conditions.apply-types');
        });

        // Balance API routes (wallet-based)
        Route::prefix('balances')->group(function () {
            Route::get('/summary', [UserBalanceController::class, 'getBalance'])->name('api.balances.summary');
            Route::get('/details', [UserBalanceController::class, 'getDetailedBalance'])->name('api.balances.details');
            Route::get('/history', [UserBalanceController::class, 'getBalanceHistory'])->name('api.balances.history');
        });

        // Reservation participants shares
        Route::get('reservations/{reservation}/participants', [ReservationParticipantController::class, 'index'])
            ->name('api.reservations.participants');
        Route::post('reservations/{reservation}/participants/{participant}/pay', [ReservationParticipantController::class, 'pay'])
            ->name('api.reservations.participants.pay');

        // Commandes de transaction
        Route::post('orders', [\App\Http\Controllers\Api\OrderController::class, 'store']);

        // Route backdoor pour confirmation et création de transaction
        Route::post('orders/{id}/backdoor-confirm', [\App\Http\Controllers\Api\OrderController::class, 'backdoorConfirmAndCreateTransaction']);

        // Transactions API routes
        Route::prefix('transactions')->group(function () {
            Route::get('/', [TransactionViewController::class, 'api']);
            Route::get('/{id}', [TransactionViewController::class, 'apiShow']);
            Route::get('/stats/summary', [TransactionViewController::class, 'apiStats']);
            Route::get('/export/csv', [TransactionViewController::class, 'export']);
        });

        // Unified hierarchical transactions
        Route::prefix('hierarchical')->group(function () {
            Route::post('/process', [\App\Http\Controllers\TransactionHierarchyController::class, 'processUnifiedHierarchicalTransaction'])
                ->name('api.hierarchical.process');
            Route::get('/summary', [\App\Http\Controllers\TransactionHierarchyController::class, 'getUnifiedTransactionSummary'])
                ->name('api.hierarchical.summary');
            Route::get('/simulate', [\App\Http\Controllers\TransactionHierarchyController::class, 'simulateUnifiedTransaction'])
                ->name('api.hierarchical.simulate');
        });

    });
});

// Route publique pour les notifications (sans middleware d'authentification)
// Note: Renommée pour éviter conflit avec admin.notifications.api.unread dans web.php
Route::get('v1/admin/notifications/unread', [AdminNotificationController::class, 'getUnreadJson'])->name('api.admin.notifications.unread');

// Admin API routes for QR code generation
Route::prefix('v1/admin/charging-points')->middleware('auth:sanctum')->group(function () {
    Route::post('{id}/qrcode', [\App\Http\Controllers\Api\Admin\QRCodeController::class, 'generate'])
        ->name('generate-qrcode');
    Route::get('{id}/qrcode/debug', [\App\Http\Controllers\Api\Admin\QRCodeController::class, 'debug'])
        ->name('debug-qrcode');
});

// Routes OCPP (pas de middleware d'authentification car les bornes ne s'authentifient pas)
Route::prefix('v1/ocpp')->group(function () {
    Route::post('/webhook', [\App\Http\Controllers\Api\OCPPWebhookController::class, 'handleWebhook'])->name('ocpp.webhook');
});

// Health check endpoint pour le monitoring
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'version' => config('app.version', '1.0.0'),
        'environment' => config('app.env'),
        'database' => [
            'status' => DB::connection()->getPdo() ? 'connected' : 'disconnected',
        ],
        'cache' => [
            'status' => Cache::store()->get('health_check') ? 'working' : 'not_working',
        ],
    ]);
});

// Advanced Fee Calculation API routes
Route::prefix('v1/fees')->middleware('auth:sanctum')->group(function () {
    Route::post('/calculate-charging-point', [AdvancedFeeController::class, 'calculateChargingPointFees']);
    Route::post('/apply-to-transaction', [AdvancedFeeController::class, 'applyFeesToTransaction']);
    Route::post('/calculate-multiple', [AdvancedFeeController::class, 'calculateMultipleChargingPointsFees']);
    Route::get('/creator-summary', [AdvancedFeeController::class, 'getCreatorFeeSummary']);
    Route::get('/test-scenarios', [AdvancedFeeController::class, 'testFeeScenarios']);
});

// Public reservation API (no CSRF protection)
Route::post('reservations/store/{chargingPoint}', [\App\Http\Controllers\PublicChargingOfferWebController::class, 'storeReservation'])->name('api.reservations.store');

// Routes pour les transactions hiérarchiques
Route::prefix('hierarchical-transactions')->name('hierarchical-transactions.')->group(function () {
    Route::post('/process-session/{sessionId}', [\App\Http\Controllers\HierarchicalTransactionController::class, 'processSessionTransaction'])
        ->name('process-session');
    Route::get('/check-balances/{chargingPointId}', [\App\Http\Controllers\HierarchicalTransactionController::class, 'checkBalances'])
        ->name('check-balances');
    Route::get('/history/{chargingPointId}', [\App\Http\Controllers\HierarchicalTransactionController::class, 'getTransactionHistory'])
        ->name('history');
    Route::post('/simulate/{chargingPointId}', [\App\Http\Controllers\HierarchicalTransactionController::class, 'simulateTransaction'])
        ->name('simulate');
});

// Routes pour les transactions de réservation avec répartition des parts
Route::prefix('reservation-transactions')->name('reservation-transactions.')->middleware('auth:sanctum')->group(function () {
    Route::post('/process', [\App\Http\Controllers\TransactionHierarchyController::class, 'processReservationTransaction'])
        ->name('process');
    Route::post('/process-all', [\App\Http\Controllers\TransactionHierarchyController::class, 'processAllConfirmedReservations'])
        ->name('process-all');
    Route::get('/summary', [\App\Http\Controllers\TransactionHierarchyController::class, 'getReservationTransactionSummary'])
        ->name('summary');
    Route::get('/list', [\App\Http\Controllers\TransactionHierarchyController::class, 'getReservationTransactions'])
        ->name('list');
    Route::get('/stats', [\App\Http\Controllers\TransactionHierarchyController::class, 'getTransactionStats'])
        ->name('stats');
    Route::get('/admin-shares', [\App\Http\Controllers\TransactionHierarchyController::class, 'getAdminShares'])
        ->name('admin-shares');
    Route::get('/hierarchical', [\App\Http\Controllers\TransactionHierarchyController::class, 'getHierarchicalTransactions'])
        ->name('hierarchical');
    Route::get('/user-balances', [\App\Http\Controllers\TransactionHierarchyController::class, 'getUserBalances'])
        ->name('user-balances');
    Route::post('/mark-paid', [\App\Http\Controllers\TransactionHierarchyController::class, 'markShareAsPaid'])
        ->name('mark-paid');
});

// Immediate Start API routes
Route::prefix('immediate-start')->middleware('auth:sanctum')->group(function () {
    Route::get('/status/{chargingPoint}', [ImmediateStartController::class, 'getStatus']);
    Route::post('/start/{chargingPoint}', [ImmediateStartController::class, 'startCharging']);
    Route::post('/process-payment/{chargingPoint}', [ImmediateStartController::class, 'processPaymentAndStart']);
    Route::get('/check-balance/{chargingPoint}', [ImmediateStartController::class, 'checkWalletBalance']);
    Route::get('/active-sessions', [ImmediateStartController::class, 'getActiveSessions']);
    Route::post('/stop/{session}', [ImmediateStartController::class, 'stopCharging']);
    Route::get('/session-status/{session}', [ImmediateStartController::class, 'getSessionStatus']);
});

// OCPP Tag Operations API routes (Start/Stop avec ID Tag spécifique comme Open10Tag)
Route::prefix('ocpp-tag-operations')->middleware('auth:sanctum')->group(function () {
    // Démarrer une transaction avec un ID Tag
    Route::post('/start/{chargingPoint}', [\App\Http\Controllers\OcppTagOperationsController::class, 'startTransaction'])
        ->name('api.ocpp-tag.start');
    
    // Arrêter une transaction
    Route::post('/stop/{chargingPoint}', [\App\Http\Controllers\OcppTagOperationsController::class, 'stopTransaction'])
        ->name('api.ocpp-tag.stop');
    
    // Arrêter toutes les transactions actives
    Route::post('/stop-all/{chargingPoint}', [\App\Http\Controllers\OcppTagOperationsController::class, 'stopAllTransactions'])
        ->name('api.ocpp-tag.stop-all');
    
    // Quick start avec le tag par défaut (Open10Tag)
    Route::post('/quick-start/{chargingPoint}', [\App\Http\Controllers\OcppTagOperationsController::class, 'quickStart'])
        ->name('api.ocpp-tag.quick-start');
    
    // Obtenir les transactions actives
    Route::get('/active-transactions/{chargingPoint}', [\App\Http\Controllers\OcppTagOperationsController::class, 'getActiveTransactions'])
        ->name('api.ocpp-tag.active-transactions');
    
    // Obtenir le statut du point de charge
    Route::get('/status/{chargingPoint}', [\App\Http\Controllers\OcppTagOperationsController::class, 'getStatus'])
        ->name('api.ocpp-tag.status');
    
    // Obtenir l'historique des opérations
    Route::get('/history/{chargingPoint}', [\App\Http\Controllers\OcppTagOperationsController::class, 'getOperationHistory'])
        ->name('api.ocpp-tag.history');
    
    // Informations et validation de tag OCPP
    Route::get('/tag-info', [\App\Http\Controllers\OcppTagOperationsController::class, 'getTagInfo'])
        ->name('api.ocpp-tag.info');
    Route::get('/validate-tag', [\App\Http\Controllers\OcppTagOperationsController::class, 'validateTag'])
        ->name('api.ocpp-tag.validate');
    
    // Gestion des tags OCPP (CRUD)
    Route::get('/tags', [\App\Http\Controllers\OcppTagOperationsController::class, 'listTags'])
        ->name('api.ocpp-tag.list');
    Route::post('/tags', [\App\Http\Controllers\OcppTagOperationsController::class, 'createTag'])
        ->name('api.ocpp-tag.create');
    Route::put('/tags/{ocppTagPk}', [\App\Http\Controllers\OcppTagOperationsController::class, 'updateTag'])
        ->name('api.ocpp-tag.update');
    Route::delete('/tags/{ocppTagPk}', [\App\Http\Controllers\OcppTagOperationsController::class, 'deleteTag'])
        ->name('api.ocpp-tag.delete');
    
    // Tags OCPP filtrés
    Route::get('/tags/in-transaction', [\App\Http\Controllers\OcppTagOperationsController::class, 'getTagsInTransaction'])
        ->name('api.ocpp-tag.in-transaction');
    Route::get('/tags/active', [\App\Http\Controllers\OcppTagOperationsController::class, 'getActiveTags'])
        ->name('api.ocpp-tag.active');
});

// Steve Transactions API routes (récupération et analyse des transactions OCPP)
Route::prefix('steve-transactions')->middleware('auth:sanctum')->group(function () {
    // Récupérer les transactions avec filtres
    Route::get('/', [\App\Http\Controllers\SteveTransactionController::class, 'apiGetTransactions'])
        ->name('api.steve-transactions.list');
    
    // Récupérer les transactions actives
    Route::get('/active', [\App\Http\Controllers\SteveTransactionController::class, 'apiGetActiveTransactions'])
        ->name('api.steve-transactions.active');
    
    // Récupérer les statistiques
    Route::get('/statistics', [\App\Http\Controllers\SteveTransactionController::class, 'apiGetStatistics'])
        ->name('api.steve-transactions.statistics');
    
    // Résumé par point de charge
    Route::get('/charge-box/{chargeBoxId}/summary', [\App\Http\Controllers\SteveTransactionController::class, 'apiGetChargeBoxSummary'])
        ->name('api.steve-transactions.charge-box-summary');
    
    // Résumé par tag OCPP
    Route::get('/tag/{ocppIdTag}/summary', [\App\Http\Controllers\SteveTransactionController::class, 'apiGetTagSummary'])
        ->name('api.steve-transactions.tag-summary');
    
    // Vider le cache
    Route::post('/clear-cache', [\App\Http\Controllers\SteveTransactionController::class, 'clearCache'])
        ->name('api.steve-transactions.clear-cache');
});

// Postpaid Charging API routes (Recharge Postpayée à la consommation)
Route::prefix('postpaid-charging')->middleware('auth:sanctum')->group(function () {
    Route::get('/check-wallet/{chargingPointId}', [\App\Http\Controllers\Api\PostpaidChargingController::class, 'checkWallet'])
        ->name('api.postpaid-charging.check-wallet');
    Route::post('/start/{chargingPointId}', [\App\Http\Controllers\Api\PostpaidChargingController::class, 'startSession'])
        ->name('api.postpaid-charging.start');
    Route::get('/{sessionId}/status', [\App\Http\Controllers\Api\PostpaidChargingController::class, 'getStatus'])
        ->name('api.postpaid-charging.status');
    Route::post('/{sessionId}/stop', [\App\Http\Controllers\Api\PostpaidChargingController::class, 'stopSession'])
        ->name('api.postpaid-charging.stop');
    Route::get('/{sessionId}/invoice', [\App\Http\Controllers\Api\PostpaidChargingController::class, 'getInvoice'])
        ->name('api.postpaid-charging.invoice');
});

// Diagnostics API routes (upload endpoint accessible sans auth car appelé par Steve)
Route::post('/diagnostics/upload', [App\Http\Controllers\Api\DiagnosticsController::class, 'upload'])
    ->name('api.diagnostics.upload');

// Diagnostics download route (avec auth)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/diagnostics/download/{path}', [App\Http\Controllers\Api\DiagnosticsController::class, 'download'])
        ->name('api.diagnostics.download');
});

// Steve API routes
Route::prefix('steve')->middleware('auth:sanctum')->group(function () {
    Route::post('/connect/{chargingPoint}', [App\Http\Controllers\SteveApiController::class, 'connectCharger']);
    Route::post('/start-charge/{chargingPoint}', [App\Http\Controllers\SteveApiController::class, 'startCharge']);
    Route::post('/stop-charge/{chargingPoint}', [App\Http\Controllers\SteveApiController::class, 'stopCharge']);
    Route::get('/status/{chargingPoint}', [App\Http\Controllers\SteveApiController::class, 'getChargerStatus']);
    Route::get('/session-status/{chargingPoint}', [App\Http\Controllers\SteveApiController::class, 'getSessionStatus']);
    Route::get('/info/{chargingPoint}', [App\Http\Controllers\SteveApiController::class, 'getChargerInfo']);
    Route::get('/stats/{chargingPoint?}', [App\Http\Controllers\SteveApiController::class, 'getChargingStats']);
    Route::get('/websocket-url/{chargingPoint}', [App\Http\Controllers\SteveApiController::class, 'generateWebSocketUrl']);
    Route::get('/test-connection', [App\Http\Controllers\SteveApiController::class, 'testConnection']);
    Route::get('/availability', [App\Http\Controllers\SteveApiController::class, 'checkAvailability']);
    Route::get('/config', [App\Http\Controllers\SteveApiController::class, 'getConfig']);
    Route::get('/chargers', [App\Http\Controllers\SteveApiController::class, 'listChargers']);
});

// Steve Dashboard API Routes
Route::prefix('steve/dashboard')->middleware('auth:sanctum')->group(function () {
    Route::get('/summary', [App\Http\Controllers\SteveDashboardController::class, 'summary']);
    Route::get('/sessions', [App\Http\Controllers\SteveDashboardController::class, 'sessions']);
    Route::get('/active-sessions', [App\Http\Controllers\SteveDashboardController::class, 'activeSessions']);
    Route::get('/energy', [App\Http\Controllers\SteveDashboardController::class, 'energy']);
    Route::get('/stations', [App\Http\Controllers\SteveDashboardController::class, 'stations']);
    Route::get('/station-metrics', [App\Http\Controllers\SteveDashboardController::class, 'stationMetrics']);
    Route::get('/availability', [App\Http\Controllers\SteveDashboardController::class, 'availability']);
    Route::get('/health', [App\Http\Controllers\SteveDashboardController::class, 'health']);
    Route::get('/performance', [App\Http\Controllers\SteveDashboardController::class, 'performance']);
    Route::get('/charger-types', [App\Http\Controllers\SteveDashboardController::class, 'chargerTypes']);
    Route::post('/refresh', [App\Http\Controllers\SteveDashboardController::class, 'refresh']);
    Route::get('/all', [App\Http\Controllers\SteveDashboardController::class, 'all']);
});

// Removed: `Route::prefix('api/steve')` group inside routes/api.php produced
// the broken `/api/api/steve/*` URLs (file is already loaded under `/api`).
// All actions duplicated by SteVeApiEndpointController have a canonical home
// under /api/v1/ocpp/... (see OcppOperationsController). The controller file
// is left in place as dead code; clean up in a follow-up commit.

// Charge Point Action Commands - Routes simplifiées pour les actions de commande
Route::prefix('commands')->middleware('auth:sanctum')->group(function () {
    Route::post('/{id}/start', [App\Http\Controllers\ChargePointActionController::class, 'start']);
    Route::post('/{id}/stop', [App\Http\Controllers\ChargePointActionController::class, 'stop']);
    Route::post('/{id}/unlock', [App\Http\Controllers\ChargePointActionController::class, 'unlock']);
    Route::post('/{id}/reset', [App\Http\Controllers\ChargePointActionController::class, 'reset']);
});

// Automatic Charger Connection API routes
Route::prefix('auto-connect')->middleware('auth:sanctum')->group(function () {
    Route::post('/connect/{chargingPoint}', [App\Http\Controllers\AutomaticChargerConnectionController::class, 'connectCharger']);
    Route::post('/disconnect/{chargingPoint}', [App\Http\Controllers\AutomaticChargerConnectionController::class, 'disconnectCharger']);
    Route::get('/status/{chargingPoint}', [App\Http\Controllers\AutomaticChargerConnectionController::class, 'getChargerStatus']);
    Route::post('/connect-group/{groupId}', [App\Http\Controllers\AutomaticChargerConnectionController::class, 'connectAllChargersInGroup']);
    Route::get('/stats', [App\Http\Controllers\AutomaticChargerConnectionController::class, 'getConnectionStats']);
    Route::delete('/cache/{chargingPoint?}', [App\Http\Controllers\AutomaticChargerConnectionController::class, 'clearConnectionCache']);
    Route::get('/test-connectivity', [App\Http\Controllers\AutomaticChargerConnectionController::class, 'testConnectivity']);
});

// Auto Remote Start - Démarrage automatique des transactions OCPP
require __DIR__.'/auto-remote-start.php';

// Withdrawal Requests API - Demandes de Retrait
Route::prefix('user/withdrawals')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\User\WithdrawalRequestController::class, 'index']);
    Route::post('/', [App\Http\Controllers\Api\User\WithdrawalRequestController::class, 'store']);
    Route::get('/limits', [App\Http\Controllers\Api\User\WithdrawalRequestController::class, 'limits']);
    Route::post('/calculate-fee', [App\Http\Controllers\Api\User\WithdrawalRequestController::class, 'calculateFee']);
    Route::get('/{withdrawalRequest}', [App\Http\Controllers\Api\User\WithdrawalRequestController::class, 'show']);
    Route::post('/{withdrawalRequest}/cancel', [App\Http\Controllers\Api\User\WithdrawalRequestController::class, 'cancel']);
});

// Admin Withdrawal Requests API
Route::prefix('admin/withdrawals')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [App\Http\Controllers\Admin\WithdrawalRequestController::class, 'list']);
    Route::get('/statistics', [App\Http\Controllers\Admin\WithdrawalRequestController::class, 'statistics']);
    Route::get('/{withdrawalRequest}', [App\Http\Controllers\Admin\WithdrawalRequestController::class, 'show']);
    Route::post('/{withdrawalRequest}/approve', [App\Http\Controllers\Admin\WithdrawalRequestController::class, 'approve']);
    Route::post('/{withdrawalRequest}/reject', [App\Http\Controllers\Admin\WithdrawalRequestController::class, 'reject']);
    Route::post('/{withdrawalRequest}/processing', [App\Http\Controllers\Admin\WithdrawalRequestController::class, 'markAsProcessing']);
    Route::post('/{withdrawalRequest}/completed', [App\Http\Controllers\Admin\WithdrawalRequestController::class, 'markAsCompleted']);
    Route::post('/{withdrawalRequest}/failed', [App\Http\Controllers\Admin\WithdrawalRequestController::class, 'markAsFailed']);
    Route::post('/bulk-process', [App\Http\Controllers\Admin\WithdrawalRequestController::class, 'bulkProcess']);
});

// Theme API - Gestion du thème Dark/Light
Route::prefix('user/theme')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\User\ThemeController::class, 'show']);
    Route::put('/', [App\Http\Controllers\Api\User\ThemeController::class, 'update']);
    Route::post('/toggle', [App\Http\Controllers\Api\User\ThemeController::class, 'toggle']);
    Route::get('/options', [App\Http\Controllers\Api\User\ThemeController::class, 'options']);
});

// Help/FAQ API
Route::prefix('help')->group(function () {
    Route::get('/categories', [App\Http\Controllers\Api\User\HelpController::class, 'categories']);
    Route::get('/featured', [App\Http\Controllers\Api\User\HelpController::class, 'featured']);
    Route::get('/popular', [App\Http\Controllers\Api\User\HelpController::class, 'popular']);
    Route::get('/search', [App\Http\Controllers\Api\User\HelpController::class, 'search']);
    Route::get('/{slug}', [App\Http\Controllers\Api\User\HelpController::class, 'show']);
});
