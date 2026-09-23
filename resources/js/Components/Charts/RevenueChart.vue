<template>
  <div class="relative">
    <canvas ref="chartRef" />
  </div>
</template>

<script setup>
import { ref, onMounted, watch, onUnmounted } from 'vue'
import { Chart, registerables } from 'chart.js'

Chart.register(...registerables)

const props = defineProps({
  data: {
    type: Array,
    required: true,
  },
})

const chartRef = ref(null)
const chart = ref(null)

onMounted(() => {
  createChart()
})

onUnmounted(() => {
  if (chart.value) {
    chart.value.destroy()
  }
})

watch(() => props.data, () => {
  updateChart()
}, { deep: true })

function createChart() {
  if (!chartRef.value) return

  const ctx = chartRef.value.getContext('2d')
  
  chart.value = new Chart(ctx, {
    type: 'line',
    data: {
      labels: props.data.map(d => d.month || d.label),
      datasets: [{
        label: 'Revenus',
        data: props.data.map(d => d.revenue || d.value),
        borderColor: '#3b82f6',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        borderWidth: 2,
        fill: true,
        tension: 0.4,
        pointBackgroundColor: '#3b82f6',
        pointBorderColor: '#ffffff',
        pointBorderWidth: 2,
        pointRadius: 4,
        pointHoverRadius: 6,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false,
        },
        tooltip: {
          backgroundColor: 'rgba(0, 0, 0, 0.8)',
          titleColor: '#ffffff',
          bodyColor: '#ffffff',
          borderColor: '#3b82f6',
          borderWidth: 1,
          callbacks: {
            label: function(context) {
              return `Revenus: ${new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: 'EUR'
              }).format(context.parsed.y)}`
            }
          }
        }
      },
      scales: {
        x: {
          grid: {
            display: false,
          },
          ticks: {
            color: '#6b7280',
          }
        },
        y: {
          beginAtZero: true,
          grid: {
            color: '#e5e7eb',
          },
          ticks: {
            color: '#6b7280',
            callback: function(value) {
              return new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: 'EUR',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
              }).format(value)
            }
          }
        }
      },
      interaction: {
        intersect: false,
        mode: 'index',
      },
    }
  })
}

function updateChart() {
  if (!chart.value) return

  chart.value.data.labels = props.data.map(d => d.month || d.label)
  chart.value.data.datasets[0].data = props.data.map(d => d.revenue || d.value)
  chart.value.update()
}
</script>
