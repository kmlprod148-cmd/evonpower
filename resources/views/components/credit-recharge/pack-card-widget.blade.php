@props(['pack'])

@php
    // Obtenir le logo de l'application
    $logoPath = null;
    if (file_exists(public_path('images/evon-logo.png'))) {
        $logoPath = asset('images/evon-logo.png');
    } elseif (file_exists(public_path('images/logo.png'))) {
        $logoPath = asset('images/logo.png');
    }
    
    // Couleur personnalisée ou couleur par défaut
    $cardColor = $pack->color ?? 'green';
    $colorClasses = [
        'green' => 'from-green-500 to-emerald-600',
        'blue' => 'from-blue-500 to-cyan-600',
        'purple' => 'from-purple-500 to-indigo-600',
        'orange' => 'from-orange-500 to-amber-600',
        'pink' => 'from-pink-500 to-rose-600',
        'teal' => 'from-teal-500 to-cyan-600',
    ];
    $gradientClass = $colorClasses[$cardColor] ?? $colorClasses['green'];
    
    // Badge de couleur pour le statut featured
    $featuredGradient = 'from-yellow-400 via-orange-500 to-pink-500';
@endphp

<label class="credit-pack-widget relative cursor-pointer group block" 
       data-id="{{ $pack->id }}" 
       data-amount="{{ $pack->amount }}"
       data-price="{{ $pack->price }}">
    <input type="radio" 
           name="recharge_type" 
           value="pack" 
           class="sr-only pack-radio"
           data-pack-id="{{ $pack->id }}"
           data-pack-amount="{{ $pack->amount }}"
           data-pack-price="{{ $pack->price }}">
    
    <!-- Widget Card Container -->
    <div class="relative h-full min-h-[120px] rounded-xl overflow-hidden transition-all duration-300 ease-out
                bg-white dark:bg-gray-800 
                border-2 border-gray-200 dark:border-gray-700
                hover:border-green-400 dark:hover:border-green-500
                hover:shadow-lg hover:shadow-green-500/20
                hover:-translate-y-1
                group-hover:scale-[1.02]">
        
        <!-- Gradient Background Overlay -->
        <div class="absolute inset-0 bg-gradient-to-br {{ $gradientClass }} opacity-0 group-hover:opacity-5 dark:group-hover:opacity-10 transition-opacity duration-300"></div>
        
        <!-- Featured Badge -->
        @if($pack->is_featured)
        <div class="absolute top-2 right-2 z-10">
            <div class="relative">
                <div class="absolute inset-0 bg-gradient-to-r {{ $featuredGradient }} rounded-full blur-sm opacity-75 animate-pulse"></div>
                <div class="relative bg-gradient-to-r {{ $featuredGradient }} text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-lg">
                    ⭐ POPULAIRE
                </div>
            </div>
        </div>
        @endif
        
        <!-- Bonus Badge -->
        @if($pack->hasBonus())
        <div class="absolute top-2 left-2 z-10">
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-md flex items-center space-x-1">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z" clip-rule="evenodd"/>
                </svg>
                <span>+{{ $pack->bonus_percentage }}%</span>
            </div>
        </div>
        @endif
        
        <!-- Card Content -->
        <div class="relative p-4 h-full flex flex-col items-center justify-between">
            <!-- Logo de l'application (16px) -->
            <div class="mb-2 flex-shrink-0">
                @if($logoPath)
                    <img src="{{ $logoPath }}" 
                         alt="Logo" 
                         class="w-4 h-4 object-contain opacity-60 dark:opacity-40 group-hover:opacity-100 transition-opacity duration-300">
                @else
                    <div class="w-4 h-4 bg-gradient-to-br {{ $gradientClass }} rounded opacity-60 dark:opacity-40 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                        <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                @endif
            </div>
            
            <!-- Montant principal -->
            <div class="flex-1 flex flex-col items-center justify-center text-center mb-2">
                <div class="flex items-baseline space-x-1">
                    <span class="text-xl font-bold text-gray-900 dark:text-gray-100 leading-tight">
                        {{ number_format($pack->amount, 0, ',', ' ') }}
                    </span>
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">
                        {{ strtoupper($pack->currency ?? 'EUR') }}
                    </span>
                </div>
                
                <!-- Prix -->
                <div class="mt-1 text-xs font-medium text-gray-600 dark:text-gray-400">
                    {{ number_format($pack->price, 2, ',', ' ') }} €
                </div>
            </div>
            
            <!-- Indicateur de sélection -->
            <div class="w-full mt-2">
                <div class="h-1 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r {{ $gradientClass }} rounded-full transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300 origin-left"></div>
                </div>
            </div>
        </div>
        
        <!-- Overlay de sélection -->
        <div class="pack-check-overlay-widget absolute inset-0 bg-gradient-to-br {{ $gradientClass }} bg-opacity-10 dark:bg-opacity-20 flex items-center justify-center opacity-0 transition-opacity duration-300">
            <div class="w-8 h-8 bg-gradient-to-br {{ $gradientClass }} rounded-full flex items-center justify-center shadow-xl transform scale-0 group-hover:scale-100 transition-transform duration-300">
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
            </div>
        </div>
    </div>
    
    <!-- État sélectionné -->
    <div class="credit-pack-widget-selected absolute inset-0 border-2 border-green-500 dark:border-green-400 rounded-xl opacity-0 pointer-events-none transition-opacity duration-300">
        <div class="absolute top-1 right-1 w-5 h-5 bg-gradient-to-br {{ $gradientClass }} rounded-full flex items-center justify-center shadow-lg">
            <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
            </svg>
        </div>
    </div>
</label>

@push('styles')
<style>
    /* Styles pour les widgets de packs de crédit */
    .credit-pack-widget {
        position: relative;
    }
    
    .credit-pack-widget input:checked + div,
    .credit-pack-widget input:checked ~ div {
        border-color: rgb(34 197 94) !important; /* green-500 */
        box-shadow: 0 10px 15px -3px rgba(34, 197, 94, 0.2), 0 4px 6px -2px rgba(34, 197, 94, 0.1);
    }
    
    .credit-pack-widget input:checked ~ .credit-pack-widget-selected {
        opacity: 1;
    }
    
    .credit-pack-widget input:checked + div .pack-check-overlay-widget {
        opacity: 1;
    }
    
    .pack-check-overlay-widget {
        backdrop-filter: blur(4px);
    }
    
    /* Animation pour le hover */
    @keyframes packPulse {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.05);
        }
    }
    
    .credit-pack-widget:hover {
        animation: packPulse 2s ease-in-out infinite;
    }
    
    /* Responsive adjustments */
    @media (max-width: 640px) {
        .credit-pack-widget {
            min-height: 100px;
        }
    }
</style>
@endpush

