@extends('layouts.app')

@section('title', __('Détail du plan tarifaire'))

@section('content')
@php
    $planRoute = function (string $name, array $parameters = []): string {
        return \App\Support\RequestAwareRoute::to(request(), $name, $parameters);
    };
@endphp
<div class="px-4 py-6 max-w-6xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ $planRoute('pricing-plans.index') }}" class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1 mb-2">
                <i class="fas fa-arrow-left"></i>
                <span>{{ __('Retour aux plans') }}</span>
            </a>
            <h1 class="text-2xl font-semibold flex items-center gap-2">
                <i class="fas fa-tags text-emerald-500"></i>
                {{ $plan->name }}
            </h1>
            @if($plan->description)
                <p class="text-sm text-gray-500 mt-1 max-w-2xl">{{ $plan->description }}</p>
            @endif
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ $planRoute('plans.edit', [$plan->id]) }}"
               class="inline-flex items-center px-4 py-2 rounded-lg border border-gray-200 text-sm font-medium text-gray-700 hover:bg-gray-50">
                <i class="fas fa-edit mr-2"></i>{{ __('Modifier') }}
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="md:col-span-2 space-y-5">
            <div class="bg-white rounded-xl shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4 uppercase tracking-wide">
                    {{ __('Bloc principal') }}
                </h2>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-gray-500">{{ __('Type principal') }}</dt>
                        <dd class="mt-1 font-medium text-gray-900">{{ $plan->main_type_label }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('Montant de base') }}</dt>
                        <dd class="mt-1 font-medium text-gray-900">
                            {{ $plan->formatted_main_value ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('TVA') }}</dt>
                        <dd class="mt-1 font-medium text-gray-900">
                            @if($plan->vatRate)
                                {{ $plan->vatRate->name ?? 'TVA' }} ({{ number_format($plan->vatRate->rate, 2) }} %)
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('Priorité') }}</dt>
                        <dd class="mt-1 font-medium text-gray-900">{{ $plan->priority ?? 0 }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('Devise') }}</dt>
                        <dd class="mt-1 font-medium text-gray-900">{{ $plan->currency ?? 'EUR' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('Statut') }}</dt>
                        <dd class="mt-1">
                            @if($plan->is_active)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">
                                    {{ __('Actif') }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                    {{ __('Inactif') }}
                                </span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                        {{ __('Blocs supplémentaires (Pick time, tarifs combinés)') }}
                    </h2>
                </div>

                @if($plan->additionalRates->isEmpty())
                    <p class="text-sm text-gray-500">
                        {{ __('Aucun bloc supplémentaire configuré pour ce plan.') }}
                    </p>
                @else
                    <div class="overflow-x-auto -mx-4 sm:mx-0">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                        {{ __('Nom du bloc') }}
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                        {{ __('Type') }}
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                        {{ __('Prix') }}
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                        {{ __('TVA') }}
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                        {{ __('Créneaux') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($plan->additionalRates as $rate)
                                    <tr>
                                        <td class="px-4 py-2">
                                            <div class="font-medium text-gray-900">{{ $rate->name }}</div>
                                        </td>
                                        <td class="px-4 py-2">
                                            @php
                                                $label = match($rate->type ?? $rate->rate_type ?? null) {
                                                    'per_minute', 'time', 'minute' => __('À la minute'),
                                                    'per_kwh', 'energy', 'kwh' => __('Par kWh'),
                                                    'fixed' => __('Fixe par recharge'),
                                                    default => __('Non défini'),
                                                };
                                            @endphp
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700">
                                                {{ $label }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2">
                                            @php
                                                $value = $rate->value ?? $rate->price;
                                            @endphp
                                            <span class="font-medium text-gray-900">
                                                {{ number_format($value, 3) }} {{ $plan->currency ?? 'EUR' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2">
                                            @if($rate->vatRate)
                                                <span class="text-xs font-medium text-gray-900">
                                                    {{ number_format($rate->vatRate->rate, 2) }} %
                                                </span>
                                            @else
                                                <span class="text-xs text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2">
                                            @if($rate->days && $rate->days->count())
                                                <div class="text-xs text-gray-700 space-y-1">
                                                    @foreach($rate->days as $day)
                                                        <div>
                                                            J{{ $day->day_of_week }} {{ $day->start_time }}–{{ $day->end_time }}
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-xs text-gray-400">
                                                    {{ __('Tous les jours / toute la journée') }}
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="space-y-5">
            <div class="bg-white rounded-xl shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4 uppercase tracking-wide">
                    {{ __('Statistiques') }}
                </h2>
                <dl class="space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-gray-500">{{ __('Groupes associés') }}</dt>
                        <dd class="font-semibold text-gray-900">
                            {{ $stats['groups_count'] ?? $plan->groups()->count() }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-gray-500">{{ __('Bornes associées') }}</dt>
                        <dd class="font-semibold text-gray-900">
                            {{ $stats['charging_points_count'] ?? $plan->chargingPoints()->count() }}
                        </dd>
                    </div>
                    @if(isset($stats['total_revenue']))
                        <div class="flex items-center justify-between">
                            <dt class="text-gray-500">{{ __('Revenus cumulés') }}</dt>
                            <dd class="font-semibold text-gray-900">
                                {{ number_format($stats['total_revenue'], 2) }} {{ $plan->currency ?? 'EUR' }}
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                        {{ __('Groupes de bornes') }}
                    </h2>
                    <a href="{{ $planRoute('plans.edit', [$plan->id]) }}#groups"
                       class="text-xs font-medium text-emerald-700 hover:text-emerald-800">
                        {{ __('Gérer les associations') }}
                    </a>
                </div>

                @if($plan->groups->isEmpty())
                    <p class="text-sm text-gray-500">
                        {{ __('Aucun groupe de bornes associé pour ce plan.') }}
                    </p>
                @else
                    <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
                        @foreach($plan->groups as $group)
                            <div class="flex items-center justify-between px-3 py-2 rounded-lg border border-gray-100">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ $group->name }}</div>
                                    <div class="text-xs text-gray-500">
                                        {{ $group->city ?? '—' }}
                                    </div>
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $group->chargingPoints()->count() }} {{ __('bornes') }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="bg-white rounded-xl shadow-sm p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                        {{ __('Bornes de recharge') }}
                    </h2>
                    <a href="{{ $planRoute('charging-points.index') }}"
                       class="text-xs font-medium text-emerald-700 hover:text-emerald-800">
                        {{ __('Gérer dans la liste des bornes') }}
                    </a>
                </div>

                @if($plan->chargingPoints->isEmpty())
                    <p class="text-sm text-gray-500">
                        {{ __('Aucune borne n’utilise ce plan actuellement.') }}
                    </p>
                @else
                    <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
                        @foreach($plan->chargingPoints->take(10) as $cp)
                            <div class="flex items-center justify-between px-3 py-2 rounded-lg border border-gray-100">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $cp->name ?? $cp->code ?? ('CP#'.$cp->id) }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ $cp->station->name ?? '—' }}
                                    </div>
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $cp->status ?? '—' }}
                                </div>
                            </div>
                        @endforeach
                        @if($plan->chargingPoints->count() > 10)
                            <div class="text-xs text-gray-500 mt-1">
                                {{ __('+ :count bornes supplémentaires', ['count' => $plan->chargingPoints->count() - 10]) }}
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
