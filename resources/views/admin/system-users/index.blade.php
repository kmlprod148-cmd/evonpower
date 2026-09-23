@extends('layouts.app')

@section('title', 'Tous les Opérateurs Système')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Tous les Opérateurs</li>
                    </ol>
                </div>
                <h4 class="page-title">
                    <i class="mdi mdi-account-group me-1"></i>
                    Tous les Opérateurs Système
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="card-title mb-0">
                                Liste des Opérateurs Système
                                @if(auth()->user()->hasRole('integrator'))
                                    (Créés par vous et vos sous-intégrateurs)
                                @elseif(auth()->user()->hasRole('partner'))
                                    (Créés par vous)
                                @elseif(auth()->user()->hasRole('admin'))
                                    (Tous les opérateurs système)
                                @endif
                            </h5>
                        </div>
                        <div class="col-auto">
                            <div class="text-muted">
                                Total: {{ $operators->total() }} opérateur(s)
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    @if($operators->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Nom</th>
                                        <th>Email</th>
                                        <th>Intégrateur</th>
                                        <th>Partenaire</th>
                                        <th>Statut</th>
                                        <th>Créé le</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($operators as $operator)
                                        <tr>
                                            <td>
                                                <span class="badge bg-secondary">#{{ $operator->id }}</span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm me-2">
                                                        <div class="avatar-title bg-primary text-white rounded-circle">
                                                            {{ strtoupper(substr($operator->name, 0, 1)) }}
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0">{{ $operator->name }}</h6>
                                                        <small class="text-muted">{{ $operator->phone ?? 'N/A' }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="text-muted">{{ $operator->email }}</span>
                                            </td>
                                            <td>
                                                @if($operator->integrator)
                                                    <span class="badge bg-info">
                                                        {{ $operator->integrator->name }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($operator->partner)
                                                    <span class="badge bg-success">
                                                        {{ $operator->partner->name }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($operator->is_active)
                                                    <span class="badge bg-success">Actif</span>
                                                @else
                                                    <span class="badge bg-danger">Inactif</span>
                                                @endif
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    {{ $operator->created_at->format('d/m/Y H:i') }}
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    @if(auth()->user()->hasRole('integrator') && $operator->integrator_id === auth()->user()->integrator->id)
                                                        <a href="{{ route('integrator.operators.show', $operator) }}" 
                                                           class="btn btn-sm btn-outline-primary" 
                                                           title="Voir les détails">
                                                            <i class="mdi mdi-eye"></i>
                                                        </a>
                                                        <a href="{{ route('integrator.operators.edit', $operator) }}" 
                                                           class="btn btn-sm btn-outline-warning" 
                                                           title="Modifier">
                                                            <i class="mdi mdi-pencil"></i>
                                                        </a>
                                                    @elseif(auth()->user()->hasRole('partner') && $operator->partner_id === auth()->user()->partner->id)
                                                        <a href="#" 
                                                           class="btn btn-sm btn-outline-primary" 
                                                           title="Voir les détails">
                                                            <i class="mdi mdi-eye"></i>
                                                        </a>
                                                    @elseif(auth()->user()->hasRole('admin'))
                                                        <a href="{{ route('admin.users.show', $operator) }}" 
                                                           class="btn btn-sm btn-outline-primary" 
                                                           title="Voir les détails">
                                                            <i class="mdi mdi-eye"></i>
                                                        </a>
                                                        <a href="{{ route('admin.users.edit', $operator) }}" 
                                                           class="btn btn-sm btn-outline-warning" 
                                                           title="Modifier">
                                                            <i class="mdi mdi-pencil"></i>
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center mt-3">
                            {{ $operators->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="mdi mdi-account-group-outline" style="font-size: 4rem; color: #6c757d;"></i>
                            </div>
                            <h5 class="text-muted">Aucun opérateur système trouvé</h5>
                            <p class="text-muted">
                                @if(auth()->user()->hasRole('integrator'))
                                    Vous n'avez pas encore créé d'opérateurs système.
                                @elseif(auth()->user()->hasRole('partner'))
                                    Vous n'avez pas encore créé d'opérateurs système.
                                @else
                                    Aucun opérateur système n'a été créé dans le système.
                                @endif
                            </p>
                            @if(auth()->user()->hasRole('integrator'))
                                <a href="{{ route('integrator.operators.create') }}" class="btn btn-primary">
                                    <i class="mdi mdi-plus"></i> Créer un Opérateur
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.avatar-sm {
    width: 32px;
    height: 32px;
}

.avatar-title {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    font-weight: 600;
}

.table th {
    border-top: none;
    font-weight: 600;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge {
    font-size: 0.75rem;
}

.btn-group .btn {
    margin-right: 2px;
}

.btn-group .btn:last-child {
    margin-right: 0;
}
</style>
@endpush
