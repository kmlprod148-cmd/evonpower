@props([
    'name',
    'label' => null,
    'id' => null,
    'value' => null,
    'placeholder' => null,
    'rows' => 3,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'maxlength' => null,
    'minlength' => null,
    'help' => null,
    'icon' => null,
    'error' => null,
    'class' => ''
])

<div class="form-textarea-wrapper">
    @if($label)
        <div class="flex items-center justify-between mb-1">
            <label 
                for="{{ $id ?? $name }}" 
                class="block text-sm font-medium text-gray-700 {{ $required ? 'font-semibold' : '' }}"
            >
                <div class="flex items-center space-x-2">
                    {{-- Optional Icon --}}
                    @if($icon)
                        <span class="text-gray-500">
                            @switch($icon)
                                @case('comment')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                    </svg>
                                @case('note')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M16 3h5v5L9.5 20.5 4 15l11.5-11.5z"></path>
                                        <line x1="16" y1="5" x2="19" y2="8"></line>
                                    </svg>
                            @endswitch
                        </span>
                    @endif

                    {{-- Label Text --}}
                    <span>{{ $label }}</span>

                    {{-- Required Indicator --}}
                    @if($required)
                        <span class="text-red-500">*</span>
                    @endif
                </div>
            </label>

            {{-- Character Counter (if maxlength is set) --}}
            @if($maxlength)
                <div 
                    x-data="{ 
                        count: '{{ old($name, $value ?? '') }}' ? '{{ strlen(old($name, $value ?? '')) }}' : '0', 
                        maxlength: {{ $maxlength }} 
                    }"
                    class="text-xs text-gray-500"
                >
                    <span x-text="count"></span>/<span>{{ $maxlength }}</span>
                </div>
            @endif
        </div>
    @endif

    <div class="relative">
        <textarea 
            name="{{ $name }}"
            id="{{ $id ?? $name }}"
            rows="{{ $rows }}"
            placeholder="{{ $placeholder }}"
            {{ $required ? 'required' : '' }}
            {{ $disabled ? 'disabled' : '' }}
            {{ $readonly ? 'readonly' : '' }}
            {{ $maxlength ? "maxlength={$maxlength}" : '' }}
            {{ $minlength ? "minlength={$minlength}" : '' }}
            x-data="{ 
                @if($maxlength)
                updateCounter(value) {
                    this.$nextTick(() => {
                        this.count = value ? value.length : 0;
                    });
                }
                @endif
            }"
            @if($maxlength)
                @input="updateCounter($event.target.value)"
            @endif
            class="
                w-full 
                rounded-md 
                border-gray-300 
                shadow-sm 
                focus:border-green-500 
                focus:ring 
                focus:ring-green-200 
                focus:ring-opacity-50
                {{ $disabled ? 'bg-gray-100 cursor-not-allowed' : '' }}
                {{ $readonly ? 'bg-gray-50' : '' }}
                {{ $class }}
                {{ $error || $errors->has($name) ? 'border-red-500' : '' }}
            "
        >{{ old($name, $value) }}</textarea>
    </div>

    {{-- Help Text --}}
    @if($help)
        <p class="mt-1 text-xs text-gray-500">{{ $help }}</p>
    @endif

    {{-- Error Message --}}
    @if($error || $errors->has($name))
        <p class="mt-1 text-sm text-red-600">
            {{ $error ?? $errors->first($name) }}
        </p>
    @endif
</div>

{{-- Alpine.js for interactivity --}}
@push('scripts')
<script src="{{ asset('js/vendor/alpine.min.js') }}" defer></script>
@endpush