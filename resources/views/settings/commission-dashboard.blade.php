@extends('layouts.app')

@section('title', 'Paramètres - Tableau de Bord des Commissions')

@section('content')
<div class="px-4 py-6">
    <div class="flex flex-wrap">
        <!-- Sidebar de navigation des paramètres -->
        <div class="w-full md:w-1/4 lg:w-1/5 pr-4">
            <div class="bg-white rounded-lg shadow-md p-4 mb-4">
                <h2 class="text-lg font-semibold mb-4">Paramètres</h2>
                <div class="flex flex-col space-y-2">
                    @include('partials.settings-submenu')
                </div>
            </div>
        </div>

        <!-- Contenu principal -->
        <div class="w-full md:w-3/4 lg:w-4/5">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold">Tableau de Bord des Commissions</h1>
                    <div class="flex space-x-2">
                        <a href="{{ route('commissions.reports') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                            Rapports détaillés
                        </a>
                        <a href="{{ route('commissions.export') }}?start_date={{ $startDate }}&end_date={{ $endDate }}" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                            Exporter CSV
                        </a>
                    </div>
                </div>

                @include('partials.flash-messages')

                <!-- Filtres de période -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                    <div class="p-4">
                        <form action="{{ route('settings.commission-dashboard') }}" method="GET" class="flex flex-wrap gap-4">
                            <div class="flex-1 min-w-[200px]">
                                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Date de début</label>
                                <input type="date" name="start_date" id="start_date" value="{{ $startDate }}" 
                                    class="w-full rounded border-gray-300">
                            </div>
                            <div class="flex-1 min-w-[200px]">
                                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">Date de fin</label>
                                <input type="date" name="end_date" id="end_date" value="{{ $endDate }}" 
                                    class="w-full rounded border-gray-300">
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                                    Filtrer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Résumé des commissions pour la période -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="p-4 bg-blue-50 border-b border-blue-100">
                            <h2 class="text-lg font-semibold text-blue-800">Résumé de la période</h2>
                            <p class="text-sm text-blue-600">{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
                        </div>
                        <div class="p-4">
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-600">Transactions:</span>
                                <span class="font-semibold">{{ $report['summary']['total_transactions'] }}</span>
                            </div>
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-600">Montant total:</span>
                                <span class="font-semibold">{{ number_format($report['summary']['total_amount'], 2) }} €</span>
                            </div>
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-600">Commissions totales:</span>
                                <span class="font-semibold">{{ number_format($report['summary']['total_commissions']['total'], 2) }} €</span>
                            </div>
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-600">% du montant total:</span>
                                <span class="font-semibold">
                                    {{ $report['summary']['total_amount'] > 0 
                                        ? number_format(($report['summary']['total_commissions']['total'] / $report['summary']['total_amount']) * 100, 2) 
                                        : '0.00' }} %
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="p-4 bg-green-50 border-b border-green-100">
                            <h2 class="text-lg font-semibold text-green-800">Commissions payées</h2>
                        </div>
                        <div class="p-4">
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-600">Admin:</span>
                                <span class="font-semibold">{{ number_format($report['summary']['paid_commissions']['admin'], 2) }} €</span>
                            </div>
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-600">Intégrateur:</span>
                                <span class="font-semibold">{{ number_format($report['summary']['paid_commissions']['integrator'], 2) }} €</span>
                            </div>
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-600">Partenaire:</span>
                                <span class="font-semibold">{{ number_format($report['summary']['paid_commissions']['partner'], 2) }} €</span>
                            </div>
                            <div class="flex justify-between mb-2 pt-2 border-t">
                                <span class="text-gray-600">Total payé:</span>
                                <span class="font-semibold">{{ number_format($report['summary']['paid_commissions']['total'], 2) }} €</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="p-4 bg-red-50 border-b border-red-100">
                            <h2 class="text-lg font-semibold text-red-800">Commissions impayées</h2>
                        </div>
                        <div class="p-4">
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-600">Admin:</span>
                                <span class="font-semibold">{{ number_format($report['summary']['unpaid_commissions']['admin'], 2) }} €</span>
                            </div>
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-600">Intégrateur:</span>
                                <span class="font-semibold">{{ number_format($report['summary']['unpaid_commissions']['integrator'], 2) }} €</span>
                            </div>
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-600">Partenaire:</span>
                                <span class="font-semibold">{{ number_format($report['summary']['unpaid_commissions']['partner'], 2) }} €</span>
                            </div>
                            <div class="flex justify-between mb-2 pt-2 border-t">
                                <span class="text-gray-600">Total impayé:</span>
                                <span class="font-semibold">{{ number_format($report['summary']['unpaid_commissions']['total'], 2) }} €</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Graphique des commissions -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                    <div class="p-4 border-b">
                        <h2 class="text-lg font-semibold">Évolution des commissions</h2>
                    </div>
                    <div class="p-4">
                        <canvas id="commissionsChart" height="300"></canvas>
                    </div>
                </div>

                <!-- Dernières transactions -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-4 border-b flex justify-between items-center">
                        <h2 class="text-lg font-semibold">Dernières transactions</h2>
                        <a href="{{ route('commissions.index') }}" class="text-blue-500 hover:text-blue-700">Voir toutes</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Point de charge</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Commissions</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($latestTransactions as $transaction)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $transaction->id }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $transaction->start_timestamp ? \Carbon\Carbon::parse($transaction->start_timestamp)->format('d/m/Y H:i') : 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ optional($transaction->chargingPoint)->name ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ number_format($transaction->price_total, 2) }} €
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <div class="text-xs">Admin: {{ number_format($transaction->admin_commission, 2) }} €</div>
                                        <div class="text-xs">Int.: {{ number_format($transaction->integrator_commission, 2) }} €</div>
                                        <div class="text-xs">Part.: {{ number_format($transaction->partner_commission, 2) }} €</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($transaction->areAllCommissionsPaid())
                                            <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Payée</span>
                                        @else
                                            <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded-full">En attente</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="{{ route('commissions.show', $transaction) }}" class="text-blue-600 hover:text-blue-900">Détails</a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                        Aucune transaction trouvée.
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
</div>

@push('scripts')
<script src="{{ asset("vendor/chartjs/chart.min.js") }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Préparer les données pour le graphique
        const ctx = document.getElementById('commissionsChart').getContext('2d');
        
        // Extraire les données du rapport
        const reportData = @json($report['details']);
        const labels = Object.keys(reportData).sort();
        
        const adminData = [];
        const integratorData = [];
        const partnerData = [];
        
        labels.forEach(date => {
            adminData.push(reportData[date].commissions.admin);
            integratorData.push(reportData[date].commissions.integrator);
            partnerData.push(reportData[date].commissions.partner);
        });
        
        // Créer le graphique
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Admin',
                        data: adminData,
                        backgroundColor: 'rgba(59, 130, 246, 0.5)',
                        borderColor: 'rgb(59, 130, 246)',
                        borderWidth: 1
                    },
                    {
                        label: 'Intégrateur',
                        data: integratorData,
                        backgroundColor: 'rgba(16, 185, 129, 0.5)',
                        borderColor: 'rgb(16, 185, 129)',
                        borderWidth: 1
                    },
                    {
                        label: 'Partenaire',
                        data: partnerData,
                        backgroundColor: 'rgba(245, 158, 11, 0.5)',
                        borderColor: 'rgb(245, 158, 11)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        stacked: false,
                    },
                    y: {
                        stacked: false,
                        beginAtZero: true
                    }
                }
            }
        });
    });
</script>
@endpush
@endsection