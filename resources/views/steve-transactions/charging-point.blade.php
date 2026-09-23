@extends('layouts.app')

@section('title', __('Transactions') . ' - ' . ($chargingPoint->name ?? $chargeBoxId))
@section('page-title', __('Transactions OCPP par Borne'))

@section('content')
<div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="bg-blue-100 dark:bg-blue-900/30 rounded-lg p-3 mr-4">
                    <i class="fas fa-charging-station text-2xl text-blue-600 dark:text-blue-400"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $chargingPoint->name ?? $chargeBoxId }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Transactions Steve OCPP') }}</p>
                </div>
            </div>
            <a href="{{ route('steve-transactions.index') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>{{ __('Retour') }}
            </a>
        </div>
    </div>

    @if ($summary)
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        @foreach ([
            [__('Total'), $summary['total'] ?? 0, 'blue'],
            [__('Actives'), $summary['active'] ?? 0, 'green'],
            [__('Terminées'), $summary['completed'] ?? 0, 'gray'],
            [__('Énergie (kWh)'), number_format($summary['total_kwh'] ?? 0, 2), 'yellow'],
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
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Tag') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Connecteur') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Début') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Fin') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Énergie') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($transactions as $tx)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-3 text-sm font-mono text-gray-500 dark:text-gray-400">{{ $tx['id'] ?? $tx->id ?? '–' }}</td>
                        <td class="px-4 py-3 text-sm font-mono text-gray-700 dark:text-gray-300">{{ $tx['idTag'] ?? $tx->id_tag ?? '–' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $tx['connectorId'] ?? $tx->connector_id ?? '–' }}</td>
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
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Aucune transaction') }}</p>
        </div>
        @endif
    </div>
</div>
@endsection
