<script setup>
/**
 * Modal tipo bottom sheet para mostrar el detalle de una estadística del perfil.
 * Muestra el número y la lista (clientes o citas); al seleccionar un item navega al cliente.
 * Ingresos mes: solo valor y descripción, sin lista.
 */
import { ref, computed, watch } from 'vue'
import { useApi } from '@/composables/useApi'

const props = defineProps({
  statKey: { type: String, default: null },
  stats: { type: Object, default: null }
})

const emit = defineEmits(['close', 'select-cliente'])

const { get } = useApi()
const loading = ref(false)
const error = ref('')
const items = ref([]) // clientes o citas según statKey

const STAT_META = {
  clientes: { titulo: 'Clientes', descripcion: 'Total de clientes registrados en tu cuenta.', api: () => get('/coach/clientes?per_page=100') },
  activos: { titulo: 'Activos', descripcion: 'Clientes con estado activo que pueden acceder a la app.', api: () => get('/coach/clientes?per_page=100&activo=1') },
  nuevo_ingreso: { titulo: 'Nuevo ingreso', descripcion: 'Personas que se inscribieron por transferencia o pasarela y están pendientes de que actives su cuenta.', api: () => get('/coach/clientes?per_page=100&nuevo_ingreso=1') },
  con_dieta: { titulo: 'Con dieta', descripcion: 'Clientes que tienen al menos un archivo de dieta asignado.', api: () => get('/coach/clientes?per_page=100&con_dieta=1') },
  sin_dieta: { titulo: 'Sin dieta', descripcion: 'Clientes que aún no tienen asignado un archivo de dieta.', api: () => get('/coach/clientes?per_page=100&sin_dieta=1') },
  suscripciones: { titulo: 'Suscripciones', descripcion: 'Clientes con suscripción activa.', api: () => get('/coach/clientes?per_page=100&suscripcion_activa=1') },
  vence_pronto: { titulo: 'Vence pronto', descripcion: 'Clientes cuya suscripción vence próximamente.', api: () => get('/coach/clientes?per_page=100&vencimiento_proximo=1') },
  citas_agendadas: { titulo: 'Citas agendadas', descripcion: 'Evaluaciones programadas pendientes.', api: () => get('/coach/citas-agendadas') },
  citas_reagendadas: { titulo: 'Citas reagendadas', descripcion: 'Evaluaciones que requieren nueva fecha.', api: () => get('/coach/citas-agendadas?estado=reagendar') },
  evaluacion_proxima: { titulo: 'Próximo a evaluación', descripcion: 'Clientes que cumplen el margen de tiempo para agendar su próxima evaluación (sin cita pendiente).', api: () => get('/coach/evaluacion-proxima') },
  ingresos_mes: { titulo: 'Ingresos mes', descripcion: 'Ingresos por pagos de suscripciones en el mes actual.' }
}

const meta = computed(() => props.statKey ? STAT_META[props.statKey] : null)
const isSoloValor = computed(() => props.statKey === 'ingresos_mes')
const valorActual = computed(() => {
  if (!props.stats) return '—'
  const s = props.stats
  const map = {
    clientes: s.clientesTotal,
    activos: s.clientesActivos,
    nuevo_ingreso: s.nuevoIngreso,
    con_dieta: s.clientesConDieta,
    sin_dieta: s.clientesSinDieta,
    suscripciones: s.suscripcionesActivas,
    vence_pronto: s.clientesVencimientoProximo,
    ingresos_mes: s.ingresosMes != null ? `$${Number(s.ingresosMes).toLocaleString()}` : '—',
    citas_agendadas: s.citasAgendadas,
    citas_reagendadas: s.citasReagendadas,
    evaluacion_proxima: s.evaluacionProxima
  }
  return map[props.statKey] ?? '—'
})

function nombreCompleto(c) {
  if (!c) return '—'
  const partes = [c.nombre, c.apellido_paterno, c.apellido_materno].filter(Boolean)
  return partes.join(' ').trim() || c.email || '—'
}

function getClienteId(item) {
  if (props.statKey === 'citas_agendadas' || props.statKey === 'citas_reagendadas') return item.cliente_id
  return item.id
}

function getItemLabel(item) {
  if (props.statKey === 'citas_agendadas' || props.statKey === 'citas_reagendadas') {
    const fecha = item.fecha ? new Date(item.fecha).toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' }) : '—'
    return `${item.cliente_nombre || '—'} · ${fecha} ${item.hora || ''}`
  }
  if (props.statKey === 'evaluacion_proxima' && item.nombre_completo) return item.nombre_completo
  return nombreCompleto(item)
}

async function cargarDatos() {
  if (!props.statKey || isSoloValor.value) {
    items.value = []
    return
  }
  const config = STAT_META[props.statKey]
  if (!config?.api) {
    items.value = []
    return
  }
  loading.value = true
  error.value = ''
  items.value = []
  try {
    const res = await config.api()
    if (props.statKey === 'citas_agendadas' || props.statKey === 'citas_reagendadas' || props.statKey === 'evaluacion_proxima') {
      items.value = res.datos ?? res.data ?? []
    } else {
      const data = res.data?.datos ?? res.datos ?? res.data ?? []
      items.value = Array.isArray(data) ? data : []
    }
  } catch (e) {
    error.value = e.message || 'No se pudo cargar la lista.'
    items.value = []
  } finally {
    loading.value = false
  }
}

function onSelect(item) {
  const id = getClienteId(item)
  if (id) {
    emit('select-cliente', id)
    emit('close')
  }
}

watch(() => props.statKey, (key) => {
  if (key) cargarDatos()
  else items.value = []
})
</script>

<template>
  <Teleport to="body">
    <div
      v-if="statKey && meta"
      class="stat-modal__overlay"
      @click.self="$emit('close')"
    >
      <div class="stat-modal">
        <div class="stat-modal__header">
          <div class="stat-modal__header-left">
            <h2 class="stat-modal__title">
              {{ meta.titulo }}
              <span v-if="stats" class="stat-modal__count">  : {{ valorActual }}</span>
            </h2>
          </div>
          <button
            type="button"
            class="stat-modal__close"
            aria-label="Cerrar"
            @click="$emit('close')"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 6L6 18M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <div class="stat-modal__body">
          <p class="stat-modal__desc">{{ meta.descripcion }}</p>

          <!-- Solo valor (ingresos): card con número y cerrar -->
          <template v-if="isSoloValor">
            <div class="stat-modal__card stat-modal__card--solo">
              <span class="stat-modal__card-value">{{ valorActual }}</span>
            </div>
            <button
              type="button"
              class="stat-modal__btn stat-modal__btn--outline"
              @click="$emit('close')"
            >
              Cerrar
            </button>
          </template>

          <!-- Con lista: cards -->
          <template v-else>
            <div v-if="loading" class="stat-modal__loading">
              <div class="stat-modal__skeleton" />
              <div class="stat-modal__skeleton" />
              <div class="stat-modal__skeleton" />
            </div>
            <p v-else-if="error" class="stat-modal__error">{{ error }}</p>
            <ul v-else-if="items.length" class="stat-modal__list">
              <li
                v-for="item in items"
                :key="item.id"
                class="stat-modal__card stat-modal__card--item"
                role="button"
                tabindex="0"
                @click="onSelect(item)"
                @keydown.enter.prevent="onSelect(item)"
                @keydown.space.prevent="onSelect(item)"
              >
                <span class="stat-modal__card-label">{{ getItemLabel(item) }}</span>
                <svg class="stat-modal__card-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M9 18l6-6-6-6"/>
                </svg>
              </li>
            </ul>
            <p v-else class="stat-modal__empty">No hay elementos para mostrar.</p>
            <button
              v-if="!loading"
              type="button"
              class="stat-modal__btn stat-modal__btn--outline"
              @click="$emit('close')"
            >
              Cerrar
            </button>
          </template>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.stat-modal__overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.75);
  display: flex;
  align-items: flex-end;
  justify-content: center;
  z-index: 1000;
  animation: stat-modal-fade 0.2s ease;
}

@keyframes stat-modal-fade {
  from { opacity: 0; }
  to { opacity: 1; }
}

.stat-modal {
  background: #161616;
  border-radius: 20px 20px 0 0;
  width: 100%;
  max-width: 480px;
  max-height: 85vh;
  display: flex;
  flex-direction: column;
  animation: stat-modal-slide 0.3s ease;
}

@keyframes stat-modal-slide {
  from { transform: translateY(100%); }
  to { transform: translateY(0); }
}

@media (min-width: 640px) {
  .stat-modal__overlay {
    align-items: center;
  }
  .stat-modal {
    border-radius: 20px;
    max-height: 70vh;
  }
}

.stat-modal__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.25rem 1.25rem 0;
}

.stat-modal__header-left {
  flex: 1;
  min-width: 0;
}

.stat-modal__title {
  font-size: var(--font-size-lg);
  font-weight: 600;
  color: var(--color-label-tertiary);
  margin: 0;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  display: flex;
  align-items: baseline;
  flex-wrap: wrap;
  gap: 0.35rem;
}

.stat-modal__count {
  font-size: var(--font-size-lg);
  font-weight: 700;
  color: var(--color-success-500);
}

.stat-modal__close {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: none;
  background: #2a2a2a;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: var(--color-label-secondary);
  flex-shrink: 0;
  transition: background 0.2s, color 0.2s;
}

.stat-modal__close:hover {
  background: #333;
  color: var(--color-label-tertiary);
}

.stat-modal__close svg {
  width: 18px;
  height: 18px;
}

.stat-modal__body {
  padding: 1rem 1.25rem;
  padding-bottom: max(1rem, env(safe-area-inset-bottom));
  overflow-y: auto;
  flex: 1;
}

.stat-modal__desc {
  font-size: var(--font-size-sm);
  color: var(--color-label-secondary);
  line-height: 1.45;
  margin: 0 0 1rem;
}

.stat-modal__loading {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.stat-modal__skeleton {
  height: 2.5rem;
  background: #252525;
  border-radius: var(--radius-xl);
  animation: stat-modal-pulse 1.5s ease-in-out infinite;
}

@keyframes stat-modal-pulse {
  0%, 100% { opacity: 0.5; }
  50% { opacity: 1; }
}

.stat-modal__error {
  font-size: var(--font-size-sm);
  color: var(--color-danger-500);
  margin: 0 0 1rem;
}

.stat-modal__list {
  list-style: none;
  margin: 0 0 1rem;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

/* Cards (estilo vista usuarios) */
.stat-modal__card {
  background: #1e1e1e;
  border-radius: 12px;
  padding: 0.875rem 1rem;
  transition: background 0.2s;
}

.stat-modal__card--solo {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.25rem;
  margin-bottom: 0.5rem;
}

.stat-modal__card-value {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--color-success-500);
}

.stat-modal__card--item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  cursor: pointer;
  text-decoration: none;
  color: inherit;
}

.stat-modal__card--item:hover {
  background: rgba(255, 255, 255, 0.03);
}

.stat-modal__card-label {
  font-size: var(--font-size-sm);
  color: var(--color-label-tertiary);
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
}

.stat-modal__card-chevron {
  width: 1.25rem;
  height: 1.25rem;
  flex-shrink: 0;
  color: var(--color-label-secondary);
}

.stat-modal__empty {
  font-size: var(--font-size-sm);
  color: var(--color-label-secondary);
  margin: 0 0 1rem;
}

.stat-modal__btn {
  width: 100%;
  padding: 0.875rem 1rem;
  font-size: var(--font-size-sm);
  font-weight: 500;
  border-radius: var(--radius-xl);
  cursor: pointer;
  border: 1px solid color-mix(in srgb, var(--color-success-500) 40%, transparent);
  color: var(--color-success-500);
  background: transparent;
  transition: background 0.2s, border-color 0.2s;
}

.stat-modal__btn:hover {
  background: color-mix(in srgb, var(--color-success-500) 10%, transparent);
  border-color: var(--color-success-500);
}

.stat-modal__btn--outline {
  margin-top: 0.5rem;
}
</style>
