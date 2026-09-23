@extends('layouts.public')

@section('title', 'Paiement Confirmé')

@section('content')
<!-- Mobile-first responsive design - works on all devices -->
<div class="min-h-screen bg-gradient-to-br from-green-50 to-blue-50 p-3 sm:p-4 md:p-6">
    <div class="max-w-sm sm:max-w-md md:max-w-lg mx-auto">
        <!-- Success Icon -->
        <div class="text-center pt-6 sm:pt-8 pb-3 sm:pb-4">
            <div class="w-14 h-14 sm:w-16 sm:h-16 md:w-20 md:h-20 bg-green-500 rounded-full mx-auto mb-2 sm:mb-3 flex items-center justify-center">
                <svg class="w-7 h-7 sm:w-8 sm:h-8 md:w-10 md:h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 class="text-lg sm:text-xl md:text-2xl font-bold text-gray-900 mb-1">Paiement Confirmé</h1>
            <p class="text-xs sm:text-sm text-gray-600">Transaction réussie</p>
        </div>

        <!-- Payment Card -->
        <div class="bg-white rounded-lg p-3 sm:p-4 mb-2 sm:mb-3 shadow-sm">
            @if(isset($order->amount) && $order->amount > 0)
            <div class="flex justify-between items-center py-1.5 sm:py-2 border-b border-gray-100">
                <span class="text-xs sm:text-sm text-gray-600">Montant</span>
                <span class="text-base sm:text-lg md:text-xl font-bold text-green-600">{{ number_format($order->amount, 2) }} €</span>
            </div>
            @elseif(isset($transaction) && $transaction && isset($transaction->amount) && $transaction->amount)
            <div class="flex justify-between items-center py-1.5 sm:py-2 border-b border-gray-100">
                <span class="text-xs sm:text-sm text-gray-600">Montant</span>
                <span class="text-base sm:text-lg md:text-xl font-bold text-green-600">{{ number_format($transaction->amount, 2) }} €</span>
            </div>
            @endif
            
            <div class="flex justify-between items-center py-1.5 sm:py-2 border-b border-gray-100">
                <span class="text-xs sm:text-sm text-gray-600">Méthode</span>
                <span class="text-xs sm:text-sm font-semibold text-gray-900">{{ $paymentMethod ?? 'Carte' }}</span>
            </div>
            
            @if(isset($transaction) && $transaction && isset($transaction->id))
            <div class="flex justify-between items-center py-1.5 sm:py-2">
                <span class="text-xs sm:text-sm text-gray-600">ID</span>
                <span class="text-xs font-mono text-gray-500">#{{ $transaction->id }}</span>
            </div>
            @endif
        </div>

        <!-- Charging Point -->
        @if(isset($chargingPoint) && $chargingPoint)
        <div class="bg-white rounded-lg p-3 sm:p-4 mb-2 sm:mb-3 shadow-sm">
            <div class="flex items-center mb-1.5 sm:mb-2">
                <div class="w-7 h-7 sm:w-8 sm:h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-2">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <span class="text-xs sm:text-sm font-semibold text-gray-900">{{ $chargingPoint->name ?? 'Point de recharge' }}</span>
            </div>
            @if($rechargeStarted ?? false)
            <div class="mt-1.5 sm:mt-2 py-1 sm:py-1.5 bg-blue-50 rounded text-center">
                <span class="text-xs font-semibold text-blue-600">Recharge en cours</span>
            </div>
            @endif
        </div>
        @endif

        <!-- Credit Balance -->
        @if($user && isset($userCreditBalance))
        <div class="bg-white rounded-lg p-3 sm:p-4 mb-2 sm:mb-3 shadow-sm">
            <div class="flex justify-between items-center">
                <span class="text-xs sm:text-sm text-gray-600">Solde crédit</span>
                <span class="text-sm sm:text-base font-bold text-gray-900">{{ number_format($userCreditBalance, 2) }} €</span>
            </div>
        </div>
        @endif

        <!-- Action Button -->
        <div class="pt-2 sm:pt-3 md:pt-4">
            @auth
            <a href="{{ route('dashboard') }}" class="block w-full bg-blue-600 hover:bg-blue-700 text-white text-center py-2.5 sm:py-3 rounded-lg font-semibold text-xs sm:text-sm transition-colors">
                Tableau de bord
            </a>
            @else
            <a href="{{ route('home') }}" class="block w-full bg-gray-600 hover:bg-gray-700 text-white text-center py-2.5 sm:py-3 rounded-lg font-semibold text-xs sm:text-sm transition-colors">
                Accueil
            </a>
            @endauth
        </div>

        <!-- Footer -->
        <div class="text-center mt-4 sm:mt-5 md:mt-6 pt-2 sm:pt-3 md:pt-4">
            <p class="text-xs text-gray-400">Confirmation par email envoyée</p>
        </div>
    </div>
</div>
@endsection
