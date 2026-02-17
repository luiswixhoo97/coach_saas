<script setup>
/**
 * ParametroCrearModal - Modal para crear o editar parámetros de evaluación.
 */
import { ref, watch } from 'vue'
import { useApi } from '@/composables/useApi'

const props = defineProps({
  parametroParaEditar: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'created'])

const { post, put } = useApi()

const formNombre = ref('')
const formUnidadMedida = ref('')
const formTipoDato = ref('numero')
const formError = ref('')
const formSaving = ref(false)

const TIPOS_DATO = [
  { value: 'numero', label: 'Número' },
  { value: 'texto', label: 'Texto' },
  { value: 'booleano', label: 'Booleano' }
]

/** Inicializar modo edición */
function initModoEdicion() {
  const p = props.parametroParaEditar
  if (!p) return
  formNombre.value = p.nombre ?? ''
  formUnidadMedida.value = p.unidad_medida ?? ''
  formTipoDato.value = p.tipo_dato ?? 'numero'
  formError.value = ''
}

watch(() => props.parametroParaEditar, (val) => {
  if (val) {
    initModoEdicion()
  } else {
    formNombre.value = ''
    formUnidadMedida.value = ''
    formTipoDato.value = 'numero'
    formError.value = ''
  }
}, { immediate: true })

/** Validar formulario */
function validar() {
  if (!formNombre.value.trim()) {
    formError.value = 'El nombre del parámetro es requerido.'
    return false
  }
  if (!formUnidadMedida.value.trim()) {
    formError.value = 'La unidad de medida es requerida.'
    return false
  }
  if (!formTipoDato.value) {
    formError.value = 'El tipo de dato es requerido.'
    return false
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
      unidad_medida: formUnidadMedida.value.trim(),
      tipo_dato: formTipoDato.value
    }

    const esEdicion = !!props.parametroParaEditar?.id
    if (esEdicion) {
      await put(`/coach/parametros/${props.parametroParaEditar.id}`, datos)
    } else {
      await post('/coach/parametros', datos)
    }
    emit('created')
    emit('close')
  } catch (e) {
    formError.value = e.errores
      ? Object.values(e.errores).flat().join(' ')
      : (e.message || (props.parametroParaEditar ? 'Error al actualizar el parámetro.' : 'Error al crear el parámetro.'))
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
    <div class="parametro-modal__overlay" @click.self="cerrar">
      <div class="parametro-modal">
        <div class="parametro-modal__header">
          <h2 class="parametro-modal__title">
            {{ parametroParaEditar ? 'Editar parámetro' : 'Crear parámetro' }}
          </h2>
          <button
            type="button"
            class="parametro-modal__close"
            aria-label="Cerrar"
            @click="cerrar"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 6L6 18M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <form @submit.prevent="enviarFormulario" class="parametro-modal__form">
          <div class="parametro-modal__body">
            <div v-if="formError" class="parametro-modal__error">{{ formError }}</div>

            <div class="parametro-modal__field">
              <label for="parametro-nombre" class="parametro-modal__label">Nombre</label>
              <input
                id="parametro-nombre"
                v-model="formNombre"
                type="text"
                class="parametro-modal__input"
                placeholder="Ej: Peso corporal"
                required
              />
            </div>

            <div class="parametro-modal__field">
              <label for="parametro-unidad" class="parametro-modal__label">Unidad de medida</label>
              <input
                id="parametro-unidad"
                v-model="formUnidadMedida"
                type="text"
                class="parametro-modal__input"
                placeholder="Ej: kg, cm, %"
                required
              />
            </div>

            <div class="parametro-modal__field">
              <label for="parametro-tipo" class="parametro-modal__label">Tipo de dato</label>
              <select
                id="parametro-tipo"
                v-model="formTipoDato"
                class="parametro-modal__select"
                required
              >
                <option v-for="tipo in TIPOS_DATO" :key="tipo.value" :value="tipo.value">
                  {{ tipo.label }}
                </option>
              </select>
            </div>
          </div>

          <div class="parametro-modal__footer">
            <button
              type="button"
              class="parametro-modal__btn parametro-modal__btn--secondary"
              @click="cerrar"
            >
              Cancelar
            </button>
            <button
              type="submit"
              class="parametro-modal__btn parametro-modal__btn--primary"
              :disabled="formSaving"
            >
              {{ formSaving ? 'Guardando…' : (parametroParaEditar ? 'Guardar' : 'Crear') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.parametro-modal__overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.75);
  display: flex;
  align-items: flex-end;
  justify-content: center;
  z-index: 2000;
  animation: parametro-modal-fade 0.2s ease;
}

@keyframes parametro-modal-fade {
  from { opacity: 0; }
  to { opacity: 1; }
}

.parametro-modal {
  background: #161616;
  border-radius: 20px 20px 0 0;
  width: 100%;
  max-width: 480px;
  max-height: 95vh;
  display: flex;
  flex-direction: column;
  animation: parametro-modal-slide 0.3s ease;
}

@keyframes parametro-modal-slide {
  from { transform: translateY(100%); }
  to { transform: translateY(0); }
}

@media (min-width: 640px) {
  .parametro-modal__overlay {
    align-items: center;
  }
  .parametro-modal {
    border-radius: 20px;
    max-height: 92vh;
  }
}

.parametro-modal__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.25rem 1.25rem 0;
  flex-shrink: 0;
}

.parametro-modal__title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #fff;
  margin: 0;
  flex: 1;
  min-width: 0;
}

.parametro-modal__close {
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

.parametro-modal__close:hover {
  background: #333;
  color: #fff;
}

.parametro-modal__close svg {
  width: 18px;
  height: 18px;
}

.parametro-modal__form {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-height: 0;
}

.parametro-modal__body {
  padding: 1rem 1.25rem;
  overflow-y: auto;
  flex: 1;
  min-height: 0;
}

.parametro-modal__error {
  font-size: 0.875rem;
  color: #EF5C5C;
  margin-bottom: 0.75rem;
  padding: 0.75rem;
  background: rgba(239, 92, 92, 0.12);
  border: 1px solid rgba(239, 92, 92, 0.3);
  border-radius: 12px;
}

.parametro-modal__field {
  margin-bottom: 1rem;
}

.parametro-modal__field:last-child {
  margin-bottom: 0;
}

.parametro-modal__label {
  display: block;
  font-size: 0.8125rem;
  font-weight: 500;
  color: #a0a0a0;
  margin-bottom: 0.5rem;
}

.parametro-modal__input,
.parametro-modal__select {
  width: 100%;
  background: #1e1e1e;
  border: 1px solid #252525;
  border-radius: 12px;
  padding: 0.625rem 0.875rem;
  font-size: 0.875rem;
  color: #fff;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.parametro-modal__input::placeholder {
  color: #697586;
}

.parametro-modal__input:focus,
.parametro-modal__select:focus {
  outline: none;
  border-color: #00D261;
  box-shadow: 0 0 0 2px rgba(0, 210, 97, 0.35);
}

.parametro-modal__select {
  accent-color: #00D261;
}

.parametro-modal__select option {
  background: #1e1e1e;
  color: #fff;
}

.parametro-modal__footer {
  display: flex;
  gap: 0.5rem;
  justify-content: flex-end;
  padding: 1rem 1.25rem;
  border-top: 1px solid #252525;
  flex-shrink: 0;
}

.parametro-modal__btn {
  padding: 0.625rem 1.25rem;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: 12px;
  cursor: pointer;
  transition: opacity 0.2s;
  border: none;
}

.parametro-modal__btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.parametro-modal__btn--secondary {
  background: #252525;
  color: #a0a0a0;
}

.parametro-modal__btn--secondary:hover:not(:disabled) {
  background: #2a2a2a;
  color: #fff;
}

.parametro-modal__btn--primary {
  background: #00D261;
  color: #0a0a0a;
}

.parametro-modal__btn--primary:hover:not(:disabled) {
  opacity: 0.9;
}
</style>

