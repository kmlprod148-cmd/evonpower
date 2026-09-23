@extends('layouts.app')

@section('content')
<div class="bg-gradient-to-b from-gray-50 to-white dark:from-gray-900 dark:to-gray-950 min-h-screen">
    @include('charging-points.partials._creation-header', [
        'title' => 'Spécifications techniques',
        'currentStep' => 2,
        'totalSteps' => 4,
        'backUrl' => route('charging-points.create.step1')
    ])

    <main class="max-w-7xl mx-auto px-4 py-8">
        @include('charging-points.partials._creation-stepper', ['currentStep' => 2])

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm">
                <form action="{{ route('charging-points.store.step2') }}" method="POST" id="step2Form">
                    @csrf
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Spécifications techniques</h2>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Les champs marqués d’un <span class="text-red-500">*</span> sont obligatoires</span>
                        </div>
                        
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <label for="power_output" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Puissance de sortie (kW) <span class="text-red-500">*</span>
                                </label>
                                <select id="power_output" name="power_output" class="mt-1 block w-full rounded-xl border-gray-200 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/60 shadow-sm" required>
                                    <option value="">Sélectionner une puissance</option>
                                    <option value="3.7" {{ old('power_output', session('charging_point_step2.power_output', '')) == '3.7' ? 'selected' : '' }}>3.7 kW</option>
                                    <option value="7.4" {{ old('power_output', session('charging_point_step2.power_output', '')) == '7.4' ? 'selected' : '' }}>7.4 kW</option>
                                    <option value="11" {{ old('power_output', session('charging_point_step2.power_output', '')) == '11' ? 'selected' : '' }}>11 kW</option>
                                    <option value="22" {{ old('power_output', session('charging_point_step2.power_output', '')) == '22' ? 'selected' : '' }}>22 kW</option>
                                    <option value="43" {{ old('power_output', session('charging_point_step2.power_output', '')) == '43' ? 'selected' : '' }}>43 kW</option>
                                    <option value="50" {{ old('power_output', session('charging_point_step2.power_output', '')) == '50' ? 'selected' : '' }}>50 kW</option>
                                    <option value="60" {{ old('power_output', session('charging_point_step2.power_output', '')) == '60' ? 'selected' : '' }}>60 kW</option>
                                    <option value="75" {{ old('power_output', session('charging_point_step2.power_output', '')) == '75' ? 'selected' : '' }}>75 kW</option>
                                    <option value="90" {{ old('power_output', session('charging_point_step2.power_output', '')) == '90' ? 'selected' : '' }}>90 kW</option>
                                    <option value="120" {{ old('power_output', session('charging_point_step2.power_output', '')) == '120' ? 'selected' : '' }}>120 kW</option>
                                    <option value="150" {{ old('power_output', session('charging_point_step2.power_output', '')) == '150' ? 'selected' : '' }}>150 kW</option>
                                    <option value="180" {{ old('power_output', session('charging_point_step2.power_output', '')) == '180' ? 'selected' : '' }}>180 kW</option>
                                    <option value="200" {{ old('power_output', session('charging_point_step2.power_output', '')) == '200' ? 'selected' : '' }}>200 kW</option>
                                    <option value="250" {{ old('power_output', session('charging_point_step2.power_output', '')) == '250' ? 'selected' : '' }}>250 kW</option>
                                    <option value="300" {{ old('power_output', session('charging_point_step2.power_output', '')) == '300' ? 'selected' : '' }}>300 kW</option>
                                    <option value="350" {{ old('power_output', session('charging_point_step2.power_output', '')) == '350' ? 'selected' : '' }}>350 kW</option>
                                    <option value="400" {{ old('power_output', session('charging_point_step2.power_output', '')) == '400' ? 'selected' : '' }}>400 kW</option>
                                    <option value="other" {{ old('power_output', session('charging_point_step2.power_output', '')) == 'other' ? 'selected' : '' }}>Autre (préciser)</option>
                                </select>
                                @error('power_output')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="connector_type" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Type de connecteur <span class="text-red-500">*</span>
                                </label>
                                <select id="connector_type" name="connector_type" class="mt-1 block w-full rounded-xl border-gray-200 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/60 shadow-sm" required>
                                    <option value="type1" {{ old('connector_type', session('charging_point_step2.connector_type', '')) == 'type1' ? 'selected' : '' }}>Type 1</option>
                                    <option value="type2" {{ old('connector_type', session('charging_point_step2.connector_type', 'type2')) == 'type2' ? 'selected' : '' }}>Type 2</option>
                                    <option value="chademo" {{ old('connector_type', session('charging_point_step2.connector_type', '')) == 'chademo' ? 'selected' : '' }}>CHAdeMO</option>
                                    <option value="ccs" {{ old('connector_type', session('charging_point_step2.connector_type', '')) == 'ccs' ? 'selected' : '' }}>CCS</option>
                                    <option value="other" {{ old('connector_type', session('charging_point_step2.connector_type', '')) == 'other' ? 'selected' : '' }}>Autre</option>
                                </select>
                                @error('connector_type')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="connection_type" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Type de connexion <span class="text-red-500">*</span>
                                </label>
                                <select id="connection_type" name="connection_type" class="mt-1 block w-full rounded-xl border-gray-200 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/60 shadow-sm" required>
                                    <option value="ethernet" {{ old('connection_type', session('charging_point_step2.connection_type', 'ethernet')) == 'ethernet' ? 'selected' : '' }}>Ethernet</option>
                                    <option value="wifi" {{ old('connection_type', session('charging_point_step2.connection_type', '')) == 'wifi' ? 'selected' : '' }}>Wi-Fi</option>
                                    <option value="gsm" {{ old('connection_type', session('charging_point_step2.connection_type', '')) == 'gsm' ? 'selected' : '' }}>GSM/4G</option>
                                    <option value="other" {{ old('connection_type', session('charging_point_step2.connection_type', '')) == 'other' ? 'selected' : '' }}>Autre</option>
                                </select>
                                @error('connection_type')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="communication_protocol" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Protocole de communication <span class="text-red-500">*</span>
                                </label>
                                <select id="communication_protocol" name="communication_protocol" class="mt-1 block w-full rounded-xl border-gray-200 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/60 shadow-sm" required>
                                    <option value="ocpp16" {{ old('communication_protocol', session('charging_point_step2.communication_protocol', 'ocpp16')) == 'ocpp16' ? 'selected' : '' }}>OCPP 1.6</option>
                                    <option value="ocpp20" {{ old('communication_protocol', session('charging_point_step2.communication_protocol', '')) == 'ocpp20' ? 'selected' : '' }}>OCPP 2.0</option>
                                    <option value="proprietary" {{ old('communication_protocol', session('charging_point_step2.communication_protocol', '')) == 'proprietary' ? 'selected' : '' }}>Propriétaire</option>
                                    <option value="other" {{ old('communication_protocol', session('charging_point_step2.communication_protocol', '')) == 'other' ? 'selected' : '' }}>Autre</option>
                                </select>
                                @error('communication_protocol')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="installation_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date d'installation</label>
                                <input type="date" name="installation_date" id="installation_date" class="mt-1 block w-full rounded-xl border-gray-200 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/60 shadow-sm" value="{{ old('installation_date', session('charging_point_step2.installation_date', '')) }}">
                                @error('installation_date')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            

                            
                            <div>
                                <label for="accessibility" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type d'accès</label>
                                <select id="accessibility" name="accessibility" class="mt-1 block w-full rounded-xl border-gray-200 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/60 shadow-sm">
                                    <option value="public" {{ old('accessibility', session('charging_point_step2.accessibility', 'public')) == 'public' ? 'selected' : '' }}>Public</option>
                                    <option value="private" {{ old('accessibility', session('charging_point_step2.accessibility', '')) == 'private' ? 'selected' : '' }}>Privé</option>
                                    <option value="restricted" {{ old('accessibility', session('charging_point_step2.accessibility', '')) == 'restricted' ? 'selected' : '' }}>Restreint</option>
                                </select>
                                @error('accessibility')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="pricing_plan_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Plan tarifaire</label>
                                <div class="flex mt-1 items-center space-x-2">
                                    <select id="pricing_plan_id" name="pricing_plan_id" class="block w-full rounded-xl border-gray-200 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/60 shadow-sm">
                                        <option value="">Sélectionner un plan tarifaire</option>
                                        @forelse(($pricingPlans ?? collect()) as $plan)
                                            <option value="{{ $plan->id }}" {{ old('pricing_plan_id', session('charging_point_step2.pricing_plan_id', '')) == $plan->id ? 'selected' : '' }}>
                                                {{ $plan->name }}
                                            </option>
                                        @empty
                                            <option value="">Aucun plan tarifaire actif disponible</option>
                                        @endforelse
                                    </select>
                                    <a href="{{ route('plans.create') }}" target="_blank" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-emerald-700 bg-emerald-100 hover:bg-emerald-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                                        <svg class="-ml-0.5 mr-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                                        </svg>
                                        Nouveau
                                    </a>
                                </div>
                                @error('pricing_plan_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div class="md:col-span-2">
                                <div class="flex items-center">
                                    <input id="authentication_required" name="authentication_required" type="checkbox" class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-gray-300 rounded" {{ old('authentication_required', session('charging_point_step2.authentication_required', '')) ? 'checked' : '' }} value="1">
                                    <label for="authentication_required" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                        Authentification requise
                                    </label>
                                </div>
                                @error('authentication_required')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="px-6 py-4 bg-gray-50/80 dark:bg-gray-900/40 border-t border-gray-200/80 dark:border-gray-800/80 rounded-b-2xl flex items-center justify-between">
                        <a href="{{ route('charging-points.create.step1') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 ring-1 ring-gray-200 dark:ring-gray-700 transition">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                            Précédent
                        </a>
                        
                        <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 shadow-sm transition active:scale-[.99]">
                            Suivant
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M10.293 15.707a1 1 0 010-1.414L12.586 12H4a1 1 0 110-2h8.586l-2.293-2.293a1 1 0 111.414-1.414l4.0 4.0a1 1 0 010 1.414l-4.0 4.0a1 1 0 01-1.414 0z"/></svg>
                        </button>
                    </div>
                </form>
            </div>

            <aside class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm p-6 h-fit sticky top-6">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">Aperçu</h3>
                
                <div class="mb-6 pb-6 border-b border-gray-200 dark:border-gray-800">
                    <h4 class="text-sm font-medium text-emerald-600 dark:text-emerald-400 mb-3">Informations générales</h4>
                    <div class="space-y-3 text-sm">
                        <p><span class="font-medium text-gray-500 dark:text-gray-400">Nom:</span><br> <span class="text-gray-900 dark:text-gray-100">{{ session('charging_point_step1.name') ?? 'Non spécifié' }}</span></p>
                        <p><span class="font-medium text-gray-500 dark:text-gray-400">N° de série:</span><br> <span class="text-gray-900 dark:text-gray-100">{{ session('charging_point_step1.serial_number') ?? 'Non spécifié' }}</span></p>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-2">Spécifications</h4>
                    <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-800/50">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Puissance</p>
                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100" id="preview-power">Non spécifié</p>
                    </div>
                    <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-800/50">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Connecteur</p>
                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100" id="preview-connector">Type 2</p>
                    </div>
                    <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-800/50">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Protocole</p>
                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100" id="preview-protocol">OCPP 1.6</p>
                    </div>
                    <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-800/50">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Authentification</p>
                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100" id="preview-auth">Non requise</p>
                    </div>
                </div>
                
                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-800">
                    <div class="rounded-xl bg-blue-50 dark:bg-blue-900/20 p-4 ring-1 ring-blue-200/70 dark:ring-blue-900/40">
                        <div class="flex gap-3">
                            <svg class="h-5 w-5 text-blue-500 dark:text-blue-300 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10A8 8 0 11 2 10a8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                            <div>
                                <h4 class="text-sm font-medium text-blue-900 dark:text-blue-200">Étape 2 de 4</h4>
                                <p class="mt-1 text-sm text-blue-800/90 dark:text-blue-300/90">Saisissez les détails techniques de la borne.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </main>
</div>

@push('styles')
<style>
    .required:after {
        content: " *";
        color: red;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Aperçu
    const powerInput = document.getElementById('power_output');
    const connectorTypeSelect = document.getElementById('connector_type');
    const protocolSelect = document.getElementById('communication_protocol');
    const installationDateInput = document.getElementById('installation_date');
    const authCheckbox = document.getElementById('authentication_required');
    const previewPower = document.getElementById('preview-power');
    const previewConnector = document.getElementById('preview-connector');
    const previewProtocol = document.getElementById('preview-protocol');
    const previewInstallationDate = document.getElementById('preview-installation-date');
    // Suppression de previewConnectors
    const previewAuth = document.getElementById('preview-auth');
    function updatePreview() {
        previewPower.textContent = powerInput.value ? powerInput.value + ' kW' : 'Non spécifié';
        if (connectorTypeSelect.selectedIndex >= 0) {
            let connectorText = connectorTypeSelect.options[connectorTypeSelect.selectedIndex].text;
            previewConnector.textContent = connectorText;
        } else {
            previewConnector.textContent = 'Non spécifié';
        }
        if (protocolSelect.selectedIndex >= 0) {
            let protocolText = protocolSelect.options[protocolSelect.selectedIndex].text;
            previewProtocol.textContent = protocolText;
        } else {
            previewProtocol.textContent = 'Non spécifié';
        }
        previewInstallationDate.textContent = installationDateInput.value || 'Non spécifié';
        // Suppression du comptage de connecteurs
        // Update authentication requirement
        previewAuth.textContent = authCheckbox.checked ? 'Requise' : 'Non requise';
    }
    powerInput.addEventListener('change', updatePreview);
    connectorTypeSelect.addEventListener('change', updatePreview);
    protocolSelect.addEventListener('change', updatePreview);
    installationDateInput.addEventListener('input', updatePreview);
    authCheckbox.addEventListener('change', updatePreview);
    updatePreview();

    // Gestion suppression du premier connecteur
    document.querySelectorAll('.remove-connector').forEach(btn => {
        btn.addEventListener('click', function() {
            if (document.querySelectorAll('.connector').length > 1) {
                btn.closest('.connector').remove();
                updatePreview();
            }
        });
    });

    // Supprimer la logique de sérialisation des connecteurs et le champ caché connectorsData
});
</script>
@endpush
@endsection