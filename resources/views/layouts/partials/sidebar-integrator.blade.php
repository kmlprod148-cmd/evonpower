<!-- SECTION GESTION INTÉGRATEUR -->
<div class="nav-section mt-6">
    <h3 class="sidebar-text text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-3 mb-3" x-show="!sidebarCollapsed">
        Gestion
    </h3>
    
    <!-- Groupes -->
        <a href="{{ route('groups.index') }}" 
           class="nav-item {{ request()->routeIs('groups.*') ? 'active' : '' }}">
            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <span class="sidebar-text" x-show="!sidebarCollapsed">Groupes</span>
        </a>

        <!-- Points de charge -->
        <a href="{{ route('charging-points.index') }}" 
           class="nav-item {{ request()->routeIs('charging-points.*') ? 'active' : '' }}">
            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            <span class="sidebar-text" x-show="!sidebarCollapsed">Points de charge</span>
        </a>

        <!-- Plans -->
        <a href="{{ route('plans.index') }}" 
           class="nav-item {{ request()->routeIs('plans.*') ? 'active' : '' }}">
            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
            <span class="sidebar-text" x-show="!sidebarCollapsed">Plans</span>
        </a>

        <!-- Business Profiles -->
        <a href="{{ route('business-profiles.index') }}" 
           class="nav-item {{ request()->routeIs('business-profiles.*') ? 'active' : '' }}">
            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <span class="sidebar-text" x-show="!sidebarCollapsed">Business Profils</span>
        </a>

        <!-- Partenaires/Opérateurs (uniquement pour les intégrateurs) -->
        @php
            $userRolesLower = auth()->user()->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
            $isPartnerOrOperator = in_array('partner', $userRolesLower) || in_array('operator', $userRolesLower);
        @endphp
        @if(auth()->user()->hasRole('integrator') && !$isPartnerOrOperator)
        <a href="{{ route('integrator.partners.index') }}" 
           class="nav-item {{ request()->routeIs('integrator.partners.*') || request()->routeIs('integrator.operators.*') ? 'active' : '' }}">
            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <span class="sidebar-text" x-show="!sidebarCollapsed">Partenaires/Opérateurs</span>
        </a>
        @endif

        <!-- Utilisateurs -->
        <a href="{{ route('admin.users.index') }}" 
           class="nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            <span class="sidebar-text" x-show="!sidebarCollapsed">Utilisateurs</span>
        </a>

        <!-- Transactions -->
        <a href="{{ route('transactions.index') }}" 
           class="nav-item {{ request()->routeIs('transactions.*') ? 'active' : '' }}">
            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
            </svg>
            <span class="sidebar-text" x-show="!sidebarCollapsed">Transactions</span>
        </a>

        <!-- Réservations -->
        <a href="{{ route('reservations.index') }}" 
           class="nav-item {{ request()->routeIs('reservations.*') ? 'active' : '' }}">
            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span class="sidebar-text" x-show="!sidebarCollapsed">Réservations</span>
        </a>

        <!-- Rapports -->
        <a href="{{ route('reports.index') }}" 
           class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}">
            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            <span class="sidebar-text" x-show="!sidebarCollapsed">Rapports</span>
        </a>

        <!-- Monitoring -->
        <a href="{{ route('monitoring.index') }}" 
           class="nav-item {{ request()->routeIs('monitoring.*') ? 'active' : '' }}">
            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            <span class="sidebar-text" x-show="!sidebarCollapsed">Monitoring</span>
        </a>
</div>
