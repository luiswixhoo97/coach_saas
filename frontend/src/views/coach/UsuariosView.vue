<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useApi } from '@/composables/useApi'
import BaseEmptyState from '@/components/ui/BaseEmptyState.vue'
import BaseTable from '@/components/ui/BaseTable.vue'
import ClienteDetalleModal from '@/components/coach/ClienteDetalleModal.vue'
import AsignarRutinaModal from '@/components/coach/AsignarRutinaModal.vue'
import SubirDietaModal from '@/components/coach/SubirDietaModal.vue'

const { get, cargando } = useApi()
const clientes = ref([])
const meta = ref({ total: 0, por_pagina: 15, pagina_actual: 1, ultima_pagina: 1 })
const error = ref('')
const buscar = ref('')
const filtroActivo = ref('') // '' | 'activos' | 'inactivos'

const modalDetalle = ref(false)
const detalleCliente = ref(null)
const cargandoDetalle = ref(false)
const isMobile = ref(false)
const MOBILE_BREAKPOINT = 768

// Multi-select
const selectedClientes = ref(new Set())
const showAsignarRutinaModal = ref(false)
const showSubirDietaModal = ref(false)
const clienteParaAsignar = ref(null)

const paginaActual = computed(() => meta.value.pagina_actual ?? 1)
const totalPaginas = computed(() => meta.value.ultima_pagina ?? 1)
const hayMas = computed(() => paginaActual.value < totalPaginas.value)

function nombreCompleto(c) {
  if (!c) return ''
  const partes = [c.nombre, c.apellido_paterno, c.apellido_materno].filter(Boolean)
  return partes.join(' ') || c.email || '—'
}

function iniciales(c) {
  const nombre = nombreCompleto(c) || c?.email || '?'
  const partes = nombre.trim().split(/\s+/)
  if (partes.length >= 2) return (partes[0][0] + partes[partes.length - 1][0]).toUpperCase()
  return nombre.slice(0, 2).toUpperCase()
}

async function cargarClientes(pagina = 1) {
  try {
    error.value = ''
    const params = new URLSearchParams()
    if (pagina > 1) params.set('page', pagina)
    if (buscar.value.trim()) params.set('buscar', buscar.value.trim())
    if (filtroActivo.value === 'activos') params.set('activo', '1')
    if (filtroActivo.value === 'inactivos') params.set('activo', '0')
    const query = params.toString() ? `?${params.toString()}` : ''
    const res = await get(`/coach/clientes${query}`)
    clientes.value = res.data?.datos ?? res.datos ?? []
    meta.value = res.meta ?? meta.value
  } catch (e) {
    error.value = e.message || 'No se pudo cargar la lista de usuarios.'
    clientes.value = []
  }
}

function irPagina(pagina) {
  if (pagina < 1 || pagina > totalPaginas.value) return
  cargarClientes(pagina)
}

function checkMobile() {
  isMobile.value = window.innerWidth < MOBILE_BREAKPOINT
}

async function abrirDetalle(c) {
  if (!isMobile.value) return
  modalDetalle.value = true
  detalleCliente.value = null
  cargandoDetalle.value = true
  try {
    const res = await get(`/coach/clientes/${c.id}`)
    detalleCliente.value = res.datos ?? res.data ?? res
  } catch (e) {
    detalleCliente.value = { error: e.message || 'No se pudo cargar el detalle.' }
  } finally {
    cargandoDetalle.value = false
  }
}

function cerrarModal() {
  modalDetalle.value = false
  detalleCliente.value = null
}

function toggleSeleccion(clienteId) {
  if (selectedClientes.value.has(clienteId)) {
    selectedClientes.value.delete(clienteId)
  } else {
    selectedClientes.value.add(clienteId)
  }
}

function toggleSeleccionTodos() {
  if (selectedClientes.value.size === clientes.value.length) {
    selectedClientes.value.clear()
  } else {
    clientes.value.forEach(c => selectedClientes.value.add(c.id))
  }
}

const haySeleccionados = computed(() => selectedClientes.value.size > 0)
const clientesSeleccionados = computed(() => {
  return clientes.value.filter(c => selectedClientes.value.has(c.id))
})

function abrirAsignarRutina(cliente = null) {
  clienteParaAsignar.value = cliente
  showAsignarRutinaModal.value = true
}

function abrirSubirDieta(cliente = null) {
  clienteParaAsignar.value = cliente
  showSubirDietaModal.value = true
}

function cerrarAsignarRutinaModal() {
  showAsignarRutinaModal.value = false
  clienteParaAsignar.value = null
  selectedClientes.value.clear()
}

function cerrarSubirDietaModal() {
  showSubirDietaModal.value = false
  clienteParaAsignar.value = null
  selectedClientes.value.clear()
}

async function onRutinaAsignada() {
  await cargarClientes(paginaActual.value)
  if (modalDetalle.value && detalleCliente.value) {
    const res = await get(`/coach/clientes/${detalleCliente.value.id}`)
    detalleCliente.value = res.datos ?? res.data ?? res
  }
}

async function onDietaSubida() {
  await cargarClientes(paginaActual.value)
  if (modalDetalle.value && detalleCliente.value) {
    const res = await get(`/coach/clientes/${detalleCliente.value.id}`)
    detalleCliente.value = res.datos ?? res.data ?? res
  }
}

async function onDietaEliminada() {
  await cargarClientes(paginaActual.value)
  if (modalDetalle.value && detalleCliente.value) {
    const res = await get(`/coach/clientes/${detalleCliente.value.id}`)
    detalleCliente.value = res.datos ?? res.data ?? res
  }
}

onMounted(() => {
  cargarClientes(1)
  checkMobile()
  window.addEventListener('resize', checkMobile)
})

onUnmounted(() => {
  window.removeEventListener('resize', checkMobile)
})

watch([buscar, filtroActivo], () => cargarClientes(1))
</script>

<template>
  <div class="usuarios">
    <div class="usuarios__wrap">
      <!-- Error -->
      <div v-if="error" class="usuarios__alert">{{ error }}</div>

      <!-- Sección Usuarios -->
      <section class="usuarios__section">
        <!-- Header y filtros (común) -->
        <div class="usuarios__section-header">
          <h2 class="usuarios__section-title">Usuarios</h2>
          <span class="usuarios__section-meta" v-if="meta.total != null">
            {{ meta.total }} {{ meta.total === 1 ? 'cliente' : 'clientes' }}
          </span>
        </div>
        <div class="usuarios__filtros">
          <input
            v-model="buscar"
            type="search"
            class="usuarios__input"
            placeholder="Buscar por email, nombre o apellido..."
            aria-label="Buscar"
          />
          <select v-model="filtroActivo" class="usuarios__select" aria-label="Estado">
            <option value="">Todos</option>
            <option value="activos">Activos</option>
            <option value="inactivos">Inactivos</option>
          </select>
        </div>

        <!-- Acciones masivas -->
        <div v-if="haySeleccionados" class="usuarios__bulk-actions">
          <span class="usuarios__bulk-count">
            {{ selectedClientes.size }} {{ selectedClientes.size === 1 ? 'cliente seleccionado' : 'clientes seleccionados' }}
          </span>
          <div class="usuarios__bulk-buttons">
            <button
              type="button"
              class="usuarios__bulk-btn"
              @click="abrirAsignarRutina()"
            >
              Asignar rutina
            </button>
            <button
              type="button"
              class="usuarios__bulk-btn"
              @click="abrirSubirDieta()"
            >
              Subir dieta
            </button>
            <button
              type="button"
              class="usuarios__bulk-btn usuarios__bulk-btn--clear"
              @click="selectedClientes.clear()"
            >
              Limpiar
            </button>
          </div>
        </div>

        <!-- Desktop: tabla normal -->
        <template v-if="!isMobile">
          <div v-if="cargando && !clientes.length" class="usuarios__loading usuarios__loading--table">
            <div v-for="i in 4" :key="i" class="usuarios__skeleton-row usuarios__skeleton-row--table">
              <div class="usuarios__skeleton-cell" />
              <div class="usuarios__skeleton-cell" />
              <div class="usuarios__skeleton-cell" />
            </div>
          </div>
          <div v-else-if="!clientes.length" class="usuarios__empty">
            <BaseEmptyState
              titulo="No hay usuarios"
              descripcion="Los clientes que agregues aparecerán aquí."
            />
          </div>
          <div v-else class="usuarios__table-wrap">
            <table class="usuarios__table">
              <thead class="usuarios__thead">
                <tr>
                  <th class="usuarios__th usuarios__th--checkbox">
                    <input
                      type="checkbox"
                      :checked="selectedClientes.size === clientes.length && clientes.length > 0"
                      @change="toggleSeleccionTodos"
                      class="usuarios__checkbox"
                    />
                  </th>
                  <th class="usuarios__th">Nombre</th>
                  <th class="usuarios__th">Email</th>
                  <th class="usuarios__th">Estado</th>
                  <th class="usuarios__th">Edad</th>
                  <th class="usuarios__th">Altura</th>
                  <th class="usuarios__th">Objetivo</th>
                  <th class="usuarios__th">Suscripción</th>
                  <th class="usuarios__th">Tiene dieta</th>
                </tr>
              </thead>
              <tbody class="usuarios__tbody">
                <tr v-for="c in clientes" :key="c.id" class="usuarios__tr">
                  <td class="usuarios__td usuarios__td--checkbox">
                    <input
                      type="checkbox"
                      :checked="selectedClientes.has(c.id)"
                      @change="toggleSeleccion(c.id)"
                      class="usuarios__checkbox"
                      @click.stop
                    />
                  </td>
                  <td class="usuarios__td">{{ nombreCompleto(c) }}</td>
                  <td class="usuarios__td usuarios__td--email">{{ c.email || '—' }}</td>
                  <td class="usuarios__td">
                    <span
                      class="usuarios__badge"
                      :class="{ 'usuarios__badge--active': c.activo }"
                    >
                      {{ c.activo ? 'Activo' : 'Inactivo' }}
                    </span>
                  </td>
                  <td class="usuarios__td">{{ c.edad != null ? `${c.edad} años` : '—' }}</td>
                  <td class="usuarios__td">{{ c.altura ? `${c.altura} cm` : '—' }}</td>
                  <td class="usuarios__td usuarios__td--objetivo">{{ c.objetivo || '—' }}</td>
                  <td class="usuarios__td">
                    <template v-if="c.suscripcion_activa">
                      {{ c.suscripcion_activa.fecha_fin }}
                      <span v-if="c.suscripcion_activa.dias_restantes != null" class="usuarios__td-hint">
                        ({{ c.suscripcion_activa.dias_restantes }} d)
                      </span>
                    </template>
                    <span v-else>—</span>
                  </td>
                  <td class="usuarios__td">
                    <span
                      class="usuarios__badge"
                      :class="{ 'usuarios__badge--active': c.tiene_dieta }"
                    >
                      {{ c.tiene_dieta ? 'Sí' : 'No' }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>

        <!-- Móvil: cards (BaseTable) -->
        <BaseTable v-else :items="clientes" key-field="id" :loading="cargando">
          <template #header />
          <template #loading>
            <div class="usuarios__loading">
              <div v-for="i in 4" :key="i" class="usuarios__skeleton-row">
                <div class="usuarios__skeleton-avatar" />
                <div class="usuarios__skeleton-text" />
              </div>
            </div>
          </template>
          <template #empty>
            <div class="usuarios__empty">
              <BaseEmptyState
                titulo="No hay usuarios"
                descripcion="Los clientes que agregues aparecerán aquí."
              />
            </div>
          </template>
          <template #row="{ item: c }">
            <div
              class="usuarios__link"
              :class="{ 'usuarios__link--clickable': isMobile }"
              role="button"
              tabindex="0"
              @click="abrirDetalle(c)"
              @keydown.enter="abrirDetalle(c)"
              @keydown.space.prevent="abrirDetalle(c)"
            >
              <div class="usuarios__avatar">{{ iniciales(c) }}</div>
              <div class="usuarios__info">
                <span class="usuarios__nombre">{{ nombreCompleto(c) }}</span>
                <span class="usuarios__email">{{ c.email || '—' }}</span>
              </div>
              <span
                class="usuarios__badge"
                :class="{ 'usuarios__badge--active': c.activo }"
              >
                {{ c.activo ? 'Activo' : 'Inactivo' }}
              </span>
            </div>
          </template>
        </BaseTable>

        <!-- Modal detalle cliente (móvil) -->
        <ClienteDetalleModal
          v-if="modalDetalle"
          :cliente="detalleCliente"
          @close="cerrarModal"
          @asignar-rutina="abrirAsignarRutina"
          @subir-dieta="abrirSubirDieta"
          @dieta-eliminada="onDietaEliminada"
        />

        <!-- Modal asignar rutina -->
        <AsignarRutinaModal
          v-if="showAsignarRutinaModal"
          :cliente="clienteParaAsignar"
          :clientes="clienteParaAsignar ? [] : clientesSeleccionados"
          @close="cerrarAsignarRutinaModal"
          @asignada="onRutinaAsignada"
        />

        <!-- Modal subir dieta -->
        <SubirDietaModal
          v-if="showSubirDietaModal"
          :cliente="clienteParaAsignar"
          :clientes="clienteParaAsignar ? [] : clientesSeleccionados"
          @close="cerrarSubirDietaModal"
          @subida="onDietaSubida"
        />

        <!-- Paginación -->
        <div v-if="totalPaginas > 1" class="usuarios__paginacion">
          <button
            type="button"
            class="usuarios__page-btn"
            :disabled="paginaActual <= 1"
            @click="irPagina(paginaActual - 1)"
          >
            Anterior
          </button>
          <span class="usuarios__page-info">
            {{ paginaActual }} / {{ totalPaginas }}
          </span>
          <button
            type="button"
            class="usuarios__page-btn"
            :disabled="!hayMas"
            @click="irPagina(paginaActual + 1)"
          >
            Siguiente
          </button>
        </div>
      </section>
    </div>
  </div>
</template>

<style scoped>
.usuarios {
  min-height: 100vh;
  min-height: 100dvh;
  background: #0a0a0a;
  padding: 1rem;
  padding-top: max(1rem, env(safe-area-inset-top));
  padding-bottom: max(2rem, env(safe-area-inset-bottom));
}

.usuarios__wrap {
  max-width: 32rem;
  margin: 0 auto;
}

@media (min-width: 768px) {
  .usuarios__wrap {
    max-width: 100%;
    padding: 0 0.5rem;
  }
}

.usuarios__alert {
  background: rgba(239, 92, 92, 0.12);
  border: 1px solid rgba(239, 92, 92, 0.3);
  color: #EF5C5C;
  padding: 0.875rem 1rem;
  border-radius: 16px;
  font-size: 0.875rem;
  margin-bottom: 1rem;
}

.usuarios__section {
  background: #161616;
  border-radius: 16px;
  padding: 1rem;
}

.usuarios__section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.75rem;
}

.usuarios__section-title {
  font-size: 0.9375rem;
  font-weight: 600;
  color: #fff;
  margin: 0;
}

.usuarios__section-meta {
  font-size: 0.75rem;
  color: #697586;
}

.usuarios__filtros {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.usuarios__input {
  flex: 1;
  min-width: 0;
  background: #1e1e1e;
  border: 1px solid #252525;
  border-radius: 12px;
  padding: 0.625rem 0.875rem;
  font-size: 0.875rem;
  color: var(--color-label-tertiary);
}

.usuarios__input::placeholder {
  color: var(--color-label-secondary);
}

.usuarios__input:focus,
.usuarios__input:focus-visible {
  outline: none;
  border-color: var(--color-success-500);
  box-shadow: 0 0 0 2px color-mix(in srgb, var(--color-success-500) 35%, transparent);
}

.usuarios__input:focus-visible {
  --tw-ring-color: var(--color-success-500);
}

.usuarios__select {
  background: #1e1e1e;
  border: 1px solid #252525;
  border-radius: 12px;
  padding: 0.625rem 0.875rem;
  font-size: 0.875rem;
  color: var(--color-label-tertiary);
  accent-color: var(--color-success-500);
}

.usuarios__select:focus,
.usuarios__select:focus-visible {
  outline: none;
  border-color: var(--color-success-500);
  box-shadow: 0 0 0 2px color-mix(in srgb, var(--color-success-500) 35%, transparent);
}

.usuarios__select:focus-visible {
  --tw-ring-color: var(--color-success-500);
}

.usuarios__select option {
  background: #1e1e1e;
  color: var(--color-label-tertiary);
}

.usuarios__select option:checked {
  background: var(--color-success-500);
  color: #0a0a0a;
}

.usuarios__link {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  padding: 0.875rem 1rem;
  text-decoration: none;
  color: inherit;
  transition: background 0.2s;
}

.usuarios__link:hover {
  background: rgba(255, 255, 255, 0.03);
}

.usuarios__link--clickable {
  cursor: pointer;
}

.usuarios__avatar {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: linear-gradient(135deg, #2970FF 0%, #528BFF 100%);
  color: #fff;
  font-size: 0.875rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.usuarios__info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
}

.usuarios__nombre {
  font-size: 0.9375rem;
  font-weight: 500;
  color: #fff;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.usuarios__email {
  font-size: 0.75rem;
  color: #697586;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.usuarios__badge {
  font-size: 0.6875rem;
  font-weight: 500;
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
  background: rgba(105, 117, 134, 0.2);
  color: #697586;
  text-transform: uppercase;
  letter-spacing: 0.02em;
  flex-shrink: 0;
}

.usuarios__badge--active {
  background: rgba(0, 210, 97, 0.15);
  color: #00D261;
}

.usuarios__empty {
  padding: 2rem 0;
}

.usuarios__empty :deep(.flex) {
  color: #a0a0a0;
}

.usuarios__empty :deep(h3) {
  color: #fff;
}

.usuarios__empty :deep(p) {
  color: #697586;
}

.usuarios__loading {
  padding: 0.5rem 0;
}

/* Tabla desktop */
.usuarios__table-wrap {
  overflow-x: auto;
  border-radius: 12px;
  border: 1px solid #252525;
}

.usuarios__table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.8125rem;
}

.usuarios__thead {
  background: #1e1e1e;
}

.usuarios__th {
  text-align: left;
  padding: 0.75rem 1rem;
  font-weight: 600;
  color: #a0a0a0;
  white-space: nowrap;
  border-bottom: 1px solid #252525;
}

.usuarios__tbody {
  background: #161616;
}

.usuarios__tr {
  border-bottom: 1px solid #252525;
  transition: background 0.2s;
}

.usuarios__tr:last-child {
  border-bottom: none;
}

.usuarios__tr:hover {
  background: rgba(255, 255, 255, 0.02);
}

.usuarios__td {
  padding: 0.75rem 1rem;
  color: #fff;
  border-bottom: 1px solid #252525;
  vertical-align: middle;
}

.usuarios__tr:last-child .usuarios__td {
  border-bottom: none;
}

.usuarios__td--email {
  color: #a0a0a0;
}

.usuarios__td--objetivo {
  max-width: 10rem;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.usuarios__td-hint {
  color: #697586;
  font-size: 0.75rem;
}

.usuarios__loading--table {
  padding: 0.75rem 0;
}

.usuarios__skeleton-row--table {
  display: flex;
  gap: 0.5rem;
  padding: 0.5rem 0;
}

.usuarios__skeleton-cell {
  flex: 1;
  min-width: 4rem;
  height: 2rem;
  background: #252525;
  border-radius: 6px;
  animation: usuarios-pulse 1.5s ease-in-out infinite;
}

.usuarios__skeleton-row {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  padding: 0.875rem 0;
  border-bottom: 1px solid #252525;
}

.usuarios__skeleton-avatar {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: #252525;
  animation: usuarios-pulse 1.5s ease-in-out infinite;
}

.usuarios__skeleton-text {
  flex: 1;
  height: 1rem;
  border-radius: 6px;
  background: #252525;
  animation: usuarios-pulse 1.5s ease-in-out infinite;
}

@keyframes usuarios-pulse {
  0%, 100% { opacity: 0.5; }
  50% { opacity: 1; }
}

.usuarios__paginacion {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #252525;
}

.usuarios__page-btn {
  padding: 0.5rem 0.75rem;
  font-size: 0.8125rem;
  font-weight: 500;
  color: #00D261;
  background: transparent;
  border: 1px solid rgba(0, 210, 97, 0.4);
  border-radius: 12px;
  cursor: pointer;
  transition: background 0.2s, border-color 0.2s;
}

.usuarios__page-btn:hover:not(:disabled) {
  background: rgba(0, 210, 97, 0.1);
  border-color: #00D261;
}

.usuarios__page-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.usuarios__page-info {
  font-size: 0.8125rem;
  color: #697586;
}

.usuarios__bulk-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.75rem 1rem;
  background: rgba(0, 210, 97, 0.1);
  border: 1px solid rgba(0, 210, 97, 0.3);
  border-radius: 8px;
  margin-bottom: 1rem;
}

.usuarios__bulk-count {
  font-size: 0.875rem;
  font-weight: 500;
  color: #00D261;
}

.usuarios__bulk-buttons {
  display: flex;
  gap: 0.5rem;
}

.usuarios__bulk-btn {
  padding: 0.5rem 0.875rem;
  font-size: 0.8125rem;
  font-weight: 500;
  color: #00D261;
  background: transparent;
  border: 1px solid rgba(0, 210, 97, 0.4);
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.usuarios__bulk-btn:hover {
  background: rgba(0, 210, 97, 0.1);
  border-color: #00D261;
}

.usuarios__bulk-btn--clear {
  color: #697586;
  border-color: #252525;
}

.usuarios__bulk-btn--clear:hover {
  background: #1e1e1e;
  border-color: #252525;
}

.usuarios__th--checkbox,
.usuarios__td--checkbox {
  width: 40px;
  text-align: center;
}

.usuarios__checkbox {
  width: 18px;
  height: 18px;
  cursor: pointer;
  accent-color: #00D261;
}
</style>
