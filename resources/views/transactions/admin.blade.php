@extends('layouts.app')

@section('title', 'Transactions Administratives')

@section('content')
<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-6">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Transactions Administratives</h1>
                <p class="text-gray-600 mt-2">Gestion complète des transactions : clients, débits admin, intégrateurs et frais périodiques</p>
            </div>
            @can('create', App\Models\Transaction::class)
            <div class="flex space-x-2">
                <a href="{{ route('transactions.create') }}" 
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Nouvelle Transaction
                </a>
                <button onclick="openBalanceModal()" 
                        class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                    Soldes
                </button>
            </div>
            @endcan
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="mb-6">
        <nav class="flex space-x-8" aria-label="Tabs">
            <a href="{{ route('transactions.index') }}" 
               class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                Toutes les Transactions
            </a>
            <a href="{{ route('transactions.admin') }}" 
               class="border-indigo-500 text-indigo-600 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                Transactions Admin
            </a>
            <a href="{{ route('transactions.activation-fees') }}" 
               class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                Frais d'Activation
            </a>
            <a href="{{ route('transactions.statistics') }}" 
               class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                Statistiques
            </a>
            <a href="{{ route('admin.transactions.charging-point-creator-fees') }}" 
               class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                Frais Créateurs
            </a>
        </nav>
    </div>

    <!-- Balance Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-6">
        <!-- Admin Balance Card -->
        <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-lg shadow-lg border border-indigo-700 p-6 text-white">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-white bg-opacity-20 rounded-md flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-indigo-100">Solde Admin</p>
                    <p class="text-2xl font-bold text-white">{{ number_format($balanceSummary['total_admin_balance'] ?? 0, 2) }} EUR</p>
                    @if(isset($balanceSummary['admin_transaction_details_count']))
                    <p class="text-xs text-indigo-200 mt-1">{{ $balanceSummary['admin_transaction_details_count'] }} transactions</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-100 rounded-md flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Intégrateurs</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ isset($balanceSummary['integrators']) ? (is_countable($balanceSummary['integrators']) ? count($balanceSummary['integrators']) : 0) : 0 }}</p>
                    <p class="text-sm text-gray-500">Solde: {{ number_format($balanceSummary['total_integrator_balance'] ?? 0, 2) }} EUR</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-green-100 rounded-md flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Partenaires</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ isset($balanceSummary['partners']) ? (is_countable($balanceSummary['partners']) ? count($balanceSummary['partners']) : 0) : 0 }}</p>
                    <p class="text-sm text-gray-500">Solde: {{ number_format(isset($balanceSummary['partners']) && is_countable($balanceSummary['partners']) ? collect($balanceSummary['partners'])->sum('balance') : 0, 2) }} EUR</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-purple-100 rounded-md flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Business Profiles</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ isset($balanceSummary['business_profiles']) ? (is_countable($balanceSummary['business_profiles']) ? count($balanceSummary['business_profiles']) : 0) : 0 }}</p>
                    <p class="text-sm text-gray-500">Solde: {{ number_format(isset($balanceSummary['business_profiles']) && is_countable($balanceSummary['business_profiles']) ? collect($balanceSummary['business_profiles'])->sum('balance') : 0, 2) }} EUR</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-yellow-100 rounded-md flex items-center justify-center">
                        <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Transactions</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $statistics['total_transactions'] }}</p>
                    <p class="text-sm text-gray-500">Revenus: {{ number_format($statistics['total_revenue'], 2) }} EUR</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert for Pending Transactions -->
    @if(isset($statistics['pending_transactions']) && $statistics['pending_transactions'] > 0)
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800">
                    {{ $statistics['pending_transactions'] }} transaction(s) en attente d'approbation
                </h3>
                <div class="mt-2 text-sm text-yellow-700">
                    <p>
                        Les transactions en statut "pending" n'ont pas encore de frais admin calculés. 
                        Le solde affiché est donc à 0 pour ces transactions. 
                        Une fois les réservations approuvées, les frais admin seront automatiquement calculés selon les Business Profiles et ajoutés au solde.
                    </p>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Admin Balance Details -->
    @if(isset($statistics['admin_balance']))
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">Détails du Solde Admin</h3>
            <span class="text-sm text-gray-500">Calculé depuis: {{ $statistics['admin_balance_calculated_from'] ?? 'TransactionDetail' }}</span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                <p class="text-sm font-medium text-green-700">Solde Actuel (Payé)</p>
                <p class="text-2xl font-bold text-green-900">{{ number_format($statistics['admin_balance'], 2) }} EUR</p>
                <p class="text-xs text-green-600 mt-1">Parts des transactions complétées/approuvées</p>
            </div>
            <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                <p class="text-sm font-medium text-blue-700">Total Parts Admin</p>
                <p class="text-2xl font-bold text-blue-900">{{ number_format($statistics['total_admin_share'] ?? 0, 2) }} EUR</p>
                <p class="text-xs text-blue-600 mt-1">Toutes les parts admin (payées + en attente)</p>
            </div>
            <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-200">
                <p class="text-sm font-medium text-yellow-700">Transactions</p>
                <p class="text-2xl font-bold text-yellow-900">{{ $statistics['admin_transaction_details_count'] ?? 0 }}</p>
                <p class="text-xs text-yellow-600 mt-1">Transactions avec parts admin</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-100 rounded-md flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Transactions Client</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $statistics['client_transactions'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-red-100 rounded-md flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Débits Admin</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($statistics['admin_debits'], 2) }} EUR</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-orange-100 rounded-md flex items-center justify-center">
                        <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Débits Intégrateurs</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($statistics['integrator_debits'], 2) }} EUR</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-purple-100 rounded-md flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Frais Périodiques</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($statistics['periodic_fees'], 2) }} EUR</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-yellow-100 rounded-md flex items-center justify-center">
                        <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Frais d'Activation</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($statistics['total_activation_fees'], 2) }} EUR</p>
                    <p class="text-xs text-gray-500">{{ $statistics['activation_fee_transactions'] }} transactions</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <form method="GET" action="{{ route('transactions.admin') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Transaction Type -->
            <div>
                <label for="transaction_type" class="block text-sm font-medium text-gray-700 mb-1">Type de Transaction</label>
                <select id="transaction_type" name="transaction_type" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Tous les types</option>
                    <option value="client" {{ request('transaction_type') === 'client' ? 'selected' : '' }}>Client</option>
                    <option value="admin" {{ request('transaction_type') === 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="activation_fee" {{ request('transaction_type') === 'activation_fee' ? 'selected' : '' }}>Frais d'Activation</option>
                </select>
            </div>

            <!-- Transaction Category -->
            <div>
                <label for="transaction_category" class="block text-sm font-medium text-gray-700 mb-1">Catégorie</label>
                <select id="transaction_category" name="transaction_category" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Toutes les catégories</option>
                    <option value="charging" {{ request('transaction_category') === 'charging' ? 'selected' : '' }}>Recharge</option>
                    <option value="debit" {{ request('transaction_category') === 'debit' ? 'selected' : '' }}>Débit Admin</option>
                    <option value="integrator_debit" {{ request('transaction_category') === 'integrator_debit' ? 'selected' : '' }}>Débit Intégrateur</option>
                    <option value="periodic_fee" {{ request('transaction_category') === 'periodic_fee' ? 'selected' : '' }}>Frais Périodiques</option>
                    <option value="activation" {{ request('transaction_category') === 'activation' ? 'selected' : '' }}>Activation</option>
                    <option value="commission" {{ request('transaction_category') === 'commission' ? 'selected' : '' }}>Commission</option>
                </select>
            </div>

            <!-- Business Profile -->
            <div>
                <label for="business_profile_id" class="block text-sm font-medium text-gray-700 mb-1">Business Profile</label>
                <select id="business_profile_id" name="business_profile_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Tous les profils</option>
                    @foreach($businessProfiles as $profile)
                        <option value="{{ $profile->id }}" {{ request('business_profile_id') == $profile->id ? 'selected' : '' }}>
                            {{ $profile->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Business Profile Owner Type -->
            <div>
                <label for="business_profile_owner_type" class="block text-sm font-medium text-gray-700 mb-1">Type de Propriétaire</label>
                <select id="business_profile_owner_type" name="business_profile_owner_type" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Tous les types</option>
                    <option value="integrator" {{ request('business_profile_owner_type') === 'integrator' ? 'selected' : '' }}>Intégrateur</option>
                    <option value="operator" {{ request('business_profile_owner_type') === 'operator' ? 'selected' : '' }}>Opérateur</option>
                    <option value="partner" {{ request('business_profile_owner_type') === 'partner' ? 'selected' : '' }}>Partenaire</option>
                </select>
            </div>

            <!-- Date Range -->
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Date de début</label>
                <input type="date" id="start_date" name="start_date" value="{{ request('start_date') }}" 
                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">Date de fin</label>
                <input type="date" id="end_date" name="end_date" value="{{ request('end_date') }}" 
                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <!-- Status -->
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                <select id="status" name="status" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Tous les statuts</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En attente</option>
                    <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>En cours</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Terminé</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Annulé</option>
                </select>
            </div>

            <!-- Actions -->
            <div class="flex items-end space-x-2">
                <button type="submit" 
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    Filtrer
                </button>
                <a href="{{ route('transactions.admin') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Réinitialiser
                </a>
            </div>
        </form>
    </div>

    <!-- Transactions Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Catégorie</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utilisateur</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Business Profile</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Propriétaire</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Borne</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant Base</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Frais d'Activation</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Répartition</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($transactions as $transaction)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            #{{ $transaction->id }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                {{ $transaction->transaction_type === 'client' ? 'bg-green-100 text-green-800' : 
                                   ($transaction->transaction_type === 'admin' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800') }}">
                                {{ $transaction->getTransactionTypeLabel() }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                {{ $transaction->transaction_category === 'charging' ? 'bg-blue-100 text-blue-800' : 
                                   ($transaction->transaction_category === 'debit' ? 'bg-red-100 text-red-800' : 
                                   ($transaction->transaction_category === 'integrator_debit' ? 'bg-orange-100 text-orange-800' : 
                                   ($transaction->transaction_category === 'periodic_fee' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800'))) }}">
                                {{ $transaction->getTransactionCategoryLabel() }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $transaction->user->name ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            @if($transaction->businessProfile)
                                <div class="flex items-center">
                                    <span class="text-sm font-medium text-gray-900">{{ $transaction->businessProfile->name }}</span>
                                    @if($transaction->businessProfile->account)
                                        <span class="ml-2 text-xs text-gray-500">(Solde: {{ number_format($transaction->businessProfile->account->balance, 2) }} EUR)</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            @if($transaction->businessProfileOwner)
                                <div class="flex items-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        {{ $transaction->business_profile_owner_type === 'integrator' ? 'bg-blue-100 text-blue-800' : 
                                           ($transaction->business_profile_owner_type === 'operator' ? 'bg-green-100 text-green-800' : 'bg-purple-100 text-purple-800') }}">
                                        {{ $transaction->getBusinessProfileOwnerTypeLabel() }}
                                    </span>
                                    <span class="ml-2 text-sm font-medium text-gray-900">{{ $transaction->businessProfileOwner->name ?? 'N/A' }}</span>
                                </div>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            @if($transaction->chargingPoint)
                                <div class="flex items-center">
                                    <span class="text-sm font-medium text-gray-900">{{ $transaction->chargingPoint->name }}</span>
                                    @if($transaction->chargingPoint->station)
                                        <span class="ml-2 text-xs text-gray-500">({{ $transaction->chargingPoint->station->name ?? 'N/A' }})</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-gray-400">N/A</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            @php
                                $activationFee = $transaction->getActivationFeeAmount();
                                $baseAmount = $transaction->price_total - $activationFee;
                            @endphp
                            {{ number_format($baseAmount, 2) }} {{ $transaction->currency ?? 'EUR' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            @php
                                $activationFee = $transaction->getActivationFeeAmount();
                            @endphp
                            @if($activationFee > 0)
                                <span class="text-yellow-600 font-medium">{{ number_format($activationFee, 2) }} {{ $transaction->currency ?? 'EUR' }}</span>
                                @if($transaction->activationFeeBusinessProfile)
                                    <div class="text-xs text-gray-500">({{ $transaction->activationFeeBusinessProfile->name }})</div>
                                @elseif($transaction->chargingPoint && $transaction->chargingPoint->integrator && $transaction->chargingPoint->integrator->businessProfile)
                                    <div class="text-xs text-gray-500">({{ $transaction->chargingPoint->integrator->businessProfile->name }} - Intégrateur)</div>
                                @elseif($transaction->chargingPoint && $transaction->chargingPoint->partner && $transaction->chargingPoint->partner->businessProfile)
                                    <div class="text-xs text-gray-500">({{ $transaction->chargingPoint->partner->businessProfile->name }} - Partenaire)</div>
                                @endif
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                            {{ number_format($transaction->price_total, 2) }} {{ $transaction->currency ?? 'EUR' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            @php
                                $transactionDetail = $transaction->transactionDetail ?? null;
                                $isPending = $transaction->status === 'pending';
                            @endphp
                            @if($transactionDetail)
                                <div class="space-y-1">
                                    <div class="flex items-center">
                                        <span class="text-xs font-medium text-red-600">Admin:</span>
                                        <span class="ml-1 text-xs">{{ number_format($transactionDetail->admin_share_amount, 2) }} €</span>
                                        @if($transactionDetail->admin_share_percentage)
                                            <span class="ml-1 text-xs text-gray-500">({{ number_format($transactionDetail->admin_share_percentage, 1) }}%)</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center">
                                        <span class="text-xs font-medium text-blue-600">Intégrateur:</span>
                                        <span class="ml-1 text-xs">{{ number_format($transactionDetail->integrator_share_amount, 2) }} €</span>
                                        @if($transactionDetail->integrator_share_percentage)
                                            <span class="ml-1 text-xs text-gray-500">({{ number_format($transactionDetail->integrator_share_percentage, 1) }}%)</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center">
                                        <span class="text-xs font-medium text-green-600">Opérateur:</span>
                                        <span class="ml-1 text-xs">{{ number_format($transactionDetail->operator_share_amount, 2) }} €</span>
                                    </div>
                                </div>
                            @else
                                @if($isPending)
                                    <div class="space-y-1">
                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                            </svg>
                                            En attente
                                        </span>
                                        <div class="text-xs text-gray-500 mt-1">
                                            Les frais admin seront calculés<br>après approbation de la réservation
                                        </div>
                                    </div>
                                @else
                                    <span class="text-xs text-yellow-600 font-medium">Répartition manquante</span>
                                @endif
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ in_array($transaction->status, ['completed', 'confirmed']) ? 'bg-green-100 text-green-800' : 
                                   ($transaction->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                   ($transaction->status === 'in_progress' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800')) }}">
                                {{ $transaction->status->label() ?? ($transaction->status === 'confirmed' ? 'Confirmé' : ucfirst($transaction->status)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $transaction->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end space-x-2">
                                <a href="{{ route('admin.transactions.show', $transaction) }}" 
                                   class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                   title="Voir les détails complets">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    Détails
                                </a>
                                @if($transaction->transactionDetail)
                                <button onclick="showTransactionBreakdown({{ $transaction->id }})" 
                                        class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                        title="Voir la répartition détaillée">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                    Répartition
                                </button>
                                @endif
                                @if($transaction->reservation)
                                <a href="{{ route('reservations.show', $transaction->reservation) }}" 
                                   class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-green-700 bg-green-100 hover:bg-green-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                   title="Voir la réservation associée">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    Réservation
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="14" class="px-6 py-12 text-center text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">Aucune transaction administrative trouvée</h3>
                            <p class="mt-1 text-sm text-gray-500">Aucune transaction administrative ne correspond aux critères de recherche.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Summary Section -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Résumé par Business Profile</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @php
                    $businessProfileTotals = $transactions->groupBy('business_profile_id')->map(function($group) {
                        $totalAmount = $group->sum('price_total');
                        $activationFees = $group->sum(function($transaction) {
                            return $transaction->getActivationFeeAmount();
                        });
                        $baseAmount = $totalAmount - $activationFees;
                        
                        return [
                            'name' => $group->first()->businessProfile->name ?? 'N/A',
                            'total_amount' => $totalAmount,
                            'base_amount' => $baseAmount,
                            'activation_fees' => $activationFees,
                            'transaction_count' => $group->count(),
                            'balance' => $group->first()->businessProfile->account->balance ?? 0
                        ];
                    });
                    
                    $activationFeeTotal = $transactions->sum(function($transaction) {
                        return $transaction->getActivationFeeAmount();
                    });
                    $baseAmountTotal = $transactions->sum('price_total') - $activationFeeTotal;
                    $grandTotal = $transactions->sum('price_total');
                @endphp

                <!-- Business Profile Totals -->
                @foreach($businessProfileTotals as $profileId => $profileData)
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-sm font-medium text-gray-900">{{ $profileData['name'] }}</h4>
                        <span class="text-xs text-gray-500">{{ $profileData['transaction_count'] }} transactions</span>
                    </div>
                    <div class="space-y-1">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Montant Base:</span>
                            <span class="font-medium">{{ number_format($profileData['base_amount'], 2) }} EUR</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Frais d'Activation:</span>
                            <span class="font-medium text-yellow-600">{{ number_format($profileData['activation_fees'], 2) }} EUR</span>
                        </div>
                        <div class="flex justify-between text-sm font-semibold border-t pt-1">
                            <span class="text-gray-900">Total:</span>
                            <span class="text-green-600">{{ number_format($profileData['total_amount'], 2) }} EUR</span>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500">
                            <span>Solde Compte:</span>
                            <span class="{{ $profileData['balance'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ number_format($profileData['balance'], 2) }} EUR
                            </span>
                        </div>
                    </div>
                </div>
                @endforeach

                <!-- Grand Totals -->
                <div class="bg-blue-50 rounded-lg p-4 border-2 border-blue-200">
                    <h4 class="text-sm font-medium text-blue-900 mb-2">TOTAUX GÉNÉRAUX</h4>
                    <div class="space-y-1">
                        <div class="flex justify-between text-sm">
                            <span class="text-blue-700">Montant Base Total:</span>
                            <span class="font-medium text-blue-900">{{ number_format($baseAmountTotal, 2) }} EUR</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-blue-700">Frais d'Activation Total:</span>
                            <span class="font-medium text-yellow-600">{{ number_format($activationFeeTotal, 2) }} EUR</span>
                        </div>
                        <div class="flex justify-between text-sm font-bold border-t pt-1">
                            <span class="text-blue-900">GRAND TOTAL:</span>
                            <span class="text-green-600">{{ number_format($grandTotal, 2) }} EUR</span>
                        </div>
                        <div class="flex justify-between text-xs text-blue-600">
                            <span>Total Transactions:</span>
                            <span>{{ $transactions->total() }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        @if($transactions->hasPages())
        <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
            {{ $transactions->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Balance Modal -->
<div id="balanceModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Soldes des Comptes</h3>
                <button onclick="closeBalanceModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Integrators Balance -->
            <div class="mb-6">
                <h4 class="text-md font-medium text-gray-700 mb-3">Intégrateurs</h4>
                <div class="space-y-2">
                    @foreach($balanceSummary['integrators'] as $integrator)
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                        <span class="text-sm font-medium text-gray-900">{{ $integrator['name'] }}</span>
                        <span class="text-sm font-semibold {{ $integrator['balance'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format($integrator['balance'], 2) }} {{ $integrator['currency'] }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Partners Balance -->
            <div class="mb-6">
                <h4 class="text-md font-medium text-gray-700 mb-3">Partenaires</h4>
                <div class="space-y-2">
                    @foreach($balanceSummary['partners'] as $partner)
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                        <span class="text-sm font-medium text-gray-900">{{ $partner['name'] }}</span>
                        <span class="text-sm font-semibold {{ $partner['balance'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format($partner['balance'], 2) }} {{ $partner['currency'] }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Business Profiles Balance -->
            <div class="mb-6">
                <h4 class="text-md font-medium text-gray-700 mb-3">Business Profiles</h4>
                <div class="space-y-2">
                    @foreach($balanceSummary['business_profiles'] as $profile)
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                        <span class="text-sm font-medium text-gray-900">{{ $profile['name'] }}</span>
                        <span class="text-sm font-semibold {{ $profile['balance'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format($profile['balance'], 2) }} {{ $profile['currency'] }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function openBalanceModal() {
    document.getElementById('balanceModal').classList.remove('hidden');
}

function closeBalanceModal() {
    document.getElementById('balanceModal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('balanceModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeBalanceModal();
    }
});

// Fonction pour afficher la répartition détaillée d'une transaction
async function showTransactionBreakdown(transactionId) {
    try {
        const response = await fetch(`/transactions/${transactionId}/fees-details`);
        const data = await response.json();
        
        if (data.success) {
            // Créer une modal pour afficher le breakdown
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50';
            modal.id = 'breakdownModal';
            modal.innerHTML = `
                <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-md bg-white">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Répartition Détaillée - Transaction #${transactionId}</h3>
                        <button onclick="closeBreakdownModal()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="max-h-96 overflow-y-auto">
                        ${data.html}
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            
            // Fermer la modal en cliquant en dehors
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeBreakdownModal();
                }
            });
        } else {
            alert('Erreur lors du chargement des détails: ' + (data.message || 'Erreur inconnue'));
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur lors du chargement des détails de la transaction');
    }
}

function closeBreakdownModal() {
    const modal = document.getElementById('breakdownModal');
    if (modal) {
        modal.remove();
    }
}
</script>
@endsection
