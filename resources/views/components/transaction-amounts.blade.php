@props(['transaction', 'user' => null, 'showDetails' => true])

@php
    $component = new \App\View\Components\TransactionAmounts($transaction, $user);
@endphp

<div class="transaction-amounts-real-data" data-has-real-data="{{ $component->hasRealData ? 'true' : 'false' }}">
    @if($component->hasRealData)
        <div class="alert alert-success mb-3">
            <i class="fas fa-check-circle mr-2"></i>
            <strong>Données réelles</strong> - Calculées selon Business Profiles
        </div>
    @else
        <div class="alert alert-warning mb-3">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <strong>Données estimées</strong> - TransactionDetail non disponible
        </div>
    @endif
    
    @if($showDetails && $component->repartitionDetails['has_repartition'])
        <div class="repartition-grid grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div class="repartition-card bg-red-50 border border-red-200 rounded-lg p-4 text-center">
                <div class="text-sm text-gray-600 mb-1">Part Admin</div>
                <div class="text-2xl font-bold text-red-600">
                    {{ number_format($component->getAdminShare(), 2) }} €
                </div>
                @if($component->transaction->transactionDetail && $component->transaction->transactionDetail->admin_share_percentage)
                    <div class="text-xs text-gray-500 mt-1">
                        {{ number_format($component->transaction->transactionDetail->admin_share_percentage, 2) }}%
                    </div>
                @endif
            </div>
            
            <div class="repartition-card bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
                <div class="text-sm text-gray-600 mb-1">Part Intégrateur</div>
                <div class="text-2xl font-bold text-blue-600">
                    {{ number_format($component->getIntegratorShare(), 2) }} €
                </div>
                @if($component->transaction->transactionDetail && $component->transaction->transactionDetail->integrator_share_percentage)
                    <div class="text-xs text-gray-500 mt-1">
                        {{ number_format($component->transaction->transactionDetail->integrator_share_percentage, 2) }}%
                    </div>
                @endif
            </div>
            
            <div class="repartition-card bg-green-50 border border-green-200 rounded-lg p-4 text-center">
                <div class="text-sm text-gray-600 mb-1">Part Opérateur</div>
                <div class="text-2xl font-bold text-green-600">
                    {{ number_format($component->getOperatorShare(), 2) }} €
                </div>
            </div>
        </div>
        
        <div class="total-amount-display bg-gray-50 border border-gray-200 rounded-lg p-3 text-center">
            <div class="text-sm text-gray-600 mb-1">Montant Total</div>
            <div class="text-xl font-bold text-gray-900">
                {{ number_format($component->getTotalAmount(), 2) }} €
            </div>
        </div>
    @else
        <div class="amount-display">
            <div class="text-sm text-gray-600 mb-1">Montant</div>
            <div class="text-xl font-bold text-gray-900">
                {{ number_format($component->getTotalAmount(), 2) }} €
            </div>
        </div>
    @endif
</div>

