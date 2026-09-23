@extends('layouts.app')

@section('title', __('Demandes de Retrait'))
@section('page-title', __('Demandes de Retrait'))

@section('content')
<div class="space-y-6" x-data="withdrawalAdmin()">
    <!-- Page Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center">
                <div class="bg-orange-100 dark:bg-orange-900/30 rounded-lg p-3 mr-4">
                    <i class="fas fa-money-bill-wave text-2xl text-orange-600 dark:text-orange-400"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Demandes de Retrait') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Gérez les demandes de retrait des utilisateurs') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-400 p-4 rounded-lg">
            <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
        </div>
    @endif
    @if (session('error'))
        <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-400 p-4 rounded-lg">
            <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
        </div>
    @endif

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex flex-wrap gap-3 items-center">
            <div class="flex-1 min-w-48">
                <input type="text" x-model="filters.search" @input.debounce.400ms="fetchData()"
                       placeholder="{{ __('Rechercher par nom ou email...') }}"
                       class="w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500">
            </div>
            <select x-model="filters.status" @change="fetchData()"
                    class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500">
                <option value="">{{ __('Tous les statuts') }}</option>
                <option value="pending">{{ __('En attente') }}</option>
                <option value="approved">{{ __('Approuvé') }}</option>
                <option value="rejected">{{ __('Rejeté') }}</option>
                <option value="processed">{{ __('Traité') }}</option>
                <option value="cancelled">{{ __('Annulé') }}</option>
            </select>
            <input type="date" x-model="filters.date_from" @change="fetchData()"
                   class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500">
            <input type="date" x-model="filters.date_to" @change="fetchData()"
                   class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500">
            <button @click="resetFilters()"
                    class="inline-flex items-center px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                <i class="fas fa-times mr-1"></i> {{ __('Réinitialiser') }}
            </button>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <!-- Loading State -->
        <div x-show="loading" class="p-8 text-center">
            <i class="fas fa-spinner fa-spin text-2xl text-primary-500"></i>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('Chargement...') }}</p>
        </div>

        <!-- Data Table -->
        <div x-show="!loading">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('ID / Utilisateur') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Montant') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Statut') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Méthode') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Date') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <template x-if="withdrawals.length === 0 && !loading">
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <i class="fas fa-inbox text-3xl text-gray-300 dark:text-gray-600 mb-3"></i>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Aucune demande trouvée') }}</p>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <template x-for="withdrawal in withdrawals" :key="withdrawal.id">
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-4 py-4">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white" x-text="'#' + withdrawal.id"></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="withdrawal.owner?.name ?? withdrawal.owner?.email ?? '–'"></p>
                                </td>
                                <td class="px-4 py-4">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                        <span x-text="formatAmount(withdrawal.amount)"></span>
                                        <span x-text="withdrawal.currency ?? 'EUR'" class="text-xs text-gray-500 dark:text-gray-400 ml-1"></span>
                                    </p>
                                </td>
                                <td class="px-4 py-4">
                                    <span :class="statusClass(withdrawal.status)"
                                          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                          x-text="statusLabel(withdrawal.status)"></span>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-400" x-text="withdrawal.method ?? '–'"></td>
                                <td class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400" x-text="formatDate(withdrawal.created_at)"></td>
                                <td class="px-4 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <template x-if="withdrawal.status === 'pending'">
                                            <div class="flex gap-2">
                                                <button @click="approve(withdrawal.id)"
                                                        class="inline-flex items-center px-3 py-1.5 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700 transition-colors">
                                                    <i class="fas fa-check mr-1"></i> {{ __('Approuver') }}
                                                </button>
                                                <button @click="reject(withdrawal.id)"
                                                        class="inline-flex items-center px-3 py-1.5 bg-red-600 text-white text-xs font-medium rounded-lg hover:bg-red-700 transition-colors">
                                                    <i class="fas fa-times mr-1"></i> {{ __('Rejeter') }}
                                                </button>
                                            </div>
                                        </template>
                                        <template x-if="withdrawal.status === 'approved'">
                                            <button @click="process(withdrawal.id)"
                                                    class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                                <i class="fas fa-cog mr-1"></i> {{ __('Traiter') }}
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                <span x-text="'Total: ' + (pagination.total ?? 0) + ' demande(s)'"></span>
                <div class="flex gap-2">
                    <button @click="prevPage()" :disabled="!pagination.prev_page_url"
                            class="px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-40 transition-colors">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <span x-text="'Page ' + (pagination.current_page ?? 1) + ' / ' + (pagination.last_page ?? 1)"></span>
                    <button @click="nextPage()" :disabled="!pagination.next_page_url"
                            class="px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-40 transition-colors">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function withdrawalAdmin() {
    return {
        withdrawals: [],
        pagination: {},
        loading: true,
        filters: { search: '', status: '', date_from: '', date_to: '' },
        currentUrl: '{{ url("/api/admin/withdrawals") }}',

        init() { this.fetchData(); },

        fetchData(url = null) {
            this.loading = true;
            const params = new URLSearchParams();
            if (this.filters.search) params.set('search', this.filters.search);
            if (this.filters.status) params.set('status', this.filters.status);
            if (this.filters.date_from) params.set('date_from', this.filters.date_from);
            if (this.filters.date_to) params.set('date_to', this.filters.date_to);

            const endpoint = (url || this.currentUrl) + '?' + params.toString();
            fetch(endpoint, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(data => {
                    this.withdrawals = data.data ?? [];
                    this.pagination = { current_page: data.current_page, last_page: data.last_page, total: data.total, prev_page_url: data.prev_page_url, next_page_url: data.next_page_url };
                    this.loading = false;
                })
                .catch(() => { this.loading = false; });
        },

        prevPage() { if (this.pagination.prev_page_url) this.fetchData(this.pagination.prev_page_url); },
        nextPage() { if (this.pagination.next_page_url) this.fetchData(this.pagination.next_page_url); },
        resetFilters() { this.filters = { search: '', status: '', date_from: '', date_to: '' }; this.fetchData(); },

        approve(id) {
            if (!confirm('{{ __("Approuver cette demande ?") }}')) return;
            this.postAction(`{{ url('/admin/withdrawals') }}/${id}/approve`);
        },
        reject(id) {
            const reason = prompt('{{ __("Motif du rejet :") }}');
            if (!reason) return;
            this.postAction(`{{ url('/admin/withdrawals') }}/${id}/reject`, { reason });
        },
        process(id) {
            if (!confirm('{{ __("Traiter cette demande de retrait ?") }}')) return;
            this.postAction(`{{ url('/admin/withdrawals') }}/${id}/process`);
        },

        postAction(url, extra = {}) {
            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                body: JSON.stringify(extra),
            })
            .then(r => r.json())
            .then(() => this.fetchData())
            .catch(e => alert('{{ __("Erreur : ") }}' + e));
        },

        statusClass(status) {
            const classes = { pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300', approved: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300', rejected: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300', processed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300', cancelled: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' };
            return classes[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
        },
        statusLabel(status) {
            const labels = { pending: '{{ __("En attente") }}', approved: '{{ __("Approuvé") }}', rejected: '{{ __("Rejeté") }}', processed: '{{ __("Traité") }}', cancelled: '{{ __("Annulé") }}' };
            return labels[status] || status;
        },
        formatAmount(amount) { return parseFloat(amount || 0).toFixed(2); },
        formatDate(date) { if (!date) return '–'; return new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }); },
    };
}
</script>
@endpush
@endsection
