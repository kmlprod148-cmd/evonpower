// resources/views/components/tab-bar.blade.php
@props(['tabs' => [], 'activeTab' => null, 'route' => null])

<div class="mb-6 border-b border-gray-200 dark:border-gray-700">
    <nav class="-mb-px flex space-x-8 overflow-x-auto" aria-label="Tabs">
        @foreach($tabs as $key => $tab)
            @php
                $isActive = $activeTab === $key;
                $href = $route ? route($route, ['tab' => $key]) : '#' . $key;
            @endphp
            
            <a 
                href="{{ $href }}"
                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ $isActive 
                    ? 'border-green-500 text-green-600 dark:text-green-400' 
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:border-gray-600' }}"
                aria-current="{{ $isActive ? 'page' : 'false' }}"
            >
                @if(isset($tab['icon']))
                    <span class="flex items-center">
                        <span class="mr-2">
                            {!! $tab['icon'] !!}
                        </span>
                        <span>{{ $tab['label'] }}</span>
                    </span>
                @else
                    {{ $tab['label'] }}
                @endif
                
                @if(isset($tab['badge']))
                    <span class="ml-2 {{ $isActive ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }} px-2 py-0.5 rounded-full text-xs">
                        {{ $tab['badge'] }}
                    </span>
                @endif
            </a>
        @endforeach
    </nav>
</div>