/**
 * Composable para manejar la lógica del chat
 * Soporta envío de mensajes con texto, emojis y archivos
 */

import { ref, computed } from 'vue'
import { useApi } from '@/composables/useApi'
import { useAuthStore } from '@/stores/auth'

// Instancia compartida del composable para mantener estado único
let chatInstance = null

export function useChat() {
  // Reutilizar la misma instancia si existe
  if (chatInstance) {
    return chatInstance
  }
  const { get, post, put, subirArchivo } = useApi()
  const authStore = useAuthStore()

  // Estado
  const chat = ref(null)
  const mensajes = ref([])
  const ultimoMensajeId = ref(null)
  const paginaActual = ref(1)
  const hayMasMensajes = ref(true)

  // Estados de carga separados para evitar race conditions
  const cargandoChats = ref(false)
  const cargandoMensajes = ref(false)
  const cargandoChat = ref(false)
  const error = ref(null)

  // Computed
  const esCoach = computed(() => authStore.esCoach)
  const esCliente = computed(() => authStore.esCliente)
  const baseEndpoint = computed(() => {
    if (esCoach.value) return '/coach/chats'
    if (esCliente.value) return '/cliente/chat'
    return null
  })

  /**
   * Obtener lista de chats (solo para coach)
   */
  async function obtenerChats(pagina = 1) {
    if (!esCoach.value) {
      throw new Error('Solo los coaches pueden listar chats')
    }

    cargandoChats.value = true
    try {
      const respuesta = await get(`${baseEndpoint.value}?page=${pagina}`)
      // Laravel envuelve ResourceCollection en 'data'
      // PaginacionCollection devuelve { data: { datos: [...] }, meta: {...} }
      if (respuesta.data) {
        return {
          datos: respuesta.data.datos || [],
          meta: respuesta.meta || {}
        }
      }
      return respuesta || { datos: [], meta: {} }
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      cargandoChats.value = false
    }
  }

  /**
   * Crear un nuevo chat con un cliente (solo para coach)
   */
  async function crearChat(clienteId) {
    if (!esCoach.value) {
      throw new Error('Solo los coaches pueden crear chats')
    }

    try {
      const respuesta = await post(`${baseEndpoint.value}`, {
        cliente_id: clienteId
      })
      return respuesta.datos?.id || null
    } catch (err) {
      throw err
    }
  }

  /**
   * Obtener chat específico
   * Para coach: requiere chatId
   * Para cliente: obtiene su único chat
   */
  async function obtenerChat(chatId = null) {
    cargandoChat.value = true
    try {
      let endpoint = baseEndpoint.value
      if (esCoach.value && chatId) {
        endpoint = `${baseEndpoint.value}/${chatId}`
      }

      const respuesta = await get(endpoint)
      chat.value = respuesta.datos || null
      return chat.value
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      cargandoChat.value = false
    }
  }

  /**
   * Obtener mensajes del chat
   */
  async function obtenerMensajes(chatId = null, pagina = 1) {
    cargandoMensajes.value = true
    try {
      let endpoint = baseEndpoint.value
      if (esCoach.value && chatId) {
        endpoint = `${baseEndpoint.value}/${chatId}/mensajes`
      } else if (esCliente.value) {
        endpoint = `${baseEndpoint.value}/mensajes`
      } else {
        throw new Error('Rol no válido para obtener mensajes')
      }

      const respuesta = await get(`${endpoint}?page=${pagina}`)
      // Laravel envuelve ResourceCollection en 'data', y luego agrega 'links' y 'meta' al mismo nivel
      const datos = respuesta.data?.datos || respuesta.datos || []
      const meta = respuesta.meta || {}

      // Los mensajes vienen ordenados descendente (más recientes primero)
      // Necesitamos revertirlos para mostrarlos cronológicamente (más antiguos arriba)
      const datosReversados = [...datos].reverse()

      // Si es la primera página, reemplazar mensajes
      if (pagina === 1) {
        mensajes.value = datosReversados
        paginaActual.value = 1
      } else {
        // Si es una página anterior (mensajes más antiguos), agregar al inicio
        mensajes.value = [...datosReversados, ...mensajes.value]
      }

      // Actualizar estado de paginación
      hayMasMensajes.value = meta.pagina_actual < meta.ultima_pagina
      paginaActual.value = meta.pagina_actual || pagina

      // Actualizar último mensaje ID (el más reciente es el primero del array original)
      if (datos.length > 0) {
        const masReciente = datos[0] // El primero es el más reciente
        if (!ultimoMensajeId.value || masReciente.id > ultimoMensajeId.value) {
          ultimoMensajeId.value = masReciente.id
        }
      }

      return datosReversados
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      cargandoMensajes.value = false
    }
  }

  /**
   * Obtener solo mensajes nuevos (para polling)
   * Compara con el último mensaje conocido
   * No usa cargandoMensajes para no bloquear la UI
   */
  async function obtenerMensajesNuevos(chatId = null) {
    try {
      // Obtener mensajes directamente sin procesar (para polling)
      let endpoint = baseEndpoint.value
      if (esCoach.value && chatId) {
        endpoint = `${baseEndpoint.value}/${chatId}/mensajes`
      } else if (esCliente.value) {
        endpoint = `${baseEndpoint.value}/mensajes`
      } else {
        throw new Error('Rol no válido para obtener mensajes')
      }

      const respuesta = await get(`${endpoint}?page=1`)
      // Laravel envuelve ResourceCollection en 'data'
      const datos = respuesta.data?.datos || respuesta.datos || []
      
      // Los mensajes vienen ordenados descendente (más recientes primero)
      // Filtrar solo los mensajes nuevos
      if (ultimoMensajeId.value) {
        const nuevos = datos.filter(
          msg => msg.id > ultimoMensajeId.value
        )
        
        // Actualizar último mensaje ID si hay nuevos
        if (nuevos.length > 0) {
          // El más reciente es el primero del array
          ultimoMensajeId.value = nuevos[0].id
          // Revertir para agregar al final en orden cronológico
          const nuevosReversados = [...nuevos].reverse()
          mensajes.value = [...mensajes.value, ...nuevosReversados]
          return nuevosReversados
        }
        
        return []
      } else {
        // Primera vez, actualizar último mensaje ID (el más reciente es el primero)
        if (datos.length > 0) {
          ultimoMensajeId.value = datos[0].id
        }
        return []
      }
    } catch (err) {
      throw err
    }
  }

  /**
   * Enviar mensaje
   * Soporta texto, emojis y archivos
   */
  async function enviarMensaje(chatId, datosMensaje) {
    try {
      let endpoint = baseEndpoint.value
      if (esCoach.value && chatId) {
        endpoint = `${baseEndpoint.value}/${chatId}/mensajes`
      } else if (esCliente.value) {
        endpoint = `${baseEndpoint.value}/mensajes`
      } else {
        throw new Error('Rol no válido para enviar mensajes')
      }

      // Crear FormData para soportar archivos
      const formData = new FormData()
      
      if (datosMensaje.mensaje) {
        formData.append('mensaje', datosMensaje.mensaje)
      }

      if (datosMensaje.archivos && datosMensaje.archivos.length > 0) {
        datosMensaje.archivos.forEach((archivo) => {
          formData.append('archivos[]', archivo)
        })
      }

      const respuesta = await subirArchivo(endpoint, formData)
      const nuevoMensaje = respuesta.datos

      // Agregar mensaje a la lista (al final, porque los mensajes están ordenados cronológicamente)
      mensajes.value.push(nuevoMensaje)
      
      // Actualizar último mensaje ID
      if (nuevoMensaje.id) {
        ultimoMensajeId.value = nuevoMensaje.id
      }

      return nuevoMensaje
    } catch (err) {
      error.value = err.message
      throw err
    }
  }

  /**
   * Marcar mensajes como leídos
   */
  async function marcarComoLeido(chatId = null) {
    try {
      let endpoint = baseEndpoint.value
      if (esCoach.value && chatId) {
        endpoint = `${baseEndpoint.value}/${chatId}/leer`
      } else if (esCliente.value) {
        endpoint = `${baseEndpoint.value}/leer`
      } else {
        throw new Error('Rol no válido para marcar como leído')
      }

      // La ruta usa PUT, no POST
      await put(endpoint)
      
      // Actualizar estado local
      if (chat.value) {
        chat.value.mensajes_no_leidos = 0
      }
    } catch (err) {
      throw err
    }
  }

  /**
   * Obtener URL de descarga de archivo
   */
  function obtenerUrlArchivo(chatId, archivoId) {
    if (esCoach.value && chatId) {
      return `${baseEndpoint.value}/${chatId}/archivos/${archivoId}`
    } else if (esCliente.value) {
      return `${baseEndpoint.value}/archivos/${archivoId}`
    }
    return null
  }

  /**
   * Cargar más mensajes (paginación hacia atrás)
   */
  async function cargarMasMensajes(chatId = null) {
    if (!hayMasMensajes.value) {
      return []
    }

    try {
      const siguientePagina = paginaActual.value + 1
      return await obtenerMensajes(chatId, siguientePagina)
    } catch (err) {
      throw err
    }
  }

  /**
   * Limpiar solo mensajes (al cambiar de chat)
   */
  function limpiarMensajes() {
    mensajes.value = []
    ultimoMensajeId.value = null
    paginaActual.value = 1
    hayMasMensajes.value = true
  }

  /**
   * Limpiar estado completo del chat
   */
  function limpiarChat() {
    chat.value = null
    limpiarMensajes()
  }

  const instance = {
    // Estado
    chat,
    mensajes,
    ultimoMensajeId,
    paginaActual,
    hayMasMensajes,
    // Estados de carga separados
    cargandoChats,
    cargandoMensajes,
    cargandoChat,
    error,
    // Computed
    esCoach,
    esCliente,
    // Métodos
    obtenerChats,
    obtenerChat,
    obtenerMensajes,
    obtenerMensajesNuevos,
    enviarMensaje,
    marcarComoLeido,
    obtenerUrlArchivo,
    cargarMasMensajes,
    crearChat,
    limpiarMensajes,
    limpiarChat
  }

  // Guardar instancia para reutilización
  chatInstance = instance

  return instance
}
