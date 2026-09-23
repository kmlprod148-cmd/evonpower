@props([
    'compact' => false
])

<div x-data="window.themeSwitcher ? window.themeSwitcher() : {}" 
     class="relative"
     @theme-changed.window="updateTheme && updateTheme($event.detail)">
    
    @if($compact)
        {{-- Compact Button Version --}}
        <button type="button"
                @click.stop="cycleTheme()" 
                class="evon-header-button group"
                :title="themeTitle()"
                :aria-label="themeTitle()"
                :aria-pressed="(theme === 'dark') ? 'true' : 'false'">
            <div class="relative flex items-center justify-center">
                <!-- Default Icon -->
                <svg x-show="!theme"
                     class="absolute text-gray-500 group-hover:text-gray-600 transition-colors" 
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                </svg>
                
                <!-- Light Mode Icon -->
                <svg x-show="theme === 'light'" 
                     x-cloak
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-0 rotate-90"
                     x-transition:enter-end="opacity-100 scale-100 rotate-0"
                     class="absolute text-yellow-500 group-hover:text-yellow-600 transition-colors" 
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                
                <!-- Dark Mode Icon -->
                <svg x-show="theme === 'dark'" 
                     x-cloak
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-0 -rotate-90"
                     x-transition:enter-end="opacity-100 scale-100 rotate-0"
                     class="absolute text-indigo-500 group-hover:text-indigo-600 transition-colors" 
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
                
                <!-- Auto Mode Icon -->
                <svg x-show="theme === 'auto'" 
                     x-cloak
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-0"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="absolute text-eco-green-600 group-hover:text-eco-green-700 dark:text-eco-green-400 dark:group-hover:text-eco-green-300 transition-colors" 
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                </svg>
            </div>
        </button>
    @else
        {{-- Full Dropdown Version --}}
        <div class="relative">
            <button type="button"
                    @click.stop="isOpen = !isOpen" 
                    class="evon-header-button group"
                    :class="{ 'ring-2 ring-eco-green-500': isOpen }"
                    :aria-expanded="isOpen ? 'true' : 'false'"
                    :aria-pressed="(theme === 'dark') ? 'true' : 'false'">
                <div class="relative flex items-center justify-center w-5 h-5">
                    <svg x-show="theme === 'light'" 
                         x-cloak
                         class="absolute text-yellow-500" 
                         width="20" height="20"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <svg x-show="theme === 'dark'" 
                         x-cloak
                         class="absolute text-indigo-500" 
                         width="20" height="20"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                    <svg x-show="theme === 'auto'" 
                         x-cloak
                         class="absolute text-eco-green-600 dark:text-eco-green-400" 
                         width="20" height="20"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                </div>
            </button>

            <!-- Dropdown Panel -->
            <div x-show="isOpen"
                 x-cloak
                 @click.away="isOpen = false"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="absolute right-0 mt-3 w-64 origin-top-right z-50">
                
                <div class="rounded-xl bg-white dark:bg-gray-800 shadow-2xl ring-1 ring-black ring-opacity-5 overflow-hidden">
                    <!-- Header -->
                    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Apparence</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Personnalisez le thème de l'interface</p>
                    </div>

                    <!-- Theme Options -->
                    <div class="p-2">
                        <button @click="setTheme('light')" 
                                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                                :class="{ 'bg-eco-green-50 dark:bg-eco-green-900/20 ring-1 ring-eco-green-500': theme === 'light' }">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center">
                                <svg class="w-5 h-5 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <div class="flex-1 text-left">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">Mode Clair</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Interface lumineuse</div>
                            </div>
                            <div x-show="theme === 'light'" x-cloak class="flex-shrink-0">
                                <svg class="w-5 h-5 text-eco-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </button>

                        <button @click="setTheme('dark')" 
                                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors mt-1"
                                :class="{ 'bg-eco-green-50 dark:bg-eco-green-900/20 ring-1 ring-eco-green-500': theme === 'dark' }">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                                </svg>
                            </div>
                            <div class="flex-1 text-left">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">Mode Sombre</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Interface foncée</div>
                            </div>
                            <div x-show="theme === 'dark'" x-cloak class="flex-shrink-0">
                                <svg class="w-5 h-5 text-eco-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </button>

                        <button @click="setTheme('auto')" 
                                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors mt-1"
                                :class="{ 'bg-eco-green-50 dark:bg-eco-green-900/20 ring-1 ring-eco-green-500': theme === 'auto' }">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-eco-green-100 dark:bg-eco-green-900/30 flex items-center justify-center">
                                <svg class="w-5 h-5 text-eco-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                </svg>
                            </div>
                            <div class="flex-1 text-left">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">Automatique</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Selon le système</div>
                            </div>
                            <div x-show="theme === 'auto'" x-cloak class="flex-shrink-0">
                                <svg class="w-5 h-5 text-eco-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </button>
                    </div>

                    <!-- Preview -->
                    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700">
                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-2">Aperçu</div>
                        <div class="flex gap-2">
                            <div class="flex-1 h-12 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700"></div>
                            <div class="flex-1 h-12 rounded-lg bg-gray-100 dark:bg-gray-700"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
