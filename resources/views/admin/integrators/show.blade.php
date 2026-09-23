@extends('layouts.app')

@section('title', 'Intégrateur : ' . $integrator->name)
@section('page-title', $integrator->name)

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.integrators.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div class="w-14 h-14 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center text-white text-2xl font-bold shadow-lg">
                    {{ substr($integrator->name, 0, 1) }}
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ $integrator->name }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $integrator->email }}</p>
                    <div class="flex items-center gap-2 mt-1">
                        @if($integrator->is_active)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-xs font-medium rounded-full">
                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Actif
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-xs font-medium rounded-full">
                                <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span> Inactif
                            </span>
                        @endif
                        @if($integrator->city)
                            <span class="text-xs text-gray-400 dark:text-gray-500"><i class="fas fa-map-marker-alt mr-0.5"></i>{{ $integrator->city }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.integrators.edit', $integrator->id) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                    <i class="fas fa-edit"></i> Modifier
                </a>
                <form method="POST" action="{{ route('admin.integrators.toggle-active', $integrator->id) }}" class="inline">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2 {{ $integrator->is_active ? 'bg-orange-100 text-orange-700 hover:bg-orange-200' : 'bg-green-100 text-green-700 hover:bg-green-200' }} text-sm font-medium rounded-lg transition-colors">
                        <i class="fas {{ $integrator->is_active ? 'fa-ban' : 'fa-check' }}"></i>
                        {{ $integrator->is_active ? 'Désactiver' : 'Activer' }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-400 p-4 rounded-lg flex items-center gap-2">
            <i class="fas fa-check-circle text-green-600"></i>
            <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
        </div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        @php $statCards = [
            ['label' => 'Partenaires', 'value' => $stats['partners_count'], 'icon' => 'fa-handshake', 'color' => 'blue'],
            ['label' => 'Partenaires actifs', 'value' => $stats['active_partners'], 'icon' => 'fa-check', 'color' => 'green'],
            ['label' => 'Bornes', 'value' => $stats['charging_points'], 'icon' => 'fa-charging-station', 'color' => 'emerald'],
            ['label' => 'Bornes actives', 'value' => $stats['active_charging_points'], 'icon' => 'fa-bolt', 'color' => 'yellow'],
            ['label' => 'Solde wallet', 'value' => number_format($stats['wallet_balance'], 2) . ' ' . ($integrator->currency ?? 'EUR'), 'icon' => 'fa-wallet', 'color' => 'purple'],
        ]; @endphp
        @foreach($statCards as $card)
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $card['value'] }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $card['label'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Contact info --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <i class="fas fa-info-circle text-indigo-500"></i> Informations
            </h3>
            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Nom</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $integrator->name }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Contact</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $integrator->contact_name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Email</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">
                        <a href="mailto:{{ $integrator->email }}" class="text-indigo-600 hover:underline">{{ $integrator->email }}</a>
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Téléphone</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $integrator->phone ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Adresse</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">
                        {{ collect([$integrator->address, $integrator->city, $integrator->country])->filter()->implode(', ') ?: '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Collecte financière</dt>
                    <dd class="font-medium text-gray-900 dark:text-white capitalize">{{ $integrator->collection_mode ?? 'admin' }}</dd>
                </div>
                @if($integrator->website)
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Site web</dt>
                        <dd><a href="{{ $integrator->website }}" target="_blank" class="text-indigo-600 hover:underline text-sm">{{ $integrator->website }}</a></dd>
                    </div>
                @endif
            </dl>
        </div>

        {{-- Partners list --}}
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-handshake text-blue-500"></i> Partenaires ({{ $integrator->partners->count() }})
                </h3>
                <a href="{{ route('admin.partners.create') }}" class="text-xs text-indigo-600 hover:underline">
                    <i class="fas fa-plus mr-0.5"></i> Ajouter
                </a>
            </div>
            @if($integrator->partners->isEmpty())
                <div class="text-center py-8 text-gray-400 dark:text-gray-500">
                    <i class="fas fa-handshake text-3xl mb-2 opacity-30"></i>
                    <p class="text-sm">Aucun partenaire associé</p>
                </div>
            @else
                <div class="space-y-2 max-h-72 overflow-y-auto">
                    @foreach($integrator->partners->take(15) as $partner)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/40 rounded-full flex items-center justify-center text-blue-700 dark:text-blue-300 font-semibold text-xs">
                                    {{ substr($partner->name, 0, 1) }}
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $partner->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $partner->city ?? '' }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs {{ $partner->is_active ? 'text-green-600' : 'text-gray-400' }}">
                                    <i class="fas fa-circle text-[8px]"></i>
                                </span>
                                <a href="{{ route('admin.partners.show', $partner->id) }}" class="text-xs text-indigo-600 hover:underline">Voir</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
