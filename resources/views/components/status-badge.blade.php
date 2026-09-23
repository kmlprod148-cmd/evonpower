@props(['status'])

<span {{ $attributes->class([
    'inline-flex px-2 py-1 rounded-full text-xs font-semibold',
    match ($status) {
        'credit', 'completed' => 'bg-green-100 text-green-800',
        'debit', 'failed'    => 'bg-red-100 text-red-800',
        'pending'           => 'bg-yellow-100 text-yellow-800',
        default             => 'bg-gray-100 text-gray-800',
    }
]) }}>
    {{ ucfirst($status) }}
</span>