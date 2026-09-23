@extends('layouts.app')

@section('title', __('Sessions Actives'))
@section('page-title', __('Sessions Actives'))

@section('content')
<div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="bg-green-100 dark:bg-green-900/30 rounded-lg p-3 mr-4">
                    <i class="fas fa-bolt text-2xl text-green-600 dark:text-green-400 animate-pulse"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Sessions Actives') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ count($sessions) }} {{ __('session(s) en cours') }}</p>
                </div>
            </div>
            <button onclick="window.location.reload()"
                    class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                <i class="fas fa-sync mr-2"></i>{{ __('Actualiser') }}
            </button>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        @if (count($sessions) > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Borne') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Connecteur') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Tag') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Transaction') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Début') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Énergie') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($sessions as $session)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-4">
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $session->chargingPoint?->name ?? $session['charge_point_id'] ?? '–' }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $session->chargingPoint?->charge_point_id ?? '–' }}</p>
                            </div>
                        </td>
                        <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $session->connector_id ?? $session['connector_id'] ?? '–' }}</td>
                        <td class="px-4 py-4 text-sm font-mono text-gray-700 dark:text-gray-300">{{ $session->id_tag ?? $session['id_tag'] ?? '–' }}</td>
                        <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $session->transaction_id ?? $session['transaction_id'] ?? '–' }}</td>
                        <td class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400">
                            {{ isset($session->started_at) ? $session->started_at->format('d/m H:i') : (isset($session['started_at']) ? \Carbon\Carbon::parse($session['started_at'])->format('d/m H:i') : '–') }}
                        </td>
                        <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">
                            {{ isset($session->energy_kwh) ? number_format($session->energy_kwh, 2) . ' kWh' : '–' }}
                        </td>
                        <td class="px-4 py-4 text-right">
                            @if (isset($session->id))
                            <a href="{{ route('charging-sessions.show', $session->id) }}"
                               class="inline-flex items-center px-3 py-1.5 bg-primary-600 text-white text-xs font-medium rounded hover:bg-primary-700 transition-colors">
                                {{ __('Voir') }}
                            </a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="p-12 text-center">
            <i class="fas fa-check-circle text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Aucune session active en ce moment') }}</p>
        </div>
        @endif
    </div>
</div>
@endsection
