@props([
    'stats' => []
])

@php
    // Structure des statistiques par défaut
    $defaultStats = [
        [
            'label' => __('dashboard.online_points'),
            'value' => $stats['onlinePoints'] ?? '0',
            'icon' => 'zap',
            'variant' => 'success',
            'trend' => 'up',
            'trendValue' => ($stats['availabilityRate'] ?? '0') . '%',
            'description' => ($stats['totalPoints'] ?? '0') . ' ' . __('dashboard.total')
        ],
        [
            'label' => __('dashboard.active_sessions'),
            'value' => $stats['activeTransactions'] ?? '0',
            'icon' => 'activity',
            'variant' => 'primary',
            'trend' => null,
            'trendValue' => null,
            'description' => __('dashboard.charging_now')
        ],
        [
            'label' => __('dashboard.revenue'),
            'value' => number_format($stats['todayRevenue'] ?? 0, 0) . ' MAD',
            'icon' => 'dollar',
            'variant' => 'warning',
            'trend' => 'up',
            'trendValue' => '+12%',
            'description' => ($stats['todayTransactions'] ?? '0') . ' ' . __('dashboard.transactions')
        ],
        [
            'label' => __('dashboard.energy'),
            'value' => number_format($stats['todayEnergy'] ?? 0, 1) . ' kWh',
            'icon' => 'battery',
            'variant' => 'info',
            'trend' => 'up',
            'trendValue' => '+8%',
            'description' => __('dashboard.distributed')
        ],
    ];
@endphp

<div class="evon-stats-grid">
    @foreach($defaultStats as $stat)
        <x-stat-card
            :label="$stat['label']"
            :value="$stat['value']"
            :icon="$stat['icon']"
            :variant="$stat['variant']"
            :trend="$stat['trend']"
            :trendValue="$stat['trendValue']"
            :description="$stat['description']"
        />
    @endforeach
</div>

