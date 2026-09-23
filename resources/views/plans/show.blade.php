@extends('layouts.app')

@section('content')
<div class="w-full">
    <!-- Navigation -->
    <nav class="flex w-full h-[52px] items-center gap-[34px] px-7 py-0 bg-white">
        <a href="{{ route('dashboard') }}" class="font-medium text-sm leading-5 text-black">Dashboard</a>
        <a href="{{ route('plans.index') }}" class="font-medium text-sm leading-5 text-black">Tarification</a>
        <a href="#" class="font-medium text-sm leading-5 text-[#49ce7d]">Détail du plan</a>
    </nav>

    <!-- Header Section -->
    <div class="px-7 py-6">
        <div class="flex justify-between items-center">
            <div>
                <div class="text-gray-500 text-sm">Business Plan</div>
                <h1 class="text-2xl font-medium mt-2">{{ $plan->name }}</h1>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('plans.edit', $plan) }}" class="flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <span class="font-medium text-sm">Modifier</span>
                </a>
                <form action="{{ route('plans.destroy', $plan) }}" method="POST" class="inline-block delete-form">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="flex items-center gap-2 px-4 py-2 border border-red-300 text-red-600 rounded-lg hover:bg-red-50" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce plan?')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <span class="font-medium text-sm">Supprimer</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <hr class="border-t border-[#0000001a]">

    <!-- Success Message -->
    @if(session('success'))
    <div class="mx-7 mt-6 bg-green-50 border-l-4 border-green-500 p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-green-700">{{ session('success') }}</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Main Content -->
    <div class="px-7 py-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Plan Details -->
            <div class="md:col-span-2">
                <div class="bg-white shadow rounded-lg p-6">
                    <h2 class="text-lg font-medium mb-4">Détails du plan</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-y-4">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Nom</h3>
                            <p class="text-sm">{{ $plan->name }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Description</h3>
                            <p class="text-sm">{{ $plan->description }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Type de tarif</h3>
                            <p class="text-sm">{{ $plan->rate_type }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Tarif de base</h3>
                            <p class="text-sm">{{ $plan->base_rate }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Prix par kWh</h3>
                            <p class="text-sm">{{ $plan->price_per_kwh }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Prix par minute</h3>
                            <p class="text-sm">{{ $plan->price_per_minute }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Frais d'activation</h3>
                            <p class="text-sm">{{ $plan->activation_fee }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">ID TVA</h3>
                            <p class="text-sm">{{ $plan->vat_rate_id }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Priorité</h3>
                            <p class="text-sm">{{ $plan->priority }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Durée maximale</h3>
                            <p class="text-sm">{{ $plan->max_duration ? $plan->max_duration . ' minutes' : 'Non définie' }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Statut</h3>
                            <p class="text-sm">{{ $plan->is_active ? 'Actif' : 'Inactif' }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Devise</h3>
                            <p class="text-sm">{{ $plan->currency }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Intervalle de facturation</h3>
                            <p class="text-sm">{{ $plan->billing_interval }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Créé le</h3>
                            <p class="text-sm">{{ $plan->created_at ? $plan->created_at->format('d/m/Y H:i') : '' }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Modifié le</h3>
                            <p class="text-sm">{{ $plan->updated_at ? $plan->updated_at->format('d/m/Y H:i') : '' }}</p>
                        </div>
                    </div>
                </div>
                
                <!-- Additional Rates -->
                <div class="bg-white shadow rounded-lg p-6 mt-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-medium">Tarifs supplémentaires</h2>
                        <a href="{{ route('plans.edit', $plan) }}#additional-rates" class="text-[#49ce7d] hover:text-[#3db96a] text-sm font-medium">
                            Gérer les tarifs
                        </a>
                    </div>
                    
                    @if($plan->additionalRates && $plan->additionalRates->count() > 0)
                        <div class="overflow-hidden border border-gray-200 rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prix</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Condition</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($plan->additionalRates as $rate)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $rate->name }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ $rate->price }} EUR</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                @if(isset($rate->condition_type_name))
                                                    <span class="font-medium">{{ $rate->condition_type_name }}:</span>
                                                @endif
                                                {{ $rate->condition_description ?? 'N/A' }}
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="py-8 text-center bg-gray-50 rounded-lg">
                            <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">Aucun tarif supplémentaire</h3>
                            <p class="mt-1 text-sm text-gray-500">Ce plan n'a pas de tarifs supplémentaires définis.</p>
                            <div class="mt-6">
                                <a href="{{ route('plans.edit', $plan) }}#additional-rates" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-[#49ce7d] hover:bg-[#3db96a]">
                                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                                    </svg>
                                    Ajouter des tarifs
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Associated Groups -->
            <div>
                <div class="bg-white shadow rounded-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-medium">Groupes associés</h2>
                        <a href="{{ route('plans.edit', $plan) }}#groups" class="text-[#49ce7d] hover:text-[#3db96a] text-sm font-medium">
                            Modifier
                        </a>
                    </div>
                    
                    @if($plan->groups && $plan->groups->count() > 0)
                        <div class="space-y-3 max-h-[400px] overflow-y-auto pr-2">
                            @foreach($plan->groups as $group)
                                <div class="p-3 border border-gray-200 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="w-2 h-2 rounded-full bg-[#49ce7d]"></div>
                                        <div class="ml-2">
                                            <div class="text-sm font-medium">{{ $group->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $group->charging_points_count ?? 0 }} bornes</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="py-8 text-center bg-gray-50 rounded-lg">
                            <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">Aucun groupe associé</h3>
                            <p class="mt-1 text-sm text-gray-500">Ce plan n'est associé à aucun groupe de bornes.</p>
                            <div class="mt-6">
                                <a href="{{ route('plans.edit', $plan) }}#groups" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-[#49ce7d] hover:bg-[#3db96a]">
                                    Associer des groupes
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
                
                <!-- Business Profiles Section -->
                <div class="bg-white shadow rounded-lg p-6 mt-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-medium">Profils Business</h2>
                        <a href="{{ route('plans.apply-to-profiles', $plan) }}" class="text-[#49ce7d] hover:text-[#3db96a] text-sm font-medium">
                            Appliquer aux intégrateurs
                        </a>
                    </div>
                    
                    @if($plan->businessProfiles && $plan->businessProfiles->count() > 0)
                        <div class="space-y-3 max-h-[400px] overflow-y-auto pr-2">
                            @foreach($plan->businessProfiles as $profile)
                                <div class="p-3 border border-gray-200 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="w-2 h-2 rounded-full {{ $profile->type == 'Intégrateur' ? 'bg-blue-500' : 'bg-green-500' }}"></div>
                                        <div class="ml-2">
                                            <div class="text-sm font-medium">{{ $profile->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $profile->type }} - {{ $profile->city }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="py-8 text-center bg-gray-50 rounded-lg">
                            <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">Aucun profil associé</h3>
                            <p class="mt-1 text-sm text-gray-500">Ce plan n'est associé à aucun profil business.</p>
                            <div class="mt-6">
                                <a href="{{ route('plans.apply-to-profiles', $plan) }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-[#49ce7d] hover:bg-[#3db96a]">
                                    Appliquer aux intégrateurs
                                </a>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Usage Statistics (if relevant) -->
                <div class="bg-white shadow rounded-lg p-6 mt-6">
                    <h2 class="text-lg font-medium mb-4">Statistiques d'utilisation</h2>
                    
                    <div class="space-y-4">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Bornes utilisant ce plan</h3>
                            <p class="text-2xl font-semibold">{{ $plan->chargingPoints()->count() }}</p>
                        </div>
                        
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Utilisateurs abonnés</h3>
                            <p class="text-2xl font-semibold">{{ $plan->users()->count() }}</p>
                        </div>
                        
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Profils business</h3>
                            <p class="text-2xl font-semibold">{{ $plan->businessProfile ? $plan->businessProfile->name : 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection