<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useApi } from '@/composables/useApi'
import BaseEmptyState from '@/components/ui/BaseEmptyState.vue'
import BaseTable from '@/components/ui/BaseTable.vue'
import FormularioCrearModal from '@/components/coach/FormularioCrearModal.vue'

const { get, post, put, del, cargando } = useApi()
const formularios = ref([])
const meta = ref({ total: 0, por_pagina: 15, pagina_actual: 1, ultima_pagina: 1 })
const error = ref('')
const buscar = ref('')

// Modal crear/editar
const showFormModal = ref(false)
const formMode = ref('create') // 'create' | 'edit'
const formFormulario = ref(null)

// Móvil: formulario seleccionado para mostrar acciones Editar / Eliminar
const selectedFormulario = ref(null)

const paginaActual = computed(() => meta.value.pagina_actual ?? 1)
const totalPaginas = computed(() => meta.value.ultima_pagina ?? 1)
const hayMas = computed(() => paginaActual.value < totalPaginas.value)
const isMobile = ref(false)
const MOBILE_BREAKPOINT = 768

async function cargarFormularios(pagina = 1) {
  try {
    error.value = ''
    const params = new URLSearchParams()
    if (pagina > 1) params.set('page', pagina)
    if (buscar.value.trim()) params.set('buscar', buscar.value.trim())
    const query = params.toString() ? `?${params.toString()}` : ''
    const res = await get(`/coach/formularios${query}`)
    formularios.value = res.data?.datos ?? res.datos ?? []
    meta.value = res.meta ?? meta.value
  } catch (e) {
    error.value = e.message || 'No se pudo cargar la lista de formularios.'
    formularios.value = []
  }
}

function irPagina(pagina) {
  if (pagina < 1 || pagina > totalPaginas.value) return
  cargarFormularios(pagina)
}

function checkMobile() {
  isMobile.value = window.innerWidth < MOBILE_BREAKPOINT
}

// --- Agregar formulario (abre modal en modo crear)
function abrirAgregar() {
  formMode.value = 'create'
  formFormulario.value = null
  showFormModal.value = true
}

// --- Editar formulario (abre modal en modo editar)
function abrirEditar(f) {
  selectedFormulario.value = null
  formMode.value = 'edit'
  formFormulario.value = f
  showFormModal.value = true
}

function cerrarFormModal() {
  showFormModal.value = false
  formFormulario.value = null
}

function onFormularioCreado() {
  cerrarFormModal()
  cargarFormularios(paginaActual.value)
}

// --- Móvil: seleccionar formulario para mostrar acciones
function seleccionarFormulario(f) {
  selectedFormulario.value = f
}

function cerrarSeleccion() {
  selectedFormulario.value = null
}

// --- Eliminar formulario
async function eliminarFormulario(f) {
  if (!confirm('¿Seguro que deseas eliminar este formulario?')) return
  selectedFormulario.value = null
  try {
    await del(`/coach/formularios/${f.id}`)
    await cargarFormularios(paginaActual.value)
  } catch (err) {
    error.value = err.message || 'No se pudo eliminar el formulario.'
  }
}

onMounted(() => {
  cargarFormularios(1)
  checkMobile()
  window.addEventListener('resize', checkMobile)
})

onUnmounted(() => {
  window.removeEventListener('resize', checkMobile)
})

watch(buscar, () => cargarFormularios(1))
</script>

<template>
  <div class="formularios">
    <div class="formularios__wrap">
      <div v-if="error" class="formularios__alert">{{ error }}</div>

      <section class="formularios__section">
        <div class="formularios__section-header">
          <div class="formularios__section-title-row">
            <h2 class="formularios__section-title">Formularios</h2>
            <span class="formularios__section-meta" v-if="meta.total != null">
              {{ meta.total }} {{ meta.total === 1 ? 'formulario' : 'formularios' }}
            </span>
          </div>
          <button type="button" class="formularios__btn-add" @click="abrirAgregar" aria-label="Agregar formulario">
            Crear formulario
          </button>
        </div>
        <div class="formularios__filtros">
          <div class="formularios__filtro-search">
            <label for="formularios-buscar" class="formularios__filtro-label">Buscar</label>
            <input
              id="formularios-buscar"
              v-model="buscar"
              type="search"
              class="formularios__input"
              placeholder="Nombre del formulario..."
              aria-label="Buscar por nombre"
            />
          </div>
        </div>

        <!-- Desktop: tabla -->
        <template v-if="!isMobile">
          <div v-if="cargando && !formularios.length" class="formularios__loading formularios__loading--table">
            <div v-for="i in 4" :key="i" class="formularios__skeleton-row formularios__skeleton-row--table">
              <div class="formularios__skeleton-cell" />
              <div class="formularios__skeleton-cell" />
              <div class="formularios__skeleton-cell" />
              <div class="formularios__skeleton-cell" />
            </div>
          </div>
          <div v-else-if="!formularios.length" class="formularios__empty">
            <BaseEmptyState
              titulo="No hay formularios"
              descripcion="Los formularios que crees aparecerán aquí."
            />
          </div>
          <div v-else class="formularios__table-wrap">
            <table class="formularios__table">
              <thead class="formularios__thead">
                <tr>
                  <th class="formularios__th">Nombre</th>
                  <th class="formularios__th">Preguntas</th>
                  <th class="formularios__th">Respuestas</th>
                  <th class="formularios__th">Estado</th>
                  <th class="formularios__th formularios__th--acciones">Acciones</th>
                </tr>
              </thead>
              <tbody class="formularios__tbody">
                <tr v-for="f in formularios" :key="f.id" class="formularios__tr">
                  <td class="formularios__td">{{ f.nombre || '—' }}</td>
                  <td class="formularios__td">{{ f.preguntas_count || 0 }}</td>
                  <td class="formularios__td">{{ f.respuestas_count || 0 }}</td>
                  <td class="formularios__td">
                    <span
                      class="formularios__badge"
                      :class="{ 'formularios__badge--active': f.activo }"
                    >
                      {{ f.activo ? 'Activo' : 'Inactivo' }}
                    </span>
                  </td>
                  <td class="formularios__td formularios__td--acciones">
                    <button type="button" class="formularios__action-btn formularios__action-btn--edit" @click="abrirEditar(f)" aria-label="Editar formulario">Editar</button>
                    <button type="button" class="formularios__action-btn formularios__action-btn--delete" @click="eliminarFormulario(f)" aria-label="Eliminar formulario">Eliminar</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>

        <!-- Móvil: cards -->
        <BaseTable v-else :items="formularios" key-field="id" :loading="cargando">
          <template #header />
          <template #loading>
            <div class="formularios__loading">
              <div v-for="i in 4" :key="i" class="formularios__skeleton-row">
                <div class="formularios__skeleton-text" />
                <div class="formularios__skeleton-text" />
              </div>
            </div>
          </template>
          <template #empty>
            <div class="formularios__empty">
              <BaseEmptyState
                titulo="No hay formularios"
                descripcion="Los formularios que crees aparecerán aquí."
              />
            </div>
          </template>
          <template #row="{ item: f }">
            <div class="formularios__link" @click="seleccionarFormulario(f)">
              <div class="formularios__info">
                <span class="formularios__nombre">{{ f.nombre || '—' }}</span>
                <span class="formularios__meta">{{ f.preguntas_count || 0 }} preguntas · {{ f.respuestas_count || 0 }} respuestas</span>
              </div>
              <span
                class="formularios__badge"
                :class="{ 'formularios__badge--active': f.activo }"
              >
                {{ f.activo ? 'Activo' : 'Inactivo' }}
              </span>
            </div>
          </template>
        </BaseTable>

        <div v-if="totalPaginas > 1" class="formularios__paginacion">
          <button
            type="button"
            class="formularios__page-btn"
            :disabled="paginaActual <= 1"
            @click="irPagina(paginaActual - 1)"
          >
            Anterior
          </button>
          <span class="formularios__page-info">
            {{ paginaActual }} / {{ totalPaginas }}
          </span>
          <button
            type="button"
            class="formularios__page-btn"
            :disabled="!hayMas"
            @click="irPagina(paginaActual + 1)"
          >
            Siguiente
          </button>
        </div>
      </section>

      <!-- Modal crear/editar formulario -->
      <FormularioCrearModal
        v-if="showFormModal"
        :formulario-para-editar="formMode === 'edit' ? formFormulario : null"
        @close="cerrarFormModal"
        @created="onFormularioCreado"
      />

      <!-- Móvil: acción al seleccionar formulario (Editar / Eliminar) -->
      <Teleport to="body">
        <Transition name="sheet">
          <div v-if="selectedFormulario && isMobile" class="formularios__sheet-overlay" @click.self="cerrarSeleccion">
            <div class="formularios__sheet">
              <p class="formularios__sheet-title">{{ selectedFormulario.nombre || '—' }}</p>
              <p class="formularios__sheet-subtitle">{{ selectedFormulario.preguntas_count || 0 }} preguntas · {{ selectedFormulario.respuestas_count || 0 }} respuestas</p>
              <div class="formularios__sheet-actions">
                <button type="button" class="formularios__sheet-btn formularios__sheet-btn--edit" @click="abrirEditar(selectedFormulario)">
                  Editar formulario
                </button>
                <button type="button" class="formularios__sheet-btn formularios__sheet-btn--delete" @click="eliminarFormulario(selectedFormulario)">
                  Eliminar formulario
                </button>
                <button type="button" class="formularios__sheet-btn formularios__sheet-btn--cancel" @click="cerrarSeleccion">
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
.formularios {
  min-height: 100vh;
  min-height: 100dvh;
  background: #0a0a0a;
  padding: 1rem;
  padding-top: max(1rem, env(safe-area-inset-top));
  padding-bottom: max(2rem, env(safe-area-inset-bottom));
}

.formularios__wrap {
  max-width: 32rem;
  margin: 0 auto;
}

@media (min-width: 768px) {
  .formularios__wrap {
    max-width: 100%;
    padding: 0 0.5rem;
  }
}

.formularios__alert {
  background: color-mix(in srgb, var(--color-danger-500) 12%, transparent);
  border: 1px solid color-mix(in srgb, var(--color-danger-500) 35%, transparent);
  color: var(--color-danger-500);
  padding: 0.875rem 1rem;
  border-radius: 16px;
  font-size: 0.875rem;
  margin-bottom: 1rem;
}

.formularios__section {
  background: #161616;
  border-radius: 16px;
  padding: 1rem;
}

.formularios__section-header {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}

.formularios__section-title-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.formularios__section-title {
  font-size: 0.9375rem;
  font-weight: 600;
  color: var(--color-label-tertiary);
  margin: 0;
}

.formularios__section-meta {
  font-size: 0.75rem;
  color: var(--color-label-secondary);
}

.formularios__btn-add {
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

.formularios__btn-add:hover {
  background: color-mix(in srgb, var(--color-success-500) 25%, transparent);
  border-color: var(--color-success-500);
}

.formularios__filtros {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.formularios__filtro-search {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  flex: 1;
  min-width: 0;
}

.formularios__filtro-label {
  font-size: 0.75rem;
  font-weight: 500;
  color: var(--color-label-secondary);
}

.formularios__input {
  flex: 1;
  min-width: 0;
  background: #1e1e1e;
  border: 1px solid #252525;
  border-radius: 12px;
  padding: 0.625rem 0.875rem;
  font-size: 0.875rem;
  color: var(--color-label-tertiary);
}

.formularios__input::placeholder {
  color: var(--color-label-secondary);
}

.formularios__input:focus,
.formularios__input:focus-visible {
  outline: none;
  border-color: var(--color-success-500);
  box-shadow: 0 0 0 2px color-mix(in srgb, var(--color-success-500) 35%, transparent);
}

.formularios__link {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.875rem;
  padding: 0.875rem 1rem;
  color: inherit;
  transition: background 0.2s;
}

.formularios__link:hover {
  background: rgba(255, 255, 255, 0.03);
}

.formularios__info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
}

.formularios__nombre {
  font-size: 0.9375rem;
  font-weight: 500;
  color: var(--color-label-tertiary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.formularios__meta {
  font-size: 0.75rem;
  color: var(--color-label-secondary);
}

.formularios__badge {
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

.formularios__badge--active {
  background: color-mix(in srgb, var(--color-success-500) 15%, transparent);
  color: var(--color-success-500);
}

.formularios__empty {
  padding: 2rem 0;
}

.formularios__empty :deep(h3) {
  color: var(--color-label-tertiary);
}

.formularios__empty :deep(p) {
  color: var(--color-label-secondary);
}

.formularios__loading {
  padding: 0.5rem 0;
}

.formularios__skeleton-row {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  padding: 0.875rem 0;
  border-bottom: 1px solid #252525;
}

.formularios__skeleton-text {
  flex: 1;
  height: 1rem;
  border-radius: 6px;
  background: #252525;
  animation: formularios-pulse 1.5s ease-in-out infinite;
}

.formularios__table-wrap {
  overflow-x: auto;
  border-radius: 12px;
  border: 1px solid #252525;
}

.formularios__table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.8125rem;
}

.formularios__thead {
  background: #1e1e1e;
}

.formularios__th {
  text-align: left;
  padding: 0.75rem 1rem;
  font-weight: 600;
  color: var(--color-label-secondary);
  white-space: nowrap;
  border-bottom: 1px solid #252525;
}

.formularios__tbody {
  background: #161616;
}

.formularios__tr {
  border-bottom: 1px solid #252525;
  transition: background 0.2s;
}

.formularios__tr:last-child {
  border-bottom: none;
}

.formularios__tr:hover {
  background: rgba(255, 255, 255, 0.02);
}

.formularios__td {
  padding: 0.75rem 1rem;
  color: var(--color-label-tertiary);
  border-bottom: 1px solid #252525;
  vertical-align: middle;
}

.formularios__tr:last-child .formularios__td {
  border-bottom: none;
}

.formularios__loading--table {
  padding: 0.75rem 0;
}

.formularios__skeleton-row--table {
  display: flex;
  gap: 0.5rem;
  padding: 0.5rem 0;
  border-bottom: none;
}

.formularios__skeleton-cell {
  flex: 1;
  min-width: 4rem;
  height: 2rem;
  background: #252525;
  border-radius: 6px;
  animation: formularios-pulse 1.5s ease-in-out infinite;
}

@keyframes formularios-pulse {
  0%, 100% { opacity: 0.5; }
  50% { opacity: 1; }
}

.formularios__paginacion {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #252525;
}

.formularios__page-btn {
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

.formularios__page-btn:hover:not(:disabled) {
  background: color-mix(in srgb, var(--color-success-500) 10%, transparent);
  border-color: var(--color-success-500);
}

.formularios__page-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.formularios__page-info {
  font-size: 0.8125rem;
  color: var(--color-label-secondary);
}

.formularios__th--acciones,
.formularios__td--acciones {
  white-space: nowrap;
  width: 1%;
}

.formularios__td--acciones {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.formularios__action-btn {
  padding: 0.375rem 0.625rem;
  font-size: 0.75rem;
  font-weight: 500;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  transition: opacity 0.2s;
}

.formularios__action-btn--edit {
  color: var(--color-success-500);
  background: color-mix(in srgb, var(--color-success-500) 15%, transparent);
}

.formularios__action-btn--edit:hover {
  background: color-mix(in srgb, var(--color-success-500) 25%, transparent);
}

.formularios__action-btn--delete {
  color: var(--color-danger-500);
  background: color-mix(in srgb, var(--color-danger-500) 15%, transparent);
}

.formularios__action-btn--delete:hover {
  background: color-mix(in srgb, var(--color-danger-500) 25%, transparent);
}

.formularios__sheet-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 90;
  display: flex;
  align-items: flex-end;
  justify-content: center;
}

.formularios__sheet {
  background: #161616;
  border-radius: 16px 16px 0 0;
  padding: 1.25rem;
  padding-bottom: max(1.25rem, env(safe-area-inset-bottom));
  width: 100%;
  max-width: 32rem;
  border: 1px solid #252525;
  border-bottom: none;
}

.formularios__sheet-title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--color-label-tertiary);
  margin: 0 0 0.25rem;
}

.formularios__sheet-subtitle {
  font-size: 0.8125rem;
  color: var(--color-label-secondary);
  margin: 0 0 1rem;
}

.formularios__sheet-actions {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.formularios__sheet-btn {
  padding: 0.75rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: 12px;
  border: none;
  cursor: pointer;
  transition: background 0.2s;
  text-align: center;
}

.formularios__sheet-btn--edit {
  background: color-mix(in srgb, var(--color-success-500) 15%, transparent);
  color: var(--color-success-500);
}

.formularios__sheet-btn--edit:hover {
  background: color-mix(in srgb, var(--color-success-500) 25%, transparent);
}

.formularios__sheet-btn--delete {
  background: color-mix(in srgb, var(--color-danger-500) 15%, transparent);
  color: var(--color-danger-500);
}

.formularios__sheet-btn--delete:hover {
  background: color-mix(in srgb, var(--color-danger-500) 25%, transparent);
}

.formularios__sheet-btn--cancel {
  background: #252525;
  color: var(--color-label-secondary);
}

.formularios__sheet-btn--cancel:hover {
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
.sheet-enter-active .formularios__sheet,
.sheet-leave-active .formularios__sheet {
  transition: transform 0.25s ease;
}
.sheet-enter-from .formularios__sheet,
.sheet-leave-to .formularios__sheet {
  transform: translateY(100%);
}
</style>

