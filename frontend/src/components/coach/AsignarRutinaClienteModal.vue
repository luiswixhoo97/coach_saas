<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useApi } from '@/composables/useApi'
import Swal from 'sweetalert2'
import RutinaDetalleModal from '@/components/coach/RutinaDetalleModal.vue'

const props = defineProps({
  cliente: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'asignada'])

const { get, post, del, cargando } = useApi()

const DIAS_SEMANA = [
  { value: 'lunes', label: 'Lun', fullLabel: 'Lunes' },
  { value: 'martes', label: 'Mar', fullLabel: 'Martes' },
  { value: 'miércoles', label: 'Mié', fullLabel: 'Miércoles' },
  { value: 'jueves', label: 'Jue', fullLabel: 'Jueves' },
  { value: 'viernes', label: 'Vie', fullLabel: 'Viernes' },
  { value: 'sábado', label: 'Sáb', fullLabel: 'Sábado' },
  { value: 'domingo', label: 'Dom', fullLabel: 'Domingo' }
]

const NIVELES = [
  { value: '', label: 'Todos los niveles' },
  { value: 'principiante', label: 'Principiante' },
  { value: 'intermedio', label: 'Intermedio' },
  { value: 'avanzado', label: 'Avanzado' }
]

// Estados
const rutinas = ref([])
const rutinasAsignadas = ref([])
const rutinaSeleccionada = ref(null)
const diasSeleccionados = ref([])
const buscar = ref('')
const filtroNivel = ref('')
const error = ref('')
const guardando = ref(false)
const cargandoRutinas = ref(false)
const cargandoAsignadas = ref(false)

// Modal de detalle
const showRutinaDetalle = ref(false)
const rutinaDetalle = ref(null)
const cargandoDetalle = ref(false)

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

// Cargar rutinas disponibles
async function cargarRutinas() {
  cargandoRutinas.value = true
  try {
    const params = new URLSearchParams()
    if (filtroNivel.value) {
      params.set('nivel', filtroNivel.value)
    }
    const query = params.toString() ? `?${params.toString()}` : ''
    const res = await get(`/coach/rutinas${query}`)
    rutinas.value = res.data?.datos ?? res.datos ?? []
  } catch (e) {
    error.value = e.message || 'No se pudieron cargar las rutinas.'
    rutinas.value = []
  } finally {
    cargandoRutinas.value = false
  }
}

// Cargar rutinas asignadas del cliente
async function cargarRutinasAsignadas() {
  cargandoAsignadas.value = true
  try {
    const res = await get(`/coach/clientes/${props.cliente.id}`)
    const clienteData = res.datos ?? res.data ?? res
    rutinasAsignadas.value = clienteData.rutinas_asignadas || []
  } catch (e) {
    error.value = e.message || 'No se pudieron cargar las rutinas asignadas.'
    rutinasAsignadas.value = []
  } finally {
    cargandoAsignadas.value = false
  }
}

// Filtrar rutinas por búsqueda
const rutinasFiltradas = computed(() => {
  let lista = [...rutinas.value]
  
  // Filtro por búsqueda (mínimo 3 caracteres)
  if (buscar.value && buscar.value.length >= 3) {
    const termino = buscar.value.toLowerCase().trim()
    lista = lista.filter(r => {
      const nombre = (r.nombre || '').toLowerCase()
      const objetivo = (r.objetivo || '').toLowerCase()
      return nombre.includes(termino) || objetivo.includes(termino)
    })
  }
  
  return lista
})

// Calcular días ocupados
const diasOcupados = computed(() => {
  const ocupados = new Set()
  rutinasAsignadas.value.forEach(rutina => {
    if (rutina.dias && Array.isArray(rutina.dias)) {
      rutina.dias.forEach(dia => ocupados.add(dia))
    }
  })
  return ocupados
})

// Días disponibles (no ocupados)
const diasDisponibles = computed(() => {
  return DIAS_SEMANA.filter(dia => !diasOcupados.value.has(dia.value))
})

// Agrupar rutinas asignadas por día
const rutinasPorDia = computed(() => {
  const porDia = {}
  rutinasAsignadas.value.forEach(rutina => {
    if (rutina.dias && Array.isArray(rutina.dias)) {
      rutina.dias.forEach(dia => {
        if (!porDia[dia]) {
          porDia[dia] = []
        }
        porDia[dia].push(rutina)
      })
    }
  })
  return porDia
})

// Seleccionar rutina
function seleccionarRutina(rutina) {
  rutinaSeleccionada.value = rutina
  diasSeleccionados.value = []
}

// Toggle día
function toggleDia(dia) {
  if (diasOcupados.value.has(dia)) return // No permitir seleccionar días ocupados
  
  const index = diasSeleccionados.value.indexOf(dia)
  if (index > -1) {
    diasSeleccionados.value.splice(index, 1)
  } else {
    diasSeleccionados.value.push(dia)
  }
}

// Ver detalle de rutina
async function verDetalleRutina(rutina) {
  showRutinaDetalle.value = true
  rutinaDetalle.value = null
  cargandoDetalle.value = true
  
  try {
    const res = await get(`/coach/rutinas/${rutina.rutina_id || rutina.id}`)
    rutinaDetalle.value = res.datos ?? res.data ?? res
  } catch (e) {
    await Swal.fire({
      title: 'Error',
      text: e.message || 'No se pudo cargar el detalle de la rutina.',
      icon: 'error',
      confirmButtonColor: '#00D261'
    })
    showRutinaDetalle.value = false
  } finally {
    cargandoDetalle.value = false
  }
}

// Asignar rutina
async function asignarRutina() {
  if (!rutinaSeleccionada.value) {
    error.value = 'Debes seleccionar una rutina.'
    return
  }
  
  if (diasSeleccionados.value.length === 0) {
    error.value = 'Debes seleccionar al menos un día.'
    return
  }
  
  guardando.value = true
  error.value = ''
  
  try {
    await post(`/coach/rutinas/${rutinaSeleccionada.value.id}/asignar`, {
      cliente_ids: [props.cliente.id],
      dias: diasSeleccionados.value
    })
    
    await Swal.fire({
      title: 'Rutina asignada',
      text: 'La rutina se ha asignado correctamente.',
      icon: 'success',
      confirmButtonColor: '#00D261',
      customClass: {
        popup: 'swal-popup-over-modal',
        container: 'swal-container-over-modal'
      },
      didClose: async () => {
        // Recargar datos
        await cargarRutinasAsignadas()
        rutinaSeleccionada.value = null
        diasSeleccionados.value = []
        emit('asignada')
      }
    })
  } catch (e) {
    error.value = e.message || 'No se pudo asignar la rutina.'
  } finally {
    guardando.value = false
  }
}

// Quitar rutina
async function quitarRutina(rutina) {
  const result = await Swal.fire({
    title: '¿Quitar rutina?',
    text: `¿Estás seguro de que quieres quitar la rutina "${rutina.nombre}" del cliente?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, quitar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#EF5C5C',
    cancelButtonColor: '#666',
    reverseButtons: true,
    customClass: {
      popup: 'swal-popup-over-modal',
      container: 'swal-container-over-modal'
    }
  })
  
  if (!result.isConfirmed) return
  
  try {
    await del(`/coach/rutinas/${rutina.rutina_id}/desasignar/${props.cliente.id}`)
    
    await Swal.fire({
      title: 'Rutina quitada',
      text: 'La rutina se ha quitado correctamente.',
      icon: 'success',
      confirmButtonColor: '#00D261',
      timer: 2000,
      showConfirmButton: false,
      customClass: {
        popup: 'swal-popup-over-modal',
        container: 'swal-container-over-modal'
      }
    })
    
    await cargarRutinasAsignadas()
    emit('asignada')
  } catch (e) {
    await Swal.fire({
      title: 'Error',
      text: e.message || 'No se pudo quitar la rutina.',
      icon: 'error',
      confirmButtonColor: '#00D261',
      customClass: {
        popup: 'swal-popup-over-modal',
        container: 'swal-container-over-modal'
      }
    })
  }
}

// Watchers
watch(filtroNivel, () => {
  cargarRutinas()
})

// Cargar datos al montar
onMounted(async () => {
  await Promise.all([
    cargarRutinas(),
    cargarRutinasAsignadas()
  ])
})
</script>

<template>
  <Teleport to="body">
    <div class="asignar-rutina-cliente-modal__overlay" @click.self="$emit('close')">
      <div class="asignar-rutina-cliente-modal">
        <!-- Header -->
        <div class="asignar-rutina-cliente-modal__header">
          <div class="asignar-rutina-cliente-modal__cliente-info">
            <div class="asignar-rutina-cliente-modal__cliente-avatar">
              {{ iniciales(cliente) }}
            </div>
            <div class="asignar-rutina-cliente-modal__cliente-details">
              <h2 class="asignar-rutina-cliente-modal__cliente-nombre">
                {{ nombreCompleto(cliente) }}
              </h2>
              <p class="asignar-rutina-cliente-modal__cliente-email">
                {{ cliente.email || '—' }}
              </p>
            </div>
          </div>
          <button
            type="button"
            class="asignar-rutina-cliente-modal__close"
            aria-label="Cerrar"
            @click="$emit('close')"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 6L6 18M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Body -->
        <div class="asignar-rutina-cliente-modal__body">
          <div v-if="error" class="asignar-rutina-cliente-modal__error">{{ error }}</div>

          <!-- Búsqueda y Filtros (misma línea) -->
          <div class="asignar-rutina-cliente-modal__filters">
            <input
              v-model="buscar"
              type="search"
              class="asignar-rutina-cliente-modal__search-input"
              placeholder="Buscar por nombre o objetivo (mín. 3 caracteres)..."
              aria-label="Buscar rutinas"
            />
            <select
              v-model="filtroNivel"
              class="asignar-rutina-cliente-modal__filter-select"
              aria-label="Filtrar por nivel"
            >
              <option v-for="nivel in NIVELES" :key="nivel.value" :value="nivel.value">
                {{ nivel.label }}
              </option>
            </select>
          </div>

          <!-- Días de la Semana -->
          <div class="asignar-rutina-cliente-modal__dias-section">
            <h3 class="asignar-rutina-cliente-modal__section-title">Días</h3>
            <div class="asignar-rutina-cliente-modal__dias-grid">
              <button
                v-for="dia in DIAS_SEMANA"
                :key="dia.value"
                type="button"
                class="asignar-rutina-cliente-modal__dia-btn"
                :class="{
                  'asignar-rutina-cliente-modal__dia-btn--selected': diasSeleccionados.includes(dia.value),
                  'asignar-rutina-cliente-modal__dia-btn--ocupado': diasOcupados.has(dia.value)
                }"
                :disabled="diasOcupados.has(dia.value)"
                @click="toggleDia(dia.value)"
              >
                {{ dia.label }}
              </button>
            </div>
          </div>

          <!-- Rutinas Disponibles -->
          <div class="asignar-rutina-cliente-modal__rutinas-section">
            <h3 class="asignar-rutina-cliente-modal__section-title">Rutinas</h3>
            <div v-if="cargandoRutinas" class="asignar-rutina-cliente-modal__loading">
              <p>Cargando rutinas...</p>
            </div>
            <div v-else-if="rutinasFiltradas.length === 0" class="asignar-rutina-cliente-modal__empty">
              <p>{{ buscar.length >= 3 ? 'No se encontraron rutinas' : 'Escribe al menos 3 caracteres para buscar' }}</p>
            </div>
            <template v-else>
              <!-- Desktop: Tabla -->
              <div class="asignar-rutina-cliente-modal__table-container asignar-rutina-cliente-modal__table-container--desktop">
                <table class="asignar-rutina-cliente-modal__table">
                  <thead class="asignar-rutina-cliente-modal__thead">
                    <tr>
                      <th class="asignar-rutina-cliente-modal__th">Nombre</th>
                      <th class="asignar-rutina-cliente-modal__th">Nivel</th>
                      <th class="asignar-rutina-cliente-modal__th">Objetivo</th>
                      <th class="asignar-rutina-cliente-modal__th">Ejercicios</th>
                      <th class="asignar-rutina-cliente-modal__th">Acciones</th>
                    </tr>
                  </thead>
                  <tbody class="asignar-rutina-cliente-modal__tbody">
                    <tr
                      v-for="rutina in rutinasFiltradas"
                      :key="rutina.id"
                      class="asignar-rutina-cliente-modal__tr"
                      :class="{ 'asignar-rutina-cliente-modal__tr--selected': rutinaSeleccionada?.id === rutina.id }"
                      @click="seleccionarRutina(rutina)"
                    >
                      <td class="asignar-rutina-cliente-modal__td">{{ rutina.nombre || '—' }}</td>
                      <td class="asignar-rutina-cliente-modal__td">{{ rutina.nivel || '—' }}</td>
                      <td class="asignar-rutina-cliente-modal__td">{{ rutina.objetivo || '—' }}</td>
                      <td class="asignar-rutina-cliente-modal__td">
                        {{ rutina.ejercicios_count || rutina.rutina_ejercicios_count || 0 }}
                      </td>
                      <td class="asignar-rutina-cliente-modal__td asignar-rutina-cliente-modal__td--actions">
                        <div class="asignar-rutina-cliente-modal__actions-group">
                          <button
                            v-if="rutinaSeleccionada?.id === rutina.id && diasSeleccionados.length > 0"
                            type="button"
                            class="asignar-rutina-cliente-modal__assign-btn-inline"
                            :disabled="guardando"
                            @click.stop="asignarRutina"
                            title="Asignar rutina"
                          >
                            <span v-if="guardando">Asignando...</span>
                            <span v-else>Asignar</span>
                          </button>
                          <button
                            type="button"
                            class="asignar-rutina-cliente-modal__action-btn"
                            @click.stop="verDetalleRutina(rutina)"
                            title="Ver detalle"
                          >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                              <circle cx="12" cy="12" r="3"/>
                            </svg>
                          </button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Móvil: Cards -->
              <div class="asignar-rutina-cliente-modal__cards-container asignar-rutina-cliente-modal__cards-container--mobile">
                <div
                  v-for="rutina in rutinasFiltradas"
                  :key="rutina.id"
                  class="asignar-rutina-cliente-modal__card"
                  :class="{ 'asignar-rutina-cliente-modal__card--selected': rutinaSeleccionada?.id === rutina.id }"
                  @click="seleccionarRutina(rutina)"
                >
                  <div class="asignar-rutina-cliente-modal__card-info">
                    <span class="asignar-rutina-cliente-modal__card-nombre">{{ rutina.nombre || '—' }}</span>
                    <span class="asignar-rutina-cliente-modal__card-ejercicios">
                      {{ rutina.ejercicios_count || rutina.rutina_ejercicios_count || 0 }} {{ (rutina.ejercicios_count || rutina.rutina_ejercicios_count || 0) === 1 ? 'ejercicio' : 'ejercicios' }}
                    </span>
                  </div>
                  <div class="asignar-rutina-cliente-modal__card-actions">
                    <button
                      v-if="rutinaSeleccionada?.id === rutina.id && diasSeleccionados.length > 0"
                      type="button"
                      class="asignar-rutina-cliente-modal__card-assign-btn"
                      :disabled="guardando"
                      @click.stop="asignarRutina"
                      title="Asignar rutina"
                    >
                      <span v-if="guardando">Asignando...</span>
                      <span v-else>Asignar</span>
                    </button>
                    <button
                      type="button"
                      class="asignar-rutina-cliente-modal__card-action"
                      @click.stop="verDetalleRutina(rutina)"
                      title="Ver detalle"
                    >
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
            </template>
          </div>

          <!-- Rutinas Asignadas -->
          <div class="asignar-rutina-cliente-modal__asignadas-section">
            <h3 class="asignar-rutina-cliente-modal__section-title">Rutinas ya asignadas</h3>
            <div v-if="cargandoAsignadas" class="asignar-rutina-cliente-modal__loading">
              <p>Cargando...</p>
            </div>
            <div v-else-if="rutinasAsignadas.length === 0" class="asignar-rutina-cliente-modal__empty">
              <p>No hay rutinas asignadas</p>
            </div>
            <div v-else class="asignar-rutina-cliente-modal__asignadas-list">
              <div
                v-for="(rutinas, dia) in rutinasPorDia"
                :key="dia"
                class="asignar-rutina-cliente-modal__dia-group"
              >
                <div class="asignar-rutina-cliente-modal__dia-header">
                  {{ DIAS_SEMANA.find(d => d.value === dia)?.fullLabel || dia }}
                </div>
                <div class="asignar-rutina-cliente-modal__dia-content">
                  <div
                    v-for="rutina in rutinas"
                    :key="`${dia}-${rutina.id}`"
                    class="asignar-rutina-cliente-modal__asignada-item"
                  >
                    <div class="asignar-rutina-cliente-modal__asignada-info">
                      <span class="asignar-rutina-cliente-modal__asignada-nombre">{{ rutina.nombre || '—' }}</span>
                      <span class="asignar-rutina-cliente-modal__asignada-ejercicios">
                        {{ rutina.ejercicios_count || 0 }} {{ rutina.ejercicios_count === 1 ? 'ejercicio' : 'ejercicios' }}
                      </span>
                    </div>
                    <div class="asignar-rutina-cliente-modal__asignada-actions">
                      <button
                        type="button"
                        class="asignar-rutina-cliente-modal__asignada-btn asignar-rutina-cliente-modal__asignada-btn--view"
                        @click="verDetalleRutina(rutina)"
                        title="Ver detalle"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                          <circle cx="12" cy="12" r="3"/>
                        </svg>
                      </button>
                      <button
                        type="button"
                        class="asignar-rutina-cliente-modal__asignada-btn asignar-rutina-cliente-modal__asignada-btn--remove"
                        @click="quitarRutina(rutina)"
                        title="Quitar"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <path d="M18 6L6 18M6 6l12 12"/>
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="asignar-rutina-cliente-modal__footer">
          <button
            type="button"
            class="asignar-rutina-cliente-modal__btn asignar-rutina-cliente-modal__btn--cancel"
            @click="$emit('close')"
          >
            Cancelar
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Detalle Rutina -->
    <RutinaDetalleModal
      v-if="showRutinaDetalle"
      :rutina="rutinaDetalle"
      @close="showRutinaDetalle = false"
    />
  </Teleport>
</template>

<style scoped>
.asignar-rutina-cliente-modal__overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.75);
  display: flex;
  align-items: flex-end;
  justify-content: center;
  z-index: 2000;
  animation: asignar-rutina-cliente-modal-fade 0.2s ease;
}

@keyframes asignar-rutina-cliente-modal-fade {
  from { opacity: 0; }
  to { opacity: 1; }
}

.asignar-rutina-cliente-modal {
  background: #161616;
  border-radius: 20px 20px 0 0;
  width: 100%;
  max-width: 100%;
  height: 90vh;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  animation: asignar-rutina-cliente-modal-slide 0.3s ease;
}

@keyframes asignar-rutina-cliente-modal-slide {
  from { transform: translateY(100%); }
  to { transform: translateY(0); }
}

@media (min-width: 640px) {
  .asignar-rutina-cliente-modal__overlay {
    align-items: center;
    padding: 1rem;
  }
  .asignar-rutina-cliente-modal {
    border-radius: 20px;
    max-width: 900px;
    height: auto;
    max-height: 90vh;
  }
}

.asignar-rutina-cliente-modal__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.25rem;
  border-bottom: 1px solid #252525;
  flex-shrink: 0;
}

.asignar-rutina-cliente-modal__cliente-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex: 1;
  min-width: 0;
}

.asignar-rutina-cliente-modal__cliente-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: linear-gradient(135deg, #2970FF 0%, #528BFF 100%);
  color: #fff;
  font-size: 1rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.asignar-rutina-cliente-modal__cliente-details {
  flex: 1;
  min-width: 0;
}

.asignar-rutina-cliente-modal__cliente-nombre {
  font-size: 1.125rem;
  font-weight: 600;
  color: #fff;
  margin: 0 0 0.25rem 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.asignar-rutina-cliente-modal__cliente-email {
  font-size: 0.875rem;
  color: #697586;
  margin: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.asignar-rutina-cliente-modal__close {
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

.asignar-rutina-cliente-modal__close:hover {
  background: #333;
  color: #fff;
}

.asignar-rutina-cliente-modal__close svg {
  width: 18px;
  height: 18px;
}

.asignar-rutina-cliente-modal__body {
  padding: 1.25rem;
  overflow-y: auto;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.asignar-rutina-cliente-modal__error {
  background: rgba(239, 92, 92, 0.12);
  border: 1px solid rgba(239, 92, 92, 0.3);
  color: #EF5C5C;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  font-size: 0.875rem;
}

.asignar-rutina-cliente-modal__filters {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
  align-items: center;
}

@media (min-width: 640px) {
  .asignar-rutina-cliente-modal__filters {
    flex-wrap: nowrap;
  }
}

.asignar-rutina-cliente-modal__search-input {
  flex: 1;
  min-width: 0;
  background: #1e1e1e;
  border: 1px solid #252525;
  border-radius: 12px;
  padding: 0.625rem 0.875rem;
  font-size: 0.875rem;
  color: #fff;
}

.asignar-rutina-cliente-modal__search-input:focus {
  outline: none;
  border-color: #00D261;
  box-shadow: 0 0 0 2px rgba(0, 210, 97, 0.2);
}

.asignar-rutina-cliente-modal__filter-select {
  background: #1e1e1e;
  border: 1px solid #252525;
  border-radius: 12px;
  padding: 0.625rem 0.875rem;
  font-size: 0.875rem;
  color: #fff;
  min-width: 150px;
  flex-shrink: 0;
}

.asignar-rutina-cliente-modal__filter-select:focus {
  outline: none;
  border-color: #00D261;
  box-shadow: 0 0 0 2px rgba(0, 210, 97, 0.2);
}

.asignar-rutina-cliente-modal__section-title {
  font-size: 0.9375rem;
  font-weight: 600;
  color: #fff;
  margin: 0 0 0.75rem 0;
}

.asignar-rutina-cliente-modal__table-container {
  max-height: calc(3 * (0.75rem * 2 + 1rem) + 2.5rem);
  overflow-y: auto;
  border: 1px solid #252525;
  border-radius: 8px;
  background: #1e1e1e;
  display: none;
}

.asignar-rutina-cliente-modal__table-container--desktop {
  display: block;
}

@media (max-width: 768px) {
  .asignar-rutina-cliente-modal__table-container--desktop {
    display: none;
  }
}

.asignar-rutina-cliente-modal__cards-container {
  max-height: calc(3 * (4.5rem + 0.5rem));
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.asignar-rutina-cliente-modal__cards-container--mobile {
  display: flex;
}

@media (min-width: 769px) {
  .asignar-rutina-cliente-modal__cards-container--mobile {
    display: none;
  }
}

.asignar-rutina-cliente-modal__card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.875rem 1rem;
  background: #1e1e1e;
  border: 1px solid #252525;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s;
  position: relative;
}

.asignar-rutina-cliente-modal__card::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 1px;
  background: transparent;
  transition: background 0.2s;
}

.asignar-rutina-cliente-modal__card:hover {
  background: #252525;
  border-color: rgba(0, 210, 97, 0.3);
}

.asignar-rutina-cliente-modal__card--selected {
  background: rgba(0, 210, 97, 0.1);
  border-color: #00D261;
  border-left: 3px solid #00D261;
}

.asignar-rutina-cliente-modal__card-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  flex: 1;
  min-width: 0;
}

.asignar-rutina-cliente-modal__card-nombre {
  font-size: 0.9375rem;
  font-weight: 500;
  color: #fff;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.asignar-rutina-cliente-modal__card-ejercicios {
  font-size: 0.75rem;
  color: #697586;
}

.asignar-rutina-cliente-modal__card-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-shrink: 0;
}

.asignar-rutina-cliente-modal__card-action {
  background: transparent;
  border: none;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #697586;
  transition: all 0.2s;
  border-radius: 6px;
  flex-shrink: 0;
}

.asignar-rutina-cliente-modal__card-action:hover {
  background: rgba(0, 210, 97, 0.1);
  color: #00D261;
}

.asignar-rutina-cliente-modal__card-action svg {
  width: 18px;
  height: 18px;
}

.asignar-rutina-cliente-modal__card-assign-btn {
  padding: 0.5rem 0.875rem;
  font-size: 0.8125rem;
  font-weight: 600;
  color: #fff;
  background: #00D261;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
}

.asignar-rutina-cliente-modal__card-assign-btn:hover:not(:disabled) {
  background: #00b854;
}

.asignar-rutina-cliente-modal__card-assign-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.asignar-rutina-cliente-modal__table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.8125rem;
}

.asignar-rutina-cliente-modal__thead {
  position: sticky;
  top: 0;
  background: #1e1e1e;
  z-index: 1;
}

.asignar-rutina-cliente-modal__th {
  text-align: left;
  padding: 0.75rem 1rem;
  font-weight: 600;
  color: #a0a0a0;
  border-bottom: 1px solid #252525;
  white-space: nowrap;
}

.asignar-rutina-cliente-modal__tbody {
  background: #161616;
}

.asignar-rutina-cliente-modal__tr {
  border-bottom: 1px solid #252525;
  transition: background 0.2s;
  cursor: pointer;
}

.asignar-rutina-cliente-modal__tr:last-child {
  border-bottom: none;
}

.asignar-rutina-cliente-modal__tr:hover {
  background: rgba(255, 255, 255, 0.02);
}

.asignar-rutina-cliente-modal__tr--selected {
  background: rgba(0, 210, 97, 0.1);
  border-left: 3px solid #00D261;
}

.asignar-rutina-cliente-modal__td {
  padding: 0.75rem 1rem;
  color: #fff;
  vertical-align: middle;
}

.asignar-rutina-cliente-modal__td--actions {
  text-align: center;
}

.asignar-rutina-cliente-modal__action-btn {
  background: transparent;
  border: none;
  width: 32px;
  height: 32px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #697586;
  transition: all 0.2s;
  border-radius: 6px;
}

.asignar-rutina-cliente-modal__action-btn:hover {
  background: rgba(0, 210, 97, 0.1);
  color: #00D261;
}

.asignar-rutina-cliente-modal__action-btn svg {
  width: 18px;
  height: 18px;
}

.asignar-rutina-cliente-modal__dias-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 0.5rem;
}

.asignar-rutina-cliente-modal__dia-btn {
  padding: 0.75rem 0.5rem;
  font-size: 0.8125rem;
  font-weight: 500;
  color: #00D261;
  background: rgba(0, 210, 97, 0.1);
  border: 1.5px solid #00D261;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
  text-align: center;
}

.asignar-rutina-cliente-modal__dia-btn:hover:not(:disabled) {
  background: rgba(0, 210, 97, 0.2);
  transform: translateY(-1px);
}

.asignar-rutina-cliente-modal__dia-btn--selected {
  background: #00D261;
  color: #fff;
}

.asignar-rutina-cliente-modal__dia-btn--ocupado {
  opacity: 1;
  cursor: not-allowed;
  background: rgba(239, 92, 92, 0.15);
  border-color: rgba(239, 92, 92, 0.4);
  color: rgba(239, 92, 92, 0.7);
}

.asignar-rutina-cliente-modal__actions-group {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.asignar-rutina-cliente-modal__assign-btn-inline {
  padding: 0.5rem 0.875rem;
  font-size: 0.8125rem;
  font-weight: 600;
  color: #fff;
  background: #00D261;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
}

.asignar-rutina-cliente-modal__assign-btn-inline:hover:not(:disabled) {
  background: #00b854;
}

.asignar-rutina-cliente-modal__assign-btn-inline:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.asignar-rutina-cliente-modal__asignadas-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.asignar-rutina-cliente-modal__dia-group {
  display: flex;
  flex-direction: column;
  border-radius: 12px;
  overflow: hidden;
  border: 1.5px solid #00D261;
  box-shadow: 0 2px 8px rgba(0, 210, 97, 0.15);
}

.asignar-rutina-cliente-modal__dia-header {
  padding: 0.625rem 1rem;
  font-size: 0.875rem;
  font-weight: 600;
  background: rgba(0, 210, 97, 0.1);
  border: none;
  border-bottom: 1.5px solid #00D261;
  color: #00D261;
  text-transform: capitalize;
}

.asignar-rutina-cliente-modal__dia-content {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  padding: 0.75rem;
  background: #1e1e1e;
}

.asignar-rutina-cliente-modal__asignada-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.75rem;
  background: #252525;
  border-radius: 6px;
  border: 1px solid #2a2a2a;
}

.asignar-rutina-cliente-modal__asignada-info {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
  flex: 1;
  min-width: 0;
}

.asignar-rutina-cliente-modal__asignada-nombre {
  font-size: 0.875rem;
  font-weight: 500;
  color: #fff;
}

.asignar-rutina-cliente-modal__asignada-ejercicios {
  font-size: 0.75rem;
  color: #697586;
}

.asignar-rutina-cliente-modal__asignada-actions {
  display: flex;
  gap: 0.5rem;
  flex-shrink: 0;
}

.asignar-rutina-cliente-modal__asignada-btn {
  background: transparent;
  border: none;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  border-radius: 6px;
}

.asignar-rutina-cliente-modal__asignada-btn svg {
  width: 18px;
  height: 18px;
}

.asignar-rutina-cliente-modal__asignada-btn--view {
  color: #697586;
}

.asignar-rutina-cliente-modal__asignada-btn--view:hover {
  background: rgba(0, 210, 97, 0.1);
  color: #00D261;
}

.asignar-rutina-cliente-modal__asignada-btn--remove {
  color: #697586;
}

.asignar-rutina-cliente-modal__asignada-btn--remove:hover {
  background: rgba(239, 92, 92, 0.1);
  color: #EF5C5C;
}

.asignar-rutina-cliente-modal__loading,
.asignar-rutina-cliente-modal__empty {
  padding: 2rem;
  text-align: center;
  color: #697586;
  font-size: 0.875rem;
}

.asignar-rutina-cliente-modal__footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 1rem 1.25rem;
  padding-bottom: max(1rem, env(safe-area-inset-bottom));
  border-top: 1px solid #252525;
  flex-shrink: 0;
}

.asignar-rutina-cliente-modal__btn {
  padding: 0.625rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.asignar-rutina-cliente-modal__btn--cancel {
  color: #697586;
  background: transparent;
  border: 1px solid #252525;
}

.asignar-rutina-cliente-modal__btn--cancel:hover {
  background: #1e1e1e;
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

