/**
 * EVON Mobile Enhancements v2.0
 * ==============================
 * Améliorations UX pour mobile avec support de:
 * - Lazy loading images
 * - Intersection Observer
 * - Performance optimizations
 * - Touch gestures
 * - Haptic feedback
 * - PWA capabilities
 */

(function() {
    'use strict';
    
    // Debug mode - set to true for verbose logging
    const DEBUG = false;
    const log = DEBUG ? console.log.bind(console) : () => {};
    
    // ========================================
    // 1. DEVICE DETECTION
    // ========================================
    
    const DEVICE = {
        isMobile: /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent),
        isIOS: /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream,
        isAndroid: /Android/.test(navigator.userAgent),
        isSafari: /^((?!chrome|android).)*safari/i.test(navigator.userAgent),
        supportsTouch: 'ontouchstart' in window || navigator.maxTouchPoints > 0,
        supportsVibrate: 'vibrate' in navigator,
        supportsIntersectionObserver: 'IntersectionObserver' in window,
        supportsServiceWorker: 'serviceWorker' in navigator,
    };
    
    // Exposer les infos de device globalement
    window.EVON_DEVICE = DEVICE;
    
    // ========================================
    // 2. LAZY LOADING IMAGES
    // ========================================
    
    function initLazyLoading() {
        if (!DEVICE.supportsIntersectionObserver) {
            // Fallback: charger toutes les images immédiatement
            document.querySelectorAll('img[data-src]').forEach(img => {
                img.src = img.dataset.src;
                if (img.dataset.srcset) {
                    img.srcset = img.dataset.srcset;
                }
            });
            return;
        }
        
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    
                    // Charger l'image
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                    }
                    if (img.dataset.srcset) {
                        img.srcset = img.dataset.srcset;
                    }
                    
                    // Animation de fade in
                    img.classList.add('loaded');
                    img.classList.remove('lazy-image');
                    
                    // Arrêter d'observer cette image
                    observer.unobserve(img);
                }
            });
        }, {
            rootMargin: '50px', // Charger 50px avant que l'image soit visible
            threshold: 0.01
        });
        
        // Observer toutes les images lazy
        document.querySelectorAll('img[data-src]').forEach(img => {
            img.classList.add('lazy-image');
            imageObserver.observe(img);
        });
        
        log('🖼️ Lazy loading initialized');
    }
    
    // ========================================
    // 3. HAPTIC FEEDBACK
    // ========================================
    
    window.vibrate = function(pattern = 'light') {
        if (!DEVICE.supportsVibrate) return;
        
        const patterns = {
            light: 10,
            medium: 20,
            heavy: 30,
            double: [10, 50, 10],
            triple: [10, 50, 10, 50, 10],
            success: [10, 50, 10],
            error: [10, 100, 10, 100, 10],
        };
        
        const vibrationPattern = patterns[pattern] || patterns.light;
        navigator.vibrate(vibrationPattern);
    };
    
    // Ajouter haptic feedback aux boutons
    function initHapticFeedback() {
        if (!DEVICE.supportsVibrate) return;
        
        // Ajouter vibration aux boutons et liens
        document.addEventListener('click', (e) => {
            const target = e.target.closest('button, a, .touch-feedback');
            if (target && !target.classList.contains('no-haptic')) {
                const intensity = target.dataset.haptic || 'light';
                window.vibrate(intensity);
            }
        }, { passive: true });
        
        log('📳 Haptic feedback initialized');
    }
    
    // ========================================
    // 4. SMOOTH SCROLLING
    // ========================================
    
    function initSmoothScrolling() {
        // Améliorer le smooth scroll sur iOS
        if (DEVICE.isIOS) {
            document.documentElement.style.webkitOverflowScrolling = 'touch';
        }
        
        // Gérer les ancres avec smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href === '#' || href === '#!') return;
                
                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                    
                    // Vibration légère
                    window.vibrate('light');
                }
            });
        });
        
        log('📜 Smooth scrolling initialized');
    }
    
    // ========================================
    // 5. TOUCH OPTIMIZATIONS
    // ========================================
    
    function initTouchOptimizations() {
        // Empêcher le zoom sur double-tap (iOS)
        let lastTouchEnd = 0;
        document.addEventListener('touchend', (e) => {
            const now = Date.now();
            if (now - lastTouchEnd <= 300) {
                e.preventDefault();
            }
            lastTouchEnd = now;
        }, { passive: false });
        
        // Fast click (réduire le délai de 300ms)
        if (DEVICE.isMobile) {
            document.addEventListener('touchstart', function() {}, { passive: true });
        }
        
        log('👆 Touch optimizations initialized');
    }
    
    // ========================================
    // 6. VIEWPORT HEIGHT FIX (iOS Safari)
    // ========================================
    
    function initViewportFix() {
        // Fix pour la barre d'adresse iOS qui change la hauteur du viewport
        function setVh() {
            const vh = window.innerHeight * 0.01;
            document.documentElement.style.setProperty('--vh', `${vh}px`);
        }
        
        setVh();
        window.addEventListener('resize', setVh);
        window.addEventListener('orientationchange', () => {
            setTimeout(setVh, 100);
        });
        
        log('📐 Viewport fix initialized');
    }
    
    // ========================================
    // 7. NETWORK STATUS
    // ========================================
    
    function initNetworkStatus() {
        if (!('connection' in navigator)) return;
        
        const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        
        function updateNetworkStatus() {
            const effectiveType = connection?.effectiveType || 'unknown';
            const isSlowConnection = effectiveType === 'slow-2g' || effectiveType === '2g';
            
            document.documentElement.classList.toggle('slow-connection', isSlowConnection);
            
            // Émettre un événement
            window.dispatchEvent(new CustomEvent('network-status-change', {
                detail: { effectiveType, isSlowConnection }
            }));
        }
        
        if (connection) {
            connection.addEventListener('change', updateNetworkStatus);
            updateNetworkStatus();
        }
        
        // Online/Offline detection
        window.addEventListener('online', () => {
            log('🌐 Connection restored');
            document.body.classList.remove('offline');
            showToast('Connexion rétablie', 'success');
        });
        
        window.addEventListener('offline', () => {
            log('📵 Connection lost');
            document.body.classList.add('offline');
            showToast('Connexion perdue', 'error');
        });
        
        log('🌐 Network status monitoring initialized');
    }
    
    // ========================================
    // 8. PERFORMANCE OPTIMIZATIONS
    // ========================================
    
    function initPerformanceOptimizations() {
        // Defer non-critical CSS
        const deferredStyles = document.querySelectorAll('link[rel="stylesheet"][data-defer]');
        deferredStyles.forEach(link => {
            link.media = 'all';
        });
        
        // Preconnect to external domains
        const externalDomains = ['fonts.googleapis.com', 'fonts.gstatic.com'];
        externalDomains.forEach(domain => {
            const link = document.createElement('link');
            link.rel = 'preconnect';
            link.href = `https://${domain}`;
            link.crossOrigin = 'anonymous';
            document.head.appendChild(link);
        });
        
        // Resource hints pour les pages probablement visitées
        if ('IntersectionObserver' in window) {
            const linkObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const link = entry.target;
                        const href = link.href;
                        
                        if (href && !document.querySelector(`link[rel="prefetch"][href="${href}"]`)) {
                            const prefetchLink = document.createElement('link');
                            prefetchLink.rel = 'prefetch';
                            prefetchLink.href = href;
                            document.head.appendChild(prefetchLink);
                        }
                    }
                });
            }, { rootMargin: '200px' });
            
            document.querySelectorAll('a[href^="/"]').forEach(link => {
                linkObserver.observe(link);
            });
        }
        
        log('⚡ Performance optimizations initialized');
    }
    
    // ========================================
    // 9. TOAST NOTIFICATIONS
    // ========================================
    
    window.showToast = function(message, type = 'info', duration = 3000) {
        const existingToast = document.querySelector('.evon-toast');
        if (existingToast) {
            existingToast.remove();
        }
        
        const toast = document.createElement('div');
        toast.className = `evon-toast evon-toast-${type}`;
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            bottom: calc(env(safe-area-inset-bottom, 0px) + 80px);
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: ${type === 'success' ? '#4acf7b' : type === 'error' ? '#ef4444' : '#3b82f6'};
            color: white;
            padding: 12px 24px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            z-index: 9999;
            transition: transform 0.3s ease-out;
            max-width: 90%;
            text-align: center;
        `;
        
        document.body.appendChild(toast);
        
        // Animation d'entrée
        setTimeout(() => {
            toast.style.transform = 'translateX(-50%) translateY(0)';
        }, 10);
        
        // Animation de sortie
        setTimeout(() => {
            toast.style.transform = 'translateX(-50%) translateY(100px)';
            setTimeout(() => toast.remove(), 300);
        }, duration);
        
        // Vibration selon le type
        if (type === 'success') {
            window.vibrate('success');
        } else if (type === 'error') {
            window.vibrate('error');
        }
    };
    
    // ========================================
    // 10. INITIALIZATION
    // ========================================
    
    function init() {
        // Attendre que le DOM soit prêt
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
            return;
        }
        
        // Initialiser tous les modules
        initLazyLoading();
        initHapticFeedback();
        initSmoothScrolling();
        initTouchOptimizations();
        initViewportFix();
        initNetworkStatus();
        initPerformanceOptimizations();
        
        // Marquer comme initialisé
        document.documentElement.classList.add('mobile-enhanced');
        
        log('✅ EVON Mobile Enhancements fully initialized');
        
        // Émettre un événement
        window.dispatchEvent(new Event('mobile-enhancements-ready'));
    }
    
    // Lancer l'initialisation
    init();
    
})();

