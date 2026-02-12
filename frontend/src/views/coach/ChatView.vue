<script setup>
/**
 * ChatView - Vista de chat para coach
 * Muestra lista de chats y área de mensajes
 */

import { ref, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import ChatLista from '@/components/chat/ChatLista.vue'
import ChatMensajes from '@/components/chat/ChatMensajes.vue'
import ChatInput from '@/components/chat/ChatInput.vue'
import { useChat } from '@/composables/useChat'
import BaseAvatar from '@/components/ui/BaseAvatar.vue'

const route = useRoute()
const {
  chat,
  obtenerChat,
  enviarMensaje,
  marcarComoLeido,
  obtenerUrlArchivo,
  limpiarMensajes,
  cargandoChat
} = useChat()

const chatSeleccionado = ref(null)
const textoMensaje = ref('')
const archivosSeleccionados = ref([])
const enviando = ref(false)

// Obtener chatId de la ruta
const chatId = computed(() => {
  const id = route.params.id
  return id ? parseInt(id) : null
})

// Obtener nombre del cliente (ClienteResource pone nombre y email al nivel raíz)
const nombreCliente = computed(() => {
  if (!chat.value) return ''
  return chat.value.cliente?.nombre || 'Cliente'
})

// Cargar chat cuando cambia el ID de la ruta
watch(chatId, async (nuevoId) => {
  if (nuevoId) {
    chatSeleccionado.value = nuevoId
    // Limpiar mensajes del chat anterior
    limpiarMensajes()
    try {
      await obtenerChat(nuevoId)
      await marcarComoLeido(nuevoId)
    } catch (err) {
      console.error('Error al cargar chat:', err)
    }
  } else {
    chatSeleccionado.value = null
  }
}, { immediate: true })

// Seleccionar chat desde la lista
async function seleccionarChat(id) {
  chatSeleccionado.value = id
  // Actualizar URL sin recargar
  window.history.pushState({}, '', `/coach/chat/${id}`)
  // Limpiar estado del chat anterior
  limpiarMensajes()
  try {
    await obtenerChat(id)
    await marcarComoLeido(id)
  } catch (err) {
    console.error('Error al cargar chat:', err)
  }
}

// Enviar mensaje
async function handleEnviarMensaje(datos) {
  if (enviando.value || !chatSeleccionado.value) return

  enviando.value = true
  try {
    await enviarMensaje(chatSeleccionado.value, {
      mensaje: datos.mensaje || '',
      archivos: datos.archivos || []
    })
    
    // Limpiar después de enviar
    textoMensaje.value = ''
    archivosSeleccionados.value = []
    
    // Marcar como leído
    await marcarComoLeido(chatSeleccionado.value)
  } catch (err) {
    console.error('Error al enviar mensaje:', err)
    alert('Error al enviar mensaje. Intenta de nuevo.')
  } finally {
    enviando.value = false
  }
}

// Manejar archivos seleccionados
function handleArchivosSeleccionados(archivos) {
  archivosSeleccionados.value = archivos
}

// Descargar archivo
function handleDescargarArchivo(archivo) {
  const url = obtenerUrlArchivo(chatSeleccionado.value, archivo.id)
  if (url) {
    window.open(url, '_blank')
  }
}
</script>

<template>
  <div class="chat-view">
    <!-- Vista de escritorio: Lista + Mensajes -->
    <div class="chat-view__desktop">
      <!-- Lista de chats -->
      <div class="chat-view__lista">
        <ChatLista
          :chat-seleccionado="chatSeleccionado"
          @seleccionar-chat="seleccionarChat"
        />
      </div>

      <!-- Área de mensajes -->
      <div class="chat-view__mensajes">
        <!-- Header del chat (solo si hay chat cargado) -->
        <div v-if="chatSeleccionado && chat" class="chat-view__header">
          <BaseAvatar
            :nombre="nombreCliente"
            :size="'md'"
          />
          <div class="chat-view__header-info">
            <h2 class="chat-view__header-nombre">{{ nombreCliente }}</h2>
            <p v-if="chat.cliente?.email" class="chat-view__header-email">
              {{ chat.cliente.email }}
            </p>
          </div>
        </div>

        <!-- Estado vacío: solo cuando NO hay chat seleccionado -->
        <div v-if="!chatSeleccionado" class="chat-view__vacio">
          <div class="chat-view__vacio-contenido">
            <div class="chat-view__vacio-icono">
              <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
              </svg>
            </div>
            <h3 class="chat-view__vacio-titulo">Selecciona un chat para comenzar</h3>
            <p class="chat-view__vacio-descripcion">
              Elige una conversación de la lista para ver los mensajes
            </p>
          </div>
        </div>

        <!-- Loading del chat -->
        <div v-if="chatSeleccionado && cargandoChat && !chat" class="chat-view__loading">
          <div class="chat-view__spinner"></div>
        </div>

        <!-- Mensajes (key fuerza remontaje al cambiar de chat) -->
        <ChatMensajes
          v-if="chatSeleccionado"
          :key="chatSeleccionado"
          :chat-id="chatSeleccionado"
          @archivo-descargar="handleDescargarArchivo"
        />

        <!-- Input de mensaje (key fuerza limpieza al cambiar de chat) -->
        <ChatInput
          v-if="chatSeleccionado"
          :key="'input-' + chatSeleccionado"
          v-model="textoMensaje"
          :cargando="enviando"
          :disabled="enviando"
          @enviar="handleEnviarMensaje"
          @archivos="handleArchivosSeleccionados"
        />
      </div>
    </div>
  </div>
</template>

<style scoped>
.chat-view {
  /* Ocupar toda la altura disponible dentro del AppLayout main */
  height: 100%;
  display: flex;
  flex-direction: column;
  background: #0a0a0a;
  overflow: hidden;
  position: relative;
}

.chat-view__desktop {
  display: flex;
  height: 100%;
  min-height: 0;
  overflow: hidden;
}

.chat-view__lista {
  width: 35%;
  min-width: 320px;
  max-width: 420px;
  border-right: 1px solid #252525;
  display: none;
  flex-shrink: 0;
  height: 100%;
  min-height: 0;
  overflow: hidden;
}

@media (min-width: 768px) {
  .chat-view__lista {
    display: flex;
    flex-direction: column;
  }
}

.chat-view__mensajes {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  min-width: 0;
  min-height: 0;
  height: 100%;
  position: relative;
}

.chat-view__header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem;
  background: #161616;
  border-bottom: 1px solid #252525;
  flex-shrink: 0;
  z-index: 10;
}

.chat-view__header-info {
  flex: 1;
  min-width: 0;
}

.chat-view__header-nombre {
  font-size: 1rem;
  font-weight: 600;
  color: #fff;
  margin: 0 0 0.125rem 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.chat-view__header-email {
  font-size: 0.75rem;
  color: #697586;
  margin: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.chat-view__vacio {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  background: #0a0a0a;
  min-height: 0;
}

.chat-view__vacio-contenido {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  max-width: 400px;
}

.chat-view__vacio-icono {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  background: #161616;
  border: 2px solid #252525;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #697586;
  margin-bottom: 1.5rem;
}

.chat-view__vacio-titulo {
  font-size: 1.25rem;
  font-weight: 600;
  color: #fff;
  margin: 0 0 0.5rem 0;
}

.chat-view__vacio-descripcion {
  font-size: 0.875rem;
  color: #697586;
  margin: 0;
  line-height: 1.5;
}

.chat-view__loading {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
}

.chat-view__spinner {
  width: 32px;
  height: 32px;
  border: 3px solid rgba(0, 210, 97, 0.2);
  border-top-color: #00D261;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
