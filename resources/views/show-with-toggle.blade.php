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
                        <div class="flex items-center space-x-3">
                            <button onclick="toggleDetailedStats()" class="flex items-center px-3 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                <span id="stats-toggle-text">Afficher détails</span>
                            </button>
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

                    <!-- Statistiques détaillées (masquées par défaut) -->
                    <div id="detailed-stats-content" class="hidden mt-6">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="font-semibold text-gray-900 mb-3">Statistiques Détaillées</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p class="text-gray-600">Dernière session:</p>
                                    <p class="font-medium">{{ $chargingPoint->updated_at ? $chargingPoint->updated_at->format('d/m/Y H:i') : 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-600">Temps moyen de charge:</p>
                                    <p class="font-medium">45 minutes</p>
                                </div>
                                <div>
                                    <p class="text-gray-600">Revenus générés:</p>
                                    <p class="font-medium">€{{ number_format(($chargingPoint->transactions_count ?? 0) * 15.50, 2) }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-600">Efficacité énergétique:</p>
                                    <p class="font-medium">98.5%</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section d'informations détaillées avec affichage/masquage -->
                <div class="bg-white shadow rounded-lg p-6 mb-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900">Informations Détaillées</h2>
                            <p class="text-sm text-gray-600 mt-1">Détails techniques et opérationnels</p>
                        </div>
                        <button onclick="toggleDetailedInfo()" class="flex items-center px-4 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors text-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <span id="detailed-info-toggle-text">Afficher détails</span>
                        </button>
                    </div>

                    <!-- Informations détaillées (masquées par défaut) -->
                    <div id="detailed-info-content" class="hidden space-y-6">
                        <!-- Informations techniques -->
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

                        <!-- Actions rapides -->
                        <div class="bg-green-50 rounded-lg p-4">
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
    // Fonction pour afficher/masquer les statistiques détaillées
    function toggleDetailedStats() {
        const content = document.getElementById('detailed-stats-content');
        const toggleText = document.getElementById('stats-toggle-text');
        
        if (content.classList.contains('hidden')) {
            content.classList.remove('hidden');
            toggleText.textContent = 'Masquer détails';
        } else {
            content.classList.add('hidden');
            toggleText.textContent = 'Afficher détails';
        }
    }

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
        alert('Fonctionnalité QR Code - En cours de développement');
    }

    // Fonction pour afficher/masquer l'estimation
    function toggleEstimation() {
        alert('Fonctionnalité Estimation - En cours de développement');
    }

    // Fonction pour afficher/masquer les actions SteVe
    function toggleSteVeActions() {
        alert('Fonctionnalité Actions SteVe - En cours de développement');
    }
</script>

@endsection
