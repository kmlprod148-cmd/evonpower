@extends('layouts.app')

@section('content')
<div class="plan-apply-container">
    <div class="text-gray-500 text-sm">Plans Tarifaires</div>

    <div class="flex items-center mb-6">
        <a href="{{ route('plans.show', $plan) }}" class="text-gray-500 hover:text-gray-700 mr-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
        </a>
        <h1 class="text-2xl font-medium">Appliquer les plans tarifaires</h1>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center mb-6">
            <div class="bg-green-100 rounded-full h-12 w-12 flex items-center justify-center text-green-500 mr-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <h2 class="text-xl font-medium">{{ $plan->name }}</h2>
                <p class="text-gray-500">{{ $plan->type_name }} - {{ number_format($plan->base_rate, 2) }} {{ $plan->currency ?? 'EUR' }}</p>
            </div>
        </div>

        @if ($errors->any())
        <div class="bg-red-50 text-red-500 p-4 rounded-lg mb-6">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('plans.save-profile-associations', $plan) }}" method="POST">
            @csrf

            <div class="mb-6">
                <h3 class="text-lg font-medium mb-4">Selectionner les profils business</h3>
                <p class="text-gray-500 mb-4">Choisissez les profils actifs auxquels ce plan tarifaire sera applique.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @forelse($businessProfiles as $businessProfile)
                        <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                            <div class="flex items-start">
                                <input
                                    type="checkbox"
                                    name="business_profiles[]"
                                    id="profile-{{ $businessProfile->id }}"
                                    value="{{ $businessProfile->id }}"
                                    class="h-5 w-5 text-green-500 focus:ring-green-400 border-gray-300 rounded mt-1"
                                    {{ in_array($businessProfile->id, $associatedProfileIds) ? 'checked' : '' }}
                                >
                                <div class="ml-3">
                                    <label for="profile-{{ $businessProfile->id }}" class="font-medium text-gray-700">{{ $businessProfile->name }}</label>
                                    <p class="text-sm text-gray-500">{{ $businessProfile->description ?: 'Profil business actif' }}</p>
                                    <p class="text-xs text-gray-400">
                                        @if($businessProfile->integrator_id)
                                            Integrateur #{{ $businessProfile->integrator_id }}
                                        @elseif($businessProfile->partner_id)
                                            Partenaire #{{ $businessProfile->partner_id }}
                                        @else
                                            Profil general
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-3 text-center py-8 text-gray-500">
                            Aucun profil business actif n'a ete trouve.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <a href="{{ route('plans.show', $plan) }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Annuler
                </a>
                <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded-md text-sm font-medium hover:bg-green-600">
                    Appliquer le plan tarifaire
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
