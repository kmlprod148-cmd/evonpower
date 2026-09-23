/**
 * Script de suppression forcée des éléments de chargement
 * S'exécute immédiatement pour supprimer tous les éléments de chargement
 */

(function() {
    'use strict';
    
    console.log('🗑️ SUPPRESSION FORCÉE DES ÉLÉMENTS DE CHARGEMENT...');
    
    // Fonction de suppression immédiate
    function forceRemoveLoadingElements() {
        console.log('🔧 Suppression forcée en cours...');
        
        // Supprimer l'élément de chargement
        const mapLoading = document.getElementById('map-loading');
        if (mapLoading) {
            mapLoading.remove();
            console.log('✅ Élément map-loading supprimé');
        }
        
        // Supprimer l'élément placeholder
        const mapPlaceholder = document.getElementById('map-placeholder');
        if (mapPlaceholder) {
            mapPlaceholder.remove();
            console.log('✅ Élément map-placeholder supprimé');
        }
        
        // Supprimer tous les éléments avec des classes de chargement
        const loadingElements = document.querySelectorAll('.animate-spin, .loading, [class*="loading"]');
        loadingElements.forEach(element => {
            if (element.closest('#map')) {
                element.remove();
                console.log('✅ Élément de chargement supprimé');
            }
        });
        
        // Supprimer tous les textes de chargement
        const loadingTexts = document.querySelectorAll('*');
        loadingTexts.forEach(element => {
            if (element.textContent && element.textContent.includes('Chargement de la carte')) {
                element.remove();
                console.log('✅ Texte de chargement supprimé');
            }
        });
        
        // Nettoyer le conteneur de carte
        const mapContainer = document.getElementById('map');
        if (mapContainer) {
            // Supprimer tous les enfants sauf ceux qui ne sont pas des éléments de chargement
            const children = Array.from(mapContainer.children);
            children.forEach(child => {
                if (child.id === 'map-loading' || 
                    child.id === 'map-placeholder' || 
                    child.classList.contains('loading') ||
                    child.textContent.includes('Chargement')) {
                    child.remove();
                    console.log('✅ Élément de chargement supprimé du conteneur');
                }
            });
            
            // S'assurer que le conteneur est propre
            mapContainer.style.background = '#f8f9fa';
            mapContainer.style.minHeight = '320px';
            console.log('✅ Conteneur de carte nettoyé');
        }
        
        console.log('✅ Suppression forcée terminée');
    }
    
    // Exécution immédiate
    forceRemoveLoadingElements();
    
    // Exécution après le chargement du DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', forceRemoveLoadingElements);
    } else {
        forceRemoveLoadingElements();
    }
    
    // Exécution après un délai pour s'assurer que tout est supprimé
    setTimeout(forceRemoveLoadingElements, 100);
    setTimeout(forceRemoveLoadingElements, 500);
    setTimeout(forceRemoveLoadingElements, 1000);
    
    // Observer les changements pour supprimer immédiatement tout nouvel élément de chargement
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1) { // Element node
                    if (node.id === 'map-loading' || 
                        node.id === 'map-placeholder' ||
                        node.classList.contains('loading') ||
                        (node.textContent && node.textContent.includes('Chargement'))) {
                        node.remove();
                        console.log('✅ Nouvel élément de chargement supprimé automatiquement');
                    }
                }
            });
        });
    });
    
    // Observer le conteneur de carte
    const mapContainer = document.getElementById('map');
    if (mapContainer) {
        observer.observe(mapContainer, {
            childList: true,
            subtree: true
        });
    }
    
    console.log('✅ Script de suppression forcée initialisé');
})();
