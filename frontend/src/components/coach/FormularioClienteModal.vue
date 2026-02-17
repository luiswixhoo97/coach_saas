<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useApi } from '@/composables/useApi'
import FormularioDinamico from '@/components/FormularioDinamico.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

const props = defineProps({
  cliente: {
    type: Object,
    required: true
  },
  formulario: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'completado'])

const { get, post, cargando } = useApi()

const respuestas = ref({})
const enviando = ref(false)
const error = ref('')

// Cargar formulario si no viene completo
const formularioCompleto = ref(props.formulario)

onMounted(async () => {
  if (!formularioCompleto.value.preguntas) {
    try {
      const response = await get(`/coach/formularios/${formularioCompleto.value.id}`)
      formularioCompleto.value = response.datos
    } catch (err) {
      error.value = err.response?.data?.mensaje || 'Error al cargar formulario'
    }
  }
})

async function enviarFormulario() {
  if (enviando.value) return

  try {
    enviando.value = true
    error.value = ''

    // Convertir respuestas de objeto a array indexado
    const respuestasArray = []
    const indices = Object.keys(respuestas.value)
      .map(Number)
      .sort((a, b) => a - b)
    indices.forEach(index => {
      respuestasArray.push(respuestas.value[index])
    })

    await post(`/coach/clientes/${props.cliente.id}/formularios/${formularioCompleto.value.id}/responder`, {
      respuestas: respuestasArray
    })

    emit('completado', {
      formulario: formularioCompleto.value,
      cliente: props.cliente
    })
    emit('close')
  } catch (err) {
    error.value = err.response?.data?.mensaje || 'Error al enviar formulario'
  } finally {
    enviando.value = false
  }
}

function cerrar() {
  emit('close')
}
</script>

<template>
  <Teleport to="body">
    <div class="formulario-cliente-modal__overlay" @click.self="cerrar">
      <div class="formulario-cliente-modal">
        <!-- Header -->
        <div class="formulario-cliente-modal__header">
          <h2 class="formulario-cliente-modal__title">
            {{ formularioCompleto.nombre || 'Formulario' }}
          </h2>
          <button
            type="button"
            class="formulario-cliente-modal__close"
            aria-label="Cerrar"
            @click="cerrar"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 6L6 18M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Body -->
        <div class="formulario-cliente-modal__body">
          <div v-if="cargando" class="formulario-cliente-modal__loading">
            <div class="formulario-cliente-modal__skeleton" />
            <div class="formulario-cliente-modal__skeleton" />
          </div>

          <div v-else-if="error" class="formulario-cliente-modal__error">
            {{ error }}
          </div>

          <form v-else @submit.prevent="enviarFormulario" class="formulario-cliente-modal__form">
            <div class="formulario-cliente-modal__info">
              <p class="formulario-cliente-modal__info-text">
                Llenando formulario para: <strong>{{ cliente.nombre }} {{ cliente.apellido_paterno }}</strong>
              </p>
            </div>

            <FormularioDinamico
              v-if="formularioCompleto.preguntas"
              :preguntas="formularioCompleto.preguntas"
              v-model="respuestas"
            />

            <div v-if="error" class="formulario-cliente-modal__error-message">
              {{ error }}
            </div>
          </form>
        </div>

        <!-- Footer -->
        <div v-if="!cargando && !error && formularioCompleto.preguntas" class="formulario-cliente-modal__footer">
          <BaseButton
            type="button"
            variant="secondary"
            @click="cerrar"
            :disabled="enviando"
          >
            Cancelar
          </BaseButton>
          <BaseButton
            type="submit"
            variant="primary"
            @click="enviarFormulario"
            :loading="enviando"
            :disabled="enviando"
          >
            {{ enviando ? 'Guardando...' : 'Guardar' }}
          </BaseButton>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.formulario-cliente-modal__overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.75);
  display: flex;
  align-items: flex-end;
  justify-content: center;
  z-index: 2000;
  animation: formulario-cliente-modal-fade 0.2s ease;
}

@keyframes formulario-cliente-modal-fade {
  from { opacity: 0; }
  to { opacity: 1; }
}

.formulario-cliente-modal {
  background: #161616;
  border-radius: 20px 20px 0 0;
  width: 100%;
  max-width: 600px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  animation: formulario-cliente-modal-slide 0.3s ease;
}

@keyframes formulario-cliente-modal-slide {
  from { transform: translateY(100%); }
  to { transform: translateY(0); }
}

@media (min-width: 640px) {
  .formulario-cliente-modal__overlay {
    align-items: center;
  }
  .formulario-cliente-modal {
    border-radius: 20px;
    max-height: 80vh;
  }
}

.formulario-cliente-modal__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.25rem 1.25rem 0;
  flex-shrink: 0;
}

.formulario-cliente-modal__title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #fff;
  margin: 0;
  flex: 1;
}

.formulario-cliente-modal__close {
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

.formulario-cliente-modal__close:hover {
  background: #333;
  color: #fff;
}

.formulario-cliente-modal__body {
  flex: 1;
  overflow-y: auto;
  padding: 1.25rem;
}

.formulario-cliente-modal__loading {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.formulario-cliente-modal__skeleton {
  height: 60px;
  background: #2a2a2a;
  border-radius: 8px;
  animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.formulario-cliente-modal__error {
  color: #EF5C5C;
  font-size: 0.875rem;
  padding: 1rem;
  text-align: center;
}

.formulario-cliente-modal__form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.formulario-cliente-modal__info {
  padding: 0.75rem 1rem;
  background: #2a2a2a;
  border-radius: 8px;
  border: 1px solid #333;
}

.formulario-cliente-modal__info-text {
  margin: 0;
  font-size: 0.875rem;
  color: #9CA3AF;
}

.formulario-cliente-modal__info-text strong {
  color: #fff;
  font-weight: 600;
}

.formulario-cliente-modal__error-message {
  padding: 0.75rem 1rem;
  background: rgba(239, 92, 92, 0.1);
  border: 1px solid rgba(239, 92, 92, 0.3);
  border-radius: 8px;
  color: #EF5C5C;
  font-size: 0.875rem;
}

.formulario-cliente-modal__footer {
  display: flex;
  gap: 0.75rem;
  padding: 1.25rem;
  border-top: 1px solid #252525;
  flex-shrink: 0;
}

.formulario-cliente-modal__footer :deep(.base-button) {
  flex: 1;
}
</style>

