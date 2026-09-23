/**
 * ═══════════════════════════════════════════════════════════════════════
 * ULTIMATE CSRF FIX FOR IPHONE - SOLUTION UNIFIÉE
 * ═══════════════════════════════════════════════════════════════════════
 * 
 * Ce script résout définitivement l'erreur 419 (Page Expired) sur iPhone
 * 
 * PROBLÈMES RÉSOLUS :
 * - ✅ Safari cache les pages et tokens expirent
 * - ✅ App revient de l'arrière-plan
 * - ✅ Token non rafraîchi avant soumission
 * - ✅ Multiples interceptions fetch() (conflits)
 * - ✅ Formulaires login/logout/actions
 * 
 * APPROCHE SENIOR LARAVEL DEVELOPER :
 * 1. UNE SEULE interception fetch() (évite conflits)
 * 2. Retry automatique avec nouveau token sur 419
 * 3. Rafraîchissement intelligent (pas trop souvent)
 * 4. Gestion spéciale iPhone/Safari/Mobile
 * 5. Prevention sur tous les formulaires
 * 
 * @version 2.0
 * @author Senior Laravel Developer
 * @date 2025-11-30
 */

(function() {
    'use strict';

    // ═══════════════════════════════════════════════════════════════
    // CONFIGURATION
    // ═══════════════════════════════════════════════════════════════
    
    const CONFIG = {
        // Intervalle de rafraîchissement (5 minutes sur iPhone)
        refreshInterval: 300000, // 5 minutes
        
        // Délai d'inactivité avant rafraîchissement forcé
        inactivityThreshold: 600000, // 10 minutes
        
        // Nombre de tentatives en cas d'erreur 419
        maxRetries: 2,
        
        // Debug mode (afficher les logs détaillés) - disabled in production
        debug: false
    };

    // ═══════════════════════════════════════════════════════════════
    // DÉTECTION DEVICE
    // ═══════════════════════════════════════════════════════════════
    
    const DEVICE = {
        isIPhone: /iPhone/.test(navigator.userAgent),
        isIPad: /iPad/.test(navigator.userAgent),
        isIOS: /iPad|iPhone|iPod/.test(navigator.userAgent),
        isSafari: /^((?!chrome|android).)*safari/i.test(navigator.userAgent),
        isMacSafari: /Macintosh/.test(navigator.userAgent) && /Safari/.test(navigator.userAgent),
        isAppleDevice: function() {
            return this.isIPhone || this.isIPad || this.isIOS || this.isMacSafari;
        },
        isMobile: /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
    };

    // ═══════════════════════════════════════════════════════════════
    // ÉTAT GLOBAL
    // ═══════════════════════════════════════════════════════════════
    
    const STATE = {
        lastActivity: Date.now(),
        lastRefresh: Date.now(),
        isRefreshing: false,
        refreshTimer: null,
        currentToken: null,
        initialized: false
    };

    // ═══════════════════════════════════════════════════════════════
    // FONCTIONS UTILITAIRES
    // ═══════════════════════════════════════════════════════════════

    /**
     * Log avec préfixe (seulement si debug activé)
     */
    function log(message, data = null) {
        if (CONFIG.debug) {
            const prefix = DEVICE.isIPhone ? '📱 [iPhone CSRF]' : '🔒 [CSRF]';
            if (data) {
                console.log(prefix, message, data);
            } else {
                console.log(prefix, message);
            }
        }
    }

    /**
     * Récupère le token CSRF actuel
     */
    function getCsrfToken() {
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        const token = metaTag ? metaTag.getAttribute('content') : null;
        
        if (token) {
            STATE.currentToken = token;
        }
        
        return token;
    }

    /**
     * Met à jour le token CSRF partout
     */
    function updateCsrfToken(newToken) {
        if (!newToken) {
            log('⚠️ Tentative de mise à jour avec token vide');
            return false;
        }

        log('✅ Mise à jour du token CSRF:', newToken.substring(0, 10) + '...');

        // 1. Mettre à jour la meta tag
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        if (metaTag) {
            metaTag.setAttribute('content', newToken);
        }

        // 2. Mettre à jour tous les champs input[name="_token"]
        document.querySelectorAll('input[name="_token"]').forEach(input => {
            input.value = newToken;
        });

        // 3. Mettre à jour l'état global
        STATE.currentToken = newToken;
        STATE.lastRefresh = Date.now();

        return true;
    }

    /**
     * Rafraîchit le token CSRF depuis le serveur
     */
    async function refreshCsrfToken() {
        // Éviter les rafraîchissements concurrents
        if (STATE.isRefreshing) {
            log('⏳ Rafraîchissement déjà en cours, attente...');
            return getCsrfToken();
        }

        STATE.isRefreshing = true;

        try {
            // URL avec cache busting agressif (critique pour Safari)
            const timestamp = Date.now();
            const random = Math.random().toString(36).substring(2, 15);
            const params = new URLSearchParams({
                t: timestamp,
                r: random,
                _: Date.now(),
                ios: DEVICE.isIPhone ? '1' : '0',
                device: DEVICE.isIPhone ? 'iphone' : (DEVICE.isIPad ? 'ipad' : 'other')
            });

            const url = `/csrf-token?${params.toString()}`;

            log('🔄 Rafraîchissement du token...', url);

            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Cache-Control': 'no-cache, no-store, must-revalidate',
                    'Pragma': 'no-cache',
                    'Expires': '0',
                    ...(DEVICE.isSafari && { 'X-Safari-Request': 'true' }),
                    ...(DEVICE.isIPhone && { 'X-iPhone-Request': 'true' })
                },
                credentials: 'same-origin',
                cache: 'no-store',
                redirect: 'follow',
                referrerPolicy: 'same-origin',
                mode: 'same-origin'
            });

            if (response.ok) {
                const data = await response.json();
                
                if (data.csrf_token) {
                    updateCsrfToken(data.csrf_token);
                    log('✅ Token rafraîchi avec succès');
                    return data.csrf_token;
                } else {
                    log('⚠️ Réponse sans token:', data);
                }
            } else {
                log('❌ Erreur HTTP lors du rafraîchissement:', response.status);
            }
        } catch (error) {
            log('❌ Erreur lors du rafraîchissement:', error);
        } finally {
            STATE.isRefreshing = false;
        }

        // Fallback : retourner le token actuel
        return getCsrfToken();
    }

    /**
     * Vérifie si le token doit être rafraîchi
     */
    function shouldRefreshToken() {
        const timeSinceLastRefresh = Date.now() - STATE.lastRefresh;
        const timeSinceLastActivity = Date.now() - STATE.lastActivity;

        // Rafraîchir si :
        // 1. Plus de 5 minutes depuis le dernier rafraîchissement
        // 2. Plus de 10 minutes d'inactivité
        return timeSinceLastRefresh > CONFIG.refreshInterval ||
               timeSinceLastActivity > CONFIG.inactivityThreshold;
    }

    // ═══════════════════════════════════════════════════════════════
    // INTERCEPTION FETCH (CORE FIX)
    // ═══════════════════════════════════════════════════════════════

    /**
     * Intercepte fetch() pour gérer automatiquement les erreurs 419
     * CRITIQUE : Une seule interception pour éviter les conflits
     */
    function interceptFetch() {
        // Vérifier si déjà intercepté
        if (window.fetch._csrfIntercepted) {
            log('⚠️ Fetch déjà intercepté, skip');
            return;
        }

        const originalFetch = window.fetch;
        
        window.fetch = async function(url, options = {}) {
            // Normaliser les options
            const normalizedOptions = { ...options };
            
            // Initialiser les headers
            if (!normalizedOptions.headers) {
                normalizedOptions.headers = {};
            }

            // Déterminer si c'est une requête qui nécessite CSRF
            const method = (normalizedOptions.method || 'GET').toUpperCase();
            const needsCsrf = ['POST', 'PUT', 'DELETE', 'PATCH'].includes(method);
            
            // Vérifier si c'est une requête vers le même domaine
            let isSameDomain = true;
            try {
                const requestUrl = new URL(url, window.location.origin);
                const currentOrigin = window.location.origin;
                isSameDomain = requestUrl.origin === currentOrigin;
            } catch (e) {
                // Si l'URL est relative, c'est forcément le même domaine
                isSameDomain = !url.includes('://');
            }

            // Ajouter le token CSRF si nécessaire ET si c'est le même domaine
            if (needsCsrf && isSameDomain) {
                // Rafraîchir le token si nécessaire AVANT la requête
                if (shouldRefreshToken()) {
                    log('🔄 Rafraîchissement préventif avant requête ' + method);
                    await refreshCsrfToken();
                }

                // Ajouter les headers nécessaires
                if (!normalizedOptions.headers['X-Requested-With']) {
                    normalizedOptions.headers['X-Requested-With'] = 'XMLHttpRequest';
                }
                
                if (!normalizedOptions.headers['X-CSRF-TOKEN']) {
                    const token = getCsrfToken();
                    if (token) {
                        normalizedOptions.headers['X-CSRF-TOKEN'] = token;
                    }
                }
            }

            // Fonction pour exécuter la requête avec retry sur 419
            async function executeWithRetry(retryCount = 0) {
                try {
                    const response = await originalFetch.call(this, url, normalizedOptions);

                    // Gestion erreur 419 (Token Mismatch)
                    if (response.status === 419) {
                        log(`❌ Erreur 419 détectée (tentative ${retryCount + 1}/${CONFIG.maxRetries + 1})`);

                        // Si on peut encore retry
                        if (retryCount < CONFIG.maxRetries) {
                            log('🔄 Retry avec nouveau token...');
                            
                            // Rafraîchir le token
                            const newToken = await refreshCsrfToken();
                            
                            if (newToken && needsCsrf) {
                                // Mettre à jour le token dans les headers
                                normalizedOptions.headers['X-CSRF-TOKEN'] = newToken;
                                
                                // Si c'est du FormData, mettre à jour aussi
                                if (normalizedOptions.body instanceof FormData) {
                                    normalizedOptions.body.set('_token', newToken);
                                }
                                
                                // Retry
                                return executeWithRetry.call(this, retryCount + 1);
                            }
                        } else {
                            log('❌ Max retries atteint, affichage erreur');
                            
                            // Afficher une alerte conviviale
                            if (DEVICE.isIPhone) {
                                alert('Votre session a expiré. La page va se recharger.');
                                window.location.reload();
                            }
                        }
                    }

                    return response;
                    
                } catch (error) {
                    log('❌ Erreur fetch:', error);
                    throw error;
                }
            }

            return executeWithRetry.call(this);
        };

        // Marquer comme intercepté
        window.fetch._csrfIntercepted = true;
        log('✅ Fetch intercepté avec succès');
    }

    // ═══════════════════════════════════════════════════════════════
    // GESTION DES FORMULAIRES
    // ═══════════════════════════════════════════════════════════════

    /**
     * Intercepte la soumission des formulaires
     */
    function interceptFormSubmissions() {
        document.addEventListener('submit', async function(e) {
            const form = e.target;
            
            // Vérifier si c'est un formulaire POST
            if (form.method && form.method.toUpperCase() === 'POST') {
                log('📝 Soumission formulaire détectée');
                
                // Rafraîchir le token si nécessaire
                if (shouldRefreshToken()) {
                    log('🔄 Rafraîchissement du token avant soumission formulaire');
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
                        
                        // Soumettre le formulaire
                        form.submit();
                    } else {
                        alert('Erreur lors du rafraîchissement. Veuillez réessayer.');
                    }
                }
            }
        }, { capture: true, passive: false });

        log('✅ Interception formulaires activée');
    }

    // ═══════════════════════════════════════════════════════════════
    // GESTION MOBILE / BACKGROUND
    // ═══════════════════════════════════════════════════════════════

    /**
     * Gère le retour de l'application au premier plan
     */
    function setupBackgroundHandlers() {
        // Visibilitychange (app en arrière-plan/premier plan)
        document.addEventListener('visibilitychange', async function() {
            if (!document.hidden) {
                log('👁️ App revenue au premier plan');
                
                // Toujours rafraîchir sur iPhone après retour
                if (DEVICE.isAppleDevice()) {
                    log('🔄 Rafraîchissement après retour au premier plan');
                    await refreshCsrfToken();
                }
            }
        });

        // Focus (iOS Safari spécifique)
        window.addEventListener('focus', async function() {
            log('👁️ Page a retrouvé le focus');
            
            if (DEVICE.isAppleDevice() && shouldRefreshToken()) {
                log('🔄 Rafraîchissement après focus');
                await refreshCsrfToken();
            }
        });

        // Pageshow (cache navigation)
        window.addEventListener('pageshow', async function(event) {
            if (event.persisted) {
                log('👁️ Page restaurée depuis le cache');
                log('🔄 Rafraîchissement après restauration');
                await refreshCsrfToken();
            }
        });

        log('✅ Handlers background activés');
    }

    /**
     * Démarre le rafraîchissement périodique
     */
    function startPeriodicRefresh() {
        // Sur iPhone, rafraîchir toutes les 5 minutes
        if (DEVICE.isAppleDevice()) {
            STATE.refreshTimer = setInterval(function() {
                log('⏰ Rafraîchissement périodique');
                refreshCsrfToken();
            }, CONFIG.refreshInterval);

            log(`✅ Rafraîchissement périodique activé (${CONFIG.refreshInterval/1000}s)`);
        }
    }

    /**
     * Tracking activité utilisateur
     */
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

    /**
     * Initialise toutes les protections CSRF
     */
    function init() {
        if (STATE.initialized) {
            log('⚠️ Déjà initialisé, skip');
            return;
        }

        log('🚀 Initialisation...');
        log('📱 Device:', {
            isIPhone: DEVICE.isIPhone,
            isIPad: DEVICE.isIPad,
            isIOS: DEVICE.isIOS,
            isSafari: DEVICE.isSafari,
            isAppleDevice: DEVICE.isAppleDevice()
        });

        // 1. Récupérer le token initial
        getCsrfToken();

        // 2. Intercepter fetch() (CRITIQUE)
        interceptFetch();

        // 3. Intercepter les formulaires
        interceptFormSubmissions();

        // 4. Setup handlers background/mobile
        setupBackgroundHandlers();

        // 5. Tracking activité
        setupActivityTracking();

        // 6. Rafraîchissement périodique
        startPeriodicRefresh();

        // 7. Rafraîchissement immédiat sur iPhone
        if (DEVICE.isIPhone) {
            log('🔄 Rafraîchissement immédiat (iPhone détecté)');
            refreshCsrfToken();
        }

        STATE.initialized = true;
        log('✅ Initialisation terminée');
    }

    // ═══════════════════════════════════════════════════════════════
    // AUTO-START
    // ═══════════════════════════════════════════════════════════════

    // Démarrer dès que le DOM est prêt
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // ═══════════════════════════════════════════════════════════════
    // API PUBLIQUE
    // ═══════════════════════════════════════════════════════════════

    // Exposer une API pour usage manuel
    window.UltimateCsrfFix = {
        refresh: refreshCsrfToken,
        getToken: getCsrfToken,
        getState: () => ({ ...STATE }),
        getDevice: () => ({ ...DEVICE }),
        config: CONFIG
    };

    log('📦 API exposée: window.UltimateCsrfFix');

})();

