<script setup>
/**
 * ChatMensaje - Componente individual de mensaje
 * Muestra texto, emojis y archivos adjuntos
 */

import { computed } from 'vue'
import BaseAvatar from '@/components/ui/BaseAvatar.vue'
import ChatArchivo from './ChatArchivo.vue'

const props = defineProps({
  mensaje: {
    type: Object,
    required: true
  },
  esPropio: {
    type: Boolean,
    default: false
  }
})

const fechaFormateada = computed(() => {
  if (!props.mensaje.enviado_en) return ''
  const fecha = new Date(props.mensaje.enviado_en)
  const ahora = new Date()
  const diffMs = ahora - fecha
  const diffMins = Math.floor(diffMs / 60000)
  const diffHoras = Math.floor(diffMs / 3600000)
  const diffDias = Math.floor(diffMs / 86400000)

  if (diffMins < 1) return 'Ahora'
  if (diffMins < 60) return `Hace ${diffMins}m`
  if (diffHoras < 24) return `Hace ${diffHoras}h`
  if (diffDias < 7) return `Hace ${diffDias}d`
  
  return fecha.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' })
})

const nombreEmisor = computed(() => {
  if (props.esPropio) return 'Tú'
  return props.mensaje.emisor_tipo === 'coach' ? 'Coach' : 'Cliente'
})

const emit = defineEmits(['archivo-descargar'])

function handleDescargarArchivo(archivo) {
  emit('archivo-descargar', archivo)
}
</script>

<template>
  <div
    :class="[
      'chat-mensaje',
      {
        'chat-mensaje--propio': esPropio,
        'chat-mensaje--recibido': !esPropio
      }
    ]"
  >
    <!-- Avatar (solo en mensajes recibidos) -->
    <BaseAvatar
      v-if="!esPropio"
      :nombre="nombreEmisor"
      :size="'sm'"
      class="chat-mensaje__avatar"
    />

    <!-- Contenedor del mensaje -->
    <div class="chat-mensaje__contenido">
      <!-- Header con nombre y fecha (solo en mensajes recibidos) -->
      <div v-if="!esPropio" class="chat-mensaje__header">
        <span class="chat-mensaje__nombre">{{ nombreEmisor }}</span>
        <span class="chat-mensaje__fecha">{{ fechaFormateada }}</span>
      </div>

      <!-- Burbuja del mensaje -->
      <div
        :class="[
          'chat-mensaje__burbuja',
          {
            'chat-mensaje__burbuja--propio': esPropio,
            'chat-mensaje__burbuja--recibido': !esPropio
          }
        ]"
      >
        <!-- Texto del mensaje -->
        <p v-if="mensaje.mensaje" class="chat-mensaje__texto">
          {{ mensaje.mensaje }}
        </p>

        <!-- Archivos adjuntos -->
        <div v-if="mensaje.archivos && mensaje.archivos.length > 0" class="chat-mensaje__archivos">
          <ChatArchivo
            v-for="archivo in mensaje.archivos"
            :key="archivo.id"
            :archivo="archivo"
            :es-propio="esPropio"
            @descargar="handleDescargarArchivo"
          />
        </div>
      </div>

      <!-- Fecha en mensajes propios -->
      <div v-if="esPropio" class="chat-mensaje__fecha-propio">
        {{ fechaFormateada }}
        <svg
          v-if="mensaje.leido"
          class="chat-mensaje__check"
          width="16"
          height="16"
          viewBox="0 0 16 16"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
        >
          <path
            d="M13.3333 4L6 11.3333L2.66667 8"
            stroke="#00D261"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </svg>
      </div>
    </div>
  </div>
</template>

<style scoped>
.chat-mensaje {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1rem;
  align-items: flex-end;
}

.chat-mensaje--propio {
  flex-direction: row-reverse;
}

.chat-mensaje__avatar {
  flex-shrink: 0;
}

.chat-mensaje__contenido {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  max-width: 75%;
}

.chat-mensaje--propio .chat-mensaje__contenido {
  align-items: flex-end;
}

.chat-mensaje__header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.125rem;
}

.chat-mensaje__nombre {
  font-size: 0.6875rem;
  font-weight: 500;
  color: #a0a0a0;
}

.chat-mensaje__fecha {
  font-size: 0.625rem;
  color: #697586;
}

.chat-mensaje__burbuja {
  padding: 0.75rem 1rem;
  border-radius: 16px;
  word-wrap: break-word;
  transition: all 0.2s ease;
}

.chat-mensaje__burbuja--propio {
  background: #00D261;
  color: #fff;
  border-bottom-right-radius: 4px;
}

.chat-mensaje__burbuja--recibido {
  background: #1e1e1e;
  color: #fff;
  border-bottom-left-radius: 4px;
}

.chat-mensaje__texto {
  font-size: 0.875rem;
  line-height: 1.5;
  margin: 0;
  white-space: pre-wrap;
}

.chat-mensaje__archivos {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-top: 0.5rem;
}

.chat-mensaje__fecha-propio {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.625rem;
  color: #697586;
  margin-top: 0.125rem;
}

.chat-mensaje__check {
  flex-shrink: 0;
}
</style>

