{{-- Navigation bar pour les clients simples (sans sidebar) --}}
<nav class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 sticky top-0 z-30 backdrop-blur-sm bg-white/95 dark:bg-gray-800/95">
    <div class="container-main">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center">
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                    @yield('page-title', __('Mes Réservations'))
                </h1>
            </div>
            <div class="flex items-center gap-4">
                <!-- Language Switcher -->
                @include('components.direct-language-switcher')
                
                @php
                    // Solde à jour depuis le ViewComposer global (user_formatted_balance)
                    $formattedBalance = $user_formatted_balance ?? '0.00 EUR';
                @endphp
                
                <!-- Balance Display -->
                <div x-data="{ balance: '{{ $formattedBalance }}', loading: false }" 
                     @walletbalanceupdated.window="
                        const d = $event.detail;
                        if (d && (d.formatted !== undefined || d.balance !== undefined)) {
                            balance = d.formatted !== undefined ? d.formatted : (parseFloat(d.balance).toFixed(2) + ' EUR');
                        }
                     "
                     x-init="
                        setInterval(async () => {
                            try {
                                loading = true;
                                const response = await fetch('{{ route('credits.balance.api') }}', {
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content') || ''
                                    },
                                    credentials: 'same-origin'
                                });
                                if (response.ok) {
                                    const data = await response.json();
                                    if (data.success) {
                                        balance = data.formatted_balance;
                                    }
                                }
                            } catch (error) {
                                console.error('Erreur lors du rafraîchissement de la balance:', error);
                            } finally {
                                loading = false;
                            }
                        }, 30000);
                     "
                     class="flex items-center gap-2 px-3 py-2 bg-gradient-to-r from-eco-green-50 to-eco-green-100 dark:from-eco-green-900/20 dark:to-eco-green-900/20 rounded-lg border border-eco-green-200 dark:border-eco-green-800 hover:shadow-md transition-all cursor-pointer group"
                     @click="window.location.href='{{ route('credit-recharge.index') }}'"
                     title="Cliquez pour recharger votre wallet">
                    <svg class="w-5 h-5 text-eco-green-600 dark:text-eco-green-400 flex-shrink-0 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="flex flex-col min-w-[80px]">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Solde</span>
                        <span class="text-sm font-bold text-eco-green-600 dark:text-eco-green-400" x-text="balance">{{ $formattedBalance }}</span>
                    </div>
                    <svg x-show="loading" class="w-4 h-4 text-eco-green-600 dark:text-eco-green-400 animate-spin ml-1" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                
                <!-- User Dropdown -->
                @include('layouts.partials.user-dropdown', ['formattedBalance' => $formattedBalance])
            </div>
        </div>
    </div>
</nav>

