@extends('layouts.app')

@section('title', 'Composants Éco-énergétiques - Démonstration')

@section('content')
<div class="min-h-screen bg-gradient-eco">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        
        <!-- Header -->
        <div class="text-center mb-12 fade-in-up">
            <h1 class="text-4xl font-bold text-gradient-eco mb-4">
                🌱 Composants Éco-énergétiques EVON
            </h1>
            <p class="text-lg text-eco-gray-600">
                Composants réutilisables avec animations vertes pour votre application
            </p>
        </div>

        <!-- Section Boutons -->
        <section class="mb-16">
            <h2 class="text-2xl font-bold text-eco-green-800 mb-8">Boutons Éco-énergétiques</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Boutons primaires -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-eco-green-700">Boutons Primaires</h3>
                    <div class="space-y-3">
                        <x-eco-button variant="primary" size="sm">Petit Bouton</x-eco-button>
                        <x-eco-button variant="primary" size="md">Bouton Normal</x-eco-button>
                        <x-eco-button variant="primary" size="lg">Grand Bouton</x-eco-button>
                        <x-eco-button variant="primary" icon="🌿">Avec Icône</x-eco-button>
                        <x-eco-button variant="primary" :loading="true">En Chargement</x-eco-button>
                    </div>
                </div>

                <!-- Boutons énergétiques -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-energy-green-700">Boutons Énergétiques</h3>
                    <div class="space-y-3">
                        <x-eco-button variant="energy" size="sm">Énergie Petit</x-eco-button>
                        <x-eco-button variant="energy" size="md">Énergie Normal</x-eco-button>
                        <x-eco-button variant="energy" size="lg">Énergie Grand</x-eco-button>
                        <x-eco-button variant="energy" icon="⚡">Avec Éclair</x-eco-button>
                    </div>
                </div>

                <!-- Boutons nature -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-nature-green-700">Boutons Nature</h3>
                    <div class="space-y-3">
                        <x-eco-button variant="nature" size="sm">Nature Petit</x-eco-button>
                        <x-eco-button variant="nature" size="md">Nature Normal</x-eco-button>
                        <x-eco-button variant="nature" size="lg">Nature Grand</x-eco-button>
                        <x-eco-button variant="nature" icon="🍃">Avec Feuille</x-eco-button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section Cartes -->
        <section class="mb-16">
            <h2 class="text-2xl font-bold text-eco-green-800 mb-8">Cartes Éco-énergétiques</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Carte par défaut -->
                <x-eco-card variant="default">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 bg-eco-green-100 rounded-full flex items-center justify-center mr-3">
                            🌿
                        </div>
                        <h3 class="text-lg font-semibold text-eco-green-800">Carte Éco</h3>
                    </div>
                    <p class="text-eco-gray-600 mb-4">Une carte avec des animations douces et des couleurs naturelles.</p>
                    <x-eco-status status="online">En ligne</x-eco-status>
                </x-eco-card>

                <!-- Carte énergétique -->
                <x-eco-card variant="energy">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 bg-energy-green-100 rounded-full flex items-center justify-center mr-3 energy-pulse">
                            ⚡
                        </div>
                        <h3 class="text-lg font-semibold text-energy-green-800">Carte Énergie</h3>
                    </div>
                    <p class="text-eco-gray-600 mb-4">Une carte avec des effets énergétiques et des animations dynamiques.</p>
                    <x-eco-status status="charging">En charge</x-eco-status>
                </x-eco-card>

                <!-- Carte nature -->
                <x-eco-card variant="nature">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 bg-nature-green-100 rounded-full flex items-center justify-center mr-3 float-gentle">
                            🍃
                        </div>
                        <h3 class="text-lg font-semibold text-nature-green-800">Carte Nature</h3>
                    </div>
                    <p class="text-eco-gray-600 mb-4">Une carte inspirée de la nature avec des animations organiques.</p>
                    <x-eco-status status="online">Actif</x-eco-status>
                </x-eco-card>

                <!-- Carte flottante -->
                <x-eco-card variant="floating">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 bg-gradient-energy rounded-full flex items-center justify-center mr-3">
                            🌱
                        </div>
                        <h3 class="text-lg font-semibold text-gradient-nature">Carte Flottante</h3>
                    </div>
                    <p class="text-eco-gray-600 mb-4">Une carte avec un effet de flottement et des gradients.</p>
                    <x-eco-status status="online">Flottant</x-eco-status>
                </x-eco-card>

                <!-- Carte élevée -->
                <x-eco-card variant="elevated">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 bg-eco-green-100 rounded-full flex items-center justify-center mr-3 grow-on-hover">
                            📊
                        </div>
                        <h3 class="text-lg font-semibold text-eco-green-800">Carte Élevée</h3>
                    </div>
                    <p class="text-eco-gray-600 mb-4">Une carte avec une ombre plus prononcée et des effets de croissance.</p>
                    <x-eco-status status="online">Statistiques</x-eco-status>
                </x-eco-card>

                <!-- Carte avec effet de brillance -->
                <x-eco-card variant="default">
                    <div class="shimmer-effect rounded-lg p-4">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 bg-eco-green-100 rounded-full flex items-center justify-center mr-3">
                                ✨
                            </div>
                            <h3 class="text-lg font-semibold text-eco-green-800">Carte Brillante</h3>
                        </div>
                        <p class="text-eco-gray-600 mb-4">Une carte avec un effet de brillance qui traverse l'élément.</p>
                        <x-eco-status status="online">Brillant</x-eco-status>
                    </div>
                </x-eco-card>
            </div>
        </section>

        <!-- Section Statuts -->
        <section class="mb-16">
            <h2 class="text-2xl font-bold text-eco-green-800 mb-8">Indicateurs de Statut</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-eco-green-700">Statuts de Base</h3>
                    <div class="space-y-3">
                        <x-eco-status status="online">En ligne</x-eco-status>
                        <x-eco-status status="charging">En charge</x-eco-status>
                        <x-eco-status status="offline">Hors ligne</x-eco-status>
                        <x-eco-status status="error">Erreur</x-eco-status>
                        <x-eco-status status="warning">Avertissement</x-eco-status>
                        <x-eco-status status="maintenance">Maintenance</x-eco-status>
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-energy-green-700">Tailles Différentes</h3>
                    <div class="space-y-3">
                        <x-eco-status status="online" size="sm">Petit</x-eco-status>
                        <x-eco-status status="charging" size="md">Normal</x-eco-status>
                        <x-eco-status status="online" size="lg">Grand</x-eco-status>
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-nature-green-700">Sans Animation</h3>
                    <div class="space-y-3">
                        <x-eco-status status="online" :animated="false">Statique</x-eco-status>
                        <x-eco-status status="charging" :animated="false">Sans pulsation</x-eco-status>
                        <x-eco-status status="offline" :animated="false">Fixe</x-eco-status>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section Exemples d'utilisation -->
        <section class="mb-16">
            <h2 class="text-2xl font-bold text-eco-green-800 mb-8">Exemples d'Utilisation</h2>
            
            <div class="space-y-8">
                <!-- Exemple de tableau avec statuts -->
                <div class="card-eco">
                    <h3 class="text-lg font-semibold text-eco-green-800 mb-4">Tableau des Stations</h3>
                    <div class="overflow-hidden">
                        <table class="min-w-full">
                            <thead>
                                <tr class="bg-eco-green-50">
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-eco-green-700 uppercase tracking-wider">Station</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-eco-green-700 uppercase tracking-wider">Statut</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-eco-green-700 uppercase tracking-wider">Puissance</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-eco-green-700 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-eco-green-100">
                                <tr class="hover:bg-eco-green-50 transition-colors duration-200">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-eco-gray-900">Station Éco 1</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <x-eco-status status="online">En ligne</x-eco-status>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-eco-gray-900">22 kW</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <x-eco-button variant="secondary" size="sm">Gérer</x-eco-button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-eco-green-50 transition-colors duration-200">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-eco-gray-900">Station Énergie 2</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <x-eco-status status="charging">En charge</x-eco-status>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-eco-gray-900">50 kW</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <x-eco-button variant="energy" size="sm">Contrôler</x-eco-button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-eco-green-50 transition-colors duration-200">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-eco-gray-900">Station Nature 3</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <x-eco-status status="offline">Hors ligne</x-eco-status>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-eco-gray-900">11 kW</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <x-eco-button variant="nature" size="sm">Réparer</x-eco-button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Exemple de formulaire -->
                <x-eco-card variant="energy">
                    <h3 class="text-lg font-semibold text-energy-green-800 mb-6">Formulaire de Contact</h3>
                    <form class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-energy-green-700 mb-2">Nom</label>
                                <input type="text" class="input-energy" placeholder="Votre nom">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-energy-green-700 mb-2">Email</label>
                                <input type="email" class="input-energy" placeholder="votre@email.com">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-energy-green-700 mb-2">Message</label>
                            <textarea class="input-energy h-32 resize-none" placeholder="Votre message..."></textarea>
                        </div>
                        <div class="flex space-x-4">
                            <x-eco-button variant="energy" type="submit">Envoyer</x-eco-button>
                            <x-eco-button variant="outline" type="button">Annuler</x-eco-button>
                        </div>
                    </form>
                </x-eco-card>
            </div>
        </section>

        <!-- Section Code d'exemple -->
        <section class="mb-16">
            <h2 class="text-2xl font-bold text-eco-green-800 mb-8">Code d'Exemple</h2>
            
            <div class="space-y-6">
                <x-eco-card variant="default">
                    <h3 class="text-lg font-semibold text-eco-green-800 mb-4">Utilisation des Boutons</h3>
                    <pre class="bg-eco-gray-900 text-eco-green-100 p-4 rounded-lg overflow-x-auto"><code>&lt;x-eco-button variant="primary" size="lg" icon="🌿"&gt;
    Bouton Éco-énergétique
&lt;/x-eco-button&gt;

&lt;x-eco-button variant="energy" :loading="true"&gt;
    Chargement...
&lt;/x-eco-button&gt;</code></pre>
                </x-eco-card>

                <x-eco-card variant="nature">
                    <h3 class="text-lg font-semibold text-nature-green-800 mb-4">Utilisation des Cartes</h3>
                    <pre class="bg-eco-gray-900 text-nature-green-100 p-4 rounded-lg overflow-x-auto"><code>&lt;x-eco-card variant="energy" animated="true"&gt;
    &lt;h3&gt;Titre de la carte&lt;/h3&gt;
    &lt;p&gt;Contenu de la carte avec animations&lt;/p&gt;
&lt;/x-eco-card&gt;</code></pre>
                </x-eco-card>

                <x-eco-card variant="floating">
                    <h3 class="text-lg font-semibold text-gradient-nature mb-4">Utilisation des Statuts</h3>
                    <pre class="bg-eco-gray-900 text-white p-4 rounded-lg overflow-x-auto"><code>&lt;x-eco-status status="charging" size="md" animated="true"&gt;
    En charge
&lt;/x-eco-status&gt;</code></pre>
                </x-eco-card>
            </div>
        </section>

    </div>
</div>

<script>
// Animation au scroll pour les éléments
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
