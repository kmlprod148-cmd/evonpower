@extends('layouts.app')

@section('title', 'Paramètres du Profil')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Paramètres du Profil</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Personnalisez vos préférences et paramètres</p>
    </div>

    <!-- Navigation -->
    <div class="mb-6">
        <nav class="flex space-x-4">
            <a href="{{ route('operator.profile') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">
                <i class="fas fa-user mr-1"></i>Profil
            </a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-600 dark:text-gray-400">Paramètres</span>
        </nav>
    </div>

    <div class="max-w-4xl mx-auto">
        <form method="POST" action="{{ route('operator.settings.update') }}">
            @csrf
            @method('PUT')

            <!-- Notifications -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-bell mr-2"></i>Préférences de notification
                </h3>
                
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Notifications par email</label>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Recevoir des notifications par email</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="email_notifications" value="1" 
                                   {{ old('email_notifications', $user->email_notifications ?? true) ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between">
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Notifications SMS</label>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Recevoir des notifications par SMS</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="sms_notifications" value="1" 
                                   {{ old('sms_notifications', $user->sms_notifications ?? false) ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Préférences de notification détaillées -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-cog mr-2"></i>Types de notifications
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex items-center">
                        <input type="checkbox" name="notification_preferences[new_transaction]" value="1" 
                               {{ old('notification_preferences.new_transaction', $user->notification_preferences['new_transaction'] ?? true) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">Nouvelles transactions</label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="notification_preferences[reservation_created]" value="1" 
                               {{ old('notification_preferences.reservation_created', $user->notification_preferences['reservation_created'] ?? true) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">Nouvelles réservations</label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="notification_preferences[charging_point_status]" value="1" 
                               {{ old('notification_preferences.charging_point_status', $user->notification_preferences['charging_point_status'] ?? true) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">Changements de statut des points de charge</label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="notification_preferences[system_alerts]" value="1" 
                               {{ old('notification_preferences.system_alerts', $user->notification_preferences['system_alerts'] ?? true) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">Alertes système</label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="notification_preferences[maintenance_reminders]" value="1" 
                               {{ old('notification_preferences.maintenance_reminders', $user->notification_preferences['maintenance_reminders'] ?? false) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">Rappels de maintenance</label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="notification_preferences[revenue_reports]" value="1" 
                               {{ old('notification_preferences.revenue_reports', $user->notification_preferences['revenue_reports'] ?? false) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">Rapports de revenus</label>
                    </div>
                </div>
            </div>

            <!-- Widgets du dashboard -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-th-large mr-2"></i>Widgets du dashboard
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="flex items-center">
                        <input type="checkbox" name="dashboard_widgets[statistics]" value="1" 
                               {{ old('dashboard_widgets.statistics', $user->dashboard_widgets['statistics'] ?? true) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">Statistiques générales</label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="dashboard_widgets[recent_activity]" value="1" 
                               {{ old('dashboard_widgets.recent_activity', $user->dashboard_widgets['recent_activity'] ?? true) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">Activité récente</label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="dashboard_widgets[charging_points]" value="1" 
                               {{ old('dashboard_widgets.charging_points', $user->dashboard_widgets['charging_points'] ?? true) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">Points de charge</label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="dashboard_widgets[transactions]" value="1" 
                               {{ old('dashboard_widgets.transactions', $user->dashboard_widgets['transactions'] ?? true) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">Transactions</label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="dashboard_widgets[revenue_chart]" value="1" 
                               {{ old('dashboard_widgets.revenue_chart', $user->dashboard_widgets['revenue_chart'] ?? false) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">Graphique des revenus</label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="dashboard_widgets[weather]" value="1" 
                               {{ old('dashboard_widgets.weather', $user->dashboard_widgets['weather'] ?? false) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">Météo</label>
                    </div>
                </div>
            </div>

            <!-- Informations système (lecture seule) -->
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-info-circle mr-2"></i>Informations système
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type de compte</label>
                        <p class="text-gray-900 dark:text-white">Opérateur Système</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Intégrateur</label>
                        <p class="text-gray-900 dark:text-white">{{ $user->integrator->name ?? 'Non assigné' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Compte créé le</label>
                        <p class="text-gray-900 dark:text-white">{{ $user->created_at->format('d/m/Y à H:i') }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Dernière connexion</label>
                        <p class="text-gray-900 dark:text-white">{{ $user->last_login_at ? $user->last_login_at->format('d/m/Y à H:i') : 'Jamais' }}</p>
                    </div>
                </div>
            </div>

            <!-- Boutons d'action -->
            <div class="flex items-center justify-between">
                <a href="{{ route('operator.profile') }}" 
                   class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Annuler
                </a>
                <button type="submit" 
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition-colors">
                    <i class="fas fa-save mr-2"></i>Enregistrer les paramètres
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
