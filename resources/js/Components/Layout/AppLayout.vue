<template>
  <div class="min-h-screen bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <!-- Logo -->
            <div class="flex-shrink-0 flex items-center">
              <h1 class="text-xl font-bold">
                <span class="text-green-600">EVON</span>
                <span class="text-gray-900 dark:text-white"> APP</span>
              </h1>
            </div>
          </div>

          <!-- Navigation droite -->
          <div class="flex items-center space-x-4">
            <!-- Language Switcher -->
            <Menu as="div" class="relative inline-block text-left">
              <div>
                <MenuButton class="inline-flex justify-center w-full rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-blue-500">
                  <img :src="currentLocaleFlag" :alt="currentLocaleName" class="h-4 w-4 mr-2">
                  {{ currentLocaleName }}
                  <ChevronDownIcon class="-mr-1 ml-2 h-5 w-5" aria-hidden="true" />
                </MenuButton>
              </div>

              <transition
                enter-active-class="transition ease-out duration-100"
                enter-from-class="transform opacity-0 scale-95"
                enter-to-class="transform opacity-100 scale-100"
                leave-active-class="transition ease-in duration-75"
                leave-from-class="transform opacity-100 scale-100"
                leave-to-class="transform opacity-0 scale-95"
              >
                <MenuItems class="origin-top-right absolute right-0 mt-2 w-40 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-50">
                  <div class="py-1">
                    <MenuItem v-for="(name, locale) in localeNames" :key="locale" v-slot="{ active }">
                      <a
                        href="#"
                        @click.prevent="switchLanguage(locale)"
                        :class="[
                          active ? 'bg-gray-100 text-gray-900' : 'text-gray-700',
                          'flex items-center px-4 py-2 text-sm'
                        ]"
                      >
                        <img :src="`https://flagcdn.com/w20/${localeFlags[locale]}.png`" :alt="name" class="h-4 w-4 mr-2">
                        {{ name }}
                      </a>
                    </MenuItem>
                  </div>
                </MenuItems>
              </transition>
            </Menu>

            <!-- Notifications -->
            <Menu as="div" class="relative inline-block text-left">
              <div>
                <MenuButton class="relative p-2 text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 rounded-full">
                  <BellIcon class="h-6 w-6" aria-hidden="true" />
                  <span class="notification-count absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 bg-red-600 rounded-full transform translate-x-1/2 -translate-y-1/2" style="display: none;">0</span>
                </MenuButton>
              </div>

              <transition
                enter-active-class="transition ease-out duration-100"
                enter-from-class="transform opacity-0 scale-95"
                enter-to-class="transform opacity-100 scale-100"
                leave-active-class="transition ease-in duration-75"
                leave-from-class="transform opacity-100 scale-100"
                leave-to-class="transform opacity-0 scale-95"
              >
                <MenuItems class="origin-top-right absolute right-0 mt-2 w-80 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-50">
                  <div class="py-1 notification-list max-h-60 overflow-y-auto">
                    <!-- Notifications will be injected here by notification-api-fix.js -->
                    <div class="text-gray-500 text-sm p-2 text-center">Chargement des notifications...</div>
                  </div>
                  <div class="border-t border-gray-200">
                    <Link :href="route('settings.notifications')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 text-center">
                      Voir toutes les notifications
                    </Link>
                  </div>
                </MenuItems>
              </transition>
            </Menu>

            <!-- Profil utilisateur -->
            <Menu as="div" class="relative">
              <MenuButton class="flex items-center space-x-2 text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <div class="h-8 w-8 rounded-full bg-blue-600 flex items-center justify-center">
                  <span class="text-white font-medium">{{ userInitials }}</span>
                </div>
                <span class="text-gray-700">{{ auth.user.name }}</span>
                <ChevronDownIcon class="h-4 w-4 text-gray-400" />
              </MenuButton>

              <transition
                enter-active-class="transition ease-out duration-100"
                enter-from-class="transform opacity-0 scale-95"
                enter-to-class="transform opacity-100 scale-100"
                leave-active-class="transition ease-in duration-75"
                leave-from-class="transform opacity-100 scale-100"
                leave-to-class="transform opacity-0 scale-95"
              >
                <MenuItems class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                  <MenuItem v-slot="{ active }">
                    <Link
                      :href="route('profile.edit')"
                      :class="[active ? 'bg-gray-100' : '', 'block px-4 py-2 text-sm text-gray-700']"
                    >
                      Profil
                    </Link>
                  </MenuItem>
                  <MenuItem v-slot="{ active }">
                    <Link
                      :href="route('settings.index')"
                      :class="[active ? 'bg-gray-100' : '', 'block px-4 py-2 text-sm text-gray-700']"
                    >
                      Paramètres
                    </Link>
                  </MenuItem>
                  <MenuItem v-slot="{ active }">
                    <form @submit.prevent="logout" method="POST">
                      <button
                        type="submit"
                        :class="[active ? 'bg-gray-100' : '', 'block w-full text-left px-4 py-2 text-sm text-gray-700']"
                      >
                        Déconnexion
                      </button>
                    </form>
                  </MenuItem>
                </MenuItems>
              </transition>
            </Menu>
          </div>
        </div>
      </div>
    </nav>

    <div class="flex">
      <!-- Sidebar -->
      <div class="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0">
        <div class="flex-1 flex flex-col min-h-0 bg-white border-r border-gray-200">
          <div class="flex-1 flex flex-col pt-5 pb-4 overflow-y-auto">
            <nav class="mt-5 flex-1 px-2 space-y-1">
              <template v-for="item in navigation" :key="item.name">
                <Link
                  v-if="!item.children"
                  :href="item.href"
                  :class="[
                    isCurrentRoute(item.route)
                      ? 'bg-blue-50 border-blue-500 text-blue-700'
                      : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                    'group flex items-center px-2 py-2 text-sm font-medium border-r-2'
                  ]"
                >
                  <component
                    :is="item.icon"
                    :class="[
                      isCurrentRoute(item.route)
                        ? 'text-blue-500'
                        : 'text-gray-400 group-hover:text-gray-500',
                      'mr-3 flex-shrink-0 h-6 w-6'
                    ]"
                  />
                  {{ item.name }}
                </Link>

                <Disclosure v-else as="div" class="space-y-1">
                  <DisclosureButton
                    :class="[
                      isCurrentRoute(item.route)
                        ? 'bg-blue-50 border-blue-500 text-blue-700'
                        : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                      'group w-full flex items-center px-2 py-2 text-sm font-medium border-r-2'
                    ]"
                  >
                    <component
                      :is="item.icon"
                      :class="[
                        isCurrentRoute(item.route)
                          ? 'text-blue-500'
                          : 'text-gray-400 group-hover:text-gray-500',
                        'mr-3 flex-shrink-0 h-6 w-6'
                      ]"
                    />
                    {{ item.name }}
                    <ChevronRightIcon
                      :class="[
                        'ml-auto flex-shrink-0 h-5 w-5 transform transition-transform duration-150',
                        open ? 'rotate-90' : ''
                      ]"
                    />
                  </DisclosureButton>
                  <DisclosurePanel class="space-y-1">
                    <Link
                      v-for="child in item.children"
                      :key="child.name"
                      :href="child.href"
                      :class="[
                        isCurrentRoute(child.route)
                          ? 'bg-blue-50 border-blue-500 text-blue-700'
                          : 'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                        'group flex items-center pl-11 pr-2 py-2 text-sm font-medium border-r-2'
                      ]"
                    >
                      {{ child.name }}
                    </Link>
                  </DisclosurePanel>
                </Disclosure>
              </template>
            </nav>
          </div>
        </div>
      </div>

      <!-- Contenu principal -->
      <div class="md:pl-64 flex flex-col flex-1">
        <main class="flex-1">
          <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
              <!-- Flash Messages -->
              <div v-if="$page.props.flash.message" class="mb-4">
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
                  {{ $page.props.flash.message }}
                </div>
              </div>
              
              <div v-if="$page.props.flash.error" class="mb-4">
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                  {{ $page.props.flash.error }}
                </div>
              </div>

              <!-- Contenu de la page -->
              <slot />
            </div>
          </div>
        </main>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import {
  Menu,
  MenuButton,
  MenuItem,
  MenuItems,
  Disclosure,
  DisclosureButton,
  DisclosurePanel,
} from '@headlessui/vue'
import {
  HomeIcon,
  BoltIcon,
  CreditCardIcon,
  UsersIcon,
  CogIcon,
  ChartBarIcon,
  BellIcon,
  ChevronDownIcon,
  ChevronRightIcon,
} from '@heroicons/vue/24/outline'

const page = usePage()
const auth = computed(() => page.props.auth)

const currentLocale = ref(page.props.locale || 'en'); // Initialize with current locale

const localeNames = {
  'fr': 'Français',
  'en': 'English',
  'ar': 'العربية',
  'es': 'Español'
};

const localeFlags = {
  'fr': 'fr',
  'en': 'gb', // Great Britain flag for English
  'ar': 'ma', // Morocco flag for Arabic
  'es': 'es'
};

const currentLocaleName = computed(() => localeNames[currentLocale.value] || currentLocale.value);
const currentLocaleFlag = computed(() => `https://flagcdn.com/w20/${localeFlags[currentLocale.value]}.png`);

const switchLanguage = async (nextLocale) => {
  try {
    const response = await fetch('/language', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': page.props.csrf_token // Assuming CSRF token is available in page props
      },
      body: JSON.stringify({ locale: nextLocale })
    });
    const data = await response.json();
    if (data.status === 'success') {
      currentLocale.value = data.locale;
      window.location.reload(); // Reload page to apply new translations
    } else {
      console.error('Failed to switch language:', data.message);
      // Optionally show a user-friendly error notification
    }
  } catch (error) {
    console.error('Error switching language:', error);
    // Optionally show a user-friendly error notification
  }
};

const userInitials = computed(() => {
  if (!auth.value.user) return ''
  return auth.value.user.name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
})

// Fonction route globale (fallback si pas disponible)
const route = (name, params = {}) => {
  if (typeof window.route === 'function') {
    return window.route(name, params)
  }
  // Fallback pour les routes de base
  const routes = {
    'dashboard': '/dashboard',
    'groups.index': '/groups',
    'integrators.index': '/integrators',
    'charging-points.index': '/charging-points',
    'pricing-plans.index': '/pricing-plans',
    'plans.index': '/plans',
    'admin.users.index': '/admin/users',
    'reports.index': '/reports',
    'transactions.index': '/transactions',
    'integrator.profile': '/integrator/profile',
    'partner.profile': '/partner/profile',
    'partners.index': '/partners',
    'profile.edit': '/profile',
    'logout': '/logout'
  }
  return routes[name] || '#'
}

// Fonction pour vérifier la route actuelle
const isCurrentRoute = (routeName) => {
  if (typeof window.route === 'function' && window.route().current) {
    return window.route().current(routeName)
  }
  // Fallback simple
  return window.location.pathname.includes(routeName.replace('.*', ''))
}

const navigation = computed(() => [
  {
    name: 'Intégrateurs',
    href: route('integrators.index'),
    route: 'integrators.*',
    icon: UsersIcon,
  },
  {
    name: 'Groupes',
    href: route('groups.index'),
    route: 'groups.*',
    icon: HomeIcon,
  },
  {
    name: 'Plans',
    href: route('plans.index'),
    route: 'plans.*',
    icon: CreditCardIcon,
  },
  {
    name: 'Profil intégrateur',
    href: route('integrator.profile'),
    route: 'integrator.profile.*',
    icon: UsersIcon,
  },
  {
    name: 'Profil partenaire',
    href: route('partner.profile'),
    route: 'partner.profile.*',
    icon: CogIcon,
  },
  {
    name: 'Partenaires/Opérateurs',
    href: route('partners.index'),
    route: 'partners.*',
    icon: CogIcon,
  },
  {
    name: 'Points de charges',
    href: route('charging-points.index'),
    route: 'charging-points.*',
    icon: BoltIcon,
  },
  {
    name: 'Utilisateurs',
    href: route('admin.users.index'),
    route: 'admin.users.*',
    icon: UsersIcon,
    can: 'manage_users',
  },
  {
    name: 'Transactions',
    href: route('transactions.index'),
    route: 'transactions.*',
    icon: ChartBarIcon,
  },
  {
    name: 'Rapports',
    href: route('reports.index'),
    route: 'reports.*',
    icon: ChartBarIcon,
    can: 'view_reports',
  },
])

onMounted(() => {
  // Dynamically load the notification script if not already loaded
  if (!document.querySelector('script[src="/js/notification-api-fix.js"]')) {
    const script = document.createElement('script');
    script.src = '/js/notification-api-fix.js';
    script.async = true;
    document.body.appendChild(script);
  }
});

const logout = () => {
  router.post(route('logout'), {}, {
    onSuccess: () => {
      // Redirection gérée par Laravel
    }
  })
}
</script>
