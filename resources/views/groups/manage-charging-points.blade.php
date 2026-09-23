@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Breadcrumb -->
    <div class="text-gray-500 text-sm mb-4">
        <a href="{{ route('groups.index') }}" class="hover:text-gray-700">Groupes</a>
        <span class="mx-2">/</span>
        <a href="{{ route('groups.show', $group) }}" class="hover:text-gray-700">{{ $group->name }}</a>
        <span class="mx-2">/</span>
        <span>Gérer les bornes</span>
    </div>

    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">
            Gérer les bornes de recharge – {{ $group->name }}
        </h1>
        <a href="{{ route('charging-points.create.step1', ['group_id' => $group->id]) }}" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 transition">
            <i class="fas fa-plus mr-2"></i> Ajouter une borne
        </a>
    </div>

    <!-- Charging Points List -->
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Liste des bornes</h2>

        @if($chargingPoints->count())
        <div class="overflow-x-auto">
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
                    @foreach ($chargingPoints as $chargingPoint)
                    <tr>
                        <td class="px-6 py-4">{{ $chargingPoint->name }}</td>
                        <td class="px-6 py-4">{{ $chargingPoint->serial_number }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $chargingPoint->status == 'online' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                {{ ucfirst($chargingPoint->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">{{ $chargingPoint->power_output }} kW</td>
                        <td class="px-6 py-4 space-x-2">
                            <a href="{{ route('charging-points.show', $chargingPoint) }}" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('charging-points.edit', $chargingPoint) }}" class="text-yellow-500 hover:text-yellow-600">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('charging-points.destroy', $chargingPoint) }}" method="POST" class="inline-block" onsubmit="return confirm('Supprimer cette borne ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $chargingPoints->links() }}
        </div>
        @else
        <div class="p-4 text-gray-600 dark:text-gray-300">
            Aucune borne de recharge disponible pour ce groupe.
        </div>
        @endif
    </div>
</div>
@endsection
