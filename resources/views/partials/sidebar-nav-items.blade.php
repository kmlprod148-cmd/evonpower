@php
// Build navigation items based on user role
$navItems = [];
$user = auth()->user();

// Intégrateurs - Only for admins
if ($user && $user->hasRole(['admin', 'super-admin'])) {
    $navItems[] = ['route' => 'integrators.index', 'icon' => 'svg/1.svg', 'label' => 'Intégrateurs'];
}

// Groupes - For admins and integrators
if ($user && $user->hasRole(['admin', 'super-admin', 'integrator'])) {
    $navItems[] = ['route' => 'groups.index', 'icon' => 'svg/2.svg', 'label' => 'Groupes'];
}

// Plans tarifaires - For admins
if ($user && $user->hasRole(['admin', 'super-admin'])) {
    $navItems[] = ['route' => 'plans.index', 'icon' => 'svg/3.svg', 'label' => 'Plans tarifaires'];
}

// Partenaires/Opérateurs - For admins and integrators (but not for partner/operator role users)
if ($user && $user->hasRole(['admin', 'super-admin', 'integrator'])) {
    $userRolesLower = $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
    $isPartnerOrOperator = in_array('partner', $userRolesLower) || in_array('operator', $userRolesLower);
    if (!$isPartnerOrOperator) {
        $navItems[] = ['route' => 'partners.index', 'icon' => 'svg/6.svg', 'label' => 'Partenaires/Opérateurs'];
    }
}

// Points de charge - For admins
if ($user && $user->hasRole(['admin', 'super-admin'])) {
    $navItems[] = ['route' => 'charging-points.index', 'icon' => 'svg/7.svg', 'label' => 'Points de charge'];
}

// Réservations - For admins
if ($user && $user->hasRole(['admin', 'super-admin'])) {
    $navItems[] = ['route' => 'admin.reservations.index', 'icon' => 'svg/4.svg', 'label' => 'Réservations'];
}

// Utilisateurs - For admins only (integrators see Opérateurs elsewhere)
if ($user && $user->hasRole(['admin', 'super-admin'])) {
    $navItems[] = ['route' => 'admin.users.index', 'icon' => 'svg/8.svg', 'label' => 'Utilisateurs'];
}

// Transactions - For admins
if ($user && $user->hasRole(['admin', 'super-admin'])) {
    $navItems[] = ['route' => 'transactions.index', 'icon' => 'svg/9.svg', 'label' => 'Transactions'];
}

// Business Profiles - For admins and integrators
if ($user && $user->hasRole(['admin', 'super-admin', 'integrator'])) {
    $navItems[] = ['route' => 'business-profiles.index', 'icon' => 'svg/9.svg', 'label' => 'Business Profiles'];
}

// Rapports - For admins
if ($user && $user->hasRole(['admin', 'super-admin'])) {
    $navItems[] = ['route' => 'reports.index', 'icon' => 'svg/10.svg', 'label' => 'Rapports'];
}

// Contrôle à distance - For admins
if ($user && $user->hasRole(['admin', 'super-admin'])) {
    $navItems[] = ['route' => 'remote-control.index', 'icon' => 'svg/11.svg', 'label' => 'Contrôle à distance'];
}
@endphp

@foreach($navItems as $item)
<a href="{{ route($item['route']) ?? '#' }}"
   class="sidebar-item {{ request()->routeIs($item['route'].'.*') ? 'active' : '' }}">
    <img src="{{ asset($item['icon']) }}" alt="{{ $item['label'] }}" class="w-5 h-5 mr-3">
    <span>{{ $item['label'] }}</span>
</a>
@endforeach