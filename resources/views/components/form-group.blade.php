{{-- components/form-group.blade.php --}}
@props(['name', 'label', 'value' => '', 'type' => 'text', 'step' => null, 'required' => false])

<div class="form-group">
    <label for="{{ $name }}" class="form-label {{ $required ? 'required-field' : '' }}">{{ $label }}</label>
    
    @if($type === 'textarea')
        <textarea 
            id="{{ $name }}" 
            name="{{ $name }}" 
            class="form-input @error($name) border-red-500 @enderror"
            {{ $required ? 'required' : '' }}
        >{{ old($name, $value) }}</textarea>
    @else
        <input 
            type="{{ $type }}" 
            id="{{ $name }}" 
            name="{{ $name }}" 
            value="{{ old($name, $value) }}" 
            @if($step) step="{{ $step }}" @endif
            class="form-input @error($name) border-red-500 @enderror"
            {{ $required ? 'required' : '' }}
        >
    @endif
    
    @error($name)
        <p class="form-error">{{ $message }}</p>
    @enderror
</div>