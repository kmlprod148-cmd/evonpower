@extends('layouts.app')

@section('title', __('Profil Utilisateur') . ' - ' . $user->name)
@section('page-title', __('Profil Utilisateur'))

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-16 h-16 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-white text-2xl font-bold">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                </div>
                <div class="ml-4">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $user->name }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                    <div class="mt-1 flex flex-wrap gap-1">
                        @foreach ($user->roles as $role)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-900/30 dark:text-primary-300">
                                {{ ucfirst($role->name) }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-3">
                @can('update', $user)
                <a href="{{ route('users.edit', $user) }}"
                   class="inline-flex items-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">
                    <i class="fas fa-edit mr-2"></i>
                    {{ __('Modifier') }}
                </a>
                @endcan
                <a href="{{ route('users.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>
                    {{ __('Retour') }}
                </a>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-400 p-4 rounded-lg">
            <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Personal Info -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fas fa-user text-primary-500 mr-2"></i>
                    {{ __('Informations Personnelles') }}
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Nom') }}</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white font-medium">{{ $user->name }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Email') }}</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->email }}</p>
                    </div>
                    @if ($user->phone)
                    <div>
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Téléphone') }}</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->phone }}</p>
                    </div>
                    @endif
                    @if ($user->address)
                    <div>
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Adresse') }}</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->address }}</p>
                    </div>
                    @endif
                    @if ($user->city)
                    <div>
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Ville') }}</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->city }} {{ $user->postal_code }}</p>
                    </div>
                    @endif
                    @if ($user->country)
                    <div>
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Pays') }}</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->country }}</p>
                    </div>
                    @endif
                    <div>
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Créé le') }}</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->created_at?->format('d/m/Y H:i') }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Statut') }}</label>
                        <p class="mt-1">
                            @if ($user->email_verified_at)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                    <i class="fas fa-check mr-1"></i> {{ __('Vérifié') }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                                    <i class="fas fa-clock mr-1"></i> {{ __('Non vérifié') }}
                                </span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar: Organization -->
        <div class="space-y-6">
            <!-- Organization Info -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fas fa-building text-indigo-500 mr-2"></i>
                    {{ __('Organisation') }}
                </h2>
                <div class="space-y-3">
                    <div>
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Rôle(s)') }}</label>
                        <div class="mt-1 flex flex-wrap gap-1">
                            @forelse ($user->roles as $role)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-900/30 dark:text-primary-300">
                                    {{ ucfirst($role->name) }}
                                </span>
                            @empty
                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('Aucun rôle') }}</span>
                            @endforelse
                        </div>
                    </div>
                    @if ($user->integrator)
                    <div>
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Intégrateur') }}</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->integrator->name }}</p>
                    </div>
                    @endif
                    @if ($user->partner)
                    <div>
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Partenaire') }}</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->partner->name }}</p>
                    </div>
                    @endif
                    @if ($user->creator)
                    <div>
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Créé par') }}</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->creator->name }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Actions -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fas fa-cog text-gray-500 mr-2"></i>
                    {{ __('Actions') }}
                </h2>
                <div class="space-y-2">
                    @can('update', $user)
                    <a href="{{ route('users.edit', $user) }}"
                       class="w-full inline-flex items-center justify-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">
                        <i class="fas fa-edit mr-2"></i>
                        {{ __('Modifier le profil') }}
                    </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
