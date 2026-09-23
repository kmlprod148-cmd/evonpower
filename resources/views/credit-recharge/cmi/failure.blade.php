@extends('layouts.app')

@section('page-title', 'Paiement Échoué')

@section('content')
<div class="bg-gray-50 dark:bg-gray-900 min-h-screen flex items-center justify-center">
    <div class="max-w-2xl w-full bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8 m-4">
        <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 dark:bg-red-900/30 mb-5">
                <svg class="h-8 w-8 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Paiement Échoué</h1>
            <p class="mt-3 text-lg text-gray-600 dark:text-gray-400">Votre paiement n'a pas pu être traité.</p>
            @if(session('error'))
            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ session('error') }}</p>
            @endif
        </div>

        @if($recharge)
        <div class="mt-8 bg-gray-50 dark:bg-gray-700/50 rounded-lg p-6">
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Référence</span>
                    <span class="font-medium text-gray-900 dark:text-gray-100">#{{ $recharge->reference }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Montant</span>
                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ number_format($recharge->amount, 2) }} {{ $recharge->currency }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Statut</span>
                    <span class="px-3 py-1 text-xs font-semibold bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-full">
                        Échoué
                    </span>
                </div>
            </div>
        </div>
        @endif

        <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('credit-recharge.index') }}" 
               class="inline-flex items-center justify-center px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-xl transition-all duration-300">
                <span>Retour aux Recharges</span>
            </a>
            @if($recharge && $recharge->status === 'failed')
            <a href="{{ route('credit-recharge.index') }}" 
               class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white font-semibold rounded-xl transition-all duration-300">
                <span>Réessayer</span>
            </a>
            @endif
        </div>
    </div>
</div>
@endsection

