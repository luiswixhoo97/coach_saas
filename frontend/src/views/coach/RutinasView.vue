<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import Swal from 'sweetalert2'
import { useApi } from '@/composables/useApi'
import BaseEmptyState from '@/components/ui/BaseEmptyState.vue'
import BaseTable from '@/components/ui/BaseTable.vue'
import RutinaDetalleModal from '@/components/coach/RutinaDetalleModal.vue'
import RutinaCrearModal from '@/components/coach/RutinaCrearModal.vue'

const { get, post, del, cargando } = useApi()
const rutinas = ref([])
const meta = ref({ total: 0, por_pagina: 15, pagina_actual: 1, ultima_pagina: 1 })
const error = ref('')
const nivelFiltro = ref('')
const objetivoFiltro = ref('')

// Modal crear rutina (o editar si rutinaParaEditar está definida)
const showCrearModal = ref(false)
const rutinaParaEditar = ref(null)

// Modal detalle / editar: rutina con ejercicios (series, repeticiones, bloque)
const showDetalleModal = ref(false)
const rutinaDetalle = ref(null)
const cargandoDetalle = ref(false)
const errorDetalle = ref('')

const NIVELES = [
  { value: 'principiante', label: 'Principiante' },
  { value: 'intermedio', label: 'Intermedio' },
  { value: 'avanzado', label: 'Avanzado' }
]

const paginaActual = computed(() => meta.value.pagina_actual ?? 1)
const totalPaginas = computed(() => meta.value.ultima_pagina ?? 1)
const hayMas = computed(() => paginaActual.value < totalPaginas.value)
const isMobile = ref(false)
const MOBILE_BREAKPOINT = 768

/** Valor a pasar al modal: null = loading, { error } = error, objeto = rutina cargada */
const rutinaParaModal = computed(() => {
  if (cargandoDetalle.value) return null
  if (errorDetalle.value) return { error: errorDetalle.value }
  return rutinaDetalle.value
})

function labelNivel(value) {
  const n = NIVELES.find(x => x.value === value)
  return n ? n.label : (value || '—')
}

async function cargarRutinas(pagina = 1) {
  try {
    error.value = ''
    const params = new URLSearchParams()
    if (pagina > 1) params.set('page', pagina)
    if (nivelFiltro.value) params.set('nivel', nivelFiltro.value)
    if (objetivoFiltro.value.trim()) params.set('objetivo', objetivoFiltro.value.trim())
    const query = params.toString() ? `?${params.toString()}` : ''
    const res = await get(`/coach/rutinas${query}`)
    rutinas.value = res.data?.datos ?? res.datos ?? []
    meta.value = res.meta ?? meta.value
  } catch (e) {
    error.value = e.message || 'No se pudo cargar la lista de rutinas.'
    rutinas.value = []
  }
}

function irPagina(pagina) {
  if (pagina < 1 || pagina > totalPaginas.value) return
  cargarRutinas(pagina)
}

function checkMobile() {
  isMobile.value = window.innerWidth < MOBILE_BREAKPOINT
}

function abrirCrear() {
  rutinaParaEditar.value = null
  showCrearModal.value = true
}

function cerrarCrearModal() {
  showCrearModal.value = false
  rutinaParaEditar.value = null
}

function onRutinaCreada() {
  cargarRutinas(1)
}

async function abrirDetalle(r) {
  showDetalleModal.value = true
  rutinaDetalle.value = null
  errorDetalle.value = ''
  cargandoDetalle.value = true
  try {
    const res = await get(`/coach/rutinas/${r.id}`)
    rutinaDetalle.value = res.datos ?? res.data ?? res
  } catch (e) {
    errorDetalle.value = e.message || 'No se pudo cargar la rutina.'
  } finally {
    cargandoDetalle.value = false
  }
}

function cerrarDetalleModal() {
  showDetalleModal.value = false
  rutinaDetalle.value = null
  errorDetalle.value = ''
}

function onEditarRutina() {
  if (!rutinaDetalle.value?.id) return
  rutinaParaEditar.value = { ...rutinaDetalle.value }
  cerrarDetalleModal()
  showCrearModal.value = true
}

async function onClonarRutina() {
  const id = rutinaDetalle.value?.id
  if (!id) return
  try {
    const res = await post(`/coach/rutinas/${id}/duplicar`, {})
    const nuevaRutina = res.datos ?? res.data?.datos ?? res
    cerrarDetalleModal()
    rutinaParaEditar.value = nuevaRutina && typeof nuevaRutina === 'object' ? { ...nuevaRutina } : nuevaRutina
    showCrearModal.value = true
    cargarRutinas(1)
  } catch (e) {
    errorDetalle.value = e.message || 'No se pudo clonar la rutina.'
  }
}

async function onEliminarRutina() {
  const id = rutinaDetalle.value?.id
  const nombre = rutinaDetalle.value?.nombre || 'esta rutina'
  if (!id) return
  const result = await Swal.fire({
    title: '¿Eliminar rutina?',
    text: `Se eliminará "${nombre}". Esta acción no se puede deshacer.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#EF5C5C',
    cancelButtonColor: '#697586',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  })
  if (!result.isConfirmed) return
  try {
    await del(`/coach/rutinas/${id}`)
    await Swal.fire({
      title: 'Eliminada',
      text: 'La rutina se ha eliminado correctamente.',
      icon: 'success',
      confirmButtonColor: '#00D261'
    })
    cargarRutinas(1)
    cerrarDetalleModal()
  } catch (e) {
    errorDetalle.value = e.message || 'No se pudo eliminar la rutina.'
  }
}

onMounted(() => {
  cargarRutinas(1)
  checkMobile()
  window.addEventListener('resize', checkMobile)
})

onUnmounted(() => {
  window.removeEventListener('resize', checkMobile)
})

watch([nivelFiltro, objetivoFiltro], () => cargarRutinas(1))
</script>

<template>
  <div class="rutinas">
    <div class="rutinas__wrap">
      <div v-if="error" class="rutinas__alert">{{ error }}</div>

      <section class="rutinas__section">
        <div class="rutinas__section-header">
          <div class="rutinas__section-title-row">
            <h2 class="rutinas__section-title">Rutinas</h2>
            <span class="rutinas__section-meta" v-if="meta.total != null">
              {{ meta.total }} {{ meta.total === 1 ? 'rutina' : 'rutinas' }}
            </span>
          </div>
          <button type="button" class="rutinas__btn-add" @click="abrirCrear" aria-label="Crear rutina">
            Crear rutina
          </button>
        </div>
        <div class="rutinas__filtros">
          <div class="rutinas__filtro-field">
            <label for="rutinas-nivel" class="rutinas__filtro-label">Nivel</label>
            <select id="rutinas-nivel" v-model="nivelFiltro" class="rutinas__select" aria-label="Filtrar por nivel">
              <option value="">Todos</option>
              <option v-for="n in NIVELES" :key="n.value" :value="n.value">{{ n.label }}</option>
            </select>
          </div>
          <div class="rutinas__filtro-field rutinas__filtro-field--objetivo">
            <label for="rutinas-objetivo" class="rutinas__filtro-label">Objetivo</label>
            <input
              id="rutinas-objetivo"
              v-model="objetivoFiltro"
              type="text"
              class="rutinas__input"
              placeholder="Filtrar por objetivo..."
              aria-label="Filtrar por objetivo"
            />
          </div>
        </div>

        <!-- Desktop: tabla -->
        <template v-if="!isMobile">
          <div v-if="cargando && !rutinas.length" class="rutinas__loading rutinas__loading--table">
            <div v-for="i in 4" :key="i" class="rutinas__skeleton-row rutinas__skeleton-row--table">
              <div class="rutinas__skeleton-cell" />
              <div class="rutinas__skeleton-cell" />
              <div class="rutinas__skeleton-cell" />
              <div class="rutinas__skeleton-cell" />
              <div class="rutinas__skeleton-cell" />
              <div class="rutinas__skeleton-cell" />
            </div>
          </div>
          <div v-else-if="!rutinas.length" class="rutinas__empty">
            <BaseEmptyState
              titulo="No hay rutinas"
              descripcion="Crea una rutina para asignarla a tus clientes."
            />
          </div>
          <div v-else class="rutinas__table-wrap">
            <table class="rutinas__table">
              <thead class="rutinas__thead">
                <tr>
                  <th class="rutinas__th">Nombre</th>
                  <th class="rutinas__th">Nivel</th>
                  <th class="rutinas__th">Objetivo</th>
                  <th class="rutinas__th">Ejercicios</th>
                  <th class="rutinas__th">Clientes</th>
                  <th class="rutinas__th rutinas__th--acciones">Acciones</th>
                </tr>
              </thead>
              <tbody class="rutinas__tbody">
                <tr v-for="r in rutinas" :key="r.id" class="rutinas__tr">
                  <td class="rutinas__td">{{ r.nombre || '—' }}</td>
                  <td class="rutinas__td">{{ labelNivel(r.nivel) }}</td>
                  <td class="rutinas__td">{{ r.objetivo || '—' }}</td>
                  <td class="rutinas__td">{{ r.ejercicios_count ?? 0 }}</td>
                  <td class="rutinas__td">{{ r.clientes_asignados_count ?? 0 }}</td>
                  <td class="rutinas__td rutinas__td--acciones">
                    <button type="button" class="rutinas__action-btn rutinas__action-btn--ver" @click="abrirDetalle(r)" aria-label="Ver rutina">Ver</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>

        <!-- Móvil: cards -->
        <BaseTable v-else :items="rutinas" key-field="id" :loading="cargando">
          <template #header />
          <template #loading>
            <div class="rutinas__loading">
              <div v-for="i in 4" :key="i" class="rutinas__skeleton-row">
                <div class="rutinas__skeleton-text" />
                <div class="rutinas__skeleton-text" />
              </div>
            </div>
          </template>
          <template #empty>
            <div class="rutinas__empty">
              <BaseEmptyState
                titulo="No hay rutinas"
                descripcion="Crea una rutina para asignarla a tus clientes."
              />
            </div>
          </template>
          <template #row="{ item: r }">
            <div class="rutinas__link" @click="abrirDetalle(r)">
              <div class="rutinas__info">
                <span class="rutinas__nombre">{{ r.nombre || '—' }}</span>
                <span class="rutinas__meta">{{ labelNivel(r.nivel) }} · {{ r.objetivo || '—' }}</span>
              </div>
              <div class="rutinas__badges">
                <span class="rutinas__badge">{{ r.ejercicios_count ?? 0 }} ejercicios</span>
                <span class="rutinas__badge">{{ r.clientes_asignados_count ?? 0 }} clientes</span>
              </div>
            </div>
          </template>
        </BaseTable>

        <div v-if="totalPaginas > 1" class="rutinas__paginacion">
          <button
            type="button"
            class="rutinas__page-btn"
            :disabled="paginaActual <= 1"
            @click="irPagina(paginaActual - 1)"
          >
            Anterior
          </button>
          <span class="rutinas__page-info">
            {{ paginaActual }} / {{ totalPaginas }}
          </span>
          <button
            type="button"
            class="rutinas__page-btn"
            :disabled="!hayMas"
            @click="irPagina(paginaActual + 1)"
          >
            Siguiente
          </button>
        </div>
      </section>

      <!-- Modal detalle rutina (mismo patrón que ClienteDetalleModal) -->
      <RutinaDetalleModal
        v-if="showDetalleModal"
        :rutina="rutinaParaModal"
        @close="cerrarDetalleModal"
        @edit="onEditarRutina"
        @clonar="onClonarRutina"
        @eliminar="onEliminarRutina"
      />

      <!-- Modal crear rutina (wizard 2 pasos) -->
      <RutinaCrearModal
        v-if="showCrearModal"
        :rutina-para-editar="rutinaParaEditar"
        @close="cerrarCrearModal"
        @created="onRutinaCreada"
      />
    </div>
  </div>
</template>

<style scoped>
.rutinas {
  min-height: 100vh;
  min-height: 100dvh;
  background: #0a0a0a;
  padding: 1rem;
  padding-top: max(1rem, env(safe-area-inset-top));
  padding-bottom: max(2rem, env(safe-area-inset-bottom));
}

.rutinas__wrap {
  max-width: 32rem;
  margin: 0 auto;
}

@media (min-width: 768px) {
  .rutinas__wrap {
    max-width: 100%;
    padding: 0 0.5rem;
  }
}

.rutinas__alert {
  background: color-mix(in srgb, var(--color-danger-500) 12%, transparent);
  border: 1px solid color-mix(in srgb, var(--color-danger-500) 35%, transparent);
  color: var(--color-danger-500);
  padding: 0.875rem 1rem;
  border-radius: 16px;
  font-size: 0.875rem;
  margin-bottom: 1rem;
}

.rutinas__section {
  background: #161616;
  border-radius: 16px;
  padding: 1rem;
}

.rutinas__section-header {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}

.rutinas__section-title-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.rutinas__section-title {
  font-size: 0.9375rem;
  font-weight: 600;
  color: var(--color-label-tertiary);
  margin: 0;
}

.rutinas__section-meta {
  font-size: 0.75rem;
  color: var(--color-label-secondary);
}

.rutinas__btn-add {
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

.rutinas__btn-add:hover {
  background: color-mix(in srgb, var(--color-success-500) 25%, transparent);
  border-color: var(--color-success-500);
}

.rutinas__filtros {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.rutinas__filtro-field {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.rutinas__filtro-field--objetivo {
  flex: 1;
  min-width: 0;
}

.rutinas__filtro-label {
  font-size: 0.75rem;
  font-weight: 500;
  color: var(--color-label-secondary);
}

.rutinas__input {
  flex: 1;
  min-width: 0;
  background: #1e1e1e;
  border: 1px solid #252525;
  border-radius: 12px;
  padding: 0.625rem 0.875rem;
  font-size: 0.875rem;
  color: var(--color-label-tertiary);
}

.rutinas__input::placeholder {
  color: var(--color-label-secondary);
}

.rutinas__input:focus,
.rutinas__input:focus-visible {
  outline: none;
  border-color: var(--color-success-500);
  box-shadow: 0 0 0 2px color-mix(in srgb, var(--color-success-500) 35%, transparent);
}

.rutinas__input:focus-visible {
  --tw-ring-color: var(--color-success-500);
}

.rutinas__select {
  background: #1e1e1e;
  border: 1px solid #252525;
  border-radius: 12px;
  padding: 0.625rem 0.875rem;
  font-size: 0.875rem;
  color: var(--color-label-tertiary);
  accent-color: var(--color-success-500);
}

.rutinas__select:focus,
.rutinas__select:focus-visible {
  outline: none;
  border-color: var(--color-success-500);
  box-shadow: 0 0 0 2px color-mix(in srgb, var(--color-success-500) 35%, transparent);
}

.rutinas__select:focus-visible {
  --tw-ring-color: var(--color-success-500);
}

.rutinas__select option {
  background: #1e1e1e;
  color: var(--color-label-tertiary);
}

.rutinas__select option:checked {
  background: var(--color-success-500);
  color: #0a0a0a;
}

.rutinas__link {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.875rem;
  padding: 0.875rem 1rem;
  color: inherit;
  transition: background 0.2s;
  cursor: pointer;
}

.rutinas__link:hover {
  background: rgba(255, 255, 255, 0.03);
}

.rutinas__info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
}

.rutinas__nombre {
  font-size: 0.9375rem;
  font-weight: 500;
  color: var(--color-label-tertiary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.rutinas__meta {
  font-size: 0.75rem;
  color: var(--color-label-secondary);
}

.rutinas__badges {
  display: flex;
  flex-wrap: wrap;
  gap: 0.375rem;
  flex-shrink: 0;
}

.rutinas__badge {
  font-size: 0.6875rem;
  font-weight: 500;
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
  background: color-mix(in srgb, var(--color-label-secondary) 20%, transparent);
  color: var(--color-label-secondary);
  flex-shrink: 0;
}

.rutinas__empty {
  padding: 2rem 0;
}

.rutinas__empty :deep(h3) {
  color: var(--color-label-tertiary);
}

.rutinas__empty :deep(p) {
  color: var(--color-label-secondary);
}

.rutinas__loading {
  padding: 0.5rem 0;
}

.rutinas__skeleton-row {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  padding: 0.875rem 0;
  border-bottom: 1px solid #252525;
}

.rutinas__skeleton-text {
  flex: 1;
  height: 1rem;
  border-radius: 6px;
  background: #252525;
  animation: rutinas-pulse 1.5s ease-in-out infinite;
}

.rutinas__table-wrap {
  overflow-x: auto;
  border-radius: 12px;
  border: 1px solid #252525;
}

.rutinas__table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.8125rem;
}

.rutinas__thead {
  background: #1e1e1e;
}

.rutinas__th {
  text-align: left;
  padding: 0.75rem 1rem;
  font-weight: 600;
  color: var(--color-label-secondary);
  white-space: nowrap;
  border-bottom: 1px solid #252525;
}

.rutinas__tbody {
  background: #161616;
}

.rutinas__tr {
  border-bottom: 1px solid #252525;
  transition: background 0.2s;
}

.rutinas__tr:last-child {
  border-bottom: none;
}

.rutinas__tr:hover {
  background: rgba(255, 255, 255, 0.02);
}

.rutinas__td {
  padding: 0.75rem 1rem;
  color: var(--color-label-tertiary);
  border-bottom: 1px solid #252525;
  vertical-align: middle;
}

.rutinas__tr:last-child .rutinas__td {
  border-bottom: none;
}

.rutinas__th--acciones,
.rutinas__td--acciones {
  white-space: nowrap;
  width: 1%;
}

.rutinas__action-btn {
  padding: 0.375rem 0.625rem;
  font-size: 0.75rem;
  font-weight: 500;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  transition: opacity 0.2s;
  color: var(--color-success-500);
  background: color-mix(in srgb, var(--color-success-500) 15%, transparent);
}

.rutinas__action-btn:hover {
  background: color-mix(in srgb, var(--color-success-500) 25%, transparent);
}

.rutinas__loading--table {
  padding: 0.75rem 0;
}

.rutinas__skeleton-row--table {
  display: flex;
  gap: 0.5rem;
  padding: 0.5rem 0;
}

.rutinas__skeleton-cell {
  flex: 1;
  min-width: 4rem;
  height: 2rem;
  background: #252525;
  border-radius: 6px;
  animation: rutinas-pulse 1.5s ease-in-out infinite;
}

@keyframes rutinas-pulse {
  0%, 100% { opacity: 0.5; }
  50% { opacity: 1; }
}

.rutinas__paginacion {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #252525;
}

.rutinas__page-btn {
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

.rutinas__page-btn:hover:not(:disabled) {
  background: color-mix(in srgb, var(--color-success-500) 10%, transparent);
  border-color: var(--color-success-500);
}

.rutinas__page-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.rutinas__page-info {
  font-size: 0.8125rem;
  color: var(--color-label-secondary);
}
</style>
