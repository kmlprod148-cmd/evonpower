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
                    <h1 class="text-xl lg:text-2xl font-bold text-gray-900">{{ __('Créer un Tag OCPP') }}</h1>
                    <p class="text-sm text-gray-500">{{ __('Créez un nouvel identifiant pour l\'authentification OCPP') }}</p>
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
                <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
                    <h2 class="text-lg font-semibold text-white flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        {{ __('Nouveau Tag OCPP') }}
                    </h2>
                </div>

                <form action="{{ route('ocpp-tags.store') }}" method="POST" class="p-6 space-y-6">
                    @csrf

                    <!-- ID Tag -->
                    <div>
                        <label for="id_tag" class="block text-sm font-medium text-gray-700">
                            {{ __('ID Tag') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="id_tag" id="id_tag" required
                            value="{{ old('id_tag') }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                            placeholder="Ex: Open10Tag, UserCard001"
                            pattern="[a-zA-Z0-9_-]+"
                            title="{{ __('Uniquement lettres, chiffres, tirets et underscores') }}">
                        <p class="mt-1 text-sm text-gray-500">{{ __('Identifiant unique (lettres, chiffres, tirets, underscores)') }}</p>
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
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                            placeholder="{{ __('Description ou commentaire sur ce tag...') }}">{{ old('note') }}</textarea>
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
                            <input type="datetime-local" name="expiry_date" id="expiry_date"
                                value="{{ old('expiry_date') }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                            <p class="mt-1 text-sm text-gray-500">{{ __('Laisser vide pour aucune expiration') }}</p>
                            @error('expiry_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Max Active Transactions -->
                        <div>
                            <label for="max_active_transaction_count" class="block text-sm font-medium text-gray-700">
                                {{ __('Max Transactions Actives') }} <span class="text-red-500">*</span>
                            </label>
                            <select name="max_active_transaction_count" id="max_active_transaction_count"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                                <option value="-1" {{ old('max_active_transaction_count', -1) == -1 ? 'selected' : '' }}>{{ __('Illimité') }} (-1)</option>
                                <option value="0" {{ old('max_active_transaction_count') === '0' ? 'selected' : '' }}>{{ __('Bloqué') }} (0)</option>
                                @for($i = 1; $i <= 20; $i++)
                                <option value="{{ $i }}" {{ old('max_active_transaction_count') == $i ? 'selected' : '' }}>{{ $i }}</option>
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
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                            <option value="">{{ __('Aucun (tag racine)') }}</option>
                            @foreach($existingTags as $existingTag)
                                <option value="{{ $existingTag['idTag'] }}" {{ old('parent_id_tag') === $existingTag['idTag'] ? 'selected' : '' }}>
                                    {{ $existingTag['idTag'] }}
                                    @if(!empty($existingTag['note']))
                                        - {{ \Str::limit($existingTag['note'], 30) }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-sm text-gray-500">{{ __('Optionnel: tag parent pour la hiérarchie') }}</p>
                        @error('parent_id_tag')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Info box -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-blue-800">{{ __('Information') }}</h3>
                                <div class="mt-2 text-sm text-blue-700">
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>{{ __('L\'ID Tag sera utilisé pour identifier les utilisateurs lors des sessions de charge') }}</li>
                                        <li>{{ __('Un tag peut être associé à une carte RFID ou utilisé via l\'application') }}</li>
                                        <li>{{ __('Définir Max Transactions à 0 bloque le tag') }}</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <a href="{{ route('ocpp-tags.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50">
                            {{ __('Annuler') }}
                        </a>
                        <button type="submit" class="inline-flex items-center px-6 py-2 bg-green-600 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white hover:bg-green-700 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            {{ __('Créer le Tag') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

