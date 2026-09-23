/**
 * Récapitulatif en temps réel amélioré
 * Mise à jour automatique lors de la saisie utilisateur
 */

(function() {
    'use strict';
    
    console.log('📊 RÉCAPITULATIF TEMPS RÉEL - Initialisation...');
    
    // Configuration des champs à surveiller
    const fieldsToWatch = {
        // Informations générales
        'name': { label: 'Nom de la borne', icon: '🏷️', step: 1 },
        'serial_number': { label: 'Numéro de série', icon: '🔢', step: 1 },
        'manufacturer': { label: 'Fabricant', icon: '🏭', step: 1 },
        'model': { label: 'Modèle', icon: '📱', step: 1 },
        'location': { label: 'Emplacement', icon: '📍', step: 1 },
        'status': { label: 'Statut', icon: '🟢', step: 1 },
        'group_id': { label: 'Groupe', icon: '👥', step: 1 },
        'description': { label: 'Description', icon: '📝', step: 1 },
        
        // Localisation
        'location_search': { label: 'Adresse', icon: '🗺️', step: 1 },
        'latitude': { label: 'Latitude', icon: '🌐', step: 1 },
        'longitude': { label: 'Longitude', icon: '🌐', step: 1 }
    };
    
    // Données du récapitulatif
    let recapData = {
        step1: {},
        step2: {},
        step3: {},
        step4: {}
    };
    
    // Fonction de mise à jour du récapitulatif
    function updateRecap() {
        console.log('📊 Mise à jour du récapitulatif...');
        
        // Calculer le pourcentage de progression
        const totalFields = Object.keys(fieldsToWatch).length;
        const filledFields = Object.values(recapData.step1).filter(value => value && value.trim() !== '').length;
        const progressPercentage = Math.round((filledFields / totalFields) * 100);
        
        // Mettre à jour la barre de progression
        updateProgressBar(progressPercentage);
        
        // Mettre à jour le contenu de l'étape 1
        updateStepContent(1, recapData.step1);
        
        // Mettre à jour le pourcentage affiché
        const progressElement = document.getElementById('progress-percentage');
        if (progressElement) {
            progressElement.textContent = progressPercentage + '%';
        }
        
        console.log('📊 Récapitulatif mis à jour:', progressPercentage + '%');
    }
    
    // Fonction de mise à jour de la barre de progression
    function updateProgressBar(percentage) {
        const progressBar = document.getElementById('progress-bar');
        if (progressBar) {
            progressBar.style.width = percentage + '%';
            
            // Changer la couleur selon le pourcentage
            if (percentage < 25) {
                progressBar.className = 'bg-red-500 h-2 rounded-full transition-all duration-300 ease-out';
            } else if (percentage < 50) {
                progressBar.className = 'bg-yellow-500 h-2 rounded-full transition-all duration-300 ease-out';
            } else if (percentage < 75) {
                progressBar.className = 'bg-blue-500 h-2 rounded-full transition-all duration-300 ease-out';
            } else {
                progressBar.className = 'bg-emerald-600 h-2 rounded-full transition-all duration-300 ease-out';
            }
        }
    }
    
    // Fonction de mise à jour du contenu d'une étape
    function updateStepContent(step, data) {
        const stepElement = document.getElementById(`recap-step${step}`);
        if (!stepElement) return;
        
        // Filtrer les données non vides
        const filledData = Object.entries(data).filter(([key, value]) => value && value.trim() !== '');
        
        if (filledData.length === 0) {
            stepElement.innerHTML = '<div class="text-gray-500 dark:text-gray-400 animate-pulse">Aucune information saisie</div>';
            return;
        }
        
        // Créer le contenu HTML
        let html = '';
        filledData.forEach(([key, value]) => {
            const fieldConfig = fieldsToWatch[key];
            if (fieldConfig) {
                html += `
                    <div class="recap-item-enter flex items-center gap-2 p-2 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <span class="text-lg">${fieldConfig.icon}</span>
                        <div class="flex-1 min-w-0">
                            <div class="text-xs font-medium text-gray-600 dark:text-gray-400">${fieldConfig.label}</div>
                            <div class="text-sm text-gray-900 dark:text-gray-100 truncate">${value}</div>
                        </div>
                    </div>
                `;
            }
        });
        
        stepElement.innerHTML = html;
        
        // Ajouter l'animation d'entrée
        const items = stepElement.querySelectorAll('.recap-item-enter');
        items.forEach((item, index) => {
            setTimeout(() => {
                item.style.opacity = '1';
                item.style.transform = 'translateX(0)';
            }, index * 100);
        });
    }
    
    // Fonction de surveillance des champs
    function watchField(fieldId) {
        const field = document.getElementById(fieldId);
        if (!field) return;
        
        const fieldConfig = fieldsToWatch[fieldId];
        if (!fieldConfig) return;
        
        // Événement de saisie
        field.addEventListener('input', function() {
            const value = this.value.trim();
            const stepKey = `step${fieldConfig.step}`;
            
            // Mettre à jour les données
            if (value) {
                recapData[stepKey][fieldId] = value;
            } else {
                delete recapData[stepKey][fieldId];
            }
            
            // Mettre à jour le récapitulatif
            updateRecap();
            
            console.log('📊 Champ mis à jour:', fieldId, value);
        });
        
        // Événement de changement (pour les selects)
        field.addEventListener('change', function() {
            const value = this.value.trim();
            const stepKey = `step${fieldConfig.step}`;
            
            // Mettre à jour les données
            if (value) {
                recapData[stepKey][fieldId] = value;
            } else {
                delete recapData[stepKey][fieldId];
            }
            
            // Mettre à jour le récapitulatif
            updateRecap();
            
            console.log('📊 Champ changé:', fieldId, value);
        });
        
        console.log('👁️ Surveillance activée pour:', fieldId);
    }
    
    // Fonction de surveillance de tous les champs
    function watchAllFields() {
        console.log('👁️ Activation de la surveillance de tous les champs...');
        
        Object.keys(fieldsToWatch).forEach(fieldId => {
            watchField(fieldId);
        });
        
        console.log('✅ Surveillance de tous les champs activée');
    }
    
    // Fonction de sauvegarde de brouillon
    function saveDraft() {
        console.log('💾 Sauvegarde du brouillon...');
        
        const draftData = {
            timestamp: new Date().toISOString(),
            data: recapData,
            progress: calculateProgress()
        };
        
        localStorage.setItem('charging_point_draft', JSON.stringify(draftData));
        
        // Afficher une notification
        showNotification('Brouillon sauvegardé avec succès !', 'success');
        
        console.log('💾 Brouillon sauvegardé:', draftData);
    }
    
    // Fonction de chargement de brouillon
    function loadDraft() {
        console.log('📂 Chargement du brouillon...');
        
        const draftData = localStorage.getItem('charging_point_draft');
        if (draftData) {
            try {
                const parsed = JSON.parse(draftData);
                recapData = parsed.data || {};
                
                // Remplir les champs avec les données du brouillon
                Object.entries(recapData.step1 || {}).forEach(([fieldId, value]) => {
                    const field = document.getElementById(fieldId);
                    if (field) {
                        field.value = value;
                    }
                });
                
                // Mettre à jour le récapitulatif
                updateRecap();
                
                showNotification('Brouillon chargé avec succès !', 'success');
                console.log('📂 Brouillon chargé:', parsed);
            } catch (error) {
                console.error('❌ Erreur lors du chargement du brouillon:', error);
            }
        }
    }
    
    // Fonction de calcul de progression
    function calculateProgress() {
        const totalFields = Object.keys(fieldsToWatch).length;
        const filledFields = Object.values(recapData.step1).filter(value => value && value.trim() !== '').length;
        return Math.round((filledFields / totalFields) * 100);
    }
    
    // Fonction d'affichage de notification
    function showNotification(message, type = 'info') {
        // Créer l'élément de notification
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 transition-all duration-300 ${
            type === 'success' ? 'bg-emerald-500 text-white' :
            type === 'error' ? 'bg-red-500 text-white' :
            'bg-blue-500 text-white'
        }`;
        notification.textContent = message;
        
        // Ajouter au DOM
        document.body.appendChild(notification);
        
        // Supprimer après 3 secondes
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }
    
    // Fonction d'aperçu
    function showPreview() {
        console.log('👁️ Affichage de l\'aperçu...');
        
        const previewData = {
            informations: recapData.step1,
            progression: calculateProgress(),
            timestamp: new Date().toLocaleString()
        };
        
        // Créer une modal d'aperçu
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-2xl w-full mx-4 max-h-96 overflow-y-auto">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Aperçu du formulaire</h3>
                    <button class="text-gray-400 hover:text-gray-600" onclick="this.closest('.fixed').remove()">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="space-y-4">
                    ${Object.entries(previewData.informations).map(([key, value]) => {
                        const fieldConfig = fieldsToWatch[key];
                        return fieldConfig ? `
                            <div class="flex items-center gap-3">
                                <span class="text-lg">${fieldConfig.icon}</span>
                                <div>
                                    <div class="text-sm font-medium text-gray-600 dark:text-gray-400">${fieldConfig.label}</div>
                                    <div class="text-gray-900 dark:text-gray-100">${value}</div>
                                </div>
                            </div>
                        ` : '';
                    }).join('')}
                </div>
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        Progression: ${previewData.progression}% | 
                        Dernière mise à jour: ${previewData.timestamp}
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Fermer en cliquant à l'extérieur
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                document.body.removeChild(modal);
            }
        });
    }
    
    // Initialisation
    function initRealtimeRecap() {
        console.log('🚀 Initialisation du récapitulatif temps réel...');
        
        // Attendre que le DOM soit chargé
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                watchAllFields();
                loadDraft();
            });
        } else {
            watchAllFields();
            loadDraft();
        }
        
        // Gestion des boutons
        const saveDraftBtn = document.getElementById('save-draft-btn');
        if (saveDraftBtn) {
            saveDraftBtn.addEventListener('click', saveDraft);
        }
        
        const previewBtn = document.getElementById('preview-btn');
        if (previewBtn) {
            previewBtn.addEventListener('click', showPreview);
        }
        
        console.log('✅ Récapitulatif temps réel initialisé');
    }
    
    // Exposer les fonctions globalement
    window.updateRecap = updateRecap;
    window.saveDraft = saveDraft;
    window.loadDraft = loadDraft;
    window.showPreview = showPreview;
    
    // Démarrer l'initialisation
    initRealtimeRecap();
    
    console.log('✅ Récapitulatif temps réel amélioré initialisé');
})();
