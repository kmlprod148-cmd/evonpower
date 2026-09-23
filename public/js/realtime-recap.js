/**
 * Récapitulatif en temps réel pour la création de borne
 * Met à jour automatiquement le récapitulatif lors de la saisie
 */

(function() {
    'use strict';
    
    console.log('📊 RÉCAPITULATIF TEMPS RÉEL - Initialisation...');
    
    // Configuration
    const CONFIG = {
        updateDelay: 300,
        animationDuration: 300,
        maxPreviewLength: 50
    };
    
    // Variables globales
    let updateTimeout = null;
    let isInitialized = false;
    
    // État du récapitulatif
    const recapState = {
        step1: {
            name: '',
            serialNumber: '',
            manufacturer: '',
            model: '',
            location: '',
            address: '',
            operator: '',
            group: '',
            status: '',
            description: '',
            coordinates: { lat: '', lng: '' }
        }
    };
    
    /**
     * Initialisation
     */
    function init() {
        console.log('🚀 Initialisation du récapitulatif temps réel...');
        
        // Vérifier que les éléments existent
        if (!document.getElementById('recap-step1')) {
            console.error('❌ Élément récapitulatif non trouvé');
            return;
        }
        
        setupEventListeners();
        updateRecap();
        isInitialized = true;
        
        console.log('✅ Récapitulatif temps réel initialisé');
    }
    
    /**
     * Configuration des événements
     */
    function setupEventListeners() {
        console.log('🔧 Configuration des événements...');
        
        // Champs de l'étape 1
        const fields = [
            'name', 'serial_number', 'manufacturer', 'model', 'location',
            'location_search', 'operator_id', 'group_id', 'status', 'description',
            'latitude', 'longitude'
        ];
        
        fields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.addEventListener('input', handleFieldChange);
                field.addEventListener('change', handleFieldChange);
            }
        });
        
        // Boutons d'action
        const saveDraftBtn = document.getElementById('save-draft-btn');
        if (saveDraftBtn) {
            saveDraftBtn.addEventListener('click', saveDraft);
        }
        
        const previewBtn = document.getElementById('preview-btn');
        if (previewBtn) {
            previewBtn.addEventListener('click', showPreview);
        }
        
        console.log('✅ Événements configurés');
    }
    
    /**
     * Gestion des changements de champs
     */
    function handleFieldChange(e) {
        const field = e.target;
        const fieldId = field.id;
        const value = field.value.trim();
        
        console.log('📝 Champ modifié:', fieldId, value);
        
        // Annuler la mise à jour précédente
        if (updateTimeout) {
            clearTimeout(updateTimeout);
        }
        
        // Mettre à jour l'état
        updateFieldState(fieldId, value);
        
        // Programmer la mise à jour du récapitulatif
        updateTimeout = setTimeout(() => {
            updateRecap();
        }, CONFIG.updateDelay);
    }
    
    /**
     * Mise à jour de l'état d'un champ
     */
    function updateFieldState(fieldId, value) {
        switch (fieldId) {
            case 'name':
                recapState.step1.name = value;
                break;
            case 'serial_number':
                recapState.step1.serialNumber = value;
                break;
            case 'manufacturer':
                recapState.step1.manufacturer = value;
                break;
            case 'model':
                recapState.step1.model = value;
                break;
            case 'location':
                recapState.step1.location = value;
                break;
            case 'location_search':
                recapState.step1.address = value;
                break;
            case 'operator_id':
                recapState.step1.operator = getOperatorName(value);
                break;
            case 'group_id':
                recapState.step1.group = getGroupName(value);
                break;
            case 'status':
                recapState.step1.status = getStatusLabel(value);
                break;
            case 'description':
                recapState.step1.description = value;
                break;
            case 'latitude':
                recapState.step1.coordinates.lat = value;
                break;
            case 'longitude':
                recapState.step1.coordinates.lng = value;
                break;
        }
    }
    
    /**
     * Obtenir le nom de l'opérateur
     */
    function getOperatorName(operatorId) {
        if (!operatorId) return '';
        
        const select = document.getElementById('operator_id');
        if (select) {
            const option = select.querySelector(`option[value="${operatorId}"]`);
            return option ? option.textContent : '';
        }
        return '';
    }
    
    /**
     * Obtenir le nom du groupe
     */
    function getGroupName(groupId) {
        if (!groupId) return 'Non assigné';
        
        const select = document.getElementById('group_id');
        if (select) {
            const option = select.querySelector(`option[value="${groupId}"]`);
            return option ? option.textContent : '';
        }
        return '';
    }
    
    /**
     * Obtenir le libellé du statut
     */
    function getStatusLabel(status) {
        const statusLabels = {
            'online': 'En ligne',
            'offline': 'Hors ligne',
            'maintenance': 'Maintenance',
            'error': 'Erreur'
        };
        return statusLabels[status] || status;
    }
    
    /**
     * Mise à jour du récapitulatif
     */
    function updateRecap() {
        console.log('📊 Mise à jour du récapitulatif...');
        
        const recapElement = document.getElementById('recap-step1');
        if (!recapElement) return;
        
        // Générer le contenu HTML
        const content = generateRecapContent();
        
        // Mettre à jour avec animation
        recapElement.style.opacity = '0';
        recapElement.style.transform = 'translateY(10px)';
        
        setTimeout(() => {
            recapElement.innerHTML = content;
            recapElement.style.opacity = '1';
            recapElement.style.transform = 'translateY(0)';
        }, CONFIG.animationDuration / 2);
        
        // Mettre à jour le compteur de champs remplis
        updateFieldCounter();
    }
    
    /**
     * Génération du contenu du récapitulatif
     */
    function generateRecapContent() {
        const step1 = recapState.step1;
        const filledFields = getFilledFieldsCount();
        const totalFields = 10;
        
        let html = `
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Progression</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">${filledFields}/${totalFields} champs</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1">
                    <div class="bg-emerald-600 h-1 rounded-full transition-all duration-300" 
                         style="width: ${(filledFields / totalFields) * 100}%"></div>
                </div>
        `;
        
        // Informations de base
        if (step1.name) {
            html += `
                <div class="recap-item">
                    <div class="flex items-center gap-2">
                        <svg class="h-3 w-3 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Nom</span>
                    </div>
                    <div class="text-sm text-gray-900 dark:text-gray-100 mt-1">${step1.name}</div>
                </div>
            `;
        }
        
        if (step1.serialNumber) {
            html += `
                <div class="recap-item">
                    <div class="flex items-center gap-2">
                        <svg class="h-3 w-3 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">N° Série</span>
                    </div>
                    <div class="text-sm text-gray-900 dark:text-gray-100 mt-1">${step1.serialNumber}</div>
                </div>
            `;
        }
        
        if (step1.manufacturer) {
            html += `
                <div class="recap-item">
                    <div class="flex items-center gap-2">
                        <svg class="h-3 w-3 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Fabricant</span>
                    </div>
                    <div class="text-sm text-gray-900 dark:text-gray-100 mt-1">${step1.manufacturer}</div>
                </div>
            `;
        }
        
        if (step1.model) {
            html += `
                <div class="recap-item">
                    <div class="flex items-center gap-2">
                        <svg class="h-3 w-3 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Modèle</span>
                    </div>
                    <div class="text-sm text-gray-900 dark:text-gray-100 mt-1">${step1.model}</div>
                </div>
            `;
        }
        
        if (step1.address) {
            html += `
                <div class="recap-item">
                    <div class="flex items-center gap-2">
                        <svg class="h-3 w-3 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Adresse</span>
                    </div>
                    <div class="text-sm text-gray-900 dark:text-gray-100 mt-1">${truncateText(step1.address, CONFIG.maxPreviewLength)}</div>
                </div>
            `;
        }
        
        if (step1.operator) {
            html += `
                <div class="recap-item">
                    <div class="flex items-center gap-2">
                        <svg class="h-3 w-3 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Opérateur</span>
                    </div>
                    <div class="text-sm text-gray-900 dark:text-gray-100 mt-1">${step1.operator}</div>
                </div>
            `;
        }
        
        if (step1.coordinates.lat && step1.coordinates.lng) {
            html += `
                <div class="recap-item">
                    <div class="flex items-center gap-2">
                        <svg class="h-3 w-3 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Coordonnées</span>
                    </div>
                    <div class="text-sm text-gray-900 dark:text-gray-100 mt-1">
                        ${parseFloat(step1.coordinates.lat).toFixed(6)}, ${parseFloat(step1.coordinates.lng).toFixed(6)}
                    </div>
                </div>
            `;
        }
        
        if (step1.status) {
            html += `
                <div class="recap-item">
                    <div class="flex items-center gap-2">
                        <svg class="h-3 w-3 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Statut</span>
                    </div>
                    <div class="text-sm text-gray-900 dark:text-gray-100 mt-1">${step1.status}</div>
                </div>
            `;
        }
        
        // Message si aucun champ rempli
        if (filledFields === 0) {
            html += `
                <div class="text-center py-4">
                    <div class="text-gray-400 dark:text-gray-500 text-sm">
                        <svg class="h-8 w-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p>Aucune information saisie</p>
                        <p class="text-xs mt-1">Commencez à remplir le formulaire</p>
                    </div>
                </div>
            `;
        }
        
        html += '</div>';
        return html;
    }
    
    /**
     * Compter les champs remplis
     */
    function getFilledFieldsCount() {
        const step1 = recapState.step1;
        let count = 0;
        
        if (step1.name) count++;
        if (step1.serialNumber) count++;
        if (step1.manufacturer) count++;
        if (step1.model) count++;
        if (step1.location) count++;
        if (step1.address) count++;
        if (step1.operator) count++;
        if (step1.group && step1.group !== 'Non assigné') count++;
        if (step1.status) count++;
        if (step1.coordinates.lat && step1.coordinates.lng) count++;
        
        return count;
    }
    
    /**
     * Mise à jour du compteur de champs
     */
    function updateFieldCounter() {
        const filledFields = getFilledFieldsCount();
        const totalFields = 10;
        const percentage = Math.round((filledFields / totalFields) * 100);
        
        // Mettre à jour la barre de progression
        const progressBar = document.getElementById('progress-bar');
        if (progressBar) {
            progressBar.style.width = `${percentage}%`;
        }
        
        // Mettre à jour le pourcentage
        const progressPercentage = document.getElementById('progress-percentage');
        if (progressPercentage) {
            progressPercentage.textContent = `${percentage}%`;
        }
    }
    
    /**
     * Tronquer le texte
     */
    function truncateText(text, maxLength) {
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
    }
    
    /**
     * Sauvegarder le brouillon
     */
    function saveDraft() {
        console.log('💾 Sauvegarde du brouillon...');
        
        const draftData = {
            timestamp: new Date().toISOString(),
            step1: recapState.step1
        };
        
        localStorage.setItem('charging_point_draft', JSON.stringify(draftData));
        
        // Afficher une notification
        showNotification('Brouillon sauvegardé avec succès', 'success');
        
        console.log('✅ Brouillon sauvegardé');
    }
    
    /**
     * Afficher l'aperçu
     */
    function showPreview() {
        console.log('👁️ Affichage de l\'aperçu...');
        
        const previewData = recapState.step1;
        
        // Créer une modal d'aperçu
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-2xl w-full mx-4 max-h-96 overflow-y-auto">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Aperçu de la borne</h3>
                    <button class="text-gray-400 hover:text-gray-600" onclick="this.closest('.fixed').remove()">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="space-y-4">
                    ${generatePreviewContent(previewData)}
                </div>
                <div class="mt-6 flex justify-end">
                    <button onclick="this.closest('.fixed').remove()" 
                            class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">
                        Fermer
                    </button>
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
    
    /**
     * Générer le contenu de l'aperçu
     */
    function generatePreviewContent(data) {
        let html = '';
        
        if (data.name) {
            html += `<div><strong>Nom:</strong> ${data.name}</div>`;
        }
        if (data.serialNumber) {
            html += `<div><strong>N° Série:</strong> ${data.serialNumber}</div>`;
        }
        if (data.manufacturer) {
            html += `<div><strong>Fabricant:</strong> ${data.manufacturer}</div>`;
        }
        if (data.model) {
            html += `<div><strong>Modèle:</strong> ${data.model}</div>`;
        }
        if (data.address) {
            html += `<div><strong>Adresse:</strong> ${data.address}</div>`;
        }
        if (data.operator) {
            html += `<div><strong>Opérateur:</strong> ${data.operator}</div>`;
        }
        if (data.coordinates.lat && data.coordinates.lng) {
            html += `<div><strong>Coordonnées:</strong> ${data.coordinates.lat}, ${data.coordinates.lng}</div>`;
        }
        if (data.status) {
            html += `<div><strong>Statut:</strong> ${data.status}</div>`;
        }
        
        if (!html) {
            html = '<div class="text-gray-500 text-center py-4">Aucune information à afficher</div>';
        }
        
        return html;
    }
    
    /**
     * Afficher une notification
     */
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 notification-slide ${
            type === 'success' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' :
            type === 'error' ? 'bg-red-100 text-red-800 border border-red-200' :
            'bg-blue-100 text-blue-800 border border-blue-200'
        }`;
        notification.innerHTML = `
            <div class="flex items-center gap-2">
                <span>${type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️'}</span>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
    
    /**
     * Exposer les fonctions globalement
     */
    window.realtimeRecap = {
        init,
        updateRecap,
        saveDraft,
        showPreview,
        getState: () => recapState
    };
    
    // Initialisation automatique
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    console.log('✅ Récapitulatif temps réel initialisé');
})();
