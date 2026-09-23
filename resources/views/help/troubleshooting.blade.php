@extends('layouts.app')

@section('title', __('messages.troubleshooting') . ' - ' . config('app.name'))

@section('meta')
    <meta name="description" content="{{ __('messages.troubleshooting_meta_desc') }}">
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
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">{{ __('messages.troubleshooting') }}</h1>
            <p class="text-xl text-gray-600 dark:text-gray-400">{{ __('messages.troubleshooting_desc') }}</p>
        </div>

        <!-- Common Issues -->
        <div class="space-y-6">
            @forelse($articles as $article)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0 w-10 h-10 bg-orange-100 dark:bg-orange-900 rounded-full flex items-center justify-center mr-4">
                        <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                </svg>
                <p class="text-gray-500 dark:text-gray-400">{{ __('messages.troubleshooting_coming') }}</p>
            </div>
            @endforelse
        </div>

        <!-- Contact Support -->
        <div class="mt-12 bg-red-50 dark:bg-gray-800 rounded-lg p-8 text-center">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">{{ __('messages.still_having_issues') }}</h2>
            <p class="text-gray-600 dark:text-gray-400 mb-6">{{ __('messages.contact_support_for_issues') }}</p>
            <a href="{{ route('contact') }}" class="inline-flex items-center px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                {{ __('messages.contact_support') }}
            </a>
        </div>
    </div>
</div>
@endsection
