/**
 * Dashboard Énergétique - JavaScript
 * Gestion des interactions et mises à jour en temps réel
 */

(function() {
    'use strict';

    // Configuration
    const CONFIG = {
        updateInterval: 30000, // 30 secondes
        animationDuration: 600,
        chartColors: {
            primary: '#10b981',
            secondary: '#059669',
            accent: '#34d399',
            blue: '#3b82f6',
            orange: '#f59e0b',
            red: '#ef4444',
            purple: '#8b5cf6',
            cyan: '#06b6d4'
        }
    };

    // État de l'application
    const state = {
        charts: {},
        updateTimer: null,
        isUpdating: false
    };

    /**
     * Initialisation du dashboard
     */
    function initDashboard() {
        console.log('🚀 Initialisation du dashboard énergétique...');
        
        // Initialiser les graphiques
        initCharts();
        
        // Configurer les mises à jour automatiques
        setupAutoUpdate();
        
        // Configurer les animations au scroll
        setupScrollAnimations();
        
        // Configurer les gestionnaires d'événements
        setupEventHandlers();
        
        console.log('✅ Dashboard initialisé avec succès');
    }

    /**
     * Initialisation des graphiques Chart.js
     */
    function initCharts() {
        // Configuration commune pour tous les graphiques
        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    borderRadius: 8,
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 13
                    },
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += new Intl.NumberFormat('fr-FR').format(context.parsed.y);
                            }
                            return label;
                        }
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        };

        // Graphique des sessions
        const sessionsCanvas = document.getElementById('sessionsChart');
        if (sessionsCanvas) {
            const ctx = sessionsCanvas.getContext('2d');
            state.charts.sessions = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
                    datasets: [{
                        label: 'Sessions',
                        data: [45, 52, 38, 67, 89, 76, 82],
                        borderColor: CONFIG.chartColors.primary,
                        backgroundColor: CONFIG.chartColors.primary + '20',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: CONFIG.chartColors.primary,
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        pointHoverBackgroundColor: CONFIG.chartColors.primary,
                        pointHoverBorderColor: '#fff',
                        pointHoverBorderWidth: 3
                    }]
                },
                options: {
                    ...commonOptions,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)',
                                drawBorder: false
                            },
                            ticks: {
                                font: {
                                    size: 12
                                },
                                padding: 8
                            }
                        },
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false
                            },
                            ticks: {
                                font: {
                                    size: 12
                                },
                                padding: 8
                            }
                        }
                    }
                }
            });
        }

        // Graphique de l'énergie
        const energyCanvas = document.getElementById('energyChart');
        if (energyCanvas) {
            const ctx = energyCanvas.getContext('2d');
            state.charts.energy = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['00h', '04h', '08h', '12h', '16h', '20h'],
                    datasets: [{
                        label: 'kWh',
                        data: [120, 80, 200, 350, 280, 180],
                        backgroundColor: [
                            CONFIG.chartColors.orange + '90',
                            CONFIG.chartColors.orange + '70',
                            CONFIG.chartColors.orange + '90',
                            CONFIG.chartColors.red + '90',
                            CONFIG.chartColors.orange + '80',
                            CONFIG.chartColors.orange + '60'
                        ],
                        borderColor: CONFIG.chartColors.orange,
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false
                    }]
                },
                options: {
                    ...commonOptions,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)',
                                drawBorder: false
                            },
                            ticks: {
                                font: {
                                    size: 12
                                },
                                padding: 8,
                                callback: function(value) {
                                    return value + ' kWh';
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false
                            },
                            ticks: {
                                font: {
                                    size: 12
                                },
                                padding: 8
                            }
                        }
                    }
                }
            });
        }

        // Graphique en donut - Types de chargeurs
        const chargerTypesCanvas = document.getElementById('chargerTypesChart');
        if (chargerTypesCanvas) {
            const ctx = chargerTypesCanvas.getContext('2d');
            state.charts.chargerTypes = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['AC 22kW', 'DC 50kW', 'DC 150kW', 'DC 350kW'],
                    datasets: [{
                        data: [35, 25, 30, 10],
                        backgroundColor: [
                            CONFIG.chartColors.primary,
                            CONFIG.chartColors.blue,
                            CONFIG.chartColors.orange,
                            CONFIG.chartColors.purple
                        ],
                        borderWidth: 0,
                        cutout: '65%'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                usePointStyle: true,
                                font: {
                                    size: 11
                                }
                            }
                        },
                        tooltip: {
                            ...commonOptions.plugins.tooltip,
                            callbacks: {
                                label: function(context) {
                                    return context.label + ': ' + context.parsed + '%';
                                }
                            }
                        }
                    }
                }
            });
        }

        // Graphique en barres - Performance par station
        const stationPerformanceCanvas = document.getElementById('stationPerformanceChart');
        if (stationPerformanceCanvas) {
            const ctx = stationPerformanceCanvas.getContext('2d');
            state.charts.stationPerformance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Casa', 'Rabat', 'Marr', 'Fès', 'Agadir'],
                    datasets: [{
                        label: 'Sessions/jour',
                        data: [120, 95, 80, 65, 45],
                        backgroundColor: CONFIG.chartColors.primary,
                        borderRadius: 8,
                        borderSkipped: false
                    }]
                },
                options: {
                    ...commonOptions,
                    indexAxis: 'y',
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)',
                                drawBorder: false
                            },
                            ticks: {
                                font: {
                                    size: 11
                                }
                            }
                        },
                        y: {
                            grid: {
                                display: false,
                                drawBorder: false
                            },
                            ticks: {
                                font: {
                                    size: 11
                                }
                            }
                        }
                    }
                }
            });
        }

        // Graphique de disponibilité
        const availabilityCanvas = document.getElementById('availabilityChart');
        if (availabilityCanvas) {
            const ctx = availabilityCanvas.getContext('2d');
            state.charts.availability = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['00h', '06h', '12h', '18h', '24h'],
                    datasets: [{
                        label: 'Disponibilité (%)',
                        data: [98, 95, 92, 96, 99],
                        borderColor: CONFIG.chartColors.primary,
                        backgroundColor: CONFIG.chartColors.primary + '30',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: CONFIG.chartColors.primary,
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }]
                },
                options: {
                    ...commonOptions,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)',
                                drawBorder: false
                            },
                            ticks: {
                                font: {
                                    size: 11
                                },
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false
                            },
                            ticks: {
                                font: {
                                    size: 11
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    /**
     * Configuration des mises à jour automatiques
     */
    function setupAutoUpdate() {
        // Mise à jour initiale
        updateDashboardData();
        
        // Configurer l'intervalle de mise à jour
        state.updateTimer = setInterval(() => {
            updateDashboardData();
        }, CONFIG.updateInterval);
        
        // Nettoyer lors de la fermeture de la page (utilise l'API moderne)
        if (window.PageLifecycle) {
            window.PageLifecycle.registerCleanup(() => {
                if (state.updateTimer) {
                    clearInterval(state.updateTimer);
                }
            }, 'dashboard-energy-cleanup');
        } else {
            // Fallback si PageLifecycle n'est pas chargé
            window.addEventListener('pagehide', () => {
                if (state.updateTimer) {
                    clearInterval(state.updateTimer);
                }
            }, { capture: true });
        }
    }

    /**
     * Mise à jour des données du dashboard
     */
    async function updateDashboardData() {
        if (state.isUpdating) {
            console.log('⏳ Mise à jour déjà en cours...');
            return;
        }

        state.isUpdating = true;
        console.log('🔄 Mise à jour des données...');

        try {
            const response = await fetch('/dashboard/realtime-data', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            
            // Mettre à jour les KPIs
            updateKPIs(data);
            
            // Mettre à jour les graphiques si nécessaire
            // updateCharts(data);
            
            console.log('✅ Données mises à jour avec succès');
        } catch (error) {
            console.error('❌ Erreur lors de la mise à jour:', error);
        } finally {
            state.isUpdating = false;
        }
    }

    /**
     * Mise à jour des KPIs
     */
    function updateKPIs(data) {
        const numberFormatter = new Intl.NumberFormat('fr-FR');
        
        // Total des sessions
        const totalSessions = document.getElementById('total-sessions');
        if (totalSessions && data.totalRecharges !== undefined) {
            animateNumber(totalSessions, parseInt(totalSessions.textContent.replace(/\s/g, '')), data.totalRecharges);
        }
        
        // Sessions actives
        const activeSessions = document.getElementById('active-sessions');
        if (activeSessions && data.rechargesActives !== undefined) {
            animateNumber(activeSessions, parseInt(activeSessions.textContent), data.rechargesActives);
        }
    }

    /**
     * Animation de nombre
     */
    function animateNumber(element, start, end, duration = 1000) {
        const range = end - start;
        const increment = range / (duration / 16);
        let current = start;
        
        const timer = setInterval(() => {
            current += increment;
            if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
                current = end;
                clearInterval(timer);
            }
            element.textContent = new Intl.NumberFormat('fr-FR').format(Math.round(current));
        }, 16);
    }

    /**
     * Configuration des animations au scroll
     */
    function setupScrollAnimations() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observer les cartes
        document.querySelectorAll('.kpi-card, .chart-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
            observer.observe(card);
        });
    }

    /**
     * Configuration des gestionnaires d'événements
     */
    function setupEventHandlers() {
        // Bouton d'actualisation
        const refreshBtn = document.querySelector('.hero-header button');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', (e) => {
                e.preventDefault();
                updateDashboardData();
                
                // Animation de rotation
                const icon = refreshBtn.querySelector('svg');
                if (icon) {
                    icon.style.animation = 'spin 0.6s linear';
                    setTimeout(() => {
                        icon.style.animation = '';
                    }, 600);
                }
            });
        }

        // Sélecteur de période
        const periodSelector = document.querySelector('.period-selector');
        if (periodSelector) {
            periodSelector.addEventListener('change', (e) => {
                console.log('Période sélectionnée:', e.target.value);
                // Ici, vous pouvez charger les données pour la période sélectionnée
                updateDashboardData();
            });
        }
    }

    /**
     * Utilitaire pour formater les nombres
     */
    function formatNumber(num, decimals = 0) {
        return new Intl.NumberFormat('fr-FR', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format(num);
    }

    /**
     * Utilitaire pour formater la devise
     */
    function formatCurrency(amount) {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'EUR'
        }).format(amount);
    }

    /**
     * Utilitaire pour formater les pourcentages
     */
    function formatPercentage(value, decimals = 1) {
        return new Intl.NumberFormat('fr-FR', {
            style: 'percent',
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format(value / 100);
    }

    /**
     * Gestion des erreurs globales
     */
    window.addEventListener('error', (event) => {
        console.error('Erreur globale:', event.error);
    });

    window.addEventListener('unhandledrejection', (event) => {
        console.error('Promise rejetée:', event.reason);
    });

    // Initialisation au chargement du DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDashboard);
    } else {
        initDashboard();
    }

    // Exposer certaines fonctions pour le débogage
    window.EnergyDashboard = {
        state,
        updateDashboardData,
        formatNumber,
        formatCurrency,
        formatPercentage
    };

})();

