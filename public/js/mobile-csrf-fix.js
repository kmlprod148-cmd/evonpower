/**
 * ═══════════════════════════════════════════════════════════════════════
 * MOBILE CSRF FIX - SOLUTION PROFESSIONNELLE SIMPLIFIÉE
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Fix définitif pour l'erreur 419 (Page Expired) sur appareils mobiles
 * 
 * @version 3.0
 * @author Senior Laravel Developer
 * @date 2025-12-14
 */

(function() {
    'use strict';

    // ═══════════════════════════════════════════════════════════════
    // CONFIGURATION
    // ═══════════════════════════════════════════════════════════════
    
    const CONFIG = {
        refreshInterval: 300000,     // 5 minutes
        inactivityThreshold: 600000, // 10 minutes
        maxRetries: 2,
        debug: true
    };

    // ═══════════════════════════════════════════════════════════════
    // DÉTECTION DEVICE
    // ═══════════════════════════════════════════════════════════════
    
    const DEVICE = {
        isIPhone: /iPhone/.test(navigator.userAgent),
        isIPad: /iPad/.test(navigator.userAgent),
        isIOS: /iPad|iPhone|iPod/.test(navigator.userAgent),
        isSafari: /^((?!chrome|android).)*safari/i.test(navigator.userAgent),
        isMobile: /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
    };

    // ═══════════════════════════════════════════════════════════════
    // ÉTAT GLOBAL
    // ═══════════════════════════════════════════════════════════════
    
    const STATE = {
        lastActivity: Date.now(),
        lastRefresh: Date.now(),
        isRefreshing: false,
        currentToken: null,
        initialized: false
    };

    // ═══════════════════════════════════════════════════════════════
    // FONCTIONS UTILITAIRES
    // ═══════════════════════════════════════════════════════════════

    function log(message, data = null) {
        if (CONFIG.debug) {
            const prefix = DEVICE.isMobile ? '📱 [Mobile CSRF]' : '🔒 [CSRF]';
            console.log(prefix, message, data || '');
        }
    }

    function getCsrfToken() {
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        const token = metaTag ? metaTag.getAttribute('content') : null;
        
        if (token) {
            STATE.currentToken = token;
        }
        
        return token;
    }

    function updateCsrfToken(newToken) {
        if (!newToken) {
            log('⚠️ Token vide ignoré');
            return false;
        }

        log('✅ Mise à jour token:', newToken.substring(0, 10) + '...');

        // Mettre à jour meta tag
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        if (metaTag) {
            metaTag.setAttribute('content', newToken);
        }

        // Mettre à jour tous les champs _token
        document.querySelectorAll('input[name="_token"]').forEach(input => {
            input.value = newToken;
        });

        STATE.currentToken = newToken;
        STATE.lastRefresh = Date.now();

        return true;
    }

    async function refreshCsrfToken() {
        if (STATE.isRefreshing) {
            log('⏳ Rafraîchissement en cours...');
            return getCsrfToken();
        }

        STATE.isRefreshing = true;

        try {
            // URL avec cache busting pour Safari/Mobile
            const timestamp = Date.now();
            const random = Math.random().toString(36).substring(2, 15);
            const url = `/csrf-token?t=${timestamp}&r=${random}&_=${Date.now()}`;

            log('🔄 Rafraîchissement token...');

            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Cache-Control': 'no-cache, no-store, must-revalidate',
                    'Pragma': 'no-cache'
                },
                credentials: 'same-origin',
                cache: 'no-store'
            });

            if (response.ok) {
                const data = await response.json();
                
                if (data.csrf_token) {
                    updateCsrfToken(data.csrf_token);
                    log('✅ Token rafraîchi');
                    return data.csrf_token;
                }
            } else {
                log('❌ Erreur HTTP:', response.status);
            }
        } catch (error) {
            log('❌ Erreur refresh:', error.message);
        } finally {
            STATE.isRefreshing = false;
        }

        return getCsrfToken();
    }

    function shouldRefreshToken() {
        const timeSinceLastRefresh = Date.now() - STATE.lastRefresh;
        const timeSinceLastActivity = Date.now() - STATE.lastActivity;

        return timeSinceLastRefresh > CONFIG.refreshInterval ||
               timeSinceLastActivity > CONFIG.inactivityThreshold;
    }

    // ═══════════════════════════════════════════════════════════════
    // INTERCEPTION FETCH (SOLUTION CLÉE)
    // ═══════════════════════════════════════════════════════════════

    function interceptFetch() {
        if (window.fetch._csrfIntercepted) {
            log('⚠️ Fetch déjà intercepté');
            return;
        }

        const originalFetch = window.fetch;
        
        window.fetch = async function(url, options = {}) {
            const method = (options.method || 'GET').toUpperCase();
            const needsCsrf = ['POST', 'PUT', 'DELETE', 'PATCH'].includes(method);

            // Préparer les headers
            if (!options.headers) {
                options.headers = {};
            }

            // Ajouter le token CSRF si nécessaire
            if (needsCsrf) {
                // Rafraîchir si inactif longtemps
                if (shouldRefreshToken()) {
                    log('🔄 Rafraîchissement préventif');
                    await refreshCsrfToken();
                }

                const token = getCsrfToken();
                if (token) {
                    options.headers['X-CSRF-TOKEN'] = token;
                }
                
                if (!options.headers['X-Requested-With']) {
                    options.headers['X-Requested-With'] = 'XMLHttpRequest';
                }
            }

            // Fonction avec retry sur 419
            async function executeWithRetry(retryCount = 0) {
                try {
                    const response = await originalFetch.call(this, url, options);

                    // Gérer erreur 419
                    if (response.status === 419 && needsCsrf) {
                        log(`❌ Erreur 419 (tentative ${retryCount + 1}/${CONFIG.maxRetries + 1})`);

                        if (retryCount < CONFIG.maxRetries) {
                            log('🔄 Retry avec nouveau token...');
                            
                            const newToken = await refreshCsrfToken();
                            
                            if (newToken) {
                                // Mettre à jour le token
                                options.headers['X-CSRF-TOKEN'] = newToken;
                                
                                // Si FormData, mettre à jour aussi
                                if (options.body instanceof FormData) {
                                    options.body.set('_token', newToken);
                                }
                                
                                // Petit délai avant retry
                                await new Promise(resolve => setTimeout(resolve, 200));
                                
                                // Retry
                                return executeWithRetry.call(this, retryCount + 1);
                            }
                        } else {
                            log('❌ Max retries atteint');
                            
                            // Sur mobile, recharger la page
                            if (DEVICE.isMobile) {
                                alert('Votre session a expiré. La page va se recharger.');
                                window.location.reload();
                            }
                        }
                    }

                    return response;
                    
                } catch (error) {
                    log('❌ Erreur fetch:', error.message);
                    throw error;
                }
            }

            return executeWithRetry.call(this);
        };

        window.fetch._csrfIntercepted = true;
        log('✅ Fetch intercepté');
    }

    // ═══════════════════════════════════════════════════════════════
    // GESTION DES FORMULAIRES
    // ═══════════════════════════════════════════════════════════════

    function interceptFormSubmissions() {
        document.addEventListener('submit', async function(e) {
            const form = e.target;
            
            // Vérifier si POST/PUT/DELETE
            const method = (form.method || 'GET').toUpperCase();
            if (!['POST', 'PUT', 'DELETE', 'PATCH'].includes(method)) {
                return; // GET forms sont ok
            }

            log('📝 Soumission formulaire détectée');
            
            // Rafraîchir si nécessaire AVANT soumission
            if (shouldRefreshToken()) {
                log('🔄 Rafraîchissement avant soumission');
                e.preventDefault();
                
                const newToken = await refreshCsrfToken();
                
                if (newToken) {
                    // Mettre à jour le token dans le formulaire
                    let tokenInput = form.querySelector('input[name="_token"]');
                    if (!tokenInput) {
                        tokenInput = document.createElement('input');
                        tokenInput.type = 'hidden';
                        tokenInput.name = '_token';
                        form.appendChild(tokenInput);
                    }
                    tokenInput.value = newToken;
                    
                    // Petit délai pour s'assurer que tout est synchro
                    await new Promise(resolve => setTimeout(resolve, 100));
                    
                    // Soumettre
                    form.submit();
                } else {
                    alert('Erreur lors du rafraîchissement. Veuillez réessayer.');
                }
            }
        }, { capture: true, passive: false });

        log('✅ Interception formulaires activée');
    }

    // ═══════════════════════════════════════════════════════════════
    // GESTION MOBILE / BACKGROUND
    // ═══════════════════════════════════════════════════════════════

    function setupBackgroundHandlers() {
        // App revenue au premier plan
        document.addEventListener('visibilitychange', async function() {
            if (!document.hidden) {
                log('👁️ App revenue au premier plan');
                
                // Toujours rafraîchir sur mobile après retour
                if (DEVICE.isMobile) {
                    log('🔄 Rafraîchissement après retour');
                    await refreshCsrfToken();
                }
            }
        });

        // Focus
        window.addEventListener('focus', async function() {
            if (DEVICE.isMobile && shouldRefreshToken()) {
                log('🔄 Rafraîchissement après focus');
                await refreshCsrfToken();
            }
        });

        // Page restaurée depuis le cache (Safari)
        window.addEventListener('pageshow', async function(event) {
            if (event.persisted) {
                log('👁️ Page restaurée depuis cache');
                log('🔄 Rafraîchissement forcé');
                await refreshCsrfToken();
            }
        });

        log('✅ Handlers background activés');
    }

    function startPeriodicRefresh() {
        // Sur mobile, rafraîchir périodiquement
        if (DEVICE.isMobile) {
            setInterval(function() {
                log('⏰ Rafraîchissement périodique');
                refreshCsrfToken();
            }, CONFIG.refreshInterval);

            log(`✅ Rafraîchissement périodique (${CONFIG.refreshInterval/1000}s)`);
        }
    }

    function setupActivityTracking() {
        const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
        
        events.forEach(event => {
            document.addEventListener(event, function() {
                STATE.lastActivity = Date.now();
            }, { passive: true });
        });

        log('✅ Tracking activité activé');
    }

    // ═══════════════════════════════════════════════════════════════
    // INITIALISATION
    // ═══════════════════════════════════════════════════════════════

    function init() {
        if (STATE.initialized) {
            log('⚠️ Déjà initialisé');
            return;
        }

        log('🚀 Initialisation Mobile CSRF Fix');
        log('📱 Device:', {
            isIPhone: DEVICE.isIPhone,
            isIPad: DEVICE.isIPad,
            isMobile: DEVICE.isMobile,
            isSafari: DEVICE.isSafari
        });

        // 1. Récupérer le token initial
        getCsrfToken();

        // 2. Intercepter fetch()
        interceptFetch();

        // 3. Intercepter les formulaires
        interceptFormSubmissions();

        // 4. Setup handlers background/mobile
        setupBackgroundHandlers();

        // 5. Tracking activité
        setupActivityTracking();

        // 6. Rafraîchissement périodique
        startPeriodicRefresh();

        // 7. Rafraîchissement immédiat sur mobile si inactif
        if (DEVICE.isMobile && shouldRefreshToken()) {
            log('🔄 Rafraîchissement initial');
            refreshCsrfToken();
        }

        STATE.initialized = true;
        log('✅ Initialisation terminée');
    }

    // ═══════════════════════════════════════════════════════════════
    // AUTO-START
    // ═══════════════════════════════════════════════════════════════

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // ═══════════════════════════════════════════════════════════════
    // API PUBLIQUE
    // ═══════════════════════════════════════════════════════════════

    window.MobileCsrfFix = {
        refresh: refreshCsrfToken,
        getToken: getCsrfToken,
        getState: () => ({ ...STATE }),
        getDevice: () => ({ ...DEVICE }),
        config: CONFIG
    };

    log('📦 API exposée: window.MobileCsrfFix');

})();

