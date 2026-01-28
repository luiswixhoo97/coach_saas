<script setup>
/**
 * BaseButton - Botón reutilizable
 * 
 * Props:
 * - variant: 'primary' | 'secondary' | 'outline' | 'danger' | 'ghost'
 * - size: 'sm' | 'md' | 'lg'
 * - loading: boolean
 * - disabled: boolean
 * - block: boolean (ancho completo)
 * - type: 'button' | 'submit' | 'reset'
 */

const props = defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: (v) => ['primary', 'secondary', 'outline', 'danger', 'ghost'].includes(v)
  },
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['sm', 'md', 'lg'].includes(v)
  },
  loading: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  },
  block: {
    type: Boolean,
    default: false
  },
  type: {
    type: String,
    default: 'button'
  }
})

const emit = defineEmits(['click'])

function handleClick(event) {
  if (!props.loading && !props.disabled) {
    emit('click', event)
  }
}
</script>

<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="[
      // Base
      'inline-flex items-center justify-center font-medium rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2',
      // Tamaños
      {
        'px-3 py-1.5 text-sm gap-1.5': size === 'sm',
        'px-4 py-2.5 text-base gap-2': size === 'md',
        'px-6 py-3 text-lg gap-2.5': size === 'lg',
      },
      // Variantes
      {
        'bg-primary-600 text-white hover:bg-primary-700 focus:ring-primary-500 active:bg-primary-800': variant === 'primary',
        'bg-gray-200 text-gray-900 hover:bg-gray-300 focus:ring-gray-500 active:bg-gray-400': variant === 'secondary',
        'border-2 border-primary-600 text-primary-600 hover:bg-primary-50 focus:ring-primary-500': variant === 'outline',
        'bg-danger-600 text-white hover:bg-danger-700 focus:ring-danger-500 active:bg-danger-800': variant === 'danger',
        'text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:ring-gray-500': variant === 'ghost',
      },
      // Estados
      {
        'opacity-50 cursor-not-allowed': disabled || loading,
        'w-full': block,
      }
    ]"
    @click="handleClick"
  >
    <!-- Spinner de carga -->
    <svg
      v-if="loading"
      class="animate-spin"
      :class="{
        'w-4 h-4': size === 'sm',
        'w-5 h-5': size === 'md',
        'w-6 h-6': size === 'lg',
      }"
      xmlns="http://www.w3.org/2000/svg"
      fill="none"
      viewBox="0 0 24 24"
    >
      <circle
        class="opacity-25"
        cx="12"
        cy="12"
        r="10"
        stroke="currentColor"
        stroke-width="4"
      />
      <path
        class="opacity-75"
        fill="currentColor"
        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
      />
    </svg>

    <!-- Contenido del botón -->
    <slot />
  </button>
</template>
