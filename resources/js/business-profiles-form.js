document.addEventListener('DOMContentLoaded', function() {
    // Définition des variables globales
    const nameInput = document.getElementById('name');
    const descriptionInput = document.getElementById('description');
    const isPublicCheckbox = document.getElementById('is_public');
    const isActiveCheckbox = document.getElementById('is_active');
    
    // Variables pour les frais d'abonnement combinés
    const subscriptionPeriodSelect = document.getElementById('subscription_period');
    const baseFeeAmountInput = document.getElementById('base_fee_amount');
    const terminalFeeAmountInput = document.getElementById('terminal_fee_amount');
    
    // Variables pour les frais de transaction
    const transactionFeeTypeCheckboxes = document.querySelectorAll('input[name="transaction_fee_type[]"]');
    const transactionFeeFixedAmountInput = document.getElementById('transaction_fee_fixed_amount');
    const transactionFeePercentageInput = document.getElementById('transaction_fee_percentage');
    
    // Variables pour les frais de recharge
    const chargeFeeFixedAmountInput = document.getElementById('charge_fee_fixed_amount');
    const chargeFeePercentageInput = document.getElementById('charge_fee_percentage');
    
    // Variables pour l'estimation des coûts
    const estimatePerPeriodSpan = document.getElementById('estimate-per-period');
    const estimateMonthlySpan = document.getElementById('estimate-monthly');
    const estimatePerTerminalSpan = document.getElementById('estimate-per-terminal');
    
    // Nombre fixe de bornes pour l'estimation
    const DEFAULT_TERMINALS = 10;

    // Vérifier si le montant de frais de base est à 0, désactiver la période
    function checkSubscriptionFees() {
        if (parseFloat(baseFeeAmountInput.value) <= 0 && parseFloat(terminalFeeAmountInput.value) <= 0) {
            subscriptionPeriodSelect.value = '';
        }
        
        updateSummary();
        updateCostEstimate();
    }
    
    baseFeeAmountInput.addEventListener('input', checkSubscriptionFees);
    terminalFeeAmountInput.addEventListener('input', checkSubscriptionFees);
    
    // Gestion des frais de transaction
    function updateTransactionFees() {
        updateSummary();
        updateCostEstimate();
    }
    
    transactionFeeFixedAmountInput.addEventListener('input', updateTransactionFees);
    transactionFeePercentageInput.addEventListener('input', updateTransactionFees);
    chargeFeeFixedAmountInput.addEventListener('input', updateTransactionFees);
    chargeFeePercentageInput.addEventListener('input', updateTransactionFees);
    
    // Ajouter des écouteurs d'événements pour les cases à cocher des types de frais
    transactionFeeTypeCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateTransactionFees);
    });
    
    // Function to validate type selection specifically
    function validateFormTypeSelection() {
        const checkedBoxes = document.querySelectorAll('.target-type-checkbox:checked');
        const typeOptions = document.querySelectorAll('.partnerType-option');
        
        if (checkedBoxes.length === 0) {
            // Add error styling
            typeOptions.forEach(el => {
                el.classList.add('border-red-500');
                el.classList.add('bg-red-50');
            });
            return false;
        } else {
            // Remove error styling
            typeOptions.forEach(el => {
                el.classList.remove('border-red-500');
                el.classList.remove('bg-red-50');
            });
            return true;
        }
    }
    
    // Gestion de la sélection du type de partenaire (checkboxes)
    const partnerTypeOptions = document.querySelectorAll('.partnerType-option');
    partnerTypeOptions.forEach(option => {
        option.addEventListener('click', function() {
            const checkbox = this.querySelector('.target-type-checkbox');
            
            // Toggle checkbox state
            checkbox.checked = !checkbox.checked;
            
            // Update visual state based on checkbox
            if (checkbox.checked) {
                this.classList.add('bg-green-50', 'border-green-500');
                this.classList.remove('border-gray-300');
            } else {
                this.classList.remove('bg-green-50', 'border-green-500');
                this.classList.add('border-gray-300');
            }
            
            updateSummary();
            validateFormTypeSelection(); // Validate type selection on change
        });
    });

    // Initialiser l'état visuel basé sur les cases cochées au chargement
    const typeCheckboxes = document.querySelectorAll('.target-type-checkbox');
    typeCheckboxes.forEach(checkbox => {
        const option = checkbox.closest('.partnerType-option');
        if (checkbox.checked) {
            option.classList.add('bg-green-50', 'border-green-500');
            option.classList.remove('border-gray-300');
        } else {
            option.classList.remove('bg-green-50', 'border-green-500');
            option.classList.add('border-gray-300');
        }
    });
    
    // Calculer et mettre à jour l'estimation des coûts
    function updateCostEstimate() {
        // Utiliser une valeur fixe de 10 bornes
        const numTerminals = DEFAULT_TERMINALS;
        const baseFee = parseFloat(baseFeeAmountInput.value) || 0;
        const terminalFee = parseFloat(terminalFeeAmountInput.value) || 0;
        const period = subscriptionPeriodSelect.value;
        
        let totalPerPeriod = baseFee + (terminalFee * numTerminals);
        let monthlyEquivalent = 0;
        let perTerminal = terminalFee;
        
        // Calculer l'équivalent mensuel
        switch(period) {
            case 'monthly':
                monthlyEquivalent = totalPerPeriod;
                break;
            case 'quarterly':
                monthlyEquivalent = totalPerPeriod / 3;
                break;
            case 'yearly':
                monthlyEquivalent = totalPerPeriod / 12;
                break;
            default:
                totalPerPeriod = 0;
                monthlyEquivalent = 0;
                perTerminal = 0;
        }
        
        // Mettre à jour l'affichage
        estimatePerPeriodSpan.textContent = totalPerPeriod.toFixed(2) + ' €' + (period ? ` (${getPeriodLabel(period)})` : '');
        estimateMonthlySpan.textContent = monthlyEquivalent.toFixed(2) + ' €';
        estimatePerTerminalSpan.textContent = perTerminal.toFixed(2) + ' €';
        
        // Mettre à jour le résumé
        document.getElementById('summary-monthly-cost').textContent = monthlyEquivalent.toFixed(2) + ' €';
        document.getElementById('summary-cost-details').textContent = 
            `Basé sur ${numTerminals} bornes fixes${period ? ` avec forfait ${getPeriodLabel(period).toLowerCase()}` : ''}`;
    }
    
    function getPeriodLabel(period) {
        switch(period) {
            case 'monthly': return 'Mensuel';
            case 'quarterly': return 'Trimestriel';
            case 'yearly': return 'Annuel';
            default: return period || 'Non défini';
        }
    }
    
    // Mettre à jour lorsque les valeurs changent
    subscriptionPeriodSelect.addEventListener('change', function() {
        updateCostEstimate();
        updateSummary();
    });
    
    // Écouteurs d'événements pour tous les champs qui affectent le résumé
    [nameInput, descriptionInput].forEach(element => {
        if (element) element.addEventListener('input', updateSummary);
    });
    
    [isPublicCheckbox, isActiveCheckbox].forEach(element => {
        if (element) element.addEventListener('change', updateSummary);
    });
    
    // Fonction de mise à jour du récapitulatif
    function updateSummary() {
        // Mise à jour du nom
        const nameValue = nameInput.value.trim();
        document.getElementById('summary-name').textContent = nameValue || '-';
        
        // Mise à jour du type de profil (checkboxes)
        const selectedTypes = Array.from(document.querySelectorAll('.target-type-checkbox:checked'))
                                   .map(cb => cb.value);
        let typeLabel = '-';
        if (selectedTypes.length > 0) {
            typeLabel = selectedTypes.map(typeValue => {
                switch(typeValue) {
                    case 'integrator': return 'Intégrateurs';
                    case 'operator': return 'Opérateurs';
                    default: return '';
                }
            }).filter(label => label).join(' / '); // Join selected types with " / "
        }
        document.getElementById('summary-type').textContent = typeLabel;
        
        // Mise à jour du statut
        const statusContainer = document.getElementById('summary-status');
        statusContainer.innerHTML = '';
        
        // Public/Privé
        const visibilitySpan = document.createElement('span');
        visibilitySpan.className = 'px-2 py-1 rounded-full text-xs mr-2 ' + 
            (isPublicCheckbox.checked ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800');
        visibilitySpan.textContent = isPublicCheckbox.checked ? 'Public' : 'Privé';
        statusContainer.appendChild(visibilitySpan);
        
        // Actif/Inactif
        const activeSpan = document.createElement('span');
        activeSpan.className = 'px-2 py-1 rounded-full text-xs ' + 
            (isActiveCheckbox.checked ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800');
        activeSpan.textContent = isActiveCheckbox.checked ? 'Actif' : 'Inactif';
        statusContainer.appendChild(activeSpan);
        
        // Mise à jour des frais d'abonnement
        let subscriptionText = '0.00 €';
        if (subscriptionPeriodSelect.value && (parseFloat(baseFeeAmountInput.value) > 0 || parseFloat(terminalFeeAmountInput.value) > 0)) {
            const baseFee = parseFloat(baseFeeAmountInput.value) || 0;
            const terminalFee = parseFloat(terminalFeeAmountInput.value) || 0;
            const terminalCount = DEFAULT_TERMINALS;
            
            let periodLabel = '';
            switch (subscriptionPeriodSelect.value) {
                case 'monthly':
                    periodLabel = ' (Mensuel)';
                    break;
                case 'quarterly':
                    periodLabel = ' (Trimestriel)';
                    break;
                case 'yearly':
                    periodLabel = ' (Annuel)';
                    break;
            }
            
            const totalFee = baseFee + (terminalFee * terminalCount);
            subscriptionText = totalFee.toFixed(2) + ' €' + periodLabel;
            
            if (terminalFee > 0) {
                subscriptionText += ` dont ${terminalFee.toFixed(2)} € par borne`;
            }
        }
        document.getElementById('summary-subscription').textContent = subscriptionText;
        
        // Mise à jour des frais par transaction
        let transactionText = '0.00 €';
        const hasFixedFee = Array.from(transactionFeeTypeCheckboxes).some(cb => cb.value === 'fixed' && cb.checked);
        const hasPercentageFee = Array.from(transactionFeeTypeCheckboxes).some(cb => cb.value === 'percentage' && cb.checked);
        
        if (hasFixedFee && hasPercentageFee) {
            const fixedAmount = parseFloat(transactionFeeFixedAmountInput.value) || 0;
            const percentageAmount = parseFloat(transactionFeePercentageInput.value) || 0;
            if (fixedAmount > 0 && percentageAmount > 0) {
                transactionText = `${fixedAmount.toFixed(2)} € + ${percentageAmount.toFixed(2)} %`;
            } else if (fixedAmount > 0) {
                transactionText = `${fixedAmount.toFixed(2)} € (fixe)`;
            } else if (percentageAmount > 0) {
                transactionText = `${percentageAmount.toFixed(2)} % (pourcentage)`;
            }
        } else if (hasFixedFee) {
            const fixedAmount = parseFloat(transactionFeeFixedAmountInput.value) || 0;
            if (fixedAmount > 0) {
                transactionText = `${fixedAmount.toFixed(2)} € (fixe)`;
            }
        } else if (hasPercentageFee) {
            const percentageAmount = parseFloat(transactionFeePercentageInput.value) || 0;
            if (percentageAmount > 0) {
                transactionText = `${percentageAmount.toFixed(2)} % (pourcentage)`;
            }
        }
        document.getElementById('summary-transaction').textContent = transactionText;
        
        // Mise à jour des frais par recharge
        let chargeText = '0.00 €';
        const chargeFixedAmount = parseFloat(chargeFeeFixedAmountInput.value) || 0;
        const chargePercentage = parseFloat(chargeFeePercentageInput.value) || 0;
        
        if (chargeFixedAmount > 0 && chargePercentage > 0) {
            chargeText = `${chargeFixedAmount.toFixed(2)} € + ${chargePercentage.toFixed(2)} %`;
        } else if (chargeFixedAmount > 0) {
            chargeText = `${chargeFixedAmount.toFixed(2)} € (fixe)`;
        } else if (chargePercentage > 0) {
            chargeText = `${chargePercentage.toFixed(2)} % (pourcentage)`;
        }
        document.getElementById('summary-charge').textContent = chargeText;
        
        // Mettre à jour l'estimation des coûts
        updateCostEstimate();
        
        // Mise à jour du statut de configuration
        const configStatus = document.getElementById('config-status');
        const hasValidConfiguration = nameValue && selectedTypes.length > 0 &&
            ((subscriptionPeriodSelect.value && (parseFloat(baseFeeAmountInput.value) > 0 || parseFloat(terminalFeeAmountInput.value) > 0)) || 
             (hasFixedFee && parseFloat(transactionFeeFixedAmountInput.value) > 0) || 
             (hasPercentageFee && parseFloat(transactionFeePercentageInput.value) > 0) || 
             (chargeFixedAmount > 0) || 
             (chargePercentage > 0));
        
        if (hasValidConfiguration) {
            configStatus.innerHTML = `
                <div class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-green-700 bg-green-100">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span>Configuration prête</span>
                </div>
            `;
        } else {
            configStatus.innerHTML = `
                <div class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-yellow-700 bg-yellow-100">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <span>Configuration incomplète</span>
                </div>
            `;
        }
    }
    
    // Initialiser le récapitulatif au chargement
    updateSummary();
    
    // Fonction de validation du formulaire
    window.validateForm = function(event) {
        let isValid = true;
        const errors = [];
        
        // Reset error states
        nameInput.classList.remove('border-red-500');
        subscriptionPeriodSelect.classList.remove('border-red-500');
        baseFeeAmountInput.classList.remove('border-red-500');
        terminalFeeAmountInput.classList.remove('border-red-500');
        transactionFeeFixedAmountInput.classList.remove('border-red-500');
        transactionFeePercentageInput.classList.remove('border-red-500');
        document.querySelectorAll('.partnerType-option').forEach(el => el.classList.remove('border-red-500'));
        
        // Required fields validation
        if (!nameInput.value.trim()) {
            errors.push('Le nom du profil est requis');
            nameInput.classList.add('border-red-500');
            isValid = false;
        }
        
        // Type selection validation
        const selectedTypes = document.querySelectorAll('.target-type-checkbox:checked');
        if (selectedTypes.length === 0) {
            errors.push('Au moins un type de profil doit être sélectionné');
            document.querySelectorAll('.partnerType-option').forEach(el => {
                el.classList.add('border-red-500');
            });
            isValid = false;
        }
        
        // Validate subscription configuration
        if (subscriptionPeriodSelect.value) {
            const baseFee = parseFloat(baseFeeAmountInput.value) || 0;
            const terminalFee = parseFloat(terminalFeeAmountInput.value) || 0;
            
            if (baseFee <= 0 && terminalFee <= 0) {
                errors.push('Vous devez définir au moins un montant pour les frais d\'abonnement');
                baseFeeAmountInput.classList.add('border-red-500');
                terminalFeeAmountInput.classList.add('border-red-500');
                isValid = false;
            }
        } else if ((parseFloat(baseFeeAmountInput.value) > 0 || parseFloat(terminalFeeAmountInput.value) > 0)) {
            errors.push('Vous devez sélectionner une période de facturation pour les frais d\'abonnement');
            subscriptionPeriodSelect.classList.add('border-red-500');
            isValid = false;
        }
        
        // Validate transaction fee configuration
        const hasFixedFee = Array.from(transactionFeeTypeCheckboxes).some(cb => cb.value === 'fixed' && cb.checked);
        const hasPercentageFee = Array.from(transactionFeeTypeCheckboxes).some(cb => cb.value === 'percentage' && cb.checked);
        
        if (hasFixedFee && parseFloat(transactionFeeFixedAmountInput.value) <= 0) {
            errors.push('Le montant fixe des frais de transaction doit être supérieur à 0');
            transactionFeeFixedAmountInput.classList.add('border-red-500');
            isValid = false;
        }
        
        if (hasPercentageFee && parseFloat(transactionFeePercentageInput.value) <= 0) {
            errors.push('Le pourcentage des frais de transaction doit être supérieur à 0');
            transactionFeePercentageInput.classList.add('border-red-500');
            isValid = false;
        }
        
        // Ensure at least one pricing configuration is set
        const hasFixedChargeFee = parseFloat(chargeFeeFixedAmountInput.value) > 0;
        const hasPercentageChargeFee = parseFloat(chargeFeePercentageInput.value) > 0;
        
        const hasPricingConfig = 
            (subscriptionPeriodSelect.value && (parseFloat(baseFeeAmountInput.value) > 0 || parseFloat(terminalFeeAmountInput.value) > 0)) || 
            (hasFixedFee && parseFloat(transactionFeeFixedAmountInput.value) > 0) || 
            (hasPercentageFee && parseFloat(transactionFeePercentageInput.value) > 0) ||
            hasFixedChargeFee ||
            hasPercentageChargeFee;
        
        if (!hasPricingConfig) {
            errors.push('Vous devez définir au moins une configuration tarifaire');
            subscriptionPeriodSelect.classList.add('border-red-500');
            chargeFeeFixedAmountInput.classList.add('border-red-500');
            chargeFeePercentageInput.classList.add('border-red-500');
            isValid = false;
        }
        
        if (!isValid) {
            event.preventDefault();
            alert('Veuillez corriger les erreurs suivantes :\n' + errors.join('\n'));
            return false;
        }
        return true;
    };
    
    // Ajouter la classe "required-field" pour les champs obligatoires
    function markRequiredFields() {
        const requiredLabels = document.querySelectorAll('label[for="name"]');
        requiredLabels.forEach(label => {
            if (!label.classList.contains('required-field')) {
                label.classList.add('required-field');
            }
        });
    }
    
    markRequiredFields();
});