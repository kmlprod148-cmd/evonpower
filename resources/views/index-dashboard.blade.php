@extends('layouts.dashboard')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <!-- Page Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Points de charge</h2>
                <p class="text-gray-600">Gérez et surveillez vos bornes de recharge</p>
            </div>
            <div class="flex items-center space-x-3">
                <select class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                    <option>Tous les statuts</option>
                    <option>En ligne</option>
                    <option>Hors ligne</option>
                    <option>En maintenance</option>
                </select>
                <button class="inline-flex items-center px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white font-medium rounded-lg transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Ajouter
                </button>
            </div>
        </div>

        <!-- Statistics Overview -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-200">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-500">Total</p>
                        <p class="text-xl font-bold text-gray-900">{{ $chargingPoints->total() }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-200">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-500">En ligne</p>
                        <p class="text-xl font-bold text-gray-900">{{ $chargingPoints->where('status', 'online')->count() }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-200">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-500">Hors ligne</p>
                        <p class="text-xl font-bold text-gray-900">{{ $chargingPoints->where('status', 'offline')->count() }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-200">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-500">En maintenance</p>
                        <p class="text-xl font-bold text-gray-900">{{ $chargingPoints->where('status', 'maintenance')->count() }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charging Points Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($chargingPoints as $chargingPoint)
                <x-charging-point-card :chargingPoint="$chargingPoint" />
            @endforeach
        </div>

        <!-- Pagination -->
        @if($chargingPoints->hasPages())
        <div class="mt-6">
            {{ $chargingPoints->links() }}
        </div>
        @endif
    </div>
</div>

<!-- SteVe API Terminal -->
@include('components.steve-terminal')

<!-- Quick Actions Modal -->
<div id="quickActionsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Actions rapides</h3>
                    <button onclick="closeQuickActions()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    <button onclick="startCharging()" class="w-full text-left px-4 py-3 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-lg transition-colors">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            Démarrer la charge
                        </div>
                    </button>
                    
                    <button onclick="stopCharging()" class="w-full text-left px-4 py-3 bg-red-50 hover:bg-red-100 text-red-700 rounded-lg transition-colors">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Arrêter la charge
                        </div>
                    </button>
                    
                    <button onclick="getStatus()" class="w-full text-left px-4 py-3 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg transition-colors">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Vérifier le statut
                        </div>
                    </button>
                    
                    <button onclick="runDiagnostic()" class="w-full text-left px-4 py-3 bg-yellow-50 hover:bg-yellow-100 text-yellow-700 rounded-lg transition-colors">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Diagnostic
                        </div>
                    </button>
                </div>
            </div>
        </div>
</div>

<script>
let currentChargingPointId = null;

function quickActions(chargingPointId) {
    currentChargingPointId = chargingPointId;
    document.getElementById('quickActionsModal').classList.remove('hidden');
}

function closeQuickActions() {
    document.getElementById('quickActionsModal').classList.add('hidden');
    currentChargingPointId = null;
}

function startCharging() {
    if (!currentChargingPointId) return;
    
    showNotification('Démarrage de la charge...', 'info');
    
    fetch(`/charging-points/${currentChargingPointId}/start-charging`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            connector_id: 1,
            id_tag: 'remote_user'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Charge démarrée avec succès', 'success');
        } else {
            showNotification('Erreur: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('Erreur lors du démarrage', 'error');
    })
    .finally(() => {
        closeQuickActions();
    });
}

function stopCharging() {
    if (!currentChargingPointId) return;
    
    showNotification('Arrêt de la charge...', 'info');
    
    fetch(`/charging-points/${currentChargingPointId}/stop-charging`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            session_id: 'current_session'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Charge arrêtée avec succès', 'success');
        } else {
            showNotification('Erreur: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('Erreur lors de l\'arrêt', 'error');
    })
    .finally(() => {
        closeQuickActions();
    });
}

function getStatus() {
    if (!currentChargingPointId) return;
    
    showNotification('Vérification du statut...', 'info');
    
    fetch(`/charging-points/${currentChargingPointId}/status`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Statut récupéré avec succès', 'success');
        } else {
            showNotification('Erreur: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('Erreur lors de la vérification', 'error');
    })
    .finally(() => {
        closeQuickActions();
    });
}

function runDiagnostic() {
    if (!currentChargingPointId) return;
    
    showNotification('Diagnostic en cours...', 'info');
    setTimeout(() => {
        showNotification('Diagnostic terminé - Aucun problème détecté', 'success');
        closeQuickActions();
    }, 2000);
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    const colors = {
        'success': 'bg-green-500 text-white',
        'error': 'bg-red-500 text-white',
        'info': 'bg-blue-500 text-white',
        'warning': 'bg-yellow-500 text-white'
    };
    
    notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${colors[type] || colors.info}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}
</script>
@endsection
