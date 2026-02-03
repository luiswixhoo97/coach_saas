<script setup>
import { ref } from 'vue'
import { useApi } from '@/composables/useApi'
import Swal from 'sweetalert2'

const props = defineProps({
  cliente: {
    type: Object,
    default: null
  },
  clientes: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['close', 'subida'])

const { post, subirArchivo, cargando } = useApi()

const archivos = ref([])
const error = ref('')
const guardando = ref(false)

function onArchivosSeleccionados(event) {
  const files = Array.from(event.target.files)
  archivos.value = files
  error.value = ''
}

function quitarArchivo(index) {
  archivos.value.splice(index, 1)
}

async function guardar() {
  if (archivos.value.length === 0) {
    error.value = 'Debes seleccionar al menos un archivo PDF.'
    return
  }

  if (!props.cliente && props.clientes.length === 0) {
    error.value = 'Debes seleccionar al menos un cliente.'
    return
  }

  guardando.value = true
  error.value = ''

  try {
    const clienteIds = props.cliente ? [props.cliente.id] : props.clientes.map(c => c.id)

    // Crear FormData con archivos y cliente_ids
    const formData = new FormData()
    archivos.value.forEach((archivo) => {
      formData.append('archivos[]', archivo)
    })
    clienteIds.forEach((id) => {
      formData.append('cliente_ids[]', id.toString())
    })

    await subirArchivo('/coach/dietas/subir-varios', formData)

    await Swal.fire({
      title: 'Dieta subida',
      text: 'Los archivos se han subido correctamente.',
      icon: 'success',
      confirmButtonColor: '#00D261'
    })

    emit('subida')
    emit('close')
  } catch (e) {
    // Mostrar errores de validación si existen
    if (e.errores) {
      const mensajes = Object.values(e.errores).flat().join(', ')
      error.value = mensajes || e.message || 'No se pudieron subir los archivos.'
    } else {
      error.value = e.message || 'No se pudieron subir los archivos.'
    }
    console.error('Error al subir dietas:', e)
  } finally {
    guardando.value = false
  }
}

function nombreArchivo(archivo) {
  return archivo.name || 'Archivo PDF'
}

function tamañoArchivo(bytes) {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}
</script>

<template>
  <Teleport to="body">
    <div class="subir-dieta-modal__overlay" @click.self="$emit('close')">
      <div class="subir-dieta-modal">
        <div class="subir-dieta-modal__header">
          <h2 class="subir-dieta-modal__title">Subir archivos de dieta</h2>
          <button
            type="button"
            class="subir-dieta-modal__close"
            aria-label="Cerrar"
            @click="$emit('close')"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 6L6 18M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <div class="subir-dieta-modal__body">
          <div v-if="error" class="subir-dieta-modal__error">{{ error }}</div>

          <!-- Información de clientes -->
          <div v-if="cliente || clientes.length > 0" class="subir-dieta-modal__info">
            <p class="subir-dieta-modal__info-text">
              <span v-if="cliente">
                Subiendo dieta para: <strong>{{ cliente.nombre }} {{ cliente.apellido_paterno }}</strong>
              </span>
              <span v-else>
                Subiendo dieta para <strong>{{ clientes.length }}</strong> cliente(s) seleccionado(s)
              </span>
            </p>
          </div>

          <!-- Selector de archivos -->
          <div class="subir-dieta-modal__file-selector">
            <label class="subir-dieta-modal__file-label">
              <input
                type="file"
                multiple
                accept=".pdf"
                class="subir-dieta-modal__file-input"
                @change="onArchivosSeleccionados"
              />
              <span class="subir-dieta-modal__file-button">Seleccionar archivos PDF</span>
            </label>
            <p class="subir-dieta-modal__file-hint">
              Puedes seleccionar uno o varios archivos PDF (máx. 10MB cada uno)
            </p>
          </div>

          <!-- Lista de archivos seleccionados -->
          <div v-if="archivos.length > 0" class="subir-dieta-modal__files-list">
            <h3 class="subir-dieta-modal__files-title">Archivos seleccionados ({{ archivos.length }})</h3>
            <div class="subir-dieta-modal__files">
              <div
                v-for="(archivo, index) in archivos"
                :key="index"
                class="subir-dieta-modal__file-item"
              >
                <div class="subir-dieta-modal__file-info">
                  <span class="subir-dieta-modal__file-name">{{ nombreArchivo(archivo) }}</span>
                  <span class="subir-dieta-modal__file-size">{{ tamañoArchivo(archivo.size) }}</span>
                </div>
                <button
                  type="button"
                  class="subir-dieta-modal__file-remove"
                  @click="quitarArchivo(index)"
                >
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18M6 6l12 12"/>
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="subir-dieta-modal__footer">
          <button
            type="button"
            class="subir-dieta-modal__btn subir-dieta-modal__btn--cancel"
            @click="$emit('close')"
          >
            Cancelar
          </button>
          <button
            type="button"
            class="subir-dieta-modal__btn subir-dieta-modal__btn--save"
            :disabled="guardando || archivos.length === 0"
            @click="guardar"
          >
            <span v-if="guardando">Subiendo...</span>
            <span v-else>Subir archivos</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.subir-dieta-modal__overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.75);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.subir-dieta-modal {
  background: #161616;
  border-radius: 16px;
  width: 100%;
  max-width: 600px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.subir-dieta-modal__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.25rem;
  border-bottom: 1px solid #252525;
}

.subir-dieta-modal__title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #fff;
  margin: 0;
}

.subir-dieta-modal__close {
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
  transition: background 0.2s, color 0.2s;
}

.subir-dieta-modal__close:hover {
  background: #333;
  color: #fff;
}

.subir-dieta-modal__close svg {
  width: 18px;
  height: 18px;
}

.subir-dieta-modal__body {
  padding: 1.25rem;
  overflow-y: auto;
  flex: 1;
}

.subir-dieta-modal__error {
  background: rgba(239, 92, 92, 0.12);
  border: 1px solid rgba(239, 92, 92, 0.3);
  color: #EF5C5C;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  font-size: 0.875rem;
  margin-bottom: 1rem;
}

.subir-dieta-modal__info {
  padding: 0.75rem 1rem;
  background: rgba(0, 210, 97, 0.1);
  border: 1px solid rgba(0, 210, 97, 0.3);
  border-radius: 8px;
  margin-bottom: 1rem;
}

.subir-dieta-modal__info-text {
  font-size: 0.875rem;
  color: #00D261;
  margin: 0;
}

.subir-dieta-modal__file-selector {
  margin-bottom: 1.5rem;
}

.subir-dieta-modal__file-label {
  display: block;
  cursor: pointer;
}

.subir-dieta-modal__file-input {
  position: absolute;
  width: 1px;
  height: 1px;
  opacity: 0;
  overflow: hidden;
}

.subir-dieta-modal__file-button {
  display: inline-block;
  padding: 0.75rem 1.25rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #00D261;
  background: transparent;
  border: 2px dashed rgba(0, 210, 97, 0.4);
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.subir-dieta-modal__file-button:hover {
  background: rgba(0, 210, 97, 0.1);
  border-color: #00D261;
}

.subir-dieta-modal__file-hint {
  font-size: 0.75rem;
  color: #697586;
  margin: 0.5rem 0 0 0;
}

.subir-dieta-modal__files-list {
  margin-top: 1.5rem;
}

.subir-dieta-modal__files-title {
  font-size: 0.9375rem;
  font-weight: 600;
  color: #fff;
  margin: 0 0 0.75rem 0;
}

.subir-dieta-modal__files {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.subir-dieta-modal__file-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  background: #1e1e1e;
  border-radius: 8px;
  border: 1px solid #252525;
}

.subir-dieta-modal__file-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  flex: 1;
  min-width: 0;
}

.subir-dieta-modal__file-name {
  font-size: 0.875rem;
  font-weight: 500;
  color: #fff;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.subir-dieta-modal__file-size {
  font-size: 0.75rem;
  color: #697586;
}

.subir-dieta-modal__file-remove {
  background: transparent;
  border: none;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #EF5C5C;
  transition: background 0.2s;
  border-radius: 4px;
  flex-shrink: 0;
}

.subir-dieta-modal__file-remove:hover {
  background: rgba(239, 92, 92, 0.1);
}

.subir-dieta-modal__file-remove svg {
  width: 16px;
  height: 16px;
}

.subir-dieta-modal__footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 1rem 1.25rem;
  border-top: 1px solid #252525;
}

.subir-dieta-modal__btn {
  padding: 0.625rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.subir-dieta-modal__btn--cancel {
  color: #697586;
  background: transparent;
  border: 1px solid #252525;
}

.subir-dieta-modal__btn--cancel:hover {
  background: #1e1e1e;
}

.subir-dieta-modal__btn--save {
  color: #fff;
  background: #00D261;
}

.subir-dieta-modal__btn--save:hover:not(:disabled) {
  background: #00b854;
}

.subir-dieta-modal__btn--save:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>

