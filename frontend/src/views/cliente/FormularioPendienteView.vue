<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '@/composables/useApi'
import FormularioDinamico from '@/components/FormularioDinamico.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

const router = useRouter()
const api = useApi()

const cargando = ref(true)
const formulario = ref(null)
const respuestas = ref({})
const enviando = ref(false)
const error = ref(null)

onMounted(async () => {
  await cargarFormulario()
})

async function cargarFormulario() {
  try {
    cargando.value = true
    const response = await api.get('/cliente/formulario-pendiente')
    formulario.value = response.datos
  } catch (err) {
    if (err.response?.status === 404 || !err.response?.data?.datos) {
      // No hay formulario pendiente, redirigir al dashboard
      router.push({ name: 'ClienteDashboard' })
    } else {
      error.value = err.response?.data?.mensaje || 'Error al cargar formulario'
    }
  } finally {
    cargando.value = false
  }
}

async function enviarRespuestas() {
  try {
    enviando.value = true
    error.value = null
    
    // Convertir respuestas de objeto a array indexado
    const respuestasArray = []
    const indices = Object.keys(respuestas.value)
      .map(Number)
      .sort((a, b) => a - b)
    indices.forEach(index => {
      respuestasArray.push(respuestas.value[index])
    })
    
    await api.post(`/cliente/formularios/${formulario.value.id}/responder`, {
      respuestas: respuestasArray
    })
    
    router.push({ name: 'ClienteDashboard' })
  } catch (err) {
    error.value = err.response?.data?.mensaje || 'Error al enviar respuestas'
  } finally {
    enviando.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 py-8 px-4">
    <div class="max-w-2xl mx-auto">
      <div class="bg-white rounded-lg shadow-sm p-6 space-y-6">
        <div class="text-center">
          <h1 class="text-2xl font-bold text-gray-900 mb-2">Formulario Pendiente</h1>
          <p class="text-gray-600">
            Debes completar este formulario antes de acceder a todas las funcionalidades
          </p>
        </div>
        
        <div v-if="cargando" class="text-center py-12">
          <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mb-4"></div>
          <p class="text-gray-600">Cargando formulario...</p>
        </div>
        
        <form v-else-if="formulario" @submit.prevent="enviarRespuestas" class="space-y-6">
          <div>
            <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ formulario.nombre }}</h2>
            
            <FormularioDinamico
              :preguntas="formulario.preguntas"
              v-model="respuestas"
            />
          </div>
          
          <div v-if="error" class="bg-danger-50 border border-danger-200 text-danger-700 px-4 py-3 rounded-lg text-sm">
            {{ error }}
          </div>
          
          <BaseButton
            type="submit"
            :disabled="enviando"
            variant="primary"
            :loading="enviando"
            block
          >
            {{ enviando ? 'Enviando...' : 'Completar Formulario' }}
          </BaseButton>
        </form>
        
        <div v-else class="text-center py-12">
          <p class="text-gray-600 mb-4">No tienes formularios pendientes</p>
          <BaseButton
            @click="router.push({ name: 'ClienteDashboard' })"
            variant="primary"
          >
            Ir al Dashboard
          </BaseButton>
        </div>
      </div>
    </div>
  </div>
</template>

