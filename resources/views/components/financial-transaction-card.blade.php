@props(['transaction'])

<div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg h-full flex flex-col">
    <div class="px-6 py-4 bg-gray-100 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('transactions.transaction_id', ['id' => $transaction->id]) }}</h3>
        <span class="inline-flex items-center px-3 py-0.5 rounded-full text-sm font-medium
            {{ $transaction->status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
            {{ __("transactions.status.{$transaction->status}") }}
        </span>
    </div>
    <div class="p-6 flex-grow text-gray-900 dark:text-gray-100">
        <p class="mb-2 text-sm"><strong class="font-semibold">{{ __('transactions.amount') }}:</strong> {{ number_format($transaction->amount, 2) }} {{ $transaction->payerAccount->currency ?? 'EUR' }}</p>
        <p class="mb-2 text-sm"><strong class="font-semibold">{{ __('transactions.type') }}:</strong> {{ __('transactions.types.' . $transaction->type) }}</p>
        <p class="mb-2 text-sm"><strong class="font-semibold">{{ __('transactions.payer') }}:</strong> {{ $transaction->payerAccount->accountable->name ?? __('N/A') }}</p>
        <p class="mb-2 text-sm"><strong class="font-semibold">{{ __('transactions.payee') }}:</strong> {{ $transaction->payeeAccount->accountable->name ?? __('N/A') }}</p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-4">{{ __('transactions.date') }}: {{ $transaction->created_at->format('F j, Y, g:i a') }}</p>
    </div>
    <div class="px-6 py-4 bg-gray-100 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-700 text-center">
        <a href="{{ route('financial_transactions.show', $transaction) }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
            {{ __('transactions.view_details') }}
        </a>
    </div>
</div>