@extends('layouts.dashboard')

@section('title', __('withdrawals.title'))
@section('page-title', __('withdrawals.title'))

@section('content')
@php
    $displayCurrency = $balance['currency'] ?? ($appCurrency ?? 'EUR');
@endphp
<div class="row">
    <!-- Balance Summary -->
    <div class="col-12 mb-4">
        <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <h6 class="text-white-50 mb-1">{{ __('wallet.available_to_withdraw') }}</h6>
                        <h2 class="text-white mb-0" style="font-size: 2.5rem;">
                            {{ number_format($balance['available'] ?? 0, 2) }} 
                            <small>{{ $displayCurrency }}</small>
                        </h2>
                    </div>
                    <div class="col-md-4">
                        <h6 class="text-white-50 mb-1">{{ __('wallet.pending_withdrawals') }}</h6>
                        <h4 class="text-white mb-0">
                            {{ number_format($pendingAmount ?? 0, 2) }} 
                            <small>{{ $displayCurrency }}</small>
                        </h4>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#withdrawalModal">
                            <i class="fas fa-plus me-2"></i>
                            {{ __('withdrawals.new_request') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="col-12 mb-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form method="GET" action="{{ route('partner.withdrawals.index') }}" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">{{ __('filters.status') }}</label>
                        <select name="status" class="form-select">
                            <option value="">{{ __('filters.all') }}</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>{{ __('withdrawals.status.pending') }}</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>{{ __('withdrawals.status.approved') }}</option>
                            <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>{{ __('withdrawals.status.paid') }}</option>
                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>{{ __('withdrawals.status.rejected') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('filters.date_from') }}</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('filters.date_to') }}</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="d-flex gap-2 w-100">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="fas fa-search me-1"></i> {{ __('actions.search') }}
                            </button>
                            <a href="{{ route('partner.withdrawals.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-redo"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Withdrawal Requests Table -->
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">{{ __('withdrawals.requests') }}</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>{{ __('fields.id') }}</th>
                                <th>{{ __('fields.date') }}</th>
                                <th>{{ __('fields.amount') }}</th>
                                <th>{{ __('fields.status') }}</th>
                                <th>{{ __('fields.external_id') }}</th>
                                <th>{{ __('fields.note') }}</th>
                                <th>{{ __('fields.processed_at') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($withdrawals as $withdrawal)
                                <tr>
                                    <td>#{{ $withdrawal->id }}</td>
                                    <td>{{ $withdrawal->requested_at ? $withdrawal->requested_at->format('d/m/Y H:i') : '-' }}</td>
                                    <td class="fw-bold">{{ number_format($withdrawal->amount, 2) }} {{ $withdrawal->currency ?? $displayCurrency }}</td>
                                    <td>
                                        @switch($withdrawal->status)
                                            @case('pending')
                                                <span class="badge bg-warning">{{ __('withdrawals.status.pending') }}</span>
                                                @break
                                            @case('approved')
                                                <span class="badge bg-info">{{ __('withdrawals.status.approved') }}</span>
                                                @break
                                            @case('paid')
                                                <span class="badge bg-success">{{ __('withdrawals.status.paid') }}</span>
                                                @break
                                            @case('rejected')
                                                <span class="badge bg-danger">{{ __('withdrawals.status.rejected') }}</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">{{ $withdrawal->status }}</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        @if($withdrawal->external_transaction_id)
                                            <span class="font-monospace">{{ $withdrawal->external_transaction_id }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($withdrawal->collector_note)
                                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="popover" data-bs-content="{{ $withdrawal->collector_note }}">
                                                <i class="fas fa-comment"></i>
                                            </button>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($withdrawal->processed_at)
                                            {{ $withdrawal->processed_at->format('d/m/Y H:i') }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <i class="fas fa-wallet fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">{{ __('withdrawals.no_requests') }}</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($withdrawals->hasPages())
                <div class="card-footer bg-white">
                    {{ $withdrawals->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
