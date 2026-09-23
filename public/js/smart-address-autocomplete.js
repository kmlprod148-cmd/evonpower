/**
 * Auto-complétion intelligente d'adresse
 * Solution complète pour la recherche et l'auto-complétion d'adresse
 */

(function() {
    'use strict';
    
    console.log('🔍 AUTO-COMPLÉTION INTELLIGENTE D\'ADRESSE - Initialisation...');
    
    // Configuration
    const CONFIG = {
        minSearchLength: 2,
        debounceDelay: 200,
        maxSuggestions: 10,
        nominatimUrl: 'https://nominatim.openstreetmap.org',
        countryCode: 'ma', // Maroc
        timeout: 5000
    };
    
    // Variables globales
    let searchTimeout = null;
    let isSearching = false;
    let currentSuggestions = [];
    let selectedIndex = -1;
    
    /**
     * Initialisation
     */
    function init() {
        console.log('🚀 Initialisation de l\'auto-complétion...');
        
        const searchField = document.getElementById('location_search');
        if (!searchField) {
            console.error('❌ Champ de recherche non trouvé');
            return;
        }
        
        setupEventListeners();
        console.log('✅ Auto-complétion initialisée');
    }
    
    /**
     * Configuration des événements
     */
    function setupEventListeners() {
        const searchField = document.getElementById('location_search');
        if (!searchField) return;
        
        console.log('🔧 Configuration des événements d\'auto-complétion...');
        
        // Événement de saisie
        searchField.addEventListener('input', handleInput);
        
        // Événements de clavier
        searchField.addEventListener('keydown', handleKeydown);
        
        // Événements de focus/blur
        searchField.addEventListener('focus', handleFocus);
        searchField.addEventListener('blur', handleBlur);
        
        // Masquer les suggestions en cliquant ailleurs
        document.addEventListener('click', handleDocumentClick);
        
        console.log('✅ Événements configurés');
    }
    
    /**
     * Gestion de la saisie
     */
    function handleInput(e) {
        const query = e.target.value.trim();
        
        // Annuler la recherche précédente
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }
        
        if (query.length < CONFIG.minSearchLength) {
            hideSuggestions();
            return;
        }
        
        // Délai de débounce pour éviter trop de requêtes
        searchTimeout = setTimeout(() => {
            searchAddress(query);
        }, CONFIG.debounceDelay);
    }
    
    /**
     * Gestion des touches du clavier
     */
    function handleKeydown(e) {
        const suggestions = document.getElementById('address_suggestions');
        if (!suggestions || suggestions.classList.contains('hidden')) {
            return;
        }
        
        const suggestionItems = suggestions.querySelectorAll('.suggestion-item');
        
        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                selectedIndex = Math.min(selectedIndex + 1, suggestionItems.length - 1);
                updateSelection(suggestionItems);
                break;
                
            case 'ArrowUp':
                e.preventDefault();
                selectedIndex = Math.max(selectedIndex - 1, -1);
                updateSelection(suggestionItems);
                break;
                
            case 'Enter':
                e.preventDefault();
                if (selectedIndex >= 0 && suggestionItems[selectedIndex]) {
                    selectSuggestion(suggestionItems[selectedIndex]);
                }
                break;
                
            case 'Escape':
                hideSuggestions();
                break;
        }
    }
    
    /**
     * Gestion du focus
     */
    function handleFocus(e) {
        const query = e.target.value.trim();
        if (query.length >= CONFIG.minSearchLength) {
            showSuggestions();
        }
    }
    
    /**
     * Gestion du blur
     */
    function handleBlur(e) {
        // Délai pour permettre le clic sur les suggestions
        setTimeout(() => {
            hideSuggestions();
        }, 200);
    }
    
    /**
     * Gestion du clic sur le document
     */
    function handleDocumentClick(e) {
        const suggestions = document.getElementById('address_suggestions');
        const searchField = document.getElementById('location_search');
        
        if (!suggestions || !searchField) return;
        
        if (!suggestions.contains(e.target) && !searchField.contains(e.target)) {
            hideSuggestions();
        }
    }
    
    /**
     * Recherche d'adresse
     */
    function searchAddress(query) {
        if (isSearching) return;
        
        console.log('🔍 Recherche d\'adresse:', query);
        isSearching = true;
        showSearchLoading(true);
        
        const url = `${CONFIG.nominatimUrl}/search?format=json&q=${encodeURIComponent(query)}&countrycodes=${CONFIG.countryCode}&limit=${CONFIG.maxSuggestions}&addressdetails=1&dedupe=1`;
        
        // Timeout pour éviter les requêtes qui traînent
        const timeoutId = setTimeout(() => {
            console.warn('⏰ Timeout de recherche d\'adresse');
            isSearching = false;
            showSearchLoading(false);
        }, CONFIG.timeout);
        
        fetch(url, {
            headers: {
                'User-Agent': 'EVON-Charging-Points/1.0'
            }
        })
        .then(response => {
            clearTimeout(timeoutId);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('✅ Résultats de recherche:', data);
            currentSuggestions = data;
            displaySuggestions(data, query);
            addToSearchHistory(query);
        })
        .catch(error => {
            clearTimeout(timeoutId);
            console.error('❌ Erreur de recherche:', error);
            showSearchError('Erreur lors de la recherche d\'adresse');
        })
        .finally(() => {
            isSearching = false;
            showSearchLoading(false);
        });
    }
    
    /**
     * Affichage des suggestions
     */
    function displaySuggestions(results, query) {
        const container = document.getElementById('address_suggestions');
        if (!container) return;
        
        if (results.length === 0) {
            container.innerHTML = `
                <div class="p-3 text-center text-gray-500 dark:text-gray-400">
                    <svg class="h-6 w-6 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0112 15c-2.34 0-4.29-1.009-5.824-2.709M15 6.291A7.962 7.962 0 0012 5c-2.34 0-4.29 1.009-5.824 2.709"></path>
                    </svg>
                    <p class="text-sm">Aucun résultat trouvé pour "${query}"</p>
                    <p class="text-xs text-gray-400 mt-1">Essayez avec un autre terme</p>
                </div>
            `;
        } else {
            let html = '';
            results.forEach((result, index) => {
                const lat = parseFloat(result.lat);
                const lng = parseFloat(result.lon);
                const address = result.display_name;
                const type = result.type || 'lieu';
                
                html += `
                    <div class="suggestion-item p-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer border-b border-gray-100 dark:border-gray-700 last:border-b-0 transition-colors duration-150" 
                         data-lat="${lat}" 
                         data-lng="${lng}" 
                         data-address="${address}"
                         data-index="${index}">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 mt-1">
                                <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                    ${highlightQuery(address, query)}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800">
                                        ${type}
                                    </span>
                                    <span>${lat.toFixed(6)}, ${lng.toFixed(6)}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
            
            // Ajouter les événements de clic
            container.querySelectorAll('.suggestion-item').forEach((item, index) => {
                item.addEventListener('click', () => selectSuggestion(item));
                item.addEventListener('mouseenter', () => {
                    selectedIndex = index;
                    updateSelection(container.querySelectorAll('.suggestion-item'));
                });
            });
        }
        
        showSuggestions();
    }
    
    /**
     * Mettre en surbrillance la requête dans le texte
     */
    function highlightQuery(text, query) {
        if (!query) return text;
        
        const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
        return text.replace(regex, '<mark class="bg-yellow-200 text-yellow-800 px-1 rounded">$1</mark>');
    }
    
    /**
     * Mettre à jour la sélection
     */
    function updateSelection(items) {
        items.forEach((item, index) => {
            if (index === selectedIndex) {
                item.classList.add('bg-emerald-50', 'dark:bg-emerald-900/20');
                item.classList.remove('hover:bg-gray-50', 'dark:hover:bg-gray-700');
            } else {
                item.classList.remove('bg-emerald-50', 'dark:bg-emerald-900/20');
                item.classList.add('hover:bg-gray-50', 'dark:hover:bg-gray-700');
            }
        });
    }
    
    /**
     * Sélectionner une suggestion
     */
    function selectSuggestion(element) {
        const lat = parseFloat(element.dataset.lat);
        const lng = parseFloat(element.dataset.lng);
        const address = element.dataset.address;
        
        console.log('📍 Adresse sélectionnée:', address, lat, lng);
        
        // Mettre à jour le champ de recherche
        const searchField = document.getElementById('location_search');
        if (searchField) {
            searchField.value = address;
        }
        
        // Mettre à jour les coordonnées
        updateCoordinateFields(lat, lng);
        
        // Mettre à jour la carte si disponible
        if (window.ultimateMapAddressSolution && window.ultimateMapAddressSolution.isInitialized()) {
            const coords = { lat, lng };
            window.ultimateMapAddressSolution.updateMap(coords);
        }
        
        // Masquer les suggestions
        hideSuggestions();
        
        // Ajouter à l'historique
        addToSearchHistory(address);
        
        // Réinitialiser la sélection
        selectedIndex = -1;
        
        updateStatus(`Adresse sélectionnée: ${address}`, 'success');
    }
    
    /**
     * Mise à jour des champs de coordonnées
     */
    function updateCoordinateFields(lat, lng) {
        const latField = document.getElementById('latitude');
        const lngField = document.getElementById('longitude');
        
        if (latField) {
            latField.value = lat.toFixed(7);
            latField.dispatchEvent(new Event('input', { bubbles: true }));
        }
        if (lngField) {
            lngField.value = lng.toFixed(7);
            lngField.dispatchEvent(new Event('input', { bubbles: true }));
        }
        
        console.log('📍 Coordonnées mises à jour:', lat, lng);
    }
    
    /**
     * Gestion de l'historique de recherche
     */
    function addToSearchHistory(query) {
        if (!query || query.trim() === '') return;
        
        let history = getSearchHistory();
        history.unshift(query.trim());
        history = [...new Set(history)].slice(0, 20);
        
        localStorage.setItem('address_search_history', JSON.stringify(history));
        console.log('📚 Historique mis à jour:', history);
    }
    
    function getSearchHistory() {
        const history = localStorage.getItem('address_search_history');
        return history ? JSON.parse(history) : [];
    }
    
    /**
     * Fonctions d'interface utilisateur
     */
    function showSuggestions() {
        const container = document.getElementById('address_suggestions');
        if (container) {
            container.classList.remove('hidden');
        }
    }
    
    function hideSuggestions() {
        const container = document.getElementById('address_suggestions');
        if (container) {
            container.classList.add('hidden');
        }
        selectedIndex = -1;
    }
    
    function showSearchLoading(show) {
        const loadingElement = document.getElementById('search-loading');
        const iconElement = document.getElementById('search-icon');
        
        if (loadingElement && iconElement) {
            if (show) {
                loadingElement.classList.remove('hidden');
                iconElement.classList.add('hidden');
            } else {
                loadingElement.classList.add('hidden');
                iconElement.classList.remove('hidden');
            }
        }
    }
    
    function updateStatus(message, type = 'info') {
        console.log('📊 Statut:', message);
        
        const statusElement = document.getElementById('location_status_text');
        const statusContainer = document.getElementById('location_status');
        
        if (statusElement) {
            statusElement.textContent = message;
        }
        
        if (statusContainer) {
            statusContainer.classList.remove('hidden');
            statusContainer.className = `mt-2 text-xs ${
                type === 'error' ? 'text-red-500' :
                type === 'success' ? 'text-green-500' :
                'text-blue-500'
            }`;
        }
    }
    
    function showSearchError(message) {
        updateStatus(message, 'error');
    }
    
    /**
     * Exposer les fonctions globalement
     */
    window.smartAddressAutocomplete = {
        init,
        searchAddress,
        getCurrentSuggestions: () => currentSuggestions,
        getSearchHistory,
        clearHistory: () => {
            localStorage.removeItem('address_search_history');
            console.log('📚 Historique effacé');
        }
    };
    
    // Initialisation automatique
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    console.log('✅ Auto-complétion intelligente initialisée');
})();
