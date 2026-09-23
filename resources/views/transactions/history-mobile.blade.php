@extends('layouts.app')

@section('title', 'Historique des Transactions')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Mobile Header -->
    <div class="bg-white shadow-sm border-b border-gray-200 px-4 py-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <button onclick="history.back()" class="mr-3 p-2 -ml-2 text-gray-600 hover:text-gray-900">
                    <i class="fas fa-arrow-left text-lg"></i>
                </button>
                <div>
                    <h1 class="text-lg font-semibold text-gray-900">Transactions</h1>
                    <p class="text-sm text-gray-500">Historique complet</p>
                </div>
            </div>
            <button id="mobile-filter-btn" class="p-2 text-gray-600 hover:text-gray-900">
                <i class="fas fa-filter text-lg"></i>
            </button>
        </div>
    </div>

    <!-- Summary Cards - Mobile -->
    <div class="px-4 py-4">
        <div class="grid grid-cols-2 gap-3 mb-4">
            <div class="bg-white rounded-lg p-4 shadow-sm">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-wallet text-blue-600"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Solde</p>
                        <p class="text-sm font-semibold text-gray-900" id="mobile-current-balance">-</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg p-4 shadow-sm">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-arrow-up text-green-600"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Crédits</p>
                        <p class="text-sm font-semibold text-green-600" id="mobile-total-credits">-</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-2 gap-3">
            <div class="bg-white rounded-lg p-4 shadow-sm">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-arrow-down text-red-600"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Débits</p>
                        <p class="text-sm font-semibold text-red-600" id="mobile-total-debits">-</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg p-4 shadow-sm">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-chart-line text-purple-600"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Total</p>
                        <p class="text-sm font-semibold text-gray-900" id="mobile-total-transactions">-</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Filters -->
    <div id="mobile-filters" class="hidden bg-white border-b border-gray-200 px-4 py-4">
        <div class="space-y-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                <select id="mobile-type-filter" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Tous</option>
                    <option value="credit">Crédits</option>
                    <option value="debit">Débits</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Début</label>
                    <input type="date" id="mobile-date-from" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fin</label>
                    <input type="date" id="mobile-date-to" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Recherche</label>
                <input type="text" id="mobile-search" placeholder="Description..." class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div class="flex space-x-2">
                <button id="mobile-apply-filters" class="flex-1 px-4 py-2 bg-blue-600 text-green-600 rounded-lg text-sm font-medium">
                    Appliquer
                </button>
                <button id="mobile-clear-filters" class="flex-1 px-4 py-2 bg-gray-600 text-green-600 rounded-lg text-sm font-medium">
                    Effacer
                </button>
            </div>
        </div>
    </div>

    <!-- Transactions List - Mobile -->
    <div class="px-4 pb-20">
        <!-- Loading State -->
        <div id="mobile-loading-state" class="hidden text-center py-8">
            <div class="inline-flex items-center">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600 mr-2"></div>
                <span class="text-gray-600 text-sm">Chargement...</span>
            </div>
        </div>

        <!-- Empty State -->
        <div id="mobile-empty-state" class="hidden text-center py-8">
            <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-receipt text-gray-400"></i>
            </div>
            <h3 class="text-sm font-medium text-gray-900 mb-1">Aucune transaction</h3>
            <p class="text-xs text-gray-500">Aucune transaction trouvée</p>
        </div>

        <!-- Transactions List -->
        <div id="mobile-transactions-list">
            <!-- Transactions will be loaded here -->
        </div>

        <!-- Load More Button -->
        <div id="mobile-load-more" class="hidden text-center py-4">
            <button id="mobile-load-more-btn" class="px-6 py-2 bg-blue-600 text-green-600 rounded-lg text-sm font-medium">
                Charger plus
            </button>
        </div>
    </div>

    <!-- Floating Action Button -->
    <div class="fixed bottom-4 right-4 z-50">
        <button id="mobile-export-btn" class="w-14 h-14 bg-green-600 text-green-600 rounded-full shadow-lg flex items-center justify-center">
            <i class="fas fa-download text-lg"></i>
        </button>
    </div>
</div>

<!-- Transaction Details Modal - Mobile -->
<div id="mobile-transaction-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-4 mx-auto p-4 border w-11/12 shadow-lg rounded-lg bg-white">
        <div class="flex items-center justify-between pb-3 border-b border-gray-200 mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Détails</h3>
            <button id="mobile-close-modal" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <div id="mobile-modal-content" class="space-y-4">
            <!-- Content will be loaded here -->
        </div>

        <div class="flex justify-end pt-4 border-t border-gray-200 mt-4">
            <button id="mobile-close-modal-btn" class="px-4 py-2 bg-gray-600 text-green-600 rounded-lg text-sm font-medium">
                Fermer
            </button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
let currentPage = 1;
let totalPages = 1;
let isLoading = false;

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadTransactions();
    setupMobileEventListeners();
    loadSummary();
});

// Setup mobile event listeners
function setupMobileEventListeners() {
    // Filter toggle
    document.getElementById('mobile-filter-btn').addEventListener('click', function() {
        const filters = document.getElementById('mobile-filters');
        filters.classList.toggle('hidden');
    });

    // Apply filters
    document.getElementById('mobile-apply-filters').addEventListener('click', function() {
        currentPage = 1;
        loadTransactions();
        document.getElementById('mobile-filters').classList.add('hidden');
    });

    // Clear filters
    document.getElementById('mobile-clear-filters').addEventListener('click', function() {
        document.getElementById('mobile-type-filter').value = '';
        document.getElementById('mobile-date-from').value = '';
        document.getElementById('mobile-date-to').value = '';
        document.getElementById('mobile-search').value = '';
        currentPage = 1;
        loadTransactions();
        document.getElementById('mobile-filters').classList.add('hidden');
    });

    // Load more
    document.getElementById('mobile-load-more-btn').addEventListener('click', function() {
        if (!isLoading && currentPage < totalPages) {
            currentPage++;
            loadTransactions(true);
        }
    });

    // Export button
    document.getElementById('mobile-export-btn').addEventListener('click', function() {
        exportTransactions();
    });

    // Modal close
    document.getElementById('mobile-close-modal').addEventListener('click', hideMobileTransactionModal);
    document.getElementById('mobile-close-modal-btn').addEventListener('click', hideMobileTransactionModal);

    // Search on enter
    document.getElementById('mobile-search').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            currentPage = 1;
            loadTransactions();
            document.getElementById('mobile-filters').classList.add('hidden');
        }
    });
}

// Load transactions
async function loadTransactions(append = false) {
    if (isLoading) return;
    
    isLoading = true;
    showMobileLoading();
    
    try {
        const filters = getMobileFilters();
        const response = await fetch(`/transactions/api?${new URLSearchParams(filters)}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            displayMobileTransactions(data.data, append);
            updateMobilePagination(data.pagination);
        } else {
            showMobileError('Erreur lors du chargement');
        }
    } catch (error) {
        showMobileError('Erreur de connexion');
    } finally {
        isLoading = false;
        hideMobileLoading();
    }
}

// Get mobile filters
function getMobileFilters() {
    return {
        type: document.getElementById('mobile-type-filter').value,
        date_from: document.getElementById('mobile-date-from').value,
        date_to: document.getElementById('mobile-date-to').value,
        search: document.getElementById('mobile-search').value,
        page: currentPage
    };
}

// Display mobile transactions
function displayMobileTransactions(transactions, append = false) {
    const container = document.getElementById('mobile-transactions-list');
    
    if (transactions.length === 0 && !append) {
        showMobileEmptyState();
        return;
    }
    
    hideMobileEmptyState();
    
    const html = transactions.map(transaction => `
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-3 p-4" onclick="showMobileTransactionDetails(${transaction.id})">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center mr-3 ${transaction.type === 'credit' ? 'bg-green-100' : 'bg-red-100'}">
                        <i class="fas ${transaction.type === 'credit' ? 'fa-arrow-up text-green-600' : 'fa-arrow-down text-red-600'}"></i>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-900">${transaction.type === 'credit' ? 'Crédit' : 'Débit'}</div>
                        <div class="text-xs text-gray-500">${formatMobileDate(transaction.created_at)}</div>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-sm font-semibold ${transaction.type === 'credit' ? 'text-green-600' : 'text-red-600'}">
                        ${transaction.type === 'credit' ? '+' : '-'}${formatAmount(transaction.amount)}
                    </div>
                    <div class="text-xs text-gray-500">${transaction.currency || 'EUR'}</div>
                </div>
            </div>
            <div class="text-sm text-gray-600 mb-1">${transaction.description}</div>
            <div class="text-xs text-gray-500">Solde: ${formatAmount(transaction.current_balance)} ${transaction.currency || 'EUR'}</div>
        </div>
    `).join('');
    
    if (append) {
        container.innerHTML += html;
    } else {
        container.innerHTML = html;
    }
}

// Update mobile pagination
function updateMobilePagination(pagination) {
    const loadMore = document.getElementById('mobile-load-more');
    
    if (pagination.current_page < pagination.total_pages) {
        loadMore.classList.remove('hidden');
    } else {
        loadMore.classList.add('hidden');
    }
    
    totalPages = pagination.total_pages;
}

// Show mobile transaction details
async function showMobileTransactionDetails(transactionId) {
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
            displayMobileTransactionModal(data.data);
        } else {
            showMobileError('Erreur lors du chargement');
        }
    } catch (error) {
        showMobileError('Erreur de connexion');
    }
}

// Display mobile transaction modal
function displayMobileTransactionModal(transaction) {
    const modal = document.getElementById('mobile-transaction-modal');
    const content = document.getElementById('mobile-modal-content');
    
    content.innerHTML = `
        <div class="space-y-4">
            <!-- Transaction Header -->
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3 ${transaction.type === 'credit' ? 'bg-green-100' : 'bg-red-100'}">
                        <i class="fas ${transaction.type === 'credit' ? 'fa-arrow-up text-green-600' : 'fa-arrow-down text-red-600'}"></i>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900">${transaction.type === 'credit' ? 'Crédit' : 'Débit'}</h4>
                        <p class="text-xs text-gray-500">#${transaction.id}</p>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-lg font-bold ${transaction.type === 'credit' ? 'text-green-600' : 'text-red-600'}">
                        ${transaction.type === 'credit' ? '+' : '-'}${formatAmount(transaction.amount)}
                    </div>
                    <div class="text-xs text-gray-500">${transaction.currency || 'EUR'}</div>
                </div>
            </div>

            <!-- Transaction Details -->
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Description:</span>
                    <span class="text-sm font-medium text-gray-900">${transaction.description}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Date:</span>
                    <span class="text-sm font-medium text-gray-900">${formatMobileDate(transaction.created_at)}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Heure:</span>
                    <span class="text-sm font-medium text-gray-900">${formatMobileTime(transaction.created_at)}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Solde après:</span>
                    <span class="text-sm font-medium text-gray-900">${formatAmount(transaction.current_balance)} ${transaction.currency || 'EUR'}</span>
                </div>
            </div>
        </div>
    `;
    
    modal.classList.remove('hidden');
}

// Hide mobile transaction modal
function hideMobileTransactionModal() {
    document.getElementById('mobile-transaction-modal').classList.add('hidden');
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
            document.getElementById('mobile-current-balance').textContent = formatAmount(data.data.current_balance);
            document.getElementById('mobile-total-credits').textContent = formatAmount(data.data.total_credits);
            document.getElementById('mobile-total-debits').textContent = formatAmount(data.data.total_debits);
            document.getElementById('mobile-total-transactions').textContent = data.data.total_transactions;
        }
    } catch (error) {
        console.error('Error loading summary:', error);
    }
}

// Export transactions
function exportTransactions() {
    const url = '/transactions/export?format=csv';
    window.open(url, '_blank');
}

// Utility functions
function formatAmount(amount) {
    return new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount);
}

function formatMobileDate(dateString) {
    return new Date(dateString).toLocaleDateString('fr-FR');
}

function formatMobileTime(dateString) {
    return new Date(dateString).toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit'
    });
}

function showMobileLoading() {
    document.getElementById('mobile-loading-state').classList.remove('hidden');
}

function hideMobileLoading() {
    document.getElementById('mobile-loading-state').classList.add('hidden');
}

function showMobileEmptyState() {
    document.getElementById('mobile-empty-state').classList.remove('hidden');
}

function hideMobileEmptyState() {
    document.getElementById('mobile-empty-state').classList.add('hidden');
}

function showMobileError(message) {
    alert('Erreur: ' + message);
}
</script>
@endsection
