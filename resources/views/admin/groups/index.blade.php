@extends('layouts.app')

@section('title', 'Gestion des Groupes')
@section('page-title', 'Groupes de Bornes')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="bg-gradient-to-r from-emerald-600 to-teal-600 rounded-xl p-6 text-white shadow-lg">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold flex items-center gap-2 text-green-800">
                    <i class="fas fa-layer-group"></i> Groupes de Bornes
                </h1>
                <p class="text-green-800 text-sm mt-1">Gérez les groupes regroupant les bornes et stations de recharge</p>
            </div>
            <a href="{{ route('admin.groups.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-white text-emerald-700 font-semibold rounded-lg hover:bg-emerald-50 transition-colors shadow-sm">
                <i class="fas fa-plus"></i> Ajouter un groupe
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

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 flex items-center gap-3">
            <div class="w-11 h-11 bg-emerald-100 dark:bg-emerald-900/40 rounded-xl flex items-center justify-center">
                <i class="fas fa-layer-group text-emerald-600 dark:text-emerald-400"></i>
            </div>
            <div><p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Total groupes</p></div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 flex items-center gap-3">
            <div class="w-11 h-11 bg-blue-100 dark:bg-blue-900/40 rounded-xl flex items-center justify-center">
                <i class="fas fa-building text-blue-600 dark:text-blue-400"></i>
            </div>
            <div><p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['business'] }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Business</p></div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 flex items-center gap-3">
            <div class="w-11 h-11 bg-purple-100 dark:bg-purple-900/40 rounded-xl flex items-center justify-center">
                <i class="fas fa-home text-purple-600 dark:text-purple-400"></i>
            </div>
            <div><p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['private'] }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Privés</p></div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 flex items-center gap-3">
            <div class="w-11 h-11 bg-orange-100 dark:bg-orange-900/40 rounded-xl flex items-center justify-center">
                <i class="fas fa-charging-station text-orange-600 dark:text-orange-400"></i>
            </div>
            <div><p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_points'] }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Bornes dans groupes</p></div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-48">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Recherche</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nom, ville..."
                       class="w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Type</label>
                <select name="type" class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                    <option value="">Tous</option>
                    <option value="business" @selected(request('type') === 'business')>Business</option>
                    <option value="private" @selected(request('type') === 'private')>Privé</option>
                </select>
            </div>
            @if($partners->isNotEmpty())
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Partenaire</label>
                <select name="partner_id" class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                    <option value="">Tous</option>
                    @foreach($partners as $p)
                        <option value="{{ $p->id }}" @selected(request('partner_id') == $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <button type="submit" class="px-4 py-2 bg-emerald-600 text-white text-sm rounded-lg hover:bg-emerald-700 transition-colors">
                <i class="fas fa-search mr-1"></i> Filtrer
            </button>
            <a href="{{ route('admin.groups.index') }}" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm rounded-lg hover:bg-gray-200 transition-colors">
                <i class="fas fa-times mr-1"></i> Réinitialiser
            </a>
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Groupe</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Propriétaire</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Plans tarifaires</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Bornes</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($groups as $group)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-4 py-3">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white text-sm">{{ $group->name }}</p>
                                    @if($group->city)
                                        <p class="text-xs text-gray-500 dark:text-gray-400"><i class="fas fa-map-marker-alt mr-0.5"></i>{{ $group->city }}</p>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 {{ $group->type === 'business' ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' : 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300' }} text-xs font-medium rounded-full">
                                    {{ $group->type === 'business' ? 'Business' : 'Privé' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @if($group->partner)
                                    <a href="{{ route('admin.partners.show', $group->partner_id) }}" class="text-blue-600 hover:underline text-sm">
                                        {{ $group->partner->name }}
                                    </a>
                                @elseif($group->integrator)
                                    <a href="{{ route('admin.integrators.show', $group->integrator_id) }}" class="text-indigo-600 hover:underline text-sm">
                                        {{ $group->integrator->name }}
                                    </a>
                                @else
                                    <span class="text-gray-400">Admin</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @foreach($group->pricingPlans->take(2) as $plan)
                                    <span class="inline-flex items-center px-2 py-0.5 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 text-xs rounded-full mr-1">
                                        {{ Str::limit($plan->name, 20) }}
                                    </span>
                                @endforeach
                                @if($group->pricingPlans->count() > 2)
                                    <span class="text-xs text-gray-400">+{{ $group->pricingPlans->count() - 2 }}</span>
                                @endif
                                @if($group->pricingPlans->isEmpty())
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center justify-center w-8 h-8 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded-full text-sm font-semibold">
                                    {{ $group->chargingPoints->count() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.groups.show', $group->id) }}" class="p-1.5 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 rounded-lg transition-colors" title="Voir">
                                        <i class="fas fa-eye text-sm"></i>
                                    </a>
                                    <a href="{{ route('admin.groups.edit', $group->id) }}" class="p-1.5 text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/30 rounded-lg transition-colors" title="Modifier">
                                        <i class="fas fa-edit text-sm"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.groups.destroy', $group->id) }}" onsubmit="return confirm('Supprimer ce groupe ?')" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors" title="Supprimer">
                                            <i class="fas fa-trash text-sm"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                <i class="fas fa-layer-group text-4xl mb-3 opacity-30"></i>
                                <p class="text-sm">Aucun groupe trouvé.</p>
                                <a href="{{ route('admin.groups.create') }}" class="mt-2 inline-flex items-center text-emerald-600 hover:underline text-sm">
                                    <i class="fas fa-plus mr-1"></i> Créer le premier groupe
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($groups->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $groups->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
