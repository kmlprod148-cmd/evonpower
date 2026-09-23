@extends('layouts.app')

@section('title', 'Diagnostic: Associations Groupe-Partenaire')

@section('content')
<div class="max-w-6xl mx-auto py-8 px-4">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
            Diagnostic: Associations Groupe-Partenaire
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
            Identifiez et corrigez les groupes incorrectement associés aux partenaires
        </p>
    </div>

    @if($issues->isEmpty())
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-6">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <h3 class="font-semibold text-green-900 dark:text-green-100">Aucun problème détecté</h3>
                    <p class="text-sm text-green-700 dark:text-green-300">Toutes les associations de groupes sont correctes</p>
                </div>
            </div>
        </div>
    @else
        <div class="space-y-6">
            @foreach($issues as $partnerId => $data)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <!-- Header -->
                    <div class="bg-amber-50 dark:bg-amber-900/20 border-b border-amber-200 dark:border-amber-800 px-6 py-4">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ $data['partner']->name }}
                                </h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    ID: {{ $data['partner']->id }} • {{ count($data['incorrect_groups']) }} groupe(s) à corriger
                                </p>
                            </div>
                            <div class="bg-amber-100 dark:bg-amber-900 px-4 py-2 rounded-lg">
                                <span class="text-amber-800 dark:text-amber-100 font-semibold">⚠️ Problème</span>
                            </div>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-6 space-y-4">
                        <div>
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Utilisateurs du partenaire</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                @foreach($data['users'] as $user)
                                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                                        <p class="font-medium text-blue-900 dark:text-blue-100">{{ $user->name }}</p>
                                        <p class="text-sm text-blue-700 dark:text-blue-300">{{ $user->email }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Groupes incorrectement associés</h4>
                            <div class="space-y-2">
                                @foreach($data['incorrect_groups'] as $group)
                                    <div class="flex items-center justify-between bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                                        <div class="flex-1">
                                            <p class="font-medium text-red-900 dark:text-red-100">{{ $group->name }}</p>
                                            <p class="text-sm text-red-700 dark:text-red-300 mt-1">
                                                ID: {{ $group->id }} • Créé par: {{ $group->user->name ?? 'N/A' }}
                                                <br>Partner ID actuel: {{ $group->partner_id ?? 'NULL' }} (devrait être: {{ $data['partner']->id }})
                                            </p>
                                        </div>
                                        <form method="POST" action="{{ route('admin.diagnostics.fix-group-association') }}" class="ml-4">
                                            @csrf
                                            <input type="hidden" name="group_id" value="{{ $group->id }}">
                                            <input type="hidden" name="partner_id" value="{{ $data['partner']->id }}">
                                            <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors">
                                                Corriger
                                            </button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Groupes correctement associés</h4>
                            <div class="space-y-2">
                                @if($data['correct_groups']->isEmpty())
                                    <p class="text-gray-500 dark:text-gray-400 text-sm italic">Aucun groupe correctement associé</p>
                                @else
                                    @foreach($data['correct_groups'] as $group)
                                        <div class="flex items-center gap-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                                            <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <div class="flex-1">
                                                <p class="font-medium text-green-900 dark:text-green-100">{{ $group->name }}</p>
                                                <p class="text-sm text-green-700 dark:text-green-300">ID: {{ $group->id }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Actions -->
        <div class="mt-8 flex gap-4">
            <form method="POST" action="{{ route('admin.diagnostics.fix-all-associations') }}" class="inline">
                @csrf
                <button type="submit" class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition-colors">
                    Corriger toutes les associations
                </button>
            </form>
            <a href="{{ route('admin.dashboard') }}" class="px-6 py-3 bg-gray-300 hover:bg-gray-400 text-gray-900 font-semibold rounded-lg transition-colors">
                Retour
            </a>
        </div>
    @endif
</div>
@endsection
