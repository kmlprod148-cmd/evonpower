@extends('layouts.app')

@section('title', 'Paiement Échoué')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg border-danger">
                <div class="card-header bg-danger text-white text-center">
                    <h4 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Paiement Échoué
                    </h4>
                </div>
                <div class="card-body text-center">
                    <div class="error-icon mb-4">
                        <i class="fas fa-times-circle fa-5x text-danger"></i>
                    </div>
                    
                    <h5 class="text-danger mb-3">{{ $error }}</h5>
                    
                    <div class="alert alert-warning">
                        <h6 class="alert-heading">
                            <i class="fas fa-info-circle me-2"></i>
                            Que faire maintenant ?
                        </h6>
                        <ul class="list-unstyled mb-0">
                            <li><i class="fas fa-redo me-2"></i> Vérifiez vos informations de paiement</li>
                            <li><i class="fas fa-credit-card me-2"></i> Essayez avec une autre carte</li>
                            <li><i class="fas fa-phone me-2"></i> Contactez votre banque si nécessaire</li>
                        </ul>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <a href="javascript:history.back()" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-arrow-left me-2"></i>
                                Réessayer
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="{{ route('dashboard') }}" class="btn btn-outline-primary btn-lg w-100">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Tableau de Bord
                            </a>
                        </div>
                    </div>

                    <div class="mt-4">
                        <a href="{{ route('reservations.create') }}" class="btn btn-success">
                            <i class="fas fa-plus me-2"></i>
                            Nouvelle Réservation
                        </a>
                    </div>
                </div>
            </div>

            <!-- Aide et support -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-question-circle me-2"></i>
                        Besoin d'Aide ?
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <i class="fas fa-phone fa-2x text-primary mb-2"></i>
                            <h6>Support Téléphonique</h6>
                            <small class="text-muted">+212 5XX XXX XXX</small>
                        </div>
                        <div class="col-md-4">
                            <i class="fas fa-envelope fa-2x text-success mb-2"></i>
                            <h6>Email Support</h6>
                            <small class="text-muted">support@evonpower.ma</small>
                        </div>
                        <div class="col-md-4">
                            <i class="fas fa-comments fa-2x text-info mb-2"></i>
                            <h6>Chat en Direct</h6>
                            <small class="text-muted">Disponible 24/7</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Causes possibles -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Causes Possibles
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li><i class="fas fa-times text-danger me-2"></i> Solde insuffisant</li>
                                <li><i class="fas fa-times text-danger me-2"></i> Carte expirée</li>
                                <li><i class="fas fa-times text-danger me-2"></i> Données incorrectes</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li><i class="fas fa-times text-danger me-2"></i> Limite dépassée</li>
                                <li><i class="fas fa-times text-danger me-2"></i> Carte bloquée</li>
                                <li><i class="fas fa-times text-danger me-2"></i> Problème réseau</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
// Animation d'erreur
document.addEventListener('DOMContentLoaded', function() {
    const errorIcon = document.querySelector('.error-icon i');
    errorIcon.style.animation = 'shake 0.5s ease-in-out';
});

// Auto-redirection après 15 secondes (optionnel)
setTimeout(() => {
    if (confirm('Voulez-vous retourner au tableau de bord ?')) {
        window.location.href = '{{ route("dashboard") }}';
    }
}, 15000);

// Fonction pour réessayer le paiement
function retryPayment() {
    if (confirm('Voulez-vous réessayer le paiement ?')) {
        window.history.back();
    }
}
</script>
@endsection

@section('styles')
<style>
.error-icon {
    animation: shake 0.5s ease-in-out;
}

@keyframes shake {
    0%, 100% {
        transform: translateX(0);
    }
    25% {
        transform: translateX(-5px);
    }
    75% {
        transform: translateX(5px);
    }
}

.card {
    border-radius: 15px;
    overflow: hidden;
}

.btn {
    border-radius: 25px;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.alert {
    border-radius: 10px;
}

.list-unstyled li {
    padding: 5px 0;
}

.text-danger {
    color: #dc3545 !important;
}

.bg-danger {
    background-color: #dc3545 !important;
}

.border-danger {
    border-color: #dc3545 !important;
}
</style>
@endsection