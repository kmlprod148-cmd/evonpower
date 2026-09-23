@extends('layouts.app')
@section('title', 'Consommation — ' . ($subscription->subscriptionPlan->name ?? 'Abonnement'))
@section('page-title', 'Consommation')

@section('content')
@php $plan = $subscription->subscriptionPlan; @endphp

{{-- Page header --}}
<div class="evon-page-header">
    <div class="flex items-center gap-4">
        <a href="{{ route('subscriptions.show', $subscription) }}" class="btn-icon text-gray-500">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div>
            <h1 class="evon-page-title">Consommation</h1>
            <p class="evon-page-subtitle">{{ $plan->name ?? 'Abonnement' }}</p>
        </div>
    </div>
</div>

{{-- Usage metric cards --}}
@if($plan && ($plan->max_sessions || $plan->max_kwh || $plan->max_duration_minutes))
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-6">

    @if($plan->max_sessions)
    @php
        $used = $subscription->sessions_used;
        $max  = $plan->max_sessions;
        $rem  = max(0, $max - $used);
        $pct  = $max > 0 ? min(100, round(($used / $max) * 100)) : 0;
        $color = $pct >= 90 ? ['ring'=>'ring-red-500','bar'=>'bg-red-500','text'=>'text-red-600 dark:text-red-400','bg'=>'bg-red-50 dark:bg-red-900/20']
               : ($pct >= 70 ? ['ring'=>'ring-orange-500','bar'=>'bg-orange-500','text'=>'text-orange-600 dark:text-orange-400','bg'=>'bg-orange-50 dark:bg-orange-900/20']
               : ['ring'=>'ring-eco-green-500','bar'=>'bg-eco-green-500','text'=>'text-eco-green-600 dark:text-eco-green-400','bg'=>'bg-eco-green-50 dark:bg-eco-green-900/20']);
    @endphp
    <div class="card">
        <div class="flex items-start justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 {{ $color['bg'] }} rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 {{ $color['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">Sessions</p>
                    <p class="text-xs text-gray-400">Recharges effectuées</p>
                </div>
            </div>
            <span class="text-sm font-bold {{ $color['text'] }}">{{ $pct }}%</span>
        </div>
        {{-- Circular-style thick bar --}}
        <div class="relative h-3 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden mb-3">
            <div class="absolute inset-y-0 left-0 {{ $color['bar'] }} rounded-full transition-all duration-700" style="width:{{ $pct }}%"></div>
        </div>
        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
            <span>Utilisées : <strong class="text-gray-900 dark:text-gray-100">{{ $used }}</strong></span>
            <span>Restantes : <strong class="text-gray-900 dark:text-gray-100">{{ $rem }}</strong></span>
        </div>
        <p class="text-xs text-gray-400 mt-2 pt-2 border-t border-gray-100 dark:border-gray-700">Total : {{ $max }} sessions</p>
    </div>
    @endif

    @if($plan->max_kwh)
    @php
        $used = $subscription->kwh_used;
        $max  = $plan->max_kwh;
        $rem  = max(0, $max - $used);
        $pct  = $max > 0 ? min(100, round(($used / $max) * 100)) : 0;
        $color = $pct >= 90 ? ['bar'=>'bg-red-500','text'=>'text-red-600 dark:text-red-400','bg'=>'bg-red-50 dark:bg-red-900/20']
               : ($pct >= 70 ? ['bar'=>'bg-orange-500','text'=>'text-orange-600 dark:text-orange-400','bg'=>'bg-orange-50 dark:bg-orange-900/20']
               : ['bar'=>'bg-blue-500','text'=>'text-blue-600 dark:text-blue-400','bg'=>'bg-blue-50 dark:bg-blue-900/20']);
    @endphp
    <div class="card">
        <div class="flex items-start justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 {{ $color['bg'] }} rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 {{ $color['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">Énergie (kWh)</p>
                    <p class="text-xs text-gray-400">Électricité consommée</p>
                </div>
            </div>
            <span class="text-sm font-bold {{ $color['text'] }}">{{ $pct }}%</span>
        </div>
        <div class="relative h-3 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden mb-3">
            <div class="absolute inset-y-0 left-0 {{ $color['bar'] }} rounded-full transition-all duration-700" style="width:{{ $pct }}%"></div>
        </div>
        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
            <span>Consommés : <strong class="text-gray-900 dark:text-gray-100">{{ number_format($used,2) }} kWh</strong></span>
            <span>Restants : <strong class="text-gray-900 dark:text-gray-100">{{ number_format($rem,2) }} kWh</strong></span>
        </div>
        <p class="text-xs text-gray-400 mt-2 pt-2 border-t border-gray-100 dark:border-gray-700">Total : {{ number_format($max,0) }} kWh</p>
    </div>
    @endif

    @if($plan->max_duration_minutes)
    @php
        $used  = $subscription->duration_minutes_used;
        $max   = $plan->max_duration_minutes;
        $rem   = max(0, $max - $used);
        $pct   = $max > 0 ? min(100, round(($used / $max) * 100)) : 0;
        $usedH = number_format($used / 60, 1);
        $remH  = number_format($rem / 60, 1);
        $maxH  = number_format($max / 60, 0);
        $color = $pct >= 90 ? ['bar'=>'bg-red-500','text'=>'text-red-600 dark:text-red-400','bg'=>'bg-red-50 dark:bg-red-900/20']
               : ($pct >= 70 ? ['bar'=>'bg-orange-500','text'=>'text-orange-600 dark:text-orange-400','bg'=>'bg-orange-50 dark:bg-orange-900/20']
               : ['bar'=>'bg-purple-500','text'=>'text-purple-600 dark:text-purple-400','bg'=>'bg-purple-50 dark:bg-purple-900/20']);
    @endphp
    <div class="card">
        <div class="flex items-start justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 {{ $color['bg'] }} rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 {{ $color['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">Durée (heures)</p>
                    <p class="text-xs text-gray-400">Temps de charge cumulé</p>
                </div>
            </div>
            <span class="text-sm font-bold {{ $color['text'] }}">{{ $pct }}%</span>
        </div>
        <div class="relative h-3 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden mb-3">
            <div class="absolute inset-y-0 left-0 {{ $color['bar'] }} rounded-full transition-all duration-700" style="width:{{ $pct }}%"></div>
        </div>
        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
            <span>Utilisées : <strong class="text-gray-900 dark:text-gray-100">{{ $usedH }}h</strong></span>
            <span>Restantes : <strong class="text-gray-900 dark:text-gray-100">{{ $remH }}h</strong></span>
        </div>
        <p class="text-xs text-gray-400 mt-2 pt-2 border-t border-gray-100 dark:border-gray-700">Total : {{ $maxH }}h incluses</p>
    </div>
    @endif

</div>

@else
<div class="card flex flex-col items-center justify-center py-16 text-center mb-6">
    <div class="w-16 h-16 bg-eco-green-50 dark:bg-eco-green-900/20 rounded-full flex items-center justify-center mb-4">
        <svg class="w-8 h-8 text-eco-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
        </svg>
    </div>
    <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">Sessions illimitées</h3>
    <p class="text-sm text-gray-500 dark:text-gray-400">Ce forfait offre un accès illimité — aucun quota à suivre.</p>
</div>
@endif

{{-- Usage log --}}
@if(isset($subscription->usageLogs) && $subscription->usageLogs->count() > 0)
<div class="card p-0 overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-700">
        <h2 class="font-semibold text-gray-900 dark:text-gray-100">Historique des sessions</h2>
        <span class="text-xs text-gray-400">{{ $subscription->usageLogs->count() }} entrée(s)</span>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sessions</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">kWh</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Durée</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-800">
                @foreach($subscription->usageLogs->sortByDesc('created_at')->take(20) as $log)
                <tr class="hover:bg-eco-green-50/30 dark:hover:bg-eco-green-900/10 transition-colors">
                    <td class="px-5 py-3 whitespace-nowrap text-gray-600 dark:text-gray-400">
                        {{ $log->created_at->format('d/m/Y H:i') }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        @if($log->sessions_delta)
                        <span class="inline-flex items-center gap-1 font-medium text-eco-green-600 dark:text-eco-green-400">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            {{ $log->sessions_delta }}
                        </span>
                        @else
                        <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-gray-700 dark:text-gray-300">
                        {{ isset($log->kwh_delta) ? number_format($log->kwh_delta,2).' kWh' : '—' }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-gray-700 dark:text-gray-300">
                        {{ isset($log->duration_delta) ? round($log->duration_delta).' min' : '—' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection
