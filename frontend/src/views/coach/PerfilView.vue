<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useApi } from '@/composables/useApi'
import { useAuth } from '@/composables/useAuth'
import BaseSegmentedControl from '@/components/ui/BaseSegmentedControl.vue'

const { get, post, put, cargando } = useApi()
const { logout } = useAuth()
const perfil = ref(null)
const dashboard = ref(null)
const error = ref('')
const cerrandoSesion = ref(false)
const linkCopiado = ref(false)
const generando = ref(false)
const linkActivo = ref(false)
const linkInput = ref(null)
const formulariosDisponibles = ref([])
const cargandoFormularios = ref(false)
const actualizandoFormularioEstandar = ref(false)

// Tabs: Estadísticas | Otros
const tabSeleccionado = ref('estadisticas')
const opcionesTab = [
  { value: 'estadisticas', label: 'Estadísticas' },
  { value: 'otros', label: 'Otros' }
]

const stats = computed(() => {
  if (!dashboard.value) return null
  return {
    clientesTotal: dashboard.value.clientes?.total ?? 0,
    clientesActivos: dashboard.value.clientes?.activos ?? 0,
    clientesConDieta: dashboard.value.clientes?.con_dieta ?? 0,
    clientesVencimientoProximo: dashboard.value.clientes?.vencimiento_proximo ?? 0,
    suscripcionesActivas: dashboard.value.suscripciones_activas ?? 0,
    ingresosMes: dashboard.value.ingresos_mes ?? 0
  }
})

function avatarUrl(path) {
  if (!path) return null
  const base = (import.meta.env.VITE_API_URL || '').replace(/\/api\/v1\/?$/, '') || window.location.origin
  return `${base}/storage/${path}`
}

async function handleLogout() {
  cerrandoSesion.value = true
  await logout()
  cerrandoSesion.value = false
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
    
    // Cargar formularios disponibles
    await cargarFormularios()
  } catch (e) {
    error.value = e.message || 'No se pudo cargar el perfil.'
  }
})

async function cargarFormularios() {
  try {
    cargandoFormularios.value = true
    const response = await get('/coach/formularios')
    formulariosDisponibles.value = response.datos || []
  } catch (e) {
    console.error('Error al cargar formularios:', e)
  } finally {
    cargandoFormularios.value = false
  }
}

async function actualizarFormularioEstandar(formularioId) {
  try {
    actualizandoFormularioEstandar.value = true
    const response = await put('/coach/perfil', {
      formulario_inicial_id: formularioId
    })
    perfil.value.formulario_inicial_id = response.datos.formulario_inicial_id
    perfil.value.formulario_estandar = response.datos.formulario_estandar
  } catch (e) {
    error.value = e.response?.data?.mensaje || 'Error al actualizar formulario estándar'
  } finally {
    actualizandoFormularioEstandar.value = false
  }
}

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
      <!-- Header card -->
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
        </div>
        <span class="perfil__status" :class="{ 'perfil__status--active': perfil.activo }">
          {{ perfil.activo ? 'Activo' : 'Inactivo' }}
        </span>
      </div>

      <!-- Bio -->
      <section class="perfil__section" v-if="perfil.bio">
        <div class="perfil__section-header">
          <h2 class="perfil__section-title">Sobre mí</h2>
        </div>
        <div class="perfil__bio-card">
          <p class="perfil__bio-text">{{ perfil.bio }}</p>
        </div>
      </section>

      <!-- Link de Registro - Siempre visible -->
      <section class="perfil__section">
        <div class="perfil__section-header">
          <h2 class="perfil__section-title">Link de Registro</h2>
        </div>
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
      </section>

      <!-- Formulario Estándar -->
      <section class="perfil__section">
        <div class="perfil__section-header">
          <h2 class="perfil__section-title">Formulario Estándar</h2>
        </div>
        <div class="perfil__formulario-estandar">
          <p class="perfil__formulario-estandar-desc">
            Selecciona el formulario que usarás para todos tus clientes (ej: "¿Cuántas veces comes?", "¿Tienes alergias?", etc.)
          </p>
          
          <div v-if="cargandoFormularios" class="perfil__formulario-estandar-loading">
            <p class="perfil__formulario-estandar-loading-text">Cargando formularios...</p>
          </div>
          
          <div v-else-if="formulariosDisponibles.length === 0" class="perfil__formulario-estandar-empty">
            <p class="perfil__formulario-estandar-empty-text">
              No tienes formularios creados. 
              <RouterLink to="/coach/formularios" class="perfil__formulario-estandar-link">
                Crea uno primero
              </RouterLink>
            </p>
          </div>
          
          <div v-else class="perfil__formulario-estandar-select">
            <select
              :value="perfil.formulario_inicial_id || ''"
              @change="actualizarFormularioEstandar($event.target.value ? parseInt($event.target.value) : null)"
              :disabled="actualizandoFormularioEstandar"
              class="perfil__formulario-estandar-select-input"
            >
              <option value="">Selecciona un formulario</option>
              <option
                v-for="formulario in formulariosDisponibles"
                :key="formulario.id"
                :value="formulario.id"
              >
                {{ formulario.nombre }}
              </option>
            </select>
            
            <div v-if="perfil.formulario_estandar" class="perfil__formulario-estandar-info">
              <p class="perfil__formulario-estandar-info-text">
                <strong>Formulario actual:</strong> {{ perfil.formulario_estandar.nombre }}
              </p>
            </div>
            
            <div v-else class="perfil__formulario-estandar-warning">
              <p class="perfil__formulario-estandar-warning-text">
                ⚠️ No tienes un formulario estándar configurado. Tus clientes no podrán completar formularios hasta que configures uno.
              </p>
            </div>
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
          <RouterLink v-if="stats" to="/coach" class="perfil__see-all">Ver dashboard</RouterLink>
        </div>
        <div class="perfil__details" v-if="stats">
          <div class="perfil__detail">
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
          <div class="perfil__detail">
            <div class="perfil__detail-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <path d="M22 4L12 14.01l-3-3"/>
              </svg>
            </div>
            <span class="perfil__detail-value">{{ stats.clientesActivos }}</span>
            <span class="perfil__detail-label">Activos</span>
          </div>
          <div class="perfil__detail">
            <div class="perfil__detail-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M18 8h1a4 4 0 0 1 0 8h-1M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/>
                <line x1="6" y1="1" x2="6" y2="4"/>
                <line x1="10" y1="1" x2="10" y2="4"/>
                <line x1="14" y1="1" x2="14" y2="4"/>
              </svg>
            </div>
            <span class="perfil__detail-value">{{ stats.clientesConDieta }}</span>
            <span class="perfil__detail-label">Con dieta</span>
          </div>
          <div class="perfil__detail">
            <div class="perfil__detail-icon" :class="{ 'perfil__detail-icon--warning': stats.clientesVencimientoProximo > 0 }">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="12" cy="12" r="10"/>
                <path d="M12 6v6l4 2"/>
              </svg>
            </div>
            <span class="perfil__detail-value">{{ stats.clientesVencimientoProximo }}</span>
            <span class="perfil__detail-label">Vence pronto (30 d)</span>
          </div>
          <div class="perfil__detail">
            <div class="perfil__detail-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="3" y="4" width="18" height="18" rx="2"/>
                <path d="M16 2v4M8 2v4M3 10h18"/>
              </svg>
            </div>
            <span class="perfil__detail-value">{{ stats.suscripcionesActivas }}</span>
            <span class="perfil__detail-label">Suscripciones</span>
          </div>
          <div class="perfil__detail">
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

      <!-- Otros -->
      <section class="perfil__section" v-if="tabSeleccionado === 'otros'">
        <div class="perfil__section-header">
          <h2 class="perfil__section-title">Otros</h2>
        </div>
        
        <!-- Link de Registro -->
        <div class="perfil__link-registro">
          <h3 class="perfil__link-registro-title">Link de Registro</h3>
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
          
          <div class="perfil__link-registro-actions">
            <button
              @click="generarLink"
              class="perfil__btn perfil__btn--outline"
              :disabled="generando"
            >
              {{ perfil.link_registro ? 'Regenerar Link' : 'Generar Link' }}
            </button>
            
            <label class="perfil__link-toggle">
              <input
                type="checkbox"
                v-model="linkActivo"
                @change="toggleLink"
              />
              <span>Link activo</span>
            </label>
          </div>
        </div>
        
        <div class="perfil__otros-placeholder">
          <p class="perfil__otros-text">Contenido adicional disponible próximamente.</p>
        </div>
      </section>

      <!-- Actions -->
      <div class="perfil__actions">
        <RouterLink to="/coach" class="perfil__btn perfil__btn--outline">
          Ir al dashboard
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
  align-items: center;
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
  text-decoration: none;
}
.perfil__see-all:hover {
  text-decoration: underline;
}

/* Bio */
.perfil__bio-card {
  background: #1e1e1e;
  border-radius: 12px;
  padding: 1rem;
}
.perfil__bio-text {
  font-size: 0.875rem;
  color: #a0a0a0;
  line-height: 1.5;
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
.perfil__detail-icon--warning {
  color: #FF9900 !important;
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

/* Formulario Estándar */
.perfil__formulario-estandar {
  background: #1e1e1e;
  border-radius: 12px;
  padding: 1rem;
}

.perfil__formulario-estandar-desc {
  font-size: 0.8125rem;
  color: #697586;
  margin: 0 0 1rem;
}

.perfil__formulario-estandar-loading,
.perfil__formulario-estandar-empty {
  background: #161616;
  border-radius: 8px;
  padding: 1rem;
  text-align: center;
}

.perfil__formulario-estandar-loading-text,
.perfil__formulario-estandar-empty-text {
  font-size: 0.8125rem;
  color: #697586;
  margin: 0;
}

.perfil__formulario-estandar-link {
  color: #00D261;
  text-decoration: none;
}

.perfil__formulario-estandar-link:hover {
  text-decoration: underline;
}

.perfil__formulario-estandar-select {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.perfil__formulario-estandar-select-input {
  width: 100%;
  background: #161616;
  border: 1px solid #252525;
  border-radius: 8px;
  padding: 0.625rem 0.875rem;
  font-size: 0.875rem;
  color: #fff;
  cursor: pointer;
  transition: all 0.2s;
}

.perfil__formulario-estandar-select-input:focus {
  outline: none;
  border-color: #00D261;
  box-shadow: 0 0 0 2px rgba(0, 210, 97, 0.35);
}

.perfil__formulario-estandar-select-input:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.perfil__formulario-estandar-select-input option {
  background: #161616;
  color: #fff;
}

.perfil__formulario-estandar-info {
  background: rgba(0, 210, 97, 0.1);
  border: 1px solid rgba(0, 210, 97, 0.3);
  border-radius: 8px;
  padding: 0.75rem;
}

.perfil__formulario-estandar-info-text {
  font-size: 0.8125rem;
  color: #a0a0a0;
  margin: 0;
}

.perfil__formulario-estandar-info-text strong {
  color: #00D261;
}

.perfil__formulario-estandar-warning {
  background: rgba(255, 153, 0, 0.1);
  border: 1px solid rgba(255, 153, 0, 0.3);
  border-radius: 8px;
  padding: 0.75rem;
}

.perfil__formulario-estandar-warning-text {
  font-size: 0.8125rem;
  color: #FF9900;
  margin: 0;
}
</style>
