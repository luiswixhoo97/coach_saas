<script setup>
import { ref, computed, onMounted } from 'vue'
import { useApi } from '@/composables/useApi'
import Swal from 'sweetalert2'
import InputPlacesAutocomplete from '@/components/ui/InputPlacesAutocomplete.vue'

const props = defineProps({
  cliente: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'creada'])

const { get, post, cargando } = useApi()

const suscripciones = ref([])
const cargandoSuscripciones = ref(false)
const error = ref('')
const guardando = ref(false)

// Formulario
const formData = ref({
  suscripcion_id: '',
  fecha: '',
  hora: '',
  modo: 'presencial',
  ubicacion_o_link: '',
  direccion: '',
  notas: ''
})

const modoOpciones = [
  { value: 'presencial', label: 'Presencial' },
  { value: 'online', label: 'Online' }
]

// Display value para el input de ubicación (muestra nombre/dirección, no la URL)
const ubicacionDisplay = ref('')

const esLink = computed(() => {
  if (!formData.value.ubicacion_o_link) return false
  const link = formData.value.ubicacion_o_link
  return link.startsWith('http://') || link.startsWith('https://') || link.startsWith('www.')
})

const placeholderUbicacion = computed(() => {
  if (formData.value.modo === 'presencial') {
    return 'Ej: Calle Principal 123, Ciudad'
  }
  return 'Ej: https://meet.google.com/abc-defg-hij'
})

onMounted(async () => {
  await cargarSuscripciones()
  // Si el cliente tiene una suscripción activa, seleccionarla por defecto
  if (props.cliente?.suscripcion_activa?.id) {
    formData.value.suscripcion_id = props.cliente.suscripcion_activa.id
  }
  // Establecer fecha mínima como hoy
  const hoy = new Date()
  hoy.setHours(0, 0, 0, 0)
  formData.value.fecha = hoy.toISOString().split('T')[0]
})

async function cargarSuscripciones() {
  cargandoSuscripciones.value = true
  error.value = ''
  try {
    const res = await get(`/coach/clientes/${props.cliente.id}`)
    const clienteData = res.datos ?? res.data ?? res
    
    // Obtener suscripciones del cliente
    if (clienteData.suscripciones && Array.isArray(clienteData.suscripciones)) {
      suscripciones.value = clienteData.suscripciones
    } else if (clienteData.suscripcion_activa) {
      // Si solo hay una suscripción activa, usarla
      suscripciones.value = [clienteData.suscripcion_activa]
    } else {
      // Intentar obtener suscripciones desde otro endpoint si existe
      try {
        const resSuscripciones = await get(`/coach/clientes/${props.cliente.id}/suscripciones`)
        suscripciones.value = resSuscripciones.datos ?? resSuscripciones.data ?? []
      } catch (e) {
        suscripciones.value = []
      }
    }
    
    if (suscripciones.value.length === 0) {
      error.value = 'El cliente no tiene suscripciones activas. Debe tener una suscripción para crear evaluaciones.'
    }
  } catch (err) {
    error.value = err.response?.data?.mensaje || 'Error al cargar suscripciones del cliente'
    suscripciones.value = []
  } finally {
    cargandoSuscripciones.value = false
  }
}

async function guardar() {
  if (guardando.value) return

  // Validaciones
  if (!formData.value.suscripcion_id) {
    error.value = 'Debes seleccionar una suscripción.'
    return
  }

  if (!formData.value.fecha) {
    error.value = 'La fecha es requerida.'
    return
  }

  if (!formData.value.hora) {
    error.value = 'La hora es requerida.'
    return
  }

  // Validar formato de hora (HH:MM)
  const horaRegex = /^([0-1][0-9]|2[0-3]):[0-5][0-9]$/
  if (!horaRegex.test(formData.value.hora)) {
    error.value = 'La hora debe tener el formato HH:MM (ej: 14:30).'
    return
  }

  guardando.value = true
  error.value = ''

  try {
    const payload = {
      suscripcion_id: Number(formData.value.suscripcion_id),
      fecha: formData.value.fecha,
      hora: formData.value.hora,
      modo: formData.value.modo,
      estado: 'agendada',
      ubicacion_o_link: formData.value.ubicacion_o_link.trim() || null,
      direccion: formData.value.direccion.trim() || null,
      notas: formData.value.notas.trim() || null
    }

    const response = await post('/coach/evaluaciones', payload)

    await Swal.fire({
      title: 'Evaluación creada',
      text: 'La evaluación se ha agendado correctamente.',
      icon: 'success',
      confirmButtonColor: '#00D261',
      timer: 2000,
      showConfirmButton: false
    })

    emit('creada', response.datos)
    emit('close')
  } catch (err) {
    const mensajeError = err.response?.data?.mensaje || err.message || 'Error al crear la evaluación'
    error.value = mensajeError
    
    // Mostrar errores de validación si existen
    if (err.response?.data?.errores) {
      const errores = err.response.data.errores
      const mensajes = Object.values(errores).flat().join(', ')
      error.value = mensajes
    }
  } finally {
    guardando.value = false
  }
}

function cerrar() {
  emit('close')
}
</script>

<template>
  <Teleport to="body">
    <div class="crear-evaluacion-modal__overlay" @click.self="cerrar">
      <div class="crear-evaluacion-modal">
        <!-- Header -->
        <div class="crear-evaluacion-modal__header">
          <h2 class="crear-evaluacion-modal__title">Agendar Evaluación</h2>
          <button
            type="button"
            class="crear-evaluacion-modal__close"
            aria-label="Cerrar"
            @click="cerrar"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 6L6 18M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Body -->
        <div class="crear-evaluacion-modal__body">
          <div v-if="cargandoSuscripciones" class="crear-evaluacion-modal__loading">
            <p>Cargando suscripciones...</p>
          </div>

          <div v-else-if="error && suscripciones.length === 0" class="crear-evaluacion-modal__error">
            {{ error }}
          </div>

          <form v-else @submit.prevent="guardar" class="crear-evaluacion-modal__form">
            <!-- Suscripción -->
            <div class="crear-evaluacion-modal__field">
              <label class="crear-evaluacion-modal__label">
                Suscripción <span class="crear-evaluacion-modal__required">*</span>
              </label>
              <select
                v-model="formData.suscripcion_id"
                required
                class="crear-evaluacion-modal__select"
                :disabled="suscripciones.length === 1"
              >
                <option value="">Selecciona una suscripción</option>
                <option
                  v-for="suscripcion in suscripciones"
                  :key="suscripcion.id"
                  :value="suscripcion.id"
                >
                  {{ suscripcion.plan?.nombre || 'Plan' }} - 
                  Vence: {{ suscripcion.fecha_fin || 'N/A' }}
                </option>
              </select>
            </div>

            <!-- Fecha -->
            <div class="crear-evaluacion-modal__field">
              <label class="crear-evaluacion-modal__label">
                Fecha <span class="crear-evaluacion-modal__required">*</span>
              </label>
              <input
                v-model="formData.fecha"
                type="date"
                required
                class="crear-evaluacion-modal__input"
                :min="new Date().toISOString().split('T')[0]"
              />
            </div>

            <!-- Hora -->
            <div class="crear-evaluacion-modal__field">
              <label class="crear-evaluacion-modal__label">
                Hora <span class="crear-evaluacion-modal__required">*</span>
              </label>
              <input
                v-model="formData.hora"
                type="time"
                required
                class="crear-evaluacion-modal__input"
              />
            </div>

            <!-- Modo -->
            <div class="crear-evaluacion-modal__field">
              <label class="crear-evaluacion-modal__label">
                Modo <span class="crear-evaluacion-modal__required">*</span>
              </label>
              <select
                v-model="formData.modo"
                required
                class="crear-evaluacion-modal__select"
              >
                <option
                  v-for="opcion in modoOpciones"
                  :key="opcion.value"
                  :value="opcion.value"
                >
                  {{ opcion.label }}
                </option>
              </select>
            </div>

            <!-- Ubicación o Link -->
            <div class="crear-evaluacion-modal__field">
              <label class="crear-evaluacion-modal__label">
                {{ formData.modo === 'presencial' ? 'Ubicación' : 'Link de videollamada' }}
                <span class="crear-evaluacion-modal__optional">(opcional)</span>
              </label>
              
              <!-- Usar autocompletado solo para modo presencial -->
              <InputPlacesAutocomplete
                v-if="formData.modo === 'presencial'"
                v-model="ubicacionDisplay"
                placeholder="Buscar gimnasio, dirección o lugar..."
                class="crear-evaluacion-modal__input-places"
                @select="(lugar) => { 
                  // Guardar tanto la dirección como el link
                  if (lugar.link_google_maps) {
                    formData.ubicacion_o_link = lugar.link_google_maps
                    formData.direccion = lugar.direccion_completa || lugar.direccion || lugar.nombre || ''
                    // Mostrar nombre o dirección en el input
                    ubicacionDisplay = lugar.nombre || lugar.direccion || lugar.direccion_completa || ''
                  }
                }"
              />
              
              <!-- Input normal para modo online -->
              <input
                v-else
                v-model="formData.ubicacion_o_link"
                type="text"
                class="crear-evaluacion-modal__input"
                :placeholder="placeholderUbicacion"
                maxlength="500"
              />
              
              <p v-if="formData.modo === 'online' && !esLink && formData.ubicacion_o_link" class="crear-evaluacion-modal__hint">
                Tip: Puedes pegar el link completo de Google Meet, Zoom, etc.
              </p>
            </div>

            <!-- Notas -->
            <div class="crear-evaluacion-modal__field">
              <label class="crear-evaluacion-modal__label">
                Notas
                <span class="crear-evaluacion-modal__optional">(opcional)</span>
              </label>
              <textarea
                v-model="formData.notas"
                class="crear-evaluacion-modal__textarea"
                rows="3"
                placeholder="Notas adicionales sobre la evaluación..."
              ></textarea>
            </div>

            <div v-if="error" class="crear-evaluacion-modal__error">
              {{ error }}
            </div>
          </form>
        </div>

        <!-- Footer -->
        <div v-if="!cargandoSuscripciones && suscripciones.length > 0" class="crear-evaluacion-modal__footer">
          <button
            type="button"
            class="crear-evaluacion-modal__btn crear-evaluacion-modal__btn--secondary"
            @click="cerrar"
            :disabled="guardando"
          >
            Cancelar
          </button>
          <button
            type="button"
            class="crear-evaluacion-modal__btn crear-evaluacion-modal__btn--primary"
            @click="guardar"
            :disabled="guardando || cargando"
          >
            <span v-if="guardando">Guardando...</span>
            <span v-else>Agendar Evaluación</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.crear-evaluacion-modal__overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.75);
  display: flex;
  align-items: flex-end;
  justify-content: center;
  z-index: 2000;
  animation: crear-evaluacion-modal-fade 0.2s ease;
}

@keyframes crear-evaluacion-modal-fade {
  from { opacity: 0; }
  to { opacity: 1; }
}

.crear-evaluacion-modal {
  background: #161616;
  border-radius: 20px 20px 0 0;
  width: 100%;
  max-width: 600px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  animation: crear-evaluacion-modal-slide 0.3s ease;
}

@keyframes crear-evaluacion-modal-slide {
  from { transform: translateY(100%); }
  to { transform: translateY(0); }
}

@media (min-width: 640px) {
  .crear-evaluacion-modal__overlay {
    align-items: center;
    padding: 1rem;
  }
  .crear-evaluacion-modal {
    border-radius: 20px;
    max-height: 80vh;
  }
}

.crear-evaluacion-modal__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.25rem 1.25rem 0;
  flex-shrink: 0;
}

.crear-evaluacion-modal__title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #fff;
  margin: 0;
  flex: 1;
}

.crear-evaluacion-modal__close {
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

.crear-evaluacion-modal__close:hover {
  background: #333;
  color: #fff;
}

.crear-evaluacion-modal__close svg {
  width: 18px;
  height: 18px;
}

.crear-evaluacion-modal__body {
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 1.25rem;
  -webkit-overflow-scrolling: touch;
}

.crear-evaluacion-modal__body::-webkit-scrollbar {
  width: 6px;
}

.crear-evaluacion-modal__body::-webkit-scrollbar-track {
  background: #1a1a1a;
}

.crear-evaluacion-modal__body::-webkit-scrollbar-thumb {
  background: #333;
  border-radius: 3px;
}

.crear-evaluacion-modal__body::-webkit-scrollbar-thumb:hover {
  background: #444;
}

.crear-evaluacion-modal__loading {
  padding: 1rem;
  text-align: center;
  color: #9CA3AF;
  font-size: 0.875rem;
}

.crear-evaluacion-modal__error {
  font-size: 0.875rem;
  color: #EF5C5C;
  margin-bottom: 0.75rem;
  padding: 0.75rem;
  background: rgba(239, 92, 92, 0.12);
  border: 1px solid rgba(239, 92, 92, 0.3);
  border-radius: 12px;
  text-align: center;
}

.crear-evaluacion-modal__form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.crear-evaluacion-modal__field {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.crear-evaluacion-modal__label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #fff;
}

.crear-evaluacion-modal__required {
  color: #EF5C5C;
}

.crear-evaluacion-modal__optional {
  color: #9CA3AF;
  font-weight: normal;
  font-size: 0.8125rem;
}

.crear-evaluacion-modal__input,
.crear-evaluacion-modal__select,
.crear-evaluacion-modal__textarea {
  width: 100%;
  padding: 0.75rem;
  border-radius: 12px;
  border: 1px solid #252525;
  background: #0a0a0a;
  color: #fff;
  font-size: 0.875rem;
  transition: all 0.2s ease;
}

.crear-evaluacion-modal__input:focus,
.crear-evaluacion-modal__select:focus,
.crear-evaluacion-modal__textarea:focus {
  outline: none;
  border-color: #00D261;
  box-shadow: 0 0 0 2px rgba(0, 210, 97, 0.35);
}

.crear-evaluacion-modal__input:disabled,
.crear-evaluacion-modal__select:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.crear-evaluacion-modal__textarea {
  resize: vertical;
  min-height: 80px;
}

.crear-evaluacion-modal__hint {
  font-size: 0.75rem;
  color: #9CA3AF;
  margin-top: 0.25rem;
}

.crear-evaluacion-modal__input-places {
  width: 100%;
}

.crear-evaluacion-modal__footer {
  display: flex;
  gap: 0.75rem;
  padding: 1.25rem;
  border-top: 1px solid #252525;
  flex-shrink: 0;
}

.crear-evaluacion-modal__btn {
  flex: 1;
  padding: 0.625rem 1.25rem;
  border-radius: 12px;
  border: none;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.crear-evaluacion-modal__btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.crear-evaluacion-modal__btn--primary {
  background: #00D261;
  color: #fff;
}

.crear-evaluacion-modal__btn--primary:hover:not(:disabled) {
  background: #00B855;
}

.crear-evaluacion-modal__btn--secondary {
  background: transparent;
  color: #fff;
  border: 1px solid #252525;
}

.crear-evaluacion-modal__btn--secondary:hover:not(:disabled) {
  background: #1a1a1a;
  border-color: #333;
}
</style>

