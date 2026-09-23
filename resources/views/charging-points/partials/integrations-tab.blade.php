@props(['chargingPoint'])

<x-card padding="md" class="mb-6">
    <x-section-header title="Intégration" subtitle="Configuration SteVe OCPP et API" />
    
    <div class="space-y-6">
        <!-- SteVe Connection -->
        <x-card padding="md" shadow="md">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h4 class="text-xl font-semibold text-gray-900">Connexion SteVe OCPP</h4>
                    <p class="text-sm text-gray-600 mt-1">Connecter la borne au serveur SteVe OCPP pour la gestion des communications</p>
                </div>
                <div class="flex space-x-3">
                    <button onclick="testSteVeConnection()" class="inline-flex items-center px-4 py-2 border border-green-300 shadow-sm text-sm font-medium rounded-lg text-green-700 bg-green-50 hover:bg-green-100 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Tester Connexion
                    </button>
                </div>
            </div>
            
            <div class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 lg:gap-6">
                    <div>
                        <label for="serial_number" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-3">
                            Charge Box ID <span class="text-red-500">*</span>
                        </label>
                        <div class="flex items-center space-x-3">
                            <div class="flex-1">
                                <input type="text" id="charge-box-id-display" value="{{ $chargingPoint->serial_number ?? $chargingPoint->charge_box_id ?? 'BORNE_' . $chargingPoint->id }}" readonly class="hidden w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm bg-gray-50 text-sm">
                            </div>
                            <button onclick="connectChargingPoint()" id="connect-btn" class="inline-flex items-center px-4 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                </svg>
                                <span id="connect-btn-text">Connecter la borne</span>
                            </button>
                        </div>
                        <p class="mt-2 text-xs text-gray-500 flex items-center">
                            <svg class="h-3 w-3 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Numéro de série: {{ $chargingPoint->serial_number ?? 'N/A' }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500 flex items-center">
                            <svg class="h-3 w-3 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Connexion automatique via API Steve
                        </p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-3">URL du serveur SteVe</label>
                        <input type="text" id="steve-server-url-input" value="{{ $chargingPoint->steve_server_url ?? 'ws://158.69.27.239:8080/steve/websocket/CentralSystemService/' }}" class="block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm transition-colors">
                        <p class="mt-2 text-xs text-gray-500 flex items-center">
                            <svg class="h-3 w-3 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            URL WebSocket du serveur SteVe OCPP
                        </p>
                    </div>
                </div>
                
                @if($chargingPoint->websocket_url)
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <label class="block text-sm font-semibold text-gray-700 mb-3">URL WebSocket complète</label>
                    <div class="flex">
                        <input type="text" 
                               value="{{ $chargingPoint->websocket_url }}" 
                               readonly 
                               class="flex-1 block w-full px-4 py-3 border border-gray-300 rounded-l-lg shadow-sm bg-white text-sm font-mono">
                        <button onclick="copyWebSocketUrl()" 
                                class="inline-flex items-center px-4 py-3 border border-l-0 border-gray-300 rounded-r-lg bg-green-600 text-white text-sm font-medium hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </button>
                    </div>
                    <p class="mt-2 text-xs text-gray-500 flex items-center">
                        <svg class="h-3 w-3 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        URL complète pour le simulateur de borne
                    </p>
                </div>
                @endif
                
                @if($chargingPoint->last_connection_attempt)
                <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h5 class="text-sm font-medium text-blue-800">Dernière tentative de connexion</h5>
                            <p class="text-sm text-blue-700">{{ \Carbon\Carbon::parse($chargingPoint->last_connection_attempt)->format('d/m/Y H:i:s') }}</p>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </x-card>
    </div>
</x-card>

