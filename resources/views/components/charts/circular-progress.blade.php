@props([
    'percentage' => 75,
    'size' => 120,
    'strokeWidth' => 10,
    'label' => '',
    'color' => '#4acf7b',
    'animated' => true
])

@php
    $radius = ($size - $strokeWidth) / 2;
    $circumference = 2 * pi() * $radius;
    $offset = $circumference - ($percentage / 100) * $circumference;
@endphp

<!-- Circular Progress Chart -->
<div class="relative inline-flex items-center justify-center" 
     x-data="circularProgress({{ $percentage }}, {{ $circumference }})"
     x-init="init()">
    
    <svg width="{{ $size }}" 
         height="{{ $size }}" 
         viewBox="0 0 {{ $size }} {{ $size }}"
         class="transform -rotate-90">
        
        <defs>
            <!-- Gradient -->
            <linearGradient id="progressGradient{{ md5($label) }}" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" style="stop-color:{{ $color }};stop-opacity:1" />
                <stop offset="100%" style="stop-color:{{ $color }};stop-opacity:0.6" />
            </linearGradient>
            
            <!-- Glow filter -->
            <filter id="progressGlow{{ md5($label) }}">
                <feGaussianBlur stdDeviation="3" result="coloredBlur"/>
                <feMerge>
                    <feMergeNode in="coloredBlur"/>
                    <feMergeNode in="SourceGraphic"/>
                </feMerge>
            </filter>
            
            <!-- Shadow -->
            <filter id="progressShadow{{ md5($label) }}">
                <feDropShadow dx="0" dy="2" stdDeviation="4" flood-opacity="0.3"/>
            </filter>
        </defs>
        
        <!-- Background circle -->
        <circle cx="{{ $size / 2 }}" 
                cy="{{ $size / 2 }}" 
                r="{{ $radius }}"
                stroke="currentColor"
                stroke-width="{{ $strokeWidth }}"
                fill="none"
                opacity="0.1"
                class="text-gray-300 dark:text-gray-600" />
        
        <!-- Progress circle -->
        <circle cx="{{ $size / 2 }}" 
                cy="{{ $size / 2 }}" 
                r="{{ $radius }}"
                stroke="url(#progressGradient{{ md5($label) }})"
                stroke-width="{{ $strokeWidth }}"
                fill="none"
                stroke-linecap="round"
                filter="url(#progressGlow{{ md5($label) }})"
                :stroke-dasharray="circumference"
                :stroke-dashoffset="currentOffset"
                class="transition-all duration-1000 ease-out"
                style="stroke-dasharray: {{ $circumference }}; stroke-dashoffset: {{ $circumference }}">
            
            @if($animated)
            <animate attributeName="stroke-dashoffset"
                     from="{{ $circumference }}"
                     to="{{ $offset }}"
                     dur="1.5s"
                     fill="freeze"
                     calcMode="spline"
                     keySplines="0.4 0 0.2 1" />
            @endif
        </circle>
        
        <!-- Animated dots at the end -->
        @if($animated)
        <circle cx="{{ $size / 2 }}" 
                cy="{{ $strokeWidth / 2 }}" 
                r="{{ $strokeWidth / 2 - 2 }}"
                fill="{{ $color }}"
                filter="url(#progressGlow{{ md5($label) }})">
            <animateTransform attributeName="transform"
                              type="rotate"
                              from="0 {{ $size / 2 }} {{ $size / 2 }}"
                              to="{{ 360 * ($percentage / 100) }} {{ $size / 2 }} {{ $size / 2 }}"
                              dur="1.5s"
                              fill="freeze" />
        </circle>
        @endif
    </svg>
    
    <!-- Center content -->
    <div class="absolute inset-0 flex flex-col items-center justify-center">
        <span class="text-3xl font-bold bg-gradient-to-br from-eco-green-500 to-eco-green-600 bg-clip-text text-transparent"
              x-text="Math.round(currentPercentage) + '%'">
            {{ $percentage }}%
        </span>
        @if($label)
        <span class="text-xs text-gray-600 dark:text-gray-400 mt-1 font-medium">{{ $label }}</span>
        @endif
    </div>
    
    <!-- Pulse effect -->
    @if($animated)
    <div class="absolute inset-0 rounded-full animate-ping opacity-20"
         style="background: {{ $color }}; animation-duration: 2s;"></div>
    @endif
</div>

<script>
function circularProgress(targetPercentage, circumference) {
    return {
        currentPercentage: 0,
        currentOffset: circumference,
        circumference: circumference,
        
        init() {
            setTimeout(() => {
                this.animateProgress(targetPercentage);
            }, 100);
        },
        
        animateProgress(target) {
            const duration = 1500;
            const steps = 60;
            const stepDuration = duration / steps;
            const increment = target / steps;
            let current = 0;
            
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                this.currentPercentage = current;
                this.currentOffset = this.circumference - (current / 100) * this.circumference;
            }, stepDuration);
        }
    }
}
</script>

