<div class="{{ $colClass ?? 'col-md-4' }}">
    <div class="card {{ $borderColor ?? 'border-danger' }}">
        <div class="card-header {{ $headerBgClass ?? 'bg-danger' }} {{ $headerTextColorClass ?? 'text-white' }}">
            <h6 class="mb-0">
                @if(isset($iconClass))
                    <i class="{{ $iconClass }}"></i>
                @endif
                {{ $title ?? '' }}
            </h6>
        </div>
        <div class="card-body">
            {{ $slot }}
        </div>
    </div>
</div>