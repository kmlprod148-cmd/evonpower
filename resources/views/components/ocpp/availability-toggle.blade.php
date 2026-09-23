@props([
    'chargingPointId',
    'connectorId' => 0,
    'currentStatus' => 'Operative',
    'size' => 'md',
    'showLabel' => true,
])

@php
    $isOperative = $currentStatus === 'Operative';
    
    $sizeClasses = match($size) {
        'sm' => 'btn-sm',
        'lg' => 'btn-lg',
        default => '',
    };
    
    $buttonClass = $isOperative 
        ? 'btn btn-outline-danger ' . $sizeClasses
        : 'btn btn-outline-success ' . $sizeClasses;
    
    $label = $isOperative ? __('messages.disable') : __('messages.enable');
    $statusLabel = $isOperative ? __('messages.operational') : __('messages.out_of_service');
@endphp

<div class="availability-toggle-wrapper d-inline-flex align-items-center gap-2">
    {{-- Status Badge --}}
    @if($showLabel)
    <span 
        class="badge {{ $isOperative ? 'bg-success' : 'bg-danger' }}"
        data-connector-status="{{ $connectorId }}"
    >
        {{ $statusLabel }}
    </span>
    @endif

    {{-- Toggle Button --}}
    <button 
        type="button"
        class="availability-toggle-btn {{ $buttonClass }}"
        data-charging-point-id="{{ $chargingPointId }}"
        data-connector-id="{{ $connectorId }}"
        data-current-status="{{ $currentStatus }}"
        {{ $attributes }}
    >
        @if($isOperative)
            <svg class="w-4 h-4 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 16px; height: 16px;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        @else
            <svg class="w-4 h-4 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 16px; height: 16px;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        @endif
        <span>{{ $label }}</span>
    </button>
</div>
