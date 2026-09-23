/**
 * EVON Dashboard Premium JavaScript
 * Interactions et animations pour une UX moderne
 * Version: 2.0
 */

(function() {
    'use strict';

    // Debug mode - set to true for verbose logging
    const DEBUG = false;
    const log = DEBUG ? console.log.bind(console) : () => {};

    // ========================================
    // Configuration
    // ========================================
    const config = {
        scrollRevealOffset: 100,
        debounceDelay: 150,
        animationDuration: 300
    };

    // ========================================
    // Utilitaires
    // ========================================
    
    /**
     * Debounce function
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Vérifier si un élément est visible dans le viewport
     */
    function isElementInViewport(el) {
        const rect = el.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    }

    /**
     * Vérifier si un élément est partiellement visible
     */
    function isElementPartiallyVisible(el, offset = config.scrollRevealOffset) {
        const rect = el.getBoundingClientRect();
        const windowHeight = window.innerHeight || document.documentElement.clientHeight;
        
        return (
            rect.top <= windowHeight - offset &&
            rect.bottom >= offset
        );
    }

    // ========================================
    // Scroll Reveal Animation
    // ========================================
    
    function initScrollReveal() {
        const revealElements = document.querySelectorAll('.evon-scroll-reveal');
        
        if (revealElements.length === 0) return;

        const checkVisibility = () => {
            revealElements.forEach(el => {
                if (isElementPartiallyVisible(el) && !el.classList.contains('evon-is-visible')) {
                    el.classList.add('evon-is-visible');
                }
            });
        };

        // Vérification initiale
        checkVisibility();

        // Vérification au scroll (avec debounce)
        window.addEventListener('scroll', debounce(checkVisibility, config.debounceDelay), { passive: true });
    }

    // ========================================
    // Stats Counter Animation
    // ========================================
    
    function animateCounter(element, start, end, duration) {
        const range = end - start;
        const increment = range / (duration / 16); // 60fps
        let current = start;
        
        const timer = setInterval(() => {
            current += increment;
            if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
                current = end;
                clearInterval(timer);
            }
            
            // Format le nombre avec séparateurs de milliers si nécessaire
            const formatted = Math.round(current).toLocaleString('fr-FR');
            element.textContent = formatted;
        }, 16);
    }

    function initStatsCounters() {
        const statValues = document.querySelectorAll('.evon-stat-value[data-count]');
        
        statValues.forEach(el => {
            const endValue = parseFloat(el.dataset.count);
            const startValue = 0;
            const duration = 2000; // 2 secondes

            // Observer pour démarrer l'animation quand l'élément est visible
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !el.dataset.animated) {
                        animateCounter(el, startValue, endValue, duration);
                        el.dataset.animated = 'true';
                        observer.unobserve(el);
                    }
                });
            }, { threshold: 0.5 });

            observer.observe(el);
        });
    }

    // ========================================
    // Chart Filter Buttons
    // ========================================
    
    function initChartFilters() {
        const filterGroups = document.querySelectorAll('.evon-chart-filters');
        
        filterGroups.forEach(group => {
            const buttons = group.querySelectorAll('.evon-chart-filter-btn');
            
            buttons.forEach(button => {
                button.addEventListener('click', function() {
                    // Retirer la classe active de tous les boutons du groupe
                    buttons.forEach(btn => btn.classList.remove('active'));
                    
                    // Ajouter la classe active au bouton cliqué
                    this.classList.add('active');
                    
                    // Trigger custom event pour mettre à jour le graphique
                    const filterValue = this.textContent.trim();
                    const event = new CustomEvent('chartFilterChange', {
                        detail: { filter: filterValue }
                    });
                    group.dispatchEvent(event);
                });
            });
        });
    }

    // ========================================
    // Smooth Scroll
    // ========================================
    
    function initSmoothScroll() {
        const links = document.querySelectorAll('a[href^="#"]');
        
        links.forEach(link => {
            link.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                
                if (href === '#') return;
                
                const target = document.querySelector(href);
                if (!target) return;
                
                e.preventDefault();
                
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            });
        });
    }

    // ========================================
    // Mobile Menu Enhancements
    // ========================================
    
    function initMobileMenuEnhancements() {
        const body = document.body;
        
        // Désactiver le scroll du body quand la sidebar est ouverte
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.attributeName === 'class') {
                    if (body.classList.contains('sidebar-open')) {
                        body.style.overflow = 'hidden';
                    } else {
                        body.style.overflow = '';
                    }
                }
            });
        });

        observer.observe(body, { attributes: true });
    }

    // ========================================
    // Tooltip Enhancement
    // ========================================
    
    function initTooltips() {
        const tooltipElements = document.querySelectorAll('[data-tooltip]');
        
        tooltipElements.forEach(el => {
            const tooltipText = el.dataset.tooltip;
            
            el.addEventListener('mouseenter', function() {
                const tooltip = document.createElement('div');
                tooltip.className = 'evon-tooltip';
                tooltip.textContent = tooltipText;
                tooltip.style.cssText = `
                    position: absolute;
                    background: rgba(0, 0, 0, 0.9);
                    color: white;
                    padding: 0.5rem 0.75rem;
                    border-radius: 0.5rem;
                    font-size: 0.875rem;
                    pointer-events: none;
                    z-index: 9999;
                    white-space: nowrap;
                    animation: evon-fade-in 0.2s ease-out;
                `;
                
                document.body.appendChild(tooltip);
                
                const rect = el.getBoundingClientRect();
                const tooltipRect = tooltip.getBoundingClientRect();
                
                tooltip.style.left = `${rect.left + (rect.width / 2) - (tooltipRect.width / 2)}px`;
                tooltip.style.top = `${rect.top - tooltipRect.height - 8}px`;
                
                el._tooltip = tooltip;
            });
            
            el.addEventListener('mouseleave', function() {
                if (el._tooltip) {
                    el._tooltip.remove();
                    delete el._tooltip;
                }
            });
        });
    }

    // ========================================
    // Card Interactions
    // ========================================
    
    function initCardInteractions() {
        const cards = document.querySelectorAll('.evon-stat-card, .evon-chart-card');
        
        cards.forEach(card => {
            // Ajouter un effet de parallax léger au survol
            card.addEventListener('mousemove', function(e) {
                if (window.innerWidth < 1024) return; // Désactiver sur mobile
                
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                
                const deltaX = (x - centerX) / centerX;
                const deltaY = (y - centerY) / centerY;
                
                const rotateX = deltaY * 2;
                const rotateY = deltaX * -2;
                
                card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-4px)`;
            });
            
            card.addEventListener('mouseleave', function() {
                card.style.transform = '';
            });
        });
    }

    // ========================================
    // Keyboard Navigation
    // ========================================
    
    function initKeyboardNavigation() {
        document.addEventListener('keydown', function(e) {
            // ESC pour fermer les modales et menus
            if (e.key === 'Escape') {
                // Fermer la sidebar mobile
                if (document.body.classList.contains('sidebar-open')) {
                    const closeEvent = new Event('click');
                    document.querySelector('.evon-mobile-sidebar-overlay')?.dispatchEvent(closeEvent);
                }
                
                // Fermer le modal de recherche
                const searchModal = document.getElementById('mobileSearchModal');
                if (searchModal && !searchModal.classList.contains('hidden')) {
                    window.closeMobileSearch?.();
                }
            }
        });
    }

    // ========================================
    // Performance Monitoring (disabled in production)
    // ========================================
    
    function initPerformanceMonitoring() {
        // Disabled - long task warnings are not useful in production
        // Enable only for development debugging if needed
        return;
    }

    // ========================================
    // Theme Toggle Enhancement
    // ========================================
    
    function initThemeToggle() {
        const themeToggle = document.querySelector('[data-theme-toggle]');
        
        if (themeToggle) {
            themeToggle.addEventListener('click', function() {
                // Ajouter une animation de transition
                document.documentElement.classList.add('evon-theme-transition');
                
                setTimeout(() => {
                    document.documentElement.classList.remove('evon-theme-transition');
                }, 300);
            });
        }
    }

    // ========================================
    // Auto-refresh Stats (optionnel)
    // ========================================
    
    function initAutoRefreshStats(interval = 30000) { // 30 secondes par défaut
        const autoRefresh = document.querySelector('[data-auto-refresh]');
        
        if (!autoRefresh) return;
        
        setInterval(() => {
            // Émettre un événement personnalisé pour rafraîchir les stats
            const event = new CustomEvent('refreshStats');
            document.dispatchEvent(event);
            
            log('Stats refreshed at:', new Date().toLocaleTimeString());
        }, interval);
    }

    // ========================================
    // Initialisation
    // ========================================
    
    function init() {
        // Attendre que le DOM soit chargé
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
            return;
        }

        log('🚀 EVON Dashboard Premium initialized');

        // Initialiser tous les modules
        initScrollReveal();
        initStatsCounters();
        initChartFilters();
        initSmoothScroll();
        initMobileMenuEnhancements();
        initTooltips();
        initCardInteractions();
        initKeyboardNavigation();
        initThemeToggle();
        
        // Performance monitoring (développement seulement)
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            initPerformanceMonitoring();
        }

        // Auto-refresh optionnel (décommenter si nécessaire)
        // initAutoRefreshStats(30000);
    }

    // Démarrer l'initialisation
    init();

    // ========================================
    // API Publique
    // ========================================
    
    window.EvonDashboard = {
        animateCounter,
        debounce,
        isElementInViewport,
        config
    };

})();

