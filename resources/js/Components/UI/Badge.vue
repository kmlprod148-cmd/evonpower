<template>
  <span :class="badgeClasses">
    <component v-if="icon" :is="icon" :size="12" />
    <slot />
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  variant: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'primary', 'success', 'warning', 'danger', 'info'].includes(value)
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value)
  },
  icon: {
    type: Object,
    default: null
  },
  rounded: {
    type: Boolean,
    default: false
  }
})

const baseClasses = 'inline-flex items-center gap-1 font-medium'

const variantClasses = computed(() => {
  const variants = {
    default: 'bg-gray-100 text-gray-800',
    primary: 'bg-[rgba(77,208,124,0.1)] text-[#4dd07b]',
    success: 'bg-green-100 text-green-800',
    warning: 'bg-yellow-100 text-yellow-800',
    danger: 'bg-red-100 text-red-800',
    info: 'bg-blue-100 text-blue-800'
  }
  return variants[props.variant]
})

const sizeClasses = computed(() => {
  const sizes = {
    sm: 'px-2 py-0.5 text-xs',
    md: 'px-2.5 py-1 text-sm',
    lg: 'px-3 py-1.5 text-base'
  }
  return sizes[props.size]
})

const badgeClasses = computed(() => {
  return [
    baseClasses,
    variantClasses.value,
    sizeClasses.value,
    props.rounded ? 'rounded-full' : 'rounded'
  ].join(' ')
})
</script>