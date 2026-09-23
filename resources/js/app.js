import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { InertiaProgress } from '@inertiajs/progress'
import { createPinia } from 'pinia'
import { QueryClient, VueQueryPlugin } from '@tanstack/vue-query'
import Alpine from 'alpinejs'

// Initialize Alpine.js
window.Alpine = Alpine
Alpine.start()

// Configuration de Vue Query
const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            staleTime: 5 * 60 * 1000, // 5 minutes
            retry: 1,
        },
    },
})

createInertiaApp({
    resolve: name => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true })
        return pages[`./Pages/${name}.vue`]
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
        
        // Plugins
        app.use(plugin)
        app.use(createPinia())
        app.use(VueQueryPlugin, { queryClient })
        
        // Configuration globale
        app.config.globalProperties.$filters = {
            formatCurrency(value, currency = 'EUR') {
                const locale = currency === 'EUR' ? 'fr-FR' : (currency === 'USD' ? 'en-US' : 'fr-MA');
                return new Intl.NumberFormat(locale, {
                    style: 'currency',
                    currency: currency
                }).format(value)
            },
            formatDate(value) {
                return new Date(value).toLocaleDateString('fr-FR')
            },
            formatDateTime(value) {
                return new Date(value).toLocaleString('fr-FR')
            }
        }
        
        app.mount(el)
    },
})

// Configuration de la barre de progression
InertiaProgress.init({
    color: '#3b82f6',
    showSpinner: true,
})
