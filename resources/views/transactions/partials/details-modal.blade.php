<div class="space-y-6">
    <!-- En-tête de la transaction -->
    <div class="flex items-center justify-between p-4 {{ $bgColorClass }} rounded-lg">
        <div class="flex items-center">
            <div class="w-12 h-12 {{ $bgColorClass }} rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-{{ $icon }} {{ $colorClass }} text-xl"></i>
            </div>
            <div>
                <h4 class="text-lg font-semibold text-gray-900">
                    Transaction #{{ $transaction->id }}
                </h4>
                <p class="text-sm text-gray-600">
                    {{ $transaction->getTransactionTypeLabel() }} - {{ $transaction->getTransactionCategoryLabel() }}
                </p>
            </div>
        </div>
        <div class="text-right">
            <div class="text-2xl font-bold {{ $colorClass }}">
                {{ $type === 'debit' ? '-' : '+' }}{{ number_format($transaction->price_total ?? $transaction->amount ?? 0, 2) }} €
            </div>
            <div class="text-sm text-gray-600">
                {{ $transaction->created_at->format('d/m/Y à H:i') }}
            </div>
        </div>
    </div>

    <!-- Informations principales -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Informations client -->
        <div class="bg-gray-50 p-4 rounded-lg">
            <h5 class="font-semibold text-gray-900 mb-3 flex items-center">
                <i class="fas fa-user mr-2 text-blue-600"></i>
                Informations Client
            </h5>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-gray-600">Nom:</span>
                    <span class="font-medium">{{ $transaction->user->name ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Email:</span>
                    <span class="font-medium">{{ $transaction->user->email ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Rôle:</span>
                    <span class="font-medium">
                        @if($transaction->user)
                            @php
                                $roleColors = [
                                    'admin' => 'bg-red-100 text-red-800',
                                    'integrator' => 'bg-blue-100 text-blue-800',
                                    'operator' => 'bg-green-100 text-green-800',
                                    'partner' => 'bg-purple-100 text-purple-800'
                                ];
                                $roleColor = $roleColors[$transaction->user->role ?? 'operator'] ?? 'bg-gray-100 text-gray-800';
                            @endphp
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $roleColor }}">
                                {{ ucfirst($transaction->user->role ?? 'operator') }}
                            </span>
                        @else
                            N/A
                        @endif
                    </span>
                </div>
            </div>
        </div>

        <!-- Informations point de recharge -->
        <div class="bg-gray-50 p-4 rounded-lg">
            <h5 class="font-semibold text-gray-900 mb-3 flex items-center">
                <i class="fas fa-charging-station mr-2 text-green-600"></i>
                Point de Recharge
            </h5>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-gray-600">Nom:</span>
                    <span class="font-medium">{{ $transaction->chargingPoint->name ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Station:</span>
                    <span class="font-medium">{{ $transaction->chargingPoint->station->name ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Localisation:</span>
                    <span class="font-medium">{{ $transaction->chargingPoint->station->address ?? 'N/A' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Détails financiers -->
    <div class="bg-gray-50 p-4 rounded-lg">
        <h5 class="font-semibold text-gray-900 mb-3 flex items-center">
            <i class="fas fa-money-bill-wave mr-2 text-yellow-600"></i>
            Détails Financiers
        </h5>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="text-center">
                <div class="text-sm text-gray-600">Montant Total</div>
                <div class="text-xl font-bold {{ $colorClass }}">
                    {{ number_format($transaction->price_total ?? $transaction->amount ?? 0, 2) }} €
                </div>
            </div>
            @if($transaction->price_energy)
            <div class="text-center">
                <div class="text-sm text-gray-600">Énergie</div>
                <div class="text-lg font-semibold text-gray-900">
                    {{ number_format($transaction->price_energy, 2) }} €
                </div>
            </div>
            @endif
            @if($transaction->price_time)
            <div class="text-center">
                <div class="text-sm text-gray-600">Temps</div>
                <div class="text-lg font-semibold text-gray-900">
                    {{ number_format($transaction->price_time, 2) }} €
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Répartition hiérarchique (Source de vérité : TransactionDetail) -->
    @php
        // PRIORITÉ 1 : TransactionDetail (montants calculés selon Business Profiles)
        $transactionDetail = $transaction->transactionDetail;
        $adminShare = $transactionDetail ? ($transactionDetail->admin_share_amount ?? 0) : ($transaction->getAdminFee());
        $integratorShare = $transactionDetail ? ($transactionDetail->integrator_share_amount ?? 0) : ($transaction->getIntegratorFee());
        $operatorShare = $transactionDetail ? ($transactionDetail->operator_share_amount ?? 0) : 0;
        $hasRepartition = $adminShare > 0 || $integratorShare > 0 || $operatorShare > 0;
    @endphp
    
    @if($hasRepartition)
    <div class="bg-gray-50 p-4 rounded-lg">
        <h5 class="font-semibold text-gray-900 mb-3 flex items-center">
            <i class="fas fa-share-alt mr-2 text-purple-600"></i>
            Répartition Hiérarchique
            @if($transactionDetail)
                <span class="ml-2 text-xs text-green-600">
                    <i class="fas fa-check-circle"></i> Selon Business Profiles
                </span>
            @endif
        </h5>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @if($adminShare > 0)
            <div class="text-center">
                <div class="text-sm text-gray-600">Part Admin</div>
                <div class="text-lg font-semibold text-red-600">
                    {{ number_format($adminShare, 2) }} €
                </div>
                @if($transactionDetail && $transactionDetail->admin_share_percentage)
                    <div class="text-xs text-gray-500">
                        {{ number_format($transactionDetail->admin_share_percentage, 2) }}%
                    </div>
                @endif
            </div>
            @endif
            @if($integratorShare > 0)
            <div class="text-center">
                <div class="text-sm text-gray-600">Part Intégrateur</div>
                <div class="text-lg font-semibold text-blue-600">
                    {{ number_format($integratorShare, 2) }} €
                </div>
                @if($transactionDetail && $transactionDetail->integrator_share_percentage)
                    <div class="text-xs text-gray-500">
                        {{ number_format($transactionDetail->integrator_share_percentage, 2) }}%
                    </div>
                @endif
            </div>
            @endif
            @if($operatorShare > 0)
            <div class="text-center">
                <div class="text-sm text-gray-600">Part Opérateur</div>
                <div class="text-lg font-semibold text-green-600">
                    {{ number_format($operatorShare, 2) }} €
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Informations techniques -->
    <div class="bg-gray-50 p-4 rounded-lg">
        <h5 class="font-semibold text-gray-900 mb-3 flex items-center">
            <i class="fas fa-cogs mr-2 text-gray-600"></i>
            Informations Techniques
        </h5>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-gray-600">ID Session:</span>
                    <span class="font-medium">{{ $transaction->session_id ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Méthode Auth:</span>
                    <span class="font-medium">{{ $transaction->auth_method ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Méthode Paiement:</span>
                    <span class="font-medium">{{ $transaction->payment_method ?? 'N/A' }}</span>
                </div>
            </div>
            <div class="space-y-2">
                @if($transaction->energy_delivered)
                <div class="flex justify-between">
                    <span class="text-gray-600">Énergie Livrée:</span>
                    <span class="font-medium">{{ number_format($transaction->energy_delivered, 2) }} kWh</span>
                </div>
                @endif
                @if($transaction->duration)
                <div class="flex justify-between">
                    <span class="text-gray-600">Durée:</span>
                    <span class="font-medium">{{ gmdate('H:i:s', $transaction->duration) }}</span>
                </div>
                @endif
                @if($transaction->meter_start && $transaction->meter_stop)
                <div class="flex justify-between">
                    <span class="text-gray-600">Compteur:</span>
                    <span class="font-medium">{{ number_format($transaction->meter_start, 2) }} → {{ number_format($transaction->meter_stop, 2) }}</span>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Business Profile -->
    @if($transaction->businessProfile)
    <div class="bg-gray-50 p-4 rounded-lg">
        <h5 class="font-semibold text-gray-900 mb-3 flex items-center">
            <i class="fas fa-briefcase mr-2 text-indigo-600"></i>
            Business Profile
        </h5>
        <div class="space-y-2">
            <div class="flex justify-between">
                <span class="text-gray-600">Nom:</span>
                <span class="font-medium">{{ $transaction->businessProfile->name }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Description:</span>
                <span class="font-medium">{{ $transaction->businessProfile->description ?? 'N/A' }}</span>
            </div>
        </div>
    </div>
    @endif

    <!-- Transactions hiérarchiques -->
    @if($transaction->transactionHierarchies && $transaction->transactionHierarchies->count() > 0)
    <div class="bg-gray-50 p-4 rounded-lg">
        <h5 class="font-semibold text-gray-900 mb-3 flex items-center">
            <i class="fas fa-sitemap mr-2 text-orange-600"></i>
            Transactions Hiérarchiques
        </h5>
        <div class="space-y-3">
            @foreach($transaction->transactionHierarchies as $hierarchy)
            <div class="bg-white p-3 rounded border">
                <div class="flex justify-between items-center">
                    <div>
                        <div class="font-medium">{{ $hierarchy->payer->name ?? 'N/A' }} → {{ $hierarchy->payee->name ?? 'N/A' }}</div>
                        <div class="text-sm text-gray-600">{{ number_format($hierarchy->amount, 2) }} €</div>
                    </div>
                    <div class="text-sm text-gray-500">
                        {{ $hierarchy->created_at->format('d/m/Y H:i') }}
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Actions -->
    <div class="flex justify-end space-x-3 pt-4 border-t">
        <button onclick="closeTransactionModal()" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors">
            Fermer
        </button>
        @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('super-admin'))
        <button onclick="cancelTransaction({{ $transaction->id }})" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition-colors">
            <i class="fas fa-times mr-2"></i>
            Annuler Transaction
        </button>
        @endif
    </div>
</div>

<script>
function cancelTransaction(transactionId) {
    if (confirm('Êtes-vous sûr de vouloir annuler cette transaction ?')) {
        fetch(`/transactions/${transactionId}/cancel`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Transaction annulée avec succès', 'success');
                closeTransactionModal();
                // Recharger la page pour mettre à jour la liste
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showNotification(data.message || 'Erreur lors de l\'annulation', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Erreur lors de l\'annulation de la transaction', 'error');
        });
    }
}
</script>