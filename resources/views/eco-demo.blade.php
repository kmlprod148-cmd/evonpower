@extends('layouts.app')

@section('title', 'Démonstration des Animations Éco-énergétiques')

@section('content')
<div class="min-h-screen bg-gradient-eco">
    <!-- Header avec animation -->
    <div class="bg-white shadow-eco-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="text-center animate-fade-in">
                <h1 class="text-4xl font-bold text-gradient-eco mb-4">
                    🌱 Animations Éco-énergétiques EVON
                </h1>
                <p class="text-lg text-eco-gray-600 animate-fade-in-delayed">
                    Découvrez notre palette de couleurs vertes et nos animations inspirées de l'énergie durable
                </p>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        
        <!-- Section Boutons -->
        <section class="mb-16 animate-on-scroll">
            <h2 class="text-3xl font-bold text-eco-green-800 mb-8 text-center">Boutons Éco-énergétiques</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <button class="btn-eco-primary hover-lift">
                    🌿 Bouton Éco
                </button>
                <button class="btn-energy-primary hover-energy-glow">
                    ⚡ Énergie
                </button>
                <button class="btn-nature-primary hover-glow">
                    🍃 Nature
                </button>
                <button class="btn-eco-secondary hover-lift">
                    🌱 Secondaire
                </button>
            </div>
        </section>

        <!-- Section Cartes -->
        <section class="mb-16 animate-on-scroll">
            <h2 class="text-3xl font-bold text-eco-green-800 mb-8 text-center">Cartes Flottantes</h2>
            <div class="grid-eco">
                <div class="card-eco hover-lift">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-eco-green-100 rounded-full flex items-center justify-center mr-4 animate-rotate-slow">
                            🌿
                        </div>
                        <h3 class="text-xl font-semibold text-eco-green-800">Carte Éco</h3>
                    </div>
                    <p class="text-eco-gray-600 mb-4">Une carte avec des animations douces et des couleurs naturelles.</p>
                    <div class="flex space-x-2">
                        <span class="badge-eco">Écologique</span>
                        <span class="badge-energy">Énergétique</span>
                    </div>
                </div>

                <div class="card-energy hover-energy-glow">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-energy-green-100 rounded-full flex items-center justify-center mr-4 animate-pulse-energy">
                            ⚡
                        </div>
                        <h3 class="text-xl font-semibold text-energy-green-800">Carte Énergie</h3>
                    </div>
                    <p class="text-eco-gray-600 mb-4">Une carte avec des effets énergétiques et des animations dynamiques.</p>
                    <div class="flex space-x-2">
                        <span class="status-charging">En charge</span>
                        <span class="badge-energy">Actif</span>
                    </div>
                </div>

                <div class="card-nature hover-glow">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-nature-green-100 rounded-full flex items-center justify-center mr-4 animate-leaf">
                            🍃
                        </div>
                        <h3 class="text-xl font-semibold text-nature-green-800">Carte Nature</h3>
                    </div>
                    <p class="text-eco-gray-600 mb-4">Une carte inspirée de la nature avec des animations organiques.</p>
                    <div class="flex space-x-2">
                        <span class="badge-nature">Naturel</span>
                        <span class="status-online">En ligne</span>
                    </div>
                </div>

                <div class="card-floating hover-lift">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-gradient-energy rounded-full flex items-center justify-center mr-4 animate-float">
                            🌱
                        </div>
                        <h3 class="text-xl font-semibold text-gradient-nature">Carte Flottante</h3>
                    </div>
                    <p class="text-eco-gray-600 mb-4">Une carte avec un effet de flottement continu et des gradients.</p>
                    <div class="flex space-x-2">
                        <span class="badge-eco">Flottant</span>
                        <span class="badge-nature">Animé</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section Formulaires -->
        <section class="mb-16 animate-on-scroll">
            <h2 class="text-3xl font-bold text-eco-green-800 mb-8 text-center">Formulaires Éco</h2>
            <div class="max-w-2xl mx-auto">
                <div class="card-eco">
                    <form class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-eco-green-700 mb-2">Nom</label>
                            <input type="text" class="input-eco" placeholder="Votre nom">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-eco-green-700 mb-2">Email</label>
                            <input type="email" class="input-energy" placeholder="votre@email.com">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-eco-green-700 mb-2">Message</label>
                            <textarea class="input-eco h-32 resize-none" placeholder="Votre message..."></textarea>
                        </div>
                        <button type="submit" class="btn-eco-primary w-full hover-lift">
                            Envoyer le message
                        </button>
                    </form>
                </div>
            </div>
        </section>

        <!-- Section Tableaux -->
        <section class="mb-16 animate-on-scroll">
            <h2 class="text-3xl font-bold text-eco-green-800 mb-8 text-center">Tableaux Éco</h2>
            <div class="card-eco overflow-hidden">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th class="table-header-eco">Station</th>
                            <th class="table-header-eco">Statut</th>
                            <th class="table-header-eco">Puissance</th>
                            <th class="table-header-eco">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-eco-green-100">
                        <tr class="hover:bg-eco-green-50 transition-colors duration-200">
                            <td class="table-cell-eco">Station Éco 1</td>
                            <td class="table-cell-eco">
                                <span class="status-online">En ligne</span>
                            </td>
                            <td class="table-cell-eco">22 kW</td>
                            <td class="table-cell-eco">
                                <button class="btn-eco-secondary text-sm">Gérer</button>
                            </td>
                        </tr>
                        <tr class="hover:bg-eco-green-50 transition-colors duration-200">
                            <td class="table-cell-eco">Station Énergie 2</td>
                            <td class="table-cell-eco">
                                <span class="status-charging">En charge</span>
                            </td>
                            <td class="table-cell-eco">50 kW</td>
                            <td class="table-cell-eco">
                                <button class="btn-energy-primary text-sm">Contrôler</button>
                            </td>
                        </tr>
                        <tr class="hover:bg-eco-green-50 transition-colors duration-200">
                            <td class="table-cell-eco">Station Nature 3</td>
                            <td class="table-cell-eco">
                                <span class="status-offline">Hors ligne</span>
                            </td>
                            <td class="table-cell-eco">11 kW</td>
                            <td class="table-cell-eco">
                                <button class="btn-nature-primary text-sm">Réparer</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Section Effets Spéciaux -->
        <section class="mb-16 animate-on-scroll">
            <h2 class="text-3xl font-bold text-eco-green-800 mb-8 text-center">Effets Spéciaux</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                
                <!-- Effet de particules énergétiques -->
                <div class="card-eco energy-particles">
                    <h3 class="text-lg font-semibold text-eco-green-800 mb-4">Particules Énergétiques</h3>
                    <p class="text-eco-gray-600">Effet de flux d'énergie continu</p>
                </div>

                <!-- Effet de brillance -->
                <div class="card-energy card-shimmer">
                    <h3 class="text-lg font-semibold text-energy-green-800 mb-4">Brillance</h3>
                    <p class="text-eco-gray-600">Effet de brillance qui traverse la carte</p>
                </div>

                <!-- Effet de croissance -->
                <div class="card-nature grow-on-hover">
                    <h3 class="text-lg font-semibold text-nature-green-800 mb-4">Croissance</h3>
                    <p class="text-eco-gray-600">La carte grandit au survol</p>
                </div>

                <!-- Effet de respiration -->
                <div class="card-eco breathe">
                    <h3 class="text-lg font-semibold text-eco-green-800 mb-4">Respiration</h3>
                    <p class="text-eco-gray-600">Animation de respiration douce</p>
                </div>

                <!-- Effet de gradient animé -->
                <div class="card-floating animated-gradient">
                    <h3 class="text-lg font-semibold text-white mb-4">Gradient Animé</h3>
                    <p class="text-white/80">Gradient qui change de couleur</p>
                </div>

                <!-- Effet de lueur pulsante -->
                <div class="card-energy pulse-glow">
                    <h3 class="text-lg font-semibold text-energy-green-800 mb-4">Lueur Pulsante</h3>
                    <p class="text-eco-gray-600">Lueur qui pulse autour de la carte</p>
                </div>
            </div>
        </section>

        <!-- Section Navigation -->
        <section class="mb-16 animate-on-scroll">
            <h2 class="text-3xl font-bold text-eco-green-800 mb-8 text-center">Navigation Éco</h2>
            <div class="max-w-md mx-auto">
                <div class="card-eco">
                    <nav class="space-y-2">
                        <a href="#" class="nav-item-active">
                            <span class="mr-3">🏠</span>
                            Tableau de bord
                        </a>
                        <a href="#" class="nav-item-eco">
                            <span class="mr-3">⚡</span>
                            Stations de charge
                        </a>
                        <a href="#" class="nav-item-eco">
                            <span class="mr-3">📊</span>
                            Statistiques
                        </a>
                        <a href="#" class="nav-item-eco">
                            <span class="mr-3">⚙️</span>
                            Paramètres
                        </a>
                    </nav>
                </div>
            </div>
        </section>

        <!-- Section Chargement -->
        <section class="mb-16 animate-on-scroll">
            <h2 class="text-3xl font-bold text-eco-green-800 mb-8 text-center">États de Chargement</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center">
                    <h3 class="text-lg font-semibold text-eco-green-800 mb-4">Chargement Éco</h3>
                    <div class="loading-eco h-4 rounded-eco mb-4"></div>
                    <div class="loading-eco h-4 rounded-eco mb-4 w-3/4"></div>
                    <div class="loading-eco h-4 rounded-eco w-1/2"></div>
                </div>
                
                <div class="text-center">
                    <h3 class="text-lg font-semibold text-energy-green-800 mb-4">Chargement Énergie</h3>
                    <div class="loading-energy h-4 rounded-eco mb-4"></div>
                    <div class="loading-energy h-4 rounded-eco mb-4 w-3/4"></div>
                    <div class="loading-energy h-4 rounded-eco w-1/2"></div>
                </div>
                
                <div class="text-center">
                    <h3 class="text-lg font-semibold text-nature-green-800 mb-4">Points Énergétiques</h3>
                    <div class="loading-energy-dots justify-center mb-4">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                    <p class="text-sm text-eco-gray-600">Chargement en cours...</p>
                </div>
            </div>
        </section>

        <!-- Section Texte avec Effets -->
        <section class="mb-16 animate-on-scroll">
            <h2 class="text-3xl font-bold text-eco-green-800 mb-8 text-center">Effets de Texte</h2>
            <div class="text-center space-y-8">
                <div class="text-reveal">
                    <h3 class="text-2xl font-bold text-gradient-eco mb-4">
                        <span>E</span><span>V</span><span>O</span><span>N</span>
                    </h3>
                </div>
                
                <div class="neon-glow">
                    <p class="text-xl text-eco-green-600 font-semibold">
                        Énergie Verte pour un Futur Durable
                    </p>
                </div>
                
                <div class="text-gradient-nature">
                    <p class="text-lg font-medium">
                        Rejoignez la révolution de la mobilité électrique
                    </p>
                </div>
            </div>
        </section>

    </div>

    <!-- Footer avec animation -->
    <footer class="bg-eco-green-800 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="animate-fade-in">
                <h3 class="text-2xl font-bold mb-4">🌱 EVON - Énergie Verte</h3>
                <p class="text-eco-green-200 mb-6">
                    Propulsé par des technologies durables et des animations éco-énergétiques
                </p>
                <div class="flex justify-center space-x-4">
                    <button class="btn-eco-secondary hover-lift">
                        En savoir plus
                    </button>
                    <button class="btn-energy-primary hover-energy-glow">
                        Commencer
                    </button>
                </div>
            </div>
        </div>
    </footer>
</div>

<script>
// Animation au scroll
document.addEventListener('DOMContentLoaded', function() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, observerOptions);

    // Observer tous les éléments avec la classe animate-on-scroll
    document.querySelectorAll('.animate-on-scroll').forEach(el => {
        observer.observe(el);
    });
});
</script>
@endsection
