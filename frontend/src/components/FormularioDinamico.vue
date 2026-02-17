<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  preguntas: {
    type: Array,
    required: true,
    default: () => []
  },
  modelValue: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['update:modelValue'])

const respuestas = ref({})

// Inicializar respuestas vacías
function inicializarRespuestas() {
  const nuevasRespuestas = {}
  props.preguntas.forEach((pregunta, index) => {
    if (pregunta.tipo === 'multiple') {
      nuevasRespuestas[index] = []
    } else {
      nuevasRespuestas[index] = ''
    }
  })
  respuestas.value = nuevasRespuestas
  emit('update:modelValue', nuevasRespuestas)
}

// Sincronizar con modelValue externo
watch(() => props.modelValue, (val) => {
  if (val && Object.keys(val).length > 0) {
    respuestas.value = { ...val }
  }
}, { immediate: true })

watch(() => props.preguntas, () => {
  inicializarRespuestas()
}, { immediate: true })

// Actualizar respuestas
function actualizarRespuesta(index, valor) {
  respuestas.value[index] = valor
  emit('update:modelValue', { ...respuestas.value })
}

function actualizarRespuestaMultiple(index, opcion, checked) {
  if (!Array.isArray(respuestas.value[index])) {
    respuestas.value[index] = []
  }
  
  if (checked) {
    if (!respuestas.value[index].includes(opcion)) {
      respuestas.value[index].push(opcion)
    }
  } else {
    respuestas.value[index] = respuestas.value[index].filter(o => o !== opcion)
  }
  
  emit('update:modelValue', { ...respuestas.value })
}
</script>

<template>
  <div class="formulario-dinamico space-y-6">
    <div
      v-for="(pregunta, index) in preguntas"
      :key="index"
      class="formulario-dinamico__pregunta"
    >
      <label class="formulario-dinamico__label">
        {{ pregunta.texto }}
        <span class="text-danger-500">*</span>
      </label>

      <!-- Tipo: Texto -->
      <input
        v-if="pregunta.tipo === 'texto'"
        :value="respuestas[index]"
        @input="actualizarRespuesta(index, $event.target.value)"
        type="text"
        class="formulario-dinamico__input"
        :placeholder="`Respuesta para: ${pregunta.texto}`"
        required
      />

      <!-- Tipo: Número -->
      <input
        v-else-if="pregunta.tipo === 'numero'"
        :value="respuestas[index]"
        @input="actualizarRespuesta(index, $event.target.value)"
        type="number"
        class="formulario-dinamico__input"
        :placeholder="`Respuesta numérica`"
        required
      />

      <!-- Tipo: Selección única -->
      <select
        v-else-if="pregunta.tipo === 'seleccion'"
        :value="respuestas[index]"
        @change="actualizarRespuesta(index, $event.target.value)"
        class="formulario-dinamico__select"
        required
      >
        <option value="">Selecciona una opción</option>
        <option
          v-for="(opcion, opcionIndex) in pregunta.opciones"
          :key="opcionIndex"
          :value="opcion"
        >
          {{ opcion }}
        </option>
      </select>

      <!-- Tipo: Selección múltiple -->
      <div v-else-if="pregunta.tipo === 'multiple'" class="formulario-dinamico__opciones">
        <label
          v-for="(opcion, opcionIndex) in pregunta.opciones"
          :key="opcionIndex"
          class="formulario-dinamico__opcion-label"
        >
          <input
            type="checkbox"
            :checked="Array.isArray(respuestas[index]) && respuestas[index].includes(opcion)"
            @change="actualizarRespuestaMultiple(index, opcion, $event.target.checked)"
            class="formulario-dinamico__checkbox"
          />
          <span>{{ opcion }}</span>
        </label>
      </div>
    </div>
  </div>
</template>

<style scoped>
.formulario-dinamico__pregunta {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.formulario-dinamico__label {
  display: block;
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
}

.formulario-dinamico__input,
.formulario-dinamico__select {
  display: block;
  width: 100%;
  border-radius: 0.5rem;
  border: 1px solid #d1d5db;
  background-color: #ffffff;
  padding: 0.75rem 1rem;
  color: #111827;
  font-size: 0.875rem;
}

.formulario-dinamico__input::placeholder {
  color: #9ca3af;
}

.formulario-dinamico__input:focus,
.formulario-dinamico__select:focus {
  outline: none;
  ring: 2px;
  ring-color: #00D261;
  border-color: #00D261;
}

.formulario-dinamico__opciones {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.formulario-dinamico__opcion-label {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  border-radius: 0.5rem;
  border: 1px solid #e5e7eb;
  cursor: pointer;
  transition: background-color 0.2s;
}

.formulario-dinamico__opcion-label:hover {
  background-color: #f9fafb;
}

.formulario-dinamico__checkbox {
  width: 1rem;
  height: 1rem;
  color: #00D261;
  border-color: #d1d5db;
  border-radius: 0.25rem;
}

.formulario-dinamico__checkbox:focus {
  ring: 2px;
  ring-color: #00D261;
}
</style>

