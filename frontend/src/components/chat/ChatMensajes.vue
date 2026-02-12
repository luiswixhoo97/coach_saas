<script setup>
/**
 * ChatMensajes - Área de mensajes con scroll automático
 * Maneja la carga de mensajes y el scroll al final
 */

import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'
import ChatMensaje from './ChatMensaje.vue'
import { useAuthStore } from '@/stores/auth'
import { useChat } from '@/composables/useChat'
import { useChatPolling } from '@/composables/useChatPolling'

// Usar la instancia compartida del composable
const { mensajes, obtenerMensajes, cargarMasMensajes, obtenerUrlArchivo, cargandoMensajes } = useChat()

const props = defineProps({
  chatId: {
    type: Number,
    default: null
  }
})

const emit = defineEmits(['archivo-descargar'])

const authStore = useAuthStore()
const { iniciar, detener } = useChatPolling(props.chatId)

const mensajesContainer = ref(null)
const cargandoMas = ref(false)
const estaEnTop = ref(false)
const observador = ref(null)

const esCoach = computed(() => authStore.esCoach)
const esCliente = computed(() => authStore.esCliente)

// Determinar si un mensaje es propio
function esMensajePropio(mensaje) {
  if (esCoach.value) {
    return mensaje.emisor_tipo === 'coach'
  }
  if (esCliente.value) {
    return mensaje.emisor_tipo === 'cliente'
  }
  return false
}

// Scroll al final
async function scrollAlFinal(suave = false) {
  await nextTick()
  if (mensajesContainer.value) {
    mensajesContainer.value.scrollTo({
      top: mensajesContainer.value.scrollHeight,
      behavior: suave ? 'smooth' : 'auto'
    })
  }
}

// Detectar cuando el usuario está en el top (para cargar más mensajes)
function configurarObservador() {
  if (!mensajesContainer.value) return

  observador.value = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        estaEnTop.value = entry.isIntersecting
        if (entry.isIntersecting) {
          cargarMasMensajesHandler()
        }
      })
    },
    { threshold: 0.1 }
  )

  // Observar el primer mensaje
  const primerMensaje = mensajesContainer.value.querySelector('.chat-mensaje')
  if (primerMensaje) {
    observador.value.observe(primerMensaje)
  }
}

// Cargar más mensajes cuando se está en el top
async function cargarMasMensajesHandler() {
  if (cargandoMas.value || cargandoMensajes.value) return

  cargandoMas.value = true
  try {
    const alturaAnterior = mensajesContainer.value?.scrollHeight || 0
    const siguientePagina = mensajes.value.length > 0 ? Math.floor(mensajes.value.length / 50) + 2 : 2
    await obtenerMensajes(props.chatId, siguientePagina)
    
    await nextTick()
    
    // Mantener posición de scroll después de cargar mensajes antiguos
    if (mensajesContainer.value && alturaAnterior > 0) {
      const nuevaAltura = mensajesContainer.value.scrollHeight
      mensajesContainer.value.scrollTop = nuevaAltura - alturaAnterior
    }
  } catch (err) {
    console.error('Error al cargar más mensajes:', err)
  } finally {
    cargandoMas.value = false
  }
}

// Manejar descarga de archivos
function handleDescargarArchivo(archivo) {
  const url = obtenerUrlArchivo(props.chatId, archivo.id)
  if (url) {
    window.open(url, '_blank')
  }
  emit('archivo-descargar', archivo)
}

// Watch para nuevos mensajes (scroll automático)
watch(
  () => mensajes.value.length,
  (nuevo, anterior) => {
    if (nuevo > anterior) {
      // Hay mensajes nuevos, hacer scroll al final
      const ultimoMensaje = mensajes.value[mensajes.value.length - 1]
      const esPropio = esMensajePropio(ultimoMensaje)
      
      // Solo hacer scroll si el mensaje es propio o si ya está cerca del final
      if (esPropio) {
        scrollAlFinal(true)
      } else {
        // Verificar si está cerca del final antes de hacer scroll
        if (mensajesContainer.value) {
          const { scrollTop, scrollHeight, clientHeight } = mensajesContainer.value
          const estaCercaDelFinal = scrollHeight - scrollTop - clientHeight < 200
          if (estaCercaDelFinal) {
            scrollAlFinal(true)
          }
        }
      }
    }
  }
)

// Cargar mensajes iniciales
onMounted(async () => {
  try {
    await obtenerMensajes(props.chatId, 1)
    await nextTick()
    scrollAlFinal()
    configurarObservador()
    
    // Iniciar polling
    iniciar()
  } catch (err) {
    console.error('Error al cargar mensajes:', err)
  }
})

// Limpiar al desmontar
onUnmounted(() => {
  detener()
  if (observador.value) {
    observador.value.disconnect()
  }
})
</script>

<template>
  <div class="chat-mensajes">
    <!-- Loading inicial -->
    <div v-if="cargandoMensajes && mensajes.length === 0" class="chat-mensajes__loading">
      <div class="chat-mensajes__spinner"></div>
    </div>

    <!-- Lista de mensajes -->
    <div
      v-else
      ref="mensajesContainer"
      class="chat-mensajes__container"
    >
      <!-- Indicador de carga más mensajes -->
      <div v-if="cargandoMas" class="chat-mensajes__cargando-mas">
        <div class="chat-mensajes__spinner chat-mensajes__spinner--pequeño"></div>
      </div>

      <!-- Mensajes -->
      <ChatMensaje
        v-for="mensaje in mensajes"
        :key="`mensaje-${mensaje.id}`"
        :mensaje="mensaje"
        :es-propio="esMensajePropio(mensaje)"
        @archivo-descargar="handleDescargarArchivo"
      />
      
      <!-- Debug: mostrar cantidad de mensajes -->
      <div v-if="false" style="color: red; padding: 1rem; font-size: 0.75rem;">
        Debug: {{ mensajes.length }} mensajes en el estado
      </div>

      <!-- Estado vacío -->
      <div v-if="!cargandoMensajes && mensajes.length === 0" class="chat-mensajes__vacio">
        <p class="chat-mensajes__vacio-texto">No hay mensajes aún</p>
        <p class="chat-mensajes__vacio-hint">Envía el primer mensaje para comenzar la conversación</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.chat-mensajes {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  background: #0a0a0a;
  min-height: 0;
  /* Ocupa todo el espacio disponible entre header e input */
}

.chat-mensajes__loading {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 0;
}

.chat-mensajes__container {
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  min-height: 0;
  /* Solo este contenedor tiene scroll, como WhatsApp */
  /* Scrollbar personalizado */
  scrollbar-width: thin;
  scrollbar-color: #252525 #0a0a0a;
}

.chat-mensajes__container::-webkit-scrollbar {
  width: 8px;
}

.chat-mensajes__container::-webkit-scrollbar-track {
  background: #0a0a0a;
}

.chat-mensajes__container::-webkit-scrollbar-thumb {
  background: #252525;
  border-radius: 4px;
}

.chat-mensajes__container::-webkit-scrollbar-thumb:hover {
  background: #353535;
}

.chat-mensajes__cargando-mas {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0.75rem;
}

.chat-mensajes__spinner {
  width: 32px;
  height: 32px;
  border: 3px solid rgba(0, 210, 97, 0.2);
  border-top-color: #00D261;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.chat-mensajes__spinner--pequeño {
  width: 20px;
  height: 20px;
  border-width: 2px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.chat-mensajes__vacio {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  text-align: center;
}

.chat-mensajes__vacio-texto {
  font-size: 1rem;
  font-weight: 600;
  color: #fff;
  margin-bottom: 0.5rem;
}

.chat-mensajes__vacio-hint {
  font-size: 0.875rem;
  color: #697586;
}
</style>

