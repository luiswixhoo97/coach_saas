<script setup>
import { ref, computed, watch } from 'vue'
import { useApi } from '@/composables/useApi'
import Swal from 'sweetalert2'

const props = defineProps({
  clientes: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['close', 'subida'])

const { get, subirArchivo } = useApi()

const todosClientes = ref([])
const buscar = ref('')
const clientesSeleccionados = ref(new Set())
const archivos = ref([])
const error = ref('')
const guardando = ref(false)
const isDragging = ref(false)
const cargandoClientes = ref(false)

// Cargar todos los clientes al abrir el modal
async function cargarTodosClientes() {
  cargandoClientes.value = true
  try {
    const res = await get('/coach/clientes?per_page=1000')
    todosClientes.value = res.data?.datos ?? res.datos ?? []
  } catch (e) {
    error.value = e.message || 'No se pudieron cargar los clientes.'
    todosClientes.value = []
  } finally {
    cargandoClientes.value = false
  }
}

// Búsqueda con mínimo 3 caracteres, si no hay búsqueda muestra todos
const clientesFiltrados = computed(() => {
  if (!buscar.value || buscar.value.length === 0) {
    return todosClientes.value
  }
  if (buscar.value.length < 3) {
    return []
  }
  const termino = buscar.value.toLowerCase().trim()
  return todosClientes.value.filter(c => {
    const nombre = `${c.nombre || ''} ${c.apellido_paterno || ''} ${c.apellido_materno || ''}`.toLowerCase()
    const email = (c.email || '').toLowerCase()
    return nombre.includes(termino) || email.includes(termino)
  })
})

function nombreCompleto(c) {
  if (!c) return ''
  const partes = [c.nombre, c.apellido_paterno, c.apellido_materno].filter(Boolean)
  return partes.join(' ') || c.email || '—'
}

function toggleSeleccion(clienteId) {
  if (clientesSeleccionados.value.has(clienteId)) {
    clientesSeleccionados.value.delete(clienteId)
  } else {
    clientesSeleccionados.value.add(clienteId)
  }
}

function toggleSeleccionTodos() {
  if (clientesSeleccionados.value.size === clientesFiltrados.value.length) {
    clientesFiltrados.value.forEach(c => clientesSeleccionados.value.delete(c.id))
  } else {
    clientesFiltrados.value.forEach(c => clientesSeleccionados.value.add(c.id))
  }
}

const haySeleccionados = computed(() => clientesSeleccionados.value.size > 0)
const puedeBuscar = computed(() => buscar.value.length >= 3 || buscar.value.length === 0)

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

  if (clientesSeleccionados.value.size === 0) {
    error.value = 'Debes seleccionar al menos un cliente.'
    return
  }

  guardando.value = true
  error.value = ''

  try {
    const clienteIds = Array.from(clientesSeleccionados.value)

    const formData = new FormData()
    archivos.value.forEach((archivo) => {
      formData.append('archivos[]', archivo)
    })
    clienteIds.forEach((id) => {
      formData.append('cliente_ids[]', id.toString())
    })

    await subirArchivo('/coach/dietas/subir-varios', formData)

    const result = await Swal.fire({
      title: 'Dieta subida',
      text: `Los archivos se han subido correctamente a ${clienteIds.length} cliente(s).`,
      icon: 'success',
      confirmButtonColor: '#00D261',
      target: document.body,
      customClass: {
        popup: 'swal-popup-over-modal',
        container: 'swal-container-over-modal'
      },
      allowOutsideClick: false,
      allowEscapeKey: false
    })

    if (result.isConfirmed || result.isDismissed) {
      emit('subida')
      emit('close')
    }
  } catch (e) {
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

// Cargar clientes cuando se abre el modal
watch(() => props.clientes, () => {
  if (props.clientes && props.clientes.length > 0) {
    todosClientes.value = [...props.clientes]
  } else {
    cargarTodosClientes()
  }
}, { immediate: true })
</script>

<template>
  <Teleport to="body">
    <div class="subir-dieta-usuarios-modal__overlay" @click.self="$emit('close')">
      <div class="subir-dieta-usuarios-modal">
        <div class="subir-dieta-usuarios-modal__header">
          <h2 class="subir-dieta-usuarios-modal__title">Subir dieta a usuarios</h2>
          <button
            type="button"
            class="subir-dieta-usuarios-modal__close"
            aria-label="Cerrar"
            @click="$emit('close')"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 6L6 18M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <div class="subir-dieta-usuarios-modal__body">
          <div v-if="error" class="subir-dieta-usuarios-modal__error">{{ error }}</div>

          <!-- Búsqueda -->
          <div class="subir-dieta-usuarios-modal__search">
            <input
              v-model="buscar"
              type="search"
              class="subir-dieta-usuarios-modal__search-input"
              placeholder="Buscar usuarios (mínimo 3 caracteres)..."
              aria-label="Buscar usuarios"
            />
            <div v-if="buscar.length > 0 && buscar.length < 3" class="subir-dieta-usuarios-modal__search-hint">
              Escribe al menos 3 caracteres para buscar
            </div>
          </div>

          <!-- Tabla de usuarios -->
          <div v-if="clientesFiltrados.length > 0" class="subir-dieta-usuarios-modal__table-container">
            <table class="subir-dieta-usuarios-modal__table">
              <thead class="subir-dieta-usuarios-modal__thead">
                <tr>
                  <th class="subir-dieta-usuarios-modal__th subir-dieta-usuarios-modal__th--checkbox">
                    <input
                      type="checkbox"
                      :checked="clientesSeleccionados.size === clientesFiltrados.length && clientesFiltrados.length > 0"
                      @change="toggleSeleccionTodos"
                      class="subir-dieta-usuarios-modal__checkbox"
                    />
                  </th>
                  <th class="subir-dieta-usuarios-modal__th">Nombre</th>
                  <th class="subir-dieta-usuarios-modal__th">Email</th>
                </tr>
              </thead>
              <tbody class="subir-dieta-usuarios-modal__tbody">
                <tr
                  v-for="c in clientesFiltrados"
                  :key="c.id"
                  class="subir-dieta-usuarios-modal__tr"
                >
                  <td class="subir-dieta-usuarios-modal__td subir-dieta-usuarios-modal__td--checkbox">
                    <input
                      type="checkbox"
                      :checked="clientesSeleccionados.has(c.id)"
                      @change="toggleSeleccion(c.id)"
                      class="subir-dieta-usuarios-modal__checkbox"
                    />
                  </td>
                  <td class="subir-dieta-usuarios-modal__td">{{ nombreCompleto(c) }}</td>
                  <td class="subir-dieta-usuarios-modal__td">{{ c.email || '—' }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-else-if="buscar.length >= 3 && !cargandoClientes && clientesFiltrados.length === 0" class="subir-dieta-usuarios-modal__empty">
            <p>No se encontraron usuarios</p>
          </div>

          <div v-if="cargandoClientes" class="subir-dieta-usuarios-modal__loading">
            <p>Cargando usuarios...</p>
          </div>

          <div v-else-if="!cargandoClientes && clientesFiltrados.length === 0 && buscar.length === 0" class="subir-dieta-usuarios-modal__empty">
            <p>No hay usuarios disponibles</p>
          </div>

          <!-- Contador de seleccionados -->
          <div v-if="haySeleccionados" class="subir-dieta-usuarios-modal__selected">
            <span>{{ clientesSeleccionados.size }} {{ clientesSeleccionados.size === 1 ? 'usuario seleccionado' : 'usuarios seleccionados' }}</span>
          </div>

          <!-- Selector de archivos -->
          <div class="subir-dieta-usuarios-modal__file-section">
            <h3 class="subir-dieta-usuarios-modal__file-title">Archivos de dieta</h3>
            <div 
              class="subir-dieta-usuarios-modal__file-selector"
              :class="{ 'subir-dieta-usuarios-modal__file-selector--dragging': isDragging }"
              @dragover.prevent="onDragOver"
              @dragleave="onDragLeave"
              @drop="onDrop"
            >
              <label class="subir-dieta-usuarios-modal__file-label">
                <input
                  type="file"
                  multiple
                  accept=".pdf,application/pdf"
                  class="subir-dieta-usuarios-modal__file-input"
                  @change="onArchivosSeleccionados"
                />
                <div class="subir-dieta-usuarios-modal__file-dropzone">
                  <div class="subir-dieta-usuarios-modal__file-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                      <polyline points="17 8 12 3 7 8"/>
                      <line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                  </div>
                  <p class="subir-dieta-usuarios-modal__file-dropzone-text">
                    <span class="subir-dieta-usuarios-modal__file-dropzone-text-main">Arrastra archivos PDF aquí</span>
                    <span class="subir-dieta-usuarios-modal__file-dropzone-text-sub">o haz clic para seleccionar</span>
                  </p>
                </div>
              </label>
            </div>

            <!-- Lista de archivos -->
            <div v-if="archivos.length > 0" class="subir-dieta-usuarios-modal__files-list">
              <div
                v-for="(archivo, index) in archivos"
                :key="index"
                class="subir-dieta-usuarios-modal__file-item"
              >
                <div class="subir-dieta-usuarios-modal__file-icon-pdf">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                  </svg>
                </div>
                <div class="subir-dieta-usuarios-modal__file-info">
                  <span class="subir-dieta-usuarios-modal__file-name">{{ nombreArchivo(archivo) }}</span>
                  <span class="subir-dieta-usuarios-modal__file-size">{{ tamañoArchivo(archivo.size) }}</span>
                </div>
                <button
                  type="button"
                  class="subir-dieta-usuarios-modal__file-remove"
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

        <div class="subir-dieta-usuarios-modal__footer">
          <button
            type="button"
            class="subir-dieta-usuarios-modal__btn subir-dieta-usuarios-modal__btn--cancel"
            @click="$emit('close')"
          >
            Cancelar
          </button>
          <button
            type="button"
            class="subir-dieta-usuarios-modal__btn subir-dieta-usuarios-modal__btn--save"
            :disabled="guardando || archivos.length === 0 || !haySeleccionados"
            @click="guardar"
          >
            <span v-if="guardando">Subiendo...</span>
            <span v-else>Subir dieta</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.subir-dieta-usuarios-modal__overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.75);
  display: flex;
  align-items: flex-end;
  justify-content: center;
  z-index: 2000;
  animation: subir-dieta-usuarios-modal-fade 0.2s ease;
}

/* Asegurar que SweetAlert esté por encima del modal */
:global(body) {
  --swal-z-index: 3000;
}

@keyframes subir-dieta-usuarios-modal-fade {
  from { opacity: 0; }
  to { opacity: 1; }
}

.subir-dieta-usuarios-modal {
  background: #161616;
  border-radius: 20px 20px 0 0;
  width: 100%;
  max-width: 100%;
  height: 90vh;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  animation: subir-dieta-usuarios-modal-slide 0.3s ease;
}

@keyframes subir-dieta-usuarios-modal-slide {
  from { transform: translateY(100%); }
  to { transform: translateY(0); }
}

@media (min-width: 640px) {
  .subir-dieta-usuarios-modal__overlay {
    align-items: center;
    padding: 1rem;
  }
  .subir-dieta-usuarios-modal {
    border-radius: 20px;
    max-width: 800px;
    height: auto;
    max-height: 90vh;
  }
}

.subir-dieta-usuarios-modal__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.25rem;
  border-bottom: 1px solid #252525;
  flex-shrink: 0;
}

.subir-dieta-usuarios-modal__title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #fff;
  margin: 0;
}

.subir-dieta-usuarios-modal__close {
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
  flex-shrink: 0;
}

.subir-dieta-usuarios-modal__close:hover {
  background: #333;
  color: #fff;
}

.subir-dieta-usuarios-modal__close svg {
  width: 18px;
  height: 18px;
}

.subir-dieta-usuarios-modal__body {
  padding: 1.25rem;
  overflow-y: auto;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.subir-dieta-usuarios-modal__error {
  background: rgba(239, 92, 92, 0.12);
  border: 1px solid rgba(239, 92, 92, 0.3);
  color: #EF5C5C;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  font-size: 0.875rem;
}

.subir-dieta-usuarios-modal__search {
  margin-bottom: 0.5rem;
}

.subir-dieta-usuarios-modal__search-input {
  width: 100%;
  background: #1e1e1e;
  border: 1px solid #252525;
  border-radius: 12px;
  padding: 0.625rem 0.875rem;
  font-size: 0.875rem;
  color: #fff;
}

.subir-dieta-usuarios-modal__search-input:focus {
  outline: none;
  border-color: #00D261;
  box-shadow: 0 0 0 2px rgba(0, 210, 97, 0.2);
}

.subir-dieta-usuarios-modal__search-hint {
  font-size: 0.75rem;
  color: #697586;
  margin-top: 0.5rem;
}

.subir-dieta-usuarios-modal__table-container {
  height: calc(3 * (0.75rem * 2 + 1rem) + 2.5rem); /* 3 filas + header */
  max-height: calc(3 * (0.75rem * 2 + 1rem) + 2.5rem);
  overflow-y: auto;
  border: 1px solid #252525;
  border-radius: 8px;
  background: #1e1e1e;
}

.subir-dieta-usuarios-modal__table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.8125rem;
}

.subir-dieta-usuarios-modal__thead {
  position: sticky;
  top: 0;
  background: #1e1e1e;
  z-index: 1;
}

.subir-dieta-usuarios-modal__th {
  text-align: left;
  padding: 0.75rem 1rem;
  font-weight: 600;
  color: #a0a0a0;
  border-bottom: 1px solid #252525;
}

.subir-dieta-usuarios-modal__th--checkbox {
  width: 40px;
  text-align: center;
}

.subir-dieta-usuarios-modal__tbody {
  background: #161616;
}

.subir-dieta-usuarios-modal__tr {
  border-bottom: 1px solid #252525;
  transition: background 0.2s;
}

.subir-dieta-usuarios-modal__tr:last-child {
  border-bottom: none;
}

.subir-dieta-usuarios-modal__tr:hover {
  background: rgba(255, 255, 255, 0.02);
}

.subir-dieta-usuarios-modal__td {
  padding: 0.75rem 1rem;
  color: #fff;
  vertical-align: middle;
}

.subir-dieta-usuarios-modal__td--checkbox {
  text-align: center;
}

.subir-dieta-usuarios-modal__checkbox {
  width: 18px;
  height: 18px;
  cursor: pointer;
  accent-color: #00D261;
}

.subir-dieta-usuarios-modal__empty,
.subir-dieta-usuarios-modal__loading {
  padding: 2rem;
  text-align: center;
  color: #697586;
  font-size: 0.875rem;
}

.subir-dieta-usuarios-modal__selected {
  padding: 0.75rem 1rem;
  background: rgba(0, 210, 97, 0.1);
  border: 1px solid rgba(0, 210, 97, 0.3);
  border-radius: 8px;
  font-size: 0.875rem;
  color: #00D261;
  font-weight: 500;
}

.subir-dieta-usuarios-modal__file-section {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #252525;
}

.subir-dieta-usuarios-modal__file-title {
  font-size: 0.9375rem;
  font-weight: 600;
  color: #fff;
  margin: 0 0 0.75rem 0;
}

.subir-dieta-usuarios-modal__file-selector {
  margin-bottom: 1rem;
}

.subir-dieta-usuarios-modal__file-selector--dragging .subir-dieta-usuarios-modal__file-dropzone {
  background: rgba(0, 210, 97, 0.15);
  border-color: #00D261;
  transform: scale(1.02);
}

.subir-dieta-usuarios-modal__file-label {
  display: block;
  cursor: pointer;
}

.subir-dieta-usuarios-modal__file-input {
  position: absolute;
  width: 1px;
  height: 1px;
  opacity: 0;
  overflow: hidden;
}

.subir-dieta-usuarios-modal__file-dropzone {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  border: 2px dashed rgba(0, 210, 97, 0.4);
  border-radius: 12px;
  background: rgba(0, 210, 97, 0.05);
  transition: all 0.3s ease;
  cursor: pointer;
  min-height: 120px;
}

.subir-dieta-usuarios-modal__file-dropzone:hover {
  background: rgba(0, 210, 97, 0.1);
  border-color: #00D261;
}

.subir-dieta-usuarios-modal__file-icon {
  width: 48px;
  height: 48px;
  color: #00D261;
  margin-bottom: 0.75rem;
}

.subir-dieta-usuarios-modal__file-icon svg {
  width: 100%;
  height: 100%;
}

.subir-dieta-usuarios-modal__file-dropzone-text {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
}

.subir-dieta-usuarios-modal__file-dropzone-text-main {
  font-size: 0.875rem;
  font-weight: 600;
  color: #fff;
}

.subir-dieta-usuarios-modal__file-dropzone-text-sub {
  font-size: 0.75rem;
  color: #697586;
}

.subir-dieta-usuarios-modal__files-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-top: 0.75rem;
}

.subir-dieta-usuarios-modal__file-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.875rem 1rem;
  background: #1e1e1e;
  border-radius: 10px;
  border: 1px solid #252525;
}

.subir-dieta-usuarios-modal__file-icon-pdf {
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

.subir-dieta-usuarios-modal__file-icon-pdf svg {
  width: 24px;
  height: 24px;
}

.subir-dieta-usuarios-modal__file-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  flex: 1;
  min-width: 0;
}

.subir-dieta-usuarios-modal__file-name {
  font-size: 0.875rem;
  font-weight: 500;
  color: #fff;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.subir-dieta-usuarios-modal__file-size {
  font-size: 0.75rem;
  color: #697586;
}

.subir-dieta-usuarios-modal__file-remove {
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
}

.subir-dieta-usuarios-modal__file-remove:hover {
  background: rgba(239, 92, 92, 0.15);
  transform: scale(1.1);
}

.subir-dieta-usuarios-modal__file-remove svg {
  width: 18px;
  height: 18px;
}

.subir-dieta-usuarios-modal__footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 1rem 1.25rem;
  padding-bottom: max(1rem, env(safe-area-inset-bottom));
  border-top: 1px solid #252525;
  flex-shrink: 0;
}

.subir-dieta-usuarios-modal__btn {
  padding: 0.625rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.subir-dieta-usuarios-modal__btn--cancel {
  color: #697586;
  background: transparent;
  border: 1px solid #252525;
  width: 40%;
  flex-shrink: 0;
}

.subir-dieta-usuarios-modal__btn--cancel:hover {
  background: #1e1e1e;
}

.subir-dieta-usuarios-modal__btn--save {
  color: #fff;
  background: #00D261;
  width: 60%;
  flex: 1;
}

.subir-dieta-usuarios-modal__btn--save:hover:not(:disabled) {
  background: #00b854;
}

.subir-dieta-usuarios-modal__btn--save:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* SweetAlert sobre el modal */
:global(.swal-popup-over-modal) {
  z-index: 3000 !important;
  position: relative !important;
}

:global(.swal-container-over-modal) {
  z-index: 3000 !important;
  position: fixed !important;
}

:global(.swal-overlay.swal-overlay--show-modal.swal-overlay) {
  z-index: 2999 !important;
  position: fixed !important;
}
</style>

