/**
 * SteVe API Integration JavaScript
 * Handles real-time data loading and OCPP management
 */

class SteVeIntegration {
    constructor() {
        this.baseUrl = '/steve';
        this.chargingPointId = null;
        this.refreshInterval = null;
        this.init();
    }

    init() {
        this.chargingPointId = this.getChargingPointId();
        if (this.chargingPointId) {
            this.loadInitialData();
            this.setupEventListeners();
            this.startAutoRefresh();
        }
    }

    getChargingPointId() {
        // Extract charging point ID from URL or data attribute
        const urlParts = window.location.pathname.split('/');
        const cpIndex = urlParts.indexOf('charging-points');
        if (cpIndex !== -1 && urlParts[cpIndex + 1]) {
            return urlParts[cpIndex + 1];
        }
        
        // Fallback to data attribute
        const element = document.querySelector('[data-charging-point-id]');
        return element ? element.dataset.chargingPointId : null;
    }

    async loadInitialData() {
        try {
            await Promise.all([
                this.loadTransactions(),
                this.loadStatistics(),
                this.loadOcppTags()
            ]);
        } catch (error) {
            console.error('Error loading initial SteVe data:', error);
            this.showError('Erreur lors du chargement des données SteVe');
        }
    }

    async loadTransactions(period = 'LAST_30') {
        try {
            const response = await fetch(`${this.baseUrl}/charging-points/${this.chargingPointId}/transactions?period=${period}`);
            const data = await response.json();

            if (data.success) {
                this.updateTransactionsTable(data.data);
                this.updateTransactionStats(data.data);
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            console.error('Error loading transactions:', error);
            this.showError('Erreur lors du chargement des transactions');
        }
    }

    async loadStatistics() {
        try {
            const response = await fetch(`${this.baseUrl}/charging-points/${this.chargingPointId}/statistics`);
            const data = await response.json();

            if (data.success) {
                this.updateStatisticsCards(data.data);
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            console.error('Error loading statistics:', error);
            this.showError('Erreur lors du chargement des statistiques');
        }
    }

    async loadOcppTags() {
        try {
            const response = await fetch(`${this.baseUrl}/charging-points/${this.chargingPointId}/tags`);
            const data = await response.json();

            if (data.success) {
                this.updateOcppTagsTable(data.data);
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            console.error('Error loading OCPP tags:', error);
            this.showError('Erreur lors du chargement des tags OCPP');
        }
    }

    updateTransactionsTable(transactions) {
        const tableBody = document.querySelector('#sessions-table tbody');
        if (!tableBody) return;

        tableBody.innerHTML = '';

        if (transactions.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-4 text-gray-500">
                        <i class="fas fa-info-circle mr-2"></i>
                        Aucune session trouvée
                    </td>
                </tr>
            `;
            return;
        }

        transactions.forEach(transaction => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-gray-50 transition-colors';
            
            const statusBadge = this.getStatusBadge(transaction.status);
            const duration = transaction.duration ? `${Math.floor(transaction.duration / 60)}h ${transaction.duration % 60}min` : 'En cours';
            const energy = transaction.energy_consumed ? `${transaction.energy_consumed} kWh` : 'N/A';

            row.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    #${transaction.id}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${this.formatDateTime(transaction.start_time)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${duration}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${energy}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${transaction.ocpp_tag}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    ${statusBadge}
                </td>
            `;

            tableBody.appendChild(row);
        });
    }

    updateStatisticsCards(stats) {
        // Update total sessions
        const totalElement = document.querySelector('[data-stat="total-sessions"]');
        if (totalElement) {
            totalElement.textContent = stats.total_sessions || '0';
        }

        // Update active sessions
        const activeElement = document.querySelector('[data-stat="active-sessions"]');
        if (activeElement) {
            activeElement.textContent = stats.active_sessions || '0';
        }

        // Update total energy
        const energyElement = document.querySelector('[data-stat="total-energy"]');
        if (energyElement) {
            energyElement.textContent = stats.total_energy || '0 kWh';
        }

        // Update average duration
        const durationElement = document.querySelector('[data-stat="average-duration"]');
        if (durationElement) {
            durationElement.textContent = stats.average_duration || '0min';
        }

        // Update success rate
        const successElement = document.querySelector('[data-stat="success-rate"]');
        if (successElement) {
            successElement.textContent = stats.success_rate || '0%';
        }
    }

    updateOcppTagsTable(tags) {
        const tableBody = document.querySelector('#ocpp-tags-table tbody');
        if (!tableBody) return;

        tableBody.innerHTML = '';

        if (tags.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-4 text-gray-500">
                        <i class="fas fa-info-circle mr-2"></i>
                        Aucun tag OCPP trouvé
                    </td>
                </tr>
            `;
            return;
        }

        tags.forEach(tag => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-gray-50 transition-colors';
            
            const statusBadge = tag.blocked ? 
                '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Bloqué</span>' :
                '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Actif</span>';

            const inTransaction = tag.in_transaction ?
                '<i class="fas fa-bolt text-yellow-500" title="En transaction"></i>' :
                '<i class="fas fa-circle text-gray-300" title="Disponible"></i>';

            row.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    ${tag.tag_id}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                    ${inTransaction}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${tag.active_transactions}/${tag.max_transactions}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    ${statusBadge}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <div class="flex space-x-2">
                        <button onclick="steveIntegration.toggleTagBlock(${tag.id}, ${!tag.blocked})" 
                                class="text-blue-600 hover:text-blue-900 transition-colors">
                            <i class="fas fa-${tag.blocked ? 'unlock' : 'lock'}"></i>
                        </button>
                        <button onclick="steveIntegration.deleteTag(${tag.id})" 
                                class="text-red-600 hover:text-red-900 transition-colors">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            `;

            tableBody.appendChild(row);
        });
    }

    async toggleTagBlock(tagId, blocked) {
        try {
            const response = await fetch(`${this.baseUrl}/ocpp-tags/${tagId}/toggle-block`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ blocked })
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess(data.message);
                await this.loadOcppTags(); // Refresh tags
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            console.error('Error toggling tag block:', error);
            this.showError('Erreur lors de la modification du tag');
        }
    }

    async deleteTag(tagId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer ce tag OCPP ?')) {
            return;
        }

        try {
            const response = await fetch(`${this.baseUrl}/ocpp-tags/${tagId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess(data.message);
                await this.loadOcppTags(); // Refresh tags
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            console.error('Error deleting tag:', error);
            this.showError('Erreur lors de la suppression du tag');
        }
    }

    async startCharging(connectorId = 1) {
        const tagId = prompt('Entrez l\'ID du tag OCPP pour démarrer la recharge:');
        if (!tagId) return;

        try {
            const response = await fetch(`${this.baseUrl}/charging-points/${this.chargingPointId}/start-charging`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ 
                    tag_id: tagId,
                    connector_id: connectorId 
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess(data.message);
                await this.loadTransactions(); // Refresh transactions
                await this.loadStatistics(); // Refresh statistics
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            console.error('Error starting charging:', error);
            this.showError('Erreur lors du démarrage de la recharge');
        }
    }

    async stopCharging() {
        if (!confirm('Êtes-vous sûr de vouloir arrêter toutes les recharges actives ?')) {
            return;
        }

        try {
            const response = await fetch(`${this.baseUrl}/charging-points/${this.chargingPointId}/stop-charging`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess(data.message);
                await this.loadTransactions(); // Refresh transactions
                await this.loadStatistics(); // Refresh statistics
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            console.error('Error stopping charging:', error);
            this.showError('Erreur lors de l\'arrêt de la recharge');
        }
    }

    setupEventListeners() {
        // Period filter for transactions
        const periodSelect = document.querySelector('#transaction-period');
        if (periodSelect) {
            periodSelect.addEventListener('change', (e) => {
                this.loadTransactions(e.target.value);
            });
        }

        // Refresh buttons
        const refreshButtons = document.querySelectorAll('[data-action="refresh"]');
        refreshButtons.forEach(button => {
            button.addEventListener('click', () => {
                this.loadInitialData();
            });
        });

        // Remote action buttons
        const startButton = document.querySelector('[data-action="start-charging"]');
        if (startButton) {
            startButton.addEventListener('click', () => this.startCharging());
        }

        const stopButton = document.querySelector('[data-action="stop-charging"]');
        if (stopButton) {
            stopButton.addEventListener('click', () => this.stopCharging());
        }
    }

    startAutoRefresh() {
        // Refresh every 30 seconds
        this.refreshInterval = setInterval(() => {
            this.loadStatistics();
            this.loadTransactions();
        }, 30000);
    }

    stopAutoRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }
    }

    getStatusBadge(status) {
        const badges = {
            'active': '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>',
            'completed': '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Terminée</span>',
            'failed': '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Échouée</span>'
        };
        return badges[status] || '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Inconnue</span>';
    }

    formatDateTime(dateString) {
        if (!dateString) return 'N/A';
        
        const date = new Date(dateString);
        return date.toLocaleString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    showError(message) {
        this.showNotification(message, 'error');
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full`;
        
        const bgColor = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500';
        notification.classList.add(bgColor, 'text-white');

        const icon = type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle';
        
        notification.innerHTML = `
            <div class="flex items-center">
                <i class="fas fa-${icon} mr-2"></i>
                <span>${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;

        document.body.appendChild(notification);

        // Animate in
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);

        // Auto remove after 5 seconds
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 300);
        }, 5000);
    }

    // Cleanup method
    destroy() {
        this.stopAutoRefresh();
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.steveIntegration = new SteVeIntegration();
});

// Cleanup on page unload (modern approach)
window.addEventListener('pagehide', () => {
    if (window.steveIntegration) {
        window.steveIntegration.destroy();
    }
});

// Cleanup on visibility change
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'hidden' && window.steveIntegration) {
        window.steveIntegration.destroy();
    }
});
