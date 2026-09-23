@extends('layouts.app')

@section('title', 'Paramètres - Plans de Commission')

@section('content')
<div class="px-4 py-6">
    <div class="flex flex-wrap">
        <!-- Sidebar de navigation des paramètres -->
        <div class="w-full md:w-1/4 lg:w-1/5 pr-4">
            <div class="bg-white rounded-lg shadow-md p-4 mb-4">
                <h2 class="text-lg font-semibold mb-4">Paramètres</h2>
                <div class="flex flex-col space-y-2">
                    @include('partials.settings-submenu')
                </div>
            </div>
        </div>

        <!-- Contenu principal -->
        <div class="w-full md:w-3/4 lg:w-4/5">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold">Plans de Commission</h1>
                    <a href="{{ route('plans.create') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                        Nouveau Plan
                    </a>
                </div>

                @include('partials.flash-messages')

                <div class="bg-white rounded-lg overflow-hidden">
                    <div class="p-4 border-b">
                        <form action="{{ route('settings.commission-plans') }}" method="GET" class="flex flex-wrap gap-4">
                            <div class="flex-1 min-w-[200px]">
                                <label for="is_active" class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                                <select name="is_active" id="is_active" class="w-full rounded border-gray-300">
                                    <option value="">Tous</option>
                                    <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Actifs</option>
                                    <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactifs</option>
                                </select>
                            </div>
                            <div class="flex-1 min-w-[200px]">
                                <label for="applies_to_type" class="block text-sm font-medium text-gray-700 mb-1">Type d'application</label>
                                <select name="applies_to_type" id="applies_to_type" class="w-full rounded border-gray-300">
                                    <option value="">Tous</option>
                                    <option value="global" {{ request('applies_to_type') === 'global' ? 'selected' : '' }}>Global</option>
                                    <option value="integrator" {{ request('applies_to_type') === 'integrator' ? 'selected' : '' }}>Intégrateur</option>
                                    <option value="partner" {{ request('applies_to_type') === 'partner' ? 'selected' : '' }}>Partenaire</option>
                                    <option value="group" {{ request('applies_to_type') === 'group' ? 'selected' : '' }}>Groupe</option>
                                </select>
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                                    Filtrer
                                </button>
                            </div>
                        </form>
                    </div>

                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Commissions (%)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Application</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priorité</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($plans as $plan)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $plan->name }}
                                                @if($plan->is_default)
                                                <span class="ml-2 px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Par défaut</span>
                                                @endif
                                            </div>
                                            <div class="text-sm text-gray-500">{{ Str::limit($plan->description, 50) }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">Admin: {{ $plan->admin_percentage }}%</div>
                                    <div class="text-sm text-gray-900">Intégrateur: {{ $plan->integrator_percentage }}%</div>
                                    <div class="text-sm text-gray-900">Partenaire: {{ $plan->partner_percentage }}%</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        @if($plan->applies_to_type === 'global')
                                            Global
                                        @elseif($plan->applies_to_type === 'integrator')
                                            Intégrateur: {{ optional($plan->appliesTo)->name ?? 'N/A' }}
                                        @elseif($plan->applies_to_type === 'partner')
                                            Partenaire: {{ optional($plan->appliesTo)->name ?? 'N/A' }}
                                        @elseif($plan->applies_to_type === 'group')
                                            Groupe: {{ optional($plan->appliesTo)->name ?? 'N/A' }}
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($plan->is_active)
                                        <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Actif</span>
                                    @else
                                        <span class="px-2 py-1 text-xs bg-red-100 text-red-800 rounded-full">Inactif</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $plan->priority }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="{{ route('commission-plans.show', $plan) }}" class="text-blue-600 hover:text-blue-900">Voir</a>
                                        <a href="{{ route('commission-plans.edit', $plan) }}" class="text-indigo-600 hover:text-indigo-900">Modifier</a>
                                        
                                        <form action="{{ route('commission-plans.toggle-active', $plan) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="text-yellow-600 hover:text-yellow-900">
                                                {{ $plan->is_active ? 'Désactiver' : 'Activer' }}
                                            </button>
                                        </form>
                                        
                                        @if(!$plan->is_default)
                                        <form action="{{ route('commission-plans.set-default', $plan) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="text-green-600 hover:text-green-900">
                                                Définir par défaut
                                            </button>
                                        </form>
                                        @endif
                                        
                                        <form action="{{ route('commission-plans.destroy', $plan) }}" method="POST" class="inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce plan?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">Supprimer</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                    Aucun plan de commission trouvé.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                    
                    <div class="p-4">
                        {{ $plans->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection