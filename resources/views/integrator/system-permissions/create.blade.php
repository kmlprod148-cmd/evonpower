@extends('layouts.app')

@section('title', __('Nouvelle Permission'))
@section('page-title', __('Nouvelle Permission'))

@section('content')
<div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center">
            <div class="bg-indigo-100 dark:bg-indigo-900/30 rounded-lg p-3 mr-4">
                <i class="fas fa-lock text-2xl text-indigo-600 dark:text-indigo-400"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Nouvelle Permission') }}</h1>
        </div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-8 text-center">
        <i class="fas fa-lock text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
        <p class="text-gray-500 dark:text-gray-400">{{ __('Nouvelle Permission') }}</p>
    </div>
</div>
@endsection
