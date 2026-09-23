@extends('layouts.app')

@section('title', 'Monitoring API')

@push('styles')
<style>
    .mon-content { padding-bottom: 2rem; }
    .mon-header { 
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); 
        border-radius: 12px; 
        padding: 16px 20px; 
        margin-bottom: 16px; 
        color: white;
    }
    .mon-header-title { font-size: 1.25rem; font-weight: 700; display: flex; align-items: center; gap: 8px; }
    .mon-header-sub { font-size: 0.75rem; opacity: 0.9; margin-top: 2px; }
    .mon-header-actions { display: flex; gap: 8px; flex-wrap: wrap; }
    .mon-header-btn {
        background: rgba(255,255,255,0.2);
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 4px;
        transition: background 0.2s;
        text-decoration: none;
        color: white;
        border: none;
        cursor: pointer;
    }
    .mon-header-btn:hover { background: rgba(255,255,255,0.3); }
    
    .mon-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 16px; }
    @media (max-width: 1024px) { .mon-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 640px) { .mon-grid { grid-template-columns: 1fr; } }
    
    .mon-card {
        background: white;
        border-radius: 8px;
        padding: 12px 14px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        border: 1px solid #e5e7eb;
    }
    .dark .mon-card { background: #1f2937; border-color: #374151; }
    
    .mon-card-header { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
    .mon-card-icon {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .mon-card-icon i { font-size: 14px; }
    .mon-card-title { font-size: 0.7rem; color: #6b7280; text-transform: uppercase; font-weight: 500; }
    .dark .mon-card-title { color: #9ca3af; }
    .mon-card-value { font-size: 1.5rem; font-weight: 700; color: #111827; }
    .dark .mon-card-value { color: #f9fafb; }
    
    .mon-filters {
        background: white;
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        border: 1px solid #e5e7eb;
    }
    .dark .mon-filters { background: #1f2937; border-color: #374151; }
    .mon-filters-grid { display: flex; flex-wrap: wrap; gap: 8px; align-items: flex-end; }
    .mon-filter-group { display: flex; flex-direction: column; min-width: 120px; }
    .mon-filter-label { font-size: 0.65rem; color: #6b7280; font-weight: 500; margin-bottom: 3px; }
    .dark .mon-filter-label { color: #9ca3af; }
    .mon-filter-input {
        padding: 6px 10px;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        font-size: 0.75rem;
        background: white;
        color: #111827;
    }
    .dark .mon-filter-input { background: #111827; border-color: #374151; color: #f9fafb; }
    .mon-filter-btn {
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 0.7rem;
        font-weight: 500;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .mon-filter-btn-primary { background: #6366f1; color: white; }
    
    .mon-charts { display: grid; grid-template-columns: 2fr 1fr; gap: 12px; margin-bottom: 16px; }
    @media (max-width: 1024px) { .mon-charts { grid-template-columns: 1fr; } }
    
    .mon-chart-card {
        background: white;
        border-radius: 10px;
        padding: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        border: 1px solid #e5e7eb;
    }
    .dark .mon-chart-card { background: #1f2937; border-color: #374151; }
    .mon-chart-title { font-size: 0.8rem; font-weight: 600; color: #111827; margin-bottom: 10px; display: flex; align-items: center; gap: 6px; }
    .dark .mon-chart-title { color: #f9fafb; }
    
    .mon-lists { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 16px; }
    @media (max-width: 768px) { .mon-lists { grid-template-columns: 1fr; } }
    
    .mon-list-card {
        background: white;
        border-radius: 10px;
        padding: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        border: 1px solid #e5e7eb;
    }
    .dark .mon-list-card { background: #1f2937; border-color: #374151; }
    .mon-list-title { font-size: 0.8rem; font-weight: 600; color: #111827; margin-bottom: 10px; display: flex; align-items: center; gap: 6px; }
    .dark .mon-list-title { color: #f9fafb; }
    
    .mon-health-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px;
        background: #f9fafb;
        border-radius: 8px;
        margin-bottom: 8px;
    }
    .dark .mon-health-item { background: #111827; }
    .mon-health-icon { font-size: 1.5rem; }
    .mon-health-name { font-size: 0.8rem; font-weight: 600; color: #111827; }
    .dark .mon-health-name { color: #f9fafb; }
    .mon-health-status { font-size: 0.65rem; color: #6b7280; }
    .dark .mon-health-status { color: #9ca3af; }
    
    .mon-endpoint-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px;
        background: #f9fafb;
        border-radius: 6px;
        margin-bottom: 6px;
        font-size: 0.75rem;
    }
    .dark .mon-endpoint-item { background: #111827; }
    
    .mon-table-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        border: 1px solid #e5e7eb;
        overflow: hidden;
        margin-bottom: 0;
    }
    .dark .mon-table-card { background: #1f2937; border-color: #374151; }
    .mon-table-header { padding: 10px 14px; border-bottom: 1px solid #e5e7eb; }
    .dark .mon-table-header { border-color: #374151; }
    .mon-table-title { font-size: 0.85rem; font-weight: 600; color: #111827; display: flex; align-items: center; gap: 6px; }
    .dark .mon-table-title { color: #f9fafb; }
    
    .mon-table { width: 100%; border-collapse: collapse; font-size: 0.7rem; }
    .mon-table th, .mon-table td { padding: 8px 10px; text-align: left; border-bottom: 1px solid #f3f4f6; }
    .dark .mon-table th, .dark .mon-table td { border-color: #374151; }
    .mon-table th { background: #f9fafb; color: #6b7280; font-weight: 600; font-size: 0.6rem; text-transform: uppercase; }
    .dark .mon-table th { background: #111827; color: #9ca3af; }
    .mon-table td { color: #374151; }
    .dark .mon-table td { color: #d1d5db; }
    
    .mon-badge {
        display: inline-flex;
        align-items: center;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.6rem;
        font-weight: 600;
    }
    .mon-badge-success { background: #d1fae5; color: #065f46; }
    .mon-badge-warning { background: #fef3c7; color: #92400e; }
    .mon-badge-danger { background: #fee2e2; color: #991b1b; }
    .mon-badge-info { background: #dbeafe; color: #1e40af; }
    .mon-badge-secondary { background: #e5e7eb; color: #374151; }
    .dark .mon-badge-success { background: rgba(16, 185, 129, 0.2); color: #6ee7b7; }
    .dark .mon-badge-warning { background: rgba(245, 158, 11, 0.2); color: #fcd34d; }
    .dark .mon-badge-danger { background: rgba(239, 68, 68, 0.2); color: #fca5a5; }
    .dark .mon-badge-info { background: rgba(59, 130, 246, 0.2); color: #93c5fd; }
    
    .mon-alert { padding: 8px 12px; border-radius: 6px; margin-bottom: 6px; font-size: 0.75rem; display: flex; align-items: center; gap: 6px; }
    .mon-alert-warning { background: #fef3c7; color: #92400e; }
    .mon-alert-danger { background: #fee2e2; color: #991b1b; }
    .mon-alert-success { background: #d1fae5; color: #065f46; }
    .dark .mon-alert-warning { background: rgba(245, 158, 11, 0.2); color: #fcd34d; }
    .dark .mon-alert-danger { background: rgba(239, 68, 68, 0.2); color: #fca5a5; }
    .dark .mon-alert-success { background: rgba(16, 185, 129, 0.2); color: #6ee7b7; }
    
    .mon-empty { text-align: center; padding: 20px; color: #6b7280; font-size: 0.75rem; }
    .dark .mon-empty { color: #9ca3af; }
    
    #loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
    }
    .mon-spinner {
        width: 40px;
        height: 40px;
        border: 3px solid #f3f3f3;
        border-top: 3px solid #6366f1;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
</style>
@endpush

@section('content')
<div class="mon-content p-4">
    
    <!-- Compact Header -->
    <div class="mon-header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
                <h1 class="mon-header-title">
                    <i class="fas fa-chart-line"></i>
                    {{ __('Monitoring API') }}
                </h1>
                <p class="mon-header-sub">{{ __('Surveillance des performances des API Evon et Steve') }}</p>
            </div>
            <div class="mon-header-actions">
                <button id="refresh-btn" class="mon-header-btn">
                    <i class="fas fa-sync-alt"></i> {{ __('Actualiser') }}
                </button>
                <button class="mon-header-btn" onclick="exportData('json')">
                    <i class="fas fa-download"></i> JSON
                </button>
                <button class="mon-header-btn" onclick="exportData('csv')">
                    <i class="fas fa-file-csv"></i> CSV
                </button>
            </div>
        </div>
    </div>

    <!-- Filters (Compact) -->
    <div class="mon-filters">
        <div class="mon-filters-grid">
            <div class="mon-filter-group">
                <label class="mon-filter-label">{{ __('API') }}</label>
                <select id="api-filter" class="mon-filter-input">
                    <option value="">{{ __('Toutes') }}</option>
                    <option value="evon" {{ ($apiName ?? '') === 'evon' ? 'selected' : '' }}>Evon API</option>
                    <option value="steve" {{ ($apiName ?? '') === 'steve' ? 'selected' : '' }}>Steve API</option>
                </select>
            </div>
            <div class="mon-filter-group">
                <label class="mon-filter-label">{{ __('Période') }}</label>
                <select id="time-filter" class="mon-filter-input">
                    <option value="5" {{ ($minutes ?? 15) == 5 ? 'selected' : '' }}>5 min</option>
                    <option value="15" {{ ($minutes ?? 15) == 15 ? 'selected' : '' }}>15 min</option>
                    <option value="30" {{ ($minutes ?? 15) == 30 ? 'selected' : '' }}>30 min</option>
                    <option value="60" {{ ($minutes ?? 15) == 60 ? 'selected' : '' }}>1 heure</option>
                    <option value="240" {{ ($minutes ?? 15) == 240 ? 'selected' : '' }}>4 heures</option>
                    <option value="1440" {{ ($minutes ?? 15) == 1440 ? 'selected' : '' }}>24 heures</option>
                </select>
            </div>
            <div class="mon-filter-group">
                <label class="mon-filter-label">{{ __('Auto-refresh') }}</label>
                <div style="display: flex; align-items: center; gap: 6px; height: 30px;">
                    <input type="checkbox" id="auto-refresh" checked style="width: 16px; height: 16px;">
                    <span id="refresh-status" style="font-size: 0.7rem; color: #6b7280;">30s</span>
                </div>
            </div>
            <button id="apply-filters" class="mon-filter-btn mon-filter-btn-primary" style="align-self: flex-end;">
                <i class="fas fa-filter"></i> {{ __('Appliquer') }}
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="mon-grid">
        <div class="mon-card">
            <div class="mon-card-header">
                <div class="mon-card-icon" style="background: #dbeafe; color: #2563eb;">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <span class="mon-card-title">{{ __('Requêtes totales') }}</span>
            </div>
            <div class="mon-card-value" id="total-requests">-</div>
        </div>
        
        <div class="mon-card">
            <div class="mon-card-header">
                <div class="mon-card-icon" style="background: #d1fae5; color: #059669;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <span class="mon-card-title">{{ __('Taux de succès') }}</span>
            </div>
            <div class="mon-card-value" id="success-rate" style="color: #059669;">-</div>
        </div>
        
        <div class="mon-card">
            <div class="mon-card-header">
                <div class="mon-card-icon" style="background: #e0e7ff; color: #4f46e5;">
                    <i class="fas fa-clock"></i>
                </div>
                <span class="mon-card-title">{{ __('Temps moyen') }}</span>
            </div>
            <div class="mon-card-value" id="avg-response-time" style="color: #4f46e5;">-</div>
        </div>
        
        <div class="mon-card">
            <div class="mon-card-header">
                <div class="mon-card-icon" style="background: #fee2e2; color: #dc2626;">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <span class="mon-card-title">{{ __('Échecs') }}</span>
            </div>
            <div class="mon-card-value" id="failed-requests" style="color: #dc2626;">-</div>
        </div>
    </div>

    <!-- Health Status -->
    <div class="mon-list-card" style="margin-bottom: 16px;">
        <div class="mon-list-title"><i class="fas fa-heartbeat" style="color: #ef4444;"></i> {{ __('Statut de santé des API') }}</div>
        <div id="health-status" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 10px;">
            <div class="mon-empty">{{ __('Chargement...') }}</div>
        </div>
    </div>

    <!-- Charts -->
    <div class="mon-charts">
        <div class="mon-chart-card">
            <div class="mon-chart-title"><i class="fas fa-chart-line" style="color: #6366f1;"></i> {{ __('Performance') }}</div>
            <div style="position: relative; height: 220px;">
                <canvas id="performance-chart"></canvas>
            </div>
        </div>
        <div class="mon-chart-card">
            <div class="mon-chart-title"><i class="fas fa-chart-pie" style="color: #10b981;"></i> {{ __('Codes de statut') }}</div>
            <div style="position: relative; height: 220px;">
                <canvas id="status-codes-chart"></canvas>
            </div>
        </div>
    </div>

    <!-- Endpoints & Alerts -->
    <div class="mon-lists">
        <div class="mon-list-card">
            <div class="mon-list-title"><i class="fas fa-list" style="color: #3b82f6;"></i> {{ __('Endpoints populaires') }}</div>
            <div id="top-endpoints">
                <div class="mon-empty">{{ __('Chargement...') }}</div>
            </div>
        </div>
        <div class="mon-list-card">
            <div class="mon-list-title"><i class="fas fa-bell" style="color: #f59e0b;"></i> {{ __('Alertes') }}</div>
            <div id="alerts">
                <div class="mon-empty">{{ __('Chargement...') }}</div>
            </div>
        </div>
    </div>

    <!-- Recent Requests Table -->
    <div class="mon-table-card">
        <div class="mon-table-header">
            <span class="mon-table-title"><i class="fas fa-history" style="color: #6b7280;"></i> {{ __('Requêtes récentes') }}</span>
        </div>
        <div style="overflow-x: auto;">
            <table class="mon-table">
                <thead>
                    <tr>
                        <th>API</th>
                        <th>Endpoint</th>
                        <th>{{ __('Méthode') }}</th>
                        <th>{{ __('Statut') }}</th>
                        <th>{{ __('Temps') }}</th>
                        <th>{{ __('Succès') }}</th>
                        <th>{{ __('Date') }}</th>
                    </tr>
                </thead>
                <tbody id="recent-requests">
                    <tr><td colspan="7" class="mon-empty">{{ __('Chargement...') }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Loading overlay -->
<div id="loading-overlay">
    <div class="mon-spinner"></div>
</div>

@push('scripts')
<script src="{{ asset('vendor/chartjs/chart.min.js') }}"></script>
<script>
let performanceChart, statusCodesChart;
let autoRefreshInterval;
let isAutoRefreshEnabled = true;

document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
    loadDashboardData();
    setupEventListeners();
    startAutoRefresh();
});

function setupEventListeners() {
    document.getElementById('refresh-btn').addEventListener('click', loadDashboardData);
    document.getElementById('apply-filters').addEventListener('click', loadDashboardData);
    document.getElementById('auto-refresh').addEventListener('change', function() {
        isAutoRefreshEnabled = this.checked;
        if (isAutoRefreshEnabled) {
            startAutoRefresh();
        } else {
            stopAutoRefresh();
        }
    });
}

async function loadDashboardData() {
    showLoading();
    
    try {
        const apiName = document.getElementById('api-filter').value;
        const minutes = document.getElementById('time-filter').value;
        
        const response = await fetch(`{{ route('admin.monitoring.dashboard-data') }}?api=${apiName}&minutes=${minutes}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            updateDashboard(data.data);
        } else {
            showNoData();
        }
    } catch (error) {
        console.error('Error:', error);
        showNoData();
    } finally {
        hideLoading();
    }
}

function updateDashboard(data) {
    // Main metrics
    document.getElementById('total-requests').textContent = data.dashboard?.stats?.total_requests || 0;
    document.getElementById('success-rate').textContent = (data.dashboard?.stats?.success_rate || 0) + '%';
    document.getElementById('avg-response-time').textContent = Math.round(data.dashboard?.stats?.average_response_time || 0) + 'ms';
    document.getElementById('failed-requests').textContent = data.dashboard?.stats?.failed_requests || 0;
    
    // Health status
    if (data.health_status) updateHealthStatus(data.health_status);
    
    // Charts
    if (data.dashboard?.performance_data) updatePerformanceChart(data.dashboard.performance_data);
    if (data.dashboard?.status_codes) updateStatusCodesChart(data.dashboard.status_codes);
    
    // Lists
    if (data.dashboard?.top_endpoints) updateTopEndpoints(data.dashboard.top_endpoints);
    if (data.dashboard?.alerts) updateAlerts(data.dashboard.alerts);
    if (data.recent_requests) updateRecentRequests(data.recent_requests);
}

function showNoData() {
    document.getElementById('total-requests').textContent = '0';
    document.getElementById('success-rate').textContent = '0%';
    document.getElementById('avg-response-time').textContent = '0ms';
    document.getElementById('failed-requests').textContent = '0';
    document.getElementById('health-status').innerHTML = '<div class="mon-alert mon-alert-warning"><i class="fas fa-info-circle"></i> Aucune donnée disponible</div>';
    document.getElementById('top-endpoints').innerHTML = '<div class="mon-empty">Aucune donnée</div>';
    document.getElementById('alerts').innerHTML = '<div class="mon-alert mon-alert-success"><i class="fas fa-check-circle"></i> Aucune alerte</div>';
    document.getElementById('recent-requests').innerHTML = '<tr><td colspan="7" class="mon-empty">Aucune requête récente</td></tr>';
}

function updateHealthStatus(healthStatus) {
    const container = document.getElementById('health-status');
    container.innerHTML = '';
    
    if (!healthStatus || Object.keys(healthStatus).length === 0) {
        container.innerHTML = '<div class="mon-alert mon-alert-warning"><i class="fas fa-info-circle"></i> Aucune donnée de santé</div>';
        return;
    }
    
    Object.entries(healthStatus).forEach(([api, status]) => {
        const iconColor = status.status === 'excellent' || status.status === 'good' ? '#10b981' : 
                         status.status === 'warning' ? '#f59e0b' : '#ef4444';
        const iconClass = status.status === 'excellent' || status.status === 'good' ? 'fa-check-circle' : 
                         status.status === 'warning' ? 'fa-exclamation-triangle' : 'fa-times-circle';
        
        container.innerHTML += `
            <div class="mon-health-item">
                <i class="fas ${iconClass} mon-health-icon" style="color: ${iconColor};"></i>
                <div>
                    <div class="mon-health-name">${status.name || api}</div>
                    <div class="mon-health-status">${(status.status || 'unknown').toUpperCase()}</div>
                </div>
            </div>
        `;
    });
}

function updatePerformanceChart(data) {
    if (!performanceChart || !data) return;
    
    performanceChart.data.labels = data.map(item => item.time);
    performanceChart.data.datasets[0].data = data.map(item => item.requests);
    performanceChart.data.datasets[1].data = data.map(item => item.successful);
    performanceChart.data.datasets[2].data = data.map(item => item.failed);
    performanceChart.update();
}

function updateStatusCodesChart(data) {
    if (!statusCodesChart || !data) return;
    
    statusCodesChart.data.labels = data.map(item => item.status_code);
    statusCodesChart.data.datasets[0].data = data.map(item => item.count);
    statusCodesChart.update();
}

function updateTopEndpoints(endpoints) {
    const container = document.getElementById('top-endpoints');
    
    if (!endpoints || endpoints.length === 0) {
        container.innerHTML = '<div class="mon-empty">Aucun endpoint</div>';
        return;
    }
    
    container.innerHTML = endpoints.slice(0, 5).map(ep => `
        <div class="mon-endpoint-item">
            <div>
                <strong style="color: #111827;">${ep.endpoint || 'N/A'}</strong>
                <div style="font-size: 0.65rem; color: #6b7280;">${ep.request_count || 0} requêtes</div>
            </div>
            <div style="text-align: right;">
                <span class="mon-badge mon-badge-info">${Math.round(ep.avg_response_time || 0)}ms</span>
            </div>
        </div>
    `).join('');
}

function updateAlerts(alerts) {
    const container = document.getElementById('alerts');
    
    if (!alerts || alerts.length === 0) {
        container.innerHTML = '<div class="mon-alert mon-alert-success"><i class="fas fa-check-circle"></i> Aucune alerte</div>';
        return;
    }
    
    container.innerHTML = alerts.slice(0, 5).map(alert => `
        <div class="mon-alert ${alert.type === 'error' ? 'mon-alert-danger' : 'mon-alert-warning'}">
            <i class="fas fa-exclamation-triangle"></i>
            ${alert.message}
        </div>
    `).join('');
}

function updateRecentRequests(requests) {
    const tbody = document.getElementById('recent-requests');
    
    if (!requests || requests.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="mon-empty">Aucune requête récente</td></tr>';
        return;
    }
    
    tbody.innerHTML = requests.slice(0, 10).map(req => {
        const statusClass = req.status_code >= 200 && req.status_code < 300 ? 'mon-badge-success' :
                           req.status_code >= 400 ? 'mon-badge-danger' : 'mon-badge-warning';
        const successIcon = req.success ? '<i class="fas fa-check-circle" style="color: #10b981;"></i>' : 
                           '<i class="fas fa-times-circle" style="color: #ef4444;"></i>';
        
        return `
            <tr>
                <td><span class="mon-badge mon-badge-secondary">${(req.api_name || 'N/A').toUpperCase()}</span></td>
                <td><code style="font-size: 0.65rem;">${req.endpoint || 'N/A'}</code></td>
                <td><span class="mon-badge mon-badge-info">${req.method || 'GET'}</span></td>
                <td><span class="mon-badge ${statusClass}">${req.status_code || 0}</span></td>
                <td>${req.response_time_ms || 0}ms</td>
                <td>${successIcon}</td>
                <td style="font-size: 0.65rem;">${req.requested_at ? new Date(req.requested_at).toLocaleString() : '-'}</td>
            </tr>
        `;
    }).join('');
}

function initializeCharts() {
    const perfCtx = document.getElementById('performance-chart')?.getContext('2d');
    if (perfCtx) {
        performanceChart = new Chart(perfCtx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    { label: 'Total', data: [], borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,0.1)', tension: 0.3, fill: true },
                    { label: 'Succès', data: [], borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.1)', tension: 0.3 },
                    { label: 'Échecs', data: [], borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.1)', tension: 0.3 }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { font: { size: 10 } } } }, scales: { y: { beginAtZero: true, ticks: { font: { size: 9 } } }, x: { ticks: { font: { size: 9 } } } } }
        });
    }
    
    const statusCtx = document.getElementById('status-codes-chart')?.getContext('2d');
    if (statusCtx) {
        statusCodesChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: [],
                datasets: [{ data: [], backgroundColor: ['#10b981', '#f59e0b', '#ef4444', '#6b7280'] }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { font: { size: 10 } } } } }
        });
    }
}

function startAutoRefresh() {
    if (autoRefreshInterval) clearInterval(autoRefreshInterval);
    autoRefreshInterval = setInterval(() => { if (isAutoRefreshEnabled) loadDashboardData(); }, 30000);
    document.getElementById('refresh-status').textContent = '30s';
}

function stopAutoRefresh() {
    if (autoRefreshInterval) { clearInterval(autoRefreshInterval); autoRefreshInterval = null; }
    document.getElementById('refresh-status').textContent = 'Off';
}

function showLoading() { document.getElementById('loading-overlay').style.display = 'flex'; }
function hideLoading() { document.getElementById('loading-overlay').style.display = 'none'; }

function exportData(format) {
    const api = document.getElementById('api-filter').value;
    const minutes = document.getElementById('time-filter').value;
    window.open(`{{ route('admin.monitoring.export') }}?api=${api}&minutes=${minutes}&format=${format}`, '_blank');
}
</script>
@endpush
@endsection
