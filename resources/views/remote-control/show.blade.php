@extends('layouts.app')

@section('title', __('Contrôle Distant') . ' - ' . ($chargingPoint->name ?? ''))
@section('page-title', __('Contrôle Distant'))

@section('content')
<div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="bg-purple-100 dark:bg-purple-900/30 rounded-lg p-3 mr-4">
                    <i class="fas fa-satellite-dish text-2xl text-purple-600 dark:text-purple-400"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $chargingPoint->name ?? __('Borne') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ID: {{ $chargingPoint->charge_point_id ?? $chargingPoint->id }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                @php $status = $connectionStatus['status'] ?? 'unknown'; @endphp
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium
                    {{ $status === 'Available' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-200' :
                       ($status === 'Charging' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-200' :
                       'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300') }}">
                    <i class="fas fa-circle text-xs mr-1.5"></i>{{ $status }}
                </span>
                <a href="{{ route('remote-control.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>{{ __('Retour') }}
                </a>
            </div>
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Remote Actions -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Start Charging -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-play text-green-500 mr-2"></i>{{ __('Démarrer une charge') }}
                </h2>
                <form method="POST" action="{{ route('remote-control.start', $chargingPoint) }}" class="space-y-3">
                    @csrf
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('Connecteur') }}</label>
                            <input type="number" name="connector_id" value="1" min="1"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('Tag OCPP') }}</label>
                            <input type="text" name="id_tag" placeholder="RFID ou badge"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                    </div>
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-play mr-2"></i>{{ __('Démarrer') }}
                    </button>
                </form>
            </div>

            <!-- Stop Charging -->
            @if (!empty($activeSessions) && count($activeSessions) > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-stop text-red-500 mr-2"></i>{{ __('Sessions actives') }}
                </h2>
                <div class="space-y-2">
                    @foreach ($activeSessions as $session)
                    <div class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ __('Transaction #') }}{{ $session['transaction_id'] ?? $session->id ?? '–' }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Connecteur') }}: {{ $session['connector_id'] ?? 1 }}</p>
                        </div>
                        <form method="POST" action="{{ route('remote-control.stop', $chargingPoint) }}">
                            @csrf
                            <input type="hidden" name="transaction_id" value="{{ $session['transaction_id'] ?? $session->id }}">
                            <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-red-600 text-white text-xs font-medium rounded hover:bg-red-700 transition-colors">
                                <i class="fas fa-stop mr-1"></i>{{ __('Arrêter') }}
                            </button>
                        </form>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Reset -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-sync text-yellow-500 mr-2"></i>{{ __('Réinitialisation') }}
                </h2>
                <div class="flex gap-3">
                    <form method="POST" action="{{ route('remote-control.reset', $chargingPoint) }}">
                        @csrf
                        <input type="hidden" name="type" value="Soft">
                        <button type="submit" onclick="return confirm('{{ __('Effectuer un Soft Reset ?') }}')"
                                class="inline-flex items-center px-4 py-2 bg-yellow-500 text-white text-sm font-medium rounded-lg hover:bg-yellow-600 transition-colors">
                            <i class="fas fa-undo mr-2"></i>{{ __('Soft Reset') }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('remote-control.reset', $chargingPoint) }}">
                        @csrf
                        <input type="hidden" name="type" value="Hard">
                        <button type="submit" onclick="return confirm('{{ __('Effectuer un Hard Reset ?') }}')"
                                class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                            <i class="fas fa-power-off mr-2"></i>{{ __('Hard Reset') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Info -->
        <div class="space-y-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4">{{ __('Informations') }}</h2>
                <dl class="space-y-2 text-sm">
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase">{{ __('Statut') }}</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $connectionStatus['status'] ?? '–' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase">{{ __('Connecté') }}</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">
                            {{ ($connectionStatus['connected'] ?? false) ? __('Oui') : __('Non') }}
                        </dd>
                    </div>
                </dl>
            </div>
            <a href="{{ route('remote-control.logs', $chargingPoint) }}"
               class="flex items-center justify-center w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                <i class="fas fa-list mr-2"></i>{{ __('Voir les logs') }}
            </a>
        </div>
    </div>
</div>
@endsection
