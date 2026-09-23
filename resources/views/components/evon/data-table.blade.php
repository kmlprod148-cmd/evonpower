@props([
    'title' => '',
    'actions' => null,
    'headers' => [],
    'rows' => [],
    'pagination' => null
])

<div {{ $attributes->merge(['class' => 'evon-table-container']) }}>
    @if($title || $actions)
        <div class="evon-table-header">
            @if($title)
                <h3 class="evon-table-title">{{ $title }}</h3>
            @endif
            @if($actions)
                <div class="evon-table-actions">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif
    
    <div class="evon-table-wrapper">
        <table class="evon-table">
            @if(count($headers) > 0)
                <thead class="evon-table-head">
                    <tr>
                        @foreach($headers as $header)
                            <th class="evon-table-head-cell" scope="col">
                                {{ $header }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
            @endif
            
            <tbody class="evon-table-body">
                @if(count($rows) > 0)
                    @foreach($rows as $row)
                        <tr class="evon-table-row">
                            @foreach($row as $cell)
                                <td class="evon-table-cell">
                                    {{ $cell }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="{{ count($headers) }}" class="evon-table-cell text-center py-12">
                            <div class="evon-empty-state">
                                <svg class="evon-empty-state-icon mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                </svg>
                                <p class="text-gray-600 dark:text-gray-400">Aucune donnée disponible</p>
                            </div>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
    
    @if($pagination)
        <div class="evon-table-pagination">
            {{ $pagination }}
        </div>
    @endif
    
    {{ $slot }}
</div>

