@extends('layouts.app')

@section('title', __('Nouveau Cycle'))
@section('page-title', __('Nouveau Cycle'))

@section('content')
<div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center">
            <div class="bg-blue-100 dark:bg-blue-900/30 rounded-lg p-3 mr-4">
                <i class="fas fa-sync text-2xl text-blue-600 dark:text-blue-400"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Nouveau Cycle') }}</h1>
        </div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-8 text-center">
        <i class="fas fa-sync text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
        <p class="text-gray-500 dark:text-gray-400">{{ __('Nouveau Cycle') }}</p>
    </div>
</div>
@endsection
