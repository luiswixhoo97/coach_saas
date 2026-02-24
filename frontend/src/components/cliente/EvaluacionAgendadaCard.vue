<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '@/composables/useApi'

const props = defineProps({
  evaluacion: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['confirmada', 'reagendada'])

const router = useRouter()
const { get, post, cargando } = useApi()
const evaluacionData = ref(props.evaluacion)
const confirmando = ref(false)
const error = ref('')

const esLink = computed(() => {
  if (!evaluacionData.value?.ubicacion_o_link) return false
  const link = evaluacionData.value.ubicacion_o_link
  return link.startsWith('http://') || link.startsWith('https://') || link.startsWith('www.')
})

const esGoogleMaps = computed(() => {
  if (!evaluacionData.value?.ubicacion_o_link) return false
  return evaluacionData.value.ubicacion_o_link.includes('google.com.mx/maps') || 
         evaluacionData.value.ubicacion_o_link.includes('google.com/maps')
})

const textoUbicacion = computed(() => {
  // Si hay dirección guardada, mostrarla
  if (evaluacionData.value?.direccion) {
    return evaluacionData.value.direccion
  }
  
  // Si no hay dirección pero hay link, mostrar texto según el tipo
  if (!evaluacionData.value?.ubicacion_o_link) return ''
  
  const link = evaluacionData.value.ubicacion_o_link
  
  // Si es Google Maps, mostrar texto amigable
  if (esGoogleMaps.value) {
    return 'Ver ubicación en Google Maps'
  }
  
  // Si es otro tipo de link (videollamada), mostrar el link completo
  if (esLink.value) {
    return link
  }
  
  // Si es texto plano, mostrarlo tal cual
  return link
})

const estadoTexto = computed(() => {
  const estado = evaluacionData.value?.estado
  if (estado === 'agendada') return 'Agendada'
  if (estado === 'confirmada') return 'Confirmada'
  return estado || 'Agendada'
})

const fechaFormateada = computed(() => {
  if (!evaluacionData.value?.fecha) return ''
  
  // Parsear la fecha de forma segura para evitar problemas de zona horaria
  // Si viene como "2026-02-29", parsearlo manualmente
  const fechaStr = evaluacionData.value.fecha
  let fecha
  
  if (typeof fechaStr === 'string' && fechaStr.match(/^\d{4}-\d{2}-\d{2}$/)) {
    // Formato YYYY-MM-DD, parsear manualmente para evitar problemas de zona horaria
    const [year, month, day] = fechaStr.split('-').map(Number)
    fecha = new Date(year, month - 1, day) // month es 0-indexed en JS
  } else {
    fecha = new Date(fechaStr)
  }
  
  // Verificar que la fecha es válida
  if (isNaN(fecha.getTime())) {
    console.error('Fecha inválida:', evaluacionData.value.fecha)
    return ''
  }
  
  return fecha.toLocaleDateString('es-ES', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
})

const horaFormateada = computed(() => {
  if (!evaluacionData.value?.hora) return ''
  return evaluacionData.value.hora
})

const diasRestantes = computed(() => {
  if (!evaluacionData.value?.fecha) return null
  const fechaEvaluacion = new Date(evaluacionData.value.fecha)
  const hoy = new Date()
  hoy.setHours(0, 0, 0, 0)
  fechaEvaluacion.setHours(0, 0, 0, 0)
  
  const diffTime = fechaEvaluacion.getTime() - hoy.getTime()
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  
  return diffDays
})

const estaProxima = computed(() => {
  return diasRestantes.value !== null && diasRestantes.value >= 0 && diasRestantes.value <= 3
})

async function confirmar() {
  if (confirmando.value || evaluacionData.value?.estado !== 'agendada') return

  try {
    confirmando.value = true
    error.value = ''

    const response = await post(`/cliente/evaluaciones/${evaluacionData.value.id}/confirmar`)
    evaluacionData.value = response.datos
    emit('confirmada', evaluacionData.value)
  } catch (err) {
    error.value = err.response?.data?.mensaje || 'Error al confirmar la evaluación'
  } finally {
    confirmando.value = false
  }
}

function reagendar() {
  // Cambiar estado a reagendar y abrir chat con mensaje prellenado
  const evaluacionId = evaluacionData.value?.id
  const fecha = fechaFormateada.value
  const hora = horaFormateada.value
  
  router.push({
    name: 'ClienteChat',
    query: {
      reagendar: 'true',
      evaluacionId: evaluacionId,
      fecha: fecha,
      hora: hora
    }
  })
  
  emit('reagendada', evaluacionData.value)
}

function abrirVideollamada() {
  if (!esLink.value) return
  const link = evaluacionData.value.ubicacion_o_link
  const url = link.startsWith('http') ? link : `https://${link}`
  window.open(url, '_blank')
}

function abrirUbicacion() {
  if (!evaluacionData.value?.ubicacion_o_link) return
  const link = evaluacionData.value.ubicacion_o_link
  
  // Si es un link de Google Maps o videollamada, abrirlo
  if (link.includes('google.com.mx/maps') || link.startsWith('http://') || link.startsWith('https://') || link.startsWith('www.')) {
    const url = link.startsWith('http') ? link : (link.startsWith('www.') ? `https://${link}` : link)
    window.open(url, '_blank')
  }
}

onMounted(async () => {
  // Si no se pasó la evaluación como prop, cargarla desde la API
  if (!props.evaluacion) {
    try {
      const response = await get('/cliente/evaluacion-agendada')
      if (response.datos) {
        evaluacionData.value = response.datos
      }
    } catch (err) {
      // Si no hay evaluación agendada, simplemente no mostrar el componente
      evaluacionData.value = null
    }
  }
})
</script>

<template>
  <section v-if="evaluacionData" class="evaluacion-agendada">
    <div class="evaluacion-agendada__card" :class="{ 'evaluacion-agendada__card--proxima': estaProxima }">
      <div class="evaluacion-agendada__header">
        <div class="evaluacion-agendada__icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <polyline points="12 6 12 12 16 14"/>
          </svg>
        </div>
        <div class="evaluacion-agendada__header-info">
          <h3 class="evaluacion-agendada__title">Evaluación Agendada</h3>
          <span class="evaluacion-agendada__estado" :class="`evaluacion-agendada__estado--${evaluacionData.estado}`">
            {{ estadoTexto }}
          </span>
        </div>
      </div>

      <div class="evaluacion-agendada__content">
        <!-- Bloque: Fecha y Hora -->
        <div class="evaluacion-agendada__block">
          <div class="evaluacion-agendada__info-item">
            <svg class="evaluacion-agendada__info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
              <line x1="16" y1="2" x2="16" y2="6"/>
              <line x1="8" y1="2" x2="8" y2="6"/>
              <line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
            <div class="evaluacion-agendada__info-text">
              <span class="evaluacion-agendada__info-label">Fecha</span>
              <span class="evaluacion-agendada__info-value">{{ fechaFormateada }}</span>
            </div>
          </div>

          <div v-if="horaFormateada" class="evaluacion-agendada__info-item">
            <svg class="evaluacion-agendada__info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/>
              <polyline points="12 6 12 12 16 14"/>
            </svg>
            <div class="evaluacion-agendada__info-text">
              <span class="evaluacion-agendada__info-label">Hora</span>
              <span class="evaluacion-agendada__info-value">{{ horaFormateada }}</span>
            </div>
          </div>
        </div>

        <!-- Bloque: Ubicación -->
        <div v-if="evaluacionData.ubicacion_o_link" class="evaluacion-agendada__block">
          <div class="evaluacion-agendada__info-item">
            <svg class="evaluacion-agendada__info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
              <circle cx="12" cy="10" r="3"/>
            </svg>
            <div class="evaluacion-agendada__info-text">
              <span class="evaluacion-agendada__info-label">{{ esGoogleMaps ? 'Ubicación' : (esLink ? 'Link de videollamada' : 'Ubicación') }}</span>
              <span 
                v-if="esGoogleMaps || esLink" 
                class="evaluacion-agendada__info-value evaluacion-agendada__info-value--link"
                @click="abrirUbicacion"
              >
                {{ textoUbicacion }}
              </span>
              <span v-else class="evaluacion-agendada__info-value">
                {{ evaluacionData.ubicacion_o_link }}
              </span>
            </div>
          </div>
        </div>

        <!-- Bloque: Notas -->
        <div v-if="evaluacionData.notas" class="evaluacion-agendada__block">
          <div class="evaluacion-agendada__info-item">
            <svg class="evaluacion-agendada__info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
              <polyline points="14 2 14 8 20 8"/>
              <line x1="16" y1="13" x2="8" y2="13"/>
              <line x1="16" y1="17" x2="8" y2="17"/>
              <polyline points="10 9 9 9 8 9"/>
            </svg>
            <div class="evaluacion-agendada__info-text">
              <span class="evaluacion-agendada__info-label">Notas</span>
              <span class="evaluacion-agendada__info-value">{{ evaluacionData.notas }}</span>
            </div>
          </div>
        </div>
      </div>

      <div v-if="error" class="evaluacion-agendada__error">
        {{ error }}
      </div>

      <div v-if="evaluacionData.estado === 'agendada'" class="evaluacion-agendada__actions">
        <button
          type="button"
          class="evaluacion-agendada__btn evaluacion-agendada__btn--primary"
          @click="confirmar"
          :disabled="confirmando || cargando"
        >
          <span v-if="confirmando">Confirmando...</span>
          <span v-else>Confirmar</span>
        </button>

        <button
          type="button"
          class="evaluacion-agendada__btn evaluacion-agendada__btn--secondary"
          @click="reagendar"
          :disabled="confirmando || cargando"
        >
          Reagendar
        </button>

        <button
          v-if="esLink && !evaluacionData.ubicacion_o_link.includes('google.com.mx/maps')"
          type="button"
          class="evaluacion-agendada__btn evaluacion-agendada__btn--link"
          @click="abrirVideollamada"
        >
          Abrir videollamada
        </button>
      </div>
    </div>
  </section>
</template>

<style scoped>
.evaluacion-agendada {
  margin-bottom: 0.75rem;
}

.evaluacion-agendada__card {
  background: #161616;
  border-radius: 16px;
  padding: 1rem;
  border: 1px solid transparent;
  transition: border-color 0.2s ease;
}

.evaluacion-agendada__card--proxima {
  border-color: #FF9900;
}

.evaluacion-agendada__header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}

.evaluacion-agendada__icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 8px;
  background: rgba(0, 210, 97, 0.15);
  color: #00D261;
  flex-shrink: 0;
}

.evaluacion-agendada__icon svg {
  width: 18px;
  height: 18px;
}

.evaluacion-agendada__header-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.evaluacion-agendada__title {
  font-size: 0.9375rem;
  font-weight: 600;
  color: #fff;
  margin: 0;
}

.evaluacion-agendada__estado {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
  font-size: 0.6875rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.02em;
  width: fit-content;
}

.evaluacion-agendada__estado--agendada {
  background: rgba(255, 193, 7, 0.15);
  color: #FFC107;
}

.evaluacion-agendada__estado--confirmada {
  background: rgba(0, 210, 97, 0.15);
  color: #00D261;
}

.evaluacion-agendada__content {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}

.evaluacion-agendada__block {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  padding: 1rem;
  background: #1e1e1e;
  border-radius: 12px;
}

.evaluacion-agendada__info-item {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
}

.evaluacion-agendada__info-icon {
  width: 20px;
  height: 20px;
  color: #00D261;
  flex-shrink: 0;
  margin-top: 0.125rem;
}

.evaluacion-agendada__info-text {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
}

.evaluacion-agendada__info-label {
  font-size: 0.75rem;
  font-weight: 400;
  color: #697586;
}

.evaluacion-agendada__info-value {
  font-size: 0.875rem;
  font-weight: 500;
  color: #fff;
  word-break: break-word;
}

.evaluacion-agendada__info-value--link {
  color: #00D261;
  cursor: pointer;
  text-decoration: underline;
  transition: color 0.2s ease;
}

.evaluacion-agendada__info-value--link:hover {
  color: #00b854;
}

.evaluacion-agendada__error {
  padding: 0.875rem 1rem;
  margin-bottom: 0.75rem;
  border-radius: 16px;
  background: rgba(239, 92, 92, 0.12);
  border: 1px solid rgba(239, 92, 92, 0.3);
  color: #EF5C5C;
  font-size: 0.875rem;
}

.evaluacion-agendada__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.evaluacion-agendada__btn {
  flex: 1;
  min-width: 120px;
  padding: 0.875rem 1rem;
  border-radius: 12px;
  border: none;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.evaluacion-agendada__btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.evaluacion-agendada__btn--primary {
  background: #00D261;
  color: #fff;
}

.evaluacion-agendada__btn--primary:hover:not(:disabled) {
  background: #00b854;
}

.evaluacion-agendada__btn--secondary {
  background: transparent;
  color: #fff;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.evaluacion-agendada__btn--secondary:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.1);
  border-color: rgba(255, 255, 255, 0.3);
}

.evaluacion-agendada__btn--link {
  background: transparent;
  color: #00D261;
  border: 1px solid rgba(0, 210, 97, 0.4);
}

.evaluacion-agendada__btn--link:hover:not(:disabled) {
  background: rgba(0, 210, 97, 0.1);
  border-color: #00D261;
}
</style>


