/**
 * Correctif pour le conteneur principal fixe
 * Empêche le glissement vers la droite et maintient la position fixe
 */

(function() {
    'use strict';
    
    console.log('🔧 Correctif conteneur principal initialisé...');
    
    // Configuration
    const CONFIG = {
        sidebarWidth: 280,
        sidebarCollapsedWidth: 80,
        transitionDuration: 300,
        breakpoint: 1024
    };
    
    // Variables globales
    let isInitialized = false;
    let currentSidebarState = 'expanded';
    
    /**
     * Initialiser le correctif
     */
    function init() {
        if (isInitialized) return;
        
        console.log('🚀 Initialisation du correctif conteneur principal...');
        
        // Appliquer les corrections CSS
        applyCSSFixes();
        
        // Configurer les événements
        setupEventListeners();
        
        // Appliquer l'état initial
        applyInitialState();
        
        isInitialized = true;
        console.log('✅ Correctif conteneur principal initialisé');
    }
    
    /**
     * Appliquer les corrections CSS
     */
    function applyCSSFixes() {
        const style = document.createElement('style');
        style.id = 'main-content-fix-styles';
        style.textContent = `
            /* Layout fixe sans espace blanc */
            .layout-fixed {
                min-height: 100vh;
                background: #f8fafc;
                position: relative;
            }
            
            /* Main content - FIXE sans glissement */
            .main-fixed {
                min-height: 100vh;
                background: #f8fafc;
                width: 100%;
                position: relative;
                left: 0;
                transition: left ${CONFIG.transitionDuration}ms ease;
            }
            
            /* Desktop: Décaler le contenu pour la sidebar */
            @media (min-width: ${CONFIG.breakpoint}px) {
                .main-fixed {
                    left: ${CONFIG.sidebarWidth}px;
                    width: calc(100% - ${CONFIG.sidebarWidth}px);
                }
                
                .main-fixed.sidebar-collapsed {
                    left: ${CONFIG.sidebarCollapsedWidth}px;
                    width: calc(100% - ${CONFIG.sidebarCollapsedWidth}px);
                }
            }
            
            /* Mobile: Pas de décalage */
            @media (max-width: ${CONFIG.breakpoint - 1}px) {
                .main-fixed {
                    left: 0 !important;
                    width: 100% !important;
                }
            }
            
            /* Sidebar fixe */
            .sidebar-fixed {
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                width: ${CONFIG.sidebarWidth}px;
                background: white;
                border-right: 1px solid #e5e7eb;
                z-index: 50;
                transition: width ${CONFIG.transitionDuration}ms ease, left ${CONFIG.transitionDuration}ms ease;
            }
            
            .sidebar-fixed.collapsed {
                width: ${CONFIG.sidebarCollapsedWidth}px;
            }
            
            /* Mobile sidebar */
            @media (max-width: ${CONFIG.breakpoint - 1}px) {
                .sidebar-fixed {
                    transform: translateX(-100%);
                    transition: transform ${CONFIG.transitionDuration}ms ease;
                }
                
                .sidebar-fixed.open {
                    transform: translateX(0);
                }
            }
            
            /* Dark mode support */
            .dark .layout-fixed {
                background: #111827;
            }
            
            .dark .main-fixed {
                background: #111827;
            }
            
            .dark .sidebar-fixed {
                background: #1f2937;
                border-right-color: #374151;
            }
            
            /* Correction pour éviter les marges problématiques */
            .main-fixed * {
                box-sizing: border-box;
            }
            
            /* Assurer que le contenu ne déborde pas */
            .main-fixed .content-container {
                max-width: 100%;
                overflow-x: hidden;
            }
        `;
        
        // Supprimer l'ancien style s'il existe
        const existingStyle = document.getElementById('main-content-fix-styles');
        if (existingStyle) {
            existingStyle.remove();
        }
        
        document.head.appendChild(style);
        console.log('✅ Corrections CSS appliquées');
    }
    
    /**
     * Configurer les événements
     */
    function setupEventListeners() {
        // Écouter les changements de taille de fenêtre
        window.addEventListener('resize', handleResize);
        
        // Écouter les événements de toggle de sidebar
        window.addEventListener('sidebar-toggle', handleSidebarToggle);
        
        // Écouter les changements de state Alpine.js
        document.addEventListener('alpine:init', () => {
            console.log('🔧 Alpine.js détecté, configuration des événements...');
        });
        
        console.log('✅ Événements configurés');
    }
    
    /**
     * Gérer le redimensionnement de la fenêtre
     */
    function handleResize() {
        const isMobile = window.innerWidth < CONFIG.breakpoint;
        const mainElement = document.querySelector('.main-fixed');
        
        if (mainElement) {
            if (isMobile) {
                // Mobile: pas de décalage
                mainElement.style.left = '0';
                mainElement.style.width = '100%';
                mainElement.classList.remove('sidebar-collapsed');
            } else {
                // Desktop: appliquer le décalage selon l'état de la sidebar
                updateMainPosition();
            }
        }
    }
    
    /**
     * Gérer le toggle de la sidebar
     */
    function handleSidebarToggle() {
        console.log('🔄 Toggle sidebar détecté');
        updateMainPosition();
    }
    
    /**
     * Mettre à jour la position du contenu principal
     */
    function updateMainPosition() {
        const mainElement = document.querySelector('.main-fixed');
        const sidebarElement = document.querySelector('.sidebar-fixed');
        
        if (!mainElement || !sidebarElement) return;
        
        const isMobile = window.innerWidth < CONFIG.breakpoint;
        const isCollapsed = sidebarElement.classList.contains('collapsed');
        
        if (isMobile) {
            // Mobile: pas de décalage
            mainElement.style.left = '0';
            mainElement.style.width = '100%';
            mainElement.classList.remove('sidebar-collapsed');
        } else {
            // Desktop: décalage selon l'état de la sidebar
            if (isCollapsed) {
                mainElement.style.left = `${CONFIG.sidebarCollapsedWidth}px`;
                mainElement.style.width = `calc(100% - ${CONFIG.sidebarCollapsedWidth}px)`;
                mainElement.classList.add('sidebar-collapsed');
                currentSidebarState = 'collapsed';
            } else {
                mainElement.style.left = `${CONFIG.sidebarWidth}px`;
                mainElement.style.width = `calc(100% - ${CONFIG.sidebarWidth}px)`;
                mainElement.classList.remove('sidebar-collapsed');
                currentSidebarState = 'expanded';
            }
        }
        
        console.log(`📍 Position mise à jour: ${mainElement.style.left}, Largeur: ${mainElement.style.width}`);
    }
    
    /**
     * Appliquer l'état initial
     */
    function applyInitialState() {
        // Attendre que le DOM soit prêt
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                setTimeout(updateMainPosition, 100);
            });
        } else {
            setTimeout(updateMainPosition, 100);
        }
    }
    
    /**
     * Fonction utilitaire pour forcer la mise à jour
     */
    window.forceUpdateMainPosition = function() {
        console.log('🔄 Mise à jour forcée de la position...');
        updateMainPosition();
    };
    
    /**
     * Fonction utilitaire pour obtenir l'état actuel
     */
    window.getMainPositionState = function() {
        const mainElement = document.querySelector('.main-fixed');
        const sidebarElement = document.querySelector('.sidebar-fixed');
        
        if (!mainElement || !sidebarElement) {
            return { error: 'Éléments non trouvés' };
        }
        
        const computedStyle = window.getComputedStyle(mainElement);
        
        return {
            left: computedStyle.left,
            width: computedStyle.width,
            sidebarState: currentSidebarState,
            isMobile: window.innerWidth < CONFIG.breakpoint,
            isCollapsed: sidebarElement.classList.contains('collapsed')
        };
    };
    
    /**
     * Fonction utilitaire pour tester le correctif
     */
    window.testMainContentFix = function() {
        console.log('🧪 Test du correctif conteneur principal...');
        
        const state = window.getMainPositionState();
        console.log('État actuel:', state);
        
        // Test de basculement
        const sidebarElement = document.querySelector('.sidebar-fixed');
        if (sidebarElement) {
            const isCollapsed = sidebarElement.classList.contains('collapsed');
            sidebarElement.classList.toggle('collapsed');
            
            setTimeout(() => {
                const newState = window.getMainPositionState();
                console.log('État après toggle:', newState);
                
                // Restaurer l'état original
                if (isCollapsed) {
                    sidebarElement.classList.add('collapsed');
                } else {
                    sidebarElement.classList.remove('collapsed');
                }
                
                setTimeout(() => {
                    const finalState = window.getMainPositionState();
                    console.log('État final:', finalState);
                }, CONFIG.transitionDuration + 100);
            }, CONFIG.transitionDuration + 100);
        }
    };
    
    // Initialiser automatiquement
    init();
    
    console.log('✅ Correctif conteneur principal chargé');
})();
