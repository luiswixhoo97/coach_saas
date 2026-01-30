<script setup>
/**
 * BaseAvatar - Avatar con iniciales o imagen
 */
const props = defineProps({
  nombre: { type: String, default: '' },
  src: { type: String, default: '' },
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['sm', 'md', 'lg'].includes(v)
  }
})

const tamanos = { sm: 'w-8 h-8 text-sm', md: 'w-12 h-12 text-base', lg: 'w-16 h-16 text-lg' }

function iniciales(nombre) {
  if (!nombre || typeof nombre !== 'string') return '?'
  const partes = nombre.trim().split(/\s+/)
  if (partes.length >= 2) {
    return (partes[0][0] + partes[partes.length - 1][0]).toUpperCase()
  }
  return nombre.slice(0, 2).toUpperCase()
}
</script>

<template>
  <div
    :class="[
      'rounded-full flex items-center justify-center flex-shrink-0 overflow-hidden bg-primary-100 text-primary-600 font-medium',
      tamanos[size]
    ]"
  >
    <img v-if="src" :src="src" alt="" class="w-full h-full object-cover" />
    <span v-else>{{ iniciales(nombre) }}</span>
  </div>
</template>
