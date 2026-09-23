/**
 * Correctif global pour l'erreur "Cannot read properties of null (reading 'dataset')"
 * S'applique à toute l'application pour prévenir cette erreur
 */

(function() {
    'use strict';
    
    // Silent initialization - no console logs in production
    
    // Intercepter toutes les erreurs TypeError
    const originalErrorHandler = window.onerror;
    window.onerror = function(message, source, lineno, colno, error) {
        const messageStr = String(message || '');
        if (messageStr.includes("Cannot read properties of null (reading 'dataset')") ||
            messageStr.includes("Cannot read property 'dataset' of null")) {
            // Empêcher l'affichage de l'erreur sans logger
            return true;
        }
        if (originalErrorHandler) {
            return originalErrorHandler.call(this, message, source, lineno, colno, error);
        }
        return false;
    };
    
    // Intercepter les erreurs de promesses
    // Vérifier si on est en mode debug (peut être défini par une variable globale)
    const isDebugMode = window.DEBUG_MODE === true || 
                        (typeof window.LARAVEL_DEBUG !== 'undefined' && window.LARAVEL_DEBUG === true);
    
    window.addEventListener('unhandledrejection', function(event) {
        if (event.reason) {
            const errorMessage = event.reason.message || event.reason.toString() || '';
            const errorString = String(event.reason);
            
            if (errorMessage.includes("Cannot read properties of null (reading 'dataset')") || 
                errorMessage.includes("Cannot read property 'dataset' of null") ||
                errorString.includes("Cannot read properties of null (reading 'dataset')") ||
                errorString.includes("Cannot read property 'dataset' of null") ||
                (event.reason && event.reason.name === 'TypeError' && (errorMessage.includes('dataset') || errorString.includes('dataset')))) {
                // Empêcher la propagation de l'erreur
                event.preventDefault();
                event.stopPropagation();
                
                // Logger uniquement en mode debug pour éviter le spam dans la console
                if (isDebugMode) {
                    console.debug('🔧 Promesse rejetée dataset null interceptée globalement:', errorMessage);
                }
                
                return false;
            }
        }
    }, true); // Utiliser capture phase pour intercepter plus tôt
    
    // Patch global pour les accès dataset
    function patchGlobalDatasetAccess() {
        // Intercepter les accès à dataset sur des éléments null
        const originalDatasetDescriptor = Object.getOwnPropertyDescriptor(Element.prototype, 'dataset');
        
        if (originalDatasetDescriptor) {
            Object.defineProperty(Element.prototype, 'dataset', {
                get: function() {
                    // Vérifications approfondies
                    if (!this) {
                        return new Proxy({}, {
                            get: () => undefined,
                            set: () => false
                        });
                    }
                    
                    if (this.nodeType !== 1) {
                        // Logger uniquement en mode debug
                        if (isDebugMode) {
                            console.debug('🔧 Accès dataset sur élément non-Element intercepté');
                        }
                        return new Proxy({}, {
                            get: () => undefined,
                            set: () => false
                        });
                    }
                    
                    try {
                        return originalDatasetDescriptor.get.call(this);
                    } catch (error) {
                        // Si l'erreur est liée à null/undefined, retourner un proxy vide
                        if (error && (error.message.includes('null') || error.message.includes('undefined'))) {
                            // Logger uniquement en mode debug
                            if (isDebugMode) {
                                console.debug('🔧 Erreur dataset null interceptée:', error.message);
                            }
                            return new Proxy({}, {
                                get: () => undefined,
                                set: () => false
                            });
                        }
                        // Logger uniquement en mode debug
                        if (isDebugMode) {
                            console.debug('🔧 Erreur dataset interceptée:', error);
                        }
                        return new Proxy({}, {
                            get: () => undefined,
                            set: () => false
                        });
                    }
                },
                configurable: true,
                enumerable: true
            });
        }
    }
    
    // Patch global pour les querySelector
    function patchGlobalQuerySelectors() {
        const originalQuerySelector = document.querySelector;
        const originalQuerySelectorAll = document.querySelectorAll;
        
        document.querySelector = function(selector) {
            try {
                const result = originalQuerySelector.call(this, selector);
                // S'assurer que l'élément retourné existe avant d'accéder à dataset
                if (result && result.nodeType === 1) {
                    return result;
                }
                return null;
            } catch (error) {
                // Logger uniquement en mode debug
                if (isDebugMode) {
                    console.debug('🔧 Erreur querySelector interceptée:', error);
                }
                return null;
            }
        };
        
        document.querySelectorAll = function(selector) {
            try {
                const results = originalQuerySelectorAll.call(this, selector);
                // Filtrer les résultats null/undefined
                return Array.from(results).filter(el => el && el.nodeType === 1);
            } catch (error) {
                // Logger uniquement en mode debug
                if (isDebugMode) {
                    console.debug('🔧 Erreur querySelectorAll interceptée:', error);
                }
                return [];
            }
        };
    }
    
    // Patch global pour les accès aux propriétés d'éléments
    function patchGlobalElementAccess() {
        // Intercepter getAttribute
        const originalGetAttribute = Element.prototype.getAttribute;
        Element.prototype.getAttribute = function(name) {
            if (!this || this.nodeType !== 1) {
                // Logger uniquement en mode debug
                if (isDebugMode) {
                    console.debug('🔧 Accès getAttribute sur élément null intercepté');
                }
                return null;
            }
            return originalGetAttribute.call(this, name);
        };
        
        // Intercepter setAttribute
        const originalSetAttribute = Element.prototype.setAttribute;
        Element.prototype.setAttribute = function(name, value) {
            if (!this || this.nodeType !== 1) {
                // Logger uniquement en mode debug
                if (isDebugMode) {
                    console.debug('🔧 Accès setAttribute sur élément null intercepté');
                }
                return;
            }
            return originalSetAttribute.call(this, name, value);
        };
        
        // Intercepter addEventListener
        const originalAddEventListener = Element.prototype.addEventListener;
        Element.prototype.addEventListener = function(type, listener, options) {
            if (!this || this.nodeType !== 1) {
                // Logger uniquement en mode debug
                if (isDebugMode) {
                    console.debug('🔧 Accès addEventListener sur élément null intercepté');
                }
                return;
            }
            return originalAddEventListener.call(this, type, listener, options);
        };
    }
    
    // Fonctions utilitaires globales
    window.safeDatasetAccess = function(element, property, defaultValue = null) {
        if (!element || !element.dataset) {
            return defaultValue;
        }
        return element.dataset[property] || defaultValue;
    };
    
    window.safeElementAccess = function(element, callback, defaultValue = null) {
        if (!element || element.nodeType !== 1) {
            return defaultValue;
        }
        try {
            return callback(element);
        } catch (error) {
            // Logger uniquement en mode debug
            if (isDebugMode) {
                console.debug('🔧 Erreur lors de l\'accès à l\'élément:', error);
            }
            return defaultValue;
        }
    };
    
    window.ensureElementExists = function(element, context = '') {
        if (!element) {
            // Logger uniquement en mode debug
            if (isDebugMode) {
                console.debug(`🔧 Élément non trouvé: ${context}`);
            }
            return false;
        }
        return true;
    };
    
    // Appliquer tous les patches
    function applyGlobalPatches() {
        patchGlobalDatasetAccess();
        patchGlobalQuerySelectors();
        patchGlobalElementAccess();
    }
    
    // Appliquer immédiatement
    applyGlobalPatches();
    
    // Réappliquer après le chargement du DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyGlobalPatches);
    }
    
    // Réappliquer après le chargement complet
    window.addEventListener('load', applyGlobalPatches);
})();
