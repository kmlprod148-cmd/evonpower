@extends('layouts.app')

@section('title', 'Détails de la Transaction #' . $transaction->id)
@section('page-title', 'Détails de la Transaction #' . $transaction->id)

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="bg-primary-100 dark:bg-primary-900/30 rounded-lg p-3">
                        <i class="fas fa-receipt text-2xl text-primary-600 dark:text-primary-400"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Détails de la Transaction #{{ $transaction->id }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $transaction->created_at ? $transaction->created_at->format('d/m/Y H:i:s') : 'N/A' }} - {{ number_format($transaction->price_total ?? 0, 2) }} €</p>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-{{ $transaction->status ? $transaction->status->color() : 'gray' }}-100 text-{{ $transaction->status ? $transaction->status->color() : 'gray' }}-800 dark:bg-{{ $transaction->status ? $transaction->status->color() : 'gray' }}-900 dark:text-{{ $transaction->status ? $transaction->status->color() : 'gray' }}-200">
                    <i class="fas fa-circle text-xs mr-1.5 animate-pulse"></i>
                    {{ $transaction->status ? $transaction->status->label() : 'Non défini' }}
                </span>
            </div>
        </div>
    </div>

    <!-- Main Content Card -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6">
            <!-- Transaction Information -->
            <div class="space-y-4 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                    <i class="fas fa-receipt text-primary-600 dark:text-primary-400 mr-2"></i>
                    Informations sur la Transaction
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            <span class="font-medium">ID Transaction:</span> {{ $transaction->id }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            <span class="font-medium">Date:</span> {{ $transaction->created_at ? $transaction->created_at->format('d/m/Y H:i:s') : 'N/A' }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            <span class="font-medium">Montant Total Facturé:</span> €{{ number_format($transaction->price_total ?? 0, 2) }}
                        </p>
                    </div>
                    <div class="space-y-2">
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            <span class="font-medium">Statut:</span>
                            @if($transaction->status)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-{{ $transaction->status->color() }}-100 text-{{ $transaction->status->color() }}-800 dark:bg-{{ $transaction->status->color() }}-900 dark:text-{{ $transaction->status->color() }}-200">
                                    {{ $transaction->status->label() }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">N/A</span>
                            @endif
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            <span class="font-medium">Méthode de Paiement:</span> {{ $transaction->payment_method_details ?? 'N/A' }}
                        </p>
                    </div>
                </div>
            </div>

            <hr class="my-6 border-gray-200 dark:border-gray-700">
            
            <!-- Charging Session Context -->
            <div class="space-y-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                    <i class="fas fa-bolt text-primary-600 dark:text-primary-400 mr-2"></i>
                    Contexte de la Session de Recharge
                </h3>
                @if($transaction->reservation)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                <span class="font-medium">Session de Recharge:</span> 
                                <a href="{{ route('reservations.show', $transaction->reservation->id) }}" class="text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300">
                                    #{{ $transaction->reservation->id }}
                                </a>
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                <span class="font-medium">Utilisateur:</span> {{ $transaction->reservation->user->name ?? 'N/A' }}
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                <span class="font-medium">Début de la charge:</span> {{ $transaction->reservation->start_time ? $transaction->reservation->start_time->format('d/m/Y H:i') : 'N/A' }}
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                <span class="font-medium">Fin de la charge:</span> {{ $transaction->reservation->end_time ? $transaction->reservation->end_time->format('d/m/Y H:i') : 'N/A' }}
                            </p>
                        </div>
                        <div class="space-y-2">
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                <span class="font-medium">Borne de Recharge:</span> 
                                @if($transaction->chargingPoint)
                                    <a href="{{ route('charging-points.show', $transaction->chargingPoint->id) }}" class="text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300">
                                        {{ $transaction->chargingPoint->name }}
                                    </a>
                                @else
                                    N/A
                                @endif
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                <span class="font-medium">Adresse:</span> 
                                {{ $transaction->chargingPoint->station->address ?? $transaction->chargingPoint->address ?? 'N/A' }}
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                <span class="font-medium">Propriétaire de la Borne:</span> 
                                @if($transaction->chargingPoint)
                                    @if($transaction->chargingPoint->integrator)
                                        {{ $transaction->chargingPoint->integrator->name }}
                                    @elseif($transaction->chargingPoint->partner)
                                        {{ $transaction->chargingPoint->partner->name }}
                                    @else
                                        N/A
                                    @endif
                                @else
                                    N/A
                                @endif
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                <span class="font-medium">Énergie Consommée:</span> {{ number_format($transaction->reservation->energy_consumed ?? 0, 2) }} kWh
                            </p>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">Aucune session de recharge associée.</p>
                @endif
            </div>

            @if($transaction->businessProfile)
                <hr class="my-6 border-gray-200 dark:border-gray-700">
                
                <!-- Business Profile Information -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-building text-primary-600 dark:text-primary-400 mr-2"></i>
                        Profil Business Appliqué
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                <span class="font-medium">Nom du Profil:</span> {{ $transaction->businessProfile->name }}
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                <span class="font-medium">Statut:</span> 
                                @if($transaction->businessProfile->is_active)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Actif</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Inactif</span>
                                @endif
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                <span class="font-medium">Date de création:</span> {{ $transaction->businessProfile->created_at ? $transaction->businessProfile->created_at->format('d/m/Y à H:i') : 'N/A' }}
                            </p>
                        </div>
                        <div class="space-y-2">
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                <span class="font-medium">Créé par:</span> 
                                @if($transaction->businessProfile->creator)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                        {{ $transaction->businessProfile->creator->name ?? $transaction->businessProfile->creator->email ?? 'Utilisateur' }}
                                    </span>
                                @else
                                    <span class="text-gray-500 dark:text-gray-400">Système</span>
                                @endif
                            </p>
                            @if($transaction->businessProfile->integrator)
                                <p class="text-sm text-gray-600 dark:text-gray-300">
                                    <span class="font-medium">Intégrateur:</span> {{ $transaction->businessProfile->integrator->name }}
                                </p>
                            @endif
                            @if($transaction->businessProfile->partner)
                                <p class="text-sm text-gray-600 dark:text-gray-300">
                                    <span class="font-medium">Partenaire:</span> {{ $transaction->businessProfile->partner->name }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <hr class="my-6 border-gray-200 dark:border-gray-700">
            
            <!-- Hierarchical Transaction Details -->
            @if($transaction->transactionDetail)
                <div class="space-y-4 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-sitemap text-primary-600 dark:text-primary-400 mr-2"></i>
                        Détails des Transactions Hiérarchiques
                        <span class="ml-2 text-xs text-green-600">
                            <i class="fas fa-check-circle"></i> Selon Business Profiles
                        </span>
                    </h3>
                    @php
                        $transactionDetail = $transaction->transactionDetail;
                        $calculationDetails = $transactionDetail->calculation_details ?? [];
                        $hierarchicalTransactions = $transaction->hierarchicalTransactions ?? collect();
                    @endphp
                    
                    @if($transactionDetail)
                        <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                            <h4 class="font-semibold text-blue-800 dark:text-blue-200 mb-2">Résumé de la Transaction Hiérarchique</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                        €{{ number_format($transactionDetail->admin_share_amount, 2) }}
                                    </div>
                                    <div class="text-sm text-blue-700 dark:text-blue-300">Part Admin ({{ $transactionDetail->admin_share_percentage }}%)</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                        €{{ number_format($transactionDetail->integrator_share_amount, 2) }}
                                    </div>
                                    <div class="text-sm text-green-700 dark:text-green-300">Part Intégrateur ({{ $transactionDetail->integrator_share_percentage }}%)</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                        €{{ number_format($transactionDetail->operator_share_amount, 2) }}
                                    </div>
                                    <div class="text-sm text-purple-700 dark:text-purple-300">Part Opérateur</div>
                                </div>
                            </div>
                        </div>
                        
                        @if($hierarchicalTransactions->count() > 0)
                            <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                                <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-2">Transactions Hiérarchiques Créées</h4>
                                <div class="space-y-2">
                                    @foreach($hierarchicalTransactions as $hierarchicalTransaction)
                                        <div class="flex justify-between items-center p-2 bg-white dark:bg-gray-700 rounded">
                                            <div>
                                                <span class="font-medium">{{ $hierarchicalTransaction->getTransactionTypeLabel() }}</span>
                                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $hierarchicalTransaction->payer->name ?? 'N/A' }} → {{ $hierarchicalTransaction->payee->name ?? 'N/A' }}
                                                </span>
                                            </div>
                                            <div class="text-right">
                                                <div class="font-semibold">€{{ number_format($hierarchicalTransaction->amount, 2) }}</div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $hierarchicalTransaction->getStatusLabel() }}
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        
                        @if(isset($calculationDetails['business_profiles']))
                            <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg">
                                <h4 class="font-semibold text-yellow-800 dark:text-yellow-200 mb-2">Business Profiles Utilisés</h4>
                                <div class="space-y-2">
                                    @if(isset($calculationDetails['business_profiles']['admin_integrator']))
                                        <div class="text-sm">
                                            <span class="font-medium">Admin → Intégrateur:</span>
                                            {{ $calculationDetails['business_profiles']['admin_integrator']['name'] ?? 'N/A' }}
                                            @if(isset($calculationDetails['business_profiles']['admin_integrator']['creator']))
                                                <span class="text-gray-500 dark:text-gray-400">(Créé par: {{ $calculationDetails['business_profiles']['admin_integrator']['creator'] }})</span>
                                            @endif
                                        </div>
                                    @endif
                                    @if(isset($calculationDetails['business_profiles']['integrator_operator']))
                                        <div class="text-sm">
                                            <span class="font-medium">Intégrateur → Opérateur:</span>
                                            {{ $calculationDetails['business_profiles']['integrator_operator']['name'] ?? 'N/A' }}
                                            @if(isset($calculationDetails['business_profiles']['integrator_operator']['creator']))
                                                <span class="text-gray-500 dark:text-gray-400">(Créé par: {{ $calculationDetails['business_profiles']['integrator_operator']['creator'] }})</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            @endif

            <!-- Related Transactions (Admin → Intégrateur et Intégrateur → Opérateur) -->
            @php
                $relatedTransactions = \App\Models\Transaction::where('reservation_id', $transaction->reservation_id)
                    ->whereIn('transaction_type', ['admin_integrator', 'integrator_operator'])
                    ->where('id', '!=', $transaction->id)
                    ->get();
            @endphp
            
            @if($relatedTransactions->count() > 0)
                <div class="space-y-4 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-exchange-alt text-primary-600 dark:text-primary-400 mr-2"></i>
                        Transactions Liées (Double Transaction)
                    </h3>
                    
                    @foreach($relatedTransactions as $relatedTransaction)
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="font-medium text-gray-900 dark:text-white">
                                    @if($relatedTransaction->transaction_type === 'admin_integrator')
                                        <i class="fas fa-arrow-right text-green-600 mr-2"></i>
                                        Transaction Admin → Intégrateur
                                    @else
                                        <i class="fas fa-arrow-right text-blue-600 mr-2"></i>
                                        Transaction Intégrateur → Opérateur
                                    @endif
                                </h4>
                                <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                    {{ ucfirst($relatedTransaction->status) }}
                                </span>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                <div>
                                    <p class="text-gray-600 dark:text-gray-400"><strong>ID Transaction:</strong> #{{ $relatedTransaction->id }}</p>
                                    <p class="text-gray-600 dark:text-gray-400"><strong>Montant:</strong> {{ number_format($relatedTransaction->amount, 2) }} {{ $relatedTransaction->currency }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-600 dark:text-gray-400"><strong>Commission Admin:</strong> {{ number_format($relatedTransaction->admin_commission, 2) }} {{ $relatedTransaction->currency }}</p>
                                    <p class="text-gray-600 dark:text-gray-400"><strong>Commission Intégrateur:</strong> {{ number_format($relatedTransaction->integrator_commission, 2) }} {{ $relatedTransaction->currency }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-600 dark:text-gray-400"><strong>Créée le:</strong> {{ $relatedTransaction->created_at->format('d/m/Y H:i') }}</p>
                                    <p class="text-gray-600 dark:text-gray-400"><strong>Description:</strong> {{ $relatedTransaction->description }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <hr class="my-6 border-gray-200 dark:border-gray-700">
            
            <!-- Revenue Breakdown -->
            <div class="space-y-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                    <i class="fas fa-handshake text-primary-600 dark:text-primary-400 mr-2"></i>
                    Répartition des Revenus (Système Hiérarchique)
                </h3>
                @php
                    // Récupérer les détails de transaction hiérarchique
                    $transactionDetail = $transaction->transactionDetail;
                    $relatedTransactions = \App\Models\Transaction::where('reservation_id', $transaction->reservation_id)
                        ->whereIn('transaction_type', ['admin_integrator', 'integrator_operator'])
                        ->where('id', '!=', $transaction->id)
                        ->get();
                @endphp
                
                @if($transactionDetail)
                    <!-- Résumé de la transaction originale -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                        <h4 class="font-semibold text-blue-800 dark:text-blue-200 mb-3">Transaction Originale</h4>
                        @php
                            $adminShareAmount = (float) ($transactionDetail->admin_share_amount ?? 0);
                            $integratorShareAmount = (float) ($transactionDetail->integrator_share_amount ?? 0);
                            $operatorShareAmount = (float) ($transactionDetail->operator_share_amount ?? 0);
                            $totalFeesAmount = $adminShareAmount + $integratorShareAmount;
                            $netAmount = $operatorShareAmount;
                            $totalAmount = $transaction->price_total ?? $transaction->amount ?? 0;
                        @endphp
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-gray-600 dark:text-gray-300">Montant Total:</span>
                                    <span class="font-bold text-lg">{{ number_format($totalAmount, 2) }} {{ $transaction->currency ?? 'EUR' }}</span>
                                </div>
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-gray-600 dark:text-gray-300">Frais Totaux:</span>
                                    <span class="font-semibold">{{ number_format($totalFeesAmount, 2) }} {{ $transaction->currency ?? 'EUR' }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600 dark:text-gray-300">Montant Net Opérateur:</span>
                                    <span class="font-semibold text-green-600">{{ number_format($netAmount, 2) }} {{ $transaction->currency ?? 'EUR' }}</span>
                                </div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-600 dark:text-gray-300 mb-2">
                                    <strong>Opérateur:</strong> {{ $transaction->user->name ?? 'N/A' }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-300 mb-2">
                                    <strong>Borne:</strong> {{ $transaction->chargingPoint->name ?? 'N/A' }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-300">
                                    <strong>Business Profile:</strong> {{ $transaction->businessProfile->name ?? 'N/A' }}
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Répartition des Parts -->
                    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                        <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-3">Répartition des Parts</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Part Admin -->
                            <div class="text-center p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                                <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                                    {{ number_format($transactionDetail->admin_share_amount, 2) }} {{ $transaction->currency }}
                                </div>
                                <div class="text-sm text-red-700 dark:text-red-300">Part Admin</div>
                                <div class="text-xs text-red-600 dark:text-red-400">{{ $transactionDetail->admin_share_percentage }}%</div>
                            </div>
                            
                            <!-- Part Intégrateur -->
                            <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                    {{ number_format($transactionDetail->integrator_share_amount, 2) }} {{ $transaction->currency }}
                                </div>
                                <div class="text-sm text-green-700 dark:text-green-300">Part Intégrateur</div>
                                <div class="text-xs text-green-600 dark:text-green-400">{{ $transactionDetail->integrator_share_percentage }}%</div>
                            </div>
                            
                            <!-- Part Opérateur -->
                            <div class="text-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                                <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                    {{ number_format($transactionDetail->operator_share_amount, 2) }} {{ $transaction->currency }}
                                </div>
                                <div class="text-sm text-purple-700 dark:text-purple-300">Part Opérateur</div>
                                {{-- Opérateur n'a pas de pourcentage (c'est le reste après déduction) --}}
                            </div>
                        </div>
                    </div>
                    
                    <!-- Transactions Créées -->
                    @if($relatedTransactions->count() > 0)
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg">
                            <h4 class="font-semibold text-yellow-800 dark:text-yellow-200 mb-3">Transactions Hiérarchiques Créées</h4>
                            <div class="space-y-3">
                                @foreach($relatedTransactions as $relatedTransaction)
                                    <div class="flex items-center justify-between p-3 bg-white dark:bg-gray-700 rounded-lg border">
                                        <div class="flex items-center">
                                            @if($relatedTransaction->transaction_type === 'admin_integrator')
                                                <i class="fas fa-arrow-right text-red-600 mr-3"></i>
                                                <div>
                                                    <div class="font-medium">Admin → Intégrateur</div>
                                                    <div class="text-sm text-gray-500">Transaction #{{ $relatedTransaction->id }}</div>
                                                </div>
                                            @else
                                                <i class="fas fa-arrow-right text-green-600 mr-3"></i>
                                                <div>
                                                    <div class="font-medium">Intégrateur → Opérateur</div>
                                                    <div class="text-sm text-gray-500">Transaction #{{ $relatedTransaction->id }}</div>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="text-right">
                                            <div class="font-bold">{{ number_format($relatedTransaction->amount, 2) }} {{ $relatedTransaction->currency }}</div>
                                            <div class="text-sm text-gray-500">
                                                <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                                    {{ ucfirst($relatedTransaction->status) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    
                    <!-- Détail des Frais depuis TransactionDetail (source de vérité) -->
                    @php
                        $transactionDetail = $transaction->transactionDetail;
                        if ($transactionDetail) {
                            $adminShare = (float) ($transactionDetail->admin_share_amount ?? 0);
                            $integratorShare = (float) ($transactionDetail->integrator_share_amount ?? 0);
                            $operatorShare = (float) ($transactionDetail->operator_share_amount ?? 0);
                            $totalFees = $adminShare + $integratorShare;
                            $hasRealData = true;
                        } else {
                            // Fallback sur les méthodes du modèle Transaction
                            $adminShare = $transaction->getAdminFee();
                            $integratorShare = $transaction->getIntegratorFee();
                            $operatorShare = 0;
                            $totalFees = $transaction->getTotalFees();
                            $hasRealData = false;
                        }
                        $totalAmount = $transaction->price_total ?? $transaction->amount ?? 0;
                    @endphp
                    
                    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                        <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-3">
                            Répartition Hiérarchique
                            @if($hasRealData)
                                <span class="ml-2 text-xs text-green-600">
                                    <i class="fas fa-check-circle"></i> Selon Business Profiles
                                </span>
                            @endif
                        </h4>
                        <div class="space-y-2">
                            @if(auth()->user()->role === 'admin' && $adminShare > 0)
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 dark:text-gray-300">
                                    <i class="fas fa-user-shield text-red-500 mr-1"></i>
                                    Part Admin:
                                </span>
                                <span class="font-semibold text-red-600">
                                    {{ number_format($adminShare, 2) }} {{ $transaction->currency ?? 'EUR' }}
                                    @if($transactionDetail && $transactionDetail->admin_share_percentage)
                                        <small class="text-gray-500">({{ number_format($transactionDetail->admin_share_percentage, 2) }}%)</small>
                                    @endif
                                </span>
                            </div>
                            @endif
                            
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 dark:text-gray-300">
                                    <i class="fas fa-user-tie text-blue-500 mr-1"></i>
                                    Part Intégrateur:
                                </span>
                                <span class="font-semibold text-blue-600">
                                    {{ number_format($integratorShare, 2) }} {{ $transaction->currency ?? 'EUR' }}
                                    @if($transactionDetail && $transactionDetail->integrator_share_percentage)
                                        <small class="text-gray-500">({{ number_format($transactionDetail->integrator_share_percentage, 2) }}%)</small>
                                    @endif
                                </span>
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 dark:text-gray-300">
                                    <i class="fas fa-user-cog text-green-500 mr-1"></i>
                                    Part Opérateur:
                                </span>
                                <span class="font-semibold text-green-600">
                                    {{ number_format($operatorShare, 2) }} {{ $transaction->currency ?? 'EUR' }}
                                </span>
                            </div>
                            
                            <hr class="my-2">
                            <div class="flex justify-between items-center font-bold">
                                <span>Total des Frais:</span>
                                <span>{{ number_format($totalFees, 2) }} {{ $transaction->currency ?? 'EUR' }}</span>
                            </div>
                            <div class="flex justify-between items-center font-bold text-green-600">
                                <span>Montant Total:</span>
                                <span>{{ number_format($totalAmount, 2) }} {{ $transaction->currency ?? 'EUR' }}</span>
                            </div>
                            <div class="flex justify-between items-center text-sm text-gray-500">
                                <span>Net Opérateur:</span>
                                <span>{{ number_format($operatorShare, 2) }} {{ $transaction->currency ?? 'EUR' }}</span>
                            </div>
                        </div>
                        
                        @if($hasRealData && auth()->user()->role === 'admin' && $transactionDetail->calculation_details)
                            <div class="mt-3 pt-3 border-t border-gray-300">
                                <small class="text-gray-600 dark:text-gray-400">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Calcul basé sur Business Profiles (TransactionDetail #{{ $transactionDetail->id }})
                                </small>
                            </div>
                        @endif
                    </div>
                    
                @else
                    <!-- Fallback si pas de TransactionDetail -->
                    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                        <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-3">
                            Répartition des Frais
                            <span class="ml-2 text-xs text-yellow-600">
                                <i class="fas fa-exclamation-triangle"></i> Données estimées
                            </span>
                        </h4>
                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 dark:text-gray-300">Montant Total:</span>
                                <span class="font-bold">{{ number_format($transaction->price_total ?? $transaction->amount ?? 0, 2) }} {{ $transaction->currency ?? 'EUR' }}</span>
                            </div>
                            @php
                                $adminFee = $transaction->getAdminFee();
                                $integratorFee = $transaction->getIntegratorFee();
                                $totalFees = $transaction->getTotalFees();
                            @endphp
                            
                            @if(auth()->user()->role === 'admin' && $adminFee > 0)
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 dark:text-gray-300">Commission Admin:</span>
                                <span class="font-semibold">{{ number_format($adminFee, 2) }} {{ $transaction->currency ?? 'EUR' }}</span>
                            </div>
                            @endif
                            
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 dark:text-gray-300">Commission Intégrateur:</span>
                                <span class="font-semibold">{{ number_format($integratorFee, 2) }} {{ $transaction->currency ?? 'EUR' }}</span>
                            </div>
                            
                            <hr class="my-2">
                            <div class="flex justify-between items-center font-bold">
                                <span>Total Frais:</span>
                                <span>{{ number_format($totalFees, 2) }} {{ $transaction->currency ?? 'EUR' }}</span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Footer Actions -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex justify-center">
            <a href="{{ url()->previous() }}" 
               class="inline-flex items-center px-4 py-2 bg-gray-500 text-white text-sm font-medium rounded-lg hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors duration-200">
                <i class="fas fa-arrow-left mr-2"></i>Retour
            </a>
        </div>
    </div>
</div>
@endsection
