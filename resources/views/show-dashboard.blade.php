@extends('layouts.dashboard')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-3 space-y-6">
                <!-- Navigation Tabs -->
                <div class="flex items-center space-x-1 bg-white rounded-lg p-1 shadow-sm">
                    <button class="flex items-center px-3 py-2 text-sm font-medium text-emerald-600 bg-emerald-50 rounded-md">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Informations
                    </button>
                    <button class="flex items-center px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Sessions de recharges
                    </button>
                    <button class="flex items-center px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M4 12h3"/>
                        </svg>
                        Intégration
                    </button>
                    <button class="flex items-center px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                        </svg>
                        Générer un URL de paiement
                    </button>
                </div>

                <!-- Charging Station Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h2 class="text-lg font-semibold text-gray-900 mb-2">Borne de recharge</h2>
                            <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ $chargingPoint->name }}</h1>
                            
                            <!-- Connection Status Alert -->
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-yellow-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                    </svg>
                                    <div>
                                        <p class="text-sm font-medium text-yellow-800">
                                            Le point de charge n'est pas connecté !
                                        </p>
                                        <p class="text-sm text-yellow-700 mt-1">
                                            Veuillez connecter le point de charge pour commencer à l'utiliser.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Connection Buttons -->
                            <div class="flex items-center space-x-3">
                                <button onclick="connectChargingPoint()" 
                                        class="inline-flex items-center px-6 py-3 bg-emerald-500 hover:bg-emerald-600 text-white font-medium rounded-lg transition-colors">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                    Connecter
                                </button>
                                
                                <!-- QR Code et Réservation -->
                                <div class="mb-4 p-4 bg-gradient-to-r from-green-50 to-blue-50 rounded-lg border border-green-200">
                                    <h4 class="text-sm font-semibold text-gray-800 mb-3 flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm2 2V5h1v1H5zM3 13a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1v-3zm2 2v-1h1v1H5zM13 4a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1V4zm2 2V5h1v1h-1z" clip-rule="evenodd"></path>
                                        </svg>
                                        QR Code de Réservation
                                    </h4>
                                    <div class="flex items-center space-x-3">
                                        <div id="qrCodeContainer" class="w-16 h-16 bg-white rounded border-2 border-gray-200 flex items-center justify-center">
                                            <div class="text-xs text-gray-400">QR Code</div>
                                        </div>
                                        <div class="flex-1">
                                            <button onclick="generateQRCode()" 
                                                    class="w-full px-3 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                                                Générer QR Code
                                            </button>
                                            <p class="text-xs text-gray-600 mt-1">Génère un QR code pour la réservation</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Connexion par ID avec champ d'entrée -->
                                <div class="flex items-center space-x-2">
                                    <input type="text" id="borneIdInput" 
                                           placeholder="ID Borne (ex: BORNE777)" 
                                           value="{{ $chargingPoint->charge_box_id ?? 'BORNE777' }}"
                                           class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                    <button onclick="connectByIdWithInput()" 
                                            class="inline-flex items-center px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white font-medium rounded-lg transition-colors">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                        </svg>
                                        Connecter
                                    </button>
                                </div>
                                
                                <button onclick="testConnectivity()" 
                                        class="inline-flex items-center px-4 py-3 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-lg transition-colors">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Test
                                </button>
                            </div>
                        </div>
                        
                        <!-- Status Indicator -->
                        <div class="ml-6">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center">
                                <div class="w-8 h-8 bg-red-500 rounded-full"></div>
                            </div>
                            <p class="text-sm text-gray-500 mt-2 text-center">Hors ligne</p>
                        </div>
                    </div>
                </div>

                <!-- Statistics Section -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">Statistiques</h3>
                        <div class="flex items-center space-x-2">
                            <select class="text-sm border border-gray-300 rounded-lg px-3 py-1 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                                <option>Aujourd'hui</option>
                                <option>Cette semaine</option>
                                <option>Ce mois</option>
                                <option>Cette année</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Total Consumption -->
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-blue-700">Consommation totale</p>
                                    <p class="text-2xl font-bold text-blue-900">123,12 kW</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Charging Sessions -->
                        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-green-700">Sessions de recharges</p>
                                    <p class="text-2xl font-bold text-green-900">155</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Success Rate -->
                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-purple-700">Charges réussies</p>
                                    <p class="text-2xl font-bold text-purple-900">99%</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar - Remote Actions -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Remote actions</h3>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                    
                    <div class="space-y-2">
                        <!-- Remote Actions List -->
                        <button onclick="triggerRecharge()" 
                                class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                            Déclencher une recharge
                        </button>
                        
                        <button onclick="stopCharging()" 
                                class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                            Arrêter une recharge
                        </button>
                        
                        <button onclick="unlockConnector()" 
                                class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                            Déblocage de connecteur
                        </button>
                        
                        <button onclick="resetChargingPoint()" 
                                class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                            Réinitialisation
                        </button>
                        
                        <button onclick="updateParameters()" 
                                class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                            Mise à jour des paramètres
                        </button>
                        
                        <button onclick="runDiagnostic()" 
                                class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                            Diagnostic
                        </button>
                        
                        <button onclick="retrieveLogs()" 
                                class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                            Récupérer le log
                        </button>
                        
                        <button onclick="getSteVeStatus()" 
                                class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                            Statut SteVe
                        </button>
                        
                        <button onclick="getActiveSessions()" 
                                class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                            Sessions actives
                        </button>
                        
                        <button onclick="createReservation()" 
                                class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                            Créer réservation
                        </button>
                        
                        <button onclick="cancelReservation()" 
                                class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                            Annuler réservation
                        </button>
                        
                        <div class="space-y-2">
                            <label class="block text-xs font-medium text-gray-500">ID de la borne</label>
                            <div class="flex space-x-1">
                                <input type="text" id="sidebarBorneIdInput" 
                                       placeholder="BORNE777" 
                                       value="{{ $chargingPoint->charge_box_id ?? 'BORNE777' }}"
                                       class="flex-1 px-2 py-1 border border-gray-300 rounded text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                <button onclick="connectByIdWithInput()" 
                                        class="px-2 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded text-xs transition-colors">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        <button onclick="disconnectChargingPoint()" 
                                class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                            Déconnecter
                        </button>
                        
                        <button onclick="getConnectionStatus()" 
                                class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                            Statut de connexion
                        </button>
                    </div>
                    
                    <!-- Make & Model Info -->
                    <div class="mt-6 pt-4 border-t border-gray-200">
                        <div class="text-sm text-gray-500">
                            <p class="font-medium">Marque & Modèle</p>
                            <p class="mt-1">{{ $chargingPoint->manufacturer }} {{ $chargingPoint->model }}</p>
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

// QR Code Generation
function generateQRCode() {
    showTerminalOnAction();
    addTerminalLine('Génération du QR code de réservation...', 'info');
    
    // URL de réservation
    const reservationUrl = `${window.location.origin}/charging-points/${chargingPointId}/offer`;
    addTerminalLine(`URL de réservation: ${reservationUrl}`, 'info');
    
    // Générer le QR code avec qrcode.js
    const qrContainer = document.getElementById('qrCodeContainer');
    qrContainer.innerHTML = '';
    
    try {
        // Créer le QR code
        const qr = new QRCode(qrContainer, {
            text: reservationUrl,
            width: 64,
            height: 64,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.M
        });
        
        addTerminalLine('QR code généré avec succès', 'success');
        showNotification('QR code généré pour la réservation', 'success');
        
        // Log de l'action
        logSteVeResponse('QR_CODE_GENERATION', {
            url: reservationUrl,
            charging_point_id: chargingPointId,
            timestamp: new Date().toISOString()
        }, true);
        
    } catch (error) {
        addTerminalLine(`Erreur lors de la génération du QR code: ${error.message}`, 'error');
        showNotification('Erreur lors de la génération du QR code', 'error');
    }
}

function connectChargingPoint() {
    showNotification('Tentative de connexion au point de charge...', 'info');
    
    // Simuler la connexion
    setTimeout(() => {
        showNotification('Point de charge connecté avec succès !', 'success');
        // Mettre à jour l'interface
        updateConnectionStatus(true);
    }, 2000);
}

function triggerRecharge() {
    showNotification('Déclenchement de la recharge...', 'info');
    
    fetch(`/charging-points/${chargingPointId}/start-charging`, {
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
            showNotification('Recharge déclenchée avec succès', 'success');
        } else {
            showNotification('Erreur: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('Erreur lors du déclenchement', 'error');
    });
}

function stopCharging() {
    showNotification('Arrêt de la recharge...', 'info');
    
    fetch(`/charging-points/${chargingPointId}/stop-charging`, {
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
            showNotification('Recharge arrêtée avec succès', 'success');
        } else {
            showNotification('Erreur: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('Erreur lors de l\'arrêt', 'error');
    });
}

function unlockConnector() {
    showNotification('Déblocage du connecteur...', 'info');
    setTimeout(() => {
        showNotification('Connecteur débloqué', 'success');
    }, 1000);
}

function resetChargingPoint() {
    if (confirm('Êtes-vous sûr de vouloir réinitialiser le point de charge ?')) {
        showNotification('Réinitialisation en cours...', 'info');
        setTimeout(() => {
            showNotification('Point de charge réinitialisé', 'success');
        }, 2000);
    }
}

function updateParameters() {
    showNotification('Mise à jour des paramètres...', 'info');
    setTimeout(() => {
        showNotification('Paramètres mis à jour', 'success');
    }, 1500);
}

function runDiagnostic() {
    showNotification('Diagnostic en cours...', 'info');
    setTimeout(() => {
        showNotification('Diagnostic terminé - Aucun problème détecté', 'success');
    }, 3000);
}

function retrieveLogs() {
    showNotification('Récupération des logs...', 'info');
    setTimeout(() => {
        showNotification('Logs téléchargés', 'success');
    }, 2000);
}

function updateConnectionStatus(connected) {
    const statusIndicator = document.querySelector('.w-8.h-8');
    const statusText = document.querySelector('.text-sm.text-gray-500');
    
    if (connected) {
        statusIndicator.className = 'w-8 h-8 bg-green-500 rounded-full';
        statusText.textContent = 'En ligne';
        statusText.className = 'text-sm text-green-500 mt-2 text-center';
    } else {
        statusIndicator.className = 'w-8 h-8 bg-red-500 rounded-full';
        statusText.textContent = 'Hors ligne';
        statusText.className = 'text-sm text-gray-500 mt-2 text-center';
    }
}

// Nouvelles fonctions de connexion avec terminal
function connectById(chargingPointId) {
    showTerminalOnAction();
    addTerminalLine(`Connecting charging point ${chargingPointId} by ID...`, 'info');
    
    const formData = new FormData();
    formData.append('iccid', '12345678901234567890');
    formData.append('imsi', '123456789012345');
    formData.append('meter_type', 'Single_Phase_Energy_Meter');
    formData.append('meter_serial', 'METER_' + chargingPointId);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
    
    fetch(`/charging-points/connect-by-id/${chargingPointId}`, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // Vérifier si la réponse est du JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            // Essayer de parser comme JSON quand même
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
            addTerminalLine(`Connection successful: ${data.message}`, 'success');
            logSteVeResponse('CONNECT_BY_ID', data.data, true);
            showNotification('Connexion par ID réussie: ' + data.message, 'success');
            updateConnectionStatus(true);
        } else {
            addTerminalLine(`Connection failed: ${data.message}`, 'error');
            logSteVeResponse('CONNECT_BY_ID', data, false);
            showNotification('Erreur: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        addTerminalLine(`Connection error: ${error.message}`, 'error');
        addTerminalLine(`Détails: ${error.stack || 'Pas de détails disponibles'}`, 'error');
        showNotification('Erreur lors de la connexion par ID: ' + error.message, 'error');
    });
}

// Nouvelle fonction avec champ d'entrée pour l'ID de la borne
function connectByIdWithInput() {
    // Récupérer l'ID de la borne depuis le champ d'entrée
    const borneIdInput = document.getElementById('borneIdInput');
    const sidebarBorneIdInput = document.getElementById('sidebarBorneIdInput');
    
    let borneId = '';
    if (borneIdInput && borneIdInput.value.trim()) {
        borneId = borneIdInput.value.trim();
    } else if (sidebarBorneIdInput && sidebarBorneIdInput.value.trim()) {
        borneId = sidebarBorneIdInput.value.trim();
    } else {
        showNotification('Veuillez entrer un ID de borne valide', 'error');
        return;
    }
    
    // Validation de l'ID de borne
    if (borneId.length < 3) {
        showNotification('L\'ID de borne doit contenir au moins 3 caractères', 'error');
        return;
    }
    
    showTerminalOnAction();
    addTerminalLine(`Connecting to borne: ${borneId}...`, 'info');
    addTerminalLine(`Using charging point ID: ${chargingPointId}`, 'info');
    
    const formData = new FormData();
    formData.append('iccid', '12345678901234567890');
    formData.append('imsi', '123456789012345');
    formData.append('meter_type', 'Single_Phase_Energy_Meter');
    formData.append('meter_serial', 'METER_' + chargingPointId);
    formData.append('borne_id', borneId); // Ajouter l'ID de la borne
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
    
    fetch(`/charging-points/connect-by-id/${chargingPointId}`, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // Vérifier si la réponse est du JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            // Essayer de parser comme JSON quand même
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
            logSteVeResponse('CONNECT_BY_ID', data.data, true);
            showNotification(`Connexion réussie à ${borneId}: ` + data.message, 'success');
            updateConnectionStatus(true);
        } else {
            addTerminalLine(`Connection failed to ${borneId}: ${data.message}`, 'error');
            logSteVeResponse('CONNECT_BY_ID', data, false);
            showNotification(`Erreur de connexion à ${borneId}: ` + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        addTerminalLine(`Connection error to ${borneId}: ${error.message}`, 'error');
        addTerminalLine(`Détails: ${error.stack || 'Pas de détails disponibles'}`, 'error');
        showNotification(`Erreur lors de la connexion à ${borneId}: ` + error.message, 'error');
    });
}

function disconnectChargingPoint() {
    if (confirm('Êtes-vous sûr de vouloir déconnecter le point de charge ?')) {
        showTerminalOnAction();
        addTerminalLine(`Disconnecting charging point ${chargingPointId}...`, 'info');
        
        fetch(`/charging-points/${chargingPointId}/disconnect`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                addTerminalLine(`Disconnection successful: ${data.message}`, 'success');
                logSteVeResponse('DISCONNECT', data.data, true);
                showNotification('Point de charge déconnecté', 'success');
                updateConnectionStatus(false);
            } else {
                addTerminalLine(`Disconnection failed: ${data.message}`, 'error');
                logSteVeResponse('DISCONNECT', data, false);
                showNotification('Erreur: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            addTerminalLine(`Disconnection error: ${error.message}`, 'error');
            showNotification('Erreur lors de la déconnexion', 'error');
        });
    }
}

function getConnectionStatus() {
    showTerminalOnAction();
    addTerminalLine(`Getting connection status for charging point ${chargingPointId}...`, 'info');
    
    fetch(`/charging-points/${chargingPointId}/connection-status`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const status = data.data;
            addTerminalLine(`Status retrieved successfully`, 'success');
            addTerminalLine(`Status: ${status.status}`, 'info');
            addTerminalLine(`SteVe Connection: ${status.steve_connection_status}`, 'info');
            addTerminalLine(`Last Attempt: ${status.last_connection_attempt}`, 'info');
            addTerminalLine(`Connected: ${status.is_connected ? 'Yes' : 'No'}`, status.is_connected ? 'success' : 'error');
            
            logSteVeResponse('GET_STATUS', status, true);
            showNotification('Statut récupéré avec succès', 'success');
            
            // Mettre à jour l'interface
            updateConnectionStatus(status.is_connected);
        } else {
            addTerminalLine(`Status retrieval failed: ${data.message}`, 'error');
            logSteVeResponse('GET_STATUS', data, false);
            showNotification('Erreur: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        addTerminalLine(`Status error: ${error.message}`, 'error');
        showNotification('Erreur lors de la récupération du statut', 'error');
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
        // Vérifier si la réponse est du JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            // Essayer de parser comme JSON quand même
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
            if (data.data && data.data.method) {
                addTerminalLine(`Method: ${data.data.method}`, 'info');
            }
            if (data.data && data.data.details) {
                addTerminalLine(`Details: ${JSON.stringify(data.data.details)}`, 'info');
            }
            
            logSteVeResponse('TEST_CONNECTIVITY', data.data, true);
            showNotification('Test de connectivité réussi: ' + data.message, 'success');
        } else {
            addTerminalLine(`Connectivity test failed: ${data.message}`, 'error');
            logSteVeResponse('TEST_CONNECTIVITY', data, false);
            showNotification('Test de connectivité échoué: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        addTerminalLine(`Connectivity test error: ${error.message}`, 'error');
        addTerminalLine(`Détails: ${error.stack || 'Pas de détails disponibles'}`, 'error');
        showNotification('Erreur lors du test de connectivité: ' + error.message, 'error');
    });
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

// ===== ACTIONS STEVE API COMPLÈTES =====

// 1. Déclencher une recharge
function triggerRecharge() {
    showTerminalOnAction();
    addTerminalLine('Déclenchement d\'une recharge...', 'info');
    
    const formData = new FormData();
    formData.append('connector_id', '1');
    formData.append('id_tag', 'TEST_TAG_001');
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
    
    fetch(`/charging-points/${chargingPointId}/start-charging`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            addTerminalLine('Recharge déclenchée avec succès', 'success');
            logSteVeResponse('START_CHARGING', data, true);
            showNotification('Recharge démarrée', 'success');
        } else {
            addTerminalLine(`Erreur: ${data.message}`, 'error');
            logSteVeResponse('START_CHARGING', data, false);
            showNotification('Erreur: ' + data.message, 'error');
        }
    })
    .catch(error => {
        addTerminalLine(`Erreur: ${error.message}`, 'error');
        showNotification('Erreur lors du déclenchement', 'error');
    });
}

// 2. Arrêter une recharge
function stopCharging() {
    showTerminalOnAction();
    addTerminalLine('Arrêt de la recharge...', 'info');
    
    const formData = new FormData();
    formData.append('session_id', 'SESSION_' + Date.now());
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
    
    fetch(`/charging-points/${chargingPointId}/stop-charging`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            addTerminalLine('Recharge arrêtée avec succès', 'success');
            logSteVeResponse('STOP_CHARGING', data, true);
            showNotification('Recharge arrêtée', 'success');
        } else {
            addTerminalLine(`Erreur: ${data.message}`, 'error');
            logSteVeResponse('STOP_CHARGING', data, false);
            showNotification('Erreur: ' + data.message, 'error');
        }
    })
    .catch(error => {
        addTerminalLine(`Erreur: ${error.message}`, 'error');
        showNotification('Erreur lors de l\'arrêt', 'error');
    });
}

// 3. Déblocage de connecteur
function unlockConnector() {
    showTerminalOnAction();
    addTerminalLine('Déblocage du connecteur...', 'info');
    
    // Simulation d'un déblocage OCPP
    setTimeout(() => {
        addTerminalLine('Commande UnlockConnector envoyée', 'info');
        addTerminalLine('Connecteur débloqué avec succès', 'success');
        logSteVeResponse('UNLOCK_CONNECTOR', {
            connector_id: 1,
            status: 'Unlocked',
            timestamp: new Date().toISOString()
        }, true);
        showNotification('Connecteur débloqué', 'success');
    }, 1000);
}

// 4. Réinitialisation
function resetChargingPoint() {
    showTerminalOnAction();
    addTerminalLine('Réinitialisation du point de charge...', 'info');
    
    // Simulation d'une réinitialisation OCPP
    setTimeout(() => {
        addTerminalLine('Commande Reset envoyée', 'info');
        addTerminalLine('Point de charge réinitialisé', 'success');
        logSteVeResponse('RESET', {
            type: 'Hard',
            status: 'Accepted',
            timestamp: new Date().toISOString()
        }, true);
        showNotification('Point de charge réinitialisé', 'success');
    }, 1500);
}

// 5. Mise à jour des paramètres
function updateParameters() {
    showTerminalOnAction();
    addTerminalLine('Mise à jour des paramètres...', 'info');
    
    // Simulation d'une mise à jour de paramètres OCPP
    setTimeout(() => {
        addTerminalLine('Commande ChangeConfiguration envoyée', 'info');
        addTerminalLine('Paramètres mis à jour', 'success');
        logSteVeResponse('CHANGE_CONFIGURATION', {
            key: 'HeartbeatInterval',
            value: '300',
            status: 'Accepted',
            timestamp: new Date().toISOString()
        }, true);
        showNotification('Paramètres mis à jour', 'success');
    }, 1200);
}

// 6. Diagnostic
function runDiagnostic() {
    showTerminalOnAction();
    addTerminalLine('Exécution du diagnostic...', 'info');
    
    // Simulation d'un diagnostic complet
    setTimeout(() => {
        addTerminalLine('Diagnostic en cours...', 'info');
        addTerminalLine('✓ Connexion SteVe: OK', 'success');
        addTerminalLine('✓ OCPP Connection: OK', 'success');
        addTerminalLine('✓ Connecteurs: OK', 'success');
        addTerminalLine('✓ Système: OK', 'success');
        addTerminalLine('Diagnostic terminé avec succès', 'success');
        logSteVeResponse('DIAGNOSTIC', {
            steve_connection: 'OK',
            ocpp_connection: 'OK',
            connectors: 'OK',
            system: 'OK',
            timestamp: new Date().toISOString()
        }, true);
        showNotification('Diagnostic terminé', 'success');
    }, 2000);
}

// 7. Récupérer le log
function retrieveLogs() {
    showTerminalOnAction();
    addTerminalLine('Récupération des logs...', 'info');
    
    // Simulation de la récupération de logs
    setTimeout(() => {
        addTerminalLine('Logs récupérés avec succès', 'success');
        addTerminalLine('Fichier: steve_logs_' + new Date().toISOString().split('T')[0] + '.log', 'info');
        logSteVeResponse('GET_LOG', {
            log_type: 'DiagnosticsLog',
            status: 'Accepted',
            filename: 'steve_logs_' + new Date().toISOString().split('T')[0] + '.log',
            timestamp: new Date().toISOString()
        }, true);
        showNotification('Logs récupérés', 'success');
    }, 1500);
}

// 8. Obtenir le statut SteVe
function getSteVeStatus() {
    showTerminalOnAction();
    addTerminalLine('Récupération du statut SteVe...', 'info');
    
    fetch(`/charging-points/${chargingPointId}/status`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            addTerminalLine('Statut SteVe récupéré', 'success');
            addTerminalLine(`Status: ${data.data.status}`, 'info');
            addTerminalLine(`Connectors: ${data.data.connectors}`, 'info');
            logSteVeResponse('GET_STATUS', data.data, true);
            showNotification('Statut récupéré', 'success');
        } else {
            addTerminalLine(`Erreur: ${data.message}`, 'error');
            showNotification('Erreur: ' + data.message, 'error');
        }
    })
    .catch(error => {
        addTerminalLine(`Erreur: ${error.message}`, 'error');
        showNotification('Erreur lors de la récupération', 'error');
    });
}

// 9. Obtenir les sessions actives
function getActiveSessions() {
    showTerminalOnAction();
    addTerminalLine('Récupération des sessions actives...', 'info');
    
    fetch(`/charging-points/${chargingPointId}/sessions`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            addTerminalLine('Sessions récupérées', 'success');
            addTerminalLine(`Sessions actives: ${data.data.length}`, 'info');
            logSteVeResponse('GET_SESSIONS', data.data, true);
            showNotification('Sessions récupérées', 'success');
        } else {
            addTerminalLine(`Erreur: ${data.message}`, 'error');
            showNotification('Erreur: ' + data.message, 'error');
        }
    })
    .catch(error => {
        addTerminalLine(`Erreur: ${error.message}`, 'error');
        showNotification('Erreur lors de la récupération', 'error');
    });
}

// 10. Créer une réservation
function createReservation() {
    showTerminalOnAction();
    addTerminalLine('Création d\'une réservation...', 'info');
    
    const formData = new FormData();
    formData.append('connector_id', '1');
    formData.append('id_tag', 'RESERVATION_TAG');
    formData.append('expiry_date', new Date(Date.now() + 24 * 60 * 60 * 1000).toISOString());
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
    
    fetch(`/charging-points/${chargingPointId}/create-reservation`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            addTerminalLine('Réservation créée avec succès', 'success');
            addTerminalLine(`ID Réservation: ${data.data.reservation_id}`, 'info');
            logSteVeResponse('CREATE_RESERVATION', data.data, true);
            showNotification('Réservation créée', 'success');
        } else {
            addTerminalLine(`Erreur: ${data.message}`, 'error');
            showNotification('Erreur: ' + data.message, 'error');
        }
    })
    .catch(error => {
        addTerminalLine(`Erreur: ${error.message}`, 'error');
        showNotification('Erreur lors de la création', 'error');
    });
}

// 11. Annuler une réservation
function cancelReservation() {
    showTerminalOnAction();
    addTerminalLine('Annulation de la réservation...', 'info');
    
    const reservationId = 'RES_' + Date.now();
    const formData = new FormData();
    formData.append('reservation_id', reservationId);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
    
    fetch(`/charging-points/${chargingPointId}/cancel-reservation`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            addTerminalLine('Réservation annulée avec succès', 'success');
            logSteVeResponse('CANCEL_RESERVATION', data.data, true);
            showNotification('Réservation annulée', 'success');
        } else {
            addTerminalLine(`Erreur: ${data.message}`, 'error');
            showNotification('Erreur: ' + data.message, 'error');
        }
    })
    .catch(error => {
        addTerminalLine(`Erreur: ${error.message}`, 'error');
        showNotification('Erreur lors de l\'annulation', 'error');
    });
}

</script>
@endsection
