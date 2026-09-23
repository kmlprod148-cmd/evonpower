@extends('layouts.app')

@section('title', 'Demandes de Crédit en Attente')

@section('content')
<div class="evon-page-header">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <h1 class="evon-page-title flex items-center gap-3">
                <svg class="w-8 h-8 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Demandes en Attente
            </h1>
            <p class="evon-page-subtitle">
                @if(isset($offlineApprovalScope) && $offlineApprovalScope)
                    {{ __('messages.offline_credit_approvals_subtitle') }} — {{ __('messages.clients_having_reserved_on_your_points') }}
                @else
                    Recharges hors ligne à approuver
                @endif
            </p>
        </div>
        
        <div class="flex items-center gap-3">
            @if(isset($offlineApprovalScope) && $offlineApprovalScope)
                <a href="{{ route('credits.index') }}" class="btn-secondary">
                    ← {{ __('messages.credit_management') }}
                </a>
            @else
                <a href="{{ route('admin.credit-recharges.dashboard') }}" class="btn-secondary">
                    ← Dashboard
                </a>
                <a href="{{ route('admin.credit-recharges.index') }}" class="btn-secondary">
                    Toutes les recharges
                </a>
            @endif
        </div>
    </div>
</div>

<!-- Stats Alert -->
@if($stats['total_pending'] > 0)
<div class="evon-alert-warning mb-6">
    <div class="evon-alert-icon">
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
    </div>
    <div class="evon-alert-content">
        <p class="font-medium">{{ $stats['total_pending'] }} demande(s) en attente d'approbation</p>
        <p class="text-sm mt-1">Montant total : {{ number_format($stats['total_amount'], 2) }} MAD</p>
    </div>
</div>
@endif

<!-- Search -->
<div class="card mb-6">
    <form method="GET" action="{{ isset($offlineApprovalScope) && $offlineApprovalScope ? route('credits.offline.pending') : route('admin.credit-recharges.pending') }}" class="flex gap-4">
        <div class="flex-1">
            <input type="search" 
                   name="search" 
                   value="{{ request('search') }}"
                   placeholder="Rechercher par référence, nom du client ou email..."
                   class="input-field">
        </div>
        <button type="submit" class="btn-primary">
            Rechercher
        </button>
    </form>
</div>

<!-- Pending Recharges -->
<div class="space-y-4">
    @forelse($recharges as $recharge)
    <div class="card hover:shadow-lg transition-shadow" x-data="{ showActions: false }">
        <div class="flex items-start justify-between gap-6">
            <!-- Left: User & Recharge Info -->
            <div class="flex-1">
                <div class="flex items-start gap-4">
                    <!-- User Avatar -->
                    <div class="flex-shrink-0">
                        <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-eco-green-500 to-eco-green-600 flex items-center justify-center text-white font-bold text-xl shadow-md">
                            {{ strtoupper(substr($recharge->user->name ?? 'U', 0, 2)) }}
                        </div>
                    </div>
                    
                    <!-- Info -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-2">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ $recharge->user->name ?? 'N/A' }}
                            </h3>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">
                                EN ATTENTE
                            </span>
                        </div>
                        
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">
                            {{ $recharge->user->email ?? 'N/A' }}
                        </p>
                        
                        <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                                {{ $recharge->reference }}
                            </span>
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ $recharge->created_at->diffForHumans() }}
                            </span>
                        </div>
                        
                        @if($recharge->description)
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                            {{ $recharge->description }}
                        </p>
                        @endif
                    </div>
                </div>
            </div>
            
            <!-- Right: Amount & Actions -->
            <div class="flex-shrink-0 text-right">
                <div class="mb-4">
                    <p class="text-3xl font-bold text-eco-green-600 dark:text-eco-green-400">
                        {{ number_format($recharge->amount, 2) }}
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $recharge->currency }}</p>
                </div>
                
                <!-- Actions -->
                <div class="flex flex-col gap-2">
                    <a href="{{ (isset($offlineApprovalScope) && $offlineApprovalScope ? route('credits.offline.show', $recharge) : route('admin.credit-recharges.show', $recharge)) }}" 
                       class="btn-secondary text-sm px-4 py-2">
                        Voir Détails
                    </a>
                    
                    <form method="POST" 
                          action="{{ (isset($offlineApprovalScope) && $offlineApprovalScope ? route('credits.offline.confirm', $recharge) : route('admin.credit-recharges.confirm', $recharge)) }}"
                          onsubmit="return confirm('Confirmer cette recharge de {{ number_format($recharge->amount, 2) }} {{ $recharge->currency }} ?')">
                        @csrf
                        <button type="submit" 
                                class="w-full btn-primary text-sm px-4 py-2 bg-green-600 hover:bg-green-700">
                            ✓ Approuver
                        </button>
                    </form>
                    
                    <button @click="showActions = !showActions"
                            class="btn-secondary text-sm px-4 py-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">
                        ✗ Rejeter
                    </button>
                </div>
                
                <!-- Reject Form (Hidden by default) -->
                <div x-show="showActions" 
                     x-cloak
                     x-transition
                     class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                    <form method="POST" action="{{ (isset($offlineApprovalScope) && $offlineApprovalScope ? route('credits.offline.reject', $recharge) : route('admin.credit-recharges.reject', $recharge)) }}">
                        @csrf
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Raison du rejet *
                        </label>
                        <textarea name="reason" 
                                  required
                                  rows="3"
                                  class="input-field mb-3 text-sm"
                                  placeholder="Expliquez pourquoi cette demande est rejetée..."></textarea>
                        <div class="flex gap-2">
                            <button type="submit" class="flex-1 btn-primary bg-red-600 hover:bg-red-700 text-sm">
                                Confirmer le rejet
                            </button>
                            <button type="button" 
                                    @click="showActions = false"
                                    class="flex-1 btn-secondary text-sm">
                                Annuler
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="card">
        <div class="text-center py-12">
            <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Aucune demande en attente</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                @if(isset($offlineApprovalScope) && $offlineApprovalScope)
                    {{ __('messages.no_offline_requests_from_your_clients') }}
                @else
                    Toutes les demandes ont été traitées !
                @endif
            </p>
        </div>
    </div>
    @endforelse
</div>

<!-- Pagination -->
@if($recharges->hasPages())
<div class="mt-6">
    {{ $recharges->links() }}
</div>
@endif
@endsection
