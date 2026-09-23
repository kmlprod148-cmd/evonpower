<nav class="h-full bg-white dark:bg-gray-800 shadow-lg border-r border-gray-200 dark:border-gray-700 flex flex-col" x-data="{ collapsed: false }">
    <!-- Logo Section -->
    <div class="p-6 border-b border-gray-100 dark:border-gray-700">
        <div class="flex items-center" :class="collapsed ? 'justify-center' : 'space-x-3'">
            <div class="relative flex-shrink-0">
                <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div class="absolute -bottom-1 -right-1 w-3 h-3 bg-green-500 rounded-full border-2 border-white dark:border-gray-800"></div>
            </div>
            <div x-show="!collapsed" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">EVON</h1>
                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">APP</p>
            </div>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
        <!-- Dashboard -->
        <a href="{{ route('dashboard') }}"
           class="sidebar-item group flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 {{ request()->routeIs('dashboard') ? 'active bg-gradient-to-r from-green-50 to-transparent dark:from-green-900/20 dark:to-transparent border-l-[3px] border-green-500' : '' }}"
           :class="collapsed ? 'justify-center' : ''"
           :title="collapsed ? 'Tableau de bord' : ''">
            <div class="flex items-center justify-center w-8 h-8" :class="collapsed ? '' : 'mr-3'">
                <svg class="w-5 h-5 {{ request()->routeIs('dashboard') ? 'text-green-500' : 'text-gray-500 dark:text-gray-400 group-hover:text-green-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m0 0l7 7m-2 2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
            </div>
            <span class="font-medium {{ request()->routeIs('dashboard') ? 'text-green-600 dark:text-green-400' : '' }}" x-show="!collapsed" x-transition>Tableau de bord</span>
        </a>

        <!-- Intégrateurs -->
        @can('view_integrators')
        <a href="{{ route('integrators.index') }}"
           class="sidebar-item group flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 {{ request()->routeIs('integrators.*') ? 'active bg-gradient-to-r from-green-50 to-transparent dark:from-green-900/20 dark:to-transparent border-l-[3px] border-green-500' : '' }}"
           :class="collapsed ? 'justify-center' : ''"
           :title="collapsed ? 'Intégrateurs' : ''">
            <div class="flex items-center justify-center w-8 h-8" :class="collapsed ? '' : 'mr-3'">
                <svg class="w-5 h-5 {{ request()->routeIs('integrators.*') ? 'text-green-500' : 'text-gray-500 dark:text-gray-400 group-hover:text-green-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
            </div>
            <span class="font-medium {{ request()->routeIs('integrators.*') ? 'text-green-600 dark:text-green-400' : '' }}" x-show="!collapsed" x-transition>Intégrateurs</span>
        </a>
        @endcan

        <!-- Groupes -->
        @can('view_all_groups', 'view_integrator_groups', 'view_own_groups')
        <a href="{{ route('groups.index') }}"
           class="sidebar-item group flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 {{ request()->routeIs('groups.*') ? 'active bg-gradient-to-r from-green-50 to-transparent dark:from-green-900/20 dark:to-transparent border-l-[3px] border-green-500' : '' }}"
           :class="collapsed ? 'justify-center' : ''"
           :title="collapsed ? 'Groupes' : ''">
            <div class="flex items-center justify-center w-8 h-8" :class="collapsed ? '' : 'mr-3'">
                @if(request()->routeIs('groups.*'))
                <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                @else
                <svg class="w-5 h-5 text-gray-500 dark:text-gray-400 group-hover:text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                @endif
            </div>
            <span class="font-medium {{ request()->routeIs('groups.*') ? 'text-green-600 dark:text-green-400' : '' }}" x-show="!collapsed" x-transition>Groupes</span>
        </a>
        @endcan

        <!-- Comptes Système (pour les intégrateurs et opérateurs système) -->
        @if(auth()->user()->hasRole('integrator') || (auth()->user()->hasRole('operator') && auth()->user()->user_type === 'system'))
        
        <!-- Comptes Système - Vue différente selon le rôle -->
        @if(auth()->user()->hasRole('integrator'))
        <a href="{{ route('system-users.index') }}"
           class="sidebar-item group flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 {{ request()->routeIs('system-users.*') ? 'active bg-gradient-to-r from-green-50 to-transparent dark:from-green-900/20 dark:to-transparent border-l-[3px] border-green-500' : '' }}"
           :class="collapsed ? 'justify-center' : ''"
           :title="collapsed ? 'Comptes Système' : ''">
            <div class="flex items-center justify-center w-8 h-8" :class="collapsed ? '' : 'mr-3'">
                @if(request()->routeIs('system-users.*'))
                <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
                @else
                <svg class="w-5 h-5 text-gray-500 dark:text-gray-400 group-hover:text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                @endif
            </div>
            <span class="font-medium {{ request()->routeIs('system-users.*') ? 'text-green-600 dark:text-green-400' : '' }}" x-show="!collapsed" x-transition>Comptes Système</span>
        </a>
        @endif

        <!-- Créer Opérateur Système (uniquement pour les intégrateurs) -->
        @if(auth()->user()->hasRole('integrator'))
        <a href="{{ route('integrator.operators.create') }}"
           class="sidebar-item group flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 {{ request()->routeIs('integrator.operators.create') ? 'active bg-gradient-to-r from-green-50 to-transparent dark:from-green-900/20 dark:to-transparent border-l-[3px] border-green-500' : '' }}"
           :class="collapsed ? 'justify-center' : ''"
           :title="collapsed ? 'Créer Opérateur' : ''">
            <div class="flex items-center justify-center w-8 h-8" :class="collapsed ? '' : 'mr-3'">
                <svg class="w-5 h-5 text-gray-500 dark:text-gray-400 group-hover:text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
            </div>
            <span class="font-medium {{ request()->routeIs('integrator.operators.create') ? 'text-green-600 dark:text-green-400' : '' }}" x-show="!collapsed" x-transition>Créer Opérateur</span>
        </a>

        @if(auth()->user()->hasRole(['integrator','Integrator']) || auth()->user()->can('create_partners'))
        <a href="{{ route('partners.create') }}"
           class="sidebar-item group flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 {{ request()->routeIs('partners.create') ? 'active bg-gradient-to-r from-green-50 to-transparent dark:from-green-900/20 dark:to-transparent border-l-[3px] border-green-500' : '' }}">
            <svg class="h-5 w-5 text-gray-500 group-hover:text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span class="font-medium {{ request()->routeIs('partners.create') ? 'text-green-600 dark:text-green-400' : '' }}" x-show="!collapsed" x-transition>Créer Partenaire</span>
        </a>
        @endif
        @endif

        <!-- Partenaires/Opérateurs (uniquement pour les intégrateurs) -->
        @php
            $userRolesLower = auth()->user()->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
            $isPartnerOrOperator = in_array('partner', $userRolesLower) || in_array('operator', $userRolesLower);
        @endphp
        @if(auth()->user()->hasRole('integrator') && !$isPartnerOrOperator)
        <a href="{{ route('integrator.partners.index') }}"
           class="sidebar-item group flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 {{ request()->routeIs('integrator.partners.*') || request()->routeIs('integrator.operators.*') ? 'active bg-gradient-to-r from-green-50 to-transparent dark:from-green-900/20 dark:to-transparent border-l-[3px] border-green-500' : '' }}"
           :class="collapsed ? 'justify-center' : ''"
           :title="collapsed ? 'Partenaires/Opérateurs' : ''">
            <div class="flex items-center justify-center w-8 h-8" :class="collapsed ? '' : 'mr-3'">
                <svg class="w-5 h-5 text-gray-500 dark:text-gray-400 group-hover:text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>
            <span class="font-medium {{ request()->routeIs('integrator.partners.*') || request()->routeIs('integrator.operators.*') ? 'text-green-600 dark:text-green-400' : '' }}" x-show="!collapsed" x-transition>Partenaires/Opérateurs</span>
        </a>
        @endif

        <!-- Dashboard Opérateur Système (pour les opérateurs système) -->
        @if(auth()->user()->hasRole('operator') && auth()->user()->user_type === 'system')
        <a href="{{ route('operator.dashboard') }}"
           class="sidebar-item group flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 {{ request()->routeIs('operator.dashboard') ? 'active bg-gradient-to-r from-green-50 to-transparent dark:from-green-900/20 dark:to-transparent border-l-[3px] border-green-500' : '' }}"
           :class="collapsed ? 'justify-center' : ''"
           :title="collapsed ? 'Dashboard Opérateur' : ''">
            <div class="flex items-center justify-center w-8 h-8" :class="collapsed ? '' : 'mr-3'">
                <svg class="w-5 h-5 text-gray-500 dark:text-gray-400 group-hover:text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v4H8V5z"></path>
                </svg>
            </div>
            <span class="font-medium {{ request()->routeIs('operator.dashboard') ? 'text-green-600 dark:text-green-400' : '' }}" x-show="!collapsed" x-transition>Dashboard Opérateur</span>
        </a>
        @endif

        <!-- Permissions Système (pour les intégrateurs) -->
        @if(auth()->user()->hasRole('integrator'))
        <a href="{{ route('system-permissions.index') }}"
           class="sidebar-item group flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 {{ request()->routeIs('system-permissions.*') ? 'active bg-gradient-to-r from-green-50 to-transparent dark:from-green-900/20 dark:to-transparent border-l-[3px] border-green-500' : '' }}"
           :class="collapsed ? 'justify-center' : ''"
           :title="collapsed ? 'Permissions Système' : ''">
            <div class="flex items-center justify-center w-8 h-8" :class="collapsed ? '' : 'mr-3'">
                <svg class="w-5 h-5 text-gray-500 dark:text-gray-400 group-hover:text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
            </div>
            <span class="font-medium {{ request()->routeIs('system-permissions.*') ? 'text-green-600 dark:text-green-400' : '' }}" x-show="!collapsed" x-transition>Permissions Système</span>
        </a>
        @endif

        <!-- Audit Comptes Système (pour les intégrateurs) -->
        @if(auth()->user()->hasRole('integrator'))
        <a href="{{ route('system-audit.index') }}"
           class="sidebar-item group flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 {{ request()->routeIs('system-audit.*') ? 'active bg-gradient-to-r from-green-50 to-transparent dark:from-green-900/20 dark:to-transparent border-l-[3px] border-green-500' : '' }}"
           :class="collapsed ? 'justify-center' : ''"
           :title="collapsed ? 'Audit Comptes' : ''">
            <div class="flex items-center justify-center w-8 h-8" :class="collapsed ? '' : 'mr-3'">
                <svg class="w-5 h-5 text-gray-500 dark:text-gray-400 group-hover:text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                </svg>
            </div>
            <span class="font-medium {{ request()->routeIs('system-audit.*') ? 'text-green-600 dark:text-green-400' : '' }}" x-show="!collapsed" x-transition>Audit Comptes</span>
        </a>
        @endif

        <!-- Configuration Système (pour les intégrateurs) -->
        @if(auth()->user()->hasRole('integrator'))
        <a href="{{ route('system-config.index') }}"
           class="sidebar-item group flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 {{ request()->routeIs('system-config.*') ? 'active bg-gradient-to-r from-green-50 to-transparent dark:from-green-900/20 dark:to-transparent border-l-[3px] border-green-500' : '' }}"
           :class="collapsed ? 'justify-center' : ''"
           :title="collapsed ? 'Config Système' : ''">
            <div class="flex items-center justify-center w-8 h-8" :class="collapsed ? '' : 'mr-3'">
                <svg class="w-5 h-5 text-gray-500 dark:text-gray-400 group-hover:text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
            </div>
            <span class="font-medium {{ request()->routeIs('system-config.*') ? 'text-green-600 dark:text-green-400' : '' }}" x-show="!collapsed" x-transition>Config Système</span>
        </a>
        @endif
        @endif

        <!-- Plan tarifaire -->
        @can('manage_plans')
        <a href="{{ route('plans.index') }}"
           class="sidebar-item group flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 {{ request()->routeIs('plans.*') ? 'active bg-gradient-to-r from-green-50 to-transparent dark:from-green-900/20 dark:to-transparent border-l-[3px] border-green-500' : '' }}"
           :class="collapsed ? 'justify-center' : ''"
           :title="collapsed ? 'Plan tarifaire' : ''">
            <div class="flex items-center justify-center w-8 h-8" :class="collapsed ? '' : 'mr-3'">
                <svg class="w-5 h-5 {{ request()->routeIs('plans.*') ? 'text-green-500' : 'text-gray-500 dark:text-gray-400 group-hover:text-green-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <span class="font-medium {{ request()->routeIs('plans.*') ? 'text-green-600 dark:text-green-400' : '' }}" x-show="!collapsed" x-transition>Plan tarifaire</span>
        </a>
        @endcan

        <!-- Offre publique -->
        <a href="{{ route('offre.show', 1) }}"
           class="sidebar-item group flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 {{ request()->routeIs('offre.show') ? 'active bg-gradient-to-r from-green-50 to-transparent dark:from-green-900/20 dark:to-transparent border-l-[3px] border-green-500' : '' }}"
           :class="collapsed ? 'justify-center' : ''"
           :title="collapsed ? 'Offre publique' : ''">
            <div class="flex items-center justify-center w-8 h-8" :class="collapsed ? '' : 'mr-3'">
                <svg class="w-5 h-5 {{ request()->routeIs('offre.show') ? 'text-green-500' : 'text-gray-500 dark:text-gray-400 group-hover:text-green-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-3-3v6m9-6a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <span class="font-medium {{ request()->routeIs('offre.show') ? 'text-green-600 dark:text-green-400' : '' }}" x-show="!collapsed" x-transition>Offre publique</span>
        </a>

        <!-- Profil intégrateur -->
        @if(auth()->user()->hasRole('integrator'))
        <a href="{{ route('profile.edit') }}"
           class="sidebar-item group flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 {{ request()->routeIs('profile.*') && auth()->user()->hasRole('integrator') ? 'active bg-gradient-to-r from-green-50 to-transparent dark:from-green-900/20 dark:to-transparent border-l-[3px] border-green-500' : '' }}"
           :class="collapsed ? 'justify-center' : ''"
           :title="collapsed ? 'Profil intégrateur' : ''">
            <div class="flex items-center justify-center w-8 h-8" :class="collapsed ? '' : 'mr-3'">
                <svg class="w-5 h-5 {{ request()->routeIs('profile.*') && auth()->user()->hasRole('integrator') ? 'text-green-500' : 'text-gray-500 dark:text-gray-400 group-hover:text-green-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <span class="font-medium {{ request()->routeIs('profile.*') && auth()->user()->hasRole('integrator') ? 'text-green-600 dark:text-green-400' : '' }}" x-show="!collapsed" x-transition>Profil intégrateur</span>
        </a>
        @endif

        <!-- Users -->
        @can('view_users')
        <a href="{{ route('admin.users.index') }}"
           class="sidebar-item group flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 {{ request()->routeIs('admin.users.*') ? 'active bg-gradient-to-r from-green-50 to-transparent dark:from-green-900/20 dark:to-transparent border-l-[3px] border-green-500' : '' }}"
           :class="collapsed ? 'justify-center' : ''"
           :title="collapsed ? 'Utilisateurs' : ''">
            <div class="flex items-center justify-center w-8 h-8" :class="collapsed ? '' : 'mr-3'">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.users.*') ? 'text-green-500' : 'text-gray-500 dark:text-gray-400 group-hover:text-green-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </div>
            <span class="font-medium {{ request()->routeIs('admin.users.*') ? 'text-green-600 dark:text-green-400' : '' }}" x-show="!collapsed" x-transition>Utilisateurs</span>
        </a>
        @endcan

        <!-- Charging Points -->
        @can('view_charging_points')
        <a href="{{ route('charging-points.index') }}"
           class="sidebar-item group flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 {{ request()->routeIs('charging-points.*') ? 'active bg-gradient-to-r from-green-50 to-transparent dark:from-green-900/20 dark:to-transparent border-l-[3px] border-green-500' : '' }}"
           :class="collapsed ? 'justify-center' : ''"
           :title="collapsed ? 'Bornes de recharge' : ''">
            <div class="flex items-center justify-center w-8 h-8" :class="collapsed ? '' : 'mr-3'">
                <svg class="w-5 h-5 {{ request()->routeIs('charging-points.*') ? 'text-green-500' : 'text-gray-500 dark:text-gray-400 group-hover:text-green-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
            </div>
            <span class="font-medium {{ request()->routeIs('charging-points.*') ? 'text-green-600 dark:text-green-400' : '' }}" x-show="!collapsed" x-transition>Bornes de recharge</span>
        </a>
        @endcan

        <!-- Stations -->
        @can('view_stations')
        <a href="{{ route('stations.index') }}"
           class="sidebar-item group flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 {{ request()->routeIs('stations.*') ? 'active bg-gradient-to-r from-green-50 to-transparent dark:from-green-900/20 dark:to-transparent border-l-[3px] border-green-500' : '' }}"
           :class="collapsed ? 'justify-center' : ''"
           :title="collapsed ? 'Stations' : ''">
            <div class="flex items-center justify-center w-8 h-8" :class="collapsed ? '' : 'mr-3'">
                <svg class="w-5 h-5 {{ request()->routeIs('stations.*') ? 'text-green-500' : 'text-gray-500 dark:text-gray-400 group-hover:text-green-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"></path>
                </svg>
            </div>
            <span class="font-medium {{ request()->routeIs('stations.*') ? 'text-green-600 dark:text-green-400' : '' }}" x-show="!collapsed" x-transition>Stations</span>
        </a>
        @endcan

        <!-- Transactions -->
        @can('view_transactions')
        <a href="{{ route('transactions.index') }}"
           class="sidebar-item group flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 {{ request()->routeIs('transactions.*') ? 'active bg-gradient-to-r from-green-50 to-transparent dark:from-green-900/20 dark:to-transparent border-l-[3px] border-green-500' : '' }}"
           :class="collapsed ? 'justify-center' : ''"
           :title="collapsed ? 'Transactions' : ''">
            <div class="flex items-center justify-center w-8 h-8" :class="collapsed ? '' : 'mr-3'">
                <svg class="w-5 h-5 {{ request()->routeIs('transactions.*') ? 'text-green-500' : 'text-gray-500 dark:text-gray-400 group-hover:text-green-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                </svg>
            </div>
            <span class="font-medium {{ request()->routeIs('transactions.*') ? 'text-green-600 dark:text-green-400' : '' }}" x-show="!collapsed" x-transition>Transactions</span>
        </a>
        @endcan

        <!-- Business Profiles (exclu pour opérateurs et partenaires) -->
        @php
            $userRolesLower = auth()->user()->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
            $isOperator = in_array('operator', $userRolesLower);
            $isPartner = in_array('partner', $userRolesLower);
        @endphp
        @can('view_business_profiles')
        @if(!$isOperator && !$isPartner)
        <a href="{{ route('business-profiles.index') }}"
           class="sidebar-item group flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 {{ request()->routeIs('business-profiles.*') ? 'active bg-gradient-to-r from-green-50 to-transparent dark:from-green-900/20 dark:to-transparent border-l-[3px] border-green-500' : '' }}"
           :class="collapsed ? 'justify-center' : ''"
           :title="collapsed ? 'Profils d\'entreprise' : ''">
            <div class="flex items-center justify-center w-8 h-8" :class="collapsed ? '' : 'mr-3'">
                <svg class="w-5 h-5 {{ request()->routeIs('business-profiles.*') ? 'text-green-500' : 'text-gray-500 dark:text-gray-400 group-hover:text-green-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.566 23.566 0 0112 15c-3.179 0-6.22-.582-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m12 5v3m0 0v3a2 2 0 01-2 2H8a2 2 0 01-2-2v-3m0-3V9a2 2 0 012-2h8a2 2 0 012 2v4zm-4-4h.01"></path>
                </svg>
            </div>
            <span class="font-medium {{ request()->routeIs('business-profiles.*') ? 'text-green-600 dark:text-green-400' : '' }}" x-show="!collapsed" x-transition>Profils d'entreprise</span>
        </a>
        @endif
        @endcan

        <!-- Partners -->
        @can('view_partners')
        <a href="{{ route('partners.index') }}"
           class="sidebar-item group flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 {{ request()->routeIs('partners.*') ? 'active bg-gradient-to-r from-green-50 to-transparent dark:from-green-900/20 dark:to-transparent border-l-[3px] border-green-500' : '' }}"
           :class="collapsed ? 'justify-center' : ''"
           :title="collapsed ? 'Partenaires' : ''">
            <div class="flex items-center justify-center w-8 h-8" :class="collapsed ? '' : 'mr-3'">
                <svg class="w-5 h-5 {{ request()->routeIs('partners.*') ? 'text-green-500' : 'text-gray-500 dark:text-gray-400 group-hover:text-green-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3v-1m18 0V9a3 3 0 00-3-3H6a3 3 0 00-3 3v1"></path>
                </svg>
            </div>
            <span class="font-medium {{ request()->routeIs('partners.*') ? 'text-green-600 dark:text-green-400' : '' }}" x-show="!collapsed" x-transition>Partenaires</span>
        </a>
        @endcan



        <!-- Refunds -->
        @can('view_refunds')
        <a href="{{ route('refunds.index') }}"
           class="sidebar-item group flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 {{ request()->routeIs('refunds.*') ? 'active bg-gradient-to-r from-green-50 to-transparent dark:from-green-900/20 dark:to-transparent border-l-[3px] border-green-500' : '' }}"
           :class="collapsed ? 'justify-center' : ''"
           :title="collapsed ? 'Remboursements' : ''">
            <div class="flex items-center justify-center w-8 h-8" :class="collapsed ? '' : 'mr-3'">
                <svg class="w-5 h-5 {{ request()->routeIs('refunds.*') ? 'text-green-500' : 'text-gray-500 dark:text-gray-400 group-hover:text-green-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <span class="font-medium {{ request()->routeIs('refunds.*') ? 'text-green-600 dark:text-green-400' : '' }}" x-show="!collapsed" x-transition>Remboursements</span>
        </a>
        @endcan

        <!-- Remote Control -->
        @can('view_remote_control')
        <a href="{{ route('remote-control.index') }}"
           class="sidebar-item group flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 {{ request()->routeIs('remote-control.*') ? 'active bg-gradient-to-r from-green-50 to-transparent dark:from-green-900/20 dark:to-transparent border-l-[3px] border-green-500' : '' }}"
           :class="collapsed ? 'justify-center' : ''"
           :title="collapsed ? 'Télécommande' : ''">
            <div class="flex items-center justify-center w-8 h-8" :class="collapsed ? '' : 'mr-3'">
                <svg class="w-5 h-5 {{ request()->routeIs('remote-control.*') ? 'text-green-500' : 'text-gray-500 dark:text-gray-400 group-hover:text-green-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16l2.879-2.879m0 0a3 3 0 104.243-4.242m-4.243 4.242L9.121 17.879m0 0l3.536 3.535m-3.536-3.535A3 3 0 105.586 14.5m-3.535 3.536l3.535 3.535m0-7.07l3.536-3.536m0 0a3 3 0 104.243-4.242m-4.243 4.242L14.879 9.121m0 0l3.535-3.536m-3.535 3.536A3 3 0 1014.5 5.586"></path>
                </svg>
            </div>
            <span class="font-medium {{ request()->routeIs('remote-control.*') ? 'text-green-600 dark:text-green-400' : '' }}" x-show="!collapsed" x-transition>Télécommande</span>
        </a>
        @endcan

        <!-- Reports -->
        @can('view_reports')
        <a href="{{ route('reports.index') }}"
           class="sidebar-item group flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 {{ request()->routeIs('reports.*') ? 'active bg-gradient-to-r from-green-50 to-transparent dark:from-green-900/20 dark:to-transparent border-l-[3px] border-green-500' : '' }}"
           :class="collapsed ? 'justify-center' : ''"
           :title="collapsed ? 'Rapports' : ''">
            <div class="flex items-center justify-center w-8 h-8" :class="collapsed ? '' : 'mr-3'">
                <svg class="w-5 h-5 {{ request()->routeIs('reports.*') ? 'text-green-500' : 'text-gray-500 dark:text-gray-400 group-hover:text-green-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <span class="font-medium {{ request()->routeIs('reports.*') ? 'text-green-600 dark:text-green-400' : '' }}" x-show="!collapsed" x-transition>Rapports</span>
        </a>
        @endcan

        <!-- Withdrawal Requests -->
        @can('view_withdrawal_requests')
        <a href="{{ route('withdrawal-requests.index') }}"
           class="sidebar-item group flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 {{ request()->routeIs('withdrawal-requests.*') ? 'active bg-gradient-to-r from-green-50 to-transparent dark:from-green-900/20 dark:to-transparent border-l-[3px] border-green-500' : '' }}"
           :class="collapsed ? 'justify-center' : ''"
           :title="collapsed ? 'Demandes de retrait' : ''">
            <div class="flex items-center justify-center w-8 h-8" :class="collapsed ? '' : 'mr-3'">
                <svg class="w-5 h-5 {{ request()->routeIs('withdrawal-requests.*') ? 'text-green-500' : 'text-gray-500 dark:text-gray-400 group-hover:text-green-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h.01M17 16l4-4m0 0l-4-4m4 4H7"></path>
                </svg>
            </div>
            <span class="font-medium {{ request()->routeIs('withdrawal-requests.*') ? 'text-green-600 dark:text-green-400' : '' }}" x-show="!collapsed" x-transition>Demandes de retrait</span>
        </a>
        @endcan

        <!-- Settings -->
        @can('view_settings')
        <a href="{{ route('settings.index') }}"
           class="sidebar-item group flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 {{ request()->routeIs('settings.*') ? 'active bg-gradient-to-r from-green-50 to-transparent dark:from-green-900/20 dark:to-transparent border-l-[3px] border-green-500' : '' }}"
           :class="collapsed ? 'justify-center' : ''"
           :title="collapsed ? 'Paramètres' : ''">
            <div class="flex items-center justify-center w-8 h-8" :class="collapsed ? '' : 'mr-3'">
                <svg class="w-5 h-5 {{ request()->routeIs('settings.*') ? 'text-green-500' : 'text-gray-500 dark:text-gray-400 group-hover:text-green-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
            </div>
            <span class="font-medium {{ request()->routeIs('settings.*') ? 'text-green-600 dark:text-green-400' : '' }}" x-show="!collapsed" x-transition>Paramètres</span>
        </a>
        @endcan

    </nav>

    <!-- Sidebar Footer (Optional: for collapse button or user info) -->
    <div class="p-4 border-t border-gray-100 dark:border-gray-700">
        <button @click="collapsed = !collapsed" class="w-full flex items-center justify-center px-4 py-2 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="collapsed ? 'M13 5l7 7-7 7M5 12h14' : 'M11 19l-7-7 7-7m8 14l7-7-7-7'"></path>
            </svg>
            <span class="ml-2 font-medium" x-show="!collapsed" x-transition>Réduire</span>
        </button>
    </div>
</nav>