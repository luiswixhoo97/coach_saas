<script setup>
/**
 * FormularioCrearModal - Modal para crear o editar formularios con preguntas dinámicas.
 * Soporta tipos: texto, numero, seleccion, multiple
 * Para seleccion/multiple: permite agregar opciones
 */
import { ref, computed, watch } from 'vue'
import { useApi } from '@/composables/useApi'

const props = defineProps({
  formularioParaEditar: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'created'])

const { get, post, put } = useApi()

const formNombre = ref('')
const formActivo = ref(true)
const preguntas = ref([])
const formError = ref('')
const formSaving = ref(false)

const TIPOS_PREGUNTA = [
  { value: 'texto', label: 'Texto' },
  { value: 'numero', label: 'Número' },
  { value: 'seleccion', label: 'Selección única' },
  { value: 'multiple', label: 'Selección múltiple' }
]

/** Inicializar modo edición */
function initModoEdicion() {
  const f = props.formularioParaEditar
  if (!f) return
  formNombre.value = f.nombre ?? ''
  formActivo.value = f.activo ?? true
  preguntas.value = Array.isArray(f.preguntas) ? f.preguntas.map((p, idx) => ({
    ...p,
    opciones: Array.isArray(p.opciones) ? [...p.opciones] : []
  })) : []
  formError.value = ''
}

watch(() => props.formularioParaEditar, (val) => {
  if (val) {
    initModoEdicion()
  } else {
    formNombre.value = ''
    formActivo.value = true
    preguntas.value = []
    formError.value = ''
  }
}, { immediate: true })

/** Agregar nueva pregunta */
function agregarPregunta() {
  preguntas.value.push({
    texto: '',
    tipo: 'texto',
    opciones: []
  })
}

/** Eliminar pregunta */
function eliminarPregunta(index) {
  preguntas.value.splice(index, 1)
}

/** Mover pregunta arriba */
function moverArriba(index) {
  if (index === 0) return
  const temp = preguntas.value[index]
  preguntas.value[index] = preguntas.value[index - 1]
  preguntas.value[index - 1] = temp
}

/** Mover pregunta abajo */
function moverAbajo(index) {
  if (index === preguntas.value.length - 1) return
  const temp = preguntas.value[index]
  preguntas.value[index] = preguntas.value[index + 1]
  preguntas.value[index + 1] = temp
}

/** Agregar opción a pregunta */
function agregarOpcion(pregunta) {
  if (!Array.isArray(pregunta.opciones)) {
    pregunta.opciones = []
  }
  pregunta.opciones.push('')
}

/** Eliminar opción */
function eliminarOpcion(pregunta, index) {
  if (Array.isArray(pregunta.opciones)) {
    pregunta.opciones.splice(index, 1)
  }
}

/** Verificar si pregunta necesita opciones */
const necesitaOpciones = (tipo) => {
  return tipo === 'seleccion' || tipo === 'multiple'
}

/** Validar formulario */
function validar() {
  if (!formNombre.value.trim()) {
    formError.value = 'El nombre del formulario es requerido.'
    return false
  }
  if (preguntas.value.length === 0) {
    formError.value = 'Debe haber al menos una pregunta.'
    return false
  }
  for (let i = 0; i < preguntas.value.length; i++) {
    const p = preguntas.value[i]
    if (!p.texto || !p.texto.trim()) {
      formError.value = `La pregunta ${i + 1} debe tener un texto.`
      return false
    }
    if (!p.tipo) {
      formError.value = `La pregunta ${i + 1} debe tener un tipo.`
      return false
    }
    if (necesitaOpciones(p.tipo)) {
      if (!Array.isArray(p.opciones) || p.opciones.length === 0) {
        formError.value = `La pregunta ${i + 1} (${p.tipo}) debe tener al menos una opción.`
        return false
      }
      // Validar que todas las opciones tengan texto
      for (let j = 0; j < p.opciones.length; j++) {
        if (!p.opciones[j] || !p.opciones[j].trim()) {
          formError.value = `La opción ${j + 1} de la pregunta ${i + 1} no puede estar vacía.`
          return false
        }
      }
    }
  }
  return true
}

/** Enviar formulario */
async function enviarFormulario() {
  formError.value = ''
  if (!validar()) return

  formSaving.value = true
  try {
    const datos = {
      nombre: formNombre.value.trim(),
      preguntas: preguntas.value.map(p => ({
        texto: p.texto.trim(),
        tipo: p.tipo,
        opciones: necesitaOpciones(p.tipo) && Array.isArray(p.opciones)
          ? p.opciones.filter(o => o && o.trim()).map(o => o.trim())
          : null
      })),
      activo: formActivo.value
    }

    const esEdicion = !!props.formularioParaEditar?.id
    if (esEdicion) {
      await put(`/coach/formularios/${props.formularioParaEditar.id}`, datos)
    } else {
      await post('/coach/formularios', datos)
    }
    emit('created')
    emit('close')
  } catch (e) {
    formError.value = e.errores
      ? Object.values(e.errores).flat().join(' ')
      : (e.message || (props.formularioParaEditar ? 'Error al actualizar el formulario.' : 'Error al crear el formulario.'))
  } finally {
    formSaving.value = false
  }
}

function cerrar() {
  emit('close')
}
</script>

<template>
  <Teleport to="body">
    <div class="formulario-modal__overlay" @click.self="cerrar">
      <div class="formulario-modal">
        <div class="formulario-modal__header">
          <h2 class="formulario-modal__title">
            {{ formularioParaEditar ? 'Editar formulario' : 'Crear formulario' }}
          </h2>
          <button
            type="button"
            class="formulario-modal__close"
            aria-label="Cerrar"
            @click="cerrar"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 6L6 18M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <form @submit.prevent="enviarFormulario" class="formulario-modal__form">
          <div class="formulario-modal__body">
            <div v-if="formError" class="formulario-modal__error">{{ formError }}</div>

            <div class="formulario-modal__field">
              <label for="formulario-nombre" class="formulario-modal__label">Nombre del formulario</label>
              <input
                id="formulario-nombre"
                v-model="formNombre"
                type="text"
                class="formulario-modal__input"
                placeholder="Ej: Evaluación inicial"
                required
              />
            </div>

            <div class="formulario-modal__field">
              <label class="formulario-modal__label">Preguntas</label>
              <div v-if="preguntas.length === 0" class="formulario-modal__empty-preguntas">
                <p>No hay preguntas. Agrega al menos una pregunta.</p>
              </div>
              <div v-else class="formulario-modal__preguntas">
                <div
                  v-for="(pregunta, index) in preguntas"
                  :key="index"
                  class="formulario-modal__pregunta"
                >
                  <div class="formulario-modal__pregunta-header">
                    <span class="formulario-modal__pregunta-numero">{{ index + 1 }}</span>
                    <div class="formulario-modal__pregunta-actions">
                      <button
                        type="button"
                        class="formulario-modal__pregunta-action"
                        :disabled="index === 0"
                        @click="moverArriba(index)"
                        aria-label="Mover arriba"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <path d="M18 15l-6-6-6 6"/>
                        </svg>
                      </button>
                      <button
                        type="button"
                        class="formulario-modal__pregunta-action"
                        :disabled="index === preguntas.length - 1"
                        @click="moverAbajo(index)"
                        aria-label="Mover abajo"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <path d="M6 9l6 6 6-6"/>
                        </svg>
                      </button>
                      <button
                        type="button"
                        class="formulario-modal__pregunta-action formulario-modal__pregunta-action--delete"
                        @click="eliminarPregunta(index)"
                        aria-label="Eliminar pregunta"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                        </svg>
                      </button>
                    </div>
                  </div>
                  <div class="formulario-modal__pregunta-body">
                    <div class="formulario-modal__field">
                      <label :for="`pregunta-texto-${index}`" class="formulario-modal__label">Texto de la pregunta</label>
                      <input
                        :id="`pregunta-texto-${index}`"
                        v-model="pregunta.texto"
                        type="text"
                        class="formulario-modal__input"
                        placeholder="¿Cuál es tu objetivo principal?"
                        required
                      />
                    </div>
                    <div class="formulario-modal__field">
                      <label :for="`pregunta-tipo-${index}`" class="formulario-modal__label">Tipo de pregunta</label>
                      <select
                        :id="`pregunta-tipo-${index}`"
                        v-model="pregunta.tipo"
                        class="formulario-modal__select"
                        required
                      >
                        <option v-for="tipo in TIPOS_PREGUNTA" :key="tipo.value" :value="tipo.value">
                          {{ tipo.label }}
                        </option>
                      </select>
                    </div>
                    <div v-if="necesitaOpciones(pregunta.tipo)" class="formulario-modal__field">
                      <div class="formulario-modal__opciones-header">
                        <label class="formulario-modal__label">Opciones</label>
                        <button
                          type="button"
                          class="formulario-modal__btn-agregar-opcion"
                          @click="agregarOpcion(pregunta)"
                        >
                          + Agregar opción
                        </button>
                      </div>
                      <div v-if="!pregunta.opciones || pregunta.opciones.length === 0" class="formulario-modal__empty-opciones">
                        <p>No hay opciones. Agrega al menos una opción.</p>
                      </div>
                      <div v-else class="formulario-modal__opciones">
                        <div
                          v-for="(opcion, opcionIndex) in pregunta.opciones"
                          :key="opcionIndex"
                          class="formulario-modal__opcion"
                        >
                          <input
                            v-model="pregunta.opciones[opcionIndex]"
                            type="text"
                            class="formulario-modal__input"
                            :placeholder="`Opción ${opcionIndex + 1}`"
                            required
                          />
                          <button
                            type="button"
                            class="formulario-modal__opcion-delete"
                            @click="eliminarOpcion(pregunta, opcionIndex)"
                            aria-label="Eliminar opción"
                          >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                              <path d="M18 6L6 18M6 6l12 12"/>
                            </svg>
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <button
                type="button"
                class="formulario-modal__btn-agregar"
                @click="agregarPregunta"
              >
                + Agregar pregunta
              </button>
            </div>

            <div class="formulario-modal__field">
              <label class="formulario-modal__label-checkbox">
                <input
                  v-model="formActivo"
                  type="checkbox"
                  class="formulario-modal__checkbox"
                />
                <span>Formulario activo</span>
              </label>
            </div>
          </div>

          <div class="formulario-modal__footer">
            <button
              type="button"
              class="formulario-modal__btn formulario-modal__btn--secondary"
              @click="cerrar"
            >
              Cancelar
            </button>
            <button
              type="submit"
              class="formulario-modal__btn formulario-modal__btn--primary"
              :disabled="formSaving"
            >
              {{ formSaving ? 'Guardando…' : (formularioParaEditar ? 'Guardar' : 'Crear') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.formulario-modal__overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.75);
  display: flex;
  align-items: flex-end;
  justify-content: center;
  z-index: 2000;
  animation: formulario-modal-fade 0.2s ease;
}

@keyframes formulario-modal-fade {
  from { opacity: 0; }
  to { opacity: 1; }
}

.formulario-modal {
  background: #161616;
  border-radius: 20px 20px 0 0;
  width: 100%;
  max-width: 540px;
  max-height: 95vh;
  display: flex;
  flex-direction: column;
  animation: formulario-modal-slide 0.3s ease;
}

@keyframes formulario-modal-slide {
  from { transform: translateY(100%); }
  to { transform: translateY(0); }
}

@media (min-width: 640px) {
  .formulario-modal__overlay {
    align-items: center;
  }
  .formulario-modal {
    border-radius: 20px;
    max-height: 92vh;
  }
}

.formulario-modal__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.25rem 1.25rem 0;
  flex-shrink: 0;
}

.formulario-modal__title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #fff;
  margin: 0;
  flex: 1;
  min-width: 0;
}

.formulario-modal__close {
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

.formulario-modal__close:hover {
  background: #333;
  color: #fff;
}

.formulario-modal__close svg {
  width: 18px;
  height: 18px;
}

.formulario-modal__form {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-height: 0;
}

.formulario-modal__body {
  padding: 1rem 1.25rem;
  overflow-y: auto;
  flex: 1;
  min-height: 0;
}

.formulario-modal__error {
  font-size: 0.875rem;
  color: #EF5C5C;
  margin-bottom: 0.75rem;
  padding: 0.75rem;
  background: rgba(239, 92, 92, 0.12);
  border: 1px solid rgba(239, 92, 92, 0.3);
  border-radius: 12px;
}

.formulario-modal__field {
  margin-bottom: 1rem;
}

.formulario-modal__field:last-child {
  margin-bottom: 0;
}

.formulario-modal__label {
  display: block;
  font-size: 0.8125rem;
  font-weight: 500;
  color: #a0a0a0;
  margin-bottom: 0.5rem;
}

.formulario-modal__input,
.formulario-modal__select {
  width: 100%;
  background: #1e1e1e;
  border: 1px solid #252525;
  border-radius: 12px;
  padding: 0.625rem 0.875rem;
  font-size: 0.875rem;
  color: #fff;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.formulario-modal__input::placeholder {
  color: #697586;
}

.formulario-modal__input:focus,
.formulario-modal__select:focus {
  outline: none;
  border-color: #00D261;
  box-shadow: 0 0 0 2px rgba(0, 210, 97, 0.35);
}

.formulario-modal__select {
  accent-color: #00D261;
}

.formulario-modal__select option {
  background: #1e1e1e;
  color: #fff;
}

.formulario-modal__empty-preguntas,
.formulario-modal__empty-opciones {
  padding: 1rem;
  background: #1e1e1e;
  border-radius: 12px;
  text-align: center;
  color: #697586;
  font-size: 0.8125rem;
  margin-bottom: 0.75rem;
}

.formulario-modal__preguntas {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}

.formulario-modal__pregunta {
  background: #1e1e1e;
  border-radius: 12px;
  padding: 1rem;
  border: 1px solid #252525;
}

.formulario-modal__pregunta-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}

.formulario-modal__pregunta-numero {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  background: #252525;
  border-radius: 50%;
  font-size: 0.75rem;
  font-weight: 600;
  color: #00D261;
  flex-shrink: 0;
}

.formulario-modal__pregunta-actions {
  display: flex;
  gap: 0.25rem;
  margin-left: auto;
}

.formulario-modal__pregunta-action {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  background: #252525;
  border: none;
  border-radius: 8px;
  color: #a0a0a0;
  cursor: pointer;
  transition: background 0.2s, color 0.2s;
}

.formulario-modal__pregunta-action:hover:not(:disabled) {
  background: #2a2a2a;
  color: #fff;
}

.formulario-modal__pregunta-action:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.formulario-modal__pregunta-action--delete {
  color: #EF5C5C;
}

.formulario-modal__pregunta-action--delete:hover:not(:disabled) {
  background: rgba(239, 92, 92, 0.15);
  color: #EF5C5C;
}

.formulario-modal__pregunta-action svg {
  width: 16px;
  height: 16px;
}

.formulario-modal__pregunta-body {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.formulario-modal__opciones-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}

.formulario-modal__btn-agregar-opcion {
  padding: 0.375rem 0.625rem;
  font-size: 0.75rem;
  font-weight: 500;
  color: #00D261;
  background: rgba(0, 210, 97, 0.15);
  border: 1px solid rgba(0, 210, 97, 0.4);
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.2s, border-color 0.2s;
}

.formulario-modal__btn-agregar-opcion:hover {
  background: rgba(0, 210, 97, 0.25);
  border-color: #00D261;
}

.formulario-modal__opciones {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.formulario-modal__opcion {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.formulario-modal__opcion .formulario-modal__input {
  flex: 1;
  margin-bottom: 0;
}

.formulario-modal__opcion-delete {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  background: rgba(239, 92, 92, 0.15);
  border: 1px solid rgba(239, 92, 92, 0.3);
  border-radius: 8px;
  color: #EF5C5C;
  cursor: pointer;
  transition: background 0.2s, border-color 0.2s;
  flex-shrink: 0;
}

.formulario-modal__opcion-delete:hover {
  background: rgba(239, 92, 92, 0.25);
  border-color: #EF5C5C;
}

.formulario-modal__opcion-delete svg {
  width: 18px;
  height: 18px;
}

.formulario-modal__btn-agregar {
  width: 100%;
  padding: 0.75rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #00D261;
  background: rgba(0, 210, 97, 0.15);
  border: 1px solid rgba(0, 210, 97, 0.4);
  border-radius: 12px;
  cursor: pointer;
  transition: background 0.2s, border-color 0.2s;
  margin-bottom: 0.75rem;
}

.formulario-modal__btn-agregar:hover {
  background: rgba(0, 210, 97, 0.25);
  border-color: #00D261;
}

.formulario-modal__label-checkbox {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: #a0a0a0;
  cursor: pointer;
}

.formulario-modal__checkbox {
  width: 20px;
  height: 20px;
  accent-color: #00D261;
  cursor: pointer;
}

.formulario-modal__footer {
  display: flex;
  gap: 0.5rem;
  justify-content: flex-end;
  padding: 1rem 1.25rem;
  border-top: 1px solid #252525;
  flex-shrink: 0;
}

.formulario-modal__btn {
  padding: 0.625rem 1.25rem;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: 12px;
  cursor: pointer;
  transition: opacity 0.2s;
  border: none;
}

.formulario-modal__btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.formulario-modal__btn--secondary {
  background: #252525;
  color: #a0a0a0;
}

.formulario-modal__btn--secondary:hover:not(:disabled) {
  background: #2a2a2a;
  color: #fff;
}

.formulario-modal__btn--primary {
  background: #00D261;
  color: #0a0a0a;
}

.formulario-modal__btn--primary:hover:not(:disabled) {
  opacity: 0.9;
}
</style>

