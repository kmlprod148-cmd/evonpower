@extends('layouts.app')
@section('title', $result['payment_approved'] ? 'Abonnement activé' : 'Paiement refusé')
@section('page-title', 'Résultat du paiement')

@section('content')
<div class="bg-gray-50 dark:bg-gray-950 min-h-screen flex items-start justify-center py-10">
    <div class="w-full max-w-md px-4">

        @if($result['payment_approved'] ?? false)
        {{-- SUCCESS --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-green-200 dark:border-green-800 shadow-lg overflow-hidden">
            <div class="bg-green-500 px-6 py-8 text-center text-white">
                <svg class="w-16 h-16 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h1 class="text-2xl font-bold mb-1">Paiement accepté !</h1>
                <p class="text-green-100 text-sm">Votre abonnement est maintenant actif</p>
            </div>
            <div class="p-6">
                @if(isset($subscription) && $subscription)
                <div class="space-y-3 mb-5 text-sm">
                    <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-800">
                        <span class="text-gray-500 dark:text-gray-400">Plan</span>
                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ $subscription->subscriptionPlan->name ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-800">
                        <span class="text-gray-500 dark:text-gray-400">Montant payé</span>
                        <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $subscription->formatted_amount_paid }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-800">
                        <span class="text-gray-500 dark:text-gray-400">Valide jusqu'au</span>
                        <span class="font-medium text-gray-900 dark:text-gray-100">
                            {{ $subscription->end_date ? $subscription->end_date->format('d/m/Y') : '—' }}
                        </span>
                    </div>
                    @if(isset($postData['TransId']) && $postData['TransId'])
                    <div class="flex justify-between py-2">
                        <span class="text-gray-500 dark:text-gray-400">Réf. CMI</span>
                        <span class="font-mono text-xs text-gray-700 dark:text-gray-300">{{ $postData['TransId'] }}</span>
                    </div>
                    @endif
                </div>
                @endif

                <div class="flex flex-col gap-2">
                    @if(isset($subscription) && $subscription)
                    <a href="{{ route('subscriptions.show', $subscription) }}"
                       class="block text-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition-colors text-sm">
                        Voir mon abonnement
                    </a>
                    @endif
                    <a href="{{ route('subscriptions.index') }}"
                       class="block text-center px-4 py-2.5 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 font-medium rounded-lg transition-colors text-sm">
                        Mes abonnements
                    </a>
                </div>
            </div>
        </div>

        @else
        {{-- FAILURE --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-red-200 dark:border-red-800 shadow-lg overflow-hidden">
            <div class="bg-red-500 px-6 py-8 text-center text-white">
                <svg class="w-16 h-16 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h1 class="text-2xl font-bold mb-1">Paiement refusé</h1>
                <p class="text-red-100 text-sm">{{ $result['error_message'] ?? 'Votre paiement n\'a pas pu être traité' }}</p>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-5 text-center">
                    Aucun montant n'a été débité. Vous pouvez réessayer avec une autre carte ou méthode de paiement.
                </p>

                <div class="flex flex-col gap-2">
                    @if(isset($subscription) && $subscription && $subscription->subscriptionPlan)
                    <a href="{{ route('subscriptions.checkout', $subscription->subscriptionPlan) }}"
                       class="block text-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition-colors text-sm">
                        Réessayer le paiement
                    </a>
                    @endif
                    <a href="{{ route('subscriptions.plans') }}"
                       class="block text-center px-4 py-2.5 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 font-medium rounded-lg transition-colors text-sm">
                        Voir tous les plans
                    </a>
                </div>
            </div>
        </div>
        @endif

    </div>
</div>
@endsection
