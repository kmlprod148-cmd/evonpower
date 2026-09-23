{{-- 
    LOCALE URL FIX - Solution inline qui ne nécessite aucun fichier JS externe
    À inclure dans le layout principal APRÈS tous les autres scripts
--}}
<script>
(function() {
    console.log('[LOCALE-URL-FIX] Inline script loaded');
    
    // Force reload with lang parameter after language change
    window.addEventListener('load', function() {
        // Intercept ALL clicks on links
        document.addEventListener('click', function(e) {
            const link = e.target.closest('a');
            if (link && link.href && link.hostname === window.location.hostname) {
                // Don't modify special links
                if (link.href.includes('javascript:') || link.href === '#') return;
                
                try {
                    const url = new URL(link.href);
                    // Get current lang from URL or HTML
                    const urlParams = new URLSearchParams(window.location.search);
                    const currentLang = urlParams.get('lang') || document.documentElement.lang || 'fr';
                    
                    // Add lang parameter if not present
                    if (!url.searchParams.has('lang')) {
                        url.searchParams.set('lang', currentLang);
                        link.href = url.toString();
                        console.log('[LOCALE-URL-FIX] Added lang:', currentLang, 'to:', link.href);
                    }
                } catch(e) {}
            }
        }, true); // Use capture phase
        
        console.log('[LOCALE-URL-FIX] Link interception active');
    });
    
    // Override the language switcher's reload behavior
    setTimeout(function() {
        // Look for language switching responses
        const originalFetch = window.fetch;
        window.fetch = function(...args) {
            return originalFetch.apply(this, args).then(function(response) {
                // Check if this is a language switch response
                if (args[0] && args[0].includes('/language/set')) {
                    response.clone().json().then(function(data) {
                        if (data.status === 'success' && data.locale) {
                            console.log('[LOCALE-URL-FIX] Language switch detected:', data.locale);
                            // Force reload with lang parameter
                            const newUrl = window.location.protocol + '//' + window.location.host + 
                                         window.location.pathname + '?lang=' + data.locale +
                                         (window.location.hash || '');
                            console.log('[LOCALE-URL-FIX] Redirecting to:', newUrl);
                            setTimeout(function() {
                                window.location.href = newUrl;
                            }, 300);
                        }
                    }).catch(function() {});
                }
                return response;
            });
        };
        console.log('[LOCALE-URL-FIX] Fetch interceptor active');
    }, 1000);
})();
</script>

