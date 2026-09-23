@props([
    'actions' => []
])

<div x-data="quickActions()" class="relative">
    <!-- Quick Actions Button -->
    <button @click="toggle()"
            @keydown.slash.window.prevent="toggle()"
            class="evon-header-button group relative"
            :class="{ 'ring-2 ring-eco-green-500': isOpen }"
            title="Actions rapides (Appuyez sur /)"
            :aria-expanded="isOpen.toString()">
        
        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 group-hover:text-eco-green-600 dark:group-hover:text-eco-green-400 transition-colors" 
             fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M13 10V3L4 14h7v7l9-11h-7z" />
        </svg>
        
        <!-- Keyboard Shortcut Hint -->
        <span class="absolute -bottom-1 -right-1 px-1 py-0.5 text-[8px] font-bold bg-eco-green-500 text-white rounded shadow-sm">
            /
        </span>
    </button>

    <!-- Command Palette Modal -->
    <div x-show="isOpen"
         x-cloak
         @keydown.escape.window="close()"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity"
             @click="close()"></div>

        <!-- Command Palette -->
        <div class="flex min-h-screen items-start justify-center p-4 sm:p-8">
            <div class="relative w-full max-w-2xl mt-20"
                 @click.away="close()"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95 -translate-y-4"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl ring-1 ring-black ring-opacity-5 overflow-hidden">
                    <!-- Search Input -->
                    <div class="relative border-b border-gray-200 dark:border-gray-700">
                        <svg class="absolute left-4 top-4 w-5 h-5 text-gray-400 pointer-events-none" 
                             fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text"
                               x-ref="searchInput"
                               x-model="searchQuery"
                               @keydown.down.prevent="navigateDown()"
                               @keydown.up.prevent="navigateUp()"
                               @keydown.enter.prevent="executeSelected()"
                               placeholder="Rechercher une action ou taper une commande..."
                               class="w-full h-14 pl-12 pr-4 bg-transparent border-0 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-0 focus:outline-none text-base">
                    </div>

                    <!-- Actions List -->
                    <div class="max-h-[60vh] overflow-y-auto p-2">
                        <template x-if="filteredActions().length > 0">
                            <div>
                                <template x-for="(action, index) in filteredActions()" :key="action.id">
                                    <button @click="execute(action)"
                                            @mouseenter="selectedIndex = index"
                                            class="w-full flex items-center gap-4 px-4 py-3 rounded-lg text-left transition-colors"
                                            :class="index === selectedIndex 
                                                ? 'bg-eco-green-50 dark:bg-eco-green-900/20 ring-2 ring-eco-green-500' 
                                                : 'hover:bg-gray-50 dark:hover:bg-gray-700/50'">
                                        
                                        <!-- Icon -->
                                        <div class="flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center"
                                             :class="action.iconClass || 'bg-gray-100 dark:bg-gray-700'">
                                            <svg class="w-5 h-5" :class="action.iconColor || 'text-gray-600 dark:text-gray-400'" 
                                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                      :d="action.icon" />
                                            </svg>
                                        </div>
                                        
                                        <!-- Content -->
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm font-medium text-gray-900 dark:text-white" 
                                                      x-text="action.title"></span>
                                                <span x-show="action.badge" 
                                                      class="px-2 py-0.5 text-xs font-medium rounded-full"
                                                      :class="action.badgeClass"
                                                      x-text="action.badge"></span>
                                            </div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5" 
                                               x-text="action.description"></p>
                                        </div>
                                        
                                        <!-- Keyboard Shortcut -->
                                        <div x-show="action.shortcut" class="flex-shrink-0 flex items-center gap-1">
                                            <template x-for="key in (action.shortcut || '').split('+')" :key="key">
                                                <kbd class="px-2 py-1 text-xs font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded"
                                                     x-text="key"></kbd>
                                            </template>
                                        </div>
                                    </button>
                                </template>
                            </div>
                        </template>

                        <!-- Empty State -->
                        <div x-show="filteredActions().length === 0" class="px-4 py-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Aucune action trouvée</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Essayez un autre terme de recherche</p>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                            <div class="flex items-center gap-4">
                                <span class="flex items-center gap-1">
                                    <kbd class="px-1.5 py-0.5 font-semibold bg-gray-100 dark:bg-gray-700 rounded">↑</kbd>
                                    <kbd class="px-1.5 py-0.5 font-semibold bg-gray-100 dark:bg-gray-700 rounded">↓</kbd>
                                    naviguer
                                </span>
                                <span class="flex items-center gap-1">
                                    <kbd class="px-1.5 py-0.5 font-semibold bg-gray-100 dark:bg-gray-700 rounded">↵</kbd>
                                    sélectionner
                                </span>
                                <span class="flex items-center gap-1">
                                    <kbd class="px-1.5 py-0.5 font-semibold bg-gray-100 dark:bg-gray-700 rounded">esc</kbd>
                                    fermer
                                </span>
                            </div>
                            <span><span x-text="filteredActions().length"></span> résultats</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function quickActions() {
    return {
        isOpen: false,
        searchQuery: '',
        selectedIndex: 0,
        
        actions: @json($actions),

        init() {
            // Register keyboard shortcuts
            document.addEventListener('keydown', (e) => {
                // Global shortcuts
                this.actions.forEach(action => {
                    if (action.shortcut && this.isShortcutPressed(e, action.shortcut)) {
                        e.preventDefault();
                        this.execute(action);
                    }
                });
            });
        },

        toggle() {
            this.isOpen = !this.isOpen;
            if (this.isOpen) {
                this.$nextTick(() => {
                    this.$refs.searchInput?.focus();
                });
            } else {
                this.searchQuery = '';
                this.selectedIndex = 0;
            }
        },

        close() {
            this.isOpen = false;
            this.searchQuery = '';
            this.selectedIndex = 0;
        },

        filteredActions() {
            if (!this.searchQuery) {
                return this.actions;
            }

            const query = this.searchQuery.toLowerCase();
            return this.actions.filter(action => {
                return action.title.toLowerCase().includes(query) ||
                       action.description.toLowerCase().includes(query) ||
                       (action.tags && action.tags.some(tag => tag.toLowerCase().includes(query)));
            });
        },

        navigateDown() {
            const filtered = this.filteredActions();
            this.selectedIndex = (this.selectedIndex + 1) % filtered.length;
        },

        navigateUp() {
            const filtered = this.filteredActions();
            this.selectedIndex = (this.selectedIndex - 1 + filtered.length) % filtered.length;
        },

        executeSelected() {
            const filtered = this.filteredActions();
            if (filtered[this.selectedIndex]) {
                this.execute(filtered[this.selectedIndex]);
            }
        },

        execute(action) {
            this.close();
            
            if (action.handler && typeof window[action.handler] === 'function') {
                window[action.handler]();
            } else if (action.url) {
                window.location.href = action.url;
            } else if (action.event) {
                window.dispatchEvent(new CustomEvent(action.event, { detail: action.eventData }));
            }

            // Analytics
            if (window.gtag) {
                gtag('event', 'quick_action', {
                    'event_category': 'Navigation',
                    'event_label': action.title
                });
            }
        },

        isShortcutPressed(e, shortcut) {
            const keys = shortcut.toLowerCase().split('+');
            const modifiers = {
                'ctrl': e.ctrlKey,
                'cmd': e.metaKey,
                'alt': e.altKey,
                'shift': e.shiftKey
            };

            let allModifiersMatch = true;
            let keyMatch = false;

            keys.forEach(key => {
                key = key.trim();
                if (modifiers[key] !== undefined) {
                    if (!modifiers[key]) {
                        allModifiersMatch = false;
                    }
                } else {
                    keyMatch = e.key.toLowerCase() === key;
                }
            });

            return allModifiersMatch && keyMatch;
        }
    }
}
</script>

