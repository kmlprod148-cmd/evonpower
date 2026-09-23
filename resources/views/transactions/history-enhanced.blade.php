@extends('layouts.app')

@section('title', 'Historique des Transactions')

@section('content')
<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-6">
    <!-- Header avec informations de rôle -->
    <div class="mb-8">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Historique des Transactions</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">
                    @if(auth()->user()->hasRole('admin'))
                        Vue complète - Toutes les transactions du système
                    @elseif(auth()->user()->hasRole('integrator'))
                        Vos transactions - Débits et crédits de votre compte
                    @elseif(auth()->user()->hasRole('operator'))
                        Vos transactions - Débits et crédits de votre compte
                    @else
                        Historique de vos transactions
                    @endif
                </p>
            </div>
            <div class="flex space-x-4">
                <a href="{{ route('transactions.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <i class="fas fa-arrow-left mr-2"></i>Retour aux Transactions
                </a>
            </div>
        </div>
    </div>

    <!-- Statistiques rapides -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Total Crédits -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                        <i class="fas fa-arrow-up text-green-600 dark:text-green-400"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Crédits</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($totalCredits, 2) }} €</p>
                </div>
            </div>
        </div>

        <!-- Total Débits -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center">
                        <i class="fas fa-arrow-down text-red-600 dark:text-red-400"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Débits</p>
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ number_format($totalDebits, 2) }} €</p>
                </div>
            </div>
        </div>

        <!-- Solde Net -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 {{ $netBalance >= 0 ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30' }} rounded-full flex items-center justify-center">
                        <i class="fas fa-balance-scale {{ $netBalance >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Solde Net</p>
                    <p class="text-2xl font-bold {{ $netBalance >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ number_format($netBalance, 2) }} €
                    </p>
                </div>
            </div>
        </div>

        <!-- Nombre de Transactions -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                        <i class="fas fa-list text-blue-600 dark:text-blue-400"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Transactions</p>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $transactions->count() }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <form method="GET" action="{{ route('transactions.history') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Type de Transaction -->
            <div>
                <label for="transaction_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type de Transaction</label>
                <select id="transaction_type" name="transaction_type" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Tous les types</option>
                    <option value="admin_integrator" {{ request('transaction_type') === 'admin_integrator' ? 'selected' : '' }}>Admin ↔ Intégrateur</option>
                    <option value="integrator_operator" {{ request('transaction_type') === 'integrator_operator' ? 'selected' : '' }}>Intégrateur ↔ Opérateur</option>
                    <option value="client" {{ request('transaction_type') === 'client' ? 'selected' : '' }}>Transaction Client</option>
                </select>
            </div>

            <!-- Direction -->
            <div>
                <label for="direction" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Direction</label>
                <select id="direction" name="direction" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Toutes les directions</option>
                    <option value="credit" {{ request('direction') === 'credit' ? 'selected' : '' }}>Crédit (Vert)</option>
                    <option value="debit" {{ request('direction') === 'debit' ? 'selected' : '' }}>Débit (Rouge)</option>
                </select>
            </div>

            <!-- Période -->
            <div>
                <label for="date_from" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Du</label>
                <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}" 
                       class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div>
                <label for="date_to" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Au</label>
                <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}" 
                       class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div class="lg:col-span-4 flex justify-end space-x-4">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <i class="fas fa-search mr-2"></i>Filtrer
                </button>
                <a href="{{ route('transactions.history') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <i class="fas fa-times mr-2"></i>Effacer
                </a>
            </div>
        </form>
    </div>

    <!-- Liste des Transactions -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Historique des Transactions</h3>
        </div>
        
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($transactions as $transaction)
                <div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <!-- Icône avec couleur selon le type -->
                            <div class="flex-shrink-0">
                                @if($transaction->type === 'credit')
                                    <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                                        <i class="fas fa-arrow-up text-green-600 dark:text-green-400"></i>
                                    </div>
                                @else
                                    <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center">
                                        <i class="fas fa-arrow-down text-red-600 dark:text-red-400"></i>
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Informations de la transaction -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center space-x-2">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $transaction->description ?? 'Transaction #' . $transaction->id }}
                                    </p>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $transaction->type === 'credit' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
                                        {{ $transaction->type === 'credit' ? 'Crédit' : 'Débit' }}
                                    </span>
                                </div>
                                
                                <div class="mt-1 flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                                    <span><i class="fas fa-calendar mr-1"></i>{{ $transaction->created_at->format('d/m/Y H:i') }}</span>
                                    @if($transaction->chargingPoint)
                                        <span><i class="fas fa-charging-station mr-1"></i>{{ $transaction->chargingPoint->name }}</span>
                                    @endif
                                    @if($transaction->user)
                                        <span><i class="fas fa-user mr-1"></i>{{ $transaction->user->name }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <!-- Montant avec couleur -->
                        <div class="flex items-center space-x-4">
                            <div class="text-right">
                                <p class="text-lg font-bold {{ $transaction->type === 'credit' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $transaction->type === 'credit' ? '+' : '-' }}{{ number_format($transaction->amount, 2) }} €
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $transaction->currency }}</p>
                            </div>
                            
                            <!-- Actions -->
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('transactions.show', $transaction->id) }}" 
                                   class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                    <i class="fas fa-eye mr-1"></i>Voir
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Détails supplémentaires selon le rôle -->
                    @if(auth()->user()->hasRole('admin'))
                        <div class="mt-4 pl-14">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Commission Admin:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ number_format($transaction->admin_commission ?? 0, 2) }} €</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Commission Intégrateur:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ number_format($transaction->integrator_commission ?? 0, 2) }} €</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Commission Opérateur:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ number_format($transaction->partner_commission ?? 0, 2) }} €</span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <div class="p-12 text-center">
                    <div class="mx-auto w-24 h-24 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-receipt text-3xl text-gray-400 dark:text-gray-500"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Aucune transaction trouvée</h3>
                    <p class="text-gray-500 dark:text-gray-400">Aucune transaction ne correspond aux critères de recherche.</p>
                </div>
            @endforelse
        </div>
        
        <!-- Pagination -->
        @if($transactions->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>
</div>

<script>
// Auto-submit form on filter change
document.addEventListener('DOMContentLoaded', function() {
    const filterSelects = document.querySelectorAll('select[name="transaction_type"], select[name="direction"]');
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            this.form.submit();
        });
    });
});
</script>
@endsection
