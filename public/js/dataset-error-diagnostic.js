/**
 * Script de diagnostic pour l'erreur "Cannot read properties of null (reading 'dataset')"
 * Identifie la source exacte de l'erreur et fournit des informations de debug
 */

(function() {
    'use strict';
    
    console.log('🔍 Initialisation du diagnostic dataset...');
    
    // Intercepter toutes les erreurs pour les analyser
    window.addEventListener('error', function(event) {
        if (event.error && event.error.message && event.error.message.includes("Cannot read properties of null (reading 'dataset')")) {
            console.group('🔍 DIAGNOSTIC ERREUR DATASET');
            console.error('Message:', event.error.message);
            console.error('Fichier:', event.filename);
            console.error('Ligne:', event.lineno);
            console.error('Colonne:', event.colno);
            console.error('Stack trace:', event.error.stack);
            
            // Analyser le contexte
            console.log('🔍 Analyse du contexte:');
            console.log('- Document ready state:', document.readyState);
            console.log('- Éléments avec dataset:', document.querySelectorAll('[data-*]').length);
            console.log('- Éléments null potentiels:', document.querySelectorAll('*').length);
            
            // Vérifier les éléments problématiques courants
            const problematicSelectors = [
                '[data-dialog]',
                '[data-popover]',
                '[data-anchor]',
                '[data-sort]',
                '[data-toggle]',
                '[data-target]'
            ];
            
            problematicSelectors.forEach(selector => {
                const elements = document.querySelectorAll(selector);
                console.log(`- Éléments ${selector}:`, elements.length);
                elements.forEach((el, index) => {
                    if (index < 3) { // Limiter l'affichage
                        console.log(`  [${index}]`, el, 'dataset:', el.dataset);
                    }
                });
            });
            
            console.groupEnd();
        }
    });
    
    // Intercepter les erreurs de promesses
    window.addEventListener('unhandledrejection', function(event) {
        if (event.reason && event.reason.message && event.reason.message.includes("Cannot read properties of null (reading 'dataset')")) {
            console.group('🔍 DIAGNOSTIC PROMESSE DATASET');
            console.error('Raison:', event.reason);
            console.error('Stack trace:', event.reason.stack);
            console.groupEnd();
        }
    });
    
    // Fonction de diagnostic des éléments
    window.diagnoseDatasetElements = function() {
        console.group('🔍 DIAGNOSTIC ÉLÉMENTS DATASET');
        
        // Vérifier tous les éléments avec des attributs data
        const elementsWithData = document.querySelectorAll('[data-*]');
        console.log('Éléments avec attributs data:', elementsWithData.length);
        
        // Analyser chaque élément
        elementsWithData.forEach((element, index) => {
            if (index < 10) { // Limiter l'affichage
                console.log(`[${index}]`, element.tagName, element.className, element.dataset);
            }
        });
        
        // Vérifier les éléments potentiellement null
        const nullCandidates = [
            document.querySelector('[data-dialog]'),
            document.querySelector('[data-popover]'),
            document.querySelector('[data-anchor]'),
            document.querySelector('[data-sort]'),
            document.querySelector('[data-toggle]'),
            document.querySelector('[data-target]')
        ];
        
        console.log('Candidats null:');
        nullCandidates.forEach((element, index) => {
            console.log(`[${index}]`, element ? 'OK' : 'NULL');
        });
        
        console.groupEnd();
    };
    
    // Exécuter le diagnostic après le chargement du DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(window.diagnoseDatasetElements, 1000);
        });
    } else {
        setTimeout(window.diagnoseDatasetElements, 1000);
    }
    
    console.log('✅ Diagnostic dataset initialisé');
})();
