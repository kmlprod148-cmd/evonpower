@php
    $steps = $steps ?? ['Informations générales', 'Spécifications techniques', 'Connectivité', 'Confirmation'];
    $currentStep = $currentStep ?? 1;
@endphp
<div class="mb-8">
    <ol class="relative flex items-center justify-between gap-4 sm:gap-6">
        @foreach($steps as $i => $label)
            @php $active = ($i + 1) <= $currentStep; @endphp
            <li class="flex-1 relative">
                @if($i < count($steps) - 1)
                    <div class="absolute top-1/2 -translate-y-1/2 left-5 right-0 h-1 rounded-full {{ $active ? 'bg-emerald-200 dark:bg-emerald-900/40' : 'bg-gray-200 dark:bg-gray-800' }}"></div>
                @endif
                <div class="relative z-10 flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-full border {{ $active ? 'bg-emerald-500 text-white border-emerald-500 shadow-emerald-500/30 shadow-md' : 'bg-white dark:bg-gray-900 text-gray-500 border-gray-200 dark:border-gray-800' }}">
                        {{ $i + 1 }}
                    </span>
                    <span class="hidden sm:block text-sm {{ $active ? 'text-emerald-700 dark:text-emerald-300 font-medium' : 'text-gray-500 dark:text-gray-400' }}">{{ $label }}</span>
                </div>
            </li>
        @endforeach
    </ol>
</div>