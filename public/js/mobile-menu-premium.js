/**
 * ========================================
 * MOBILE MENU PREMIUM - JavaScript
 * Gestion du menu mobile avec swipe gestures
 * ========================================
 */

(function() {
    'use strict';
    
    // Debug mode - set to true for verbose logging
    const DEBUG = false;
    const log = DEBUG ? console.log.bind(console) : () => {};
    
    // État du menu
    let menuState = {
        isOpen: false,
        startX: 0,
        currentX: 0,
        isDragging: false
    };
    
    // Éléments DOM
    let elements = {
        body: null,
        sidebar: null,
        overlay: null,
        menuBtn: null,
        hamburger: null
    };
    
    /**
     * Initialisation
     */
    const init = () => {
        
        // Attendre le DOM
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', setup);
        } else {
            setup();
        }
    };
    
    /**
     * Setup des éléments et événements
     */
    const setup = () => {
        // Récupérer les éléments
        elements.body = document.body;
        elements.sidebar = document.querySelector('.evon-sidebar');
        elements.overlay = document.querySelector('.evon-mobile-sidebar-overlay');
        elements.menuBtn = document.querySelector('.evon-mobile-menu-btn');
        elements.hamburger = document.querySelector('.evon-hamburger');
        
        if (!elements.sidebar) {
            console.error('[Mobile Menu] Sidebar introuvable');
            return;
        }
        
        // Créer l'overlay s'il n'existe pas
        if (!elements.overlay) {
            createOverlay();
        }
        
        // Événements bouton menu
        if (elements.menuBtn) {
            elements.menuBtn.addEventListener('click', toggleMenu);
        }
        
        // Événements overlay
        if (elements.overlay) {
            elements.overlay.addEventListener('click', closeMenu);
        }
        
        // Swipe gestures
        setupSwipeGestures();
        
        // Keyboard shortcuts
        setupKeyboardShortcuts();
        
        // Fermer menu quand on clique sur un lien
        setupMenuLinks();
        
        log('[Mobile Menu] Initialisé ✓');
    };
    
    /**
     * Créer l'overlay
     */
    const createOverlay = () => {
        elements.overlay = document.createElement('div');
        elements.overlay.className = 'evon-mobile-sidebar-overlay';
        elements.overlay.addEventListener('click', closeMenu);
        document.body.appendChild(elements.overlay);
    };
    
    /**
     * Toggle menu
     */
    const toggleMenu = (e) => {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        if (menuState.isOpen) {
            closeMenu();
        } else {
            openMenu();
        }
    };
    
    /**
     * Ouvrir le menu
     */
    const openMenu = () => {
        menuState.isOpen = true;
        
        // Ajouter classes
        elements.body.classList.add('sidebar-open', 'menu-open');
        
        // Bloquer le scroll du body
        elements.body.style.overflow = 'hidden';
        
        // Accessibilité
        elements.sidebar.setAttribute('aria-hidden', 'false');
        if (elements.menuBtn) {
            elements.menuBtn.setAttribute('aria-expanded', 'true');
        }
        
        // Animation
        requestAnimationFrame(() => {
            if (elements.sidebar) {
                elements.sidebar.style.transform = 'translateX(0)';
            }
        });
        
        // Haptic feedback sur mobile
        if (navigator.vibrate) {
            navigator.vibrate(10);
        }
        
        log('[Mobile Menu] Ouvert');
    };
    
    /**
     * Fermer le menu
     */
    const closeMenu = () => {
        if (!menuState.isOpen) return;
        
        menuState.isOpen = false;
        
        // Retirer classes
        elements.body.classList.remove('sidebar-open', 'menu-open');
        
        // Débloquer le scroll
        elements.body.style.overflow = '';
        
        // Accessibilité
        elements.sidebar.setAttribute('aria-hidden', 'true');
        if (elements.menuBtn) {
            elements.menuBtn.setAttribute('aria-expanded', 'false');
        }
        
        // Reset transform
        const isRTL = document.documentElement.dir === 'rtl';
        if (elements.sidebar) {
            elements.sidebar.style.transform = isRTL ? 'translateX(100%)' : 'translateX(-100%)';
        }
        
        log('[Mobile Menu] Fermé');
    };
    
    /**
     * Setup swipe gestures
     */
    const setupSwipeGestures = () => {
        // Touch start
        elements.sidebar.addEventListener('touchstart', handleTouchStart, { passive: true });
        
        // Touch move
        elements.sidebar.addEventListener('touchmove', handleTouchMove, { passive: false });
        
        // Touch end
        elements.sidebar.addEventListener('touchend', handleTouchEnd, { passive: true });
        
        // Swipe depuis le bord pour ouvrir
        document.addEventListener('touchstart', handleEdgeSwipeStart, { passive: true });
        document.addEventListener('touchmove', handleEdgeSwipeMove, { passive: false });
        document.addEventListener('touchend', handleEdgeSwipeEnd, { passive: true });
    };
    
    /**
     * Touch start handler
     */
    const handleTouchStart = (e) => {
        if (!menuState.isOpen) return;
        
        menuState.startX = e.touches[0].clientX;
        menuState.isDragging = true;
    };
    
    /**
     * Touch move handler
     */
    const handleTouchMove = (e) => {
        if (!menuState.isDragging || !menuState.isOpen) return;
        
        menuState.currentX = e.touches[0].clientX;
        const diff = menuState.currentX - menuState.startX;
        const isRTL = document.documentElement.dir === 'rtl';
        
        // Glissement pour fermer (LTR: vers la gauche, RTL: vers la droite)
        if ((isRTL && diff > 0) || (!isRTL && diff < 0)) {
            const absValue = Math.abs(diff);
            const sidebarWidth = elements.sidebar.offsetWidth;
            
            if (absValue < sidebarWidth) {
                e.preventDefault();
                const progress = absValue / sidebarWidth;
                const translateValue = isRTL ? diff : diff;
                
                elements.sidebar.style.transform = `translateX(${translateValue}px)`;
                elements.overlay.style.opacity = 1 - progress;
            }
        }
    };
    
    /**
     * Touch end handler
     */
    const handleTouchEnd = () => {
        if (!menuState.isDragging) return;
        
        menuState.isDragging = false;
        
        const diff = Math.abs(menuState.currentX - menuState.startX);
        const threshold = elements.sidebar.offsetWidth * 0.3; // 30% de la largeur
        
        if (diff > threshold) {
            closeMenu();
        } else {
            // Retour à la position ouverte
            elements.sidebar.style.transform = 'translateX(0)';
            elements.overlay.style.opacity = '1';
        }
    };
    
    /**
     * Edge swipe start
     */
    let edgeSwipeStartX = 0;
    let isEdgeSwiping = false;
    
    const handleEdgeSwipeStart = (e) => {
        if (menuState.isOpen || window.innerWidth > 1023) return;
        
        const touch = e.touches[0];
        const isRTL = document.documentElement.dir === 'rtl';
        const edgeThreshold = 20; // 20px depuis le bord
        
        // Détecter swipe depuis le bord
        if ((isRTL && touch.clientX > window.innerWidth - edgeThreshold) ||
            (!isRTL && touch.clientX < edgeThreshold)) {
            edgeSwipeStartX = touch.clientX;
            isEdgeSwiping = true;
        }
    };
    
    /**
     * Edge swipe move
     */
    const handleEdgeSwipeMove = (e) => {
        if (!isEdgeSwiping || window.innerWidth > 1023) return;
        
        const touch = e.touches[0];
        const diff = touch.clientX - edgeSwipeStartX;
        const isRTL = document.documentElement.dir === 'rtl';
        
        // Mouvement suffisant pour afficher le menu
        if ((isRTL && diff < -30) || (!isRTL && diff > 30)) {
            e.preventDefault();
            
            // Pré-visualisation du menu
            const progress = Math.min(Math.abs(diff) / 100, 1);
            elements.sidebar.style.transition = 'none';
            elements.overlay.style.transition = 'none';
            
            const maxTranslate = elements.sidebar.offsetWidth;
            const translateValue = isRTL ? 
                maxTranslate - (progress * maxTranslate) : 
                -maxTranslate + (progress * maxTranslate);
            
            elements.sidebar.style.transform = `translateX(${translateValue}px)`;
            elements.overlay.style.opacity = progress * 0.5;
            elements.overlay.style.visibility = 'visible';
        }
    };
    
    /**
     * Edge swipe end
     */
    const handleEdgeSwipeEnd = (e) => {
        if (!isEdgeSwiping) return;
        
        isEdgeSwiping = false;
        
        // Restaurer transitions
        elements.sidebar.style.transition = '';
        elements.overlay.style.transition = '';
        
        const touch = e.changedTouches[0];
        const diff = Math.abs(touch.clientX - edgeSwipeStartX);
        
        if (diff > 80) {
            openMenu();
        } else {
            // Annuler
            const isRTL = document.documentElement.dir === 'rtl';
            elements.sidebar.style.transform = isRTL ? 'translateX(100%)' : 'translateX(-100%)';
            elements.overlay.style.opacity = '0';
            elements.overlay.style.visibility = 'hidden';
        }
    };
    
    /**
     * Setup keyboard shortcuts
     */
    const setupKeyboardShortcuts = () => {
        document.addEventListener('keydown', (e) => {
            // ESC pour fermer
            if (e.key === 'Escape' && menuState.isOpen) {
                closeMenu();
            }
            
            // Ctrl/Cmd + M pour toggle (mobile)
            if ((e.ctrlKey || e.metaKey) && e.key === 'm' && window.innerWidth <= 1023) {
                e.preventDefault();
                toggleMenu();
            }
        });
    };
    
    /**
     * Setup menu links
     */
    const setupMenuLinks = () => {
        // Fermer le menu quand on clique sur un lien
        const links = elements.sidebar.querySelectorAll('a:not([target="_blank"])');
        links.forEach(link => {
            link.addEventListener('click', () => {
                // Petit délai pour la navigation
                setTimeout(closeMenu, 300);
            });
        });
    };
    
    /**
     * Responsive handler
     */
    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            // Fermer le menu si on passe en desktop
            if (window.innerWidth > 1023 && menuState.isOpen) {
                closeMenu();
            }
        }, 250);
    });
    
    /**
     * API publique
     */
    window.MobileMenu = {
        open: openMenu,
        close: closeMenu,
        toggle: toggleMenu,
        isOpen: () => menuState.isOpen
    };
    
    // Démarrer
    init();
    
    log('[Mobile Menu] Script chargé ✓');
    
})();

/**
 * Integration avec Alpine.js
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('mobileMenu', () => ({
        isOpen: false,
        
        toggle() {
            if (window.MobileMenu) {
                window.MobileMenu.toggle();
                this.isOpen = window.MobileMenu.isOpen();
            }
        },
        
        close() {
            if (window.MobileMenu) {
                window.MobileMenu.close();
                this.isOpen = false;
            }
        }
    }));
});

