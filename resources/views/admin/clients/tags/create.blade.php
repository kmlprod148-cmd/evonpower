@extends('layouts.app')

@section('title', __('Nouveau Tag OCPP'))
@section('page-title', __('Nouveau Tag OCPP'))

@section('content')
<div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="bg-indigo-100 dark:bg-indigo-900/30 rounded-lg p-3 mr-4">
                    <i class="fas fa-id-card text-2xl text-indigo-600 dark:text-indigo-400"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Nouveau Tag OCPP') }}</h1>
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
            <form method="POST" action="{{ route('admin.clients.tags.store', $client) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Code Tag OCPP') }} <span class="text-red-500">*</span></label>
                    <input type="text" name="ocpp_tag" value="{{ old('ocpp_tag') }}" required
                           pattern="[a-zA-Z0-9_-]+" maxlength="50"
                           placeholder="ex: ABC123_TAG"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-mono bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Lettres, chiffres, tirets et underscores uniquement') }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Date d\'expiration') }}</label>
                    <input type="datetime-local" name="expiry_date" value="{{ old('expiry_date') }}"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
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
                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('Tag par défaut') }}</span>
                </label>
                <div class="pt-2">
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">
                        <i class="fas fa-save mr-2"></i>{{ __('Créer le tag') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
