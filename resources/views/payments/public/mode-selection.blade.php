@extends('layouts.app')

@section('title', 'Choix du Mode de Recharge')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-xl mx-auto">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Mode de recharge</h1>

        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">{{ $chargingPoint->name }}</h2>
            
            <p class="text-gray-600 mb-6">Choisissez le mode de paiement qui vous convient :</p>

            <!-- Prépayé -->
            <a href="{{ route('public.payment.station', $chargingPointId) }}" 
               class="block border-2 border-gray-200 rounded-lg p-4 mb-4 hover:border-blue-500 hover:bg-blue-50 transition">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-medium text-gray-800">Prépayé</h3>
                        <p class="text-sm text-gray-500">Paiement immédiat. Crédit non utilisé remboursé.</p>
                    </div>
                </div>
            </a>

            <!-- Postpayé -->
            @if($consumptionMode === 'postpaid')
            <a href="{{ route('public.payment.postpaid.start', $chargingPointId) }}" 
               class="block border-2 border-gray-200 rounded-lg p-4 hover:border-green-500 hover:bg-green-50 transition">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-medium text-gray-800">Postpayé</h3>
                        <p class="text-sm text-gray-500">Paiement à la fin de la session. Débit automatique.</p>
                    </div>
                </div>
            </a>
            @else
            <div class="border-2 border-gray-100 rounded-lg p-4 bg-gray-50">
                <div class="flex items-center gap-4 opacity-50">
                    <div class="w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-medium text-gray-800">Postpayé</h3>
                        <p class="text-sm text-gray-400">Non disponible pour cette borne</p>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <a href="{{ route('home') }}" class="block text-center text-gray-600 hover:text-gray-800">
            Retour à l'accueil
        </a>
    </div>
</div>
@endsection
