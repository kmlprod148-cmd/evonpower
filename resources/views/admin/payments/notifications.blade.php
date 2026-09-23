@extends('layouts.admin')

@section('title', 'Notifications Paiements')

@push('styles')
<style>
    .notifications-container {
        padding: 2rem;
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        min-height: 100vh;
    }

    .notifications-header {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        border-left: 6px solid #f59e0b;
    }

    .notification-types {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
        margin-bottom: 3rem;
    }

    .notification-card {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 2px solid transparent;
    }

    .notification-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 40px rgba(0,0,0,0.15);
    }

    .notification-card.active {
        border-color: #f59e0b;
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    }

    .notification-icon {
        width: 60px;
        height: 60px;
        border-radius: 16px;
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1.5rem;
        font-size: 1.5rem;
        color: white;
    }

    .notification-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.5rem;
    }

    .notification-description {
        color: #64748b;
        font-size: 0.9rem;
        margin-bottom: 1.5rem;
    }

    .notification-settings {
        space-y: 1rem;
    }

    .setting-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem;
        border: 2px solid #f1f5f9;
        border-radius: 12px;
        transition: all 0.3s ease;
    }

    .setting-item:hover {
        border-color: #f59e0b;
        background: #fef3c7;
    }

    .setting-info {
        flex: 1;
    }

    .setting-name {
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.25rem;
    }

    .setting-description {
        color: #64748b;
        font-size: 0.9rem;
    }

    .setting-toggle {
        position: relative;
        width: 60px;
        height: 30px;
        background: #e5e7eb;
        border-radius: 15px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .setting-toggle.active {
        background: #f59e0b;
    }

    .setting-toggle::after {
        content: '';
        position: absolute;
        top: 3px;
        left: 3px;
        width: 24px;
        height: 24px;
        background: white;
        border-radius: 50%;
        transition: all 0.3s ease;
    }

    .setting-toggle.active::after {
        transform: translateX(30px);
    }

    .notification-history {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    }

    .history-filters {
        display: flex;
        gap: 1rem;
        margin-bottom: 2rem;
        flex-wrap: wrap;
    }

    .filter-btn {
        padding: 0.5rem 1rem;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        background: white;
        color: #64748b;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .filter-btn.active {
        background: #f59e0b;
        color: white;
        border-color: #f59e0b;
    }

    .history-list {
        space-y: 1rem;
    }

    .history-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        border: 2px solid #f1f5f9;
        border-radius: 12px;
        transition: all 0.3s ease;
    }

    .history-item:hover {
        border-color: #f59e0b;
        background: #fef3c7;
    }

    .history-item.unread {
        border-color: #3b82f6;
        background: #dbeafe;
    }

    .history-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        color: white;
    }

    .history-icon.success {
        background: #10b981;
    }

    .history-icon.warning {
        background: #f59e0b;
    }

    .history-icon.error {
        background: #ef4444;
    }

    .history-icon.info {
        background: #3b82f6;
    }

    .history-content {
        flex: 1;
    }

    .history-title {
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.25rem;
    }

    .history-description {
        color: #64748b;
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
    }

    .history-meta {
        display: flex;
        gap: 1rem;
        font-size: 0.8rem;
        color: #64748b;
    }

    .history-actions {
        display: flex;
        gap: 0.5rem;
    }

    .history-btn {
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 6px;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .history-btn.mark-read {
        background: #3b82f6;
        color: white;
    }

    .history-btn.delete {
        background: #ef4444;
        color: white;
    }

    .history-btn.archive {
        background: #6b7280;
        color: white;
    }

    .notification-templates {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    }

    .template-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-top: 2rem;
    }

    .template-card {
        border: 2px solid #f1f5f9;
        border-radius: 12px;
        padding: 1.5rem;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .template-card:hover {
        border-color: #f59e0b;
        transform: translateY(-2px);
    }

    .template-title {
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.5rem;
    }

    .template-description {
        color: #64748b;
        font-size: 0.9rem;
        margin-bottom: 1rem;
    }

    .template-preview {
        background: #f8fafc;
        border-radius: 8px;
        padding: 1rem;
        font-size: 0.8rem;
        color: #64748b;
        margin-bottom: 1rem;
    }

    .template-actions {
        display: flex;
        gap: 0.5rem;
    }

    .template-btn {
        flex: 1;
        padding: 0.5rem;
        border: none;
        border-radius: 6px;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .template-btn.edit {
        background: #3b82f6;
        color: white;
    }

    .template-btn.test {
        background: #10b981;
        color: white;
    }

    @media (max-width: 768px) {
        .notification-types {
            grid-template-columns: 1fr;
        }
        
        .history-filters {
            flex-direction: column;
        }
        
        .template-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="notifications-container">
    <!-- Header -->
    <div class="notifications-header">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Notifications Paiements</h1>
                <p class="text-gray-600 mt-2">Gérez les notifications et alertes du système de paiement</p>
            </div>
            <div class="flex gap-3">
                <button onclick="testAllNotifications()" class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors">
                    <i class="fas fa-bell mr-2"></i>
                    Tester Toutes
                </button>
                <button onclick="markAllAsRead()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-check mr-2"></i>
                    Marquer comme Lu
                </button>
            </div>
        </div>
    </div>

    <!-- Notification Types -->
    <div class="notification-types">
        <div class="notification-card active">
            <div class="notification-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="notification-title">Alertes de Paiement</h3>
            <p class="notification-description">Notifications pour les échecs de paiement, problèmes techniques et alertes critiques</p>
            <div class="notification-settings">
                <div class="setting-item">
                    <div class="setting-info">
                        <div class="setting-name">Échecs de Paiement</div>
                        <div class="setting-description">Alertes immédiates pour les échecs</div>
                    </div>
                    <div class="setting-toggle active" onclick="toggleSetting(this)"></div>
                </div>
                <div class="setting-item">
                    <div class="setting-info">
                        <div class="setting-name">Problèmes Techniques</div>
                        <div class="setting-description">Notifications pour les problèmes système</div>
                    </div>
                    <div class="setting-toggle active" onclick="toggleSetting(this)"></div>
                </div>
                <div class="setting-item">
                    <div class="setting-info">
                        <div class="setting-name">Seuils de Montant</div>
                        <div class="setting-description">Alertes pour les transactions importantes</div>
                    </div>
                    <div class="setting-toggle" onclick="toggleSetting(this)"></div>
                </div>
            </div>
        </div>

        <div class="notification-card">
            <div class="notification-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <h3 class="notification-title">Rapports Automatiques</h3>
            <p class="notification-description">Notifications pour les rapports planifiés et analyses automatiques</p>
            <div class="notification-settings">
                <div class="setting-item">
                    <div class="setting-info">
                        <div class="setting-name">Rapports Quotidiens</div>
                        <div class="setting-description">Résumé quotidien des paiements</div>
                    </div>
                    <div class="setting-toggle active" onclick="toggleSetting(this)"></div>
                </div>
                <div class="setting-item">
                    <div class="setting-info">
                        <div class="setting-name">Rapports Hebdomadaires</div>
                        <div class="setting-description">Analyse hebdomadaire des performances</div>
                    </div>
                    <div class="setting-toggle" onclick="toggleSetting(this)"></div>
                </div>
                <div class="setting-item">
                    <div class="setting-info">
                        <div class="setting-name">Alertes de Performance</div>
                        <div class="setting-description">Notifications pour les changements de performance</div>
                    </div>
                    <div class="setting-toggle active" onclick="toggleSetting(this)"></div>
                </div>
            </div>
        </div>

        <div class="notification-card">
            <div class="notification-icon">
                <i class="fas fa-users"></i>
            </div>
            <h3 class="notification-title">Notifications Utilisateur</h3>
            <p class="notification-description">Notifications pour les interactions utilisateur et support</p>
            <div class="notification-settings">
                <div class="setting-item">
                    <div class="setting-info">
                        <div class="setting-name">Nouveaux Utilisateurs</div>
                        <div class="setting-description">Alertes pour les nouveaux comptes</div>
                    </div>
                    <div class="setting-toggle" onclick="toggleSetting(this)"></div>
                </div>
                <div class="setting-item">
                    <div class="setting-info">
                        <div class="setting-name">Support Client</div>
                        <div class="setting-description">Notifications pour les demandes de support</div>
                    </div>
                    <div class="setting-toggle active" onclick="toggleSetting(this)"></div>
                </div>
                <div class="setting-item">
                    <div class="setting-info">
                        <div class="setting-name">Remboursements</div>
                        <div class="setting-description">Alertes pour les demandes de remboursement</div>
                    </div>
                    <div class="setting-toggle active" onclick="toggleSetting(this)"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification History -->
    <div class="notification-history">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold">Historique des Notifications</h3>
            <div class="flex gap-2">
                <button onclick="clearHistory()" class="px-3 py-1 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-sm">
                    <i class="fas fa-trash mr-1"></i>
                    Vider
                </button>
                <button onclick="exportHistory()" class="px-3 py-1 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm">
                    <i class="fas fa-download mr-1"></i>
                    Exporter
                </button>
            </div>
        </div>

        <div class="history-filters">
            <button class="filter-btn active" data-filter="all">Toutes</button>
            <button class="filter-btn" data-filter="unread">Non lues</button>
            <button class="filter-btn" data-filter="success">Succès</button>
            <button class="filter-btn" data-filter="warning">Avertissements</button>
            <button class="filter-btn" data-filter="error">Erreurs</button>
        </div>

        <div class="history-list" id="notification-history-list">
            <!-- Notifications will be loaded here -->
        </div>
    </div>

    <!-- Notification Templates -->
    <div class="notification-templates">
        <h3 class="text-xl font-bold mb-4">Modèles de Notifications</h3>
        <div class="template-grid">
            <div class="template-card" onclick="editTemplate('payment-success')">
                <h4 class="template-title">Paiement Réussi</h4>
                <p class="template-description">Notification envoyée lors d'un paiement réussi</p>
                <div class="template-preview">
                    "Votre paiement de {amount} € a été traité avec succès. Transaction ID: {transaction_id}"
                </div>
                <div class="template-actions">
                    <button class="template-btn edit">Modifier</button>
                    <button class="template-btn test">Tester</button>
                </div>
            </div>

            <div class="template-card" onclick="editTemplate('payment-failed')">
                <h4 class="template-title">Paiement Échoué</h4>
                <p class="template-description">Notification envoyée lors d'un échec de paiement</p>
                <div class="template-preview">
                    "Votre paiement de {amount} € a échoué. Raison: {failure_reason}. Veuillez réessayer."
                </div>
                <div class="template-actions">
                    <button class="template-btn edit">Modifier</button>
                    <button class="template-btn test">Tester</button>
                </div>
            </div>

            <div class="template-card" onclick="editTemplate('refund-processed')">
                <h4 class="template-title">Remboursement Traité</h4>
                <p class="template-description">Notification pour les remboursements traités</p>
                <div class="template-preview">
                    "Votre remboursement de {amount} € a été traité. Il apparaîtra sur votre compte dans 3-5 jours ouvrés."
                </div>
                <div class="template-actions">
                    <button class="template-btn edit">Modifier</button>
                    <button class="template-btn test">Tester</button>
                </div>
            </div>

            <div class="template-card" onclick="editTemplate('system-alert')">
                <h4 class="template-title">Alerte Système</h4>
                <p class="template-description">Notifications pour les alertes système</p>
                <div class="template-preview">
                    "Alerte système: {alert_type} détecté à {timestamp}. Action requise."
                </div>
                <div class="template-actions">
                    <button class="template-btn edit">Modifier</button>
                    <button class="template-btn test">Tester</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadNotificationHistory();
    setupFilterButtons();
});

function toggleSetting(element) {
    element.classList.toggle('active');
    // Save setting to backend
    saveNotificationSetting(element);
}

function saveNotificationSetting(element) {
    // Implement saving notification settings
    console.log('Saving notification setting:', element);
}

function loadNotificationHistory() {
    // Simulate loading notification history
    const notifications = [
        {
            id: 1,
            type: 'success',
            title: 'Paiement CMI Réussi',
            description: 'Transaction #TXN_ABC123 pour 45.50 € traitée avec succès',
            time: 'Il y a 2 minutes',
            unread: true
        },
        {
            id: 2,
            type: 'error',
            title: 'Échec de Paiement Stripe',
            description: 'Transaction #TXN_DEF456 échouée - Carte expirée',
            time: 'Il y a 5 minutes',
            unread: true
        },
        {
            id: 3,
            type: 'warning',
            title: 'Seuil de Montant Atteint',
            description: 'Transaction de 1000+ € détectée - Vérification requise',
            time: 'Il y a 10 minutes',
            unread: false
        },
        {
            id: 4,
            type: 'info',
            title: 'Rapport Quotidien Généré',
            description: 'Rapport des paiements du 14/01/2024 disponible',
            time: 'Il y a 1 heure',
            unread: false
        }
    ];

    const container = document.getElementById('notification-history-list');
    container.innerHTML = notifications.map(notification => `
        <div class="history-item ${notification.unread ? 'unread' : ''}" data-type="${notification.type}">
            <div class="history-icon ${notification.type}">
                <i class="fas fa-${getNotificationIcon(notification.type)}"></i>
            </div>
            <div class="history-content">
                <div class="history-title">${notification.title}</div>
                <div class="history-description">${notification.description}</div>
                <div class="history-meta">
                    <span>${notification.time}</span>
                    <span>•</span>
                    <span>${notification.type.toUpperCase()}</span>
                </div>
            </div>
            <div class="history-actions">
                ${notification.unread ? '<button class="history-btn mark-read" onclick="markAsRead(' + notification.id + ')">Marquer lu</button>' : ''}
                <button class="history-btn delete" onclick="deleteNotification(${notification.id})">Supprimer</button>
                <button class="history-btn archive" onclick="archiveNotification(${notification.id})">Archiver</button>
            </div>
        </div>
    `).join('');
}

function getNotificationIcon(type) {
    const icons = {
        'success': 'check-circle',
        'error': 'times-circle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };
    return icons[type] || 'bell';
}

function setupFilterButtons() {
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            filterNotifications(this.dataset.filter);
        });
    });
}

function filterNotifications(filter) {
    const items = document.querySelectorAll('.history-item');
    items.forEach(item => {
        if (filter === 'all' || item.dataset.type === filter) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}

function markAsRead(id) {
    // Mark notification as read
    console.log('Marking notification as read:', id);
    // Update UI
    const item = document.querySelector(`[data-id="${id}"]`);
    if (item) {
        item.classList.remove('unread');
    }
}

function deleteNotification(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette notification ?')) {
        console.log('Deleting notification:', id);
        // Remove from UI
        const item = document.querySelector(`[data-id="${id}"]`);
        if (item) {
            item.remove();
        }
    }
}

function archiveNotification(id) {
    console.log('Archiving notification:', id);
    // Move to archive
}

function markAllAsRead() {
    document.querySelectorAll('.history-item.unread').forEach(item => {
        item.classList.remove('unread');
    });
    alert('Toutes les notifications ont été marquées comme lues');
}

function clearHistory() {
    if (confirm('Êtes-vous sûr de vouloir vider l\'historique des notifications ?')) {
        document.getElementById('notification-history-list').innerHTML = '';
        alert('Historique vidé');
    }
}

function exportHistory() {
    alert('Export de l\'historique en cours...');
    // Implement export functionality
}

function testAllNotifications() {
    alert('Test de toutes les notifications en cours...');
    // Implement test functionality
}

function editTemplate(templateId) {
    alert(`Modification du modèle ${templateId}`);
    // Implement template editing
}
</script>
@endsection
