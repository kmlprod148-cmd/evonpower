@extends('layouts.app')

@section('title', __('Logs') . ' - ' . ($chargingPoint->name ?? ''))
@section('page-title', __('Logs OCPP'))

@section('content')
<div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-3 mr-4">
                    <i class="fas fa-terminal text-2xl text-gray-600 dark:text-gray-400"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Logs') }}: {{ $chargingPoint->name ?? '' }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Historique des messages OCPP') }}</p>
                </div>
            </div>
            <a href="{{ route('remote-control.show', $chargingPoint) }}"
               class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>{{ __('Retour') }}
            </a>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        @if (!empty($logs) && count($logs) > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Date') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Type') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Action') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Détails') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($logs as $log)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                            {{ isset($log['created_at']) ? \Carbon\Carbon::parse($log['created_at'])->format('d/m/Y H:i:s') : '–' }}
                        </td>
                        <td class="px-4 py-3">
                            @php $logType = $log['type'] ?? 'info'; @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $logType === 'error' ? 'bg-red-100 text-red-800' :
                                   ($logType === 'warning' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800') }}">
                                {{ strtoupper($logType) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $log['action'] ?? $log['message'] ?? '–' }}</td>
                        <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400 font-mono max-w-xs truncate">
                            {{ isset($log['details']) ? json_encode($log['details']) : '' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="p-12 text-center">
            <i class="fas fa-inbox text-3xl text-gray-300 dark:text-gray-600 mb-3"></i>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Aucun log disponible') }}</p>
        </div>
        @endif
    </div>
</div>
@endsection
