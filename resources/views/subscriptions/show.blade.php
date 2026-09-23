@extends('layouts.app')
@section('title', 'Abonnement — ' . ($subscription->subscriptionPlan->name ?? '#' . $subscription->id))
@section('page-title', 'Détail abonnement')

@section('content')
@php
    $plan = $subscription->subscriptionPlan;
    $statusConfig = [
        'active'    => ['bg'=>'bg-eco-green-100 dark:bg-eco-green-900/30','text'=>'text-eco-green-700 dark:text-eco-green-300','dot'=>'bg-eco-green-500','label'=>'Actif'],
        'pending'   => ['bg'=>'bg-yellow-100 dark:bg-yellow-900/30',    'text'=>'text-yellow-700 dark:text-yellow-300',   'dot'=>'bg-yellow-500', 'label'=>'En attente'],
        'expired'   => ['bg'=>'bg-orange-100 dark:bg-orange-900/30',    'text'=>'text-orange-700 dark:text-orange-300',   'dot'=>'bg-orange-500', 'label'=>'Expiré'],
        'cancelled' => ['bg'=>'bg-red-100 dark:bg-red-900/30',          'text'=>'text-red-700 dark:text-red-300',         'dot'=>'bg-red-500',    'label'=>'Annulé'],
        'suspended' => ['bg'=>'bg-gray-100 dark:bg-gray-800',           'text'=>'text-gray-700 dark:text-gray-300',       'dot'=>'bg-gray-400',   'label'=>'Suspendu'],
    ];
    $sc = $statusConfig[$subscription->status] ?? ['bg'=>'bg-gray-100','text'=>'text-gray-700','dot'=>'bg-gray-400','label'=>ucfirst($subscription->status)];
@endphp

{{-- Page header --}}
<div class="evon-page-header">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('subscriptions.index') }}"
               class="btn-icon text-gray-500 dark:text-gray-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h1 class="evon-page-title">{{ $plan->name ?? 'Abonnement' }}</h1>
                <p class="evon-page-subtitle">{{ $plan->type_label ?? '' }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-full {{ $sc['bg'] }} {{ $sc['text'] }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $sc['dot'] }} {{ $subscription->status === 'active' ? 'animate-pulse' : '' }}"></span>
                {{ $sc['label'] }}
            </span>
            @if(in_array($subscription->status, ['active','pending']))
            <form action="{{ route('subscriptions.cancel', $subscription) }}" method="POST"
                  onsubmit="return confirm('Annuler cet abonnement ? Cette action est irréversible.');">
                @csrf
                <button class="btn-danger btn-sm">Annuler</button>
            </form>
            @endif
            @if($subscription->status === 'expired' && $plan && ($plan->allow_renewal ?? false))
            <a href="{{ route('subscriptions.checkout', $plan) }}" class="btn-primary btn-sm">Renouveler</a>
            @endif
        </div>
    </div>
</div>

{{-- Flash messages --}}
@foreach(['success','error','info'] as $type)
@if(session($type))
@php $colors = ['success'=>'eco-green','error'=>'red','info'=>'blue']; $c = $colors[$type]; @endphp
<div class="mb-4 p-3 bg-{{ $c }}-50 dark:bg-{{ $c }}-900/20 border-l-4 border-{{ $c }}-500 text-{{ $c }}-800 dark:text-{{ $c }}-200 rounded-r text-sm">
    {{ session($type) }}
</div>
@endif
@endforeach

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

    {{-- Left: main info --}}
    <div class="lg:col-span-2 space-y-5">

        {{-- Active subscription countdown --}}
        @if($subscription->status === 'active')
        <div class="relative card overflow-hidden border-eco-green-200 dark:border-eco-green-800 p-0">
            <div class="absolute inset-0 bg-gradient-to-br from-eco-green-50 to-white dark:from-eco-green-900/20 dark:to-gray-800 pointer-events-none"></div>
            <div class="relative flex flex-col sm:flex-row items-start sm:items-center gap-5 p-5">
                <div class="flex-1">
                    <p class="text-xs font-semibold text-eco-green-600 dark:text-eco-green-400 uppercase tracking-wide mb-1">Abonnement actif</p>
                    <p class="text-gray-700 dark:text-gray-300 text-sm">Expire le <strong>{{ $subscription->end_date->format('d/m/Y') }}</strong></p>
                </div>
                <div class="text-center sm:text-right">
                    <div class="text-4xl font-extrabold text-eco-green-600 dark:text-eco-green-400">{{ $subscription->days_remaining }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">jours restants</div>
                </div>
            </div>
            {{-- Progress bar for time --}}
            @php
                $totalDays = $subscription->start_date->diffInDays($subscription->end_date);
                $usedDays  = $subscription->start_date->diffInDays(now());
                $timePct   = $totalDays > 0 ? min(100, round(($usedDays / $totalDays) * 100)) : 0;
            @endphp
            <div class="h-1.5 bg-gray-100 dark:bg-gray-700">
                <div class="h-full rounded-full transition-all duration-700 {{ $timePct >= 90 ? 'bg-red-500' : ($timePct >= 70 ? 'bg-orange-500' : 'bg-eco-green-500') }}"
                     style="width:{{ $timePct }}%"></div>
            </div>
        </div>
        @endif

        {{-- Info grid --}}
        <div class="card">
            <h2 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Informations</h2>
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div class="p-3 bg-gray-50 dark:bg-gray-800/50 rounded-xl">
                    <p class="text-xs text-gray-400 mb-1">Début</p>
                    <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $subscription->start_date->format('d/m/Y') }}</p>
                </div>
                <div class="p-3 bg-gray-50 dark:bg-gray-800/50 rounded-xl">
                    <p class="text-xs text-gray-400 mb-1">Fin</p>
                    <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $subscription->end_date->format('d/m/Y') }}</p>
                </div>
                <div class="p-3 bg-gray-50 dark:bg-gray-800/50 rounded-xl">
                    <p class="text-xs text-gray-400 mb-1">Méthode</p>
                    <p class="font-semibold text-gray-900 dark:text-gray-100 capitalize">{{ $subscription->payment_method ?? '—' }}</p>
                </div>
                <div class="p-3 bg-eco-green-50 dark:bg-eco-green-900/20 rounded-xl">
                    <p class="text-xs text-eco-green-500 mb-1">Montant TTC</p>
                    <p class="font-bold text-eco-green-700 dark:text-eco-green-300 text-lg">{{ $subscription->formatted_amount_paid }}</p>
                </div>
                @if($subscription->payment_reference)
                <div class="p-3 bg-gray-50 dark:bg-gray-800/50 rounded-xl col-span-2">
                    <p class="text-xs text-gray-400 mb-1">Référence paiement</p>
                    <p class="font-mono text-xs text-gray-700 dark:text-gray-300 break-all">{{ $subscription->payment_reference }}</p>
                </div>
                @endif
                <div class="p-3 bg-gray-50 dark:bg-gray-800/50 rounded-xl">
                    <p class="text-xs text-gray-400 mb-1">Renouvellement</p>
                    <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $subscription->auto_renew ? 'Automatique' : 'Manuel' }}</p>
                </div>
            </div>
        </div>

        {{-- Usage stats --}}
        @if($plan && ($plan->max_sessions || $plan->max_kwh || $plan->max_duration_minutes))
        <div class="card">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-gray-900 dark:text-gray-100">Consommation</h2>
                <a href="{{ route('subscriptions.usage', $subscription) }}"
                   class="text-xs font-medium text-eco-green-600 dark:text-eco-green-400 hover:underline">
                    Voir détails →
                </a>
            </div>
            <div class="space-y-4">
                @if($plan->max_sessions)
                @php $pct = $plan->max_sessions > 0 ? min(100, round(($subscription->sessions_used / $plan->max_sessions) * 100)) : 0; @endphp
                <div>
                    <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1.5">
                        <div class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-eco-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            Sessions
                        </div>
                        <span class="font-semibold {{ $pct >= 90 ? 'text-red-600' : ($pct >= 70 ? 'text-orange-600' : 'text-gray-700 dark:text-gray-300') }}">{{ $subscription->sessions_used }} / {{ $plan->max_sessions }}</span>
                    </div>
                    <div class="h-2.5 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-700 {{ $pct >= 90 ? 'bg-red-500' : ($pct >= 70 ? 'bg-orange-500' : 'bg-eco-green-500') }}" style="width:{{ $pct }}%"></div>
                    </div>
                </div>
                @endif
                @if($plan->max_kwh)
                @php $pct = $plan->max_kwh > 0 ? min(100, round(($subscription->kwh_used / $plan->max_kwh) * 100)) : 0; @endphp
                <div>
                    <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1.5">
                        <div class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            Énergie (kWh)
                        </div>
                        <span class="font-semibold {{ $pct >= 90 ? 'text-red-600' : ($pct >= 70 ? 'text-orange-600' : 'text-gray-700 dark:text-gray-300') }}">{{ number_format($subscription->kwh_used,1) }} / {{ number_format($plan->max_kwh,0) }}</span>
                    </div>
                    <div class="h-2.5 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-700 {{ $pct >= 90 ? 'bg-red-500' : ($pct >= 70 ? 'bg-orange-500' : 'bg-blue-500') }}" style="width:{{ $pct }}%"></div>
                    </div>
                </div>
                @endif
                @if($plan->max_duration_minutes)
                @php $pct = $plan->max_duration_minutes > 0 ? min(100, round(($subscription->duration_minutes_used / $plan->max_duration_minutes) * 100)) : 0; @endphp
                <div>
                    <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1.5">
                        <div class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Durée
                        </div>
                        <span class="font-semibold {{ $pct >= 90 ? 'text-red-600' : ($pct >= 70 ? 'text-orange-600' : 'text-gray-700 dark:text-gray-300') }}">{{ number_format($subscription->duration_minutes_used/60,1) }}h / {{ number_format($plan->max_duration_minutes/60,0) }}h</span>
                    </div>
                    <div class="h-2.5 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-700 {{ $pct >= 90 ? 'bg-red-500' : ($pct >= 70 ? 'bg-orange-500' : 'bg-purple-500') }}" style="width:{{ $pct }}%"></div>
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif

    </div>

    {{-- Right sidebar --}}
    <div class="lg:col-span-1 space-y-5">

        {{-- Plan summary --}}
        @if($plan)
        <div class="card">
            <h2 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Forfait inclus</h2>
            <div class="relative rounded-xl overflow-hidden border border-eco-green-100 dark:border-eco-green-900/40 mb-4">
                <div class="h-1 bg-gradient-to-r from-eco-green-400 to-eco-green-600"></div>
                <div class="p-4 bg-eco-green-50 dark:bg-eco-green-900/10">
                    <p class="font-bold text-gray-900 dark:text-gray-100 mb-1">{{ $plan->name }}</p>
                    <span class="inline-block px-2 py-0.5 bg-eco-green-100 dark:bg-eco-green-900/30 text-eco-green-600 dark:text-eco-green-400 text-xs rounded-full">
                        {{ $plan->type_label }}
                    </span>
                </div>
            </div>

            @if($plan->features && is_array($plan->features))
            <ul class="space-y-2">
                @foreach($plan->features as $feature)
                <li class="flex items-start gap-2 text-xs text-gray-600 dark:text-gray-400">
                    <svg class="w-3.5 h-3.5 text-eco-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ $feature }}
                </li>
                @endforeach
            </ul>
            @endif
        </div>
        @endif

        {{-- Quick actions --}}
        <div class="card p-4 space-y-2">
            <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Actions rapides</h3>
            @if($plan && ($plan->max_sessions || $plan->max_kwh || $plan->max_duration_minutes))
            <a href="{{ route('subscriptions.usage', $subscription) }}"
               class="flex items-center gap-3 p-3 rounded-xl hover:bg-eco-green-50 dark:hover:bg-eco-green-900/20 transition-colors group">
                <div class="w-8 h-8 bg-eco-green-100 dark:bg-eco-green-900/30 rounded-lg flex items-center justify-center group-hover:bg-eco-green-200 dark:group-hover:bg-eco-green-900/50 transition-colors">
                    <svg class="w-4 h-4 text-eco-green-600 dark:text-eco-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Consommation détaillée</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Historique des sessions</p>
                </div>
                <svg class="w-4 h-4 text-gray-400 group-hover:text-eco-green-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
            @endif
            <a href="{{ route('subscriptions.plans') }}"
               class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group">
                <div class="w-8 h-8 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center group-hover:bg-gray-200 dark:group-hover:bg-gray-600 transition-colors">
                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Tous les forfaits</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Comparer et souscrire</p>
                </div>
                <svg class="w-4 h-4 text-gray-400 group-hover:text-gray-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

    </div>
</div>

@endsection
