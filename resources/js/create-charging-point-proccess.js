/**
 * Charging Point Creation - Multi-step Form Process
 * 
 * Comprehensive form management with advanced validation, 
 * data persistence, and user experience features.
 */
(function() {
    'use strict';

    // Configuration object for centralized settings
    const CONFIG = {
        maxNameLength: 100,
        serialNumberRegex: /^[A-Za-z0-9\-_]+$/,
        postalCodeRegex: {
            France: /^\d{5}$/
        },
        geolocation: {
            timeout: 10000,
            maximumAge: 0,
            enableHighAccuracy: true
        },
        storageKey: 'chargingPointFormData',
        endpoints: {
            saveStep: '/charging-points/save-step',
            checkSerial: '/charging-points/check-serial',
            createStation: '/stations/store-ajax'
        }
    };

    // Main form management class
    class ChargingPointFormManager {
        constructor() {
            // Core DOM elements
            this.form = document.getElementById('createChargingPointForm');
            this.steps = document.querySelectorAll('.step');
            this.stepIndicators = document.querySelectorAll('.step-indicator');
            this.nextButtons = document.querySelectorAll('.next-step');
            this.prevButtons = document.querySelectorAll('.prev-step');
            
            // Current step tracking
            this.currentStep = 0;
            
            // CSRF token for AJAX requests
            this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            // Bind methods to maintain context
            this.initForm = this.initForm.bind(this);
            this.validateCurrentStep = this.validateCurrentStep.bind(this);
            this.goToNextStep = this.goToNextStep.bind(this);
            this.goToPrevStep = this.goToPrevStep.bind(this);
            
            // Initialize the form when DOM is ready
            this.initForm();
        }

        // Setup geolocation feature
        setupGeolocation() {
            const geolocateButton = document.getElementById('geolocate-button');
            
            if (!geolocateButton) return;
            
            geolocateButton.addEventListener('click', () => {
                // Check if geolocation is supported
                if (!navigator.geolocation) {
                    this.showNotification('La géolocalisation n\'est pas supportée par votre navigateur.', 'error');
                    return;
                }
                
                // Disable button and prepare for geolocation
                geolocateButton.disabled = true;
                geolocateButton.classList.add('opacity-50');
                
                // Store original button content
                const originalContent = geolocateButton.innerHTML;
                geolocateButton.textContent = 'Localisation en cours...';
                
                // Attempt to get current position
                navigator.geolocation.getCurrentPosition(
                    // Success callback
                    (position) => {
                        // Get latitude and longitude fields
                        const latField = this.form.querySelector('[name="latitude"]');
                        const lngField = this.form.querySelector('[name="longitude"]');
                        
                        // Set coordinates with 6 decimal places precision
                        if (latField) latField.value = position.coords.latitude.toFixed(6);
                        if (lngField) lngField.value = position.coords.longitude.toFixed(6);
                        
                        // Show success notification
                        this.showNotification('Localisation récupérée avec succès', 'success');
                        
                        // Reset button state
                        this.resetGeolocationButton(geolocateButton, originalContent);
                    },
                    // Error callback
                    (error) => {
                        // Predefined error messages
                        const errorMessages = {
                            1: 'Accès à la géolocalisation refusé.',
                            2: 'Position non disponible.',
                            3: 'Délai d\'attente dépassé.',
                            0: 'Erreur inconnue de géolocalisation.'
                        };
                        
                        // Show specific error message
                        this.showNotification(
                            `Erreur de géolocalisation: ${errorMessages[error.code] || errorMessages[0]}`, 
                            'error'
                        );
                        
                        // Reset button state
                        this.resetGeolocationButton(geolocateButton, originalContent);
                    },
                    // Geolocation options
                    {
                        timeout: 10000,       // 10 seconds timeout
                        maximumAge: 0,        // Always get fresh location
                        enableHighAccuracy: true  // Most accurate position
                    }
                );
            });
        }

        // Reset geolocation button to original state
        resetGeolocationButton(button, originalContent) {
            button.disabled = false;
            button.classList.remove('opacity-50');
            button.innerHTML = originalContent;
        }

        // Setup station creation modal
        setupStationModal() {
            const stationCreateButton = document.querySelector('[data-station-create]');
            const stationModal = document.getElementById('create-station-modal');
            const closeButton = document.getElementById('close-station-modal');
            const submitButton = document.getElementById('submit-station-form');
            const stationForm = document.getElementById('create-station-form');
            
            // Validate modal elements exist
            if (!stationCreateButton || !stationModal || !closeButton || !submitButton || !stationForm) {
                return; // Missing required elements
            }
            
            // Open modal event
            stationCreateButton.addEventListener('click', () => {
                stationModal.classList.remove('hidden');
            });

            // Close modal events
            closeButton.addEventListener('click', () => {
                stationModal.classList.add('hidden');
            });

            // Close when clicking outside modal
            window.addEventListener('click', (event) => {
                if (event.target === stationModal) {
                    stationModal.classList.add('hidden');
                }
            });

            // Submit station form
            submitButton.addEventListener('click', async () => {
                // Client-side form validation
                if (!stationForm.checkValidity()) {
                    stationForm.reportValidity();
                    return;
                }

                // Disable submit button during submission
                submitButton.disabled = true;
                submitButton.classList.add('opacity-50');
                
                // Store original button text
                const originalButtonText = submitButton.textContent;
                submitButton.textContent = 'Création en cours...';

                try {
                    // Collect station data
                    const formData = new FormData(stationForm);
                    const stationData = {
                        name: formData.get('label'),
                        address: formData.get('station_address'),
                        city: formData.get('station_city'),
                        postal_code: formData.get('station_postal_code'),
                        country: formData.get('station_country'),
                        type: formData.get('station_type')
                    };

                    // Send AJAX request to create station
                    const response = await fetch(CONFIG.endpoints.createStation, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken
                        },
                        body: JSON.stringify(stationData)
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        // If the response is not OK (e.g., 4xx or 5xx status), throw an error
                        throw new Error(data.message || `HTTP error! Status: ${response.status}`);
                    }

                    // Handle successful station creation
                    if (data.success) {
                        // Add new station to dropdown
                        const stationSelect = this.form.querySelector('[name="station_id"]');
                        if (stationSelect) {
                            const option = document.createElement('option');
                            option.value = data.station.id;
                            option.text = data.station.name;
                            option.selected = true;
                            stationSelect.appendChild(option);

                            // Trigger change event
                            const event = new Event('change', { bubbles: true });
                            stationSelect.dispatchEvent(event);
                        }

                        // Reset form and close modal
                        stationForm.reset();
                        stationModal.classList.add('hidden');

                        // Show success notification
                        this.showNotification('Station créée avec succès!', 'success');
                    } else {
                        // Handle creation failure (if success is false but response.ok is true)
                        throw new Error(data.message || 'Une erreur est survenue');
                    }
                } catch (error) {
                    // Log and show error
                    console.error('Error creating station:', error);
                    this.showNotification(`Erreur: ${error.message}`, 'error');
                } finally {
                    // Restore button state
                    submitButton.disabled = false;
                    submitButton.classList.remove('opacity-50');
                    submitButton.textContent = originalButtonText;
                }
            });
        }

        // Show notification method
        showNotification(message, type = 'success', duration = 5000) {
            // Check for global notification function
            if (typeof window.showNotification === 'function') {
                window.showNotification(message, type, duration);
            } else {
                // Fallback to console and alert
                console.log(`${type.toUpperCase()}: ${message}`);
                if (type === 'error') {
                    alert(message);
                }
            }
        }

        // Additional methods will follow...
    }

    // Initialize form manager when DOM is fully loaded
    document.addEventListener('DOMContentLoaded', () => {
        new ChargingPointFormManager();
    });
})();