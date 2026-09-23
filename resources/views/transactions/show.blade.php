@extends('layouts.app')

@section('title', __('Transaction') . ' #' . $transaction->id)

@push('styles')
<style>
    .tx-show-content { padding-bottom: 100px; }
    
    /* Compact card design */
    .tx-card {
        background: white;
        border-radius: 0.75rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        margin-bottom: 1rem;
        overflow: hidden;
    }
    .dark .tx-card { background: #1f2937; }
    
    .tx-card-header {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 600;
        font-size: 0.875rem;
        color: #374151;
    }
    .dark .tx-card-header { border-color: #374151; color: #e5e7eb; }
    
    .tx-card-body { padding: 1rem; }
    
    /* Status badge */
    .tx-status {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .tx-status-completed { background: #d1fae5; color: #065f46; }
    .tx-status-pending { background: #fef3c7; color: #92400e; }
    .tx-status-failed { background: #fee2e2; color: #991b1b; }
    .dark .tx-status-completed { background: #065f46; color: #d1fae5; }
    .dark .tx-status-pending { background: #92400e; color: #fef3c7; }
    .dark .tx-status-failed { background: #991b1b; color: #fee2e2; }
    
    /* Info row compact */
    .tx-info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
        border-bottom: 1px solid #f3f4f6;
        font-size: 0.875rem;
    }
    .tx-info-row:last-child { border-bottom: none; }
    .dark .tx-info-row { border-color: #374151; }
    
    .tx-info-label { color: #6b7280; }
    .dark .tx-info-label { color: #9ca3af; }
    
    .tx-info-value { font-weight: 500; color: #111827; }
    .dark .tx-info-value { color: #f3f4f6; }
    
    /* Amount highlight */
    .tx-amount-large {
        font-size: 1.5rem;
        font-weight: 700;
        color: #059669;
    }
    
    /* Repartition grid */
    .tx-repartition-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.75rem;
    }
    @media (max-width: 640px) {
        .tx-repartition-grid { grid-template-columns: 1fr; }
    }
    
    .tx-repartition-item {
        text-align: center;
        padding: 0.75rem;
        border-radius: 0.5rem;
        background: #f9fafb;
    }
    .dark .tx-repartition-item { background: #374151; }
    
    .tx-repartition-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #6b7280;
        margin-bottom: 0.25rem;
    }
    .dark .tx-repartition-label { color: #9ca3af; }
    
    .tx-repartition-value {
        font-size: 1rem;
        font-weight: 600;
    }
    
    .tx-admin { border-left: 3px solid #ef4444; }
    .tx-admin .tx-repartition-value { color: #ef4444; }
    
    .tx-integrator { border-left: 3px solid #3b82f6; }
    .tx-integrator .tx-repartition-value { color: #3b82f6; }
    
    .tx-operator { border-left: 3px solid #10b981; }
    .tx-operator .tx-repartition-value { color: #10b981; }
    
    /* Quick stats */
    .tx-quick-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0.75rem;
        margin-bottom: 1rem;
    }
    @media (max-width: 768px) {
        .tx-quick-stats { grid-template-columns: repeat(2, 1fr); }
    }
    
    .tx-stat {
        background: white;
        border-radius: 0.75rem;
        padding: 0.75rem;
        text-align: center;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    }
    .dark .tx-stat { background: #1f2937; }
    
    .tx-stat-label {
        font-size: 0.7rem;
        color: #6b7280;
        margin-bottom: 0.25rem;
    }
    .dark .tx-stat-label { color: #9ca3af; }
    
    .tx-stat-value {
        font-size: 0.9rem;
        font-weight: 600;
        color: #111827;
    }
    .dark .tx-stat-value { color: #f3f4f6; }
    
    /* Back button */
    .tx-back-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: #f3f4f6;
        color: #374151;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        text-decoration: none;
        transition: all 0.2s;
    }
    .tx-back-btn:hover { background: #e5e7eb; }
    .dark .tx-back-btn { background: #374151; color: #e5e7eb; }
    .dark .tx-back-btn:hover { background: #4b5563; }
    
    /* Page header */
    .tx-page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        flex-wrap: wrap;
        gap: 0.75rem;
    }
    
    .tx-page-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #111827;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .dark .tx-page-title { color: #f3f4f6; }
</style>
@endpush

@section('content')
@php
    $currentUser = auth()->user();
    $isAdmin = $currentUser && $currentUser->hasRole(['admin', 'super-admin']);
    
    // Get user
    $transactionUser = $transaction->user ?? $transaction->reservation?->user ?? null;
    
    // Calculate amounts
    try {
        $httcService = new \App\Services\TransactionHTTTCService();
        $amounts = $httcService->calculateHTTTC($transaction);
        $amountHT = $amounts['ht'];
        $amountTTC = $amounts['ttc'];
    } catch (\Exception $e) {
        $amountHT = $transaction->amount ?? 0;
        $amountTTC = $transaction->price_total ?? $transaction->amount ?? 0;
    }
    
    // Get repartition
    $adminAmount = $repartition->admin_amount ?? $transaction->admin_commission ?? 0;
    $integratorAmount = $repartition->integrator_amount ?? $transaction->integrator_commission ?? 0;
    $operatorAmount = $repartition->operator_amount ?? $transaction->partner_commission ?? 0;
    
    $currency = $transaction->currency ?? 'EUR';
    $currencySymbol = $currency === 'MAD' ? 'DH' : '€';
@endphp

<div class="tx-show-content p-4">
    {{-- Header --}}
    <div class="tx-page-header">
        <div class="tx-page-title">
            <i class="fas fa-receipt text-eco-green-600"></i>
            {{ __('Transaction') }} #{{ $transaction->id }}
            <span class="tx-status tx-status-{{ $transaction->status }}">
                <i class="fas fa-{{ $transaction->status === 'completed' ? 'check' : ($transaction->status === 'pending' ? 'clock' : 'times') }}"></i>
                {{ ucfirst($transaction->status) }}
            </span>
        </div>
        <a href="{{ route('transactions.index') }}" class="tx-back-btn">
            <i class="fas fa-arrow-left"></i>
            {{ __('Back') }}
        </a>
    </div>

    {{-- Quick Stats --}}
    <div class="tx-quick-stats">
        <div class="tx-stat">
            <div class="tx-stat-label">{{ __('Amount TTC') }}</div>
            <div class="tx-stat-value text-green-600">{{ number_format($amountTTC, 2) }} {{ $currencySymbol }}</div>
        </div>
        <div class="tx-stat">
            <div class="tx-stat-label">{{ __('Amount HT') }}</div>
            <div class="tx-stat-value">{{ number_format($amountHT, 2) }} {{ $currencySymbol }}</div>
        </div>
        <div class="tx-stat">
            <div class="tx-stat-label">{{ __('Date') }}</div>
            <div class="tx-stat-value">{{ $transaction->created_at->format('d/m/Y H:i') }}</div>
        </div>
        <div class="tx-stat">
            <div class="tx-stat-label">{{ __('Status') }}</div>
            <div class="tx-stat-value">{{ ucfirst($transaction->status) }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {{-- Transaction Details --}}
        <div class="tx-card">
            <div class="tx-card-header">
                <i class="fas fa-info-circle text-blue-500"></i>
                {{ __('Transaction Details') }}
            </div>
            <div class="tx-card-body">
                <div class="tx-info-row">
                    <span class="tx-info-label">{{ __('Transaction ID') }}</span>
                    <span class="tx-info-value">#{{ $transaction->id }}</span>
                </div>
                @if($transaction->transaction_id)
                <div class="tx-info-row">
                    <span class="tx-info-label">{{ __('External ID') }}</span>
                    <span class="tx-info-value text-xs">{{ Str::limit($transaction->transaction_id, 20) }}</span>
                </div>
                @endif
                <div class="tx-info-row">
                    <span class="tx-info-label">{{ __('Amount TTC') }}</span>
                    <span class="tx-info-value text-green-600 font-bold">{{ number_format($amountTTC, 2) }} {{ $currencySymbol }}</span>
                </div>
                <div class="tx-info-row">
                    <span class="tx-info-label">{{ __('Amount HT') }}</span>
                    <span class="tx-info-value">{{ number_format($amountHT, 2) }} {{ $currencySymbol }}</span>
                </div>
                <div class="tx-info-row">
                    <span class="tx-info-label">{{ __('Currency') }}</span>
                    <span class="tx-info-value">{{ $currency }}</span>
                </div>
                <div class="tx-info-row">
                    <span class="tx-info-label">{{ __('Created') }}</span>
                    <span class="tx-info-value">{{ $transaction->created_at->format('d/m/Y H:i:s') }}</span>
                </div>
            </div>
        </div>

        {{-- User & Charging Point --}}
        <div class="tx-card">
            <div class="tx-card-header">
                <i class="fas fa-user text-purple-500"></i>
                {{ __('User & Location') }}
            </div>
            <div class="tx-card-body">
                @if($transactionUser)
                <div class="tx-info-row">
                    <span class="tx-info-label">{{ __('User') }}</span>
                    <span class="tx-info-value">{{ $transactionUser->name }}</span>
                </div>
                <div class="tx-info-row">
                    <span class="tx-info-label">{{ __('Email') }}</span>
                    <span class="tx-info-value text-xs">{{ $transactionUser->email }}</span>
                </div>
                @endif
                @if($transaction->chargingPoint)
                <div class="tx-info-row">
                    <span class="tx-info-label">{{ __('Charging Point') }}</span>
                    <span class="tx-info-value">{{ $transaction->chargingPoint->name ?? 'N/A' }}</span>
                </div>
                @if($transaction->chargingPoint->group)
                <div class="tx-info-row">
                    <span class="tx-info-label">{{ __('Group') }}</span>
                    <span class="tx-info-value">{{ $transaction->chargingPoint->group->name ?? 'N/A' }}</span>
                </div>
                @endif
                @endif
                @if($transaction->reservation_id)
                <div class="tx-info-row">
                    <span class="tx-info-label">{{ __('Reservation') }}</span>
                    <span class="tx-info-value">
                        <a href="{{ route('reservations.show', $transaction->reservation_id) }}" class="text-blue-600 hover:underline">
                            #{{ $transaction->reservation_id }}
                        </a>
                    </span>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Revenue Distribution --}}
    @if($isAdmin || $adminAmount > 0 || $integratorAmount > 0 || $operatorAmount > 0)
    <div class="tx-card">
        <div class="tx-card-header">
            <i class="fas fa-chart-pie text-green-500"></i>
            {{ __('Revenue Distribution') }}
        </div>
        <div class="tx-card-body">
            <div class="tx-repartition-grid">
                <div class="tx-repartition-item tx-admin">
                    <div class="tx-repartition-label">{{ __('Admin') }}</div>
                    <div class="tx-repartition-value">{{ number_format($adminAmount, 2) }} {{ $currencySymbol }}</div>
                </div>
                <div class="tx-repartition-item tx-integrator">
                    <div class="tx-repartition-label">{{ __('Integrator') }}</div>
                    <div class="tx-repartition-value">{{ number_format($integratorAmount, 2) }} {{ $currencySymbol }}</div>
                </div>
                <div class="tx-repartition-item tx-operator">
                    <div class="tx-repartition-label">{{ __('Operator') }}</div>
                    <div class="tx-repartition-value">{{ number_format($operatorAmount, 2) }} {{ $currencySymbol }}</div>
                </div>
            </div>
            
            {{-- Total verification --}}
            <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                <div class="tx-info-row">
                    <span class="tx-info-label">{{ __('Total Distributed') }}</span>
                    <span class="tx-info-value font-bold">
                        {{ number_format($adminAmount + $integratorAmount + $operatorAmount, 2) }} {{ $currencySymbol }}
                    </span>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Session Details (if available) --}}
    @if($transaction->energy_delivered || $transaction->duration || $transaction->meter_start !== null)
    <div class="tx-card">
        <div class="tx-card-header">
            <i class="fas fa-bolt text-yellow-500"></i>
            {{ __('Charging Session') }}
        </div>
        <div class="tx-card-body">
            @if($transaction->energy_delivered)
            <div class="tx-info-row">
                <span class="tx-info-label">{{ __('Energy Delivered') }}</span>
                <span class="tx-info-value">{{ number_format($transaction->energy_delivered, 2) }} kWh</span>
            </div>
            @endif
            @if($transaction->duration)
            <div class="tx-info-row">
                <span class="tx-info-label">{{ __('Duration') }}</span>
                <span class="tx-info-value">{{ $transaction->duration }} min</span>
            </div>
            @endif
            @if($transaction->meter_start !== null)
            <div class="tx-info-row">
                <span class="tx-info-label">{{ __('Meter Start') }}</span>
                <span class="tx-info-value">{{ $transaction->meter_start }}</span>
            </div>
            @endif
            @if($transaction->meter_stop !== null)
            <div class="tx-info-row">
                <span class="tx-info-label">{{ __('Meter Stop') }}</span>
                <span class="tx-info-value">{{ $transaction->meter_stop }}</span>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Pricing Plan (if available) --}}
    @if($transaction->pricingPlan)
    <div class="tx-card">
        <div class="tx-card-header">
            <i class="fas fa-tag text-orange-500"></i>
            {{ __('Pricing Plan') }}
        </div>
        <div class="tx-card-body">
            <div class="tx-info-row">
                <span class="tx-info-label">{{ __('Plan Name') }}</span>
                <span class="tx-info-value">{{ $transaction->pricingPlan->name ?? 'N/A' }}</span>
            </div>
            @if($transaction->pricingPlan->price_per_kwh)
            <div class="tx-info-row">
                <span class="tx-info-label">{{ __('Price/kWh') }}</span>
                <span class="tx-info-value">{{ number_format($transaction->pricingPlan->price_per_kwh, 2) }} {{ $currencySymbol }}</span>
            </div>
            @endif
            @if($transaction->pricingPlan->price_per_minute)
            <div class="tx-info-row">
                <span class="tx-info-label">{{ __('Price/min') }}</span>
                <span class="tx-info-value">{{ number_format($transaction->pricingPlan->price_per_minute, 2) }} {{ $currencySymbol }}</span>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Admin Actions --}}
    @if($isAdmin && $transactionUser)
    <div class="tx-card">
        <div class="tx-card-header">
            <i class="fas fa-cog text-gray-500"></i>
            {{ __('Actions') }}
        </div>
        <div class="tx-card-body">
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.users.show', ['id' => $transactionUser->id]) }}" 
                   class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-user mr-1.5"></i>
                    {{ __('View User') }}
                </a>
                @if($transaction->reservation_id)
                <a href="{{ route('reservations.show', $transaction->reservation_id) }}" 
                   class="inline-flex items-center px-3 py-1.5 bg-purple-600 text-white text-sm rounded-lg hover:bg-purple-700 transition">
                    <i class="fas fa-calendar-check mr-1.5"></i>
                    {{ __('View Reservation') }}
                </a>
                @endif
                @if($transaction->chargingPoint)
                <a href="{{ route('charging-points.show', $transaction->chargingPoint->id) }}" 
                   class="inline-flex items-center px-3 py-1.5 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition">
                    <i class="fas fa-charging-station mr-1.5"></i>
                    {{ __('View Charging Point') }}
                </a>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
