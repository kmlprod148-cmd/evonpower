@extends('layouts.app')

@section('page-title', 'Détails de la Transaction')

@section('content')
<div class="bg-gradient-to-b from-gray-50 to-white dark:from-gray-900 dark:to-gray-950 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Détails de la Transaction</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Informations complètes sur cette transaction</p>
            </div>
            <div>
                <a href="{{ route('admin.client-transactions.index') }}" 
                   class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i> Retour
                </a>
            </div>
        </div>

        @if($transaction)
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Informations principales -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Carte principale -->
                    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Informations Générales</h2>
                        
                        <dl class="grid grid-cols-1 gap-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Type de transaction</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full 
                                        @if($type === 'transaction') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                        @elseif($type === 'credit_recharge') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200
                                        @else bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200
                                        @endif">
                                        @if($type === 'transaction') Recharge de charge
                                        @elseif($type === 'credit_recharge') Recharge de crédit
                                        @else Transaction wallet
                                        @endif
                                    </span>
                                </dd>
                            </div>

                            @if($type === 'transaction')
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ID Transaction</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $transaction->transaction_id ?? "TXN-{$transaction->id}" }}</dd>
                                </div>
                                @if($transaction->chargingPoint)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Point de recharge</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $transaction->chargingPoint->name }}</dd>
                                </div>
                                @endif
                                @if($transaction->reservation)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Réservation</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                        <a href="{{ route('admin.reservations.show', $transaction->reservation) }}" class="text-green-600 hover:text-green-700 dark:text-green-400">
                                            Réservation #{{ $transaction->reservation->id }}
                                        </a>
                                    </dd>
                                </div>
                                @endif
                            @elseif($type === 'credit_recharge')
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Référence</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 font-mono">{{ $transaction->reference }}</dd>
                                </div>
                                @if($transaction->creditPack)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Pack de crédit</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $transaction->creditPack->name }}</dd>
                                </div>
                                @endif
                                @if($transaction->external_id)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ID Externe</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 font-mono">{{ $transaction->external_id }}</dd>
                                </div>
                                @endif
                            @else
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Référence</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 font-mono">{{ $transaction->reference ?? "WT-{$transaction->id}" }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Type</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full 
                                            @if($transaction->type === 'credit') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                            @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                            @endif">
                                            {{ $transaction->type === 'credit' ? 'Crédit' : 'Débit' }}
                                        </span>
                                    </dd>
                                </div>
                            @endif

                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Client</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                    @if($transaction->user)
                                        <div>{{ $transaction->user->name }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $transaction->user->email }}</div>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Montant</dt>
                                <dd class="mt-1 text-lg font-semibold text-green-600 dark:text-green-400">
                                    {{ number_format($transaction->amount ?? 0, 2) }} {{ $transaction->currency ?? 'EUR' }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Méthode de paiement</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                    @if($type === 'credit_recharge')
                                        {{ $transaction->payment_method_name ?? ($transaction->payment_method ? ucfirst($transaction->payment_method) : 'Non spécifié') }}
                                    @elseif($type === 'transaction')
                                        @php
                                            $paymentMethod = 'wallet';
                                            if($transaction->reservation && $transaction->reservation->payment_method) {
                                                $paymentMethod = $transaction->reservation->payment_method;
                                            }
                                            $paymentMethodNames = [
                                                'offline' => 'Paiement hors ligne',
                                                'cmi' => 'CMI (Maroc)',
                                                'stripe' => 'Stripe (International)',
                                                'wallet' => 'Portefeuille',
                                            ];
                                        @endphp
                                        {{ $paymentMethodNames[$paymentMethod] ?? ucfirst($paymentMethod) }}
                                    @else
                                        Portefeuille
                                    @endif
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Statut</dt>
                                <dd class="mt-1">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full 
                                        @if($transaction->status === 'completed') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                        @elseif($transaction->status === 'pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                        @elseif($transaction->status === 'processing') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                        @elseif($transaction->status === 'failed') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                        @else bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200
                                        @endif">
                                        {{ ucfirst($transaction->status) }}
                                    </span>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Date de création</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $transaction->created_at->format('d/m/Y H:i:s') }}</dd>
                            </div>

                            @if($transaction->processed_at)
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Date de traitement</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $transaction->processed_at->format('d/m/Y H:i:s') }}</dd>
                            </div>
                            @endif

                            @if($type === 'credit_recharge' && $transaction->processor)
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Traité par</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $transaction->processor->name }}</dd>
                            </div>
                            @endif

                            @if($transaction->failure_reason)
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Raison de l'échec</dt>
                                <dd class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $transaction->failure_reason }}</dd>
                            </div>
                            @endif
                        </dl>
                    </div>

                    <!-- Métadonnées -->
                    @if($transaction->metadata || $transaction->payment_data)
                    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Métadonnées</h2>
                        <pre class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg text-xs overflow-x-auto">{{ json_encode($transaction->metadata ?? $transaction->payment_data ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                    @endif
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Actions rapides -->
                    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Actions</h2>
                        <div class="space-y-2">
                            @if($type === 'credit_recharge' && $transaction->status === 'pending' && $transaction->payment_method === 'offline')
                                <form method="POST" action="{{ route('admin.credit-recharges.confirm', $transaction) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                                        <i class="fas fa-check mr-2"></i> Confirmer
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.credit-recharges.reject', $transaction) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                                        <i class="fas fa-times mr-2"></i> Rejeter
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <!-- Informations supplémentaires -->
                    @if($type === 'transaction' && $transaction->transactionDetail)
                    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Répartition</h2>
                        <dl class="space-y-2">
                            @if($transaction->transactionDetail->admin_share_amount > 0)
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Part Admin</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ number_format($transaction->transactionDetail->admin_share_amount, 2) }} €</dd>
                            </div>
                            @endif
                            @if($transaction->transactionDetail->integrator_share_amount > 0)
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Part Intégrateur</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ number_format($transaction->transactionDetail->integrator_share_amount, 2) }} €</dd>
                            </div>
                            @endif
                            @if($transaction->transactionDetail->operator_share_amount > 0)
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Part Opérateur</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ number_format($transaction->transactionDetail->operator_share_amount, 2) }} €</dd>
                            </div>
                            @endif
                        </dl>
                    </div>
                    @endif

                    @if($type === 'wallet_transaction')
                    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Soldes</h2>
                        <dl class="space-y-2">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Avant</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ number_format($transaction->balance_before ?? 0, 2) }} €</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Après</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ number_format($transaction->balance_after ?? 0, 2) }} €</dd>
                            </div>
                        </dl>
                    </div>
                    @endif
                </div>
            </div>
        @else
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm p-8 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Transaction non trouvée</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Cette transaction n'existe pas ou a été supprimée.</p>
            </div>
        @endif
    </div>
</div>
@endsection

