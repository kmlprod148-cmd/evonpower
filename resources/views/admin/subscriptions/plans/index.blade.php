@extends('layouts.app')

@section('title', 'Plans d\'abonnement')

@section('content')
<div class="space-y-5" x-data="plansIndex()">

    {{-- ── Header ─────────────────────────────────────────────────── --}}
    <div class="relative overflow-hidden rounded-2xl p-6 shadow-lg"
         style="background: linear-gradient(135deg, #3bb86b 0%, #2dd4bf 55%, #06b6d4 100%);">
        {{-- Decorative circles --}}
        <div class="pointer-events-none absolute -top-12 -right-12 w-48 h-48 rounded-full bg-white/10"></div>
        <div class="pointer-events-none absolute bottom-0 left-1/2 w-32 h-32 rounded-full bg-white/5"></div>

        <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="flex-shrink-0 w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                    <i class="fas fa-id-card text-white text-xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-white leading-tight">Plans d'abonnement</h1>
                    <p class="text-sm text-white/80 mt-0.5">Gérez vos offres de recharge EV</p>
                </div>
            </div>
            <a href="{{ route('admin.subscriptions.plans.create') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-white text-white text-sm font-semibold rounded-xl shadow-md hover:shadow-lg hover:bg-white/90 transition-all duration-200 flex-shrink-0">
                <i class="fas fa-plus text-sm"></i>
                Nouveau plan
            </a>
        </div>

        {{-- Stats Strip --}}
        <div class="relative grid grid-cols-2 sm:grid-cols-4 gap-3 mt-5">
            <div class="bg-white/15 backdrop-blur-sm rounded-xl p-3 text-center">
                <p class="text-2xl font-bold text-white">{{ $stats['total'] }}</p>
                <p class="text-xs text-white/70 mt-0.5 font-medium">Total plans</p>
            </div>
            <div class="bg-white/15 backdrop-blur-sm rounded-xl p-3 text-center">
                <p class="text-2xl font-bold text-white">{{ $stats['active'] }}</p>
                <p class="text-xs text-white/70 mt-0.5 font-medium">Actifs</p>
            </div>
            <div class="bg-white/15 backdrop-blur-sm rounded-xl p-3 text-center">
                <p class="text-2xl font-bold text-white">{{ $stats['featured'] }}</p>
                <p class="text-xs text-white/70 mt-0.5 font-medium">Mis en avant</p>
            </div>
            <div class="bg-white/15 backdrop-blur-sm rounded-xl p-3 text-center">
                <p class="text-2xl font-bold text-white">{{ $stats['subscribers'] }}</p>
                <p class="text-xs text-white/70 mt-0.5 font-medium">Abonnés actifs</p>
            </div>
        </div>
    </div>

    {{-- ── Filters ─────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <form method="GET" action="{{ route('admin.subscriptions.plans.index') }}"
              class="flex flex-wrap gap-3 items-end">
            {{-- Search --}}
            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">Recherche</label>
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                           placeholder="Nom ou description…"
                           class="w-full pl-8 pr-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-eco-green-500 focus:border-eco-green-500">
                </div>
            </div>
            {{-- Type --}}
            <div class="w-44">
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">Type</label>
                <select name="type"
                        class="w-full py-2 px-3 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-eco-green-500 focus:border-eco-green-500">
                    <option value="">Tous les types</option>
                    @foreach($types as $value => $label)
                        <option value="{{ $value }}" {{ ($filters['type'] ?? '') == $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
            {{-- Status --}}
            <div class="w-36">
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">Statut</label>
                <select name="active"
                        class="w-full py-2 px-3 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-eco-green-500 focus:border-eco-green-500">
                    <option value="">Tous</option>
                    <option value="1" {{ ($filters['active'] ?? '') === '1' ? 'selected' : '' }}>Actifs</option>
                    <option value="0" {{ ($filters['active'] ?? '') === '0' ? 'selected' : '' }}>Inactifs</option>
                </select>
            </div>
            {{-- Featured --}}
            <div class="w-36">
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1.5">Mis en avant</label>
                <select name="featured"
                        class="w-full py-2 px-3 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-eco-green-500 focus:border-eco-green-500">
                    <option value="">Tous</option>
                    <option value="1" {{ ($filters['featured'] ?? '') === '1' ? 'selected' : '' }}>Oui</option>
                    <option value="0" {{ ($filters['featured'] ?? '') === '0' ? 'selected' : '' }}>Non</option>
                </select>
            </div>
            {{-- Buttons --}}
            <div class="flex gap-2">
                <button type="submit"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-eco-green-600 hover:bg-eco-green-700 text-white text-sm font-medium rounded-lg transition-colors">
                    <i class="fas fa-search text-xs"></i> Filtrer
                </button>
                @if(array_filter($filters ?? []))
                    <a href="{{ route('admin.subscriptions.plans.index') }}"
                       class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                        <i class="fas fa-times text-xs"></i> Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- ── Plans Table ─────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        @if($plans->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-900/50">
                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Plan</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Prix HT</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Durée / Quotas</th>
                        <th class="px-4 py-3.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Abonnés</th>
                        <th class="px-4 py-3.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Statut</th>
                        <th class="px-4 py-3.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($plans as $plan)
                    @php
                        $typeColors = [
                            'per_charge'  => ['icon'=>'fa-bolt',                 'bg'=>'bg-blue-100 dark:bg-blue-900/30',   'text'=>'text-blue-600 dark:text-blue-400',   'badge'=>'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'],
                            'per_session' => ['icon'=>'fa-plug',                 'bg'=>'bg-indigo-100 dark:bg-indigo-900/30','text'=>'text-indigo-600 dark:text-indigo-400','badge'=>'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300'],
                            'per_kwh'     => ['icon'=>'fa-battery-three-quarters','bg'=>'bg-amber-100 dark:bg-amber-900/30',  'text'=>'text-amber-600 dark:text-amber-400',  'badge'=>'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'],
                            'per_time'    => ['icon'=>'fa-clock',                'bg'=>'bg-purple-100 dark:bg-purple-900/30','text'=>'text-purple-600 dark:text-purple-400','badge'=>'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300'],
                            'monthly'     => ['icon'=>'fa-calendar-day',         'bg'=>'bg-eco-green-100 dark:bg-eco-green-900/30','text'=>'text-eco-green-700 dark:text-eco-green-400','badge'=>'bg-eco-green-100 text-eco-green-700 dark:bg-eco-green-900/30 dark:text-eco-green-300'],
                            'quarterly'   => ['icon'=>'fa-calendar-week',        'bg'=>'bg-teal-100 dark:bg-teal-900/30',   'text'=>'text-teal-600 dark:text-teal-400',   'badge'=>'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300'],
                            'semi_annual' => ['icon'=>'fa-calendar-alt',         'bg'=>'bg-cyan-100 dark:bg-cyan-900/30',   'text'=>'text-cyan-600 dark:text-cyan-400',   'badge'=>'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300'],
                            'annual'      => ['icon'=>'fa-award',                'bg'=>'bg-orange-100 dark:bg-orange-900/30','text'=>'text-orange-600 dark:text-orange-400','badge'=>'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300'],
                        ];
                        $tc = $typeColors[$plan->type] ?? $typeColors['monthly'];
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                        {{-- Plan name + description --}}
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0 {{ $tc['bg'] }}">
                                    <i class="fas {{ $tc['icon'] }} text-sm {{ $tc['text'] }}"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $plan->name }}</span>
                                        @if($plan->is_featured)
                                            <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[11px] font-semibold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                                <i class="fas fa-star text-[9px]"></i> Pro
                                            </span>
                                        @endif
                                    </div>
                                    @if($plan->description)
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate max-w-xs">
                                            {{ Str::limit($plan->description, 60) }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        {{-- Type badge --}}
                        <td class="px-4 py-4">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $tc['badge'] }}">
                                {{ $plan->type_label }}
                            </span>
                        </td>
                        {{-- Price --}}
                        <td class="px-4 py-4 whitespace-nowrap">
                            <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $plan->formatted_price }}</p>
                            @if($plan->vat_rate > 0)
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    TTC&nbsp;{{ $plan->formatted_price_with_vat }}
                                    <span class="text-gray-400">(TVA {{ $plan->vat_rate }}%)</span>
                                </p>
                            @else
                                <p class="text-xs text-gray-400 dark:text-gray-500">Hors TVA</p>
                            @endif
                        </td>
                        {{-- Duration / Quotas --}}
                        <td class="px-4 py-4">
                            <div class="flex flex-wrap gap-1">
                                @if($plan->duration_months > 0)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-xs rounded-full">
                                        <i class="fas fa-calendar-check text-[10px]"></i>
                                        {{ $plan->duration_months }}&nbsp;mois
                                    </span>
                                @endif
                                @if($plan->max_sessions)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 text-xs rounded-full">
                                        <i class="fas fa-bolt text-[10px]"></i>{{ $plan->max_sessions }}&nbsp;sess.
                                    </span>
                                @endif
                                @if($plan->max_kwh)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300 text-xs rounded-full">
                                        <i class="fas fa-battery-full text-[10px]"></i>{{ $plan->max_kwh }}&nbsp;kWh
                                    </span>
                                @endif
                                @if($plan->max_duration_minutes)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300 text-xs rounded-full">
                                        <i class="fas fa-clock text-[10px]"></i>{{ $plan->max_duration_minutes }}&nbsp;min
                                    </span>
                                @endif
                                @if(!$plan->duration_months && !$plan->max_sessions && !$plan->max_kwh && !$plan->max_duration_minutes)
                                    <span class="text-xs text-gray-400 dark:text-gray-500 italic">Illimité</span>
                                @endif
                            </div>
                        </td>
                        {{-- Subscriber count --}}
                        <td class="px-4 py-4 text-center">
                            @php $subCount = $plan->getActiveSubscriptionsCount(); @endphp
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold
                                {{ $subCount > 0 ? 'bg-eco-green-100 text-eco-green-700 dark:bg-eco-green-900/30 dark:text-eco-green-400' : 'bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500' }}">
                                {{ $subCount }}
                            </span>
                        </td>
                        {{-- Status (clickable toggle) --}}
                        <td class="px-4 py-4 text-center">
                            <form method="POST" action="{{ route('admin.subscriptions.plans.toggle-active', $plan->id) }}" class="inline">
                                @csrf
                                <button type="submit" title="{{ $plan->is_active ? 'Cliquer pour désactiver' : 'Cliquer pour activer' }}"
                                        class="group inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold transition-all duration-200
                                            {{ $plan->is_active
                                                ? 'bg-eco-green-100 text-eco-green-700 hover:bg-red-100 hover:text-red-600 dark:bg-eco-green-900/30 dark:text-eco-green-400 dark:hover:bg-red-900/30 dark:hover:text-red-400'
                                                : 'bg-gray-100 text-gray-500 hover:bg-eco-green-100 hover:text-eco-green-700 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-eco-green-900/30 dark:hover:text-eco-green-400' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $plan->is_active ? 'bg-eco-green-500 group-hover:bg-red-500' : 'bg-gray-400 group-hover:bg-eco-green-500' }} transition-colors"></span>
                                    <span class="{{ $plan->is_active ? 'group-hover:hidden' : '' }}">{{ $plan->is_active ? 'Actif' : 'Inactif' }}</span>
                                    @if($plan->is_active)
                                        <span class="hidden group-hover:inline">Désactiver</span>
                                    @else
                                        <span class="hidden group-hover:inline">Activer</span>
                                    @endif
                                </button>
                            </form>
                        </td>
                        {{-- Actions --}}
                        <td class="px-4 py-4">
                            <div class="flex items-center justify-end gap-1.5">
                                <a href="{{ route('admin.subscriptions.plans.show', $plan->id) }}"
                                   title="Voir le détail"
                                   class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:bg-blue-100 hover:text-blue-600 dark:hover:bg-blue-900/30 dark:hover:text-blue-400 transition-colors">
                                    <i class="fas fa-eye text-xs"></i>
                                </a>
                                <a href="{{ route('admin.subscriptions.plans.edit', $plan->id) }}"
                                   title="Modifier"
                                   class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:bg-amber-100 hover:text-amber-600 dark:hover:bg-amber-900/30 dark:hover:text-amber-400 transition-colors">
                                    <i class="fas fa-edit text-xs"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.subscriptions.plans.toggle-featured', $plan->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" title="{{ $plan->is_featured ? 'Retirer de la une' : 'Mettre en avant' }}"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 transition-colors
                                                {{ $plan->is_featured ? 'text-amber-500 hover:bg-amber-100 dark:hover:bg-amber-900/30' : 'text-gray-400 hover:bg-amber-100 hover:text-amber-500 dark:hover:bg-amber-900/30 dark:hover:text-amber-400' }}">
                                        <i class="fas fa-star text-xs"></i>
                                    </button>
                                </form>
                                <button type="button"
                                        title="Supprimer"
                                        @click="confirmDelete({{ $plan->id }}, '{{ addslashes($plan->name) }}')"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:bg-red-100 hover:text-red-600 dark:hover:bg-red-900/30 dark:hover:text-red-400 transition-colors">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($plans->hasPages())
            <div class="px-5 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/30">
                {{ $plans->withQueryString()->links() }}
            </div>
        @endif

        @else
        {{-- Empty state --}}
        <div class="py-20 text-center">
            <div class="w-20 h-20 bg-gray-100 dark:bg-gray-700 rounded-2xl flex items-center justify-center mx-auto mb-5">
                <i class="fas fa-id-card text-3xl text-gray-300 dark:text-gray-500"></i>
            </div>
            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">
                Aucun plan trouvé
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
                @if(array_filter($filters ?? []))
                    Aucun plan ne correspond à vos critères de recherche.
                @else
                    Créez votre premier plan d'abonnement pour commencer.
                @endif
            </p>
            @if(array_filter($filters ?? []))
                <a href="{{ route('admin.subscriptions.plans.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors mr-2">
                    <i class="fas fa-times"></i> Effacer les filtres
                </a>
            @endif
            <a href="{{ route('admin.subscriptions.plans.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-eco-green-600 hover:bg-eco-green-700 text-white text-sm font-medium rounded-lg transition-colors">
                <i class="fas fa-plus"></i> Nouveau plan
            </a>
        </div>
        @endif
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

        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showDeleteModal = false"></div>

        {{-- Modal box --}}
        <div class="relative z-10 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md p-6"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">

            <div class="flex items-start gap-4 mb-4">
                <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-lg"></i>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Supprimer le plan</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        Vous allez supprimer <strong x-text="'«\u00A0' + deletePlanName + '\u00A0»'" class="text-gray-700 dark:text-gray-300"></strong>
                    </p>
                </div>
            </div>

            <p class="text-sm text-gray-600 dark:text-gray-400 bg-red-50 dark:bg-red-900/20 rounded-lg px-4 py-3 mb-5">
                <i class="fas fa-info-circle text-red-400 mr-1.5"></i>
                Cette action est irréversible. Les abonnements actifs liés à ce plan ne seront pas immédiatement affectés.
            </p>

            <div class="flex gap-3">
                <button @click="showDeleteModal = false"
                        class="flex-1 px-4 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    Annuler
                </button>
                <form :action="'{{ url('admin/subscriptions/plans') }}/' + deletePlanId" method="POST" class="flex-1">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="w-full px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
                        <i class="fas fa-trash mr-1.5"></i> Supprimer définitivement
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
function plansIndex() {
    return {
        showDeleteModal: false,
        deletePlanId: null,
        deletePlanName: '',
        confirmDelete(id, name) {
            this.deletePlanId = id;
            this.deletePlanName = name;
            this.showDeleteModal = true;
        }
    };
}
</script>
@endpush
@endsection
