<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <h2 class="text-lg font-medium mb-4">Tarification de base</h2>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="base_rate" class="block text-sm font-medium text-gray-700 mb-1">Tarif de base (EUR/kWh) *</label>
            <input type="number" name="base_rate" id="base_rate" step="0.01" min="0" required
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-[#49ce7d] focus:border-[#49ce7d]" 
                   placeholder="0.00" value="{{ old('base_rate', $plan->base_rate ?? '') }}">
        </div>
        
        <div>
            <label for="rate_type" class="block text-sm font-medium text-gray-700 mb-1">Type de tarification</label>
            <select name="rate_type" id="rate_type"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-[#49ce7d] focus:border-[#49ce7d]">
                <option value="fixed" {{ old('rate_type', $plan->rate_type ?? '') == 'fixed' ? 'selected' : '' }}>Tarif fixe</option>
                <option value="variable" {{ old('rate_type', $plan->rate_type ?? '') == 'variable' ? 'selected' : '' }}>Tarif variable</option>
            </select>
        </div>
        
        <div class="md:col-span-2 border-t pt-4 mt-2">
            <h3 class="text-md font-medium mb-3">Limites d'application</h3>
            <p class="text-gray-500 text-sm mb-4">Facultatif: Définissez des limites pour la tarification de base</p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="max_power" class="block text-sm font-medium text-gray-700 mb-1">Puissance maximale (kW)</label>
                    <input type="number" name="max_power" id="max_power" step="0.1" min="0"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-[#49ce7d] focus:border-[#49ce7d]" 
                           placeholder="Ex: 22" value="{{ old('max_power', $plan->max_power ?? '') }}">
                    <p class="text-xs text-gray-500 mt-1">Laissez vide si le tarif s'applique sans limite de puissance</p>
                </div>
                
                <div>
                    <label for="max_duration" class="block text-sm font-medium text-gray-700 mb-1">Durée maximale (minutes)</label>
                    <input type="number" name="max_duration" id="max_duration" step="1" min="0"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-[#49ce7d] focus:border-[#49ce7d]" 
                           placeholder="Ex: 120" value="{{ old('max_duration', $plan->max_duration ?? '') }}">
                    <p class="text-xs text-gray-500 mt-1">Laissez vide si le tarif s'applique sans limite de durée</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="mt-6 p-4 bg-gray-50 rounded-lg">
        <div class="flex items-start">
            <div class="flex-shrink-0 mt-0.5">
                <svg class="h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">À propos de la tarification de base</h3>
                <div class="mt-1 text-sm text-blue-700">
                    <p>Le tarif de base sera appliqué à toutes les recharges sous ce plan tarifaire. Vous pourrez ajouter des tarifs supplémentaires lors de l'étape suivante.</p>
                </div>
            </div>
        </div>
    </div>
</div>