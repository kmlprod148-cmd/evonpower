<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('messages.credits_management') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ __('messages.credits_management') }}</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">{{ __('messages.view_balance_history') }}</p>
            </div>

            <!-- Current Balance -->
            <div class="mb-6 bg-gradient-to-r from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90">{{ __('messages.current_balance') }}</p>
                        <p class="text-3xl font-bold" data-balance-value>{{ number_format($balance, 2, ',', ' ') }} EUR</p>
                    </div>
                    <svg class="w-16 h-16 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>

            <!-- Tabs -->
            <div class="mb-6">
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <nav class="-mb-px flex space-x-8" aria-label="{{ __('messages.navigation') }}">
                        <button onclick="switchTab('history')" id="tab-history" class="tab-button active border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            {{ __('messages.history') }}
                        </button>
                    </nav>
                </div>
            </div>

            <!-- History Tab Content -->
            <div id="tab-content-history" class="tab-content">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">{{ __('messages.credits_history') }}</h2>
                    
                    <!-- Filters -->
                    <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="filter_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('messages.type') }}
                            </label>
                            <select id="filter_type" onchange="applyFilters()"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-gray-700 dark:text-white">
                                <option value="">{{ __('messages.all_types') }}</option>
                                <option value="manuel" {{ (request('type') == 'manuel') ? 'selected' : '' }}>{{ __('messages.manual') }}</option>
                                <option value="bonus" {{ (request('type') == 'bonus') ? 'selected' : '' }}>{{ __('messages.bonus') }}</option>
                                <option value="automatique" {{ (request('type') == 'automatique') ? 'selected' : '' }}>{{ __('messages.automatic') }}</option>
                            </select>
                        </div>

                        <div>
                            <label for="filter_date_from" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('messages.start_date') }}
                            </label>
                            <input type="date" id="filter_date_from" onchange="applyFilters()"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                                value="{{ request('date_from') }}">
                        </div>

                        <div>
                            <label for="filter_date_to" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('messages.end_date') }}
                            </label>
                            <input type="date" id="filter_date_to" onchange="applyFilters()"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                                value="{{ request('date_to') }}">
                        </div>
                    </div>

                    <!-- Statistics -->
                    <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('messages.total_credits_received') }}</p>
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                                {{ number_format($statistics['total_credits'] ?? 0, 2, ',', ' ') }} EUR
                            </p>
                        </div>
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('messages.transactions_count') }}</p>
                            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                {{ $statistics['total_transactions'] ?? 0 }}
                            </p>
                        </div>
                        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 border border-purple-200 dark:border-purple-800">
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('messages.distribution_by_type') }}</p>
                            <div class="text-sm text-gray-700 dark:text-gray-300 mt-1">
                                @forelse($statistics['by_type'] ?? [] as $type => $total)
                                    <div>{{ ucfirst($type) }}: {{ number_format($total, 2, ',', ' ') }} EUR</div>
                                @empty
                                    <div class="text-gray-500 dark:text-gray-400">{{ __('messages.no_data') }}</div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- History Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ __('messages.date') }}
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ __('messages.amount') }}
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ __('messages.type') }}
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ __('messages.added_by') }}
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        {{ __('messages.comment') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($creditHistory->items() as $transaction)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ $transaction->created_at->format('d/m/Y H:i') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600 dark:text-green-400">
                                            +{{ number_format($transaction->amount, 2, ',', ' ') }} EUR
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full
                                                @if($transaction->type == 'bonus') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                                @elseif($transaction->type == 'automatique') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                                @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
                                                @endif">
                                                {{ ucfirst($transaction->type) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            {{ $transaction->creator->name ?? __('messages.system') }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                            {{ $transaction->commentaire ?? '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                            {{ __('messages.no_credit_transaction_found') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($creditHistory->hasPages())
                        <div class="mt-4">
                            {{ $creditHistory->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <style>
    .tab-button.active {
        border-color: #10b981;
        color: #10b981;
    }
    </style>

    <script>
    function switchTab(tab) {
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
        });
        
        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('active');
        });
        
        document.getElementById('tab-content-' + tab).classList.remove('hidden');
        document.getElementById('tab-' + tab).classList.add('active');
    }

    function applyFilters() {
        const params = new URLSearchParams();
        
        const type = document.getElementById('filter_type').value;
        const dateFrom = document.getElementById('filter_date_from').value;
        const dateTo = document.getElementById('filter_date_to').value;
        
        if (type) params.append('type', type);
        if (dateFrom) params.append('date_from', dateFrom);
        if (dateTo) params.append('date_to', dateTo);
        
        window.location.href = '{{ route("credits.client-profile") }}?' + params.toString();
    }
    </script>
</x-app-layout>
