{{-- Language Test Page --}}
@extends('layouts.app')

@section('title', __('messages.language') . ' - ' . __('messages.test'))

@section('content')
<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white">
                <h1 class="text-2xl font-bold text-center">
                    {{ __('messages.language') }} - {{ __('messages.test') }}
                </h1>
            </div>

            <div class="p-6">
                {{-- Language Switcher --}}
                <div class="mb-8 flex justify-center">
                    <x-language-switcher style="buttons" />
                </div>

                {{-- Current Language Info --}}
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-2">{{ __('messages.current_language') }}</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-white p-3 rounded border">
                            <span class="font-medium text-gray-700">{{ __('messages.locale_name_' . app()->getLocale()) }}</span>
                            <span class="text-sm text-gray-500 ml-2">({{ app()->getLocale() }})</span>
                        </div>
                        <div class="bg-white p-3 rounded border">
                            <span class="font-medium text-gray-700">
                                {{ app()->getLocale() === 'ar' ? 'RTL' : 'LTR' }}
                            </span>
                            <span class="text-sm text-gray-500 ml-2">{{ __('messages.direction') ?? 'Direction' }}</span>
                        </div>
                        <div class="bg-white p-3 rounded border">
                            <span class="font-medium text-gray-700">{{ now()->format('l, F j, Y') }}</span>
                            <span class="text-sm text-gray-500 ml-2">{{ __('messages.date') ?? 'Date' }}</span>
                        </div>
                    </div>
                </div>

                {{-- Test Translations --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Common Messages --}}
                    <div class="bg-white border rounded-lg p-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">{{ __('messages.general_messages') ?? 'General Messages' }}</h3>
                        <ul class="space-y-2">
                            <li><strong>{{ __('messages.welcome') }}:</strong> {{ __('messages.welcome') }}</li>
                            <li><strong>{{ __('messages.home') }}:</strong> {{ __('messages.home') }}</li>
                            <li><strong>{{ __('messages.dashboard') }}:</strong> {{ __('messages.dashboard') }}</li>
                            <li><strong>{{ __('messages.settings') }}:</strong> {{ __('messages.settings') }}</li>
                            <li><strong>{{ __('messages.save') }}:</strong> {{ __('messages.save') }}</li>
                            <li><strong>{{ __('messages.cancel') }}:</strong> {{ __('messages.cancel') }}</li>
                        </ul>
                    </div>

                    {{-- Actions --}}
                    <div class="bg-white border rounded-lg p-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">{{ __('messages.actions') ?? 'Actions' }}</h3>
                        <ul class="space-y-2">
                            <li><strong>{{ __('messages.create') }}:</strong> {{ __('messages.create') }}</li>
                            <li><strong>{{ __('messages.edit') }}:</strong> {{ __('messages.edit') }}</li>
                            <li><strong>{{ __('messages.delete') }}:</strong> {{ __('messages.delete') }}</li>
                            <li><strong>{{ __('messages.view') }}:</strong> {{ __('messages.view') }}</li>
                            <li><strong>{{ __('messages.update') }}:</strong> {{ __('messages.update') }}</li>
                            <li><strong>{{ __('messages.submit') }}:</strong> {{ __('messages.submit') }}</li>
                        </ul>
                    </div>

                    {{-- Status Messages --}}
                    <div class="bg-white border rounded-lg p-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">{{ __('messages.status') ?? 'Status' }}</h3>
                        <ul class="space-y-2">
                            <li><strong>{{ __('messages.success') }}:</strong> {{ __('messages.success') }}</li>
                            <li><strong>{{ __('messages.error') }}:</strong> {{ __('messages.error') }}</li>
                            <li><strong>{{ __('messages.active') }}:</strong> {{ __('messages.active') }}</li>
                            <li><strong>{{ __('messages.inactive') }}:</strong> {{ __('messages.inactive') }}</li>
                            <li><strong>{{ __('messages.pending') }}:</strong> {{ __('messages.pending') }}</li>
                            <li><strong>{{ __('messages.completed') }}:</strong> {{ __('messages.completed') }}</li>
                        </ul>
                    </div>

                    {{-- Charging Points --}}
                    <div class="bg-white border rounded-lg p-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">{{ __('messages.charging_points') ?? 'Charging Points' }}</h3>
                        <ul class="space-y-2">
                            <li><strong>{{ __('messages.charging_point') }}:</strong> {{ __('messages.charging_point') }}</li>
                            <li><strong>{{ __('messages.no_charging_points_found') }}:</strong> {{ __('messages.no_charging_points_found') }}</li>
                            <li><strong>{{ __('messages.charging_point_created') }}:</strong> {{ __('messages.charging_point_created') }}</li>
                            <li><strong>{{ __('messages.charging_point_updated') }}:</strong> {{ __('messages.charging_point_updated') }}</li>
                            <li><strong>{{ __('messages.charging_point_deleted') }}:</strong> {{ __('messages.charging_point_deleted') }}</li>
                        </ul>
                    </div>
                </div>

                {{-- Test Buttons --}}
                <div class="mt-8 text-center">
                    <a href="{{ route('language.switch', 'en') }}"
                       class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150 mr-2">
                        🇺🇸 English
                    </a>
                    <a href="{{ route('language.switch', 'fr') }}"
                       class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150 mr-2">
                        🇫🇷 Français
                    </a>
                    <a href="{{ route('language.switch', 'ar') }}"
                       class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 focus:bg-yellow-700 active:bg-yellow-900 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        🇸🇦 العربية
                    </a>
                </div>

                {{-- API Test --}}
                <div class="mt-8 bg-gray-50 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">{{ __('messages.api_test') ?? 'API Test' }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <button onclick="testCurrentLocale()"
                                    class="w-full bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                                {{ __('messages.get_current_locale') ?? 'Get Current Locale' }}
                            </button>
                            <div id="current-locale-result" class="mt-2 text-sm text-gray-600"></div>
                        </div>
                        <div>
                            <button onclick="testAvailableLocales()"
                                    class="w-full bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">
                                {{ __('messages.get_available_locales') ?? 'Get Available Locales' }}
                            </button>
                            <div id="available-locales-result" class="mt-2 text-sm text-gray-600"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function testCurrentLocale() {
    fetch('{{ route("language.current") }}')
        .then(response => response.json())
        .then(data => {
            document.getElementById('current-locale-result').innerHTML =
                '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
        })
        .catch(error => {
            document.getElementById('current-locale-result').innerHTML =
                '<span class="text-red-600">Error: ' + error.message + '</span>';
        });
}

function testAvailableLocales() {
    fetch('{{ route("language.available") }}')
        .then(response => response.json())
        .then(data => {
            document.getElementById('available-locales-result').innerHTML =
                '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
        })
        .catch(error => {
            document.getElementById('available-locales-result').innerHTML =
                '<span class="text-red-600">Error: ' + error.message + '</span>';
        });
}
</script>
@endsection
