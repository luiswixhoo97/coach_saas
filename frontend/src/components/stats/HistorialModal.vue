<template>
  <Teleport to="body">
    <div class="modal-overlay" @click.self="$emit('close')">
      <div class="modal">
        <!-- Header -->
        <div class="modal__header">
          <div class="modal__title-group">
            <h2 class="modal__title">{{ parametro.nombre }}</h2>
            <span class="modal__subtitle" v-if="parametro.unidad">
              Unidad: {{ parametro.unidad }}
            </span>
          </div>
          <button class="modal__close" @click="$emit('close')" aria-label="Cerrar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 6L6 18M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Current value highlight -->
        <div class="modal__current" v-if="valorActual">
          <span class="modal__current-label">Valor actual</span>
          <span class="modal__current-value">
            {{ valorActual.valor }}
            <small v-if="parametro.unidad">{{ parametro.unidad }}</small>
          </span>
          <span class="modal__current-date">{{ formatDate(valorActual.fecha) }}</span>
        </div>

        <!-- History list -->
        <div class="modal__content">
          <h3 class="modal__section-title">Historial</h3>
          
          <div class="modal__empty" v-if="!historial?.length">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p>No hay registros históricos</p>
          </div>

          <ul class="modal__list" v-else>
            <li 
              v-for="(item, index) in historialOrdenado" 
              :key="index"
              class="modal__item"
              :class="{ 'modal__item--first': index === 0 }"
            >
              <div class="modal__item-date">
                <span class="modal__item-day">{{ formatDay(item.fecha) }}</span>
                <span class="modal__item-month">{{ formatMonth(item.fecha) }}</span>
              </div>
              <div class="modal__item-line">
                <span class="modal__item-dot" :class="getDotClass(item, index)"></span>
                <span class="modal__item-connector" v-if="index < historialOrdenado.length - 1"></span>
              </div>
              <div class="modal__item-content">
                <span class="modal__item-value">
                  {{ item.valor }}
                  <small v-if="parametro.unidad">{{ parametro.unidad }}</small>
                </span>
                <span class="modal__item-change" :class="changeClass(item, index)" v-if="index > 0">
                  {{ getChange(item, index) }}
                </span>
              </div>
            </li>
          </ul>
        </div>

        <!-- Footer -->
        <div class="modal__footer">
          <button class="modal__btn" @click="$emit('close')">
            Cerrar
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  parametro: {
    type: Object,
    required: true
    // { nombre, unidad, historial: [{ fecha, valor }] }
  }
})

defineEmits(['close'])

const historial = computed(() => props.parametro?.historial || [])

const historialOrdenado = computed(() => {
  return [...historial.value].sort((a, b) => new Date(b.fecha) - new Date(a.fecha))
})

const valorActual = computed(() => {
  if (!historialOrdenado.value.length) return null
  return historialOrdenado.value[0]
})

function formatDate(fecha) {
  if (!fecha) return ''
  const d = new Date(fecha)
  return d.toLocaleDateString('es-MX', { 
    day: 'numeric', 
    month: 'long', 
    year: 'numeric' 
  })
}

function formatDay(fecha) {
  if (!fecha) return ''
  const d = new Date(fecha)
  return d.getDate()
}

function formatMonth(fecha) {
  if (!fecha) return ''
  const d = new Date(fecha)
  return d.toLocaleDateString('es-MX', { month: 'short' }).toUpperCase()
}

function getChange(item, index) {
  if (index === 0 || !historialOrdenado.value[index - 1]) return ''
  const prev = Number(historialOrdenado.value[index - 1].valor)
  const curr = Number(item.valor)
  const diff = prev - curr
  if (diff === 0) return '='
  const sign = diff > 0 ? '-' : '+'
  return `${sign}${Math.abs(diff).toFixed(1)}`
}

function changeClass(item, index) {
  if (index === 0) return ''
  const prev = Number(historialOrdenado.value[index - 1].valor)
  const curr = Number(item.valor)
  const diff = prev - curr
  // For weight/fat: lower is usually better
  // For IMC: closer to normal range is better
  if (diff > 0) return 'modal__item-change--positive'
  if (diff < 0) return 'modal__item-change--negative'
  return ''
}

function getDotClass(item, index) {
  if (index === 0) return 'modal__item-dot--current'
  return ''
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.75);
  display: flex;
  align-items: flex-end;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
  animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal {
  background: #161616;
  border-radius: 20px 20px 0 0;
  width: 100%;
  max-width: 480px;
  max-height: 85vh;
  display: flex;
  flex-direction: column;
  animation: slideUp 0.3s ease;
}

@keyframes slideUp {
  from { 
    transform: translateY(100%);
    opacity: 0;
  }
  to { 
    transform: translateY(0);
    opacity: 1;
  }
}

@media (min-width: 640px) {
  .modal-overlay {
    align-items: center;
  }
  
  .modal {
    border-radius: 20px;
    max-height: 70vh;
  }
}

.modal__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  padding: 1.25rem 1.25rem 0;
  gap: 1rem;
}

.modal__title-group {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.modal__title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #fff;
  margin: 0;
}

.modal__subtitle {
  font-size: 0.75rem;
  color: #697586;
}

.modal__close {
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
  transition: background 0.2s, color 0.2s;
  flex-shrink: 0;
}

.modal__close:hover {
  background: #333;
  color: #fff;
}

.modal__close svg {
  width: 18px;
  height: 18px;
}

.modal__current {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 1.5rem 1.25rem;
  margin: 1rem 1.25rem 0;
  background: linear-gradient(135deg, #1e3a2f 0%, #1a1a1a 100%);
  border-radius: 16px;
  border: 1px solid rgba(0, 210, 97, 0.2);
}

.modal__current-label {
  font-size: 0.75rem;
  font-weight: 500;
  color: #697586;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.modal__current-value {
  font-size: 2.5rem;
  font-weight: 700;
  color: #00D261;
  line-height: 1.2;
  margin-top: 0.25rem;
}

.modal__current-value small {
  font-size: 1rem;
  font-weight: 500;
  color: #697586;
  margin-left: 0.25rem;
}

.modal__current-date {
  font-size: 0.8125rem;
  color: #697586;
  margin-top: 0.25rem;
}

.modal__content {
  flex: 1;
  overflow-y: auto;
  padding: 1.25rem;
}

.modal__section-title {
  font-size: 0.875rem;
  font-weight: 600;
  color: #fff;
  margin: 0 0 1rem;
}

.modal__empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  color: #697586;
  text-align: center;
}

.modal__empty svg {
  width: 48px;
  height: 48px;
  margin-bottom: 0.75rem;
  opacity: 0.5;
}

.modal__empty p {
  margin: 0;
  font-size: 0.875rem;
}

.modal__list {
  list-style: none;
  margin: 0;
  padding: 0;
}

.modal__item {
  display: grid;
  grid-template-columns: 50px 24px 1fr;
  gap: 0.75rem;
  align-items: flex-start;
  padding: 0.5rem 0;
}

.modal__item-date {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  text-align: right;
}

.modal__item-day {
  font-size: 1.125rem;
  font-weight: 600;
  color: #fff;
  line-height: 1;
}

.modal__item-month {
  font-size: 0.6875rem;
  font-weight: 500;
  color: #697586;
  text-transform: uppercase;
  margin-top: 0.125rem;
}

.modal__item-line {
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  padding-top: 0.25rem;
}

.modal__item-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #2a2a2a;
  border: 2px solid #444;
  z-index: 1;
}

.modal__item-dot--current {
  background: #00D261;
  border-color: #00D261;
  box-shadow: 0 0 8px rgba(0, 210, 97, 0.4);
}

.modal__item-connector {
  position: absolute;
  top: 14px;
  width: 2px;
  height: calc(100% + 0.5rem);
  background: #2a2a2a;
}

.modal__item-content {
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
}

.modal__item-value {
  font-size: 1rem;
  font-weight: 600;
  color: #fff;
}

.modal__item-value small {
  font-size: 0.75rem;
  font-weight: 400;
  color: #697586;
  margin-left: 0.25rem;
}

.modal__item-change {
  font-size: 0.75rem;
  font-weight: 500;
  color: #697586;
}

.modal__item-change--positive {
  color: #00D261;
}

.modal__item-change--negative {
  color: #EF5C5C;
}

.modal__footer {
  padding: 1rem 1.25rem 1.5rem;
  border-top: 1px solid #2a2a2a;
}

.modal__btn {
  width: 100%;
  padding: 0.875rem 1.5rem;
  background: #2a2a2a;
  color: #fff;
  border: none;
  border-radius: 12px;
  font-size: 0.9375rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.modal__btn:hover {
  background: #333;
}

.modal__btn:active {
  background: #222;
}
</style>
