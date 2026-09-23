@extends('layouts.app')

@section('title', 'Démonstration des Boutons avec #4acf7b')

@section('content')
<div class="min-h-screen bg-gradient-eco">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        
        <!-- Header -->
        <div class="text-center mb-12 fade-in-up">
            <h1 class="text-4xl font-bold text-gradient-eco mb-4">
                🎨 Boutons avec la Couleur #4acf7b
            </h1>
            <p class="text-lg text-eco-gray-600">
                Tous les boutons utilisent maintenant la nouvelle couleur verte EVON
            </p>
            <div class="mt-4 inline-flex items-center px-4 py-2 bg-eco-green-100 rounded-lg">
                <div class="w-6 h-6 bg-eco-green-500 rounded-full mr-3"></div>
                <span class="text-eco-green-800 font-medium">Couleur principale: #4acf7b</span>
            </div>
        </div>

        <!-- Section Boutons Primaires -->
        <section class="mb-16">
            <h2 class="text-2xl font-bold text-eco-green-800 mb-8">Boutons Primaires</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-eco-green-700">Tailles</h3>
                    <div class="space-y-3">
                        <button class="btn-eco-primary text-sm px-3 py-2">Petit</button>
                        <button class="btn-eco-primary px-4 py-2">Normal</button>
                        <button class="btn-eco-primary text-lg px-6 py-3">Grand</button>
                        <button class="btn-eco-primary text-xl px-8 py-4">Très Grand</button>
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-eco-green-700">États</h3>
                    <div class="space-y-3">
                        <button class="btn-eco-primary">Normal</button>
                        <button class="btn-eco-primary hover:bg-eco-green-700">Survol</button>
                        <button class="btn-eco-primary opacity-50 cursor-not-allowed" disabled>Désactivé</button>
                        <button class="btn-eco-primary animate-pulse-energy">Pulsation</button>
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-eco-green-700">Avec Icônes</h3>
                    <div class="space-y-3">
                        <button class="btn-eco-primary">
                            <span class="mr-2">🌿</span>
                            Écologique
                        </button>
                        <button class="btn-eco-primary">
                            <span class="mr-2">⚡</span>
                            Énergétique
                        </button>
                        <button class="btn-eco-primary">
                            <span class="mr-2">🔋</span>
                            Chargement
                        </button>
                        <button class="btn-eco-primary">
                            <span class="mr-2">🚗</span>
                            Véhicule
                        </button>
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-eco-green-700">Effets</h3>
                    <div class="space-y-3">
                        <button class="btn-eco-primary hover-lift">Élévation</button>
                        <button class="btn-eco-primary hover-glow">Lueur</button>
                        <button class="btn-eco-primary btn-eco-hover">Brillance</button>
                        <button class="btn-eco-primary energy-pulse">Pulsation</button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section Boutons Secondaires -->
        <section class="mb-16">
            <h2 class="text-2xl font-bold text-eco-green-800 mb-8">Boutons Secondaires</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-eco-green-700">Styles</h3>
                    <div class="space-y-3">
                        <button class="btn-eco-secondary">Secondaire</button>
                        <button class="bg-transparent hover:bg-eco-green-50 text-eco-green-600 border border-eco-green-300 hover:border-eco-green-400 px-4 py-2 rounded-lg transition-all duration-300">
                            Outline
                        </button>
                        <button class="bg-transparent hover:bg-eco-green-100 text-eco-green-600 px-4 py-2 rounded-lg transition-all duration-300">
                            Ghost
                        </button>
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-eco-green-700">Actions</h3>
                    <div class="space-y-3">
                        <button class="btn-eco-secondary">
                            <span class="mr-2">✅</span>
                            Confirmer
                        </button>
                        <button class="btn-eco-secondary">
                            <span class="mr-2">❌</span>
                            Annuler
                        </button>
                        <button class="btn-eco-secondary">
                            <span class="mr-2">📝</span>
                            Modifier
                        </button>
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-eco-green-700">Navigation</h3>
                    <div class="space-y-3">
                        <button class="btn-eco-secondary">
                            <span class="mr-2">←</span>
                            Précédent
                        </button>
                        <button class="btn-eco-secondary">
                            <span class="mr-2">→</span>
                            Suivant
                        </button>
                        <button class="btn-eco-secondary">
                            <span class="mr-2">🏠</span>
                            Accueil
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section Boutons d'Action -->
        <section class="mb-16">
            <h2 class="text-2xl font-bold text-eco-green-800 mb-8">Boutons d'Action Spéciaux</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-eco-green-700">Chargement</h3>
                    <div class="space-y-3">
                        <button class="btn-energy-primary">
                            <div class="loading-energy-dots mr-2">
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>
                            Chargement...
                        </button>
                        <button class="btn-energy-primary shimmer-effect">
                            Effet Brillance
                        </button>
                        <button class="btn-energy-primary energy-pulse">
                            Pulsation Énergétique
                        </button>
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-eco-green-700">Statuts</h3>
                    <div class="space-y-3">
                        <button class="btn-nature-primary">
                            <span class="mr-2">🟢</span>
                            En ligne
                        </button>
                        <button class="btn-nature-primary">
                            <span class="mr-2">⚡</span>
                            En charge
                        </button>
                        <button class="btn-nature-primary">
                            <span class="mr-2">🔴</span>
                            Hors ligne
                        </button>
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-eco-green-700">Contrôles</h3>
                    <div class="space-y-3">
                        <button class="btn-eco-primary">
                            <span class="mr-2">▶️</span>
                            Démarrer
                        </button>
                        <button class="btn-eco-primary">
                            <span class="mr-2">⏸️</span>
                            Pause
                        </button>
                        <button class="btn-eco-primary">
                            <span class="mr-2">⏹️</span>
                            Arrêter
                        </button>
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-eco-green-700">Système</h3>
                    <div class="space-y-3">
                        <button class="btn-danger">
                            <span class="mr-2">🗑️</span>
                            Supprimer
                        </button>
                        <button class="btn-danger">
                            <span class="mr-2">⚠️</span>
                            Danger
                        </button>
                        <button class="btn-danger">
                            <span class="mr-2">🚨</span>
                            Urgence
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section Boutons avec Animations -->
        <section class="mb-16">
            <h2 class="text-2xl font-bold text-eco-green-800 mb-8">Boutons avec Animations</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="card-eco">
                    <h3 class="text-lg font-semibold text-eco-green-800 mb-4">Animations de Base</h3>
                    <div class="space-y-3">
                        <button class="btn-eco-primary animate-fade-in">Apparition</button>
                        <button class="btn-eco-primary animate-scale-in">Zoom</button>
                        <button class="btn-eco-primary animate-slide-up">Glissement</button>
                        <button class="btn-eco-primary animate-bounce-energy">Rebond</button>
                    </div>
                </div>

                <div class="card-energy">
                    <h3 class="text-lg font-semibold text-energy-green-800 mb-4">Effets Visuels</h3>
                    <div class="space-y-3">
                        <button class="btn-energy-primary animate-glow-green">Lueur Verte</button>
                        <button class="btn-energy-primary animate-glow-energy">Lueur Énergétique</button>
                        <button class="btn-energy-primary animate-float">Flottement</button>
                        <button class="btn-energy-primary animate-wiggle">Balancement</button>
                    </div>
                </div>

                <div class="card-nature">
                    <h3 class="text-lg font-semibold text-nature-green-800 mb-4">Effets Naturels</h3>
                    <div class="space-y-3">
                        <button class="btn-nature-primary animate-leaf">Feuille</button>
                        <button class="btn-nature-primary animate-wave">Vague</button>
                        <button class="btn-nature-primary animate-energy-flow">Flux Énergétique</button>
                        <button class="btn-nature-primary animate-rotate-slow">Rotation</button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section Exemples d'Utilisation -->
        <section class="mb-16">
            <h2 class="text-2xl font-bold text-eco-green-800 mb-8">Exemples d'Utilisation</h2>
            
            <div class="space-y-8">
                <!-- Formulaire -->
                <div class="card-eco">
                    <h3 class="text-lg font-semibold text-eco-green-800 mb-6">Formulaire de Contact</h3>
                    <form class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-eco-green-700 mb-2">Nom</label>
                                <input type="text" class="input-eco" placeholder="Votre nom">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-eco-green-700 mb-2">Email</label>
                                <input type="email" class="input-energy" placeholder="votre@email.com">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-eco-green-700 mb-2">Message</label>
                            <textarea class="input-eco h-32 resize-none" placeholder="Votre message..."></textarea>
                        </div>
                        <div class="flex space-x-4">
                            <button type="submit" class="btn-eco-primary hover-lift">
                                <span class="mr-2">📧</span>
                                Envoyer
                            </button>
                            <button type="button" class="btn-eco-secondary">
                                <span class="mr-2">❌</span>
                                Annuler
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Actions de Station -->
                <div class="card-energy">
                    <h3 class="text-lg font-semibold text-energy-green-800 mb-6">Actions de Station</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <button class="btn-energy-primary energy-pulse">
                            <span class="mr-2">⚡</span>
                            Démarrer Charge
                        </button>
                        <button class="btn-eco-secondary">
                            <span class="mr-2">⏸️</span>
                            Pause Charge
                        </button>
                        <button class="btn-danger">
                            <span class="mr-2">⏹️</span>
                            Arrêter Charge
                        </button>
                    </div>
                </div>

                <!-- Navigation -->
                <div class="card-nature">
                    <h3 class="text-lg font-semibold text-nature-green-800 mb-6">Navigation</h3>
                    <div class="flex flex-wrap gap-4">
                        <button class="btn-nature-primary">
                            <span class="mr-2">🏠</span>
                            Tableau de bord
                        </button>
                        <button class="btn-nature-primary">
                            <span class="mr-2">⚡</span>
                            Stations
                        </button>
                        <button class="btn-nature-primary">
                            <span class="mr-2">📊</span>
                            Statistiques
                        </button>
                        <button class="btn-nature-primary">
                            <span class="mr-2">⚙️</span>
                            Paramètres
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section Code d'Exemple -->
        <section class="mb-16">
            <h2 class="text-2xl font-bold text-eco-green-800 mb-8">Code d'Exemple</h2>
            
            <div class="space-y-6">
                <div class="card-eco">
                    <h3 class="text-lg font-semibold text-eco-green-800 mb-4">Classes CSS</h3>
                    <pre class="bg-eco-gray-900 text-eco-green-100 p-4 rounded-lg overflow-x-auto"><code>&lt;!-- Bouton principal avec #4acf7b --&gt;
&lt;button class="btn-eco-primary hover-lift"&gt;
    Mon Bouton Éco
&lt;/button&gt;

&lt;!-- Bouton avec animation --&gt;
&lt;button class="btn-energy-primary animate-pulse-energy"&gt;
    Bouton Énergétique
&lt;/button&gt;

&lt;!-- Bouton avec effet de brillance --&gt;
&lt;button class="btn-eco-primary btn-eco-hover"&gt;
    Bouton Brillant
&lt;/button&gt;</code></pre>
                </div>

                <div class="card-energy">
                    <h3 class="text-lg font-semibold text-energy-green-800 mb-4">Composants Blade</h3>
                    <pre class="bg-eco-gray-900 text-energy-green-100 p-4 rounded-lg overflow-x-auto"><code>&lt;x-eco-button variant="primary" size="lg" icon="🌿"&gt;
    Bouton Éco-énergétique
&lt;/x-eco-button&gt;

&lt;x-eco-button variant="energy" :loading="true"&gt;
    Chargement...
&lt;/x-eco-button&gt;

&lt;x-eco-button variant="nature" animated="true"&gt;
    Bouton Nature
&lt;/x-eco-button&gt;</code></pre>
                </div>
            </div>
        </section>

    </div>
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

    document.querySelectorAll('.animate-on-scroll').forEach(el => {
        observer.observe(el);
    });
});
</script>
@endsection
