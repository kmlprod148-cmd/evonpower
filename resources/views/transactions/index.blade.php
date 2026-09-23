@extends('layouts.app')

@section('title', __('messages.transactions'))

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white transactions-page-content" style="padding-bottom: 120px;">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Enhanced Page Header --}}
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
                            {{ __('messages.transactions') }}
            </h1>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 flex items-center">
                            <i class="fas fa-chart-line mr-2"></i>
                            {{ __('Manage and track all your transactions') }}
                        </p>
                    </div>
        </div>
                <div class="mt-4 sm:mt-0 flex space-x-3">
            <a href="{{ route('transactions.export', request()->query()) }}"
                       class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200"
               aria-label="{{ __('Export Transactions as CSV') }}">
                        <i class="fas fa-download mr-2"></i>{{ __('Export CSV') }}
            </a>
                </div>
        </div>
    </div>

        {{-- Enhanced Balance Card --}}
    @if($wallet)
        <div class="mb-8">
            <div class="rounded-2xl shadow-xl overflow-hidden relative" style="background: linear-gradient(to right, #2563eb, #1d4ed8);">
                <!-- Background Pattern -->
                <div class="absolute top-0 right-0 opacity-10">
                    <svg width="200" height="200" viewBox="0 0 200 200" fill="none">
                        <circle cx="100" cy="100" r="80" stroke="white" stroke-width="2"/>
                        <circle cx="100" cy="100" r="60" stroke="white" stroke-width="1"/>
                        <circle cx="100" cy="100" r="40" stroke="white" stroke-width="1"/>
                    </svg>
                </div>
                
                <div class="relative z-10 p-8">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                        <div class="lg:w-2/3">
                            <div class="flex items-center mb-6">
                                <div class="rounded-full p-3 mr-4" style="background: rgba(255,255,255,0.2);">
                                    <i class="fas fa-wallet text-xl" style="color: white;"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold uppercase tracking-wide" style="color: #dbeafe;">{{ __('Current Balance') }}</p>
                                    <h2 class="text-4xl lg:text-5xl font-bold mt-1" style="color: white;">
                                        {{ isset($stats['current_balance']) ? number_format($stats['current_balance'], 2) . ' €' : (method_exists($wallet, 'getFormattedBalance') ? $wallet->getFormattedBalance() : number_format($wallet->balance ?? 0, 2) . ' €') }}
                                    </h2>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-3">
                                <div class="rounded-full px-4 py-2" style="background: rgba(255,255,255,0.1);">
                                    <span class="text-sm flex items-center" style="color: #dbeafe;">
                                        <i class="fas fa-clock mr-2" style="color: #dbeafe;"></i>
                                        {{ __('Last updated') }}: {{ $wallet->updated_at ? $wallet->updated_at->format('M d, Y H:i') : now()->format('M d, Y H:i') }}
                                    </span>
                                </div>
                                <div class="rounded-full px-4 py-2" style="background: rgba(255,255,255,0.1);">
                                    <span class="text-sm flex items-center" style="color: #dbeafe;">
                                        <i class="fas fa-shield-alt mr-2" style="color: #dbeafe;"></i>
                                        {{ __('Secure Wallet') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="lg:w-1/3 mt-6 lg:mt-0 lg:flex lg:flex-col lg:items-end">
                            <div class="grid grid-cols-2 gap-4 lg:grid-cols-1 lg:gap-3">
                                <div class="rounded-xl p-4" style="background: rgba(255,255,255,0.1);">
                                    <div class="text-center">
                                        <div class="text-sm mb-1" style="color: #dbeafe;">{{ __('Total Credits') }}</div>
                                        <div class="text-xl font-bold" style="color: white;">{{ number_format($stats['total_credits'], 2) }} €</div>
                                    </div>
                                </div>
                                <div class="rounded-xl p-4" style="background: rgba(255,255,255,0.1);">
                                    <div class="text-center">
                                        <div class="text-sm mb-1" style="color: #dbeafe;">{{ __('Total Debits') }}</div>
                                        <div class="text-xl font-bold" style="color: white;">{{ number_format($stats['total_debits'], 2) }} €</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

        {{-- Enhanced Statistics Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition-all duration-300 border border-gray-200 dark:border-gray-700 p-6 group hover:-translate-y-1 animate-fade-in">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <i class="fas fa-list text-blue-600 dark:text-blue-400 text-lg"></i>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                            {{ __('Total Transactions') }}
                        </p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ $stats['total_transactions'] }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 flex items-center">
                            <i class="fas fa-chart-line mr-1"></i>
                            {{ __('All time') }}
                            @if(isset($stats['wallet_transactions_count']) && isset($stats['reservation_transactions_count']))
                                <span class="ml-2">({{ $stats['wallet_transactions_count'] }} Wallet, {{ $stats['reservation_transactions_count'] }} Réservations)</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition-all duration-300 border border-gray-200 dark:border-gray-700 p-6 group hover:-translate-y-1 animate-fade-in" style="animation-delay: 0.1s">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <i class="fas fa-arrow-up text-green-600 dark:text-green-400 text-lg"></i>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                            {{ __('Total Credits') }}
                        </p>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">
                            {{ number_format($stats['total_credits'], 2) }} €
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 flex items-center">
                            <i class="fas fa-plus-circle mr-1"></i>
                            {{ __('Money in') }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition-all duration-300 border border-gray-200 dark:border-gray-700 p-6 group hover:-translate-y-1 animate-fade-in" style="animation-delay: 0.2s">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-red-100 dark:bg-red-900 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <i class="fas fa-arrow-down text-red-600 dark:text-red-400 text-lg"></i>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                            {{ __('Total Debits') }}
                        </p>
                        <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">
                            {{ number_format($stats['total_debits'], 2) }} €
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 flex items-center">
                            <i class="fas fa-minus-circle mr-1"></i>
                            {{ __('Money out') }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition-all duration-300 border border-gray-200 dark:border-gray-700 p-6 group hover:-translate-y-1 animate-fade-in" style="animation-delay: 0.3s">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 {{ $stats['net_amount'] >= 0 ? 'bg-green-100 dark:bg-green-900' : 'bg-yellow-100 dark:bg-yellow-900' }} rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <i class="fas fa-balance-scale {{ $stats['net_amount'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400' }} text-lg"></i>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                            {{ __('Net Amount') }}
                        </p>
                        <p class="text-2xl font-bold {{ $stats['net_amount'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400' }} mt-1">
                            {{ number_format($stats['net_amount'], 2) }} €
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 flex items-center">
                            <i class="fas fa-calculator mr-1"></i>
                            {{ __('Balance') }}
                        </p>
                    </div>
                </div>
            </div>
    </div>

    {{-- Filters Section removed per request --}}

        {{-- Filter by Reservation ID --}}
        @if(isset($transactionsByReservation) && $transactionsByReservation->isNotEmpty())
        <div class="mb-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="bg-blue-100 dark:bg-blue-900 rounded-lg p-2 mr-3">
                        <i class="fas fa-filter text-blue-600 dark:text-blue-400"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ __('Filter by Reservation') }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ __('View transactions related to a specific reservation') }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <select id="reservationFilter" 
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            onchange="filterByReservation(this.value)">
                        <option value="">{{ __('All Reservations') }}</option>
                        @foreach($transactionsByReservation as $group)
                            <option value="{{ $group['reservation_id'] }}" {{ request('reservation_id') == $group['reservation_id'] ? 'selected' : '' }}>
                                {{ __('Reservation') }} #{{ $group['reservation_id'] }} 
                                ({{ $group['total_wallet_count'] }} Wallet, {{ $group['total_reservation_count'] }} Reservation)
                            </option>
                        @endforeach
                    </select>
                    @if(request('reservation_id'))
                        <a href="{{ route('transactions.index', array_merge(request()->query(), ['reservation_id' => null, 'page' => 1])) }}"
                           class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                            <i class="fas fa-times"></i> {{ __('Clear') }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Transactions Grouped by Reservation --}}
        @if(request('reservation_id') && isset($transactionsByReservation))
            @php
                $selectedGroup = $transactionsByReservation->firstWhere('reservation_id', request('reservation_id'));
            @endphp
            @if($selectedGroup)
            <div class="mb-6 bg-gradient-to-r from-indigo-50 to-blue-50 dark:from-indigo-900/20 dark:to-blue-900/20 rounded-xl shadow-lg border-2 border-indigo-200 dark:border-indigo-800 overflow-hidden">
                <div class="bg-indigo-600 dark:bg-indigo-800 px-6 py-4 text-white">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="bg-white bg-opacity-20 rounded-lg p-2 mr-3">
                                <i class="fas fa-link text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold">
                                    {{ __('Reservation') }} #{{ $selectedGroup['reservation_id'] }} - {{ __('Related Transactions') }}
                                </h3>
                                <p class="text-sm text-indigo-100 mt-1">
                                    {{ $selectedGroup['total_wallet_count'] }} {{ __('Wallet Transactions') }} + 
                                    {{ $selectedGroup['total_reservation_count'] }} {{ __('Reservation Transactions') }}
                                </p>
                            </div>
                        </div>
                        <a href="{{ route('reservations.show', $selectedGroup['reservation_id']) }}" 
                           class="px-4 py-2 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg text-sm font-medium transition-colors">
                            <i class="fas fa-external-link-alt mr-2"></i>{{ __('View Reservation') }}
                        </a>
                    </div>
                </div>
                
                <div class="p-6">
                    {{-- Wallet Transactions for this Reservation --}}
                    @if($selectedGroup['wallet_transactions']->isNotEmpty())
                    <div class="mb-6">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center">
                            <i class="fas fa-wallet text-purple-600 dark:text-purple-400 mr-2"></i>
                            @php
                                $wtCount = 0;
                                if (isset($selectedGroup['wallet_transactions'])) {
                                    $wt = $selectedGroup['wallet_transactions'];
                                    if (is_array($wt)) {
                                        $wtCount = count($wt);
                                    } elseif (is_object($wt) && method_exists($wt, 'count')) {
                                        $wtCount = $wt->count();
                                    } elseif (is_countable($wt)) {
                                        $wtCount = count($wt);
                                    }
                                }
                                $wtCount = (int) $wtCount;
                            @endphp
                            {{ __('Wallet Transactions') }} ({{ $wtCount }})
                        </h4>
                        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-purple-50 dark:bg-purple-900/20">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('ID') }}</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Date') }}</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Amount') }}</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Description') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($selectedGroup['wallet_transactions'] as $wt)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">#{{ $wt['id'] }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">
                                                {{ $wt['created_at'] instanceof \Carbon\Carbon ? $wt['created_at']->format('M d, Y H:i') : \Carbon\Carbon::parse($wt['created_at'])->format('M d, Y H:i') }}
                                            </td>
                                            <td class="px-4 py-2 text-sm font-medium {{ $wt['amount'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                                {{ number_format(abs($wt['amount']), 2) }} {{ $wt['currency'] }}
                                            </td>
                                            <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">{{ $wt['description'] }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Reservation Transactions for this Reservation --}}
                    @if($selectedGroup['reservation_transactions']->isNotEmpty())
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center">
                            <i class="fas fa-calendar-check text-green-600 dark:text-green-400 mr-2"></i>
                            @php
                                $rtCount = 0;
                                if (isset($selectedGroup['reservation_transactions'])) {
                                    $rt = $selectedGroup['reservation_transactions'];
                                    if (is_array($rt)) {
                                        $rtCount = count($rt);
                                    } elseif (is_object($rt) && method_exists($rt, 'count')) {
                                        $rtCount = $rt->count();
                                    } elseif (is_countable($rt)) {
                                        $rtCount = count($rt);
                                    }
                                }
                                $rtCount = (int) $rtCount;
                            @endphp
                            {{ __('Reservation Transactions') }} ({{ $rtCount }})
                        </h4>
                        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-green-50 dark:bg-green-900/20">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('ID') }}</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Date') }}</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Amount') }}</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Status') }}</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($selectedGroup['reservation_transactions'] as $rt)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">#{{ $rt['id'] }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">
                                                {{ $rt['created_at'] instanceof \Carbon\Carbon ? $rt['created_at']->format('M d, Y H:i') : \Carbon\Carbon::parse($rt['created_at'])->format('M d, Y H:i') }}
                                            </td>
                                            <td class="px-4 py-2 text-sm font-medium text-gray-900 dark:text-white">
                                                {{ number_format($rt['amount'], 2) }} {{ $rt['currency'] }}
                                            </td>
                                            <td class="px-4 py-2 text-sm">
                                                <span class="inline-flex items-center px-2 py-1 rounded text-xs
                                                    @if($rt['status'] === 'completed') bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200
                                                    @elseif($rt['status'] === 'pending') bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200
                                                    @else bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200
                                                    @endif">
                                                    {{ ucfirst($rt['status']) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2 text-sm">
                                                <a href="{{ route('transactions.show', $rt['transaction']->id) }}" 
                                                   class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        @endif

        {{-- Table 1: Wallet Transactions --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden animate-slide-in mb-6">
            <div class="bg-gradient-to-r from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 px-6 py-4 border-b border-gray-200 dark:border-gray-600">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center">
                        <div class="bg-purple-100 dark:bg-purple-900 rounded-lg p-2 mr-4">
                            <i class="fas fa-wallet text-purple-600 dark:text-purple-400 text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ __('Wallet Transactions') }}
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 flex items-center mt-1">
                                <i class="fas fa-info-circle mr-2"></i>
                                {{ __('Transactions from wallet operations') }} 
                                <span class="ml-2 text-xs bg-purple-200 dark:bg-purple-800 px-2 py-1 rounded">
                                    @php
                                        $walletCount = 0;
                                        try {
                                            if (isset($walletTransactionsPaginated) && $walletTransactionsPaginated instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator) {
                                                $total = $walletTransactionsPaginated->total();
                                                $walletCount = is_numeric($total) ? (int) $total : 0;
                                            } elseif (isset($walletTransactions) && !is_null($walletTransactions)) {
                                                if (is_array($walletTransactions)) {
                                                    $walletCount = (int) count($walletTransactions);
                                                } elseif (is_object($walletTransactions) && method_exists($walletTransactions, 'count')) {
                                                    $cnt = $walletTransactions->count();
                                                    $walletCount = is_numeric($cnt) ? (int) $cnt : 0;
                                                } elseif (is_countable($walletTransactions)) {
                                                    $walletCount = (int) count($walletTransactions);
                                                }
                                            }
                                        } catch (\Exception $e) {
                                            $walletCount = 0;
                                        }
                                        // Force to integer
                                        $walletCount = (int) $walletCount;
                                    @endphp
                                    @php
                                        $transactionsText = __('messages.transactions');
                                        if (is_array($transactionsText)) {
                                            $transactionsText = 'transactions';
                                        }
                                    @endphp
                                    {{ $walletCount }} {{ $transactionsText }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="overflow-x-auto custom-scrollbar">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                <div class="flex items-center">
                                    <i class="fas fa-hashtag mr-2"></i>
                                    {{ __('ID') }}
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                <div class="flex items-center">
                                    <i class="fas fa-calendar-check mr-2"></i>
                                    {{ __('Reservation ID') }}
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                <div class="flex items-center">
                                    <i class="fas fa-calendar mr-2"></i>
                                    {{ __('Date') }}
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                <div class="flex items-center">
                                    <i class="fas fa-user mr-2"></i>
                                    {{ __('User') }}
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                <div class="flex items-center">
                                    <i class="fas fa-tag mr-2"></i>
                                    {{ __('Type') }}
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                <div class="flex items-center">
                                    <i class="fas fa-bolt mr-2"></i>
                                    {{ __('Charging Point') }}
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                <div class="flex items-center">
                                    <i class="fas fa-euro-sign mr-2"></i>
                                    {{ __('Amount') }}
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                <div class="flex items-center">
                                    <i class="fas fa-check-circle mr-2"></i>
                                    {{ __('Status') }}
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                <div class="flex items-center">
                                    <i class="fas fa-comment mr-2"></i>
                                    {{ __('Description') }}
                                </div>
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                <div class="flex items-center justify-end">
                                    <i class="fas fa-cogs mr-2"></i>
                                    {{ __('Actions') }}
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($walletTransactionsPaginated as $transaction)
                            <tr class="align-middle border-bottom-light hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded text-sm font-semibold">#{{ $transaction['id'] }}</span>
                                    <span class="ml-2 inline-flex items-center px-2 py-1 bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-300 rounded text-xs">
                                        <i class="fas fa-wallet mr-1"></i>Wallet
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($transaction['reservation_id'])
                                        <a href="{{ route('reservations.show', $transaction['reservation_id']) }}" 
                                           class="inline-flex items-center px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 rounded text-xs font-medium hover:bg-blue-200 dark:hover:bg-blue-800">
                                            <i class="fas fa-calendar-check mr-1"></i>
                                            #{{ $transaction['reservation_id'] }}
                                        </a>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500 text-xs">
                                            <i class="fas fa-minus"></i> N/A
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm">
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            {{ $transaction['created_at'] instanceof \Carbon\Carbon ? $transaction['created_at']->format('M d, Y') : \Carbon\Carbon::parse($transaction['created_at'])->format('M d, Y') }}
                                        </div>
                                        <small class="text-gray-500 dark:text-gray-400">
                                            {{ $transaction['created_at'] instanceof \Carbon\Carbon ? $transaction['created_at']->format('H:i:s') : \Carbon\Carbon::parse($transaction['created_at'])->format('H:i:s') }}
                                        </small>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-truncate" style="max-width: 200px;" title="{{ $transaction['owner_name'] ?? auth()->user()->name }}">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $transaction['owner_name'] ?? auth()->user()->name }}</div>
                                        <small class="text-gray-500 dark:text-gray-400">{{ $transaction['owner_email'] ?? auth()->user()->email }}</small>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-1 bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-300 rounded text-xs">
                                        <i class="fas fa-wallet mr-1"></i>Wallet
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-900 dark:text-white">
                                        {{ $transaction['charging_point_name'] ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-medium {{ $transaction['amount'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ number_format(abs($transaction['amount']), 2) }} {{ $transaction['currency'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded text-xs">
                                        <i class="fas fa-check mr-1"></i>Completed
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 dark:text-white max-w-xs truncate" title="{{ $transaction['description'] }}">
                                        {{ $transaction['description'] }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <a href="{{ route('transactions.show', $transaction['wallet_transaction']->id) }}"
                                       class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700 transition-colors">
                                        <i class="fas fa-eye mr-1"></i>
                                        {{ __('View') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-6 py-12 text-center">
                                    <div class="text-gray-400 dark:text-gray-500">
                                        <i class="fas fa-wallet text-4xl mb-4"></i>
                                        <p class="text-lg font-medium">{{ __('No wallet transactions found') }}</p>
                                        <p class="text-sm mt-2">
                                            @if(isset($filters['exclude_reservation_wallet_transactions']) && $filters['exclude_reservation_wallet_transactions'])
                                                {{ __('Filter is excluding wallet transactions related to reservations') }}
                                            @else
                                                {{ __('Your wallet transaction history will appear here') }}
                                            @endif
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination for Wallet Transactions --}}
            @if($walletTransactionsPaginated->hasPages())
                <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700 px-6 py-5 border-t border-gray-200 dark:border-gray-600 shadow-sm">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div class="flex items-center">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('Showing') }} 
                                <span class="font-semibold">{{ $walletTransactionsPaginated->firstItem() ?? 0 }}</span>
                                {{ __('to') }} 
                                <span class="font-semibold">{{ $walletTransactionsPaginated->lastItem() ?? 0 }}</span>
                                {{ __('of') }} 
                                <span class="font-semibold">{{ $walletTransactionsPaginated->total() }}</span>
                                {{ __('results') }}
                            </p>
                        </div>
                        <div class="flex items-center justify-center lg:justify-end">
                            {{ $walletTransactionsPaginated->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Table 2: Reservation Transactions --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden animate-slide-in">
            <div class="bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 px-6 py-4 border-b border-gray-200 dark:border-gray-600">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center">
                        <div class="bg-green-100 dark:bg-green-900 rounded-lg p-2 mr-4">
                            <i class="fas fa-calendar-check text-green-600 dark:text-green-400 text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ __('Reservation Transactions') }}
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 flex items-center mt-1">
                                <i class="fas fa-info-circle mr-2"></i>
                                {{ __('Transactions from reservations') }} 
                                <span class="ml-2 text-xs bg-green-200 dark:bg-green-800 px-2 py-1 rounded">
                                    @php
                                        $reservationCount = 0;
                                        try {
                                            if (isset($reservationTransactionsPaginated) && $reservationTransactionsPaginated instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator) {
                                                $total = $reservationTransactionsPaginated->total();
                                                $reservationCount = is_numeric($total) ? (int) $total : 0;
                                            } elseif (isset($reservationTransactions) && !is_null($reservationTransactions)) {
                                                if (is_array($reservationTransactions)) {
                                                    $reservationCount = (int) count($reservationTransactions);
                                                } elseif (is_object($reservationTransactions) && method_exists($reservationTransactions, 'count')) {
                                                    $cnt = $reservationTransactions->count();
                                                    $reservationCount = is_numeric($cnt) ? (int) $cnt : 0;
                                                } elseif (is_countable($reservationTransactions)) {
                                                    $reservationCount = (int) count($reservationTransactions);
                                                }
                                            }
                                        } catch (\Exception $e) {
                                            $reservationCount = 0;
                                        }
                                        // Force to integer
                                        $reservationCount = (int) $reservationCount;
                                        
                                        $transactionsText = __('messages.transactions');
                                        if (is_array($transactionsText)) {
                                            $transactionsText = 'transactions';
                                        }
                                    @endphp
                                    {{ $reservationCount }} {{ $transactionsText }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="overflow-x-auto custom-scrollbar">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                <div class="flex items-center">
                                    <i class="fas fa-hashtag mr-2"></i>
                                    {{ __('ID') }}
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                <div class="flex items-center">
                                    <i class="fas fa-calendar-check mr-2"></i>
                                    {{ __('Reservation ID') }}
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                <div class="flex items-center">
                                    <i class="fas fa-calendar mr-2"></i>
                                    {{ __('Date') }}
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                <div class="flex items-center">
                                    <i class="fas fa-user mr-2"></i>
                                    {{ __('User') }}
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                <div class="flex items-center">
                                    <i class="fas fa-bolt mr-2"></i>
                                    {{ __('Charging Point') }}
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                <div class="flex items-center">
                                    <i class="fas fa-euro-sign mr-2"></i>
                                    {{ __('Amount HT') }}
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                <div class="flex items-center">
                                    <i class="fas fa-euro-sign mr-2"></i>
                                    {{ __('Amount TTC') }}
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                <div class="flex items-center">
                                    <i class="fas fa-check-circle mr-2"></i>
                                    {{ __('Status') }}
                                </div>
                            </th>
                            @if(auth()->user()->role === 'admin')
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                <div class="flex items-center">
                                    <i class="fas fa-share-alt mr-2"></i>
                                    {{ __('Parts & Fees') }}
                                </div>
                            </th>
                            @endif
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                <div class="flex items-center justify-end">
                                    <i class="fas fa-cogs mr-2"></i>
                                    {{ __('Actions') }}
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($reservationTransactionsPaginated as $transaction)
                            <tr class="align-middle border-bottom-light hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded text-sm font-semibold">#{{ $transaction['id'] }}</span>
                                    <span class="ml-2 inline-flex items-center px-2 py-1 bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 rounded text-xs">
                                        <i class="fas fa-calendar-check mr-1"></i>Reservation
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($transaction['reservation_id'])
                                        <a href="{{ route('reservations.show', $transaction['reservation_id']) }}" 
                                           class="inline-flex items-center px-2 py-1 bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 rounded text-xs font-medium hover:bg-green-200 dark:hover:bg-green-800">
                                            <i class="fas fa-calendar-check mr-1"></i>
                                            #{{ $transaction['reservation_id'] }}
                                        </a>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500 text-xs">
                                            <i class="fas fa-minus"></i> N/A
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm">
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            {{ $transaction['created_at'] instanceof \Carbon\Carbon ? $transaction['created_at']->format('M d, Y') : \Carbon\Carbon::parse($transaction['created_at'])->format('M d, Y') }}
                                        </div>
                                        <small class="text-gray-500 dark:text-gray-400">
                                            {{ $transaction['created_at'] instanceof \Carbon\Carbon ? $transaction['created_at']->format('H:i:s') : \Carbon\Carbon::parse($transaction['created_at'])->format('H:i:s') }}
                                        </small>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-truncate" style="max-width: 200px;">
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            {{ $transaction['transaction']->user->name ?? 'N/A' }}
                                        </div>
                                        <small class="text-gray-500 dark:text-gray-400">
                                            {{ $transaction['transaction']->user->email ?? 'N/A' }}
                                        </small>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-900 dark:text-white">
                                        {{ $transaction['charging_point_name'] ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        // CORRECTION: Utiliser le service TransactionHTTTCService pour calculer correctement HT
                                        try {
                                            $httcService = new \App\Services\TransactionHTTTCService();
                                            $httcAmounts = $httcService->calculateHTTTC($transaction['transaction']);
                                            $amountHT = $httcAmounts['ht'];
                                        } catch (\Exception $e) {
                                            // Fallback si le service échoue
                                            $amountHT = $transaction['transaction']->amount_ht ?? ($transaction['transaction']->amount ?? 0);
                                        }
                                    @endphp
                                    <span class="font-medium text-gray-900 dark:text-white">
                                        {{ number_format($amountHT, 2) }} {{ $transaction['currency'] ?? 'EUR' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        // CORRECTION: Utiliser le service TransactionHTTTCService pour calculer correctement TTC
                                        try {
                                            $httcService = new \App\Services\TransactionHTTTCService();
                                            $httcAmounts = $httcService->calculateHTTTC($transaction['transaction']);
                                            $amountTTC = $httcAmounts['ttc'];
                                        } catch (\Exception $e) {
                                            // Fallback si le service échoue
                                            $amountTTC = $transaction['transaction']->price_total ?? ($transaction['amount'] ?? 0);
                                        }
                                    @endphp
                                    <span class="font-medium text-gray-900 dark:text-white">
                                        {{ number_format($amountTTC, 2) }} {{ $transaction['currency'] ?? 'EUR' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs
                                        @if($transaction['status'] === 'completed') bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200
                                        @elseif($transaction['status'] === 'pending') bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200
                                        @elseif($transaction['status'] === 'failed') bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200
                                        @else bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200
                                        @endif">
                                        <i class="fas fa-{{ $transaction['status'] === 'completed' ? 'check' : ($transaction['status'] === 'pending' ? 'clock' : 'times') }} mr-1"></i>
                                        {{ ucfirst($transaction['status']) }}
                                    </span>
                                </td>
                                @if(auth()->user()->role === 'admin')
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($transaction['transaction_detail'])
                                        <button type="button" 
                                                class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700 transition-colors"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#transactionFeesModal"
                                                data-transaction-id="{{ $transaction['transaction']->id }}">
                                            <i class="fas fa-calculator mr-1"></i>
                                            {{ __('View Parts') }}
                                        </button>
                                    @else
                                        <span class="text-xs text-gray-500 dark:text-gray-400">N/A</span>
                                    @endif
                                </td>
                                @endif
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <a href="{{ route('transactions.show', $transaction['transaction']->id) }}"
                                       class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700 transition-colors">
                                        <i class="fas fa-eye mr-1"></i>
                                        {{ __('View') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ auth()->user()->role === 'admin' ? '10' : '9' }}" class="px-6 py-12 text-center">
                                    <div class="text-gray-400 dark:text-gray-500">
                                        <i class="fas fa-calendar-check text-4xl mb-4"></i>
                                        <p class="text-lg font-medium">{{ __('No reservation transactions found') }}</p>
                                        <p class="text-sm mt-2">{{ __('Your reservation transaction history will appear here') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination for Reservation Transactions --}}
            @if($reservationTransactionsPaginated->hasPages())
                <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700 px-6 py-5 border-t border-gray-200 dark:border-gray-600 shadow-sm">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div class="flex items-center">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('Showing') }} 
                                <span class="font-semibold">{{ $reservationTransactionsPaginated->firstItem() ?? 0 }}</span>
                                {{ __('to') }} 
                                <span class="font-semibold">{{ $reservationTransactionsPaginated->lastItem() ?? 0 }}</span>
                                {{ __('of') }} 
                                <span class="font-semibold">{{ $reservationTransactionsPaginated->total() }}</span>
                                {{ __('results') }}
                            </p>
                        </div>
                        <div class="flex items-center justify-center lg:justify-end">
                            {{ $reservationTransactionsPaginated->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Modal for Admin to view full transaction fees details --}}
        @if(auth()->user()->role === 'admin')
        <div class="modal fade" id="transactionFeesModal" tabindex="-1" aria-labelledby="transactionFeesModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-fullscreen-lg-down modal-xl">
                <div class="modal-content bg-white dark:bg-gray-800 rounded-xl border-0 shadow-2xl">
                    <div class="modal-header bg-gradient-to-r from-blue-600 via-blue-700 to-blue-800 text-white rounded-t-xl px-6 py-4 border-0">
                        <div class="flex items-center flex-1">
                            <div class="bg-white bg-opacity-20 rounded-lg p-2 mr-3">
                                <i class="fas fa-calculator text-xl"></i>
                            </div>
                            <div>
                                <h5 class="modal-title text-lg font-bold mb-0" id="transactionFeesModalLabel">
                                    {{ __('messages.complete_fees_breakdown') }}
                                </h5>
                                <p class="text-blue-100 text-xs mb-0 mt-1">Analyse détaillée des calculs transactionnels</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" 
                                    class="btn btn-sm btn-light btn-outline-light" 
                                    id="exportFeesDetailsBtn"
                                    title="{{ __('messages.export_details') }}"
                                    style="display: none;">
                                <i class="fas fa-download"></i>
                            </button>
                            <button type="button" 
                                    class="btn btn-sm btn-light btn-outline-light" 
                                    id="printFeesDetailsBtn"
                                    title="{{ __('messages.print') }}"
                                    style="display: none;">
                                <i class="fas fa-print"></i>
                            </button>
                            <button type="button" 
                                    class="text-white hover:text-gray-200 focus:outline-none transition-colors duration-200" 
                                    data-bs-dismiss="modal" 
                                    aria-label="Close">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                    </div>
                    <div class="modal-body px-6 py-6 overflow-y-auto" id="transactionFeesModalBody" style="max-height: calc(100vh - 200px);">
                        <div class="text-center py-12">
                            <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                                <span class="visually-hidden">Chargement...</span>
                            </div>
                            <p class="mt-3 text-gray-500 dark:text-gray-400 font-medium">Chargement des détails...</p>
                            <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">Veuillez patienter</p>
                        </div>
                    </div>
                    <div class="modal-footer bg-gray-50 dark:bg-gray-700 rounded-b-xl px-6 py-3 border-t border-gray-200 dark:border-gray-600">
                        <div class="flex items-center justify-between w-100">
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                <i class="fas fa-info-circle me-1"></i>
                                Les calculs sont basés sur les Business Profiles actifs au moment de la transaction
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>{{ __('messages.close') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
{{-- Tailwind CSS Custom Styles --}}
<style>
    /* Text visibility - respect Tailwind classes */
    .transactions-page-content {
        color: #1f2937;
    }
    
    .dark .transactions-page-content {
        color: #f3f4f6;
    }
    
    /* Ensure text colors work properly */
    .transactions-page-content [class*="text-white"] { color: white !important; }
    .transactions-page-content [class*="text-gray-900"] { color: #111827 !important; }
    .transactions-page-content [class*="text-gray-500"] { color: #6b7280 !important; }
    .transactions-page-content [class*="text-gray-400"] { color: #9ca3af !important; }
    .transactions-page-content [class*="text-blue-100"] { color: #dbeafe !important; }
    .transactions-page-content [class*="text-blue-600"] { color: #2563eb !important; }
    .transactions-page-content [class*="text-green-600"] { color: #16a34a !important; }
    .transactions-page-content [class*="text-red-600"] { color: #dc2626 !important; }
    
    .dark .transactions-page-content [class*="dark:text-white"] { color: white !important; }
    .dark .transactions-page-content [class*="dark:text-gray-400"] { color: #9ca3af !important; }
    .dark .transactions-page-content [class*="dark:text-blue-400"] { color: #60a5fa !important; }
    .dark .transactions-page-content [class*="dark:text-green-400"] { color: #4ade80 !important; }
    .dark .transactions-page-content [class*="dark:text-red-400"] { color: #f87171 !important; }

    /* Custom animations for enhanced UX */
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes fadeInScale {
        from {
            opacity: 0;
            transform: scale(0.9);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }
    
    .animate-slide-in {
        animation: slideInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .animate-fade-in {
        animation: fadeInScale 0.8s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Enhanced loading overlay */
    .loading-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background-color: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(4px);
        z-index: 2000;
        align-items: center;
        justify-content: center;
    }
    
    .loading-overlay.show {
        display: flex;
        animation: fadeInScale 0.3s ease;
    }

    /* Custom scrollbar for better UX */
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 3px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }
    
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
</style>
@endpush

@push('scripts')
{{-- For performance, it's better to compile assets using Vite/Mix --}}
<script>
    (function() {
        'use strict';

        document.addEventListener('DOMContentLoaded', function() {
            const ui = {
                filterForm: document.getElementById('filterForm'),
                typeSelect: document.getElementById('type'),
                dateFromInput: document.getElementById('date_from'),
                dateToInput: document.getElementById('date_to'),
                searchInput: document.getElementById('search'),
                searchClearBtn: document.getElementById('searchClear'),
                dateRangeFeedback: document.getElementById('dateRangeFeedback'),
                exportBtn: document.querySelector('a[href*="export"]'),
                quickRangeBtns: document.querySelectorAll('.quick-range'),
                cards: document.querySelectorAll('.card'),
                tooltips: document.querySelectorAll('[data-bs-toggle="tooltip"]')
            };

            let searchTimeout;
            const overlay = createLoadingOverlay();

            function createLoadingOverlay() {
                const el = document.createElement('div');
                el.className = 'loading-overlay';
                el.innerHTML = `<div class="spinner"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><div class="mt-2 small text-muted">${"{{ __('Loading...') }}"}</div></div>`;
                document.body.appendChild(el);
                return {
                    show: () => el.classList.add('show'),
                    hide: () => el.classList.remove('show')
                };
            }

            function submitFilterForm() {
                if (!validateDateRange()) return;
                overlay.show();
                ui.filterForm.submit();
            }

            function validateDateRange() {
                const from = ui.dateFromInput.value ? new Date(ui.dateFromInput.value) : null;
                const to = ui.dateToInput.value ? new Date(ui.dateToInput.value) : null;
                const isValid = !(from && to && from > to);

                ui.dateFromInput.classList.toggle('is-invalid', !isValid);
                ui.dateToInput.classList.toggle('is-invalid', !isValid);
                if (ui.dateRangeFeedback) {
                    ui.dateRangeFeedback.style.display = isValid ? 'none' : 'block';
                }
                return isValid;
            }

            // Load transaction fees details for admin modal
            function initTransactionFeesModal() {
                @if(auth()->user()->role === 'admin')
                const modal = document.getElementById('transactionFeesModal');
                if (!modal) return;

                modal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const transactionId = button.getAttribute('data-transaction-id');
                    const modalBody = document.getElementById('transactionFeesModalBody');

                    if (!transactionId) {
                        modalBody.innerHTML = '<div class="alert alert-danger">Erreur: ID de transaction manquant</div>';
                        return;
                    }

                    // Show loading state
                    modalBody.innerHTML = `
                        <div class="text-center py-8">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Chargement...</span>
                            </div>
                            <p class="mt-3 text-gray-500 dark:text-gray-400">Chargement des détails...</p>
                        </div>
                    `;

                    // Fetch transaction details via AJAX
                    const url = `{{ route('transactions.fees-details', ['transaction' => ':id']) }}`.replace(':id', transactionId);
                    fetch(url, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success && data.html) {
                            modalBody.innerHTML = data.html;
                            
                            // Afficher les boutons d'export et impression
                            const exportBtn = document.getElementById('exportFeesDetailsBtn');
                            const printBtn = document.getElementById('printFeesDetailsBtn');
                            if (exportBtn) exportBtn.style.display = 'inline-block';
                            if (printBtn) printBtn.style.display = 'inline-block';
                            
                            // Initialiser les tooltips
                            const tooltipTriggerList = modalBody.querySelectorAll('[data-bs-toggle="tooltip"]');
                            [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
                        } else {
                            modalBody.innerHTML = `
                                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Attention!</strong> ${data.message || 'Impossible de charger les détails'}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            `;
                        }
                    })
                    .catch(error => {
                        console.error('Error loading transaction fees:', error);
                        modalBody.innerHTML = `
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <strong>Erreur!</strong> Une erreur s'est produite lors du chargement des détails.
                                <br><small class="text-muted">${error.message}</small>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        `;
                    });
                
                    // Export functionality
                    const exportBtn = document.getElementById('exportFeesDetailsBtn');
                    if (exportBtn) {
                        exportBtn.addEventListener('click', function() {
                            const content = modalBody.innerHTML;
                            const blob = new Blob([`
                                <!DOCTYPE html>
                                <html>
                                <head>
                                    <title>Transaction Fees Details - ${transactionId}</title>
                                    <style>
                                        body { font-family: Arial, sans-serif; padding: 20px; }
                                        table { border-collapse: collapse; width: 100%; margin: 15px 0; }
                                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                                        th { background-color: #f2f2f2; }
                                        .card { border: 1px solid #ddd; margin: 15px 0; padding: 15px; }
                                    </style>
                                </head>
                                <body>
                                    ${content}
                                </body>
                                </html>
                            `], { type: 'text/html' });
                            const url = URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url;
                            a.download = `transaction-fees-${transactionId}-${Date.now()}.html`;
                            a.click();
                            URL.revokeObjectURL(url);
                        });
                    }
                    
                    // Print functionality
                    const printBtn = document.getElementById('printFeesDetailsBtn');
                    if (printBtn) {
                        printBtn.addEventListener('click', function() {
                            const printWindow = window.open('', '_blank');
                            printWindow.document.write(`
                                <!DOCTYPE html>
                                <html>
                                <head>
                                    <title>Transaction Fees Details - ${transactionId}</title>
                                    <style>
                                        body { font-family: Arial, sans-serif; padding: 20px; }
                                        table { border-collapse: collapse; width: 100%; margin: 15px 0; }
                                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                                        th { background-color: #f2f2f2; }
                                        .card { border: 1px solid #ddd; margin: 15px 0; padding: 15px; }
                                        @media print {
                                            .no-print { display: none; }
                                        }
                                    </style>
                                </head>
                                <body>
                                    ${modalBody.innerHTML}
                                </body>
                                </html>
                            `);
                            printWindow.document.close();
                            printWindow.print();
                        });
                    }
                });
                @endif
            }

            function initEventListeners() {
                [ui.typeSelect, ui.dateFromInput, ui.dateToInput].forEach(el => {
                    el?.addEventListener('change', submitFilterForm);
                });

                ui.searchInput?.addEventListener('input', () => {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(submitFilterForm, 500);
                });

                ui.searchClearBtn?.addEventListener('click', () => {
                    if (ui.searchInput) {
                        ui.searchInput.value = '';
                        submitFilterForm();
                    }
                });

                ui.quickRangeBtns.forEach(btn => {
                    btn.addEventListener('click', function() {
                        const range = this.dataset.range;
                        const today = new Date();
                        const format = d => d.toISOString().split('T');
                        let fromDate = new Date();

                        if (range !== 'today') {
                            fromDate.setDate(today.getDate() - (parseInt(range, 10) - 1));
                        }

                        ui.dateFromInput.value = format(fromDate);
                        ui.dateToInput.value = format(today);
                        submitFilterForm();
                    });
                });

                ui.filterForm?.addEventListener('submit', e => {
                    if (!validateDateRange()) {
                        e.preventDefault();
                        return;
                    }
                    overlay.show();
                });

                ui.exportBtn?.addEventListener('click', function() {
                    this.innerHTML = `<i class="fas fa-spinner fa-spin me-2"></i>${"{{ __('Exporting...') }}"}`;
                    this.classList.add('disabled');
                    overlay.show();
                });
            }

            function initAnimations() {
                ui.cards.forEach((card, index) => {
                    card.style.animationDelay = `${index * 0.05}s`;
                });
            }

            function initTooltips() {
                [...ui.tooltips].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
            }

            // Filter by reservation ID
            function filterByReservation(reservationId) {
                const url = new URL(window.location.href);
                
                if (reservationId) {
                    url.searchParams.set('reservation_id', reservationId);
                } else {
                    url.searchParams.delete('reservation_id');
                }
                
                // Reset to page 1 when filtering
                url.searchParams.set('page', '1');
                
                // Show loading overlay
                overlay.show();
                
                // Redirect to filtered URL
                window.location.href = url.toString();
            }

            // Initialize all parts of the script
            initTransactionFeesModal();
            initEventListeners();
            initAnimations();
            initTooltips();
        });
    })();
</script>
@endpush
