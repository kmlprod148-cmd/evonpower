@props(['pack'])

<label class="pack-card-compact relative cursor-pointer group" data-id="{{ $pack->id }}" data-amount="{{ $pack->amount }}">
    <input type="radio" 
           name="recharge_type" 
           value="pack" 
           class="sr-only pack-radio"
           data-pack-id="{{ $pack->id }}"
           data-pack-amount="{{ $pack->amount }}">
    
    <!-- Contenu compact avec 16px padding -->
    <div class="p-4 flex flex-col items-center justify-center h-full min-h-[100px]">
        <!-- Montant principal - 16px font size -->
        <div class="pack-amount-compact text-center mb-1">
            <span class="text-base font-bold text-gray-900 dark:text-gray-100">
                {{ number_format($pack->amount, 0, ',', ' ') }}
            </span>
            <span class="text-xs font-semibold text-gray-600 dark:text-gray-400 ml-0.5">
                {{ strtoupper($pack->currency ?? 'EUR') }}
            </span>
        </div>
        
        <!-- Badge bonus si disponible -->
        @if($pack->hasBonus())
            <div class="pack-bonus-badge-compact mt-1">
                <span class="text-[10px] font-bold text-green-600 dark:text-green-400">
                    +{{ $pack->bonus_percentage }}%
                </span>
            </div>
        @endif
        
        <!-- Badge featured si applicable -->
        @if($pack->is_featured)
            <div class="absolute top-2 right-2">
                <span class="text-[9px] font-bold bg-gradient-to-r from-yellow-400 to-orange-500 text-white px-1.5 py-0.5 rounded-full">
                    ⭐
                </span>
            </div>
        @endif
    </div>
    
    <!-- Overlay de sélection -->
    <div class="pack-check-overlay-compact">
        <div class="w-6 h-6 bg-green-500 rounded-full flex items-center justify-center shadow-lg">
            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
            </svg>
        </div>
    </div>
</label>