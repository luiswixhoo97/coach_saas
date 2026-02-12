/**
 * Composable para manejar polling de mensajes nuevos
 * Polling cada 3 segundos cuando el chat está activo
 */

import { ref, onUnmounted, watch } from 'vue'
import { useChat } from './useChat'

export function useChatPolling(chatId = null) {
  // Reutilizar la instancia compartida del composable
  const { obtenerMensajesNuevos, cargandoMensajes } = useChat()

  // Estado
  const activo = ref(false)
  const intervalo = ref(null)
  const intervaloMs = ref(3000) // 3 segundos por defecto
  const ultimaActualizacion = ref(null)
  const errores = ref(0)
  const maxErrores = 3
  let pollingEnProceso = false

  /**
   * Iniciar polling
   */
  function iniciar() {
    if (activo.value) {
      return // Ya está activo
    }

    activo.value = true
    errores.value = 0

    // Polling inmediato
    ejecutarPolling()

    // Configurar intervalo
    intervalo.value = setInterval(() => {
      if (activo.value) {
        ejecutarPolling()
      }
    }, intervaloMs.value)
  }

  /**
   * Detener polling
   */
  function detener() {
    activo.value = false
    if (intervalo.value) {
      clearInterval(intervalo.value)
      intervalo.value = null
    }
  }

  /**
   * Ejecutar una consulta de polling
   * Solo bloquea si hay una carga completa de mensajes en curso (cargandoMensajes)
   * o si ya hay un polling en proceso
   */
  async function ejecutarPolling() {
    if (!activo.value || cargandoMensajes.value || pollingEnProceso) {
      return
    }

    pollingEnProceso = true
    try {
      const nuevosMensajes = await obtenerMensajesNuevos(chatId)
      ultimaActualizacion.value = new Date()
      errores.value = 0 // Resetear contador de errores

      // Retornar los mensajes nuevos para que el componente pueda reaccionar
      return nuevosMensajes
    } catch (err) {
      console.error('Error en polling de mensajes:', err)
      errores.value++

      // Si hay muchos errores consecutivos, detener polling
      if (errores.value >= maxErrores) {
        console.warn('Demasiados errores en polling, deteniendo...')
        detener()
      }

      throw err
    } finally {
      pollingEnProceso = false
    }
  }

  /**
   * Cambiar intervalo de polling
   */
  function cambiarIntervalo(ms) {
    intervaloMs.value = ms
    if (activo.value) {
      detener()
      iniciar()
    }
  }

  /**
   * Reiniciar polling (útil después de errores)
   */
  function reiniciar() {
    detener()
    iniciar()
  }

  // Limpiar al desmontar
  onUnmounted(() => {
    detener()
  })

  // Watch para chatId (si cambia, reiniciar polling)
  watch(() => chatId, () => {
    if (activo.value) {
      reiniciar()
    }
  })

  return {
    // Estado
    activo,
    intervaloMs,
    ultimaActualizacion,
    errores,
    cargandoMensajes,
    // Métodos
    iniciar,
    detener,
    ejecutarPolling,
    cambiarIntervalo,
    reiniciar
  }
}
