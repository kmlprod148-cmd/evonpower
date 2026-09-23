@extends('layouts.app')

@section('content')
<div class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="border-b border-gray-200 bg-white">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between py-4">
                <div>
                    <h1 class="text-xl lg:text-2xl font-bold text-gray-900">{{ __('Transactions OCPP (Steve)') }}</h1>
                    <p class="text-sm text-gray-500">{{ __('Visualisez et analysez les transactions de charge enregistrées dans Steve') }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('steve-transactions.export', request()->query()) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        {{ __('Exporter CSV') }}
                    </a>
                    @if(auth()->user()->hasRole(['admin', 'super_admin']))
                    <form action="{{ route('steve-transactions.sync') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white hover:bg-blue-700 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            {{ __('Synchroniser') }}
                        </button>
                    </form>
                    @endif
                </div>
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

        <!-- Statistiques -->
        @if($stats)
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-sm text-gray-500">{{ __('Total') }}</div>
                <div class="text-2xl font-bold text-gray-900">{{ $stats['total_count'] }}</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-sm text-gray-500">{{ __('Actives') }}</div>
                <div class="text-2xl font-bold text-yellow-600">{{ $stats['active_count'] }}</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-sm text-gray-500">{{ __('Arrêtées') }}</div>
                <div class="text-2xl font-bold text-green-600">{{ $stats['stopped_count'] }}</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-sm text-gray-500">{{ __('Énergie Totale') }}</div>
                <div class="text-2xl font-bold text-blue-600">{{ $stats['total_energy_kwh'] }} kWh</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-sm text-gray-500">{{ __('Moyenne') }}</div>
                <div class="text-2xl font-bold text-indigo-600">{{ $stats['average_energy_kwh'] }} kWh</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-sm text-gray-500">{{ __('Durée Moy.') }}</div>
                <div class="text-2xl font-bold text-purple-600">{{ $stats['average_duration_minutes'] }} min</div>
            </div>
        </div>
        @endif

        <!-- Filtres -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="p-4 border-b border-gray-200">
                <h3 class="font-semibold text-gray-700 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    {{ __('Filtres de Recherche') }}
                </h3>
            </div>
            <div class="p-4">
                <form action="{{ route('steve-transactions.index') }}" method="GET">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                        <!-- Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Type') }}</label>
                            <select name="type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="ALL" {{ ($filters['type'] ?? 'ALL') === 'ALL' ? 'selected' : '' }}>{{ __('Toutes') }}</option>
                                <option value="ACTIVE" {{ ($filters['type'] ?? '') === 'ACTIVE' ? 'selected' : '' }}>{{ __('Actives') }}</option>
                                <option value="STOPPED" {{ ($filters['type'] ?? '') === 'STOPPED' ? 'selected' : '' }}>{{ __('Arrêtées') }}</option>
                            </select>
                        </div>

                        <!-- Période -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Période') }}</label>
                            <select name="periodType" id="periodType" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="ALL" {{ ($filters['periodType'] ?? '') === 'ALL' ? 'selected' : '' }}>{{ __('Toutes') }}</option>
                                <option value="TODAY" {{ ($filters['periodType'] ?? '') === 'TODAY' ? 'selected' : '' }}>{{ __('Aujourd\'hui') }}</option>
                                <option value="LAST_10" {{ ($filters['periodType'] ?? '') === 'LAST_10' ? 'selected' : '' }}>{{ __('10 derniers jours') }}</option>
                                <option value="LAST_30" {{ ($filters['periodType'] ?? 'LAST_30') === 'LAST_30' ? 'selected' : '' }}>{{ __('30 derniers jours') }}</option>
                                <option value="LAST_90" {{ ($filters['periodType'] ?? '') === 'LAST_90' ? 'selected' : '' }}>{{ __('90 derniers jours') }}</option>
                                <option value="FROM_TO" {{ ($filters['periodType'] ?? '') === 'FROM_TO' ? 'selected' : '' }}>{{ __('Période personnalisée') }}</option>
                            </select>
                        </div>

                        <!-- Point de charge -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Point de Charge') }}</label>
                            <select name="chargeBoxId" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="">{{ __('Tous') }}</option>
                                @foreach($chargingPoints as $cp)
                                    @php $cbId = $cp->charge_box_id ?? $cp->steve_charging_point_id; @endphp
                                    @if($cbId)
                                    <option value="{{ $cbId }}" {{ ($filters['chargeBoxId'] ?? '') === $cbId ? 'selected' : '' }}>
                                        {{ $cp->name }} ({{ $cbId }})
                                    </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>

                        <!-- Tag OCPP -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Tag OCPP') }}</label>
                            <input type="text" name="ocppIdTag" value="{{ $filters['ocppIdTag'] ?? '' }}" 
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                placeholder="Ex: Open10Tag">
                        </div>
                    </div>

                    <!-- Dates personnalisées (affichées si FROM_TO sélectionné) -->
                    <div id="customDates" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4" style="display: {{ ($filters['periodType'] ?? '') === 'FROM_TO' ? 'grid' : 'none' }};">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Date de début') }}</label>
                            <input type="datetime-local" name="from" value="{{ $filters['from'] ?? '' }}" 
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Date de fin') }}</label>
                            <input type="datetime-local" name="to" value="{{ $filters['to'] ?? '' }}" 
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors text-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            {{ __('Rechercher') }}
                        </button>
                        <a href="{{ route('steve-transactions.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors text-sm">
                            {{ __('Réinitialiser') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Liste des transactions -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('ChargeBox') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Tag OCPP') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Conn.') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Début') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Fin') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Durée') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Énergie') }}</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Statut') }}</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($transactions as $transaction)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-mono text-gray-900">
                                #{{ $transaction['id'] ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm">
                                <code class="bg-gray-100 px-2 py-1 rounded text-xs">{{ $transaction['chargeBoxId'] ?? 'N/A' }}</code>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm">
                                <code class="bg-indigo-100 text-indigo-800 px-2 py-1 rounded text-xs font-semibold">{{ $transaction['ocppIdTag'] ?? 'N/A' }}</code>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-center text-gray-500">
                                {{ $transaction['connectorId'] ?? '-' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                {{ $transaction['start_formatted'] ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                {{ $transaction['stop_formatted'] ?? '-' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-900">
                                {{ $transaction['duration_formatted'] ?? '-' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-semibold text-blue-600">
                                @if($transaction['energy_consumed_kwh'])
                                    {{ $transaction['energy_consumed_kwh'] }} kWh
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center">
                                @if($transaction['is_active'])
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        <span class="h-2 w-2 mr-1 rounded-full bg-yellow-500 animate-pulse"></span>
                                        {{ __('Active') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        {{ __('Terminée') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-center text-sm">
                                <a href="{{ route('steve-transactions.show', $transaction['id']) }}" class="text-blue-600 hover:text-blue-900">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="px-6 py-12 text-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <p class="text-gray-500">{{ __('Aucune transaction trouvée') }}</p>
                                <p class="text-sm text-gray-400 mt-2">{{ __('Essayez de modifier les filtres de recherche') }}</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if(count($transactions) > 0)
            <div class="bg-gray-50 px-4 py-3 border-t border-gray-200 text-sm text-gray-700">
                {{ __('Affichage de') }} <strong>{{ count($transactions) }}</strong> {{ __('transaction(s)') }}
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Afficher/masquer les champs de dates personnalisées
    const periodType = document.getElementById('periodType');
    const customDates = document.getElementById('customDates');
    
    if (periodType) {
        periodType.addEventListener('change', function() {
            if (this.value === 'FROM_TO') {
                customDates.style.display = 'grid';
            } else {
                customDates.style.display = 'none';
            }
        });
    }
});
</script>
@endpush
@endsection

