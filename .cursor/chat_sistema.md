# Sistema de Chat - Coach SaaS

## 📋 Índice

1. [Contexto General](#contexto-general)
2. [Modificaciones en la Base de Datos](#modificaciones-en-la-base-de-datos)
3. [Tecnologías Utilizadas](#tecnologías-utilizadas)
4. [Arquitectura del Sistema](#arquitectura-del-sistema)
5. [Flujo de Funcionamiento](#flujo-de-funcionamiento)
6. [Estructura de Archivos](#estructura-de-archivos)
7. [Endpoints API](#endpoints-api)
8. [Características Implementadas](#características-implementadas)

---

## 🎯 Contexto General

El sistema de chat permite la comunicación bidireccional entre **coaches** y sus **clientes** dentro de la aplicación. Está diseñado para facilitar la comunicación en tiempo real (mediante polling) y soporta:

- **Mensajes de texto** con emojis
- **Imágenes** (JPG, PNG, GIF, WebP)
- **Documentos** (PDF, DOC, DOCX, XLS, XLSX, etc.)
- **Notificaciones de lectura** (mensajes leídos/no leídos)
- **Búsqueda de clientes** (para coaches en desktop)

### Roles y Permisos

- **Coach**: Puede ver todos sus clientes en una lista, iniciar conversaciones y enviar mensajes
- **Cliente**: Solo puede ver y comunicarse con su coach asignado

---

## 🗄️ Modificaciones en la Base de Datos

### Nueva Tabla: `archivos_mensaje`

Se creó una tabla separada para almacenar los archivos adjuntos de los mensajes, siguiendo el principio de normalización de base de datos.

**Migración**: `database/migrations/Chat/2026_02_10_162848_create_archivos_mensaje_table.php`

```sql
CREATE TABLE archivos_mensaje (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    mensaje_id BIGINT UNSIGNED NOT NULL,
    tipo VARCHAR(255) NOT NULL,              -- 'imagen' | 'documento'
    nombre_original VARCHAR(255) NOT NULL,
    ruta VARCHAR(255) NOT NULL,              -- Path en storage/app/public/chat/{chat_id}/
    tamaño BIGINT UNSIGNED NOT NULL,         -- Tamaño en bytes
    mime_type VARCHAR(255) NULL,            -- image/jpeg, application/pdf, etc.
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (mensaje_id) REFERENCES mensajes(id) ON DELETE CASCADE
);
```

### Tablas Existentes Utilizadas

#### `chats`
- Relación 1:1 entre un coach y un cliente
- Campos: `id`, `coach_id`, `cliente_id`, `created_at`, `updated_at`, `deleted_at`

#### `mensajes`
- Mensajes dentro de un chat
- Campos: `id`, `chat_id`, `emisor_tipo` ('coach' | 'cliente'), `mensaje` (texto), `enviado_en`, `leido` (boolean)

### Relaciones

```
Chat (1) ──< (N) Mensaje (1) ──< (N) ArchivoMensaje
```

- Un `Chat` tiene muchos `Mensaje`
- Un `Mensaje` puede tener muchos `ArchivoMensaje`
- Los archivos se eliminan en cascada cuando se elimina un mensaje

---

## 🛠️ Tecnologías Utilizadas

### Backend (Laravel)

- **Framework**: Laravel 11.x
- **Autenticación**: Laravel Sanctum (SPA Authentication)
- **ORM**: Eloquent
- **Validación**: Form Requests (`SolicitudEnviarMensaje`, `SolicitudSubirArchivo`)
- **API Resources**: Para formatear respuestas JSON
- **Storage**: Sistema de archivos de Laravel (`storage/app/public/chat/`)

### Frontend (Vue.js)

- **Framework**: Vue 3 (Composition API)
- **Estado Global**: Pinia
- **Routing**: Vue Router
- **HTTP Client**: Fetch API (wrapped en `useApi` composable)
- **Emojis**: `emoji-mart-vue-fast` (picker de emojis)
- **Polling**: Intervalo de 3 segundos para obtener nuevos mensajes
- **Estilos**: Tailwind CSS + CSS personalizado (dark theme)

### Comunicación

- **Método**: Polling (cada 3 segundos)
- **Razón**: Aplicación demo con bajo tráfico inicial
- **Futuro**: Migración a WebSockets (Laravel Reverb) cuando el tráfico aumente

---

## 🏗️ Arquitectura del Sistema

### Patrón de Diseño

- **Backend**: MVC (Model-View-Controller)
- **Frontend**: Component-Based Architecture
- **Estado**: Singleton Pattern (composable `useChat` compartido)

### Flujo de Datos

```
Frontend (Vue) 
    ↓
Composable (useChat) 
    ↓
API (Laravel) 
    ↓
Controller → Form Request (Validación) 
    ↓
Service/Model (Lógica de Negocio) 
    ↓
Database (MySQL/MariaDB)
```

### Almacenamiento de Archivos

```
storage/
└── app/
    └── public/
        └── chat/
            └── {chat_id}/
                ├── imagenes/
                │   └── {timestamp}_{nombre_sanitizado}.{ext}
                └── documentos/
                    └── {timestamp}_{nombre_sanitizado}.{ext}
```

**URLs públicas**: `http://domain.com/storage/chat/{chat_id}/...`

---

## 🔄 Flujo de Funcionamiento

### 1. Inicialización del Chat

#### Para Coach:
1. El coach accede a `/coach/chat`
2. El frontend llama a `GET /api/v1/coach/chats`
3. El backend:
   - Obtiene todos los clientes del coach (`clientes.creado_por = coach.id`)
   - Busca chats existentes para cada cliente
   - **Crea automáticamente** un chat si no existe para algún cliente
   - Retorna lista paginada de chats con último mensaje

#### Para Cliente:
1. El cliente accede a `/cliente/chat`
2. El frontend llama a `GET /api/v1/cliente/chat`
3. El backend:
   - Busca el chat del cliente con su coach
   - Si no existe, **crea automáticamente** un chat
   - Retorna el chat con información del coach

### 2. Carga de Mensajes

1. Al seleccionar un chat (coach) o cargar la vista (cliente)
2. Frontend llama a `GET /api/v1/{rol}/chat/{id}/mensajes?page=1`
3. Backend retorna mensajes paginados (50 por página) en orden descendente
4. Frontend invierte el orden para mostrar cronológicamente (más antiguos arriba)
5. Se guarda el `ultimoMensajeId` para detectar mensajes nuevos

### 3. Polling de Nuevos Mensajes

1. Cada 3 segundos, el frontend llama a `GET /api/v1/{rol}/chat/{id}/mensajes?page=1`
2. Compara los mensajes recibidos con `ultimoMensajeId`
3. Si hay mensajes nuevos:
   - Los agrega al array de mensajes
   - Hace scroll automático al final (si el usuario está cerca del final)
   - Actualiza `ultimoMensajeId`

### 4. Envío de Mensaje

#### Con Texto:
1. Usuario escribe mensaje y presiona "Enviar"
2. Frontend llama a `POST /api/v1/{rol}/chat/{id}/mensajes` con:
   ```json
   {
     "mensaje": "Texto del mensaje",
     "archivos": []
   }
   ```
3. Backend:
   - Valida el mensaje (máx. 5000 caracteres)
   - Crea registro en `mensajes`
   - Retorna `MensajeResource` con el mensaje creado
4. Frontend agrega el mensaje al array y hace scroll al final

#### Con Archivos:
1. Usuario selecciona archivos (máx. 5, 10MB cada uno)
2. Frontend prepara `FormData` con mensaje y archivos
3. Backend:
   - Valida archivos (tipo, tamaño, mime)
   - Almacena archivos en `storage/app/public/chat/{chat_id}/`
   - Crea registros en `archivos_mensaje`
   - Crea registro en `mensajes` con relación a archivos
4. Frontend muestra los archivos adjuntos en el mensaje

### 5. Descarga de Archivos

1. Usuario hace clic en un archivo adjunto
2. Frontend llama a `GET /api/v1/{rol}/chat/{id}/archivos/{archivoId}`
3. Backend:
   - Verifica que el usuario tenga acceso al chat
   - Retorna el archivo con headers apropiados (`Content-Disposition`, `Content-Type`)
4. El navegador descarga o muestra el archivo

### 6. Marcar como Leído

1. Cuando un usuario abre un chat, se llama a `PUT /api/v1/{rol}/chat/{id}/leer`
2. Backend actualiza `mensajes.leido = true` para todos los mensajes del otro usuario
3. El contador de "no leídos" se actualiza en la lista de chats

---

## 📁 Estructura de Archivos

### Backend

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Coach/
│   │   │   │   └── ControladorChat.php
│   │   │   └── Cliente/
│   │   │       └── ControladorChat.php
│   │   ├── Requests/
│   │   │   └── Chat/
│   │   │       ├── SolicitudEnviarMensaje.php
│   │   │       └── SolicitudSubirArchivo.php
│   │   └── Resources/
│   │       └── Chat/
│   │           ├── ChatResource.php
│   │           ├── MensajeResource.php
│   │           └── ArchivoMensajeResource.php
│   ├── Models/
│   │   ├── Chat.php
│   │   ├── Mensaje.php
│   │   └── ArchivoMensaje.php
│   └── Console/
│       └── Commands/
│           └── VerificarRelacionesChat.php
├── database/
│   └── migrations/
│       └── Chat/
│           └── 2026_02_10_162848_create_archivos_mensaje_table.php
└── routes/
    └── app/
        ├── coach.php
        └── cliente.php
```

### Frontend

```
frontend/
├── src/
│   ├── components/
│   │   └── chat/
│   │       ├── ChatLista.vue          # Lista de chats (solo coach)
│   │       ├── ChatMensajes.vue       # Área de mensajes con scroll
│   │       ├── ChatMensaje.vue        # Componente individual de mensaje
│   │       ├── ChatInput.vue           # Input de mensaje (texto, emojis, archivos)
│   │       └── ChatArchivo.vue        # Componente para mostrar archivos adjuntos
│   ├── views/
│   │   ├── coach/
│   │   │   └── ChatView.vue           # Vista de chat para coach (2 paneles)
│   │   └── cliente/
│   │       └── ChatView.vue           # Vista de chat para cliente (1 panel)
│   ├── composables/
│   │   ├── useChat.js                 # Lógica principal del chat (singleton)
│   │   └── useChatPolling.js         # Polling de mensajes nuevos
│   └── router/
│       └── index.js                   # Rutas: /coach/chat, /cliente/chat
```

---

## 🔌 Endpoints API

### Coach

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `GET` | `/api/v1/coach/chats` | Lista de chats (paginada) |
| `GET` | `/api/v1/coach/chats/{id}` | Detalles de un chat |
| `GET` | `/api/v1/coach/chats/{id}/mensajes` | Mensajes del chat (paginados) |
| `POST` | `/api/v1/coach/chats/{id}/mensajes` | Enviar mensaje |
| `PUT` | `/api/v1/coach/chats/{id}/leer` | Marcar mensajes como leídos |
| `GET` | `/api/v1/coach/chats/{id}/archivos/{archivoId}` | Descargar archivo |

### Cliente

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `GET` | `/api/v1/cliente/chat` | Obtener chat con coach |
| `GET` | `/api/v1/cliente/chat/mensajes` | Mensajes del chat (paginados) |
| `POST` | `/api/v1/cliente/chat/mensajes` | Enviar mensaje |
| `PUT` | `/api/v1/cliente/chat/leer` | Marcar mensajes como leídos |
| `GET` | `/api/v1/cliente/chat/archivos/{archivoId}` | Descargar archivo |

### Autenticación

Todos los endpoints requieren:
- **Middleware**: `auth:sanctum`
- **Headers**: `X-XSRF-TOKEN` (para POST/PUT/DELETE)
- **Cookies**: Sesión de Sanctum

---

## ✨ Características Implementadas

### ✅ Completadas

1. **Chat bidireccional** entre coach y cliente
2. **Mensajes de texto** con soporte para emojis Unicode
3. **Subida de archivos** (imágenes y documentos)
4. **Picker de emojis** integrado (`emoji-mart-vue-fast`)
5. **Polling automático** cada 3 segundos para nuevos mensajes
6. **Scroll automático** al final cuando hay mensajes nuevos
7. **Marcado de lectura** de mensajes
8. **Lista de chats** para coaches con búsqueda
9. **Creación automática** de chats cuando no existen
10. **Vista responsive** (mobile-first, desktop con 2 paneles)
11. **Diseño tipo WhatsApp Desktop** (input fijo, solo mensajes con scroll)
12. **Validación de archivos** (tipo, tamaño, cantidad)
13. **Sanitización de nombres** de archivos
14. **Descarga segura** de archivos con autorización

### 🔄 Pendientes (Futuro)

1. **WebSockets** (Laravel Reverb) para comunicación en tiempo real
2. **Notificaciones push** cuando hay mensajes nuevos
3. **Vista previa de imágenes** en modal
4. **Indicador de "escribiendo..."**
5. **Respuestas a mensajes** (quote/reply)
6. **Reacciones a mensajes** (👍, ❤️, etc.)
7. **Búsqueda de mensajes** dentro de un chat
8. **Eliminación de mensajes** (propios)
9. **Edición de mensajes** (propios)
10. **Compartir ubicación** (opcional)

---

## 🎨 Diseño UI/UX

### Principios

- **Mobile-first**: Diseño optimizado para móviles, luego desktop
- **Dark theme**: Colores oscuros (#0a0a0a, #161616, #252525)
- **Acento verde**: #00D261 (similar a WhatsApp)
- **Tipografía**: Sistema de fuentes del proyecto
- **Espaciado**: Consistente con el resto de la aplicación

### Layout Desktop (Coach)

```
┌─────────────────────────────────────────────────┐
│  Lista de Chats (35%)  │  Área de Mensajes (65%)│
│  ┌──────────────────┐  │  ┌──────────────────┐ │
│  │ [Búsqueda]       │  │  │ [Header Cliente] │ │
│  ├──────────────────┤  │  ├──────────────────┤ │
│  │ Chat 1           │  │  │                  │ │
│  │ Chat 2           │  │  │   Mensajes       │ │
│  │ Chat 3           │  │  │   (scroll)       │ │
│  │ ...              │  │  │                  │ │
│  └──────────────────┘  │  ├──────────────────┤ │
│                        │  │ [Input Mensaje]  │ │
│                        │  └──────────────────┘ │
└─────────────────────────────────────────────────┘
```

### Layout Mobile

```
┌─────────────────────┐
│ [Header Cliente]    │
├─────────────────────┤
│                     │
│   Mensajes         │
│   (scroll)          │
│                     │
├─────────────────────┤
│ [Input Mensaje]    │
├─────────────────────┤
│ [Bottom Nav]        │
└─────────────────────┘
```

---

## 🔒 Seguridad

### Validaciones Backend

- **Autenticación**: Solo usuarios autenticados pueden acceder
- **Autorización**: Cada usuario solo ve sus propios chats
- **Validación de archivos**: Tipo, tamaño, cantidad limitados
- **Sanitización**: Nombres de archivos sanitizados antes de guardar
- **Storage**: Archivos almacenados en `storage/app/public/` (acceso controlado)

### Validaciones Frontend

- **Límite de caracteres**: 5000 caracteres por mensaje
- **Límite de archivos**: Máximo 5 archivos por mensaje
- **Tamaño máximo**: 10MB por archivo
- **Tipos permitidos**: Validados antes de subir

---

## 📝 Notas Técnicas

### Polling vs WebSockets

**Decisión actual**: Polling cada 3 segundos

**Razones**:
- Aplicación demo con bajo tráfico inicial
- Más simple de implementar y mantener
- No requiere servidor WebSocket adicional

**Migración futura**: Laravel Reverb cuando el tráfico aumente

### Orden de Mensajes

- **Backend**: Retorna mensajes en orden descendente (más recientes primero)
- **Frontend**: Invierte el array para mostrar cronológicamente (más antiguos arriba)
- **Scroll**: Automático al final cuando hay mensajes nuevos

### Estado Compartido

El composable `useChat` usa un patrón singleton para mantener un estado único compartido entre todos los componentes que lo usan. Esto evita inconsistencias y duplicación de datos.

---

## 🐛 Comandos Útiles

### Verificar Relaciones de Chat

```bash
php artisan chat:verificar-relaciones
```

Este comando verifica y corrige inconsistencias en las relaciones `coach_id` y `cliente_id` de los chats.

### Limpiar Archivos Huérfanos

```bash
# Manual: Eliminar archivos de mensajes eliminados
# (Implementar comando si es necesario)
```

---

## 📚 Referencias

- [Laravel Documentation](https://laravel.com/docs)
- [Vue 3 Documentation](https://vuejs.org/)
- [Laravel Sanctum](https://laravel.com/docs/sanctum)
- [emoji-mart-vue-fast](https://github.com/jm-david/emoji-mart-vue-fast)

---

**Última actualización**: Febrero 2026
**Versión**: 1.0.0


