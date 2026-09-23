{{-- Mobile Bottom Navigation Bar Premium (iOS/Android style) --}}
@auth
<nav class="evon-mobile-bottom-nav lg:hidden" 
     role="navigation" 
     aria-label="{{ __('messages.main_navigation') }}">
    <div class="evon-mobile-bottom-nav-content">
        {{-- Dashboard --}}
        <a href="{{ route('dashboard') }}" 
           class="evon-mobile-bottom-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}"
           aria-label="{{ __('messages.dashboard') }}"
           {{ request()->routeIs('dashboard') ? 'aria-current="page"' : '' }}>
            <svg class="evon-mobile-bottom-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span class="evon-mobile-bottom-nav-label">{{ __('messages.home') }}</span>
        </a>

        {{-- Stations / Bornes --}}
        <a href="{{ route('charging-points.index') }}" 
           class="evon-mobile-bottom-nav-item {{ request()->routeIs('charging-points.*') ? 'active' : '' }}"
           aria-label="{{ __('messages.charging_points') }}">
            <svg class="evon-mobile-bottom-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            <span class="evon-mobile-bottom-nav-label">{{ __('messages.stations') }}</span>
        </a>

        {{-- Menu FAB Central --}}
        <button type="button"
                onclick="toggleMobileSidebar()"
                class="evon-mobile-bottom-nav-item evon-mobile-bottom-nav-fab"
                aria-label="{{ __('messages.menu') }}"
                aria-expanded="false"
                id="mobileMenuFabTrigger">
            <div class="evon-mobile-bottom-nav-fab-icon">
                <div class="evon-hamburger-minimal">
                    <span class="evon-hamburger-line"></span>
                    <span class="evon-hamburger-line"></span>
                    <span class="evon-hamburger-line"></span>
                </div>
            </div>
        </button>

        {{-- Transactions --}}
        <a href="{{ route('transactions.index') }}" 
           class="evon-mobile-bottom-nav-item {{ request()->routeIs('transactions.*') ? 'active' : '' }}"
           aria-label="{{ __('messages.transactions') }}">
            <svg class="evon-mobile-bottom-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="evon-mobile-bottom-nav-label">{{ __('messages.wallet') }}</span>
        </a>

        {{-- Notifications --}}
        <a href="{{ route('notifications.index') }}" 
           class="evon-mobile-bottom-nav-item {{ request()->routeIs('notifications.*') ? 'active' : '' }}"
           aria-label="{{ __('messages.notifications') }}">
            @if(auth()->check() && auth()->user()->unreadNotifications->count() > 0)
                <span class="evon-nav-badge-mobile">{{ auth()->user()->unreadNotifications->count() > 9 ? '9+' : auth()->user()->unreadNotifications->count() }}</span>
            @endif
            <svg class="evon-mobile-bottom-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <span class="evon-mobile-bottom-nav-label">{{ __('messages.alerts') }}</span>
        </a>
    </div>
</nav>
@endauth

<style>
/* Hamburger Icon Minimal pour FAB */
.evon-hamburger-minimal {
    display: flex;
    flex-direction: column;
    gap: 3px;
    width: 24px;
    height: 24px;
    justify-content: center;
    align-items: center;
}

.evon-hamburger-minimal .evon-hamburger-line {
    width: 18px;
    height: 2px;
    background: white;
    border-radius: 2px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Animation du hamburger quand le menu est ouvert */
.menu-open .evon-hamburger-minimal .evon-hamburger-line:nth-child(1) {
    transform: translateY(5px) rotate(45deg);
}

.menu-open .evon-hamburger-minimal .evon-hamburger-line:nth-child(2) {
    opacity: 0;
    transform: scaleX(0);
}

.menu-open .evon-hamburger-minimal .evon-hamburger-line:nth-child(3) {
    transform: translateY(-5px) rotate(-45deg);
}

/* Badge pour notifications */
.evon-nav-badge-mobile {
    position: absolute;
    top: 4px;
    right: 8px;
    min-width: 18px;
    height: 18px;
    padding: 0 4px;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    font-size: 10px;
    font-weight: 700;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
    z-index: 10;
}

/* Animation pulse pour le badge */
@keyframes badgePulse {
    0%, 100% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.1);
        opacity: 0.9;
    }
}

.evon-nav-badge-mobile {
    animation: badgePulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>

<script>
// Toggle mobile sidebar from bottom nav
function toggleMobileSidebar() {
    // Méthode 1: Alpine.js (préférée)
    if (window.Alpine && document.body.__x && document.body.__x.$data) {
        document.body.__x.$data.sidebarOpen = !document.body.__x.$data.sidebarOpen;
        
        // Toggle class pour animation du hamburger
        const fabBtn = document.getElementById('mobileMenuFabTrigger');
        if (fabBtn) {
            fabBtn.classList.toggle('menu-open');
        }
        return;
    }
    
    // Méthode 2: Fallback avec bouton menu classique
    const mobileMenuBtn = document.querySelector('.evon-mobile-menu-btn');
    if (mobileMenuBtn) {
        mobileMenuBtn.click();
        
        // Toggle class pour animation
        const fabBtn = document.getElementById('mobileMenuFabTrigger');
        if (fabBtn) {
            fabBtn.classList.toggle('menu-open');
        }
        return;
    }
    
    // Méthode 3: Fallback manuel
    const sidebar = document.querySelector('[x-show="sidebarOpen"]') || 
                    document.querySelector('.evon-mobile-sidebar');
    if (sidebar) {
        const isVisible = !sidebar.classList.contains('hidden');
        if (isVisible) {
            sidebar.classList.add('hidden');
        } else {
            sidebar.classList.remove('hidden');
        }
        
        // Toggle class pour animation
        const fabBtn = document.getElementById('mobileMenuFabTrigger');
        if (fabBtn) {
            fabBtn.classList.toggle('menu-open');
        }
    }
}

// Synchroniser l'état du hamburger avec l'état du sidebar
document.addEventListener('DOMContentLoaded', function() {
    // Observer pour détecter les changements du sidebar
    const observeSidebarChanges = () => {
        const sidebar = document.querySelector('[x-show="sidebarOpen"]') || 
                        document.querySelector('.evon-mobile-sidebar');
        const fabBtn = document.getElementById('mobileMenuFabTrigger');
        
        if (sidebar && fabBtn) {
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.attributeName === 'class' || mutation.attributeName === 'style') {
                        const isOpen = !sidebar.classList.contains('hidden') && 
                                       sidebar.style.display !== 'none';
                        if (isOpen) {
                            fabBtn.classList.add('menu-open');
                        } else {
                            fabBtn.classList.remove('menu-open');
                        }
                    }
                });
            });
            
            observer.observe(sidebar, {
                attributes: true,
                attributeFilter: ['class', 'style']
            });
        }
    };
    
    // Démarrer l'observation avec un léger délai pour Alpine.js
    setTimeout(observeSidebarChanges, 100);
});
</script>

