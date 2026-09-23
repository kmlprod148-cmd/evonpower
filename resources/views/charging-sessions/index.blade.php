@extends('layouts.app')

@section('title', __('Sessions de Charge'))
@section('page-title', __('Sessions de Charge'))

@section('content')
<div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center">
            <div class="bg-blue-100 dark:bg-blue-900/30 rounded-lg p-3 mr-4">
                <i class="fas fa-bolt text-2xl text-blue-600 dark:text-blue-400"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Sessions de Charge') }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $sessions->total() ?? $sessions->count() }} {{ __('sessions') }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        @if ($sessions->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Borne') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Utilisateur') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Statut') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Énergie') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Début') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Fin') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($sessions as $session)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-3 text-sm font-mono text-gray-500 dark:text-gray-400">{{ $session->id }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $session->chargingPoint?->name ?? '–' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $session->user?->name ?? $session->client?->name ?? '–' }}</td>
                        <td class="px-4 py-3">
                            @php
                                $s = $session->status ?? 'unknown';
                                $colors = ['active' => 'blue', 'completed' => 'green', 'failed' => 'red', 'cancelled' => 'gray'];
                                $color = $colors[$s] ?? 'gray';
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $color }}-100 text-{{ $color }}-800 dark:bg-{{ $color }}-900/30 dark:text-{{ $color }}-200">
                                {{ ucfirst($s) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                            {{ $session->energy_kwh ? number_format($session->energy_kwh, 2) . ' kWh' : '–' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $session->started_at?->format('d/m/Y H:i') ?? '–' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $session->ended_at?->format('d/m/Y H:i') ?? '–' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('charging-sessions.show', $session) }}"
                               class="inline-flex items-center px-3 py-1.5 bg-primary-600 text-white text-xs font-medium rounded hover:bg-primary-700 transition-colors">
                                {{ __('Détails') }}
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if (method_exists($sessions, 'hasPages') && $sessions->hasPages())
            <div class="px-4 py-4 border-t border-gray-200 dark:border-gray-700">{{ $sessions->links() }}</div>
        @endif
        @else
        <div class="p-12 text-center">
            <i class="fas fa-bolt text-3xl text-gray-300 dark:text-gray-600 mb-3"></i>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Aucune session de charge') }}</p>
        </div>
        @endif
    </div>
</div>
@endsection
