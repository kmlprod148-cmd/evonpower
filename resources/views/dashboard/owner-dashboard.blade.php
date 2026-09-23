@extends('layouts.app')

@section('title', 'Dashboard Proprietaire')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-tachometer-alt text-primary mr-2"></i>
                        Dashboard Proprietaire
                    </h1>
                    <p class="text-muted">Gerez vos points de charge et reservations</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('reservations.pending-approvals') }}" class="btn btn-warning">
                        <i class="fas fa-clock mr-1"></i>
                        Reservations en attente
                        @if($pendingCount > 0)
                            <span class="badge badge-light ml-1">{{ $pendingCount }}</span>
                        @endif
                    </a>
                    @if(\Illuminate\Support\Facades\Route::has('charging-points.create'))
                        <a href="{{ route('charging-points.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus mr-1"></i>
                            Nouveau point de charge
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Points de charge
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['charging_points'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-charging-station fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Reservations actives
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['active_reservations'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Revenus du mois
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['monthly_revenue'], 2) }} EUR</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-euro-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                En attente
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['pending_reservations'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Reservations -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Reservations recentes</h6>
                    <a href="{{ route('reservations.index') }}" class="btn btn-sm btn-primary">Voir tout</a>
                </div>
                <div class="card-body">
                    @if($recentReservations->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Client</th>
                                        <th>Point de charge</th>
                                        <th>Type</th>
                                        <th>Montant</th>
                                        <th>Statut</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentReservations as $reservation)
                                    @php
                                        $statusValue = $reservation->status?->value ?? $reservation->status;
                                        $statusClass = ($reservation->isPaid() && in_array($statusValue, ['pending', 'pending_confirmation'], true))
                                            ? 'success'
                                            : match ($statusValue) {
                                                'pending', 'pending_confirmation' => 'warning',
                                                'confirmed' => 'success',
                                                'active' => 'primary',
                                                'completed' => 'secondary',
                                                'rejected', 'cancelled', 'canceled' => 'danger',
                                                default => 'secondary',
                                            };
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="badge badge-info">#{{ $reservation->id }}</span>
                                        </td>
                                        <td>
                                            @if($reservation->user)
                                                <div>
                                                    <strong>{{ $reservation->user->name }}</strong>
                                                    <br>
                                                    <small class="text-muted">{{ $reservation->user->email }}</small>
                                                </div>
                                            @else
                                                <div>
                                                    <strong>{{ $reservation->guest_email ?? 'Invite' }}</strong>
                                                    <br>
                                                    <small class="text-muted">{{ $reservation->guest_phone ?? 'Telephone non fourni' }}</small>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <strong>{{ $reservation->chargingPoint->name ?? 'N/A' }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $reservation->chargingPoint->location ?? 'N/A' }}</small>
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $reservation->reservation_type === 'kwh' ? 'success' : 'info' }}">
                                                {{ $reservation->reservation_type === 'kwh' ? 'Energie' : 'Duree' }}
                                            </span>
                                            <br>
                                            <small>{{ $reservation->reservation_value }} {{ $reservation->reservation_type === 'kwh' ? 'kWh' : 'min' }}</small>
                                        </td>
                                        <td>
                                            <strong class="text-success">{{ number_format($reservation->estimated_cost, 2) }} EUR</strong>
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $statusClass }}">
                                                {{ $reservation->getDisplayStatusLabel() }}
                                            </span>
                                        </td>
                                        <td>
                                            <div>
                                                <strong>{{ $reservation->created_at->format('d/m/Y') }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $reservation->created_at->format('H:i') }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                @if(in_array($statusValue, ['pending', 'pending_confirmation']) && !$reservation->isPaid())
                                                    <form action="{{ route('reservations.approve-by-owner', $reservation) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-success btn-sm" 
                                                                onclick="return confirm('Approuver cette reservation ?')">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                    <button type="button" class="btn btn-danger btn-sm" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#rejectModal{{ $reservation->id }}">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                @endif
                                                <a href="{{ route('reservations.show', $reservation) }}" 
                                                   class="btn btn-info btn-sm">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-times fa-3x text-gray-300 mb-3"></i>
                            <h5 class="text-muted">Aucune reservation recente</h5>
                            <p class="text-muted">Les nouvelles reservations apparaitront ici.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Quick Actions & Notifications -->
        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Actions rapides</h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="{{ route('reservations.pending-approvals') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-clock text-warning mr-2"></i>
                                Reservations en attente
                            </div>
                            @if($pendingCount > 0)
                                <span class="badge badge-warning badge-pill">{{ $pendingCount }}</span>
                            @endif
                        </a>
                        @if(\Illuminate\Support\Facades\Route::has('charging-points.index'))
                            <a href="{{ route('charging-points.index') }}" class="list-group-item list-group-item-action">
                                <i class="fas fa-charging-station text-primary mr-2"></i>
                                Mes points de charge
                            </a>
                        @endif
                        <a href="{{ route('reservations.index') }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-list text-info mr-2"></i>
                            Toutes les reservations
                        </a>
                        @if(\Illuminate\Support\Facades\Route::has('transactions.index'))
                            <a href="{{ route('transactions.index') }}" class="list-group-item list-group-item-action">
                                <i class="fas fa-euro-sign text-success mr-2"></i>
                                Transactions
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Revenue Chart -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Revenus (7 derniers jours)</h6>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" width="100" height="100"></canvas>
                </div>
            </div>

            <!-- System Status -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Statut du systeme</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Points de charge actifs</span>
                            <span class="badge badge-success">{{ $stats['active_charging_points'] }}/{{ $stats['charging_points'] }}</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Reservations aujourd'hui</span>
                            <span class="badge badge-info">{{ $stats['today_reservations'] }}</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Revenus aujourd'hui</span>
                            <span class="badge badge-success">{{ number_format($stats['today_revenue'], 2) }} EUR</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
@foreach($recentReservations as $reservation)
@php
    $modalStatusValue = $reservation->status?->value ?? $reservation->status;
@endphp
@if(in_array($modalStatusValue, ['pending', 'pending_confirmation']))
<div class="modal fade" id="rejectModal{{ $reservation->id }}" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rejeter la reservation #{{ $reservation->id }}</h5>
                <button type="button" class="close" data-bs-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="{{ route('reservations.reject-by-owner', $reservation) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="rejection_reason">Raison du rejet</label>
                        <textarea class="form-control" 
                                  name="rejection_reason" 
                                  id="rejection_reason" 
                                  rows="3" 
                                  placeholder="Expliquez pourquoi cette reservation est rejetee..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">Rejeter la reservation</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endforeach

@endsection

@push('scripts')
<script src="{{ asset("vendor/chartjs/chart.min.js") }}"></script>
<script>
    // Revenue Chart
    const ctx = document.getElementById('revenueChart').getContext('2d');
    const revenueChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($revenueData['labels']) !!},
            datasets: [{
                label: 'Revenus (EUR)',
                data: {!! json_encode($revenueData['values']) !!},
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Auto-refresh every 30 seconds
    setInterval(function() {
        location.reload();
    }, 30000);
</script>
@endpush
