/**
 * Protection CSRF renforcée pour les appareils mobiles
 * Gère le rafraîchissement automatique du token CSRF
 * pour éviter l'erreur 419 sur les appareils qui restent inactifs
 */

(function() {
    'use strict';

    let lastActivity = Date.now();
    let tokenRefreshInterval;
    
    /**
     * Vérifie si l'appareil est un mobile
     */
    function isMobileDevice() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    }
    
    /**
     * Rafraîchit le token CSRF
     */
    async function refreshToken() {
        try {
            const timestamp = Date.now();
            const response = await fetch(`/csrf-token?t=${timestamp}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Cache-Control': 'no-cache'
                },
                credentials: 'same-origin',
                cache: 'no-store'
            });

            if (response.ok) {
                const data = await response.json();
                if (data.csrf_token) {
                    // Mettre à jour la meta tag
                    const metaTag = document.querySelector('meta[name="csrf-token"]');
                    if (metaTag) {
                        metaTag.setAttribute('content', data.csrf_token);
                    }

                    // Mettre à jour tous les champs _token
                    document.querySelectorAll('input[name="_token"]').forEach(input => {
                        input.value = data.csrf_token;
                    });

                    console.log('[Mobile CSRF] Token rafraîchi avec succès');
                    return true;
                }
            }
        } catch (error) {
            console.warn('[Mobile CSRF] Erreur lors du rafraîchissement:', error);
        }
        return false;
    }
    
    /**
     * Met à jour le timestamp de la dernière activité
     */
    function updateLastActivity() {
        lastActivity = Date.now();
    }
    
    /**
     * Vérifie si le token doit être rafraîchi
     */
    function shouldRefreshToken() {
        const inactivityTime = Date.now() - lastActivity;
        // Rafraîchir si inactif pendant plus de 10 minutes
        return inactivityTime > 600000;
    }
    
    /**
     * Initialise les listeners pour détecter l'activité
     */
    function initActivityDetection() {
        const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
        
        events.forEach(event => {
            document.addEventListener(event, updateLastActivity, { passive: true });
        });
    }
    
    /**
     * Gère le retour de l'application au premier plan
     */
    function handleVisibilityChange() {
        if (!document.hidden) {
            console.log('[Mobile CSRF] Application revenue au premier plan');
            
            // Rafraîchir le token si nécessaire
            if (shouldRefreshToken()) {
                console.log('[Mobile CSRF] Rafraîchissement du token après inactivité');
                refreshToken();
            }
        }
    }
    
    /**
     * Gère le retour du focus sur la page
     */
    function handlePageFocus() {
        console.log('[Mobile CSRF] Page a retrouvé le focus');
        
        // Rafraîchir le token si nécessaire
        if (shouldRefreshToken()) {
            console.log('[Mobile CSRF] Rafraîchissement du token après inactivité');
            refreshToken();
        }
    }
    
    /**
     * Intercepte toutes les soumissions de formulaire
     */
    function interceptFormSubmissions() {
        document.addEventListener('submit', async function(e) {
            const form = e.target;
            
            // Vérifier si c'est une requête POST
            if (form.method.toUpperCase() === 'POST') {
                // Rafraîchir le token si nécessaire avant la soumission
                if (shouldRefreshToken()) {
                    console.log('[Mobile CSRF] Rafraîchissement du token avant soumission du formulaire');
                    e.preventDefault();
                    
                    const success = await refreshToken();
                    if (success) {
                        // Mettre à jour le token dans le formulaire
                        let tokenInput = form.querySelector('input[name="_token"]');
                        if (tokenInput) {
                            const metaTag = document.querySelector('meta[name="csrf-token"]');
                            if (metaTag) {
                                tokenInput.value = metaTag.getAttribute('content');
                            }
                        }
                        
                        // Soumettre le formulaire
                        form.submit();
                    } else {
                        alert('Erreur de connexion. Veuillez actualiser la page.');
                    }
                }
            }
        }, true);
    }
    
    /**
     * Démarre le rafraîchissement automatique périodique
     */
    function startPeriodicRefresh() {
        // Rafraîchir le token toutes les 10 minutes sur mobile
        if (isMobileDevice()) {
            tokenRefreshInterval = setInterval(function() {
                console.log('[Mobile CSRF] Rafraîchissement périodique du token');
                refreshToken();
            }, 600000); // 10 minutes
        }
    }
    
    /**
     * Initialise toutes les protections
     */
    function init() {
        console.log('[Mobile CSRF] Initialisation de la protection CSRF mobile');
        
        // Détecter l'activité utilisateur
        initActivityDetection();
        
        // Écouter le changement de visibilité (app en arrière-plan/premier plan)
        document.addEventListener('visibilitychange', handleVisibilityChange);
        
        // Écouter le focus de la page (iOS Safari)
        window.addEventListener('focus', handlePageFocus);
        window.addEventListener('pageshow', handlePageFocus);
        
        // Intercepter les soumissions de formulaire
        interceptFormSubmissions();
        
        // Démarrer le rafraîchissement périodique sur mobile
        startPeriodicRefresh();
        
        // Rafraîchir immédiatement sur mobile
        if (isMobileDevice()) {
            refreshToken();
        }
    }
    
    // Initialiser quand le DOM est prêt
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // Exposer la fonction pour utilisation externe
    window.mobileCsrfRefresh = refreshToken;
    
})();

