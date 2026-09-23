@extends('layouts.app')

@section('title', __('Taux de TVA'))
@section('page-title', __('Taux de TVA'))

@section('content')
<div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="bg-blue-100 dark:bg-blue-900/30 rounded-lg p-3 mr-4">
                    <i class="fas fa-percent text-2xl text-blue-600 dark:text-blue-400"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Taux de TVA') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $vatRates->count() }} {{ __('taux configurés') }}</p>
                </div>
            </div>
            <a href="{{ route('admin.vat_rates.create') }}"
               class="inline-flex items-center px-4 py-2 bg-primary-600 text-green-900 text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">
                <i class="fas fa-plus mr-2"></i>{{ __('Nouveau taux') }}
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-400 p-4 rounded-lg">
            <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
        </div>
    @endif
    @if (session('error'))
        <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-400 p-4 rounded-lg">
            <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        @if ($vatRates->count() > 0)
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Nom') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Taux') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Statut') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Défaut') }}</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach ($vatRates as $vat)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="px-4 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ $vat->name }}</td>
                    <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $vat->rate }}%</td>
                    <td class="px-4 py-4">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $vat->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-200' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' }}">
                            {{ $vat->is_active ? __('Actif') : __('Inactif') }}
                        </span>
                    </td>
                    <td class="px-4 py-4">
                        @if ($vat->is_default)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-200">
                                <i class="fas fa-star mr-1"></i>{{ __('Défaut') }}
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.vat_rates.edit', $vat) }}"
                               class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded hover:bg-blue-700 transition-colors">
                                <i class="fas fa-edit mr-1"></i>{{ __('Modifier') }}
                            </a>
                            @if (!$vat->is_default)
                            <form method="POST" action="{{ route('admin.vat_rates.destroy', $vat) }}" onsubmit="return confirm('{{ __('Supprimer ce taux ?') }}')">
                                @csrf @method('DELETE')
                                <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-red-600 text-white text-xs font-medium rounded hover:bg-red-700 transition-colors">
                                    <i class="fas fa-trash mr-1"></i>{{ __('Supprimer') }}
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="p-12 text-center">
            <i class="fas fa-percent text-3xl text-gray-300 dark:text-gray-600 mb-3"></i>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Aucun taux de TVA configuré') }}</p>
            <a href="{{ route('admin.vat_rates.create') }}" class="mt-4 inline-flex items-center px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700">
                <i class="fas fa-plus mr-2"></i>{{ __('Ajouter un taux') }}
            </a>
        </div>
        @endif
    </div>
</div>
@endsection
