# Coach SaaS

Proyecto full-stack con backend en Laravel y frontend en Vue 3, con soporte para aplicaciones móviles mediante Capacitor.

## Contexto del proyecto

Plataforma SaaS para entrenadores y coaches de fitness que les permite operar y gestionar su negocio digital desde un solo sistema. Pensada principalmente para **entrenamiento online**, con soporte para atención presencial cuando se requiera.

Cada coach tiene su propio espacio en la plataforma: administra sus clientes, crea rutinas, ejercicios con videos, planes nutricionales, evaluaciones físicas, formularios personalizados y su propia tienda de productos. Los datos de un coach no se mezclan con los de otros.

- **Administrador**: ve y gestiona a todos los coaches y clientes.
- **Coach**: solo ve y trabaja con sus clientes asignados (rutinas, evaluaciones, pagos, formularios, tienda).
- **Cliente**: solo accede a su información, rutinas, progreso, pagos y chat con su entrenador.

Para el coach es una herramienta que centraliza toda la operación de su negocio (planes, cobros Stripe/efectivo, historial de progreso, tienda). Para el cliente es una app integral de entrenamiento (rutinas con videos, registrar entrenamientos, formularios, evolución, chat, pagos). En conjunto, infraestructura digital que integra entrenamiento, nutrición, seguimiento, comunicación y monetización en una sola plataforma.

## Estructura del Proyecto

```
coach_saas/
├── backend/          # API Laravel
├── frontend/         # Aplicación Vue 3
└── README.md
```

## Tecnologías

- **Backend**: Laravel 12
- **Frontend**: Vue 3 + TypeScript
- **Estilos**: Tailwind CSS
- **Móvil**: Capacitor
- **Routing**: Vue Router 4
- **State Management**: Pinia

## Requisitos Previos

- PHP >= 8.2
- Composer
- Node.js >= 18
- npm o yarn

## Instalación

### Backend (Laravel)

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

El backend estará disponible en `http://localhost:8000`

### Frontend (Vue 3)

```bash
cd frontend
npm install
npm run dev
```

El frontend estará disponible en `http://localhost:5173`

## Configuración de Capacitor

Para agregar plataformas móviles:

```bash
cd frontend
npm run build
npm run cap:add android
# o
npm run cap:add ios
```

Para sincronizar cambios:

```bash
npm run cap:sync
```

Para abrir en el IDE nativo:

```bash
npm run cap:open android
# o
npm run cap:open ios
```

## Desarrollo

### Backend

```bash
cd backend
php artisan serve
```

### Frontend

```bash
cd frontend
npm run dev
```

## Build para Producción

### Frontend

```bash
cd frontend
npm run build
```

Los archivos compilados estarán en `frontend/dist/`

### Backend

```bash
cd backend
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## API Endpoints

La API está disponible en `/api`. Ejemplo:

- `GET /api/health` - Verificar estado de la API

## Configuración de CORS

El backend está configurado para aceptar peticiones desde el frontend. Si necesitas ajustar los orígenes permitidos, modifica `backend/bootstrap/app.php`.

## Estructura de Carpetas

### Backend
- `app/` - Lógica de la aplicación
- `routes/api.php` - Rutas de la API
- `config/` - Archivos de configuración

### Frontend
- `src/` - Código fuente
  - `router/` - Configuración de Vue Router
  - `stores/` - Stores de Pinia
  - `views/` - Componentes de vista
  - `components/` - Componentes reutilizables

## Estado actual

- Autenticación con Laravel Sanctum (login, logout, roles: admin, coach, cliente)
- Modelos y migraciones para coaches, clientes, planes, suscripciones, rutinas, ejercicios, evaluaciones, formularios, pagos, tienda
- API REST por rol (admin, coach, cliente) con recursos y relaciones
- Frontend Vue 3 con Pinia, Vue Router, Tailwind CSS y paleta de diseño definida
- Seeder de relaciones para pruebas (admin@test.com, coach@test.com, cliente@test.com)

## Licencia

MIT
