<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useApi } from '@/composables/useApi'
import BaseEmptyState from '@/components/ui/BaseEmptyState.vue'
import BaseTable from '@/components/ui/BaseTable.vue'
import ParametroCrearModal from '@/components/coach/ParametroCrearModal.vue'

const { get, post, put, del, cargando } = useApi()
const parametros = ref([])
const error = ref('')

// Modal crear/editar
const showFormModal = ref(false)
const formMode = ref('create') // 'create' | 'edit'
const formParametro = ref(null)

// Móvil: parámetro seleccionado para mostrar acciones Editar / Eliminar
const selectedParametro = ref(null)

const isMobile = ref(false)
const MOBILE_BREAKPOINT = 768

const parametrosPersonalizados = computed(() => {
  return parametros.value.filter(p => !p.es_predeterminado)
})

const parametrosPredeterminados = computed(() => {
  return parametros.value.filter(p => p.es_predeterminado)
})

async function cargarParametros() {
  try {
    error.value = ''
    const res = await get('/coach/parametros')
    parametros.value = res.data?.datos ?? res.datos ?? []
  } catch (e) {
    error.value = e.message || 'No se pudo cargar la lista de parámetros.'
    parametros.value = []
  }
}

function checkMobile() {
  isMobile.value = window.innerWidth < MOBILE_BREAKPOINT
}

// --- Agregar parámetro (abre modal en modo crear)
function abrirAgregar() {
  formMode.value = 'create'
  formParametro.value = null
  showFormModal.value = true
}

// --- Editar parámetro (abre modal en modo editar)
function abrirEditar(p) {
  if (p.es_predeterminado || !p.editable) return
  selectedParametro.value = null
  formMode.value = 'edit'
  formParametro.value = p
  showFormModal.value = true
}

function cerrarFormModal() {
  showFormModal.value = false
  formParametro.value = null
}

function onParametroCreado() {
  cerrarFormModal()
  cargarParametros()
}

// --- Móvil: seleccionar parámetro para mostrar acciones
function seleccionarParametro(p) {
  if (p.es_predeterminado || !p.editable) return
  selectedParametro.value = p
}

function cerrarSeleccion() {
  selectedParametro.value = null
}

// --- Eliminar parámetro
async function eliminarParametro(p) {
  if (p.es_predeterminado || !p.editable) return
  if (!confirm('¿Seguro que deseas eliminar este parámetro?')) return
  selectedParametro.value = null
  try {
    await del(`/coach/parametros/${p.id}`)
    await cargarParametros()
  } catch (err) {
    error.value = err.message || 'No se pudo eliminar el parámetro.'
  }
}

function labelTipoDato(value) {
  const tipos = {
    numero: 'Número',
    texto: 'Texto',
    booleano: 'Booleano'
  }
  return tipos[value] || value
}

onMounted(() => {
  cargarParametros()
  checkMobile()
  window.addEventListener('resize', checkMobile)
})

onUnmounted(() => {
  window.removeEventListener('resize', checkMobile)
})
</script>

<template>
  <div class="parametros">
    <div class="parametros__wrap">
      <div v-if="error" class="parametros__alert">{{ error }}</div>

      <section class="parametros__section">
        <div class="parametros__section-header">
          <div class="parametros__section-title-row">
            <h2 class="parametros__section-title">Parámetros de Evaluación</h2>
            <span class="parametros__section-meta" v-if="parametros.length > 0">
              {{ parametros.length }} {{ parametros.length === 1 ? 'parámetro' : 'parámetros' }}
            </span>
          </div>
          <button type="button" class="parametros__btn-add" @click="abrirAgregar" aria-label="Agregar parámetro">
            Crear parámetro
          </button>
        </div>

        <!-- Parámetros predeterminados -->
        <div v-if="parametrosPredeterminados.length > 0" class="parametros__grupo">
          <h3 class="parametros__grupo-title">Parámetros predeterminados</h3>
          <p class="parametros__grupo-desc">Estos parámetros están disponibles para todos los coaches y no se pueden editar.</p>
          
          <!-- Desktop: tabla -->
          <template v-if="!isMobile">
            <div class="parametros__table-wrap">
              <table class="parametros__table">
                <thead class="parametros__thead">
                  <tr>
                    <th class="parametros__th">Nombre</th>
                    <th class="parametros__th">Unidad de medida</th>
                    <th class="parametros__th">Tipo de dato</th>
                  </tr>
                </thead>
                <tbody class="parametros__tbody">
                  <tr v-for="p in parametrosPredeterminados" :key="p.id" class="parametros__tr">
                    <td class="parametros__td">{{ p.nombre || '—' }}</td>
                    <td class="parametros__td">{{ p.unidad_medida || '—' }}</td>
                    <td class="parametros__td">{{ labelTipoDato(p.tipo_dato) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </template>

          <!-- Móvil: cards -->
          <BaseTable v-else :items="parametrosPredeterminados" key-field="id" :loading="false">
            <template #row="{ item: p }">
              <div class="parametros__link parametros__link--predeterminado">
                <div class="parametros__info">
                  <span class="parametros__nombre">{{ p.nombre || '—' }}</span>
                  <span class="parametros__meta">{{ p.unidad_medida }} · {{ labelTipoDato(p.tipo_dato) }}</span>
                </div>
                <span class="parametros__badge parametros__badge--predeterminado">Predeterminado</span>
              </div>
            </template>
          </BaseTable>
        </div>

        <!-- Parámetros personalizados -->
        <div v-if="parametrosPersonalizados.length > 0" class="parametros__grupo">
          <h3 class="parametros__grupo-title">Mis parámetros</h3>
          <p class="parametros__grupo-desc">Parámetros personalizados que puedes editar o eliminar.</p>

          <!-- Desktop: tabla -->
          <template v-if="!isMobile">
            <div v-if="cargando && !parametrosPersonalizados.length" class="parametros__loading parametros__loading--table">
              <div v-for="i in 4" :key="i" class="parametros__skeleton-row parametros__skeleton-row--table">
                <div class="parametros__skeleton-cell" />
                <div class="parametros__skeleton-cell" />
                <div class="parametros__skeleton-cell" />
                <div class="parametros__skeleton-cell" />
              </div>
            </div>
            <div v-else class="parametros__table-wrap">
              <table class="parametros__table">
                <thead class="parametros__thead">
                  <tr>
                    <th class="parametros__th">Nombre</th>
                    <th class="parametros__th">Unidad de medida</th>
                    <th class="parametros__th">Tipo de dato</th>
                    <th class="parametros__th parametros__th--acciones">Acciones</th>
                  </tr>
                </thead>
                <tbody class="parametros__tbody">
                  <tr v-for="p in parametrosPersonalizados" :key="p.id" class="parametros__tr">
                    <td class="parametros__td">{{ p.nombre || '—' }}</td>
                    <td class="parametros__td">{{ p.unidad_medida || '—' }}</td>
                    <td class="parametros__td">{{ labelTipoDato(p.tipo_dato) }}</td>
                    <td class="parametros__td parametros__td--acciones">
                      <button type="button" class="parametros__action-btn parametros__action-btn--edit" @click="abrirEditar(p)" aria-label="Editar parámetro">Editar</button>
                      <button type="button" class="parametros__action-btn parametros__action-btn--delete" @click="eliminarParametro(p)" aria-label="Eliminar parámetro">Eliminar</button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </template>

          <!-- Móvil: cards -->
          <BaseTable v-else :items="parametrosPersonalizados" key-field="id" :loading="cargando">
            <template #loading>
              <div class="parametros__loading">
                <div v-for="i in 4" :key="i" class="parametros__skeleton-row">
                  <div class="parametros__skeleton-text" />
                  <div class="parametros__skeleton-text" />
                </div>
              </div>
            </template>
            <template #row="{ item: p }">
              <div class="parametros__link" @click="seleccionarParametro(p)">
                <div class="parametros__info">
                  <span class="parametros__nombre">{{ p.nombre || '—' }}</span>
                  <span class="parametros__meta">{{ p.unidad_medida }} · {{ labelTipoDato(p.tipo_dato) }}</span>
                </div>
                <span class="parametros__badge parametros__badge--personalizado">Personalizado</span>
              </div>
            </template>
          </BaseTable>
        </div>

        <!-- Empty state -->
        <div v-if="!cargando && parametros.length === 0" class="parametros__empty">
          <BaseEmptyState
            titulo="No hay parámetros"
            descripcion="Los parámetros que crees aparecerán aquí."
          />
        </div>
      </section>

      <!-- Modal crear/editar parámetro -->
      <ParametroCrearModal
        v-if="showFormModal"
        :parametro-para-editar="formMode === 'edit' ? formParametro : null"
        @close="cerrarFormModal"
        @created="onParametroCreado"
      />

      <!-- Móvil: acción al seleccionar parámetro (Editar / Eliminar) -->
      <Teleport to="body">
        <Transition name="sheet">
          <div v-if="selectedParametro && isMobile" class="parametros__sheet-overlay" @click.self="cerrarSeleccion">
            <div class="parametros__sheet">
              <p class="parametros__sheet-title">{{ selectedParametro.nombre || '—' }}</p>
              <p class="parametros__sheet-subtitle">{{ selectedParametro.unidad_medida }} · {{ labelTipoDato(selectedParametro.tipo_dato) }}</p>
              <div class="parametros__sheet-actions">
                <button type="button" class="parametros__sheet-btn parametros__sheet-btn--edit" @click="abrirEditar(selectedParametro)">
                  Editar parámetro
                </button>
                <button type="button" class="parametros__sheet-btn parametros__sheet-btn--delete" @click="eliminarParametro(selectedParametro)">
                  Eliminar parámetro
                </button>
                <button type="button" class="parametros__sheet-btn parametros__sheet-btn--cancel" @click="cerrarSeleccion">
                  Cerrar
                </button>
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>
    </div>
  </div>
</template>

<style scoped>
.parametros {
  min-height: 100vh;
  min-height: 100dvh;
  background: #0a0a0a;
  padding: 1rem;
  padding-top: max(1rem, env(safe-area-inset-top));
  padding-bottom: max(2rem, env(safe-area-inset-bottom));
}

.parametros__wrap {
  max-width: 32rem;
  margin: 0 auto;
}

@media (min-width: 768px) {
  .parametros__wrap {
    max-width: 100%;
    padding: 0 0.5rem;
  }
}

.parametros__alert {
  background: color-mix(in srgb, var(--color-danger-500) 12%, transparent);
  border: 1px solid color-mix(in srgb, var(--color-danger-500) 35%, transparent);
  color: var(--color-danger-500);
  padding: 0.875rem 1rem;
  border-radius: 16px;
  font-size: 0.875rem;
  margin-bottom: 1rem;
}

.parametros__section {
  background: #161616;
  border-radius: 16px;
  padding: 1rem;
}

.parametros__section-header {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.parametros__section-title-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.parametros__section-title {
  font-size: 0.9375rem;
  font-weight: 600;
  color: var(--color-label-tertiary);
  margin: 0;
}

.parametros__section-meta {
  font-size: 0.75rem;
  color: var(--color-label-secondary);
}

.parametros__btn-add {
  padding: 0.5rem 0.875rem;
  font-size: 0.8125rem;
  font-weight: 500;
  color: var(--color-success-500);
  background: color-mix(in srgb, var(--color-success-500) 15%, transparent);
  border: 1px solid color-mix(in srgb, var(--color-success-500) 40%, transparent);
  border-radius: 12px;
  cursor: pointer;
  transition: background 0.2s, border-color 0.2s;
}

.parametros__btn-add:hover {
  background: color-mix(in srgb, var(--color-success-500) 25%, transparent);
  border-color: var(--color-success-500);
}

.parametros__grupo {
  margin-bottom: 1.5rem;
}

.parametros__grupo:last-child {
  margin-bottom: 0;
}

.parametros__grupo-title {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-label-tertiary);
  margin: 0 0 0.25rem;
}

.parametros__grupo-desc {
  font-size: 0.75rem;
  color: var(--color-label-secondary);
  margin: 0 0 0.75rem;
}

.parametros__link {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.875rem;
  padding: 0.875rem 1rem;
  color: inherit;
  transition: background 0.2s;
  cursor: pointer;
}

.parametros__link:hover {
  background: rgba(255, 255, 255, 0.03);
}

.parametros__link--predeterminado {
  cursor: default;
  opacity: 0.7;
}

.parametros__info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
}

.parametros__nombre {
  font-size: 0.9375rem;
  font-weight: 500;
  color: var(--color-label-tertiary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.parametros__meta {
  font-size: 0.75rem;
  color: var(--color-label-secondary);
}

.parametros__badge {
  font-size: 0.6875rem;
  font-weight: 500;
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
  text-transform: uppercase;
  letter-spacing: 0.02em;
  flex-shrink: 0;
}

.parametros__badge--predeterminado {
  background: color-mix(in srgb, var(--color-label-secondary) 20%, transparent);
  color: var(--color-label-secondary);
}

.parametros__badge--personalizado {
  background: color-mix(in srgb, var(--color-success-500) 15%, transparent);
  color: var(--color-success-500);
}

.parametros__empty {
  padding: 2rem 0;
}

.parametros__empty :deep(h3) {
  color: var(--color-label-tertiary);
}

.parametros__empty :deep(p) {
  color: var(--color-label-secondary);
}

.parametros__loading {
  padding: 0.5rem 0;
}

.parametros__skeleton-row {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  padding: 0.875rem 0;
  border-bottom: 1px solid #252525;
}

.parametros__skeleton-text {
  flex: 1;
  height: 1rem;
  border-radius: 6px;
  background: #252525;
  animation: parametros-pulse 1.5s ease-in-out infinite;
}

.parametros__table-wrap {
  overflow-x: auto;
  border-radius: 12px;
  border: 1px solid #252525;
  margin-top: 0.75rem;
}

.parametros__table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.8125rem;
}

.parametros__thead {
  background: #1e1e1e;
}

.parametros__th {
  text-align: left;
  padding: 0.75rem 1rem;
  font-weight: 600;
  color: var(--color-label-secondary);
  white-space: nowrap;
  border-bottom: 1px solid #252525;
}

.parametros__tbody {
  background: #161616;
}

.parametros__tr {
  border-bottom: 1px solid #252525;
  transition: background 0.2s;
}

.parametros__tr:last-child {
  border-bottom: none;
}

.parametros__tr:hover {
  background: rgba(255, 255, 255, 0.02);
}

.parametros__td {
  padding: 0.75rem 1rem;
  color: var(--color-label-tertiary);
  border-bottom: 1px solid #252525;
  vertical-align: middle;
}

.parametros__tr:last-child .parametros__td {
  border-bottom: none;
}

.parametros__loading--table {
  padding: 0.75rem 0;
}

.parametros__skeleton-row--table {
  display: flex;
  gap: 0.5rem;
  padding: 0.5rem 0;
  border-bottom: none;
}

.parametros__skeleton-cell {
  flex: 1;
  min-width: 4rem;
  height: 2rem;
  background: #252525;
  border-radius: 6px;
  animation: parametros-pulse 1.5s ease-in-out infinite;
}

@keyframes parametros-pulse {
  0%, 100% { opacity: 0.5; }
  50% { opacity: 1; }
}

.parametros__th--acciones,
.parametros__td--acciones {
  white-space: nowrap;
  width: 1%;
}

.parametros__td--acciones {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.parametros__action-btn {
  padding: 0.375rem 0.625rem;
  font-size: 0.75rem;
  font-weight: 500;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  transition: opacity 0.2s;
}

.parametros__action-btn--edit {
  color: var(--color-success-500);
  background: color-mix(in srgb, var(--color-success-500) 15%, transparent);
}

.parametros__action-btn--edit:hover {
  background: color-mix(in srgb, var(--color-success-500) 25%, transparent);
}

.parametros__action-btn--delete {
  color: var(--color-danger-500);
  background: color-mix(in srgb, var(--color-danger-500) 15%, transparent);
}

.parametros__action-btn--delete:hover {
  background: color-mix(in srgb, var(--color-danger-500) 25%, transparent);
}

.parametros__sheet-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 90;
  display: flex;
  align-items: flex-end;
  justify-content: center;
}

.parametros__sheet {
  background: #161616;
  border-radius: 16px 16px 0 0;
  padding: 1.25rem;
  padding-bottom: max(1.25rem, env(safe-area-inset-bottom));
  width: 100%;
  max-width: 32rem;
  border: 1px solid #252525;
  border-bottom: none;
}

.parametros__sheet-title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--color-label-tertiary);
  margin: 0 0 0.25rem;
}

.parametros__sheet-subtitle {
  font-size: 0.8125rem;
  color: var(--color-label-secondary);
  margin: 0 0 1rem;
}

.parametros__sheet-actions {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.parametros__sheet-btn {
  padding: 0.75rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: 12px;
  border: none;
  cursor: pointer;
  transition: background 0.2s;
  text-align: center;
}

.parametros__sheet-btn--edit {
  background: color-mix(in srgb, var(--color-success-500) 15%, transparent);
  color: var(--color-success-500);
}

.parametros__sheet-btn--edit:hover {
  background: color-mix(in srgb, var(--color-success-500) 25%, transparent);
}

.parametros__sheet-btn--delete {
  background: color-mix(in srgb, var(--color-danger-500) 15%, transparent);
  color: var(--color-danger-500);
}

.parametros__sheet-btn--delete:hover {
  background: color-mix(in srgb, var(--color-danger-500) 25%, transparent);
}

.parametros__sheet-btn--cancel {
  background: #252525;
  color: var(--color-label-secondary);
}

.parametros__sheet-btn--cancel:hover {
  background: #1e1e1e;
  color: var(--color-label-tertiary);
}

.sheet-enter-active,
.sheet-leave-active {
  transition: opacity 0.2s ease;
}
.sheet-enter-from,
.sheet-leave-to {
  opacity: 0;
}
.sheet-enter-active .parametros__sheet,
.sheet-leave-active .parametros__sheet {
  transition: transform 0.25s ease;
}
.sheet-enter-from .parametros__sheet,
.sheet-leave-to .parametros__sheet {
  transform: translateY(100%);
}
</style>

