<script setup>
/**
 * BaseTable - Tabla / lista por elementos (cards) según estándar de diseño.
 * Tema oscuro: fondo #161616 sección, cada fila en card #1e1e1e, gap entre filas.
 *
 * Props: items (array), keyField (string, default 'id')
 * Slots: header, row ({ item }), empty, loading
 */
const props = defineProps({
  items: {
    type: Array,
    default: () => []
  },
  keyField: {
    type: String,
    default: 'id'
  },
  loading: {
    type: Boolean,
    default: false
  }
})

const getKey = (item) => item == null ? undefined : (item[props.keyField] ?? undefined)
</script>

<template>
  <div class="base-table">
    <slot name="header" />

    <div v-if="loading && !items.length && $slots.loading" class="base-table__loading">
      <slot name="loading" />
    </div>

    <div v-else-if="!items.length && $slots.empty" class="base-table__empty">
      <slot name="empty" />
    </div>

    <ul v-else-if="items.length" class="base-table__list">
      <li
        v-for="(item, index) in items"
        :key="getKey(item) ?? index"
        class="base-table__item"
      >
        <div class="base-table__card">
          <slot name="row" :item="item" :index="index" />
        </div>
      </li>
    </ul>
  </div>
</template>

<style scoped>
.base-table {
  /* contenedor; el header y el contenido van por slots */
}

.base-table__list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.base-table__item {
  /* un contenedor por elemento */
}

.base-table__card {
  background: #1e1e1e;
  border-radius: 12px;
  padding: 0;
  overflow: hidden;
}

.base-table__card > * {
  /* contenido del slot row: el padre define padding y hover */
  display: block;
  transition: background 0.2s ease;
}

.base-table__empty {
  padding: 2rem 0;
}

.base-table__loading {
  padding: 0.5rem 0;
}
</style>
