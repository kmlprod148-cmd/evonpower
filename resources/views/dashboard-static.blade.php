@extends('layouts.app')

@section('title', 'Tableau de bord')
@section('page-title', 'Tableau de bord')

@section('content')
<div class="mb-4">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Dashboard</h2>
    </div>
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Tableau de bord</h1>
</div>

<!-- Cartes statistiques -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Nombre total de recharge -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5 relative">
        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Nombre total de recharge</h3>
        <p class="text-3xl font-bold mt-2 text-gray-900 dark:text-white">{{ $stats['totalRecharges'] ?? 1250 }}</p>
        <div class="absolute top-5 right-5 h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
            <div class="h-6 w-6 rounded-full bg-green-300"></div>
        </div>
    </div>

    <!-- Recharge active -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5 relative">
        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Recharge active</h3>
        <p class="text-3xl font-bold mt-2 text-gray-900 dark:text-white">{{ $stats['rechargesActives'] ?? 8 }}</p>
        <div class="absolute top-5 right-5 h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
            <div class="h-6 w-6 rounded-full bg-green-300"></div>
        </div>
    </div>

    <!-- Abonnements actifs -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5 relative">
        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Abonnements actifs</h3>
        <p class="text-3xl font-bold mt-2 text-gray-900 dark:text-white">{{ $stats['abonnementsActifs'] ?? 45 }}</p>
        <div class="absolute top-5 right-5 h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
            <div class="h-6 w-6 rounded-full bg-green-300"></div>
        </div>
    </div>

    <!-- Bornes actifs -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5 relative">
        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Bornes actives</h3>
        <p class="text-3xl font-bold mt-2 text-gray-900 dark:text-white">{{ $stats['bornesActives'] ?? 23 }}</p>
        <div class="absolute top-5 right-5 h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
            <div class="h-6 w-6 rounded-full bg-green-300"></div>
        </div>
    </div>
</div>

<!-- Graphique et tableau -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Graphique des recharges -->
    <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-lg shadow p-5">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-base font-medium text-gray-700 dark:text-gray-300">Nombre des recharges</h3>
            <div class="relative">
                <button class="flex items-center text-sm text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded px-3 py-1">
                    Aujourd'hui
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            </div>
        </div>
        <div class="h-72 bg-gray-50 dark:bg-gray-700 rounded flex items-center justify-center">
            <div class="text-center">
                <div class="text-2xl font-bold text-gray-400 mb-2">Graphique</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Données: {{ json_encode($hourlyData['data'] ?? []) }}
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau des recharges actives -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-base font-medium text-gray-700 dark:text-gray-300">Recharge active ({{ $stats['rechargesActives'] ?? 3 }})</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nom</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">kWh</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @if(isset($rechargesActivesList) && count($rechargesActivesList) > 0)
                        @foreach($rechargesActivesList as $recharge)
                            <tr>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-800 dark:text-gray-200">
                                    {{ $recharge['name'] ?? 'N/A' }}
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-800 dark:text-gray-200 text-right">
                                    {{ $recharge['kwh'] ?? '0' }}
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-800 dark:text-gray-200">Morocco Mall 01</td>
                            <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-800 dark:text-gray-200 text-right">423</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-800 dark:text-gray-200">Mall 02</td>
                            <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-800 dark:text-gray-200 text-right">387</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-800 dark:text-gray-200">Centre Ville</td>
                            <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-800 dark:text-gray-200 text-right">298</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-800 dark:text-gray-200">Aéroport</td>
                            <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-800 dark:text-gray-200 text-right">156</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Informations de debug -->
<div class="mt-8 bg-yellow-50 dark:bg-yellow-900 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4">
    <h4 class="font-medium text-yellow-800 dark:text-yellow-200 mb-2">Informations de debug:</h4>
    <div class="text-sm text-yellow-700 dark:text-yellow-300">
        <p>Stats: {{ json_encode($stats ?? []) }}</p>
        <p>Données horaires: {{ json_encode($hourlyData ?? []) }}</p>
        <p>Recharges actives: {{ json_encode($rechargesActivesList ?? []) }}</p>
    </div>
</div>
@endsection
