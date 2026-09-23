@extends('layouts.app')

@section('title', __('Demande de Crédit') . ' #' . $creditRequest->id)
@section('page-title', __('Demande de Crédit'))

@section('content')
<div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="bg-green-100 dark:bg-green-900/30 rounded-lg p-3 mr-4">
                    <i class="fas fa-hand-holding-usd text-2xl text-green-600 dark:text-green-400"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Demande #') }}{{ $creditRequest->id }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $creditRequest->created_at?->format('d/m/Y à H:i') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                @php
                    $st = $creditRequest->status ?? 'pending';
                    $stColors = ['pending' => 'yellow', 'approved' => 'green', 'rejected' => 'red', 'cancelled' => 'gray'];
                    $stColor = $stColors[$st] ?? 'gray';
                @endphp
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-{{ $stColor }}-100 text-{{ $stColor }}-800 dark:bg-{{ $stColor }}-900/30 dark:text-{{ $stColor }}-200">
                    {{ ucfirst($st) }}
                </span>
                <a href="{{ route('credit-requests.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>{{ __('Retour') }}
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4">{{ __('Informations') }}</h2>
            <dl class="space-y-3">
                @foreach ([
                    [__('Montant demandé'), number_format($creditRequest->amount ?? 0, 2) . ' €'],
                    [__('Propriétaire'), $creditRequest->owner?->name ?? '–'],
                    [__('Client'), $creditRequest->client?->name ?? '–'],
                    [__('Borne'), $creditRequest->chargingPoint?->name ?? '–'],
                    [__('Réservation'), $creditRequest->reservation_id ? '#' . $creditRequest->reservation_id : '–'],
                ] as [$label, $value])
                <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $value }}</dd>
                </div>
                @endforeach
            </dl>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4">{{ __('Motif') }}</h2>
            <p class="text-sm text-gray-700 dark:text-gray-300">{{ $creditRequest->reason ?? '–' }}</p>

            @if ($creditRequest->admin_notes)
            <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">{{ __('Notes admin') }}</p>
                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $creditRequest->admin_notes }}</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
