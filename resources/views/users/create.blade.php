@extends('layouts.app')

@section('content')
<div class="users-create-container">
    <div class="flex items-center mb-6">
        <a href="{{ route('admin.users.index') }}" class="text-gray-500 hover:text-gray-700 mr-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
        </a>
        <h1 class="text-2xl font-medium">Ajouter un utilisateur</h1>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="text-lg font-medium mb-6">Informations de l'utilisateur</div>
        
        @if ($errors->any())
        <div class="bg-red-50 text-red-500 p-4 rounded-lg mb-6">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif
        
        <form action="{{ route('admin.users.store') }}" method="POST">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Personal Information Section -->
                <div class="col-span-2 border-b border-gray-200 pb-4 mb-4">
                    <h3 class="text-md font-medium text-gray-700 mb-4">Informations personnelles</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="nom" class="block text-sm font-medium text-gray-700 mb-1">Nom*</label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div>
                            <label for="prenom" class="block text-sm font-medium text-gray-700 mb-1">Prénom*</label>
                            <input type="text" name="prenom" id="prenom" value="{{ old('prenom') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email*</label>
                            <input type="email" name="email" id="email" value="{{ old('email') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div>
                            <label for="telephone" class="block text-sm font-medium text-gray-700 mb-1">Téléphone*</label>
                            <input type="tel" name="telephone" id="telephone" value="{{ old('telephone') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                    </div>
                </div>
                
                <!-- Company Information Section -->
                <div class="col-span-2 border-b border-gray-200 pb-4 mb-4">
                    <h3 class="text-md font-medium text-gray-700 mb-4">Informations professionnelles</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="raison_social" class="block text-sm font-medium text-gray-700 mb-1">Raison sociale</label>
                            <input type="text" name="raison_social" id="raison_social" value="{{ old('raison_social') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div>
                            <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Rôle</label>
                            <select name="role" id="role" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                                <option value="">Sélectionner un rôle</option>
                                <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Administrateur</option>
                                <option value="user" {{ old('role') == 'user' ? 'selected' : '' }}>Utilisateur</option>
                                <option value="manager" {{ old('role') == 'manager' ? 'selected' : '' }}>Gestionnaire</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Access Information Section -->
                <div class="col-span-2 border-b border-gray-200 pb-4 mb-4">
                    <h3 class="text-md font-medium text-gray-700 mb-4">Informations d'accès</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Mot de passe*</label>
                            <input type="password" name="password" id="password" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirmer le mot de passe*</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        </div>
                    </div>
                </div>
                
                <!-- Additional Information and Permissions -->
                <div class="col-span-2">
                    <h3 class="text-md font-medium text-gray-700 mb-4">Permissions</h3>
                    
                    <div class="mb-4">
                        <p class="text-sm text-gray-500 mb-2">Sélectionnez les modules auxquels l'utilisateur aura accès :</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="flex items-center">
                                <input type="checkbox" name="permissions[]" id="permission_users" value="users" {{ in_array('users', old('permissions', [])) ? 'checked' : '' }} class="h-4 w-4 text-green-500 focus:ring-green-500 border-gray-300 rounded">
                                <label for="permission_users" class="ml-2 block text-sm text-gray-700">Utilisateurs</label>
                            </div>
                            
                            <div class="flex items-center">
                                <input type="checkbox" name="permissions[]" id="permission_charging_points" value="charging_points" {{ in_array('charging_points', old('permissions', [])) ? 'checked' : '' }} class="h-4 w-4 text-green-500 focus:ring-green-500 border-gray-300 rounded">
                                <label for="permission_charging_points" class="ml-2 block text-sm text-gray-700">Points de recharge</label>
                            </div>
                            
                            <div class="flex items-center">
                                <input type="checkbox" name="permissions[]" id="permission_stations" value="stations" {{ in_array('stations', old('permissions', [])) ? 'checked' : '' }} class="h-4 w-4 text-green-500 focus:ring-green-500 border-gray-300 rounded">
                                <label for="permission_stations" class="ml-2 block text-sm text-gray-700">Stations</label>
                            </div>
                            
                            <div class="flex items-center">
                                <input type="checkbox" name="permissions[]" id="permission_partners" value="partners" {{ in_array('partners', old('permissions', [])) ? 'checked' : '' }} class="h-4 w-4 text-green-500 focus:ring-green-500 border-gray-300 rounded">
                                <label for="permission_partners" class="ml-2 block text-sm text-gray-700">Partenaires</label>
                            </div>
                            
                            <div class="flex items-center">
                                <input type="checkbox" name="permissions[]" id="permission_integrators" value="integrators" {{ in_array('integrators', old('permissions', [])) ? 'checked' : '' }} class="h-4 w-4 text-green-500 focus:ring-green-500 border-gray-300 rounded">
                                <label for="permission_integrators" class="ml-2 block text-sm text-gray-700">Intégrateurs</label>
                            </div>
                            
                            <div class="flex items-center">
                                <input type="checkbox" name="permissions[]" id="permission_reports" value="reports" {{ in_array('reports', old('permissions', [])) ? 'checked' : '' }} class="h-4 w-4 text-green-500 focus:ring-green-500 border-gray-300 rounded">
                                <label for="permission_reports" class="ml-2 block text-sm text-gray-700">Rapports</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-start mb-6">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="active" id="active" value="1" {{ old('active', '1') == '1' ? 'checked' : '' }} class="h-4 w-4 text-green-500 focus:ring-green-500 border-gray-300 rounded">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="active" class="font-medium text-gray-700">Activer ce compte</label>
                            <p class="text-gray-500">L'utilisateur pourra se connecter immédiatement si activé.</p>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-end space-x-3">
                        <a href="{{ route('admin.users.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Annuler
                        </a>
                        <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded-md text-sm font-medium hover:bg-green-600">
                            Enregistrer
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
<style>
    body {
        background-color: #f9fafb;
    }
</style>
@endpush