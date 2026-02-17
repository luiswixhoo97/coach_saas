<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApi } from '@/composables/useApi'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseEmptyState from '@/components/ui/BaseEmptyState.vue'

const route = useRoute()
const router = useRouter()
const { get, post, put, del, cargando } = useApi()

const clienteId = computed(() => parseInt(route.params.id))
const cliente = ref(null)
const parametros = ref([])
const parametrosDisponibles = ref([])
const historial = ref([])
const error = ref('')
const cargandoCliente = ref(false)
const cargandoParametros = ref(false)
const cargandoHistorial = ref(false)

// Formulario para agregar parámetro
const mostrarFormulario = ref(false)
const parametroSeleccionado = ref(null)
const valorParametro = ref('')
const fechaParametro = ref(new Date().toISOString().split('T')[0])
const notasParametro = ref('')
const guardando = ref(false)
const editandoId = ref(null)

onMounted(async () => {
  await Promise.all([
    cargarCliente(),
    cargarParametrosDisponibles(),
    cargarHistorial()
  ])
})

async function cargarCliente() {
  try {
    cargandoCliente.value = true
    const response = await get(`/coach/clientes/${clienteId.value}`)
    cliente.value = response.datos
  } catch (err) {
    error.value = err.response?.data?.mensaje || 'Error al cargar cliente'
  } finally {
    cargandoCliente.value = false
  }
}

async function cargarParametrosDisponibles() {
  try {
    cargandoParametros.value = true
    const response = await get('/coach/parametros')
    parametrosDisponibles.value = response.datos || []
  } catch (err) {
    error.value = err.response?.data?.mensaje || 'Error al cargar parámetros'
  } finally {
    cargandoParametros.value = false
  }
}

async function cargarHistorial() {
  try {
    cargandoHistorial.value = true
    const response = await get(`/coach/clientes/${clienteId.value}/parametros`)
    historial.value = response.datos || []
  } catch (err) {
    error.value = err.response?.data?.mensaje || 'Error al cargar historial'
  } finally {
    cargandoHistorial.value = false
  }
}

function nombreCompleto() {
  if (!cliente.value) return ''
  const partes = [cliente.value.nombre, cliente.value.apellido_paterno, cliente.value.apellido_materno].filter(Boolean)
  return partes.join(' ') || 'Cliente'
}

function abrirFormulario(parametro = null, itemHistorial = null) {
  if (itemHistorial) {
    // Editar
    editandoId.value = itemHistorial.id
    parametroSeleccionado.value = itemHistorial.parametro_id
    valorParametro.value = itemHistorial.valor
    fechaParametro.value = itemHistorial.fecha
    notasParametro.value = itemHistorial.notas || ''
  } else {
    // Nuevo
    editandoId.value = null
    parametroSeleccionado.value = parametro ? parametro.id : null
    valorParametro.value = ''
    fechaParametro.value = new Date().toISOString().split('T')[0]
    notasParametro.value = ''
  }
  mostrarFormulario.value = true
}

function cerrarFormulario() {
  mostrarFormulario.value = false
  editandoId.value = null
  parametroSeleccionado.value = null
  valorParametro.value = ''
  fechaParametro.value = new Date().toISOString().split('T')[0]
  notasParametro.value = ''
}

async function guardarParametro() {
  if (!parametroSeleccionado.value || !valorParametro.value.trim() || !fechaParametro.value) {
    error.value = 'Por favor completa todos los campos requeridos'
    return
  }

  try {
    guardando.value = true
    error.value = ''

    if (editandoId.value) {
      // Actualizar
      await put(`/coach/parametros-cliente/${editandoId.value}`, {
        valor: valorParametro.value.trim(),
        fecha: fechaParametro.value,
        notas: notasParametro.value.trim() || null
      })
    } else {
      // Crear
      await post(`/coach/clientes/${clienteId.value}/parametros`, {
        parametro_id: parametroSeleccionado.value,
        valor: valorParametro.value.trim(),
        fecha: fechaParametro.value,
        notas: notasParametro.value.trim() || null
      })
    }

    await cargarHistorial()
    cerrarFormulario()
  } catch (err) {
    error.value = err.response?.data?.mensaje || 'Error al guardar parámetro'
  } finally {
    guardando.value = false
  }
}

async function eliminarParametro(id) {
  if (!confirm('¿Estás seguro de eliminar este parámetro del historial?')) {
    return
  }

  try {
    await del(`/coach/parametros-cliente/${id}`)
    await cargarHistorial()
  } catch (err) {
    error.value = err.response?.data?.mensaje || 'Error al eliminar parámetro'
  }
}

function obtenerNombreParametro(parametroId) {
  const parametro = parametrosDisponibles.value.find(p => p.id === parametroId)
  return parametro ? parametro.nombre : 'Parámetro'
}

function obtenerUnidadParametro(parametroId) {
  const parametro = parametrosDisponibles.value.find(p => p.id === parametroId)
  return parametro ? parametro.unidad_medida : ''
}

// Agrupar historial por fecha
const historialAgrupado = computed(() => {
  const grupos = {}
  historial.value.forEach(item => {
    const fecha = item.fecha
    if (!grupos[fecha]) {
      grupos[fecha] = []
    }
    grupos[fecha].push(item)
  })
  return Object.entries(grupos)
    .sort((a, b) => new Date(b[0]) - new Date(a[0]))
    .map(([fecha, items]) => ({ fecha, items }))
})
</script>

<template>
  <div class="parametros-cliente-view">
    <div class="parametros-cliente-view__header">
      <div class="parametros-cliente-view__header-content">
        <button
          type="button"
          class="parametros-cliente-view__back"
          @click="router.push({ name: 'CoachUsuarios' })"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5M12 19l-7-7 7-7"/>
          </svg>
        </button>
        <div>
          <h1 class="parametros-cliente-view__title">Parámetros</h1>
          <p v-if="cliente" class="parametros-cliente-view__subtitle">
            {{ nombreCompleto() }}
          </p>
        </div>
      </div>
      <BaseButton
        v-if="!mostrarFormulario"
        variant="primary"
        @click="abrirFormulario()"
      >
        Agregar Parámetro
      </BaseButton>
    </div>

    <div class="parametros-cliente-view__content">
      <!-- Formulario para agregar/editar parámetro -->
      <div v-if="mostrarFormulario" class="parametros-cliente-view__form-card">
        <div class="parametros-cliente-view__form-header">
          <h2 class="parametros-cliente-view__form-title">
            {{ editandoId ? 'Editar Parámetro' : 'Agregar Parámetro' }}
          </h2>
          <button
            type="button"
            class="parametros-cliente-view__form-close"
            @click="cerrarFormulario"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 6L6 18M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <form @submit.prevent="guardarParametro" class="parametros-cliente-view__form">
          <div class="parametros-cliente-view__form-group">
            <label class="parametros-cliente-view__form-label">
              Parámetro <span class="text-danger-500">*</span>
            </label>
            <select
              v-model="parametroSeleccionado"
              required
              :disabled="guardando"
              class="parametros-cliente-view__form-select"
            >
              <option value="">Selecciona un parámetro</option>
              <option
                v-for="parametro in parametrosDisponibles"
                :key="parametro.id"
                :value="parametro.id"
              >
                {{ parametro.nombre }}
              </option>
            </select>
          </div>

          <BaseInput
            v-model="valorParametro"
            type="text"
            label="Valor"
            placeholder="Ingresa el valor"
            required
            :disabled="guardando"
          />

          <BaseInput
            v-model="fechaParametro"
            type="date"
            label="Fecha"
            required
            :disabled="guardando"
          />

          <div class="parametros-cliente-view__form-group">
            <label class="parametros-cliente-view__form-label">
              Notas (opcional)
            </label>
            <textarea
              v-model="notasParametro"
              placeholder="Notas adicionales..."
              :disabled="guardando"
              rows="3"
              class="parametros-cliente-view__form-textarea"
            />
          </div>

          <div v-if="error" class="parametros-cliente-view__error">
            {{ error }}
          </div>

          <div class="parametros-cliente-view__form-actions">
            <BaseButton
              type="button"
              variant="secondary"
              @click="cerrarFormulario"
              :disabled="guardando"
            >
              Cancelar
            </BaseButton>
            <BaseButton
              type="submit"
              variant="primary"
              :loading="guardando"
              :disabled="guardando"
            >
              {{ editandoId ? 'Actualizar' : 'Guardar' }}
            </BaseButton>
          </div>
        </form>
      </div>

      <!-- Historial de parámetros -->
      <div v-else class="parametros-cliente-view__historial">
        <!-- Lista de parámetros disponibles para llenar rápidamente -->
        <div v-if="!cargandoParametros && parametrosDisponibles.length > 0" class="parametros-cliente-view__parametros-disponibles">
          <h3 class="parametros-cliente-view__seccion-titulo">Parámetros Disponibles</h3>
          <div class="parametros-cliente-view__parametros-grid">
            <button
              v-for="parametro in parametrosDisponibles"
              :key="parametro.id"
              type="button"
              class="parametros-cliente-view__parametro-card"
              @click="abrirFormulario(parametro)"
            >
              <div class="parametros-cliente-view__parametro-info">
                <span class="parametros-cliente-view__parametro-nombre">{{ parametro.nombre }}</span>
                <span class="parametros-cliente-view__parametro-unidad">{{ parametro.unidad_medida }}</span>
              </div>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="parametros-cliente-view__parametro-icon">
                <path d="M12 5v14M5 12h14"/>
              </svg>
            </button>
          </div>
        </div>

        <div v-if="cargandoHistorial" class="parametros-cliente-view__loading">
          <div class="parametros-cliente-view__skeleton" />
          <div class="parametros-cliente-view__skeleton" />
        </div>

        <div v-else-if="historial.length > 0" class="parametros-cliente-view__historial-section">
          <h3 class="parametros-cliente-view__seccion-titulo">Historial</h3>
        </div>

        <BaseEmptyState
          v-else-if="historial.length === 0 && !cargandoHistorial"
          title="Sin parámetros registrados"
          description="Haz clic en un parámetro disponible arriba o en 'Agregar Parámetro' para comenzar."
        />

        <div v-if="historial.length > 0" class="parametros-cliente-view__historial-grupos">
          <div
            v-for="grupo in historialAgrupado"
            :key="grupo.fecha"
            class="parametros-cliente-view__grupo"
          >
            <h3 class="parametros-cliente-view__grupo-fecha">
              {{ new Date(grupo.fecha).toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
            </h3>
            <div class="parametros-cliente-view__grupo-items">
              <div
                v-for="item in grupo.items"
                :key="item.id"
                class="parametros-cliente-view__item"
              >
                <div class="parametros-cliente-view__item-content">
                  <div class="parametros-cliente-view__item-info">
                    <span class="parametros-cliente-view__item-nombre">
                      {{ obtenerNombreParametro(item.parametro_id) }}
                    </span>
                    <span class="parametros-cliente-view__item-valor">
                      {{ item.valor }} {{ obtenerUnidadParametro(item.parametro_id) }}
                    </span>
                  </div>
                  <div v-if="item.notas" class="parametros-cliente-view__item-notas">
                    {{ item.notas }}
                  </div>
                </div>
                <div class="parametros-cliente-view__item-actions">
                  <button
                    type="button"
                    class="parametros-cliente-view__item-btn"
                    @click="abrirFormulario(null, item)"
                    title="Editar"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                  </button>
                  <button
                    type="button"
                    class="parametros-cliente-view__item-btn parametros-cliente-view__item-btn--danger"
                    @click="eliminarParametro(item.id)"
                    title="Eliminar"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.parametros-cliente-view {
  min-height: 100vh;
  background: #0f0f0f;
  padding: 1.5rem;
}

.parametros-cliente-view__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 2rem;
}

.parametros-cliente-view__header-content {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex: 1;
  min-width: 0;
}

.parametros-cliente-view__back {
  background: #1a1a1a;
  border: 1px solid #252525;
  border-radius: 8px;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #9CA3AF;
  transition: all 0.2s;
  flex-shrink: 0;
}

.parametros-cliente-view__back:hover {
  background: #252525;
  color: #fff;
}

.parametros-cliente-view__title {
  font-size: 1.5rem;
  font-weight: 600;
  color: #fff;
  margin: 0;
}

.parametros-cliente-view__subtitle {
  font-size: 0.875rem;
  color: #9CA3AF;
  margin: 0.25rem 0 0;
}

.parametros-cliente-view__content {
  max-width: 900px;
  margin: 0 auto;
}

.parametros-cliente-view__form-card {
  background: #161616;
  border: 1px solid #252525;
  border-radius: 12px;
  padding: 1.5rem;
}

.parametros-cliente-view__form-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
}

.parametros-cliente-view__form-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #fff;
  margin: 0;
}

.parametros-cliente-view__form-close {
  background: transparent;
  border: none;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #9CA3AF;
  border-radius: 6px;
  transition: all 0.2s;
}

.parametros-cliente-view__form-close:hover {
  background: #252525;
  color: #fff;
}

.parametros-cliente-view__form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.parametros-cliente-view__form-actions {
  display: flex;
  gap: 0.75rem;
  margin-top: 0.5rem;
}

.parametros-cliente-view__form-actions :deep(.base-button) {
  flex: 1;
}

.parametros-cliente-view__form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.parametros-cliente-view__form-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #9CA3AF;
}

.parametros-cliente-view__form-select {
  width: 100%;
  padding: 0.75rem 1rem;
  background: #1a1a1a;
  border: 1px solid #252525;
  border-radius: 8px;
  color: #fff;
  font-size: 0.875rem;
  transition: all 0.2s;
}

.parametros-cliente-view__form-select:focus {
  outline: none;
  border-color: #00D261;
  ring: 2px;
  ring-color: rgba(0, 210, 97, 0.2);
}

.parametros-cliente-view__form-select:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.parametros-cliente-view__form-select option {
  background: #1a1a1a;
  color: #fff;
}

.parametros-cliente-view__form-textarea {
  width: 100%;
  padding: 0.75rem 1rem;
  background: #1a1a1a;
  border: 1px solid #252525;
  border-radius: 8px;
  color: #fff;
  font-size: 0.875rem;
  font-family: inherit;
  resize: vertical;
  transition: all 0.2s;
}

.parametros-cliente-view__form-textarea:focus {
  outline: none;
  border-color: #00D261;
  ring: 2px;
  ring-color: rgba(0, 210, 97, 0.2);
}

.parametros-cliente-view__form-textarea:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.parametros-cliente-view__form-textarea::placeholder {
  color: #697586;
}

.parametros-cliente-view__error {
  padding: 0.75rem 1rem;
  background: rgba(239, 92, 92, 0.1);
  border: 1px solid rgba(239, 92, 92, 0.3);
  border-radius: 8px;
  color: #EF5C5C;
  font-size: 0.875rem;
}

.parametros-cliente-view__historial {
  background: #161616;
  border: 1px solid #252525;
  border-radius: 12px;
  padding: 1.5rem;
}

.parametros-cliente-view__loading {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.parametros-cliente-view__skeleton {
  height: 80px;
  background: #1a1a1a;
  border-radius: 8px;
  animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.parametros-cliente-view__historial-grupos {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.parametros-cliente-view__grupo {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.parametros-cliente-view__grupo-fecha {
  font-size: 0.875rem;
  font-weight: 600;
  color: #9CA3AF;
  text-transform: capitalize;
  margin: 0;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid #252525;
}

.parametros-cliente-view__grupo-items {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.parametros-cliente-view__item {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  padding: 1rem;
  background: #1a1a1a;
  border: 1px solid #252525;
  border-radius: 8px;
  transition: all 0.2s;
}

.parametros-cliente-view__item:hover {
  border-color: #333;
}

.parametros-cliente-view__item-content {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.parametros-cliente-view__item-info {
  display: flex;
  align-items: baseline;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.parametros-cliente-view__item-nombre {
  font-size: 0.875rem;
  font-weight: 500;
  color: #9CA3AF;
}

.parametros-cliente-view__item-valor {
  font-size: 1rem;
  font-weight: 600;
  color: #fff;
}

.parametros-cliente-view__item-notas {
  font-size: 0.875rem;
  color: #697586;
  font-style: italic;
}

.parametros-cliente-view__item-actions {
  display: flex;
  gap: 0.5rem;
  flex-shrink: 0;
}

.parametros-cliente-view__item-btn {
  background: transparent;
  border: 1px solid #252525;
  border-radius: 6px;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #9CA3AF;
  transition: all 0.2s;
}

.parametros-cliente-view__item-btn:hover {
  background: #252525;
  color: #fff;
}

.parametros-cliente-view__item-btn--danger:hover {
  background: rgba(239, 92, 92, 0.1);
  border-color: rgba(239, 92, 92, 0.3);
  color: #EF5C5C;
}

.parametros-cliente-view__seccion-titulo {
  font-size: 1rem;
  font-weight: 600;
  color: #fff;
  margin: 0 0 1rem;
}

.parametros-cliente-view__parametros-disponibles {
  margin-bottom: 2rem;
}

.parametros-cliente-view__parametros-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 0.75rem;
}

.parametros-cliente-view__parametro-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 1rem;
  background: #1a1a1a;
  border: 1px solid #252525;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.parametros-cliente-view__parametro-card:hover {
  background: #252525;
  border-color: #00D261;
}

.parametros-cliente-view__parametro-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  flex: 1;
  min-width: 0;
}

.parametros-cliente-view__parametro-nombre {
  font-size: 0.875rem;
  font-weight: 500;
  color: #fff;
}

.parametros-cliente-view__parametro-unidad {
  font-size: 0.75rem;
  color: #9CA3AF;
}

.parametros-cliente-view__parametro-icon {
  width: 20px;
  height: 20px;
  color: #00D261;
  flex-shrink: 0;
}

.parametros-cliente-view__historial-section {
  margin-top: 2rem;
}

@media (max-width: 640px) {
  .parametros-cliente-view {
    padding: 1rem;
  }

  .parametros-cliente-view__header {
    flex-direction: column;
    align-items: stretch;
  }

  .parametros-cliente-view__header-content {
    flex-direction: column;
    align-items: stretch;
  }

  .parametros-cliente-view__back {
    align-self: flex-start;
  }

  .parametros-cliente-view__parametros-grid {
    grid-template-columns: 1fr;
  }
}
</style>

