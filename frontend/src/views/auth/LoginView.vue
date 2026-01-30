<script setup>
import { ref, reactive } from 'vue'
import { useAuth } from '@/composables/useAuth'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'

const { login, cargando, erroresValidacion } = useAuth()

// Estado del formulario
const formulario = reactive({
  email: '',
  password: ''
})

// Errores locales
const errorGeneral = ref('')

// Manejar envío del formulario
async function handleSubmit() {
  errorGeneral.value = ''
  
  const resultado = await login({
    email: formulario.email,
    password: formulario.password
  })
  
  if (!resultado.exito) {
    errorGeneral.value = resultado.error
  }
}
</script>

<template>
  <div class="min-h-screen flex flex-col justify-center bg-gray-50 px-4 py-8 safe-area-top safe-area-bottom">
    <div class="w-full max-w-sm mx-auto">
      <!-- Logo / Título -->
      <div class="text-center mb-8">
        <div class="w-16 h-16 bg-primary-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
          </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">Coach SaaS</h1>
        <p class="text-gray-500 mt-1">Inicia sesión en tu cuenta</p>
      </div>

      <!-- Formulario -->
      <form @submit.prevent="handleSubmit" class="space-y-5">
        <!-- Error general -->
        <div 
          v-if="errorGeneral" 
          class="bg-danger-50 border border-danger-200 text-danger-700 px-4 py-3 rounded-lg text-sm"
        >
          {{ errorGeneral }}
        </div>

        <!-- Email -->
        <BaseInput
          v-model="formulario.email"
          type="email"
          label="Correo electrónico"
          placeholder="tu@email.com"
          :error="erroresValidacion.email?.[0]"
          autocomplete="email"
          required
        >
          <template #icon>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
            </svg>
          </template>
        </BaseInput>

        <!-- Password -->
        <BaseInput
          v-model="formulario.password"
          type="password"
          label="Contraseña"
          placeholder="••••••••"
          :error="erroresValidacion.password?.[0]"
          autocomplete="current-password"
          required
        >
          <template #icon>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
          </template>
        </BaseInput>

        <!-- Botón de submit -->
        <BaseButton
          type="submit"
          variant="primary"
          size="lg"
          :loading="cargando"
          block
        >
          Iniciar Sesión
        </BaseButton>
      </form>

      <!-- Footer -->
      <p class="text-center text-sm text-gray-500 mt-8">
        ¿Olvidaste tu contraseña? Contacta a tu administrador.
      </p>
    </div>
  </div>
</template>
