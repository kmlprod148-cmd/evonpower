@extends('layouts.app')

@section('title', __('Plans tarifaires'))

@section('content')
@php
    $planRoute = function (string $name, array $parameters = []): string {
        return \App\Support\RequestAwareRoute::to(request(), $name, $parameters);
    };
@endphp
<div class="px-4 py-6 max-w-7xl mx-auto">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-semibold flex items-center gap-2">
                <i class="fas fa-tags text-emerald-500"></i>
                {{ __('Plans tarifaires') }}
            </h1>
            <p class="text-sm text-gray-500">
                {{ __('Configurez les tarifs principaux (minute / kWh / fixe) et les blocs supplémentaires (heures de pointe, nuit, etc.).') }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ $planRoute('plans.create') }}"
               class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">
                <i class="fas fa-plus mr-2"></i>
                {{ __('Nouveau plan') }}
            </a>
        </div>
    </div>

    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
        <div class="md:col-span-2">
            <input type="text"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="{{ __('Rechercher par nom...') }}"
                   class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500">
        </div>
        <div>
            <select name="type"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white focus:ring-emerald-500 focus:border-emerald-500">
                <option value="">{{ __('Tous les types') }}</option>
                <option value="time" @selected(request('type') === 'time')>{{ __('À la minute') }}</option>
                <option value="energy" @selected(request('type') === 'energy')>{{ __('Par kWh') }}</option>
                <option value="fixed" @selected(request('type') === 'fixed')>{{ __('Fixe par recharge') }}</option>
            </select>
        </div>
        <div>
            <select name="status"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white focus:ring-emerald-500 focus:border-emerald-500">
                <option value="">{{ __('Tous les statuts') }}</option>
                <option value="active" @selected(request('status') === 'active')>{{ __('Actifs') }}</option>
                <option value="inactive" @selected(request('status') === 'inactive')>{{ __('Inactifs') }}</option>
            </select>
        </div>
    </form>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('Nom') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('Type') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('Valeur') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('TVA') }}</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('Groupes') }}</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('Bornes') }}</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('Statut') }}</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($plans as $plan)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">{{ $plan->name }}</div>
                            @if($plan->description)
                                <div class="text-xs text-gray-500 line-clamp-1">{{ $plan->description }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">
                                {{ $plan->main_type_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="font-semibold text-gray-900">
                                {{ $plan->formatted_main_value ?? '—' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @if($plan->vatRate)
                                <div class="text-xs text-gray-600">
                                    {{ $plan->vatRate->name ?? 'TVA' }}
                                </div>
                                <div class="text-xs font-semibold text-gray-900">
                                    {{ number_format($plan->vatRate->rate, 2) }} %
                                </div>
                            @else
                                <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="font-semibold text-gray-900">
                                {{ $plan->groups_count ?? $plan->groups()->count() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="font-semibold text-gray-900">
                                {{ $plan->charging_points_count ?? $plan->chargingPoints()->count() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($plan->is_active)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">
                                    {{ __('Actif') }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                    {{ __('Inactif') }}
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="inline-flex items-center gap-2">
                                <a href="{{ $planRoute('pricing-plans.show', [$plan]) }}"
                                   class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-eye mr-1"></i>{{ __('Détail') }}
                                </a>
                                <a href="{{ $planRoute('plans.edit', [$plan->id]) }}"
                                   class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium text-emerald-700 hover:bg-emerald-50">
                                    <i class="fas fa-edit mr-1"></i>{{ __('Modifier') }}
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">
                            {{ __('Aucun plan tarifaire configuré pour le moment.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-4 py-3 border-t border-gray-100 bg-gray-50">
            {{ $plans->links() }}
        </div>
    </div>
</div>
@endsection
