@extends('layouts.app')

@section('title', 'Détails Recharge #' . $creditRecharge->reference)

@section('content')
<div class="evon-page-header">
    <div class="flex items-center gap-4 mb-4">
        <a href="{{ (isset($offlineApprovalScope) && $offlineApprovalScope ? route('credits.offline.pending') : route('admin.credit-recharges.index')) }}" 
           class="flex items-center gap-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Retour
        </a>
    </div>
    
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <h1 class="evon-page-title">Détails de la Recharge</h1>
            <p class="evon-page-subtitle">{{ $creditRecharge->reference }}</p>
        </div>
        
        <div>
            <span class="px-4 py-2 text-sm font-semibold rounded-lg
                {{ $creditRecharge->status === 'completed' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : '' }}
                {{ $creditRecharge->status === 'pending' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}
                {{ $creditRecharge->status === 'failed' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : '' }}">
                {{ strtoupper($creditRecharge->status) }}
            </span>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Info -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Client Info -->
        <div class="card">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                Informations Client
            </h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Nom</label>
                    <p class="text-base font-semibold text-gray-900 dark:text-white">{{ $creditRecharge->user->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Email</label>
                    <p class="text-base text-gray-900 dark:text-white">{{ $creditRecharge->user->email ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Téléphone</label>
                    <p class="text-base text-gray-900 dark:text-white">{{ $creditRecharge->user->phone ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Rôle</label>
                    <p class="text-base text-gray-900 dark:text-white">{{ $creditRecharge->user->roles->first()?->name ?? 'Client' }}</p>
                </div>
            </div>
            
            @if(!isset($offlineApprovalScope) || !$offlineApprovalScope)
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 flex flex-col gap-2">
                <a href="{{ route('admin.users.show', $creditRecharge->user) }}" 
                   class="text-sm font-medium text-eco-green-600 hover:text-eco-green-700 flex items-center gap-1">
                    Voir le profil complet
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
                <a href="{{ route('admin.credit-recharges.client.balance-details', $creditRecharge->user) }}" 
                   class="text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 flex items-center gap-1">
                    Voir balance crédit complète
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </a>
            </div>
            @endif
        </div>

        <!-- Recharge Details -->
        <div class="card">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Détails de la Recharge
            </h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Référence</label>
                    <p class="text-base font-mono text-gray-900 dark:text-white">{{ $creditRecharge->reference }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Montant</label>
                    <p class="text-2xl font-bold text-eco-green-600 dark:text-eco-green-400">
                        {{ number_format($creditRecharge->amount, 2) }} {{ $creditRecharge->currency }}
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Méthode de Paiement</label>
                    <span class="inline-flex px-3 py-1 text-sm font-medium rounded-full
                        {{ $creditRecharge->payment_method === 'offline' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400' : '' }}
                        {{ $creditRecharge->payment_method === 'cmi' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                        {{ $creditRecharge->payment_method === 'stripe' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400' : '' }}">
                        {{ $creditRecharge->payment_method_name }}
                    </span>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Type</label>
                    <p class="text-base text-gray-900 dark:text-white">
                        {{ $creditRecharge->is_custom ? 'Montant personnalisé' : 'Pack prédéfini' }}
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Date de Demande</label>
                    <p class="text-base text-gray-900 dark:text-white">
                        {{ $creditRecharge->created_at->format('d/m/Y à H:i') }}
                    </p>
                </div>
                @if($creditRecharge->processed_at)
                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Date de Traitement</label>
                    <p class="text-base text-gray-900 dark:text-white">
                        {{ $creditRecharge->processed_at->format('d/m/Y à H:i') }}
                    </p>
                </div>
                @endif
            </div>
            
            @if($creditRecharge->creditPack)
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Pack Utilisé</label>
                <div class="p-3 bg-eco-green-50 dark:bg-eco-green-900/20 rounded-lg">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ $creditRecharge->creditPack->name }}
                    </p>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                        {{ $creditRecharge->creditPack->description ?? '' }}
                    </p>
                </div>
            </div>
            @endif
        </div>

        <!-- Processing Info -->
        @if($creditRecharge->processor)
        <div class="card">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Traité par</h3>
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold">
                    {{ strtoupper(substr($creditRecharge->processor->name ?? 'A', 0, 2)) }}
                </div>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $creditRecharge->processor->name }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $creditRecharge->processed_at->format('d/m/Y à H:i') }}</p>
                </div>
            </div>
        </div>
        @endif
    </div>
    
    <!-- Sidebar Actions -->
    <div class="space-y-6">
        <!-- Amount Card -->
        <div class="card text-center">
            <div class="mb-2">
                <svg class="mx-auto w-12 h-12 text-eco-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <p class="text-4xl font-bold text-eco-green-600 dark:text-eco-green-400 mb-1">
                {{ number_format($creditRecharge->amount, 2) }}
            </p>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $creditRecharge->currency }}</p>
        </div>

        <!-- Actions -->
        @if($creditRecharge->status === 'pending' && $creditRecharge->payment_method === 'offline')
        <div class="card">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Actions d'Approbation</h3>
            
            <!-- Approve -->
            <form method="POST" action="{{ (isset($offlineApprovalScope) && $offlineApprovalScope ? route('credits.offline.confirm', $creditRecharge) : route('admin.credit-recharges.confirm', $creditRecharge)) }}" class="mb-3">
                @csrf
                <button type="submit" 
                        onclick="return confirm('Confirmer cette recharge de {{ number_format($creditRecharge->amount, 2) }} {{ $creditRecharge->currency }} ?')"
                        class="w-full btn-primary bg-green-600 hover:bg-green-700 flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Approuver
                </button>
            </form>
            
            <!-- Reject -->
            <div x-data="{ showReject: false }">
                <button @click="showReject = !showReject"
                        class="w-full btn-secondary text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Rejeter
                </button>
                
                <div x-show="showReject" 
                     x-cloak
                     x-transition
                     class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                    <form method="POST" action="{{ (isset($offlineApprovalScope) && $offlineApprovalScope ? route('credits.offline.reject', $creditRecharge) : route('admin.credit-recharges.reject', $creditRecharge)) }}">
                        @csrf
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Raison du rejet *
                        </label>
                        <textarea name="reason" 
                                  required
                                  rows="4"
                                  class="input-field mb-3"
                                  placeholder="Expliquez pourquoi cette demande est rejetée..."></textarea>
                        <button type="submit" class="w-full btn-primary bg-red-600 hover:bg-red-700">
                            Confirmer le Rejet
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endif

        <!-- Wallet Info -->
        @if($creditRecharge->wallet)
        <div class="card">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Wallet Client</h3>
            <div class="space-y-2">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Solde Actuel</span>
                    <span class="font-bold text-gray-900 dark:text-white">
                        {{ number_format($creditRecharge->wallet->balance, 2) }} {{ $creditRecharge->wallet->currency }}
                    </span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Après Recharge</span>
                    <span class="font-bold text-eco-green-600 dark:text-eco-green-400">
                        {{ number_format($creditRecharge->wallet->balance + $creditRecharge->amount, 2) }} {{ $creditRecharge->wallet->currency }}
                    </span>
                </div>
            </div>
        </div>
        @endif

        <!-- Timeline -->
        <div class="card">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Historique</h3>
            <div class="space-y-4">
                <div class="flex gap-3">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">Demande créée</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $creditRecharge->created_at->format('d/m/Y à H:i:s') }}</p>
                    </div>
                </div>
                
                @if($creditRecharge->processed_at)
                <div class="flex gap-3">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 rounded-full bg-{{ $creditRecharge->status === 'completed' ? 'green' : 'red' }}-500 flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                @if($creditRecharge->status === 'completed')
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                @else
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                @endif
                            </svg>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $creditRecharge->status === 'completed' ? 'Approuvée' : 'Rejetée' }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $creditRecharge->processed_at->format('d/m/Y à H:i:s') }}</p>
                        @if($creditRecharge->processor)
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Par {{ $creditRecharge->processor->name }}
                        </p>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Quick Actions -->
        <div class="card">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Actions Rapides</h3>
            <div class="space-y-2">
                @if(isset($offlineApprovalScope) && $offlineApprovalScope)
                <a href="{{ route('credits.index') }}" 
                   class="block w-full px-4 py-2 text-sm text-center bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    Gestion des crédits
                </a>
                <a href="{{ route('credits.offline.pending') }}" 
                   class="block w-full px-4 py-2 text-sm text-center bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 rounded-lg hover:bg-yellow-200 dark:hover:bg-yellow-900/50 transition-colors">
                    Voir En Attente
                </a>
                @else
                <a href="{{ route('admin.credit-recharges.index') }}" 
                   class="block w-full px-4 py-2 text-sm text-center bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    Voir Toutes les Recharges
                </a>
                <a href="{{ route('admin.credit-recharges.pending') }}" 
                   class="block w-full px-4 py-2 text-sm text-center bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 rounded-lg hover:bg-yellow-200 dark:hover:bg-yellow-900/50 transition-colors">
                    Voir En Attente
                </a>
                @endif
            </div>
        </div>

        <!-- Metadata -->
        @if($creditRecharge->metadata)
        <div class="card">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Métadonnées</h3>
            <div class="text-xs font-mono bg-gray-100 dark:bg-gray-900 p-3 rounded-lg overflow-auto max-h-64">
                <pre class="text-gray-700 dark:text-gray-300">{{ json_encode($creditRecharge->metadata, JSON_PRETTY_PRINT) }}</pre>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
