@extends('layouts.app')

@section('title', __('Comparaison des Frais'))
@section('page-title', __('Comparaison des Frais de Transaction'))

@section('content')
<div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center">
            <div class="bg-purple-100 dark:bg-purple-900/30 rounded-lg p-3 mr-4">
                <i class="fas fa-balance-scale text-2xl text-purple-600 dark:text-purple-400"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Comparaison des Frais') }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Analyse comparative des frais de transaction') }}</p>
            </div>
        </div>
    </div>

    @if (!empty($comparison))
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Méthode') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Transactions') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Volume total') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Frais totaux') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Frais moy.') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('% Frais') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($comparison as $row)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ ucfirst($row['method'] ?? '–') }}</td>
                        <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $row['count'] ?? 0 }}</td>
                        <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">{{ number_format($row['total_amount'] ?? 0, 2) }} €</td>
                        <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">{{ number_format($row['total_fees'] ?? 0, 2) }} €</td>
                        <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">{{ number_format($row['avg_fee'] ?? 0, 2) }} €</td>
                        <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">{{ number_format($row['fee_percentage'] ?? 0, 2) }}%</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @else
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
        <i class="fas fa-balance-scale text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Aucune donnée de comparaison disponible') }}</p>
    </div>
    @endif
</div>
@endsection
