<script setup>
/**
 * AppLayout - Layout con topbar opcional, contenido y bottom nav opcional
 * Slots: default (contenido), topbar, bottom-nav
 * theme: 'light' | 'dark' — aplica fondos y bordes según el diseño
 */
const props = defineProps({
  showTopbar: { type: Boolean, default: true },
  showBottomNav: { type: Boolean, default: true },
  theme: {
    type: String,
    default: 'light',
    validator: (v) => ['light', 'dark'].includes(v)
  }
})

const isDark = () => props.theme === 'dark'
</script>

<template>
  <div
    class="app-container min-h-screen flex flex-col"
    :class="isDark() ? 'app-container--dark' : 'bg-[var(--color-bg-quaternary)]'"
  >
    <header
      v-if="showTopbar && $slots.topbar"
      class="app-header sticky top-0 z-20 flex items-center h-[var(--height-topbar)] px-4 safe-area-top"
      :class="isDark() ? 'app-header--dark' : 'bg-white border-b border-gray-200'"
    >
      <slot name="topbar" />
    </header>

    <main
      class="app-content flex-1 overflow-y-auto"
      :class="[
        showBottomNav ? 'pb-[var(--height-bottomnav)]' : '',
        isDark() ? 'app-content--dark' : ''
      ]"
    >
      <slot />
    </main>

    
    <nav
      v-if="showBottomNav && $slots['bottom-nav']"
      class="app-bottom-nav fixed bottom-0 left-0 right-0 z-20 h-[var(--height-bottomnav)] safe-area-bottom"
      :class="isDark() ? 'app-bottom-nav--dark' : 'bg-white border-t border-gray-200'"
    >
      <slot name="bottom-nav" />
    </nav>
  </div>
</template>

<style scoped>
.app-container--dark {
  background: #0a0a0a;
}

.app-content--dark {
  background: #0a0a0a;
}

.app-header--dark {
  background: #161616;
  border-bottom: 1px solid #252525;
}

.app-bottom-nav--dark {
  background: #161616;
  border-top: 1px solid #252525;
  overflow: visible;
}

.app-bottom-nav {
  overflow: visible;
}
</style>
