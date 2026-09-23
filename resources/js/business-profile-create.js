import EvonFormValidator from './libs/form-validator';
import EvonUtilities from './libs/utilities';

document.addEventListener('DOMContentLoaded', function() {
    // Get form element
    const form = document.getElementById('profileForm');
    
    // Elements
    const nameInput = document.getElementById('name');
    const descriptionInput = document.getElementById('description');
    const audienceOptions = document.querySelectorAll('.target-audience-option');
    const audienceCheckboxes = document.querySelectorAll('.target-audience-checkbox');
    const isPublicCheckbox = document.getElementById('is_public');
    const isActiveCheckbox = document.getElementById('is_active');
    const subscriptionPeriodSelect = document.getElementById('subscription_period');
    const baseFeeAmountInput = document.getElementById('base_fee_amount');
    const terminalFeeAmountInput = document.getElementById('terminal_fee_amount');
    const transactionFeeTypeCheckboxes = document.querySelectorAll('input[name="transaction_fee_type[]"]');
    const transactionFeeFixedAmountInput = document.getElementById('transaction_fee_fixed_amount');
    const transactionFeePercentageInput = document.getElementById('transaction_fee_percentage');
    const chargeFeeFixedAmountInput = document.getElementById('charge_fee_fixed_amount');
    const chargeFeePercentageInput = document.getElementById('charge_fee_percentage');
    const terminalCountInput = document.getElementById('terminal_count');

    // Summary elements
    const summaryNameElement = document.getElementById('summary-name');
    const summaryAudienceElement = document.getElementById('summary-audience');
    const summaryStatusElement = document.getElementById('summary-status');
    const summarySubscriptionElement = document.getElementById('summary-subscription');
    const summaryTransactionElement = document.getElementById('summary-transaction');
    const summaryChargeElement = document.getElementById('summary-charge');
    const summaryMonthlyCostElement = document.getElementById('summary-monthly-cost');
    const summaryTerminalCountElement = document.getElementById('summary-terminal-count');
    const summarySingleTerminalCostElement = document.getElementById('summary-single-terminal-cost');
    const configurationType = document.getElementById('config-status');

    // Form validation function
    function validateForm(event) {
        let isValid = true;
        const errors = [];
        
        // Reset error states
        document.querySelectorAll('.border-red-500').forEach(el => {
            el.classList.remove('border-red-500');
        });
        
        // Validate name
        if (!nameInput.value.trim()) {
            errors.push('Le nom du profil est requis');
            nameInput.classList.add('border-red-500');
            isValid = false;
        }
        
        // Validate audience selection
        const selectedAudiences = Array.from(audienceCheckboxes).filter(cb => cb.checked);
        if (selectedAudiences.length === 0) {
            errors.push('Au moins une audience doit être sélectionnée');
            audienceOptions.forEach(option => {
                option.classList.add('border-red-500');
            });
            isValid = false;
        }
        
        // Validate subscription configuration
        const baseFee = parseFloat(baseFeeAmountInput.value) || 0;
        const terminalFee = parseFloat(terminalFeeAmountInput.value) || 0;
        const period = subscriptionPeriodSelect.value;
        
        if (period && baseFee <= 0 && terminalFee <= 0) {
            errors.push('Vous devez définir au moins un montant pour les frais d\'abonnement');
            baseFeeAmountInput.classList.add('border-red-500');
            terminalFeeAmountInput.classList.add('border-red-500');
            isValid = false;
        } else if (!period && (baseFee > 0 || terminalFee > 0)) {
            errors.push('Vous devez sélectionner une période de facturation pour les frais d\'abonnement');
            subscriptionPeriodSelect.classList.add('border-red-500');
            isValid = false;
        }
        
        // Validation des frais de transaction (optionnels)
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
        // Si aucun type n'est coché, ne pas bloquer la soumission (le backend accepte ce cas)
        // Donc, pas de else bloquant ici
        
        // Validation de la configuration tarifaire globale (au moins une config)
        const hasSubscription = period && (baseFee > 0 || terminalFee > 0);
        const hasTransaction = (hasFixedFee && parseFloat(transactionFeeFixedAmountInput.value) > 0) || 
                             (hasPercentageFee && parseFloat(transactionFeePercentageInput.value) > 0);
        const hasCharge = (parseFloat(chargeFeeFixedAmountInput.value) > 0) || 
                         (parseFloat(chargeFeePercentageInput.value) > 0);
        
        if (!hasSubscription && !hasTransaction && !hasCharge) {
            errors.push('Vous devez définir au moins une configuration tarifaire');
            isValid = false;
        }
        
        if (!isValid) {
            event.preventDefault();
            alert('Veuillez corriger les erreurs suivantes :\n\n' + errors.join('\n'));
            
            // Scroll to first error
            const firstError = document.querySelector('.border-red-500');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
        
        return isValid;
    }
    
    // Attach validation to form submission
    if (form) {
        form.addEventListener('submit', validateForm);
    }
    
    // Audience selection handling
    audienceOptions.forEach(option => {
        option.addEventListener('click', function() {
            const checkbox = this.querySelector('.target-audience-checkbox');
            checkbox.checked = !checkbox.checked;
            
            if (checkbox.checked) {
                this.classList.add('border-green-500', 'bg-green-50');
                this.classList.remove('border-gray-300');
            } else {
                this.classList.remove('border-green-500', 'bg-green-50');
                this.classList.add('border-gray-300');
            }
            
            updateSummary();
        });
    });
    
    // Initialize visual state
    audienceCheckboxes.forEach(checkbox => {
        const option = checkbox.closest('.target-audience-option');
        if (checkbox.checked) {
            option.classList.add('border-green-500', 'bg-green-50');
            option.classList.remove('border-gray-300');
        }
    });
    
    // Transaction fee type toggles
    transactionFeeTypeCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const fixedSection = document.querySelector('.fee-fixed-section');
            const percentageSection = document.querySelector('.fee-percentage-section');
            
            if (this.value === 'fixed' && fixedSection) {
                fixedSection.classList.toggle('hidden', !this.checked);
            } else if (this.value === 'percentage' && percentageSection) {
                percentageSection.classList.toggle('hidden', !this.checked);
            }
            
            updateSummary();
        });
    });
    
    // Update summary function
    function updateSummary() {
        // Update name
        summaryNameElement.textContent = nameInput.value || '-';
        
        // Update audience
        const selectedAudiences = Array.from(audienceCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value === 'integrator' ? 'Intégrateurs' : 'Opérateurs');
        
        summaryAudienceElement.textContent = selectedAudiences.length > 0 
            ? selectedAudiences.join(', ') 
            : '-';
        
        // Update status
        summaryStatusElement.innerHTML = '';
        
        const visibilityBadge = document.createElement('span');
        visibilityBadge.className = `px-2 py-1 rounded-full text-xs mr-2 ${isPublicCheckbox.checked 
            ? 'bg-green-100 text-green-800' 
            : 'bg-gray-100 text-gray-800'}`;
        visibilityBadge.textContent = isPublicCheckbox.checked ? 'Public' : 'Privé';
        summaryStatusElement.appendChild(visibilityBadge);
        
        const activeBadge = document.createElement('span');
        activeBadge.className = `px-2 py-1 rounded-full text-xs ${isActiveCheckbox.checked 
            ? 'bg-green-100 text-green-800' 
            : 'bg-red-100 text-red-800'}`;
        activeBadge.textContent = isActiveCheckbox.checked ? 'Actif' : 'Inactif';
        summaryStatusElement.appendChild(activeBadge);
        
        // Update subscription fees
        const baseFee = parseFloat(baseFeeAmountInput.value) || 0;
        const terminalFee = parseFloat(terminalFeeAmountInput.value) || 0;
        const terminalCount = parseInt(terminalCountInput.value) || 10;
        const period = subscriptionPeriodSelect.value;
        
        let subscriptionText = '0.00 €';
        let monthlyCost = 0;
        
        if (period && (baseFee > 0 || terminalFee > 0)) {
            const totalCost = baseFee + (terminalFee * terminalCount);
            const singleTerminalCost = baseFee + terminalFee;
            
            switch (period) {
                case 'monthly':
                    monthlyCost = totalCost;
                    subscriptionText = `${totalCost.toFixed(2)} € / mois`;
                    break;
                case 'quarterly':
                    monthlyCost = totalCost / 3;
                    subscriptionText = `${totalCost.toFixed(2)} € / trimestre`;
                    break;
                case 'yearly':
                    monthlyCost = totalCost / 12;
                    subscriptionText = `${totalCost.toFixed(2)} € / an`;
                    break;
            }
            
            summarySingleTerminalCostElement.textContent = `${singleTerminalCost.toFixed(2)} €`;
        } else {
            summarySingleTerminalCostElement.textContent = '0.00 €';
        }
        
        summarySubscriptionElement.textContent = subscriptionText;
        summaryMonthlyCostElement.textContent = `${monthlyCost.toFixed(2)} €`;
        summaryTerminalCountElement.textContent = terminalCount;
        
        // Update transaction fees
        const hasFixedFee = Array.from(transactionFeeTypeCheckboxes).some(cb => cb.value === 'fixed' && cb.checked);
        const hasPercentageFee = Array.from(transactionFeeTypeCheckboxes).some(cb => cb.value === 'percentage' && cb.checked);
        const fixedAmount = parseFloat(transactionFeeFixedAmountInput.value) || 0;
        const percentageAmount = parseFloat(transactionFeePercentageInput.value) || 0;
        
        let transactionText = [];
        if (hasFixedFee && fixedAmount > 0) transactionText.push(`${fixedAmount.toFixed(2)} €`);
        if (hasPercentageFee && percentageAmount > 0) transactionText.push(`${percentageAmount.toFixed(2)} %`);
        
        summaryTransactionElement.textContent = transactionText.length > 0 ? transactionText.join(' + ') : '0.00 €';
        
        // Update charge fees
        const chargeFixed = parseFloat(chargeFeeFixedAmountInput.value) || 0;
        const chargePercent = parseFloat(chargeFeePercentageInput.value) || 0;
        
        let chargeText = [];
        if (chargeFixed > 0) chargeText.push(`${chargeFixed.toFixed(2)} €`);
        if (chargePercent > 0) chargeText.push(`${chargePercent.toFixed(2)} %`);
        
        summaryChargeElement.textContent = chargeText.length > 0 ? chargeText.join(' + ') : '0.00 €';
        
        // Update configuration status
        const isConfigComplete = nameInput.value.trim() !== '' &&
                               selectedAudiences.length > 0 &&
                               (period || transactionText.length > 0 || chargeText.length > 0);
        
        if (isConfigComplete) {
            configurationType.innerHTML = `
                <div class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-green-700 bg-green-100">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Configuration complète</span>
                </div>
            `;
        } else {
            configurationType.innerHTML = `
                <div class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-yellow-700 bg-yellow-100">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <span>Configuration incomplète</span>
                </div>
            `;
        }
        
        // Update cost estimate
        updateCostEstimate();
    }
    
    // Update cost estimate
    function updateCostEstimate() {
        const baseFee = parseFloat(baseFeeAmountInput.value) || 0;
        const terminalFee = parseFloat(terminalFeeAmountInput.value) || 0;
        const terminalCount = parseInt(terminalCountInput.value) || 10;
        const period = subscriptionPeriodSelect.value;
        
        const totalPerPeriod = baseFee + (terminalFee * terminalCount);
        const singleTerminalCost = baseFee + terminalFee;
        let monthlyEquivalent = 0;
        
        switch (period) {
            case 'monthly':
                monthlyEquivalent = totalPerPeriod;
                break;
            case 'quarterly':
                monthlyEquivalent = totalPerPeriod / 3;
                break;
            case 'yearly':
                monthlyEquivalent = totalPerPeriod / 12;
                break;
        }
        
        // Update estimate displays
        const estimatePerPeriod = document.getElementById('estimate-per-period');
        const estimateMonthly = document.getElementById('estimate-monthly');
        const estimatePerTerminal = document.getElementById('estimate-per-terminal');
        const estimateSingleTerminal = document.getElementById('estimate-single-terminal');
        
        if (estimatePerPeriod) estimatePerPeriod.textContent = `${totalPerPeriod.toFixed(2)} €`;
        if (estimateMonthly) estimateMonthly.textContent = `${monthlyEquivalent.toFixed(2)} €`;
        if (estimatePerTerminal) estimatePerTerminal.textContent = `${terminalFee.toFixed(2)} €`;
        if (estimateSingleTerminal) estimateSingleTerminal.textContent = `${singleTerminalCost.toFixed(2)} €`;
    }
    
    // Add event listeners for all inputs
    [nameInput, descriptionInput, isPublicCheckbox, isActiveCheckbox,
     subscriptionPeriodSelect, baseFeeAmountInput, terminalFeeAmountInput,
     transactionFeeFixedAmountInput, transactionFeePercentageInput,
     chargeFeeFixedAmountInput, chargeFeePercentageInput, terminalCountInput].forEach(element => {
        if (element) {
            element.addEventListener('input', updateSummary);
            element.addEventListener('change', updateSummary);
        }
    });
    
    // Initialize summary on page load
    updateSummary();
});