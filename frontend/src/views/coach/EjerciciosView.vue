<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useApi } from '@/composables/useApi'
import BaseEmptyState from '@/components/ui/BaseEmptyState.vue'
import BaseTable from '@/components/ui/BaseTable.vue'

const { get, post, put, del, cargando } = useApi()
const ejercicios = ref([])
const meta = ref({ total: 0, por_pagina: 15, pagina_actual: 1, ultima_pagina: 1 })
const error = ref('')
const buscar = ref('')
const grupoMuscular = ref('')

// Modal crear/editar
const showFormModal = ref(false)
const formMode = ref('create') // 'create' | 'edit'
const formEjercicio = ref(null)
const formNombre = ref('')
const formGrupo = ref('')
const formVideoUrl = ref('')
const formError = ref('')
const formSaving = ref(false)

// Móvil: ejercicio seleccionado para mostrar acciones Editar / Eliminar
const selectedEjercicio = ref(null)

const GRUPOS_MUSCULARES = [
  { value: 'pecho', label: 'Pecho' },
  { value: 'tricep', label: 'Tríceps' },
  { value: 'bicep', label: 'Bíceps' },
  { value: 'pierna', label: 'Pierna' },
  { value: 'hombro', label: 'Hombro' },
  { value: 'pantorrilla', label: 'Pantorrilla' },
  { value: 'espalda', label: 'Espalda' },
  { value: 'gluteo', label: 'Glúteo' },
  { value: 'cardio', label: 'Cardio' }
]

const paginaActual = computed(() => meta.value.pagina_actual ?? 1)
const totalPaginas = computed(() => meta.value.ultima_pagina ?? 1)
const hayMas = computed(() => paginaActual.value < totalPaginas.value)
const isMobile = ref(false)
const MOBILE_BREAKPOINT = 768

function labelGrupo(value) {
  const g = GRUPOS_MUSCULARES.find(x => x.value === value)
  return g ? g.label : (value || '—')
}

async function cargarEjercicios(pagina = 1) {
  try {
    error.value = ''
    const params = new URLSearchParams()
    if (pagina > 1) params.set('page', pagina)
    if (buscar.value.trim()) params.set('buscar', buscar.value.trim())
    if (grupoMuscular.value) params.set('grupo_muscular', grupoMuscular.value)
    const query = params.toString() ? `?${params.toString()}` : ''
    const res = await get(`/coach/ejercicios${query}`)
    ejercicios.value = res.data?.datos ?? res.datos ?? []
    meta.value = res.meta ?? meta.value
  } catch (e) {
    error.value = e.message || 'No se pudo cargar la lista de ejercicios.'
    ejercicios.value = []
  }
}

function irPagina(pagina) {
  if (pagina < 1 || pagina > totalPaginas.value) return
  cargarEjercicios(pagina)
}

function checkMobile() {
  isMobile.value = window.innerWidth < MOBILE_BREAKPOINT
}

// --- Agregar ejercicio (abre modal en modo crear)
function abrirAgregar() {
  formMode.value = 'create'
  formEjercicio.value = null
  formNombre.value = ''
  formGrupo.value = ''
  formVideoUrl.value = ''
  formError.value = ''
  showFormModal.value = true
}

// --- Editar ejercicio (abre modal en modo editar)
function abrirEditar(e) {
  selectedEjercicio.value = null
  formMode.value = 'edit'
  formEjercicio.value = e
  formNombre.value = e.nombre || ''
  formGrupo.value = e.grupo_muscular || ''
  formVideoUrl.value = e.video_url || ''
  formError.value = ''
  showFormModal.value = true
}

function cerrarFormModal() {
  showFormModal.value = false
  formEjercicio.value = null
  formError.value = ''
}

async function enviarFormulario() {
  formError.value = ''
  const nombre = formNombre.value.trim()
  const grupo = formGrupo.value
  const videoUrl = formVideoUrl.value.trim() || null
  if (!nombre) {
    formError.value = 'El nombre del ejercicio es requerido.'
    return
  }
  if (!grupo) {
    formError.value = 'El grupo muscular es requerido.'
    return
  }
  formSaving.value = true
  try {
    if (formMode.value === 'create') {
      await post('/coach/ejercicios', { nombre, grupo_muscular: grupo, video_url: videoUrl })
      cerrarFormModal()
      await cargarEjercicios(1)
    } else {
      await put(`/coach/ejercicios/${formEjercicio.value.id}`, { nombre, grupo_muscular: grupo, video_url: videoUrl })
      cerrarFormModal()
      await cargarEjercicios(paginaActual.value)
    }
  } catch (e) {
    formError.value = e.errores ? Object.values(e.errores).flat().join(' ') : (e.message || 'Error al guardar.')
  } finally {
    formSaving.value = false
  }
}

// --- Móvil: seleccionar ejercicio para mostrar acciones
function seleccionarEjercicio(e) {
  selectedEjercicio.value = e
}

function cerrarSeleccion() {
  selectedEjercicio.value = null
}

// --- Eliminar ejercicio
async function eliminarEjercicio(e) {
  if (!confirm('¿Seguro que deseas eliminar este ejercicio?')) return
  selectedEjercicio.value = null
  try {
    await del(`/coach/ejercicios/${e.id}`)
    await cargarEjercicios(paginaActual.value)
  } catch (err) {
    error.value = err.message || 'No se pudo eliminar el ejercicio.'
  }
}

onMounted(() => {
  cargarEjercicios(1)
  checkMobile()
  window.addEventListener('resize', checkMobile)
})

onUnmounted(() => {
  window.removeEventListener('resize', checkMobile)
})

watch([buscar, grupoMuscular], () => cargarEjercicios(1))
</script>

<template>
  <div class="ejercicios">
    <div class="ejercicios__wrap">
      <div v-if="error" class="ejercicios__alert">{{ error }}</div>

      <section class="ejercicios__section">
        <div class="ejercicios__section-header">
          <div class="ejercicios__section-title-row">
            <h2 class="ejercicios__section-title">Ejercicios</h2>
            <span class="ejercicios__section-meta" v-if="meta.total != null">
              {{ meta.total }} {{ meta.total === 1 ? 'ejercicio' : 'ejercicios' }}
            </span>
          </div>
          <button type="button" class="ejercicios__btn-add" @click="abrirAgregar" aria-label="Agregar ejercicio">
            Agregar ejercicio
          </button>
        </div>
        <div class="ejercicios__filtros">
          <div class="ejercicios__filtro-search">
            <label for="ejercicios-buscar" class="ejercicios__filtro-label">Buscar</label>
            <input
              id="ejercicios-buscar"
              v-model="buscar"
              type="search"
              class="ejercicios__input"
              placeholder="Nombre o categoría..."
              aria-label="Buscar por nombre o categoría"
            />
          </div>
          <div class="ejercicios__filtro-group">
            <label for="ejercicios-grupo" class="ejercicios__filtro-label">Grupo muscular</label>
            <select id="ejercicios-grupo" v-model="grupoMuscular" class="ejercicios__select" aria-label="Filtrar por grupo muscular">
              <option value="">Todos los grupos</option>
              <option v-for="g in GRUPOS_MUSCULARES" :key="g.value" :value="g.value">
                {{ g.label }}
              </option>
            </select>
          </div>
        </div>

        <!-- Desktop: tabla -->
        <template v-if="!isMobile">
          <div v-if="cargando && !ejercicios.length" class="ejercicios__loading ejercicios__loading--table">
            <div v-for="i in 4" :key="i" class="ejercicios__skeleton-row ejercicios__skeleton-row--table">
              <div class="ejercicios__skeleton-cell" />
              <div class="ejercicios__skeleton-cell" />
              <div class="ejercicios__skeleton-cell" />
            </div>
          </div>
          <div v-else-if="!ejercicios.length" class="ejercicios__empty">
            <BaseEmptyState
              titulo="No hay ejercicios"
              descripcion="Los ejercicios que crees aparecerán aquí."
            />
          </div>
          <div v-else class="ejercicios__table-wrap">
            <table class="ejercicios__table">
              <thead class="ejercicios__thead">
                <tr>
                  <th class="ejercicios__th">Nombre</th>
                  <th class="ejercicios__th">Grupo muscular</th>
                  <th class="ejercicios__th">Video</th>
                  <th class="ejercicios__th ejercicios__th--acciones">Acciones</th>
                </tr>
              </thead>
              <tbody class="ejercicios__tbody">
                <tr v-for="e in ejercicios" :key="e.id" class="ejercicios__tr">
                  <td class="ejercicios__td">{{ e.nombre || '—' }}</td>
                  <td class="ejercicios__td">{{ labelGrupo(e.grupo_muscular) }}</td>
                  <td class="ejercicios__td">
                    <span
                      class="ejercicios__badge"
                      :class="{ 'ejercicios__badge--active': e.video_url }"
                    >
                      {{ e.video_url ? 'Sí' : 'No' }}
                    </span>
                  </td>
                  <td class="ejercicios__td ejercicios__td--acciones">
                    <button type="button" class="ejercicios__action-btn ejercicios__action-btn--edit" @click="abrirEditar(e)" aria-label="Editar ejercicio">Editar</button>
                    <button type="button" class="ejercicios__action-btn ejercicios__action-btn--delete" @click="eliminarEjercicio(e)" aria-label="Eliminar ejercicio">Eliminar</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>

        <!-- Móvil: cards -->
        <BaseTable v-else :items="ejercicios" key-field="id" :loading="cargando">
          <template #header />
          <template #loading>
            <div class="ejercicios__loading">
              <div v-for="i in 4" :key="i" class="ejercicios__skeleton-row">
                <div class="ejercicios__skeleton-text" />
                <div class="ejercicios__skeleton-text" />
              </div>
            </div>
          </template>
          <template #empty>
            <div class="ejercicios__empty">
              <BaseEmptyState
                titulo="No hay ejercicios"
                descripcion="Los ejercicios que crees aparecerán aquí."
              />
            </div>
          </template>
          <template #row="{ item: e }">
            <div class="ejercicios__link" @click="seleccionarEjercicio(e)">
              <div class="ejercicios__info">
                <span class="ejercicios__nombre">{{ e.nombre || '—' }}</span>
                <span class="ejercicios__grupo">{{ labelGrupo(e.grupo_muscular) }}</span>
              </div>
              <span
                class="ejercicios__badge"
                :class="{ 'ejercicios__badge--active': e.video_url }"
              >
                {{ e.video_url ? 'Video' : 'Sin video' }}
              </span>
            </div>
          </template>
        </BaseTable>

        <div v-if="totalPaginas > 1" class="ejercicios__paginacion">
          <button
            type="button"
            class="ejercicios__page-btn"
            :disabled="paginaActual <= 1"
            @click="irPagina(paginaActual - 1)"
          >
            Anterior
          </button>
          <span class="ejercicios__page-info">
            {{ paginaActual }} / {{ totalPaginas }}
          </span>
          <button
            type="button"
            class="ejercicios__page-btn"
            :disabled="!hayMas"
            @click="irPagina(paginaActual + 1)"
          >
            Siguiente
          </button>
        </div>
      </section>

      <!-- Modal crear/editar ejercicio -->
      <Teleport to="body">
        <Transition name="modal">
          <div v-if="showFormModal" class="ejercicios__modal-overlay" @click.self="cerrarFormModal">
            <div class="ejercicios__modal" role="dialog" aria-modal="true" aria-labelledby="ejercicios-modal-title">
              <h3 id="ejercicios-modal-title" class="ejercicios__modal-title">
                {{ formMode === 'create' ? 'Agregar ejercicio' : 'Editar ejercicio' }}
              </h3>
              <form @submit.prevent="enviarFormulario" class="ejercicios__form">
                <div v-if="formError" class="ejercicios__form-error">{{ formError }}</div>
                <label for="ejercicio-nombre" class="ejercicios__form-label">Nombre</label>
                <input id="ejercicio-nombre" v-model="formNombre" type="text" class="ejercicios__form-input" placeholder="Nombre del ejercicio" required />
                <label for="ejercicio-grupo" class="ejercicios__form-label">Grupo muscular</label>
                <select id="ejercicio-grupo" v-model="formGrupo" class="ejercicios__form-select" required aria-label="Grupo muscular">
                  <option value="">Selecciona grupo</option>
                  <option v-for="g in GRUPOS_MUSCULARES" :key="g.value" :value="g.value">{{ g.label }}</option>
                </select>
                <label for="ejercicio-video" class="ejercicios__form-label">URL del video (opcional)</label>
                <input id="ejercicio-video" v-model="formVideoUrl" type="url" class="ejercicios__form-input" placeholder="https://..." aria-label="URL del video" />
                <div class="ejercicios__modal-actions">
                  <button type="button" class="ejercicios__modal-btn ejercicios__modal-btn--secondary" @click="cerrarFormModal">Cancelar</button>
                  <button type="submit" class="ejercicios__modal-btn ejercicios__modal-btn--primary" :disabled="formSaving">
                    {{ formSaving ? 'Guardando…' : (formMode === 'create' ? 'Crear' : 'Guardar') }}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </Transition>
      </Teleport>

      <!-- Móvil: acción al seleccionar ejercicio (Editar / Eliminar) -->
      <Teleport to="body">
        <Transition name="sheet">
          <div v-if="selectedEjercicio && isMobile" class="ejercicios__sheet-overlay" @click.self="cerrarSeleccion">
            <div class="ejercicios__sheet">
              <p class="ejercicios__sheet-title">{{ selectedEjercicio.nombre || '—' }}</p>
              <p class="ejercicios__sheet-subtitle">{{ labelGrupo(selectedEjercicio.grupo_muscular) }}</p>
              <div class="ejercicios__sheet-actions">
                <button type="button" class="ejercicios__sheet-btn ejercicios__sheet-btn--edit" @click="abrirEditar(selectedEjercicio)">
                  Editar ejercicio
                </button>
                <button type="button" class="ejercicios__sheet-btn ejercicios__sheet-btn--delete" @click="eliminarEjercicio(selectedEjercicio)">
                  Eliminar ejercicio
                </button>
                <button type="button" class="ejercicios__sheet-btn ejercicios__sheet-btn--cancel" @click="cerrarSeleccion">
                  Cerrar
                </button>
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>
    </div>
  </div>
</template>

<style scoped>
.ejercicios {
  min-height: 100vh;
  min-height: 100dvh;
  background: #0a0a0a;
  padding: 1rem;
  padding-top: max(1rem, env(safe-area-inset-top));
  padding-bottom: max(2rem, env(safe-area-inset-bottom));
}

.ejercicios__wrap {
  max-width: 32rem;
  margin: 0 auto;
}

@media (min-width: 768px) {
  .ejercicios__wrap {
    max-width: 100%;
    padding: 0 0.5rem;
  }
}

.ejercicios__alert {
  background: color-mix(in srgb, var(--color-danger-500) 12%, transparent);
  border: 1px solid color-mix(in srgb, var(--color-danger-500) 35%, transparent);
  color: var(--color-danger-500);
  padding: 0.875rem 1rem;
  border-radius: 16px;
  font-size: 0.875rem;
  margin-bottom: 1rem;
}

.ejercicios__section {
  background: #161616;
  border-radius: 16px;
  padding: 1rem;
}

.ejercicios__section-header {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}

.ejercicios__section-title-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.ejercicios__section-title {
  font-size: 0.9375rem;
  font-weight: 600;
  color: var(--color-label-tertiary);
  margin: 0;
}

.ejercicios__section-meta {
  font-size: 0.75rem;
  color: var(--color-label-secondary);
}

.ejercicios__btn-add {
  padding: 0.5rem 0.875rem;
  font-size: 0.8125rem;
  font-weight: 500;
  color: var(--color-success-500);
  background: color-mix(in srgb, var(--color-success-500) 15%, transparent);
  border: 1px solid color-mix(in srgb, var(--color-success-500) 40%, transparent);
  border-radius: 12px;
  cursor: pointer;
  transition: background 0.2s, border-color 0.2s;
}

.ejercicios__btn-add:hover {
  background: color-mix(in srgb, var(--color-success-500) 25%, transparent);
  border-color: var(--color-success-500);
}

.ejercicios__filtros {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.ejercicios__filtro-search,
.ejercicios__filtro-group {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.ejercicios__filtro-search {
  flex: 1;
  min-width: 0;
}

.ejercicios__filtro-label {
  font-size: 0.75rem;
  font-weight: 500;
  color: var(--color-label-secondary);
}

.ejercicios__input {
  flex: 1;
  min-width: 0;
  background: #1e1e1e;
  border: 1px solid #252525;
  border-radius: 12px;
  padding: 0.625rem 0.875rem;
  font-size: 0.875rem;
  color: var(--color-label-tertiary);
}

.ejercicios__input::placeholder {
  color: var(--color-label-secondary);
}

.ejercicios__input:focus,
.ejercicios__input:focus-visible {
  outline: none;
  border-color: var(--color-success-500);
  box-shadow: 0 0 0 2px color-mix(in srgb, var(--color-success-500) 35%, transparent);
}

.ejercicios__input:focus-visible {
  --tw-ring-color: var(--color-success-500);
}

.ejercicios__select {
  background: #1e1e1e;
  border: 1px solid #252525;
  border-radius: 12px;
  padding: 0.625rem 0.875rem;
  font-size: 0.875rem;
  color: var(--color-label-tertiary);
  accent-color: var(--color-success-500);
}

.ejercicios__select:focus,
.ejercicios__select:focus-visible {
  outline: none;
  border-color: var(--color-success-500);
  box-shadow: 0 0 0 2px color-mix(in srgb, var(--color-success-500) 35%, transparent);
}

/* Sobrescribe el ring azul global (main.css *:focus-visible) para usar verde */
.ejercicios__select:focus-visible {
  --tw-ring-color: var(--color-success-500);
}

.ejercicios__select option {
  background: #1e1e1e;
  color: var(--color-label-tertiary);
}

.ejercicios__select option:checked {
  background: var(--color-success-500);
  color: #0a0a0a;
}

.ejercicios__link {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.875rem;
  padding: 0.875rem 1rem;
  color: inherit;
  transition: background 0.2s;
}

.ejercicios__link:hover {
  background: rgba(255, 255, 255, 0.03);
}

.ejercicios__info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
}

.ejercicios__nombre {
  font-size: 0.9375rem;
  font-weight: 500;
  color: var(--color-label-tertiary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.ejercicios__grupo {
  font-size: 0.75rem;
  color: var(--color-label-secondary);
}

.ejercicios__badge {
  font-size: 0.6875rem;
  font-weight: 500;
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
  background: color-mix(in srgb, var(--color-label-secondary) 20%, transparent);
  color: var(--color-label-secondary);
  text-transform: uppercase;
  letter-spacing: 0.02em;
  flex-shrink: 0;
}

.ejercicios__badge--active {
  background: color-mix(in srgb, var(--color-success-500) 15%, transparent);
  color: var(--color-success-500);
}

.ejercicios__empty {
  padding: 2rem 0;
}

.ejercicios__empty :deep(h3) {
  color: var(--color-label-tertiary);
}

.ejercicios__empty :deep(p) {
  color: var(--color-label-secondary);
}

.ejercicios__loading {
  padding: 0.5rem 0;
}

.ejercicios__skeleton-row {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  padding: 0.875rem 0;
  border-bottom: 1px solid #252525;
}

.ejercicios__skeleton-text {
  flex: 1;
  height: 1rem;
  border-radius: 6px;
  background: #252525;
  animation: ejercicios-pulse 1.5s ease-in-out infinite;
}

.ejercicios__table-wrap {
  overflow-x: auto;
  border-radius: 12px;
  border: 1px solid #252525;
}

.ejercicios__table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.8125rem;
}

.ejercicios__thead {
  background: #1e1e1e;
}

.ejercicios__th {
  text-align: left;
  padding: 0.75rem 1rem;
  font-weight: 600;
  color: var(--color-label-secondary);
  white-space: nowrap;
  border-bottom: 1px solid #252525;
}

.ejercicios__tbody {
  background: #161616;
}

.ejercicios__tr {
  border-bottom: 1px solid #252525;
  transition: background 0.2s;
}

.ejercicios__tr:last-child {
  border-bottom: none;
}

.ejercicios__tr:hover {
  background: rgba(255, 255, 255, 0.02);
}

.ejercicios__td {
  padding: 0.75rem 1rem;
  color: var(--color-label-tertiary);
  border-bottom: 1px solid #252525;
  vertical-align: middle;
}

.ejercicios__tr:last-child .ejercicios__td {
  border-bottom: none;
}

.ejercicios__loading--table {
  padding: 0.75rem 0;
}

.ejercicios__skeleton-row--table {
  display: flex;
  gap: 0.5rem;
  padding: 0.5rem 0;
  border-bottom: none;
}

.ejercicios__skeleton-cell {
  flex: 1;
  min-width: 4rem;
  height: 2rem;
  background: #252525;
  border-radius: 6px;
  animation: ejercicios-pulse 1.5s ease-in-out infinite;
}

@keyframes ejercicios-pulse {
  0%, 100% { opacity: 0.5; }
  50% { opacity: 1; }
}

.ejercicios__paginacion {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #252525;
}

.ejercicios__page-btn {
  padding: 0.5rem 0.75rem;
  font-size: 0.8125rem;
  font-weight: 500;
  color: var(--color-success-500);
  background: transparent;
  border: 1px solid color-mix(in srgb, var(--color-success-500) 40%, transparent);
  border-radius: 12px;
  cursor: pointer;
  transition: background 0.2s, border-color 0.2s;
}

.ejercicios__page-btn:hover:not(:disabled) {
  background: color-mix(in srgb, var(--color-success-500) 10%, transparent);
  border-color: var(--color-success-500);
}

.ejercicios__page-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.ejercicios__page-info {
  font-size: 0.8125rem;
  color: var(--color-label-secondary);
}

/* Columna Acciones (desktop) */
.ejercicios__th--acciones,
.ejercicios__td--acciones {
  white-space: nowrap;
  width: 1%;
}

.ejercicios__td--acciones {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.ejercicios__action-btn {
  padding: 0.375rem 0.625rem;
  font-size: 0.75rem;
  font-weight: 500;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  transition: opacity 0.2s;
}

.ejercicios__action-btn--edit {
  color: var(--color-success-500);
  background: color-mix(in srgb, var(--color-success-500) 15%, transparent);
}

.ejercicios__action-btn--edit:hover {
  background: color-mix(in srgb, var(--color-success-500) 25%, transparent);
}

.ejercicios__action-btn--delete {
  color: var(--color-danger-500);
  background: color-mix(in srgb, var(--color-danger-500) 15%, transparent);
}

.ejercicios__action-btn--delete:hover {
  background: color-mix(in srgb, var(--color-danger-500) 25%, transparent);
}

/* Modal crear/editar */
.ejercicios__modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 100;
  padding: 1rem;
}

.ejercicios__modal {
  background: #161616;
  border-radius: 16px;
  padding: 1.25rem;
  width: 100%;
  max-width: 24rem;
  border: 1px solid #252525;
}

.ejercicios__modal-title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--color-label-tertiary);
  margin: 0 0 1rem;
}

.ejercicios__form {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.ejercicios__form-error {
  font-size: 0.8125rem;
  color: var(--color-danger-500);
  padding: 0.5rem 0;
}

.ejercicios__form-label {
  font-size: 0.8125rem;
  font-weight: 500;
  color: var(--color-label-secondary);
}

.ejercicios__form-input,
.ejercicios__form-select {
  background: #1e1e1e;
  border: 1px solid #252525;
  border-radius: 12px;
  padding: 0.625rem 0.875rem;
  font-size: 0.875rem;
  color: var(--color-label-tertiary);
  width: 100%;
}

.ejercicios__form-select {
  accent-color: var(--color-success-500);
}

.ejercicios__form-input::placeholder {
  color: var(--color-label-secondary);
}

.ejercicios__form-input:focus,
.ejercicios__form-input:focus-visible,
.ejercicios__form-select:focus,
.ejercicios__form-select:focus-visible {
  outline: none;
  border-color: var(--color-success-500);
  box-shadow: 0 0 0 2px color-mix(in srgb, var(--color-success-500) 35%, transparent);
}

.ejercicios__form-select:focus-visible {
  --tw-ring-color: var(--color-success-500);
}

.ejercicios__form-select option {
  background: #1e1e1e;
  color: var(--color-label-tertiary);
}

.ejercicios__form-select option:checked {
  background: var(--color-success-500);
  color: #0a0a0a;
}

.ejercicios__modal-actions {
  display: flex;
  gap: 0.5rem;
  justify-content: flex-end;
  margin-top: 0.5rem;
}

.ejercicios__modal-btn {
  padding: 0.5rem 1rem;
  font-size: 0.8125rem;
  font-weight: 500;
  border-radius: 12px;
  cursor: pointer;
  transition: opacity 0.2s;
}

.ejercicios__modal-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.ejercicios__modal-btn--secondary {
  background: #252525;
  color: var(--color-label-tertiary);
  border: 1px solid #252525;
}

.ejercicios__modal-btn--primary {
  background: var(--color-success-500);
  color: #0a0a0a;
  border: none;
}

.ejercicios__modal-btn--primary:hover:not(:disabled) {
  opacity: 0.9;
}

/* Transición modal */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
.modal-enter-active .ejercicios__modal,
.modal-leave-active .ejercicios__modal {
  transition: transform 0.2s ease;
}
.modal-enter-from .ejercicios__modal,
.modal-leave-to .ejercicios__modal {
  transform: scale(0.95);
}

/* Sheet móvil (acciones Editar / Eliminar) */
.ejercicios__sheet-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 90;
  display: flex;
  align-items: flex-end;
  justify-content: center;
}

.ejercicios__sheet {
  background: #161616;
  border-radius: 16px 16px 0 0;
  padding: 1.25rem;
  padding-bottom: max(1.25rem, env(safe-area-inset-bottom));
  width: 100%;
  max-width: 32rem;
  border: 1px solid #252525;
  border-bottom: none;
}

.ejercicios__sheet-title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--color-label-tertiary);
  margin: 0 0 0.25rem;
}

.ejercicios__sheet-subtitle {
  font-size: 0.8125rem;
  color: var(--color-label-secondary);
  margin: 0 0 1rem;
}

.ejercicios__sheet-actions {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.ejercicios__sheet-btn {
  padding: 0.75rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: 12px;
  border: none;
  cursor: pointer;
  transition: background 0.2s;
  text-align: center;
}

.ejercicios__sheet-btn--edit {
  background: color-mix(in srgb, var(--color-success-500) 15%, transparent);
  color: var(--color-success-500);
}

.ejercicios__sheet-btn--edit:hover {
  background: color-mix(in srgb, var(--color-success-500) 25%, transparent);
}

.ejercicios__sheet-btn--delete {
  background: color-mix(in srgb, var(--color-danger-500) 15%, transparent);
  color: var(--color-danger-500);
}

.ejercicios__sheet-btn--delete:hover {
  background: color-mix(in srgb, var(--color-danger-500) 25%, transparent);
}

.ejercicios__sheet-btn--cancel {
  background: #252525;
  color: var(--color-label-secondary);
}

.ejercicios__sheet-btn--cancel:hover {
  background: #1e1e1e;
  color: var(--color-label-tertiary);
}

.sheet-enter-active,
.sheet-leave-active {
  transition: opacity 0.2s ease;
}
.sheet-enter-from,
.sheet-leave-to {
  opacity: 0;
}
.sheet-enter-active .ejercicios__sheet,
.sheet-leave-active .ejercicios__sheet {
  transition: transform 0.25s ease;
}
.sheet-enter-from .ejercicios__sheet,
.sheet-leave-to .ejercicios__sheet {
  transform: translateY(100%);
}
</style>
