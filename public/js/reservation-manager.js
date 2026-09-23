/**
 * Reservation Manager - Improved and simplified reservation handling
 * Handles the reservation form logic with better error handling and UX
 */
class ReservationManager {
    constructor() {
        this.selectedReservationType = null;
        this.selectedReservationValue = null;
        this.selectedTimeSlot = null;
        this.reserveButton = document.getElementById('reserve-btn');
        this.isSubmitting = false;
        
        this.init();
    }
    
    init() {
        if (!this.reserveButton) {
            console.error('Reserve button not found!');
            return;
        }
        
        this.setupEventListeners();
        this.initializePricingData();
        this.updateUI();
    }
    
    setupEventListeners() {
        // Reservation type selection
        document.querySelectorAll('.reservation-option[data-type]').forEach(option => {
            option.addEventListener('click', (e) => this.handleReservationTypeSelection(e));
        });
        
        // Duration buttons
        document.querySelectorAll('.duration-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.handleDurationSelection(e));
        });
        
        // Custom value input
        const customValueInput = document.getElementById('custom-value');
        if (customValueInput) {
            customValueInput.addEventListener('input', (e) => this.handleCustomValueInput(e));
        }
        
        // Time selection
        document.querySelectorAll('.time-slot').forEach(slot => {
            slot.addEventListener('click', (e) => this.handleTimeSlotSelection(e));
        });
        
        // Custom time input
        const customTimeInput = document.getElementById('custom-time');
        if (customTimeInput) {
            customTimeInput.addEventListener('change', (e) => this.handleCustomTimeInput(e));
        }
        
        // Current time button
        const useCurrentTimeBtn = document.getElementById('use-current-time');
        if (useCurrentTimeBtn) {
            useCurrentTimeBtn.addEventListener('click', (e) => this.handleCurrentTimeClick(e));
        }
        
        // Reserve button
        this.reserveButton.addEventListener('click', (e) => this.handleReservationSubmit(e));
    }
    
    initializePricingData() {
        // Get pricing data from the page
        this.activationFee = parseFloat(window.pricingData?.activationFee || 0);
        this.pricePerKwh = parseFloat(window.pricingData?.pricePerKwh || 0);
        this.pricePerMinute = parseFloat(window.pricingData?.pricePerMinute || 0);
        this.vatRate = parseFloat(window.pricingData?.vatRate || 0);
        this.currency = window.pricingData?.currency || 'MAD';
        this.pricingPlan = window.pricingData?.pricingPlan || {};
        this.chargingPoint = window.pricingData?.chargingPoint || {};
        
        this.allowedReservationTypes = this.getAllowedReservationTypes();
        this.maxDurationMinutes = this.pricingPlan.max_duration || 180;
        this.maxEnergyKwh = this.getMaxEnergyKwh();
        
        this.updateReservationOptionsVisibility();
        this.updateDurationButtonsVisibility();
        this.updateLimitInfo();
    }
    
    getAllowedReservationTypes() {
        const rateType = this.pricingPlan.rate_type;
        switch (rateType) {
            case 'time':
            case 'minute':
                return ['minute'];
            case 'energy':
            case 'kwh':
                return ['kwh'];
            case 'mixed':
            case 'both':
                return ['kwh', 'minute'];
            default:
                const allowedTypes = [];
                if (this.pricingPlan.price_per_kwh > 0) allowedTypes.push('kwh');
                if (this.pricingPlan.price_per_minute > 0) allowedTypes.push('minute');
                return allowedTypes.length > 0 ? allowedTypes : ['kwh', 'minute'];
        }
    }
    
    getMaxEnergyKwh() {
        if (this.pricingPlan.max_energy) {
            return this.pricingPlan.max_energy;
        }
        if (this.maxDurationMinutes && this.chargingPoint.power_output) {
            return this.chargingPoint.power_output * (this.maxDurationMinutes / 60);
        }
        return null;
    }
    
    updateReservationOptionsVisibility() {
        document.querySelectorAll('.reservation-option[data-type]').forEach(option => {
            const reservationType = option.dataset.type;
            if (this.allowedReservationTypes.includes(reservationType)) {
                option.style.display = 'block';
                option.style.opacity = '1';
                option.style.pointerEvents = 'auto';
                option.classList.remove('disabled');
            } else {
                option.style.display = 'none';
                option.style.opacity = '0.3';
                option.style.pointerEvents = 'none';
                option.classList.add('disabled');
            }
        });
        
        // Auto-select if only one type is allowed
        if (this.allowedReservationTypes.length === 1 && !this.selectedReservationType) {
            const autoSelectType = this.allowedReservationTypes[0];
            const autoSelectOption = document.querySelector(`.reservation-option[data-type="${autoSelectType}"]`);
            if (autoSelectOption) {
                autoSelectOption.click();
            }
        }
    }
    
    updateDurationButtonsVisibility() {
        if (this.maxDurationMinutes) {
            document.querySelectorAll('.duration-btn').forEach(btn => {
                const durationValue = parseInt(btn.dataset.duration);
                if (durationValue > this.maxDurationMinutes) {
                    btn.style.opacity = '0.3';
                    btn.style.filter = 'grayscale(100%)';
                    btn.style.pointerEvents = 'none';
                    btn.classList.add('disabled');
                    btn.title = `Durée maximale autorisée: ${this.maxDurationMinutes} minutes`;
                } else {
                    btn.style.opacity = '1';
                    btn.style.filter = 'none';
                    btn.style.pointerEvents = 'auto';
                    btn.classList.remove('disabled');
                    btn.title = '';
                }
            });
        }
    }
    
    updateLimitInfo() {
        const limitTextElement = document.getElementById('limit-text');
        const limitInfoElement = document.getElementById('limit-info');
        
        if (limitTextElement && limitInfoElement) {
            let limitText = '';
            
            if (this.maxDurationMinutes) {
                limitText += `Durée maximale: ${this.maxDurationMinutes} minutes`;
            }
            
            if (this.maxEnergyKwh) {
                if (limitText) limitText += ' • ';
                limitText += `Énergie maximale: ${this.maxEnergyKwh.toFixed(2)} kWh`;
            }
            
            if (limitText) {
                limitTextElement.textContent = limitText;
                limitInfoElement.style.display = 'block';
            } else {
                limitInfoElement.style.display = 'none';
            }
        }
    }
    
    handleReservationTypeSelection(e) {
        const reservationType = e.currentTarget.dataset.type;
        
        if (!this.allowedReservationTypes.includes(reservationType)) {
            this.showAlert('error', 'Type non autorisé', `Le type "${reservationType}" n'est pas autorisé par ce plan tarifaire.`);
            return;
        }
        
        document.querySelectorAll('.reservation-option[data-type]').forEach(opt => {
            opt.classList.remove('selected');
        });
        e.currentTarget.classList.add('selected');
        
        this.selectedReservationType = reservationType;
        this.clearHighlights();
        this.updateUI();
    }
    
    handleDurationSelection(e) {
        const durationValue = parseInt(e.currentTarget.dataset.duration);
        
        if (this.selectedReservationType === 'minute') {
            const validation = this.validateDurationLimit(durationValue);
            if (!validation.valid) {
                this.showAlert('error', 'Durée maximale dépassée', validation.message);
                return;
            }
        }
        
        document.querySelectorAll('.duration-btn').forEach(b => {
            b.classList.remove('selected');
        });
        e.currentTarget.classList.add('selected');
        
        this.selectedReservationValue = durationValue;
        this.clearHighlights();
        this.updateUI();
    }
    
    handleCustomValueInput(e) {
        const value = parseFloat(e.target.value);
        if (value && value > 0) {
            if (this.selectedReservationType === 'minute' && value % 10 !== 0) {
                this.showAlert('warning', 'Intervalle invalide', 'Les durées doivent être par intervalles de 10 minutes.');
                const roundedValue = Math.round(value / 10) * 10;
                e.target.value = roundedValue;
                this.selectedReservationValue = roundedValue;
            } else {
                const validation = this.validateReservationValue(value, this.selectedReservationType);
                if (!validation.valid) {
                    this.showAlert('error', 'Limite dépassée', validation.message);
                    e.target.value = '';
                    this.selectedReservationValue = null;
                } else {
                    this.selectedReservationValue = value;
                }
            }
            this.clearHighlights();
            this.updateUI();
        }
    }
    
    handleTimeSlotSelection(e) {
        if (e.currentTarget.dataset.available === 'true') {
            document.querySelectorAll('.time-slot').forEach(s => {
                s.classList.remove('selected');
            });
            e.currentTarget.classList.add('selected');
            this.selectedTimeSlot = e.currentTarget.dataset.time;
            
            // Clear custom time input
            const customTimeInput = document.getElementById('custom-time');
            if (customTimeInput) {
                customTimeInput.value = '';
                customTimeInput.style.borderColor = '';
                customTimeInput.style.backgroundColor = '';
            }
            
            this.clearHighlights();
            this.updateUI();
        }
    }
    
    handleCustomTimeInput(e) {
        const selectedTime = e.target.value;
        if (selectedTime) {
            const timeParts = selectedTime.split(':');
            const hours = parseInt(timeParts[0]);
            const minutes = parseInt(timeParts[1]);
            
            if (hours < 6 || hours > 22 || (hours === 22 && minutes > 0)) {
                this.showAlert('error', 'Heure invalide', 'L\'heure doit être entre 06:00 et 22:00.');
                e.target.value = '';
                this.selectedTimeSlot = null;
                this.updateUI();
                return;
            }
            
            document.querySelectorAll('.time-slot').forEach(s => {
                s.classList.remove('selected');
            });
            
            this.selectedTimeSlot = selectedTime;
            this.clearHighlights();
            this.updateUI();
            
            e.target.style.borderColor = '#3b82f6';
            e.target.style.backgroundColor = '#eff6ff';
        }
    }
    
    handleCurrentTimeClick(e) {
        const now = new Date();
        const currentTime = now.toTimeString().slice(0, 5);
        
        const timeParts = currentTime.split(':');
        const hours = parseInt(timeParts[0]);
        const minutes = parseInt(timeParts[1]);
        
        if (hours < 6 || hours > 22 || (hours === 22 && minutes > 0)) {
            this.showAlert('warning', 'Heure actuelle non disponible', 
                `L'heure actuelle (${currentTime}) est en dehors des heures de service (06:00 - 22:00).`);
            return;
        }
        
        const customTimeInput = document.getElementById('custom-time');
        if (customTimeInput) {
            customTimeInput.value = currentTime;
            customTimeInput.dispatchEvent(new Event('change'));
        }
        
        document.querySelectorAll('.time-slot').forEach(s => {
            s.classList.remove('selected');
        });
    }
    
    validateDurationLimit(durationMinutes) {
        if (this.maxDurationMinutes && durationMinutes > this.maxDurationMinutes) {
            return {
                valid: false,
                message: `La durée ne peut pas dépasser ${this.maxDurationMinutes} minutes selon ce plan tarifaire.`
            };
        }
        return { valid: true };
    }
    
    validateEnergyLimit(energyKwh) {
        if (this.maxEnergyKwh && energyKwh > this.maxEnergyKwh) {
            return {
                valid: false,
                message: `La quantité d'énergie ne peut pas dépasser ${this.maxEnergyKwh.toFixed(2)} kWh selon ce plan tarifaire.`
            };
        }
        return { valid: true };
    }
    
    validateReservationValue(value, type) {
        if (type === 'minute') {
            return this.validateDurationLimit(value);
        } else if (type === 'kwh') {
            return this.validateEnergyLimit(value);
        }
        return { valid: true };
    }
    
    clearHighlights() {
        document.querySelectorAll('.missing-selection').forEach(el => {
            el.classList.remove('missing-selection');
        });
    }
    
    updateUI() {
        this.updateCostEstimation();
        this.updateReserveButtonState();
        this.updateValueUnit();
    }
    
    updateValueUnit() {
        const valueUnit = document.getElementById('value-unit');
        if (valueUnit) {
            valueUnit.textContent = this.selectedReservationType === 'kwh' ? 'kWh' : 'min';
        }
    }
    
    updateCostEstimation() {
        if (!this.selectedReservationType || !this.selectedReservationValue) {
            this.resetCostDisplay();
            return;
        }

        let baseAmount = 0;
        let calculationLabel = 'Tarif de base:';
        
        if (this.selectedReservationType === 'kwh') {
            baseAmount = this.selectedReservationValue * this.pricePerKwh;
            calculationLabel = `Tarif énergie (${this.selectedReservationValue} kWh × ${this.pricePerKwh.toFixed(2)} ${this.currency}/kWh):`;
        } else if (this.selectedReservationType === 'minute') {
            baseAmount = this.selectedReservationValue * this.pricePerMinute;
            calculationLabel = `Tarif temps (${this.selectedReservationValue} min × ${this.pricePerMinute.toFixed(2)} ${this.currency}/min):`;
        }

        const subtotalHT = baseAmount + this.activationFee;
        const vatAmount = subtotalHT * (this.vatRate / 100);
        const totalTTC = subtotalHT + vatAmount;

        this.updateCostDisplay(calculationLabel, baseAmount, subtotalHT, vatAmount, totalTTC);
    }
    
    resetCostDisplay() {
        const elements = {
            'calculation-label': 'Tarif de base:',
            'calculation-amount': `0.00 ${this.currency}`,
            'subtotal-ht': `0.00 ${this.currency}`,
            'vat-amount': `0.00 ${this.currency}`,
            'total-ttc': `0.00 ${this.currency}`
        };
        
        Object.entries(elements).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) element.textContent = value;
        });
    }
    
    updateCostDisplay(calculationLabel, baseAmount, subtotalHT, vatAmount, totalTTC) {
        const elements = {
            'calculation-label': calculationLabel,
            'calculation-amount': `${baseAmount.toFixed(2)} ${this.currency}`,
            'subtotal-ht': `${subtotalHT.toFixed(2)} ${this.currency}`,
            'vat-amount': `${vatAmount.toFixed(2)} ${this.currency}`,
            'total-ttc': `${totalTTC.toFixed(2)} ${this.currency}`
        };
        
        Object.entries(elements).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) element.textContent = value;
        });
    }
    
    updateReserveButtonState() {
        const isReservationTypeValid = this.selectedReservationType && this.allowedReservationTypes.includes(this.selectedReservationType);
        
        if (isReservationTypeValid && this.selectedReservationValue && this.selectedTimeSlot) {
            this.reserveButton.disabled = false;
            this.reserveButton.classList.remove('opacity-50', 'cursor-not-allowed');
            this.reserveButton.classList.add('hover:bg-blue-700', 'hover:shadow-xl', 'hover:-translate-y-1');
        } else {
            this.reserveButton.disabled = true;
            this.reserveButton.classList.add('opacity-50', 'cursor-not-allowed');
            this.reserveButton.classList.remove('hover:bg-blue-700', 'hover:shadow-xl', 'hover:-translate-y-1');
        }
    }
    
    handleReservationSubmit(e) {
        if (this.isSubmitting) {
            e.preventDefault();
            return;
        }
        
        if (this.reserveButton.disabled) {
            e.preventDefault();
            this.showMissingSelectionsAlert();
            return;
        }
        
        // Final validation
        const finalValidation = this.validateReservationValue(this.selectedReservationValue, this.selectedReservationType);
        if (!finalValidation.valid) {
            this.showAlert('error', 'Limite dépassée', finalValidation.message);
            return;
        }
        
        this.submitReservation();
    }
    
    showMissingSelectionsAlert() {
        const missingSelections = [];
        if (!this.selectedReservationType) missingSelections.push('type de réservation');
        if (!this.selectedReservationValue) missingSelections.push('valeur/durée');
        if (!this.selectedTimeSlot) missingSelections.push('créneau horaire');
        
        if (missingSelections.length > 0) {
            const message = missingSelections.length === 1 
                ? `Veuillez sélectionner un ${missingSelections[0]}.`
                : `Veuillez sélectionner: ${missingSelections.join(', ')}.`;
            
            this.highlightMissingSelections();
            this.showAlert('warning', 'Sélection requise', message);
        }
    }
    
    highlightMissingSelections() {
        const missingSelections = [];
        if (!this.selectedReservationType) missingSelections.push('reservation-option');
        if (!this.selectedReservationValue) missingSelections.push('duration-btn');
        if (!this.selectedTimeSlot) missingSelections.push('time-slot');
        
        this.clearHighlights();
        
        missingSelections.forEach(selector => {
            document.querySelectorAll(`.${selector}`).forEach(el => {
                el.classList.add('missing-selection');
            });
        });
        
        setTimeout(() => this.clearHighlights(), 3000);
    }
    
    async submitReservation() {
        this.isSubmitting = true;
        this.reserveButton.disabled = true;
        this.reserveButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Création de la réservation...';
        
        try {
            const reservationData = {
                charging_point_id: window.pricingData?.chargingPointId,
                pricing_plan_id: window.pricingData?.pricingPlanId,
                reservation_type: this.selectedReservationType,
                reservation_value: this.selectedReservationValue,
                start_time: this.selectedTimeSlot,
                payment_type: 'offline',
                guest_email: null,
                guest_phone: null
            };
            
            const csrfToken = this.getCSRFToken();
            if (!csrfToken) {
                throw new Error('Token CSRF manquant');
            }
            
            const response = await fetch(window.pricingData?.reservationUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(reservationData)
            });
            
            if (response.status === 419) {
                throw new Error('Token CSRF expiré. Veuillez recharger la page.');
            }
            
            const data = await response.json();
            
            if (data.success === true || data.reservation) {
                this.showAlert('success', 'Réservation Confirmée!', 
                    data.message || 'Votre réservation a été créée avec succès.', 
                    'Vous allez être redirigé vers la page de confirmation...',
                    () => {
                        const redirectUrl = data.redirect_url || '/reservations/' + (data.reservation?.id || data.reservation_id) + '/thank-you';
                        window.location.href = redirectUrl;
                    }
                );
                
                setTimeout(() => {
                    if (document.getElementById('customAlertOverlay').style.display !== 'none') {
                        const redirectUrl = data.redirect_url || '/reservations/' + (data.reservation?.id || data.reservation_id) + '/thank-you';
                        window.location.href = redirectUrl;
                    }
                }, 3000);
            } else {
                this.handleReservationError(data);
            }
        } catch (error) {
            console.error('Reservation error:', error);
            this.showAlert('error', 'Erreur de réservation', 
                'Une erreur est survenue lors de la création de la réservation.', 
                'Veuillez vérifier votre connexion internet et réessayer.');
        } finally {
            this.isSubmitting = false;
            this.reserveButton.disabled = false;
            this.reserveButton.innerHTML = '<i class="fas fa-check mr-2"></i>Réserver la borne';
        }
    }
    
    handleReservationError(data) {
        if (data.error === 'limit_exceeded') {
            this.showAlert('error', 'Limite dépassée', 
                data.message || 'La durée de réservation ne peut pas dépasser la limite autorisée.',
                'Veuillez réduire la durée ou la quantité d\'énergie de votre réservation.');
        } else {
            this.showAlert('error', 'Erreur de réservation', 
                data.message || 'Erreur lors de la création de la réservation.');
        }
    }
    
    getCSRFToken() {
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        if (metaTag) {
            return metaTag.getAttribute('content');
        }
        return window.pricingData?.csrfToken || '';
    }
    
    showAlert(type, title, message, bodyContent = '', primaryAction = null) {
        const overlay = document.getElementById('customAlertOverlay');
        const icon = document.getElementById('customAlertIconClass');
        const titleEl = document.getElementById('customAlertTitle');
        const messageEl = document.getElementById('customAlertMessage');
        const bodyEl = document.getElementById('customAlertBodyContent');
        const primaryBtn = document.getElementById('customAlertPrimaryBtn');
        
        // Configure icon
        const iconClasses = {
            'success': 'fas fa-check text-green-500 text-3xl',
            'error': 'fas fa-exclamation-triangle text-red-500 text-3xl',
            'warning': 'fas fa-exclamation-circle text-yellow-500 text-3xl',
            'info': 'fas fa-info-circle text-blue-500 text-3xl'
        };
        icon.className = iconClasses[type] || iconClasses['info'];
        
        // Configure content
        titleEl.textContent = title;
        messageEl.textContent = message;
        bodyEl.innerHTML = bodyContent;
        
        // Configure primary button
        if (primaryAction) {
            primaryBtn.textContent = 'Continuer';
            primaryBtn.onclick = primaryAction;
        } else {
            primaryBtn.textContent = 'OK';
            primaryBtn.onclick = () => this.hideAlert();
        }
        
        overlay.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    
    hideAlert() {
        const overlay = document.getElementById('customAlertOverlay');
        overlay.classList.add('hidden');
        document.body.style.overflow = '';
    }
}

// Initialize the reservation manager when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    new ReservationManager();
});
