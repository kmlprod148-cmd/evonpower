@php
    $settingsItems = [
        [
            'route' => 'settings.index',
            'label' => 'General',
            'permission' => 'view_general_settings'
        ],
        [
            'route' => 'settings.security',
            'label' => 'Security',
            'permission' => 'view_security_settings'
        ],
        [
            'route' => 'settings.notifications',
            'label' => 'Notifications',
            'permission' => 'view_notification_settings'
        ],
        [
            'route' => 'settings.api',
            'label' => 'API',
            'permission' => 'view_api_settings'
        ],
        [
            'route' => 'settings.commission-plans',
            'label' => 'Business Plans',
            'permission' => 'view_commission_settings'
        ],
        [
            'route' => 'settings.commission-dashboard',
            'label' => 'Business Dashboard',
            'permission' => 'view_commission_settings'
        ]
    ];
@endphp

@foreach($settingsItems as $item)
    @can($item['permission'])
    <a href="{{ route($item['route']) ?? '#' }}" 
       class="submenu-item {{ request()->routeIs($item['route']) ? 'active' : '' }}">
        <span>{{ __($item['label']) }}</span>
    </a>
    @endcan
@endforeach