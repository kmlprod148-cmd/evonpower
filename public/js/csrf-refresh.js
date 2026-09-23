/**
 * Script de rafraîchissement automatique du token CSRF
 * Évite les erreurs "page expired" en renouvelant le token périodiquement
 */

(function() {
    'use strict';
    
    let csrfToken = null;
    let refreshInterval = null;
    
    // Fonction pour récupérer le token CSRF actuel
    function getCurrentCsrfToken() {
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        return metaTag ? metaTag.getAttribute('content') : null;
    }
    
    // Fonction pour rafraîchir le token CSRF
    async function refreshCsrfToken() {
        try {
            const response = await fetch('/csrf-token', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.csrf_token) {
                    // Mettre à jour le token dans la meta tag
                    const metaTag = document.querySelector('meta[name="csrf-token"]');
                    if (metaTag) {
                        metaTag.setAttribute('content', data.csrf_token);
                        csrfToken = data.csrf_token;
                        
                        // Mettre à jour tous les champs cachés _token
                        document.querySelectorAll('input[name="_token"]').forEach(input => {
                            input.value = data.csrf_token;
                        });
                        
                        console.log('Token CSRF rafraîchi automatiquement');
                    }
                }
            }
        } catch (error) {
            console.warn('Impossible de rafraîchir le token CSRF:', error);
        }
    }
    
    // Fonction pour intercepter les erreurs 419 (CSRF token mismatch)
    function handleCsrfError() {
        // Rafraîchir la page si le token est expiré
        if (confirm('Votre session a expiré. Voulez-vous recharger la page ?')) {
            window.location.reload();
        }
    }
    
    // Fonction pour intercepter les requêtes fetch et ajouter le token CSRF
    const originalFetch = window.fetch;
    window.fetch = function(url, options = {}) {
        // Normaliser les options
        const normalizedOptions = { ...options };
        
        // Initialiser les headers si nécessaire
        if (!normalizedOptions.headers) {
            normalizedOptions.headers = {};
        }
        
        // Vérifier si c'est une requête vers l'API (pour Sanctum)
        const urlString = typeof url === 'string' ? url : url.url || '';
        const isApiRequest = urlString.startsWith('/api/') || urlString.includes('/api/');
        
        // Pour Sanctum avec cookies de session, ajouter credentials aux requêtes API
        if (isApiRequest && normalizedOptions.credentials === undefined) {
            normalizedOptions.credentials = 'same-origin';
        }
        
        // Ajouter le token CSRF aux requêtes POST, PUT, DELETE, PATCH
        if (normalizedOptions.method && ['POST', 'PUT', 'DELETE', 'PATCH'].includes(normalizedOptions.method.toUpperCase())) {
            // Ajouter X-Requested-With pour identifier les requêtes AJAX
            if (!normalizedOptions.headers['X-Requested-With']) {
                normalizedOptions.headers['X-Requested-With'] = 'XMLHttpRequest';
            }
            
            // Ajouter le token CSRF s'il n'est pas déjà présent
            if (!normalizedOptions.headers['X-CSRF-TOKEN']) {
                const token = getCurrentCsrfToken();
                if (token) {
                    normalizedOptions.headers['X-CSRF-TOKEN'] = token;
                }
            }
        }
        
        // Pour les requêtes GET vers l'API, ajouter aussi le token CSRF et X-Requested-With
        if (isApiRequest && (!normalizedOptions.method || normalizedOptions.method.toUpperCase() === 'GET')) {
            if (!normalizedOptions.headers['X-Requested-With']) {
                normalizedOptions.headers['X-Requested-With'] = 'XMLHttpRequest';
            }
        }
        
        return originalFetch.call(this, url, normalizedOptions)
            .then(response => {
                // Vérifier si c'est une erreur CSRF
                if (response.status === 419) {
                    handleCsrfError();
                }
                return response;
            })
            .catch(error => {
                // Répercuter l'erreur
                throw error;
            });
    };
    
    // Initialiser le token CSRF
    csrfToken = getCurrentCsrfToken();
    
    // Rafraîchir le token toutes les 30 minutes
    if (csrfToken) {
        refreshInterval = setInterval(refreshCsrfToken, 30 * 60 * 1000); // 30 minutes
        
        // Rafraîchir aussi avant que la session expire (toutes les 7 heures)
        setTimeout(() => {
            refreshCsrfToken();
        }, 7 * 60 * 60 * 1000); // 7 heures
    }
    
    // Nettoyer l'intervalle quand la page se ferme
    window.addEventListener('beforeunload', function() {
        if (refreshInterval) {
            clearInterval(refreshInterval);
        }
    });
    
    // Exposer la fonction de rafraîchissement manuel
    window.refreshCsrfToken = refreshCsrfToken;
    
    console.log('Script de rafraîchissement CSRF initialisé');
})();
