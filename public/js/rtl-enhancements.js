/**
 * RTL Enhancements - JavaScript Helper
 * Améliore dynamiquement l'expérience RTL pour l'arabe
 * @version 1.0
 */

(function() {
    'use strict';
    
    // Détecter si nous sommes en mode RTL
    const isRTL = () => {
        return document.documentElement.getAttribute('dir') === 'rtl' ||
               document.documentElement.lang === 'ar';
    };
    
    // Initialiser les améliorations RTL
    const initRTLEnhancements = () => {
        if (!isRTL()) {
            return;
        }
        
        console.log('[RTL] Initialisation des améliorations RTL...');
        
        // 1. Ajuster les dropdowns
        adjustDropdowns();
        
        // 2. Ajuster les tooltips
        adjustTooltips();
        
        // 3. Ajuster les animations
        adjustAnimations();
        
        // 4. Ajuster les icônes directionnelles
        adjustDirectionalIcons();
        
        // 5. Observer les changements DOM
        observeDOMChanges();
        
        console.log('[RTL] Améliorations RTL activées ✓');
    };
    
    // Ajuster la position des dropdowns
    const adjustDropdowns = () => {
        const dropdowns = document.querySelectorAll('[x-show]');
        dropdowns.forEach(dropdown => {
            if (dropdown.classList.contains('absolute')) {
                // Inverser left/right
                if (dropdown.classList.contains('right-0')) {
                    dropdown.classList.remove('right-0');
                    dropdown.classList.add('left-0');
                } else if (dropdown.classList.contains('left-0')) {
                    dropdown.classList.remove('left-0');
                    dropdown.classList.add('right-0');
                }
            }
        });
    };
    
    // Ajuster les tooltips
    const adjustTooltips = () => {
        const tooltips = document.querySelectorAll('.evon-nav-item-tooltip, .evon-toggle-tooltip');
        tooltips.forEach(tooltip => {
            // Les tooltips sont déjà gérés par CSS, mais on peut ajouter des classes
            tooltip.setAttribute('data-rtl', 'true');
        });
    };
    
    // Ajuster les animations de sidebar
    const adjustAnimations = () => {
        const sidebar = document.querySelector('.evon-sidebar');
        if (sidebar) {
            // Ajouter une classe pour les animations RTL
            sidebar.classList.add('rtl-sidebar');
            
            // Ne forcer la position que sur mobile (< 1024px)
            const isMobile = window.innerWidth < 1024;
            if (isMobile) {
                // Forcer la position à droite sur mobile seulement
                sidebar.style.right = '0';
                sidebar.style.left = 'auto';
            } else {
                // Sur desktop, laisser le CSS gérer avec flex order
                sidebar.style.right = '';
                sidebar.style.left = '';
            }
            
            // Retirer tout margin inattendu
            sidebar.style.marginRight = '0';
            sidebar.style.marginLeft = '0';
            
            // Écouter les changements de sidebarOpen
            if (window.Alpine) {
                // Alpine.js est chargé
                const checkSidebar = setInterval(() => {
                    if (sidebar._x_dataStack && sidebar._x_dataStack[0]) {
                        clearInterval(checkSidebar);
                    }
                }, 100);
            }
        }
    };
    
    // Ajuster les icônes directionnelles (flèches, chevrons)
    const adjustDirectionalIcons = () => {
        const directionalIcons = document.querySelectorAll(
            '[data-lucide="chevron-left"], ' +
            '[data-lucide="chevron-right"], ' +
            '[data-lucide="arrow-left"], ' +
            '[data-lucide="arrow-right"]'
        );
        
        directionalIcons.forEach(icon => {
            if (!icon.hasAttribute('data-no-flip')) {
                icon.style.transform = 'scaleX(-1)';
            }
        });
        
        // Ajuster les SVG dans les breadcrumbs
        const breadcrumbs = document.querySelectorAll('nav.flex.items-center.gap-2 svg');
        breadcrumbs.forEach(svg => {
            if (!svg.hasAttribute('data-no-flip')) {
                svg.style.transform = 'scaleX(-1)';
            }
        });
    };
    
    // Observer les changements DOM pour réappliquer les ajustements
    const observeDOMChanges = () => {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.addedNodes.length) {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === 1) { // Element node
                            // Réappliquer les ajustements sur les nouveaux éléments
                            if (node.matches && node.matches('[x-show]')) {
                                adjustDropdowns();
                            }
                            if (node.matches && node.matches('[data-lucide]')) {
                                adjustDirectionalIcons();
                            }
                        }
                    });
                }
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    };
    
    // Fonction pour ajuster les stats cards
    const adjustStatsCards = () => {
        const statsCards = document.querySelectorAll('.evon-stat-card, .bg-white\\/10');
        statsCards.forEach(card => {
            card.setAttribute('dir', 'rtl');
        });
    };
    
    // Fonction pour ajuster les formulaires
    const adjustForms = () => {
        const inputs = document.querySelectorAll('input:not([type="checkbox"]):not([type="radio"]), textarea, select');
        inputs.forEach(input => {
            if (!input.hasAttribute('dir')) {
                input.setAttribute('dir', 'rtl');
            }
        });
    };
    
    // Fonction pour ajuster les tables
    const adjustTables = () => {
        const tables = document.querySelectorAll('.evon-table, table');
        tables.forEach(table => {
            table.setAttribute('dir', 'rtl');
            
            // Ajuster les cellules
            const cells = table.querySelectorAll('td, th');
            cells.forEach(cell => {
                if (!cell.style.textAlign) {
                    cell.style.textAlign = 'right';
                }
            });
        });
    };
    
    // Fonction pour corriger les marges auto
    const fixAutoMargins = () => {
        const elements = document.querySelectorAll('.ml-auto, .mr-auto');
        elements.forEach(el => {
            if (el.classList.contains('ml-auto')) {
                el.classList.remove('ml-auto');
                el.classList.add('mr-auto-rtl');
            } else if (el.classList.contains('mr-auto')) {
                el.classList.remove('mr-auto');
                el.classList.add('ml-auto-rtl');
            }
        });
    };
    
    // Fonction pour améliorer les modals
    const adjustModals = () => {
        const modals = document.querySelectorAll('.evon-modal-panel');
        modals.forEach(modal => {
            modal.setAttribute('dir', 'rtl');
        });
    };
    
    // Helper pour debug RTL
    const debugRTL = () => {
        if (localStorage.getItem('rtl_debug') === 'true') {
            console.log('[RTL DEBUG] Mode actif');
            
            // Ajouter des bordures colorées pour debug
            document.querySelectorAll('[dir="rtl"]').forEach(el => {
                el.style.outline = '1px solid rgba(255, 0, 0, 0.3)';
            });
        }
    };
    
    // Forcer la sidebar à droite en RTL (mobile uniquement)
    const forceSidebarRight = () => {
        const sidebar = document.querySelector('.evon-sidebar');
        if (!sidebar) return;
        
        // Vérifier si on est sur mobile
        const isMobile = () => window.innerWidth < 1024;
        
        // Appliquer la position seulement sur mobile
        const applySidebarPosition = () => {
            if (isMobile()) {
                // Mobile: forcer position fixe à droite
                sidebar.style.setProperty('right', '0', 'important');
                sidebar.style.setProperty('left', 'auto', 'important');
            } else {
                // Desktop: retirer les styles inline, laisser CSS/flex-order gérer
                sidebar.style.removeProperty('right');
                sidebar.style.removeProperty('left');
            }
        };
        
        // Appliquer initialement
        applySidebarPosition();
        
        // Réappliquer sur redimensionnement
        window.addEventListener('resize', applySidebarPosition);
        
        // Observer les changements de style seulement sur mobile
        const observer = new MutationObserver((mutations) => {
            if (isMobile()) {
                mutations.forEach((mutation) => {
                    if (mutation.attributeName === 'style') {
                        const right = sidebar.style.right;
                        const left = sidebar.style.left;
                        if (right !== '0px' && right !== '0' || left !== 'auto') {
                            sidebar.style.setProperty('right', '0', 'important');
                            sidebar.style.setProperty('left', 'auto', 'important');
                        }
                    }
                });
            }
        });
        
        observer.observe(sidebar, {
            attributes: true,
            attributeFilter: ['style']
        });
        
        console.log('[RTL] Sidebar positionnée correctement (responsive) ✓');
    };
    
    // Fonction principale d'initialisation
    const init = () => {
        if (!isRTL()) {
            console.log('[RTL] Mode LTR détecté, pas d\'améliorations nécessaires');
            return;
        }
        
        // Attendre que le DOM soit complètement chargé
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                initRTLEnhancements();
                adjustStatsCards();
                adjustForms();
                adjustTables();
                fixAutoMargins();
                adjustModals();
                forceSidebarRight();
                debugRTL();
            });
        } else {
            initRTLEnhancements();
            adjustStatsCards();
            adjustForms();
            adjustTables();
            fixAutoMargins();
            adjustModals();
            forceSidebarRight();
            debugRTL();
        }
        
        // Réappliquer après Alpine.js
        document.addEventListener('alpine:init', () => {
            console.log('[RTL] Alpine.js initialisé, réapplication des améliorations RTL');
            setTimeout(() => {
                adjustDropdowns();
                adjustTooltips();
                adjustDirectionalIcons();
                forceSidebarRight();
            }, 100);
        });
        
        // Gérer les changements de taille d'écran
        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                adjustAnimations();
                console.log('[RTL] Ajustements après redimensionnement');
            }, 250);
        });
        
        // Vérifier périodiquement (uniquement sur mobile)
        setInterval(() => {
            if (isRTL() && window.innerWidth < 1024) {
                const sidebar = document.querySelector('.evon-sidebar');
                if (sidebar && sidebar.style.position === 'fixed') {
                    sidebar.style.setProperty('right', '0', 'important');
                    sidebar.style.setProperty('left', 'auto', 'important');
                }
            }
        }, 1000);
    };
    
    // Exposer certaines fonctions globalement pour debug
    window.RTLEnhancements = {
        isRTL,
        reapply: () => {
            initRTLEnhancements();
            adjustStatsCards();
            adjustForms();
            adjustTables();
        },
        enableDebug: () => {
            localStorage.setItem('rtl_debug', 'true');
            debugRTL();
        },
        disableDebug: () => {
            localStorage.removeItem('rtl_debug');
            document.querySelectorAll('[dir="rtl"]').forEach(el => {
                el.style.outline = '';
            });
        }
    };
    
    // Démarrer
    init();
    
})();

// CSS Helpers dynamiques
const addDynamicRTLStyles = () => {
    if (document.documentElement.getAttribute('dir') !== 'rtl') {
        return;
    }
    
    const style = document.createElement('style');
    style.id = 'dynamic-rtl-styles';
    style.textContent = `
        /* Classes dynamiques RTL */
        .mr-auto-rtl {
            margin-right: 0 !important;
            margin-left: auto !important;
        }
        
        .ml-auto-rtl {
            margin-left: 0 !important;
            margin-right: auto !important;
        }
        
        /* Animation sidebar RTL */
        .rtl-sidebar {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* Debug mode */
        [data-rtl="true"] {
            position: relative;
        }
    `;
    
    document.head.appendChild(style);
};

// Ajouter les styles dynamiques
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', addDynamicRTLStyles);
} else {
    addDynamicRTLStyles();
}

console.log('[RTL] Script d\'améliorations RTL chargé ✓');

