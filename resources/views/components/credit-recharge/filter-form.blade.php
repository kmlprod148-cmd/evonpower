@props(['filters' => []])

<form method="GET" action="{{ route('credit-recharge.index') }}" class="flex flex-wrap items-center gap-3">
    <div class="relative">
        <select name="status" 
                class="pl-10 pr-4 py-2.5 text-sm border-2 border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 dark:bg-gray-800 dark:text-white appearance-none bg-white dark:bg-gray-800 transition-all">
            <option value="">{{ __('Tous les statuts') }}</option>
            <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>{{ __('En attente') }}</option>
            <option value="processing" {{ ($filters['status'] ?? '') === 'processing' ? 'selected' : '' }}>{{ __('En traitement') }}</option>
            <option value="completed" {{ ($filters['status'] ?? '') === 'completed' ? 'selected' : '' }}>{{ __('Complétée') }}</option>
            <option value="failed" {{ ($filters['status'] ?? '') === 'failed' ? 'selected' : '' }}>{{ __('Échouée') }}</option>
            <option value="cancelled" {{ ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' }}>{{ __('Annulée') }}</option>
        </select>
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
    </div>
    
    <div class="relative">
        <select name="payment_method" 
                class="pl-10 pr-4 py-2.5 text-sm border-2 border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 dark:bg-gray-800 dark:text-white appearance-none bg-white dark:bg-gray-800 transition-all">
            <option value="">{{ __('Toutes les méthodes') }}</option>
            <option value="offline" {{ ($filters['payment_method'] ?? '') === 'offline' ? 'selected' : '' }}>{{ __('Hors ligne') }}</option>
            <option value="cmi" {{ ($filters['payment_method'] ?? '') === 'cmi' ? 'selected' : '' }}>CMI</option>
            <option value="stripe" {{ ($filters['payment_method'] ?? '') === 'stripe' ? 'selected' : '' }}>Stripe</option>
        </select>
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
        </div>
    </div>
    
    <button type="submit" 
            class="inline-flex items-center px-4 py-2.5 text-sm bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white font-semibold rounded-lg transition-all shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
        </svg>
        {{ __('Filtrer') }}
    </button>
    
    @if(!empty($filters))
        <a href="{{ route('credit-recharge.index') }}" 
           class="inline-flex items-center px-4 py-2.5 text-sm bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg transition-all shadow-md hover:shadow-lg">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            {{ __('Réinitialiser') }}
        </a>
    @endif
</form>

