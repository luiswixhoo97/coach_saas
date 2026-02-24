<script setup>
import { ref, reactive, watch, computed } from 'vue'
import { useApi } from '@/composables/useApi'
import Swal from 'sweetalert2'

const props = defineProps({
  evaluacion: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'actualizado'])

const { get, post } = useApi()

const evaluacionDetalle = ref(null)
const cargando = ref(false)
const parametrosDisponibles = ref([])
const cargandoParametros = ref(false)
const guardando = ref(false)
const error = ref('')

// Lista reactiva: cada item { parametro_id, nombre, unidad_medida, valor, notas }
// para que v-model actualice y el computed parametrosConValor reaccione
const formParametros = reactive([])

function construirFormParametros() {
  const params = parametrosDisponibles.value
  const registrados = evaluacionDetalle.value?.parametros ?? []
  const nuevaLista = params.map((p) => {
    const reg = registrados.find((r) => r.nombre === p.nombre)
    return {
      parametro_id: p.id,
      nombre: p.nombre,
      unidad_medida: p.unidad_medida ?? '',
      valor: reg ? reg.valor : '',
      notas: reg ? (reg.notas || '') : ''
    }
  })
  formParametros.splice(0, formParametros.length, ...nuevaLista)
}

watch(
  () => props.evaluacion?.id,
  async (id) => {
    if (!id) {
      evaluacionDetalle.value = null
      formParametros.splice(0, formParametros.length)
      return
    }
    error.value = ''
    await cargarDetalle()
    await cargarParametrosDisponibles()
    construirFormParametros()
  },
  { immediate: true }
)

watch(
  () => [evaluacionDetalle.value?.parametros, parametrosDisponibles.value],
  () => {
    if (evaluacionDetalle.value && parametrosDisponibles.value.length > 0) {
      construirFormParametros()
    }
  },
  { deep: true }
)

async function cargarDetalle() {
  if (!props.evaluacion?.id) return
  cargando.value = true
  try {
    const res = await get(`/coach/evaluaciones/${props.evaluacion.id}`)
    evaluacionDetalle.value = res.datos ?? res.data ?? res
  } catch {
    evaluacionDetalle.value = null
    error.value = 'No se pudo cargar la evaluación.'
  } finally {
    cargando.value = false
  }
}

async function cargarParametrosDisponibles() {
  try {
    cargandoParametros.value = true
    const res = await get('/coach/parametros')
    parametrosDisponibles.value = res.datos ?? res.data ?? []
  } catch {
    parametrosDisponibles.value = []
  } finally {
    cargandoParametros.value = false
  }
}

const parametrosConValor = computed(() =>
  formParametros.filter((f) => f.valor != null && String(f.valor).trim() !== '')
)

async function guardarParametros() {
  const conValor = parametrosConValor.value
  if (conValor.length === 0) {
    error.value = 'Ingresa al menos un valor en algún parámetro.'
    return
  }
  if (!evaluacionDetalle.value?.id) return

  const confirmacion = await Swal.fire({
    icon: 'question',
    title: '¿Finalizar evaluación?',
    text: '¿Estás seguro de finalizar la evaluación? Se guardarán los parámetros y la evaluación quedará como completada.',
    showCancelButton: true,
    confirmButtonText: 'Sí, finalizar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#00D261',
    cancelButtonColor: '#697586'
  })
  if (!confirmacion.isConfirmed) return

  error.value = ''
  guardando.value = true
  try {
    await Promise.all(
      conValor.map((f) =>
        post(`/coach/evaluaciones/${evaluacionDetalle.value.id}/parametros`, {
          parametro_id: Number(f.parametro_id),
          valor: String(f.valor).trim(),
          notas: f.notas != null && String(f.notas).trim() !== '' ? String(f.notas).trim() : null
        })
      )
    )
    await Swal.fire({
      icon: 'success',
      title: 'Guardado',
      text: 'Parámetros registrados correctamente.',
      confirmButtonColor: '#00D261'
    })
    emit('actualizado')
    cerrar()
  } catch (e) {
    error.value = e.response?.data?.mensaje || 'Error al guardar los parámetros.'
  } finally {
    guardando.value = false
  }
}

function cerrar() {
  emit('close')
}

function estadoTexto(estado) {
  const s = estado || 'agendada'
  return s.charAt(0).toUpperCase() + s.slice(1)
}
</script>

<template>
  <Teleport to="body">
    <div v-if="evaluacion" class="detalle-eval-modal__overlay" @click.self="cerrar">
      <div class="detalle-eval-modal">
        <div class="detalle-eval-modal__header">
          <h2 class="detalle-eval-modal__title">Detalle de evaluación</h2>
          <button
            type="button"
            class="detalle-eval-modal__close"
            aria-label="Cerrar"
            @click="cerrar"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 6L6 18M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <div class="detalle-eval-modal__body">
          <div v-if="cargando" class="detalle-eval-modal__loading">
            <p>Cargando...</p>
          </div>

          <template v-else-if="evaluacionDetalle">
            <div class="detalle-eval-modal__cita">
              <div class="detalle-eval-modal__cita-row">
                <span class="detalle-eval-modal__cita-fecha">{{ evaluacionDetalle.fecha }} {{ evaluacionDetalle.hora || '' }}</span>
                <span
                  class="detalle-eval-modal__cita-estado"
                  :class="`detalle-eval-modal__cita-estado--${evaluacionDetalle.estado || 'agendada'}`"
                >
                  {{ estadoTexto(evaluacionDetalle.estado) }}
                </span>
              </div>
              <p v-if="evaluacionDetalle.direccion || evaluacionDetalle.ubicacion?.nombre" class="detalle-eval-modal__cita-ubicacion">
                {{ evaluacionDetalle.ubicacion?.nombre || evaluacionDetalle.direccion }}
              </p>
              <p v-if="evaluacionDetalle.modo" class="detalle-eval-modal__cita-modo">
                Modo: {{ evaluacionDetalle.modo === 'online' ? 'En línea' : 'Presencial' }}
              </p>
            </div>

            <div class="detalle-eval-modal__parametros-section">
              <h3 class="detalle-eval-modal__section-title">Registrar parámetros</h3>
              <p class="detalle-eval-modal__section-desc">Llena solo los que necesites; al guardar se registrarán los que tengan valor.</p>
              <p v-if="error" class="detalle-eval-modal__error">{{ error }}</p>
              <div v-if="cargandoParametros" class="detalle-eval-modal__loading-inline">
                <p>Cargando parámetros...</p>
              </div>
              <div v-else-if="formParametros.length === 0" class="detalle-eval-modal__empty">
                <p class="detalle-eval-modal__empty-text">No hay parámetros disponibles. Crea parámetros en tu perfil primero.</p>
              </div>
              <div v-else class="detalle-eval-modal__scroll-params">
                <div
                  v-for="f in formParametros"
                  :key="f.parametro_id"
                  class="detalle-eval-modal__param-row"
                >
                  <label :for="`param-valor-${f.parametro_id}`" class="detalle-eval-modal__param-label">
                    {{ f.nombre }} <span v-if="f.unidad_medida" class="detalle-eval-modal__param-unit">({{ f.unidad_medida }})</span>
                  </label>
                  <div class="detalle-eval-modal__param-inputs">
                    <input
                      :id="`param-valor-${f.parametro_id}`"
                      v-model="f.valor"
                      type="text"
                      class="detalle-eval-modal__input"
                      :placeholder="`Ej: 72.5`"
                    />
                    <input
                      :id="`param-notas-${f.parametro_id}`"
                      v-model="f.notas"
                      type="text"
                      class="detalle-eval-modal__input detalle-eval-modal__input--notes"
                      placeholder="Notas (opcional)"
                    />
                  </div>
                </div>
              </div>
            </div>
          </template>

          <div v-else-if="error" class="detalle-eval-modal__error-block">
            {{ error }}
          </div>
        </div>

        <div class="detalle-eval-modal__footer">
          <button type="button" class="detalle-eval-modal__btn detalle-eval-modal__btn--danger" @click="cerrar">
            Cerrar
          </button>
          <button
            v-if="formParametros.length > 0"
            type="button"
            class="detalle-eval-modal__btn detalle-eval-modal__btn--primary"
            :disabled="guardando || parametrosConValor.length === 0"
            @click="guardarParametros"
          >
            {{ guardando ? 'Guardando...' : 'Guardar parámetros' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.detalle-eval-modal__overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.detalle-eval-modal {
  background: #1e1e1e;
  border-radius: 12px;
  max-width: 480px;
  width: 100%;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
  border: 1px solid #2a2a2a;
}

.detalle-eval-modal__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid #2a2a2a;
  flex-shrink: 0;
}

.detalle-eval-modal__title {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 600;
  color: #fff;
}

.detalle-eval-modal__close {
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

.detalle-eval-modal__close:hover {
  color: #fff;
  background: #2a2a2a;
}

.detalle-eval-modal__close svg {
  width: 20px;
  height: 20px;
}

.detalle-eval-modal__body {
  padding: 1.25rem;
  overflow-y: auto;
  flex: 1;
}

.detalle-eval-modal__loading,
.detalle-eval-modal__error-block {
  text-align: center;
  padding: 1.5rem;
  color: #697586;
}

.detalle-eval-modal__error-block {
  color: #f44336;
}

.detalle-eval-modal__cita {
  margin-bottom: 1.25rem;
  padding: 1rem;
  background: #252525;
  border-radius: 8px;
  border: 1px solid #2a2a2a;
}

.detalle-eval-modal__cita-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.detalle-eval-modal__cita-fecha {
  font-size: 1rem;
  font-weight: 600;
  color: #fff;
}

.detalle-eval-modal__cita-estado {
  font-size: 0.75rem;
  text-transform: capitalize;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
}

.detalle-eval-modal__cita-estado--agendada {
  background: rgba(255, 193, 7, 0.2);
  color: #ffc107;
}

.detalle-eval-modal__cita-estado--confirmada {
  background: rgba(33, 150, 243, 0.2);
  color: #2196f3;
}

.detalle-eval-modal__cita-estado--completada {
  background: rgba(0, 210, 97, 0.2);
  color: #00D261;
}

.detalle-eval-modal__cita-estado--cancelada {
  background: rgba(244, 67, 54, 0.2);
  color: #f44336;
}

.detalle-eval-modal__cita-ubicacion,
.detalle-eval-modal__cita-modo {
  margin: 0.5rem 0 0;
  font-size: 0.875rem;
  color: #697586;
}

.detalle-eval-modal__section-title {
  margin: 0 0 0.75rem;
  font-size: 0.9375rem;
  font-weight: 600;
  color: #fff;
}

.detalle-eval-modal__parametros-section {
  margin-bottom: 0;
}

.detalle-eval-modal__section-desc {
  margin: 0 0 0.75rem;
  font-size: 0.8125rem;
  color: #9ca3af;
}

.detalle-eval-modal__loading-inline {
  padding: 1rem;
  text-align: center;
  color: #697586;
  font-size: 0.875rem;
}

.detalle-eval-modal__empty {
  padding: 0.75rem;
  text-align: center;
  background: #252525;
  border-radius: 8px;
  border: 1px solid #2a2a2a;
}

.detalle-eval-modal__empty-text {
  margin: 0;
  font-size: 0.875rem;
  color: #697586;
}

/* Lista con scroll de todos los parámetros */
.detalle-eval-modal__scroll-params {
  max-height: 40vh;
  overflow-y: auto;
  padding-right: 0.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.detalle-eval-modal__scroll-params::-webkit-scrollbar {
  width: 6px;
}

.detalle-eval-modal__scroll-params::-webkit-scrollbar-track {
  background: #252525;
  border-radius: 3px;
}

.detalle-eval-modal__scroll-params::-webkit-scrollbar-thumb {
  background: #444;
  border-radius: 3px;
}

.detalle-eval-modal__param-row {
  padding: 0.75rem;
  background: #252525;
  border-radius: 8px;
  border: 1px solid #2a2a2a;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.detalle-eval-modal__param-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #fff;
  cursor: pointer;
}

.detalle-eval-modal__param-unit {
  font-weight: 400;
  color: #9ca3af;
}

.detalle-eval-modal__param-inputs {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.detalle-eval-modal__input {
  padding: 0.5rem 0.75rem;
  border-radius: 6px;
  border: 1px solid #2a2a2a;
  background: #1e1e1e;
  color: #fff;
  font-size: 0.875rem;
}

.detalle-eval-modal__input--notes {
  font-size: 0.8125rem;
  color: #9ca3af;
}

.detalle-eval-modal__input:focus {
  outline: none;
  border-color: #00D261;
}

.detalle-eval-modal__error {
  margin: 0 0 0.75rem;
  font-size: 0.875rem;
  color: #f44336;
}

.detalle-eval-modal__btn {
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  border: none;
  transition: background 0.2s, opacity 0.2s;
}

.detalle-eval-modal__btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.detalle-eval-modal__btn--primary {
  background: #00D261;
  color: #0a0a0a;
}

.detalle-eval-modal__btn--primary:hover:not(:disabled) {
  background: #00b855;
}

.detalle-eval-modal__btn--danger {
  background: #EF5C5C;
  color: #fff;
}

.detalle-eval-modal__btn--danger:hover:not(:disabled) {
  background: #dc4c4c;
  opacity: 0.9;
}

.detalle-eval-modal__footer {
  padding: 1rem 1.25rem;
  border-top: 1px solid #2a2a2a;
  flex-shrink: 0;
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  gap: 1rem;
  align-items: center;
}
</style>
