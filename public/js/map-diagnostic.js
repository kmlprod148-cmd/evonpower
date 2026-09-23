/**
 * Script de diagnostic pour identifier les problèmes de chargement de la carte
 */

(function() {
    'use strict';
    
    console.log('🔍 DIAGNOSTIC CARTE - Analyse des problèmes...');
    
    // Fonction de diagnostic complète
    function runFullDiagnostic() {
        console.group('🔍 DIAGNOSTIC COMPLET DE LA CARTE');
        
        // 1. Vérifier les éléments DOM
        console.log('📋 Vérification des éléments DOM:');
        const mapContainer = document.getElementById('map');
        const mapLoading = document.getElementById('map-loading');
        const mapPlaceholder = document.getElementById('map-placeholder');
        const searchField = document.getElementById('location_search');
        
        console.log('  - Conteneur de carte:', !!mapContainer);
        console.log('  - Élément de chargement:', !!mapLoading);
        console.log('  - Élément placeholder:', !!mapPlaceholder);
        console.log('  - Champ de recherche:', !!searchField);
        
        if (mapContainer) {
            console.log('  - Dimensions conteneur:', mapContainer.offsetWidth + 'x' + mapContainer.offsetHeight);
            console.log('  - Contenu conteneur:', mapContainer.innerHTML.length + ' caractères');
        }
        
        // 2. Vérifier Leaflet
        console.log('🗺️ Vérification de Leaflet:');
        console.log('  - Leaflet disponible:', typeof L !== 'undefined');
        if (typeof L !== 'undefined') {
            console.log('  - Version Leaflet:', L.version);
        }
        
        // 3. Vérifier la solution unifiée
        console.log('🔧 Vérification de la solution unifiée:');
        console.log('  - Solution disponible:', typeof window.unifiedMapSolution !== 'undefined');
        if (window.unifiedMapSolution) {
            console.log('  - Carte initialisée:', window.unifiedMapSolution.isInitialized());
        }
        
        // 4. Vérifier les scripts chargés
        console.log('📜 Vérification des scripts:');
        const scripts = document.querySelectorAll('script[src*="map"], script[src*="leaflet"]');
        console.log('  - Scripts de carte chargés:', scripts.length);
        scripts.forEach((script, index) => {
            console.log(`    ${index + 1}. ${script.src}`);
        });
        
        // 5. Vérifier les styles
        console.log('🎨 Vérification des styles:');
        const leafletCSS = document.querySelector('link[href*="leaflet"]');
        console.log('  - CSS Leaflet chargé:', !!leafletCSS);
        
        // 6. Vérifier les erreurs de console
        console.log('❌ Vérification des erreurs:');
        const originalError = console.error;
        const errors = [];
        console.error = function(...args) {
            errors.push(args.join(' '));
            originalError.apply(console, args);
        };
        
        // 7. Test de connectivité
        console.log('🌐 Test de connectivité:');
        fetch('https://nominatim.openstreetmap.org/search?format=json&q=test&limit=1')
            .then(response => {
                console.log('  - API Nominatim accessible:', response.ok);
                return response.json();
            })
            .then(data => {
                console.log('  - Réponse API:', data.length > 0 ? 'Données reçues' : 'Aucune donnée');
            })
            .catch(error => {
                console.log('  - Erreur API:', error.message);
            });
        
        console.groupEnd();
        
        // Afficher un résumé
        displayDiagnosticSummary();
    }
    
    // Fonction d'affichage du résumé
    function displayDiagnosticSummary() {
        const issues = [];
        const solutions = [];
        
        // Vérifier les problèmes courants
        if (document.getElementById('map-loading') && document.getElementById('map-loading').style.display !== 'none') {
            issues.push('Éléments de chargement visibles');
            solutions.push('Exécuter: window.hideMapLoadingElements()');
        }
        
        if (typeof L === 'undefined') {
            issues.push('Leaflet non chargé');
            solutions.push('Vérifier la connexion internet et recharger la page');
        }
        
        if (!window.unifiedMapSolution) {
            issues.push('Solution unifiée non disponible');
            solutions.push('Vérifier que unified-map-solution.js est chargé');
        }
        
        if (issues.length === 0) {
            console.log('✅ Aucun problème détecté');
        } else {
            console.log('⚠️ Problèmes détectés:');
            issues.forEach((issue, index) => {
                console.log(`  ${index + 1}. ${issue}`);
            });
            console.log('🔧 Solutions suggérées:');
            solutions.forEach((solution, index) => {
                console.log(`  ${index + 1}. ${solution}`);
            });
        }
    }
    
    // Fonction de correction automatique
    function autoFix() {
        console.log('🔧 Tentative de correction automatique...');
        
        // Masquer les éléments de chargement
        if (typeof window.hideMapLoadingElements === 'function') {
            window.hideMapLoadingElements();
            console.log('✅ Éléments de chargement masqués');
        }
        
        // Forcer le chargement de la carte
        if (window.unifiedMapSolution && typeof window.unifiedMapSolution.forceMapLoad === 'function') {
            window.unifiedMapSolution.forceMapLoad();
            console.log('✅ Chargement de carte forcé');
        }
        
        // Afficher un message de statut
        const statusElement = document.getElementById('location_status_text');
        const statusContainer = document.getElementById('location_status');
        
        if (statusElement && statusContainer) {
            statusElement.textContent = 'Correction automatique appliquée';
            statusContainer.classList.remove('hidden');
            statusContainer.className = 'mt-2 text-xs text-green-500';
        }
    }
    
    // Exposer les fonctions globalement
    window.mapDiagnostic = {
        runFullDiagnostic,
        autoFix,
        displayDiagnosticSummary
    };
    
    // Auto-exécution du diagnostic
    setTimeout(() => {
        runFullDiagnostic();
    }, 1000);
    
    console.log('✅ Diagnostic initialisé');
})();
