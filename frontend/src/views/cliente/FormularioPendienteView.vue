<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useApi } from '@/composables/useApi'
import FormularioDinamico from '@/components/FormularioDinamico.vue'

const router = useRouter()
const route = useRoute()
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
      // Permitir valores null (preguntas no contestadas)
      respuestasArray.push(respuestas.value[index] === '' ? null : respuestas.value[index])
    })
    
    await api.post('/cliente/formulario-estandar/responder', {
      respuestas: respuestasArray
    })
    
    router.push({ name: 'ClienteDashboard' })
  } catch (err) {
    error.value = err.response?.data?.mensaje || 'Error al enviar respuestas'
  } finally {
    enviando.value = false
  }
}

function cerrar() {
  // Redirigir al dashboard o perfil, no forzar el formulario
  if (route.name === 'ClienteFormularioPendiente') {
    router.push({ name: 'ClienteDashboard' })
  }
}
</script>

<template>
        <Teleport to="body">
          <div class="formulario-pendiente-modal__overlay">
            <div class="formulario-pendiente-modal" @click.stop>
        <!-- Header -->
        <div class="formulario-pendiente-modal__header">
          <h2 class="formulario-pendiente-modal__title">
            {{ formulario?.nombre || 'Formulario Pendiente' }}
          </h2>
          <button
            type="button"
            class="formulario-pendiente-modal__close"
            aria-label="Cerrar"
            @click="cerrar"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 6L6 18M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Body -->
        <div class="formulario-pendiente-modal__body">
          <div v-if="cargando" class="formulario-pendiente-modal__loading">
            <div class="formulario-pendiente-modal__skeleton" />
            <div class="formulario-pendiente-modal__skeleton" />
          </div>

          <div v-else-if="error" class="formulario-pendiente-modal__error">
            {{ error }}
          </div>

          <form v-else-if="formulario" @submit.prevent="enviarRespuestas" class="formulario-pendiente-modal__form">
            <div class="formulario-pendiente-modal__info">
              <p class="formulario-pendiente-modal__info-text">
                Debes completar este formulario antes de acceder a todas las funcionalidades
              </p>
            </div>

            <FormularioDinamico
              :preguntas="formulario.preguntas"
              v-model="respuestas"
            />

            <div v-if="error" class="formulario-pendiente-modal__error-message">
              {{ error }}
            </div>
          </form>

          <div v-else class="formulario-pendiente-modal__empty">
            <p class="formulario-pendiente-modal__empty-text">No tienes formularios pendientes</p>
          </div>
        </div>

        <!-- Footer -->
        <div v-if="!cargando && !error && formulario" class="formulario-pendiente-modal__footer">
          <button
            type="button"
            class="formulario-pendiente-modal__btn formulario-pendiente-modal__btn--secondary"
            @click="cerrar"
            :disabled="enviando"
          >
            Cancelar
          </button>
          <button
            type="submit"
            class="formulario-pendiente-modal__btn formulario-pendiente-modal__btn--primary"
            @click="enviarRespuestas"
            :disabled="enviando"
          >
            <span v-if="enviando" class="formulario-pendiente-modal__btn-spinner"></span>
            <span v-else>Completar Formulario</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.formulario-pendiente-modal__overlay {
  position: fixed;
  inset: 0;
  background: transparent; /* Sin fondo negro, se ve lo que está detrás */
  display: flex;
  align-items: flex-end;
  justify-content: center;
  z-index: 2000;
  animation: formulario-pendiente-modal-fade 0.2s ease;
  pointer-events: none; /* Permitir interacción con el contenido detrás */
}

@keyframes formulario-pendiente-modal-fade {
  from { opacity: 0; }
  to { opacity: 1; }
}

.formulario-pendiente-modal {
  background: #161616;
  border-radius: 20px 20px 0 0;
  width: 100%;
  max-width: 600px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  animation: formulario-pendiente-modal-slide 0.3s ease;
  pointer-events: auto; /* Permitir interacción con el modal */
  box-shadow: 0 -4px 24px rgba(0, 0, 0, 0.5); /* Sombra para destacar el modal */
}

@keyframes formulario-pendiente-modal-slide {
  from { transform: translateY(100%); }
  to { transform: translateY(0); }
}

@media (min-width: 640px) {
  .formulario-pendiente-modal__overlay {
    align-items: center;
  }
  .formulario-pendiente-modal {
    border-radius: 20px;
    max-height: 80vh;
  }
}

.formulario-pendiente-modal__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.25rem 1.25rem 0;
  flex-shrink: 0;
}

.formulario-pendiente-modal__title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #fff;
  margin: 0;
  flex: 1;
}

.formulario-pendiente-modal__close {
  background: #2a2a2a;
  border: none;
  border-radius: 50%;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #697586;
  flex-shrink: 0;
  transition: background 0.2s, color 0.2s;
}

.formulario-pendiente-modal__close:hover {
  background: #333;
  color: #fff;
}

.formulario-pendiente-modal__close svg {
  width: 18px;
  height: 18px;
}

.formulario-pendiente-modal__body {
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 1.25rem;
  -webkit-overflow-scrolling: touch;
}

.formulario-pendiente-modal__body::-webkit-scrollbar {
  width: 6px;
}

.formulario-pendiente-modal__body::-webkit-scrollbar-track {
  background: #1a1a1a;
  border-radius: 3px;
}

.formulario-pendiente-modal__body::-webkit-scrollbar-thumb {
  background: #333;
  border-radius: 3px;
}

.formulario-pendiente-modal__body::-webkit-scrollbar-thumb:hover {
  background: #444;
}

.formulario-pendiente-modal__loading {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.formulario-pendiente-modal__skeleton {
  height: 60px;
  background: #2a2a2a;
  border-radius: 8px;
  animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.formulario-pendiente-modal__error {
  color: #EF5C5C;
  font-size: 0.875rem;
  padding: 1rem;
  text-align: center;
}

.formulario-pendiente-modal__form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.formulario-pendiente-modal__info {
  padding: 0.75rem 1rem;
  background: #2a2a2a;
  border-radius: 8px;
  border: 1px solid #333;
  margin-bottom: 1rem;
}

.formulario-pendiente-modal__info-text {
  margin: 0;
  font-size: 0.875rem;
  color: #9CA3AF;
}

.formulario-pendiente-modal__error-message {
  font-size: 0.875rem;
  color: #EF5C5C;
  padding: 0.75rem;
  background: rgba(239, 92, 92, 0.12);
  border: 1px solid rgba(239, 92, 92, 0.3);
  border-radius: 12px;
}

.formulario-pendiente-modal__empty {
  text-align: center;
  padding: 2rem 1rem;
}

.formulario-pendiente-modal__empty-text {
  color: #9CA3AF;
  font-size: 0.875rem;
  margin: 0;
}

.formulario-pendiente-modal__footer {
  display: flex;
  gap: 0.75rem;
  padding: 1.25rem;
  border-top: 1px solid #252525;
  flex-shrink: 0;
}

.formulario-pendiente-modal__btn {
  flex: 1;
  padding: 0.625rem 1.25rem;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.formulario-pendiente-modal__btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.formulario-pendiente-modal__btn--secondary {
  background: #252525;
  color: #a0a0a0;
}

.formulario-pendiente-modal__btn--secondary:hover:not(:disabled) {
  background: #2a2a2a;
  color: #fff;
}

.formulario-pendiente-modal__btn--primary {
  background: #00D261;
  color: #0a0a0a;
}

.formulario-pendiente-modal__btn--primary:hover:not(:disabled) {
  opacity: 0.9;
}

.formulario-pendiente-modal__btn-spinner {
  display: inline-block;
  width: 16px;
  height: 16px;
  border: 2px solid rgba(10, 10, 10, 0.3);
  border-top-color: #0a0a0a;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
