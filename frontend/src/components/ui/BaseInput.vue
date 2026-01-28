<script setup>
/**
 * BaseInput - Input reutilizable
 * 
 * Props:
 * - modelValue: valor del input
 * - type: 'text' | 'email' | 'password' | 'number' | 'tel'
 * - label: etiqueta del campo
 * - placeholder: placeholder
 * - error: mensaje de error
 * - disabled: boolean
 * - required: boolean
 * - icon: nombre del icono (slot)
 */

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: ''
  },
  type: {
    type: String,
    default: 'text'
  },
  label: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: ''
  },
  error: {
    type: String,
    default: ''
  },
  disabled: {
    type: Boolean,
    default: false
  },
  required: {
    type: Boolean,
    default: false
  },
  autocomplete: {
    type: String,
    default: 'off'
  }
})

const emit = defineEmits(['update:modelValue'])

function handleInput(event) {
  emit('update:modelValue', event.target.value)
}
</script>

<template>
  <div class="w-full">
    <!-- Label -->
    <label 
      v-if="label" 
      class="block text-sm font-medium text-gray-700 mb-1.5"
    >
      {{ label }}
      <span v-if="required" class="text-danger-500 ml-0.5">*</span>
    </label>

    <!-- Input container -->
    <div class="relative">
      <!-- Icono izquierdo -->
      <div 
        v-if="$slots.icon" 
        class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400"
      >
        <slot name="icon" />
      </div>

      <!-- Input -->
      <input
        :type="type"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :required="required"
        :autocomplete="autocomplete"
        :class="[
          // Base
          'block w-full rounded-lg border bg-white text-gray-900 placeholder-gray-400 transition-colors duration-200',
          'focus:outline-none focus:ring-2 focus:ring-offset-0',
          // Padding
          $slots.icon ? 'pl-10 pr-4' : 'px-4',
          'py-3',
          // Estados
          error 
            ? 'border-danger-500 focus:border-danger-500 focus:ring-danger-500/20' 
            : 'border-gray-300 focus:border-primary-500 focus:ring-primary-500/20',
          disabled ? 'bg-gray-100 cursor-not-allowed opacity-60' : ''
        ]"
        @input="handleInput"
      />
    </div>

    <!-- Mensaje de error -->
    <p v-if="error" class="mt-1.5 text-sm text-danger-600">
      {{ error }}
    </p>
  </div>
</template>
