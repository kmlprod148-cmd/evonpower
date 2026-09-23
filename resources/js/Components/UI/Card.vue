<template>
  <div
    :class="[
      'bg-white rounded-[15px]',
      padding ? paddingClasses : '',
      shadow ? 'shadow-[0px_4px_4px_rgba(0,0,0,0.04)]' : '',
      hover ? 'transition-shadow hover:shadow-lg' : '',
      className
    ]"
  >
    <!-- Header -->
    <div v-if="$slots.header || title" :class="['border-b border-gray-100', headerPadding ? 'p-6 pb-4' : '']">
      <slot name="header">
        <h3 class="text-sm font-semibold text-black" style="font-family: 'Poppins', sans-serif; line-height: 20px;">
          {{ title }}
        </h3>
      </slot>
    </div>

    <!-- Content -->
    <div :class="contentPadding ? 'p-6' : ''">
      <slot />
    </div>

    <!-- Footer -->
    <div v-if="$slots.footer" :class="['border-t border-gray-100', footerPadding ? 'p-6 pt-4' : '']">
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  title: {
    type: String,
    default: ''
  },
  padding: {
    type: Boolean,
    default: true
  },
  shadow: {
    type: Boolean,
    default: true
  },
  hover: {
    type: Boolean,
    default: false
  },
  headerPadding: {
    type: Boolean,
    default: true
  },
  contentPadding: {
    type: Boolean,
    default: true
  },
  footerPadding: {
    type: Boolean,
    default: true
  },
  className: {
    type: String,
    default: ''
  }
})

const paddingClasses = computed(() => {
  return props.padding ? 'p-6' : ''
})
</script>