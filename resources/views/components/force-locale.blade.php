@php
use App\Helpers\LanguageHelper;

// Forcer la locale si spécifiée
if (isset($locale)) {
    LanguageHelper::forceLocale($locale);
}

// Obtenir les informations de langue
$currentLocale = LanguageHelper::getCurrentLocale();
$sessionLocale = LanguageHelper::getSessionLocale();
$availableLocales = LanguageHelper::getAvailableLocales();
@endphp

{{-- Debug info (optionnel) --}}
@if(config('app.debug'))
<div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4" style="display: none;">
    <div class="flex">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
        </div>
        <div class="ml-3">
            <p class="text-sm">
                <strong>Debug Info:</strong><br>
                Current Locale: {{ $currentLocale }}<br>
                Session Locale: {{ $sessionLocale }}<br>
                Config Locale: {{ config('app.locale') }}<br>
                Welcome Message: {{ __('messages.welcome') }}
            </p>
        </div>
    </div>
</div>
@endif

{{-- Contenu principal --}}
<div class="force-locale-component" data-current-locale="{{ $currentLocale }}" data-session-locale="{{ $sessionLocale }}">
    {{ $slot }}
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Forcer la locale via JavaScript si nécessaire
    const currentLocale = '{{ $currentLocale }}';
    const sessionLocale = '{{ $sessionLocale }}';
    
    console.log('Force Locale Component:', {
        currentLocale: currentLocale,
        sessionLocale: sessionLocale,
        welcomeMessage: '{{ __('messages.welcome') }}'
    });
    
    // Si la session locale est différente de la locale actuelle, forcer le changement
    if (sessionLocale && sessionLocale !== currentLocale) {
        console.log('Locale mismatch detected, forcing locale change...');
        // Optionnel: rediriger vers la page de changement de langue
        // window.location.href = '/language/' + sessionLocale;
    }
});
</script> 