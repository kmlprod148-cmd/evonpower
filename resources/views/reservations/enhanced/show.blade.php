@extends('layouts.app')

@section('title', 'Détails Réservation - Frais Complets')

@php
    // Cacher Frais Admin, Frais Intégrateur, Net Opérateur, détail BP pour les clients
    $user = auth()->user();
    $isAdmin = $user && $user->hasRole(['admin', 'super_admin']);
    $userRolesLower = $user ? $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray() : [];
    $isPartnerOrOperator = in_array('partner', $userRolesLower) || in_array('operator', $userRolesLower);
    $isIntegrator = in_array('integrator', $userRolesLower);
    $showFeeBreakdownForClient = $isAdmin || $isPartnerOrOperator || $isIntegrator;
@endphp

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header Section -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-calendar-check me-2"></i>
                        Détails Réservation #{{ $reservation->id }}
                    </h1>
                    <p class="text-muted mb-0">Analyse complète des frais appliqués</p>
                </div>
                <div>
                    <a href="{{ route('reservations.enhanced.index') }}" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left me-1"></i>
                        Retour
                    </a>
                    @can('update', $reservation)
                        <a href="{{ route('reservations.enhanced.edit', $reservation) }}" class="btn btn-warning me-2">
                            <i class="fas fa-edit me-1"></i>
                            Modifier
                        </a>
                    @endcan
                    <button type="button" class="btn btn-success" onclick="exportFees()">
                        <i class="fas fa-download me-1"></i>
                        Exporter PDF
                    </button>
                </div>
            </div>

            <div class="row">
                <!-- Informations Générales -->
                <div class="col-lg-4 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                Informations Générales
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-semibold">ID Réservation</label>
                                    <p class="mb-0">#{{ $reservation->id }}</p>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Utilisateur</label>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                            {{ substr($reservation->user->name ?? 'G', 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="fw-semibold">{{ $reservation->user->name ?? 'Invité' }}</div>
                                            <small class="text-muted">{{ $reservation->user->email ?? $reservation->guest_email }}</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Point de Charge</label>
                                    <p class="mb-0">{{ $reservation->chargingPoint->name ?? 'N/A' }}</p>
                                    <small class="text-muted">{{ $reservation->chargingPoint->station->name ?? 'N/A' }}</small>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Plan Tarifaire</label>
                                    <p class="mb-0">{{ $reservation->pricingPlan->name ?? 'N/A' }}</p>
                                    <small class="text-muted">{{ $reservation->pricingPlan->rate_type ?? 'N/A' }}</small>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Statut</label>
                                    @php
                                        $statusColors = [
                                            'pending' => 'warning',
                                            'confirmed' => 'info',
                                            'active' => 'success',
                                            'completed' => 'primary',
                                            'cancelled' => 'danger'
                                        ];
                                        $color = $statusColors[$reservation->status->value] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $color }} fs-6">
                                        {{ ucfirst($reservation->status->value) }}
                                    </span>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Date de Création</label>
                                    <p class="mb-0">{{ $reservation->created_at->format('d/m/Y H:i') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Résumé Financier -->
                <div class="col-lg-8 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-success text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-euro-sign me-2"></i>
                                Résumé Financier
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="text-center p-3 bg-light rounded">
                                        <h3 class="text-primary mb-1">{{ number_format($feesStatistics['total_cost'], 2) }}€</h3>
                                        <p class="text-muted mb-0">Coût Total</p>
                                    </div>
                                </div>
                                @if($showFeeBreakdownForClient)
                                <div class="col-md-6">
                                    <div class="text-center p-3 bg-light rounded">
                                        <h3 class="text-warning mb-1">{{ number_format($feesStatistics['total_fees'], 2) }}€</h3>
                                        <p class="text-muted mb-0">Frais Totaux</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-center p-3 bg-light rounded">
                                        <h3 class="text-success mb-1">{{ number_format($feesStatistics['net_amount'], 2) }}€</h3>
                                        <p class="text-muted mb-0">Montant Net</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-center p-3 bg-light rounded">
                                        <h3 class="text-info mb-1">{{ $feesStatistics['fee_percentage'] }}%</h3>
                                        <p class="text-muted mb-0">% Frais</p>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Détail des Frais (caché pour les clients) -->
            @if($showFeeBreakdownForClient)
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-info text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-receipt me-2"></i>
                                Détail des Frais Appliqués
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Frais de Réservation -->
                                <div class="col-lg-6 mb-4">
                                    <h6 class="text-primary mb-3">
                                        <i class="fas fa-calendar-alt me-1"></i>
                                        Frais de Réservation
                                    </h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <tbody>
                                                <tr>
                                                    <td>Tarif de base</td>
                                                    <td class="text-end">{{ number_format($feesBreakdown['fees_breakdown']['reservation_fees']['base_rate'], 2) }}€</td>
                                                </tr>
                                                <tr>
                                                    <td>Frais d'activation</td>
                                                    <td class="text-end">{{ number_format($feesBreakdown['fees_breakdown']['reservation_fees']['activation_fee'], 2) }}€</td>
                                                </tr>
                                                <tr>
                                                    <td>Frais énergie ({{ $reservation->reservation_type }})</td>
                                                    <td class="text-end">{{ number_format($feesBreakdown['fees_breakdown']['reservation_fees']['energy_fee'], 2) }}€</td>
                                                </tr>
                                                <tr>
                                                    <td>Frais temps</td>
                                                    <td class="text-end">{{ number_format($feesBreakdown['fees_breakdown']['reservation_fees']['time_fee'], 2) }}€</td>
                                                </tr>
                                                <tr>
                                                    <td>TVA</td>
                                                    <td class="text-end">{{ number_format($feesBreakdown['fees_breakdown']['reservation_fees']['vat_fee'], 2) }}€</td>
                                                </tr>
                                                <tr class="table-primary">
                                                    <td><strong>Total Réservation</strong></td>
                                                    <td class="text-end"><strong>{{ number_format($feesBreakdown['fees_breakdown']['reservation_fees']['total'], 2) }}€</strong></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Frais Business Profile -->
                                <div class="col-lg-6 mb-4">
                                    <h6 class="text-success mb-3">
                                        <i class="fas fa-building me-1"></i>
                                        Frais Business Profile
                                    </h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <tbody>
                                                <tr>
                                                    <td>Frais de transaction</td>
                                                    <td class="text-end">{{ number_format($feesBreakdown['fees_breakdown']['business_profile_fees']['transaction_fees'], 2) }}€</td>
                                                </tr>
                                                <tr>
                                                    <td>Frais de charge</td>
                                                    <td class="text-end">{{ number_format($feesBreakdown['fees_breakdown']['business_profile_fees']['charge_fees'], 2) }}€</td>
                                                </tr>
                                                <tr>
                                                    <td>Frais de maintenance</td>
                                                    <td class="text-end">{{ number_format($feesBreakdown['fees_breakdown']['business_profile_fees']['maintenance_fees'], 2) }}€</td>
                                                </tr>
                                                <tr>
                                                    <td>Frais de terminal</td>
                                                    <td class="text-end">{{ number_format($feesBreakdown['fees_breakdown']['business_profile_fees']['terminal_fees'], 2) }}€</td>
                                                </tr>
                                                <tr>
                                                    <td>Frais de base</td>
                                                    <td class="text-end">{{ number_format($feesBreakdown['fees_breakdown']['business_profile_fees']['base_fees'], 2) }}€</td>
                                                </tr>
                                                <tr class="table-success">
                                                    <td><strong>Total Business Profile</strong></td>
                                                    <td class="text-end"><strong>{{ number_format($feesBreakdown['fees_breakdown']['business_profile_fees']['total'], 2) }}€</strong></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Frais de Transaction -->
                                <div class="col-lg-6 mb-4">
                                    <h6 class="text-warning mb-3">
                                        <i class="fas fa-exchange-alt me-1"></i>
                                        Frais de Transaction
                                    </h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <tbody>
                                                <tr>
                                                    <td>Frais Admin</td>
                                                    <td class="text-end">{{ number_format($feesBreakdown['fees_breakdown']['transaction_fees']['admin_fees'], 2) }}€</td>
                                                </tr>
                                                <tr>
                                                    <td>Frais Intégrateur</td>
                                                    <td class="text-end">{{ number_format($feesBreakdown['fees_breakdown']['transaction_fees']['integrator_fees'], 2) }}€</td>
                                                </tr>
                                                <tr>
                                                    <td>Frais Partenaire</td>
                                                    <td class="text-end">{{ number_format($feesBreakdown['fees_breakdown']['transaction_fees']['partner_fees'], 2) }}€</td>
                                                </tr>
                                                <tr>
                                                    <td>Frais Opérateur</td>
                                                    <td class="text-end">{{ number_format($feesBreakdown['fees_breakdown']['transaction_fees']['operator_fees'], 2) }}€</td>
                                                </tr>
                                                <tr class="table-warning">
                                                    <td><strong>Total Transaction</strong></td>
                                                    <td class="text-end"><strong>{{ number_format($feesBreakdown['fees_breakdown']['transaction_fees']['total'], 2) }}€</strong></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Frais d'Activation et Commission -->
                                <div class="col-lg-6 mb-4">
                                    <h6 class="text-danger mb-3">
                                        <i class="fas fa-bolt me-1"></i>
                                        Frais d'Activation & Commission
                                    </h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <tbody>
                                                <tr>
                                                    <td>Frais d'activation</td>
                                                    <td class="text-end">{{ number_format($feesBreakdown['fees_breakdown']['activation_fees']['activation_fee'], 2) }}€</td>
                                                </tr>
                                                <tr>
                                                    <td>Frais de charge (Creator)</td>
                                                    <td class="text-end">{{ number_format($feesBreakdown['fees_breakdown']['commission_fees']['creator_charging_fees'], 2) }}€</td>
                                                </tr>
                                                <tr>
                                                    <td>Frais de transaction (Creator)</td>
                                                    <td class="text-end">{{ number_format($feesBreakdown['fees_breakdown']['commission_fees']['creator_transaction_fees'], 2) }}€</td>
                                                </tr>
                                                <tr>
                                                    <td>Frais d'activation (Creator)</td>
                                                    <td class="text-end">{{ number_format($feesBreakdown['fees_breakdown']['commission_fees']['creator_activation_fees'], 2) }}€</td>
                                                </tr>
                                                <tr>
                                                    <td>Frais Admin (Creator)</td>
                                                    <td class="text-end">{{ number_format($feesBreakdown['fees_breakdown']['commission_fees']['creator_admin_fees'], 2) }}€</td>
                                                </tr>
                                                <tr class="table-danger">
                                                    <td><strong>Total Activation & Commission</strong></td>
                                                    <td class="text-end"><strong>{{ number_format($feesBreakdown['fees_breakdown']['activation_fees']['total'] + $feesBreakdown['fees_breakdown']['commission_fees']['total'], 2) }}€</strong></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Résumé Final -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <div class="row text-center">
                                            <div class="col-md-3">
                                                <h4 class="text-primary mb-1">{{ number_format($feesBreakdown['summary']['total_cost'], 2) }}€</h4>
                                                <small class="text-muted">Coût Total</small>
                                            </div>
                                            <div class="col-md-3">
                                                <h4 class="text-warning mb-1">{{ number_format($feesBreakdown['summary']['total_fees'], 2) }}€</h4>
                                                <small class="text-muted">Frais Totaux</small>
                                            </div>
                                            <div class="col-md-3">
                                                <h4 class="text-success mb-1">{{ number_format($feesBreakdown['summary']['net_amount'], 2) }}€</h4>
                                                <small class="text-muted">Montant Net</small>
                                            </div>
                                            <div class="col-md-3">
                                                <h4 class="text-info mb-1">{{ $feesBreakdown['summary']['fee_percentage'] }}%</h4>
                                                <small class="text-muted">% Frais</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Informations Supplémentaires -->
            @if($reservation->notes)
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-sticky-note me-2"></i>
                                Notes
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-0">{{ $reservation->notes }}</p>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function exportFees() {
    // TODO: Implémenter l'export PDF
    fetch(`{{ route('reservations.enhanced.export-fees', $reservation) }}`)
        .then(response => response.json())
        .then(data => {
            console.log('Données des frais:', data);
            alert('Export PDF en cours de développement');
        })
        .catch(error => {
            console.error('Erreur lors de l\'export:', error);
            alert('Erreur lors de l\'export');
        });
}
</script>
@endpush

@push('styles')
<style>
.avatar-sm {
    width: 40px;
    height: 40px;
    font-size: 16px;
}

.card {
    border: 1px solid #e3e6f0;
    border-radius: 0.35rem;
}

.card-header {
    border-bottom: 1px solid #e3e6f0;
}

.table th {
    border-top: none;
    font-weight: 600;
    color: #6c757d;
    font-size: 0.875rem;
}

.table td {
    vertical-align: middle;
    font-size: 0.875rem;
}

.alert {
    border-radius: 0.5rem;
}

.bg-light {
    background-color: #f8f9fc !important;
}
</style>
@endpush
