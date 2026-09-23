<!-- Sidebar Header Component -->
<div class="sidebar-header">
    <!-- Logo et nom de la marque -->
    <div class="flex items-center" :class="sidebarCollapsed ? 'justify-center' : ''">
        <img src="{{ \App\Helpers\Brand::logo() }}" 
             alt="{{ \App\Helpers\Brand::name() }} Logo" 
             class="h-8 w-8 rounded-lg object-contain">
        <span class="sidebar-text ml-3 text-xl font-bold text-gray-900 dark:text-white" 
              x-show="!sidebarCollapsed">
            {{ \App\Helpers\Brand::name() }}
        </span>
    </div>
    
    <!-- Bouton de réduction/expansion -->
    <button @click="toggleSidebar()" 
            class="hidden lg:block sidebar-toggle bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 p-2 rounded-lg transition-colors duration-200">
        <svg class="h-5 w-5 text-gray-600 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
        </svg>
    </button>
    
    <!-- Informations utilisateur (optionnel) -->
    @auth
    <div class="user-info mt-4 pt-4 border-t border-gray-200 dark:border-gray-700" x-show="!sidebarCollapsed">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="h-8 w-8 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center text-white font-semibold text-sm">
                    {{ substr(Auth::user()->name, 0, 1) }}
                </div>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ Auth::user()->name }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ Auth::user()->email }}
                </p>
            </div>
        </div>
    </div>
    @endauth
</div>