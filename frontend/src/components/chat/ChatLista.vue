<script setup>
/**
 * ChatLista - Lista de chats (solo para coach)
 * Muestra todos los chats del coach con último mensaje y no leídos
 */

import { ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import BaseAvatar from '@/components/ui/BaseAvatar.vue'
import { useChat } from '@/composables/useChat'

const props = defineProps({
  chatSeleccionado: {
    type: Number,
    default: null
  }
})

const emit = defineEmits(['seleccionar-chat'])

const router = useRouter()
const { obtenerChats, cargandoChats } = useChat()

const chats = ref([])
const chatsFiltrados = ref([])
const pagina = ref(1)
const hayMas = ref(true)
const busqueda = ref('')

// Filtrar chats por búsqueda
function filtrarChats() {
  if (!busqueda.value.trim()) {
    chatsFiltrados.value = chats.value
    return
  }
  
  const termino = busqueda.value.toLowerCase().trim()
  chatsFiltrados.value = chats.value.filter(chat => {
    const nombre = obtenerNombreCliente(chat).toLowerCase()
    return nombre.includes(termino)
  })
}

// Watch para filtrar cuando cambia la búsqueda o los chats
watch([busqueda, chats], () => {
  filtrarChats()
}, { immediate: true, deep: true })

// Formatear fecha del último mensaje
function formatearFecha(fecha) {
  if (!fecha) return ''
  const fechaObj = new Date(fecha)
  const ahora = new Date()
  const diffMs = ahora - fechaObj
  const diffDias = Math.floor(diffMs / 86400000)

  if (diffDias === 0) {
    return fechaObj.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })
  }
  if (diffDias === 1) return 'Ayer'
  if (diffDias < 7) return `Hace ${diffDias}d`
  
  return fechaObj.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' })
}

// Obtener nombre del cliente
function obtenerNombreCliente(chat) {
  return chat.cliente?.nombre || 'Cliente'
}

// Obtener preview del último mensaje
function obtenerPreviewMensaje(chat) {
  if (!chat.ultimo_mensaje) return 'Sin mensajes'
  
  const mensaje = chat.ultimo_mensaje.mensaje
  if (mensaje) {
    return mensaje.length > 50 ? mensaje.substring(0, 50) + '...' : mensaje
  }
  
  // Si tiene archivos
  if (chat.ultimo_mensaje.archivos && chat.ultimo_mensaje.archivos.length > 0) {
    const archivos = chat.ultimo_mensaje.archivos
    const imagenes = archivos.filter(a => a.es_imagen).length
    const documentos = archivos.filter(a => a.es_documento).length
    
    if (imagenes > 0 && documentos > 0) {
      return `📎 ${imagenes} imagen${imagenes > 1 ? 'es' : ''}, ${documentos} documento${documentos > 1 ? 's' : ''}`
    }
    if (imagenes > 0) {
      return `📷 ${imagenes} imagen${imagenes > 1 ? 'es' : ''}`
    }
    if (documentos > 0) {
      return `📄 ${documentos} documento${documentos > 1 ? 's' : ''}`
    }
  }
  
  return 'Archivo adjunto'
}

// Seleccionar chat
function seleccionarChat(chat) {
  emit('seleccionar-chat', chat.id)
}

// Cargar chats
async function cargarChats() {
  try {
    const respuesta = await obtenerChats(pagina.value)
    
    // La respuesta de PaginacionCollection viene como { datos: [...], meta: {...} }
    if (respuesta && typeof respuesta === 'object') {
      if (Array.isArray(respuesta)) {
        // Si es un array directo
        chats.value = respuesta
      } else if (respuesta.datos && Array.isArray(respuesta.datos)) {
        // Si tiene estructura { datos: [...], meta: {...} }
        chats.value = respuesta.datos
        if (respuesta.meta) {
          hayMas.value = respuesta.meta.pagina_actual < respuesta.meta.ultima_pagina
        }
      }
    }
  } catch (err) {
    console.error('Error al cargar chats:', err)
  }
}

onMounted(() => {
  cargarChats()
})
</script>

<template>
  <div class="chat-lista">
    <!-- Header -->
    <div class="chat-lista__header">
      <h2 class="chat-lista__titulo">Chats</h2>
      
      <!-- Búsqueda -->
      <div class="chat-lista__busqueda">
        <svg class="chat-lista__busqueda-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"></circle>
          <path d="m21 21-4.35-4.35"></path>
        </svg>
        <input
          v-model="busqueda"
          type="text"
          placeholder="Buscar cliente..."
          class="chat-lista__busqueda-input"
        />
        <button
          v-if="busqueda"
          @click="busqueda = ''"
          type="button"
          class="chat-lista__busqueda-clear"
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

    <!-- Loading -->
    <div v-if="cargandoChats && chats.length === 0" class="chat-lista__loading">
      <div class="chat-lista__spinner"></div>
    </div>

    <!-- Lista de chats -->
    <div v-else class="chat-lista__contenido">
      <!-- Estado vacío: sin chats -->
      <div v-if="!cargandoChats && chats.length === 0" class="chat-lista__vacio">
        <p class="chat-lista__vacio-texto">No tienes chats aún</p>
        <p class="chat-lista__vacio-hint">Los chats aparecerán aquí cuando tus clientes te escriban</p>
      </div>

      <!-- Estado vacío: sin resultados de búsqueda -->
      <div v-else-if="!cargandoChats && chats.length > 0 && chatsFiltrados.length === 0" class="chat-lista__vacio">
        <p class="chat-lista__vacio-texto">No se encontraron resultados</p>
        <p class="chat-lista__vacio-hint">Intenta con otro término de búsqueda</p>
      </div>

      <!-- Items de chat -->
      <button
        v-for="chat in chatsFiltrados"
        :key="chat.id"
        @click="seleccionarChat(chat)"
        :class="[
          'chat-lista__item',
          {
            'chat-lista__item--seleccionado': chatSeleccionado === chat.id
          }
        ]"
      >
        <!-- Avatar -->
        <BaseAvatar
          :nombre="obtenerNombreCliente(chat)"
          :size="'md'"
          class="chat-lista__avatar"
        />

        <!-- Contenido -->
        <div class="chat-lista__info">
          <div class="chat-lista__info-header">
            <span class="chat-lista__nombre">
              {{ obtenerNombreCliente(chat) }}
            </span>
            <span v-if="chat.ultimo_mensaje" class="chat-lista__fecha">
              {{ formatearFecha(chat.ultimo_mensaje.enviado_en) }}
            </span>
          </div>
          <div class="chat-lista__info-footer">
            <span class="chat-lista__preview">
              {{ obtenerPreviewMensaje(chat) }}
            </span>
            <span
              v-if="chat.mensajes_no_leidos > 0"
              class="chat-lista__badge"
            >
              {{ chat.mensajes_no_leidos > 99 ? '99+' : chat.mensajes_no_leidos }}
            </span>
          </div>
        </div>
      </button>
    </div>
  </div>
</template>

<style scoped>
.chat-lista {
  display: flex;
  flex-direction: column;
  height: 100%;
  min-height: 0;
  background: #161616;
  overflow: hidden;
}

.chat-lista__header {
  padding: 1rem;
  border-bottom: 1px solid #252525;
  flex-shrink: 0;
}

.chat-lista__titulo {
  font-size: 1.125rem;
  font-weight: 600;
  color: #fff;
  margin: 0 0 0.75rem 0;
}

.chat-lista__busqueda {
  position: relative;
  display: flex;
  align-items: center;
  background: #1e1e1e;
  border: 1px solid #252525;
  border-radius: 12px;
  padding: 0.5rem 0.75rem;
  transition: all 0.2s ease;
}

.chat-lista__busqueda:focus-within {
  border-color: #00D261;
  background: #252525;
}

.chat-lista__busqueda-icon {
  color: #697586;
  flex-shrink: 0;
  margin-right: 0.5rem;
}

.chat-lista__busqueda-input {
  flex: 1;
  background: transparent;
  border: none;
  color: #fff;
  font-size: 0.875rem;
  outline: none;
  padding: 0;
}

.chat-lista__busqueda-input::placeholder {
  color: #697586;
}

.chat-lista__busqueda-clear {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  background: transparent;
  border: none;
  color: #697586;
  cursor: pointer;
  transition: color 0.2s ease;
  flex-shrink: 0;
  margin-left: 0.5rem;
  padding: 0;
}

.chat-lista__busqueda-clear:hover {
  color: #fff;
}

.chat-lista__loading {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
}

.chat-lista__spinner {
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

.chat-lista__contenido {
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
  min-height: 0;
  /* Scrollbar personalizado */
  scrollbar-width: thin;
  scrollbar-color: #252525 #161616;
}

.chat-lista__contenido::-webkit-scrollbar {
  width: 8px;
}

.chat-lista__contenido::-webkit-scrollbar-track {
  background: #161616;
}

.chat-lista__contenido::-webkit-scrollbar-thumb {
  background: #252525;
  border-radius: 4px;
}

.chat-lista__contenido::-webkit-scrollbar-thumb:hover {
  background: #353535;
}

.chat-lista__vacio {
  padding: 2rem 1rem;
  text-align: center;
}

.chat-lista__vacio-texto {
  font-size: 1rem;
  font-weight: 600;
  color: #fff;
  margin-bottom: 0.5rem;
}

.chat-lista__vacio-hint {
  font-size: 0.875rem;
  color: #697586;
}

.chat-lista__item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem;
  width: 100%;
  text-align: left;
  background: transparent;
  border: none;
  border-bottom: 1px solid #252525;
  cursor: pointer;
  transition: all 0.2s ease;
}

.chat-lista__item:hover {
  background: #1e1e1e;
}

.chat-lista__item--seleccionado {
  background: #1e1e1e;
  border-left: 3px solid #00D261;
}

.chat-lista__avatar {
  flex-shrink: 0;
}

.chat-lista__info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.chat-lista__info-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
}

.chat-lista__nombre {
  font-size: 0.875rem;
  font-weight: 600;
  color: #fff;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.chat-lista__fecha {
  font-size: 0.75rem;
  color: #697586;
  flex-shrink: 0;
}

.chat-lista__info-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
}

.chat-lista__preview {
  font-size: 0.8125rem;
  color: #a0a0a0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  flex: 1;
}

.chat-lista__badge {
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 20px;
  height: 20px;
  padding: 0 0.375rem;
  background: #00D261;
  color: #fff;
  font-size: 0.6875rem;
  font-weight: 600;
  border-radius: 10px;
  flex-shrink: 0;
}
</style>

