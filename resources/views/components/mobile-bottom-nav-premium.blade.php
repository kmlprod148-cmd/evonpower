{{-- Mobile Bottom Navigation Premium --}}
@auth
@php
    $user = auth()->user();
    $isAdmin = $user && $user->hasRole(['admin', 'super_admin']);
    $userRolesLower = $user ? $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray() : [];
    $isPartnerOrOperator = in_array('partner', $userRolesLower) || in_array('operator', $userRolesLower);
    $isIntegrator = in_array('integrator', $userRolesLower);
    $isSimpleUser = !$isAdmin && !$isPartnerOrOperator && !$isIntegrator;
@endphp
<nav class="evon-mobile-bottom-nav lg:hidden" aria-label="{{ __('messages.main_navigation') }}">
    <div class="evon-mobile-bottom-nav-content">
        
        {{-- Dashboard --}}
        <a href="{{ route($isSimpleUser ? 'dashboard.client' : 'dashboard') }}" 
           class="evon-mobile-bottom-nav-item {{ request()->routeIs('dashboard*') ? 'active' : '' }}"
           aria-label="{{ __('messages.dashboard') }}">
            <svg class="evon-mobile-bottom-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span class="evon-mobile-bottom-nav-label">{{ __('messages.home') }}</span>
        </a>

        {{-- Reservations (clients) / Stations (admin, partner, operator, integrator) --}}
        @if($isSimpleUser)
        <a href="{{ route('reservations.index') }}" 
           class="evon-mobile-bottom-nav-item {{ request()->routeIs('reservations.*') ? 'active' : '' }}"
           aria-label="{{ __('messages.reservations') }}">
            <svg class="evon-mobile-bottom-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <rect stroke-linecap="round" stroke-linejoin="round" stroke-width="2" x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                <line x1="16" x2="16" y1="2" y2="6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <line x1="8" x2="8" y1="2" y2="6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <line x1="3" x2="21" y1="10" y2="10" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span class="evon-mobile-bottom-nav-label">{{ __('messages.reservations') }}</span>
        </a>
        @else
        <a href="{{ route('charging-points.index') }}" 
           class="evon-mobile-bottom-nav-item {{ request()->routeIs('charging-points.*') ? 'active' : '' }}"
           aria-label="{{ __('messages.charging_points') }}">
            <svg class="evon-mobile-bottom-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            <span class="evon-mobile-bottom-nav-label">{{ __('messages.stations') }}</span>
        </a>
        @endif

        {{-- Menu FAB Central --}}
        <button type="button"
                @click.stop="sidebarOpen = !sidebarOpen" 
                class="evon-bottom-nav-menu-btn"
                :class="{ 'menu-open': sidebarOpen }"
                aria-label="{{ __('messages.menu') }}"
                aria-expanded="false">
            <div class="evon-hamburger">
                <span class="evon-hamburger-line"></span>
                <span class="evon-hamburger-line"></span>
                <span class="evon-hamburger-line"></span>
            </div>
        </button>

        {{-- Transactions --}}
        <a href="{{ route('transactions.index') }}" 
           class="evon-mobile-bottom-nav-item {{ request()->routeIs('transactions.*') ? 'active' : '' }}"
           aria-label="{{ __('messages.transactions') }}">
            <svg class="evon-mobile-bottom-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="evon-mobile-bottom-nav-label">{{ __('messages.wallet') }}</span>
        </a>

        {{-- Notifications --}}
        <a href="{{ route('notifications.index') }}" 
           class="evon-mobile-bottom-nav-item {{ request()->routeIs('notifications.*') ? 'active' : '' }}"
           aria-label="{{ __('messages.notifications') }}">
            @if(auth()->user()->unreadNotifications->count() > 0)
                <span class="evon-nav-badge-mobile">{{ auth()->user()->unreadNotifications->count() > 9 ? '9+' : auth()->user()->unreadNotifications->count() }}</span>
            @endif
            <svg class="evon-mobile-bottom-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <span class="evon-mobile-bottom-nav-label">{{ __('messages.alerts') }}</span>
        </a>

    </div>
</nav>

<style>
/* Animation du bouton menu quand actif */
.menu-open .evon-bottom-nav-menu-btn {
    transform: rotate(90deg);
    background: linear-gradient(135deg, #3ab56a 0%, #4acf7b 100%);
}

.menu-open .evon-bottom-nav-menu-btn .evon-hamburger-line:nth-child(1) {
    transform: translateY(6px) rotate(45deg);
}

.menu-open .evon-bottom-nav-menu-btn .evon-hamburger-line:nth-child(2) {
    opacity: 0;
    transform: scaleX(0);
}

.menu-open .evon-bottom-nav-menu-btn .evon-hamburger-line:nth-child(3) {
    transform: translateY(-6px) rotate(-45deg);
}

/* Pulse effect quand menu ouvert */
.menu-open .evon-bottom-nav-menu-btn::after {
    content: '';
    position: absolute;
    inset: -8px;
    border-radius: 50%;
    border: 2px solid #4acf7b;
    animation: pulseRing 1.5s cubic-bezier(0.455, 0.03, 0.515, 0.955) infinite;
}

@keyframes pulseRing {
    0% {
        transform: scale(0.9);
        opacity: 1;
    }
    100% {
        transform: scale(1.3);
        opacity: 0;
    }
}
</style>
@endauth

