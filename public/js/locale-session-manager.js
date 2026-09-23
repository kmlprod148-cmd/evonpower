/**
 * Locale Session Manager - OPTIMIZED VERSION
 * 
 * Simplified to avoid conflicts with Alpine.js language switcher component
 * Only handles:
 * - Locale persistence verification
 * - Session synchronization
 * - Mobile background state handling
 */

(function() {
    'use strict';

    // Debug mode - set to true for verbose logging
    const DEBUG = false;
    const log = DEBUG ? console.log.bind(console) : () => {};

    const LocaleSessionManager = {
        /**
         * Current locale
         */
        currentLocale: document.documentElement.lang || 'fr',

        /**
         * Initialize the manager
         */
        init() {
            // Sync locale on load
            this.syncLocale();
            
            // Setup listeners for persistence
            this.setupListeners();
        },

        /**
         * Sync locale from HTML to localStorage
         */
        syncLocale() {
            const htmlLang = document.documentElement.lang;
            if (htmlLang) {
                this.currentLocale = htmlLang;
                localStorage.setItem('evon_locale', htmlLang);
                log('[Locale Manager] Synced locale:', htmlLang);
            }
        },

        /**
         * Setup event listeners for mobile support
         */
        setupListeners() {
            // CRITICAL FIX: Add lang parameter to all internal links
            log('[Locale Manager] Setting up link interception for lang parameter');
            
            document.addEventListener('click', (e) => {
                const link = e.target.closest('a');
                if (link && link.href && !link.href.includes('javascript:') && !link.href.includes('#')) {
                    try {
                        const url = new URL(link.href);
                        // Skip language switch links - they handle locale via path /language/{locale}
                        if (url.pathname.match(/^\/language\/[^/?#]+$/)) {
                            return;
                        }
                        // Only modify internal links
                        if (url.hostname === window.location.hostname) {
                            // Get current locale from URL or HTML
                            const urlParams = new URLSearchParams(window.location.search);
                            const currentLang = urlParams.get('lang') || document.documentElement.lang || 'fr';
                            
                            // Add or update lang parameter
                            url.searchParams.set('lang', currentLang);
                            link.href = url.toString();
                            log('[Locale Manager] Added lang to link:', currentLang);
                        }
                    } catch(err) {
                        // Ignore malformed URLs
                    }
                }
            }, true);
            
            // Listen for page visibility (mobile app backgrounding)
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    // Page became visible again, verify locale is still correct
                    this.verifyLocaleQuiet();
                }
            });

            // Listen for page show (back/forward cache)
            window.addEventListener('pageshow', (event) => {
                if (event.persisted) {
                    // Page was restored from cache
                    this.verifyLocaleQuiet();
                }
            });

            // Listen for custom locale change events (from Alpine component)
            window.addEventListener('locale-changed', (e) => {
                if (e.detail && e.detail.locale) {
                    this.currentLocale = e.detail.locale;
                    localStorage.setItem('evon_locale', e.detail.locale);
                    log('[Locale Manager] Locale updated via event:', e.detail.locale);
                }
            });
        },

        /**
         * Quietly verify locale with server (no UI updates)
         */
        async verifyLocaleQuiet() {
            try {
                const response = await fetch('/language/current', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });

                if (response.ok) {
                    const data = await response.json();
                    
                    // If server locale differs significantly from client, log warning
                    const clientLocale = document.documentElement.lang;
                    if (data.locale && data.locale !== clientLocale) {
                        console.warn('[Locale Manager] Locale mismatch detected:', {
                            client: clientLocale,
                            server: data.locale
                        });
                        
                        // Update localStorage to match server
                        localStorage.setItem('evon_locale', data.locale);
                        
                        // If mismatch is significant, consider reloading
                        // (but don't do it automatically to avoid reload loops)
                    } else {
                        log('[Locale Manager] Locale verified:', data.locale);
                    }
                }
            } catch (error) {
                console.debug('[Locale Manager] Could not verify locale:', error.message);
            }
        }
    };

    /**
     * Initialize when DOM is ready
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            LocaleSessionManager.init();
        });
    } else {
        LocaleSessionManager.init();
    }

    // Expose manager globally (for debugging)
    window.LocaleSessionManager = LocaleSessionManager;

    log('[Locale Manager] Optimized script loaded');

})();

