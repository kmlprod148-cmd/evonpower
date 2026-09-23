@props([
    'id' => 'ptr-' . uniqid(),
    'threshold' => 80, // Distance en pixels avant de déclencher le refresh
    'onRefresh' => '', // JavaScript callback function name
])

<div 
    x-data="pullToRefresh('{{ $id }}', {{ $threshold }}, '{{ $onRefresh }}')"
    x-init="init()"
    @touchstart="handleTouchStart"
    @touchmove="handleTouchMove"
    @touchend="handleTouchEnd"
    class="relative overflow-hidden"
    id="{{ $id }}"
>
    {{-- Pull to Refresh Indicator --}}
    <div 
        x-show="pullDistance > 0"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        class="absolute top-0 left-0 right-0 z-50 flex items-center justify-center pointer-events-none"
        :style="`transform: translateY(${Math.min(pullDistance - 60, 60)}px); opacity: ${Math.min(pullDistance / threshold, 1)}`"
    >
        <div class="flex flex-col items-center gap-2 py-4">
            {{-- Loading Spinner ou Pull Icon --}}
            <div 
                class="w-8 h-8 transition-transform duration-300"
                :class="{ 'animate-spin': refreshing }"
            >
                <template x-if="refreshing">
                    <svg class="w-8 h-8 text-eco-green-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </template>
                <template x-if="!refreshing">
                    <svg 
                        class="w-8 h-8 text-eco-green-500 transition-transform duration-300"
                        :style="`transform: rotate(${Math.min(pullDistance / threshold * 180, 180)}deg)`"
                        fill="none" 
                        viewBox="0 0 24 24" 
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </template>
            </div>
            
            {{-- Text --}}
            <p class="text-xs font-medium text-gray-600 dark:text-gray-400" x-text="statusText"></p>
        </div>
    </div>
    
    {{-- Content --}}
    <div :style="`transform: translateY(${refreshing ? '60px' : '0'}); transition: transform 0.3s ease-out`">
        {{ $slot }}
    </div>
</div>

<script>
function pullToRefresh(id, threshold, onRefreshCallback) {
    return {
        pullDistance: 0,
        startY: 0,
        refreshing: false,
        statusText: 'Tirer pour actualiser',
        canPull: false,
        
        init() {
            // Vérifier que nous sommes bien en haut de la page
            this.checkScrollPosition();
        },
        
        checkScrollPosition() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            this.canPull = scrollTop <= 0;
        },
        
        handleTouchStart(e) {
            this.checkScrollPosition();
            if (this.canPull && !this.refreshing) {
                this.startY = e.touches[0].pageY;
            }
        },
        
        handleTouchMove(e) {
            if (!this.canPull || this.refreshing) return;
            
            const currentY = e.touches[0].pageY;
            const diff = currentY - this.startY;
            
            // Ne tirer que vers le bas
            if (diff > 0) {
                // Effet de résistance (plus on tire, plus c'est difficile)
                this.pullDistance = Math.min(diff * 0.5, threshold * 1.5);
                
                // Mettre à jour le texte
                if (this.pullDistance >= threshold) {
                    this.statusText = 'Relâcher pour actualiser';
                } else {
                    this.statusText = 'Tirer pour actualiser';
                }
                
                // Empêcher le scroll si on tire
                if (this.pullDistance > 10) {
                    e.preventDefault();
                }
            }
        },
        
        async handleTouchEnd() {
            if (!this.canPull || this.refreshing) return;
            
            // Si on a dépassé le seuil, déclencher le refresh
            if (this.pullDistance >= threshold) {
                this.refreshing = true;
                this.statusText = 'Actualisation...';
                
                try {
                    // Appeler le callback si fourni
                    if (onRefreshCallback && typeof window[onRefreshCallback] === 'function') {
                        await window[onRefreshCallback]();
                    } else {
                        // Par défaut, recharger la page après 1 seconde
                        await new Promise(resolve => setTimeout(resolve, 1000));
                        window.location.reload();
                    }
                    
                    this.statusText = 'Actualisé !';
                    
                    // Attendre un peu avant de réinitialiser
                    setTimeout(() => {
                        this.refreshing = false;
                        this.pullDistance = 0;
                        this.statusText = 'Tirer pour actualiser';
                    }, 500);
                    
                } catch (error) {
                    console.error('Erreur lors du refresh:', error);
                    this.statusText = 'Erreur lors de l\'actualisation';
                    setTimeout(() => {
                        this.refreshing = false;
                        this.pullDistance = 0;
                        this.statusText = 'Tirer pour actualiser';
                    }, 1500);
                }
            } else {
                // Reset si on n'a pas atteint le seuil
                this.pullDistance = 0;
            }
        }
    };
}

// Fonction de refresh par défaut (peut être surchargée)
window.defaultRefreshHandler = async function() {
    // Simuler un appel API
    await new Promise(resolve => setTimeout(resolve, 1500));
    console.log('Page refreshed!');
};
</script>

