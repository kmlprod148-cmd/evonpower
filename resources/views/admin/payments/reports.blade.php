@extends('layouts.admin')

@section('title', 'Rapports Avancés - Paiements')

@push('styles')
<style>
    .reports-container {
        padding: 2rem;
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        min-height: 100vh;
    }

    .reports-header {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        border-left: 6px solid #10b981;
    }

    .report-types {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
        margin-bottom: 3rem;
    }

    .report-card {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
        border: 2px solid transparent;
    }

    .report-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 40px rgba(0,0,0,0.15);
        border-color: #10b981;
    }

    .report-icon {
        width: 60px;
        height: 60px;
        border-radius: 16px;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1.5rem;
        font-size: 1.5rem;
        color: white;
    }

    .report-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.5rem;
    }

    .report-description {
        color: #64748b;
        font-size: 0.9rem;
        margin-bottom: 1.5rem;
    }

    .report-features {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .report-features li {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.25rem 0;
        color: #64748b;
        font-size: 0.9rem;
    }

    .report-features li::before {
        content: '✓';
        color: #10b981;
        font-weight: bold;
    }

    .report-actions {
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
    }

    .action-btn {
        flex: 1;
        padding: 0.75rem 1rem;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .action-btn.primary {
        background: #10b981;
        color: white;
    }

    .action-btn.primary:hover {
        background: #059669;
    }

    .action-btn.secondary {
        background: #f3f4f6;
        color: #374151;
    }

    .action-btn.secondary:hover {
        background: #e5e7eb;
    }

    .filters-section {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    }

    .filters-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
    }

    .filter-label {
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
    }

    .filter-input {
        padding: 0.75rem;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 0.9rem;
        transition: border-color 0.3s ease;
    }

    .filter-input:focus {
        outline: none;
        border-color: #10b981;
    }

    .scheduled-reports {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    }

    .scheduled-list {
        space-y: 1rem;
    }

    .scheduled-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem;
        border: 2px solid #f1f5f9;
        border-radius: 12px;
        transition: all 0.3s ease;
    }

    .scheduled-item:hover {
        border-color: #10b981;
        background: #f0fdf4;
    }

    .scheduled-info {
        flex: 1;
    }

    .scheduled-name {
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.25rem;
    }

    .scheduled-details {
        color: #64748b;
        font-size: 0.9rem;
    }

    .scheduled-actions {
        display: flex;
        gap: 0.5rem;
    }

    .scheduled-btn {
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 6px;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .scheduled-btn.edit {
        background: #3b82f6;
        color: white;
    }

    .scheduled-btn.delete {
        background: #ef4444;
        color: white;
    }

    .scheduled-btn.run {
        background: #10b981;
        color: white;
    }

    .export-history {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    }

    .history-list {
        space-y: 1rem;
    }

    .history-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem;
        border-bottom: 1px solid #f1f5f9;
    }

    .history-item:last-child {
        border-bottom: none;
    }

    .history-info {
        flex: 1;
    }

    .history-name {
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.25rem;
    }

    .history-details {
        color: #64748b;
        font-size: 0.9rem;
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

    .history-btn.download {
        background: #10b981;
        color: white;
    }

    .history-btn.delete {
        background: #ef4444;
        color: white;
    }

    @media (max-width: 768px) {
        .report-types {
            grid-template-columns: 1fr;
        }
        
        .filters-grid {
            grid-template-columns: 1fr;
        }
        
        .report-actions {
            flex-direction: column;
        }
    }
</style>
@endpush

@section('content')
<div class="reports-container">
    <!-- Header -->
    <div class="reports-header">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Rapports Avancés</h1>
                <p class="text-gray-600 mt-2">Générez et gérez des rapports détaillés sur les paiements</p>
            </div>
            <div class="flex gap-3">
                <button onclick="createScheduledReport()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i>
                    Planifier un Rapport
                </button>
                <button onclick="exportAllReports()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-download mr-2"></i>
                    Exporter Tout
                </button>
            </div>
        </div>
    </div>

    <!-- Report Types -->
    <div class="report-types">
        <div class="report-card" onclick="generateReport('revenue')">
            <div class="report-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <h3 class="report-title">Rapport de Revenus</h3>
            <p class="report-description">Analyse détaillée des revenus par période, méthode de paiement et tendances</p>
            <ul class="report-features">
                <li>Revenus par période</li>
                <li>Comparaison des méthodes</li>
                <li>Tendances et prévisions</li>
                <li>Analyse de croissance</li>
            </ul>
            <div class="report-actions">
                <button class="action-btn primary">Générer</button>
                <button class="action-btn secondary">Prévisualiser</button>
            </div>
        </div>

        <div class="report-card" onclick="generateReport('transactions')">
            <div class="report-icon">
                <i class="fas fa-list"></i>
            </div>
            <h3 class="report-title">Rapport des Transactions</h3>
            <p class="report-description">Liste détaillée de toutes les transactions avec filtres avancés</p>
            <ul class="report-features">
                <li>Liste complète des transactions</li>
                <li>Filtres par statut et méthode</li>
                <li>Export en multiple formats</li>
                <li>Recherche avancée</li>
            </ul>
            <div class="report-actions">
                <button class="action-btn primary">Générer</button>
                <button class="action-btn secondary">Prévisualiser</button>
            </div>
        </div>

        <div class="report-card" onclick="generateReport('methods')">
            <div class="report-icon">
                <i class="fas fa-credit-card"></i>
            </div>
            <h3 class="report-title">Performance des Méthodes</h3>
            <p class="report-description">Analyse comparative des performances des différentes méthodes de paiement</p>
            <ul class="report-features">
                <li>Comparaison CMI vs Stripe</li>
                <li>Taux de réussite par méthode</li>
                <li>Temps de traitement</li>
                <li>Recommandations</li>
            </ul>
            <div class="report-actions">
                <button class="action-btn primary">Générer</button>
                <button class="action-btn secondary">Prévisualiser</button>
            </div>
        </div>

        <div class="report-card" onclick="generateReport('failures')">
            <div class="report-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="report-title">Analyse des Échecs</h3>
            <p class="report-description">Rapport détaillé sur les échecs de paiement et leurs causes</p>
            <ul class="report-features">
                <li>Classification des échecs</li>
                <li>Analyse des causes</li>
                <li>Recommandations d'amélioration</li>
                <li>Tendances des échecs</li>
            </ul>
            <div class="report-actions">
                <button class="action-btn primary">Générer</button>
                <button class="action-btn secondary">Prévisualiser</button>
            </div>
        </div>

        <div class="report-card" onclick="generateReport('analytics')">
            <div class="report-icon">
                <i class="fas fa-chart-bar"></i>
            </div>
            <h3 class="report-title">Rapport Analytique</h3>
            <p class="report-description">Rapport complet avec analyses statistiques et insights</p>
            <ul class="report-features">
                <li>Métriques clés</li>
                <li>Analyses statistiques</li>
                <li>Insights et recommandations</li>
                <li>Graphiques et visualisations</li>
            </ul>
            <div class="report-actions">
                <button class="action-btn primary">Générer</button>
                <button class="action-btn secondary">Prévisualiser</button>
            </div>
        </div>

        <div class="report-card" onclick="generateReport('custom')">
            <div class="report-icon">
                <i class="fas fa-cog"></i>
            </div>
            <h3 class="report-title">Rapport Personnalisé</h3>
            <p class="report-description">Créez votre propre rapport avec des critères personnalisés</p>
            <ul class="report-features">
                <li>Critères personnalisés</li>
                <li>Filtres avancés</li>
                <li>Format de sortie flexible</li>
                <li>Sauvegarde des modèles</li>
            </ul>
            <div class="report-actions">
                <button class="action-btn primary">Créer</button>
                <button class="action-btn secondary">Modèles</button>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="filters-section">
        <h3 class="text-xl font-bold mb-4">Filtres de Rapport</h3>
        <div class="filters-grid">
            <div class="filter-group">
                <label class="filter-label">Période</label>
                <select id="reportPeriod" class="filter-input">
                    <option value="7d">7 derniers jours</option>
                    <option value="30d" selected>30 derniers jours</option>
                    <option value="90d">90 derniers jours</option>
                    <option value="1y">1 an</option>
                    <option value="custom">Période personnalisée</option>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">Méthode de Paiement</label>
                <select id="reportMethod" class="filter-input">
                    <option value="">Toutes les méthodes</option>
                    <option value="cmi">CMI</option>
                    <option value="stripe">Stripe</option>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">Statut</label>
                <select id="reportStatus" class="filter-input">
                    <option value="">Tous les statuts</option>
                    <option value="completed">Terminé</option>
                    <option value="failed">Échec</option>
                    <option value="pending">En attente</option>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">Format d'Export</label>
                <select id="exportFormat" class="filter-input">
                    <option value="csv">CSV</option>
                    <option value="xlsx">Excel</option>
                    <option value="pdf">PDF</option>
                    <option value="json">JSON</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Scheduled Reports -->
    <div class="scheduled-reports">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold">Rapports Planifiés</h3>
            <button onclick="createScheduledReport()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                <i class="fas fa-plus mr-2"></i>
                Nouveau
            </button>
        </div>
        <div class="scheduled-list" id="scheduled-reports-list">
            <!-- Scheduled reports will be loaded here -->
        </div>
    </div>

    <!-- Export History -->
    <div class="export-history">
        <h3 class="text-xl font-bold mb-4">Historique des Exports</h3>
        <div class="history-list" id="export-history-list">
            <!-- Export history will be loaded here -->
        </div>
    </div>
</div>

<!-- Scheduled Report Modal -->
<div id="scheduledReportModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md">
            <h3 class="text-xl font-bold mb-4">Planifier un Rapport</h3>
            <form id="scheduledReportForm">
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2">Nom du Rapport</label>
                    <input type="text" id="reportName" class="w-full p-3 border rounded-lg" placeholder="Nom du rapport">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2">Type de Rapport</label>
                    <select id="reportType" class="w-full p-3 border rounded-lg">
                        <option value="revenue">Revenus</option>
                        <option value="transactions">Transactions</option>
                        <option value="methods">Méthodes</option>
                        <option value="failures">Échecs</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2">Fréquence</label>
                    <select id="reportFrequency" class="w-full p-3 border rounded-lg">
                        <option value="daily">Quotidien</option>
                        <option value="weekly">Hebdomadaire</option>
                        <option value="monthly">Mensuel</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold mb-2">Email de Notification</label>
                    <input type="email" id="reportEmail" class="w-full p-3 border rounded-lg" placeholder="email@example.com">
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-green-600 text-white py-3 rounded-lg hover:bg-green-700">
                        Planifier
                    </button>
                    <button type="button" onclick="closeScheduledModal()" class="flex-1 bg-gray-300 text-gray-700 py-3 rounded-lg hover:bg-gray-400">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadScheduledReports();
    loadExportHistory();
});

function generateReport(type) {
    const filters = {
        period: document.getElementById('reportPeriod').value,
        method: document.getElementById('reportMethod').value,
        status: document.getElementById('reportStatus').value,
        format: document.getElementById('exportFormat').value
    };
    
    // Show loading state
    showLoading(true);
    
    // Generate report
    fetch(`/admin/payments/analytics/export?type=${type}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(filters)
    })
    .then(response => {
        if (response.ok) {
            return response.blob();
        }
        throw new Error('Erreur lors de la génération du rapport');
    })
    .then(blob => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `${type}_report_${new Date().toISOString().split('T')[0]}.${filters.format}`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Erreur lors de la génération du rapport');
    })
    .finally(() => {
        showLoading(false);
    });
}

function createScheduledReport() {
    document.getElementById('scheduledReportModal').classList.remove('hidden');
}

function closeScheduledModal() {
    document.getElementById('scheduledReportModal').classList.add('hidden');
}

function exportAllReports() {
    alert('Export de tous les rapports en cours...');
    // Implement full export
}

function loadScheduledReports() {
    // Simulate loading scheduled reports
    const scheduledReports = [
        {
            id: 1,
            name: 'Rapport Quotidien Revenus',
            type: 'revenue',
            frequency: 'daily',
            nextRun: '2024-01-15 09:00',
            status: 'active'
        },
        {
            id: 2,
            name: 'Rapport Hebdomadaire Transactions',
            type: 'transactions',
            frequency: 'weekly',
            nextRun: '2024-01-21 08:00',
            status: 'active'
        }
    ];

    const container = document.getElementById('scheduled-reports-list');
    container.innerHTML = scheduledReports.map(report => `
        <div class="scheduled-item">
            <div class="scheduled-info">
                <div class="scheduled-name">${report.name}</div>
                <div class="scheduled-details">
                    ${report.frequency} • Prochaine exécution: ${report.nextRun}
                </div>
            </div>
            <div class="scheduled-actions">
                <button class="scheduled-btn edit" onclick="editScheduledReport(${report.id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="scheduled-btn run" onclick="runScheduledReport(${report.id})">
                    <i class="fas fa-play"></i>
                </button>
                <button class="scheduled-btn delete" onclick="deleteScheduledReport(${report.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `).join('');
}

function loadExportHistory() {
    // Simulate loading export history
    const history = [
        {
            id: 1,
            name: 'Rapport Revenus 2024-01-14',
            type: 'revenue',
            size: '2.3 MB',
            date: '2024-01-14 14:30',
            status: 'completed'
        },
        {
            id: 2,
            name: 'Transactions Q4 2023',
            type: 'transactions',
            size: '5.7 MB',
            date: '2024-01-13 10:15',
            status: 'completed'
        }
    ];

    const container = document.getElementById('export-history-list');
    container.innerHTML = history.map(item => `
        <div class="history-item">
            <div class="history-info">
                <div class="history-name">${item.name}</div>
                <div class="history-details">
                    ${item.type} • ${item.size} • ${item.date}
                </div>
            </div>
            <div class="history-actions">
                <button class="history-btn download" onclick="downloadReport(${item.id})">
                    <i class="fas fa-download"></i> Télécharger
                </button>
                <button class="history-btn delete" onclick="deleteReport(${item.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `).join('');
}

function editScheduledReport(id) {
    alert(`Modification du rapport planifié ${id}`);
}

function runScheduledReport(id) {
    alert(`Exécution du rapport planifié ${id}`);
}

function deleteScheduledReport(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce rapport planifié ?')) {
        alert(`Suppression du rapport planifié ${id}`);
    }
}

function downloadReport(id) {
    alert(`Téléchargement du rapport ${id}`);
}

function deleteReport(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce rapport ?')) {
        alert(`Suppression du rapport ${id}`);
    }
}

function showLoading(show) {
    // Implement loading state
    if (show) {
        document.body.style.cursor = 'wait';
    } else {
        document.body.style.cursor = 'default';
    }
}

// Scheduled report form submission
document.getElementById('scheduledReportForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = {
        name: document.getElementById('reportName').value,
        type: document.getElementById('reportType').value,
        frequency: document.getElementById('reportFrequency').value,
        email: document.getElementById('reportEmail').value
    };
    
    // Submit scheduled report
    fetch('/admin/payments/analytics/schedule', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeScheduledModal();
            loadScheduledReports();
            alert('Rapport planifié avec succès !');
        } else {
            alert('Erreur lors de la planification du rapport');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Erreur lors de la planification du rapport');
    });
});
</script>
@endsection
