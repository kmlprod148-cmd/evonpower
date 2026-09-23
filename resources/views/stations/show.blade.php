@extends('layouts.app')

@section('content')
<div class="px-4 py-6" x-data="stationDetailManager()">
    <!-- Breadcrumb -->
    <nav class="flex mb-6" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ route('stations.index') }}" class="text-gray-700 hover:text-gray-900">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                    </svg>
                    {{ __('extracted.stations') }}
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                    </svg>
                    <span class="ml-1 text-gray-500 md:ml-2">{{ $station->name }}</span>
                </div>
            </li>
        </ol>
    </nav>

    <!-- Station Header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">{{ $station->name }}</h1>
                <p class="text-gray-600 mt-1">{{ $station->city }}, {{ $station->address }}</p>
                @if($station->postal_code)
                    <p class="text-gray-500 text-sm">{{ $station->postal_code }}</p>
                @endif
            </div>
            <div class="flex items-center space-x-3">
                <span class="px-3 py-1 text-sm font-medium rounded-full
                    @if($station->status == 'active') bg-green-100 text-green-800
                    @elseif($station->status == 'maintenance') bg-yellow-100 text-yellow-800
                    @elseif($station->status == 'inactive') bg-red-100 text-red-800
                    @else bg-gray-100 text-gray-800
                    @endif">
                    {{ ucfirst($station->status) }}
                </span>
                <a href="{{ route('stations.edit', $station) }}" 
                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                    {{ __('extracted.modifier') }}
                </a>
            </div>
        </div>
    </div>

    <!-- Station Info Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Basic Information -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('extracted.informations_generales') }}</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Type:</span>
                    <span class="text-sm font-medium text-gray-900 capitalize">{{ $station->type }}</span>
                </div>
                @if($station->group)
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Groupe:</span>
                    <span class="text-sm font-medium text-gray-900">{{ $station->group->name ?? $station->group->title }}</span>
                </div>
                @endif
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">{{ __('extracted.points_de_charge') }}:</span>
                    <span class="text-sm font-medium text-gray-900">{{ $station->chargingPoints->count() }}/2</span>
                </div>
                @if($station->latitude && $station->longitude)
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">{{ __('extracted.coordonnees') }}:</span>
                    <span class="text-sm font-medium text-gray-900">{{ $station->latitude }}, {{ $station->longitude }}</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Location Information -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('extracted.localisation') }}</h3>
            <div class="space-y-3">
                <div>
                    <span class="text-sm text-gray-600">Adresse:</span>
                    <p class="text-sm font-medium text-gray-900 mt-1">{{ $station->address }}</p>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Ville:</span>
                    <span class="text-sm font-medium text-gray-900">{{ $station->city }}</span>
                </div>
                @if($station->country)
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Pays:</span>
                    <span class="text-sm font-medium text-gray-900">{{ $station->country }}</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Status Information -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Statut</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">{{ __('extracted.statut_actuel') }}:</span>
                    <span class="px-2 py-1 text-xs font-medium rounded-full
                        @if($station->status == 'active') bg-green-100 text-green-800
                        @elseif($station->status == 'maintenance') bg-yellow-100 text-yellow-800
                        @elseif($station->status == 'inactive') bg-red-100 text-red-800
                        @else bg-gray-100 text-gray-800
                        @endif">
                        {{ ucfirst($station->status) }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">{{ __('extracted.points_actifs_label') }}:</span>
                    <span class="text-sm font-medium text-gray-900">{{ $station->chargingPoints->where('status', 'online')->count() }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">{{ __('extracted.puissance_totale') }}:</span>
                    <span class="text-sm font-medium text-gray-900">{{ $station->total_power_output ?? 0 }} {{ __('extracted.kw') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Charging Points Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-semibold text-gray-900">{{ __('extracted.points_de_charge') }} ({{ $station->chargingPoints->count() }}/2)</h3>
            @if($station->chargingPoints->count() < 2)
            <button @click="openAddChargingPointsModal()" 
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                {{ __('extracted.ajouter_un_point_de_charge') }}
            </button>
            @endif
        </div>

        @if($station->chargingPoints->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($station->chargingPoints as $chargingPoint)
            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                <div class="flex justify-between items-start mb-3">
                    <div class="flex items-center">
                        <div class="w-3 h-3 rounded-full mr-3
                            @if($chargingPoint->status == 'online') bg-green-500
                            @elseif($chargingPoint->status == 'offline') bg-red-500
                            @else bg-yellow-500
                            @endif">
                        </div>
                        <h4 class="text-lg font-semibold text-gray-900">{{ $chargingPoint->name }}</h4>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="px-2 py-1 text-xs font-medium rounded-full
                            @if($chargingPoint->status == 'online') bg-green-100 text-green-800
                            @elseif($chargingPoint->status == 'offline') bg-red-100 text-red-800
                            @else bg-yellow-100 text-yellow-800
                            @endif">
                            {{ ucfirst($chargingPoint->status) }}
                        </span>
                        <button @click="removeChargingPoint({{ $chargingPoint->id }})" 
                                class="text-red-600 hover:text-red-800 text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">{{ __('extracted.puissance') }}:</span>
                        <span class="font-medium text-gray-900">{{ $chargingPoint->power_output ?? 'N/A' }} {{ __('extracted.kw') }}</span>
                    </div>
                    @if($chargingPoint->connector_type)
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">{{ __('extracted.type_de_connecteur') }}:</span>
                        <span class="font-medium text-gray-900">{{ $chargingPoint->connector_type }}</span>
                    </div>
                    @endif
                    @if($chargingPoint->pricing_plan_id)
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">{{ __('extracted.plan_tarifaire') }}:</span>
                        <span class="font-medium text-gray-900">{{ $chargingPoint->pricingPlan->name ?? 'N/A' }}</span>
                    </div>
                    @endif
                </div>

                <div class="mt-4 pt-4 border-t border-gray-200">
                    <a href="{{ route('charging-points.show', $chargingPoint) }}" 
                       class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        {{ __('extracted.voir_les_details') }}
                    </a>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-12">
            <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            <h4 class="text-lg font-medium text-gray-900 mb-2">{{ __('extracted.aucun_point_de_charge_assigné') }}</h4>
            <p class="text-gray-600 mb-6">{{ __('extracted.cette_station_pas_points_charge') }}</p>
            <button @click="openAddChargingPointsModal()" 
                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg inline-flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                {{ __('extracted.ajouter_des_points_de_charge') }}
            </button>
        </div>
        @endif
    </div>

    <!-- Add Charging Points Modal -->
    <div x-show="showAddChargingPointsModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto" 
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeAddChargingPointsModal()"></div>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form @submit.prevent="addChargingPoints()">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('extracted.ajouter_des_points_de_charge') }}</h3>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                {{ __('extracted.points_de_charge_disponibles') }} ({{ __('extracted.max') }} {{ 2 - $station->chargingPoints->count() }})
                            </label>
                            <div class="space-y-2 max-h-48 overflow-y-auto">
                                <template x-for="chargingPoint in availableChargingPoints" :key="chargingPoint.id">
                                    <label class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                                        <input type="checkbox" 
                                               :value="chargingPoint.id" 
                                               x-model="selectedChargingPoints"
                                               class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-900" x-text="chargingPoint.name"></div>
                                            <div class="text-xs text-gray-500" x-text="chargingPoint.power_output + ' kW - ' + chargingPoint.status"></div>
                                        </div>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" 
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm">
                            {{ __('extracted.ajouter') }}
                        </button>
                        <button type="button" 
                                @click="closeAddChargingPointsModal()"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            {{ __('extracted.annuler') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function stationDetailManager() {
    return {
        showAddChargingPointsModal: false,
        selectedChargingPoints: [],
        availableChargingPoints: [],

        openAddChargingPointsModal() {
            this.selectedChargingPoints = [];
            this.loadAvailableChargingPoints();
            this.showAddChargingPointsModal = true;
        },

        closeAddChargingPointsModal() {
            this.showAddChargingPointsModal = false;
            this.selectedChargingPoints = [];
            this.availableChargingPoints = [];
        },

        async loadAvailableChargingPoints() {
            try {
                const response = await fetch('{{ route("available-charging-points") }}');
                const data = await response.json();
                this.availableChargingPoints = data.charging_points;
            } catch (error) {
                console.error('Error loading charging points:', error);
            }
        },

        async addChargingPoints() {
            if (this.selectedChargingPoints.length === 0) {
                alert('{{ __('extracted.selectionner_au_moins_un_point_charge') }}');
                return;
            }

            const maxAllowed = {{ 2 - $station->chargingPoints->count() }};
            if (this.selectedChargingPoints.length > maxAllowed) {
                alert('{{ __('extracted.vous_ne_pouvez_selectionner_que') }} ' + maxAllowed + ' {{ __('extracted.points_maximum') }}');
                return;
            }

            try {
                const response = await fetch(`{{ route('stations.add-charging-points', $station) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        charging_point_ids: this.selectedChargingPoints
                    })
                });

                const data = await response.json();

                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || '{{ __('extracted.erreur_ajout_points_charge_message') }}');
                }
            } catch (error) {
                console.error('Error adding charging points:', error);
                alert('{{ __('extracted.erreur_ajout_points_charge_message') }}');
            }
        },

        async removeChargingPoint(chargingPointId) {
            if (!confirm('{{ __('extracted.voulez_retirer_point_charge') }}')) {
                return;
            }

            try {
                const response = await fetch(`{{ route('stations.remove-charging-point', ['station' => $station, 'chargingPoint' => ':chargingPoint']) }}`.replace(':chargingPoint', chargingPointId), {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                const data = await response.json();

                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || '{{ __('extracted.erreur_suppression_point_charge_message') }}');
                }
            } catch (error) {
                console.error('Error removing charging point:', error);
                alert('{{ __('extracted.erreur_suppression_point_charge_message') }}');
            }
        }
    }
}
</script>
@endsection