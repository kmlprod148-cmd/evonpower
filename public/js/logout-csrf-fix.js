/**
 * Script pour corriger l'erreur 419 (Page Expired) lors du logout
 * Rafraîchit automatiquement le token CSRF avant la soumission du formulaire de logout
 */

(function() {
    'use strict';

    /**
     * Récupère le token CSRF actuel depuis la meta tag
     */
    function getCsrfToken() {
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        return metaTag ? metaTag.getAttribute('content') : null;
    }

    /**
     * Rafraîchit le token CSRF depuis le serveur
     */
    async function refreshCsrfToken() {
        try {
            // Utiliser un timestamp pour éviter le cache (important pour Safari)
            const timestamp = new Date().getTime();
            const random = Math.random().toString(36).substring(7);
            const url = `/csrf-token?t=${timestamp}&r=${random}&_=${Date.now()}`;
            
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Cache-Control': 'no-cache, no-store, must-revalidate',
                    'Pragma': 'no-cache',
                    'Expires': '0'
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

                    // Mettre à jour tous les champs cachés _token
                    document.querySelectorAll('input[name="_token"]').forEach(input => {
                        input.value = data.csrf_token;
                    });

                    return data.csrf_token;
                }
            }
        } catch (error) {
            console.warn('Erreur lors du rafraîchissement du token CSRF:', error);
        }
        return null;
    }

    /**
     * Intercepte et gère la soumission des formulaires de logout
     */
    function handleLogoutForm(form) {
        form.addEventListener('submit', async function(e) {
            // Ne pas empêcher la soumission par défaut, on va juste rafraîchir le token
            const submitButton = form.querySelector('button[type="submit"]');
            const originalText = submitButton ? submitButton.innerHTML : '';
            
            // Désactiver le bouton pour éviter les doubles clics
            if (submitButton) {
                submitButton.disabled = true;
                if (originalText) {
                    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Déconnexion...';
                }
            }

            try {
                // Rafraîchir le token CSRF avant la soumission
                const newToken = await refreshCsrfToken();
                
                if (newToken) {
                    // Mettre à jour le token dans le formulaire
                    let tokenInput = form.querySelector('input[name="_token"]');
                    if (!tokenInput) {
                        // Créer le champ s'il n'existe pas
                        tokenInput = document.createElement('input');
                        tokenInput.type = 'hidden';
                        tokenInput.name = '_token';
                        form.appendChild(tokenInput);
                    }
                    tokenInput.value = newToken;
                }

                // Soumettre le formulaire normalement
                // Le formulaire sera soumis avec le nouveau token
                return true;
            } catch (error) {
                console.error('Erreur lors de la préparation du logout:', error);
                
                // Réactiver le bouton en cas d'erreur
                if (submitButton) {
                    submitButton.disabled = false;
                    if (originalText) {
                        submitButton.innerHTML = originalText;
                    }
                }

                // Afficher un message d'erreur
                alert('Une erreur est survenue lors de la déconnexion. Veuillez réessayer.');
                e.preventDefault();
                return false;
            }
        });
    }

    /**
     * Rafraîchit périodiquement le token CSRF (toutes les 5 minutes)
     * Utile pour les appareils mobiles qui restent inactifs
     */
    function startPeriodicTokenRefresh() {
        // Rafraîchir le token toutes les 5 minutes (300000 ms)
        setInterval(async function() {
            const newToken = await refreshCsrfToken();
            if (newToken) {
                console.log('Token CSRF rafraîchi automatiquement');
            }
        }, 300000); // 5 minutes
        
        // Rafraîchir aussi quand l'application redevient visible (mobile)
        document.addEventListener('visibilitychange', async function() {
            if (!document.hidden) {
                const newToken = await refreshCsrfToken();
                if (newToken) {
                    console.log('Token CSRF rafraîchi (retour en premier plan)');
                }
            }
        });
        
        // Rafraîchir quand la page reprend le focus (iOS Safari)
        window.addEventListener('focus', async function() {
            const newToken = await refreshCsrfToken();
            if (newToken) {
                console.log('Token CSRF rafraîchi (focus retrouvé)');
            }
        });
    }

    /**
     * Initialise la gestion des formulaires de logout
     */
    function initLogoutHandlers() {
        // Démarrer le rafraîchissement périodique
        startPeriodicTokenRefresh();
        
        // Trouver tous les formulaires de logout
        const logoutForms = document.querySelectorAll('form[action*="logout"]');
        
        logoutForms.forEach(form => {
            // Vérifier que le formulaire n'a pas déjà été traité
            if (!form.dataset.logoutHandlerAttached) {
                handleLogoutForm(form);
                form.dataset.logoutHandlerAttached = 'true';
            }
        });

        // Écouter les nouveaux formulaires ajoutés dynamiquement
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        // Vérifier si c'est un formulaire de logout
                        if (node.tagName === 'FORM' && node.action && node.action.includes('logout')) {
                            if (!node.dataset.logoutHandlerAttached) {
                                handleLogoutForm(node);
                                node.dataset.logoutHandlerAttached = 'true';
                            }
                        }
                        // Vérifier les formulaires à l'intérieur du node
                        const logoutForms = node.querySelectorAll ? node.querySelectorAll('form[action*="logout"]') : [];
                        logoutForms.forEach(form => {
                            if (!form.dataset.logoutHandlerAttached) {
                                handleLogoutForm(form);
                                form.dataset.logoutHandlerAttached = 'true';
                            }
                        });
                    }
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    // Initialiser quand le DOM est prêt
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLogoutHandlers);
    } else {
        initLogoutHandlers();
    }

    // Exposer la fonction de rafraîchissement pour utilisation manuelle
    window.refreshLogoutCsrfToken = refreshCsrfToken;

    console.log('Script de correction logout CSRF initialisé');
})();

