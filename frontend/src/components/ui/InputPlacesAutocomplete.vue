<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'
import { usePlacesAutocomplete } from '@/composables/usePlacesAutocomplete'

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: 'Buscar ubicación...'
  },
  disabled: {
    type: Boolean,
    default: false
  },
  ciudad: {
    type: String,
    default: null
  }
})

const emit = defineEmits(['update:modelValue', 'select'])

const { sugerencias, cargando, error: errorBusqueda, buscarLugares, limpiarSugerencias } = usePlacesAutocomplete()

const inputRef = ref(null)
const inputValue = ref(props.modelValue)
const mostrarSugerencias = ref(false)
const indiceSeleccionado = ref(-1)
const timeoutRef = ref(null)
const seleccionandoLugar = ref(false) // Flag para saber si estamos seleccionando un lugar

watch(() => props.modelValue, (newVal) => {
  if (newVal !== inputValue.value) {
    inputValue.value = newVal
  }
})

watch(inputValue, (newVal) => {
  // Solo emitir update:modelValue si NO estamos seleccionando un lugar
  // Cuando se selecciona un lugar, el padre maneja el valor a través del evento 'select'
  if (!seleccionandoLugar.value) {
    emit('update:modelValue', newVal)
  }
  // Ya no buscamos automáticamente mientras escribe
  // Solo se busca con Enter o botón de búsqueda
})

watch(sugerencias, (nuevas) => {
  if (nuevas.length > 0 && inputValue.value.trim().length >= 3) {
    mostrarSugerencias.value = true
  } else if (nuevas.length === 0) {
    // No cerrar si aún está cargando
    if (!cargando.value) {
      mostrarSugerencias.value = false
    }
  }
})

function seleccionarLugar(lugar) {
  // SOLO guardar el link de Google Maps, no la dirección
  if (!lugar.link_google_maps) {
    console.warn('El lugar seleccionado no tiene link de Google Maps')
    return
  }
  
  // Marcar que estamos seleccionando para evitar emitir update:modelValue
  seleccionandoLugar.value = true
  
  // Mostrar nombre o dirección en el input para que el usuario vea qué seleccionó
  inputValue.value = lugar.nombre || lugar.direccion || lugar.direccion_completa
  mostrarSugerencias.value = false
  limpiarSugerencias()
  
  // Emitir el lugar completo con el link (para que el padre pueda usarlo)
  emit('select', lugar)
  
  // Resetear el flag después de un pequeño delay
  setTimeout(() => {
    seleccionandoLugar.value = false
  }, 100)
}

function cerrarSugerencias() {
  // Cerrar después de un pequeño delay para permitir clicks
  setTimeout(() => {
    if (!inputRef.value?.contains(document.activeElement)) {
      mostrarSugerencias.value = false
    }
  }, 300)
}

function manejarTeclado(event) {
  switch (event.key) {
    case 'Enter':
      event.preventDefault()
      // Si hay sugerencias y una seleccionada, seleccionarla
      if (mostrarSugerencias.value && sugerencias.value.length > 0) {
        if (indiceSeleccionado.value >= 0 && indiceSeleccionado.value < sugerencias.value.length) {
          seleccionarLugar(sugerencias.value[indiceSeleccionado.value])
        } else if (sugerencias.value.length === 1) {
          // Si solo hay un resultado, seleccionarlo automáticamente
          seleccionarLugar(sugerencias.value[0])
        }
      } else {
        // Si no hay sugerencias, buscar con el texto actual
        realizarBusqueda()
      }
      break
    case 'ArrowDown':
      if (mostrarSugerencias.value && sugerencias.value.length > 0) {
        event.preventDefault()
        indiceSeleccionado.value = Math.min(
          indiceSeleccionado.value + 1,
          sugerencias.value.length - 1
        )
      }
      break
    case 'ArrowUp':
      if (mostrarSugerencias.value && sugerencias.value.length > 0) {
        event.preventDefault()
        indiceSeleccionado.value = Math.max(indiceSeleccionado.value - 1, -1)
      }
      break
    case 'Escape':
      mostrarSugerencias.value = false
      break
  }
}

function realizarBusqueda() {
  const query = inputValue.value?.trim()
  if (!query || query.length < 3) {
    limpiarSugerencias()
    mostrarSugerencias.value = false
    return
  }

  buscarLugares(query, props.ciudad).then(() => {
    if (sugerencias.value.length > 0) {
      mostrarSugerencias.value = true
      indiceSeleccionado.value = -1
    } else {
      mostrarSugerencias.value = false
    }
  })
}

function clickFuera(event) {
  if (inputRef.value && !inputRef.value.contains(event.target)) {
    mostrarSugerencias.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', clickFuera)
})

onUnmounted(() => {
  document.removeEventListener('click', clickFuera)
  if (timeoutRef.value) {
    clearTimeout(timeoutRef.value)
  }
})
</script>

<template>
  <div class="input-places-autocomplete" ref="inputRef">
    <div class="input-places-autocomplete__wrapper">
      <input
        v-model="inputValue"
        type="text"
        :placeholder="placeholder"
        :disabled="disabled"
        class="input-places-autocomplete__input"
        autocomplete="off"
        @focus="() => { if (sugerencias.length > 0) mostrarSugerencias = true }"
        @keydown="manejarTeclado"
        @blur="cerrarSugerencias"
      />
      <button
        type="button"
        class="input-places-autocomplete__search-btn"
        :disabled="disabled || cargando || !inputValue?.trim() || inputValue.trim().length < 3"
        @click="realizarBusqueda"
        title="Buscar"
      >
        <svg v-if="!cargando" class="input-places-autocomplete__search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <circle cx="11" cy="11" r="8"/>
          <path d="m21 21-4.35-4.35"/>
        </svg>
        <svg v-else class="input-places-autocomplete__spinner" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" opacity="0.25"/>
          <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
        </svg>
      </button>
    </div>

    <!-- Mensaje de error -->
    <div
      v-if="errorBusqueda && inputValue.trim().length >= 3 && !cargando"
      class="input-places-autocomplete__error"
    >
      {{ errorBusqueda }}
    </div>
    
    <!-- Mensaje cuando no hay resultados -->
    <div
      v-if="!cargando && inputValue.trim().length >= 3 && sugerencias.length === 0 && mostrarSugerencias && !errorBusqueda"
      class="input-places-autocomplete__no-results"
    >
      No se encontraron lugares. Intenta con otra búsqueda.
    </div>

    <!-- Lista de sugerencias -->
    <div
      v-if="mostrarSugerencias && sugerencias.length > 0"
      class="input-places-autocomplete__sugerencias"
    >
      <button
        v-for="(lugar, index) in sugerencias"
        :key="lugar.id"
        type="button"
        class="input-places-autocomplete__sugerencia"
        :class="{ 'input-places-autocomplete__sugerencia--selected': index === indiceSeleccionado }"
        @click="seleccionarLugar(lugar)"
        @mousemove="indiceSeleccionado = index"
      >
        <div class="input-places-autocomplete__sugerencia-content">
          <div class="input-places-autocomplete__sugerencia-nombre">
            {{ lugar.nombre || lugar.direccion.split(',')[0] }}
          </div>
          <div class="input-places-autocomplete__sugerencia-direccion">
            {{ lugar.direccion }}
          </div>
        </div>
        <svg class="input-places-autocomplete__sugerencia-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path d="M5 12h14M12 5l7 7-7 7"/>
        </svg>
      </button>
    </div>
  </div>
</template>

<style scoped>
.input-places-autocomplete {
  position: relative;
  width: 100%;
}

.input-places-autocomplete__wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-places-autocomplete__input {
  width: 100%;
  padding: 0.75rem;
  padding-right: 2.5rem;
  border-radius: 12px;
  border: 1px solid #252525;
  background: #0a0a0a;
  color: #fff;
  font-size: 0.875rem;
  transition: all 0.2s ease;
}

.input-places-autocomplete__input:focus {
  outline: none;
  border-color: #00D261;
  box-shadow: 0 0 0 2px rgba(0, 210, 97, 0.35);
}

.input-places-autocomplete__input:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.input-places-autocomplete__search-btn {
  position: absolute;
  right: 0.5rem;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: none;
  cursor: pointer;
  color: #697586;
  transition: color 0.2s ease;
  border-radius: 8px;
  padding: 0;
}

.input-places-autocomplete__search-btn:hover:not(:disabled) {
  color: #00D261;
  background: rgba(0, 210, 97, 0.1);
}

.input-places-autocomplete__search-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.input-places-autocomplete__search-icon {
  width: 18px;
  height: 18px;
  stroke-width: 2;
}

.input-places-autocomplete__spinner {
  width: 18px;
  height: 18px;
  color: #00D261;
  animation: spin 1s linear infinite;
}

.input-places-autocomplete__spinner {
  width: 20px;
  height: 20px;
  color: #00D261;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.input-places-autocomplete__sugerencias {
  position: absolute;
  top: calc(100% + 0.5rem);
  left: 0;
  right: 0;
  background: #161616;
  border: 1px solid #252525;
  border-radius: 12px;
  max-height: 300px;
  overflow-y: auto;
  z-index: 3000;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
}

.input-places-autocomplete__sugerencias::-webkit-scrollbar {
  width: 6px;
}

.input-places-autocomplete__sugerencias::-webkit-scrollbar-track {
  background: #1a1a1a;
}

.input-places-autocomplete__sugerencias::-webkit-scrollbar-thumb {
  background: #333;
  border-radius: 3px;
}

.input-places-autocomplete__sugerencia {
  width: 100%;
  padding: 0.75rem 1rem;
  background: transparent;
  border: none;
  border-bottom: 1px solid #252525;
  color: #fff;
  text-align: left;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  transition: background 0.15s ease;
}

.input-places-autocomplete__sugerencia:last-child {
  border-bottom: none;
}

.input-places-autocomplete__sugerencia:hover,
.input-places-autocomplete__sugerencia--selected {
  background: #1f1f1f;
}

.input-places-autocomplete__sugerencia-content {
  flex: 1;
  min-width: 0;
}

.input-places-autocomplete__sugerencia-nombre {
  font-size: 0.875rem;
  font-weight: 500;
  color: #fff;
  margin-bottom: 0.25rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.input-places-autocomplete__sugerencia-direccion {
  font-size: 0.75rem;
  color: #9CA3AF;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.input-places-autocomplete__sugerencia-icon {
  width: 16px;
  height: 16px;
  color: #697586;
  flex-shrink: 0;
  opacity: 0;
  transition: opacity 0.15s ease;
}

.input-places-autocomplete__sugerencia:hover .input-places-autocomplete__sugerencia-icon,
.input-places-autocomplete__sugerencia--selected .input-places-autocomplete__sugerencia-icon {
  opacity: 1;
  color: #00D261;
}

.input-places-autocomplete__error,
.input-places-autocomplete__no-results {
  position: absolute;
  top: calc(100% + 0.5rem);
  left: 0;
  right: 0;
  padding: 0.5rem 0.75rem;
  border-radius: 8px;
  font-size: 0.75rem;
  z-index: 3001;
  margin-top: 0.25rem;
}

.input-places-autocomplete__error {
  background: rgba(239, 92, 92, 0.12);
  border: 1px solid rgba(239, 92, 92, 0.3);
  color: #EF5C5C;
}

.input-places-autocomplete__no-results {
  background: rgba(156, 163, 175, 0.12);
  border: 1px solid rgba(156, 163, 175, 0.3);
  color: #9CA3AF;
}
</style>

