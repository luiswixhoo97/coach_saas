import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: () => import('@/views/HomeView.vue')
  },
  // Ruta pública de registro
  {
    path: '/registro/:token',
    name: 'RegistroPublico',
    component: () => import('@/views/RegistroPublicoView.vue'),
    meta: { requiresGuest: false } // Público, sin restricción
  },
  // Rutas de autenticación
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/auth/LoginView.vue'),
    meta: { requiresGuest: true }
  },
  // Rutas del Coach (layout con nav móvil + sidebar desktop)
  {
    path: '/coach',
    component: () => import('@/layouts/CoachLayout.vue'),
    meta: { requiresAuth: true, rol: 'coach' },
    children: [
      {
        path: '',
        name: 'CoachDashboard',
        component: () => import('@/views/coach/DashboardView.vue')
      },
      {
        path: 'usuarios',
        name: 'CoachUsuarios',
        component: () => import('@/views/coach/UsuariosView.vue')
      },
      {
        path: 'ejercicios',
        name: 'CoachEjercicios',
        component: () => import('@/views/coach/EjerciciosView.vue')
      },
      {
        path: 'rutinas',
        name: 'CoachRutinas',
        component: () => import('@/views/coach/RutinasView.vue')
      },
      {
        path: 'perfil',
        name: 'CoachPerfil',
        component: () => import('@/views/coach/PerfilView.vue')
      },
      {
        path: 'chat',
        name: 'CoachChat',
        component: () => import('@/views/coach/ChatView.vue')
      },
      {
        path: 'chat/:id',
        name: 'CoachChatDetalle',
        component: () => import('@/views/coach/ChatView.vue')
      },
      {
        path: 'formularios',
        name: 'CoachFormularios',
        component: () => import('@/views/coach/FormulariosView.vue')
      },
      {
        path: 'parametros',
        name: 'CoachParametros',
        component: () => import('@/views/coach/ParametrosView.vue')
      },
      {
        path: 'clientes/:id/parametros',
        name: 'CoachParametrosCliente',
        component: () => import('@/views/coach/ParametrosClienteView.vue')
      }
    ]
  },
  // Rutas del Cliente
  {
    path: '/cliente',
    component: () => import('@/layouts/ClienteLayout.vue'),
    meta: { requiresAuth: true, rol: 'cliente' },
    children: [
      {
        path: '',
        name: 'ClienteDashboard',
        component: () => import('@/views/cliente/DashboardView.vue')
      },
      {
        path: 'perfil',
        name: 'ClientePerfil',
        component: () => import('@/views/cliente/PerfilView.vue')
      },
      {
        path: 'rutina',
        name: 'ClienteRutina',
        component: () => import('@/views/cliente/RutinaView.vue')
      },
      {
        path: 'dieta',
        name: 'ClienteDieta',
        component: () => import('@/views/cliente/DietaView.vue')
      },
      {
        path: 'chat',
        name: 'ClienteChat',
        component: () => import('@/views/cliente/ChatView.vue')
      },
      {
        path: 'formulario-pendiente',
        name: 'ClienteFormularioPendiente',
        component: () => import('@/views/cliente/FormularioPendienteView.vue')
      }
    ]
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
