<script setup>
/**
 * ChatView - Vista de chat para coach
 * Desktop: layout WhatsApp Desktop (lista izquierda + chat derecha)
 * Móvil: flujo WhatsApp móvil (lista → chat con back button)
 */

import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import ChatLista from '@/components/chat/ChatLista.vue'
import ChatMensajes from '@/components/chat/ChatMensajes.vue'
import ChatInput from '@/components/chat/ChatInput.vue'
import { useChat } from '@/composables/useChat'
import BaseAvatar from '@/components/ui/BaseAvatar.vue'

const route = useRoute()
const router = useRouter()
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

// --- Detección de móvil ---
const esMobil = ref(false)
const MOBILE_BREAKPOINT = 768

function checkMobile() {
  esMobil.value = window.innerWidth < MOBILE_BREAKPOINT
}

onMounted(() => {
  checkMobile()
  window.addEventListener('resize', checkMobile)
})

onUnmounted(() => {
  window.removeEventListener('resize', checkMobile)
})

// --- Computeds para mostrar lista o chat ---
// En desktop siempre se muestran ambos (lado a lado)
// En móvil: lista cuando no hay chat, chat cuando hay uno seleccionado
const mostrarLista = computed(() => {
  if (!esMobil.value) return true
  return !chatSeleccionado.value
})

const mostrarAreaChat = computed(() => {
  if (!esMobil.value) return true
  return !!chatSeleccionado.value
})

// Obtener chatId de la ruta
const chatId = computed(() => {
  const id = route.params.id
  return id ? parseInt(id) : null
})

// Obtener nombre del cliente (ClienteResource pone nombre y email al nivel raíz)
const nombreCliente = computed(() => {
  if (!chat.value) return ''
  const c = chat.value.cliente
  if (!c) return 'Cliente'
  const partes = [c.nombre, c.apellido_paterno].filter(Boolean)
  return partes.length > 0 ? partes.join(' ') : 'Cliente'
})

// Cargar chat cuando cambia el ID de la ruta
watch(chatId, async (nuevoId) => {
  if (nuevoId) {
    chatSeleccionado.value = nuevoId
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

// Seleccionar chat desde la lista (usa router para que el layout reaccione)
function seleccionarChat(id) {
  router.push({ name: 'CoachChatDetalle', params: { id } })
}

// Volver a la lista de chats (solo en móvil)
function volverALista() {
  chatSeleccionado.value = null
  limpiarMensajes()
  chat.value = null
  router.push({ name: 'CoachChat' })
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
    
    textoMensaje.value = ''
    archivosSeleccionados.value = []
    
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
  <div class="chat-view" :class="{ 'chat-view--en-chat-movil': chatSeleccionado && esMobil }">
    <!-- Panel izquierdo: Lista de chats -->
    <div v-show="mostrarLista" class="chat-view__panel-lista">
      <ChatLista
        :chat-seleccionado="chatSeleccionado"
        @seleccionar-chat="seleccionarChat"
      />
    </div>

    <!-- Panel derecho: Área de chat -->
    <div v-show="mostrarAreaChat" class="chat-view__panel-chat">
      <!-- Header del chat (con info del cliente) -->
      <div v-if="chatSeleccionado && chat" class="chat-view__header">
        <!-- Botón volver (solo móvil) -->
        <button
          v-if="esMobil"
          @click="volverALista"
          class="chat-view__back-btn"
          type="button"
          aria-label="Volver a la lista"
        >
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M19 12H5"></path>
            <path d="M12 19l-7-7 7-7"></path>
          </svg>
        </button>

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

      <!-- Estado vacío: cuando NO hay chat seleccionado (solo desktop) -->
      <div v-if="!chatSeleccionado && !esMobil" class="chat-view__vacio">
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
</template>

<style scoped>
/* ========================================
   CHAT VIEW - Layout tipo WhatsApp
   ======================================== */

.chat-view {
  display: flex;
  flex-direction: column;
  background: #0a0a0a;
  overflow: hidden;
  position: relative;
  /* Móvil: altura explícita descontando topbar y bottomnav.
     Usamos calc() porque el parent (AppLayout main) usa flex-1 con min-h-screen
     y height:100% no resuelve correctamente. */
  height: calc(100vh - var(--height-topbar) - var(--height-bottomnav));
  height: calc(100dvh - var(--height-topbar) - var(--height-bottomnav));
}

/* En DESKTOP: layout horizontal (lista + chat).
   Solo descuenta el topbar porque no hay bottomnav en desktop. */
@media (min-width: 768px) {
  .chat-view {
    flex-direction: row;
    height: calc(100vh - var(--height-topbar));
    height: calc(100dvh - var(--height-topbar));
    max-height: calc(100vh - var(--height-topbar));
  }
}

/* En móvil cuando estás dentro de un chat: ocupar pantalla completa */
.chat-view--en-chat-movil {
  position: fixed;
  inset: 0;
  z-index: 50;
  height: 100vh;
  height: 100dvh;
}

/* ========================================
   PANEL IZQUIERDO: Lista de chats
   ======================================== */
.chat-view__panel-lista {
  display: flex;
  flex-direction: column;
  overflow: hidden;
  background: #111111;
}

/* Móvil: la lista ocupa todo */
.chat-view__panel-lista {
  width: 100%;
  height: 100%;
  min-height: 0;
}

/* Desktop: panel fijo a la izquierda con ancho definido */
@media (min-width: 768px) {
  .chat-view__panel-lista {
    width: 380px;
    min-width: 300px;
    max-width: 420px;
    flex-shrink: 0;
    height: 100%;
    border-right: 1px solid #1e1e1e;
  }
}

@media (min-width: 1200px) {
  .chat-view__panel-lista {
    width: 400px;
  }
}

/* ========================================
   PANEL DERECHO: Área de chat
   ======================================== */
.chat-view__panel-chat {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  min-width: 0;
  min-height: 0;
  height: 100%;
  position: relative;
  background: #0a0a0a;
}

/* ========================================
   HEADER DEL CHAT (info del contacto)
   ======================================== */
.chat-view__header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.625rem 1rem;
  background: #111111;
  border-bottom: 1px solid #1e1e1e;
  flex-shrink: 0;
  z-index: 10;
  min-height: 56px;
}

@media (min-width: 768px) {
  .chat-view__header {
    padding: 0.5rem 1.25rem;
    min-height: 60px;
  }
}

.chat-view__back-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: transparent;
  border: none;
  color: #fff;
  cursor: pointer;
  transition: all 0.2s ease;
  flex-shrink: 0;
}

.chat-view__back-btn:hover {
  background: #252525;
}

.chat-view__back-btn:active {
  background: #353535;
  transform: scale(0.95);
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

/* ========================================
   ESTADO VACÍO (solo desktop, sin chat seleccionado)
   ======================================== */
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
  background: #111111;
  border: 2px solid #1e1e1e;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #3a3a3a;
  margin-bottom: 1.5rem;
}

.chat-view__vacio-titulo {
  font-size: 1.25rem;
  font-weight: 600;
  color: #e0e0e0;
  margin: 0 0 0.5rem 0;
}

.chat-view__vacio-descripcion {
  font-size: 0.875rem;
  color: #697586;
  margin: 0;
  line-height: 1.5;
}

/* ========================================
   LOADING
   ======================================== */
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
