@extends('layouts.app')

@section('content')
<div class="bg-gradient-to-b from-gray-50 to-white dark:from-gray-900 dark:to-gray-950 min-h-screen">
    @include('charging-points.partials._creation-header', [
        'title' => 'Borne créée avec succès',
        'currentStep' => 4,
        'totalSteps' => 4,
        'backUrl' => route('charging-points.index')
    ])

    <main class="max-w-7xl mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                        </span>
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Votre borne a été créée</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Revoyez les informations principales avant de passer aux actions suivantes.</p>
                        </div>
                    </div>

                    <div class="space-y-8">
                        <section>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">Informations générales</h3>
                            <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                                <div>
                                    <dt class="font-medium text-gray-500 dark:text-gray-400">Nom</dt>
                                    <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ $chargingPoint->name ?? 'Non spécifié' }}</dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-500 dark:text-gray-400">Numéro de série</dt>
                                    <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ $chargingPoint->serial_number ?? 'Non spécifié' }}</dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-500 dark:text-gray-400">Statut</dt>
                                    <dd class="mt-1">
                                        @php
    $status = $chargingPoint->status ?? null;
@endphp
                                        @if($status === 'online')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">En ligne</span>
                                        @elseif($status === 'offline')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Hors ligne</span>
                                        @elseif($status === 'maintenance')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Maintenance</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Non défini</span>
                                        @endif
                                    </dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-500 dark:text-gray-400">Créée par</dt>
                                    <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ optional($chargingPoint->user)->name ?? 'Utilisateur inconnu' }}</dd>
                                </div>
                            </dl>
                        </section>

                        <section>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">Localisation</h3>
                            <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                                <div>
                                    <dt class="font-medium text-gray-500 dark:text-gray-400">Adresse</dt>
                                    <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ $chargingPoint->address ?? 'Non renseignée' }}</dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-500 dark:text-gray-400">Ville</dt>
                                    <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ $chargingPoint->city ?? 'Non renseignée' }}</dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-500 dark:text-gray-400">Latitude</dt>
                                    <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ $chargingPoint->latitude ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-500 dark:text-gray-400">Longitude</dt>
                                    <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ $chargingPoint->longitude ?? 'N/A' }}</dd>
                                </div>
                            </dl>
                        </section>

                        <section>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">Détails techniques</h3>
                            <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                                <div>
                                    <dt class="font-medium text-gray-500 dark:text-gray-400">Fabricant</dt>
                                    <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ $chargingPoint->manufacturer ?? 'Non spécifié' }}</dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-500 dark:text-gray-400">Modèle</dt>
                                    <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ $chargingPoint->model ?? 'Non spécifié' }}</dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-500 dark:text-gray-400">Puissance de sortie</dt>
                                    <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ $chargingPoint->power_output ? $chargingPoint->power_output . ' kW' : 'Non spécifiée' }}</dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-500 dark:text-gray-400">Type de connecteur</dt>
                                    <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ $chargingPoint->connector_type ?? 'Non spécifié' }}</dd>
                                </div>
                            </dl>
                        </section>

                        @if(!empty($chargingPoint->description))
                        <section>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-3">Description</h3>
                            <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $chargingPoint->description }}</p>
                        </section>
                        @endif
                    </div>
                </div>
            </div>

            <aside class="space-y-6 h-fit">
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm p-6">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">Actions rapides</h3>
                    <div class="space-y-3">
                        <a href="{{ route('charging-points.show', $chargingPoint->id) }}" class="inline-flex items-center justify-between w-full px-4 py-3 rounded-xl border border-gray-200 text-sm font-medium text-gray-700 hover:border-emerald-300 hover:bg-emerald-50 dark:text-gray-200 dark:border-gray-800 dark:hover:bg-gray-800 transition">
                            Voir la fiche détaillée
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                        <a href="{{ route('charging-points.index') }}" class="inline-flex items-center justify-between w-full px-4 py-3 rounded-xl border border-gray-200 text-sm font-medium text-gray-700 hover:border-gray-300 hover:bg-gray-50 dark:text-gray-200 dark:border-gray-800 dark:hover:bg-gray-800 transition">
                            Retour à la liste
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                        <a href="{{ route('charging-points.create.step1') }}" class="inline-flex items-center justify-between w-full px-4 py-3 rounded-xl border border-gray-200 text-sm font-medium text-gray-700 hover:border-gray-300 hover:bg-gray-50 dark:text-gray-200 dark:border-gray-800 dark:hover:bg-gray-800 transition">
                            Créer une autre borne
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </a>
                    </div>
                </div>

                <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-2xl border border-emerald-200/70 dark:border-emerald-900/40 p-6">
                    <h4 class="text-sm font-semibold text-emerald-900 dark:text-emerald-100 mb-2">Prochaines étapes</h4>
                    <ul class="mt-2 space-y-2 text-sm text-emerald-900/80 dark:text-emerald-200/80">
                        <li class="flex items-start gap-2">
                            <span class="mt-1 inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                                <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                            </span>
                            <span>Configurez les plans tarifaires et profils de commission si nécessaire.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="mt-1 inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                                <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                            </span>
                            <span>Ajoutez ou configurez les connecteurs associés depuis la fiche détaillée.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="mt-1 inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                                <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                            </span>
                            <span>Connectez la borne au serveur OCPP pour activer les commandes à distance.</span>
                        </li>
                    </ul>
                </div>
            </aside>
        </div>
    </main>
</div>
@endsection
