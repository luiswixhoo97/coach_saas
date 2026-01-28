import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'

// Estilos
import './assets/styles/main.css'

// Crear aplicación
const app = createApp(App)

// Plugins
app.use(createPinia())
app.use(router)

// Montar
app.mount('#app')
