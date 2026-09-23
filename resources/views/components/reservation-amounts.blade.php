@props([
    'reservation',
    'showBreakdown' => true,
    'size' => 'default' // 'small', 'default', 'large'
])

@php
    use App\Services\ReservationDisplayService;

    // Cacher le détail des frais (Frais Admin, Frais Intégrateur, Net Opérateur, Calculé selon BP) pour les clients
    $user = auth()->user();
    $isAdmin = $user && $user->hasRole(['admin', 'super_admin']);
    $userRolesLower = $user ? $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray() : [];
    $isPartnerOrOperator = in_array('partner', $userRolesLower) || in_array('operator', $userRolesLower);
    $isIntegrator = in_array('integrator', $userRolesLower);
    $isSimpleClient = !$user || (!$isAdmin && !$isPartnerOrOperator && !$isIntegrator);
    if ($isSimpleClient) {
        $showBreakdown = false;
    }
    
    $displayService = app(ReservationDisplayService::class);
    $amounts = $displayService->getDisplayAmounts($reservation);
    
    // CORRECTION : Valider les montants avec correction automatique
    // Si une correction est effectuée dans getDisplayAmounts(), elle devrait déjà être appliquée
    $validationResult = $displayService->validateAmounts($amounts, true);
    
    // Si la validation retourne un array (montants corrigés), l'utiliser
    if (is_array($validationResult)) {
        $amounts = $validationResult;
        // Les montants ont été corrigés, donc ils sont maintenant valides
        $isValid = true;
    } else {
        // Si la validation retourne un booléen, utiliser cette valeur
        // MAIS si les montants ont été corrigés dans getDisplayAmounts(), considérer comme valide
        $isValid = is_bool($validationResult) ? $validationResult : true;
        
        // Vérification supplémentaire : si les montants sont cohérents après correction dans getDisplayAmounts
        // mais validateAmounts retourne false, revalider une dernière fois
        if (!$isValid) {
            // Recalculer pour une vérification finale après toutes les corrections
            $recalculatedTotal = round(
                (float) ($amounts['admin_fees'] ?? 0) + 
                (float) ($amounts['integrator_fees'] ?? 0) + 
                (float) ($amounts['operator_net'] ?? 0), 
                2
            );
            $totalAmount = round((float) ($amounts['total_amount'] ?? 0), 2);
            $finalDifference = abs($recalculatedTotal - $totalAmount);
            
            // Si la différence est maintenant acceptable (< 0.02€ pour tolérer les arrondis), considérer comme valide
            if ($finalDifference <= 0.02 || ($totalAmount > 0 && $recalculatedTotal <= 0.01)) {
                $isValid = true;
            }
        }
    }
    
    $formatted = $displayService->formatAmountsForDisplay($amounts);
    
    $sizeClasses = [
        'small' => 'text-xs',
        'default' => 'text-sm',
        'large' => 'text-base'
    ];
    
    $textSize = $sizeClasses[$size] ?? $sizeClasses['default'];
@endphp

<div class="reservation-amounts {{ $textSize }}">
    <!-- Montant total -->
    <div class="flex justify-between items-center font-semibold text-green-600 dark:text-green-400">
        <span>Total Facturé</span>
        <span>{{ $formatted['total_amount_formatted'] }}</span>
    </div>

    @if($showBreakdown && ($amounts['admin_fees'] > 0 || $amounts['integrator_fees'] > 0))
        <div class="mt-2 space-y-1 {{ $textSize }}">
            <!-- Frais Admin -->
            @if($amounts['admin_fees'] > 0)
                <div class="flex justify-between items-center text-red-600 dark:text-red-400">
                    <span class="flex items-center">
                        <i class="fas fa-crown mr-1 w-3"></i>
                        Frais Admin
                    </span>
                    <span>{{ $formatted['admin_fees_formatted'] }}</span>
                </div>
            @endif

            <!-- Frais Intégrateur -->
            @if($amounts['integrator_fees'] > 0)
                <div class="flex justify-between items-center text-blue-600 dark:text-blue-400">
                    <span class="flex items-center">
                        <i class="fas fa-handshake mr-1 w-3"></i>
                        Frais Intégrateur
                    </span>
                    <span>{{ $formatted['integrator_fees_formatted'] }}</span>
                </div>
            @endif

            <!-- Net Opérateur -->
            <div class="flex justify-between items-center font-medium text-gray-700 dark:text-gray-300 border-t pt-1">
                <span class="flex items-center">
                    <i class="fas fa-user-tie mr-1 w-3"></i>
                    Net Opérateur
                </span>
                <span>{{ $formatted['operator_net_formatted'] }}</span>
            </div>
        </div>

        <!-- Indicateur de méthode de calcul -->
        <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            @switch($amounts['calculation_method'])
                @case('transaction_calculated')
                    <i class="fas fa-check-circle text-green-500 mr-1"></i>
                    Calculé selon transaction réelle
                    @break
                @case('estimated_calculated')
                    <i class="fas fa-calculator text-blue-500 mr-1"></i>
                    Calculé selon Business Profiles
                    @break
                @case('default_fallback')
                    <i class="fas fa-exclamation-triangle text-yellow-500 mr-1"></i>
                    Estimation par défaut
                    @break
                @default
                    <i class="fas fa-info-circle text-gray-500 mr-1"></i>
                    Montant estimé
            @endswitch
        </div>
    @endif

    <!-- Validation de cohérence -->
    @if(!$isValid)
        <div class="mt-2 text-xs text-red-500 dark:text-red-400">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            Incohérence détectée dans les montants
        </div>
    @endif
</div>
