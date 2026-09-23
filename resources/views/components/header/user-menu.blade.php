@php
    use App\Helpers\UserRoleHelper;
    
    $user = auth()->user();
    $roleStyles = UserRoleHelper::getRoleStyles($user);
@endphp

<div class="relative" 
     x-data="window.userMenu ? window.userMenu() : {}" 
     @click.away="close()"
     @keydown.escape.window="close()">
    
    <!-- User Menu Button -->
    <button 
        @click.stop="toggle()"
        class="group relative flex items-center gap-2 px-2 py-1.5 rounded-xl transition-all duration-300 ease-out hover:bg-white/50 dark:hover:bg-gray-800/50 hover:shadow-lg hover:shadow-black/5 dark:hover:shadow-black/20 focus:outline-none focus:ring-2 focus:ring-eco-green-500/50 focus:ring-offset-2 dark:focus:ring-offset-gray-900 {{ app()->getLocale() === 'ar' ? 'flex-row-reverse' : '' }}" 
        id="userMenuButton"
        aria-haspopup="true"
        :aria-expanded="isOpen.toString()"
        :class="{ 'bg-white/50 dark:bg-gray-800/50 shadow-lg': isOpen }">
        
        <!-- Avatar Container with Animated Ring -->
        <div class="relative">
            <!-- Animated Ring on Hover -->
            <div class="absolute inset-0 rounded-full {{ $roleStyles['border'] }} opacity-0 group-hover:opacity-100 transition-opacity duration-300 scale-105 blur-sm"></div>
            
            <!-- Avatar -->
            <div class="relative h-9 w-9 rounded-full overflow-hidden ring-2 {{ $roleStyles['border'] }} transition-all duration-300 group-hover:scale-105 group-hover:ring-4">
                @if($user->profile_photo_path)
                    <img class="h-full w-full object-cover" 
                         src="{{ asset('storage/' . $user->profile_photo_path) }}" 
                         alt="{{ $user->name }}" 
                         loading="lazy" />
                @else
                    <div class="h-full w-full flex items-center justify-center bg-gradient-to-br {{ $roleStyles['gradient'] }} {{ $roleStyles['text'] }}">
                        <span class="text-lg font-semibold select-none">
                            {{ $roleStyles['role_emoji'] }}
                        </span>
                    </div>
                @endif
            </div>
            
            <!-- Online Status Badge with Pulse Animation -->
            <span class="absolute -bottom-1 -right-1 block">
                <span class="relative flex h-3 w-3 user-status-dot">
                    <!-- Ping Effect -->
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                    <!-- Solid Dot -->
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500 ring-2 ring-white dark:ring-gray-900 shadow-sm"></span>
                </span>
            </span>
        </div>
        
        <!-- User Info (Hidden on Mobile) -->
        <div class="hidden md:flex flex-col min-w-0 flex-1 {{ app()->getLocale() === 'ar' ? 'items-end text-right' : 'items-start text-left' }}">
            <span class="text-xs font-semibold text-gray-900 dark:text-white truncate max-w-[120px] group-hover:text-eco-green-600 dark:group-hover:text-eco-green-400 transition-colors">
                {{ $user->name ?? 'Utilisateur' }}
            </span>
            <span class="text-[10px] {{ $roleStyles['text'] }} truncate font-medium">
                {{ $roleStyles['role_name'] }}
            </span>
        </div>
        
        <!-- Chevron Icon with Rotation Animation -->
        <svg class="hidden md:block h-3.5 w-3.5 text-gray-500 dark:text-gray-400 transition-transform duration-300 ease-out" 
             :class="{ 'rotate-180': isOpen }"
             fill="none" 
             viewBox="0 0 24 24" 
             stroke="currentColor"
             stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>
    
    <!-- Dropdown Menu -->
    <div id="userDropdownMenu"
         x-show="isOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
         class="absolute right-0 mt-3 w-72 sm:w-80 origin-top-right z-50"
         role="menu"
         aria-orientation="vertical"
         aria-labelledby="userMenuButton">
        
        <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-2xl shadow-black/10 dark:shadow-black/40 ring-1 ring-gray-900/5 dark:ring-white/10 overflow-hidden backdrop-blur-xl">
            
            <!-- User Profile Header -->
            <div class="relative px-4 py-5 bg-gradient-to-br {{ $roleStyles['gradient'] }} overflow-hidden">
                <!-- Decorative Background Pattern -->
                <div class="absolute inset-0 opacity-10">
                    <svg class="absolute -right-8 -top-8 h-32 w-32" fill="currentColor" viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="40"/>
                    </svg>
                </div>
                
                <div class="relative flex items-center gap-4">
                    <!-- Large Avatar -->
                    <div class="relative flex-shrink-0">
                        <div class="h-16 w-16 rounded-2xl overflow-hidden ring-4 ring-white/50 dark:ring-gray-900/50 shadow-xl">
                            @if($user->profile_photo_path)
                                <img class="h-full w-full object-cover" 
                                     src="{{ asset('storage/' . $user->profile_photo_path) }}" 
                                     alt="{{ $user->name }}" />
                            @else
                                <div class="h-full w-full flex items-center justify-center bg-white dark:bg-gray-800 {{ $roleStyles['text'] }}">
                                    <span class="text-2xl">{{ $roleStyles['role_emoji'] }}</span>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Verified Badge (if applicable) -->
                        @if($user->email_verified_at)
                            <div class="absolute -bottom-1 -right-1 h-6 w-6 bg-eco-green-500 rounded-full flex items-center justify-center ring-4 ring-white dark:ring-gray-800 shadow-lg">
                                <svg class="h-3.5 w-3.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        @endif
                    </div>
                    
                    <!-- User Details -->
                    <div class="flex-1 min-w-0">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white truncate mb-1">
                            {{ $user->name ?? 'Utilisateur' }}
                        </h3>
                        <p class="text-xs text-gray-700 dark:text-gray-300 truncate mb-2">
                            {{ $user->email ?? '' }}
                        </p>
                        
                        <!-- Role Badge -->
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold {{ $roleStyles['bg'] }} {{ $roleStyles['text'] }} shadow-sm">
                            <span>{{ $roleStyles['role_emoji'] }}</span>
                            <span>{{ $roleStyles['role_name'] }}</span>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Divider -->
            <div class="h-px bg-gradient-to-r from-transparent via-gray-200 dark:via-gray-700 to-transparent"></div>
            
            <!-- Menu Items -->
            <div class="p-2" role="none">
                <!-- Profile Link -->
                <a href="{{ route('profile.edit') }}" 
                   class="group flex items-center gap-3 px-3 py-3 rounded-xl text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-all duration-200 active:scale-[0.98]"
                   role="menuitem">
                    <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 group-hover:scale-110 group-hover:rotate-3 transition-all duration-200">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-sm mb-0.5">Mon Profil</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Gérer vos informations</div>
                    </div>
                    <svg class="h-4 w-4 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 group-hover:translate-x-1 transition-all" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
                
                <!-- Settings Link -->
                <a href="{{ route('settings.index') }}" 
                   class="group flex items-center gap-3 px-3 py-3 rounded-xl text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-all duration-200 active:scale-[0.98]"
                   role="menuitem">
                    <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 group-hover:scale-110 group-hover:rotate-3 transition-all duration-200">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-sm mb-0.5">Paramètres</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Configuration du compte</div>
                    </div>
                    <svg class="h-4 w-4 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 group-hover:translate-x-1 transition-all" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
            
            <!-- Divider -->
            <div class="h-px bg-gradient-to-r from-transparent via-gray-200 dark:via-gray-700 to-transparent"></div>
            
            <!-- Logout Section -->
            <div class="p-2" role="none">
                <form method="POST" action="{{ route('logout') }}" id="logoutForm">
                    @csrf
                    <button type="submit" 
                            class="group w-full flex items-center gap-3 px-3 py-3 rounded-xl text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all duration-200 active:scale-[0.98]"
                            role="menuitem"
                            onclick="this.disabled=true; this.classList.add('opacity-50', 'cursor-not-allowed'); this.form.submit();">
                        <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 group-hover:scale-110 group-hover:rotate-3 transition-all duration-200">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </div>
                        <div class="flex-1 text-left min-w-0">
                            <div class="font-semibold text-sm mb-0.5">Déconnexion</div>
                            <div class="text-xs text-red-500 dark:text-red-400/80">Quitter votre session</div>
                        </div>
                        <svg class="h-4 w-4 text-red-400 group-hover:text-red-600 dark:group-hover:text-red-300 group-hover:translate-x-1 transition-all" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

