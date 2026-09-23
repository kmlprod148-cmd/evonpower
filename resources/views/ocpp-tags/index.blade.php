@extends('layouts.app')

@section('content')
<div class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="border-b border-gray-200 bg-white">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between py-4">
                <div>
                    <h1 class="text-xl lg:text-2xl font-bold text-gray-900">{{ __('Gestion des Tags OCPP') }}</h1>
                    <p class="text-sm text-gray-500">{{ __('Gérez les identifiants utilisés pour l\'authentification et les sessions de charge') }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('ocpp-tags.export') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        {{ __('Exporter CSV') }}
                    </a>
                    @if(auth()->user()->hasRole(['admin', 'super_admin']))
                    <a href="{{ route('ocpp-tags.create') }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white hover:bg-green-700 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        {{ __('Nouveau Tag') }}
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="w-full px-4 sm:px-6 lg:px-8 py-6">
        @if(session('success'))
            <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm" role="alert">
                <p>{{ session('success') }}</p>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm" role="alert">
                <p>{{ session('error') }}</p>
            </div>
        @endif

        <!-- Statistiques -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">{{ __('Total Tags') }}</p>
                        <p class="text-2xl font-semibold text-gray-900" id="stat-total">{{ $stats['total'] }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">{{ __('Actifs') }}</p>
                        <p class="text-2xl font-semibold text-green-600" id="stat-active">{{ $stats['active'] }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">{{ __('Bloqués') }}</p>
                        <p class="text-2xl font-semibold text-red-600" id="stat-blocked">{{ $stats['blocked'] }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">{{ __('En Transaction') }}</p>
                        <p class="text-2xl font-semibold text-yellow-600" id="stat-in-transaction">{{ $stats['in_transaction'] }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-gray-500 rounded-md p-3">
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">{{ __('Expirés') }}</p>
                        <p class="text-2xl font-semibold text-gray-600" id="stat-expired">{{ $stats['expired'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tag par défaut -->
        <div class="rounded-lg shadow-lg p-6 mb-6" style="background: linear-gradient(to right, #4f46e5, #4338ca);">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h3 class="text-lg font-semibold flex items-center" style="color: white;">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color: white;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                        </svg>
                        {{ __('Tag par Défaut') }}
                    </h3>
                    <p class="text-sm mt-1" style="color: #c7d2fe;">{{ __('Ce tag est utilisé pour les opérations de démarrage rapide') }}</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <div class="inline-flex items-center px-6 py-3 bg-white rounded-lg shadow">
                        <code class="text-xl font-bold text-indigo-700">{{ $defaultTag }}</code>
                        @if($defaultTagInfo['success'])
                            <span class="ml-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <span class="h-2 w-2 mr-1 rounded-full bg-green-500"></span>
                                {{ __('Valide') }}
                            </span>
                        @else
                            <span class="ml-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                <span class="h-2 w-2 mr-1 rounded-full bg-red-500"></span>
                                {{ __('Non trouvé') }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="p-4 border-b border-gray-200">
                <h3 class="font-semibold text-gray-700">{{ __('Filtres') }}</h3>
            </div>
            <div class="p-4">
                <form action="{{ route('ocpp-tags.index') }}" method="GET" class="flex flex-wrap gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('ID Tag') }}</label>
                        <input type="text" name="idTag" value="{{ $filters['idTag'] ?? '' }}" 
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                            placeholder="Rechercher par ID Tag...">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Statut Expiration') }}</label>
                        <select name="expired" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">{{ __('Tous') }}</option>
                            <option value="FALSE" {{ ($filters['expired'] ?? '') === 'FALSE' ? 'selected' : '' }}>{{ __('Non Expirés') }}</option>
                            <option value="TRUE" {{ ($filters['expired'] ?? '') === 'TRUE' ? 'selected' : '' }}>{{ __('Expirés') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('En Transaction') }}</label>
                        <select name="inTransaction" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">{{ __('Tous') }}</option>
                            <option value="TRUE" {{ ($filters['inTransaction'] ?? '') === 'TRUE' ? 'selected' : '' }}>{{ __('Oui') }}</option>
                            <option value="FALSE" {{ ($filters['inTransaction'] ?? '') === 'FALSE' ? 'selected' : '' }}>{{ __('Non') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Bloqué') }}</label>
                        <select name="blocked" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">{{ __('Tous') }}</option>
                            <option value="TRUE" {{ ($filters['blocked'] ?? '') === 'TRUE' ? 'selected' : '' }}>{{ __('Oui') }}</option>
                            <option value="FALSE" {{ ($filters['blocked'] ?? '') === 'FALSE' ? 'selected' : '' }}>{{ __('Non') }}</option>
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors text-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            {{ __('Filtrer') }}
                        </button>
                        <a href="{{ route('ocpp-tags.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors text-sm">
                            {{ __('Réinitialiser') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Liste des tags -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('ID Tag') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Note') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Parent') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Expiration') }}</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Max Trans.') }}</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Actives') }}</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Statut') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($tags as $tag)
                        @php
                            $isExpired = !empty($tag['expiryDate']) && \Carbon\Carbon::parse($tag['expiryDate'])->isPast();
                            $isDefault = ($tag['idTag'] ?? '') === $defaultTag;
                        @endphp
                        <tr class="hover:bg-gray-50 {{ $isDefault ? 'bg-indigo-50' : '' }}">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <code class="text-sm font-mono font-semibold {{ $isDefault ? 'text-indigo-700' : 'text-gray-900' }}">
                                        {{ $tag['idTag'] ?? 'N/A' }}
                                    </code>
                                    @if($isDefault)
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">
                                            {{ __('Défaut') }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ \Str::limit($tag['note'] ?? '-', 30) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @if(!empty($tag['parentIdTag']))
                                    <code class="bg-gray-100 px-2 py-1 rounded text-xs">{{ $tag['parentIdTag'] }}</code>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if(!empty($tag['expiryDate']))
                                    <span class="{{ $isExpired ? 'text-red-600' : 'text-gray-600' }}">
                                        {{ \Carbon\Carbon::parse($tag['expiryDate'])->format('d/m/Y H:i') }}
                                    </span>
                                    @if($isExpired)
                                        <span class="text-xs text-red-500 block">{{ __('Expiré') }}</span>
                                    @endif
                                @else
                                    <span class="text-gray-400">{{ __('Jamais') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @php $maxTrans = $tag['maxActiveTransactionCount'] ?? -1; @endphp
                                @if($maxTrans == 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        {{ __('Bloqué') }}
                                    </span>
                                @elseif($maxTrans < 0)
                                    <span class="text-green-600 font-semibold">∞</span>
                                @else
                                    <span class="font-semibold">{{ $maxTrans }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ ($tag['activeTransactionCount'] ?? 0) > 0 ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $tag['activeTransactionCount'] ?? 0 }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex justify-center gap-1">
                                    @if($tag['blocked'] ?? false)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            {{ __('Bloqué') }}
                                        </span>
                                    @endif
                                    @if($tag['inTransaction'] ?? false)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            {{ __('En charge') }}
                                        </span>
                                    @endif
                                    @if(!($tag['blocked'] ?? false) && !($tag['inTransaction'] ?? false) && !$isExpired)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            {{ __('OK') }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('ocpp-tags.show', $tag['ocppTagPk']) }}" class="text-indigo-600 hover:text-indigo-900" title="{{ __('Voir') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    @if(auth()->user()->hasRole(['admin', 'super_admin']))
                                    <a href="{{ route('ocpp-tags.edit', $tag['ocppTagPk']) }}" class="text-blue-600 hover:text-blue-900" title="{{ __('Modifier') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                    <form action="{{ route('ocpp-tags.destroy', $tag['ocppTagPk']) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('Êtes-vous sûr de vouloir supprimer ce tag ?') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900" title="{{ __('Supprimer') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                                <p class="text-gray-500">{{ __('Aucun tag OCPP trouvé') }}</p>
                                @if(auth()->user()->hasRole(['admin', 'super_admin']))
                                <a href="{{ route('ocpp-tags.create') }}" class="mt-4 inline-flex items-center px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700">
                                    {{ __('Créer un premier tag') }}
                                </a>
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Auto-refresh stats every 30 seconds
setInterval(function() {
    fetch('{{ route("ocpp-tags.refresh-stats") }}')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('stat-total').textContent = data.stats.total;
                document.getElementById('stat-active').textContent = data.stats.active;
                document.getElementById('stat-blocked').textContent = data.stats.blocked;
                document.getElementById('stat-in-transaction').textContent = data.stats.in_transaction;
                document.getElementById('stat-expired').textContent = data.stats.expired;
            }
        })
        .catch(error => console.log('Error refreshing stats:', error));
}, 30000);
</script>
@endpush
@endsection

