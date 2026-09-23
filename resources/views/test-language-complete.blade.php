<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ config('app.direction', 'ltr') }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Test Changement de Langue - EVON</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50">
    <div class="max-w-4xl mx-auto p-8">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h1 class="text-2xl font-bold mb-6">🌍 Test Changement de Langue</h1>

            <!-- Informations actuelles -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <h2 class="font-semibold mb-3">📊 Informations actuelles :</h2>
                <ul class="space-y-2 text-sm">
                    <li><strong>App Locale:</strong> {{ app()->getLocale() }}</li>
                    <li><strong>Session Locale:</strong> {{ session('locale', 'not set') }}</li>
                    <li><strong>Cookie Locale:</strong> {{ request()->cookie('locale', 'not set') }}</li>
                    <li><strong>User Locale:</strong> {{ auth()->check() ? (auth()->user()->locale ?? 'not set') : 'not authenticated' }}</li>
                    <li><strong>Config Locale:</strong> {{ config('app.locale') }}</li>
                    <li><strong>Direction:</strong> {{ config('app.direction', 'ltr') }}</li>
                    <li><strong>Test traduction:</strong> {{ __('messages.welcome') }}</li>
                </ul>
            </div>

            <!-- Sélecteur de langue -->
            <div class="mb-6">
                <h2 class="font-semibold mb-3">🔄 Changer de langue :</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @foreach(config('app.available_locales', ['fr', 'en', 'ar', 'es']) as $locale)
                        @php
                            $flags = ['fr' => '🇫🇷', 'en' => '🇬🇧', 'ar' => '🇲🇦', 'es' => '🇪🇸'];
                            $names = ['fr' => 'Français', 'en' => 'English', 'ar' => 'العربية', 'es' => 'Español'];
                        @endphp
                        <a href="{{ route('language.switch', $locale) }}" 
                           class="flex items-center justify-center gap-2 px-4 py-3 border-2 rounded-lg transition-all
                                  {{ app()->getLocale() === $locale 
                                      ? 'border-green-500 bg-green-50 text-green-700 font-bold' 
                                      : 'border-gray-300 hover:border-green-500 hover:bg-green-50' }}">
                            <span class="text-2xl">{{ $flags[$locale] ?? '🌐' }}</span>
                            <span>{{ $names[$locale] ?? $locale }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <!-- Test AJAX -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <h2 class="font-semibold mb-3">🔧 Test AJAX :</h2>
                <div x-data="languageTest()" class="space-y-3">
                    <div class="flex gap-3">
                        <button @click="testAjax('en')" 
                                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            Test EN (AJAX)
                        </button>
                        <button @click="testAjax('fr')" 
                                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            Test FR (AJAX)
                        </button>
                        <button @click="testAjax('ar')" 
                                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            Test AR (AJAX)
                        </button>
                        <button @click="testAjax('es')" 
                                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            Test ES (AJAX)
                        </button>
                    </div>
                    
                    <div x-show="loading" class="text-blue-600">
                        ⏳ Changement en cours...
                    </div>
                    
                    <div x-show="response" x-html="response" 
                         class="p-3 bg-gray-100 rounded text-xs overflow-x-auto">
                    </div>
                </div>
            </div>

            <!-- Traductions de test -->
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <h2 class="font-semibold mb-3">📝 Traductions de test :</h2>
                <ul class="space-y-1 text-sm">
                    <li>• <strong>welcome:</strong> {{ __('messages.welcome') }}</li>
                    <li>• <strong>dashboard:</strong> {{ __('dashboard.title') }}</li>
                    <li>• <strong>logout:</strong> {{ __('messages.logout') }}</li>
                    <li>• <strong>login:</strong> {{ __('auth.login') }}</li>
                </ul>
            </div>

            <!-- Actions -->
            <div class="mt-6 flex gap-3">
                <button onclick="window.location.reload()" 
                        class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                    🔄 Recharger la page
                </button>
                <a href="{{ route('dashboard') }}" 
                   class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                    🏠 Retour au tableau de bord
                </a>
            </div>
        </div>
    </div>

    <script>
    function languageTest() {
        return {
            loading: false,
            response: '',

            async testAjax(locale) {
                this.loading = true;
                this.response = '';

                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
                    
                    console.log('Testing AJAX for locale:', locale);
                    
                    const res = await fetch('/language/set', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ locale: locale })
                    });

                    const data = await res.json();
                    console.log('Response:', data);

                    this.response = `
                        <div class="space-y-2">
                            <div class="${res.ok ? 'text-green-600' : 'text-red-600'} font-bold">
                                ${res.ok ? '✅ Succès' : '❌ Erreur'} (${res.status})
                            </div>
                            <pre class="text-xs">${JSON.stringify(data, null, 2)}</pre>
                        </div>
                    `;

                    if (res.ok && data.status === 'success') {
                        setTimeout(() => {
                            console.log('Reloading page...');
                            window.location.reload();
                        }, 2000);
                    }

                } catch (error) {
                    console.error('AJAX Error:', error);
                    this.response = `<div class="text-red-600">❌ Erreur: ${error.message}</div>`;
                } finally {
                    this.loading = false;
                }
            }
        }
    }
    </script>
</body>
</html>

