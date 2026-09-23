@extends('layouts.admin')

@section('title', 'Transaction Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-receipt"></i>
                        Transaction Details #{{ $transaction->id }}
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.transactions.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Transactions
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <!-- Transaction Information -->
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-info-circle"></i>
                                        Transaction Information
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="fw-bold">Transaction ID</label>
                                                <p class="form-control-plaintext">{{ $transaction->id }}</p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="fw-bold">Type</label>
                                                <p class="form-control-plaintext">
                                                    <span class="badge {{ $transaction->type === 'credit' ? 'badge-success' : 'badge-danger' }}">
                                                        <i class="fas {{ $transaction->type === 'credit' ? 'fa-arrow-up' : 'fa-arrow-down' }}"></i>
                                                        {{ ucfirst($transaction->type) }}
                                                    </span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="fw-bold">Amount</label>
                                                <p class="form-control-plaintext">
                                                    <span class="h4 {{ $transaction->type === 'credit' ? 'text-success' : 'text-danger' }}">
                                                        {{ $transaction->type === 'credit' ? '+' : '-' }}{{ number_format($transaction->amount, 2) }} €
                                                    </span>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="fw-bold">Current Balance</label>
                                                <p class="form-control-plaintext">
                                                    <span class="h5 text-primary">{{ number_format($transaction->current_balance, 2) }} €</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="fw-bold">Description</label>
                                                <p class="form-control-plaintext">{{ $transaction->description ?? 'N/A' }}</p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="fw-bold">Date</label>
                                                <p class="form-control-plaintext">
                                                    {{ $transaction->created_at->format('M d, Y H:i:s') }}
                                                    <small class="text-muted">({{ $transaction->created_at->diffForHumans() }})</small>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    @if($transaction->metadata)
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="form-group">
                                                    <label class="fw-bold">Metadata</label>
                                                    <pre class="bg-light p-3 rounded">{{ json_encode($transaction->metadata, JSON_PRETTY_PRINT) }}</pre>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Wallet Owner Information -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-wallet"></i>
                                        Wallet Owner
                                    </h5>
                                </div>
                                <div class="card-body">
                                    @if($transaction->wallet && $transaction->wallet->owner)
                                        @php
                                            $owner = $transaction->wallet->owner;
                                            $role = $owner->roles->first();
                                        @endphp
                                        
                                        <div class="text-center mb-3">
                                            <div class="avatar-lg bg-light rounded-circle d-inline-flex align-items-center justify-content-center">
                                                <i class="fas fa-user fa-2x"></i>
                                            </div>
                                        </div>
                                        
                                        <div class="text-center">
                                            <h6 class="fw-bold">{{ $owner->name ?? 'N/A' }}</h6>
                                            <p class="text-muted">{{ $owner->email ?? 'N/A' }}</p>
                                            
                                            @if($role)
                                                <span class="badge badge-info">{{ ucfirst($role->name) }}</span>
                                            @else
                                                <span class="badge badge-secondary">No Role</span>
                                            @endif
                                            
                                            <div class="mt-2">
                                                <small class="text-muted">
                                                    Owner Type: {{ class_basename($transaction->wallet->owner_type) }}
                                                </small>
                                            </div>
                                        </div>
                                    @else
                                        <div class="text-center text-muted">
                                            <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                                            <p>Owner information not available</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Répartition Hiérarchique (si TransactionDetail existe) -->
                    @php
                        // Essayer de charger la transaction comme Transaction model si possible
                        $transactionModel = null;
                        if ($transaction instanceof \App\Models\Transaction) {
                            $transactionModel = $transaction;
                        } elseif (isset($transaction->transaction_id)) {
                            $transactionModel = \App\Models\Transaction::with(['transactionDetail', 'transactionDetail.adminCreator', 'transactionDetail.integratorCreator', 'transactionDetail.operator'])->find($transaction->transaction_id);
                        } elseif (isset($transaction->id)) {
                            $transactionModel = \App\Models\Transaction::with(['transactionDetail', 'transactionDetail.adminCreator', 'transactionDetail.integratorCreator', 'transactionDetail.operator'])->find($transaction->id);
                        }
                    @endphp
                    
                    @if(auth()->user()->role === 'admin' && $transactionModel)
                    {{-- Breakdown Complet avec TOUS les détails pour Admin --}}
                    <div class="row mt-4">
                        <div class="col-12">
                            @include('components.admin-transaction-full-breakdown', ['transaction' => $transactionModel])
                        </div>
                    </div>
                    @elseif($transactionModel && $transactionModel->transactionDetail)
                    {{-- Vue simplifiée pour non-admin --}}
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-gradient-to-r from-blue-600 to-blue-700 text-white">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-chart-pie me-2"></i>
                                        Répartition Hiérarchique des Parts
                                    </h5>
                                </div>
                                <div class="card-body p-4">
                                    @include('components.transaction-parts-breakdown', ['transaction' => $transactionModel])
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Transaction Timeline -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <i class="fas fa-history"></i>
                                        Transaction Timeline
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="timeline">
                                        <div class="time-label">
                                            <span class="bg-primary">{{ $transaction->created_at->format('M d, Y') }}</span>
                                        </div>
                                        
                                        <div>
                                            <i class="fas {{ $transaction->type === 'credit' ? 'fa-plus bg-success' : 'fa-minus bg-danger' }}"></i>
                                            <div class="timeline-item">
                                                <span class="time">
                                                    <i class="fas fa-clock"></i> {{ $transaction->created_at->format('H:i:s') }}
                                                </span>
                                                <h3 class="timeline-header">
                                                    {{ $transaction->type === 'credit' ? 'Credit Transaction' : 'Debit Transaction' }}
                                                </h3>
                                                <div class="timeline-body">
                                                    <p><strong>Amount:</strong> {{ number_format($transaction->amount, 2) }} €</p>
                                                    <p><strong>Description:</strong> {{ $transaction->description ?? 'N/A' }}</p>
                                                    <p><strong>Balance After:</strong> {{ number_format($transaction->current_balance, 2) }} €</p>
                                                    @if($transactionModel && $transactionModel->transactionDetail)
                                                        <hr class="my-2">
                                                        <p class="text-sm text-muted mb-1"><strong>Parts calculées:</strong></p>
                                                        @if($transactionModel->transactionDetail->admin_share_amount > 0)
                                                            <p class="text-sm mb-0">
                                                                <span class="badge bg-danger">Admin: {{ number_format($transactionModel->transactionDetail->admin_share_amount, 2) }} €</span>
                                                            </p>
                                                        @endif
                                                        @if($transactionModel->transactionDetail->integrator_share_amount > 0)
                                                            <p class="text-sm mb-0">
                                                                <span class="badge bg-info">Intégrateur: {{ number_format($transactionModel->transactionDetail->integrator_share_amount, 2) }} €</span>
                                                            </p>
                                                        @endif
                                                        @if($transactionModel->transactionDetail->operator_share_amount > 0)
                                                            <p class="text-sm mb-0">
                                                                <span class="badge bg-success">Opérateur: {{ number_format($transactionModel->transactionDetail->operator_share_amount, 2) }} €</span>
                                                            </p>
                                                        @endif
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .avatar-lg {
        width: 80px;
        height: 80px;
    }
    
    .timeline {
        position: relative;
        padding: 0;
        margin: 0;
    }
    
    .timeline::before {
        content: '';
        position: absolute;
        top: 0;
        bottom: 0;
        width: 4px;
        background: #dee2e6;
        left: 31px;
        margin: 0;
        border-radius: 2px;
    }
    
    .timeline > div {
        position: relative;
        margin-bottom: 15px;
    }
    
    .timeline > div > i {
        position: absolute;
        left: 18px;
        top: 0;
        background: #6c757d;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        text-align: center;
        line-height: 16px;
        font-size: 10px;
        color: #fff;
    }
    
    .timeline > div > .timeline-item {
        margin-left: 60px;
        background: #fff;
        color: #444;
        margin-right: 15px;
        padding: 0;
        position: relative;
        border-radius: 0.25rem;
        border: 1px solid #dee2e6;
    }
    
    .timeline > div > .timeline-item > .time {
        color: #999;
        font-size: 0.875rem;
        padding: 10px;
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }
    
    .timeline > div > .timeline-item > .timeline-header {
        margin: 0;
        color: #555;
        border-bottom: 1px solid #dee2e6;
        padding: 10px;
        font-size: 1rem;
        line-height: 1.42857143;
    }
    
    .timeline > div > .timeline-item > .timeline-body {
        padding: 10px;
    }
    
    .time-label > span {
        font-weight: 600;
        padding: 5px 10px;
        background-color: #6c757d;
        color: #fff;
        border-radius: 4px;
        font-size: 0.875rem;
    }
    
    .time-label {
        position: relative;
        margin-bottom: 15px;
    }
</style>
@endpush