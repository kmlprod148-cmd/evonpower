/**
 * Charging Point 3D Viewer
 * Fichier de fallback pour éviter l'erreur 404
 */

console.log("Charging Point 3D Viewer chargé");

// Fonction de fallback pour le viewer 3D
window.ChargingPoint3DViewer = {
    init: function() {
        console.log("3D Viewer initialisé");
    },
    
    loadModel: function(modelPath) {
        console.log("Chargement du modèle 3D:", modelPath);
    },
    
    render: function() {
        console.log("Rendu 3D");
    }
};

// Initialiser automatiquement si disponible
document.addEventListener("DOMContentLoaded", function() {
    if (window.ChargingPoint3DViewer) {
        window.ChargingPoint3DViewer.init();
    }
});
