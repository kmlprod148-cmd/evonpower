@extends('layouts.app')

@section('title', __('Vue Unifiée des Transactions'))

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 transition-colors duration-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Header --}}
        <div class="mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center space-x-4">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center shadow-lg" style="background: linear-gradient(to right, #3b82f6, #2563eb);">
                            <i class="fas fa-exchange-alt text-lg" style="color: white;"></i>
                        </div>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                            {{ __('Vue Unifiée des Transactions') }}
                        </h1>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 flex items-center">
                            <i class="fas fa-chart-line mr-2"></i>
                            {{ __('Gérez et analysez toutes vos transactions') }}
                        </p>
                    </div>
                </div>
                <div class="mt-4 sm:mt-0 flex space-x-3">
                    <button id="refreshStatsBtn" 
                            class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-lg shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200">
                        <i class="fas fa-sync-alt mr-2"></i>{{ __('Actualiser') }}
                    </button>
                    <a href="{{ route('transactions.export', request()->query()) }}"
                       class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200">
                        <i class="fas fa-download mr-2"></i>{{ __('Exporter CSV') }}
                    </a>
                </div>
            </div>
        </div>

        {{-- Real-time Statistics Cards --}}
        <div id="statisticsCards" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
            {{-- Cards will be loaded dynamically --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-xl flex items-center justify-center">
                            <i class="fas fa-spinner fa-spin text-blue-600 dark:text-blue-400 text-lg"></i>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                            {{ __('Chargement...') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Advanced Filters Panel --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mb-8">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i class="fas fa-filter text-gray-500 dark:text-gray-400 mr-2"></i>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ __('Filtres Avancés') }}
                        </h3>
                    </div>
                    <button id="toggleFiltersBtn" 
                            class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
            </div>
            <div id="filtersPanel" class="px-6 py-4">
                <form id="filtersForm" method="GET" action="{{ route('transactions.index') }}">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        {{-- Search --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('Recherche') }}
                            </label>
                            <input type="text" 
                                   name="search" 
                                   id="search"
                                   value="{{ request('search') }}"
                                   placeholder="{{ __('ID, description, utilisateur...') }}"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        {{-- Date From --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('Date de début') }}
                            </label>
                            <input type="date" 
                                   name="date_from" 
                                   id="date_from"
                                   value="{{ request('date_from') }}"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        {{-- Date To --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('Date de fin') }}
                            </label>
                            <input type="date" 
                                   name="date_to" 
                                   id="date_to"
                                   value="{{ request('date_to') }}"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        {{-- Status Filter --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('Statut') }}
                            </label>
                            <select name="status" 
                                    id="status"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">{{ __('Tous les statuts') }}</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('En attente') }}</option>
                                <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>{{ __('En cours') }}</option>
                                <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>{{ __('Confirmé') }}</option>
                                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>{{ __('Terminé') }}</option>
                                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>{{ __('Échoué') }}</option>
                                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>{{ __('Annulé') }}</option>
                            </select>
                        </div>

                        {{-- Payment Method Filter --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('Méthode de paiement') }}
                            </label>
                            <select name="payment_method" 
                                    id="payment_method"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">{{ __('Toutes les méthodes') }}</option>
                                <option value="credit_card" {{ request('payment_method') === 'credit_card' ? 'selected' : '' }}>{{ __('Carte de crédit') }}</option>
                                <option value="debit_card" {{ request('payment_method') === 'debit_card' ? 'selected' : '' }}>{{ __('Carte de débit') }}</option>
                                <option value="wallet" {{ request('payment_method') === 'wallet' ? 'selected' : '' }}>{{ __('Portefeuille') }}</option>
                                <option value="cmi" {{ request('payment_method') === 'cmi' ? 'selected' : '' }}>CMI</option>
                                <option value="stripe" {{ request('payment_method') === 'stripe' ? 'selected' : '' }}>Stripe</option>
                                <option value="bank_transfer" {{ request('payment_method') === 'bank_transfer' ? 'selected' : '' }}>{{ __('Virement bancaire') }}</option>
                                <option value="cash" {{ request('payment_method') === 'cash' ? 'selected' : '' }}>{{ __('Espèces') }}</option>
                            </select>
                        </div>

                        {{-- Type Filter --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('Type') }}
                            </label>
                            <select name="type" 
                                    id="type"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">{{ __('Tous les types') }}</option>
                                <option value="credit" {{ request('type') === 'credit' ? 'selected' : '' }}>{{ __('Crédit') }}</option>
                                <option value="debit" {{ request('type') === 'debit' ? 'selected' : '' }}>{{ __('Débit') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end space-x-3">
                        <button type="button" 
                                id="clearFiltersBtn"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                            {{ __('Réinitialiser') }}
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                            {{ __('Appliquer les filtres') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Unified Transactions Table --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ __('Toutes les Transactions') }}
                    </h3>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-500 dark:text-gray-400" id="transactionsCount">
                            {{ ($walletTransactionsPaginated->total() ?? 0) + ($reservationTransactionsPaginated->total() ?? 0) }} {{ __('transactions') }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                {{ __('ID') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                {{ __('Date') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                {{ __('Utilisateur') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                {{ __('Montant') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                {{ __('Statut') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                {{ __('Méthode de paiement') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                {{ __('Description') }}
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                {{ __('Actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        {{-- Wallet Transactions --}}
                        @forelse($walletTransactionsPaginated as $transaction)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">#{{ $transaction['id'] }}</span>
                                        <span class="ml-2 px-2 py-1 text-xs font-medium bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 rounded">
                                            Wallet
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $transaction['created_at'] instanceof \Carbon\Carbon ? $transaction['created_at']->format('d/m/Y H:i') : \Carbon\Carbon::parse($transaction['created_at'])->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ auth()->user()->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium {{ $transaction['amount'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ $transaction['amount'] >= 0 ? '+' : '' }}{{ number_format(abs($transaction['amount']), 2) }} {{ $transaction['currency'] ?? 'EUR' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                        {{ __('Terminé') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <span class="flex items-center">
                                        <i class="fas fa-wallet mr-2"></i>
                                        {{ __('Portefeuille') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $transaction['description'] ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('transactions.show', $transaction['wallet_transaction']->id) }}" 
                                       class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300">
                                        {{ __('Voir') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                        @endforelse

                        {{-- Reservation Transactions --}}
                        @forelse($reservationTransactionsPaginated as $transaction)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">#{{ $transaction['id'] }}</span>
                                        <span class="ml-2 px-2 py-1 text-xs font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded">
                                            Réservation
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $transaction['created_at'] instanceof \Carbon\Carbon ? $transaction['created_at']->format('d/m/Y H:i') : \Carbon\Carbon::parse($transaction['created_at'])->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $transaction['transaction']->user->name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ number_format($transaction['amount'], 2) }} {{ $transaction['currency'] ?? 'EUR' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $status = $transaction['status'] ?? 'unknown';
                                        $statusColors = [
                                            'completed' => 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200',
                                            'confirmed' => 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200',
                                            'pending' => 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200',
                                            'in_progress' => 'bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200',
                                            'failed' => 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200',
                                            'cancelled' => 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200',
                                        ];
                                        $statusLabels = [
                                            'completed' => __('Terminé'),
                                            'confirmed' => __('Confirmé'),
                                            'pending' => __('En attente'),
                                            'in_progress' => __('En cours'),
                                            'failed' => __('Échoué'),
                                            'cancelled' => __('Annulé'),
                                        ];
                                        $color = $statusColors[$status] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200';
                                        $label = $statusLabels[$status] ?? ucfirst($status);
                                    @endphp
                                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $color }}">
                                        {{ $label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    @php
                                        $paymentMethod = $transaction['transaction']->payment_method ?? 'wallet';
                                        $methodIcons = [
                                            'credit_card' => 'fa-credit-card',
                                            'debit_card' => 'fa-credit-card',
                                            'wallet' => 'fa-wallet',
                                            'cmi' => 'fa-university',
                                            'stripe' => 'fab fa-stripe',
                                            'bank_transfer' => 'fa-university',
                                            'cash' => 'fa-money-bill',
                                        ];
                                        $methodLabels = [
                                            'credit_card' => __('Carte de crédit'),
                                            'debit_card' => __('Carte de débit'),
                                            'wallet' => __('Portefeuille'),
                                            'cmi' => 'CMI',
                                            'stripe' => 'Stripe',
                                            'bank_transfer' => __('Virement bancaire'),
                                            'cash' => __('Espèces'),
                                        ];
                                        $icon = $methodIcons[$paymentMethod] ?? 'fa-wallet';
                                        $label = $methodLabels[$paymentMethod] ?? ucfirst($paymentMethod);
                                    @endphp
                                    <span class="flex items-center">
                                        <i class="fas {{ $icon }} mr-2"></i>
                                        {{ $label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $transaction['description'] ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('transactions.show', $transaction['transaction']->id) }}" 
                                       class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300">
                                        {{ __('Voir') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                        @endforelse

                        @if($walletTransactionsPaginated->isEmpty() && $reservationTransactionsPaginated->isEmpty())
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center">
                                    <div class="text-gray-400 dark:text-gray-500">
                                        <i class="fas fa-inbox text-4xl mb-4"></i>
                                        <p class="text-lg font-medium">{{ __('Aucune transaction trouvée') }}</p>
                                        <p class="text-sm mt-2">{{ __('Vos transactions apparaîtront ici') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($walletTransactionsPaginated->hasPages() || $reservationTransactionsPaginated->hasPages())
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div class="text-sm text-gray-700 dark:text-gray-300">
                            {{ __('Affichage de') }} 
                            <span class="font-semibold">{{ ($walletTransactionsPaginated->firstItem() ?? 0) + ($reservationTransactionsPaginated->firstItem() ?? 0) }}</span>
                            {{ __('à') }} 
                            <span class="font-semibold">{{ ($walletTransactionsPaginated->lastItem() ?? 0) + ($reservationTransactionsPaginated->lastItem() ?? 0) }}</span>
                            {{ __('sur') }} 
                            <span class="font-semibold">{{ ($walletTransactionsPaginated->total() ?? 0) + ($reservationTransactionsPaginated->total() ?? 0) }}</span>
                            {{ __('résultats') }}
                        </div>
                        <div class="flex items-center justify-center lg:justify-end space-x-2">
                            {{ $walletTransactionsPaginated->links() }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Real-time statistics update
    function updateStatistics() {
        const filters = {
            type: document.getElementById('type')?.value || '',
            date_from: document.getElementById('date_from')?.value || '',
            date_to: document.getElementById('date_to')?.value || '',
            status: document.getElementById('status')?.value || '',
            payment_method: document.getElementById('payment_method')?.value || '',
        };

        fetch('{{ route("transactions.statistics") }}?' + new URLSearchParams(filters), {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderStatisticsCards(data.data);
            }
        })
        .catch(error => {
            console.error('Error updating statistics:', error);
        });
    }

    function renderStatisticsCards(stats) {
        const cardsContainer = document.getElementById('statisticsCards');
        if (!cardsContainer) return;

        const cards = [
            {
                icon: 'fa-list',
                iconBg: 'bg-blue-100 dark:bg-blue-900',
                iconColor: 'text-blue-600 dark:text-blue-400',
                label: '{{ __("Total Transactions") }}',
                value: stats.total_transactions || 0,
                subtitle: '{{ __("Toutes les transactions") }}',
                color: 'text-gray-900 dark:text-white'
            },
            {
                icon: 'fa-arrow-up',
                iconBg: 'bg-green-100 dark:bg-green-900',
                iconColor: 'text-green-600 dark:text-green-400',
                label: '{{ __("Total Crédits") }}',
                value: (stats.total_credits || 0).toFixed(2) + ' €',
                subtitle: '{{ __("Argent entrant") }}',
                color: 'text-green-600 dark:text-green-400'
            },
            {
                icon: 'fa-arrow-down',
                iconBg: 'bg-red-100 dark:bg-red-900',
                iconColor: 'text-red-600 dark:text-red-400',
                label: '{{ __("Total Débits") }}',
                value: (stats.total_debits || 0).toFixed(2) + ' €',
                subtitle: '{{ __("Argent sortant") }}',
                color: 'text-red-600 dark:text-red-400'
            },
            {
                icon: 'fa-balance-scale',
                iconBg: stats.net_amount >= 0 ? 'bg-green-100 dark:bg-green-900' : 'bg-yellow-100 dark:bg-yellow-900',
                iconColor: stats.net_amount >= 0 ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400',
                label: '{{ __("Solde Net") }}',
                value: (stats.net_amount || 0).toFixed(2) + ' €',
                subtitle: '{{ __("Balance actuelle") }}',
                color: stats.net_amount >= 0 ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400'
            }
        ];

        cardsContainer.innerHTML = cards.map(card => `
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition-all duration-300 border border-gray-200 dark:border-gray-700 p-6 group hover:-translate-y-1">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 ${card.iconBg} rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <i class="fas ${card.icon} ${card.iconColor} text-lg"></i>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                            ${card.label}
                        </p>
                        <p class="text-2xl font-bold ${card.color} mt-1">
                            ${card.value}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 flex items-center">
                            <i class="fas fa-info-circle mr-1"></i>
                            ${card.subtitle}
                        </p>
                    </div>
                </div>
            </div>
        `).join('');
    }

    // Toggle filters panel
    const toggleFiltersBtn = document.getElementById('toggleFiltersBtn');
    const filtersPanel = document.getElementById('filtersPanel');
    if (toggleFiltersBtn && filtersPanel) {
        let filtersVisible = true;
        toggleFiltersBtn.addEventListener('click', function() {
            filtersVisible = !filtersVisible;
            filtersPanel.style.display = filtersVisible ? 'block' : 'none';
            toggleFiltersBtn.querySelector('i').classList.toggle('fa-chevron-down');
            toggleFiltersBtn.querySelector('i').classList.toggle('fa-chevron-up');
        });
    }

    // Clear filters
    const clearFiltersBtn = document.getElementById('clearFiltersBtn');
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', function() {
            document.getElementById('search').value = '';
            document.getElementById('date_from').value = '';
            document.getElementById('date_to').value = '';
            document.getElementById('status').value = '';
            document.getElementById('payment_method').value = '';
            document.getElementById('type').value = '';
            document.getElementById('filtersForm').submit();
        });
    }

    // Refresh statistics
    const refreshStatsBtn = document.getElementById('refreshStatsBtn');
    if (refreshStatsBtn) {
        refreshStatsBtn.addEventListener('click', function() {
            const icon = refreshStatsBtn.querySelector('i');
            icon.classList.add('fa-spin');
            updateStatistics();
            setTimeout(() => {
                icon.classList.remove('fa-spin');
            }, 1000);
        });
    }

    // Auto-refresh statistics every 30 seconds
    updateStatistics();
    setInterval(updateStatistics, 30000);

    // Update statistics on filter change
    ['type', 'date_from', 'date_to', 'status', 'payment_method'].forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener('change', updateStatistics);
        }
    });
});
</script>
@endpush

