// Time Picker avec intervalles de 5 minutes
class TimePicker {
    constructor(container, options = {}) {
        this.container = container;
        this.options = {
            minDuration: 5,
            maxDuration: 240, // 4 heures
            interval: 5, // intervalles de 5 minutes
            onDurationChange: null,
            ...options
        };
        
        this.currentDuration = 0;
        this.isDragging = false;
        this.startX = 0;
        this.timelineWidth = 0;
        
        this.init();
    }
    
    init() {
        this.createTimePicker();
        this.setupEventListeners();
        this.updateDisplay(0);
    }
    
    createTimePicker() {
        // Créer la structure du time picker
        this.container.innerHTML = `
            <div class="timepicker-container-inner">
                <div class="timeline-container">
                    <div class="current-time">
                        <div class="actual-time">0 min</div>
                    </div>
                    <div class="timeline"></div>
                    <div class="hours-container">
                        ${this.createHourMarks()}
                    </div>
                </div>
                <div class="display-time">
                    <div class="decrement-time">
                        <svg width="24" height="24">
                            <path stroke="currentColor" stroke-width="2" d="M8,12 h8" />
                        </svg>
                    </div>
                    <div class="time">
                        <input type="text" class="time-input" value="0 min" />
                        <div class="formatted-time">0 min</div>
                    </div>
                    <div class="increment-time">
                        <svg width="24" height="24">
                            <path stroke="currentColor" stroke-width="2" d="M12,7 v10 M7,12 h10" />
                        </svg>
                    </div>
                </div>
                <div class="am-pm-container">
                    <div class="am-pm-button active">min</div>
                    <div class="am-pm-button">heures</div>
                </div>
            </div>
        `;
        
        // Obtenir les références des éléments
        this.currentTimeElement = this.container.querySelector('.current-time');
        this.actualTimeElement = this.container.querySelector('.actual-time');
        this.formattedTimeElement = this.container.querySelector('.formatted-time');
        this.timeInput = this.container.querySelector('.time-input');
        this.timeline = this.container.querySelector('.timeline');
        this.timelineContainer = this.container.querySelector('.timeline-container');
        
        // Calculer la largeur de la timeline
        this.timelineWidth = this.timeline.offsetWidth;
    }
    
    createHourMarks() {
        let marks = '';
        for (let i = 0; i < 12; i++) {
            marks += '<div class="hour-mark"></div>';
        }
        return marks;
    }
    
    setupEventListeners() {
        // Boutons + et -
        const decrementBtn = this.container.querySelector('.decrement-time');
        const incrementBtn = this.container.querySelector('.increment-time');
        
        decrementBtn.addEventListener('click', () => {
            this.adjustDuration(-this.options.interval);
        });
        
        incrementBtn.addEventListener('click', () => {
            this.adjustDuration(this.options.interval);
        });
        
        // Input manuel
        this.timeInput.addEventListener('input', (e) => {
            const value = parseInt(e.target.value) || 0;
            this.setDuration(value);
        });
        
        // Drag sur la timeline
        this.setupDragEvents();
        
        // Boutons AM/PM (pour affichage)
        const amPmButtons = this.container.querySelectorAll('.am-pm-button');
        amPmButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                amPmButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            });
        });
    }
    
    setupDragEvents() {
        this.timelineContainer.addEventListener('mousedown', (e) => {
            this.isDragging = true;
            this.startX = e.clientX;
            const rect = this.timelineContainer.getBoundingClientRect();
            const position = e.clientX - rect.left;
            this.setDurationFromPosition(position);
            
            document.body.style.userSelect = 'none';
        });
        
        document.addEventListener('mousemove', (e) => {
            if (!this.isDragging) return;
            
            const rect = this.timelineContainer.getBoundingClientRect();
            const position = e.clientX - rect.left;
            this.setDurationFromPosition(position);
        });
        
        document.addEventListener('mouseup', () => {
            this.isDragging = false;
            document.body.style.userSelect = '';
        });
        
        // Support tactile
        this.timelineContainer.addEventListener('touchstart', (e) => {
            this.isDragging = true;
            this.startX = e.touches[0].clientX;
            const rect = this.timelineContainer.getBoundingClientRect();
            const position = e.touches[0].clientX - rect.left;
            this.setDurationFromPosition(position);
        });
        
        document.addEventListener('touchmove', (e) => {
            if (!this.isDragging) return;
            e.preventDefault();
            
            const rect = this.timelineContainer.getBoundingClientRect();
            const position = e.touches[0].clientX - rect.left;
            this.setDurationFromPosition(position);
        });
        
        document.addEventListener('touchend', () => {
            this.isDragging = false;
        });
    }
    
    setDurationFromPosition(position) {
        const clampedPosition = Math.max(0, Math.min(position, this.timelineWidth));
        const duration = Math.round((clampedPosition / this.timelineWidth) * this.options.maxDuration);
        this.setDuration(duration);
    }
    
    adjustDuration(delta) {
        const newDuration = this.currentDuration + delta;
        this.setDuration(newDuration);
    }
    
    setDuration(duration) {
        // Arrondir aux intervalles de 5 minutes
        const roundedDuration = Math.round(duration / this.options.interval) * this.options.interval;
        const clampedDuration = Math.max(this.options.minDuration, Math.min(roundedDuration, this.options.maxDuration));
        
        if (clampedDuration !== this.currentDuration) {
            this.currentDuration = clampedDuration;
            this.updateDisplay(clampedDuration);
            
            if (this.options.onDurationChange) {
                this.options.onDurationChange(clampedDuration);
            }
        }
    }
    
    updateDisplay(duration) {
        // Mettre à jour l'affichage du temps
        const displayText = this.formatDuration(duration);
        
        if (this.actualTimeElement) {
            this.actualTimeElement.textContent = displayText;
        }
        
        if (this.formattedTimeElement) {
            this.formattedTimeElement.textContent = displayText;
        }
        
        if (this.timeInput) {
            this.timeInput.value = displayText;
        }
        
        // Mettre à jour la position du curseur
        this.updateCursorPosition(duration);
    }
    
    formatDuration(minutes) {
        if (minutes < 60) {
            return `${minutes} min`;
        } else {
            const hours = Math.floor(minutes / 60);
            const remainingMinutes = minutes % 60;
            if (remainingMinutes === 0) {
                return `${hours}h`;
            } else {
                return `${hours}h ${remainingMinutes}min`;
            }
        }
    }
    
    updateCursorPosition(duration) {
        if (this.currentTimeElement && this.timelineWidth > 0) {
            const position = (duration / this.options.maxDuration) * this.timelineWidth;
            this.currentTimeElement.style.transform = `translateX(${position}px)`;
        }
    }
    
    getDuration() {
        return this.currentDuration;
    }
    
    setDuration(duration) {
        this.setDuration(duration);
    }
}

        // Initialisation automatique quand le DOM est chargé
        document.addEventListener('DOMContentLoaded', function() {
            const timePickerContainer = document.querySelector('.timepicker');
            if (timePickerContainer) {
                const timePicker = new TimePicker(timePickerContainer, {
                    onDurationChange: function(duration) {
                        // Mettre à jour la durée sélectionnée dans l'interface
                        const selectedDurationElement = document.getElementById('selected-duration');
                        if (selectedDurationElement) {
                            selectedDurationElement.textContent = `${duration} min`;
                        }
                        
                        // Mettre à jour les données de réservation
                        if (window.selectedReservationOption) {
                            window.selectedReservationOption = {
                                type: 'minute',
                                value: duration,
                                price: window.calculatePrice ? window.calculatePrice(duration) : 0
                            };
                        }
                        
                        // Mettre à jour l'affichage de la réservation
                        if (window.updateReservationDisplay) {
                            window.updateReservationDisplay();
                        }
                        
                        // Mettre à jour l'estimation en temps réel
                        if (window.updateRealTimeEstimation) {
                            window.updateRealTimeEstimation();
                        }
                        
                        // Mettre à jour le prix si la fonction existe
                        if (window.updatePriceSummary) {
                            window.updatePriceSummary();
                        }
                        
                        // Mettre à jour l'état du bouton de réservation
                        if (window.updateReserveButtonState) {
                            window.updateReserveButtonState();
                        }
                    }
                });
                
                // Exposer l'instance pour un accès global
                window.timePicker = timePicker;
            }
        });
