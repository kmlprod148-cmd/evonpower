<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChargingPointViewController;
use App\Http\Controllers\UnifiedChargingPointController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\OperatorDashboardController;
use App\Http\Controllers\IntegratorDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\StationController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\Admin\AdminTransactionController;
use App\Http\Controllers\IntegratorController;
use App\Http\Controllers\PricingPlanController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\AdminNotificationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Admin\BusinessProfileController as AdminBusinessProfileController;
use App\Http\Controllers\Admin\AdminReservationController;
use App\Http\Controllers\Admin\OrderAdminController;
use App\Http\Controllers\BusinessProfileController;
use App\Http\Controllers\WithdrawalRequestController;
use App\Http\Controllers\CommissionPlanController;
use App\Http\Controllers\CommissionController;
use App\Http\Controllers\FinancialTransactionController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\QRCodeTestController;
use App\Http\Controllers\RemoteControlController;
use App\Models\BusinessProfile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

// Language routes
Route::middleware(['web'])->group(function () {
    Route::get('/lang/{locale}', [\App\Http\Controllers\LanguageController::class, 'switchLanguage'])->name('lang.switch');
    Route::get('/lang-test', [\App\Http\Controllers\LanguageController::class, 'testLanguage'])->name('lang.test');
});

// Test routes
Route::get('/test', function () { return 'Test route works!'; })->name('test');
Route::get('/debug-test-route', function () { return 'Debug test route hit!'; });

// Main Application Routes with Unified Permission Middleware
Route::middleware(['auth'])->group(function () {
    // Charging Points Routes (consolidated in charging-points.php)
    require __DIR__.'/charging-points.php';
    
    // Support contact route
    Route::get('/support/contact', function () { return view('contact'); })->name('support.contact');
    
    // Public charging offer routes
    Route::prefix('public/charging-point/{charging_point_id}/offer')
        ->name('public.charging-point.offer.')
        ->group(function () {
            Route::post('/store-reservation', [ReservationController::class, 'store'])->name('store-reservation');
        });
    
    Route::get('/reservations/{reservation}', [ReservationController::class, 'show'])->name('reservations.show');
    Route::get('/operator/dashboard', [OperatorDashboardController::class, 'index'])->name('operator.dashboard');
    Route::get('/operator/profile', [ProfileController::class, 'edit'])->name('operator.profile');
    Route::get('/integrator/dashboard', [IntegratorDashboardController::class, 'index'])->name('integrator.dashboard');

    // Payment Routes
    Route::prefix('payment')->name('payment.')->group(function () {
        Route::get('/{reservation}/choose', [PaymentController::class, 'show'])
            ->name('choose')
            ->middleware('can:pay,reservation');
        Route::post('/{reservation}/stripe/initiate', [PaymentController::class, 'payStripe'])
            ->name('stripe.initiate')
            ->middleware('can:pay,reservation');
        Route::post('/stripe/webhook', [PaymentController::class, 'stripeWebhook'])->name('stripe.webhook');
    });

    // CMI server-to-server callback URL
    Route::get('/dashboard/realtime-data', [DashboardController::class, 'realtimeData'])->name('dashboard.realtime-data');

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Groups routes
    Route::prefix('groups')->name('groups.')->group(function () {
        Route::get('/', [GroupController::class, 'index'])->name('index');
        Route::get('/create/step1', [GroupController::class, 'create'])->name('create.step1');
        Route::post('/create/step1', [GroupController::class, 'storeStep1'])->name('store.step1');
        Route::get('/create/step2', [GroupController::class, 'createStep2'])->name('create.step2');
        Route::post('/create/step2', [GroupController::class, 'storeStep2'])->name('store.step2');
        Route::post('/', [GroupController::class, 'store'])->name('store');
        Route::get('/{group}', [GroupController::class, 'show'])->name('show');
        Route::get('/{group}/edit', [GroupController::class, 'edit'])->name('edit');
        Route::put('/{group}', [GroupController::class, 'update'])->name('update');
        Route::delete('/{group}', [GroupController::class, 'destroy'])->name('destroy');
    });

    // Station routes
    Route::prefix('stations')->name('stations.')->group(function () {
        Route::get('/', [StationController::class, 'index'])->name('index');
        Route::get('/create', [StationController::class, 'create'])->name('create');
        Route::post('/', [StationController::class, 'store'])->name('store');
        Route::get('/{station}', [StationController::class, 'show'])->name('show');
        Route::get('/{station}/edit', [StationController::class, 'edit'])->name('edit');
        Route::put('/{station}', [StationController::class, 'update'])->name('update');
        Route::delete('/{station}', [StationController::class, 'destroy'])->name('destroy');
    });

    Route::get('/stations/available-charging-points', [StationController::class, 'getAvailableChargingPoints'])->name('available-charging-points');

    // Partners
    Route::prefix('partners')->name('partners.')->group(function () {
        Route::get('/', [PartnerController::class, 'index'])->name('index');
        Route::get('/create', [PartnerController::class, 'create'])->name('create');
        Route::post('/', [PartnerController::class, 'store'])->name('store');
        Route::get('/{partner}', [PartnerController::class, 'show'])->name('show');
        Route::get('/{partner}/edit', [PartnerController::class, 'edit'])->name('edit');
        Route::put('/{partner}', [PartnerController::class, 'update'])->name('update');
        Route::delete('/{partner}', [PartnerController::class, 'destroy'])->name('destroy');
    });

    // Transactions
    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/', [TransactionController::class, 'index'])->name('index');
        Route::get('/create', [TransactionController::class, 'create'])->name('create');
        Route::post('/', [TransactionController::class, 'store'])->name('store');
        Route::get('/{transaction}', [TransactionController::class, 'show'])->name('show');
        Route::get('/{transaction}/edit', [TransactionController::class, 'edit'])->name('edit');
        Route::put('/{transaction}', [TransactionController::class, 'update'])->name('update');
        Route::delete('/{transaction}', [TransactionController::class, 'destroy'])->name('destroy');
        Route::get('/{transaction}/invoice', [TransactionController::class, 'invoice'])->name('invoice');
        Route::get('/{transaction}/receipt', [TransactionController::class, 'receipt'])->name('receipt');
    });

    // Admin transactions
    Route::prefix('admin/transactions')->name('admin.transactions.')->group(function () {
        Route::get('/', [AdminTransactionController::class, 'index'])->name('index');
        Route::get('/charging-point-creator-fees', [AdminTransactionController::class, 'chargingPointCreatorFees'])->name('charging-point-creator-fees');
        Route::get('/integrator-fees', [AdminTransactionController::class, 'integratorFees'])->name('integrator-fees');
        Route::get('/partner-fees', [AdminTransactionController::class, 'partnerFees'])->name('partner-fees');
        Route::get('/operator-fees', [AdminTransactionController::class, 'operatorFees'])->name('operator-fees');
        Route::get('/{transaction}', [AdminTransactionController::class, 'show'])->name('show');
        Route::get('/{transaction}/edit', [AdminTransactionController::class, 'edit'])->name('edit');
        Route::put('/{transaction}', [AdminTransactionController::class, 'update'])->name('update');
        Route::delete('/{transaction}', [AdminTransactionController::class, 'destroy'])->name('destroy');
        Route::get('/{transaction}/invoice', [AdminTransactionController::class, 'invoice'])->name('invoice');
        Route::get('/{transaction}/receipt', [AdminTransactionController::class, 'receipt'])->name('receipt');
    });

    // Integrators
    Route::prefix('integrators')->name('integrators.')->group(function () {
        Route::get('/', [IntegratorController::class, 'index'])->name('index');
        Route::get('/create', [IntegratorController::class, 'create'])->name('create');
        Route::post('/', [IntegratorController::class, 'store'])->name('store');
        Route::get('/{integrator}', [IntegratorController::class, 'show'])->name('show');
        Route::get('/{integrator}/edit', [IntegratorController::class, 'edit'])->name('edit');
        Route::put('/{integrator}', [IntegratorController::class, 'update'])->name('update');
        Route::delete('/{integrator}', [IntegratorController::class, 'destroy'])->name('destroy');
    });

    // Pricing Plans
    Route::prefix('pricing-plans')->name('pricing-plans.')->group(function () {
        Route::get('/', [PricingPlanController::class, 'index'])->name('index');
        Route::get('/create', [PricingPlanController::class, 'create'])->name('create');
        Route::post('/', [PricingPlanController::class, 'store'])->name('store');
        Route::get('/{pricing_plan}', [PricingPlanController::class, 'show'])->name('show');
        Route::get('/{pricing_plan}/edit', [PricingPlanController::class, 'edit'])->name('edit');
        Route::put('/{pricing_plan}', [PricingPlanController::class, 'update'])->name('update');
        Route::delete('/{pricing_plan}', [PricingPlanController::class, 'destroy'])->name('destroy');
    });

    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::get('/general', [SettingsController::class, 'general'])->name('general');
        Route::put('/general', [SettingsController::class, 'updateGeneral'])->name('general.update');
        Route::get('/profile', [SettingsController::class, 'profile'])->name('profile');
        Route::put('/profile', [SettingsController::class, 'updateProfile'])->name('profile.update');
        Route::get('/security', [SettingsController::class, 'security'])->name('security');
        Route::put('/security', [SettingsController::class, 'updateSecurity'])->name('security.update');
        Route::get('/notifications', [SettingsController::class, 'notifications'])->name('notifications');
        Route::put('/notifications', [SettingsController::class, 'updateNotifications'])->name('notifications.update');
        Route::get('/billing', [SettingsController::class, 'billing'])->name('billing');
        Route::put('/billing', [SettingsController::class, 'updateBilling'])->name('billing.update');
        Route::get('/api', [SettingsController::class, 'api'])->name('api');
        Route::post('/api/token', [SettingsController::class, 'generateApiToken'])->name('api.token');
        Route::delete('/api/token/{tokenId}', [SettingsController::class, 'revokeApiToken'])->name('api.token.revoke');
    });

    // Admin routes
    Route::prefix('admin')->name('admin.')->group(function () {
        // Admin notifications
        Route::get('notifications/unread', [AdminNotificationController::class, 'getUnreadJson'])->name('notifications.unread');
        Route::post('notifications/{id}/mark-read', [AdminNotificationController::class, 'markAsRead'])->name('notifications.mark-read');
        Route::post('notifications/mark-all-read', [AdminNotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');

        // Admin users
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        // Admin business profiles
        Route::get('business-profiles', [AdminBusinessProfileController::class, 'index'])->name('business-profiles.index');
        Route::get('business-profiles/create', [AdminBusinessProfileController::class, 'create'])->name('business-profiles.create');
        Route::post('business-profiles', [AdminBusinessProfileController::class, 'store'])->name('business-profiles.store');
        Route::get('business-profiles/{businessProfile}', [AdminBusinessProfileController::class, 'show'])->name('business-profiles.show');
        Route::get('business-profiles/{businessProfile}/edit', [AdminBusinessProfileController::class, 'edit'])->name('business-profiles.edit');
        Route::put('business-profiles/{businessProfile}', [AdminBusinessProfileController::class, 'update'])->name('business-profiles.update');
        Route::delete('business-profiles/{businessProfile}', [AdminBusinessProfileController::class, 'destroy'])->name('business-profiles.destroy');

        // Admin reservations
        Route::get('reservations', [AdminReservationController::class, 'index'])->name('reservations.index');
        Route::get('reservations/create', [AdminReservationController::class, 'create'])->name('reservations.create');
        Route::post('reservations', [AdminReservationController::class, 'store'])->name('reservations.store');
        Route::get('reservations/{reservation}', [AdminReservationController::class, 'show'])->name('reservations.show');
        Route::get('reservations/{reservation}/edit', [AdminReservationController::class, 'edit'])->name('reservations.edit');
        Route::put('reservations/{reservation}', [AdminReservationController::class, 'update'])->name('reservations.update');
        Route::delete('reservations/{reservation}', [AdminReservationController::class, 'destroy'])->name('reservations.destroy');

        // Admin orders
        Route::get('orders', [OrderAdminController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [OrderAdminController::class, 'show'])->name('orders.show');
    });

    // Business Profiles
    Route::resource('business-profiles', BusinessProfileController::class);

    // Withdrawal requests
    Route::prefix('withdrawal-requests')->name('withdrawal-requests.')->group(function () {
        Route::get('/', [WithdrawalRequestController::class, 'index'])->name('index');
        Route::get('/create', [WithdrawalRequestController::class, 'create'])->name('create');
        Route::post('/', [WithdrawalRequestController::class, 'store'])->name('store');
        Route::get('/{withdrawalRequest}', [WithdrawalRequestController::class, 'show'])->name('show');
        Route::get('/{withdrawalRequest}/edit', [WithdrawalRequestController::class, 'edit'])->name('edit');
        Route::put('/{withdrawalRequest}', [WithdrawalRequestController::class, 'update'])->name('update');
        Route::delete('/{withdrawalRequest}', [WithdrawalRequestController::class, 'destroy'])->name('destroy');
        Route::post('/{withdrawalRequest}/approve', [WithdrawalRequestController::class, 'approve'])->name('approve');
        Route::post('/{withdrawalRequest}/reject', [WithdrawalRequestController::class, 'reject'])->name('reject');
    });

    // Commission plans
    Route::prefix('commission-plans')->name('commission-plans.')->group(function () {
        Route::get('/', [CommissionPlanController::class, 'index'])->name('index');
        Route::get('/create', [CommissionPlanController::class, 'create'])->name('create');
        Route::post('/', [CommissionPlanController::class, 'store'])->name('store');
        Route::get('/{commissionPlan}', [CommissionPlanController::class, 'show'])->name('show');
        Route::get('/{commissionPlan}/edit', [CommissionPlanController::class, 'edit'])->name('edit');
        Route::put('/{commissionPlan}', [CommissionPlanController::class, 'update'])->name('update');
        Route::delete('/{commissionPlan}', [CommissionPlanController::class, 'destroy'])->name('destroy');
    });

    // Commissions
    Route::prefix('commissions')->name('commissions.')->group(function () {
        Route::get('/', [CommissionController::class, 'index'])->name('index');
        Route::get('/{commission}', [CommissionController::class, 'show'])->name('show');
        Route::post('/{commission}/pay', [CommissionController::class, 'pay'])->name('pay');
        Route::post('/batch-pay', [CommissionController::class, 'batchPay'])->name('batch-pay');
    });

    // Financial transactions
    Route::prefix('financial-transactions')->name('financial-transactions.')->group(function () {
        Route::get('/', [FinancialTransactionController::class, 'index'])->name('index');
        Route::get('/create', [FinancialTransactionController::class, 'create'])->name('create');
        Route::post('/', [FinancialTransactionController::class, 'store'])->name('store');
        Route::get('/{financialTransaction}', [FinancialTransactionController::class, 'show'])->name('show');
        Route::get('/{financialTransaction}/edit', [FinancialTransactionController::class, 'edit'])->name('edit');
        Route::put('/{financialTransaction}', [FinancialTransactionController::class, 'update'])->name('update');
        Route::delete('/{financialTransaction}', [FinancialTransactionController::class, 'destroy'])->name('destroy');
    });

    // Refunds
    Route::prefix('refunds')->name('refunds.')->group(function () {
        Route::get('/', [RefundController::class, 'index'])->name('index');
        Route::get('/create', [RefundController::class, 'create'])->name('create');
        Route::post('/', [RefundController::class, 'store'])->name('store');
        Route::get('/{refund}', [RefundController::class, 'show'])->name('show');
        Route::get('/{refund}/edit', [RefundController::class, 'edit'])->name('edit');
        Route::put('/{refund}', [RefundController::class, 'update'])->name('update');
        Route::delete('/{refund}', [RefundController::class, 'destroy'])->name('destroy');
        Route::post('/{refund}/process', [RefundController::class, 'process'])->name('process');
    });

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/transactions', [ReportController::class, 'transactions'])->name('transactions');
        Route::get('/sessions', [ReportController::class, 'sessions'])->name('sessions');
        Route::get('/commissions', [ReportController::class, 'commissions'])->name('commissions');
        Route::get('/financials', [ReportController::class, 'financials'])->name('financials');
        Route::get('/export/{type}', [ReportController::class, 'export'])->name('export');
    });

    // Account
    Route::get('/account', [AccountController::class, 'index'])->name('account.index');
    Route::get('/account/edit', [AccountController::class, 'edit'])->name('account.edit');
    Route::put('/account', [AccountController::class, 'update'])->name('account.update');
    Route::get('/account/password', [AccountController::class, 'editPassword'])->name('account.password.edit');
    Route::put('/account/password', [AccountController::class, 'updatePassword'])->name('account.password.update');
    Route::get('/account/notifications', [AccountController::class, 'notifications'])->name('account.notifications');
    Route::put('/account/notifications', [AccountController::class, 'updateNotifications'])->name('account.notifications.update');
    Route::get('/account/api', [AccountController::class, 'api'])->name('account.api');
    Route::post('/account/api/token', [AccountController::class, 'generateApiToken'])->name('account.api.token');
    Route::delete('/account/api/token/{tokenId}', [AccountController::class, 'revokeApiToken'])->name('account.api.token.revoke');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{notification}', [NotificationController::class, 'show'])->name('notifications.show');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::delete('/notifications', [NotificationController::class, 'destroyAll'])->name('notifications.destroy-all');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    // Offers
    Route::resource('offers', OfferController::class);

    // Test route for QR code
    Route::get('/qrcode-test', [QRCodeTestController::class, 'index'])->name('qrcode.test');
    Route::post('/qrcode-generate', [QRCodeTestController::class, 'generate'])->name('qrcode.generate');

    // Remote control routes
    Route::prefix('remote-control')->name('remote-control.')->group(function () {
        Route::get('/', [RemoteControlController::class, 'index'])->name('index');
        Route::get('/charging-points', [RemoteControlController::class, 'getChargingPoints'])->name('charging-points');
        Route::get('/{chargingPoint}', [RemoteControlController::class, 'show'])->name('show');
        Route::post('/{chargingPoint}/start', [RemoteControlController::class, 'startCharging'])->name('start');
        Route::post('/{chargingPoint}/stop', [RemoteControlController::class, 'stopCharging'])->name('stop');
        Route::post('/{chargingPoint}/unlock', [RemoteControlController::class, 'unlockConnector'])->name('unlock');
        Route::get('/{chargingPoint}/status', [RemoteControlController::class, 'getStatus'])->name('status');
    });

    // Test route for business profile policy
    Route::get('/test-delete-policy/{businessProfile}', function (BusinessProfile $businessProfile) {
        $allowed = Gate::allows('delete', $businessProfile);
        return response()->json(['allowed' => $allowed]);
    })->middleware('auth');
});

// Test route for dashboard
Route::get('/dashboard-test', function() {
    return view('dashboard', [
        'stats' => [
            'totalRecharges' => 1250,
            'rechargesActives' => 8,
            'consommationMoyenne' => 42.5,
            'revenuMensuel' => 12500
        ]
    ]);
})->name('dashboard.test');

// Test route for leaflet
Route::get('/test-leaflet', function () {
    return view('charging-points.test-leaflet');
});

// Test route for session
Route::get('/test-session', function() {
    return response()->json([
        'app_locale' => app()->getLocale(),
        'session_locale' => session('locale'),
        'config_locale' => config('app.locale'),
        'session_all' => session()->all(),
    ]);
});

// Test route for user roles and permissions
Route::get('/debug/user-roles-permissions', function () {
    if (!auth()->check()) {
        return 'Non connecté.';
    }
    $user = auth()->user();
    
    return [
        'user_id' => $user->id,
        'email' => $user->email,
        'roles' => $user->roles->pluck('name'),
        'permissions' => $user->getAllPermissions()->pluck('name'),
        'is_admin' => $user->hasRole('admin'),
        'is_integrator' => $user->hasRole('integrator'),
        'is_partner' => $user->hasRole('partner'),
        'is_operator' => $user->hasRole('operator'),
    ];
});

// Test route for charging session
Route::get('/test-charging-session', function () {
    return view('charging-sessions.test');
});
