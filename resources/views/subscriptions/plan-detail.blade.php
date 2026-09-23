@extends('layouts.app')
@section('title', $plan->name)
@section('page-title', $plan->name)

@section('content')
@php
    $totalPrice = round($plan->price * (1 + $plan->vat_rate / 100), 2);
    $vatAmount  = round($plan->price * ($plan->vat_rate / 100), 2);
    $features   = is_array($plan->features) ? $plan->features : [];
    $durationLabel = match($plan->type) {
        'monthly'     => '/ mois',
        'quarterly'   => '/ trimestre',
        'semi_annual' => '/ 6 mois',
        'annual'      => '/ an',
        default       => '',
    };
@endphp

{{-- Page header --}}
<div class="evon-page-header">
    <div class="flex items-center gap-3">
        <a href="{{ route('subscriptions.plans') }}" class="btn-icon text-gray-500">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div>
            <div class="flex items-center gap-2">
                <h1 class="evon-page-title">{{ $plan->name }}</h1>
                @if($plan->is_featured)
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-eco-green-500 text-white text-xs font-bold rounded-full">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    Populaire
                </span>
                @endif
            </div>
            <p class="evon-page-subtitle">{{ $plan->type_label }}</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Main content --}}
    <div class="lg:col-span-2 space-y-5">

        {{-- Description & limits --}}
        <div class="card">
            @if($plan->description)
            <p class="text-gray-600 dark:text-gray-400 mb-5 text-sm leading-relaxed">{{ $plan->description }}</p>
            @endif

            {{-- Limits grid --}}
            <div class="grid grid-cols-2 gap-3">
                @if($plan->max_sessions)
                <div class="flex items-center gap-3 p-4 bg-eco-green-50 dark:bg-eco-green-900/20 rounded-xl border border-eco-green-100 dark:border-eco-green-900/30">
                    <div class="w-9 h-9 bg-eco-green-100 dark:bg-eco-green-900/40 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-eco-green-600 dark:text-eco-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <div>
                        <p class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ number_format($plan->max_sessions) }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Sessions</p>
                    </div>
                </div>
                @endif
                @if($plan->max_kwh)
                <div class="flex items-center gap-3 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-100 dark:border-blue-900/30">
                    <div class="w-9 h-9 bg-blue-100 dark:bg-blue-900/40 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <p class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ number_format($plan->max_kwh, 0) }} kWh</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Énergie</p>
                    </div>
                </div>
                @endif
                @if($plan->max_duration_minutes)
                <div class="flex items-center gap-3 p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl border border-purple-100 dark:border-purple-900/30">
                    <div class="w-9 h-9 bg-purple-100 dark:bg-purple-900/40 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ number_format($plan->max_duration_minutes / 60, 0) }}h</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Durée max</p>
                    </div>
                </div>
                @endif
                @if($plan->getDurationInMonths() > 0)
                <div class="flex items-center gap-3 p-4 bg-orange-50 dark:bg-orange-900/20 rounded-xl border border-orange-100 dark:border-orange-900/30">
                    <div class="w-9 h-9 bg-orange-100 dark:bg-orange-900/40 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <p class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ $plan->getDurationInMonths() }} mois</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Engagement</p>
                    </div>
                </div>
                @endif
                @if(!$plan->max_sessions && !$plan->max_kwh && !$plan->max_duration_minutes)
                <div class="col-span-2 flex items-center gap-3 p-4 bg-eco-green-50 dark:bg-eco-green-900/20 rounded-xl border border-eco-green-100 dark:border-eco-green-900/30">
                    <div class="w-9 h-9 bg-eco-green-100 dark:bg-eco-green-900/40 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-eco-green-600 dark:text-eco-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                    </div>
                    <div>
                        <p class="font-bold text-eco-green-700 dark:text-eco-green-300">Sessions illimitées</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Aucune limite de recharge</p>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Features --}}
        @if($features)
        <div class="card">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Inclus dans ce forfait</h3>
            <ul class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                @foreach($features as $feature)
                <li class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                    <div class="w-5 h-5 bg-eco-green-100 dark:bg-eco-green-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-3 h-3 text-eco-green-600 dark:text-eco-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    {{ $feature }}
                </li>
                @endforeach
            </ul>
        </div>
        @endif

        {{-- Terms --}}
        @if($plan->terms_conditions)
        <div class="card bg-gray-50 dark:bg-gray-800/50">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Conditions générales
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">{{ $plan->terms_conditions }}</p>
        </div>
        @endif

    </div>

    {{-- Pricing sidebar --}}
    <div class="lg:col-span-1">
        <div class="card sticky top-4 border-2 border-eco-green-200 dark:border-eco-green-800 relative overflow-hidden">
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-eco-green-400 to-eco-green-600"></div>
            <div class="pt-2">
                <p class="text-xs text-gray-400 mb-1">Prix total TTC</p>
                <div class="flex items-end gap-1 mb-1">
                    <span class="text-4xl font-extrabold text-gray-900 dark:text-gray-100">{{ number_format($totalPrice, 2, ',', ' ') }}</span>
                    <span class="text-sm text-gray-400 mb-1">€{{ $durationLabel ? ' '.$durationLabel : '' }}</span>
                </div>
                @if($plan->vat_rate > 0)
                <div class="text-xs text-gray-400 space-y-1 mb-5">
                    <div class="flex justify-between">
                        <span>Prix HT</span>
                        <span>{{ number_format($plan->price, 2, ',', ' ') }} €</span>
                    </div>
                    <div class="flex justify-between">
                        <span>TVA ({{ $plan->vat_rate }}%)</span>
                        <span>{{ number_format($vatAmount, 2, ',', ' ') }} €</span>
                    </div>
                </div>
                @else
                <div class="mb-5"></div>
                @endif

                {{-- Perks --}}
                <div class="space-y-2 mb-5 text-xs text-gray-500 dark:text-gray-400">
                    @if($plan->allow_renewal)
                    <div class="flex items-center gap-1.5 text-eco-green-600 dark:text-eco-green-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Renouvellement possible
                    </div>
                    @endif
                    <div class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-eco-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        Paiement sécurisé
                    </div>
                    @if($plan->cancellation_fee)
                    <div class="flex items-center gap-1.5 text-orange-600 dark:text-orange-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        Frais annulation : {{ number_format($plan->cancellation_fee, 2, ',', ' ') }} €
                    </div>
                    @endif
                </div>

                <a href="{{ route('subscriptions.checkout', $plan) }}" class="btn-primary block text-center w-full text-base py-3">
                    S'abonner maintenant
                </a>
                <a href="{{ route('subscriptions.plans') }}" class="btn-ghost block text-center w-full text-sm mt-2">
                    Voir tous les forfaits
                </a>
            </div>
        </div>
    </div>

</div>

@endsection
