@php
    use App\Helpers\TranslationHelper;
    $languageInfo = TranslationHelper::getLanguageInfo();
@endphp

<!-- LANGUAGE SWITCHER COMPONENT -->
<div class="relative inline-block text-left" 
     dir="{{ $languageInfo['direction'] }}" 
     lang="{{ $languageInfo['current'] }}"
     class="{{ $languageInfo['isRTL'] ? 'rtl' : 'ltr' }}">
    
    <button type="button" 
            onclick="switchLanguage()"
            class="inline-flex justify-center items-center w-full rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 transition-colors duration-200"
            title="{{ __('messages.change_language') }}">
        
        <img id="current-flag" 
             src="{{ $languageInfo['currentFlag'] }}" 
             alt="{{ $languageInfo['currentName'] }}" 
             class="w-4 h-auto rounded mr-2">
        
        <span id="current-locale">{{ $languageInfo['currentName'] }}</span>
        
        <svg class="-mr-1 ml-2 h-5 w-5" 
             xmlns="http://www.w3.org/2000/svg" 
             viewBox="0 0 20 20" 
             fill="currentColor" 
             aria-hidden="true">
            <path fill-rule="evenodd" 
                  d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" 
                  clip-rule="evenodd" />
            </svg>
        </button>
</div>

<script>
// Configuration des langues
const languageConfig = @json($languageInfo);

// Fonction pour changer de langue
function switchLanguage() {
    if (!languageConfig.available || languageConfig.available.length === 0) {
        console.error('No available locales configured');
        return;
    }
    
    const nextLocale = languageConfig.next;
    
    // Afficher un indicateur de chargement
    const button = document.querySelector('button[onclick="switchLanguage()"]');
    const originalContent = button.innerHTML;
    button.innerHTML = '<div class="animate-spin rounded-full h-4 w-4 border-b-2 border-gray-900"></div>';
    button.disabled = true;

    fetch('{{ route('language.set.ajax') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        body: JSON.stringify({ locale: nextLocale })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Mettre à jour l'interface avant le rechargement
            updateLanguageDisplay(nextLocale);
            
            // Afficher un message de succès
            showNotification(data.message || '{{ __("messages.language_changed_successfully") }}', 'success');
            
            // Recharger la page pour appliquer les nouvelles traductions
            setTimeout(() => {
                window.location.reload();
            }, 500);
        } else {
            console.error('Failed to switch language:', data.message);
            showNotification(data.message || '{{ __("messages.language_not_supported") }}', 'error');
            button.innerHTML = originalContent;
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error switching language:', error);
        showNotification('{{ __("messages.something_went_wrong") }}', 'error');
        button.innerHTML = originalContent;
        button.disabled = false;
    });
}

// Fonction pour mettre à jour l'affichage de la langue
function updateLanguageDisplay(locale) {
    const flagElement = document.getElementById('current-flag');
    const localeElement = document.getElementById('current-locale');
    
    if (flagElement && localeElement) {
        flagElement.src = `https://flagcdn.com/w20/${getFlagCode(locale)}.png`;
        localeElement.textContent = languageConfig.names[locale] || locale;
    }
}

// Fonction pour obtenir le code du drapeau
function getFlagCode(locale) {
    const flags = {
        'fr': 'fr',
        'en': 'gb',
        'ar': 'ma',
        'es': 'es'
    };
    return flags[locale] || 'fr';
}

// Fonction pour afficher les notifications
function showNotification(message, type = 'info') {
    // Supprimer les notifications existantes
    const existingNotifications = document.querySelectorAll('.language-notification');
    existingNotifications.forEach(notification => notification.remove());
    
    // Créer une notification temporaire
    const notification = document.createElement('div');
    notification.className = `language-notification fixed top-4 right-4 z-50 px-4 py-2 rounded-md text-white shadow-lg ${
        type === 'error' ? 'bg-red-500' : 
        type === 'success' ? 'bg-green-500' : 
        'bg-blue-500'
    }`;
    notification.textContent = message;
    
    // Ajouter une animation d'entrée
    notification.style.transform = 'translateX(100%)';
    notification.style.transition = 'transform 0.3s ease-in-out';
    
    document.body.appendChild(notification);
    
    // Animer l'entrée
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 10);
    
    // Supprimer la notification après 3 secondes
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Initialiser le composant au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si le composant existe
    if (document.querySelector('button[onclick="switchLanguage()"]')) {
        console.log('Language switcher initialized with config:', languageConfig);
    }
});
</script>

<style>
/* Styles pour le support RTL */
.rtl {
    direction: rtl;
}

.ltr {
    direction: ltr;
}

/* Animation pour le bouton de changement de langue */
button[onclick="switchLanguage()"]:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

button[onclick="switchLanguage()"]:active {
    transform: translateY(0);
}

/* Styles pour les notifications */
.language-notification {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    font-size: 14px;
    font-weight: 500;
    border-radius: 6px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    backdrop-filter: blur(10px);
}

/* Responsive design */
@media (max-width: 640px) {
    .language-notification {
        top: 1rem;
        right: 1rem;
        left: 1rem;
        transform: translateY(-100%);
    }
    
    .language-notification.show {
        transform: translateY(0);
    }
}
</style>