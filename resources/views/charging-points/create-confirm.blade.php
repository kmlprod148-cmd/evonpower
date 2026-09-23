@extends('layouts.app')

@section('content')
<div class="bg-gradient-to-b from-gray-50 to-white dark:from-gray-900 dark:to-gray-950 min-h-screen">
    <style>
        .sk { position: relative; overflow: hidden; background: rgba(0,0,0,0.06); border-radius: .5rem; }
        .sk::after { content: ""; position: absolute; inset: 0; transform: translateX(-100%); background: linear-gradient(90deg, transparent, rgba(255,255,255,.35), transparent); animation: sh 1.2s infinite; }
        @keyframes sh { 100% { transform: translateX(100%);} }
        .sk-line { height: 12px; margin: 8px 0; }
        .sk-title { height: 20px; width: 40%; }
        .sk-badge { height: 18px; width: 80px; display: inline-block; }
        .sk-grid { display: grid; gap: 12px; grid-template-columns: repeat(2,minmax(0,1fr)); }
        .hidden { display: none; }
        /* Hide skeleton immediately - no animation delay */
        #cp-confirm-skeleton { display: none; }
    </style>
    @include('charging-points.partials._creation-header', [
        'title' => 'Confirmation',
        'currentStep' => 4,
        'totalSteps' => 4,
        'backUrl' => route('charging-points.create.step3')
    ])

    <div id="cp-confirm-skeleton" class="max-w-7xl mx-auto px-4 py-8">
        <div class="sk sk-title"></div>
        <div class="sk sk-badge" style="margin-top:12px"></div>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
            <div class="lg:col-span-2 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm p-6">
                <div class="sk sk-line" style="width:30%"></div>
                <div class="sk-grid mt-4">
                    <div class="sk sk-line" style="width:90%"></div>
                    <div class="sk sk-line" style="width:80%"></div>
                    <div class="sk sk-line" style="width:85%"></div>
                    <div class="sk sk-line" style="width:70%"></div>
                </div>
                <div class="sk-grid mt-6">
                    <div class="sk sk-line" style="width:88%"></div>
                    <div class="sk sk-line" style="width:76%"></div>
                    <div class="sk sk-line" style="width:92%"></div>
                    <div class="sk sk-line" style="width:66%"></div>
                </div>
                <div class="sk sk-line mt-6" style="width:40%; height:40px"></div>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm p-6 h-fit">
                <div class="sk sk-line" style="width:60%"></div>
                <div class="sk sk-line" style="width:90%"></div>
                <div class="sk sk-line" style="width:70%"></div>
            </div>
        </div>
    </div>

    <main id="cp-confirm-content" class="max-w-7xl mx-auto px-4 py-8">
        @include('charging-points.partials._creation-stepper', ['currentStep' => 4])

        @if(session('error'))
            <div class="mb-6 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4">
                <div class="flex items-start gap-3">
                    <svg class="h-5 w-5 text-red-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <h3 class="text-sm font-medium text-red-800 dark:text-red-200">Erreur</h3>
                        <p class="mt-1 text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        @endif
        
        {{-- Afficher les erreurs de validation --}}
        @if($errors->any())
            <div class="mb-6 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4">
                <div class="flex items-start gap-3">
                    <svg class="h-5 w-5 text-red-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <h3 class="text-sm font-medium text-red-800 dark:text-red-200">Erreurs de validation</h3>
                        <ul class="mt-1 text-sm text-red-700 dark:text-red-300 list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif
        
        @if(!session('charging_point_step1') || !session('charging_point_step2') || !session('charging_point_step3'))
            <div class="mb-6 rounded-xl bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 p-4">
                <div class="flex items-start gap-3">
                    <svg class="h-5 w-5 text-yellow-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.72-1.36 3.485 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Données manquantes</h3>
                        <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
                            Certaines données de session sont manquantes. Veuillez recommencer depuis le début.
                        </p>
                        <a href="{{ route('charging-points.create.step1') }}" class="mt-3 inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-yellow-800 bg-yellow-100 hover:bg-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-200 dark:hover:bg-yellow-900/50">
                            Recommencer
                        </a>
                    </div>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm">
                <form action="{{ route('charging-points.create.store.final') }}" method="POST" id="confirmForm">
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
                                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ session('charging_point_step1.manufacturer') ?? session('charging_point_step2.manufacturer') ?? 'Non spécifié' }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="font-medium text-gray-500 dark:text-gray-400">Modèle</dt>
                                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ session('charging_point_step1.model') ?? session('charging_point_step2.model') ?? 'Non spécifié' }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="font-medium text-gray-500 dark:text-gray-400">Puissance</dt>
                                        <dd class="mt-1 text-gray-900 dark:text-gray-100">
                                            @if(session('charging_point_step2.power_output'))
                                                @if(session('charging_point_step2.power_output') == '0' || session('charging_point_step2.power_output') == 0)
                                                    Autre (non spécifié)
                                                @else
                                                    {{ session('charging_point_step2.power_output') }} kW
                                                @endif
                                            @else
                                                Non spécifié
                                            @endif
                                        </dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="font-medium text-gray-500 dark:text-gray-400">Connecteur</dt>
                                        <dd class="mt-1 text-gray-900 dark:text-gray-100">
                                            @php
                                                $connectorType = session('charging_point_step2.connector_type', 'Non spécifié');
                                                $connectorLabels = [
                                                    'type1' => 'Type 1',
                                                    'type2' => 'Type 2',
                                                    'chademo' => 'CHAdeMO',
                                                    'ccs' => 'CCS',
                                                    'other' => 'Autre'
                                                ];
                                            @endphp
                                            {{ $connectorLabels[$connectorType] ?? ucfirst($connectorType) }}
                                        </dd>
                                    </div>
                                    @if(isset($planId) || session('charging_point_step2.pricing_plan_id'))
                                    <div class="sm:col-span-1">
                                        <dt class="font-medium text-gray-500 dark:text-gray-400">Plan tarifaire</dt>
                                        <dd class="mt-1 text-gray-900 dark:text-gray-100">
                                            {{ $planName ?? ('ID: ' . ($planId ?? session('charging_point_step2.pricing_plan_id'))) }}
                                        </dd>
                                    </div>
                                    @endif
                                    @if(session('charging_point_step2.installation_date'))
                                    <div class="sm:col-span-1">
                                        <dt class="font-medium text-gray-500 dark:text-gray-400">Date d'installation</dt>
                                        <dd class="mt-1 text-gray-900 dark:text-gray-100">
                                            @php
                                                try {
                                                    $installDate = \Carbon\Carbon::parse(session('charging_point_step2.installation_date'))->format('d/m/Y');
                                                } catch (\Exception $e) {
                                                    $installDate = session('charging_point_step2.installation_date');
                                                }
                                            @endphp
                                            {{ $installDate }}
                                        </dd>
                                    </div>
                                    @endif
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
                                        <dt class="font-medium text-gray-500 dark:text-gray-400">Type de connexion</dt>
                                        <dd class="mt-1 text-gray-900 dark:text-gray-100">
                                            @php
                                                $connectionType = session('charging_point_step3.connection_type') ?? session('charging_point_step2.connection_type', 'Non spécifié');
                                                $connectionLabels = [
                                                    'ethernet' => 'Ethernet',
                                                    'wifi' => 'Wi-Fi',
                                                    'gsm' => 'GSM/4G',
                                                    'other' => 'Autre'
                                                ];
                                            @endphp
                                            {{ $connectionLabels[$connectionType] ?? ucfirst($connectionType) }}
                                        </dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="font-medium text-gray-500 dark:text-gray-400">Protocole de communication</dt>
                                        <dd class="mt-1 text-gray-900 dark:text-gray-100">
                                            @php
                                                $protocol = session('charging_point_step3.communication_protocol') ?? session('charging_point_step2.communication_protocol', 'Non spécifié');
                                                $protocolLabels = [
                                                    'ocpp16' => 'OCPP 1.6',
                                                    'ocpp20' => 'OCPP 2.0',
                                                    'proprietary' => 'Propriétaire',
                                                    'other' => 'Autre'
                                                ];
                                            @endphp
                                            {{ $protocolLabels[$protocol] ?? ucfirst($protocol) }}
                                        </dd>
                                    </div>
                                    @if(session('charging_point_step3.ip_address'))
                                    <div class="sm:col-span-1">
                                        <dt class="font-medium text-gray-500 dark:text-gray-400">Adresse IP</dt>
                                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ session('charging_point_step3.ip_address') }}</dd>
                                    </div>
                                    @endif
                                    @if(session('charging_point_step3.mac_address'))
                                    <div class="sm:col-span-1">
                                        <dt class="font-medium text-gray-500 dark:text-gray-400">Adresse MAC</dt>
                                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ session('charging_point_step3.mac_address') }}</dd>
                                    </div>
                                    @endif
                                    @if(session('charging_point_step3.firmware_version'))
                                    <div class="sm:col-span-1">
                                        <dt class="font-medium text-gray-500 dark:text-gray-400">Version firmware</dt>
                                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ session('charging_point_step3.firmware_version') }}</dd>
                                    </div>
                                    @endif
                                    @if(session('charging_point_step3.server_url'))
                                    <div class="sm:col-span-1">
                                        <dt class="font-medium text-gray-500 dark:text-gray-400">URL serveur</dt>
                                        <dd class="mt-1 text-gray-900 dark:text-gray-100 break-all">{{ session('charging_point_step3.server_url') }}</dd>
                                    </div>
                                    @endif
                                    @if(session('charging_point_step3.access_type'))
                                    <div class="sm:col-span-1">
                                        <dt class="font-medium text-gray-500 dark:text-gray-400">Type d'accès</dt>
                                        <dd class="mt-1 text-gray-900 dark:text-gray-100">
                                            @php
                                                $accessType = session('charging_point_step3.access_type') ?? session('charging_point_step2.accessibility', 'public');
                                                $accessLabels = [
                                                    'public' => 'Public',
                                                    'private' => 'Privé',
                                                    'restricted' => 'Restreint'
                                                ];
                                            @endphp
                                            {{ $accessLabels[$accessType] ?? ucfirst($accessType) }}
                                        </dd>
                                    </div>
                                    @endif
                                </dl>
                            </div>
                            
                            <!-- Section: Location -->
                            @if(session('charging_point_step1.address') || session('charging_point_step1.city') || session('charging_point_step1.latitude'))
                            <div>
                                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4 pb-3 border-b border-gray-200/80 dark:border-gray-800/80 flex items-center gap-3">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-300">📍</span>
                                    Localisation
                                </h3>
                                <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                                    @if(session('charging_point_step1.address'))
                                    <div class="sm:col-span-2">
                                        <dt class="font-medium text-gray-500 dark:text-gray-400">Adresse</dt>
                                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ session('charging_point_step1.address') }}</dd>
                                    </div>
                                    @endif
                                    @if(session('charging_point_step1.city'))
                                    <div class="sm:col-span-1">
                                        <dt class="font-medium text-gray-500 dark:text-gray-400">Ville</dt>
                                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ session('charging_point_step1.city') }}</dd>
                                    </div>
                                    @endif
                                    @if(session('charging_point_step1.postal_code'))
                                    <div class="sm:col-span-1">
                                        <dt class="font-medium text-gray-500 dark:text-gray-400">Code postal</dt>
                                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ session('charging_point_step1.postal_code') }}</dd>
                                    </div>
                                    @endif
                                    @if(session('charging_point_step1.latitude') && session('charging_point_step1.longitude'))
                                    <div class="sm:col-span-1">
                                        <dt class="font-medium text-gray-500 dark:text-gray-400">Latitude</dt>
                                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ session('charging_point_step1.latitude') }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="font-medium text-gray-500 dark:text-gray-400">Longitude</dt>
                                        <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ session('charging_point_step1.longitude') }}</dd>
                                    </div>
                                    @endif
                                </dl>
                            </div>
                            @endif
                        </div>
                    </div>
                    
                    <div class="px-6 py-4 bg-gray-50/80 dark:bg-gray-900/40 border-t border-gray-200/80 dark:border-gray-800/80 rounded-b-2xl flex items-center justify-between">
                        <a href="{{ route('charging-points.create.step3') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 ring-1 ring-gray-200 dark:ring-gray-700 transition">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                            Précédent
                        </a>
                        
                        <button type="submit" id="submitButton" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 shadow-sm transition active:scale-[.99]">
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
    (function() {
        // Immediately hide skeleton and show content (CSS handles skeleton hiding)
        function revealConfirm() {
            var sk = document.getElementById('cp-confirm-skeleton');
            var ct = document.getElementById('cp-confirm-content');
            if (sk) sk.style.display = 'none';
            if (ct) ct.style.display = 'block';
        }
        // Run immediately
        revealConfirm();
        // Also run after DOM loads
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', revealConfirm);
        }
    })();
    document.addEventListener('DOMContentLoaded', function() {
        // Connecter le bouton du panneau latéral au formulaire principal (si présent)
        const submitButton = document.getElementById('submitButton');
        const confirmForm = document.getElementById('confirmForm');

        if (!submitButton || !confirmForm) {
            console.error('Form elements not found: submitButton=', submitButton, 'confirmForm=', confirmForm);
            return; // Rien à faire si l'un des éléments n'existe pas
        }

        // Ajouter un gestionnaire d'événements pour le bouton de soumission
        submitButton.addEventListener('click', function(e) {
            // Empêcher la soumission par défaut
            e.preventDefault();
            
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
        
        // Allow submission with Enter key
        confirmForm.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                submitButton.click();
            }
        });
    });
</script>
@endpush
@endsection
