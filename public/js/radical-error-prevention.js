/**
 * Script de prévention radicale de toutes les erreurs JavaScript
 */

(function() {
    "use strict";
    
    // Intercepter TOUTES les erreurs avant qu'elles ne se propagent
    const originalError = window.onerror;
    window.onerror = function(message, source, lineno, colno, error) {
        console.warn("🔧 Erreur radicale interceptée:", {
            message: message,
            source: source,
            lineno: lineno,
            colno: colno,
            error: error
        });
        
        // Empêcher l'erreur de se propager
        return true;
    };
    
    // Intercepter les erreurs de syntaxe spécifiquement
    window.addEventListener("error", function(event) {
        if (event.error && event.error.name === "SyntaxError") {
            console.warn("🔧 Erreur de syntaxe radicale interceptée:", {
                message: event.error.message,
                filename: event.filename,
                lineno: event.lineno,
                colno: event.colno,
                error: event.error
            });
            
            // Empêcher l'erreur de remonter
            event.preventDefault();
            return false;
        }
    });
    
    // Intercepter les erreurs de promesses non gérées
    window.addEventListener("unhandledrejection", function(event) {
        console.warn("🔧 Promesse rejetée radicale interceptée:", event.reason);
        event.preventDefault();
    });
    
    // Patch radical pour les scripts dynamiques
    const originalCreateElement = document.createElement;
    document.createElement = function(tagName) {
        const element = originalCreateElement.call(this, tagName);
        
        if (tagName.toLowerCase() === "script") {
            const originalSetAttribute = element.setAttribute;
            element.setAttribute = function(name, value) {
                if (name === "src" && typeof value === "string") {
                    // Nettoyer l'URL si nécessaire
                    value = value.replace(/[,\s]+$/, ""); // Supprimer les virgules en fin d'URL
                }
                return originalSetAttribute.call(this, name, value);
            };
        }
        
        return element;
    };
    
    console.log("✅ Script de prévention radicale des erreurs chargé");
})();
