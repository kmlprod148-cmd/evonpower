@extends('layouts.app')

@section('title', 'Partenaire : ' . $partner->name)
@section('page-title', $partner->name)

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.partners.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl flex items-center justify-center text-white text-2xl font-bold shadow-lg">
                    {{ substr($partner->name, 0, 1) }}
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ $partner->name }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $partner->email }}</p>
                    <div class="flex items-center gap-2 mt-1">
                        @php $typeLabels = ['partner' => 'Partenaire', 'proprietaire' => 'Propriétaire', 'exploitant' => 'Exploitant']; @endphp
                        <span class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-xs rounded-full">
                            {{ $typeLabels[$partner->type] ?? $partner->type }}
                        </span>
                        @if($partner->is_active)
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
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.partners.edit', $partner->id) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-edit"></i> Modifier
                </a>
                <form method="POST" action="{{ route('admin.partners.toggle-active', $partner->id) }}" class="inline">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2 {{ $partner->is_active ? 'bg-orange-100 text-orange-700 hover:bg-orange-200' : 'bg-green-100 text-green-700 hover:bg-green-200' }} text-sm font-medium rounded-lg transition-colors">
                        <i class="fas {{ $partner->is_active ? 'fa-ban' : 'fa-check' }}"></i>
                        {{ $partner->is_active ? 'Désactiver' : 'Activer' }}
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

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @php $statCards = [
            ['label' => 'Groupes', 'value' => $stats['groups_count'], 'icon' => 'fa-layer-group', 'color' => 'blue'],
            ['label' => 'Bornes totales', 'value' => $stats['charging_points'], 'icon' => 'fa-charging-station', 'color' => 'emerald'],
            ['label' => 'Bornes actives', 'value' => $stats['active_points'], 'icon' => 'fa-bolt', 'color' => 'green'],
            ['label' => 'Solde wallet', 'value' => number_format($stats['wallet_balance'], 2) . ' ' . ($partner->currency ?? 'MAD'), 'icon' => 'fa-wallet', 'color' => 'purple'],
        ]; @endphp
        @foreach($statCards as $card)
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $card['value'] }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $card['label'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Info card --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <i class="fas fa-info-circle text-blue-500"></i> Coordonnées
            </h3>
            <dl class="space-y-3 text-sm">
                <div><dt class="text-gray-500 dark:text-gray-400">Raison sociale</dt><dd class="font-medium text-gray-900 dark:text-white">{{ $partner->name }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">Contact</dt><dd class="font-medium text-gray-900 dark:text-white">{{ $partner->contact_name ?? '—' }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">Email</dt><dd><a href="mailto:{{ $partner->email }}" class="text-blue-600 hover:underline">{{ $partner->email }}</a></dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">Téléphone</dt><dd class="font-medium text-gray-900 dark:text-white">{{ $partner->phone ?? '—' }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">Adresse</dt><dd class="font-medium text-gray-900 dark:text-white">{{ collect([$partner->address, $partner->city, $partner->country])->filter()->implode(', ') ?: '—' }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">Intégrateur</dt><dd class="font-medium text-gray-900 dark:text-white">
                    @if($partner->integrator)
                        <a href="{{ route('admin.integrators.show', $partner->integrator_id) }}" class="text-blue-600 hover:underline">{{ $partner->integrator->name }}</a>
                    @else
                        <span class="text-gray-400">Autonome</span>
                    @endif
                </dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">Devise</dt><dd class="font-medium text-gray-900 dark:text-white">{{ $partner->currency ?? 'MAD' }}</dd></div>
                <div><dt class="text-gray-500 dark:text-gray-400">Collecte</dt><dd class="font-medium text-gray-900 dark:text-white capitalize">{{ $partner->collection_mode ?? 'admin' }}</dd></div>
            </dl>
        </div>

        {{-- Groups --}}
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-layer-group text-blue-500"></i> Groupes de bornes ({{ $partner->groups->count() }})
                </h3>
                <a href="{{ route('admin.groups.create') }}" class="text-xs text-blue-600 hover:underline">
                    <i class="fas fa-plus mr-0.5"></i> Créer un groupe
                </a>
            </div>
            @if($partner->groups->isEmpty())
                <div class="text-center py-8 text-gray-400 dark:text-gray-500">
                    <i class="fas fa-layer-group text-3xl mb-2 opacity-30"></i>
                    <p class="text-sm">Aucun groupe associé</p>
                </div>
            @else
                <div class="space-y-2 max-h-72 overflow-y-auto">
                    @foreach($partner->groups as $group)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $group->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $group->chargingPoints->count() }} bornes · {{ ucfirst($group->type ?? '—') }}
                                </p>
                            </div>
                            <a href="{{ route('admin.groups.show', $group->id) }}" class="text-xs text-blue-600 hover:underline">Voir</a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
</div>
@endsection
