{{-- resources/views/components/demo-switches.blade.php --}}
@props(['class' => ''])

<div class="demo-switches-container {{ $class }}" x-data="{
    notifications: true,
    darkMode: false,
    autoSave: true,
    emailUpdates: false,
    smsAlerts: true,
    twoFactor: false,
    analytics: true,
    marketing: false
}">
    <div class="max-w-4xl mx-auto p-6 space-y-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                Démonstration des Boutons Switch Modernes
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                Composants switch avec design moderne et animations fluides
            </p>
        </div>

        <!-- Language Switcher -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                Sélecteur de Langue
            </h2>
            <div class="flex flex-wrap gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Style Dropdown
                    </label>
                    <x-language-switcher style="dropdown" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Style Boutons
                    </label>
                    <x-language-switcher style="buttons" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Style Compact
                    </label>
                    <x-language-switcher style="compact" />
                </div>
            </div>
        </div>

        <!-- Switch Examples Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            
            <!-- Notifications -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Notifications
                </h3>
                <div class="space-y-4">
                    <x-modern-switch 
                        name="notifications"
                        label="Notifications Push"
                        description="Recevoir des notifications push"
                        value="true"
                        color="primary"
                        size="md"
                        x-model="notifications"
                    />
                    
                    <x-modern-switch 
                        name="email_updates"
                        label="Mises à jour par email"
                        description="Recevoir des emails de mise à jour"
                        value="false"
                        color="success"
                        size="md"
                        x-model="emailUpdates"
                    />
                </div>
            </div>

            <!-- Security -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Sécurité
                </h3>
                <div class="space-y-4">
                    <x-modern-switch 
                        name="two_factor"
                        label="Authentification à deux facteurs"
                        description="Sécuriser votre compte avec 2FA"
                        value="false"
                        color="warning"
                        size="md"
                        x-model="twoFactor"
                    />
                    
                    <x-modern-switch 
                        name="sms_alerts"
                        label="Alertes SMS"
                        description="Recevoir des alertes par SMS"
                        value="true"
                        color="danger"
                        size="md"
                        x-model="smsAlerts"
                    />
                </div>
            </div>

            <!-- Preferences -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Préférences
                </h3>
                <div class="space-y-4">
                    <x-modern-switch 
                        name="dark_mode"
                        label="Mode sombre"
                        description="Activer le thème sombre"
                        value="false"
                        color="primary"
                        size="lg"
                        x-model="darkMode"
                    />
                    
                    <x-modern-switch 
                        name="auto_save"
                        label="Sauvegarde automatique"
                        description="Sauvegarder automatiquement les modifications"
                        value="true"
                        color="success"
                        size="sm"
                        x-model="autoSave"
                    />
                </div>
            </div>

            <!-- Analytics -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Analytics
                </h3>
                <div class="space-y-4">
                    <x-modern-switch 
                        name="analytics"
                        label="Analytics"
                        description="Collecter des données d'utilisation"
                        value="true"
                        color="primary"
                        size="md"
                        x-model="analytics"
                    />
                    
                    <x-modern-switch 
                        name="marketing"
                        label="Marketing"
                        description="Recevoir des communications marketing"
                        value="false"
                        color="warning"
                        size="md"
                        x-model="marketing"
                    />
                </div>
            </div>

            <!-- Size Variations -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Variations de Taille
                </h3>
                <div class="space-y-4">
                    <x-modern-switch 
                        name="switch_sm"
                        label="Petit (SM)"
                        size="sm"
                        color="primary"
                    />
                    
                    <x-modern-switch 
                        name="switch_md"
                        label="Moyen (MD)"
                        size="md"
                        color="success"
                    />
                    
                    <x-modern-switch 
                        name="switch_lg"
                        label="Grand (LG)"
                        size="lg"
                        color="warning"
                    />
                </div>
            </div>

            <!-- Color Variations -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Variations de Couleur
                </h3>
                <div class="space-y-4">
                    <x-modern-switch 
                        name="switch_primary"
                        label="Primaire"
                        color="primary"
                        size="md"
                    />
                    
                    <x-modern-switch 
                        name="switch_success"
                        label="Succès"
                        color="success"
                        size="md"
                    />
                    
                    <x-modern-switch 
                        name="switch_warning"
                        label="Attention"
                        color="warning"
                        size="md"
                    />
                    
                    <x-modern-switch 
                        name="switch_danger"
                        label="Danger"
                        color="danger"
                        size="md"
                    />
                </div>
            </div>
        </div>

        <!-- RTL Support Demo -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Support RTL (Arabe)
            </h3>
            <x-rtl-wrapper>
                <div class="flex items-center space-x-4">
                    <x-modern-switch 
                        name="rtl_switch"
                        label="Bouton RTL"
                        description="Test du support RTL"
                        color="primary"
                        size="md"
                    />
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        Ce composant s'adapte automatiquement à la direction RTL
                    </span>
                </div>
            </x-rtl-wrapper>
        </div>

        <!-- Current State Display -->
        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
            <h3 class="text-lg font-semibold mb-4">État Actuel des Paramètres</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 rounded-full" :class="notifications ? 'bg-green-400' : 'bg-gray-400'"></div>
                    <span>Notifications: <span x-text="notifications ? 'Activé' : 'Désactivé'"></span></span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 rounded-full" :class="darkMode ? 'bg-green-400' : 'bg-gray-400'"></div>
                    <span>Mode sombre: <span x-text="darkMode ? 'Activé' : 'Désactivé'"></span></span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 rounded-full" :class="twoFactor ? 'bg-green-400' : 'bg-gray-400'"></div>
                    <span>2FA: <span x-text="twoFactor ? 'Activé' : 'Désactivé'"></span></span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 rounded-full" :class="analytics ? 'bg-green-400' : 'bg-gray-400'"></div>
                    <span>Analytics: <span x-text="analytics ? 'Activé' : 'Désactivé'"></span></span>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animation d'entrée pour les cartes
    const cards = document.querySelectorAll('.bg-white, .bg-gray-800');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Effet de hover pour les switches
    const switches = document.querySelectorAll('.modern-switch-container');
    switches.forEach(switchEl => {
        switchEl.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.02)';
        });
        
        switchEl.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
});
</script>
@endpush
