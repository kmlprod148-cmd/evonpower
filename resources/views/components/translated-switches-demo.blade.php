{{-- resources/views/components/translated-switches-demo.blade.php --}}
@props(['class' => ''])

<div class="translated-switches-demo {{ $class }}" x-data="{
    // État des switches
    notifications: true,
    darkMode: false,
    autoSave: true,
    emailUpdates: false,
    smsAlerts: true,
    twoFactor: false,
    analytics: true,
    marketing: false,
    privacy: true,
    security: false,
    
    // Fonction pour basculer un switch
    toggleSwitch(switchName) {
        this[switchName] = !this[switchName];
        
        // Animation de feedback
        this.showFeedback(switchName);
    },
    
    // Fonction pour afficher un feedback
    showFeedback(switchName) {
        const feedback = document.getElementById('feedback-' + switchName);
        if (feedback) {
            feedback.style.opacity = '1';
            feedback.style.transform = 'translateY(0)';
            
            setTimeout(() => {
                feedback.style.opacity = '0';
                feedback.style.transform = 'translateY(-10px)';
            }, 2000);
        }
    },
    
    // Obtenir le statut traduit
    getStatusText(isEnabled) {
        return isEnabled ? '{{ trans("switch.enabled") }}' : '{{ trans("switch.disabled") }}';
    }
}">
    <div class="max-w-6xl mx-auto p-6 space-y-8">
        <!-- Header avec sélecteur de langue -->
        <div class="text-center mb-8">
            <div class="flex justify-center items-center space-x-4 mb-4">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    {{ trans("switch.settings") }}
                </h1>
                <x-language-switcher style="compact" class="ml-4" />
            </div>
            <p class="text-gray-600 dark:text-gray-400">
                {{ trans("switch.settings") }} - {{ trans("switch.preferences") }}
            </p>
        </div>

        <!-- Grid des switches traduits -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            
            <!-- Notifications -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow duration-300">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ trans("switch.notifications") }}
                    </h3>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm font-medium" 
                              :class="notifications ? 'text-green-600' : 'text-gray-500'"
                              x-text="getStatusText(notifications)">
                        </span>
                        <div class="w-2 h-2 rounded-full" 
                             :class="notifications ? 'bg-green-500' : 'bg-gray-400'">
                        </div>
                    </div>
                </div>
                
                <x-translated-switch 
                    name="notifications"
                    labelKey="notifications"
                    descriptionKey="notifications_description"
                    value="true"
                    color="primary"
                    size="md"
                    x-model="notifications"
                    showStatus="false"
                />
                
                <!-- Feedback -->
                <div id="feedback-notifications" 
                     class="mt-2 text-sm text-green-600 opacity-0 transform -translate-y-2 transition-all duration-300"
                     x-text="notifications ? '{{ trans("switch.enabled_successfully") }}' : '{{ trans("switch.disabled_successfully") }}'">
                </div>
            </div>

            <!-- Mode sombre -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow duration-300">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ trans("switch.dark_mode") }}
                    </h3>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm font-medium" 
                              :class="darkMode ? 'text-green-600' : 'text-gray-500'"
                              x-text="getStatusText(darkMode)">
                        </span>
                        <div class="w-2 h-2 rounded-full" 
                             :class="darkMode ? 'bg-green-500' : 'bg-gray-400'">
                        </div>
                    </div>
                </div>
                
                <x-translated-switch 
                    name="dark_mode"
                    labelKey="dark_mode"
                    descriptionKey="dark_mode_description"
                    value="false"
                    color="primary"
                    size="lg"
                    x-model="darkMode"
                    showStatus="false"
                />
                
                <div id="feedback-darkMode" 
                     class="mt-2 text-sm text-green-600 opacity-0 transform -translate-y-2 transition-all duration-300"
                     x-text="darkMode ? '{{ trans("switch.enabled_successfully") }}' : '{{ trans("switch.disabled_successfully") }}'">
                </div>
            </div>

            <!-- Sécurité -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow duration-300">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ trans("switch.security") }}
                    </h3>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm font-medium" 
                              :class="twoFactor ? 'text-green-600' : 'text-gray-500'"
                              x-text="getStatusText(twoFactor)">
                        </span>
                        <div class="w-2 h-2 rounded-full" 
                             :class="twoFactor ? 'bg-green-500' : 'bg-gray-400'">
                        </div>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <x-translated-switch 
                        name="two_factor"
                        labelKey="two_factor"
                        descriptionKey="two_factor_description"
                        value="false"
                        color="warning"
                        size="md"
                        x-model="twoFactor"
                        showStatus="false"
                    />
                    
                    <x-translated-switch 
                        name="sms_alerts"
                        labelKey="sms_alerts"
                        descriptionKey="sms_alerts_description"
                        value="true"
                        color="danger"
                        size="md"
                        x-model="smsAlerts"
                        showStatus="false"
                    />
                </div>
                
                <div id="feedback-twoFactor" 
                     class="mt-2 text-sm text-green-600 opacity-0 transform -translate-y-2 transition-all duration-300"
                     x-text="twoFactor ? '{{ trans("switch.enabled_successfully") }}' : '{{ trans("switch.disabled_successfully") }}'">
                </div>
            </div>

            <!-- Préférences -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow duration-300">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ trans("switch.preferences") }}
                    </h3>
                </div>
                
                <div class="space-y-4">
                    <x-translated-switch 
                        name="auto_save"
                        labelKey="auto_save"
                        descriptionKey="auto_save_description"
                        value="true"
                        color="success"
                        size="md"
                        x-model="autoSave"
                        showStatus="false"
                    />
                    
                    <x-translated-switch 
                        name="email_updates"
                        labelKey="email_updates"
                        descriptionKey="email_updates_description"
                        value="false"
                        color="primary"
                        size="md"
                        x-model="emailUpdates"
                        showStatus="false"
                    />
                </div>
            </div>

            <!-- Analytics -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow duration-300">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ trans("switch.analytics") }}
                    </h3>
                </div>
                
                <div class="space-y-4">
                    <x-translated-switch 
                        name="analytics"
                        labelKey="analytics"
                        descriptionKey="analytics_description"
                        value="true"
                        color="primary"
                        size="md"
                        x-model="analytics"
                        showStatus="false"
                    />
                    
                    <x-translated-switch 
                        name="marketing"
                        labelKey="marketing"
                        descriptionKey="marketing_description"
                        value="false"
                        color="warning"
                        size="md"
                        x-model="marketing"
                        showStatus="false"
                    />
                </div>
            </div>

            <!-- Confidentialité -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow duration-300">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ trans("switch.privacy") }}
                    </h3>
                </div>
                
                <x-translated-switch 
                    name="privacy"
                    labelKey="privacy"
                    descriptionKey="privacy_description"
                    value="true"
                    color="success"
                    size="lg"
                    x-model="privacy"
                    showStatus="false"
                />
            </div>
        </div>

        <!-- Résumé des paramètres -->
        <div class="bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 rounded-xl shadow-lg p-6 text-white">
            <h3 class="text-xl font-semibold mb-4">
                {{ trans("switch.settings") }} - {{ trans("switch.preferences") }}
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 rounded-full" :class="notifications ? 'bg-green-400' : 'bg-gray-400'"></div>
                    <span>{{ trans("switch.notifications") }}: <span x-text="getStatusText(notifications)"></span></span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 rounded-full" :class="darkMode ? 'bg-green-400' : 'bg-gray-400'"></div>
                    <span>{{ trans("switch.dark_mode") }}: <span x-text="getStatusText(darkMode)"></span></span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 rounded-full" :class="twoFactor ? 'bg-green-400' : 'bg-gray-400'"></div>
                    <span>{{ trans("switch.two_factor") }}: <span x-text="getStatusText(twoFactor)"></span></span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-3 h-3 rounded-full" :class="analytics ? 'bg-green-400' : 'bg-gray-400'"></div>
                    <span>{{ trans("switch.analytics") }}: <span x-text="getStatusText(analytics)"></span></span>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-center space-x-4">
            <button @click="
                notifications = true;
                darkMode = false;
                autoSave = true;
                twoFactor = false;
                analytics = true;
                marketing = false;
            " class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors duration-200">
                {{ trans("switch.enable") }} {{ trans("switch.settings") }}
            </button>
            
            <button @click="
                notifications = false;
                darkMode = true;
                autoSave = false;
                twoFactor = true;
                analytics = false;
                marketing = true;
            " class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors duration-200">
                {{ trans("switch.disable") }} {{ trans("switch.settings") }}
            </button>
        </div>
    </div>
</div>

@push('styles')
<style>
.translated-switches-demo {
    @apply transition-all duration-300;
}

/* Animation d'entrée pour les cartes */
.translated-switches-demo .bg-white,
.translated-switches-demo .bg-gray-800 {
    animation: slideInUp 0.6s ease-out;
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Effet de hover amélioré */
.translated-switches-demo .bg-white:hover,
.translated-switches-demo .bg-gray-800:hover {
    @apply transform scale-105;
}

/* Animation pour les feedbacks */
.translated-switches-demo .opacity-0 {
    transition: opacity 0.3s ease, transform 0.3s ease;
}

/* Support RTL */
[dir="rtl"] .translated-switches-demo .flex {
    @apply flex-row-reverse;
}

[dir="rtl"] .translated-switches-demo .space-x-2 > * + * {
    @apply ml-0 mr-2;
}

[dir="rtl"] .translated-switches-demo .space-x-4 > * + * {
    @apply ml-0 mr-4;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animation d'entrée séquentielle pour les cartes
    const cards = document.querySelectorAll('.translated-switches-demo .bg-white, .translated-switches-demo .bg-gray-800');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Effet de parallaxe pour le header
    const header = document.querySelector('.translated-switches-demo h1');
    if (header) {
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const rate = scrolled * -0.5;
            header.style.transform = `translateY(${rate}px)`;
        });
    }
});
</script>
@endpush
