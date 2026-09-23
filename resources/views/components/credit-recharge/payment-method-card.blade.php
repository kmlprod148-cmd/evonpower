@props(['method', 'paymentMethods', 'isFirst' => false])

@php
    $methodConfig = [
        'offline' => [
            'name' => 'Hors Ligne',
            'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
            'iconBg' => 'from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600',
            'iconColor' => 'text-gray-600 dark:text-gray-400',
            'badge' => 'Manuel',
            'badgeColor' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
            'description' => $paymentMethods['offline']['description'] ?? 'Paiement manuel avec confirmation admin',
            'processing' => '24-48h',
            'security' => 'Élevée',
            'showOnlineBadge' => false,
        ],
        'on_site_card' => [
            'name' => 'Sur place par carte crédit',
            'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
            'iconBg' => 'from-orange-100 to-orange-200 dark:from-orange-900/30 dark:to-orange-800/30',
            'iconColor' => 'text-orange-600 dark:text-orange-400',
            'badge' => 'Sur place',
            'badgeColor' => 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300',
            'description' => '<strong class="text-orange-600 dark:text-orange-400">Paiement sur place</strong> par carte bancaire nationale ou internationale.',
            'processing' => 'Immédiat',
            'security' => 'PCI DSS',
            'showOnlineBadge' => false,
            'processSteps' => [
                'Présentez-vous sur place avec votre carte bancaire.',
                'Le personnel validera votre paiement.',
                'Le crédit sera ajouté immédiatement à votre compte.',
            ],
            'cards' => ['visa', 'mastercard'],
            'infoBg' => 'bg-orange-50 dark:bg-orange-900/20',
            'infoBorder' => 'border-orange-200 dark:border-orange-800',
            'infoText' => 'text-orange-900 dark:text-orange-200',
            'infoTextSecondary' => 'text-orange-800 dark:text-orange-300',
        ],
        'cmi' => [
            'name' => 'CMI International',
            'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
            'iconBg' => 'from-blue-100 to-blue-200 dark:from-blue-900/30 dark:to-blue-800/30',
            'iconColor' => 'text-blue-600 dark:text-blue-400',
            'badge' => 'Instantané',
            'badgeColor' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
            'description' => '<strong class="text-green-600 dark:text-green-400">Paiement en ligne sécurisé</strong> via la plateforme CMI, leader du paiement au Maroc.',
            'processing' => 'Immédiat',
            'security' => '3D Secure',
            'showOnlineBadge' => true,
            'processSteps' => [
                'Redirection sécurisée vers la page de paiement CMI.',
                'Saisie des informations de votre carte bancaire.',
                'Validation 3D Secure pour une sécurité maximale.',
                'Confirmation instantanée et retour sur notre site.',
            ],
            'cards' => ['visa', 'mastercard', 'cmi'],
            'infoBg' => 'bg-blue-50 dark:bg-blue-900/20',
            'infoBorder' => 'border-blue-200 dark:border-blue-800',
            'infoText' => 'text-blue-900 dark:text-blue-200',
            'infoTextSecondary' => 'text-blue-800 dark:text-blue-300',
        ],
        'stripe' => [
            'name' => 'Stripe',
            'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
            'iconBg' => 'from-purple-100 to-purple-200 dark:from-purple-900/30 dark:to-purple-800/30',
            'iconColor' => 'text-purple-600 dark:text-purple-400',
            'badge' => 'Instantané',
            'badgeColor' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
            'description' => '<strong class="text-green-600 dark:text-green-400">Paiement international sécurisé</strong> avec Stripe, une solution de confiance mondiale.',
            'processing' => 'Immédiat',
            'security' => 'PCI DSS',
            'showOnlineBadge' => true,
            'processSteps' => [
                'Redirection vers la page de paiement Stripe Checkout.',
                'Interface optimisée pour tous les appareils.',
                'Paiement rapide et sécurisé.',
                'Crédit ajouté instantanément après le succès du paiement.',
            ],
            'cards' => ['visa', 'mastercard', 'amex'],
            'infoBg' => 'bg-purple-50 dark:bg-purple-900/20',
            'infoBorder' => 'border-purple-200 dark:border-purple-800',
            'infoText' => 'text-purple-900 dark:text-purple-200',
            'infoTextSecondary' => 'text-purple-800 dark:text-purple-300',
        ],
        'online_card' => [
            'name' => 'Paiement en ligne par carte',
            'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
            'iconBg' => 'from-green-100 to-emerald-200 dark:from-green-900/30 dark:to-emerald-800/30',
            'iconColor' => 'text-green-600 dark:text-green-400',
            'badge' => 'Automatique',
            'badgeColor' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
            'description' => '<strong class="text-green-600 dark:text-green-400">Paiement en ligne automatique</strong> par carte bancaire directement sur le site, sans redirection.',
            'processing' => 'Immédiat',
            'security' => '3D Secure',
            'showOnlineBadge' => true,
            'processSteps' => [
                'Saisissez vos informations de carte directement sur le site.',
                'Validation automatique et sécurisée avec 3D Secure.',
                'Confirmation instantanée du paiement.',
                'Le crédit est ajouté immédiatement à votre compte.',
            ],
            'cards' => ['visa', 'mastercard', 'amex'],
            'infoBg' => 'bg-green-50 dark:bg-green-900/20',
            'infoBorder' => 'border-green-200 dark:border-green-800',
            'infoText' => 'text-green-900 dark:text-green-200',
            'infoTextSecondary' => 'text-green-800 dark:text-green-300',
        ],
    ];
    
    $config = $methodConfig[$method] ?? $methodConfig['offline'];
@endphp

<label class="payment-method-card relative flex flex-col p-6 bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl cursor-pointer hover:border-green-500 shadow-sm group" data-method="{{ $method }}">
    <input type="radio" name="payment_method" value="{{ $method }}" class="sr-only" required {{ $isFirst ? 'checked' : '' }}>
    
    @if($config['showOnlineBadge'] ?? false)
    <!-- Badge "Paiement en ligne" -->
    <div class="absolute top-3 left-3 z-10">
        <span class="px-2.5 py-1 text-xs font-bold bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-full shadow-md flex items-center space-x-1">
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-2.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clip-rule="evenodd"/>
            </svg>
            <span>En ligne</span>
        </span>
    </div>
    @endif
    
    <!-- Header avec icône et badge -->
    <div class="flex items-start justify-between mb-4">
        <div class="p-3 bg-gradient-to-br {{ $config['iconBg'] }} rounded-xl group-hover:scale-110 transition-transform duration-300">
            <svg class="w-6 h-6 {{ $config['iconColor'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $config['icon'] }}"/>
            </svg>
        </div>
        <div class="flex flex-col items-end space-y-1">
            <span class="px-2.5 py-1 text-xs font-semibold {{ $config['badgeColor'] }} rounded-full {{ ($method === 'cmi' || $method === 'stripe') ? 'flex items-center space-x-1' : '' }}">
                @if($method === 'cmi' || $method === 'stripe')
                <svg class="w-3 h-3 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-2.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clip-rule="evenodd"/>
                </svg>
                @endif
                <span>{{ $config['badge'] }}</span>
            </span>
            <svg class="w-6 h-6 text-green-500 payment-check-icon {{ $isFirst ? '' : 'hidden' }}" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
        </div>
    </div>
    
    <!-- Contenu principal -->
    <div class="flex-1">
        <h3 class="font-bold text-lg text-gray-900 dark:text-gray-100 mb-2">{{ $config['name'] }}</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 leading-relaxed">
            {!! $config['description'] !!}
        </p>
        
        @if(isset($config['processSteps']))
        <!-- Processus de paiement -->
        <div class="mb-4 p-3 {{ $config['infoBg'] }} rounded-lg border {{ $config['infoBorder'] }}">
            <p class="text-xs font-semibold {{ $config['infoText'] }} mb-2 flex items-center space-x-1">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <span>{{ $method === 'cmi' ? 'Processus simple et rapide :' : 'Expérience de paiement fluide :' }}</span>
            </p>
            <ol class="text-xs {{ $config['infoTextSecondary'] }} space-y-1 ml-4 list-decimal">
                @foreach($config['processSteps'] as $step)
                <li>{{ $step }}</li>
                @endforeach
            </ol>
        </div>
        
        <!-- Types de cartes acceptées -->
        @if(isset($config['cards']))
        <div class="mb-3">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Cartes acceptées :</p>
            <div class="flex items-center space-x-2 flex-wrap gap-1">
                @foreach($config['cards'] as $card)
                    @if($card === 'visa')
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/5e/Visa_Inc._logo.svg/1200px-Visa_Inc._logo.svg.png" alt="Visa" class="h-4">
                    @elseif($card === 'mastercard')
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/b/b7/MasterCard_Logo.svg/1200px-MasterCard_Logo.svg.png" alt="Mastercard" class="h-4">
                    @elseif($card === 'amex')
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/f/f4/Amex_logo_2018.svg/1200px-Amex_logo_2018.svg.png" alt="American Express" class="h-4">
                    @elseif($card === 'cmi')
                        <img src="https://www.cmi.co.ma/themes/cmi/assets/images/logo-cmi.svg" alt="CMI" class="h-4">
                    @endif
                @endforeach
            </div>
        </div>
        @endif
        @endif
        
        <!-- Informations détaillées -->
        <div class="space-y-2 pt-3 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between text-xs">
                <span class="text-gray-500 dark:text-gray-400 flex items-center space-x-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Traitement</span>
                </span>
                <span class="font-semibold {{ ($method === 'cmi' || $method === 'stripe') ? 'text-green-600 dark:text-green-400 flex items-center space-x-1' : 'text-gray-700 dark:text-gray-300' }}">
                    @if($method === 'cmi' || $method === 'stripe')
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-2.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clip-rule="evenodd"/>
                    </svg>
                    @endif
                    <span>{{ $config['processing'] }}</span>
                </span>
            </div>
            <div class="flex items-center justify-between text-xs">
                <span class="text-gray-500 dark:text-gray-400 flex items-center space-x-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <span>Sécurité</span>
                </span>
                <span class="font-semibold text-green-600 dark:text-green-400">{{ $config['security'] }}</span>
            </div>
        </div>
    </div>
</label>

