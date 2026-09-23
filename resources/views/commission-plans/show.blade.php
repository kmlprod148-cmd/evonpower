@extends('layouts.app')

@section('title', 'Détails du Plan de Commission')

@section('content')
<div class="px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">{{ $commissionPlan->name }}</h1>
        <div class="flex space-x-2">
            <a href="{{ route('admin.commission-plans.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                Retour à la liste
            </a>
            <a href="{{ route('admin.commission-plans.edit', $commissionPlan) }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                Modifier
            </a>
        </div>
    </div>

    @include('partials.flash-messages')

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <!-- Informations générales -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-4 bg-blue-50 border-b border-blue-100">
                <h2 class="text-lg font-semibold text-blue-800">Informations générales</h2>
            </div>
            <div class="p-4">
                <div class="flex justify-between mb-2">
                    <span class="text-gray-600">Statut:</span>
                    <span class="font-semibold">
                        @if($commissionPlan->is_active)
                            <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Actif</span>
                        @else
                            <span class="px-2 py-1 text-xs bg-red-100 text-red-800 rounded-full">Inactif</span>
                        @endif
                    </span>
                </div>
                <div class="flex justify-between mb-2">
                    <span class="text-gray-600">Plan par défaut:</span>
                    <span class="font-semibold">
                        @if($commissionPlan->is_default)
                            <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Oui</span>
                        @else
                            <span class="px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded-full">Non</span>
                        @endif
                    </span>
                </div>
                <div class="flex justify-between mb-2">
                    <span class="text-gray-600">Priorité:</span>
                    <span class="font-semibold">{{ $commissionPlan->priority }}</span>
                </div>
                <div class="flex justify-between mb-2">
                    <span class="text-gray-600">Créé le:</span>
                    <span class="font-semibold">{{ $commissionPlan->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <div class="flex justify-between mb-2">
                    <span class="text-gray-600">Dernière mise à jour:</span>
                    <span class="font-semibold">{{ $commissionPlan->updated_at->format('d/m/Y H:i') }}</span>
                </div>
                @if($commissionPlan->valid_from || $commissionPlan->valid_until)
                <div class="mt-4 pt-4 border-t">
                    <div class="flex justify-between mb-2">
                        <span class="text-gray-600">Valide du:</span>
                        <span class="font-semibold">{{ $commissionPlan->valid_from ? $commissionPlan->valid_from->format('d/m/Y') : 'Indéfini' }}</span>
                    </div>
                    <div class="flex justify-between mb-2">
                        <span class="text-gray-600">Valide jusqu'au:</span>
                        <span class="font-semibold">{{ $commissionPlan->valid_until ? $commissionPlan->valid_until->format('d/m/Y') : 'Indéfini' }}</span>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Pourcentages de commission -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-4 bg-green-50 border-b border-green-100">
                <h2 class="text-lg font-semibold text-green-800">Pourcentages de commission</h2>
            </div>
            <div class="p-4">
                <div class="flex justify-between mb-2">
                    <span class="text-gray-600">Admin:</span>
                    <span class="font-semibold">{{ $commissionPlan->admin_percentage }}%</span>
                </div>
                <div class="flex justify-between mb-2">
                    <span class="text-gray-600">Intégrateur:</span>
                    <span class="font-semibold">{{ $commissionPlan->integrator_percentage }}%</span>
                </div>
                <div class="flex justify-between mb-2">
                    <span class="text-gray-600">Partenaire:</span>
                    <span class="font-semibold">{{ $commissionPlan->partner_percentage }}%</span>
                </div>
                <div class="flex justify-between mb-2 pt-2 border-t">
                    <span class="text-gray-600">Total:</span>
                    <span class="font-semibold">{{ $commissionPlan->admin_percentage + $commissionPlan->integrator_percentage + $commissionPlan->partner_percentage }}%</span>
                </div>
            </div>
        </div>

        <!-- Conditions d'application -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-4 bg-yellow-50 border-b border-yellow-100">
                <h2 class="text-lg font-semibold text-yellow-800">Conditions d'application</h2>
            </div>
            <div class="p-4">
                <div class="flex justify-between mb-2">
                    <span class="text-gray-600">Type d'application:</span>
                    <span class="font-semibold">
                        @if($commissionPlan->applies_to_type === 'global')
                            Global
                        @elseif($commissionPlan->applies_to_type === 'integrator')
                            Intégrateur
                        @elseif($commissionPlan->applies_to_type === 'partner')
                            Partenaire
                        @elseif($commissionPlan->applies_to_type === 'group')
                            Groupe
                        @endif
                    </span>
                </div>
                
                @if($commissionPlan->applies_to_type !== 'global')
                <div class="flex justify-between mb-2">
                    <span class="text-gray-600">Appliqué à:</span>
                    <span class="font-semibold">
                        {{ optional($commissionPlan->appliesTo)->name ?? 'N/A' }}
                    </span>
                </div>
                @endif
                
                <div class="flex justify-between mb-2">
                    <span class="text-gray-600">Valeur min. transaction:</span>
                    <span class="font-semibold">
                        {{ $commissionPlan->min_transaction_value ? number_format($commissionPlan->min_transaction_value, 2) . ' €' : 'Aucune' }}
                    </span>
                </div>
                
                <div class="flex justify-between mb-2">
                    <span class="text-gray-600">Valeur max. transaction:</span>
                    <span class="font-semibold">
                        {{ $commissionPlan->max_transaction_value ? number_format($commissionPlan->max_transaction_value, 2) . ' €' : 'Aucune' }}
                    </span>
                </div>
                
                @if($commissionPlan->exclusions)
                <div class="mt-4 pt-4 border-t">
                    <span class="text-gray-600 block mb-2">Exclusions:</span>
                    <ul class="list-disc list-inside text-sm">
                        @foreach($commissionPlan->exclusions as $key => $value)
                            @if(is_array($value))
                                <li class="mb-1">
                                    <span class="font-semibold">{{ str_replace('_', ' ', $key) }}:</span>
                                    <ul class="list-disc list-inside ml-4">
                                        @foreach($value as $item)
                                            <li>{{ $item }}</li>
                                        @endforeach
                                    </ul>
                                </li>
                            @else
                                <li class="mb-1">
                                    <span class="font-semibold">{{ str_replace('_', ' ', $key) }}:</span> {{ $value }}
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Description -->
    @if($commissionPlan->description)
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="p-4 border-b">
            <h2 class="text-lg font-semibold">Description</h2>
        </div>
        <div class="p-4">
            <p class="text-gray-700">{{ $commissionPlan->description }}</p>
        </div>
    </div>
    @endif

    <!-- Transactions associées -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-4 border-b flex justify-between items-center">
            <h2 class="text-lg font-semibold">Transactions associées</h2>
            <form action="{{ route('admin.commission-plans.recalculate', $commissionPlan) }}" method="POST" class="inline">
                @csrf
                <input type="hidden" name="apply_to" value="plan_transactions">
                <button type="submit" class="text-blue-500 hover:text-blue-700">
                    Recalculer les commissions
                </button>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Point de charge</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Commissions</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($transactions as $transaction)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $transaction->id }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $transaction->start_timestamp ? \Carbon\Carbon::parse($transaction->start_timestamp)->format('d/m/Y H:i') : 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ optional($transaction->chargingPoint)->name ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ number_format($transaction->price_total, 2) }} €
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div class="flex items-center">
                                <span class="w-2 h-2 rounded-full {{ $transaction->admin_commission_paid ? 'bg-green-500' : 'bg-red-500' }} mr-1"></span>
                                <span class="text-gray-900">Admin: {{ number_format($transaction->admin_commission, 2) }} €</span>
                            </div>
                            <div class="flex items-center">
                                <span class="w-2 h-2 rounded-full {{ $transaction->integrator_commission_paid ? 'bg-green-500' : 'bg-red-500' }} mr-1"></span>
                                <span class="text-gray-900">Int.: {{ number_format($transaction->integrator_commission, 2) }} €</span>
                            </div>
                            <div class="flex items-center">
                                <span class="w-2 h-2 rounded-full {{ $transaction->partner_commission_paid ? 'bg-green-500' : 'bg-red-500' }} mr-1"></span>
                                <span class="text-gray-900">Part.: {{ number_format($transaction->partner_commission, 2) }} €</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($transaction->areAllCommissionsPaid())
                                <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Payée</span>
                            @else
                                <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded-full">En attente</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="{{ route('commissions.show', $transaction) }}" class="text-blue-600 hover:text-blue-900">Détails</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                            Aucune transaction n'utilise ce plan de commission.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="p-4">
            {{ $transactions->links() }}
        </div>
    </div>
</div>
@endsection