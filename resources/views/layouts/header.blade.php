<div class="h-16 border-b border-gray-100 flex items-center justify-between px-6">
    <div class="relative">
        <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-3 h-4 w-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
            type="text"
            placeholder="Search"
            class="pl-10 pr-4 py-2 rounded-md bg-gray-100 text-sm w-64"
        />
    </div>
    <div class="flex items-center space-x-4">
        <div class="w-6 h-6 flex items-center justify-center">
            <span class="text-sm">🇺🇸</span>
        </div>
        <button class="w-6 h-6 flex items-center justify-center" @click="darkMode = !darkMode">
            <span x-text="darkMode ? '☀️' : '🌙'"></span>
        </button>
        @include('partials.notification-dropdown')
        <div class="w-6 h-6 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
        </div>
        {{-- Logout Button --}}
        <form method="POST" action="{{ route('logout') }}" class="flex items-center"> {{-- Added flex items-center for alignment --}}
            @csrf
            <button type="submit" class="text-gray-500 hover:text-gray-700 text-sm">
                Déconnecter
            </button>
        </form>
    </div>
</div>