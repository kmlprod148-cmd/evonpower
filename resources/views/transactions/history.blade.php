@extends('layouts.app')

@section('title', 'Historique des Transactions')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">
                        <i class="fas fa-history text-blue-600 mr-3"></i>
                        Historique des Transactions
                    </h1>
                    <p class="mt-2 text-gray-600">Consultez l'historique complet de vos transactions</p>
                </div>
                <div class="mt-4 sm:mt-0 flex flex-col sm:flex-row gap-3">
                    <button id="export-btn" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors">
                        <i class="fas fa-download mr-2"></i>
                        Exporter
                    </button>
                    <button id="refresh-btn" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                        <i class="fas fa-sync-alt mr-2"></i>
                        Actualiser
                    </button>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-wallet text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Solde Actuel</p>
                        <p class="text-2xl font-bold text-gray-900" id="current-balance">-</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-arrow-up text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Crédits</p>
                        <p class="text-2xl font-bold text-green-600" id="total-credits">-</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-arrow-down text-red-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Débits</p>
                        <p class="text-2xl font-bold text-red-600" id="total-debits">-</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-chart-line text-purple-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Transactions</p>
                        <p class="text-2xl font-bold text-gray-900" id="total-transactions">-</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="type-filter" class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                    <select id="type-filter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Tous les types</option>
                        <option value="credit">Crédits</option>
                        <option value="debit">Débits</option>
                    </select>
                </div>
                <div>
                    <label for="date-from" class="block text-sm font-medium text-gray-700 mb-2">Date de début</label>
                    <input type="date" id="date-from" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="date-to" class="block text-sm font-medium text-gray-700 mb-2">Date de fin</label>
                    <input type="date" id="date-to" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Recherche</label>
                    <input type="text" id="search" placeholder="Description, ID..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
            <div class="mt-4 flex justify-end">
                <button id="apply-filters" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                    <i class="fas fa-filter mr-2"></i>
                    Appliquer les filtres
                </button>
            </div>
        </div>

        <!-- Transactions List -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Transactions</h3>
            </div>
            
            <!-- Loading State -->
            <div id="loading-state" class="hidden p-8 text-center">
                <div class="inline-flex items-center">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mr-3"></div>
                    <span class="text-gray-600">Chargement des transactions...</span>
                </div>
            </div>

            <!-- Empty State -->
            <div id="empty-state" class="hidden p-8 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-receipt text-gray-400 text-2xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune transaction trouvée</h3>
                <p class="text-gray-500">Aucune transaction ne correspond à vos critères de recherche.</p>
            </div>

            <!-- Transactions Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Solde</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                    <tbody id="transactions-table-body" class="bg-white divide-y divide-gray-200">
                        <!-- Transactions will be loaded here -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
            <div id="pagination" class="px-6 py-4 border-t border-gray-200">
                <!-- Pagination will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Transaction Details Modal -->
<div id="transaction-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-receipt text-blue-600 mr-2"></i>
                    Détails de la Transaction
                </h3>
                <button id="close-modal" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Modal Content -->
            <div id="modal-content" class="mt-6">
                <!-- Content will be loaded here -->
            </div>

            <!-- Modal Footer -->
            <div class="flex justify-end pt-4 border-t border-gray-200 mt-6">
                <button id="close-modal-btn" class="px-6 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors">
                    Fermer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Export Modal -->
<div id="export-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between pb-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-download text-green-600 mr-2"></i>
                    Exporter les Transactions
                </h3>
                <button id="close-export-modal" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <div class="mt-6">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Format</label>
                        <select id="export-format" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                            <option value="csv">CSV</option>
                            <option value="pdf">PDF</option>
                            <option value="excel">Excel</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Période</label>
                        <select id="export-period" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                            <option value="all">Toutes les transactions</option>
                            <option value="last-30">30 derniers jours</option>
                            <option value="last-90">90 derniers jours</option>
                            <option value="custom">Période personnalisée</option>
                        </select>
                        </div>
                        </div>

                <div class="flex justify-end space-x-3 mt-6">
                    <button id="cancel-export" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors">
                        Annuler
                    </button>
                    <button id="confirm-export" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors">
                        <i class="fas fa-download mr-2"></i>
                        Exporter
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
let currentPage = 1;
let totalPages = 1;
let currentFilters = {};

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadTransactions();
    setupEventListeners();
    loadSummary();
});

// Setup event listeners
function setupEventListeners() {
    // Filter buttons
    document.getElementById('apply-filters').addEventListener('click', function() {
        currentPage = 1;
        loadTransactions();
    });

    // Export button
    document.getElementById('export-btn').addEventListener('click', function() {
        showExportModal();
    });

    // Refresh button
    document.getElementById('refresh-btn').addEventListener('click', function() {
        loadTransactions();
        loadSummary();
    });

    // Modal close buttons
    document.getElementById('close-modal').addEventListener('click', hideTransactionModal);
    document.getElementById('close-modal-btn').addEventListener('click', hideTransactionModal);
    document.getElementById('close-export-modal').addEventListener('click', hideExportModal);
    document.getElementById('cancel-export').addEventListener('click', hideExportModal);

    // Export confirm
    document.getElementById('confirm-export').addEventListener('click', exportTransactions);

    // Search on enter
    document.getElementById('search').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            currentPage = 1;
            loadTransactions();
        }
    });
}

// Load transactions
async function loadTransactions() {
    showLoading();
    
    try {
        const filters = getFilters();
        const response = await fetch(`/transactions/api?${new URLSearchParams(filters)}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            displayTransactions(data.data);
            displayPagination(data.pagination);
        } else {
            showError('Erreur lors du chargement des transactions');
        }
    } catch (error) {
        showError('Erreur de connexion: ' + error.message);
    } finally {
        hideLoading();
    }
}

// Get current filters
function getFilters() {
    return {
        type: document.getElementById('type-filter').value,
        date_from: document.getElementById('date-from').value,
        date_to: document.getElementById('date-to').value,
        search: document.getElementById('search').value,
        page: currentPage
    };
}

// Display transactions
function displayTransactions(transactions) {
    const tbody = document.getElementById('transactions-table-body');
    
    if (transactions.length === 0) {
        showEmptyState();
        return;
    }
    
    hideEmptyState();
    
    tbody.innerHTML = transactions.map(transaction => `
        <tr class="hover:bg-gray-50 transition-colors">
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center ${transaction.type === 'credit' ? 'bg-green-100' : 'bg-red-100'}">
                            <i class="fas ${transaction.type === 'credit' ? 'fa-arrow-up text-green-600' : 'fa-arrow-down text-red-600'}"></i>
                        </div>
                    </div>
                    <div class="ml-3">
                        <div class="text-sm font-medium text-gray-900">
                            ${transaction.type === 'credit' ? 'Crédit' : 'Débit'}
                        </div>
                        <div class="text-sm text-gray-500">
                            ${transaction.type === 'credit' ? 'Entrée' : 'Sortie'}
                        </div>
                    </div>
                </div>
            </td>
            <td class="px-6 py-4">
                <div class="text-sm text-gray-900">${transaction.description}</div>
                <div class="text-sm text-gray-500">ID: ${transaction.id}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium ${transaction.type === 'credit' ? 'text-green-600' : 'text-red-600'}">
                    ${transaction.type === 'credit' ? '+' : '-'}${formatAmount(transaction.amount)}
                </div>
                <div class="text-sm text-gray-500">${transaction.currency || 'EUR'}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">${formatAmount(transaction.current_balance)}</div>
                <div class="text-sm text-gray-500">Solde après</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">${formatDate(transaction.created_at)}</div>
                <div class="text-sm text-gray-500">${formatTime(transaction.created_at)}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <button onclick="showTransactionDetails(${transaction.id})" class="text-blue-600 hover:text-blue-900 transition-colors">
                    <i class="fas fa-eye mr-1"></i>
                    Voir détails
                </button>
            </td>
        </tr>
    `).join('');
}

// Display pagination
function displayPagination(pagination) {
    const container = document.getElementById('pagination');
    
    if (pagination.total_pages <= 1) {
        container.innerHTML = '';
        return;
    }
    
    let html = '<div class="flex items-center justify-between">';
    html += `<div class="text-sm text-gray-700">Page ${pagination.current_page} sur ${pagination.total_pages}</div>`;
    html += '<div class="flex space-x-2">';
    
    // Previous button
    if (pagination.current_page > 1) {
        html += `<button onclick="changePage(${pagination.current_page - 1})" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">Précédent</button>`;
    }
    
    // Page numbers
    for (let i = Math.max(1, pagination.current_page - 2); i <= Math.min(pagination.total_pages, pagination.current_page + 2); i++) {
        const activeClass = i === pagination.current_page ? 'bg-blue-600 text-white' : 'text-gray-500 bg-white border border-gray-300 hover:bg-gray-50';
        html += `<button onclick="changePage(${i})" class="px-3 py-2 text-sm font-medium rounded-md ${activeClass}">${i}</button>`;
    }
    
    // Next button
    if (pagination.current_page < pagination.total_pages) {
        html += `<button onclick="changePage(${pagination.current_page + 1})" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">Suivant</button>`;
    }
    
    html += '</div></div>';
    container.innerHTML = html;
}

// Change page
function changePage(page) {
    currentPage = page;
    loadTransactions();
}

// Show transaction details
async function showTransactionDetails(transactionId) {
    try {
        const response = await fetch(`/transactions/${transactionId}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            displayTransactionModal(data.data);
        } else {
            showError('Erreur lors du chargement des détails');
        }
    } catch (error) {
        showError('Erreur de connexion: ' + error.message);
    }
}

// Display transaction modal
function displayTransactionModal(transaction) {
    const modal = document.getElementById('transaction-modal');
    const content = document.getElementById('modal-content');
    
    content.innerHTML = `
        <div class="space-y-6">
            <!-- Transaction Header -->
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center ${transaction.type === 'credit' ? 'bg-green-100' : 'bg-red-100'}">
                        <i class="fas ${transaction.type === 'credit' ? 'fa-arrow-up text-green-600' : 'fa-arrow-down text-red-600'} text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <h4 class="text-lg font-semibold text-gray-900">${transaction.type === 'credit' ? 'Crédit' : 'Débit'}</h4>
                        <p class="text-sm text-gray-500">Transaction #${transaction.id}</p>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold ${transaction.type === 'credit' ? 'text-green-600' : 'text-red-600'}">
                        ${transaction.type === 'credit' ? '+' : '-'}${formatAmount(transaction.amount)}
                    </div>
                    <div class="text-sm text-gray-500">${transaction.currency || 'EUR'}</div>
                </div>
            </div>

            <!-- Transaction Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h5 class="text-sm font-medium text-gray-500 mb-3">Informations générales</h5>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Description:</span>
                            <span class="text-sm font-medium text-gray-900">${transaction.description}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Type:</span>
                            <span class="text-sm font-medium text-gray-900">${transaction.type === 'credit' ? 'Crédit' : 'Débit'}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Date:</span>
                            <span class="text-sm font-medium text-gray-900">${formatDate(transaction.created_at)}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Heure:</span>
                            <span class="text-sm font-medium text-gray-900">${formatTime(transaction.created_at)}</span>
                        </div>
                    </div>
                </div>

                <div>
                    <h5 class="text-sm font-medium text-gray-500 mb-3">Détails financiers</h5>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Montant:</span>
                            <span class="text-sm font-medium ${transaction.type === 'credit' ? 'text-green-600' : 'text-red-600'}">${formatAmount(transaction.amount)} ${transaction.currency || 'EUR'}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Solde avant:</span>
                            <span class="text-sm font-medium text-gray-900">${formatAmount(transaction.current_balance - transaction.amount)} ${transaction.currency || 'EUR'}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Solde après:</span>
                            <span class="text-sm font-medium text-gray-900">${formatAmount(transaction.current_balance)} ${transaction.currency || 'EUR'}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Statut:</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i>
                                Confirmé
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Metadata -->
            ${transaction.metadata ? `
            <div>
                <h5 class="text-sm font-medium text-gray-500 mb-3">Informations supplémentaires</h5>
                <div class="bg-gray-50 rounded-lg p-4">
                    <pre class="text-sm text-gray-700 whitespace-pre-wrap">${JSON.stringify(transaction.metadata, null, 2)}</pre>
                </div>
            </div>
            ` : ''}
        </div>
    `;
    
    modal.classList.remove('hidden');
}

// Show export modal
function showExportModal() {
    document.getElementById('export-modal').classList.remove('hidden');
}

// Hide export modal
function hideExportModal() {
    document.getElementById('export-modal').classList.add('hidden');
}

// Hide transaction modal
function hideTransactionModal() {
    document.getElementById('transaction-modal').classList.add('hidden');
}

// Export transactions
async function exportTransactions() {
    const format = document.getElementById('export-format').value;
    const period = document.getElementById('export-period').value;
    
    try {
        const url = `/transactions/export?format=${format}&period=${period}`;
        window.open(url, '_blank');
        hideExportModal();
    } catch (error) {
        showError('Erreur lors de l\'export: ' + error.message);
    }
}

// Load summary
async function loadSummary() {
    try {
        const response = await fetch('/transactions/summary', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('current-balance').textContent = formatAmount(data.data.current_balance);
            document.getElementById('total-credits').textContent = formatAmount(data.data.total_credits);
            document.getElementById('total-debits').textContent = formatAmount(data.data.total_debits);
            document.getElementById('total-transactions').textContent = data.data.total_transactions;
        }
    } catch (error) {
        console.error('Error loading summary:', error);
    }
}

// Utility functions
function formatAmount(amount) {
    return new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount);
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('fr-FR');
}

function formatTime(dateString) {
    return new Date(dateString).toLocaleTimeString('fr-FR');
}

function showLoading() {
    document.getElementById('loading-state').classList.remove('hidden');
    document.getElementById('transactions-table-body').innerHTML = '';
    document.getElementById('pagination').innerHTML = '';
}

function hideLoading() {
    document.getElementById('loading-state').classList.add('hidden');
}

function showEmptyState() {
    document.getElementById('empty-state').classList.remove('hidden');
}

function hideEmptyState() {
    document.getElementById('empty-state').classList.add('hidden');
}

function showError(message) {
    alert('Erreur: ' + message);
}
</script>
@endsection