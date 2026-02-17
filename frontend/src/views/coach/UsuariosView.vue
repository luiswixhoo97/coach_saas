<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useApi } from '@/composables/useApi'
import BaseEmptyState from '@/components/ui/BaseEmptyState.vue'
import BaseTable from '@/components/ui/BaseTable.vue'
import BaseSwitch from '@/components/ui/BaseSwitch.vue'
import ClienteDetalleModal from '@/components/coach/ClienteDetalleModal.vue'
import AsignarRutinaModal from '@/components/coach/AsignarRutinaModal.vue'
import AsignarRutinaClienteModal from '@/components/coach/AsignarRutinaClienteModal.vue'
import SubirDietaModal from '@/components/coach/SubirDietaModal.vue'
import SubirDietaUsuariosModal from '@/components/coach/SubirDietaUsuariosModal.vue'
import FormularioClienteModal from '@/components/coach/FormularioClienteModal.vue'
import ParametrosClienteModal from '@/components/coach/ParametrosClienteModal.vue'

const { get, put, cargando } = useApi()
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
const showAsignarRutinaClienteModal = ref(false)
const showSubirDietaModal = ref(false)
const showSubirDietaUsuariosModal = ref(false)
const clienteParaAsignar = ref(null)
const clientesProcesando = ref(new Set())
const showFormularioClienteModal = ref(false)
const formularioParaCliente = ref(null)
const formulariosDisponibles = ref([])
const showParametrosClienteModal = ref(false)

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
  // Si hay un cliente específico (desktop), usar el modal que muestra rutinas
  // Si no hay cliente (selección masiva), usar el modal de selección múltiple
  if (cliente) {
    showAsignarRutinaClienteModal.value = true
  } else {
    showAsignarRutinaModal.value = true
  }
}

function abrirSubirDieta(cliente = null) {
  clienteParaAsignar.value = cliente
  showSubirDietaModal.value = true
}

function abrirSubirDietaUsuarios() {
  showSubirDietaUsuariosModal.value = true
}

function cerrarAsignarRutinaModal() {
  showAsignarRutinaModal.value = false
  clienteParaAsignar.value = null
  selectedClientes.value.clear()
}

function cerrarAsignarRutinaClienteModal() {
  showAsignarRutinaClienteModal.value = false
  clienteParaAsignar.value = null
}

function cerrarSubirDietaModal() {
  showSubirDietaModal.value = false
  clienteParaAsignar.value = null
  selectedClientes.value.clear()
}

function cerrarSubirDietaUsuariosModal() {
  showSubirDietaUsuariosModal.value = false
}

async function activarCliente(id) {
  clientesProcesando.value.add(id)
  try {
    await put(`/coach/clientes/${id}/activar`)
    await cargarClientes(paginaActual.value)
  } catch (e) {
    error.value = e.message || 'Error al activar cliente'
  } finally {
    clientesProcesando.value.delete(id)
  }
}

async function desactivarCliente(id) {
  clientesProcesando.value.add(id)
  try {
    if (!confirm('¿Desactivar este cliente? Solo podrá ver su perfil hasta que lo reactives.')) {
      return
    }
    await put(`/coach/clientes/${id}/desactivar`)
    await cargarClientes(paginaActual.value)
  } catch (e) {
    error.value = e.message || 'Error al desactivar cliente'
  } finally {
    clientesProcesando.value.delete(id)
  }
}

async function toggleClienteActivo(cliente) {
  if (cliente.activo) {
    await desactivarCliente(cliente.id)
  } else {
    await activarCliente(cliente.id)
  }
}

async function onRutinaAsignada(clienteActualizado) {
  await cargarClientes(paginaActual.value)
  if (modalDetalle.value && detalleCliente.value) {
    if (clienteActualizado) {
      detalleCliente.value = clienteActualizado
    } else {
      const res = await get(`/coach/clientes/${detalleCliente.value.id}`)
      detalleCliente.value = res.datos ?? res.data ?? res
    }
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

async function onClienteActualizado(clienteActualizado) {
  await cargarClientes(paginaActual.value)
  if (modalDetalle.value && detalleCliente.value) {
    detalleCliente.value = clienteActualizado
  }
}

function abrirParametros(c) {
  clienteParaAsignar.value = c
  showParametrosClienteModal.value = true
}

function cerrarParametrosClienteModal() {
  showParametrosClienteModal.value = false
  clienteParaAsignar.value = null
}

function onParametrosGuardado() {
  cargarClientes(paginaActual.value)
}

async function abrirFormulario(c) {
  try {
    // Cargar formularios disponibles
    const response = await get('/coach/formularios')
    formulariosDisponibles.value = response.datos || []
    
    if (formulariosDisponibles.value.length === 0) {
      alert('No hay formularios disponibles. Crea un formulario primero.')
      return
    }
    
    // Si solo hay uno, abrirlo directamente
    if (formulariosDisponibles.value.length === 1) {
      formularioParaCliente.value = formulariosDisponibles.value[0]
      clienteParaAsignar.value = c
      showFormularioClienteModal.value = true
      return
    }
    
    // Si hay varios, mostrar selector
    const opciones = formulariosDisponibles.value.map((f, i) => `${i + 1}. ${f.nombre}`).join('\n')
    const seleccion = prompt(`Selecciona un formulario (1-${formulariosDisponibles.value.length}):\n${opciones}`)
    
    if (seleccion) {
      const indice = parseInt(seleccion) - 1
      if (indice >= 0 && indice < formulariosDisponibles.value.length) {
        formularioParaCliente.value = formulariosDisponibles.value[indice]
        clienteParaAsignar.value = c
        showFormularioClienteModal.value = true
      }
    }
  } catch (err) {
    error.value = err.response?.data?.mensaje || 'Error al cargar formularios'
  }
}

function cerrarFormularioClienteModal() {
  showFormularioClienteModal.value = false
  formularioParaCliente.value = null
  clienteParaAsignar.value = null
}

function onFormularioCompletado() {
  cerrarFormularioClienteModal()
  cargarClientes(paginaActual.value)
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
          <div class="usuarios__section-header-left">
            <h2 class="usuarios__section-title">Usuarios</h2>
            <span class="usuarios__section-meta usuarios__section-meta--mobile" v-if="meta.total != null">
              {{ meta.total }} {{ meta.total === 1 ? 'cliente' : 'clientes' }}
            </span>
          </div>
          <button
            type="button"
            class="usuarios__header-action-btn"
            @click="abrirSubirDietaUsuarios"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
              <polyline points="17 8 12 3 7 8"/>
              <line x1="12" y1="3" x2="12" y2="15"/>
            </svg>
            <span>Subir dieta</span>
          </button>
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
            <div class="usuarios__table-header">
              <span class="usuarios__table-meta usuarios__table-meta--desktop" v-if="meta.total != null">
                {{ meta.total }} {{ meta.total === 1 ? 'cliente' : 'clientes' }}
              </span>
            </div>
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
                  <th class="usuarios__th">Acciones</th>
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
                    <div class="usuarios__estado-container">
                      <span
                        class="usuarios__badge"
                        :class="{ 'usuarios__badge--active': c.activo }"
                      >
                        {{ c.activo ? 'Activo' : 'Inactivo' }}
                      </span>
                      <BaseSwitch
                        :model-value="c.activo"
                        :loading="clientesProcesando.has(c.id)"
                        @update:model-value="toggleClienteActivo(c)"
                      />
                    </div>
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
                  <td class="usuarios__td">
                    <div class="usuarios__acciones">
                      <button
                        type="button"
                        class="usuarios__accion-btn usuarios__accion-btn--primary"
                        @click="abrirAsignarRutina(c)"
                        title="Asignar rutina"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="usuarios__accion-icon">
                          <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                        Rutina
                      </button>
                      <button
                        type="button"
                        class="usuarios__accion-btn usuarios__accion-btn--primary"
                        @click="abrirSubirDieta(c)"
                        title="Subir dieta"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="usuarios__accion-icon">
                          <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                          <polyline points="17 8 12 3 7 8"/>
                          <line x1="12" y1="3" x2="12" y2="15"/>
                        </svg>
                        Dieta
                      </button>
                      <button
                        type="button"
                        class="usuarios__accion-btn usuarios__accion-btn--primary"
                        @click="abrirParametros(c)"
                        title="Parámetros"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="usuarios__accion-icon">
                          <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                        Parámetros
                      </button>
                      <button
                        type="button"
                        class="usuarios__accion-btn usuarios__accion-btn--primary"
                        @click="abrirFormulario(c)"
                        title="Llenar formulario"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="usuarios__accion-icon">
                          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                          <polyline points="14 2 14 8 20 8"/>
                          <line x1="16" y1="13" x2="8" y2="13"/>
                          <line x1="16" y1="17" x2="8" y2="17"/>
                          <polyline points="10 9 9 9 8 9"/>
                        </svg>
                        Formulario
                      </button>
                    </div>
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
          @rutina-asignada="onRutinaAsignada"
          @cliente-actualizado="onClienteActualizado"
        />

        <!-- Modal asignar rutina (selección masiva) -->
        <AsignarRutinaModal
          v-if="showAsignarRutinaModal"
          :cliente="clienteParaAsignar"
          :clientes="clienteParaAsignar ? [] : clientesSeleccionados"
          @close="cerrarAsignarRutinaModal"
          @asignada="onRutinaAsignada"
        />

        <!-- Modal asignar rutina a cliente específico (desktop) -->
        <AsignarRutinaClienteModal
          v-if="showAsignarRutinaClienteModal && clienteParaAsignar"
          :cliente="clienteParaAsignar"
          @close="cerrarAsignarRutinaClienteModal"
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

        <!-- Modal subir dieta a usuarios -->
        <SubirDietaUsuariosModal
          v-if="showSubirDietaUsuariosModal"
          :clientes="clientes"
          @close="cerrarSubirDietaUsuariosModal"
          @subida="onDietaSubida"
        />

        <!-- Modal formulario cliente -->
        <FormularioClienteModal
          v-if="showFormularioClienteModal && clienteParaAsignar && formularioParaCliente"
          :cliente="clienteParaAsignar"
          :formulario="formularioParaCliente"
          @close="cerrarFormularioClienteModal"
          @completado="onFormularioCompletado"
        />

        <!-- Modal parámetros cliente -->
        <ParametrosClienteModal
          v-if="showParametrosClienteModal && clienteParaAsignar"
          :cliente="clienteParaAsignar"
          @close="cerrarParametrosClienteModal"
          @guardado="onParametrosGuardado"
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
  gap: 1rem;
}

.usuarios__section-header-left {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  flex: 1;
  min-width: 0;
}

.usuarios__section-title {
  font-size: 0.9375rem;
  font-weight: 600;
  color: #fff;
  margin: 0;
  flex-shrink: 0;
}

.usuarios__section-meta {
  font-size: 0.75rem;
  color: #697586;
}

.usuarios__section-meta--mobile {
  display: block;
}

@media (min-width: 769px) {
  .usuarios__section-meta--mobile {
    display: none;
  }
}

.usuarios__header-action-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.875rem;
  font-size: 0.8125rem;
  font-weight: 600;
  color: #00D261;
  background: rgba(0, 210, 97, 0.1);
  border: 1.5px solid #00D261;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 2px 8px rgba(0, 210, 97, 0.15);
  flex-shrink: 0;
  white-space: nowrap;
}

.usuarios__header-action-btn:hover {
  background: rgba(0, 210, 97, 0.2);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 210, 97, 0.3);
}

.usuarios__header-action-btn svg {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
}

@media (max-width: 768px) {
  .usuarios__header-action-btn {
    padding: 0.625rem 1.25rem;
    font-size: 0.875rem;
    min-width: 140px;
    justify-content: center;
  }
  
  .usuarios__header-action-btn svg {
    width: 18px;
    height: 18px;
  }
}

.usuarios__table-header {
  padding: 0.75rem 1rem;
  background: #1e1e1e;
  border-bottom: 1px solid #252525;
  border-radius: 12px 12px 0 0;
}

.usuarios__table-meta {
  font-size: 0.75rem;
  color: #697586;
}

.usuarios__table-meta--desktop {
  display: block;
}

@media (max-width: 768px) {
  .usuarios__table-meta--desktop {
    display: none;
  }
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

.usuarios__estado-container {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.usuarios__acciones {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.usuarios__accion-btn {
  padding: 0.375rem 0.75rem;
  font-size: 0.75rem;
  font-weight: 500;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  white-space: nowrap;
}

.usuarios__accion-icon {
  width: 14px;
  height: 14px;
  flex-shrink: 0;
}

.usuarios__accion-btn--primary {
  background: rgba(0, 210, 97, 0.1);
  color: #00D261;
  border: 1px solid rgba(0, 210, 97, 0.3);
}

.usuarios__accion-btn--primary:hover {
  background: rgba(0, 210, 97, 0.2);
  border-color: #00D261;
  transform: translateY(-1px);
}

.usuarios__accion-btn--success {
  background: rgba(0, 210, 97, 0.1);
  color: #00D261;
  border: 1px solid rgba(0, 210, 97, 0.3);
}

.usuarios__accion-btn--success:hover {
  background: rgba(0, 210, 97, 0.2);
  border-color: #00D261;
}

.usuarios__accion-btn--danger {
  background: rgba(239, 92, 92, 0.1);
  color: #EF5C5C;
  border: 1px solid rgba(239, 92, 92, 0.3);
}

.usuarios__accion-btn--danger:hover {
  background: rgba(239, 92, 92, 0.2);
  border-color: #EF5C5C;
}
</style>
