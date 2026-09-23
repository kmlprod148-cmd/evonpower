@extends('layouts.app')

@section('content')
<div class="px-4 py-6">
    <div class="mb-6">
        <div class="text-gray-500 text-sm mb-1">Paramètres > Notifications</div>
        <h1 class="text-2xl font-medium">Paramètres de notifications</h1>
    </div>

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
        <p>{{ session('success') }}</p>
    </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <form action="{{ route('settings.notifications.update') }}" method="POST">
            @csrf
            @method('PUT')
            
            <!-- Email Notifications Section -->
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-medium mb-4">Notifications par email</h2>
                <p class="text-sm text-gray-600 mb-6">Choisissez les types de notifications que vous souhaitez recevoir par email.</p>
                
                <div class="space-y-4">
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="email_security_alerts" id="email_security_alerts" 
                                class="focus:ring-green-500 h-4 w-4 text-green-600 border-gray-300 rounded" 
                                {{ ($notifications['email_security_alerts'] ?? true) ? 'checked' : '' }}>
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="email_security_alerts" class="font-medium text-gray-700">Alertes de sécurité</label>
                            <p class="text-gray-500">Notifications concernant les connexions suspectes et les modifications de sécurité.</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="email_account_updates" id="email_account_updates" 
                                class="focus:ring-green-500 h-4 w-4 text-green-600 border-gray-300 rounded" 
                                {{ ($notifications['email_account_updates'] ?? true) ? 'checked' : '' }}>
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="email_account_updates" class="font-medium text-gray-700">Mises à jour du compte</label>
                            <p class="text-gray-500">Notifications concernant les modifications de votre profil ou de vos paramètres.</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="email_transactions" id="email_transactions" 
                                class="focus:ring-green-500 h-4 w-4 text-green-600 border-gray-300 rounded" 
                                {{ ($notifications['email_transactions'] ?? true) ? 'checked' : '' }}>
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="email_transactions" class="font-medium text-gray-700">Transactions</label>
                            <p class="text-gray-500">Notifications pour les nouvelles transactions, paiements et factures.</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="email_stations" id="email_stations" 
                                class="focus:ring-green-500 h-4 w-4 text-green-600 border-gray-300 rounded" 
                                {{ ($notifications['email_stations'] ?? true) ? 'checked' : '' }}>
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="email_stations" class="font-medium text-gray-700">Stations</label>
                            <p class="text-gray-500">Notifications concernant l'état de vos stations et les alertes de maintenance.</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="email_marketing" id="email_marketing" 
                                class="focus:ring-green-500 h-4 w-4 text-green-600 border-gray-300 rounded" 
                                {{ ($notifications['email_marketing'] ?? false) ? 'checked' : '' }}>
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="email_marketing" class="font-medium text-gray-700">Marketing</label>
                            <p class="text-gray-500">Promotions, nouveautés et informations sur nos produits et services.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- In-App Notifications Section -->
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-medium mb-4">Notifications dans l'application</h2>
                <p class="text-sm text-gray-600 mb-6">Choisissez les types de notifications que vous souhaitez recevoir dans l'application.</p>
                
                <div class="space-y-4">
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="app_security_alerts" id="app_security_alerts" 
                                class="focus:ring-green-500 h-4 w-4 text-green-600 border-gray-300 rounded" 
                                {{ ($notifications['app_security_alerts'] ?? true) ? 'checked' : '' }}>
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="app_security_alerts" class="font-medium text-gray-700">Alertes de sécurité</label>
                            <p class="text-gray-500">Alertes concernant les connexions suspectes et les modifications de sécurité.</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="app_account_updates" id="app_account_updates" 
                                class="focus:ring-green-500 h-4 w-4 text-green-600 border-gray-300 rounded" 
                                {{ ($notifications['app_account_updates'] ?? true) ? 'checked' : '' }}>
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="app_account_updates" class="font-medium text-gray-700">Mises à jour du compte</label>
                            <p class="text-gray-500">Alertes concernant les modifications de votre profil ou de vos paramètres.</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="app_transactions" id="app_transactions" 
                                class="focus:ring-green-500 h-4 w-4 text-green-600 border-gray-300 rounded" 
                                {{ ($notifications['app_transactions'] ?? true) ? 'checked' : '' }}>
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="app_transactions" class="font-medium text-gray-700">Transactions</label>
                            <p class="text-gray-500">Alertes pour les nouvelles transactions, paiements et factures.</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="app_stations" id="app_stations" 
                                class="focus:ring-green-500 h-4 w-4 text-green-600 border-gray-300 rounded" 
                                {{ ($notifications['app_stations'] ?? true) ? 'checked' : '' }}>
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="app_stations" class="font-medium text-gray-700">Stations</label>
                            <p class="text-gray-500">Alertes concernant l'état de vos stations et les alertes de maintenance.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Notification Frequency Section -->
            <div class="p-6">
                <h2 class="text-lg font-medium mb-4">Fréquence des notifications</h2>
                <p class="text-sm text-gray-600 mb-6">Choisissez à quelle fréquence vous souhaitez recevoir des notifications par email.</p>
                
                <div class="max-w-md">
                    <div class="mb-4">
                        <label for="email_frequency" class="block text-sm font-medium text-gray-700 mb-1">Résumé des activités</label>
                        <select name="email_frequency" id="email_frequency" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50">
                            <option value="realtime" {{ ($notifications['email_frequency'] ?? 'daily') == 'realtime' ? 'selected' : '' }}>En temps réel</option>
                            <option value="daily" {{ ($notifications['email_frequency'] ?? 'daily') == 'daily' ? 'selected' : '' }}>Résumé quotidien</option>
                            <option value="weekly" {{ ($notifications['email_frequency'] ?? 'daily') == 'weekly' ? 'selected' : '' }}>Résumé hebdomadaire</option>
                            <option value="never" {{ ($notifications['email_frequency'] ?? 'daily') == 'never' ? 'selected' : '' }}>Ne pas envoyer</option>
                        </select>
                        <p class="mt-1 text-sm text-gray-500">Cette option concerne uniquement les résumés d'activité, les alertes critiques seront toujours envoyées en temps réel.</p>
                    </div>
                </div>
            </div>
            
            <div class="px-6 py-4 bg-gray-50 text-right">
                <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-500 hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    Enregistrer les préférences
                </button>
            </div>
        </form>
    </div>
</div>
@endsection