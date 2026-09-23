@php
use App\Models\CreditRecharge;
// Always use the web guard for admin sidebar; a ClientUser (client guard) must never
// reach Gate/permission checks in this sidebar – those would cause a TypeError.
$user = auth('web')->user();
$userRolesLower = $user ? $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray() : [];
$isSuperAdmin = in_array('super_admin', $userRolesLower, true)
    || in_array('super-admin', $userRolesLower, true)
    || in_array('super admin', $userRolesLower, true);
$isAdmin = $user && (in_array('admin', $userRolesLower, true) || $isSuperAdmin);
$isPartnerOrOperator = in_array('partner', $userRolesLower) || in_array('operator', $userRolesLower);
$isIntegrator = in_array('integrator', $userRolesLower);
$isSimpleUser = !$isAdmin && !$isPartnerOrOperator && !$isIntegrator;
$dashboardRoute = $isSimpleUser ? 'dashboard.client' : 'dashboard';
@endphp

<!-- Logo with Toggle - Visible on all screens -->
<div class="evon-sidebar-header flex items-center px-3 py-2 relative" 
     dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" 
     style="margin-top: 0; padding-top: 0.5rem;"
     :class="sidebarCollapsed ? 'justify-center' : 'justify-between'">
    {{-- Logo Section --}}
    <div class="evon-sidebar-logo" style="margin: 0; padding: 0;" :class="sidebarCollapsed ? '' : 'flex-1'">
        <a href="{{ route($dashboardRoute) }}" 
           class="evon-logo-link flex items-center gap-2 group focus:outline-none focus:ring-2 focus:ring-eco-green-500 focus:ring-offset-2 rounded-lg p-1 transition-all duration-200 hover:bg-eco-green-50 dark:hover:bg-eco-green-900/20"
           :class="sidebarCollapsed ? 'justify-center' : ''"
           aria-label="{{ __('messages.back_to_dashboard') }}">
            
            {{-- Logo Icon --}}
            @if(file_exists(public_path('images/evon-logo.png')))
                <img src="{{ asset('images/evon-logo.png') }}" 
                     alt="EVON Logo" 
                     class="object-contain transition-all duration-200 group-hover:scale-110 flex-shrink-0 drop-shadow-lg"
                     :class="sidebarCollapsed ? 'h-16 w-16' : 'h-20 w-20'"
                     loading="lazy">
            @else
                <div class="bg-gradient-to-br from-eco-green-500 to-eco-green-600 rounded-lg flex items-center justify-center shadow-lg group-hover:shadow-xl transition-all duration-200 group-hover:scale-110 flex-shrink-0"
                     :class="sidebarCollapsed ? 'h-16 w-16' : 'h-20 w-20'"
                     role="img"
                     aria-label="Logo EVON">
                    <svg class="text-white" :class="sidebarCollapsed ? 'h-8 w-8' : 'h-10 w-10'" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
            @endif
            
            {{-- Logo Text - Hidden when collapsed --}}
            <div class="flex flex-col transition-all duration-300 overflow-hidden {{ app()->getLocale() === 'ar' ? 'text-right' : 'text-left' }}" 
                 x-show="!sidebarCollapsed"
                 x-cloak>
                <span class="text-xs text-gray-700 dark:text-gray-300 leading-tight whitespace-nowrap font-medium">{{ __('messages.charging_management') }}</span>
            </div>
        </a>
    </div>
    
    {{-- Toggle button moved to app.blade.php to avoid sidebar stacking context --}}
</div>

    <!-- Navigation -->
    <nav class="evon-sidebar-nav" aria-label="{{ __('messages.main_menu') }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
        {{-- Dashboard Link - Always first (single link, no duplication) --}}
        @php
            $isDashboardActive = request()->routeIs('dashboard') || request()->routeIs('dashboard.professional') || request()->routeIs('dashboard.client');
        @endphp
        <a 
            href="{{ route($dashboardRoute) }}" 
            class="evon-nav-item {{ $isDashboardActive ? 'evon-nav-item-active' : 'evon-nav-item-inactive' }} {{ app()->getLocale() === 'ar' ? 'flex-row-reverse text-right' : '' }}"
            aria-label="{{ __('dashboard.dashboard') }}"
            {{ $isDashboardActive ? 'aria-current="page"' : '' }}
            role="menuitem"
            tabindex="0"
            x-data="{ showTooltip: false }"
            @mouseenter="showTooltip = sidebarCollapsed"
            @mouseleave="showTooltip = false"
        >
            <span class="evon-nav-icon-wrapper evon-nav-icon {{ $isDashboardActive ? 'evon-nav-icon-active' : 'evon-nav-icon-inactive' }} {{ app()->getLocale() === 'ar' ? 'ml-0 mr-auto' : '' }}">
                @include('components.icons.lucide-home', [
                    'size' => 20,
                    'strokeWidth' => $isDashboardActive ? 2.5 : 2
                ])
            </span>
            <span class="evon-nav-label">{{ __('dashboard.dashboard') }}</span>
            
            {{-- Tooltip for collapsed state --}}
            <span class="evon-nav-item-tooltip {{ app()->getLocale() === 'ar' ? 'left-auto right-full mr-2' : '' }}"
                  x-show="showTooltip && sidebarCollapsed"
                  x-cloak>
                {{ __('dashboard.dashboard') }}
            </span>
        </a>

        @php
            // Pre-compute outside closure to guarantee correct Route::has results
            $bpRoute = $isAdmin ? 'admin.business-profiles.index' : 'business-profiles.index';
            $bpRouteExists = \Illuminate\Support\Facades\Route::has($bpRoute);
            $menuSections = [
                // Section: Administration (Admin/Super Admin only)
                [
                    'title' => __('messages.section_administration'),
                    'visible' => $isAdmin,
                    'items' => [
                        [
                            'label' => __('messages.monitoring'),
                            'route' => 'admin.monitoring.index',
                            'icon' => 'activity',
                            'permission' => 'view_reports'
                        ],
                        [
                            'label' => __('messages.users'),
                            'route' => 'admin.users.index',
                            'icon' => 'users',
                            'permission' => 'admin'
                        ],
                        [
                            'label' => __('messages.clients'),
                            'route' => 'admin.clients.index',
                            'icon' => 'user',
                            'permission' => 'admin'
                        ],
                        [
                            'label' => 'TVA',
                            'route' => 'admin.vat_rates.index',
                            'icon' => 'percent',
                            'permission' => 'admin'
                        ],
                        [
                            'label' => 'Abonnements',
                            'route' => 'admin.subscriptions.plans.index',
                            'icon' => 'list',
                            'permission' => 'admin'
                        ],
                    ]
                ],
                // Section: Gestion
                [
                    'title' => __('messages.section_management'),
                    'visible' => true,
                    'items' => [
                        [
                            'label' => __('messages.integrators'),
                            'route' => $isAdmin ? 'admin.integrators.index' : 'integrators.index',
                            'icon' => 'user-check',
                            'permission' => 'view_integrators'
                        ],
                        [
                            'label' => __('messages.partners_operators'),
                            'route' => $isAdmin ? 'admin.partners.index' : 'partners.index',
                            'icon' => 'building',
                            'permission' => $isPartnerOrOperator ? 'denied' : 'view_partners'
                        ],
                        [
                            'label' => __('messages.business_profiles'),
                            'route' => $bpRoute,
                            'route_exists' => $bpRouteExists,
                            'icon' => 'briefcase',
                            'permission' => $isAdmin ? 'admin' : ($isSimpleUser ? 'denied' : ($isPartnerOrOperator ? 'denied' : null))
                        ],
                        [
                            'label' => __('messages.groups'),
                            'route' => $isAdmin ? 'admin.groups.index' : 'groups.index',
                            'icon' => 'leaf',
                            'permission' => $isSimpleUser ? 'denied' : null
                        ],
                        [
                            'label' => __('messages.charging_points'),
                            'route' => 'charging-points.index',
                            'icon' => 'plug',
                            'permission' => $isSimpleUser ? 'denied' : null
                        ],
                        [
                            'label' => __('messages.stations'),
                            'route' => 'stations.index',
                            'icon' => 'server',
                            'permission' => $isSimpleUser ? 'denied' : ($isPartnerOrOperator ? 'denied' : 'view_stations')
                        ],
                        [
                            'label' => __('messages.steve_api'),
                            'route' => 'steve-charging-points.index', // Route déplacée hors du groupe charging-points
                            'icon' => 'zap',
                            'permission' => $isPartnerOrOperator ? 'denied' : 'create_charging_points'
                        ],
                        [
                            'label' => __('messages.ocpp_tags'),
                            'route' => 'ocpp-tags.index',
                            'icon' => 'tag',
                            'permission' => $isSimpleUser ? 'denied' : ($isPartnerOrOperator ? 'denied' : 'view_charging_points')
                        ],
                        [
                            'label' => __('messages.ocpp_transactions'),
                            'route' => 'steve-transactions.index',
                            'icon' => 'list',
                            'permission' => $isSimpleUser ? 'denied' : ($isPartnerOrOperator ? 'denied' : 'view_charging_points')
                        ],
                    ]
                ],
                // Section: Finances
                [
                    'title' => __('messages.section_finances'),
                    'visible' => true,
                    'items' => [
                        [
                            'label' => __('messages.transactions'),
                            'route' => 'transactions.index',
                            'icon' => 'receipt',
                            'permission' => null
                        ],
                        [
                            'label' => __('messages.credit_management'),
                            'route' => 'credits.index',
                            'icon' => 'wallet',
                            'permission' => $isSimpleUser ? null : 'denied'  // Visible only for clients
                        ],
                        [
                            'label' => __('messages.offline_credit_approvals'),
                            'route' => 'credits.offline.pending',
                            'icon' => 'coins',
                            'permission' => 'approve_offline_credits'
                        ],
                        [
                            'label' => __('messages.payments_admin'),
                            'route' => 'admin.payments.index',
                            'icon' => 'credit-card',
                            'permission' => 'admin'
                        ],
                        [
                            'label' => __('messages.pricing_plan'),
                            'route' => $isAdmin ? 'admin.pricing-plans.index' : 'plans.index',
                            'icon' => 'dollar-sign',
                            'permission' => $isSimpleUser ? 'denied' : null
                        ],
                    ]
                ],
                // Section: Opérations
                [
                    'title' => __('messages.section_operations'),
                    'visible' => true,
                    'items' => [
                        [
                            'label' => __('messages.reservations'),
                            'route' => $isAdmin ? 'admin.reservations.index' : 'reservations.index',
                            'icon' => 'calendar',
                            'permission' => null
                        ],
                        [
                            'label' => __('messages.pending_reservations'),
                            'route' => 'reservations.pending-approvals',
                            'icon' => 'bell',
                            'permission' => $isSimpleUser ? 'denied' : null,
                        ],
                        [
                            'label' => __('messages.reports'),
                            'route' => $isAdmin ? 'admin.reports.index' : 'reports.index',
                            'icon' => 'bar-chart',
                            'permission' => $isSimpleUser ? 'denied' : 'view_reports'
                        ],
                    ]
                ],
            ];
        @endphp

        {{-- DIRECT HARDCODED BYPASS: Business Profiles for admin/integrator --}}
        @if($isAdmin && \Illuminate\Support\Facades\Route::has('admin.business-profiles.index'))
            <!-- sidebar-patch-v1-admin -->
            <a href="{{ route('admin.business-profiles.index') }}"
               class="evon-nav-item {{ request()->routeIs('admin.business-profiles.*') ? 'evon-nav-item-active' : 'evon-nav-item-inactive' }} {{ app()->getLocale() === 'ar' ? 'flex-row-reverse text-right' : '' }}"
               aria-label="{{ __('messages.business_profiles') }}"
               x-data="{ showTooltip: false }"
               @mouseenter="showTooltip = sidebarCollapsed"
               @mouseleave="showTooltip = false">
                <span class="evon-nav-icon-wrapper evon-nav-icon {{ request()->routeIs('admin.business-profiles.*') ? 'evon-nav-icon-active' : 'evon-nav-icon-inactive' }}">
                    @include('components.icons.lucide-briefcase', ['size' => 20, 'strokeWidth' => 2])
                </span>
                <span class="evon-nav-label">{{ __('messages.business_profiles') }}</span>
                <span class="evon-nav-item-tooltip" x-show="showTooltip && sidebarCollapsed" x-cloak>{{ __('messages.business_profiles') }}</span>
            </a>
        @elseif(!$isAdmin && ($isIntegrator) && \Illuminate\Support\Facades\Route::has('business-profiles.index'))
            <!-- sidebar-patch-v1-integrator -->
            <a href="{{ route('business-profiles.index') }}"
               class="evon-nav-item {{ request()->routeIs('business-profiles.*') ? 'evon-nav-item-active' : 'evon-nav-item-inactive' }} {{ app()->getLocale() === 'ar' ? 'flex-row-reverse text-right' : '' }}"
               aria-label="{{ __('messages.business_profiles') }}"
               x-data="{ showTooltip: false }"
               @mouseenter="showTooltip = sidebarCollapsed"
               @mouseleave="showTooltip = false">
                <span class="evon-nav-icon-wrapper evon-nav-icon {{ request()->routeIs('business-profiles.*') ? 'evon-nav-icon-active' : 'evon-nav-icon-inactive' }}">
                    @include('components.icons.lucide-briefcase', ['size' => 20, 'strokeWidth' => 2])
                </span>
                <span class="evon-nav-label">{{ __('messages.business_profiles') }}</span>
                <span class="evon-nav-item-tooltip" x-show="showTooltip && sidebarCollapsed" x-cloak>{{ __('messages.business_profiles') }}</span>
            </a>
        @endif
        {{-- END DIRECT HARDCODED BYPASS --}}

        @foreach($menuSections as $section)
            @if($section['visible'])
                @php
                    $visibleItems = collect($section['items'])->filter(function ($item) {
                        $routeOk = isset($item['route_exists'])
                            ? $item['route_exists']
                            : (isset($item['route']) && \Illuminate\Support\Facades\Route::has($item['route']));
                        if (!$routeOk) {
                            return false;
                        }
                        if (!isset($item['permission']) || $item['permission'] === null) {
                            return true;
                        }

                        // Admin sidebar only evaluates permissions for web-guard users.
                        // If a ClientUser is the active guard user, deny all items.
                        if (!auth('web')->check()) {
                            return false;
                        }

                        if ($item['permission'] === 'denied') {
                            return false;
                        }

                        if ($item['permission'] === 'admin') {
                            return auth('web')->user()->hasAnyRole([
                                'admin',
                                'Admin',
                                'super_admin',
                                'super-admin',
                                'super admin',
                                'Super Admin',
                                'Super-Admin',
                            ]);
                        }

                        // Super admin sees all non-denied items
                        if (auth('web')->user()->hasAnyRole([
                            'super_admin',
                            'super-admin',
                            'super admin',
                            'Super Admin',
                            'Super-Admin',
                        ])) {
                            return true;
                        }

                        return auth('web')->user()->can($item['permission']);
                    });
                @endphp
                
                @if($visibleItems->isNotEmpty())
                    {{-- Section Title --}}
                    <div class="evon-nav-section-title {{ app()->getLocale() === 'ar' ? 'text-right' : 'text-left' }}">{{ $section['title'] }}</div>
                    
                    {{-- Section Items --}}
                    @foreach($visibleItems as $item)
                        @php
                            $activePattern = $item['active']
                                ?? (\Illuminate\Support\Str::endsWith($item['route'], '.index')
                                    ? \Illuminate\Support\Str::replaceLast('.index', '.*', $item['route'])
                                    : $item['route'] . '*');
                            $isActive = request()->routeIs($activePattern);
                            $hasBadge = isset($item['badge']) && $item['badge'] > 0;
                        @endphp
                        <a 
                            href="{{ route($item['route']) }}" 
                            class="evon-nav-item {{ $isActive ? 'evon-nav-item-active' : 'evon-nav-item-inactive' }} {{ app()->getLocale() === 'ar' ? 'flex-row-reverse text-right' : '' }}"
                            aria-label="{{ $item['label'] }}"
                            {{ $isActive ? 'aria-current="page"' : '' }}
                            x-data="{ showTooltip: false }"
                            @mouseenter="showTooltip = sidebarCollapsed"
                            @mouseleave="showTooltip = false"
                        >
                            <span class="evon-nav-icon-wrapper evon-nav-icon {{ $isActive ? 'evon-nav-icon-active' : 'evon-nav-icon-inactive' }} {{ app()->getLocale() === 'ar' ? 'ml-0 mr-auto' : '' }}">
                                @include('components.icons.lucide-' . $item['icon'], [
                                    'size' => 20,
                                    'strokeWidth' => $isActive ? 2.5 : 2
                                ])
                                
                                {{-- Badge for collapsed state --}}
                                @if($hasBadge)
                                    <span class="evon-nav-badge {{ $item['badge_class'] ?? 'bg-red-500' }} text-white"
                                          x-show="sidebarCollapsed">
                                        {{ $item['badge'] > 99 ? '99+' : $item['badge'] }}
                                    </span>
                                @endif
                            </span>
                            
                            <span class="evon-nav-label">
                                {{ $item['label'] }}
                            </span>
                            
                            {{-- Badge for expanded state --}}
                            @if($hasBadge)
                                <span class="evon-nav-badge {{ $item['badge_class'] ?? 'bg-red-500' }} text-white {{ app()->getLocale() === 'ar' ? 'mr-auto' : 'ml-auto' }}"
                                      x-show="!sidebarCollapsed"
                                      x-cloak>
                                    {{ $item['badge'] > 99 ? '99+' : $item['badge'] }}
                                </span>
                            @endif
                            
                            {{-- Tooltip for collapsed state --}}
                            <span class="evon-nav-item-tooltip {{ app()->getLocale() === 'ar' ? 'left-auto right-full mr-2' : '' }}"
                                  x-show="showTooltip && sidebarCollapsed"
                                  x-cloak>
                                {{ $item['label'] }}
                                @if($hasBadge)
                                    <span class="{{ app()->getLocale() === 'ar' ? 'mr-2' : 'ml-2' }} px-1.5 py-0.5 bg-red-600 rounded text-xs">{{ $item['badge'] }}</span>
                                @endif
                            </span>
                        </a>
                    @endforeach
                @endif
            @endif
        @endforeach
    </nav>

    <!-- Settings at bottom -->
    <div class="evon-sidebar-footer" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
        @php
            $isSettingsActive = $isAdmin
                ? request()->routeIs('admin.system-settings.*')
                : request()->routeIs('settings.*');
            $settingsRoute = $isAdmin ? 'admin.system-settings.index' : 'settings.index';
        @endphp
        <a
            href="{{ route($settingsRoute) }}"
            class="evon-nav-item {{ $isSettingsActive ? 'evon-nav-item-active' : 'evon-nav-item-inactive' }} {{ app()->getLocale() === 'ar' ? 'flex-row-reverse text-right' : '' }}"
            aria-label="{{ __('dashboard.settings') }}"
            {{ $isSettingsActive ? 'aria-current="page"' : '' }}
            x-data="{ showTooltip: false }"
            @mouseenter="showTooltip = sidebarCollapsed"
            @mouseleave="showTooltip = false"
        >
            <span class="evon-nav-icon-wrapper evon-nav-icon {{ $isSettingsActive ? 'evon-nav-icon-active' : 'evon-nav-icon-inactive' }} {{ app()->getLocale() === 'ar' ? 'ml-0 mr-auto' : '' }}">
                @include('components.icons.lucide-settings', ['size' => 20, 'strokeWidth' => $isSettingsActive ? 2.5 : 2])
            </span>
            <span class="evon-nav-label">{{ __('dashboard.settings') }}</span>
            
            {{-- Tooltip for collapsed state --}}
            <span class="evon-nav-item-tooltip {{ app()->getLocale() === 'ar' ? 'left-auto right-full mr-2' : '' }}"
                  x-show="showTooltip && sidebarCollapsed"
                  x-cloak>
                {{ __('dashboard.settings') }}
            </span>
        </a>
    </div>
