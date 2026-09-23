@extends('layouts.app')

@section('title', 'Modifier l\'Utilisateur')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Utilisateurs</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.users.show', $id) }}">Détails</a></li>
                        <li class="breadcrumb-item active">Modifier</li>
                    </ol>
                </div>
                <h4 class="page-title">
                    <i class="mdi mdi-account-edit me-1"></i>
                    Modifier l'Utilisateur
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Modifier les informations</h5>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('admin.users.update', $id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nom complet <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Nouveau mot de passe</label>
                                    <input type="password" class="form-control" id="password" name="password">
                                    <small class="form-text text-muted">Laissez vide pour conserver le mot de passe actuel</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password_confirmation" class="form-label">Confirmer le nouveau mot de passe</label>
                                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="role" class="form-label">Rôle <span class="text-danger">*</span></label>
                                    <select class="form-select" id="role" name="role" required>
                                        <option value="">Sélectionner un rôle</option>
                                        <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>Administrateur</option>
                                        <option value="integrator" {{ old('role', $user->role) == 'integrator' ? 'selected' : '' }}>Intégrateur</option>
                                        <option value="partner" {{ old('role', $user->role) == 'partner' ? 'selected' : '' }}>Partenaire</option>
                                        <option value="operator" {{ old('role', $user->role) == 'operator' ? 'selected' : '' }}>Opérateur</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Téléphone</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" value="{{ old('phone', $user->phone) }}">
                                </div>
                            </div>
                        </div>

                        <!-- Champ Intégrateur responsable (pour partenaires et opérateurs) -->
                        <div class="row" id="integrator-field" style="{{ in_array(old('role', $user->role), ['partner', 'operator']) ? '' : 'display: none;' }}">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="integrator_id" class="form-label">Intégrateur responsable <span class="text-danger">*</span></label>
                                    <select class="form-select" id="integrator_id" name="integrator_id">
                                        <option value="">Sélectionner un intégrateur</option>
                                        @foreach($integrators as $integrator)
                                            <option value="{{ $integrator->id }}" {{ old('integrator_id', $user->integrator_id) == $integrator->id ? 'selected' : '' }}>
                                                {{ $integrator->name }} ({{ $integrator->user->name ?? 'N/A' }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Sélectionnez l'intégrateur qui sera responsable de cet utilisateur.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="company" class="form-label">Entreprise</label>
                                    <input type="text" class="form-control" id="company" name="company" value="{{ old('company', $user->company) }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="position" class="form-label">Poste</label>
                                    <input type="text" class="form-control" id="position" name="position" value="{{ old('position', $user->position) }}">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Utilisateur actif
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes', $user->notes) }}</textarea>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.users.show', $id) }}" class="btn btn-secondary">
                                <i class="mdi mdi-arrow-left"></i> Annuler
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-check"></i> Mettre à jour
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validation du mot de passe
    const password = document.getElementById('password');
    const passwordConfirmation = document.getElementById('password_confirmation');
    
    function validatePassword() {
        if (password.value && passwordConfirmation.value && password.value !== passwordConfirmation.value) {
            passwordConfirmation.setCustomValidity('Les mots de passe ne correspondent pas');
        } else {
            passwordConfirmation.setCustomValidity('');
        }
    }
    
    password.addEventListener('change', validatePassword);
    passwordConfirmation.addEventListener('keyup', validatePassword);
    
    // Validation conditionnelle du mot de passe
    function validatePasswordRequired() {
        if (password.value || passwordConfirmation.value) {
            passwordConfirmation.required = true;
            password.required = true;
        } else {
            passwordConfirmation.required = false;
            password.required = false;
        }
    }
    
    password.addEventListener('input', validatePasswordRequired);
    passwordConfirmation.addEventListener('input', validatePasswordRequired);
    
    // Gestion du champ intégrateur selon le rôle
    const roleSelect = document.getElementById('role');
    const integratorField = document.getElementById('integrator-field');
    const integratorSelect = document.getElementById('integrator_id');
    
    function toggleIntegratorField() {
        const selectedRole = roleSelect.value;
        if (selectedRole === 'partner' || selectedRole === 'operator') {
            integratorField.style.display = 'block';
            integratorSelect.required = true;
        } else {
            integratorField.style.display = 'none';
            integratorSelect.required = false;
        }
    }
    
    roleSelect.addEventListener('change', toggleIntegratorField);
    
    // Initialiser l'état au chargement
    toggleIntegratorField();
});
</script>
@endpush
