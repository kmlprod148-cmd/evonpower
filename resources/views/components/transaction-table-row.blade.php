@props(['transaction'])

@php
    // Utiliser le service centralisé pour calculer HT et TTC de manière cohérente
    $httcService = new \App\Services\TransactionHTTTCService();
    $httcAmounts = $httcService->calculateHTTTC($transaction);
    $amountHT = $httcAmounts['ht'];
    $amountTTC = $httcAmounts['ttc'];
    
    // Vérifier si c'est un tableau normalisé (fusion wallet + reservation) ou un objet Transaction
    if (is_array($transaction)) {
        // Transaction normalisée (fusion wallet + reservation)
        $type = $transaction['type'] ?? 'wallet';
        $transactionId = $transaction['id'] ?? '';
        $amount = abs($transaction['amount'] ?? 0);
        // Forcer EUR pour toutes les transactions
        $currency = 'EUR';
        $description = $transaction['description'] ?? 'N/A';
        $status = $transaction['status'] ?? 'completed';
        $createdAt = $transaction['created_at'] ?? now();
        $userName = auth()->user()->name ?? 'N/A';
        $userEmail = auth()->user()->email ?? '';
        $chargingPointName = $transaction['charging_point_name'] ?? ($transaction['charging_point']['name'] ?? ($transaction['charging_point'] ?? 'N/A'));
        $transactionTypeLabel = $type === 'reservation' ? 'Réservation' : 'Wallet';
        $paymentMethod = $type === 'reservation' ? 'Carte' : 'Wallet';
        $hasTransactionDetail = isset($transaction['transaction_detail']) && $transaction['transaction_detail'];
        
        // Récupérer les parts si TransactionDetail existe
        if ($hasTransactionDetail) {
            $detail = $transaction['transaction_detail'];
            $adminShare = (float)($detail->admin_share_amount ?? 0);
            $integratorShare = (float)($detail->integrator_share_amount ?? 0);
            $operatorShare = (float)($detail->operator_share_amount ?? 0);
        }
    } else {
        // Objet Transaction classique (ancien format)
        try {
            $calculator = new \App\Services\TransactionAmountCalculator();
            $transactionType = $calculator->getTransactionType($transaction);
            $paymentMethod = $calculator->getPaymentMethod($transaction);
        } catch (\Exception $e) {
            $transactionType = 'transaction';
            $paymentMethod = 'N/A';
        }
        
        $type = 'transaction';
        $transactionId = $transaction->id;
        $amount = $transaction->amount ?? $transaction->price_total ?? 0;
        // Forcer EUR pour toutes les transactions
        $currency = 'EUR';
        $description = $transaction->description ?? 'N/A';
        $status = $transaction->status ?? 'completed';
        $createdAt = $transaction->created_at ?? now();
        $userName = $transaction->user->name ?? 'N/A';
        $userEmail = $transaction->user->email ?? '';
        $chargingPointName = $transaction->chargingPoint->name ?? 'N/A';
        $transactionTypeLabel = $transactionType ?? 'Transaction';
        
        // Récupérer les parts si TransactionDetail existe
        if ($transaction->transactionDetail) {
            $detail = $transaction->transactionDetail;
            $adminShare = (float)($detail->admin_share_amount ?? 0);
            $integratorShare = (float)($detail->integrator_share_amount ?? 0);
            $operatorShare = (float)($detail->operator_share_amount ?? 0);
        }
    }
@endphp

<tr class="align-middle border-bottom-light hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
    <td class="px-6 py-4 whitespace-nowrap">
        <span class="badge bg-light text-dark fw-semibold">#{{ str_replace(['wallet_', 'reservation_'], '', $transactionId) }}</span>
        @if($type === 'reservation')
            <span class="ml-2 badge bg-info-light text-info text-xs">
                <i class="fas fa-calendar-check me-1"></i>Réservation
            </span>
        @elseif($type === 'wallet')
            <span class="ml-2 badge bg-secondary-light text-secondary text-xs">
                <i class="fas fa-wallet me-1"></i>Wallet
            </span>
        @endif
    </td>
    <td class="px-6 py-4 whitespace-nowrap">
        <div class="text-sm">
            <div class="font-medium text-gray-900 dark:text-white">{{ $createdAt instanceof \Carbon\Carbon ? $createdAt->format('M d, Y') : \Carbon\Carbon::parse($createdAt)->format('M d, Y') }}</div>
            <small class="text-gray-500 dark:text-gray-400">{{ $createdAt instanceof \Carbon\Carbon ? $createdAt->format('H:i:s') : \Carbon\Carbon::parse($createdAt)->format('H:i:s') }}</small>
        </div>
    </td>
    <td class="px-6 py-4 whitespace-nowrap">
        <div class="text-truncate" style="max-width: 200px;" title="{{ $userName }}">
            <div class="font-medium text-gray-900 dark:text-white">{{ $userName }}</div>
            @if($userEmail)
                <small class="text-gray-500 dark:text-gray-400">{{ $userEmail }}</small>
            @endif
        </div>
    </td>
    <td class="px-6 py-4 whitespace-nowrap">
        <span class="badge {{ $transactionTypeLabel === 'Réservation' ? 'bg-info-light text-info' : 'bg-secondary-light text-secondary' }}">
            <i class="fas {{ $transactionTypeLabel === 'Réservation' ? 'fa-calendar-check' : 'fa-wallet' }} me-1"></i>
            {{ $transactionTypeLabel }}
        </span>
    </td>
    <td class="px-6 py-4 whitespace-nowrap">
        <div class="text-truncate" style="max-width: 200px;" title="{{ $chargingPointName }}">
            <span class="font-medium text-gray-900 dark:text-white">{{ $chargingPointName }}</span>
            @if(isset($transaction['transaction']) && $transaction['transaction']->charging_point_id)
                <br><small class="text-gray-500 dark:text-gray-400">ID: {{ $transaction['transaction']->charging_point_id }}</small>
            @endif
        </div>
    </td>
    <td class="px-6 py-4 whitespace-nowrap">
        <span class="font-medium text-blue-600 dark:text-blue-400">
            {{ number_format($amountHT, 2) }} {{ $currency }}
        </span>
    </td>
    <td class="px-6 py-4">
        <div class="space-y-2">
            {{-- Montant TTC --}}
            <div>
        <span class="font-medium {{ $status === 'completed' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
            {{ number_format($amountTTC, 2) }} {{ $currency }}
        </span>
            </div>
        </div>
    </td>
    <td class="px-6 py-4 whitespace-nowrap">
        <span class="badge {{ $status === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : ($status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200') }}">
            <i class="fas {{ $status === 'completed' ? 'fa-check' : ($status === 'pending' ? 'fa-clock' : 'fa-times') }} me-1"></i>
            {{ ucfirst($status) }}
        </span>
    </td>
    <td class="px-6 py-4 whitespace-nowrap">
        <span class="badge bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
            <i class="fas fa-credit-card me-1"></i>
            {{ ucfirst($paymentMethod) }}
        </span>
    </td>
    @if(auth()->user()->role === 'admin')
    <td class="px-6 py-4">
        @php
            // Pour l'admin : afficher un résumé des parts directement dans le tableau
            $transactionDetail = null;
            $transactionModel = null;
            
            if (is_array($transaction)) {
                if ($type === 'reservation' && isset($transaction['transaction_detail'])) {
                    $transactionDetail = $transaction['transaction_detail'];
                } elseif ($type === 'reservation' && isset($transaction['transaction'])) {
                    $transactionModel = $transaction['transaction'];
                    if ($transactionModel && !$transactionModel->relationLoaded('transactionDetail')) {
                        $transactionModel->load('transactionDetail');
                    }
                    $transactionDetail = $transactionModel->transactionDetail ?? null;
                }
            } elseif (!is_array($transaction)) {
                if (method_exists($transaction, 'transactionDetail')) {
                    if (!$transaction->relationLoaded('transactionDetail')) {
                        $transaction->load('transactionDetail');
                    }
                    $transactionDetail = $transaction->transactionDetail;
                }
            }
            
            $adminShare = $transactionDetail ? (float)($transactionDetail->admin_share_amount ?? 0) : 0;
            $integratorShare = $transactionDetail ? (float)($transactionDetail->integrator_share_amount ?? 0) : 0;
            $operatorShare = $transactionDetail ? (float)($transactionDetail->operator_share_amount ?? 0) : 0;
            $hasParts = $transactionDetail && ($adminShare > 0 || $integratorShare > 0 || $operatorShare > 0);
        @endphp
        
        @if($hasParts)
            @php
                $totalAmountForPct = $transactionDetail->transaction->amount ?? ($transaction['amount'] ?? $totalAmount ?? 1);
                $adminPct = $totalAmountForPct > 0 ? ($adminShare / $totalAmountForPct * 100) : 0;
                $integratorPct = $totalAmountForPct > 0 ? ($integratorShare / $totalAmountForPct * 100) : 0;
                $operatorPct = $totalAmountForPct > 0 ? ($operatorShare / $totalAmountForPct * 100) : 0;
                $totalShares = $adminShare + $integratorShare + $operatorShare;
                $consistencyCheck = abs($totalShares - $totalAmountForPct) < 0.01;
            @endphp
            <div class="space-y-1.5" data-toggle="tooltip" data-placement="left" 
                 title="Cliquez pour voir les détails complets du calcul">
                @if($adminShare > 0)
                    <div class="flex items-center justify-between text-xs group hover:bg-red-50 dark:hover:bg-red-900/10 px-2 py-1 rounded transition-colors duration-200">
                        <div class="flex items-center flex-1">
                            <span class="inline-block w-2.5 h-2.5 bg-red-500 rounded-full mr-2 shadow-sm"></span>
                            <span class="text-gray-700 dark:text-gray-300 font-medium">Admin:</span>
                            @if($transactionDetail && $transactionDetail->admin_share_percentage)
                                <span class="ml-2 text-red-600 dark:text-red-400 text-xs opacity-75">
                                    ({{ number_format($transactionDetail->admin_share_percentage, 2) }}%)
                                </span>
                            @elseif($adminPct > 0)
                                <span class="ml-2 text-red-600 dark:text-red-400 text-xs opacity-75">
                                    ({{ number_format($adminPct, 2) }}%)
                                </span>
                            @endif
                        </div>
                        <span class="ml-auto font-bold text-red-600 dark:text-red-400 tabular-nums">
                            {{ number_format($adminShare, 2) }} {{ $currency }}
                        </span>
                    </div>
                @endif
                @if($integratorShare > 0)
                    <div class="flex items-center justify-between text-xs group hover:bg-yellow-50 dark:hover:bg-yellow-900/10 px-2 py-1 rounded transition-colors duration-200">
                        <div class="flex items-center flex-1">
                            <span class="inline-block w-2.5 h-2.5 bg-yellow-500 rounded-full mr-2 shadow-sm"></span>
                            <span class="text-gray-700 dark:text-gray-300 font-medium">Intégrateur:</span>
                            @if($transactionDetail && $transactionDetail->integrator_share_percentage)
                                <span class="ml-2 text-yellow-600 dark:text-yellow-400 text-xs opacity-75">
                                    ({{ number_format($transactionDetail->integrator_share_percentage, 2) }}%)
                                </span>
                            @elseif($integratorPct > 0)
                                <span class="ml-2 text-yellow-600 dark:text-yellow-400 text-xs opacity-75">
                                    ({{ number_format($integratorPct, 2) }}%)
                                </span>
                            @endif
                        </div>
                        <span class="ml-auto font-bold text-yellow-600 dark:text-yellow-400 tabular-nums">
                            {{ number_format($integratorShare, 2) }} {{ $currency }}
                        </span>
                    </div>
                @endif
                @if($operatorShare > 0)
                    <div class="flex items-center justify-between text-xs group hover:bg-blue-50 dark:hover:bg-blue-900/10 px-2 py-1 rounded transition-colors duration-200">
                        <div class="flex items-center flex-1">
                            <span class="inline-block w-2.5 h-2.5 bg-blue-500 rounded-full mr-2 shadow-sm"></span>
                            <span class="text-gray-700 dark:text-gray-300 font-medium">Opérateur:</span>
                            @if($operatorPct > 0)
                                <span class="ml-2 text-blue-600 dark:text-blue-400 text-xs opacity-75">
                                    ({{ number_format($operatorPct, 2) }}%)
                                </span>
                            @endif
                        </div>
                        <span class="ml-auto font-bold text-blue-600 dark:text-blue-400 tabular-nums">
                            {{ number_format($operatorShare, 2) }} {{ $currency }}
                        </span>
                    </div>
                @endif
                <div class="mt-1.5 pt-1.5 border-t border-gray-200 dark:border-gray-600">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-green-600 dark:text-green-400 font-medium">
                            <i class="fas fa-check-circle me-1"></i>Selon Business Profiles
                        </span>
                        @if($consistencyCheck)
                            <span class="text-xs text-green-500" title="Vérification de cohérence: OK">
                                <i class="fas fa-shield-check"></i>
                            </span>
                        @else
                            <span class="text-xs text-amber-500" title="Écart détecté: {{ number_format(abs($totalShares - $totalAmountForPct), 2) }} {{ $currency }}">
                                <i class="fas fa-exclamation-triangle"></i>
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        @elseif($isPending)
            <div class="text-xs text-amber-600 dark:text-amber-400">
                <i class="fas fa-clock me-1"></i>
                En attente
            </div>
        @else
            <div class="text-xs text-gray-500 dark:text-gray-400">
                <i class="fas fa-info-circle me-1"></i>
                Aucun frais
            </div>
        @endif
    </td>
    @endif
    <td class="px-6 py-4 whitespace-nowrap text-right">
        <div class="flex items-center justify-end gap-2">
        @php
            $isAdmin = auth()->user()->role === 'admin';
            $hasTransactionDetail = false;
            $transactionModel = null;
            
            // Déterminer le modèle de transaction et si TransactionDetail existe
            if ($type === 'reservation' && isset($transaction['transaction'])) {
                $transactionModel = $transaction['transaction'];
                $hasTransactionDetail = isset($transactionModel->transactionDetail) && $transactionModel->transactionDetail;
            } elseif ($type === 'wallet' && isset($transaction['wallet_transaction'])) {
                $transactionModel = $transaction['wallet_transaction'];
            } elseif (isset($transaction) && !is_array($transaction)) {
                $transactionModel = $transaction;
                if (method_exists($transaction, 'transactionDetail')) {
                    $hasTransactionDetail = $transaction->transactionDetail !== null;
                }
            }
        @endphp
        
        @if($type === 'reservation' && isset($transaction['transaction']))
            {{-- Bouton Voir détails - Toujours visible, avec texte pour Admin --}}
            <a href="{{ route('transactions.show', $transaction['transaction']) }}"
               class="inline-flex items-center justify-center {{ $isAdmin ? 'gap-2 px-4 py-2 bg-blue-600 text-white hover:bg-blue-700' : 'gap-0 px-2.5 py-2 border-2 border-blue-500 text-blue-800 bg-blue-50 hover:bg-blue-100' }} text-sm font-medium rounded-md transition-colors"
               title="Voir détails de la transaction">
                <i class="fas fa-eye {{ $isAdmin ? '' : 'text-base' }}"></i>
                @if($isAdmin)
                    <span>Voir détails</span>
                @endif
            </a>
            {{-- Bouton Breakdown - Visible uniquement pour Admin avec TransactionDetail --}}
            @if($isAdmin && $hasTransactionDetail)
                <button type="button" 
                        class="relative inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium rounded-md border border-cyan-300 text-cyan-700 bg-white hover:bg-cyan-50 transition-colors" 
                        data-bs-toggle="modal" 
                        data-bs-target="#transactionFeesModal"
                        data-transaction-id="{{ $transaction['transaction']->id }}"
                        title="Voir breakdown complet des frais et calculs">
                    <i class="fas fa-calculator"></i>
                    <span class="absolute -top-1 -right-1 w-3 h-3 bg-cyan-500 rounded-full flex items-center justify-center" style="font-size: 0.5rem;">
                        <i class="fas fa-info text-white" style="font-size: 0.35rem;"></i>
                    </span>
                </button>
            @endif
        @elseif($type === 'wallet' && isset($transaction['wallet_transaction']))
            <a href="{{ route('transactions.show', $transaction['wallet_transaction']) }}"
               class="inline-flex items-center justify-center {{ $isAdmin ? 'gap-2 px-4 py-2 bg-blue-600 text-white hover:bg-blue-700' : 'gap-0 px-2.5 py-2 border-2 border-blue-500 text-blue-800 bg-blue-50 hover:bg-blue-100' }} text-sm font-medium rounded-md transition-colors"
               title="Voir détails de la transaction">
                <i class="fas fa-eye {{ $isAdmin ? '' : 'text-base' }}"></i>
                @if($isAdmin)
                    <span>Voir détails</span>
                @endif
            </a>
        @elseif(isset($transaction) && !is_array($transaction))
            <a href="{{ route('transactions.show', $transaction) }}"
               class="inline-flex items-center justify-center {{ $isAdmin ? 'gap-2 px-4 py-2 bg-blue-600 text-white hover:bg-blue-700' : 'gap-0 px-2.5 py-2 border-2 border-blue-500 text-blue-800 bg-blue-50 hover:bg-blue-100' }} text-sm font-medium rounded-md transition-colors"
               title="Voir détails de la transaction">
                <i class="fas fa-eye {{ $isAdmin ? '' : 'text-base' }}"></i>
                @if($isAdmin)
                    <span>Voir détails</span>
                @endif
            </a>
            {{-- Bouton Breakdown - Visible uniquement pour Admin avec TransactionDetail --}}
            @if($isAdmin && method_exists($transaction, 'transactionDetail') && $transaction->transactionDetail)
                <button type="button" 
                        class="relative inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium rounded-md border border-cyan-300 text-cyan-700 bg-white hover:bg-cyan-50 transition-colors" 
                        data-bs-toggle="modal" 
                        data-bs-target="#transactionFeesModal"
                        data-transaction-id="{{ $transaction->id }}"
                        title="Voir breakdown complet des frais et calculs">
                    <i class="fas fa-calculator"></i>
                    <span class="absolute -top-1 -right-1 w-3 h-3 bg-cyan-500 rounded-full flex items-center justify-center" style="font-size: 0.5rem;">
                        <i class="fas fa-info text-white" style="font-size: 0.35rem;"></i>
                    </span>
                </button>
            @endif
        @else
            {{-- Fallback pour les transactions non identifiées --}}
            @if($isAdmin && isset($transaction['id']))
                <a href="{{ route('transactions.show', $transaction['id']) }}"
                   class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700 transition-colors"
                   title="Voir détails de la transaction">
                    <i class="fas fa-eye"></i>
                    <span>Voir détails</span>
                </a>
            @endif
        @endif
        </div>
    </td>
</tr>

