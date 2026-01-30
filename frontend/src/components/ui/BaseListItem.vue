<script setup>
/**
 * BaseListItem - Fila de lista con slot izquierdo (avatar), título, subtítulo, slot derecho (acciones)
 */
defineProps({
  titulo: { type: String, default: '' },
  subtitulo: { type: String, default: '' }
})
</script>

<template>
  <div class="flex items-center gap-4 py-3 px-1">
    <div v-if="$slots.left" class="flex-shrink-0">
      <slot name="left" />
    </div>
    <div class="flex-1 min-w-0">
      <p v-if="titulo" class="font-medium text-gray-900 truncate">{{ titulo }}</p>
      <p v-if="subtitulo" class="text-sm text-gray-500 truncate">{{ subtitulo }}</p>
      <slot name="default" />
    </div>
    <div v-if="$slots.right" class="flex-shrink-0">
      <slot name="right" />
    </div>
  </div>
</template>
