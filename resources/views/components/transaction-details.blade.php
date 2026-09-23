<div class="transaction-details">
    <div class="row">
        <div class="col-md-6">
            <h5 class="text-primary">
                <i class="fas fa-receipt"></i>
                Détails de la Transaction #{{ $transaction->id }}
            </h5>
        </div>
        <div class="col-md-6 text-right">
            <span class="badge badge-{{ $transaction->status == 'completed' ? 'success' : ($transaction->status == 'pending' ? 'warning' : 'danger') }} badge-lg">
                {{ ucfirst($transaction->status) }}
            </span>
        </div>
    </div>

    <hr>

    <div class="row">
        <div class="col-md-6">
            <h6 class="text-muted">Informations Générales</h6>
            <table class="table table-sm">
                <tr>
                    <td><strong>ID:</strong></td>
                    <td>#{{ $transaction->id }}</td>
                </tr>
                <tr>
                    <td><strong>Date:</strong></td>
                    <td>{{ $transaction->created_at->format('d/m/Y H:i:s') }}</td>
                </tr>
                <tr>
                    <td><strong>Type:</strong></td>
                    <td>
                        <span class="badge badge-{{ $transaction->type == 'commission' ? 'success' : ($transaction->type == 'payment' ? 'warning' : 'info') }}">
                            {{ ucfirst($transaction->type) }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td><strong>Montant:</strong></td>
                    <td>
                        @if($transaction->amount > 0)
                            <span class="text-success font-weight-bold">
                                <i class="fas fa-arrow-up"></i>
                                +{{ number_format($transaction->amount, 2) }} {{ $transaction->currency }}
                            </span>
                        @else
                            <span class="text-danger font-weight-bold">
                                <i class="fas fa-arrow-down"></i>
                                {{ number_format($transaction->amount, 2) }} {{ $transaction->currency }}
                            </span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td><strong>Référence:</strong></td>
                    <td>{{ $transaction->reference ?? 'N/A' }}</td>
                </tr>
            </table>
        </div>

        <div class="col-md-6">
            <h6 class="text-muted">Utilisateur</h6>
            <table class="table table-sm">
                <tr>
                    <td><strong>Nom:</strong></td>
                    <td>{{ $transaction->user->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td><strong>Email:</strong></td>
                    <td>{{ $transaction->user->email ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td><strong>Rôle:</strong></td>
                    <td>
                        @if($transaction->user)
                            @foreach($transaction->user->getRoleNames() as $role)
                                <span class="badge badge-info">{{ ucfirst($role) }}</span>
                            @endforeach
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    </div>

    @if($transaction->chargingPoint)
    <div class="row mt-3">
        <div class="col-12">
            <h6 class="text-muted">Point de Charge</h6>
            <table class="table table-sm">
                <tr>
                    <td><strong>Nom:</strong></td>
                    <td>{{ $transaction->chargingPoint->name }}</td>
                </tr>
                <tr>
                    <td><strong>Numéro de série:</strong></td>
                    <td>{{ $transaction->chargingPoint->serial_number }}</td>
                </tr>
                <tr>
                    <td><strong>Puissance:</strong></td>
                    <td>{{ $transaction->chargingPoint->power_output }} kW</td>
                </tr>
                <tr>
                    <td><strong>Localisation:</strong></td>
                    <td>{{ $transaction->chargingPoint->city }}, {{ $transaction->chargingPoint->country }}</td>
                </tr>
            </table>
        </div>
    </div>
    @endif

    @if($transaction->reservation)
    <div class="row mt-3">
        <div class="col-12">
            <h6 class="text-muted">Réservation Associée</h6>
            <table class="table table-sm">
                <tr>
                    <td><strong>ID Réservation:</strong></td>
                    <td>#{{ $transaction->reservation->id }}</td>
                </tr>
                <tr>
                    <td><strong>Début:</strong></td>
                    <td>{{ $transaction->reservation->start_time ? $transaction->reservation->start_time->format('d/m/Y H:i') : 'N/A' }}</td>
                </tr>
                <tr>
                    <td><strong>Fin:</strong></td>
                    <td>{{ $transaction->reservation->end_time ? $transaction->reservation->end_time->format('d/m/Y H:i') : 'N/A' }}</td>
                </tr>
                <tr>
                    <td><strong>Énergie:</strong></td>
                    <td>{{ $transaction->reservation->energy_kwh ?? $transaction->reservation->estimated_energy ?? 'N/A' }} kWh</td>
                </tr>
                <tr>
                    <td><strong>Coût estimé:</strong></td>
                    <td>{{ $transaction->reservation->estimated_cost ?? 'N/A' }} EUR</td>
                </tr>
            </table>
        </div>
    </div>
    @endif

    @if($transaction->description)
    <div class="row mt-3">
        <div class="col-12">
            <h6 class="text-muted">Description</h6>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                {{ $transaction->description }}
            </div>
        </div>
    </div>
    @endif

    @if($transaction->metadata)
    <div class="row mt-3">
        <div class="col-12">
            <h6 class="text-muted">Métadonnées</h6>
            <div class="card">
                <div class="card-body">
                    <pre class="mb-0"><code>{{ json_encode(json_decode($transaction->metadata), JSON_PRETTY_PRINT) }}</code></pre>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-light">
                <small class="text-muted">
                    <i class="fas fa-clock"></i>
                    Dernière mise à jour: {{ $transaction->updated_at->format('d/m/Y H:i:s') }}
                </small>
            </div>
        </div>
    </div>
</div>

<style>
.transaction-details .badge-lg {
    font-size: 0.9rem;
    padding: 0.5rem 0.75rem;
}

.transaction-details .table td {
    border-top: none;
    padding: 0.25rem 0.5rem;
}

.transaction-details .table td:first-child {
    width: 30%;
    font-weight: 600;
}

.transaction-details pre {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    padding: 1rem;
    font-size: 0.875rem;
}
</style>
