@props(['chargingPoint'])

<div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-all duration-200 group">
    <!-- Card Header -->
    <div class="p-6 border-b border-gray-100">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900 group-hover:text-primary-600 transition-colors">
                    {{ $chargingPoint->name }}
                </h3>
                <p class="text-sm text-gray-500 mt-1 flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    {{ $chargingPoint->location }}
                </p>
            </div>
            <div class="flex items-center space-x-2">
                <!-- Status Indicator -->
                <div class="flex items-center">
                    <div class="w-3 h-3 rounded-full {{ $chargingPoint->status === 'online' ? 'bg-green-500' : ($chargingPoint->status === 'offline' ? 'bg-red-500' : 'bg-yellow-500') }}"></div>
                    <span class="ml-2 text-sm font-medium {{ $chargingPoint->status === 'online' ? 'text-green-700' : ($chargingPoint->status === 'offline' ? 'text-red-700' : 'text-yellow-700') }}">
                        {{ ucfirst($chargingPoint->status) }}
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Card Content -->
    <div class="p-6">
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Puissance</p>
                <p class="text-lg font-semibold text-gray-900">{{ $chargingPoint->power_output ?? 'N/A' }} kW</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Connecteur</p>
                <p class="text-lg font-semibold text-gray-900">{{ $chargingPoint->connector_type ?? 'Type 2' }}</p>
            </div>
        </div>
        
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-500">
                <p class="font-medium">{{ $chargingPoint->manufacturer }} {{ $chargingPoint->model }}</p>
                <p class="text-xs">ID: {{ $chargingPoint->id }}</p>
            </div>
            <div class="flex items-center space-x-2">
                <a href="{{ route('charging-points.show', $chargingPoint) }}" 
                   class="inline-flex items-center px-3 py-1.5 bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium rounded-lg transition-colors">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    Voir
                </a>
                <button onclick="quickActions({{ $chargingPoint->id }})" 
                        class="inline-flex items-center px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
