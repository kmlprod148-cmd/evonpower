@extends('layouts.app')

@section('title', __('Paiements en Attente'))
@section('page-title', __('Paiements Espèces en Attente'))

@section('content')
<div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="bg-yellow-100 dark:bg-yellow-900/30 rounded-lg p-3 mr-4">
                    <i class="fas fa-money-bill text-2xl text-yellow-600 dark:text-yellow-400"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Paiements Espèces en Attente') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ count($pendingPayments) }} {{ __('paiement(s) à traiter') }}</p>
                </div>
            </div>
            <a href="{{ route('admin.cash-payments.index') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>{{ __('Tous les paiements') }}
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-400 p-4 rounded-lg">
            <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        @if (count($pendingPayments) > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Client') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Montant') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Date') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($pendingPayments as $payment)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-4 text-sm font-mono text-gray-500 dark:text-gray-400">{{ $payment->id }}</td>
                        <td class="px-4 py-4">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $payment->user?->name ?? '–' }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $payment->user?->email ?? '–' }}</p>
                        </td>
                        <td class="px-4 py-4 text-sm font-semibold text-gray-900 dark:text-white">
                            {{ number_format($payment->amount ?? 0, 2) }} {{ strtoupper($payment->currency ?? 'EUR') }}
                        </td>
                        <td class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $payment->created_at?->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.cash-payments.show', $payment->id) }}"
                                   class="inline-flex items-center px-3 py-1.5 bg-primary-600 text-white text-xs font-medium rounded hover:bg-primary-700 transition-colors">
                                    {{ __('Voir') }}
                                </a>
                                <form method="POST" action="{{ route('admin.cash-payments.process', $payment->id) }}">
                                    @csrf
                                    <button type="submit" onclick="return confirm('{{ __('Traiter ce paiement ?') }}')"
                                            class="inline-flex items-center px-3 py-1.5 bg-green-600 text-white text-xs font-medium rounded hover:bg-green-700 transition-colors">
                                        <i class="fas fa-check mr-1"></i>{{ __('Traiter') }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="p-12 text-center">
            <i class="fas fa-check-circle text-3xl text-gray-300 dark:text-gray-600 mb-3"></i>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Aucun paiement en attente') }}</p>
        </div>
        @endif
    </div>
</div>
@endsection
