# Configuración Local - Desarrollo

## Arquitectura Local Simplificada

Para desarrollo local, todo corre en tu máquina:

```
┌─────────────────────────────────────┐
│     TU MÁQUINA LOCAL                 │
├─────────────────────────────────────┤
│  Laravel (HTTP) - localhost:8000   │
│  Reverb (WebSocket) - localhost:8080 │
│  Base de Datos (SQLite/MySQL)       │
│  Frontend (Vite) - localhost:5173   │
└─────────────────────────────────────┘
```

## Configuración Paso a Paso

### 1. Variables de Entorno

**Backend (.env):**
```env
# Aplicación
APP_URL=http://localhost:8000
APP_ENV=local
APP_DEBUG=true

# Base de datos (tu configuración actual)
DB_CONNECTION=sqlite
# O MySQL:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=coach_saas
# DB_USERNAME=root
# DB_PASSWORD=

# WebSockets - Local
BROADCAST_DRIVER=reverb

REVERB_APP_ID=coach-saas-local
REVERB_APP_KEY=local-app-key-12345
REVERB_APP_SECRET=local-app-secret-67890
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

**Frontend (.env o .env.local):**
```env
VITE_API_URL=http://localhost:8000/api/v1
VITE_REVERB_APP_KEY=local-app-key-12345
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME=http
```

### 2. Instalar Dependencias

**Backend:**
```bash
cd backend
composer install
composer require laravel/reverb
php artisan reverb:install
php artisan migrate
```

**Frontend:**
```bash
cd frontend
npm install
npm install laravel-echo pusher-js
npm install @emoji-mart/vue @emoji-mart/data
```

### 3. Ejecutar en Desarrollo

Necesitas **3 terminales** abiertas:

#### Terminal 1: Laravel (Backend HTTP)
```bash
cd backend
php artisan serve
```
✅ Laravel corriendo en: `http://localhost:8000`

#### Terminal 2: Reverb (WebSocket)
```bash
cd backend
php artisan reverb:start
```
✅ Reverb corriendo en: `ws://localhost:8080`

#### Terminal 3: Frontend (Vite)
```bash
cd frontend
npm run dev
```
✅ Frontend corriendo en: `http://localhost:5173`

### 4. Verificar que Todo Funciona

**1. Verificar Laravel:**
```bash
curl http://localhost:8000/api/v1/health
```

**2. Verificar Reverb:**
- Deberías ver en la terminal: `Starting Reverb server on localhost:8080...`
- Si hay errores, aparecerán en esa terminal

**3. Verificar Frontend:**
- Abre `http://localhost:5173` en el navegador
- Abre la consola del navegador (F12)
- Deberías ver conexiones sin errores

## Scripts Útiles para Desarrollo

### Script para Iniciar Todo (Windows)

**start-dev.bat:**
```batch
@echo off
start "Laravel" cmd /k "cd backend && php artisan serve"
timeout /t 2
start "Reverb" cmd /k "cd backend && php artisan reverb:start"
timeout /t 2
start "Frontend" cmd /k "cd frontend && npm run dev"
echo Todos los servicios iniciados
```

### Script para Iniciar Todo (Linux/Mac)

**start-dev.sh:**
```bash
#!/bin/bash

# Terminal 1: Laravel
gnome-terminal -- bash -c "cd backend && php artisan serve; exec bash"

# Terminal 2: Reverb
sleep 2
gnome-terminal -- bash -c "cd backend && php artisan reverb:start; exec bash"

# Terminal 3: Frontend
sleep 2
gnome-terminal -- bash -c "cd frontend && npm run dev; exec bash"

echo "✅ Todos los servicios iniciados"
```

**Hacer ejecutable:**
```bash
chmod +x start-dev.sh
./start-dev.sh
```

### Usando npm-run-all (Alternativa)

**package.json (en la raíz del proyecto):**
```json
{
  "scripts": {
    "dev": "npm-run-all --parallel dev:*",
    "dev:laravel": "cd backend && php artisan serve",
    "dev:reverb": "cd backend && php artisan reverb:start",
    "dev:frontend": "cd frontend && npm run dev"
  },
  "devDependencies": {
    "npm-run-all": "^4.1.5"
  }
}
```

**Ejecutar todo:**
```bash
npm run dev
```

## Configuración de Laravel Echo (Frontend)

**frontend/src/composables/useWebSocket.js:**
```javascript
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import { useAuthStore } from '@/stores/auth'

window.Pusher = Pusher

let echoInstance = null

export default function useWebSocket() {
  const authStore = useAuthStore()

  if (!echoInstance) {
    echoInstance = new Echo({
      broadcaster: 'reverb',
      key: import.meta.env.VITE_REVERB_APP_KEY,
      wsHost: import.meta.env.VITE_REVERB_HOST,
      wsPort: import.meta.env.VITE_REVERB_PORT,
      wssPort: import.meta.env.VITE_REVERB_PORT,
      forceTLS: false, // Local no usa SSL
      enabledTransports: ['ws', 'wss'],
      // Autenticación para canales privados
      authEndpoint: `${import.meta.env.VITE_API_URL}/broadcasting/auth`,
      auth: {
        headers: {
          Authorization: `Bearer ${authStore.token}`,
          Accept: 'application/json',
        },
      },
    })

    // Logs para debugging
    echoInstance.connector.pusher.connection.bind('connected', () => {
      console.log('✅ Conectado a Reverb (WebSocket)')
    })

    echoInstance.connector.pusher.connection.bind('disconnected', () => {
      console.log('❌ Desconectado de Reverb')
    })

    echoInstance.connector.pusher.connection.bind('error', (error) => {
      console.error('❌ Error en WebSocket:', error)
    })
  }

  return echoInstance
}
```

## Troubleshooting Local

### Problema: Puerto 8000 ya en uso
```bash
# Cambiar puerto de Laravel
php artisan serve --port=8001

# Actualizar VITE_API_URL en frontend/.env
```

### Problema: Puerto 8080 ya en uso
```bash
# Cambiar puerto de Reverb
# En .env:
REVERB_PORT=8081

# Actualizar VITE_REVERB_PORT en frontend/.env
```

### Problema: Reverb no inicia
```bash
# Verificar que Laravel Reverb esté instalado
composer show laravel/reverb

# Reinstalar si es necesario
composer require laravel/reverb
php artisan reverb:install
```

### Problema: CORS en desarrollo
**backend/config/cors.php:**
```php
'paths' => ['api/*', 'broadcasting/auth'],
'allowed_origins' => ['http://localhost:5173'],
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
'supports_credentials' => true,
```

### Problema: WebSocket no conecta
1. Verificar que Reverb esté corriendo (Terminal 2)
2. Verificar variables de entorno en frontend
3. Verificar consola del navegador para errores
4. Verificar que no haya firewall bloqueando

## Flujo de Desarrollo

1. **Iniciar servicios:**
   ```bash
   # Terminal 1
   cd backend && php artisan serve
   
   # Terminal 2
   cd backend && php artisan reverb:start
   
   # Terminal 3
   cd frontend && npm run dev
   ```

2. **Desarrollar:**
   - Código backend en `backend/`
   - Código frontend en `frontend/`
   - Vite recarga automáticamente el frontend
   - Laravel recarga automáticamente (con Laravel Octane o reiniciar)

3. **Probar:**
   - Abrir `http://localhost:5173`
   - Probar funcionalidad de chat
   - Ver logs en las terminales

4. **Debugging:**
   - Backend: `storage/logs/laravel.log`
   - Reverb: Logs en la terminal donde corre
   - Frontend: Consola del navegador (F12)

## Comandos Útiles

```bash
# Limpiar cache de Laravel
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Ver rutas disponibles
php artisan route:list

# Verificar conexión a BD
php artisan tinker
>>> DB::connection()->getPdo();

# Verificar broadcasting
php artisan tinker
>>> broadcast(new App\Events\TestEvent());
```

## Resumen para Desarrollo Local

✅ **3 terminales**: Laravel, Reverb, Frontend  
✅ **Puertos**: 8000 (Laravel), 8080 (Reverb), 5173 (Frontend)  
✅ **Sin configuración compleja**: Todo local, sin SSL  
✅ **Hot reload**: Vite recarga automáticamente  
✅ **Fácil debugging**: Logs en terminales y consola del navegador  

**¡Listo para desarrollar!** 🚀






