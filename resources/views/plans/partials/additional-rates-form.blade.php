<div class="bg-white rounded-xl shadow-sm p-6 mb-6" id="additional-rates-section">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-medium">Tarifs supplémentaires</h2>
        <button type="button" id="addRateButton" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-lg text-white bg-[#49ce7d] hover:bg-[#3db96a] focus:outline-none">
            <svg class="-ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>
            Ajouter un tarif
        </button>
    </div>
    
    <p class="text-gray-500 text-sm mb-6">Définissez des tarifs supplémentaires qui s'appliquent dans des conditions spécifiques.</p>
    
    <!-- Additional rates container -->
    <div id="additionalRatesContainer">
        @if(isset($plan) && $plan->additionalRates && count($plan->additionalRates) > 0)
            @foreach($plan->additionalRates as $index => $rate)
                <div class="rate-item bg-gray-50 p-4 rounded-lg mb-4">
                    <div class="flex justify-between mb-3">
                        <h3 class="text-md font-medium">Tarif supplémentaire #{{ $index + 1 }}</h3>
                        <button type="button" class="remove-rate text-red-500 hover:text-red-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
                            <input type="text" name="additional_rates[{{ $index }}][name]" value="{{ $rate->name }}" required
                                   class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-[#49ce7d] focus:border-[#49ce7d]"
                                   placeholder="Ex: Heures creuses">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Prix (EUR/kWh)</label>
                            <input type="number" name="additional_rates[{{ $index }}][price]" value="{{ $rate->price }}" step="0.01" min="0" required
                                   class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-[#49ce7d] focus:border-[#49ce7d]"
                                   placeholder="0.00">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Type de condition</label>
                            <select name="additional_rates[{{ $index }}][condition_type]"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-[#49ce7d] focus:border-[#49ce7d] condition-type-select">
                                <option value="time" {{ $rate->condition_type == 'time' ? 'selected' : '' }}>Plage horaire</option>
                                <option value="day" {{ $rate->condition_type == 'day' ? 'selected' : '' }}>Jour de la semaine</option>
                                <option value="power" {{ $rate->condition_type == 'power' ? 'selected' : '' }}>Puissance de charge</option>
                                <option value="duration" {{ $rate->condition_type == 'duration' ? 'selected' : '' }}>Durée de charge</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Time condition fields -->
                    <div class="time-condition condition-fields mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 {{ $rate->condition_type == 'time' ? '' : 'hidden' }}">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Heure de début</label>
                            <input type="time" name="additional_rates[{{ $index }}][time_start]" value="{{ $rate->time_start ?? '' }}"
                                   class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-[#49ce7d] focus:border-[#49ce7d]">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Heure de fin</label>
                            <input type="time" name="additional_rates[{{ $index }}][time_end]" value="{{ $rate->time_end ?? '' }}"
                                   class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-[#49ce7d] focus:border-[#49ce7d]">
                        </div>
                    </div>
                    
                    <!-- Day condition fields -->
                    <div class="day-condition condition-fields mt-4 {{ $rate->condition_type == 'day' ? '' : 'hidden' }}">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Jours d'application</label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-7 gap-2">
                            @php
                                $dayLabels = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
                                $dayValues = isset($rate->days) ? json_decode($rate->days) : [];
                            @endphp
                            
                            @foreach($dayLabels as $dayIndex => $day)
                                <div class="flex items-center p-2 border border-gray-200 rounded-lg">
                                    <input type="checkbox" 
                                           name="additional_rates[{{ $index }}][days][]" 
                                           value="{{ $dayIndex + 1 }}" 
                                           id="day-{{ $index }}-{{ $dayIndex }}"
                                           {{ in_array($dayIndex + 1, $dayValues) ? 'checked' : '' }}
                                           class="h-4 w-4 text-[#49ce7d] focus:ring-[#49ce7d] border-gray-300 rounded">
                                    <label for="day-{{ $index }}-{{ $dayIndex }}" class="ml-2 text-sm text-gray-700">
                                        {{ $day }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    
                    <!-- Power condition fields -->
                    <div class="power-condition condition-fields mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 {{ $rate->condition_type == 'power' ? '' : 'hidden' }}">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Puissance minimale (kW)</label>
                            <input type="number" name="additional_rates[{{ $index }}][min_power]" value="{{ $rate->min_power ?? '' }}" step="0.1" min="0"
                                   class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-[#49ce7d] focus:border-[#49ce7d]">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Puissance maximale (kW)</label>
                            <input type="number" name="additional_rates[{{ $index }}][max_power]" value="{{ $rate->max_power ?? '' }}" step="0.1" min="0"
                                   class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-[#49ce7d] focus:border-[#49ce7d]">
                        </div>
                    </div>
                    
                    <!-- Duration condition fields -->
                    <div class="duration-condition condition-fields mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 {{ $rate->condition_type == 'duration' ? '' : 'hidden' }}">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Durée minimale (minutes)</label>
                            <input type="number" name="additional_rates[{{ $index }}][min_duration]" value="{{ $rate->min_duration ?? '' }}" step="1" min="0"
                                   class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-[#49ce7d] focus:border-[#49ce7d]">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Durée maximale (minutes)</label>
                            <input type="number" name="additional_rates[{{ $index }}][max_duration]" value="{{ $rate->max_duration ?? '' }}" step="1" min="0"
                                   class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-[#49ce7d] focus:border-[#49ce7d]">
                        </div>
                    </div>

                    <!-- Advanced conditions: always visible -->
                    <div class="advanced-conditions mt-4 border-t border-gray-200 pt-4">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Conditions avancées (optionnelles)</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Segment client</label>
                                <input type="text" name="additional_rates[{{ $index }}][customer_segment]" value="{{ $rate->customer_segment ?? '' }}"
                                       class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-[#49ce7d] focus:border-[#49ce7d]"
                                       placeholder="Ex: individual, business">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Zone géographique</label>
                                <input type="text" name="additional_rates[{{ $index }}][location_zone]" value="{{ $rate->location_zone ?? '' }}"
                                       class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-[#49ce7d] focus:border-[#49ce7d]"
                                       placeholder="Ex: zone1">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Quantité minimale</label>
                                <input type="number" name="additional_rates[{{ $index }}][quantity_min]" value="{{ $rate->quantity_min ?? '' }}" min="1"
                                       class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-[#49ce7d] focus:border-[#49ce7d]">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Quantité maximale</label>
                                <input type="number" name="additional_rates[{{ $index }}][quantity_max]" value="{{ $rate->quantity_max ?? '' }}" min="1"
                                       class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-[#49ce7d] focus:border-[#49ce7d]">
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Durée maximale (minutes)</label>
                            <input type="number" name="additional_rates[{{ $index }}][max_duration]" value="{{ $rate->max_duration ?? '' }}" step="1" min="0"
                                   class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-[#49ce7d] focus:border-[#49ce7d]">
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <div class="text-center py-8 bg-gray-50 rounded-lg" id="empty-rates-message">
                <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Aucun tarif supplémentaire</h3>
                <p class="mt-1 text-sm text-gray-500">Commencez par ajouter un tarif supplémentaire à ce plan.</p>
            </div>
        @endif
    </div>
    
    <!-- Template for new additional rate item (hidden) -->
    <template id="rateItemTemplate">
        <div class="rate-item bg-gray-50 p-4 rounded-lg mb-4">
            <div class="flex justify-between mb-3">
                <h3 class="text-md font-medium">Nouveau tarif supplémentaire</h3>
                <button type="button" class="remove-rate text-red-500 hover:text-red-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
                    <input type="text" name="additional_rates[INDEX][name]" required
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-[#49ce7d] focus:border-[#49ce7d]"
                           placeholder="Ex: Heures creuses">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prix (EUR/kWh)</label>
                    <input type="number" name="additional_rates[INDEX][price]" step="0.01" min="0" required
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-[#49ce7d] focus:border-[#49ce7d]"
                           placeholder="0.00">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type de condition</label>
                    <select name="additional_rates[INDEX][condition_type]"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-[#49ce7d] focus:border-[#49ce7d] condition-type-select">
                        <option value="time">Plage horaire</option>
                        <option value="day">Jour de la semaine</option>
                        <option value="power">Puissance de charge</option>
                        <option value="duration">Durée de charge</option>
                    </select>
                </div>
            </div>
            
            <!-- All condition fields -->
            <!-- Time condition fields -->
            <div class="time-condition condition-fields mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Heure de début</label>
                    <input type="time" name="additional_rates[INDEX][time_start]"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-[#49ce7d] focus:border-[#49ce7d]">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Heure de fin</label>
                    <input type="time" name="additional_rates[INDEX][time_end]"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-[#49ce7d] focus:border-[#49ce7d]">
                </div>
            </div>
            
            <!-- Day condition fields -->
            <div class="day-condition condition-fields mt-4 hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2">Jours d'application</label>
                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-7 gap-2">
                    @php
                        $dayLabels = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
                    @endphp
                    
                    @foreach($dayLabels as $dayIndex => $day)
                        <div class="flex items-center p-2 border border-gray-200 rounded-lg">
                            <input type="checkbox" 
                                   name="additional_rates[INDEX][days][]" 
                                   value="{{ $dayIndex + 1 }}" 
                                   id="day-INDEX-{{ $dayIndex }}"
                                   class="h-4 w-4 text-[#49ce7d] focus:ring-[#49ce7d] border-gray-300 rounded">
                            <label for="day-INDEX-{{ $dayIndex }}" class="ml-2 text-sm text-gray-700">
                                {{ $day }}
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
            
            <!-- Power condition fields -->
            <div class="power-condition condition-fields mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 hidden">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Puissance minimale (kW)</label>
                    <input type="number" name="additional_rates[INDEX][min_power]" step="0.1" min="0"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-[#49ce7d] focus:border-[#49ce7d]"
                           placeholder="Ex: 11">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Puissance maximale (kW)</label>
                    <input type="number" name="additional_rates[INDEX][max_power]" step="0.1" min="0"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-[#49ce7d] focus:border-[#49ce7d]"
                           placeholder="Ex: 22">
                </div>
            </div>
            
            <!-- Duration condition fields -->
            <div class="duration-condition condition-fields mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 hidden">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Durée minimale (minutes)</label>
                    <input type="number" name="additional_rates[INDEX][min_duration]" step="1" min="0"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-[#49ce7d] focus:border-[#49ce7d]"
                           placeholder="Ex: 30">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Durée maximale (minutes)</label>
                    <input type="number" name="additional_rates[INDEX][max_duration]" step="1" min="0"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-[#49ce7d] focus:border-[#49ce7d]"
                           placeholder="Ex: 120">
                </div>
            </div>

            <!-- Advanced conditions: always visible -->
            <div class="advanced-conditions mt-4 border-t border-gray-200 pt-4">
                <h4 class="text-sm font-medium text-gray-700 mb-2">Conditions avancées (optionnelles)</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Segment client</label>
                        <input type="text" name="additional_rates[INDEX][customer_segment]"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-[#49ce7d] focus:border-[#49ce7d]"
                               placeholder="Ex: individual, business">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Zone géographique</label>
                        <input type="text" name="additional_rates[INDEX][location_zone]"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-[#49ce7d] focus:border-[#49ce7d]"
                               placeholder="Ex: zone1">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Quantité minimale</label>
                        <input type="number" name="additional_rates[INDEX][quantity_min]" min="1"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-[#49ce7d] focus:border-[#49ce7d]">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Quantité maximale</label>
                        <input type="number" name="additional_rates[INDEX][quantity_max]" min="1"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-[#49ce7d] focus:border-[#49ce7d]">
                    </div>
                </div>
            </div>
        </div>
    </template>
    
    <div class="mt-6 p-4 bg-gray-50 rounded-lg">
        <div class="flex items-start">
            <div class="flex-shrink-0 mt-0.5">
                <svg class="h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">À propos des tarifs supplémentaires</h3>
                <div class="mt-1 text-sm text-blue-700">
                    <p>Les tarifs supplémentaires permettent d'appliquer des prix différents selon certaines conditions (heures creuses, week-end, recharge rapide, etc). Ils s'ajoutent au tarif de base et sont utilisés automatiquement lorsque les conditions sont remplies.</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Sélectionnez les éléments du DOM
        const addRateButton = document.getElementById('addRateButton');
        const ratesContainer = document.getElementById('additionalRatesContainer');
        const rateTemplate = document.getElementById('rateItemTemplate');
        const emptyMessage = document.getElementById('empty-rates-message');
        
        // Générer un index unique pour de nouveaux éléments
        let rateIndex = {{ isset($plan) && $plan->additionalRates ? $plan->additionalRates->count() : 0 }};
        
        // Ajouter un nouveau tarif
        addRateButton.addEventListener('click', function() {
            // Supprimer le message "vide" s'il existe
            if (emptyMessage) {
                emptyMessage.remove();
            }
            
            // Cloner le template
            const newRate = rateTemplate.content.cloneNode(true);
            
            // Mettre à jour tous les attributs INDEX avec l'index unique
            newRate.querySelectorAll('[name*="INDEX"]').forEach(el => {
                el.name = el.name.replace('INDEX', rateIndex);
            });
            
            newRate.querySelectorAll('[id*="INDEX"]').forEach(el => {
                el.id = el.id.replace('INDEX', rateIndex);
            });
            
            newRate.querySelectorAll('[for*="INDEX"]').forEach(el => {
                el.htmlFor = el.htmlFor.replace('INDEX', rateIndex);
            });
            
            // Ajouter des gestionnaires d'événements
            const newRateElement = newRate.querySelector('.rate-item');
            
            // Gestionnaire pour le bouton de suppression
            const removeButton = newRateElement.querySelector('.remove-rate');
            removeButton.addEventListener('click', function() {
                this.closest('.rate-item').remove();
                
                // Afficher le message vide si plus aucun tarif
                if (ratesContainer.querySelectorAll('.rate-item').length === 0) {
                    showEmptyMessage();
                }
            });
            
            // Gestionnaire pour le changement de type de condition
            const conditionSelect = newRateElement.querySelector('.condition-type-select');
            conditionSelect.addEventListener('change', function() {
                const conditionType = this.value;
                const rateItem = this.closest('.rate-item');
                
                // Cacher tous les champs de condition
                rateItem.querySelectorAll('.condition-fields').forEach(field => {
                    field.classList.add('hidden');
                });
                
                // Afficher les champs pour le type sélectionné
                rateItem.querySelector('.' + conditionType + '-condition').classList.remove('hidden');
            });
            
            // Ajouter l'élément au conteneur
            ratesContainer.appendChild(newRate);
            
            // Incrémenter l'index pour le prochain élément
            rateIndex++;
        });
        
        // Initialiser les gestionnaires d'événements pour les éléments existants
        document.querySelectorAll('.condition-type-select').forEach(select => {
            select.addEventListener('change', function() {
                const conditionType = this.value;
                const rateItem = this.closest('.rate-item');
                
                // Cacher tous les champs de condition
                rateItem.querySelectorAll('.condition-fields').forEach(field => {
                    field.classList.add('hidden');
                });
                
                // Afficher les champs pour le type sélectionné
                rateItem.querySelector('.' + conditionType + '-condition').classList.remove('hidden');
            });
        });
        
        document.querySelectorAll('.remove-rate').forEach(button => {
            button.addEventListener('click', function() {
                this.closest('.rate-item').remove();
                
                // Afficher le message vide si plus aucun tarif
                if (ratesContainer.querySelectorAll('.rate-item').length === 0) {
                    showEmptyMessage();
                }
            });
        });
        
        // Fonction pour afficher le message vide
        function showEmptyMessage() {
            const messageHTML = `
                <div class="text-center py-8 bg-gray-50 rounded-lg" id="empty-rates-message">
                    <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Aucun tarif supplémentaire</h3>
                    <p class="mt-1 text-sm text-gray-500">Commencez par ajouter un tarif supplémentaire à ce plan.</p>
                </div>
            `;
            
            ratesContainer.innerHTML = messageHTML;
        }
    });
</script>
@endpush