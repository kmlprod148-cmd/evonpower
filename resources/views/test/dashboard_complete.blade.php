@extends("layouts.app")
@section("content")
<div class="container mx-auto p-4">
    <h1 class="text-3xl font-bold mb-6">Dashboard</h1>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Nombre total de recharge</h3>
            <p class="text-3xl font-bold mt-2 text-gray-900 dark:text-white">{{ $totalRecharges ?? 12 }}</p>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Recharge active</h3>
            <p class="text-3xl font-bold mt-2 text-gray-900 dark:text-white">{{ $rechargesActives ?? 4 }}</p>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Abonnements actifs</h3>
            <p class="text-3xl font-bold mt-2 text-gray-900 dark:text-white">{{ $abonnementsActifs ?? 3 }}</p>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Bornes actifs</h3>
            <p class="text-3xl font-bold mt-2 text-gray-900 dark:text-white">{{ $bornesActives ?? 16 }}</p>
        </div>
    </div>
</div>
@endsection