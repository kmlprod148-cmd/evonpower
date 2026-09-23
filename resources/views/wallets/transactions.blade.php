@extends('layouts.app')

@section('title', __('Transactions du Portefeuille'))
@section('page-title', __('Transactions'))

@section('content')
<div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="bg-blue-100 dark:bg-blue-900/30 rounded-lg p-3 mr-4">
                    <i class="fas fa-exchange-alt text-2xl text-blue-600 dark:text-blue-400"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Historique des Transactions') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('Solde actuel:') }}
                        <span class="font-semibold text-green-600 dark:text-green-400">
                            {{ number_format($wallet->balance ?? 0, 2) }} {{ strtoupper($wallet->currency ?? 'EUR') }}
                        </span>
                    </p>
                </div>
            </div>
            <a href="{{ route('wallets.show', $wallet) }}"
               class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>{{ __('Mon portefeuille') }}
            </a>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        @if ($transactions->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Type') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Description') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Montant') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Solde après') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Date') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($transactions as $tx)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-4">
                                    @php
                                        $typeClass = in_array($tx->type, ['credit', 'deposit', 'refund']) ? 'green' : 'red';
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $typeClass }}-100 text-{{ $typeClass }}-800 dark:bg-{{ $typeClass }}-900/30 dark:text-{{ $typeClass }}-300">
                                        {{ ucfirst($tx->type ?? 'transaction') }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $tx->description ?? '–' }}</td>
                                <td class="px-4 py-4">
                                    <span class="text-sm font-semibold {{ $typeClass === 'green' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ $typeClass === 'green' ? '+' : '-' }}{{ number_format(abs($tx->amount ?? 0), 2) }}
                                        {{ strtoupper($tx->currency ?? 'EUR') }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    {{ number_format($tx->balance_after ?? 0, 2) }} {{ strtoupper($tx->currency ?? 'EUR') }}
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $tx->created_at?->format('d/m/Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($transactions->hasPages())
                <div class="px-4 py-4 border-t border-gray-200 dark:border-gray-700">{{ $transactions->links() }}</div>
            @endif
        @else
            <div class="p-12 text-center">
                <i class="fas fa-inbox text-3xl text-gray-300 dark:text-gray-600 mb-3"></i>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Aucune transaction') }}</p>
            </div>
        @endif
    </div>
</div>
@endsection
