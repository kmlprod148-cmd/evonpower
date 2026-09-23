@if(file_exists(public_path('images/evon-logo.png')))
    <img src="{{ asset('images/evon-logo.png') }}" alt="EVON Logo" {{ $attributes }}>
@elseif(file_exists(public_path('images/logo.png')))
    <img src="{{ asset('images/logo.png') }}" alt="EVON Logo" {{ $attributes }}>
@else
    <div class="h-8 w-8 bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center" {{ $attributes }}>
        <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
        </svg>
    </div>
@endif
