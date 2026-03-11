<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

## Backend - Coach SaaS

API Laravel del proyecto Coach SaaS.

## Configuración local - Firebase (FCM)

Las credenciales de Firebase **no se suben a Git**. Para usarlas en tu máquina:

1. En [Firebase Console](https://console.firebase.google.com) → tu proyecto → **Configuración del proyecto** → **Cuentas de servicio** → **Generar nueva clave privada**, descarga el JSON.
2. Copia ese JSON a `storage/app/firebase-credentials.json` (o copia `firebase-credentials.example.json` a esa ruta y reemplaza con el contenido descargado).
3. El archivo `storage/app/firebase-credentials.json` está en `.gitignore`, así que nunca se hará commit.

Opcional: si quieres poner el JSON en otra ruta, define en `.env`:

```env
FIREBASE_CREDENTIALS=/ruta/absoluta/a/tu/firebase-credentials.json
```

## Configuración en producción (servidor)

En el `.env` del servidor, define la URL donde está el backend (carpeta `public`):

```env
APP_URL=https://tu-dominio.com/backend/public
```

Si el frontend está en el mismo dominio, opcional:

```env
CORS_ALLOWED_ORIGINS=https://tu-dominio.com
SANCTUM_STATEFUL_DOMAINS=tu-dominio.com
```

## Aprender Laravel

Laravel tiene [documentación](https://laravel.com/docs) y [Laravel Learn](https://laravel.com/learn) en inglés. Para tutoriales en vídeo, [Laracasts](https://laracasts.com).

## Licencia

El framework Laravel es software de código abierto bajo la [licencia MIT](https://opensource.org/licenses/MIT).
