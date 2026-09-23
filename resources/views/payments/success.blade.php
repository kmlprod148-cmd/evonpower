@extends('layouts.app')

@section('title', 'Paiement Réussi')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg border-success">
                <div class="card-header bg-success text-white text-center">
                    <h4 class="mb-0">
                        <i class="fas fa-check-circle me-2"></i>
                        Paiement Réussi !
                    </h4>
                </div>
                <div class="card-body text-center">
                    <div class="success-icon mb-4">
                        <i class="fas fa-check-circle fa-5x text-success"></i>
                    </div>
                    
                    <h5 class="text-success mb-3">{{ $message }}</h5>
                    
                    <div class="alert alert-success">
                        <h6 class="alert-heading">
                            <i class="fas fa-info-circle me-2"></i>
                            Prochaines Étapes
                        </h6>
                        <ul class="list-unstyled mb-0">
                            <li><i class="fas fa-envelope me-2"></i> Un email de confirmation vous a été envoyé</li>
                            <li><i class="fas fa-calendar me-2"></i> Votre réservation est confirmée</li>
                            <li><i class="fas fa-map-marker-alt me-2"></i> Rendez-vous à la borne de recharge</li>
                        </ul>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <a href="{{ route('dashboard') }}" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Tableau de Bord
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="{{ route('reservations.index') }}" class="btn btn-outline-primary btn-lg w-100">
                                <i class="fas fa-list me-2"></i>
                                Mes Réservations
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

            <!-- Informations supplémentaires -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-shield-alt me-2"></i>
                        Sécurité du Paiement
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <i class="fas fa-lock fa-2x text-primary mb-2"></i>
                            <h6>Chiffrement SSL</h6>
                            <small class="text-muted">256-bit</small>
                        </div>
                        <div class="col-md-4">
                            <i class="fas fa-shield-alt fa-2x text-success mb-2"></i>
                            <h6>Protection PCI</h6>
                            <small class="text-muted">Conforme</small>
                        </div>
                        <div class="col-md-4">
                            <i class="fas fa-user-shield fa-2x text-info mb-2"></i>
                            <h6>Données Sécurisées</h6>
                            <small class="text-muted">Non stockées</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Animation de confetti -->
<div id="confetti-container"></div>

@endsection

@section('scripts')
<script>
// Animation de confetti
document.addEventListener('DOMContentLoaded', function() {
    createConfetti();
});

function createConfetti() {
    const container = document.getElementById('confetti-container');
    const colors = ['#ff6b6b', '#4ecdc4', '#45b7d1', '#96ceb4', '#feca57', '#ff9ff3'];
    
    for (let i = 0; i < 50; i++) {
        setTimeout(() => {
            const confetti = document.createElement('div');
            confetti.style.position = 'fixed';
            confetti.style.width = '10px';
            confetti.style.height = '10px';
            confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
            confetti.style.left = Math.random() * 100 + 'vw';
            confetti.style.top = '-10px';
            confetti.style.zIndex = '9999';
            confetti.style.borderRadius = '50%';
            confetti.style.animation = 'confetti-fall 3s linear forwards';
            
            container.appendChild(confetti);
            
            setTimeout(() => {
                confetti.remove();
            }, 3000);
        }, i * 50);
    }
}

// Auto-redirection après 10 secondes (optionnel)
setTimeout(() => {
    if (confirm('Voulez-vous retourner au tableau de bord ?')) {
        window.location.href = '{{ route("dashboard") }}';
    }
}, 10000);
</script>
@endsection

@section('styles')
<style>
.success-icon {
    animation: bounce 1s ease-in-out;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-30px);
    }
    60% {
        transform: translateY(-15px);
    }
}

@keyframes confetti-fall {
    0% {
        transform: translateY(-100vh) rotate(0deg);
        opacity: 1;
    }
    100% {
        transform: translateY(100vh) rotate(720deg);
        opacity: 0;
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

#confetti-container {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 9999;
}
</style>
@endsection