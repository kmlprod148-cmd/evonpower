@extends('layouts.app')

@section('title', 'Contrats Intégrateurs')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-file-contract me-2"></i>
            Contrats Intégrateurs
        </h1>
        <a href="{{ route('admin.integrator-contracts.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i>
            Nouveau contrat
        </a>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filtres</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.integrator-contracts.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Recherche</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="N° contrat ou nom..." 
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <label for="integrator_id" class="form-label">Intégrateur</label>
                    <select class="form-select" id="integrator_id" name="integrator_id">
                        <option value="">Tous les intégrateurs</option>
                        @foreach($integrators as $integrator)
                            <option value="{{ $integrator->id }}" 
                                    {{ request('integrator_id') == $integrator->id ? 'selected' : '' }}>
                                {{ $integrator->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label">Statut</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Tous</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Brouillon</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Actif</option>
                        <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspendu</option>
                        <option value="terminated" {{ request('status') == 'terminated' ? 'selected' : '' }}>Terminé</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="maintenance_fee" class="form-label">Frais maintenance</label>
                    <select class="form-select" id="maintenance_fee" name="maintenance_fee">
                        <option value="">Tous</option>
                        <option value="1" {{ request('maintenance_fee') == '1' ? 'selected' : '' }}>Activés</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i>
                        Filtrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Contracts Table -->
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="contractsTable">
                    <thead>
                        <tr>
                            <th>N° Contrat</th>
                            <th>Intégrateur</th>
                            <th>Nom</th>
                            <th>Statut</th>
                            <th>Maintenance</th>
                            <th>Borne active</th>
                            <th>Commission</th>
                            <th>Date création</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($contracts as $contract)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.integrator-contracts.show', $contract) }}">
                                        {{ $contract->contract_number }}
                                    </a>
                                </td>
                                <td>
                                    @if($contract->integrator)
                                        <a href="{{ route('integrators.show', $contract->integrator) }}">
                                            {{ $contract->integrator->name }}
                                        </a>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>{{ $contract->name }}</td>
                                <td>
                                    @switch($contract->status)
                                        @case('active')
                                            <span class="badge bg-success">Actif</span>
                                            @break
                                        @case('suspended')
                                            <span class="badge bg-warning">Suspendu</span>
                                            @break
                                        @case('terminated')
                                            <span class="badge bg-danger">Terminé</span>
                                            @break
                                        @default
                                            <span class="badge bg-secondary">Brouillon</span>
                                    @endswitch
                                </td>
                                <td>
                                    @if($contract->maintenance_fee_enabled)
                                        <span class="badge bg-info">
                                            {{ number_format($contract->maintenance_fee_amount, 2) }} {{ $contract->currency }}
                                            <small>/{{ $contract->maintenance_fee_period }}</small>
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($contract->terminal_fee_enabled)
                                        <span class="badge bg-info">
                                            {{ number_format($contract->terminal_fee_amount, 2) }} {{ $contract->currency }}
                                            <small>/borne</small>
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($contract->transaction_commission_enabled)
                                        @switch($contract->transaction_commission_type)
                                            @case('percentage')
                                                <span class="badge bg-primary">{{ $contract->transaction_commission_percentage }}%</span>
                                                @break
                                            @case('fixed')
                                                <span class="badge bg-primary">{{ number_format($contract->transaction_commission_fixed_amount, 2) }} {{ $contract->currency }}</span>
                                                @break
                                            @case('combined')
                                                <span class="badge bg-primary">{{ $contract->transaction_commission_percentage }}% + {{ number_format($contract->transaction_commission_fixed_amount, 2) }}</span>
                                                @break
                                        @endswitch
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $contract->created_at->format('d/m/Y') }}</td>
                                <td class="text-end">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.integrator-contracts.show', $contract) }}" 
                                           class="btn btn-sm btn-primary" title="Voir">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.integrator-contracts.edit', $contract) }}" 
                                           class="btn btn-sm btn-warning" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @if($contract->invoices()->count() == 0)
                                            <form action="{{ route('admin.integrator-contracts.destroy', $contract) }}" 
                                                  method="POST" class="d-inline"
                                                  onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce contrat?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-inbox fa-2x mb-2"></i>
                                        <p>Aucun contrat trouvé</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-4">
                {{ $contracts->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
