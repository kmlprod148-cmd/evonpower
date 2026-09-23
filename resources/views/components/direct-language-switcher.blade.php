<!-- LANGUAGE SWITCHER COMPONENT -->
@php
    $currentLocale = app()->getLocale();
    $availableLocales = config('app.available_locales', ['fr', 'en', 'ar', 'es']);
    $localeNames = config('app.locale_names', [
        'fr' => 'Français', 
        'en' => 'English', 
        'ar' => 'العربية', 
        'es' => 'Español'
    ]);
    $currentLocaleName = $localeNames[$currentLocale] ?? $currentLocale;
    $flags = [
        'fr' => 'fr', 
        'en' => 'gb', 
        'ar' => 'ma', 
        'es' => 'es'
    ];
    $currentFlag = 'https://flagcdn.com/w20/' . ($flags[$currentLocale] ?? 'fr') . '.png';
    $direction = in_array($currentLocale, config('app.rtl_locales', ['ar'])) ? 'rtl' : 'ltr';
@endphp

{{-- LOCALE PERSISTENCE SCRIPT - Load once per page --}}
@once
<script>
// CRITICAL: Add lang parameter to all internal links for persistence
(function() {
    console.log('[LOCALE-PERSISTENCE] Script loaded');
    
    // Get current lang from URL
    const urlParams = new URLSearchParams(window.location.search);
    const currentLang = urlParams.get('lang') || '{{ app()->getLocale() }}';
    
    console.log('[LOCALE-PERSISTENCE] Current lang:', currentLang);
    
    // Add lang to all links on click
    document.addEventListener('click', function(e) {
        const link = e.target.closest('a');
        if (link && link.href && !link.href.includes('javascript:') && !link.href.includes('#')) {
            try {
                const url = new URL(link.href);
                // Only modify internal links
                if (url.hostname === window.location.hostname) {
                    // Add or update lang parameter
                    url.searchParams.set('lang', currentLang);
                    link.href = url.toString();
                    console.log('[LOCALE-PERSISTENCE] Updated link:', link.href);
                }
            } catch(err) {
                // Ignore malformed URLs
            }
        }
    }, true);
})();
</script>
@endonce

<div class="language-switcher-wrapper relative inline-block text-left z-50" 
     dir="{{ $direction }}" 
     lang="{{ $currentLocale }}"
     id="language-switcher-wrapper">
    <!-- Bouton principal -->
    <button type="button" 
            id="language-switcher-btn"
            onclick="toggleLanguageDropdown()"
            class="language-switcher-btn inline-flex justify-center items-center w-full rounded-lg border border-gray-300 dark:border-gray-600 shadow-md px-4 py-2.5 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-all duration-200 ease-in-out font-medium"
            title="{{ __('messages.change_language') }}"
            aria-label="{{ __('messages.change_language') }}"
            aria-expanded="false"
            aria-haspopup="true">
        <img id="current-flag" 
             src="{{ $currentFlag }}" 
             alt="{{ $currentLocaleName }}" 
             class="w-5 h-5 rounded-sm mr-2.5 shadow-sm"
             loading="lazy">
        <span id="current-locale" class="text-sm font-semibold">{{ $currentLocaleName }}</span>
        <svg id="chevron-icon" 
             class="ml-2.5 h-4 w-4 text-gray-500 dark:text-gray-400 transition-transform duration-200" 
             xmlns="http://www.w3.org/2000/svg" 
             viewBox="0 0 20 20" 
             fill="currentColor" 
             aria-hidden="true">
            <path fill-rule="evenodd" 
                  d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" 
                  clip-rule="evenodd" />
        </svg>
    </button>

    <!-- Menu déroulant -->
    <div id="language-dropdown" 
         class="language-dropdown-menu absolute right-0 mt-2 w-56 rounded-lg shadow-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 opacity-0 invisible transform scale-95 translate-y-[-10px] transition-all duration-200 ease-out origin-top-right z-50 overflow-hidden">
        <div class="py-1.5" role="menu" aria-orientation="vertical">
            @foreach($availableLocales as $locale)
                @php
                    $localeName = $localeNames[$locale] ?? $locale;
                    $flagCode = $flags[$locale] ?? 'fr';
                    $isActive = $locale === $currentLocale;
                @endphp
                <button type="button"
                        onclick="selectLanguage('{{ $locale }}')"
                        class="language-option w-full text-left px-4 py-3 flex items-center text-sm font-medium transition-colors duration-150 {{ $isActive ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}"
                        role="menuitem"
                        tabindex="{{ $isActive ? '0' : '-1' }}"
                        data-locale="{{ $locale }}"
                        aria-label="{{ __('messages.change_language') }}: {{ $localeName }}">
                    <img src="https://flagcdn.com/w20/{{ $flagCode }}.png" 
                         alt="{{ $localeName }}" 
                         class="w-5 h-5 rounded-sm mr-3 shadow-sm"
                         loading="lazy">
                    <span class="flex-1">{{ $localeName }}</span>
                    @if($isActive)
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    @endif
                </button>
            @endforeach
        </div>
    </div>
</div>

<script>
// Encapsuler dans une IIFE pour éviter les conflits
(function() {
    'use strict';
    
    // Configuration des langues
    const languageConfig = {
        currentLocale: '{{ app()->getLocale() }}',
        availableLocales: {!! json_encode(config('app.available_locales', ['fr', 'en', 'ar', 'es'])) !!},
        localeNames: {!! json_encode(config('app.locale_names', ['fr' => 'Français', 'en' => 'English', 'ar' => 'العربية', 'es' => 'Español'])) !!},
        flags: {
            'fr': 'fr',
            'en': 'gb',
            'ar': 'ma',
            'es': 'es'
        },
        messages: {
            loading: '{{ __('messages.loading') }}',
            languageChanged: '{{ __('messages.language_changed_successfully') }}',
            errorOccurred: '{{ __('messages.error_occurred') ?? 'An error occurred' }}',
            connectionError: '{{ __('messages.connection_error') ?? 'Connection error' }}',
            securityTokenMissing: '{{ __('messages.security_token_missing') ?? 'Security token missing' }}'
        }
    };

    // État du menu déroulant - utiliser un objet pour éviter les conflits globaux
    const languageSwitcherState = {
        isDropdownOpen: false,
        isSwitching: false
    };

    // Fonction pour ouvrir/fermer le menu déroulant
    window.toggleLanguageDropdown = function toggleLanguageDropdown() {
        const dropdown = document.getElementById('language-dropdown');
        const button = document.getElementById('language-switcher-btn');
        const chevron = document.getElementById('chevron-icon');
        const languageOptions = document.querySelectorAll('.language-option');
        
        if (!dropdown || !button) return;
        
        // Empêcher l'ouverture si un changement de langue est en cours
        if (languageSwitcherState.isSwitching) return;
        
        languageSwitcherState.isDropdownOpen = !languageSwitcherState.isDropdownOpen;
        
        if (languageSwitcherState.isDropdownOpen) {
        dropdown.classList.remove('opacity-0', 'invisible', 'scale-95', 'translate-y-[-10px]');
        dropdown.classList.add('opacity-100', 'visible', 'scale-100', 'translate-y-0');
        button.setAttribute('aria-expanded', 'true');
        if (chevron) {
            chevron.classList.add('rotate-180');
        }
        // Activer le tabindex pour toutes les options quand le menu est ouvert
        languageOptions.forEach(option => {
            option.setAttribute('tabindex', '0');
        });
        // Focus sur la première option
        if (languageOptions.length > 0) {
            setTimeout(() => languageOptions[0].focus(), 100);
        }
        } else {
            dropdown.classList.remove('opacity-100', 'visible', 'scale-100', 'translate-y-0');
            dropdown.classList.add('opacity-0', 'invisible', 'scale-95', 'translate-y-[-10px]');
            button.setAttribute('aria-expanded', 'false');
            if (chevron) {
                chevron.classList.remove('rotate-180');
            }
            // Désactiver le tabindex pour toutes les options quand le menu est fermé
            languageOptions.forEach((option, index) => {
                const isActive = option.getAttribute('data-locale') === languageConfig.currentLocale;
                option.setAttribute('tabindex', isActive ? '0' : '-1');
            });
        }
    }

    // Fonction pour sélectionner une langue
    window.selectLanguage = function selectLanguage(locale) {
        // Empêcher les clics multiples
        if (languageSwitcherState.isSwitching) {
            return;
        }
        
        if (!locale || locale === languageConfig.currentLocale) {
            toggleLanguageDropdown();
            return;
        }

        const button = document.getElementById('language-switcher-btn');
        if (!button) return;

        // Marquer comme en cours de changement
        languageSwitcherState.isSwitching = true;

        // Fermer le menu déroulant
        toggleLanguageDropdown();

        // Sauvegarder le contenu original
        const originalContent = button.innerHTML;
        
        // Désactiver le bouton et afficher un indicateur de chargement
        button.disabled = true;
        button.innerHTML = `
            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-gray-700 dark:border-gray-300"></div>
            <span class="ml-2 text-sm">${languageConfig.messages.loading}</span>
        `;

        // Obtenir le token CSRF
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!csrfToken) {
            console.error('CSRF token not found');
            showNotification(languageConfig.messages.securityTokenMissing, 'error');
            button.innerHTML = originalContent;
            button.disabled = false;
            languageSwitcherState.isSwitching = false;
            return;
        }

        // Envoyer la requête
        fetch('{{ route('language.set.ajax') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin', // Important pour envoyer les cookies de session
            body: JSON.stringify({ locale: locale })
        })
        .then(response => {
            // Vérifier si la réponse est ok
            if (!response.ok) {
                return response.json().then(data => {
                    throw new Error(data.message || `HTTP error! status: ${response.status}`);
                }).catch(() => {
                    throw new Error(`HTTP error! status: ${response.status}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                // CRITICAL FIX: Reload immediately with lang parameter
                // Don't wait, don't show notifications, just reload with lang in URL
                
                // Build new URL with lang parameter
                let newUrl = window.location.protocol + '//' + window.location.host + window.location.pathname;
                
                // Add lang parameter
                const separator = window.location.search ? '&' : '?';
                newUrl += (window.location.search || '') + (window.location.search.includes('lang=') ? '' : separator + 'lang=' + locale);
                
                // If lang already exists in URL, replace it
                newUrl = newUrl.replace(/([?&])lang=[^&]*/, '$1lang=' + locale);
                
                // Add hash if present
                if (window.location.hash) {
                    newUrl += window.location.hash;
                }
                
                console.log('[LANGUAGE-SWITCH] Redirecting to:', newUrl);
                
                // Force reload with new URL
                window.location.href = newUrl;
            } else {
                throw new Error(data.message || languageConfig.messages.errorOccurred);
            }
        })
        .catch(error => {
            console.error('Error switching language:', error);
            showNotification(error.message || languageConfig.messages.connectionError, 'error');
            button.innerHTML = originalContent;
            button.disabled = false;
            languageSwitcherState.isSwitching = false;
        });
    }

    // Fonction pour mettre à jour l'affichage de la langue
    function updateLanguageDisplay(locale) {
        const flagElement = document.getElementById('current-flag');
        const localeElement = document.getElementById('current-locale');
        
        if (flagElement && localeElement) {
            flagElement.src = `https://flagcdn.com/w20/${languageConfig.flags[locale] || 'fr'}.png`;
            localeElement.textContent = languageConfig.localeNames[locale] || locale;
            languageConfig.currentLocale = locale;
        }
    }

    // Fonction pour afficher les notifications
    function showNotification(message, type = 'info') {
        // Supprimer les notifications existantes
        const existingNotifications = document.querySelectorAll('.language-notification');
        existingNotifications.forEach(notification => notification.remove());
        
        // Créer une notification temporaire
        const notification = document.createElement('div');
        notification.className = `language-notification fixed top-4 right-4 z-50 px-4 py-3 rounded-lg text-white shadow-lg max-w-sm ${
            type === 'error' ? 'bg-red-500 border-red-600' : 
            type === 'success' ? 'bg-green-500 border-green-600' : 
            'bg-blue-500 border-blue-600'
        }`;
        
        // Ajouter une icône selon le type
        const icon = type === 'error' ? '❌' : type === 'success' ? '✅' : 'ℹ️';
        notification.innerHTML = `
            <div class="flex items-center">
                <span class="mr-2">${icon}</span>
                <span class="text-sm font-medium">${message}</span>
            </div>
        `;
        
        // Ajouter une animation d'entrée
        notification.style.transform = 'translateX(100%)';
        notification.style.transition = 'transform 0.3s ease-in-out, opacity 0.3s ease-in-out';
        notification.style.opacity = '0';
        
        document.body.appendChild(notification);
        
        // Animer l'entrée
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
            notification.style.opacity = '1';
        }, 10);
        
        // Supprimer la notification après 4 secondes
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            notification.style.opacity = '0';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 4000);
    }

    // Fermer le menu déroulant quand on clique en dehors
    document.addEventListener('click', function(event) {
        const wrapper = document.getElementById('language-switcher-wrapper');
        const dropdown = document.getElementById('language-dropdown');
        const button = document.getElementById('language-switcher-btn');
        
        if (!wrapper || !dropdown || !button) return;
        
        // Si le clic est en dehors du composant, fermer le menu
        if (languageSwitcherState.isDropdownOpen && !wrapper.contains(event.target)) {
            toggleLanguageDropdown();
        }
    });

    // Fermer le menu avec la touche Escape
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && languageSwitcherState.isDropdownOpen) {
            toggleLanguageDropdown();
        }
    });

    // Initialiser le composant au chargement de la page
    document.addEventListener('DOMContentLoaded', function() {
        // Vérifier si le composant existe
        const button = document.getElementById('language-switcher-btn');
        const wrapper = document.getElementById('language-switcher-wrapper');
        
        if (button && wrapper) {
            // Navigation au clavier
            button.addEventListener('keydown', function(event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    toggleLanguageDropdown();
                }
            });
            
            // Navigation dans le menu avec le clavier
            const languageOptions = document.querySelectorAll('.language-option');
            languageOptions.forEach((option, index) => {
                option.addEventListener('keydown', function(event) {
                    const locale = this.getAttribute('data-locale');
                    
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        selectLanguage(locale);
                    } else if (event.key === 'ArrowDown') {
                        event.preventDefault();
                        const nextOption = languageOptions[index + 1] || languageOptions[0];
                        nextOption.focus();
                    } else if (event.key === 'ArrowUp') {
                        event.preventDefault();
                        const prevOption = languageOptions[index - 1] || languageOptions[languageOptions.length - 1];
                        prevOption.focus();
                    } else if (event.key === 'Escape') {
                        event.preventDefault();
                        toggleLanguageDropdown();
                        button.focus();
                    } else if (event.key === 'Home') {
                        event.preventDefault();
                        languageOptions[0].focus();
                    } else if (event.key === 'End') {
                        event.preventDefault();
                        languageOptions[languageOptions.length - 1].focus();
                    }
                });
            });
        }
    });
})(); // Fin de l'IIFE
</script>

<style>
/* Styles pour le composant de changement de langue */
.language-switcher-wrapper {
    position: relative;
}

.language-switcher-btn {
    min-width: 120px;
    cursor: pointer;
    user-select: none;
}

.language-switcher-btn:focus {
    outline: 2px solid transparent;
    outline-offset: 2px;
}

.language-switcher-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Menu déroulant */
.language-dropdown-menu {
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

.language-dropdown-menu::before {
    content: '';
    position: absolute;
    top: -8px;
    right: 16px;
    width: 0;
    height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-bottom: 8px solid white;
}

.dark .language-dropdown-menu::before {
    border-bottom-color: #1f2937;
}

/* Options de langue */
.language-option {
    position: relative;
    cursor: pointer;
    outline: none;
}

.language-option:focus {
    outline: 2px solid transparent;
    outline-offset: -2px;
    background-color: rgba(59, 130, 246, 0.1);
}

.language-option:focus-visible {
    outline: 2px solid #3b82f6;
    outline-offset: -2px;
}

.language-option img {
    flex-shrink: 0;
}

/* Styles pour les notifications */
.language-notification {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    font-size: 14px;
    font-weight: 500;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15), 0 4px 6px rgba(0, 0, 0, 0.1);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid;
    animation: slideInRight 0.3s ease-out;
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* Animation de chargement */
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.animate-spin {
    animation: spin 1s linear infinite;
}

/* Support RTL */
[dir="rtl"] .language-switcher-btn {
    text-align: right;
}

[dir="rtl"] .language-switcher-btn img {
    margin-left: 0.5rem;
    margin-right: 0;
}

[dir="rtl"] .language-switcher-btn svg {
    margin-right: 0.5rem;
    margin-left: 0;
}

[dir="rtl"] .language-dropdown-menu {
    right: auto;
    left: 0;
}

[dir="rtl"] .language-dropdown-menu::before {
    right: auto;
    left: 16px;
}

[dir="rtl"] .language-option {
    text-align: right;
}

[dir="rtl"] .language-option img {
    margin-left: 0.75rem;
    margin-right: 0;
}

/* Responsive design */
@media (max-width: 640px) {
    .language-notification {
        top: 1rem;
        right: 1rem;
        left: 1rem;
        max-width: none;
        font-size: 13px;
    }
    
    .language-switcher-btn {
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        min-width: 100px;
    }
    
    .language-dropdown-menu {
        width: 200px;
        right: 0;
    }
    
    [dir="rtl"] .language-dropdown-menu {
        left: 0;
        right: auto;
    }
}

/* Améliorations pour l'accessibilité */
@media (prefers-reduced-motion: reduce) {
    .language-dropdown-menu,
    .language-switcher-btn,
    .language-option,
    .language-notification {
        transition: none !important;
        animation: none !important;
    }
}

/* Amélioration du contraste pour l'accessibilité */
.language-option {
    color: #374151;
}

.dark .language-option {
    color: #e5e7eb;
}

.language-option:hover {
    background-color: #f3f4f6;
}

.dark .language-option:hover {
    background-color: #374151;
}

/* Effet de surbrillance pour l'option active */
.language-option.bg-blue-50 {
    background-color: #eff6ff !important;
    border-left: 3px solid #3b82f6;
    padding-left: calc(1rem - 3px);
}

.dark .language-option.bg-blue-900\/20 {
    background-color: rgba(30, 58, 138, 0.2) !important;
    border-left: 3px solid #60a5fa;
}

/* Transition douce pour le chevron */
#chevron-icon {
    transition: transform 0.2s ease-in-out;
}
</style>
