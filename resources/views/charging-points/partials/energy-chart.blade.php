<!-- Energy consumption chart -->
<x-card class="mb-4 md:mb-6">
    <x-section-header title="Consommation d'énergie" subtitle="Évolution sur 24h" />
    <div class="h-48 md:h-56 w-full">
        <div id="energy-chart" class="w-full h-full">
            <svg class="w-full h-full" viewBox="0 0 800 200">
                <path d="M0,150 C100,100 150,180 200,150 C250,120 300,180 350,150 C400,120 450,100 500,50 C550,0 600,50 650,100 C700,150 750,120 800,150" stroke="#10B981" stroke-width="2" fill="none" />
                <path d="M0,150 C100,100 150,180 200,150 C250,120 300,180 350,150 C400,120 450,100 500,50 C550,0 600,50 650,100 C700,150 750,120 800,150 L800,200 L0,200 Z" fill="url(#gradient)" fill-opacity="0.2" />
                <defs>
                    <linearGradient id="gradient" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" stop-color="#10B981" />
                        <stop offset="100%" stop-color="#10B981" stop-opacity="0" />
                    </linearGradient>
                </defs>
                <text x="10" y="160" font-size="12" fill="#6B7280">10 kWh</text>
                <text x="10" y="110" font-size="12" fill="#6B7280">15 kWh</text>
                <text x="10" y="60" font-size="12" fill="#6B7280">20 kWh</text>
                <text x="0" y="190" font-size="10" fill="#6B7280">00:00</text>
                <text x="200" y="190" font-size="10" fill="#6B7280">06:00</text>
                <text x="400" y="190" font-size="10" fill="#6B7280">12:00</text>
                <text x="600" y="190" font-size="10" fill="#6B7280">18:00</text>
                <text x="790" y="190" font-size="10" fill="#6B7280">24:00</text>
            </svg>
        </div>
    </div>
</x-card>

