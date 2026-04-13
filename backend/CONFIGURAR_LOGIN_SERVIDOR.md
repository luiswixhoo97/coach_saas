# Configurar el login en el servidor – Paso a paso

Esta guía explica **qué hacer en el servidor** (donde está tu backend en Hostinger) para que el login funcione desde:
- La web (navegador en tu PC o en la URL de producción)
- La app móvil (APK instalada en el celular)

Si no tocas nada en el servidor, al intentar “Iniciar sesión” puede aparecer un error **419** o que “algo falló”. Eso se soluciona configurando unas variables en el backend.

---

## ¿Y en mi PC (entorno local)?

Sí. En tu **.env local** (carpeta `backend/` en tu computadora) también tienes que tener CORS y Sanctum configurados para que el login funcione cuando corres el frontend en `http://localhost:3000` y el backend en `http://localhost:8000`.

La diferencia con el servidor es la sesión:
- **En el servidor** (HTTPS): `SESSION_SAME_SITE=none` y `SESSION_SECURE_COOKIE=true`.
- **En local** (HTTP): `SESSION_SAME_SITE=lax` y `SESSION_SECURE_COOKIE=false`.

Si en tu `.env` local ya están puestas las líneas de CORS, Sanctum y sesión (como en el ejemplo más abajo), no hace falta cambiar nada. Si no, añade las mismas variables que en el servidor pero con `SESSION_SECURE_COOKIE=false` y `SESSION_SAME_SITE=lax`.

---

## Qué vas a hacer en resumen

1. Entrar al servidor (Hostinger) y abrir el archivo de configuración del backend.
2. Añadir o cambiar unas líneas en ese archivo.
3. Guardar y, si hace falta, reiniciar el servicio.

No hace falta tocar código PHP ni el frontend. Solo ese archivo de configuración.

---

## Paso 1: Localizar el archivo de configuración en el servidor

En el servidor (Hostinger, por FTP o el administrador de archivos), ve a la carpeta donde está el **backend** de Coach SaaS.

Dentro del backend busca el archivo llamado **`.env`** (empieza por punto).  
Es el archivo de configuración que usa Laravel. Ahí es donde vas a trabajar.

- Si no ves archivos que empiecen por punto, en el administrador de archivos suele haber una opción tipo “Mostrar archivos ocultos”.
- Si solo tienes un archivo **`.env.example`**, cópialo y renombra la copia a **`.env`**. Luego edita el **`.env`**.

---

## Paso 2: Abrir el archivo .env

Abre el archivo **`.env`** con el editor de texto del panel de Hostinger (o con el programa que uses para editar archivos en el servidor).

Verás muchas líneas con cosas como `APP_NAME=...`, `DB_DATABASE=...`, etc. No borres nada que ya exista. Solo vas a **añadir o cambiar** las líneas que te indico abajo.

---

## Paso 3: Añadir o completar las variables para el login

Busca en el `.env` si ya existen estas variables. Si existen, **sustitúyelas** por lo que pone aquí. Si no existen, **añádelas** al final del archivo (o en una zona que tengas para “CORS / Sanctum”).

Copia y pega **exactamente** esto (puedes cambiar la URL de producción por la tuya si es distinta):

```env
# ----- Login desde web y APK (evitar error 419) -----
# Orígenes desde los que se abre la app: tu PC (puerto 3000), la APK y la web en el servidor
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://127.0.0.1:3000,https://localhost,http://localhost,https://palegoldenrod-scorpion-430177.hostingersite.com

# Dominios que el backend acepta para cookies de sesión (mismos que arriba, sin http/https)
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,127.0.0.1,127.0.0.1:3000,palegoldenrod-scorpion-430177.hostingersite.com

# Para que las cookies funcionen cuando la app y el API están en dominios distintos
SESSION_SAME_SITE=none
SESSION_SECURE_COOKIE=true
```

**Importante:**
- No pongas comillas alrededor de los valores.
- No dejes espacios antes del `=` ni después.
- Si tu dominio de producción es otro (no `palegoldenrod-scorpion-430177.hostingersite.com`), cambia esas dos apariciones por tu dominio real.

---

## Paso 4: Guardar el archivo

Guarda el archivo **`.env`** en el servidor con los cambios.

---

## Paso 5: Aplicar los cambios en el servidor

Según cómo esté montado tu hosting:

- **Si usas PHP desde el panel de Hostinger**: a veces basta con guardar el `.env`; el siguiente request ya usará la nueva config.
- **Si tienes opción de “Reiniciar PHP” o “Reiniciar servicio”**: úsala una vez después de guardar.
- **Si alguien más administra el servidor**: pídele que reinicie PHP o el servidor web después de cambiar el `.env`.

No hace falta volver a desplegar el frontend ni la APK. Los cambios son solo en el backend.

---

## Paso 6: Probar el login

1. **Desde la web**  
   Abre tu app en el navegador (en tu PC con `npm run dev` en el puerto 3000, o en la URL de producción).  
   Ve a “Iniciar sesión”, escribe email y contraseña y envía.  
   Debería entrar sin error 419.

2. **Desde la APK**  
   Abre la app en el celular, ve a “Iniciar sesión” e inicia sesión con un usuario que exista en el servidor.  
   Debería entrar igual.

Si sigue fallando, revisa el paso 3 (que no haya espacios de más, que el dominio sea el correcto) y el paso 5 (reiniciar PHP/servicio).

---

## Resumen rápido (para quien ya sabe dónde está el .env)

En el **`.env`** del **backend en el servidor**:

1. Poner `CORS_ALLOWED_ORIGINS` con: localhost:3000, https://localhost, http://localhost y tu URL de producción.
2. Poner `SANCTUM_STATEFUL_DOMAINS` con: localhost, 127.0.0.1 y tu dominio de producción (sin http/https).
3. Poner `SESSION_SAME_SITE=none` y `SESSION_SECURE_COOKIE=true`.
4. Guardar y reiniciar PHP si hace falta.

Con eso el login debería funcionar desde la web y desde la APK.
