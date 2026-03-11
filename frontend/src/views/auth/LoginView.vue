<script setup>
import { ref, reactive } from 'vue'
import { useAuth } from '@/composables/useAuth'

const { login, cargando, erroresValidacion } = useAuth()

const formulario = reactive({
  email: '',
  password: ''
})

const errorGeneral = ref('')

async function handleSubmit() {
  errorGeneral.value = ''

  const resultado = await login({
    email: formulario.email,
    password: formulario.password
  })

  if (!resultado.exito) {
    errorGeneral.value = resultado.error
  }
}
</script>

<template>
  <div class="login">
    <div class="login__container">
      <div class="login__section">
        <!-- Logo / Título -->
        <div class="login__header">
          <div class="login__logo">
            <img src="/favicon.svg" alt="" class="login__logo-img" width="40" height="40" />
          </div>
          <h1 class="login__title">Coach SaaS</h1>
          <p class="login__subtitle">Inicia sesión en tu cuenta</p>
        </div>

        <form @submit.prevent="handleSubmit" class="login__form">
          <div v-if="errorGeneral" class="login__alert login__alert--error" role="alert">
            {{ errorGeneral }}
          </div>

          <div class="login__field">
            <label class="login__label" for="login-email">
              Correo electrónico <span class="login__required">*</span>
            </label>
            <div class="login__input-wrap">
              <span class="login__input-icon" aria-hidden="true">
                <svg class="login__input-icon-svg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                </svg>
              </span>
              <input
                id="login-email"
                v-model="formulario.email"
                type="email"
                placeholder="tu@email.com"
                autocomplete="email"
                required
                class="login__input"
                :class="{ 'login__input--error': erroresValidacion?.email?.[0] }"
              />
            </div>
            <p v-if="erroresValidacion?.email?.[0]" class="login__error-text">
              {{ erroresValidacion.email[0] }}
            </p>
          </div>

          <div class="login__field">
            <label class="login__label" for="login-password">
              Contraseña <span class="login__required">*</span>
            </label>
            <div class="login__input-wrap">
              <span class="login__input-icon" aria-hidden="true">
                <svg class="login__input-icon-svg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
              </span>
              <input
                id="login-password"
                v-model="formulario.password"
                type="password"
                placeholder="••••••••"
                autocomplete="current-password"
                required
                class="login__input"
                :class="{ 'login__input--error': erroresValidacion?.password?.[0] }"
              />
            </div>
            <p v-if="erroresValidacion?.password?.[0]" class="login__error-text">
              {{ erroresValidacion.password[0] }}
            </p>
          </div>

          <button
            type="submit"
            :disabled="cargando"
            class="login__btn login__btn--primary"
          >
            <span v-if="cargando" class="login__spinner" aria-hidden="true"></span>
            <span>{{ cargando ? 'Iniciando sesión...' : 'Iniciar sesión' }}</span>
          </button>
        </form>

        <p class="login__footer">
          ¿Olvidaste tu contraseña? Contacta a tu administrador.
        </p>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Base */
.login {
  min-height: 100vh;
  min-height: 100dvh;
  background: #0a0a0a;
  padding: 1rem;
  padding-top: max(1rem, env(safe-area-inset-top));
  padding-bottom: max(2rem, env(safe-area-inset-bottom));
  display: flex;
  align-items: center;
  justify-content: center;
}

.login ::selection {
  background: rgba(0, 210, 97, 0.3);
  color: #fff;
}

.login ::-moz-selection {
  background: rgba(0, 210, 97, 0.3);
  color: #fff;
}

/* Container */
.login__container {
  width: 100%;
  max-width: 24rem;
  margin: 0 auto;
}

/* Section card */
.login__section {
  background: #161616;
  border: 1px solid #252525;
  border-radius: 16px;
  padding: 1.5rem;
}

/* Header */
.login__header {
  text-align: center;
  margin-bottom: 1.5rem;
}

.login__logo {
  width: 4rem;
  height: 4rem;
  margin: 0 auto 1rem;
  background: rgba(0, 210, 97, 0.12);
  border: 1px solid rgba(0, 210, 97, 0.35);
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #00D261;
}

.login__logo-img {
  width: 2.5rem;
  height: 2.5rem;
  object-fit: contain;
}

.login__title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #fff;
  margin: 0 0 0.25rem;
}

.login__subtitle {
  font-size: 0.875rem;
  color: #697586;
  margin: 0;
}

/* Form */
.login__form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

/* Alert */
.login__alert {
  border-radius: 12px;
  padding: 0.75rem 1rem;
  font-size: 0.8125rem;
}

.login__alert--error {
  background: rgba(239, 92, 92, 0.12);
  border: 1px solid rgba(239, 92, 92, 0.3);
  color: #EF5C5C;
}

/* Field */
.login__field {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.login__label {
  font-size: 0.8125rem;
  font-weight: 500;
  color: #a0a0a0;
}

.login__required {
  color: #EF5C5C;
  margin-left: 0.125rem;
}

/* Input wrap (with icon) */
.login__input-wrap {
  position: relative;
}

.login__input-icon {
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 2.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #697586;
  pointer-events: none;
}

.login__input-icon-svg {
  width: 1.25rem;
  height: 1.25rem;
}

.login__input {
  width: 100%;
  background: #1e1e1e;
  border: 1px solid #252525;
  border-radius: 12px;
  padding: 0.875rem 1rem 0.875rem 2.75rem;
  font-size: 0.875rem;
  color: #fff;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
  -webkit-appearance: none;
  appearance: none;
}

.login__input::placeholder {
  color: #697586;
}

.login__input:focus {
  outline: none;
  border-color: #00D261;
  box-shadow: 0 0 0 2px rgba(0, 210, 97, 0.2);
}

.login__input--error {
  border-color: rgba(239, 92, 92, 0.5);
}

.login__input--error:focus {
  border-color: #EF5C5C;
  box-shadow: 0 0 0 2px rgba(239, 92, 92, 0.2);
}

/* Autofill dark override */
.login__input:-webkit-autofill,
.login__input:-webkit-autofill:hover,
.login__input:-webkit-autofill:focus {
  -webkit-box-shadow: 0 0 0 30px #1e1e1e inset;
  -webkit-text-fill-color: #fff;
  caret-color: #fff;
}

.login__error-text {
  font-size: 0.8125rem;
  color: #EF5C5C;
  margin: 0;
}

/* Button */
.login__btn {
  padding: 0.875rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
  border: none;
  text-align: center;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  min-height: 2.75rem;
}

.login__btn--primary {
  background: transparent;
  border: 1px solid rgba(0, 210, 97, 0.4);
  color: #00D261;
}

.login__btn--primary:hover:not(:disabled) {
  background: rgba(0, 210, 97, 0.1);
  border-color: #00D261;
}

.login__btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.login__spinner {
  width: 1.25rem;
  height: 1.25rem;
  border: 2px solid rgba(0, 210, 97, 0.25);
  border-top-color: #00D261;
  border-radius: 50%;
  animation: login-spin 0.7s linear infinite;
}

@keyframes login-spin {
  to { transform: rotate(360deg); }
}

/* Footer */
.login__footer {
  text-align: center;
  font-size: 0.8125rem;
  color: #697586;
  margin: 1.5rem 0 0;
  padding-top: 1.25rem;
  border-top: 1px solid #252525;
}
</style>
