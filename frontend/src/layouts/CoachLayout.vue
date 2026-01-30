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

const showBottomNav = computed(() => !isDesktop.value)
const showTopbar = computed(() => true)
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
        <h2 class="coach-layout__sidebar-title">Panel Coach</h2>
        <nav class="coach-layout__sidebar-nav">
          <RouterLink
            :to="{ name: 'CoachDashboard' }"
            class="coach-layout__sidebar-link"
            :class="{ 'coach-layout__sidebar-link--active': isActive('CoachDashboard') }"
            @click="closeSidebar"
          >
            <svg class="coach-layout__sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
              <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            Dashboard
          </RouterLink>
          <RouterLink
            :to="{ name: 'CoachUsuarios' }"
            class="coach-layout__sidebar-link"
            :class="{ 'coach-layout__sidebar-link--active': isActive('CoachUsuarios') }"
            @click="closeSidebar"
          >
            <svg class="coach-layout__sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
              <circle cx="9" cy="7" r="4"/>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            Usuarios
          </RouterLink>
          <RouterLink
            :to="{ name: 'CoachEjercicios' }"
            class="coach-layout__sidebar-link"
            :class="{ 'coach-layout__sidebar-link--active': isActive('CoachEjercicios') }"
            @click="closeSidebar"
          >
            <svg class="coach-layout__sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M6.5 6.5h11M6.5 6.5v11M6.5 17.5h11M17.5 6.5v11M4 12h4M16 12h4M12 4v4M12 16v4"/>
              <path d="M12 8v8M8 12h8"/>
            </svg>
            Ejercicios
          </RouterLink>
          <RouterLink
            :to="{ name: 'CoachRutinas' }"
            class="coach-layout__sidebar-link"
            :class="{ 'coach-layout__sidebar-link--active': isActive('CoachRutinas') }"
            @click="closeSidebar"
          >
            <svg class="coach-layout__sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
            Rutinas
          </RouterLink>
          <RouterLink
            :to="{ name: 'CoachPerfil' }"
            class="coach-layout__sidebar-link"
            :class="{ 'coach-layout__sidebar-link--active': isActive('CoachPerfil') }"
            @click="closeSidebar"
          >
            <svg class="coach-layout__sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
              <circle cx="12" cy="7" r="4"/>
            </svg>
            Perfil
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
  background: #161616;
  border-right: 1px solid #252525;
  transform: translateX(-100%);
  transition: transform 0.25s ease;
}

.coach-layout__sidebar--open {
  transform: translateX(0);
}

.coach-layout__sidebar-inner {
  padding: 1.25rem 1rem;
  padding-top: max(1.25rem, env(safe-area-inset-top));
}

.coach-layout__sidebar-title {
  font-size: 0.9375rem;
  font-weight: 600;
  color: #fff;
  margin: 0 0 1rem;
  padding: 0 0.5rem;
}

.coach-layout__sidebar-nav {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.coach-layout__sidebar-link {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  border-radius: 12px;
  font-size: 0.875rem;
  font-weight: 500;
  color: #a0a0a0;
  text-decoration: none;
  transition: background 0.2s, color 0.2s;
}

.coach-layout__sidebar-link:hover {
  background: #1e1e1e;
  color: #fff;
}

.coach-layout__sidebar-link--active {
  background: rgba(0, 210, 97, 0.15);
  color: #00D261;
}

.coach-layout__sidebar-icon {
  width: 22px;
  height: 22px;
  flex-shrink: 0;
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
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: transparent;
  border: none;
  color: #fff;
  cursor: pointer;
  transition: background 0.2s;
}

.coach-layout__menu-btn:hover {
  background: #252525;
}

.coach-layout__menu-icon {
  width: 24px;
  height: 24px;
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
