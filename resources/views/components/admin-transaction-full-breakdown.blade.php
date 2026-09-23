@php
    // PRIORITÉ : TransactionDetail (source de vérité)
    $transactionDetail = $transaction->transactionDetail ?? null;
    $calculationDetails = $transactionDetail->calculation_details ?? [];
    
    // Business Profiles utilisés
    $businessProfiles = [];
    if (!empty($calculationDetails['business_profiles'])) {
        if (isset($calculationDetails['business_profiles']['admin_integrator_id'])) {
            $businessProfiles['admin_integrator'] = \App\Models\BusinessProfile::with('owner')->find($calculationDetails['business_profiles']['admin_integrator_id']);
        }
        if (isset($calculationDetails['business_profiles']['integrator_operator_id'])) {
            $businessProfiles['integrator_operator'] = \App\Models\BusinessProfile::with('owner')->find($calculationDetails['business_profiles']['integrator_operator_id']);
        }
    }
    
    // Breakdown des frais Admin
    $adminBreakdown = $calculationDetails['admin_fees_breakdown'] ?? [];
    $adminFixed = $calculationDetails['admin_fixed'] ?? 0;
    
    // Breakdown des frais Intégrateur
    $integratorBreakdown = $calculationDetails['integrator_fees_breakdown'] ?? [];
    $integratorFixed = $calculationDetails['integrator_fixed'] ?? 0;
    
    // Hiérarchie
    $hierarchy = $calculationDetails['hierarchy'] ?? [];
    
    // Montants
    $totalAmount = $transaction->amount ?? $transaction->price_total ?? 0;
    $adminShare = $transactionDetail->admin_share_amount ?? 0;
    $integratorShare = $transactionDetail->integrator_share_amount ?? 0;
    $operatorShare = $transactionDetail->operator_share_amount ?? 0;
@endphp

@if($transactionDetail && !empty($calculationDetails))
<div class="card border-0 shadow-lg mb-4">
    <div class="card-header bg-gradient-primary text-white py-3">
        <h5 class="mb-0">
            <i class="fas fa-calculator me-2"></i>
            Breakdown Complet du Calcul Transactionnel
            <span class="badge bg-light text-primary ms-2">
                <i class="fas fa-check-circle"></i> Données Réelles
            </span>
        </h5>
    </div>
    <div class="card-body">
        
        {{-- MONTANT TOTAL DE LA TRANSACTION --}}
        <div class="alert alert-primary mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-1"><i class="fas fa-money-bill-wave me-2"></i>Montant Total de la Transaction</h6>
                    <span class="text-muted">Montant de base pour tous les calculs</span>
                </div>
                <div class="text-end">
                    <h4 class="mb-0 fw-bold text-primary">{{ number_format($totalAmount, 2) }} {{ $transaction->currency ?? 'EUR' }}</h4>
                </div>
            </div>
        </div>

        {{-- BUSINESS PROFILES UTILISÉS --}}
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card border-primary h-100">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-arrow-right me-2"></i>
                            Business Profile Admin → Intégrateur
                        </h6>
                    </div>
                    <div class="card-body">
                        @if($businessProfiles['admin_integrator'] ?? null)
                            <div class="mb-2">
                                <strong>Nom:</strong> 
                                <a href="{{ route('business-profiles.show', $businessProfiles['admin_integrator']->id) }}" class="text-primary">
                                    {{ $businessProfiles['admin_integrator']->name }}
                                </a>
                            </div>
                            <div class="mb-2">
                                <strong>ID:</strong> 
                                <code>{{ $businessProfiles['admin_integrator']->id }}</code>
                            </div>
                            <div class="mb-2">
                                <strong>Propriétaire:</strong> 
                                {{ $businessProfiles['admin_integrator']->owner->name ?? 'N/A' }}
                            </div>
                            <div>
                                <strong>Méthode de calcul:</strong>
                                <span class="badge bg-info">Variables du Business Profile</span>
                            </div>
                        @else
                            <span class="text-muted">Business Profile non trouvé</span>
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card border-info h-100">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-arrow-right me-2"></i>
                            Business Profile Intégrateur → Opérateur
                        </h6>
                    </div>
                    <div class="card-body">
                        @if($businessProfiles['integrator_operator'] ?? null)
                            <div class="mb-2">
                                <strong>Nom:</strong> 
                                <a href="{{ route('business-profiles.show', $businessProfiles['integrator_operator']->id) }}" class="text-info">
                                    {{ $businessProfiles['integrator_operator']->name }}
                                </a>
                            </div>
                            <div class="mb-2">
                                <strong>ID:</strong> 
                                <code>{{ $businessProfiles['integrator_operator']->id }}</code>
                            </div>
                            <div class="mb-2">
                                <strong>Propriétaire:</strong> 
                                {{ $businessProfiles['integrator_operator']->owner->name ?? 'N/A' }}
                            </div>
                            <div>
                                <strong>Méthode de calcul:</strong>
                                <span class="badge bg-info">Variables du Business Profile</span>
                            </div>
                        @else
                            <span class="text-muted">Business Profile non trouvé</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- CALCUL DÉTAILLÉ DES FRAIS ADMIN --}}
        <div class="card border-danger mb-4">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">
                    <i class="fas fa-user-shield me-2"></i>
                    Calcul Détaillé - Part Admin
                    <span class="badge bg-light text-danger ms-2">
                        {{ number_format($adminShare, 2) }} {{ $transaction->currency ?? 'EUR' }}
                    </span>
                </h5>
            </div>
            <div class="card-body">
                @if(!empty($adminBreakdown))
                    {{-- Variables utilisées depuis le Business Profile --}}
                    <div class="mb-4">
                        <h6 class="text-danger mb-3">
                            <i class="fas fa-database me-2"></i>
                            Variables Récupérées du Business Profile Admin→Intégrateur
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Variable</th>
                                        <th>Valeur</th>
                                        <th>Source</th>
                                        <th>Type</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if($businessProfiles['admin_integrator'] ?? null)
                                        <tr>
                                            <td><code>base_fee_amount</code></td>
                                            <td><strong>{{ number_format($businessProfiles['admin_integrator']->base_fee_amount ?? 0, 2) }}</strong></td>
                                            <td>Business Profile #{{ $businessProfiles['admin_integrator']->id }}</td>
                                            <td><span class="badge bg-secondary">Frais Fixe</span></td>
                                        </tr>
                                        @php
                                            $adminTransactionConfig = is_string($businessProfiles['admin_integrator']->transaction_fee_config ?? '') 
                                                ? json_decode($businessProfiles['admin_integrator']->transaction_fee_config, true) 
                                                : ($businessProfiles['admin_integrator']->transaction_fee_config ?? []);
                                        @endphp
                                        @if(!empty($adminTransactionConfig))
                                            <tr>
                                                <td><code>transaction_fee_config.fixed_amount</code></td>
                                                <td><strong>{{ number_format($adminTransactionConfig['fixed_amount'] ?? 0, 2) }}</strong></td>
                                                <td>Business Profile #{{ $businessProfiles['admin_integrator']->id }}</td>
                                                <td><span class="badge bg-secondary">Frais Fixe</span></td>
                                            </tr>
                                            <tr>
                                                <td><code>transaction_fee_config.percentage</code></td>
                                                <td><strong>{{ number_format($adminTransactionConfig['percentage'] ?? 0, 2) }}%</strong></td>
                                                <td>Business Profile #{{ $businessProfiles['admin_integrator']->id }}</td>
                                                <td><span class="badge bg-warning">Pourcentage</span></td>
                                            </tr>
                                            <tr>
                                                <td><code>transaction_fee_config.per_kwh_fee</code></td>
                                                <td><strong>{{ number_format($adminTransactionConfig['per_kwh_fee'] ?? 0, 4) }}</strong></td>
                                                <td>Business Profile #{{ $businessProfiles['admin_integrator']->id }}</td>
                                                <td><span class="badge bg-info">Par kWh</span></td>
                                            </tr>
                                            <tr>
                                                <td><code>transaction_fee_config.per_minute_fee</code></td>
                                                <td><strong>{{ number_format($adminTransactionConfig['per_minute_fee'] ?? 0, 4) }}</strong></td>
                                                <td>Business Profile #{{ $businessProfiles['admin_integrator']->id }}</td>
                                                <td><span class="badge bg-info">Par Minute</span></td>
                                            </tr>
                                        @endif
                                        @php
                                            $adminChargeConfig = is_string($businessProfiles['admin_integrator']->charge_fee_config ?? '') 
                                                ? json_decode($businessProfiles['admin_integrator']->charge_fee_config, true) 
                                                : ($businessProfiles['admin_integrator']->charge_fee_config ?? []);
                                        @endphp
                                        @if(!empty($adminChargeConfig))
                                            <tr>
                                                <td><code>charge_fee_config.fixed_amount</code></td>
                                                <td><strong>{{ number_format($adminChargeConfig['fixed_amount'] ?? 0, 2) }}</strong></td>
                                                <td>Business Profile #{{ $businessProfiles['admin_integrator']->id }}</td>
                                                <td><span class="badge bg-secondary">Frais Fixe</span></td>
                                            </tr>
                                            <tr>
                                                <td><code>charge_fee_config.percentage</code></td>
                                                <td><strong>{{ number_format($adminChargeConfig['percentage'] ?? 0, 2) }}%</strong></td>
                                                <td>Business Profile #{{ $businessProfiles['admin_integrator']->id }}</td>
                                                <td><span class="badge bg-warning">Pourcentage</span></td>
                                            </tr>
                                        @endif
                                        <tr>
                                            <td><code>admin_fee_fixed</code></td>
                                            <td><strong>{{ number_format($businessProfiles['admin_integrator']->admin_fee_fixed ?? 0, 2) }}</strong></td>
                                            <td>Business Profile #{{ $businessProfiles['admin_integrator']->id }}</td>
                                            <td><span class="badge bg-secondary">Frais Fixe</span></td>
                                        </tr>
                                        <tr>
                                            <td><code>admin_fee_percentage</code></td>
                                            <td><strong>{{ number_format($businessProfiles['admin_integrator']->admin_fee_percentage ?? 0, 2) }}%</strong></td>
                                            <td>Business Profile #{{ $businessProfiles['admin_integrator']->id }}</td>
                                            <td><span class="badge bg-warning">Pourcentage</span></td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Breakdown du calcul Admin --}}
                    <div class="mb-4">
                        <h6 class="text-danger mb-3">
                            <i class="fas fa-calculator me-2"></i>
                            Breakdown du Calcul - Étape par Étape
                        </h6>
                        <div class="calculation-steps">
                            {{-- Frais d'activation -- Toujours afficher même si à zéro --}}
                            @php
                                $activationFee = $adminBreakdown['activation_fee'] ?? 0;
                                $isActivationFeeZero = $activationFee == 0;
                            @endphp
                            <div class="calculation-step mb-3 p-3 {{ $isActivationFeeZero ? 'bg-secondary bg-opacity-10' : 'bg-light' }} rounded {{ $isActivationFeeZero ? 'border border-warning' : '' }}">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <strong class="{{ $isActivationFeeZero ? 'text-warning' : 'text-danger' }}">1. Frais d'Activation</strong>
                                        <small class="d-block text-muted">Source: <code>base_fee_amount</code></small>
                                        @if($isActivationFeeZero)
                                            <small class="d-block text-warning mt-1">
                                                <i class="fas fa-info-circle"></i> Le Business Profile n'a pas de frais d'activation configuré
                                            </small>
                                        @endif
                                    </div>
                                    <span class="badge {{ $isActivationFeeZero ? 'bg-warning text-dark' : 'bg-danger' }} fs-6">{{ number_format($activationFee, 2) }} {{ $transaction->currency ?? 'EUR' }}</span>
                                </div>
                                <div class="calculation-formula bg-white p-2 rounded border">
                                    <code class="text-primary">
                                        activation_fee = base_fee_amount = {{ number_format($adminBreakdown['breakdown']['activation']['amount'] ?? 0, 2) }}
                                    </code>
                                </div>
                            </div>

                            {{-- Frais de transaction -- Toujours afficher même si à zéro --}}
                            @php
                                $transactionFee = $adminBreakdown['transaction_fee'] ?? 0;
                                $transBreakdown = $adminBreakdown['breakdown']['transaction'] ?? [
                                    'fixed_amount' => 0,
                                    'percentage' => 0
                                ];
                                $isTransactionFeeZero = $transactionFee == 0;
                            @endphp
                            <div class="calculation-step mb-3 p-3 {{ $isTransactionFeeZero ? 'bg-secondary bg-opacity-10' : 'bg-light' }} rounded {{ $isTransactionFeeZero ? 'border border-warning' : '' }}">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <strong class="{{ $isTransactionFeeZero ? 'text-warning' : 'text-danger' }}">2. Frais de Transaction</strong>
                                        <small class="d-block text-muted">Source: <code>transaction_fee_config</code></small>
                                        @if($isTransactionFeeZero)
                                            <small class="d-block text-warning mt-1">
                                                <i class="fas fa-info-circle"></i> Le Business Profile n'a pas de frais de transaction configuré
                                            </small>
                                        @endif
                                    </div>
                                    <span class="badge {{ $isTransactionFeeZero ? 'bg-warning text-dark' : 'bg-danger' }} fs-6">{{ number_format($transactionFee, 2) }} {{ $transaction->currency ?? 'EUR' }}</span>
                                </div>
                                <div class="calculation-formula bg-white p-2 rounded border mb-2">
                                    <code class="text-primary">
                                        transaction_fee = fixed_amount + (total_amount × percentage / 100)
                                    </code>
                                    <br>
                                    <code>
                                        = {{ number_format($transBreakdown['fixed_amount'] ?? 0, 2) }} + ({{ number_format($totalAmount, 2) }} × {{ number_format($transBreakdown['percentage'] ?? 0, 2) }}% / 100)
                                    </code>
                                    <br>
                                    <code class="{{ $isTransactionFeeZero ? 'text-warning' : 'text-success' }}">
                                        = {{ number_format($transBreakdown['fixed_amount'] ?? 0, 2) }} + {{ number_format(($totalAmount * ($transBreakdown['percentage'] ?? 0) / 100), 2) }}
                                        = <strong>{{ number_format($transactionFee, 2) }}</strong>
                                    </code>
                                </div>
                                @if(isset($adminBreakdown['calculation_details']['transaction']['formula']))
                                    <div class="alert alert-info mb-0">
                                        <small><strong>Formule stockée:</strong> {{ $adminBreakdown['calculation_details']['transaction']['formula'] }}</small>
                                    </div>
                                @endif
                            </div>

                            {{-- Frais de recharge -- Toujours afficher même si à zéro --}}
                            @php
                                $chargeFee = $adminBreakdown['charge_fee'] ?? 0;
                                $chargeBreakdown = $adminBreakdown['breakdown']['charge'] ?? [
                                    'fixed_amount' => 0,
                                    'percentage' => 0
                                ];
                                $isChargeFeeZero = $chargeFee == 0;
                            @endphp
                            <div class="calculation-step mb-3 p-3 {{ $isChargeFeeZero ? 'bg-secondary bg-opacity-10' : 'bg-light' }} rounded {{ $isChargeFeeZero ? 'border border-warning' : '' }}">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <strong class="{{ $isChargeFeeZero ? 'text-warning' : 'text-danger' }}">3. Frais de Recharge</strong>
                                        <small class="d-block text-muted">Source: <code>charge_fee_config</code></small>
                                        @if($isChargeFeeZero)
                                            <small class="d-block text-warning mt-1">
                                                <i class="fas fa-info-circle"></i> Le Business Profile n'a pas de frais de recharge configuré
                                            </small>
                                        @endif
                                    </div>
                                    <span class="badge {{ $isChargeFeeZero ? 'bg-warning text-dark' : 'bg-danger' }} fs-6">{{ number_format($chargeFee, 2) }} {{ $transaction->currency ?? 'EUR' }}</span>
                                </div>
                                <div class="calculation-formula bg-white p-2 rounded border mb-2">
                                    <code class="text-primary">
                                        charge_fee = fixed_amount + (total_amount × percentage / 100)
                                    </code>
                                    <br>
                                    <code>
                                        = {{ number_format($chargeBreakdown['fixed_amount'] ?? 0, 2) }} + ({{ number_format($totalAmount, 2) }} × {{ number_format($chargeBreakdown['percentage'] ?? 0, 2) }}% / 100)
                                    </code>
                                    <br>
                                    <code class="{{ $isChargeFeeZero ? 'text-warning' : 'text-success' }}">
                                        = {{ number_format($chargeBreakdown['fixed_amount'] ?? 0, 2) }} + {{ number_format(($totalAmount * ($chargeBreakdown['percentage'] ?? 0) / 100), 2) }}
                                        = <strong>{{ number_format($chargeFee, 2) }}</strong>
                                    </code>
                                </div>
                                @if(isset($adminBreakdown['calculation_details']['charge']['formula']))
                                    <div class="alert alert-info mb-0">
                                        <small><strong>Formule stockée:</strong> {{ $adminBreakdown['calculation_details']['charge']['formula'] }}</small>
                                    </div>
                                @endif
                            </div>

                            {{-- Frais spécifiques Admin -- Toujours afficher même si à zéro --}}
                            @php
                                $adminRoleBreakdown = $adminBreakdown['breakdown']['admin'] ?? [
                                    'fixed_amount' => $adminBreakdown['fixed_used'] ?? 0,
                                    'percentage' => $adminBreakdown['percentage_used'] ?? 0,
                                    'calculated' => $adminBreakdown['role_fee'] ?? 0
                                ];
                                $adminRoleFee = $adminBreakdown['role_fee'] ?? 0;
                                $isAdminRoleFeeZero = $adminRoleFee == 0;
                            @endphp
                            <div class="calculation-step mb-3 p-3 {{ $isAdminRoleFeeZero ? 'bg-secondary bg-opacity-10' : 'bg-light' }} rounded {{ $isAdminRoleFeeZero ? 'border border-warning' : '' }}">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <strong class="{{ $isAdminRoleFeeZero ? 'text-warning' : 'text-danger' }}">4. Frais Spécifiques Admin</strong>
                                        <small class="d-block text-muted">Source: <code>admin_fee_fixed</code> + <code>admin_fee_percentage</code></small>
                                        @if($isAdminRoleFeeZero)
                                            <small class="d-block text-warning mt-1">
                                                <i class="fas fa-info-circle"></i> Les Business Profiles utilisés ont des frais Admin à zéro
                                            </small>
                                        @endif
                                    </div>
                                    <span class="badge {{ $isAdminRoleFeeZero ? 'bg-warning text-dark' : 'bg-danger' }} fs-6">{{ number_format($adminRoleFee, 2) }} {{ $transaction->currency ?? 'EUR' }}</span>
                                </div>
                                <div class="calculation-formula bg-white p-2 rounded border mb-2">
                                    <code class="text-primary">
                                        admin_role_fee = admin_fee_fixed + (total_amount × admin_fee_percentage / 100)
                                    </code>
                                    <br>
                                    <code>
                                        = {{ number_format($adminRoleBreakdown['fixed_amount'] ?? 0, 2) }} + ({{ number_format($totalAmount, 2) }} × {{ number_format($adminRoleBreakdown['percentage'] ?? 0, 2) }}% / 100)
                                    </code>
                                    <br>
                                    <code class="{{ $isAdminRoleFeeZero ? 'text-warning' : 'text-success' }}">
                                        = {{ number_format($adminRoleBreakdown['fixed_amount'] ?? 0, 2) }} + {{ number_format(($totalAmount * ($adminRoleBreakdown['percentage'] ?? 0) / 100), 2) }}
                                        = <strong>{{ number_format($adminRoleFee, 2) }}</strong>
                                    </code>
                                </div>
                                @if(isset($adminBreakdown['calculation_details']['admin']['formula']))
                                    <div class="alert alert-info mb-0">
                                        <small><strong>Formule stockée:</strong> {{ $adminBreakdown['calculation_details']['admin']['formula'] }}</small>
                                    </div>
                                @endif
                            </div>

                            {{-- Total des frais Admin --}}
                            <div class="calculation-step mb-3 p-4 bg-danger bg-opacity-10 rounded border border-danger">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong class="text-danger fs-5">TOTAL PART ADMIN</strong>
                                        <small class="d-block text-muted">Somme de tous les frais calculés</small>
                                    </div>
                                    <div class="text-end">
                                        <code class="fs-6 text-danger">
                                            total_admin_fees = activation_fee + transaction_fee + charge_fee + role_fee
                                        </code>
                                        <br>
                                        <h4 class="mb-0 fw-bold text-danger">
                                            = {{ number_format($adminShare, 2) }} {{ $transaction->currency ?? 'EUR' }}
                                        </h4>
                                    </div>
                                </div>
                                <div class="mt-3 p-2 bg-white rounded">
                                    <code>
                                        = {{ number_format($adminBreakdown['activation_fee'] ?? 0, 2) }}
                                        + {{ number_format($adminBreakdown['transaction_fee'] ?? 0, 2) }}
                                        + {{ number_format($adminBreakdown['charge_fee'] ?? 0, 2) }}
                                        + {{ number_format($adminBreakdown['role_fee'] ?? 0, 2) }}
                                        = <strong>{{ number_format($adminBreakdown['total_fees'] ?? $adminShare, 2) }}</strong>
                                    </code>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Breakdown des frais Admin non disponible dans calculation_details
                    </div>
                @endif
            </div>
        </div>

        {{-- CALCUL DÉTAILLÉ DES FRAIS INTÉGRATEUR --}}
        <div class="card border-info mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-user-tie me-2"></i>
                    Calcul Détaillé - Part Intégrateur
                    <span class="badge bg-light text-info ms-2">
                        {{ number_format($integratorShare, 2) }} {{ $transaction->currency ?? 'EUR' }}
                    </span>
                </h5>
            </div>
            <div class="card-body">
                @if(!empty($integratorBreakdown))
                    {{-- Variables utilisées depuis le Business Profile --}}
                    <div class="mb-4">
                        <h6 class="text-info mb-3">
                            <i class="fas fa-database me-2"></i>
                            Variables Récupérées du Business Profile Intégrateur→Opérateur
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Variable</th>
                                        <th>Valeur</th>
                                        <th>Source</th>
                                        <th>Type</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if($businessProfiles['integrator_operator'] ?? null)
                                        <tr>
                                            <td><code>base_fee_amount</code></td>
                                            <td><strong>{{ number_format($businessProfiles['integrator_operator']->base_fee_amount ?? 0, 2) }}</strong></td>
                                            <td>Business Profile #{{ $businessProfiles['integrator_operator']->id }}</td>
                                            <td><span class="badge bg-secondary">Frais Fixe</span></td>
                                        </tr>
                                        @php
                                            $integratorTransactionConfig = is_string($businessProfiles['integrator_operator']->transaction_fee_config ?? '') 
                                                ? json_decode($businessProfiles['integrator_operator']->transaction_fee_config, true) 
                                                : ($businessProfiles['integrator_operator']->transaction_fee_config ?? []);
                                        @endphp
                                        @if(!empty($integratorTransactionConfig))
                                            <tr>
                                                <td><code>transaction_fee_config.fixed_amount</code></td>
                                                <td><strong>{{ number_format($integratorTransactionConfig['fixed_amount'] ?? 0, 2) }}</strong></td>
                                                <td>Business Profile #{{ $businessProfiles['integrator_operator']->id }}</td>
                                                <td><span class="badge bg-secondary">Frais Fixe</span></td>
                                            </tr>
                                            <tr>
                                                <td><code>transaction_fee_config.percentage</code></td>
                                                <td><strong>{{ number_format($integratorTransactionConfig['percentage'] ?? 0, 2) }}%</strong></td>
                                                <td>Business Profile #{{ $businessProfiles['integrator_operator']->id }}</td>
                                                <td><span class="badge bg-warning">Pourcentage</span></td>
                                            </tr>
                                        @endif
                                        <tr>
                                            <td><code>integrator_fee_fixed</code></td>
                                            <td><strong>{{ number_format($businessProfiles['integrator_operator']->integrator_fee_fixed ?? 0, 2) }}</strong></td>
                                            <td>Business Profile #{{ $businessProfiles['integrator_operator']->id }}</td>
                                            <td><span class="badge bg-secondary">Frais Fixe</span></td>
                                        </tr>
                                        <tr>
                                            <td><code>integrator_fee_percentage</code></td>
                                            <td><strong>{{ number_format($businessProfiles['integrator_operator']->integrator_fee_percentage ?? 0, 2) }}%</strong></td>
                                            <td>Business Profile #{{ $businessProfiles['integrator_operator']->id }}</td>
                                            <td><span class="badge bg-warning">Pourcentage</span></td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Breakdown du calcul Intégrateur (similaire à Admin mais pour Intégrateur) --}}
                    <div class="mb-4">
                        <h6 class="text-info mb-3">
                            <i class="fas fa-calculator me-2"></i>
                            Breakdown du Calcul - Étape par Étape
                        </h6>
                        <div class="calculation-steps">
                            {{-- Frais d'activation Intégrateur -- Toujours afficher même si à zéro --}}
                            @php
                                $integratorActivationFee = $integratorBreakdown['activation_fee'] ?? 0;
                                $isIntegratorActivationFeeZero = $integratorActivationFee == 0;
                            @endphp
                            <div class="calculation-step mb-3 p-3 {{ $isIntegratorActivationFeeZero ? 'bg-secondary bg-opacity-10' : 'bg-light' }} rounded {{ $isIntegratorActivationFeeZero ? 'border border-warning' : '' }}">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <strong class="{{ $isIntegratorActivationFeeZero ? 'text-warning' : 'text-info' }}">1. Frais d'Activation</strong>
                                        <small class="d-block text-muted">Source: <code>base_fee_amount</code></small>
                                        @if($isIntegratorActivationFeeZero)
                                            <small class="d-block text-warning mt-1">
                                                <i class="fas fa-info-circle"></i> Le Business Profile n'a pas de frais d'activation configuré
                                            </small>
                                        @endif
                                    </div>
                                    <span class="badge {{ $isIntegratorActivationFeeZero ? 'bg-warning text-dark' : 'bg-info' }} fs-6">{{ number_format($integratorActivationFee, 2) }} {{ $transaction->currency ?? 'EUR' }}</span>
                                </div>
                                <div class="calculation-formula bg-white p-2 rounded border">
                                    <code class="text-primary">
                                        activation_fee = base_fee_amount = {{ number_format($integratorBreakdown['breakdown']['activation']['amount'] ?? 0, 2) }}
                                    </code>
                                </div>
                            </div>

                            {{-- Frais de transaction Intégrateur -- Toujours afficher même si à zéro --}}
                            @php
                                $integratorTransactionFee = $integratorBreakdown['transaction_fee'] ?? 0;
                                $integratorTransBreakdown = $integratorBreakdown['breakdown']['transaction'] ?? [
                                    'fixed_amount' => 0,
                                    'percentage' => 0
                                ];
                                $isIntegratorTransactionFeeZero = $integratorTransactionFee == 0;
                            @endphp
                            <div class="calculation-step mb-3 p-3 {{ $isIntegratorTransactionFeeZero ? 'bg-secondary bg-opacity-10' : 'bg-light' }} rounded {{ $isIntegratorTransactionFeeZero ? 'border border-warning' : '' }}">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <strong class="{{ $isIntegratorTransactionFeeZero ? 'text-warning' : 'text-info' }}">2. Frais de Transaction</strong>
                                        <small class="d-block text-muted">Source: <code>transaction_fee_config</code></small>
                                        @if($isIntegratorTransactionFeeZero)
                                            <small class="d-block text-warning mt-1">
                                                <i class="fas fa-info-circle"></i> Le Business Profile n'a pas de frais de transaction configuré
                                            </small>
                                        @endif
                                    </div>
                                    <span class="badge {{ $isIntegratorTransactionFeeZero ? 'bg-warning text-dark' : 'bg-info' }} fs-6">{{ number_format($integratorTransactionFee, 2) }} {{ $transaction->currency ?? 'EUR' }}</span>
                                </div>
                                <div class="calculation-formula bg-white p-2 rounded border mb-2">
                                    <code class="text-primary">
                                        transaction_fee = fixed_amount + (total_amount × percentage / 100)
                                    </code>
                                    <br>
                                    <code>
                                        = {{ number_format($integratorTransBreakdown['fixed_amount'] ?? 0, 2) }} + ({{ number_format($totalAmount, 2) }} × {{ number_format($integratorTransBreakdown['percentage'] ?? 0, 2) }}% / 100)
                                    </code>
                                    <br>
                                    <code class="{{ $isIntegratorTransactionFeeZero ? 'text-warning' : 'text-success' }}">
                                        = {{ number_format($integratorTransBreakdown['fixed_amount'] ?? 0, 2) }} + {{ number_format(($totalAmount * ($integratorTransBreakdown['percentage'] ?? 0) / 100), 2) }}
                                        = <strong>{{ number_format($integratorTransactionFee, 2) }}</strong>
                                    </code>
                                </div>
                            </div>

                            {{-- Frais spécifiques Intégrateur - Toujours afficher même si à zéro --}}
                            @php
                                $integratorRoleFee = $integratorBreakdown['role_fee'] ?? 0;
                                $integratorBrutBreakdown = $integratorBreakdown['breakdown']['integrator_brut'] ?? null;
                                $integratorRoleBreakdown = $integratorBreakdown['breakdown']['integrator'] ?? ($integratorBrutBreakdown ?? [
                                    'fixed_amount' => $integratorBreakdown['fixed_used'] ?? 0,
                                    'percentage' => $integratorBreakdown['percentage_used'] ?? 0,
                                    'calculated' => $integratorBreakdown['integrator_fees_brut'] ?? 0
                                ]);
                                $isIntegratorRoleFeeZero = ($integratorBreakdown['integrator_fees_brut'] ?? 0) == 0;
                            @endphp
                            <div class="calculation-step mb-3 p-3 {{ $isIntegratorRoleFeeZero ? 'bg-secondary bg-opacity-10' : 'bg-light' }} rounded {{ $isIntegratorRoleFeeZero ? 'border border-warning' : '' }}">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <strong class="{{ $isIntegratorRoleFeeZero ? 'text-warning' : 'text-info' }}">3. Frais Spécifiques Intégrateur (Brut)</strong>
                                        <small class="d-block text-muted">Source: <code>integrator_fee_fixed</code> + <code>integrator_fee_percentage</code></small>
                                        @if($isIntegratorRoleFeeZero)
                                            <small class="d-block text-warning mt-1">
                                                <i class="fas fa-info-circle"></i> Les Business Profiles utilisés ont des frais Intégrateur à zéro
                                            </small>
                                        @endif
                                    </div>
                                    <span class="badge {{ $isIntegratorRoleFeeZero ? 'bg-warning text-dark' : 'bg-info' }} fs-6">{{ number_format($integratorBreakdown['integrator_fees_brut'] ?? 0, 2) }} {{ $transaction->currency ?? 'EUR' }}</span>
                                </div>
                                <div class="calculation-formula bg-white p-2 rounded border mb-2">
                                    <code class="text-primary">
                                        integrator_fees_brut = integrator_fee_fixed + (total_amount × integrator_fee_percentage / 100)
                                    </code>
                                    <br>
                                    <code>
                                        = {{ number_format($integratorRoleBreakdown['fixed_amount'] ?? 0, 2) }} + ({{ number_format($totalAmount, 2) }} × {{ number_format($integratorRoleBreakdown['percentage'] ?? 0, 2) }}% / 100)
                                    </code>
                                    <br>
                                    <code class="{{ $isIntegratorRoleFeeZero ? 'text-warning' : 'text-success' }}">
                                        = {{ number_format($integratorRoleBreakdown['fixed_amount'] ?? 0, 2) }} + {{ number_format(($totalAmount * ($integratorRoleBreakdown['percentage'] ?? 0) / 100), 2) }}
                                        = <strong>{{ number_format($integratorBreakdown['integrator_fees_brut'] ?? 0, 2) }}</strong>
                                    </code>
                                </div>
                                @if(isset($integratorBreakdown['calculation_details']['integrator_brut']['formula']))
                                    <div class="alert alert-info mb-0">
                                        <small><strong>Formule stockée:</strong> {{ $integratorBreakdown['calculation_details']['integrator_brut']['formula'] }}</small>
                                    </div>
                                @endif
                            </div>
                            
                            {{-- Part Intégrateur Nette (après déduction admin) --}}
                            @if(isset($integratorBreakdown['breakdown']['integrator_net']))
                                @php
                                    $integratorNetBreakdown = $integratorBreakdown['breakdown']['integrator_net'];
                                @endphp
                                <div class="calculation-step mb-3 p-3 bg-light rounded">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <strong class="text-info">4. Part Intégrateur Nette</strong>
                                            <small class="d-block text-muted">Source: <code>integrator_fees_brut - admin_share</code></small>
                                        </div>
                                        <span class="badge bg-info fs-6">{{ number_format($integratorShare, 2) }} {{ $transaction->currency ?? 'EUR' }}</span>
                                    </div>
                                    <div class="calculation-formula bg-white p-2 rounded border mb-2">
                                        <code class="text-primary">
                                            integrator_share_net = integrator_fees_brut - admin_share
                                        </code>
                                        <br>
                                        <code>
                                            = {{ number_format($integratorBreakdown['integrator_fees_brut'] ?? 0, 2) }} - {{ number_format($adminShare, 2) }}
                                        </code>
                                        <br>
                                        <code class="text-success">
                                            = <strong>{{ number_format($integratorShare, 2) }}</strong>
                                        </code>
                                    </div>
                                    @if(isset($integratorBreakdown['calculation_details']['integrator_net']['formula']))
                                        <div class="alert alert-info mb-0">
                                            <small><strong>Formule stockée:</strong> {{ $integratorBreakdown['calculation_details']['integrator_net']['formula'] }}</small>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            {{-- Total des frais Intégrateur --}}
                            <div class="calculation-step mb-3 p-4 bg-info bg-opacity-10 rounded border border-info">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong class="text-info fs-5">TOTAL PART INTÉGRATEUR</strong>
                                        <small class="d-block text-muted">Somme de tous les frais calculés</small>
                                    </div>
                                    <div class="text-end">
                                        <code class="fs-6 text-info">
                                            total_integrator_fees = activation_fee + transaction_fee + role_fee
                                        </code>
                                        <br>
                                        <h4 class="mb-0 fw-bold text-info">
                                            = {{ number_format($integratorShare, 2) }} {{ $transaction->currency ?? 'EUR' }}
                                        </h4>
                                    </div>
                                </div>
                                <div class="mt-3 p-2 bg-white rounded">
                                    <code>
                                        = {{ number_format($integratorBreakdown['activation_fee'] ?? 0, 2) }}
                                        + {{ number_format($integratorBreakdown['transaction_fee'] ?? 0, 2) }}
                                        + {{ number_format($integratorBreakdown['role_fee'] ?? 0, 2) }}
                                        = <strong>{{ number_format($integratorBreakdown['total_fees'] ?? $integratorShare, 2) }}</strong>
                                    </code>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Breakdown des frais Intégrateur non disponible dans calculation_details
                    </div>
                @endif
            </div>
        </div>

        {{-- RÉCAPITULATIF FINAL --}}
        <div class="card border-success">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-check-circle me-2"></i>
                    Récapitulatif Final
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="text-center p-3 bg-danger bg-opacity-10 rounded">
                            <h6 class="text-danger">Part Admin</h6>
                            <h4 class="fw-bold text-danger">{{ number_format($adminShare, 2) }} {{ $transaction->currency ?? 'EUR' }}</h4>
                            <small class="text-muted">{{ number_format($transactionDetail->admin_share_percentage ?? 0, 2) }}%</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center p-3 bg-info bg-opacity-10 rounded">
                            <h6 class="text-info">Part Intégrateur</h6>
                            <h4 class="fw-bold text-info">{{ number_format($integratorShare, 2) }} {{ $transaction->currency ?? 'EUR' }}</h4>
                            <small class="text-muted">{{ number_format($transactionDetail->integrator_share_percentage ?? 0, 2) }}%</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center p-3 bg-success bg-opacity-10 rounded">
                            <h6 class="text-success">Part Opérateur (Net)</h6>
                            <h4 class="fw-bold text-success">{{ number_format($operatorShare, 2) }} {{ $transaction->currency ?? 'EUR' }}</h4>
                            <small class="text-muted">Montant restant après déduction</small>
                        </div>
                    </div>
                </div>
                <div class="mt-4 p-3 bg-primary bg-opacity-10 rounded border border-primary">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong class="text-primary">Montant Total de la Transaction</strong>
                            <small class="d-block text-muted">Vérification: Admin + Intégrateur + Opérateur = Total</small>
                        </div>
                        <div class="text-end">
                            <code class="fs-6">
                                {{ number_format($adminShare, 2) }} + {{ number_format($integratorShare, 2) }} + {{ number_format($operatorShare, 2) }}
                                = <strong class="text-primary">{{ number_format($totalAmount, 2) }}</strong>
                            </code>
                            @php
                                $sum = $adminShare + $integratorShare + $operatorShare;
                                $diff = abs($totalAmount - $sum);
                            @endphp
                            @if($diff <= 0.01)
                                <span class="badge bg-success ms-2"><i class="fas fa-check"></i> Cohérent</span>
                            @else
                                <span class="badge bg-warning ms-2"><i class="fas fa-exclamation-triangle"></i> Différence: {{ number_format($diff, 2) }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- HIÉRARCHIE COMPLÈTE --}}
        @if(!empty($hierarchy))
        <div class="card border-secondary mt-4">
            <div class="card-header bg-secondary text-white">
                <h6 class="mb-0">
                    <i class="fas fa-sitemap me-2"></i>
                    Hiérarchie des Acteurs
                </h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3">
                        <div class="p-2">
                            <i class="fas fa-user-shield fa-2x text-danger mb-2"></i>
                            <div><strong>Admin</strong></div>
                            <small class="text-muted">ID: {{ $hierarchy['admin_id'] ?? 'N/A' }}</small>
                        </div>
                    </div>
                    <div class="col-md-1 text-center">
                        <i class="fas fa-arrow-right fa-2x text-muted"></i>
                    </div>
                    <div class="col-md-3">
                        <div class="p-2">
                            <i class="fas fa-user-tie fa-2x text-info mb-2"></i>
                            <div><strong>Intégrateur</strong></div>
                            <small class="text-muted">ID: {{ $hierarchy['integrator_id'] ?? 'N/A' }}</small>
                        </div>
                    </div>
                    <div class="col-md-1 text-center">
                        <i class="fas fa-arrow-right fa-2x text-muted"></i>
                    </div>
                    <div class="col-md-3">
                        <div class="p-2">
                            <i class="fas fa-user-cog fa-2x text-success mb-2"></i>
                            <div><strong>Opérateur</strong></div>
                            <small class="text-muted">ID: {{ $hierarchy['operator_id'] ?? 'N/A' }}</small>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <small class="text-muted">
                        Borne ID: {{ $hierarchy['charging_point_id'] ?? 'N/A' }}
                    </small>
                </div>
            </div>
        </div>
        @endif

    </div>
</div>
@else
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <strong>TransactionDetail non disponible</strong>
    <br>
    Les détails complets du calcul ne sont pas disponibles pour cette transaction.
    @if($transaction->transactionDetail)
        <br>
        <small class="text-muted">TransactionDetail existe mais calculation_details est vide.</small>
    @else
        <br>
        <small class="text-muted">Cette transaction n'a pas encore été traitée par le système hiérarchique.</small>
    @endif
</div>
@endif
