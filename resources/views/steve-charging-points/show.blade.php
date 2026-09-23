@extends('layouts.app')

@section('page-title', 'Détails de la Borne')

@section('content')
<div class="bg-gradient-to-b from-gray-50 to-white dark:from-gray-900 dark:to-gray-950 min-h-screen">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-6">
            <a href="{{ route('steve-charging-points.index') }}" 
               class="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 mb-4">
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Retour à la liste
            </a>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Détails de la Borne</h1>
        </div>

        <!-- Messages -->
        @if(session('success'))
            <div class="mb-6 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 text-green-700 dark:text-green-300 p-4 rounded-lg shadow-sm" role="alert">
                <p>{{ session('success') }}</p>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 text-red-700 dark:text-red-300 p-4 rounded-lg shadow-sm" role="alert">
                <p>{{ session('error') }}</p>
            </div>
        @endif

        <!-- Card -->
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm overflow-hidden">
            <div class="p-6">
                <!-- Informations principales -->
                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Informations principales</h2>
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ChargeBox PK</dt>
                            <dd class="mt-1 text-sm font-mono text-gray-900 dark:text-gray-100">{{ $chargePoint['chargeBoxPk'] ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ChargeBox ID</dt>
                            <dd class="mt-1 text-sm font-mono text-gray-900 dark:text-gray-100">{{ $chargePoint['chargeBoxId'] ?? 'N/A' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $chargePoint['description'] ?? 'N/A' }}</dd>
                        </div>
                        @if(!empty($chargePoint['ocppProtocol']))
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Protocole OCPP</dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                    {{ $chargePoint['ocppProtocol'] }}
                                </span>
                            </dd>
                        </div>
                        @endif
                        @if(!empty($chargePoint['lastHeartbeatTimestamp']))
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Dernier Heartbeat</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                <span class="inline-flex items-center">
                                    <svg class="w-4 h-4 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ $chargePoint['lastHeartbeatTimestamp'] }}
                                </span>
                            </dd>
                        </div>
                        @endif
                    </dl>
                </div>

                <!-- Adresse -->
                @if(!empty($chargePoint['street']) || !empty($chargePoint['city']) || !empty($chargePoint['zipCode']))
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Adresse</h2>
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        @if(!empty($chargePoint['street']))
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Rue</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $chargePoint['street'] }}</dd>
                        </div>
                        @endif
                        @if(!empty($chargePoint['zipCode']))
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Code postal</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $chargePoint['zipCode'] }}</dd>
                        </div>
                        @endif
                        @if(!empty($chargePoint['city']))
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Ville</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $chargePoint['city'] }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
                @endif

                <!-- Informations additionnelles -->
                @if(!empty($chargePoint['adminAddress']) || !empty($chargePoint['registrationStatus']) || !empty($chargePoint['note']))
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Informations additionnelles</h2>
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        @if(!empty($chargePoint['adminAddress']))
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Adresse Administrateur</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $chargePoint['adminAddress'] }}</dd>
                        </div>
                        @endif
                        @if(!empty($chargePoint['registrationStatus']))
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Statut d'enregistrement</dt>
                            <dd class="mt-1">
                                @php
                                    $status = $chargePoint['registrationStatus'];
                                    $statusClass = match($status) {
                                        'Accepted' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                                        'Pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
                                        'Rejected' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                                    {{ $status }}
                                </span>
                            </dd>
                        </div>
                        @endif
                        @if(!empty($chargePoint['note']))
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Note</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap">{{ $chargePoint['note'] }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
                @endif
            </div>

            <!-- Actions -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <a href="{{ route('steve-charging-points.index') }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                    Retour
                </a>
                <div class="flex space-x-3">
                    <a href="{{ route('steve-charging-points.edit', $chargePoint['chargeBoxPk'] ?? $chargePoint['id'] ?? 0) }}" 
                       class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Modifier
                    </a>
                    <form action="{{ route('steve-charging-points.destroy', $chargePoint['chargeBoxPk'] ?? $chargePoint['id'] ?? 0) }}" method="POST" class="inline" onsubmit="return confirm('⚠️ ATTENTION: Cette opération va supprimer définitivement la borne et TOUTES ses données associées (transactions, réservations, statuts, valeurs de compteur). Êtes-vous absolument sûr ?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 transition-colors">
                            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Supprimer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

