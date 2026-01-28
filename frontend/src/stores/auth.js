import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

const TOKEN_KEY = 'coach_saas_token'
const USER_KEY = 'coach_saas_user'

export const useAuthStore = defineStore('auth', () => {
  // Estado
  const usuario = ref(null)
  const token = ref(null)
  const cargando = ref(false)

  // Getters computados
  const autenticado = computed(() => !!token.value && !!usuario.value)
  const rol = computed(() => usuario.value?.rol || null)
  const esCoach = computed(() => rol.value === 'coach')
  const esCliente = computed(() => rol.value === 'cliente')
  const esAdmin = computed(() => rol.value === 'admin')

  // Acciones
  function inicializarSesion() {
    const tokenGuardado = localStorage.getItem(TOKEN_KEY)
    const usuarioGuardado = localStorage.getItem(USER_KEY)

    if (tokenGuardado && usuarioGuardado) {
      token.value = tokenGuardado
      usuario.value = JSON.parse(usuarioGuardado)
    }
  }

  function establecerSesion(datosUsuario, tokenAcceso) {
    usuario.value = datosUsuario
    token.value = tokenAcceso

    localStorage.setItem(TOKEN_KEY, tokenAcceso)
    localStorage.setItem(USER_KEY, JSON.stringify(datosUsuario))
  }

  function cerrarSesion() {
    usuario.value = null
    token.value = null

    localStorage.removeItem(TOKEN_KEY)
    localStorage.removeItem(USER_KEY)
  }

  function actualizarUsuario(datosUsuario) {
    usuario.value = { ...usuario.value, ...datosUsuario }
    localStorage.setItem(USER_KEY, JSON.stringify(usuario.value))
  }

  return {
    // Estado
    usuario,
    token,
    cargando,
    // Getters
    autenticado,
    rol,
    esCoach,
    esCliente,
    esAdmin,
    // Acciones
    inicializarSesion,
    establecerSesion,
    cerrarSesion,
    actualizarUsuario
  }
})
