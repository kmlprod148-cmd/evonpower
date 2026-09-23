@extends('layouts.app')

@section('title', 'Statistiques des Transactions - Admin')

@section('content')
<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-6">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Statistiques des Transactions</h1>
                <p class="text-gray-600 mt-2">Vue d'ensemble complète des transactions et des revenus</p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('admin.transactions.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    Retour aux Transactions
                </a>
            </div>
        </div>
    </div>

    <!-- Statistiques Générales -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total des Transactions -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total des Transactions</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ number_format($statistics['total_transactions'] ?? 0) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Montant Total -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Montant Total</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ number_format($statistics['total_amount'] ?? 0, 2) }} EUR</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transactions Clients -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Transactions Clients</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ number_format($statistics['client_transactions'] ?? 0) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transactions Admin -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Transactions Admin</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ number_format($statistics['admin_transactions'] ?? 0) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Détails des Statistiques -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Répartition par Type -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Répartition par Type de Transaction</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-500">Transactions Clients</span>
                        <span class="text-sm font-medium text-gray-900">{{ number_format($statistics['client_transactions'] ?? 0) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-500">Transactions Admin</span>
                        <span class="text-sm font-medium text-gray-900">{{ number_format($statistics['admin_transactions'] ?? 0) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-500">Frais d'Activation</span>
                        <span class="text-sm font-medium text-gray-900">{{ number_format($statistics['activation_fee_transactions'] ?? 0) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Commissions -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Commissions</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-500">
                            <i class="fas fa-user-shield mr-1 text-red-500"></i>
                            Part Admin (réelle)
                        </span>
                        <span class="text-sm font-medium text-gray-900">
                            {{ number_format($statistics['total_admin_share'] ?? 0, 2) }} EUR
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-500">
                            <i class="fas fa-user-tie mr-1 text-blue-500"></i>
                            Part Intégrateur (réelle)
                        </span>
                        <span class="text-sm font-medium text-gray-900">
                            {{ number_format($statistics['total_integrator_share'] ?? 0, 2) }} EUR
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-500">
                            <i class="fas fa-user-cog mr-1 text-green-500"></i>
                            Part Opérateur (réelle)
                        </span>
                        <span class="text-sm font-medium text-gray-900">
                            {{ number_format($statistics['total_operator_share'] ?? 0, 2) }} EUR
                        </span>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-700">Total Réparti</span>
                            <span class="text-sm font-bold text-gray-900">
                                {{ number_format(($statistics['total_admin_share'] ?? 0) + ($statistics['total_integrator_share'] ?? 0) + ($statistics['total_operator_share'] ?? 0), 2) }} EUR
                            </span>
                        </div>
                        <div class="mt-2 text-xs text-gray-500">
                            <i class="fas fa-check-circle text-green-500 mr-1"></i>
                            Calculé depuis TransactionDetail (Business Profiles)
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Soldes des Comptes -->
    @if(isset($balanceSummary) && !empty($balanceSummary))
    <div class="mt-8">
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Soldes des Comptes</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($balanceSummary as $account)
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $account['name'] ?? 'Compte' }}</p>
                                <p class="text-sm text-gray-500">{{ $account['type'] ?? 'Type inconnu' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold {{ ($account['balance'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ number_format($account['balance'] ?? 0, 2) }} EUR
                                </p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Graphiques (optionnel) -->
    <div class="mt-8">
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Évolution des Transactions</h3>
                <div class="h-64 flex items-center justify-center bg-gray-50 rounded-lg">
                    <p class="text-gray-500">Graphiques à implémenter</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
