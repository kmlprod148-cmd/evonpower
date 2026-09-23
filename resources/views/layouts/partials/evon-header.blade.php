<!-- Safari-Style Desktop Search Bar -->
<div class="evon-header-search hidden md:flex flex-1" x-data="safariSearch()" x-init="initSearch()">
    <!-- Safari-style Search Container -->
    <div class="relative w-full max-w-2xl mx-auto">
        <!-- Main Search Input - Safari Style -->
        <div class="safari-search-container relative group">
            <!-- Search Icon (Left) -->
            <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                <svg class="w-4 h-4 text-gray-400 group-focus-within:text-indigo-500 transition-colors duration-200" 
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="m21 21-4.3-4.3"/>
                </svg>
            </div>
            
            <!-- Search Input -->
            <input
                type="search"
                x-ref="searchInput"
                placeholder="{{ __('messages.search_placeholder_full') }}"
                class="safari-search-input w-full h-10 pl-10 pr-20 bg-gray-100/80 dark:bg-gray-800/80 backdrop-blur-sm border border-transparent dark:border-gray-700 rounded-full text-sm text-gray-700 dark:text-gray-200 placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:bg-white dark:focus:bg-gray-700 focus:border-indigo-300 dark:focus:border-indigo-600 focus:ring-2 focus:ring-indigo-200 dark:focus:ring-indigo-800/50 transition-all duration-300 shadow-sm hover:shadow-md hover:bg-gray-200/80 dark:hover:bg-gray-700/80"
                aria-label="{{ __('messages.search_in_app') }}"
                autocomplete="off"
                x-model="query"
                @input.debounce.300ms="performSearch()"
                @keydown.escape="clearSearch()"
                @keydown.down.prevent="navigateResults(1)"
                @keydown.up.prevent="navigateResults(-1)"
                @keydown.enter.prevent="selectResult()"
                @focus="handleFocus()"
                @blur="handleBlur()"
            />
            
            <!-- Clear Button (Right) - Shows when there's text -->
            <button 
                x-show="query.length > 0"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-50"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-50"
                @click="clearSearch()"
                class="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors duration-200 focus:outline-none"
                aria-label="{{ __('messages.clear_search') }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M15 9l-6 6M9 9l6 6"/>
                </svg>
            </button>
            
            <!-- Loading Spinner (Safari Style) -->
            <div 
                x-show="loading"
                x-cloak
                class="absolute inset-y-0 right-0 flex items-center pr-4">
                <div class="safari-spinner w-4 h-4"></div>
            </div>
        </div>
        
        <!-- Search Results Dropdown - Safari Style -->
        <div 
            x-show="showDropdown"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            class="safari-search-dropdown absolute top-12 left-0 right-0 bg-white/95 dark:bg-gray-800/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-gray-200/50 dark:border-gray-700/50 overflow-hidden z-50 max-h-[480px] overflow-y-auto">
            
            <!-- Recent Searches Section -->
            <template x-if="!loading && query.length === 0 && recentSearches.length > 0">
                <div class="recent-searches-section">
                    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('messages.recent_searches') }}
                        </span>
                        <button 
                            @click="clearRecentSearches()"
                            class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 transition-colors">
                            {{ __('messages.clear_all') }}
                        </button>
                    </div>
                    <ul class="py-2">
                        <template x-for="(search, index) in recentSearches" :key="index">
                            <li>
                                <button 
                                    @click="useRecentSearch(search)"
                                    class="w-full flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors text-left">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span class="text-sm text-gray-700 dark:text-gray-200" x-text="search"></span>
                                </button>
                            </li>
                        </template>
                    </ul>
                </div>
            </template>
            
            <!-- Search Results -->
            <template x-if="!loading && results.length > 0">
                <div class="search-results-section">
                    <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('messages.results') }}
                        </span>
                    </div>
                    <ul class="py-2">
                        <template x-for="(result, index) in results" :key="result.id">
                            <li>
                                <a 
                                    :href="result.url"
                                    :class="{'bg-indigo-50 dark:bg-indigo-900/20': selectedIndex === index}"
                                    class="flex items-center gap-4 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-all duration-150 group"
                                    @mouseenter="selectedIndex = index">
                                    <!-- Type Icon -->
                                    <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-100 to-purple-100 dark:from-indigo-900/30 dark:to-purple-900/30 flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                                        <!-- Charging Point Icon -->
                                        <template x-if="result.type === 'charging_point'">
                                            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                            </svg>
                                        </template>
                                        <!-- Reservation Icon -->
                                        <template x-if="result.type === 'reservation'">
                                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </template>
                                        <!-- Transaction Icon -->
                                        <template x-if="result.type === 'transaction'">
                                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                            </svg>
                                        </template>
                                        <!-- Client Icon -->
                                        <template x-if="result.type === 'client'">
                                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                            </svg>
                                        </template>
                                        <!-- OCPP Tag Icon -->
                                        <template x-if="result.type === 'ocpp_tag'">
                                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                            </svg>
                                        </template>
                                    </div>
                                    <!-- Result Info -->
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white truncate" x-text="result.title"></p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate" x-text="result.subtitle"></p>
                                    </div>
                                    <!-- Arrow Icon -->
                                    <svg class="w-4 h-4 text-gray-400 group-hover:text-indigo-500 dark:group-hover:text-indigo-400 transition-colors opacity-0 group-hover:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/>
                                    </svg>
                                </a>
                            </li>
                        </template>
                    </ul>
                </div>
            </template>
            
            <!-- Loading State - Safari Style -->
            <template x-if="loading">
                <div class="safari-loading-state px-4 py-8">
                    <div class="flex flex-col items-center gap-3">
                        <div class="safari-loading-spinner"></div>
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('messages.searching') }}</span>
                    </div>
                </div>
            </template>
            
            <!-- No Results State -->
            <template x-if="!loading && query.length >= 2 && results.length === 0">
                <div class="no-results-state px-4 py-8">
                    <div class="flex flex-col items-center gap-3">
                        <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                            <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <circle cx="11" cy="11" r="8"/>
                                <path d="m21 21-4.3-4.3"/>
                                <path d="M8 8l6 6M14 8l-6 6"/>
                            </svg>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('messages.no_results_found') }}</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500" x-text="'{{ __('messages.try_different_search') }}'"></p>
                    </div>
                </div>
            </template>
            
            <!-- Empty State - Start Typing -->
            <template x-if="!loading && query.length === 0 && recentSearches.length === 0">
                <div class="empty-state px-4 py-8">
                    <div class="flex flex-col items-center gap-3">
                        <svg class="w-12 h-12 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.3-4.3"/>
                        </svg>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('messages.start_typing_search') }}</p>
                    </div>
                </div>
            </template>
        </div>
        
        <!-- Backdrop for dropdown -->
        <div 
            x-show="showDropdown"
            x-cloak
            @click="clearSearch()"
            class="fixed inset-0 z-40"
            aria-hidden="true">
        </div>
    </div>
</div>

<style>
/* Safari-Style Search Input Enhancements */
.safari-search-input {
    font-size: 14px;
    letter-spacing: -0.01em;
}

.safari-search-input::placeholder {
    color: #9ca3af;
    transition: color 0.2s ease;
}

.safari-search-input:focus::placeholder {
    color: #d1d5db;
}

/* Safari Spinner Animation */
.safari-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid #e5e7eb;
    border-top-color: #6366f1;
    border-radius: 50%;
    animation: safari-spin 0.8s linear infinite;
}

@keyframes safari-spin {
    to {
        transform: rotate(360deg);
    }
}

/* Safari Loading Spinner - Larger */
.safari-loading-spinner {
    width: 32px;
    height: 32px;
    border: 3px solid #e5e7eb;
    border-top-color: #6366f1;
    border-radius: 50%;
    animation: safari-loading-spin 1s linear infinite;
}

@keyframes safari-loading-spin {
    to {
        transform: rotate(360deg);
    }
}

/* Safari Search Dropdown Styling */
.safari-search-dropdown {
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}

/* Smooth scrollbar */
.safari-search-dropdown::-webkit-scrollbar {
    width: 6px;
}

.safari-search-dropdown::-webkit-scrollbar-track {
    background: transparent;
}

.safari-search-dropdown::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 3px;
}

.safari-search-dropdown::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}

.dark .safari-search-dropdown::-webkit-scrollbar-thumb {
    background: #4b5563;
}

.dark .safari-search-dropdown::-webkit-scrollbar-thumb:hover {
    background: #6b7280;
}

/* Dropdown shadow */
.safari-search-dropdown {
    box-shadow: 
        0 0 0 1px rgba(0, 0, 0, 0.05),
        0 4px 6px -1px rgba(0, 0, 0, 0.1),
        0 2px 4px -1px rgba(0, 0, 0, 0.06),
        0 10px 15px -3px rgba(0, 0, 0, 0.1),
        0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

/* Focus ring animation */
.safari-search-input:focus {
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
}

/* RTL Support */
[dir="rtl"] .safari-search-container input {
    padding-left: 5rem;
    padding-right: 5rem;
}

[dir="rtl"] .safari-search-container .safari-search-icon {
    left: auto;
    right: 1rem;
}

[dir="rtl"] .safari-search-container button {
    left: 1rem;
    right: auto;
}

/* Responsive */
@media (max-width: 768px) {
    .safari-search-container {
        max-width: 100%;
    }
}
</style>

<script>
function safariSearch() {
    return {
        query: '',
        results: [],
        recentSearches: [],
        loading: false,
        isFocused: false,
        selectedIndex: -1,
        searchTimeout: null,
        
        initSearch() {
            // Load recent searches from localStorage
            const stored = localStorage.getItem('evon_recent_searches');
            if (stored) {
                this.recentSearches = JSON.parse(stored);
            }
        },
        
        get showDropdown() {
            return this.isFocused && (this.results.length > 0 || this.loading || this.query.length === 0);
        },
        
        handleFocus() {
            this.isFocused = true;
            this.$refs.searchInput.focus();
        },
        
        handleBlur() {
            // Delay to allow click on results
            setTimeout(() => {
                if (!this.$refs.searchInput.matches(':focus')) {
                    this.isFocused = false;
                }
            }, 200);
        },
        
        performSearch() {
            // Clear previous timeout
            if (this.searchTimeout) {
                clearTimeout(this.searchTimeout);
            }
            
            // Reset selection
            this.selectedIndex = -1;
            
            // Minimum 2 characters required
            if (this.query.length < 2) {
                this.results = [];
                return;
            }
            
            // Save to recent searches
            this.saveToRecentSearches(this.query);
            
            // Debounce search requests
            this.searchTimeout = setTimeout(() => {
                this.loading = true;
                
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') 
                    || document.querySelector('input[name="_token"]')?.value;
                
                const url = '{{ route("global-search") }}?q=' + encodeURIComponent(this.query);
                
                fetch(url, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    this.results = data.results || [];
                    this.loading = false;
                })
                .catch(error => {
                    console.error('Search error:', error);
                    this.loading = false;
                    this.results = [];
                });
            }, 300);
        },
        
        saveToRecentSearches(searchQuery) {
            // Don't save empty or very short queries
            if (searchQuery.length < 2) return;
            
            // Remove if already exists
            this.recentSearches = this.recentSearches.filter(s => s !== searchQuery);
            
            // Add to beginning
            this.recentSearches.unshift(searchQuery);
            
            // Keep only last 5
            this.recentSearches = this.recentSearches.slice(0, 5);
            
            // Save to localStorage
            localStorage.setItem('evon_recent_searches', JSON.stringify(this.recentSearches));
        },
        
        useRecentSearch(search) {
            this.query = search;
            this.performSearch();
        },
        
        clearRecentSearches() {
            this.recentSearches = [];
            localStorage.removeItem('evon_recent_searches');
        },
        
        clearSearch() {
            this.query = '';
            this.results = [];
            this.isFocused = false;
            this.selectedIndex = -1;
            this.$refs.searchInput.blur();
        },
        
        navigateResults(direction) {
            if (this.results.length === 0) return;
            
            this.selectedIndex += direction;
            
            if (this.selectedIndex < 0) {
                this.selectedIndex = this.results.length - 1;
            } else if (this.selectedIndex >= this.results.length) {
                this.selectedIndex = 0;
            }
        },
        
        selectResult() {
            if (this.selectedIndex >= 0 && this.results[this.selectedIndex]) {
                window.location.href = this.results[this.selectedIndex].url;
            }
        }
    };
}
</script>

<!-- Mobile Search Component - Hidden on Desktop -->
<div x-data="{ mobileSearchOpen: false }" class="mobile-search-component flex-shrink-0">
    <!-- Mobile Search Button (visible only on mobile/tablet) -->
    <button @click="mobileSearchOpen = true" 
            class="evon-header-button mobile-search-trigger" 
            aria-label="{{ __('messages.search') }}"
            aria-haspopup="dialog">
        @include('components.icons.lucide-search', ['size' => 18, 'class' => 'text-gray-600 dark:text-gray-400'])
    </button>

    <!-- Mobile Search Modal -->
    <div x-show="mobileSearchOpen"
         x-cloak
         @keydown.escape.window="mobileSearchOpen = false"
         class="fixed inset-0 z-[999] bg-white dark:bg-gray-900"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <div class="flex flex-col h-full">
            <!-- Header -->
            <div class="flex items-center gap-3 p-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-gray-800 dark:to-gray-800">
                <button @click="mobileSearchOpen = false" 
                        class="flex-shrink-0 p-2 rounded-xl bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 transition-all duration-200 active:scale-95"
                        aria-label="{{ __('messages.close_search') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                </button>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('messages.search') }}</h2>
            </div>

            <!-- Search Input -->
            <div class="p-4">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.3-4.3"></path>
                        </svg>
                    </div>
                    <input
                        x-ref="mobileSearchInput"
                        type="search"
                        placeholder="{{ __('messages.what_are_you_looking_for') }}"
                        class="w-full pl-12 pr-4 py-4 text-base bg-gray-100 dark:bg-gray-800 border-2 border-transparent focus:border-indigo-500 dark:focus:border-indigo-400 rounded-2xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 transition-all duration-200 focus:outline-none focus:ring-4 focus:ring-indigo-500/20"
                        aria-label="{{ __('messages.search_in_app') }}"
                        autocomplete="off"
                        x-init="$watch('mobileSearchOpen', value => { 
                            if(value) {
                                setTimeout(() => {
                                    $refs.mobileSearchInput.focus();
                                }, 100);
                            }
                        })"
                    />
                </div>
            </div>

            <!-- Search Results / Suggestions -->
            <div class="flex-1 overflow-y-auto px-4 pb-4">
                <!-- Quick Actions -->
                <div class="mb-6">
                    <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">{{ __('messages.quick_actions') }}</h3>
                    <div class="space-y-2">
                        <a href="{{ route('charging-points.index') }}" 
                           class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 dark:bg-gray-800 hover:bg-indigo-50 dark:hover:bg-gray-700 transition-all duration-200 group">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-indigo-600 dark:text-indigo-400">
                                    <path d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ __('messages.charging_points') }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('messages.manage_stations') }}</p>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                <path d="m9 18 6-6-6-6"/>
                            </svg>
                        </a>

                        <a href="{{ route('reservations.index') }}" 
                           class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 dark:bg-gray-800 hover:bg-green-50 dark:hover:bg-gray-700 transition-all duration-200 group">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-green-600 dark:text-green-400">
                                    <path d="M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ __('messages.reservations') }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('messages.view_all_reservations') }}</p>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400 group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors">
                                <path d="m9 18 6-6-6-6"/>
                            </svg>
                        </a>

                        <a href="{{ route('transactions.index') }}" 
                           class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 dark:bg-gray-800 hover:bg-purple-50 dark:hover:bg-gray-700 transition-all duration-200 group">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-purple-600 dark:text-purple-400">
                                    <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ __('messages.transactions') }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('messages.payment_history') }}</p>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">
                                <path d="m9 18 6-6-6-6"/>
                            </svg>
                        </a>

                        <a href="{{ route('profile.edit') }}" 
                           class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 dark:bg-gray-800 hover:bg-amber-50 dark:hover:bg-gray-700 transition-all duration-200 group">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-amber-600 dark:text-amber-400">
                                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ __('messages.profile') }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('messages.account_settings') }}</p>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gray-400 group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">
                                <path d="m9 18 6-6-6-6"/>
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Search Tips -->
                <div class="mt-8 p-4 rounded-xl bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-gray-800 dark:to-gray-800 border border-indigo-100 dark:border-gray-700">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-indigo-600 dark:text-indigo-400">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M12 16v-4M12 8h.01"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white mb-1">{{ __('messages.search_tip') }}</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">{{ __('messages.search_tip_text') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Utilities (Right in LTR, Left in RTL) - Optimized for Mobile -->
<div class="evon-header-utilities" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
        <!-- Language Switcher - FIXED -->
        <div class="flex-shrink-0">
            <x-language-switch-fixed />
        </div>

        <!-- Theme toggle -->
        <div class="flex-shrink-0">
            <x-header.theme-switcher :compact="true" />
        </div>

        <!-- Notifications -->
        @if(auth()->check())
            <div x-data="{ notificationOpen: false }" class="relative flex-shrink-0" @click.away="notificationOpen = false">
                <button @click.stop="notificationOpen = !notificationOpen"
                        class="evon-header-button relative" 
                        title="Notifications"
                        aria-label="{{ __('messages.view_notifications') }}"
                        :aria-expanded="notificationOpen.toString()"
                        @if(auth()->user()->unreadNotifications->count() > 0)
                            aria-describedby="notification-count"
                        @endif>
                    @include('components.icons.lucide-bell', ['size' => 16, 'class' => 'text-gray-600 dark:text-gray-400'])
                    @if(auth()->user()->unreadNotifications->count() > 0)
                        <span class="evon-notification-badge" 
                              id="notification-count"
                              aria-label="{{ auth()->user()->unreadNotifications->count() }} {{ __('messages.new_notifications') }}"></span>
                    @endif
                </button>
                
                <!-- Notifications Dropdown -->
                <div x-show="notificationOpen"
                     x-cloak
                     @click.away="notificationOpen = false"
                     @keydown.escape.window="notificationOpen = false"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="absolute {{ app()->getLocale() === 'ar' ? 'left-0' : 'right-0' }} mt-3 w-80 sm:w-96 origin-top-right z-50"
                     dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
                     
                    <div class="rounded-xl bg-white dark:bg-gray-800 shadow-2xl ring-1 ring-black ring-opacity-5 overflow-hidden max-h-[480px] flex flex-col">
                        <!-- Header -->
                        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('messages.notifications') }}</h3>
                                @if(auth()->user()->unreadNotifications->count() > 0)
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-eco-green-500 text-white">
                                        {{ auth()->user()->unreadNotifications->count() }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Notifications List -->
                        <div class="overflow-y-auto flex-1">
                            @forelse(auth()->user()->notifications->take(5) as $notification)
                                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer {{ $notification->read_at ? 'opacity-60' : '' }}">
                                    <div class="flex items-start gap-3">
                                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-eco-green-100 dark:bg-eco-green-900/30 flex items-center justify-center">
                                            @include('components.icons.lucide-bell', ['size' => 14, 'class' => 'text-eco-green-600 dark:text-eco-green-400'])
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm text-gray-900 dark:text-white font-medium">
                                                {{ $notification->data['title'] ?? 'Notification' }}
                                            </p>
                                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">
                                                {{ $notification->data['message'] ?? '' }}
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                                {{ $notification->created_at->diffForHumans() }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="px-4 py-8 text-center">
                                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-700 mb-3">
                                        @include('components.icons.lucide-bell', ['size' => 24, 'class' => 'text-gray-400'])
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('messages.no_notifications') }}</p>
                                </div>
                            @endforelse
                        </div>

                        <!-- Footer -->
                        @if(auth()->user()->notifications->count() > 0)
                            <div class="px-4 py-2 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                                <button @click="notificationOpen = false" 
                                        class="w-full text-center text-sm font-medium text-eco-green-600 dark:text-eco-green-400 hover:text-eco-green-700 dark:hover:text-eco-green-300 transition-colors py-1">
                                    {{ __('messages.close') }}
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- User profile -->
        @if(auth()->check())
            <div class="flex-shrink-0">
                <x-header.user-menu />
            </div>
        @endif
    </div>
