@extends('layouts.app')

@section('content')
<div class="bg-gray-50 min-h-screen">
    <!-- Header with back button and tabs -->
    <div class="border-b border-gray-200 bg-white">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex items-center py-3">
                <a href="{{ route('charging-points.index') }}" class="mr-4 text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <h1 class="text-xl lg:text-2xl font-medium text-gray-900">{{ $chargingPoint->name }}</h1>
                </div>
                
            <div class="flex flex-wrap lg:flex-nowrap space-x-4 lg:space-x-8 -mb-px">
                <a href="#overview" class="border-b-2 border-green-500 text-green-600 font-medium py-4 px-1">
                    Informations
                </a>
                <a href="#sessions" class="text-gray-500 hover:text-gray-700 hover:border-gray-300 py-4 px-1">
                    Sessions de recharges
                </a>
                <a href="#integrations" class="text-gray-500 hover:text-gray-700 hover:border-gray-300 py-4 px-1">
                    Intégration
                </a>
                <a href="#payments" class="text-gray-500 hover:text-gray-700 hover:border-gray-300 py-4 px-1">
                    Paiements
                </a>
            </div>
        </div>
    </div>

    <div class="w-full px-4 sm:px-6 lg:px-8 py-6">
        @if(session('success'))
            <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm" role="alert">
                <p>{{ session('success') }}</p>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm" role="alert">
                <p>{{ session('error') }}</p>
            </div>
        @endif

        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Main content -->
            <div class="w-full lg:w-3/4 xl:w-4/5">
                <!-- Charging point header -->
                <div class="bg-white shadow rounded-lg p-6 mb-6">
                    <div class="flex flex-col md:flex-row md:justify-between md:items-center">
                        <div class="mb-4 md:mb-0">
                            <p class="text-gray-500">Borne de recharge</p>
                            <h1 class="text-2xl font-bold">{{ $chargingPoint->name }}</h1>
                            <p class="text-gray-500">{{ $chargingPoint->serial_number }}</p>
                        </div>
                        <div class="flex space-x-2">
                            <button onclick="toggleDetailedInfo()" class="flex items-center px-4 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors text-sm">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                <span id="detailed-info-toggle-text">Afficher détails</span>
                            </button>
                            <a href="{{ route('charging-points.enhanced', $chargingPoint->id) }}" 
                               class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium">
                                <i class="fas fa-rocket mr-2"></i>Interface Améliorée
                            </a>
                                    </div>
                                </div>

                    <!-- Connection status alert (shown only if offline) -->
                    @if($chargingPoint->status == 'offline')
                    <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM10 11a1 1 0 00-1 1v1a1 1 0 002 0v-1a1 1 0 00-1-1zm0-7.5a1 1 0 00-1 1v3a1 1 0 002 0v-3a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                </svg>
                            </div>
                            <div class="ml-3 flex-1">
                                <h3 class="text-sm font-medium text-yellow-800">
                                    Le point de charge n'est pas connecté !
                                </h3>
                                <p class="text-sm text-yellow-700 mt-1">
                                    Veuillez connecter le point de charge pour commencer à l'utiliser.
                                </p>
                            </div>
                            <div class="ml-4">
                                <form action="{{ route('charging-points.update', $chargingPoint->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="status" value="online">
                                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium">
                                        Connecter
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Statistics -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900">Statistiques</h2>
                            <p class="text-sm text-gray-600 mt-1">Performances de la borne</p>
                        </div>
                        <div class="relative">
                            <select id="stats-period" class="block appearance-none bg-white border border-gray-300 hover:border-gray-400 px-4 py-2 pr-8 rounded-lg leading-tight focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm">
                                <option value="day">Aujourd'hui</option>
                                <option value="week">Cette semaine</option>
                                <option value="month">Ce mois</option>
                                <option value="year">Cette année</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                            </div>
                                        </div>
                                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-6">
                        <div class="bg-white shadow rounded-lg p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Consommation totale</p>
                                    <p class="text-2xl font-bold text-gray-900" id="total-consumption">
                                        {{ number_format($chargingPoint->energy_delivered ?? 0, 2) }} kWh
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white shadow rounded-lg p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Sessions de recharges</p>
                                    <p class="text-2xl font-bold text-gray-900" id="session-count">
                                        {{ $chargingPoint->transactions_count ?? 0 }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white shadow rounded-lg p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Charges réussies</p>
                                    <p class="text-2xl font-bold text-gray-900" id="success-rate">
                                        {{ $chargingPoint->success_rate ?? '99' }}%
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section d'informations détaillées (masquée par défaut) -->
                <div id="detailed-info-content" class="hidden bg-white shadow rounded-lg p-6 mb-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Informations Détaillées</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="font-semibold text-gray-900 mb-3">Spécifications Techniques</h3>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Fabricant:</span>
                                    <span class="font-medium">{{ $chargingPoint->manufacturer ?? 'N/A' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Modèle:</span>
                                    <span class="font-medium">{{ $chargingPoint->model ?? 'N/A' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Numéro de série:</span>
                                    <span class="font-medium">{{ $chargingPoint->serial_number ?? 'N/A' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Puissance:</span>
                                    <span class="font-medium">{{ $chargingPoint->power_output ?? 'N/A' }} kW</span>
                    </div>
                </div>
            </div>

                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="font-semibold text-gray-900 mb-3">Statut Opérationnel</h3>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Statut:</span>
                                    <span class="font-medium text-green-600">{{ $chargingPoint->status ?? 'N/A' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Dernière activité:</span>
                                    <span class="font-medium">{{ $chargingPoint->updated_at ? $chargingPoint->updated_at->format('d/m/Y H:i') : 'N/A' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Créé le:</span>
                                    <span class="font-medium">{{ $chargingPoint->created_at ? $chargingPoint->created_at->format('d/m/Y') : 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 bg-green-50 rounded-lg p-4">
                        <h3 class="font-semibold text-green-900 mb-3">Actions Rapides</h3>
                        <div class="flex flex-wrap gap-2">
                            <button onclick="toggleQRCode()" class="px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm">
                                <i class="fas fa-qrcode mr-2"></i>Générer QR Code
                        </button>
                            <button onclick="toggleEstimation()" class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm">
                                <i class="fas fa-calculator mr-2"></i>Estimation de coût
                        </button>
                            <button onclick="toggleSteVeActions()" class="px-3 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-sm">
                                <i class="fas fa-plug mr-2"></i>Actions SteVe
                        </button>
                        </div>
                    </div>
                </div>

                <!-- Actions SteVe -->
                <div class="bg-white shadow rounded-lg p-6 mb-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Actions SteVe</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="font-semibold text-gray-900 mb-3">Connexion par ID</h3>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">ID SteVe</label>
                                    <input type="text" id="steve-id" value="BORNE777" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                </div>
                                <button onclick="connectToSteVe()" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                    <i class="fas fa-plug mr-2"></i>Connecter
                                </button>
                            </div>
                        </div>

                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="font-semibold text-gray-900 mb-3">Test de connectivité</h3>
                            <div class="space-y-3">
                                <div class="flex items-center">
                                    <div id="connection-status" class="w-3 h-3 bg-gray-400 rounded-full mr-3"></div>
                                    <span id="connection-text" class="text-sm text-gray-600">Déconnecté</span>
                                </div>
                                <button onclick="testConnectivity()" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                    <i class="fas fa-wifi mr-2"></i>Test de connectivité
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 bg-blue-50 rounded-lg p-4">
                        <h3 class="font-semibold text-blue-900 mb-3">Actions disponibles</h3>
                        <div class="flex flex-wrap gap-2">
                            <button onclick="executeSteVeAction('start')" class="px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm">
                                <i class="fas fa-play mr-2"></i>Démarrer
                            </button>
                            <button onclick="executeSteVeAction('stop')" class="px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-sm">
                                <i class="fas fa-stop mr-2"></i>Arrêter
                            </button>
                            <button onclick="executeSteVeAction('status')" class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm">
                                <i class="fas fa-info mr-2"></i>Statut
                            </button>
                            <button onclick="executeSteVeAction('transactions')" class="px-3 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-sm">
                                <i class="fas fa-list mr-2"></i>Transactions
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right sidebar -->
            <div class="w-full lg:w-1/4 xl:w-1/5">
                <div class="bg-white shadow rounded-lg">
                    <div class="p-6 space-y-6">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900">Détails</h3>
                            <p class="text-sm text-gray-600 mt-1">Informations techniques de la borne</p>
                            </div>
                        
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm font-semibold text-gray-700 mb-1">Nom</p>
                            <p class="text-lg font-medium text-gray-900">{{ $chargingPoint->name }}</p>
                        </div>
                        
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm font-semibold text-gray-700 mb-1">Statut</p>
                            <p class="text-lg font-medium text-gray-900">{{ $chargingPoint->status }}</p>
                            </div>
                        
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm font-semibold text-gray-700 mb-1">Puissance</p>
                            <p class="text-lg font-medium text-gray-900">{{ $chargingPoint->power_output }} kW</p>
                        </div>
                        
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm font-semibold text-gray-700 mb-1">Fabricant</p>
                            <p class="text-lg font-medium text-gray-900">{{ $chargingPoint->manufacturer }}</p>
                            </div>
                        
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm font-semibold text-gray-700 mb-1">Modèle</p>
                            <p class="text-lg font-medium text-gray-900">{{ $chargingPoint->model }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript pour les fonctionnalités interactives -->
<script>
    // Fonction pour afficher/masquer les informations détaillées
    function toggleDetailedInfo() {
        const content = document.getElementById('detailed-info-content');
        const toggleText = document.getElementById('detailed-info-toggle-text');
        
        if (content.classList.contains('hidden')) {
            content.classList.remove('hidden');
            toggleText.textContent = 'Masquer détails';
        } else {
            content.classList.add('hidden');
            toggleText.textContent = 'Afficher détails';
        }
    }

    // Fonction pour afficher/masquer le QR Code
    function toggleQRCode() {
        const qrCodeSection = document.getElementById('qr-code-section');
        if (!qrCodeSection) {
            // Créer la section QR Code si elle n'existe pas
            createQRCodeSection();
        } else {
            // Basculer l'affichage
            qrCodeSection.style.display = qrCodeSection.style.display === 'none' ? 'block' : 'none';
        }
    }

    // Fonction pour créer la section QR Code
    function createQRCodeSection() {
        const container = document.querySelector('.w-full.lg\\:w-3\\/4.xl\\:w-4\\/5');
        const qrSection = document.createElement('div');
        qrSection.id = 'qr-code-section';
        qrSection.className = 'bg-white shadow rounded-lg p-6 mb-6';
        qrSection.innerHTML = `
            <h2 class="text-xl font-semibold text-gray-900 mb-4">QR Code de Réservation</h2>
            <div class="text-center">
                <div id="qr-code-container" class="mb-4">
                    <p class="text-gray-600">Cliquez sur "Générer QR Code" pour afficher le code</p>
                </div>
                <div class="flex justify-center space-x-4">
                    <button onclick="generateQRCode()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-qrcode mr-2"></i>Générer QR Code
                    </button>
                    <button onclick="downloadQRCode()" id="download-btn" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors hidden">
                        <i class="fas fa-download mr-2"></i>Télécharger
                    </button>
                </div>
                <div id="qr-code-url" class="mt-4 hidden">
                    <p class="text-sm text-gray-600">URL de réservation:</p>
                    <a href="#" id="reservation-url" class="text-blue-600 hover:underline" target="_blank"></a>
                </div>
            </div>
        `;
        container.appendChild(qrSection);
    }

    // Fonction pour générer le QR Code
    function generateQRCode() {
        const container = document.getElementById('qr-code-container');
        const downloadBtn = document.getElementById('download-btn');
        const urlDiv = document.getElementById('qr-code-url');
        const urlLink = document.getElementById('reservation-url');
        
        // Afficher un indicateur de chargement
        container.innerHTML = '<div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600 mx-auto mb-2"></div><p class="text-sm text-gray-600">Génération du QR Code...</p>';
        
        // Appel API pour générer le QR Code
        fetch(`{{ route('charging-points.enhanced.generate-qr', $chargingPoint->id) }}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
        .then(response => response.json())
    .then(data => {
        if (data.success) {
                // Afficher le QR Code
                container.innerHTML = `
                    <img src="${data.qr_code_url}" alt="QR Code" class="mx-auto max-w-xs border rounded-lg shadow-sm">
                    <p class="text-sm text-green-600 mt-2">QR Code généré avec succès</p>
                `;
                
                // Afficher le bouton de téléchargement
                downloadBtn.classList.remove('hidden');
                downloadBtn.onclick = () => window.open(data.download_url, '_blank');
                
                // Afficher l'URL de réservation
                urlDiv.classList.remove('hidden');
                urlLink.href = data.qr_code_url;
                urlLink.textContent = data.qr_code_url;
        } else {
                container.innerHTML = `<p class="text-red-600">Erreur: ${data.message}</p>`;
        }
    })
    .catch(error => {
            container.innerHTML = `<p class="text-red-600">Erreur: ${error.message}</p>`;
        });
    }

    // Fonction pour télécharger le QR Code
    function downloadQRCode() {
        const downloadBtn = document.getElementById('download-btn');
        if (downloadBtn.onclick) {
            downloadBtn.onclick();
        }
    }

    // Fonction pour afficher/masquer l'estimation
    function toggleEstimation() {
        const estimationSection = document.getElementById('estimation-section');
        if (!estimationSection) {
            // Créer la section estimation si elle n'existe pas
            createEstimationSection();
        } else {
            // Basculer l'affichage
            estimationSection.style.display = estimationSection.style.display === 'none' ? 'block' : 'none';
        }
    }

    // Fonction pour créer la section estimation
    function createEstimationSection() {
        const container = document.querySelector('.w-full.lg\\:w-3\\/4.xl\\:w-4\\/5');
        const estimationDiv = document.createElement('div');
        estimationDiv.id = 'estimation-section';
        estimationDiv.className = 'bg-white shadow rounded-lg p-6 mb-6';
        estimationDiv.innerHTML = `
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Estimation de Coût de Recharge</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Type de calcul</label>
                    <select id="calculation-type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        <option value="duration">Par durée</option>
                        <option value="energy">Par énergie</option>
                        <option value="target">Par charge cible</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Durée (minutes)</label>
                    <input type="number" id="duration" value="60" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Énergie (kWh)</label>
                    <input type="number" id="energy" value="22" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Charge cible (%)</label>
                    <input type="number" id="target-charge" value="80" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
            </div>
            <div class="mt-6">
                <button onclick="calculateEstimation()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-calculator mr-2"></i>Calculer l'estimation
                </button>
            </div>
            <div id="estimation-result" class="mt-6 hidden">
                <div class="bg-green-50 rounded-lg p-4">
                    <h3 class="font-semibold text-green-900 mb-2">Résultat de l'estimation</h3>
                    <div id="estimation-details"></div>
                </div>
            </div>
        `;
        container.appendChild(estimationDiv);
    }

    // Fonction pour calculer l'estimation
    function calculateEstimation() {
        const calculationType = document.getElementById('calculation-type').value;
        const duration = document.getElementById('duration').value;
        const energy = document.getElementById('energy').value;
        const targetCharge = document.getElementById('target-charge').value;
        
        const resultDiv = document.getElementById('estimation-result');
        const detailsDiv = document.getElementById('estimation-details');
        
        // Afficher un indicateur de chargement
        detailsDiv.innerHTML = '<div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600 mx-auto"></div>';
        resultDiv.classList.remove('hidden');
        
        // Appel API pour calculer l'estimation
        fetch(`{{ route('charging-points.enhanced.calculate-estimation', $chargingPoint->id) }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                calculation_type: calculationType,
                duration: duration,
                energy: energy,
                target_charge: targetCharge
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                detailsDiv.innerHTML = `
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div>
                            <p class="text-gray-600">Énergie estimée:</p>
                            <p class="font-semibold text-green-600">${data.estimated_energy} kWh</p>
                        </div>
                        <div>
                            <p class="text-gray-600">Durée estimée:</p>
                            <p class="font-semibold text-blue-600">${data.estimated_duration} min</p>
                        </div>
                        <div>
                            <p class="text-gray-600">Coût total:</p>
                            <p class="font-semibold text-purple-600">€${data.total_cost}</p>
                        </div>
                    </div>
                    <div class="mt-4 text-xs text-gray-500">
                        <p>Détail: Activation (€${data.activation_fee}) + Énergie (€${data.energy_cost}) + Temps (€${data.time_cost}) + TVA (€${data.tax_cost})</p>
                    </div>
                `;
            } else {
                detailsDiv.innerHTML = `<p class="text-red-600">Erreur: ${data.message}</p>`;
            }
        })
        .catch(error => {
            detailsDiv.innerHTML = `<p class="text-red-600">Erreur: ${error.message}</p>`;
        });
    }

    // Fonction pour afficher/masquer les actions SteVe
    function toggleSteVeActions() {
        alert('Fonctionnalité Actions SteVe - En cours de développement');
    }

    // Fonction pour se connecter à SteVe
    function connectToSteVe() {
        const steveId = document.getElementById('steve-id').value;
        const statusIndicator = document.getElementById('connection-status');
        const statusText = document.getElementById('connection-text');
        
        statusIndicator.className = 'w-3 h-3 bg-yellow-400 rounded-full mr-3 animate-pulse';
        statusText.textContent = 'Connexion en cours...';
        
        // Appel API réel
        fetch(`{{ route('charging-points.connect-steve', $chargingPoint->id) }}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                steve_id: steveId
            })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
                statusIndicator.className = 'w-3 h-3 bg-green-400 rounded-full mr-3';
                statusText.textContent = 'Connecté';
                alert(data.message);
        } else {
                statusIndicator.className = 'w-3 h-3 bg-red-400 rounded-full mr-3';
                statusText.textContent = 'Erreur';
                alert('Erreur de connexion');
        }
    })
    .catch(error => {
            statusIndicator.className = 'w-3 h-3 bg-red-400 rounded-full mr-3';
            statusText.textContent = 'Erreur';
            alert('Erreur de connexion: ' + error.message);
        });
    }

    // Fonction pour tester la connectivité
    function testConnectivity() {
        const statusIndicator = document.getElementById('connection-status');
        const statusText = document.getElementById('connection-text');
        
        statusIndicator.className = 'w-3 h-3 bg-yellow-400 rounded-full mr-3 animate-pulse';
        statusText.textContent = 'Test en cours...';
        
        // Appel API réel
        fetch(`{{ route('charging-points.test-connectivity-enhanced', $chargingPoint->id) }}`, {
        method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
                statusIndicator.className = 'w-3 h-3 bg-green-400 rounded-full mr-3';
                statusText.textContent = 'Connecté';
                alert(data.message + ' (Temps de réponse: ' + data.response_time + ')');
        } else {
                statusIndicator.className = 'w-3 h-3 bg-red-400 rounded-full mr-3';
                statusText.textContent = 'Erreur';
                alert('Test de connectivité échoué');
        }
    })
    .catch(error => {
            statusIndicator.className = 'w-3 h-3 bg-red-400 rounded-full mr-3';
            statusText.textContent = 'Erreur';
            alert('Erreur de test: ' + error.message);
        });
    }

    // Fonction pour exécuter les actions SteVe
    function executeSteVeAction(action) {
        // Appel API réel
        fetch(`{{ route('charging-points.execute-steve-action', $chargingPoint->id) }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                action: action
            })
        })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
                alert(data.message + ' (Timestamp: ' + data.timestamp + ')');
        } else {
                alert('Erreur lors de l\'exécution de l\'action');
        }
    })
    .catch(error => {
            alert('Erreur: ' + error.message);
        });
    }
</script>

@endsection