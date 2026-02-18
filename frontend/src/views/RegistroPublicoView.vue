<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApi } from '@/composables/useApi'
import FormularioDinamico from '@/components/FormularioDinamico.vue'

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
  
  estado.value = 'formulario'
}

async function enviarRegistro() {
  try {
    procesando.value = true
    error.value = null
    
    let respuestasArray = null
    if (Object.keys(respuestasFormulario.value).length > 0) {
      respuestasArray = []
      const indices = Object.keys(respuestasFormulario.value)
        .map(Number)
        .sort((a, b) => a - b)
      indices.forEach(index => {
        respuestasArray.push(respuestasFormulario.value[index])
      })
    }
    
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
    if (err.response?.status === 422) {
      const data = err.response.data
      if (data.errors) {
        const errores = data.errors
        const mensajes = Object.values(errores).flat()
        error.value = mensajes.join('. ')
      } else if (data.errores) {
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
  <div class="registro-publico">
    <!-- Paso 1: Cargando -->
    <div v-if="estado === 'loading'" class="registro-publico__loading">
      <div class="registro-publico__spinner"></div>
      <p class="registro-publico__loading-text">Cargando información...</p>
    </div>
    
    <!-- Paso 2: Mostrar Planes -->
    <div v-else-if="estado === 'planes'" class="registro-publico__container">
      <div class="registro-publico__section">
        <div class="registro-publico__header">
          <h1 class="registro-publico__title">Registro</h1>
          <p class="registro-publico__subtitle">{{ datosCoach?.nombre }}</p>
        </div>
        
        <div class="registro-publico__planes">
          <h2 class="registro-publico__section-title">Selecciona tu plan</h2>
          
          <div class="registro-publico__planes-grid">
            <div
              v-for="plan in planes"
              :key="plan.id"
              :class="[
                'registro-publico__plan-card',
                { 'registro-publico__plan-card--selected': planSeleccionado?.id === plan.id }
              ]"
              @click="seleccionarPlan(plan)"
            >
              <h3 class="registro-publico__plan-name">{{ plan.nombre }}</h3>
              <p class="registro-publico__plan-price">${{ plan.precio }}</p>
              <p class="registro-publico__plan-duration">{{ plan.duracion_dias }} días</p>
            </div>
          </div>
        </div>
        
        <div v-if="planSeleccionado" class="registro-publico__pago">
          <h3 class="registro-publico__section-title">Método de pago</h3>
          
          <!-- Opción Stripe (Deshabilitada) -->
          <label class="registro-publico__pago-option registro-publico__pago-option--disabled">
            <input
              type="radio"
              value="stripe"
              v-model="metodoPago"
              disabled
              class="registro-publico__radio"
            />
            <div class="registro-publico__pago-option-content">
              <span class="registro-publico__pago-option-label">Pago con Tarjeta (Stripe)</span>
              <span class="registro-publico__badge registro-publico__badge--warning">Próximamente</span>
            </div>
          </label>
          
          <!-- Opción Transferencia (Funcional) -->
          <label class="registro-publico__pago-option">
            <input
              type="radio"
              value="transferencia"
              v-model="metodoPago"
              class="registro-publico__radio"
            />
            <span class="registro-publico__pago-option-label">Transferencia Bancaria</span>
          </label>
          
          <button
            @click="continuarPago"
            :disabled="!metodoPago"
            class="registro-publico__btn registro-publico__btn--primary"
          >
            Continuar
          </button>
        </div>
      </div>
    </div>
    
    <!-- Paso 3: Formulario de Registro -->
    <div v-else-if="estado === 'formulario'" class="registro-publico__container">
      <div class="registro-publico__section">
        <div class="registro-publico__header">
          <h2 class="registro-publico__title">Completa tu información</h2>
          <div class="registro-publico__info">
            <p class="registro-publico__info-text">
              Plan: <strong>{{ planSeleccionado?.nombre }}</strong> - ${{ planSeleccionado?.precio }}
            </p>
            <p class="registro-publico__info-text">
              Método de pago: <strong>Transferencia Bancaria</strong>
            </p>
          </div>
        </div>
        
        <form @submit.prevent="enviarRegistro" class="registro-publico__form">
          <!-- Formulario dinámico si existe -->
          <div v-if="formularioRegistro" class="registro-publico__form-section">
            <h3 class="registro-publico__form-section-title">{{ formularioRegistro.nombre }}</h3>
            <FormularioDinamico
              :preguntas="formularioRegistro.preguntas"
              v-model="respuestasFormulario"
            />
          </div>
          
          <!-- Campos básicos siempre requeridos -->
          <div class="registro-publico__form-section">
            <h3 v-if="formularioRegistro" class="registro-publico__form-section-title">Datos personales</h3>
            
            <div class="registro-publico__form-grid">
              <div class="registro-publico__field">
                <label class="registro-publico__label">
                  Nombre <span class="registro-publico__required">*</span>
                </label>
                <input
                  v-model="nombre"
                  type="text"
                  placeholder="Tu nombre"
                  required
                  class="registro-publico__input"
                />
              </div>
              
              <div class="registro-publico__field">
                <label class="registro-publico__label">
                  Apellido Paterno <span class="registro-publico__required">*</span>
                </label>
                <input
                  v-model="apellidoPaterno"
                  type="text"
                  placeholder="Tu apellido paterno"
                  required
                  class="registro-publico__input"
                />
              </div>
            </div>
            
            <div class="registro-publico__field">
              <label class="registro-publico__label">Apellido Materno</label>
              <input
                v-model="apellidoMaterno"
                type="text"
                placeholder="Tu apellido materno (opcional)"
                class="registro-publico__input"
              />
            </div>
            
            <div class="registro-publico__form-grid">
              <div class="registro-publico__field">
                <label class="registro-publico__label">
                  Fecha de Nacimiento <span class="registro-publico__required">*</span>
                </label>
                <input
                  v-model="fechaNacimiento"
                  type="date"
                  required
                  class="registro-publico__input"
                />
              </div>
              
              <div class="registro-publico__field">
                <label class="registro-publico__label">
                  Altura (cm) <span class="registro-publico__required">*</span>
                </label>
                <input
                  v-model="altura"
                  type="number"
                  placeholder="Ej: 175"
                  :min="0"
                  :max="300"
                  required
                  class="registro-publico__input"
                />
              </div>
            </div>
            
            <div class="registro-publico__form-grid">
              <div class="registro-publico__field">
                <label class="registro-publico__label">
                  Sexo <span class="registro-publico__required">*</span>
                </label>
                <select
                  v-model="sexo"
                  required
                  class="registro-publico__select"
                >
                  <option value="">Selecciona tu sexo</option>
                  <option value="masculino">Masculino</option>
                  <option value="femenino">Femenino</option>
                  <option value="otro">Otro</option>
                </select>
              </div>
              
              <div class="registro-publico__field">
                <label class="registro-publico__label">
                  Objetivo <span class="registro-publico__required">*</span>
                </label>
                <select
                  v-model="objetivo"
                  required
                  class="registro-publico__select"
                >
                  <option value="">Selecciona tu objetivo</option>
                  <option v-for="obj in objetivosDisponibles" :key="obj" :value="obj">
                    {{ obj }}
                  </option>
                </select>
              </div>
            </div>
            
            <h3 class="registro-publico__form-section-title">Datos de acceso</h3>
            
            <div class="registro-publico__field">
              <label class="registro-publico__label">
                Email <span class="registro-publico__required">*</span>
              </label>
              <input
                v-model="email"
                type="email"
                placeholder="tu@email.com"
                required
                autocomplete="off"
                class="registro-publico__input"
              />
            </div>
            
            <div class="registro-publico__field">
              <label class="registro-publico__label">
                Contraseña <span class="registro-publico__required">*</span>
              </label>
              <input
                v-model="password"
                type="password"
                placeholder="Mínimo 8 caracteres"
                required
                :minlength="8"
                autocomplete="new-password"
                class="registro-publico__input"
              />
            </div>
          </div>
          
          <!-- Información sobre transferencia -->
          <div class="registro-publico__alert registro-publico__alert--info">
            <p class="registro-publico__alert-text">
              <strong>Nota:</strong> Después de crear tu cuenta, deberás realizar el pago por transferencia bancaria. 
              El coach activará tu cuenta una vez que verifique el pago.
            </p>
          </div>
          
          <div v-if="error" class="registro-publico__alert registro-publico__alert--error">
            <p class="registro-publico__alert-text">{{ error }}</p>
          </div>
          
          <div class="registro-publico__form-actions">
            <button
              type="button"
              @click="estado = 'planes'"
              class="registro-publico__btn registro-publico__btn--secondary"
            >
              Volver
            </button>
            <button
              type="submit"
              :disabled="procesando"
              class="registro-publico__btn registro-publico__btn--primary"
            >
              {{ procesando ? 'Creando cuenta...' : 'Crear cuenta' }}
            </button>
          </div>
        </form>
      </div>
    </div>
    
    <!-- Paso 4: Éxito -->
    <div v-else-if="estado === 'exito'" class="registro-publico__container">
      <div class="registro-publico__section registro-publico__section--centered">
        <div class="registro-publico__success-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <h2 class="registro-publico__title">¡Cuenta creada exitosamente!</h2>
        <div class="registro-publico__success-info">
          <p class="registro-publico__success-text">
            <strong>Email:</strong> {{ credenciales?.email }}
          </p>
          <div class="registro-publico__alert registro-publico__alert--warning">
            <p class="registro-publico__alert-text">
              {{ credenciales?.mensaje_activacion }}
            </p>
          </div>
        </div>
        <button
          @click="router.push('/login')"
          class="registro-publico__btn registro-publico__btn--primary registro-publico__btn--block"
        >
          Iniciar sesión
        </button>
      </div>
    </div>
    
    <!-- Error -->
    <div v-if="error && estado === 'error'" class="registro-publico__container">
      <div class="registro-publico__section">
        <div class="registro-publico__alert registro-publico__alert--error">
          <p class="registro-publico__alert-text">{{ error }}</p>
        </div>
        <button
          @click="router.push('/')"
          class="registro-publico__btn registro-publico__btn--secondary registro-publico__btn--block"
        >
          Volver al inicio
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Base */
.registro-publico {
  min-height: 100vh;
  min-height: 100dvh;
  background: #0a0a0a;
  padding: 1rem;
  padding-top: max(1rem, env(safe-area-inset-top));
  padding-bottom: max(2rem, env(safe-area-inset-bottom));
}

/* Selección de texto global para el componente */
.registro-publico ::selection {
  background: rgba(0, 210, 97, 0.3) !important;
  color: #fff !important;
}

.registro-publico ::-moz-selection {
  background: rgba(0, 210, 97, 0.3) !important;
  color: #fff !important;
}

/* Loading */
.registro-publico__loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 50vh;
  gap: 1rem;
}

.registro-publico__spinner {
  width: 48px;
  height: 48px;
  border: 3px solid rgba(0, 210, 97, 0.2);
  border-top-color: #00D261;
  border-radius: 50%;
  animation: registro-spin 0.8s linear infinite;
}

@keyframes registro-spin {
  to { transform: rotate(360deg); }
}

.registro-publico__loading-text {
  font-size: 0.875rem;
  color: #697586;
}

/* Container */
.registro-publico__container {
  max-width: 42rem;
  margin: 0 auto;
}

/* Section */
.registro-publico__section {
  background: #161616;
  border-radius: 16px;
  padding: 1rem;
  margin-bottom: 0.75rem;
}

.registro-publico__section--centered {
  text-align: center;
}

/* Header */
.registro-publico__header {
  text-align: center;
  margin-bottom: 1.5rem;
}

.registro-publico__title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #fff;
  margin: 0 0 0.5rem;
}

.registro-publico__subtitle {
  font-size: 0.875rem;
  color: #697586;
  margin: 0;
}

.registro-publico__info {
  margin-top: 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.registro-publico__info-text {
  font-size: 0.8125rem;
  color: #697586;
  margin: 0;
}

.registro-publico__info-text strong {
  color: #a0a0a0;
}

/* Section Title */
.registro-publico__section-title {
  font-size: 0.9375rem;
  font-weight: 600;
  color: #fff;
  margin: 0 0 0.75rem;
}

/* Planes */
.registro-publico__planes {
  margin-bottom: 1.5rem;
}

.registro-publico__planes-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 0.75rem;
  margin-top: 0.75rem;
}

.registro-publico__plan-card {
  background: #1e1e1e;
  border: 1px solid #252525;
  border-radius: 12px;
  padding: 1rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.registro-publico__plan-card:hover {
  border-color: rgba(0, 210, 97, 0.4);
  transform: translateY(-2px);
}

.registro-publico__plan-card--selected {
  border-color: #00D261;
  background: rgba(0, 210, 97, 0.1);
}

.registro-publico__plan-name {
  font-size: 0.875rem;
  font-weight: 600;
  color: #fff;
  margin: 0 0 0.5rem;
}

.registro-publico__plan-price {
  font-size: 1.125rem;
  font-weight: 700;
  color: #00D261;
  margin: 0 0 0.25rem;
}

.registro-publico__plan-duration {
  font-size: 0.8125rem;
  color: #697586;
  margin: 0;
}

/* Pago */
.registro-publico__pago {
  padding-top: 1rem;
  border-top: 1px solid #252525;
}

.registro-publico__pago-option {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.875rem 1rem;
  background: #1e1e1e;
  border: 1px solid #252525;
  border-radius: 12px;
  margin-bottom: 0.75rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.registro-publico__pago-option:hover {
  border-color: rgba(0, 210, 97, 0.4);
}

.registro-publico__pago-option--disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.registro-publico__pago-option--disabled:hover {
  border-color: #252525;
}

.registro-publico__radio {
  width: 18px;
  height: 18px;
  accent-color: #00D261;
  cursor: pointer;
}

.registro-publico__pago-option-content {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex: 1;
}

.registro-publico__pago-option-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #a0a0a0;
}

/* Badge */
.registro-publico__badge {
  font-size: 0.6875rem;
  font-weight: 500;
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

.registro-publico__badge--warning {
  background: rgba(255, 153, 0, 0.15);
  color: #FF9900;
}

/* Form */
.registro-publico__form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.registro-publico__form-section {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.registro-publico__form-section-title {
  font-size: 0.9375rem;
  font-weight: 600;
  color: #fff;
  margin: 0 0 0.5rem;
}

.registro-publico__form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1rem;
}

/* Field */
.registro-publico__field {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.registro-publico__label {
  font-size: 0.8125rem;
  font-weight: 500;
  color: #a0a0a0;
}

.registro-publico__required {
  color: #EF5C5C;
  margin-left: 0.125rem;
}

/* Input */
.registro-publico__input,
.registro-publico__select {
  width: 100%;
  background: #1e1e1e !important;
  background-color: #1e1e1e !important;
  border: 1px solid #252525;
  border-radius: 12px;
  padding: 0.875rem 1rem;
  font-size: 0.875rem;
  color: #fff !important;
  transition: all 0.2s ease;
  -webkit-appearance: none;
  appearance: none;
}

.registro-publico__input::placeholder {
  color: #697586;
  opacity: 1;
}

/* Text selection styles - más específicos */
.registro-publico__input::selection {
  background: rgba(0, 210, 97, 0.3) !important;
  color: #fff !important;
}

.registro-publico__input::-moz-selection {
  background: rgba(0, 210, 97, 0.3) !important;
  color: #fff !important;
}

/* Forzar fondo oscuro en todos los estados */
.registro-publico__input:active,
.registro-publico__input:focus,
.registro-publico__input:focus-visible,
.registro-publico__input:focus-within {
  background: #1e1e1e !important;
  background-color: #1e1e1e !important;
  color: #fff !important;
}

.registro-publico__input:focus,
.registro-publico__select:focus {
  outline: none;
  border-color: #00D261;
  box-shadow: 0 0 0 2px rgba(0, 210, 97, 0.2);
  background: #1e1e1e !important;
  color: #fff !important;
}

/* Override autofill styles */
.registro-publico__input:-webkit-autofill,
.registro-publico__input:-webkit-autofill:hover {
  -webkit-box-shadow: 0 0 0 30px #1e1e1e inset !important;
  -webkit-text-fill-color: #fff !important;
  background-color: #1e1e1e !important;
  border-color: #252525 !important;
}

.registro-publico__input:-webkit-autofill:focus,
.registro-publico__input:-webkit-autofill:active {
  -webkit-box-shadow: 0 0 0 30px #1e1e1e inset, 0 0 0 2px rgba(0, 210, 97, 0.2) !important;
  -webkit-text-fill-color: #fff !important;
  background-color: #1e1e1e !important;
  border-color: #00D261 !important;
}

.registro-publico__input[type="email"],
.registro-publico__input[type="password"] {
  background: #1e1e1e !important;
  background-color: #1e1e1e !important;
  color: #fff !important;
  -webkit-text-fill-color: #fff !important;
}

.registro-publico__input[type="email"]:focus,
.registro-publico__input[type="password"]:focus,
.registro-publico__input[type="email"]:active,
.registro-publico__input[type="password"]:active,
.registro-publico__input[type="email"]:focus-visible,
.registro-publico__input[type="password"]:focus-visible {
  background: #1e1e1e !important;
  background-color: #1e1e1e !important;
  color: #fff !important;
  -webkit-text-fill-color: #fff !important;
  border-color: #00D261 !important;
  box-shadow: 0 0 0 2px rgba(0, 210, 97, 0.2) !important;
}

/* Forzar fondo oscuro incluso con autofill para email y password */
.registro-publico__input[type="email"]:-webkit-autofill,
.registro-publico__input[type="password"]:-webkit-autofill {
  -webkit-box-shadow: 0 0 0 30px #1e1e1e inset !important;
  -webkit-text-fill-color: #fff !important;
  background-color: #1e1e1e !important;
  background: #1e1e1e !important;
  border-color: #252525 !important;
  color: #fff !important;
}

.registro-publico__input[type="email"]:-webkit-autofill:focus,
.registro-publico__input[type="email"]:-webkit-autofill:active,
.registro-publico__input[type="password"]:-webkit-autofill:focus,
.registro-publico__input[type="password"]:-webkit-autofill:active {
  -webkit-box-shadow: 0 0 0 30px #1e1e1e inset, 0 0 0 2px rgba(0, 210, 97, 0.2) !important;
  -webkit-text-fill-color: #fff !important;
  background-color: #1e1e1e !important;
  background: #1e1e1e !important;
  border-color: #00D261 !important;
  color: #fff !important;
}

/* Selección de texto específica para email y password */
.registro-publico__input[type="email"]::selection,
.registro-publico__input[type="password"]::selection {
  background: rgba(0, 210, 97, 0.3) !important;
  color: #fff !important;
}

.registro-publico__input[type="email"]::-moz-selection,
.registro-publico__input[type="password"]::-moz-selection {
  background: rgba(0, 210, 97, 0.3) !important;
  color: #fff !important;
}

.registro-publico__select {
  cursor: pointer;
}

.registro-publico__select option {
  background: #1e1e1e;
  color: #fff;
}

.registro-publico__select option:checked {
  background: rgba(0, 210, 97, 0.2);
  color: #00D261;
}

/* Buttons */
.registro-publico__btn {
  padding: 0.875rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
  border: none;
  text-align: center;
}

.registro-publico__btn--primary {
  background: transparent;
  border: 1px solid rgba(0, 210, 97, 0.4);
  color: #00D261;
}

.registro-publico__btn--primary:hover:not(:disabled) {
  background: rgba(0, 210, 97, 0.1);
  border-color: #00D261;
}

.registro-publico__btn--secondary {
  background: transparent;
  border: 1px solid rgba(105, 117, 134, 0.4);
  color: #697586;
}

.registro-publico__btn--secondary:hover:not(:disabled) {
  background: rgba(105, 117, 134, 0.1);
  border-color: #697586;
  color: #a0a0a0;
}

.registro-publico__btn--block {
  width: 100%;
}

.registro-publico__btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Form Actions */
.registro-publico__form-actions {
  display: flex;
  gap: 0.75rem;
}

.registro-publico__form-actions .registro-publico__btn {
  flex: 1;
}

/* Alerts */
.registro-publico__alert {
  border-radius: 12px;
  padding: 0.875rem 1rem;
}

.registro-publico__alert--error {
  background: rgba(239, 92, 92, 0.12);
  border: 1px solid rgba(239, 92, 92, 0.3);
}

.registro-publico__alert--info {
  background: rgba(0, 122, 255, 0.12);
  border: 1px solid rgba(0, 122, 255, 0.3);
}

.registro-publico__alert--warning {
  background: rgba(255, 153, 0, 0.12);
  border: 1px solid rgba(255, 153, 0, 0.3);
}

.registro-publico__alert-text {
  font-size: 0.8125rem;
  margin: 0;
}

.registro-publico__alert--error .registro-publico__alert-text {
  color: #EF5C5C;
}

.registro-publico__alert--info .registro-publico__alert-text {
  color: #007AFF;
}

.registro-publico__alert--warning .registro-publico__alert-text {
  color: #FF9900;
}

.registro-publico__alert-text strong {
  font-weight: 600;
}

/* Success */
.registro-publico__success-icon {
  width: 64px;
  height: 64px;
  background: rgba(0, 210, 97, 0.15);
  border: 1px solid rgba(0, 210, 97, 0.3);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1rem;
  color: #00D261;
}

.registro-publico__success-icon svg {
  width: 32px;
  height: 32px;
}

.registro-publico__success-info {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin: 1rem 0;
}

.registro-publico__success-text {
  font-size: 0.875rem;
  color: #a0a0a0;
  margin: 0;
}

.registro-publico__success-text strong {
  color: #fff;
}

/* Responsive */
@media (min-width: 640px) {
  .registro-publico__planes-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .registro-publico__form-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
