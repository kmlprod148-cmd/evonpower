@props(['transaction'])

@php
    $calculator = new \App\Services\TransactionAmountCalculator();
    $amounts = $calculator->calculateAmounts($transaction);
    $transactionType = $calculator->getTransactionType($transaction);
    $paymentMethod = $calculator->getPaymentMethod($transaction);
@endphp

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-2">
                <div class="text-center">
                    <div class="stat-icon {{ $transaction->status === 'completed' ? 'bg-success-light text-success' : ($transaction->status === 'pending' ? 'bg-warning-light text-warning' : 'bg-danger-light text-danger') }} mx-auto mb-2">
                        <i class="fas {{ $transaction->status === 'completed' ? 'fa-check' : ($transaction->status === 'pending' ? 'fa-clock' : 'fa-times') }}"></i>
                    </div>
                    <small class="text-muted">#{{ $transaction->id }}</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="fw-bold">{{ $transaction->user->name ?? 'N/A' }}</div>
                <small class="text-muted">{{ $transaction->user->email ?? '' }}</small>
            </div>
            <div class="col-md-2">
                <div class="fw-semibold">{{ $transaction->chargingPoint->name ?? 'N/A' }}</div>
                <small class="text-muted">ID: {{ $transaction->chargingPoint->id ?? 'N/A' }}</small>
            </div>
            <div class="col-md-2">
                <div class="text-center">
                    <div class="fw-bold text-primary">{{ number_format($amounts['ht'], 2) }} €</div>
                    <small class="text-muted">HT</small>
                </div>
            </div>
            <div class="col-md-2">
                <div class="text-center">
                    <div class="fw-bold text-success">{{ number_format($amounts['ttc'], 2) }} €</div>
                    <small class="text-muted">TTC</small>
                </div>
            </div>
            <div class="col-md-1">
                <a href="{{ route('transactions.show', $transaction) }}" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-eye"></i>
                </a>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-md-3">
                <span class="badge {{ $transactionType === 'prépayée' ? 'bg-info-light text-info' : 'bg-warning-light text-warning' }}">
                    <i class="fas {{ $transactionType === 'prépayée' ? 'fa-credit-card' : 'fa-clock' }} me-1"></i>
                    {{ ucfirst($transactionType) }}
                </span>
            </div>
            <div class="col-md-3">
                <span class="badge bg-secondary-light text-secondary">
                    <i class="fas fa-credit-card me-1"></i>
                    {{ ucfirst($paymentMethod) }}
                </span>
            </div>
            <div class="col-md-3">
                <small class="text-muted">
                    <i class="fas fa-calendar me-1"></i>
                    {{ $transaction->created_at->format('M d, Y H:i') }}
                </small>
            </div>
            <div class="col-md-3">
                <span class="badge {{ $transaction->status === 'completed' ? 'bg-success-light text-success' : ($transaction->status === 'pending' ? 'bg-warning-light text-warning' : 'bg-danger-light text-danger') }}">
                    {{ ucfirst($transaction->status) }}
                </span>
            </div>
        </div>
    </div>
</div>
