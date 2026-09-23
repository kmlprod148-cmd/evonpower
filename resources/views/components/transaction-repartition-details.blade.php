@php
    // Récupérer la répartition (TransactionRepartition)
    $repartition = $transaction->repartition ?? $transaction->repartitions->first() ?? null;
    
    // Récupérer les détails visibles (passés par le contrôleur)
    $visibleDetails = $visibleDetails ?? [
        'show_admin_details' => true,
        'show_integrator_details' => true,
        'show_operator_details' => true,
        'show_partner_details' => true,
        'show_hierarchy' => true,
        'show_business_profiles' => true,
        'show_calculation_breakdown' => true,
        'show_repartition_details' => true,
    ];
    
    $currentUser = auth()->user();
    $isAdmin = $currentUser && $currentUser->hasRole(['admin', 'super-admin']);
    $isIntegrator = $currentUser && $currentUser->hasRole('integrator');
    $isOperator = $currentUser && $currentUser->hasRole('operator');
@endphp

@if($repartition)
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="card-title mb-0">
            <i class="fas fa-chart-pie me-2"></i>
            Détails de la Répartition (TransactionRepartition)
            <span class="badge bg-light text-primary ms-2">
                <i class="fas fa-check-circle"></i> Enregistrée
            </span>
        </h5>
    </div>
    <div class="card-body p-4">
        {{-- Informations générales --}}
        <div class="alert alert-info mb-4">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Enregistrement complet de la répartition pour traçabilité</strong>
            <br>
            <small>Ces données proviennent de la table transaction_repartitions et reflètent la répartition réelle calculée lors du traitement de la transaction.</small>
        </div>

        {{-- Grille des montants par partie --}}
        <div class="row g-4 mb-4">
            {{-- Part Admin - Visible uniquement pour Admin --}}
            @if($visibleDetails['show_admin_details'])
            <div class="col-md-4">
                <div class="card border-danger h-100 shadow-sm">
                    <div class="card-header bg-danger text-white text-center">
                        <h6 class="mb-0">
                            <i class="fas fa-user-shield me-2"></i>Part Admin
                        </h6>
                    </div>
                    <div class="card-body text-center p-4">
                        <h2 class="text-danger fw-bold mb-2">
                            {{ number_format($repartition->admin_amount ?? 0, 2) }} €
                        </h2>
                        @if($repartition->total_amount && $repartition->total_amount > 0)
                            <small class="text-muted">
                                {{ number_format(($repartition->admin_amount / $repartition->total_amount) * 100, 2) }}% du total
                            </small>
                        @endif
                        @if($repartition->admin_fee)
                            <div class="mt-3 pt-3 border-top">
                                <small class="text-muted d-block">Frais admin calculés:</small>
                                <strong class="text-danger">{{ number_format($repartition->admin_fee, 2) }} €</strong>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- Part Intégrateur - Visible pour Admin et Integrator --}}
            @if($visibleDetails['show_integrator_details'])
            <div class="col-md-4">
                <div class="card border-info h-100 shadow-sm">
                    <div class="card-header bg-info text-white text-center">
                        <h6 class="mb-0">
                            <i class="fas fa-user-tie me-2"></i>Part Intégrateur
                        </h6>
                    </div>
                    <div class="card-body text-center p-4">
                        <h2 class="text-info fw-bold mb-2">
                            {{ number_format($repartition->integrator_amount ?? 0, 2) }} €
                        </h2>
                        @if($repartition->total_amount && $repartition->total_amount > 0)
                            <small class="text-muted">
                                {{ number_format(($repartition->integrator_amount / $repartition->total_amount) * 100, 2) }}% du total
                            </small>
                        @endif
                        @if($repartition->integrator_fee)
                            <div class="mt-3 pt-3 border-top">
                                <small class="text-muted d-block">Frais intégrateur bruts:</small>
                                <strong class="text-info">{{ number_format($repartition->integrator_fee, 2) }} €</strong>
                                @if($repartition->admin_fee)
                                    <br><small class="text-muted">Net après admin: {{ number_format($repartition->integrator_amount, 2) }} €</small>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- Part Opérateur - Visible pour tous --}}
            @if($visibleDetails['show_operator_details'])
            <div class="col-md-4">
                <div class="card border-success h-100 shadow-sm">
                    <div class="card-header bg-success text-white text-center">
                        <h6 class="mb-0">
                            <i class="fas fa-user-cog me-2"></i>Part Opérateur
                        </h6>
                    </div>
                    <div class="card-body text-center p-4">
                        <h2 class="text-success fw-bold mb-2">
                            {{ number_format($repartition->operator_amount ?? 0, 2) }} €
                        </h2>
                        @if($repartition->total_amount && $repartition->total_amount > 0)
                            <small class="text-muted">
                                {{ number_format(($repartition->operator_amount / $repartition->total_amount) * 100, 2) }}% du total
                            </small>
                        @endif
                        <div class="mt-3 pt-3 border-top">
                            <small class="text-muted d-block">Montant net après déductions</small>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- Tableau détaillé --}}
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th><i class="fas fa-info-circle me-2"></i>Détails</th>
                        <th class="text-end">Montant (€)</th>
                        <th class="text-end">% du Total</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Admin - Visible uniquement pour Admin --}}
                    @if($visibleDetails['show_admin_details'])
                    <tr>
                        <td>
                            <i class="fas fa-user-shield text-danger me-2"></i>
                            <strong>Admin Share (admin_amount)</strong>
                            @if($repartition->admin_fee)
                                <br><small class="text-muted">Frais admin calculés: {{ number_format($repartition->admin_fee, 2) }} €</small>
                            @endif
                        </td>
                        <td class="text-end text-danger fw-bold">
                            {{ number_format($repartition->admin_amount ?? 0, 2) }}
                        </td>
                        <td class="text-end">
                            @if($repartition->total_amount && $repartition->total_amount > 0)
                                {{ number_format(($repartition->admin_amount / $repartition->total_amount) * 100, 2) }}%
                            @else
                                0%
                            @endif
                        </td>
                    </tr>
                    @endif

                    {{-- Intégrateur - Visible pour Admin et Integrator --}}
                    @if($visibleDetails['show_integrator_details'])
                    <tr>
                        <td>
                            <i class="fas fa-user-tie text-info me-2"></i>
                            <strong>Integrator Share (integrator_amount)</strong>
                            @if($repartition->integrator_fee)
                                <br><small class="text-muted">Frais intégrateur bruts: {{ number_format($repartition->integrator_fee, 2) }} €</small>
                                @if($repartition->admin_fee && $visibleDetails['show_admin_details'])
                                    <br><small class="text-muted">Déduction admin: -{{ number_format($repartition->admin_fee, 2) }} €</small>
                                @endif
                            @endif
                        </td>
                        <td class="text-end text-info fw-bold">
                            {{ number_format($repartition->integrator_amount ?? 0, 2) }}
                        </td>
                        <td class="text-end">
                            @if($repartition->total_amount && $repartition->total_amount > 0)
                                {{ number_format(($repartition->integrator_amount / $repartition->total_amount) * 100, 2) }}%
                            @else
                                0%
                            @endif
                        </td>
                    </tr>
                    @endif

                    {{-- Opérateur - Visible pour tous --}}
                    @if($visibleDetails['show_operator_details'])
                    <tr>
                        <td>
                            <i class="fas fa-user-cog text-success me-2"></i>
                            <strong>Operator Share (operator_amount)</strong>
                            <br><small class="text-muted">Montant net après déduction des frais intégrateur</small>
                        </td>
                        <td class="text-end text-success fw-bold">
                            {{ number_format($repartition->operator_amount ?? 0, 2) }}
                        </td>
                        <td class="text-end">
                            @if($repartition->total_amount && $repartition->total_amount > 0)
                                {{ number_format(($repartition->operator_amount / $repartition->total_amount) * 100, 2) }}%
                            @else
                                0%
                            @endif
                        </td>
                    </tr>
                    @endif

                    {{-- Total --}}
                    <tr class="table-primary">
                        <td>
                            <i class="fas fa-calculator me-2"></i>
                            <strong>Total Réparti (total_amount)</strong>
                            @if($repartition->isBalanced())
                                <span class="badge bg-success ms-2">
                                    <i class="fas fa-check-circle"></i> Équilibré
                                </span>
                            @else
                                <span class="badge bg-warning ms-2">
                                    <i class="fas fa-exclamation-triangle"></i> Différence détectée
                                </span>
                            @endif
                        </td>
                        <td class="text-end fw-bold">
                            {{ number_format($repartition->total_amount ?? 0, 2) }}
                        </td>
                        <td class="text-end fw-bold">100.00%</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Vérification de cohérence --}}
        @php
            $calculatedTotal = $repartition->admin_amount + $repartition->integrator_amount + $repartition->operator_amount;
            $difference = abs($repartition->total_amount - $calculatedTotal);
            $isBalanced = $difference < 0.01;
        @endphp

        <div class="alert {{ $isBalanced ? 'alert-success' : 'alert-warning' }} mt-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h6 class="mb-2">
                        <i class="fas {{ $isBalanced ? 'fa-check-circle' : 'fa-exclamation-triangle' }} me-2"></i>
                        Vérification de Cohérence
                    </h6>
                    <p class="mb-0">
                        <strong>Total calculé:</strong> 
                        {{ number_format($calculatedTotal, 2) }} €
                        <br>
                        <strong>Total enregistré:</strong> 
                        {{ number_format($repartition->total_amount ?? 0, 2) }} €
                        @if(!$isBalanced)
                            <br>
                            <strong class="text-warning">Différence:</strong> 
                            {{ number_format($difference, 2) }} €
                        @endif
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    @if($isBalanced)
                        <span class="badge bg-success fs-6 p-2">
                            <i class="fas fa-check-circle me-1"></i>
                            Répartition équilibrée ✓
                        </span>
                    @else
                        <span class="badge bg-warning fs-6 p-2">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            Attention: Différence de {{ number_format($difference, 2) }} €
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Informations de traçabilité --}}
        <div class="row mt-4">
            <div class="col-md-6">
                <small class="text-muted">
                    <i class="fas fa-calendar me-1"></i>
                    <strong>Créé le:</strong> {{ $repartition->created_at ? $repartition->created_at->format('d/m/Y H:i:s') : 'N/A' }}
                </small>
            </div>
            <div class="col-md-6 text-end">
                <small class="text-muted">
                    <i class="fas fa-sync-alt me-1"></i>
                    <strong>Mis à jour le:</strong> {{ $repartition->updated_at ? $repartition->updated_at->format('d/m/Y H:i:s') : 'N/A' }}
                </small>
            </div>
        </div>
    </div>
</div>
@else
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <strong>Aucune répartition enregistrée</strong>
    <br>
    <small>La transaction n'a pas encore de répartition enregistrée dans la table transaction_repartitions. La répartition sera créée automatiquement lors du traitement de la transaction.</small>
</div>
@endif

