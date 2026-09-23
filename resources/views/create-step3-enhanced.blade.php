@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-emerald-50/30 dark:from-gray-900 dark:via-gray-900 dark:to-emerald-900/10">
    @include('charging-points.partials._creation-header', [
        'title' => 'Nouveau point de charge',
        'currentStep' => 3,
        'totalSteps' => 4
    ])

    <main class="max-w-7xl mx-auto px-4 py-8">
        @include('charging-points.partials._creation-stepper', ['currentStep' => 3])

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Main Form - Left Side (75%) -->
            <div class="lg:col-span-9">
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-xl shadow-gray-900/5">
                    <!-- Form Header -->
                    <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-800">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center">
                                <i class="fas fa-wifi text-white"></i>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Connectivité et réseau</h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Configurez les paramètres de connexion et de communication</p>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('charging-points.create.store.step3') }}" method="POST" id="step3Form" class="p-6 space-y-8">
                        @csrf

                        <!-- Section Connexion Réseau -->
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/10 dark:to-indigo-900/10 rounded-xl p-6 border border-blue-200/50 dark:border-blue-800/50">
                            <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-4 flex items-center gap-2">
                                <i class="fas fa-network-wired text-blue-500"></i>
                                Connexion réseau
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="connection_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Type de connexion <span class="text-red-500">*</span>
                                    </label>
                                    <select id="connection_type" name="connection_type" 
                                            class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors" 
                                            required onchange="updateConnectionInfo(this.value)">
                                        <option value="">Sélectionner un type</option>
                                        <option value="ethernet" {{ old('connection_type', session('charging_point_step3.connection_type', 'ethernet')) == 'ethernet' ? 'selected' : '' }}>Ethernet (RJ45)</option>
                                        <option value="wifi" {{ old('connection_type', session('charging_point_step3.connection_type')) == 'wifi' ? 'selected' : '' }}>Wi-Fi</option>
                                        <option value="gsm" {{ old('connection_type', session('charging_point_step3.connection_type')) == 'gsm' ? 'selected' : '' }}>GSM/4G/5G</option>
                                        <option value="other" {{ old('connection_type', session('charging_point_step3.connection_type')) == 'other' ? 'selected' : '' }}>Autre</option>
                                    </select>
                                    <div id="connection-info" class="mt-2 text-xs text-gray-500 dark:text-gray-400"></div>
                                    @error('connection_type')
                                        <p class="mt-1 text-sm text-red-600 flex items-center gap-1">
                                            <i class="fas fa-exclamation-circle"></i>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="ip_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Adresse IP
                                    </label>
                                    <input type="text" name="ip_address" id="ip_address" 
                                           class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors" 
                                           value="{{ old('ip_address', session('charging_point_step3.ip_address')) }}"
                                           placeholder="192.168.1.100">
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Laissez vide pour DHCP automatique</p>
                                    @error('ip_address')
                                        <p class="mt-1 text-sm text-red-600 flex items-center gap-1">
                                            <i class="fas fa-exclamation-circle"></i>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="mac_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Adresse MAC
                                    </label>
                                    <input type="text" name="mac_address" id="mac_address" 
                                           class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors" 
                                           value="{{ old('mac_address', session('charging_point_step3.mac_address')) }}"
                                           placeholder="00:1A:2B:3C:4D:5E">
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Format: XX:XX:XX:XX:XX:XX</p>
                                    @error('mac_address')
                                        <p class="mt-1 text-sm text-red-600 flex items-center gap-1">
                                            <i class="fas fa-exclamation-circle"></i>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="server_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        URL du serveur
                                    </label>
                                    <input type="url" name="server_url" id="server_url" 
                                           class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors" 
                                           value="{{ old('server_url', session('charging_point_step3.server_url')) }}"
                                           placeholder="https://server.example.com/ocpp">
                                    @error('server_url')
                                        <p class="mt-1 text-sm text-red-600 flex items-center gap-1">
                                            <i class="fas fa-exclamation-circle"></i>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Section Protocole de Communication -->
                        <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/10 dark:to-pink-900/10 rounded-xl p-6 border border-purple-200/50 dark:border-purple-800/50">
                            <h3 class="text-lg font-semibold text-purple-900 dark:text-purple-100 mb-4 flex items-center gap-2">
                                <i class="fas fa-comments text-purple-500"></i>
                                Protocole de communication
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="communication_protocol" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Protocole <span class="text-red-500">*</span>
                                    </label>
                                    <select id="communication_protocol" name="communication_protocol" 
                                            class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors" 
                                            required onchange="updateProtocolInfo(this.value)">
                                        <option value="">Sélectionner un protocole</option>
                                        <option value="ocpp16" {{ old('communication_protocol', session('charging_point_step3.communication_protocol', 'ocpp16')) == 'ocpp16' ? 'selected' : '' }}>OCPP 1.6</option>
                                        <option value="ocpp20" {{ old('communication_protocol', session('charging_point_step3.communication_protocol')) == 'ocpp20' ? 'selected' : '' }}>OCPP 2.0</option>
                                        <option value="ocpp201" {{ old('communication_protocol', session('charging_point_step3.communication_protocol')) == 'ocpp201' ? 'selected' : '' }}>OCPP 2.0.1</option>
                                        <option value="proprietary" {{ old('communication_protocol', session('charging_point_step3.communication_protocol')) == 'proprietary' ? 'selected' : '' }}>Propriétaire</option>
                                        <option value="other" {{ old('communication_protocol', session('charging_point_step3.communication_protocol')) == 'other' ? 'selected' : '' }}>Autre</option>
                                    </select>
                                    <div id="protocol-info" class="mt-2 text-xs text-gray-500 dark:text-gray-400"></div>
                                    @error('communication_protocol')
                                        <p class="mt-1 text-sm text-red-600 flex items-center gap-1">
                                            <i class="fas fa-exclamation-circle"></i>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="firmware_version" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Version du firmware
                                    </label>
                                    <input type="text" name="firmware_version" id="firmware_version" 
                                           class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors" 
                                           value="{{ old('firmware_version', session('charging_point_step3.firmware_version')) }}"
                                           placeholder="v1.2.3">
                                    @error('firmware_version')
                                        <p class="mt-1 text-sm text-red-600 flex items-center gap-1">
                                            <i class="fas fa-exclamation-circle"></i>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Section Sécurité et Accès -->
                        <div class="bg-gradient-to-r from-orange-50 to-red-50 dark:from-orange-900/10 dark:to-red-900/10 rounded-xl p-6 border border-orange-200/50 dark:border-orange-800/50">
                            <h3 class="text-lg font-semibold text-orange-900 dark:text-orange-100 mb-4 flex items-center gap-2">
                                <i class="fas fa-shield-alt text-orange-500"></i>
                                Sécurité et accès
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="access_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Type d'accès
                                    </label>
                                    <select id="access_type" name="access_type" 
                                            class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors" 
                                            onchange="toggleAccessCode(this.value)">
                                        <option value="public" {{ old('access_type', session('charging_point_step3.access_type', 'public')) == 'public' ? 'selected' : '' }}>Public</option>
                                        <option value="private" {{ old('access_type', session('charging_point_step3.access_type')) == 'private' ? 'selected' : '' }}>Privé</option>
                                        <option value="restricted" {{ old('access_type', session('charging_point_step3.access_type')) == 'restricted' ? 'selected' : '' }}>Accès restreint</option>
                                    </select>
                                    @error('access_type')
                                        <p class="mt-1 text-sm text-red-600 flex items-center gap-1">
                                            <i class="fas fa-exclamation-circle"></i>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>

                                <div id="access_code_container" class="hidden">
                                    <label for="access_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Code d'accès
                                    </label>
                                    <input type="text" name="access_code" id="access_code" 
                                           class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors" 
                                           value="{{ old('access_code', session('charging_point_step3.access_code')) }}"
                                           placeholder="Code PIN ou mot de passe">
                                    @error('access_code')
                                        <p class="mt-1 text-sm text-red-600 flex items-center gap-1">
                                            <i class="fas fa-exclamation-circle"></i>
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>

                                <div class="flex items-center">
                                    <input type="checkbox" id="authentication_required" name="authentication_required" value="1" 
                                           class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-gray-300 rounded"
                                           {{ old('authentication_required', session('charging_point_step3.authentication_required')) ? 'checked' : '' }}>
                                    <label for="authentication_required" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                        Authentification requise
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Navigation Buttons -->
                        <div class="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-gray-700">
                            <a href="{{ route('charging-points.create.step2') }}" 
                               class="inline-flex items-center gap-2 px-6 py-3 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-semibold rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-all duration-200">
                                <i class="fas fa-arrow-left"></i>
                                Précédent
                            </a>
                            
                            <button type="submit" 
                                    class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white font-semibold rounded-xl shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/40 transition-all duration-200 transform hover:scale-105">
                                Continuer
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sidebar - Right Side (25%) -->
            <div class="lg:col-span-3 order-first lg:order-last">
                <!-- Récapitulatif connectivité -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-xl shadow-gray-900/5 p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                        <i class="fas fa-clipboard-list text-emerald-500"></i>
                        Récapitulatif
                    </h3>
                    <div class="space-y-4" id="connectivity-summary">
                        <div class="text-sm">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-gray-600 dark:text-gray-400">Étape</span>
                                <span class="font-semibold text-emerald-600 dark:text-emerald-400">3 / 4</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mb-3">
                                <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 h-2 rounded-full transition-all duration-500" style="width: 75%"></div>
                            </div>
                        </div>
                        
                        <!-- Étapes précédentes -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                            <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2 text-xs uppercase tracking-wide">Étapes précédentes</h4>
                            <div class="space-y-1 text-xs text-gray-600 dark:text-gray-400">
                                <div>{{ session('charging_point_step1.name', 'Nom non défini') }}</div>
                                <div>{{ session('charging_point_step2.power_output', 'Puissance non définie') }} kW</div>
                            </div>
                        </div>
                        
                        <!-- Étape 3 - Configuration actuelle -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                            <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-3 text-xs uppercase tracking-wide">Étape 3 - Connectivité</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Connexion :</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100" id="summary-connection">-</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Protocole :</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100" id="summary-protocol">-</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">IP :</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100" id="summary-ip">-</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Accès :</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100" id="summary-access">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Help Card -->
                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-2xl border border-blue-200/50 dark:border-blue-800/50 p-6">
                    <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-3 flex items-center gap-2">
                        <i class="fas fa-question-circle text-blue-500"></i>
                        Aide connectivité
                    </h3>
                    <div class="space-y-3 text-sm text-blue-800 dark:text-blue-200">
                        <div>
                            <p class="font-medium">OCPP 1.6 vs 2.0 :</p>
                            <p class="text-xs mt-1">OCPP 1.6 est plus stable, OCPP 2.0 offre plus de fonctionnalités.</p>
                        </div>
                        <div>
                            <p class="font-medium">Connexion réseau :</p>
                            <p class="text-xs mt-1">Ethernet recommandé pour la stabilité, Wi-Fi pour la flexibilité.</p>
                        </div>
                        <div>
                            <p class="font-medium">Sécurité :</p>
                            <p class="text-xs mt-1">Activez l'authentification pour les bornes publiques.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
// Update connectivity summary
function updateConnectivitySummary() {
    const connectionType = document.getElementById('connection_type');
    const protocol = document.getElementById('communication_protocol');
    const ipAddress = document.getElementById('ip_address');
    const accessType = document.getElementById('access_type');
    
    const summaryConnection = document.getElementById('summary-connection');
    const summaryProtocol = document.getElementById('summary-protocol');
    const summaryIp = document.getElementById('summary-ip');
    const summaryAccess = document.getElementById('summary-access');
    
    if (summaryConnection) summaryConnection.textContent = connectionType?.value || '-';
    if (summaryProtocol) summaryProtocol.textContent = protocol?.value || '-';
    if (summaryIp) summaryIp.textContent = ipAddress?.value || 'DHCP';
    if (summaryAccess) summaryAccess.textContent = accessType?.value || '-';
}

// Update connection info
function updateConnectionInfo(type) {
    const info = document.getElementById('connection-info');
    const descriptions = {
        'ethernet': 'Connexion filaire stable et rapide',
        'wifi': 'Connexion sans fil, nécessite configuration réseau',
        'gsm': 'Connexion mobile, nécessite carte SIM',
        'other': 'Autre type de connexion'
    };
    
    info.innerHTML = descriptions[type] ? `<i class="fas fa-info-circle text-blue-500"></i> ${descriptions[type]}` : '';
    updateConnectivitySummary();
}

// Update protocol info
function updateProtocolInfo(protocol) {
    const info = document.getElementById('protocol-info');
    const descriptions = {
        'ocpp16': 'Standard OCPP 1.6 - Stable et largement supporté',
        'ocpp20': 'Standard OCPP 2.0 - Nouvelles fonctionnalités',
        'ocpp201': 'Standard OCPP 2.0.1 - Version améliorée',
        'proprietary': 'Protocole spécifique au fabricant',
        'other': 'Autre protocole de communication'
    };
    
    info.innerHTML = descriptions[protocol] ? `<i class="fas fa-info-circle text-blue-500"></i> ${descriptions[protocol]}` : '';
    updateConnectivitySummary();
}

// Toggle access code field
function toggleAccessCode(accessType) {
    const accessCodeContainer = document.getElementById('access_code_container');
    if (accessType === 'restricted') {
        accessCodeContainer.classList.remove('hidden');
    } else {
        accessCodeContainer.classList.add('hidden');
    }
    updateConnectivitySummary();
}

// Auto-update summary on input
document.addEventListener('input', function(e) {
    if (e.target.form && e.target.form.id === 'step3Form') {
        updateConnectivitySummary();
    }
});

document.addEventListener('change', function(e) {
    if (e.target.form && e.target.form.id === 'step3Form') {
        updateConnectivitySummary();
    }
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const connectionType = document.getElementById('connection_type');
    const protocol = document.getElementById('communication_protocol');
    const accessType = document.getElementById('access_type');
    
    if (connectionType.value) updateConnectionInfo(connectionType.value);
    if (protocol.value) updateProtocolInfo(protocol.value);
    if (accessType.value) toggleAccessCode(accessType.value);
    
    // Initialize summary
    updateConnectivitySummary();
});
</script>
@endsection
