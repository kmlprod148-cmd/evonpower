@extends('layouts.app')

@section('page-title', 'Toutes les Transactions des Clients')

@section('content')
<div class="bg-gradient-to-b from-gray-50 to-white dark:from-gray-900 dark:to-gray-950 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Toutes les Transactions des Clients</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Vue complète de toutes les transactions : recharges de charge, recharges de crédit et transactions wallet</p>
            </div>
        </div>

        <!-- Messages -->
        @if(session('success'))
            <div class="mb-6 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 text-green-700 dark:text-green-300 p-4 rounded-lg shadow-sm" role="alert">
                <p>{{ session('success') }}</p>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 text-red-700 dark:text-red-300 p-4 rounded-lg shadow-sm" role="alert">
                <p>{{ session('error') }}</p>
            </div>
        @endif

        <!-- Statistiques rapides -->
        @if(isset($statistics))
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm p-4">
                <div class="text-sm text-gray-600 dark:text-gray-400">Total Transactions</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{{ number_format($statistics['total_transactions']) }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ $statistics['transactions_count'] }} charges, {{ $statistics['recharges_count'] }} recharges, {{ $statistics['wallet_transactions_count'] }} wallet
                </div>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm p-4">
                <div class="text-sm text-gray-600 dark:text-gray-400">Montant Total</div>
                <div class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ number_format($statistics['total_amount'], 2) }} €</div>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm p-4">
                <div class="text-sm text-gray-600 dark:text-gray-400">Complétées</div>
                <div class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ number_format($statistics['completed_count']) }}</div>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm p-4">
                <div class="text-sm text-gray-600 dark:text-gray-400">En attente</div>
                <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">{{ number_format($statistics['pending_count']) }}</div>
            </div>
        </div>
        @endif

        <!-- Filtres -->
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm p-4 mb-6">
            <form method="GET" action="{{ route('admin.client-transactions.index') }}" class="flex flex-wrap items-end gap-4">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Client</label>
                    <select name="user_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg dark:bg-gray-800 dark:text-gray-100">
                        <option value="">Tous les clients</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ request('user_id') == $client->id ? 'selected' : '' }}>
                                {{ $client->name }} ({{ $client->email }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Type</label>
                    <select name="transaction_type" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg dark:bg-gray-800 dark:text-gray-100">
                        <option value="">Tous les types</option>
                        <option value="transaction" {{ request('transaction_type') === 'transaction' ? 'selected' : '' }}>Recharges de charge</option>
                        <option value="credit_recharge" {{ request('transaction_type') === 'credit_recharge' ? 'selected' : '' }}>Recharges de crédit</option>
                        <option value="wallet" {{ request('transaction_type') === 'wallet' ? 'selected' : '' }}>Transactions wallet</option>
                    </select>
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Statut</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg dark:bg-gray-800 dark:text-gray-100">
                        <option value="">Tous</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Complétées</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En attente</option>
                        <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>En traitement</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Échouées</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Annulées</option>
                    </select>
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Méthode de paiement</label>
                    <select name="payment_method" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg dark:bg-gray-800 dark:text-gray-100">
                        <option value="">Toutes</option>
                        <option value="offline" {{ request('payment_method') === 'offline' ? 'selected' : '' }}>Hors ligne</option>
                        <option value="cmi" {{ request('payment_method') === 'cmi' ? 'selected' : '' }}>CMI</option>
                        <option value="stripe" {{ request('payment_method') === 'stripe' ? 'selected' : '' }}>Stripe</option>
                        <option value="wallet" {{ request('payment_method') === 'wallet' ? 'selected' : '' }}>Portefeuille</option>
                    </select>
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date début</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg dark:bg-gray-800 dark:text-gray-100">
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date fin</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg dark:bg-gray-800 dark:text-gray-100">
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Recherche</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Référence, email, nom..." class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg dark:bg-gray-800 dark:text-gray-100">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                        <i class="fas fa-filter mr-2"></i> Filtrer
                    </button>
                    <a href="{{ route('admin.client-transactions.index') }}" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors">
                        <i class="fas fa-redo mr-2"></i> Réinitialiser
                    </a>
                </div>
            </form>
        </div>

        <!-- Liste des transactions -->
        @if($transactions->count() > 0)
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Référence</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Client</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Montant</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Méthode de paiement</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Statut</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($transactions as $tx)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full 
                                        @if($tx['type'] === 'transaction') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                        @elseif($tx['type'] === 'credit_recharge') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200
                                        @else bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200
                                        @endif">
                                        {{ $tx['transaction_type'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="{{ route('admin.client-transactions.show', ['type' => $tx['type'], 'id' => $tx['id']]) }}" 
                                       class="text-sm font-medium text-green-600 dark:text-green-400 hover:text-green-700 dark:hover:text-green-300">
                                        {{ $tx['reference'] }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($tx['user'])
                                        <div class="text-sm text-gray-900 dark:text-gray-100">{{ $tx['user']->name }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $tx['user']->email }}</div>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold 
                                        @if(isset($tx['metadata']['type']) && $tx['metadata']['type'] === 'debit') text-red-600 dark:text-red-400
                                        @else text-green-600 dark:text-green-400
                                        @endif">
                                        @if(isset($tx['metadata']['type']) && $tx['metadata']['type'] === 'debit')
                                            -{{ number_format($tx['amount'], 2) }} {{ $tx['currency'] }}
                                        @else
                                            +{{ number_format($tx['amount'], 2) }} {{ $tx['currency'] }}
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-gray-100">{{ $tx['payment_method_name'] }}</div>
                                    @if($tx['payment_method'] && $tx['payment_method'] !== 'wallet')
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            @if(isset($tx['metadata']['external_id']))
                                                ID: {{ $tx['metadata']['external_id'] }}
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full 
                                        @if($tx['status'] === 'completed') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                        @elseif($tx['status'] === 'pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                        @elseif($tx['status'] === 'processing') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                        @elseif($tx['status'] === 'failed') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                        @elseif($tx['status'] === 'cancelled') bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200
                                        @else bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200
                                        @endif">
                                        {{ ucfirst($tx['status']) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <div>{{ $tx['created_at']->format('d/m/Y') }}</div>
                                    <div class="text-xs">{{ $tx['created_at']->format('H:i') }}</div>
                                    @if($tx['processed_at'])
                                        <div class="text-xs text-green-600 dark:text-green-400 mt-1">
                                            Traité: {{ $tx['processed_at']->format('d/m/Y H:i') }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="{{ route('admin.client-transactions.show', ['type' => $tx['type'], 'id' => $tx['id']]) }}" 
                                       class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300">
                                        <i class="fas fa-eye mr-1"></i> Détails
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    {{ $transactions->links() }}
                </div>
            </div>
        @else
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm p-8 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Aucune transaction</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Aucune transaction ne correspond aux critères de recherche.</p>
            </div>
        @endif
    </div>
</div>
@endsection

