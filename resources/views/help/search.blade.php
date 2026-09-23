@extends('layouts.app')

@section('title', __('messages.search_results') . ' - ' . config('app.name'))

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Search Header -->
        <div class="mb-8">
            <a href="{{ route('help.index') }}" class="inline-flex items-center text-green-600 hover:text-green-700 mb-4">
                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                {{ __('messages.back_to_help') }}
            </a>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">{{ __('messages.search_results_for') }} "{{ $query }}"</h1>
            <p class="text-gray-600 dark:text-gray-400">{{ $count }} {{ __('messages.results_found') }}</p>
        </div>

        <!-- Search Form -->
        <form action="{{ route('help.search') }}" method="GET" class="mb-8">
            <div class="relative">
                <input 
                    type="text" 
                    name="q" 
                    value="{{ $query }}"
                    placeholder="{{ __('messages.search_help') }}..." 
                    class="w-full px-6 py-4 rounded-full text-gray-900 bg-white shadow-lg focus:outline-none focus:ring-4 focus:ring-green-300"
                >
                <button 
                    type="submit"
                    class="absolute right-2 top-2 bg-green-600 text-white px-6 py-2 rounded-full hover:bg-green-700 transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </div>
        </form>

        <!-- Results -->
        <div class="space-y-4">
            @forelse($results as $article)
            <a href="{{ route('help.article', $article->slug) }}" class="block bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-start">
                    <div class="flex-1">
                        @if($article->category)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 mb-2">
                            {{ $article->category->name }}
                        </span>
                        @endif
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ $article->title }}</h2>
                        <p class="text-gray-600 dark:text-gray-400">{{ $article->excerpt }}</p>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 ml-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </a>
            @empty
            <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-lg">
                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">{{ __('messages.no_results_found') }}</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-4">{{ __('messages.try_different_keywords') }}</p>
                <a href="{{ route('help.index') }}" class="inline-flex items-center px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    {{ __('messages.browse_help_topics') }}
                </a>
            </div>
            @endforelse
        </div>

        <!-- Contact Support -->
        @if($count === 0)
        <div class="mt-8 bg-green-50 dark:bg-gray-800 rounded-lg p-8 text-center">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">{{ __('messages.still_need_help') }}</h2>
            <p class="text-gray-600 dark:text-gray-400 mb-6">{{ __('messages.contact_us_directly') }}</p>
            <a href="{{ route('contact') }}" class="inline-flex items-center px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                {{ __('messages.contact_support') }}
            </a>
        </div>
        @endif
    </div>
</div>
@endsection
