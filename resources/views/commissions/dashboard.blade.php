@extends('layouts.app')

@section('title', 'Tableau de Bord Business Plan')

@section('content')
<div class="px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Tableau de Bord Business Plan</h1>
        <div class="flex space-x-2">
            <a href="{{ route('plans.create') }}" class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                Créer Business Plan
            </a>
            <a href="{{ route('commissions.reports') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                Rapports détaillés
            </a>
            <a href="{{ route('commissions.export') }}?start_date={{ $startDate }}&end_date={{ $endDate }}" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                Exporter CSV
            </a>
        </div>
    </div>

    @include('partials.flash-messages')

    <!-- Onglets de navigation -->
    <div class="mb-6 border-b border-gray-200">
        <ul class="flex flex-wrap -mb-px text-sm font-medium text-center">
            <li class="mr-2">
                <a href="#overview" class="inline-block p-4 border-b-2 border-blue-600 rounded-t-lg active text-blue-600" aria-current="page">
                    Vue d'ensemble
                </a>
            </li>
            <li class="mr-2">
                <a href="#business-plan" class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300">
                    Business Plan
                </a>
            </li>
            <li class="mr-2">
                <a href="#transactions" class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300">
                    Transactions
                </a>
            </li>
        </ul>
    </div>

    <!-- Filtres de période -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="p-4">
            <form action="{{ route('commissions.dashboard') }}" method="GET" class="flex flex-wrap gap-4">
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

    <!-- Section Vue d'ensemble -->
    <div id="overview-content" class="tab-content">
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
    </div>

    <!-- Section Business Plan -->
    <div id="business-plan-content" class="tab-content hidden">
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="p-4 border-b">
                <h2 class="text-lg font-semibold">Business Plan basé sur les plans de commission</h2>
            </div>
            <div class="p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-md font-semibold mb-4">Plans de commission actifs</h3>
                        <div class="space-y-4">
                            @foreach(\App\Models\CommissionPlan::where('is_active', true)->get() as $plan)
                            <div class="border rounded-lg p-4">
                                <div class="flex justify-between items-center mb-2">
                                    <h4 class="font-semibold">{{ $plan->name }}</h4>
                                    <div class="flex space-x-1">
                                        @if($plan->is_default)
                                            <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">Par défaut</span>
                                        @endif
                                        <span class="px-2 py-1 text-xs bg-purple-100 text-purple-800 rounded-full">
                                            @switch($plan->transaction_manager)
                                                @case('admin')
                                                    Géré par Admin
                                                    @break
                                                @case('integrator')
                                                    Géré par Intégrateur
                                                    @break
                                                @case('partner')
                                                    Géré par Partenaire
                                                    @break
                                                @case('shared')
                                                    Gestion partagée
                                                    @break
                                                @default
                                                    Géré par Admin
                                            @endswitch
                                        </span>
                                    </div>
                                </div>
                                <div class="text-sm text-gray-600 mb-2">{{ $plan->description }}</div>
                                <div class="grid grid-cols-3 gap-2 text-sm">
                                    <div>
                                        <span class="block text-gray-500">Admin</span>
                                        <span class="font-medium">{{ $plan->admin_percentage }}%</span>
                                    </div>
                                    <div>
                                        <span class="block text-gray-500">Intégrateur</span>
                                        <span class="font-medium">{{ $plan->integrator_percentage }}%</span>
                                    </div>
                                    <div>
                                        <span class="block text-gray-500">Partenaire</span>
                                        <span class="font-medium">{{ $plan->partner_percentage }}%</span>
                                    </div>
                                </div>
                                @if($plan->requires_approval)
                                <div class="mt-2 pt-2 border-t border-gray-100">
                                    <span class="text-xs text-amber-600">Nécessite une approbation pour certaines actions</span>
                                </div>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <h3 class="text-md font-semibold mb-4">Projections financières</h3>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Montant mensuel estimé (€)</label>
                                <input type="number" id="estimated-amount" class="w-full rounded border-gray-300" value="10000">
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Plan de commission</label>
                                <select id="commission-plan" class="w-full rounded border-gray-300">
                                    @foreach(\App\Models\CommissionPlan::where('is_active', true)->get() as $plan)
                                    <option value="{{ $plan->id }}" 
                                        data-admin="{{ $plan->admin_percentage }}" 
                                        data-integrator="{{ $plan->integrator_percentage }}" 
                                        data-partner="{{ $plan->partner_percentage }}">
                                        {{ $plan->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-4">
                                <button id="calculate-projection" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                                    Calculer
                                </button>
                            </div>
                            <div id="projection-results" class="mt-4 p-4 border rounded-lg bg-white">
                                <h4 class="font-semibold mb-2">Résultats de la projection</h4>
                                <div class="grid grid-cols-2 gap-2">
                                    <div class="text-sm">
                                        <span class="block text-gray-500">Revenu mensuel</span>
                                        <span id="monthly-revenue" class="font-medium">10,000.00 €</span>
                                    </div>
                                    <div class="text-sm">
                                        <span class="block text-gray-500">Revenu annuel</span>
                                        <span id="annual-revenue" class="font-medium">120,000.00 €</span>
                                    </div>
                                    <div class="text-sm">
                                        <span class="block text-gray-500">Commission Admin</span>
                                        <span id="admin-commission" class="font-medium">0.00 €</span>
                                    </div>
                                    <div class="text-sm">
                                        <span class="block text-gray-500">Commission Intégrateur</span>
                                        <span id="integrator-commission" class="font-medium">0.00 €</span>
                                    </div>
                                    <div class="text-sm">
                                        <span class="block text-gray-500">Commission Opérateur (Partenaire)</span>
                                        <span id="partner-commission" class="font-medium">0.00 €</span>
                                    </div>
                                    <div class="text-sm">
                                        <span class="block text-gray-500">Total Commissions</span>
                                        <span id="total-commission" class="font-medium">0.00 €</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Transactions -->
    <div id="transactions-content" class="tab-content hidden">
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

@push('scripts')
<script src="{{ asset("vendor/chartjs/chart.min.js") }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Gestion des onglets
        const tabs = document.querySelectorAll('ul.flex a');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Retirer la classe active de tous les onglets
                tabs.forEach(t => {
                    t.classList.remove('border-blue-600', 'text-blue-600');
                    t.classList.add('border-transparent');
                });
                
                // Ajouter la classe active à l'onglet cliqué
                this.classList.add('border-blue-600', 'text-blue-600');
                this.classList.remove('border-transparent');
                
                // Masquer tous les contenus
                tabContents.forEach(content => {
                    content.classList.add('hidden');
                });
                
                // Afficher le contenu correspondant
                const target = this.getAttribute('href').substring(1);
                document.getElementById(target + '-content').classList.remove('hidden');
            });
        });
        
        // Afficher l'onglet par défaut (Vue d'ensemble)
        document.querySelector('ul.flex a').click();
        
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
        
        // Calculateur de projection pour le Business Plan
        document.getElementById('calculate-projection').addEventListener('click', function() {
            const amount = parseFloat(document.getElementById('estimated-amount').value) || 0;
            const planSelect = document.getElementById('commission-plan');
            const selectedOption = planSelect.options[planSelect.selectedIndex];
            
            // Vérifier que selectedOption existe et a les propriétés dataset
            if (!selectedOption || !selectedOption.dataset) {
                console.error('Option sélectionnée non trouvée ou sans dataset');
                return;
            }
            
            const adminPercentage = parseFloat(selectedOption.dataset.admin) || 0;
            const integratorPercentage = parseFloat(selectedOption.dataset.integrator) || 0;
            const partnerPercentage = parseFloat(selectedOption.dataset.partner) || 0;
            
            const adminCommission = amount * (adminPercentage / 100);
            const integratorCommission = amount * (integratorPercentage / 100);
            const partnerCommission = amount * (partnerPercentage / 100);
            const totalCommission = adminCommission + integratorCommission + partnerCommission;
            
            document.getElementById('monthly-revenue').textContent = amount.toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' €';
            document.getElementById('annual-revenue').textContent = (amount * 12).toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' €';
            document.getElementById('admin-commission').textContent = adminCommission.toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' €';
            document.getElementById('integrator-commission').textContent = integratorCommission.toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' €';
            document.getElementById('partner-commission').textContent = partnerCommission.toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' €';
            document.getElementById('total-commission').textContent = totalCommission.toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' €';
        });
    });
</script>
@endpush
@endsection