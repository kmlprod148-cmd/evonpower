@extends('layouts.app')

@section('title', __('messages.faq') . ' - ' . config('app.name'))

@section('meta')
    <meta name="description" content="{{ __('messages.faq_description') }}">
@endsection

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">{{ __('messages.frequently_asked_questions') }}</h1>
            <p class="text-xl text-gray-600 dark:text-gray-400">{{ __('messages.faq_subtitle') }}</p>
        </div>

        <!-- FAQ Accordion -->
        <div class="space-y-4" x-data="{ selected: null }">
            @forelse($categories as $category)
                @foreach($category->articles as $article)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                    <button 
                        @click="selected !== {{ $article->id }} ? selected = {{ $article->id }} : selected = null"
                        class="w-full px-6 py-4 text-left flex items-center justify-between focus:outline-none"
                    >
                        <span class="font-medium text-gray-900 dark:text-white">{{ $article->title }}</span>
                        <svg 
                            class="w-5 h-5 text-gray-500 transform transition-transform duration-200"
                            :class="selected === {{ $article->id }} ? 'rotate-180' : ''"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div 
                        x-show="selected === {{ $article->id }}"
                        x-collapse
                        class="px-6 pb-4"
                    >
                        <div class="prose dark:prose-invert max-w-none">
                            {!! $article->content !!}
                        </div>
                    </div>
                </div>
                @endforeach
            @empty
            <div class="text-center py-12">
                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-gray-500 dark:text-gray-400">{{ __('messages.no_faq_available') }}</p>
            </div>
            @endforelse
        </div>

        <!-- Contact CTA -->
        <div class="mt-12 bg-green-50 dark:bg-gray-800 rounded-lg p-8 text-center">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ __('messages.cant_find_answer') }}</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-4">{{ __('messages.contact_us_for_help') }}</p>
            <a href="{{ route('contact') }}" class="inline-flex items-center px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                {{ __('messages.contact_support') }}
            </a>
        </div>
    </div>
</div>
@endsection
