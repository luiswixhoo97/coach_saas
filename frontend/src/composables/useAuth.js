/**
 * Composable para manejar autenticación
 */

import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useApi } from '@/composables/useApi'

export function useAuth() {
  const router = useRouter()
  const authStore = useAuthStore()
  const { post, get, cargando, error } = useApi()

  const erroresValidacion = ref({})

  // Estado computado
  const autenticado = computed(() => authStore.autenticado)
  const usuario = computed(() => authStore.usuario)
  const rol = computed(() => authStore.rol)

  /**
   * Iniciar sesión
   */
  async function login(credenciales) {
    erroresValidacion.value = {}
    
    try {
      const respuesta = await post('/auth/login', credenciales)
      
      if (respuesta.datos) {
        const { usuario, token } = respuesta.datos
        authStore.establecerSesion(usuario, token)
        
        // Redirigir según el rol
        redirigirSegunRol(usuario.rol)
        
        return { exito: true }
      }
    } catch (err) {
      // Manejar errores de validación
      if (err.errores) {
        erroresValidacion.value = err.errores
      }
      return { exito: false, error: err.message || 'Error al iniciar sesión' }
    }
  }

  /**
   * Cerrar sesión
   */
  async function logout() {
    try {
      await post('/auth/logout')
    } catch (err) {
      // Ignorar errores al cerrar sesión
      console.error('Error al cerrar sesión:', err)
    } finally {
      authStore.cerrarSesion()
      router.push('/login')
    }
  }

  /**
   * Obtener usuario actual
   */
  async function obtenerUsuarioActual() {
    try {
      const respuesta = await get('/auth/me')
      if (respuesta.datos) {
        authStore.actualizarUsuario(respuesta.datos)
      }
      return respuesta.datos
    } catch (err) {
      // Si falla, probablemente el token expiró
      authStore.cerrarSesion()
      return null
    }
  }

  /**
   * Verificar si hay sesión activa al iniciar la app
   */
  async function verificarSesion() {
    authStore.inicializarSesion()
    
    if (authStore.token) {
      // Verificar que el token siga siendo válido
      const usuario = await obtenerUsuarioActual()
      return !!usuario
    }
    
    return false
  }

  /**
   * Redirigir según el rol del usuario
   */
  function redirigirSegunRol(rolUsuario) {
    switch (rolUsuario) {
      case 'admin':
        router.push('/admin')
        break
      case 'coach':
        router.push('/coach')
        break
      case 'cliente':
        router.push('/cliente')
        break
      default:
        router.push('/')
    }
  }

  return {
    // Estado
    cargando,
    error,
    erroresValidacion,
    autenticado,
    usuario,
    rol,
    // Métodos
    login,
    logout,
    obtenerUsuarioActual,
    verificarSesion,
    redirigirSegunRol
  }
}
