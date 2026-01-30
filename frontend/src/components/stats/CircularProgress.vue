<template>
  <div class="circular-progress" @click="$emit('click')">
    <div class="circular-progress__chart">
      <svg :viewBox="`0 0 ${size} ${size}`" class="circular-progress__svg">
        <!-- Background circle -->
        <circle
          class="circular-progress__bg"
          :cx="center"
          :cy="center"
          :r="radius"
          fill="none"
          :stroke-width="strokeWidth"
        />
        <!-- Progress circle -->
        <circle
          class="circular-progress__fill"
          :cx="center"
          :cy="center"
          :r="radius"
          fill="none"
          :stroke-width="strokeWidth"
          :stroke-dasharray="circumference"
          :stroke-dashoffset="dashOffset"
          stroke-linecap="round"
        />
      </svg>
      <div class="circular-progress__content">
        <span class="circular-progress__value">{{ formattedValue }}</span>
        <span class="circular-progress__unit" v-if="unidad">{{ unidad }}</span>
      </div>
    </div>
    <div class="circular-progress__info">
      <span class="circular-progress__percentage">{{ porcentaje }}% goal</span>
      <span class="circular-progress__label">{{ label }}</span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  valor: {
    type: [Number, String],
    default: 0
  },
  meta: {
    type: Number,
    default: 100
  },
  unidad: {
    type: String,
    default: ''
  },
  label: {
    type: String,
    required: true
  },
  size: {
    type: Number,
    default: 120
  },
  strokeWidth: {
    type: Number,
    default: 8
  }
})

defineEmits(['click'])

const center = computed(() => props.size / 2)
const radius = computed(() => (props.size - props.strokeWidth) / 2)
const circumference = computed(() => 2 * Math.PI * radius.value)

const porcentaje = computed(() => {
  if (!props.meta || props.meta === 0) return 0
  const pct = Math.round((Number(props.valor) / props.meta) * 100)
  return Math.min(pct, 100)
})

const dashOffset = computed(() => {
  const progress = porcentaje.value / 100
  return circumference.value * (1 - progress)
})

const formattedValue = computed(() => {
  const num = Number(props.valor)
  if (Number.isInteger(num)) return num.toString()
  return num.toFixed(1)
})
</script>

<style scoped>
/* Mobile: compact square cards */
.circular-progress {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.25rem;
  padding: 0.5rem;
  background: #1e1e1e;
  border-radius: 12px;
  cursor: pointer;
  transition: transform 0.2s ease, background 0.2s ease;
  aspect-ratio: 1 / 1;
  min-width: 0;
  overflow: hidden;
}

.circular-progress:hover {
  transform: scale(1.02);
  background: #252525;
}

.circular-progress:active {
  transform: scale(0.98);
}

.circular-progress__chart {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 105px;
  height: 105px;
  flex-shrink: 0;
}

.circular-progress__svg {
  transform: rotate(-90deg);
  width: 100%;
  height: 100%;
}

.circular-progress__bg {
  stroke: #2a2a2a;
}

.circular-progress__fill {
  stroke: #00D261;
  transition: stroke-dashoffset 0.6s ease;
}

.circular-progress__content {
  position: absolute;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.circular-progress__value {
  font-size: 1.3rem;
  font-weight: 700;
  color: #fff;
  line-height: 1;
}

.circular-progress__unit {
  font-size: 0.7rem;
  font-weight: 500;
  color: #697586;
  margin-top: 0;
}

.circular-progress__info {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0;
  text-align: center;
  min-width: 0;
  width: 100%;
  overflow: hidden;
}

.circular-progress__percentage {
  font-size: 0.8rem;
  font-weight: 600;
  color: #00D261;
}

.circular-progress__label {
  font-size: 0.7rem;
  font-weight: 500;
  color: #f3f6f9;
  text-transform: uppercase;
  letter-spacing: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 100%;
}

/* Desktop: original larger size */
@media (min-width: 640px) {
  .circular-progress {
    gap: 0.5rem;
    padding: 1rem;
    border-radius: 16px;
    aspect-ratio: auto;
    overflow: visible;
  }

  .circular-progress__chart {
    width: 100px;
    height: 100px;
  }

  .circular-progress__value {
    font-size: 1.5rem;
  }

  .circular-progress__unit {
    font-size: 0.75rem;
    margin-top: 0.125rem;
  }

  .circular-progress__info {
    gap: 0.125rem;
    overflow: visible;
  }

  .circular-progress__percentage {
    font-size: 0.8125rem;
  }

  .circular-progress__label {
    font-size: 0.75rem;
    white-space: normal;
    overflow: visible;
    text-overflow: clip;
  }
}
</style>
