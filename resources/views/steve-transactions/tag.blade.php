@extends('layouts.app')

@section('title', __('Transactions') . ' - ' . $ocppIdTag)
@section('page-title', __('Transactions par Tag OCPP'))

@section('content')
<div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="bg-indigo-100 dark:bg-indigo-900/30 rounded-lg p-3 mr-4">
                    <i class="fas fa-id-card text-2xl text-indigo-600 dark:text-indigo-400"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white font-mono">{{ $ocppIdTag }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Transactions Steve OCPP pour ce tag') }}</p>
                </div>
            </div>
            <a href="{{ route('steve-transactions.index') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>{{ __('Retour') }}
            </a>
        </div>
    </div>

    @if ($summary)
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
        @foreach ([
            [__('Total sessions'), $summary['total'] ?? 0, 'blue'],
            [__('Énergie totale'), number_format($summary['total_kwh'] ?? 0, 2) . ' kWh', 'green'],
            [__('Dernière activité'), isset($summary['last_activity']) ? \Carbon\Carbon::parse($summary['last_activity'])->format('d/m/Y') : '–', 'gray'],
        ] as [$label, $value, $color])
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</p>
            <p class="text-xl font-bold text-{{ $color }}-600 dark:text-{{ $color }}-400">{{ $value }}</p>
        </div>
        @endforeach
    </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        @if (!empty($transactions) && count($transactions) > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('ID') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Borne') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Connecteur') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Début') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Fin') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Énergie') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($transactions as $tx)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-3 text-sm font-mono text-gray-500 dark:text-gray-400">{{ $tx['id'] ?? '–' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $tx['chargeBoxId'] ?? '–' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $tx['connectorId'] ?? '–' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ isset($tx['startTimestamp']) ? \Carbon\Carbon::parse($tx['startTimestamp'])->format('d/m H:i') : '–' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ isset($tx['stopTimestamp']) ? \Carbon\Carbon::parse($tx['stopTimestamp'])->format('d/m H:i') : '–' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                            {{ isset($tx['stopValue']) ? number_format(($tx['stopValue'] - ($tx['startValue'] ?? 0)) / 1000, 3) . ' kWh' : '–' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="p-12 text-center">
            <i class="fas fa-inbox text-3xl text-gray-300 dark:text-gray-600 mb-3"></i>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Aucune transaction pour ce tag') }}</p>
        </div>
        @endif
    </div>
</div>
@endsection
