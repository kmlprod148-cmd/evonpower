@props([
    'showSearch' => true,
    'showNotifications' => true,
    'showQuickActions' => true,
    'showThemeSwitcher' => true,
    'showLanguageSwitcher' => true,
    'compact' => false
])

<header class="evon-header" role="banner" x-data="advancedHeader()">
    <div class="flex items-center justify-between w-full">
        <!-- Left Section: Mobile Menu + Search -->
        <div class="flex items-center gap-3 flex-1">
            <!-- Mobile Menu Button -->
            <button @click="$dispatch('toggle-sidebar')" 
                    class="evon-mobile-menu-btn"
                    aria-label="Ouvrir le menu">
                <svg class="h-6 w-6 text-gray-600 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <!-- Search Bar (Desktop) -->
            @if($showSearch)
            <div class="hidden lg:block flex-1 max-w-2xl">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="search"
                           @click="openQuickActions()"
                           @focus="openQuickActions()"
                           readonly
                           placeholder="Rechercher ou taper une commande..."
                           class="evon-header-search-input cursor-pointer"
                           aria-label="Rechercher">
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                        <kbd class="hidden sm:inline-block px-2 py-1 text-xs font-semibold text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded">
                            /
                        </kbd>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Right Section: Utilities -->
        <div class="evon-header-utilities">
            <!-- Language Switcher -->
            @if($showLanguageSwitcher)
            <div class="hidden sm:block">
                @if(View::exists('components.direct-language-switcher'))
                    @include('components.direct-language-switcher')
                @else
                    <button class="evon-header-button" 
                            title="Changer la langue">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                        </svg>
                    </button>
                @endif
            </div>
            @endif

            <!-- Quick Actions -->
            @if($showQuickActions)
            <div class="hidden md:block">
                <x-header.quick-actions :actions="[
                    [
                        'id' => 'create-charging-point',
                        'title' => 'Nouvelle Borne',
                        'description' => 'Ajouter une nouvelle borne de recharge',
                        'icon' => 'M13 10V3L4 14h7v7l9-11h-7z',
                        'iconClass' => 'bg-eco-green-100 dark:bg-eco-green-900/30',
                        'iconColor' => 'text-eco-green-600 dark:text-eco-green-400',
                        'url' => route('charging-points.create'),
                        'shortcut' => 'ctrl+n',
                        'badge' => 'Nouveau',
                        'badgeClass' => 'bg-green-100 text-green-700',
                        'tags' => ['borne', 'créer', 'nouveau']
                    ],
                    [
                        'id' => 'view-transactions',
                        'title' => 'Transactions',
                        'description' => 'Voir toutes les transactions',
                        'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
                        'iconClass' => 'bg-blue-100 dark:bg-blue-900/30',
                        'iconColor' => 'text-blue-600 dark:text-blue-400',
                        'url' => route('transactions.index'),
                        'shortcut' => 'ctrl+t',
                        'tags' => ['transactions', 'paiements']
                    ],
                    [
                        'id' => 'dashboard',
                        'title' => 'Tableau de Bord',
                        'description' => 'Retour à l\'accueil',
                        'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
                        'iconClass' => 'bg-purple-100 dark:bg-purple-900/30',
                        'iconColor' => 'text-purple-600 dark:text-purple-400',
                        'url' => route('dashboard'),
                        'shortcut' => 'ctrl+h',
                        'tags' => ['accueil', 'dashboard']
                    ]
                ]" />
            </div>
            @endif

            <!-- Theme Switcher -->
            @if($showThemeSwitcher)
            <x-header.theme-switcher :compact="$compact" />
            @endif

            <!-- Notifications -->
            @if($showNotifications && auth()->check())
            <x-header.notification-center 
                :notifications="auth()->user()->notifications()->latest()->take(10)->get()->map(function($notif) {
                    return [
                        'id' => $notif->id,
                        'title' => $notif->data['title'] ?? 'Notification',
                        'message' => $notif->data['message'] ?? '',
                        'type' => $notif->data['type'] ?? 'info',
                        'important' => $notif->data['important'] ?? false,
                        'read' => $notif->read_at !== null,
                        'created_at' => $notif->created_at->toIso8601String(),
                        'action_url' => $notif->data['action_url'] ?? null
                    ];
                })->toArray()"
                :unreadCount="auth()->user()->unreadNotifications->count()" />
            @endif

            <!-- User Profile Menu -->
            @if(auth()->check())
            <x-header.user-profile-menu :user="auth()->user()" />
            @endif
        </div>
    </div>

    <!-- Progress Bar (for page loading) -->
    <div x-show="isLoading" 
         x-cloak
         class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-eco-green-500 via-blue-500 to-purple-500"
         style="animation: shimmer 2s infinite;">
    </div>
</header>

<script>
function advancedHeader() {
    return {
        isLoading: false,

        init() {
            // Listen for page loading events
            document.addEventListener('DOMContentLoaded', () => {
                this.setupLoadingIndicator();
            });
        },

        openQuickActions() {
            // Dispatch event to open quick actions
            window.dispatchEvent(new Event('keydown'));
            const event = new KeyboardEvent('keydown', {
                key: '/',
                code: 'Slash',
                keyCode: 191,
                which: 191,
                bubbles: true,
                cancelable: true
            });
            window.dispatchEvent(event);
        },

        setupLoadingIndicator() {
            // Show loading bar on page transitions
            document.addEventListener('click', (e) => {
                const link = e.target.closest('a');
                if (link && link.href && link.target !== '_blank' && !link.href.startsWith('#')) {
                    this.isLoading = true;
                }
            });

            // Hide loading bar when page loads
            window.addEventListener('load', () => {
                this.isLoading = false;
            });
        }
    }
}

// Shimmer animation
const shimmerStyle = document.createElement('style');
shimmerStyle.textContent = `
    @keyframes shimmer {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }
`;
document.head.appendChild(shimmerStyle);
</script>

