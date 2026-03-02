<script setup>
import { ref, onMounted } from 'vue'
import { useApi } from '@/composables/useApi'

const { get, put, cargando } = useApi()

const error = ref('')
const configEdit = ref({
  nombre_cuenta: '',
  banco: '',
  clave: '',
  semanas_entre_evaluaciones: 4
})
const guardando = ref(false)
const guardado = ref(false)

async function cargarConfiguracion() {
  error.value = ''
  try {
    const res = await get('/coach/configuracion')
    const datos = res.datos ?? res.data ?? res
    configEdit.value = {
      nombre_cuenta: datos.nombre_cuenta ?? '',
      banco: datos.banco ?? '',
      clave: datos.clave ?? '',
      semanas_entre_evaluaciones: datos.semanas_entre_evaluaciones ?? 4
    }
  } catch (e) {
    error.value = e.message || 'No se pudo cargar la configuración.'
  }
}

onMounted(async () => {
  await cargarConfiguracion()
})

async function guardarConfiguracion() {
  if (!configEdit.value.semanas_entre_evaluaciones || configEdit.value.semanas_entre_evaluaciones < 1) {
    configEdit.value.semanas_entre_evaluaciones = 1
  }
  if (configEdit.value.semanas_entre_evaluaciones > 52) {
    configEdit.value.semanas_entre_evaluaciones = 52
  }

  guardando.value = true
  guardado.value = false
  error.value = ''

  try {
    await put('/coach/configuracion', {
      nombre_cuenta: configEdit.value.nombre_cuenta || null,
      banco: configEdit.value.banco || null,
      clave: configEdit.value.clave || null,
      semanas_entre_evaluaciones: configEdit.value.semanas_entre_evaluaciones || 4
    })
    guardado.value = true
    setTimeout(() => {
      guardado.value = false
    }, 3000)
  } catch (e) {
    error.value = e.message || 'No se pudo guardar la configuración.'
  } finally {
    guardando.value = false
  }
}
</script>

<template>
  <div class="config">
    <div v-if="error" class="config__alert">
      {{ error }}
    </div>

    <section class="config__card">
      <header class="config__header">
        <h1 class="config__title">Configuración</h1>
        <p class="config__subtitle">
          Define el margen recomendado para agendar la próxima evaluación y tus datos de transferencia.
        </p>
      </header>

      <div class="config__body">
        <div class="config__field">
          <label for="config-semanas">Semanas entre evaluaciones</label>
          <input
            id="config-semanas"
            type="number"
            min="1"
            max="52"
            v-model.number="configEdit.semanas_entre_evaluaciones"
            class="config__input"
          />
          <p class="config__hint">
            Se usa como valor por defecto. Si un cliente tiene su propio valor, se aplica el de ese cliente.
          </p>
        </div>

        <div class="config__field">
          <label for="config-cuenta">Nombre de cuenta (transferencias)</label>
          <input
            id="config-cuenta"
            type="text"
            v-model="configEdit.nombre_cuenta"
            class="config__input"
            placeholder="Opcional"
          />
        </div>

        <div class="config__field">
          <label for="config-banco">Banco</label>
          <input
            id="config-banco"
            type="text"
            v-model="configEdit.banco"
            class="config__input"
            placeholder="Opcional"
          />
        </div>

        <div class="config__field">
          <label for="config-clave">CLABE / Cuenta</label>
          <input
            id="config-clave"
            type="text"
            v-model="configEdit.clave"
            class="config__input"
            placeholder="Opcional"
          />
        </div>

        <div class="config__actions">
          <button
            type="button"
            class="config__btn"
            :disabled="guardando || cargando"
            @click="guardarConfiguracion"
          >
            <span v-if="guardando || cargando" class="config__spinner" />
            <span v-else>Guardar configuración</span>
          </button>
          <span v-if="guardado" class="config__ok">Guardado</span>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
.config {
  min-height: 100vh;
  min-height: 100dvh;
  background: #0a0a0a;
  padding: 1rem;
  padding-top: max(1rem, env(safe-area-inset-top));
  padding-bottom: max(2rem, env(safe-area-inset-bottom));
}

.config__alert {
  background: rgba(239, 92, 92, 0.12);
  border: 1px solid rgba(239, 92, 92, 0.3);
  color: #ef5c5c;
  padding: 0.875rem 1rem;
  border-radius: 16px;
  font-size: 0.875rem;
  margin-bottom: 1rem;
}

.config__card {
  background: #161616;
  border-radius: 16px;
  padding: 1.25rem 1.25rem 1.5rem;
}

.config__header {
  margin-bottom: 1rem;
}

.config__title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #fff;
  margin: 0 0 0.25rem;
}

.config__subtitle {
  font-size: 0.8125rem;
  color: #697586;
  margin: 0;
  line-height: 1.4;
}

.config__body {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.config__field {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.config__field label {
  font-size: 0.8125rem;
  color: #697586;
}

.config__input {
  background: #1e1e1e;
  border: 1px solid #333;
  border-radius: 10px;
  padding: 0.625rem 0.75rem;
  font-size: 0.875rem;
  color: #fff;
}

.config__input:focus,
.config__input:focus-visible {
  outline: none;
  border-color: #00d261;
  box-shadow: 0 0 0 2px rgba(0, 210, 97, 0.2);
}

.config__hint {
  font-size: 0.75rem;
  color: #697586;
  margin: 0;
}

.config__actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-top: 0.5rem;
}

.config__btn {
  flex: 0 0 auto;
  padding: 0.875rem 1.25rem;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: 12px;
  cursor: pointer;
  border: none;
  background: var(--color-success-500, #00d261);
  color: #0a0a0a;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.config__btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.config__spinner {
  width: 1rem;
  height: 1rem;
  border-radius: 999px;
  border: 2px solid rgba(10, 10, 10, 0.3);
  border-top-color: #0a0a0a;
  animation: config-spin 0.8s linear infinite;
}

.config__ok {
  font-size: 0.8125rem;
  color: var(--color-success-500, #00d261);
}

@keyframes config-spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>

