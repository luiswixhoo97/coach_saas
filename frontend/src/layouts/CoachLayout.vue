<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import AppLayout from '@/layouts/AppLayout.vue'

const route = useRoute()
const sidebarOpen = ref(false)
const isDesktop = ref(false)

const MOBILE_BREAKPOINT = 768

function checkDesktop() {
  isDesktop.value = window.innerWidth >= MOBILE_BREAKPOINT
  if (isDesktop.value) sidebarOpen.value = false
}

function isActive(name) {
  return route.name === name
}

function closeSidebar() {
  sidebarOpen.value = false
}

onMounted(() => {
  checkDesktop()
  window.addEventListener('resize', checkDesktop)
})

onUnmounted(() => {
  window.removeEventListener('resize', checkDesktop)
})

// Ocultar bottomnav y topbar en móvil cuando estamos dentro de un chat (WhatsApp-like)
const enChatMovil = computed(() => {
  return !isDesktop.value && route.name === 'CoachChatDetalle'
})

const showBottomNav = computed(() => {
  if (isDesktop.value) return false
  if (enChatMovil.value) return false
  return true
})

const showTopbar = computed(() => {
  if (enChatMovil.value) return false
  return true
})
</script>

<template>
  <div class="coach-layout">
    <!-- Overlay sidebar (desktop) -->
    <Transition name="overlay">
      <div
        v-if="isDesktop && sidebarOpen"
        class="coach-layout__overlay"
        aria-hidden="true"
        @click="closeSidebar"
      />
    </Transition>

    <!-- Sidebar (desktop) -->
    <aside
      v-if="isDesktop"
      class="coach-layout__sidebar"
      :class="{ 'coach-layout__sidebar--open': sidebarOpen }"
      aria-label="Menú principal"
    >
      <div class="coach-layout__sidebar-inner">
        <div class="coach-layout__sidebar-header">
          <h2 class="coach-layout__sidebar-title">Panel Coach</h2>
          <div class="coach-layout__sidebar-title-accent"></div>
        </div>
        <nav class="coach-layout__sidebar-nav">
          <RouterLink
            :to="{ name: 'CoachDashboard' }"
            class="coach-layout__sidebar-link"
            :class="{ 'coach-layout__sidebar-link--active': isActive('CoachDashboard') }"
            @click="closeSidebar"
          >
            <div class="coach-layout__sidebar-link-bg"></div>
            <div class="coach-layout__sidebar-link-indicator"></div>
            <div class="coach-layout__sidebar-icon-wrapper">
              <svg class="coach-layout__sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
              </svg>
            </div>
            <span class="coach-layout__sidebar-link-text">Dashboard</span>
          </RouterLink>
          <RouterLink
            :to="{ name: 'CoachUsuarios' }"
            class="coach-layout__sidebar-link"
            :class="{ 'coach-layout__sidebar-link--active': isActive('CoachUsuarios') }"
            @click="closeSidebar"
          >
            <div class="coach-layout__sidebar-link-bg"></div>
            <div class="coach-layout__sidebar-link-indicator"></div>
            <div class="coach-layout__sidebar-icon-wrapper">
              <svg class="coach-layout__sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
              </svg>
            </div>
            <span class="coach-layout__sidebar-link-text">Usuarios</span>
          </RouterLink>
          <RouterLink
            :to="{ name: 'CoachEjercicios' }"
            class="coach-layout__sidebar-link"
            :class="{ 'coach-layout__sidebar-link--active': isActive('CoachEjercicios') }"
            @click="closeSidebar"
          >
            <div class="coach-layout__sidebar-link-bg"></div>
            <div class="coach-layout__sidebar-link-indicator"></div>
            <div class="coach-layout__sidebar-icon-wrapper">
              <svg class="coach-layout__sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M6.5 6.5h11M6.5 6.5v11M6.5 17.5h11M17.5 6.5v11M4 12h4M16 12h4M12 4v4M12 16v4"/>
                <path d="M12 8v8M8 12h8"/>
              </svg>
            </div>
            <span class="coach-layout__sidebar-link-text">Ejercicios</span>
          </RouterLink>
          <RouterLink
            :to="{ name: 'CoachRutinas' }"
            class="coach-layout__sidebar-link"
            :class="{ 'coach-layout__sidebar-link--active': isActive('CoachRutinas') }"
            @click="closeSidebar"
          >
            <div class="coach-layout__sidebar-link-bg"></div>
            <div class="coach-layout__sidebar-link-indicator"></div>
            <div class="coach-layout__sidebar-icon-wrapper">
              <svg class="coach-layout__sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
              </svg>
            </div>
            <span class="coach-layout__sidebar-link-text">Rutinas</span>
          </RouterLink>
          <RouterLink
            :to="{ name: 'CoachChat' }"
            class="coach-layout__sidebar-link"
            :class="{ 'coach-layout__sidebar-link--active': isActive('CoachChat') || isActive('CoachChatDetalle') }"
            @click="closeSidebar"
          >
            <div class="coach-layout__sidebar-link-bg"></div>
            <div class="coach-layout__sidebar-link-indicator"></div>
            <div class="coach-layout__sidebar-icon-wrapper">
              <svg class="coach-layout__sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
              </svg>
            </div>
            <span class="coach-layout__sidebar-link-text">Chat</span>
          </RouterLink>
          <RouterLink
            :to="{ name: 'CoachPerfil' }"
            class="coach-layout__sidebar-link"
            :class="{ 'coach-layout__sidebar-link--active': isActive('CoachPerfil') }"
            @click="closeSidebar"
          >
            <div class="coach-layout__sidebar-link-bg"></div>
            <div class="coach-layout__sidebar-link-indicator"></div>
            <div class="coach-layout__sidebar-icon-wrapper">
              <svg class="coach-layout__sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
              </svg>
            </div>
            <span class="coach-layout__sidebar-link-text">Perfil</span>
          </RouterLink>
        </nav>
      </div>
    </aside>

    <AppLayout theme="dark" :show-topbar="showTopbar" :show-bottom-nav="showBottomNav">
      <template #topbar>
        <div class="coach-layout__header">
          <button
            v-if="isDesktop"
            type="button"
            class="coach-layout__menu-btn"
            aria-label="Abrir menú"
            @click="sidebarOpen = !sidebarOpen"
          >
            <svg class="coach-layout__menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="3" y1="6" x2="21" y2="6"/>
              <line x1="3" y1="12" x2="21" y2="12"/>
              <line x1="3" y1="18" x2="21" y2="18"/>
            </svg>
          </button>
          <h1 class="coach-layout__header-title">Panel Coach</h1>
        </div>
      </template>

      <RouterView />

      <template #bottom-nav>
        <div class="coach-layout__bottom-nav">
          <RouterLink
            :to="{ name: 'CoachDashboard' }"
            class="coach-layout__nav-item"
            :class="{ 'coach-layout__nav-item--active': isActive('CoachDashboard') }"
          >
            <svg class="coach-layout__nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
              <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            <span class="coach-layout__nav-label">Dashboard</span>
          </RouterLink>
          <RouterLink
            :to="{ name: 'CoachUsuarios' }"
            class="coach-layout__nav-item"
            :class="{ 'coach-layout__nav-item--active': isActive('CoachUsuarios') }"
          >
            <svg class="coach-layout__nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
              <circle cx="9" cy="7" r="4"/>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            <span class="coach-layout__nav-label">Usuarios</span>
          </RouterLink>
          <RouterLink
            :to="{ name: 'CoachEjercicios' }"
            class="coach-layout__nav-item"
            :class="{ 'coach-layout__nav-item--active': isActive('CoachEjercicios') }"
          >
            <svg class="coach-layout__nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M6.5 6.5h11M6.5 6.5v11M6.5 17.5h11M17.5 6.5v11M4 12h4M16 12h4M12 4v4M12 16v4"/>
              <path d="M12 8v8M8 12h8"/>
            </svg>
            <span class="coach-layout__nav-label">Ejercicios</span>
          </RouterLink>
          <RouterLink
            :to="{ name: 'CoachRutinas' }"
            class="coach-layout__nav-item"
            :class="{ 'coach-layout__nav-item--active': isActive('CoachRutinas') }"
          >
            <svg class="coach-layout__nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
            <span class="coach-layout__nav-label">Rutinas</span>
          </RouterLink>
          <RouterLink
            :to="{ name: 'CoachChat' }"
            class="coach-layout__nav-item"
            :class="{ 'coach-layout__nav-item--active': isActive('CoachChat') || isActive('CoachChatDetalle') }"
          >
            <svg class="coach-layout__nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            <span class="coach-layout__nav-label">Chat</span>
          </RouterLink>
          <RouterLink
            :to="{ name: 'CoachPerfil' }"
            class="coach-layout__nav-item"
            :class="{ 'coach-layout__nav-item--active': isActive('CoachPerfil') }"
          >
            <svg class="coach-layout__nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
              <circle cx="12" cy="7" r="4"/>
            </svg>
            <span class="coach-layout__nav-label">Perfil</span>
          </RouterLink>
        </div>
      </template>
    </AppLayout>
  </div>
</template>

<style scoped>
.coach-layout {
  min-height: 100vh;
  min-height: 100dvh;
  background: #0a0a0a;
}

/* Overlay */
.coach-layout__overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 30;
}

.overlay-enter-active,
.overlay-leave-active {
  transition: opacity 0.2s ease;
}
.overlay-enter-from,
.overlay-leave-to {
  opacity: 0;
}

/* Sidebar desktop */
.coach-layout__sidebar {
  position: fixed;
  top: 0;
  left: 0;
  z-index: 40;
  width: 280px;
  max-width: 85vw;
  height: 100vh;
  height: 100dvh;
  background: linear-gradient(180deg, #161616 0%, #1a1a1a 100%);
  border-right: 1px solid #252525;
  box-shadow: 4px 0 24px rgba(0, 0, 0, 0.5);
  transform: translateX(-100%);
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  overflow-y: auto;
  overflow-x: hidden;
}

.coach-layout__sidebar--open {
  transform: translateX(0);
}

.coach-layout__sidebar-inner {
  padding: 1.5rem 1rem;
  padding-top: max(1.5rem, env(safe-area-inset-top));
}

.coach-layout__sidebar-header {
  position: relative;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid rgba(37, 37, 37, 0.8);
}

.coach-layout__sidebar-title {
  font-size: 1rem;
  font-weight: 700;
  color: #fff;
  margin: 0;
  padding: 0 0.5rem;
  position: relative;
  z-index: 1;
  letter-spacing: -0.01em;
}

.coach-layout__sidebar-title-accent {
  position: absolute;
  bottom: -1px;
  left: 0.5rem;
  width: 40px;
  height: 3px;
  background: linear-gradient(90deg, #00D261 0%, rgba(0, 210, 97, 0.5) 100%);
  border-radius: 2px 2px 0 0;
  box-shadow: 0 2px 8px rgba(0, 210, 97, 0.4);
}

.coach-layout__sidebar-nav {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.coach-layout__sidebar-link {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  padding: 0.875rem 1rem;
  border-radius: 12px;
  font-size: 0.875rem;
  font-weight: 500;
  color: #a0a0a0;
  text-decoration: none;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

.coach-layout__sidebar-link-bg {
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg, rgba(0, 210, 97, 0.1) 0%, rgba(0, 210, 97, 0.05) 100%);
  opacity: 0;
  transition: opacity 0.3s ease;
  border-radius: 12px;
}

.coach-layout__sidebar-link-indicator {
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%) translateX(-100%);
  width: 4px;
  height: 0;
  background: linear-gradient(180deg, #00D261 0%, rgba(0, 210, 97, 0.8) 100%);
  border-radius: 0 2px 2px 0;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 0 12px rgba(0, 210, 97, 0.5);
}

.coach-layout__sidebar-icon-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.02);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  z-index: 1;
  flex-shrink: 0;
}

.coach-layout__sidebar-icon {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  z-index: 1;
  filter: drop-shadow(0 0 0 rgba(0, 210, 97, 0));
}

.coach-layout__sidebar-link-text {
  position: relative;
  z-index: 1;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Hover state */
.coach-layout__sidebar-link:hover {
  color: #fff;
  transform: translateX(4px);
}

.coach-layout__sidebar-link:hover .coach-layout__sidebar-link-bg {
  opacity: 1;
}

.coach-layout__sidebar-link:hover .coach-layout__sidebar-icon-wrapper {
  background: rgba(255, 255, 255, 0.05);
  transform: scale(1.05);
}

.coach-layout__sidebar-link:hover .coach-layout__sidebar-icon {
  transform: scale(1.1);
}

/* Active state */
.coach-layout__sidebar-link--active {
  color: #00D261;
  transform: translateX(4px);
}

.coach-layout__sidebar-link--active .coach-layout__sidebar-link-bg {
  opacity: 1;
  background: linear-gradient(90deg, rgba(0, 210, 97, 0.15) 0%, rgba(0, 210, 97, 0.08) 100%);
}

.coach-layout__sidebar-link--active .coach-layout__sidebar-link-indicator {
  transform: translateY(-50%) translateX(0);
  height: 60%;
}

.coach-layout__sidebar-link--active .coach-layout__sidebar-icon-wrapper {
  background: linear-gradient(135deg, rgba(0, 210, 97, 0.2) 0%, rgba(0, 210, 97, 0.1) 100%);
  box-shadow: 0 4px 12px rgba(0, 210, 97, 0.2);
}

.coach-layout__sidebar-link--active .coach-layout__sidebar-icon {
  color: #00D261;
  filter: drop-shadow(0 0 8px rgba(0, 210, 97, 0.6));
  animation: iconGlow 2s ease-in-out infinite;
}

.coach-layout__sidebar-link--active .coach-layout__sidebar-link-text {
  font-weight: 600;
  color: #00D261;
}

.coach-layout__sidebar-link--active:hover {
  transform: translateX(6px);
}

.coach-layout__sidebar-link--active:hover .coach-layout__sidebar-icon {
  filter: drop-shadow(0 0 12px rgba(0, 210, 97, 0.8));
}

/* Animación de glow para icono activo */
@keyframes iconGlow {
  0%, 100% {
    filter: drop-shadow(0 0 8px rgba(0, 210, 97, 0.6));
  }
  50% {
    filter: drop-shadow(0 0 12px rgba(0, 210, 97, 0.9));
  }
}

/* Header */
.coach-layout__header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  width: 100%;
}

.coach-layout__menu-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.05);
  color: #fff;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

.coach-layout__menu-btn::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, rgba(0, 210, 97, 0.1) 0%, rgba(0, 210, 97, 0.05) 100%);
  opacity: 0;
  transition: opacity 0.3s ease;
}

.coach-layout__menu-btn:hover {
  background: rgba(255, 255, 255, 0.06);
  border-color: rgba(255, 255, 255, 0.1);
  transform: scale(1.05);
}

.coach-layout__menu-btn:hover::before {
  opacity: 1;
}

.coach-layout__menu-btn:active {
  transform: scale(0.95);
}

.coach-layout__menu-icon {
  width: 22px;
  height: 22px;
  position: relative;
  z-index: 1;
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.coach-layout__menu-btn:hover .coach-layout__menu-icon {
  transform: rotate(90deg);
}

.coach-layout__header-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #fff;
  margin: 0;
}

/* Bottom nav mobile */
.coach-layout__bottom-nav {
  display: flex;
  align-items: center;
  justify-content: space-around;
  height: 100%;
  max-width: 24rem;
  margin: 0 auto;
}

.coach-layout__nav-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.25rem;
  flex: 1;
  padding: 0.5rem;
  font-size: 0.6875rem;
  font-weight: 500;
  color: #697586;
  text-decoration: none;
  transition: color 0.2s;
}

.coach-layout__nav-item:hover {
  color: #a0a0a0;
}

.coach-layout__nav-item--active {
  color: #00D261;
}

.coach-layout__nav-icon {
  width: 24px;
  height: 24px;
}

.coach-layout__nav-label {
  text-transform: uppercase;
  letter-spacing: 0.02em;
}
</style>
