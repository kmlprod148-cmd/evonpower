@props([
    'title' => '',
    'value' => '0',
    'currency' => 'MAD',
    'icon' => 'fa-chart-line',
    'iconBgColor' => 'primary',
    'trend' => null,
    'trendValue' => null,
    'description' => null,
    'link' => null,
])

<div class="card border-0 shadow-sm h-100">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h6 class="text-muted mb-1">{{ $title }}</h6>
                <h3 class="mb-0 fw-bold">
                    @if(is_numeric($value))
                        {{ number_format($value, 2) }}
                    @else
                        {{ $value }}
                    @endif
                    @if($currency)
                        <small class="text-muted fw-normal">{{ $currency }}</small>
                    @endif
                </h3>
                
                @if($trend || $trendValue)
                    <div class="mt-2">
                        @if($trend === 'up')
                            <span class="badge bg-success bg-opacity-10 text-success">
                                <i class="fas fa-arrow-up me-1"></i>
                                {{ $trendValue }}
                            </span>
                        @elseif($trend === 'down')
                            <span class="badge bg-danger bg-opacity-10 text-danger">
                                <i class="fas fa-arrow-down me-1"></i>
                                {{ $trendValue }}
                            </span>
                        @endif
                    </div>
                @endif
                
                @if($description)
                    <p class="text-muted small mb-0 mt-1">{{ $description }}</p>
                @endif
            </div>
            <div class="bg-{{ $iconBgColor }} bg-opacity-10 p-3 rounded">
                <i class="fas {{ $icon }} text-{{ $iconBgColor }} fs-4"></i>
            </div>
        </div>
        
        @if($link)
            <div class="mt-3">
                <a href="{{ $link }}" class="text-decoration-none small">
                    {{ __('actions.view_details') }} <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
        @endif
    </div>
</div>
