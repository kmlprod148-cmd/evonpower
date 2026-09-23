<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminNotificationController;
use App\Http\Controllers\BalanceController;
use App\Http\Controllers\BusinessProfileController;
use App\Http\Controllers\CommissionController;
use App\Http\Controllers\CommissionPlanController;
use App\Http\Controllers\ChargingStartController;
use App\Http\Controllers\CreditRechargeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinancialTransactionController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\IntegratorController;
use App\Http\Controllers\IntegratorDashboardController;
use App\Http\Controllers\IntegratorOperatorController;
use App\Http\Controllers\OperatorChargingPointController;
use App\Http\Controllers\OperatorPricingPlanController;
use App\Http\Controllers\OperatorReservationController;
use App\Http\Controllers\OperatorTransactionHistoryController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\OperatorDashboardController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PricingPlanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QRCodeTestController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\RemoteControlController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ChargingPointOfferController;
use App\Http\Controllers\Client\ClientChargingSessionController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\StationController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WithdrawalRequestController;
use App\Models\BusinessProfile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

// Authentication routes (login, register, password reset, etc.)
require __DIR__.'/auth.php';

// Public checkout routes (QR code / pay flow)
require __DIR__.'/checkout.php';

// ── Public webhook / callback routes (no auth, must be accessible by payment gateways) ──

// Generic CMI callback used by CMICreditRechargeService (oid in POST body identifies the recharge)
Route::post('/credit-recharge/cmi/callback', [CreditRechargeController::class, 'handleCmiCallbackGeneric'])
    ->name('credit-recharge.cmi.callback.generic')
    ->withoutMiddleware(['auth', 'verified']);

// Per-recharge CMI callback (used when the recharge ID is in the URL)
Route::post('/credit-recharge/{recharge}/cmi/callback', [CreditRechargeController::class, 'handleCmiCallback'])
    ->name('credit-recharge.cmi.callback')
    ->withoutMiddleware(['auth', 'verified']);

Route::post('/credit-recharge/stripe/webhook', [CreditRechargeController::class, 'stripeWebhook'])
    ->name('credit-recharge.stripe.webhook')
    ->withoutMiddleware(['auth', 'verified']);

// Public Stripe webhook for subscriptions (no auth)
Route::post('/subscriptions/stripe/webhook', [SubscriptionController::class, 'stripeWebhook'])
    ->name('subscriptions.stripe.webhook')
    ->withoutMiddleware(['auth', 'verified']);

// Public CMI callback for subscriptions (no auth, called server-to-server by CMI)
Route::post('/subscriptions/{subscription}/cmi/callback', [SubscriptionController::class, 'handleCmiCallback'])
    ->name('subscriptions.cmi.callback')
    ->withoutMiddleware(['auth', 'verified']);

// Language routes
Route::middleware(['web'])->group(function () {
    Route::get('/lang/{locale}', [\App\Http\Controllers\LanguageController::class, 'switchLanguage'])->name('lang.switch');
    Route::get('/lang-test', [\App\Http\Controllers\LanguageController::class, 'testLanguage'])->name('lang.test');
});

// Public reservation offer page (no auth required)
Route::get('/offer/{id}', [OfferController::class, 'show'])
    ->name('public.charging-point.offer')
    ->withoutMiddleware(['auth', 'verified']);

Route::get('/offer/{id}/reservation', [ChargingPointOfferController::class, 'showReservationOfferPublic'])
    ->name('public.charging-point.offer.reservation')
    ->withoutMiddleware(['auth', 'verified']);

Route::post('/public/charging-point/{charging_point_id}/offer/store-reservation', [ReservationController::class, 'store'])
    ->name('public.charging-point.offer.store-reservation')
    ->middleware(['auth', 'phone.verified']);

// Entry point for the offer → register → Stripe → Steve RemoteStart flow.
// Unauthenticated visitors are stashed with pending_charging_point_id and sent to
// /register; RegisteredUserController resumes them on the client.charging.offer
// route after account creation. Authenticated visitors are redirected directly.
Route::get('/public/charging-points/{id}/offer/view', [\App\Http\Controllers\PublicChargingOfferWebController::class, 'showOffer'])
    ->name('public.charging-point.offer.view.public')
    ->withoutMiddleware(['auth', 'verified']);

// Guest reservation routes removed: registration + phone verification are now mandatory
// for all reservation flows. Any external link to /guest-reservations should redirect
// users through /register. ReservationController::guestIndex/guestSearch are now
// orphaned and can be deleted in a follow-up cleanup once links are confirmed dead.
Route::redirect('/guest-reservations', '/register')
    ->name('guest-reservations.index')
    ->withoutMiddleware(['auth', 'verified']);

Route::redirect('/guest/reservations', '/register')
    ->name('guest.reservations.index')
    ->withoutMiddleware(['auth', 'verified']);

// Protected all-in-one deployment assistant.
Route::prefix('setup')->name('setup.')->group(function () {
    Route::get('/', [SetupController::class, 'index'])->name('index');
    Route::post('/claim', [SetupController::class, 'claim'])->name('claim');
    Route::post('/login', [SetupController::class, 'login'])->name('login');
    Route::post('/logout', [SetupController::class, 'logout'])->name('logout');
    Route::post('/preflight', [SetupController::class, 'preflight'])->name('preflight');
    Route::post('/run', [SetupController::class, 'run'])->name('run');
    // Wizard API endpoints
    Route::get('/check', [SetupController::class, 'check'])->name('check');
    Route::post('/validate/{step}', [SetupController::class, 'validateStep'])->name('validate-step');
    Route::post('/stream', [SetupController::class, 'stream'])->name('stream');
})->withoutMiddleware([
    \App\Http\Middleware\DetectClient::class,
    \App\Http\Middleware\SetCurrencyMiddleware::class,
    \App\Http\Middleware\HandleInertiaRequests::class,
]);

// Main Application Routes
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/realtime-data', [DashboardController::class, 'realtimeData'])->name('dashboard.realtime-data');

    // Client dashboard
    Route::get('/client/dashboard', [\App\Http\Controllers\Client\ClientDashboardController::class, 'index'])->name('dashboard.client');
    Route::get('/client/dashboard/statistics', [\App\Http\Controllers\Client\ClientDashboardController::class, 'statistics'])->name('dashboard.client.statistics');
    Route::get('/balances', [BalanceController::class, 'index'])->name('balances.index');
    Route::prefix('client/charging-sessions')->name('client.charging-sessions.')->group(function () {
        Route::get('/active', [ClientChargingSessionController::class, 'active'])->name('active');
        Route::get('/{session}/status', [ClientChargingSessionController::class, 'status'])->name('status');
        Route::post('/{session}/stop', [ClientChargingSessionController::class, 'stop'])->name('stop');
    });

    // Offer → Stripe → Steve RemoteStart flow for authenticated users.
    // Entry point: PublicChargingOfferWebController@showOffer redirects unauthenticated
    // visitors to /register (which resumes via pending_charging_point_id) and
    // authenticated visitors to client.charging.offer below.
    Route::prefix('client/charging')->name('client.charging.')->group(function () {
        Route::get('/{id}', [\App\Http\Controllers\Client\ClientChargingPaymentController::class, 'showOffer'])->name('offer');
        Route::post('/{id}/pay', [\App\Http\Controllers\Client\ClientChargingPaymentController::class, 'initiatePayment'])
            ->middleware('phone.verified')
            ->name('pay');
        Route::get('/{id}/confirm/{reservationId}', [\App\Http\Controllers\Client\ClientChargingPaymentController::class, 'confirmPayment'])->name('confirm');
        Route::get('/{id}/success/{reservationId}', [\App\Http\Controllers\Client\ClientChargingPaymentController::class, 'success'])->name('success');
        Route::get('/{id}/status/{reservationId}', [\App\Http\Controllers\Client\ClientChargingPaymentController::class, 'chargingStatus'])->name('status');
    });

    // Live charging session screen and polling endpoints.
    Route::prefix('charging/session')->name('charging.session.')->group(function () {
        Route::get('/{reservation}', [ChargingStartController::class, 'sessionLive'])->name('live');
        Route::get('/{reservation}/status', [ChargingStartController::class, 'sessionStatus'])->name('status');
        Route::post('/{reservation}/stop', [ChargingStartController::class, 'stopSession'])->name('stop');
    });

    // Charging Points Routes (consolidated in charging-points.php)
    require __DIR__.'/charging-points.php';

    // Support
    Route::get('/support/contact', function () {
        return view('contact');
    })->name('support.contact');

    // Reservations
    Route::get('/reservations', [ReservationController::class, 'index'])->name('reservations.index');
    Route::get('/reservations/{reservation}', [ReservationController::class, 'show'])->name('reservations.show');

    // Post-reservation confirmation page. Public (no auth) because guests who reserved
    // via the public offer flow may land here without a session, and ReservationController
    // ::store() / thankYou() handle the unauthenticated case (renders 'reservation_not_found'
    // when the row can't be located via findReservationForThankYou).
    Route::get('/reservations/thank-you/{reservation}', [ReservationController::class, 'thankYou'])
        ->name('reservations.thank-you')
        ->withoutMiddleware('auth');
    Route::get('/reservations/thank-you/{reservation}/status', [ReservationController::class, 'thankYouStatus'])
        ->name('reservations.thank-you.status')
        ->withoutMiddleware('auth');

    // Role-specific dashboards
    Route::get('/operator/dashboard', [OperatorDashboardController::class, 'index'])->name('operator.dashboard');
    Route::get('/operator/profile', [ProfileController::class, 'edit'])->name('operator.profile');
    Route::get('/integrator/dashboard', [IntegratorDashboardController::class, 'index'])->name('integrator.dashboard');

    // ── Operator: reservations (all payment methods: Stripe, CMI, credit, offline) ──
    Route::prefix('operator/reservations')->name('operator.reservations.')->group(function () {
        Route::get('/', [OperatorReservationController::class, 'index'])->name('index');
        Route::get('/{reservation}', [OperatorReservationController::class, 'show'])->name('show');
        Route::post('/{reservation}/approve', [OperatorReservationController::class, 'approve'])->name('approve');
        Route::post('/{reservation}/reject', [OperatorReservationController::class, 'reject'])->name('reject');
    });

    // ── Operator: transaction history (Stripe, CMI, credit — all successful transactions processed) ──
    Route::prefix('operator/transaction-history')->name('operator.transaction-history.')->group(function () {
        Route::get('/', [OperatorTransactionHistoryController::class, 'index'])->name('index');
        Route::get('/export', [OperatorTransactionHistoryController::class, 'export'])->name('export');
        Route::get('/{id}/details', [OperatorTransactionHistoryController::class, 'details'])->name('details');
    });

    // Operator charging point management
    Route::prefix('operator/charging-points')->name('operator.charging-points.')->group(function () {
        Route::get('/', [OperatorChargingPointController::class, 'index'])->name('index');
        Route::get('/create', [OperatorChargingPointController::class, 'create'])->name('create');
        Route::post('/', [OperatorChargingPointController::class, 'store'])->name('store');
        Route::get('/{chargingPoint}', [OperatorChargingPointController::class, 'show'])->name('show');
        Route::get('/{chargingPoint}/edit', [OperatorChargingPointController::class, 'edit'])->name('edit');
        Route::put('/{chargingPoint}', [OperatorChargingPointController::class, 'update'])->name('update');
        Route::delete('/{chargingPoint}', [OperatorChargingPointController::class, 'destroy'])->name('destroy');
        Route::post('/{chargingPoint}/toggle-status', [OperatorChargingPointController::class, 'toggleStatus'])->name('toggle-status');
    });

    // Operator pricing plan management
    Route::prefix('operator/pricing-plans')->name('operator.pricing-plans.')->group(function () {
        Route::get('/', [OperatorPricingPlanController::class, 'index'])->name('index');
        Route::get('/create', [OperatorPricingPlanController::class, 'create'])->name('create');
        Route::post('/', [OperatorPricingPlanController::class, 'store'])->name('store');
        Route::get('/{pricingPlan}', [OperatorPricingPlanController::class, 'show'])->name('show');
        Route::get('/{pricingPlan}/edit', [OperatorPricingPlanController::class, 'edit'])->name('edit');
        Route::put('/{pricingPlan}', [OperatorPricingPlanController::class, 'update'])->name('update');
        Route::delete('/{pricingPlan}', [OperatorPricingPlanController::class, 'destroy'])->name('destroy');
        Route::post('/{pricingPlan}/toggle-status', [OperatorPricingPlanController::class, 'toggleStatus'])->name('toggle-status');
    });

    // Integrator operator management
    Route::prefix('integrator/operators')->name('integrator.operators.')->group(function () {
        Route::get('/', [IntegratorOperatorController::class, 'index'])->name('index');
        Route::get('/create', [IntegratorOperatorController::class, 'create'])->name('create');
        Route::post('/', [IntegratorOperatorController::class, 'store'])->name('store');
        Route::get('/{operator}', [IntegratorOperatorController::class, 'show'])->name('show');
        Route::get('/{operator}/edit', [IntegratorOperatorController::class, 'edit'])->name('edit');
        Route::put('/{operator}', [IntegratorOperatorController::class, 'update'])->name('update');
        Route::delete('/{operator}', [IntegratorOperatorController::class, 'destroy'])->name('destroy');
        Route::post('/{operator}/activate', [IntegratorOperatorController::class, 'activate'])->name('activate');
        Route::post('/{operator}/deactivate', [IntegratorOperatorController::class, 'deactivate'])->name('deactivate');
    });

    // Payment Routes
    Route::prefix('payment')->name('payment.')->group(function () {
        Route::get('/{reservation}/choose', [PaymentController::class, 'show'])
            ->name('choose')
            ->middleware(['can:pay,reservation', 'phone.verified']);
        Route::post('/{reservation}/stripe/initiate', [PaymentController::class, 'payStripe'])
            ->name('stripe.initiate')
            ->middleware(['can:pay,reservation', 'phone.verified']);
        Route::post('/stripe/webhook', [PaymentController::class, 'stripeWebhook'])->name('stripe.webhook');

        // Public payment-gateway initiation from the offer/reservation page (mobile + desktop).
        // The page creates a reservation first, then posts the returned reservation_id here.
        Route::post('/cmi/initiate', [\App\Http\Controllers\SecurePaymentController::class, 'initiateCmiPayment'])
            ->name('cmi.initiate')
            ->withoutMiddleware('auth');
        Route::post('/stripe/initiate/public', [\App\Http\Controllers\SecurePaymentController::class, 'initiateStripePayment'])
            ->name('stripe.initiate.public')
            ->withoutMiddleware('auth');
    });

    // Credits — wallet top-up endpoints used by the offer-reservation page and credit dashboards.
    // CreditController::store enforces role checks (client / credit-manager) internally via Auth::user(),
    // so the auth middleware from the parent group is sufficient.
    Route::prefix('credits')->name('credits.')->group(function () {
        Route::post('/', [\App\Http\Controllers\CreditController::class, 'store'])->name('store');
    });

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::patch('/profile/payment-method', [ProfileController::class, 'updatePaymentMethod'])
        ->name('profile.payment-method.update');

    // Groups routes
    Route::prefix('groups')->name('groups.')->group(function () {
        Route::get('/', [GroupController::class, 'index'])->name('index');
        Route::get('/create/step1', [GroupController::class, 'create'])->name('create.step1');
        Route::post('/create/step1', [GroupController::class, 'storeStep1'])->name('store.step1');
        Route::get('/create/step2', [GroupController::class, 'createStep2'])->name('create.step2');
        Route::post('/create/step2', [GroupController::class, 'storeStep2'])->name('store.step2');
        Route::post('/', [GroupController::class, 'store'])->name('store');
        Route::get('/{group}/charging-points', [GroupController::class, 'manageChargingPoints'])->name('manage-charging-points');
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
        Route::post('/{station}/add-charging-points', [StationController::class, 'addChargingPoints'])->name('add-charging-points');
        Route::delete('/{station}/charging-points/{chargingPoint}', [StationController::class, 'removeChargingPoint'])->name('remove-charging-point');
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

    // ─── Transactions (user-facing) ───────────────────────────────────────────
    // IMPORTANT: specific sub-routes MUST come BEFORE the {transaction} wildcard
    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/', [TransactionController::class, 'index'])->name('index');
        Route::get('/export', [TransactionController::class, 'export'])->name('export');
        Route::get('/create', [TransactionController::class, 'create'])->name('create');
        Route::post('/', [TransactionController::class, 'store'])->name('store');
        Route::get('/{transaction}', [TransactionController::class, 'show'])->name('show');
        Route::get('/{transaction}/edit', [TransactionController::class, 'edit'])->name('edit');
        Route::put('/{transaction}', [TransactionController::class, 'update'])->name('update');
        Route::delete('/{transaction}', [TransactionController::class, 'destroy'])->name('destroy');
        Route::get('/{transaction}/invoice', [TransactionController::class, 'invoice'])->name('invoice');
        Route::get('/{transaction}/receipt', [TransactionController::class, 'receipt'])->name('receipt');
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

    // Legacy alias kept for the advanced pricing-plan builder and older links.
    Route::prefix('plans')->name('plans.')->group(function () {
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
        Route::post('/theme', [SettingsController::class, 'updateTheme'])->name('theme.update');
        Route::get('/security', [SettingsController::class, 'security'])->name('security');
        Route::put('/security', [SettingsController::class, 'updateSecurity'])->name('security.update');
        Route::put('/password', [SettingsController::class, 'updatePassword'])->name('update-password');
        Route::get('/notifications', [SettingsController::class, 'notifications'])->name('notifications');
        Route::put('/notifications', [SettingsController::class, 'updateNotifications'])->name('notifications.update');
        Route::get('/billing', [SettingsController::class, 'billing'])->name('billing');
        Route::put('/billing', [SettingsController::class, 'updateBilling'])->name('billing.update');
        Route::post('/billing/default/{method}', [SettingsController::class, 'setDefaultPaymentMethod'])->name('billing.default');
        Route::get('/api', [SettingsController::class, 'api'])->name('api');
        Route::put('/api', [SettingsController::class, 'apiUpdate'])->name('api.update');
        Route::post('/api/token', [SettingsController::class, 'generateApiToken'])->name('api.token');
        Route::delete('/api/token/{tokenId}', [SettingsController::class, 'revokeApiToken'])->name('api.token.revoke');
        Route::get('/commission-plans', [SettingsController::class, 'commissionPlans'])->name('commission-plans');
        Route::get('/commission-dashboard', [SettingsController::class, 'commissionDashboard'])->name('commission-dashboard');
    });

    // Admin notifications (JSON endpoint used by JS)
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('notifications/unread', [AdminNotificationController::class, 'getUnreadJson'])->name('notifications.api.unread');
        Route::post('notifications/{id}/mark-read', [AdminNotificationController::class, 'markAsRead'])->name('notifications.mark-read');
        Route::post('notifications/mark-all-read', [AdminNotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    });

    // Business Profiles
    Route::get('business-profiles/{businessProfile}/manage-partner-rates', [BusinessProfileController::class, 'managePartnerRates'])
        ->name('business-profiles.manage-partner-rates');
    Route::post('business-profiles/{businessProfile}/apply-partner-rates', [BusinessProfileController::class, 'applyPartnerRates'])
        ->name('business-profiles.apply-partner-rates');
    Route::resource('business-profiles', BusinessProfileController::class);

    // Business Profile Applications (for super admin management)
    Route::prefix('admin/business-profile-applications')->name('admin.business-profile-applications.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\BusinessProfileApplicationController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Admin\BusinessProfileApplicationController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Admin\BusinessProfileApplicationController::class, 'store'])->name('store');
        Route::get('/{application}', [App\Http\Controllers\Admin\BusinessProfileApplicationController::class, 'show'])->name('show');
        Route::post('/{application}/approve', [App\Http\Controllers\Admin\BusinessProfileApplicationController::class, 'approve'])->name('approve');
        Route::post('/{application}/reject', [App\Http\Controllers\Admin\BusinessProfileApplicationController::class, 'reject'])->name('reject');
        Route::post('/batch-approve', [App\Http\Controllers\Admin\BusinessProfileApplicationController::class, 'batchApprove'])->name('batch-approve');
        // Direct attachment routes
        Route::post('/{businessProfile}/attach-to-integrator', [App\Http\Controllers\Admin\BusinessProfileApplicationController::class, 'attachToIntegrator'])->name('attach-to-integrator');
        Route::post('/{businessProfile}/attach-to-partner', [App\Http\Controllers\Admin\BusinessProfileApplicationController::class, 'attachToPartner'])->name('attach-to-partner');
        Route::post('/{businessProfile}/attach-to-user', [App\Http\Controllers\Admin\BusinessProfileApplicationController::class, 'attachToUser'])->name('attach-to-user');
        Route::post('/{businessProfile}/detach-from-integrator', [App\Http\Controllers\Admin\BusinessProfileApplicationController::class, 'detachFromIntegrator'])->name('detach-from-integrator');
        Route::post('/{businessProfile}/detach-from-partner', [App\Http\Controllers\Admin\BusinessProfileApplicationController::class, 'detachFromPartner'])->name('detach-from-partner');
        Route::post('/{businessProfile}/detach-from-user', [App\Http\Controllers\Admin\BusinessProfileApplicationController::class, 'detachFromUser'])->name('detach-from-user');
    });

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

    // ── Credit Recharge routes ─────────────────────────────────────────────────
    Route::prefix('credit-recharge')->name('credit-recharge.')->group(function () {
        Route::get('/', [CreditRechargeController::class, 'index'])->name('index');
        Route::post('/', [CreditRechargeController::class, 'store'])->name('store');
        Route::get('/{creditRecharge}', [CreditRechargeController::class, 'show'])->name('show');
        Route::post('/{creditRecharge}/cancel', [CreditRechargeController::class, 'cancel'])->name('cancel');

        // CMI: auto-submit form page (authenticated user is redirected here)
        Route::get('/{recharge}/cmi/send', [CreditRechargeController::class, 'sendCmiPayment'])->name('cmi.send');
        // CMI: return page after payment (user browser redirect from CMI)
        Route::match(['get', 'post'], '/{recharge}/cmi/return', [CreditRechargeController::class, 'handleCmiReturn'])->name('cmi.return');

        // Stripe success / cancel (user browser redirect from Stripe)
        Route::get('/stripe/success', [CreditRechargeController::class, 'stripeSuccess'])->name('stripe.success');
        Route::get('/stripe/cancel', [CreditRechargeController::class, 'stripeCancel'])->name('stripe.cancel');

        // Thank-you page + live Steve session status (polling JSON)
        Route::get('/thank-you', [CreditRechargeController::class, 'thankYou'])->name('thank-you');
        Route::get('/session-status', [CreditRechargeController::class, 'chargingSessionStatus'])->name('session-status');
    });

    // ── Subscription / Abonnements routes ─────────────────────────────────────
    Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
        Route::get('/', [SubscriptionController::class, 'index'])->name('index');
        Route::get('/plans', [SubscriptionController::class, 'plans'])->name('plans');
        Route::get('/plans/{plan}', [SubscriptionController::class, 'showPlan'])->name('plans.show');
        Route::get('/plans/{plan}/checkout', [SubscriptionController::class, 'checkout'])->name('checkout');
        Route::post('/plans/{plan}/subscribe', [SubscriptionController::class, 'subscribe'])->name('subscribe');

        Route::get('/{subscription}', [SubscriptionController::class, 'show'])->name('show');
        Route::post('/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('cancel');
        Route::post('/{subscription}/renew', [SubscriptionController::class, 'renew'])->name('renew');
        Route::get('/{subscription}/usage', [SubscriptionController::class, 'usage'])->name('usage');

        // CMI: auto-submit form page
        Route::get('/{subscription}/cmi/send', [SubscriptionController::class, 'sendCmiPayment'])->name('cmi.send');
        // CMI: return page after payment (browser redirect)
        Route::match(['get', 'post'], '/{subscription}/cmi/return', [SubscriptionController::class, 'handleCmiReturn'])->name('cmi.return');

        // Stripe success / cancel (browser redirect from Stripe hosted checkout)
        Route::get('/stripe/success', [SubscriptionController::class, 'stripeSuccess'])->name('stripe.success');
        Route::get('/stripe/cancel', [SubscriptionController::class, 'stripeCancel'])->name('stripe.cancel');
    });

    // Offers
    Route::resource('offers', OfferController::class);

    // QR code test
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

    // Global Search
    Route::get('/search', [\App\Http\Controllers\GlobalSearchController::class, 'search'])->name('global-search');

    // Business profile policy debug (dev only)
    Route::get('/test-delete-policy/{businessProfile}', function (BusinessProfile $businessProfile) {
        $allowed = Gate::allows('delete', $businessProfile);

        return response()->json(['allowed' => $allowed]);
    })->middleware('auth');
});

// Test / debug routes
Route::get('/test', function () {
    return 'Test route works!';
})->name('test');
Route::get('/test-session', function () {
    return response()->json([
        'app_locale' => app()->getLocale(),
        'session_locale' => session('locale'),
        'config_locale' => config('app.locale'),
        'session_all' => session()->all(),
    ]);
});
Route::get('/debug/user-roles-permissions', function () {
    if (! auth()->check()) {
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
