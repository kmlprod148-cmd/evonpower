@extends('layouts.app')

@section('page-title', 'Opérations OCPP')

@section('content')
<div class="bg-gradient-to-b from-gray-50 to-white dark:from-gray-900 dark:to-gray-950 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Opérations OCPP</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Contrôle direct des bornes via Steve API</p>
        </div>

        <!-- Messages -->
        @if(session('success'))
            <div class="mb-6 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 text-green-700 dark:text-green-300 p-4 rounded-lg shadow-sm">
                <div class="flex items-start">
                    <svg class="h-5 w-5 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <p>{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if(session('error') || $errors->any())
            <div class="mb-6 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 text-red-700 dark:text-red-300 p-4 rounded-lg shadow-sm">
                <div class="flex items-start">
                    <svg class="h-5 w-5 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <p>{{ session('error') }}</p>
                        @if($errors->any())
                            <ul class="mt-2 list-disc list-inside">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Remote Start -->
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-green-50 dark:bg-green-900/20">
                    <h2 class="text-lg font-semibold text-green-900 dark:text-green-100">▶️ Remote Start</h2>
                    <p class="text-sm text-green-700 dark:text-green-300 mt-1">Démarrer une session de recharge</p>
                </div>
                <div class="p-6">
                    <form action="{{ route('ocpp.remote-start') }}" method="POST" class="space-y-4">
                        @csrf
                        
                        <!-- ChargeBox ID -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ChargeBox ID <span class="text-red-500">*</span>
                            </label>
                            <select name="chargeBoxId" 
                                    id="start-chargeBoxId"
                                    required
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 shadow-sm focus:border-green-500 focus:ring-green-500">
                                <option value="">Sélectionner une borne...</option>
                                @foreach($chargingPoints as $cp)
                                    <option value="{{ $cp->steve_charging_point_id }}" 
                                            data-connector="{{ $cp->connector_id ?? 1 }}">
                                        {{ $cp->name }} ({{ $cp->steve_charging_point_id }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Identifiant de la borne sur Steve</p>
                        </div>

                        <!-- Connector ID -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Connector ID <span class="text-red-500">*</span>
                            </label>
                            <input type="number" 
                                   name="connectorId" 
                                   id="start-connectorId"
                                   value="1"
                                   min="1"
                                   required
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 shadow-sm focus:border-green-500 focus:ring-green-500">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Numéro du connecteur (généralement 1)</p>
                        </div>

                        <!-- OCPP Tag -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                OCPP Tag (RFID) <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   name="ocppTag" 
                                   id="start-ocppTag"
                                   value="{{ auth()->user()->ocpp_tag ?? 'USER-' . str_pad(auth()->id(), 8, '0', STR_PAD_LEFT) }}"
                                   required
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 shadow-sm focus:border-green-500 focus:ring-green-500">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Tag RFID ou identifiant utilisateur</p>
                        </div>

                        <!-- JSON Preview -->
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                            <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Payload JSON:</p>
                            <pre class="text-xs text-gray-600 dark:text-gray-400 font-mono" id="start-preview">{
  "chargeBoxId": "",
  "connectorId": 1,
  "ocppTag": ""
}</pre>
                        </div>

                        <button type="submit" 
                                class="w-full inline-flex items-center justify-center px-4 py-3 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Démarrer la recharge
                        </button>
                    </form>
                </div>
            </div>

            <!-- Remote Stop -->
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-red-50 dark:bg-red-900/20">
                    <h2 class="text-lg font-semibold text-red-900 dark:text-red-100">⏹️ Remote Stop</h2>
                    <p class="text-sm text-red-700 dark:text-red-300 mt-1">Arrêter une session de recharge</p>
                </div>
                <div class="p-6">
                    <form action="{{ route('ocpp.remote-stop') }}" method="POST" class="space-y-4">
                        @csrf
                        
                        <!-- ChargeBox ID -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ChargeBox ID <span class="text-red-500">*</span>
                            </label>
                            <select name="chargeBoxId" 
                                    id="stop-chargeBoxId"
                                    required
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 shadow-sm focus:border-red-500 focus:ring-red-500">
                                <option value="">Sélectionner une borne...</option>
                                @foreach($chargingPoints as $cp)
                                    <option value="{{ $cp->steve_charging_point_id }}">
                                        {{ $cp->name }} ({{ $cp->steve_charging_point_id }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Identifiant de la borne sur Steve</p>
                        </div>

                        <!-- JSON Preview -->
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                            <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Query Parameters:</p>
                            <pre class="text-xs text-gray-600 dark:text-gray-400 font-mono" id="stop-preview">?chargeBoxId=</pre>
                        </div>

                        <button type="submit" 
                                onclick="return confirm('Êtes-vous sûr de vouloir arrêter la recharge en cours ?')"
                                class="w-full inline-flex items-center justify-center px-4 py-3 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z" />
                            </svg>
                            Arrêter la recharge
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sessions actives -->
        @if($activeSessions->count() > 0)
        <div class="mt-6 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">⚡ Sessions actives ({{ $activeSessions->count() }})</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Borne</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Utilisateur</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Connecteur</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Démarrée</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Durée</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Mode</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($activeSessions as $session)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $session->chargingPoint->name ?? 'N/A' }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                                    {{ $session->chargingPoint->steve_charging_point_id ?? 'N/A' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                {{ $session->user->name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                #{{ $session->connector_id }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ $session->started_at->format('H:i:s') }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                    {{ $session->getDuration() }} min
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if($session->payment_mode === 'prepaid')
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                        💰 Prépayé
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                                        💳 Postpayé
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <form action="{{ route('ocpp.web-stop', $session->chargingPoint->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit"
                                            onclick="return confirm('Arrêter cette session ?')"
                                            class="inline-flex items-center px-3 py-1 border border-transparent rounded-md text-xs font-medium text-white bg-red-600 hover:bg-red-700">
                                        ⏹️ Arrêter
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Documentation -->
        <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 p-4 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800 dark:text-blue-300">ℹ️ API Endpoints</h3>
                    <div class="mt-2 text-sm text-blue-700 dark:text-blue-400 space-y-1">
                        <p><code class="bg-white dark:bg-gray-800 px-2 py-1 rounded">POST /api/v1/ocpp/remote-start</code></p>
                        <p><code class="bg-white dark:bg-gray-800 px-2 py-1 rounded">POST /api/v1/ocpp/remote-stop?chargeBoxId={id}</code></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Preview JSON pour Remote Start
document.addEventListener('DOMContentLoaded', function() {
    const chargeBoxIdSelect = document.getElementById('start-chargeBoxId');
    const connectorIdInput = document.getElementById('start-connectorId');
    const ocppTagInput = document.getElementById('start-ocppTag');
    const startPreview = document.getElementById('start-preview');

    function updateStartPreview() {
        const payload = {
            chargeBoxId: chargeBoxIdSelect.value || "",
            connectorId: parseInt(connectorIdInput.value) || 1,
            ocppTag: ocppTagInput.value || ""
        };
        startPreview.textContent = JSON.stringify(payload, null, 2);
    }

    // Auto-remplir le connector ID depuis la borne sélectionnée
    chargeBoxIdSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const connectorId = selectedOption.getAttribute('data-connector');
        if (connectorId) {
            connectorIdInput.value = connectorId;
        }
        updateStartPreview();
    });

    connectorIdInput.addEventListener('input', updateStartPreview);
    ocppTagInput.addEventListener('input', updateStartPreview);

    // Preview pour Remote Stop
    const stopChargeBoxIdSelect = document.getElementById('stop-chargeBoxId');
    const stopPreview = document.getElementById('stop-preview');

    function updateStopPreview() {
        stopPreview.textContent = '?chargeBoxId=' + (stopChargeBoxIdSelect.value || '');
    }

    stopChargeBoxIdSelect.addEventListener('change', updateStopPreview);
});
</script>
@endpush
@endsection

