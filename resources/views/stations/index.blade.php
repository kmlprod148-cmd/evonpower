@extends('layouts.app')

@section('content')
<div class="px-4 py-6" x-data="stationManager()">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">{{ __('extracted.stations') }}</h1>
            <p class="text-gray-600 mt-1">{{ __('extracted.gerer_stations_recharge_points_charge') }}</p>
        </div>
        <a href="{{ route('stations.create') }}" 
           class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            {{ __('extracted.nouvelle_station') }}
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">{{ __('extracted.total_stations') }}</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stations->total() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">{{ __('extracted.stations_actives') }}</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stations->where('status', 'active')->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
            <div class="flex items-center">
                <div class="p-3 bg-yellow-100 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">{{ __('extracted.en_maintenance') }}</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stations->where('status', 'maintenance')->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">{{ __('extracted.points_de_charge_total') }}</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stations->sum(function($station) { return $station->chargingPoints->count(); }) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <form method="GET" action="{{ route('stations.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <!-- Search -->
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">{{ __('extracted.recherche') }}</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}" 
                           placeholder="{{ __('extracted.nom_adresse_ville') }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>

                <!-- City -->
                <div>
                    <label for="city" class="block text-sm font-medium text-gray-700 mb-1">{{ __('extracted.ville') }}</label>
                    <input type="text" name="city" id="city" value="{{ request('city') }}" 
                           placeholder="{{ __('extracted.ville') }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>

                <!-- Type -->
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-1">{{ __('extracted.type') }}</label>
                    <select name="type" id="type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <option value="">{{ __('extracted.tous_les_types') }}</option>
                        <option value="public" {{ request('type') == 'public' ? 'selected' : '' }}>{{ __('extracted.public') }}</option>
                        <option value="private" {{ request('type') == 'private' ? 'selected' : '' }}>{{ __('extracted.prive') }}</option>
                        <option value="commercial" {{ request('type') == 'commercial' ? 'selected' : '' }}>{{ __('extracted.commercial') }}</option>
                    </select>
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">{{ __('extracted.statut') }}</label>
                    <select name="status" id="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <option value="">{{ __('extracted.tous_les_statuts') }}</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>{{ __('extracted.actif') }}</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>{{ __('extracted.inactif') }}</option>
                        <option value="maintenance" {{ request('status') == 'maintenance' ? 'selected' : '' }}>{{ __('extracted.maintenance') }}</option>
                        <option value="planned" {{ request('status') == 'planned' ? 'selected' : '' }}>{{ __('extracted.planifie') }}</option>
                    </select>
                </div>

                <!-- Group -->
                <div>
                    <label for="group_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('extracted.groupe') }}</label>
                    <select name="group_id" id="group_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <option value="">{{ __('extracted.tous_les_groupes') }}</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" {{ request('group_id') == $group->id ? 'selected' : '' }}>
                                {{ $group->name ?? $group->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex justify-between items-center">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md transition-colors">
                    {{ __('extracted.filtrer') }}
                </button>
                <a href="{{ route('stations.index') }}" class="text-gray-600 hover:text-gray-800 px-4 py-2">
                    {{ __('extracted.effacer_les_filtres') }}
                </a>
            </div>
        </form>
    </div>

    <!-- Stations Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($stations as $station)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
            <!-- Station Header -->
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">{{ $station->name }}</h3>
                        <p class="text-sm text-gray-600 mt-1">{{ $station->city }}, {{ $station->address }}</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="px-2 py-1 text-xs font-medium rounded-full
                            @if($station->status == 'active') bg-green-100 text-green-800
                            @elseif($station->status == 'maintenance') bg-yellow-100 text-yellow-800
                            @elseif($station->status == 'inactive') bg-red-100 text-red-800
                            @else bg-gray-100 text-gray-800
                            @endif">
                            {{ ucfirst($station->status) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Station Details -->
            <div class="p-6">
                <div class="space-y-3">
                    <div class="flex items-center text-sm text-gray-600">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span class="font-medium">{{ __('extracted.type') }}:</span>
                        <span class="ml-1 capitalize">{{ $station->type }}</span>
                    </div>

                    @if($station->group)
                    <div class="flex items-center text-sm text-gray-600">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <span class="font-medium">{{ __('extracted.groupe') }}:</span>
                        <span class="ml-1">{{ $station->group->name ?? $station->group->title }}</span>
                    </div>
                    @endif

                    <div class="flex items-center text-sm text-gray-600">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <span class="font-medium">{{ __('extracted.points_de_charge') }}:</span>
                        <span class="ml-1">{{ $station->chargingPoints->count() }}/2</span>
                    </div>
                </div>

                <!-- Charging Points List -->
                @if($station->chargingPoints->count() > 0)
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <h4 class="text-sm font-medium text-gray-900 mb-2">{{ __('extracted.points_de_charge') }}:</h4>
                    <div class="space-y-2">
                        @foreach($station->chargingPoints as $chargingPoint)
                        <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg">
                            <div class="flex items-center">
                                <div class="w-2 h-2 rounded-full mr-2
                                    @if($chargingPoint->status == 'online') bg-green-500
                                    @elseif($chargingPoint->status == 'offline') bg-red-500
                                    @else bg-yellow-500
                                    @endif">
                                </div>
                                <span class="text-sm font-medium text-gray-900">{{ $chargingPoint->name }}</span>
                            </div>
                            <span class="text-xs text-gray-500">{{ $chargingPoint->power_output ?? 'N/A' }} kW</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @else
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <div class="text-center py-4">
                        <svg class="w-8 h-8 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <p class="text-sm text-gray-500">{{ __('extracted.aucun_point_de_charge_assigné') }}</p>
                        <button @click="openAddChargingPointsModal({{ $station->id }})" 
                                class="mt-2 text-green-600 hover:text-green-700 text-sm font-medium">
                            {{ __('extracted.ajouter_des_points_de_charge') }}
                        </button>
                    </div>
                </div>
                @endif
            </div>

            <!-- Actions -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-between items-center">
                <div class="flex space-x-2">
                    <a href="{{ route('stations.show', $station) }}" 
                       class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        {{ __('extracted.voir_details') }}
                    </a>
                    <a href="{{ route('stations.edit', $station) }}" 
                       class="text-gray-600 hover:text-gray-800 text-sm font-medium">
                        {{ __('extracted.modifier') }}
                    </a>
                </div>
                
                @if($station->chargingPoints->count() < 2)
                <button @click="openAddChargingPointsModal({{ $station->id }})" 
                        class="text-green-600 hover:text-green-800 text-sm font-medium">
                    {{ __('extracted.ajouter_point') }}
                </button>
                @endif
            </div>
        </div>
        @empty
        <div class="col-span-full">
            <div class="text-center py-12">
                <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('extracted.aucune_station_trouvee') }}</h3>
                <p class="text-gray-600 mb-6">{{ __('extracted.commencez_par_creer_premiere_station') }}</p>
                <a href="{{ route('stations.create') }}" 
                   class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg inline-flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    {{ __('extracted.creer_une_station') }}
                </a>
            </div>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($stations->hasPages())
    <div class="mt-8">
        {{ $stations->links() }}
    </div>
    @endif

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
                                {{ __('extracted.points_de_charge_disponibles') }} ({{ __('extracted.max') }} 2)
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
function stationManager() {
    return {
        showAddChargingPointsModal: false,
        selectedStationId: null,
        selectedChargingPoints: [],
        availableChargingPoints: [],

        openAddChargingPointsModal(stationId) {
            this.selectedStationId = stationId;
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

            if (this.selectedChargingPoints.length > 2) {
                alert('{{ __('extracted.selectionner_max_points_charge', ['max' => 2]) }}');
                return;
            }

            try {
                const response = await fetch(`/stations/${this.selectedStationId}/charging-points`, {
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
        }
    }
}
</script>
@endsection
