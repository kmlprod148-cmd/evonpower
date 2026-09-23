@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-emerald-50/30 dark:from-gray-900 dark:via-gray-900 dark:to-emerald-900/10">
    @include('charging-points.partials._creation-header', [
        'title' => 'Nouveau point de charge',
        'currentStep' => 2,
        'totalSteps' => 4
    ])

    <main class="max-w-7xl mx-auto px-4 py-8">
        @include('charging-points.partials._creation-stepper', ['currentStep' => 2])

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Main Form - Left Side (75%) -->
            <div class="lg:col-span-9">
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-xl shadow-gray-900/5">
                    <!-- Form Header -->
                    <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-800">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center">
                                <i class="fas fa-cogs text-white"></i>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Spécifications techniques</h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Configurez les caractéristiques techniques de votre point de charge</p>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('charging-points.create.store.step2') }}" method="POST" id="step2Form" class="p-6 space-y-8">
                        @csrf

                        <!-- Error Display -->
                        @if ($errors->any())
                            <div class="rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4">
                                <div class="flex items-start gap-3">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-exclamation-triangle text-red-500 text-lg"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-sm font-medium text-red-800 dark:text-red-200 mb-2">
                                            Veuillez corriger les erreurs suivantes :
                                        </h3>
                                        <ul class="text-sm text-red-700 dark:text-red-300 space-y-1">
                                            @foreach ($errors->all() as $error)
                                                <li class="flex items-center gap-2">
                                                    <i class="fas fa-dot-circle text-xs"></i>
                                                    {{ $error }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Power Specifications -->
                        <div class="space-y-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2 pb-2 border-b border-gray-200 dark:border-gray-700">
                                <i class="fas fa-bolt text-yellow-500"></i>
                                Caractéristiques électriques
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Power Output -->
                                <div>
                                    <label for="power_output" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                        <i class="fas fa-tachometer-alt text-emerald-500 mr-2"></i>
                                        Puissance de sortie (kW)
                                        <span class="text-red-500 ml-1">*</span>
                                    </label>
                                    <div class="relative">
                                        <input type="number" name="power_output" id="power_output" step="0.1" min="0" max="350"
                                            class="block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 shadow-sm focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/60 transition-all duration-200 pl-4 pr-16 py-3 text-sm"
                                            required
                                            placeholder="22.0"
                                            value="{{ old('power_output', session('charging_point_step2.power_output', '')) }}"
                                            oninput="updatePowerInfo(this.value); updateTechSummary();">
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                            <span class="text-gray-500 text-sm">kW</span>
                                        </div>
                                    </div>
                                    <div id="power-info" class="mt-2 text-xs text-gray-500 dark:text-gray-400"></div>
                                    @error('power_output') 
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-2">
                                            <i class="fas fa-exclamation-circle"></i>
                                            {{ $message }}
                                        </p> 
                                    @enderror
                                </div>

                                <!-- Connector Type - Supprimé car non utilisé -->

                                <!-- Connection Type -->
                                <div>
                                    <label for="connection_type" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                        <i class="fas fa-link text-emerald-500 mr-2"></i>
                                        Type de connexion
                                        <span class="text-red-500 ml-1">*</span>
                                    </label>
                                    <select name="connection_type" id="connection_type"
                                        class="block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 shadow-sm focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/60 transition-all duration-200 px-4 py-3 text-sm"
                                        required onchange="updateConnectionInfo(this.value); updateTechSummary();">
                                        <option value="">Sélectionner un type</option>
                                        <option value="Ethernet" {{ old('connection_type', session('charging_point_step2.connection_type', '')) == 'Ethernet' ? 'selected' : '' }}>Ethernet (Câble)</option>
                                        <option value="WiFi" {{ old('connection_type', session('charging_point_step2.connection_type', '')) == 'WiFi' ? 'selected' : '' }}>WiFi</option>
                                        <option value="4G/LTE" {{ old('connection_type', session('charging_point_step2.connection_type', '')) == '4G/LTE' ? 'selected' : '' }}>4G/LTE</option>
                                        <option value="5G" {{ old('connection_type', session('charging_point_step2.connection_type', '')) == '5G' ? 'selected' : '' }}>5G</option>
                                    </select>
                                    @error('connection_type') 
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-2">
                                            <i class="fas fa-exclamation-circle"></i>
                                            {{ $message }}
                                        </p> 
                                    @enderror
                                </div>

                                <!-- Communication Protocol -->
                                <div>
                                    <label for="communication_protocol" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                        <i class="fas fa-comments text-emerald-500 mr-2"></i>
                                        Protocole de communication
                                        <span class="text-red-500 ml-1">*</span>
                                    </label>
                                    <select name="communication_protocol" id="communication_protocol"
                                        class="block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 shadow-sm focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/60 transition-all duration-200 px-4 py-3 text-sm"
                                        required onchange="updateProtocolInfo(this.value); updateTechSummary();">
                                        <option value="">Sélectionner un protocole</option>
                                        <option value="OCPP 1.6" {{ old('communication_protocol', session('charging_point_step2.communication_protocol', '')) == 'OCPP 1.6' ? 'selected' : '' }}>OCPP 1.6</option>
                                        <option value="OCPP 2.0" {{ old('communication_protocol', session('charging_point_step2.communication_protocol', '')) == 'OCPP 2.0' ? 'selected' : '' }}>OCPP 2.0</option>
                                        <option value="OCPP 2.0.1" {{ old('communication_protocol', session('charging_point_step2.communication_protocol', '')) == 'OCPP 2.0.1' ? 'selected' : '' }}>OCPP 2.0.1</option>
                                        <option value="Propriétaire" {{ old('communication_protocol', session('charging_point_step2.communication_protocol', '')) == 'Propriétaire' ? 'selected' : '' }}>Protocole propriétaire</option>
                                    </select>
                                    <div id="protocol-info" class="mt-2 text-xs text-gray-500 dark:text-gray-400"></div>
                                    @error('communication_protocol') 
                                        <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-2">
                                            <i class="fas fa-exclamation-circle"></i>
                                            {{ $message }}
                                        </p> 
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Operator and Business Profile Selection -->
                        <div class="space-y-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2 pb-2 border-b border-gray-200 dark:border-gray-700">
                                <i class="fas fa-users text-blue-500"></i>
                                Opérateur et Business Profile
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Operator Selection - Supprimé car non nécessaire -->

                                <!-- Business Profile - Automatiquement assigné selon le créateur -->
                                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                                    <div class="flex items-center">
                                        <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                                        <p class="text-sm text-blue-700 dark:text-blue-300">
                                            Le business profile sera automatiquement assigné selon votre rôle.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Connectors Configuration - Supprimé car non utilisé -->

                        <!-- Pricing Plan Selection -->
                        <div class="space-y-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2 pb-2 border-b border-gray-200 dark:border-gray-700">
                                <i class="fas fa-euro-sign text-green-500"></i>
                                Plan tarifaire
                            </h3>

                            <div>
                                <label for="pricing_plan_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                                    <i class="fas fa-tags text-emerald-500 mr-2"></i>
                                    Sélectionner un plan tarifaire
                                </label>
                                
                                @if($pricingPlans && $pricingPlans->count() > 0)
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        @foreach($pricingPlans as $plan)
                                            <div class="relative">
                                                <input type="radio" name="pricing_plan_id" id="plan_{{ $plan->id }}" value="{{ $plan->id }}"
                                                    class="peer"
                                                    {{ old('pricing_plan_id', session('charging_point_step2.pricing_plan_id', '')) == $plan->id ? 'checked' : '' }}>
                                                <label for="plan_{{ $plan->id }}" 
                                                    class="block p-4 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:border-emerald-300 dark:hover:border-emerald-600 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 dark:peer-checked:bg-emerald-900/20 cursor-pointer transition-all duration-200">
                                                    <div class="flex items-start justify-between">
                                                        <div class="flex-1">
                                                            <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">{{ $plan->name }}</h4>
                                                            @if($plan->description)
                                                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ Str::limit($plan->description, 60) }}</p>
                                                            @endif
                                                            <div class="space-y-1 text-xs text-gray-500 dark:text-gray-400">
                                                                @if($plan->price_per_kwh)
                                                                    <div class="flex items-center gap-1">
                                                                        <i class="fas fa-bolt text-yellow-500"></i>
                                                                        {{ number_format($plan->price_per_kwh, 2) }} €/kWh
                                                                    </div>
                                                                @endif
                                                                @if($plan->price_per_minute)
                                                                    <div class="flex items-center gap-1">
                                                                        <i class="fas fa-clock text-blue-500"></i>
                                                                        {{ number_format($plan->price_per_minute, 2) }} €/min
                                                                    </div>
                                                                @endif
                                                                @if($plan->activation_fee)
                                                                    <div class="flex items-center gap-1">
                                                                        <i class="fas fa-play text-green-500"></i>
                                                                        {{ number_format($plan->activation_fee, 2) }} € activation
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div class="ml-3">
                                                            <div class="w-5 h-5 rounded-full border-2 border-gray-300 peer-checked:border-emerald-500 peer-checked:bg-emerald-500 flex items-center justify-center">
                                                                <i class="fas fa-check text-white text-xs opacity-0 peer-checked:opacity-100"></i>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-center py-8 bg-gray-50 dark:bg-gray-800 rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-600">
                                        <i class="fas fa-tags text-gray-400 text-3xl mb-3"></i>
                                        <p class="text-gray-600 dark:text-gray-400 mb-2">Aucun plan tarifaire disponible</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-500">Vous pourrez en assigner un plus tard</p>
                                    </div>
                                @endif
                                
                                @error('pricing_plan_id') 
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-2">
                                        <i class="fas fa-exclamation-circle"></i>
                                        {{ $message }}
                                    </p> 
                                @enderror
                            </div>
                        </div>

                        <!-- Access Configuration -->
                        <div class="space-y-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2 pb-2 border-b border-gray-200 dark:border-gray-700">
                                <i class="fas fa-shield-alt text-blue-500"></i>
                                Configuration d'accès
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Access Type -->
                                <div>
                                    <label for="access_type" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                        <i class="fas fa-key text-emerald-500 mr-2"></i>
                                        Type d'accès
                                    </label>
                                    <select name="access_type" id="access_type"
                                        class="block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 shadow-sm focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/60 transition-all duration-200 px-4 py-3 text-sm"
                                        onchange="toggleAccessCode(this.value)">
                                        <option value="public" {{ old('access_type', session('charging_point_step2.access_type', 'public')) == 'public' ? 'selected' : '' }}>Public</option>
                                        <option value="private" {{ old('access_type', session('charging_point_step2.access_type', '')) == 'private' ? 'selected' : '' }}>Privé</option>
                                        <option value="restricted" {{ old('access_type', session('charging_point_step2.access_type', '')) == 'restricted' ? 'selected' : '' }}>Accès restreint</option>
                                    </select>
                                </div>

                                <!-- Authentication Required -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                                        <i class="fas fa-user-check text-emerald-500 mr-2"></i>
                                        Authentification requise
                                    </label>
                                    <div class="flex items-center gap-4">
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="radio" name="authentication_required" value="1" 
                                                class="text-emerald-500 focus:ring-emerald-500"
                                                {{ old('authentication_required', session('charging_point_step2.authentication_required', '0')) == '1' ? 'checked' : '' }}>
                                            <span class="text-sm text-gray-700 dark:text-gray-300">Oui</span>
                                        </label>
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="radio" name="authentication_required" value="0" 
                                                class="text-emerald-500 focus:ring-emerald-500"
                                                {{ old('authentication_required', session('charging_point_step2.authentication_required', '0')) == '0' ? 'checked' : '' }}>
                                            <span class="text-sm text-gray-700 dark:text-gray-300">Non</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-gray-700">
                            <a href="{{ route('charging-points.create.step1') }}" 
                               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition-all duration-200">
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
                <!-- Récapitulatif technique -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-xl shadow-gray-900/5 p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                        <i class="fas fa-clipboard-list text-emerald-500"></i>
                        Récapitulatif
                    </h3>
                    <div class="space-y-4" id="tech-summary">
                        <div class="text-sm">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-gray-600 dark:text-gray-400">Étape</span>
                                <span class="font-semibold text-emerald-600 dark:text-emerald-400">2 / 4</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mb-3">
                                <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 h-2 rounded-full transition-all duration-500" style="width: 50%"></div>
                            </div>
                        </div>
                        
                        <!-- Étape 1 - Récapitulatif -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                            <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2 text-xs uppercase tracking-wide">Étape 1 - Général</h4>
                            <div class="space-y-1 text-xs text-gray-600 dark:text-gray-400">
                                <div>{{ session('charging_point_step1.name', 'Nom non défini') }}</div>
                                <div>{{ session('charging_point_step1.manufacturer', 'Fabricant non défini') }}</div>
                            </div>
                        </div>
                        
                        <!-- Étape 2 - Configuration actuelle -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                            <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-3 text-xs uppercase tracking-wide">Étape 2 - Technique</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Puissance :</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100" id="summary-power">-</span>
                                </div>
                                <!-- Connecteur supprimé -->
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Connexion :</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100" id="summary-connection">-</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Protocole :</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100" id="summary-protocol">-</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Opérateur :</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100" id="summary-operator">-</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Business Profile :</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100" id="summary-business-profile">-</span>
                                </div>
                                <!-- Connecteurs supprimés -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Technical Info Card -->
                <div class="bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-2xl border border-purple-200/50 dark:border-purple-800/50 p-6">
                    <h3 class="text-lg font-semibold text-purple-900 dark:text-purple-100 mb-3 flex items-center gap-2">
                        <i class="fas fa-info-circle text-purple-500"></i>
                        Informations techniques
                    </h3>
                    <div class="space-y-3 text-sm text-purple-800 dark:text-purple-200">
                        <div class="flex items-start gap-2">
                            <i class="fas fa-bolt text-purple-500 mt-0.5"></i>
                            <p><strong>AC vs DC:</strong> AC pour recharge lente/normale, DC pour recharge rapide.</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <i class="fas fa-plug text-purple-500 mt-0.5"></i>
                            <p><strong>Type 2:</strong> Standard européen pour AC (jusqu'à 43kW).</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <i class="fas fa-tachometer-alt text-purple-500 mt-0.5"></i>
                            <p><strong>CCS:</strong> Recharge rapide DC (jusqu'à 350kW).</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
// Power output information
function updatePowerInfo(power) {
    const info = document.getElementById('power-info');
    if (!power) {
        info.textContent = '';
        return;
    }
    
    const powerNum = parseFloat(power);
    let category = '';
    let time = '';
    
    if (powerNum <= 3.7) {
        category = 'Recharge lente';
        time = '6-8h pour 100km';
    } else if (powerNum <= 22) {
        category = 'Recharge normale';
        time = '2-4h pour 100km';
    } else if (powerNum <= 50) {
        category = 'Recharge semi-rapide';
        time = '30-60min pour 100km';
    } else {
        category = 'Recharge rapide';
        time = '10-30min pour 100km';
    }
    
    info.innerHTML = `<i class="fas fa-info-circle text-blue-500"></i> ${category} - ${time}`;
}

// Connector type information - Supprimé car non utilisé

// Protocol information
function updateProtocolInfo(protocol) {
    const info = document.getElementById('protocol-info');
    const descriptions = {
        'OCPP 1.6': 'Standard actuel - Compatible avec la plupart des systèmes',
        'OCPP 2.0': 'Nouvelle génération - Fonctionnalités avancées',
        'OCPP 2.0.1': 'Version la plus récente - Recommandée pour nouveaux projets',
        'Propriétaire': 'Protocole spécifique au fabricant'
    };
    
    info.innerHTML = descriptions[protocol] ? `<i class="fas fa-info-circle text-blue-500"></i> ${descriptions[protocol]}` : '';
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
    
    if (info) {
        info.innerHTML = descriptions[type] ? `<i class="fas fa-info-circle text-blue-500"></i> ${descriptions[type]}` : '';
    }
    updateTechSummary();
}

// Toggle access code field
function toggleAccessCode(accessType) {
    // This would show/hide access code field if it existed
    console.log('Access type changed to:', accessType);
}

// Update technical summary
function updateTechSummary() {
    const powerOutput = document.getElementById('power_output');
    // const connectorType supprimé
    const connectionType = document.getElementById('connection_type');
    const protocol = document.getElementById('communication_protocol');
    const operatorSelect = document.getElementById('operator_id');
    const businessProfileSelect = document.getElementById('business_profile_id');
    
    const summaryPower = document.getElementById('summary-power');
    // const summaryConnector supprimé
    const summaryConnection = document.getElementById('summary-connection');
    const summaryProtocol = document.getElementById('summary-protocol');
    const summaryOperator = document.getElementById('summary-operator');
    const summaryBusinessProfile = document.getElementById('summary-business-profile');
    // const summaryConnectors supprimé
    
    if (summaryPower) {
        const power = powerOutput?.value || '-';
        summaryPower.textContent = power !== '-' ? power + ' kW' : '-';
    }
    
    // if summaryConnector supprimé
    
    if (summaryConnection) {
        summaryConnection.textContent = connectionType?.value || '-';
    }
    
    if (summaryProtocol) {
        summaryProtocol.textContent = protocol?.value || '-';
    }
    
    if (summaryOperator && operatorSelect) {
        const selectedOption = operatorSelect.options[operatorSelect.selectedIndex];
        summaryOperator.textContent = selectedOption?.text || '-';
    }
    
    if (summaryBusinessProfile && businessProfileSelect) {
        const selectedOption = businessProfileSelect.options[businessProfileSelect.selectedIndex];
        summaryBusinessProfile.textContent = selectedOption?.text || '-';
    }
    
    // if summaryConnectors supprimé connecteur(s)` : '-';
    }
}

// Auto-update summary on input
document.addEventListener('input', function(e) {
    if (e.target.form && e.target.form.id === 'step2Form') {
        updateTechSummary();
    }
});

document.addEventListener('change', function(e) {
    if (e.target.form && e.target.form.id === 'step2Form') {
        updateTechSummary();
    }
});

// Connector management supprimé

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const powerOutput = document.getElementById('power_output');
    // const connectorType supprimé
    const connectionType = document.getElementById('connection_type');
    const protocol = document.getElementById('communication_protocol');
    
    if (powerOutput && powerOutput.value) updatePowerInfo(powerOutput.value);
    // if connectorType supprimé
    if (connectionType && connectionType.value) updateConnectionInfo(connectionType.value);
    if (protocol && protocol.value) updateProtocolInfo(protocol.value);
    
    // Initialize summary
    updateTechSummary();
    
    // Add connector functionality supprimé
    
    // Force update summary after a short delay to ensure all elements are loaded
    setTimeout(updateTechSummary, 100);
});
</script>
@endsection
