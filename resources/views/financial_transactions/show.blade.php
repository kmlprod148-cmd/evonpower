@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Financial Transaction Details</h4>
                    <span class="badge bg-{{ $financialTransaction->status === 'completed' ? 'success' : 'warning' }}">{{ ucfirst($financialTransaction->status) }}</span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Transaction ID:</strong> {{ $financialTransaction->id }}</p>
                            <p><strong>Charging Session Transaction ID:</strong> {{ $financialTransaction->transaction_id ?? 'N/A' }}</p>
                            <p><strong>Revenue Share ID:</strong> {{ $financialTransaction->revenue_share_id ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Amount:</strong> {{ number_format($financialTransaction->amount, 2) }} {{ $financialTransaction->payerAccount->currency ?? 'EUR' }}</p>
                            <p><strong>Type:</strong> {{ ucfirst(str_replace('_', ' ', $financialTransaction->type)) }}</p>
                            <p><strong>Description:</strong> {{ $financialTransaction->description ?? 'No description provided.' }}</p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Payer</h5>
                            <p><a href="{{ route('accounts.show', $financialTransaction->payer_account_id) }}">{{ $financialTransaction->payerAccount->accountable->name ?? 'N/A' }}</a></p>
                            <p>Account ID: {{ $financialTransaction->payer_account_id }}</p>
                        </div>
                        <div class="col-md-6">
                            <h5>Payee</h5>
                            <p><a href="{{ route('accounts.show', a$financialTransaction->payee_account_id) }}">{{ $financialTransaction->payeeAccount->accountable->name ?? 'N/A' }}</a></p>
                            <p>Account ID: {{ $financialTransaction->payee_account_id }}</p>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-muted">
                    <div class="d-flex justify-content-between">
                        <span><strong>Created:</strong> {{ $financialTransaction->created_at->format('F j, Y, g:i a') }}</span>
                        <span><strong>Last Updated:</strong> {{ $financialTransaction->updated_at->format('F j, Y, g:i a') }}</span>
                    </div>
                </div>
            </div>
            <a href="{{ url()->previous() }}" class="btn btn-primary mt-4">Back</a>
        </div>
    </div>
</div>
@endsection