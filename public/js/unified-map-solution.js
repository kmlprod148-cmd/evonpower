/**
 * Solution unifiée pour la carte et la recherche d'adresse
 * Remplace tous les scripts de carte existants par une solution robuste
 */

(function() {
    'use strict';
    
    console.log('🗺️ SOLUTION UNIFIÉE CARTE - Initialisation...');
    
    // Configuration globale
    const CONFIG = {
        // Coordonnées par défaut (Casablanca, Maroc)
        defaultLat: 33.5731,
        defaultLng: -7.5898,
        defaultZoom: 13,
        
        // Configuration de recherche
        minSearchLength: 3,
        debounceDelay: 500,
        maxSuggestions: 8,
        
        // URLs des services
        nominatimUrl: 'https://nominatim.openstreetmap.org',
        leafletVersion: '1.9.4',
        
        // Timeouts
        geocodingTimeout: 10000,
        mapLoadTimeout: 5000
    };
    
    // Variables globales
    let map = null;
    let marker = null;
    let mapInitialized = false;
    let searchTimeout = null;
    let isSearching = false;
    
    // État de l'application
    const state = {
        currentAddress: '',
        currentCoords: { lat: CONFIG.defaultLat, lng: CONFIG.defaultLng },
        searchHistory: [],
        isGeolocating: false
    };
    
    /**
     * Initialisation principale
     */
    function init() {
        console.log('🚀 Initialisation de la solution unifiée...');
        
        // Vérifier les prérequis
        if (!document.getElementById('map')) {
            console.error('❌ Conteneur de carte non trouvé');
            return;
        }
        
        // Masquer immédiatement les éléments de chargement
        hideMapLoadingElements();
        
        // Charger Leaflet si nécessaire
        if (typeof L === 'undefined') {
            loadLeaflet().then(() => {
                initializeMap();
                setupEventListeners();
            }).catch(error => {
                console.error('❌ Erreur de chargement de Leaflet:', error);
                showError('Impossible de charger la bibliothèque de carte');
            });
        } else {
            initializeMap();
            setupEventListeners();
        }
    }
    
    /**
     * Chargement de Leaflet
     */
    function loadLeaflet() {
        return new Promise((resolve, reject) => {
            // Vérifier si déjà chargé
            if (typeof L !== 'undefined') {
                resolve();
                return;
            }
            
            console.log('🔄 Chargement de Leaflet...');
            
            // Charger CSS
            const cssLink = document.createElement('link');
            cssLink.rel = 'stylesheet';
            cssLink.href = `https://unpkg.com/leaflet@${CONFIG.leafletVersion}/dist/leaflet.css`;
            cssLink.onerror = () => reject(new Error('Erreur de chargement CSS Leaflet'));
            document.head.appendChild(cssLink);
            
            // Charger JS
            const script = document.createElement('script');
            script.src = `https://unpkg.com/leaflet@${CONFIG.leafletVersion}/dist/leaflet.js`;
            script.onload = () => {
                console.log('✅ Leaflet chargé avec succès');
                resolve();
            };
            script.onerror = () => reject(new Error('Erreur de chargement JS Leaflet'));
            document.head.appendChild(script);
        });
    }
    
    /**
     * Initialisation de la carte
     */
    function initializeMap() {
        console.log('🗺️ Initialisation de la carte...');
        
        try {
            const mapContainer = document.getElementById('map');
            if (!mapContainer) {
                throw new Error('Conteneur de carte non trouvé');
            }
            
            // Masquer les éléments de chargement
            hideMapLoadingElements();
            
            // Nettoyer le conteneur
            mapContainer.innerHTML = '';
            
            // Créer la carte
            map = L.map('map', {
                zoomControl: true,
                attributionControl: true,
                preferCanvas: true
            }).setView([state.currentCoords.lat, state.currentCoords.lng], CONFIG.defaultZoom);
            
            // Ajouter les tuiles OpenStreetMap
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19,
                subdomains: ['a', 'b', 'c']
            }).addTo(map);
            
            // Créer le marqueur
            marker = L.marker([state.currentCoords.lat, state.currentCoords.lng], {
                draggable: true,
                title: 'Faites glisser pour déplacer'
            }).addTo(map);
            
            // Événements du marqueur
            marker.on('dragend', handleMarkerDrag);
            
            // Événements de la carte
            map.on('click', handleMapClick);
            
            // Marquer comme initialisé
            mapInitialized = true;
            
            console.log('✅ Carte initialisée avec succès');
            updateStatus('Carte chargée avec succès', 'success');
            
            // Mettre à jour les champs de coordonnées
            updateCoordinateFields(state.currentCoords.lat, state.currentCoords.lng);
            
        } catch (error) {
            console.error('❌ Erreur d\'initialisation de la carte:', error);
            showError('Erreur lors de l\'initialisation de la carte');
        }
    }
    
    /**
     * Configuration des événements
     */
    function setupEventListeners() {
        console.log('🔧 Configuration des événements...');
        
        // Champ de recherche d'adresse
        const searchField = document.getElementById('location_search');
        if (searchField) {
            searchField.addEventListener('input', handleAddressInput);
            searchField.addEventListener('focus', showSearchTips);
            searchField.addEventListener('blur', hideSuggestions);
        }
        
        // Bouton de géolocalisation
        const geoBtn = document.getElementById('auto-locate-btn');
        if (geoBtn) {
            geoBtn.addEventListener('click', handleGeolocation);
        }
        
        // Bouton d'historique
        const historyBtn = document.getElementById('search-history-btn');
        if (historyBtn) {
            historyBtn.addEventListener('click', showSearchHistory);
        }
        
        // Boutons de diagnostic
        const forceBtn = document.getElementById('force-load-btn');
        if (forceBtn) {
            forceBtn.addEventListener('click', forceMapLoad);
        }
        
        const diagnoseBtn = document.getElementById('diagnose-btn');
        if (diagnoseBtn) {
            diagnoseBtn.addEventListener('click', diagnoseMap);
        }
        
        // Masquer les suggestions en cliquant ailleurs
        document.addEventListener('click', (e) => {
            if (!e.target.closest('#address_suggestions') && !e.target.closest('#location_search')) {
                hideSuggestions();
            }
        });
        
        console.log('✅ Événements configurés');
    }
    
    /**
     * Gestion de la saisie d'adresse
     */
    function handleAddressInput(e) {
        const query = e.target.value.trim();
        state.currentAddress = query;
        
        // Annuler la recherche précédente
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }
        
        if (query.length < CONFIG.minSearchLength) {
            hideSuggestions();
            return;
        }
        
        // Délai de débounce
        searchTimeout = setTimeout(() => {
            searchAddress(query);
        }, CONFIG.debounceDelay);
    }
    
    /**
     * Recherche d'adresse
     */
    function searchAddress(query) {
        if (isSearching) return;
        
        console.log('🔍 Recherche d\'adresse:', query);
        isSearching = true;
        showSearchLoading(true);
        
        const url = `${CONFIG.nominatimUrl}/search?format=json&q=${encodeURIComponent(query)}&countrycodes=ma&limit=${CONFIG.maxSuggestions}&addressdetails=1`;
        
        fetch(url, {
            headers: {
                'User-Agent': 'EVON-Charging-Points/1.0'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
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
                </div>
            `;
        } else {
            let html = '';
            results.forEach((result, index) => {
                const lat = parseFloat(result.lat);
                const lng = parseFloat(result.lon);
                const address = result.display_name;
                
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
            
            container.innerHTML = html;
            
            // Ajouter les événements de clic
            container.querySelectorAll('.suggestion-item').forEach(item => {
                item.addEventListener('click', () => selectAddress(item));
            });
        }
        
        showSuggestions();
    }
    
    /**
     * Sélection d'une adresse
     */
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
        state.currentCoords = { lat, lng };
        updateCoordinateFields(lat, lng);
        
        // Mettre à jour la carte
        if (map && marker) {
            map.setView([lat, lng], 15);
            marker.setLatLng([lat, lng]);
        }
        
        // Masquer les suggestions
        hideSuggestions();
        
        // Ajouter à l'historique
        addToSearchHistory(address);
        
        updateStatus(`Adresse sélectionnée: ${address}`, 'success');
    }
    
    /**
     * Géolocalisation
     */
    function handleGeolocation() {
        if (state.isGeolocating) return;
        
        console.log('📍 Démarrage de la géolocalisation...');
        state.isGeolocating = true;
        
        if (!navigator.geolocation) {
            showError('Géolocalisation non supportée par ce navigateur');
            state.isGeolocating = false;
            return;
        }
        
        const options = {
            enableHighAccuracy: true,
            timeout: CONFIG.geocodingTimeout,
            maximumAge: 300000
        };
        
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                console.log('✅ Position obtenue:', lat, lng);
                
                // Mettre à jour les coordonnées
                state.currentCoords = { lat, lng };
                updateCoordinateFields(lat, lng);
                
                // Mettre à jour la carte
                if (map && marker) {
                    map.setView([lat, lng], 15);
                    marker.setLatLng([lat, lng]);
                }
                
                // Géocoder inverse pour obtenir l'adresse
                reverseGeocode(lat, lng, (address) => {
                    if (address) {
                        const searchField = document.getElementById('location_search');
                        if (searchField) {
                            searchField.value = address;
                        }
                    }
                    updateStatus('Position actuelle détectée', 'success');
                });
                
                state.isGeolocating = false;
            },
            (error) => {
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
                
                showError(message);
                state.isGeolocating = false;
            },
            options
        );
    }
    
    /**
     * Géocodage inverse
     */
    function reverseGeocode(lat, lng, callback) {
        const url = `${CONFIG.nominatimUrl}/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1`;
        
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
    
    /**
     * Gestion du déplacement du marqueur
     */
    function handleMarkerDrag(e) {
        const pos = e.target.getLatLng();
        console.log('📍 Marqueur déplacé:', pos);
        
        // Mettre à jour les coordonnées
        state.currentCoords = { lat: pos.lat, lng: pos.lng };
        updateCoordinateFields(pos.lat, pos.lng);
        
        // Géocoder inverse pour obtenir l'adresse
        reverseGeocode(pos.lat, pos.lng, (address) => {
            if (address) {
                const searchField = document.getElementById('location_search');
                if (searchField) {
                    searchField.value = address;
                }
                updateStatus(`Position mise à jour: ${address}`, 'success');
            }
        });
    }
    
    /**
     * Gestion du clic sur la carte
     */
    function handleMapClick(e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;
        
        console.log('🗺️ Carte cliquée:', lat, lng);
        
        // Déplacer le marqueur
        if (marker) {
            marker.setLatLng([lat, lng]);
        }
        
        // Mettre à jour les coordonnées
        state.currentCoords = { lat, lng };
        updateCoordinateFields(lat, lng);
        
        // Géocoder inverse pour obtenir l'adresse
        reverseGeocode(lat, lng, (address) => {
            if (address) {
                const searchField = document.getElementById('location_search');
                if (searchField) {
                    searchField.value = address;
                }
                updateStatus(`Position sélectionnée: ${address}`, 'success');
            }
        });
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
    
    function showSearchHistory() {
        const history = getSearchHistory();
        
        if (history.length === 0) {
            updateStatus('Aucun historique de recherche', 'info');
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
    }
    
    /**
     * Masquer les éléments de chargement de la carte
     */
    function hideMapLoadingElements() {
        console.log('🔄 Masquage des éléments de chargement...');
        
        // Masquer l'élément de chargement
        const mapLoading = document.getElementById('map-loading');
        if (mapLoading) {
            mapLoading.style.display = 'none';
            console.log('✅ Élément de chargement masqué');
        }
        
        // Masquer l'élément placeholder
        const mapPlaceholder = document.getElementById('map-placeholder');
        if (mapPlaceholder) {
            mapPlaceholder.style.display = 'none';
            console.log('✅ Élément placeholder masqué');
        }
        
        // S'assurer que le conteneur de carte est visible
        const mapContainer = document.getElementById('map');
        if (mapContainer) {
            mapContainer.style.position = 'relative';
            mapContainer.style.zIndex = '1';
        }
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
    
    function showSearchTips() {
        const tipsElement = document.getElementById('search-tips');
        if (tipsElement) {
            tipsElement.classList.remove('hidden');
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
                'text-gray-500 dark:text-gray-400'
            }`;
        }
    }
    
    function showError(message) {
        updateStatus(message, 'error');
    }
    
    function showSearchError(message) {
        updateStatus(message, 'error');
    }
    
    /**
     * Fonctions de diagnostic et de réparation
     */
    function forceMapLoad() {
        console.log('🔧 Forçage du chargement de la carte...');
        
        if (mapInitialized && map) {
            updateStatus('Carte déjà chargée', 'success');
            return;
        }
        
        if (typeof L === 'undefined') {
            loadLeaflet().then(() => {
                initializeMap();
                updateStatus('Carte forcée avec succès', 'success');
            }).catch(error => {
                showError('Impossible de forcer le chargement de la carte');
            });
        } else {
            initializeMap();
            updateStatus('Carte forcée avec succès', 'success');
        }
    }
    
    function diagnoseMap() {
        console.group('🔍 DIAGNOSTIC CARTE');
        console.log('Leaflet disponible:', typeof L !== 'undefined');
        console.log('Carte initialisée:', mapInitialized);
        console.log('Objet carte:', !!map);
        console.log('Marqueur:', !!marker);
        console.log('Conteneur:', !!document.getElementById('map'));
        console.log('Coordonnées actuelles:', state.currentCoords);
        console.log('Adresse actuelle:', state.currentAddress);
        console.groupEnd();
        
        updateStatus('Diagnostic terminé - voir la console', 'info');
    }
    
    /**
     * Exposer les fonctions globalement
     */
    window.unifiedMapSolution = {
        init,
        forceMapLoad,
        diagnoseMap,
        getCurrentCoords: () => state.currentCoords,
        getCurrentAddress: () => state.currentAddress,
        isInitialized: () => mapInitialized
    };
    
    // Initialisation automatique
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    console.log('✅ Solution unifiée carte initialisée');
})();
