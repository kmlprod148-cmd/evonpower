<!-- Récapitulatif en temps réel -->
<div class="lg:col-span-1">
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-sm sticky top-6">
        <!-- Header -->
        <div class="p-6 border-b border-gray-200 dark:border-gray-800">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                    <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Récapitulatif</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Étape {{ $currentStep ?? 1 }} sur 4</p>
                </div>
            </div>
        </div>

        <!-- Progress -->
        <div class="p-6 border-b border-gray-200 dark:border-gray-800">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Progression</span>
                <span class="text-sm text-gray-500 dark:text-gray-400" id="progress-percentage">{{ round(($currentStep ?? 1) * 25) }}%</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div class="bg-emerald-600 h-2 rounded-full transition-all duration-300 ease-out" 
                     style="width: {{ ($currentStep ?? 1) * 25 }}%" id="progress-bar"></div>
            </div>
        </div>

        <!-- Content -->
        <div class="p-6 space-y-6">
            <!-- Étape 1: Informations générales -->
            <div class="recap-section transition-all duration-300 ease-in-out" data-step="1">
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-6 h-6 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center transition-all duration-300 ease-in-out">
                        <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">1</span>
                    </div>
                    <h4 class="font-medium text-gray-900 dark:text-gray-100 transition-colors duration-300">Informations générales</h4>
                </div>
                <div class="space-y-2 text-sm transition-all duration-500 ease-in-out" id="recap-step1">
                    <div class="text-gray-500 dark:text-gray-400 animate-pulse">Aucune information saisie</div>
                </div>
            </div>

            <!-- Étape 2: Spécifications techniques -->
            <div class="recap-section opacity-50" data-step="2">
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-6 h-6 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                        <span class="text-xs font-semibold text-gray-400">2</span>
                    </div>
                    <h4 class="font-medium text-gray-500 dark:text-gray-400">Spécifications techniques</h4>
                </div>
                <div class="space-y-2 text-sm text-gray-400 dark:text-gray-500">
                    <div>Non disponible</div>
                </div>
            </div>

            <!-- Étape 3: Emplacement -->
            <div class="recap-section opacity-50" data-step="3">
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-6 h-6 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                        <span class="text-xs font-semibold text-gray-400">3</span>
                    </div>
                    <h4 class="font-medium text-gray-500 dark:text-gray-400">Emplacement</h4>
                </div>
                <div class="space-y-2 text-sm text-gray-400 dark:text-gray-500">
                    <div>Non disponible</div>
                </div>
            </div>

            <!-- Étape 4: Plan tarifaire -->
            <div class="recap-section opacity-50" data-step="4">
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-6 h-6 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                        <span class="text-xs font-semibold text-gray-400">4</span>
                    </div>
                    <h4 class="font-medium text-gray-500 dark:text-gray-400">Plan tarifaire</h4>
                </div>
                <div class="space-y-2 text-sm text-gray-400 dark:text-gray-500">
                    <div>Non disponible</div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="p-6 border-t border-gray-200 dark:border-gray-800">
            <div class="space-y-3">
                <button type="button" id="save-draft-btn" 
                        class="w-full px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition-colors">
                    💾 Sauvegarder brouillon
                </button>
                <button type="button" id="preview-btn" 
                        class="w-full px-4 py-2 text-sm font-medium text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-900/20 hover:bg-emerald-100 dark:hover:bg-emerald-900/30 rounded-lg transition-colors">
                    👁️ Aperçu
                </button>
            </div>
        </div>
    </div>
</div>
