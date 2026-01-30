<script setup>
/**
 * BaseBadge - Etiqueta de estado (activo, inactivo, éxito, advertencia, etc.)
 */
const props = defineProps({
  variant: {
    type: String,
    default: 'neutral',
    validator: (v) => ['neutral', 'success', 'warning', 'danger', 'primary'].includes(v)
  },
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['sm', 'md'].includes(v)
  }
})

const classes = {
  neutral: 'bg-gray-200 text-gray-800',
  success: 'bg-success-50 text-success-700',
  warning: 'bg-warning-50 text-warning-700',
  danger: 'bg-danger-50 text-danger-700',
  primary: 'bg-primary-100 text-primary-700'
}
</script>

<template>
  <span
    :class="[
      'inline-flex items-center font-medium rounded-full',
      size === 'sm' ? 'px-2 py-0.5 text-xs' : 'px-2.5 py-1 text-sm',
      classes[variant]
    ]"
  >
    <slot />
  </span>
</template>
