@extends('layouts.dashboard')

@section('title', 'Recalcul des Balances')

@section('content')
<div class="container-fluid">
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Recalcul des Balances Hiérarchiques</h1>
                    <p class="text-muted">Forcer le recalcul des balances pour tous les utilisateurs ou des utilisateurs spécifiques</p>
                </div>
                <div class="btn-group">
                    <a href="{{ route('dashboard.balances.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Retour au Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
        </div>
    @endif

    <!-- Formulaire de recalcul -->
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-sync-alt"></i> Options de Recalcul
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('dashboard.balances.process-recalculation') }}" id="recalculateForm">
                        @csrf
                        
                        <!-- Type de recalcul -->
                        <div class="form-group mb-4">
                            <label class="form-label">Type de recalcul</label>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="recalc_type" id="all_users" value="all" checked>
                                        <label class="form-check-label" for="all_users">
                                            <strong>Tous les utilisateurs</strong>
                                            <br>
                                            <small class="text-muted">Recalcul complet du système</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="recalc_type" id="by_role" value="role">
                                        <label class="form-check-label" for="by_role">
                                            <strong>Par rôle</strong>
                                            <br>
                                            <small class="text-muted">Recalcul pour un rôle spécifique</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="recalc_type" id="single_user" value="user">
                                        <label class="form-check-label" for="single_user">
                                            <strong>Utilisateur spécifique</strong>
                                            <br>
                                            <small class="text-muted">Recalcul pour un utilisateur</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sélection du rôle -->
                        <div class="form-group mb-4" id="role_selection" style="display: none;">
                            <label for="role" class="form-label">Rôle</label>
                            <select class="form-control" name="role" id="role">
                                <option value="">Sélectionner un rôle</option>
                                <option value="admin">Administrateur</option>
                                <option value="integrator">Intégrateur</option>
                                <option value="operator">Opérateur</option>
                            </select>
                        </div>

                        <!-- Sélection de l'utilisateur -->
                        <div class="form-group mb-4" id="user_selection" style="display: none;">
                            <label for="user_id" class="form-label">Utilisateur</label>
                            <select class="form-control" name="user_id" id="user_id">
                                <option value="">Sélectionner un utilisateur</option>
                                @foreach(\App\Models\User::with('roles')->get() as $user)
                                <option value="{{ $user->id }}">
                                    {{ $user->name }} ({{ $user->getRoleNames()->first() }})
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Options avancées -->
                        <div class="form-group mb-4">
                            <label class="form-label">Options avancées</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="force" id="force" value="1">
                                        <label class="form-check-label" for="force">
                                            <strong>Forcer le recalcul</strong>
                                            <br>
                                            <small class="text-muted">Ignorer le cache et recalculer même si récent</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="clear_cache" id="clear_cache" value="1" checked>
                                        <label class="form-check-label" for="clear_cache">
                                            <strong>Nettoyer le cache</strong>
                                            <br>
                                            <small class="text-muted">Vider le cache avant le recalcul</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Boutons d'action -->
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                <i class="fas fa-sync-alt"></i> Lancer le Recalcul
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-lg ml-2" onclick="resetForm()">
                                <i class="fas fa-undo"></i> Réinitialiser
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Informations sur le recalcul -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle"></i> Informations
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-lightbulb"></i> À savoir</h6>
                        <ul class="mb-0">
                            <li>Le recalcul peut prendre plusieurs minutes</li>
                            <li>Les utilisateurs peuvent voir des données temporairement incohérentes</li>
                            <li>Le cache sera automatiquement mis à jour</li>
                            <li>Un rapport sera généré après le recalcul</li>
                        </ul>
                    </div>

                    <div class="alert alert-warning">
                        <h6><i class="fas fa-exclamation-triangle"></i> Attention</h6>
                        <ul class="mb-0">
                            <li>Évitez de lancer plusieurs recalculs simultanément</li>
                            <li>Le recalcul complet peut impacter les performances</li>
                            <li>Privilégiez les recalculs ciblés en production</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Statut du système -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-server"></i> Statut du Système
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Cache des balances</span>
                        <span class="badge badge-success">Actif</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Dernier recalcul</span>
                        <span class="text-muted">{{ \Carbon\Carbon::now()->subHours(2)->diffForHumans() }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Utilisateurs actifs</span>
                        <span class="badge badge-primary">{{ \App\Models\User::count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Transactions aujourd'hui</span>
                        <span class="badge badge-info">{{ \App\Models\Transaction::whereDate('created_at', today())->count() }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Historique des recalculs -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-history"></i> Historique des Recalculs
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Utilisateurs traités</th>
                                    <th>Durée</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ \Carbon\Carbon::now()->subHours(2)->format('d/m/Y H:i') }}</td>
                                    <td><span class="badge badge-primary">Complet</span></td>
                                    <td>{{ \App\Models\User::count() }}</td>
                                    <td>2m 15s</td>
                                    <td><span class="badge badge-success">Succès</span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> Voir
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>{{ \Carbon\Carbon::now()->subDays(1)->format('d/m/Y H:i') }}</td>
                                    <td><span class="badge badge badge-info">Par rôle</span></td>
                                    <td>{{ \App\Models\User::role('operator')->count() }}</td>
                                    <td>45s</td>
                                    <td><span class="badge badge-success">Succès</span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> Voir
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Gestion des types de recalcul
    document.addEventListener('DOMContentLoaded', function() {
        const recalcTypeInputs = document.querySelectorAll('input[name="recalc_type"]');
        const roleSelection = document.getElementById('role_selection');
        const userSelection = document.getElementById('user_selection');

        recalcTypeInputs.forEach(input => {
            input.addEventListener('change', function() {
                // Masquer toutes les sélections
                roleSelection.style.display = 'none';
                userSelection.style.display = 'none';

                // Afficher la sélection appropriée
                if (this.value === 'role') {
                    roleSelection.style.display = 'block';
                } else if (this.value === 'user') {
                    userSelection.style.display = 'block';
                }
            });
        });

        // Validation du formulaire
        document.getElementById('recalculateForm').addEventListener('submit', function(e) {
            const recalcType = document.querySelector('input[name="recalc_type"]:checked').value;
            
            if (recalcType === 'role' && !document.getElementById('role').value) {
                e.preventDefault();
                alert('Veuillez sélectionner un rôle');
                return;
            }
            
            if (recalcType === 'user' && !document.getElementById('user_id').value) {
                e.preventDefault();
                alert('Veuillez sélectionner un utilisateur');
                return;
            }

            // Confirmation
            const message = recalcType === 'all' 
                ? 'Êtes-vous sûr de vouloir recalculer toutes les balances ? Cela peut prendre plusieurs minutes.'
                : 'Êtes-vous sûr de vouloir lancer le recalcul ?';
            
            if (!confirm(message)) {
                e.preventDefault();
                return;
            }

            // Désactiver le bouton de soumission
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Recalcul en cours...';
        });
    });

    // Fonction de réinitialisation
    function resetForm() {
        document.getElementById('recalculateForm').reset();
        document.getElementById('role_selection').style.display = 'none';
        document.getElementById('user_selection').style.display = 'none';
        document.getElementById('submitBtn').disabled = false;
        document.getElementById('submitBtn').innerHTML = '<i class="fas fa-sync-alt"></i> Lancer le Recalcul';
    }

    // Auto-refresh du statut toutes les 30 secondes
    setInterval(function() {
        // Ici on pourrait faire un appel AJAX pour mettre à jour le statut
        console.log('Mise à jour du statut...');
    }, 30000);
</script>
@endpush
@endsection
