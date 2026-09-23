@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between py-4">
                <div class="flex items-center space-x-4">
                    <a href="{{ route('charging-points.index') }}" 
                       class="inline-flex items-center justify-center w-10 h-10 rounded-lg border border-gray-300 hover:bg-gray-50 text-gray-500 hover:text-gray-700 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ $chargingPoint->name }}</h1>
                        <p class="text-sm text-gray-600">Borne de recharge</p>
                    </div>
                </div>
                
                <!-- Status Badge -->
                <div class="flex items-center space-x-3">
                    <div class="flex items-center space-x-2">
                        <div class="w-3 h-3 rounded-full {{ $chargingPoint->status === 'online' ? 'bg-green-500' : 'bg-red-500' }}"></div>
                        <span class="text-sm font-medium text-gray-700">
                            {{ $chargingPoint->status === 'online' ? 'En ligne' : 'Hors ligne' }}
                        </span>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="flex items-center space-x-2">
                        <a href="{{ route('charging-points.edit', $chargingPoint) }}" 
                           class="inline-flex items-center px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Modifier
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <!-- Messages -->
        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                <p class="font-medium">{{ session('success') }}</p>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                <p class="font-medium">{{ session('error') }}</p>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Information -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Basic Information Card -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Informations générales</h3>
                    </div>
                    <div class="px-6 py-4">
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Nom</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $chargingPoint->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Statut</dt>
                                <dd class="mt-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $chargingPoint->status === 'online' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $chargingPoint->status === 'online' ? 'En ligne' : 'Hors ligne' }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Type</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $chargingPoint->type ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Puissance</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $chargingPoint->power_output ?? 'N/A' }} kW</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Adresse</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $chargingPoint->address ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Ville</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $chargingPoint->city ?? 'N/A' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Technical Details Card -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Détails techniques</h3>
                    </div>
                    <div class="px-6 py-4">
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Numéro de série</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $chargingPoint->serial_number ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Modèle</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $chargingPoint->model ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Fabricant</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $chargingPoint->manufacturer ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Type d'accès</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $chargingPoint->access_type ?? 'N/A' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Business Profiles Card -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Business Profiles</h3>
                    </div>
                    <div class="px-6 py-4">
                        <div id="business-profiles-content">
                            <div class="text-center py-4">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-500 mx-auto"></div>
                                <p class="mt-2 text-sm text-gray-500">Chargement des business profiles...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Quick Actions Card -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Actions rapides</h3>
                    </div>
                    <div class="px-6 py-4 space-y-3">
                        <!-- Connexion par ID -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Connexion par ID</label>
                            <div class="flex space-x-2">
                                <input type="text" id="borneIdInput" 
                                       placeholder="ID Borne (ex: BORNE777)" 
                                       value="{{ $chargingPoint->charge_box_id ?? 'BORNE777' }}"
                                       class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm">
                                <button onclick="connectByIdWithInput()" 
                                        class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg transition-colors text-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <button onclick="testConnectivity()" 
                                class="w-full flex items-center justify-center px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors text-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Test de connectivité
                        </button>

                        <button onclick="getConnectionStatus()" 
                                class="w-full flex items-center justify-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors text-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Statut de connexion
                        </button>
                    </div>
                </div>

                <!-- Connection Status Card -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Statut de connexion</h3>
                    </div>
                    <div class="px-6 py-4">
                        <div class="text-center">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <div class="w-8 h-8 {{ $chargingPoint->status === 'online' ? 'bg-green-500' : 'bg-red-500' }} rounded-full"></div>
                            </div>
                            <p class="text-sm text-gray-500">
                                {{ $chargingPoint->status === 'online' ? 'Connecté' : 'Déconnecté' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SteVe API Terminal -->
@include('components.steve-terminal')

<script>
// Charging Point Actions
const chargingPointId = {{ $chargingPoint->id ?: 1 }};

// Fonction de connexion par ID avec champ d'entrée
function connectByIdWithInput() {
    const borneIdInput = document.getElementById('borneIdInput');
    let borneId = '';
    
    if (borneIdInput && borneIdInput.value.trim()) {
        borneId = borneIdInput.value.trim();
    } else {
        showNotification('Veuillez entrer un ID de borne valide', 'error');
        return;
    }
    
    if (borneId.length < 3) {
        showNotification('L\'ID de borne doit contenir au moins 3 caractères', 'error');
        return;
    }
    
    showTerminalOnAction();
    addTerminalLine(`Connecting to borne: ${borneId}...`, 'info');
    
    const formData = new FormData();
    formData.append('iccid', '12345678901234567890');
    formData.append('imsi', '123456789012345');
    formData.append('meter_type', 'Single_Phase_Energy_Meter');
    formData.append('meter_serial', 'METER_' + chargingPointId);
    formData.append('borne_id', borneId);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
    
    fetch(`/charging-points/connect-by-id/${chargingPointId}`, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error('Réponse non-JSON reçue du serveur: ' + text.substring(0, 200));
                }
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            addTerminalLine(`Connection successful to ${borneId}: ${data.message}`, 'success');
            showNotification(`Connexion réussie à ${borneId}: ` + data.message, 'success');
        } else {
            addTerminalLine(`Connection failed to ${borneId}: ${data.message}`, 'error');
            showNotification(`Erreur de connexion à ${borneId}: ` + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        addTerminalLine(`Connection error to ${borneId}: ${error.message}`, 'error');
        showNotification(`Erreur lors de la connexion à ${borneId}: ` + error.message, 'error');
    });
}

function testConnectivity() {
    showTerminalOnAction();
    addTerminalLine(`Testing SteVe connectivity for charging point ${chargingPointId}...`, 'info');
    
    fetch(`/charging-points/${chargingPointId}/test-connectivity`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error('Réponse non-JSON reçue du serveur: ' + text.substring(0, 200));
                }
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            addTerminalLine(`Connectivity test successful: ${data.message}`, 'success');
            showNotification('Test de connectivité réussi: ' + data.message, 'success');
        } else {
            addTerminalLine(`Connectivity test failed: ${data.message}`, 'error');
            showNotification('Test de connectivité échoué: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        addTerminalLine(`Connectivity test error: ${error.message}`, 'error');
        showNotification('Erreur lors du test de connectivité: ' + error.message, 'error');
    });
}

function getConnectionStatus() {
    showTerminalOnAction();
    addTerminalLine(`Getting connection status for charging point ${chargingPointId}...`, 'info');
    
    fetch(`/charging-points/${chargingPointId}/connection-status`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            addTerminalLine(`Connection status: ${JSON.stringify(data.data)}`, 'info');
            showNotification('Statut de connexion récupéré', 'success');
        } else {
            addTerminalLine(`Status error: ${data.message}`, 'error');
            showNotification('Erreur lors de la récupération du statut', 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        addTerminalLine(`Status error: ${error.message}`, 'error');
        showNotification('Erreur lors de la récupération du statut', 'error');
    });
}

// Fonctions du terminal SteVe
function showTerminalOnAction() {
    const terminal = document.getElementById('steve-terminal');
    if (terminal) {
        terminal.classList.remove('hidden');
        terminal.scrollTop = terminal.scrollHeight;
    }
}

function addTerminalLine(message, type = 'info') {
    const terminal = document.getElementById('steve-terminal');
    if (terminal) {
        const terminalContent = terminal.querySelector('.terminal-content');
        if (terminalContent) {
            const line = document.createElement('div');
            line.className = `terminal-line ${type}`;
            line.textContent = `[${new Date().toLocaleTimeString()}] ${message}`;
            terminalContent.appendChild(line);
            terminalContent.scrollTop = terminalContent.scrollHeight;
        }
    }
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    const colors = {
        'success': 'bg-green-500 text-white',
        'error': 'bg-red-500 text-white',
        'info': 'bg-blue-500 text-white',
        'warning': 'bg-yellow-500 text-white'
    };
    
    notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 ${colors[type] || colors.info}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

// Charger les business profiles
document.addEventListener('DOMContentLoaded', function() {
    loadBusinessProfiles();
});

function loadBusinessProfiles() {
    fetch(`/charging-points/{{ $chargingPoint->id ?: 1 }}/business-profiles`)
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('business-profiles-content');
            if (data.success && data.business_profiles) {
                container.innerHTML = `
                    <div class="space-y-3">
                        ${data.business_profiles.map(profile => `
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div>
                                    <h4 class="font-medium text-gray-900">${profile.name}</h4>
                                    <p class="text-sm text-gray-600">${profile.description || 'Aucune description'}</p>
                                </div>
                                <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                                    Appliqué
                                </span>
                            </div>
                        `).join('')}
                    </div>
                `;
            } else {
                container.innerHTML = `
                    <div class="text-center py-4">
                        <p class="text-sm text-gray-500">Aucun business profile appliqué</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement des business profiles:', error);
            const container = document.getElementById('business-profiles-content');
            container.innerHTML = `
                <div class="text-center py-4">
                    <p class="text-sm text-red-500">Erreur lors du chargement des business profiles</p>
                </div>
            `;
        });
}

// Fonction de génération de QR code
function generateQRCode() {
    showNotification('Génération du QR code...', 'info');
    
    // URL de réservation
    const reservationUrl = `${window.location.origin}/charging-points/${chargingPointId}/offer`;
    
    // Créer un modal pour afficher le QR code
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    modal.innerHTML = `
        <div class="bg-white rounded-lg p-6 max-w-sm w-full mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">QR Code de Réservation</h3>
                <button onclick="this.closest('.fixed').remove()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="qrCodeModal" class="flex justify-center mb-4"></div>
            <p class="text-sm text-gray-600 text-center">Scannez ce QR code pour accéder à la page de réservation</p>
            <div class="mt-4 text-center">
                <button onclick="this.closest('.fixed').remove()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Fermer
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Générer le QR code
    try {
        const qr = new QRCode(document.getElementById('qrCodeModal'), {
            text: reservationUrl,
            width: 200,
            height: 200,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.M
        });
        
        showNotification('QR code généré avec succès', 'success');
    } catch (error) {
        showNotification('Erreur lors de la génération du QR code', 'error');
        modal.remove();
    }
}
</script>
@endsection
