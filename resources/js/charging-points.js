/**
 * Fichier JavaScript pour la gestion des formulaires de bornes de recharge
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser toutes les fonctionnalités du formulaire
    initTabNavigation();
    initDependentSelects();
    initMapFunctionality();
    initConditionalFields();
    initFormDataPersistence();
    setupInputMasks();
    
    // Empêcher la soumission accidentelle en appuyant sur Entrée
    const form = document.getElementById('chargingPointForm');
    if (form) {
        form.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                e.preventDefault();
            }
        });
        
        // Validation complète avant soumission
        form.addEventListener('submit', function(e) {
            const tabs = document.querySelectorAll('.tab-btn');
            let formIsValid = true;
            
            // Vérifier chaque onglet
            for (let i = 0; i < tabs.length; i++) {
                if (!validateCurrentTab(i)) {
                    formIsValid = false;
                    // Aller à l'onglet avec erreur
                    tabs[i].click();
                    break;
                }
            }
            
            if (!formIsValid) {
                e.preventDefault();
                alert('Veuillez corriger les erreurs dans le formulaire avant de continuer.');
            }
        });
    }
});

/**
 * Initialisation de la navigation par onglets
 */
function initTabNavigation() {
    const tabs = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    const prevTabBtn = document.getElementById('prev-tab');
    const nextTabBtn = document.getElementById('next-tab');
    
    if (!tabs.length || !tabContents.length) return;
    
    let currentTabIndex = 0;
    
    // Fonction pour afficher un onglet spécifique
    function showTab(index) {
        // Désactiver tous les onglets
        tabs.forEach(tab => {
            tab.classList.remove('active', 'border-green-500', 'text-green-600');
            tab.classList.add('border-transparent', 'text-gray-500');
        });
        
        // Cacher tous les contenus d'onglets
        tabContents.forEach(content => content.classList.add('hidden'));
        
        // Activer l'onglet sélectionné
        tabs[index].classList.add('active', 'border-green-500', 'text-green-600');
        tabs[index].classList.remove('border-transparent', 'text-gray-500');
        
        // Afficher le contenu de l'onglet sélectionné
        tabContents[index].classList.remove('hidden');
        
        // Mettre à jour l'index actuel
        currentTabIndex = index;
        
        // Mettre à jour les boutons Précédent/Suivant
        if (prevTabBtn) {
            prevTabBtn.disabled = (currentTabIndex === 0);
            prevTabBtn.classList.toggle('opacity-50', currentTabIndex === 0);
        }
        
        if (nextTabBtn) {
            nextTabBtn.disabled = (currentTabIndex === tabs.length - 1);
            nextTabBtn.classList.toggle('opacity-50', currentTabIndex === tabs.length - 1);
            nextTabBtn.style.display = (currentTabIndex === tabs.length - 1) ? 'none' : 'inline-flex';
        }
        
        // Sauvegarder l'onglet actif dans localStorage
        localStorage.setItem('activeChargingPointTab', index);
    }
    
    // Ajouter des écouteurs d'événements aux onglets
    tabs.forEach((tab, index) => {
        tab.addEventListener('click', function() {
            showTab(index);
        });
    });
    
    // Configurer les boutons Précédent/Suivant
    if (prevTabBtn) {
        prevTabBtn.addEventListener('click', function() {
            if (currentTabIndex > 0) {
                showTab(currentTabIndex - 1);
            }
        });
    }
    
    if (nextTabBtn) {
        nextTabBtn.addEventListener('click', function() {
            if (validateCurrentTab(currentTabIndex)) {
                if (currentTabIndex < tabs.length - 1) {
                    showTab(currentTabIndex + 1);
                }
            }
        });
    }
    
    // Restaurer l'onglet actif depuis localStorage ou afficher le premier onglet
    const savedTabIndex = localStorage.getItem('activeChargingPointTab');
    showTab(savedTabIndex !== null ? parseInt(savedTabIndex) : 0);
}

/**
 * Validation des onglets avant de continuer
 */
function validateCurrentTab(tabIndex) {
    const tabContents = document.querySelectorAll('.tab-content');
    if (!tabContents[tabIndex]) return true;
    
    // Obtenir tous les champs requis dans l'onglet actuel
    const currentTabContent = tabContents[tabIndex];
    const requiredFields = currentTabContent.querySelectorAll('[required]');
    let isValid = true;
    
    // Vérifier chaque champ requis
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('border-red-500');
            
            // Créer un message d'erreur s'il n'existe pas déjà
            let errorMsg = field.nextElementSibling;
            if (!errorMsg || !errorMsg.classList.contains('error-message')) {
                errorMsg = document.createElement('p');
                errorMsg.className = 'error-message text-sm text-red-600 mt-1';
                errorMsg.textContent = 'Ce champ est requis';
                field.parentNode.insertBefore(errorMsg, field.nextSibling);
            }
            
            isValid = false;
        } else {
            field.classList.remove('border-red-500');
            
            // Supprimer le message d'erreur s'il existe
            const errorMsg = field.nextElementSibling;
            if (errorMsg && errorMsg.classList.contains('error-message')) {
                errorMsg.remove();
            }
        }
    });
    
    // Validation spécifique à chaque onglet
    switch(tabIndex) {
        case 0: // Informations de base
            // Valider le format du numéro de série
            const serialInput = document.getElementById('serial_number');
            if (serialInput && serialInput.value.trim() && !/^[A-Za-z0-9-]+$/.test(serialInput.value)) {
                serialInput.classList.add('border-red-500');
                let errorMsg = serialInput.nextElementSibling;
                if (!errorMsg || !errorMsg.classList.contains('error-message')) {
                    errorMsg = document.createElement('p');
                    errorMsg.className = 'error-message text-sm text-red-600 mt-1';
                    errorMsg.textContent = 'Format invalide (lettres, chiffres et tirets uniquement)';
                    serialInput.parentNode.insertBefore(errorMsg, serialInput.nextSibling);
                }
                isValid = false;
            }
            break;
            
        case 2: // Localisation
            // Vérifier que les coordonnées sont valides si renseignées
            const latInput = document.getElementById('latitude');
            const lngInput = document.getElementById('longitude');
            
            if (latInput && latInput.value && (isNaN(parseFloat(latInput.value)) || parseFloat(latInput.value) < -90 || parseFloat(latInput.value) > 90)) {
                latInput.classList.add('border-red-500');
                let errorMsg = latInput.nextElementSibling;
                if (!errorMsg || !errorMsg.classList.contains('error-message')) {
                    errorMsg = document.createElement('p');
                    errorMsg.className = 'error-message text-sm text-red-600 mt-1';
                    errorMsg.textContent = 'La latitude doit être entre -90 et 90';
                    latInput.parentNode.insertBefore(errorMsg, latInput.nextSibling);
                }
                isValid = false;
            }
            
            if (lngInput && lngInput.value && (isNaN(parseFloat(lngInput.value)) || parseFloat(lngInput.value) < -180 || parseFloat(lngInput.value) > 180)) {
                lngInput.classList.add('border-red-500');
                let errorMsg = lngInput.nextElementSibling;
                if (!errorMsg || !errorMsg.classList.contains('error-message')) {
                    errorMsg = document.createElement('p');
                    errorMsg.className = 'error-message text-sm text-red-600 mt-1';
                    errorMsg.textContent = 'La longitude doit être entre -180 et 180';
                    lngInput.parentNode.insertBefore(errorMsg, lngInput.nextSibling);
                }
                isValid = false;
            }
            break;
            
        case 3: // Informations techniques
            // Valider le format de l'adresse IP
            const ipInput = document.getElementById('ip_address');
            if (ipInput && ipInput.value.trim() && !/^(\d{1,3}\.){3}\d{1,3}$/.test(ipInput.value)) {
                ipInput.classList.add('border-red-500');
                let errorMsg = ipInput.nextElementSibling;
                if (!errorMsg || !errorMsg.classList.contains('error-message')) {
                    errorMsg = document.createElement('p');
                    errorMsg.className = 'error-message text-sm text-red-600 mt-1';
                    errorMsg.textContent = 'Format d\'adresse IP invalide';
                    ipInput.parentNode.insertBefore(errorMsg, ipInput.nextSibling);
                }
                isValid = false;
            }
            
            // Valider le format de l'adresse MAC
            const macInput = document.getElementById('mac_address');
            if (macInput && macInput.value.trim() && !/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/.test(macInput.value)) {
                macInput.classList.add('border-red-500');
                let errorMsg = macInput.nextElementSibling;
                if (!errorMsg || !errorMsg.classList.contains('error-message')) {
                    errorMsg = document.createElement('p');
                    errorMsg.className = 'error-message text-sm text-red-600 mt-1';
                    errorMsg.textContent = 'Format d\'adresse MAC invalide (ex: 00:1B:44:11:3A:B7)';
                    macInput.parentNode.insertBefore(errorMsg, macInput.nextSibling);
                }
                isValid = false;
            }
            break;
            
        case 5: // Connecteurs
            // Vérifier que chaque connecteur a une puissance positive
            const powerInputs = document.querySelectorAll('input[name$="[power]"]');
            powerInputs.forEach(input => {
                if (input.value && (isNaN(parseFloat(input.value)) || parseFloat(input.value) <= 0)) {
                    input.classList.add('border-red-500');
                    let errorMsg = input.nextElementSibling;
                    if (!errorMsg || !errorMsg.classList.contains('error-message')) {
                        errorMsg = document.createElement('p');
                        errorMsg.className = 'error-message text-sm text-red-600 mt-1';
                        errorMsg.textContent = 'La puissance doit être positive';
                        input.parentNode.insertBefore(errorMsg, input.nextSibling);
                    }
                    isValid = false;
                }
            });
            break;
    }
    
    if (!isValid) {
        // Scroll jusqu'au premier champ avec erreur
        const firstErrorField = currentTabContent.querySelector('.border-red-500');
        if (firstErrorField) {
            firstErrorField.scrollIntoView({behavior: 'smooth', block: 'center'});
            firstErrorField.focus();
        }
    }
    
    return isValid;
}

/**
 * Initialisation des sélecteurs dépendants (intégrateur, partenaire, groupe)
 */
function initDependentSelects() {
    const integratorSelect = document.getElementById('integrator_id');
    const partnerSelect = document.getElementById('partner_id');
    const groupSelect = document.getElementById('group_id');
    
    if (!integratorSelect || !partnerSelect || !groupSelect) return;
    
    // Fonction pour mettre à jour les options en fonction d'un filtre
    function updateSelectOptions(selectElement, filterAttribute, filterValue) {
        Array.from(selectElement.options).forEach(option => {
            // Toujours afficher l'option vide
            if (option.value === '') {
                option.hidden = false;
                return;
            }
            
            // Filtrer les options en fonction de l'attribut data
            const attributeValue = option.dataset[filterAttribute];
            
            // Si pas de filtre ou l'option correspond au filtre
            if (!filterValue || attributeValue === filterValue) {
                option.hidden = false;
            } else {
                option.hidden = true;
                // Désélectionner si cachée et actuellement sélectionnée
                if (option.selected) {
                    option.selected = false;
                    selectElement.selectedIndex = 0; // Sélectionner l'option vide
                }
            }
        });
    }
    
    // Intégrateur change -> mise à jour des partenaires et groupes
    integratorSelect.addEventListener('change', function() {
        const selectedIntegratorId = this.value;
        
        // Mettre à jour les partenaires filtrés par intégrateur
        updateSelectOptions(partnerSelect, 'integrator', selectedIntegratorId);
        
        // Mettre à jour les groupes filtrés par intégrateur
        updateSelectOptions(groupSelect, 'integrator', selectedIntegratorId);
    });
    
    // Partenaire change -> mise à jour des groupes
    partnerSelect.addEventListener('change', function() {
        const selectedPartnerId = this.value;
        
        // Mettre à jour les groupes filtrés par partenaire
        updateSelectOptions(groupSelect, 'partner', selectedPartnerId);
    });
    
    // Déclencher les filtres au chargement
    integratorSelect.dispatchEvent(new Event('change'));
    partnerSelect.dispatchEvent(new Event('change'));
}

/**
 * Initialisation de la carte Google Maps
 */
function initMapFunctionality() {
    // Vérifier si l'API Google Maps est chargée et si le conteneur de carte existe
    if (typeof google === 'undefined' || !document.getElementById('map')) {
        return;
    }
    
    // Éléments du DOM
    const mapElement = document.getElementById('map');
    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');
    const addressInput = document.getElementById('address');
    const cityInput = document.getElementById('city');
    const postalCodeInput = document.getElementById('postal_code');
    const countryInput = document.getElementById('country');
    
    // Coordonnées par défaut (Paris)
    const defaultLat = 48.864716;
    const defaultLng = 2.349014;
    
    // Utiliser les coordonnées existantes ou les valeurs par défaut
    const lat = latInput.value ? parseFloat(latInput.value) : defaultLat;
    const lng = lngInput.value ? parseFloat(lngInput.value) : defaultLng;
    
    // Initialiser la carte
    const map = new google.maps.Map(mapElement, {
        center: { lat, lng },
        zoom: 13,
        mapTypeControl: true,
        fullscreenControl: true,
        streetViewControl: false,
        zoomControl: true
    });
    
    // Ajouter un marqueur
    const marker = new google.maps.Marker({
        position: { lat, lng },
        map: map,
        draggable: true,
        animation: google.maps.Animation.DROP
    });
    
    // Mettre à jour les champs de coordonnées
    function updateCoordinateFields(position) {
        latInput.value = position.lat().toFixed(8);
        lngInput.value = position.lng().toFixed(8);
    }
    
    // Géocodage inversé pour obtenir l'adresse
    function reverseGeocode(position) {
        const geocoder = new google.maps.Geocoder();
        
        geocoder.geocode({ location: position }, function(results, status) {
            if (status === 'OK' && results[0]) {
                // Extraire les composants d'adresse
                let street = '';
                let city = '';
                let postalCode = '';
                let country = '';
                
                // Parcourir les composants d'adresse
                if (results[0].address_components) {
                    results[0].address_components.forEach(component => {
                        const types = component.types;
                        
                        if (types.includes('street_number')) {
                            street = component.long_name + ' ' + street;
                        }
                        else if (types.includes('route')) {
                            street += component.long_name;
                        }
                        else if (types.includes('locality')) {
                            city = component.long_name;
                        }
                        else if (types.includes('postal_code')) {
                            postalCode = component.long_name;
                        }
                        else if (types.includes('country')) {
                            country = component.long_name;
                        }
                    });
                } else {
                    console.warn('No address components found for reverse geocoding result.');
                }
                
                // Mettre à jour les champs d'adresse
                if (street) addressInput.value = street;
                if (city) cityInput.value = city;
                if (postalCode) postalCodeInput.value = postalCode;
                if (country) countryInput.value = country;
            } else {
                console.error('Geocoder failed due to: ' + status);
                showNotification('Erreur de géocodage: ' + status, 'error');
            }
        });
    }
    
    // Écouteur d'événement pour le déplacement du marqueur
    google.maps.event.addListener(marker, 'dragend', function() {
        const position = marker.getPosition();
        updateCoordinateFields(position);
        reverseGeocode(position);
        map.panTo(position);
    });
    
    // Écouteur d'événement pour cliquer sur la carte
    google.maps.event.addListener(map, 'click', function(event) {
        marker.setPosition(event.latLng);
        updateCoordinateFields(event.latLng);
        reverseGeocode(event.latLng);
        
        // Animation du marqueur lors du clic
        marker.setAnimation(google.maps.Animation.DROP);
    });
    
    // Initialiser l'autocomplétion d'adresse si l'API Places est disponible
    if (google.maps.places && addressInput) {
        const autocomplete = new google.maps.places.Autocomplete(addressInput, {
            types: ['address'],
            fields: ['address_components', 'geometry', 'formatted_address']
        });
        
        autocomplete.addListener('place_changed', function() {
            try {
                const place = autocomplete.getPlace();
                
                if (!place.geometry) {
                    // L'utilisateur a appuyé sur Entrée sans sélectionner un lieu
                    addressInput.placeholder = 'Saisissez une adresse valide';
                    return;
                }
                
                // Zoomer et centrer la carte
                map.setCenter(place.geometry.location);
                map.setZoom(16);
                
                // Positionner le marqueur
                marker.setPosition(place.geometry.location);
                marker.setAnimation(google.maps.Animation.DROP);
                
                // Mettre à jour les coordonnées
                updateCoordinateFields(place.geometry.location);
                
                // Remplir les autres champs d'adresse
                let city = '';
                let postalCode = '';
                let country = '';
                
                if (place.address_components) {
                    place.address_components.forEach(component => {
                        const types = component.types;
                        
                        if (types.includes('locality')) {
                            city = component.long_name;
                        }
                        else if (types.includes('postal_code')) {
                            postalCode = component.long_name;
                        }
                        else if (types.includes('country')) {
                            country = component.long_name;
                        }
                    });
                } else {
                    console.warn('No address components found for the selected place.');
                }
                
                if (city) cityInput.value = city;
                if (postalCode) postalCodeInput.value = postalCode;
                if (country) countryInput.value = country;
            } catch (error) {
                console.error('Error processing place_changed event:', error);
                showNotification('Erreur lors du traitement de l\'adresse: ' + error.message, 'error');
            }
        });
    }
    
    // Synchroniser les champs de coordonnées avec la carte
    latInput.addEventListener('change', updateMapFromFields);
    lngInput.addEventListener('change', updateMapFromFields);
    
    function updateMapFromFields() {
        const lat = parseFloat(latInput.value);
        const lng = parseFloat(lngInput.value);
        
        if (!isNaN(lat) && !isNaN(lng)) {
            const position = new google.maps.LatLng(lat, lng);
            marker.setPosition(position);
            map.setCenter(position);
        }
    }
}

/**
 * Contrôle de la visibilité des champs conditionnels
 */
function initConditionalFields() {
    // Contrôle de la visibilité du champ code d'accès
    const accessTypeSelect = document.getElementById('access_type');
    const accessCodeContainer = document.getElementById('access_code_container');
    
    if (accessTypeSelect && accessCodeContainer) {
        // Fonction pour mettre à jour la visibilité
        function updateAccessCodeVisibility() {
            if (accessTypeSelect.value === 'restricted') {
                accessCodeContainer.classList.remove('hidden');
                // Rendre le champ obligatoire quand visible
                const accessCodeInput = document.getElementById('access_code');
                if (accessCodeInput) {
                    accessCodeInput.setAttribute('required', '');
                }
            } else {
                accessCodeContainer.classList.add('hidden');
                // Supprimer l'attribut required quand caché
                const accessCodeInput = document.getElementById('access_code');
                if (accessCodeInput) {
                    accessCodeInput.removeAttribute('required');
                    // Vider le champ si caché
                    accessCodeInput.value = '';
                }
            }
        }
        
        // Appliquer au chargement
        updateAccessCodeVisibility();
        
        // Appliquer lors du changement
        accessTypeSelect.addEventListener('change', updateAccessCodeVisibility);
    }
    
    // Mise à jour de la prochaine date de maintenance
    const lastMaintenanceDate = document.getElementById('last_maintenance_date');
    const nextMaintenanceDate = document.getElementById('next_maintenance_date');
    
    if (lastMaintenanceDate && nextMaintenanceDate) {
        lastMaintenanceDate.addEventListener('change', function() {
            if (this.value) {
                // Calculer la prochaine date de maintenance (6 mois plus tard)
                const date = new Date(this.value);
                date.setMonth(date.getMonth() + 6);
                
                // Formater la date au format YYYY-MM-DD
                const yyyy = date.getFullYear();
                const mm = String(date.getMonth() + 1).padStart(2, '0');
                const dd = String(date.getDate()).padStart(2, '0');
                nextMaintenanceDate.value = `${yyyy}-${mm}-${dd}`;
            }
        });
    }
}

/**
 * Sauvegarde automatique des données du formulaire
 */
function initFormDataPersistence() {
    const form = document.getElementById('chargingPointForm');
    
    if (!form) return;
    
    const formId = 'charging-point-form-data';
    
    // Sauvegarder les données du formulaire dans localStorage
    function saveFormData() {
        const formData = {};
        
        // Collecter les valeurs des champs
        form.querySelectorAll('input, select, textarea').forEach(field => {
            const name = field.name;
            
            if (!name) return;
            
            // Traiter différents types de champs
            if (field.type === 'radio' || field.type === 'checkbox') {
                if (field.checked) {
                    formData[name] = field.value;
                }
            } else {
                formData[name] = field.value;
            }
        });
        
        // Sauvegarder dans localStorage
        localStorage.setItem(formId, JSON.stringify(formData));
    }
    
    // Restaurer les données du formulaire depuis localStorage
    function restoreFormData() {
        const savedData = localStorage.getItem(formId);

        if (!savedData) return;

        const formData = JSON.parse(savedData);

        // Remplir les champs avec les données sauvegardées
        for (const name in formData) {
            const field = form.querySelector(`[name="${name}"]`);

            if (!field) continue;

            if (field.type === 'radio' || field.type === 'checkbox') {
                // Pour les boutons radio et cases à cocher
                const radioOrCheckbox = form.querySelector(`[name="${name}"][value="${formData[name]}"]`);
                if (radioOrCheckbox) {
                    radioOrCheckbox.checked = true;
                }
            } else {
                // Pour les autres types de champs
                field.value = formData[name];
            }
        }
    }
    
    // Sauvegarder les données à intervalle régulier
    const saveInterval = setInterval(saveFormData, 30000); // Toutes les 30 secondes
    
    // Sauvegarder lors des changements importants
    form.addEventListener('change', saveFormData);
    form.addEventListener('input', function(e) {
        // Limiter la fréquence de sauvegarde pour les événements 'input'
        if (this.saveTimeout) clearTimeout(this.saveTimeout);
        this.saveTimeout = setTimeout(saveFormData, 1000);
    });
    
    // Nettoyer localStorage à la soumission réussie
    form.addEventListener('submit', function() {
        localStorage.removeItem(formId);
        clearInterval(saveInterval);
    });
    
    // Restaurer les données au chargement
    restoreFormData();
    
    // Ajouter un bouton de restauration si des données existent
    if(form){
        if (localStorage.getItem(formId)) {
            const restoreBtn = document.createElement('button');
            restoreBtn.type = 'button';
            restoreBtn.className = 'ml-3 inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500';
            restoreBtn.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 -ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Restaurer données
            `;
            restoreBtn.addEventListener('click', function() {
                if (confirm('Voulez-vous restaurer les données du formulaire précédemment saisies?')) {
                    restoreFormData();
                }
            });

            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.parentNode.insertBefore(restoreBtn, submitBtn);
            }
        }
    }
}

/**
 * Configuration des masques de saisie pour certains champs
 */
function setupInputMasks() {
    // Masque pour l'adresse MAC (XX:XX:XX:XX:XX:XX)
    const macAddressInput = document.getElementById('mac_address');
    if (macAddressInput) {
        macAddressInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^0-9A-Fa-f]/g, '').toUpperCase();
            let formattedValue = '';
            
            for (let i = 0; i < value.length && i < 12; i++) {
                if (i > 0 && i % 2 === 0) {
                    formattedValue += ':';
                }
                formattedValue += value[i];
            }
            
            e.target.value = formattedValue;
        });
    }
    
    // Masque pour l'adresse IP (XXX.XXX.XXX.XXX)
    const ipAddressInput = document.getElementById('ip_address');
    if (ipAddressInput) {
        ipAddressInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^0-9.]/g, '');
            let parts = value.split('.');
            
            // Limiter chaque partie à 3 chiffres et à une valeur max de 255
            for (let i = 0; i < parts.length; i++) {
                if (parts[i].length > 3) {
                    parts[i] = parts[i].substring(0, 3);
                }
                
                const num = parseInt(parts[i], 10);
                if (!isNaN(num) && num > 255) {
                    parts[i] = '255';
                }
            }
            
            // Limiter à 4 parties
            if (parts.length > 4) {
                parts = parts.slice(0, 4);
            }
            
            e.target.value = parts.join('.');
        });
    }
    
    // Masque pour les coordonnées géographiques
    const latitudeInput = document.getElementById('latitude');
    const longitudeInput = document.getElementById('longitude');
    
    if (latitudeInput) {
        latitudeInput.addEventListener('blur', function() {
            const value = parseFloat(this.value);
            if (!isNaN(value)) {
                // Limiter entre -90 et 90 et formater avec 8 décimales
                const limitedValue = Math.max(-90, Math.min(90, value));
                this.value = limitedValue.toFixed(8);
            }
        });
    }
    
    if (longitudeInput) {
        longitudeInput.addEventListener('blur', function() {
            const value = parseFloat(this.value);
            if (!isNaN(value)) {
                // Limiter entre -180 et 180 et formater avec 8 décimales
                const limitedValue = Math.max(-180, Math.min(180, value));
                this.value = limitedValue.toFixed(8);
            }
        });
    }
}

/**
 * Gestion des événements de raccourcis clavier
 */
function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl+S pour sauvegarder
        if ((e.ctrlKey || e.metaKey) && e.key === 's' && document.getElementById('chargingPointForm')) {
            e.preventDefault();
            // Déclencher la sauvegarde automatique
            const event = new Event('change');
            document.getElementById('chargingPointForm').dispatchEvent(event);
            
            // Notification de sauvegarde
            showNotification('Formulaire sauvegardé localement', 'success');
        }
        
        // Ctrl+Flèche droite/gauche pour naviguer entre les onglets
        if ((e.ctrlKey || e.metaKey) && (e.key === 'ArrowRight' || e.key === 'ArrowLeft')) {
            e.preventDefault();
            
            const tabs = document.querySelectorAll('.tab-btn');
            const activeTabIndex = Array.from(tabs).findIndex(tab => tab.classList.contains('active'));
            
            if (activeTabIndex !== -1) {
                const nextTabIndex = e.key === 'ArrowRight' 
                    ? Math.min(activeTabIndex + 1, tabs.length - 1) 
                    : Math.max(activeTabIndex - 1, 0);
                
                tabs[nextTabIndex].click();
            }
        }
    });
}

/**
 * Afficher une notification à l'utilisateur
 */
function showNotification(message, type = 'info') {
    // Créer l'élément de notification
    const notification = document.createElement('div');
    notification.className = `fixed bottom-4 right-4 p-4 rounded-md shadow-lg z-50 ${
        type === 'success' ? 'bg-green-100 border-green-500 text-green-800' : 
        type === 'error' ? 'bg-red-100 border-red-500 text-red-800' : 
        'bg-blue-100 border-blue-500 text-blue-800'
    } border-l-4`;
    
    notification.innerHTML = `
        <div class="flex items-center">
            <div class="flex-shrink-0">
                ${type === 'success' 
                    ? '<svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>'
                    : type === 'error'
                    ? '<svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>'
                    : '<svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>'
                }
            </div>
            <div class="ml-3">
                <p class="text-sm">${message}</p>
            </div>
            <div class="ml-auto pl-3">
                <button class="inline-flex text-gray-400 hover:text-gray-500">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>
        </div>
    `;
    
    // Ajouter la notification au DOM
    document.body.appendChild(notification);
    
    // Configurer le bouton de fermeture
    notification.querySelector('button').addEventListener('click', function() {
        notification.remove();
    });
    
    // Supprimer automatiquement après 5 secondes
    setTimeout(() => {
        notification.classList.add('opacity-0', 'transition-opacity', 'duration-500');
        setTimeout(() => notification.remove(), 500);
    }, 5000);
}