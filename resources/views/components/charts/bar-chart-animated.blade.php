@props([
    'data' => [],
    'labels' => [],
    'height' => 300,
    'title' => '',
    'color' => '#4acf7b'
])

<!-- Animated Bar Chart -->
<div class="w-full" x-data="barChart()" x-init="init()">
    @if($title)
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ $title }}</h3>
    @endif
    
    <svg class="w-full" height="{{ $height }}" viewBox="0 0 800 {{ $height }}">
        <defs>
            <!-- Gradient for bars -->
            <linearGradient id="barGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                <stop offset="0%" style="stop-color:{{ $color }};stop-opacity:1" />
                <stop offset="100%" style="stop-color:{{ $color }};stop-opacity:0.7" />
            </linearGradient>
            
            <!-- Shadow -->
            <filter id="barShadow">
                <feDropShadow dx="0" dy="4" stdDeviation="4" flood-color="#000000" flood-opacity="0.15"/>
            </filter>
            
            <!-- Glow on hover -->
            <filter id="barGlow">
                <feGaussianBlur stdDeviation="3" result="coloredBlur"/>
                <feMerge>
                    <feMergeNode in="coloredBlur"/>
                    <feMergeNode in="SourceGraphic"/>
                </feMerge>
            </filter>
        </defs>
        
        <!-- Grid lines -->
        <g class="grid-lines" opacity="0.1">
            @for($i = 0; $i <= 5; $i++)
                <line x1="60" 
                      y1="{{ 40 + ($height - 80) / 5 * $i }}" 
                      x2="780" 
                      y2="{{ 40 + ($height - 80) / 5 * $i }}" 
                      stroke="currentColor" 
                      stroke-width="1" 
                      stroke-dasharray="4,4"
                      class="text-gray-400 dark:text-gray-600" />
            @endfor
        </g>
        
        <!-- Y-axis labels -->
        <g class="y-axis-labels">
            <template x-for="(label, index) in yAxisLabels" :key="index">
                <text :x="50" 
                      :y="40 + ({{ $height }} - 80) / 5 * index + 5" 
                      text-anchor="end" 
                      class="text-xs fill-current text-gray-600 dark:text-gray-400"
                      x-text="label">
                </text>
            </template>
        </g>
        
        <!-- Bars -->
        <g class="bars">
            <template x-for="(bar, index) in bars" :key="index">
                <g class="bar-group cursor-pointer" 
                   @mouseenter="hoveredIndex = index"
                   @mouseleave="hoveredIndex = null">
                    
                    <!-- Bar rectangle -->
                    <rect :x="bar.x" 
                          :y="bar.currentY"
                          :width="bar.width" 
                          :height="bar.currentHeight"
                          fill="url(#barGradient)"
                          filter="url(#barShadow)"
                          :class="{'brightness-110': hoveredIndex === index}"
                          class="transition-all duration-300 rounded-t-lg"
                          rx="4">
                        
                        <!-- Animate on load -->
                        <animate attributeName="y"
                                 :from="{{ $height }} - 40"
                                 :to="bar.y"
                                 :begin="`${index * 0.1}s`"
                                 dur="0.8s"
                                 fill="freeze"
                                 calcMode="spline"
                                 keySplines="0.4 0 0.2 1" />
                        
                        <animate attributeName="height"
                                 from="0"
                                 :to="bar.height"
                                 :begin="`${index * 0.1}s`"
                                 dur="0.8s"
                                 fill="freeze"
                                 calcMode="spline"
                                 keySplines="0.4 0 0.2 1" />
                    </rect>
                    
                    <!-- Value label on top -->
                    <text :x="bar.x + bar.width / 2" 
                          :y="bar.currentY - 10" 
                          text-anchor="middle" 
                          class="text-sm font-bold fill-current transition-opacity duration-300"
                          :class="hoveredIndex === index ? 'opacity-100 text-eco-green-600 dark:text-eco-green-400' : 'opacity-0'"
                          x-text="bar.value">
                    </text>
                    
                    <!-- X-axis label -->
                    <text :x="bar.x + bar.width / 2" 
                          :y="{{ $height }} - 15" 
                          text-anchor="middle" 
                          class="text-xs fill-current text-gray-600 dark:text-gray-400"
                          x-text="bar.label">
                    </text>
                </g>
            </template>
        </g>
        
        <!-- Tooltip -->
        <g x-show="hoveredIndex !== null" x-cloak>
            <rect :x="bars[hoveredIndex]?.x - 10" 
                  :y="bars[hoveredIndex]?.y - 50" 
                  width="80" 
                  height="35" 
                  fill="#1f2937" 
                  rx="4" 
                  opacity="0.95"
                  filter="url(#barShadow)" />
            
            <text :x="bars[hoveredIndex]?.x + 30" 
                  :y="bars[hoveredIndex]?.y - 30" 
                  text-anchor="middle" 
                  class="text-sm font-bold fill-white"
                  x-text="bars[hoveredIndex]?.value + ' kWh'">
            </text>
        </g>
    </svg>
</div>

<script>
function barChart() {
    return {
        data: @json($data),
        labels: @json($labels),
        hoveredIndex: null,
        bars: [],
        yAxisLabels: [],
        
        init() {
            this.calculateBars();
        },
        
        calculateBars() {
            const maxValue = Math.max(...this.data, 1);
            const barWidth = (800 - 120) / this.data.length;
            const padding = barWidth * 0.2;
            const effectiveBarWidth = barWidth - padding;
            const chartHeight = {{ $height }} - 80;
            
            // Generate Y-axis labels
            this.yAxisLabels = [];
            for (let i = 0; i <= 5; i++) {
                this.yAxisLabels.push(Math.round((maxValue / 5) * (5 - i)));
            }
            
            // Generate bars
            this.bars = this.data.map((value, index) => {
                const height = (value / maxValue) * chartHeight;
                const y = 40 + chartHeight - height;
                
                return {
                    x: 80 + index * barWidth + padding / 2,
                    y: y,
                    currentY: {{ $height }} - 40,
                    width: effectiveBarWidth,
                    height: height,
                    currentHeight: 0,
                    value: value,
                    label: this.labels[index] || `Item ${index + 1}`
                };
            });
            
            // Animate bars
            setTimeout(() => {
                this.bars.forEach((bar, index) => {
                    setTimeout(() => {
                        bar.currentY = bar.y;
                        bar.currentHeight = bar.height;
                    }, index * 100);
                });
            }, 100);
        }
    }
}
</script>

