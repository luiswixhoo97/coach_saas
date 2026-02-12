<script setup>
/**
 * ChatArchivo - Componente para mostrar archivos adjuntos
 * Soporta imágenes (preview) y documentos (descarga)
 */

import { computed } from 'vue'

const props = defineProps({
  archivo: {
    type: Object,
    required: true
  },
  esPropio: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['descargar'])

const esImagen = computed(() => props.archivo.es_imagen || props.archivo.tipo === 'imagen')
const esDocumento = computed(() => props.archivo.es_documento || props.archivo.tipo === 'documento')

function handleDescargar() {
  emit('descargar', props.archivo)
}

function obtenerIconoDocumento() {
  const extension = props.archivo.nombre_original?.split('.').pop()?.toLowerCase() || ''
  if (extension === 'pdf') return '📄'
  if (['doc', 'docx'].includes(extension)) return '📝'
  return '📎'
}
</script>

<template>
  <div
    :class="[
      'chat-archivo',
      {
        'chat-archivo--imagen': esImagen,
        'chat-archivo--documento': esDocumento
      }
    ]"
  >
    <!-- Imagen con preview -->
    <div v-if="esImagen" class="chat-archivo__imagen">
      <img
        :src="archivo.url"
        :alt="archivo.nombre_original"
        class="chat-archivo__imagen-img"
        loading="lazy"
      />
    </div>

    <!-- Documento con icono y descarga -->
    <button
      v-else-if="esDocumento"
      @click="handleDescargar"
      class="chat-archivo__documento"
    >
      <div class="chat-archivo__documento-icono">
        {{ obtenerIconoDocumento() }}
      </div>
      <div class="chat-archivo__documento-info">
        <span class="chat-archivo__documento-nombre">
          {{ archivo.nombre_original }}
        </span>
        <span class="chat-archivo__documento-tamaño">
          {{ archivo.tamaño_formateado || archivo.tamaño }}
        </span>
      </div>
      <svg
        class="chat-archivo__documento-descarga"
        width="20"
        height="20"
        viewBox="0 0 20 20"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
      >
        <path
          d="M10 12.5V2.5M10 12.5L6.25 8.75M10 12.5L13.75 8.75M2.5 15V16.25C2.5 17.0784 3.17157 17.75 4 17.75H16C16.8284 17.75 17.5 17.0784 17.5 16.25V15"
          stroke="currentColor"
          stroke-width="1.5"
          stroke-linecap="round"
          stroke-linejoin="round"
        />
      </svg>
    </button>
  </div>
</template>

<style scoped>
.chat-archivo {
  display: flex;
  flex-direction: column;
}

.chat-archivo__imagen {
  border-radius: 12px;
  overflow: hidden;
  max-width: 250px;
  cursor: pointer;
  transition: transform 0.2s ease;
}

.chat-archivo__imagen:hover {
  transform: scale(1.02);
}

.chat-archivo__imagen-img {
  width: 100%;
  height: auto;
  display: block;
  object-fit: cover;
}

.chat-archivo__documento {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
  text-align: left;
  width: 100%;
}

.chat-archivo__documento:hover {
  background: rgba(255, 255, 255, 0.1);
  border-color: rgba(255, 255, 255, 0.2);
}

.chat-archivo__documento-icono {
  font-size: 1.5rem;
  flex-shrink: 0;
}

.chat-archivo__documento-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
  min-width: 0;
}

.chat-archivo__documento-nombre {
  font-size: 0.8125rem;
  font-weight: 500;
  color: #fff;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.chat-archivo__documento-tamaño {
  font-size: 0.75rem;
  color: rgba(255, 255, 255, 0.6);
}

.chat-archivo__documento-descarga {
  flex-shrink: 0;
  color: rgba(255, 255, 255, 0.6);
  transition: color 0.2s ease;
}

.chat-archivo__documento:hover .chat-archivo__documento-descarga {
  color: #00D261;
}
</style>




