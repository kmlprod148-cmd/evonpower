@extends('layouts.app')

@section('title', 'Frais des Créateurs de Bornes')

@section('content')
<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-6">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Calcul Avancé des Frais</h1>
                <p class="text-gray-600 mt-2">Frais de recharge, frais de transaction et frais d'activation des créateurs de bornes</p>
            </div>
        </div>
    </div>

    <!-- Error Display -->
    @if(isset($error))
    <div class="bg-red-50 border border-red-200 rounded-md p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">Erreur lors du chargement de la page</h3>
                <div class="mt-2 text-sm text-red-700">
                    <p>{{ $error }}</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <form method="GET" action="{{ route('admin.transactions.charging-point-creator-fees') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Sélection Borne -->
            <div>
                <label for="charging_point_id" class="block text-sm font-medium text-gray-700 mb-1">Borne de Recharge</label>
                <select id="charging_point_id" name="charging_point_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Sélectionner une borne</option>
                    @foreach($chargingPoints as $cp)
                        <option value="{{ $cp->id }}" {{ $chargingPointId == $cp->id ? 'selected' : '' }}>
                            {{ $cp->name }} ({{ $cp->integrator->name ?? 'N/A' }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Type de Créateur -->
            <div>
                <label for="creator_type" class="block text-sm font-medium text-gray-700 mb-1">Type de Créateur</label>
                <select id="creator_type" name="creator_type" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Sélectionner un type</option>
                    <option value="integrator" {{ $creatorType === 'integrator' ? 'selected' : '' }}>Intégrateur</option>
                    <option value="partner" {{ $creatorType === 'partner' ? 'selected' : '' }}>Partenaire</option>
                </select>
            </div>

            <!-- Créateur -->
            <div id="creator_selection" class="hidden">
                <label for="creator_id" class="block text-sm font-medium text-gray-700 mb-1">Créateur</label>
                <select id="creator_id" name="creator_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Sélectionner un créateur</option>
                </select>
            </div>

            <!-- Date de début -->
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Date de début</label>
                <input type="date" id="start_date" name="start_date" value="{{ $startDate }}" 
                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <!-- Date de fin -->
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">Date de fin</label>
                <input type="date" id="end_date" name="end_date" value="{{ $endDate }}" 
                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <!-- Actions -->
            <div class="flex items-end space-x-2">
                <button type="submit" 
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    Calculer
                </button>
                <button type="button" onclick="calculateFeesForAmount()" 
                        class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                    Calculer pour Montant
                </button>
            </div>
        </form>
    </div>

    <!-- Calculateur de frais en temps réel -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Calculateur de Frais en Temps Réel</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="base_amount" class="block text-sm font-medium text-gray-700 mb-1">Montant de Base (EUR)</label>
                <input type="number" id="base_amount" step="0.01" min="0" value="100" 
                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label for="selected_charging_point" class="block text-sm font-medium text-gray-700 mb-1">Borne Sélectionnée</label>
                <select id="selected_charging_point" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Sélectionner une borne</option>
                    @foreach($chargingPoints as $cp)
                        <option value="{{ $cp->id }}">{{ $cp->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button onclick="calculateRealTimeFees()" 
                        class="w-full inline-flex items-center justify-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Calculer
                </button>
            </div>
        </div>
    </div>

    <!-- Résultats des frais -->
    @if(!empty($feeDetails))
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Détails des Frais</h3>
        
        @if(isset($feeDetails['creator_info']))
        <!-- Informations du créateur -->
        <div class="mb-6">
            <h4 class="text-md font-medium text-gray-700 mb-3">Informations du Créateur</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600">Type</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $feeDetails['creator_info']['type'] ?? 'N/A' }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600">Nom</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $feeDetails['creator_info']['name'] ?? 'N/A' }}</p>
                </div>
            </div>
        </div>
        @endif

        @if(isset($feeDetails['business_profile_info']))
        <!-- Informations du business profile -->
        <div class="mb-6">
            <h4 class="text-md font-medium text-gray-700 mb-3">Business Profile</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-blue-50 rounded-lg p-4">
                    <p class="text-sm text-blue-600">Nom</p>
                    <p class="text-lg font-semibold text-blue-900">{{ $feeDetails['business_profile_info']['name'] ?? 'N/A' }}</p>
                </div>
                <div class="bg-blue-50 rounded-lg p-4">
                    <p class="text-sm text-blue-600">Statut</p>
                    <p class="text-lg font-semibold text-blue-900">{{ $feeDetails['business_profile_info']['is_active'] ? 'Actif' : 'Inactif' }}</p>
                </div>
                <div class="bg-blue-50 rounded-lg p-4">
                    <p class="text-sm text-blue-600">Type</p>
                    <p class="text-lg font-semibold text-blue-900">{{ $feeDetails['business_profile_info']['is_public'] ? 'Public' : 'Privé' }}</p>
                </div>
            </div>
        </div>
        @endif

        <!-- Détail des frais -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Frais de recharge -->
            @if(isset($feeDetails['charging_fees']))
            <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                <h5 class="text-md font-medium text-green-800 mb-3">Frais de Recharge</h5>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-sm text-green-600">Frais fixes:</span>
                        <span class="font-medium">{{ number_format($feeDetails['charging_fees']['fixed_fee'], 2) }} EUR</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-green-600">Frais %:</span>
                        <span class="font-medium">{{ number_format($feeDetails['charging_fees']['percentage_fee'], 2) }} EUR</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-green-600">Par kWh:</span>
                        <span class="font-medium">{{ number_format($feeDetails['charging_fees']['per_kwh_fee'], 2) }} EUR</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-green-600">Par minute:</span>
                        <span class="font-medium">{{ number_format($feeDetails['charging_fees']['per_minute_fee'], 2) }} EUR</span>
                    </div>
                    <div class="border-t pt-2">
                        <div class="flex justify-between font-bold">
                            <span class="text-green-800">Total:</span>
                            <span class="text-green-900">{{ number_format($feeDetails['charging_fees']['total'], 2) }} EUR</span>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Frais de transaction -->
            @if(isset($feeDetails['transaction_fees']))
            <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-200">
                <h5 class="text-md font-medium text-yellow-800 mb-3">Frais de Transaction</h5>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-sm text-yellow-600">Frais fixes:</span>
                        <span class="font-medium">{{ number_format($feeDetails['transaction_fees']['fixed_fee'], 2) }} EUR</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-yellow-600">Frais %:</span>
                        <span class="font-medium">{{ number_format($feeDetails['transaction_fees']['percentage_fee'], 2) }} EUR</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-yellow-600">Minimum:</span>
                        <span class="font-medium">{{ number_format($feeDetails['transaction_fees']['minimum_fee'], 2) }} EUR</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-yellow-600">Maximum:</span>
                        <span class="font-medium">{{ number_format($feeDetails['transaction_fees']['maximum_fee'], 2) }} EUR</span>
                    </div>
                    <div class="border-t pt-2">
                        <div class="flex justify-between font-bold">
                            <span class="text-yellow-800">Total:</span>
                            <span class="text-yellow-900">{{ number_format($feeDetails['transaction_fees']['total'], 2) }} EUR</span>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Frais d'activation -->
            @if(isset($feeDetails['activation_fees']))
            <div class="bg-purple-50 rounded-lg p-4 border border-purple-200">
                <h5 class="text-md font-medium text-purple-800 mb-3">Frais d'Activation</h5>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-sm text-purple-600">Frais de base:</span>
                        <span class="font-medium">{{ number_format($feeDetails['activation_fees']['base_fee'], 2) }} EUR</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-purple-600">Frais uniques:</span>
                        <span class="font-medium">{{ number_format($feeDetails['activation_fees']['one_time_fee'], 2) }} EUR</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-purple-600">Frais d'installation:</span>
                        <span class="font-medium">{{ number_format($feeDetails['activation_fees']['setup_fee'], 2) }} EUR</span>
                    </div>
                    <div class="border-t pt-2">
                        <div class="flex justify-between font-bold">
                            <span class="text-purple-800">Total:</span>
                            <span class="text-purple-900">{{ number_format($feeDetails['activation_fees']['total'], 2) }} EUR</span>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Frais administratifs -->
            @if(isset($feeDetails['admin_fees']))
            <div class="bg-red-50 rounded-lg p-4 border border-red-200">
                <h5 class="text-md font-medium text-red-800 mb-3">Frais Administratifs</h5>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-sm text-red-600">Frais fixes:</span>
                        <span class="font-medium">{{ number_format($feeDetails['admin_fees']['fixed_fee'], 2) }} EUR</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-red-600">Frais %:</span>
                        <span class="font-medium">{{ number_format($feeDetails['admin_fees']['percentage_fee'], 2) }} EUR</span>
                    </div>
                    <div class="border-t pt-2">
                        <div class="flex justify-between font-bold">
                            <span class="text-red-800">Total:</span>
                            <span class="text-red-900">{{ number_format($feeDetails['admin_fees']['total'], 2) }} EUR</span>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Résumé total -->
        @if(isset($feeDetails['summary']))
        <div class="mt-6 bg-gray-50 rounded-lg p-6">
            <h4 class="text-lg font-medium text-gray-900 mb-4">Résumé Total</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($feeDetails['summary']['fee_breakdown'] as $feeType => $amount)
                <div class="bg-white rounded-lg p-4 border">
                    <p class="text-sm text-gray-600">{{ $feeType }}</p>
                    <p class="text-lg font-bold text-gray-900">{{ number_format($amount, 2) }} EUR</p>
                </div>
                @endforeach
            </div>
            <div class="mt-4 pt-4 border-t">
                <div class="flex justify-between items-center">
                    <span class="text-xl font-bold text-gray-900">Part Admin:</span>
                    <span class="text-2xl font-bold text-indigo-600">{{ number_format($feeDetails['summary']['total_fees'], 2) }} EUR</span>
                </div>
                <p class="text-sm text-gray-500 mt-2">
                    @if($feeDetails['summary']['has_fees'])
                        ✅ Des frais sont appliqués pour ce créateur
                    @else
                        ℹ️ Aucun frais n'est appliqué pour ce créateur
                    @endif
                </p>
            </div>
        </div>
        @endif
    </div>
    @endif

    <!-- Résultats en temps réel -->
    <div id="realtime-results" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6 hidden">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Résultats en Temps Réel</h3>
        <div id="realtime-content"></div>
    </div>
</div>

<script>
// Gestion de la sélection du type de créateur
document.getElementById('creator_type').addEventListener('change', function() {
    const creatorType = this.value;
    const creatorSelection = document.getElementById('creator_selection');
    const creatorSelect = document.getElementById('creator_id');
    
    if (creatorType) {
        creatorSelection.classList.remove('hidden');
        creatorSelect.innerHTML = '<option value="">Sélectionner un créateur</option>';
        
        if (creatorType === 'integrator') {
            @foreach($integrators as $integrator)
                creatorSelect.innerHTML += '<option value="{{ $integrator->id }}">{{ $integrator->name }}</option>';
            @endforeach
        } else if (creatorType === 'partner') {
            @foreach($partners as $partner)
                creatorSelect.innerHTML += '<option value="{{ $partner->id }}">{{ $partner->name }}</option>';
            @endforeach
        }
    } else {
        creatorSelection.classList.add('hidden');
    }
});

// Calcul des frais en temps réel
function calculateRealTimeFees() {
    const chargingPointId = document.getElementById('selected_charging_point').value;
    const baseAmount = document.getElementById('base_amount').value;
    
    if (!chargingPointId) {
        alert('Veuillez sélectionner une borne de recharge');
        return;
    }
    
    if (!baseAmount || baseAmount <= 0) {
        alert('Veuillez entrer un montant valide');
        return;
    }
    
    // Afficher un indicateur de chargement
    const resultsDiv = document.getElementById('realtime-results');
    const contentDiv = document.getElementById('realtime-content');
    resultsDiv.classList.remove('hidden');
    contentDiv.innerHTML = '<div class="text-center py-8"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600 mx-auto"></div><p class="mt-2 text-gray-600">Calcul en cours...</p></div>';
    
    // Appel AJAX
    fetch('{{ route("admin.transactions.calculate-fees") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            charging_point_id: chargingPointId,
            base_amount: parseFloat(baseAmount)
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayFeeResults(data.data, baseAmount);
        } else {
            contentDiv.innerHTML = '<div class="text-center py-8 text-red-600">Erreur lors du calcul des frais</div>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        contentDiv.innerHTML = '<div class="text-center py-8 text-red-600">Erreur lors du calcul des frais</div>';
    });
}

// Affichage des résultats
function displayFeeResults(feeData, baseAmount) {
    const contentDiv = document.getElementById('realtime-content');
    
    let html = `
        <div class="mb-4">
            <h4 class="text-md font-medium text-gray-700 mb-2">Montant de base: ${parseFloat(baseAmount).toFixed(2)} EUR</h4>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    `;
    
    // Affichage des différents types de frais
    if (feeData.charging_fees) {
        html += `
            <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                <h5 class="text-md font-medium text-green-800 mb-2">Frais de Recharge</h5>
                <p class="text-2xl font-bold text-green-900">${parseFloat(feeData.charging_fees.total).toFixed(2)} EUR</p>
            </div>
        `;
    }
    
    if (feeData.transaction_fees) {
        html += `
            <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-200">
                <h5 class="text-md font-medium text-yellow-800 mb-2">Frais de Transaction</h5>
                <p class="text-2xl font-bold text-yellow-900">${parseFloat(feeData.transaction_fees.total).toFixed(2)} EUR</p>
            </div>
        `;
    }
    
    if (feeData.activation_fees) {
        html += `
            <div class="bg-purple-50 rounded-lg p-4 border border-purple-200">
                <h5 class="text-md font-medium text-purple-800 mb-2">Frais d'Activation</h5>
                <p class="text-2xl font-bold text-purple-900">${parseFloat(feeData.activation_fees.total).toFixed(2)} EUR</p>
            </div>
        `;
    }
    
    if (feeData.admin_fees) {
        html += `
            <div class="bg-red-50 rounded-lg p-4 border border-red-200">
                <h5 class="text-md font-medium text-red-800 mb-2">Frais Admin</h5>
                <p class="text-2xl font-bold text-red-900">${parseFloat(feeData.admin_fees.total).toFixed(2)} EUR</p>
            </div>
        `;
    }
    
    html += '</div>';
    
    // Total
    if (feeData.summary) {
        const totalFees = feeData.summary.total_fees;
        const totalAmount = parseFloat(baseAmount) + totalFees;
        
        html += `
            <div class="bg-gray-50 rounded-lg p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-center">
                    <div>
                        <p class="text-sm text-gray-600">Montant de base</p>
                        <p class="text-xl font-bold text-gray-900">${parseFloat(baseAmount).toFixed(2)} EUR</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Part Admin</p>
                        <p class="text-xl font-bold text-indigo-600">${parseFloat(totalFees).toFixed(2)} EUR</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Montant total</p>
                        <p class="text-xl font-bold text-green-600">${parseFloat(totalAmount).toFixed(2)} EUR</p>
                    </div>
                </div>
            </div>
        `;
    }
    
    contentDiv.innerHTML = html;
}

// Calcul des frais pour un montant spécifique
function calculateFeesForAmount() {
    const chargingPointId = document.getElementById('charging_point_id').value;
    if (!chargingPointId) {
        alert('Veuillez sélectionner une borne de recharge');
        return;
    }
    
    const amount = prompt('Entrez le montant de base (EUR):', '100');
    if (amount && !isNaN(amount) && amount > 0) {
        document.getElementById('base_amount').value = amount;
        document.getElementById('selected_charging_point').value = chargingPointId;
        calculateRealTimeFees();
    }
}
</script>
@endsection
