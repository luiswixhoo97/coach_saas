<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useApi } from '@/composables/useApi'
import FormularioDinamico from '@/components/FormularioDinamico.vue'

const props = defineProps({
  cliente: {
    type: Object,
    required: true
  },
  modoVer: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['close', 'completado'])

const { get, post, cargando } = useApi()

const formularioCompleto = ref(null)
const respuestas = ref({})
const respuestaCliente = ref(null)
const enviando = ref(false)
const error = ref('')
const cargandoFormulario = ref(false)

onMounted(async () => {
  if (props.modoVer) {
    await cargarRespuestasCliente()
  } else {
    await cargarFormularioEstandar()
  }
})

async function cargarFormularioEstandar() {
  try {
    cargandoFormulario.value = true
    error.value = ''
    
    // Obtener el formulario estándar (el endpoint solo devuelve el estándar)
    const response = await get('/coach/formularios')
    const formularios = response.datos || []
    
    if (formularios.length === 0) {
      error.value = 'No tienes un formulario estándar configurado. Crea uno en la sección de Formularios.'
      return
    }
    
    // El endpoint solo devuelve el formulario estándar (el primero)
    const formularioEstandar = formularios[0]
    
    // Cargar el formulario completo con preguntas
    const formularioResponse = await get(`/coach/formularios/${formularioEstandar.id}`)
    formularioCompleto.value = formularioResponse.datos
    respuestas.value = {}
  } catch (err) {
    error.value = err.response?.data?.mensaje || 'Error al cargar formulario estándar'
  } finally {
    cargandoFormulario.value = false
  }
}

async function cargarRespuestasCliente() {
  try {
    cargandoFormulario.value = true
    error.value = ''
    
    const response = await get(`/coach/clientes/${props.cliente.id}/formulario-estandar/respuestas`)
    formularioCompleto.value = {
      id: response.datos.formulario.id,
      nombre: response.datos.formulario.nombre,
      preguntas: response.datos.formulario.preguntas
    }
    respuestaCliente.value = response.datos.respuesta
    
    // Convertir array de respuestas a objeto indexado para mostrar
    if (response.datos.respuesta.respuestas && Array.isArray(response.datos.respuesta.respuestas)) {
      const respuestasObj = {}
      response.datos.respuesta.respuestas.forEach((respuesta, index) => {
        respuestasObj[index] = respuesta
      })
      respuestas.value = respuestasObj
    } else {
      respuestas.value = {}
    }
  } catch (err) {
    if (err.response?.status === 404) {
      error.value = 'El cliente no ha contestado este formulario.'
    } else {
      error.value = err.response?.data?.mensaje || 'Error al cargar respuestas del formulario'
    }
  } finally {
    cargandoFormulario.value = false
  }
}

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
      // Permitir valores null (preguntas no contestadas)
      respuestasArray.push(respuestas.value[index] === '' ? null : respuestas.value[index])
    })

    await post(`/coach/clientes/${props.cliente.id}/formulario-estandar/responder`, {
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

function formatearFecha(fecha) {
  if (!fecha) return ''
  const fechaObj = new Date(fecha)
  return fechaObj.toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>

<template>
  <Teleport to="body">
    <div class="formulario-cliente-modal__overlay" @click.self="cerrar">
      <div class="formulario-cliente-modal">
        <!-- Header -->
        <div class="formulario-cliente-modal__header">
          <h2 class="formulario-cliente-modal__title">
            {{ formularioCompleto?.nombre || 'Formulario Estándar' }}
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
          <div v-if="cargandoFormulario" class="formulario-cliente-modal__loading">
            <div class="formulario-cliente-modal__skeleton" />
            <div class="formulario-cliente-modal__skeleton" />
          </div>

          <div v-else-if="error" class="formulario-cliente-modal__error">
            {{ error }}
          </div>

          <div v-else-if="formularioCompleto?.preguntas" class="formulario-cliente-modal__form">
            <div class="formulario-cliente-modal__info">
              <p class="formulario-cliente-modal__info-text">
                <span v-if="modoVer">Respuestas de: <strong>{{ cliente.nombre }} {{ cliente.apellido_paterno }}</strong></span>
                <span v-else>Llenando formulario para: <strong>{{ cliente.nombre }} {{ cliente.apellido_paterno }}</strong></span>
              </p>
              <p v-if="modoVer && respuestaCliente" class="formulario-cliente-modal__info-text" style="margin-top: 0.5rem; font-size: 0.8125rem;">
                Fecha: <strong>{{ formatearFecha(respuestaCliente.fecha) }}</strong>
              </p>
            </div>

            <div v-if="modoVer" class="formulario-cliente-modal__respuestas">
              <div
                v-for="(pregunta, index) in formularioCompleto.preguntas"
                :key="index"
                class="formulario-cliente-modal__respuesta-item"
              >
                <label class="formulario-cliente-modal__respuesta-label">
                  {{ pregunta.texto }}
                </label>
                <div class="formulario-cliente-modal__respuesta-valor">
                  <span v-if="respuestas[index] === null || respuestas[index] === undefined || respuestas[index] === ''" class="formulario-cliente-modal__respuesta-vacia">
                    Sin respuesta
                  </span>
                  <span v-else-if="Array.isArray(respuestas[index])" class="formulario-cliente-modal__respuesta-multiple">
                    {{ respuestas[index].join(', ') }}
                  </span>
                  <span v-else>
                    {{ respuestas[index] }}
                  </span>
                </div>
              </div>
            </div>

            <FormularioDinamico
              v-else
              :preguntas="formularioCompleto.preguntas"
              v-model="respuestas"
            />

            <div v-if="error" class="formulario-cliente-modal__error-message">
              {{ error }}
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div v-if="!cargandoFormulario && !error && formularioCompleto?.preguntas" class="formulario-cliente-modal__footer">
          <button
            v-if="modoVer"
            type="button"
            class="formulario-cliente-modal__btn formulario-cliente-modal__btn--primary"
            @click="cerrar"
          >
            Cerrar
          </button>
          <template v-else>
            <button
              type="button"
              class="formulario-cliente-modal__btn formulario-cliente-modal__btn--danger"
              @click="cerrar"
              :disabled="enviando"
            >
              Cancelar
            </button>
            <button
              type="submit"
              class="formulario-cliente-modal__btn formulario-cliente-modal__btn--primary"
              @click="enviarFormulario"
              :disabled="enviando"
            >
              <span v-if="enviando" class="formulario-cliente-modal__btn-spinner"></span>
              <span v-else>Guardar</span>
            </button>
          </template>
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

.formulario-cliente-modal__close svg {
  width: 18px;
  height: 18px;
}

.formulario-cliente-modal__body {
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 1.25rem;
  -webkit-overflow-scrolling: touch;
}

.formulario-cliente-modal__body::-webkit-scrollbar {
  width: 6px;
}

.formulario-cliente-modal__body::-webkit-scrollbar-track {
  background: #1a1a1a;
}

.formulario-cliente-modal__body::-webkit-scrollbar-thumb {
  background: #333;
  border-radius: 3px;
}

.formulario-cliente-modal__body::-webkit-scrollbar-thumb:hover {
  background: #444;
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
  font-size: 0.875rem;
  color: #EF5C5C;
  margin-bottom: 0.75rem;
  padding: 0.75rem;
  background: rgba(239, 92, 92, 0.12);
  border: 1px solid rgba(239, 92, 92, 0.3);
  border-radius: 12px;
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
  margin-bottom: 1.5rem;
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
  font-size: 0.875rem;
  color: #EF5C5C;
  padding: 0.75rem;
  background: rgba(239, 92, 92, 0.12);
  border: 1px solid rgba(239, 92, 92, 0.3);
  border-radius: 12px;
}

.formulario-cliente-modal__footer {
  display: flex;
  gap: 0.75rem;
  padding: 1.25rem;
  border-top: 1px solid #252525;
  flex-shrink: 0;
}

.formulario-cliente-modal__btn {
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

.formulario-cliente-modal__btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.formulario-cliente-modal__btn--secondary {
  background: #252525;
  color: #a0a0a0;
}

.formulario-cliente-modal__btn--secondary:hover:not(:disabled) {
  background: #2a2a2a;
  color: #fff;
}

.formulario-cliente-modal__btn--primary {
  background: #00D261;
  color: #0a0a0a;
}

.formulario-cliente-modal__btn--primary:hover:not(:disabled) {
  opacity: 0.9;
}

.formulario-cliente-modal__btn--danger {
  background: #EF5C5C;
  color: #fff;
}

.formulario-cliente-modal__btn--danger:hover:not(:disabled) {
  background: #dc4c4c;
  opacity: 0.9;
}

.formulario-cliente-modal__btn-spinner {
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

.formulario-cliente-modal__lista {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  padding: 0.75rem 0;
  margin-top: 0.5rem;
}

.formulario-cliente-modal__item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1rem;
  background: #1e1e1e;
  border: 1px solid #00D261;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s;
  text-align: left;
}

.formulario-cliente-modal__item:hover {
  background: #252525;
  border-color: #00D261;
  transform: translateX(4px);
}

.formulario-cliente-modal__item-content {
  flex: 1;
  min-width: 0;
}

.formulario-cliente-modal__item-nombre {
  font-size: 0.9375rem;
  font-weight: 600;
  color: #fff;
  margin: 0 0 0.25rem 0;
}

.formulario-cliente-modal__item-info {
  font-size: 0.8125rem;
  color: #9CA3AF;
  margin: 0;
}

.formulario-cliente-modal__item-icon {
  width: 20px;
  height: 20px;
  color: #00D261;
  flex-shrink: 0;
}

.formulario-cliente-modal__empty {
  text-align: center;
  padding: 2rem 1rem;
  color: #9CA3AF;
  font-size: 0.875rem;
}

/* Respuestas (modo ver) */
.formulario-cliente-modal__respuestas {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.formulario-cliente-modal__respuesta-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  padding: 1rem;
  background: #1e1e1e;
  border: 1px solid #252525;
  border-radius: 12px;
}

.formulario-cliente-modal__respuesta-label {
  font-size: 0.8125rem;
  font-weight: 500;
  color: #a0a0a0;
  margin: 0;
}

.formulario-cliente-modal__respuesta-valor {
  font-size: 0.875rem;
  color: #fff;
  padding: 0.75rem;
  background: #161616;
  border-radius: 8px;
  border: 1px solid #252525;
}

.formulario-cliente-modal__respuesta-vacia {
  color: #697586;
  font-style: italic;
}

.formulario-cliente-modal__respuesta-multiple {
  display: inline-block;
}
</style>

