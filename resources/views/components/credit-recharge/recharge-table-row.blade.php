@props(['recharge'])

<tr class="table-row">
    <td class="px-6 py-4 whitespace-nowrap">
        <div class="flex items-center space-x-2">
            <div class="p-1.5 bg-gray-100 dark:bg-gray-800 rounded">
                <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                </svg>
            </div>
            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $recharge->reference }}</span>
        </div>
    </td>
    <td class="px-6 py-4 whitespace-nowrap">
        <span class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $recharge->formatted_amount }}</span>
    </td>
    <td class="px-6 py-4 whitespace-nowrap">
        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $recharge->payment_method_name }}</span>
    </td>
    <td class="px-6 py-4 whitespace-nowrap">
        <x-credit-recharge.status-badge :status="$recharge->status" />
    </td>
    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
        {{ $recharge->created_at->format('d/m/Y H:i') }}
    </td>
    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
        <div class="flex items-center space-x-3">
            <a href="{{ route('credit-recharge.show', $recharge) }}" 
               class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-green-600 hover:text-green-700 dark:text-green-400 dark:hover:text-green-300 bg-green-50 dark:bg-green-900/20 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                Voir
            </a>
            @if(!$recharge->isCompleted() && !$recharge->isFailed())
            <form action="{{ route('credit-recharge.cancel', $recharge) }}" 
                  method="POST" 
                  class="inline-block"
                  onsubmit="return confirm('Êtes-vous sûr de vouloir annuler cette recharge ?');">
                @csrf
                <button type="submit" class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Annuler
                </button>
            </form>
            @endif
        </div>
    </td>
</tr>

