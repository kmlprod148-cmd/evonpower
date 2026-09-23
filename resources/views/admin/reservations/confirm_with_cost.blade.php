@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Confirm Reservation with Cost Adjustment</h1>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            Reservation Details #{{ $reservation->id }}
        </div>
        <div class="card-body">
            <p><strong>User:</strong> {{ $reservation->user->name ?? 'Guest' }}</p>
            <p><strong>Charging Point:</strong> {{ $reservation->chargingPoint->name ?? 'N/A' }}</p>
            <p><strong>Start Time:</strong> {{ $reservation->start_time->format('Y-m-d H:i') }}</p>
            <p><strong>End Time:</strong> {{ $reservation->end_time->format('Y-m-d H:i') }}</p>
            <p><strong>Current Total Facturé (TTC):</strong> {{ number_format($reservation->estimated_cost, 2) }} {{ $reservation->pricingPlan->currency ?? 'EUR' }}</p>
            @if ($suggestedCost !== null)
                <p><strong>Suggested Recalculated Cost:</strong> {{ number_format($suggestedCost, 2) }} {{ $reservation->pricingPlan->currency ?? 'EUR' }}</p>
            @else
                <p class="text-muted">No suggested recalculation available (missing actual energy or pricing plan details).</p>
            @endif
            <p><strong>Status:</strong> <span class="badge bg-{{ $reservation->status->color() }}">{{ $reservation->getDisplayStatusLabel() }}</span></p>

            <hr>

            <form action="{{ route('admin.reservations.confirm-with-cost.update', $reservation) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="estimated_cost" class="form-label">New Total Facturé (TTC)</label>
                    <input type="number" step="0.01" class="form-control @error('estimated_cost') is-invalid @enderror" id="estimated_cost" name="estimated_cost" value="{{ old('estimated_cost', $suggestedCost ?? $reservation->estimated_cost) }}" required>
                    @error('estimated_cost')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">Confirm with New Cost</button>
                <a href="{{ route('admin.reservations.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection