@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Accounts</h1>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Accountable Type</th>
                <th>Accountable ID</th>
                <th>Balance</th>
                <th>Currency</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($accounts as $account)
            <tr>
                <td>{{ $account->id }}</td>
                <td>{{ $account->accountable_type }}</td>
                <td>{{ $account->accountable_id }}</td>
                <td>{{ $account->balance }}</td>
                <td>{{ $account->currency }}</td>
                <td>
                    <a href="{{ route('accounts.show', $account->id) }}" class="btn btn-info btn-sm">View</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection