@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-green-50 via-white to-blue-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">Test du Modèle 3D EvonPower</h1>
            <p class="text-xl text-gray-600">Vérification complète du composant Model3DViewer</p>
        </div>
        
        <!-- Grid Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
            
            <!-- Left Side - 3D Model -->
            <div class="space-y-6">
                <div class="bg-white/80 backdrop-blur-sm rounded-3xl p-8 shadow-2xl border border-white/20">
                    <h3 class="text-2xl font-bold text-gray-800 mb-4 text-center">Modèle 3D hitem3d.glb</h3>
                    
                    <!-- 3D Model Container -->
                    <div id="3d-model-container" class="w-full h-96 rounded-2xl overflow-hidden bg-gradient-to-br from-gray-900 to-gray-800 shadow-inner mb-6">
                        <!-- Loading indicator -->
                        <div id="3d-loading" class="flex items-center justify-center h-full">
                            <div class="text-center">
                                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-500 mx-auto mb-4"></div>
                                <p class="text-green-400 font-medium">Chargement du modèle 3D...</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Model Info -->
                    <div class="text-center">
                        <div class="inline-flex items-center px-4 py-2 rounded-full bg-green-100 text-green-800 text-sm font-medium">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            Modèle 3D Interactif
                        </div>
                    </div>
                </div>
                
                <!-- Controls -->
                <div class="bg-white/80 backdrop-blur-sm rounded-2xl p-6 shadow-xl border border-white/20">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Contrôles de Test</h4>
                    <div class="space-y-3">
                        <button onclick="testModel()" class="w-full px-4 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-all duration-200 transform hover:scale-105">
                            🚀 Tester le Modèle 3D
                        </button>
                        <button onclick="resetModel()" class="w-full px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-all duration-200 transform hover:scale-105">
                            🔄 Réinitialiser
                        </button>
                        <button onclick="checkWebGL()" class="w-full px-4 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-all duration-200 transform hover:scale-105">
                            🌐 Vérifier WebGL
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Right Side - Test Results -->
            <div class="space-y-6">
                
                <!-- Status -->
                <div class="bg-white/80 backdrop-blur-sm rounded-2xl p-6 shadow-xl border border-white/20">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">État du Composant</h4>
                    <div id="component-status" class="text-center">
                        <span class="inline-flex items-center px-3 py-2 rounded-full bg-yellow-100 text-yellow-800 text-sm font-medium">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-yellow-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            En attente d'initialisation...
                        </span>
                    </div>
                </div>
                
                <!-- Console Logs -->
                <div class="bg-white/80 backdrop-blur-sm rounded-2xl p-6 shadow-xl border border-white/20">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Console Logs</h4>
                    <div id="console-logs" class="bg-gray-900 rounded-lg p-4 text-xs font-mono max-h-64 overflow-y-auto text-green-400">
                        <div>Console prête...</div>
                    </div>
                </div>
                
                <!-- Test Results -->
                <div class="bg-white/80 backdrop-blur-sm rounded-2xl p-6 shadow-xl border border-white/20">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Résultats des Tests</h4>
                    <div id="test-results" class="space-y-3">
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="text-gray-700">Modèle 3D</span>
                            <span class="text-gray-500">En attente...</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="text-gray-700">WebGL Support</span>
                            <span class="text-gray-500">En attente...</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="text-gray-700">Three.js</span>
                            <span class="text-gray-500">En attente...</span>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="bg-white/80 backdrop-blur-sm rounded-2xl p-6 shadow-xl border border-white/20">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Actions Rapides</h4>
                    <div class="grid grid-cols-2 gap-3">
                        <button onclick="runAllTests()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors text-sm">
                            🧪 Tous les Tests
                        </button>
                        <button onclick="clearLogs()" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors text-sm">
                            🗑️ Vider Logs
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Instructions -->
        <div class="mt-12 bg-white/80 backdrop-blur-sm rounded-3xl p-8 shadow-xl border border-white/20">
            <h3 class="text-2xl font-bold text-gray-800 mb-6 text-center">Instructions de Test</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <h4 class="text-lg font-semibold text-gray-800 mb-3">📋 Prérequis</h4>
                    <ul class="space-y-2 text-gray-600">
                        <li>✅ Fichier <code class="bg-gray-100 px-2 py-1 rounded">hitem3d.glb</code> dans <code class="bg-gray-100 px-2 py-1 rounded">public/models/</code></li>
                        <li>✅ Navigateur moderne avec support WebGL</li>
                        <li>✅ JavaScript activé</li>
                        <li>✅ Console du navigateur ouverte</li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-semibold text-gray-800 mb-3">🔍 Vérifications</h4>
                    <ul class="space-y-2 text-gray-600">
                        <li>📊 Logs de chargement dans la console</li>
                        <li>🎯 Modèle 3D avec rotation et flottement</li>
                        <li>📱 Responsive design sur mobile</li>
                        <li>⚡ Performance et fluidité</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/3d-model-viewer.js') }}"></script>
<script>
let modelViewer = null;
let logs = [];
let testResults = {};

// Fonction pour ajouter des logs
function addLog(message, type = 'info') {
    const timestamp = new Date().toLocaleTimeString();
    const logEntry = `[${timestamp}] ${message}`;
    logs.push(logEntry);
    
    const logsContainer = document.getElementById('console-logs');
    if (logsContainer) {
        logsContainer.innerHTML = logs.map(log => 
            `<div class="text-green-400">${log}</div>`
        ).join('');
        logsContainer.scrollTop = logsContainer.scrollHeight;
    }
}

// Fonction pour mettre à jour les résultats de test
function updateTestResult(testName, status, message = '') {
    testResults[testName] = { status, message };
    
    const resultsContainer = document.getElementById('test-results');
    if (resultsContainer) {
        const testElement = resultsContainer.querySelector(`[data-test="${testName}"]`);
        if (testElement) {
            const statusElement = testElement.querySelector('span:last-child');
            if (statusElement) {
                statusElement.textContent = message || status;
                statusElement.className = `text-${status === 'success' ? 'green' : status === 'error' ? 'red' : 'yellow'}-600 font-medium`;
            }
        }
    }
}

// Fonction de test du modèle
function testModel() {
    addLog('🧪 Test du modèle 3D...');
    
    if (modelViewer) {
        addLog('⚠️ Modèle 3D déjà initialisé');
        updateTestResult('model', 'warning', 'Déjà initialisé');
        return;
    }
    
    try {
        addLog('🚀 Initialisation du Model3DViewer...');
        modelViewer = new Model3DViewer('3d-model-container', '{{ asset("models/hitem3d.glb") }}');
        
        // Mettre à jour le statut
        const statusElement = document.getElementById('component-status');
        if (statusElement) {
            statusElement.innerHTML = `
                <span class="inline-flex items-center px-3 py-2 rounded-full bg-green-100 text-green-800 text-sm font-medium">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    Modèle 3D Actif
                </span>
            `;
        }
        
        updateTestResult('model', 'success', '✅ Actif');
        addLog('✅ Model3DViewer initialisé avec succès');
        
        // Masquer l'indicateur de chargement après un délai
        setTimeout(() => {
            const loadingElement = document.getElementById('3d-loading');
            if (loadingElement) {
                loadingElement.style.display = 'none';
                addLog('📱 Indicateur de chargement masqué');
            }
        }, 3000);
        
    } catch (error) {
        addLog(`❌ Erreur lors de l'initialisation: ${error.message}`);
        updateTestResult('model', 'error', '❌ Erreur');
        
        const statusElement = document.getElementById('component-status');
        if (statusElement) {
            statusElement.innerHTML = `
                <span class="inline-flex items-center px-3 py-2 rounded-full bg-red-100 text-red-800 text-sm font-medium">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    Erreur d'initialisation
                </span>
            `;
        }
    }
}

// Fonction de réinitialisation
function resetModel() {
    addLog('🔄 Réinitialisation du modèle 3D...');
    
    if (modelViewer) {
        modelViewer.destroy();
        modelViewer = null;
        addLog('🗑️ Modèle 3D détruit');
    }
    
    // Réinitialiser le conteneur
    const container = document.getElementById('3d-model-container');
    if (container) {
        container.innerHTML = `
            <div id="3d-loading" class="flex items-center justify-center h-full">
                <div class="text-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-500 mx-auto mb-4"></div>
                    <p class="text-green-400 font-medium">Chargement du modèle 3D...</p>
                </div>
            </div>
        `;
    }
    
    // Réinitialiser le statut
    const statusElement = document.getElementById('component-status');
    if (statusElement) {
        statusElement.innerHTML = `
            <span class="inline-flex items-center px-3 py-2 rounded-full bg-yellow-100 text-yellow-800 text-sm font-medium">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-yellow-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                En attente d'initialisation...
            </span>
        `;
    }
    
    // Réinitialiser les résultats de test
    updateTestResult('model', 'pending', 'En attente...');
    updateTestResult('webgl', 'pending', 'En attente...');
    updateTestResult('threejs', 'pending', 'En attente...');
    
    addLog('✅ Modèle 3D réinitialisé');
}

// Fonction de vérification WebGL
function checkWebGL() {
    addLog('🌐 Vérification du support WebGL...');
    
    try {
        const canvas = document.createElement('canvas');
        const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
        
        if (gl) {
            const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
            const renderer = debugInfo ? gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL) : 'Inconnu';
            
            addLog(`✅ WebGL supporté: ${renderer}`);
            updateTestResult('webgl', 'success', '✅ Supporté');
        } else {
            addLog('❌ WebGL non supporté');
            updateTestResult('webgl', 'error', '❌ Non supporté');
        }
    } catch (error) {
        addLog(`❌ Erreur lors de la vérification WebGL: ${error.message}`);
        updateTestResult('webgl', 'error', '❌ Erreur');
    }
}

// Fonction pour exécuter tous les tests
function runAllTests() {
    addLog('🧪 Exécution de tous les tests...');
    
    setTimeout(() => checkWebGL(), 500);
    setTimeout(() => testModel(), 1000);
    
    // Test Three.js après l'initialisation
    setTimeout(() => {
        if (typeof THREE !== 'undefined') {
            addLog('✅ Three.js détecté');
            updateTestResult('threejs', 'success', '✅ Détecté');
        } else {
            addLog('❌ Three.js non détecté');
            updateTestResult('threejs', 'error', '❌ Non détecté');
        }
    }, 3000);
}

// Fonction pour vider les logs
function clearLogs() {
    logs = [];
    const logsContainer = document.getElementById('console-logs');
    if (logsContainer) {
        logsContainer.innerHTML = '<div class="text-green-400">Logs vidés...</div>';
    }
    addLog('🗑️ Logs vidés');
}

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    addLog('🚀 Page chargée, prêt pour les tests');
    
    // Initialiser les résultats de test
    updateTestResult('model', 'pending', 'En attente...');
    updateTestResult('webgl', 'pending', 'En attente...');
    updateTestResult('threejs', 'pending', 'En attente...');
    
    // Test automatique après 2 secondes
    setTimeout(() => {
        runAllTests();
    }, 2000);
});

// Intercepter les logs de la console
const originalLog = console.log;
const originalError = console.error;

console.log = function(...args) {
    originalLog.apply(console, args);
    addLog(args.join(' '));
};

console.error = function(...args) {
    originalError.apply(console, args);
    addLog(`❌ ERREUR: ${args.join(' ')}`);
};
</script>
@endpush

@push('styles')
<link rel="stylesheet" href="{{ asset('css/3d-model.css') }}">
@endpush
