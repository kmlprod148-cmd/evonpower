@props([
    'name',
    'label' => null,
    'id' => null,
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'disabled' => false,
    'multiple' => false,
    'help' => null,
    'icon' => null,
    'error' => null,
    'class' => '',
    'options' => [],
    'optionValue' => 'id',
    'optionLabel' => 'name'
])

<div class="form-select-wrapper">
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
                                @case('list')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="8" y1="6" x2="21" y2="6"></line>
                                        <line x1="8" y1="12" x2="21" y2="12"></line>
                                        <line x1="8" y1="18" x2="21" y2="18"></line>
                                        <line x1="3" y1="6" x2="3.01" y2="6"></line>
                                        <line x1="3" y1="12" x2="3.01" y2="12"></line>
                                        <line x1="3" y1="18" x2="3.01" y2="18"></line>
                                    </svg>
                                @case('category')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                                    </svg>
                                @case('filter')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
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
        </div>
    @endif

    <div class="relative">
        <select 
            name="{{ $name }}{{ $multiple ? '[]' : '' }}"
            id="{{ $id ?? $name }}"
            {{ $required ? 'required' : '' }}
            {{ $disabled ? 'disabled' : '' }}
            {{ $multiple ? 'multiple' : '' }}
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
                {{ $class }}
                {{ $error || $errors->has($name) ? 'border-red-500' : '' }}
            "
            x-data="selectComponent()"
            @if($multiple)
                x-model="selectedValues"
            @endif
        >
            {{-- Placeholder Option --}}
            @if($placeholder && !$multiple)
                <option value="" {{ !$value ? 'selected' : '' }} disabled>
                    {{ $placeholder }}
                </option>
            @endif

            {{-- Dynamic Options --}}
            @if(is_array($options) || $options instanceof \Traversable)
                @foreach($options as $option)
                    @php
                        $optionValueItem = is_array($option) 
                            ? $option[$optionValue] 
                            : $option->{$optionValue};
                        $optionLabelItem = is_array($option) 
                            ? $option[$optionLabel] 
                            : $option->{$optionLabel};
                    @endphp
                    <option 
                        value="{{ $optionValueItem }}"
                        {{ 
                            $multiple 
                                ? (is_array(old($name, $value)) && in_array($optionValueItem, old($name, $value)) ? 'selected' : '')
                                : (old($name, $value) == $optionValueItem ? 'selected' : '')
                        }}
                    >
                        {{ $optionLabelItem }}
                    </option>
                @endforeach
            @endif

            {{ $slot }}
        </select>

        {{-- Decorative arrow for custom styling --}}
        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                <path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z" />
            </svg>
        </div>
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

@push('scripts')
<script>
    function selectComponent() {
        return {
            @if($multiple)
            selectedValues: @js(old($name, $value ?? [])),
            @endif
        }
    }
</script>
<script src="{{ asset('js/vendor/alpine.min.js') }}" defer></script>
@endpush