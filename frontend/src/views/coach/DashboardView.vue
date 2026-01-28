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
            <p class="text-sm text-gray-500">Bienvenido</p>
            <h1 class="text-xl font-bold text-gray-900">
              {{ usuario?.nombre || 'Coach' }}
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
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Panel del Coach</h2>
        <p class="text-gray-600 mb-4">
          Este es el dashboard del coach. Aquí se mostrarán las estadísticas, 
          clientes activos, rutinas y más.
        </p>
        <div class="grid grid-cols-2 gap-3">
          <div class="bg-primary-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-primary-600">0</p>
            <p class="text-sm text-gray-600">Clientes</p>
          </div>
          <div class="bg-success-50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-success-600">0</p>
            <p class="text-sm text-gray-600">Activos</p>
          </div>
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
