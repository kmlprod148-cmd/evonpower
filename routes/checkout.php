<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Public\CheckoutController;

/*
|--------------------------------------------------------------------------
| Public QR Checkout Routes
|--------------------------------------------------------------------------
|
| These routes handle the 3-step QR checkout flow for public charging.
| No authentication required - designed for guest users scanning QR codes.
|
| Routes:
| - GET  /pay/{slug}              → Step 1: Choose duration
| - POST /pay/{slug}/calculate    → AJAX: Calculate price
| - POST /pay/{slug}/session      → Step 2: Create session with personal info
| - POST /pay/{slug}/pay          → Step 3: Initiate payment
| - GET  /pay/{slug}/confirm/{id}→ Payment return handler
| - GET  /pay/{slug}/receipt/{id}→ Receipt page
| - GET  /pay/{slug}/cancel/{id}  → Payment cancel handler
| - POST /pay/{slug}/callback     → Payment gateway webhook (CMI/Stripe)
| - GET  /privacy-policy          → Privacy policy page
|
*/

// Privacy policy route (no rate limit needed)
Route::get('/privacy-policy', [CheckoutController::class, 'privacyPolicy'])
    ->name('privacy-policy');

// Rate limit: 60 requests per minute per IP
Route::middleware(['throttle:60,1'])->group(function () {
    
    // Step 1: Show checkout page with duration selector
    Route::get('/pay/{slug}', [CheckoutController::class, 'show'])
        ->name('public.checkout.show');
    
    // AJAX: Calculate price based on duration
    Route::post('/pay/{slug}/calculate', [CheckoutController::class, 'calculate'])
        ->name('public.checkout.calculate');

    // Step 2: Show user info form
    Route::get('/pay/{slug}/info', [CheckoutController::class, 'userInfo'])
        ->name('public.checkout.user-info');
    
    // Step 2: Create checkout session with personal info
    Route::post('/pay/{slug}/session', [CheckoutController::class, 'createSession'])
        ->name('public.checkout.session');
    
    // Step 3: Initiate payment
    Route::post('/pay/{slug}/pay', [CheckoutController::class, 'pay'])
        ->name('public.checkout.pay');
    
    // Payment confirmation/return handler
    Route::get('/pay/{slug}/confirm/{id}', [CheckoutController::class, 'confirm'])
        ->name('public.checkout.confirm');
    
    // Payment receipt page
    Route::get('/pay/{slug}/receipt/{id}', [CheckoutController::class, 'receipt'])
        ->name('public.checkout.receipt');
    
    // Payment cancel handler
    Route::get('/pay/{slug}/cancel/{id}', [CheckoutController::class, 'cancel'])
        ->name('public.checkout.cancel');
    
    // Payment gateway webhook/callback
    Route::post('/pay/{slug}/callback', [CheckoutController::class, 'handleCallback'])
        ->name('public.checkout.callback');
});
