/**
 * SteVe OCPP Connection Manager
 * Handles modal interactions, connection status, and WebSocket URL generation
 */

class SteVeConnectionManager {
    constructor() {
        this.modal = null;
        this.chargingPointId = null;
        this.chargeBoxId = '';
        this.serverUrl = 'ws://158.69.27.239:8080/steve/websocket/CentralSystemService/';
        this.serverStatus = 'disconnected';
        this.isLoading = false;
        this.init();
    }

    /**
     * Initialize the manager
     */
    init() {
        this.modal = document.getElementById('steveConnectionModal');
        if (!this.modal) {
            console.warn('SteVe connection modal not found');
            return;
        }

        // Listen for modal open events
        document.addEventListener('openSteveModal', (e) => this.openModal(e.detail));
        
        // Listen for connection events
        window.addEventListener('steveConnected', (e) => this.handleConnectionSuccess(e.detail));
    }

    /**
     * Open the modal with charging point data
     */
    openModal(data) {
        this.chargingPointId = data.chargingPointId;
        this.chargeBoxId = data.chargeBoxId || `BORNE_${this.chargingPointId}`;
        
        // Update modal data attributes
        this.modal.dataset.chargingPointId = this.chargingPointId;
        
        // Show modal
        this.modal.classList.remove('hidden');
        
        // Load charge box ID
        this.loadChargeBoxId();
        
        // Check server status
        this.checkServerStatus();
    }

    /**
     * Close the modal
     */
    closeModal() {
        this.modal.classList.add('hidden');
        this.resetForm();
    }

    /**
     * Load charge box ID from API
     */
    async loadChargeBoxId() {
        try {
            const response = await fetch(`/api/v1/charging-points/${this.chargingPointId}/charge-box-id`);
            const data = await response.json();
            
            if (data.success && data.charge_box_id) {
                this.chargeBoxId = data.charge_box_id;
                this.updateChargeBoxIdDisplay();
            }
        } catch (error) {
            console.error('Error loading charge box ID:', error);
        }
    }

    /**
     * Update charge box ID display
     */
    updateChargeBoxIdDisplay() {
        const input = document.getElementById('chargeBoxId');
        if (input) {
            input.value = this.chargeBoxId;
        }
    }

    /**
     * Check server status
     */
    async checkServerStatus() {
        try {
            const response = await fetch(`/api/v1/charging-points/${this.chargingPointId}/steve-status`);
            const data = await response.json();
            
            if (data.success) {
                const serverData = data.data.server_status;
                this.serverStatus = serverData.overall_status === 'success' ? 'connected' : 'disconnected';
                this.updateStatusIndicator();
            }
        } catch (error) {
            console.error('Error checking server status:', error);
            this.serverStatus = 'disconnected';
            this.updateStatusIndicator();
        }
    }

    /**
     * Update status indicator
     */
    updateStatusIndicator() {
        const indicator = document.getElementById('statusIndicator');
        if (indicator) {
            indicator.className = 'w-3 h-3 rounded-full transition-colors';
            
            if (this.serverStatus === 'connected') {
                indicator.classList.add('bg-green-500');
            } else if (this.serverStatus === 'connecting') {
                indicator.classList.add('bg-yellow-500');
            } else {
                indicator.classList.add('bg-red-500');
            }
        }
    }

    /**
     * Get status label
     */
    getStatusLabel() {
        const labels = {
            'connected': '✓ Connecté',
            'connecting': '⟳ Connexion en cours...',
            'disconnected': '✗ Déconnecté'
        };
        return labels[this.serverStatus] || 'Statut inconnu';
    }

    /**
     * Connect to SteVe
     */
    async connectToSteVe() {
        if (!this.chargeBoxId) {
            this.showError('L\'ID de la borne est requis');
            return;
        }

        this.isLoading = true;
        this.showLoading(true);

        try {
            const response = await fetch(`/api/v1/charging-points/${this.chargingPointId}/connect-steve`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCsrfToken()
                },
                body: JSON.stringify({
                    charge_box_id: this.chargeBoxId,
                    steve_server_url: this.serverUrl
                })
            });

            const data = await response.json();

            if (data.success) {
                this.serverStatus = 'connected';
                this.updateStatusIndicator();
                this.showSuccess('Borne connectée avec succès au serveur SteVe');
                
                // Emit event
                window.dispatchEvent(new CustomEvent('steveConnected', {
                    detail: {
                        chargeBoxId: this.chargeBoxId,
                        websocketUrl: data.data.websocket_url,
                        chargingPoint: data.data.charging_point
                    }
                }));

                // Close modal after 2 seconds
                setTimeout(() => this.closeModal(), 2000);
            } else {
                this.showError(data.message || 'Erreur lors de la connexion');
                this.serverStatus = 'disconnected';
                this.updateStatusIndicator();
            }
        } catch (error) {
            this.showError('Erreur réseau: ' + error.message);
            this.serverStatus = 'disconnected';
            this.updateStatusIndicator();
        } finally {
            this.isLoading = false;
            this.showLoading(false);
        }
    }

    /**
     * Handle connection success
     */
    handleConnectionSuccess(detail) {
        console.log('SteVe connection successful:', detail);
        
        // Reload page or update UI
        if (window.location.pathname.includes('charging-points')) {
            location.reload();
        }
    }

    /**
     * Show error message
     */
    showError(message) {
        const errorDiv = this.modal.querySelector('[x-show="errorMessage"]') || this.createMessageDiv('error');
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
        
        setTimeout(() => {
            errorDiv.style.display = 'none';
        }, 5000);
    }

    /**
     * Show success message
     */
    showSuccess(message) {
        const successDiv = this.modal.querySelector('[x-show="successMessage"]') || this.createMessageDiv('success');
        successDiv.textContent = message;
        successDiv.style.display = 'block';
        
        setTimeout(() => {
            successDiv.style.display = 'none';
        }, 5000);
    }

    /**
     * Show loading state
     */
    showLoading(show) {
        const loadingDiv = this.modal.querySelector('[v-if="isLoading"]');
        if (loadingDiv) {
            loadingDiv.style.display = show ? 'flex' : 'none';
        }
    }

    /**
     * Create message div
     */
    createMessageDiv(type) {
        const div = document.createElement('div');
        div.className = type === 'error' 
            ? 'p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800'
            : 'p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800';
        
        const p = document.createElement('p');
        p.className = type === 'error'
            ? 'text-sm text-red-600 dark:text-red-400'
            : 'text-sm text-green-600 dark:text-green-400';
        
        div.appendChild(p);
        this.modal.querySelector('.px-6.py-4.space-y-4').appendChild(div);
        
        return p;
    }

    /**
     * Reset form
     */
    resetForm() {
        const input = document.getElementById('chargeBoxId');
        if (input) {
            input.value = '';
        }
    }

    /**
     * Get CSRF token
     */
    getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    /**
     * Get generated WebSocket URL
     */
    getGeneratedWebSocketUrl() {
        if (!this.chargeBoxId) return 'URL sera générée après saisie de l\'ID';
        return this.serverUrl + this.chargeBoxId;
    }
}

// Initialize on document ready
document.addEventListener('DOMContentLoaded', () => {
    window.steveConnectionManager = new SteVeConnectionManager();
});

// Helper function to open modal from anywhere
function openSteveConnectionModal(chargingPointId, chargeBoxId = null) {
    const event = new CustomEvent('openSteveModal', {
        detail: {
            chargingPointId: chargingPointId,
            chargeBoxId: chargeBoxId
        }
    });
    document.dispatchEvent(event);
}

