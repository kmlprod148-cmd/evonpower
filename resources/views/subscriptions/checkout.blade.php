@extends('layouts.app')
@section('title', 'Souscrire — ' . $plan->name)
@section('page-title', 'Souscrire à un forfait')

@push('styles')
<style>
    .pm-card.selected {
        border-color: #4acf7b !important;
        background: rgba(74, 207, 123, 0.06);
    }
    .dark .pm-card.selected {
        background: rgba(74, 207, 123, 0.08);
    }
</style>
@endpush

@section('content')
@php
    $totalPrice = $total;
    $vatAmount  = round($plan->price * ($plan->vat_rate / 100), 2);
@endphp

{{-- Page header --}}
<div class="evon-page-header">
    <div class="flex items-center gap-3">
        <a href="{{ route('subscriptions.plans.show', $plan) }}" class="btn-icon text-gray-500">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div>
            <h1 class="evon-page-title">Souscrire au forfait</h1>
            <p class="evon-page-subtitle">{{ $plan->name }} — {{ $plan->type_label }}</p>
        </div>
    </div>
</div>

@if($errors->any())
<div class="mb-5 p-4 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 text-red-800 dark:text-red-200 rounded-r text-sm">
    <ul class="list-disc list-inside space-y-0.5">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

    {{-- Payment form --}}
    <div class="lg:col-span-3">
        <div class="card">
            <h2 class="font-semibold text-gray-900 dark:text-gray-100 mb-5">Méthode de paiement</h2>

            <form action="{{ route('subscriptions.subscribe', $plan) }}" method="POST" id="checkoutForm">
                @csrf

                <div class="space-y-3 mb-6">

                    {{-- Wallet --}}
                    <label class="pm-card relative flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-800/50 border-2 border-gray-200 dark:border-gray-700 rounded-xl cursor-pointer hover:border-eco-green-400 dark:hover:border-eco-green-600 transition-all {{ !$hasSufficientBalance ? 'opacity-50 cursor-not-allowed' : '' }}"
                           data-method="wallet">
                        <input type="radio" name="payment_method" value="wallet" class="sr-only"
                               {{ !$hasSufficientBalance ? 'disabled' : '' }}
                               {{ old('payment_method') === 'wallet' ? 'checked' : '' }}>
                        <div class="w-10 h-10 bg-eco-green-100 dark:bg-eco-green-900/30 rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-eco-green-600 dark:text-eco-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-gray-900 dark:text-gray-100 text-sm">Mon solde wallet</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                Disponible : <strong data-balance-value>{{ $wallet->getFormattedBalance('EUR') }}</strong>
                                @if(!$hasSufficientBalance)
                                — <span class="text-red-500 font-medium">Solde insuffisant</span>
                                <a href="{{ route('credit-recharge.index') }}" class="ml-1 text-eco-green-600 underline">Recharger</a>
                                @endif
                            </p>
                        </div>
                        <svg class="w-5 h-5 text-eco-green-500 pm-check hidden shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </label>

                    {{-- CMI --}}
                    @php $cmiEnabled = isset($paymentMethods['cmi']) && ($paymentMethods['cmi']['enabled'] ?? false); @endphp
                    <label class="pm-card relative flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-800/50 border-2 border-gray-200 dark:border-gray-700 rounded-xl cursor-pointer hover:border-eco-green-400 dark:hover:border-eco-green-600 transition-all {{ !$cmiEnabled ? 'opacity-50 cursor-not-allowed' : '' }}"
                           data-method="cmi">
                        <input type="radio" name="payment_method" value="cmi" class="sr-only"
                               {{ !$cmiEnabled ? 'disabled' : '' }}
                               {{ old('payment_method') === 'cmi' ? 'checked' : '' }}>
                        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-gray-900 dark:text-gray-100 text-sm">Carte bancaire CMI</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $cmiEnabled ? 'Maroc · Paiement 3D Secure sécurisé' : 'Non disponible actuellement' }}</p>
                        </div>
                        <svg class="w-5 h-5 text-eco-green-500 pm-check hidden shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </label>

                    {{-- Stripe --}}
                    @php $stripeEnabled = isset($paymentMethods['stripe']) && ($paymentMethods['stripe']['enabled'] ?? false); @endphp
                    <label class="pm-card relative flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-800/50 border-2 border-gray-200 dark:border-gray-700 rounded-xl cursor-pointer hover:border-eco-green-400 dark:hover:border-eco-green-600 transition-all {{ !$stripeEnabled ? 'opacity-50 cursor-not-allowed' : '' }}"
                           data-method="stripe">
                        <input type="radio" name="payment_method" value="stripe" class="sr-only"
                               {{ !$stripeEnabled ? 'disabled' : '' }}
                               {{ old('payment_method') === 'stripe' ? 'checked' : '' }}>
                        <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-gray-900 dark:text-gray-100 text-sm">Carte bancaire Stripe</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $stripeEnabled ? 'International · Visa, Mastercard, CB' : 'Non disponible actuellement' }}</p>
                        </div>
                        <svg class="w-5 h-5 text-eco-green-500 pm-check hidden shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </label>

                </div>

                @error('payment_method')
                <p class="mb-4 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror

                {{-- Auto-renew --}}
                @if($plan->allow_renewal)
                <label class="flex items-center gap-3 mb-5 cursor-pointer p-3 rounded-xl bg-eco-green-50 dark:bg-eco-green-900/10 border border-eco-green-100 dark:border-eco-green-900/30">
                    <input type="checkbox" name="auto_renew" value="1"
                           class="w-4 h-4 rounded border-gray-300 text-eco-green-600 focus:ring-eco-green-500"
                           {{ old('auto_renew') ? 'checked' : '' }}>
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Renouvellement automatique</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Reconduit automatiquement à l'expiration</p>
                    </div>
                </label>
                @endif

                {{-- Submit --}}
                <button type="submit" id="submitBtn"
                        class="btn-primary w-full flex items-center justify-center gap-2 text-base py-3.5">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    Confirmer — {{ number_format($totalPrice, 2, ',', ' ') }} €
                </button>

                <div class="mt-3 flex items-center justify-center gap-1.5 text-xs text-gray-400">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    Paiement 100% sécurisé — Conditions d'utilisation applicables
                </div>
            </form>
        </div>
    </div>

    {{-- Order summary sidebar --}}
    <div class="lg:col-span-2">
        <div class="card sticky top-4">
            <h2 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Récapitulatif</h2>

            {{-- Plan card preview --}}
            <div class="relative rounded-xl overflow-hidden border border-eco-green-100 dark:border-eco-green-900/40 mb-5">
                <div class="h-1 bg-gradient-to-r from-eco-green-400 to-eco-green-600"></div>
                <div class="p-4 bg-eco-green-50 dark:bg-eco-green-900/10">
                    <div class="flex items-start gap-3">
                        <div class="w-9 h-9 bg-eco-green-100 dark:bg-eco-green-900/40 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-eco-green-600 dark:text-eco-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-bold text-gray-900 dark:text-gray-100">{{ $plan->name }}</p>
                            <span class="inline-block mt-0.5 px-2 py-0.5 bg-eco-green-100 dark:bg-eco-green-900/30 text-eco-green-600 dark:text-eco-green-400 text-xs rounded-full">{{ $plan->type_label }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Price breakdown --}}
            <div class="space-y-2.5 text-sm mb-5">
                <div class="flex justify-between text-gray-500 dark:text-gray-400">
                    <span>Prix HT</span>
                    <span>{{ number_format($plan->price, 2, ',', ' ') }} €</span>
                </div>
                @if($plan->vat_rate > 0)
                <div class="flex justify-between text-gray-500 dark:text-gray-400">
                    <span>TVA ({{ $plan->vat_rate }}%)</span>
                    <span>{{ number_format($vatAmount, 2, ',', ' ') }} €</span>
                </div>
                @endif
                <div class="flex justify-between font-bold text-gray-900 dark:text-gray-100 text-base pt-2.5 border-t border-gray-100 dark:border-gray-700">
                    <span>Total TTC</span>
                    <span class="text-eco-green-600 dark:text-eco-green-400">{{ number_format($totalPrice, 2, ',', ' ') }} €</span>
                </div>
            </div>

            {{-- Included features --}}
            <div class="space-y-2 text-xs text-gray-500 dark:text-gray-400 pt-4 border-t border-gray-100 dark:border-gray-700">
                @if($plan->max_sessions)
                <div class="flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 text-eco-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ number_format($plan->max_sessions) }} sessions incluses
                </div>
                @endif
                @if($plan->max_kwh)
                <div class="flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 text-eco-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ number_format($plan->max_kwh, 0) }} kWh inclus
                </div>
                @endif
                @if($plan->max_duration_minutes)
                <div class="flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 text-eco-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ number_format($plan->max_duration_minutes / 60, 0) }}h de charge
                </div>
                @endif
                @if($plan->getDurationInMonths() > 0)
                <div class="flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 text-eco-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Valide {{ $plan->getDurationInMonths() }} mois
                </div>
                @endif
                @if($plan->features && is_array($plan->features))
                    @foreach($plan->features as $f)
                    <div class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 text-eco-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ $f }}
                    </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>

</div>

<script>
document.querySelectorAll('.pm-card').forEach(card => {
    card.addEventListener('click', function() {
        const radio = this.querySelector('input[type="radio"]');
        if (radio.disabled) return;

        document.querySelectorAll('.pm-card').forEach(c => {
            c.classList.remove('selected');
            c.querySelector('.pm-check')?.classList.add('hidden');
        });

        radio.checked = true;
        this.classList.add('selected');
        this.querySelector('.pm-check')?.classList.remove('hidden');
    });
});

// Auto-select first available method
const firstEnabled = document.querySelector('.pm-card:not(.opacity-50)');
if (firstEnabled) firstEnabled.click();

// Submit feedback
document.getElementById('checkoutForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Traitement en cours...';
    }
});
</script>
@endsection
