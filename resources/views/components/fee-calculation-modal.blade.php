<!-- Fee Calculation Modal Component -->
<div id="feeCalculationModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
    <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white dark:bg-gray-800">
        <!-- Modal Header -->
        <div class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center">
                <div class="h-10 w-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center mr-3">
                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">Calcul des Frais et Revenus</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Répartition détaillée des frais et revenus</p>
                </div>
            </div>
            <button onclick="closeFeeModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="py-6">
            <!-- Loading State -->
            <div id="feeLoadingState" class="text-center py-8">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Calcul des frais en cours...</p>
            </div>

            <!-- Error State -->
            <div id="feeErrorState" class="hidden text-center py-8">
                <div class="h-16 w-16 bg-red-100 dark:bg-red-900 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="h-8 w-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Erreur de calcul</h3>
                <p class="text-gray-600 dark:text-gray-400" id="feeErrorMessage">Impossible de calculer les frais pour cette borne.</p>
            </div>

            <!-- Fee Calculation Content -->
            <div id="feeCalculationContent" class="hidden">
                <!-- Charging Point Info -->
                <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="flex items-center mb-3">
                        <svg class="h-5 w-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <h4 class="font-semibold text-gray-900 dark:text-gray-100">Informations de la Borne</h4>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">Nom:</span>
                            <span class="ml-2 font-medium text-gray-900 dark:text-gray-100" id="chargingPointName">-</span>
                        </div>
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">Créateur:</span>
                            <span class="ml-2 font-medium text-gray-900 dark:text-gray-100" id="chargingPointCreator">-</span>
                        </div>
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">Montant de base:</span>
                            <span class="ml-2 font-medium text-gray-900 dark:text-gray-100" id="baseAmount">-</span>
                        </div>
                    </div>
                </div>

                <!-- Fee Breakdown Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <!-- Admin Part Card -->
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                        <div class="flex items-center mb-3">
                            <div class="h-8 w-8 bg-red-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <h5 class="font-semibold text-red-800 dark:text-red-200">Admin</h5>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-red-700 dark:text-red-300">Appliquer:</span>
                                <span class="font-medium" id="adminApplies">-</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-red-700 dark:text-red-300">Total:</span>
                                <span class="font-bold text-red-800 dark:text-red-200" id="adminTotal">-</span>
                            </div>
                        </div>
                        <div class="mt-3 text-xs text-red-600 dark:text-red-400" id="adminReason">-</div>
                    </div>

                    <!-- Integrator Part Card -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                        <div class="flex items-center mb-3">
                            <div class="h-8 w-8 bg-blue-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <h5 class="font-semibold text-blue-800 dark:text-blue-200">Intégrateur</h5>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-blue-700 dark:text-blue-300">Appliquer:</span>
                                <span class="font-medium" id="integratorApplies">-</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-blue-700 dark:text-blue-300">Total:</span>
                                <span class="font-bold text-blue-800 dark:text-blue-200" id="integratorTotal">-</span>
                            </div>
                        </div>
                        <div class="mt-3 text-xs text-blue-600 dark:text-blue-400" id="integratorReason">-</div>
                    </div>

                    <!-- Partner Part Card -->
                    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                        <div class="flex items-center mb-3">
                            <div class="h-8 w-8 bg-green-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <h5 class="font-semibold text-green-800 dark:text-green-200">Partenaire</h5>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-green-700 dark:text-green-300">Appliquer:</span>
                                <span class="font-medium" id="partnerApplies">-</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-green-700 dark:text-green-300">Total:</span>
                                <span class="font-bold text-green-800 dark:text-green-200" id="partnerTotal">-</span>
                            </div>
                        </div>
                        <div class="mt-3 text-xs text-green-600 dark:text-green-400" id="partnerReason">-</div>
                    </div>

                    <!-- Operator Part Card -->
                    <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-4">
                        <div class="flex items-center mb-3">
                            <div class="h-8 w-8 bg-purple-500 rounded-lg flex items-center justify-center mr-3">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <h5 class="font-semibold text-purple-800 dark:text-purple-200">Opérateur</h5>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-purple-700 dark:text-purple-300">Revenus:</span>
                                <span class="font-bold text-purple-800 dark:text-purple-200" id="operatorRevenue">-</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-purple-700 dark:text-purple-300">Frais déduits:</span>
                                <span class="font-medium text-purple-800 dark:text-purple-200" id="operatorDeducted">-</span>
                            </div>
                        </div>
                        <div class="mt-3 text-xs text-purple-600 dark:text-purple-400">Revenus nets après déduction des frais</div>
                    </div>
                </div>

                <!-- Detailed Breakdown -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                        <svg class="h-5 w-5 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        Détail des Frais
                    </h4>
                    
                    <div class="space-y-4">
                        <!-- Admin Fees Detail -->
                        <div id="adminFeesDetail" class="hidden">
                            <h5 class="font-medium text-red-800 dark:text-red-200 mb-2">Frais Admin:</h5>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                <div class="bg-red-50 dark:bg-red-900/20 p-3 rounded">
                                    <div class="text-red-700 dark:text-red-300">Frais fixes</div>
                                    <div class="font-semibold text-red-800 dark:text-red-200" id="adminFixedFee">-</div>
                                </div>
                                <div class="bg-red-50 dark:bg-red-900/20 p-3 rounded">
                                    <div class="text-red-700 dark:text-red-300">Frais %</div>
                                    <div class="font-semibold text-red-800 dark:text-red-200" id="adminPercentageFee">-</div>
                                </div>
                                <div class="bg-red-50 dark:bg-red-900/20 p-3 rounded">
                                    <div class="text-red-700 dark:text-red-300">Frais activation</div>
                                    <div class="font-semibold text-red-800 dark:text-red-200" id="adminActivationFee">-</div>
                                </div>
                                <div class="bg-red-50 dark:bg-red-900/20 p-3 rounded">
                                    <div class="text-red-700 dark:text-red-300">Frais transaction</div>
                                    <div class="font-semibold text-red-800 dark:text-red-200" id="adminTransactionFee">-</div>
                                </div>
                            </div>
                        </div>

                        <!-- Integrator Fees Detail -->
                        <div id="integratorFeesDetail" class="hidden">
                            <h5 class="font-medium text-blue-800 dark:text-blue-200 mb-2">Frais Intégrateur:</h5>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                                <div class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded">
                                    <div class="text-blue-700 dark:text-blue-300">Frais fixes</div>
                                    <div class="font-semibold text-blue-800 dark:text-blue-200" id="integratorFixedFee">-</div>
                                </div>
                                <div class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded">
                                    <div class="text-blue-700 dark:text-blue-300">Frais %</div>
                                    <div class="font-semibold text-blue-800 dark:text-blue-200" id="integratorPercentageFee">-</div>
                                </div>
                                <div class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded">
                                    <div class="text-blue-700 dark:text-blue-300">Commission</div>
                                    <div class="font-semibold text-blue-800 dark:text-blue-200" id="integratorCommissionFee">-</div>
                                </div>
                            </div>
                        </div>

                        <!-- Partner Fees Detail -->
                        <div id="partnerFeesDetail" class="hidden">
                            <h5 class="font-medium text-green-800 dark:text-green-200 mb-2">Frais Partenaire:</h5>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                                <div class="bg-green-50 dark:bg-green-900/20 p-3 rounded">
                                    <div class="text-green-700 dark:text-green-300">Frais fixes</div>
                                    <div class="font-semibold text-green-800 dark:text-green-200" id="partnerFixedFee">-</div>
                                </div>
                                <div class="bg-green-50 dark:bg-green-900/20 p-3 rounded">
                                    <div class="text-green-700 dark:text-green-300">Frais %</div>
                                    <div class="font-semibold text-green-800 dark:text-green-200" id="partnerPercentageFee">-</div>
                                </div>
                                <div class="bg-green-50 dark:bg-green-900/20 p-3 rounded">
                                    <div class="text-green-700 dark:text-green-300">Commission</div>
                                    <div class="font-semibold text-green-800 dark:text-green-200" id="partnerCommissionFee">-</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary -->
                <div class="mt-6 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600 rounded-lg p-6">
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Résumé</h4>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-center">
                        <div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100" id="summaryBaseAmount">-</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Montant de Base</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100" id="summaryTotalFees">-</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Total Frais</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400" id="summaryFinalTotal">-</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Montant Total Final</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100" id="summaryNetRevenue">-</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Revenus Nets</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100" id="summaryCreatorType">-</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Type Créateur</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="flex items-center justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
            <button onclick="closeFeeModal()" class="px-4 py-2 bg-gray-500 text-white text-sm font-medium rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                Fermer
            </button>
        </div>
    </div>
</div>

<script>
function openFeeModal(chargingPointId, baseAmount = 100) {
    const modal = document.getElementById('feeCalculationModal');
    const loadingState = document.getElementById('feeLoadingState');
    const errorState = document.getElementById('feeErrorState');
    const content = document.getElementById('feeCalculationContent');
    
    // Show modal and loading state
    modal.classList.remove('hidden');
    loadingState.classList.remove('hidden');
    errorState.classList.add('hidden');
    content.classList.add('hidden');
    
    // Fetch fee calculation data
    fetch(`/api/v1/fees/calculate-charging-point`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            charging_point_id: chargingPointId,
            base_amount: baseAmount
        })
    })
    .then(response => response.json())
    .then(data => {
        loadingState.classList.add('hidden');
        
        if (data.success) {
            populateFeeModal(data.data);
            content.classList.remove('hidden');
        } else {
            showFeeError(data.message || 'Erreur lors du calcul des frais');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showFeeError('Erreur de connexion');
    });
}

function populateFeeModal(data) {
    // Charging Point Info
    document.getElementById('chargingPointName').textContent = data.charging_point_info.name || 'N/A';
    document.getElementById('chargingPointCreator').textContent = `${data.creator_info.name} (${data.creator_type})`;
    document.getElementById('baseAmount').textContent = `${data.base_amount.toFixed(2)} EUR`;
    
    // Admin Part
    const adminApplies = data.admin_part.applies;
    document.getElementById('adminApplies').textContent = adminApplies ? '✅ Oui' : '❌ Non';
    document.getElementById('adminApplies').className = adminApplies ? 'font-medium text-green-600' : 'font-medium text-red-600';
    document.getElementById('adminTotal').textContent = `${data.admin_part.fees.total.toFixed(2)} EUR`;
    document.getElementById('adminReason').textContent = data.admin_part.reason;
    
    if (adminApplies) {
        document.getElementById('adminFeesDetail').classList.remove('hidden');
        document.getElementById('adminFixedFee').textContent = `${data.admin_part.fees.fixed_fee.toFixed(2)} EUR`;
        document.getElementById('adminPercentageFee').textContent = `${data.admin_part.fees.percentage_fee.toFixed(2)} EUR`;
        document.getElementById('adminActivationFee').textContent = `${data.admin_part.fees.activation_fee.toFixed(2)} EUR`;
        document.getElementById('adminTransactionFee').textContent = `${data.admin_part.fees.transaction_fee.toFixed(2)} EUR`;
    }
    
    // Integrator Part
    const integratorApplies = data.integrator_part.applies;
    document.getElementById('integratorApplies').textContent = integratorApplies ? '✅ Oui' : '❌ Non';
    document.getElementById('integratorApplies').className = integratorApplies ? 'font-medium text-green-600' : 'font-medium text-red-600';
    document.getElementById('integratorTotal').textContent = `${data.integrator_part.fees.total.toFixed(2)} EUR`;
    document.getElementById('integratorReason').textContent = data.integrator_part.reason;
    
    if (integratorApplies) {
        document.getElementById('integratorFeesDetail').classList.remove('hidden');
        document.getElementById('integratorFixedFee').textContent = `${data.integrator_part.fees.fixed_fee.toFixed(2)} EUR`;
        document.getElementById('integratorPercentageFee').textContent = `${data.integrator_part.fees.percentage_fee.toFixed(2)} EUR`;
        document.getElementById('integratorCommissionFee').textContent = `${data.integrator_part.fees.commission_fee.toFixed(2)} EUR`;
    }
    
    // Partner Part
    const partnerApplies = data.partner_part.applies;
    document.getElementById('partnerApplies').textContent = partnerApplies ? '✅ Oui' : '❌ Non';
    document.getElementById('partnerApplies').className = partnerApplies ? 'font-medium text-green-600' : 'font-medium text-red-600';
    document.getElementById('partnerTotal').textContent = `${data.partner_part.fees.total.toFixed(2)} EUR`;
    document.getElementById('partnerReason').textContent = data.partner_part.reason;
    
    if (partnerApplies) {
        document.getElementById('partnerFeesDetail').classList.remove('hidden');
        document.getElementById('partnerFixedFee').textContent = `${data.partner_part.fees.fixed_fee.toFixed(2)} EUR`;
        document.getElementById('partnerPercentageFee').textContent = `${data.partner_part.fees.percentage_fee.toFixed(2)} EUR`;
        document.getElementById('partnerCommissionFee').textContent = `${data.partner_part.fees.commission_fee.toFixed(2)} EUR`;
    }
    
    // Operator Part
    document.getElementById('operatorRevenue').textContent = `${data.operator_part.revenue.net_revenue.toFixed(2)} EUR`;
    document.getElementById('operatorDeducted').textContent = `${data.operator_part.revenue.deducted_fees.toFixed(2)} EUR`;
    
    // Summary
    document.getElementById('summaryBaseAmount').textContent = `${data.base_amount.toFixed(2)} EUR`;
    document.getElementById('summaryTotalFees').textContent = `${data.total_breakdown.total_fees.toFixed(2)} EUR`;
    document.getElementById('summaryFinalTotal').textContent = `${data.total_breakdown.final_total_amount.toFixed(2)} EUR`;
    document.getElementById('summaryNetRevenue').textContent = `${data.total_breakdown.net_revenue.toFixed(2)} EUR`;
    document.getElementById('summaryCreatorType').textContent = data.creator_type;
}

function showFeeError(message) {
    const loadingState = document.getElementById('feeLoadingState');
    const errorState = document.getElementById('feeErrorState');
    const errorMessage = document.getElementById('feeErrorMessage');
    
    loadingState.classList.add('hidden');
    errorMessage.textContent = message;
    errorState.classList.remove('hidden');
}

function closeFeeModal() {
    const modal = document.getElementById('feeCalculationModal');
    modal.classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('feeCalculationModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeFeeModal();
    }
});
</script>
