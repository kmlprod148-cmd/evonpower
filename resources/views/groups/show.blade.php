@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Breadcrumb -->
    <div class="text-gray-500 text-sm mb-4">
        <a href="{{ route('groups.index') }}" class="hover:text-gray-700">Groupes</a> 
        <span class="mx-2">/</span> 
        <span>{{ $group->name }}</span>
    </div>

    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">{{ $group->name }}</h1>
        <div class="flex space-x-3">
            <a href="{{ route('groups.manage-charging-points', $group) }}" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 transition">
                <i class="fas fa-charging-station mr-2"></i> Gérer les bornes
            </a>
            <a href="{{ route('groups.edit', $group) }}" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md text-white bg-yellow-500 hover:bg-yellow-600 transition">
                <i class="fas fa-edit mr-2"></i> Modifier
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Panel -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Group Details -->
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Détails du groupe</h2>
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-gray-700 dark:text-gray-300">
                    <div>
                        <dt class="text-sm font-medium">Type</dt>
                        <dd>
                            <span class="inline-block mt-1 px-2 py-1 text-xs rounded-full {{ $group->type == 'public' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' }}">
                                {{ ucfirst($group->type) }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium">Ville</dt>
                        <dd class="mt-1">{{ $group->city ?? 'Non définie' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium">Adresse</dt>
                        <dd class="mt-1">{{ $group->address ?? 'Non définie' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium">Code postal</dt>
                        <dd class="mt-1">{{ $group->postal_code ?? 'Non défini' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium">Pays</dt>
                        <dd class="mt-1">{{ $group->country ?? 'Non défini' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium">Partenaire</dt>
                        <dd class="mt-1">{{ $group->partner->name ?? 'Non défini' }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Description -->
            @if($group->description)
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-2">Description</h2>
                <p class="text-gray-700 dark:text-gray-300">{{ $group->description }}</p>
            </div>
            @endif

            <!-- Charging Points Table -->
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
                <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Bornes de recharge ({{ $chargingPoints->count() }})</h2>
                    <a href="{{ route('charging-points.create.step1', ['group_id' => $group->id]) }}" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                        <i class="fas fa-plus mr-2"></i> Créer une borne
                    </a>
                </div>
                <div class="overflow-x-auto">
                    @if($chargingPoints->count())
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700 text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            <tr>
                                <th class="px-6 py-3 text-left">Nom</th>
                                <th class="px-6 py-3 text-left">Numéro de série</th>
                                <th class="px-6 py-3 text-left">Statut</th>
                                <th class="px-6 py-3 text-left">Puissance</th>
                                <th class="px-6 py-3 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            @foreach($chargingPoints as $point)
                            <tr>
                                <td class="px-6 py-4">{{ $point->name }}</td>
                                <td class="px-6 py-4">{{ $point->serial_number }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $point->status == 'online' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                        {{ ucfirst($point->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">{{ $point->power_output }} kW</td>
                                <td class="px-6 py-4">
                                    <a href="{{ route('charging-points.show', $point) }}" class="text-blue-600 hover:text-blue-800">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="px-6 py-3">
                        {{ $chargingPoints->links() }}
                    </div>
                    @else
                    <div class="p-6 text-sm text-gray-500 dark:text-gray-400">
                        Aucune borne de recharge n'est associée à ce groupe.
                        <a href="{{ route('groups.manage-charging-points', $group) }}" class="inline-block mt-2 text-blue-600 hover:underline">Ajouter des bornes</a>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right Panel - Stats -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Statistiques du groupe</h3>

                <div class="mb-4">
                    <h4 class="text-sm text-gray-500 dark:text-gray-400 mb-1">Total des bornes</h4>
                    <p class="text-xl text-primary">{{ $stats['total_charging_points'] }}</p>
                </div>

                <div class="mb-4">
                    <h4 class="text-sm text-gray-500 dark:text-gray-400 mb-1">Par statut</h4>
                    @foreach($stats['charging_points_status'] as $status => $count)
                    <div class="flex justify-between items-center text-sm mb-1">
                        <span>{{ ucfirst($status) }}</span>
                        <span class="px-2 py-0.5 rounded-full bg-{{ $status == 'online' ? 'green' : 'gray' }}-200 text-{{ $status == 'online' ? 'green' : 'gray' }}-800 dark:bg-{{ $status == 'online' ? 'green' : 'gray' }}-900 dark:text-{{ $status == 'online' ? 'green' : 'gray' }}-200">
                            {{ $count }}
                        </span>
                    </div>
                    @endforeach
                </div>

                <div>
                    <h4 class="text-sm text-gray-500 dark:text-gray-400 mb-1">Puissance totale</h4>
                    <p class="text-lg text-primary">{{ $stats['total_power_output'] }} kW</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
