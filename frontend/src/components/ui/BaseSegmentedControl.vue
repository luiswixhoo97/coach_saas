<script setup>
/**
 * BaseSegmentedControl - Control segmentado tipo toggle (Rent/Buy)
 * Opciones: array de { value, label }. v-model = valor seleccionado.
 */

const props = defineProps({
  options: {
    type: Array,
    required: true
    // [{ value: string, label: string }]
  },
  modelValue: {
    type: [String, Number],
    default: null
  }
})

const emit = defineEmits(['update:modelValue'])

function select(value) {
  emit('update:modelValue', value)
}
</script>

<template>
  <div class="segmented-control" role="tablist">
    <button
      v-for="opt in options"
      :key="opt.value"
      type="button"
      role="tab"
      :aria-selected="modelValue === opt.value"
      :class="[
        'segmented-control__segment',
        { 'segmented-control__segment--active': modelValue === opt.value }
      ]"
      @click="select(opt.value)"
    >
      {{ opt.label }}
    </button>
  </div>
</template>

<style scoped>
.segmented-control {
  display: flex;
  background: #1e1e1e;
  border-radius: 12px;
  padding: 0.25rem;
  gap: 0;
}

.segmented-control__segment {
  flex: 1;
  padding: 0.625rem 1rem;
  border: none;
  border-radius: 10px;
  font-size: 0.875rem;
  font-weight: 500;
  color: #a0a0a0;
  background: transparent;
  cursor: pointer;
  transition: background 0.2s ease, color 0.2s ease;
  font-family: inherit;
}

.segmented-control__segment:hover:not(.segmented-control__segment--active) {
  color: #fff;
  background: rgba(255, 255, 255, 0.05);
}

.segmented-control__segment--active {
  background: rgba(0, 210, 97, 0.15);
  color: #00D261;
}
</style>
