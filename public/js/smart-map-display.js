/**
 * Affichage automatique et intelligent de la carte lors de la saisie d'adresse
 * La carte se charge automatiquement quand l'utilisateur tape une adresse
 */

(function() {
    'use strict';
    
    console.log('🗺️ AFFICHAGE INTELLIGENT CARTE - Initialisation...');
    
    // Variables globales
    window.map = null;
    window.marker = null;
    window.mapInitialized = false;
    window.currentAddress = '';
    
    // Fonction d'affichage intelligent de la carte
    function smartMapDisplay(address) {
        console.log('🗺️ Affichage intelligent de la carte pour:', address);
        
        // Vérifier le conteneur
        const mapContainer = document.getElementById('map');
        if (!mapContainer) {
            console.error('❌ Conteneur de carte non trouvé');
            return false;
        }
        
        // Vérifier Leaflet
        if (typeof L === 'undefined') {
            console.log('🔄 Leaflet non disponible, chargement...');
            loadLeafletForSmartDisplay();
            return false;
        }
        
        try {
            // Nettoyer le conteneur
            mapContainer.innerHTML = '';
            
            // Coordonnées par défaut (Casablanca)
            let lat = 33.5731;
            let lng = -7.5898;
            
            // Essayer de géocoder l'adresse
            if (address && address.trim().length > 2) {
                console.log('🔍 Tentative de géocodage pour:', address);
                geocodeAddress(address, function(coords) {
                    if (coords) {
                        lat = coords.lat;
                        lng = coords.lng;
                        console.log('✅ Coordonnées trouvées:', lat, lng);
                    }
                    createSmartMap(lat, lng, address);
                });
            } else {
                createSmartMap(lat, lng, address);
            }
            
            return true;
            
        } catch (error) {
            console.error('❌ Erreur lors de l\'affichage intelligent:', error);
            return false;
        }
    }
    
    // Fonction de création de carte intelligente
    function createSmartMap(lat, lng, address) {
        console.log('🗺️ Création de carte intelligente:', lat, lng, address);
        
        const mapContainer = document.getElementById('map');
        
        try {
            // Créer la carte
            const map = L.map('map', {
                zoomControl: true,
                attributionControl: true
            }).setView([lat, lng], 15);
            
            // Ajouter les tuiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19
            }).addTo(map);
            
            // Ajouter le marqueur
            const marker = L.marker([lat, lng], {
                draggable: true,
                title: 'Faites glisser pour déplacer'
            }).addTo(map);
            
            // Événements du marqueur
            marker.on('dragend', function(ev) {
                const pos = ev.target.getLatLng();
                console.log('📍 Marqueur déplacé:', pos);
                
                // Mettre à jour les champs
                updateCoordinateFields(pos.lat, pos.lng);
                
                // Géocoder inverse pour obtenir l'adresse
                reverseGeocode(pos.lat, pos.lng, function(address) {
                    if (address) {
                        updateAddressField(address);
                    }
                });
            });
            
            // Événements de la carte
            map.on('click', function(e) {
                marker.setLatLng(e.latlng);
                console.log('🗺️ Carte cliquée:', e.latlng);
                
                // Mettre à jour les champs
                updateCoordinateFields(e.latlng.lat, e.latlng.lng);
                
                // Géocoder inverse pour obtenir l'adresse
                reverseGeocode(e.latlng.lat, e.latlng.lng, function(address) {
                    if (address) {
                        updateAddressField(address);
                    }
                });
            });
            
            // Stocker les références
            window.map = map;
            window.marker = marker;
            window.mapInitialized = true;
            
            console.log('✅ Carte intelligente créée avec succès !');
            
            // Mettre à jour l'interface
            updateMapStatus(`Carte affichée pour: ${address || 'Position par défaut'}`, 'success');
            
            return true;
            
        } catch (error) {
            console.error('❌ Erreur lors de la création de carte intelligente:', error);
            updateMapStatus('Erreur lors de l\'affichage de la carte', 'error');
            return false;
        }
    }
    
    // Fonction de géocodage d'adresse
    function geocodeAddress(address, callback) {
        console.log('🔍 Géocodage de l\'adresse:', address);
        
        // URL de géocodage Nominatim
        const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&countrycodes=ma&limit=1&addressdetails=1`;
        
        fetch(url, {
            headers: {
                'User-Agent': 'EVON-Charging-Points/1.0'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data && data.length > 0) {
                const result = data[0];
                const coords = {
                    lat: parseFloat(result.lat),
                    lng: parseFloat(result.lon)
                };
                console.log('✅ Géocodage réussi:', coords);
                callback(coords);
            } else {
                console.log('⚠️ Aucun résultat de géocodage trouvé');
                callback(null);
            }
        })
        .catch(error => {
            console.error('❌ Erreur de géocodage:', error);
            callback(null);
        });
    }
    
    // Fonction de géocodage inverse
    function reverseGeocode(lat, lng, callback) {
        console.log('🔍 Géocodage inverse:', lat, lng);
        
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
                console.log('⚠️ Aucun résultat de géocodage inverse trouvé');
                callback(null);
            }
        })
        .catch(error => {
            console.error('❌ Erreur de géocodage inverse:', error);
            callback(null);
        });
    }
    
    // Fonction de mise à jour des champs de coordonnées
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
    
    // Fonction de mise à jour du champ d'adresse
    function updateAddressField(address) {
        const addressField = document.getElementById('location_search');
        
        if (addressField) {
            addressField.value = address;
            addressField.dispatchEvent(new Event('input', { bubbles: true }));
        }
        
        console.log('🏠 Adresse mise à jour:', address);
    }
    
    // Fonction de chargement de Leaflet pour l'affichage intelligent
    function loadLeafletForSmartDisplay() {
        console.log('🔄 Chargement de Leaflet pour affichage intelligent...');
        
        // Charger le CSS
        const cssLink = document.createElement('link');
        cssLink.rel = 'stylesheet';
        cssLink.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        document.head.appendChild(cssLink);
        
        // Charger le JavaScript
        const script = document.createElement('script');
        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        script.onload = function() {
            console.log('✅ Leaflet chargé pour affichage intelligent');
            // Réessayer l'affichage intelligent
            if (window.currentAddress) {
                smartMapDisplay(window.currentAddress);
            }
        };
        script.onerror = function() {
            console.error('❌ Erreur lors du chargement de Leaflet');
            updateMapStatus('Erreur lors du chargement de Leaflet', 'error');
        };
        document.head.appendChild(script);
    }
    
    // Fonction de mise à jour du statut
    function updateMapStatus(message, type) {
        console.log('📊 Statut carte:', message);
        
        // Mettre à jour le texte de statut
        const statusElement = document.getElementById('location_status_text');
        if (statusElement) {
            statusElement.textContent = message;
        }
        
        // Afficher le statut
        const statusContainer = document.getElementById('location_status');
        if (statusContainer) {
            statusContainer.classList.remove('hidden');
        }
    }
    
    // Fonction de gestion de la saisie d'adresse
    function handleAddressInput() {
        const addressField = document.getElementById('location_search');
        
        if (addressField) {
            console.log('🔍 Gestion de la saisie d\'adresse activée');
            
            // Délai pour éviter trop de requêtes
            let timeout;
            
            addressField.addEventListener('input', function(e) {
                const address = e.target.value.trim();
                window.currentAddress = address;
                
                // Annuler le timeout précédent
                if (timeout) {
                    clearTimeout(timeout);
                }
                
                // Attendre 1 seconde après la dernière frappe
                timeout = setTimeout(() => {
                    if (address.length >= 3) {
                        console.log('🗺️ Affichage intelligent déclenché pour:', address);
                        smartMapDisplay(address);
                    } else if (address.length === 0) {
                        // Réinitialiser la carte si le champ est vide
                        console.log('🗺️ Réinitialisation de la carte');
                        smartMapDisplay('');
                    }
                }, 1000);
            });
        }
    }
    
    // Exposer les fonctions globalement
    window.smartMapDisplay = smartMapDisplay;
    window.createSmartMap = createSmartMap;
    window.geocodeAddress = geocodeAddress;
    window.reverseGeocode = reverseGeocode;
    
    // Initialisation
    function initSmartMapDisplay() {
        console.log('🚀 Initialisation de l\'affichage intelligent...');
        
        // Attendre que le DOM soit chargé
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                handleAddressInput();
                // Afficher la carte par défaut
                setTimeout(() => {
                    smartMapDisplay('');
                }, 1000);
            });
        } else {
            handleAddressInput();
            // Afficher la carte par défaut
            setTimeout(() => {
                smartMapDisplay('');
            }, 1000);
        }
    }
    
    // Démarrer l'initialisation
    initSmartMapDisplay();
    
    console.log('✅ Affichage intelligent de la carte initialisé');
})();
