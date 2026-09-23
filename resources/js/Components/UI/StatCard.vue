<template>
  <div class="bg-white rounded-[15px] p-5 relative" style="box-shadow: 0px 4px 4px rgba(0, 0, 0, 0.04);">
    <p class="text-label mb-2">{{ label }}</p>
    <p class="text-stat text-black">{{ formattedValue }}</p>
    
    <!-- Indicator Circle -->
    <div v-if="showIndicator" class="absolute top-5 right-5 w-10 h-10 rounded-full bg-[rgba(77,208,124,0.20)] flex items-center justify-center">
      <div class="w-6 h-6 rounded-full bg-[rgba(77,208,124,0.20)]"></div>
    </div>

    <!-- Custom Icon -->
    <div v-else-if="icon" class="absolute top-5 right-5">
      <component
        :is="icon"
        :size="iconSize"
        :class="iconColor"
      />
    </div>

    <!-- Trend Indicator -->
    <div v-if="trend" class="mt-2 flex items-center gap-1">
      <component
        :is="trend > 0 ? TrendingUp : TrendingDown"
        :size="16"
        :class="trend > 0 ? 'text-green-600' : 'text-red-600'"
      />
      <span :class="['text-caption', trend > 0 ? 'text-green-600' : 'text-red-600']">
        {{ Math.abs(trend) }}%
      </span>
      <span class="text-caption">vs last period</span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { TrendingUp, TrendingDown } from 'lucide-vue-next'

const props = defineProps({
  label: {
    type: String,
    required: true
  },
  value: {
    type: [Number, String],
    required: true
  },
  showIndicator: {
    type: Boolean,
    default: true
  },
  icon: {
    type: Object,
    default: null
  },
  iconSize: {
    type: Number,
    default: 24
  },
  iconColor: {
    type: String,
    default: 'text-[#4dd07b]'
  },
  trend: {
    type: Number,
    default: null
  },
  format: {
    type: Function,
    default: null
  }
})

const formattedValue = computed(() => {
  if (props.format) {
    return props.format(props.value)
  }
  return props.value
})
</script>