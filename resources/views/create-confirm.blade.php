@extends('layouts.app')

@section('content')
<div class="bg-gradient-to-b from-gray-50 to-white dark:from-gray-900 dark:to-gray-950 min-h-screen">
    @include('charging-points.partials._creation-header', [
        'title' => 'Confirmation',
        'currentStep' => 4,
        'totalSteps' => 4,
        'backUrl' => route('charging-points.create.step3')
    ])

    <main class="max-w-7xl mx-auto px-4 py-8">
        @include('charging-points.partials._creation-stepper', ['currentStep' => 4])

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm">
                <form action="{{ route('charging-points.store') }}" method="POST" id="confirmForm">
                    @csrf
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">Résumé de la configuration</h2>
                        
                        <div class="space-y-8">
                            <!-- Section: General -->
                            <div>
                                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4 pb-3 border-b border-gray-200/80 dark:border-gray-800/80 flex items-center gap-3">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-300">1</span>
                                    Informations générales
                                </h3>
                                <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                                    <div class="sm:col-span-1">
                                        <dt class="font-medium text-gray-500 dark:text-gray-400">Nom</dt>
                                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ session('charging_point_step1.name') ?? 'Non spécifié' }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="font-medium text-gray-500 dark:text-gray-400">Numéro de série</dt>
                                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ session('charging_point_step1.serial_number') ?? 'Non spécifié' }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="font-medium text-gray-500 dark:text-gray-400">Emplacement</dt>
                                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ session('charging_point_step1.location') ?? 'Non spécifié' }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="font-medium text-gray-500 dark:text-gray-400">Statut initial</dt>
                                        <dd class="mt-1 text-gray-900 dark:text-gray-100">
                                            @if(session('charging_point_step1.status') == 'online')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">En ligne</span>
                                            @elseif(session('charging_point_step1.status') == 'offline')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">Hors ligne</span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">Maintenance</span>
                                            @endif
                                        </dd>
                                    </div>
                                    @if(session('charging_point_step1.description'))
                                    <div class="sm:col-span-2">
                                        <dt class="font-medium text-gray-500 dark:text-gray-400">Description</dt>
                                        <dd class="mt-1 text-gray-900 dark:text-gray-100 whitespace-pre-wrap">{{ session('charging_point_step1.description') }}</dd>
                                    </div>
                                    @endif
                                </dl>
                            </div>

                            <!-- Section: Technical -->
                            <div>
                                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4 pb-3 border-b border-gray-200/80 dark:border-gray-800/80 flex items-center gap-3">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-300">2</span>
                                    Spécifications techniques
                                </h3>
                                <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                                    <div class="sm:col-span-1">
                                        <dt class="font-medium text-gray-500 dark:text-gray-400">Fabricant</dt>
                                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ session('charging_point_step2.manufacturer') ?? 'Non spécifié' }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="font-medium text-gray-500 dark:text-gray-400">Modèle</dt>
                                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ session('charging_point_step2.model') ?? 'Non spécifié' }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="font-medium text-gray-500 dark:text-gray-400">Puissance</dt>
                                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ session('charging_point_step2.power_output') ? session('charging_point_step2.power_output') . ' kW' : 'Non spécifié' }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="font-medium text-gray-500 dark:text-gray-400">Connecteur</dt>
                                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ session('charging_point_step2.connector_type') ?? 'Non spécifié' }}</dd>
                                    </div>
                                </dl>
                            </div>

                            <!-- Section: Connectivity -->
                            <div>
                                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4 pb-3 border-b border-gray-200/80 dark:border-gray-800/80 flex items-center gap-3">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-300">3</span>
                                    Connectivité
                                </h3>
                                <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                                    <div class="sm:col-span-1">
                                        <dt class="font-medium text-gray-500 dark:text-gray-400">Connexion</dt>
                                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ session('charging_point_step3.connection_type') ?? 'Non spécifié' }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="font-medium text-gray-500 dark:text-gray-400">Protocole</dt>
                                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ session('charging_point_step3.communication_protocol') ?? 'Non spécifié' }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>
                    
                    <div class="px-6 py-4 bg-gray-50/80 dark:bg-gray-900/40 border-t border-gray-200/80 dark:border-gray-800/80 rounded-b-2xl flex items-center justify-between">
                        <a href="{{ route('charging-points.create.step3') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 ring-1 ring-gray-200 dark:ring-gray-700 transition">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                            Précédent
                        </a>
                        
                        <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 shadow-sm transition active:scale-[.99]">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                            Créer le point de charge
                        </button>
                    </div>
                </form>
            </div>

            <aside class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm p-6 h-fit sticky top-6">
                <div class="rounded-xl bg-emerald-50 dark:bg-emerald-900/20 p-4 ring-1 ring-emerald-200/70 dark:ring-emerald-900/40">
                    <div class="flex gap-3">
                        <svg class="h-5 w-5 text-emerald-500 dark:text-emerald-300 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                        <div>
                            <h4 class="text-sm font-medium text-emerald-900 dark:text-emerald-200">Prêt à finaliser</h4>
                            <p class="mt-1 text-sm text-emerald-800/90 dark:text-emerald-300/90">Vérifiez les informations ci-contre. Une fois la borne créée, vous pourrez la gérer depuis votre tableau de bord.</p>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </main>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Connecter le bouton du panneau latéral au formulaire principal
        const submitButton = document.getElementById('submitButton');
        const confirmForm = document.getElementById('confirmForm');
        
        submitButton.addEventListener('click', function() {
            // Afficher un message de confirmation
            if (confirm('Êtes-vous sûr de vouloir créer ce point de charge avec les informations spécifiées ?')) {
                // Animation de chargement pour montrer que le formulaire est en train d'être soumis
                submitButton.disabled = true;
                submitButton.innerHTML = `
                    <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Création en cours...
                `;
                
                // Soumettre le formulaire
                confirmForm.submit();
            }
        });
    });
</script>
@endpush
@endsection