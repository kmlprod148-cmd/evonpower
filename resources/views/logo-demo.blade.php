{{-- Page de démonstration des logos avec arrière-plan gris --}}
@extends('layouts.compact')

@section('title', 'Démonstration des Logos')
@section('description', 'Différents styles de logos avec arrière-plan gris')

@section('content')
<div class="compact-full-width">
    <!-- Header -->
    <div class="compact-card compact-mb-4">
        <div class="compact-card-header">
            <h3 class="compact-text-lg compact-font-semibold compact-text-gray-900 compact-m-0">
                🎨 Démonstration des Logos avec Arrière-plan Gris
            </h3>
            <p class="compact-text-sm compact-text-gray-600 compact-m-0 compact-mt-1">
                Différents styles et tailles de logos avec arrière-plan gris
            </p>
        </div>
    </div>

    <!-- Logos avec icônes -->
    <div class="compact-card compact-mb-4">
        <div class="compact-card-header">
            <h4 class="compact-text-base compact-font-semibold compact-text-gray-900 compact-m-0">
                Logos avec Icônes
            </h4>
        </div>
        <div class="compact-card-body">
            <div class="compact-flex compact-flex-wrap compact-gap-4 compact-items-center">
                <!-- Logo normal -->
                <div class="compact-flex compact-flex-col compact-items-center compact-gap-2">
                    <span class="compact-text-xs compact-text-gray-600">Normal</span>
                    <x-logo 
                        size="normal"
                        type="icon"
                        :icon="'<svg class=\"w-8 h-8 text-blue-600\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M13 10V3L4 14h7v7l9-11h-7z\"/></svg>'"
                        text="EVON"
                        href="#"
                    />
                </div>

                <!-- Logo compact -->
                <div class="compact-flex compact-flex-col compact-items-center compact-gap-2">
                    <span class="compact-text-xs compact-text-gray-600">Compact</span>
                    <x-logo 
                        size="compact"
                        type="icon"
                        :icon="'<svg class=\"w-6 h-6 text-blue-600\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M13 10V3L4 14h7v7l9-11h-7z\"/></svg>'"
                        text="EVON"
                        href="#"
                    />
                </div>

                <!-- Logo small -->
                <div class="compact-flex compact-flex-col compact-items-center compact-gap-2">
                    <span class="compact-text-xs compact-text-gray-600">Small</span>
                    <x-logo 
                        size="small"
                        type="icon"
                        :icon="'<svg class=\"w-4 h-4 text-blue-600\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M13 10V3L4 14h7v7l9-11h-7z\"/></svg>'"
                        text="EVON"
                        href="#"
                    />
                </div>
            </div>
        </div>
    </div>

    <!-- Logos avec images -->
    <div class="compact-card compact-mb-4">
        <div class="compact-card-header">
            <h4 class="compact-text-base compact-font-semibold compact-text-gray-900 compact-m-0">
                Logos avec Images
            </h4>
        </div>
        <div class="compact-card-body">
            <div class="compact-flex compact-flex-wrap compact-gap-4 compact-items-center">
                <!-- Logo avec image normale -->
                <div class="compact-flex compact-flex-col compact-items-center compact-gap-2">
                    <span class="compact-text-xs compact-text-gray-600">Avec Image</span>
                    <x-logo 
                        size="normal"
                        type="image"
                        :image="asset('images/logo2.png')"
                        text="EVON"
                        href="#"
                    />
                </div>

                <!-- Logo avec image compact -->
                <div class="compact-flex compact-flex-col compact-items-center compact-gap-2">
                    <span class="compact-text-xs compact-text-gray-600">Compact</span>
                    <x-logo 
                        size="compact"
                        type="image"
                        :image="asset('images/logo2.png')"
                        text="EVON"
                        href="#"
                    />
                </div>

                <!-- Logo avec image small -->
                <div class="compact-flex compact-flex-col compact-items-center compact-gap-2">
                    <span class="compact-text-xs compact-text-gray-600">Small</span>
                    <x-logo 
                        size="small"
                        type="image"
                        :image="asset('images/logo2.png')"
                        text="EVON"
                        href="#"
                    />
                </div>
            </div>
        </div>
    </div>

    <!-- Logos avec texte seulement -->
    <div class="compact-card compact-mb-4">
        <div class="compact-card-header">
            <h4 class="compact-text-base compact-font-semibold compact-text-gray-900 compact-m-0">
                Logos avec Texte Seulement
            </h4>
        </div>
        <div class="compact-card-body">
            <div class="compact-flex compact-flex-wrap compact-gap-4 compact-items-center">
                <!-- Logo texte normal -->
                <div class="compact-flex compact-flex-col compact-items-center compact-gap-2">
                    <span class="compact-text-xs compact-text-gray-600">Normal</span>
                    <x-logo 
                        size="normal"
                        type="text"
                        text="EVON"
                        href="#"
                    />
                </div>

                <!-- Logo texte compact -->
                <div class="compact-flex compact-flex-col compact-items-center compact-gap-2">
                    <span class="compact-text-xs compact-text-gray-600">Compact</span>
                    <x-logo 
                        size="compact"
                        type="text"
                        text="EVON"
                        href="#"
                    />
                </div>

                <!-- Logo texte small -->
                <div class="compact-flex compact-flex-col compact-items-center compact-gap-2">
                    <span class="compact-text-xs compact-text-gray-600">Small</span>
                    <x-logo 
                        size="small"
                        type="text"
                        text="EVON"
                        href="#"
                    />
                </div>
            </div>
        </div>
    </div>

    <!-- Différentes icônes -->
    <div class="compact-card compact-mb-4">
        <div class="compact-card-header">
            <h4 class="compact-text-base compact-font-semibold compact-text-gray-900 compact-m-0">
                Différentes Icônes
            </h4>
        </div>
        <div class="compact-card-body">
            <div class="compact-grid compact-grid-3 compact-gap-4">
                <!-- Logo avec icône éclair -->
                <div class="compact-flex compact-flex-col compact-items-center compact-gap-2">
                    <span class="compact-text-xs compact-text-gray-600">Éclair</span>
                    <x-logo 
                        size="compact"
                        type="icon"
                        :icon="'<svg class=\"w-6 h-6 text-blue-600\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M13 10V3L4 14h7v7l9-11h-7z\"/></svg>'"
                        text="EVON"
                        href="#"
                    />
                </div>

                <!-- Logo avec icône batterie -->
                <div class="compact-flex compact-flex-col compact-items-center compact-gap-2">
                    <span class="compact-text-xs compact-text-gray-600">Batterie</span>
                    <x-logo 
                        size="compact"
                        type="icon"
                        :icon="'<svg class=\"w-6 h-6 text-green-600\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4\"/></svg>'"
                        text="EVON"
                        href="#"
                    />
                </div>

                <!-- Logo avec icône voiture -->
                <div class="compact-flex compact-flex-col compact-items-center compact-gap-2">
                    <span class="compact-text-xs compact-text-gray-600">Voiture</span>
                    <x-logo 
                        size="compact"
                        type="icon"
                        :icon="'<svg class=\"w-6 h-6 text-purple-600\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z\"/><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1a1 1 0 001-1v-3a1 1 0 00-1-1H9a1 1 0 00-1 1v3z\"/></svg>'"
                        text="EVON"
                        href="#"
                    />
                </div>
            </div>
        </div>
    </div>

    <!-- États interactifs -->
    <div class="compact-card compact-mb-4">
        <div class="compact-card-header">
            <h4 class="compact-text-base compact-font-semibold compact-text-gray-900 compact-m-0">
                États Interactifs
            </h4>
        </div>
        <div class="compact-card-body">
            <div class="compact-flex compact-flex-wrap compact-gap-4 compact-items-center">
                <!-- Logo normal -->
                <div class="compact-flex compact-flex-col compact-items-center compact-gap-2">
                    <span class="compact-text-xs compact-text-gray-600">Normal</span>
                    <x-logo 
                        size="compact"
                        type="icon"
                        :icon="'<svg class=\"w-6 h-6 text-blue-600\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M13 10V3L4 14h7v7l9-11h-7z\"/></svg>'"
                        text="EVON"
                        href="#"
                    />
                </div>

                <!-- Logo avec hover -->
                <div class="compact-flex compact-flex-col compact-items-center compact-gap-2">
                    <span class="compact-text-xs compact-text-gray-600">Hover (survolez)</span>
                    <x-logo 
                        size="compact"
                        type="icon"
                        :icon="'<svg class=\"w-6 h-6 text-blue-600\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M13 10V3L4 14h7v7l9-11h-7z\"/></svg>'"
                        text="EVON"
                        href="#"
                        class="hover:scale-105"
                    />
                </div>

                <!-- Logo avec focus -->
                <div class="compact-flex compact-flex-col compact-items-center compact-gap-2">
                    <span class="compact-text-xs compact-text-gray-600">Focus</span>
                    <x-logo 
                        size="compact"
                        type="icon"
                        :icon="'<svg class=\"w-6 h-6 text-blue-600\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M13 10V3L4 14h7v7l9-11h-7z\"/></svg>'"
                        text="EVON"
                        href="#"
                        class="focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    />
                </div>
            </div>
        </div>
    </div>

    <!-- Code d'exemple -->
    <div class="compact-card">
        <div class="compact-card-header">
            <h4 class="compact-text-base compact-font-semibold compact-text-gray-900 compact-m-0">
                Code d'Exemple
            </h4>
        </div>
        <div class="compact-card-body">
            <div class="compact-bg-gray-900 compact-text-green-400 compact-p-4 compact-rounded compact-font-mono compact-text-sm compact-overflow-x-auto">
                <div class="compact-mb-2">
                    <span class="compact-text-gray-500">// Logo avec icône</span>
                </div>
                <div class="compact-mb-2">
                    <span class="compact-text-blue-400">&lt;x-logo</span>
                </div>
                <div class="compact-mb-2">
                    <span class="compact-text-yellow-400">    size="compact"</span>
                </div>
                <div class="compact-mb-2">
                    <span class="compact-text-yellow-400">    type="icon"</span>
                </div>
                <div class="compact-mb-2">
                    <span class="compact-text-yellow-400">    :icon="'&lt;svg&gt;...&lt;/svg&gt;'"</span>
                </div>
                <div class="compact-mb-2">
                    <span class="compact-text-yellow-400">    text="EVON"</span>
                </div>
                <div class="compact-mb-2">
                    <span class="compact-text-yellow-400">    :href="route('dashboard')"</span>
                </div>
                <div class="compact-mb-2">
                    <span class="compact-text-blue-400">/&gt;</span>
                </div>
                <div class="compact-mt-4 compact-mb-2">
                    <span class="compact-text-gray-500">// Logo avec image</span>
                </div>
                <div class="compact-mb-2">
                    <span class="compact-text-blue-400">&lt;x-logo</span>
                </div>
                <div class="compact-mb-2">
                    <span class="compact-text-yellow-400">    size="normal"</span>
                </div>
                <div class="compact-mb-2">
                    <span class="compact-text-yellow-400">    type="image"</span>
                </div>
                <div class="compact-mb-2">
                    <span class="compact-text-yellow-400">    :image="asset('images/logo.png')"</span>
                </div>
                <div class="compact-mb-2">
                    <span class="compact-text-yellow-400">    text="EVON"</span>
                </div>
                <div class="compact-mb-2">
                    <span class="compact-text-blue-400">/&gt;</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
