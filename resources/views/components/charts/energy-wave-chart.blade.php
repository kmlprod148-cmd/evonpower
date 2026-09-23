@props(['data' => [], 'height' => 200])

<!-- Energy Wave Chart - Animated SVG -->
<div class="relative" x-data="energyWaveChart()" x-init="init()">
    <svg class="w-full" :height="height" viewBox="0 0 800 {{ $height }}" preserveAspectRatio="none">
        <defs>
            <!-- Gradient for energy wave -->
            <linearGradient id="energyGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                <stop offset="0%" style="stop-color:#4acf7b;stop-opacity:0.8" />
                <stop offset="50%" style="stop-color:#34d399;stop-opacity:0.4" />
                <stop offset="100%" style="stop-color:#10b981;stop-opacity:0.1" />
            </linearGradient>
            
            <!-- Glow effect -->
            <filter id="glow">
                <feGaussianBlur stdDeviation="4" result="coloredBlur"/>
                <feMerge>
                    <feMergeNode in="coloredBlur"/>
                    <feMergeNode in="SourceGraphic"/>
                </feMerge>
            </filter>
            
            <!-- Shadow -->
            <filter id="shadow" x="-50%" y="-50%" width="200%" height="200%">
                <feDropShadow dx="0" dy="4" stdDeviation="6" flood-color="#000000" flood-opacity="0.2"/>
            </filter>
        </defs>
        
        <!-- Grid lines -->
        <g class="grid-lines" opacity="0.1">
            @for($i = 0; $i <= 4; $i++)
                <line x1="0" y1="{{ ($height / 4) * $i }}" x2="800" y2="{{ ($height / 4) * $i }}" 
                      stroke="currentColor" stroke-width="1" stroke-dasharray="4,4" />
            @endfor
            @for($i = 0; $i <= 8; $i++)
                <line x1="{{ (800 / 8) * $i }}" y1="0" x2="{{ (800 / 8) * $i }}" y2="{{ $height }}" 
                      stroke="currentColor" stroke-width="1" stroke-dasharray="4,4" />
            @endfor
        </g>
        
        <!-- Animated wave path -->
        <path class="energy-wave" 
              :d="wavePath" 
              fill="url(#energyGradient)" 
              filter="url(#shadow)"
              style="transition: d 0.5s ease-in-out;">
            <animate attributeName="d" 
                     :values="animationValues" 
                     dur="3s" 
                     repeatCount="indefinite" />
        </path>
        
        <!-- Top line with glow -->
        <path class="energy-line" 
              :d="linePath" 
              fill="none" 
              stroke="#4acf7b" 
              stroke-width="3" 
              filter="url(#glow)"
              stroke-linecap="round"
              style="transition: d 0.5s ease-in-out;">
            <animate attributeName="stroke-dashoffset" 
                     from="0" 
                     to="-20" 
                     dur="1s" 
                     repeatCount="indefinite" />
        </path>
        
        <!-- Data points -->
        <g class="data-points">
            <template x-for="(point, index) in dataPoints" :key="index">
                <g>
                    <!-- Point circle -->
                    <circle :cx="point.x" 
                            :cy="point.y" 
                            r="5" 
                            fill="#4acf7b" 
                            filter="url(#glow)"
                            class="cursor-pointer transition-all duration-200 hover:r-7">
                        <animate attributeName="r" 
                                 values="5;7;5" 
                                 :begin="`${index * 0.2}s`"
                                 dur="2s" 
                                 repeatCount="indefinite" />
                    </circle>
                    
                    <!-- Value label -->
                    <text :x="point.x" 
                          :y="point.y - 15" 
                          text-anchor="middle" 
                          class="text-xs font-bold fill-current text-gray-700 dark:text-gray-300"
                          x-text="point.value + ' kWh'">
                    </text>
                </g>
            </template>
        </g>
    </svg>
    
    <!-- Legend -->
    <div class="flex items-center justify-center gap-6 mt-4 text-sm">
        <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded-full bg-gradient-to-r from-eco-green-500 to-eco-green-600 animate-pulse"></div>
            <span class="text-gray-600 dark:text-gray-400">{{ __('dashboard.energy') }}</span>
        </div>
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-eco-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
            </svg>
            <span class="text-gray-600 dark:text-gray-400">{{ __('dashboard.trending_up') }}</span>
        </div>
    </div>
</div>

<script>
function energyWaveChart() {
    return {
        height: {{ $height }},
        data: @json($data),
        wavePath: '',
        linePath: '',
        animationValues: '',
        dataPoints: [],
        
        init() {
            this.generatePaths();
            setInterval(() => this.updateData(), 5000);
        },
        
        generatePaths() {
            const points = this.data.length || 8;
            const width = 800;
            const height = this.height;
            const segmentWidth = width / (points - 1);
            
            // Generate random data if not provided
            if (this.data.length === 0) {
                for (let i = 0; i < points; i++) {
                    this.data.push(Math.floor(Math.random() * 50) + 30);
                }
            }
            
            // Calculate data points
            this.dataPoints = this.data.map((value, index) => ({
                x: index * segmentWidth,
                y: height - (value / 100 * height * 0.7) - 20,
                value: value
            }));
            
            // Create smooth curve path
            let path = `M 0 ${height}`;
            let line = `M ${this.dataPoints[0].x} ${this.dataPoints[0].y}`;
            
            for (let i = 0; i < this.dataPoints.length; i++) {
                const point = this.dataPoints[i];
                
                if (i === 0) {
                    path += ` L ${point.x} ${point.y}`;
                } else {
                    const prevPoint = this.dataPoints[i - 1];
                    const cpx1 = prevPoint.x + (point.x - prevPoint.x) / 2;
                    const cpx2 = cpx1;
                    
                    path += ` C ${cpx1} ${prevPoint.y}, ${cpx2} ${point.y}, ${point.x} ${point.y}`;
                    line += ` C ${cpx1} ${prevPoint.y}, ${cpx2} ${point.y}, ${point.x} ${point.y}`;
                }
            }
            
            path += ` L ${width} ${height} Z`;
            
            this.wavePath = path;
            this.linePath = line;
        },
        
        updateData() {
            // Simulate real-time data update
            this.data = this.data.map(() => Math.floor(Math.random() * 50) + 30);
            this.generatePaths();
        }
    }
}
</script>

<style>
.energy-wave {
    animation: wave-flow 3s ease-in-out infinite;
}

@keyframes wave-flow {
    0%, 100% { opacity: 0.9; }
    50% { opacity: 1; }
}

.data-points circle:hover {
    r: 8;
    filter: url(#glow) brightness(1.2);
}
</style>

