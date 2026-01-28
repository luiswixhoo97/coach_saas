<script setup>
import { useAuth } from '@/composables/useAuth'
import BaseButton from '@/components/ui/BaseButton.vue'

const { usuario, logout, cargando } = useAuth()
</script>

<template>
  <div class="min-h-screen bg-gray-50 p-4 safe-area-top safe-area-bottom">
    <div class="max-w-lg mx-auto">
      <!-- Header -->
      <div class="bg-white rounded-2xl p-6 shadow-sm mb-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">Hola</p>
            <h1 class="text-xl font-bold text-gray-900">
              {{ usuario?.nombre || 'Cliente' }}
            </h1>
          </div>
          <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center">
            <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
          </div>
        </div>
      </div>

      <!-- Contenido placeholder -->
      <div class="bg-white rounded-2xl p-6 shadow-sm mb-4">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Mi Entrenamiento</h2>
        <p class="text-gray-600 mb-4">
          Este es tu dashboard personal. Aquí verás tu rutina del día, 
          progreso y más.
        </p>
        <div class="bg-gray-100 rounded-xl p-4 text-center">
          <p class="text-gray-500">No tienes rutina asignada</p>
        </div>
      </div>

      <!-- Cerrar sesión -->
      <BaseButton
        variant="outline"
        :loading="cargando"
        block
        @click="logout"
      >
        Cerrar Sesión
      </BaseButton>
    </div>
  </div>
</template>
