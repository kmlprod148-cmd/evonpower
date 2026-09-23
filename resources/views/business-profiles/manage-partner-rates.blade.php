@extends('layouts.app')

@section('content')
@php
    $businessProfileRoutePrefix = $businessProfileRoutePrefix ?? 'business-profiles';
@endphp
<div class="partner-rates-container">
    <div class="text-gray-500 text-sm">Profils Business</div>
    
    <div class="flex items-center mb-6">
        <a href="{{ route($businessProfileRoutePrefix . '.show', $businessProfile) }}" class="text-gray-500 hover:text-gray-700 mr-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
        </a>
        <h1 class="text-2xl font-medium">Gérer les tarifs des partenaires</h1>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center mb-6">
            @if($businessProfile->logo)
                <img src="{{ asset('storage/' . $businessProfile->logo) }}" alt="Logo" class="h-16 w-16 rounded-full object-cover mr-4">
            @else
                <div class="h-16 w-16 bg-blue-100 rounded-full flex items-center justify-center text-blue-500 font-bold text-2xl mr-4">
                    {{ substr($businessProfile->name, 0, 1) }}
                </div>
            @endif
            
            <div>
                <h2 class="text-xl font-medium">{{ $businessProfile->name }}</h2>
                <p class="text-gray-500">Intégrateur - {{ $businessProfile->partners->count() }} partenaires</p>
            </div>
        </div>
        
        @php
            $partners = $partners ?? ($businessProfile->partners ?? collect());
            $availablePlans = $availablePlans ?? (($businessProfile->pricingPlans ?? null) ? $businessProfile->pricingPlans : collect());
        @endphp

        @if ($errors->any())
        <div class="bg-red-50 text-red-500 p-4 rounded-lg mb-6">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif
        
        @if(session('success'))
        <div class="bg-green-50 text-green-500 p-4 rounded-lg mb-6">
            {{ session('success') }}
        </div>
        @endif
        
        @if(session('error'))
        <div class="bg-red-50 text-red-500 p-4 rounded-lg mb-6">
            {{ session('error') }}
        </div>
        @endif
        
        <div class="mb-6">
            <h3 class="text-lg font-medium mb-4">Vos plans tarifaires</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                @forelse(($businessProfile->pricingPlans ?? collect()) as $plan)
                    <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                        <div class="flex items-center mb-2">
                            <div class="w-2 h-2 rounded-full bg-green-500 mr-2"></div>
                            <h4 class="font-medium">{{ $plan->name }}</h4>
                        </div>
                        <p class="text-sm text-gray-500">{{ $plan->rate_type }} - {{ $plan->formatted_price }}</p>
                    </div>
                @empty
                    <div class="col-span-3 text-center py-4 text-gray-500">
                        Vous n'avez aucun plan tarifaire associé à votre profil.
                    </div>
                @endforelse
            </div>
        </div>
        
        @if($partners->count() > 0)
            <form action="{{ route($businessProfileRoutePrefix . '.apply-partner-rates', $businessProfile) }}" method="POST">
                @csrf
                
                <h3 class="text-lg font-medium mb-4">Appliquer des tarifs à vos partenaires</h3>
                
                <div class="overflow-hidden border border-gray-200 rounded-lg mb-6">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Partenaire</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plans tarifaires</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($partners as $partner)
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        @if($partner->logo)
                                            <img src="{{ asset('storage/' . $partner->logo) }}" alt="Logo" class="h-10 w-10 rounded-full object-cover mr-3">
                                        @else
                                            <div class="h-10 w-10 bg-green-100 rounded-full flex items-center justify-center text-green-500 font-bold text-xl mr-3">
                                                {{ substr($partner->name, 0, 1) }}
                                            </div>
                                        @endif
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ $partner->name }}</div>
                                            <div class="text-sm text-gray-500">{{ $partner->city }}, {{ $partner->country }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                        @foreach($availablePlans as $plan)
                                            <div class="flex items-center">
                                                <input type="checkbox" 
                                                       name="partner_plans[{{ $partner->id }}][]" 
                                                       id="plan-{{ $partner->id }}-{{ $plan->id }}" 
                                                       value="{{ $plan->id }}" 
                                                       class="h-4 w-4 text-green-500 focus:ring-green-400 border-gray-300 rounded"
                                                       {{ ($partner->pricingPlans && $partner->pricingPlans->contains($plan->id)) ? 'checked' : '' }}>
                                                <label for="plan-{{ $partner->id }}-{{ $plan->id }}" class="ml-2 text-sm text-gray-700">
                                                    {{ $plan->name }} ({{ number_format($plan->base_rate, 2) }} {{ $plan->currency ?? '€' }})
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <a href="{{ route($businessProfileRoutePrefix . '.show', $businessProfile) }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Annuler
                    </a>
                    <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded-md text-sm font-medium hover:bg-green-600">
                        Appliquer les tarifs
                    </button>
                </div>
            </form>
        @else
            <div class="py-8 text-center bg-gray-50 rounded-lg">
                <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Aucun partenaire</h3>
                <p class="mt-1 text-sm text-gray-500">Vous n'avez aucun partenaire associé à votre profil.</p>
                <div class="mt-6">
                    <a href="{{ route($businessProfileRoutePrefix . '.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-500 hover:bg-green-600">
                        Retour à la liste des profils
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
