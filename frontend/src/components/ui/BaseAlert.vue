<script setup>
/**
 * BaseAlert - Mensaje de feedback (error, éxito, advertencia, info)
 */
const props = defineProps({
  variant: {
    type: String,
    default: 'info',
    validator: (v) => ['info', 'success', 'warning', 'danger'].includes(v)
  },
  title: { type: String, default: '' }
})

const classes = {
  info: 'bg-primary-50 border-primary-200 text-primary-800',
  success: 'bg-success-50 border-success-500/30 text-success-700',
  warning: 'bg-warning-50 border-warning-500/30 text-warning-700',
  danger: 'bg-danger-50 border-danger-500/30 text-danger-700'
}
</script>

<template>
  <div
    :class="[
      'rounded-lg border px-4 py-3 text-sm',
      classes[variant]
    ]"
    role="alert"
  >
    <p v-if="title" class="font-medium">{{ title }}</p>
    <p :class="{ 'mt-1': title }">
      <slot />
    </p>
  </div>
</template>
