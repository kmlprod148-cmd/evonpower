@props(['transaction', 'user' => null])

@php
    $displayService = app(\App\Services\TransactionDisplayService::class);
    $amounts = $displayService->getDisplayAmounts($transaction, $user ?? auth()->user());
    $repartitionDetails = $displayService->getRepartitionDetails($transaction, $user ?? auth()->user());
    $user = $user ?? auth()->user();
    $userRole = $user ? $user->roles->pluck('name')->first() : 'customer';
@endphp

<div class="transaction-amounts-display" data-role="{{ $userRole }}">
    {{-- Montant principal selon le rôle --}}
    <div class="main-amount mb-4">
        <div class="flex justify-between items-center p-4 bg-{{ $amounts['display_type'] === 'admin' ? 'red' : ($amounts['display_type'] === 'integrator' ? 'blue' : ($amounts['display_type'] === 'operator' ? 'green' : 'gray')) }}-50 rounded-lg border border-{{ $amounts['display_type'] === 'admin' ? 'red' : ($amounts['display_type'] === 'integrator' ? 'blue' : ($amounts['display_type'] === 'operator' ? 'green' : 'gray')) }}-200">
            <div>
                <div class="text-sm font-medium text-gray-600 mb-1">
                    @if($userRole === 'admin')
                        Votre commission (Admin)
                    @elseif($userRole === 'integrator')
                        Votre commission (Intégrateur)
                    @elseif($userRole === 'operator')
                        Votre revenu net (Opérateur)
                    @elseif($userRole === 'partner')
                        Votre part (Partenaire)
                    @else
                        Montant total
                    @endif
                </div>
                <div class="text-3xl font-bold text-{{ $amounts['display_type'] === 'admin' ? 'red' : ($amounts['display_type'] === 'integrator' ? 'blue' : ($amounts['display_type'] === 'operator' ? 'green' : 'gray')) }}-700">
                    {{ number_format($amounts['your_amount'], 2) }} €
                </div>
            </div>
            <div class="text-right">
                <div class="text-sm text-gray-500">Total transaction</div>
                <div class="text-lg font-semibold text-gray-700">
                    {{ number_format($amounts['total_amount'], 2) }} €
                </div>
            </div>
        </div>
    </div>

    {{-- Détails des frais selon le rôle --}}
    @if(!empty($amounts['fees_breakdown']) && in_array($userRole, ['admin', 'integrator', 'operator', 'partner']))
    <div class="fees-breakdown mb-4">
        <h5 class="font-semibold text-gray-800 mb-3">
            <i class="fas fa-receipt mr-2"></i>
            Détails des Frais
        </h5>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Admin view --}}
            @if($userRole === 'admin')
                <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                    <div class="text-sm font-medium text-red-800 mb-2">Frais Admin</div>
                    <div class="space-y-1 text-sm">
                        <div class="flex justify-between">
                            <span class="text-red-700">Commission fixe:</span>
                            <span class="font-medium">{{ number_format($amounts['admin_fee'] ?? 0, 2) }} €</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-red-700">Total commission:</span>
                            <span class="font-bold">{{ number_format($amounts['admin_commission'] ?? 0, 2) }} €</span>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <div class="text-sm font-medium text-gray-800 mb-2">Répartition Totale</div>
                    <div class="space-y-1 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-700">Intégrateur:</span>
                            <span class="font-medium">{{ number_format($amounts['repartition']['integrator_amount'], 2) }} €</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-700">Opérateur:</span>
                            <span class="font-medium">{{ number_format($amounts['repartition']['operator_amount'], 2) }} €</span>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Integrator view --}}
            @if($userRole === 'integrator')
                <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                    <div class="text-sm font-medium text-blue-800 mb-2">Votre Part</div>
                    <div class="space-y-1 text-sm">
                        <div class="flex justify-between">
                            <span class="text-blue-700">Frais intégrateur:</span>
                            <span class="font-medium">{{ number_format($amounts['integrator_fee'] ?? 0, 2) }} €</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-blue-700">Commission nette:</span>
                            <span class="font-bold">{{ number_format($amounts['net_amount'] ?? $amounts['your_amount'], 2) }} €</span>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <div class="text-sm font-medium text-gray-800 mb-2">Déductions</div>
                    <div class="space-y-1 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-700">Déduction admin:</span>
                            <span class="font-medium text-red-600">-{{ number_format($amounts['fees_breakdown']['admin_deduction'] ?? 0, 2) }} €</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-700">Revenu opérateur:</span>
                            <span class="font-medium">{{ number_format($amounts['fees_breakdown']['remaining_for_operator'] ?? 0, 2) }} €</span>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Operator view --}}
            @if($userRole === 'operator')
                <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                    <div class="text-sm font-medium text-green-800 mb-2">Votre Revenu Net</div>
                    <div class="space-y-1 text-sm">
                        <div class="flex justify-between">
                            <span class="text-green-700">Montant total reçu:</span>
                            <span class="font-medium">{{ number_format($amounts['fees_breakdown']['total_received'] ?? $amounts['total_amount'], 2) }} €</span>
                        </div>
                        <div class="flex justify-between border-t border-green-300 pt-1 mt-1">
                            <span class="text-green-700 font-medium">Votre part nette:</span>
                            <span class="font-bold">{{ number_format($amounts['net_amount'] ?? $amounts['your_amount'], 2) }} €</span>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <div class="text-sm font-medium text-gray-800 mb-2">Déductions</div>
                    <div class="space-y-1 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-700">Déduction admin:</span>
                            <span class="font-medium text-red-600">-{{ number_format($amounts['deductions']['admin'] ?? 0, 2) }} €</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-700">Déduction intégrateur:</span>
                            <span class="font-medium text-red-600">-{{ number_format($amounts['deductions']['integrator'] ?? 0, 2) }} €</span>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Répartition complète (si disponible) --}}
    @if($repartitionDetails['has_repartition'] && in_array($userRole, ['admin', 'integrator']))
    <div class="full-repartition mt-4">
        <h5 class="font-semibold text-gray-800 mb-3">
            <i class="fas fa-chart-pie mr-2"></i>
            Répartition Complète
        </h5>
        <div class="grid grid-cols-3 gap-4">
            <div class="text-center p-3 bg-red-50 rounded-lg border border-red-200">
                <div class="text-xs text-red-700 mb-1">Admin</div>
                <div class="text-lg font-bold text-red-700">{{ number_format($repartitionDetails['admin_amount'], 2) }} €</div>
                <div class="text-xs text-red-600">{{ number_format($repartitionDetails['percentages']['admin_percentage'], 1) }}%</div>
            </div>
            <div class="text-center p-3 bg-blue-50 rounded-lg border border-blue-200">
                <div class="text-xs text-blue-700 mb-1">Intégrateur</div>
                <div class="text-lg font-bold text-blue-700">{{ number_format($repartitionDetails['integrator_amount'], 2) }} €</div>
                <div class="text-xs text-blue-600">{{ number_format($repartitionDetails['percentages']['integrator_percentage'], 1) }}%</div>
            </div>
            <div class="text-center p-3 bg-green-50 rounded-lg border border-green-200">
                <div class="text-xs text-green-700 mb-1">Opérateur</div>
                <div class="text-lg font-bold text-green-700">{{ number_format($repartitionDetails['operator_amount'], 2) }} €</div>
                <div class="text-xs text-green-600">{{ number_format($repartitionDetails['percentages']['operator_percentage'], 1) }}%</div>
            </div>
        </div>
    </div>
    @endif
</div>

