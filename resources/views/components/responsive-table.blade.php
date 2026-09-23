@props([
    'headers' => [],
    'responsive' => true,
    'class' => ''
])

<div class="evon-table-container {{ $class }}">
    @if(isset($title))
        <div class="evon-table-header">
            <h3 class="evon-table-title">{{ $title }}</h3>
            @if(isset($actions))
                <div class="evon-table-actions">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif
    
    <div class="evon-table-wrapper">
        <table class="evon-table {{ $responsive ? 'evon-table-responsive' : '' }}">
            @if(count($headers) > 0)
                <thead class="evon-table-head">
                    <tr>
                        @foreach($headers as $header)
                            <th class="evon-table-head-cell">{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
            @endif
            
            <tbody class="evon-table-body">
                {{ $slot }}
            </tbody>
        </table>
    </div>
    
    @if(isset($pagination))
        <div class="evon-table-pagination">
            {{ $pagination }}
        </div>
    @endif
</div>

