<script setup>
import { RouterLink, useRoute } from 'vue-router'
import AppLayout from '@/layouts/AppLayout.vue'

const route = useRoute()

function isActive(name) {
  return route.name === name
}
</script>

<template>
  <AppLayout>
    <template #topbar>
      <div class="flex items-center justify-between w-full">
        <h1 class="text-lg font-semibold text-gray-900">Coach SaaS</h1>
      </div>
    </template>

    <RouterView />

    <template #bottom-nav>
      <div class="flex items-center justify-around h-full max-w-lg mx-auto">
        <RouterLink
          :to="{ name: 'ClienteDashboard' }"
          :class="[
            'flex flex-col items-center justify-center gap-1 py-2 px-4 text-sm font-medium transition-colors',
            isActive('ClienteDashboard')
              ? 'text-primary-500'
              : 'text-gray-500 hover:text-gray-900'
          ]"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
          </svg>
          Inicio
        </RouterLink>
        <RouterLink
          :to="{ name: 'ClienteRutina' }"
          :class="[
            'flex flex-col items-center justify-center gap-1 py-2 px-4 text-sm font-medium transition-colors',
            isActive('ClienteRutina')
              ? 'text-primary-500'
              : 'text-gray-500 hover:text-gray-900'
          ]"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
          </svg>
          Rutina
        </RouterLink>
        <RouterLink
          :to="{ name: 'ClienteDieta' }"
          :class="[
            'flex flex-col items-center justify-center gap-1 py-2 px-4 text-sm font-medium transition-colors',
            isActive('ClienteDieta')
              ? 'text-primary-500'
              : 'text-gray-500 hover:text-gray-900'
          ]"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          Dieta
        </RouterLink>
        <RouterLink
          :to="{ name: 'ClientePerfil' }"
          :class="[
            'flex flex-col items-center justify-center gap-1 py-2 px-4 text-sm font-medium transition-colors',
            isActive('ClientePerfil')
              ? 'text-primary-500'
              : 'text-gray-500 hover:text-gray-900'
          ]"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
          Perfil
        </RouterLink>
      </div>
    </template>
  </AppLayout>
</template>
