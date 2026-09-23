@extends('layouts.app')

@section('title', 'Mes Business Profiles')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Mes Business Profiles</h1>
        <a href="{{ route('integrator.business-profiles.create') }}" 
           class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
            <i class="fas fa-plus mr-2"></i>Créer un Business Profile
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    @if($businessProfiles->count() > 0)
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <ul class="divide-y divide-gray-200">
                @foreach($businessProfiles as $profile)
                    <li class="px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center">
                                    <h3 class="text-lg font-medium text-gray-900">
                                        {{ $profile->name }}
                                    </h3>
                                    @if($profile->is_public)
                                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Public
                                        </span>
                                    @else
                                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            Privé
                                        </span>
                                    @endif
                                    
                                    @if($profile->is_active)
                                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            Actif
                                        </span>
                                    @else
                                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            Inactif
                                        </span>
                                    @endif
                                </div>
                                
                                <p class="mt-1 text-sm text-gray-500">
                                    {{ Str::limit($profile->description, 100) }}
                                </p>
                                
                                <div class="mt-2 text-sm text-gray-500">
                                    <span>Créé le {{ $profile->created_at->format('d/m/Y à H:i') }}</span>
                                    @if($profile->updated_at != $profile->created_at)
                                        <span class="ml-4">Modifié le {{ $profile->updated_at->format('d/m/Y à H:i') }}</span>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('integrator.business-profiles.show', $profile) }}" 
                                   class="text-blue-600 hover:text-blue-900 text-sm font-medium">
                                    Voir
                                </a>
                                <a href="{{ route('integrator.business-profiles.edit', $profile) }}" 
                                   class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                    Modifier
                                </a>
                                <form action="{{ route('integrator.business-profiles.destroy', $profile) }}" 
                                      method="POST" 
                                      class="inline"
                                      onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce business profile ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 text-sm font-medium">
                                        Supprimer
                                    </button>
                                </form>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="mt-6">
            {{ $businessProfiles->links() }}
        </div>
    @else
        <div class="text-center py-12">
            <div class="mx-auto h-12 w-12 text-gray-400">
                <i class="fas fa-building text-4xl"></i>
            </div>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Aucun business profile</h3>
            <p class="mt-1 text-sm text-gray-500">
                Commencez par créer votre premier business profile.
            </p>
            <div class="mt-6">
                <a href="{{ route('integrator.business-profiles.create') }}" 
                   class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    <i class="fas fa-plus -ml-1 mr-2 h-5 w-5"></i>
                    Créer un Business Profile
                </a>
            </div>
        </div>
    @endif
</div>
@endsection
