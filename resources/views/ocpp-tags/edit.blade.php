@extends('layouts.app')

@section('content')
<div class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="border-b border-gray-200 bg-white">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex items-center py-4">
                <a href="{{ route('ocpp-tags.index') }}" class="mr-4 text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div>
                    <h1 class="text-xl lg:text-2xl font-bold text-gray-900">{{ __('Modifier le Tag OCPP') }}</h1>
                    <p class="text-sm text-gray-500">{{ $tag['idTag'] ?? 'N/A' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="w-full px-4 sm:px-6 lg:px-8 py-6">
        @if(session('error'))
            <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm" role="alert">
                <p>{{ session('error') }}</p>
            </div>
        @endif

        <div class="max-w-2xl mx-auto">
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                    <h2 class="text-lg font-semibold text-white flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        {{ __('Modifier') }}: {{ $tag['idTag'] ?? 'N/A' }}
                    </h2>
                </div>

                <form action="{{ route('ocpp-tags.update', $tag['ocppTagPk']) }}" method="POST" class="p-6 space-y-6">
                    @csrf
                    @method('PUT')

                    <!-- ID Tag (read-only ou modifiable) -->
                    <div>
                        <label for="id_tag" class="block text-sm font-medium text-gray-700">
                            {{ __('ID Tag') }}
                        </label>
                        <input type="text" name="id_tag" id="id_tag"
                            value="{{ old('id_tag', $tag['idTag'] ?? '') }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            pattern="[a-zA-Z0-9_-]+">
                        <p class="mt-1 text-sm text-gray-500">{{ __('Attention: modifier l\'ID Tag peut affecter les autorisations existantes') }}</p>
                        @error('id_tag')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Note -->
                    <div>
                        <label for="note" class="block text-sm font-medium text-gray-700">
                            {{ __('Note / Description') }}
                        </label>
                        <textarea name="note" id="note" rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="{{ __('Description ou commentaire sur ce tag...') }}">{{ old('note', $tag['note'] ?? '') }}</textarea>
                        @error('note')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Date d'expiration -->
                        <div>
                            <label for="expiry_date" class="block text-sm font-medium text-gray-700">
                                {{ __('Date d\'Expiration') }}
                            </label>
                            @php
                                $expiryDate = old('expiry_date');
                                if (!$expiryDate && !empty($tag['expiryDate'])) {
                                    $expiryDate = \Carbon\Carbon::parse($tag['expiryDate'])->format('Y-m-d\TH:i');
                                }
                            @endphp
                            <input type="datetime-local" name="expiry_date" id="expiry_date"
                                value="{{ $expiryDate }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <p class="mt-1 text-sm text-gray-500">{{ __('Laisser vide pour aucune expiration') }}</p>
                            @error('expiry_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Max Active Transactions -->
                        <div>
                            <label for="max_active_transaction_count" class="block text-sm font-medium text-gray-700">
                                {{ __('Max Transactions Actives') }}
                            </label>
                            @php $currentMax = old('max_active_transaction_count', $tag['maxActiveTransactionCount'] ?? -1); @endphp
                            <select name="max_active_transaction_count" id="max_active_transaction_count"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="-1" {{ $currentMax == -1 ? 'selected' : '' }}>{{ __('Illimité') }} (-1)</option>
                                <option value="0" {{ $currentMax == 0 ? 'selected' : '' }}>{{ __('Bloqué') }} (0)</option>
                                @for($i = 1; $i <= 20; $i++)
                                <option value="{{ $i }}" {{ $currentMax == $i ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                            <p class="mt-1 text-sm text-gray-500">{{ __('-1 = Illimité, 0 = Bloqué') }}</p>
                            @error('max_active_transaction_count')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Parent ID Tag -->
                    <div>
                        <label for="parent_id_tag" class="block text-sm font-medium text-gray-700">
                            {{ __('Tag Parent') }}
                        </label>
                        <select name="parent_id_tag" id="parent_id_tag"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">{{ __('Aucun (tag racine)') }}</option>
                            @foreach($existingTags as $existingTag)
                                @if(($existingTag['idTag'] ?? '') !== ($tag['idTag'] ?? ''))
                                <option value="{{ $existingTag['idTag'] }}" {{ old('parent_id_tag', $tag['parentIdTag'] ?? '') === $existingTag['idTag'] ? 'selected' : '' }}>
                                    {{ $existingTag['idTag'] }}
                                    @if(!empty($existingTag['note']))
                                        - {{ \Str::limit($existingTag['note'], 30) }}
                                    @endif
                                </option>
                                @endif
                            @endforeach
                        </select>
                        @error('parent_id_tag')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Statut actuel -->
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <h3 class="text-sm font-medium text-gray-700 mb-3">{{ __('Statut Actuel') }}</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500">{{ __('Transactions Actives') }}:</span>
                                <span class="font-semibold ml-1">{{ $tag['activeTransactionCount'] ?? 0 }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">{{ __('En Transaction') }}:</span>
                                <span class="font-semibold ml-1 {{ ($tag['inTransaction'] ?? false) ? 'text-yellow-600' : 'text-green-600' }}">
                                    {{ ($tag['inTransaction'] ?? false) ? __('Oui') : __('Non') }}
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-500">{{ __('Bloqué') }}:</span>
                                <span class="font-semibold ml-1 {{ ($tag['blocked'] ?? false) ? 'text-red-600' : 'text-green-600' }}">
                                    {{ ($tag['blocked'] ?? false) ? __('Oui') : __('Non') }}
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-500">PK:</span>
                                <span class="font-mono ml-1">{{ $tag['ocppTagPk'] ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-between pt-4 border-t">
                        <form action="{{ route('ocpp-tags.destroy', $tag['ocppTagPk']) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('Êtes-vous sûr de vouloir supprimer ce tag ?') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-red-300 shadow-sm text-sm font-medium rounded-lg text-red-700 bg-white hover:bg-red-50">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                {{ __('Supprimer') }}
                            </button>
                        </form>
                        <div class="flex gap-3">
                            <a href="{{ route('ocpp-tags.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50">
                                {{ __('Annuler') }}
                            </a>
                            <button type="submit" class="inline-flex items-center px-6 py-2 bg-blue-600 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white hover:bg-blue-700 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ __('Enregistrer') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

