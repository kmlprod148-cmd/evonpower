@extends('layouts.app')

@section('title', __('Démarrer une Charge'))
@section('page-title', __('Démarrer une Charge'))

@section('content')
<div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="bg-green-100 dark:bg-green-900/30 rounded-lg p-3 mr-4">
                    <i class="fas fa-bolt text-2xl text-green-600 dark:text-green-400"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $data['charging_point']->name ?? __('Démarrer une Charge') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $data['charging_point']->location ?? '' }}</p>
                </div>
            </div>
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
            <form method="POST" action="{{ route('charging.start.session', $data['charging_point']->id) }}" class="space-y-4">
                @csrf

                @if (!empty($data['connectors']) && count($data['connectors']) > 0)
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Connecteur') }} <span class="text-red-500">*</span></label>
                    <div class="space-y-2">
                        @foreach ($data['connectors'] as $connector)
                        <label class="flex items-center gap-3 p-3 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:border-green-400 transition-colors">
                            <input type="radio" name="connector_id" value="{{ $connector->id }}" required class="text-green-600">
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ __('Connecteur') }} #{{ $connector->id }}</p>
                                @if ($connector->type)
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $connector->type }}</p>
                                @endif
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>
                @else
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Connecteur') }} <span class="text-red-500">*</span></label>
                    <input type="number" name="connector_id" value="1" min="1"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Email (optionnel)') }}</label>
                    <input type="email" name="email" value="{{ old('email', auth()->user()?->email) }}"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Plaque d\'immatriculation') }}</label>
                    <input type="text" name="vehicle_plate" value="{{ old('vehicle_plate') }}" maxlength="20"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>

                @if ($data['pricing_plan'])
                <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('Tarif applicable') }}: {{ $data['pricing_plan']->name }}</h3>
                    @if ($data['pricing_plan']->price_per_kwh)
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ number_format($data['pricing_plan']->price_per_kwh, 4) }} {{ strtoupper($data['pricing_plan']->currency ?? 'EUR') }}/kWh</p>
                    @endif
                </div>
                @endif

                <div class="pt-2">
                    <button type="submit"
                            class="w-full inline-flex items-center justify-center px-4 py-3 bg-green-600 text-white font-semibold rounded-xl hover:bg-green-700 transition-colors">
                        <i class="fas fa-bolt mr-2"></i>{{ __('Démarrer la charge') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
