<script setup>
/**
 * ClienteDetalleModal - Detalle del cliente en móvil (bottom sheet).
 * Muestra nombre, email, estado, edad, altura, objetivo, suscripción y tiene dieta.
 */
const props = defineProps({
  cliente: {
    type: Object,
    default: null
  }
})

defineEmits(['close'])

function nombreCompleto(c) {
  if (!c) return ''
  const partes = [c.nombre, c.apellido_paterno, c.apellido_materno].filter(Boolean)
  return partes.join(' ') || c.email || '—'
}
</script>

<template>
  <Teleport to="body">
    <div class="cliente-modal__overlay" @click.self="$emit('close')">
      <div class="cliente-modal">
        <!-- Header -->
        <div class="cliente-modal__header">
          <h2 class="cliente-modal__title">
            {{ cliente ? nombreCompleto(cliente) : 'Detalle del cliente' }}
          </h2>
          <button
            type="button"
            class="cliente-modal__close"
            aria-label="Cerrar"
            @click="$emit('close')"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 6L6 18M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Loading -->
        <div v-if="!cliente" class="cliente-modal__loading">
          <div class="cliente-modal__skeleton" />
          <div class="cliente-modal__skeleton" />
          <div class="cliente-modal__skeleton" />
        </div>

        <!-- Error -->
        <div v-else-if="cliente.error" class="cliente-modal__body">
          <p class="cliente-modal__error">{{ cliente.error }}</p>
        </div>

        <!-- Contenido -->
        <div v-else class="cliente-modal__body">
          <div class="cliente-modal__row">
            <span class="cliente-modal__label">Email</span>
            <span class="cliente-modal__value">{{ cliente.email || '—' }}</span>
          </div>
          <div class="cliente-modal__row">
            <span class="cliente-modal__label">Estado</span>
            <span
              class="cliente-modal__badge"
              :class="{ 'cliente-modal__badge--active': cliente.activo }"
            >
              {{ cliente.activo ? 'Activo' : 'Inactivo' }}
            </span>
          </div>
          <div class="cliente-modal__row" v-if="cliente.edad != null">
            <span class="cliente-modal__label">Edad</span>
            <span class="cliente-modal__value">{{ cliente.edad }} años</span>
          </div>
          <div class="cliente-modal__row" v-if="cliente.altura">
            <span class="cliente-modal__label">Altura</span>
            <span class="cliente-modal__value">{{ cliente.altura }} cm</span>
          </div>
          <div class="cliente-modal__row" v-if="cliente.objetivo">
            <span class="cliente-modal__label">Objetivo</span>
            <span class="cliente-modal__value">{{ cliente.objetivo }}</span>
          </div>
          <div class="cliente-modal__row" v-if="cliente.suscripcion_activa">
            <span class="cliente-modal__label">Suscripción</span>
            <span class="cliente-modal__value">
              Vence {{ cliente.suscripcion_activa.fecha_fin }}
              <span v-if="cliente.suscripcion_activa.dias_restantes != null" class="cliente-modal__hint">
                ({{ cliente.suscripcion_activa.dias_restantes }} días)
              </span>
            </span>
          </div>
          <div class="cliente-modal__row">
            <span class="cliente-modal__label">Tiene dieta</span>
            <span
              class="cliente-modal__badge"
              :class="{ 'cliente-modal__badge--active': cliente.tiene_dieta }"
            >
              {{ cliente.tiene_dieta ? 'Sí' : 'No' }}
            </span>
          </div>
        </div>

        <div class="cliente-modal__footer">
          <button type="button" class="cliente-modal__btn" @click="$emit('close')">
            Cerrar
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.cliente-modal__overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.75);
  display: flex;
  align-items: flex-end;
  justify-content: center;
  z-index: 1000;
  animation: cliente-modal-fade 0.2s ease;
}

@keyframes cliente-modal-fade {
  from { opacity: 0; }
  to { opacity: 1; }
}

.cliente-modal {
  background: #161616;
  border-radius: 20px 20px 0 0;
  width: 100%;
  max-width: 480px;
  max-height: 85vh;
  display: flex;
  flex-direction: column;
  animation: cliente-modal-slide 0.3s ease;
}

@keyframes cliente-modal-slide {
  from { transform: translateY(100%); }
  to { transform: translateY(0); }
}

@media (min-width: 640px) {
  .cliente-modal__overlay {
    align-items: center;
  }
  .cliente-modal {
    border-radius: 20px;
    max-height: 70vh;
  }
}

.cliente-modal__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.25rem 1.25rem 0;
}

.cliente-modal__title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #fff;
  margin: 0;
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
}

.cliente-modal__error {
  font-size: 0.875rem;
  color: #EF5C5C;
  margin: 0;
  padding: 1rem 0;
}

.cliente-modal__close {
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

.cliente-modal__close:hover {
  background: #333;
  color: #fff;
}

.cliente-modal__close svg {
  width: 18px;
  height: 18px;
}

.cliente-modal__loading {
  padding: 1rem 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.cliente-modal__skeleton {
  height: 2.5rem;
  background: #252525;
  border-radius: 12px;
  animation: cliente-modal-pulse 1.5s ease-in-out infinite;
}

@keyframes cliente-modal-pulse {
  0%, 100% { opacity: 0.5; }
  50% { opacity: 1; }
}

.cliente-modal__body {
  padding: 1rem 1.25rem;
  overflow-y: auto;
  flex: 1;
}

.cliente-modal__row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.75rem 0;
  border-bottom: 1px solid #252525;
}

.cliente-modal__row:last-child {
  border-bottom: none;
}

.cliente-modal__label {
  font-size: 0.8125rem;
  color: #697586;
  flex-shrink: 0;
}

.cliente-modal__value {
  font-size: 0.875rem;
  font-weight: 500;
  color: #fff;
  text-align: right;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
}

.cliente-modal__hint {
  font-weight: 400;
  color: #697586;
}

.cliente-modal__badge {
  font-size: 0.6875rem;
  font-weight: 500;
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
  background: rgba(105, 117, 134, 0.2);
  color: #697586;
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

.cliente-modal__badge--active {
  background: rgba(0, 210, 97, 0.15);
  color: #00D261;
}

.cliente-modal__footer {
  padding: 1rem 1.25rem;
  padding-bottom: max(1rem, env(safe-area-inset-bottom));
  border-top: 1px solid #252525;
}

.cliente-modal__btn {
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

.cliente-modal__btn:hover {
  background: rgba(0, 210, 97, 0.1);
  border-color: #00D261;
}
</style>
