@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6 text-gray-800">{{ __('transactions.financial_transactions') }}</h1>
    <div class="bg-white shadow rounded-lg overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('transactions.id') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('transactions.transaction') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('transactions.revenue_share') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('transactions.payer') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('transactions.payee') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('transactions.amount') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('transactions.type') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('transactions.status') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('transactions.description') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('transactions.date') }}</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @forelse ($financialTransactions as $financialTransaction)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-sm text-gray-700">{{ $financialTransaction->id }}</td>
                    <td class="px-4 py-2 text-sm text-gray-700">{{ $financialTransaction->transaction_id }}</td>
                    <td class="px-4 py-2 text-sm text-gray-700">{{ $financialTransaction->revenue_share_id }}</td>
                    <td class="px-4 py-2 text-sm text-gray-700">
                        {{ $financialTransaction->payerAccount->accountable->name ?? __('transactions.not_applicable') }}
                        <span class="text-xs text-gray-400">(ID: {{ $financialTransaction->payer_account_id }})</span>
                    </td>
                    <td class="px-4 py-2 text-sm text-gray-700">
                        {{ $financialTransaction->payeeAccount->accountable->name ?? __('transactions.not_applicable') }}
                        <span class="text-xs text-gray-400">(ID: {{ $financialTransaction->payee_account_id }})</span>
                    </td>
                    <td class="px-4 py-2 text-sm font-semibold text-green-600">
                        {{ number_format($financialTransaction->amount, 2) }}
                    </td>
                    <td class="px-4 py-2">
                        <x-status-badge :status="$financialTransaction->type" />
                    </td>
                    <td class="px-4 py-2">
                        <x-status-badge :status="$financialTransaction->status" />
                    </td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $financialTransaction->description }}</td>
                    <td class="px-4 py-2 text-sm text-gray-400">{{ $financialTransaction->created_at->format('Y-m-d H:i') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center py-4 text-gray-500">
                        {{ __('messages.no_transactions_found') }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection