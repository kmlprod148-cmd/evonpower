@props([
    'user' => auth()->user()
])

<div x-data="userProfileMenu()" class="relative">
    <!-- User Avatar Button -->
    <button @click="toggle()"
            class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors focus:outline-none focus:ring-2 focus:ring-eco-green-500"
            :class="{ 'ring-2 ring-eco-green-500': isOpen }"
            :aria-expanded="isOpen.toString()">
        
        <!-- Avatar -->
        <div class="relative">
            @if($user->profile_photo_path)
                <img src="{{ asset('storage/' . $user->profile_photo_path) }}" 
                     alt="{{ $user->name }}"
                     class="w-9 h-9 rounded-lg object-cover ring-2 ring-gray-200 dark:ring-gray-700">
            @else
                @php
                    $roleColors = [
                        'admin' => 'bg-gradient-to-br from-red-500 to-pink-600',
                        'super-admin' => 'bg-gradient-to-br from-purple-500 to-indigo-600',
                        'integrator' => 'bg-gradient-to-br from-blue-500 to-cyan-600',
                        'operator' => 'bg-gradient-to-br from-green-500 to-emerald-600',
                        'partner' => 'bg-gradient-to-br from-yellow-500 to-orange-600',
                        'default' => 'bg-gradient-to-br from-gray-500 to-gray-600'
                    ];
                    $userRole = $user->roles->first()?->name ?? 'default';
                    $avatarColor = $roleColors[$userRole] ?? $roleColors['default'];
                @endphp
                <div class="w-9 h-9 rounded-lg {{ $avatarColor }} flex items-center justify-center text-white font-bold text-sm ring-2 ring-gray-200 dark:ring-gray-700 shadow-md">
                    {{ strtoupper(substr($user->name ?? 'U', 0, 2)) }}
                </div>
            @endif
            
            <!-- Online Status Badge -->
            <span class="absolute bottom-0 right-0 block h-2.5 w-2.5 rounded-full bg-green-400 ring-2 ring-white dark:ring-gray-800 shadow-sm"></span>
        </div>

        <!-- User Info (Hidden on mobile) -->
        <div class="hidden md:block text-left">
            <p class="text-sm font-semibold text-gray-900 dark:text-white leading-tight">
                {{ $user->name ?? 'Utilisateur' }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 leading-tight">
                {{ $user->roles->first()?->name ?? 'Utilisateur' }}
            </p>
        </div>

        <!-- Chevron -->
        <svg class="hidden md:block w-4 h-4 text-gray-400 transition-transform"
             :class="{ 'rotate-180': isOpen }"
             fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <!-- Dropdown Menu -->
    <div x-show="isOpen"
         x-cloak
         @click.away="isOpen = false"
         @keydown.escape.window="isOpen = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute right-0 mt-3 w-80 origin-top-right z-50">
        
        <div class="rounded-xl bg-white dark:bg-gray-800 shadow-2xl ring-1 ring-black ring-opacity-5 overflow-hidden">
            <!-- User Info Card -->
            <div class="px-4 py-4 bg-gradient-to-br from-eco-green-50 via-blue-50 to-purple-50 dark:from-gray-800 dark:via-gray-800 dark:to-gray-800 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <!-- Large Avatar -->
                    @if($user->profile_photo_path)
                        <img src="{{ asset('storage/' . $user->profile_photo_path) }}" 
                             alt="{{ $user->name }}"
                             class="w-16 h-16 rounded-xl object-cover ring-2 ring-white dark:ring-gray-700 shadow-md">
                    @else
                        <div class="w-16 h-16 rounded-xl {{ $avatarColor }} flex items-center justify-center text-white font-bold text-xl ring-2 ring-white dark:ring-gray-700 shadow-md">
                            {{ strtoupper(substr($user->name ?? 'U', 0, 2)) }}
                        </div>
                    @endif

                    <div class="flex-1 min-w-0">
                        <p class="text-base font-bold text-gray-900 dark:text-white truncate">
                            {{ $user->name ?? 'Utilisateur' }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400 truncate">
                            {{ $user->email ?? '' }}
                        </p>
                        <div class="mt-1 flex items-center gap-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $avatarColor }} text-white">
                                {{ $user->roles->first()?->name ?? 'User' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="mt-3 grid grid-cols-3 gap-2">
                    <div class="bg-white dark:bg-gray-700/50 rounded-lg px-2 py-1.5 text-center">
                        <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $user->created_at->diffForHumans(['short' => true]) }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Membre</div>
                    </div>
                    <div class="bg-white dark:bg-gray-700/50 rounded-lg px-2 py-1.5 text-center">
                        <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $user->notifications()->count() }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Notif.</div>
                    </div>
                    <div class="bg-white dark:bg-gray-700/50 rounded-lg px-2 py-1.5 text-center">
                        <div class="text-sm font-bold text-eco-green-600 dark:text-eco-green-400">●</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">En ligne</div>
                    </div>
                </div>
            </div>

            <!-- Menu Items -->
            <div class="py-2">
                <!-- Profile -->
                <a href="{{ route('profile.edit') }}" 
                   class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <div class="w-9 h-9 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <div class="font-medium">Mon Profil</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Gérer vos informations</div>
                    </div>
                    <kbd class="hidden sm:inline-block px-2 py-1 text-xs font-semibold bg-gray-100 dark:bg-gray-700 rounded">⌘P</kbd>
                </a>

                <!-- Settings -->
                <a href="{{ route('settings.index') }}" 
                   class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <div class="w-9 h-9 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center text-purple-600 dark:text-purple-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <div class="font-medium">Paramètres</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Préférences du compte</div>
                    </div>
                    <kbd class="hidden sm:inline-block px-2 py-1 text-xs font-semibold bg-gray-100 dark:bg-gray-700 rounded">⌘S</kbd>
                </a>

                <!-- Activity -->
                <a href="{{ route('activity.index') }}" 
                   class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <div class="w-9 h-9 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center text-green-600 dark:text-green-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <div class="font-medium">Activité</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Historique d'activité</div>
                    </div>
                </a>
            </div>

            <!-- Divider -->
            <div class="border-t border-gray-200 dark:border-gray-700"></div>

            <!-- Help & Support -->
            <div class="py-2">
                <a href="{{ route('help.index') }}" 
                   class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Centre d'aide</span>
                </a>
                <a href="{{ route('feedback.create') }}" 
                   class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                    </svg>
                    <span>Envoyer un feedback</span>
                </a>
            </div>

            <!-- Divider -->
            <div class="border-t border-gray-200 dark:border-gray-700"></div>

            <!-- Logout -->
            <div class="p-2">
                <form method="POST" action="{{ route('logout') }}" id="logoutForm">
                    @csrf
                    <button type="submit"
                            @click.prevent="logout()"
                            class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                        <div class="w-9 h-9 rounded-lg bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </div>
                        <div class="flex-1 text-left">
                            <div class="font-semibold">Déconnexion</div>
                            <div class="text-xs opacity-75">Quitter votre session</div>
                        </div>
                        <kbd class="hidden sm:inline-block px-2 py-1 text-xs font-semibold bg-red-100 dark:bg-red-900/30 rounded">⇧⌘Q</kbd>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function userProfileMenu() {
    return {
        isOpen: false,

        toggle() {
            this.isOpen = !this.isOpen;
        },

        async logout() {
            // Refresh CSRF token before logout
            if (window.refreshLogoutCsrfToken) {
                await window.refreshLogoutCsrfToken();
            }
            
            // Submit the form
            document.getElementById('logoutForm').submit();
        }
    }
}

// Global keyboard shortcuts
document.addEventListener('keydown', (e) => {
    // ⌘P - Profile
    if ((e.metaKey || e.ctrlKey) && e.key === 'p') {
        e.preventDefault();
        window.location.href = '{{ route("profile.edit") }}';
    }
    
    // ⌘S - Settings
    if ((e.metaKey || e.ctrlKey) && e.key === 's') {
        e.preventDefault();
        window.location.href = '{{ route("settings.index") }}';
    }
    
    // ⇧⌘Q - Logout
    if ((e.metaKey || e.ctrlKey) && e.shiftKey && e.key === 'q') {
        e.preventDefault();
        document.getElementById('logoutForm').submit();
    }
});
</script>

