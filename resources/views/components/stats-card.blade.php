@props(['label', 'value', 'icon', 'trend'])

<div class="bg-white rounded-lg shadow p-6">
    <div class="flex justify-between">
        <div>
            <p class="text-gray-500">{{ $label }}</p>
            <p class="text-2xl font-bold">{{ $value }}</p>
        </div>
        <div class="p-3 rounded-full bg-indigo-50">
            <x-dynamic-component :component="'icons.' . $icon" class="w-6 h-6 text-indigo-600" />
        </div>
    </div>
    <!-- Trend indicator (optional) -->
    @if($trend)
        <div class="mt-2 flex items-center text-sm {{ $trend === 'up' ? 'text-green-500' : ($trend === 'down' ? 'text-red-500' : 'text-gray-500') }}">
            <x-icons.trend-arrow class="w-4 h-4 mr-1" />
            {{ $trend === 'up' ? '+12%' : ($trend === 'down' ? '-5%' : '0%') }}
        </div>
    @endif
</div>