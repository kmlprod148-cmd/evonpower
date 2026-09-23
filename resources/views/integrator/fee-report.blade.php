@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header Section -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 mb-8">
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-t-2xl p-6">
                <div class="flex justify-between items-center">
                    <div class="flex items-center">
                        <div class="bg-white bg-opacity-20 rounded-full p-3 mr-4">
                            <i class="fas fa-chart-line text-white text-xl"></i>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-white">Rapport de Frais et Répartition</h1>
                            <p class="text-blue-100 mt-1">Détail des frais appliqués aux opérateurs</p>
                        </div>
                    </div>
                    <div class="flex space-x-4">
                        <a href="{{ route('integrator.fee-report.export', ['start_date' => $startDate, 'end_date' => $endDate]) }}" 
                           class="inline-flex items-center px-4 py-2 bg-white bg-opacity-20 text-white font-semibold rounded-xl hover:bg-opacity-30 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-blue-600 transition-all duration-200">
                            <i class="fas fa-download mr-2"></i>
                            Exporter JSON
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Filter Section -->
            <div class="px-6 pb-4">
                <form method="GET" class="flex items-center space-x-4">
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date de début</label>
                        <input type="date" name="start_date" id="start_date" value="{{ $startDate }}" 
                               class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date de fin</label>
                        <input type="date" name="end_date" id="end_date" value="{{ $endDate }}" 
                               class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div class="pt-6">
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200">
                            <i class="fas fa-search mr-2"></i>
                            Filtrer
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Business Profile Info -->
        @if($report['integrator']['business_profile'])
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 mb-8">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fas fa-briefcase text-blue-600 mr-2"></i>
                    Configuration du Business Profile
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-blue-50 dark:bg-blue-900/30 rounded-lg p-4">
                        <h3 class="font-medium text-blue-900 dark:text-blue-100">Frais Fixes Intégrateur</h3>
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                            {{ number_format($report['integrator']['business_profile']['integrator_fee_fixed'], 2) }} EUR
                        </p>
                    </div>
                    <div class="bg-green-50 dark:bg-green-900/30 rounded-lg p-4">
                        <h3 class="font-medium text-green-900 dark:text-green-100">Frais Pourcentage Intégrateur</h3>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                            {{ number_format($report['integrator']['business_profile']['integrator_fee_percentage'], 2) }}%
                        </p>
                    </div>
                    <div class="bg-purple-50 dark:bg-purple-900/30 rounded-lg p-4">
                        <h3 class="font-medium text-purple-900 dark:text-purple-100">Business Profile</h3>
                        <p class="text-lg font-semibold text-purple-600 dark:text-purple-400">
                            {{ $report['integrator']['business_profile']['name'] }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center">
                    <div class="bg-blue-100 dark:bg-blue-900 rounded-full p-3 mr-4">
                        <i class="fas fa-exchange-alt text-blue-600 dark:text-blue-400"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Transactions</h3>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $report['summary']['total_transactions'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center">
                    <div class="bg-green-100 dark:bg-green-900 rounded-full p-3 mr-4">
                        <i class="fas fa-money-bill-wave text-green-600 dark:text-green-400"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Montant Total</h3>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($report['summary']['total_amount'], 2) }} EUR</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center">
                    <div class="bg-yellow-100 dark:bg-yellow-900 rounded-full p-3 mr-4">
                        <i class="fas fa-percentage text-yellow-600 dark:text-yellow-400"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Frais</h3>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($report['summary']['total_fees'], 2) }} EUR</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center">
                    <div class="bg-purple-100 dark:bg-purple-900 rounded-full p-3 mr-4">
                        <i class="fas fa-hand-holding-usd text-purple-600 dark:text-purple-400"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Part Intégrateur</h3>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($report['summary']['total_integrator_share'], 2) }} EUR</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fee Breakdown -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 mb-8">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center">
                    <i class="fas fa-chart-pie text-blue-600 mr-2"></i>
                    Répartition Détaillée des Frais
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Admin Fees -->
                    <div>
                        <h3 class="text-md font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                            <i class="fas fa-crown text-red-600 mr-2"></i>
                            Frais Admin
                        </h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center p-3 bg-red-50 dark:bg-red-900/30 rounded-lg">
                                <span class="text-sm font-medium text-red-900 dark:text-red-100">Frais Fixes</span>
                                <span class="font-bold text-red-600 dark:text-red-400">{{ number_format($report['fee_breakdown']['admin_fees']['fixed_total'], 2) }} EUR</span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-red-50 dark:bg-red-900/30 rounded-lg">
                                <span class="text-sm font-medium text-red-900 dark:text-red-100">Frais Pourcentage</span>
                                <span class="font-bold text-red-600 dark:text-red-400">{{ number_format($report['fee_breakdown']['admin_fees']['percentage_total'], 2) }} EUR</span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-red-50 dark:bg-red-900/30 rounded-lg">
                                <span class="text-sm font-medium text-red-900 dark:text-red-100">Part Frais de Recharge</span>
                                <span class="font-bold text-red-600 dark:text-red-400">{{ number_format($report['fee_breakdown']['admin_fees']['charge_share_total'], 2) }} EUR</span>
                            </div>
                        </div>
                    </div>

                    <!-- Integrator Fees -->
                    <div>
                        <h3 class="text-md font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                            <i class="fas fa-user-tie text-blue-600 mr-2"></i>
                            Frais Intégrateur
                        </h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center p-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
                                <span class="text-sm font-medium text-blue-900 dark:text-blue-100">Frais Fixes</span>
                                <span class="font-bold text-blue-600 dark:text-blue-400">{{ number_format($report['fee_breakdown']['integrator_fees']['fixed_total'], 2) }} EUR</span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
                                <span class="text-sm font-medium text-blue-900 dark:text-blue-100">Frais Pourcentage</span>
                                <span class="font-bold text-blue-600 dark:text-blue-400">{{ number_format($report['fee_breakdown']['integrator_fees']['percentage_total'], 2) }} EUR</span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
                                <span class="text-sm font-medium text-blue-900 dark:text-blue-100">Part Frais de Recharge</span>
                                <span class="font-bold text-blue-600 dark:text-blue-400">{{ number_format($report['fee_breakdown']['integrator_fees']['charge_share_total'], 2) }} EUR</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center">
                    <i class="fas fa-list text-blue-600 mr-2"></i>
                    Détail des Transactions
                </h2>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Transaction</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Montant</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Frais</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Part Intégrateur</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Part Admin</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Part Opérateur</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($report['transactions'] as $transaction)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    #{{ $transaction['transaction_id'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ number_format($transaction['amount'], 2) }} EUR
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ number_format($transaction['fees'], 2) }} EUR
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600 dark:text-blue-400 font-semibold">
                                    {{ number_format($transaction['integrator_share'], 2) }} EUR
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 dark:text-red-400">
                                    {{ number_format($transaction['admin_share'], 2) }} EUR
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 dark:text-green-400">
                                    {{ number_format($transaction['operator_share'], 2) }} EUR
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ \Carbon\Carbon::parse($transaction['created_at'])->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="{{ route('integrator.fee-report.transaction', $transaction['transaction_id']) }}" 
                                       class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                        <i class="fas fa-eye"></i> Détails
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                    Aucune transaction trouvée pour cette période.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
