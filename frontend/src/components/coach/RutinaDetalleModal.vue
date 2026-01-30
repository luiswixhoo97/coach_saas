<script setup>
/**
 * RutinaDetalleModal - Detalle de la rutina con ejercicios por bloque.
 * Mismo patrón que ClienteDetalleModal: bottom sheet en móvil, centrado en desktop.
 * Muestra nombre, nivel, objetivo y ejercicios agrupados por bloque (nombre, series, repeticiones, descanso).
 */
import { computed } from 'vue'

const props = defineProps({
  rutina: {
    type: Object,
    default: null
  }
})

defineEmits(['close'])

const NIVELES = [
  { value: 'principiante', label: 'Principiante' },
  { value: 'intermedio', label: 'Intermedio' },
  { value: 'avanzado', label: 'Avanzado' }
]

function labelNivel(value) {
  const n = NIVELES.find(x => x.value === value)
  return n ? n.label : (value || '—')
}

/** Ejercicios agrupados por bloque */
const ejerciciosPorBloque = computed(() => {
  const ejercicios = props.rutina?.ejercicios
  if (!ejercicios?.length) return []
  const porBloque = {}
  for (const e of ejercicios) {
    const b = e.bloque ?? 1
    if (!porBloque[b]) porBloque[b] = []
    porBloque[b].push(e)
  }
  return Object.keys(porBloque)
    .sort((a, b) => Number(a) - Number(b))
    .map(bloque => ({ bloque: Number(bloque), ejercicios: porBloque[bloque] }))
})
</script>

<template>
  <Teleport to="body">
    <div class="rutina-modal__overlay" @click.self="$emit('close')">
      <div class="rutina-modal">
        <!-- Header -->
        <div class="rutina-modal__header">
          <h2 class="rutina-modal__title">
            {{ rutina && !rutina.error ? (rutina.nombre || 'Rutina') : 'Detalle de la rutina' }}
          </h2>
          <button
            type="button"
            class="rutina-modal__close"
            aria-label="Cerrar"
            @click="$emit('close')"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 6L6 18M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Loading -->
        <div v-if="!rutina" class="rutina-modal__loading">
          <div class="rutina-modal__skeleton" />
          <div class="rutina-modal__skeleton" />
          <div class="rutina-modal__skeleton" />
        </div>

        <!-- Error -->
        <div v-else-if="rutina.error" class="rutina-modal__body">
          <p class="rutina-modal__error">{{ rutina.error }}</p>
        </div>

        <!-- Contenido -->
        <div v-else class="rutina-modal__body">
          <div class="rutina-modal__row">
            <span class="rutina-modal__label">Nivel</span>
            <span class="rutina-modal__value">{{ labelNivel(rutina.nivel) }}</span>
          </div>
          <div class="rutina-modal__row">
            <span class="rutina-modal__label">Objetivo</span>
            <span class="rutina-modal__value">{{ rutina.objetivo || '—' }}</span>
          </div>

          <div v-if="!rutina.ejercicios?.length" class="rutina-modal__empty">
            Aún no hay ejercicios en esta rutina.
          </div>
          <template v-else>
            <div
              v-for="grupo in ejerciciosPorBloque"
              :key="grupo.bloque"
              class="rutina-modal__bloque"
            >
              <h4 class="rutina-modal__bloque-title">Bloque {{ grupo.bloque }}</h4>
              <div class="rutina-modal__ejercicios">
                <div
                  v-for="e in grupo.ejercicios"
                  :key="e.id"
                  class="rutina-modal__ejercicio-row"
                >
                  <span class="rutina-modal__ejercicio-nombre">{{ e.nombre || '—' }}</span>
                  <span class="rutina-modal__ejercicio-datos">
                    {{ e.series ?? '—' }} series × {{ e.repeticiones ?? '—' }} rep · {{ e.descanso_segundos ?? 0 }} s descanso
                  </span>
                </div>
              </div>
            </div>
          </template>
        </div>

        <div class="rutina-modal__footer">
          <button type="button" class="rutina-modal__btn" @click="$emit('close')">
            Cerrar
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.rutina-modal__overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.75);
  display: flex;
  align-items: flex-end;
  justify-content: center;
  z-index: 1000;
  animation: rutina-modal-fade 0.2s ease;
}

@keyframes rutina-modal-fade {
  from { opacity: 0; }
  to { opacity: 1; }
}

.rutina-modal {
  background: #161616;
  border-radius: 20px 20px 0 0;
  width: 100%;
  max-width: 480px;
  max-height: 85vh;
  display: flex;
  flex-direction: column;
  animation: rutina-modal-slide 0.3s ease;
}

@keyframes rutina-modal-slide {
  from { transform: translateY(100%); }
  to { transform: translateY(0); }
}

@media (min-width: 640px) {
  .rutina-modal__overlay {
    align-items: center;
  }
  .rutina-modal {
    border-radius: 20px;
    max-height: 70vh;
  }
}

.rutina-modal__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.25rem 1.25rem 0;
}

.rutina-modal__title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #fff;
  margin: 0;
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
}

.rutina-modal__close {
  background: #2a2a2a;
  border: none;
  border-radius: 50%;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #697586;
  flex-shrink: 0;
  transition: background 0.2s, color 0.2s;
}

.rutina-modal__close:hover {
  background: #333;
  color: #fff;
}

.rutina-modal__close svg {
  width: 18px;
  height: 18px;
}

.rutina-modal__loading {
  padding: 1rem 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.rutina-modal__skeleton {
  height: 2.5rem;
  background: #252525;
  border-radius: 12px;
  animation: rutina-modal-pulse 1.5s ease-in-out infinite;
}

@keyframes rutina-modal-pulse {
  0%, 100% { opacity: 0.5; }
  50% { opacity: 1; }
}

.rutina-modal__body {
  padding: 1rem 1.25rem;
  overflow-y: auto;
  flex: 1;
}

.rutina-modal__error {
  font-size: 0.875rem;
  color: #EF5C5C;
  margin: 0;
  padding: 1rem 0;
}

.rutina-modal__row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.75rem 0;
  border-bottom: 1px solid #252525;
}

.rutina-modal__row:last-of-type {
  border-bottom: none;
}

.rutina-modal__label {
  font-size: 0.8125rem;
  color: #697586;
  flex-shrink: 0;
}

.rutina-modal__value {
  font-size: 0.875rem;
  font-weight: 500;
  color: #fff;
  text-align: right;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
}

.rutina-modal__empty {
  font-size: 0.875rem;
  color: #697586;
  padding: 1rem 0;
}

/* Bloques y ejercicios */
.rutina-modal__bloque {
  margin-top: 1.25rem;
  padding-top: 1rem;
  border-top: 1px solid #252525;
}

.rutina-modal__bloque:first-of-type {
  margin-top: 0.5rem;
  padding-top: 0;
  border-top: none;
}

.rutina-modal__bloque-title {
  font-size: 0.75rem;
  font-weight: 600;
  color: #00D261;
  margin: 0 0 0.5rem;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.rutina-modal__ejercicios {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.rutina-modal__ejercicio-row {
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
  padding: 0.625rem 0.75rem;
  background: #1e1e1e;
  border-radius: 10px;
  border: 1px solid #252525;
}

.rutina-modal__ejercicio-nombre {
  font-size: 0.9375rem;
  font-weight: 500;
  color: #fff;
}

.rutina-modal__ejercicio-datos {
  font-size: 0.75rem;
  color: #697586;
}

.rutina-modal__footer {
  padding: 1rem 1.25rem;
  padding-bottom: max(1rem, env(safe-area-inset-bottom));
  border-top: 1px solid #252525;
}

.rutina-modal__btn {
  width: 100%;
  padding: 0.875rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #00D261;
  background: transparent;
  border: 1px solid rgba(0, 210, 97, 0.4);
  border-radius: 12px;
  cursor: pointer;
  transition: background 0.2s, border-color 0.2s;
}

.rutina-modal__btn:hover {
  background: rgba(0, 210, 97, 0.1);
  border-color: #00D261;
}
</style>
