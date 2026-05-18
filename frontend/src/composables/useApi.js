/**
 * Composable para manejar llamadas a la API
 * Incluye interceptores para auth y manejo de errores
 */

import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { Capacitor } from '@capacitor/core'

// URL base de la API (exportada para fetch directo en PDFs/descargas)
export const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1'
// Origen del backend (sin /api/v1) para Sanctum CSRF y URLs de storage
export const API_ORIGIN = API_BASE_URL.replace(/\/api\/v1\/?$/, '')

/** Obtener valor de la cookie XSRF-TOKEN (Laravel Sanctum) */
function getXsrfTokenFromCookie() {
  const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/)
  if (!match) return null
  try {
    return decodeURIComponent(match[1])
  } catch {
    return match[1]
  }
}

/** Asegurar que la cookie CSRF esté establecida antes de POST/PUT/DELETE.
 *  En apps nativas (Capacitor/APK) se omite: usan solo Bearer token, sin CSRF. */
let csrfPromise = null
async function ensureCsrfCookie() {
  if (Capacitor.isNativePlatform()) return
  if (csrfPromise) return csrfPromise
  csrfPromise = fetch(`${API_ORIGIN}/sanctum/csrf-cookie`, {
    credentials: 'include'
  })
  await csrfPromise
  csrfPromise = null
}

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
    const method = (opciones.method || 'GET').toUpperCase()

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

      // Sanctum SPA: CSRF obligatorio en peticiones con mutación cuando se usan cookies
      if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
        await ensureCsrfCookie()
        const xsrf = getXsrfTokenFromCookie()
        if (xsrf) headers['X-XSRF-TOKEN'] = xsrf
      }

      const respuesta = await fetch(`${API_BASE_URL}${endpoint}`, {
        ...opciones,
        headers,
        credentials: 'include' // Para cookies de Sanctum
      })

      let datos = {}
      const contentType = respuesta.headers.get('content-type')
      if (contentType && contentType.includes('application/json')) {
        datos = await respuesta.json()
      }

      // Manejar errores HTTP
      if (!respuesta.ok) {
        // Token CSRF inválido o caducado (419)
        if (respuesta.status === 419) {
          csrfPromise = null
          throw new Error('Sesión de seguridad caducada. Recarga la página e intenta de nuevo.')
        }
        // Token expirado o no autorizado
        if (respuesta.status === 401) {
          authStore.cerrarSesion()
          window.location.href = '/login'
          throw new Error('Sesión expirada. Por favor, inicia sesión nuevamente.')
        }
        // Error de validación (Laravel devuelve "message" y "errors")
        if (respuesta.status === 422) {
          const mensaje = datos.message || datos.mensaje || 'Error de validación'
          const err = new Error(mensaje)
          err.errores = datos.errors || {}
          throw err
        }
        throw new Error(datos.message || datos.mensaje || 'Error en la petición')
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

      // Sanctum SPA: CSRF obligatorio en peticiones POST
      await ensureCsrfCookie()
      const xsrf = getXsrfTokenFromCookie()
      if (xsrf) headers['X-XSRF-TOKEN'] = xsrf

      const respuesta = await fetch(`${API_BASE_URL}${endpoint}`, {
        method: 'POST',
        headers,
        body: formData,
        credentials: 'include'
      })

      let datos = {}
      const contentType = respuesta.headers.get('content-type')
      if (contentType && contentType.includes('application/json')) {
        datos = await respuesta.json()
      }

      if (!respuesta.ok) {
        // Token CSRF inválido o caducado (419)
        if (respuesta.status === 419) {
          csrfPromise = null
          throw new Error('Sesión de seguridad caducada. Recarga la página e intenta de nuevo.')
        }
        // Token expirado o no autorizado
        if (respuesta.status === 401) {
          authStore.cerrarSesion()
          window.location.href = '/login'
          throw new Error('Sesión expirada. Por favor, inicia sesión nuevamente.')
        }
        // Error de validación
        if (respuesta.status === 422) {
          const mensaje = datos.message || datos.mensaje || 'Error de validación'
          const err = new Error(mensaje)
          err.errores = datos.errors || {}
          throw err
        }
        throw new Error(datos.mensaje || datos.message || 'Error al subir archivo')
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
