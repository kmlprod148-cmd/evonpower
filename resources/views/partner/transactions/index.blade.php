@extends('layouts.dashboard')

@section('title', __('transactions.partner_transactions'))
@section('page-title', __('transactions.partner_transactions'))

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
@endpush

@section('content')
@php
    $displayCurrency = $balance['currency'] ?? ($appCurrency ?? 'EUR');
@endphp
<div class="row">
    <!-- Balance Card -->
    <div class="col-12 mb-4">
        <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <h6 class="text-white-50 mb-1">{{ __('wallet.available_to_withdraw') }}</h6>
                        <h2 class="text-white mb-0" style="font-size: 2.5rem;">
                            {{ number_format($balance['available'] ?? 0, 2) }} 
                            <small>{{ $displayCurrency }}</small>
                        </h2>
                    </div>
                    <div class="col-md-3">
                        <h6 class="text-white-50 mb-1">{{ __('wallet.total_transactions') }}</h6>
                        <h4 class="text-white mb-0">
                            {{ number_format($balance['total'] ?? 0, 2) }} 
                            <small>{{ $displayCurrency }}</small>
                        </h4>
                    </div>
                    <div class="col-md-3">
                        <h6 class="text-white-50 mb-1">{{ __('wallet.withdrawn') }}</h6>
                        <h4 class="text-white mb-0">
                            {{ number_format($balance['withdrawn'] ?? 0, 2) }} 
                            <small>{{ $displayCurrency }}</small>
                        </h4>
                    </div>
                    <div class="col-md-3 text-md-end">
                        <button type="button" class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#withdrawalModal">
                            <i class="fas fa-wallet me-2"></i>
                            {{ __('wallet.request_withdrawal') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="col-md-4 mb-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">{{ __('transactions.pending_withdrawal') }}</h6>
                        <h4 class="mb-0">{{ number_format($pendingWithdrawal ?? 0, 2) }} {{ $displayCurrency }}</h4>
                    </div>
                    <div class="bg-warning bg-opacity-10 p-3 rounded">
                        <i class="fas fa-clock text-warning fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">{{ __('transactions.today_revenue') }}</h6>
                        <h4 class="mb-0">{{ number_format($todayRevenue ?? 0, 2) }} {{ $displayCurrency }}</h4>
                    </div>
                    <div class="bg-success bg-opacity-10 p-3 rounded">
                        <i class="fas fa-chart-line text-success fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">{{ __('transactions.total_transactions') }}</h6>
                        <h4 class="mb-0">{{ $totalTransactions ?? 0 }}</h4>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-3 rounded">
                        <i class="fas fa-receipt text-primary fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="col-12 mb-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form method="GET" action="{{ route('partner.transactions.index') }}" class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">{{ __('filters.date_from') }}</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">{{ __('filters.date_to') }}</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">{{ __('filters.status') }}</label>
                        <select name="status" class="form-select">
                            <option value="">{{ __('filters.all') }}</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>{{ __('status.completed') }}</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>{{ __('status.pending') }}</option>
                            <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>{{ __('status.failed') }}</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">{{ __('filters.type') }}</label>
                        <select name="type" class="form-select">
                            <option value="">{{ __('filters.all') }}</option>
                            <option value="charge" {{ request('type') == 'charge' ? 'selected' : '' }}>{{ __('transactions.types.charge') }}</option>
                            <option value="subscription" {{ request('type') == 'subscription' ? 'selected' : '' }}>{{ __('transactions.types.subscription') }}</option>
                            <option value="wallet_topup" {{ request('type') == 'wallet_topup' ? 'selected' : '' }}>{{ __('transactions.types.wallet_topup') }}</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">{{ __('filters.payment_method') }}</label>
                        <select name="payment_method" class="form-select">
                            <option value="">{{ __('filters.all') }}</option>
                            <option value="wallet" {{ request('payment_method') == 'wallet' ? 'selected' : '' }}>{{ __('payment.wallet') }}</option>
                            <option value="card" {{ request('payment_method') == 'card' ? 'selected' : '' }}>{{ __('payment.card') }}</option>
                            <option value="subscription" {{ request('payment_method') == 'subscription' ? 'selected' : '' }}>{{ __('payment.subscription') }}</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="d-flex gap-2 w-100">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="fas fa-search me-1"></i> {{ __('actions.search') }}
                            </button>
                            <a href="{{ route('partner.transactions.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-redo"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('transactions.list') }}</h5>
                    <a href="{{ route('partner.transactions.export', request()->query()) }}" class="btn btn-success btn-sm">
                        <i class="fas fa-download me-1"></i> {{ __('actions.export_csv') }}
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="transactionsTable">
                        <thead class="bg-light">
                            <tr>
                                <th>{{ __('fields.id') }}</th>
                                <th>{{ __('fields.date') }}</th>
                                <th>{{ __('fields.type') }}</th>
                                <th>{{ __('fields.user') }}</th>
                                <th>{{ __('fields.charging_point') }}</th>
                                <th>{{ __('fields.amount') }}</th>
                                <th>{{ __('fields.vat') }}</th>
                                <th>{{ __('fields.total') }}</th>
                                <th>{{ __('fields.payment_method') }}</th>
                                <th>{{ __('fields.status') }}</th>
                                <th>{{ __('fields.collect_status') }}</th>
                                <th>{{ __('fields.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $transaction)
                                <tr>
                                    <td>
                                        <a href="{{ route('partner.transactions.show', $transaction->id) }}" class="text-decoration-none">
                                            #{{ $transaction->id }}
                                        </a>
                                    </td>
                                    <td>{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <span class="badge bg-{{ $transaction->transaction_type === 'charge' ? 'primary' : 'info' }}">
                                            {{ __('transactions.types.' . $transaction->transaction_type) }}
                                        </span>
                                    </td>
                                    <td>{{ $transaction->user->name ?? '-' }}</td>
                                    <td>{{ $transaction->chargingPoint->name ?? '-' }}</td>
                                    <td>{{ number_format($transaction->amount ?? 0, 2) }} {{ $transaction->currency ?? $displayCurrency }}</td>
                                    <td>{{ number_format($transaction->vat_amount ?? 0, 2) }} {{ $transaction->currency ?? $displayCurrency }}</td>
                                    <td class="fw-bold">{{ number_format($transaction->total_amount ?? $transaction->amount ?? 0, 2) }} {{ $transaction->currency ?? $displayCurrency }}</td>
                                    <td>
                                        @if($transaction->payment_method)
                                            <span class="badge bg-secondary">{{ __('payment.' . $transaction->payment_method) }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $transaction->status === 'completed' ? 'success' : ($transaction->status === 'pending' ? 'warning' : 'danger') }}">
                                            {{ __('status.' . $transaction->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $transaction->collect_status === 'withdrawn' ? 'success' : ($transaction->collect_status === 'collected' ? 'info' : ($transaction->collect_status === 'to_collect' ? 'warning' : 'secondary')) }}">
                                            {{ __('collect_status.' . $transaction->collect_status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('partner.transactions.show', $transaction->id) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="text-center py-5">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">{{ __('messages.no_transactions') }}</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($transactions->hasPages())
                <div class="card-footer bg-white">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Withdrawal Modal -->
@include('partner.partials.withdrawal-modal')
@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script>
    $(document).ready(function() {
        $('#transactionsTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr.json'
            },
            order: [[1, 'desc']],
            pageLength: 15,
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'csv',
                    className: 'btn btn-sm btn-secondary',
                    text: '<i class="fas fa-file-csv me-1"></i> CSV'
                }
            ]
        });
    });
</script>
@endpush
