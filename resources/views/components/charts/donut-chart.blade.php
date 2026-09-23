@props([
    'data' => [],
    'labels' => [],
    'colors' => ['#4acf7b', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6'],
    'size' => 200,
    'strokeWidth' => 40,
    'title' => ''
])

<!-- Animated Donut Chart -->
<div class="w-full" x-data="donutChart()" x-init="init()">
    @if($title)
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 text-center">{{ $title }}</h3>
    @endif
    
    <div class="flex flex-col lg:flex-row items-center justify-center gap-8">
        <!-- Chart -->
        <div class="relative">
            <svg :width="size" 
                 :height="size" 
                 viewBox="0 0 {{ $size }} {{ $size }}"
                 class="transform -rotate-90">
                
                <defs>
                    <!-- Gradients for each segment -->
                    <template x-for="(segment, index) in segments" :key="index">
                        <linearGradient :id="'donutGradient' + index" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" :style="`stop-color:${segment.color};stop-opacity:1`" />
                            <stop offset="100%" :style="`stop-color:${segment.color};stop-opacity:0.7`" />
                        </linearGradient>
                    </template>
                    
                    <!-- Glow filter -->
                    <filter id="donutGlow">
                        <feGaussianBlur stdDeviation="2" result="coloredBlur"/>
                        <feMerge>
                            <feMergeNode in="coloredBlur"/>
                            <feMergeNode in="SourceGraphic"/>
                        </feMerge>
                    </filter>
                    
                    <!-- Shadow -->
                    <filter id="donutShadow">
                        <feDropShadow dx="0" dy="2" stdDeviation="3" flood-opacity="0.2"/>
                    </filter>
                </defs>
                
                <!-- Background circle -->
                <circle :cx="size / 2" 
                        :cy="size / 2" 
                        :r="radius"
                        fill="none"
                        stroke="currentColor"
                        :stroke-width="strokeWidth"
                        opacity="0.1"
                        class="text-gray-200 dark:text-gray-700" />
                
                <!-- Segments -->
                <template x-for="(segment, index) in segments" :key="index">
                    <circle :cx="size / 2" 
                            :cy="size / 2" 
                            :r="radius"
                            fill="none"
                            :stroke="`url(#donutGradient${index})`"
                            :stroke-width="hoveredIndex === index ? strokeWidth + 5 : strokeWidth"
                            stroke-linecap="round"
                            :stroke-dasharray="`${segment.currentDash} ${circumference - segment.currentDash}`"
                            :stroke-dashoffset="segment.offset"
                            :filter="hoveredIndex === index ? 'url(#donutGlow)' : 'url(#donutShadow)'"
                            class="transition-all duration-300 cursor-pointer"
                            @mouseenter="hoveredIndex = index"
                            @mouseleave="hoveredIndex = null">
                        
                        <!-- Animate segment -->
                        <animate attributeName="stroke-dasharray"
                                 :from="`0 ${circumference}`"
                                 :to="`${segment.dash} ${circumference - segment.dash}`"
                                 :begin="`${index * 0.2}s`"
                                 dur="1s"
                                 fill="freeze"
                                 calcMode="spline"
                                 keySplines="0.4 0 0.2 1" />
                    </circle>
                </template>
            </svg>
            
            <!-- Center content -->
            <div class="absolute inset-0 flex flex-col items-center justify-center">
                <template x-if="hoveredIndex !== null">
                    <div class="text-center">
                        <div class="text-3xl font-bold bg-gradient-to-br from-eco-green-500 to-eco-green-600 bg-clip-text text-transparent"
                             x-text="segments[hoveredIndex]?.percentage + '%'">
                        </div>
                        <div class="text-xs text-gray-600 dark:text-gray-400 mt-1 font-medium max-w-20 truncate"
                             x-text="segments[hoveredIndex]?.label">
                        </div>
                    </div>
                </template>
                <template x-if="hoveredIndex === null">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ __('dashboard.total') }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1"
                             x-text="total">
                        </div>
                    </div>
                </template>
            </div>
        </div>
        
        <!-- Legend -->
        <div class="flex flex-col gap-3">
            <template x-for="(segment, index) in segments" :key="index">
                <div class="flex items-center gap-3 cursor-pointer group"
                     @mouseenter="hoveredIndex = index"
                     @mouseleave="hoveredIndex = null">
                    
                    <!-- Color indicator -->
                    <div class="relative">
                        <div :style="`background: ${segment.color}`"
                             class="w-4 h-4 rounded transition-all duration-300"
                             :class="hoveredIndex === index ? 'scale-125 shadow-lg' : ''">
                        </div>
                        <div x-show="hoveredIndex === index"
                             :style="`background: ${segment.color}`"
                             class="absolute inset-0 w-4 h-4 rounded animate-ping opacity-75">
                        </div>
                    </div>
                    
                    <!-- Label and value -->
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-gray-900 dark:text-white truncate"
                             x-text="segment.label">
                        </div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">
                            <span x-text="segment.value"></span>
                            <span class="mx-1">•</span>
                            <span x-text="segment.percentage + '%'"></span>
                        </div>
                    </div>
                    
                    <!-- Arrow indicator -->
                    <svg class="w-4 h-4 text-gray-400 transition-all duration-300"
                         :class="hoveredIndex === index ? 'text-eco-green-500 transform translate-x-1' : ''"
                         fill="none" 
                         stroke="currentColor" 
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </template>
        </div>
    </div>
</div>

<script>
function donutChart() {
    return {
        data: @json($data),
        labels: @json($labels),
        colors: @json($colors),
        size: {{ $size }},
        strokeWidth: {{ $strokeWidth }},
        radius: 0,
        circumference: 0,
        hoveredIndex: null,
        segments: [],
        total: 0,
        
        init() {
            this.radius = (this.size - this.strokeWidth) / 2;
            this.circumference = 2 * Math.PI * this.radius;
            this.calculateSegments();
        },
        
        calculateSegments() {
            this.total = this.data.reduce((sum, val) => sum + val, 0);
            
            let currentOffset = 0;
            this.segments = this.data.map((value, index) => {
                const percentage = ((value / this.total) * 100).toFixed(1);
                const dash = (value / this.total) * this.circumference;
                const offset = -currentOffset;
                
                currentOffset += dash;
                
                return {
                    value: value,
                    label: this.labels[index] || `Item ${index + 1}`,
                    percentage: percentage,
                    color: this.colors[index % this.colors.length],
                    dash: dash,
                    currentDash: 0,
                    offset: offset
                };
            });
            
            // Animate segments
            setTimeout(() => {
                this.segments.forEach((segment, index) => {
                    setTimeout(() => {
                        segment.currentDash = segment.dash;
                    }, index * 200);
                });
            }, 100);
        }
    }
}
</script>

