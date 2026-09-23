@extends('layouts.app')

@section('title', 'Créer un Charging Point')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-emerald-50/30 dark:from-gray-900 dark:via-gray-900 dark:to-emerald-900/10">
    <!-- Header with back button and title -->
    <div class="bg-white dark:bg-gray-900 shadow-sm border-b border-gray-200 dark:border-gray-800">
        <div class="max-w-7xl mx-auto px-4 py-6">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.charging-points.index') }}" class="p-2 rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">
                    <svg class="h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Créer un Charging Point</h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Créez un nouveau charging point et assignez-le à un utilisateur.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <main class="max-w-7xl mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">

            @if(session('error'))
                <div class="mb-6 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 text-red-700 dark:text-red-300 p-4 rounded-lg shadow-sm" role="alert">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium">{{ session('error') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <form action="{{ route('admin.charging-points.store') }}" method="POST" class="space-y-6">
                @csrf
                
                <!-- Informations de base -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-xl shadow-gray-900/5">
                    <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-800">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center">
                                <i class="fas fa-info-circle text-white"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">Informations de base</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Renseignez les informations de base du charging point</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-6">
                
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nom du charging point *</label>
                                <input type="text" name="name" id="name" value="{{ old('name') }}" 
                                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-800 dark:text-gray-100 @error('name') border-red-300 @enderror" 
                                       placeholder="Entrez le nom du charging point" required>
                                @error('name')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="location" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Localisation *</label>
                                <input type="text" name="location" id="location" value="{{ old('location') }}" 
                                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-800 dark:text-gray-100 @error('location') border-red-300 @enderror" 
                                       placeholder="Entrez la localisation" required>
                                @error('location')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-6">
                            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description</label>
                            <textarea name="description" id="description" rows="3" 
                                      class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-800 dark:text-gray-100 @error('description') border-red-300 @enderror"
                                      placeholder="Description du charging point (optionnel)">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Coordonnées géographiques -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-xl shadow-gray-900/5">
                    <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-800">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center">
                                <i class="fas fa-map-marker-alt text-white"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">Coordonnées géographiques</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Définissez la position géographique du charging point</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label for="latitude" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Latitude *</label>
                                <input type="number" name="latitude" id="latitude" value="{{ old('latitude') }}" 
                                       step="any" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-800 dark:text-gray-100 @error('latitude') border-red-300 @enderror" 
                                       placeholder="Ex: 48.8566" required>
                                @error('latitude')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="longitude" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Longitude *</label>
                                <input type="number" name="longitude" id="longitude" value="{{ old('longitude') }}" 
                                       step="any" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-800 dark:text-gray-100 @error('longitude') border-red-300 @enderror" 
                                       placeholder="Ex: 2.3522" required>
                                @error('longitude')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Configuration technique -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-xl shadow-gray-900/5">
                    <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-800">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center">
                                <i class="fas fa-cogs text-white"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">Configuration technique</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Spécifiez les caractéristiques techniques du charging point</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label for="max_power" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Puissance maximale (kW) *</label>
                                <input type="number" name="max_power" id="max_power" value="{{ old('max_power') }}" 
                                       step="0.1" min="0" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-800 dark:text-gray-100 @error('max_power') border-red-300 @enderror" 
                                       placeholder="Ex: 22.0" required>
                                @error('max_power')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="connector_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Type de connecteur *</label>
                                <select name="connector_type" id="connector_type" 
                                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-800 dark:text-gray-100 @error('connector_type') border-red-300 @enderror" required>
                                    <option value="">Sélectionnez un type</option>
                                    <option value="Type 2" {{ old('connector_type') == 'Type 2' ? 'selected' : '' }}>Type 2</option>
                                    <option value="CCS" {{ old('connector_type') == 'CCS' ? 'selected' : '' }}>CCS</option>
                                    <option value="CHAdeMO" {{ old('connector_type') == 'CHAdeMO' ? 'selected' : '' }}>CHAdeMO</option>
                                    <option value="Tesla" {{ old('connector_type') == 'Tesla' ? 'selected' : '' }}>Tesla</option>
                                </select>
                                @error('connector_type')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Assignations -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-xl shadow-gray-900/5">
                    <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-800">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl flex items-center justify-center">
                                <i class="fas fa-users text-white"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">Assignations</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Assignez le charging point à un utilisateur et configurez les relations</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label for="user_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Propriétaire *</label>
                                <select name="user_id" id="user_id" 
                                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-800 dark:text-gray-100 @error('user_id') border-red-300 @enderror" required>
                                    <option value="">Sélectionnez un propriétaire</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }} ({{ $user->email }}) - {{ ucfirst($user->role) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('user_id')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="pricing_plan_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Plan tarifaire *</label>
                                <select name="pricing_plan_id" id="pricing_plan_id" 
                                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-800 dark:text-gray-100 @error('pricing_plan_id') border-red-300 @enderror" required>
                                    <option value="">Sélectionnez un plan</option>
                                    @foreach($pricingPlans as $plan)
                                        <option value="{{ $plan->id }}" {{ old('pricing_plan_id') == $plan->id ? 'selected' : '' }}>
                                            {{ $plan->name }} - {{ $plan->base_price }}€
                                        </option>
                                    @endforeach
                                </select>
                                @error('pricing_plan_id')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-3">
                            <div>
                                <label for="partner_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Partenaire</label>
                                <select name="partner_id" id="partner_id" 
                                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-800 dark:text-gray-100 @error('partner_id') border-red-300 @enderror">
                                    <option value="">Aucun partenaire</option>
                                    @foreach($partners as $partner)
                                        <option value="{{ $partner->id }}" {{ old('partner_id') == $partner->id ? 'selected' : '' }}>
                                            {{ $partner->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('partner_id')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="group_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Groupe</label>
                                <select name="group_id" id="group_id" 
                                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-800 dark:text-gray-100 @error('group_id') border-red-300 @enderror">
                                    <option value="">Aucun groupe</option>
                                    @foreach($groups as $group)
                                        <option value="{{ $group->id }}" {{ old('group_id') == $group->id ? 'selected' : '' }}>
                                            {{ $group->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('group_id')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="station_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Station</label>
                                <select name="station_id" id="station_id" 
                                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-800 dark:text-gray-100 @error('station_id') border-red-300 @enderror">
                                    <option value="">Aucune station</option>
                                    @foreach($stations as $station)
                                        <option value="{{ $station->id }}" {{ old('station_id') == $station->id ? 'selected' : '' }}>
                                            {{ $station->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('station_id')
                                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Paramètres -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-xl shadow-gray-900/5">
                    <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-800">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl flex items-center justify-center">
                                <i class="fas fa-sliders-h text-white"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">Paramètres</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Configurez la visibilité et le statut du charging point</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="space-y-6">
                            <div class="flex items-center p-4 bg-gray-50 dark:bg-gray-800 rounded-xl">
                                <input type="checkbox" name="is_public" id="is_public" value="1" 
                                       {{ old('is_public') ? 'checked' : '' }}
                                       class="h-5 w-5 text-emerald-600 focus:ring-emerald-500 border-gray-300 rounded">
                                <label for="is_public" class="ml-3 block text-sm font-medium text-gray-900 dark:text-gray-100">
                                    Charging point public (visible par d'autres utilisateurs)
                                </label>
                            </div>

                            <div class="flex items-center p-4 bg-gray-50 dark:bg-gray-800 rounded-xl">
                                <input type="checkbox" name="is_active" id="is_active" value="1" 
                                       {{ old('is_active', true) ? 'checked' : '' }}
                                       class="h-5 w-5 text-emerald-600 focus:ring-emerald-500 border-gray-300 rounded">
                                <label for="is_active" class="ml-3 block text-sm font-medium text-gray-900 dark:text-gray-100">
                                    Charging point actif
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Boutons d'action -->
                <div class="flex justify-end space-x-4 pt-6">
                    <a href="{{ route('admin.charging-points.index') }}" 
                       class="px-6 py-3 border border-gray-300 dark:border-gray-600 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-colors">
                        Annuler
                    </a>
                    <button type="submit" 
                            class="px-6 py-3 bg-gradient-to-r from-emerald-600 to-emerald-700 text-white rounded-xl text-sm font-medium hover:from-emerald-700 hover:to-emerald-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-all duration-200 shadow-lg hover:shadow-xl">
                        <i class="fas fa-plus mr-2"></i>
                        Créer le Charging Point
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>
@endsection
