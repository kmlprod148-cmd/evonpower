@extends('layouts.guest')

@section('title', __('Checkout - Continue'))

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4">

        <!-- Progress Steps -->
        <div class="mb-8">
            <div class="flex items-center justify-center">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-green-600 text-white flex items-center justify-center font-bold">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <span class="ml-2 font-medium text-gray-900">{{ __('Duration') }}</span>
                </div>
                <div class="w-16 h-1 bg-green-500 mx-4"></div>
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-green-600 text-white flex items-center justify-center font-bold">2</div>
                    <span class="ml-2 font-medium text-gray-900">{{ __('Information') }}</span>
                </div>
                <div class="w-16 h-1 bg-gray-300 mx-4"></div>
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-gray-300 text-gray-600 flex items-center justify-center font-bold">3</div>
                    <span class="ml-2 font-medium text-gray-500">{{ __('Payment') }}</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-4">

                {{-- ──────────────────────────────────────────────
                     SECTION 1: Account choice cards
                ────────────────────────────────────────────── --}}
                <div id="choice-section" class="bg-white rounded-xl shadow-sm p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-2">{{ __('How would you like to continue?') }}</h2>
                    <p class="text-sm text-gray-500 mb-6">{{ __('Choose an option below to proceed to payment.') }}</p>

                    <div class="space-y-3">

                        <!-- Login card -->
                        <a href="{{ route('public.checkout.user-info', ['slug' => $slug, 'action' => 'login', 'data' => $rawData]) }}"
                           class="flex items-center gap-4 p-4 border-2 border-gray-200 rounded-xl hover:border-green-500 hover:bg-green-50 transition-all group">
                            <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center shrink-0 group-hover:bg-green-200 transition-colors">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-gray-900">{{ __('I already have an account') }}</p>
                                <p class="text-sm text-gray-500">{{ __('Log in to pay instantly — no form to fill out.') }}</p>
                            </div>
                            <svg class="w-5 h-5 text-gray-400 group-hover:text-green-600 shrink-0 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>

                        <!-- Register card -->
                        <a href="{{ route('public.checkout.user-info', ['slug' => $slug, 'action' => 'register', 'data' => $rawData]) }}"
                           class="flex items-center gap-4 p-4 border-2 border-gray-200 rounded-xl hover:border-blue-500 hover:bg-blue-50 transition-all group">
                            <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center shrink-0 group-hover:bg-blue-200 transition-colors">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-gray-900">{{ __('Create an account') }}</p>
                                <p class="text-sm text-gray-500">{{ __('Register once, then return here automatically to pay.') }}</p>
                            </div>
                            <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-600 shrink-0 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>

                        <!-- Guest card (toggle) -->
                        <button type="button" id="guest-toggle-btn"
                                class="w-full flex items-center gap-4 p-4 border-2 border-gray-200 rounded-xl hover:border-orange-400 hover:bg-orange-50 transition-all group text-left">
                            <div class="w-12 h-12 rounded-full bg-orange-100 flex items-center justify-center shrink-0 group-hover:bg-orange-200 transition-colors">
                                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-gray-900">{{ __('Continue as guest') }}</p>
                                <p class="text-sm text-gray-500">{{ __('Just your name and email — quick one-time payment.') }}</p>
                            </div>
                            <svg id="guest-toggle-icon" class="w-5 h-5 text-gray-400 group-hover:text-orange-500 shrink-0 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- ──────────────────────────────────────────────
                     SECTION 2: Guest minimal form (hidden by default)
                ────────────────────────────────────────────── --}}
                <div id="guest-section" class="hidden bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-1">{{ __('Guest checkout') }}</h3>
                    <p class="text-sm text-gray-500 mb-5">{{ __('Just a few details and you\'re good to go.') }}</p>

                    <form id="guest-form" novalidate>
                        @csrf
                        <!-- Hidden checkout data -->
                        <input type="hidden" name="charge_point_id"  value="{{ $checkoutData['charge_point_id']  ?? '' }}">
                        <input type="hidden" name="duration_minutes" value="{{ $checkoutData['duration_minutes'] ?? '' }}">
                        <input type="hidden" name="estimated_kwh"    value="{{ $checkoutData['estimated_kwh']    ?? '' }}">
                        <input type="hidden" name="total_price"      value="{{ $checkoutData['total_price']      ?? '' }}">
                        <input type="hidden" name="currency"         value="{{ $checkoutData['currency']         ?? 'MAD' }}">
                        <input type="hidden" name="payment_mode"     value="prepaid">

                        <!-- Name -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">{{ __('First Name') }} *</label>
                                <input type="text" name="first_name" id="first_name" required autocomplete="given-name"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm">
                            </div>
                            <div>
                                <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Last Name') }} *</label>
                                <input type="text" name="last_name" id="last_name" required autocomplete="family-name"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm">
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="mb-4">
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Email') }} *</label>
                            <input type="email" name="email" id="email" required autocomplete="email"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm"
                                placeholder="you@example.com">
                        </div>

                        <!-- Phone (optional) -->
                        <div class="mb-5">
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Phone') }} <span class="text-gray-400 font-normal">({{ __('optional') }})</span></label>
                            <input type="tel" name="phone" id="phone" autocomplete="tel"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm"
                                placeholder="+212 6xx xxx xxx">
                        </div>

                        <!-- GDPR consent -->
                        <div class="mb-5 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <label class="flex items-start cursor-pointer gap-2">
                                <input type="checkbox" name="gdpr_consent" id="gdpr_consent" value="1" required
                                    class="mt-0.5 w-4 h-4 shrink-0 text-green-600 border-gray-300 rounded focus:ring-green-500">
                                <span class="text-sm text-gray-700 leading-snug">
                                    {!! __('I accept the <a href=":url" target="_blank" class="text-green-600 underline hover:text-green-700">privacy policy</a> and consent to my personal data being processed for this charging session.', [
                                        'url' => route('privacy-policy')
                                    ]) !!}
                                    <span class="text-red-500">*</span>
                                </span>
                            </label>
                        </div>

                        <!-- Error display -->
                        <div id="guest-error" class="hidden mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600"></div>

                        <!-- Buttons -->
                        <div class="flex gap-3">
                            <button type="button" id="guest-cancel-btn"
                                class="px-5 py-3 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50">
                                {{ __('Back') }}
                            </button>
                            <button type="submit" id="guest-submit-btn"
                                class="flex-1 px-6 py-3 bg-green-600 text-white rounded-lg font-semibold text-sm hover:bg-green-700 disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                                <span id="guest-btn-label">{{ __('Continue to Payment') }}</span>
                                <svg id="guest-spinner" class="hidden animate-spin w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>

                {{-- ──────────────────────────────────────────────
                     SECTION 3: Stripe payment (revealed after AJAX)
                ────────────────────────────────────────────── --}}
                <div id="stripe-section" class="hidden bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">{{ __('Secure Card Payment') }}</h3>
                            <p class="text-xs text-gray-500">{{ __('Powered by Stripe — your card details are encrypted.') }}</p>
                        </div>
                    </div>

                    <!-- Summary banner -->
                    <div class="mb-5 p-3 bg-green-50 border border-green-200 rounded-lg flex items-center justify-between">
                        <span class="text-sm text-gray-700">{{ $checkoutData['charge_point_name'] ?? __('Charge point') }}</span>
                        <span class="font-bold text-green-700 text-lg">
                            {{ $checkoutData['total_price'] ?? '-' }} {{ $checkoutData['currency'] ?? 'MAD' }}
                        </span>
                    </div>

                    <!-- Stripe card element -->
                    <div id="card-element-inline"
                         class="mb-4 p-4 border border-gray-300 rounded-lg bg-white min-h-[46px]">
                        <!-- Stripe.js mounts here -->
                    </div>

                    <!-- Card errors -->
                    <div id="card-errors" class="hidden mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600"></div>

                    <!-- Pay button -->
                    <button type="button" id="stripe-pay-btn"
                        class="w-full py-4 bg-green-600 text-white rounded-lg font-semibold text-lg hover:bg-green-700 disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                        <span id="stripe-btn-label">
                            {{ __('Pay') }} {{ $checkoutData['total_price'] ?? '' }} {{ $checkoutData['currency'] ?? 'MAD' }}
                        </span>
                        <svg id="stripe-spinner" class="hidden animate-spin w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </button>

                    <p class="mt-3 text-xs text-center text-gray-400">
                        <svg class="w-3 h-3 inline mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        {{ __('256-bit SSL encryption') }}
                    </p>
                </div>

            </div><!-- /main col -->

            <!-- Sidebar: Order Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-sm p-6 sticky top-4">
                    <h3 class="font-semibold text-gray-900 mb-4">{{ __('Order Summary') }}</h3>

                    <div class="border-b pb-4 mb-4 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 text-sm">{{ __('Charge Point') }}</span>
                            <span class="font-medium text-gray-900 text-sm text-right">{{ $checkoutData['charge_point_name'] ?? 'N/A' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 text-sm">{{ __('Duration') }}</span>
                            <span class="font-medium text-gray-900 text-sm">{{ $checkoutData['duration_minutes'] ?? '-' }} min</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 text-sm">{{ __('Est. Consumption') }}</span>
                            <span class="font-medium text-gray-900 text-sm">{{ $checkoutData['estimated_kwh'] ?? '-' }} kWh</span>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 text-sm">{{ __('Subtotal') }}</span>
                            <span class="font-medium text-gray-900 text-sm">{{ $checkoutData['price_excl_vat'] ?? '-' }} {{ $checkoutData['currency'] ?? 'MAD' }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 text-sm">{{ __('VAT') }}</span>
                            <span class="font-medium text-gray-900 text-sm">{{ $checkoutData['vat_amount'] ?? '-' }} {{ $checkoutData['currency'] ?? 'MAD' }}</span>
                        </div>
                        <div class="flex justify-between items-center pt-2 border-t">
                            <span class="font-semibold text-gray-900">{{ __('Total') }}</span>
                            <span class="text-2xl font-bold text-green-600">{{ $checkoutData['total_price'] ?? '-' }} {{ $checkoutData['currency'] ?? 'MAD' }}</span>
                        </div>
                    </div>

                    <div class="mt-5 pt-4 border-t space-y-2">
                        <div class="flex items-center text-xs text-gray-400">
                            <svg class="w-4 h-4 mr-1.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            {{ __('Secure payment') }}
                        </div>
                        <div class="flex items-center text-xs text-gray-400">
                            <svg class="w-4 h-4 mr-1.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                            {{ __('Excess refunded to wallet') }}
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- /grid -->
    </div>
</div>

@push('scripts')
<script src="https://js.stripe.com/v3/"></script>
<script>
(function () {
    'use strict';

    // ── DOM refs ────────────────────────────────────────────────────────
    const choiceSection  = document.getElementById('choice-section');
    const guestSection   = document.getElementById('guest-section');
    const stripeSection  = document.getElementById('stripe-section');
    const guestToggleBtn = document.getElementById('guest-toggle-btn');
    const guestCancelBtn = document.getElementById('guest-cancel-btn');
    const guestForm      = document.getElementById('guest-form');
    const guestError     = document.getElementById('guest-error');
    const guestSubmitBtn = document.getElementById('guest-submit-btn');
    const guestBtnLabel  = document.getElementById('guest-btn-label');
    const guestSpinner   = document.getElementById('guest-spinner');
    const stripePayBtn   = document.getElementById('stripe-pay-btn');
    const stripeBtnLabel = document.getElementById('stripe-btn-label');
    const stripeSpinner  = document.getElementById('stripe-spinner');
    const cardErrors     = document.getElementById('card-errors');

    const CSRF_TOKEN      = document.querySelector('input[name="_token"]')?.value ?? '';
    const SESSION_URL     = @json(route('public.checkout.session', ['slug' => $slug]));
    const PAY_URL         = @json(route('public.checkout.pay',     ['slug' => $slug]));
    const CONFIRM_BASE    = @json(route('public.checkout.confirm',  ['slug' => $slug, 'id' => '__ID__']));
    const STRIPE_KEY      = @json(config('payments.stripe.public_key'));

    let stripeInstance = null;
    let cardElement    = null;
    let clientSecret   = null;
    let reservationId  = null;

    // ── Toggle: show guest form ─────────────────────────────────────────
    guestToggleBtn.addEventListener('click', function () {
        choiceSection.classList.add('hidden');
        guestSection.classList.remove('hidden');
        guestSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    guestCancelBtn.addEventListener('click', function () {
        guestSection.classList.add('hidden');
        choiceSection.classList.remove('hidden');
        clearGuestError();
    });

    // ── Helpers ─────────────────────────────────────────────────────────
    function showGuestError(msg) {
        guestError.textContent = msg;
        guestError.classList.remove('hidden');
        guestError.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function clearGuestError() {
        guestError.textContent = '';
        guestError.classList.add('hidden');
    }

    function showCardError(msg) {
        cardErrors.textContent = msg;
        cardErrors.classList.remove('hidden');
    }

    function clearCardError() {
        cardErrors.textContent = '';
        cardErrors.classList.add('hidden');
    }

    function setGuestLoading(loading) {
        guestSubmitBtn.disabled = loading;
        guestBtnLabel.textContent = loading
            ? '{{ __("Processing...") }}'
            : '{{ __("Continue to Payment") }}';
        guestSpinner.classList.toggle('hidden', !loading);
    }

    function setStripeLoading(loading) {
        stripePayBtn.disabled = loading;
        stripeBtnLabel.textContent = loading
            ? '{{ __("Processing...") }}'
            : '{{ __("Pay") }} {{ $checkoutData["total_price"] ?? "" }} {{ $checkoutData["currency"] ?? "MAD" }}';
        stripeSpinner.classList.toggle('hidden', !loading);
    }

    // ── Guest form submit → AJAX two-step ───────────────────────────────
    guestForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        clearGuestError();

        // Basic client-side validation
        const first = guestForm.querySelector('[name="first_name"]').value.trim();
        const last  = guestForm.querySelector('[name="last_name"]').value.trim();
        const email = guestForm.querySelector('[name="email"]').value.trim();
        const gdpr  = guestForm.querySelector('[name="gdpr_consent"]').checked;

        if (!first || !last || !email) {
            showGuestError('{{ __("Please fill in all required fields.") }}');
            return;
        }
        if (!gdpr) {
            showGuestError('{{ __("You must accept the privacy policy to continue.") }}');
            return;
        }

        setGuestLoading(true);

        try {
            // ── Step 1: Create checkout session ──────────────────────
            const formData = new FormData(guestForm);
            const sessionResp = await fetch(SESSION_URL, {
                method: 'POST',
                headers: {
                    'Accept':       'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                },
                body: formData,
            });
            const sessionData = await sessionResp.json();

            if (!sessionResp.ok || !sessionData.success) {
                throw new Error(sessionData.error ?? sessionData.message ?? '{{ __("Session creation failed.") }}');
            }

            const sessionId    = sessionData.session_id;
            const sessionToken = sessionData.session_token;

            // ── Step 2: Initiate payment ──────────────────────────────
            const payResp = await fetch(PAY_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept':       'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                },
                body: JSON.stringify({
                    session_id:    sessionId,
                    session_token: sessionToken,
                }),
            });
            const payData = await payResp.json();

            if (!payResp.ok || !payData.success) {
                throw new Error(payData.error ?? '{{ __("Payment initiation failed.") }}');
            }

            // ── Step 3a: Stripe inline ────────────────────────────────
            if (payData.payment_type === 'stripe' && payData.client_secret) {
                clientSecret  = payData.client_secret;
                reservationId = payData.checkout_session_id;

                guestSection.classList.add('hidden');
                stripeSection.classList.remove('hidden');
                stripeSection.scrollIntoView({ behavior: 'smooth', block: 'start' });

                // Mount Stripe Elements
                stripeInstance = Stripe(STRIPE_KEY);
                const elements = stripeInstance.elements();
                cardElement = elements.create('card', {
                    style: {
                        base: {
                            fontSize:    '16px',
                            color:       '#1f2937',
                            fontFamily:  '"Inter", "Helvetica Neue", Helvetica, sans-serif',
                            '::placeholder': { color: '#9ca3af' },
                        },
                        invalid: {
                            color:     '#dc2626',
                            iconColor: '#dc2626',
                        },
                    },
                });
                cardElement.mount('#card-element-inline');
                cardElement.on('change', function (event) {
                    if (event.error) {
                        showCardError(event.error.message);
                    } else {
                        clearCardError();
                    }
                });

                setGuestLoading(false);
                return;
            }

            // ── Step 3b: CMI / redirect gateway ──────────────────────
            if (payData.payment_type === 'redirect' && payData.redirect_url) {
                window.location.href = payData.redirect_url;
                return;
            }

            throw new Error('{{ __("Unexpected payment response. Please try again.") }}');

        } catch (err) {
            setGuestLoading(false);
            showGuestError(err.message ?? '{{ __("An error occurred. Please try again.") }}');
        }
    });

    // ── Stripe pay button ───────────────────────────────────────────────
    stripePayBtn.addEventListener('click', async function () {
        if (!stripeInstance || !cardElement || !clientSecret) return;

        clearCardError();
        setStripeLoading(true);

        try {
            const { error, paymentIntent } = await stripeInstance.confirmCardPayment(clientSecret, {
                payment_method: { card: cardElement },
            });

            if (error) {
                showCardError(error.message);
                setStripeLoading(false);
                return;
            }

            if (paymentIntent.status === 'succeeded') {
                window.location.href = CONFIRM_BASE.replace('__ID__', reservationId);
                return;
            }

            // requires_action etc. — Stripe should have handled 3DS already
            showCardError('{{ __("Payment could not be completed. Please try again.") }}');
            setStripeLoading(false);

        } catch (err) {
            showCardError(err.message ?? '{{ __("An error occurred. Please try again.") }}');
            setStripeLoading(false);
        }
    });

})();
</script>
@endpush
@endsection
