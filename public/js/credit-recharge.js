/**
 * Script de gestion de la page de recharge de crédit
 */

(function() {
    'use strict';

    // Éléments DOM
    const elements = {
        rechargeTypeRadios: document.querySelectorAll('.recharge-type-radio'),
        packOptions: document.querySelectorAll('.pack-option'),
        paymentMethodRadios: document.querySelectorAll('input[name="payment_method"]'),
        rechargeForm: document.getElementById('rechargeForm'),
        amountInput: document.getElementById('amount'),
        descriptionTextarea: document.getElementById('description'),
        submitButton: document.getElementById('submitButton'),
        submitSpinner: document.getElementById('submitSpinner'),
        creditPackId: document.getElementById('credit_pack_id'),
        customAmountContainer: document.getElementById('customAmountContainer'),
        amountErrorContainer: document.getElementById('amountErrorContainer'),
        amountErrorText: document.getElementById('amountErrorText'),
        amountValidIcon: document.getElementById('amountValidIcon'),
        amountInvalidIcon: document.getElementById('amountInvalidIcon'),
        amountPreview: document.getElementById('amountPreview'),
        amountPreviewValue: document.getElementById('amountPreviewValue'),
        descriptionCharCount: document.getElementById('descriptionCharCount'),
        packsContainer: document.getElementById('packsContainer')
    };

    // Constantes
    const MIN_AMOUNT = 50;
    const MAX_AMOUNT = 5000;
    const VALIDATION_DELAY = 300;

    /**
     * Formate un montant en devise EUR
     */
    function formatCurrency(value) {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'EUR',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(value);
    }

    /**
     * Affiche une notification élégante
     */
    function showNotification(message, type = 'info') {
        // Supprimer les notifications existantes
        const existingNotifications = document.querySelectorAll('.custom-notification');
        existingNotifications.forEach(n => n.remove());

        const notification = document.createElement('div');
        const bgColor = type === 'error' ? 'bg-red-500' : 
                       type === 'success' ? 'bg-green-500' : 'bg-blue-500';
        
        notification.className = `custom-notification fixed top-4 right-4 z-50 p-4 rounded-lg shadow-2xl transform transition-all duration-300 ${bgColor} text-white`;

        const iconPath = type === 'error' ? 
            '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>' :
            '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>';

        notification.innerHTML = `
            <div class="flex items-center space-x-3">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    ${iconPath}
                </svg>
                <span class="font-medium">${message}</span>
            </div>
        `;

        document.body.appendChild(notification);

        // Animation d'entrée
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
            notification.style.opacity = '1';
        }, 10);

        // Supprimer après 4 secondes
        setTimeout(() => {
            notification.style.transform = 'translateX(400px)';
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }, 4000);
    }

    /**
     * Réinitialise la sélection des packs
     */
    function resetPackSelection() {
        // Support pour les anciennes cartes
        elements.packOptions.forEach(option => {
            option.classList.remove('selected');
            option.style.transform = '';
            option.style.borderWidth = '';
        });
        
        // Support pour les nouvelles cartes compactes
        const packCards = document.querySelectorAll('.pack-card-compact');
        packCards.forEach(card => {
            card.classList.remove('selected');
            const radio = card.querySelector('.pack-radio');
            if (radio) radio.checked = false;
        });
        
        // Support pour les nouvelles cartes widgets
        const packWidgets = document.querySelectorAll('.credit-pack-widget');
        packWidgets.forEach(widget => {
            const radio = widget.querySelector('.pack-radio, .recharge-type-radio');
            if (radio) radio.checked = false;
            const selectedOverlay = widget.querySelector('.credit-pack-widget-selected');
            if (selectedOverlay) selectedOverlay.style.opacity = '0';
        });
    }

    /**
     * Réinitialise le champ de montant personnalisé
     */
    function resetCustomAmount() {
        if (elements.amountInput) {
            elements.amountInput.removeAttribute('required');
            elements.amountInput.value = '';
            elements.amountInput.classList.remove('amount-input-valid', 'amount-input-invalid', 'border-red-500', 'border-green-500');
            elements.amountInput.classList.add('border-gray-300');
        }
        
        if (elements.amountErrorContainer) elements.amountErrorContainer.classList.add('hidden');
        if (elements.amountPreview) elements.amountPreview.classList.add('hidden');
        if (elements.amountValidIcon) elements.amountValidIcon.classList.add('hidden');
        if (elements.amountInvalidIcon) elements.amountInvalidIcon.classList.add('hidden');
    }

    /**
     * Gère la sélection d'un pack
     */
    function handlePackSelection(radio) {
        resetPackSelection();
        
        // Support pour les anciennes cartes
        const label = radio.closest('.pack-option');
        if (label) {
            label.classList.add('selected');
            label.style.transform = 'translateY(-4px) scale(1.01)';
        }
        
        // Support pour les nouvelles cartes compactes
        const compactCard = radio.closest('.pack-card-compact');
        if (compactCard) {
            compactCard.classList.add('selected');
            radio.checked = true;
        }
        
        // Support pour les nouvelles cartes widgets
        const widgetCard = radio.closest('.credit-pack-widget');
        if (widgetCard) {
            radio.checked = true;
            const selectedOverlay = widgetCard.querySelector('.credit-pack-widget-selected');
            if (selectedOverlay) selectedOverlay.style.opacity = '1';
        }
        
        if (elements.creditPackId && radio.dataset.packId) {
            elements.creditPackId.value = radio.dataset.packId;
        }
        
        if (elements.customAmountContainer) {
            elements.customAmountContainer.classList.add('hidden');
            elements.customAmountContainer.classList.remove('custom-amount-slide');
        }
        
        resetCustomAmount();
    }

    /**
     * Gère la sélection d'un montant personnalisé
     */
    function handleCustomAmountSelection() {
        resetPackSelection();
        
        if (elements.creditPackId) {
            elements.creditPackId.value = '';
        }
        
        if (elements.customAmountContainer) {
            elements.customAmountContainer.classList.remove('hidden');
            elements.customAmountContainer.classList.add('custom-amount-slide');
        }
        
        if (elements.amountInput) {
            elements.amountInput.setAttribute('required', 'required');
            setTimeout(() => {
                elements.amountInput.focus();
            }, 100);
        }
    }

    /**
     * Valide le montant saisi
     */
    function validateAmount(value) {
        if (isNaN(value) || value <= 0) {
            return { valid: false, message: 'Veuillez saisir un montant valide' };
        }
        
        if (value < MIN_AMOUNT) {
            return { valid: false, message: `Le montant minimum est de ${formatCurrency(MIN_AMOUNT)}` };
        }
        
        if (value > MAX_AMOUNT) {
            return { valid: false, message: `Le montant maximum est de ${formatCurrency(MAX_AMOUNT)}` };
        }
        
        return { valid: true };
    }

    /**
     * Gère la validation en temps réel du montant
     */
    function setupAmountValidation() {
        if (!elements.amountInput) return;

        let validationTimeout;

        elements.amountInput.addEventListener('input', function() {
            clearTimeout(validationTimeout);
            const inputValue = this.value.trim();

            // Masquer les icônes et messages par défaut
            if (elements.amountValidIcon) elements.amountValidIcon.classList.add('hidden');
            if (elements.amountInvalidIcon) elements.amountInvalidIcon.classList.add('hidden');
            if (elements.amountErrorContainer) elements.amountErrorContainer.classList.add('hidden');
            if (elements.amountPreview) elements.amountPreview.classList.add('hidden');

            // Réinitialiser les classes
            this.classList.remove('amount-input-valid', 'amount-input-invalid', 'border-red-500', 'border-green-500');
            this.classList.add('border-gray-300');

            if (!inputValue) {
                return;
            }

            const value = parseFloat(inputValue);

            // Délai pour éviter la validation à chaque frappe
            validationTimeout = setTimeout(() => {
                const validation = validateAmount(value);

                if (!validation.valid) {
                    this.classList.remove('amount-input-valid', 'border-green-500');
                    this.classList.add('amount-input-invalid', 'border-red-500');
                    if (elements.amountInvalidIcon) elements.amountInvalidIcon.classList.remove('hidden');
                    if (elements.amountErrorContainer) {
                        elements.amountErrorContainer.classList.remove('hidden');
                        if (elements.amountErrorText) elements.amountErrorText.textContent = validation.message;
                    }
                } else {
                    this.classList.remove('amount-input-invalid', 'border-red-500');
                    this.classList.add('amount-input-valid', 'border-green-500');
                    if (elements.amountValidIcon) elements.amountValidIcon.classList.remove('hidden');
                    if (elements.amountPreview) {
                        elements.amountPreview.classList.remove('hidden');
                        if (elements.amountPreviewValue) elements.amountPreviewValue.textContent = formatCurrency(value);
                    }
                }
            }, VALIDATION_DELAY);
        });

        // Validation au blur
        elements.amountInput.addEventListener('blur', function() {
            clearTimeout(validationTimeout);
            const inputValue = this.value.trim();
            if (inputValue) {
                const value = parseFloat(inputValue);
                const validation = validateAmount(value);
                
                if (!validation.valid) {
                    this.classList.add('amount-input-invalid', 'border-red-500');
                    this.classList.remove('amount-input-valid', 'border-green-500');
                }
            }
        });
    }

    /**
     * Gère la sélection des méthodes de paiement
     */
    function setupPaymentMethodSelection() {
        elements.paymentMethodRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                // Désélectionner toutes les cartes
                document.querySelectorAll('.payment-method-card').forEach(card => {
                    const checkIcon = card.querySelector('.payment-check-icon');
                    if (checkIcon) {
                        checkIcon.style.animation = 'none';
                        setTimeout(() => {
                            checkIcon.classList.add('hidden');
                            checkIcon.style.animation = '';
                        }, 200);
                    }
                    card.classList.remove('selected', 'border-green-500');
                    card.style.borderWidth = '2px';
                    card.style.transform = '';
                });

                // Sélectionner la carte choisie
                if (this.checked) {
                    const label = this.closest('.payment-method-card');
                    const checkIcon = label.querySelector('.payment-check-icon');

                    label.style.transform = 'scale(0.98)';
                    setTimeout(() => {
                        label.style.transform = '';
                        label.classList.add('selected', 'border-green-500');
                        label.style.borderWidth = '3px';

                        if (checkIcon) {
                            checkIcon.classList.remove('hidden');
                            checkIcon.style.animation = 'checkPop 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55)';
                        }
                    }, 100);

                    setTimeout(() => {
                        label.style.boxShadow = '0 20px 30px -5px rgba(16, 185, 129, 0.3)';
                    }, 300);
                }
            });

            // Effet hover
            const card = radio.closest('.payment-method-card');
            if (card) {
                card.addEventListener('mouseenter', function() {
                    if (!this.classList.contains('selected')) {
                        this.style.transform = 'translateY(-3px)';
                    }
                });

                card.addEventListener('mouseleave', function() {
                    if (!this.classList.contains('selected')) {
                        this.style.transform = '';
                    }
                });
            }
        });

        // Initialiser l'état visuel de la méthode sélectionnée par défaut
        const defaultPaymentMethod = document.querySelector('input[name="payment_method"]:checked');
        if (defaultPaymentMethod) {
            const label = defaultPaymentMethod.closest('.payment-method-card');
            const checkIcon = label.querySelector('.payment-check-icon');
            if (checkIcon) {
                checkIcon.classList.remove('hidden');
                checkIcon.style.animation = 'checkPop 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55)';
            }
            label.classList.add('selected', 'border-green-500');
            label.style.borderWidth = '3px';
        }

        // Ajouter des tooltips
        document.querySelectorAll('.payment-method-card').forEach(card => {
            const method = card.dataset.method;
            const tooltips = {
                'offline': 'Votre demande sera traitée manuellement par un administrateur. Le crédit sera ajouté après confirmation (24-48h).',
                'cmi': 'Paiement en ligne instantané via CMI International. Vous serez redirigé vers leur plateforme sécurisée. Accepte les cartes Visa, Mastercard et autres cartes internationales. Le crédit sera ajouté immédiatement après confirmation.',
                'stripe': 'Paiement en ligne instantané via Stripe. Vous serez redirigé vers Stripe Checkout pour compléter votre transaction. Accepte toutes les cartes internationales (Visa, Mastercard, Amex, etc.). Le crédit sera ajouté immédiatement après confirmation.',
                'on_site_card': 'Paiement sur place par carte bancaire nationale ou internationale. Présentez-vous sur place avec votre carte pour finaliser le paiement. Le crédit sera ajouté immédiatement après validation.'
            };

            if (tooltips[method]) {
                card.setAttribute('title', tooltips[method]);
                card.style.cursor = 'pointer';
            }
        });
    }

    /**
     * Gère le compteur de caractères pour la description
     */
    function setupDescriptionCounter() {
        if (!elements.descriptionTextarea || !elements.descriptionCharCount) return;

        elements.descriptionCharCount.textContent = elements.descriptionTextarea.value.length;

        elements.descriptionTextarea.addEventListener('input', function() {
            const length = this.value.length;
            elements.descriptionCharCount.textContent = length;

            // Changer la couleur si proche de la limite
            if (length > 450) {
                elements.descriptionCharCount.classList.add('text-red-500', 'font-semibold');
                elements.descriptionCharCount.classList.remove('text-gray-400');
            } else if (length > 400) {
                elements.descriptionCharCount.classList.add('text-yellow-500', 'font-semibold');
                elements.descriptionCharCount.classList.remove('text-red-500', 'text-gray-400');
            } else {
                elements.descriptionCharCount.classList.remove('text-red-500', 'text-yellow-500', 'font-semibold');
                elements.descriptionCharCount.classList.add('text-gray-400');
            }
        });
    }

    /**
     * Gère la validation du formulaire
     */
    function setupFormValidation() {
        if (!elements.rechargeForm) return;

        elements.rechargeForm.addEventListener('submit', function(e) {
            const rechargeType = document.querySelector('input[name="recharge_type"]:checked');
            
            if (!rechargeType) {
                e.preventDefault();
                if (elements.packsContainer) {
                    elements.packsContainer.style.animation = 'shake 0.5s ease';
                    setTimeout(() => {
                        elements.packsContainer.style.animation = '';
                    }, 500);
                }
                showNotification('Veuillez sélectionner un pack ou une recharge personnalisée', 'error');
                return false;
            }

            if (rechargeType.value === 'custom') {
                if (!elements.amountInput) {
                    e.preventDefault();
                    showNotification('Erreur: champ de montant introuvable', 'error');
                    return false;
                }

                const amount = parseFloat(elements.amountInput.value);
                const validation = validateAmount(amount);

                if (!validation.valid) {
                    e.preventDefault();
                    elements.amountInput.focus();
                    elements.amountInput.classList.add('amount-input-invalid', 'border-red-500');
                    showNotification(validation.message, 'error');
                    return false;
                }
            }

            // Gérer la redirection pour les paiements en ligne
            const selectedPaymentMethod = document.querySelector('input[name="payment_method"]:checked');
            if (selectedPaymentMethod && ['cmi', 'stripe'].includes(selectedPaymentMethod.value)) {
                if (elements.submitButton) {
                    const span = elements.submitButton.querySelector('span');
                    if (span) span.textContent = 'Redirection vers le paiement...';
                    elements.submitButton.disabled = true;
                    
                    setTimeout(() => {
                        showNotification('Vous allez être redirigé vers la page de paiement sécurisée...', 'info');
                    }, 100);
                }
            }

            // Afficher le spinner et désactiver le bouton
            if (elements.submitButton && elements.submitSpinner) {
                elements.submitButton.disabled = true;
                elements.submitButton.classList.add('opacity-75', 'cursor-not-allowed');
                elements.submitSpinner.classList.remove('hidden');
                const span = elements.submitButton.querySelector('span');
                if (span) span.textContent = 'Traitement en cours...';
            }
        });
    }

    /**
     * Initialise tous les gestionnaires d'événements
     */
    function init() {
        // Gestion de la sélection des packs (anciennes cartes)
        elements.rechargeTypeRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'pack') {
                    handlePackSelection(this);
                } else if (this.value === 'custom') {
                    handleCustomAmountSelection();
                }
            });
        });
        
        // Gestion de la sélection des packs (nouvelles cartes compactes)
        const packRadios = document.querySelectorAll('.pack-radio');
        packRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.checked) {
                    handlePackSelection(this);
                }
            });
            
            // Gestion du clic sur la carte
            const card = radio.closest('.pack-card-compact');
            if (card) {
                card.addEventListener('click', function(e) {
                    if (e.target !== radio) {
                        radio.checked = true;
                        radio.dispatchEvent(new Event('change'));
                    }
                });
            }
            
            // Support pour les nouvelles cartes widgets
            const widgetCard = radio.closest('.credit-pack-widget');
            if (widgetCard) {
                widgetCard.addEventListener('click', function(e) {
                    if (e.target !== radio && e.target.tagName !== 'INPUT') {
                        radio.checked = true;
                        radio.dispatchEvent(new Event('change'));
                    }
                });
            }
        });

        // Configuration des autres fonctionnalités
        setupAmountValidation();
        setupPaymentMethodSelection();
        setupDescriptionCounter();
        setupFormValidation();
    }

    // Initialiser quand le DOM est prêt
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Exposer showNotification globalement si nécessaire
    window.showNotification = showNotification;
})();

