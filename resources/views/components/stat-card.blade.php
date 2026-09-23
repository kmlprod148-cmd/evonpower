@props([
    'label' => '',
    'value' => '0',
    'icon' => 'activity',
    'variant' => 'primary', // primary, success, warning, info, danger
    'trend' => null, // 'up', 'down', null
    'trendValue' => null,
    'description' => null,
    'animated' => true
])

@php
    $iconMap = [
        'activity' => 'M22 12h-4l-3 9L9 3l-3 9H2',
        'users' => 'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2',
        'zap' => 'M13 2L3 14h9l-1 8 10-12h-9l1-8z',
        'dollar' => 'M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6',
        'trending-up' => 'M23 6l-9.5 9.5-5-5L1 18',
        'trending-down' => 'M23 18L13.5 8.5l-5 5L1 6',
        'battery' => 'M2 7h14a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2z',
        'credit-card' => 'M1 4h22v5H1zM1 13h22v7H1z',
    ];
    
    $iconPath = $iconMap[$icon] ?? $iconMap['activity'];
@endphp

<div class="evon-stat-card variant-{{ $variant }} {{ $animated ? 'evon-animate-fade-in-up' : '' }}">
    <!-- Header -->
    <div class="evon-stat-card-header">
        <div class="evon-stat-card-icon">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPath }}"/>
            </svg>
        </div>
        
        @if($trend)
        <div class="evon-stat-badge {{ $trend === 'up' ? 'positive' : 'negative' }}">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                @if($trend === 'up')
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                @else
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                @endif
            </svg>
            {{ $trendValue }}
        </div>
        @endif
    </div>
    
    <!-- Body -->
    <div class="evon-stat-card-body">
        <div class="evon-stat-label">{{ $label }}</div>
        <div class="evon-stat-value">{{ $value }}</div>
        
        @if($description)
        <div class="evon-stat-description">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ $description }}
        </div>
        @endif
    </div>
</div>

