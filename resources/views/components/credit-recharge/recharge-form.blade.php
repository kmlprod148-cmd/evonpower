@props(['creditPacks', 'paymentMethods'])

<div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-lg p-6 card-hover max-w-2xl mx-auto">
    <div class="flex items-center space-x-3 mb-6">
        <div class="p-2 bg-gradient-to-br from-green-500 to-green-600 rounded-lg">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
        </div>
        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Nouvelle Recharge</h2>
    </div>
    
    <form action="{{ route('credit-recharge.store') }}" method="POST" id="rechargeForm">
        @csrf
        
        <!-- Sélection Pack ou Personnalisé améliorée -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-4 flex items-center space-x-2">
                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Choisissez un pack ou créez une recharge personnalisée</span>
            </label>
            
            <!-- Packs de crédit modernes style app mobile - petites cartes compactes -->
            <div class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 mb-4" id="packsContainer">
                @foreach($creditPacks as $pack)
                    <x-credit-recharge.pack-card-widget :pack="$pack" />
                @endforeach
                
                <!-- Option Personnalisée moderne -->
                <x-credit-recharge.custom-amount-option />
            </div>
            
            <!-- Champ caché pour credit_pack_id -->
            <input type="hidden" name="credit_pack_id" id="credit_pack_id" value="">
            
            <!-- Montant personnalisé amélioré -->
            <div class="mb-6 hidden custom-amount-slide" id="customAmountContainer">
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-l-4 border-green-500 rounded-lg p-4 mb-4 info-badge">
                    <div class="flex items-start space-x-3">
                        <div class="p-1.5 bg-green-100 dark:bg-green-900/30 rounded-lg flex-shrink-0">
                            <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-green-900 dark:text-green-200 mb-1">Montant libre</p>
                            <p class="text-xs text-green-800 dark:text-green-300">Vous pouvez choisir un montant personnalisé entre 50,00 € et 5 000,00 €</p>
                        </div>
                    </div>
                </div>
                
                <label for="amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 flex items-center space-x-2">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Montant personnalisé (EUR)</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <span class="text-gray-500 dark:text-gray-400 text-lg font-bold">€</span>
                    </div>
                    <input type="number" 
                           id="amount" 
                           name="amount" 
                           step="0.01" 
                           min="50" 
                           max="5000"
                           value="{{ old('amount') }}"
                           class="w-full pl-10 pr-12 py-4 text-lg font-semibold border-2 border-gray-300 dark:border-gray-700 rounded-xl focus:ring-4 focus:ring-green-500/20 focus:border-green-500 dark:bg-gray-800 dark:text-gray-100 transition-all duration-300"
                           placeholder="50.00 - 5000.00">
                    <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none" id="amountStatusIcon">
                        <svg class="w-5 h-5 text-gray-400 hidden" id="amountValidIcon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <svg class="w-5 h-5 text-red-500 hidden" id="amountInvalidIcon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="mt-3 flex items-center justify-between">
                        <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Montant entre 50,00 € et 5 000,00 €</span>
                        </div>
                        <div id="amountPreview" class="text-sm font-semibold text-green-600 dark:text-green-400 hidden">
                            <span id="amountPreviewValue">0,00 €</span>
                        </div>
                    </div>
                </div>
                <div id="amountErrorContainer" class="mt-2 hidden">
                    <p class="text-sm text-red-600 dark:text-red-400 flex items-center space-x-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3">
                        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <span id="amountErrorText"></span>
                    </p>
                </div>
                @error('amount')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center space-x-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3">
                        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>
        </div>

        <!-- Méthode de paiement améliorée -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-4 flex items-center space-x-2">
                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
                <span>Méthode de Paiement</span>
            </label>
            
            @if(isset($paymentMethods['offline']) && $paymentMethods['offline']['enabled'])
            <!-- Information pour les clients publics améliorée -->
            @if(auth()->user()->hasRole('user') && !auth()->user()->hasAnyRole(['admin', 'super-admin', 'integrator', 'operator', 'partner']))
            <div class="mb-4 p-3 bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 border-l-4 border-blue-500 rounded-lg shadow-sm info-badge">
                <div class="flex items-center justify-between cursor-pointer" onclick="toggleInfo('admin-info')">
                    <div class="flex items-center space-x-2 flex-1">
                        <div class="p-1.5 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex-shrink-0">
                            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-semibold text-blue-900 dark:text-blue-200">Recharge avec confirmation admin</p>
                    </div>
                    <svg id="admin-info-icon" class="w-4 h-4 text-blue-600 dark:text-blue-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>
                <div id="admin-info-details" class="hidden mt-2 pl-6">
                    <p class="text-xs text-blue-800 dark:text-blue-300 leading-relaxed">
                        En tant que client public, votre demande de recharge sera soumise à confirmation par un administrateur. 
                        Une fois confirmée, le montant sera ajouté à votre solde. Vous recevrez une notification par email une fois la recharge validée.
                    </p>
                </div>
            </div>
            @endif
            @endif
            
            <!-- Information sur les méthodes de paiement améliorée -->
            <div class="mb-4 p-3 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-l-4 border-green-500 rounded-lg shadow-sm info-badge">
                <div class="flex items-center justify-between cursor-pointer" onclick="toggleInfo('payment-info')">
                    <div class="flex items-center space-x-2 flex-1">
                        <div class="p-1.5 bg-green-100 dark:bg-green-900/30 rounded-lg flex-shrink-0">
                            <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <p class="text-sm font-semibold text-green-900 dark:text-green-200">Paiements en ligne sécurisés</p>
                    </div>
                    <svg id="payment-info-icon" class="w-4 h-4 text-green-600 dark:text-green-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>
                <div id="payment-info-details" class="hidden mt-2 pl-6">
                    <p class="text-xs text-green-800 dark:text-green-300 leading-relaxed mb-2">
                        <strong>CMI International</strong> et <strong>Stripe</strong> sont des méthodes de paiement en ligne instantanées. 
                        Vous serez redirigé vers leur plateforme sécurisée pour compléter votre transaction. 
                        Le crédit sera ajouté immédiatement après confirmation du paiement.
                    </p>
                    <div class="flex items-center space-x-4 text-xs text-green-700 dark:text-green-400">
                        <span class="flex items-center space-x-1">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Traitement instantané</span>
                        </span>
                        <span class="flex items-center space-x-1">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>100% sécurisé</span>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @php
                    $paymentMethodKeys = ['online_card', 'cmi', 'stripe', 'on_site_card', 'offline'];
                @endphp
                @foreach ($paymentMethodKeys as $method)
                    @if(isset($paymentMethods[$method]) && $paymentMethods[$method]['enabled'])
                        <x-credit-recharge.payment-method-card 
                            :method="$method" 
                            :paymentMethods="$paymentMethods" 
                            :isFirst="$loop->first" 
                        />
                    @endif
                @endforeach
            </div>
            @error('payment_method')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Description (optionnelle) améliorée -->
        <div class="mb-6">
            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 flex items-center space-x-2">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                <span>Description (optionnelle)</span>
            </label>
            <div class="relative">
                <textarea id="description" 
                          name="description" 
                          rows="3"
                          maxlength="500"
                          class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-700 rounded-xl focus:ring-4 focus:ring-green-500/20 focus:border-green-500 dark:bg-gray-800 dark:text-gray-100 transition-all resize-none"
                          placeholder="Ajoutez une description pour cette recharge (ex: Recharge mensuelle, Budget marketing, etc.)...">{{ old('description') }}</textarea>
                <div class="absolute bottom-2 right-2 text-xs text-gray-400 dark:text-gray-500">
                    <span id="descriptionCharCount">0</span>/500
                </div>
            </div>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 flex items-center space-x-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Cette description vous aidera à identifier cette recharge dans votre historique</span>
            </p>
        </div>

        <!-- Bouton de soumission amélioré -->
        <button type="submit" 
                id="submitButton"
                class="submit-button w-full bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white font-bold py-4 px-6 rounded-xl transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-green-500/30 shadow-xl hover:shadow-2xl transform hover:-translate-y-1 flex items-center justify-center space-x-3 relative overflow-hidden">
            <svg class="w-6 h-6 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="relative z-10 text-lg text-green-600 dark:text-green-400">Recharger</span>
            <svg class="w-5 h-5 relative z-10 hidden animate-spin" id="submitSpinner" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </button>
    </form>
</div>

<script>
function toggleInfo(id) {
    const details = document.getElementById(id + '-details');
    const icon = document.getElementById(id + '-icon');
    
    if (details && icon) {
        const isHidden = details.classList.contains('hidden');
        
        if (isHidden) {
            details.classList.remove('hidden');
            icon.style.transform = 'rotate(180deg)';
        } else {
            details.classList.add('hidden');
            icon.style.transform = 'rotate(0deg)';
        }
    }
}
</script>