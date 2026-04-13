# Evitar error 419 en login (Sanctum / CSRF)

Cuando el frontend y el backend están en **orígenes distintos** (por ejemplo: abres la app en `http://localhost:5173` y la API está en `https://palegoldenrod-scorpion-430177.hostingersite.com`), el navegador puede no enviar la cookie CSRF en el POST de login y Laravel responde **419 Page Expired**.

Configura en el **.env del servidor** (carpeta `backend/`) lo siguiente.

## 1. CORS – orígenes permitidos

Debe incluir **exactamente** la URL desde la que abres la app (origen del navegador):

```env
# Si pruebas desde tu PC (npm run dev, puerto 3000):
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://127.0.0.1:3000

# Si además la app está publicada en el mismo servidor, añade su URL:
# CORS_ALLOWED_ORIGINS=http://localhost:3000,http://127.0.0.1:3000,https://palegoldenrod-scorpion-430177.hostingersite.com
```

Sin comillas, varios orígenes separados por coma.

## 2. Sanctum – dominios stateful

Dominios/hosts del frontend **sin** `http://` ni `https://`:

```env
# Desarrollando en local contra el API del servidor:
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1

# Si el frontend también está en el servidor (añade el dominio):
# SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,127.0.0.1,127.0.0.1:3000,palegoldenrod-scorpion-430177.hostingersite.com
```

## 3. Sesión – cookies en peticiones cross-origin

Para que la cookie de sesión/CSRF se envíe en peticiones **cross-origin** (desde otro dominio/puerto), la cookie debe ser `SameSite=None` y `Secure`:

```env
SESSION_SAME_SITE=none
SESSION_SECURE_COOKIE=true
```

Requisitos:

- El backend debe servirse por **HTTPS** (en Hostinger suele ser así).
- Si en local usas `http://localhost:8000`, en ese entorno no pongas `SESSION_SECURE_COOKIE=true` o la cookie no se enviará.

## 4. Comprobar

1. Guarda el `.env` en el servidor.
2. Reinicia PHP / el servidor web (o toca un archivo para recargar config si aplica).
3. En el frontend, recarga la página (F5) y vuelve a intentar **Iniciar sesión**.

Si sigue saliendo 419, revisa en DevTools (F12) → pestaña **Network**:

- La petición a `/sanctum/csrf-cookie` debe devolver **204** y en **Response Headers** debe aparecer `Set-Cookie` (p. ej. para la sesión Laravel y/o XSRF-TOKEN).
- La petición `POST .../auth/login` debe ir con **Credentials** y la cabecera **X-XSRF-TOKEN** (el frontend la rellena desde la cookie).

## Resumen mínimo en el servidor (frontend en local)

```env
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://127.0.0.1:3000
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,127.0.0.1,127.0.0.1:3000
SESSION_SAME_SITE=none
SESSION_SECURE_COOKIE=true
```

Después de cambiar el `.env`, reinicia el servicio PHP/web en el servidor.

---

## 5. APK (app móvil con Capacitor)

Cuando los usuarios abren la **APK** en el celular, la app se ejecuta en un WebView. Las peticiones al API (login, etc.) salen con un **Origin** distinto al del navegador:

- Con `androidScheme: "https"` (como en este proyecto), el origen suele ser **`https://localhost`**.
- En algunos entornos puede ser `http://localhost` o `capacitor://localhost`.

Para que el **login funcione desde la APK** (evitar 419), el servidor debe aceptar ese origen.

### En el .env del servidor, incluye los orígenes de la app móvil:

```env
# Orígenes: web local (3000) + web producción + APK (Capacitor)
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://127.0.0.1:3000,https://localhost,http://localhost,https://palegoldenrod-scorpion-430177.hostingersite.com

# Dominios stateful (incluir localhost para la APK)
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,127.0.0.1,127.0.0.1:3000,palegoldenrod-scorpion-430177.hostingersite.com
```

Mantén también:

```env
SESSION_SAME_SITE=none
SESSION_SECURE_COOKIE=true
```

Así la APK podrá obtener la cookie CSRF y enviarla en el POST de login sin recibir 419.
