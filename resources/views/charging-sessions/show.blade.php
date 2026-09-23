@extends('layouts.app')

@section('title', __('Session de Charge') . ' #' . $session->id)
@section('page-title', __('Session de Charge'))

@section('content')
<div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="bg-blue-100 dark:bg-blue-900/30 rounded-lg p-3 mr-4">
                    <i class="fas fa-bolt text-2xl text-blue-600 dark:text-blue-400"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Session #') }}{{ $session->id }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $session->started_at?->format('d/m/Y à H:i') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                @php
                    $s = $session->status ?? 'unknown';
                    $colors = ['active' => 'blue', 'completed' => 'green', 'failed' => 'red', 'cancelled' => 'gray'];
                    $color = $colors[$s] ?? 'gray';
                @endphp
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-{{ $color }}-100 text-{{ $color }}-800 dark:bg-{{ $color }}-900/30 dark:text-{{ $color }}-200">
                    {{ ucfirst($s) }}
                </span>
                <a href="{{ route('charging-sessions.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>{{ __('Retour') }}
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4">{{ __('Détails de la session') }}</h2>
            <dl class="space-y-3">
                @foreach ([
                    [__('Borne'), $session->chargingPoint?->name ?? '–'],
                    [__('Connecteur'), $session->connector_id ?? '–'],
                    [__('Tag OCPP'), $session->id_tag ?? '–'],
                    [__('Énergie consommée'), $session->energy_kwh ? number_format($session->energy_kwh, 3) . ' kWh' : '–'],
                    [__('Durée'), $session->duration_minutes ? $session->duration_minutes . ' min' : '–'],
                    [__('Transaction OCPP'), $session->transaction_id ?? '–'],
                    [__('Début'), $session->started_at?->format('d/m/Y H:i:s') ?? '–'],
                    [__('Fin'), $session->ended_at?->format('d/m/Y H:i:s') ?? '–'],
                ] as [$label, $value])
                <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $value }}</dd>
                </div>
                @endforeach
            </dl>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4">{{ __('Utilisateur') }}</h2>
            @if ($session->user)
            <dl class="space-y-3">
                <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('Nom') }}</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $session->user->name }}</dd>
                </div>
                <div class="flex justify-between py-2">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('Email') }}</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $session->user->email }}</dd>
                </div>
            </dl>
            @else
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Session anonyme') }}</p>
            @endif
        </div>
    </div>
</div>
@endsection
