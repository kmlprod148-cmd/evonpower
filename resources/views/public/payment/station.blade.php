@extends('layouts.guest')

@section('title', __('Station de Recharge'))

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-lg mx-auto px-4">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
                <i class="fas fa-charging-station text-3xl text-green-600"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $chargingPoint->name ?? __('Station de Recharge') }}</h1>
            @if ($chargingPoint->location)
                <p class="text-sm text-gray-500 mt-1"><i class="fas fa-map-marker-alt mr-1"></i>{{ $chargingPoint->location }}</p>
            @endif
        </div>

        <!-- Pricing Info -->
        @if ($pricingPlan)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">{{ __('Tarification') }}</h2>
            <div class="space-y-2 text-sm text-gray-700">
                @if ($pricingPlan->price_per_kwh)
                    <div class="flex justify-between">
                        <span>{{ __('Prix au kWh') }}</span>
                        <span class="font-semibold">{{ number_format($pricingPlan->price_per_kwh, 4) }} {{ strtoupper($pricingPlan->currency ?? 'EUR') }}</span>
                    </div>
                @endif
                @if ($pricingPlan->price_per_minute)
                    <div class="flex justify-between">
                        <span>{{ __('Prix à la minute') }}</span>
                        <span class="font-semibold">{{ number_format($pricingPlan->price_per_minute, 4) }} {{ strtoupper($pricingPlan->currency ?? 'EUR') }}</span>
                    </div>
                @endif
                @if ($pricingPlan->connection_fee)
                    <div class="flex justify-between">
                        <span>{{ __('Frais de connexion') }}</span>
                        <span class="font-semibold">{{ number_format($pricingPlan->connection_fee, 2) }} {{ strtoupper($pricingPlan->currency ?? 'EUR') }}</span>
                    </div>
                @endif
            </div>
        </div>
        @endif

        <!-- CTA -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">{{ __('Commencer une session') }}</h2>
            <p class="text-sm text-gray-500 mb-6">{{ __('Pour démarrer une session de recharge, veuillez renseigner vos informations de contact et choisir votre mode de paiement.') }}</p>
            <a href="{{ route('public.payment.customer', $chargingPoint->id) }}"
               class="w-full inline-flex items-center justify-center px-6 py-3 bg-green-600 text-white font-semibold rounded-xl hover:bg-green-700 transition-colors">
                <i class="fas fa-bolt mr-2"></i>{{ __('Démarrer la recharge') }}
            </a>
        </div>
    </div>
</div>
@endsection
