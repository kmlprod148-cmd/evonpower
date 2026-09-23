{{-- Enhanced Mobile Bottom Navigation avec Gestures --}}
<nav 
    x-data="mobileBottomNav()"
    x-init="init()"
    @touchstart="handleTouchStart($event)"
    @touchmove="handleTouchMove($event)"
    @touchend="handleTouchEnd($event)"
    class="evon-mobile-bottom-nav safe-bottom" 
    :class="{ 'translate-y-full': hidden }"
    role="navigation" 
    aria-label="Navigation mobile principale"
    style="transition: transform 0.3s ease-out;"
>
    {{-- Swipe Indicator --}}
    <div class="swipe-indicator"></div>
    
    <div class="evon-mobile-bottom-nav-content">
        {{-- Dashboard --}}
        <a href="{{ route('dashboard') }}" 
           class="evon-mobile-bottom-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}"
           aria-label="Tableau de bord"
           {{ request()->routeIs('dashboard') ? 'aria-current="page"' : '' }}
           @click="vibrate('light')">
            <svg class="evon-mobile-bottom-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span class="evon-mobile-bottom-nav-label">{{ __('messages.dashboard') ?? 'Accueil' }}</span>
            @if(request()->routeIs('dashboard'))
                <span class="absolute -top-1 left-1/2 transform -translate-x-1/2 w-1 h-1 bg-eco-green-500 rounded-full"></span>
            @endif
        </a>

        {{-- Stations / Bornes --}}
        @can('view-charging-points')
        <a href="{{ route('charging-points.index') }}" 
           class="evon-mobile-bottom-nav-item {{ request()->routeIs('charging-points.*') ? 'active' : '' }}"
           aria-label="Bornes de recharge"
           @click="vibrate('light')">
            <svg class="evon-mobile-bottom-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            <span class="evon-mobile-bottom-nav-label">{{ __('messages.charging_points') ?? 'Bornes' }}</span>
            @if(request()->routeIs('charging-points.*'))
                <span class="absolute -top-1 left-1/2 transform -translate-x-1/2 w-1 h-1 bg-eco-green-500 rounded-full"></span>
            @endif
        </a>
        @endcan

        {{-- Action principale (FAB - Floating Action Button) --}}
        <button 
            @click="openQuickActions(); vibrate('medium')"
            class="evon-mobile-bottom-nav-item evon-mobile-bottom-nav-fab group"
            aria-label="Actions rapides"
        >
            <div class="evon-mobile-bottom-nav-fab-icon group-active:scale-90 transition-transform duration-150">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
            </div>
            {{-- Ripple effect --}}
            <span class="absolute inset-0 rounded-full bg-white opacity-0 group-active:opacity-20 transition-opacity duration-150"></span>
        </button>

        {{-- Transactions --}}
        @can('view-transactions')
        <a href="{{ route('transactions.index') }}" 
           class="evon-mobile-bottom-nav-item {{ request()->routeIs('transactions.*') ? 'active' : '' }}"
           aria-label="Transactions"
           @click="vibrate('light')">
            <svg class="evon-mobile-bottom-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <span class="evon-mobile-bottom-nav-label">{{ __('messages.transactions') ?? 'Transactions' }}</span>
            @if(request()->routeIs('transactions.*'))
                <span class="absolute -top-1 left-1/2 transform -translate-x-1/2 w-1 h-1 bg-eco-green-500 rounded-full"></span>
            @endif
            {{-- Badge pour notifications --}}
            @if(isset($pendingTransactions) && $pendingTransactions > 0)
                <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center animate-pulse">
                    {{ $pendingTransactions > 9 ? '9+' : $pendingTransactions }}
                </span>
            @endif
        </a>
        @endcan

        {{-- Menu / Plus --}}
        <button 
            @click="toggleMenu(); vibrate('light')"
            class="evon-mobile-bottom-nav-item"
            aria-label="Menu"
            id="mobileMenuTrigger"
        >
            <svg class="evon-mobile-bottom-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
            <span class="evon-mobile-bottom-nav-label">{{ __('messages.menu') ?? 'Menu' }}</span>
        </button>
    </div>
</nav>

<script>
function mobileBottomNav() {
    return {
        hidden: false,
        lastScrollY: 0,
        scrollThreshold: 10,
        touchStartY: 0,
        touchMoveY: 0,
        
        init() {
            // Auto-hide on scroll down, show on scroll up
            let ticking = false;
            
            window.addEventListener('scroll', () => {
                if (!ticking) {
                    window.requestAnimationFrame(() => {
                        this.handleScroll();
                        ticking = false;
                    });
                    ticking = true;
                }
            });
            
            // Gérer la résolution des promesses pour les actions
            this.setupActionHandlers();
        },
        
        handleScroll() {
            const currentScrollY = window.pageYOffset || document.documentElement.scrollTop;
            
            if (Math.abs(currentScrollY - this.lastScrollY) < this.scrollThreshold) {
                return;
            }
            
            // Masquer si on scroll vers le bas, afficher si on scroll vers le haut
            if (currentScrollY > this.lastScrollY && currentScrollY > 100) {
                this.hidden = true;
            } else {
                this.hidden = false;
            }
            
            this.lastScrollY = currentScrollY;
        },
        
        handleTouchStart(e) {
            this.touchStartY = e.touches[0].clientY;
        },
        
        handleTouchMove(e) {
            this.touchMoveY = e.touches[0].clientY;
        },
        
        handleTouchEnd() {
            const diff = this.touchStartY - this.touchMoveY;
            
            // Swipe down pour afficher, swipe up pour masquer
            if (Math.abs(diff) > 50) {
                if (diff > 0) {
                    // Swipe up - masquer
                    this.hidden = true;
                } else {
                    // Swipe down - afficher
                    this.hidden = false;
                }
            }
        },
        
        openQuickActions() {
            // Ouvrir l'action sheet des actions rapides
            if (typeof openActionSheet === 'function') {
                openActionSheet('dashboard-actions');
            }
        },
        
        toggleMenu() {
            // Basculer le sidebar
            if (window.Alpine && document.body.__x && document.body.__x.$data) {
                document.body.__x.$data.sidebarOpen = !document.body.__x.$data.sidebarOpen;
            } else {
                const mobileMenuBtn = document.querySelector('.evon-mobile-menu-btn');
                if (mobileMenuBtn) {
                    mobileMenuBtn.click();
                }
            }
        },
        
        vibrate(intensity = 'light') {
            if ('vibrate' in navigator) {
                const patterns = {
                    light: 10,
                    medium: 20,
                    heavy: 30
                };
                navigator.vibrate(patterns[intensity] || 10);
            }
        },
        
        setupActionHandlers() {
            // Configuration des handlers pour les actions rapides
            console.log('Mobile Bottom Nav initialized');
        }
    };
}
</script>

<style>
/* Enhanced Mobile Bottom Nav Styles */
.evon-mobile-bottom-nav {
    @apply fixed bottom-0 left-0 right-0 z-50;
    @apply bg-white/95 dark:bg-gray-800/95 backdrop-blur-lg;
    @apply border-t border-gray-200 dark:border-gray-700;
    @apply shadow-lg shadow-gray-900/5;
}

.evon-mobile-bottom-nav-content {
    @apply flex items-center justify-around;
    @apply px-2 py-2;
    max-width: 640px;
    margin: 0 auto;
}

.evon-mobile-bottom-nav-item {
    @apply relative flex flex-col items-center justify-center gap-1;
    @apply min-w-[60px] py-2 px-3;
    @apply text-gray-600 dark:text-gray-400;
    @apply transition-all duration-200;
    @apply rounded-xl;
    @apply touch-target;
}

.evon-mobile-bottom-nav-item:active {
    @apply scale-95;
}

.evon-mobile-bottom-nav-item.active {
    @apply text-white bg-eco-green-600;
}

.evon-mobile-bottom-nav-icon {
    @apply w-6 h-6;
    @apply transition-transform duration-200;
}

.evon-mobile-bottom-nav-item.active .evon-mobile-bottom-nav-icon {
    @apply scale-110 text-white;
}

.evon-mobile-bottom-nav-label {
    @apply text-[10px] font-semibold;
    @apply leading-tight;
}

.evon-mobile-bottom-nav-item.active .evon-mobile-bottom-nav-label {
    @apply text-white;
}

/* FAB (Floating Action Button) */
.evon-mobile-bottom-nav-fab {
    @apply relative;
    margin-top: -20px;
}

.evon-mobile-bottom-nav-fab-icon {
    @apply w-14 h-14;
    @apply bg-gradient-to-br from-eco-green-500 to-eco-green-600;
    @apply text-white;
    @apply rounded-full;
    @apply flex items-center justify-center;
    @apply shadow-xl shadow-eco-green-500/30;
    @apply transition-all duration-200;
}

.evon-mobile-bottom-nav-fab:active .evon-mobile-bottom-nav-fab-icon {
    @apply shadow-lg;
}

/* Hide on large screens */
@media (min-width: 1024px) {
    .evon-mobile-bottom-nav {
        @apply hidden;
    }
}

/* Landscape mobile adjustments */
@media (max-height: 500px) and (orientation: landscape) {
    .evon-mobile-bottom-nav-content {
        @apply py-1;
    }
    
    .evon-mobile-bottom-nav-item {
        @apply py-1;
    }
    
    .evon-mobile-bottom-nav-label {
        @apply hidden;
    }
    
    .evon-mobile-bottom-nav-fab {
        margin-top: -10px;
    }
    
    .evon-mobile-bottom-nav-fab-icon {
        @apply w-12 h-12;
    }
}
</style>

