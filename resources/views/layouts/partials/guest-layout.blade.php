<!-- Layout pour visiteurs non connectés -->
<div class="min-h-screen flex flex-col bg-gray-50 dark:bg-gray-900">
    <!-- Simple Header for Guests -->
    <nav class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 sticky top-0 z-30" role="banner">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="{{ url('/') }}" class="flex items-center gap-2 focus:outline-none focus:ring-2 focus:ring-eco-green-500 focus:ring-offset-2 rounded-lg">
                        @if(file_exists(public_path('images/evon-logo.png')))
                            <img src="{{ asset('images/evon-logo.png') }}" alt="EVON Logo" class="h-8 w-auto">
                        @else
                            <div class="h-8 w-8 bg-gradient-to-br from-eco-green-500 to-eco-green-600 rounded-lg flex items-center justify-center shadow-md">
                                <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                        @endif
                        <span class="text-xl font-bold text-gray-900 dark:text-white">EVON</span>
                    </a>
                </div>
                
                <!-- Auth Links -->
                <div class="flex items-center gap-3">
                    @if(View::exists('components.direct-language-switcher'))
                        @include('components.direct-language-switcher')
                    @endif
                    
                    <x-evon.button variant="ghost" href="{{ route('login') }}" size="sm">
                        {{ __('messages.login') }}
                    </x-evon.button>
                    
                    @if(Route::has('register'))
                        <x-evon.button variant="primary" href="{{ route('register') }}" size="sm">
                            {{ __('messages.register') }}
                        </x-evon.button>
                    @endif
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main id="main-content" class="flex-1 w-full" role="main" tabindex="-1">
        <div class="py-6">
            <div class="w-full px-4 sm:px-6 lg:px-8">
                <!-- Flash Messages -->
                @include('layouts.partials.flash-messages')
                
                <!-- Page Content -->
                @if(View::hasSection('content'))
                    @yield('content')
                @else
                    <div class="max-w-4xl mx-auto">
                        <div class="card text-center py-12">
                            <h1 class="heading-1 mb-4">{{ __('messages.welcome_to_evon') }}</h1>
                            <p class="body-large text-gray-600 dark:text-gray-300 mb-8">
                                {{ __('messages.main_content_description') }}
                            </p>
                            <div class="flex gap-4 justify-center">
                                <x-evon.button variant="primary" href="{{ route('login') }}">
                                    {{ __('messages.login') }}
                                </x-evon.button>
                                @if(Route::has('register'))
                                    <x-evon.button variant="outline" href="{{ route('register') }}">
                                        {{ __('messages.register') }}
                                    </x-evon.button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 mt-auto" role="contentinfo">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <p class="text-center text-sm text-gray-600 dark:text-gray-400">
                © {{ date('Y') }} EVON. {{ __('messages.all_rights_reserved') }}.
            </p>
        </div>
    </footer>
</div>

