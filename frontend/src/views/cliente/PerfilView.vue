<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useApi } from '@/composables/useApi'
import { useAuth } from '@/composables/useAuth'
import BaseSegmentedControl from '@/components/ui/BaseSegmentedControl.vue'
import CircularProgress from '@/components/stats/CircularProgress.vue'
import HistorialModal from '@/components/stats/HistorialModal.vue'

const { get, cargando } = useApi()
const { logout } = useAuth()
const perfil = ref(null)
const error = ref('')
const cerrandoSesion = ref(false)

// Estadísticas
const estadisticasRaw = ref([])
const modalHistorial = ref(false)
const parametroSeleccionado = ref(null)

// Tabs: Estadísticas | Otros
const tabSeleccionado = ref('estadisticas')
const opcionesTab = [
  { value: 'estadisticas', label: 'Estadísticas' },
  { value: 'otros', label: 'Otros' }
]

// Parámetros que queremos mostrar con sus metas
const parametrosConfig = {
  'Peso': { meta: 65, orden: 1 },
  'IMC': { meta: 24, orden: 2 },
  'Grasa corporal': { meta: 15, orden: 3 }
}

const estadisticas = computed(() => {
  return estadisticasRaw.value
    .filter(p => parametrosConfig[p.nombre])
    .map(p => ({
      ...p,
      meta: parametrosConfig[p.nombre].meta,
      orden: parametrosConfig[p.nombre].orden,
      valorActual: p.datos?.length ? p.datos[p.datos.length - 1].valor : null,
      historial: p.datos || []
    }))
    .sort((a, b) => a.orden - b.orden)
})

function abrirHistorial(stat) {
  parametroSeleccionado.value = {
    nombre: stat.nombre,
    unidad: stat.unidad,
    historial: stat.historial
  }
  modalHistorial.value = true
}

async function handleLogout() {
  cerrandoSesion.value = true
  await logout()
  cerrandoSesion.value = false
}

function nombreCompleto(datos) {
  if (!datos) return ''
  const partes = [datos.nombre, datos.apellido_paterno, datos.apellido_materno].filter(Boolean)
  return partes.join(' ')
}

function inicialesAvatar(datos) {
  const nombre = nombreCompleto(datos) || datos?.email || '?'
  const partes = nombre.trim().split(/\s+/)
  if (partes.length >= 2) return (partes[0][0] + partes[partes.length - 1][0]).toUpperCase()
  return nombre.slice(0, 2).toUpperCase()
}

onMounted(async () => {
  try {
    // Cargar perfil y estadísticas en paralelo
    const [resPerfil, resProgreso] = await Promise.all([
      get('/cliente/perfil'),
      get('/cliente/progreso').catch(() => ({ datos: [] }))
    ])
    perfil.value = resPerfil.datos
    estadisticasRaw.value = resProgreso.datos || []
  } catch (e) {
    error.value = e.message || 'No se pudo cargar el perfil.'
  }
})
</script>

<template>
  <div class="perfil">
    <!-- Error -->
    <div v-if="error" class="perfil__alert">{{ error }}</div>

    <!-- Sin perfil -->
    <div v-else-if="!cargando && !perfil" class="perfil__empty">
      <p class="perfil__empty-title">Sin perfil</p>
      <p class="perfil__empty-desc">No se encontró tu perfil. Contacta a tu entrenador.</p>
    </div>

    <!-- Contenido -->
    <template v-else-if="perfil">
      <!-- Header card -->
      <div class="perfil__header-card">
        <div class="perfil__avatar">{{ inicialesAvatar(perfil) }}</div>
        <div class="perfil__header-info">
          <h1 class="perfil__name">{{ nombreCompleto(perfil) || 'Cliente' }}</h1>
          <p class="perfil__email">{{ perfil.email }}</p>
        </div>
        <span class="perfil__status" :class="{ 'perfil__status--active': perfil.activo }">
          {{ perfil.activo ? 'Activo' : 'Inactivo' }}
        </span>
      </div>

      <!-- Details grid -->
      <section class="perfil__section">
        <div class="perfil__section-header">
          <h2 class="perfil__section-title">Detalles</h2>
        </div>
        <div class="perfil__details">
          <div class="perfil__detail" v-if="perfil.edad != null">
            <div class="perfil__detail-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="12" cy="8" r="4"/>
                <path d="M4 21v-2a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4v2"/>
              </svg>
            </div>
            <span class="perfil__detail-value">{{ perfil.edad }}</span>
            <span class="perfil__detail-label">Edad</span>
          </div>
          <div class="perfil__detail" v-if="perfil.altura">
            <div class="perfil__detail-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M12 2v20M8 6l4-4 4 4M8 18l4 4 4-4"/>
              </svg>
            </div>
            <span class="perfil__detail-value">{{ perfil.altura }}</span>
            <span class="perfil__detail-label">Altura (cm)</span>
          </div>
          <div class="perfil__detail" v-if="perfil.objetivo">
            <div class="perfil__detail-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="12" cy="12" r="10"/>
                <circle cx="12" cy="12" r="6"/>
                <circle cx="12" cy="12" r="2"/>
              </svg>
            </div>
            <span class="perfil__detail-value perfil__detail-value--small">{{ perfil.objetivo }}</span>
            <span class="perfil__detail-label">Objetivo</span>
          </div>
          <div class="perfil__detail" v-if="perfil.suscripcion?.fecha_fin">
            <div class="perfil__detail-icon" :class="{ 'perfil__detail-icon--warning': perfil.suscripcion.dias_restantes <= 7 }">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="3" y="4" width="18" height="18" rx="2"/>
                <path d="M16 2v4M8 2v4M3 10h18"/>
              </svg>
            </div>
            <span class="perfil__detail-value">{{ perfil.suscripcion.fecha_fin }}</span>
            <span class="perfil__detail-label">Vence plan</span>
          </div>
        </div>
      </section>

      <!-- Coach -->
      <section class="perfil__section" v-if="perfil.coach">
        <div class="perfil__section-header">
          <h2 class="perfil__section-title">Mi Entrenador</h2>
          <span class="perfil__see-all">Ver perfil</span>
        </div>
        <div class="perfil__coach-card">
          <div class="perfil__coach-avatar">
            {{ [perfil.coach.nombre, perfil.coach.apellido_paterno].filter(Boolean).map(n => n[0]).join('').toUpperCase() }}
          </div>
          <div class="perfil__coach-info">
            <p class="perfil__coach-name">
              {{ perfil.coach.nombre }} {{ perfil.coach.apellido_paterno }} {{ perfil.coach.apellido_materno }}
            </p>
            <p class="perfil__coach-role">Coach personal</p>
          </div>
          <div class="perfil__coach-action">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
          </div>
        </div>
      </section>

      <!-- Tabs: Estadísticas / Otros -->
      <div class="perfil__tabs-wrap">
        <BaseSegmentedControl
          v-model="tabSeleccionado"
          :options="opcionesTab"
        />
      </div>

      <!-- Estadísticas -->
      <section class="perfil__section" v-if="tabSeleccionado === 'estadisticas'">
        <div class="perfil__section-header">
          <h2 class="perfil__section-title">Estadísticas</h2>
          <span class="perfil__see-all" v-if="estadisticas.length">Ver todo</span>
        </div>
        <div class="perfil__stats-grid" v-if="estadisticas.length">
          <CircularProgress
            v-for="stat in estadisticas"
            :key="stat.nombre"
            :valor="stat.valorActual"
            :meta="stat.meta"
            :unidad="stat.unidad"
            :label="stat.nombre"
            :size="100"
            @click="abrirHistorial(stat)"
          />
        </div>
        <div class="perfil__otros-placeholder" v-else>
          <p class="perfil__otros-text">Aún no hay estadísticas. Tu entrenador las irá añadiendo.</p>
        </div>
      </section>

      <!-- Otros (contenido alternativo) -->
      <section class="perfil__section" v-if="tabSeleccionado === 'otros'">
        <div class="perfil__section-header">
          <h2 class="perfil__section-title">Otros</h2>
        </div>
        <div class="perfil__otros-placeholder">
          <p class="perfil__otros-text">Contenido adicional disponible próximamente.</p>
        </div>
      </section>

      <!-- Modal de historial -->
      <HistorialModal
        v-if="modalHistorial && parametroSeleccionado"
        :parametro="parametroSeleccionado"
        @close="modalHistorial = false"
      />

      <!-- Actions -->
      <div class="perfil__actions">
        <RouterLink to="/cliente/rutina" class="perfil__btn perfil__btn--outline">
          Ver mis rutinas
        </RouterLink>
        <button
          type="button"
          class="perfil__btn perfil__btn--danger"
          :disabled="cerrandoSesion"
          @click="handleLogout"
        >
          <span v-if="cerrandoSesion" class="perfil__spinner" />
          <span v-else>Cerrar sesión</span>
        </button>
      </div>
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

/* Alert */
.perfil__alert {
  background: rgba(239, 92, 92, 0.12);
  border: 1px solid rgba(239, 92, 92, 0.3);
  color: #EF5C5C;
  padding: 0.875rem 1rem;
  border-radius: 16px;
  font-size: 0.875rem;
  margin-bottom: 1rem;
}

/* Empty */
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
  align-items: center;
  gap: 1rem;
  background: #161616;
  border-radius: 16px;
  padding: 1rem;
  margin-bottom: 1rem;
}
.perfil__avatar {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: linear-gradient(135deg, #2970FF 0%, #528BFF 100%);
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

.perfil__section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.75rem;
}
.perfil__section-title {
  font-size: 0.9375rem;
  font-weight: 600;
  color: #fff;
  margin: 0;
}
.perfil__see-all {
  font-size: 0.75rem;
  color: #00D261;
  cursor: pointer;
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
.perfil__detail-value {
  font-size: 1rem;
  font-weight: 600;
  color: #fff;
  text-align: center;
}
.perfil__detail-value--small {
  font-size: 0.75rem;
  font-weight: 500;
  line-height: 1.3;
  max-height: 2.6em;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}
.perfil__detail-icon--warning {
  color: #FF9900 !important;
}
.perfil__detail-label {
  font-size: 0.6875rem;
  color: #697586;
  text-transform: uppercase;
  letter-spacing: 0.02em;
}
.capitalize {
  text-transform: capitalize !important;
}

/* Stats grid */
.perfil__stats-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.5rem;
}

@media (min-width: 640px) {
  .perfil__stats-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

/* Coach card */
.perfil__coach-card {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  background: #1e1e1e;
  border-radius: 12px;
  padding: 0.875rem;
}
.perfil__coach-avatar {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: linear-gradient(135deg, #00D261 0%, #00b355 100%);
  color: #fff;
  font-size: 0.875rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.perfil__coach-info {
  flex: 1;
  min-width: 0;
}
.perfil__coach-name {
  font-size: 0.9375rem;
  font-weight: 500;
  color: #fff;
  margin: 0;
}
.perfil__coach-role {
  font-size: 0.75rem;
  color: #697586;
  margin: 0.125rem 0 0;
}
.perfil__coach-action {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: rgba(0, 210, 97, 0.15);
  color: #00D261;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background 0.2s;
}
.perfil__coach-action:hover {
  background: rgba(0, 210, 97, 0.25);
}
.perfil__coach-action svg {
  width: 18px;
  height: 18px;
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
  animation: spin 0.7s linear infinite;
}
@keyframes spin {
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
  animation: pulse 1.5s ease-in-out infinite;
}
.perfil__skeleton .perfil__detail {
  height: 90px;
}
@keyframes pulse {
  0%, 100% { opacity: 0.5; }
  50% { opacity: 1; }
}
</style>
