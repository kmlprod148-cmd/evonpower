@extends('layouts.app')

@section('title', __('messages.getting_started') . ' - ' . config('app.name'))

@section('meta')
    <meta name="description" content="{{ __('messages.getting_started_meta_desc') }}">
@endsection

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <a href="{{ route('help.index') }}" class="inline-flex items-center text-green-600 hover:text-green-700 mb-4">
                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                {{ __('messages.back_to_help') }}
            </a>
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">{{ __('messages.getting_started') }}</h1>
            <p class="text-xl text-gray-600 dark:text-gray-400">{{ __('messages.getting_started_desc') }}</p>
        </div>

        <!-- Steps -->
        <div class="space-y-8">
            @forelse($articles as $index => $article)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0 w-10 h-10 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mr-4">
                        <span class="text-lg font-bold text-green-600 dark:text-green-400">{{ $index + 1 }}</span>
                    </div>
                    <div class="flex-1">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">{{ $article->title }}</h2>
                        <div class="prose dark:prose-invert max-w-none">
                            {!! $article->content !!}
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center py-12">
                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                <p class="text-gray-500 dark:text-gray-400">{{ __('messages.getting_started_coming') }}</p>
            </div>
            @endforelse
        </div>

        <!-- Next Steps -->
        <div class="mt-12 bg-green-50 dark:bg-gray-800 rounded-lg p-8">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">{{ __('messages.next_steps') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <a href="{{ route('help.faq') }}" class="flex items-center p-4 bg-white dark:bg-gray-700 rounded-lg hover:shadow-md transition-shadow">
                    <svg class="w-8 h-8 text-blue-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="font-medium text-gray-900 dark:text-white">{{ __('messages.read_faq') }}</span>
                </a>
                <a href="{{ route('help.troubleshooting') }}" class="flex items-center p-4 bg-white dark:bg-gray-700 rounded-lg hover:shadow-md transition-shadow">
                    <svg class="w-8 h-8 text-orange-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                    </svg>
                    <span class="font-medium text-gray-900 dark:text-white">{{ __('messages.troubleshooting_guide') }}</span>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
