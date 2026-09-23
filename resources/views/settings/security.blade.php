@extends('layouts.app')

@section('content')
    <div class="bg-white rounded-lg shadow-sm p-6">
        <!-- Page Header -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-semibold text-gray-800">Paramètres de sécurité</h1>
        </div>

        <!-- Settings Navigation -->
        <div class="border-b border-gray-200 mb-6">
            <nav class="flex -mb-px space-x-8">
                <a href="{{ route('settings.index') }}" class="py-4 px-1 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium text-sm border-b-2">
                    Général
                </a>
                <a href="{{ route('settings.security') }}" class="py-4 px-1 border-green-500 text-green-600 font-medium text-sm border-b-2">
                    Sécurité
                </a>
                <a href="#" class="py-4 px-1 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium text-sm border-b-2">
                    Notifications
                </a>
                <a href="#" class="py-4 px-1 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium text-sm border-b-2">
                    API
                </a>
            </nav>
        </div>

        <!-- Password Change Form -->
        <div class="max-w-xl">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Changer le mot de passe</h2>
            
            <form action="{{ Route::has('settings.update-password') ? route('settings.update-password') : '#' }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="space-y-4">
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700">Mot de passe actuel</label>
                        <input type="password" name="current_password" id="current_password" class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    </div>
                    
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Nouveau mot de passe</label>
                        <input type="password" name="password" id="password" class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    </div>
                    
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirmer le nouveau mot de passe</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                    </div>
                </div>
                
                <div class="mt-6">
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Mettre à jour le mot de passe
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Two Factor Authentication -->
        <div class="mt-10 pt-10 border-t border-gray-200 max-w-xl">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Authentification à deux facteurs</h2>
            
            <p class="text-sm text-gray-500 mb-4">
                Ajoutez une couche supplémentaire de sécurité à votre compte en activant l'authentification à deux facteurs.
            </p>
            
            <button type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                Activer l'authentification à deux facteurs
            </button>
        </div>
        
        <!-- Session Management -->
        <div class="mt-10 pt-10 border-t border-gray-200 max-w-xl">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Sessions actives</h2>
            
            <p class="text-sm text-gray-500 mb-4">
                Consultez et gérez vos sessions actives sur d'autres navigateurs et appareils.
            </p>
            
            <div class="space-y-4">
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900">Session actuelle</p>
                            <p class="text-xs text-gray-500">Paris, France · Chrome · Windows</p>
                        </div>
                    </div>
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                        Actif
                    </span>
                </div>
                
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900">iPhone</p>
                            <p class="text-xs text-gray-500">Lyon, France · Safari · iOS</p>
                        </div>
                    </div>
                    <button class="text-sm text-red-600 hover:text-red-900">
                        Déconnecter
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection