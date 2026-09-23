@extends('layouts.app')

@section('title', 'Démo Mobile - EVON')

@section('content')
<div class="evon-content">
    {{-- Page Header --}}
    <div class="evon-page-header">
        <h1 class="evon-page-title">📱 Mobile Design Demo</h1>
        <p class="evon-page-subtitle">Découvrez le nouveau design mobile premium</p>
    </div>

    {{-- Alert Success --}}
    <div class="evon-alert-success">
        <div class="evon-alert-icon">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
        </div>
        <div class="evon-alert-content">
            <p class="font-medium">Design Mobile Activé ✅</p>
            <p class="text-sm">Votre application dispose maintenant d'un design mobile premium et centré</p>
        </div>
    </div>

    {{-- Stats Grid --}}
    <div class="evon-stats-grid">
        <div class="evon-stat-card">
            <div class="evon-stat-card-header">
                <span class="evon-stat-card-label">Utilisateurs</span>
                <div class="evon-stat-card-icon">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
            </div>
            <div class="evon-stat-card-value">1,234</div>
            <div class="evon-stat-card-change evon-stat-card-change-positive">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
                <span>+12.5%</span>
            </div>
        </div>

        <div class="evon-stat-card">
            <div class="evon-stat-card-header">
                <span class="evon-stat-card-label">Bornes</span>
                <div class="evon-stat-card-icon">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
            </div>
            <div class="evon-stat-card-value">89</div>
            <div class="evon-stat-card-change evon-stat-card-change-positive">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
                <span>+8.3%</span>
            </div>
        </div>

        <div class="evon-stat-card">
            <div class="evon-stat-card-header">
                <span class="evon-stat-card-label">Transactions</span>
                <div class="evon-stat-card-icon">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
            <div class="evon-stat-card-value">5.4K</div>
            <div class="evon-stat-card-change evon-stat-card-change-positive">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
                <span>+23.1%</span>
            </div>
        </div>

        <div class="evon-stat-card">
            <div class="evon-stat-card-header">
                <span class="evon-stat-card-label">Revenus</span>
                <div class="evon-stat-card-icon">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="evon-stat-card-value">€12.5K</div>
            <div class="evon-stat-card-change evon-stat-card-change-positive">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
                <span>+15.7%</span>
            </div>
        </div>
    </div>

    {{-- Form Section --}}
    <div class="evon-form-section">
        <div class="evon-form-section-header">
            <h2 class="evon-form-section-title">Formulaire de Test</h2>
            <p class="evon-form-section-description">Testez les inputs optimisés pour mobile</p>
        </div>

        <form onsubmit="event.preventDefault(); alert('✅ Formulaire validé ! Le design mobile fonctionne parfaitement.');">
            <div class="form-group">
                <label for="demo-name">Nom complet</label>
                <input type="text" 
                       id="demo-name" 
                       name="name" 
                       class="input-field" 
                       placeholder="Jean Dupont"
                       required>
            </div>

            <div class="form-group">
                <label for="demo-email">Adresse email</label>
                <input type="email" 
                       id="demo-email" 
                       name="email" 
                       class="input-field" 
                       placeholder="jean.dupont@example.com"
                       required>
            </div>

            <div class="form-group">
                <label for="demo-phone">Téléphone</label>
                <input type="tel" 
                       id="demo-phone" 
                       name="phone" 
                       class="input-field" 
                       placeholder="+33 6 12 34 56 78">
            </div>

            <div class="form-group">
                <label for="demo-select">Catégorie</label>
                <select id="demo-select" name="category" class="input-field">
                    <option value="">Sélectionnez une option</option>
                    <option value="1">Utilisateur Standard</option>
                    <option value="2">Opérateur</option>
                    <option value="3">Administrateur</option>
                </select>
            </div>

            <div class="form-group">
                <label for="demo-message">Message</label>
                <textarea id="demo-message" 
                          name="message" 
                          class="input-field" 
                          rows="4"
                          placeholder="Votre message..."></textarea>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="btn-primary">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Enregistrer
                </button>
                
                <button type="button" class="btn-secondary" onclick="document.querySelector('form').reset()">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Annuler
                </button>
            </div>
        </form>
    </div>

    {{-- Table Demo --}}
    <div class="evon-table-container">
        <div class="evon-table-header">
            <h3 class="evon-table-title">Table Responsive</h3>
        </div>
        
        <div class="evon-table-wrapper">
            <table class="evon-table">
                <thead class="evon-table-head">
                    <tr>
                        <th class="evon-table-head-cell">Nom</th>
                        <th class="evon-table-head-cell">Email</th>
                        <th class="evon-table-head-cell">Statut</th>
                        <th class="evon-table-head-cell">Date</th>
                    </tr>
                </thead>
                <tbody class="evon-table-body">
                    <tr class="evon-table-row">
                        <td class="evon-table-cell" data-label="Nom">Jean Dupont</td>
                        <td class="evon-table-cell" data-label="Email">jean@example.com</td>
                        <td class="evon-table-cell" data-label="Statut">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                Actif
                            </span>
                        </td>
                        <td class="evon-table-cell" data-label="Date">21/12/2024</td>
                    </tr>
                    <tr class="evon-table-row">
                        <td class="evon-table-cell" data-label="Nom">Marie Martin</td>
                        <td class="evon-table-cell" data-label="Email">marie@example.com</td>
                        <td class="evon-table-cell" data-label="Statut">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                Actif
                            </span>
                        </td>
                        <td class="evon-table-cell" data-label="Date">21/12/2024</td>
                    </tr>
                    <tr class="evon-table-row">
                        <td class="evon-table-cell" data-label="Nom">Pierre Durand</td>
                        <td class="evon-table-cell" data-label="Email">pierre@example.com</td>
                        <td class="evon-table-cell" data-label="Statut">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                Inactif
                            </span>
                        </td>
                        <td class="evon-table-cell" data-label="Date">20/12/2024</td>
                    </tr>
                    <tr class="evon-table-row">
                        <td class="evon-table-cell" data-label="Nom">Sophie Bernard</td>
                        <td class="evon-table-cell" data-label="Email">sophie@example.com</td>
                        <td class="evon-table-cell" data-label="Statut">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                Actif
                            </span>
                        </td>
                        <td class="evon-table-cell" data-label="Date">19/12/2024</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Feature Cards --}}
    <div class="evon-chart-card">
        <div class="evon-chart-header">
            <h3 class="evon-chart-title">🎨 Caractéristiques du Design</h3>
        </div>
        
        <div class="space-y-4 text-sm">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-green-100 dark:bg-green-900 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-900 dark:text-white">Layout Centré</h4>
                    <p class="text-gray-600 dark:text-gray-400">Tout le contenu est parfaitement centré et aligné</p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-green-100 dark:bg-green-900 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-900 dark:text-white">Boutons Tactiles</h4>
                    <p class="text-gray-600 dark:text-gray-400">Minimum 44x44px pour un confort optimal</p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-green-100 dark:bg-green-900 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-900 dark:text-white">Formulaires Optimisés</h4>
                    <p class="text-gray-600 dark:text-gray-400">Font-size 16px pour éviter le zoom iOS</p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-green-100 dark:bg-green-900 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-900 dark:text-white">Animations Fluides</h4>
                    <p class="text-gray-600 dark:text-gray-400">60fps avec GPU acceleration</p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-green-100 dark:bg-green-900 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-900 dark:text-white">Dark Mode</h4>
                    <p class="text-gray-600 dark:text-gray-400">Support complet du mode sombre</p>
                </div>
            </div>

            <div class="flex items-start gap-3">
                <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-green-100 dark:bg-green-900 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-900 dark:text-white">Bottom Navigation</h4>
                    <p class="text-gray-600 dark:text-gray-400">Style iOS/Android natif avec FAB central</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Info Card --}}
    <div class="evon-alert-info">
        <div class="evon-alert-icon">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
        </div>
        <div class="evon-alert-content">
            <p class="font-medium">Documentation Complète</p>
            <p class="text-sm">Consultez <strong>MOBILE_DESIGN_GUIDE.md</strong> pour plus de détails sur le design mobile</p>
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex gap-3">
        <button onclick="alert('🎉 Le design mobile est maintenant actif sur toutes vos pages !')" class="btn-primary">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Tester le Design
        </button>
        
        <a href="{{ route('dashboard') }}" class="btn-secondary">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Retour Dashboard
        </a>
    </div>
</div>
@endsection

