@extends('layouts.app')

@section('title', 'Réservations Invités')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-7xl mx-auto">
        <!-- En-tête -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">📅 Réservations Invités</h1>
            <p class="text-gray-600">Gérez vos réservations de points de charge sans compte</p>
        </div>

        <!-- Messages de succès/erreur -->
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                {{ session('error') }}
            </div>
        @endif

        <!-- Actions rapides -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="flex flex-wrap gap-4">
                <a href="{{ route('guest.reservations.create') }}" 
                   class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                    ➕ Nouvelle Réservation
                </a>
                <button onclick="refreshReservations()" 
                        class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                    🔄 Actualiser
                </button>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 rounded-full">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total</p>
                        <p class="text-2xl font-semibold text-gray-900" id="total-reservations">-</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-full">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Confirmées</p>
                        <p class="text-2xl font-semibold text-gray-900" id="confirmed-reservations">-</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-yellow-100 rounded-full">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">En Attente</p>
                        <p class="text-2xl font-semibold text-gray-900" id="pending-reservations">-</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-red-100 rounded-full">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Annulées</p>
                        <p class="text-2xl font-semibold text-gray-900" id="cancelled-reservations">-</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">🔍 Filtres</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                    <select id="status-filter" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="">Tous les statuts</option>
                        <option value="confirmed">Confirmées</option>
                        <option value="pending">En attente</option>
                        <option value="active">Actives</option>
                        <option value="completed">Terminées</option>
                        <option value="cancelled">Annulées</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date de début</label>
                    <input type="date" id="start-date-filter" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date de fin</label>
                    <input type="date" id="end-date-filter" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div class="flex items-end">
                    <button onclick="applyFilters()" 
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        Appliquer
                    </button>
                </div>
            </div>
        </div>

        <!-- Liste des réservations -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">📋 Liste des Réservations</h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Point de Charge</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date/Heure</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Durée</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Énergie</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Coût</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($reservations as $reservation)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    RES-{{ str_pad($reservation->id, 3, '0', STR_PAD_LEFT) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $reservation->charging_point_name }} - {{ $reservation->station_name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ \Carbon\Carbon::parse($reservation->start_time)->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $reservation->duration_minutes }} min
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $reservation->energy_kwh }} kWh
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ number_format($reservation->amount, 2) }}€
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $reservation->status === 'confirmed' ? 'bg-green-100 text-green-800' : ($reservation->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : ($reservation->status === 'active' ? 'bg-blue-100 text-blue-800' : ($reservation->status === 'completed' ? 'bg-gray-100 text-gray-800' : 'bg-red-100 text-red-800'))) }}">
                                        {{ ucfirst($reservation->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="{{ route('guest.reservations.show', $reservation->id) }}" 
                                           class="text-blue-600 hover:text-blue-900">Voir</a>
                                        @if($reservation->status !== 'completed' && $reservation->status !== 'cancelled')
                                            <a href="{{ route('guest.reservations.edit', $reservation->id) }}" 
                                               class="text-green-600 hover:text-green-900">Modifier</a>
                                        @endif
                                        @if($reservation->status !== 'completed' && $reservation->status !== 'cancelled')
                                            <form method="POST" action="{{ route('guest.reservations.destroy', $reservation->id) }}" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" onclick="return confirm('Êtes-vous sûr de vouloir annuler cette réservation ?')" 
                                                        class="text-red-600 hover:text-red-900">Annuler</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                        </svg>
                                        <p class="text-lg font-medium">Aucune réservation trouvée</p>
                                        <p class="text-sm">Créez votre première réservation pour commencer</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div class="mt-8 flex justify-center">
            <nav class="flex items-center space-x-2">
                <button class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    Précédent
                </button>
                <span class="px-3 py-2 text-sm font-medium text-gray-700 bg-blue-50 border border-blue-300 rounded-md">
                    1
                </span>
                <button class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    Suivant
                </button>
            </nav>
        </div>
    </div>
</div>

<script>
// Données de démonstration
const demoReservations = [
    {
        id: 'RES-001',
        chargingPoint: 'CP-001 - Station Paris Centre',
        startTime: '2024-01-15 14:30',
        duration: '2h 30min',
        energy: '25 kWh',
        cost: '12.50€',
        status: 'confirmed',
        statusText: 'Confirmée'
    },
    {
        id: 'RES-002',
        chargingPoint: 'CP-002 - Station Lyon Centre',
        startTime: '2024-01-16 09:15',
        duration: '1h 45min',
        energy: '18 kWh',
        cost: '8.75€',
        status: 'active',
        statusText: 'Active'
    },
    {
        id: 'RES-003',
        chargingPoint: 'CP-001 - Station Paris Centre',
        startTime: '2024-01-14 16:00',
        duration: '3h 00min',
        energy: '35 kWh',
        cost: '17.25€',
        status: 'completed',
        statusText: 'Terminée'
    }
];

function loadReservations() {
    const tbody = document.getElementById('reservations-table-body');
    
    if (demoReservations.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                    <div class="flex flex-col items-center">
                        <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        <p class="text-lg font-medium">Aucune réservation trouvée</p>
                        <p class="text-sm">Créez votre première réservation pour commencer</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = demoReservations.map(reservation => `
        <tr class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                ${reservation.id}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${reservation.chargingPoint}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${reservation.startTime}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${reservation.duration}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${reservation.energy}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                ${reservation.cost}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 py-1 text-xs font-medium rounded-full ${getStatusClass(reservation.status)}">
                    ${reservation.statusText}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex space-x-2">
                    <a href="{{ route('guest.reservations.show', '') }}/${reservation.id}" 
                       class="text-blue-600 hover:text-blue-900">Voir</a>
                    <a href="{{ route('guest.reservations.edit', '') }}/${reservation.id}" 
                       class="text-green-600 hover:text-green-900">Modifier</a>
                    <button onclick="cancelReservation('${reservation.id}')" 
                            class="text-red-600 hover:text-red-900">Annuler</button>
                </div>
            </td>
        </tr>
    `).join('');
}

function getStatusClass(status) {
    const classes = {
        'confirmed': 'bg-green-100 text-green-800',
        'pending': 'bg-yellow-100 text-yellow-800',
        'active': 'bg-blue-100 text-blue-800',
        'completed': 'bg-gray-100 text-gray-800',
        'cancelled': 'bg-red-100 text-red-800'
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
}

function updateStatistics() {
    const total = demoReservations.length;
    const confirmed = demoReservations.filter(r => r.status === 'confirmed').length;
    const pending = demoReservations.filter(r => r.status === 'pending').length;
    const cancelled = demoReservations.filter(r => r.status === 'cancelled').length;

    document.getElementById('total-reservations').textContent = total;
    document.getElementById('confirmed-reservations').textContent = confirmed;
    document.getElementById('pending-reservations').textContent = pending;
    document.getElementById('cancelled-reservations').textContent = cancelled;
}

function refreshReservations() {
    loadReservations();
    updateStatistics();
}

function applyFilters() {
    // Logique de filtrage à implémenter
    console.log('Filtres appliqués');
}

function cancelReservation(reservationId) {
    if (confirm('Êtes-vous sûr de vouloir annuler cette réservation ?')) {
        // Logique d'annulation à implémenter
        console.log('Réservation annulée:', reservationId);
        refreshReservations();
    }
}

// Charger les données au démarrage
document.addEventListener('DOMContentLoaded', function() {
    loadReservations();
    updateStatistics();
});
</script>
@endsection
