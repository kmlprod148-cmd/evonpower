@extends('layouts.app')

@section('title', 'Détails de la Transaction')

@section('content')
<div class="px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Transaction #{{ $transaction->id }}</h1>
        <div class="flex space-x-2">
            <a href="{{ route('commissions.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                Retour à la liste
            </a>
            <form action="{{ route('commissions.recalculate', $transaction) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                    Recalculer les commissions
                </button>
            </form>
        </div>
    </div>

    @include('partials.flash-messages')

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Informations de la transaction -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-4 bg-blue-50 border-b border-blue-100">
                <h2 class="text-lg font-semibold text-blue-800">Informations de la transaction</h2>
            </div>
            <div class="p-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">ID de transaction</p>
                        <p class="font-semibold">{{ $transaction->id }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">ID externe</p>
                        <p class="font-semibold">{{ $transaction->transaction_id ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Date de début</p>
                        <p class="font-semibold">{{ $transaction->start_timestamp ? \Carbon\Carbon::parse($transaction->start_timestamp)->format('d/m/Y H:i') : 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Date de fin</p>
                        <p class="font-semibold">{{ $transaction->stop_timestamp ? \Carbon\Carbon::parse($transaction->stop_timestamp)->format('d/m/Y H:i') : 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Durée</p>
                        <p class="font-semibold">{{ $transaction->duration ? gmdate('H:i:s', $transaction->duration) : 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Énergie délivrée</p>
                        <p class="font-semibold">{{ $transaction->energy_delivered ? number_format($transaction->energy_delivered, 2) . ' kWh' : 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Montant total</p>
                        <p class="font-semibold">{{ number_format($transaction->price_total, 2) }} €</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Statut de paiement</p>
                        <p class="font-semibold">{{ $transaction->payment_status ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informations du point de charge -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-4 bg-green-50 border-b border-green-100">
                <h2 class="text-lg font-semibold text-green-800">Point de charge</h2>
            </div>
            <div class="p-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Point de charge</p>
                        <p class="font-semibold">{{ optional($transaction->chargingPoint)->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Connecteur</p>
                        <p class="font-semibold">{{ optional($transaction->connector)->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Intégrateur</p>
                        <p class="font-semibold">{{ optional($transaction->chargingPoint)->integrator->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Partenaire</p>
                        <p class="font-semibold">{{ optional($transaction->chargingPoint)->partner->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Groupe</p>
                        <p class="font-semibold">{{ optional($transaction->chargingPoint)->group->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Utilisateur</p>
                        <p class="font-semibold">{{ optional($transaction->user)->name ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Détails des commissions -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="p-4 bg-yellow-50 border-b border-yellow-100">
            <h2 class="text-lg font-semibold text-yellow-800">Détails des commissions</h2>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="border rounded-lg p-4">
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="font-semibold">Commission Admin</h3>
                        <span class="px-2 py-1 text-xs {{ $transaction->admin_commission_paid ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }} rounded-full">
                            {{ $transaction->admin_commission_paid ? 'Payée' : 'En attente' }}
                        </span>
                    </div>
                    <div class="text-2xl font-bold mb-2">{{ number_format($transaction->admin_commission, 2) }} €</div>
                    <div class="text-sm text-gray-500 mb-4">
                        @if($transaction->price_total > 0)
                            {{ number_format(($transaction->admin_commission / $transaction->price_total) * 100, 2) }}% du montant total
                        @else
                            N/A
                        @endif
                    </div>
                    
                    @if($transaction->admin_commission_paid)
                        <div class="text-sm text-gray-600">
                            Payée le: {{ \Carbon\Carbon::parse($transaction->admin_commission_paid_at)->format('d/m/Y') }}
                        </div>
                    @else
                        <form action="{{ route('commissions.mark-paid', $transaction) }}" method="POST">
                            @csrf
                            <input type="hidden" name="commission_type" value="admin">
                            <button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                                Marquer comme payée
                            </button>
                        </form>
                    @endif
                </div>
                
                <div class="border rounded-lg p-4">
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="font-semibold">Commission Intégrateur</h3>
                        <span class="px-2 py-1 text-xs {{ $transaction->integrator_commission_paid ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }} rounded-full">
                            {{ $transaction->integrator_commission_paid ? 'Payée' : 'En attente' }}
                        </span>
                    </div>
                    <div class="text-2xl font-bold mb-2">{{ number_format($transaction->integrator_commission, 2) }} €</div>
                    <div class="text-sm text-gray-500 mb-4">
                        @if($transaction->price_total > 0)
                            {{ number_format(($transaction->integrator_commission / $transaction->price_total) * 100, 2) }}% du montant total
                        @else
                            N/A
                        @endif
                    </div>
                    
                    @if($transaction->integrator_commission_paid)
                        <div class="text-sm text-gray-600">
                            Payée le: {{ \Carbon\Carbon::parse($transaction->integrator_commission_paid_at)->format('d/m/Y') }}
                        </div>
                    @else
                        <form action="{{ route('commissions.mark-paid', $transaction) }}" method="POST">
                            @csrf
                            <input type="hidden" name="commission_type" value="integrator">
                            <button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                                Marquer comme payée
                            </button>
                        </form>
                    @endif
                </div>
                
                <div class="border rounded-lg p-4">
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="font-semibold">Commission Opérateur (Partenaire)</h3>
                        <span class="px-2 py-1 text-xs {{ $transaction->partner_commission_paid ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }} rounded-full">
                            {{ $transaction->partner_commission_paid ? 'Payée' : 'En attente' }}
                        </span>
                    </div>
                    <div class="text-2xl font-bold mb-2">{{ number_format($transaction->partner_commission, 2) }} €</div>
                    <div class="text-sm text-gray-500 mb-4">
                        @if($transaction->price_total > 0)
                            {{ number_format(($transaction->partner_commission / $transaction->price_total) * 100, 2) }}% du montant total
                        @else
                            N/A
                        @endif
                    </div>
                    
                    @if($transaction->partner_commission_paid)
                        <div class="text-sm text-gray-600">
                            Payée le: {{ \Carbon\Carbon::parse($transaction->partner_commission_paid_at)->format('d/m/Y') }}
                        </div>
                    @else
                        <form action="{{ route('commissions.mark-paid', $transaction) }}" method="POST">
                            @csrf
                            <input type="hidden" name="commission_type" value="partner">
                            <button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                                Marquer comme payée
                            </button>
                        </form>
                    @endif
                </div>
            </div>
            
            <div class="mt-6 pt-6 border-t">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm text-gray-600">Plan de commission appliqué</p>
                        <p class="font-semibold">
                            @if($transaction->commissionPlan)
                                <a href="{{ route('admin.commission-plans.show', $transaction->commissionPlan) }}" class="text-blue-600 hover:text-blue-900">
                                    {{ $transaction->commissionPlan->name }}
                                </a>
                            @else
                                Aucun plan spécifique (calcul par défaut)
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Total des commissions</p>
                        <p class="text-xl font-bold">{{ number_format($transaction->getTotalCommission(), 2) }} €</p>
                    </div>
                </div>
                
                @if($transaction->commission_notes)
                <div class="mt-4">
                    <p class="text-sm text-gray-600">Notes</p>
                    <p class="text-gray-800">{{ $transaction->commission_notes }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Détails du prix -->
    @if(isset($transaction->price_details) && is_array($transaction->price_details))
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-4 border-b">
            <h2 class="text-lg font-semibold">Détails du prix</h2>
        </div>
        <div class="p-4">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantité</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prix unitaire</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($transaction->price_details as $key => $detail)
                        @if(is_array($detail) && isset($detail['amount']))
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $detail['description'] ?? str_replace('_', ' ', $key) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ isset($detail['quantity']) ? $detail['quantity'] : 'N/A' }}
                                {{ isset($detail['unit']) ? $detail['unit'] : '' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ isset($detail['unit_price']) ? number_format($detail['unit_price'], 2) . ' €' : 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ number_format($detail['amount'], 2) }} €
                            </td>
                        </tr>
                        @endif
                    @endforeach
                    <tr class="bg-gray-50">
                        <td colspan="3" class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 text-right">
                            Total
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                            {{ number_format($transaction->price_total, 2) }} €
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection