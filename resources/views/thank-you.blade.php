@extends('layouts.app')

@section('title', 'Paiement Confirmé')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-green-50 to-blue-50 p-4">
    <div class="max-w-sm mx-auto">
        <!-- Success Icon -->
        <div class="text-center pt-8 pb-4">
            <div class="w-16 h-16 bg-green-500 rounded-full mx-auto mb-3 flex items-center justify-center">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 class="text-xl font-bold text-gray-900 mb-1">Paiement Confirmé</h1>
            <p class="text-sm text-gray-600">Transaction réussie</p>
        </div>

        <!-- Payment Card -->
        <div class="bg-white rounded-lg p-4 mb-3 shadow-sm">
            @if(isset($order->amount) && $order->amount > 0)
            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                <span class="text-sm text-gray-600">Montant</span>
                <span class="text-lg font-bold text-green-600">{{ number_format($order->amount, 2) }} €</span>
            </div>
            @elseif(isset($transaction) && $transaction->amount)
            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                <span class="text-sm text-gray-600">Montant</span>
                <span class="text-lg font-bold text-green-600">{{ number_format($transaction->amount, 2) }} €</span>
            </div>
            @endif
            
            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                <span class="text-sm text-gray-600">Méthode</span>
                <span class="text-sm font-semibold text-gray-900">{{ $paymentMethod ?? 'Carte' }}</span>
            </div>
            
            @if(isset($transaction) && $transaction->id)
            <div class="flex justify-between items-center py-2">
                <span class="text-sm text-gray-600">ID</span>
                <span class="text-xs font-mono text-gray-500">#{{ $transaction->id }}</span>
            </div>
            @endif
        </div>

        <!-- Charging Point -->
        @if(isset($chargingPoint) && $chargingPoint)
        <div class="bg-white rounded-lg p-4 mb-3 shadow-sm">
            <div class="flex items-center mb-2">
                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-2">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <span class="text-sm font-semibold text-gray-900">{{ $chargingPoint->name ?? 'Point de recharge' }}</span>
            </div>
            @if($rechargeStarted ?? false)
            <div class="mt-2 py-1.5 bg-blue-50 rounded text-center">
                <span class="text-xs font-semibold text-blue-600">Recharge en cours</span>
            </div>
            @endif
        </div>
        @endif

        <!-- Credit Balance -->
        @if($user && isset($userCreditBalance))
        <div class="bg-white rounded-lg p-4 mb-3 shadow-sm">
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">Solde crédit</span>
                <span class="text-base font-bold text-gray-900">{{ number_format($userCreditBalance, 2) }} €</span>
            </div>
        </div>
        @endif

        <!-- Action Button -->
        <div class="pt-4">
            @auth
            <a href="{{ route('dashboard') }}" class="block w-full bg-blue-600 text-white text-center py-3 rounded-lg font-semibold text-sm">
                Tableau de bord
            </a>
            @else
            <a href="{{ route('home') }}" class="block w-full bg-gray-600 text-white text-center py-3 rounded-lg font-semibold text-sm">
                Accueil
            </a>
            @endauth
        </div>

        <!-- Footer -->
        <div class="text-center mt-6 pt-4">
            <p class="text-xs text-gray-400">Confirmation par email envoyée</p>
        </div>
    </div>
</div>
@endsection
