@extends('layouts.app')

@section('title', 'Admin Fees Configuration - EVON')
@section('page-title', 'Business Profile Admin Fees')

@push('styles')
<style>
    .gradient-bg {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }
    .fee-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        transition: all 0.3s ease;
    }
    .fee-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }
    .fee-input {
        transition: all 0.2s ease;
    }
    .fee-input:focus {
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    }
    .total-card {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }
</style>
@endpush

@section('content')
@php
    $businessProfileRoutePrefix = $businessProfileRoutePrefix ?? 'business-profiles';
@endphp
<div class="space-y-6">
    <!-- Header Section -->
    <div class="gradient-bg text-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold mb-2">
                    <i class="fas fa-percentage mr-3"></i>
                    Configuration des Frais d'Administration
                </h1>
                <p class="text-green-100">
                    Définissez les frais administrateur pour les profils business attachés aux partenaires et intégrateurs
                </p>
            </div>
        </div>
    </div>

    <!-- Business Profile Selection -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-end space-x-4">
            <div class="flex-1">
<label for="business_profile_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Sélectionner un Profil Business
                </label>
                <select id="business_profile_id" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    <option value="">-- Choisir un profil --</option>
                    @foreach($businessProfiles as $profile)
                        <option value="{{ $profile->id }}" 
                                data-integrator="{{ $profile->integrator_id }}"
                                data-partner="{{ $profile->partner_id }}">
                            {{ $profile->name }}
                            @if($profile->integrator_id) - Intégrateur: {{ $profile->integrator->name ?? 'N/A' }} @endif
                            @if($profile->partner_id) - Partenaire: {{ $profile->partner->name ?? 'N/A' }} @endif
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="button" onclick="loadProfileFees()" class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                <i class="fas fa-search mr-2"></i>Charger
            </button>
        </div>
    </div>

    <!-- Fee Configuration Form -->
    <form id="feeForm" action="" method="POST" class="hidden">
        @csrf
        @method('PUT')
        
        <!-- Fee Configuration Cards -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Admin Fee -->
            <div class="fee-card p-6">
                <div class="flex items-center mb-4">
                    <div class="p-3 bg-indigo-100 rounded-lg mr-4">
                        <i class="fas fa-crown text-indigo-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Frais Admin</h3>
                        <p class="text-sm text-gray-500">Frais facturés par l'administrateur</p>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <div>
                        <label for="admin_fee_fixed" class="block text-sm font-medium text-gray-700 mb-1">Montant fixe (€)</label>
                        <div class="relative">
                            <input type="number" id="admin_fee_fixed" name="admin_fee_fixed" 
                                   step="0.01" min="0" value="0.00"
                                   class="w-full pl-3 pr-10 py-2 border border-gray-300 rounded-lg fee-input"
                                   placeholder="0.00">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <span class="text-gray-500">€</span>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Frais fixes indépendante du montant</p>
                    </div>
                    
                    <div>
                        <label for="admin_fee_percentage" class="block text-sm font-medium text-gray-700 mb-1">Pourcentage (%)</label>
                        <div class="relative">
                            <input type="number" id="admin_fee_percentage" name="admin_fee_percentage" 
                                   step="0.01" min="0" max="100" value="0.00"
                                   class="w-full pl-3 pr-10 py-2 border border-gray-300 rounded-lg fee-input"
                                   placeholder="0.00">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <span class="text-gray-500">%</span>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Pourcentage du montant de la transaction</p>
                    </div>
                    
                    <div class="pt-4 border-t border-gray-200">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Total estimé:</span>
                            <span id="admin_fee_total" class="text-lg font-bold text-indigo-600">0.00 €</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Integrator Fee -->
            <div class="fee-card p-6">
                <div class="flex items-center mb-4">
                    <div class="p-3 bg-purple-100 rounded-lg mr-4">
                        <i class="fas fa-plug text-purple-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Frais Intégrateur</h3>
                        <p class="text-sm text-gray-500">Frais facturés par l'intégrateur</p>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <div>
                        <label for="integrator_fee_fixed" class="block text-sm font-medium text-gray-700 mb-1">Montant fixe (€)</label>
                        <div class="relative">
                            <input type="number" id="integrator_fee_fixed" name="integrator_fee_fixed" 
                                   step="0.01" min="0" value="0.00"
                                   class="w-full pl-3 pr-10 py-2 border border-gray-300 rounded-lg fee-input"
                                   placeholder="0.00">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <span class="text-gray-500">€</span>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <label for="integrator_fee_percentage" class="block text-sm font-medium text-gray-700 mb-1">Pourcentage (%)</label>
                        <div class="relative">
                            <input type="number" id="integrator_fee_percentage" name="integrator_fee_percentage" 
                                   step="0.01" min="0" max="100" value="0.00"
                                   class="w-full pl-3 pr-10 py-2 border border-gray-300 rounded-lg fee-input"
                                   placeholder="0.00">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <span class="text-gray-500">%</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="pt-4 border-t border-gray-200">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Total estimé:</span>
                            <span id="integrator_fee_total" class="text-lg font-bold text-purple-600">0.00 €</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Partner Fee -->
            <div class="fee-card p-6">
                <div class="flex items-center mb-4">
                    <div class="p-3 bg-blue-100 rounded-lg mr-4">
                        <i class="fas fa-handshake text-blue-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Frais Partenaire</h3>
                        <p class="text-sm text-gray-500">Frais facturés par le partenaire</p>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <div>
                        <label for="partner_fee_fixed" class="block text-sm font-medium text-gray-700 mb-1">Montant fixe (€)</label>
                        <div class="relative">
                            <input type="number" id="partner_fee_fixed" name="partner_fee_fixed" 
                                   step="0.01" min="0" value="0.00"
                                   class="w-full pl-3 pr-10 py-2 border border-gray-300 rounded-lg fee-input"
                                   placeholder="0.00">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <span class="text-gray-500">€</span>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <label for="partner_fee_percentage" class="block text-sm font-medium text-gray-700 mb-1">Pourcentage (%)</label>
                        <div class="relative">
                            <input type="number" id="partner_fee_percentage" name="partner_fee_percentage" 
                                   step="0.01" min="0" max="100" value="0.00"
                                   class="w-full pl-3 pr-10 py-2 border border-gray-300 rounded-lg fee-input"
                                   placeholder="0.00">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <span class="text-gray-500">%</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="pt-4 border-t border-gray-200">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Total estimé:</span>
                            <span id="partner_fee_total" class="text-lg font-bold text-blue-600">0.00 €</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transaction Amount Calculator -->
        <div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-calculator mr-2 text-green-500"></i>
                Calculateur de frais
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="test_amount" class="block text-sm font-medium text-gray-700 mb-2">
                        Montant de transaction test (€)
                    </label>
                    <input type="number" id="test_amount" step="0.01" min="0" value="100.00"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                           placeholder="Entrez un montant pour calculer les frais">
                </div>
                
                <div class="total-card rounded-xl p-6">
                    <h4 class="text-sm font-medium mb-4 opacity-90">Récapitulatif des frais</h4>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="opacity-80">Frais Admin:</span>
                            <span id="calc_admin_fee" class="font-bold">0.00 €</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="opacity-80">Frais Intégrateur:</span>
                            <span id="calc_integrator_fee" class="font-bold">0.00 €</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="opacity-80">Frais Partenaire:</span>
                            <span id="calc_partner_fee" class="font-bold">0.00 €</span>
                        </div>
                        <div class="flex justify-between pt-3 border-t border-white border-opacity-30">
                            <span class="font-semibold">Total des frais:</span>
                            <span id="calc_total_fee" class="font-bold text-xl">0.00 €</span>
                        </div>
                        <div class="flex justify-between text-sm opacity-80">
                            <span>Montant net:</span>
                            <span id="calc_net_amount">0.00 €</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Commission Split -->
        <div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-chart-pie mr-2 text-orange-500"></i>
                Répartition des commissions
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="operator_commission" class="block text-sm font-medium text-gray-700 mb-1">Commission Opérateur (%)</label>
                    <input type="number" id="operator_commission" name="operator_commission" 
                           step="0.01" min="0" max="100" value="0.00"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label for="integrator_commission" class="block text-sm font-medium text-gray-700 mb-1">Commission Intégrateur (%)</label>
                    <input type="number" id="integrator_commission" name="integrator_commission" 
                           step="0.01" min="0" max="100" value="0.00"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label for="owner_commission" class="block text-sm font-medium text-gray-700 mb-1">Commission Propriétaire (%)</label>
                    <input type="number" id="owner_commission" name="owner_commission" 
                           step="0.01" min="0" max="100" value="0.00"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label for="partner_commission" class="block text-sm font-medium text-gray-700 mb-1">Commission Partenaire (%)</label>
                    <input type="number" id="partner_commission" name="partner_commission" 
                           step="0.01" min="0" max="100" value="0.00"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
            </div>
            
            <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-700">Total des commissions:</span>
                    <span id="total_commission" class="text-lg font-bold text-orange-600">0.00%</span>
                </div>
                <div class="mt-2">
                    <div class="w-full bg-gray-200 rounded-full h-4">
                        <div id="commission_bar" class="bg-orange-500 h-4 rounded-full" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="mt-8 flex justify-end space-x-4">
            <button type="button" onclick="resetFees()" class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-colors">
                <i class="fas fa-undo mr-2"></i>Réinitialiser
            </button>
            <button type="submit" class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                <i class="fas fa-save mr-2"></i>Enregistrer la configuration
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
let currentProfileId = null;
const updateAdminFeesUrlTemplate = @json(route($businessProfileRoutePrefix . '.update-admin-fees', ['businessProfile' => '__PROFILE__']));

// Currency formatter
const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(value);
};

const parseFloatOrDefault = (value, defaultValue = 0) => {
    const parsed = parseFloat(value);
    return isNaN(parsed) ? defaultValue : parsed;
};

// Load profile fees
function loadProfileFees() {
    const profileId = document.getElementById('business_profile_id').value;
    if (!profileId) {
        alert('Veuillez sélectionner un profil business');
        return;
    }
    
    currentProfileId = profileId;
    document.getElementById('feeForm').action = updateAdminFeesUrlTemplate.replace('__PROFILE__', profileId);
    document.getElementById('feeForm').classList.remove('hidden');
    
    // Fetch profile data (simplified - in production, use fetch or AJAX)
    // For now, show empty form - data should be populated via AJAX
    resetFees();
}

// Reset fees to zero or loaded values
function resetFees() {
    document.getElementById('admin_fee_fixed').value = '0.00';
    document.getElementById('admin_fee_percentage').value = '0.00';
    document.getElementById('integrator_fee_fixed').value = '0.00';
    document.getElementById('integrator_fee_percentage').value = '0.00';
    document.getElementById('partner_fee_fixed').value = '0.00';
    document.getElementById('partner_fee_percentage').value = '0.00';
    document.getElementById('operator_commission').value = '0.00';
    document.getElementById('integrator_commission').value = '0.00';
    document.getElementById('owner_commission').value = '0.00';
    document.getElementById('partner_commission').value = '0.00';
    
    updateCalculations();
}

// Update fee calculations
function updateCalculations() {
    const testAmount = parseFloatOrDefault(document.getElementById('test_amount').value);
    
    // Calculate individual totals
    const adminFixed = parseFloatOrDefault(document.getElementById('admin_fee_fixed').value);
    const adminPercentage = parseFloatOrDefault(document.getElementById('admin_fee_percentage').value);
    const adminTotal = adminFixed + (testAmount * adminPercentage / 100);
    
    const integratorFixed = parseFloatOrDefault(document.getElementById('integrator_fee_fixed').value);
    const integratorPercentage = parseFloatOrDefault(document.getElementById('integrator_fee_percentage').value);
    const integratorTotal = integratorFixed + (testAmount * integratorPercentage / 100);
    
    const partnerFixed = parseFloatOrDefault(document.getElementById('partner_fee_fixed').value);
    const partnerPercentage = parseFloatOrDefault(document.getElementById('partner_fee_percentage').value);
    const partnerTotal = partnerFixed + (testAmount * partnerPercentage / 100);
    
    // Update displays
    document.getElementById('admin_fee_total').textContent = formatCurrency(adminTotal);
    document.getElementById('integrator_fee_total').textContent = formatCurrency(integratorTotal);
    document.getElementById('partner_fee_total').textContent = formatCurrency(partnerTotal);
    
    // Update calculator
    document.getElementById('calc_admin_fee').textContent = formatCurrency(adminTotal);
    document.getElementById('calc_integrator_fee').textContent = formatCurrency(integratorTotal);
    document.getElementById('calc_partner_fee').textContent = formatCurrency(partnerTotal);
    
    const totalFee = adminTotal + integratorTotal + partnerTotal;
    document.getElementById('calc_total_fee').textContent = formatCurrency(totalFee);
    document.getElementById('calc_net_amount').textContent = formatCurrency(testAmount - totalFee);
    
    // Calculate commission
    const operatorCommission = parseFloatOrDefault(document.getElementById('operator_commission').value);
    const integratorCommission = parseFloatOrDefault(document.getElementById('integrator_commission').value);
    const ownerCommission = parseFloatOrDefault(document.getElementById('owner_commission').value);
    const partnerCommission = parseFloatOrDefault(document.getElementById('partner_commission').value);
    
    const totalCommission = operatorCommission + integratorCommission + ownerCommission + partnerCommission;
    document.getElementById('total_commission').textContent = totalCommission.toFixed(2) + '%';
    document.getElementById('commission_bar').style.width = Math.min(totalCommission, 100) + '%';
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Fee input changes
    const feeInputs = [
        'admin_fee_fixed', 'admin_fee_percentage',
        'integrator_fee_fixed', 'integrator_fee_percentage',
        'partner_fee_fixed', 'partner_fee_percentage',
        'operator_commission', 'integrator_commission',
        'owner_commission', 'partner_commission',
        'test_amount'
    ];
    
    feeInputs.forEach(inputId => {
        const input = document.getElementById(inputId);
        if (input) {
            input.addEventListener('input', updateCalculations);
        }
    });
    
    // Initial calculation
    updateCalculations();
});
</script>
@endpush
