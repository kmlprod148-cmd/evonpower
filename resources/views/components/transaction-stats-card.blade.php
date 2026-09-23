@props([
    'title',
    'value',
    'icon' => 'fa-chart-bar',
    'type' => 'info',
    'format' => 'number'
])

@php
    $typeClasses = [
        'info' => 'bg-info-light text-info',
        'success' => 'bg-success-light text-success',
        'warning' => 'bg-warning-light text-warning',
        'danger' => 'bg-danger-light text-danger',
        'primary' => 'bg-primary-light text-primary',
        'secondary' => 'bg-secondary-light text-secondary',
    ];
    
    $typeClass = $typeClasses[$type] ?? $typeClasses['info'];
    
    $formattedValue = $value;
    if ($format === 'currency') {
        $formattedValue = number_format($value, 2) . ' €';
    } elseif ($format === 'number') {
        $formattedValue = number_format($value);
    }
@endphp

<div class="col-lg-3 col-md-6">
    <div class="card border-0 shadow-sm stat-card stat-card-{{ $type }}">
        <div class="card-body">
            <div class="d-flex align-items-center">
                <div class="stat-icon {{ $typeClass }} me-3">
                    <i class="fas {{ $icon }}"></i>
                </div>
                <div class="flex-grow-1">
                    <h6 class="card-title text-muted mb-1">{{ $title }}</h6>
                    <h4 class="fw-bold mb-0">{{ $formattedValue }}</h4>
                </div>
            </div>
        </div>
    </div>
</div>