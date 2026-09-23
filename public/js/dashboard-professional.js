(function () {
    'use strict';

    const COLORS = {
        success: '#16a34a',
        successSoft: 'rgba(22, 163, 74, 0.18)',
        info: '#0284c7',
        infoSoft: 'rgba(2, 132, 199, 0.18)',
        energy: '#d97706',
        energySoft: 'rgba(217, 119, 6, 0.18)',
        danger: '#dc2626',
        dangerSoft: 'rgba(220, 38, 38, 0.18)',
        slate: '#64748b',
        slateSoft: 'rgba(100, 116, 139, 0.18)',
        white: '#ffffff',
        ink: '#0f172a',
        inkSoft: '#475569',
        darkInk: '#e2e8f0',
        darkInkSoft: '#94a3b8'
    };

    function isDarkMode() {
        return document.documentElement.classList.contains('dark');
    }

    function getTextColor() {
        return isDarkMode() ? COLORS.darkInk : COLORS.ink;
    }

    function getMutedTextColor() {
        return isDarkMode() ? COLORS.darkInkSoft : COLORS.inkSoft;
    }

    function parsePayload() {
        const node = document.getElementById('dashboard-professional-data');
        if (!node) {
            return {};
        }

        try {
            return JSON.parse(node.textContent || '{}');
        } catch (error) {
            return {};
        }
    }

    function normalizeSeries(series, fallback) {
        if (!series || !Array.isArray(series.labels) || !Array.isArray(series.values) || series.labels.length === 0) {
            return fallback;
        }

        return {
            labels: series.labels,
            values: series.values
        };
    }

    function createGradient(ctx, stops, horizontal) {
        const gradient = horizontal
            ? ctx.createLinearGradient(0, 0, ctx.canvas.width, 0)
            : ctx.createLinearGradient(0, 0, 0, ctx.canvas.height);

        stops.forEach(function (stop, index) {
            gradient.addColorStop(index / Math.max(stops.length - 1, 1), stop);
        });

        return gradient;
    }

    function baseScales(lightOnDark) {
        const tickColor = lightOnDark ? 'rgba(255,255,255,0.86)' : getMutedTextColor();
        const gridColor = lightOnDark ? 'rgba(255,255,255,0.14)' : 'rgba(148,163,184,0.16)';

        return {
            x: {
                ticks: {
                    color: tickColor,
                    font: { size: 11, weight: 600 }
                },
                grid: {
                    color: gridColor,
                    drawBorder: false,
                    display: !lightOnDark
                }
            },
            y: {
                beginAtZero: true,
                ticks: {
                    color: tickColor,
                    font: { size: 11, weight: 600 }
                },
                grid: {
                    color: gridColor,
                    drawBorder: false
                }
            }
        };
    }

    function createSessionsChart(canvasId, series) {
        const canvas = document.getElementById(canvasId);
        if (!canvas || typeof Chart === 'undefined') {
            return null;
        }

        const ctx = canvas.getContext('2d');
        const data = normalizeSeries(series, {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            values: [0, 0, 0, 0, 0, 0, 0]
        });

        const fill = createGradient(ctx, ['rgba(255,255,255,0.28)', 'rgba(255,255,255,0.04)'], false);

        return new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.values,
                    borderColor: COLORS.white,
                    backgroundColor: fill,
                    fill: true,
                    borderWidth: 3,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: COLORS.white,
                    pointBorderWidth: 0,
                    tension: 0.38
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15,23,42,0.92)',
                        titleColor: COLORS.white,
                        bodyColor: 'rgba(255,255,255,0.84)',
                        displayColors: false,
                        padding: 12,
                        cornerRadius: 12
                    }
                },
                scales: baseScales(true)
            }
        });
    }

    function createEnergyChart(canvasId, series) {
        const canvas = document.getElementById(canvasId);
        if (!canvas || typeof Chart === 'undefined') {
            return null;
        }

        const ctx = canvas.getContext('2d');
        const data = normalizeSeries(series, {
            labels: ['00:00', '04:00', '08:00', '12:00', '16:00', '20:00'],
            values: [0, 0, 0, 0, 0, 0]
        });

        const barGradient = createGradient(ctx, ['rgba(255,255,255,0.95)', 'rgba(255,255,255,0.35)'], false);

        return new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.values,
                    backgroundColor: barGradient,
                    borderRadius: 12,
                    borderSkipped: false,
                    maxBarThickness: 28
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15,23,42,0.92)',
                        titleColor: COLORS.white,
                        bodyColor: 'rgba(255,255,255,0.84)',
                        displayColors: false,
                        padding: 12,
                        cornerRadius: 12,
                        callbacks: {
                            label: function (context) {
                                return context.raw + ' kWh';
                            }
                        }
                    }
                },
                scales: baseScales(true)
            }
        });
    }

    function createChargerTypesChart(canvasId, series) {
        const canvas = document.getElementById(canvasId);
        if (!canvas || typeof Chart === 'undefined') {
            return null;
        }

        const ctx = canvas.getContext('2d');
        const data = normalizeSeries(series, {
            labels: ['Type 2'],
            values: [1]
        });

        const colors = [
            COLORS.success,
            COLORS.info,
            '#14b8a6',
            '#6366f1',
            COLORS.energy,
            COLORS.danger
        ];

        return new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.values,
                    backgroundColor: data.values.map(function (_, index) {
                        return colors[index % colors.length];
                    }),
                    borderWidth: 0,
                    spacing: 4,
                    cutout: '68%'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: getTextColor(),
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 18,
                            font: { size: 11, weight: 600 }
                        }
                    },
                    tooltip: {
                        displayColors: false,
                        padding: 12,
                        cornerRadius: 12
                    }
                }
            }
        });
    }

    function createStationPerformanceChart(canvasId, series) {
        const canvas = document.getElementById(canvasId);
        if (!canvas || typeof Chart === 'undefined') {
            return null;
        }

        const ctx = canvas.getContext('2d');
        const data = normalizeSeries(series, {
            labels: ['Station'],
            values: [0]
        });

        const barGradient = createGradient(ctx, [COLORS.success, COLORS.info], true);

        return new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.values,
                    backgroundColor: barGradient,
                    borderRadius: 10,
                    borderSkipped: false,
                    maxBarThickness: 20
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        displayColors: false,
                        padding: 12,
                        cornerRadius: 12,
                        callbacks: {
                            label: function (context) {
                                return context.raw + ' sessions';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            color: getMutedTextColor(),
                            font: { size: 11, weight: 600 }
                        },
                        grid: {
                            color: 'rgba(148,163,184,0.16)',
                            drawBorder: false
                        }
                    },
                    y: {
                        ticks: {
                            color: getTextColor(),
                            font: { size: 11, weight: 700 }
                        },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    function createAvailabilityChart(canvasId, series) {
        const canvas = document.getElementById(canvasId);
        if (!canvas || typeof Chart === 'undefined') {
            return null;
        }

        const ctx = canvas.getContext('2d');
        const data = normalizeSeries(series, {
            labels: ['Available', 'Charging', 'Offline', 'Faulted'],
            values: [0, 0, 0, 0]
        });

        return new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.values,
                    backgroundColor: [COLORS.success, COLORS.info, COLORS.slate, COLORS.danger],
                    borderRadius: 10,
                    borderSkipped: false,
                    maxBarThickness: 26
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        displayColors: false,
                        padding: 12,
                        cornerRadius: 12,
                        callbacks: {
                            label: function (context) {
                                return context.raw + '%';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            color: getMutedTextColor(),
                            font: { size: 11, weight: 700 }
                        },
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        suggestedMax: 100,
                        ticks: {
                            color: getMutedTextColor(),
                            callback: function (value) {
                                return value + '%';
                            },
                            font: { size: 11, weight: 600 }
                        },
                        grid: {
                            color: 'rgba(148,163,184,0.16)',
                            drawBorder: false
                        }
                    }
                }
            }
        });
    }

    function createPerformanceRadarChart(canvasId, series) {
        const canvas = document.getElementById(canvasId);
        if (!canvas || typeof Chart === 'undefined') {
            return null;
        }

        const ctx = canvas.getContext('2d');
        const data = normalizeSeries(series, {
            labels: ['Availability', 'Utilization', 'Success', 'API', 'Energy', 'Capacity'],
            values: [0, 0, 0, 0, 0, 0]
        });

        return new Chart(ctx, {
            type: 'radar',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.values,
                    backgroundColor: 'rgba(22, 163, 74, 0.18)',
                    borderColor: COLORS.success,
                    borderWidth: 2.5,
                    pointBackgroundColor: COLORS.success,
                    pointBorderColor: COLORS.white,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            backdropColor: 'transparent',
                            color: getMutedTextColor(),
                            showLabelBackdrop: false
                        },
                        angleLines: {
                            color: 'rgba(148,163,184,0.18)'
                        },
                        grid: {
                            color: 'rgba(148,163,184,0.18)'
                        },
                        pointLabels: {
                            color: getTextColor(),
                            font: { size: 11, weight: 700 }
                        }
                    }
                }
            }
        });
    }

    function createSparkline(canvasId, values, color) {
        const canvas = document.getElementById(canvasId);
        if (!canvas || typeof Chart === 'undefined') {
            return null;
        }

        const ctx = canvas.getContext('2d');
        const fill = createGradient(ctx, [color.replace(')', ', 0.16)').replace('rgb', 'rgba'), 'rgba(255,255,255,0)'], false);

        return new Chart(ctx, {
            type: 'line',
            data: {
                labels: values.map(function (_, index) { return index + 1; }),
                datasets: [{
                    data: values,
                    borderColor: color,
                    backgroundColor: fill,
                    fill: true,
                    borderWidth: 2,
                    pointRadius: 0,
                    tension: 0.38
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { enabled: false } },
                scales: { x: { display: false }, y: { display: false } },
                interaction: { intersect: false }
            }
        });
    }

    function animateCounter(element, endValue) {
        const duration = 1200;
        const startValue = 0;
        const startTime = performance.now();

        function step(currentTime) {
            const progress = Math.min((currentTime - startTime) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 4);
            const current = Math.round(startValue + (endValue - startValue) * eased);
            element.textContent = current.toLocaleString();

            if (progress < 1) {
                requestAnimationFrame(step);
            }
        }

        requestAnimationFrame(step);
    }

    function initCounters() {
        document.querySelectorAll('[data-counter]').forEach(function (node) {
            const value = Number(node.getAttribute('data-counter') || 0);
            animateCounter(node, Number.isFinite(value) ? value : 0);
        });
    }

    function initCharts() {
        if (typeof Chart === 'undefined') {
            return;
        }

        Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
        Chart.defaults.color = getMutedTextColor();
        Chart.defaults.plugins.tooltip.backgroundColor = isDarkMode() ? 'rgba(15,23,42,0.94)' : 'rgba(255,255,255,0.96)';
        Chart.defaults.plugins.tooltip.titleColor = isDarkMode() ? COLORS.white : COLORS.ink;
        Chart.defaults.plugins.tooltip.bodyColor = isDarkMode() ? 'rgba(255,255,255,0.86)' : COLORS.inkSoft;
        Chart.defaults.plugins.tooltip.borderWidth = 0;
        Chart.defaults.plugins.tooltip.displayColors = false;

        const payload = parsePayload();

        createSessionsChart('sessionsChartPro', payload.sessions);
        createEnergyChart('energyChartPro', payload.energy);
        createChargerTypesChart('chargerTypesChartPro', payload.chargerTypes);
        createStationPerformanceChart('stationPerformanceChartPro', payload.stationPerformance);
        createAvailabilityChart('availabilityChartPro', payload.availability);
        createPerformanceRadarChart('performanceRadarChartPro', payload.performanceRadar);

        const spark = payload.sparklines || {};
        createSparkline('sparkline1', spark.sessions || [0, 0, 0, 0], 'rgb(22, 163, 74)');
        createSparkline('sparkline2', spark.active || [0, 0, 0, 0], 'rgb(2, 132, 199)');
        createSparkline('sparkline3', spark.energy || [0, 0, 0, 0], 'rgb(217, 119, 6)');
        createSparkline('sparkline4', spark.stations || [0, 0, 0, 0], 'rgb(100, 116, 139)');
    }

    function init() {
        initCounters();
        initCharts();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.EVONDashboard = { init: init };
})();
