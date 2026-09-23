@extends('layouts.app')

@section('title', __('messages.client_dashboard'))
@section('page-title', __('messages.client_dashboard'))

@php
    $locale = app()->getLocale();
    $isArabic = $locale === 'ar';
    
    // Format helpers
    function formatCurrency($amount, $currency = 'EUR') {
        return number_format($amount, 2, ',', ' ') . ' ' . $currency;
    }
    
    function formatDate($date, $locale = 'fr') {
        if ($locale === 'ar') {
            return \Carbon\Carbon::parse($date)->locale('ar')->format('d/m/Y');
        }
        return \Carbon\Carbon::parse($date)->locale('fr')->format('d/m/Y H:i');
    }
    
    function getStatusBadge($status) {
        $statusValue = $status instanceof \BackedEnum ? $status->value : $status;
        $badges = [
            'completed' => ['class' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400', 'label' => __('messages.completed')],
            'confirmed' => ['class' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400', 'label' => __('messages.confirmed')],
            'pending' => ['class' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400', 'label' => __('messages.pending')],
            'pending_confirmation' => ['class' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400', 'label' => __('messages.pending')],
            'cancelled' => ['class' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400', 'label' => __('messages.cancelled')],
            'canceled' => ['class' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400', 'label' => __('messages.cancelled')],
            'active' => ['class' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400', 'label' => __('messages.in_progress')],
            'in_progress' => ['class' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400', 'label' => __('messages.in_progress')],
        ];
        
        return $badges[$statusValue] ?? ['class' => 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400', 'label' => $statusValue];
    }
@endphp

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-professional.css') }}?v={{ time() }}">
<style>
    .client-dashboard {
        position: relative;
        min-height: calc(100vh - 64px);
    }
    .client-header {
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        padding: 2rem;
        border-radius: 1rem;
        color: white;
        margin-bottom: 1.5rem;
    }
    .client-stat-card {
        background: white;
        border-radius: 1rem;
        padding: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }
    .client-stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
    }
    .stat-icon-circle {
        width: 56px;
        height: 56px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .reservations-card {
        background: white;
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .reservation-item {
        padding: 1rem;
        border-bottom: 1px solid #f3f4f6;
        transition: background 0.2s;
    }
    .reservation-item:hover {
        background: #f9fafb;
    }
    .quick-action-btn {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.875rem 1rem;
        border-radius: 0.75rem;
        transition: all 0.2s;
        color: #374151;
    }
    .quick-action-btn:hover {
        background: #f3f4f6;
    }
    .action-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .empty-state {
        text-align: center;
        padding: 3rem;
    }
    .empty-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: #f3f4f6;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
    }
</style>
@endpush

@section('content')
<div class="client-dashboard" dir="{{ $isArabic ? 'rtl' : 'ltr' }}">
    {{-- Client Header --}}
    <header class="client-header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm">
                    <span class="text-2xl font-bold">{{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}</span>
                </div>
                <div>
                    <h1 class="text-2xl font-bold">
                        {{ __('messages.welcome_back') }}, {{ explode(' ', Auth::user()->name)[0] }}!
                    </h1>
                    <p class="text-green-100 mt-1">
                        {{ __('messages.client_dashboard_subtitle') }}
                    </p>
                </div>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('reservations.index') }}" 
                   class="inline-flex items-center px-5 py-2.5 bg-green-600 text-white rounded-xl font-semibold hover:bg-green-700 transition-all shadow-md">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    {{ __('messages.my_reservations') }}
                </a>
                <a href="{{ route('credit-recharge.index') }}" 
                   class="inline-flex items-center px-5 py-2.5 bg-green-700/50 text-white rounded-xl font-semibold hover:bg-green-800 transition-all backdrop-blur-sm">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ __('messages.recharge') }}
                </a>
            </div>
        </div>
    </header>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- Balance Card --}}
        <div class="client-stat-card">
            <div class="flex items-center justify-between mb-4">
                <div class="stat-icon-circle" style="background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%); box-shadow: 0 8px 16px -4px rgba(34, 197, 94, 0.4);">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium px-2 py-1 bg-green-100 text-green-700 rounded-full">{{ __('messages.current') }}</span>
            </div>
            <p class="text-sm text-gray-500">{{ __('messages.current_balance') }}</p>
            <p class="text-2xl font-bold text-gray-900 mt-1" data-balance-value>{{ $stats['balance'] }}</p>
            <div class="mt-3 flex flex-wrap gap-3 text-sm">
                <a href="{{ route('credit-recharge.index') }}" class="inline-flex items-center text-green-600 font-medium hover:underline">
                    {{ __('messages.recharge_wallet') }} &rarr;
                </a>
                <a href="{{ route('balances.index') }}" class="inline-flex items-center text-gray-600 font-medium hover:underline">
                    {{ __('messages.view_balance_history') }} &rarr;
                </a>
            </div>
        </div>

        {{-- Total Reservations Card --}}
        <div class="client-stat-card">
            <div class="flex items-center justify-between mb-4">
                <div class="stat-icon-circle" style="background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%); box-shadow: 0 8px 16px -4px rgba(59, 130, 246, 0.4);">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
            <p class="text-sm text-gray-500">{{ __('messages.total_reservations') }}</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['total_reservations'] }}</p>
            <div class="mt-3 flex gap-3 text-xs">
                <span class="flex items-center gap-1 text-green-600">
                    <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                    {{ $stats['completed_reservations'] }} {{ __('messages.completed') }}
                </span>
                <span class="flex items-center gap-1 text-yellow-600">
                    <span class="w-2 h-2 bg-yellow-500 rounded-full"></span>
                    {{ $stats['pending_reservations'] }} {{ __('messages.pending') }}
                </span>
            </div>
        </div>

        {{-- Energy Card --}}
        <div class="client-stat-card">
            <div class="flex items-center justify-between mb-4">
                <div class="stat-icon-circle" style="background: linear-gradient(135deg, #c084fc 0%, #a855f7 100%); box-shadow: 0 8px 16px -4px rgba(168, 85, 247, 0.4);">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
            </div>
            <p class="text-sm text-gray-500">{{ __('messages.total_energy') }}</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['total_energy_kwh'] }} <span class="text-sm font-normal text-gray-500">kWh</span></p>
            <p class="mt-3 text-xs text-gray-500">{{ $stats['total_transactions'] }} {{ __('messages.sessions') }}</p>
        </div>

        {{-- Total Spent Card --}}
        <div class="client-stat-card">
            <div class="flex items-center justify-between mb-4">
                <div class="stat-icon-circle" style="background: linear-gradient(135deg, #fb923c 0%, #f97316 100%); box-shadow: 0 8px 16px -4px rgba(249, 115, 22, 0.4);">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-sm text-gray-500">{{ __('messages.total_spent') }}</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ formatCurrency($stats['total_spent']) }}</p>
            <p class="mt-3 text-xs text-gray-500">{{ __('messages.total_recharged') }}: {{ formatCurrency($stats['total_recharged']) }}</p>
        </div>
    </div>

    {{-- Main Content Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Recent Reservations --}}
        <div class="lg:col-span-2 reservations-card">
            <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="stat-icon-circle" style="background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%);">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h2 class="text-lg font-semibold text-gray-900">{{ __('messages.recent_reservations') }}</h2>
                </div>
                <a href="{{ route('reservations.index') }}" class="text-sm text-green-600 font-medium hover:underline">
                    {{ __('messages.view_all') }} →
                </a>
            </div>
            <div>
                @if($recentReservations->isEmpty())
                    <div class="empty-state">
                        <div class="empty-icon">
                            <svg class="w-10 h-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('messages.no_reservations_yet') }}</h3>
                        <p class="text-gray-500 mb-4">{{ __('messages.start_charging_message') }}</p>
                        <a href="{{ route('reservations.index') }}" class="inline-flex items-center px-5 py-2.5 bg-green-600 text-white rounded-xl font-medium hover:bg-green-700 transition-colors">
                            {{ __('messages.find_charging_point') }}
                        </a>
                    </div>
                @else
                    @foreach($recentReservations as $reservation)
                        @php
                            $badge = getStatusBadge($reservation->status);
                            $chargingPoint = $reservation->chargingPoint;
                        @endphp
                        <a href="{{ route('reservations.show', $reservation->id) }}" class="reservation-item flex items-center justify-between group">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900">
                                        {{ $chargingPoint->name ?? __('messages.charging_point') }} #{{ $chargingPoint->id ?? 'N/A' }}
                                    </p>
                                    <p class="text-sm text-gray-500">{{ formatDate($reservation->start_time, $locale) }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="px-3 py-1.5 text-xs font-semibold rounded-full {{ $badge['class'] }}">
                                    {{ $badge['label'] }}
                                </span>
                                <svg class="w-5 h-5 text-gray-400 group-hover:text-green-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>
                        </a>
                    @endforeach
                @endif
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Wallet Activity --}}
            <div class="reservations-card">
                <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="stat-icon-circle" style="background: linear-gradient(135deg, #34d399 0%, #10b981 100%);">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ __('messages.credits_history') }}</h2>
                    </div>
                    <a href="{{ route('balances.index') }}" class="text-sm text-green-600 font-medium hover:underline">
                        {{ __('messages.view_all') }} &rarr;
                    </a>
                </div>
                <div class="p-4 space-y-3">
                    @forelse($walletTransactions as $walletTransaction)
                        @php
                            $isCredit = $walletTransaction->type === 'credit';
                            $amountClass = $isCredit ? 'text-green-600' : 'text-red-600';
                            $description = $walletTransaction->description && $walletTransaction->description !== 'N/A'
                                ? $walletTransaction->description
                                : ucfirst($walletTransaction->type);
                        @endphp
                        <div class="p-3 bg-gray-50 dark:bg-gray-800/40 rounded-xl border border-gray-100 dark:border-gray-700">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">{{ $description }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ formatDate($walletTransaction->created_at, $locale) }}</p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ __('messages.current_balance') }}: {{ $walletTransaction->formatted_current_balance }}
                                    </p>
                                </div>
                                <p class="text-sm font-semibold {{ $amountClass }}">{{ $walletTransaction->formatted_amount }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-sm text-gray-500 py-4">
                            {{ __('messages.no_transactions_found') }}
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Upcoming Reservations --}}
            @if($upcomingReservations->isNotEmpty())
            <div class="reservations-card">
                <div class="p-5 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="stat-icon-circle" style="background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ __('messages.upcoming_reservations') }}</h2>
                    </div>
                </div>
                <div class="p-4 space-y-3">
                    @foreach($upcomingReservations as $reservation)
                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-100 dark:border-blue-800">
                            <p class="font-semibold text-gray-900">
                                {{ $reservation->chargingPoint->name ?? __('messages.charging_point') }}
                            </p>
                            <p class="text-sm text-blue-600 dark:text-blue-400 mt-1">
                                {{ formatDate($reservation->start_time, $locale) }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Quick Actions --}}
            <div class="reservations-card">
                <div class="p-5 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="stat-icon-circle" style="background: linear-gradient(135deg, #c084fc 0%, #a855f7 100%);">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ __('messages.quick_actions') }}</h2>
                    </div>
                </div>
                <div class="p-4 space-y-1">
                    <a href="{{ route('reservations.index') }}" class="quick-action-btn">
                        <div class="action-icon bg-green-100 dark:bg-green-900/30">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                        </div>
                        <span class="font-medium text-gray-900">{{ __('messages.new_reservation') }}</span>
                    </a>
                    
                    {{-- Recharge with Stripe --}}
                    <a href="{{ route('credit-recharge.index') }}?payment_method=stripe" class="quick-action-btn">
                        <div class="action-icon bg-purple-100 dark:bg-purple-900/30">
                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                        </div>
                        <span class="font-medium text-gray-900">Stripe</span>
                    </a>

                    {{-- Recharge with CMI --}}
                    <a href="{{ route('credit-recharge.index') }}?payment_method=cmi" class="quick-action-btn">
                        <div class="action-icon bg-blue-100 dark:bg-blue-900/30">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                        </div>
                        <span class="font-medium text-gray-900">CMI</span>
                    </a>
                    
                    {{-- Recharge with Offline --}}
                    <a href="{{ route('credit-recharge.index') }}?payment_method=offline" class="quick-action-btn">
                        <div class="action-icon bg-gray-100 dark:bg-gray-700">
                            <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <span class="font-medium text-gray-900">Hors Ligne</span>
                    </a>
                    
                    <a href="{{ route('transactions.index') }}" class="quick-action-btn">
                        <div class="action-icon bg-purple-100 dark:bg-purple-900/30">
                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                        <span class="font-medium text-gray-900">{{ __('messages.transaction_history') }}</span>
                    </a>
                    
                    <a href="{{ route('settings.index') }}" class="quick-action-btn">
                        <div class="action-icon bg-gray-100 dark:bg-gray-700">
                            <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <span class="font-medium text-gray-900">{{ __('messages.settings') }}</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Payment Methods Info for Clients --}}
    <div class="reservations-card mt-6">
        <div class="p-5 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
                {{ __('messages.payment_methods') }}
            </h3>
        </div>
        <div class="p-5">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- Stripe --}}
                <div class="payment-method-card p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl border border-purple-200 dark:border-purple-800">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                        </div>
                        <span class="font-semibold text-purple-700 dark:text-purple-400">Stripe</span>
                    </div>
                    <p class="text-sm text-purple-600 dark:text-purple-400">Paiement par carte bancaire (International)</p>
                    <p class="text-xs text-green-600 font-medium mt-1">✓ Approbation automatique - Montant crédité instantanément</p>
                </div>

                {{-- CMI --}}
                <div class="payment-method-card p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                        </div>
                        <span class="font-semibold text-blue-700 dark:text-blue-400">CMI</span>
                    </div>
                    <p class="text-sm text-blue-600 dark:text-blue-400">Paiement par carte bancaire (Maroc)</p>
                    <p class="text-xs text-green-600 font-medium mt-1">✓ Approbation automatique - Montant crédité instantanément</p>
                </div>

                {{-- Hors Ligne --}}
                <div class="payment-method-card p-4 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="p-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <span class="font-semibold text-gray-700 dark:text-gray-400">Hors Ligne</span>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Paiement manuel avec confirmation admin</p>
                    <p class="text-xs text-yellow-600 font-medium mt-1">⏳ Soumis à confirmation par un administrateur</p>
                </div>
            </div>
            <div class="mt-4 text-center">
                <a href="{{ route('credit-recharge.index') }}" class="inline-flex items-center px-6 py-2.5 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Recharger mon crédit
                </a>
            </div>
        </div>
    </div>

    {{-- Recent Transactions --}}
    @if($recentTransactions->isNotEmpty())
    <div class="reservations-card mt-6">
        <div class="p-5 border-b border-gray-100 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="stat-icon-circle" style="background: linear-gradient(135deg, #c084fc 0%, #a855f7 100%);">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <h2 class="text-lg font-semibold text-gray-900">{{ __('messages.recent_transactions') }}</h2>
            </div>
            <a href="{{ route('transactions.index') }}" class="text-sm text-green-600 font-medium hover:underline">
                {{ __('messages.view_all') }} →
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700/50">
                        <th class="px-5 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('messages.date') }}</th>
                        <th class="px-5 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('messages.charging_point') }}</th>
                        <th class="px-5 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('messages.energy') }}</th>
                        <th class="px-5 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('messages.amount') }}</th>
                        <th class="px-5 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('messages.status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($recentTransactions as $transaction)
                        @php
                            $badge = getStatusBadge($transaction->status);
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-5 py-4 text-sm text-gray-900">{{ formatDate($transaction->created_at, $locale) }}</td>
                            <td class="px-5 py-4 text-sm font-medium text-gray-900">{{ $transaction->chargingPoint->name ?? __('messages.unknown') }}</td>
                            <td class="px-5 py-4 text-sm text-gray-900">{{ $transaction->energy_delivered ?? 0 }} kWh</td>
                            <td class="px-5 py-4 text-sm font-bold text-gray-900">{{ formatCurrency($transaction->total_amount ?? 0) }}</td>
                            <td class="px-5 py-4">
                                <span class="px-3 py-1 text-xs font-semibold rounded-full {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
