/**
 * Mobile Enhancements for EVON Dashboard
 * Améliore l'expérience mobile avec des gestures et interactions avancées
 */

(function() {
    'use strict';

    // Détecter si c'est un appareil mobile
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    const isTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;

    /**
     * Swipe Gesture Detection
     */
    class SwipeDetector {
        constructor(element, callbacks = {}) {
            this.element = element;
            this.callbacks = callbacks;
            this.startX = 0;
            this.startY = 0;
            this.distX = 0;
            this.distY = 0;
            this.startTime = 0;
            this.threshold = 100; // Distance minimum pour détecter un swipe
            this.timeThreshold = 300; // Temps maximum pour détecter un swipe rapide
            this.restraint = 100; // Distance max perpendiculaire

            this.init();
        }

        init() {
            this.element.addEventListener('touchstart', this.handleTouchStart.bind(this), { passive: true });
            this.element.addEventListener('touchmove', this.handleTouchMove.bind(this), { passive: false });
            this.element.addEventListener('touchend', this.handleTouchEnd.bind(this), { passive: true });
        }

        handleTouchStart(e) {
            const touch = e.touches[0];
            this.startX = touch.clientX;
            this.startY = touch.clientY;
            this.startTime = Date.now();
        }

        handleTouchMove(e) {
            // Empêcher le scroll si c'est un swipe horizontal
            if (Math.abs(this.distX) > Math.abs(this.distY) && Math.abs(this.distX) > 10) {
                e.preventDefault();
            }
        }

        handleTouchEnd(e) {
            const touch = e.changedTouches[0];
            this.distX = touch.clientX - this.startX;
            this.distY = touch.clientY - this.startY;
            const elapsedTime = Date.now() - this.startTime;

            // Déterminer la direction du swipe
            if (elapsedTime <= this.timeThreshold) {
                if (Math.abs(this.distX) >= this.threshold && Math.abs(this.distY) <= this.restraint) {
                    // Swipe horizontal
                    const direction = this.distX > 0 ? 'right' : 'left';
                    if (this.callbacks[direction]) {
                        this.callbacks[direction](this.element);
                    }
                } else if (Math.abs(this.distY) >= this.threshold && Math.abs(this.distX) <= this.restraint) {
                    // Swipe vertical
                    const direction = this.distY > 0 ? 'down' : 'up';
                    if (this.callbacks[direction]) {
                        this.callbacks[direction](this.element);
                    }
                }
            }

            // Reset
            this.distX = 0;
            this.distY = 0;
        }
    }

    /**
     * Pull to Refresh
     */
    class PullToRefresh {
        constructor(element, callback) {
            this.element = element;
            this.callback = callback;
            this.startY = 0;
            this.pullDistance = 0;
            this.threshold = 80;
            this.indicator = null;
            this.isRefreshing = false;

            this.createIndicator();
            this.init();
        }

        createIndicator() {
            this.indicator = document.createElement('div');
            this.indicator.className = 'evon-pull-to-refresh';
            this.indicator.innerHTML = `
                <svg class="w-6 h-6 text-eco-green-600 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span class="text-sm text-eco-green-600 ml-2">Tirer pour actualiser...</span>
            `;
            this.element.style.position = 'relative';
            this.element.insertBefore(this.indicator, this.element.firstChild);
        }

        init() {
            this.element.addEventListener('touchstart', this.handleTouchStart.bind(this), { passive: true });
            this.element.addEventListener('touchmove', this.handleTouchMove.bind(this), { passive: false });
            this.element.addEventListener('touchend', this.handleTouchEnd.bind(this), { passive: true });
        }

        handleTouchStart(e) {
            if (this.element.scrollTop === 0 && !this.isRefreshing) {
                this.startY = e.touches[0].clientY;
            }
        }

        handleTouchMove(e) {
            if (this.startY === 0 || this.isRefreshing) return;

            this.pullDistance = e.touches[0].clientY - this.startY;
            
            if (this.pullDistance > 0 && this.element.scrollTop === 0) {
                e.preventDefault();
                const progress = Math.min(this.pullDistance / this.threshold, 1);
                this.indicator.style.transform = `translateY(${this.pullDistance * 0.5}px)`;
                this.indicator.style.opacity = progress;
            }
        }

        handleTouchEnd() {
            if (this.pullDistance >= this.threshold && !this.isRefreshing) {
                this.isRefreshing = true;
                this.indicator.classList.add('active');
                this.indicator.querySelector('span').textContent = 'Actualisation...';
                
                // Appeler la fonction de callback
                Promise.resolve(this.callback()).finally(() => {
                    setTimeout(() => {
                        this.reset();
                    }, 500);
                });
            } else {
                this.reset();
            }
        }

        reset() {
            this.startY = 0;
            this.pullDistance = 0;
            this.isRefreshing = false;
            this.indicator.classList.remove('active');
            this.indicator.style.transform = '';
            this.indicator.style.opacity = '';
            this.indicator.querySelector('span').textContent = 'Tirer pour actualiser...';
        }
    }

    /**
     * Optimiser les performances de scroll
     */
    function optimizeScrollPerformance() {
        let ticking = false;
        const scrollElements = document.querySelectorAll('.evon-main, .evon-sidebar-nav, .evon-mobile-search-results');

        scrollElements.forEach(element => {
            element.addEventListener('scroll', function() {
                if (!ticking) {
                    window.requestAnimationFrame(function() {
                        // Logique de scroll optimisée
                        ticking = false;
                    });
                    ticking = true;
                }
            }, { passive: true });
        });
    }

    /**
     * Améliorer les tap sur les boutons
     */
    function enhanceTapFeedback() {
        const buttons = document.querySelectorAll('button, a.btn-primary, a.btn-secondary, .evon-nav-item');
        
        buttons.forEach(button => {
            button.addEventListener('touchstart', function() {
                this.style.opacity = '0.7';
            }, { passive: true });

            button.addEventListener('touchend', function() {
                this.style.opacity = '';
            }, { passive: true });

            button.addEventListener('touchcancel', function() {
                this.style.opacity = '';
            }, { passive: true });
        });
    }

    /**
     * Optimiser les inputs pour iOS (empêcher le zoom)
     */
    function optimizeInputsForIOS() {
        if (/iPhone|iPad|iPod/i.test(navigator.userAgent)) {
            const inputs = document.querySelectorAll('input[type="text"], input[type="email"], input[type="password"], input[type="search"], input[type="tel"], textarea');
            
            inputs.forEach(input => {
                // S'assurer que la taille de police est au moins 16px pour éviter le zoom
                const computedStyle = window.getComputedStyle(input);
                const fontSize = parseFloat(computedStyle.fontSize);
                
                if (fontSize < 16) {
                    input.style.fontSize = '16px';
                }
            });
        }
    }

    /**
     * Gérer la viewport height sur mobile (pour gérer la barre d'adresse)
     */
    function handleMobileViewportHeight() {
        const setVH = () => {
            const vh = window.innerHeight * 0.01;
            document.documentElement.style.setProperty('--vh', `${vh}px`);
        };

        setVH();
        window.addEventListener('resize', setVH);
        window.addEventListener('orientationchange', setVH);
    }

    /**
     * Ajouter le support des swipes sur les cards
     */
    function initializeSwipeableCards() {
        const swipeableCards = document.querySelectorAll('.evon-swipeable-card');
        
        swipeableCards.forEach(card => {
            new SwipeDetector(card, {
                left: (element) => {
                    // Action swipe left (ex: supprimer)
                    element.classList.add('animate-slide-out-left');
                },
                right: (element) => {
                    // Action swipe right (ex: archiver)
                    element.classList.add('animate-slide-out-right');
                }
            });
        });
    }

    /**
     * Initialiser Pull to Refresh sur la page principale
     */
    function initializePullToRefresh() {
        const mainContent = document.querySelector('.evon-main');
        if (mainContent && isMobile) {
            new PullToRefresh(mainContent, () => {
                // Rafraîchir la page ou charger les nouvelles données
                return new Promise((resolve) => {
                    setTimeout(() => {
                        window.location.reload();
                        resolve();
                    }, 1000);
                });
            });
        }
    }

    /**
     * Améliorer les transitions lors du changement d'orientation
     */
    function handleOrientationChange() {
        window.addEventListener('orientationchange', function() {
            // Désactiver temporairement les transitions pendant le changement
            document.body.style.transition = 'none';
            
            setTimeout(function() {
                document.body.style.transition = '';
            }, 300);
        });
    }

    /**
     * Initialisation
     */
    function init() {
        if (!isMobile && !isTouch) {
            console.log('[Mobile Enhancements] Not a mobile device, skipping mobile enhancements');
            return;
        }

        console.log('[Mobile Enhancements] Initializing mobile enhancements');

        // Attendre que le DOM soit prêt
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                applyEnhancements();
            });
        } else {
            applyEnhancements();
        }
    }

    function applyEnhancements() {
        optimizeScrollPerformance();
        enhanceTapFeedback();
        optimizeInputsForIOS();
        handleMobileViewportHeight();
        initializeSwipeableCards();
        // initializePullToRefresh(); // Désactiver si non désiré
        handleOrientationChange();

        console.log('[Mobile Enhancements] Mobile enhancements applied successfully');
    }

    // Exposer pour utilisation externe si nécessaire
    window.MobileEnhancements = {
        SwipeDetector,
        PullToRefresh,
        init
    };

    // Auto-initialisation
    init();

})();

