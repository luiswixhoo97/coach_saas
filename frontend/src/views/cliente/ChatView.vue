<script setup>
/**
 * ChatView - Vista de chat para cliente
 * Muestra el chat con su coach
 */

import { ref, computed, onMounted } from 'vue'
import ChatMensajes from '@/components/chat/ChatMensajes.vue'
import ChatInput from '@/components/chat/ChatInput.vue'
import BaseAvatar from '@/components/ui/BaseAvatar.vue'
import { useChat } from '@/composables/useChat'

const { chat, obtenerChat, enviarMensaje, marcarComoLeido, obtenerUrlArchivo, cargandoChat } = useChat()

const textoMensaje = ref('')
const archivosSeleccionados = ref([])
const enviando = ref(false)

// Obtener nombre del coach
const nombreCoach = computed(() => {
  if (!chat.value) return 'Coach'
  return chat.value.coach?.nombre || 'Coach'
})

// Cargar chat al montar
onMounted(async () => {
  try {
    await obtenerChat()
    await marcarComoLeido()
  } catch (err) {
    console.error('Error al cargar chat:', err)
  }
})

// Enviar mensaje
async function handleEnviarMensaje(datos) {
  if (enviando.value) return

  enviando.value = true
  try {
    await enviarMensaje(null, {
      mensaje: datos.mensaje || '',
      archivos: datos.archivos || []
    })
    
    // Limpiar después de enviar
    textoMensaje.value = ''
    archivosSeleccionados.value = []
    
    // Marcar como leído
    await marcarComoLeido()
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
  const url = obtenerUrlArchivo(null, archivo.id)
  if (url) {
    window.open(url, '_blank')
  }
}
</script>

<template>
  <div class="chat-view">
    <!-- Header del chat -->
    <div v-if="chat" class="chat-view__header">
      <BaseAvatar
        :nombre="nombreCoach"
        :size="'md'"
      />
      <div class="chat-view__header-info">
        <h2 class="chat-view__header-nombre">{{ nombreCoach }}</h2>
        <p v-if="chat.coach?.email" class="chat-view__header-email">
          {{ chat.coach.email }}
        </p>
      </div>
    </div>

    <!-- Loading inicial -->
    <div v-if="cargandoChat && !chat" class="chat-view__loading">
      <div class="chat-view__spinner"></div>
    </div>

    <!-- Mensajes (solo si hay chat) -->
    <ChatMensajes
      v-if="chat"
      :chat-id="null"
      @archivo-descargar="handleDescargarArchivo"
    />

    <!-- Estado vacío: sin chat -->
    <div v-if="!chat && !cargandoChat" class="chat-view__vacio">
      <p class="chat-view__vacio-texto">No tienes un chat activo</p>
      <p class="chat-view__vacio-hint">Tu coach creará un chat cuando sea necesario</p>
    </div>

    <!-- Input de mensaje -->
    <ChatInput
      v-if="chat"
      v-model="textoMensaje"
      :cargando="enviando"
      :disabled="enviando"
      @enviar="handleEnviarMensaje"
      @archivos="handleArchivosSeleccionados"
    />
  </div>
</template>

<style scoped>
.chat-view {
  height: 100%;
  display: flex;
  flex-direction: column;
  background: #0a0a0a;
  overflow: hidden;
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

.chat-view__vacio {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  text-align: center;
}

.chat-view__vacio-texto {
  font-size: 1rem;
  font-weight: 600;
  color: #fff;
  margin-bottom: 0.5rem;
}

.chat-view__vacio-hint {
  font-size: 0.875rem;
  color: #697586;
}
</style>

