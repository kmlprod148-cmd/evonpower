@extends('layouts.sidebar')

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Détails des Frais et Parts</h1>
                        <p class="text-sm text-gray-600 mt-1">Répartition détaillée des revenus pour la transaction</p>
                    </div>
                    <div class="flex space-x-3">
                        <a href="{{ route('admin.transactions.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Retour
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @if(isset($feeDetails))
        <!-- Informations de la Transaction -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Informations de la Transaction</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-blue-50 rounded-lg p-4">
                        <h3 class="text-sm font-medium text-blue-600 mb-2">Montant Total</h3>
                        <p class="text-2xl font-bold text-blue-900">{{ number_format($feeDetails['breakdown']['total_amount'], 2) }} EUR</p>
                    </div>
                    <div class="bg-red-50 rounded-lg p-4">
                        <h3 class="text-sm font-medium text-red-600 mb-2">Total des Frais</h3>
                        <p class="text-2xl font-bold text-red-900">{{ number_format($feeDetails['total_fees'], 2) }} EUR</p>
                    </div>
                    <div class="bg-green-50 rounded-lg p-4">
                        <h3 class="text-sm font-medium text-green-600 mb-2">Revenu Net</h3>
                        <p class="text-2xl font-bold text-green-900">{{ number_format($feeDetails['net_revenue'], 2) }} EUR</p>
                    </div>
                </div>
            </div>
        </div>

        @if($feeDetails['business_profile'])
        <!-- Business Profile Information -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Business Profile</h2>
            </div>
            <div class="p-6">
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center shadow-lg">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-lg font-semibold text-blue-900">{{ $feeDetails['business_profile']['name'] }}</h4>
                                <p class="text-sm text-blue-700">Type: {{ $feeDetails['business_profile']['owner_type'] }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $feeDetails['business_profile']['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                <span class="h-2 w-2 mr-2 rounded-full {{ $feeDetails['business_profile']['is_active'] ? 'bg-green-500' : 'bg-red-500' }}"></span>
                                {{ $feeDetails['business_profile']['is_active'] ? 'Actif' : 'Inactif' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Détail des Frais -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Frais de Recharge -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <svg class="h-5 w-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        Frais de Recharge
                    </h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                            <span class="text-sm text-gray-600">Frais fixes</span>
                            <span class="font-semibold text-green-600">{{ number_format($feeDetails['charging_fees']['fixed_fee'], 2) }} EUR</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                            <span class="text-sm text-gray-600">Frais en pourcentage</span>
                            <span class="font-semibold text-green-600">{{ number_format($feeDetails['charging_fees']['percentage_fee'], 2) }} EUR</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                            <span class="text-sm text-gray-600">Frais par kWh</span>
                            <span class="font-semibold text-green-600">{{ number_format($feeDetails['charging_fees']['per_kwh_fee'], 2) }} EUR</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                            <span class="text-sm text-gray-600">Frais par minute</span>
                            <span class="font-semibold text-green-600">{{ number_format($feeDetails['charging_fees']['per_minute_fee'], 2) }} EUR</span>
                        </div>
                        <div class="border-t pt-4">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-semibold text-gray-900">Total</span>
                                <span class="text-xl font-bold text-green-600">{{ number_format($feeDetails['charging_fees']['total'], 2) }} EUR</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Frais de Transaction -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <svg class="h-5 w-5 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        Frais de Transaction
                    </h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex justify-between items-center p-3 bg-purple-50 rounded-lg">
                            <span class="text-sm text-gray-600">Frais fixes</span>
                            <span class="font-semibold text-purple-600">{{ number_format($feeDetails['transaction_fees']['fixed_fee'], 2) }} EUR</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-purple-50 rounded-lg">
                            <span class="text-sm text-gray-600">Frais en pourcentage</span>
                            <span class="font-semibold text-purple-600">{{ number_format($feeDetails['transaction_fees']['percentage_fee'], 2) }} EUR</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-purple-50 rounded-lg">
                            <span class="text-sm text-gray-600">Frais minimum</span>
                            <span class="font-semibold text-purple-600">{{ number_format($feeDetails['transaction_fees']['minimum_fee'], 2) }} EUR</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-purple-50 rounded-lg">
                            <span class="text-sm text-gray-600">Frais maximum</span>
                            <span class="font-semibold text-purple-600">{{ number_format($feeDetails['transaction_fees']['maximum_fee'], 2) }} EUR</span>
                        </div>
                        <div class="border-t pt-4">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-semibold text-gray-900">Total</span>
                                <span class="text-xl font-bold text-purple-600">{{ number_format($feeDetails['transaction_fees']['total'], 2) }} EUR</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Frais d'Activation -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                    <svg class="h-5 w-5 text-orange-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Frais d'Activation
                </h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-orange-50 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-orange-600 mb-2">Frais de base</h4>
                        <p class="text-xl font-bold text-orange-900">{{ number_format($feeDetails['activation_fees']['base_fee'], 2) }} EUR</p>
                    </div>
                    <div class="bg-orange-50 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-orange-600 mb-2">Frais uniques</h4>
                        <p class="text-xl font-bold text-orange-900">{{ number_format($feeDetails['activation_fees']['one_time_fee'], 2) }} EUR</p>
                    </div>
                    <div class="bg-orange-50 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-orange-600 mb-2">Frais d'installation</h4>
                        <p class="text-xl font-bold text-orange-900">{{ number_format($feeDetails['activation_fees']['setup_fee'], 2) }} EUR</p>
                    </div>
                </div>
                <div class="mt-6 border-t pt-4">
                    <div class="flex justify-between items-center">
                        <span class="text-lg font-semibold text-gray-900">Total des frais d'activation</span>
                        <span class="text-xl font-bold text-orange-600">{{ number_format($feeDetails['activation_fees']['total'], 2) }} EUR</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Répartition des Revenus -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                    <svg class="h-5 w-5 text-indigo-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                    </svg>
                    Répartition des Revenus
                </h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Part Admin -->
                    <div class="bg-red-50 rounded-lg p-4 border border-red-200">
                        <div class="flex items-center mb-3">
                            <div class="w-8 h-8 bg-red-500 rounded-lg flex items-center justify-center">
                                <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <h4 class="ml-2 font-semibold text-gray-900">Admin</h4>
                        </div>
                        <div class="space-y-2">
                            <div class="text-center">
                                <p class="text-2xl font-bold text-red-600">{{ number_format($feeDetails['revenue_distribution']['admin']['amount'], 2) }} EUR</p>
                                <p class="text-sm text-red-600">{{ number_format($feeDetails['revenue_distribution']['admin']['percentage'], 1) }}%</p>
                            </div>
                            <p class="text-xs text-gray-600 text-center">{{ $feeDetails['revenue_distribution']['admin']['description'] }}</p>
                        </div>
                    </div>

                    <!-- Part Intégrateur -->
                    <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                        <div class="flex items-center mb-3">
                            <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center">
                                <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <h4 class="ml-2 font-semibold text-gray-900">Intégrateur</h4>
                        </div>
                        <div class="space-y-2">
                            <div class="text-center">
                                <p class="text-2xl font-bold text-blue-600">{{ number_format($feeDetails['revenue_distribution']['integrator']['amount'], 2) }} EUR</p>
                                <p class="text-sm text-blue-600">{{ number_format($feeDetails['revenue_distribution']['integrator']['percentage'], 1) }}%</p>
                            </div>
                            <p class="text-xs text-gray-600 text-center">{{ $feeDetails['revenue_distribution']['integrator']['description'] }}</p>
                        </div>
                    </div>

                    <!-- Part Partenaire -->
                    <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                        <div class="flex items-center mb-3">
                            <div class="w-8 h-8 bg-green-500 rounded-lg flex items-center justify-center">
                                <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <h4 class="ml-2 font-semibold text-gray-900">Partenaire</h4>
                        </div>
                        <div class="space-y-2">
                            <div class="text-center">
                                <p class="text-2xl font-bold text-green-600">{{ number_format($feeDetails['revenue_distribution']['partner']['amount'], 2) }} EUR</p>
                                <p class="text-sm text-green-600">{{ number_format($feeDetails['revenue_distribution']['partner']['percentage'], 1) }}%</p>
                            </div>
                            <p class="text-xs text-gray-600 text-center">{{ $feeDetails['revenue_distribution']['partner']['description'] }}</p>
                        </div>
                    </div>

                    <!-- Part Opérateur -->
                    <div class="bg-purple-50 rounded-lg p-4 border border-purple-200">
                        <div class="flex items-center mb-3">
                            <div class="w-8 h-8 bg-purple-500 rounded-lg flex items-center justify-center">
                                <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <h4 class="ml-2 font-semibold text-gray-900">Opérateur</h4>
                        </div>
                        <div class="space-y-2">
                            <div class="text-center">
                                <p class="text-2xl font-bold text-purple-600">{{ number_format($feeDetails['revenue_distribution']['operator']['amount'], 2) }} EUR</p>
                                <p class="text-sm text-purple-600">{{ number_format($feeDetails['revenue_distribution']['operator']['percentage'], 1) }}%</p>
                            </div>
                            <p class="text-xs text-gray-600 text-center">{{ $feeDetails['revenue_distribution']['operator']['description'] }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Résumé -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Résumé de la Répartition</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-gray-700 mb-3">Résumé des Frais</h4>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Frais de recharge:</span>
                                <span class="font-medium">{{ number_format($feeDetails['breakdown']['fees_summary']['charging_fees'], 2) }} EUR</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Frais de transaction:</span>
                                <span class="font-medium">{{ number_format($feeDetails['breakdown']['fees_summary']['transaction_fees'], 2) }} EUR</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Frais d'activation:</span>
                                <span class="font-medium">{{ number_format($feeDetails['breakdown']['fees_summary']['activation_fees'], 2) }} EUR</span>
                            </div>
                            <div class="border-t pt-2">
                                <div class="flex justify-between font-semibold">
                                    <span class="text-gray-900">Total des frais:</span>
                                    <span class="text-red-600">{{ number_format($feeDetails['breakdown']['fees_summary']['total_fees'], 2) }} EUR</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-gray-700 mb-3">Répartition des Revenus</h4>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Revenu brut:</span>
                                <span class="font-medium">{{ number_format($feeDetails['breakdown']['revenue_summary']['gross_revenue'], 2) }} EUR</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Revenu net:</span>
                                <span class="font-medium">{{ number_format($feeDetails['breakdown']['revenue_summary']['net_revenue'], 2) }} EUR</span>
                            </div>
                            <div class="border-t pt-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-red-600">Part Admin:</span>
                                    <span class="font-medium text-red-600">{{ number_format($feeDetails['breakdown']['revenue_summary']['admin_share'], 2) }} EUR</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-blue-600">Part Intégrateur:</span>
                                    <span class="font-medium text-blue-600">{{ number_format($feeDetails['breakdown']['revenue_summary']['integrator_share'], 2) }} EUR</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-green-600">Part Partenaire:</span>
                                    <span class="font-medium text-green-600">{{ number_format($feeDetails['breakdown']['revenue_summary']['partner_share'], 2) }} EUR</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-purple-600">Part Opérateur:</span>
                                    <span class="font-medium text-purple-600">{{ number_format($feeDetails['breakdown']['revenue_summary']['operator_share'], 2) }} EUR</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @else
        <div class="bg-white shadow rounded-lg">
            <div class="p-6 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Aucune donnée disponible</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Les détails des frais ne sont pas disponibles pour cette transaction.
                </p>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
