<script setup>
import { ref, computed, watch } from 'vue'
import { useApi } from '@/composables/useApi'
import Swal from 'sweetalert2'
import InputPlacesAutocomplete from '@/components/ui/InputPlacesAutocomplete.vue'

const props = defineProps({
  evaluacion: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'actualizado'])

const { put } = useApi()

const error = ref('')
const guardando = ref(false)

const formData = ref({
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

const ubicacionDisplay = ref('')
const selectedLugar = ref(null)

const placeholderUbicacion = computed(() => {
  if (formData.value.modo === 'presencial') return 'Ej: Calle Principal 123, Ciudad'
  return 'Ej: https://meet.google.com/abc-defg-hij'
})

watch(
  () => props.evaluacion,
  (ev) => {
    if (!ev) return
    formData.value.fecha = ev.fecha || ''
    formData.value.hora = ev.hora || ''
    formData.value.modo = ev.modo || 'presencial'
    formData.value.ubicacion_o_link = ev.ubicacion_o_link || ''
    formData.value.direccion = ev.direccion || ''
    formData.value.notas = ev.notas || ''
    if (ev.ubicacion) {
      selectedLugar.value = {
        id: ev.ubicacion.id,
        link_google_maps: ev.ubicacion.link_google_maps,
        nombre: ev.ubicacion.nombre,
        direccion: ev.ubicacion.direccion,
        direccion_completa: ev.ubicacion.direccion
      }
      ubicacionDisplay.value = ev.ubicacion.nombre || ev.ubicacion.direccion || ''
    } else {
      selectedLugar.value = null
      ubicacionDisplay.value = ev.direccion || ''
    }
    error.value = ''
  },
  { immediate: true, deep: true }
)

watch(() => formData.value.modo, (modo) => {
  if (modo === 'online') {
    selectedLugar.value = null
    formData.value.ubicacion_o_link = ''
    formData.value.direccion = ''
    ubicacionDisplay.value = ''
  }
})

async function guardar() {
  if (guardando.value || !props.evaluacion?.id) return
  if (!formData.value.fecha) {
    error.value = 'La fecha es requerida.'
    return
  }
  if (!formData.value.hora) {
    error.value = 'La hora es requerida.'
    return
  }
  const horaRegex = /^([0-1][0-9]|2[0-3]):[0-5][0-9]$/
  if (!horaRegex.test(formData.value.hora)) {
    error.value = 'La hora debe tener el formato HH:MM (ej: 14:30).'
    return
  }

  guardando.value = true
  error.value = ''
  try {
    const payload = {
      fecha: formData.value.fecha,
      hora: formData.value.hora,
      modo: formData.value.modo,
      estado: 'agendada',
      ubicacion_o_link: formData.value.ubicacion_o_link?.trim() || null,
      direccion: formData.value.direccion?.trim() || null,
      notas: formData.value.notas?.trim() || null
    }
    if (formData.value.modo === 'presencial' && selectedLugar.value?.link_google_maps) {
      const lugar = selectedLugar.value
      const idNum = Number(lugar.id)
      if (Number.isInteger(idNum) && idNum > 0) {
        payload.ubicacion_id = idNum
      } else {
        payload.ubicacion = {
          link_google_maps: lugar.link_google_maps,
          nombre: lugar.nombre || '',
          direccion: lugar.direccion_completa || lugar.direccion || ''
        }
      }
    }
    await put(`/coach/evaluaciones/${props.evaluacion.id}`, payload)
    await Swal.fire({
      icon: 'success',
      title: 'Evaluación reagendada',
      text: 'La nueva fecha y datos se han guardado correctamente.',
      confirmButtonColor: '#00D261'
    })
    emit('actualizado')
    emit('close')
  } catch (err) {
    error.value = err.response?.data?.mensaje || err.message || 'Error al actualizar la evaluación.'
    if (err.response?.data?.errores) {
      const errores = err.response.data.errores
      error.value = Object.values(errores).flat().join(', ')
    }
  } finally {
    guardando.value = false
  }
}

function onSelectLugar(lugar) {
  if (!lugar?.link_google_maps) return
  formData.value.ubicacion_o_link = lugar.link_google_maps
  formData.value.direccion = lugar.direccion_completa || lugar.direccion || lugar.nombre || ''
  ubicacionDisplay.value = lugar.nombre || lugar.direccion || lugar.direccion_completa || ''
  selectedLugar.value = lugar
}

function cerrar() {
  emit('close')
}
</script>

<template>
  <Teleport to="body">
    <div v-if="evaluacion" class="crear-evaluacion-modal__overlay" @click.self="cerrar">
      <div class="crear-evaluacion-modal">
        <div class="crear-evaluacion-modal__header">
          <h2 class="crear-evaluacion-modal__title">Reagendar evaluación</h2>
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

        <div class="crear-evaluacion-modal__body">
          <form @submit.prevent="guardar" class="crear-evaluacion-modal__form">
            <div class="crear-evaluacion-modal__field">
              <label class="crear-evaluacion-modal__label">Fecha <span class="crear-evaluacion-modal__required">*</span></label>
              <input
                v-model="formData.fecha"
                type="date"
                required
                class="crear-evaluacion-modal__input"
                :min="new Date().toISOString().split('T')[0]"
              />
            </div>
            <div class="crear-evaluacion-modal__field">
              <label class="crear-evaluacion-modal__label">Hora <span class="crear-evaluacion-modal__required">*</span></label>
              <input
                v-model="formData.hora"
                type="time"
                required
                class="crear-evaluacion-modal__input"
              />
            </div>
            <div class="crear-evaluacion-modal__field">
              <label class="crear-evaluacion-modal__label">Modo <span class="crear-evaluacion-modal__required">*</span></label>
              <select v-model="formData.modo" required class="crear-evaluacion-modal__select">
                <option v-for="op in modoOpciones" :key="op.value" :value="op.value">{{ op.label }}</option>
              </select>
            </div>
            <div class="crear-evaluacion-modal__field">
              <label class="crear-evaluacion-modal__label">
                {{ formData.modo === 'presencial' ? 'Ubicación' : 'Link de videollamada' }}
                <span class="crear-evaluacion-modal__optional">(opcional)</span>
              </label>
              <InputPlacesAutocomplete
                v-if="formData.modo === 'presencial'"
                v-model="ubicacionDisplay"
                placeholder="Buscar gimnasio, dirección o lugar..."
                class="crear-evaluacion-modal__input-places"
                @select="onSelectLugar"
              />
              <input
                v-else
                v-model="formData.ubicacion_o_link"
                type="text"
                class="crear-evaluacion-modal__input"
                :placeholder="placeholderUbicacion"
                maxlength="500"
              />
            </div>
            <div class="crear-evaluacion-modal__field">
              <label class="crear-evaluacion-modal__label">
                Notas <span class="crear-evaluacion-modal__optional">(opcional)</span>
              </label>
              <textarea
                v-model="formData.notas"
                class="crear-evaluacion-modal__textarea"
                rows="3"
                placeholder="Notas adicionales..."
              />
            </div>
            <div v-if="error" class="crear-evaluacion-modal__error">{{ error }}</div>
          </form>
        </div>

        <div class="crear-evaluacion-modal__footer">
          <button
            type="button"
            class="crear-evaluacion-modal__btn crear-evaluacion-modal__btn--secondary"
            :disabled="guardando"
            @click="cerrar"
          >
            Cancelar
          </button>
          <button
            type="button"
            class="crear-evaluacion-modal__btn crear-evaluacion-modal__btn--primary"
            :disabled="guardando"
            @click="guardar"
          >
            <span v-if="guardando">Guardando...</span>
            <span v-else>Reagendar</span>
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
  .crear-evaluacion-modal__overlay { align-items: center; padding: 1rem; }
  .crear-evaluacion-modal { border-radius: 20px; max-height: 80vh; }
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
.crear-evaluacion-modal__close svg { width: 18px; height: 18px; }
.crear-evaluacion-modal__body {
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 1.25rem;
  -webkit-overflow-scrolling: touch;
}
.crear-evaluacion-modal__form { display: flex; flex-direction: column; gap: 1.5rem; }
.crear-evaluacion-modal__field { display: flex; flex-direction: column; gap: 0.5rem; }
.crear-evaluacion-modal__label { font-size: 0.875rem; font-weight: 500; color: #fff; }
.crear-evaluacion-modal__required { color: #EF5C5C; }
.crear-evaluacion-modal__optional { color: #9CA3AF; font-weight: normal; font-size: 0.8125rem; }
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
.crear-evaluacion-modal__textarea { resize: vertical; min-height: 80px; }
.crear-evaluacion-modal__input-places { width: 100%; }
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
.crear-evaluacion-modal__btn:disabled { opacity: 0.5; cursor: not-allowed; }
.crear-evaluacion-modal__btn--primary { background: #00D261; color: #fff; }
.crear-evaluacion-modal__btn--primary:hover:not(:disabled) { background: #00B855; }
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
