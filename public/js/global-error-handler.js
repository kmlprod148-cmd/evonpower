/**
 * Gestionnaire d'erreurs JavaScript global
 */

(function() {
    "use strict";
    
    // Intercepter toutes les erreurs JavaScript
    window.addEventListener("error", function(event) {
        console.warn("🔧 Erreur JavaScript interceptée:", {
            message: event.message,
            filename: event.filename,
            lineno: event.lineno,
            colno: event.colno,
            error: event.error
        });
        
        // Empêcher l'erreur de remonter
        event.preventDefault();
        return false;
    });
    
    // Intercepter les erreurs de promesses non gérées
    window.addEventListener("unhandledrejection", function(event) {
        console.warn("🔧 Promesse rejetée interceptée:", event.reason);
        event.preventDefault();
    });
    
    // Patch pour les sélecteurs invalides
    const originalQuerySelectorAll = document.querySelectorAll;
    document.querySelectorAll = function(selector) {
        // Corriger les sélecteurs invalides
        if (selector.includes("[data-*]")) {
            console.warn("🔧 Sélecteur invalide [data-*] détecté et corrigé");
            selector = "[data-type], [data-duration], [data-time], [data-available]";
        }
        
        try {
            return originalQuerySelectorAll.call(this, selector);
        } catch (error) {
            console.error("🔧 Erreur querySelectorAll:", selector, error);
            return [];
        }
    };
    
    // Patch pour les accès dataset
    const originalDatasetAccess = Object.getOwnPropertyDescriptor(Element.prototype, "dataset");
    if (originalDatasetAccess) {
        Object.defineProperty(Element.prototype, "dataset", {
            get: function() {
                if (!this || this.nodeType !== 1) {
                    console.warn("🔧 Tentative d'accès à dataset sur un élément invalide");
                    return {};
                }
                return originalDatasetAccess.get.call(this);
            },
            configurable: true
        });
    }
    
    // Fonctions utilitaires globales
    window.safeAccess = function(obj, property, defaultValue = null) {
        if (obj === null || obj === undefined) {
            console.warn("🔧 Tentative d'accès à une propriété sur null/undefined:", property);
            return defaultValue;
        }
        return obj[property] !== undefined ? obj[property] : defaultValue;
    };
    
    window.safeCall = function(fn, context, ...args) {
        if (typeof fn !== "function") {
            console.warn("🔧 Tentative d'appel d'une non-fonction:", fn);
            return null;
        }
        try {
            return fn.apply(context, args);
        } catch (error) {
            console.error("🔧 Erreur lors de l'appel de fonction:", error);
            return null;
        }
    };
    
    console.log("✅ Gestionnaire d'erreurs JavaScript global chargé");
})();
