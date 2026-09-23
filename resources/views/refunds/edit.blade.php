@extends('layouts.app')

@section('title', __('Modifier Remboursement'))
@section('page-title', __('Modifier Remboursement'))

@section('content')
<div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="bg-orange-100 dark:bg-orange-900/30 rounded-lg p-3 mr-4">
                    <i class="fas fa-undo text-2xl text-orange-600 dark:text-orange-400"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Modifier Remboursement #') }}{{ $id }}</h1>
            </div>
            <a href="{{ route('refunds.show', $id) }}"
               class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>{{ __('Retour') }}
            </a>
        </div>
    </div>

    <div class="max-w-lg">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-8 text-center">
            <i class="fas fa-edit text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
            <p class="text-gray-500 dark:text-gray-400">{{ __('Modification du remboursement #') }}{{ $id }}</p>
        </div>
    </div>
</div>
@endsection
