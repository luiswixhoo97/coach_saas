<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import Swal from 'sweetalert2'
import { useApi } from '@/composables/useApi'

const router = useRouter()

const { get, post, cargando } = useApi()
const rutina = ref(null)
const rutinasDisponibles = ref([])
const rutinaSeleccionadaId = ref(null)
const error = ref('')
const mostrarVideos = ref(false)
const bloqueActual = ref(0)
const guardandoProgreso = ref(false)

// Cargar rutina del día actual
async function cargarRutinaDelDia() {
  try {
    const res = await get('/cliente/rutinas/dia-actual')
    if (res.datos) {
      rutina.value = res.datos
      rutinaSeleccionadaId.value = res.datos.id
      // Iniciar en el primer bloque no completado
      if (res.datos.bloques && res.datos.bloques.length > 0) {
        const primerBloqueNoCompletado = res.datos.bloques.findIndex(b => !b.completado)
        bloqueActual.value = primerBloqueNoCompletado >= 0 ? primerBloqueNoCompletado : 0
      } else {
        bloqueActual.value = 0
      }
    } else {
      rutina.value = null
      bloqueActual.value = 0
    }
  } catch (e) {
    error.value = e.message || 'No se pudo cargar la rutina del día.'
    rutina.value = null
    bloqueActual.value = 0
  }
}

// Cargar todas las rutinas disponibles
async function cargarRutinasDisponibles() {
  try {
    const res = await get('/cliente/rutinas')
    rutinasDisponibles.value = res.datos || []
  } catch (e) {
    console.error('Error cargando rutinas:', e)
  }
}

// Cargar rutina específica por ID
async function cargarRutina(id) {
  try {
    const res = await get(`/cliente/rutinas/${id}`)
    const datos = res.datos
    
    // Agrupar ejercicios por bloque
    const bloquesMap = {}
    if (datos.ejercicios) {
      datos.ejercicios.forEach(ej => {
        const bloqueNum = ej.bloque || 1
        if (!bloquesMap[bloqueNum]) {
          bloquesMap[bloqueNum] = {
            numero: bloqueNum,
            ejercicios: [],
            completado: false
          }
        }
        bloquesMap[bloqueNum].ejercicios.push(ej)
      })
    }
    
    const bloquesArray = Object.keys(bloquesMap)
      .sort((a, b) => Number(a) - Number(b))
      .map(k => bloquesMap[k])
    
    rutina.value = {
      ...datos,
      bloques: bloquesArray,
      progreso: {
        bloques_completados: datos.progreso?.series_realizadas ? bloquesArray.length : 0,
        total_bloques: bloquesArray.length
      }
    }
    
    // Iniciar en el primer bloque no completado
    const primerBloqueNoCompletado = bloquesArray.findIndex(b => !b.completado)
    bloqueActual.value = primerBloqueNoCompletado >= 0 ? primerBloqueNoCompletado : 0
  } catch (e) {
    error.value = e.message || 'No se pudo cargar la rutina.'
    bloqueActual.value = 0
  }
}

// Cambiar rutina seleccionada
async function cambiarRutina() {
  if (!rutinaSeleccionadaId.value) return
  await cargarRutina(rutinaSeleccionadaId.value)
}

// Continuar al siguiente bloque
async function continuarSiguienteBloque() {
  if (!rutina.value || !rutina.value.bloques) return
  
  const bloque = rutina.value.bloques[bloqueActual.value]
  if (!bloque) return
  
  guardandoProgreso.value = true
  
  try {
    // Marcar bloque actual como completado
    bloque.completado = true
    
    // Actualizar progreso localmente
    if (rutina.value.progreso) {
      rutina.value.progreso.bloques_completados = Math.min(
        rutina.value.progreso.bloques_completados + 1,
        rutina.value.progreso.total_bloques
      )
    }
    
    // Avanzar al siguiente bloque
    if (bloqueActual.value < rutina.value.bloques.length - 1) {
      bloqueActual.value++
      // Scroll al inicio
      window.scrollTo({ top: 0, behavior: 'smooth' })
    } else {
      // Ya se completaron todos los bloques - mostrar felicitación
      await Swal.fire({
        title: '¡Felicidades! 🎉',
        html: `
          <p style="font-size: 1.1rem; margin-bottom: 1rem;">Has completado tu rutina exitosamente</p>
          <p style="color: #697586; font-size: 0.95rem;">¡Sigue así! Tu dedicación te llevará a alcanzar tus objetivos.</p>
        `,
        icon: 'success',
        confirmButtonText: 'Ver mi perfil',
        confirmButtonColor: '#00D261',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showClass: {
          popup: 'animate__animated animate__fadeInUp'
        },
        hideClass: {
          popup: 'animate__animated animate__fadeOutDown'
        }
      })
      
      // Redirigir al perfil
      router.push({ name: 'ClientePerfil' })
    }
    
    // Guardar progreso en el backend (opcional, si hay endpoint)
    // await post(`/cliente/rutinas/${rutina.value.id}/registrar`, {
    //   bloques_completados: rutina.value.progreso.bloques_completados
    // })
  } catch (e) {
    console.error('Error guardando progreso:', e)
  } finally {
    guardandoProgreso.value = false
  }
}

// Bloque actual visible
const bloqueVisible = computed(() => {
  if (!rutina.value || !rutina.value.bloques) return null
  return rutina.value.bloques[bloqueActual.value] || null
})

// Hay siguiente bloque
const haySiguienteBloque = computed(() => {
  if (!rutina.value || !rutina.value.bloques) return false
  return bloqueActual.value < rutina.value.bloques.length - 1
})

// Todos los bloques completados
const todosBloquesCompletados = computed(() => {
  if (!rutina.value || !rutina.value.progreso) return false
  return rutina.value.progreso.bloques_completados >= rutina.value.progreso.total_bloques
})

// Progreso calculado
const progresoPorcentaje = computed(() => {
  if (!rutina.value?.progreso) return 0
  const { bloques_completados, total_bloques } = rutina.value.progreso
  if (total_bloques === 0) return 0
  return Math.round((bloques_completados / total_bloques) * 100)
})

// Formatear tiempo de descanso
function formatearDescanso(segundos) {
  if (!segundos) return '—'
  if (segundos < 60) return `${segundos}s`
  const minutos = Math.floor(segundos / 60)
  const segs = segundos % 60
  return segs > 0 ? `${minutos}m ${segs}s` : `${minutos}m`
}

// Label de nivel
function labelNivel(nivel) {
  const niveles = {
    principiante: 'Principiante',
    intermedio: 'Intermedio',
    avanzado: 'Avanzado'
  }
  return niveles[nivel] || nivel
}

// Obtener URL de YouTube en formato embed
function obtenerUrlVideo(videoUrl) {
  if (!videoUrl) return ''
  
  // Si ya es una URL completa de YouTube, convertirla a embed
  if (videoUrl.includes('youtube.com') || videoUrl.includes('youtu.be')) {
    let videoId = ''
    
    // Extraer el ID del video de diferentes formatos de URL de YouTube
    if (videoUrl.includes('youtube.com/watch?v=')) {
      videoId = videoUrl.split('v=')[1]?.split('&')[0]
    } else if (videoUrl.includes('youtube.com/embed/')) {
      videoId = videoUrl.split('embed/')[1]?.split('?')[0]
    } else if (videoUrl.includes('youtu.be/')) {
      videoId = videoUrl.split('youtu.be/')[1]?.split('?')[0]
    }
    
    if (videoId) {
      return `https://www.youtube.com/embed/${videoId}`
    }
  }
  
  // Si no es YouTube, retornar la URL original (por si acaso hay videos locales)
  return videoUrl
}

// Verificar si es una URL de YouTube
function esYouTube(videoUrl) {
  if (!videoUrl) return false
  return videoUrl.includes('youtube.com') || videoUrl.includes('youtu.be')
}

onMounted(async () => {
  await Promise.all([
    cargarRutinaDelDia(),
    cargarRutinasDisponibles()
  ])
})
</script>

<template>
  <div class="rutina">
    <!-- Error -->
    <div v-if="error" class="rutina__alert">{{ error }}</div>

    <!-- Sin rutina -->
    <div v-else-if="!cargando && !rutina && rutinasDisponibles.length === 0" class="rutina__empty">
      <p class="rutina__empty-title">No tienes rutinas asignadas</p>
      <p class="rutina__empty-desc">Tu entrenador te asignará una rutina cuando esté lista.</p>
    </div>

    <!-- Contenido -->
    <template v-else-if="rutina">
      <!-- Header con selector de rutinas -->
      <div class="rutina__header">
        <div class="rutina__selector-wrap">
          <label for="rutina-selector" class="rutina__selector-label">Rutina:</label>
          <select
            id="rutina-selector"
            v-model="rutinaSeleccionadaId"
            class="rutina__selector"
            @change="cambiarRutina"
          >
            <option value="">Selecciona una rutina</option>
            <option
              v-for="r in rutinasDisponibles"
              :key="r.id"
              :value="r.id"
            >
              {{ r.nombre }} ({{ r.dias?.join(', ') || 'Sin días' }})
            </option>
          </select>
        </div>
        
        <!-- Toggle de videos -->
        <button
          type="button"
          class="rutina__toggle-btn"
          :class="{ 'rutina__toggle-btn--active': !mostrarVideos }"
          @click="mostrarVideos = !mostrarVideos"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
          </svg>
          {{ mostrarVideos ? 'Ocultar videos' : 'Mostrar videos' }}
        </button>
      </div>

      <!-- Barra de progreso -->
      <section class="rutina__section rutina__progreso-section">
        <div class="rutina__progreso-header">
          <h2 class="rutina__progreso-title">PROGRESO</h2>
          <div class="rutina__progreso-stats">
            <span class="rutina__progreso-bloques">
              Bloque {{ rutina.progreso?.bloques_completados || 0 }} de {{ rutina.progreso?.total_bloques || 0 }}
            </span>
            <span class="rutina__progreso-porcentaje">{{ progresoPorcentaje }}%</span>
          </div>
        </div>
        <div class="rutina__progreso-bar">
          <div
            class="rutina__progreso-fill"
            :style="{ width: `${progresoPorcentaje}%` }"
          ></div>
        </div>
      </section>

      <!-- Bloque actual visible -->
      <section
        v-if="bloqueVisible"
        class="rutina__section rutina__bloque"
      >
        <div class="rutina__bloque-header-static">
          <div class="rutina__bloque-title-wrap">
            <h3 class="rutina__bloque-title">Bloque {{ bloqueVisible.numero }}</h3>
          
          </div>
          <span
            v-if="bloqueVisible.completado"
            class="rutina__bloque-completado"
          >
            ✓ Completado
          </span>
        </div>

        <div class="rutina__bloque-content">
          <div
            v-for="(ejercicio, idx) in bloqueVisible.ejercicios"
            :key="`${ejercicio.id}-${idx}`"
            class="rutina__ejercicio"
          >
            <!-- Video/Imagen (si está habilitado y existe) -->
            <div
              v-if="mostrarVideos && ejercicio.video_url"
              class="rutina__ejercicio-media"
            >
              <!-- YouTube iframe -->
              <iframe
                v-if="esYouTube(ejercicio.video_url)"
                :src="obtenerUrlVideo(ejercicio.video_url)"
                class="rutina__video-iframe"
                frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                referrerpolicy="strict-origin-when-cross-origin"
                allowfullscreen
                title="Video del ejercicio"
              ></iframe>
              <!-- Video local (si no es YouTube) -->
              <video
                v-else
                :src="obtenerUrlVideo(ejercicio.video_url)"
                controls
                playsinline
                class="rutina__video"
                preload="metadata"
                crossorigin="anonymous"
              >
                Tu navegador no soporta videos.
              </video>
            </div>
            <!-- Placeholder si no hay video pero está habilitado -->
            <div
              v-else-if="mostrarVideos && !ejercicio.video_url"
              class="rutina__ejercicio-media rutina__ejercicio-media--placeholder"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
              </svg>
              <p>Sin video disponible</p>
            </div>

            <!-- Detalles del ejercicio (sección oscura) -->
            <div class="rutina__ejercicio-info">
              <h4 class="rutina__ejercicio-nombre">{{ ejercicio.nombre }}</h4>
              
              <div class="rutina__ejercicio-separator"></div>
              
              <div class="rutina__ejercicio-details">
                <div class="rutina__ejercicio-detail">
                  <span class="rutina__ejercicio-detail-label">Serie(s):</span>
                  <span class="rutina__ejercicio-detail-value">{{ ejercicio.series || '—' }}</span>
                </div>
                <div class="rutina__ejercicio-detail">
                  <span class="rutina__ejercicio-detail-label">Repetición(es):</span>
                  <span class="rutina__ejercicio-detail-value">{{ ejercicio.repeticiones || '—' }}</span>
                </div>
              </div>

              <!-- Descripción/Nota del ejercicio -->
              <div v-if="ejercicio.nota" class="rutina__ejercicio-descripcion">
                <span class="rutina__ejercicio-descripcion-text">{{ ejercicio.nota }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Botón continuar/finalizar -->
        <div class="rutina__continuar-wrap">
          <button
            type="button"
            class="rutina__continuar-btn"
            :disabled="guardandoProgreso"
            @click="continuarSiguienteBloque"
          >
            <span v-if="guardandoProgreso" class="rutina__continuar-spinner"></span>
            <span v-else-if="haySiguienteBloque">Continuar al siguiente bloque</span>
            <span v-else>Finalizar rutina</span>
          </button>
        </div>
      </section>
    </template>

    <!-- Estado: Sin rutina del día pero hay rutinas disponibles -->
    <div v-else-if="!cargando && !rutina && rutinasDisponibles.length > 0" class="rutina__empty">
      <p class="rutina__empty-title">No hay rutina asignada para hoy</p>
      <p class="rutina__empty-desc">Puedes seleccionar otra rutina del selector arriba.</p>
      <div class="rutina__selector-wrap rutina__selector-wrap--center">
        <select
          v-model="rutinaSeleccionadaId"
          class="rutina__selector"
          @change="cambiarRutina"
        >
          <option value="">Selecciona una rutina</option>
          <option
            v-for="r in rutinasDisponibles"
            :key="r.id"
            :value="r.id"
          >
            {{ r.nombre }} ({{ r.dias?.join(', ') || 'Sin días' }})
          </option>
        </select>
      </div>
    </div>

    <!-- Loading -->
    <div v-else-if="cargando" class="rutina__loading">
      <div class="rutina__skeleton-line" style="width: 200px; height: 24px; margin-bottom: 1rem;"></div>
      <div class="rutina__skeleton-line" style="width: 100%; height: 8px; margin-bottom: 0.5rem;"></div>
      <div class="rutina__skeleton-line" style="width: 60px; height: 14px; margin-bottom: 1.5rem;"></div>
      <div class="rutina__skeleton-box" style="height: 200px; margin-bottom: 1rem;"></div>
    </div>
  </div>
</template>

<style scoped>
/* Base */
.rutina {
  min-height: 100vh;
  min-height: 100dvh;
  background: #0a0a0a;
  padding: 1rem;
  padding-top: max(1rem, env(safe-area-inset-top));
  padding-bottom: max(2rem, env(safe-area-inset-bottom));
}

/* Desktop */
@media (min-width: 768px) {
  .rutina {
    padding: 2rem;
    max-width: 1200px;
    margin: 0 auto;
  }
}

/* Alert */
.rutina__alert {
  background: rgba(239, 92, 92, 0.12);
  border: 1px solid rgba(239, 92, 92, 0.3);
  color: #EF5C5C;
  padding: 0.875rem 1rem;
  border-radius: 16px;
  font-size: 0.875rem;
  margin-bottom: 1rem;
}

/* Empty */
.rutina__empty {
  text-align: center;
  padding: 4rem 1rem;
}
.rutina__empty-title {
  font-size: 1.125rem;
  font-weight: 500;
  color: #fff;
  margin: 0 0 0.5rem;
}
.rutina__empty-desc {
  font-size: 0.875rem;
  color: #697586;
  margin: 0 0 1.5rem;
}

/* Header */
.rutina__header {
  background: #161616;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 16px;
  padding: 1.25rem;
  margin-bottom: 1rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

@media (min-width: 768px) {
  .rutina__header {
    flex-direction: row;
    gap: 1rem;
    padding: 1.5rem;
    align-items: flex-end;
  }
  
  .rutina__selector-wrap {
    flex: 1;
    width: 50%;
  }
  
  .rutina__toggle-btn {
    flex: 1;
    width: 50%;
  }
}

/* Selector */
.rutina__selector-wrap {
  width: 100%;
}
.rutina__selector-wrap--center {
  text-align: center;
}
.rutina__selector-label {
  display: block;
  font-size: 0.75rem;
  color: #fff;
  margin-bottom: 0.625rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.rutina__selector {
  width: 100%;
  padding: 0.875rem 1rem;
  background: #1e1e1e;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  color: #fff;
  font-size: 0.9375rem;
  font-family: inherit;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23697586' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 1rem center;
  background-size: 12px;
  padding-right: 2.5rem;
}
.rutina__selector:hover {
  border-color: rgba(255, 255, 255, 0.2);
  background-color: #222;
}
.rutina__selector:focus {
  outline: none;
  border-color: #00D261;
  box-shadow: 0 0 0 3px rgba(0, 210, 97, 0.1);
}
.rutina__selector option {
  background: #1e1e1e;
  color: #fff;
  padding: 0.5rem;
}

/* Toggle videos */
.rutina__toggle-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.625rem;
  padding: 0.875rem 1rem;
  background: #1e1e1e;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  color: #697586;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  width: 100%;
}
.rutina__toggle-btn:hover {
  border-color: rgba(255, 255, 255, 0.2);
  background-color: #222;
  color: #fff;
}
.rutina__toggle-btn--active {
  background: rgba(0, 210, 97, 0.15);
  border-color: #00D261;
  color: #00D261;
}
.rutina__toggle-btn--active:hover {
  background: rgba(0, 210, 97, 0.2);
  border-color: #00D261;
  color: #00D261;
}
.rutina__toggle-btn svg {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
}

/* Section */
.rutina__section {
  background: #161616;
  border-radius: 16px;
  padding: 1rem;
  margin-bottom: 0.75rem;
}
.rutina__section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.75rem;
}
.rutina__section-title {
  font-size: 0.9375rem;
  font-weight: 600;
  color: #fff;
  margin: 0;
}

/* Progreso */
.rutina__progreso-section {
  padding: 1.25rem;
}

@media (min-width: 768px) {
  .rutina__progreso-section {
    padding: 1.5rem;
  }
}
.rutina__progreso-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1rem;
  flex-wrap: wrap;
  gap: 0.75rem;
}
.rutina__progreso-title {
  font-size: 0.875rem;
  font-weight: 700;
  color: #fff;
  margin: 0;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}
.rutina__progreso-stats {
  display: flex;
  align-items: center;
  gap: 1rem;
}
.rutina__progreso-bloques {
  font-size: 0.8125rem;
  color: #697586;
  font-weight: 500;
}
.rutina__progreso-porcentaje {
  font-size: 0.9375rem;
  color: #00D261;
  font-weight: 700;
}
.rutina__progreso-bar {
  width: 100%;
  height: 12px;
  background: #1e1e1e;
  border-radius: 6px;
  overflow: hidden;
  position: relative;
  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.3);
}
.rutina__progreso-fill {
  height: 100%;
  background: linear-gradient(90deg, #00D261 0%, #00b355 100%);
  transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 2px 8px rgba(0, 210, 97, 0.4);
}

/* Bloque */
.rutina__bloque {
  padding: 0;
  overflow: hidden;
}

@media (min-width: 768px) {
  .rutina__bloque {
    max-width: 100%;
  }
}
.rutina__bloque-header-static {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem;
  background: transparent;
}
.rutina__bloque-title-wrap {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}
.rutina__bloque-title {
  font-size: 0.8125rem;
  color: #697586;
  font-weight: 500;
  margin: 0;
}
.rutina__bloque-progreso {
  font-size: 0.75rem;
  color: #697586;
  font-weight: 500;
}
.rutina__bloque-completado {
  font-size: 0.75rem;
  color: #00D261;
  font-weight: 500;
}
.rutina__bloque-content {
  padding: 0 1rem;
}

@media (min-width: 768px) {
  .rutina__bloque-content {
    padding: 0 1.5rem;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
  }
}

@media (min-width: 1024px) {
  .rutina__bloque-content {
    grid-template-columns: repeat(2, 1fr);
    gap: 2rem;
  }
}

/* Botón continuar */
.rutina__continuar-wrap {
  padding: 1rem;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  margin-top: 1rem;
}
.rutina__continuar-btn {
  width: 100%;
  padding: 1rem;
  background: linear-gradient(135deg, #00D261 0%, #00b355 100%);
  border: none;
  border-radius: 12px;
  color: #fff;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}
.rutina__continuar-btn:hover:not(:disabled) {
  background: linear-gradient(135deg, #00b355 0%, #00a050 100%);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 210, 97, 0.3);
}
.rutina__continuar-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}
.rutina__continuar-spinner {
  width: 20px;
  height: 20px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
.rutina__completado {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem 1rem;
  gap: 0.75rem;
  color: #00D261;
}
.rutina__completado svg {
  width: 48px;
  height: 48px;
}
.rutina__completado-texto {
  font-size: 1rem;
  font-weight: 600;
  color: #00D261;
  margin: 0;
}

/* Ejercicio */
.rutina__ejercicio {
  background: #161616;
  border: 2px solid #00D261;
  border-radius: 16px;
  overflow: hidden;
  margin-bottom: 0.75rem;
}
.rutina__ejercicio:last-child {
  margin-bottom: 0;
}

@media (min-width: 768px) {
  .rutina__ejercicio {
    margin-bottom: 0;
    display: flex;
    flex-direction: column;
    height: 100%;
  }
}
.rutina__ejercicio-media {
  width: 100%;
  background: #000;
  border-radius: 14px 14px 0 0;
  overflow: hidden;
  position: relative;
  aspect-ratio: 16 / 9;
  display: flex;
  align-items: center;
  justify-content: center;
}
.rutina__ejercicio-media--placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem 1rem;
  color: #697586;
  min-height: 200px;
  background: #1e1e1e;
}
.rutina__ejercicio-media--placeholder svg {
  width: 48px;
  height: 48px;
  margin-bottom: 0.5rem;
  opacity: 0.5;
}
.rutina__ejercicio-media--placeholder p {
  font-size: 0.875rem;
  margin: 0;
}
.rutina__video {
  width: 100%;
  height: 100%;
  display: block;
  object-fit: contain;
  background: #000;
}
.rutina__video-iframe {
  width: 100%;
  height: 100%;
  border: none;
  display: block;
}
.rutina__ejercicio-info {
  background: #1e1e1e;
  padding: 1.25rem;
  border-radius: 0 0 14px 14px;
}
.rutina__ejercicio-nombre {
  font-size: 1.125rem;
  font-weight: 700;
  color: #fff;
  margin: 0 0 1rem;
  line-height: 1.4;
  letter-spacing: -0.01em;
}
.rutina__ejercicio-separator {
  width: 90%;
  height: 2px;
  background: linear-gradient(90deg, transparent 0%, #00D261 50%, transparent 100%);
  margin: 0 auto 1rem;
  border-radius: 2px;
}
.rutina__ejercicio-details {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
  margin-bottom: 1rem;
}
.rutina__ejercicio-detail {
  display: flex;
  align-items: baseline;
  gap: 0.5rem;
}
.rutina__ejercicio-detail-label {
  font-size: 0.9375rem;
  color: #fff;
  font-weight: 600;
}
.rutina__ejercicio-detail-value {
  font-size: 0.9375rem;
  font-weight: 500;
  color: #fff;
}
.rutina__ejercicio-descripcion {
  padding-top: 1rem;
  border-top: 1px solid rgba(255, 255, 255, 0.15);
}
.rutina__ejercicio-descripcion-text {
  font-size: 0.9375rem;
  color: #e0e0e0;
  line-height: 1.6;
  margin: 0;
  font-weight: 400;
}

/* Responsive para móviles */
@media (max-width: 767px) {
  .rutina__ejercicio {
    margin-bottom: 0.5rem;
  }
  .rutina__ejercicio-media {
    aspect-ratio: 4 / 3;
    min-height: 200px;
  }
  .rutina__ejercicio-info {
    padding: 1rem;
  }
  .rutina__ejercicio-nombre {
    font-size: 1rem;
    font-weight: 700;
    text-transform: uppercase;
    text-align: center;
    margin-bottom: 0.875rem;
  }
  .rutina__ejercicio-details {
    gap: 0.75rem;
    margin-bottom: 0.875rem;
  }
  .rutina__ejercicio-detail {
    gap: 0.375rem;
  }
  .rutina__ejercicio-detail-label {
    font-size: 1rem;
    text-transform: uppercase;
    margin-top: 0.5rem;
  }
  .rutina__ejercicio-detail-value {
    font-size: 1.3rem;
  }
  .rutina__ejercicio-descripcion {
    padding-top: 0.875rem;
  }
  .rutina__ejercicio-descripcion-text {
    font-size: 0.875rem;
    color: #e0e0e0;
  }
  .rutina__bloque-content {
    padding: 0 0.75rem;
  }
}

/* Desktop específico */
@media (min-width: 768px) {
  .rutina__ejercicio-media {
    aspect-ratio: 16 / 9;
    min-height: auto;
  }
  
  .rutina__ejercicio-info {
    padding: 1.5rem;
    flex: 1;
    display: flex;
    flex-direction: column;
  }
  
  .rutina__ejercicio-nombre {
    font-size: 1.25rem;
  }
  
  .rutina__ejercicio-details {
    gap: 1.5rem;
  }
  
  .rutina__ejercicio-detail-label {
    font-size: 0.875rem;
  }
  
  .rutina__ejercicio-detail-value {
    font-size: 1rem;
  }
  
  .rutina__continuar-wrap {
    padding: 1.5rem;
  }
  
  .rutina__continuar-btn {
    max-width: 400px;
    margin: 0 auto;
  }
}

/* Loading / Skeleton */
.rutina__loading {
  padding-top: 1rem;
}
.rutina__skeleton-line {
  background: #252525;
  border-radius: 6px;
  animation: pulse 1.5s ease-in-out infinite;
}
.rutina__skeleton-box {
  background: #252525;
  border-radius: 12px;
  animation: pulse 1.5s ease-in-out infinite;
}
@keyframes pulse {
  0%, 100% { opacity: 0.5; }
  50% { opacity: 1; }
}
@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>

