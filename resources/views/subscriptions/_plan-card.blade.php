@php
    $totalPrice = round($plan->price * (1 + $plan->vat_rate / 100), 2);
    $durationLabel = match($plan->type) {
        'monthly'     => '/ mois',
        'quarterly'   => '/ trim.',
        'semi_annual' => '/ 6 mois',
        'annual'      => '/ an',
        default       => '',
    };
    $isFeatured = $plan->is_featured ?? false;
@endphp

<div class="relative flex flex-col rounded-2xl border-2 transition-all duration-300 hover:shadow-xl hover:-translate-y-1 overflow-hidden
    {{ $isFeatured
        ? 'border-eco-green-500 dark:border-eco-green-500 shadow-lg shadow-eco-green-500/20'
        : 'border-gray-200 dark:border-gray-700 hover:border-eco-green-300 dark:hover:border-eco-green-700' }}
    bg-white dark:bg-gray-800">

    {{-- Featured accent top bar --}}
    @if($isFeatured)
    <div class="h-1.5 bg-gradient-to-r from-eco-green-400 to-eco-green-600 w-full"></div>
    @endif

    {{-- Featured badge --}}
    @if($isFeatured)
    <div class="absolute top-4 right-4">
        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-eco-green-500 text-white text-xs font-bold rounded-full shadow-md shadow-eco-green-500/30">
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
            Populaire
        </span>
    </div>
    @endif

    <div class="p-5 flex flex-col flex-1">
        {{-- Plan name & type --}}
        <div class="mb-4">
            <h4 class="font-bold text-gray-900 dark:text-gray-100 text-base leading-tight mb-1 pr-20">{{ $plan->name }}</h4>
            <span class="inline-block px-2 py-0.5 text-xs font-medium rounded-full bg-eco-green-50 dark:bg-eco-green-900/20 text-eco-green-700 dark:text-eco-green-400">
                {{ $plan->type_label }}
            </span>
        </div>

        {{-- Price --}}
        <div class="mb-4 pb-4 border-b border-gray-100 dark:border-gray-700">
            <div class="flex items-end gap-1">
                <span class="text-3xl font-extrabold text-gray-900 dark:text-gray-100">{{ number_format($plan->price, 2, ',', ' ') }}</span>
                <span class="text-sm text-gray-500 dark:text-gray-400 mb-1">€ HT{{ $durationLabel ? ' '.$durationLabel : '' }}</span>
            </div>
            @if($plan->vat_rate > 0)
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ number_format($totalPrice, 2, ',', ' ') }} € TTC</p>
            @endif
        </div>

        {{-- Limits --}}
        <div class="space-y-2 mb-4 text-xs flex-1">
            @if($plan->max_sessions)
            <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                <svg class="w-3.5 h-3.5 text-eco-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                <span>{{ $plan->max_sessions }} sessions incluses</span>
            </div>
            @endif
            @if($plan->max_kwh)
            <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                <svg class="w-3.5 h-3.5 text-eco-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                <span>{{ number_format($plan->max_kwh, 0) }} kWh inclus</span>
            </div>
            @endif
            @if($plan->max_duration_minutes)
            <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                <svg class="w-3.5 h-3.5 text-eco-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ number_format($plan->max_duration_minutes / 60, 0) }}h de charge incluses</span>
            </div>
            @endif
            @if(!$plan->max_sessions && !$plan->max_kwh && !$plan->max_duration_minutes)
            <div class="flex items-center gap-2 text-eco-green-600 dark:text-eco-green-400 font-medium">
                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>Sessions illimitées</span>
            </div>
            @endif

            {{-- Features --}}
            @if($plan->features && is_array($plan->features))
                @foreach(array_slice($plan->features, 0, 3) as $feature)
                <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                    <svg class="w-3.5 h-3.5 text-eco-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>{{ $feature }}</span>
                </div>
                @endforeach
            @endif
        </div>

        {{-- CTA Buttons --}}
        <div class="space-y-2 mt-auto">
            <a href="{{ route('subscriptions.checkout', $plan) }}"
               class="block text-center font-semibold text-sm py-2.5 px-4 rounded-xl transition-all duration-200
               {{ $isFeatured
                   ? 'bg-eco-green-500 hover:bg-eco-green-600 text-white shadow-md shadow-eco-green-500/30 hover:shadow-lg hover:shadow-eco-green-500/40'
                   : 'bg-eco-green-50 hover:bg-eco-green-100 dark:bg-eco-green-900/20 dark:hover:bg-eco-green-900/40 text-eco-green-700 dark:text-eco-green-300 border border-eco-green-200 dark:border-eco-green-800' }}">
                Souscrire
            </a>
            <a href="{{ route('subscriptions.plans.show', $plan) }}"
               class="block text-center text-xs font-medium text-gray-500 dark:text-gray-400 hover:text-eco-green-600 dark:hover:text-eco-green-400 transition-colors py-1">
                Voir les détails →
            </a>
        </div>
    </div>
</div>
