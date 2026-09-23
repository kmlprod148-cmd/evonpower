@props(['user', 'stats' => []])

<div class="relative bg-gradient-to-br from-indigo-600 via-blue-600 to-purple-700 dark:from-indigo-900 dark:via-blue-900 dark:to-purple-900 overflow-hidden">
    <!-- Grid pattern overlay -->
    <div class="absolute inset-0 bg-grid-overlay pointer-events-none"></div>

    <div class="relative px-4 sm:px-6 lg:px-8 py-8">
        <div class="max-w-7xl mx-auto">
            <!-- Top row: Welcome & Actions -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6 mb-8">
                <!-- Welcome section -->
                <div class="flex items-center gap-4">
                    <!-- Avatar -->
                    <div class="relative">
                        <div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-sm border-2 border-white/30 flex items-center justify-center text-2xl font-bold text-white shadow-lg">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </div>
                        <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-green-400 border-2 border-white rounded-full animate-pulse"></div>
                    </div>

                    <!-- Welcome text -->
                    <div>
                        <h1 class="text-3xl sm:text-4xl font-bold text-white mb-1 tracking-tight">
                            {{ __('dashboard.welcome') }}, {{ explode(' ', $user->name)[0] }} 👋
                        </h1>
                        <p class="text-blue-100 text-sm sm:text-base flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ now()->isoFormat('dddd D MMMM YYYY, HH:mm') }}
                        </p>
                    </div>
                </div>

                <!-- Action buttons -->
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Notifications -->
                    <button class="relative group">
                        <div class="flex items-center gap-2 px-4 py-2.5 bg-white/10 hover:bg-white/20 backdrop-blur-sm border border-white/20 rounded-xl text-white transition-all duration-300 group-hover:scale-105">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <span class="hidden sm:inline font-medium">3</span>
                        </div>
                        <span class="absolute top-0 right-0 flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500 border-2 border-white"></span>
                        </span>
                    </button>

                    <!-- Quick Actions Dropdown -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center gap-2 px-4 py-2.5 bg-white/10 hover:bg-white/20 backdrop-blur-sm border border-white/20 rounded-xl text-white transition-all duration-300 hover:scale-105">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            <span class="hidden sm:inline font-medium">{{ __('dashboard.quick_actions') }}</span>
                            <svg class="w-4 h-4" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="open" 
                             @click.away="open = false"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 transform scale-95"
                             x-transition:enter-end="opacity-100 transform scale-100"
                             class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden z-50">
                            <div class="py-2">
                                <a href="{{ route('charging-points.create') }}" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                    </div>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('dashboard.add_charging_point') }}</span>
                                </a>
                                <a href="{{ route('reservations.index') }}" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <div class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                                        <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                        </svg>
                                    </div>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('dashboard.view_reservations') }}</span>
                                </a>
                                <a href="{{ route('transactions.index') }}" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                                        <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                        </svg>
                                    </div>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('dashboard.transactions') }}</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Refresh button -->
                    <button onclick="refreshDashboard()" class="group relative overflow-hidden px-5 py-2.5 bg-white hover:bg-gray-50 text-blue-600 font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105">
                        <div class="absolute inset-0 bg-gradient-to-r from-blue-600 to-purple-600 opacity-0 group-hover:opacity-10 transition-opacity"></div>
                        <div class="relative flex items-center gap-2">
                            <svg class="w-5 h-5 transition-transform group-hover:rotate-180 duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <span class="hidden sm:inline">{{ __('dashboard.refresh') }}</span>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Stats cards row -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Online Points -->
                <div class="group bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-5 hover:bg-white/20 transition-all duration-300 hover:scale-105 cursor-pointer">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-12 h-12 bg-green-400/20 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6 text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="flex items-center gap-1 text-green-300 text-xs font-semibold bg-green-400/20 px-2 py-1 rounded-full">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                            {{ $stats['availabilityRate'] ?? 0 }}%
                        </div>
                    </div>
                    <div class="text-white/70 text-sm font-medium mb-1">{{ __('dashboard.online_points') }}</div>
                    <div class="text-3xl font-bold text-white">{{ $stats['onlinePoints'] ?? 0 }}</div>
                    <div class="text-white/50 text-xs mt-1">/ {{ $stats['totalPoints'] ?? 0 }} {{ __('dashboard.total') }}</div>
                </div>

                <!-- Active Sessions -->
                <div class="group bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-5 hover:bg-white/20 transition-all duration-300 hover:scale-105 cursor-pointer">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-12 h-12 bg-blue-400/20 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <div class="relative flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-300 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-blue-400"></span>
                        </div>
                    </div>
                    <div class="text-white/70 text-sm font-medium mb-1">{{ __('dashboard.active_sessions') }}</div>
                    <div class="text-3xl font-bold text-white">{{ $stats['activeTransactions'] ?? 0 }}</div>
                    <div class="text-white/50 text-xs mt-1">{{ __('dashboard.charging_now') }}</div>
                </div>

                <!-- Today Revenue -->
                <div class="group bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-5 hover:bg-white/20 transition-all duration-300 hover:scale-105 cursor-pointer">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-12 h-12 bg-amber-400/20 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                            </svg>
                        </div>
                        <div class="text-amber-300 text-xs font-semibold">{{ __('dashboard.today') }}</div>
                    </div>
                    <div class="text-white/70 text-sm font-medium mb-1">{{ __('dashboard.revenue') }}</div>
                    <div class="text-2xl font-bold text-white">{{ number_format($stats['todayRevenue'] ?? 0, 0) }} MAD</div>
                    <div class="text-white/50 text-xs mt-1">{{ $stats['todayTransactions'] ?? 0 }} {{ __('dashboard.transactions') }}</div>
                </div>

                <!-- Energy Distributed -->
                <div class="group bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-5 hover:bg-white/20 transition-all duration-300 hover:scale-105 cursor-pointer">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-12 h-12 bg-purple-400/20 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6 text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"/>
                            </svg>
                        </div>
                        <div class="text-purple-300 text-xs font-semibold">{{ __('dashboard.today') }}</div>
                    </div>
                    <div class="text-white/70 text-sm font-medium mb-1">{{ __('dashboard.energy') }}</div>
                    <div class="text-2xl font-bold text-white">{{ number_format($stats['todayEnergy'] ?? 0, 1) }} kWh</div>
                    <div class="text-white/50 text-xs mt-1">{{ __('dashboard.distributed') }}</div>
                </div>
            </div>

            <!-- Navigation tabs -->
            <div class="mt-8 flex flex-wrap gap-2">
                <a href="{{ route('dashboard.enhanced') }}" class="px-4 py-2 bg-white/20 backdrop-blur-sm border border-white/30 rounded-lg text-white font-medium hover:bg-white/30 transition-all duration-200 hover:scale-105">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        {{ __('dashboard.overview') }}
                    </span>
                </a>
                <a href="{{ route('charging-points.index') }}" class="px-4 py-2 bg-white/10 backdrop-blur-sm border border-white/20 rounded-lg text-white/80 font-medium hover:bg-white/20 hover:text-white transition-all duration-200 hover:scale-105">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        {{ __('dashboard.charging_points') }}
                    </span>
                </a>
                <a href="{{ route('transactions.index') }}" class="px-4 py-2 bg-white/10 backdrop-blur-sm border border-white/20 rounded-lg text-white/80 font-medium hover:bg-white/20 hover:text-white transition-all duration-200 hover:scale-105">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        {{ __('dashboard.transactions') }}
                    </span>
                </a>
                <a href="{{ route('reservations.index') }}" class="px-4 py-2 bg-white/10 backdrop-blur-sm border border-white/20 rounded-lg text-white/80 font-medium hover:bg-white/20 hover:text-white transition-all duration-200 hover:scale-105">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        {{ __('dashboard.reservations') }}
                    </span>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.bg-grid-overlay {
    background-image:
        linear-gradient(rgba(255, 255, 255, 0.08) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255, 255, 255, 0.08) 1px, transparent 1px);
    background-size: 20px 20px;
}
</style>

@if(!isset($Alpine))
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endif

