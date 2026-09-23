@extends('layouts.app')

@section('title', __('Détails Paiement') . ' #' . $payment->id)
@section('page-title', __('Détails Paiement'))

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="bg-blue-100 dark:bg-blue-900/30 rounded-lg p-3 mr-4">
                    <i class="fas fa-credit-card text-2xl text-blue-600 dark:text-blue-400"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Paiement #') }}{{ $payment->id }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $payment->created_at?->format('d/m/Y à H:i') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                @php
                    $statusColors = ['pending' => 'yellow', 'completed' => 'green', 'failed' => 'red', 'refunded' => 'blue', 'cancelled' => 'gray'];
                    $statusColor = $statusColors[$payment->status ?? 'pending'] ?? 'gray';
                @endphp
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-{{ $statusColor }}-100 text-{{ $statusColor }}-800 dark:bg-{{ $statusColor }}-900/30 dark:text-{{ $statusColor }}-200">
                    {{ ucfirst($payment->status ?? 'pending') }}
                </span>
                <a href="{{ route('admin.payments.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>{{ __('Retour') }}
                </a>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-400 p-4 rounded-lg">
            <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
        </div>
    @endif
    @if (session('error'))
        <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-400 p-4 rounded-lg">
            <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Details -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fas fa-info-circle text-primary-500 mr-2"></i>
                    {{ __('Informations du Paiement') }}
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Montant') }}</label>
                        <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">
                            {{ number_format($payment->amount ?? 0, 2) }} {{ strtoupper($payment->currency ?? 'EUR') }}
                        </p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Méthode') }}</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ ucfirst($payment->payment_method ?? '–') }}</p>
                    </div>
                    @if ($payment->gateway_transaction_id)
                    <div>
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Référence Gateway') }}</label>
                        <p class="mt-1 text-sm font-mono text-gray-900 dark:text-white">{{ $payment->gateway_transaction_id }}</p>
                    </div>
                    @endif
                    @if ($payment->reference)
                    <div>
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Référence') }}</label>
                        <p class="mt-1 text-sm font-mono text-gray-900 dark:text-white">{{ $payment->reference }}</p>
                    </div>
                    @endif
                    @if ($payment->description)
                    <div class="sm:col-span-2">
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Description') }}</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $payment->description }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="space-y-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fas fa-cog text-gray-500 mr-2"></i>
                    {{ __('Actions') }}
                </h2>
                <div class="space-y-2">
                    @if (in_array($payment->status ?? '', ['completed', 'paid']))
                    <form method="POST" action="{{ route('admin.payments.refund', $payment) }}">
                        @csrf
                        <button type="submit"
                                class="w-full inline-flex items-center justify-center px-4 py-2 bg-orange-600 text-white text-sm font-medium rounded-lg hover:bg-orange-700 transition-colors"
                                onclick="return confirm('{{ __('Effectuer un remboursement ?') }}')">
                            <i class="fas fa-undo mr-2"></i>{{ __('Rembourser') }}
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
