@extends('layouts.app')

@section('content')
<div class="px-4 py-6">
    <div class="mb-6">
        <div class="text-gray-500 text-sm mb-1">Paramètres > Sécurité > Activité</div>
        <div class="flex justify-between items-center">
            <h1 class="text-2xl font-medium">Historique d'activité</h1>
            <a href="{{ route('settings.security') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                ← Retour aux paramètres de sécurité
            </a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-medium">Activités récentes</h2>
                <span class="text-sm text-gray-500">Les 100 dernières activités</span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Activité</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Emplacement</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date et heure</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($activities as $activity)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <span class="text-sm font-medium text-gray-900">{{ $activity['type'] }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $activity['location'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $activity['created_at']->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-sm text-gray-500 text-center">
                                    Aucune activité enregistrée.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="p-6">
            <h3 class="text-md font-medium mb-3">À propos de l'historique d'activité</h3>
            <p class="text-sm text-gray-600 mb-2">Nous enregistrons les activités importantes liées à votre compte afin de vous aider à maintenir sa sécurité.</p>
            <p class="text-sm text-gray-600 mb-2">Les activités enregistrées comprennent :</p>
            <ul class="list-disc pl-5 text-sm text-gray-600 mb-4">
                <li>Connexions</li>
                <li>Changements de mot de passe</li>
                <li>Mises à jour de profil</li>
                <li>Modifications des paramètres de sécurité</li>
                <li>Autres actions importantes liées à votre compte</li>
            </ul>
            <p class="text-sm text-gray-600">Les informations de localisation sont approximatives et basées sur votre adresse IP.</p>
        </div>
    </div>

    <div class="mt-6 flex justify-between items-center">
        <a href="{{ route('settings.security') }}" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-gray-700 bg-gray-100 hover:bg-gray-200">
            Retour aux paramètres de sécurité
        </a>
        @if(count($activities) > 0)
            <a href="{{ route('settings.security.activity.export') }}" class="inline-flex items-center justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Exporter l'historique
            </a>
        @endif
    </div>
</div>
@endsection