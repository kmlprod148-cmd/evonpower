@extends('layouts.app')

@section('title', 'Créer un Plan Tarifaire')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Créer un Plan Tarifaire</h1>
            <p class="mt-1 text-sm text-gray-600">
                Créez un nouveau plan tarifaire pour vos charging points.
            </p>
        </div>

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('operator.pricing-plans.store') }}" method="POST" class="space-y-6">
            @csrf
            
            <!-- Informations de base -->
            <div class="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Informations de base</h3>
                
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Nom du plan *</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('name') border-red-300 @enderror" required>
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="base_price" class="block text-sm font-medium text-gray-700">Prix de base (€) *</label>
                        <input type="number" name="base_price" id="base_price" value="{{ old('base_price') }}" 
                               step="0.01" min="0" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('base_price') border-red-300 @enderror" required>
                        @error('base_price')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-6">
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" id="description" rows="3" 
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('description') border-red-300 @enderror">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Tarification -->
            <div class="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Tarification</h3>
                
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="price_per_kwh" class="block text-sm font-medium text-gray-700">Prix par kWh (€) *</label>
                        <input type="number" name="price_per_kwh" id="price_per_kwh" value="{{ old('price_per_kwh') }}" 
                               step="0.001" min="0" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('price_per_kwh') border-red-300 @enderror" required>
                        @error('price_per_kwh')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="price_per_minute" class="block text-sm font-medium text-gray-700">Prix par minute (€)</label>
                        <input type="number" name="price_per_minute" id="price_per_minute" value="{{ old('price_per_minute') }}" 
                               step="0.001" min="0" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('price_per_minute') border-red-300 @enderror">
                        @error('price_per_minute')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-3">
                    <div>
                        <label for="connection_fee" class="block text-sm font-medium text-gray-700">Frais de connexion (€)</label>
                        <input type="number" name="connection_fee" id="connection_fee" value="{{ old('connection_fee') }}" 
                               step="0.01" min="0" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('connection_fee') border-red-300 @enderror">
                        @error('connection_fee')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="minimum_fee" class="block text-sm font-medium text-gray-700">Frais minimum (€)</label>
                        <input type="number" name="minimum_fee" id="minimum_fee" value="{{ old('minimum_fee') }}" 
                               step="0.01" min="0" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('minimum_fee') border-red-300 @enderror">
                        @error('minimum_fee')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="maximum_fee" class="block text-sm font-medium text-gray-700">Frais maximum (€)</label>
                        <input type="number" name="maximum_fee" id="maximum_fee" value="{{ old('maximum_fee') }}" 
                               step="0.01" min="0" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('maximum_fee') border-red-300 @enderror">
                        @error('maximum_fee')
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
                               {{ old('is_public') ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_public" class="ml-2 block text-sm text-gray-900">
                            Plan public (visible par d'autres utilisateurs)
                        </label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" id="is_active" value="1" 
                               {{ old('is_active', true) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_active" class="ml-2 block text-sm text-gray-900">
                            Plan actif
                        </label>
                    </div>
                </div>
            </div>

            <!-- Boutons d'action -->
            <div class="flex justify-end space-x-3">
                <a href="{{ route('operator.pricing-plans.index') }}" 
                   class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Annuler
                </a>
                <button type="submit" 
                        class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Créer le Plan Tarifaire
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
