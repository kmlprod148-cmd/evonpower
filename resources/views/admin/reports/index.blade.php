@extends('layouts.app')

@section('title', __('Rapports & Exports'))
@section('page-title', __('Rapports & Exports'))

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="bg-indigo-100 dark:bg-indigo-900/30 rounded-lg p-3">
                    <i class="fas fa-chart-bar text-2xl text-indigo-600 dark:text-indigo-400"></i>
                </div>
            </div>
            <div class="ml-4">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Rapports & Exports') }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Générez et exportez vos rapports financiers et opérationnels') }}</p>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-400 p-4 rounded-lg">
            <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
        </div>
    @endif
    @if (session('error'))
        <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-400 p-4 rounded-lg">
            <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
        </div>
    @endif

    <!-- Date Filter -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
            <i class="fas fa-filter text-gray-400 mr-2"></i>
            {{ __('Période de rapport') }}
        </h2>
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Du') }}</label>
                <input type="date" name="date_from" value="{{ request('date_from', now()->startOfMonth()->format('Y-m-d')) }}"
                       class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Au') }}</label>
                <input type="date" name="date_to" value="{{ request('date_to', now()->format('Y-m-d')) }}"
                       class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500">
            </div>
            <button type="submit"
                    class="inline-flex items-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">
                <i class="fas fa-search mr-2"></i>
                {{ __('Appliquer') }}
            </button>
        </form>
    </div>

    <!-- Report Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">

        <!-- Transactions Report -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center mb-4">
                <div class="bg-blue-100 dark:bg-blue-900/30 rounded-lg p-3 mr-4">
                    <i class="fas fa-exchange-alt text-xl text-blue-600 dark:text-blue-400"></i>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Transactions') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Export de toutes les transactions') }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.reports.transactions.export', ['format' => 'pdf', 'date_from' => request('date_from', now()->startOfMonth()->format('Y-m-d')), 'date_to' => request('date_to', now()->format('Y-m-d'))]) }}"
                   class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-red-600 text-white text-xs font-medium rounded-lg hover:bg-red-700 transition-colors">
                    <i class="fas fa-file-pdf mr-1.5"></i> PDF
                </a>
                <a href="{{ route('admin.reports.transactions.export', ['format' => 'excel', 'date_from' => request('date_from', now()->startOfMonth()->format('Y-m-d')), 'date_to' => request('date_to', now()->format('Y-m-d'))]) }}"
                   class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-file-excel mr-1.5"></i> Excel
                </a>
            </div>
        </div>

        <!-- Withdrawals Report -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center mb-4">
                <div class="bg-orange-100 dark:bg-orange-900/30 rounded-lg p-3 mr-4">
                    <i class="fas fa-money-bill-wave text-xl text-orange-600 dark:text-orange-400"></i>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Retraits') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Export des demandes de retrait') }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.reports.withdrawals.export', ['format' => 'pdf', 'date_from' => request('date_from'), 'date_to' => request('date_to')]) }}"
                   class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-red-600 text-white text-xs font-medium rounded-lg hover:bg-red-700 transition-colors">
                    <i class="fas fa-file-pdf mr-1.5"></i> PDF
                </a>
                <a href="{{ route('admin.reports.withdrawals.export', ['format' => 'excel', 'date_from' => request('date_from'), 'date_to' => request('date_to')]) }}"
                   class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-file-excel mr-1.5"></i> Excel
                </a>
            </div>
        </div>

        <!-- Charging Sessions Report -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center mb-4">
                <div class="bg-green-100 dark:bg-green-900/30 rounded-lg p-3 mr-4">
                    <i class="fas fa-charging-station text-xl text-green-600 dark:text-green-400"></i>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Sessions de Recharge') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Export des sessions de charge') }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.reports.sessions.export', ['format' => 'pdf', 'date_from' => request('date_from'), 'date_to' => request('date_to')]) }}"
                   class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-red-600 text-white text-xs font-medium rounded-lg hover:bg-red-700 transition-colors">
                    <i class="fas fa-file-pdf mr-1.5"></i> PDF
                </a>
                <a href="{{ route('admin.reports.sessions.export', ['format' => 'excel', 'date_from' => request('date_from'), 'date_to' => request('date_to')]) }}"
                   class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-file-excel mr-1.5"></i> Excel
                </a>
            </div>
        </div>

        <!-- Financial Report -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center mb-4">
                <div class="bg-purple-100 dark:bg-purple-900/30 rounded-lg p-3 mr-4">
                    <i class="fas fa-chart-pie text-xl text-purple-600 dark:text-purple-400"></i>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Rapport Financier') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Résumé financier global') }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.reports.financial.export', ['format' => 'pdf', 'date_from' => request('date_from'), 'date_to' => request('date_to')]) }}"
                   class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-red-600 text-white text-xs font-medium rounded-lg hover:bg-red-700 transition-colors">
                    <i class="fas fa-file-pdf mr-1.5"></i> PDF
                </a>
                <a href="{{ route('admin.reports.financial.export', ['format' => 'excel', 'date_from' => request('date_from'), 'date_to' => request('date_to')]) }}"
                   class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-file-excel mr-1.5"></i> Excel
                </a>
            </div>
        </div>

        <!-- Statistics Report -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center mb-4">
                <div class="bg-teal-100 dark:bg-teal-900/30 rounded-lg p-3 mr-4">
                    <i class="fas fa-chart-line text-xl text-teal-600 dark:text-teal-400"></i>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Statistiques') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Export des statistiques globales') }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.reports.statistics.export', ['format' => 'pdf', 'date_from' => request('date_from'), 'date_to' => request('date_to')]) }}"
                   class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-red-600 text-white text-xs font-medium rounded-lg hover:bg-red-700 transition-colors">
                    <i class="fas fa-file-pdf mr-1.5"></i> PDF
                </a>
                <a href="{{ route('admin.reports.statistics.export', ['format' => 'excel', 'date_from' => request('date_from'), 'date_to' => request('date_to')]) }}"
                   class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-file-excel mr-1.5"></i> Excel
                </a>
            </div>
        </div>

        <!-- Financial Preview -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center mb-4">
                <div class="bg-yellow-100 dark:bg-yellow-900/30 rounded-lg p-3 mr-4">
                    <i class="fas fa-eye text-xl text-yellow-600 dark:text-yellow-400"></i>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Aperçu Financier') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Visualisation avant export') }}</p>
                </div>
            </div>
            <a href="{{ route('admin.reports.financial.export', ['preview' => true, 'date_from' => request('date_from'), 'date_to' => request('date_to')]) }}"
               class="w-full inline-flex items-center justify-center px-3 py-2 bg-yellow-600 text-white text-xs font-medium rounded-lg hover:bg-yellow-700 transition-colors">
                <i class="fas fa-search mr-1.5"></i> {{ __('Prévisualiser') }}
            </a>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
            <i class="fas fa-tachometer-alt text-gray-400 mr-2"></i>
            {{ __('Liens Rapides') }}
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <a href="{{ route('admin.transactions.index') }}"
               class="flex items-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors">
                <i class="fas fa-exchange-alt text-blue-500 mr-2"></i>
                <span class="text-sm text-blue-700 dark:text-blue-300">{{ __('Transactions') }}</span>
            </a>
            <a href="{{ route('admin.withdrawals.index') }}"
               class="flex items-center p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg hover:bg-orange-100 dark:hover:bg-orange-900/30 transition-colors">
                <i class="fas fa-money-bill-wave text-orange-500 mr-2"></i>
                <span class="text-sm text-orange-700 dark:text-orange-300">{{ __('Retraits') }}</span>
            </a>
            <a href="{{ route('admin.reservations.index') }}"
               class="flex items-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors">
                <i class="fas fa-calendar-check text-green-500 mr-2"></i>
                <span class="text-sm text-green-700 dark:text-green-300">{{ __('Réservations') }}</span>
            </a>
            <a href="{{ route('admin.payments.index') }}"
               class="flex items-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors">
                <i class="fas fa-credit-card text-purple-500 mr-2"></i>
                <span class="text-sm text-purple-700 dark:text-purple-300">{{ __('Paiements') }}</span>
            </a>
        </div>
    </div>
</div>
@endsection
