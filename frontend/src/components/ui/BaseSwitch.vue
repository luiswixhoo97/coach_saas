<script setup>
/**
 * BaseSwitch - Switch/Toggle reutilizable
 * 
 * Props:
 * - modelValue: boolean (valor del switch)
 * - disabled: boolean
 * - label: string (opcional, etiqueta)
 * - loading: boolean (opcional, muestra estado de carga)
 */

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  },
  loading: {
    type: Boolean,
    default: false
  },
  label: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:modelValue'])

function toggle() {
  if (!props.disabled && !props.loading) {
    emit('update:modelValue', !props.modelValue)
  }
}
</script>

<template>
  <div class="base-switch">
    <label 
      v-if="label" 
      class="base-switch__label"
    >
      {{ label }}
    </label>
    <button
      type="button"
      role="switch"
      :aria-checked="modelValue"
      :disabled="disabled"
      :class="[
        'base-switch__toggle',
        {
          'base-switch__toggle--active': modelValue,
          'base-switch__toggle--disabled': disabled || loading
        }
      ]"
      @click="toggle"
    >
      <span class="base-switch__thumb"></span>
    </button>
  </div>
</template>

<style scoped>
.base-switch {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.base-switch__label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #fff;
  cursor: pointer;
  user-select: none;
}

.base-switch__toggle {
  position: relative;
  width: 44px;
  height: 24px;
  border-radius: 12px;
  border: none;
  background: rgba(105, 117, 134, 0.3);
  cursor: pointer;
  transition: background 0.2s ease;
  padding: 2px;
  outline: none;
}

.base-switch__toggle:focus-visible {
  box-shadow: 0 0 0 2px rgba(0, 210, 97, 0.3);
}

.base-switch__toggle--active {
  background: rgba(0, 210, 97, 0.3);
}

.base-switch__toggle--active .base-switch__thumb {
  transform: translateX(20px);
  background: #00D261;
}

.base-switch__toggle--disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.base-switch__toggle--disabled .base-switch__thumb {
  opacity: 0.7;
}

.base-switch__thumb {
  display: block;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: #697586;
  transition: transform 0.2s ease, background 0.2s ease;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}
</style>

