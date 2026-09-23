@extends('layouts.app')

@section('title', __('Catégories d\'Aide'))
@section('page-title', __('Catégories d\'Aide'))

@section('content')
<div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="bg-purple-100 dark:bg-purple-900/30 rounded-lg p-3 mr-4">
                    <i class="fas fa-folder text-2xl text-purple-600 dark:text-purple-400"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Catégories d\'Aide') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $categories->count() }} {{ __('catégorie(s)') }}</p>
                </div>
            </div>
            <a href="{{ route('admin.help.index') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>{{ __('Retour') }}
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-400 p-4 rounded-lg">
            <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
        </div>
    @endif

    <!-- Create Category Form -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4">{{ __('Nouvelle Catégorie') }}</h2>
        <form method="POST" action="{{ route('admin.help.categories.store') }}" class="flex gap-3">
            @csrf
            <input type="text" name="name" placeholder="{{ __('Nom de la catégorie') }}" required
                   class="flex-1 text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500">
            <input type="text" name="slug" placeholder="{{ __('Slug (optionnel)') }}"
                   class="w-48 text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500">
            <button type="submit"
                    class="inline-flex items-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">
                <i class="fas fa-plus mr-2"></i>{{ __('Ajouter') }}
            </button>
        </form>
    </div>

    <!-- Categories List -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        @if ($categories->count() > 0)
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Nom') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Slug') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Articles') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($categories as $category)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ $category->name }}</td>
                            <td class="px-4 py-4 text-sm font-mono text-gray-500 dark:text-gray-400">{{ $category->slug }}</td>
                            <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-400">{{ $category->articles_count ?? 0 }}</td>
                            <td class="px-4 py-4 text-right">
                                <form method="POST" action="{{ route('admin.help.categories.destroy', $category) }}" class="inline"
                                      onsubmit="return confirm('{{ __('Supprimer cette catégorie ?') }}')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="inline-flex items-center px-3 py-1.5 bg-red-600 text-white text-xs rounded-lg hover:bg-red-700 transition-colors">
                                        <i class="fas fa-trash mr-1"></i>{{ __('Supprimer') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="p-12 text-center">
                <i class="fas fa-folder-open text-3xl text-gray-300 dark:text-gray-600 mb-3"></i>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Aucune catégorie créée') }}</p>
            </div>
        @endif
    </div>
</div>
@endsection
