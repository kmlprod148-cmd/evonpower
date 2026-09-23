@extends('layouts.app')

@section('title', 'Statistiques Détaillées')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Statistiques Détaillées</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Analysez vos performances et votre activité</p>
    </div>

    <!-- Navigation -->
    <div class="mb-6">
        <nav class="flex space-x-4">
            <a href="{{ route('operator.profile') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">
                <i class="fas fa-user mr-1"></i>Profil
            </a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-600 dark:text-gray-400">Statistiques</span>
        </nav>
    </div>

    <!-- Statistiques des points de charge -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900">
                    <i class="fas fa-charging-station text-blue-600 dark:text-blue-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Points de Charge</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['charging_points']['total'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 dark:bg-green-900">
                    <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Points Actifs</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['charging_points']['active'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-red-100 dark:bg-red-900">
                    <i class="fas fa-times-circle text-red-600 dark:text-red-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Points Inactifs</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['charging_points']['inactive'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900">
                    <i class="fas fa-calendar-plus text-purple-600 dark:text-purple-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Ajoutés ce Mois</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['charging_points']['this_month'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques des transactions -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-indigo-100 dark:bg-indigo-900">
                    <i class="fas fa-exchange-alt text-indigo-600 dark:text-indigo-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Transactions</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['transactions']['total'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 dark:bg-yellow-900">
                    <i class="fas fa-calendar text-yellow-600 dark:text-yellow-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Ce Mois</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['transactions']['this_month'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-orange-100 dark:bg-orange-900">
                    <i class="fas fa-clock text-orange-600 dark:text-orange-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Cette Semaine</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['transactions']['this_week'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 dark:bg-green-900">
                    <i class="fas fa-euro-sign text-green-600 dark:text-green-400 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Revenus Totaux</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['transactions']['total_revenue'], 2) }}€</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphiques et analyses -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Revenus mensuels -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Revenus Mensuels</h3>
            <div class="text-center">
                <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ number_format($stats['transactions']['monthly_revenue'], 2) }}€</p>
                <p class="text-sm text-gray-600 dark:text-gray-400">Ce mois-ci</p>
            </div>
        </div>

        <!-- Réservations -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Réservations</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 dark:text-gray-400">Total</span>
                    <span class="font-bold text-gray-900 dark:text-white">{{ $stats['reservations']['total'] }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 dark:text-gray-400">Actives</span>
                    <span class="font-bold text-green-600 dark:text-green-400">{{ $stats['reservations']['active'] }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 dark:text-gray-400">Terminées</span>
                    <span class="font-bold text-blue-600 dark:text-blue-400">{{ $stats['reservations']['completed'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="flex justify-between items-center">
        <a href="{{ route('operator.profile') }}" 
           class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Retour au profil
        </a>
        <a href="{{ route('operator.activity') }}" 
           class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors">
            <i class="fas fa-history mr-2"></i>Voir l'activité
        </a>
    </div>
</div>
@endsection
