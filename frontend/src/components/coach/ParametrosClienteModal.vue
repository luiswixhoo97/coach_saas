<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useApi } from '@/composables/useApi'

const props = defineProps({
  cliente: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'guardado'])

const { get, post, put, del, cargando } = useApi()

const parametrosDisponibles = ref([])
const error = ref('')
const cargandoParametros = ref(false)

// Formulario - un objeto con todos los parámetros
const valoresParametros = ref({}) // { parametro_id: { valor: '', notas: '' } }
const guardando = ref(false)
const mostrarFormulario = ref(true) // Mostrar formulario por defecto

onMounted(async () => {
  await cargarParametrosDisponibles()
  // Inicializar valores vacíos para todos los parámetros
  inicializarValores()
})

function inicializarValores() {
  const nuevosValores = {}
  parametrosDisponibles.value.forEach(parametro => {
    nuevosValores[parametro.id] = {
      valor: '',
      notas: ''
    }
  })
  valoresParametros.value = nuevosValores
}

watch(() => parametrosDisponibles.value, () => {
  if (parametrosDisponibles.value.length > 0) {
    inicializarValores()
  }
}, { deep: true })

async function cargarParametrosDisponibles() {
  try {
    cargandoParametros.value = true
    const response = await get('/coach/parametros')
    parametrosDisponibles.value = response.datos || []
  } catch (err) {
    error.value = err.response?.data?.mensaje || 'Error al cargar parámetros'
  } finally {
    cargandoParametros.value = false
  }
}


function abrirFormulario() {
  mostrarFormulario.value = true
  error.value = ''
  inicializarValores()
}

function cerrarFormulario() {
  mostrarFormulario.value = false
  inicializarValores()
  error.value = ''
}

async function guardarParametros() {
  // Filtrar solo los parámetros que tienen valor
  const parametrosConValor = Object.entries(valoresParametros.value)
    .filter(([parametroId, datos]) => datos.valor && datos.valor.trim())
    .map(([parametroId, datos]) => ({
      parametro_id: parseInt(parametroId),
      valor: datos.valor.trim(),
      notas: datos.notas.trim() || null
    }))

  if (parametrosConValor.length === 0) {
    error.value = 'Por favor ingresa al menos un valor'
    return
  }

  try {
    guardando.value = true
    error.value = ''

    // Guardar todos los parámetros con valor
    const fechaActual = new Date().toISOString().split('T')[0]
    const promesas = parametrosConValor.map(parametro => 
      post(`/coach/clientes/${props.cliente.id}/parametros`, {
        ...parametro,
        fecha: fechaActual
      })
    )

    await Promise.all(promesas)

    cerrarFormulario()
    emit('guardado')
  } catch (err) {
    error.value = err.response?.data?.mensaje || 'Error al guardar parámetros'
  } finally {
    guardando.value = false
  }
}


function nombreCompleto() {
  if (!props.cliente) return ''
  const partes = [props.cliente.nombre, props.cliente.apellido_paterno, props.cliente.apellido_materno].filter(Boolean)
  return partes.join(' ') || 'Cliente'
}
</script>

<template>
  <Teleport to="body">
    <div class="parametros-cliente-modal__overlay" @click.self="$emit('close')">
      <div class="parametros-cliente-modal">
        <!-- Header -->
        <div class="parametros-cliente-modal__header">
          <h2 class="parametros-cliente-modal__title">
            Parámetros - {{ nombreCompleto() }}
          </h2>
          <button
            type="button"
            class="parametros-cliente-modal__close"
            aria-label="Cerrar"
            @click="$emit('close')"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 6L6 18M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Body -->
        <div class="parametros-cliente-modal__body">
          <!-- Formulario para agregar parámetros -->
          <div v-if="mostrarFormulario" class="parametros-cliente-modal__form-section">
            <div class="parametros-cliente-modal__form-header">
              <h3 class="parametros-cliente-modal__form-title">
                Agregar Parámetros
              </h3>
              <button
                type="button"
                class="parametros-cliente-modal__form-close"
                @click="cerrarFormulario"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
              </button>
            </div>

            <form @submit.prevent="guardarParametros" class="parametros-cliente-modal__form">
              <div v-if="cargandoParametros" class="parametros-cliente-modal__loading">
                <div class="parametros-cliente-modal__skeleton" />
                <div class="parametros-cliente-modal__skeleton" />
              </div>

              <div v-else-if="parametrosDisponibles.length === 0" class="parametros-cliente-modal__empty">
                <p class="parametros-cliente-modal__empty-text">
                  No hay parámetros disponibles. Crea parámetros primero.
                </p>
              </div>

              <div v-else class="parametros-cliente-modal__parametros-form">
                <div
                  v-for="parametro in parametrosDisponibles"
                  :key="parametro.id"
                  class="parametros-cliente-modal__parametro-form-item"
                >
                  <div class="parametros-cliente-modal__parametro-form-header">
                    <h4 class="parametros-cliente-modal__parametro-form-nombre">
                      {{ parametro.nombre }}
                    </h4>
                    <span class="parametros-cliente-modal__parametro-form-unidad">
                      ({{ parametro.unidad_medida }})
                    </span>
                  </div>

                  <div class="parametros-cliente-modal__parametro-form-fields">
                    <div class="parametros-cliente-modal__form-group">
                      <label class="parametros-cliente-modal__form-label">
                        Valor
                      </label>
                      <input
                        v-model="valoresParametros[parametro.id].valor"
                        type="text"
                        :placeholder="`Ingresa el valor de ${parametro.nombre}`"
                        :disabled="guardando"
                        class="parametros-cliente-modal__form-input"
                      />
                    </div>

                    <div class="parametros-cliente-modal__form-group">
                      <label class="parametros-cliente-modal__form-label">
                        Notas (opcional)
                      </label>
                      <textarea
                        v-model="valoresParametros[parametro.id].notas"
                        :placeholder="`Notas para ${parametro.nombre}...`"
                        :disabled="guardando"
                        rows="2"
                        class="parametros-cliente-modal__form-textarea"
                      />
                    </div>
                  </div>
                </div>
              </div>

              <div v-if="error" class="parametros-cliente-modal__error">
                {{ error }}
              </div>

              <div class="parametros-cliente-modal__form-actions">
                <button
                  type="button"
                  class="parametros-cliente-modal__btn parametros-cliente-modal__btn--secondary"
                  @click="cerrarFormulario"
                  :disabled="guardando"
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  class="parametros-cliente-modal__btn parametros-cliente-modal__btn--primary"
                  :disabled="guardando"
                >
                  <span v-if="guardando" class="parametros-cliente-modal__btn-spinner"></span>
                  <span v-else>Guardar</span>
                </button>
              </div>
            </form>
          </div>

        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.parametros-cliente-modal__overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.75);
  display: flex;
  align-items: flex-end;
  justify-content: center;
  z-index: 2000;
  animation: parametros-cliente-modal-fade 0.2s ease;
}

@keyframes parametros-cliente-modal-fade {
  from { opacity: 0; }
  to { opacity: 1; }
}

.parametros-cliente-modal {
  background: #161616;
  border-radius: 20px 20px 0 0;
  width: 100%;
  max-width: 600px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  animation: parametros-cliente-modal-slide 0.3s ease;
}

@keyframes parametros-cliente-modal-slide {
  from { transform: translateY(100%); }
  to { transform: translateY(0); }
}

@media (min-width: 640px) {
  .parametros-cliente-modal__overlay {
    align-items: center;
  }
  .parametros-cliente-modal {
    border-radius: 20px;
    max-height: 80vh;
  }
}

.parametros-cliente-modal__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.25rem 1.25rem 0;
  flex-shrink: 0;
}

.parametros-cliente-modal__title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #fff;
  margin: 0;
  flex: 1;
}

.parametros-cliente-modal__close {
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

.parametros-cliente-modal__close:hover {
  background: #333;
  color: #fff;
}

.parametros-cliente-modal__body {
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 1.25rem;
  -webkit-overflow-scrolling: touch;
}

.parametros-cliente-modal__body::-webkit-scrollbar {
  width: 6px;
}

.parametros-cliente-modal__body::-webkit-scrollbar-track {
  background: #1a1a1a;
}

.parametros-cliente-modal__body::-webkit-scrollbar-thumb {
  background: #333;
  border-radius: 3px;
}

.parametros-cliente-modal__body::-webkit-scrollbar-thumb:hover {
  background: #444;
}

.parametros-cliente-modal__content {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.parametros-cliente-modal__form-section {
  background: #1a1a1a;
  border: 1px solid #252525;
  border-radius: 12px;
  padding: 1.5rem;
}

.parametros-cliente-modal__form-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
}

.parametros-cliente-modal__form-title {
  font-size: 1rem;
  font-weight: 600;
  color: #fff;
  margin: 0;
}

.parametros-cliente-modal__form-close {
  background: transparent;
  border: none;
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #9CA3AF;
  border-radius: 6px;
  transition: all 0.2s;
}

.parametros-cliente-modal__form-close:hover {
  background: #252525;
  color: #fff;
}

.parametros-cliente-modal__form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  min-height: min-content;
}

.parametros-cliente-modal__parametros-form {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.parametros-cliente-modal__parametro-form-item {
  padding: 1.25rem;
  background: #1a1a1a;
  border: 1px solid #00D261;
  border-radius: 12px;
}

.parametros-cliente-modal__parametro-form-header {
  display: flex;
  align-items: baseline;
  gap: 0.5rem;
  margin-bottom: 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #252525;
}

.parametros-cliente-modal__parametro-form-nombre {
  font-size: 1rem;
  font-weight: 600;
  color: #fff;
  margin: 0;
}

.parametros-cliente-modal__parametro-form-unidad {
  font-size: 0.875rem;
  color: #9CA3AF;
}

.parametros-cliente-modal__parametro-form-fields {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.parametros-cliente-modal__form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.parametros-cliente-modal__form-label {
  display: block;
  font-size: 0.8125rem;
  font-weight: 500;
  color: #a0a0a0;
  margin-bottom: 0.5rem;
}

.parametros-cliente-modal__form-select {
  width: 100%;
  padding: 0.75rem 1rem;
  background: #1a1a1a;
  border: 1px solid #252525;
  border-radius: 8px;
  color: #fff;
  font-size: 0.875rem;
  transition: all 0.2s;
}

.parametros-cliente-modal__form-select:focus {
  outline: none;
  border-color: #00D261;
  ring: 2px;
  ring-color: rgba(0, 210, 97, 0.2);
}

.parametros-cliente-modal__form-select:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.parametros-cliente-modal__form-select option {
  background: #1a1a1a;
  color: #fff;
}

.parametros-cliente-modal__form-input,
.parametros-cliente-modal__form-textarea {
  width: 100%;
  padding: 0.625rem 0.875rem;
  background: #1e1e1e;
  border: 1px solid #252525;
  border-radius: 12px;
  color: #fff;
  font-size: 0.875rem;
  font-family: inherit;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.parametros-cliente-modal__form-textarea {
  resize: vertical;
  min-height: 60px;
}

.parametros-cliente-modal__form-input::placeholder,
.parametros-cliente-modal__form-textarea::placeholder {
  color: #697586;
}

.parametros-cliente-modal__form-input:focus,
.parametros-cliente-modal__form-textarea:focus {
  outline: none;
  border-color: #00D261;
  box-shadow: 0 0 0 2px rgba(0, 210, 97, 0.35);
}

.parametros-cliente-modal__form-input:disabled,
.parametros-cliente-modal__form-textarea:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.parametros-cliente-modal__form-actions {
  display: flex;
  gap: 0.75rem;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #252525;
}

.parametros-cliente-modal__btn {
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

.parametros-cliente-modal__btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.parametros-cliente-modal__btn--secondary {
  background: #252525;
  color: #a0a0a0;
}

.parametros-cliente-modal__btn--secondary:hover:not(:disabled) {
  background: #2a2a2a;
  color: #fff;
}

.parametros-cliente-modal__btn--primary {
  background: #00D261;
  color: #0a0a0a;
}

.parametros-cliente-modal__btn--primary:hover:not(:disabled) {
  opacity: 0.9;
}

.parametros-cliente-modal__btn-spinner {
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

.parametros-cliente-modal__error {
  padding: 0.75rem 1rem;
  background: rgba(239, 92, 92, 0.1);
  border: 1px solid rgba(239, 92, 92, 0.3);
  border-radius: 8px;
  color: #EF5C5C;
  font-size: 0.875rem;
}

.parametros-cliente-modal__actions-header {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 1.5rem;
}

.parametros-cliente-modal__parametros-section {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.parametros-cliente-modal__section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.parametros-cliente-modal__section-title {
  font-size: 1rem;
  font-weight: 600;
  color: #fff;
  margin: 0;
}

.parametros-cliente-modal__parametros-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 0.75rem;
}

.parametros-cliente-modal__parametro-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 1rem;
  background: #1a1a1a;
  border: 1px solid #252525;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.parametros-cliente-modal__parametro-card:hover {
  background: #252525;
  border-color: #00D261;
}

.parametros-cliente-modal__parametro-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  flex: 1;
  min-width: 0;
}

.parametros-cliente-modal__parametro-nombre {
  font-size: 0.875rem;
  font-weight: 500;
  color: #fff;
}

.parametros-cliente-modal__parametro-unidad {
  font-size: 0.75rem;
  color: #9CA3AF;
}

.parametros-cliente-modal__parametro-icon {
  width: 20px;
  height: 20px;
  color: #00D261;
  flex-shrink: 0;
}

.parametros-cliente-modal__historial-section {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.parametros-cliente-modal__historial-grupos {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.parametros-cliente-modal__grupo {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.parametros-cliente-modal__grupo-fecha {
  font-size: 0.875rem;
  font-weight: 600;
  color: #9CA3AF;
  text-transform: capitalize;
  margin: 0;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid #252525;
}

.parametros-cliente-modal__grupo-items {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.parametros-cliente-modal__item {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.75rem;
  background: #1a1a1a;
  border: 1px solid #252525;
  border-radius: 8px;
}

.parametros-cliente-modal__item-content {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.parametros-cliente-modal__item-info {
  display: flex;
  align-items: baseline;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.parametros-cliente-modal__item-nombre {
  font-size: 0.875rem;
  font-weight: 500;
  color: #9CA3AF;
}

.parametros-cliente-modal__item-valor {
  font-size: 0.875rem;
  font-weight: 600;
  color: #fff;
}

.parametros-cliente-modal__item-notas {
  font-size: 0.75rem;
  color: #697586;
  font-style: italic;
}

.parametros-cliente-modal__item-actions {
  display: flex;
  gap: 0.5rem;
  flex-shrink: 0;
}

.parametros-cliente-modal__item-btn {
  background: transparent;
  border: 1px solid #252525;
  border-radius: 6px;
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #9CA3AF;
  transition: all 0.2s;
}

.parametros-cliente-modal__item-btn:hover {
  background: #252525;
  color: #fff;
}

.parametros-cliente-modal__item-btn--danger:hover {
  background: rgba(239, 92, 92, 0.1);
  border-color: rgba(239, 92, 92, 0.3);
  color: #EF5C5C;
}

.parametros-cliente-modal__empty {
  text-align: center;
  padding: 2rem 1rem;
}

.parametros-cliente-modal__empty-text {
  color: #9CA3AF;
  font-size: 0.875rem;
  margin: 0;
}

.parametros-cliente-modal__loading {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.parametros-cliente-modal__skeleton {
  height: 60px;
  background: #1a1a1a;
  border-radius: 8px;
  animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}
</style>

