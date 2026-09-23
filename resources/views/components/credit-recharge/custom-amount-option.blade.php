@php
    // Obtenir le logo de l'application
    $logoPath = null;
    if (file_exists(public_path('images/evon-logo.png'))) {
        $logoPath = asset('images/evon-logo.png');
    } elseif (file_exists(public_path('images/logo.png'))) {
        $logoPath = asset('images/logo.png');
    }
@endphp

<label class="credit-pack-widget relative cursor-pointer group block custom-amount-widget" 
       data-amount="custom">
    <input type="radio" 
           name="recharge_type" 
           value="custom" 
           class="sr-only recharge-type-radio"
           id="recharge_type_custom">
    
    <!-- Widget Card Container -->
    <div class="relative h-full min-h-[120px] rounded-xl overflow-hidden transition-all duration-300 ease-out
                bg-white dark:bg-gray-800 
                border-2 border-dashed border-gray-300 dark:border-gray-600
                hover:border-green-400 dark:hover:border-green-500
                hover:shadow-lg hover:shadow-green-500/20
                hover:-translate-y-1
                group-hover:scale-[1.02]">
        
        <!-- Gradient Background Overlay -->
        <div class="absolute inset-0 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 opacity-0 group-hover:opacity-10 dark:group-hover:opacity-20 transition-opacity duration-300"></div>
        
        <!-- Card Content -->
        <div class="relative p-4 h-full flex flex-col items-center justify-between">
            <!-- Logo de l'application (16px) -->
            <div class="mb-2 flex-shrink-0">
                @if($logoPath)
                    <img src="{{ $logoPath }}" 
                         alt="Logo" 
                         class="w-4 h-4 object-contain opacity-60 dark:opacity-40 group-hover:opacity-100 transition-opacity duration-300">
                @else
                    <div class="w-4 h-4 bg-gradient-to-br from-gray-400 to-gray-500 rounded opacity-60 dark:opacity-40 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                        <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                    </div>
                @endif
            </div>
            
            <!-- Texte principal -->
            <div class="flex-1 flex flex-col items-center justify-center text-center mb-2">
                <div class="text-lg font-bold text-gray-700 dark:text-gray-300 mb-1">
                    Personnalisé
                </div>
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">
                    50 - 5000 €
                </div>
            </div>
            
            <!-- Indicateur de sélection -->
            <div class="w-full mt-2">
                <div class="h-1 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-gray-400 to-gray-500 rounded-full transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300 origin-left"></div>
                </div>
            </div>
        </div>
        
        <!-- Overlay de sélection -->
        <div class="pack-check-overlay-widget absolute inset-0 bg-gradient-to-br from-green-500 to-emerald-600 bg-opacity-10 dark:bg-opacity-20 flex items-center justify-center opacity-0 transition-opacity duration-300">
            <div class="w-8 h-8 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center shadow-xl transform scale-0 group-hover:scale-100 transition-transform duration-300">
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
            </div>
        </div>
    </div>
    
    <!-- État sélectionné -->
    <div class="credit-pack-widget-selected absolute inset-0 border-2 border-green-500 dark:border-green-400 rounded-xl opacity-0 pointer-events-none transition-opacity duration-300">
        <div class="absolute top-1 right-1 w-5 h-5 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center shadow-lg">
            <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
            </svg>
        </div>
    </div>
</label>

