import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: () => import('@/views/HomeView.vue')
  },
  // Rutas de autenticación
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/auth/LoginView.vue'),
    meta: { requiresGuest: true }
  },
  // Rutas del Coach (placeholder por ahora)
  {
    path: '/coach',
    name: 'CoachDashboard',
    component: () => import('@/views/coach/DashboardView.vue'),
    meta: { requiresAuth: true, rol: 'coach' }
  },
  // Rutas del Cliente (placeholder por ahora)
  {
    path: '/cliente',
    name: 'ClienteDashboard',
    component: () => import('@/views/cliente/DashboardView.vue'),
    meta: { requiresAuth: true, rol: 'cliente' }
  },
  // Ruta 404
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    redirect: '/'
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Guards de navegación
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()
  
  // Inicializar sesión si no se ha hecho
  if (!authStore.autenticado) {
    authStore.inicializarSesion()
  }

  const requiresAuth = to.meta.requiresAuth
  const requiresGuest = to.meta.requiresGuest
  const rolRequerido = to.meta.rol

  // Ruta requiere autenticación
  if (requiresAuth && !authStore.autenticado) {
    next({ name: 'Login' })
    return
  }

  // Ruta requiere ser invitado (no autenticado)
  if (requiresGuest && authStore.autenticado) {
    // Redirigir según el rol
    if (authStore.esCoach) {
      next({ name: 'CoachDashboard' })
    } else if (authStore.esCliente) {
      next({ name: 'ClienteDashboard' })
    } else {
      next({ name: 'Home' })
    }
    return
  }

  // Verificar rol si es necesario
  if (rolRequerido && authStore.rol !== rolRequerido) {
    next({ name: 'Home' })
    return
  }

  next()
})

export default router
