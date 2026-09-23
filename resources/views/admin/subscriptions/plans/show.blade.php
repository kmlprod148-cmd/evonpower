@extends('layouts.app')

@section('title', $plan->name . ' — Plan d\'abonnement')

@section('content')
@php
    $typeColors = [
        'per_charge'  => ['icon'=>'fa-bolt',                  'bg'=>'bg-blue-500',         'light'=>'bg-blue-100 dark:bg-blue-900/30',    'text'=>'text-blue-600 dark:text-blue-400',    'badge'=>'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'],
        'per_session' => ['icon'=>'fa-plug',                  'bg'=>'bg-indigo-500',        'light'=>'bg-indigo-100 dark:bg-indigo-900/30', 'text'=>'text-indigo-600 dark:text-indigo-400','badge'=>'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300'],
        'per_kwh'     => ['icon'=>'fa-battery-three-quarters', 'bg'=>'bg-amber-500',         'light'=>'bg-amber-100 dark:bg-amber-900/30',   'text'=>'text-amber-600 dark:text-amber-400',  'badge'=>'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'],
        'per_time'    => ['icon'=>'fa-clock',                 'bg'=>'bg-purple-500',        'light'=>'bg-purple-100 dark:bg-purple-900/30', 'text'=>'text-purple-600 dark:text-purple-400','badge'=>'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300'],
        'monthly'     => ['icon'=>'fa-calendar-day',          'bg'=>'bg-eco-green-600',     'light'=>'bg-eco-green-100 dark:bg-eco-green-900/30','text'=>'text-eco-green-700 dark:text-eco-green-400','badge'=>'bg-eco-green-100 text-eco-green-700 dark:bg-eco-green-900/30 dark:text-eco-green-300'],
        'quarterly'   => ['icon'=>'fa-calendar-week',         'bg'=>'bg-teal-500',          'light'=>'bg-teal-100 dark:bg-teal-900/30',    'text'=>'text-teal-600 dark:text-teal-400',   'badge'=>'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300'],
        'semi_annual' => ['icon'=>'fa-calendar-alt',          'bg'=>'bg-cyan-500',          'light'=>'bg-cyan-100 dark:bg-cyan-900/30',    'text'=>'text-cyan-600 dark:text-cyan-400',   'badge'=>'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300'],
        'annual'      => ['icon'=>'fa-award',                 'bg'=>'bg-orange-500',        'light'=>'bg-orange-100 dark:bg-orange-900/30','text'=>'text-orange-600 dark:text-orange-400','badge'=>'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300'],
    ];
    $tc = $typeColors[$plan->type] ?? $typeColors['monthly'];
    $activeCount = $plan->getActiveSubscriptionsCount();
@endphp

<div class="space-y-5" x-data="{ showDeleteModal: false }">

    {{-- ── Hero Header ─────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        {{-- Top color bar --}}
        <div class="h-1.5 {{ $tc['bg'] }}"></div>
        <div class="p-6">
            <div class="flex flex-col sm:flex-row sm:items-start gap-5">
                {{-- Icon --}}
                <div class="flex-shrink-0 w-16 h-16 rounded-2xl {{ $tc['light'] }} flex items-center justify-center shadow-sm">
                    <i class="fas {{ $tc['icon'] }} text-2xl {{ $tc['text'] }}"></i>
                </div>
                {{-- Title & meta --}}
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-1">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $plan->name }}</h1>
                        @if($plan->is_featured)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                <i class="fas fa-star text-[10px]"></i> Mis en avant
                            </span>
                        @endif
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold
                            {{ $plan->is_active ? 'bg-eco-green-100 text-eco-green-700 dark:bg-eco-green-900/30 dark:text-eco-green-400' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $plan->is_active ? 'bg-eco-green-500' : 'bg-gray-400' }}"></span>
                            {{ $plan->is_active ? 'Actif' : 'Inactif' }}
                        </span>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $tc['badge'] }}">
                            {{ $plan->type_label }}
                        </span>
                    </div>
                    @if($plan->description)
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">{{ $plan->description }}</p>
                    @endif
                </div>
                {{-- Actions --}}
                <div class="flex items-center gap-2 flex-shrink-0">
                    <a href="{{ route('admin.subscriptions.plans.edit', $plan->id) }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-eco-green-600 hover:bg-eco-green-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors">
                        <i class="fas fa-edit text-xs"></i> Modifier
                    </a>
                    <a href="{{ route('admin.subscriptions.plans.index') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                        <i class="fas fa-arrow-left text-xs"></i> Retour
                    </a>
                </div>
            </div>

            {{-- Quick Stats Strip --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-5 pt-5 border-t border-gray-100 dark:border-gray-700">
                <div class="text-center">
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $plan->formatted_price }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Prix HT</p>
                </div>
                <div class="text-center">
                    @if($plan->vat_rate > 0)
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $plan->formatted_price_with_vat }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Prix TTC ({{ $plan->vat_rate }}% TVA)</p>
                    @else
                        <p class="text-2xl font-bold text-gray-400 dark:text-gray-500">—</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Hors TVA</p>
                    @endif
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold {{ $activeCount > 0 ? 'text-eco-green-600 dark:text-eco-green-400' : 'text-gray-400 dark:text-gray-500' }}">
                        {{ $activeCount }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Abonnés actifs</p>
                </div>
                <div class="text-center">
                    @if($plan->duration_months > 0)
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $plan->duration_months }}<span class="text-sm font-normal text-gray-500"> mois</span></p>
                    @else
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">Auto</p>
                    @endif
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Cycle</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Main Content Grid ────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- ── LEFT (2/3) ───────────────────────────────────────────── --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Plan Details --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                        <i class="fas fa-info-circle text-gray-500 dark:text-gray-400 text-xs"></i>
                    </div>
                    <h2 class="text-sm font-semibold text-gray-800 dark:text-white">Détails du plan</h2>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                        <div>
                            <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Type de plan</dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $tc['badge'] }}">
                                    <i class="fas {{ $tc['icon'] }} mr-1.5 text-[10px]"></i>
                                    {{ $plan->type_label }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Durée minimale contrat</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                {{ $plan->min_contract_months ?? 1 }} mois
                            </dd>
                        </div>
                        @if($plan->cancellation_fee)
                        <div>
                            <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Frais de résiliation</dt>
                            <dd class="mt-1 text-sm font-medium text-red-600 dark:text-red-400">
                                {{ $plan->formatted_price ? number_format($plan->cancellation_fee, 2, ',', ' ') . ' €' : '—' }}
                            </dd>
                        </div>
                        @endif
                        <div>
                            <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Ordre d'affichage</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                {{ $plan->sort_order ?? 0 }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Créé le</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                {{ $plan->created_at?->format('d/m/Y à H:i') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Modifié le</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                {{ $plan->updated_at?->format('d/m/Y à H:i') }}
                            </dd>
                        </div>
                    </dl>

                    {{-- Quotas section --}}
                    @if($plan->max_sessions || $plan->max_kwh || $plan->max_duration_minutes || $plan->max_charging_points)
                    <div class="mt-5 pt-5 border-t border-gray-100 dark:border-gray-700">
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Limitations</p>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            @if($plan->max_sessions)
                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-3 text-center">
                                <i class="fas fa-bolt text-blue-500 text-lg mb-1"></i>
                                <p class="text-lg font-bold text-blue-700 dark:text-blue-300">{{ $plan->max_sessions }}</p>
                                <p class="text-xs text-blue-600 dark:text-blue-400">Sessions max</p>
                            </div>
                            @endif
                            @if($plan->max_kwh)
                            <div class="bg-amber-50 dark:bg-amber-900/20 rounded-xl p-3 text-center">
                                <i class="fas fa-battery-three-quarters text-amber-500 text-lg mb-1"></i>
                                <p class="text-lg font-bold text-amber-700 dark:text-amber-300">{{ $plan->max_kwh }}</p>
                                <p class="text-xs text-amber-600 dark:text-amber-400">kWh max</p>
                            </div>
                            @endif
                            @if($plan->max_duration_minutes)
                            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-xl p-3 text-center">
                                <i class="fas fa-clock text-purple-500 text-lg mb-1"></i>
                                <p class="text-lg font-bold text-purple-700 dark:text-purple-300">{{ $plan->max_duration_minutes }}</p>
                                <p class="text-xs text-purple-600 dark:text-purple-400">Minutes max</p>
                            </div>
                            @endif
                            @if($plan->max_charging_points)
                            <div class="bg-eco-green-50 dark:bg-eco-green-900/20 rounded-xl p-3 text-center">
                                <i class="fas fa-charging-station text-eco-green-600 dark:text-eco-green-400 text-lg mb-1"></i>
                                <p class="text-lg font-bold text-eco-green-700 dark:text-eco-green-300">{{ $plan->max_charging_points }}</p>
                                <p class="text-xs text-eco-green-600 dark:text-eco-green-400">Bornes max</p>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    {{-- Features --}}
                    @if($plan->features && count($plan->features) > 0)
                    <div class="mt-5 pt-5 border-t border-gray-100 dark:border-gray-700">
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">
                            <i class="fas fa-list-check mr-1.5"></i>Points forts
                        </p>
                        <ul class="space-y-1.5">
                            @foreach($plan->features as $feature)
                            <li class="flex items-center gap-2.5 text-sm text-gray-700 dark:text-gray-300">
                                <span class="flex-shrink-0 w-5 h-5 rounded-full bg-eco-green-100 dark:bg-eco-green-900/30 flex items-center justify-center">
                                    <i class="fas fa-check text-eco-green-600 dark:text-eco-green-400 text-[10px]"></i>
                                </span>
                                {{ $feature }}
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    {{-- Terms --}}
                    @if($plan->terms_conditions)
                    <div class="mt-5 pt-5 border-t border-gray-100 dark:border-gray-700">
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">
                            <i class="fas fa-file-contract mr-1.5"></i>Conditions générales
                        </p>
                        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg px-4 py-3 text-sm text-gray-600 dark:text-gray-400 leading-relaxed whitespace-pre-line">{{ $plan->terms_conditions }}</div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Access Control --}}
            @php
                $hasGroups = $plan->groups && $plan->groups->count() > 0;
                $hasStations = $plan->stations && $plan->stations->count() > 0;
                $hasCPs = $plan->chargingPoints && $plan->chargingPoints->count() > 0;
            @endphp
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                            <i class="fas fa-shield-alt text-indigo-600 dark:text-indigo-400 text-xs"></i>
                        </div>
                        <h2 class="text-sm font-semibold text-gray-800 dark:text-white">Contrôle d'accès</h2>
                    </div>
                    @if(!$hasGroups && !$hasStations && !$hasCPs)
                        <span class="text-xs text-eco-green-600 dark:text-eco-green-400 font-medium bg-eco-green-50 dark:bg-eco-green-900/20 px-2.5 py-1 rounded-full">
                            <i class="fas fa-globe-europe mr-1"></i>Accès universel
                        </span>
                    @endif
                </div>
                <div class="p-6">
                    @if(!$hasGroups && !$hasStations && !$hasCPs)
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Ce plan est accessible à <strong class="text-gray-700 dark:text-gray-300">tous les groupes</strong>,
                            <strong class="text-gray-700 dark:text-gray-300">toutes les stations</strong>
                            et <strong class="text-gray-700 dark:text-gray-300">toutes les bornes</strong>
                            sans restriction.
                        </p>
                    @else
                        <div class="space-y-5">
                            {{-- Groups --}}
                            <div>
                                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">
                                    <i class="fas fa-users mr-1.5"></i>Groupes clients
                                    @if($hasGroups)
                                        <span class="ml-1 px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-[11px] font-normal">{{ $plan->groups->count() }}</span>
                                    @endif
                                </p>
                                @if($hasGroups)
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach($plan->groups as $group)
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300 border border-indigo-100 dark:border-indigo-800/50">
                                                <i class="fas fa-users text-[10px] mr-1"></i>{{ $group->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-sm text-eco-green-600 dark:text-eco-green-400 flex items-center gap-1.5">
                                        <i class="fas fa-check-circle"></i> Tous les groupes
                                    </p>
                                @endif
                            </div>
                            {{-- Stations --}}
                            <div>
                                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">
                                    <i class="fas fa-map-marker-alt mr-1.5"></i>Stations
                                    @if($hasStations)
                                        <span class="ml-1 px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-[11px] font-normal">{{ $plan->stations->count() }}</span>
                                    @endif
                                </p>
                                @if($hasStations)
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach($plan->stations as $station)
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-teal-50 dark:bg-teal-900/20 text-teal-700 dark:text-teal-300 border border-teal-100 dark:border-teal-800/50">
                                                <i class="fas fa-map-marker-alt text-[10px] mr-1"></i>{{ $station->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-sm text-eco-green-600 dark:text-eco-green-400 flex items-center gap-1.5">
                                        <i class="fas fa-check-circle"></i> Toutes les stations
                                    </p>
                                @endif
                            </div>
                            {{-- Charging Points --}}
                            <div>
                                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">
                                    <i class="fas fa-charging-station mr-1.5"></i>Bornes spécifiques
                                    @if($hasCPs)
                                        <span class="ml-1 px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-[11px] font-normal">{{ $plan->chargingPoints->count() }}</span>
                                    @endif
                                </p>
                                @if($hasCPs)
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach($plan->chargingPoints as $cp)
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-eco-green-50 dark:bg-eco-green-900/20 text-eco-green-700 dark:text-eco-green-300 border border-eco-green-100 dark:border-eco-green-800/50">
                                                <i class="fas fa-charging-station text-[10px] mr-1"></i>{{ $cp->name ?? $cp->charge_point_id }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-sm text-eco-green-600 dark:text-eco-green-400 flex items-center gap-1.5">
                                        <i class="fas fa-check-circle"></i> Toutes les bornes
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Active Subscribers --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-eco-green-100 dark:bg-eco-green-900/30 flex items-center justify-center">
                            <i class="fas fa-users text-eco-green-600 dark:text-eco-green-400 text-xs"></i>
                        </div>
                        <h2 class="text-sm font-semibold text-gray-800 dark:text-white">Abonnés actifs</h2>
                    </div>
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2.5 py-1 rounded-full">
                        {{ $activeSubscriptions->total() }} au total
                    </span>
                </div>

                @if($activeSubscriptions->count() > 0)
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($activeSubscriptions as $subscription)
                    <div class="flex items-center gap-4 px-6 py-3.5 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                        {{-- Avatar --}}
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-eco-green-400 to-teal-500 flex items-center justify-center flex-shrink-0 text-white font-semibold text-sm">
                            {{ strtoupper(substr($subscription->user->name ?? '?', 0, 1)) }}
                        </div>
                        {{-- Info --}}
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                {{ $subscription->user->name ?? 'Utilisateur inconnu' }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                {{ $subscription->user->email ?? '' }}
                            </p>
                        </div>
                        {{-- Dates --}}
                        <div class="text-right flex-shrink-0 hidden sm:block">
                            @if($subscription->end_date)
                                <p class="text-xs font-medium text-gray-600 dark:text-gray-300">
                                    Expire {{ \Carbon\Carbon::parse($subscription->end_date)->diffForHumans() }}
                                </p>
                            @endif
                            <p class="text-xs text-gray-400 dark:text-gray-500">
                                Depuis {{ $subscription->start_date ? \Carbon\Carbon::parse($subscription->start_date)->format('d/m/Y') : '—' }}
                            </p>
                        </div>
                    </div>
                    @endforeach
                </div>
                @if($activeSubscriptions->hasPages())
                    <div class="px-5 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/30">
                        {{ $activeSubscriptions->links() }}
                    </div>
                @endif
                @else
                <div class="py-12 text-center">
                    <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-user-slash text-gray-400 dark:text-gray-500 text-lg"></i>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Aucun abonné actif pour ce plan</p>
                </div>
                @endif
            </div>

        </div>{{-- end left --}}

        {{-- ── RIGHT SIDEBAR (1/3) ──────────────────────────────────── --}}
        <div class="space-y-5">

            {{-- Quick Actions --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-2">
                    <i class="fas fa-bolt text-eco-green-500 text-sm"></i>
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Actions rapides</h3>
                </div>
                <div class="p-4 space-y-2">
                    {{-- Edit --}}
                    <a href="{{ route('admin.subscriptions.plans.edit', $plan->id) }}"
                       class="w-full inline-flex items-center gap-2.5 px-4 py-2.5 bg-eco-green-600 hover:bg-eco-green-700 text-white text-sm font-medium rounded-lg transition-colors">
                        <i class="fas fa-edit text-xs"></i> Modifier le plan
                    </a>
                    {{-- Toggle Active --}}
                    <form method="POST" action="{{ route('admin.subscriptions.plans.toggle-active', $plan->id) }}">
                        @csrf
                        <button type="submit"
                                class="w-full inline-flex items-center gap-2.5 px-4 py-2.5 text-sm font-medium rounded-lg transition-colors
                                    {{ $plan->is_active
                                        ? 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 hover:bg-amber-100 border border-amber-200 dark:border-amber-800/50'
                                        : 'bg-eco-green-50 dark:bg-eco-green-900/20 text-eco-green-700 dark:text-eco-green-400 hover:bg-eco-green-100 border border-eco-green-200 dark:border-eco-green-800/50' }}">
                            <i class="fas {{ $plan->is_active ? 'fa-pause-circle' : 'fa-play-circle' }} text-xs"></i>
                            {{ $plan->is_active ? 'Désactiver le plan' : 'Activer le plan' }}
                        </button>
                    </form>
                    {{-- Toggle Featured --}}
                    <form method="POST" action="{{ route('admin.subscriptions.plans.toggle-featured', $plan->id) }}">
                        @csrf
                        <button type="submit"
                                class="w-full inline-flex items-center gap-2.5 px-4 py-2.5 text-sm font-medium rounded-lg transition-colors border
                                    {{ $plan->is_featured
                                        ? 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 hover:bg-amber-100 border-amber-200 dark:border-amber-800/50'
                                        : 'bg-gray-50 dark:bg-gray-900/30 text-gray-600 dark:text-gray-400 hover:bg-amber-50 hover:text-amber-700 border-gray-200 dark:border-gray-700' }}">
                            <i class="fas fa-star text-xs"></i>
                            {{ $plan->is_featured ? 'Retirer de la une' : 'Mettre en avant' }}
                        </button>
                    </form>
                    {{-- Delete --}}
                    <button type="button"
                            @click="showDeleteModal = true"
                            class="w-full inline-flex items-center gap-2.5 px-4 py-2.5 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-sm font-medium rounded-lg hover:bg-red-100 border border-red-200 dark:border-red-800/50 transition-colors">
                        <i class="fas fa-trash text-xs"></i> Supprimer le plan
                    </button>
                </div>
            </div>

            {{-- Plan Options / Flags --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                        <i class="fas fa-sliders-h text-gray-500 dark:text-gray-400 text-xs"></i>
                    </div>
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Options du plan</h3>
                </div>
                <div class="p-4 divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach([
                        ['label' => 'Renouvellement automatique', 'value' => $plan->allow_renewal,  'icon' => 'fa-sync'],
                        ['label' => 'Mise à niveau autorisée',    'value' => $plan->allow_upgrade,   'icon' => 'fa-arrow-up'],
                        ['label' => 'Déclassement autorisé',      'value' => $plan->allow_downgrade, 'icon' => 'fa-arrow-down'],
                    ] as $opt)
                    <div class="flex items-center justify-between py-3">
                        <div class="flex items-center gap-2.5 text-sm text-gray-700 dark:text-gray-300">
                            <i class="fas {{ $opt['icon'] }} text-gray-400 dark:text-gray-500 w-4 text-center text-xs"></i>
                            {{ $opt['label'] }}
                        </div>
                        @if($opt['value'])
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-eco-green-100 text-eco-green-700 dark:bg-eco-green-900/30 dark:text-eco-green-400">
                                <i class="fas fa-check text-[10px]"></i> Oui
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                                <i class="fas fa-times text-[10px]"></i> Non
                            </span>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Pricing breakdown --}}
            @if($plan->vat_rate > 0)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                        <i class="fas fa-receipt text-blue-600 dark:text-blue-400 text-xs"></i>
                    </div>
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Détail tarifaire</h3>
                </div>
                <div class="p-4 space-y-2">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Prix HT</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $plan->formatted_price }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">TVA ({{ $plan->vat_rate }}%)</span>
                        <span class="font-medium text-amber-600 dark:text-amber-400">{{ $plan->formatted_vat_amount }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm font-semibold border-t border-gray-100 dark:border-gray-700 pt-2 mt-2">
                        <span class="text-gray-800 dark:text-gray-200">Total TTC</span>
                        <span class="text-eco-green-700 dark:text-eco-green-300 text-base">{{ $plan->formatted_price_with_vat }}</span>
                    </div>
                </div>
            </div>
            @endif

        </div>{{-- end sidebar --}}
    </div>

    {{-- ── Delete Confirmation Modal ───────────────────────────────── --}}
    <div x-show="showDeleteModal"
         x-cloak
         class="fixed inset-0 z-[9998] flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showDeleteModal = false"></div>
        <div class="relative z-10 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md p-6"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-start gap-4 mb-4">
                <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-lg"></i>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Supprimer ce plan ?</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        Vous allez supprimer <strong class="text-gray-700 dark:text-gray-300">{{ $plan->name }}</strong>
                    </p>
                </div>
            </div>
            @if($activeCount > 0)
            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/50 rounded-lg p-3 mb-4">
                <p class="text-xs text-amber-700 dark:text-amber-400">
                    <i class="fas fa-exclamation-circle mr-1.5"></i>
                    <strong>{{ $activeCount }} abonnement(s) actif(s)</strong> sont liés à ce plan. Ils ne seront pas supprimés immédiatement.
                </p>
            </div>
            @endif
            <p class="text-sm text-gray-600 dark:text-gray-400 bg-red-50 dark:bg-red-900/20 rounded-lg px-4 py-3 mb-5">
                <i class="fas fa-info-circle text-red-400 mr-1.5"></i>
                Cette action est irréversible.
            </p>
            <div class="flex gap-3">
                <button @click="showDeleteModal = false"
                        class="flex-1 px-4 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    Annuler
                </button>
                <form action="{{ route('admin.subscriptions.plans.destroy', $plan->id) }}" method="POST" class="flex-1">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="w-full px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
                        <i class="fas fa-trash mr-1.5"></i> Supprimer
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
