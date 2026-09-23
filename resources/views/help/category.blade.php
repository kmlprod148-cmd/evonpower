@extends('layouts.app')

@section('title', $category->name . ' - ' . config('app.name'))

@section('meta')
    <meta name="description" content="{{ $category->description ?? $category->name }}">
@endsection

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="flex mb-8" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2">
                <li>
                    <a href="{{ route('help.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                    </a>
                </li>
                <li class="text-gray-400">/</li>
                <li>
                    <span class="text-gray-900 dark:text-white font-medium">{{ $category->name }}</span>
                </li>
            </ol>
        </nav>

        <!-- Category Header -->
        <div class="mb-8">
            @if($category->icon)
            <div class="w-16 h-16 bg-green-100 dark:bg-green-900 rounded-xl flex items-center justify-center mb-4">
                <span class="text-3xl">{{ $category->icon }}</span>
            </div>
            @endif
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">{{ $category->name }}</h1>
            @if($category->description)
            <p class="text-xl text-gray-600 dark:text-gray-400">{{ $category->description }}</p>
            @endif
        </div>

        <!-- Articles List -->
        <div class="space-y-4">
            @forelse($category->articles as $article)
            <a href="{{ route('help.article', $article->slug) }}" class="block bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ $article->title }}</h2>
                        @if($article->excerpt)
                        <p class="text-gray-600 dark:text-gray-400">{{ $article->excerpt }}</p>
                        @endif
                    </div>
                    <svg class="w-5 h-5 text-gray-400 ml-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </a>
            @empty
            <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-lg">
                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-gray-500 dark:text-gray-400">{{ __('messages.no_articles_in_category') }}</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
