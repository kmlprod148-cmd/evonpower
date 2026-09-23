<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\TestSidebarController;
use App\Http\Controllers\Admin\AdminCreditRechargeController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CashPaymentController;
use App\Http\Controllers\Admin\ClientUserController;
use App\Http\Controllers\Admin\ClientOcppTagController;
use App\Http\Controllers\Admin\SubscriptionPlanController;
use App\Http\Controllers\Admin\WithdrawalRequestController;
use App\Http\Controllers\Admin\ChargePointActionController;
use App\Http\Controllers\Admin\AdminChargingPointController;
use App\Http\Controllers\Admin\IntegratorContractProfileController;
use App\Http\Controllers\Admin\IntegratorBillingInvoiceController;
use App\Http\Controllers\Admin\AdminIntegratorController;
use App\Http\Controllers\Admin\AdminPartnerController;
use App\Http\Controllers\Admin\AdminGroupController;
use App\Http\Controllers\Admin\AdminPricingPlanController;
use App\Http\Controllers\Admin\AdminReservationController;
use App\Http\Controllers\BusinessProfileController as SharedBusinessProfileController;

// Routes Admin
// Prefix and gate are configurable: see config/admin.php (ADMIN_PATH, ADMIN_PANEL_PERMISSION).
// `admin.panel` enforces web-guard auth + the access-admin-panel permission.
$adminPath = config('admin.path') ?: 'admin';

Route::prefix($adminPath)->middleware(["auth", "admin.panel"])->group(function () use ($adminPath) {
    // Dashboard - Une seule route suffit
    Route::get("/", [DashboardController::class, "index"])->name("admin.dashboard");
    // Redirection de /dashboard vers /
    Route::redirect("/dashboard", "/{$adminPath}");
    
    // Test Sidebar
    Route::get("/test-sidebar", [TestSidebarController::class, "index"])->name("admin.test-sidebar");
    
    // User Management Routes (includes operators)
    Route::prefix('users')->name('admin.users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{id}', [UserController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{id}', [UserController::class, 'update'])->name('update');
        Route::delete('/{id}', [UserController::class, 'destroy'])->name('destroy');
    });
    
    // Integrators Management
    Route::prefix('integrators')->name('admin.integrators.')->group(function () {
        Route::get('/', [AdminIntegratorController::class, 'index'])->name('index');
        Route::get('/create', [AdminIntegratorController::class, 'create'])->name('create');
        Route::post('/', [AdminIntegratorController::class, 'store'])->name('store');
        Route::get('/{integrator}', [AdminIntegratorController::class, 'show'])->name('show');
        Route::get('/{integrator}/edit', [AdminIntegratorController::class, 'edit'])->name('edit');
        Route::put('/{integrator}', [AdminIntegratorController::class, 'update'])->name('update');
        Route::delete('/{integrator}', [AdminIntegratorController::class, 'destroy'])->name('destroy');
        Route::post('/{integrator}/toggle-active', [AdminIntegratorController::class, 'toggleActive'])->name('toggle-active');
    });

    // Partners / Operators Management
    Route::prefix('partners')->name('admin.partners.')->group(function () {
        Route::get('/', [AdminPartnerController::class, 'index'])->name('index');
        Route::get('/create', [AdminPartnerController::class, 'create'])->name('create');
        Route::post('/', [AdminPartnerController::class, 'store'])->name('store');
        Route::get('/{partner}', [AdminPartnerController::class, 'show'])->name('show');
        Route::get('/{partner}/edit', [AdminPartnerController::class, 'edit'])->name('edit');
        Route::put('/{partner}', [AdminPartnerController::class, 'update'])->name('update');
        Route::delete('/{partner}', [AdminPartnerController::class, 'destroy'])->name('destroy');
        Route::post('/{partner}/toggle-active', [AdminPartnerController::class, 'toggleActive'])->name('toggle-active');
    });

    Route::get("/operators", function () {
        return redirect()->route('admin.partners.index');
    })->name("admin.operators.index");

    // Business Profiles Management
    Route::prefix('business-profiles')->name('admin.business-profiles.')->group(function () {
        Route::get('/', [SharedBusinessProfileController::class, 'index'])->name('index');
        Route::get('/create', [SharedBusinessProfileController::class, 'create'])->name('create');
        Route::post('/', [SharedBusinessProfileController::class, 'store'])->name('store');
        Route::get('/admin-fees', [SharedBusinessProfileController::class, 'adminFees'])->name('admin-fees');
        Route::get('/{businessProfile}/manage-partner-rates', [SharedBusinessProfileController::class, 'managePartnerRates'])->name('manage-partner-rates');
        Route::post('/{businessProfile}/apply-partner-rates', [SharedBusinessProfileController::class, 'applyPartnerRates'])->name('apply-partner-rates');
        Route::put('/{businessProfile}/admin-fees', [SharedBusinessProfileController::class, 'updateAdminFees'])->name('update-admin-fees');
        Route::get('/{businessProfile}', [SharedBusinessProfileController::class, 'show'])->name('show');
        Route::get('/{businessProfile}/edit', [SharedBusinessProfileController::class, 'edit'])->name('edit');
        Route::put('/{businessProfile}', [SharedBusinessProfileController::class, 'update'])->name('update');
        Route::delete('/{businessProfile}', [SharedBusinessProfileController::class, 'destroy'])->name('destroy');
    });

    // Transaction Management Routes
    Route::prefix('transactions')->name('admin.transactions.')->group(function () {
        Route::get('/', [TransactionController::class, 'index'])->name('index');
        Route::get('/{transaction}', [TransactionController::class, 'show'])->name('show');
        Route::get('/export/csv', [TransactionController::class, 'export'])->name('export.csv'); // Renommé pour éviter conflit
        Route::get('/api/users', [TransactionController::class, 'getUsers'])->name('users');
    });
    
    Route::prefix('payments')->name('admin.payments.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\PaymentManagementController::class, 'index'])->name('index');
        Route::get('/export', [App\Http\Controllers\Admin\PaymentManagementController::class, 'export'])->name('export');
        Route::get('/statistics', [App\Http\Controllers\Admin\PaymentManagementController::class, 'getStatistics'])->name('statistics');
        Route::get('/{payment}', [App\Http\Controllers\Admin\PaymentManagementController::class, 'show'])->name('show');
        Route::post('/{payment}/status', [App\Http\Controllers\Admin\PaymentManagementController::class, 'updateStatus'])->name('update-status');
        Route::post('/{payment}/refund', [App\Http\Controllers\Admin\PaymentManagementController::class, 'refund'])->name('refund');
    });

    // Reservations Management
    Route::prefix('reservations')->name('admin.reservations.')->group(function () {
        Route::get('/', [AdminReservationController::class, 'index'])->name('index');
        Route::get('/create', [AdminReservationController::class, 'create'])->name('create');
        Route::post('/', [AdminReservationController::class, 'store'])->name('store');
        Route::get('/{reservation}', [AdminReservationController::class, 'show'])->name('show');
        Route::get('/{reservation}/edit', [AdminReservationController::class, 'edit'])->name('edit');
        Route::put('/{reservation}', [AdminReservationController::class, 'update'])->name('update');
        Route::get('/{reservation}/confirm', [AdminReservationController::class, 'showConfirmForm'])->name('confirm.show');
        Route::post('/{reservation}/confirm', [AdminReservationController::class, 'confirm'])->name('confirm');
        Route::post('/{reservation}/confirm-with-fees', [AdminReservationController::class, 'confirmWithCustomFees'])->name('confirm-with-fees');
        Route::get('/{reservation}/confirm-with-cost', [AdminReservationController::class, 'showConfirmWithCostForm'])->name('confirm-with-cost.show');
        Route::post('/{reservation}/confirm-with-cost', [AdminReservationController::class, 'confirmWithCost'])->name('confirm-with-cost');
        Route::post('/{reservation}/reject', [AdminReservationController::class, 'reject'])->name('reject');
    });
    
    // Pricing Plans Management
    Route::prefix('pricing-plans')->name('admin.pricing-plans.')->group(function () {
        Route::get('/', [AdminPricingPlanController::class, 'index'])->name('index');
        Route::get('/create', [AdminPricingPlanController::class, 'create'])->name('create');
        Route::post('/', [AdminPricingPlanController::class, 'store'])->name('store');
        Route::get('/{pricingPlan}', [AdminPricingPlanController::class, 'show'])->name('show');
        Route::get('/{pricingPlan}/edit', [AdminPricingPlanController::class, 'edit'])->name('edit');
        Route::put('/{pricingPlan}', [AdminPricingPlanController::class, 'update'])->name('update');
        Route::delete('/{pricingPlan}', [AdminPricingPlanController::class, 'destroy'])->name('destroy');
        Route::post('/{pricingPlan}/toggle-active', [AdminPricingPlanController::class, 'toggleActive'])->name('toggle-active');
    });

    // Groups Management
    Route::prefix('groups')->name('admin.groups.')->group(function () {
        Route::get('/', [AdminGroupController::class, 'index'])->name('index');
        Route::get('/create', [AdminGroupController::class, 'create'])->name('create');
        Route::post('/', [AdminGroupController::class, 'store'])->name('store');
        Route::get('/{group}', [AdminGroupController::class, 'show'])->name('show');
        Route::get('/{group}/edit', [AdminGroupController::class, 'edit'])->name('edit');
        Route::put('/{group}', [AdminGroupController::class, 'update'])->name('update');
        Route::delete('/{group}', [AdminGroupController::class, 'destroy'])->name('destroy');
    });
    
    // VAT Rates Management
    Route::prefix('vat-rates')->name('admin.vat_rates.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\VatRateController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Admin\VatRateController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Admin\VatRateController::class, 'store'])->name('store');
        Route::get('/{vatRate}/edit', [App\Http\Controllers\Admin\VatRateController::class, 'edit'])->name('edit');
        Route::put('/{vatRate}', [App\Http\Controllers\Admin\VatRateController::class, 'update'])->name('update');
        Route::delete('/{vatRate}', [App\Http\Controllers\Admin\VatRateController::class, 'destroy'])->name('destroy');
    });

    // System Settings (API keys, WebSocket, Currency)
    Route::prefix('system-settings')->name('admin.system-settings.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\SystemSettingsController::class, 'index'])->name('index');
        Route::post('/api-keys', [App\Http\Controllers\Admin\SystemSettingsController::class, 'updateApiKeys'])->name('update-api-keys');
        Route::post('/websocket', [App\Http\Controllers\Admin\SystemSettingsController::class, 'updateWebSocketUrls'])->name('update-websocket-urls');
        Route::post('/currency', [App\Http\Controllers\Admin\SystemSettingsController::class, 'updateCurrency'])->name('update-currency');
    });
    Route::redirect('/settings', "/{$adminPath}/system-settings")->name('admin.settings.index');
    
    // Subscription Plans Management Routes
    Route::prefix('subscriptions/plans')->name('admin.subscriptions.plans.')->group(function () {
        Route::get('/', [SubscriptionPlanController::class, 'index'])->name('index');
        Route::get('/create', [SubscriptionPlanController::class, 'create'])->name('create');
        Route::post('/', [SubscriptionPlanController::class, 'store'])->name('store');
        Route::get('/{id}', [SubscriptionPlanController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [SubscriptionPlanController::class, 'edit'])->name('edit');
        Route::put('/{id}', [SubscriptionPlanController::class, 'update'])->name('update');
        Route::delete('/{id}', [SubscriptionPlanController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/toggle-active', [SubscriptionPlanController::class, 'toggleActive'])->name('toggle-active');
        Route::post('/{id}/toggle-featured', [SubscriptionPlanController::class, 'toggleFeatured'])->name('toggle-featured');
    });
    
    // Withdrawal Requests Management Routes
    Route::prefix('withdrawals')->name('admin.withdrawals.')->group(function () {
        Route::get('/', [WithdrawalRequestController::class, 'index'])->name('index');
        Route::get('/{id}', [WithdrawalRequestController::class, 'show'])->name('show');
        Route::post('/{id}/approve', [WithdrawalRequestController::class, 'approve'])->name('approve');
        Route::post('/{id}/reject', [WithdrawalRequestController::class, 'reject'])->name('reject');
        Route::post('/{id}/process', [WithdrawalRequestController::class, 'process'])->name('process');
        Route::post('/{id}/cancel', [WithdrawalRequestController::class, 'cancel'])->name('cancel');
        Route::post('/bulk-approve', [WithdrawalRequestController::class, 'bulkApprove'])->name('bulk-approve');
        Route::post('/bulk-process', [WithdrawalRequestController::class, 'bulkProcess'])->name('bulk-process');
    });

    // Integrator Contract Profiles Routes
    Route::prefix('integrator-contracts')->name('admin.integrator-contracts.')->group(function () {
        Route::get('/', [IntegratorContractProfileController::class, 'index'])->name('index');
        Route::get('/create', [IntegratorContractProfileController::class, 'create'])->name('create');
        Route::post('/', [IntegratorContractProfileController::class, 'store'])->name('store');
        Route::get('/{contract}', [IntegratorContractProfileController::class, 'show'])->name('show');
        Route::get('/{contract}/edit', [IntegratorContractProfileController::class, 'edit'])->name('edit');
        Route::put('/{contract}', [IntegratorContractProfileController::class, 'update'])->name('update');
        Route::delete('/{contract}', [IntegratorContractProfileController::class, 'destroy'])->name('destroy');
        Route::post('/{contract}/process-billing', [IntegratorContractProfileController::class, 'processBilling'])->name('process-billing');
    });

    // Integrator Billing Invoices Routes
    Route::prefix('integrator-invoices')->name('admin.integrator-invoices.')->group(function () {
        Route::get('/', [IntegratorBillingInvoiceController::class, 'index'])->name('index');
        Route::get('/line-items', [IntegratorBillingInvoiceController::class, 'getLineItems'])->name('line-items');
        Route::get('/{invoice}', [IntegratorBillingInvoiceController::class, 'show'])->name('show');
        Route::post('/{invoice}/mark-paid', [IntegratorBillingInvoiceController::class, 'markAsPaid'])->name('mark-paid');
        Route::post('/{invoice}/cancel', [IntegratorBillingInvoiceController::class, 'cancel'])->name('cancel');
    });
    
    // Client User Management Routes
    Route::prefix('clients')->name('admin.clients.')->group(function () {
        Route::get('/', [ClientUserController::class, 'index'])->name('index');
        Route::get('/statistics', [ClientUserController::class, 'statistics'])->name('statistics');
        // Two-step creation
        Route::get('/create/step1', [ClientUserController::class, 'createStep1'])->name('create-step1');
        Route::post('/create/step1', [ClientUserController::class, 'storeStep1'])->name('store-step1');
        Route::get('/create/step2', [ClientUserController::class, 'createStep2'])->name('create-step2');
        Route::post('/create/step2', [ClientUserController::class, 'storeStep2'])->name('store-step2');
        Route::post('/create/cancel', [ClientUserController::class, 'cancelCreate'])->name('cancel-create');
        // CRUD
        Route::get('/{client}', [ClientUserController::class, 'show'])->name('show');
        Route::get('/{client}/edit', [ClientUserController::class, 'edit'])->name('edit');
        Route::put('/{client}', [ClientUserController::class, 'update'])->name('update');
        Route::delete('/{client}', [ClientUserController::class, 'destroy'])->name('destroy');
        // Actions
        Route::put('/{client}/toggle-active', [ClientUserController::class, 'toggleActive'])->name('toggle-active');
        Route::put('/{client}/verify-email', [ClientUserController::class, 'verifyEmail'])->name('verify-email');

        // OCPP Tag Management for Client
        Route::prefix('{client}/tags')->name('clients.tags.')->group(function () {
            Route::get('/', [ClientOcppTagController::class, 'index'])->name('index');
            Route::get('/create', [ClientOcppTagController::class, 'create'])->name('create');
            Route::post('/', [ClientOcppTagController::class, 'store'])->name('store');
            Route::get('/associate', [ClientOcppTagController::class, 'showAssociateForm'])->name('associate-form');
            Route::post('/associate', [ClientOcppTagController::class, 'associate'])->name('associate');
            Route::post('/{tag}/dissociate', [ClientOcppTagController::class, 'dissociate'])->name('dissociate');
            Route::post('/{tag}/block', [ClientOcppTagController::class, 'block'])->name('block');
            Route::post('/{tag}/unblock', [ClientOcppTagController::class, 'unblock'])->name('unblock');
            Route::post('/{tag}/default', [ClientOcppTagController::class, 'setDefault'])->name('default');
            Route::get('/{tag}/history', [ClientOcppTagController::class, 'history'])->name('history');
            Route::delete('/{tag}', [ClientOcppTagController::class, 'destroy'])->name('destroy');
        });
    });
    
    // Credit Recharge Management Routes
    Route::prefix('credit-recharges')->name('admin.credit-recharges.')->group(function () {
        Route::get('/dashboard', [AdminCreditRechargeController::class, 'dashboard'])->name('dashboard');
        Route::get('/', [AdminCreditRechargeController::class, 'index'])->name('index');
        Route::get('/pending', [AdminCreditRechargeController::class, 'pending'])->name('pending');
        Route::get('/{creditRecharge}', [AdminCreditRechargeController::class, 'show'])->name('show');
        Route::post('/{creditRecharge}/confirm', [AdminCreditRechargeController::class, 'confirm'])->name('confirm');
        Route::post('/{creditRecharge}/reject', [AdminCreditRechargeController::class, 'reject'])->name('reject');
    });
    
    // Monitoring Routes
    Route::prefix('monitoring')->name('admin.monitoring.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\MonitoringController::class, 'index'])->name('index');
        Route::get('/dashboard-data', [App\Http\Controllers\Admin\MonitoringController::class, 'getDashboardData'])->name('dashboard-data');
        Route::get('/real-time-metrics', [App\Http\Controllers\Admin\MonitoringController::class, 'getRealTimeMetrics'])->name('real-time-metrics');
        Route::get('/recent-requests', [App\Http\Controllers\Admin\MonitoringController::class, 'getRecentRequests'])->name('recent-requests');
        Route::get('/detailed-stats', [App\Http\Controllers\Admin\MonitoringController::class, 'getDetailedStats'])->name('detailed-stats');
        Route::get('/api-health', [App\Http\Controllers\Admin\MonitoringController::class, 'getApiHealth'])->name('api-health');
        Route::get('/export', [App\Http\Controllers\Admin\MonitoringController::class, 'export'])->name('export');
        Route::post('/cleanup', [App\Http\Controllers\Admin\MonitoringController::class, 'cleanup'])->name('cleanup');
        Route::get('/alerts', [App\Http\Controllers\Admin\MonitoringController::class, 'getAlerts'])->name('alerts');
        Route::get('/chart-data', [App\Http\Controllers\Admin\MonitoringController::class, 'getChartData'])->name('chart-data');
        Route::get('/charger-status', [App\Http\Controllers\Admin\MonitoringController::class, 'getChargerStatus'])->name('charger-status');
        Route::get('/overall-health', [App\Http\Controllers\Admin\MonitoringController::class, 'getOverallHealth'])->name('overall-health');
        Route::post('/check-charger/{chargingPoint}', [App\Http\Controllers\Admin\MonitoringController::class, 'checkChargerHealth'])->name('check-charger');
        Route::post('/check-api/{apiName}', [App\Http\Controllers\Admin\MonitoringController::class, 'checkApiHealth'])->name('check-api');
        Route::get('/entity-history/{entityType}/{entityId}', [App\Http\Controllers\Admin\MonitoringController::class, 'getEntityHistory'])->name('entity-history');
        Route::post('/clear-cache', [App\Http\Controllers\Admin\MonitoringController::class, 'clearCache'])->name('clear-cache');
    });
    
    // Cash Payment Routes - Espèce Client
    Route::prefix('cash-payments')->name('admin.cash-payments.')->group(function () {
        Route::get('/', [CashPaymentController::class, 'index'])->name('index');
        Route::get('/create', [CashPaymentController::class, 'create'])->name('create');
        Route::post('/', [CashPaymentController::class, 'store'])->name('store');
        Route::get('/{id}', [CashPaymentController::class, 'show'])->name('show');
        Route::post('/{id}/cancel', [CashPaymentController::class, 'cancel'])->name('cancel');
        // API routes
        Route::get('/search-clients', [CashPaymentController::class, 'searchClients'])->name('search-clients');
        Route::post('/validate', [CashPaymentController::class, 'validatePayment'])->name('validate');
    });
    
    // -------------------------------------------------------------------------
    // Charging Points — Full CRUD (local DB + Steve sync)
    // -------------------------------------------------------------------------
    Route::prefix('charging-points')->name('admin.charging-points.')->group(function () {
        // List (with filters)
        Route::get('/', [AdminChargingPointController::class, 'index'])->name('index');

        // Create / Store
        Route::get('/create', [AdminChargingPointController::class, 'create'])->name('create');
        Route::post('/', [AdminChargingPointController::class, 'store'])->name('store');

        // Browse Steve-registered charge points
        Route::get('/steve-list', [AdminChargingPointController::class, 'steveList'])->name('steve-list');

        // Show
        Route::get('/{chargingPoint}', [AdminChargingPointController::class, 'show'])->name('show');

        // Edit / Update
        Route::get('/{chargingPoint}/edit', [AdminChargingPointController::class, 'edit'])->name('edit');
        Route::put('/{chargingPoint}', [AdminChargingPointController::class, 'update'])->name('update');
        Route::patch('/{chargingPoint}', [AdminChargingPointController::class, 'update'])->name('update-patch');

        // Delete
        Route::delete('/{chargingPoint}', [AdminChargingPointController::class, 'destroy'])->name('destroy');

        // Local status toggle
        Route::post('/{chargingPoint}/toggle-status', [AdminChargingPointController::class, 'toggleStatus'])->name('toggle-status');

        // Real-time status from Steve (JSON — AJAX polling)
        Route::get('/{chargingPoint}/realtime-status', [AdminChargingPointController::class, 'realtimeStatus'])->name('realtime-status');

        // Manual Steve registration / re-sync
        Route::post('/{chargingPoint}/sync-steve', [AdminChargingPointController::class, 'syncToSteve'])->name('sync-steve');

        // -------------------------------------------------------------------------
        // OCPP Remote Actions — all calls go directly to Steve API (synchronous)
        // -------------------------------------------------------------------------

        // Remote Start Transaction: { connector_id, id_tag?, charging_profile_pk? }
        Route::post('/{chargingPoint}/remote-start', [ChargePointActionController::class, 'remoteStart'])
            ->name('remote-start');

        // Remote Stop Transaction: { transaction_id }
        Route::post('/{chargingPoint}/remote-stop', [ChargePointActionController::class, 'remoteStop'])
            ->name('remote-stop');

        // Reset (Soft/Hard): { type? }
        Route::post('/{chargingPoint}/reset', [ChargePointActionController::class, 'reset'])
            ->name('reset');

        // Reboot — alias for Soft Reset: { type? }
        Route::post('/{chargingPoint}/reboot', [ChargePointActionController::class, 'reboot'])
            ->name('reboot');

        // ChangeAvailability: { connector_id?, type (Operative|Inoperative) }
        Route::post('/{chargingPoint}/availability', [ChargePointActionController::class, 'changeAvailability'])
            ->name('availability');

        // Lock — sets connector Inoperative: { connector_id? }
        Route::post('/{chargingPoint}/lock', [ChargePointActionController::class, 'lock'])
            ->name('lock');

        // Unlock Connector: { connector_id }
        Route::post('/{chargingPoint}/unlock', [ChargePointActionController::class, 'unlockConnector'])
            ->name('unlock');

        // Clear authorization cache on the station
        Route::post('/{chargingPoint}/clear-cache', [ChargePointActionController::class, 'clearCache'])
            ->name('clear-cache');

        // Live real-time status: ?connector_id=&id_tag=
        Route::get('/{chargingPoint}/live-status', [ChargePointActionController::class, 'liveStatus'])
            ->name('live-status');

        // Command history log
        Route::get('/{chargingPoint}/commands', [ChargePointActionController::class, 'getCommands'])
            ->name('commands');
    });
    
    // Reports and Exports
    Route::prefix('reports')->name('admin.reports.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\ReportController::class, 'index'])->name('index');
        Route::get('/transactions/export', [App\Http\Controllers\Admin\ReportController::class, 'exportTransactions'])->name('transactions.export');
        Route::get('/withdrawals/export', [App\Http\Controllers\Admin\ReportController::class, 'exportWithdrawals'])->name('withdrawals.export');
        Route::get('/sessions/export', [App\Http\Controllers\Admin\ReportController::class, 'exportSessions'])->name('sessions.export');
        Route::get('/financial/export', [App\Http\Controllers\Admin\ReportController::class, 'exportFinancialReport'])->name('financial.export');
        Route::get('/statistics/export', [App\Http\Controllers\Admin\ReportController::class, 'exportStatistics'])->name('statistics.export');
    });
    
    // Help and FAQ Management
    Route::prefix('help')->name('admin.help.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\HelpController::class, 'index'])->name('index');
        Route::get('/statistics', [App\Http\Controllers\Admin\HelpController::class, 'statistics'])->name('statistics');
        Route::get('/popular-searches', [App\Http\Controllers\Admin\HelpController::class, 'popularSearches'])->name('popular-searches');
        
        // Categories
        Route::prefix('categories')->name('categories.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\HelpController::class, 'categoriesIndex'])->name('index');
            Route::post('/', [App\Http\Controllers\Admin\HelpController::class, 'categoryStore'])->name('store');
            Route::put('/{category}', [App\Http\Controllers\Admin\HelpController::class, 'categoryUpdate'])->name('update');
            Route::delete('/{category}', [App\Http\Controllers\Admin\HelpController::class, 'categoryDestroy'])->name('destroy');
        });
        
        // Articles
        Route::prefix('articles')->name('articles.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\HelpController::class, 'articlesIndex'])->name('index');
            Route::get('/create', [App\Http\Controllers\Admin\HelpController::class, 'articleCreate'])->name('create');
            Route::post('/', [App\Http\Controllers\Admin\HelpController::class, 'articleStore'])->name('store');
            Route::get('/{article}/edit', [App\Http\Controllers\Admin\HelpController::class, 'articleEdit'])->name('edit');
            Route::put('/{article}', [App\Http\Controllers\Admin\HelpController::class, 'articleUpdate'])->name('update');
            Route::delete('/{article}', [App\Http\Controllers\Admin\HelpController::class, 'articleDestroy'])->name('destroy');
            Route::post('/{article}/toggle-publish', [App\Http\Controllers\Admin\HelpController::class, 'articleTogglePublish'])->name('toggle-publish');
        });
    });
    
    // Diagnostics Routes
    Route::prefix('diagnostics')->name('admin.diagnostics.')->group(function () {
        Route::get('/group-partner-associations', [App\Http\Controllers\Admin\DiagnosticController::class, 'showGroupPartnerAssociations'])->name('group-partner-associations');
        Route::post('/fix-group-association', [App\Http\Controllers\Admin\DiagnosticController::class, 'fixGroupAssociation'])->name('fix-group-association');
        Route::post('/fix-all-associations', [App\Http\Controllers\Admin\DiagnosticController::class, 'fixAllAssociations'])->name('fix-all-associations');
    });
});
