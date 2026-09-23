/**
 * Transaction History Real-time Updates
 * Gère les mises à jour en temps réel pour l'historique des transactions
 */
class TransactionHistoryRealtime {
    constructor(options = {}) {
        this.options = {
            container: '#transactions-table tbody',
            statisticsContainer: '.stat-card',
            notificationContainer: '#realtime-notifications',
            autoRefresh: true,
            refreshInterval: 30000, // 30 secondes
            maxNotifications: 5,
            showNotifications: true,
            ...options
        };

        this.isConnected = false;
        this.refreshTimer = null;
        this.notifications = [];
        this.lastUpdateTime = null;

        this.init();
    }

    init() {
        this.setupWebSocket();
        this.setupAutoRefresh();
        this.setupNotificationContainer();
        this.bindEvents();
    }

    setupWebSocket() {
        if (typeof Echo === 'undefined') {
            console.warn('Echo (Laravel WebSocket) n\'est pas disponible');
            return;
        }

        try {
            // Canal pour les admins
            if (this.hasRole('admin') || this.hasRole('super-admin')) {
                Echo.private('transaction-history.admin')
                    .listen('transaction.created', (e) => this.handleTransactionCreated(e))
                    .listen('transaction.updated', (e) => this.handleTransactionUpdated(e));
            }

            // Canal pour les intégrateurs
            if (this.hasRole('integrator')) {
                Echo.private('transaction-history.integrator.' + this.getIntegratorId())
                    .listen('transaction.created', (e) => this.handleTransactionCreated(e))
                    .listen('transaction.updated', (e) => this.handleTransactionUpdated(e));
            }

            // Canal pour les opérateurs
            if (this.hasRole('operator')) {
                Echo.private('transaction-history.operator.' + this.getUserId())
                    .listen('transaction.created', (e) => this.handleTransactionCreated(e))
                    .listen('transaction.updated', (e) => this.handleTransactionUpdated(e));
            }

            // Canal pour l'utilisateur
            Echo.private('transaction-history.user.' + this.getUserId())
                .listen('transaction.created', (e) => this.handleTransactionCreated(e))
                .listen('transaction.updated', (e) => this.handleTransactionUpdated(e));

            this.isConnected = true;
            console.log('Transaction History WebSocket connecté');

        } catch (error) {
            console.error('Erreur de connexion WebSocket:', error);
            this.isConnected = false;
        }
    }

    setupAutoRefresh() {
        if (!this.options.autoRefresh) return;

        this.refreshTimer = setInterval(() => {
            this.refreshStatistics();
        }, this.options.refreshInterval);
    }

    setupNotificationContainer() {
        if (!this.options.showNotifications) return;

        // Créer le conteneur de notifications s'il n'existe pas
        if (!document.querySelector(this.options.notificationContainer)) {
            const container = document.createElement('div');
            container.id = 'realtime-notifications';
            container.className = 'position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }
    }

    bindEvents() {
        // Écouter les changements de visibilité de la page
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.pauseUpdates();
            } else {
                this.resumeUpdates();
            }
        });

        // Écouter les changements de connexion
        window.addEventListener('online', () => this.handleConnectionChange(true));
        window.addEventListener('offline', () => this.handleConnectionChange(false));
    }

    handleTransactionCreated(data) {
        console.log('Nouvelle transaction créée:', data);
        
        // Ajouter la nouvelle transaction au tableau
        this.addTransactionToTable(data.transaction);
        
        // Mettre à jour les statistiques
        this.updateStatistics('created', data.transaction);
        
        // Afficher une notification
        this.showNotification('Nouvelle transaction', 
            `Transaction #${data.transaction.id} créée pour ${data.transaction.user_name}`, 
            'success');
    }

    handleTransactionUpdated(data) {
        console.log('Transaction mise à jour:', data);
        
        // Mettre à jour la transaction dans le tableau
        this.updateTransactionInTable(data.transaction);
        
        // Mettre à jour les statistiques
        this.updateStatistics('updated', data.transaction);
        
        // Afficher une notification
        this.showNotification('Transaction mise à jour', 
            `Transaction #${data.transaction.id} mise à jour`, 
            'info');
    }

    addTransactionToTable(transaction) {
        const tbody = document.querySelector(this.options.container);
        if (!tbody) return;

        const row = this.createTransactionRow(transaction);
        
        // Insérer au début du tableau
        tbody.insertBefore(row, tbody.firstChild);
        
        // Limiter le nombre de lignes affichées
        this.limitTableRows();
    }

    updateTransactionInTable(transaction) {
        const row = document.querySelector(`tr[data-transaction-id="${transaction.id}"]`);
        if (!row) return;

        // Mettre à jour les données de la ligne
        this.updateTransactionRow(row, transaction);
    }

    createTransactionRow(transaction) {
        const row = document.createElement('tr');
        row.setAttribute('data-transaction-id', transaction.id);
        
        // Déterminer le type et les couleurs
        const type = this.getTransactionType(transaction);
        const colorClass = type === 'debit' ? 'text-red-600' : 'text-green-600';
        const bgClass = type === 'debit' ? 'bg-red-50' : 'bg-green-50';
        const icon = type === 'debit' ? 'arrow-down' : 'arrow-up';
        
        // Déterminer la classe de statut
        const statusClass = this.getStatusClass(transaction.status);

        row.className = bgClass;
        row.innerHTML = `
            <td>
                <span class="badge bg-secondary">#${transaction.id}</span>
            </td>
            <td>
                <span class="badge bg-info">${this.getTransactionTypeLabel(transaction.type)}</span>
            </td>
            <td>
                <span class="badge bg-warning">${this.getTransactionCategoryLabel(transaction.category)}</span>
            </td>
            <td>
                <span class="badge ${statusClass}">${this.getStatusLabel(transaction.status)}</span>
            </td>
            <td>
                <div class="d-flex align-items-center">
                    <i class="fas fa-${icon} me-2 ${colorClass}"></i>
                    <span class="fw-bold ${colorClass}">
                        ${type === 'debit' ? '-' : '+'}${this.formatAmount(transaction.amount)} ${transaction.currency}
                    </span>
                </div>
            </td>
            <td>
                <div class="d-flex align-items-center">
                    <div class="avatar-sm bg-light rounded-circle me-2">
                        <span class="avatar-title bg-primary text-white">
                            ${transaction.user_name.charAt(0)}
                        </span>
                    </div>
                    <div>
                        <h6 class="mb-0">${transaction.user_name}</h6>
                    </div>
                </div>
            </td>
            <td>
                <div>
                    <h6 class="mb-0">${transaction.charging_point_name}</h6>
                </div>
            </td>
            <td>
                <div>
                    <h6 class="mb-0">${transaction.created_at}</h6>
                    <small class="text-muted">${transaction.created_at_human}</small>
                </div>
            </td>
            <td>
                <div class="btn-group" role="group">
                    <a href="/transactions/${transaction.id}" 
                       class="btn btn-outline-primary btn-sm" 
                       title="Voir les détails">
                        <i class="fas fa-eye"></i>
                    </a>
                    ${this.hasRole('admin') ? `
                        <button class="btn btn-outline-secondary btn-sm" 
                                onclick="showTransactionDetails(${transaction.id})"
                                title="Détails complets">
                            <i class="fas fa-info-circle"></i>
                        </button>
                    ` : ''}
                </div>
            </td>
        `;

        // Ajouter une animation d'apparition
        row.style.opacity = '0';
        row.style.transform = 'translateY(-20px)';
        
        setTimeout(() => {
            row.style.transition = 'all 0.3s ease';
            row.style.opacity = '1';
            row.style.transform = 'translateY(0)';
        }, 100);

        return row;
    }

    updateTransactionRow(row, transaction) {
        // Mettre à jour les données de la ligne existante
        const cells = row.querySelectorAll('td');
        
        // Mettre à jour le statut
        const statusCell = cells[3];
        const statusClass = this.getStatusClass(transaction.status);
        statusCell.innerHTML = `<span class="badge ${statusClass}">${this.getStatusLabel(transaction.status)}</span>`;
        
        // Mettre à jour la date
        const dateCell = cells[7];
        dateCell.innerHTML = `
            <div>
                <h6 class="mb-0">${transaction.updated_at}</h6>
                <small class="text-muted">${transaction.updated_at_human}</small>
            </div>
        `;

        // Ajouter une animation de mise à jour
        row.style.backgroundColor = '#fff3cd';
        setTimeout(() => {
            row.style.backgroundColor = '';
        }, 2000);
    }

    updateStatistics(action, transaction) {
        // Mettre à jour les statistiques en temps réel
        const stats = this.calculateStatisticsUpdate(action, transaction);
        
        // Mettre à jour les cartes de statistiques
        this.updateStatisticsCards(stats);
    }

    calculateStatisticsUpdate(action, transaction) {
        const stats = {
            total_transactions: 0,
            credits: 0,
            debits: 0,
            net_balance: 0
        };

        if (action === 'created') {
            stats.total_transactions = 1;
            
            const type = this.getTransactionType(transaction);
            if (type === 'credit') {
                stats.credits = transaction.amount;
            } else {
                stats.debits = transaction.amount;
            }
            
            stats.net_balance = stats.credits - stats.debits;
        }

        return stats;
    }

    updateStatisticsCards(stats) {
        // Mettre à jour les cartes de statistiques
        const cards = document.querySelectorAll(this.options.statisticsContainer);
        
        cards.forEach(card => {
            const title = card.querySelector('h3');
            const label = card.querySelector('p').textContent;
            
            if (label.includes('Total Transactions')) {
                const current = parseInt(title.textContent.replace(/,/g, ''));
                title.textContent = (current + stats.total_transactions).toLocaleString();
            } else if (label.includes('Crédits')) {
                const current = parseFloat(title.textContent.replace(/[^\d.-]/g, ''));
                title.textContent = (current + stats.credits).toFixed(2) + ' €';
            } else if (label.includes('Débits')) {
                const current = parseFloat(title.textContent.replace(/[^\d.-]/g, ''));
                title.textContent = (current + stats.debits).toFixed(2) + ' €';
            } else if (label.includes('Solde Net')) {
                const current = parseFloat(title.textContent.replace(/[^\d.-]/g, ''));
                const newBalance = current + stats.net_balance;
                title.textContent = newBalance.toFixed(2) + ' €';
                
                // Mettre à jour la couleur selon le solde
                const icon = card.querySelector('.stat-icon');
                if (newBalance >= 0) {
                    icon.className = icon.className.replace('bg-danger', 'bg-success');
                    title.className = title.className.replace('text-red-600', 'text-green-600');
                } else {
                    icon.className = icon.className.replace('bg-success', 'bg-danger');
                    title.className = title.className.replace('text-green-600', 'text-red-600');
                }
            }
        });
    }

    showNotification(title, message, type = 'info') {
        if (!this.options.showNotifications) return;

        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show`;
        notification.style.minWidth = '300px';
        notification.innerHTML = `
            <strong>${title}</strong><br>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        const container = document.querySelector(this.options.notificationContainer);
        container.appendChild(notification);

        // Supprimer automatiquement après 5 secondes
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);

        // Limiter le nombre de notifications
        this.limitNotifications();
    }

    limitNotifications() {
        const container = document.querySelector(this.options.notificationContainer);
        const notifications = container.querySelectorAll('.alert');
        
        if (notifications.length > this.options.maxNotifications) {
            for (let i = this.options.maxNotifications; i < notifications.length; i++) {
                notifications[i].remove();
            }
        }
    }

    limitTableRows() {
        const tbody = document.querySelector(this.options.container);
        if (!tbody) return;

        const rows = tbody.querySelectorAll('tr');
        const maxRows = 50; // Limiter à 50 lignes pour les performances
        
        if (rows.length > maxRows) {
            for (let i = maxRows; i < rows.length; i++) {
                rows[i].remove();
            }
        }
    }

    refreshStatistics() {
        // Rafraîchir les statistiques via AJAX
        fetch('/transactions-history/statistics', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateStatisticsCards(data.statistics);
            }
        })
        .catch(error => {
            console.error('Erreur lors du rafraîchissement des statistiques:', error);
        });
    }

    pauseUpdates() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
        }
    }

    resumeUpdates() {
        if (this.options.autoRefresh) {
            this.setupAutoRefresh();
        }
    }

    handleConnectionChange(isOnline) {
        if (isOnline) {
            console.log('Connexion rétablie');
            this.setupWebSocket();
        } else {
            console.log('Connexion perdue');
            this.isConnected = false;
        }
    }

    // Méthodes utilitaires
    getTransactionType(transaction) {
        if (['debit', 'integrator_debit', 'periodic_fee', 'withdrawal'].includes(transaction.category)) {
            return 'debit';
        }
        if (['charging', 'commission', 'deposit', 'activation'].includes(transaction.category)) {
            return 'credit';
        }
        return transaction.type === 'client' ? 'credit' : 'debit';
    }

    getStatusClass(status) {
        const classes = {
            'completed': 'bg-success',
            'pending': 'bg-warning',
            'cancelled': 'bg-danger',
            'failed': 'bg-danger'
        };
        return classes[status] || 'bg-secondary';
    }

    getTransactionTypeLabel(type) {
        const labels = {
            'client': 'Client',
            'admin': 'Administration',
            'activation_fee': 'Frais d\'activation'
        };
        return labels[type] || 'Inconnu';
    }

    getTransactionCategoryLabel(category) {
        const labels = {
            'charging': 'Recharge',
            'debit': 'Débit Admin',
            'integrator_debit': 'Débit Intégrateur',
            'commission': 'Commission',
            'activation': 'Activation'
        };
        return labels[category] || 'Inconnu';
    }

    getStatusLabel(status) {
        const labels = {
            'completed': 'Terminé',
            'pending': 'En attente',
            'cancelled': 'Annulé',
            'failed': 'Échoué'
        };
        return labels[status] || status;
    }

    formatAmount(amount) {
        return parseFloat(amount).toFixed(2);
    }

    hasRole(role) {
        // Cette méthode doit être implémentée selon votre système d'authentification
        return document.body.getAttribute('data-user-roles')?.includes(role) || false;
    }

    getUserId() {
        return document.body.getAttribute('data-user-id') || null;
    }

    getIntegratorId() {
        return document.body.getAttribute('data-integrator-id') || null;
    }

    destroy() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
        }
        
        if (typeof Echo !== 'undefined') {
            Echo.leaveAllChannels();
        }
        
        this.isConnected = false;
    }
}

// Initialisation automatique si la page contient l'historique des transactions
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('#transactions-table')) {
        window.transactionHistoryRealtime = new TransactionHistoryRealtime({
            container: '#transactions-table tbody',
            statisticsContainer: '.stat-card',
            autoRefresh: true,
            refreshInterval: 30000,
            showNotifications: true
        });
    }
});
