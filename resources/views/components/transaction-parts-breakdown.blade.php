@php
    // PRIORITÉ : TransactionDetail (source de vérité) > Fallback
    $transactionDetail = $transaction->transactionDetail;
    
    if ($transactionDetail) {
        $adminShare = (float) ($transactionDetail->admin_share_amount ?? 0);
        $integratorShare = (float) ($transactionDetail->integrator_share_amount ?? 0);
        $operatorShare = (float) ($transactionDetail->operator_share_amount ?? 0);
        $totalFees = $adminShare + $integratorShare;
        $calculationDetails = $transactionDetail->calculation_details ?? [];
        $hasRealData = true;
    } else {
        // Utiliser les méthodes du modèle Transaction qui calculent selon la logique hiérarchique
        $adminShare = $transaction->getAdminFee();
        $integratorFeeBrut = $transaction->getIntegratorFee();
        $operatorShare = $transaction->getOperatorShare();
        
        // Dans la logique hiérarchique:
        // - Integrator fee brut = calculé sur le total
        // - Admin fee = calculé sur la part intégrateur
        // - Integrator share net = integrator_fee_brut - admin_fee
        // - Operator share = total - integrator_fee_brut
        $integratorShare = max(0, round($integratorFeeBrut - $adminShare, 2));
        
        $totalFees = $adminShare + $integratorFeeBrut; // Frais totaux déduits du total
        $calculationDetails = [];
        $hasRealData = false;
    }
    
    $totalAmount = $transaction->amount ?? $transaction->price_total ?? 0;
    $businessProfiles = $calculationDetails['business_profiles'] ?? [];
    $adminBreakdown = $calculationDetails['admin_fees_breakdown'] ?? [];
    $integratorBreakdown = $calculationDetails['integrator_fees_breakdown'] ?? [];
    $hierarchicalLogic = $calculationDetails['hierarchical_logic'] ?? [];
    
    // Déterminer le rôle de l'utilisateur actuel
    $currentUser = auth()->user();
    $isAdmin = $currentUser && $currentUser->hasRole(['admin', 'super-admin']);
    $isIntegrator = $currentUser && $currentUser->hasRole('integrator');
    $isOperator = $currentUser && $currentUser->hasRole(['operator', 'partner']);
    
    // Règles de visibilité selon TRANSACTION_VISIBILITY_BY_ROLE.md
    $canViewAdminDetails = $isAdmin; // Seul l'admin voit les détails admin complets
    $canViewIntegratorDetails = $isAdmin || $isIntegrator; // Admin et Intégrateur voient les détails intégrateur
    $canViewHierarchyComplete = $isAdmin; // Seul l'admin voit la hiérarchie complète
    $canViewBusinessProfiles = $isAdmin || $isIntegrator; // Admin et Intégrateur voient les Business Profiles
    
    // Extraire les informations de la logique hiérarchique depuis TransactionDetail
    if ($hasRealData && !empty($hierarchicalLogic)) {
        // Récupérer les frais bruts depuis hierarchical_logic si disponibles
        $integratorFeesAmount = $hierarchicalLogic['integrator_fees_amount'] ?? null;
        $adminFeesAmount = $hierarchicalLogic['admin_fees_amount'] ?? null;
        $integratorFeesCalculatedOn = $hierarchicalLogic['integrator_fees_calculated_on'] ?? 'total_amount';
        
        // admin_fees_calculated_on peut être un tableau ou une chaîne
        $adminFeesCalculatedOnRaw = $hierarchicalLogic['admin_fees_calculated_on'] ?? 'total_amount';
        if (is_array($adminFeesCalculatedOnRaw)) {
            // Si c'est un tableau, créer une description détaillée
            $adminFeesCalculatedOn = 'transaction_fee et charge_fee sur Montant Total, role_fee sur Frais Intégrateur';
            $adminFeesCalculatedOnSimple = 'total_amount'; // Pour les comparaisons simples
        } else {
            $adminFeesCalculatedOn = $adminFeesCalculatedOnRaw;
            $adminFeesCalculatedOnSimple = $adminFeesCalculatedOnRaw;
        }
    } else {
        // Si pas de données réelles, calculer approximativement
        $integratorFeesAmount = isset($integratorFeeBrut) ? $integratorFeeBrut : ($integratorShare + $adminShare);
        $adminFeesAmount = $adminShare;
        $integratorFeesCalculatedOn = 'total_amount';
        $adminFeesCalculatedOn = 'total_amount'; // Note: selon le service, admin est calculé sur le total mais déduit de la part intégrateur
        $adminFeesCalculatedOnSimple = 'total_amount';
    }
@endphp

<div class="transaction-parts-breakdown">
    {{-- Header avec indicateur de source --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0">
            <i class="fas fa-chart-pie text-primary me-2"></i>
            Répartition Hiérarchique des Parts
        </h5>
        @if($hasRealData)
            <span class="badge badge-success badge-lg">
                <i class="fas fa-check-circle me-1"></i>
                Selon Business Profiles
            </span>
        @else
            <span class="badge badge-warning badge-lg">
                <i class="fas fa-exclamation-triangle me-1"></i>
                Données estimées
            </span>
        @endif
    </div>

    @php
        // Déterminer si l'utilisateur actuel est lié à cette transaction
        $userRole = $currentUser->role ?? null;
        $isUserRelatedToTransaction = false;
        $userRelatedPart = null;
        
        if ($transactionDetail) {
            if ($userRole === 'admin' && $transactionDetail->admin_creator_id == $currentUser->id) {
                $isUserRelatedToTransaction = true;
                $userRelatedPart = 'admin';
            } elseif ($userRole === 'integrator' && $transactionDetail->integrator_creator_id == $currentUser->id) {
                $isUserRelatedToTransaction = true;
                $userRelatedPart = 'integrator';
            } elseif (in_array($userRole, ['operator', 'partner']) && $transactionDetail->operator_id == $currentUser->id) {
                $isUserRelatedToTransaction = true;
                $userRelatedPart = 'operator';
            }
        }
        
        // Vérifier aussi via la hiérarchie de la borne
        $isUserRelatedViaChargingPoint = false;
        if ($transaction->chargingPoint) {
            $cp = $transaction->chargingPoint;
            if ($userRole === 'integrator' && ($cp->integrator_id == $currentUser->integrator_id || $cp->integrator_id == $currentUser->id)) {
                $isUserRelatedViaChargingPoint = true;
            } elseif ($userRole === 'partner' && ($cp->partner_id == ($currentUser->partner_id ?? $currentUser->id) || ($cp->group && $cp->group->partner_id == ($currentUser->partner_id ?? $currentUser->id)))) {
                $isUserRelatedViaChargingPoint = true;
            } elseif (in_array($userRole, ['operator', 'partner']) && ($cp->user_id == $currentUser->id || ($cp->group && $cp->group->user_id == $currentUser->id))) {
                $isUserRelatedViaChargingPoint = true;
            }
        }
        
        // Règles d'affichage des parts selon le rôle
        // Admin : voit toutes les parts (même si à 0 pour debug)
        // Intégrateur : voit sa part + part opérateur, mais pas les détails admin complets
        // Opérateur : voit sa part + part intégrateur (pour voir les frais déduits)
        $showAdminPart = $isAdmin; // Admin voit toujours sa part, même si à 0
        $showIntegratorPart = ($isAdmin || $isIntegrator || $isOperator); // Tous voient la part intégrateur (pour comprendre les frais)
        $showOperatorPart = true; // Toujours visible
    @endphp
    
    {{-- Afficher un message si aucune donnée réelle n'est disponible --}}
    @if(!$hasRealData && !$transactionDetail)
    <div class="col-12 mb-3">
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Attention:</strong> Cette transaction n'a pas de TransactionDetail. Les montants affichés sont estimés.
            <br>
            <small>Pour obtenir la répartition exacte selon les Business Profiles, exécutez la commande: <code>php artisan transactions:fix-all</code></small>
        </div>
    </div>
    @endif
    
    {{-- Cartes des Parts principales - Affichage selon le rôle --}}
    <div class="row g-3 mb-4">
        {{-- Part Admin - Visible UNIQUEMENT pour Admin --}}
        @if($showAdminPart)
        <div class="col-md-4">
            <div class="card border-danger h-100 shadow-sm {{ $userRelatedPart === 'admin' ? 'border-danger border-3 shadow-lg' : '' }}">
                <div class="card-body text-center p-4">
                    <div class="mb-3">
                        <i class="fas fa-user-shield fa-3x text-danger"></i>
                    </div>
                    <h6 class="text-muted mb-2">
                        Part Admin
                        @if($userRelatedPart === 'admin')
                            <span class="badge bg-danger ms-2">Votre part</span>
                        @endif
                    </h6>
                    <h3 class="text-danger fw-bold mb-2">
                        {{ number_format($adminShare, 2) }} €
                    </h3>
                    @if($transactionDetail && $transactionDetail->admin_share_percentage)
                        <small class="text-muted">
                            {{ number_format($transactionDetail->admin_share_percentage, 2) }}% du total
                        </small>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Part Intégrateur - Visible pour Admin, Intégrateur et Opérateur --}}
        @if($showIntegratorPart)
        <div class="col-md-4">
            <div class="card border-info h-100 shadow-sm {{ $userRelatedPart === 'integrator' ? 'border-info border-3 shadow-lg' : '' }}">
                <div class="card-body text-center p-4">
                    <div class="mb-3">
                        <i class="fas fa-user-tie fa-3x text-info"></i>
                    </div>
                    <h6 class="text-muted mb-2">
                        Part Intégrateur
                        @if($userRelatedPart === 'integrator')
                            <span class="badge bg-info ms-2">Votre part</span>
                        @endif
                    </h6>
                    <h3 class="text-info fw-bold mb-2">
                        {{ number_format($integratorShare, 2) }} €
                    </h3>
                    @if($transactionDetail && $transactionDetail->integrator_share_percentage)
                        <small class="text-muted">
                            {{ number_format($transactionDetail->integrator_share_percentage, 2) }}% du total
                        </small>
                    @endif
                    @if($adminShare > 0 && $hasRealData)
                        <br><small class="text-muted">Net après déduction admin</small>
                        @if(!$isAdmin)
                            <br><small class="text-warning"><i class="fas fa-info-circle"></i> Frais admin déduits (détails non visibles)</small>
                        @endif
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Part Opérateur - Toujours visible --}}
        <div class="col-md-4">
            <div class="card border-success h-100 shadow-sm {{ $userRelatedPart === 'operator' ? 'border-success border-3 shadow-lg' : '' }}">
                <div class="card-body text-center p-4">
                    <div class="mb-3">
                        <i class="fas fa-user-cog fa-3x text-success"></i>
                    </div>
                    <h6 class="text-muted mb-2">
                        Net Opérateur
                        @if($userRelatedPart === 'operator')
                            <span class="badge bg-success ms-2">Votre part</span>
                        @endif
                    </h6>
                    <h3 class="text-success fw-bold mb-2">
                        {{ number_format($operatorShare, 2) }} €
                    </h3>
                    <small class="text-muted">
                        Montant après déduction des frais
                    </small>
                    @if($isOperator && $integratorShare > 0)
                        @php
                            // Pour l'opérateur : calculer les frais intégrateur bruts déduits
                            $integratorFeesDeducted = $hasRealData && isset($integratorFeesAmount) 
                                ? $integratorFeesAmount 
                                : ($integratorShare + $adminShare);
                        @endphp
                        <br><small class="text-warning">
                            <i class="fas fa-info-circle"></i> Frais déduits: <strong>{{ number_format($integratorFeesDeducted, 2) }} €</strong>
                        </small>
                    @endif
                </div>
            </div>
        </div>
    </div>


    {{-- Détails de la Logique Hiérarchique - Visible UNIQUEMENT pour Admin --}}
    @if($canViewHierarchyComplete && $hasRealData && !empty($hierarchicalLogic))
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-warning-light border-bottom">
            <h6 class="mb-0 text-warning">
                <i class="fas fa-project-diagram me-2"></i>
                Logique Hiérarchique de Calcul (selon ReservationTransactionService)
            </h6>
        </div>
        <div class="card-body">
            <div class="alert alert-info mb-0">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-info mb-2">
                            <i class="fas fa-calculator me-2"></i>Calcul des Frais Intégrateur
                        </h6>
                        <p class="mb-1">
                            <strong>Calculé sur:</strong> {{ $integratorFeesCalculatedOn === 'total_amount' ? 'Montant Total' : $integratorFeesCalculatedOn }}
                        </p>
                        @if(isset($integratorFeesAmount))
                        <p class="mb-0">
                            <strong>Frais Intégrateur (brut):</strong> {{ number_format($integratorFeesAmount, 2) }} €
                        </p>
                        <p class="mb-0 text-muted small">
                            = Config Transaction + Config Charge (selon BP Intégrateur→Opérateur)
                        </p>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-danger mb-2">
                            <i class="fas fa-calculator me-2"></i>Calcul des Frais Admin
                        </h6>
                        <p class="mb-1">
                            <strong>Calculé sur:</strong> 
                            @if(isset($adminFeesCalculatedOnSimple) && $adminFeesCalculatedOnSimple === 'total_amount')
                                Montant Total
                            @elseif(is_string($adminFeesCalculatedOn))
                                {{ $adminFeesCalculatedOn === 'integrator_fees' ? 'Frais Intégrateur' : $adminFeesCalculatedOn }}
                            @else
                                {{ is_array($adminFeesCalculatedOn) ? 'transaction_fee et charge_fee sur Montant Total, role_fee sur Frais Intégrateur' : 'Montant Total' }}
                            @endif
                        </p>
                        @if(isset($adminFeesAmount))
                        <p class="mb-0">
                            <strong>Frais Admin:</strong> {{ number_format($adminFeesAmount, 2) }} €
                        </p>
                        <p class="mb-0 text-muted small">
                            = Config Transaction + Config Charge + Frais Admin (selon BP Admin→Intégrateur)
                        </p>
                        @endif
                    </div>
                </div>
                <hr class="my-3">
                <div class="row">
                    <div class="col-12">
                        <h6 class="mb-2"><i class="fas fa-share-alt me-2"></i>Répartition des Parts</h6>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <div class="p-2 bg-light rounded">
                                    <strong class="text-success">Part Opérateur:</strong><br>
                                    <small class="text-muted">
                                        {{ number_format($totalAmount, 2) }}€ - {{ number_format($integratorFeesAmount ?? ($integratorShare + $adminShare), 2) }}€ = 
                                        <strong>{{ number_format($operatorShare, 2) }} €</strong>
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-2 bg-light rounded">
                                    <strong class="text-info">Part Intégrateur Net:</strong><br>
                                    <small class="text-muted">
                                        {{ number_format($integratorFeesAmount ?? ($integratorShare + $adminShare), 2) }}€ - {{ number_format($adminShare, 2) }}€ = 
                                        <strong>{{ number_format($integratorShare, 2) }} €</strong>
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-2 bg-light rounded">
                                    <strong class="text-danger">Part Admin:</strong><br>
                                    <small class="text-muted">
                                        = <strong>{{ number_format($adminShare, 2) }} €</strong>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Hiérarchie et Business Profiles utilisés - Visible pour Admin et Intégrateur --}}
    @if($canViewBusinessProfiles && $hasRealData && (!empty($businessProfiles) || !empty($calculationDetails['hierarchy'] ?? [])))
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-primary-light border-bottom">
            <h6 class="mb-0 text-primary">
                <i class="fas fa-sitemap me-2"></i>
                Hiérarchie et Business Profiles Utilisés
            </h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                {{-- Hiérarchie --}}
                @if(!empty($calculationDetails['hierarchy'] ?? []))
                @php $hierarchy = $calculationDetails['hierarchy']; @endphp
                <div class="col-md-6">
                    <h6 class="text-muted mb-3">
                        <i class="fas fa-users me-2"></i>Hiérarchie des Acteurs
                    </h6>
                    <div class="list-group list-group-flush">
                        {{-- Admin visible uniquement pour Admin --}}
                        @if($canViewHierarchyComplete && isset($hierarchy['admin_id']))
                        <div class="list-group-item d-flex align-items-center px-0 py-2">
                            <i class="fas fa-user-shield text-danger me-2"></i>
                            <div class="flex-grow-1">
                                <strong>Admin</strong>
                                <br><small class="text-muted">ID: {{ $hierarchy['admin_id'] }}</small>
                                @if($transactionDetail && $transactionDetail->adminCreator)
                                    <br><small class="text-info">{{ $transactionDetail->adminCreator->name ?? $transactionDetail->adminCreator->email }}</small>
                                @endif
                            </div>
                        </div>
                        @endif
                        @if(isset($hierarchy['integrator_id']))
                        <div class="list-group-item d-flex align-items-center px-0 py-2">
                            <i class="fas fa-user-tie text-info me-2"></i>
                            <div class="flex-grow-1">
                                <strong>Intégrateur</strong>
                                <br><small class="text-muted">ID: {{ $hierarchy['integrator_id'] }}</small>
                                @if($transactionDetail && $transactionDetail->integratorCreator)
                                    <br><small class="text-info">{{ $transactionDetail->integratorCreator->name ?? $transactionDetail->integratorCreator->email }}</small>
                                @endif
                            </div>
                        </div>
                        @endif
                        @if(isset($hierarchy['operator_id']))
                        <div class="list-group-item d-flex align-items-center px-0 py-2">
                            <i class="fas fa-user-cog text-success me-2"></i>
                            <div class="flex-grow-1">
                                <strong>Opérateur</strong>
                                <br><small class="text-muted">ID: {{ $hierarchy['operator_id'] }}</small>
                                @if($transactionDetail && $transactionDetail->operator)
                                    <br><small class="text-info">{{ $transactionDetail->operator->name ?? $transactionDetail->operator->email }}</small>
                                @endif
                            </div>
                        </div>
                        @endif
                        @if(isset($hierarchy['charging_point_id']))
                        <div class="list-group-item d-flex align-items-center px-0 py-2">
                            <i class="fas fa-charging-station text-primary me-2"></i>
                            <div class="flex-grow-1">
                                <strong>Borne de Recharge</strong>
                                <br><small class="text-muted">ID: {{ $hierarchy['charging_point_id'] }}</small>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Business Profiles --}}
                @if(!empty($businessProfiles))
                <div class="col-md-6">
                    <h6 class="text-muted mb-3">
                        <i class="fas fa-file-contract me-2"></i>Business Profiles
                    </h6>
                    {{-- Business Profile Admin → Intégrateur visible UNIQUEMENT pour Admin --}}
                    @if($canViewHierarchyComplete && isset($businessProfiles['admin_integrator_id']))
                    <div class="p-3 border rounded mb-3">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-arrow-right text-danger me-2"></i>
                            <strong>Admin → Intégrateur</strong>
                        </div>
                        <p class="mb-1 text-muted">
                            <small>ID: {{ $businessProfiles['admin_integrator_id'] }}</small>
                        </p>
                        @if(isset($businessProfiles['admin_integrator_name']))
                            <p class="mb-2">
                                <strong>{{ $businessProfiles['admin_integrator_name'] }}</strong>
                            </p>
                        @endif
                        <a href="{{ route('business-profiles.show', $businessProfiles['admin_integrator_id']) }}" 
                           class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-eye me-1"></i>Voir le Profil
                        </a>
                    </div>
                    @endif

                    @if(isset($businessProfiles['integrator_operator_id']))
                    <div class="p-3 border rounded">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-arrow-right text-info me-2"></i>
                            <strong>Intégrateur → Opérateur</strong>
                        </div>
                        <p class="mb-1 text-muted">
                            <small>ID: {{ $businessProfiles['integrator_operator_id'] }}</small>
                        </p>
                        @if(isset($businessProfiles['integrator_operator_name']))
                            <p class="mb-2">
                                <strong>{{ $businessProfiles['integrator_operator_name'] }}</strong>
                            </p>
                        @endif
                        <a href="{{ route('business-profiles.show', $businessProfiles['integrator_operator_id']) }}" 
                           class="btn btn-sm btn-outline-info">
                            <i class="fas fa-eye me-1"></i>Voir le Profil
                        </a>
                    </div>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Résumé total avec vérification de cohérence --}}
    @php
        $sharesTotal = $adminShare + $integratorShare + $operatorShare;
        $isConsistent = abs($totalAmount - $sharesTotal) < 0.01; // Tolérance de 1 centime
        $integratorFeeBrutDisplay = $hasRealData ? $integratorShare + $adminShare : ($integratorFeeBrut ?? ($integratorShare + $adminShare));
    @endphp
    <div class="alert {{ $isConsistent ? 'alert-success' : 'alert-warning' }} border">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="d-flex align-items-center">
                    <i class="fas {{ $isConsistent ? 'fa-check-circle' : 'fa-exclamation-triangle' }} text-{{ $isConsistent ? 'success' : 'warning' }} me-2"></i>
                    <div>
                        <strong>Montant Total de la Transaction</strong>
                        <p class="mb-0">
                            <span class="fs-5">{{ number_format($totalAmount, 2) }} €</span>
                        </p>
                        @if($isOperator)
                            {{-- Opérateur : informations minimales seulement --}}
                            <small class="text-muted d-block mt-1">
                                Montant net après déduction des frais de transaction
                            </small>
                        @elseif(!$hasRealData)
                            <small class="text-muted d-block mt-1">
                                Logique: Opérateur = Total - Frais Intégrateur | 
                                Intégrateur Net = Frais Intégrateur - Frais Admin
                            </small>
                        @elseif(!empty($hierarchicalLogic) && $canViewHierarchyComplete)
                            {{-- Logique hiérarchique visible uniquement pour Admin --}}
                            <small class="text-muted d-block mt-1">
                                <strong>Logique Hiérarchique (selon ReservationTransactionService):</strong><br>
                                Frais Intégrateur calculé sur: <strong>{{ $integratorFeesCalculatedOn === 'total_amount' ? 'Montant Total' : $integratorFeesCalculatedOn }}</strong><br>
                                Frais Admin calculé sur: <strong>
                                    @if(isset($adminFeesCalculatedOnSimple) && $adminFeesCalculatedOnSimple === 'total_amount')
                                        transaction_fee et charge_fee sur Montant Total, role_fee sur Frais Intégrateur
                                    @elseif(is_string($adminFeesCalculatedOn))
                                        {{ $adminFeesCalculatedOn === 'integrator_fees' ? 'Frais Intégrateur' : $adminFeesCalculatedOn }}
                                    @else
                                        transaction_fee et charge_fee sur Montant Total, role_fee sur Frais Intégrateur
                                    @endif
                                </strong> (mais déduit de la part intégrateur)
                            </small>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="text-end">
                    <div class="mb-2">
                        <strong class="text-muted">Répartition:</strong>
                        <div class="mt-1">
                            {{-- Admin visible uniquement pour Admin --}}
                            @if($showAdminPart)
                                <small class="d-block">
                                    Admin: <strong class="text-danger">{{ number_format($adminShare, 2) }} €</strong>
                                </small>
                            @endif
                            {{-- Part Intégrateur visible uniquement pour Admin et Intégrateur --}}
                            @if($showIntegratorPart)
                                <small class="d-block">
                                    Intégrateur: <strong class="text-info">{{ number_format($integratorShare, 2) }} €</strong>
                                    @if($isAdmin && $hasRealData && isset($integratorFeesAmount) && $integratorFeesAmount > 0)
                                        <span class="text-muted">
                                            (Frais bruts: {{ number_format($integratorFeesAmount, 2) }}€ - Frais Admin: {{ number_format($adminShare, 2) }}€)
                                        </span>
                                    @elseif($isAdmin && !$hasRealData)
                                        <span class="text-muted">({{ number_format($integratorFeeBrutDisplay, 2) }}€ - {{ number_format($adminShare, 2) }}€)</span>
                                    @elseif($isIntegrator && $adminShare > 0)
                                        <span class="text-muted">
                                            (Frais admin déduits: {{ number_format($adminShare, 2) }}€)
                                        </span>
                                    @endif
                                </small>
                            @endif
                            <small class="d-block">
                                Opérateur: <strong class="text-success">{{ number_format($operatorShare, 2) }} €</strong>
                            </small>
                        </div>
                    </div>
                    <div class="pt-2 border-top">
                        <small class="text-muted">
                            <strong>Total Répartition:</strong> 
                            <span class="{{ $isConsistent ? 'text-success' : 'text-warning' }}">
                                {{ number_format($sharesTotal, 2) }} €
                            </span>
                            @if(!$isConsistent)
                                <br><span class="text-warning">
                                    Différence: {{ number_format(abs($totalAmount - $sharesTotal), 2) }} €
                                </span>
                            @endif
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.transaction-parts-breakdown .badge-lg {
    font-size: 0.85rem;
    padding: 0.5rem 0.75rem;
}

.transaction-parts-breakdown .card {
    transition: transform 0.2s, box-shadow 0.2s;
}

.transaction-parts-breakdown .card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
}

.bg-danger-light {
    background-color: rgba(220, 53, 69, 0.1) !important;
}

.bg-info-light {
    background-color: rgba(13, 202, 240, 0.1) !important;
}

.bg-primary-light {
    background-color: rgba(13, 110, 253, 0.1) !important;
}

.bg-warning-light {
    background-color: rgba(255, 193, 7, 0.1) !important;
}
</style>
@endpush

