<script setup>
/**
 * StatCard - Tarjeta de estadística con progreso circular
 * Para índice de grasa, índice de masa (IMC), peso.
 * Al hacer click abre el modal de histórico.
 */

const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  value: {
    type: [Number, String],
    required: true,
  },
  unit: {
    type: String,
    default: '',
  },
  /** Texto bajo el valor, ej. "50% objetivo" */
  goalText: {
    type: String,
    default: '',
  },
  /** Porcentaje del círculo (0-100) */
  percentage: {
    type: Number,
    default: 0,
    validator: (v) => v >= 0 && v <= 100,
  },
  /** Tipo para icono: grasa | masa | peso */
  type: {
    type: String,
    default: 'peso',
    validator: (v) => ['grasa', 'masa', 'peso'].includes(v),
  },
})

const emit = defineEmits(['click'])

const displayValue = () => {
  if (typeof props.value === 'number' && Number.isInteger(props.value) === false) {
    return props.value.toFixed(1)
  }
  return props.value
}

const strokeDash = () => {
  const p = Math.min(100, Math.max(0, props.percentage))
  const circumference = 2 * Math.PI * 17
  const filled = (p / 100) * circumference
  return `${filled} ${circumference}`
}
</script>

<template>
  <article
    class="stat-card"
    role="button"
    tabindex="0"
    aria-label="Ver histórico de {{ title }}"
    @click="$emit('click')"
    @keydown.enter="$emit('click')"
  >
    <header class="stat-card__header">
      <span class="stat-card__icon" :class="`stat-card__icon--${type}`" aria-hidden="true">
        <svg v-if="type === 'grasa'" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <circle cx="12" cy="12" r="10"/>
          <path d="M12 6v4l3 3"/>
          <path d="M8 16l4-4 4 4"/>
        </svg>
        <svg v-else-if="type === 'masa'" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M12 3v18M3 12h18"/>
          <circle cx="12" cy="12" r="9"/>
        </svg>
        <svg v-else width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M12 2v20M8 6l4-4 4 4v12"/>
          <path d="M6 12h12"/>
        </svg>
      </span>
      <h3 class="stat-card__title">{{ title }}</h3>
    </header>

    <div class="stat-card__progress">
      <svg viewBox="0 0 40 40" class="stat-card__svg">
        <circle
          cx="20"
          cy="20"
          r="17"
          fill="none"
          stroke="var(--white-10)"
          stroke-width="3"
        />
        <circle
          cx="20"
          cy="20"
          r="17"
          fill="none"
          stroke="var(--brand)"
          stroke-width="3"
          stroke-linecap="round"
          :stroke-dasharray="strokeDash()"
          transform="rotate(-90 20 20)"
        />
      </svg>
      <div class="stat-card__center">
        <span class="stat-card__value">{{ displayValue() }}{{ unit }}</span>
        <span v-if="goalText" class="stat-card__goal">{{ goalText }}</span>
      </div>
    </div>
  </article>
</template>

<style scoped>
.stat-card {
  display: flex;
  flex-direction: column;
  gap: 12px;
  width: 100%;
  padding: 14px;
  border-radius: 12px;
  background: var(--card-color);
  border: 1px solid var(--card-color);
  cursor: pointer;
  transition: transform 0.2s ease, border-color 0.2s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
  border-color: var(--brand-30);
}

.stat-card:focus-visible {
  outline: none;
  border-color: var(--brand);
  box-shadow: 0 0 0 2px var(--brand-30);
}

.stat-card__header {
  display: flex;
  align-items: center;
  gap: 8px;
}

.stat-card__icon {
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--brand);
}

.stat-card__icon svg {
  width: 22px;
  height: 22px;
}

.stat-card__title {
  font-family: var(--poppins-14-medium-font-family);
  font-weight: var(--poppins-14-medium-font-weight);
  font-size: var(--poppins-14-medium-font-size);
  line-height: var(--poppins-14-medium-line-height);
  color: var(--white);
  margin: 0;
}

.stat-card__progress {
  position: relative;
  width: 140px;
  height: 140px;
  margin: 0 auto;
  flex-shrink: 0;
}

.stat-card__svg {
  width: 100%;
  height: 100%;
}

.stat-card__center {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 2px;
  width: 100%;
  text-align: center;
  pointer-events: none;
}

.stat-card__value {
  font-family: var(--poppins-16-medium-font-family);
  font-weight: var(--poppins-16-medium-font-weight);
  font-size: var(--poppins-16-medium-font-size);
  line-height: var(--poppins-16-medium-line-height);
  color: var(--white);
  display: block;
  text-align: center;
}

.stat-card__goal {
  font-family: var(--poppins-12-regular-font-family);
  font-weight: var(--poppins-12-regular-font-weight);
  font-size: var(--poppins-12-regular-font-size);
  line-height: var(--poppins-12-regular-line-height);
  color: var(--white-80);
  display: block;
  text-align: center;
}
</style>
