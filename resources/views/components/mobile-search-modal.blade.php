{{-- Mobile Search Modal Premium --}}
<div id="mobileSearchModal" class="evon-mobile-search-modal hidden">
    <div class="evon-mobile-search-content evon-animate-scale-in">
        <div class="evon-mobile-search-header">
            <input type="search" 
                   class="evon-mobile-search-input" 
                   placeholder="{{ __('dashboard.search_placeholder') ?? 'Rechercher...' }}"
                   autocomplete="off"
                   id="mobileSearchInput">
            <button onclick="closeMobileSearch()" 
                    class="evon-mobile-search-close"
                    aria-label="Fermer la recherche">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        
        <div class="evon-mobile-search-results" id="mobileSearchResults">
            {{-- Suggestions rapides --}}
            <div class="mb-4">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">
                    {{ __('dashboard.quick_access') ?? 'Accès rapide' }}
                </p>
                <div class="space-y-2">
                    @can('view-charging-points')
                    <a href="{{ route('charging-points.index') }}" 
                       class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                        <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">{{ __('messages.charging_points') }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('dashboard.manage_stations') ?? 'Gérer les bornes' }}</p>
                        </div>
                    </a>
                    @endcan
                    
                    @can('view-transactions')
                    <a href="{{ route('transactions.index') }}" 
                       class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">{{ __('messages.transactions') }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('dashboard.view_history') ?? 'Voir l\'historique' }}</p>
                        </div>
                    </a>
                    @endcan
                    
                    <a href="{{ route('reservations.index') }}" 
                       class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                        <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">{{ __('messages.reservations') }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('dashboard.my_bookings') ?? 'Mes réservations' }}</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Close mobile search modal
function closeMobileSearch() {
    const searchModal = document.getElementById('mobileSearchModal');
    if (searchModal) {
        searchModal.classList.remove('flex');
        searchModal.classList.add('hidden');
        
        // Re-enable body scroll
        document.body.style.overflow = '';
    }
}

// Close on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeMobileSearch();
    }
});

// Close on backdrop click
document.getElementById('mobileSearchModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeMobileSearch();
    }
});
</script>
