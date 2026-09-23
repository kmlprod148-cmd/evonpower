<template>
  <div class="bg-white rounded-[15px] p-6" style="box-shadow: 0px 4px 4px rgba(0, 0, 0, 0.04);">
    <!-- Chart Header -->
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-sm font-semibold text-black" style="font-family: 'Poppins', sans-serif; line-height: 20px;">
        {{ title }}
      </h3>
      
      <!-- Filter/Actions -->
      <div v-if="$slots.actions || showFilter" class="flex items-center gap-2">
        <slot name="actions">
          <button
            v-if="showFilter"
            class="flex items-center gap-1 px-3 py-1.5 rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors"
            @click="$emit('filter-click')"
          >
            <span class="text-button text-black" style="font-family: 'Poppins', sans-serif;">
              {{ filterLabel }}
            </span>
            <ChevronDown :size="9" class="text-[rgba(0,0,0,0.40)]" />
          </button>
        </slot>
      </div>
    </div>

    <!-- Chart Content -->
    <div :class="['relative', heightClass]">
      <slot />
    </div>

    <!-- Legend -->
    <div v-if="$slots.legend" class="mt-4 pt-4 border-t border-[#e9e9e9]">
      <slot name="legend" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { ChevronDown } from 'lucide-vue-next'

const props = defineProps({
  title: {
    type: String,
    required: true
  },
  showFilter: {
    type: Boolean,
    default: false
  },
  filterLabel: {
    type: String,
    default: "Aujourd'hui"
  },
  height: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg', 'xl'].includes(value)
  }
})

defineEmits(['filter-click'])

const heightClass = computed(() => {
  const heights = {
    sm: 'h-48',
    md: 'h-64',
    lg: 'h-80',
    xl: 'h-96'
  }
  return heights[props.height]
})
</script>