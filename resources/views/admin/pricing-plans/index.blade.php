@extends('layouts.app')

@section('title', 'Plans Tarifaires')
@section('page-title', 'Plans Tarifaires')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="bg-gradient-to-r from-eco-green-600 to-teal-400 rounded-xl p-6 text-white shadow-lg">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold flex items-center gap-2 text-green-900">
                    <i class="fas fa-tags"></i> Plans Tarifaires
                </h1>
                <p class="text-green-900 text-sm mt-1">Configurez les grilles tarifaires appliquées aux bornes et groupes</p>
            </div>
            <a href="{{ route('admin.pricing-plans.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-white text-eco-green-700 font-semibold rounded-lg hover:bg-green-50 transition-colors shadow-sm">
                <i class="fas fa-plus"></i> Ajouter un plan
            </a>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-400 p-4 rounded-lg flex items-center gap-2">
            <i class="fas fa-check-circle text-green-600"></i>
            <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-400 p-4 rounded-lg flex items-center gap-2">
            <i class="fas fa-exclamation-circle text-red-600"></i>
            <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
        </div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 flex items-center gap-3">
            <div class="w-11 h-11 bg-orange-100 dark:bg-orange-900/40 rounded-xl flex items-center justify-center">
                <i class="fas fa-tags text-orange-600 dark:text-orange-400"></i>
            </div>
            <div><p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Total plans</p></div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 flex items-center gap-3">
            <div class="w-11 h-11 bg-green-100 dark:bg-green-900/40 rounded-xl flex items-center justify-center">
                <i class="fas fa-check-circle text-green-600 dark:text-green-400"></i>
            </div>
            <div><p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['active'] }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Actifs</p></div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 flex items-center gap-3">
            <div class="w-11 h-11 bg-blue-100 dark:bg-blue-900/40 rounded-xl flex items-center justify-center">
                <i class="fas fa-clock text-blue-600 dark:text-blue-400"></i>
            </div>
            <div><p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['by_minute'] }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">À la minute</p></div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 flex items-center gap-3">
            <div class="w-11 h-11 bg-purple-100 dark:bg-purple-900/40 rounded-xl flex items-center justify-center">
                <i class="fas fa-bolt text-purple-600 dark:text-purple-400"></i>
            </div>
            <div><p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['by_kwh'] }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Par kWh</p></div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-48">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Recherche</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nom du plan..."
                       class="w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Type</label>
                <select name="rate_type" class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500">
                    <option value="">Tous</option>
                    <option value="minute" @selected(request('rate_type') === 'minute')>À la minute</option>
                    <option value="kwh" @selected(request('rate_type') === 'kwh')>Par kWh</option>
                    <option value="fixed" @selected(request('rate_type') === 'fixed')>Prix fixe</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Statut</label>
                <select name="status" class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500">
                    <option value="">Tous</option>
                    <option value="active" @selected(request('status') === 'active')>Actifs</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactifs</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-orange-500 text-white text-sm rounded-lg hover:bg-orange-600 transition-colors">
                <i class="fas fa-search mr-1"></i> Filtrer
            </button>
            <a href="{{ route('admin.pricing-plans.index') }}" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm rounded-lg hover:bg-gray-200 transition-colors">
                <i class="fas fa-times mr-1"></i> Réinitialiser
            </a>
        </form>
    </div>

    {{-- Cards grid --}}
    @if($plans->isEmpty())
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-12 text-center text-gray-500 dark:text-gray-400">
            <i class="fas fa-tags text-4xl mb-3 opacity-30"></i>
            <p class="text-sm">Aucun plan tarifaire trouvé.</p>
            <a href="{{ route('admin.pricing-plans.create') }}" class="mt-2 inline-flex items-center text-orange-600 hover:underline text-sm">
                <i class="fas fa-plus mr-1"></i> Créer le premier plan
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($plans as $plan)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 flex flex-col gap-3 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">{{ $plan->name }}</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $plan->main_type_label }}</p>
                        </div>
                        <div class="flex items-center gap-1.5">
                            @if($plan->is_active)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-xs font-medium rounded-full">
                                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Actif
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-xs font-medium rounded-full">
                                    <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span> Inactif
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Price highlight --}}
                    <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                            {{ $plan->formatted_main_value ?? '—' }}
                        </p>
                        @if($plan->activation_fee)
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">+ {{ number_format($plan->activation_fee, 2) }} {{ $plan->currency }} frais d'activation</p>
                        @endif
                    </div>

                    {{-- Details --}}
                    <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                        <span><i class="fas fa-layer-group mr-1"></i>{{ $plan->groups->count() }} groupe(s)</span>
                        <span><i class="fas fa-charging-station mr-1"></i>{{ $plan->chargingPoints->count() }} borne(s)</span>
                        <span>Priorité {{ $plan->priority ?? 0 }}</span>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-2 pt-1 border-t border-gray-100 dark:border-gray-700">
                        <a href="{{ route('admin.pricing-plans.show', $plan->id) }}"
                           class="flex-1 text-center py-1.5 text-xs font-medium text-orange-600 hover:bg-orange-50 dark:hover:bg-orange-900/20 rounded-lg transition-colors">
                            <i class="fas fa-eye mr-1"></i> Voir
                        </a>
                        <a href="{{ route('admin.pricing-plans.edit', $plan->id) }}"
                           class="flex-1 text-center py-1.5 text-xs font-medium text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-lg transition-colors">
                            <i class="fas fa-edit mr-1"></i> Modifier
                        </a>
                        <form method="POST" action="{{ route('admin.pricing-plans.toggle-active', $plan->id) }}" class="flex-1">
                            @csrf
                            <button type="submit" class="w-full text-center py-1.5 text-xs font-medium {{ $plan->is_active ? 'text-gray-500 hover:bg-gray-50' : 'text-green-600 hover:bg-green-50' }} dark:hover:bg-gray-700 rounded-lg transition-colors">
                                <i class="fas {{ $plan->is_active ? 'fa-pause' : 'fa-play' }} mr-1"></i>
                                {{ $plan->is_active ? 'Pause' : 'Activer' }}
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($plans->hasPages())
            <div class="flex justify-center">
                {{ $plans->links() }}
            </div>
        @endif
    @endif

</div>
@endsection
