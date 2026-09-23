@extends('layouts.app')

@section('title', __('Associer un Tag OCPP'))
@section('page-title', __('Associer un Tag OCPP'))

@section('content')
<div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="bg-indigo-100 dark:bg-indigo-900/30 rounded-lg p-3 mr-4">
                    <i class="fas fa-link text-2xl text-indigo-600 dark:text-indigo-400"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Associer un Tag Existant') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Client:') }} {{ $client->name }}</p>
                </div>
            </div>
            <a href="{{ route('admin.clients.tags.index', $client) }}"
               class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>{{ __('Retour') }}
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-400 p-4 rounded-lg">
            <ul class="text-sm text-red-700 dark:text-red-300 space-y-1">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="max-w-lg">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <form method="POST" action="{{ route('admin.clients.tags.associate.store', $client) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Tag OCPP disponible') }} <span class="text-red-500">*</span></label>
                    @if ($availableTags->count() > 0)
                    <select name="ocpp_tag" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">{{ __('Sélectionner un tag') }}</option>
                        @foreach ($availableTags as $tag)
                            <option value="{{ $tag->ocpp_tag }}" {{ old('ocpp_tag') == $tag->ocpp_tag ? 'selected' : '' }}>
                                {{ $tag->ocpp_tag }}
                                @if ($tag->user_id == $client->id) ({{ __('déjà associé') }}) @endif
                            </option>
                        @endforeach
                    </select>
                    @else
                    <p class="text-sm text-gray-500 dark:text-gray-400 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg">{{ __('Aucun tag disponible') }}</p>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Note') }}</label>
                    <textarea name="note" rows="2"
                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">{{ old('note') }}</textarea>
                </div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="hidden" name="is_default" value="0">
                    <input type="checkbox" name="is_default" value="1" {{ old('is_default') ? 'checked' : '' }}
                           class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('Définir comme tag par défaut') }}</span>
                </label>
                @if ($availableTags->count() > 0)
                <div class="pt-2">
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">
                        <i class="fas fa-link mr-2"></i>{{ __('Associer') }}
                    </button>
                </div>
                @endif
            </form>
        </div>
    </div>
</div>
@endsection
