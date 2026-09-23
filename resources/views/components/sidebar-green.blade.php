{{-- Sidebar Verte Élégante - Composant --}}
<aside class="fixed inset-y-0 left-0 z-50 w-64 bg-white dark:bg-gray-900 shadow-lg transform transition-transform duration-300 ease-in-out"
       :class="{
           'translate-x-0': !sidebarCollapsed || window.innerWidth >= 1024,
           '-translate-x-full': sidebarCollapsed && window.innerWidth < 1024,
           'lg:translate-x-0': window.innerWidth >= 1024
       }"
       x-data="{ 
           sidebarCollapsed: false,
           init() {
               this.$watch('sidebarCollapsed', value => {
                   if (window.innerWidth >= 1024) {
                       document.body.classList.toggle('sidebar-collapsed', value);
                   }
               });
           }
       }">
    
    <!-- Sidebar Header avec gradient vert -->
    <div class="h-16 bg-gradient-to-r from-green-500 to-green-600 flex items-center justify-between px-4 shadow-lg">
        <div class="flex items-center">
            @if(file_exists(public_path('images/evon-logo.png')))
                <img src="{{ asset('images/evon-logo.png') }}" alt="EVON Logo" class="h-8 w-auto">
            @else
                <div class="h-8 w-8 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                    <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
            @endif
            <span class="ml-3 text-xl font-bold text-white" x-show="!sidebarCollapsed">EVON</span>
        </div>
        
        <!-- Desktop Collapse Toggle -->
        <button @click="sidebarCollapsed = !sidebarCollapsed" 
                class="hidden lg:block p-1 rounded-lg hover:bg-white hover:bg-opacity-20 transition-colors"
                :title="sidebarCollapsed ? 'Étendre la sidebar' : 'Réduire la sidebar'">
            <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
            </svg>
        </button>
    </div>

    <!-- Sidebar Navigation -->
    <nav class="flex-1 overflow-y-auto py-4">
        <div class="px-3 space-y-1">
            <!-- Dashboard -->
            <a href="{{ route('dashboard') }}" 
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('dashboard') ? 'bg-green-500 text-white shadow-lg' : 'text-gray-700 dark:text-gray-200 hover:bg-green-50 dark:hover:bg-green-900/20 hover:text-green-600 dark:hover:text-green-400' }}"
               :title="sidebarCollapsed ? 'Tableau de bord' : ''">
                <svg class="h-5 w-5 {{ request()->routeIs('dashboard') ? 'text-green-500' : 'text-gray-500 group-hover:text-green-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v4H8V5z"/>
                </svg>
                <span class="ml-3" x-show="!sidebarCollapsed">{{ __('messages.dashboard') }}</span>
            </a>

            <!-- Intégrateurs -->
            @can('view_integrators')
            <a href="{{ route('integrators.index') }}" 
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('integrators.*') ? 'bg-green-500 text-white shadow-lg' : 'text-gray-700 dark:text-gray-200 hover:bg-green-50 dark:hover:bg-green-900/20 hover:text-green-600 dark:hover:text-green-400' }}"
               :title="sidebarCollapsed ? 'Intégrateurs' : ''">
                <svg class="h-5 w-5 {{ request()->routeIs('integrators.*') ? 'text-green-500' : 'text-gray-500 group-hover:text-green-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <span class="ml-3" x-show="!sidebarCollapsed">Intégrateurs</span>
            </a>
            @endcan

            <!-- Groupes -->
            <a href="{{ route('groups.index') }}" 
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('groups.*') ? 'bg-green-500 text-white shadow-lg' : 'text-gray-700 dark:text-gray-200 hover:bg-green-50 dark:hover:bg-green-900/20 hover:text-green-600 dark:hover:text-green-400' }}"
               :title="sidebarCollapsed ? 'Groupes' : ''">
                <svg class="h-5 w-5 {{ request()->routeIs('groups.*') ? 'text-green-500' : 'text-gray-500 group-hover:text-green-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <span class="ml-3" x-show="!sidebarCollapsed">Groupes</span>
            </a>

            <!-- Section Système -->
            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700" x-show="!sidebarCollapsed">
                <h3 class="px-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Système
                </h3>
            </div>

            <!-- Comptes Système (pour les intégrateurs et opérateurs système) -->
            @if(auth()->user()->hasRole('integrator') || auth()->user()->hasRole('operator'))
            
            <!-- Comptes Système - Vue différente selon le rôle -->
            @if(auth()->user()->hasRole('integrator'))
            <a href="{{ route('system-users.index') }}" 
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('system-users.*') ? 'bg-green-500 text-white shadow-lg' : 'text-gray-700 dark:text-gray-200 hover:bg-green-50 dark:hover:bg-green-900/20 hover:text-green-600 dark:hover:text-green-400' }}"
               :title="sidebarCollapsed ? 'Comptes Système' : ''">
                <svg class="h-5 w-5 {{ request()->routeIs('system-users.*') ? 'text-green-500' : 'text-gray-500 group-hover:text-green-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                <span class="ml-3" x-show="!sidebarCollapsed">Comptes Système</span>
            </a>
            @else
            <!-- Fallback vers le profil utilisateur -->
            <a href="{{ route('profile.edit') }}" 
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('profile.edit') ? 'bg-green-500 text-white shadow-lg' : 'text-gray-700 dark:text-gray-200 hover:bg-green-50 dark:hover:bg-green-900/20 hover:text-green-600 dark:hover:text-green-400' }}"
               :title="sidebarCollapsed ? 'Mon Profil' : ''">
                <svg class="h-5 w-5 {{ request()->routeIs('profile.edit') ? 'text-green-500' : 'text-gray-500 group-hover:text-green-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <span class="ml-3" x-show="!sidebarCollapsed">Mon Profil</span>
            </a>
            @endif
            @endif

            <!-- Créer Opérateur Système (uniquement pour les intégrateurs) -->
            @if(auth()->user()->hasRole('integrator') && Route::has('integrator.operators.create'))
            <a href="{{ route('integrator.operators.create') }}" 
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('integrator.operators.create') ? 'bg-green-500 text-white shadow-lg' : 'text-gray-700 dark:text-gray-200 hover:bg-green-50 dark:hover:bg-green-900/20 hover:text-green-600 dark:hover:text-green-400' }}"
               :title="sidebarCollapsed ? 'Créer Opérateur' : ''">
                <svg class="h-5 w-5 {{ request()->routeIs('integrator.operators.create') ? 'text-green-500' : 'text-gray-500 group-hover:text-green-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                <span class="ml-3" x-show="!sidebarCollapsed">Créer Opérateur</span>
            </a>
            @endif

            @if((auth()->user()->hasRole(['integrator','Integrator']) || auth()->user()->can('create_partners')) && Route::has('partners.create'))
            <a href="{{ route('partners.create') }}" 
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('partners.create') ? 'bg-green-500 text-white shadow-lg' : 'text-gray-700 dark:text-gray-200 hover:bg-green-50 dark:hover:bg-green-900/20 hover:text-green-600 dark:hover:text-green-400' }}">
                <svg class="h-5 w-5 {{ request()->routeIs('partners.create') ? 'text-green-500' : 'text-gray-500 group-hover:text-green-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span class="ml-3">Créer Partenaire</span>
            </a>
            @endif

            <!-- Partenaires/Opérateurs (uniquement pour les intégrateurs) -->
            @php
                $userRolesLower = auth()->user()->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
                $isPartnerOrOperator = in_array('partner', $userRolesLower) || in_array('operator', $userRolesLower);
            @endphp
            @if(auth()->user()->hasRole('integrator') && !$isPartnerOrOperator && Route::has('integrator.partners.index'))
            <a href="{{ route('integrator.partners.index') }}" 
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('integrator.partners.*') || request()->routeIs('integrator.operators.*') ? 'bg-green-500 text-white shadow-lg' : 'text-gray-700 dark:text-gray-200 hover:bg-green-50 dark:hover:bg-green-900/20 hover:text-green-600 dark:hover:text-green-400' }}"
               :title="sidebarCollapsed ? 'Partenaires/Opérateurs' : ''">
                <svg class="h-5 w-5 {{ request()->routeIs('integrator.partners.*') || request()->routeIs('integrator.operators.*') ? 'text-green-500' : 'text-gray-500 group-hover:text-green-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <span class="ml-3" x-show="!sidebarCollapsed">Partenaires/Opérateurs</span>
            </a>
            @endif



            <!-- Plans (pour les opérateurs) -->
            @if(auth()->user()->hasRole('operator') && Route::has('plans.index'))
            <a href="{{ route('plans.index') }}" 
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('plans.*') ? 'bg-green-500 text-white shadow-lg' : 'text-gray-700 dark:text-gray-200 hover:bg-green-50 dark:hover:bg-green-900/20 hover:text-green-600 dark:hover:text-green-400' }}"
               :title="sidebarCollapsed ? 'Plans' : ''">
                <svg class="h-5 w-5 {{ request()->routeIs('plans.*') ? 'text-green-500' : 'text-gray-500 group-hover:text-green-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span class="ml-3" x-show="!sidebarCollapsed">Plans</span>
            </a>
            @endif


            <!-- Réservations (pour les opérateurs) -->
            @if(auth()->user()->hasRole('operator') && Route::has('admin.reservations.index'))
            <a href="{{ route('admin.reservations.index') }}" 
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.reservations.*') ? 'bg-green-500 text-white shadow-lg' : 'text-gray-700 dark:text-gray-200 hover:bg-green-50 dark:hover:bg-green-900/20 hover:text-green-600 dark:hover:text-green-400' }}"
               :title="sidebarCollapsed ? 'Réservations' : ''">
                <svg class="h-5 w-5 {{ request()->routeIs('admin.reservations.*') ? 'text-green-500' : 'text-gray-500 group-hover:text-green-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span class="ml-3" x-show="!sidebarCollapsed">Réservations</span>
            </a>
            @endif

            <!-- Permissions Système (pour les intégrateurs) -->
            @if(auth()->user()->hasRole('integrator') && Route::has('system-permissions.index'))
            <a href="{{ route('system-permissions.index') }}" 
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('system-permissions.*') ? 'bg-green-500 text-white shadow-lg' : 'text-gray-700 dark:text-gray-200 hover:bg-green-50 dark:hover:bg-green-900/20 hover:text-green-600 dark:hover:text-green-400' }}"
               :title="sidebarCollapsed ? 'Permissions Système' : ''">
                <svg class="h-5 w-5 {{ request()->routeIs('system-permissions.*') ? 'text-green-500' : 'text-gray-500 group-hover:text-green-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <span class="ml-3" x-show="!sidebarCollapsed">Permissions Système</span>
            </a>
            @endif

            <!-- Audit Comptes Système (pour les intégrateurs) -->
            @if(auth()->user()->hasRole('integrator') && Route::has('system-audit.index'))
            <a href="{{ route('system-audit.index') }}" 
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('system-audit.*') ? 'bg-green-500 text-white shadow-lg' : 'text-gray-700 dark:text-gray-200 hover:bg-green-50 dark:hover:bg-green-900/20 hover:text-green-600 dark:hover:text-green-400' }}"
               :title="sidebarCollapsed ? 'Audit Comptes' : ''">
                <svg class="h-5 w-5 {{ request()->routeIs('system-audit.*') ? 'text-green-500' : 'text-gray-500 group-hover:text-green-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
                <span class="ml-3" x-show="!sidebarCollapsed">Audit Comptes</span>
            </a>
            @endif

            <!-- Configuration Système (pour les intégrateurs) -->
            @if(auth()->user()->hasRole('integrator') && Route::has('system-config.index'))
            <a href="{{ route('system-config.index') }}" 
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('system-config.*') ? 'bg-green-500 text-white shadow-lg' : 'text-gray-700 dark:text-gray-200 hover:bg-green-50 dark:hover:bg-green-900/20 hover:text-green-600 dark:hover:text-green-400' }}"
               :title="sidebarCollapsed ? 'Config Système' : ''">
                <svg class="h-5 w-5 {{ request()->routeIs('system-config.*') ? 'text-green-500' : 'text-gray-500 group-hover:text-green-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span class="ml-3" x-show="!sidebarCollapsed">Config Système</span>
            </a>
            @endif
            @endif

            <!-- Section Business -->
            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700" x-show="!sidebarCollapsed">
                <h3 class="px-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Business
                </h3>
            </div>

            <!-- Business Profiles (exclu pour opérateurs et partenaires) -->
            @php
                $userRolesLower = auth()->user()->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
                $isOperator = in_array('operator', $userRolesLower);
                $isPartner = in_array('partner', $userRolesLower);
            @endphp
            @can('view_business_profiles')
            @if(!$isOperator && !$isPartner)
            <a href="{{ route('business-profiles.index') }}" 
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('business-profiles.*') ? 'bg-green-500 text-white shadow-lg' : 'text-gray-700 dark:text-gray-200 hover:bg-green-50 dark:hover:bg-green-900/20 hover:text-green-600 dark:hover:text-green-400' }}"
               :title="sidebarCollapsed ? 'Business Profils' : ''">
                <svg class="h-5 w-5 {{ request()->routeIs('business-profiles.*') ? 'text-green-500' : 'text-gray-500 group-hover:text-green-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <span class="ml-3" x-show="!sidebarCollapsed">Business Profils</span>
            </a>
            @endif
            @endcan

            <!-- Partenaires -->
            @can('view_partners')
            <a href="{{ route('partners.index') }}" 
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('partners.*') ? 'bg-green-500 text-white shadow-lg' : 'text-gray-700 dark:text-gray-200 hover:bg-green-50 dark:hover:bg-green-900/20 hover:text-green-600 dark:hover:text-green-400' }}"
               :title="sidebarCollapsed ? 'Partenaires' : ''">
                <svg class="h-5 w-5 {{ request()->routeIs('partners.*') ? 'text-green-500' : 'text-gray-500 group-hover:text-green-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V8a2 2 0 01-2 2H6a2 2 0 01-2-2V6m8 0H8m0 0v2m0 0V4m0 4h8m-8 0H4m4 0V4"/>
                </svg>
                <span class="ml-3" x-show="!sidebarCollapsed">Partenaires</span>
            </a>
            @endcan

            <!-- Points de charge -->
            @can('view_charging_points')
            <a href="{{ route('charging-points.index') }}" 
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('charging-points.*') ? 'bg-green-500 text-white shadow-lg' : 'text-gray-700 dark:text-gray-200 hover:bg-green-50 dark:hover:bg-green-900/20 hover:text-green-600 dark:hover:text-green-400' }}"
               :title="sidebarCollapsed ? 'Points de charges' : ''">
                <svg class="h-5 w-5 {{ request()->routeIs('charging-points.*') ? 'text-green-500' : 'text-gray-500 group-hover:text-green-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <span class="ml-3" x-show="!sidebarCollapsed">Points de charges</span>
            </a>
            @endcan

            <!-- Stations -->
            @can('view_stations')
            <a href="{{ route('stations.index') }}" 
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('stations.*') ? 'bg-green-500 text-white shadow-lg' : 'text-gray-700 dark:text-gray-200 hover:bg-green-50 dark:hover:bg-green-900/20 hover:text-green-600 dark:hover:text-green-400' }}"
               :title="sidebarCollapsed ? 'Stations' : ''">
                <svg class="h-5 w-5 {{ request()->routeIs('stations.*') ? 'text-green-500' : 'text-gray-500 group-hover:text-green-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <span class="ml-3" x-show="!sidebarCollapsed">Stations</span>
            </a>
            @endcan

            <!-- Section Administration -->
            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700" x-show="!sidebarCollapsed">
                <h3 class="px-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Administration
                </h3>
            </div>

            <!-- Utilisateurs -->
            @can('manage_users')
            <a href="{{ route('admin.users.index') }}" 
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('admin.users.*') ? 'bg-green-500 text-white shadow-lg' : 'text-gray-700 dark:text-gray-200 hover:bg-green-50 dark:hover:bg-green-900/20 hover:text-green-600 dark:hover:text-green-400' }}"
               :title="sidebarCollapsed ? 'Utilisateurs' : ''">
                <svg class="h-5 w-5 {{ request()->routeIs('admin.users.*') ? 'text-green-500' : 'text-gray-500 group-hover:text-green-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                <span class="ml-3" x-show="!sidebarCollapsed">Utilisateurs</span>
            </a>
            @endcan

            <!-- Transactions -->
            @can('view_transactions')
            <a href="{{ route('transactions.index') }}" 
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('transactions.*') ? 'bg-green-500 text-white shadow-lg' : 'text-gray-700 dark:text-gray-200 hover:bg-green-50 dark:hover:bg-green-900/20 hover:text-green-600 dark:hover:text-green-400' }}"
               :title="sidebarCollapsed ? 'Transactions' : ''">
                <svg class="h-5 w-5 {{ request()->routeIs('transactions.*') ? 'text-green-500' : 'text-gray-500 group-hover:text-green-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                <span class="ml-3" x-show="!sidebarCollapsed">Transactions</span>
            </a>
            @endcan

            <!-- Reports -->
            @can('view_reports')
            <a href="{{ route('reports.index') }}" 
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 {{ request()->routeIs('reports.*') ? 'bg-green-500 text-white shadow-lg' : 'text-gray-700 dark:text-gray-200 hover:bg-green-50 dark:hover:bg-green-900/20 hover:text-green-600 dark:hover:text-green-400' }}"
               :title="sidebarCollapsed ? 'Rapports' : ''">
                <svg class="h-5 w-5 {{ request()->routeIs('reports.*') ? 'text-green-500' : 'text-gray-500 group-hover:text-green-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <span class="ml-3" x-show="!sidebarCollapsed">Rapports</span>
            </a>
            @endcan


        </div>

        <!-- User Profile Section -->
        <div class="mt-auto px-3 py-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center">
                <div class="h-10 w-10 bg-gradient-to-r from-green-500 to-green-600 rounded-full flex items-center justify-center">
                    <span class="text-sm font-medium text-white">{{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}</span>
                </div>
                <div class="ml-3" x-show="!sidebarCollapsed">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ Auth::user()->name ?? 'User' }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ Auth::user()->getRoleNames()->first() ?? 'Utilisateur' }}</p>
                </div>
            </div>
        </div>
    </nav>
</aside>
