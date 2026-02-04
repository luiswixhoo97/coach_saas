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
  <div class="cliente-layout">
    <!-- Overlay sidebar (desktop) -->
    <Transition name="overlay">
      <div
        v-if="isDesktop && sidebarOpen"
        class="cliente-layout__overlay"
        aria-hidden="true"
        @click="closeSidebar"
      />
    </Transition>

    <!-- Sidebar (desktop) -->
    <aside
      v-if="isDesktop"
      class="cliente-layout__sidebar"
      :class="{ 'cliente-layout__sidebar--open': sidebarOpen }"
      aria-label="Menú principal"
    >
      <div class="cliente-layout__sidebar-inner">
        <div class="cliente-layout__sidebar-header">
          <h2 class="cliente-layout__sidebar-title">Mi Panel</h2>
          <div class="cliente-layout__sidebar-title-accent"></div>
        </div>
        <nav class="cliente-layout__sidebar-nav">
          <RouterLink
            :to="{ name: 'ClienteDashboard' }"
            class="cliente-layout__sidebar-link"
            :class="{ 'cliente-layout__sidebar-link--active': isActive('ClienteDashboard') }"
            @click="closeSidebar"
          >
            <div class="cliente-layout__sidebar-link-bg"></div>
            <div class="cliente-layout__sidebar-link-indicator"></div>
            <div class="cliente-layout__sidebar-icon-wrapper">
              <svg class="cliente-layout__sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
              </svg>
            </div>
            <span class="cliente-layout__sidebar-link-text">Inicio</span>
          </RouterLink>
          <RouterLink
            :to="{ name: 'ClienteRutina' }"
            class="cliente-layout__sidebar-link"
            :class="{ 'cliente-layout__sidebar-link--active': isActive('ClienteRutina') }"
            @click="closeSidebar"
          >
            <div class="cliente-layout__sidebar-link-bg"></div>
            <div class="cliente-layout__sidebar-link-indicator"></div>
            <div class="cliente-layout__sidebar-icon-wrapper">
              <svg class="cliente-layout__sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
              </svg>
            </div>
            <span class="cliente-layout__sidebar-link-text">Rutina</span>
          </RouterLink>
          <RouterLink
            :to="{ name: 'ClienteDieta' }"
            class="cliente-layout__sidebar-link"
            :class="{ 'cliente-layout__sidebar-link--active': isActive('ClienteDieta') }"
            @click="closeSidebar"
          >
            <div class="cliente-layout__sidebar-link-bg"></div>
            <div class="cliente-layout__sidebar-link-indicator"></div>
            <div class="cliente-layout__sidebar-icon-wrapper">
              <svg class="cliente-layout__sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <span class="cliente-layout__sidebar-link-text">Dieta</span>
          </RouterLink>
          <RouterLink
            :to="{ name: 'ClientePerfil' }"
            class="cliente-layout__sidebar-link"
            :class="{ 'cliente-layout__sidebar-link--active': isActive('ClientePerfil') }"
            @click="closeSidebar"
          >
            <div class="cliente-layout__sidebar-link-bg"></div>
            <div class="cliente-layout__sidebar-link-indicator"></div>
            <div class="cliente-layout__sidebar-icon-wrapper">
              <svg class="cliente-layout__sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
              </svg>
            </div>
            <span class="cliente-layout__sidebar-link-text">Perfil</span>
          </RouterLink>
        </nav>
      </div>
    </aside>

    <AppLayout theme="dark" :show-topbar="showTopbar" :show-bottom-nav="showBottomNav">
      <template #topbar>
        <div class="cliente-layout__header">
          <div class="cliente-layout__header-content">
            <button
              v-if="isDesktop"
              type="button"
              class="cliente-layout__menu-btn"
              aria-label="Abrir menú"
              @click="sidebarOpen = !sidebarOpen"
            >
              <svg class="cliente-layout__menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="3" y1="6" x2="21" y2="6"/>
                <line x1="3" y1="12" x2="21" y2="12"/>
                <line x1="3" y1="18" x2="21" y2="18"/>
              </svg>
            </button>
            <h1 class="cliente-layout__header-title">Coach SaaS</h1>
            <div class="cliente-layout__header-accent"></div>
          </div>
        </div>
      </template>

      <RouterView />

    <template #bottom-nav>
      <div class="bottom-nav">
        <div class="bottom-nav__container">
          <RouterLink
            :to="{ name: 'ClienteDashboard' }"
            :class="[
              'bottom-nav__item',
              isActive('ClienteDashboard') ? 'bottom-nav__item--active' : ''
            ]"
          >
            <div class="bottom-nav__icon-wrapper">
              <div class="bottom-nav__icon-bg"></div>
              <svg class="bottom-nav__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
              </svg>
            </div>
            <span class="bottom-nav__label">Inicio</span>
            <div class="bottom-nav__indicator"></div>
          </RouterLink>
          <RouterLink
            :to="{ name: 'ClienteRutina' }"
            :class="[
              'bottom-nav__item',
              isActive('ClienteRutina') ? 'bottom-nav__item--active' : ''
            ]"
          >
            <div class="bottom-nav__icon-wrapper">
              <div class="bottom-nav__icon-bg"></div>
              <svg class="bottom-nav__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
              </svg>
            </div>
            <span class="bottom-nav__label">Rutina</span>
            <div class="bottom-nav__indicator"></div>
          </RouterLink>
          <RouterLink
            :to="{ name: 'ClienteDieta' }"
            :class="[
              'bottom-nav__item',
              isActive('ClienteDieta') ? 'bottom-nav__item--active' : ''
            ]"
          >
            <div class="bottom-nav__icon-wrapper">
              <div class="bottom-nav__icon-bg"></div>
              <svg class="bottom-nav__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <span class="bottom-nav__label">Dieta</span>
            <div class="bottom-nav__indicator"></div>
          </RouterLink>
          <RouterLink
            :to="{ name: 'ClientePerfil' }"
            :class="[
              'bottom-nav__item',
              isActive('ClientePerfil') ? 'bottom-nav__item--active' : ''
            ]"
          >
            <div class="bottom-nav__icon-wrapper">
              <div class="bottom-nav__icon-bg"></div>
              <svg class="bottom-nav__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </div>
            <span class="bottom-nav__label">Perfil</span>
            <div class="bottom-nav__indicator"></div>
          </RouterLink>
        </div>
      </div>
    </template>
    </AppLayout>
  </div>
</template>

<style scoped>
.cliente-layout {
  min-height: 100vh;
  min-height: 100dvh;
  background: #0a0a0a;
}

/* Overlay */
.cliente-layout__overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 30;
  backdrop-filter: blur(2px);
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
.cliente-layout__sidebar {
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

.cliente-layout__sidebar--open {
  transform: translateX(0);
}

.cliente-layout__sidebar-inner {
  padding: 1.5rem 1rem;
  padding-top: max(1.5rem, env(safe-area-inset-top));
}

.cliente-layout__sidebar-header {
  position: relative;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid rgba(37, 37, 37, 0.8);
}

.cliente-layout__sidebar-title {
  font-size: 1rem;
  font-weight: 700;
  color: #fff;
  margin: 0;
  padding: 0 0.5rem;
  position: relative;
  z-index: 1;
  letter-spacing: -0.01em;
}

.cliente-layout__sidebar-title-accent {
  position: absolute;
  bottom: -1px;
  left: 0.5rem;
  width: 40px;
  height: 3px;
  background: linear-gradient(90deg, #00D261 0%, rgba(0, 210, 97, 0.5) 100%);
  border-radius: 2px 2px 0 0;
  box-shadow: 0 2px 8px rgba(0, 210, 97, 0.4);
}

.cliente-layout__sidebar-nav {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.cliente-layout__sidebar-link {
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

.cliente-layout__sidebar-link-bg {
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg, rgba(0, 210, 97, 0.1) 0%, rgba(0, 210, 97, 0.05) 100%);
  opacity: 0;
  transition: opacity 0.3s ease;
  border-radius: 12px;
}

.cliente-layout__sidebar-link-indicator {
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

.cliente-layout__sidebar-icon-wrapper {
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

.cliente-layout__sidebar-icon {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  z-index: 1;
  filter: drop-shadow(0 0 0 rgba(0, 210, 97, 0));
}

.cliente-layout__sidebar-link-text {
  position: relative;
  z-index: 1;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Hover state */
.cliente-layout__sidebar-link:hover {
  color: #fff;
  transform: translateX(4px);
}

.cliente-layout__sidebar-link:hover .cliente-layout__sidebar-link-bg {
  opacity: 1;
}

.cliente-layout__sidebar-link:hover .cliente-layout__sidebar-icon-wrapper {
  background: rgba(255, 255, 255, 0.05);
  transform: scale(1.05);
}

.cliente-layout__sidebar-link:hover .cliente-layout__sidebar-icon {
  transform: scale(1.1);
}

/* Active state */
.cliente-layout__sidebar-link--active {
  color: #00D261;
  transform: translateX(4px);
}

.cliente-layout__sidebar-link--active .cliente-layout__sidebar-link-bg {
  opacity: 1;
  background: linear-gradient(90deg, rgba(0, 210, 97, 0.15) 0%, rgba(0, 210, 97, 0.08) 100%);
}

.cliente-layout__sidebar-link--active .cliente-layout__sidebar-link-indicator {
  transform: translateY(-50%) translateX(0);
  height: 60%;
}

.cliente-layout__sidebar-link--active .cliente-layout__sidebar-icon-wrapper {
  background: linear-gradient(135deg, rgba(0, 210, 97, 0.2) 0%, rgba(0, 210, 97, 0.1) 100%);
  box-shadow: 0 4px 12px rgba(0, 210, 97, 0.2);
}

.cliente-layout__sidebar-link--active .cliente-layout__sidebar-icon {
  color: #00D261;
  filter: drop-shadow(0 0 8px rgba(0, 210, 97, 0.6));
  animation: iconGlow 2s ease-in-out infinite;
}

.cliente-layout__sidebar-link--active .cliente-layout__sidebar-link-text {
  font-weight: 600;
  color: #00D261;
}

.cliente-layout__sidebar-link--active:hover {
  transform: translateX(6px);
}

.cliente-layout__sidebar-link--active:hover .cliente-layout__sidebar-icon {
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
.cliente-layout__header {
  width: 100%;
  position: relative;
}

.cliente-layout__header-content {
  position: relative;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  width: 100%;
}

.cliente-layout__header-title {
  font-size: 1.125rem;
  font-weight: 700;
  color: #fff;
  margin: 0;
  letter-spacing: -0.01em;
  position: relative;
  z-index: 1;
}

.cliente-layout__header-accent {
  position: absolute;
  bottom: -0.5rem;
  left: 0;
  width: 32px;
  height: 3px;
  background: linear-gradient(90deg, #00D261 0%, rgba(0, 210, 97, 0.5) 100%);
  border-radius: 2px;
  box-shadow: 0 2px 8px rgba(0, 210, 97, 0.3);
  opacity: 0.8;
}

.cliente-layout__menu-btn {
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

.cliente-layout__menu-btn::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, rgba(0, 210, 97, 0.1) 0%, rgba(0, 210, 97, 0.05) 100%);
  opacity: 0;
  transition: opacity 0.3s ease;
}

.cliente-layout__menu-btn:hover {
  background: rgba(255, 255, 255, 0.06);
  border-color: rgba(255, 255, 255, 0.1);
  transform: scale(1.05);
}

.cliente-layout__menu-btn:hover::before {
  opacity: 1;
}

.cliente-layout__menu-btn:active {
  transform: scale(0.95);
}

.cliente-layout__menu-icon {
  width: 22px;
  height: 22px;
  position: relative;
  z-index: 1;
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.cliente-layout__menu-btn:hover .cliente-layout__menu-icon {
  transform: rotate(90deg);
}

/* Bottom Nav */
.bottom-nav {
  position: relative;
  height: 100%;
  width: 100%;
  padding: 0.75rem 0.75rem;
  padding-bottom: max(0.75rem, env(safe-area-inset-bottom));
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: visible;
}

.bottom-nav::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 1px;
  background: linear-gradient(
    90deg,
    transparent 0%,
    rgba(37, 37, 37, 0.6) 20%,
    rgba(37, 37, 37, 0.9) 50%,
    rgba(37, 37, 37, 0.6) 80%,
    transparent 100%
  );
}

.bottom-nav__container {
  display: flex;
  align-items: center;
  justify-content: space-around;
  width: 100%;
  max-width: 28rem;
  gap: 0.25rem;
  position: relative;
  overflow: visible;
}

.bottom-nav__item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.375rem;
  padding: 0.5rem 0.5rem;
  border-radius: 16px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  text-decoration: none;
  flex: 1;
  min-width: 0;
  position: relative;
  overflow: visible;
  margin-top: 0.25rem;
}

.bottom-nav__item::after {
  content: '';
  position: absolute;
  inset: 0;
  border-radius: 16px;
  background: linear-gradient(135deg, rgba(0, 210, 97, 0.1) 0%, rgba(0, 210, 97, 0.05) 100%);
  opacity: 0;
  transition: opacity 0.3s ease;
  pointer-events: none;
}

.bottom-nav__item:hover::after {
  opacity: 1;
}

.bottom-nav__item:active {
  transform: scale(0.94);
  transition: transform 0.15s ease;
}

.bottom-nav__icon-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  position: relative;
  flex-shrink: 0;
}

.bottom-nav__icon-bg {
  position: absolute;
  inset: 0;
  border-radius: 12px;
  background: linear-gradient(135deg, rgba(0, 210, 97, 0.18) 0%, rgba(0, 210, 97, 0.1) 100%);
  opacity: 0;
  transform: scale(0.85);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 4px 12px rgba(0, 210, 97, 0.15);
}

.bottom-nav__icon {
  width: 24px;
  height: 24px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  stroke-width: 2.5;
  color: #697586;
  flex-shrink: 0;
  position: relative;
  z-index: 1;
  filter: drop-shadow(0 0 0 rgba(0, 210, 97, 0));
}

.bottom-nav__label {
  font-size: 0.625rem;
  font-weight: 500;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  color: #697586;
  text-align: center;
  line-height: 1.2;
  white-space: nowrap;
  position: relative;
  z-index: 1;
}

.bottom-nav__indicator {
  position: absolute;
  top: -0.25rem;
  left: 50%;
  transform: translateX(-50%) scaleX(0);
  width: 28px;
  height: 3px;
  border-radius: 0 0 2px 2px;
  background: linear-gradient(90deg, transparent 0%, #00D261 30%, #00D261 70%, transparent 100%);
  opacity: 0;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 2px 10px rgba(0, 210, 97, 0.5), 0 0 20px rgba(0, 210, 97, 0.3);
}

/* Estado activo */
.bottom-nav__item--active {
  transform: translateY(-1px);
}

.bottom-nav__item--active .bottom-nav__icon-bg {
  opacity: 1;
  transform: scale(1);
  box-shadow: 0 4px 16px rgba(0, 210, 97, 0.25);
}

.bottom-nav__item--active .bottom-nav__icon {
  width: 26px;
  height: 26px;
  color: #00D261;
  stroke-width: 2.5;
  filter: drop-shadow(0 0 8px rgba(0, 210, 97, 0.6));
  animation: iconPulse 2s ease-in-out infinite;
}

.bottom-nav__item--active .bottom-nav__label {
  color: #00D261;
  font-weight: 600;
  transform: scale(1.05);
}

.bottom-nav__item--active .bottom-nav__indicator {
  opacity: 1;
  transform: translateX(-50%) scaleX(1);
}

.bottom-nav__item--active::after {
  opacity: 1;
}

.bottom-nav__item--active:hover {
  transform: translateY(-1.5px);
}

.bottom-nav__item--active:hover .bottom-nav__icon {
  filter: drop-shadow(0 0 12px rgba(0, 210, 97, 0.7));
}

/* Hover en estado inactivo */
.bottom-nav__item:not(.bottom-nav__item--active):hover {
  transform: translateY(-1px);
}

.bottom-nav__item:not(.bottom-nav__item--active):hover .bottom-nav__icon {
  color: #a0a0a0;
  transform: scale(1.1);
}

.bottom-nav__item:not(.bottom-nav__item--active):hover .bottom-nav__label {
  color: #a0a0a0;
}

/* Animación de pulso para icono activo */
@keyframes iconPulse {
  0%, 100% {
    filter: drop-shadow(0 0 8px rgba(0, 210, 97, 0.6));
  }
  50% {
    filter: drop-shadow(0 0 14px rgba(0, 210, 97, 0.9));
  }
}

/* Mejoras adicionales de hover */
.bottom-nav__item:hover .bottom-nav__icon-bg {
  opacity: 0.3;
  transform: scale(0.95);
}

.bottom-nav__item--active:hover .bottom-nav__icon-bg {
  opacity: 1;
  transform: scale(1.05);
  box-shadow: 0 6px 20px rgba(0, 210, 97, 0.3);
}
</style>
