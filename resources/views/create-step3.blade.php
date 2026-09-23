@extends('layouts.app')

@section('content')
<div class="bg-gradient-to-b from-gray-50 to-white dark:from-gray-900 dark:to-gray-950 min-h-screen">
    @include('charging-points.partials._creation-header', [
        'title' => 'Connectivité',
        'currentStep' => 3,
        'totalSteps' => 4,
        'backUrl' => route('charging-points.create.step2')
    ])

    <main class="max-w-7xl mx-auto px-4 py-8">
        @include('charging-points.partials._creation-stepper', ['currentStep' => 3])

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm">
                <form action="{{ route('charging-points.store.step3') }}" method="POST" id="step3Form">
                    @csrf
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Connectivité</h2>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Les champs marqués d’un <span class="text-red-500">*</span> sont obligatoires</span>
                        </div>
                        
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <label for="connection_type" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Type de connexion <span class="text-red-500">*</span>
                                </label>
                                <select id="connection_type" name="connection_type" class="mt-1 block w-full rounded-xl border-gray-200 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/60 shadow-sm" required>
                                    <option value="ethernet" {{ old('connection_type', session('charging_point_step3.connection_type', 'ethernet')) == 'ethernet' ? 'selected' : '' }}>Ethernet</option>
                                    <option value="wifi" {{ old('connection_type', session('charging_point_step3.connection_type', '')) == 'wifi' ? 'selected' : '' }}>Wi-Fi</option>
                                    <option value="gsm" {{ old('connection_type', session('charging_point_step3.connection_type', '')) == 'gsm' ? 'selected' : '' }}>GSM/4G</option>
                                    <option value="other" {{ old('connection_type', session('charging_point_step3.connection_type', '')) == 'other' ? 'selected' : '' }}>Autre</option>
                                </select>
                                @error('connection_type')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="ip_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Adresse IP</label>
                                <input type="text" name="ip_address" id="ip_address" class="mt-1 block w-full rounded-xl border-gray-200 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/60 shadow-sm" value="{{ old('ip_address', session('charging_point_step3.ip_address', '')) }}">
                                @error('ip_address')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="communication_protocol" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Protocole <span class="text-red-500">*</span>
                                </label>
                                <select id="communication_protocol" name="communication_protocol" class="mt-1 block w-full rounded-xl border-gray-200 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/60 shadow-sm" required>
                                    <option value="ocpp16" {{ old('communication_protocol', session('charging_point_step3.communication_protocol', 'ocpp16')) == 'ocpp16' ? 'selected' : '' }}>OCPP 1.6</option>
                                    <option value="ocpp20" {{ old('communication_protocol', session('charging_point_step3.communication_protocol', '')) == 'ocpp20' ? 'selected' : '' }}>OCPP 2.0</option>
                                    <option value="proprietary" {{ old('communication_protocol', session('charging_point_step3.communication_protocol', '')) == 'proprietary' ? 'selected' : '' }}>Propriétaire</option>
                                    <option value="other" {{ old('communication_protocol', session('charging_point_step3.communication_protocol', '')) == 'other' ? 'selected' : '' }}>Autre</option>
                                </select>
                                @error('communication_protocol')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="firmware_version" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Version du firmware</label>
                                <input type="text" name="firmware_version" id="firmware_version" class="mt-1 block w-full rounded-xl border-gray-200 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/60 shadow-sm" value="{{ old('firmware_version', session('charging_point_step3.firmware_version', '')) }}">
                                @error('firmware_version')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="mac_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Adresse MAC</label>
                                <input type="text" name="mac_address" id="mac_address" class="mt-1 block w-full rounded-xl border-gray-200 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/60 shadow-sm" value="{{ old('mac_address', session('charging_point_step3.mac_address', '')) }}">
                                @error('mac_address')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            

                        </div>
                    </div>
                    
                    <div class="px-6 py-4 bg-gray-50/80 dark:bg-gray-900/40 border-t border-gray-200/80 dark:border-gray-800/80 rounded-b-2xl flex items-center justify-between">
                        <a href="{{ route('charging-points.create.step2') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 ring-1 ring-gray-200 dark:ring-gray-700 transition">
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

                <div class="mb-6 pb-6 border-b border-gray-200 dark:border-gray-800">
                    <h4 class="text-sm font-medium text-emerald-600 dark:text-emerald-400 mb-3">Spécifications</h4>
                    <div class="space-y-3 text-sm">
                        <p><span class="font-medium text-gray-500 dark:text-gray-400">Puissance:</span><br> <span class="text-gray-900 dark:text-gray-100">{{ session('charging_point_step2.power_output') ? session('charging_point_step2.power_output') . ' kW' : 'Non spécifié' }}</span></p>
                        <p><span class="font-medium text-gray-500 dark:text-gray-400">Connecteur:</span><br> <span class="text-gray-900 dark:text-gray-100">{{ session('charging_point_step2.connector_type') ?? 'Non spécifié' }}</span></p>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-2">Connectivité</h4>
                    <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-800/50">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Connexion</p>
                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100" id="preview-connection">Ethernet</p>
                    </div>
                    <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-800/50">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Protocole</p>
                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100" id="preview-protocol">OCPP 1.6</p>
                    </div>
                </div>
                
                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-800">
                    <div class="rounded-xl bg-blue-50 dark:bg-blue-900/20 p-4 ring-1 ring-blue-200/70 dark:ring-blue-900/40">
                        <div class="flex gap-3">
                            <svg class="h-5 w-5 text-blue-500 dark:text-blue-300 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10A8 8 0 11 2 10a8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                            <div>
                                <h4 class="text-sm font-medium text-blue-900 dark:text-blue-200">Étape 3 de 4</h4>
                                <p class="mt-1 text-sm text-blue-800/90 dark:text-blue-300/90">Configurez la connectivité de la borne.</p>
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
        // Elements for real-time preview
        const connectionTypeSelect = document.getElementById('connection_type');
        const protocolSelect = document.getElementById('communication_protocol');
        const ipAddressInput = document.getElementById('ip_address');
        const macAddressInput = document.getElementById('mac_address');
        const accessTypeSelect = document.getElementById('access_type');
        const authCheckbox = document.getElementById('authentication_required');
        const accessCodeContainer = document.getElementById('access_code_container');
        
        // Preview elements
        const previewConnection = document.getElementById('preview-connection');
        const previewProtocol = document.getElementById('preview-protocol');
        const previewNetworking = document.getElementById('preview-networking');
        const previewAccessType = document.getElementById('preview-access-type');
        const previewAuth = document.getElementById('preview-auth');
        
        // Update preview on input
        function updatePreview() {
            if (connectionTypeSelect.selectedIndex >= 0) {
                previewConnection.textContent = connectionTypeSelect.options[connectionTypeSelect.selectedIndex].text;
            } else {
                previewConnection.textContent = 'Non spécifié';
            }
            
            if (protocolSelect.selectedIndex >= 0) {
                previewProtocol.textContent = protocolSelect.options[protocolSelect.selectedIndex].text;
            } else {
                previewProtocol.textContent = 'Non spécifié';
            }
            
            // IP / MAC
            let networkingText = [];
            if (ipAddressInput.value) networkingText.push(`IP: ${ipAddressInput.value}`);
            if (macAddressInput.value) networkingText.push(`MAC: ${macAddressInput.value}`);
            previewNetworking.textContent = networkingText.length > 0 ? networkingText.join(' / ') : 'Non spécifié';
            
            if (accessTypeSelect.selectedIndex >= 0) {
                previewAccessType.textContent = accessTypeSelect.options[accessTypeSelect.selectedIndex].text;
            } else {
                previewAccessType.textContent = 'Non spécifié';
            }
            
            previewAuth.textContent = authCheckbox.checked ? 'Oui' : 'Non';
            
            // Show/hide access code field based on access type
            if (accessTypeSelect.value === 'restricted') {
                accessCodeContainer.classList.remove('hidden');
            } else {
                accessCodeContainer.classList.add('hidden');
            }
        }
        
        // Add event listeners
        connectionTypeSelect.addEventListener('change', updatePreview);
        protocolSelect.addEventListener('change', updatePreview);
        ipAddressInput.addEventListener('input', updatePreview);
        macAddressInput.addEventListener('input', updatePreview);
        accessTypeSelect.addEventListener('change', updatePreview);
        authCheckbox.addEventListener('change', updatePreview);
        
        // Initialize preview
        updatePreview();
        
        // Form validation
        document.getElementById('step3Form').addEventListener('submit', function(event) {
            let isValid = true;
            
            // Reset error styles
            document.querySelectorAll('.border-red-500').forEach(el => {
                el.classList.remove('border-red-500');
            });
            
            // Validate required fields
            if (!connectionTypeSelect.value) {
                connectionTypeSelect.classList.add('border-red-500');
                isValid = false;
            }
            
            if (!protocolSelect.value) {
                protocolSelect.classList.add('border-red-500');
                isValid = false;
            }
            
            // Validate IP address format if provided
            if (ipAddressInput.value && !isValidIpAddress(ipAddressInput.value)) {
                ipAddressInput.classList.add('border-red-500');
                isValid = false;
            }
            
            // Validate MAC address format if provided
            if (macAddressInput.value && !isValidMacAddress(macAddressInput.value)) {
                macAddressInput.classList.add('border-red-500');
                isValid = false;
            }
            
            if (!isValid) {
                event.preventDefault();
                
                // Show error message
                alert('Veuillez corriger les erreurs dans le formulaire avant de continuer.');
            }
        });
        
        // Validation helpers
        function isValidIpAddress(ip) {
            // Simple regex for IPv4 validation
            return /^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/.test(ip);
        }
        
        function isValidMacAddress(mac) {
            // Simple regex for MAC address validation
            return /^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/.test(mac);
        }
    });
</script>
@endpush
@endsection