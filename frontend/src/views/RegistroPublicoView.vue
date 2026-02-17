<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApi } from '@/composables/useApi'
import FormularioDinamico from '@/components/FormularioDinamico.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'

const route = useRoute()
const router = useRouter()
const api = useApi()

const estado = ref('loading') // loading, planes, formulario, exito, error
const datosCoach = ref(null)
const planes = ref([])
const formularioRegistro = ref(null)
const planSeleccionado = ref(null)
const metodoPago = ref('transferencia')
const nombre = ref('')
const apellidoPaterno = ref('')
const apellidoMaterno = ref('')
const fechaNacimiento = ref('')
const altura = ref('')
const sexo = ref('')
const objetivo = ref('')
const email = ref('')
const password = ref('')
const respuestasFormulario = ref({})

const objetivosDisponibles = [
  'Ganar masa muscular',
  'Bajar de peso',
  'Recomposición corporal',
  'Mejorar condición física',
  'Mantenimiento'
]
const credenciales = ref(null)
const error = ref(null)
const procesando = ref(false)

onMounted(async () => {
  await cargarDatos()
})

async function cargarDatos() {
  try {
    estado.value = 'loading'
    const token = route.params.token
    
    const response = await api.get(`/registro/${token}`)
    datosCoach.value = response.datos.coach
    planes.value = response.datos.planes
    formularioRegistro.value = response.datos.formulario_registro
    
    estado.value = 'planes'
  } catch (err) {
    error.value = err.response?.data?.mensaje || 'Error al cargar datos del coach'
    estado.value = 'error'
  }
}

function seleccionarPlan(plan) {
  planSeleccionado.value = plan
}

async function continuarPago() {
  if (metodoPago.value === 'stripe') {
    error.value = 'Pago con tarjeta no disponible por el momento. Por favor, selecciona transferencia bancaria.'
    return
  }
  
  // Con transferencia bancaria, siempre mostrar formulario de registro
  // (ya sea el formulario dinámico del coach o los campos básicos)
  estado.value = 'formulario'
}

async function enviarRegistro() {
  try {
    procesando.value = true
    error.value = null
    
    // Convertir respuestas de objeto a array indexado
    let respuestasArray = null
    if (Object.keys(respuestasFormulario.value).length > 0) {
      respuestasArray = []
      // Ordenar por índice y convertir a array
      const indices = Object.keys(respuestasFormulario.value)
        .map(Number)
        .sort((a, b) => a - b)
      indices.forEach(index => {
        respuestasArray.push(respuestasFormulario.value[index])
      })
    }
    
    // Validar que todos los campos requeridos estén presentes
    if (!nombre.value || !apellidoPaterno.value || !fechaNacimiento.value || !altura.value || !sexo.value || !objetivo.value || !email.value || !password.value || !planSeleccionado.value) {
      error.value = 'Por favor completa todos los campos requeridos'
      procesando.value = false
      return
    }
    
    if (password.value.length < 8) {
      error.value = 'La contraseña debe tener al menos 8 caracteres'
      procesando.value = false
      return
    }
    
    const payload = {
      coach_token: route.params.token,
      plan_id: planSeleccionado.value.id,
      nombre: nombre.value.trim(),
      apellido_paterno: apellidoPaterno.value.trim(),
      apellido_materno: apellidoMaterno.value.trim() || null,
      fecha_nacimiento: fechaNacimiento.value,
      altura: parseFloat(altura.value) || null,
      sexo: sexo.value,
      objetivo: objetivo.value,
      email: email.value.trim(),
      password: password.value,
      metodo_pago: metodoPago.value,
      respuestas_formulario: respuestasArray,
    }
    
    const response = await api.post('/registro', payload)
    credenciales.value = response.datos
    estado.value = 'exito'
  } catch (err) {
    // Mostrar errores de validación detallados
    if (err.response?.status === 422) {
      const data = err.response.data
      // Laravel puede devolver errores en diferentes formatos
      if (data.errors) {
        // Formato estándar de Laravel: { errors: { campo: ["mensaje"] } }
        const errores = data.errors
        const mensajes = Object.values(errores).flat()
        error.value = mensajes.join('. ')
      } else if (data.errores) {
        // Formato alternativo
        const errores = data.errores
        const mensajes = Object.values(errores).flat()
        error.value = mensajes.join('. ')
      } else if (data.mensaje) {
        error.value = data.mensaje
      } else {
        error.value = 'Error de validación. Por favor verifica los datos ingresados.'
      }
    } else {
      error.value = err.response?.data?.mensaje || 'Error al crear cuenta'
    }
    procesando.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 py-8 px-4">
    <div class="max-w-2xl mx-auto">
      <!-- Paso 1: Cargando -->
      <div v-if="estado === 'loading'" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mb-4"></div>
        <p class="text-gray-600">Cargando información...</p>
      </div>
      
      <!-- Paso 2: Mostrar Planes -->
      <div v-else-if="estado === 'planes'" class="bg-white rounded-lg shadow-sm p-6 space-y-6">
        <div class="text-center">
          <h1 class="text-2xl font-bold text-gray-900 mb-2">Registro</h1>
          <p class="text-gray-600">{{ datosCoach?.nombre }}</p>
        </div>
        
        <div>
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Selecciona tu plan</h2>
          
          <div class="grid gap-4 md:grid-cols-2">
            <div
              v-for="plan in planes"
              :key="plan.id"
              :class="[
                'p-4 rounded-lg border-2 cursor-pointer transition-all',
                planSeleccionado?.id === plan.id
                  ? 'border-primary-600 bg-primary-50'
                  : 'border-gray-200 hover:border-gray-300'
              ]"
              @click="seleccionarPlan(plan)"
            >
              <h3 class="font-semibold text-gray-900 mb-1">{{ plan.nombre }}</h3>
              <p class="text-2xl font-bold text-primary-600 mb-1">${{ plan.precio }}</p>
              <p class="text-sm text-gray-600">{{ plan.duracion_dias }} días</p>
            </div>
          </div>
        </div>
        
        <div v-if="planSeleccionado" class="space-y-4 pt-4 border-t">
          <h3 class="font-semibold text-gray-900">Método de pago</h3>
          
          <!-- Opción Stripe (Deshabilitada) -->
          <label class="flex items-center gap-3 p-4 rounded-lg border-2 border-gray-200 bg-gray-50 cursor-not-allowed opacity-60">
            <input
              type="radio"
              value="stripe"
              v-model="metodoPago"
              disabled
              class="w-4 h-4"
            />
            <div class="flex-1">
              <span class="font-medium text-gray-700">Pago con Tarjeta (Stripe)</span>
              <span class="ml-2 px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded">Próximamente</span>
            </div>
          </label>
          
          <!-- Opción Transferencia (Funcional) -->
          <label class="flex items-center gap-3 p-4 rounded-lg border-2 border-gray-200 hover:border-primary-500 cursor-pointer transition-colors">
            <input
              type="radio"
              value="transferencia"
              v-model="metodoPago"
              class="w-4 h-4 text-primary-600"
            />
            <span class="font-medium text-gray-700">Transferencia Bancaria</span>
          </label>
          
          <BaseButton
            @click="continuarPago"
            :disabled="!metodoPago"
            variant="primary"
            block
            class="mt-4"
          >
            Continuar
          </BaseButton>
        </div>
      </div>
      
      <!-- Paso 3: Formulario de Registro -->
      <div v-else-if="estado === 'formulario'" class="bg-white rounded-lg shadow-sm p-6 space-y-6">
        <div class="text-center mb-4">
          <h2 class="text-xl font-bold text-gray-900">Completa tu información</h2>
          <div class="mt-2 space-y-1">
            <p class="text-sm text-gray-600">
              Plan: <strong>{{ planSeleccionado?.nombre }}</strong> - ${{ planSeleccionado?.precio }}
            </p>
            <p class="text-sm text-gray-600">
              Método de pago: <strong>Transferencia Bancaria</strong>
            </p>
          </div>
        </div>
        
        <form @submit.prevent="enviarRegistro" class="space-y-6">
          <!-- Formulario dinámico si existe -->
          <div v-if="formularioRegistro">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ formularioRegistro.nombre }}</h3>
            <FormularioDinamico
              :preguntas="formularioRegistro.preguntas"
              v-model="respuestasFormulario"
            />
          </div>
          
          <!-- Campos básicos siempre requeridos -->
          <div>
            <h3 v-if="formularioRegistro" class="text-lg font-semibold text-gray-900 mb-4 mt-6">Datos personales</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <BaseInput
                v-model="nombre"
                type="text"
                label="Nombre"
                placeholder="Tu nombre"
                required
              />
              
              <BaseInput
                v-model="apellidoPaterno"
                type="text"
                label="Apellido Paterno"
                placeholder="Tu apellido paterno"
                required
              />
            </div>
            
            <BaseInput
              v-model="apellidoMaterno"
              type="text"
              label="Apellido Materno"
              placeholder="Tu apellido materno (opcional)"
            />
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
              <BaseInput
                v-model="fechaNacimiento"
                type="date"
                label="Fecha de Nacimiento"
                placeholder="DD/MM/YYYY"
                required
              />
              
              <BaseInput
                v-model="altura"
                type="number"
                label="Altura (cm)"
                placeholder="Ej: 175"
                :min="0"
                :max="300"
                required
              />
            </div>
            
            <div class="mt-4">
              <label class="block text-sm font-medium text-gray-700 mb-1.5">
                Sexo <span class="text-danger-500">*</span>
              </label>
              <select
                v-model="sexo"
                required
                class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-900 placeholder-gray-400 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-0 focus:border-primary-500 focus:ring-primary-500/20"
              >
                <option value="">Selecciona tu sexo</option>
                <option value="masculino">Masculino</option>
                <option value="femenino">Femenino</option>
                <option value="otro">Otro</option>
              </select>
            </div>
            
            <div class="mt-4">
              <label class="block text-sm font-medium text-gray-700 mb-1.5">
                Objetivo <span class="text-danger-500">*</span>
              </label>
              <select
                v-model="objetivo"
                required
                class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-900 placeholder-gray-400 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-0 focus:border-primary-500 focus:ring-primary-500/20"
              >
                <option value="">Selecciona tu objetivo</option>
                <option v-for="obj in objetivosDisponibles" :key="obj" :value="obj">
                  {{ obj }}
                </option>
              </select>
            </div>
            
            <h3 class="text-lg font-semibold text-gray-900 mb-4 mt-6">Datos de acceso</h3>
            
            <BaseInput
              v-model="email"
              type="email"
              label="Email"
              placeholder="tu@email.com"
              required
            />
            
            <BaseInput
              v-model="password"
              type="password"
              label="Contraseña"
              placeholder="Mínimo 8 caracteres"
              required
              :minlength="8"
            />
          </div>
          
          <!-- Información sobre transferencia -->
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <p class="text-sm text-blue-800">
              <strong>Nota:</strong> Después de crear tu cuenta, deberás realizar el pago por transferencia bancaria. 
              El coach activará tu cuenta una vez que verifique el pago.
            </p>
          </div>
          
          <div v-if="error" class="bg-danger-50 border border-danger-200 text-danger-700 px-4 py-3 rounded-lg text-sm">
            {{ error }}
          </div>
          
          <div class="flex gap-3">
            <BaseButton
              type="button"
              @click="estado = 'planes'"
              variant="secondary"
              class="flex-1"
            >
              Volver
            </BaseButton>
            <BaseButton
              type="submit"
              :disabled="procesando"
              variant="primary"
              :loading="procesando"
              class="flex-1"
            >
              {{ procesando ? 'Creando cuenta...' : 'Crear cuenta' }}
            </BaseButton>
          </div>
        </form>
      </div>
      
      <!-- Paso 4: Éxito -->
      <div v-else-if="estado === 'exito'" class="bg-white rounded-lg shadow-sm p-6 text-center space-y-4">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto">
          <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <h2 class="text-2xl font-bold text-gray-900">¡Cuenta creada exitosamente!</h2>
        <div class="space-y-2">
          <p class="text-gray-600"><strong>Email:</strong> {{ credenciales?.email }}</p>
          <p class="text-sm text-gray-500 bg-yellow-50 border border-yellow-200 rounded-lg p-3">
            {{ credenciales?.mensaje_activacion }}
          </p>
        </div>
        <BaseButton
          @click="router.push('/login')"
          variant="primary"
          block
        >
          Iniciar sesión
        </BaseButton>
      </div>
      
      <!-- Error -->
      <div v-if="error && estado === 'error'" class="bg-white rounded-lg shadow-sm p-6">
        <div class="bg-danger-50 border border-danger-200 text-danger-700 px-4 py-3 rounded-lg">
          {{ error }}
        </div>
        <BaseButton
          @click="router.push('/')"
          variant="secondary"
          block
          class="mt-4"
        >
          Volver al inicio
        </BaseButton>
      </div>
    </div>
  </div>
</template>

