@extends('layouts.app')

@section('title', 'Détails de la Réservation')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- En-tête -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">📋 Réservation #{{ $id }}</h1>
                    <p class="text-gray-600">Détails de votre réservation</p>
                </div>
                <div class="flex space-x-2">
                    <a href="{{ route('guest.reservations.edit', $id) }}" 
                       class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        ✏️ Modifier
                    </a>
                    <a href="{{ route('guest.reservations.index') }}" 
                       class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        ← Retour
                    </a>
                </div>
            </div>
        </div>

        <!-- Données de démonstration -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Informations principales -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">📊 Informations Générales</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm font-medium text-gray-600">ID Réservation</p>
                            <p class="text-lg font-semibold text-gray-900">RES-{{ $id }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Statut</p>
                            <span class="px-3 py-1 text-sm font-medium rounded-full bg-green-100 text-green-800">
                                Confirmée
                            </span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Point de Charge</p>
                            <p class="text-lg font-semibold text-gray-900">CP-001 - Station Paris Centre</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Puissance</p>
                            <p class="text-lg font-semibold text-gray-900">22 kW</p>
                        </div>
                    </div>
                </div>

                <!-- Planning -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">📅 Planning</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Date de début</p>
                            <p class="text-lg font-semibold text-gray-900">15 Janvier 2024</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Heure de début</p>
                            <p class="text-lg font-semibold text-gray-900">14:30</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Date de fin</p>
                            <p class="text-lg font-semibold text-gray-900">15 Janvier 2024</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Heure de fin</p>
                            <p class="text-lg font-semibold text-gray-900">17:00</p>
                        </div>
                    </div>
                </div>

                <!-- Détails techniques -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">⚡ Détails Techniques</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Durée estimée</p>
                            <p class="text-lg font-semibold text-gray-900">2h 30min</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Énergie estimée</p>
                            <p class="text-lg font-semibold text-gray-900">25 kWh</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Type de connecteur</p>
                            <p class="text-lg font-semibold text-gray-900">Type 2</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Protocole</p>
                            <p class="text-lg font-semibold text-gray-900">OCPP 1.6</p>
                        </div>
                    </div>
                </div>

                <!-- Informations de contact -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">📞 Contact</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Email</p>
                            <p class="text-lg font-semibold text-gray-900">client.premium@example.com</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Téléphone</p>
                            <p class="text-lg font-semibold text-gray-900">+33 6 12 34 56 78</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panneau latéral -->
            <div class="space-y-6">
                <!-- Coût -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">💰 Coût</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Énergie (25 kWh)</span>
                            <span class="font-semibold">8.75€</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Frais d'activation</span>
                            <span class="font-semibold">1.00€</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">TVA (20%)</span>
                            <span class="font-semibold">1.95€</span>
                        </div>
                        <hr>
                        <div class="flex justify-between text-lg font-bold">
                            <span>Total</span>
                            <span class="text-blue-600">11.70€</span>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">🎯 Actions</h3>
                    <div class="space-y-3">
                        <button class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            📱 Recevoir SMS de rappel
                        </button>
                        <button class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            📧 Envoyer par email
                        </button>
                        <button class="w-full bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            📄 Télécharger PDF
                        </button>
                        <button onclick="cancelReservation()" class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                            ❌ Annuler
                        </button>
                    </div>
                </div>

                <!-- QR Code -->
                <div class="bg-white rounded-lg shadow-md p-6 text-center">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">📱 QR Code</h3>
                    <div class="bg-gray-100 rounded-lg p-4 mb-4">
                        <div class="w-32 h-32 mx-auto bg-gray-300 rounded flex items-center justify-center">
                            <span class="text-gray-500 text-xs">QR Code</span>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600">Scannez ce code pour accéder rapidement à votre réservation</p>
                </div>

                <!-- Statut en temps réel -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">🔄 Statut</h3>
                    <div class="flex items-center space-x-3">
                        <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                        <span class="text-sm font-medium text-gray-900">Réservation active</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Dernière mise à jour : il y a 2 minutes</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function cancelReservation() {
    if (confirm('Êtes-vous sûr de vouloir annuler cette réservation ? Cette action est irréversible.')) {
        // Logique d'annulation
        alert('Réservation annulée avec succès');
        window.location.href = "{{ route('guest.reservations.index') }}";
    }
}
</script>
@endsection
