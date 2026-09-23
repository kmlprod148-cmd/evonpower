<!-- Bulk Fee Calculation Modal Component -->
<div id="bulkFeeCalculationModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
    <div class="relative top-10 mx-auto p-5 border w-11/12 max-w-6xl shadow-lg rounded-md bg-white dark:bg-gray-800">
        <!-- Modal Header -->
        <div class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center">
                <div class="h-10 w-10 bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg flex items-center justify-center mr-3">
                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">Calcul des Frais en Lot</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Calcul des frais pour plusieurs bornes de recharge</p>
                </div>
            </div>
            <button onclick="closeBulkFeeModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="py-6">
            <!-- Configuration Section -->
            <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Configuration</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Montant de base (EUR)
                        </label>
                        <input type="number" id="bulkBaseAmount" value="100" min="0" step="0.01" 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-800 dark:text-gray-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Bornes à calculer
                        </label>
                        <div class="text-sm text-gray-600 dark:text-gray-400" id="selectedChargingPointsCount">
                            Sélectionnez les bornes à calculer
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charging Points Selection -->
            <div class="mb-6">
                <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Sélection des Bornes</h4>
                <div class="max-h-64 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-lg">
                    <div id="chargingPointsList" class="divide-y divide-gray-200 dark:divide-gray-700">
                        <!-- Charging points will be populated here -->
                    </div>
                </div>
            </div>

            <!-- Loading State -->
            <div id="bulkFeeLoadingState" class="hidden text-center py-8">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Calcul des frais en cours...</p>
            </div>

            <!-- Error State -->
            <div id="bulkFeeErrorState" class="hidden text-center py-8">
                <div class="h-16 w-16 bg-red-100 dark:bg-red-900 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="h-8 w-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Erreur de calcul</h3>
                <p class="text-gray-600 dark:text-gray-400" id="bulkFeeErrorMessage">Impossible de calculer les frais pour les bornes sélectionnées.</p>
            </div>

            <!-- Results -->
            <div id="bulkFeeResults" class="hidden">
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                    <div class="bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                        <div class="flex items-center mb-2">
                            <div class="h-8 w-8 bg-gray-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                                </svg>
                            </div>
                            <h5 class="font-semibold text-gray-800 dark:text-gray-200">Montant Base</h5>
                        </div>
                        <div class="text-2xl font-bold text-gray-800 dark:text-gray-200" id="bulkBaseAmount">-</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Total montant de base</div>
                    </div>

                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                        <div class="flex items-center mb-2">
                            <div class="h-8 w-8 bg-red-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <h5 class="font-semibold text-red-800 dark:text-red-200">Admin</h5>
                        </div>
                        <div class="text-2xl font-bold text-red-800 dark:text-red-200" id="bulkAdminTotal">-</div>
                        <div class="text-sm text-red-600 dark:text-red-400">Total frais admin</div>
                    </div>

                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                        <div class="flex items-center mb-2">
                            <div class="h-8 w-8 bg-blue-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <h5 class="font-semibold text-blue-800 dark:text-blue-200">Intégrateur</h5>
                        </div>
                        <div class="text-2xl font-bold text-blue-800 dark:text-blue-200" id="bulkIntegratorTotal">-</div>
                        <div class="text-sm text-blue-600 dark:text-blue-400">Total frais intégrateur</div>
                    </div>

                    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                        <div class="flex items-center mb-2">
                            <div class="h-8 w-8 bg-green-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <h5 class="font-semibold text-green-800 dark:text-green-200">Partenaire</h5>
                        </div>
                        <div class="text-2xl font-bold text-green-800 dark:text-green-200" id="bulkPartnerTotal">-</div>
                        <div class="text-sm text-green-600 dark:text-green-400">Total frais partenaire</div>
                    </div>

                    <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-4">
                        <div class="flex items-center mb-2">
                            <div class="h-8 w-8 bg-purple-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <h5 class="font-semibold text-purple-800 dark:text-purple-200">Opérateur</h5>
                        </div>
                        <div class="text-2xl font-bold text-purple-800 dark:text-purple-200" id="bulkOperatorTotal">-</div>
                        <div class="text-sm text-purple-600 dark:text-purple-400">Total revenus opérateur</div>
                    </div>
                </div>

                <!-- Final Total Amount -->
                <div class="mb-6 bg-gradient-to-r from-green-50 to-green-100 dark:from-green-700 dark:to-green-600 rounded-lg p-6">
                    <div class="text-center">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Montant Total Final</h4>
                        <div class="text-4xl font-bold text-green-600 dark:text-green-400" id="bulkFinalTotal">-</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Montant de base + Total des frais</div>
                    </div>
                </div>

                <!-- Detailed Results Table -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h4 class="font-semibold text-gray-900 dark:text-gray-100">Détail par Borne</h4>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Borne</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Créateur</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Admin</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Intégrateur</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Partenaire</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Opérateur</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Frais</th>
                                </tr>
                            </thead>
                            <tbody id="bulkResultsTableBody" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <!-- Results will be populated here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
            <div class="text-sm text-gray-500 dark:text-gray-400">
                <span id="bulkCalculationInfo">Sélectionnez les bornes et configurez le montant de base</span>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="closeBulkFeeModal()" class="px-4 py-2 bg-gray-500 text-white text-sm font-medium rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                    Fermer
                </button>
                <button onclick="calculateBulkFees()" id="calculateBulkFeesBtn" class="px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition-colors">
                    Calculer les Frais
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let selectedChargingPoints = [];

function openBulkFeeCalculation() {
    const modal = document.getElementById('bulkFeeCalculationModal');
    const loadingState = document.getElementById('bulkFeeLoadingState');
    const errorState = document.getElementById('bulkFeeErrorState');
    const results = document.getElementById('bulkFeeResults');
    
    // Show modal and reset states
    modal.classList.remove('hidden');
    loadingState.classList.add('hidden');
    errorState.classList.add('hidden');
    results.classList.add('hidden');
    
    // Populate charging points list
    populateChargingPointsList();
}

function populateChargingPointsList() {
    const listContainer = document.getElementById('chargingPointsList');
    const countElement = document.getElementById('selectedChargingPointsCount');
    
    // Get charging points from the current page
    const chargingPoints = [];
    document.querySelectorAll('tbody tr').forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length >= 3) {
            const nameCell = cells[1]; // Assuming name is in second column
            const link = row.querySelector('a[href*="/charging-points/"]');
            if (link && nameCell) {
                const href = link.getAttribute('href');
                const id = href.match(/\/charging-points\/(\d+)/);
                if (id) {
                    chargingPoints.push({
                        id: parseInt(id[1]),
                        name: nameCell.textContent.trim(),
                        href: href
                    });
                }
            }
        }
    });
    
    if (chargingPoints.length === 0) {
        listContainer.innerHTML = '<div class="p-4 text-center text-gray-500 dark:text-gray-400">Aucune borne trouvée sur cette page</div>';
        countElement.textContent = 'Aucune borne disponible';
        return;
    }
    
    // Populate the list
    listContainer.innerHTML = chargingPoints.map(cp => `
        <div class="flex items-center justify-between p-3 hover:bg-gray-50 dark:hover:bg-gray-700">
            <div class="flex items-center">
                <input type="checkbox" id="cp_${cp.id}" value="${cp.id}" 
                       class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded"
                       onchange="updateSelectedChargingPoints()">
                <label for="cp_${cp.id}" class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                    ${cp.name}
                </label>
            </div>
            <a href="${cp.href}" class="text-purple-600 hover:text-purple-800 text-sm">Voir</a>
        </div>
    `).join('');
    
    // Select all by default
    document.querySelectorAll('#chargingPointsList input[type="checkbox"]').forEach(checkbox => {
        checkbox.checked = true;
    });
    
    updateSelectedChargingPoints();
}

function updateSelectedChargingPoints() {
    const checkboxes = document.querySelectorAll('#chargingPointsList input[type="checkbox"]:checked');
    selectedChargingPoints = Array.from(checkboxes).map(cb => parseInt(cb.value));
    
    const countElement = document.getElementById('selectedChargingPointsCount');
    countElement.textContent = `${selectedChargingPoints.length} borne(s) sélectionnée(s)`;
    
    const calculateBtn = document.getElementById('calculateBulkFeesBtn');
    calculateBtn.disabled = selectedChargingPoints.length === 0;
}

function calculateBulkFees() {
    if (selectedChargingPoints.length === 0) {
        alert('Veuillez sélectionner au moins une borne.');
        return;
    }
    
    const baseAmount = parseFloat(document.getElementById('bulkBaseAmount').value) || 100;
    const loadingState = document.getElementById('bulkFeeLoadingState');
    const errorState = document.getElementById('bulkFeeErrorState');
    const results = document.getElementById('bulkFeeResults');
    
    // Show loading state
    loadingState.classList.remove('hidden');
    errorState.classList.add('hidden');
    results.classList.add('hidden');
    
    // Fetch bulk fee calculation data
    fetch(`/api/v1/fees/calculate-multiple`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            charging_point_ids: selectedChargingPoints,
            base_amount: baseAmount
        })
    })
    .then(response => response.json())
    .then(data => {
        loadingState.classList.add('hidden');
        
        if (data.success) {
            populateBulkResults(data.data);
            results.classList.remove('hidden');
        } else {
            showBulkFeeError(data.message || 'Erreur lors du calcul des frais');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showBulkFeeError('Erreur de connexion');
    });
}

function populateBulkResults(data) {
    // Calculate totals
    const totalBaseAmount = data.results.length * data.base_amount;
    const totalFees = data.summary.total_admin_fees + data.summary.total_integrator_fees + data.summary.total_partner_fees;
    const finalTotalAmount = totalBaseAmount + totalFees;
    
    // Update summary cards
    document.getElementById('bulkBaseAmount').textContent = `${totalBaseAmount.toFixed(2)} EUR`;
    document.getElementById('bulkAdminTotal').textContent = `${data.summary.total_admin_fees.toFixed(2)} EUR`;
    document.getElementById('bulkIntegratorTotal').textContent = `${data.summary.total_integrator_fees.toFixed(2)} EUR`;
    document.getElementById('bulkPartnerTotal').textContent = `${data.summary.total_partner_fees.toFixed(2)} EUR`;
    document.getElementById('bulkOperatorTotal').textContent = `${data.summary.total_operator_revenue.toFixed(2)} EUR`;
    document.getElementById('bulkFinalTotal').textContent = `${finalTotalAmount.toFixed(2)} EUR`;
    
    // Populate results table
    const tableBody = document.getElementById('bulkResultsTableBody');
    tableBody.innerHTML = data.results.map(result => `
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                ${result.charging_point_name}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                ${result.creator_name} (${result.creator_type})
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                ${result.admin_fees.toFixed(2)} EUR
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                ${result.integrator_fees.toFixed(2)} EUR
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                ${result.partner_fees.toFixed(2)} EUR
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                ${result.operator_revenue.toFixed(2)} EUR
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-gray-100">
                ${result.total_fees.toFixed(2)} EUR
            </td>
        </tr>
    `).join('');
    
    // Update info text
    document.getElementById('bulkCalculationInfo').textContent = 
        `Calcul terminé pour ${data.results.length} borne(s) avec un montant de base de ${data.base_amount} EUR`;
}

function showBulkFeeError(message) {
    const loadingState = document.getElementById('bulkFeeLoadingState');
    const errorState = document.getElementById('bulkFeeErrorState');
    const errorMessage = document.getElementById('bulkFeeErrorMessage');
    
    loadingState.classList.add('hidden');
    errorMessage.textContent = message;
    errorState.classList.remove('hidden');
}

function closeBulkFeeModal() {
    const modal = document.getElementById('bulkFeeCalculationModal');
    modal.classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('bulkFeeCalculationModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeBulkFeeModal();
    }
});
</script>
