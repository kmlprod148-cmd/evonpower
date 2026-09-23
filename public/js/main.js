/**
 * Corrections de connexion serveur appliquées
 * Version: 2025-01-27
 */

// Charger les corrections de connexion
if (typeof window !== "undefined") {
    // Attendre que le DOM soit prêt
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", function() {
            loadConnectionFixes();
        });
    } else {
        loadConnectionFixes();
    }
}

function loadConnectionFixes() {
    // Charger le script de correction complet
    const script = document.createElement("script");
    script.src = "/js/complete-error-fix.js";
    script.onload = function() {
        console.log("✅ Corrections de connexion chargées");
    };
    script.onerror = function() {
        console.warn("⚠️ Impossible de charger les corrections de connexion");
    };
    document.head.appendChild(script);
}

/**
 * Main JavaScript file
 * Fichier principal pour éviter l'erreur 404
 * Version: 2025-09-29-09:25 - Enhanced error handling for production server
 */

console.log("Main.js chargé");

// Fonction de base pour les notifications
window.loadNotifications = function() {
    console.log("Chargement des notifications...");
    
    fetch("/api/v1/admin/notifications/unread", {
        method: "GET",
        headers: {
            "Accept": "application/json",
            "Content-Type": "application/json",
            "X-Requested-With": "XMLHttpRequest"
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log("Notifications chargées:", data);
    })
    .catch(error => {
        console.error("Erreur lors du chargement des notifications:", error);
    });
};

// Fonction pour charger les business profiles
window.loadBusinessProfiles = function(chargingPointId) {
    console.log("Chargement des business profiles pour le charging point:", chargingPointId);
    
    fetch(`/charging-points/${chargingPointId}/business-profiles`, {
        method: "GET",
        headers: {
            "Accept": "application/json",
            "Content-Type": "application/json",
            "X-Requested-With": "XMLHttpRequest"
        }
    })
    .then(response => {
        if (!response.ok) {
            if (response.status === 404) {
                throw new Error(`Point de charge non trouvé (${response.status})`);
            } else if (response.status === 401) {
                throw new Error(`Non autorisé - veuillez vous reconnecter (${response.status})`);
            } else if (response.status === 500) {
                throw new Error(`Erreur serveur - le point de charge n'existe pas ou n'est pas accessible (${response.status}). Veuillez réessayer plus tard ou contacter l'administrateur.`);
            } else {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
        }
        return response.json();
    })
    .then(data => {
        console.log("Business profiles chargés:", data);
        
        // Mettre à jour l'interface utilisateur avec les données
        if (data.success && data.data) {
            updateBusinessProfilesUI(data.data);
        } else {
            // Afficher un message d'erreur si l'API retourne success: false
            showBusinessProfilesError(data.message || 'Erreur inconnue');
        }
    })
    .catch(error => {
        console.error("Erreur lors du chargement des business profiles:", error);
        console.error("URL attempted:", `/charging-points/${chargingPointId}/business-profiles`);
        showBusinessProfilesError(error.message);
    });
};

// Fonction pour mettre à jour l'interface utilisateur
function updateBusinessProfilesUI(data) {
    // Mettre à jour les profils appliqués par l'admin
    const adminProfiles = data.admin_applied_profiles || [];
    const integratorProfiles = data.integrator_applied_profiles || [];
    
    console.log("Admin profiles:", adminProfiles.length);
    console.log("Integrator profiles:", integratorProfiles.length);
    
    // Mettre à jour les éléments de l'interface si ils existent
    const adminSection = document.querySelector('[data-section="admin-profiles"]');
    const integratorSection = document.querySelector('[data-section="integrator-profiles"]');
    
    if (adminSection) {
        if (adminProfiles.length > 0) {
            adminSection.innerHTML = `
                <div class="space-y-3">
                    ${adminProfiles.map(profile => `
                        <div class="bg-white rounded-lg p-4 border border-blue-200 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h5 class="font-semibold text-gray-900">${profile.name || 'Profil Admin'}</h5>
                                    <p class="text-sm text-gray-600">${profile.type || 'Admin → Intégrateur'}</p>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm text-gray-500">Frais Admin: ${profile.fees_details?.admin_fee_percentage || 0}%</div>
                                    <div class="text-sm text-gray-500">Frais Intégrateur: ${profile.fees_details?.integrator_fee_percentage || 0}%</div>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
        } else {
            adminSection.innerHTML = `
                <div class="text-center py-8">
                    <svg class="h-12 w-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="text-gray-500">Aucun profil appliqué par Admin</p>
                </div>
            `;
        }
    }
    
    if (integratorSection) {
        if (integratorProfiles.length > 0) {
            integratorSection.innerHTML = `
                <div class="space-y-3">
                    ${integratorProfiles.map(profile => `
                        <div class="bg-white rounded-lg p-4 border border-green-200 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h5 class="font-semibold text-gray-900">${profile.name || 'Profil Intégrateur'}</h5>
                                    <p class="text-sm text-gray-600">${profile.type || 'Intégrateur → Opérateur'}</p>
                                    ${profile.applied_by ? `<p class="text-xs text-gray-500">Appliqué par: ${profile.applied_by}</p>` : ''}
                                </div>
                                <div class="text-right">
                                    <div class="text-sm text-gray-500">Frais Admin: ${profile.fees_details?.admin_fee_percentage || 0}%</div>
                                    <div class="text-sm text-gray-500">Frais Intégrateur: ${profile.fees_details?.integrator_fee_percentage || 0}%</div>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
        } else {
            integratorSection.innerHTML = `
                <div class="text-center py-8">
                    <svg class="h-12 w-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="text-gray-500">Aucun profil appliqué par Intégrateurs</p>
                </div>
            `;
        }
    }
}

// Fonction pour afficher les erreurs des Business Profiles
function showBusinessProfilesError(message) {
    const adminSection = document.querySelector('[data-section="admin-profiles"]');
    const integratorSection = document.querySelector('[data-section="integrator-profiles"]');
    
    const errorHtml = `
        <div class="text-center py-8">
            <svg class="h-12 w-12 text-red-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-red-600 font-medium">Erreur lors du chargement</p>
            <p class="text-sm text-red-500 mt-1">${message}</p>
        </div>
    `;
    
    if (adminSection) {
        adminSection.innerHTML = errorHtml;
    }
    
    if (integratorSection) {
        integratorSection.innerHTML = errorHtml;
    }
}

// Fonction pour actualiser les Business Profiles
function refreshBusinessProfiles() {
    const path = window.location.pathname;
    const chargingPointMatch = path.match(/\/charging-points\/(\d+)/);
    if (chargingPointMatch) {
        const chargingPointId = chargingPointMatch[1];
        if (typeof window.loadBusinessProfiles === "function") {
            window.loadBusinessProfiles(chargingPointId);
        }
    }
}

// Fonction utilitaire pour accéder aux dataset de manière sécurisée
function safeDatasetAccess(element, property) {
    if (!element || !element.dataset) {
        console.warn('Element or dataset is null:', element);
        return null;
    }
    return element.dataset[property];
}

// Initialiser les fonctions au chargement de la page
document.addEventListener("DOMContentLoaded", function() {
    console.log("DOM chargé, initialisation des fonctions...");
    
    // Vérifications de sécurité pour éviter les erreurs dataset
    document.querySelectorAll('[data-*]').forEach(element => {
        if (!element.dataset) {
            console.warn('Element without dataset found:', element);
        }
    });
    
    if (typeof window.loadNotifications === "function") {
        window.loadNotifications();
    }
    
    // Charger les business profiles si on est sur une page de charging point
    const path = window.location.pathname;
    const chargingPointMatch = path.match(/\/charging-points\/(\d+)/);
    if (chargingPointMatch) {
        const chargingPointId = chargingPointMatch[1];
        if (typeof window.loadBusinessProfiles === "function") {
            window.loadBusinessProfiles(chargingPointId);
        }
    }
});
