@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
            Intégration SteVe OCPP
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
            Connecter vos bornes de charge au serveur SteVe pour la gestion OCPP
        </p>
    </div>

    <!-- Server Status Card -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Server Status -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Statut du serveur</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2" id="serverStatus">
                        Vérification...
                    </p>
                </div>
                <div id="serverStatusIndicator" class="w-12 h-12 rounded-full bg-gray-300 flex items-center justify-center">
                    <svg class="w-6 h-6 text-gray-600 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Connected Points -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Bornes connectées</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2" id="connectedPoints">
                        0
                    </p>
                </div>
                <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
        </div>

        <!-- Total Points -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Total des bornes</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2" id="totalPoints">
                        0
                    </p>
                </div>
                <svg class="w-12 h-12 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Configuration Section -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Configuration SteVe</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Server URL -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    URL du serveur SteVe
                </label>
                <input 
                    type="text" 
                    id="steveServerUrl"
                    value="ws://158.69.27.239:8080/steve/websocket/CentralSystemService/"
                    readonly
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                >
            </div>

            <!-- API URL -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    URL de l'API SteVe
                </label>
                <input 
                    type="text" 
                    id="steveApiUrl"
                    value="http://158.69.27.239:8080"
                    readonly
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                >
            </div>
        </div>

        <!-- Test Connection Button -->
        <div class="mt-6">
            <button 
                onclick="testSteveConnection()"
                class="px-6 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors font-medium"
            >
                Tester la connexion
            </button>
        </div>
    </div>

    <!-- Charging Points List -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Bornes de charge</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">Nom</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">Charge Box ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">Statut</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">Dernière connexion</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700" id="chargingPointsList">
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            Chargement des bornes...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- SteVe Connection Modal -->
@include('components.steve-connection-modal')

<script src="{{ asset('js/steve-connection.js') }}"></script>

<script>
// Load charging points on page load
document.addEventListener('DOMContentLoaded', () => {
    loadChargingPoints();
    checkServerStatus();
    loadConnectionStatistics();
});

// Load charging points
async function loadChargingPoints() {
    try {
        const response = await fetch('/api/v1/charging-points/');
        const data = await response.json();
        
        if (data.success && data.data.data) {
            displayChargingPoints(data.data.data);
        }
    } catch (error) {
        console.error('Error loading charging points:', error);
    }
}

// Display charging points
function displayChargingPoints(points) {
    const tbody = document.getElementById('chargingPointsList');
    
    if (points.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Aucune borne trouvée</td></tr>';
        return;
    }

    tbody.innerHTML = points.map(point => `
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">${point.name}</td>
            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">${point.charge_box_id || 'N/A'}</td>
            <td class="px-6 py-4 text-sm">
                <span class="px-3 py-1 rounded-full text-xs font-medium ${getStatusClass(point.status)}">
                    ${getStatusLabel(point.status)}
                </span>
            </td>
            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                ${point.last_connection_attempt ? new Date(point.last_connection_attempt).toLocaleString() : 'Jamais'}
            </td>
            <td class="px-6 py-4 text-sm space-x-2">
                <button 
                    onclick="openSteveConnectionModal(${point.id}, '${point.charge_box_id || ''}')"
                    class="px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded transition-colors text-xs"
                >
                    Connecter
                </button>
            </td>
        </tr>
    `).join('');
}

// Get status class
function getStatusClass(status) {
    const classes = {
        'online': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        'offline': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        'connecting': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        'maintenance': 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400'
    };
    return classes[status] || classes['offline'];
}

// Get status label
function getStatusLabel(status) {
    const labels = {
        'online': 'En ligne',
        'offline': 'Hors ligne',
        'connecting': 'Connexion...',
        'maintenance': 'Maintenance'
    };
    return labels[status] || status;
}

// Check server status
async function checkServerStatus() {
    try {
        const response = await fetch('/api/v1/charging-points/1/steve-status');
        const data = await response.json();
        
        if (data.success) {
            const serverStatus = data.data.server_status;
            const isOnline = serverStatus.overall_status === 'success';
            
            document.getElementById('serverStatus').textContent = isOnline ? 'En ligne' : 'Hors ligne';
            
            const indicator = document.getElementById('serverStatusIndicator');
            indicator.className = `w-12 h-12 rounded-full flex items-center justify-center ${isOnline ? 'bg-green-100' : 'bg-red-100'}`;
            indicator.innerHTML = isOnline 
                ? '<svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>'
                : '<svg class="w-6 h-6 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>';
        }
    } catch (error) {
        console.error('Error checking server status:', error);
    }
}

// Load connection statistics
async function loadConnectionStatistics() {
    try {
        const response = await fetch('/api/v1/charging-points/');
        const data = await response.json();
        
        if (data.success && data.data.data) {
            const points = data.data.data;
            const connected = points.filter(p => p.status === 'online').length;
            
            document.getElementById('connectedPoints').textContent = connected;
            document.getElementById('totalPoints').textContent = points.length;
        }
    } catch (error) {
        console.error('Error loading statistics:', error);
    }
}

// Test SteVe connection
async function testSteveConnection() {
    const button = event.target;
    button.disabled = true;
    button.textContent = 'Test en cours...';
    
    try {
        const response = await fetch('/api/v1/charging-points/1/steve-status');
        const data = await response.json();
        
        if (data.success) {
            alert('Connexion au serveur SteVe réussie!');
        } else {
            alert('Erreur de connexion au serveur SteVe');
        }
    } catch (error) {
        alert('Erreur: ' + error.message);
    } finally {
        button.disabled = false;
        button.textContent = 'Tester la connexion';
    }
}
</script>
@endsection

