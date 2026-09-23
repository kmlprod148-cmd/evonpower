@extends('layouts.app')

@section('page-title', 'Synchronisation Hiérarchique des Balances')

@section('content')
<div class="bg-gradient-to-b from-gray-50 to-white dark:from-gray-900 dark:to-gray-950 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Synchronisation Hiérarchique des Balances</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Synchronise les balances de manière hiérarchique : Admin → Intégrateur → Opérateur/Partenaire
            </p>
        </div>

        <!-- Messages -->
        @if(session('success'))
            <div class="mb-6 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 text-green-700 dark:text-green-300 p-4 rounded-lg shadow-sm" role="alert">
                <p>{{ session('success') }}</p>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 text-red-700 dark:text-red-300 p-4 rounded-lg shadow-sm" role="alert">
                <p>{{ session('error') }}</p>
            </div>
        @endif

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 p-4">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Admins</div>
                <div class="mt-2 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['admins'] ?? 0 }}</div>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 p-4">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Intégrateurs</div>
                <div class="mt-2 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['integrators'] ?? 0 }}</div>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 p-4">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Opérateurs</div>
                <div class="mt-2 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['operators'] ?? 0 }}</div>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 p-4">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Partenaires</div>
                <div class="mt-2 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['partners'] ?? 0 }}</div>
            </div>
        </div>

        <!-- Actions -->
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Actions de Synchronisation</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Synchroniser toutes les balances -->
                <form action="{{ route('admin.balances.sync-all') }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir synchroniser toutes les balances ?');">
                    @csrf
                    <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-3 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Synchroniser Toutes les Balances
                    </button>
                </form>

                <!-- Synchroniser par rôle -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Synchroniser par rôle :</label>
                    <div class="flex flex-wrap gap-2">
                        <form action="{{ route('admin.balances.sync-role', 'admin') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-3 py-2 text-xs font-medium text-white bg-purple-600 hover:bg-purple-700 rounded-lg transition-colors">
                                Admins
                            </button>
                        </form>
                        <form action="{{ route('admin.balances.sync-role', 'integrator') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-3 py-2 text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors">
                                Intégrateurs
                            </button>
                        </form>
                        <form action="{{ route('admin.balances.sync-role', 'operator') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-3 py-2 text-xs font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors">
                                Opérateurs
                            </button>
                        </form>
                        <form action="{{ route('admin.balances.sync-role', 'partner') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-3 py-2 text-xs font-medium text-white bg-teal-600 hover:bg-teal-700 rounded-lg transition-colors">
                                Partenaires
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Information -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 p-4 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">Comment ça fonctionne ?</h3>
                    <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                        <ul class="list-disc list-inside space-y-1">
                            <li><strong>Admin :</strong> Balance inclut les money in/out de tous les intégrateurs (parts admin déduites)</li>
                            <li><strong>Intégrateur :</strong> Balance reflète les parts des opérateurs/partenaires (money in brut - money out admin)</li>
                            <li><strong>Opérateur/Partenaire :</strong> Balance = parts opérateur depuis TransactionDetails</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

