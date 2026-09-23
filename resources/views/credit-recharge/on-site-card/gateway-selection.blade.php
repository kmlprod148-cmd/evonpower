@extends('layouts.app')

@section('page-title', 'Choisir votre Passerelle de Paiement')

@section('content')
<div class="bg-gray-50 dark:bg-gray-900 min-h-screen py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                Choisir votre Passerelle de Paiement
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                Recharge de crédit #{{ $creditRecharge->reference }} - {{ number_format($creditRecharge->amount, 2) }} {{ $creditRecharge->currency }}
            </p>
            <p class="text-sm text-gray-500 dark:text-gray-500 mt-2">
                Après le paiement en ligne, vous devrez vous présenter sur place pour la confirmation finale.
            </p>
        </div>

        <!-- Gateway Selection Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            @foreach($gateways as $gatewayKey => $gateway)
            <form method="POST" action="{{ route('credit-recharge.on-site-card.select-gateway', $creditRecharge->id) }}" class="gateway-card">
                @csrf
                <input type="hidden" name="gateway" value="{{ $gatewayKey }}">
                
                <button type="submit" class="w-full text-left p-6 bg-white dark:bg-gray-800 rounded-xl shadow-lg border-2 border-gray-200 dark:border-gray-700 hover:border-{{ $gatewayKey === 'stripe' ? 'blue' : 'green' }}-500 dark:hover:border-{{ $gatewayKey === 'stripe' ? 'blue' : 'green' }}-400 transition-all duration-300 transform hover:scale-105 hover:shadow-xl">
                    <div class="flex items-start space-x-4">
                        <!-- Icon -->
                        <div class="p-3 {{ $gateway['bgColor'] }} rounded-xl">
                            <i class="{{ $gateway['icon'] }} text-2xl {{ $gateway['color'] }}"></i>
                        </div>
                        
                        <!-- Content -->
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                                {{ $gateway['name'] }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                {{ $gateway['description'] }}
                            </p>
                            
                            <!-- Features -->
                            <div class="space-y-2">
                                <div class="flex items-center space-x-2 text-xs text-gray-500 dark:text-gray-400">
                                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>Paiement sécurisé</span>
                                </div>
                                <div class="flex items-center space-x-2 text-xs text-gray-500 dark:text-gray-400">
                                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>Confirmation sur place requise</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Arrow -->
                        <div class="flex items-center">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </div>
                </button>
            </form>
            @endforeach
        </div>

        <!-- Information Box -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6">
            <div class="flex items-start space-x-3">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-200 mb-2">
                        Processus de paiement sur place
                    </h3>
                    <ol class="text-sm text-blue-800 dark:text-blue-300 space-y-1 list-decimal list-inside">
                        <li>Choisissez votre passerelle de paiement ci-dessus</li>
                        <li>Effectuez le paiement en ligne de manière sécurisée</li>
                        <li>Présentez-vous sur place avec votre carte bancaire</li>
                        <li>Le personnel validera votre paiement et ajoutera le crédit à votre compte</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Back Button -->
        <div class="mt-6 text-center">
            <a href="{{ route('credit-recharge.index') }}" class="inline-flex items-center space-x-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>Retour à la liste des recharges</span>
            </a>
        </div>
    </div>
</div>

<style>
.gateway-card button:focus {
    outline: none;
    ring: 2px;
    ring-offset: 2px;
    ring-color: rgba(59, 130, 246, 0.5);
}
</style>
@endsection

