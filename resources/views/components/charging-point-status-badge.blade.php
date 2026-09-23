@props(['status', 'chargingPointId'])

@php
    $status = $status ?? 'unknown';
    $statusClasses = [
        'online' => 'bg-green-100 text-green-800',
        'offline' => 'bg-red-100 text-red-800',
        'maintenance' => 'bg-yellow-100 text-yellow-800',
        'unknown' => 'bg-gray-100 text-gray-800',
    ];
    $dotClasses = [
        'online' => 'bg-green-500',
        'offline' => 'bg-red-500',
        'maintenance' => 'bg-yellow-500',
        'unknown' => 'bg-gray-500',
    ];
    $displayText = [
        'online' => 'Online',
        'offline' => 'Hors ligne',
        'maintenance' => 'Maintenance',
        'unknown' => 'Inconnu',
    ];

    $currentStatusClass = $statusClasses[$status] ?? $statusClasses['unknown'];
    $currentDotClass = $dotClasses[$status] ?? $dotClasses['unknown'];
    $currentDisplayText = $displayText[$status] ?? $displayText['unknown'];
@endphp

<span id="cp-status-badge-{{ $chargingPointId }}" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $currentStatusClass }}">
    <span class="h-2 w-2 mr-1 rounded-full {{ $currentDotClass }}"></span>
    {{ $currentDisplayText }}
</span>