<script setup>
/**
 * AppLayout - Layout con topbar opcional, contenido y bottom nav opcional
 * Slots: default (contenido), topbar, bottom-nav
 */
defineProps({
  showTopbar: { type: Boolean, default: true },
  showBottomNav: { type: Boolean, default: true }
})
</script>

<template>
  <div class="app-container min-h-screen flex flex-col bg-[var(--color-bg-quaternary)]">
    <header
      v-if="showTopbar && $slots.topbar"
      class="sticky top-0 z-10 flex items-center h-[var(--height-topbar)] px-4 safe-area-top bg-white border-b border-gray-200"
    >
      <slot name="topbar" />
    </header>

    <main class="app-content flex-1 overflow-y-auto" :class="{ 'pb-[var(--height-bottomnav)]': showBottomNav }">
      <slot />
    </main>

    <nav
      v-if="showBottomNav && $slots['bottom-nav']"
      class="fixed bottom-0 left-0 right-0 z-10 h-[var(--height-bottomnav)] safe-area-bottom bg-white border-t border-gray-200"
    >
      <slot name="bottom-nav" />
    </nav>
  </div>
</template>
