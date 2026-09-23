/**
 * OCPP Operations Manager
 * 
 * Gère les opérations OCPP côté frontend avec UX optimisée:
 * - États de chargement pendant les requêtes
 * - Feedback immédiat pour l'utilisateur
 * - Gestion des erreurs avec messages clairs
 */
class OcppOperationsManager {
    constructor(options = {}) {
        this.baseUrl = options.baseUrl || '';
        this.csrfToken = options.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content;
        this.onSuccess = options.onSuccess || null;
        this.onError = options.onError || null;
        this.onStatusChange = options.onStatusChange || null;
    }

    /**
     * Configure le token CSRF
     */
    setCsrfToken(token) {
        this.csrfToken = token;
    }

    /**
     * Effectue une requête API
     */
    async request(url, method = 'POST', data = {}) {
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        };

        if (this.csrfToken) {
            headers['X-CSRF-TOKEN'] = this.csrfToken;
        }

        try {
            const response = await fetch(url, {
                method,
                headers,
                body: method !== 'GET' ? JSON.stringify(data) : undefined,
                credentials: 'same-origin',
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.error || result.message || 'Erreur serveur');
            }

            return result;
        } catch (error) {
            throw error;
        }
    }

    // =========================================================================
    // AVAILABILITY OPERATIONS
    // =========================================================================

    /**
     * Change la disponibilité d'un connecteur
     */
    async changeConnectorAvailability(chargingPointId, connectorId, type) {
        const url = `${this.baseUrl}/charging-points/${chargingPointId}/ocpp/connectors/${connectorId}/availability`;
        return this.request(url, 'POST', { type });
    }

    /**
     * Active un connecteur
     */
    async enableConnector(chargingPointId, connectorId) {
        const url = `${this.baseUrl}/charging-points/${chargingPointId}/ocpp/connectors/${connectorId}/enable`;
        return this.request(url, 'POST');
    }

    /**
     * Désactive un connecteur
     */
    async disableConnector(chargingPointId, connectorId) {
        const url = `${this.baseUrl}/charging-points/${chargingPointId}/ocpp/connectors/${connectorId}/disable`;
        return this.request(url, 'POST');
    }

    /**
     * Change la disponibilité de toute la borne
     */
    async changeChargePointAvailability(chargingPointId, type) {
        const url = `${this.baseUrl}/charging-points/${chargingPointId}/ocpp/availability`;
        return this.request(url, 'POST', { type });
    }

    /**
     * Active toute la borne
     */
    async enableChargePoint(chargingPointId) {
        const url = `${this.baseUrl}/charging-points/${chargingPointId}/ocpp/enable`;
        return this.request(url, 'POST');
    }

    /**
     * Désactive toute la borne
     */
    async disableChargePoint(chargingPointId) {
        const url = `${this.baseUrl}/charging-points/${chargingPointId}/ocpp/disable`;
        return this.request(url, 'POST');
    }

    // =========================================================================
    // OTHER OPERATIONS
    // =========================================================================

    /**
     * Réinitialise la borne
     */
    async resetChargePoint(chargingPointId, hard = false) {
        const url = `${this.baseUrl}/charging-points/${chargingPointId}/ocpp/reset`;
        return this.request(url, 'POST', { hard });
    }

    /**
     * Déverrouille un connecteur
     */
    async unlockConnector(chargingPointId, connectorId) {
        const url = `${this.baseUrl}/charging-points/${chargingPointId}/ocpp/connectors/${connectorId}/unlock`;
        return this.request(url, 'POST');
    }

    /**
     * Vide le cache de la borne
     */
    async clearCache(chargingPointId) {
        const url = `${this.baseUrl}/charging-points/${chargingPointId}/ocpp/clear-cache`;
        return this.request(url, 'POST');
    }
}

/**
 * Composant UI pour le bouton de disponibilité
 * 
 * Usage:
 * <button 
 *   class="availability-toggle-btn"
 *   data-charging-point-id="123"
 *   data-connector-id="1"
 *   data-current-status="Operative"
 * >
 *   Désactiver
 * </button>
 */
class AvailabilityToggleButton {
    constructor(element, manager) {
        this.element = element;
        this.manager = manager;
        this.chargingPointId = element.dataset.chargingPointId;
        this.connectorId = element.dataset.connectorId;
        this.currentStatus = element.dataset.currentStatus || 'Operative';
        this.isLoading = false;

        this.init();
    }

    init() {
        this.element.addEventListener('click', (e) => this.handleClick(e));
        this.updateButtonState();
    }

    async handleClick(e) {
        e.preventDefault();

        if (this.isLoading) {
            return;
        }

        this.setLoading(true);

        try {
            const newType = this.currentStatus === 'Operative' ? 'Inoperative' : 'Operative';
            
            let result;
            if (this.connectorId && this.connectorId !== '0') {
                result = await this.manager.changeConnectorAvailability(
                    this.chargingPointId,
                    this.connectorId,
                    newType
                );
            } else {
                result = await this.manager.changeChargePointAvailability(
                    this.chargingPointId,
                    newType
                );
            }

            if (result.success) {
                this.currentStatus = newType;
                this.element.dataset.currentStatus = newType;
                this.showSuccess(result.message || 'Disponibilité modifiée avec succès');
                
                // Émettre un événement personnalisé
                this.element.dispatchEvent(new CustomEvent('availability-changed', {
                    bubbles: true,
                    detail: {
                        chargingPointId: this.chargingPointId,
                        connectorId: this.connectorId,
                        newStatus: newType,
                        response: result,
                    }
                }));
            } else {
                this.showError(result.message || 'Erreur lors du changement de disponibilité');
            }
        } catch (error) {
            this.showError(error.message || 'Erreur de connexion');
        } finally {
            this.setLoading(false);
            this.updateButtonState();
        }
    }

    setLoading(loading) {
        this.isLoading = loading;
        this.element.disabled = loading;
        
        if (loading) {
            this.element.classList.add('loading');
            this.originalContent = this.element.innerHTML;
            this.element.innerHTML = `
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                <span class="ms-1">Chargement...</span>
            `;
        } else {
            this.element.classList.remove('loading');
            if (this.originalContent) {
                this.element.innerHTML = this.originalContent;
            }
        }
    }

    updateButtonState() {
        const isOperative = this.currentStatus === 'Operative';
        
        // Update button text
        const label = isOperative ? 'Désactiver' : 'Activer';
        const icon = isOperative ? 'x-circle' : 'check-circle';
        
        this.element.innerHTML = `
            <svg class="w-4 h-4 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                ${isOperative 
                    ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>'
                    : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>'
                }
            </svg>
            ${label}
        `;

        // Update button classes
        this.element.classList.remove('btn-success', 'btn-danger', 'btn-outline-success', 'btn-outline-danger');
        if (isOperative) {
            this.element.classList.add('btn-outline-danger');
        } else {
            this.element.classList.add('btn-outline-success');
        }
    }

    showSuccess(message) {
        this.showToast(message, 'success');
    }

    showError(message) {
        this.showToast(message, 'error');
    }

    showToast(message, type) {
        // Utiliser le système de toast existant s'il existe
        if (window.showToast) {
            window.showToast(message, type);
            return;
        }

        // Fallback: créer un toast simple
        const toast = document.createElement('div');
        toast.className = `toast-notification toast-${type}`;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 10000;
            animation: slideIn 0.3s ease;
            background-color: ${type === 'success' ? '#10b981' : '#ef4444'};
        `;
        toast.textContent = message;

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
}

/**
 * Composant UI pour le panneau de contrôle OCPP
 */
class OcppControlPanel {
    constructor(container, options = {}) {
        this.container = container;
        this.chargingPointId = options.chargingPointId || container.dataset.chargingPointId;
        this.manager = options.manager || new OcppOperationsManager();
        
        this.init();
    }

    init() {
        // Initialiser tous les boutons de disponibilité
        this.container.querySelectorAll('.availability-toggle-btn').forEach(btn => {
            new AvailabilityToggleButton(btn, this.manager);
        });

        // Écouter les événements de changement
        this.container.addEventListener('availability-changed', (e) => {
            this.handleAvailabilityChanged(e.detail);
        });

        // Initialiser les boutons d'action
        this.initActionButtons();
    }

    initActionButtons() {
        // Reset button
        this.container.querySelectorAll('[data-action="reset"]').forEach(btn => {
            btn.addEventListener('click', () => this.handleReset(btn));
        });

        // Clear cache button
        this.container.querySelectorAll('[data-action="clear-cache"]').forEach(btn => {
            btn.addEventListener('click', () => this.handleClearCache(btn));
        });

        // Unlock button
        this.container.querySelectorAll('[data-action="unlock"]').forEach(btn => {
            btn.addEventListener('click', () => this.handleUnlock(btn));
        });
    }

    async handleReset(btn) {
        if (!confirm('Voulez-vous vraiment réinitialiser cette borne ?')) {
            return;
        }

        this.setButtonLoading(btn, true);

        try {
            const hard = btn.dataset.hard === 'true';
            const result = await this.manager.resetChargePoint(this.chargingPointId, hard);
            
            if (result.success) {
                this.showSuccess('Borne réinitialisée avec succès');
            } else {
                this.showError(result.message || 'Erreur lors de la réinitialisation');
            }
        } catch (error) {
            this.showError(error.message);
        } finally {
            this.setButtonLoading(btn, false);
        }
    }

    async handleClearCache(btn) {
        this.setButtonLoading(btn, true);

        try {
            const result = await this.manager.clearCache(this.chargingPointId);
            
            if (result.success) {
                this.showSuccess('Cache vidé avec succès');
            } else {
                this.showError(result.message || 'Erreur lors du vidage du cache');
            }
        } catch (error) {
            this.showError(error.message);
        } finally {
            this.setButtonLoading(btn, false);
        }
    }

    async handleUnlock(btn) {
        const connectorId = btn.dataset.connectorId;
        
        this.setButtonLoading(btn, true);

        try {
            const result = await this.manager.unlockConnector(this.chargingPointId, connectorId);
            
            if (result.success) {
                this.showSuccess('Connecteur déverrouillé avec succès');
            } else {
                this.showError(result.message || 'Erreur lors du déverrouillage');
            }
        } catch (error) {
            this.showError(error.message);
        } finally {
            this.setButtonLoading(btn, false);
        }
    }

    handleAvailabilityChanged(detail) {
        // Mettre à jour les indicateurs de statut dans l'UI
        const statusBadges = this.container.querySelectorAll(
            `[data-connector-status="${detail.connectorId}"]`
        );

        statusBadges.forEach(badge => {
            const isOperative = detail.newStatus === 'Operative';
            badge.textContent = isOperative ? 'Opérationnel' : 'Hors service';
            badge.className = `badge ${isOperative ? 'bg-success' : 'bg-danger'}`;
        });

        // Émettre un événement global pour les autres composants
        document.dispatchEvent(new CustomEvent('ocpp-availability-changed', {
            detail: detail
        }));
    }

    setButtonLoading(btn, loading) {
        btn.disabled = loading;
        if (loading) {
            btn.dataset.originalContent = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        } else if (btn.dataset.originalContent) {
            btn.innerHTML = btn.dataset.originalContent;
        }
    }

    showSuccess(message) {
        if (window.showToast) {
            window.showToast(message, 'success');
        } else {
            alert(message);
        }
    }

    showError(message) {
        if (window.showToast) {
            window.showToast(message, 'error');
        } else {
            alert('Erreur: ' + message);
        }
    }
}

// Auto-initialisation lorsque le DOM est prêt
document.addEventListener('DOMContentLoaded', () => {
    // Initialiser le manager global
    window.ocppManager = new OcppOperationsManager();

    // Initialiser tous les panneaux de contrôle OCPP
    document.querySelectorAll('[data-ocpp-control-panel]').forEach(panel => {
        new OcppControlPanel(panel, { manager: window.ocppManager });
    });

    // Initialiser les boutons de disponibilité autonomes
    document.querySelectorAll('.availability-toggle-btn:not([data-initialized])').forEach(btn => {
        btn.dataset.initialized = 'true';
        new AvailabilityToggleButton(btn, window.ocppManager);
    });
});

// Ajouter les styles CSS pour les animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }

    .availability-toggle-btn.loading {
        cursor: wait;
        opacity: 0.7;
    }

    .availability-toggle-btn:disabled {
        pointer-events: none;
    }
`;
document.head.appendChild(style);

// Export pour utilisation en module
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        OcppOperationsManager,
        AvailabilityToggleButton,
        OcppControlPanel
    };
}
