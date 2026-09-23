@extends('layouts.app')

@section('page-title', 'Bornes Steve API')

@section('content')
<div class="bg-gradient-to-b from-gray-50 to-white dark:from-gray-900 dark:to-gray-950 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Liste des Bornes (API Steve)</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Gestion des bornes directement depuis l'API Steve</p>
            </div>
            <div class="mt-4 sm:mt-0 flex space-x-2">
                <a href="{{ route('steve-api.diagnostic') }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    🔍 Diagnostic
                </a>
            </div>
        </div>

        <!-- Messages -->
        @if(session('success'))
            <div class="mb-6 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 text-green-700 dark:text-green-300 p-4 rounded-lg shadow-sm" role="alert">
                <p>{{ session('success') }}</p>
            </div>
        @endif

        @if(!isset($error) && !session('error') && empty($chargePoints))
            <div class="mb-6 bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 text-blue-700 dark:text-blue-300 p-4 rounded-lg shadow-sm">
                <div class="flex items-start">
                    <svg class="h-5 w-5 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <p class="font-medium">API Steve connectée avec succès</p>
                        <p class="text-sm mt-1">Aucune borne n'est actuellement enregistrée dans l'API Steve.</p>
                    </div>
                </div>
            </div>
        @endif

        @if(session('error') || isset($error))
            <div class="mb-6 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 text-red-700 dark:text-red-300 p-4 rounded-lg shadow-sm" role="alert">
                <div class="flex items-start justify-between">
                    <div class="flex items-start flex-1">
                        <svg class="h-5 w-5 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="flex-1">
                            <p class="font-medium whitespace-pre-line">{{ session('error') ?? $error ?? 'Une erreur est survenue' }}</p>
                            
                            @php
                                $errorMsg = session('error') ?? $error ?? '';
                                $isOcppError = str_contains($errorMsg, 'OCPP') || str_contains($errorMsg, 'connectée') || str_contains($errorMsg, 'heartbeat');
                            @endphp
                            
                            @if($isOcppError)
                                <details class="mt-3 text-sm">
                                    <summary class="cursor-pointer hover:underline font-medium">
                                        🔧 Aide au dépannage
                                    </summary>
                                    <div class="mt-2 pl-4 border-l-2 border-red-400 dark:border-red-600 space-y-2">
                                        <p><strong>Actions à effectuer :</strong></p>
                                        <ol class="list-decimal list-inside space-y-1">
                                            <li>Vérifier que la borne est allumée</li>
                                            <li>Configurer l'URL OCPP sur la borne</li>
                                            <li>Vérifier la connexion réseau</li>
                                            <li>Consulter l'interface Steve pour voir si les heartbeats arrivent</li>
                                        </ol>
                                        <p class="mt-2">
                                            <a href="{{ url('/') }}/TROUBLESHOOTING_STEVE_REMOTE_START.md" target="_blank" class="underline hover:no-underline">
                                                📖 Consulter le guide complet de dépannage
                                            </a>
                                        </p>
                                    </div>
                                </details>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('steve-api.diagnostic') }}" 
                       class="ml-4 inline-flex items-center px-3 py-1 border border-red-600 dark:border-red-500 rounded-md text-sm font-medium text-red-700 dark:text-red-300 bg-white dark:bg-red-900/20 hover:bg-red-50 dark:hover:bg-red-900/40 transition-colors flex-shrink-0">
                        🔍 Diagnostic
                    </a>
                </div>
            </div>
        @endif
        
        <!-- Aide : Configuration OCPP pour les bornes -->
        @if(!empty($chargePoints))
            @php
                $offlineCount = 0;
                foreach ($chargePoints as $cp) {
                    $lastHeartbeat = $cp['lastHeartbeatTimestamp'] ?? null;
                    if (!$lastHeartbeat || \Carbon\Carbon::parse($lastHeartbeat)->lt(now()->subMinutes(5))) {
                        $offlineCount++;
                    }
                }
            @endphp
            
            @if($offlineCount > 0)
                <div class="mb-6 bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500 text-yellow-700 dark:text-yellow-300 p-4 rounded-lg shadow-sm">
                    <div class="flex items-start">
                        <svg class="h-5 w-5 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div class="flex-1">
                            <p class="font-medium">
                                ⚠️ {{ $offlineCount }} borne(s) hors ligne (pas de heartbeat récent)
                            </p>
                            <details class="mt-2 text-sm">
                                <summary class="cursor-pointer hover:underline font-medium">
                                    📖 Comment connecter une borne au serveur OCPP Steve
                                </summary>
                                <div class="mt-3 pl-4 border-l-2 border-yellow-400 dark:border-yellow-600 space-y-3">
                                    <div>
                                        <p class="font-semibold">Configuration OCPP sur la borne :</p>
                                        <div class="mt-1 p-2 bg-yellow-100 dark:bg-yellow-900/30 rounded">
                                            <p class="font-mono text-xs">
                                                URL WebSocket (OCPP 1.6 JSON) :<br>
                                                <code class="select-all">ws://158.69.27.239:8180/steve/websocket/CentralSystemService/{ChargeBoxId}</code>
                                            </p>
                                            <p class="font-mono text-xs mt-2">
                                                URL SOAP (OCPP 1.5/1.6) :<br>
                                                <code class="select-all">http://158.69.27.239:8180/steve/services/CentralSystemService</code>
                                            </p>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="font-semibold">Vérification :</p>
                                        <ol class="list-decimal list-inside space-y-1 mt-1">
                                            <li>Vérifier que la borne apparaît dans l'<a href="http://158.69.27.239:8180/steve/manager/home" target="_blank" class="underline">interface web Steve</a></li>
                                            <li>Vérifier que "Last Heartbeat" est récent (< 5 minutes)</li>
                                            <li>Vérifier les logs de la borne pour confirmer la connexion OCPP</li>
                                        </ol>
                                    </div>
                                    <div class="pt-2 border-t border-yellow-400 dark:border-yellow-600">
                                        <a href="{{ url('/') }}/TROUBLESHOOTING_STEVE_REMOTE_START.md" target="_blank" class="inline-flex items-center text-sm font-medium underline hover:no-underline">
                                            📚 Guide complet de dépannage
                                            <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            </details>
                        </div>
                    </div>
                </div>
            @endif
        @endif

        <!-- Table -->
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ChargeBox PK</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ChargeBox ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Protocole OCPP</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Dernier Heartbeat</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($chargePoints ?? [] as $cp)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                {{ $cp['chargeBoxPk'] ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $cp['chargeBoxId'] ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ Str::limit($cp['description'] ?? 'Aucune description', 50) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                @if(!empty($cp['ocppProtocol']))
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                        {{ $cp['ocppProtocol'] }}
                                    </span>
                                @else
                                    <span class="text-gray-400">N/A</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                @if(!empty($cp['lastHeartbeatTimestamp']))
                                    <span class="inline-flex items-center">
                                        <svg class="w-4 h-4 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        {{ $cp['lastHeartbeatTimestamp'] }}
                                    </span>
                                @else
                                    <span class="text-gray-400">Jamais</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    <!-- Remote Start -->
                                    <button 
                                        onclick="openRemoteStartModal('{{ $cp['chargeBoxId'] ?? '' }}')"
                                        class="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300 tooltip"
                                        title="Démarrer la charge">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </button>
                                    
                                    <!-- Remote Stop -->
                                    <form action="{{ route('steve-charging-points.remote-stop', $cp['chargeBoxId'] ?? 0) }}" method="POST" class="inline" 
                                          onsubmit="return confirm('Êtes-vous sûr de vouloir arrêter la charge sur cette borne ?');">
                                        @csrf
                                        <button type="submit" class="text-yellow-600 dark:text-yellow-400 hover:text-yellow-900 dark:hover:text-yellow-300 tooltip"
                                                title="Arrêter la charge">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10h6v4H9z" />
                                            </svg>
                                        </button>
                                    </form>
                                    
                                    <a href="{{ route('steve-charging-points.show', $cp['chargeBoxPk'] ?? $cp['id'] ?? 0) }}" 
                                       class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300 tooltip"
                                       title="Voir détails">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    
                                    <a href="{{ route('steve-charging-points.edit', $cp['chargeBoxPk'] ?? $cp['id'] ?? 0) }}" 
                                       class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 tooltip"
                                       title="Modifier">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                    
                                    <form action="{{ route('steve-charging-points.destroy', $cp['chargeBoxPk'] ?? $cp['id'] ?? 0) }}" method="POST" class="inline" onsubmit="return confirm('⚠️ ATTENTION: Cette opération va supprimer définitivement la borne et TOUTES ses données associées (transactions, réservations, statuts, valeurs de compteur). Êtes-vous absolument sûr ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 tooltip"
                                                title="Supprimer">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                <div class="flex flex-col items-center justify-center py-8">
                                    <svg class="h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                    <p>Aucune borne trouvée sur l'API Steve</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Remote Start -->
<div id="remoteStartModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3">
            <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100 mb-4">
                Démarrer la charge (Remote Start)
            </h3>
            <form id="remoteStartForm" method="POST" action="">
                @csrf
                <input type="hidden" id="remoteStartChargeBoxId" name="chargeBoxId" value="">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Connecteur ID
                    </label>
                    <input type="number" 
                           name="connectorId" 
                           min="0" 
                           value="1" 
                           required
                           class="shadow-sm focus:ring-green-500 focus:border-green-500 block w-full sm:text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        0 = choix automatique du connecteur
                    </p>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Tag OCPP (RFID)
                    </label>
                    <input type="text" 
                           name="ocppTag" 
                           placeholder="Ex: RFID-TAG-12345" 
                           maxlength="20"
                           required
                           class="shadow-sm focus:ring-green-500 focus:border-green-500 block w-full sm:text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Identifiant du badge/carte RFID pour démarrer la charge
                    </p>
                </div>
                
                <div class="flex items-center justify-end space-x-3">
                    <button type="button" 
                            onclick="closeRemoteStartModal()"
                            class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                        Annuler
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                        Démarrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openRemoteStartModal(chargeBoxId) {
    document.getElementById('remoteStartModal').classList.remove('hidden');
    document.getElementById('remoteStartChargeBoxId').value = chargeBoxId;
    document.getElementById('remoteStartForm').action = `/steve-api/${chargeBoxId}/remote-start`;
}

function closeRemoteStartModal() {
    document.getElementById('remoteStartModal').classList.add('hidden');
    document.getElementById('remoteStartForm').reset();
}

// Fermer le modal si on clique en dehors
document.getElementById('remoteStartModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeRemoteStartModal();
    }
});
</script>

@endsection

