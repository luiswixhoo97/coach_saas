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
const isDragging = ref(false)

function onArchivosSeleccionados(event) {
  const files = Array.from(event.target.files)
  procesarArchivos(files)
}

function procesarArchivos(files) {
  const pdfs = files.filter(file => file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf'))
  if (pdfs.length !== files.length) {
    error.value = 'Solo se permiten archivos PDF. Se omitieron ' + (files.length - pdfs.length) + ' archivo(s).'
  }
  archivos.value = [...archivos.value, ...pdfs]
  error.value = ''
}

function onDragOver(event) {
  event.preventDefault()
  isDragging.value = true
}

function onDragLeave() {
  isDragging.value = false
}

function onDrop(event) {
  event.preventDefault()
  isDragging.value = false
  const files = Array.from(event.dataTransfer.files)
  procesarArchivos(files)
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

          <!-- Selector de archivos con drag & drop -->
          <div 
            class="subir-dieta-modal__file-selector"
            :class="{ 'subir-dieta-modal__file-selector--dragging': isDragging }"
            @dragover.prevent="onDragOver"
            @dragleave="onDragLeave"
            @drop="onDrop"
          >
            <label class="subir-dieta-modal__file-label">
              <input
                type="file"
                multiple
                accept=".pdf,application/pdf"
                class="subir-dieta-modal__file-input"
                @change="onArchivosSeleccionados"
              />
              <div class="subir-dieta-modal__file-dropzone">
                <div class="subir-dieta-modal__file-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="17 8 12 3 7 8"/>
                    <line x1="12" y1="3" x2="12" y2="15"/>
                  </svg>
                </div>
                <p class="subir-dieta-modal__file-dropzone-text">
                  <span class="subir-dieta-modal__file-dropzone-text-main">Arrastra archivos PDF aquí</span>
                  <span class="subir-dieta-modal__file-dropzone-text-sub">o haz clic para seleccionar</span>
                </p>
                <p class="subir-dieta-modal__file-hint">
                  Puedes seleccionar uno o varios archivos PDF (máx. 10MB cada uno)
                </p>
              </div>
            </label>
          </div>

          <!-- Lista de archivos seleccionados -->
          <div v-if="archivos.length > 0" class="subir-dieta-modal__files-list">
            <div class="subir-dieta-modal__files-header">
              <h3 class="subir-dieta-modal__files-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                  <polyline points="14 2 14 8 20 8"/>
                </svg>
                <span>Archivos seleccionados ({{ archivos.length }})</span>
              </h3>
            </div>
            <div class="subir-dieta-modal__files">
              <div
                v-for="(archivo, index) in archivos"
                :key="index"
                class="subir-dieta-modal__file-item"
              >
                <div class="subir-dieta-modal__file-icon-pdf">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                    <polyline points="10 9 9 9 8 9"/>
                  </svg>
                </div>
                <div class="subir-dieta-modal__file-info">
                  <span class="subir-dieta-modal__file-name">{{ nombreArchivo(archivo) }}</span>
                  <span class="subir-dieta-modal__file-size">{{ tamañoArchivo(archivo.size) }}</span>
                </div>
                <button
                  type="button"
                  class="subir-dieta-modal__file-remove"
                  @click="quitarArchivo(index)"
                  title="Quitar archivo"
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
  align-items: flex-end;
  justify-content: center;
  z-index: 1000;
  animation: subir-dieta-modal-fade 0.2s ease;
}

@keyframes subir-dieta-modal-fade {
  from { opacity: 0; }
  to { opacity: 1; }
}

.subir-dieta-modal {
  background: #161616;
  border-radius: 20px 20px 0 0;
  width: 100%;
  max-width: 100%;
  height: 90vh;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  animation: subir-dieta-modal-slide 0.3s ease;
}

@keyframes subir-dieta-modal-slide {
  from { transform: translateY(100%); }
  to { transform: translateY(0); }
}

@media (min-width: 640px) {
  .subir-dieta-modal__overlay {
    align-items: center;
    padding: 1rem;
  }
  .subir-dieta-modal {
    border-radius: 20px;
    max-width: 600px;
    height: auto;
    max-height: 90vh;
  }
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

.subir-dieta-modal__file-selector--dragging {
  opacity: 1;
}

.subir-dieta-modal__file-selector--dragging .subir-dieta-modal__file-dropzone {
  background: rgba(0, 210, 97, 0.15);
  border-color: #00D261;
  transform: scale(1.02);
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

.subir-dieta-modal__file-dropzone {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 2rem;
  border: 2px dashed rgba(0, 210, 97, 0.4);
  border-radius: 12px;
  background: rgba(0, 210, 97, 0.05);
  transition: all 0.3s ease;
  cursor: pointer;
  min-height: 200px;
}

.subir-dieta-modal__file-dropzone:hover {
  background: rgba(0, 210, 97, 0.1);
  border-color: #00D261;
  transform: translateY(-2px);
}

.subir-dieta-modal__file-icon {
  width: 64px;
  height: 64px;
  color: #00D261;
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.subir-dieta-modal__file-icon svg {
  width: 100%;
  height: 100%;
}

.subir-dieta-modal__file-dropzone-text {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
  margin-bottom: 0.75rem;
}

.subir-dieta-modal__file-dropzone-text-main {
  font-size: 1rem;
  font-weight: 600;
  color: #fff;
}

.subir-dieta-modal__file-dropzone-text-sub {
  font-size: 0.875rem;
  color: #697586;
}

.subir-dieta-modal__file-hint {
  font-size: 0.75rem;
  color: #697586;
  margin: 0;
  text-align: center;
}

.subir-dieta-modal__files-list {
  margin-top: 1.5rem;
}

.subir-dieta-modal__files-header {
  margin-bottom: 1rem;
}

.subir-dieta-modal__files-title {
  font-size: 0.9375rem;
  font-weight: 600;
  color: #fff;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.subir-dieta-modal__files-title svg {
  width: 18px;
  height: 18px;
  color: #00D261;
  flex-shrink: 0;
}

.subir-dieta-modal__files {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.subir-dieta-modal__file-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.875rem 1rem;
  background: #1e1e1e;
  border-radius: 10px;
  border: 1px solid #252525;
  transition: all 0.2s;
}

.subir-dieta-modal__file-item:hover {
  background: #252525;
  border-color: rgba(0, 210, 97, 0.3);
}

.subir-dieta-modal__file-icon-pdf {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(239, 92, 92, 0.1);
  border-radius: 8px;
  color: #EF5C5C;
  flex-shrink: 0;
}

.subir-dieta-modal__file-icon-pdf svg {
  width: 24px;
  height: 24px;
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
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #EF5C5C;
  transition: all 0.2s;
  border-radius: 6px;
  flex-shrink: 0;
  margin-left: auto;
}

.subir-dieta-modal__file-remove:hover {
  background: rgba(239, 92, 92, 0.15);
  transform: scale(1.1);
}

.subir-dieta-modal__file-remove svg {
  width: 18px;
  height: 18px;
}

.subir-dieta-modal__footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 1rem 1.25rem;
  padding-bottom: max(1rem, env(safe-area-inset-bottom));
  border-top: 1px solid #252525;
  flex-shrink: 0;
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
  width: 40%;
  flex-shrink: 0;
}

.subir-dieta-modal__btn--cancel:hover {
  background: #1e1e1e;
}

.subir-dieta-modal__btn--save {
  color: #fff;
  background: #00D261;
  width: 60%;
  flex: 1;
}

.subir-dieta-modal__btn--save:hover:not(:disabled) {
  background: #00b854;
}

.subir-dieta-modal__btn--save:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>

