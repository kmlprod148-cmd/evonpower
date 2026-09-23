<template>
  <div class="flex h-screen bg-[#f9f9f9]" style="font-family: 'Poppins', sans-serif;">
    <!-- Sidebar -->
    <aside class="w-[233px] bg-white border-r border-[rgba(0,0,0,0.10)] flex flex-col">
      <!-- Logo -->
      <div class="px-3 py-6">
        <div class="flex items-center gap-1">
          <span class="text-[#4dd07b] text-xl font-bold">EVON</span>
          <span class="text-xs text-gray-600">APP</span>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 px-5 space-y-1">
        <Link
          v-for="item in navigationItems"
          :key="item.name"
          :href="item.href"
          :class="[
            'flex items-center gap-2 px-0 py-2.5 rounded-lg text-body transition-colors',
            isActive(item.route) 
              ? 'text-[#49ce7d] bg-[rgba(77,208,124,0.08)]' 
              : 'text-black hover:bg-gray-50'
          ]"
        >
          <component 
            :is="item.icon" 
            :size="20"
            :class="[
              'flex-shrink-0',
              isActive(item.route) ? 'text-[#49ce7d]' : 'text-black'
            ]"
            :stroke-width="isActive(item.route) ? 2.5 : 2"
          />
          <span>{{ item.name }}</span>
        </Link>
      </nav>

      <!-- Settings at bottom -->
      <div class="px-5 py-6">
        <Link
          :href="route('settings.index')"
          class="flex items-center gap-2 px-0 py-2.5 rounded-lg text-body text-black hover:bg-gray-50 transition-colors"
        >
          <Settings :size="20" class="flex-shrink-0" :stroke-width="2" />
          <span>Paramètres</span>
        </Link>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
      <!-- Header -->
      <header class="bg-white border-b border-[rgba(0,0,0,0.10)] px-7 py-5 flex items-center justify-between">
        <!-- Search -->
        <div class="relative w-[160px]">
          <div class="absolute inset-0 flex items-center justify-between px-3 pointer-events-none">
            <div class="flex items-center gap-2">
              <Search :size="13" class="text-[rgba(0,0,0,0.20)]" />
              <span class="text-body text-[rgba(0,0,0,0.20)]">Search</span>
            </div>
            <span class="text-body text-[rgba(0,0,0,0.20)]">⌘/</span>
          </div>
          <input
            type="text"
            class="w-full h-7 px-3 bg-[rgba(0,0,0,0.04)] border-0 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#4dd07b]"
            style="font-family: 'Poppins', sans-serif;"
          />
        </div>

        <!-- Right side utilities -->
        <div class="flex items-center gap-3.5">
          <!-- Flag -->
          <button class="w-7 h-7 rounded-lg hover:bg-gray-100 flex items-center justify-center">
            <Flag :size="20" class="text-gray-600" />
          </button>

          <!-- Theme toggle -->
          <button class="w-7 h-7 rounded-lg hover:bg-gray-100 flex items-center justify-center">
            <Sun :size="20" class="text-gray-600" />
          </button>

          <!-- Notifications -->
          <button class="w-7 h-7 rounded-lg hover:bg-gray-100 flex items-center justify-center">
            <Bell :size="20" class="text-gray-600" />
          </button>

          <!-- User profile -->
          <button class="w-7 h-7 rounded-lg hover:bg-gray-100 flex items-center justify-center">
            <User :size="20" class="text-gray-600" />
          </button>
        </div>
      </header>

      <!-- Page Content -->
      <main class="flex-1 overflow-y-auto">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { 
  Users, 
  Leaf, 
  Receipt, 
  User,
  Building,
  Plug,
  DollarSign,
  BarChart,
  TowerControl,
  Settings,
  Search,
  Flag,
  Sun,
  Bell
} from 'lucide-vue-next'

const page = usePage()

// Fonction route globale (fallback si pas disponible)
const route = (name, params = {}) => {
  if (typeof window.route === 'function') {
    return window.route(name, params)
  }
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
    'settings.index': '/settings'
  }
  return routes[name] || '#'
}

const isActive = (routeName) => {
  if (typeof window.route === 'function' && window.route().current) {
    return window.route().current(routeName)
  }
  return window.location.pathname.includes(routeName.replace('.*', ''))
}

const navigationItems = [
  {
    name: 'Intégrateurs',
    href: route('integrators.index'),
    route: 'integrators.*',
    icon: Users,
  },
  {
    name: 'Groupes',
    href: route('groups.index'),
    route: 'groups.*',
    icon: Leaf,
  },
  {
    name: 'Plan tarifaire',
    href: route('plans.index'),
    route: 'plans.*',
    icon: Receipt,
  },
  {
    name: 'Profil intégrateur',
    href: route('integrator.profile'),
    route: 'integrator.profile.*',
    icon: User,
  },
  {
    name: 'Profil partenaire',
    href: route('partner.profile'),
    route: 'partner.profile.*',
    icon: Building,
  },
  {
    name: 'Partenaires/Opérateurs',
    href: route('partners.index'),
    route: 'partners.*',
    icon: Building,
  },
  {
    name: 'Points de charges',
    href: route('charging-points.index'),
    route: 'charging-points.*',
    icon: Plug,
  },
  {
    name: 'Utilisateurs',
    href: route('admin.users.index'),
    route: 'admin.users.*',
    icon: Users,
  },
  {
    name: 'Transactions',
    href: route('transactions.index'),
    route: 'transactions.*',
    icon: DollarSign,
  },
  {
    name: 'Rapports',
    href: route('reports.index'),
    route: 'reports.*',
    icon: BarChart,
  },
  {
    name: 'Contrôle à distance',
    href: route('dashboard'),
    route: 'remote.*',
    icon: TowerControl,
  },
]
</script>