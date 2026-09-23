/**
 * Recherche d'adresse et géolocalisation améliorée
 * Gestion complète de la localisation avec validation Laravel
 */

(function() {
    'use strict';
    
    console.log('🗺️ RECHERCHE ADRESSE AMÉLIORÉE - Initialisation...');
    
    // Variables globales
    let searchTimeout;
    let currentSuggestions = [];
    let isSearching = false;
    
    // Configuration
    const config = {
        minSearchLength: 3,
        debounceDelay: 300,
        maxSuggestions: 10,
        searchHistoryKey: 'address_search_history',
        maxHistoryItems: 20
    };
    
    // Fonction de recherche d'adresse
    function searchAddress(query) {
        if (!query || query.length < config.minSearchLength) {
            hideSuggestions();
            return;
        }
        
        console.log('🔍 Recherche d\'adresse:', query);
        showSearchLoading(true);
        
        // Annuler la recherche précédente
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }
        
        // Délai de débounce
        searchTimeout = setTimeout(() => {
            performAddressSearch(query);
        }, config.debounceDelay);
    }
    
    // Fonction de recherche effective
    function performAddressSearch(query) {
        isSearching = true;
        
        // Recherche avec Nominatim (OpenStreetMap)
        const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=ma&limit=${config.maxSuggestions}&addressdetails=1`;
        
        fetch(url, {
            headers: {
                'User-Agent': 'EVON-Charging-Points/1.0'
            }
        })
        .then(response => response.json())
        .then(data => {
            console.log('✅ Résultats de recherche:', data);
            displaySuggestions(data, query);
            addToSearchHistory(query);
        })
        .catch(error => {
            console.error('❌ Erreur de recherche:', error);
            showSearchError('Erreur lors de la recherche d\'adresse');
        })
        .finally(() => {
            isSearching = false;
            showSearchLoading(false);
        });
    }
    
    // Fonction d'affichage des suggestions
    function displaySuggestions(results, query) {
        const suggestionsContainer = document.getElementById('address_suggestions');
        if (!suggestionsContainer) return;
        
        if (results.length === 0) {
            suggestionsContainer.innerHTML = `
                <div class="p-3 text-center text-gray-500 dark:text-gray-400">
                    <svg class="h-6 w-6 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0112 15c-2.34 0-4.29-1.009-5.824-2.709M15 6.291A7.962 7.962 0 0012 5c-2.34 0-4.29 1.009-5.824 2.709"></path>
                    </svg>
                    <p class="text-sm">Aucun résultat trouvé pour "${query}"</p>
                </div>
            `;
        } else {
            let html = '';
            results.forEach((result, index) => {
                const address = result.display_name;
                const lat = parseFloat(result.lat);
                const lng = parseFloat(result.lon);
                
                html += `
                    <div class="suggestion-item p-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer border-b border-gray-100 dark:border-gray-700 last:border-b-0" 
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
                                    ${address}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    ${lat.toFixed(6)}, ${lng.toFixed(6)}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            suggestionsContainer.innerHTML = html;
            
            // Ajouter les événements de clic
            suggestionsContainer.querySelectorAll('.suggestion-item').forEach(item => {
                item.addEventListener('click', function() {
                    selectAddress(this);
                });
            });
        }
        
        showSuggestions();
    }
    
    // Fonction de sélection d'adresse
    function selectAddress(element) {
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
        updateCoordinates(lat, lng);
        
        // Mettre à jour la carte
        updateMap(lat, lng, address);
        
        // Masquer les suggestions
        hideSuggestions();
        
        // Ajouter à l'historique
        addToSearchHistory(address);
    }
    
    // Fonction de mise à jour des coordonnées
    function updateCoordinates(lat, lng) {
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
    
    // Fonction de mise à jour de la carte
    function updateMap(lat, lng, address) {
        if (window.smartMapDisplay) {
            window.smartMapDisplay(address);
        } else if (window.loadMapAtPosition) {
            window.loadMapAtPosition(lat, lng);
        } else {
            console.log('🗺️ Fonction de carte non disponible');
        }
    }
    
    // Fonction de géolocalisation
    function getCurrentLocation() {
        console.log('📍 Géolocalisation en cours...');
        
        if (!navigator.geolocation) {
            showLocationError('Géolocalisation non supportée par ce navigateur');
            return;
        }
        
        const options = {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 300000
        };
        
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                console.log('✅ Position obtenue:', lat, lng);
                
                // Géocoder inverse pour obtenir l'adresse
                reverseGeocode(lat, lng, function(address) {
                    if (address) {
                        const searchField = document.getElementById('location_search');
                        if (searchField) {
                            searchField.value = address;
                        }
                    }
                    
                    // Mettre à jour les coordonnées
                    updateCoordinates(lat, lng);
                    
                    // Mettre à jour la carte
                    updateMap(lat, lng, address);
                    
                    showLocationSuccess('Position actuelle détectée');
                });
            },
            function(error) {
                console.error('❌ Erreur de géolocalisation:', error);
                let message = 'Erreur de géolocalisation';
                
                switch (error.code) {
                    case error.PERMISSION_DENIED:
                        message = 'Permission de géolocalisation refusée';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        message = 'Position non disponible';
                        break;
                    case error.TIMEOUT:
                        message = 'Délai de géolocalisation dépassé';
                        break;
                }
                
                showLocationError(message);
            },
            options
        );
    }
    
    // Fonction de géocodage inverse
    function reverseGeocode(lat, lng, callback) {
        const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1`;
        
        fetch(url, {
            headers: {
                'User-Agent': 'EVON-Charging-Points/1.0'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data && data.display_name) {
                console.log('✅ Géocodage inverse réussi:', data.display_name);
                callback(data.display_name);
            } else {
                console.log('⚠️ Aucun résultat de géocodage inverse');
                callback(null);
            }
        })
        .catch(error => {
            console.error('❌ Erreur de géocodage inverse:', error);
            callback(null);
        });
    }
    
    // Fonction d'affichage des suggestions
    function showSuggestions() {
        const suggestionsContainer = document.getElementById('address_suggestions');
        if (suggestionsContainer) {
            suggestionsContainer.classList.remove('hidden');
        }
    }
    
    // Fonction de masquage des suggestions
    function hideSuggestions() {
        const suggestionsContainer = document.getElementById('address_suggestions');
        if (suggestionsContainer) {
            suggestionsContainer.classList.add('hidden');
        }
    }
    
    // Fonction d'affichage du chargement
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
    
    // Fonction d'affichage des erreurs
    function showSearchError(message) {
        showLocationStatus(message, 'error');
    }
    
    function showLocationError(message) {
        showLocationStatus(message, 'error');
    }
    
    function showLocationSuccess(message) {
        showLocationStatus(message, 'success');
    }
    
    // Fonction de mise à jour du statut
    function showLocationStatus(message, type) {
        const statusElement = document.getElementById('location_status_text');
        const statusContainer = document.getElementById('location_status');
        
        if (statusElement) {
            statusElement.textContent = message;
        }
        
        if (statusContainer) {
            statusContainer.classList.remove('hidden');
            
            // Changer la couleur selon le type
            statusContainer.className = `mt-2 text-xs ${
                type === 'error' ? 'text-red-500' :
                type === 'success' ? 'text-green-500' :
                'text-gray-500 dark:text-gray-400'
            }`;
        }
        
        console.log('📊 Statut localisation:', message);
    }
    
    // Fonction de gestion de l'historique
    function addToSearchHistory(query) {
        if (!query || query.trim() === '') return;
        
        let history = getSearchHistory();
        
        // Ajouter la nouvelle recherche
        history.unshift(query.trim());
        
        // Supprimer les doublons
        history = [...new Set(history)];
        
        // Limiter le nombre d'éléments
        history = history.slice(0, config.maxHistoryItems);
        
        // Sauvegarder
        localStorage.setItem(config.searchHistoryKey, JSON.stringify(history));
        
        console.log('📚 Historique mis à jour:', history);
    }
    
    function getSearchHistory() {
        const history = localStorage.getItem(config.searchHistoryKey);
        return history ? JSON.parse(history) : [];
    }
    
    function showSearchHistory() {
        const history = getSearchHistory();
        
        if (history.length === 0) {
            showLocationStatus('Aucun historique de recherche', 'info');
            return;
        }
        
        // Créer une modal d'historique
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Historique des recherches</h3>
                    <button class="text-gray-400 hover:text-gray-600" onclick="this.closest('.fixed').remove()">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="space-y-2 max-h-64 overflow-y-auto">
                    ${history.map(item => `
                        <div class="p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded cursor-pointer" onclick="selectHistoryItem('${item}')">
                            <div class="text-sm text-gray-900 dark:text-gray-100">${item}</div>
                        </div>
                    `).join('')}
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
        
        // Fonction globale pour sélectionner un élément d'historique
        window.selectHistoryItem = function(item) {
            const searchField = document.getElementById('location_search');
            if (searchField) {
                searchField.value = item;
                searchAddress(item);
            }
            document.body.removeChild(modal);
        };
    }
    
    // Initialisation
    function initLocationSearch() {
        console.log('🚀 Initialisation de la recherche d\'adresse...');
        
        // Champ de recherche
        const searchField = document.getElementById('location_search');
        if (searchField) {
            searchField.addEventListener('input', function() {
                const query = this.value.trim();
                if (query.length >= config.minSearchLength) {
                    searchAddress(query);
                } else {
                    hideSuggestions();
                }
            });
            
            // Masquer les suggestions en cliquant ailleurs
            document.addEventListener('click', function(e) {
                if (!e.target.closest('#address_suggestions') && !e.target.closest('#location_search')) {
                    hideSuggestions();
                }
            });
        }
        
        // Bouton de géolocalisation
        const autoLocateBtn = document.getElementById('auto-locate-btn');
        if (autoLocateBtn) {
            autoLocateBtn.addEventListener('click', function(e) {
                e.preventDefault();
                getCurrentLocation();
            });
        }
        
        // Bouton d'historique
        const historyBtn = document.getElementById('search-history-btn');
        if (historyBtn) {
            historyBtn.addEventListener('click', function(e) {
                e.preventDefault();
                showSearchHistory();
            });
        }
        
        console.log('✅ Recherche d\'adresse initialisée');
    }
    
    // Démarrer l'initialisation
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLocationSearch);
    } else {
        initLocationSearch();
    }
    
    console.log('✅ Recherche d\'adresse améliorée initialisée');
})();
