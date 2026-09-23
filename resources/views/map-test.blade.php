{{-- Page de test pour la carte --}}
@extends('layouts.app')

@section('title', 'Test de la Carte')

@section('content')
<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto">
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white">
                <h1 class="text-2xl font-bold text-center">
                    🗺️ Test de la Carte - EVON
                </h1>
            </div>

            <div class="p-6">
                {{-- Contrôles de test --}}
                <div class="mb-6 bg-gray-50 rounded-lg p-4">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Contrôles de Test</h2>
                    <div class="flex flex-wrap gap-2">
                        <button onclick="runMapTests()" 
                                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            🧪 Lancer les Tests
                        </button>
                        <button onclick="testLeafletOnly()" 
                                class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                            🍃 Test Leaflet
                        </button>
                        <button onclick="testMapCreation()" 
                                class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700">
                            🗺️ Test Carte
                        </button>
                        <button onclick="testGeocoding()" 
                                class="px-4 py-2 bg-orange-600 text-white rounded hover:bg-orange-700">
                            🔍 Test Géocodage
                        </button>
                        <button onclick="clearResults()" 
                                class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                            🗑️ Effacer
                        </button>
                    </div>
                </div>

                {{-- Résultats des tests --}}
                <div id="map-test-results" class="mb-6">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-center gap-2">
                            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
                            <span class="text-blue-800">Prêt pour les tests...</span>
                        </div>
                    </div>
                </div>

                {{-- Carte de test --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Carte de Test</h3>
                        <div class="border rounded-lg overflow-hidden">
                            <div id="map" style="height: 400px; width: 100%;"></div>
                        </div>
                        <div class="mt-2 text-sm text-gray-600">
                            <p><strong>Instructions:</strong></p>
                            <ul class="list-disc list-inside space-y-1">
                                <li>Cliquez sur la carte pour déplacer le marqueur</li>
                                <li>Glissez le marqueur pour le repositionner</li>
                                <li>Utilisez les contrôles de zoom</li>
                                <li>Testez la recherche d'adresse</li>
                            </ul>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Contrôles de la Carte</h3>
                        
                        {{-- Recherche d'adresse --}}
                        <div class="mb-4">
                            <label for="address-search" class="block text-sm font-medium text-gray-700 mb-2">
                                Recherche d'Adresse
                            </label>
                            <div class="relative">
                                <input type="text" 
                                       id="address-search" 
                                       placeholder="Entrez une adresse..."
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <button onclick="searchAddress()" 
                                        class="absolute right-2 top-2 px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">
                                    🔍
                                </button>
                            </div>
                        </div>

                        {{-- Coordonnées --}}
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="test-lat" class="block text-sm font-medium text-gray-700 mb-1">
                                    Latitude
                                </label>
                                <input type="number" 
                                       id="test-lat" 
                                       step="0.000001" 
                                       value="33.5731"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="test-lng" class="block text-sm font-medium text-gray-700 mb-1">
                                    Longitude
                                </label>
                                <input type="number" 
                                       id="test-lng" 
                                       step="0.000001" 
                                       value="-7.5898"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        {{-- Boutons de contrôle --}}
                        <div class="space-y-2">
                            <button onclick="goToCoordinates()" 
                                    class="w-full px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                                📍 Aller aux Coordonnées
                            </button>
                            <button onclick="getCurrentLocation()" 
                                    class="w-full px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700">
                                📍 Ma Position
                            </button>
                            <button onclick="resetMap()" 
                                    class="w-full px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                                🔄 Réinitialiser
                            </button>
                        </div>

                        {{-- Informations de la carte --}}
                        <div class="mt-4 p-3 bg-gray-50 rounded">
                            <h4 class="font-semibold text-gray-900 mb-2">Informations</h4>
                            <div class="text-sm text-gray-600 space-y-1">
                                <p><strong>Position actuelle:</strong> <span id="current-position">Non définie</span></p>
                                <p><strong>Zoom:</strong> <span id="current-zoom">13</span></p>
                                <p><strong>Adresse:</strong> <span id="current-address">Non définie</span></p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Logs de test --}}
                <div class="mt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Logs de Test</h3>
                    <div id="test-logs" class="bg-gray-900 text-green-400 p-4 rounded-lg font-mono text-sm h-64 overflow-y-auto">
                        <div class="text-gray-500">En attente des tests...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Scripts de test --}}
<script src="{{ asset('js/map-test.js') }}"></script>

{{-- Leaflet CSS et JS --}}
<link rel="stylesheet" href="{{ asset("css/leaflet/leaflet.css") }}" />
<script src="{{ asset("js/leaflet/leaflet.js") }}"></script>

<script>
// Variables globales pour la carte de test
let testMap = null;
let testMarker = null;
let testInitialized = false;

// Fonction pour ajouter des logs
function addLog(message, type = 'info') {
    const logsContainer = document.getElementById('test-logs');
    const timestamp = new Date().toLocaleTimeString();
    const logEntry = document.createElement('div');
    logEntry.className = `mb-1 ${type === 'error' ? 'text-red-400' : type === 'success' ? 'text-green-400' : 'text-blue-400'}`;
    logEntry.innerHTML = `[${timestamp}] ${message}`;
    logsContainer.appendChild(logEntry);
    logsContainer.scrollTop = logsContainer.scrollHeight;
}

// Fonction pour lancer tous les tests
async function runMapTests() {
    addLog('🚀 Démarrage des tests complets...', 'info');
    
    if (typeof window.testMapFunctionality === 'function') {
        try {
            const results = await window.testMapFunctionality();
            addLog(`✅ Tests terminés - Score: ${results.score}%`, 'success');
        } catch (error) {
            addLog(`❌ Erreur lors des tests: ${error.message}`, 'error');
        }
    } else {
        addLog('❌ Script de test non chargé', 'error');
    }
}

// Fonction pour tester Leaflet uniquement
function testLeafletOnly() {
    addLog('🍃 Test de Leaflet...', 'info');
    
    if (typeof L !== 'undefined') {
        addLog('✅ Leaflet chargé avec succès', 'success');
        addLog(`Version Leaflet: ${L.version || 'Inconnue'}`, 'info');
    } else {
        addLog('❌ Leaflet non chargé', 'error');
    }
}

// Fonction pour tester la création de carte
function testMapCreation() {
    addLog('🗺️ Test de création de carte...', 'info');
    
    try {
        if (typeof L === 'undefined') {
            addLog('❌ Leaflet non disponible', 'error');
            return;
        }
        
        // Nettoyer la carte existante
        if (testMap) {
            testMap.remove();
            testMap = null;
        }
        
        // Créer une nouvelle carte
        testMap = L.map('map', {
            zoomControl: true,
            attributionControl: true
        }).setView([33.5731, -7.5898], 13);
        
        // Ajouter les tuiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(testMap);
        
        // Créer un marqueur
        testMarker = L.marker([33.5731, -7.5898], {
            draggable: true,
            title: 'Marqueur de test'
        }).addTo(testMap);
        
        // Événements
        testMarker.on('dragend', function(e) {
            const pos = e.target.getLatLng();
            addLog(`📍 Marqueur déplacé: ${pos.lat.toFixed(6)}, ${pos.lng.toFixed(6)}`, 'info');
            updatePositionInfo(pos.lat, pos.lng);
        });
        
        testMap.on('click', function(e) {
            const pos = e.latlng;
            addLog(`🖱️ Clic sur la carte: ${pos.lat.toFixed(6)}, ${pos.lng.toFixed(6)}`, 'info');
            testMarker.setLatLng(pos);
            updatePositionInfo(pos.lat, pos.lng);
        });
        
        testMap.on('zoomend', function() {
            const zoom = testMap.getZoom();
            addLog(`🔍 Zoom changé: ${zoom}`, 'info');
            document.getElementById('current-zoom').textContent = zoom;
        });
        
        testInitialized = true;
        addLog('✅ Carte créée avec succès', 'success');
        updatePositionInfo(33.5731, -7.5898);
        
    } catch (error) {
        addLog(`❌ Erreur lors de la création: ${error.message}`, 'error');
    }
}

// Fonction pour tester le géocodage
async function testGeocoding() {
    addLog('🔍 Test de géocodage...', 'info');
    
    const testAddress = 'Casablanca, Morocco';
    const geocodingUrl = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(testAddress)}&limit=1`;
    
    try {
        const response = await fetch(geocodingUrl);
        const data = await response.json();
        
        if (data && data.length > 0) {
            const result = data[0];
            addLog(`✅ Géocodage réussi: ${result.display_name}`, 'success');
            addLog(`📍 Coordonnées: ${result.lat}, ${result.lon}`, 'info');
            
            // Mettre à jour la carte si elle existe
            if (testMap && testMarker) {
                const lat = parseFloat(result.lat);
                const lng = parseFloat(result.lon);
                testMap.setView([lat, lng], 15);
                testMarker.setLatLng([lat, lng]);
                updatePositionInfo(lat, lng);
            }
        } else {
            addLog('❌ Aucun résultat de géocodage', 'error');
        }
    } catch (error) {
        addLog(`❌ Erreur de géocodage: ${error.message}`, 'error');
    }
}

// Fonction pour rechercher une adresse
async function searchAddress() {
    const address = document.getElementById('address-search').value;
    if (!address.trim()) {
        addLog('❌ Veuillez entrer une adresse', 'error');
        return;
    }
    
    addLog(`🔍 Recherche: ${address}`, 'info');
    
    const geocodingUrl = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&limit=1`;
    
    try {
        const response = await fetch(geocodingUrl);
        const data = await response.json();
        
        if (data && data.length > 0) {
            const result = data[0];
            addLog(`✅ Adresse trouvée: ${result.display_name}`, 'success');
            
            if (testMap && testMarker) {
                const lat = parseFloat(result.lat);
                const lng = parseFloat(result.lon);
                testMap.setView([lat, lng], 15);
                testMarker.setLatLng([lat, lng]);
                updatePositionInfo(lat, lng);
                document.getElementById('current-address').textContent = result.display_name;
            }
        } else {
            addLog('❌ Adresse non trouvée', 'error');
        }
    } catch (error) {
        addLog(`❌ Erreur de recherche: ${error.message}`, 'error');
    }
}

// Fonction pour aller aux coordonnées
function goToCoordinates() {
    const lat = parseFloat(document.getElementById('test-lat').value);
    const lng = parseFloat(document.getElementById('test-lng').value);
    
    if (isNaN(lat) || isNaN(lng)) {
        addLog('❌ Coordonnées invalides', 'error');
        return;
    }
    
    if (testMap && testMarker) {
        testMap.setView([lat, lng], 15);
        testMarker.setLatLng([lat, lng]);
        updatePositionInfo(lat, lng);
        addLog(`📍 Aller à: ${lat}, ${lng}`, 'info');
    } else {
        addLog('❌ Carte non initialisée', 'error');
    }
}

// Fonction pour obtenir la position actuelle
function getCurrentLocation() {
    addLog('📍 Obtention de la position actuelle...', 'info');
    
    if (!navigator.geolocation) {
        addLog('❌ Géolocalisation non supportée', 'error');
        return;
    }
    
    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            addLog(`✅ Position obtenue: ${lat.toFixed(6)}, ${lng.toFixed(6)}`, 'success');
            
            if (testMap && testMarker) {
                testMap.setView([lat, lng], 15);
                testMarker.setLatLng([lat, lng]);
                updatePositionInfo(lat, lng);
            }
        },
        function(error) {
            addLog(`❌ Erreur de géolocalisation: ${error.message}`, 'error');
        }
    );
}

// Fonction pour réinitialiser la carte
function resetMap() {
    if (testMap) {
        testMap.setView([33.5731, -7.5898], 13);
        if (testMarker) {
            testMarker.setLatLng([33.5731, -7.5898]);
        }
        updatePositionInfo(33.5731, -7.5898);
        addLog('🔄 Carte réinitialisée', 'info');
    }
}

// Fonction pour mettre à jour les informations de position
function updatePositionInfo(lat, lng) {
    document.getElementById('current-position').textContent = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
    document.getElementById('test-lat').value = lat.toFixed(6);
    document.getElementById('test-lng').value = lng.toFixed(6);
}

// Fonction pour effacer les résultats
function clearResults() {
    document.getElementById('test-logs').innerHTML = '<div class="text-gray-500">Logs effacés...</div>';
    document.getElementById('map-test-results').innerHTML = '<div class="bg-blue-50 border border-blue-200 rounded-lg p-4"><div class="flex items-center gap-2"><div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div><span class="text-blue-800">Prêt pour les tests...</span></div></div>';
}

// Initialisation automatique
document.addEventListener('DOMContentLoaded', function() {
    addLog('🚀 Page de test chargée', 'info');
    
    // Attendre que Leaflet se charge
    setTimeout(() => {
        if (typeof L !== 'undefined') {
            addLog('✅ Leaflet chargé automatiquement', 'success');
            testMapCreation();
        } else {
            addLog('❌ Leaflet non chargé automatiquement', 'error');
        }
    }, 1000);
});
</script>
@endsection
