@extends('layouts.app')

@section('title', __('Mon Portefeuille'))
@section('page-title', __('Mon Portefeuille'))

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center">
                <div class="bg-green-100 dark:bg-green-900/30 rounded-lg p-3 mr-4">
                    <i class="fas fa-wallet text-2xl text-green-600 dark:text-green-400"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Mon Portefeuille') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Solde et historique de vos transactions') }}</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Solde disponible') }}</p>
                <p class="text-3xl font-bold text-green-600 dark:text-green-400">
                    {{ number_format($wallet->balance ?? 0, 2) }} {{ strtoupper($wallet->currency ?? 'EUR') }}
                </p>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    @if (!empty($statistics))
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        @foreach ([
            ['label' => __('Total Crédité'), 'value' => number_format($statistics['total_credited'] ?? 0, 2), 'icon' => 'fas fa-arrow-down', 'color' => 'green'],
            ['label' => __('Total Débité'), 'value' => number_format($statistics['total_debited'] ?? 0, 2), 'icon' => 'fas fa-arrow-up', 'color' => 'red'],
            ['label' => __('Transactions'), 'value' => $statistics['transaction_count'] ?? 0, 'icon' => 'fas fa-exchange-alt', 'color' => 'blue'],
        ] as $stat)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center">
                <div class="bg-{{ $stat['color'] }}-100 dark:bg-{{ $stat['color'] }}-900/30 rounded-lg p-3 mr-3">
                    <i class="{{ $stat['icon'] }} text-{{ $stat['color'] }}-600 dark:text-{{ $stat['color'] }}-400"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $stat['value'] }}</p>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <!-- Actions -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4">{{ __('Actions') }}</h2>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('wallets.transactions', $wallet) }}"
               class="inline-flex items-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">
                <i class="fas fa-history mr-2"></i>
                {{ __('Historique des transactions') }}
            </a>
            @if (auth()->user() && auth()->user()->can('withdraw', $wallet))
            <a href="{{ route('withdrawal-requests.create') }}"
               class="inline-flex items-center px-4 py-2 bg-orange-600 text-white text-sm font-medium rounded-lg hover:bg-orange-700 transition-colors">
                <i class="fas fa-money-bill-wave mr-2"></i>
                {{ __('Demander un retrait') }}
            </a>
            @endif
        </div>
    </div>
</div>
@endsection
