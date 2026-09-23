@extends('layouts.app')

@section('title', 'Modifier le Charging Point')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Modifier le Charging Point</h1>
            <p class="mt-1 text-sm text-gray-600">
                Modifiez les informations du charging point.
            </p>
        </div>

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('admin.charging-points.update', $chargingPoint) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')
            
            <!-- Informations de base -->
            <div class="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Informations de base</h3>
                
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Nom du charging point *</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $chargingPoint->name) }}" 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('name') border-red-300 @enderror" required>
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="location" class="block text-sm font-medium text-gray-700">Localisation *</label>
                        <input type="text" name="location" id="location" value="{{ old('location', $chargingPoint->location) }}" 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('location') border-red-300 @enderror" required>
                        @error('location')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-6">
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" id="description" rows="3" 
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('description') border-red-300 @enderror">{{ old('description', $chargingPoint->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Coordonnées géographiques -->
            <div class="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Coordonnées géographiques</h3>
                
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="latitude" class="block text-sm font-medium text-gray-700">Latitude *</label>
                        <input type="number" name="latitude" id="latitude" value="{{ old('latitude', $chargingPoint->latitude) }}" 
                               step="any" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('latitude') border-red-300 @enderror" required>
                        @error('latitude')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="longitude" class="block text-sm font-medium text-gray-700">Longitude *</label>
                        <input type="number" name="longitude" id="longitude" value="{{ old('longitude', $chargingPoint->longitude) }}" 
                               step="any" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('longitude') border-red-300 @enderror" required>
                        @error('longitude')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Configuration technique -->
            <div class="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Configuration technique</h3>
                
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="max_power" class="block text-sm font-medium text-gray-700">Puissance maximale (kW) *</label>
                        <input type="number" name="max_power" id="max_power" value="{{ old('max_power', $chargingPoint->max_power) }}" 
                               step="0.1" min="0" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('max_power') border-red-300 @enderror" required>
                        @error('max_power')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="connector_type" class="block text-sm font-medium text-gray-700">Type de connecteur *</label>
                        <select name="connector_type" id="connector_type" 
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('connector_type') border-red-300 @enderror" required>
                            <option value="">Sélectionnez un type</option>
                            <option value="Type 2" {{ old('connector_type', $chargingPoint->connector_type) == 'Type 2' ? 'selected' : '' }}>Type 2</option>
                            <option value="CCS" {{ old('connector_type', $chargingPoint->connector_type) == 'CCS' ? 'selected' : '' }}>CCS</option>
                            <option value="CHAdeMO" {{ old('connector_type', $chargingPoint->connector_type) == 'CHAdeMO' ? 'selected' : '' }}>CHAdeMO</option>
                            <option value="Tesla" {{ old('connector_type', $chargingPoint->connector_type) == 'Tesla' ? 'selected' : '' }}>Tesla</option>
                        </select>
                        @error('connector_type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Assignations -->
            <div class="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Assignations</h3>
                
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="user_id" class="block text-sm font-medium text-gray-700">Propriétaire *</label>
                        <select name="user_id" id="user_id" 
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('user_id') border-red-300 @enderror" required>
                            <option value="">Sélectionnez un propriétaire</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('user_id', $chargingPoint->user_id) == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }} ({{ $user->email }}) - {{ ucfirst($user->role) }}
                                </option>
                            @endforeach
                        </select>
                        @error('user_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="pricing_plan_id" class="block text-sm font-medium text-gray-700">Plan tarifaire *</label>
                        <select name="pricing_plan_id" id="pricing_plan_id" 
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('pricing_plan_id') border-red-300 @enderror" required>
                            <option value="">Sélectionnez un plan</option>
                            @foreach($pricingPlans as $plan)
                                <option value="{{ $plan->id }}" {{ old('pricing_plan_id', $chargingPoint->pricing_plan_id) == $plan->id ? 'selected' : '' }}>
                                    {{ $plan->name }} - {{ $plan->base_price }}€
                                </option>
                            @endforeach
                        </select>
                        @error('pricing_plan_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-3">
                    <div>
                        <label for="partner_id" class="block text-sm font-medium text-gray-700">Partenaire</label>
                        <select name="partner_id" id="partner_id" 
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('partner_id') border-red-300 @enderror">
                            <option value="">Aucun partenaire</option>
                            @foreach($partners as $partner)
                                <option value="{{ $partner->id }}" {{ old('partner_id', $chargingPoint->partner_id) == $partner->id ? 'selected' : '' }}>
                                    {{ $partner->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('partner_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="group_id" class="block text-sm font-medium text-gray-700">Groupe</label>
                        <select name="group_id" id="group_id" 
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('group_id') border-red-300 @enderror">
                            <option value="">Aucun groupe</option>
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}" {{ old('group_id', $chargingPoint->group_id) == $group->id ? 'selected' : '' }}>
                                    {{ $group->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('group_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="station_id" class="block text-sm font-medium text-gray-700">Station</label>
                        <select name="station_id" id="station_id" 
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('station_id') border-red-300 @enderror">
                            <option value="">Aucune station</option>
                            @foreach($stations as $station)
                                <option value="{{ $station->id }}" {{ old('station_id', $chargingPoint->station_id) == $station->id ? 'selected' : '' }}>
                                    {{ $station->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('station_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Paramètres -->
            <div class="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Paramètres</h3>
                
                <div class="space-y-4">
                    <div class="flex items-center">
                        <input type="checkbox" name="is_public" id="is_public" value="1" 
                               {{ old('is_public', $chargingPoint->is_public) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_public" class="ml-2 block text-sm text-gray-900">
                            Charging point public (visible par d'autres utilisateurs)
                        </label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" id="is_active" value="1" 
                               {{ old('is_active', $chargingPoint->is_active) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_active" class="ml-2 block text-sm text-gray-900">
                            Charging point actif
                        </label>
                    </div>
                </div>
            </div>

            <!-- Boutons d'action -->
            <div class="flex justify-end space-x-3">
                <a href="{{ route('admin.charging-points.show', $chargingPoint) }}" 
                   class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Annuler
                </a>
                <button type="submit" 
                        class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Mettre à jour
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
