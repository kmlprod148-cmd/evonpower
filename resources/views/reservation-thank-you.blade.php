@extends('layouts.app')

@section('title', 'Réservation Confirmée - Merci !')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-green-50 via-blue-50 to-purple-50 flex items-center justify-center p-4">
    <div class="max-w-2xl w-full">
        <!-- Success Animation Container -->
        <div class="bg-white/90 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/20 overflow-hidden">
            <!-- Header with Gradient -->
            <div class="bg-gradient-to-r from-green-500 via-emerald-500 to-teal-500 p-8 text-white relative overflow-hidden">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="relative z-10 text-center">
                    <!-- Animated Success Icon -->
                    <div class="w-24 h-24 mx-auto mb-6 bg-white/20 rounded-full flex items-center justify-center animate-bounce">
                        <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-3xl text-green-500 animate-pulse"></i>
                        </div>
                    </div>
                    
                    <h1 class="text-4xl font-bold mb-2">Réservation Confirmée !</h1>
                    <p class="text-green-100 text-lg">Votre session de recharge a été réservée avec succès</p>
                </div>
                
                <!-- Decorative Elements -->
                <div class="absolute -top-4 -right-4 w-32 h-32 bg-white/10 rounded-full animate-pulse"></div>
                <div class="absolute -bottom-2 -left-2 w-20 h-20 bg-white/5 rounded-full"></div>
            </div>
            
            <!-- Content -->
            <div class="p-8 space-y-6">
                <!-- Reservation Details Card -->
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-2xl p-6">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-xl flex items-center justify-center mr-4">
                            <i class="fas fa-charging-station text-white text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-800">Détails de la Réservation</h3>
                            <p class="text-gray-600">Informations de votre session</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-3">
                            <div class="flex items-center">
                                <i class="fas fa-map-marker-alt text-blue-500 mr-3"></i>
                                <div>
                                    <p class="font-semibold text-gray-800">{{ $chargingPoint->name ?? 'Borne de Recharge' }}</p>
                                    <p class="text-sm text-gray-600">Emplacement</p>
                                </div>
                            </div>
                            
                            <div class="flex items-center">
                                <i class="fas fa-clock text-green-500 mr-3"></i>
                                <div>
                                    <p class="font-semibold text-gray-800">{{ $reservation->duration ?? '30' }} minutes</p>
                                    <p class="text-sm text-gray-600">Durée de recharge</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="space-y-3">
                            <div class="flex items-center">
                                <i class="fas fa-calendar-alt text-purple-500 mr-3"></i>
                                <div>
                                    <p class="font-semibold text-gray-800">{{ now()->format('d/m/Y') }}</p>
                                    <p class="text-sm text-gray-600">Date de réservation</p>
                                </div>
                            </div>
                            
                            <div class="flex items-center">
                                <i class="fas fa-euro-sign text-orange-500 mr-3"></i>
                                <div>
                                    <p class="font-semibold text-gray-800">{{ number_format($reservation->total_cost ?? 0, 2) }} EUR</p>
                                    <p class="text-sm text-gray-600">Coût total</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Method Card -->
                <div class="bg-gradient-to-r from-orange-50 to-red-50 border border-orange-200 rounded-2xl p-6">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-gradient-to-r from-orange-500 to-red-500 rounded-xl flex items-center justify-center mr-4">
                            <i class="fas fa-cash-register text-white text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-800">Paiement Sur Place</h3>
                            <p class="text-gray-600">À régler lors de votre arrivée</p>
                        </div>
                    </div>
                    
                    <div class="bg-white/80 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="font-semibold text-gray-800">Montant à payer :</span>
                            <span class="text-2xl font-bold text-orange-600">{{ number_format($reservation->total_cost ?? 0, 2) }} EUR</span>
                        </div>
                        
                        <div class="space-y-2 text-sm text-gray-600">
                            <div class="flex items-center">
                                <i class="fas fa-credit-card text-gray-400 mr-2"></i>
                                <span>Carte bancaire acceptée</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-money-bill-wave text-gray-400 mr-2"></i>
                                <span>Espèces acceptées</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Instructions Card -->
                <div class="bg-gradient-to-r from-purple-50 to-pink-50 border border-purple-200 rounded-2xl p-6">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-pink-500 rounded-xl flex items-center justify-center mr-4">
                            <i class="fas fa-list-check text-white text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-800">Prochaines Étapes</h3>
                            <p class="text-gray-600">Ce qu'il faut faire maintenant</p>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center mr-3 mt-1">
                                <span class="text-purple-600 font-bold text-sm">1</span>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800">Rendez-vous à la borne</p>
                                <p class="text-sm text-gray-600">Présentez-vous à l'emplacement indiqué</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center mr-3 mt-1">
                                <span class="text-purple-600 font-bold text-sm">2</span>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800">Effectuez le paiement</p>
                                <p class="text-sm text-gray-600">Payez le montant indiqué sur place</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center mr-3 mt-1">
                                <span class="text-purple-600 font-bold text-sm">3</span>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800">Commencez la recharge</p>
                                <p class="text-sm text-gray-600">Votre session sera activée immédiatement</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer Actions -->
            <div class="bg-gray-50/80 backdrop-blur-sm p-6 border-t border-gray-200">
                <div class="flex flex-col sm:flex-row gap-4">
                    <button onclick="window.print()" class="flex-1 bg-white border-2 border-gray-300 text-gray-700 py-3 px-6 rounded-xl font-semibold hover:bg-gray-50 hover:border-gray-400 transition-all duration-200 shadow-sm">
                        <i class="fas fa-print mr-2"></i>
                        Imprimer la Réservation
                    </button>
                    <a href="{{ route('charging-points.index') }}" class="flex-1 bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-3 px-6 rounded-xl font-semibold hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 text-center">
                        <i class="fas fa-home mr-2"></i>
                        Retour à l'Accueil
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Floating Success Elements -->
        <div class="fixed top-10 right-10 w-20 h-20 bg-green-500/20 rounded-full animate-ping"></div>
        <div class="fixed bottom-10 left-10 w-16 h-16 bg-blue-500/20 rounded-full animate-pulse"></div>
    </div>
</div>

<!-- Custom Animations -->
<style>
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

.animate-float {
    animation: float 3s ease-in-out infinite;
}

@keyframes glow {
    0%, 100% { box-shadow: 0 0 20px rgba(34, 197, 94, 0.3); }
    50% { box-shadow: 0 0 40px rgba(34, 197, 94, 0.6); }
}

.animate-glow {
    animation: glow 2s ease-in-out infinite;
}
</style>

<script>
// Add some interactive animations
document.addEventListener('DOMContentLoaded', function() {
    // Add floating animation to success icon
    const successIcon = document.querySelector('.animate-bounce');
    if (successIcon) {
        successIcon.classList.add('animate-float');
    }
    
    // Add glow effect to main card
    const mainCard = document.querySelector('.bg-white\\/90');
    if (mainCard) {
        mainCard.classList.add('animate-glow');
    }
    
    // Confetti effect (optional)
    setTimeout(() => {
        // Simple confetti effect
        for (let i = 0; i < 50; i++) {
            createConfetti();
        }
    }, 1000);
});

function createConfetti() {
    const confetti = document.createElement('div');
    confetti.style.position = 'fixed';
    confetti.style.width = '10px';
    confetti.style.height = '10px';
    confetti.style.backgroundColor = ['#10b981', '#3b82f6', '#8b5cf6', '#f59e0b'][Math.floor(Math.random() * 4)];
    confetti.style.left = Math.random() * 100 + 'vw';
    confetti.style.top = '-10px';
    confetti.style.borderRadius = '50%';
    confetti.style.pointerEvents = 'none';
    confetti.style.zIndex = '1000';
    
    document.body.appendChild(confetti);
    
    const animation = confetti.animate([
        { transform: 'translateY(0px) rotate(0deg)', opacity: 1 },
        { transform: `translateY(${window.innerHeight + 100}px) rotate(720deg)`, opacity: 0 }
    ], {
        duration: 3000 + Math.random() * 2000,
        easing: 'cubic-bezier(0.25, 0.46, 0.45, 0.94)'
    });
    
    animation.onfinish = () => confetti.remove();
}
</script>
@endsection
