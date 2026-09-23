@extends('layouts.app')
@section('title', 'Mes Abonnements')
@section('page-title', 'Mes Abonnements')

@section('content')
@php
    $statusColors = [
        'active'    => ['dot' => 'bg-eco-green-500', 'badge' => 'bg-eco-green-100 text-eco-green-700 dark:bg-eco-green-900/30 dark:text-eco-green-300', 'label' => 'Actif'],
        'pending'   => ['dot' => 'bg-yellow-500',    'badge' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',       'label' => 'En attente'],
        'expired'   => ['dot' => 'bg-orange-500',    'badge' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300',       'label' => 'Expiré'],
        'cancelled' => ['dot' => 'bg-red-500',       'badge' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',                   'label' => 'Annulé'],
        'suspended' => ['dot' => 'bg-gray-400',      'badge' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',                  'label' => 'Suspendu'],
    ];
@endphp

{{-- Page header --}}
<div class="evon-page-header">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="evon-page-title">Mes Abonnements</h1>
            <p class="evon-page-subtitle">Gérez vos forfaits de recharge électrique</p>
        </div>
        <a href="{{ route('subscriptions.plans') }}" class="btn-primary inline-flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Voir les forfaits
        </a>
    </div>
</div>

{{-- Flash messages --}}
@foreach(['success' => ['eco-green','bg-eco-green-50 dark:bg-eco-green-900/20 border-eco-green-500 text-eco-green-800 dark:text-eco-green-200'], 'error' => ['red','bg-red-50 dark:bg-red-900/20 border-red-500 text-red-800 dark:text-red-200'], 'info' => ['blue','bg-blue-50 dark:bg-blue-900/20 border-blue-500 text-blue-800 dark:text-blue-200']] as $type => $cfg)
@if(session($type))
<div class="mb-4 p-3 {{ $cfg[1] }} border-l-4 rounded-r text-sm flex items-center gap-2" role="alert">
    <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
    <p>{{ session($type) }}</p>
</div>
@endif
@endforeach

{{-- Active subscription hero banner --}}
@if($activeSubscription)
@php $ap = $activeSubscription; $planA = $ap->subscriptionPlan; @endphp
<div class="card mb-6 relative overflow-hidden border-eco-green-200 dark:border-eco-green-800">
    {{-- Background glow --}}
    <div class="absolute inset-0 bg-gradient-to-br from-eco-green-50 to-white dark:from-eco-green-900/20 dark:to-gray-800 pointer-events-none"></div>
    <div class="relative flex flex-col sm:flex-row sm:items-center gap-4">
        <div class="flex items-center gap-4 flex-1">
            <div class="relative flex-shrink-0">
                <div class="w-14 h-14 bg-eco-green-100 dark:bg-eco-green-900/40 rounded-2xl flex items-center justify-center">
                    <svg class="w-7 h-7 text-eco-green-600 dark:text-eco-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="absolute -top-1 -right-1 w-4 h-4 bg-eco-green-500 rounded-full border-2 border-white dark:border-gray-800">
                    <span class="absolute inset-0 rounded-full bg-eco-green-400 animate-ping opacity-75"></span>
                </span>
            </div>
            <div>
                <div class="flex items-center gap-2 mb-0.5">
                    <p class="font-bold text-gray-900 dark:text-gray-100">{{ $planA->name ?? 'Abonnement actif' }}</p>
                    <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-eco-green-100 text-eco-green-700 dark:bg-eco-green-900/30 dark:text-eco-green-300">Actif</span>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Expire le <strong class="text-gray-900 dark:text-gray-100">{{ $ap->end_date->format('d/m/Y') }}</strong>
                    &mdash; <span class="font-semibold text-eco-green-600 dark:text-eco-green-400">{{ $ap->days_remaining }} jour(s) restant(s)</span>
                </p>
            </div>
        </div>
        <a href="{{ route('subscriptions.show', $ap) }}" class="btn-outline shrink-0 text-sm">
            Voir les détails →
        </a>
    </div>

    {{-- Usage mini-bars --}}
    @if($planA && ($planA->max_sessions || $planA->max_kwh || $planA->max_duration_minutes))
    <div class="relative grid grid-cols-1 sm:grid-cols-3 gap-3 mt-4 pt-4 border-t border-eco-green-100 dark:border-eco-green-800/40">
        @if($planA->max_sessions)
        @php $pct = $planA->max_sessions > 0 ? min(100, round(($ap->sessions_used / $planA->max_sessions) * 100)) : 0; @endphp
        <div>
            <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                <span>Sessions</span><span>{{ $ap->sessions_used }} / {{ $planA->max_sessions }}</span>
            </div>
            <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all duration-700 {{ $pct >= 90 ? 'bg-red-500' : ($pct >= 70 ? 'bg-orange-500' : 'bg-eco-green-500') }}" style="width:{{ $pct }}%"></div>
            </div>
        </div>
        @endif
        @if($planA->max_kwh)
        @php $pct = $planA->max_kwh > 0 ? min(100, round(($ap->kwh_used / $planA->max_kwh) * 100)) : 0; @endphp
        <div>
            <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                <span>Énergie</span><span>{{ number_format($ap->kwh_used,1) }} / {{ number_format($planA->max_kwh,0) }} kWh</span>
            </div>
            <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all duration-700 {{ $pct >= 90 ? 'bg-red-500' : ($pct >= 70 ? 'bg-orange-500' : 'bg-eco-green-500') }}" style="width:{{ $pct }}%"></div>
            </div>
        </div>
        @endif
        @if($planA->max_duration_minutes)
        @php $pct = $planA->max_duration_minutes > 0 ? min(100, round(($ap->duration_minutes_used / $planA->max_duration_minutes) * 100)) : 0; @endphp
        <div>
            <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                <span>Durée</span><span>{{ number_format($ap->duration_minutes_used/60,1) }}h / {{ number_format($planA->max_duration_minutes/60,0) }}h</span>
            </div>
            <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all duration-700 {{ $pct >= 90 ? 'bg-red-500' : ($pct >= 70 ? 'bg-orange-500' : 'bg-eco-green-500') }}" style="width:{{ $pct }}%"></div>
            </div>
        </div>
        @endif
    </div>
    @endif
</div>
@endif

{{-- Subscriptions list --}}
<div class="card p-0 overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-700">
        <h2 class="font-semibold text-gray-900 dark:text-gray-100">Historique</h2>
        <span class="text-xs text-gray-400">{{ $subscriptions->total() }} abonnement(s)</span>
    </div>

    @if($subscriptions->count() > 0)
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Plan</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Montant</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden md:table-cell">Période</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden sm:table-cell">Méthode</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Statut</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-800">
                @foreach($subscriptions as $sub)
                @php $sc = $statusColors[$sub->status] ?? ['dot'=>'bg-gray-400','badge'=>'bg-gray-100 text-gray-700','label'=>ucfirst($sub->status)]; @endphp
                <tr class="hover:bg-eco-green-50/40 dark:hover:bg-eco-green-900/10 transition-colors">
                    <td class="px-5 py-3 whitespace-nowrap">
                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ $sub->subscriptionPlan->name ?? '—' }}</div>
                        <div class="text-xs text-gray-400">{{ $sub->subscriptionPlan->type_label ?? '' }}</div>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap font-semibold text-gray-900 dark:text-gray-100">{{ $sub->formatted_amount_paid }}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400 hidden md:table-cell">
                        {{ $sub->start_date->format('d/m/Y') }} → {{ $sub->end_date->format('d/m/Y') }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-gray-600 dark:text-gray-400 capitalize hidden sm:table-cell">{{ $sub->payment_method ?? '—' }}</td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full {{ $sc['badge'] }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $sc['dot'] }}"></span>
                            {{ $sc['label'] }}
                        </span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('subscriptions.show', $sub) }}"
                               class="text-xs font-medium text-eco-green-600 hover:text-eco-green-700 dark:text-eco-green-400 hover:underline">
                               Voir
                            </a>
                            @if(in_array($sub->status, ['active','pending']))
                            <form action="{{ route('subscriptions.cancel', $sub) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Annuler cet abonnement ? Cette action est irréversible.');">
                                @csrf
                                <button class="text-xs font-medium text-red-500 hover:text-red-700 dark:text-red-400">Annuler</button>
                            </form>
                            @endif
                            @if($sub->status === 'expired' && ($sub->subscriptionPlan->allow_renewal ?? false))
                            <a href="{{ route('subscriptions.checkout', $sub->subscriptionPlan) }}"
                               class="text-xs font-medium text-eco-green-600 hover:text-eco-green-700 dark:text-eco-green-400">
                               Renouveler
                            </a>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="px-5 py-3 border-t border-gray-100 dark:border-gray-700">
        {{ $subscriptions->links() }}
    </div>

    @else
    <div class="flex flex-col items-center justify-center py-16 px-6 text-center">
        <div class="w-20 h-20 bg-eco-green-50 dark:bg-eco-green-900/20 rounded-full flex items-center justify-center mb-5">
            <svg class="w-10 h-10 text-eco-green-400 dark:text-eco-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
            </svg>
        </div>
        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-2">Aucun abonnement pour le moment</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 max-w-xs">Souscrivez à un forfait pour bénéficier de tarifs préférentiels sur vos recharges.</p>
        <a href="{{ route('subscriptions.plans') }}" class="btn-primary inline-flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            Découvrir les forfaits
        </a>
    </div>
    @endif
</div>

@endsection
