@extends('layouts.app')

@section('title', __('transactions.financial_transactions'))

@section('content')
<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">{{ __('transactions.financial_transactions') }}</h1>

    @if($financialTransactions->isEmpty())
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-900 dark:text-gray-100">
            <p>{{ __('transactions.no_transactions_found') }}</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($financialTransactions as $transaction)
                <x-financial-transaction-card :transaction="$transaction" />
            @endforeach
        </div>

        <div class="mt-6 flex justify-center">
            {{ $financialTransactions->links() }}
        </div>
    @endif
</div>
@endsection