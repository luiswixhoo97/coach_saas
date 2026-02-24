# Implementación de Emojis en el Chat

## Estado Actual de la Base de Datos

Según tu estructura actual:

```php
// Tabla: mensajes
- id
- chat_id (FK)
- emisor_tipo (string: 'coach' | 'cliente')
- mensaje (text) ← Este campo puede almacenar emojis
- enviado_en (datetime)
- leido (boolean)
- timestamps
```

## ✅ Soporte de Emojis en la Base de Datos

### 1. Verificar Configuración UTF-8

El campo `mensaje` es de tipo `text`, que en MySQL/MariaDB puede almacenar emojis si:
- La tabla está en charset `utf8mb4` (no solo `utf8`)
- La columna está en collation `utf8mb4_unicode_ci` o `utf8mb4_bin`

**Verificar charset actual:**
```sql
SHOW CREATE TABLE mensajes;
-- Debe mostrar: DEFAULT CHARSET=utf8mb4
```

**Si no está en utf8mb4, crear migración:**
```php
// database/migrations/Chat/YYYY_MM_DD_HHMMSS_ensure_utf8mb4_for_mensajes.php
Schema::table('mensajes', function (Blueprint $table) {
    $table->text('mensaje')->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->change();
});
```

### 2. Configuración de Laravel

**Verificar `config/database.php`:**
```php
'mysql' => [
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    // ...
],
```

## 🎨 Frontend - Picker de Emojis

### Opción 1: EmojiMart (Recomendado)
```bash
npm install @emoji-mart/vue @emoji-mart/data
```

**Componente EmojiPicker:**
```vue
<!-- frontend/src/components/chat/EmojiPicker.vue -->
<template>
  <div class="emoji-picker">
    <button @click="mostrar = !mostrar" class="emoji-button">
      😊
    </button>
    
    <div v-if="mostrar" class="emoji-picker__popup">
      <Picker
        :data="emojiData"
        @emoji-select="onEmojiSelect"
        :theme="'dark'"
      />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import data from '@emoji-mart/data'
import Picker from '@emoji-mart/vue'

const emit = defineEmits(['select'])

const mostrar = ref(false)
const emojiData = data

function onEmojiSelect(emoji) {
  emit('select', emoji.native)
  mostrar.value = false
}
</script>
```

### Opción 2: Emoji Picker Simple (Más ligero)
```bash
npm install emoji-picker-element
```

## 📝 Uso en el Chat

### 1. Input de Mensaje con Emojis

```vue
<!-- frontend/src/components/chat/ChatInput.vue -->
<template>
  <div class="chat-input">
    <EmojiPicker @select="agregarEmoji" />
    <input
      v-model="texto"
      @keyup.enter="enviar"
      placeholder="Escribe un mensaje..."
    />
    <button @click="enviar">Enviar</button>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import EmojiPicker from './EmojiPicker.vue'

const texto = ref('')

function agregarEmoji(emoji) {
  texto.value += emoji
}

function enviar() {
  // El emoji se envía como parte del texto
  // Ejemplo: "¡Hola! 😊 ¿Cómo estás?"
  emit('enviar', texto.value)
  texto.value = ''
}
</script>
```

### 2. Renderizado de Emojis

Los emojis se renderizan automáticamente en HTML si:
- El texto está en UTF-8
- El navegador soporta emojis (todos los navegadores modernos)

```vue
<!-- frontend/src/components/chat/ChatMensaje.vue -->
<template>
  <div class="mensaje">
    <!-- Los emojis se renderizan automáticamente -->
    <p>{{ mensaje.mensaje }}</p>
  </div>
</template>
```

### 3. Estilos para Emojis

```css
.mensaje {
  font-size: 1rem;
  line-height: 1.5;
}

/* Asegurar que los emojis se vean bien */
.mensaje p {
  font-family: 'Apple Color Emoji', 'Segoe UI Emoji', 'Noto Color Emoji', sans-serif;
  word-wrap: break-word;
}

/* Tamaño de emojis en el picker */
.emoji-picker__popup {
  position: absolute;
  bottom: 100%;
  right: 0;
  background: #161616;
  border-radius: 12px;
  padding: 1rem;
  z-index: 1000;
}
```

## 🔍 Validación en Backend

### Form Request para Mensajes

```php
// app/Http/Requests/Chat/SolicitudEnviarMensaje.php
public function rules(): array
{
    return [
        'mensaje' => [
            'required',
            'string',
            'max:5000',
            // Validar que el mensaje no sea solo espacios/emojis
            function ($attribute, $value, $fail) {
                $texto = trim($value);
                if (empty($texto)) {
                    $fail('El mensaje no puede estar vacío.');
                }
            },
        ],
    ];
}
```

**Nota**: Los emojis son caracteres Unicode válidos, no necesitan validación especial.

## 📊 Almacenamiento

### Ejemplo de Mensaje con Emojis

```php
// El mensaje se almacena tal cual
$mensaje = "¡Hola! 😊 ¿Cómo estás? 👍";

Mensaje::create([
    'chat_id' => $chatId,
    'emisor_tipo' => 'coach',
    'mensaje' => $mensaje, // Se guarda con emojis incluidos
    'enviado_en' => now(),
    'leido' => false,
]);
```

**En la base de datos:**
```
mensaje: "¡Hola! 😊 ¿Cómo estás? 👍"
```

**En JSON (API):**
```json
{
  "id": 1,
  "mensaje": "¡Hola! 😊 ¿Cómo estás? 👍",
  "emisor_tipo": "coach",
  "enviado_en": "2024-01-15 10:30:00"
}
```

## 🚀 Implementación Paso a Paso

### Paso 1: Verificar/Actualizar Charset de BD
```bash
# Verificar charset actual
php artisan tinker
>>> DB::select("SHOW CREATE TABLE mensajes");

# Si no es utf8mb4, crear migración
php artisan make:migration ensure_utf8mb4_for_mensajes_table
```

### Paso 2: Instalar Picker de Emojis
```bash
cd frontend
npm install @emoji-mart/vue @emoji-mart/data
```

### Paso 3: Crear Componente EmojiPicker
- Crear `frontend/src/components/chat/EmojiPicker.vue`
- Integrar en `ChatInput.vue`

### Paso 4: Probar
- Enviar mensaje con emojis desde el frontend
- Verificar que se guarda correctamente en BD
- Verificar que se renderiza correctamente

## ⚠️ Consideraciones

### 1. Tamaño de Mensaje
- Cada emoji puede ocupar 1-4 bytes en UTF-8
- El límite de 5000 caracteres sigue siendo válido
- Un mensaje con muchos emojis puede ser más corto en caracteres pero similar en bytes

### 2. Compatibilidad
- ✅ Todos los navegadores modernos soportan emojis
- ✅ iOS, Android, Windows, macOS - todos soportan emojis
- ⚠️ Navegadores muy antiguos pueden mostrar cuadrados en lugar de emojis

### 3. Búsqueda
Si implementas búsqueda de mensajes:
- Los emojis se pueden buscar como texto normal
- Ejemplo: buscar "😊" encontrará todos los mensajes con ese emoji

### 4. Filtrado
Si necesitas filtrar emojis (opcional):
```php
// Remover emojis (rara vez necesario)
$texto = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $mensaje);
```

## 📱 Ejemplos de Uso

### Mensaje con Emojis
```
Usuario escribe: "¡Hola! 😊 ¿Cómo estás? 👍"
Se guarda: "¡Hola! 😊 ¿Cómo estás? 👍"
Se muestra: "¡Hola! 😊 ¿Cómo estás? 👍"
```

### Mensaje Mixto
```
Usuario escribe: "Entrenamiento completado 💪\nRutina: Piernas\nSeries: 4x12"
Se guarda tal cual con emojis y saltos de línea
```

## ✅ Checklist

- [ ] Verificar que la tabla `mensajes` está en `utf8mb4`
- [ ] Verificar `config/database.php` tiene `utf8mb4`
- [ ] Instalar librería de emoji picker
- [ ] Crear componente `EmojiPicker.vue`
- [ ] Integrar en `ChatInput.vue`
- [ ] Probar envío de mensajes con emojis
- [ ] Verificar renderizado en diferentes dispositivos

## 🎯 Resumen

**Los emojis funcionan automáticamente si:**
1. ✅ La BD está en `utf8mb4` (ya debería estarlo en Laravel moderno)
2. ✅ El campo `mensaje` es `text` (ya lo es)
3. ✅ El frontend renderiza el texto tal cual (automático)

**Solo necesitas:**
- Agregar un picker de emojis en el frontend para facilitar la selección
- No necesitas cambios en el backend (los emojis son texto UTF-8 normal)






