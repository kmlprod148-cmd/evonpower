@props([
    'filtersApplied' => false
])

<tr>
    <td colspan="7" class="text-center py-5">
        <div class="text-muted">
            <i class="fas fa-inbox fa-3x mb-3 opacity-50"></i>
            <div class="h5 mb-1">{{ __('No transactions found') }}</div>
            @if($filtersApplied)
                <small>{{ __('Try adjusting your filters') }}</small>
            @else
                <small>{{ __('Your transaction history will appear here') }}</small>
            @endif
        </div>
    </td>
</tr>