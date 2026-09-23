@extends('layouts.app')

@section('page-title', 'Paiement Réussi')

@section('content')
<div class="bg-gray-50 dark:bg-gray-900 min-h-screen flex items-center justify-center">
    <div class="max-w-2xl w-full bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8 m-4">
        <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 dark:bg-green-900/30 mb-5">
                <svg class="h-8 w-8 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Paiement Réussi !</h1>
            <p class="mt-3 text-lg text-gray-600 dark:text-gray-400">Votre recharge a été effectuée avec succès via Stripe.</p>
            <p class="mt-1 text-sm text-gray-500">Le crédit a été ajouté à votre compte.</p>
        </div>

        <div class="mt-8 text-center">
            <a href="{{ route('credit-recharge.index') }}" 
               class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white font-bold rounded-xl transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-green-500/30 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                <span>Retour aux Recharges</span>
            </a>
        </div>
    </div>
</div>
@endsection