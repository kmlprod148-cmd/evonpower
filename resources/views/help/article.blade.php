@extends('layouts.app')

@section('title', $article->meta_title ?? $article->title . ' - ' . config('app.name'))

@section('meta')
    <meta name="description" content="{{ $article->meta_description ?? $article->excerpt }}">
    @if($article->tags)
    <meta name="keywords" content="{{ implode(', ', $article->tags) }}">
    @endif
@endsection

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="flex mb-8" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2 flex-wrap">
                <li>
                    <a href="{{ route('help.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        {{ __('messages.help_center') }}
                    </a>
                </li>
                <li class="text-gray-400">/</li>
                @if($article->category)
                <li>
                    <a href="{{ route('help.category', $article->category->slug) }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        {{ $article->category->name }}
                    </a>
                </li>
                <li class="text-gray-400">/</li>
                @endif
                <li>
                    <span class="text-gray-900 dark:text-white font-medium truncate">{{ Str::limit($article->title, 40) }}</span>
                </li>
            </ol>
        </nav>

        <!-- Article -->
        <article class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
            <!-- Header -->
            <div class="p-8 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-4">
                    @if($article->category)
                    <a href="{{ route('help.category', $article->category->slug) }}" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                        {{ $article->category->name }}
                    </a>
                    @endif
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $article->view_count }} {{ __('messages.views') }}
                    </span>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">{{ $article->title }}</h1>
                @if($article->excerpt)
                <p class="text-xl text-gray-600 dark:text-gray-400">{{ $article->excerpt }}</p>
                @endif
            </div>

            <!-- Content -->
            <div class="p-8">
                <div class="prose dark:prose-invert max-w-none">
                    {!! $article->content !!}
                </div>

                <!-- Tags -->
                @if($article->tags && count($article->tags) > 0)
                <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">{{ __('messages.related_tags') }}</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($article->tags as $tag)
                        <a href="{{ route('help.search', ['q' => $tag]) }}" class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full text-sm hover:bg-gray-200 dark:hover:bg-gray-600">
                            #{{ $tag }}
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </article>

        <!-- Related Articles -->
        @if($relatedArticles->count() > 0)
        <div class="mt-12">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">{{ __('messages.related_articles') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($relatedArticles as $related)
                <a href="{{ route('help.article', $related->slug) }}" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 hover:shadow-lg transition-shadow">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-2">{{ $related->title }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ Str::limit($related->excerpt, 100) }}</p>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Feedback -->
        <div class="mt-12 bg-gray-100 dark:bg-gray-800 rounded-lg p-8 text-center">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ __('messages.was_this_helpful') }}</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-4">{{ __('messages.your_feedback_matters') }}</p>
            <div class="flex justify-center space-x-4">
                <button @click="helpful = true" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    {{ __('messages.yes_helpful') }}
                </button>
                <button @click="helpful = false" class="px-6 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                    {{ __('messages.no_not_helpful') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
