@extends('layouts.app')

@section('title', 'Paiement Annulé')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-xl mx-auto text-center">
        <!-- Icône d'annulation -->
        <div class="mb-6">
            <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto">
                <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </div>
        </div>

        <h1 class="text-2xl font-bold text-gray-800 mb-2">Paiement annulé</h1>
        <p class="text-gray-600 mb-8">Le paiement a été annulé. Aucune somme n'a été débitée.</p>

        @if($reservationId)
        <div class="bg-white rounded-lg shadow-md p-4 mb-6">
            <p class="text-sm text-gray-500">Numéro de réservation: <span class="font-medium">#{{ $reservationId }}</span></p>
        </div>
        @endif

        <!-- Suggestions -->
        <div class="bg-gray-50 rounded-lg p-4 mb-6 text-left">
            <h3 class="font-medium text-gray-800 mb-2">Que souhaitez-vous faire ?</h3>
            <ul class="text-sm text-gray-600 space-y-2">
                <li>• <a href="{{ route('home') }}" class="text-blue-600 hover:underline">Retourner à l'accueil</a></li>
                <li>• <a href="#" onclick="window.history.back()" class="text-blue-600 hover:underline">Réessayer le paiement</a></li>
                <li>• <a href="{{ route('contact') }}" class="text-blue-600 hover:underline">Contacter le support</a></li>
            </ul>
        </div>

        <a href="{{ route('home') }}" 
           class="inline-block bg-blue-600 text-white py-3 px-6 rounded-lg font-medium hover:bg-blue-700 transition">
            Retour à l'accueil
        </a>
    </div>
</div>
@endsection
