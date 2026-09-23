@extends('layouts.app')

@section('title', 'Détails de l\'Utilisateur')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Utilisateurs</a></li>
                        <li class="breadcrumb-item active">Détails</li>
                    </ol>
                </div>
                <h4 class="page-title">
                    <i class="mdi mdi-account me-1"></i>
                    Détails de l'Utilisateur
                </h4>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="avatar-lg mx-auto mb-3">
                        <div class="avatar-title bg-primary text-white rounded-circle" style="width: 80px; height: 80px; font-size: 2rem;">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                    </div>
                    <h5 class="mb-1">{{ $user->name }}</h5>
                    <p class="text-muted mb-3">{{ $user->email }}</p>
                    <span class="badge bg-{{ $user->is_active ? 'success' : 'danger' }} mb-3">
                        {{ $user->is_active ? 'Actif' : 'Inactif' }}
                    </span>
                    
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-warning">
                            <i class="mdi mdi-pencil"></i> Modifier
                        </a>
                        <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                            <i class="mdi mdi-delete"></i> Supprimer
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Informations détaillées</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Nom complet</label>
                                <p class="text-muted">{{ $user->name }}</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Email</label>
                                <p class="text-muted">{{ $user->email }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Rôle</label>
                                <p>
                                    <span class="badge bg-{{ $user->role === 'admin' ? 'danger' : ($user->role === 'integrator' ? 'info' : ($user->role === 'partner' ? 'success' : 'warning')) }}">
                                        {{ ucfirst($user->role) }}
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Téléphone</label>
                                <p class="text-muted">{{ $user->phone ?? 'Non renseigné' }}</p>
                            </div>
                        </div>
                    </div>

                    @if(in_array($user->role, ['partner', 'operator']) && $user->integrator)
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Intégrateur responsable</label>
                                <p class="text-muted">{{ $user->integrator->name ?? 'Non assigné' }}</p>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Entreprise</label>
                                <p class="text-muted">{{ $user->company ?? 'Non renseigné' }}</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Poste</label>
                                <p class="text-muted">{{ $user->position ?? 'Non renseigné' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Statut</label>
                                <p>
                                    <span class="badge bg-{{ $user->is_active ? 'success' : 'danger' }}">
                                        {{ $user->is_active ? 'Actif' : 'Inactif' }}
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Dernière connexion</label>
                                <p class="text-muted">{{ $user->last_login ? $user->last_login->format('d/m/Y H:i') : 'Jamais' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Créé le</label>
                                <p class="text-muted">{{ $user->created_at ? $user->created_at->format('d/m/Y H:i') : 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Modifié le</label>
                                <p class="text-muted">{{ $user->updated_at ? $user->updated_at->format('d/m/Y H:i') : 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    @if($user->notes)
                    <div class="mb-3">
                        <label class="form-label fw-bold">Notes</label>
                        <p class="text-muted">{{ $user->notes }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Activité récente -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">Activité récente</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        @if($user->last_login)
                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">Dernière connexion</h6>
                                <p class="timeline-text">Connexion réussie</p>
                                <small class="text-muted">{{ $user->last_login->format('d/m/Y H:i') }}</small>
                            </div>
                        </div>
                        @endif
                        <div class="timeline-item">
                            <div class="timeline-marker bg-info"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">Compte créé</h6>
                                <p class="timeline-text">Le compte a été créé dans le système</p>
                                <small class="text-muted">{{ $user->created_at ? $user->created_at->format('d/m/Y H:i') : 'N/A' }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.avatar-lg {
    width: 80px;
    height: 80px;
}

.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -8px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #e9ecef;
}

.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border-left: 3px solid #dee2e6;
}

.timeline-title {
    margin-bottom: 5px;
    font-size: 0.9rem;
    font-weight: 600;
}

.timeline-text {
    margin-bottom: 5px;
    font-size: 0.85rem;
    color: #6c757d;
}
</style>
@endpush

@push('scripts')
<script>
function confirmDelete() {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.')) {
        // Créer un formulaire pour la suppression
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("admin.users.destroy", $user->id) }}';
        
        // Ajouter le token CSRF
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        
        // Ajouter la méthode DELETE
        const methodField = document.createElement('input');
        methodField.type = 'hidden';
        methodField.name = '_method';
        methodField.value = 'DELETE';
        
        form.appendChild(csrfToken);
        form.appendChild(methodField);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endpush
