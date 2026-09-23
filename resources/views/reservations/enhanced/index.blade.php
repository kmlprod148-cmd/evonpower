@extends('layouts.app')

@section('title', 'Réservations - Frais Détaillés')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header Section -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-calendar-check me-2"></i>
                        Réservations avec Frais Détaillés
                    </h1>
                    <p class="text-muted mb-0">Gestion complète des réservations et frais appliqués</p>
                </div>
                <div>
                    <a href="{{ route('reservations.enhanced.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>
                        Nouvelle Réservation
                    </a>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('reservations.enhanced.index') }}" class="row g-3">
                        <div class="col-md-3">
                            <label for="status" class="form-label">Statut</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">Tous les statuts</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>En attente</option>
                                <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmée</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Terminée</option>
                                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Annulée</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="charging_point_id" class="form-label">Point de Charge</label>
                            <select name="charging_point_id" id="charging_point_id" class="form-select">
                                <option value="">Tous les points</option>
                                @foreach(\App\Models\ChargingPoint::with('station')->get() as $cp)
                                    <option value="{{ $cp->id }}" {{ request('charging_point_id') == $cp->id ? 'selected' : '' }}>
                                        {{ $cp->name }} - {{ $cp->station->name ?? 'N/A' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="date_from" class="form-label">Date début</label>
                            <input type="date" name="date_from" id="date_from" class="form-control" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-2">
                            <label for="date_to" class="form-label">Date fin</label>
                            <input type="date" name="date_to" id="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                                <a href="{{ route('reservations.enhanced.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Reservations Table -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>
                        Liste des Réservations ({{ $reservations->total() }})
                    </h5>
                </div>
                <div class="card-body p-0">
                    @if($reservations->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Utilisateur</th>
                                        <th>Point de Charge</th>
                                        <th>Type</th>
                                        <th>Valeur</th>
                                        <th>Coût Estimé</th>
                                        <th>Frais Totaux</th>
                                        <th>Montant Net</th>
                                        <th>Statut</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reservations as $reservation)
                                        <tr>
                                            <td>
                                                <span class="badge bg-secondary">#{{ $reservation->id }}</span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                                        {{ substr($reservation->user->name ?? 'G', 0, 1) }}
                                                    </div>
                                                    <div>
                                                        <div class="fw-semibold">{{ $reservation->user->name ?? 'Invité' }}</div>
                                                        <small class="text-muted">{{ $reservation->user->email ?? $reservation->guest_email }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <div class="fw-semibold">{{ $reservation->chargingPoint->name ?? 'N/A' }}</div>
                                                    <small class="text-muted">{{ $reservation->chargingPoint->station->name ?? 'N/A' }}</small>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    {{ $reservation->reservation_type === 'kwh' ? 'kWh' : 'Minutes' }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="fw-semibold">{{ number_format($reservation->reservation_value, 2) }}</span>
                                            </td>
                                            <td>
                                                <span class="fw-semibold text-primary">
                                                    {{ number_format($reservation->estimated_cost, 2) }}€
                                                </span>
                                            </td>
                                            <td>
                                                <span class="fw-semibold text-warning">
                                                    {{ number_format($reservation->fees_breakdown['total_fees'] ?? 0, 2) }}€
                                                </span>
                                            </td>
                                            <td>
                                                <span class="fw-semibold text-success">
                                                    {{ number_format($reservation->fees_breakdown['net_amount'] ?? 0, 2) }}€
                                                </span>
                                            </td>
                                            <td>
                                                @php
                                                    $statusColors = [
                                                        'pending' => 'warning',
                                                        'confirmed' => 'info',
                                                        'active' => 'success',
                                                        'completed' => 'primary',
                                                        'cancelled' => 'danger'
                                                    ];
                                                    $color = $statusColors[$reservation->status->value] ?? 'secondary';
                                                @endphp
                                                <span class="badge bg-{{ $color }}">
                                                    {{ ucfirst($reservation->status->value) }}
                                                </span>
                                            </td>
                                            <td>
                                                <div>
                                                    <div class="fw-semibold">{{ $reservation->created_at->format('d/m/Y') }}</div>
                                                    <small class="text-muted">{{ $reservation->created_at->format('H:i') }}</small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('reservations.enhanced.show', $reservation) }}" 
                                                       class="btn btn-sm btn-outline-primary" title="Voir les détails">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    @can('update', $reservation)
                                                        <a href="{{ route('reservations.enhanced.edit', $reservation) }}" 
                                                           class="btn btn-sm btn-outline-warning" title="Modifier">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    @endcan
                                                    @can('delete', $reservation)
                                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                onclick="deleteReservation({{ $reservation->id }})" title="Supprimer">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="card-footer bg-white">
                            {{ $reservations->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Aucune réservation trouvée</h5>
                            <p class="text-muted">
                                @if(auth()->user()->hasRole('admin'))
                                    Aucune réservation n'existe dans le système. Créez des données de test ou une nouvelle réservation.
                                @else
                                    Vous n'avez pas encore de réservations. Créez votre première réservation.
                                @endif
                            </p>
                            <div class="d-flex gap-2 justify-content-center">
                                @if(auth()->user()->hasRole('admin'))
                                    <button type="button" class="btn btn-warning" onclick="createSampleData()">
                                        <i class="fas fa-database me-1"></i>
                                        Créer des Données de Test
                                    </button>
                                @endif
                                <a href="{{ route('reservations.enhanced.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i>
                                    Créer une Réservation
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer cette réservation ?</p>
                <p class="text-danger"><strong>Cette action est irréversible.</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function deleteReservation(reservationId) {
    const form = document.getElementById('deleteForm');
    form.action = `/reservations/enhanced/${reservationId}`;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

function createSampleData() {
    if (confirm('Voulez-vous créer des données de test ? Cela ajoutera des réservations d\'exemple au système.')) {
        // Afficher un indicateur de chargement
        const button = event.target;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Création en cours...';
        button.disabled = true;
        
        // Faire l'appel AJAX
        fetch('{{ route("reservations.enhanced.create-sample-data") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Afficher un message de succès
                button.innerHTML = '<i class="fas fa-check me-1"></i>Créé avec succès !';
                button.classList.remove('btn-warning');
                button.classList.add('btn-success');
                
                // Recharger la page après 1 seconde
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                // Afficher un message d'erreur
                button.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Erreur';
                button.classList.remove('btn-warning');
                button.classList.add('btn-danger');
                
                // Restaurer le bouton après 3 secondes
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.classList.remove('btn-danger');
                    button.classList.add('btn-warning');
                    button.disabled = false;
                }, 3000);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            button.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Erreur';
            button.classList.remove('btn-warning');
            button.classList.add('btn-danger');
            
            // Restaurer le bouton après 3 secondes
            setTimeout(() => {
                button.innerHTML = originalText;
                button.classList.remove('btn-danger');
                button.classList.add('btn-warning');
                button.disabled = false;
            }, 3000);
        });
    }
}

// Auto-submit filters on change
document.addEventListener('DOMContentLoaded', function() {
    const filterInputs = document.querySelectorAll('select[name="status"], select[name="charging_point_id"]');
    filterInputs.forEach(input => {
        input.addEventListener('change', function() {
            this.form.submit();
        });
    });
    
    // Afficher un message si l'admin n'a pas de réservations
    @if(auth()->user()->hasRole('admin') && $reservations->count() == 0)
        console.log('Admin: Aucune réservation trouvée. Des données de test peuvent être créées.');
    @endif
});
</script>
@endpush

@push('styles')
<style>
.avatar-sm {
    width: 32px;
    height: 32px;
    font-size: 14px;
}

.table th {
    border-top: none;
    font-weight: 600;
    color: #6c757d;
    font-size: 0.875rem;
}

.table td {
    vertical-align: middle;
    font-size: 0.875rem;
}

.btn-group .btn {
    border-radius: 0.375rem;
    margin-right: 2px;
}

.btn-group .btn:last-child {
    margin-right: 0;
}

.card {
    border: 1px solid #e3e6f0;
    border-radius: 0.35rem;
}

.card-header {
    border-bottom: 1px solid #e3e6f0;
    background-color: #f8f9fc !important;
}
</style>
@endpush
