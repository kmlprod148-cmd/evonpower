/**
 * ========================================
 * EVON MOBILE INTERACTIONS
 * Script d'amélioration des interactions mobiles
 * Design par un développeur Laravel Senior
 * ========================================
 */

(function() {
    'use strict';

    // Debug mode - set to true for verbose logging
    const DEBUG = false;
    const log = DEBUG ? console.log.bind(console) : () => {};

    // ========================================
    // 1. DÉTECTION DE L'ENVIRONNEMENT
    // ========================================

    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    const isIOS = /iPhone|iPad|iPod/i.test(navigator.userAgent);
    const isAndroid = /Android/i.test(navigator.userAgent);
    const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;

    // ========================================
    // 2. VIEWPORT HEIGHT FIX (iOS)
    // ========================================

    function setViewportHeight() {
        // Fix pour le 100vh sur iOS qui inclut la barre d'URL
        const vh = window.innerHeight * 0.01;
        document.documentElement.style.setProperty('--vh', `${vh}px`);
        
        // Update aussi la propriété CSS custom
        document.documentElement.style.setProperty('--real-viewport-height', `${window.innerHeight}px`);
    }

    // Initialiser au chargement
    setViewportHeight();

    // Mettre à jour lors du resize (orientation change, keyboard open/close)
    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(setViewportHeight, 100);
    });

    // ========================================
    // 3. SMOOTH SCROLL POUR ANCRES
    // ========================================

    document.addEventListener('click', (e) => {
        const link = e.target.closest('a[href^="#"]');
        if (link && link.hash) {
            const targetId = link.hash.slice(1);
            const target = document.getElementById(targetId);
            
            if (target) {
                e.preventDefault();
                
                // Smooth scroll
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });

                // Update URL sans déclencher le jump
                if (history.pushState) {
                    history.pushState(null, null, link.hash);
                }
            }
        }
    });

    // ========================================
    // 4. HAPTIC FEEDBACK (iOS)
    // ========================================

    function hapticFeedback(style = 'light') {
        if (window.navigator && window.navigator.vibrate) {
            // Android vibration
            const duration = {
                'light': 10,
                'medium': 20,
                'heavy': 30
            }[style] || 10;
            
            window.navigator.vibrate(duration);
        }
        
        // iOS Haptic (via WebKit)
        if (window.webkit && window.webkit.messageHandlers && window.webkit.messageHandlers.haptic) {
            window.webkit.messageHandlers.haptic.postMessage(style);
        }
    }

    // Ajouter le feedback aux boutons
    document.addEventListener('click', (e) => {
        const button = e.target.closest('button, .btn-primary, .btn-secondary, .evon-mobile-bottom-nav-item');
        if (button && !button.disabled) {
            hapticFeedback('light');
        }
    }, { passive: true });

    // ========================================
    // 5. PULL TO REFRESH (optionnel)
    // ========================================

    let startY = 0;
    let currentY = 0;
    let pulling = false;
    const pullThreshold = 80;

    function initPullToRefresh() {
        const main = document.querySelector('.evon-main');
        if (!main) return;

        main.addEventListener('touchstart', (e) => {
            if (main.scrollTop === 0) {
                startY = e.touches[0].clientY;
                pulling = true;
            }
        }, { passive: true });

        main.addEventListener('touchmove', (e) => {
            if (!pulling) return;
            
            currentY = e.touches[0].clientY;
            const diff = currentY - startY;

            if (diff > 0 && diff < pullThreshold) {
                // Afficher un indicateur visuel (optionnel)
                main.style.transform = `translateY(${diff * 0.5}px)`;
            }
        }, { passive: true });

        main.addEventListener('touchend', () => {
            if (!pulling) return;
            
            const diff = currentY - startY;
            
            if (diff > pullThreshold) {
                // Déclencher le refresh
                log('[MOBILE-INTERACTIONS] Pull to refresh triggered');
                window.location.reload();
            } else {
                // Reset
                main.style.transform = '';
            }
            
            pulling = false;
            startY = 0;
            currentY = 0;
        });
    }

    // Initialiser le pull to refresh sur mobile uniquement
    if (isMobile && window.innerWidth < 1024) {
        // Désactivé par défaut - décommenter pour activer
        // initPullToRefresh();
    }

    // ========================================
    // 6. KEYBOARD MANAGEMENT (iOS)
    // ========================================

    let originalViewportHeight = window.innerHeight;

    function handleKeyboardOpen() {
        const activeElement = document.activeElement;
        
        if (activeElement && (activeElement.tagName === 'INPUT' || activeElement.tagName === 'TEXTAREA')) {
            // Scroll vers l'élément actif après un délai
            setTimeout(() => {
                activeElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }, 300);

            // Cacher le bottom nav quand le clavier est ouvert
            const bottomNav = document.querySelector('.evon-mobile-bottom-nav');
            if (bottomNav) {
                bottomNav.style.transform = 'translateY(100%)';
            }
        }
    }

    function handleKeyboardClose() {
        // Restaurer le bottom nav
        const bottomNav = document.querySelector('.evon-mobile-bottom-nav');
        if (bottomNav) {
            bottomNav.style.transform = '';
        }
    }

    // Détecter l'ouverture/fermeture du clavier
    if (isIOS) {
        document.addEventListener('focusin', (e) => {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                handleKeyboardOpen();
            }
        });

        document.addEventListener('focusout', () => {
            handleKeyboardClose();
        });
    }

    // ========================================
    // 7. SWIPE GESTURES (optionnel)
    // ========================================

    let touchStartX = 0;
    let touchEndX = 0;
    let touchStartY = 0;
    let touchEndY = 0;

    function handleSwipe() {
        const diffX = touchEndX - touchStartX;
        const diffY = touchEndY - touchStartY;
        const absX = Math.abs(diffX);
        const absY = Math.abs(diffY);

        // Swipe horizontal dominant
        if (absX > absY && absX > 50) {
            if (diffX > 0) {
                // Swipe right - Ouvrir sidebar
                log('[MOBILE-INTERACTIONS] Swipe right detected');
                if (window.Alpine && document.body.__x) {
                    document.body.__x.$data.sidebarOpen = true;
                }
            } else {
                // Swipe left - Fermer sidebar
                log('[MOBILE-INTERACTIONS] Swipe left detected');
                if (window.Alpine && document.body.__x) {
                    document.body.__x.$data.sidebarOpen = false;
                }
            }
        }
    }

    document.addEventListener('touchstart', (e) => {
        touchStartX = e.changedTouches[0].screenX;
        touchStartY = e.changedTouches[0].screenY;
    }, { passive: true });

    document.addEventListener('touchend', (e) => {
        touchEndX = e.changedTouches[0].screenX;
        touchEndY = e.changedTouches[0].screenY;
        
        // Seulement si on swipe depuis le bord
        if (touchStartX < 50 || touchStartX > window.innerWidth - 50) {
            handleSwipe();
        }
    }, { passive: true });

    // ========================================
    // 8. PRÉVENTION DU ZOOM DOUBLE-TAP (iOS)
    // ========================================

    let lastTouchEnd = 0;
    document.addEventListener('touchend', (e) => {
        const now = Date.now();
        if (now - lastTouchEnd <= 300) {
            e.preventDefault();
        }
        lastTouchEnd = now;
    }, { passive: false });

    // ========================================
    // 9. OPTIMISATION DU SCROLL
    // ========================================

    let ticking = false;
    let lastScrollY = window.scrollY;

    function updateScrollState() {
        const currentScrollY = window.scrollY;
        const header = document.querySelector('.evon-header');
        const bottomNav = document.querySelector('.evon-mobile-bottom-nav');

        if (header && bottomNav && window.innerWidth < 1024) {
            if (currentScrollY > lastScrollY && currentScrollY > 100) {
                // Scroll down - Cacher header et bottom nav
                header.style.transform = 'translateY(-100%)';
                bottomNav.style.transform = 'translateY(100%)';
            } else {
                // Scroll up - Afficher header et bottom nav
                header.style.transform = '';
                bottomNav.style.transform = '';
            }
        }

        lastScrollY = currentScrollY;
        ticking = false;
    }

    function onScroll() {
        if (!ticking) {
            window.requestAnimationFrame(updateScrollState);
            ticking = true;
        }
    }

    // Activer le hide/show du header au scroll (optionnel)
    // Décommenter pour activer
    // window.addEventListener('scroll', onScroll, { passive: true });

    // ========================================
    // 10. AMÉLIORATION DES FORMULAIRES
    // ========================================

    function enhanceForms() {
        const inputs = document.querySelectorAll('input, textarea, select');
        
        inputs.forEach(input => {
            // Auto-focus sur le premier input des modals
            if (input.closest('.evon-modal-panel')) {
                const modal = input.closest('.evon-modal-panel');
                const observer = new MutationObserver((mutations) => {
                    mutations.forEach((mutation) => {
                        if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                            if (!modal.classList.contains('hidden')) {
                                setTimeout(() => input.focus(), 300);
                                observer.disconnect();
                            }
                        }
                    });
                });
                
                observer.observe(modal, { attributes: true });
            }

            // Amélioration du placeholder
            if (input.placeholder) {
                input.addEventListener('focus', () => {
                    input.dataset.placeholder = input.placeholder;
                    input.placeholder = '';
                });

                input.addEventListener('blur', () => {
                    if (input.dataset.placeholder) {
                        input.placeholder = input.dataset.placeholder;
                    }
                });
            }
        });
    }

    // ========================================
    // 11. GESTION DES IMAGES LAZY LOADING
    // ========================================

    function setupLazyLoading() {
        if ('loading' in HTMLImageElement.prototype) {
            // Support natif
            const images = document.querySelectorAll('img[data-src]');
            images.forEach(img => {
                img.src = img.dataset.src;
            });
        } else {
            // Fallback avec Intersection Observer
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        observer.unobserve(img);
                    }
                });
            });

            const images = document.querySelectorAll('img[data-src]');
            images.forEach(img => imageObserver.observe(img));
        }
    }

    // ========================================
    // 12. ORIENTATION CHANGE HANDLER
    // ========================================

    let lastOrientation = window.innerWidth > window.innerHeight ? 'landscape' : 'portrait';
    let orientationChangeTimer = null;

    function handleOrientationChange() {
        // Détecter le changement réel d'orientation
        const currentOrientation = window.innerWidth > window.innerHeight ? 'landscape' : 'portrait';
        
        // Ne rien faire si l'orientation n'a pas changé
        if (currentOrientation === lastOrientation) {
            return;
        }
        
        lastOrientation = currentOrientation;
        
        log('[MOBILE-INTERACTIONS] Orientation changed:', currentOrientation);
        
        // Ajuster le layout
        setViewportHeight();
        
        // Fermer la sidebar en mode landscape si elle est ouverte
        if (currentOrientation === 'landscape' && window.Alpine && document.body.__x) {
            document.body.__x.$data.sidebarOpen = false;
        }
    }

    // Debounced handler pour éviter les appels multiples
    function debouncedOrientationChange() {
        clearTimeout(orientationChangeTimer);
        orientationChangeTimer = setTimeout(handleOrientationChange, 150);
    }

    // Utiliser uniquement orientationchange (plus fiable)
    window.addEventListener('orientationchange', handleOrientationChange);
    // Fallback pour les navigateurs qui ne supportent pas orientationchange
    if (!('onorientationchange' in window)) {
        window.addEventListener('resize', debouncedOrientationChange);
    }

    // ========================================
    // 13. PERFORMANCE MONITORING
    // ========================================

    function logPerformance() {
        if (window.performance && window.performance.timing) {
            const timing = window.performance.timing;
            const loadTime = timing.loadEventEnd - timing.navigationStart;
            const domReady = timing.domContentLoadedEventEnd - timing.navigationStart;
            
            log('[MOBILE-INTERACTIONS] Performance:', {
                'Load Time': `${loadTime}ms`,
                'DOM Ready': `${domReady}ms`,
                'Mobile': isMobile,
                'Touch Device': isTouchDevice
            });
        }
    }

    // ========================================
    // 14. NETWORK STATUS
    // ========================================

    function updateOnlineStatus() {
        const isOnline = navigator.onLine;
        const statusBar = document.querySelector('.network-status-bar');
        
        if (!isOnline) {
            console.warn('[MOBILE-INTERACTIONS] Offline detected');
            // Afficher un message (optionnel)
            if (!statusBar) {
                const bar = document.createElement('div');
                bar.className = 'network-status-bar';
                bar.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    background: #ef4444;
                    color: white;
                    text-align: center;
                    padding: 8px;
                    font-size: 14px;
                    font-weight: 600;
                    z-index: 10000;
                `;
                bar.textContent = '📡 Pas de connexion internet';
                document.body.prepend(bar);
            }
        } else {
            if (statusBar) {
                statusBar.remove();
            }
        }
    }

    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);

    // ========================================
    // 15. INITIALISATION
    // ========================================

    function init() {
        log('[MOBILE-INTERACTIONS] Initialisé avec succès ✓');
        
        // Appliquer les améliorations
        enhanceForms();
        setupLazyLoading();
        updateOnlineStatus();
        
        // Log performance après chargement complet
        if (document.readyState === 'complete') {
            logPerformance();
        } else {
            window.addEventListener('load', logPerformance);
        }

        // Ajouter une classe au body pour indiquer que mobile est initialisé
        document.body.classList.add('mobile-enhanced');
        
        // Ajouter des classes spécifiques au device
        if (isIOS) document.body.classList.add('is-ios');
        if (isAndroid) document.body.classList.add('is-android');
        if (isMobile) document.body.classList.add('is-mobile');
    }

    // Initialiser quand le DOM est prêt
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // ========================================
    // 16. EXPORT DES UTILITAIRES (optionnel)
    // ========================================

    window.EVON_Mobile = {
        isMobile,
        isIOS,
        isAndroid,
        isTouchDevice,
        hapticFeedback,
        setViewportHeight,
        version: '1.0.0'
    };

})();

