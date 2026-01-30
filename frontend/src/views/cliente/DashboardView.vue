<script setup>
import { useAuth } from '@/composables/useAuth'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseAvatar from '@/components/ui/BaseAvatar.vue'
import BaseEmptyState from '@/components/ui/BaseEmptyState.vue'
import { computed } from 'vue'

const { usuario } = useAuth()

const nombreParaMostrar = computed(() => {
  const p = usuario.value?.perfil
  if (!p) return usuario.value?.email || 'Cliente'
  const partes = [p.nombre, p.apellido_paterno, p.apellido_materno].filter(Boolean)
  return partes.length ? partes.join(' ') : p.email || 'Cliente'
})
</script>

<template>
  <div class="p-4 max-w-lg mx-auto">
    <!-- Header -->
    <BaseCard class="mb-4">
      <div class="flex items-center justify-between gap-4">
        <div class="min-w-0 flex-1">
          <p class="text-sm text-gray-500">Hola</p>
          <h1 class="text-xl font-bold text-gray-900 truncate">
            {{ nombreParaMostrar }}
          </h1>
        </div>
        <BaseAvatar :nombre="nombreParaMostrar" size="lg" />
      </div>
    </BaseCard>

    <!-- Mi entrenamiento -->
    <BaseCard class="mb-4">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">Mi Entrenamiento</h2>
      <p class="text-gray-500 text-sm mb-4">
        Aquí verás tu rutina del día, progreso y más.
      </p>
      <BaseEmptyState
        titulo="No tienes rutina asignada"
        descripcion="Tu entrenador te asignará una rutina cuando esté lista."
      />
    </BaseCard>
  </div>
</template>
