@extends('layouts.admin')

@section('title', 'Transaction Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-exchange-alt"></i>
                        Transaction Management
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.transactions.export', request()->query()) }}" 
                           class="btn btn-success btn-sm">
                            <i class="fas fa-download"></i> Export CSV
                        </a>
                    </div>
                </div>

                    <!-- Statistics Cards -->
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-lg-2 col-6">
                                <div class="small-box bg-info">
                                    <div class="inner">
                                        <h3>{{ number_format($stats['total_transactions']) }}</h3>
                                        <p>Total Transactions</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-list"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-6">
                                <div class="small-box bg-success">
                                    <div class="inner">
                                        <h3>{{ number_format($stats['total_credits'], 2) }} €</h3>
                                        <p>Total Credits</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-arrow-up"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-6">
                                <div class="small-box bg-danger">
                                    <div class="inner">
                                        <h3>{{ number_format($stats['total_debits'], 2) }} €</h3>
                                        <p>Total Debits</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-arrow-down"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-6">
                                <div class="small-box {{ $stats['net_amount'] >= 0 ? 'bg-success' : 'bg-warning' }}">
                                    <div class="inner">
                                        <h3>{{ number_format($stats['net_amount'], 2) }} €</h3>
                                        <p>Net Amount</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-balance-scale"></i>
                                    </div>
                                </div>
                            </div>
                            @if(isset($stats['total_wallet_balance']))
                            <div class="col-lg-2 col-6">
                                <div class="small-box bg-primary">
                                    <div class="inner">
                                        <h3>{{ number_format($stats['total_wallet_balance'], 2) }} €</h3>
                                        <p>Total Wallet Balance</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-wallet"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-6">
                                <div class="small-box bg-secondary">
                                    <div class="inner">
                                        <h3>{{ number_format($stats['total_wallets'] ?? 0) }}</h3>
                                        <p>Total Wallets</p>
                                    </div>
                                    <div class="icon">
                                        <i class="fas fa-credit-card"></i>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                        
                        @if(isset($stats['wallet_stats_by_owner']) && $stats['wallet_stats_by_owner']->isNotEmpty())
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">
                                            <i class="fas fa-chart-pie"></i> Statistiques par Type de Propriétaire
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            @foreach($stats['wallet_stats_by_owner'] as $ownerType => $stat)
                                            <div class="col-md-3">
                                                <div class="info-box">
                                                    <span class="info-box-icon bg-info">
                                                        <i class="fas fa-user-tag"></i>
                                                    </span>
                                                    <div class="info-box-content">
                                                        <span class="info-box-text">{{ class_basename($ownerType) }}</span>
                                                        <span class="info-box-number">{{ $stat->count }} wallets</span>
                                                        <span class="info-box-text">{{ number_format($stat->total_balance, 2) }} €</span>
                                                    </div>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                    <!-- Filters -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-filter"></i> Filters
                                    </h5>
                                    <div class="card-tools">
                                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <form method="GET" action="{{ route('admin.transactions.index') }}" id="filterForm">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="role">Role</label>
                                                    <select name="role" id="role" class="form-control">
                                                        <option value="">All Roles</option>
                                                        @foreach($roles as $role)
                                                            <option value="{{ $role->name }}" 
                                                                    {{ $filters['role'] == $role->name ? 'selected' : '' }}>
                                                                {{ ucfirst($role->name) }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="user">User</label>
                                                    <select name="user" id="user" class="form-control select2">
                                                        <option value="">All Users</option>
                                                        @foreach($users as $user)
                                                            <option value="{{ $user->id }}" 
                                                                    {{ $filters['user'] == $user->id ? 'selected' : '' }}>
                                                                {{ $user->name }} ({{ $user->email }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label for="type">Type</label>
                                                    <select name="type" id="type" class="form-control">
                                                        <option value="">All Types</option>
                                                        @foreach($transactionTypes as $type)
                                                            <option value="{{ $type }}" 
                                                                    {{ $filters['type'] == $type ? 'selected' : '' }}>
                                                                {{ ucfirst($type) }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label for="date_from">From Date</label>
                                                    <input type="date" name="date_from" id="date_from" 
                                                           class="form-control" value="{{ $filters['date_from'] }}">
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label for="date_to">To Date</label>
                                                    <input type="date" name="date_to" id="date_to" 
                                                           class="form-control" value="{{ $filters['date_to'] }}">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="search">Search</label>
                                                    <input type="text" name="search" id="search" 
                                                           class="form-control" placeholder="Search by description or owner name..." 
                                                           value="{{ $filters['search'] }}">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>&nbsp;</label>
                                                    <div class="btn-group w-100">
                                                        <button type="submit" class="btn btn-primary">
                                                            <i class="fas fa-search"></i> Filter
                                                        </button>
                                                        <a href="{{ route('admin.transactions.index') }}" class="btn btn-secondary">
                                                            <i class="fas fa-times"></i> Clear
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Transactions Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Owner</th>
                                    <th>Role</th>
                                    <th>Amount</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th>Balance</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $transaction)
                                    <tr>
                                        <td>
                                            <span class="badge badge-secondary">#{{ $transaction->id }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm bg-light rounded-circle d-flex align-items-center justify-content-center me-2">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold">{{ $transaction->owner_name ?? 'N/A' }}</div>
                                                    <small class="text-muted">{{ $transaction->owner_email ?? 'N/A' }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if($transaction->wallet && $transaction->wallet->owner)
                                                @php
                                                    $owner = $transaction->wallet->owner;
                                                    $role = $owner->roles->first();
                                                @endphp
                                                @if($role)
                                                    <span class="badge badge-info">{{ ucfirst($role->name) }}</span>
                                                @else
                                                    <span class="badge badge-secondary">No Role</span>
                                                @endif
                                            @else
                                                <span class="badge badge-secondary">Unknown</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="fw-bold {{ $transaction->type === 'credit' ? 'text-success' : 'text-danger' }}">
                                                {{ $transaction->type === 'credit' ? '+' : '-' }}{{ number_format($transaction->amount, 2) }} €
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge {{ $transaction->type === 'credit' ? 'badge-success' : 'badge-danger' }}">
                                                <i class="fas {{ $transaction->type === 'credit' ? 'fa-arrow-up' : 'fa-arrow-down' }}"></i>
                                                {{ ucfirst($transaction->type) }}
                                            </span>
                                        </td>
                                        <td>
                                            @php
                                                $sourceInfo = $transaction->getSourceInfo();
                                                $displayDescription = $sourceInfo['description'];
                                            @endphp
                                            <div class="text-truncate" style="max-width: 200px;" title="{{ $displayDescription }}">
                                                {{ $displayDescription }}
                                            </div>
                                            @if($sourceInfo['has_related_transaction'])
                                                <small class="text-muted d-block" style="font-size: 0.75em;">
                                                    <i class="fas fa-link"></i> Transaction #{{ $sourceInfo['transaction_id'] }}
                                                    @if($sourceInfo['has_reservation'])
                                                        | <i class="fas fa-calendar"></i> Réservation #{{ $sourceInfo['reservation_id'] }}
                                                    @endif
                                                    @if(isset($sourceInfo['has_transaction_detail']) && $sourceInfo['has_transaction_detail'])
                                                        | <i class="fas fa-chart-pie"></i> TransactionDetail #{{ $sourceInfo['transaction_detail_id'] }}
                                                    @endif
                                                </small>
                                            @elseif(isset($sourceInfo['has_transaction_detail']) && $sourceInfo['has_transaction_detail'])
                                                <small class="text-muted d-block" style="font-size: 0.75em;">
                                                    <i class="fas fa-chart-pie"></i> TransactionDetail #{{ $sourceInfo['transaction_detail_id'] }}
                                                </small>
                                            @endif
                                            @if($transaction->metadata && isset($transaction->metadata['source']))
                                                <small class="text-info d-block" style="font-size: 0.75em;">
                                                    <i class="fas fa-info-circle"></i> Source: {{ $transaction->metadata['source'] }}
                                                </small>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                // Afficher le solde du wallet après la transaction (balance_after)
                                                $currentBalance = $transaction->current_balance ?? $transaction->balance_after ?? 0;
                                                $balanceBefore = $transaction->balance_before ?? 0;
                                                $relatedTransaction = $transaction->getTransactionModel();
                                                $adminFee = $relatedTransaction ? $relatedTransaction->getAdminFee() : 0;
                                            @endphp
                                            <div>
                                                <span class="fw-bold text-primary">{{ number_format($currentBalance, 2) }} €</span>
                                                <small class="text-muted d-block" style="font-size: 0.75em;">
                                                    Avant: {{ number_format($balanceBefore, 2) }} €
                                                </small>
                                                @if($adminFee > 0 && $transaction->type === 'credit')
                                                    <small class="text-success d-block" style="font-size: 0.75em;">
                                                        <i class="fas fa-info-circle"></i> Frais admin: {{ number_format($adminFee, 2) }} €
                                                    </small>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <div>{{ $transaction->created_at->format('M d, Y') }}</div>
                                                <small class="text-muted">{{ $transaction->created_at->format('H:i:s') }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="{{ route('admin.transactions.show', $transaction) }}" 
                                                   class="btn btn-sm btn-outline-primary" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @php
                                                    $relatedTransaction = $transaction->getTransactionModel();
                                                @endphp
                                                @if($relatedTransaction)
                                                    <a href="{{ route('admin.transactions.show', $relatedTransaction) }}" 
                                                       class="btn btn-sm btn-outline-info" title="View Related Transaction">
                                                        <i class="fas fa-exchange-alt"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-inbox fa-2x mb-2"></i>
                                                <div>No transactions found</div>
                                                @if(array_filter($filters))
                                                    <small>Try adjusting your filters</small>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($transactions->hasPages())
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div class="text-muted">
                                Showing {{ $transactions->firstItem() }} to {{ $transactions->lastItem() }} 
                                of {{ $transactions->total() }} results
                            </div>
                            <div>
                                {{ $transactions->links() }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .avatar-sm {
        width: 32px;
        height: 32px;
    }
    
    .table td {
        vertical-align: middle;
    }
    
    .badge {
        font-size: 0.75em;
    }
    
    .small-box {
        border-radius: 0.25rem;
    }
    
    .small-box .inner h3 {
        font-size: 2.2rem;
        font-weight: bold;
        margin: 0 0 10px 0;
        white-space: nowrap;
        padding: 0;
    }
    
    .small-box .inner p {
        font-size: 1.1rem;
        margin: 0;
    }
    
    .text-truncate {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .btn-group .btn {
        border-radius: 0.25rem;
    }
    
    .btn-group .btn:not(:last-child) {
        margin-right: 2px;
    }
</style>
@endpush

@push('scripts')
<script>
    $(document).ready(function() {
        // Initialize Select2
        $('#user').select2({
            placeholder: 'Select a user...',
            allowClear: true,
            width: '100%'
        });
        
        // Auto-submit form on filter change
        $('#role, #type, #date_from, #date_to').on('change', function() {
            $('#filterForm').submit();
        });
        
        // Search with debounce
        let searchTimeout;
        $('#search').on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                $('#filterForm').submit();
            }, 500);
        });
        
        // Export button
        $('.btn-export').on('click', function(e) {
            e.preventDefault();
            const url = $(this).attr('href');
            window.open(url, '_blank');
        });
    });
</script>
@endpush