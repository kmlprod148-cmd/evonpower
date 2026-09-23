<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CmiPaymentController;

Route::post('/payment/cmi/callback', [CmiPaymentController::class, 'handleCallback'])->name('payment.cmi.callback');