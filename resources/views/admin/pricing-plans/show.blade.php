@extends('layouts.app')

@section('title', 'Plan : ' . $pricingPlan->name)
@section('page-title', $pricingPlan->name)

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.pricing-plans.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div class="w-14 h-14 bg-gradient-to-br from-orange-400 to-amber-500 rounded-xl flex items-center justify-center text-white shadow-lg">
                    <i class="fas fa-tags text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ $pricingPlan->name }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $pricingPlan->main_type_label }}</p>
                    <div class="flex items-center gap-2 mt-1">
                        @if($pricingPlan->is_active)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-xs font-medium rounded-full">
                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Actif
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-xs font-medium rounded-full">
                                <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span> Inactif
                            </span>
                        @endif
                        @if($pricingPlan->is_public)
                            <span class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-xs rounded-full">Public</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.pricing-plans.edit', $pricingPlan->id) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-orange-500 text-white text-sm font-medium rounded-lg hover:bg-orange-600 transition-colors">
                    <i class="fas fa-edit"></i> Modifier
                </a>
                <form method="POST" action="{{ route('admin.pricing-plans.toggle-active', $pricingPlan->id) }}" class="inline">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2 {{ $pricingPlan->is_active ? 'bg-gray-100 text-gray-700 hover:bg-gray-200' : 'bg-green-100 text-green-700 hover:bg-green-200' }} text-sm font-medium rounded-lg transition-colors">
                        <i class="fas {{ $pricingPlan->is_active ? 'fa-pause' : 'fa-play' }}"></i>
                        {{ $pricingPlan->is_active ? 'Désactiver' : 'Activer' }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-400 p-4 rounded-lg flex items-center gap-2">
            <i class="fas fa-check-circle text-green-600"></i>
            <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Prix --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <i class="fas fa-euro-sign text-orange-500"></i> Configuration tarifaire
            </h3>

            <div class="text-center mb-4 p-4 bg-orange-50 dark:bg-orange-900/20 rounded-xl">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Prix principal</p>
                <p class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ $pricingPlan->formatted_main_value ?? '—' }}</p>
            </div>

            <dl class="space-y-3 text-sm">
                <div><dt class="text-gray-500 dark:text-gray-400">Type</dt><dd class="font-medium text-gray-900 dark:text-white">{{ $pricingPlan->main_type_label }}</dd></div>
                @if($pricingPlan->price_per_minute)
                    <div><dt class="text-gray-500 dark:text-gray-400">Prix/min</dt><dd class="font-medium text-gray-900 dark:text-white">{{ number_format($pricingPlan->price_per_minute, 4) }} {{ $pricingPlan->currency }}</dd></div>
                @endif
                @if($pricingPlan->price_per_kwh)
                    <div><dt class="text-gray-500 dark:text-gray-400">Prix/kWh</dt><dd class="font-medium text-gray-900 dark:text-white">{{ number_format($pricingPlan->price_per_kwh, 4) }} {{ $pricingPlan->currency }}</dd></div>
                @endif
                @if($pricingPlan->fixed_price)
                    <div><dt class="text-gray-500 dark:text-gray-400">Prix fixe</dt><dd class="font-medium text-gray-900 dark:text-white">{{ number_format($pricingPlan->fixed_price, 2) }} {{ $pricingPlan->currency }}</dd></div>
                @endif
                @if($pricingPlan->activation_fee)
                    <div><dt class="text-gray-500 dark:text-gray-400">Frais activation</dt><dd class="font-medium text-gray-900 dark:text-white">{{ number_format($pricingPlan->activation_fee, 2) }} {{ $pricingPlan->currency }}</dd></div>
                @endif
                <div><dt class="text-gray-500 dark:text-gray-400">TVA</dt><dd class="font-medium text-gray-900 dark:text-white">{{ optional($pricingPlan->vatRate)->name ?? 'Sans TVA' }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">Devise</dt><dd class="font-medium text-gray-900 dark:text-white">{{ $pricingPlan->currency ?? 'MAD' }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">Priorité</dt><dd class="font-medium text-gray-900 dark:text-white">{{ $pricingPlan->priority ?? 0 }}</dd></div>
                @if($pricingPlan->max_duration)
                    <div><dt class="text-gray-500 dark:text-gray-400">Durée max</dt><dd class="font-medium text-gray-900 dark:text-white">{{ $pricingPlan->max_duration }} min</dd></div>
                @endif
                @if($pricingPlan->valid_from)
                    <div><dt class="text-gray-500 dark:text-gray-400">Valide du</dt><dd class="font-medium text-gray-900 dark:text-white">{{ $pricingPlan->valid_from->format('d/m/Y') }} → {{ optional($pricingPlan->valid_until)->format('d/m/Y') ?? '∞' }}</dd></div>
                @endif
            </dl>
        </div>

        {{-- Groupes et bornes --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Groupes --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-layer-group text-emerald-500"></i> Groupes utilisant ce plan ({{ $pricingPlan->groups->count() }})
                </h3>
                @if($pricingPlan->groups->isEmpty())
                    <p class="text-sm text-gray-400 text-center py-4">Aucun groupe associé</p>
                @else
                    <div class="space-y-2">
                        @foreach($pricingPlan->groups->take(10) as $group)
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $group->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ optional($group->partner)->name ?? 'Admin' }} · {{ $group->city ?? '' }}</p>
                                </div>
                                <a href="{{ route('admin.groups.show', $group->id) }}" class="text-xs text-emerald-600 hover:underline">Voir</a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Tarifs additionnels --}}
            @if($pricingPlan->additionalRates->isNotEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-clock text-blue-500"></i> Tarifs de plage horaire ({{ $pricingPlan->additionalRates->count() }})
                </h3>
                <div class="space-y-2">
                    @foreach($pricingPlan->additionalRates as $rate)
                        <div class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $rate->name ?? 'Tarif heure de pointe' }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $rate->start_time ?? '' }} — {{ $rate->end_time ?? '' }}</p>
                            </div>
                            <span class="text-sm font-bold text-blue-600 dark:text-blue-400">
                                +{{ number_format($rate->price ?? 0, 2) }} {{ $pricingPlan->currency }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

    </div>
</div>
@endsection
