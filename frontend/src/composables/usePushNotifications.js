/**
 * Composable para registrar el dispositivo y recibir notificaciones push (FCM).
 * Solo se ejecuta en app nativa (Capacitor); en web no hace nada.
 */

import { Capacitor } from '@capacitor/core'
import { PushNotifications } from '@capacitor/push-notifications'
import { useApi } from './useApi'

export function usePushNotifications() {
  const { post } = useApi()

  /**
   * Inicializa push: configura listeners (envío de token al backend, opcionalmente
   * al tocar notificación) y solicita permiso + registro.
   * Llamar una vez al entrar en la app con usuario autenticado (ej. en layout).
   * @param {Object} opciones
   * @param {Function} [opciones.alAbrirNotificacion] - (payload, result) => {} para navegar al tocar (ej. a chat)
   */
  async function inicializarPush(opciones = {}) {
    if (!Capacitor.isNativePlatform()) {
      return
    }

    const { alAbrirNotificacion } = opciones

    const handRegistration = async (ev) => {
      const token = ev.token
      if (!token) return
      try {
        await post('/dispositivos', {
          fcm_token: token,
          plataforma: Capacitor.getPlatform(),
        })
      } catch (err) {
        console.warn('Error enviando token FCM al backend:', err)
      }
    }

    PushNotifications.addListener('registration', handRegistration)

    if (alAbrirNotificacion) {
      PushNotifications.addListener('pushNotificationActionPerformed', (ev) => {
        const data = ev.notification?.data || {}
        alAbrirNotificacion(data, ev)
      })
    }

    try {
      let permiso = await PushNotifications.checkPermissions()
      if (permiso.receive !== 'granted') {
        permiso = await PushNotifications.requestPermissions()
        if (permiso.receive !== 'granted') {
          return
        }
      }
      await PushNotifications.register()
    } catch (err) {
      console.warn('Error registrando push:', err)
    }
  }

  return { inicializarPush }
}
