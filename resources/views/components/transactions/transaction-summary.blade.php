@props(['transaction'])

@php
    // Utiliser le service centralisé pour calculer HT et TTC
    try {
        $httcService = new \App\Services\TransactionHTTTCService();
        $httcAmounts = $httcService->calculateHTTTC($transaction);
        $amountHT = $httcAmounts['ht'];
        $amountTTC = $httcAmounts['ttc'];
        $vatAmount = $httcAmounts['vat_amount'];
    } catch (\Exception $e) {
        $amountHT = $transaction->amount ?? $transaction->price_total ?? 0;
        $amountTTC = $transaction->amount ?? $transaction->price_total ?? 0;
        $vatAmount = 0;
    }
    
    // Utiliser TransactionAmountCalculator pour les autres informations
    $calculator = new \App\Services\TransactionAmountCalculator();
    $summary = $calculator->getTransactionSummary($transaction);
    // Remplacer les montants HT/TTC par ceux calculés par le service centralisé
    $summary['amount_ht'] = $amountHT;
    $summary['amount_ttc'] = $amountTTC;
    $summary['fees'] = $vatAmount;
@endphp

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="card-title mb-0">
            <i class="fas fa-receipt me-2 text-primary"></i>{{ __('Transaction Summary') }}
        </h6>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm bg-light rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-user text-muted"></i>
                    </div>
                    <div>
                        <div class="fw-bold">{{ $summary['user'] }}</div>
                        <small class="text-muted">{{ $summary['user_email'] }}</small>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm bg-light rounded-circle d-flex align-items-center justify-content-center me-3">
                        <i class="fas fa-charging-station text-muted"></i>
                    </div>
                    <div>
                        <div class="fw-bold">{{ $summary['charging_point'] }}</div>
                        <small class="text-muted">ID: {{ $summary['charging_point_id'] }}</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center">
                    <div class="fw-bold text-primary">{{ number_format($summary['amount_ht'], 2) }} €</div>
                    <small class="text-muted">{{ __('Amount HT') }}</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center">
                    <div class="fw-bold text-success">{{ number_format($summary['amount_ttc'], 2) }} €</div>
                    <small class="text-muted">{{ __('Amount TTC') }}</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center">
                    <div class="fw-bold text-warning">{{ number_format($summary['fees'], 2) }} €</div>
                    <small class="text-muted">{{ __('Fees') }}</small>
                </div>
            </div>
            <div class="col-md-6">
                <span class="badge {{ $summary['type'] === 'prépayée' ? 'bg-info-light text-info' : 'bg-warning-light text-warning' }}">
                    <i class="fas {{ $summary['type'] === 'prépayée' ? 'fa-credit-card' : 'fa-clock' }} me-1"></i>
                    {{ ucfirst($summary['type']) }}
                </span>
            </div>
            <div class="col-md-6">
                <span class="badge {{ $summary['status'] === 'completed' ? 'bg-success-light text-success' : ($summary['status'] === 'pending' ? 'bg-warning-light text-warning' : 'bg-danger-light text-danger') }}">
                    {{ ucfirst($summary['status']) }}
                </span>
            </div>
        </div>
    </div>
</div>
