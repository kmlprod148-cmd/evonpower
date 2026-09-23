@extends('layouts.app')

@section('title', 'Réservation Confirmée')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-xl mx-auto text-center">
        <!-- Icône de succès -->
        <div class="mb-6">
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto">
                <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
        </div>

        <h1 class="text-2xl font-bold text-gray-800 mb-2">Réservation confirmée !</h1>
        <p class="text-gray-600 mb-8">Votre réservation a été créée avec succès</p>

        <!-- Détails de la réservation -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6 text-left">
            <h2 class="text-lg font-semibold mb-4">Détails de la réservation</h2>
            
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-500">Numéro de réservation</span>
                    <span class="font-medium">#{{ $reservation->id }}</span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-gray-500">Borne</span>
                    <span class="font-medium">{{ $reservation->chargingPoint->name ?? 'N/A' }}</span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-gray-500">Montant payé</span>
                    <span class="font-medium text-green-600">{{ number_format($reservation->prepaid_amount ?? $reservation->amount, 2) }} EUR</span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-gray-500">Statut</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        Confirmée
                    </span>
                </div>
            </div>
        </div>

        <!-- QR Code d'activation -->
        @if($qrCodeUrl)
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Code QR d'activation</h2>
            <p class="text-sm text-gray-500 mb-4">Scannez ce code à la borne pour démarrer votre recharge</p>
            
            <img src="{{ $qrCodeUrl }}" alt="QR Code Activation" class="mx-auto w-48 h-48">
            
            @if($reservation->activation_token)
            <p class="text-xs text-gray-400 mt-2">Token: {{ $reservation->activation_token }}</p>
            @endif
        </div>
        @endif

        <!-- Instructions -->
        <div class="bg-blue-50 rounded-lg p-4 mb-6 text-left">
            <h3 class="font-medium text-blue-800 mb-2">Instructions</h3>
            <ul class="text-sm text-blue-700 space-y-1">
                <li>1. Rendez-vous à la borne indiquer</li>
                <li>2. Scannez le code QR ou entrez le token</li>
                <li>3. Connectez votre véhicule</li>
                <li>4. La recharge démarre automatiquement</li>
                <li>5. Le crédit non utilisé vous sera remboursé</li>
            </ul>
        </div>

        <!-- Actions -->
        <div class="flex gap-4">
            <a href="{{ route('home') }}" 
               class="flex-1 bg-gray-200 text-gray-700 py-3 px-6 rounded-lg font-medium text-center hover:bg-gray-300 transition">
                Retour à l'accueil
            </a>
            
            @auth
            <a href="{{ route('reservations.show', $reservation->id) }}" 
               class="flex-1 bg-blue-600 text-white py-3 px-6 rounded-lg font-medium text-center hover:bg-blue-700 transition">
                Voir ma réservation
            </a>
            @endauth
        </div>
    </div>
</div>

@push('scripts')
<script>
// Confirmer l'envoi des notifications
document.addEventListener('DOMContentLoaded', function() {
    // Log pour confirmer l'affichage
    console.log('Réservation confirmée: {{ $reservation->id }}');
    
    // Si l'utilisateur a demandé un compte, afficher un message
    @if($reservation->user_id && $reservation->guest_email)
    // Envoyer un email de confirmation depuis le serveur
    @endif
});
</script>
@endpush
@endsection
