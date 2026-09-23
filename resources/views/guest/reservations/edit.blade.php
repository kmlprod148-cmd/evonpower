@extends('layouts.app')

@section('title', 'Modifier la Réservation')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- En-tête -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">✏️ Modifier la Réservation #{{ $id }}</h1>
                    <p class="text-gray-600">Modifiez les détails de votre réservation</p>
                </div>
                <div class="flex space-x-2">
                    <a href="{{ route('guest.reservations.show', $id) }}" 
                       class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        👁️ Voir
                    </a>
                    <a href="{{ route('guest.reservations.index') }}" 
                       class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        ← Retour
                    </a>
                </div>
            </div>
        </div>

        <!-- Messages d'erreur -->
        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Formulaire de modification -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <form action="{{ route('guest.reservations.update', $id) }}" method="POST" id="edit-reservation-form">
                @csrf
                @method('PUT')
                
                <!-- Sélection du point de charge -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">🔌 Point de Charge</label>
                    <select name="charging_point_id" id="charging_point_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                        <option value="1" selected>CP-001 - Station Paris Centre (22kW)</option>
                        <option value="2">CP-002 - Station Lyon Centre (11kW)</option>
                    </select>
                </div>

                <!-- Date et heure -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">📅 Date de début</label>
                        <input type="date" name="start_date" id="start_date" value="2024-01-15" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">🕐 Heure de début</label>
                        <input type="time" name="start_time" id="start_time" value="14:30" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    </div>
                </div>

                <!-- Durée et énergie -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">⏱️ Durée estimée (minutes)</label>
                        <input type="number" name="duration_minutes" id="duration_minutes" min="30" max="480" value="150" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                        <p class="text-sm text-gray-500 mt-1">Entre 30 minutes et 8 heures</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">⚡ Énergie estimée (kWh)</label>
                        <input type="number" name="energy_kwh" id="energy_kwh" min="5" max="100" step="0.1" value="25" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                        <p class="text-sm text-gray-500 mt-1">Entre 5 et 100 kWh</p>
                    </div>
                </div>

                <!-- Informations de contact -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">📞 Informations de Contact</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">📧 Email</label>
                            <input type="email" name="guest_email" id="guest_email" value="client.premium@example.com" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">📱 Téléphone</label>
                            <input type="tel" name="guest_phone" id="guest_phone" value="+33 6 12 34 56 78" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                    </div>
                </div>

                <!-- Type de paiement -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">💳 Type de paiement</label>
                    <select name="payment_type" id="payment_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                        <option value="card" selected>Carte bancaire</option>
                        <option value="wallet">Portefeuille électronique</option>
                        <option value="subscription">Abonnement</option>
                    </select>
                </div>

                <!-- Notes -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">📝 Notes (optionnel)</label>
                    <textarea name="notes" id="notes" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Ajoutez des informations supplémentaires...">Réservation modifiée - besoin d'une charge rapide</textarea>
                </div>

                <!-- Estimation du coût -->
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">💰 Estimation du Coût</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Coût énergie</p>
                            <p class="text-lg font-semibold text-gray-900" id="energy-cost">8.75€</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Frais d'activation</p>
                            <p class="text-lg font-semibold text-gray-900" id="activation-fee">1.00€</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Total estimé</p>
                            <p class="text-xl font-bold text-blue-600" id="total-cost">11.70€</p>
                        </div>
                    </div>
                </div>

                <!-- Avertissement de modification -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">⚠️ Attention</h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <p>La modification de votre réservation peut affecter la disponibilité et le coût. Vérifiez les nouvelles informations avant de confirmer.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Boutons d'action -->
                <div class="flex flex-wrap gap-4">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                        ✅ Sauvegarder les Modifications
                    </button>
                    <a href="{{ route('guest.reservations.show', $id) }}" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                        ❌ Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Tarifs par défaut
const pricing = {
    energyRate: 0.35, // €/kWh
    activationFee: 1.00 // €
};

function calculateCost() {
    const energy = parseFloat(document.getElementById('energy_kwh').value) || 0;
    const energyCost = energy * pricing.energyRate;
    const totalCost = energyCost + pricing.activationFee;

    document.getElementById('energy-cost').textContent = energyCost.toFixed(2) + '€';
    document.getElementById('total-cost').textContent = totalCost.toFixed(2) + '€';
}

function updateEndTime() {
    const startDate = document.getElementById('start_date').value;
    const startTime = document.getElementById('start_time').value;
    const duration = parseInt(document.getElementById('duration_minutes').value) || 0;

    if (startDate && startTime && duration) {
        const start = new Date(startDate + 'T' + startTime);
        const end = new Date(start.getTime() + duration * 60000);
        
        // Afficher l'heure de fin estimée
        const endTimeElement = document.getElementById('end_time');
        if (!endTimeElement) {
            const endTimeDiv = document.createElement('div');
            endTimeDiv.id = 'end_time';
            endTimeDiv.className = 'mt-2 p-2 bg-blue-50 rounded text-sm text-blue-800';
            document.getElementById('start_time').parentNode.appendChild(endTimeDiv);
        }
        document.getElementById('end_time').textContent = `Fin estimée : ${end.toLocaleDateString('fr-FR')} à ${end.toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit'})`;
    }
}

function validateForm() {
    const form = document.getElementById('edit-reservation-form');
    const startDate = new Date(document.getElementById('start_date').value);
    const now = new Date();
    
    if (startDate < now) {
        alert('La date de début ne peut pas être dans le passé');
        return false;
    }
    
    return true;
}

// Événements
document.getElementById('energy_kwh').addEventListener('input', calculateCost);
document.getElementById('duration_minutes').addEventListener('input', updateEndTime);
document.getElementById('start_date').addEventListener('change', updateEndTime);
document.getElementById('start_time').addEventListener('change', updateEndTime);

document.getElementById('edit-reservation-form').addEventListener('submit', function(e) {
    if (!validateForm()) {
        e.preventDefault();
    }
});

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    // Définir la date minimale à aujourd'hui
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('start_date').setAttribute('min', today);
    
    // Calculer le coût initial
    calculateCost();
    updateEndTime();
});
</script>
@endsection
