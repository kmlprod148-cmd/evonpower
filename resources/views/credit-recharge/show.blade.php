@extends('layouts.app')

@section('page-title', 'Détails de la Recharge')

@section('content')
<div class="bg-gradient-to-b from-gray-50 to-white dark:from-gray-900 dark:to-gray-950 min-h-screen">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-6">
            <a href="{{ route('credit-recharge.index') }}" 
               class="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 mb-4">
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Retour aux recharges
            </a>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Détails de la Recharge</h1>
        </div>

        <!-- Messages -->
        @if(session('success'))
            <div class="mb-6 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 text-green-700 dark:text-green-300 p-4 rounded-lg shadow-sm" role="alert">
                <p>{{ session('success') }}</p>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 text-red-700 dark:text-red-300 p-4 rounded-lg shadow-sm" role="alert">
                <p>{{ session('error') }}</p>
            </div>
        @endif

        <!-- Card -->
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm overflow-hidden">
            <div class="p-6">
                <!-- Informations principales -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Référence</label>
                        <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $creditRecharge->reference }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Montant</label>
                        <p class="text-lg font-semibold text-green-600 dark:text-green-400">{{ $creditRecharge->formatted_amount }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Méthode de Paiement</label>
                        <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $creditRecharge->payment_method_name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Statut</label>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                            @if($creditRecharge->status === 'completed') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                            @elseif($creditRecharge->status === 'pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                            @elseif($creditRecharge->status === 'processing') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                            @elseif($creditRecharge->status === 'failed') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                            @else bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200
                            @endif">
                            {{ ucfirst($creditRecharge->status) }}
                        </span>
                    </div>
                </div>

                <!-- Dates -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Date de Création</label>
                        <p class="text-gray-900 dark:text-gray-100">{{ $creditRecharge->created_at->format('d/m/Y H:i:s') }}</p>
                    </div>
                    @if($creditRecharge->processed_at)
                    <div>
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Date de Traitement</label>
                        <p class="text-gray-900 dark:text-gray-100">{{ $creditRecharge->processed_at->format('d/m/Y H:i:s') }}</p>
                    </div>
                    @endif
                </div>

                <!-- Description -->
                @if($creditRecharge->description)
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Description</label>
                    <p class="text-gray-900 dark:text-gray-100">{{ $creditRecharge->description }}</p>
                </div>
                @endif

                <!-- Raison d'échec -->
                @if($creditRecharge->failure_reason)
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                    <label class="block text-sm font-medium text-red-800 dark:text-red-200 mb-1">Raison de l'échec</label>
                    <p class="text-red-700 dark:text-red-300">{{ $creditRecharge->failure_reason }}</p>
                </div>
                @endif

                <!-- Informations utilisateur -->
                <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Informations Utilisateur</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Nom</label>
                            <p class="text-sm text-gray-900 dark:text-gray-100">{{ $creditRecharge->user->name }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Email</label>
                            <p class="text-sm text-gray-900 dark:text-gray-100">{{ $creditRecharge->user->email }}</p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-wrap gap-4">
                    <a href="{{ route('credit-recharge.index') }}" 
                       class="px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        Retour
                    </a>
                    @if(!$creditRecharge->isCompleted() && !$creditRecharge->isFailed())
                    <form action="{{ route('credit-recharge.cancel', $creditRecharge) }}" 
                          method="POST" 
                          class="inline-block"
                          onsubmit="return confirm('Êtes-vous sûr de vouloir annuler cette recharge ?');">
                        @csrf
                        <button type="submit" 
                                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                            Annuler la Recharge
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

