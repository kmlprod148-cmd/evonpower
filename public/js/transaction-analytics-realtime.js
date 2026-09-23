/**
 * Script JavaScript pour les mises à jour en temps réel du tableau de bord des transactions
 */

class TransactionAnalyticsRealtime {
    constructor() {
        this.refreshInterval = 30000; // 30 secondes
        this.isConnected = false;
        this.eventSource = null;
        this.charts = {};
        this.lastUpdate = null;
        
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.startRealTimeUpdates();
        this.setupAutoRefresh();
    }

    setupEventListeners() {
        // Écouter les événements de visibilité de la page
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.pauseUpdates();
            } else {
                this.resumeUpdates();
            }
        });

        // Écouter les événements de redimensionnement de la fenêtre
        window.addEventListener('resize', () => {
            this.resizeCharts();
        });
    }

    startRealTimeUpdates() {
        // Vérifier si Server-Sent Events sont supportés
        if (typeof EventSource !== 'undefined') {
            this.setupServerSentEvents();
        } else {
            // Fallback vers polling si SSE n'est pas supporté
            this.setupPolling();
        }
    }

    setupServerSentEvents() {
        try {
            this.eventSource = new EventSource('/transactions/analytics/stream');
            
            this.eventSource.onopen = () => {
                this.isConnected = true;
                this.updateConnectionStatus(true);
                console.log('Connexion SSE établie');
            };

            this.eventSource.onmessage = (event) => {
                this.handleRealTimeData(JSON.parse(event.data));
            };

            this.eventSource.onerror = () => {
                this.isConnected = false;
                this.updateConnectionStatus(false);
                console.error('Erreur de connexion SSE');
                
                // Retry après 5 secondes
                setTimeout(() => {
                    this.setupServerSentEvents();
                }, 5000);
            };

        } catch (error) {
            console.error('Erreur lors de la configuration SSE:', error);
            this.setupPolling();
        }
    }

    setupPolling() {
        console.log('Utilisation du polling pour les mises à jour');
        
        setInterval(() => {
            this.fetchLatestData();
        }, this.refreshInterval);
    }

    async fetchLatestData() {
        try {
            const response = await fetch('/transactions/analytics/data', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (response.ok) {
                const data = await response.json();
                this.handleRealTimeData(data);
                this.updateConnectionStatus(true);
            } else {
                this.updateConnectionStatus(false);
            }
        } catch (error) {
            console.error('Erreur lors de la récupération des données:', error);
            this.updateConnectionStatus(false);
        }
    }

    handleRealTimeData(data) {
        this.lastUpdate = new Date();
        
        // Mettre à jour les statistiques générales
        this.updateGeneralStats(data.general_stats);
        
        // Mettre à jour les graphiques
        this.updateCharts(data);
        
        // Mettre à jour les tableaux
        this.updateTables(data);
        
        // Afficher une notification de mise à jour
        this.showUpdateNotification();
        
        // Mettre à jour l'indicateur de dernière mise à jour
        this.updateLastUpdateIndicator();
    }

    updateGeneralStats(stats) {
        // Mettre à jour les cartes de statistiques
        const statCards = document.querySelectorAll('.stat-card .stat-value');
        
        if (statCards[0]) statCards[0].textContent = this.formatNumber(stats.total_transactions);
        if (statCards[1]) statCards[1].textContent = this.formatCurrency(stats.total_amount);
        if (statCards[2]) statCards[2].textContent = this.formatNumber(stats.total_energy_delivered) + ' kWh';
        if (statCards[3]) statCards[3].textContent = this.formatCurrency(stats.average_transaction_amount);
        if (statCards[4]) statCards[4].textContent = this.formatNumber(stats.average_duration / 60, 1) + ' min';
        
        // Calculer et mettre à jour le taux de réussite
        const successRate = stats.total_transactions > 0 ? 
            ((stats.completed_transactions / stats.total_transactions) * 100).toFixed(1) : 0;
        if (statCards[5]) statCards[5].textContent = successRate + '%';
    }

    updateCharts(data) {
        // Mettre à jour le graphique des flux entre rôles
        if (this.charts.roleFlow && data.hierarchy_analytics?.role_flows) {
            this.updateRoleFlowChart(data.hierarchy_analytics.role_flows);
        }

        // Mettre à jour le graphique des commissions
        if (this.charts.commission && data.financial_analytics?.commission_by_role) {
            this.updateCommissionChart(data.financial_analytics.commission_by_role);
        }

        // Mettre à jour le graphique des tendances quotidiennes
        if (this.charts.dailyTrends && data.temporal_analytics?.daily_trends) {
            this.updateDailyTrendsChart(data.temporal_analytics.daily_trends);
        }

        // Mettre à jour le graphique de distribution par heure
        if (this.charts.hourly && data.temporal_analytics?.hourly_distribution) {
            this.updateHourlyChart(data.temporal_analytics.hourly_distribution);
        }
    }

    updateRoleFlowChart(roleFlows) {
        const chart = this.charts.roleFlow;
        if (!chart) return;

        const labels = roleFlows.map(item => {
            return item.transaction_type === 'admin_integrator' ? 'Admin ↔ Intégrateur' : 'Intégrateur ↔ Opérateur';
        });
        
        chart.data.labels = labels;
        chart.data.datasets[0].data = roleFlows.map(item => item.count);
        chart.update('active');
    }

    updateCommissionChart(commissionData) {
        const chart = this.charts.commission;
        if (!chart) return;

        chart.data.datasets[0].data = [
            commissionData.admin || 0,
            commissionData.integrator || 0,
            commissionData.partner || 0
        ];
        chart.update('active');
    }

    updateDailyTrendsChart(dailyTrends) {
        const chart = this.charts.dailyTrends;
        if (!chart) return;

        chart.data.labels = dailyTrends.map(item => item.date);
        chart.data.datasets[0].data = dailyTrends.map(item => item.transaction_count);
        chart.data.datasets[1].data = dailyTrends.map(item => item.total_amount);
        chart.update('active');
    }

    updateHourlyChart(hourlyData) {
        const chart = this.charts.hourly;
        if (!chart) return;

        chart.data.labels = hourlyData.map(item => item.hour + 'h');
        chart.data.datasets[0].data = hourlyData.map(item => item.transaction_count);
        chart.update('active');
    }

    updateTables(data) {
        // Mettre à jour les tableaux de top utilisateurs
        this.updateTopUsersTable(data.user_analytics?.top_active_users, 'top-active-users');
        this.updateTopUsersTable(data.user_analytics?.top_revenue_users, 'top-revenue-users');
        
        // Mettre à jour les tableaux géographiques
        this.updateGeographicTables(data.geographic_analytics);
    }

    updateTopUsersTable(users, tableId) {
        const tableBody = document.querySelector(`#${tableId} tbody`);
        if (!tableBody || !users) return;

        tableBody.innerHTML = '';
        
        users.forEach(user => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${user.user?.name || 'N/A'}</td>
                <td><span class="badge bg-primary">${user.user?.role || 'N/A'}</span></td>
                <td>${user.transaction_count}</td>
                <td>${this.formatCurrency(user.total_amount)}</td>
                <td>${this.formatCurrency(user.avg_amount || (user.total_amount / user.transaction_count))}</td>
            `;
            tableBody.appendChild(row);
        });
    }

    updateGeographicTables(geographicData) {
        if (!geographicData) return;

        // Mettre à jour le tableau des stations
        this.updateTableRows(geographicData.station_stats, 'station-stats-table');
        
        // Mettre à jour le tableau des villes
        this.updateTableRows(geographicData.city_stats, 'city-stats-table');
        
        // Mettre à jour le tableau des pays
        this.updateTableRows(geographicData.country_stats, 'country-stats-table');
    }

    updateTableRows(data, tableId) {
        const tableBody = document.querySelector(`#${tableId} tbody`);
        if (!tableBody || !data) return;

        tableBody.innerHTML = '';
        
        data.forEach(item => {
            const row = document.createElement('tr');
            const cells = this.getTableCells(item, tableId);
            row.innerHTML = cells.map(cell => `<td>${cell}</td>`).join('');
            tableBody.appendChild(row);
        });
    }

    getTableCells(item, tableId) {
        switch (tableId) {
            case 'station-stats-table':
                return [
                    item.station_name || 'N/A',
                    item.city || 'N/A',
                    item.transaction_count || 0,
                    this.formatCurrency(item.total_amount || 0)
                ];
            case 'city-stats-table':
                return [
                    item.city || 'N/A',
                    item.country || 'N/A',
                    item.transaction_count || 0,
                    this.formatCurrency(item.total_amount || 0)
                ];
            case 'country-stats-table':
                return [
                    item.country || 'N/A',
                    item.transaction_count || 0,
                    this.formatCurrency(item.total_amount || 0)
                ];
            default:
                return [];
        }
    }

    updateConnectionStatus(isConnected) {
        const statusIndicator = document.getElementById('connection-status');
        if (!statusIndicator) return;

        if (isConnected) {
            statusIndicator.className = 'connection-status connected';
            statusIndicator.innerHTML = '<i class="fas fa-circle text-success"></i> Connecté';
        } else {
            statusIndicator.className = 'connection-status disconnected';
            statusIndicator.innerHTML = '<i class="fas fa-circle text-danger"></i> Déconnecté';
        }
    }

    updateLastUpdateIndicator() {
        const indicator = document.getElementById('last-update');
        if (!indicator || !this.lastUpdate) return;

        indicator.textContent = `Dernière mise à jour: ${this.lastUpdate.toLocaleTimeString()}`;
    }

    showUpdateNotification() {
        // Créer une notification discrète
        const notification = document.createElement('div');
        notification.className = 'update-notification';
        notification.innerHTML = '<i class="fas fa-sync-alt"></i> Données mises à jour';
        
        document.body.appendChild(notification);
        
        // Supprimer la notification après 2 secondes
        setTimeout(() => {
            notification.remove();
        }, 2000);
    }

    setupAutoRefresh() {
        // Actualisation automatique toutes les 5 minutes
        setInterval(() => {
            if (!document.hidden) {
                this.fetchLatestData();
            }
        }, 300000); // 5 minutes
    }

    pauseUpdates() {
        if (this.eventSource) {
            this.eventSource.close();
        }
        console.log('Mises à jour en pause');
    }

    resumeUpdates() {
        if (this.eventSource) {
            this.eventSource.close();
        }
        this.startRealTimeUpdates();
        console.log('Mises à jour reprises');
    }

    resizeCharts() {
        Object.values(this.charts).forEach(chart => {
            if (chart && typeof chart.resize === 'function') {
                chart.resize();
            }
        });
    }

    // Méthodes utilitaires
    formatNumber(number, decimals = 0) {
        return new Intl.NumberFormat('fr-FR', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format(number);
    }

    formatCurrency(amount) {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'EUR'
        }).format(amount);
    }

    // Méthode publique pour enregistrer les graphiques
    registerChart(name, chart) {
        this.charts[name] = chart;
    }

    // Méthode publique pour obtenir les données actuelles
    async getCurrentData() {
        try {
            const response = await fetch('/transactions/analytics/data');
            return await response.json();
        } catch (error) {
            console.error('Erreur lors de la récupération des données actuelles:', error);
            return null;
        }
    }

    // Méthode publique pour forcer une mise à jour
    forceUpdate() {
        this.fetchLatestData();
    }

    // Nettoyage lors de la fermeture de la page
    destroy() {
        if (this.eventSource) {
            this.eventSource.close();
        }
        
        Object.values(this.charts).forEach(chart => {
            if (chart && typeof chart.destroy === 'function') {
                chart.destroy();
            }
        });
    }
}

// Initialiser le système de mises à jour en temps réel
document.addEventListener('DOMContentLoaded', function() {
    window.transactionAnalyticsRealtime = new TransactionAnalyticsRealtime();
    
    // Nettoyer lors de la fermeture de la page
    window.addEventListener('beforeunload', function() {
        if (window.transactionAnalyticsRealtime) {
            window.transactionAnalyticsRealtime.destroy();
        }
    });
});

// Styles CSS pour les notifications et indicateurs
const style = document.createElement('style');
style.textContent = `
    .connection-status {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 8px 12px;
        border-radius: 4px;
        font-size: 0.875rem;
        z-index: 1000;
        background: white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .connection-status.connected {
        border-left: 4px solid #28a745;
    }

    .connection-status.disconnected {
        border-left: 4px solid #dc3545;
    }

    .update-notification {
        position: fixed;
        bottom: 20px;
        right: 20px;
        padding: 12px 16px;
        background: #28a745;
        color: white;
        border-radius: 4px;
        font-size: 0.875rem;
        z-index: 1000;
        animation: slideIn 0.3s ease-out;
    }

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

    .last-update-indicator {
        position: fixed;
        bottom: 20px;
        left: 20px;
        padding: 8px 12px;
        background: rgba(0,0,0,0.7);
        color: white;
        border-radius: 4px;
        font-size: 0.75rem;
        z-index: 1000;
    }
`;
document.head.appendChild(style);
