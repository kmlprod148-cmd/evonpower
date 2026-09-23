@extends('layouts.app')

@section('content')
<div class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="bg-white shadow-sm">
        <div class="px-4 py-3">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-medium text-gray-900">Ajouter un point de charge</h1>
                <a href="{{ route('charging-points.index') }}" class="text-green-600 hover:text-green-900">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
            </div>
        </div>
    </div>

    <div class="px-4 py-6">
        <!-- Form Progress -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div class="w-full flex items-center">
                    @foreach(['Informations générales', 'Spécifications techniques', 'Connectivité', 'Confirmation'] as $index => $step)
                        <!-- Step -->
                        <div class="relative flex-1 text-center">
                            <div class="bg-green-500 rounded-full h-8 w-8 mx-auto flex items-center justify-center text-white font-medium">
                                {{ $index + 1 }}
                            </div>
                            <div class="mt-2 text-sm text-green-500 font-medium">{{ $step }}</div>
                            
                            <!-- Connector line -->
                            @if($index < 3)
                                <div class="absolute top-4 w-full flex">
                                    <div class="h-0.5 w-full bg-green-500"></div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Form Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white shadow rounded-lg">
                <form action="{{ route('charging-points.store') }}" method="POST" id="confirmForm">
                    @csrf
                    <div class="p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-6">Confirmation des informations</h2>
                        
                        <div class="bg-gray-50 p-4 rounded-lg mb-6">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <h3 class="text-sm font-medium text-gray-500 mb-2">Informations générales</h3>
                                    <div class="mt-2 space-y-1">
                                        <p class="text-sm text-gray-900"><span class="font-medium">Nom:</span> {{ $step1['name'] ?? 'Non spécifié' }}</p>
                                        <p class="text-sm text-gray-900"><span class="font-medium">Groupe:</span> {{ $group->name ?? 'Non assigné' }}</p>
                                        <p class="text-sm text-gray-900"><span class="font-medium">Emplacement:</span> {{ $step1['location'] ?? 'Non spécifié' }}</p>
                                        <p class="text-sm text-gray-900"><span class="font-medium">Statut:</span>
                                            @if($step1['status'] == 'active')
                                                Actif
                                            @elseif($step1['status'] == 'maintenance')
                                                Maintenance
                                            @elseif($step1['status'] == 'inactive')
                                                Inactif
                                            @endif
                                        </p>
                                        @if(!empty($step1['description']))
                                            <p class="text-sm text-gray-900"><span class="font-medium">Description:</span> {{ $step1['description'] }}</p>
                                        @endif
                                    </div>
                                </div>
                                
                                <div>
                                    <h3 class="text-sm font-medium text-gray-500 mb-2">Spécifications techniques</h3>
                                    <div class="mt-2 space-y-1">
                                        <p class="text-sm text-gray-900"><span class="font-medium">Fabricant:</span> {{ $step2['manufacturer'] }}</p>
                                        <p class="text-sm text-gray-900"><span class="font-medium">Modèle:</span> {{ $step2['model'] }}</p>
                                        <p class="text-sm text-gray-900"><span class="font-medium">Puissance:</span> {{ $step2['power_output'] }} kW</p>
                                        <p class="text-sm text-gray-900"><span class="font-medium">Type de connecteur:</span>
                                            @if($step2['connector_type'] == 'type1')
                                                Type 1
                                            @elseif($step2['connector_type'] == 'type2')
                                                Type 2
                                            @elseif($step2['connector_type'] == 'chademo')
                                                CHAdeMO
                                            @elseif($step2['connector_type'] == 'ccs')
                                                CCS
                                            @elseif($step2['connector_type'] == 'other')
                                                Autre
                                            @endif
                                        </p>
                                        @if(!empty($step2['serial_number']))
                                            <p class="text-sm text-gray-900"><span class="font-medium">Numéro de série:</span> {{ $step2['serial_number'] }}</p>
                                        @endif
                                        @if(!empty($step2['installation_date']))
                                            <p class="text-sm text-gray-900"><span class="font-medium">Date d'installation:</span> {{ $step2['installation_date'] }}</p>
                                        @endif
                                    </div>
                                </div>
                                
                                <div>
                                    <h3 class="text-sm font-medium text-gray-500 mb-2">Connectivité</h3>
                                    <div class="mt-2 space-y-1">
                                        <p class="text-sm text-gray-900"><span class="font-medium">Type de connexion:</span>
                                            @if($step3['connection_type'] == 'ethernet')
                                                Ethernet
                                            @elseif($step3['connection_type'] == 'wifi')
                                                Wi-Fi
                                            @elseif($step3['connection_type'] == 'gsm')
                                                GSM/4G
                                            @elseif($step3['connection_type'] == 'other')
                                                Autre
                                            @endif
                                        </p>
                                        @if(!empty($step3['ip_address']))
                                            <p class="text-sm text-gray-900"><span class="font-medium">Adresse IP:</span> {{ $step3['ip_address'] }}</p>
                                        @endif
                                        <p class="text-sm text-gray-900"><span class="font-medium">Protocole:</span>
                                            @if($step3['communication_protocol'] == 'ocpp16')
                                                OCPP 1.6
                                            @elseif($step3['communication_protocol'] == 'ocpp20')
                                                OCPP 2.0
                                            @elseif($step3['communication_protocol'] == 'proprietary')
                                                Propriétaire
                                            @elseif($step3['communication_protocol'] == 'other')
                                                Autre
                                            @endif
                                        </p>
                                        @if(!empty($step3['firmware_version']))
                                            <p class="text-sm text-gray-900"><span class="font-medium">Version du firmware:</span> {{ $step3['firmware_version'] }}</p>
                                        @endif
                                        <p class="text-sm text-gray-900"><span class="font-medium">Authentification requise:</span> {{ isset($step3['authentication_required']) && $step3['authentication_required'] ? 'Oui' : 'Non' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="rounded-md bg-yellow-50 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800">Veuillez vérifier toutes les informations</h3>
                                    <div class="mt-2 text-sm text-yellow-700">
                                        <p>Assurez-vous que les informations saisies sont correctes avant de terminer le processus.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Form Navigation -->
                    <div class="px-6 py-4 bg-gray-50 border-t text-right flex justify-between">
                        <a href="{{ route('charging-points.create.step3') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Précédent
                        </a>
                        
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-green-600 bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 01-1.414 0l-4-4a1 1 011.414-1.414L8 12.586l7.293-7.293a1 1 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Créer le point de charge
                        </button>
                    </div>
                </form>
            </div>

            <!-- Summary Card -->
            <div class="bg-white shadow rounded-lg p-6 h-fit sticky top-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Résumé</h3>
                
                <div class="space-y-6">
                    <div>
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-green-100 rounded-md p-1">
                                <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h4 class="text-sm font-medium text-gray-900">Informations générales</h4>
                                <p class="text-sm text-gray-500">Terminé</p>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-green-100 rounded-md p-1">
                                <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h4 class="text-sm font-medium text-gray-900">Spécifications techniques</h4>
                                <p class="text-sm text-gray-500">Terminé</p>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-green-100 rounded-md p-1">
                                <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h4 class="text-sm font-medium text-gray-900">Connectivité</h4>
                                <p class="text-sm text-gray-500">Terminé</p>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-green-100 rounded-md p-1">
                                <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h4 class="text-sm font-medium text-gray-900">Vérification finale</h4>
                                <p class="text-sm text-gray-500">En cours</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="pt-6 border-t border-gray-200">
                        <button type="button" id="submitButton" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-green-600 bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 01-1.414 0l-4-4a1 1 011.414-1.414L8 12.586l7.293-7.293a1 1 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Confirmer et créer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Connecter le bouton du panneau latéral au formulaire principal
        const submitButton = document.getElementById('submitButton');
        const confirmForm = document.getElementById('confirmForm');
        
        submitButton.addEventListener('click', function() {
            confirmForm.submit();
        });
    });
</script>
@endpush
@endsection