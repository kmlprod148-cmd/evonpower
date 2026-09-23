@extends('layouts.app')

@section('title', 'Statistiques Clients')
@section('page-title', 'Statistiques Clients')

@section('content')
<div class="bg-gradient-to-b from-gray-50 to-white dark:from-gray-900 dark:to-gray-950 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Statistiques Clients</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Vue d'ensemble de l'activité et des performances des clients</p>
            </div>
            <div class="flex items-center space-x-4">
                <form method="GET" action="{{ route('admin.clients.statistics') }}" class="flex items-center space-x-2">
                    <input type="date" 
                           name="date_from" 
                           value="{{ $dateFrom }}" 
                           class="px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg dark:bg-gray-800 dark:text-gray-100">
                    <span class="text-gray-500">à</span>
                    <input type="date" 
                           name="date_to" 
                           value="{{ $dateTo }}" 
                           class="px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg dark:bg-gray-800 dark:text-gray-100">
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                        Filtrer
                    </button>
                </form>
            </div>
        </div>

        <!-- Vue d'ensemble -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Clients</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $statistics['overview']['total_clients'] }}</p>
                    </div>
                    <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Clients Actifs</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $statistics['overview']['active_clients'] }}</p>
                    </div>
                    <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Recharges Complétées</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $statistics['recharges']['completed'] }}</p>
                    </div>
                    <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Montant Total</p>
                        <p class="mt-2 text-3xl font-bold text-green-600 dark:text-green-400">{{ number_format($statistics['recharges']['total_amount'], 2) }} €</p>
                    </div>
                    <div class="p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiques de recharges -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Statistiques de Recharges</h2>
                <dl class="space-y-4">
                    <div class="flex justify-between items-center">
                        <dt class="text-sm text-gray-600 dark:text-gray-400">Total</dt>
                        <dd class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $statistics['recharges']['total'] }}</dd>
                    </div>
                    <div class="flex justify-between items-center">
                        <dt class="text-sm text-gray-600 dark:text-gray-400">En attente</dt>
                        <dd class="text-sm font-semibold text-yellow-600 dark:text-yellow-400">{{ $statistics['recharges']['pending'] }}</dd>
                    </div>
                    <div class="flex justify-between items-center">
                        <dt class="text-sm text-gray-600 dark:text-gray-400">Échouées</dt>
                        <dd class="text-sm font-semibold text-red-600 dark:text-red-400">{{ $statistics['recharges']['failed'] }}</dd>
                    </div>
                    <div class="flex justify-between items-center">
                        <dt class="text-sm text-gray-600 dark:text-gray-400">Montant moyen</dt>
                        <dd class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ number_format($statistics['recharges']['average_amount'], 2) }} €</dd>
                    </div>
                    <div class="flex justify-between items-center">
                        <dt class="text-sm text-gray-600 dark:text-gray-400">Montant en attente</dt>
                        <dd class="text-sm font-semibold text-yellow-600 dark:text-yellow-400">{{ number_format($statistics['recharges']['pending_amount'], 2) }} €</dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Par Méthode de Paiement</h2>
                <dl class="space-y-4">
                    @foreach($statistics['recharges']['by_payment_method'] as $method => $data)
                    <div class="flex justify-between items-center">
                        <dt class="text-sm text-gray-600 dark:text-gray-400 capitalize">{{ $method }}</dt>
                        <dd class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                            {{ $data['count'] }} ({{ number_format($data['total'], 2) }} €)
                        </dd>
                    </div>
                    @endforeach
                </dl>
            </div>
        </div>

        <!-- Statistiques de réservations -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Statistiques de Réservations</h2>
                <dl class="space-y-4">
                    <div class="flex justify-between items-center">
                        <dt class="text-sm text-gray-600 dark:text-gray-400">Total</dt>
                        <dd class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $statistics['reservations']['total'] }}</dd>
                    </div>
                    <div class="flex justify-between items-center">
                        <dt class="text-sm text-gray-600 dark:text-gray-400">Complétées</dt>
                        <dd class="text-sm font-semibold text-green-600 dark:text-green-400">{{ $statistics['reservations']['completed'] }}</dd>
                    </div>
                    <div class="flex justify-between items-center">
                        <dt class="text-sm text-gray-600 dark:text-gray-400">Annulées</dt>
                        <dd class="text-sm font-semibold text-red-600 dark:text-red-400">{{ $statistics['reservations']['cancelled'] }}</dd>
                    </div>
                    <div class="flex justify-between items-center">
                        <dt class="text-sm text-gray-600 dark:text-gray-400">Énergie totale (kWh)</dt>
                        <dd class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ number_format($statistics['reservations']['total_energy_kwh'], 2) }}</dd>
                    </div>
                    <div class="flex justify-between items-center">
                        <dt class="text-sm text-gray-600 dark:text-gray-400">Revenus totaux</dt>
                        <dd class="text-sm font-semibold text-green-600 dark:text-green-400">{{ number_format($statistics['reservations']['total_revenue'], 2) }} €</dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Top 10 Clients</h2>
                <div class="space-y-3">
                    @forelse(array_slice($statistics['reservations']['top_clients'], 0, 10) as $client)
                    <div class="flex justify-between items-center pb-3 border-b border-gray-200 dark:border-gray-700 last:border-0">
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $client['user_name'] }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $client['user_email'] }}</div>
                        </div>
                        <div class="text-sm font-semibold text-blue-600 dark:text-blue-400">{{ $client['reservations_count'] }}</div>
                    </div>
                    @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">Aucun client</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Statistiques financières -->
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm p-6 mb-8">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Statistiques Financières</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Total crédité</p>
                    <p class="mt-1 text-2xl font-bold text-green-600 dark:text-green-400">
                        {{ number_format($statistics['financial']['total_credited'], 2) }} €
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Total dépensé</p>
                    <p class="mt-1 text-2xl font-bold text-red-600 dark:text-red-400">
                        {{ number_format($statistics['financial']['total_spent'], 2) }} €
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Flux net</p>
                    <p class="mt-1 text-2xl font-bold {{ $statistics['financial']['net_flow'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ number_format($statistics['financial']['net_flow'], 2) }} €
                    </p>
                </div>
            </div>
            <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Solde total des wallets</p>
                    <p class="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ number_format($statistics['financial']['total_wallet_balance'], 2) }} €
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Clients avec solde</p>
                    <p class="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ $statistics['financial']['clients_with_balance'] }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Solde moyen</p>
                    <p class="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-100">
                        {{ number_format($statistics['financial']['average_wallet_balance'], 2) }} €
                    </p>
                </div>
            </div>
        </div>

        <!-- Activité -->
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Activité des Clients</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Nouveaux clients</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $statistics['activity']['new_clients'] }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Actifs aujourd'hui</p>
                    <p class="mt-1 text-2xl font-bold text-green-600 dark:text-green-400">{{ $statistics['activity']['active_today'] }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Actifs cette semaine</p>
                    <p class="mt-1 text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $statistics['activity']['active_this_week'] }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Actifs ce mois</p>
                    <p class="mt-1 text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $statistics['activity']['active_this_month'] }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

