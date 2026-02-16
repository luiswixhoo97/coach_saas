<script setup>
/**
 * ChatInput - Input para escribir mensajes con emojis y archivos
 */

import { ref, computed, watch } from 'vue'
import { Picker } from 'emoji-mart-vue-fast'

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  cargando: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue', 'enviar', 'archivos'])

const texto = ref(props.modelValue)
const mostrarEmojiPicker = ref(false)
const archivosSeleccionados = ref([])
const inputRef = ref(null)

watch(() => props.modelValue, (nuevo) => {
  texto.value = nuevo
})

function actualizarTexto(valor) {
  texto.value = valor
  emit('update:modelValue', valor)
}

function insertarEmoji(emoji) {
  const nuevoTexto = texto.value + emoji.native
  actualizarTexto(nuevoTexto)
  mostrarEmojiPicker.value = false
  inputRef.value?.focus()
}

function toggleEmojiPicker() {
  mostrarEmojiPicker.value = !mostrarEmojiPicker.value
}

function seleccionarArchivos(event) {
  const archivos = Array.from(event.target.files || [])
  if (archivos.length === 0) return

  // Validar cantidad (máximo 5)
  if (archivosSeleccionados.value.length + archivos.length > 5) {
    alert('No puedes seleccionar más de 5 archivos')
    return
  }

  // Validar tamaño (máximo 10MB cada uno)
  const archivosInvalidos = archivos.filter(archivo => archivo.size > 10 * 1024 * 1024)
  if (archivosInvalidos.length > 0) {
    alert('Algunos archivos superan los 10MB')
    return
  }

  archivosSeleccionados.value = [...archivosSeleccionados.value, ...archivos]
  emit('archivos', archivosSeleccionados.value)
  
  // Limpiar input file
  event.target.value = ''
}

function eliminarArchivo(index) {
  archivosSeleccionados.value.splice(index, 1)
  emit('archivos', archivosSeleccionados.value)
}

function enviarMensaje() {
  if (estaDeshabilitado.value) return
  
  const tieneContenido = texto.value.trim().length > 0
  const tieneArchivos = archivosSeleccionados.value.length > 0

  if (!tieneContenido && !tieneArchivos) return

  emit('enviar', {
    mensaje: texto.value.trim(),
    archivos: archivosSeleccionados.value
  })

  // Limpiar después de enviar
  texto.value = ''
  actualizarTexto('')
  archivosSeleccionados.value = []
  emit('archivos', [])
  mostrarEmojiPicker.value = false
}

function handleKeydown(event) {
  if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault()
    enviarMensaje()
  }
}

const estaDeshabilitado = computed(() => props.disabled || props.cargando)
</script>

<template>
  <div class="chat-input">
    <!-- Archivos seleccionados -->
    <div v-if="archivosSeleccionados.length > 0" class="chat-input__archivos">
      <div
        v-for="(archivo, index) in archivosSeleccionados"
        :key="index"
        class="chat-input__archivo"
      >
        <span class="chat-input__archivo-nombre">
          {{ archivo.name }}
        </span>
        <button
          @click="eliminarArchivo(index)"
          class="chat-input__archivo-eliminar"
          type="button"
        >
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <path
              d="M12 4L4 12M4 4L12 12"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
            />
          </svg>
        </button>
      </div>
    </div>

    <!-- Input y botones -->
    <div class="chat-input__container">
      <!-- Botón de archivos -->
      <label class="chat-input__boton-archivo">
        <input
          type="file"
          multiple
          accept="image/*,.pdf,.doc,.docx"
          @change="seleccionarArchivos"
          class="chat-input__file-input"
        />
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
          <path
            d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15M7 10L12 15M12 15L17 10M12 15V3"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </svg>
      </label>

      <!-- Textarea -->
      <div class="chat-input__textarea-wrapper">
        <textarea
          ref="inputRef"
          :value="texto"
          :disabled="estaDeshabilitado"
          :placeholder="archivosSeleccionados.length > 0 ? 'Escribe un mensaje (opcional)...' : 'Escribe un mensaje...'"
          class="chat-input__textarea"
          rows="1"
          @input="actualizarTexto($event.target.value)"
          @keydown="handleKeydown"
        />
      </div>

      <!-- Botón de emoji -->
      <button
        @click="toggleEmojiPicker"
        type="button"
        class="chat-input__boton-emoji"
        :class="{ 'chat-input__boton-emoji--activo': mostrarEmojiPicker }"
      >
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
          <path
            d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
          <path
            d="M8 14C8 14 9.5 16 12 16C14.5 16 16 14 16 14"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
          <path
            d="M9 9H9.01M15 9H15.01"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </svg>
      </button>

      <!-- Botón de enviar -->
      <button
        @click="enviarMensaje"
        :disabled="estaDeshabilitado || (!texto.trim() && archivosSeleccionados.length === 0)"
        type="button"
        class="chat-input__boton-enviar"
      >
        <svg
          v-if="!cargando"
          width="24"
          height="24"
          viewBox="0 0 24 24"
          fill="none"
        >
          <path
            d="M22 2L11 13M22 2L15 22L11 13M22 2L2 9L11 13"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </svg>
        <svg
          v-else
          class="animate-spin"
          width="24"
          height="24"
          viewBox="0 0 24 24"
          fill="none"
        >
          <circle
            class="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"
          />
          <path
            class="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
          />
        </svg>
      </button>
    </div>

    <!-- Emoji Picker -->
    <div v-if="mostrarEmojiPicker" class="chat-input__emoji-picker">
      <Picker
        :native="true"
        :show-search="false"
        :show-preview="false"
        :show-skin-tones="false"
        @select="insertarEmoji"
      />
    </div>
</div>
</template>

<style scoped>
.chat-input {
  /* Fijo en la parte inferior, sin scroll - como WhatsApp Desktop */
  flex-shrink: 0;
  width: 100%;
  background: #111111;
  border-top: 1px solid #1e1e1e;
  padding: 0.625rem 1rem;
  padding-bottom: max(0.625rem, env(safe-area-inset-bottom));
  z-index: 100;
  /* Asegurar que no tenga scroll */
  overflow: visible;
  /* El input está al final del contenedor flex, por lo que queda fijo naturalmente */
}

/* En móvil el ChatInput queda al final del flex container
   ya que el bottom-nav se oculta cuando estamos dentro de un chat */

.chat-input__archivos {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.chat-input__archivo {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  background: #1e1e1e;
  border: 1px solid #252525;
  border-radius: 12px;
  font-size: 0.75rem;
}

.chat-input__archivo-nombre {
  color: #fff;
  max-width: 150px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.chat-input__archivo-eliminar {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  color: #697586;
  cursor: pointer;
  transition: color 0.2s ease;
  flex-shrink: 0;
}

.chat-input__archivo-eliminar:hover {
  color: #EF5C5C;
}

.chat-input__container {
  display: flex;
  align-items: flex-end;
  gap: 0.5rem;
}

.chat-input__boton-archivo,
.chat-input__boton-emoji,
.chat-input__boton-enviar {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border: none;
  background: transparent;
  color: #697586;
  cursor: pointer;
  transition: all 0.2s ease;
  flex-shrink: 0;
  border-radius: 12px;
}

.chat-input__boton-archivo:hover,
.chat-input__boton-emoji:hover {
  background: #1e1e1e;
  color: #00D261;
}

.chat-input__boton-emoji--activo {
  background: #1e1e1e;
  color: #00D261;
}

.chat-input__boton-enviar {
  background: #00D261;
  color: #fff;
}

.chat-input__boton-enviar:hover:not(:disabled) {
  background: #00b355;
}

.chat-input__boton-enviar:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.chat-input__file-input {
  display: none;
}

.chat-input__textarea-wrapper {
  flex: 1;
  position: relative;
  border-radius: 12px;
  outline: none !important;
}

.chat-input__textarea-wrapper:focus-within {
  outline: none !important;
}

.chat-input__textarea {
  width: 100%;
  min-height: 40px;
  max-height: 120px;
  padding: 0.75rem 1rem;
  background: #1e1e1e;
  border: 1px solid #252525;
  border-radius: 12px;
  color: #fff;
  font-size: 0.875rem;
  font-family: inherit;
  resize: none;
  overflow-y: auto;
  transition: all 0.2s ease;
  outline: none !important;
  box-shadow: none;
}

.chat-input__textarea:focus {
  outline: none !important;
  border-color: #00D261;
  background: #252525;
  box-shadow: none;
}

.chat-input__textarea:focus-visible {
  outline: none !important;
  box-shadow: none;
}

.chat-input__textarea:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.chat-input__textarea::placeholder {
  color: #697586;
}

.chat-input__emoji-picker {
  position: absolute;
  bottom: 100%;
  left: 0;
  right: 0;
  margin-bottom: 0.5rem;
  z-index: 10;
}
</style>

