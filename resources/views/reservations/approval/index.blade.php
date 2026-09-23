@extends('layouts.app')

@section('title', __('Approbation des Réservations'))
@section('page-title', __('Approbation des Réservations'))

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="bg-amber-100 dark:bg-amber-900/30 rounded-lg p-3">
                        <i class="fas fa-clipboard-check text-2xl text-amber-600 dark:text-amber-400"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Approbation des Réservations') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Approuvez ou rejetez les réservations en attente') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-200">
                    <i class="fas fa-clock mr-1.5"></i>
                    {{ $reservations->total() }} {{ __('en attente') }}
                </span>
                <a href="{{ route('reservations.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    <i class="fas fa-list mr-2"></i>
                    {{ __('Toutes les réservations') }}
                </a>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-400 p-4 rounded-lg">
            <div class="flex">
                <i class="fas fa-check-circle text-green-400 mr-3 mt-0.5"></i>
                <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-400 p-4 rounded-lg">
            <div class="flex">
                <i class="fas fa-exclamation-circle text-red-400 mr-3 mt-0.5"></i>
                <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    <!-- Bulk Actions -->
    @if ($reservations->count() > 0)
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4" id="bulk-actions-bar" style="display:none;">
        <form method="POST" action="{{ route('reservations.approval.bulk-approve') }}" id="bulk-approve-form">
            @csrf
            <div class="flex items-center gap-4">
                <span class="text-sm text-gray-600 dark:text-gray-400">
                    <span id="selected-count">0</span> {{ __('réservation(s) sélectionnée(s)') }}
                </span>
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-check-double mr-2"></i>
                    {{ __('Approuver la sélection') }}
                </button>
            </div>
        </form>
    </div>
    @endif

    <!-- Reservations Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        @if ($reservations->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-4 py-3 text-left">
                                <input type="checkbox" id="select-all"
                                       class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500">
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ __('ID / Client') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ __('Borne') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ __('Type / Valeur') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ __('Coût') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ __('Paiement') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ __('Statut') }}
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ __('Date') }}
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ __('Actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($reservations as $reservation)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-4 py-4">
                                    <input type="checkbox" name="reservation_ids[]" value="{{ $reservation->id }}"
                                           class="reservation-checkbox rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500">
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center">
                                            <span class="text-xs font-semibold text-primary-700 dark:text-primary-300">
                                                #{{ $reservation->id }}
                                            </span>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $reservation->user->name ?? $reservation->guest_email ?? __('Inconnu') }}
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $reservation->user->email ?? $reservation->guest_email ?? '' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <p class="text-sm text-gray-900 dark:text-white font-medium">
                                        {{ $reservation->chargingPoint->name ?? __('N/A') }}
                                    </p>
                                    @if ($reservation->connector)
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ __('Connecteur') }} #{{ $reservation->connector->connector_id ?? $reservation->connector->id }}
                                        </p>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium
                                        {{ $reservation->reservation_type === 'kwh' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' : 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300' }}">
                                        @if ($reservation->reservation_type === 'kwh')
                                            <i class="fas fa-bolt mr-1"></i> {{ $reservation->reservation_value }} kWh
                                        @else
                                            <i class="fas fa-clock mr-1"></i> {{ $reservation->reservation_value }} min
                                        @endif
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ number_format($reservation->estimated_cost ?? $reservation->amount ?? 0, 2) }}
                                        {{ strtoupper($reservation->pricingPlan->currency ?? 'EUR') }}
                                    </p>
                                </td>
                                <td class="px-4 py-4">
                                    @php
                                        $payStatus = strtoupper($reservation->payment_status ?? 'PENDING');
                                        $payColors = [
                                            'PAID' => 'green', 'PAYE' => 'green',
                                            'PENDING' => 'yellow', 'FAILED' => 'red', 'REFUNDED' => 'blue',
                                        ];
                                        $payColor = $payColors[$payStatus] ?? 'gray';
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                        bg-{{ $payColor }}-100 text-{{ $payColor }}-800 dark:bg-{{ $payColor }}-900/30 dark:text-{{ $payColor }}-300">
                                        {{ $payStatus }}
                                    </span>
                                    @if ($reservation->payment_method)
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                            {{ ucfirst($reservation->payment_method) }}
                                        </p>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    @php
                                        $statusVal = is_object($reservation->status) ? $reservation->status->value : ($reservation->status ?? 'pending');
                                        $statusColors = [
                                            'pending' => 'yellow', 'pending_confirmation' => 'orange',
                                            'confirmed' => 'blue', 'approved' => 'green', 'active' => 'green',
                                            'completed' => 'gray', 'cancelled' => 'red', 'canceled' => 'red',
                                        ];
                                        $statusColor = $statusColors[$statusVal] ?? 'gray';
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                        bg-{{ $statusColor }}-100 text-{{ $statusColor }}-800 dark:bg-{{ $statusColor }}-900/30 dark:text-{{ $statusColor }}-300">
                                        {{ ucfirst(str_replace('_', ' ', $statusVal)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $reservation->created_at?->format('d/m/Y H:i') ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('reservations.approval.show', $reservation) }}"
                                           class="inline-flex items-center px-3 py-1.5 bg-primary-600 text-white text-xs font-medium rounded-lg hover:bg-primary-700 transition-colors">
                                            <i class="fas fa-eye mr-1"></i>
                                            {{ __('Voir') }}
                                        </a>
                                        <form method="POST" action="{{ route('reservations.approval.approve', $reservation) }}" class="inline">
                                            @csrf
                                            <button type="submit"
                                                    class="inline-flex items-center px-3 py-1.5 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700 transition-colors"
                                                    onclick="return confirm('{{ __('Approuver cette réservation ?') }}')">
                                                <i class="fas fa-check mr-1"></i>
                                                {{ __('Approuver') }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if ($reservations->hasPages())
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    {{ $reservations->links() }}
                </div>
            @endif

        @else
            <!-- Empty State -->
            <div class="p-12 text-center">
                <div class="flex flex-col items-center">
                    <div class="bg-green-100 dark:bg-green-900/30 rounded-full p-6 mb-4">
                        <i class="fas fa-check-circle text-4xl text-green-600 dark:text-green-400"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                        {{ __('Aucune réservation en attente') }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                        {{ __('Toutes les réservations ont été traitées.') }}
                    </p>
                    <a href="{{ route('reservations.index') }}"
                       class="inline-flex items-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">
                        <i class="fas fa-list mr-2"></i>
                        {{ __('Voir toutes les réservations') }}
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.reservation-checkbox');
    const bulkBar = document.getElementById('bulk-actions-bar');
    const selectedCount = document.getElementById('selected-count');
    const bulkForm = document.getElementById('bulk-approve-form');

    function updateBulkBar() {
        const checked = document.querySelectorAll('.reservation-checkbox:checked');
        if (checked.length > 0) {
            bulkBar.style.display = 'block';
            selectedCount.textContent = checked.length;
        } else {
            bulkBar.style.display = 'none';
        }
    }

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            checkboxes.forEach(cb => { cb.checked = this.checked; });
            updateBulkBar();
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkBar);
    });

    if (bulkForm) {
        bulkForm.addEventListener('submit', function (e) {
            const checked = document.querySelectorAll('.reservation-checkbox:checked');
            // Clear previous hidden inputs
            bulkForm.querySelectorAll('input[name="reservation_ids[]"]').forEach(el => el.remove());
            checked.forEach(cb => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'reservation_ids[]';
                input.value = cb.value;
                bulkForm.appendChild(input);
            });
        });
    }
});
</script>
@endpush
@endsection
