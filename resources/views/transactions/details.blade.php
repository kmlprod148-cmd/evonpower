@extends('layouts.app')

@section('title', 'Détails de la Transaction')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">
            <i class="fas fa-receipt mr-2 text-blue-600"></i>
            Détails de la Transaction
        </h1>
        <p class="text-gray-600">Transaction #{{ $transaction->transaction_id }}</p>
    </div>

    <!-- Informations générales -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Informations transaction -->
        <div class="lg:col-span-2">
            <div class="card">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        <i class="fas fa-info-circle mr-2 text-blue-600"></i>
                        Informations de la Transaction
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-500">ID Transaction</label>
                            <p class="text-gray-900 font-mono">{{ $transaction->transaction_id }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Statut</label>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if($transaction->status->value === 'completed') bg-green-100 text-green-800
                                @elseif($transaction->status->value === 'pending') bg-yellow-100 text-yellow-800
                                @elseif($transaction->status->value === 'active') bg-blue-100 text-blue-800
                                @else bg-gray-100 text-gray-800 @endif">
                                {{ ucfirst($transaction->status->value) }}
                            </span>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Montant Total</label>
                            <p class="text-gray-900 font-semibold">{{ number_format($transaction->price_total, 2) }} {{ $transaction->currency }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Current Balance (Opérateur)</label>
                            <p class="text-gray-900 font-semibold">
                                {{ $transaction->operator_current_balance ? number_format($transaction->operator_current_balance, 2) : ($transaction->current_balance ? number_format($transaction->current_balance, 2) : '0.00') }} {{ $transaction->currency ?? 'EUR' }}
                                <span class="text-xs text-gray-500 ml-2">updated</span>
                            </p>
                        </div>
                        @if($transaction->admin_current_balance !== null || $transaction->integrator_current_balance !== null)
                        <div>
                            <label class="text-sm font-medium text-gray-500">Admin Current Balance</label>
                            <p class="text-gray-900 font-semibold">
                                {{ $transaction->admin_current_balance ? number_format($transaction->admin_current_balance, 2) : '0.00' }} {{ $transaction->currency ?? 'EUR' }}
                                <span class="text-xs text-gray-500 ml-2">updated</span>
                            </p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Intégrateur Current Balance</label>
                            <p class="text-gray-900 font-semibold">
                                {{ $transaction->integrator_current_balance ? number_format($transaction->integrator_current_balance, 2) : '0.00' }} {{ $transaction->currency ?? 'EUR' }}
                                <span class="text-xs text-gray-500 ml-2">updated</span>
                            </p>
                        </div>
                        @endif
                        <div>
                            <label class="text-sm font-medium text-gray-500">Date</label>
                            <p class="text-gray-900">{{ $transaction->start_timestamp->format('d/m/Y H:i') }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Borne</label>
                            <p class="text-gray-900">{{ $transaction->chargingPoint->name }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Méthode d'authentification</label>
                            <p class="text-gray-900">{{ ucfirst(str_replace('_', ' ', $transaction->auth_method)) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Répartition (Source de vérité : TransactionDetail) -->
        <div>
            <div class="card">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        <i class="fas fa-chart-pie mr-2 text-green-600"></i>
                        Répartition Hiérarchique
                        @if($transaction->transactionDetail)
                            <span class="ml-2 text-xs text-green-600">
                                <i class="fas fa-check-circle"></i> Selon Business Profiles
                            </span>
                        @endif
                    </h3>
                    
                    @if(isset($transactionDetailErrors) && !empty($transactionDetailErrors))
                        {{-- Afficher les erreurs exactes avec croix rouge --}}
                        <div class="mb-4 p-4 bg-red-50 border-2 border-red-300 rounded-lg">
                            <div class="flex items-start">
                                <div class="flex-shrink-0 mr-3">
                                    <i class="fas fa-times-circle fa-2x text-red-600"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="font-semibold text-red-800 mb-2">
                                        <i class="fas fa-exclamation-triangle mr-2"></i>
                                        Erreur : TransactionDetail non disponible
                                    </h5>
                                    <p class="text-sm text-red-700 mb-2">Impossible de créer un TransactionDetail pour cette transaction en raison des erreurs suivantes :</p>
                                    <ul class="list-disc list-inside text-sm text-red-700 mb-2">
                                        @foreach($transactionDetailErrors as $error)
                                            <li><strong>{{ $error }}</strong></li>
                                        @endforeach
                                    </ul>
                                    <hr class="my-2 border-red-300">
                                    <small class="text-red-600">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        <strong>Action requise :</strong> Veuillez corriger les erreurs ci-dessus pour que cette transaction puisse être traitée correctement.
                                    </small>
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    @php
                        // PRIORITÉ : TransactionDetail (données réelles) > TransactionRepartition (fallback)
                        $transactionDetail = $transaction->transactionDetail;
                        if ($transactionDetail) {
                            $adminAmount = (float) ($transactionDetail->admin_share_amount ?? 0);
                            $integratorAmount = (float) ($transactionDetail->integrator_share_amount ?? 0);
                            $operatorAmount = (float) ($transactionDetail->operator_share_amount ?? 0);
                            $totalAmount = $transaction->amount ?? $transaction->price_total ?? ($adminAmount + $integratorAmount + $operatorAmount);
                            $hasRealData = true;
                        } elseif (isset($repartition)) {
                            $adminAmount = $repartition->admin_amount ?? 0;
                            $integratorAmount = $repartition->integrator_amount ?? 0;
                            $operatorAmount = $repartition->operator_amount ?? 0;
                            $totalAmount = $repartition->total_amount ?? $transaction->amount ?? 0;
                            $hasRealData = false;
                        } else {
                            // Dernier recours : utiliser les méthodes du modèle
                            $adminAmount = $transaction->getAdminFee();
                            $integratorAmount = $transaction->getIntegratorFee();
                            $operatorAmount = 0;
                            $totalAmount = $transaction->amount ?? $transaction->price_total ?? 0;
                            $hasRealData = false;
                        }
                        $currency = $transaction->currency ?? 'EUR';
                    @endphp
                    
                    @if($hasRealData)
                        <div class="mb-3 p-2 bg-green-50 border border-green-200 rounded text-xs text-green-700">
                            <i class="fas fa-check-circle mr-1"></i>
                            Données réelles calculées selon Business Profiles
                        </div>
                    @else
                        <div class="mb-3 p-2 bg-yellow-50 border border-yellow-200 rounded text-xs text-yellow-700">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            Données estimées - TransactionDetail non disponible
                        </div>
                    @endif
                    
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">
                                <i class="fas fa-user-shield mr-1 text-red-500"></i>
                                Admin
                            </span>
                            <span class="font-semibold text-red-600">
                                {{ number_format($adminAmount, 2) }} {{ $currency }}
                            </span>
                            @if($transactionDetail && $transactionDetail->admin_share_percentage)
                                <span class="text-xs text-gray-500 ml-2">
                                    ({{ number_format($transactionDetail->admin_share_percentage, 2) }}%)
                                </span>
                            @endif
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">
                                <i class="fas fa-user-tie mr-1 text-blue-500"></i>
                                Intégrateur
                            </span>
                            <span class="font-semibold text-blue-600">
                                {{ number_format($integratorAmount, 2) }} {{ $currency }}
                            </span>
                            @if($transactionDetail && $transactionDetail->integrator_share_percentage)
                                <span class="text-xs text-gray-500 ml-2">
                                    ({{ number_format($transactionDetail->integrator_share_percentage, 2) }}%)
                                </span>
                            @endif
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">
                                <i class="fas fa-user-cog mr-1 text-green-500"></i>
                                Opérateur
                            </span>
                            <span class="font-semibold text-green-600">
                                {{ number_format($operatorAmount, 2) }} {{ $currency }}
                            </span>
                        </div>
                        <hr class="my-2">
                        <div class="flex justify-between items-center font-semibold">
                            <span>Total</span>
                            <span>{{ number_format($totalAmount, 2) }} {{ $currency }}</span>
                        </div>
                        @if($hasRealData)
                            <div class="mt-2 text-xs text-gray-500 text-center">
                                <i class="fas fa-info-circle mr-1"></i>
                                La somme des parts = {{ number_format($adminAmount + $integratorAmount + $operatorAmount, 2) }} {{ $currency }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Transactions Hiérarchiques -->
    <div class="card">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">
                <i class="fas fa-sitemap mr-2 text-purple-600"></i>
                Transactions Hiérarchiques Créées
            </h3>

            @if(count($hierarchicalTransactions) > 0)
                <div class="space-y-4">
                    @foreach($hierarchicalTransactions as $index => $hierarchical)
                        <div class="border rounded-lg p-4 {{ $index % 2 === 0 ? 'bg-gray-50' : 'bg-white' }}">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <!-- Icône -->
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center
                                            @if($hierarchical['color'] === 'blue') bg-blue-100 text-blue-600
                                            @elseif($hierarchical['color'] === 'green') bg-green-100 text-green-600
                                            @elseif($hierarchical['color'] === 'purple') bg-purple-100 text-purple-600
                                            @else bg-gray-100 text-gray-600 @endif">
                                            <i class="fas fa-exchange-alt"></i>
                                        </div>
                                    </div>

                                    <!-- Informations -->
                                    <div>
                                        <div class="flex items-center space-x-2">
                                            <span class="font-semibold text-gray-900">{{ $hierarchical['from'] }}</span>
                                            <i class="fas fa-arrow-right text-gray-400"></i>
                                            <span class="font-semibold text-gray-900">{{ $hierarchical['to'] }}</span>
                                        </div>
                                        <p class="text-sm text-gray-600">{{ $hierarchical['description'] }}</p>
                                        <p class="text-xs text-gray-500 font-mono">{{ $hierarchical['transaction_id'] }}</p>
                                    </div>
                                </div>

                                <!-- Montant et statut -->
                                <div class="text-right">
                                    <div class="text-lg font-bold text-gray-900">
                                        {{ number_format($hierarchical['amount'], 2) }} {{ $transaction->currency }}
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($hierarchical['status'] === 'completed') bg-green-100 text-green-800
                                        @elseif($hierarchical['status'] === 'pending') bg-yellow-100 text-yellow-800
                                        @else bg-gray-100 text-gray-800 @endif">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        {{ ucfirst($hierarchical['status']) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <i class="fas fa-info-circle text-gray-400 text-4xl mb-4"></i>
                    <p class="text-gray-600">Aucune transaction hiérarchique générée pour cette transaction.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Actions -->
    <div class="mt-6 flex justify-between">
        <a href="{{ url()->previous() }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>
            Retour
        </a>
        
        <div class="space-x-2">
            <button onclick="refreshHierarchicalTransactions()" class="btn btn-primary">
                <i class="fas fa-sync-alt mr-2"></i>
                Actualiser
            </button>
            
            <a href="{{ route('transactions.export', $transaction->id) }}" class="btn btn-success">
                <i class="fas fa-download mr-2"></i>
                Exporter
            </a>
        </div>
    </div>
</div>

<!-- Modal pour les détails supplémentaires -->
<div id="transaction-details-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Détails de la Transaction</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="modal-content">
                    <!-- Contenu dynamique -->
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .card {
        @apply bg-white rounded-lg shadow-sm border border-gray-200;
    }
    
    .btn {
        @apply px-4 py-2 rounded-lg font-medium transition-colors duration-200;
    }
    
    .btn-primary {
        @apply bg-blue-600 text-white hover:bg-blue-700;
    }
    
    .btn-secondary {
        @apply bg-gray-600 text-white hover:bg-gray-700;
    }
    
    .btn-success {
        @apply bg-green-600 text-white hover:bg-green-700;
    }
</style>
@endpush

@push('scripts')
<script>
    function refreshHierarchicalTransactions() {
        // Afficher un indicateur de chargement
        const button = event.target;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Actualisation...';
        button.disabled = true;

        // Faire une requête AJAX pour actualiser les données
        fetch(`/api/transactions/{{ $transaction->id }}/hierarchical`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Recharger la page pour afficher les nouvelles données
                    window.location.reload();
                } else {
                    alert('Erreur lors de l\'actualisation: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de l\'actualisation');
            })
            .finally(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            });
    }

    function closeModal() {
        document.getElementById('transaction-details-modal').classList.add('hidden');
    }

    // Fonction pour afficher les détails d'une transaction hiérarchique
    function showTransactionDetails(transactionId) {
        // Remplir le modal avec les détails
        document.getElementById('modal-content').innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-spinner fa-spin text-2xl text-blue-600"></i>
                <p class="mt-2 text-gray-600">Chargement des détails...</p>
            </div>
        `;
        
        document.getElementById('transaction-details-modal').classList.remove('hidden');
        
        // Simuler le chargement des détails
        setTimeout(() => {
            document.getElementById('modal-content').innerHTML = `
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-500">ID Transaction</label>
                        <p class="text-gray-900 font-mono">${transactionId}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Statut</label>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            Terminé
                        </span>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Date de création</label>
                        <p class="text-gray-900">{{ now()->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            `;
        }, 1000);
    }
</script>
@endpush

<!-- Détails de TransactionRepartition -->
@php
    $repartition = $transaction->repartition ?? $transaction->repartitions->first() ?? $repartition ?? null;
@endphp

@if($repartition)
    @include('components.transaction-repartition-details', ['transaction' => $transaction, 'repartition' => $repartition])
@endif

<!-- Répartition des montants -->
@if(isset($repartition))
<div class="card mb-8">
    <div class="p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-chart-pie mr-2 text-green-600"></i>
            Répartition des Montants
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Part Admin -->
            <div class="text-center p-4 bg-red-50 rounded-lg border border-red-200">
                <div class="text-2xl font-bold text-red-600 mb-2">
                    {{ number_format($repartition->admin_amount, 2) }} €
                </div>
                <div class="text-sm text-red-700 font-medium">Part Admin</div>
                <div class="text-xs text-red-600 mt-1">
                    {{ $repartition->getTotalAmount() > 0 ? number_format(($repartition->admin_amount / $repartition->getTotalAmount()) * 100, 1) : 0 }}%
                </div>
            </div>
            
            <!-- Part Intégrateur -->
            <div class="text-center p-4 bg-blue-50 rounded-lg border border-blue-200">
                <div class="text-2xl font-bold text-blue-600 mb-2">
                    {{ number_format($repartition->integrator_amount, 2) }} €
                </div>
                <div class="text-sm text-blue-700 font-medium">Part Intégrateur</div>
                <div class="text-xs text-blue-600 mt-1">
                    {{ $repartition->getTotalAmount() > 0 ? number_format(($repartition->integrator_amount / $repartition->getTotalAmount()) * 100, 1) : 0 }}%
                </div>
            </div>
            
            <!-- Part Opérateur -->
            <div class="text-center p-4 bg-green-50 rounded-lg border border-green-200">
                <div class="text-2xl font-bold text-green-600 mb-2">
                    {{ number_format($repartition->operator_amount, 2) }} €
                </div>
                <div class="text-sm text-green-700 font-medium">Part Opérateur</div>
                <div class="text-xs text-green-600 mt-1">
                    {{ $repartition->getTotalAmount() > 0 ? number_format(($repartition->operator_amount / $repartition->getTotalAmount()) * 100, 1) : 0 }}%
                </div>
            </div>
        </div>
        
        <!-- Total -->
        <div class="mt-6 pt-4 border-t border-gray-200">
            <div class="flex justify-between items-center">
                <span class="text-lg font-semibold text-gray-900">Total Réparti</span>
                <span class="text-xl font-bold text-gray-900">{{ number_format($repartition->getTotalAmount(), 2) }} €</span>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Détails du calcul de répartition -->
@if(isset($calculationDetails) && isset($calculationDetails['details']))
<div class="card mb-8">
    <div class="p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-calculator mr-2 text-purple-600"></i>
            Détails du Calcul de Répartition
        </h3>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Détails Admin -->
            <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                <h4 class="font-semibold text-red-800 mb-3">Frais Admin</h4>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-red-700">Frais de transaction:</span>
                        <span class="font-medium">{{ number_format($calculationDetails['details']['admin_fees_breakdown']['transaction_fee'], 2) }} €</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-red-700">Frais de recharge:</span>
                        <span class="font-medium">{{ number_format($calculationDetails['details']['admin_fees_breakdown']['recharge_fee'], 2) }} €</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-red-700">Autres frais:</span>
                        <span class="font-medium">{{ number_format($calculationDetails['details']['admin_fees_breakdown']['other_fees'], 2) }} €</span>
                    </div>
                    <div class="flex justify-between border-t border-red-300 pt-2">
                        <span class="text-red-700 font-medium">Total frais fixes:</span>
                        <span class="font-bold">{{ number_format($calculationDetails['details']['admin_fees_breakdown']['total_fixed_fees'], 2) }} €</span>
                    </div>
                    @if($calculationDetails['details']['admin_fees_breakdown']['admin_fee_fixed'] > 0)
                    <div class="flex justify-between">
                        <span class="text-red-700">Frais admin fixes:</span>
                        <span class="font-medium">{{ number_format($calculationDetails['details']['admin_fees_breakdown']['admin_fee_fixed'], 2) }} €</span>
                    </div>
                    @endif
                    @if($calculationDetails['details']['admin_fees_breakdown']['admin_fee_percentage'] > 0)
                    <div class="flex justify-between">
                        <span class="text-red-700">Pourcentage admin:</span>
                        <span class="font-medium">{{ $calculationDetails['details']['admin_fees_breakdown']['admin_fee_percentage'] }}%</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-red-700">Montant pourcentage:</span>
                        <span class="font-medium">{{ number_format($calculationDetails['details']['admin_fees_breakdown']['admin_percentage_amount'], 2) }} €</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Détails Intégrateur -->
            @if($calculationDetails['details']['integrator_details'])
            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                <h4 class="font-semibold text-blue-800 mb-3">Part Intégrateur</h4>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-blue-700">Pourcentage intégrateur:</span>
                        <span class="font-medium">{{ $calculationDetails['details']['integrator_details']['integrator_fee_percentage'] }}%</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-blue-700">Montant brut:</span>
                        <span class="font-medium">{{ number_format($calculationDetails['details']['integrator_details']['gross_amount'], 2) }} €</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-blue-700">Frais fixes:</span>
                        <span class="font-medium">{{ number_format($calculationDetails['details']['integrator_details']['integrator_fixed_fee'], 2) }} €</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-blue-700">Déduction admin:</span>
                        <span class="font-medium text-red-600">-{{ number_format($calculationDetails['details']['integrator_details']['admin_deduction'], 2) }} €</span>
                    </div>
                    <div class="flex justify-between border-t border-blue-300 pt-2">
                        <span class="text-blue-700 font-medium">Montant net:</span>
                        <span class="font-bold">{{ number_format($calculationDetails['details']['integrator_details']['net_amount'], 2) }} €</span>
                    </div>
                </div>
            </div>
            @endif

            <!-- Détails Opérateur -->
            <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                <h4 class="font-semibold text-green-800 mb-3">Part Opérateur</h4>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-green-700">Montant total:</span>
                        <span class="font-medium">{{ number_format($calculationDetails['details']['operator_details']['total_amount'], 2) }} €</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-green-700">Déduction admin:</span>
                        <span class="font-medium text-red-600">-{{ number_format($calculationDetails['details']['operator_details']['admin_deduction'], 2) }} €</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-green-700">Déduction intégrateur:</span>
                        <span class="font-medium text-red-600">-{{ number_format($calculationDetails['details']['operator_details']['integrator_deduction'], 2) }} €</span>
                    </div>
                    <div class="flex justify-between border-t border-green-300 pt-2">
                        <span class="text-green-700 font-medium">Montant net:</span>
                        <span class="font-bold">{{ number_format($calculationDetails['details']['operator_details']['net_amount'], 2) }} €</span>
                    </div>
                </div>
            </div>

            <!-- Informations Business Profile -->
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <h4 class="font-semibold text-gray-800 mb-3">Business Profiles</h4>
                <div class="space-y-2 text-sm">
                    @if($calculationDetails['details']['business_profile_info']['bp_id'])
                    <div class="flex justify-between">
                        <span class="text-gray-700">BP Point de charge:</span>
                        <span class="font-medium">{{ $calculationDetails['details']['business_profile_info']['bp_name'] ?? 'ID: ' . $calculationDetails['details']['business_profile_info']['bp_id'] }}</span>
                    </div>
                    @endif
                    @if($calculationDetails['details']['business_profile_info']['integrator_bp_id'])
                    <div class="flex justify-between">
                        <span class="text-gray-700">BP Intégrateur:</span>
                        <span class="font-medium">{{ $calculationDetails['details']['business_profile_info']['integrator_bp_name'] ?? 'ID: ' . $calculationDetails['details']['business_profile_info']['integrator_bp_id'] }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endif