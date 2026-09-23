<template>
  <AppLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-sm font-medium text-gray-500 dark:text-gray-400">Groupes</h2>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
        </div>
      </div>
    </template>

    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Barre de recherche -->
        <div class="mb-6">
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <input
              type="text"
              placeholder="Search"
              class="block w-full pl-10 pr-12 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
            />
            <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
              <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            </div>
          </div>
        </div>

        <!-- Onglets -->
        <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
          <nav class="-mb-px flex space-x-8">
            <a
              href="#"
              class="border-green-500 text-green-600 dark:text-green-400 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
            >
              Dashboard
            </a>
            <a
              href="#"
              class="border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
            >
              Liste des groupes
            </a>
          </nav>
        </div>

        <!-- Cartes statistiques (3 cartes comme dans la maquette) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          <!-- Nombre total de recharge -->
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-5 relative">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Nombre total de recharge</h3>
            <p class="text-3xl font-bold mt-2 text-gray-900 dark:text-white">{{ stats?.sessions?.total_today || 12 }}</p>
            <div class="absolute top-5 right-5 h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
              <div class="h-6 w-6 rounded-full bg-green-300"></div>
            </div>
          </div>

          <!-- Recharge active -->
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-5 relative">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Recharge active</h3>
            <p class="text-3xl font-bold mt-2 text-gray-900 dark:text-white">{{ stats?.sessions?.active || 4 }}</p>
            <div class="absolute top-5 right-5 h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
              <div class="h-6 w-6 rounded-full bg-green-300"></div>
            </div>
          </div>

          <!-- Abonnements actifs -->
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-5 relative">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Abonnements actifs</h3>
            <p class="text-3xl font-bold mt-2 text-gray-900 dark:text-white">{{ stats?.users?.active_today || 3 }}</p>
            <div class="absolute top-5 right-5 h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
              <div class="h-6 w-6 rounded-full bg-green-300"></div>
            </div>
          </div>
        </div>

        <!-- Graphique des recharges -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-5">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-base font-medium text-gray-700 dark:text-gray-300">Nombre des recharges</h3>
            <div class="relative">
              <button class="flex items-center text-sm text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded px-3 py-1">
                Aujourd'hui
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>
            </div>
          </div>
          <div class="h-72">
            <canvas ref="rechargesChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
import AppLayout from '@/Components/Layout/AppLayout.vue'

const props = defineProps({
  stats: {
    type: Object,
    default: () => ({}),
  },
  topChargingPoints: {
    type: Array,
    default: () => [],
  },
  revenueData: {
    type: Array,
    default: () => [],
  },
  sessionsData: {
    type: Array,
    default: () => [],
  },
})

const rechargesChart = ref(null)
let chart = null

const initChart = () => {
  if (!rechargesChart.value) return

  const ctx = rechargesChart.value.getContext('2d')
  
  // Données pour le graphique (échelle 30K comme dans la maquette)
  const labels = ['00h', '03h', '06h', '09h', '12h', '15h', '18h', '21h']
  const data = [10000, 8000, 15000, 20000, 28000, 25000, 20000, 25000]
  
  const gradient = ctx.createLinearGradient(0, 0, 0, 400)
  gradient.addColorStop(0, 'rgba(34, 197, 94, 0.2)') // Vert plus vif
  gradient.addColorStop(1, 'rgba(34, 197, 94, 0)')
  
  // Création du graphique optimisé
  chart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [{
        label: 'Nombre de recharges',
        data: data,
        borderColor: 'rgb(34, 197, 94)', // Vert plus vif
        backgroundColor: gradient,
        tension: 0.4,
        fill: true,
        pointBackgroundColor: 'rgb(34, 197, 94)',
        pointRadius: 4,
        pointHoverRadius: 6
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      animation: {
        duration: 1000
      },
      interaction: {
        mode: 'index',
        intersect: false
      },
      plugins: {
        legend: {
          display: false
        },
        tooltip: {
          enabled: true,
          backgroundColor: 'rgba(0, 0, 0, 0.7)',
          titleColor: '#fff',
          bodyColor: '#fff',
          cornerRadius: 4,
          displayColors: false
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          max: 30000, // Échelle 30K comme dans la maquette
          grid: {
            color: 'rgba(200, 200, 200, 0.1)'
          },
          ticks: {
            callback: function(value) {
              if (value === 0) return '0'
              if (value === 10000) return '10K'
              if (value === 20000) return '20K'
              if (value === 30000) return '30K'
              return ''
            },
            font: {
              size: 10
            }
          }
        },
        x: {
          grid: {
            display: false
          },
          ticks: {
            font: {
              size: 10
            }
          }
        }
      }
    }
  })
}

onMounted(async () => {
  // Attendre que le DOM soit prêt pour initialiser le graphique
  await nextTick()
  
  // Charger Chart.js dynamiquement
  if (typeof Chart === 'undefined') {
    const script = document.createElement('script')
    script.src = 'https://cdn.jsdelivr.net/npm/chart.js'
    script.onload = () => {
      initChart()
    }
    document.head.appendChild(script)
  } else {
    initChart()
  }
})
</script>
