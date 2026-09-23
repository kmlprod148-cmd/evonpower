<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
                ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store'])
                ->middleware('throttle:10,1');

    Route::get('register/verify', [RegisteredUserController::class, 'showOtp'])
                ->name('register.verify.show');

    Route::post('register/verify', [RegisteredUserController::class, 'verifyOtp'])
                ->middleware('throttle:10,1')
                ->name('register.verify');

    Route::post('register/resend', [RegisteredUserController::class, 'resendOtp'])
                ->middleware('throttle:5,1')
                ->name('register.resend');

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
                ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    // Back-office login — vue distincte avec son propre thème (auth.login-staff)
    // pour que les administrateurs / opérateurs / intégrateurs / partenaires
    // n'aient pas à passer par le formulaire client. Le POST réutilise
    // l'endpoint partagé `login.admin` ci-dessous : la logique d'auth
    // (LoginRequest::authenticateAdmin + LoginRolePolicy) reste centralisée.
    // URL is configurable via ADMIN_PATH so it can be hidden behind a slug.
    Route::get((config('admin.path') ?: 'admin').'/login', [AuthenticatedSessionController::class, 'showStaffLoginForm'])
                ->name('admin.login');

    Route::post('login/customer', [AuthenticatedSessionController::class, 'store'])
                ->name('login.customer');

    Route::get('login/customer/verify', [AuthenticatedSessionController::class, 'showCustomerOtp'])
                ->name('login.customer.verify.show');

    Route::post('login/customer/verify', [AuthenticatedSessionController::class, 'verifyCustomerOtp'])
                ->middleware('throttle:10,1')
                ->name('login.customer.verify');

    Route::post('login/customer/resend', [AuthenticatedSessionController::class, 'resendCustomerOtp'])
                ->middleware('throttle:5,1')
                ->name('login.customer.resend');

    Route::post('login/admin', [AuthenticatedSessionController::class, 'store'])
                ->name('login.admin');

    // Route pour l'auto-login avec les comptes de démo
    Route::get('demo-login/{role}', [AuthenticatedSessionController::class, 'autoLogin'])
                ->name('demo.login');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
                ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
                ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
                ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
                ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', [EmailVerificationPromptController::class, '__invoke'])
                ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
                ->middleware(['signed', 'throttle:6,1'])
                ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
                ->middleware('throttle:6,1')
                ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
                ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
                ->name('logout');
});
