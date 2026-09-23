<div class="bg-white rounded-xl shadow-sm p-6 mb-6" id="summary-section">
    <div class="mb-4">
        <h2 class="text-lg font-medium">Récapitulatif du plan tarifaire</h2>
        <p class="text-gray-500 text-sm mt-1">Vérifiez les informations du plan avant de le créer.</p>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Left column -->
        <div>
            <h3 class="text-sm font-medium text-gray-500 mb-3">Informations générales</h3>
            <div class="bg-gray-50 rounded-lg p-4">
                <dl class="divide-y divide-gray-200">
                    <div class="py-2 flex flex-col sm:flex-row">
                        <dt class="text-sm font-medium text-gray-500 sm:w-40">Nom</dt>
                        <dd class="text-sm text-gray-900 mt-1 sm:mt-0 sm:ml-6" id="summary-name"></dd>
                    </div>
                    
                    <div class="py-2 flex flex-col sm:flex-row">
                        <dt class="text-sm font-medium text-gray-500 sm:w-40">Type</dt>
                        <dd class="text-sm text-gray-900 mt-1 sm:mt-0 sm:ml-6" id="summary-type"></dd>
                    </div>
                    
                    <div class="py-2 flex flex-col sm:flex-row">
                        <dt class="text-sm font-medium text-gray-500 sm:w-40">Durée</dt>
                        <dd class="text-sm text-gray-900 mt-1 sm:mt-0 sm:ml-6" id="summary-duration"></dd>
                    </div>
                    
                    <div class="py-2 flex flex-col sm:flex-row">
                        <dt class="text-sm font-medium text-gray-500 sm:w-40">Frais d'activation</dt>
                        <dd class="text-sm text-gray-900 mt-1 sm:mt-0 sm:ml-6" id="summary-activation-fee"></dd>
                    </div>
                    
                    <div class="py-2 flex flex-col sm:flex-row">
                        <dt class="text-sm font-medium text-gray-500 sm:w-40">Statut</dt>
                        <dd class="mt-1 sm:mt-0 sm:ml-6" id="summary-status"></dd>
                    </div>
                </dl>
            </div>
            
            <h3 class="text-sm font-medium text-gray-500 mt-6 mb-3">Tarification</h3>
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="py-2">
                    <dt class="text-sm font-medium text-gray-900">Tarif de base</dt>
                    <dd class="text-sm text-gray-900 mt-1 flex items-center" id="summary-base-rate"></dd>
                </div>
                
                <div class="py-2 mt-2 pt-2 border-t border-gray-200" id="summary-additional-rates-container">
                    <dt class="text-sm font-medium text-gray-900">Tarifs supplémentaires</dt>
                    <dd class="mt-1" id="summary-additional-rates">
                        <div class="text-sm text-gray-500">Aucun tarif supplémentaire défini</div>
                    </dd>
                </div>
            </div>
        </div>
        
        <!-- Right column -->
        <div>
            <h3 class="text-sm font-medium text-gray-500 mb-3">Groupes de bornes associés</h3>
            <div class="bg-gray-50 rounded-lg p-4 h-[200px] overflow-y-auto">
                <div id="summary-groups">
                    <div class="text-sm text-gray-500">Aucun groupe sélectionné</div>
                </div>
            </div>
            
            <h3 class="text-sm font-medium text-gray-500 mt-6 mb-3">Description</h3>
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-sm text-gray-900" id="summary-description"></p>
            </div>
        </div>
    </div>
    
    <!-- Warning message -->
    <div class="mt-6 bg-yellow-50 p-4 rounded-lg">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800">Vérification finale</h3>
                <div class="mt-2 text-sm text-yellow-700">
                    <p>Veuillez vérifier soigneusement tous les détails du plan tarifaire avant de continuer. Une fois créé, ce plan sera disponible pour les utilisateurs sélectionnés.</p>
                </div>
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Fonction pour mettre à jour le récapitulatif
            window.updatePlanSummary = function() {
                // Informations générales
                const nameField = document.getElementById('name');
                document.getElementById('summary-name').textContent = nameField?.value || '(Non défini)';
                
                const typeSelect = document.getElementById('type');
                if (typeSelect && typeSelect.selectedIndex >= 0) {
                    const typeNames = {
                        'subscription': 'Abonnement',
                        'on_demand': 'À la demande',
                        'prepaid': 'Prépayé'
                    };
                    const typeValue = typeSelect.value;
                    document.getElementById('summary-type').textContent = typeNames[typeValue] || typeValue;
                } else {
                    document.getElementById('summary-type').textContent = '(Non défini)';
                }
                
                const durationValue = document.getElementById('duration_value')?.value;
                const durationUnit = document.getElementById('duration_unit');
                let durationText = 'N/A';
                
                if (durationValue && durationUnit) {
                    const unitLabels = {
                        'days': 'jours',
                        'months': 'mois',
                        'years': 'années'
                    };
                    const unitText = unitLabels[durationUnit.value] || durationUnit.options[durationUnit.selectedIndex]?.text || '';
                    durationText = `${durationValue} ${unitText}`;
                }
                document.getElementById('summary-duration').textContent = durationText;
                
                const activationFee = document.getElementById('activation_fee')?.value || '0';
                document.getElementById('summary-activation-fee').textContent = `${activationFee} EUR`;
                
                const isActive = document.getElementById('is_active')?.checked;
                const statusHtml = isActive ? 
                    '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-[#49ce7d1f] text-[#49ce7d]">Actif</span>' : 
                    '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Inactif</span>';
                document.getElementById('summary-status').innerHTML = statusHtml;
                
                // Tarification
                const baseRate = document.getElementById('base_rate')?.value || '0';
                document.getElementById('summary-base-rate').textContent = `${baseRate} EUR/kWh`;
                
                // Tarifs supplémentaires
                updateAdditionalRatesSummary();
                
                // Groupes
                updateGroupsSummary();
                
                // Description
                document.getElementById('summary-description').textContent = document.getElementById('description')?.value || '(Aucune description)';
            };
            
            // Fonction pour mettre à jour le récapitulatif des tarifs supplémentaires
            function updateAdditionalRatesSummary() {
                const additionalRatesContainer = document.getElementById('additionalRatesContainer');
                const additionalRatesItems = additionalRatesContainer?.querySelectorAll('.rate-item');
                const summaryRatesContainer = document.getElementById('summary-additional-rates');
                
                if (!additionalRatesItems || additionalRatesItems.length === 0) {
                    summaryRatesContainer.innerHTML = '<div class="text-sm text-gray-500">Aucun tarif supplémentaire défini</div>';
                    return;
                }
                
                let ratesHtml = '<ul class="divide-y divide-gray-200 text-sm">';
                
                additionalRatesItems.forEach(function(item, index) {
                    const name = item.querySelector('input[name*="[name]"]')?.value || 'Sans nom';
                    const price = item.querySelector('input[name*="[price]"]')?.value || '0';
                    const conditionTypeSelect = item.querySelector('select[name*="[condition_type]"]');
                    const conditionType = conditionTypeSelect?.value || '';
                    
                    // Déterminer la description de la condition
                    let conditionText = '';
                    switch(conditionType) {
                        case 'time': {
                            const timeStart = item.querySelector('input[name*="[time_start]"]')?.value;
                            const timeEnd = item.querySelector('input[name*="[time_end]"]')?.value;
                            conditionText = timeStart && timeEnd ? `De ${timeStart} à ${timeEnd}` : 'Plage horaire';
                            break;
                        }
                        case 'day': {
                            const dayCheckboxes = item.querySelectorAll('input[name*="[days][]"]:checked');
                            if (dayCheckboxes && dayCheckboxes.length > 0) {
                                const dayLabels = [];
                                dayCheckboxes.forEach(checkbox => {
                                    const label = checkbox.nextElementSibling?.textContent.trim();
                                    if (label) dayLabels.push(label);
                                });
                                conditionText = dayLabels.join(', ');
                            } else {
                                conditionText = 'Jours non spécifiés';
                            }
                            break;
                        }
                        case 'power': {
                            const minPower = item.querySelector('input[name*="[min_power]"]')?.value;
                            const maxPower = item.querySelector('input[name*="[max_power]"]')?.value;
                            if (minPower && maxPower) {
                                conditionText = `${minPower} kW - ${maxPower} kW`;
                            } else if (minPower) {
                                conditionText = `> ${minPower} kW`;
                            } else if (maxPower) {
                                conditionText = `< ${maxPower} kW`;
                            } else {
                                conditionText = 'Puissance non spécifiée';
                            }
                            break;
                        }
                        case 'duration': {
                            const minDuration = item.querySelector('input[name*="[min_duration]"]')?.value;
                            const maxDuration = item.querySelector('input[name*="[max_duration]"]')?.value;
                            if (minDuration && maxDuration) {
                                conditionText = `${minDuration} - ${maxDuration} minutes`;
                            } else if (minDuration) {
                                conditionText = `> ${minDuration} minutes`;
                            } else if (maxDuration) {
                                conditionText = `< ${maxDuration} minutes`;
                            } else {
                                conditionText = 'Durée non spécifiée';
                            }
                            break;
                        }
                        default:
                            conditionText = 'Condition non spécifiée';
                    }
                    
                    const conditionTypeNames = {
                        'time': 'Plage horaire',
                        'day': 'Jour de la semaine',
                        'power': 'Puissance de charge',
                        'duration': 'Durée de charge'
                    };
                    
                    ratesHtml += `
                        <li class="py-3 flex flex-col">
                            <div class="flex items-center justify-between">
                                <div class="font-medium">${name}</div>
                                <div class="text-[#49ce7d] font-semibold">${price} EUR/kWh</div>
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                <span class="font-medium">${conditionTypeNames[conditionType] || 'Condition'}: </span>
                                ${conditionText}
                            </div>
                        </li>
                    `;
                });
                
                ratesHtml += '</ul>';
                summaryRatesContainer.innerHTML = ratesHtml;
            }
            
            // Fonction pour mettre à jour le récapitulatif des groupes
            function updateGroupsSummary() {
                const selectedGroups = document.querySelectorAll('.selected-groups .group-item');
                const summaryGroupsContainer = document.getElementById('summary-groups');
                
                if (!selectedGroups || selectedGroups.length === 0) {
                    summaryGroupsContainer.innerHTML = '<div class="text-sm text-gray-500">Aucun groupe sélectionné</div>';
                    return;
                }
                
                let groupsHtml = '<ul class="divide-y divide-gray-200">';
                
                selectedGroups.forEach(function(group) {
                    const groupName = group.getAttribute('data-group-name') || 'Groupe sans nom';
                    const chargingPointsCount = group.querySelector('.text-xs.text-gray-500')?.textContent || '';
                    
                    groupsHtml += `
                        <li class="py-2 flex items-center">
                            <div class="w-2 h-2 rounded-full bg-[#49ce7d]"></div>
                            <div class="ml-2">
                                <div class="text-sm font-medium">${groupName}</div>
                                <div class="text-xs text-gray-500">${chargingPointsCount}</div>
                            </div>
                        </li>
                    `;
                });
                
                groupsHtml += '</ul>';
                summaryGroupsContainer.innerHTML = groupsHtml;
            }
        });
    </script>
    @endpush
</div>