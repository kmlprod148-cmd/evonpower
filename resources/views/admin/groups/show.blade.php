@extends('layouts.app')

@section('title', 'Groupe : ' . $group->name)
@section('page-title', $group->name)

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.groups.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div class="w-14 h-14 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl flex items-center justify-center text-white shadow-lg">
                    <i class="fas fa-layer-group text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ $group->name }}</h1>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="px-2 py-0.5 {{ $group->type === 'business' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }} text-xs rounded-full dark:bg-opacity-30">
                            {{ $group->type === 'business' ? 'Business' : 'Privé' }}
                        </span>
                        @if($group->city)
                            <span class="text-xs text-gray-400"><i class="fas fa-map-marker-alt mr-0.5"></i>{{ $group->city }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.groups.edit', $group->id) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors">
                    <i class="fas fa-edit"></i> Modifier
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-400 p-4 rounded-lg flex items-center gap-2">
            <i class="fas fa-check-circle text-green-600"></i>
            <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
        </div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['charging_points'] }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Bornes totales</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['active_points'] }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Bornes actives</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['pricing_plans'] }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Plans tarifaires</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Details --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <i class="fas fa-info-circle text-emerald-500"></i> Informations
            </h3>
            <dl class="space-y-3 text-sm">
                <div><dt class="text-gray-500 dark:text-gray-400">Nom</dt><dd class="font-medium text-gray-900 dark:text-white">{{ $group->name }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">Type</dt><dd class="font-medium text-gray-900 dark:text-white">{{ $group->type === 'business' ? 'Business' : 'Privé' }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">Mode facturation</dt><dd class="font-medium text-gray-900 dark:text-white capitalize">{{ $group->consumption_mode ?? 'Prépayé' }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">Ville</dt><dd class="font-medium text-gray-900 dark:text-white">{{ $group->city ?? '—' }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">Partenaire</dt><dd class="font-medium">
                    @if($group->partner)
                        <a href="{{ route('admin.partners.show', $group->partner_id) }}" class="text-blue-600 hover:underline">{{ $group->partner->name }}</a>
                    @else <span class="text-gray-400">—</span> @endif
                </dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">Intégrateur</dt><dd class="font-medium">
                    @if($group->integrator)
                        <a href="{{ route('admin.integrators.show', $group->integrator_id) }}" class="text-indigo-600 hover:underline">{{ $group->integrator->name }}</a>
                    @else <span class="text-gray-400">—</span> @endif
                </dd></div>
                @if($group->description)
                    <div><dt class="text-gray-500 dark:text-gray-400">Description</dt><dd class="font-medium text-gray-900 dark:text-white text-xs">{{ $group->description }}</dd></div>
                @endif
            </dl>

            {{-- Plans tarifaires --}}
            @if($group->pricingPlans->isNotEmpty())
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white mb-2">Plans tarifaires</p>
                    <div class="space-y-1.5">
                        @foreach($group->pricingPlans as $plan)
                            <div class="flex items-center justify-between p-2 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg">
                                <span class="text-xs font-medium text-emerald-800 dark:text-emerald-300">{{ $plan->name }}</span>
                                <span class="text-xs text-gray-500">{{ $plan->formatted_main_value ?? '—' }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Charging points --}}
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-charging-station text-emerald-500"></i> Bornes du groupe ({{ $group->chargingPoints->count() }})
                </h3>
            </div>
            @if($group->chargingPoints->isEmpty())
                <div class="text-center py-8 text-gray-400 dark:text-gray-500">
                    <i class="fas fa-charging-station text-3xl mb-2 opacity-30"></i>
                    <p class="text-sm">Aucune borne dans ce groupe</p>
                </div>
            @else
                <div class="space-y-2 max-h-80 overflow-y-auto">
                    @foreach($group->chargingPoints as $point)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center
                                    {{ $point->status === 'Available' ? 'bg-green-100 dark:bg-green-900/40' :
                                       ($point->status === 'Charging' ? 'bg-blue-100 dark:bg-blue-900/40' : 'bg-gray-100 dark:bg-gray-700') }}">
                                    <i class="fas fa-bolt text-xs
                                        {{ $point->status === 'Available' ? 'text-green-600' :
                                           ($point->status === 'Charging' ? 'text-blue-600' : 'text-gray-400') }}"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $point->name ?? $point->charge_point_id }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">ID: {{ $point->charge_point_id }}</p>
                                </div>
                            </div>
                            <span class="text-xs {{ $point->is_active ? 'text-green-600' : 'text-gray-400' }}">
                                {{ $point->status ?? ($point->is_active ? 'Actif' : 'Inactif') }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
</div>
@endsection
