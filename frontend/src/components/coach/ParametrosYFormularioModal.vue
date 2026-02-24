<script setup>
import { ref, computed, watch } from 'vue'
import { useApi } from '@/composables/useApi'

const props = defineProps({
  cliente: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'abrir-formulario'])

const { get } = useApi()

const tabActivo = ref('parametros') // 'parametros' | 'formulario'

// Tab Parámetros (historial)
const historialParametros = ref([])
const cargandoHistorial = ref(false)
const errorParametros = ref('')

// Tab Formulario
const formularioRespuestas = ref(null) // { formulario, respuesta, preguntas }
const cargandoFormulario = ref(false)
const errorFormulario = ref('')

watch(
  () => props.cliente?.id,
  (id) => {
    if (!id) return
    if (tabActivo.value === 'parametros') cargarHistorial()
    else cargarFormularioRespuestas()
  },
  { immediate: true }
)

watch(tabActivo, (t) => {
  if (!props.cliente?.id) return
  if (t === 'parametros') cargarHistorial()
  else cargarFormularioRespuestas()
})

watch(
  () => props.cliente?.formulario_completado,
  (completado) => {
    if (completado && tabActivo.value === 'formulario') cargarFormularioRespuestas()
  }
)

async function cargarHistorial() {
  if (!props.cliente?.id) return
  cargandoHistorial.value = true
  errorParametros.value = ''
  try {
    const res = await get(`/coach/clientes/${props.cliente.id}/parametros`)
    historialParametros.value = res.datos ?? res.data ?? []
  } catch (e) {
    errorParametros.value = e.response?.data?.mensaje || 'Error al cargar historial.'
    historialParametros.value = []
  } finally {
    cargandoHistorial.value = false
  }
}

async function cargarFormularioRespuestas() {
  if (!props.cliente?.id) return
  if (!props.cliente?.formulario_completado) {
    formularioRespuestas.value = null
    return
  }
  cargandoFormulario.value = true
  errorFormulario.value = ''
  try {
    const res = await get(`/coach/clientes/${props.cliente.id}/formulario-estandar/respuestas`)
    formularioRespuestas.value = res.datos ?? res.data ?? res
  } catch (e) {
    errorFormulario.value = e.response?.data?.mensaje || 'Error al cargar respuestas.'
    formularioRespuestas.value = null
  } finally {
    cargandoFormulario.value = false
  }
}

const parametrosAgrupados = computed(() => {
  const grupos = {}
  historialParametros.value.forEach((p) => {
    const fecha = p.fecha
    if (!grupos[fecha]) grupos[fecha] = []
    grupos[fecha].push(p)
  })
  return Object.entries(grupos)
    .sort((a, b) => new Date(b[0]) - new Date(a[0]))
    .map(([fecha, parametros]) => ({ fecha, parametros }))
})

function formatearFecha(fecha) {
  if (!fecha) return ''
  return new Date(fecha).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const respuestasObj = computed(() => {
  const r = formularioRespuestas.value?.respuesta?.respuestas
  if (!r || !Array.isArray(r)) return {}
  const out = {}
  r.forEach((v, i) => { out[i] = v })
  return out
})

function cerrar() {
  emit('close')
}

function abrirFormulario() {
  emit('abrir-formulario')
}
</script>

<template>
  <Teleport to="body">
    <div v-if="cliente" class="pyf-modal__overlay" @click.self="cerrar">
      <div class="pyf-modal">
        <div class="pyf-modal__header">
          <h2 class="pyf-modal__title">Parámetros y formulario</h2>
          <button type="button" class="pyf-modal__close" aria-label="Cerrar" @click="cerrar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 6L6 18M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <div class="pyf-modal__tabs">
          <button
            type="button"
            class="pyf-modal__tab"
            :class="{ 'pyf-modal__tab--active': tabActivo === 'parametros' }"
            @click="tabActivo = 'parametros'"
          >
            Parámetros
          </button>
          <button
            type="button"
            class="pyf-modal__tab"
            :class="{ 'pyf-modal__tab--active': tabActivo === 'formulario' }"
            @click="tabActivo = 'formulario'"
          >
            Formulario
          </button>
        </div>

        <div class="pyf-modal__body">
          <!-- Tab Parámetros: historial -->
          <div v-show="tabActivo === 'parametros'" class="pyf-modal__panel">
            <h3 class="pyf-modal__panel-title">Historial de parámetros</h3>
            <div v-if="cargandoHistorial" class="pyf-modal__loading">
              <div class="pyf-modal__skeleton" />
              <div class="pyf-modal__skeleton" />
            </div>
            <p v-else-if="errorParametros" class="pyf-modal__error">{{ errorParametros }}</p>
            <div v-else-if="parametrosAgrupados.length === 0" class="pyf-modal__empty">
              <p class="pyf-modal__empty-text">No hay parámetros registrados para este cliente.</p>
            </div>
            <div v-else class="pyf-modal__grupos">
              <div v-for="grupo in parametrosAgrupados" :key="grupo.fecha" class="pyf-modal__grupo">
                <h4 class="pyf-modal__grupo-fecha">{{ formatearFecha(grupo.fecha) }}</h4>
                <div class="pyf-modal__grupo-items">
                  <div
                    v-for="p in grupo.parametros"
                    :key="p.id"
                    class="pyf-modal__item"
                  >
                    <span class="pyf-modal__item-nombre">{{ p.parametro?.nombre || 'Parámetro' }}:</span>
                    <span class="pyf-modal__item-valor">{{ p.valor }} {{ p.parametro?.unidad_medida || '' }}</span>
                    <p v-if="p.notas" class="pyf-modal__item-notas">{{ p.notas }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Tab Formulario: respondido o card pendiente -->
          <div v-show="tabActivo === 'formulario'" class="pyf-modal__panel">
            <h3 class="pyf-modal__panel-title">Formulario</h3>
            <div v-if="cargandoFormulario" class="pyf-modal__loading">
              <div class="pyf-modal__skeleton" />
              <div class="pyf-modal__skeleton" />
            </div>
            <p v-else-if="errorFormulario" class="pyf-modal__error">{{ errorFormulario }}</p>
            <template v-else-if="!cliente?.formulario_completado">
              <div class="pyf-modal__card pyf-modal__card--formulario">
                <div class="pyf-modal__card-info">
                  <span class="pyf-modal__card-titulo">Formulario pendiente</span>
                  <span class="pyf-modal__card-desc">El cliente aún no ha contestado el formulario.</span>
                </div>
                <button
                  type="button"
                  class="pyf-modal__card-btn"
                  @click="abrirFormulario"
                >
                  Llenar formulario
                </button>
              </div>
            </template>
            <template v-else-if="formularioRespuestas">
              <div class="pyf-modal__respuestas">
                <p v-if="formularioRespuestas.respuesta?.fecha" class="pyf-modal__respuestas-fecha">
                  Fecha: {{ formatearFecha(formularioRespuestas.respuesta.fecha) }}
                </p>
                <div
                  v-for="(pregunta, index) in formularioRespuestas.formulario?.preguntas"
                  :key="index"
                  class="pyf-modal__respuesta-item"
                >
                  <label class="pyf-modal__respuesta-label">{{ pregunta.texto }}</label>
                  <div class="pyf-modal__respuesta-valor">
                    <span v-if="respuestasObj[index] === null || respuestasObj[index] === undefined || respuestasObj[index] === ''" class="pyf-modal__respuesta-vacia">
                      Sin respuesta
                    </span>
                    <span v-else-if="Array.isArray(respuestasObj[index])">
                      {{ respuestasObj[index].join(', ') }}
                    </span>
                    <span v-else>{{ respuestasObj[index] }}</span>
                  </div>
                </div>
              </div>
            </template>
            <div v-else class="pyf-modal__empty">
              <p class="pyf-modal__empty-text">No hay respuestas de formulario.</p>
            </div>
          </div>
        </div>

        <div class="pyf-modal__footer">
          <button type="button" class="pyf-modal__btn pyf-modal__btn--danger" @click="cerrar">
            Cerrar
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.pyf-modal__overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.pyf-modal {
  background: #1e1e1e;
  border-radius: 12px;
  max-width: 520px;
  width: 100%;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
  border: 1px solid #2a2a2a;
}

.pyf-modal__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid #2a2a2a;
  flex-shrink: 0;
}

.pyf-modal__title {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 600;
  color: #fff;
}

.pyf-modal__close {
  background: none;
  border: none;
  color: #697586;
  padding: 0.25rem;
  cursor: pointer;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: color 0.2s, background 0.2s;
}

.pyf-modal__close:hover {
  color: #fff;
  background: #2a2a2a;
}

.pyf-modal__close svg {
  width: 20px;
  height: 20px;
}

.pyf-modal__tabs {
  display: flex;
  border-bottom: 1px solid #2a2a2a;
  flex-shrink: 0;
}

.pyf-modal__tab {
  flex: 1;
  padding: 0.75rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #9ca3af;
  background: transparent;
  border: none;
  cursor: pointer;
  transition: color 0.2s, background 0.2s;
  border-bottom: 2px solid transparent;
}

.pyf-modal__tab:hover {
  color: #fff;
}

.pyf-modal__tab--active {
  color: #00D261;
  border-bottom-color: #00D261;
}

.pyf-modal__body {
  flex: 1;
  overflow-y: auto;
  padding: 1.25rem;
}

.pyf-modal__panel {
  min-height: 200px;
}

.pyf-modal__panel-title {
  margin: 0 0 1rem;
  font-size: 0.9375rem;
  font-weight: 600;
  color: #fff;
}

.pyf-modal__loading {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.pyf-modal__skeleton {
  height: 48px;
  background: #252525;
  border-radius: 8px;
  animation: pyf-pulse 1.5s ease-in-out infinite;
}

@keyframes pyf-pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.pyf-modal__error {
  margin: 0;
  padding: 0.75rem;
  background: rgba(239, 92, 92, 0.1);
  border: 1px solid rgba(239, 92, 92, 0.3);
  border-radius: 8px;
  color: #EF5C5C;
  font-size: 0.875rem;
}

.pyf-modal__empty {
  padding: 1rem;
  text-align: center;
  background: #252525;
  border-radius: 8px;
  border: 1px solid #2a2a2a;
}

.pyf-modal__empty-text {
  margin: 0;
  font-size: 0.875rem;
  color: #697586;
}

.pyf-modal__grupos {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.pyf-modal__grupo-fecha {
  margin: 0 0 0.5rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: #9ca3af;
  text-transform: capitalize;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid #2a2a2a;
}

.pyf-modal__grupo-items {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.pyf-modal__item {
  padding: 0.75rem;
  background: #252525;
  border-radius: 8px;
  border: 1px solid #2a2a2a;
}

.pyf-modal__item-nombre {
  font-size: 0.875rem;
  color: #9ca3af;
  margin-right: 0.5rem;
}

.pyf-modal__item-valor {
  font-size: 0.875rem;
  font-weight: 600;
  color: #fff;
}

.pyf-modal__item-notas {
  margin: 0.25rem 0 0;
  font-size: 0.75rem;
  color: #697586;
  font-style: italic;
}

/* Card tipo evaluación para formulario pendiente */
.pyf-modal__card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1rem;
  background: #252525;
  border-radius: 8px;
  border: 1px solid #2a2a2a;
  flex-wrap: wrap;
}

.pyf-modal__card--formulario {
  border-color: rgba(255, 193, 7, 0.3);
  background: rgba(255, 193, 7, 0.06);
}

.pyf-modal__card-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  min-width: 0;
  flex: 1;
}

.pyf-modal__card-titulo {
  font-size: 0.9375rem;
  font-weight: 600;
  color: #fff;
}

.pyf-modal__card-desc {
  font-size: 0.8125rem;
  color: #697586;
}

.pyf-modal__card-btn {
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  background: #00D261;
  color: #0a0a0a;
  transition: background 0.2s;
  flex-shrink: 0;
}

.pyf-modal__card-btn:hover {
  background: #00b855;
}

/* Respuestas del formulario */
.pyf-modal__respuestas {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.pyf-modal__respuestas-fecha {
  margin: 0 0 0.25rem;
  font-size: 0.8125rem;
  color: #9ca3af;
}

.pyf-modal__respuesta-item {
  padding: 0.75rem;
  background: #252525;
  border-radius: 8px;
  border: 1px solid #2a2a2a;
}

.pyf-modal__respuesta-label {
  display: block;
  font-size: 0.8125rem;
  font-weight: 500;
  color: #9ca3af;
  margin-bottom: 0.25rem;
}

.pyf-modal__respuesta-valor {
  font-size: 0.875rem;
  color: #fff;
}

.pyf-modal__respuesta-vacia {
  color: #697586;
  font-style: italic;
}

.pyf-modal__footer {
  padding: 1rem 1.25rem;
  border-top: 1px solid #2a2a2a;
  flex-shrink: 0;
}

.pyf-modal__btn {
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  border: none;
  transition: background 0.2s, opacity 0.2s;
}

.pyf-modal__btn--danger {
  background: #EF5C5C;
  color: #fff;
}

.pyf-modal__btn--danger:hover {
  background: #dc4c4c;
  opacity: 0.9;
}
</style>
