@extends('layouts.app')

@section('content')
<div class="bg-white rounded-lg shadow-sm p-6">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold text-gray-800">Créer un nouveau rapport</h1>
        
        <div>
            <a href="{{ route('reports.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 -ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Retour à la liste
            </a>
        </div>
    </div>
    
    @if($errors->any())
        <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <ul>
                        @foreach($errors->all() as $error)
                            <li class="text-sm text-red-700">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif
    
    <form action="{{ route('reports.store') }}" method="POST">
        @csrf
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700">Titre du rapport</label>
                <input type="text" name="title" id="title" value="{{ old('title') }}" class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
            </div>
            
            <div>
                <label for="type" class="block text-sm font-medium text-gray-700">Type de rapport</label>
                <select name="type" id="type" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm">
                    <option value="usage" {{ old('type') == 'usage' ? 'selected' : '' }}>Utilisation</option>
                    <option value="financial" {{ old('type') == 'financial' ? 'selected' : '' }}>Financier</option>
                    <option value="performance" {{ old('type') == 'performance' ? 'selected' : '' }}>Performance</option>
                    <option value="custom" {{ old('type') == 'custom' ? 'selected' : '' }}>Personnalisé</option>
                </select>
            </div>
            
            <div class="md:col-span-2">
                <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                <textarea name="description" id="description" rows="3" class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">{{ old('description') }}</textarea>
            </div>
            
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700">Date de début</label>
                <input type="date" name="start_date" id="start_date" value="{{ old('start_date') }}" class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
            </div>
            
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700">Date de fin</label>
                <input type="date" name="end_date" id="end_date" value="{{ old('end_date') }}" class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
            </div>
            
            <div>
                <label for="partner_id" class="block text-sm font-medium text-gray-700">Partenaire</label>
                <select name="partner_id" id="partner_id" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm">
                    <option value="">Tous les partenaires</option>
                    @foreach($partners ?? [] as $partner)
                        <option value="{{ $partner->id }}" {{ old('partner_id') == $partner->id ? 'selected' : '' }}>
                            {{ $partner->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700">Statut</label>
                <select name="status" id="status" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm">
                    <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>Brouillon</option>
                    <option value="scheduled" {{ old('status') == 'scheduled' ? 'selected' : '' }}>Programmé</option>
                    <option value="published" {{ old('status') == 'published' ? 'selected' : '' }}>Publié</option>
                </select>
            </div>
            
            <div class="md:col-span-2">
                <label for="recipients" class="block text-sm font-medium text-gray-700">Destinataires (emails séparés par des virgules)</label>
                <input type="text" name="recipients" id="recipients" value="{{ old('recipients') }}" class="mt-1 focus:ring-green-500 focus:border-green-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
            </div>
            
            <div class="md:col-span-2">
                <fieldset>
                    <legend class="text-sm font-medium text-gray-700">Options du rapport</legend>
                    <div class="mt-2 space-y-2">
                        <div class="flex items-center">
                            <input id="include_charts" name="include_charts" type="checkbox" value="1" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded" {{ old('include_charts') ? 'checked' : '' }}>
                            <label for="include_charts" class="ml-2 block text-sm text-gray-700">Inclure les graphiques</label>
                        </div>
                        <div class="flex items-center">
                            <input id="include_summary" name="include_summary" type="checkbox" value="1" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded" {{ old('include_summary') ? 'checked' : '' }}>
                            <label for="include_summary" class="ml-2 block text-sm text-gray-700">Inclure un résumé</label>
                        </div>
                        <div class="flex items-center">
                            <input id="auto_send" name="auto_send" type="checkbox" value="1" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded" {{ old('auto_send') ? 'checked' : '' }}>
                            <label for="auto_send" class="ml-2 block text-sm text-gray-700">Envoi automatique</label>
                        </div>
                    </div>
                </fieldset>
            </div>
        </div>
        
        <div class="mt-8 flex justify-end">
            <button type="button" onclick="window.history.back()" class="mr-3 inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                Annuler
            </button>
            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                Créer
            </button>
        </div>
    </form>
</div>
@endsection