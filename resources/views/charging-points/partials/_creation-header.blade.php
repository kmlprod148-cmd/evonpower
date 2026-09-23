<header class="border-b border-gray-200/70 dark:border-gray-800/60 bg-white/70 dark:bg-gray-900/60 backdrop-blur">
    <div class="max-w-7xl mx-auto px-4 py-4">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ $backUrl ?? route('charging-points.index') }}"
                   class="inline-flex items-center justify-center rounded-xl ring-1 ring-gray-200 hover:ring-gray-300 dark:ring-gray-800 dark:hover:ring-gray-700 h-10 w-10 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-700 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0 7-7m-7 7h18"/>
                    </svg>
                </a>
                <div>
                    <p class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">Borne de recharge</p>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $title ?? 'Ajouter un point de charge' }}</h1>
                </div>
            </div>
            <div class="hidden sm:flex items-center gap-2">
                @if(isset($currentStep) && isset($totalSteps))
                <span class="inline-flex items-center gap-2 text-xs px-2.5 py-1.5 rounded-full bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-300 dark:ring-emerald-900">
                    Étape {{ $currentStep }} sur {{ $totalSteps }}
                </span>
                @endif
            </div>
        </div>
    </div>
</header>