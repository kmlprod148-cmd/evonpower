/**
 * JavaScript Error Fix for Plan Creation Form
 * This script addresses the form field handling issues and fixes the JS error.
 */
document.addEventListener('DOMContentLoaded', function() {
    // Variables globales
    const planForm = document.getElementById('planForm');
    const baseRateInput = document.getElementById('base_rate');
    const rateTypeRadios = document.querySelectorAll('input[name="rate_type"]');
    const addRateButton = document.getElementById('addRateButton');
    const ratesContainer = document.getElementById('additionalRatesContainer');

    // Get all step navigation elements
    const steps = document.querySelectorAll('.flex.items-center.gap-3.px-4.py-2\\.5');
    const prevButton = document.getElementById('prevButton');
    const nextButton = document.getElementById('nextButton');
    const submitButton = document.getElementById('submitButton');

    // Get all step content sections
    const generalInfoSection = document.getElementById('general-info-section');
    const baseRateSection = document.getElementById('base-rate-section');
    const additionalRatesSection = document.getElementById('additional-rates-section');
    const groupsSection = document.getElementById('groups-section');
    const summarySection = document.getElementById('summary-section');

    // Form state tracking
    const currentStepInput = document.getElementById('current_step');
    let currentStep = parseInt(currentStepInput ? currentStepInput.value : '1') || 1; // Added null check

    // Initialize the form view based on current step
    updateFormView(currentStep);

    // Add click handlers to step navigation items
    if (steps) { // Added existence check
        steps.forEach((step, index) => {
            step.addEventListener('click', function() {
                if (validateCurrentStep()) {
                    goToStep(index + 1);
                }
            });
        });
    }

    // Next button click handler
    if (nextButton) { // Added existence check
        nextButton.addEventListener('click', function() {
            if (validateCurrentStep()) {
                goToStep(currentStep + 1);
            }
        });
    }

    // Previous button click handler
    if (prevButton) { // Added existence check
        prevButton.addEventListener('click', function() {
            goToStep(currentStep - 1);
        });
    }

    /**
     * Navigate to a specific step
     * @param {number} step The step number to navigate to
     */
    function goToStep(step) {
        // Ensure step is within valid range
        if (step < 1 || step > steps.length) {
            return;
        }

        // Update current step
        currentStep = step;
        if (currentStepInput) { // Added null check
            currentStepInput.value = step;
        }

        // Update UI
        updateFormView(step);

        // If on the summary step, generate the summary
        if (step === 5 && typeof window.updatePlanSummary === 'function') {
            window.updatePlanSummary();
        }

        // Scroll to top for better UX
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    /**
     * Update the form view based on the current step
     * @param {number} step The current step number
     */
    function updateFormView(step) {
        // Update step navigation styling
        if (steps) { // Added existence check
            steps.forEach((stepEl, index) => {
                const dot = stepEl.querySelector('div.w-3\\.5.h-3\\.5');
                const check = stepEl.querySelector('img');

                // Current step
                if (index + 1 === step) {
                    stepEl.classList.add('bg-[#49ce7d1f]');
                    if (dot) dot.classList.remove('hidden');
                    if (check) check.classList.add('hidden');
                }
                // Completed steps
                else if (index + 1 < step) {
                    stepEl.classList.remove('bg-[#49ce7d1f]');
                    if (dot) dot.classList.add('hidden');
                    if (check) check.classList.remove('hidden');
                }
                // Future steps
                else {
                    stepEl.classList.remove('bg-[#49ce7d1f]');
                    if (dot) dot.classList.add('hidden');
                    if (check) check.classList.remove('hidden');
                }
            });
        }

        // Show/hide content sections based on current step
        if (generalInfoSection) generalInfoSection.classList.toggle('hidden', step !== 1);
        if (baseRateSection) baseRateSection.classList.toggle('hidden', step !== 2);
        if (additionalRatesSection) additionalRatesSection.classList.toggle('hidden', step !== 3);
        if (groupsSection) groupsSection.classList.toggle('hidden', step !== 4);
        if (summarySection) summarySection.classList.toggle('hidden', step !== 5);

        // Update navigation buttons
        if (prevButton) prevButton.classList.toggle('hidden', step === 1);
        if (nextButton) nextButton.classList.toggle('hidden', step === 5);
        if (submitButton) submitButton.classList.toggle('hidden', step !== 5);

        // Update opacity for sections based on completion
        if (baseRateSection) baseRateSection.classList.toggle('opacity-50', step < 2);
        if (additionalRatesSection) additionalRatesSection.classList.toggle('opacity-50', step < 3);
        if (groupsSection) groupsSection.classList.toggle('opacity-50', step < 4);
        if (summarySection) summarySection.classList.toggle('opacity-50', step < 5);
    }

    /**
     * Validate the current step before proceeding
     * @returns {boolean} True if validation passes, false otherwise
     */
    function validateCurrentStep() {
        let valid = true;
        let errorMessage = '';

        // Remove previous error messages
        const existingErrorContainer = document.getElementById('validation-error');
        if (existingErrorContainer) {
            existingErrorContainer.remove();
        }

        switch (currentStep) {
            case 1: // General Information
                const name = document.getElementById('name');
                const selectedRateType = document.querySelector('input[name="rate_type"]:checked');

                if (!name || !name.value.trim()) {
                    if (name) name.classList.add('border-red-500');
                    errorMessage = 'Le nom du plan est requis';
                    valid = false;
                } else if (name) {
                    name.classList.remove('border-red-500');
                }

                if (!selectedRateType) {
                    errorMessage = errorMessage || 'Le type de tarification est requis';
                    valid = false;
                    // Optionally, add a visual indicator to the rate type section
                    const rateTypeContainer = document.querySelector('.grid.grid-cols-1.md\\:grid-cols-3.gap-3');
                    if (rateTypeContainer) {
                        rateTypeContainer.classList.add('border', 'border-red-500', 'rounded-lg', 'p-2');
                    }
                } else {
                    const rateTypeContainer = document.querySelector('.grid.grid-cols-1.md\\:grid-cols-3.gap-3');
                    if (rateTypeContainer) {
                        rateTypeContainer.classList.remove('border', 'border-red-500', 'rounded-lg', 'p-2');
                    }
                }
                break;

            case 2: // Base Rate
                const baseRate = document.getElementById('base_rate');

                if (!baseRate || !baseRate.value || parseFloat(baseRate.value) < 0) {
                    if (baseRate) baseRate.classList.add('border-red-500');
                    errorMessage = 'Le tarif de base est requis et doit être positif';
                    valid = false;
                } else if (baseRate) {
                    baseRate.classList.remove('border-red-500');
                }
                break;

            case 3: // Additional Rates
                // We validate each additional rate, but don't block progression
                // if there are no rates defined
                const rateItems = document.querySelectorAll('#additionalRatesContainer .rate-item');

                rateItems.forEach((item, index) => {
                    const nameInput = item.querySelector('input[name*="[name]"]');
                    const priceInput = item.querySelector('input[name*="[price]"]');

                    if (nameInput && !nameInput.value.trim()) {
                        nameInput.classList.add('border-red-500');
                        errorMessage = 'Tous les tarifs supplémentaires doivent avoir un nom';
                        valid = false;
                    } else if (nameInput) {
                        nameInput.classList.remove('border-red-500');
                    }

                    if (priceInput && (!priceInput.value || parseFloat(priceInput.value) < 0)) {
                        priceInput.classList.add('border-red-500');
                        errorMessage = errorMessage || 'Les prix doivent être positifs';
                        valid = false;
                    } else if (priceInput) {
                        priceInput.classList.remove('border-red-500');
                    }

                    // Validate condition fields based on type
                    const conditionType = item.querySelector('select[name*="[condition_type]"]');
                    if (conditionType) {
                        const type = conditionType.value;

                        if (type === 'time') {
                            const timeStart = item.querySelector('input[name*="[time_start]"]');
                            const timeEnd = item.querySelector('input[name*="[time_end]"]');

                            if (timeStart && timeEnd && timeStart.value && timeEnd.value) {
                                // Compare times to ensure end is after start
                                if (timeStart.value >= timeEnd.value) {
                                    timeStart.classList.add('border-red-500');
                                    timeEnd.classList.add('border-red-500');
                                    errorMessage = errorMessage || 'L\'heure de fin doit être après l\'heure de début';
                                    valid = false;
                                } else {
                                    timeStart.classList.remove('border-red-500');
                                    timeEnd.classList.remove('border-red-500');
                                }
                            }
                        } else if (type === 'day') {
                            const dayCheckboxes = item.querySelectorAll('input[name*="[days][]"]:checked');

                            // At least one day must be selected
                            if (dayCheckboxes.length === 0) {
                                const dayCondition = item.querySelector('.day-condition');
                                if (dayCondition) {
                                    dayCondition.classList.add('border', 'border-red-500', 'rounded-lg');
                                    errorMessage = errorMessage || 'Sélectionnez au moins un jour de la semaine';
                                    valid = false;
                                }
                            } else {
                                const dayCondition = item.querySelector('.day-condition');
                                if (dayCondition) {
                                    dayCondition.classList.remove('border', 'border-red-500');
                                }
                            }
                        } else if (type === 'power') {
                            const minPower = item.querySelector('input[name*="[power_min]"]'); // Corrected name
                            const maxPower = item.querySelector('input[name*="[power_max]"]'); // Corrected name

                            // If both are set, ensure min < max
                            if (minPower && maxPower && minPower.value && maxPower.value) {
                                if (parseFloat(minPower.value) >= parseFloat(maxPower.value)) {
                                    minPower.classList.add('border-red-500');
                                    maxPower.classList.add('border-red-500');
                                    errorMessage = errorMessage || 'La puissance minimale doit être inférieure à la puissance maximale';
                                    valid = false;
                                } else {
                                    minPower.classList.remove('border-red-500');
                                    maxPower.classList.remove('border-red-500');
                                }
                            }
                        } else if (type === 'duration') {
                            const minDuration = item.querySelector('input[name*="[duration_min]"]'); // Corrected name
                            const maxDuration = item.querySelector('input[name*="[duration_max]"]'); // Corrected name

                            // If both are set, ensure min < max
                            if (minDuration && maxDuration && minDuration.value && maxDuration.value) {
                                if (parseInt(minDuration.value) >= parseInt(maxDuration.value)) {
                                    minDuration.classList.add('border-red-500');
                                    maxDuration.classList.add('border-red-500');
                                    errorMessage = errorMessage || 'La durée minimale doit être inférieure à la durée maximale';
                                    valid = false;
                                } else {
                                    minDuration.classList.remove('border-red-500');
                                    maxDuration.classList.remove('border-red-500');
                                }
                            }
                        }
                    }
                });
                break;

            case 4: // Groups
                // No validation required for groups
                break;

            case 5: // Summary
                // No validation required for summary
                break;
        }

        // Display error message if validation fails
        if (!valid && errorMessage) {
            let errorContainer = document.getElementById('validation-error');
            if (!errorContainer) {
                errorContainer = document.createElement('div');
                errorContainer.id = 'validation-error';
                errorContainer.className = 'bg-red-50 border-l-4 border-red-500 p-4 mb-4 mt-2';
                errorContainer.innerHTML = `
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700" id="error-message">${errorMessage}</p>
                        </div>
                    </div>
                `;

                // Add the error message to the appropriate section
                switch (currentStep) {
                    case 1:
                        if (generalInfoSection) generalInfoSection.prepend(errorContainer);
                        break;
                    case 2:
                        if (baseRateSection) baseRateSection.prepend(errorContainer);
                        break;
                    case 3:
                        if (additionalRatesSection) additionalRatesSection.prepend(errorContainer);
                        break;
                    case 4:
                        if (groupsSection) groupsSection.prepend(errorContainer);
                        break;
                    case 5:
                        if (summarySection) summarySection.prepend(errorContainer);
                        break;
                }
            } else {
                const errorMessageElement = document.getElementById('error-message');
                if (errorMessageElement) errorMessageElement.textContent = errorMessage;
            }
        }

        return valid;
    }

    // Handle rate type selection visibility
    function handleRateTypeChange() {
        const selectedType = document.querySelector('input[name="rate_type"]:checked')?.value;
        const baseRateContainer = document.getElementById('base_rate_container');
        const pricePerKwhContainer = document.getElementById('price_per_kwh_container');
        const pricePerMinuteContainer = document.getElementById('price_per_minute_container');

        // Hide all specific rate input containers initially
        if (baseRateContainer) baseRateContainer.classList.add('hidden');
        if (pricePerKwhContainer) pricePerKwhContainer.classList.add('hidden');
        if (pricePerMinuteContainer) pricePerMinuteContainer.classList.add('hidden');

        // Show the relevant container based on selected type
        if (selectedType === 'fixed') {
            if (baseRateContainer) baseRateContainer.classList.remove('hidden');
        } else if (selectedType === 'energy') {
            if (pricePerKwhContainer) pricePerKwhContainer.classList.remove('hidden');
        } else if (selectedType === 'time') {
            if (pricePerMinuteContainer) pricePerMinuteContainer.classList.remove('hidden');
        }

        // Update label for additional rates if applicable (this part might need more context from the HTML)
        const label = document.querySelector('.additional-rate-price-label');
        if (label) {
            if (selectedType === 'energy') {
                label.textContent = 'Montant (EUR/kWh)';
            } else if (selectedType === 'time') {
                label.textContent = 'Montant (EUR/min)';
            } else {
                label.textContent = 'Montant (EUR)'; // Default for fixed or if no type selected
            }
        }
    }

    // Attach event listeners to all rate type radio buttons
    if (rateTypeRadios) {
        rateTypeRadios.forEach(radio => {
            radio.addEventListener('change', handleRateTypeChange);
        });
    }

    // Initial call to set correct visibility on page load
    handleRateTypeChange();

    // Fix additional rate item creation (from artifact)
    if (addRateButton && ratesContainer) {
        const rateTemplate = document.getElementById('rateItemTemplate');
        let rateIndex = document.querySelectorAll('.rate-item').length;

        addRateButton.addEventListener('click', function() {
            // Remove empty message if exists
            const emptyMessage = document.getElementById('empty-rates-message');
            if (emptyMessage) {
                emptyMessage.remove();
            }

            // Clone template
            if (!rateTemplate) {
                console.error('Rate item template not found!');
                return;
            }
            const newRate = rateTemplate.content.cloneNode(true);

            // Update all INDEX placeholders
            newRate.querySelectorAll('[name*="INDEX"]').forEach(el => {
                el.name = el.name.replace('INDEX', rateIndex);
            });

            newRate.querySelectorAll('[id*="INDEX"]').forEach(el => {
                el.id = el.id.replace('INDEX', rateIndex);
            });

            newRate.querySelectorAll('[data-rate-id="INDEX"]').forEach(el => {
                el.dataset.rateId = rateIndex;
            });

            newRate.querySelectorAll('[for*="INDEX"]').forEach(el => {
                el.setAttribute('for', el.getAttribute('for').replace('INDEX', rateIndex));
            });

            // Set up event handlers
            const newRateElement = newRate.querySelector('.rate-item');

            // Handle condition type changes
            const conditionSelect = newRateElement.querySelector('.condition-type-select');
            if (conditionSelect) {
                conditionSelect.addEventListener('change', function() {
                    const conditionType = this.value;
                    const rateItem = this.closest('.rate-item');

                    // Hide all condition fields
                    if (rateItem) {
                        rateItem.querySelectorAll('.condition-fields').forEach(field => {
                            field.classList.add('hidden');
                        });

                        // Show selected condition fields
                        const selectedFields = rateItem.querySelector('.' + conditionType + '-condition');
                        if (selectedFields) {
                            selectedFields.classList.remove('hidden');
                        }
                    }
                });
            }

            // Handle remove button
            const removeButton = newRateElement.querySelector('.remove-rate');
            if (removeButton) {
                removeButton.addEventListener('click', function() {
                    const rateItemToRemove = this.closest('.rate-item');
                    if (rateItemToRemove) {
                        rateItemToRemove.remove();

                        // Show empty message if no rates left
                        if (ratesContainer.querySelectorAll('.rate-item').length === 0) {
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
                    }
                });
            }

            // Add new rate to container
            ratesContainer.appendChild(newRate);

            // Increment rate index
            rateIndex++;
        });
    }

    // Form submission handling
    if (planForm) {
        planForm.addEventListener('submit', function(e) {
            // Validate form before submission
            if (!validateForm()) {
                e.preventDefault();
                return false;
            }

            // Set loading state
            const submitButton = document.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = `
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Traitement en cours...
                `;
            }

            // Extra handling for rate type to ensure only relevant fields are submitted
            const selectedTypeRadio = document.querySelector('input[name="rate_type"]:checked');
            if (selectedTypeRadio) {
                const selectedType = selectedTypeRadio.value;

                const baseRateInput = document.getElementById('base_rate');
                const pricePerKwhInput = document.getElementById('price_per_kwh');
                const pricePerMinuteInput = document.getElementById('price_per_minute');

                // Set non-selected rate type values to 0 before submission
                if (selectedType === 'fixed') {
                    if (pricePerKwhInput) pricePerKwhInput.value = "0";
                    if (pricePerMinuteInput) pricePerMinuteInput.value = "0";
                } else if (selectedType === 'energy') {
                    if (baseRateInput) baseRateInput.value = "0";
                    if (pricePerMinuteInput) pricePerMinuteInput.value = "0";
                } else if (selectedType === 'time') {
                    if (baseRateInput) baseRateInput.value = "0";
                    if (pricePerKwhInput) pricePerKwhInput.value = "0";
                }
            }

            return true;
        });
    }

    // Initial call to set visibility based on default selected rate type
    if (rateTypeSelect) {
        const event = new Event('change');
        rateTypeSelect.dispatchEvent(event);
    }
});