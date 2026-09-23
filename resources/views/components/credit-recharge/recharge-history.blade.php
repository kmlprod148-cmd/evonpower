@props(['recharges', 'packStatistics', 'filters'])

<div class="mt-8">
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-lg p-6 card-hover">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-3">
                <div class="p-2 bg-gradient-to-br from-green-500 to-green-600 rounded-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Historique des Recharges</h2>
            </div>
            
            @if(isset($packStatistics))
            <!-- Statistiques rapides -->
            <div class="mb-6 grid grid-cols-2 md:grid-cols-4 gap-4">
                <x-credit-recharge.stat-card 
                    label="Packs Disponibles" 
                    :value="$packStatistics['total_packs'] ?? 0" 
                    color="green" 
                />
                <x-credit-recharge.stat-card 
                    label="Plage de Prix" 
                    :value="number_format($packStatistics['total_amount_range']['min'] ?? 0, 0, ',', ' ') . ' - ' . number_format($packStatistics['total_amount_range']['max'] ?? 0, 0, ',', ' ') . ' €'" 
                    color="blue" 
                />
                <x-credit-recharge.stat-card 
                    label="Packs avec Bonus" 
                    :value="$packStatistics['packs_with_bonus'] ?? 0" 
                    color="purple" 
                />
                <x-credit-recharge.stat-card 
                    label="Bonus Moyen" 
                    :value="number_format($packStatistics['average_bonus_percentage'] ?? 0, 1) . '%'" 
                    color="yellow" 
                />
            </div>
            @endif
            
            <!-- Filtres pour clients -->
            @if(auth()->user()->hasRole('user') && !auth()->user()->hasAnyRole(['admin', 'super-admin', 'integrator', 'operator', 'partner']))
                <x-credit-recharge.filter-form :filters="$filters ?? []" />
            @endif
        </div>
        
        @if($recharges->count() > 0)
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Référence</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Montant</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Méthode</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Statut</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($recharges as $recharge)
                            <x-credit-recharge.recharge-table-row :recharge="$recharge" />
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4">
                {{ $recharges->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-gray-500 dark:text-gray-400 text-lg font-medium">Aucune recharge effectuée pour le moment.</p>
                <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">Commencez par recharger votre crédit ci-dessus.</p>
            </div>
        @endif
    </div>
</div>