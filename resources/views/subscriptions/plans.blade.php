@extends('layouts.app')
@section('title', 'Forfaits d\'abonnement')
@section('page-title', 'Forfaits')

@section('content')

{{-- Hero section --}}
<div class="relative rounded-3xl overflow-hidden mb-8 bg-gradient-to-br from-eco-green-50 via-white to-blue-50 dark:from-gray-800 dark:via-gray-800 dark:to-gray-900 border border-gray-100 dark:border-gray-700 shadow-xl p-8">
    <div class="absolute top-0 right-0 w-72 h-72 opacity-[0.06] dark:opacity-[0.04] pointer-events-none">
        <svg viewBox="0 0 320 320" xmlns="http://www.w3.org/2000/svg">
            <circle cx="160" cy="160" r="140" fill="none" stroke="#4acf7b" stroke-width="2"/>
            <circle cx="160" cy="160" r="100" fill="none" stroke="#4acf7b" stroke-width="1.5"/>
            <circle cx="160" cy="160" r="60" fill="none" stroke="#4acf7b" stroke-width="1"/>
            <path d="M160 80 L152 106 L166 106 L152 132" stroke="#4acf7b" stroke-width="3" fill="none" stroke-linecap="round"/>
        </svg>
    </div>
    <div class="relative z-10 max-w-2xl">
        <div class="inline-flex items-center gap-2 bg-eco-green-100 dark:bg-eco-green-900/30 text-eco-green-700 dark:text-eco-green-300 px-4 py-1.5 rounded-full text-sm font-semibold mb-5">
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-eco-green-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-eco-green-500"></span>
            </span>
            Forfaits disponibles
        </div>
        <h2 class="text-3xl lg:text-4xl font-extrabold text-gray-900 dark:text-white mb-3">
            Choisissez votre <span class="text-eco-green-600 dark:text-eco-green-400">forfait</span>
        </h2>
        <p class="text-gray-600 dark:text-gray-400 text-lg mb-0">
            Des abonnements flexibles, adaptés à chaque usage.
        </p>
        @if($activeSubscription)
        <div class="mt-5 inline-flex items-center gap-2 px-4 py-2 bg-eco-green-100 dark:bg-eco-green-900/30 text-eco-green-800 dark:text-eco-green-200 rounded-xl text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Abonnement actif : <strong>{{ $activeSubscription->subscriptionPlan->name }}</strong> — expire le {{ $activeSubscription->end_date->format('d/m/Y') }}
        </div>
        @endif
    </div>
</div>

{{-- Periodic plans --}}
@if($periodicPlans->count() > 0)
<div class="mb-10">
    <div class="flex items-center gap-3 mb-5">
        <div class="w-8 h-8 bg-eco-green-100 dark:bg-eco-green-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
            <svg class="w-4 h-4 text-eco-green-600 dark:text-eco-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </div>
        <div>
            <h3 class="font-bold text-gray-900 dark:text-gray-100">Forfaits périodiques</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">Accès sans limite sur une durée définie</p>
        </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
        @foreach($periodicPlans as $plan)
            @include('subscriptions._plan-card', ['plan' => $plan])
        @endforeach
    </div>
</div>
@endif

{{-- Quota plans --}}
@if($quotaPlans->count() > 0)
<div class="mb-10">
    <div class="flex items-center gap-3 mb-5">
        <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
        </div>
        <div>
            <h3 class="font-bold text-gray-900 dark:text-gray-100">Forfaits à la consommation</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">Quota de sessions, d'énergie ou de durée</p>
        </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
        @foreach($quotaPlans as $plan)
            @include('subscriptions._plan-card', ['plan' => $plan])
        @endforeach
    </div>
</div>
@endif

@if($periodicPlans->count() === 0 && $quotaPlans->count() === 0)
<div class="card flex flex-col items-center justify-center py-20 text-center">
    <div class="w-20 h-20 bg-eco-green-50 dark:bg-eco-green-900/20 rounded-full flex items-center justify-center mb-5">
        <svg class="w-10 h-10 text-eco-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
        </svg>
    </div>
    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-2">Aucun forfait disponible</h3>
    <p class="text-sm text-gray-500 dark:text-gray-400">Revenez bientôt, de nouveaux forfaits arrivent.</p>
</div>
@endif

{{-- Trust badges --}}
<div class="mt-8 grid grid-cols-1 sm:grid-cols-3 gap-4">
    @foreach([
        ['icon'=>'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'title'=>'Paiement sécurisé', 'desc'=>'3D Secure & chiffrement SSL'],
        ['icon'=>'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15', 'title'=>'Renouvellement facile', 'desc'=>'Manuel ou automatique selon votre choix'],
        ['icon'=>'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', 'title'=>'Support dédié', 'desc'=>'Notre équipe répond sous 24h'],
    ] as $badge)
    <div class="flex items-start gap-3 p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-700">
        <div class="w-9 h-9 bg-eco-green-100 dark:bg-eco-green-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-eco-green-600 dark:text-eco-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $badge['icon'] }}"/>
            </svg>
        </div>
        <div>
            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $badge['title'] }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $badge['desc'] }}</p>
        </div>
    </div>
    @endforeach
</div>

@endsection
