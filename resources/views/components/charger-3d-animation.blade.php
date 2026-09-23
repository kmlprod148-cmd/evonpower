{{-- Composant d'animation 3D légère du chargeur --}}
@props(['state' => 'idle']) {{-- idle, started, charging, complete --}}

<div class="charger-3d-container relative" x-data="{ state: '{{ $state }}' }">
    
    <div class="relative w-full max-w-md mx-auto">
        {{-- Charger 3D --}}
        <div class="charger-3d-wrapper relative perspective-1000">
            {{-- Base du chargeur --}}
            <div class="charger-base relative mx-auto" 
                 :class="{
                     'animate-pulse': state === 'started',
                     'animate-charging': state === 'charging',
                     'animate-complete': state === 'complete'
                 }">
                {{-- Corps principal --}}
                <div class="charger-body relative bg-gradient-to-br from-gray-700 to-gray-900 rounded-t-3xl rounded-b-xl shadow-2xl border-4 border-gray-600"
                     style="width: 120px; height: 180px; margin: 0 auto; transform-style: preserve-3d;">
                    
                    {{-- Écran LED --}}
                    <div class="charger-screen absolute top-4 left-1/2 transform -translate-x-1/2 w-16 h-12 bg-black rounded-lg border-2 border-gray-500 flex items-center justify-center"
                         :class="{
                             'bg-green-500': state === 'charging' || state === 'complete',
                             'bg-gray-800': state === 'idle' || state === 'started',
                             'animate-pulse': state === 'charging'
                         }">
                        <span class="text-white text-xs font-bold animate-pulse" x-show="state === 'charging'">⚡</span>
                        <span class="text-white text-xs font-bold" x-show="state === 'complete'" style="display: none;">✓</span>
                    </div>
                    
                    {{-- Lignes de charge (animation) --}}
                    <div class="charging-lines absolute inset-0 overflow-hidden rounded-t-3xl" 
                         x-show="state === 'charging' || state === 'complete'"
                         style="display: none;">
                        <div class="charging-wave absolute bottom-0 left-0 right-0 h-full bg-gradient-to-t from-green-400 to-transparent opacity-30"
                             style="animation: chargeWave 2s ease-in-out infinite;">
                        </div>
                    </div>
                    
                    {{-- Connecteur (prise) --}}
                    <div class="charger-connector absolute bottom-0 left-1/2 transform -translate-x-1/2 translate-y-1/2 w-16 h-8 bg-gradient-to-b from-gray-800 to-gray-900 rounded-b-xl border-4 border-gray-600 shadow-lg">
                        <div class="connector-pin absolute top-0 left-1/2 transform -translate-x-1/2 w-8 h-4 bg-gradient-to-b from-gray-700 to-gray-900 rounded-t-lg border-2 border-gray-500"></div>
                    </div>
                    
                    {{-- Lumières LED de statut --}}
                    <div class="status-leds absolute top-8 right-2 space-y-1">
                        <div class="led w-2 h-2 rounded-full"
                             :class="{
                                 'bg-green-500 animate-pulse': state === 'charging',
                                 'bg-green-400': state === 'complete',
                                 'bg-yellow-500 animate-pulse': state === 'started',
                                 'bg-gray-600': state === 'idle'
                             }"
                             style="box-shadow: 0 0 8px currentColor;"></div>
                        <div class="led w-2 h-2 rounded-full"
                             :class="{
                                 'bg-green-500 animate-pulse': state === 'charging',
                                 'bg-green-400': state === 'complete',
                                 'bg-gray-600': state !== 'charging' && state !== 'complete'
                             }"
                             style="box-shadow: 0 0 8px currentColor;"></div>
                    </div>
                </div>
                
                {{-- Câble --}}
                <div class="charger-cable absolute top-full left-1/2 transform -translate-x-1/2 w-3 h-32 bg-gradient-to-b from-gray-700 via-gray-800 to-gray-700 rounded-b-lg shadow-inner">
                    <div class="cable-coil absolute top-8 left-1/2 transform -translate-x-1/2 w-12 h-12 border-4 border-gray-600 rounded-full"
                         :class="{
                             'animate-spin-slow': state === 'charging',
                             'animate-none': state !== 'charging'
                         }"
                         style="animation-duration: 3s;"></div>
                </div>
            </div>
        </div>
        
        {{-- Texte de statut --}}
        <div class="text-center mt-8 space-y-2">
            <p class="text-xl font-bold text-gray-800 dark:text-gray-200 transition-all duration-500"
               x-show="state === 'idle'"
               x-transition:enter="transition ease-out duration-300"
               x-transition:enter-start="opacity-0 transform scale-95"
               x-transition:enter-end="opacity-100 transform scale-100">
                Prêt
            </p>
            <p class="text-xl font-bold text-yellow-600 dark:text-yellow-400 transition-all duration-500"
               x-show="state === 'started'"
               x-transition:enter="transition ease-out duration-300"
               x-transition:enter-start="opacity-0 transform scale-95"
               x-transition:enter-end="opacity-100 transform scale-100">
                Chargement démarré...
            </p>
            <p class="text-xl font-bold text-green-600 dark:text-green-400 transition-all duration-500"
               x-show="state === 'charging'"
               x-transition:enter="transition ease-out duration-300"
               x-transition:enter-start="opacity-0 transform scale-95"
               x-transition:enter-end="opacity-100 transform scale-100">
                ⚡ Chargement en cours...
            </p>
            <p class="text-xl font-bold text-green-700 dark:text-green-300 transition-all duration-500"
               x-show="state === 'complete'"
               x-transition:enter="transition ease-out duration-300"
               x-transition:enter-start="opacity-0 transform scale-95"
               x-transition:enter-end="opacity-100 transform scale-100">
                ✓ Chargement terminé !
            </p>
        </div>
    </div>
    
    <style>
        /* Optimisations iPhone 14 Pro - Performance GPU */
        .charger-3d-container {
            transform: translateZ(0);
            -webkit-transform: translateZ(0);
            will-change: contents;
        }
        
        .charger-3d-wrapper {
            transform: translateZ(0);
            -webkit-transform: translateZ(0);
            backface-visibility: hidden;
            -webkit-backface-visibility: hidden;
        }
        
        .charger-base {
            transform: translateZ(0);
            -webkit-transform: translateZ(0);
            will-change: transform;
        }
        
        @keyframes chargeWave {
            0% {
                transform: translateY(100%) translateZ(0);
                opacity: 0.3;
            }
            50% {
                opacity: 0.6;
            }
            100% {
                transform: translateY(0%) translateZ(0);
                opacity: 0.3;
            }
        }
        
        @keyframes charging {
            0%, 100% {
                transform: translateY(0) scale(1) translateZ(0);
                box-shadow: 0 0 20px rgba(34, 197, 94, 0.3);
            }
            50% {
                transform: translateY(-5px) scale(1.02) translateZ(0);
                box-shadow: 0 5px 30px rgba(34, 197, 94, 0.5);
            }
        }
        
        @keyframes complete {
            0% {
                transform: scale(1) translateZ(0);
            }
            50% {
                transform: scale(1.05) translateZ(0);
            }
            100% {
                transform: scale(1) translateZ(0);
            }
        }
        
        .perspective-1000 {
            perspective: 1000px;
            -webkit-perspective: 1000px;
        }
        
        .animate-charging {
            animation: charging 2s ease-in-out infinite;
            /* GPU acceleration */
            transform: translateZ(0);
            -webkit-transform: translateZ(0);
            will-change: transform, box-shadow;
        }
        
        .animate-complete {
            animation: complete 1s ease-in-out;
            transform: translateZ(0);
            -webkit-transform: translateZ(0);
        }
        
        .animate-spin-slow {
            animation: spin 3s linear infinite;
            transform: translateZ(0);
            -webkit-transform: translateZ(0);
            will-change: transform;
        }
        
        @keyframes spin {
            from {
                transform: translateX(-50%) translateZ(0) rotate(0deg);
            }
            to {
                transform: translateX(-50%) translateZ(0) rotate(360deg);
            }
        }
        
        /* Optimisations spécifiques iPhone 14 Pro */
        @media only screen 
            and (device-width: 393px) 
            and (device-height: 852px) 
            and (-webkit-device-pixel-ratio: 3) {
            
            .charger-body {
                width: 140px !important;
                height: 200px !important;
            }
            
            .charger-screen {
                width: 20px !important;
                height: 16px !important;
                top: 8px !important;
            }
            
            /* Réduction de la complexité pour performance */
            .charging-wave {
                animation-duration: 1.5s;
            }
            
            .cable-coil {
                animation-duration: 2.5s;
            }
        }
    </style>
</div>

