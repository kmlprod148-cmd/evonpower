@extends('layouts.app')

@section('title', __('credits.title'))
@section('page-title', __('credits.page_title'))

@section('content')
<div class="space-y-6 animate-fade-in">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <div class="p-2 rounded-lg shadow-lg" style="background: linear-gradient(135deg, #22c55e, #059669);">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: white;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                {{ __('credits.page_title') }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2 ml-12">{{ __('credits.description') }}</p>
        </div>
    </div>

    <!-- Balance Card - Enhanced -->
    <div class="relative overflow-hidden rounded-2xl shadow-2xl p-8 transform transition-all duration-300 hover:scale-[1.02] hover:shadow-3xl" style="background: linear-gradient(135deg, #22c55e 0%, #10b981 50%, #14b8a6 100%);">
        <div class="absolute top-0 right-0 w-64 h-64 rounded-full -mr-32 -mt-32" style="background: rgba(255,255,255,0.1);"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 rounded-full -ml-24 -mb-24" style="background: rgba(255,255,255,0.05);"></div>
        
        <div class="relative z-10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: white; opacity: 0.9;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm font-medium uppercase tracking-wide" style="color: white; opacity: 0.9;">{{ __('credits.current_balance') }}</p>
                </div>
                <p class="text-5xl font-bold mb-2 tracking-tight" style="color: white;">{{ number_format($balance, 2, ',', ' ') }} <span class="text-2xl" style="color: white; opacity: 0.8;">{{ __('credits.currency') }}</span></p>
                <p class="text-sm flex items-center gap-1" style="color: white; opacity: 0.75;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: white;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ __('credits.updated_realtime') }}
                </p>
            </div>
            <div class="hidden sm:block">
                <div class="w-24 h-24 rounded-full flex items-center justify-center" style="background: rgba(255,255,255,0.2); backdrop-filter: blur(4px);">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: white; opacity: 0.9;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs - Enhanced -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
            <nav class="flex" aria-label="Tabs">
                <button onclick="switchTab('add')" id="tab-add" 
                    class="tab-button active flex-1 px-6 py-4 text-sm font-semibold text-center border-b-2 transition-all duration-300 relative group">
                    <span class="flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        {{ __('credits.add_credit') }}
                    </span>
                    <span class="absolute bottom-0 left-0 right-0 h-0.5 bg-gradient-to-r from-green-500 to-emerald-600 transform scale-x-0 group-[.active]:scale-x-100 transition-transform duration-300"></span>
                </button>
                <button onclick="switchTab('history')" id="tab-history" 
                    class="tab-button flex-1 px-6 py-4 text-sm font-semibold text-center border-b-2 transition-all duration-300 relative group">
                    <span class="flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ __('credits.history') }}
                    </span>
                    <span class="absolute bottom-0 left-0 right-0 h-0.5 bg-gradient-to-r from-green-500 to-emerald-600 transform scale-x-0 group-[.active]:scale-x-100 transition-transform duration-300"></span>
                </button>
            </nav>
        </div>

        <!-- Tab Content: Add Credit -->
        <div id="tab-content-add" class="tab-content p-6 sm:p-8">
            <div class="max-w-2xl mx-auto">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                        <div class="p-1.5 bg-green-100 dark:bg-green-900/30 rounded-lg">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        @if($isAdmin ?? false)
                            {{ __('credits.add_credit_title') }}
                        @else
                            {{ __('credits.request_credit_title') }}
                        @endif
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400">
                        @if($isAdmin ?? false)
                            {{ __('credits.add_credit_description') }}
                        @else
                            {{ __('credits.request_credit_description') }}
                        @endif
                    </p>
                </div>
                
                <form id="add-credit-form" class="space-y-6" action="{{ route('credits.store') }}" method="POST">
                    @csrf
                    
                    @if($isAdmin ?? false)
                    <!-- User Selection (Admin only) -->
                    <div class="space-y-2">
                        <label for="user_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                            {{ __('credits.user') }} <span class="text-red-500">*</span>
                        </label>
                        <select name="user_id" id="user_id" required
                            class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 dark:bg-gray-700 dark:text-white transition-all duration-200 appearance-none bg-white dark:bg-gray-700"
                            onchange="updateUserSelection()">
                            <option value="">{{ __('credits.select_user') }}</option>
                            @foreach($clients ?? [] as $client)
                                <option value="{{ $client->id }}" {{ (request('user_id') == $client->id || ($selectedUserId ?? null) == $client->id) ? 'selected' : '' }}>
                                    {{ $client->name }} ({{ $client->email }})
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ __('credits.select_user_hint') }}
                        </p>
                    </div>
                    @endif
                    
                    <!-- Amount Input -->
                    <div class="space-y-2">
                        <label for="amount" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                            {{ __('credits.amount') }} <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <span class="text-gray-500 dark:text-gray-400 font-medium">€</span>
                            </div>
                            <input type="number" name="amount" id="amount" step="0.01" min="0.01" required
                                class="w-full pl-10 pr-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 dark:bg-gray-700 dark:text-white transition-all duration-200"
                                placeholder="{{ __('credits.amount_placeholder') }}">
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ __('credits.minimum_amount') }}
                        </p>
                    </div>

                    <!-- Type Select -->
                    <div class="space-y-2">
                        <label for="type" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                            {{ __('credits.type') }} <span class="text-red-500">*</span>
                        </label>
                        <select name="type" id="type" required
                            class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 dark:bg-gray-700 dark:text-white transition-all duration-200 appearance-none bg-white dark:bg-gray-700">
                            <option value="manuel">{{ __('credits.type_manual') }}</option>
                            <option value="bonus">{{ __('credits.type_bonus') }}</option>
                            <option value="automatique">{{ __('credits.type_automatic') }}</option>
                        </select>
                    </div>

                    <!-- Description Textarea -->
                    <div class="space-y-2">
                        <label for="commentaire" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                            {{ __('credits.description') }}
                        </label>
                        <textarea name="commentaire" id="commentaire" rows="4"
                            class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 dark:bg-gray-700 dark:text-white transition-all duration-200 resize-none"
                            placeholder="{{ __('credits.description_placeholder') }}"></textarea>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-3 pt-4">
                        <button type="button" onclick="resetForm()"
                            class="px-6 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 font-medium">
                            {{ __('credits.reset') }}
                        </button>
                        <button type="submit"
                            class="px-6 py-3 bg-white text-green-600 rounded-xl hover:bg-gray-50 transition-all duration-200 font-semibold shadow-lg hover:shadow-xl transform hover:scale-105 flex items-center justify-center gap-2 border border-green-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            @if($isAdmin ?? false)
                                {{ __('credits.add_credit_button') }}
                            @else
                                {{ __('credits.submit_request') }}
                            @endif
                        </button>
                    </div>
                </form>

                <!-- Result Message -->
                <div id="form-result" class="mt-6 hidden animate-slide-down"></div>
            </div>
        </div>

        <!-- Tab Content: History -->
        <div id="tab-content-history" class="tab-content hidden p-6 sm:p-8">
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <div class="p-1.5 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        {{ __('credits.credit_history') }}
                    </h2>
                </div>
                
                <!-- Filters -->
                <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="filter_type" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('credits.type') }}
                            </label>
                            <select id="filter_type" onchange="applyFilters()"
                                class="w-full px-4 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                                <option value="">{{ __('credits.all_types') }}</option>
                                <option value="manuel" {{ (request('type') == 'manuel') ? 'selected' : '' }}>{{ __('credits.type_manual') }}</option>
                                <option value="bonus" {{ (request('type') == 'bonus') ? 'selected' : '' }}>{{ __('credits.type_bonus') }}</option>
                                <option value="automatique" {{ (request('type') == 'automatique') ? 'selected' : '' }}>{{ __('credits.type_automatic') }}</option>
                            </select>
                        </div>

                        <div>
                            <label for="filter_date_from" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('credits.date_from') }}
                            </label>
                            <input type="date" id="filter_date_from" onchange="applyFilters()"
                                class="w-full px-4 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 dark:bg-gray-700 dark:text-white transition-all duration-200"
                                value="{{ request('date_from') }}">
                        </div>

                        <div>
                            <label for="filter_date_to" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('credits.date_to') }}
                            </label>
                            <input type="date" id="filter_date_to" onchange="applyFilters()"
                                class="w-full px-4 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 dark:bg-gray-700 dark:text-white transition-all duration-200"
                                value="{{ request('date_to') }}">
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl p-6 border-2 border-green-200 dark:border-green-800 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">{{ __('credits.total_credits_received') }}</p>
                            <svg class="w-8 h-8 text-green-600 dark:text-green-400 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <p class="text-3xl font-bold text-green-600 dark:text-green-400">
                            {{ number_format($statistics['total_credits'] ?? 0, 2, ',', ' ') }} <span class="text-lg">{{ __('credits.currency') }}</span>
                        </p>
                    </div>
                    
                    <div class="bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-xl p-6 border-2 border-blue-200 dark:border-blue-800 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">{{ __('credits.number_of_transactions') }}</p>
                            <svg class="w-8 h-8 text-blue-600 dark:text-blue-400 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                        <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                            {{ $statistics['total_transactions'] ?? 0 }}
                        </p>
                    </div>
                    
                    <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl p-6 border-2 border-purple-200 dark:border-purple-800 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">{{ __('credits.breakdown_by_type') }}</p>
                            <svg class="w-8 h-8 text-purple-600 dark:text-purple-400 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <div class="text-sm font-medium text-gray-700 dark:text-gray-300 space-y-1">
                            @forelse($statistics['by_type'] ?? [] as $type => $total)
                                <div class="flex items-center justify-between">
                                    <span class="capitalize">{{ $type }}</span>
                                    <span class="font-bold">{{ number_format($total, 2, ',', ' ') }} {{ __('credits.currency') }}</span>
                                </div>
                            @empty
                                <div class="text-gray-500 dark:text-gray-400">{{ __('credits.no_data') }}</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Transactions Table -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                        {{ __('credits.date') }}
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                        {{ __('credits.amount') }}
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                        {{ __('credits.type') }}
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                        {{ __('credits.added_by') }}
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                        {{ __('credits.comment') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($creditHistory->items() as $transaction)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $transaction->created_at->format('d/m/Y') }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $transaction->created_at->format('H:i') }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-2">
                                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                                </svg>
                                                <span class="text-sm font-bold text-green-600 dark:text-green-400">
                                                    +{{ number_format($transaction->amount, 2, ',', ' ') }} {{ __('credits.currency') }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold
                                                @if($transaction->type == 'bonus') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300
                                                @elseif($transaction->type == 'automatique') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300
                                                @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
                                                @endif">
                                                @if($transaction->type == 'manuel')
                                                    {{ __('credits.type_manual') }}
                                                @elseif($transaction->type == 'bonus')
                                                    {{ __('credits.type_bonus') }}
                                                @elseif($transaction->type == 'automatique')
                                                    {{ __('credits.type_automatic') }}
                                                @else
                                                    {{ ucfirst($transaction->type) }}
                                                @endif
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white font-medium">
                                            {{ optional($transaction->creator)->name ?? __('credits.system') }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 max-w-xs truncate">
                                            {{ $transaction->commentaire ?? '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-12 text-center">
                                            <div class="flex flex-col items-center justify-center">
                                                <svg class="w-16 h-16 text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                                <p class="text-gray-500 dark:text-gray-400 font-medium">{{ __('credits.no_transactions_found') }}</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($creditHistory->hasPages())
                        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                            {{ $creditHistory->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slide-down {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fade-in 0.5s ease-out;
}

.animate-slide-down {
    animation: slide-down 0.3s ease-out;
}

.tab-button {
    color: #6b7280;
    border-color: transparent;
}

.tab-button.active {
    color: #10b981;
    border-color: #10b981;
}

.tab-button:hover:not(.active) {
    color: #374151;
    background-color: rgba(0, 0, 0, 0.02);
}

.dark .tab-button:hover:not(.active) {
    color: #d1d5db;
    background-color: rgba(255, 255, 255, 0.05);
}

/* Custom select arrow */
select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 0.5rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
    padding-right: 2.5rem;
}
</style>

<script>
function switchTab(tab) {
    // Masquer tous les contenus avec animation
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Désactiver tous les onglets
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active');
    });
    
    // Afficher le contenu sélectionné avec animation
    const selectedContent = document.getElementById('tab-content-' + tab);
    selectedContent.classList.remove('hidden');
    selectedContent.classList.add('animate-fade-in');
    
    // Activer l'onglet sélectionné
    document.getElementById('tab-' + tab).classList.add('active');
}

function resetForm() {
    document.getElementById('add-credit-form').reset();
    document.getElementById('form-result').classList.add('hidden');
}

// Session keep-alive: refresh session periodically (every 4 minutes)
function setupSessionKeepAlive() {
    const sessionKeepaliveInterval = 4 * 60 * 1000; // 4 minutes
    
    setInterval(() => {
        fetch('{{ route("credits.index") }}', {
            method: 'HEAD',
            credentials: 'same-origin'
        }).catch(() => {
            // If session check fails, reload to re-authenticate
            console.log('Session check failed, page will reload');
        });
    }, sessionKeepaliveInterval);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', setupSessionKeepAlive);

function applyFilters() {
    const params = new URLSearchParams();
    
    const type = document.getElementById('filter_type').value;
    const dateFrom = document.getElementById('filter_date_from').value;
    const dateTo = document.getElementById('filter_date_to').value;
    
    @if($isAdmin ?? false)
    const userId = document.getElementById('user_id')?.value;
    if (userId) params.append('user_id', userId);
    @endif
    
    if (type) params.append('type', type);
    if (dateFrom) params.append('date_from', dateFrom);
    if (dateTo) params.append('date_to', dateTo);
    
    window.location.href = '{{ route("credits.index") }}?' + params.toString();
}

@if($isAdmin ?? false)
function updateUserSelection() {
    // Mettre à jour l'URL avec le nouvel utilisateur sélectionné
    applyFilters();
}
@endif

// Gestion du formulaire d'ajout de crédit (demande)
document.getElementById('add-credit-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitButton = this.querySelector('button[type="submit"]');
    const resultDiv = document.getElementById('form-result');
    const originalButtonText = submitButton.innerHTML;
    
    // Get CSRF token and validate it exists
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') 
        || document.querySelector('input[name="_token"]')?.value;
    
    if (!csrfToken) {
        resultDiv.innerHTML = `
            <div class="bg-gradient-to-r from-red-50 to-pink-50 dark:from-red-900/20 dark:to-pink-900/20 border-2 border-red-200 dark:border-red-800 rounded-xl p-4 shadow-lg">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-red-800 dark:text-red-200 font-semibold">{{ __('credits.error_occurred') }}: Token CSRF manquant. Veuillez rafraîchir la page.</p>
                    </div>
                </div>
            </div>
        `;
        resultDiv.classList.remove('hidden');
        return;
    }
    
    // Désactiver le bouton avec animation
    submitButton.disabled = true;
    submitButton.innerHTML = `
        <svg class="animate-spin h-5 w-5 inline-block mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        {{ __('credits.processing') }}
    `;
    
    try {
        const response = await fetch('{{ route("credits.store") }}', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(Object.fromEntries(formData))
        });
        
        // Handle different status codes properly
        if (response.status === 401) {
            // Unauthorized - session expired
            resultDiv.innerHTML = `
                <div class="bg-gradient-to-r from-red-50 to-pink-50 dark:from-red-900/20 dark:to-pink-900/20 border-2 border-red-200 dark:border-red-800 rounded-xl p-4 shadow-lg">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-red-800 dark:text-red-200 font-semibold">Votre session a expiré. Veuillez vous reconnecter.</p>
                        </div>
                    </div>
                </div>
            `;
            resultDiv.classList.remove('hidden');
            // Redirect to login after delay
            setTimeout(() => window.location.href = '{{ route("login") }}', 2000);
            return;
        }
        
        if (response.status === 419) {
            // CSRF token expired - need to refresh and retry
            resultDiv.innerHTML = `
                <div class="bg-gradient-to-r from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 border-2 border-yellow-200 dark:border-yellow-800 rounded-xl p-4 shadow-lg">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-yellow-800 dark:text-yellow-200 font-semibold">Token expiré. Actualisation de la page...</p>
                        </div>
                    </div>
                </div>
            `;
            resultDiv.classList.remove('hidden');
            // Refresh page to get new CSRF token
            setTimeout(() => window.location.reload(), 1500);
            return;
        }
        
        if (response.status === 403) {
            const errorData = await response.json().catch(() => null);
            throw new Error(errorData?.message || 'Accès refusé.');
        }

        if (!response.ok) {
            const errorData = await response.json().catch(() => null);
            throw new Error(errorData?.message || '{{ __('credits.error_occurred') }}');
        }

        const data = await response.json();
        
        if (data.success) {
            resultDiv.innerHTML = `
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-2 border-green-200 dark:border-green-800 rounded-xl p-4 shadow-lg">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-green-800 dark:text-green-200 font-semibold">${data.message}</p>
                        </div>
                    </div>
                </div>
            `;
            resultDiv.classList.remove('hidden');
            
            // Réinitialiser le formulaire
            resetForm();
            
            // Recharger la page après 2 secondes
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            resultDiv.innerHTML = `
                <div class="bg-gradient-to-r from-red-50 to-pink-50 dark:from-red-900/20 dark:to-pink-900/20 border-2 border-red-200 dark:border-red-800 rounded-xl p-4 shadow-lg">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-red-800 dark:text-red-200 font-semibold">${data.message || '{{ __('credits.error_creating_request') }}'}</p>
                        </div>
                    </div>
                </div>
            `;
            resultDiv.classList.remove('hidden');
        }
    } catch (error) {
        resultDiv.innerHTML = `
            <div class="bg-gradient-to-r from-red-50 to-pink-50 dark:from-red-900/20 dark:to-pink-900/20 border-2 border-red-200 dark:border-red-800 rounded-xl p-4 shadow-lg">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-red-800 dark:text-red-200 font-semibold">{{ __('credits.error_occurred') }}: ${error.message}</p>
                    </div>
                </div>
            </div>
        `;
        resultDiv.classList.remove('hidden');
    } finally {
        // Réactiver le bouton
        submitButton.disabled = false;
        submitButton.innerHTML = originalButtonText;
    }
});
</script>
@endsection
