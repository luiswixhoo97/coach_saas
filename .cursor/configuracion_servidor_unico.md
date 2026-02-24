# Configuración: Servidor Único (Demo/Desarrollo)

## Arquitectura Simplificada

Para una aplicación demo o con poco tráfico inicial, **un solo servidor es perfecto**:

```
┌─────────────────────────────────────┐
│     SERVIDOR ÚNICO                  │
├─────────────────────────────────────┤
│  Laravel (HTTP) - Puerto 8000      │
│  Reverb (WebSocket) - Puerto 8080   │
│  Base de Datos                      │
└─────────────────────────────────────┘
```

## Ventajas

✅ **Simple**: Todo en un lugar, fácil de configurar  
✅ **Económico**: Un solo servidor, menos costos  
✅ **Suficiente**: Para demos y aplicaciones pequeñas/medianas  
✅ **Fácil mantenimiento**: Menos complejidad  

## Configuración Paso a Paso

### 1. Variables de Entorno

```env
# .env
APP_URL=http://localhost:8000

# Base de datos (tu configuración actual)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=coach_saas
DB_USERNAME=root
DB_PASSWORD=

# WebSockets - Mismo servidor
BROADCAST_DRIVER=reverb

REVERB_APP_ID=coach-saas-demo
REVERB_APP_KEY=tu-app-key-generado
REVERB_APP_SECRET=tu-app-secret-generado
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

# Frontend (Vite)
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### 2. Desarrollo Local

**Terminal 1 - Laravel:**
```bash
cd backend
php artisan serve
# Laravel corriendo en http://localhost:8000
```

**Terminal 2 - Reverb:**
```bash
cd backend
php artisan reverb:start
# Reverb corriendo en ws://localhost:8080
```

**Terminal 3 - Frontend:**
```bash
cd frontend
npm run dev
# Frontend en http://localhost:5173
```

### 3. Producción (Servidor Único)

#### Opción A: Con Supervisor (Recomendado)

**Instalar Supervisor:**
```bash
sudo apt-get install supervisor
```

**Configurar Reverb:**
```ini
# /etc/supervisor/conf.d/reverb.conf
[program:reverb]
process_name=%(program_name)s
command=php /ruta/completa/a/tu/proyecto/backend/artisan reverb:start
directory=/ruta/completa/a/tu/proyecto/backend
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/ruta/a/logs/reverb.log
stopwaitsecs=3600
```

**Activar:**
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start reverb
```

#### Opción B: Con systemd (Alternativa)

```ini
# /etc/systemd/system/reverb.service
[Unit]
Description=Laravel Reverb WebSocket Server
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/ruta/a/tu/proyecto/backend
ExecStart=/usr/bin/php artisan reverb:start
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

**Activar:**
```bash
sudo systemctl enable reverb
sudo systemctl start reverb
sudo systemctl status reverb
```

### 4. Configurar Nginx (Si usas Nginx)

```nginx
# /etc/nginx/sites-available/coach-saas
server {
    listen 80;
    server_name tu-dominio.com;
    
    # Laravel (HTTP)
    location / {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
    
    # Reverb (WebSocket)
    location /app/ {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

### 5. Firewall

Asegurar que los puertos estén abiertos:

```bash
# Desarrollo local - no necesario
# Producción - abrir puertos si es necesario
sudo ufw allow 80/tcp   # HTTP
sudo ufw allow 443/tcp  # HTTPS
sudo ufw allow 8080/tcp  # Reverb (solo si acceso directo)
```

**Nota**: En producción, normalmente solo expones 80/443 y usas proxy reverso.

## Verificación

### 1. Verificar Laravel
```bash
curl http://localhost:8000/api/v1/health
```

### 2. Verificar Reverb
```bash
# Debería mostrar que está escuchando
php artisan reverb:start --debug
```

### 3. Verificar desde Frontend
```javascript
// En la consola del navegador
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

const echo = new Echo({
  broadcaster: 'reverb',
  key: import.meta.env.VITE_REVERB_APP_KEY,
  wsHost: import.meta.env.VITE_REVERB_HOST,
  wsPort: import.meta.env.VITE_REVERB_PORT,
  wssPort: import.meta.env.VITE_REVERB_PORT,
  forceTLS: false,
  enabledTransports: ['ws', 'wss'],
})

echo.connector.pusher.connection.bind('connected', () => {
  console.log('✅ Conectado a Reverb')
})
```

## Recursos del Servidor

### Mínimos Recomendados

- **CPU**: 1-2 cores
- **RAM**: 2GB (1GB para Laravel, 512MB para Reverb, 512MB sistema)
- **Disco**: 20GB (suficiente para código y archivos)

### Monitoreo Básico

```bash
# Ver procesos
ps aux | grep -E "(php artisan|reverb)"

# Ver uso de memoria
free -h

# Ver conexiones WebSocket
netstat -an | grep 8080
```

## Escalabilidad Futura

Si en el futuro necesitas más capacidad:

1. **Primero**: Optimizar código y queries
2. **Segundo**: Aumentar recursos del servidor (más RAM/CPU)
3. **Tercero**: Separar Reverb a servidor dedicado
4. **Cuarto**: Múltiples servidores con Redis

**Para una demo, el servidor único es perfecto y suficiente.**

## Troubleshooting

### Reverb no inicia
```bash
# Verificar puerto disponible
netstat -tuln | grep 8080

# Verificar permisos
ls -la /ruta/a/proyecto/backend

# Ver logs
tail -f storage/logs/laravel.log
```

### Conexión WebSocket falla
- Verificar que Reverb esté corriendo
- Verificar variables de entorno
- Verificar firewall/proxy
- Verificar CORS si es necesario

### Alto uso de memoria
- Limitar conexiones en Reverb: `REVERB_MAX_CONNECTIONS=1000`
- Monitorear conexiones activas
- Considerar separar Reverb si crece mucho

## Resumen

✅ **Un solo servidor es perfecto para demo**  
✅ **Laravel en puerto 8000, Reverb en puerto 8080**  
✅ **Usar Supervisor en producción para mantener Reverb activo**  
✅ **Fácil de escalar después si es necesario**

