<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useApi, API_BASE_URL } from '@/composables/useApi'
import { useAuthStore } from '@/stores/auth'

const { get, cargando } = useApi()
const authStore = useAuthStore()

const archivos = ref([])
const archivoSeleccionado = ref(null)
const pdfBlobUrl = ref(null)
const error = ref('')
const cargandoPdf = ref(false)
const zoom = ref(100)
const paginaActual = ref(1)
const totalPaginas = ref(1)
const rotacion = ref(0)

// Cargar archivos de dieta
async function cargarArchivos() {
  try {
    const res = await get('/cliente/dieta')
    archivos.value = Array.isArray(res.datos) ? res.datos : [res.datos].filter(Boolean)
  } catch (e) {
    error.value = e.message || 'No se pudo cargar las dietas.'
    archivos.value = []
  }
}

// Seleccionar archivo
async function seleccionarArchivo(archivo) {
  archivoSeleccionado.value = archivo
  
  // Limpiar blob URL anterior si existe
  if (pdfBlobUrl.value) {
    URL.revokeObjectURL(pdfBlobUrl.value)
    pdfBlobUrl.value = null
  }
  
  // Cargar PDF como blob con autenticación
  await cargarPdfComoBlob(archivo.id)
}

// Cargar PDF como blob con autenticación
async function cargarPdfComoBlob(dietaId) {
  cargandoPdf.value = true
  error.value = ''
  
  try {
    const url = `${API_BASE_URL}/cliente/dieta/${dietaId}`
    
    const response = await fetch(url, {
      headers: {
        'Authorization': `Bearer ${authStore.token}`,
        'Accept': 'application/pdf'
      },
      credentials: 'include'
    })
    
    if (!response.ok) {
      throw new Error(`Error ${response.status}: No se pudo cargar el PDF`)
    }
    
    const blob = await response.blob()
    pdfBlobUrl.value = URL.createObjectURL(blob)
  } catch (e) {
    console.error('Error cargando PDF:', e)
    error.value = e.message || 'No se pudo cargar el PDF.'
    pdfBlobUrl.value = null
  } finally {
    cargandoPdf.value = false
  }
}

// Volver a la lista
function volverALista() {
  // Limpiar blob URL
  if (pdfBlobUrl.value) {
    URL.revokeObjectURL(pdfBlobUrl.value)
    pdfBlobUrl.value = null
  }
  archivoSeleccionado.value = null
  zoom.value = 100
  paginaActual.value = 1
  rotacion.value = 0
}

// Ajustar zoom
function ajustarZoom(delta) {
  zoom.value = Math.max(50, Math.min(200, zoom.value + delta))
}

// Rotar PDF
function rotarPdf() {
  rotacion.value = (rotacion.value + 90) % 360
}

// Navegar páginas
function cambiarPagina(delta) {
  const nuevaPagina = paginaActual.value + delta
  if (nuevaPagina >= 1 && nuevaPagina <= totalPaginas.value) {
    paginaActual.value = nuevaPagina
  }
}

// Actualizar total de páginas cuando el PDF carga
function onPdfLoad(event) {
  const iframe = event.target
  try {
    const pdfWindow = iframe.contentWindow
    if (pdfWindow && pdfWindow.PDFViewerApplication) {
      totalPaginas.value = pdfWindow.PDFViewerApplication.pagesCount || 1
      paginaActual.value = pdfWindow.PDFViewerApplication.page || 1
    }
  } catch (e) {
    // Si no se puede acceder al PDF viewer interno, usar valores por defecto
    console.log('No se pudo acceder al PDF viewer interno')
  }
}

// Descargar archivo
async function descargarArchivo(archivo) {
  try {
    const url = `${API_BASE_URL}/cliente/dieta/${archivo.id}/download`
    
    const response = await fetch(url, {
      headers: {
        'Authorization': `Bearer ${authStore.token}`,
        'Accept': 'application/pdf'
      },
      credentials: 'include'
    })
    
    if (!response.ok) {
      throw new Error(`Error ${response.status}: No se pudo descargar el archivo`)
    }
    
    const blob = await response.blob()
    const blobUrl = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = blobUrl
    link.download = archivo.nombre || 'dieta.pdf'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    URL.revokeObjectURL(blobUrl)
  } catch (e) {
    console.error('Error descargando archivo:', e)
    error.value = e.message || 'No se pudo descargar el archivo.'
  }
}

// Limpiar blob URL al desmontar
onUnmounted(() => {
  if (pdfBlobUrl.value) {
    URL.revokeObjectURL(pdfBlobUrl.value)
  }
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

    <!-- Lista de archivos -->
    <template v-else-if="archivos.length > 0">
      <!-- Lista de archivos -->
      <div class="dieta__lista">
        <section class="dieta__section">
          <h1 class="dieta__section-title">Mis Dietas</h1>
          <p class="dieta__section-desc">Selecciona una dieta para verla</p>
          
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
              <div class="dieta__archivo-actions">
                <button
                  type="button"
                  class="dieta__archivo-action-btn"
                  @click.stop="seleccionarArchivo(archivo)"
                  title="Vista previa"
                >
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                    <circle cx="12" cy="12" r="3"/>
                  </svg>
                </button>
                <button
                  type="button"
                  class="dieta__archivo-action-btn"
                  @click.stop="descargarArchivo(archivo)"
                  title="Descargar"
                >
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </section>
      </div>

      <!-- Modal de preview con Teleport -->
      <Teleport to="body">
        <div
          v-if="archivoSeleccionado"
          class="dieta__modal-overlay"
          @click.self="volverALista"
        >
          <div class="dieta__modal">
            <!-- Header -->
            <div class="dieta__modal-header">
              <h2 class="dieta__modal-title">
                {{ archivoSeleccionado.nombre || 'Dieta' }}
              </h2>
              <button
                type="button"
                class="dieta__modal-close"
                aria-label="Cerrar"
                @click="volverALista"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
              </button>
            </div>

            <!-- Body con PDF -->
            <div class="dieta__modal-body">
              <div v-if="cargandoPdf" class="dieta__pdf-loading">
                <p>Cargando PDF...</p>
              </div>
              <iframe
                v-else-if="pdfBlobUrl"
                :src="pdfBlobUrl"
                class="dieta__pdf-iframe"
                frameborder="0"
                type="application/pdf"
              ></iframe>
              <div v-else-if="error" class="dieta__pdf-error">
                <p>{{ error }}</p>
              </div>
            </div>
          </div>
        </div>
      </Teleport>
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
  position: relative;
}

/* Modal Overlay */
.dieta__modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.9);
  display: flex;
  align-items: flex-end;
  justify-content: center;
  z-index: 2000;
  animation: dieta-modal-fade 0.2s ease;
}

@keyframes dieta-modal-fade {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@media (min-width: 640px) {
  .dieta__modal-overlay {
    align-items: center;
  }
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
  max-width: 600px;
  margin: 0 auto;
}
.dieta__empty-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #fff;
  margin: 0 0 0.75rem;
}
.dieta__empty-desc {
  font-size: 0.9375rem;
  color: #697586;
  margin: 0;
  line-height: 1.6;
}

/* Lista de archivos */
.dieta__lista {
  max-width: 800px;
  margin: 0 auto;
}

/* Section */
.dieta__section {
  background: #161616;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 16px;
  padding: 1.25rem;
  margin-bottom: 0.75rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}
.dieta__section-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: #fff;
  margin: 0 0 0.5rem;
  letter-spacing: -0.01em;
}
.dieta__section-desc {
  font-size: 0.875rem;
  color: #697586;
  margin: 0 0 1.5rem;
  line-height: 1.5;
}

.dieta__archivos-grid {
  display: grid;
  gap: 0.75rem;
}
.dieta__archivo-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  background: #1e1e1e;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  padding: 1.25rem;
  transition: all 0.2s ease;
  cursor: pointer;
}
.dieta__archivo-card:hover {
  background: #252525;
  border-color: rgba(0, 210, 97, 0.3);
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0, 210, 97, 0.15);
}
.dieta__archivo-card:active {
  transform: translateY(0);
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
  font-weight: 500;
}
.dieta__archivo-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-shrink: 0;
}
.dieta__archivo-action-btn {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: none;
  border-radius: 8px;
  color: #697586;
  cursor: pointer;
  transition: all 0.2s;
  padding: 0;
}
.dieta__archivo-action-btn:hover {
  background: rgba(0, 210, 97, 0.1);
  color: #00D261;
}
.dieta__archivo-action-btn svg {
  width: 18px;
  height: 18px;
}

/* Modal */
.dieta__modal {
  background: #161616;
  border-radius: 20px 20px 0 0;
  width: 100%;
  max-width: 100%;
  height: 90vh;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  animation: dieta-modal-slide 0.3s ease;
}

@keyframes dieta-modal-slide {
  from {
    transform: translateY(100%);
  }
  to {
    transform: translateY(0);
  }
}

@media (min-width: 640px) {
  .dieta__modal {
    border-radius: 20px;
    max-width: 90vw;
    height: 90vh;
  }
}

.dieta__modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.25rem;
  border-bottom: 1px solid #252525;
  flex-shrink: 0;
}

.dieta__modal-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #fff;
  margin: 0;
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.dieta__modal-close {
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
  flex-shrink: 0;
  transition: background 0.2s, color 0.2s;
}

.dieta__modal-close:hover {
  background: #333;
  color: #fff;
}

.dieta__modal-close svg {
  width: 18px;
  height: 18px;
}

/* Toolbar */
.dieta__toolbar {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  background: #1e1e1e;
  border-bottom: 1px solid #252525;
  flex-shrink: 0;
  overflow-x: auto;
}
.dieta__toolbar-group {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}
.dieta__toolbar-separator {
  width: 1px;
  height: 24px;
  background: #252525;
  margin: 0 0.25rem;
}
.dieta__toolbar-btn {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: none;
  border-radius: 6px;
  color: #697586;
  cursor: pointer;
  transition: all 0.2s;
  padding: 0;
  flex-shrink: 0;
}
.dieta__toolbar-btn:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.05);
  color: #fff;
}
.dieta__toolbar-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}
.dieta__toolbar-btn svg {
  width: 18px;
  height: 18px;
}
.dieta__toolbar-page-info {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0 0.5rem;
  font-size: 0.8125rem;
  color: #fff;
  white-space: nowrap;
}
.dieta__toolbar-page-current {
  background: #252525;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-weight: 500;
}
.dieta__toolbar-page-separator {
  color: #697586;
}
.dieta__toolbar-page-total {
  color: #697586;
}
.dieta__modal-body {
  flex: 1;
  overflow: hidden;
  position: relative;
}

.dieta__pdf-iframe {
  width: 100%;
  height: 100%;
  border: none;
  background: #fff;
  display: block;
}
.dieta__pdf-loading,
.dieta__pdf-error {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: #697586;
  font-size: 0.875rem;
}
.dieta__pdf-error {
  color: #EF5C5C;
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
  .dieta__section {
    padding: 1rem;
  }
  .dieta__section-title {
    font-size: 1.25rem;
  }
  .dieta__archivo-card {
    padding: 1rem;
    gap: 0.875rem;
  }
  .dieta__archivo-icon {
    width: 40px;
    height: 40px;
  }
  .dieta__archivo-icon svg {
    width: 20px;
    height: 20px;
  }
  .dieta__archivo-nombre {
    font-size: 0.875rem;
  }
  .dieta__archivo-fecha {
    font-size: 0.6875rem;
  }
  .dieta__archivo-action-btn {
    width: 32px;
    height: 32px;
  }
  .dieta__archivo-action-btn svg {
    width: 16px;
    height: 16px;
  }
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

