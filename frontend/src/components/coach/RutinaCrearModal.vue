<script setup>
/**
 * RutinaCrearModal - Wizard para crear o editar rutinas con ejercicios por bloques.
 * Paso 1: Nombre, nivel, objetivo
 * Paso 2: Agregar/editar ejercicios por bloques colapsables
 * Si rutinaParaEditar está definida, modo edición (PUT y sincronizar ejercicios).
 */
import { ref, computed, watch } from 'vue'
import { useApi } from '@/composables/useApi'

const props = defineProps({
  rutinaParaEditar: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'created'])

const { get, post, put, del } = useApi()

// Estado del wizard
const paso = ref(1)
const rutinaCreadaId = ref(null)

// Paso 1: datos básicos
const formNombre = ref('')
const formNivel = ref('')
const formObjetivo = ref('')
const formError = ref('')
const formSaving = ref(false)

// Paso 2: ejercicios y bloques
const bloques = ref([[]])
const bloqueExpandido = ref(0)
const busqueda = ref('')
const filtroGrupo = ref('')
const ejerciciosDisponibles = ref([])
const cargandoEjercicios = ref(false)
const errorPaso2 = ref('')
const guardandoEjercicios = ref(false)

const NIVELES = [
  { value: 'principiante', label: 'Principiante' },
  { value: 'intermedio', label: 'Intermedio' },
  { value: 'avanzado', label: 'Avanzado' }
]

const GRUPOS_MUSCULARES = [
  { value: 'pecho', label: 'Pecho' },
  { value: 'tricep', label: 'Tríceps' },
  { value: 'bicep', label: 'Bíceps' },
  { value: 'pierna', label: 'Pierna' },
  { value: 'hombro', label: 'Hombro' },
  { value: 'pantorrilla', label: 'Pantorrilla' },
  { value: 'espalda', label: 'Espalda' },
  { value: 'gluteo', label: 'Glúteo' },
  { value: 'cardio', label: 'Cardio' }
]

function labelGrupo(value) {
  const g = GRUPOS_MUSCULARES.find(x => x.value === value)
  return g ? g.label : (value || '—')
}

/** Ejercicios filtrados por grupo y búsqueda (mínimo 3 caracteres) */
const ejerciciosFiltrados = computed(() => {
  let lista = ejerciciosDisponibles.value

  // Filtro por grupo muscular
  if (filtroGrupo.value) {
    lista = lista.filter(e => e.grupo_muscular === filtroGrupo.value)
  }

  // Búsqueda por nombre (mínimo 3 caracteres)
  if (busqueda.value.length >= 3) {
    const term = busqueda.value.toLowerCase()
    lista = lista.filter(e => e.nombre?.toLowerCase().includes(term))
  }

  return lista
})

/** Inicializar modo edición con datos de la rutina */
function initModoEdicion() {
  const r = props.rutinaParaEditar
  if (!r) return
  formNombre.value = r.nombre ?? ''
  formNivel.value = r.nivel ?? ''
  formObjetivo.value = r.objetivo ?? ''
  rutinaCreadaId.value = r.id ?? null
  paso.value = 1
  formError.value = ''
  errorPaso2.value = ''
  const ejercicios = r.ejercicios ?? []
  if (ejercicios.length === 0) {
    bloques.value = [[]]
    bloqueExpandido.value = 0
    return
  }
  const porBloque = {}
  for (const e of ejercicios) {
    const b = e.bloque ?? 1
    if (!porBloque[b]) porBloque[b] = []
    porBloque[b].push({
      rutina_ejercicio_id: e.rutina_ejercicio_id ?? null,
      ejercicio_id: e.id,
      nombre: e.nombre ?? '—',
      grupo_muscular: e.grupo_muscular,
      video_url: e.video_url,
      series: e.series ?? 3,
      repeticiones: e.repeticiones ?? 10,
      descanso_segundos: e.descanso_segundos ?? 60,
      nota: e.nota ?? ''
    })
  }
  const ordenBloques = Object.keys(porBloque).sort((a, b) => Number(a) - Number(b))
  bloques.value = ordenBloques.map(k => porBloque[Number(k)])
  bloqueExpandido.value = 0
}

watch(() => props.rutinaParaEditar, (val) => {
  if (val) initModoEdicion()
}, { immediate: true })

/** Cargar ejercicios desde la API */
async function cargarEjercicios() {
  cargandoEjercicios.value = true
  try {
    const res = await get('/coach/ejercicios?per_page=200')
    // Soportar respuesta paginada: datos puede estar en res.datos o res.data?.datos
    const lista = res?.data?.datos ?? res?.datos ?? []
    ejerciciosDisponibles.value = Array.isArray(lista) ? lista : []
  } catch {
    ejerciciosDisponibles.value = []
  } finally {
    cargandoEjercicios.value = false
  }
}

/** Paso 1: crear o actualizar rutina */
async function enviarPaso1() {
  formError.value = ''
  const nombre = formNombre.value.trim()
  const nivel = formNivel.value
  const objetivo = formObjetivo.value.trim()

  if (!nombre) {
    formError.value = 'El nombre de la rutina es requerido.'
    return
  }
  if (!nivel) {
    formError.value = 'El nivel es requerido.'
    return
  }
  if (!objetivo) {
    formError.value = 'El objetivo es requerido.'
    return
  }

  const esEdicion = !!props.rutinaParaEditar?.id
  formSaving.value = true
  try {
    if (esEdicion) {
      const id = props.rutinaParaEditar.id
      await put(`/coach/rutinas/${id}`, { nombre, nivel, objetivo })
      rutinaCreadaId.value = id
      paso.value = 2
      await cargarEjercicios()
    } else {
      const res = await post('/coach/rutinas', { nombre, nivel, objetivo })
      const id = res?.datos?.id
      if (id) {
        rutinaCreadaId.value = id
        paso.value = 2
        await cargarEjercicios()
      } else {
        emit('created')
        emit('close')
      }
    }
  } catch (e) {
    formError.value = e.errores
      ? Object.values(e.errores).flat().join(' ')
      : (e.message || (esEdicion ? 'Error al actualizar la rutina.' : 'Error al crear la rutina.'))
  } finally {
    formSaving.value = false
  }
}

/** Agregar ejercicio al bloque expandido */
function agregarEjercicio(ejercicio) {
  const idx = bloqueExpandido.value
  if (!bloques.value[idx]) bloques.value[idx] = []
  bloques.value[idx].push({
    ejercicio_id: ejercicio.id,
    nombre: ejercicio.nombre || '—',
    grupo_muscular: ejercicio.grupo_muscular,
    video_url: ejercicio.video_url,
    series: 3,
    repeticiones: 10,
    descanso_segundos: 60,
    nota: ''
  })
}

/** Quitar ejercicio de un bloque */
function quitarEjercicio(bloqueIndex, itemIndex) {
  const b = bloques.value[bloqueIndex]
  if (b) b.splice(itemIndex, 1)
}

/** Agregar nuevo bloque y expandirlo */
function agregarBloque() {
  bloques.value.push([])
  bloqueExpandido.value = bloques.value.length - 1
}

/** Expandir/colapsar bloque */
function toggleBloque(index) {
  bloqueExpandido.value = bloqueExpandido.value === index ? -1 : index
}

/** Verificar si un item de ejercicio es válido */
function esItemValido(item) {
  const eid = item.ejercicio_id
  if (eid == null || eid === '' || !Number.isInteger(Number(eid))) return false
  const s = Number(item.series)
  const r = Number(item.repeticiones)
  return Number.isInteger(s) && s >= 1 && Number.isInteger(r) && r >= 1
}

/** Terminar: guardar o sincronizar ejercicios */
async function terminarConEjercicios() {
  errorPaso2.value = ''
  const itemsAEnviar = []

  bloques.value.forEach((bloque, blockIndex) => {
    bloque.forEach((item) => {
      if (esItemValido(item)) {
        itemsAEnviar.push({
          rutina_ejercicio_id: item.rutina_ejercicio_id ?? null,
          ejercicio_id: Number(item.ejercicio_id),
          series: Number(item.series),
          repeticiones: Number(item.repeticiones),
          descanso_segundos: Number(item.descanso_segundos ?? 60) || 60,
          bloque: blockIndex + 1,
          nota: item.nota ? String(item.nota).trim() : null
        })
      }
    })
  })

  if (itemsAEnviar.length === 0) {
    errorPaso2.value = 'Agrega al menos un ejercicio para continuar.'
    return
  }

  const rutinaId = rutinaCreadaId.value
  const esEdicion = !!props.rutinaParaEditar?.id
  const ejerciciosOriginales = esEdicion ? (props.rutinaParaEditar.ejercicios ?? []) : []
  const idsOriginalesPivot = new Set(
    ejerciciosOriginales.map(e => e.rutina_ejercicio_id).filter(Boolean)
  )
  const pivotIdsEnFormulario = new Set(
    itemsAEnviar.filter(i => i.rutina_ejercicio_id).map(i => i.rutina_ejercicio_id)
  )

  guardandoEjercicios.value = true
  try {
    if (esEdicion) {
      for (const pivotId of idsOriginalesPivot) {
        if (!pivotIdsEnFormulario.has(pivotId)) {
          await del(`/coach/rutinas/${rutinaId}/ejercicios/${pivotId}`)
        }
      }
      for (const body of itemsAEnviar) {
        const { ejercicio_id, rutina_ejercicio_id, ...resto } = body
        if (rutina_ejercicio_id) {
          await put(`/coach/rutinas/${rutinaId}/ejercicios/${rutina_ejercicio_id}`, resto)
        } else {
          await post(`/coach/rutinas/${rutinaId}/ejercicios`, { ejercicio_id, ...resto })
        }
      }
    } else {
      for (const body of itemsAEnviar) {
        const { rutina_ejercicio_id: _, ...payload } = body
        await post(`/coach/rutinas/${rutinaId}/ejercicios`, payload)
      }
    }
    emit('created')
    emit('close')
  } catch (e) {
    errorPaso2.value = e.errores
      ? Object.values(e.errores).flat().join(' ')
      : (e.message || 'Error al guardar ejercicios.')
  } finally {
    guardandoEjercicios.value = false
  }
}

/** Cerrar modal */
function cerrar() {
  emit('close')
}

/** Contar ejercicios válidos en un bloque */
function contarEjercicios(bloque) {
  return bloque.filter(esItemValido).length
}
</script>

<template>
  <Teleport to="body">
    <div class="crear-modal__overlay" @click.self="cerrar">
      <div class="crear-modal" :class="{ 'crear-modal--paso2': paso === 2 }">
        <!-- PASO 1: Datos básicos -->
        <template v-if="paso === 1">
          <div class="crear-modal__header">
            <h2 class="crear-modal__title">{{ rutinaParaEditar ? 'Editar rutina' : 'Crear rutina' }}</h2>
            <button
              type="button"
              class="crear-modal__close"
              aria-label="Cerrar"
              @click="cerrar"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 6L6 18M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <form class="crear-modal__form-step1" @submit.prevent="enviarPaso1">
            <div class="crear-modal__body crear-modal__body--paso1">
              <div v-if="formError" class="crear-modal__error">{{ formError }}</div>

              <div class="crear-modal__field">
                <label for="crear-nombre" class="crear-modal__label">Nombre</label>
                <input
                  id="crear-nombre"
                  v-model="formNombre"
                  type="text"
                  class="crear-modal__input"
                  placeholder="Nombre de la rutina"
                  required
                />
              </div>

              <div class="crear-modal__field">
                <label for="crear-nivel" class="crear-modal__label">Nivel</label>
                <select
                  id="crear-nivel"
                  v-model="formNivel"
                  class="crear-modal__select"
                  required
                >
                  <option value="">Selecciona nivel</option>
                  <option v-for="n in NIVELES" :key="n.value" :value="n.value">
                    {{ n.label }}
                  </option>
                </select>
              </div>

              <div class="crear-modal__field">
                <label for="crear-objetivo" class="crear-modal__label">Objetivo</label>
                <input
                  id="crear-objetivo"
                  v-model="formObjetivo"
                  type="text"
                  class="crear-modal__input"
                  placeholder="Ej. hipertrofia, fuerza, bajar peso"
                  required
                />
              </div>
            </div>

            <div class="crear-modal__footer crear-modal__footer--paso1">
              <button
                type="button"
                class="crear-modal__btn crear-modal__btn--secondary crear-modal__btn--paso1"
                @click="cerrar"
              >
                Cancelar
              </button>
              <button
                type="submit"
                class="crear-modal__btn crear-modal__btn--primary crear-modal__btn--paso1"
                :disabled="formSaving"
              >
                {{ formSaving ? (rutinaParaEditar ? 'Guardando…' : 'Creando…') : (rutinaParaEditar ? 'Guardar y continuar' : 'Siguiente') }}
              </button>
            </div>
          </form>
        </template>

        <!-- PASO 2: Agregar ejercicios por bloques -->
        <template v-else>
          <div class="crear-modal__header">
            <h2 class="crear-modal__title">Agregar ejercicios</h2>
            <button
              type="button"
              class="crear-modal__close"
              aria-label="Cerrar"
              @click="cerrar"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 6L6 18M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <div class="crear-modal__body crear-modal__body--paso2">
            <div v-if="errorPaso2" class="crear-modal__error">{{ errorPaso2 }}</div>

            <!-- Barra de búsqueda y filtros -->
            <div class="crear-modal__filtros">
              <input
                v-model="busqueda"
                type="text"
                class="crear-modal__input crear-modal__input--busqueda"
                placeholder="Buscar ejercicio (mín. 3 caracteres)..."
              />
              <select
                v-model="filtroGrupo"
                class="crear-modal__select crear-modal__select--filtro"
              >
                <option value="">Todos los grupos</option>
                <option v-for="g in GRUPOS_MUSCULARES" :key="g.value" :value="g.value">
                  {{ g.label }}
                </option>
              </select>
            </div>

            <!-- Lista de ejercicios disponibles -->
            <div class="crear-modal__ejercicios-lista">
              <p class="crear-modal__hint">Haz clic en un ejercicio para agregarlo al bloque.</p>
              <div v-if="cargandoEjercicios" class="crear-modal__loading">
                Cargando ejercicios…
              </div>
              <div v-else-if="!ejerciciosFiltrados.length" class="crear-modal__empty">
                No hay ejercicios. Crea ejercicios en el catálogo o ajusta los filtros.
              </div>
              <div v-else class="crear-modal__tabla-wrap">
                <div
                  v-for="e in ejerciciosFiltrados"
                  :key="e.id"
                  class="crear-modal__ejercicio-item"
                  @click="agregarEjercicio(e)"
                >
                  <div class="crear-modal__ejercicio-info">
                    <span class="crear-modal__ejercicio-nombre">{{ e.nombre || '—' }}</span>
                    <span class="crear-modal__ejercicio-grupo">{{ labelGrupo(e.grupo_muscular) }}</span>
                  </div>
                  <span v-if="e.video_url" class="crear-modal__ejercicio-video">VIDEO</span>
                </div>
              </div>
            </div>

            <!-- Bloques -->
            <div class="crear-modal__bloques">
              <div
                v-for="(bloque, idx) in bloques"
                :key="idx"
                class="crear-modal__bloque"
                :class="{
                  'crear-modal__bloque--expandido': bloqueExpandido === idx,
                  'crear-modal__bloque--colapsado': bloqueExpandido !== idx
                }"
              >
                <!-- Header del bloque (clickeable para expandir/colapsar) -->
                <div
                  class="crear-modal__bloque-header"
                  @click="toggleBloque(idx)"
                >
                  <span class="crear-modal__bloque-titulo">
                    Bloque {{ idx + 1 }}
                    <span v-if="bloqueExpandido !== idx" class="crear-modal__bloque-contador">
                      — {{ contarEjercicios(bloque) }} ejercicio{{ contarEjercicios(bloque) !== 1 ? 's' : '' }}
                    </span>
                  </span>
                  <svg
                    class="crear-modal__bloque-chevron"
                    :class="{ 'crear-modal__bloque-chevron--up': bloqueExpandido === idx }"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <path d="M6 9l6 6 6-6"/>
                  </svg>
                </div>

                <!-- Contenido del bloque (solo si está expandido) -->
                <div v-if="bloqueExpandido === idx" class="crear-modal__bloque-contenido">
                  <div v-if="!bloque.length" class="crear-modal__bloque-empty">
                    Aún no has añadido ejercicios. Haz clic en uno de la lista.
                  </div>
                  <div v-else class="crear-modal__bloque-ejercicios">
                    <div
                      v-for="(item, itemIdx) in bloque"
                      :key="itemIdx"
                      class="crear-modal__bloque-item"
                    >
                      <div class="crear-modal__bloque-item-header">
                        <span class="crear-modal__bloque-item-nombre">{{ item.nombre }}</span>
                        <button
                          type="button"
                          class="crear-modal__bloque-item-remove"
                          @click.stop="quitarEjercicio(idx, itemIdx)"
                          aria-label="Quitar"
                        >
                          &times;
                        </button>
                      </div>
                      <div class="crear-modal__bloque-item-inputs">
                        <div class="crear-modal__bloque-item-row">
                          <div class="crear-modal__bloque-item-field">
                            <label class="crear-modal__bloque-item-label">Series</label>
                            <input
                              v-model.number="item.series"
                              type="number"
                              min="1"
                              class="crear-modal__bloque-item-input"
                            />
                          </div>
                          <div class="crear-modal__bloque-item-field">
                            <label class="crear-modal__bloque-item-label">Reps</label>
                            <input
                              v-model.number="item.repeticiones"
                              type="number"
                              min="1"
                              class="crear-modal__bloque-item-input"
                            />
                          </div>
                          <div class="crear-modal__bloque-item-field">
                            <label class="crear-modal__bloque-item-label">Descanso</label>
                            <input
                              v-model.number="item.descanso_segundos"
                              type="number"
                              min="0"
                              class="crear-modal__bloque-item-input crear-modal__bloque-item-input--descanso"
                            />
                          </div>
                        </div>
                        <div class="crear-modal__bloque-item-field crear-modal__bloque-item-field--nota">
                          <label class="crear-modal__bloque-item-label">Nota</label>
                          <input
                            v-model="item.nota"
                            type="text"
                            class="crear-modal__bloque-item-input crear-modal__bloque-item-input--nota"
                            placeholder="Opcional"
                          />
                        </div>
                      </div>
                    </div>
                  </div>
                  <p class="crear-modal__bloque-hint">
                    Haz clic en otro ejercicio de la lista para agregarlo a este bloque.
                  </p>
                </div>
              </div>
            </div>
          </div>

          <div class="crear-modal__footer crear-modal__footer--paso2">
            <button
              type="button"
              class="crear-modal__btn crear-modal__btn--secondary crear-modal__btn--paso2"
              @click="agregarBloque"
            >
              + Bloque
            </button>
            <button
              type="button"
              class="crear-modal__btn crear-modal__btn--primary crear-modal__btn--paso2"
              :disabled="guardandoEjercicios"
              @click="terminarConEjercicios"
            >
              {{ guardandoEjercicios ? 'Guardando…' : 'Terminar' }}
            </button>
          </div>
        </template>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.crear-modal__overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.75);
  display: flex;
  align-items: flex-end;
  justify-content: center;
  z-index: 1000;
  animation: crear-modal-fade 0.2s ease;
}

@keyframes crear-modal-fade {
  from { opacity: 0; }
  to { opacity: 1; }
}

.crear-modal {
  background: #161616;
  border-radius: 20px 20px 0 0;
  width: 100%;
  max-width: 480px;
  max-height: 95vh;
  min-height: 50vh;
  display: flex;
  flex-direction: column;
  animation: crear-modal-slide 0.3s ease;
}

.crear-modal--paso2 {
  max-width: 540px;
  height: 90vh;
  max-height: 90vh;
}

@keyframes crear-modal-slide {
  from { transform: translateY(100%); }
  to { transform: translateY(0); }
}

@media (min-width: 640px) {
  .crear-modal__overlay {
    align-items: center;
  }
  .crear-modal {
    border-radius: 20px;
    max-height: 92vh;
  }
  .crear-modal--paso2 {
    height: 90vh;
    max-height: 90vh;
  }
}

.crear-modal__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.25rem 1.25rem 0;
  flex-shrink: 0;
}

.crear-modal__title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #fff;
  margin: 0;
  flex: 1;
  min-width: 0;
}

.crear-modal__close {
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

.crear-modal__close:hover {
  background: #333;
  color: #fff;
}

.crear-modal__close svg {
  width: 18px;
  height: 18px;
}

.crear-modal__body {
  padding: 1rem 1.25rem;
  overflow-y: auto;
  flex: 1;
  min-height: 0;
}

.crear-modal__form-step1 {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-height: 0;
}

.crear-modal__body--paso1 {
  flex: 1;
  overflow-y: auto;
  min-height: 0;
}

.crear-modal__body--paso2 {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.crear-modal__error {
  font-size: 0.875rem;
  color: #EF5C5C;
  margin-bottom: 0.75rem;
  padding: 0.75rem;
  background: rgba(239, 92, 92, 0.12);
  border: 1px solid rgba(239, 92, 92, 0.3);
  border-radius: 12px;
}

.crear-modal__field {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
  margin-bottom: 0.875rem;
}

.crear-modal__label {
  font-size: 0.8125rem;
  font-weight: 500;
  color: #697586;
}

.crear-modal__input,
.crear-modal__select {
  background: #1e1e1e;
  border: 1px solid #252525;
  border-radius: 12px;
  padding: 0.75rem 1rem;
  font-size: 0.875rem;
  color: #fff;
  width: 100%;
  transition: border-color 0.2s;
}

.crear-modal__input::placeholder {
  color: #697586;
}

.crear-modal__input:focus,
.crear-modal__input:focus-visible,
.crear-modal__select:focus,
.crear-modal__select:focus-visible {
  outline: none;
  border-color: #00D261;
  box-shadow: none;
}

.crear-modal__select option {
  background: #1e1e1e;
  color: #fff;
}

/* Filtros paso 2 */
.crear-modal__filtros {
  display: flex;
  gap: 0.75rem;
  flex-shrink: 0;
}

.crear-modal__input--busqueda {
  flex: 1;
  min-width: 0;
}

.crear-modal__select--filtro {
  width: auto;
  min-width: 140px;
}

/* Lista de ejercicios disponibles */
.crear-modal__ejercicios-lista {
  flex-shrink: 0;
}

.crear-modal__hint {
  font-size: 0.75rem;
  color: #697586;
  margin: 0 0 0.5rem;
}

.crear-modal__loading,
.crear-modal__empty {
  font-size: 0.8125rem;
  color: #697586;
  padding: 1rem;
  text-align: center;
  background: #1e1e1e;
  border-radius: 12px;
}

.crear-modal__tabla-wrap {
  max-height: 180px;
  overflow-y: auto;
  border: 1px solid #252525;
  border-radius: 12px;
  background: #1e1e1e;
}

.crear-modal__ejercicio-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #252525;
  cursor: pointer;
  transition: background 0.2s;
}

.crear-modal__ejercicio-item:last-child {
  border-bottom: none;
}

.crear-modal__ejercicio-item:hover {
  background: rgba(0, 210, 97, 0.08);
}

.crear-modal__ejercicio-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
}

.crear-modal__ejercicio-nombre {
  font-size: 0.875rem;
  font-weight: 500;
  color: #fff;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.crear-modal__ejercicio-grupo {
  font-size: 0.75rem;
  color: #697586;
}

.crear-modal__ejercicio-video {
  font-size: 0.6875rem;
  font-weight: 600;
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
  background: rgba(0, 210, 97, 0.15);
  color: #00D261;
  flex-shrink: 0;
}

/* Bloques */
.crear-modal__bloques {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  flex: 1;
  min-height: 0;
  overflow-y: auto;
}

.crear-modal__bloque {
  border: 1px solid #252525;
  border-radius: 12px;
  background: #161616;
  overflow: hidden;
  transition: border-color 0.2s;
}

.crear-modal__bloque--expandido {
  border-color: #00D261;
}

.crear-modal__bloque-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.875rem 1rem;
  cursor: pointer;
  transition: background 0.2s;
}

.crear-modal__bloque-header:hover {
  background: rgba(255, 255, 255, 0.03);
}

.crear-modal__bloque--expandido .crear-modal__bloque-header {
  background: rgba(0, 210, 97, 0.08);
}

.crear-modal__bloque-titulo {
  font-size: 0.875rem;
  font-weight: 600;
  color: #fff;
}

.crear-modal__bloque--expandido .crear-modal__bloque-titulo {
  color: #00D261;
}

.crear-modal__bloque-contador {
  font-weight: 400;
  color: #697586;
}

.crear-modal__bloque-chevron {
  width: 18px;
  height: 18px;
  color: #697586;
  transition: transform 0.2s;
  flex-shrink: 0;
}

.crear-modal__bloque-chevron--up {
  transform: rotate(180deg);
}

.crear-modal__bloque-contenido {
  margin-top: 0.5rem;
  padding: 0 0.5rem 1rem;
  max-height: 280px;
  overflow-y: auto;
}

.crear-modal__bloque-empty {
  font-size: 0.8125rem;
  color: #697586;
  padding: 0.75rem 0;
}

.crear-modal__bloque-ejercicios {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.crear-modal__bloque-item {
  background: #1e1e1e;
  border: 1px solid #252525;
  border-radius: 10px;
  padding: 0.75rem;
}

.crear-modal__bloque-item-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}

.crear-modal__bloque-item-nombre {
  font-size: 0.9375rem;
  font-weight: 500;
  color: #fff;
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.crear-modal__bloque-item-remove {
  width: 1.75rem;
  height: 1.75rem;
  padding: 0;
  font-size: 1.125rem;
  line-height: 1;
  color: #697586;
  background: #252525;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: color 0.2s, background 0.2s;
  flex-shrink: 0;
}

.crear-modal__bloque-item-remove:hover {
  color: #fff;
  background: #EF5C5C;
}

.crear-modal__bloque-item-inputs {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.crear-modal__bloque-item-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.crear-modal__bloque-item-field {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.crear-modal__bloque-item-field--nota {
  width: 100%;
}

.crear-modal__bloque-item-label {
  font-size: 0.6875rem;
  font-weight: 500;
  color: #697586;
}

.crear-modal__bloque-item-input {
  width: 3.5rem;
  background: #161616;
  border: 1px solid #252525;
  border-radius: 8px;
  padding: 0.375rem 0.5rem;
  font-size: 0.8125rem;
  color: #fff;
  transition: border-color 0.2s;
}

.crear-modal__bloque-item-input:focus,
.crear-modal__bloque-item-input:focus-visible {
  outline: none;
  border-color: #00D261;
  box-shadow: none;
}

.crear-modal__bloque-item-input--descanso {
  width: 4rem;
}

.crear-modal__bloque-item-input--nota {
  flex: 1;
  width: 100%;
  min-width: 0;
  padding: 0.5rem 0.625rem;
  min-height: 2.25rem;
}

.crear-modal__bloque-hint {
  font-size: 0.6875rem;
  color: #697586;
  margin: 0.5rem 0 0;
}

/* Footer */
.crear-modal__footer {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  padding: 1rem 1.25rem;
  padding-bottom: max(1rem, env(safe-area-inset-bottom));
  border-top: 1px solid #252525;
  flex-shrink: 0;
}

.crear-modal__footer--paso1 {
  display: flex;
  flex-direction: row;
  align-items: stretch;
  gap: 0.75rem;
  margin-top: auto;
  padding: 1.25rem 1.25rem;
  padding-bottom: max(1.25rem, env(safe-area-inset-bottom));
  border-top: 1px solid #252525;
  flex-shrink: 0;
}

.crear-modal__footer--paso1 .crear-modal__btn--secondary {
  flex: 0.3;
}

.crear-modal__footer--paso1 .crear-modal__btn--primary {
  flex: 0.7;
  margin-left: 0;
}

.crear-modal__btn--paso1 {
  padding: 1.125rem 1.5rem;
  font-size: 0.9375rem;
  min-height: 2.75rem;
}

.crear-modal__footer--paso2 {
  display: flex;
  flex-direction: row;
  align-items: stretch;
  gap: 0.75rem;
  padding: 1.25rem 1.25rem;
  padding-bottom: max(1.25rem, env(safe-area-inset-bottom));
}

.crear-modal__footer--paso2 .crear-modal__btn--secondary {
  flex: 0.4;
}

.crear-modal__footer--paso2 .crear-modal__btn--primary {
  flex: 0.6;
  margin-left: 0;
}

.crear-modal__btn--paso2 {
  padding: 1.125rem 1.5rem;
  font-size: 0.9375rem;
  min-height: 2.75rem;
}

.crear-modal__btn {
  padding: 0.625rem 1rem;
  font-size: 0.8125rem;
  font-weight: 500;
  border-radius: 12px;
  cursor: pointer;
  transition: opacity 0.2s, background 0.2s, border-color 0.2s;
}

.crear-modal__btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.crear-modal__btn--secondary {
  background: #252525;
  color: #fff;
  border: 1px solid #252525;
}

.crear-modal__btn--secondary:hover:not(:disabled) {
  background: #333;
  border-color: #333;
}

.crear-modal__btn--primary {
  background: #00D261;
  color: #0a0a0a;
  border: none;
  margin-left: auto;
}

.crear-modal__btn--primary:hover:not(:disabled) {
  background: #00b355;
}
</style>
