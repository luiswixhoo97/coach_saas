<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '@/composables/useApi'
import BaseSegmentedControl from '@/components/ui/BaseSegmentedControl.vue'
import PerfilStatModal from '@/components/coach/PerfilStatModal.vue'

const router = useRouter()
const { get, post, put, cargando } = useApi()
const perfil = ref(null)
const dashboard = ref(null)
const error = ref('')
const linkCopiado = ref(false)
const generando = ref(false)
const linkActivo = ref(false)
const linkInput = ref(null)

const stats = computed(() => {
  if (!dashboard.value) return null
  return {
    clientesTotal: dashboard.value.clientes?.total ?? 0,
    clientesActivos: dashboard.value.clientes?.activos ?? 0,
    nuevoIngreso: dashboard.value.clientes?.nuevo_ingreso ?? 0,
    clientesConDieta: dashboard.value.clientes?.con_dieta ?? 0,
    clientesSinDieta: dashboard.value.clientes?.sin_dieta ?? 0,
    clientesVencimientoProximo: dashboard.value.clientes?.vencimiento_proximo ?? 0,
    suscripcionesActivas: dashboard.value.suscripciones_activas ?? 0,
    ingresosMes: dashboard.value.ingresos_mes ?? 0,
    citasAgendadas: dashboard.value.citas_agendadas ?? 0,
    citasReagendadas: dashboard.value.citas_reagendadas ?? 0,
    evaluacionProxima: dashboard.value.clientes?.evaluacion_proxima ?? 0
  }
})

const statModalActiva = ref(null)
const linkRegistroAbierto = ref(false)
const citasHoy = ref([])
const cargandoCitasHoy = ref(false)

function toggleLinkRegistro() {
  linkRegistroAbierto.value = !linkRegistroAbierto.value
}

const fechaHoyFormateada = computed(() => {
  const d = new Date()
  const day = String(d.getDate()).padStart(2, '0')
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const year = d.getFullYear()
  return `${day}/${month}/${year}`
})

function horaSolo(hora) {
  if (!hora) return '—'
  const s = String(hora)
  const match = s.match(/^(\d{1,2}):(\d{2})/)
  return match ? `${match[1].padStart(2, '0')}:${match[2]}` : s
}

async function cargarCitasHoy() {
  cargandoCitasHoy.value = true
  citasHoy.value = []
  try {
    const hoy = new Date().toISOString().slice(0, 10)
    const res = await get(`/coach/citas-agendadas?fecha=${hoy}`)
    citasHoy.value = res.datos ?? res.data ?? []
  } catch {
    citasHoy.value = []
  } finally {
    cargandoCitasHoy.value = false
  }
}

function avatarUrl(path) {
  if (!path) return null
  const base = (import.meta.env.VITE_API_URL || '').replace(/\/api\/v1\/?$/, '') || window.location.origin
  return `${base}/storage/${path}`
}

function inicialesAvatar(datos) {
  if (!datos?.nombre) return (datos?.email || '?').slice(0, 2).toUpperCase()
  const partes = String(datos.nombre).trim().split(/\s+/)
  if (partes.length >= 2) return (partes[0][0] + partes[partes.length - 1][0]).toUpperCase()
  return datos.nombre.slice(0, 2).toUpperCase()
}

onMounted(async () => {
  try {
    const [resPerfil, resDashboard] = await Promise.all([
      get('/coach/perfil'),
      get('/coach/dashboard').catch(() => ({ datos: null }))
    ])
    perfil.value = resPerfil.datos
    dashboard.value = resDashboard?.datos ?? null
    linkActivo.value = perfil.value?.link_registro_activo ?? false
    await cargarCitasHoy()
  } catch (e) {
    error.value = e.message || 'No se pudo cargar el perfil.'
  }
})

async function generarLink() {
  try {
    generando.value = true
    const response = await post('/coach/perfil/generar-link-registro')
    perfil.value.link_registro = response.datos.link
    perfil.value.token_registro = response.datos.token
    linkActivo.value = true
    perfil.value.link_registro_activo = true
  } catch (e) {
    error.value = e.message || 'Error al generar link'
  } finally {
    generando.value = false
  }
}

async function toggleLink() {
  try {
    await put('/coach/perfil/toggle-link-registro')
    perfil.value.link_registro_activo = linkActivo.value
  } catch (e) {
    linkActivo.value = !linkActivo.value // Revertir
    error.value = e.message || 'Error al cambiar estado del link'
  }
}

function copiarLink() {
  if (linkInput.value) {
    linkInput.value.select()
    document.execCommand('copy')
    linkCopiado.value = true
    setTimeout(() => {
      linkCopiado.value = false
    }, 2000)
  }
}

function irACliente(clienteId) {
  statModalActiva.value = null
  router.push({
    name: 'CoachUsuarios',
    state: { openClienteId: clienteId }
  })
}
</script>

<template>
  <div class="perfil">
    <!-- Error -->
    <div v-if="error" class="perfil__alert">{{ error }}</div>

    <!-- Sin perfil -->
    <div v-else-if="!cargando && !perfil" class="perfil__empty">
      <p class="perfil__empty-title">Sin perfil</p>
      <p class="perfil__empty-desc">No se encontró tu perfil de coach.</p>
    </div>

    <!-- Contenido -->
    <template v-else-if="perfil">
      <!-- Header card (info del coach + sobre mí) -->
      <div class="perfil__header-card">
        <div class="perfil__avatar-wrap">
          <img
            v-if="perfil.avatar && avatarUrl(perfil.avatar)"
            :src="avatarUrl(perfil.avatar)"
            alt=""
            class="perfil__avatar-img"
          />
          <span v-else class="perfil__avatar">{{ inicialesAvatar(perfil) }}</span>
        </div>
        <div class="perfil__header-info">
          <h1 class="perfil__name">{{ perfil.nombre || 'Coach' }}</h1>
          <p class="perfil__email">{{ perfil.email }}</p>
          <p v-if="perfil.bio" class="perfil__header-bio">{{ perfil.bio }}</p>
        </div>
        <span class="perfil__status" :class="{ 'perfil__status--active': perfil.activo }">
          {{ perfil.activo ? 'Activo' : 'Inactivo' }}
        </span>
      </div>

      <!-- Citas de hoy (agendadas y confirmadas) -->
      <section class="perfil__section">
        <div class="perfil__section-header">
          <h2 class="perfil__section-title">Citas de hoy · {{ fechaHoyFormateada }}</h2>
        </div>
        <div v-if="cargandoCitasHoy" class="perfil__citas-loading">
          <span class="perfil__citas-loading-text">Cargando…</span>
        </div>
        <div v-else-if="citasHoy.length === 0" class="perfil__citas-empty">
          <p class="perfil__citas-empty-text">No tienes citas agendadas o confirmadas para hoy.</p>
        </div>
        <ul v-else class="perfil__citas-list">
          <li
            v-for="cita in citasHoy"
            :key="cita.id"
            class="perfil__cita-card"
            role="button"
            tabindex="0"
            @click="irACliente(cita.cliente_id)"
            @keydown.enter.prevent="irACliente(cita.cliente_id)"
            @keydown.space.prevent="irACliente(cita.cliente_id)"
          >
            <span class="perfil__cita-nombre">{{ cita.cliente_nombre }}</span>
            <span class="perfil__cita-hora">{{ horaSolo(cita.hora) }}</span>
            <span class="perfil__cita-estado" :class="`perfil__cita-estado--${cita.estado}`">{{ cita.estado === 'confirmada' ? 'Confirmada' : 'Agendada' }}</span>
          </li>
        </ul>
      </section>

      <!-- Link de Registro (acordeón) -->
      <section class="perfil__section perfil__section--accordion" :class="{ 'perfil__section--open': linkRegistroAbierto }">
        <div
          class="perfil__section-header perfil__section-header--clickable"
          role="button"
          tabindex="0"
          :aria-expanded="linkRegistroAbierto"
          @click="toggleLinkRegistro"
          @keydown.enter.prevent="toggleLinkRegistro"
          @keydown.space.prevent="toggleLinkRegistro"
        >
          <h2 class="perfil__section-title">Link de Registro</h2>
          <svg
            class="perfil__section-chevron"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
          >
            <path d="M9 18l6-6-6-6"/>
          </svg>
        </div>
        <div class="perfil__accordion-content" :class="{ 'perfil__accordion-content--collapsed': !linkRegistroAbierto }">
          <div class="perfil__link-registro">
            <p class="perfil__link-registro-desc">
              Comparte este link para que nuevos clientes se registren
            </p>
            
            <div v-if="perfil.link_registro" class="perfil__link-registro-container">
              <input
                type="text"
                :value="perfil.link_registro"
                readonly
                class="perfil__link-input"
                ref="linkInput"
              />
              <button
                @click="copiarLink"
                class="perfil__link-btn"
              >
                {{ linkCopiado ? 'Copiado' : 'Copiar' }}
              </button>
            </div>
            
            <div v-else class="perfil__link-registro-empty">
              <p class="perfil__link-registro-empty-text">No tienes un link de registro generado</p>
            </div>
            
            <div class="perfil__link-registro-actions">
              <button
                @click="generarLink"
                class="perfil__btn perfil__btn--outline"
                :disabled="generando"
              >
                {{ perfil.link_registro ? 'Regenerar Link' : 'Generar Link' }}
              </button>
              
              <label v-if="perfil.link_registro" class="perfil__link-toggle">
                <input
                  type="checkbox"
                  v-model="linkActivo"
                  @change="toggleLink"
                />
                <span>Link activo</span>
              </label>
            </div>
          </div>
        </div>
      </section>

      <!-- Estadísticas -->
      <section class="perfil__section">
        <div class="perfil__section-header">
          <h2 class="perfil__section-title">Estadísticas</h2>
        </div>
        <div class="perfil__details" v-if="stats">
          <div
            class="perfil__detail perfil__detail--clickable"
            role="button"
            tabindex="0"
            @click="statModalActiva = 'clientes'"
            @keydown.enter.prevent="statModalActiva = 'clientes'"
            @keydown.space.prevent="statModalActiva = 'clientes'"
          >
            <div class="perfil__detail-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
              </svg>
            </div>
            <span class="perfil__detail-value">{{ stats.clientesTotal }}</span>
            <span class="perfil__detail-label">Clientes</span>
          </div>
          <div
            class="perfil__detail perfil__detail--clickable"
            role="button"
            tabindex="0"
            @click="statModalActiva = 'activos'"
            @keydown.enter.prevent="statModalActiva = 'activos'"
            @keydown.space.prevent="statModalActiva = 'activos'"
          >
            <div class="perfil__detail-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <path d="M22 4L12 14.01l-3-3"/>
              </svg>
            </div>
            <span class="perfil__detail-value">{{ stats.clientesActivos }}</span>
            <span class="perfil__detail-label">Activos</span>
          </div>
          <div
            class="perfil__detail perfil__detail--clickable"
            role="button"
            tabindex="0"
            @click="statModalActiva = 'evaluacion_proxima'"
            @keydown.enter.prevent="statModalActiva = 'evaluacion_proxima'"
            @keydown.space.prevent="statModalActiva = 'evaluacion_proxima'"
          >
            <div class="perfil__detail-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
                <rect x="9" y="3" width="6" height="4" rx="2"/>
                <path d="M9 12h6M9 16h6"/>
              </svg>
            </div>
            <span class="perfil__detail-value">{{ stats.evaluacionProxima }}</span>
            <span class="perfil__detail-label">Próximo a evaluación</span>
          </div>
          <div
            class="perfil__detail perfil__detail--clickable"
            role="button"
            tabindex="0"
            @click="statModalActiva = 'nuevo_ingreso'"
            @keydown.enter.prevent="statModalActiva = 'nuevo_ingreso'"
            @keydown.space.prevent="statModalActiva = 'nuevo_ingreso'"
          >
            <div class="perfil__detail-icon perfil__detail-icon--warning">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <line x1="19" y1="8" x2="19" y2="14"/>
                <line x1="22" y1="11" x2="16" y2="11"/>
              </svg>
            </div>
            <span class="perfil__detail-value">{{ stats.nuevoIngreso }}</span>
            <span class="perfil__detail-label">Nuevo ingreso</span>
          </div>
          <div
            class="perfil__detail perfil__detail--clickable"
            role="button"
            tabindex="0"
            @click="statModalActiva = 'con_dieta'"
            @keydown.enter.prevent="statModalActiva = 'con_dieta'"
            @keydown.space.prevent="statModalActiva = 'con_dieta'"
          >
            <div class="perfil__detail-icon perfil__detail-icon--success">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="16" y1="13" x2="8" y2="13"/>
                <line x1="16" y1="17" x2="8" y2="17"/>
              </svg>
            </div>
            <span class="perfil__detail-value">{{ stats.clientesConDieta }}</span>
            <span class="perfil__detail-label">Con dieta</span>
          </div>
          <div
            class="perfil__detail perfil__detail--clickable"
            role="button"
            tabindex="0"
            @click="statModalActiva = 'sin_dieta'"
            @keydown.enter.prevent="statModalActiva = 'sin_dieta'"
            @keydown.space.prevent="statModalActiva = 'sin_dieta'"
          >
            <div class="perfil__detail-icon perfil__detail-icon--warning">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="16" y1="13" x2="8" y2="13"/>
                <line x1="16" y1="17" x2="8" y2="17"/>
              </svg>
            </div>
            <span class="perfil__detail-value">{{ stats.clientesSinDieta }}</span>
            <span class="perfil__detail-label">Sin dieta</span>
          </div>
          <div
            class="perfil__detail perfil__detail--clickable"
            role="button"
            tabindex="0"
            @click="statModalActiva = 'suscripciones'"
            @keydown.enter.prevent="statModalActiva = 'suscripciones'"
            @keydown.space.prevent="statModalActiva = 'suscripciones'"
          >
            <div class="perfil__detail-icon perfil__detail-icon--success">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="12" cy="12" r="10"/>
                <path d="M12 6v6l4 2"/>
              </svg>
            </div>
            <span class="perfil__detail-value">{{ stats.suscripcionesActivas }}</span>
            <span class="perfil__detail-label">Suscripciones</span>
          </div>
          <div
            class="perfil__detail perfil__detail--clickable"
            role="button"
            tabindex="0"
            @click="statModalActiva = 'vence_pronto'"
            @keydown.enter.prevent="statModalActiva = 'vence_pronto'"
            @keydown.space.prevent="statModalActiva = 'vence_pronto'"
          >
            <div class="perfil__detail-icon" :class="{ 'perfil__detail-icon--warning': stats.clientesVencimientoProximo > 0 }">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="12" cy="12" r="10"/>
                <path d="M12 6v6l4 2"/>
              </svg>
            </div>
            <span class="perfil__detail-value">{{ stats.clientesVencimientoProximo }}</span>
            <span class="perfil__detail-label">Vence pronto</span>
          </div>
          <div
            class="perfil__detail perfil__detail--clickable"
            role="button"
            tabindex="0"
            @click="statModalActiva = 'citas_agendadas'"
            @keydown.enter.prevent="statModalActiva = 'citas_agendadas'"
            @keydown.space.prevent="statModalActiva = 'citas_agendadas'"
          >
            <div class="perfil__detail-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/>
              </svg>
            </div>
            <span class="perfil__detail-value">{{ stats.citasAgendadas }}</span>
            <span class="perfil__detail-label">Citas agendadas</span>
          </div>
          <div
            class="perfil__detail perfil__detail--clickable"
            role="button"
            tabindex="0"
            @click="statModalActiva = 'citas_reagendadas'"
            @keydown.enter.prevent="statModalActiva = 'citas_reagendadas'"
            @keydown.space.prevent="statModalActiva = 'citas_reagendadas'"
          >
            <div class="perfil__detail-icon perfil__detail-icon--reagendar">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="3" y="4" width="18" height="18" rx="2"/>
                <path d="M16 2v4M8 2v4M3 10h18"/>
                <path d="M8 14h.01M12 14h.01M16 14h.01"/>
              </svg>
            </div>
            <span class="perfil__detail-value">{{ stats.citasReagendadas }}</span>
            <span class="perfil__detail-label">Citas reagendadas</span>
          </div>
          <div
            class="perfil__detail perfil__detail--clickable perfil__detail--full"
            role="button"
            tabindex="0"
            @click="statModalActiva = 'ingresos_mes'"
            @keydown.enter.prevent="statModalActiva = 'ingresos_mes'"
            @keydown.space.prevent="statModalActiva = 'ingresos_mes'"
          >
            <div class="perfil__detail-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <line x1="12" y1="1" x2="12" y2="23"/>
                <path d="M17 5 H9.5 a3.5 3.5 0 0 0 0 7 h5 a3.5 3.5 0 0 1 0 7 H6"/>
              </svg>
            </div>
            <span class="perfil__detail-value">{{ stats.ingresosMes != null ? `$${Number(stats.ingresosMes).toLocaleString()}` : '—' }}</span>
            <span class="perfil__detail-label">Ingresos mes</span>
          </div>
        </div>
        <div class="perfil__otros-placeholder" v-else>
          <p class="perfil__otros-text">Cargando estadísticas…</p>
        </div>
      </section>

      <!-- Modal detalle estadística (bottom sheet con lista y navegación) -->
      <PerfilStatModal
        :stat-key="statModalActiva"
        :stats="stats"
        @close="statModalActiva = null"
        @select-cliente="irACliente"
      />
    </template>

    <!-- Loading -->
    <div v-else-if="cargando" class="perfil__loading">
      <div class="perfil__header-card perfil__skeleton">
        <div class="perfil__avatar perfil__skeleton-box"></div>
        <div class="perfil__header-info">
          <div class="perfil__skeleton-line" style="width: 140px; height: 20px;"></div>
          <div class="perfil__skeleton-line" style="width: 180px; height: 14px; margin-top: 8px;"></div>
        </div>
      </div>
      <div class="perfil__section perfil__skeleton">
        <div class="perfil__skeleton-line" style="width: 80px; height: 16px;"></div>
        <div class="perfil__details" style="margin-top: 16px;">
          <div class="perfil__detail perfil__skeleton-box"></div>
          <div class="perfil__detail perfil__skeleton-box"></div>
          <div class="perfil__detail perfil__skeleton-box"></div>
          <div class="perfil__detail perfil__skeleton-box"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Base */
.perfil {
  min-height: 100vh;
  min-height: 100dvh;
  background: #0a0a0a;
  padding: 1rem;
  padding-top: max(1rem, env(safe-area-inset-top));
  padding-bottom: max(2rem, env(safe-area-inset-bottom));
}

.perfil__alert {
  background: rgba(239, 92, 92, 0.12);
  border: 1px solid rgba(239, 92, 92, 0.3);
  color: #EF5C5C;
  padding: 0.875rem 1rem;
  border-radius: 16px;
  font-size: 0.875rem;
  margin-bottom: 1rem;
}

.perfil__empty {
  text-align: center;
  padding: 4rem 1rem;
}
.perfil__empty-title {
  font-size: 1.125rem;
  font-weight: 500;
  color: #fff;
  margin: 0 0 0.5rem;
}
.perfil__empty-desc {
  font-size: 0.875rem;
  color: #697586;
  margin: 0;
}

/* Header card */
.perfil__header-card {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  background: #161616;
  border-radius: 16px;
  padding: 1rem;
  margin-bottom: 1rem;
}
.perfil__avatar-wrap {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  overflow: hidden;
  flex-shrink: 0;
  background: linear-gradient(135deg, #00D261 0%, #00b355 100%);
}
.perfil__avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.perfil__avatar {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: linear-gradient(135deg, #00D261 0%, #00b355 100%);
  color: #fff;
  font-size: 1.125rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.perfil__header-info {
  flex: 1;
  min-width: 0;
}
.perfil__name {
  font-size: 1.125rem;
  font-weight: 600;
  color: #fff;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.perfil__email {
  font-size: 0.8125rem;
  color: #697586;
  margin: 0.25rem 0 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.perfil__header-bio {
  font-size: 0.8125rem;
  color: var(--color-label-secondary);
  line-height: 1.45;
  margin: 0.5rem 0 0;
  white-space: pre-wrap;
  word-break: break-word;
}
.perfil__status {
  font-size: 0.6875rem;
  font-weight: 500;
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
  background: rgba(105, 117, 134, 0.2);
  color: #697586;
  text-transform: uppercase;
  letter-spacing: 0.02em;
}
.perfil__status--active {
  background: rgba(0, 210, 97, 0.15);
  color: #00D261;
}

/* Citas de hoy */
.perfil__citas-loading,
.perfil__citas-empty {
  padding: 1rem;
  text-align: center;
}
.perfil__citas-loading-text {
  font-size: 0.875rem;
  color: var(--color-label-secondary);
}
.perfil__citas-empty-text {
  font-size: 0.875rem;
  color: var(--color-label-secondary);
  margin: 0;
}
.perfil__citas-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.perfil__cita-card {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  background: #1e1e1e;
  border-radius: 12px;
  cursor: pointer;
  transition: background 0.2s;
}
.perfil__cita-card:hover {
  background: rgba(255, 255, 255, 0.03);
}
.perfil__cita-hora {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-success-500);
  flex-shrink: 0;
  min-width: 2.5rem;
}
.perfil__cita-nombre {
  font-size: 0.875rem;
  color: var(--color-label-tertiary);
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
}
.perfil__cita-estado {
  font-size: 0.6875rem;
  font-weight: 500;
  padding: 0.2rem 0.5rem;
  border-radius: 6px;
  text-transform: capitalize;
}
.perfil__cita-estado--agendada {
  background: color-mix(in srgb, var(--color-warning-500) 18%, transparent);
  color: var(--color-warning-500);
}
.perfil__cita-estado--confirmada {
  background: color-mix(in srgb, var(--color-success-500) 18%, transparent);
  color: var(--color-success-500);
}

/* Section */
.perfil__tabs-wrap {
  margin-bottom: 0.75rem;
}
.perfil__section {
  background: #161616;
  border-radius: 16px;
  padding: 1rem;
  margin-bottom: 0.75rem;
}
.perfil__section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.75rem;
}
.perfil__section-header--clickable {
  cursor: pointer;
  margin-bottom: 0;
  padding: 0.125rem 0;
  user-select: none;
}
.perfil__section--accordion .perfil__section-header--clickable {
  margin-bottom: 0;
}
.perfil__section--accordion .perfil__section-header--clickable + .perfil__accordion-content {
  margin-top: 0;
}
.perfil__section--accordion.perfil__section--open .perfil__section-header--clickable + .perfil__accordion-content {
  margin-top: 0.75rem;
}
.perfil__section-chevron {
  width: 1.25rem;
  height: 1.25rem;
  flex-shrink: 0;
  color: var(--color-label-secondary);
  transition: transform 0.2s ease;
}
.perfil__section--accordion.perfil__section--open .perfil__section-chevron {
  transform: rotate(90deg);
}

/* Configuración */
.perfil__config {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.perfil__config-desc {
  font-size: 0.8125rem;
  color: var(--color-label-secondary);
  margin: 0 0 0.25rem;
  line-height: 1.4;
}
.perfil__config-field {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}
.perfil__config-field label {
  font-size: 0.8125rem;
  color: #697586;
}
.perfil__config-input {
  background: #1e1e1e;
  border: 1px solid #333;
  border-radius: 10px;
  padding: 0.625rem 0.75rem;
  font-size: 0.875rem;
  color: #fff;
}
.perfil__config-input:focus {
  outline: none;
  border-color: var(--color-success-500);
}
.perfil__config-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-top: 0.5rem;
}
.perfil__config-ok {
  font-size: 0.8125rem;
  color: var(--color-success-500);
}

.perfil__accordion-content {
  overflow: hidden;
  max-height: 800px;
  transition: max-height 0.25s ease, opacity 0.2s ease;
}
.perfil__accordion-content--collapsed {
  max-height: 0;
  opacity: 0;
  margin-top: 0 !important;
}
.perfil__section-title {
  font-size: 0.9375rem;
  font-weight: 600;
  color: #fff;
  margin: 0;
}

/* Details grid */
.perfil__details {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.75rem;
}
.perfil__detail {
  background: #1e1e1e;
  border-radius: 12px;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
}
.perfil__detail-icon {
  width: 28px;
  height: 28px;
  color: #00D261;
}
.perfil__detail-icon svg {
  width: 100%;
  height: 100%;
}
.perfil__detail-icon--success {
  color: var(--color-success-500);
}
.perfil__detail-icon--warning {
  color: var(--color-warning-500);
}
.perfil__detail-icon--reagendar {
  color: #A855F7;
}
.perfil__detail--full {
  grid-column: 1 / -1;
}
.perfil__detail-value {
  font-size: 1rem;
  font-weight: 600;
  color: #fff;
  text-align: center;
}
.perfil__detail-label {
  font-size: 0.6875rem;
  color: #697586;
  text-transform: uppercase;
  letter-spacing: 0.02em;
}
.perfil__detail--clickable {
  cursor: pointer;
  transition: background 0.2s;
}
.perfil__detail--clickable:hover {
  background: #252525;
}
.perfil__detail--clickable:focus-visible {
  outline: none;
  box-shadow: 0 0 0 2px rgba(0, 210, 97, 0.4);
}

.perfil__otros-placeholder {
  background: #1e1e1e;
  border-radius: 12px;
  padding: 1rem;
}
.perfil__otros-text {
  font-size: 0.875rem;
  color: #697586;
  margin: 0;
}

/* Link de Registro */
.perfil__link-registro {
  background: #1e1e1e;
  border-radius: 12px;
  padding: 1rem;
}

.perfil__link-registro-desc {
  font-size: 0.8125rem;
  color: #697586;
  margin: 0 0 1rem;
}

.perfil__link-registro-empty {
  background: #161616;
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 1rem;
  text-align: center;
}

.perfil__link-registro-empty-text {
  font-size: 0.8125rem;
  color: #697586;
  margin: 0;
}

.perfil__link-registro-container {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.perfil__link-input {
  flex: 1;
  background: #161616;
  border: 1px solid #252525;
  border-radius: 8px;
  padding: 0.625rem 0.875rem;
  font-size: 0.8125rem;
  color: #fff;
  font-family: monospace;
  transition: border-color 0.2s;
}

.perfil__link-input:focus {
  outline: none;
  border-color: #00D261;
  box-shadow: 0 0 0 2px rgba(0, 210, 97, 0.2);
}

.perfil__link-btn {
  padding: 0.625rem 1rem;
  background: rgba(0, 210, 97, 0.1);
  border: 1px solid rgba(0, 210, 97, 0.3);
  border-radius: 8px;
  color: #00D261;
  font-size: 0.8125rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.perfil__link-btn:hover {
  background: rgba(0, 210, 97, 0.2);
  border-color: #00D261;
}

.perfil__link-registro-actions {
  display: flex;
  gap: 0.75rem;
  align-items: center;
}

.perfil__link-toggle {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.8125rem;
  color: #a0a0a0;
  cursor: pointer;
}

.perfil__link-toggle input[type="checkbox"] {
  width: 18px;
  height: 18px;
  accent-color: #00D261;
  cursor: pointer;
}

/* Actions */
.perfil__actions {
  display: flex;
  gap: 0.75rem;
  margin-top: 1.5rem;
}
.perfil__btn {
  flex: 1;
  padding: 0.875rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s;
  text-align: center;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: none;
}
.perfil__btn--primary {
  background: var(--color-success-500, #00D261);
  color: #0a0a0a;
}
.perfil__btn--primary:hover:not(:disabled) {
  background: #00e56b;
}
.perfil__btn--outline {
  background: transparent;
  border: 1px solid rgba(0, 210, 97, 0.4);
  color: #00D261;
}
.perfil__btn--outline:hover {
  background: rgba(0, 210, 97, 0.1);
  border-color: #00D261;
}
.perfil__btn--danger {
  background: transparent;
  border: 1px solid rgba(239, 92, 92, 0.4);
  color: #EF5C5C;
}
.perfil__btn--danger:hover:not(:disabled) {
  background: rgba(239, 92, 92, 0.1);
  border-color: #EF5C5C;
}
.perfil__btn--danger:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.perfil__spinner {
  width: 1rem;
  height: 1rem;
  border: 2px solid rgba(239, 92, 92, 0.3);
  border-top-color: #EF5C5C;
  border-radius: 50%;
  animation: perfil-spin 0.7s linear infinite;
}
@keyframes perfil-spin {
  to { transform: rotate(360deg); }
}

/* Loading / Skeleton */
.perfil__loading {
  padding-top: 1rem;
}
.perfil__skeleton-box {
  background: #252525 !important;
}
.perfil__skeleton-line {
  background: #252525;
  border-radius: 6px;
  animation: perfil-pulse 1.5s ease-in-out infinite;
}
.perfil__skeleton .perfil__detail {
  height: 90px;
}
@keyframes perfil-pulse {
  0%, 100% { opacity: 0.5; }
  50% { opacity: 1; }
}

</style>
