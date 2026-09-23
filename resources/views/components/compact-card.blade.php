@props([
    'title' => '',
    'subtitle' => '',
    'icon' => '',
    'actions' => false,
    'class' => ''
])

<div class="compact-card {{ $class }}">
    @if($title || $subtitle || $icon || $actions)
    <div class="compact-card-header">
        <div class="compact-flex compact-items-center compact-justify-between">
            <div class="compact-flex compact-items-center compact-gap-2">
                @if($icon)
                <div class="compact-text-blue-600">
                    {!! $icon !!}
                </div>
                @endif
                <div>
                    @if($title)
                    <h3 class="compact-text-lg compact-font-semibold compact-text-gray-900 compact-m-0">
                        {{ $title }}
                    </h3>
                    @endif
                    @if($subtitle)
                    <p class="compact-text-sm compact-text-gray-600 compact-m-0 compact-mt-1">
                        {{ $subtitle }}
                    </p>
                    @endif
                </div>
            </div>
            @if($actions)
            <div class="compact-flex compact-items-center compact-gap-1">
                {{ $actions }}
            </div>
            @endif
        </div>
    </div>
    @endif
    
    <div class="compact-card-body">
        {{ $slot }}
    </div>
</div>
