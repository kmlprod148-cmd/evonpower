@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Account Details</h1>
    <div class="card">
        <div class="card-header">
            Account ID: {{ $account->id }}
        </div>
        <div class="card-body">
            <p><strong>Accountable Type:</strong> {{ $account->accountable_type }}</p>
            <p><strong>Accountable ID:</strong> {{ $account->accountable_id }}</p>
            <p><strong>Balance:</strong> {{ $account->balance }} {{ $account->currency }}</p>
            <p><strong>Created At:</strong> {{ $account->created_at }}</p>
            <p><strong>Updated At:</strong> {{ $account->updated_at }}</p>
        </div>
    </div>

    <h2 class="mt-4">Financial Transactions (Payer)</h2>
    @if ($account->sentTransactions->count() > 0)
    <table class="table mt-2">
        <thead>
            <tr>
                <th>ID</th>
                <th>Payee</th>
                <th>Amount</th>
                <th>Type</th>
                <th>Description</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($account->sentTransactions as $transaction)
            <tr>
                <td>{{ $transaction->id }}</td>
                <td>{{ $transaction->payeeAccount->accountable->name ?? 'N/A' }}</td>
                <td>-{{ $transaction->amount }} {{ $account->currency }}</td>
                <td>{{ $transaction->type }}</td>
                <td>{{ $transaction->description }}</td>
                <td>{{ $transaction->created_at->format('Y-m-d H:i') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p>No outgoing financial transactions for this account.</p>
    @endif

    <h2 class="mt-4">Financial Transactions (Payee)</h2>
    @if ($account->receivedTransactions->count() > 0)
    <table class="table mt-2">
        <thead>
            <tr>
                <th>ID</th>
                <th>Payer</th>
                <th>Amount</th>
                <th>Type</th>
                <th>Description</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($account->receivedTransactions as $transaction)
            <tr>
                <td>{{ $transaction->id }}</td>
                <td>{{ $transaction->payerAccount->accountable->name ?? 'N/A' }}</td>
                <td>+{{ $transaction->amount }} {{ $account->currency }}</td>
                <td>{{ $transaction->type }}</td>
                <td>{{ $transaction->description }}</td>
                <td>{{ $transaction->created_at->format('Y-m-d H:i') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p>No incoming financial transactions for this account.</p>
    @endif

    <a href="{{ route('accounts.index') }}" class="btn btn-primary mt-4">Back to Accounts</a>
</div>
@endsection