document.addEventListener('DOMContentLoaded', function() {
    // Multi-step form functionality
    const steps = document.querySelectorAll('.step');
    const nextButtons = document.querySelectorAll('.next-step');
    const prevButtons = document.querySelectorAll('.prev-step');
    const stepIndicators = document.querySelectorAll('.step-indicator');
    
    // Progress through steps
    nextButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Find the active step
            const activeStep = document.querySelector('.step.active');
            const activeIndex = Array.from(steps).indexOf(activeStep);
            
            // Validate the current step before proceeding
            if (validateStep(activeIndex)) {
                // Hide current step
                activeStep.classList.remove('active');
                activeStep.classList.add('hidden');
                
                // Show next step
                const nextStep = steps[activeIndex + 1];
                nextStep.classList.remove('hidden');
                nextStep.classList.add('active');
                
                // Update step indicators
                updateStepIndicators(activeIndex + 1);
                
                // If it's the last step, populate the summary
                if (activeIndex + 1 === steps.length - 1) {
                    populateSummary();
                }
            }
        });
    });
    
    // Go back to previous step
    prevButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Find the active step
            const activeStep = document.querySelector('.step.active');
            const activeIndex = Array.from(steps).indexOf(activeStep);
            
            // Hide current step
            activeStep.classList.remove('active');
            activeStep.classList.add('hidden');
            
            // Show previous step
            const prevStep = steps[activeIndex - 1];
            prevStep.classList.remove('hidden');
            prevStep.classList.add('active');
            
            // Update step indicators
            updateStepIndicators(activeIndex - 1);
        });
    });
    
    // Function to update step indicators
    function updateStepIndicators(activeIndex) {
        stepIndicators.forEach((indicator, index) => {
            const stepNumber = indicator.querySelector('.step-number');
            const stepText = indicator.querySelector('span');
            
            if (index <= activeIndex) {
                stepNumber.classList.remove('bg-gray-200', 'text-gray-500');
                stepNumber.classList.add('bg-green-500', 'text-white');
                stepText.classList.remove('text-gray-500');
                stepText.classList.add('text-green-500', 'font-medium');
            } else {
                stepNumber.classList.remove('bg-green-500', 'text-white');
                stepNumber.classList.add('bg-gray-200', 'text-gray-500');
                stepText.classList.remove('text-green-500', 'font-medium');
                stepText.classList.add('text-gray-500');
            }
        });
    }
    
    // Function to validate each step
    function validateStep(stepIndex) {
        const currentStep = steps[stepIndex];
        const requiredFields = currentStep.querySelectorAll('input[required], select[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('border-red-500');
                
                // Add error message if it doesn't exist
                const errorMessage = field.nextElementSibling && field.nextElementSibling.classList.contains('text-red-500') 
                    ? field.nextElementSibling 
                    : null;
                
                if (!errorMessage) {
                    const error = document.createElement('p');
                    error.textContent = 'Ce champ est requis';
                    error.classList.add('text-red-500', 'text-xs', 'mt-1');
                    field.parentNode.insertBefore(error, field.nextSibling);
                }
            } else {
                field.classList.remove('border-red-500');
                
                // Remove error message if it exists
                const errorMessage = field.nextElementSibling && field.nextElementSibling.classList.contains('text-red-500') 
                    ? field.nextElementSibling 
                    : null;
                
                if (errorMessage) {
                    errorMessage.remove();
                }
            }
        });
        
        return isValid;
    }
    
    // Function to populate the summary on the last step
    function populateSummary() {
        const summaryContent = document.getElementById('summaryContent');
        summaryContent.innerHTML = '';
        
        // Basic Information
        addSummaryItem(summaryContent, 'Nom de la Borne', document.querySelector('[name="name"]').value);
        addSummaryItem(summaryContent, 'Numéro de Série', document.querySelector('[name="serial_number"]').value);
        addSummaryItem(summaryContent, 'Fabricant', document.querySelector('[name="manufacturer"]').value);
        addSummaryItem(summaryContent, 'Modèle', document.querySelector('[name="model"]').value);
        
        // Location Information
        const stationSelect = document.querySelector('[name="station_id"]');
        const stationText = stationSelect.options[stationSelect.selectedIndex].text;
        addSummaryItem(summaryContent, 'Station', stationText);
        
        addSummaryItem(summaryContent, 'Adresse', document.querySelector('[name="address"]').value);
        addSummaryItem(summaryContent, 'Ville', document.querySelector('[name="city"]').value);
        addSummaryItem(summaryContent, 'Code Postal', document.querySelector('[name="postal_code"]').value);
        addSummaryItem(summaryContent, 'Pays', document.querySelector('[name="country"]').value);
        
        // Technical Information
        addSummaryItem(summaryContent, 'Puissance Maximale', document.querySelector('[name="max_power"]').value + ' kW');
        
        const connectionTypeSelect = document.querySelector('[name="connection_type"]');
        const connectionTypeText = connectionTypeSelect.options[connectionTypeSelect.selectedIndex].text;
        addSummaryItem(summaryContent, 'Type de Connexion', connectionTypeText);
        
        const protocolSelect = document.querySelector('[name="communication_protocol"]');
        const protocolText = protocolSelect.options[protocolSelect.selectedIndex].text;
        addSummaryItem(summaryContent, 'Protocole de Communication', protocolText);
        
        const statusSelect = document.querySelector('[name="status"]');
        const statusText = statusSelect.options[statusSelect.selectedIndex].text;
        addSummaryItem(summaryContent, 'Statut Initial', statusText);
        
        const accessibility = document.querySelector('input[name="accessibility"]:checked').value;
        addSummaryItem(summaryContent, 'Accessibilité', accessibility === 'private' ? 'Privé' : 'Publique');
    }
    
    // Helper function to add an item to the summary
    function addSummaryItem(container, label, value) {
        const div = document.createElement('div');
        div.innerHTML = `
            <p class="text-sm font-medium text-gray-500">${label}</p>
            <p class="text-sm font-semibold">${value}</p>
        `;
        container.appendChild(div);
    }
    
    // Handle accessibility options selection
    const accessibilityOptions = document.querySelectorAll('.accessibility-option');
    
    accessibilityOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Remove selected styles from all options
            accessibilityOptions.forEach(opt => {
                opt.classList.remove('border-green-500');
                const icon = opt.querySelector('svg');
                icon.classList.remove('text-green-500');
                icon.classList.add('text-gray-400');
                
                const radioButton = opt.querySelector('input[type="radio"]');
                radioButton.checked = false;
                
                const indicator = opt.querySelector('.h-5.w-5 div');
                if (indicator) {
                    indicator.remove();
                }
            });
            
            // Add selected styles to clicked option
            this.classList.add('border-green-500');
            const icon = this.querySelector('svg');
            icon.classList.remove('text-gray-400');
            icon.classList.add('text-green-500');
            
            const radioButton = this.querySelector('input[type="radio"]');
            radioButton.checked = true;
            
            const indicatorContainer = this.querySelector('.h-5.w-5');
            const indicator = document.createElement('div');
            indicator.classList.add('h-3', 'w-3', 'rounded-full', 'bg-green-500');
            indicatorContainer.appendChild(indicator);
        });
    });
    
    // Geolocation button functionality
    document.getElementById('geolocate-button').addEventListener('click', function() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                document.querySelector('[name="latitude"]').value = position.coords.latitude.toFixed(6);
                document.querySelector('[name="longitude"]').value = position.coords.longitude.toFixed(6);
            }, function(error) {
                alert('Erreur de géolocalisation: ' + error.message);
            });
        } else {
            alert('La géolocalisation n\'est pas supportée par votre navigateur.');
        }
    });
    
    // Station creation modal functionality
    const stationCreateButton = document.querySelector('[data-station-create]');
    const createStationModal = document.getElementById('create-station-modal');
    const closeStationModalButton = document.getElementById('close-station-modal');
    const submitStationFormButton = document.getElementById('submit-station-form');
    const createStationForm = document.getElementById('create-station-form');
    
    // Show modal
    stationCreateButton.addEventListener('click', function() {
        createStationModal.classList.remove('hidden');
    });
    
    // Hide modal
    closeStationModalButton.addEventListener('click', function() {
        createStationModal.classList.add('hidden');
    });
    
    // Submit station form via AJAX
    submitStationFormButton.addEventListener('click', function() {
        const formData = new FormData(createStationForm);
        
        // Create an object from the form data
        const stationData = {
            name: formData.get('label'),
            address: formData.get('station_address'),
            city: formData.get('station_city'),
            postal_code: formData.get('station_postal_code'),
            country: formData.get('station_country'),
            type: formData.get('station_type')
        };
        
        // Send AJAX request
        fetch('{{ route("stations.store-ajax") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(stationData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Add the new station to the select dropdown
                const stationSelect = document.querySelector('[name="station_id"]');
                const option = document.createElement('option');
                option.value = data.station.id;
                option.text = data.station.name;
                option.selected = true;
                stationSelect.appendChild(option);
                
                // Close the modal
                createStationModal.classList.add('hidden');
                
                // Reset the form
                createStationForm.reset();
                
                // Show success message
                alert('Station créée avec succès!');
            } else {
                // Show error message
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Une erreur est survenue lors de la création de la station.');
        });
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === createStationModal) {
            createStationModal.classList.add('hidden');
        }
    });
});