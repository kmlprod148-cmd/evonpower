@extends('layouts.app')

@section('title', __('Centre d\'Aide - Administration'))
@section('page-title', __('Centre d\'Aide'))

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="bg-blue-100 dark:bg-blue-900/30 rounded-lg p-3 mr-4">
                    <i class="fas fa-question-circle text-2xl text-blue-600 dark:text-blue-400"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Centre d\'Aide') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Gérez les articles d\'aide et les FAQ') }}</p>
                </div>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.help.categories.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fas fa-folder mr-2"></i>{{ __('Catégories') }}
                </a>
                <a href="{{ route('admin.help.articles.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i>{{ __('Nouvel Article') }}
                </a>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        @foreach ([['label' => __('Total Articles'), 'value' => $stats['total_articles'] ?? 0, 'icon' => 'fas fa-file-alt', 'color' => 'blue'], ['label' => __('Publiés'), 'value' => $stats['published_articles'] ?? 0, 'icon' => 'fas fa-check-circle', 'color' => 'green'], ['label' => __('Catégories'), 'value' => $stats['total_categories'] ?? 0, 'icon' => 'fas fa-folder', 'color' => 'purple']] as $stat)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center">
                <div class="bg-{{ $stat['color'] }}-100 dark:bg-{{ $stat['color'] }}-900/30 rounded-lg p-3 mr-3">
                    <i class="{{ $stat['icon'] }} text-{{ $stat['color'] }}-600 dark:text-{{ $stat['color'] }}-400"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $stat['value'] }}</p>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Quick Links -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4">{{ __('Actions Rapides') }}</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <a href="{{ route('admin.help.articles.index') }}"
               class="flex items-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors">
                <i class="fas fa-file-alt text-blue-500 mr-3 text-lg"></i>
                <div>
                    <p class="text-sm font-medium text-blue-700 dark:text-blue-300">{{ __('Gérer les Articles') }}</p>
                    <p class="text-xs text-blue-500">{{ $stats['total_articles'] ?? 0 }} {{ __('article(s)') }}</p>
                </div>
            </a>
            <a href="{{ route('admin.help.categories.index') }}"
               class="flex items-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors">
                <i class="fas fa-folder text-purple-500 mr-3 text-lg"></i>
                <div>
                    <p class="text-sm font-medium text-purple-700 dark:text-purple-300">{{ __('Gérer les Catégories') }}</p>
                    <p class="text-xs text-purple-500">{{ $stats['total_categories'] ?? 0 }} {{ __('catégorie(s)') }}</p>
                </div>
            </a>
        </div>
    </div>
</div>
@endsection
