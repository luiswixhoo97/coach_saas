import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useUIStore = defineStore('ui', () => {
  // Estado
  const cargandoGlobal = ref(false)
  const menuAbierto = ref(false)
  const modalActivo = ref(null)
  const notificacion = ref(null)

  // Acciones - Loading
  function mostrarCargando() {
    cargandoGlobal.value = true
  }

  function ocultarCargando() {
    cargandoGlobal.value = false
  }

  // Acciones - Menu
  function abrirMenu() {
    menuAbierto.value = true
  }

  function cerrarMenu() {
    menuAbierto.value = false
  }

  function toggleMenu() {
    menuAbierto.value = !menuAbierto.value
  }

  // Acciones - Modal
  function abrirModal(nombreModal) {
    modalActivo.value = nombreModal
  }

  function cerrarModal() {
    modalActivo.value = null
  }

  // Acciones - Notificaciones
  function mostrarNotificacion(mensaje, tipo = 'info', duracion = 3000) {
    notificacion.value = { mensaje, tipo }
    
    if (duracion > 0) {
      setTimeout(() => {
        notificacion.value = null
      }, duracion)
    }
  }

  function mostrarExito(mensaje) {
    mostrarNotificacion(mensaje, 'success')
  }

  function mostrarError(mensaje) {
    mostrarNotificacion(mensaje, 'error', 5000)
  }

  function mostrarAdvertencia(mensaje) {
    mostrarNotificacion(mensaje, 'warning')
  }

  function cerrarNotificacion() {
    notificacion.value = null
  }

  return {
    // Estado
    cargandoGlobal,
    menuAbierto,
    modalActivo,
    notificacion,
    // Acciones Loading
    mostrarCargando,
    ocultarCargando,
    // Acciones Menu
    abrirMenu,
    cerrarMenu,
    toggleMenu,
    // Acciones Modal
    abrirModal,
    cerrarModal,
    // Acciones Notificaciones
    mostrarNotificacion,
    mostrarExito,
    mostrarError,
    mostrarAdvertencia,
    cerrarNotificacion
  }
})
