@extends('layouts.app')

@section('title', __('Aperçu Rapport Financier'))
@section('page-title', __('Aperçu Rapport Financier'))

@section('content')
<div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="bg-yellow-100 dark:bg-yellow-900/30 rounded-lg p-3 mr-4">
                    <i class="fas fa-chart-pie text-2xl text-yellow-600 dark:text-yellow-400"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Aperçu Rapport Financier') }}</h1>
                    @if (!empty($filters['date_from']) || !empty($filters['date_to']))
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('Période:') }} {{ $filters['date_from'] ?? '–' }} {{ __('au') }} {{ $filters['date_to'] ?? '–' }}
                        </p>
                    @endif
                </div>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.reports.financial.export', array_merge($filters ?? [], ['format' => 'pdf'])) }}"
                   class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                    <i class="fas fa-file-pdf mr-2"></i>{{ __('Export PDF') }}
                </a>
                <a href="{{ route('admin.reports.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>{{ __('Retour') }}
                </a>
            </div>
        </div>
    </div>

    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex">
            <i class="fas fa-info-circle text-blue-500 mr-3 mt-0.5"></i>
            <p class="text-sm text-blue-700 dark:text-blue-300">
                {{ __('Cet aperçu affiche un résumé du rapport financier. Pour un rapport complet, utilisez l\'export PDF ou Excel.') }}
            </p>
        </div>
    </div>

    <!-- Placeholder content -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-8 text-center">
        <i class="fas fa-chart-bar text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ __('Aperçu en cours de génération') }}</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('Utilisez les boutons ci-dessus pour exporter le rapport dans le format souhaité.') }}</p>
        <div class="flex justify-center gap-3">
            <a href="{{ route('admin.reports.financial.export', array_merge($filters ?? [], ['format' => 'pdf'])) }}"
               class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                <i class="fas fa-file-pdf mr-2"></i>{{ __('Télécharger PDF') }}
            </a>
            <a href="{{ route('admin.reports.financial.export', array_merge($filters ?? [], ['format' => 'excel'])) }}"
               class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                <i class="fas fa-file-excel mr-2"></i>{{ __('Télécharger Excel') }}
            </a>
        </div>
    </div>
</div>
@endsection
