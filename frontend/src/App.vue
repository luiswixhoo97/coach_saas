<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { RouterView } from 'vue-router'

const splashVisible = ref(true)

onMounted(() => {
  const duration = 1800
  const timer = setTimeout(() => {
    splashVisible.value = false
    clearTimeout(timer)
  }, duration)
})
</script>

<template>
  <div class="app-root">
    <!-- Splash: transición al abrir la app (favicon) -->
    <Transition name="splash-fade">
      <div v-show="splashVisible" class="splash" aria-hidden="true">
        <div class="splash__inner">
          <img src="/favicon.svg" alt="" class="splash__logo" width="120" height="120" />
          <span class="splash__name">Coach SaaS</span>
        </div>
      </div>
    </Transition>

    <!-- Contenido de la app -->
    <RouterView />
  </div>
</template>

<style scoped>
.app-root {
  position: relative;
  min-height: 100vh;
  min-height: 100dvh;
}

.splash {
  position: fixed;
  inset: 0;
  z-index: 9999;
  background: #0a0a0a;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: env(safe-area-inset-top) env(safe-area-inset-right) env(safe-area-inset-bottom) env(safe-area-inset-left);
}

.splash__inner {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.splash__logo {
  width: 7.5rem;
  height: 7.5rem;
  object-fit: contain;
  animation: splash-pulse 1.2s ease-in-out infinite;
}

.splash__name {
  font-size: 1.125rem;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.9);
  letter-spacing: 0.02em;
}

@keyframes splash-pulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.85; transform: scale(1.02); }
}

/* Transición: salida del splash */
.splash-fade-leave-active {
  transition: opacity 0.4s ease;
}

.splash-fade-leave-to {
  opacity: 0;
}
</style>
