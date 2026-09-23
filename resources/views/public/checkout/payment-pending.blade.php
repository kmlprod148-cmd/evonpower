@extends('layouts.guest')

@section('title', __('Paiement en attente'))

@section('content')
<div class="min-h-screen bg-gray-50 py-12" x-data="{ checking: false }" x-init="setTimeout(() => window.location.reload(), 5000)">
    <div class="max-w-lg mx-auto px-4 text-center">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
            <div class="w-20 h-20 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-clock text-4xl text-yellow-500 animate-pulse"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ __('Paiement en cours de vérification') }}</h1>
            <p class="text-gray-500 mb-6">{{ __('Votre paiement est en cours de traitement. Cette page se rafraîchit automatiquement.') }}</p>

            @if ($checkoutSession)
            <div class="bg-gray-50 rounded-xl p-4 mb-6 text-left space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">{{ __('Réservation #') }}</span>
                    <span class="font-medium">{{ $checkoutSession->id }}</span>
                </div>
                @if ($transaction)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">{{ __('Transaction') }}</span>
                    <span class="font-medium font-mono text-xs">{{ $transaction->reference ?? $transaction->id }}</span>
                </div>
                @endif
            </div>
            @endif

            <div class="flex justify-center mb-6">
                <div class="flex space-x-1">
                    <div class="w-2 h-2 bg-yellow-400 rounded-full animate-bounce" style="animation-delay: 0s"></div>
                    <div class="w-2 h-2 bg-yellow-400 rounded-full animate-bounce" style="animation-delay: 0.15s"></div>
                    <div class="w-2 h-2 bg-yellow-400 rounded-full animate-bounce" style="animation-delay: 0.3s"></div>
                </div>
            </div>

            <p class="text-xs text-gray-400">{{ __('Rafraîchissement automatique dans 5 secondes...') }}</p>
        </div>
    </div>
</div>
@endsection
