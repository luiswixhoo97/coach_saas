<script setup>
/**
 * BaseCard - Contenedor con borde, sombra y padding
 * Props: padding (default true), shadow (default true)
 */
defineProps({
  padding: { type: Boolean, default: true },
  shadow: { type: Boolean, default: true }
})
</script>

<template>
  <div
    :class="[
      'rounded-2xl border border-gray-200 bg-white',
      padding && 'p-6',
      shadow && 'shadow-sm'
    ]"
  >
    <slot />
  </div>
</template>
