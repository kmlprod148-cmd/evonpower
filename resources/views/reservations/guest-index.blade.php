@extends('layouts.app')

@section('title', 'Accès aux Réservations')

@section('content')
<div class="min-h-screen bg-gray-50 py-4 sm:py-8">
    <div class="max-w-3xl mx-auto px-3 sm:px-4 lg:px-8">
        <!-- En-tête -->
        <div class="mb-6 sm:mb-8 text-center">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Accès aux Réservations</h1>
            <p class="mt-1 sm:mt-2 text-sm sm:text-base text-gray-600">
                Consultez vos réservations en utilisant votre email ou numéro de téléphone
            </p>
        </div>

        <!-- Formulaire de recherche -->
        <div class="bg-white shadow-sm rounded-lg p-6 sm:p-8">
            <form id="guestSearchForm" class="space-y-6">
                @csrf
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Adresse email
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="votre.email@example.com">
                </div>

                <div class="text-center">
                    <span class="text-sm text-gray-500">ou</span>
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                        Numéro de téléphone
                    </label>
                    <input type="tel" 
                           id="phone" 
                           name="phone" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="+212XXXXXXXXX">
                </div>

                <div class="text-center">
                    <button type="submit" 
                            class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg transition duration-200">
                        <i class="fas fa-search mr-2"></i>
                        Rechercher mes réservations
                    </button>
                </div>
            </form>

            <!-- État de chargement -->
            <div id="loading-state" class="hidden mt-6 text-center">
                <div class="inline-flex items-center px-4 py-2 font-semibold leading-6 text-sm shadow rounded-md text-white bg-blue-500 hover:bg-blue-400 transition ease-in-out duration-150 cursor-not-allowed">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Recherche en cours...
                </div>
            </div>

            <!-- Messages d'erreur -->
            <div id="error-message" class="hidden mt-4 p-4 bg-red-50 border border-red-200 rounded-md">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p id="error-text" class="text-sm text-red-800"></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Résultats -->
        <div id="results-container" class="hidden mt-6">
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Vos Réservations</h2>
                </div>
                
                <div id="reservations-list" class="divide-y divide-gray-200">
                    <!-- Les réservations seront affichées ici -->
                </div>
                
                <!-- État vide -->
                <div id="empty-results" class="hidden p-6 text-center">
                    <div class="text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Aucune réservation trouvée</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Aucune réservation n'a été trouvée avec les informations fournies.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('guestSearchForm');
    const loadingState = document.getElementById('loading-state');
    const errorMessage = document.getElementById('error-message');
    const errorText = document.getElementById('error-text');
    const resultsContainer = document.getElementById('results-container');
    const reservationsList = document.getElementById('reservations-list');
    const emptyResults = document.getElementById('empty-results');

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const email = document.getElementById('email').value.trim();
        const phone = document.getElementById('phone').value.trim();
        
        if (!email && !phone) {
            showError('Veuillez fournir un email ou un numéro de téléphone.');
            return;
        }

        // Afficher l'état de chargement
        loadingState.classList.remove('hidden');
        errorMessage.classList.add('hidden');
        resultsContainer.classList.add('hidden');

        // Préparer les données
        const formData = new FormData();
        if (email) formData.append('email', email);
        if (phone) formData.append('phone', phone);
        formData.append('_token', '{{ csrf_token() }}');

        // Envoyer la requête
        fetch('{{ route("guest-reservations.search") }}', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            loadingState.classList.add('hidden');
            
            if (data.success) {
                if (data.reservations.length > 0) {
                    displayReservations(data.reservations);
                } else {
                    showEmptyResults();
                }
            } else {
                showError(data.message || 'Une erreur est survenue.');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            loadingState.classList.add('hidden');
            showError('Une erreur est survenue lors de la recherche.');
        });
    });

    function showError(message) {
        errorText.textContent = message;
        errorMessage.classList.remove('hidden');
    }

    function showEmptyResults() {
        resultsContainer.classList.remove('hidden');
        reservationsList.innerHTML = '';
        emptyResults.classList.remove('hidden');
    }

    function displayReservations(reservations) {
        resultsContainer.classList.remove('hidden');
        emptyResults.classList.add('hidden');
        
        reservationsList.innerHTML = '';
        
        reservations.forEach(reservation => {
            const reservationElement = createReservationElement(reservation);
            reservationsList.appendChild(reservationElement);
        });
    }

    function createReservationElement(reservation) {
        const div = document.createElement('div');
        div.className = 'p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between bg-gray-50 rounded-lg shadow-sm mb-4';

        const statusColors = {
            'pending': 'bg-yellow-100 text-yellow-800',
            'pending_confirmation': 'bg-indigo-100 text-indigo-800', // Added pending_confirmation
            'confirmed': 'bg-blue-100 text-blue-800',
            'active': 'bg-green-100 text-green-800',
            'completed': 'bg-gray-100 text-gray-800',
            'canceled': 'bg-red-100 text-red-800' // Changed 'cancelled' to 'canceled' to match enum
        };

        const statusText = {
            'pending': 'En attente',
            'pending_confirmation': 'En attente de confirmation', // Added pending_confirmation
            'confirmed': 'Confirmée',
            'active': 'En cours',
            'completed': 'Terminée',
            'canceled': 'Annulée' // Changed 'cancelled' to 'canceled' to match enum
        };

        div.innerHTML = `
            <div class="flex items-center space-x-4">
                <div class="flex-shrink-0">
                    <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
                        <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-900 mb-1">
                        <span class="mr-2">#${reservation.id}</span>
                        ${reservation.charging_point?.name || 'Borne inconnue'}
                    </h3>
                    <p class="text-sm text-gray-500">${reservation.charging_point?.address || ''}</p>
                    <p class="text-xs text-gray-400 mt-1">
                        <span class="mr-2"><i class="fas fa-clock"></i> ${new Date(reservation.start_time).toLocaleString('fr-FR')}</span>
                        <span class="mr-2"><i class="fas fa-bolt"></i> ${reservation.reservation_value} ${reservation.reservation_type === 'kwh' ? 'kWh' : 'min'}</span>
                    </p>
                </div>
            </div>
            <div class="mt-4 sm:mt-0 flex flex-col items-end space-y-2">
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${statusColors[reservation.status] || 'bg-gray-100 text-gray-800'}">
                    ${statusText[reservation.status] || reservation.status}
                </span>
                <span class="text-sm font-bold text-green-700">
                    ${reservation.estimated_cost} ${reservation.pricing_plan?.currency || 'EUR'}
                </span>
                <a href="/reservations/${reservation.id}" class="inline-block mt-2 px-4 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded shadow transition">
                    Voir détails
                </a>
            </div>
        `;

        return div;
    }
});
</script>
@endpush
@endsection