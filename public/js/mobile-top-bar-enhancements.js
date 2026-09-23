/**
 * Mobile Top Bar Enhancements
 * Améliore les interactions tactiles et l'expérience utilisateur sur mobile
 * 
 * Fonctionnalités :
 * - Gestion intelligente des dropdowns
 * - Détection de swipe pour fermer les menus
 * - Haptic feedback (si supporté)
 * - Prévention du scroll lors des dropdowns ouverts
 * - Amélioration de la performance
 */

(function() {
    'use strict';

    // Debug mode - set to true for verbose logging
    const DEBUG = false;
    const log = DEBUG ? console.log.bind(console) : () => {};

    // Détection de l'environnement mobile
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    const isTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;

    // Configuration
    const CONFIG = {
        swipeThreshold: 50, // Distance minimale pour un swipe (px)
        swipeTimeout: 300,  // Temps maximum pour un swipe (ms)
        hapticEnabled: true, // Activer le haptic feedback si disponible
        preventScrollOnOpen: true, // Empêcher le scroll quand un dropdown est ouvert
    };

    /**
     * Initialisation au chargement du DOM
     */
    function init() {
        if (!isMobile && !isTouch) {
            return;
        }

        // Attendre qu'Alpine soit chargé
        if (typeof Alpine !== 'undefined') {
            setupEnhancements();
        } else {
            document.addEventListener('alpine:init', setupEnhancements);
        }
    }

    /**
     * Configuration des améliorations
     */
    function setupEnhancements() {
        setupDropdownEnhancements();
        setupSwipeGestures();
        setupScrollPrevention();
        optimizeAnimations();
        
        log('[Mobile Top Bar] Mobile enhancements initialized');
    }

    /**
     * Amélioration des dropdowns
     */
    function setupDropdownEnhancements() {
        const dropdownButtons = document.querySelectorAll('.evon-header-utilities button[aria-expanded]');
        
        dropdownButtons.forEach(button => {
            // Ajouter un indicateur visuel au tap
            button.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.95)';
            }, { passive: true });

            button.addEventListener('touchend', function() {
                this.style.transform = '';
            }, { passive: true });

            // Améliorer le feedback visuel
            button.addEventListener('click', function() {
                triggerHaptic('light');
            });
        });
    }

    /**
     * Gestion des gestes de swipe pour fermer les dropdowns
     */
    function setupSwipeGestures() {
        const dropdowns = document.querySelectorAll('.evon-header-utilities [x-show]');
        
        dropdowns.forEach(dropdown => {
            let touchStartX = 0;
            let touchStartY = 0;
            let touchStartTime = 0;

            dropdown.addEventListener('touchstart', function(e) {
                touchStartX = e.touches[0].clientX;
                touchStartY = e.touches[0].clientY;
                touchStartTime = Date.now();
            }, { passive: true });

            dropdown.addEventListener('touchend', function(e) {
                const touchEndX = e.changedTouches[0].clientX;
                const touchEndY = e.changedTouches[0].clientY;
                const touchEndTime = Date.now();

                const deltaX = touchEndX - touchStartX;
                const deltaY = touchEndY - touchStartY;
                const deltaTime = touchEndTime - touchStartTime;

                // Swipe vers le haut pour fermer
                if (Math.abs(deltaY) > CONFIG.swipeThreshold && 
                    deltaY < 0 && 
                    deltaTime < CONFIG.swipeTimeout &&
                    Math.abs(deltaX) < Math.abs(deltaY)) {
                    
                    // Trouver le bouton parent et trigger la fermeture
                    const parent = dropdown.closest('[x-data]');
                    if (parent) {
                        // Déclencher l'événement Alpine pour fermer
                        const event = new CustomEvent('click', { bubbles: true });
                        document.dispatchEvent(event);
                        triggerHaptic('medium');
                    }
                }
            });
        });
    }

    /**
     * Haptic Feedback (iOS et Android modernes)
     */
    function triggerHaptic(intensity = 'light') {
        if (!CONFIG.hapticEnabled) return;

        // Vibration API (Android)
        if (navigator.vibrate) {
            const patterns = {
                light: 10,
                medium: 20,
                heavy: 50
            };
            navigator.vibrate(patterns[intensity] || 10);
        }

        // Haptic Engine (iOS via Taptic Engine)
        if (window.TapticEngine) {
            try {
                window.TapticEngine.impact(intensity);
            } catch (e) {
                log('[Mobile Top Bar] Haptic not available');
            }
        }
    }

    /**
     * Prévention du scroll lors de l'ouverture des dropdowns
     */
    function setupScrollPrevention() {
        if (!CONFIG.preventScrollOnOpen) return;

        // Observer les changements d'attributs aria-expanded
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'aria-expanded') {
                    const button = mutation.target;
                    const isExpanded = button.getAttribute('aria-expanded') === 'true';
                    
                    if (isExpanded) {
                        // Empêcher le scroll du body
                        document.body.style.overflow = 'hidden';
                        document.body.style.touchAction = 'none';
                    } else {
                        // Restaurer le scroll
                        document.body.style.overflow = '';
                        document.body.style.touchAction = '';
                    }
                }
            });
        });

        // Observer tous les boutons avec aria-expanded
        const buttons = document.querySelectorAll('.evon-header-utilities button[aria-expanded]');
        buttons.forEach(button => {
            observer.observe(button, { attributes: true });
        });
    }

    /**
     * Optimisation des animations pour mobile
     */
    function optimizeAnimations() {
        // Détecter les performances de l'appareil
        const isLowEndDevice = detectLowEndDevice();

        if (isLowEndDevice) {
            log('[Mobile Top Bar] Low-end device detected, optimizing animations');
            
            // Réduire la complexité des animations
            document.documentElement.style.setProperty('--animation-duration', '150ms');
            
            // Désactiver les animations complexes
            const style = document.createElement('style');
            style.textContent = `
                .evon-notification-badge {
                    animation: none !important;
                }
                .evon-header-button {
                    transition-duration: 100ms !important;
                }
            `;
            document.head.appendChild(style);
        }
    }

    /**
     * Détecter les appareils bas de gamme
     */
    function detectLowEndDevice() {
        // Vérifier le nombre de cœurs CPU
        const cores = navigator.hardwareConcurrency || 4;
        
        // Vérifier la mémoire disponible
        const memory = navigator.deviceMemory || 4;
        
        // Considéré comme bas de gamme si moins de 4 cœurs ou moins de 2GB RAM
        return cores < 4 || memory < 2;
    }

    /**
     * Amélioration de l'accessibilité tactile
     */
    function enhanceTouchAccessibility() {
        // Augmenter la zone de tap pour les petits éléments
        const smallButtons = document.querySelectorAll('.evon-header-button');
        
        smallButtons.forEach(button => {
            const rect = button.getBoundingClientRect();
            
            // Si le bouton est plus petit que 44px, ajouter un pseudo-élément invisible
            if (rect.width < 44 || rect.height < 44) {
                button.style.position = 'relative';
                button.style.setProperty('--min-touch-size', '44px');
                
                // Ajouter un style inline pour agrandir la zone de tap
                const style = document.createElement('style');
                style.textContent = `
                    .evon-header-button::after {
                        content: '';
                        position: absolute;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%);
                        min-width: var(--min-touch-size, 44px);
                        min-height: var(--min-touch-size, 44px);
                    }
                `;
                document.head.appendChild(style);
            }
        });
    }

    /**
     * Gestion intelligente des notifications
     */
    function setupNotificationEnhancements() {
        const notificationButton = document.querySelector('[aria-label*="notification" i], [title*="notification" i]');
        
        if (notificationButton) {
            // Ajouter un indicateur de chargement lors du clic
            notificationButton.addEventListener('click', function() {
                const badge = this.querySelector('.evon-notification-badge');
                if (badge) {
                    badge.style.animation = 'none';
                    setTimeout(() => {
                        badge.style.animation = '';
                    }, 10);
                }
            });
        }
    }

    /**
     * Amélioration du changeur de langue
     */
    function setupLanguageSwitcherEnhancements() {
        const langSwitcher = document.querySelector('[x-data*="language" i]');
        
        if (langSwitcher) {
            // Ajouter un feedback visuel lors du changement
            const langButtons = langSwitcher.querySelectorAll('button, a');
            
            langButtons.forEach(button => {
                button.addEventListener('click', function() {
                    triggerHaptic('medium');
                    
                    // Ajouter un indicateur de chargement
                    const originalContent = this.innerHTML;
                    this.innerHTML = '<span class="inline-block animate-spin">⚙</span>';
                    
                    setTimeout(() => {
                        this.innerHTML = originalContent;
                    }, 1000);
                });
            });
        }
    }

    /**
     * Gestion du theme switcher
     */
    function setupThemeSwitcherEnhancements() {
        const themeButton = document.querySelector('[x-data*="darkMode" i], [x-data*="theme" i]');
        
        if (themeButton) {
            themeButton.addEventListener('click', function() {
                triggerHaptic('light');
                
                // Ajouter une animation de rotation
                const icon = this.querySelector('svg');
                if (icon) {
                    icon.style.transform = 'rotate(360deg)';
                    icon.style.transition = 'transform 0.5s ease';
                    
                    setTimeout(() => {
                        icon.style.transform = '';
                    }, 500);
                }
            });
        }
    }

    /**
     * Debug et monitoring
     */
    function setupDebugMode() {
        // Activer avec localStorage
        if (localStorage.getItem('mobileTopBarDebug') === 'true') {
            log('[Mobile Top Bar] Debug mode enabled');
            
            // Logger toutes les interactions
            document.addEventListener('touchstart', function(e) {
                console.log('[Touch]', e.target);
            }, { passive: true });

            document.addEventListener('click', function(e) {
                console.log('[Click]', e.target);
            });

            // Afficher les zones tactiles
            const style = document.createElement('style');
            style.textContent = `
                .evon-header-button {
                    outline: 2px dashed red !important;
                    outline-offset: 2px;
                }
            `;
            document.head.appendChild(style);
        }
    }

    /**
     * Performance monitoring
     */
    function monitorPerformance() {
        // Mesurer le temps de réponse des interactions
        let interactionStart = 0;

        document.addEventListener('touchstart', function() {
            interactionStart = performance.now();
        }, { passive: true });

        document.addEventListener('touchend', function() {
            const duration = performance.now() - interactionStart;
            
            if (duration > 100) {
                console.warn('[Mobile Top Bar] Slow interaction detected:', duration + 'ms');
            }
        }, { passive: true });
    }

    /**
     * Nettoyage et désactivation
     */
    function cleanup() {
        // Restaurer le comportement par défaut
        document.body.style.overflow = '';
        document.body.style.touchAction = '';
        
        log('[Mobile Top Bar] Cleanup completed');
    }

    // Initialisation au chargement
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Réinitialiser si Alpine se recharge
    document.addEventListener('alpine:initialized', function() {
        log('[Mobile Top Bar] Alpine initialized, setting up enhancements');
        setupNotificationEnhancements();
        setupLanguageSwitcherEnhancements();
        setupThemeSwitcherEnhancements();
        enhanceTouchAccessibility();
    });

    // Nettoyage avant déchargement (utilise l'API moderne pagehide)
    if (window.PageLifecycle) {
        window.PageLifecycle.registerCleanup(cleanup, 'mobile-top-bar-cleanup');
    } else {
        // Fallback si PageLifecycle n'est pas chargé
        window.addEventListener('pagehide', cleanup, { capture: true });
    }

    // Export pour debug
    window.MobileTopBarEnhancements = {
        version: '1.0.0',
        config: CONFIG,
        triggerHaptic: triggerHaptic,
        detectLowEndDevice: detectLowEndDevice,
        enableDebug: function() {
            localStorage.setItem('mobileTopBarDebug', 'true');
            log('[Mobile Top Bar] Debug mode enabled, reload the page');
        },
        disableDebug: function() {
            localStorage.removeItem('mobileTopBarDebug');
            log('[Mobile Top Bar] Debug mode disabled');
        }
    };

    log('[Mobile Top Bar] Script loaded successfully');
})();

