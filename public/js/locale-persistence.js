/**
 * Locale Persistence Script
 * This script ensures language persists across page loads
 * by adding ?lang= parameter to all navigation
 * 
 * WORKAROUND: This bypasses the middleware issue
 */

(function() {
    'use strict';
    
    // Get current locale from URL parameter or default to 'fr'
    function getCurrentLocale() {
        const urlParams = new URLSearchParams(window.location.search);
        const langParam = urlParams.get('lang');
        
        if (langParam && ['fr', 'en', 'ar', 'es'].includes(langParam)) {
            return langParam;
        }
        
        // Try to get from session via a quick check
        // We'll use the app's current locale if available
        const htmlLang = document.documentElement.lang;
        if (htmlLang && ['fr', 'en', 'ar', 'es'].includes(htmlLang)) {
            return htmlLang;
        }
        
        return 'fr'; // Default
    }
    
    // Add lang parameter to a URL
    function addLangToUrl(url, locale) {
        try {
            const urlObj = new URL(url, window.location.origin);
            urlObj.searchParams.set('lang', locale);
            return urlObj.toString();
        } catch (e) {
            // If URL parsing fails, just return original
            return url;
        }
    }
    
    // Intercept all link clicks and add lang parameter
    function interceptLinks() {
        const currentLocale = getCurrentLocale();
        
        // Store in sessionStorage for quick access
        sessionStorage.setItem('app_locale', currentLocale);
        
        // Intercept all clicks on links
        document.addEventListener('click', function(e) {
            const link = e.target.closest('a');
            
            if (link && link.href) {
                // Only modify internal links
                try {
                    const linkUrl = new URL(link.href);
                    const currentUrl = new URL(window.location.href);
                    
                    if (linkUrl.origin === currentUrl.origin) {
                        // Don't modify if lang is already in URL
                        if (!linkUrl.searchParams.has('lang')) {
                            const locale = sessionStorage.getItem('app_locale') || currentLocale;
                            linkUrl.searchParams.set('lang', locale);
                            link.href = linkUrl.toString();
                        }
                    }
                } catch (err) {
                    // Ignore errors for malformed URLs
                }
            }
        }, true); // Use capture phase to catch before other handlers
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', interceptLinks);
    } else {
        interceptLinks();
    }
    
    // Also intercept form submissions
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (form.method.toLowerCase() === 'get') {
            const locale = sessionStorage.getItem('app_locale') || getCurrentLocale();
            
            // Add hidden input for lang if not already present
            const existingLangInput = form.querySelector('input[name="lang"]');
            if (!existingLangInput) {
                const langInput = document.createElement('input');
                langInput.type = 'hidden';
                langInput.name = 'lang';
                langInput.value = locale;
                form.appendChild(langInput);
            }
        }
    });
    
    console.log('[LOCALE-PERSISTENCE] Initialized with locale:', getCurrentLocale());
})();

