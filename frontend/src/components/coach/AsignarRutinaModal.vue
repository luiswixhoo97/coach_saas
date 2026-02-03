<script setup>
import { ref, computed, onMounted, watch } from 'vue'
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
  },
  rutinaId: {
    type: Number,
    default: null
  }
})

const emit = defineEmits(['close', 'asignada'])

const { get, post, del, cargando } = useApi()

const DIAS_SEMANA = [
  { value: 'lunes', label: 'Lunes' },
  { value: 'martes', label: 'Martes' },
  { value: 'miércoles', label: 'Miércoles' },
  { value: 'jueves', label: 'Jueves' },
  { value: 'viernes', label: 'Viernes' },
  { value: 'sábado', label: 'Sábado' },
  { value: 'domingo', label: 'Domingo' }
]

// Estados del flujo
const paso = ref(1) // 1: seleccionar clientes, 2: procesar clientes uno por uno
const buscarCliente = ref('')
const todosClientes = ref([])
const clientesSeleccionados = ref(new Set())
const cargandoClientes = ref(false)

// Procesamiento secuencial
const listaClientesProcesar = ref([]) // Array de IDs de clientes a procesar
const indiceClienteActual = ref(0) // Índice del cliente que se está procesando
const clienteActual = computed(() => {
  if (listaClientesProcesar.value.length === 0 || indiceClienteActual.value < 0) return null
  const clienteId = listaClientesProcesar.value[indiceClienteActual.value]
  return todosClientes.value.find(c => c.id === clienteId) || null
})

// Rutinas
const rutinas = ref([])
const rutinasAsignadas = ref([]) // Rutinas actuales del cliente actual
const diasSeleccionados = ref([])
const error = ref('')
const guardando = ref(false)
const cargandoRutinas = ref(false)

// Si viene cliente o clientes pre-seleccionados
if (props.cliente) {
  clientesSeleccionados.value.add(props.cliente.id)
}
if (props.clientes && props.clientes.length > 0) {
  props.clientes.forEach(c => clientesSeleccionados.value.add(c.id))
}

onMounted(async () => {
  await cargarClientes()
  await cargarRutinas()
  if (clientesSeleccionados.value.size > 0) {
    iniciarProcesamiento()
  }
})

// Iniciar procesamiento secuencial
function iniciarProcesamiento() {
  listaClientesProcesar.value = Array.from(clientesSeleccionados.value)
  indiceClienteActual.value = 0
  paso.value = 2
  cargarDatosClienteActual()
}

// Cargar datos del cliente actual
async function cargarDatosClienteActual() {
  if (!clienteActual.value) return
  await cargarRutinasAsignadas()
  diasSeleccionados.value = []
}

// Filtrar clientes: mostrar primeros 4 por defecto, o resultados de búsqueda si hay 3+ caracteres
const clientesFiltrados = computed(() => {
  // Asegurar que siempre sea un array
  if (!Array.isArray(todosClientes.value)) {
    return []
  }
  
  let lista = [...todosClientes.value] // Crear copia para evitar mutaciones
  
  // Si hay búsqueda con 3+ caracteres, filtrar
  if (buscarCliente.value.trim().length >= 3) {
    const term = buscarCliente.value.trim().toLowerCase()
    lista = lista.filter(c => {
      if (!c) return false
      const nombre = nombreCompleto(c).toLowerCase()
      const email = (c.email || '').toLowerCase()
      return nombre.includes(term) || email.includes(term)
    })
  } else {
    // Por defecto, mostrar solo los primeros 4
    lista = lista.slice(0, 4)
  }
  
  // Asegurar que el resultado final siempre sea un array
  return Array.isArray(lista) ? lista : []
})

// Días ocupados (que ya tienen rutina asignada) - solo del cliente actual
const diasOcupados = computed(() => {
  const ocupados = new Set()
  if (Array.isArray(rutinasAsignadas.value)) {
    rutinasAsignadas.value.forEach(ra => {
      // Solo considerar rutinas del cliente actual
      if (ra.cliente_id === clienteActual.value?.id) {
        const dias = ra.dias ?? ra.dia ?? []
        if (Array.isArray(dias) && dias.length > 0) {
          dias.forEach(dia => {
            if (dia) ocupados.add(dia)
          })
        }
      }
    })
  }
  return ocupados
})

// Progreso: cliente X de Y
const progreso = computed(() => {
  const total = listaClientesProcesar.value.length
  const actual = indiceClienteActual.value + 1
  return { actual, total }
})

async function cargarClientes() {
  try {
    cargandoClientes.value = true
    const res = await get('/coach/clientes?activo=1')
    // Manejar estructura anidada: res.data.datos o res.datos
    let datos = res.data?.datos ?? res.datos ?? res.data ?? res
    // Asegurar que siempre sea un array
    todosClientes.value = Array.isArray(datos) ? datos : []
  } catch (e) {
    error.value = e.message || 'No se pudo cargar los clientes.'
    todosClientes.value = []
  } finally {
    cargandoClientes.value = false
  }
}

async function cargarRutinas() {
  try {
    const res = await get('/coach/rutinas')
    // Manejar estructura anidada: res.data.datos o res.datos
    const datos = res.data?.datos ?? res.datos ?? res.data ?? res
    rutinas.value = Array.isArray(datos) ? datos : []
  } catch (e) {
    error.value = e.message || 'No se pudo cargar las rutinas.'
    rutinas.value = []
  }
}

async function cargarRutinasAsignadas() {
  if (!clienteActual.value) {
    rutinasAsignadas.value = []
    return
  }
  
  try {
    cargandoRutinas.value = true
    const res = await get(`/coach/clientes/${clienteActual.value.id}`)
    // Manejar estructura anidada: res.data.datos o res.datos
    const cliente = res.data?.datos ?? res.datos ?? res.data ?? res
    
    // Verificar si tiene rutinas_asignadas
    const rutinasCliente = cliente.rutinas_asignadas ?? []
    
    // Normalizar las rutinas asignadas
    rutinasAsignadas.value = Array.isArray(rutinasCliente) ? rutinasCliente.map(ra => ({
      id: ra.id,
      rutina_id: ra.rutina_id,
      cliente_id: clienteActual.value.id,
      dias: Array.isArray(ra.dias) ? ra.dias : (Array.isArray(ra.dia) ? ra.dia : []),
      nombre: ra.nombre || 'Rutina sin nombre'
    })) : []
  } catch (e) {
    console.error('Error cargando rutinas asignadas:', e)
    rutinasAsignadas.value = []
  } finally {
    cargandoRutinas.value = false
  }
}

function toggleCliente(clienteId) {
  if (clientesSeleccionados.value.has(clienteId)) {
    clientesSeleccionados.value.delete(clienteId)
  } else {
    clientesSeleccionados.value.add(clienteId)
  }
}

function continuarASeleccionarDias() {
  if (clientesSeleccionados.value.size === 0) {
    error.value = 'Debes seleccionar al menos un cliente.'
    return
  }
  iniciarProcesamiento()
}

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

// Función eliminada - ya no hay paso 2, todo se muestra en el mismo modal

function toggleDia(dia) {
  const index = diasSeleccionados.value.indexOf(dia)
  if (index > -1) {
    diasSeleccionados.value.splice(index, 1)
  } else {
    diasSeleccionados.value.push(dia)
  }
}

// Guardar rutina para el cliente actual y avanzar al siguiente
async function guardarYContinuar() {
  if (!props.rutinaId) {
    error.value = 'No hay rutina seleccionada para asignar.'
    return
  }
  
  if (!clienteActual.value) {
    error.value = 'No hay cliente actual para procesar.'
    return
  }
  
  if (diasSeleccionados.value.length === 0) {
    error.value = 'Debes seleccionar al menos un día.'
    return
  }
  
  guardando.value = true
  error.value = ''
  
  try {
    // Guardar rutina para el cliente actual
    await post(`/coach/rutinas/${props.rutinaId}/asignar`, {
      cliente_ids: [clienteActual.value.id],
      dias: diasSeleccionados.value
    })
    
    // Avanzar al siguiente cliente
    indiceClienteActual.value++
    
    if (indiceClienteActual.value >= listaClientesProcesar.value.length) {
      // Se terminaron todos los clientes
      await Swal.fire({
        title: 'Rutina asignada',
        text: `La rutina se ha asignado correctamente a ${listaClientesProcesar.value.length} ${listaClientesProcesar.value.length === 1 ? 'cliente' : 'clientes'}.`,
        icon: 'success',
        confirmButtonColor: '#00D261'
      })
      emit('asignada')
      emit('close')
    } else {
      // Cargar datos del siguiente cliente
      await cargarDatosClienteActual()
    }
  } catch (e) {
    error.value = e.message || 'No se pudo asignar la rutina.'
  } finally {
    guardando.value = false
  }
}

// Omitir cliente actual y pasar al siguiente
function omitirCliente() {
  indiceClienteActual.value++
  if (indiceClienteActual.value >= listaClientesProcesar.value.length) {
    emit('close')
  } else {
    cargarDatosClienteActual()
  }
}

async function quitarRutina(rutinaAsignada) {
  if (!rutinaAsignada.rutina_id || !rutinaAsignada.cliente_id) {
    error.value = 'Datos incompletos para desasignar la rutina.'
    return
  }
  
  const nombreRutinaTexto = rutinaAsignada.nombre || nombreRutina(rutinaAsignada.rutina_id) || 'esta rutina'
  
  const result = await Swal.fire({
    title: '¿Desasignar rutina?',
    text: `¿Estás seguro de que quieres desasignar ${nombreRutinaTexto}?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, desasignar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#00D261',
    cancelButtonColor: '#666',
    reverseButtons: true
  })
  
  if (!result.isConfirmed) {
    return
  }
  
  try {
    await del(`/coach/rutinas/${rutinaAsignada.rutina_id}/desasignar/${rutinaAsignada.cliente_id}`)
    // Recargar rutinas asignadas
    await cargarRutinasAsignadas()
    await Swal.fire({
      title: 'Rutina desasignada',
      text: 'La rutina se ha desasignado correctamente.',
      icon: 'success',
      confirmButtonColor: '#00D261',
      timer: 2000,
      showConfirmButton: false
    })
    error.value = ''
  } catch (e) {
    error.value = e.message || 'No se pudo desasignar la rutina.'
    await Swal.fire({
      title: 'Error',
      text: e.message || 'No se pudo desasignar la rutina.',
      icon: 'error',
      confirmButtonColor: '#00D261'
    })
  }
}

function nombreRutina(rutinaId) {
  const rutina = rutinas.value.find(r => r.id === rutinaId)
  return rutina ? rutina.nombre : '—'
}

function diaAbreviado(dia) {
  const diasLabels = {
    lunes: 'Lun',
    martes: 'Mar',
    miércoles: 'Mié',
    jueves: 'Jue',
    viernes: 'Vie',
    sábado: 'Sáb',
    domingo: 'Dom'
  }
  return diasLabels[dia] || dia
}

function diasTexto(dias) {
  if (!dias || !Array.isArray(dias) || dias.length === 0) return 'Sin días'
  return dias.map(d => diaAbreviado(d)).join(', ')
}

// Función eliminada - ya no se usa guardar() porque guardamos directamente con guardarRutinaEnDiasSeleccionados()
</script>

<template>
  <Teleport to="body">
    <div class="asignar-rutina-modal__overlay" @click.self="$emit('close')">
      <div class="asignar-rutina-modal">
        <div class="asignar-rutina-modal__header">
          <h2 class="asignar-rutina-modal__title">
            {{ paso === 1 ? 'Seleccionar clientes' : 'Asignar rutina' }}
          </h2>
          <button
            type="button"
            class="asignar-rutina-modal__close"
            aria-label="Cerrar"
            @click="$emit('close')"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 6L6 18M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <div class="asignar-rutina-modal__body">
          <div v-if="error" class="asignar-rutina-modal__error">{{ error }}</div>

          <!-- Paso 1: Seleccionar clientes -->
          <div v-if="paso === 1" class="asignar-rutina-modal__step">
            <div class="asignar-rutina-modal__search">
              <input
                v-model="buscarCliente"
                type="search"
                class="asignar-rutina-modal__search-input"
                placeholder="Buscar cliente (mínimo 3 caracteres)..."
                aria-label="Buscar cliente"
              />
              <p v-if="buscarCliente.trim().length > 0 && buscarCliente.trim().length < 3" class="asignar-rutina-modal__search-hint">
                Escribe al menos 3 caracteres para buscar
              </p>
            </div>

            <div class="asignar-rutina-modal__clientes-list">
              <div
                v-for="cliente in clientesFiltrados"
                :key="cliente.id"
                class="asignar-rutina-modal__cliente-item"
                :class="{ 'asignar-rutina-modal__cliente-item--selected': clientesSeleccionados.has(cliente.id) }"
                @click="toggleCliente(cliente.id)"
              >
                <div class="asignar-rutina-modal__cliente-avatar">
                  {{ iniciales(cliente) }}
                </div>
                <div class="asignar-rutina-modal__cliente-info">
                  <span class="asignar-rutina-modal__cliente-nombre">{{ nombreCompleto(cliente) }}</span>
                  <span class="asignar-rutina-modal__cliente-email">{{ cliente.email || '—' }}</span>
                </div>
                <div class="asignar-rutina-modal__cliente-checkbox">
                  <input
                    type="checkbox"
                    :checked="clientesSeleccionados.has(cliente.id)"
                    @change.stop="toggleCliente(cliente.id)"
                  />
                </div>
              </div>
              
              <div v-if="cargandoClientes" class="asignar-rutina-modal__loading">
                Cargando clientes...
              </div>
              
              <div v-if="!cargandoClientes && clientesFiltrados.length === 0" class="asignar-rutina-modal__empty">
                <p>No se encontraron clientes.</p>
              </div>
            </div>

          </div>

          <!-- Paso 2: Procesar cliente actual -->
          <div v-if="paso === 2 && clienteActual" class="asignar-rutina-modal__step">
            <!-- Información del cliente actual -->
            <div class="asignar-rutina-modal__cliente-actual">
              <div class="asignar-rutina-modal__cliente-avatar asignar-rutina-modal__cliente-avatar--large">
                {{ iniciales(clienteActual) }}
              </div>
              <div class="asignar-rutina-modal__cliente-actual-info">
                <h3 class="asignar-rutina-modal__cliente-actual-nombre">{{ nombreCompleto(clienteActual) }}</h3>
                <p class="asignar-rutina-modal__cliente-actual-email">{{ clienteActual.email || '—' }}</p>
              </div>
            </div>

            <!-- 1. Días de la semana (primero) -->
            <div class="asignar-rutina-modal__step">
              <h3 class="asignar-rutina-modal__step-title">¿Qué días entrena a la semana?</h3>
              <div class="asignar-rutina-modal__dias-grid">
                <button
                  v-for="dia in DIAS_SEMANA"
                  :key="dia.value"
                  type="button"
                  class="asignar-rutina-modal__dia-btn"
                  :class="{
                    'asignar-rutina-modal__dia-btn--selected': diasSeleccionados.includes(dia.value)
                  }"
                  :disabled="diasOcupados.has(dia.value)"
                  @click="toggleDia(dia.value)"
                >
                  {{ diaAbreviado(dia.value) }}
                </button>
              </div>
            </div>
            <!-- 2. Rutinas asignadas actualmente (después de días) -->
            <div class="asignar-rutina-modal__rutinas-actuales">
              <h3 class="asignar-rutina-modal__step-title">Rutinas asignadas actualmente</h3>
              <div v-if="cargandoRutinas" class="asignar-rutina-modal__loading">
                Cargando rutinas...
              </div>
              <div v-else-if="rutinasAsignadas.length > 0" class="asignar-rutina-modal__rutinas-list">
                <div
                  v-for="(ra, index) in rutinasAsignadas"
                  :key="`${ra.rutina_id || 'unknown'}-${ra.cliente_id || 'unknown'}-${ra.id || index}`"
                  class="asignar-rutina-modal__rutina-actual"
                >
                  <div class="asignar-rutina-modal__rutina-actual-info">
                    <span class="asignar-rutina-modal__rutina-actual-nombre">
                      {{ ra.nombre || nombreRutina(ra.rutina_id) || `Rutina #${ra.rutina_id || '?'}` }}
                    </span>
                    <span class="asignar-rutina-modal__rutina-actual-dias">
                      {{ diasTexto(ra.dias) }}
                    </span>
                  </div>
                  <button
                    type="button"
                    class="asignar-rutina-modal__quitar-rutina-btn"
                    @click="quitarRutina(ra)"
                  >
                    Quitar
                  </button>
                </div>
              </div>
              <div v-else class="asignar-rutina-modal__empty">
                <p>No hay rutinas asignadas actualmente.</p>
              </div>
            </div>
          </div>
        </div>

        <div class="asignar-rutina-modal__footer">
          <div class="asignar-rutina-modal__footer-left">
            <button
              type="button"
              class="asignar-rutina-modal__btn asignar-rutina-modal__btn--cancel"
              @click="$emit('close')"
            >
              Cancelar
            </button>
            <button
              v-if="paso === 2"
              type="button"
              class="asignar-rutina-modal__btn asignar-rutina-modal__btn--skip"
              @click="omitirCliente"
            >
              Omitir
            </button>
          </div>
          <div class="asignar-rutina-modal__footer-right">
            <button
              v-if="paso === 1"
              type="button"
              class="asignar-rutina-modal__btn asignar-rutina-modal__btn--continue"
              :disabled="clientesSeleccionados.size === 0"
              @click="continuarASeleccionarDias"
            >
              Continuar
            </button>
            <button
              v-if="paso === 2 && props.rutinaId"
              type="button"
              class="asignar-rutina-modal__btn asignar-rutina-modal__btn--save"
              :disabled="guardando || diasSeleccionados.length === 0"
              @click="guardarYContinuar"
            >
              <span v-if="guardando">Guardando...</span>
              <span v-else-if="indiceClienteActual < listaClientesProcesar.length - 1">Continuar</span>
              <span v-else>Finalizar</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.asignar-rutina-modal__overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.75);
  display: flex;
  align-items: flex-end;
  justify-content: center;
  z-index: 1000;
  animation: asignar-rutina-fade 0.2s ease;
}

@keyframes asignar-rutina-fade {
  from { opacity: 0; }
  to { opacity: 1; }
}

.asignar-rutina-modal {
  background: #161616;
  border-radius: 20px 20px 0 0;
  width: 100%;
  max-width: 480px;
  height: 90vh;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  animation: asignar-rutina-slide 0.3s ease;
}

@keyframes asignar-rutina-slide {
  from { transform: translateY(100%); }
  to { transform: translateY(0); }
}

@media (min-width: 640px) {
  .asignar-rutina-modal__overlay {
    align-items: center;
  }
  .asignar-rutina-modal {
    border-radius: 20px;
    height: auto;
    max-height: 70vh;
  }
}

.asignar-rutina-modal__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.25rem;
  border-bottom: 1px solid #252525;
}

.asignar-rutina-modal__title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #fff;
  margin: 0;
}

.asignar-rutina-modal__close {
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

.asignar-rutina-modal__close:hover {
  background: #333;
  color: #fff;
}

.asignar-rutina-modal__close svg {
  width: 18px;
  height: 18px;
}

.asignar-rutina-modal__body {
  padding: 1.25rem;
  overflow-y: auto;
  flex: 1;
}

.asignar-rutina-modal__error {
  background: rgba(239, 92, 92, 0.12);
  border: 1px solid rgba(239, 92, 92, 0.3);
  color: #EF5C5C;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  font-size: 0.875rem;
  margin-bottom: 1rem;
}

.asignar-rutina-modal__step {
  margin-bottom: 1.5rem;
}

.asignar-rutina-modal__step-title {
  font-size: 0.9375rem;
  font-weight: 600;
  color: #fff;
  margin: 0 0 0.75rem 0;
}

.asignar-rutina-modal__search {
  margin-bottom: 1rem;
}

.asignar-rutina-modal__search-input {
  width: 100%;
  padding: 0.625rem 0.875rem;
  font-size: 0.875rem;
  color: #fff;
  background: #1e1e1e;
  border: 1px solid #252525;
  border-radius: 8px;
}

.asignar-rutina-modal__search-input:focus {
  outline: none;
  border-color: #00D261;
  box-shadow: 0 0 0 2px rgba(0, 210, 97, 0.2);
}

.asignar-rutina-modal__search-hint {
  font-size: 0.75rem;
  color: #697586;
  margin: 0.5rem 0 0 0;
}

.asignar-rutina-modal__clientes-list {
  max-height: 300px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.asignar-rutina-modal__cliente-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  background: #1e1e1e;
  border: 1px solid #252525;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.asignar-rutina-modal__cliente-item:hover {
  background: #252525;
  border-color: #00D261;
}

.asignar-rutina-modal__cliente-item--selected {
  background: rgba(0, 210, 97, 0.1);
  border-color: #00D261;
}

.asignar-rutina-modal__cliente-avatar {
  width: 40px;
  height: 40px;
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

.asignar-rutina-modal__cliente-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  min-width: 0;
}

.asignar-rutina-modal__cliente-nombre {
  font-size: 0.875rem;
  font-weight: 500;
  color: #fff;
}

.asignar-rutina-modal__cliente-email {
  font-size: 0.75rem;
  color: #697586;
}

.asignar-rutina-modal__cliente-checkbox input {
  width: 18px;
  height: 18px;
  cursor: pointer;
  accent-color: #00D261;
}


.asignar-rutina-modal__rutinas-actuales {
  margin-bottom: 1.5rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid #252525;
}

.asignar-rutina-modal__rutinas-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.asignar-rutina-modal__rutina-actual {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.75rem;
  background: #1e1e1e;
  border-radius: 8px;
  border: 1px solid #252525;
}

.asignar-rutina-modal__rutina-actual-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  flex: 1;
}

.asignar-rutina-modal__rutina-actual-nombre {
  font-size: 0.875rem;
  font-weight: 500;
  color: #fff;
}

.asignar-rutina-modal__rutina-actual-dias {
  font-size: 0.75rem;
  color: #697586;
}

.asignar-rutina-modal__quitar-rutina-btn {
  padding: 0.375rem 0.625rem;
  font-size: 0.75rem;
  color: #EF5C5C;
  background: transparent;
  border: 1px solid rgba(239, 92, 92, 0.4);
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
}

.asignar-rutina-modal__quitar-rutina-btn:hover {
  background: rgba(239, 92, 92, 0.1);
  border-color: #EF5C5C;
}

.asignar-rutina-modal__dias-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
  gap: 0.5rem;
}

.asignar-rutina-modal__dia-btn {
  padding: 0.625rem 0.875rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #697586;
  background: #1e1e1e;
  border: 1px solid #252525;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.asignar-rutina-modal__dia-btn:hover:not(:disabled) {
  background: #252525;
  border-color: #00D261;
}

.asignar-rutina-modal__dia-btn--selected {
  color: #00D261;
  background: rgba(0, 210, 97, 0.1);
  border-color: #00D261;
}

.asignar-rutina-modal__dia-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  background: rgba(239, 92, 92, 0.15);
  border-color: rgba(239, 92, 92, 0.3);
  color: rgba(239, 92, 92, 0.6);
}

.asignar-rutina-modal__dias-asignacion {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.asignar-rutina-modal__dia-asignacion {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  background: #1e1e1e;
  border-radius: 8px;
  border: 1px solid #252525;
}

.asignar-rutina-modal__dia-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #fff;
  min-width: 80px;
}

.asignar-rutina-modal__dia-rutina {
  flex: 1;
}

.asignar-rutina-modal__select {
  width: 100%;
  padding: 0.625rem 0.875rem;
  font-size: 0.875rem;
  color: #fff;
  background: #1e1e1e;
  border: 1px solid #252525;
  border-radius: 8px;
  cursor: pointer;
}

.asignar-rutina-modal__select:focus {
  outline: none;
  border-color: #00D261;
  box-shadow: 0 0 0 2px rgba(0, 210, 97, 0.2);
}

.asignar-rutina-modal__loading,
.asignar-rutina-modal__empty {
  padding: 2rem;
  text-align: center;
  color: #697586;
  font-size: 0.875rem;
}

.asignar-rutina-modal__footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 1rem 1.25rem;
  padding-bottom: max(1rem, env(safe-area-inset-bottom));
  border-top: 1px solid #252525;
}

.asignar-rutina-modal__footer-left {
  display: flex;
  gap: 0.75rem;
  flex: 0 0 auto;
  justify-content: flex-start;
  min-width: 0;
}

.asignar-rutina-modal__footer-left .asignar-rutina-modal__btn {
  flex: 0 0 auto;
  min-width: 80px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.asignar-rutina-modal__footer-right {
  display: flex;
  gap: 0.75rem;
  flex: 1;
  justify-content: flex-end;
  min-width: 0;
}

.asignar-rutina-modal__footer-right .asignar-rutina-modal__btn {
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.asignar-rutina-modal__btn {
  padding: 0.625rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.asignar-rutina-modal__btn--back,
.asignar-rutina-modal__btn--cancel {
  color: #EF5C5C;
  background: transparent;
  border: 1px solid #EF5C5C;
}

.asignar-rutina-modal__btn--back:hover,
.asignar-rutina-modal__btn--cancel:hover {
  background: rgba(239, 92, 92, 0.1);
}

.asignar-rutina-modal__btn--continue,
.asignar-rutina-modal__btn--save {
  color: #00D261;
  background: #0d2818;
  border: 1px solid #00D261;
  box-shadow: 0 0 8px rgba(0, 210, 97, 0.3);
}

.asignar-rutina-modal__btn--continue:hover:not(:disabled),
.asignar-rutina-modal__btn--save:hover:not(:disabled) {
  background: #0f2e1c;
  box-shadow: 0 0 12px rgba(0, 210, 97, 0.4);
}

.asignar-rutina-modal__btn--continue:disabled,
.asignar-rutina-modal__btn--save:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  box-shadow: none;
}

.asignar-rutina-modal__btn--skip {
  color: #F5A623;
  background: transparent;
  border: 1px solid #F5A623;
}

.asignar-rutina-modal__btn--skip:hover {
  background: rgba(245, 166, 35, 0.1);
}


.asignar-rutina-modal__cliente-actual {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #1e1e1e;
  border-radius: 12px;
  margin-bottom: 1.5rem;
  border: 1px solid #252525;
}

.asignar-rutina-modal__cliente-avatar--large {
  width: 56px;
  height: 56px;
  font-size: 1.25rem;
}

.asignar-rutina-modal__cliente-actual-info {
  flex: 1;
}

.asignar-rutina-modal__cliente-actual-nombre {
  display: block;
  font-size: 1rem;
  font-weight: 600;
  color: #fff;
  margin-bottom: 0.25rem;
}

.asignar-rutina-modal__cliente-actual-email {
  display: block;
  font-size: 0.875rem;
  color: #697586;
  margin: 0;
}
</style>
