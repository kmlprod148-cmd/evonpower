@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Rapports de commissions</h1>
        <a href="{{ route('commissions.dashboard') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
            Retour au tableau de bord
        </a>
    </div>

    @include('partials.flash-messages')

    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form action="{{ route('commissions.reports') }}" method="GET">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700">Date de début</label>
                    <input type="date" name="start_date" id="start_date" value="{{ $startDate }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                </div>
                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700">Date de fin</label>
                    <input type="date" name="end_date" id="end_date" value="{{ $endDate }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                </div>
                <div>
                    <label for="group_by" class="block text-sm font-medium text-gray-700">Regrouper par</label>
                    <select name="group_by" id="group_by" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        <option value="day" @if($groupBy == 'day') selected @endif>Jour</option>
                        <option value="week" @if($groupBy == 'week') selected @endif>Semaine</option>
                        <option value="month" @if($groupBy == 'month') selected @endif>Mois</option>
                        <option value="integrator" @if($groupBy == 'integrator') selected @endif>Intégrateur</option>
                        <option value="partner" @if($groupBy == 'partner') selected @endif>Partenaire</option>
                        <option value="commission_plan" @if($groupBy == 'commission_plan') selected @endif>Plan de commission</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="mt-7 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                        Filtrer
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-4 border-b">
            <h2 class="text-lg font-semibold">Détails du rapport</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ ucfirst($groupBy) }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Transactions
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Montant Total
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Commission Admin
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Commission Intégrateur
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Commission Opérateur (Partenaire)
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Total Commissions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($report['details'] as $key => $data)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $key }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $data['total_transactions'] }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ number_format($data['total_amount'], 2) }} €</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ number_format($data['commissions']['admin'], 2) }} €</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ number_format($data['commissions']['integrator'], 2) }} €</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ number_format($data['commissions']['partner'], 2) }} €</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ number_format($data['commissions']['total'], 2) }} €</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 whitespace-nowrap text-center text-gray-500">
                            Aucune donnée disponible pour la période sélectionnée.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection