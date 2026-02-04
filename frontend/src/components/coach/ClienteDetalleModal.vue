<script setup>
import { ref } from 'vue'
import { useApi } from '@/composables/useApi'
import Swal from 'sweetalert2'
import RutinaDetalleModal from '@/components/coach/RutinaDetalleModal.vue'

/**
 * ClienteDetalleModal - Detalle del cliente en móvil (bottom sheet).
 * Muestra nombre, email, estado, edad, altura, objetivo, suscripción, rutinas asignadas y dieta.
 */
const props = defineProps({
  cliente: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'asignar-rutina', 'subir-dieta', 'dieta-eliminada'])

const { del, get } = useApi()

// Estado para el modal de preview
const showPreviewModal = ref(false)
const dietaPreview = ref(null)
const cargandoPreview = ref(false)

// Estado para el modal de detalle de rutina
const showRutinaDetalleModal = ref(false)
const rutinaDetalle = ref(null)
const cargandoRutina = ref(false)

function nombreCompleto(c) {
  if (!c) return ''
  const partes = [c.nombre, c.apellido_paterno, c.apellido_materno].filter(Boolean)
  return partes.join(' ') || c.email || '—'
}

function diasTexto(dias) {
  if (!dias || !Array.isArray(dias) || dias.length === 0) return 'Sin días asignados'
  const diasLabels = {
    lunes: 'Lun',
    martes: 'Mar',
    miércoles: 'Mié',
    jueves: 'Jue',
    viernes: 'Vie',
    sábado: 'Sáb',
    domingo: 'Dom'
  }
  return dias.map(d => diasLabels[d] || d).join(', ')
}

function diaCompleto(dia) {
  const diasLabels = {
    lunes: 'Lunes',
    martes: 'Martes',
    miércoles: 'Miércoles',
    jueves: 'Jueves',
    viernes: 'Viernes',
    sábado: 'Sábado',
    domingo: 'Domingo'
  }
  return diasLabels[dia] || dia
}

function rutinasPorDia(rutinasAsignadas) {
  if (!rutinasAsignadas || !Array.isArray(rutinasAsignadas)) return {}
  
  const rutinasPorDiaMap = {}
  
  rutinasAsignadas.forEach(rutina => {
    if (rutina.dias && Array.isArray(rutina.dias)) {
      rutina.dias.forEach(dia => {
        if (!rutinasPorDiaMap[dia]) {
          rutinasPorDiaMap[dia] = []
        }
        rutinasPorDiaMap[dia].push(rutina)
      })
    }
  })
  
  // Ordenar días según el orden de la semana
  const ordenDias = ['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo']
  const ordenado = {}
  ordenDias.forEach(dia => {
    if (rutinasPorDiaMap[dia]) {
      ordenado[dia] = rutinasPorDiaMap[dia]
    }
  })
  
  return ordenado
}

async function verDetalleRutina(rutina) {
  if (!rutina?.rutina_id) return
  
  showRutinaDetalleModal.value = true
  rutinaDetalle.value = null
  cargandoRutina.value = true
  
  try {
    const res = await get(`/coach/rutinas/${rutina.rutina_id}`)
    rutinaDetalle.value = res.datos ?? res.data ?? res
  } catch (e) {
    await Swal.fire({
      title: 'Error',
      text: e.message || 'No se pudo cargar el detalle de la rutina.',
      icon: 'error',
      confirmButtonColor: '#00D261'
    })
    showRutinaDetalleModal.value = false
  } finally {
    cargandoRutina.value = false
  }
}

function cerrarRutinaDetalleModal() {
  showRutinaDetalleModal.value = false
  rutinaDetalle.value = null
}

async function previewDieta(dieta) {
  if (!dieta.id) return
  
  cargandoPreview.value = true
  dietaPreview.value = null
  showPreviewModal.value = true
  
  try {
    // Cargar el PDF con autenticación y crear blob URL
    const { useAuthStore } = await import('@/stores/auth')
    const authStore = useAuthStore()
    const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1'
    
    const response = await fetch(`${API_BASE_URL}/coach/dietas/${dieta.id}/ver`, {
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
    const blobUrl = window.URL.createObjectURL(blob)
    
    dietaPreview.value = {
      ...dieta,
      previewUrl: blobUrl
    }
  } catch (e) {
    console.error('Error cargando PDF:', e)
    await Swal.fire({
      title: 'Error',
      text: e.message || 'No se pudo cargar la vista previa del PDF.',
      icon: 'error',
      confirmButtonColor: '#00D261'
    })
    showPreviewModal.value = false
  } finally {
    cargandoPreview.value = false
  }
}

function cerrarPreview() {
  // Limpiar blob URL si existe
  if (dietaPreview.value?.previewUrl && dietaPreview.value.previewUrl.startsWith('blob:')) {
    window.URL.revokeObjectURL(dietaPreview.value.previewUrl)
  }
  showPreviewModal.value = false
  dietaPreview.value = null
}

async function descargarDieta(dieta) {
  try {
    // Obtener el token de autenticación
    const { useAuthStore } = await import('@/stores/auth')
    const authStore = useAuthStore()
    const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1'
    
    // Descargar usando el endpoint que devuelve el archivo directamente
    const response = await fetch(`${API_BASE_URL}/coach/dietas/${dieta.id}/download`, {
      headers: {
        'Authorization': `Bearer ${authStore.token}`,
        'Accept': 'application/pdf'
      },
      credentials: 'include'
    })

    if (!response.ok) {
      throw new Error('No se pudo descargar el archivo')
    }

    // Crear blob y descargar
    const blob = await response.blob()
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = dieta.nombre_archivo || 'dieta.pdf'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch (e) {
    // Fallback: intentar descargar directamente desde la URL
    if (dieta.url) {
      const link = document.createElement('a')
      link.href = dieta.url
      link.download = dieta.nombre_archivo || 'dieta.pdf'
      link.target = '_blank'
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
    } else {
      await Swal.fire({
        title: 'Error',
        text: e.message || 'No se pudo descargar el archivo.',
        icon: 'error',
        confirmButtonColor: '#00D261'
      })
    }
  }
}

async function quitarDieta(dieta) {
  const result = await Swal.fire({
    title: '¿Eliminar dieta?',
    text: `¿Estás seguro de que quieres eliminar ${dieta.nombre_archivo || 'este archivo'}?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#EF5C5C',
    cancelButtonColor: '#666',
    reverseButtons: true
  })

  if (!result.isConfirmed) {
    return
  }

  try {
    await del(`/coach/dietas/${dieta.id}`)
    await Swal.fire({
      title: 'Dieta eliminada',
      text: 'El archivo se ha eliminado correctamente.',
      icon: 'success',
      confirmButtonColor: '#00D261',
      timer: 2000,
      showConfirmButton: false
    })
    emit('dieta-eliminada')
  } catch (e) {
    await Swal.fire({
      title: 'Error',
      text: e.message || 'No se pudo eliminar el archivo.',
      icon: 'error',
      confirmButtonColor: '#00D261'
    })
  }
}
</script>

<template>
  <Teleport to="body">
    <div class="cliente-modal__overlay" @click.self="$emit('close')">
      <div class="cliente-modal">
        <!-- Header -->
        <div class="cliente-modal__header">
          <h2 class="cliente-modal__title">
            {{ cliente ? nombreCompleto(cliente) : 'Detalle del cliente' }}
          </h2>
          <button
            type="button"
            class="cliente-modal__close"
            aria-label="Cerrar"
            @click="$emit('close')"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 6L6 18M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Loading -->
        <div v-if="!cliente" class="cliente-modal__loading">
          <div class="cliente-modal__skeleton" />
          <div class="cliente-modal__skeleton" />
          <div class="cliente-modal__skeleton" />
        </div>

        <!-- Error -->
        <div v-else-if="cliente.error" class="cliente-modal__body">
          <p class="cliente-modal__error">{{ cliente.error }}</p>
        </div>

        <!-- Contenido -->
        <div v-else class="cliente-modal__body">
          <div class="cliente-modal__row">
            <span class="cliente-modal__label">Email</span>
            <span class="cliente-modal__value">{{ cliente.email || '—' }}</span>
          </div>
          <div class="cliente-modal__row">
            <span class="cliente-modal__label">Estado</span>
            <span
              class="cliente-modal__badge"
              :class="{ 'cliente-modal__badge--active': cliente.activo }"
            >
              {{ cliente.activo ? 'Activo' : 'Inactivo' }}
            </span>
          </div>
          <div class="cliente-modal__row" v-if="cliente.edad != null">
            <span class="cliente-modal__label">Edad</span>
            <span class="cliente-modal__value">{{ cliente.edad }} años</span>
          </div>
          <div class="cliente-modal__row" v-if="cliente.altura">
            <span class="cliente-modal__label">Altura</span>
            <span class="cliente-modal__value">{{ cliente.altura }} cm</span>
          </div>
          <div class="cliente-modal__row" v-if="cliente.objetivo">
            <span class="cliente-modal__label">Objetivo</span>
            <span class="cliente-modal__value">{{ cliente.objetivo }}</span>
          </div>
          <div class="cliente-modal__row" v-if="cliente.suscripcion_activa">
            <span class="cliente-modal__label">Suscripción</span>
            <span class="cliente-modal__value">
              Vence {{ cliente.suscripcion_activa.fecha_fin }}
              <span v-if="cliente.suscripcion_activa.dias_restantes != null" class="cliente-modal__hint">
                ({{ cliente.suscripcion_activa.dias_restantes }} días)
              </span>
            </span>
          </div>
          <div class="cliente-modal__row">
            <span class="cliente-modal__label">Tiene dieta</span>
            <span
              class="cliente-modal__badge"
              :class="{ 'cliente-modal__badge--active': cliente.tiene_dieta }"
            >
              {{ cliente.tiene_dieta ? 'Sí' : 'No' }}
            </span>
          </div>

          <!-- Sección Rutinas Asignadas -->
          <div class="cliente-modal__section">
            <div class="cliente-modal__section-header">
              <h3 class="cliente-modal__section-title">Rutinas asignadas</h3>
              <button
                type="button"
                class="cliente-modal__section-btn cliente-modal__section-btn--assign"
                @click="emit('asignar-rutina', cliente)"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                  <line x1="16" y1="2" x2="16" y2="6"/>
                  <line x1="8" y1="2" x2="8" y2="6"/>
                  <line x1="3" y1="10" x2="21" y2="10"/>
                  <path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01M16 18h.01"/>
                </svg>
                <span>Asignar rutina</span>
              </button>
            </div>
            <div v-if="cliente.rutinas_asignadas && cliente.rutinas_asignadas.length > 0" class="cliente-modal__rutinas">
              <div
                v-for="(rutinas, dia) in rutinasPorDia(cliente.rutinas_asignadas)"
                :key="dia"
                class="cliente-modal__rutina-dia-group"
              >
                <div class="cliente-modal__rutina-dia-header">
                  {{ diaCompleto(dia) }}
                </div>
                <div class="cliente-modal__rutina-dia-content">
                  <div
                    v-for="rutina in rutinas"
                    :key="`${dia}-${rutina.id}`"
                    class="cliente-modal__rutina-item cliente-modal__rutina-item--clickable"
                    @click="verDetalleRutina(rutina)"
                  >
                    <div class="cliente-modal__rutina-info">
                      <span class="cliente-modal__rutina-nombre">{{ rutina.nombre || '—' }}</span>
                      <span class="cliente-modal__rutina-ejercicios">
                        {{ rutina.ejercicios_count || 0 }} {{ rutina.ejercicios_count === 1 ? 'ejercicio' : 'ejercicios' }}
                      </span>
                    </div>
                    <svg class="cliente-modal__rutina-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M9 18l6-6-6-6"/>
                    </svg>
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="cliente-modal__empty-state">
              <p class="cliente-modal__empty-text">No hay rutinas asignadas</p>
            </div>
          </div>

          <!-- Sección Dieta -->
          <div class="cliente-modal__section">
            <div class="cliente-modal__section-header">
              <h3 class="cliente-modal__section-title">Dieta</h3>
              <button
                type="button"
                class="cliente-modal__section-btn cliente-modal__section-btn--upload"
                @click="emit('subir-dieta', cliente)"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                  <polyline points="17 8 12 3 7 8"/>
                  <line x1="12" y1="3" x2="12" y2="15"/>
                </svg>
                <span>Subir archivos</span>
              </button>
            </div>
            <div v-if="cliente.dietas && cliente.dietas.length > 0" class="cliente-modal__dietas">
              <div
                v-for="dieta in cliente.dietas"
                :key="dieta.id"
                class="cliente-modal__dieta-item"
              >
                <div class="cliente-modal__dieta-info">
                  <div class="cliente-modal__dieta-details">
                    <span class="cliente-modal__dieta-nombre">{{ dieta.nombre_archivo || 'Archivo PDF' }}</span>
                    <span class="cliente-modal__dieta-fecha">{{ dieta.created_at }}</span>
                  </div>
                  <div class="cliente-modal__dieta-actions">
                    <button
                      type="button"
                      class="cliente-modal__dieta-action-btn cliente-modal__dieta-action-btn--preview"
                      @click="previewDieta(dieta)"
                      title="Vista previa"
                    >
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                      </svg>
                    </button>
                    <button
                      type="button"
                      class="cliente-modal__dieta-action-btn cliente-modal__dieta-action-btn--download"
                      @click="descargarDieta(dieta)"
                      title="Descargar"
                    >
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                      </svg>
                    </button>
                    <button
                      type="button"
                      class="cliente-modal__dieta-action-btn cliente-modal__dieta-action-btn--delete"
                      @click="quitarDieta(dieta)"
                      title="Eliminar"
                    >
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="cliente-modal__empty-state">
              <p class="cliente-modal__empty-text">No hay archivos de dieta</p>
            </div>
          </div>
        </div>

        <div class="cliente-modal__footer">
          <button type="button" class="cliente-modal__btn" @click="$emit('close')">
            Cerrar
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Preview PDF -->
    <Teleport to="body">
      <div v-if="showPreviewModal" class="preview-modal__overlay" @click.self="cerrarPreview">
        <div class="preview-modal">
          <div class="preview-modal__header">
            <h2 class="preview-modal__title">
              {{ dietaPreview?.nombre_archivo || 'Vista previa' }}
            </h2>
            <button
              type="button"
              class="preview-modal__close"
              aria-label="Cerrar"
              @click="cerrarPreview"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 6L6 18M6 6l12 12"/>
              </svg>
            </button>
          </div>
          <div class="preview-modal__body">
            <div v-if="cargandoPreview" class="preview-modal__loading">
              <p>Cargando PDF...</p>
            </div>
            <iframe
              v-else-if="dietaPreview?.previewUrl"
              :src="dietaPreview.previewUrl"
              class="preview-modal__iframe"
              frameborder="0"
              type="application/pdf"
            ></iframe>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Modal Detalle Rutina -->
    <RutinaDetalleModal
      v-if="showRutinaDetalleModal"
      :rutina="rutinaDetalle"
      @close="cerrarRutinaDetalleModal"
    />
  </Teleport>
</template>

<style scoped>
.cliente-modal__overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.75);
  display: flex;
  align-items: flex-end;
  justify-content: center;
  z-index: 1000;
  animation: cliente-modal-fade 0.2s ease;
}

@keyframes cliente-modal-fade {
  from { opacity: 0; }
  to { opacity: 1; }
}

.cliente-modal {
  background: #161616;
  border-radius: 20px 20px 0 0;
  width: 100%;
  max-width: 480px;
  max-height: 85vh;
  display: flex;
  flex-direction: column;
  animation: cliente-modal-slide 0.3s ease;
}

@keyframes cliente-modal-slide {
  from { transform: translateY(100%); }
  to { transform: translateY(0); }
}

@media (min-width: 640px) {
  .cliente-modal__overlay {
    align-items: center;
  }
  .cliente-modal {
    border-radius: 20px;
    max-height: 70vh;
  }
}

.cliente-modal__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.25rem 1.25rem 0;
}

.cliente-modal__title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #fff;
  margin: 0;
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
}

.cliente-modal__error {
  font-size: 0.875rem;
  color: #EF5C5C;
  margin: 0;
  padding: 1rem 0;
}

.cliente-modal__close {
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

.cliente-modal__close:hover {
  background: #333;
  color: #fff;
}

.cliente-modal__close svg {
  width: 18px;
  height: 18px;
}

.cliente-modal__loading {
  padding: 1rem 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.cliente-modal__skeleton {
  height: 2.5rem;
  background: #252525;
  border-radius: 12px;
  animation: cliente-modal-pulse 1.5s ease-in-out infinite;
}

@keyframes cliente-modal-pulse {
  0%, 100% { opacity: 0.5; }
  50% { opacity: 1; }
}

.cliente-modal__body {
  padding: 1rem 1.25rem;
  overflow-y: auto;
  flex: 1;
}

.cliente-modal__row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.75rem 0;
  border-bottom: 1px solid #252525;
}

.cliente-modal__row:last-child {
  border-bottom: none;
}

.cliente-modal__label {
  font-size: 0.8125rem;
  color: #697586;
  flex-shrink: 0;
}

.cliente-modal__value {
  font-size: 0.875rem;
  font-weight: 500;
  color: #fff;
  text-align: right;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
}

.cliente-modal__hint {
  font-weight: 400;
  color: #697586;
}

.cliente-modal__badge {
  font-size: 0.6875rem;
  font-weight: 500;
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
  background: rgba(105, 117, 134, 0.2);
  color: #697586;
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

.cliente-modal__badge--active {
  background: rgba(0, 210, 97, 0.15);
  color: #00D261;
}

.cliente-modal__footer {
  padding: 1rem 1.25rem;
  padding-bottom: max(1rem, env(safe-area-inset-bottom));
  border-top: 1px solid #252525;
}

.cliente-modal__btn {
  width: 100%;
  padding: 0.875rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #00D261;
  background: transparent;
  border: 1px solid rgba(0, 210, 97, 0.4);
  border-radius: 12px;
  cursor: pointer;
  transition: background 0.2s, border-color 0.2s;
}

.cliente-modal__btn:hover {
  background: rgba(0, 210, 97, 0.1);
  border-color: #00D261;
}

.cliente-modal__section {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #252525;
}

.cliente-modal__section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}

.cliente-modal__section-title {
  font-size: 0.9375rem;
  font-weight: 600;
  color: #fff;
  margin: 0;
}

.cliente-modal__section-btn {
  padding: 0.375rem 0.75rem;
  font-size: 0.75rem;
  font-weight: 500;
  color: #00D261;
  background: transparent;
  border: 1px solid rgba(0, 210, 97, 0.4);
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
}

.cliente-modal__section-btn:hover {
  background: rgba(0, 210, 97, 0.1);
  border-color: #00D261;
}

.cliente-modal__section-btn--assign {
  padding: 0.625rem 1rem;
  font-size: 0.875rem;
  font-weight: 600;
  background: rgba(0, 210, 97, 0.1);
  border: 1.5px solid #00D261;
  box-shadow: 0 2px 8px rgba(0, 210, 97, 0.15);
}

.cliente-modal__section-btn--assign:hover {
  background: rgba(0, 210, 97, 0.2);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 210, 97, 0.3);
}

.cliente-modal__section-btn--assign svg {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
}

.cliente-modal__section-btn--upload {
  padding: 0.625rem 1rem;
  font-size: 0.875rem;
  background: rgba(0, 210, 97, 0.1);
  border: 1.5px solid #00D261;
  font-weight: 600;
}

.cliente-modal__section-btn--upload:hover {
  background: rgba(0, 210, 97, 0.2);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 210, 97, 0.2);
}

.cliente-modal__section-btn--upload svg {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
}

.cliente-modal__rutinas,
.cliente-modal__dietas {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.cliente-modal__rutina-dia-group {
  display: flex;
  flex-direction: column;
  border-radius: 12px;
  overflow: hidden;
  border: 1.5px solid #00D261;
  box-shadow: 0 2px 8px rgba(0, 210, 97, 0.15);
}

.cliente-modal__rutina-dia-header {
  padding: 0.625rem 1rem;
  font-size: 0.875rem;
  font-weight: 600;
  background: rgba(0, 210, 97, 0.1);
  border: none;
  border-bottom: 1.5px solid #00D261;
  color: #00D261;
  text-transform: capitalize;
  border-radius: 12px 12px 0 0;
}

.cliente-modal__rutina-dia-content {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  padding: 0.75rem;
  background: #1e1e1e;
}

.cliente-modal__rutina-item {
  padding: 0.75rem;
  background: #252525;
  border-radius: 6px;
  border: 1px solid #2a2a2a;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
}

.cliente-modal__rutina-item--clickable {
  cursor: pointer;
  transition: all 0.2s;
}

.cliente-modal__rutina-item--clickable:hover {
  background: #2a2a2a;
  border-color: rgba(0, 210, 97, 0.3);
  transform: translateX(2px);
}

.cliente-modal__rutina-info {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
  flex: 1;
  min-width: 0;
}

.cliente-modal__rutina-arrow {
  width: 20px;
  height: 20px;
  color: #697586;
  flex-shrink: 0;
  transition: color 0.2s, transform 0.2s;
}

.cliente-modal__rutina-item--clickable:hover .cliente-modal__rutina-arrow {
  color: #00D261;
  transform: translateX(2px);
}

.cliente-modal__rutina-nombre {
  font-size: 0.875rem;
  font-weight: 500;
  color: #fff;
}

.cliente-modal__rutina-ejercicios {
  font-size: 0.75rem;
  color: #697586;
}

.cliente-modal__dieta-item {
  padding: 0.75rem;
  background: #1e1e1e;
  border-radius: 8px;
  border: 1px solid #252525;
}

.cliente-modal__dieta-info {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
}

.cliente-modal__dieta-details {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  flex: 1;
  min-width: 0;
}

.cliente-modal__dieta-nombre {
  font-size: 0.875rem;
  font-weight: 500;
  color: #fff;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.cliente-modal__dieta-fecha {
  font-size: 0.75rem;
  color: #697586;
}

.cliente-modal__dieta-actions {
  display: flex;
  gap: 0.5rem;
  flex-shrink: 0;
}

.cliente-modal__dieta-action-btn {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
  background: transparent;
  color: #697586;
}

.cliente-modal__dieta-action-btn svg {
  width: 18px;
  height: 18px;
}

.cliente-modal__dieta-action-btn--preview:hover {
  background: rgba(0, 210, 97, 0.1);
  color: #00D261;
}

.cliente-modal__dieta-action-btn--download:hover {
  background: rgba(37, 112, 255, 0.1);
  color: #2970FF;
}

.cliente-modal__dieta-action-btn--delete:hover {
  background: rgba(239, 92, 92, 0.1);
  color: #EF5C5C;
}

.cliente-modal__empty-state {
  padding: 1rem 0;
  text-align: center;
}

.cliente-modal__empty-text {
  font-size: 0.8125rem;
  color: #697586;
  margin: 0;
}

/* Modal Preview PDF */
.preview-modal__overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.9);
  display: flex;
  align-items: flex-end;
  justify-content: center;
  z-index: 2000;
  animation: preview-modal-fade 0.2s ease;
}

@keyframes preview-modal-fade {
  from { opacity: 0; }
  to { opacity: 1; }
}

.preview-modal {
  background: #161616;
  border-radius: 20px 20px 0 0;
  width: 100%;
  max-width: 100%;
  height: 90vh;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  animation: preview-modal-slide 0.3s ease;
}

@keyframes preview-modal-slide {
  from { transform: translateY(100%); }
  to { transform: translateY(0); }
}

@media (min-width: 640px) {
  .preview-modal__overlay {
    align-items: center;
  }
  .preview-modal {
    border-radius: 20px;
    max-width: 90vw;
    height: 90vh;
  }
}

.preview-modal__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.25rem;
  border-bottom: 1px solid #252525;
  flex-shrink: 0;
}

.preview-modal__title {
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

.preview-modal__close {
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

.preview-modal__close:hover {
  background: #333;
  color: #fff;
}

.preview-modal__close svg {
  width: 18px;
  height: 18px;
}

.preview-modal__body {
  flex: 1;
  overflow: hidden;
  position: relative;
}

.preview-modal__iframe {
  width: 100%;
  height: 100%;
  border: none;
  background: #fff;
}

.preview-modal__loading {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: #697586;
  font-size: 0.875rem;
}
</style>
