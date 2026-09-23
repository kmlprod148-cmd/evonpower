{{-- 
    Mobile Floating Widgets - Alternative moderne à la Top Utility Bar
    Widgets flottants sur le côté de l'écran mobile
--}}

@auth
<!-- Widgets Flottants Mobile (Visible uniquement sur mobile) -->
<div class="evon-floating-widgets" x-data="{ 
    notifOpen: false, 
    langOpen: false, 
    userOpen: false,
    widgetsVisible: true 
}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    
    {{-- Widget 1: Recherche Mobile --}}
    <button 
        @click="$dispatch('open-mobile-search')"
        class="evon-floating-widget widget-search"
        data-tooltip="{{ __('dashboard.search') ?? 'Rechercher' }}"
        aria-label="{{ __('dashboard.search') ?? 'Rechercher' }}"
        type="button">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <path d="m21 21-4.3-4.3"></path>
        </svg>
    </button>

    {{-- Widget 2: Changeur de Langue --}}
    <div class="relative">
        <button 
            @click="langOpen = !langOpen; notifOpen = false; userOpen = false"
            class="evon-floating-widget widget-language"
            data-tooltip="{{ __('dashboard.language') ?? 'Langue' }}"
            aria-label="{{ __('dashboard.change_language') ?? 'Changer la langue' }}"
            :aria-expanded="langOpen.toString()"
            type="button">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                <path d="M2 12h20"></path>
            </svg>
        </button>

        {{-- Dropdown Langue - Boutons avec redirection explicite pour compatibilité mobile --}}
        <div x-show="langOpen" 
             x-cloak
             @click.away="langOpen = false"
             class="evon-floating-dropdown"
             :class="{ 'active': langOpen }"
             style="top: 0; pointer-events: auto;">
            <div class="p-3 bg-gradient-to-r from-pink-50 to-rose-50 dark:from-gray-800 dark:to-gray-800 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white text-sm">{{ __('dashboard.select_language') ?? 'Choisir la langue' }}</h3>
            </div>
            <div class="p-2">
                @foreach (config('app.locale_names', config('languages.names', ['fr' => 'Français', 'en' => 'English', 'ar' => 'العربية', 'es' => 'Español'])) as $locale => $name)
                    @php $switchUrl = route('language.switch', ['locale' => $locale]); @endphp
                    <button type="button"
                            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-left {{ app()->getLocale() === $locale ? 'bg-pink-50 dark:bg-pink-900/20' : '' }}"
                            @click="langOpen = false; window.location.href = '{{ $switchUrl }}'">
                        <span class="text-2xl">
                            @if($locale === 'fr') 🇫🇷
                            @elseif($locale === 'en') 🇬🇧
                            @elseif($locale === 'ar') 🇸🇦
                            @elseif($locale === 'es') 🇪🇸
                            @else 🌐
                            @endif
                        </span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white flex-1">{{ $name }}</span>
                        @if(app()->getLocale() === $locale)
                            <svg class="w-4 h-4 flex-shrink-0 text-pink-600 dark:text-pink-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Widget 3: Mode Sombre/Clair --}}
    <button 
        @click="$dispatch('toggle-theme')"
        class="evon-floating-widget widget-theme"
        data-tooltip="{{ __('dashboard.theme') ?? 'Thème' }}"
        aria-label="{{ __('dashboard.toggle_theme') ?? 'Changer le thème' }}"
        type="button"
        x-data="{ isDark: localStorage.getItem('theme') === 'dark' }"
        @theme-changed.window="isDark = $event.detail.isDark">
        <svg x-show="!isDark" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="4"></circle>
            <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"></path>
        </svg>
        <svg x-show="isDark" x-cloak xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
        </svg>
    </button>

    {{-- Widget 4: Notifications --}}
    <div class="relative">
        <button 
            @click="notifOpen = !notifOpen; langOpen = false; userOpen = false"
            class="evon-floating-widget widget-notifications"
            data-tooltip="{{ __('dashboard.notifications') ?? 'Notifications' }}"
            aria-label="{{ __('dashboard.notifications') ?? 'Notifications' }}"
            :aria-expanded="notifOpen.toString()"
            type="button">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path>
                <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"></path>
            </svg>
            @if(auth()->user()->unreadNotifications->count() > 0)
                <span class="evon-floating-badge">{{ auth()->user()->unreadNotifications->count() }}</span>
            @endif
        </button>

        {{-- Dropdown Notifications --}}
        <div x-show="notifOpen" 
             x-cloak
             @click.away="notifOpen = false"
             class="evon-floating-dropdown"
             :class="{ 'active': notifOpen }"
             style="top: 0; width: 360px; max-height: 500px;">
            <div class="p-3 bg-gradient-to-r from-orange-50 to-yellow-50 dark:from-gray-800 dark:to-gray-800 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900 dark:text-white text-sm">{{ __('dashboard.notifications') ?? 'Notifications' }}</h3>
                    @if(auth()->user()->unreadNotifications->count() > 0)
                        <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-gradient-to-r from-orange-500 to-red-500 text-white">
                            {{ auth()->user()->unreadNotifications->count() }}
                        </span>
                    @endif
                </div>
            </div>
            <div class="overflow-y-auto max-h-96">
                @forelse(auth()->user()->notifications->take(5) as $notification)
                    <div class="px-3 py-3 border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors {{ $notification->read_at ? 'opacity-60' : '' }}">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-10 h-10 rounded-full bg-gradient-to-br from-orange-400 to-red-400 flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $notification->data['title'] ?? 'Notification' }}
                                </p>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">
                                    {{ $notification->data['message'] ?? '' }}
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ $notification->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="px-4 py-8 text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 mb-3">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('dashboard.no_notifications') ?? 'Aucune notification' }}</p>
                    </div>
                @endforelse
            </div>
            @if(auth()->user()->notifications->count() > 0)
                <div class="p-2 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                    <a href="{{ route('notifications.index') }}" 
                       class="block w-full text-center text-sm font-medium text-orange-600 dark:text-orange-400 hover:text-orange-700 dark:hover:text-orange-300 py-2"
                       @click="notifOpen = false">
                        {{ __('dashboard.view_all') ?? 'Voir tout' }}
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- Widget 5: Menu Utilisateur --}}
    <div class="relative">
        <button 
            @click="userOpen = !userOpen; langOpen = false; notifOpen = false"
            class="evon-floating-widget widget-user"
            data-tooltip="{{ auth()->user()->name }}"
            aria-label="{{ __('dashboard.user_menu') ?? 'Menu utilisateur' }}"
            :aria-expanded="userOpen.toString()"
            type="button">
            <div class="w-8 h-8 rounded-full bg-white/30 flex items-center justify-center text-white font-bold text-sm">
                {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
            </div>
        </button>

        {{-- Dropdown User --}}
        <div x-show="userOpen" 
             x-cloak
             @click.away="userOpen = false"
             class="evon-floating-dropdown"
             :class="{ 'active': userOpen }"
             style="top: 0;">
            <div class="p-4 bg-gradient-to-r from-cyan-50 to-blue-50 dark:from-gray-800 dark:to-gray-800 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-cyan-400 to-blue-600 flex items-center justify-center text-white font-bold text-lg">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400 truncate">{{ auth()->user()->email }}</p>
                    </div>
                </div>
            </div>
            <div class="p-2">
                <a href="{{ route('profile.edit') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                   @click="userOpen = false">
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ __('dashboard.profile') ?? 'Profil' }}</span>
                </a>
                <a href="{{ route('settings.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                   @click="userOpen = false">
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ __('dashboard.settings') ?? 'Paramètres' }}</span>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" 
                            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span class="text-sm font-medium text-red-600 dark:text-red-400">{{ __('dashboard.logout') ?? 'Déconnexion' }}</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>

{{-- Script pour gérer le thème --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Écouter l'événement toggle-theme
    document.addEventListener('toggle-theme', function() {
        const currentTheme = localStorage.getItem('theme') || 'light';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        localStorage.setItem('theme', newTheme);
        
        if (newTheme === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
        
        // Dispatche un événement pour mettre à jour l'icône
        window.dispatchEvent(new CustomEvent('theme-changed', { 
            detail: { isDark: newTheme === 'dark' } 
        }));
        
        // Haptic feedback si disponible
        if (navigator.vibrate) {
            navigator.vibrate(10);
        }
    });
    
    // Écouter l'événement open-mobile-search
    document.addEventListener('open-mobile-search', function() {
        // Ouvrir le modal de recherche mobile existant
        const searchButton = document.querySelector('[\\@click*="mobileSearchOpen"]');
        if (searchButton) {
            searchButton.click();
        }
    });
});
</script>
@endauth

