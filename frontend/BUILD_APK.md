# Generar APK - Coach SaaS

## Requisitos previos

| Herramienta | Version minima | Verificar con |
|-------------|---------------|---------------|
| **Node.js** | 18+ | `node -v` |
| **npm** | 9+ | `npm -v` |
| **Java JDK** | 21 | `java -version` |
| **Android SDK** | compileSdk 36 | Android Studio o variable `ANDROID_HOME` |

> No necesitas Android Studio instalado si tienes el JDK y Android SDK configurados en el PATH.

---

## Pasos para generar la APK

### 1. Instalar dependencias (solo la primera vez)

```bash
cd frontend
npm install
```

### 2. Compilar los assets web para produccion

```bash
npm run build
```

Esto genera la carpeta `dist/` con los archivos optimizados de Vue. Usa las variables de `frontend/.env.production` (la URL de la API de produccion).

### 3. Sincronizar con Capacitor

```bash
npx cap sync android
```

Copia los assets de `dist/` al proyecto Android y actualiza los plugins nativos.

### 4. Generar la APK

#### APK de Debug (sin firmar, para pruebas)

```bash
cd android
./gradlew assembleDebug
```

La APK se genera en:

```
android/app/build/outputs/apk/debug/app-debug.apk
```

#### APK de Release (firmada, para distribucion)

Para release necesitas un keystore. Si no tienes uno, crealo:

```bash
keytool -genkey -v -keystore coachsaas-release.keystore -alias coachsaas -keyalg RSA -keysize 2048 -validity 10000
```

Luego agrega la configuracion de firma en `android/app/build.gradle`:

```groovy
android {
    ...
    signingConfigs {
        release {
            storeFile file('coachsaas-release.keystore')
            storePassword 'tu_password'
            keyAlias 'coachsaas'
            keyPassword 'tu_key_password'
        }
    }
    buildTypes {
        release {
            signingConfig signingConfigs.release
            minifyEnabled false
            proguardFiles getDefaultProguardFile('proguard-android.txt'), 'proguard-rules.pro'
        }
    }
}
```

Luego ejecuta:

```bash
cd android
./gradlew assembleRelease
```

La APK firmada se genera en:

```
android/app/build/outputs/apk/release/app-release.apk
```

---

## Comando rapido (todo en uno)

Desde la carpeta `frontend/`:

```bash
npm run build && npx cap sync android && cd android && ./gradlew assembleDebug
```

---

## Instalar la APK en un dispositivo

### Opcion A: Transferencia directa
Copia el archivo `.apk` al celular (por USB, correo, Drive, etc.) y abrelo. Necesitas habilitar "Instalar desde fuentes desconocidas" en Ajustes.

### Opcion B: ADB (con USB)
```bash
adb install android/app/build/outputs/apk/debug/app-debug.apk
```

### Opcion C: Abrir en Android Studio
```bash
npx cap open android
```
Luego usa Run > Run 'app' con un dispositivo conectado o emulador.

---

## Cambiar la URL de la API

Edita `frontend/.env.production` antes del paso 2:

```
VITE_API_URL=https://tu-dominio.com/backend/public/api/v1
```

---

## Cambiar el icono de la app

Los iconos estan en `android/app/src/main/res/mipmap-*/`:

| Carpeta | Tamano ic_launcher | Tamano foreground |
|---------|-------------------|-------------------|
| mipmap-mdpi | 48x48 | 108x108 |
| mipmap-hdpi | 72x72 | 162x162 |
| mipmap-xhdpi | 96x96 | 216x216 |
| mipmap-xxhdpi | 144x144 | 324x324 |
| mipmap-xxxhdpi | 192x192 | 432x432 |

Reemplaza `ic_launcher.png`, `ic_launcher_round.png` y `ic_launcher_foreground.png` en cada carpeta con las imagenes del tamano correspondiente.

---

## Cambiar nombre y package de la app

- **Nombre visible**: `android/app/src/main/res/values/strings.xml` -> campo `app_name`
- **Package / Application ID**: `android/app/build.gradle` -> campo `applicationId`
- **Capacitor config**: `frontend/capacitor.config.json` -> campo `appId` y `appName`

---

## Estructura relevante

```
frontend/
├── .env.production          # URL de API para produccion
├── capacitor.config.json    # Config de Capacitor (appId, appName)
├── dist/                    # Assets compilados (generado por npm run build)
├── android/
│   ├── app/
│   │   ├── build.gradle     # Config del build Android
│   │   ├── google-services.json  # Firebase (push notifications)
│   │   └── src/main/res/    # Iconos, splash, recursos Android
│   └── gradlew              # Wrapper de Gradle
└── package.json             # Scripts: build, cap:sync, cap:open
```

---

## Notas importantes

- **Firebase**: El archivo `google-services.json` debe tener el `package_name` igual al `applicationId` del `build.gradle` (`com.coachsaas.app`). Si no coincide, el build falla.
- **Push Notifications**: Requiere un proyecto Firebase correctamente configurado con el package name `com.coachsaas.app`.
- **Java**: El proyecto usa Java 21. Asegurate de tener el JDK 21 instalado y configurado en `JAVA_HOME`.
- **Gradle**: El wrapper (`gradlew`) descarga automaticamente Gradle 8.14.3, no necesitas instalarlo manualmente.
