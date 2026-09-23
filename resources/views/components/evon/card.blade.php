@props(['title' => null, 'padding' => true])

<div class="bg-white rounded-[15px] {{ $padding ? 'p-6' : '' }}" style="box-shadow: 0px 4px 4px rgba(0, 0, 0, 0.04);">
    @if($title)
        <h3 class="text-sm font-semibold text-black mb-4" style="font-family: 'Poppins', sans-serif; line-height: 20px;">
            {{ $title }}
        </h3>
    @endif
    
    {{ $slot }}
</div>