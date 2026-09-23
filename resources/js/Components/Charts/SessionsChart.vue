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
    type: 'bar',
    data: {
      labels: props.data.map(d => d.day || d.label),
      datasets: [{
        label: 'Sessions',
        data: props.data.map(d => d.sessions || d.value),
        backgroundColor: '#10b981',
        borderColor: '#059669',
        borderWidth: 1,
        borderRadius: 4,
        borderSkipped: false,
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
          borderColor: '#10b981',
          borderWidth: 1,
          callbacks: {
            label: function(context) {
              return `Sessions: ${context.parsed.y}`
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
            stepSize: 1,
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

  chart.value.data.labels = props.data.map(d => d.day || d.label)
  chart.value.data.datasets[0].data = props.data.map(d => d.sessions || d.value)
  chart.value.update()
}
</script>
