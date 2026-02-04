<script setup>
import { ref, computed, onMounted } from 'vue'
import { useApi } from '@/composables/useApi'

const { get, cargando } = useApi()
const archivos = ref([])
const archivoSeleccionado = ref(null)
const error = ref('')
const zoom = ref(100)

// Cargar archivos de dieta
async function cargarArchivos() {
  try {
    const res = await get('/cliente/dieta')
    archivos.value = Array.isArray(res.datos) ? res.datos : [res.datos].filter(Boolean)
    
    // Si hay un solo archivo, seleccionarlo automáticamente
    if (archivos.value.length === 1) {
      archivoSeleccionado.value = archivos.value[0]
    }
  } catch (e) {
    error.value = e.message || 'No se pudo cargar las dietas.'
    archivos.value = []
  }
}

// Seleccionar archivo
function seleccionarArchivo(archivo) {
  archivoSeleccionado.value = archivo
  zoom.value = 100 // Resetear zoom al cambiar archivo
}

// Volver a la lista
function volverALista() {
  archivoSeleccionado.value = null
  zoom.value = 100
}

// Ajustar zoom
function ajustarZoom(delta) {
  zoom.value = Math.max(50, Math.min(200, zoom.value + delta))
}

// Descargar archivo
function descargarArchivo(archivo) {
  const url = `/api/cliente/dieta/${archivo.id}/download`
  window.open(url, '_blank')
}

// Imprimir archivo
function imprimirArchivo(archivo) {
  const url = `/api/cliente/dieta/${archivo.id}`
  const iframe = document.createElement('iframe')
  iframe.style.display = 'none'
  iframe.src = url
  document.body.appendChild(iframe)
  iframe.onload = () => {
    iframe.contentWindow.print()
    setTimeout(() => {
      document.body.removeChild(iframe)
    }, 1000)
  }
}

// URL del PDF para el visor
const pdfUrl = computed(() => {
  if (!archivoSeleccionado.value) return null
  return `/api/cliente/dieta/${archivoSeleccionado.value.id}`
})

// Formatear fecha
function formatearFecha(fecha) {
  if (!fecha) return '—'
  try {
    const date = new Date(fecha)
    return date.toLocaleDateString('es-ES', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    })
  } catch {
    return fecha
  }
}

onMounted(() => {
  cargarArchivos()
})
</script>

<template>
  <div class="dieta">
    <!-- Error -->
    <div v-if="error" class="dieta__alert">{{ error }}</div>

    <!-- Sin archivos -->
    <div v-else-if="!cargando && archivos.length === 0" class="dieta__empty">
      <p class="dieta__empty-title">No tienes dietas asignadas</p>
      <p class="dieta__empty-desc">Tu entrenador te asignará una dieta cuando esté lista.</p>
    </div>

    <!-- Caso 1: Un solo archivo - Mostrar directamente -->
    <template v-else-if="archivos.length === 1 && archivoSeleccionado">
      <div class="dieta__viewer">
        <!-- Header con controles -->
        <div class="dieta__viewer-header">
          <div class="dieta__viewer-title-wrap">
            <h1 class="dieta__viewer-title">{{ archivoSeleccionado.nombre || 'Dieta' }}</h1>
            <span class="dieta__viewer-date">{{ formatearFecha(archivoSeleccionado.created_at) }}</span>
          </div>
          <div class="dieta__viewer-controls">
            <!-- Zoom -->
            <div class="dieta__zoom-controls">
              <button
                type="button"
                class="dieta__zoom-btn"
                :disabled="zoom <= 50"
                @click="ajustarZoom(-10)"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="11" cy="11" r="8"/>
                  <path d="M21 21l-4.35-4.35"/>
                  <path d="M8 11h6"/>
                </svg>
              </button>
              <span class="dieta__zoom-value">{{ zoom }}%</span>
              <button
                type="button"
                class="dieta__zoom-btn"
                :disabled="zoom >= 200"
                @click="ajustarZoom(10)"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="11" cy="11" r="8"/>
                  <path d="M21 21l-4.35-4.35"/>
                  <path d="M11 8v6M8 11h6"/>
                </svg>
              </button>
            </div>
            <!-- Acciones -->
            <button
              type="button"
              class="dieta__action-btn"
              @click="descargarArchivo(archivoSeleccionado)"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                <polyline points="7 10 12 15 17 10"/>
                <line x1="12" y1="15" x2="12" y2="3"/>
              </svg>
              Descargar
            </button>
            <button
              type="button"
              class="dieta__action-btn"
              @click="imprimirArchivo(archivoSeleccionado)"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 6 2 18 2 18 9"/>
                <path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/>
                <rect x="6" y="14" width="12" height="8"/>
              </svg>
              Imprimir
            </button>
          </div>
        </div>

        <!-- Visor PDF -->
        <div class="dieta__viewer-content">
          <iframe
            :src="pdfUrl"
            class="dieta__pdf-iframe"
            :style="{ transform: `scale(${zoom / 100})`, transformOrigin: 'top left' }"
          ></iframe>
        </div>
      </div>
    </template>

    <!-- Caso 2: Múltiples archivos -->
    <template v-else-if="archivos.length > 1">
      <!-- Lista de archivos -->
      <div v-if="!archivoSeleccionado" class="dieta__lista">
        <h1 class="dieta__lista-title">Mis Dietas</h1>
        <p class="dieta__lista-desc">Selecciona una dieta para verla</p>
        <div class="dieta__archivos-grid">
          <div
            v-for="archivo in archivos"
            :key="archivo.id"
            class="dieta__archivo-card"
            @click="seleccionarArchivo(archivo)"
          >
            <div class="dieta__archivo-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="16" y1="13" x2="8" y2="13"/>
                <line x1="16" y1="17" x2="8" y2="17"/>
                <polyline points="10 9 9 9 8 9"/>
              </svg>
            </div>
            <div class="dieta__archivo-info">
              <h3 class="dieta__archivo-nombre">{{ archivo.nombre || 'Dieta' }}</h3>
              <p class="dieta__archivo-fecha">{{ formatearFecha(archivo.created_at) }}</p>
            </div>
            <div class="dieta__archivo-action">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="9 18 15 12 9 6"/>
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Preview del archivo seleccionado -->
      <div v-else class="dieta__viewer">
        <!-- Header con controles -->
        <div class="dieta__viewer-header">
          <div class="dieta__viewer-title-wrap">
            <button
              type="button"
              class="dieta__back-btn"
              @click="volverALista"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"/>
              </svg>
              Volver
            </button>
            <div>
              <h1 class="dieta__viewer-title">{{ archivoSeleccionado.nombre || 'Dieta' }}</h1>
              <span class="dieta__viewer-date">{{ formatearFecha(archivoSeleccionado.created_at) }}</span>
            </div>
          </div>
          <div class="dieta__viewer-controls">
            <!-- Zoom -->
            <div class="dieta__zoom-controls">
              <button
                type="button"
                class="dieta__zoom-btn"
                :disabled="zoom <= 50"
                @click="ajustarZoom(-10)"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="11" cy="11" r="8"/>
                  <path d="M21 21l-4.35-4.35"/>
                  <path d="M8 11h6"/>
                </svg>
              </button>
              <span class="dieta__zoom-value">{{ zoom }}%</span>
              <button
                type="button"
                class="dieta__zoom-btn"
                :disabled="zoom >= 200"
                @click="ajustarZoom(10)"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="11" cy="11" r="8"/>
                  <path d="M21 21l-4.35-4.35"/>
                  <path d="M11 8v6M8 11h6"/>
                </svg>
              </button>
            </div>
            <!-- Acciones -->
            <button
              type="button"
              class="dieta__action-btn"
              @click="descargarArchivo(archivoSeleccionado)"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                <polyline points="7 10 12 15 17 10"/>
                <line x1="12" y1="15" x2="12" y2="3"/>
              </svg>
              Descargar
            </button>
            <button
              type="button"
              class="dieta__action-btn"
              @click="imprimirArchivo(archivoSeleccionado)"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 6 2 18 2 18 9"/>
                <path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/>
                <rect x="6" y="14" width="12" height="8"/>
              </svg>
              Imprimir
            </button>
          </div>
        </div>

        <!-- Visor PDF -->
        <div class="dieta__viewer-content">
          <iframe
            :src="pdfUrl"
            class="dieta__pdf-iframe"
            :style="{ transform: `scale(${zoom / 100})`, transformOrigin: 'top left' }"
          ></iframe>
        </div>
      </div>
    </template>

    <!-- Loading -->
    <div v-else-if="cargando" class="dieta__loading">
      <div class="dieta__skeleton-line" style="width: 200px; height: 24px; margin-bottom: 1rem;"></div>
      <div class="dieta__skeleton-box" style="height: 400px;"></div>
    </div>
  </div>
</template>

<style scoped>
/* Base */
.dieta {
  min-height: 100vh;
  min-height: 100dvh;
  background: #0a0a0a;
  padding: 1rem;
  padding-top: max(1rem, env(safe-area-inset-top));
  padding-bottom: max(2rem, env(safe-area-inset-bottom));
}

/* Alert */
.dieta__alert {
  background: rgba(239, 92, 92, 0.12);
  border: 1px solid rgba(239, 92, 92, 0.3);
  color: #EF5C5C;
  padding: 0.875rem 1rem;
  border-radius: 16px;
  font-size: 0.875rem;
  margin-bottom: 1rem;
}

/* Empty */
.dieta__empty {
  text-align: center;
  padding: 4rem 1rem;
}
.dieta__empty-title {
  font-size: 1.125rem;
  font-weight: 500;
  color: #fff;
  margin: 0 0 0.5rem;
}
.dieta__empty-desc {
  font-size: 0.875rem;
  color: #697586;
  margin: 0;
}

/* Lista de archivos */
.dieta__lista {
  max-width: 800px;
  margin: 0 auto;
}
.dieta__lista-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: #fff;
  margin: 0 0 0.5rem;
}
.dieta__lista-desc {
  font-size: 0.875rem;
  color: #697586;
  margin: 0 0 1.5rem;
}
.dieta__archivos-grid {
  display: grid;
  gap: 0.75rem;
}
.dieta__archivo-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  background: #161616;
  border-radius: 16px;
  padding: 1rem;
  cursor: pointer;
  transition: background 0.2s, transform 0.2s;
}
.dieta__archivo-card:hover {
  background: #1e1e1e;
  transform: translateY(-2px);
}
.dieta__archivo-icon {
  width: 48px;
  height: 48px;
  background: rgba(0, 210, 97, 0.15);
  color: #00D261;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.dieta__archivo-icon svg {
  width: 24px;
  height: 24px;
}
.dieta__archivo-info {
  flex: 1;
  min-width: 0;
}
.dieta__archivo-nombre {
  font-size: 0.9375rem;
  font-weight: 600;
  color: #fff;
  margin: 0 0 0.25rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.dieta__archivo-fecha {
  font-size: 0.75rem;
  color: #697586;
  margin: 0;
}
.dieta__archivo-action {
  width: 32px;
  height: 32px;
  color: #697586;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.dieta__archivo-action svg {
  width: 20px;
  height: 20px;
}

/* Viewer */
.dieta__viewer {
  display: flex;
  flex-direction: column;
  height: calc(100vh - 2rem);
  height: calc(100dvh - 2rem);
  max-height: calc(100vh - 2rem);
  max-height: calc(100dvh - 2rem);
  background: #161616;
  border-radius: 16px;
  overflow: hidden;
}
.dieta__viewer-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem;
  background: #1e1e1e;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  flex-shrink: 0;
  gap: 1rem;
  flex-wrap: wrap;
}
.dieta__viewer-title-wrap {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex: 1;
  min-width: 0;
}
.dieta__back-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  background: transparent;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  color: #697586;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
}
.dieta__back-btn:hover {
  border-color: rgba(255, 255, 255, 0.2);
  color: #fff;
}
.dieta__back-btn svg {
  width: 18px;
  height: 18px;
}
.dieta__viewer-title {
  font-size: 1rem;
  font-weight: 600;
  color: #fff;
  margin: 0 0 0.25rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.dieta__viewer-date {
  font-size: 0.75rem;
  color: #697586;
}
.dieta__viewer-controls {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}
.dieta__zoom-controls {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: #161616;
  border-radius: 8px;
  padding: 0.25rem;
}
.dieta__zoom-btn {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: none;
  color: #697586;
  cursor: pointer;
  transition: color 0.2s;
  border-radius: 6px;
}
.dieta__zoom-btn:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.05);
  color: #fff;
}
.dieta__zoom-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}
.dieta__zoom-btn svg {
  width: 16px;
  height: 16px;
}
.dieta__zoom-value {
  font-size: 0.75rem;
  color: #fff;
  font-weight: 500;
  min-width: 40px;
  text-align: center;
}
.dieta__action-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1rem;
  background: rgba(0, 210, 97, 0.15);
  border: 1px solid rgba(0, 210, 97, 0.3);
  border-radius: 8px;
  color: #00D261;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
}
.dieta__action-btn:hover {
  background: rgba(0, 210, 97, 0.25);
  border-color: #00D261;
}
.dieta__action-btn svg {
  width: 18px;
  height: 18px;
}
.dieta__viewer-content {
  flex: 1;
  overflow: auto;
  padding: 1rem;
  background: #0a0a0a;
  position: relative;
}
.dieta__pdf-iframe {
  width: 100%;
  height: 100%;
  min-height: 800px;
  border: none;
  background: #fff;
  border-radius: 8px;
}

/* Loading / Skeleton */
.dieta__loading {
  padding-top: 1rem;
}
.dieta__skeleton-line {
  background: #252525;
  border-radius: 6px;
  animation: pulse 1.5s ease-in-out infinite;
}
.dieta__skeleton-box {
  background: #252525;
  border-radius: 12px;
  animation: pulse 1.5s ease-in-out infinite;
}
@keyframes pulse {
  0%, 100% { opacity: 0.5; }
  50% { opacity: 1; }
}

/* Responsive */
@media (max-width: 640px) {
  .dieta__viewer-header {
    flex-direction: column;
    align-items: flex-start;
  }
  .dieta__viewer-controls {
    width: 100%;
    justify-content: space-between;
  }
  .dieta__action-btn {
    flex: 1;
    justify-content: center;
  }
}
</style>

