/**
 * Composable para manejar llamadas a la API
 * Incluye interceptores para auth y manejo de errores
 */

import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'

// URL base de la API
const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1'

/**
 * Composable principal para llamadas API
 */
export function useApi() {
  const cargando = ref(false)
  const error = ref(null)

  /**
   * Realiza una petición HTTP
   */
  async function peticion(endpoint, opciones = {}) {
    const authStore = useAuthStore()
    
    cargando.value = true
    error.value = null

    try {
      const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...opciones.headers
      }

      if (authStore.token) {
        headers['Authorization'] = `Bearer ${authStore.token}`
      }

      const respuesta = await fetch(`${API_BASE_URL}${endpoint}`, {
        ...opciones,
        headers,
        credentials: 'include' // Para cookies de Sanctum
      })

      const datos = await respuesta.json()

      // Manejar errores HTTP
      if (!respuesta.ok) {
        // Token expirado o no autorizado
        if (respuesta.status === 401) {
          authStore.cerrarSesion()
          window.location.href = '/login'
          throw new Error('Sesión expirada. Por favor, inicia sesión nuevamente.')
        }

        // Error de validación
        if (respuesta.status === 422) {
          throw new Error(datos.mensaje || 'Error de validación')
        }

        throw new Error(datos.mensaje || 'Error en la petición')
      }

      return datos
    } catch (err) {
      const mensaje = err instanceof Error ? err.message : 'Error desconocido'
      error.value = mensaje
      throw err
    } finally {
      cargando.value = false
    }
  }

  /**
   * GET request
   */
  async function get(endpoint) {
    return peticion(endpoint, { method: 'GET' })
  }

  /**
   * POST request
   */
  async function post(endpoint, datos = null) {
    return peticion(endpoint, {
      method: 'POST',
      body: datos ? JSON.stringify(datos) : undefined
    })
  }

  /**
   * PUT request
   */
  async function put(endpoint, datos = null) {
    return peticion(endpoint, {
      method: 'PUT',
      body: datos ? JSON.stringify(datos) : undefined
    })
  }

  /**
   * DELETE request
   */
  async function del(endpoint) {
    return peticion(endpoint, { method: 'DELETE' })
  }

  /**
   * POST para subir archivos (FormData)
   */
  async function subirArchivo(endpoint, formData) {
    const authStore = useAuthStore()
    
    cargando.value = true
    error.value = null

    try {
      const headers = {
        'Accept': 'application/json'
      }

      if (authStore.token) {
        headers['Authorization'] = `Bearer ${authStore.token}`
      }

      const respuesta = await fetch(`${API_BASE_URL}${endpoint}`, {
        method: 'POST',
        headers,
        body: formData,
        credentials: 'include'
      })

      const datos = await respuesta.json()

      if (!respuesta.ok) {
        throw new Error(datos.mensaje || 'Error al subir archivo')
      }

      return datos
    } catch (err) {
      const mensaje = err instanceof Error ? err.message : 'Error desconocido'
      error.value = mensaje
      throw err
    } finally {
      cargando.value = false
    }
  }

  return {
    cargando,
    error,
    get,
    post,
    put,
    del,
    subirArchivo
  }
}
