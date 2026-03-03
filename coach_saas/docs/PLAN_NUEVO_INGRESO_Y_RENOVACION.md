# Plan: Nuevo ingreso, actualización post-renovación y margen para evaluaciones

Plan actualizado: (1) Nuevo ingreso vía pendiente_activacion en clientes, (2) Actualizar rutina/dieta con pendiente_actualizacion en clientes (bandera manual), (3) Margen editable por coach (y por cliente) con bandera 5 días antes, (4) Tabla de configuración del coach, (5) Pasarela de pago (futuro).

---

## Resumen de casos

| Caso | Cómo se identifica | Acción del coach |
|------|--------------------|------------------|
| **Nuevo ingreso** | Se inscribió por transferencia o pasarela; cuenta creada **inactiva**. En tabla `clientes`: `pendiente_activacion = true`. | Activar cuenta y dar rutina, dieta, etc. Al activar: `pendiente_activacion = false`. |
| **Se venció y pagó de nuevo** | Ya tiene historial; no es nuevo. Al renovar/reactivar la suscripción se pone en el **cliente** `pendiente_actualizacion = true`. | Actualizar dieta y rutina. Marcar como actualizado cuando termine (bandera manual). |
| **Renovación a tiempo** | Al renovar el plan se pone en el **cliente** `pendiente_actualizacion = true`. | Actualizar rutina y/o dieta (puede ser solo rutina un día y dieta días después). **Bandera manual:** el coach marca "Actualizado" cuando considere que terminó. |
| **Próximo a evaluación** | Margen por defecto del coach o **por cliente** (ej. plan alimentación: cada 2 semanas; otros cada 4). Se usa **5 días antes** de cumplir ese margen como bandera. Sin cita en agendada/confirmada/reagendar. | Ver lista de clientes, coordinar y agendar la siguiente evaluación. |

---

## 1. Nuevo ingreso (pendiente de activación)

**Objetivo:** Identificar a quienes se inscribieron con el coach (transferencia o pasarela): solo se les crea la cuenta con estatus **inactivo**. Diferenciarlos de quienes están inactivos por vencimiento de suscripción.

**Problema actual:** Tanto "nuevo signup" como "vencido y desactivado" quedan con `activo = false`.

**Solución:** Bandera en tabla `clientes`: `pendiente_activacion` (boolean, default `false`).

- **Al crear cliente por registro** (transferencia o pasarela) en `ControladorRegistro`: en `Cliente::create([...])` añadir `'pendiente_activacion' => true`.
- **Al activar** en `ControladorCliente::activar`: además de `activo => true`, setear `'pendiente_activacion' => false`.
- **Al crear cliente manualmente** en `ControladorCliente::almacenar`: no setear `pendiente_activacion` (queda `false`); no son "nuevo ingreso" por link/pasarela.

**Backend**

- Migración: tabla `clientes`, columna `pendiente_activacion` (boolean, default false).
- Modelo `Cliente`: añadir a `$fillable` y `$casts`.
- `ClienteResource`: exponer `es_nuevo_ingreso` (alias de `pendiente_activacion`).
- `ControladorCliente::index`: filtro `?nuevo_ingreso=1` → `where('pendiente_activacion', true)`.
- Dashboard en `ControladorPerfil`: contador `nuevo_ingreso` (clientes del coach con `pendiente_activacion = true`).

**Frontend**

- `UsuariosView.vue`: badge "Nuevo ingreso" cuando `c.es_nuevo_ingreso`; opcional filtro "Solo nuevo ingreso" con `?nuevo_ingreso=1`.
- `PerfilView.vue`: estadística "Nuevo ingreso" con contador y lista (PerfilStatModal), enlace a clientes con filtro `nuevo_ingreso=1`.

---

## 2. Marcador "actualizar rutina/dieta" (bandera manual en cliente)

**Objetivo:** Tras renovación (manual o pasarela) o reactivación de quien se venció, el coach debe actualizar dieta y rutina. La bandera debe ser **manual**: el coach marca "Actualizado" cuando haya terminado (puede subir solo rutina un día y la dieta días después).

**Por qué en tabla `clientes` y no en `suscripciones`:** El cliente puede tener planes de 3 o 6 meses (u otros). La necesidad de "actualizar rutina/dieta" es del **cliente**, no de una suscripción concreta. Una sola bandera por cliente simplifica la lógica y la UI.

**Backend**

- Migración: tabla `clientes`, columna `pendiente_actualizacion` (boolean, default `false`).
- Modelo `Cliente`: `pendiente_actualizacion` en `$fillable` y `$casts` (boolean).
- `ControladorSuscripcion::renovar`: al renovar, obtener el cliente de la suscripción y setear `$suscripcion->cliente->update(['pendiente_actualizacion' => true])`.
- Futuro flujo **pasarela de pago** o **reactivación** que extienda `fecha_fin` de una suscripción: también actualizar el **cliente** asociado con `pendiente_actualizacion => true`.
- **Marcar como actualizado:** Permitir al coach poner `pendiente_actualizacion = false` en el cliente. Opciones: (A) extender `ControladorCliente::actualizar` para aceptar `pendiente_actualizacion` en el request; (B) endpoint dedicado `POST .../clientes/{id}/marcar-actualizado` que ponga `pendiente_actualizacion = false`.
- `ClienteResource`: incluir `pendiente_actualizacion` en el array.

**Frontend**

- `UsuariosView.vue`: badge "Actualizar rutina/dieta" (o "Actualización pendiente") cuando `c.pendiente_actualizacion`.
- `ClienteDetalleModal.vue`: si `cliente.pendiente_actualizacion`, mostrar aviso y botón **"Marcar como actualizado"** que llame al endpoint sobre el cliente; tras éxito actualizar estado local para que desaparezca el aviso.

---

## 3. Margen del coach para agendar evaluación (5 días antes como bandera)

**Objetivo:** Cada coach usa un margen (cada cuánto debería el cliente volver a ser evaluado). Ese margen debe ser **editable** por el coach. Se usa **5 días antes** de cumplir ese margen como **bandera** para saber qué clientes debería agendar evaluaciones y coordinar los tiempos del cliente y del coach.

**Por cliente o por defecto del coach:** No es siempre el mismo intervalo para todos. Por situaciones concretas (p. ej. plan de alimentación) algunos clientes necesitan evaluación **cada 2 semanas**, otros cada 4. Por eso el margen puede ser:

- **Por defecto (coach):** en `configuraciones_coach.semanas_entre_evaluaciones` (ej. 4 semanas).
- **Por cliente (opcional):** en tabla `clientes`, campo `semanas_entre_evaluaciones` (nullable). Si el cliente tiene valor (ej. 2), se usa ese; si es null, se usa el del coach. Así se cubren casos particulares sin cambiar el valor general.

**Dónde guardar el margen:** En la **configuración del coach** (`configuraciones_coach`): datos bancarios (nombre de cuenta, banco, clave) y **margen para la evaluación** (`semanas_entre_evaluaciones`), para que cada coach pueda decidir cada cuántas semanas agendar la siguiente evaluación. A futuro: tienda online. El campo del margen por defecto es `semanas_entre_evaluaciones` en esa tabla. En **clientes** se añade `semanas_entre_evaluaciones` (nullable) para override por cliente. Ver sección **"Tabla de configuración del coach"** más abajo.

**Regla "5 días antes":**

- Fecha de referencia = última evaluación `completada` (su fecha) o, si no hay, `fecha_inicio` de la suscripción activa.
- **Semanas a usar por cliente:** `cliente.semanas_entre_evaluaciones ?? coach.configuracion.semanas_entre_evaluaciones` (si el cliente tiene valor propio, ese; si no, el del coach).
- Días del margen = semanas * 7.
- **Bandera:** si desde la fecha de referencia han pasado **>= (días del margen − 5)** días, el cliente entra en la ventana "próximo a evaluación". Excluir si ya tiene evaluación en agendada/confirmada/reagendar.

**Backend**

- Tabla y modelo en sección "Tabla de configuración del coach". Incluir `semanas_entre_evaluaciones` (unsignedTinyInteger, default 4) como **valor por defecto del coach**. En tabla **clientes**, añadir columna `semanas_entre_evaluaciones` (unsignedTinyInteger, nullable): si está definida, se usa para ese cliente; si es null, se usa la de configuraciones_coach.
- Perfil/configuración: cargar y exponer configuración; PATCH para editar. Lógica "próximo a evaluación": para cada cliente, `semanas = cliente.semanas_entre_evaluaciones ?? coach.configuracion.semanas_entre_evaluaciones`, luego aplicar regla 5 días antes.
- ControladorPerfil: contador y endpoint lista "próximo a evaluación". Opcional ClienteResource: `proximo_a_evaluacion` (boolean). Endpoint o PATCH cliente para que el coach edite `semanas_entre_evaluaciones` por cliente (ej. en detalle del cliente).

**Frontend**

- En perfil o configuración del coach, campo editable "Cada cuánto agendar evaluación del cliente" (semanas) como **valor por defecto**. En detalle del cliente (ClienteDetalleModal o formulario editar cliente), campo opcional "Cada cuánto evaluar a este cliente" (semanas, ej. 2 o 4): si se rellena, ese cliente usa ese intervalo; si se deja vacío, usa el del coach. PerfilView: tarjeta "Próximo a evaluación" + modal; opcional badge en UsuariosView.

---

## Tabla de configuración del coach (recomendaciones)

**Objetivo:** Una sola tabla con las configuraciones del coach: datos bancarios para transferencia, **margen para la evaluación** (para que los coaches puedan decidir cada cuánto agendar), y a futuro tienda online asociada al coach.

**Margen para la evaluación:** En la configuración del coach se incluye el **margen para la evaluación** (`semanas_entre_evaluaciones`) para que cada coach pueda **decidir** cada cuántas semanas quiere que se le avise para agendar la siguiente evaluación (ej. 3, 4 o 5 semanas). Es editable en la pantalla de configuración del coach. Si un cliente tiene su propio valor en `clientes.semanas_entre_evaluaciones`, se usa ese; si no, el de la configuración del coach. La bandera "próximo a evaluación" se activa X días antes de cumplir ese margen (p. ej. 5 días).

**Recomendaciones:**

1. **Nombre:** `configuraciones_coach` (o `configuraciones` con `coach_id` único). Deja claro que es por coach y evita confusión con otras configuraciones.
2. **Relación:** 1:1 con `coaches`. Un coach tiene una fila (creada al alta o al guardar configuración por primera vez). Foreign key `coach_id` único.
3. **Campos iniciales:**

| Campo                        | Tipo                           | Uso                                                                 |
| ---------------------------- | ------------------------------ | ------------------------------------------------------------------- |
| `id`                         | bigint PK                      |                                                                     |
| `coach_id`                   | FK coaches, unique             |                                                                     |
| `nombre_cuenta`              | string, nullable               | Nombre de la cuenta (transferencia)                                 |
| `banco`                      | string, nullable               | Banco                                                               |
| `clave`                      | string, nullable               | CLABE / número de cuenta. No loguear en claro; valorar cifrado.     |
| `semanas_entre_evaluaciones` | unsignedTinyInteger, default 4 | **Margen para la evaluación:** cada cuántas semanas el coach decide agendar la siguiente evaluación (editable por el coach). Bandera "próximo a evaluación" X días antes (ej. 5). |
| `timestamps`                 |                                |                                                                     |

4. **Futuro (tienda online):** Añadir columnas en esta tabla (ej. `tienda_activa`, `tienda_slug`) o columna JSON `opciones_tienda` hasta definir el modelo de tienda. Todo en una tabla evita dispersar datos del coach.
5. **Seguridad:** `clave` (y datos bancarios) no exponer por defecto en APIs; solo en "editar mi configuración" del coach autenticado y, si se muestra, enmascarado (ej. últimos 4 dígitos).
6. **Plan actual:** En el punto 3 (margen para evaluación) se usa `configuraciones_coach.semanas_entre_evaluaciones` como **valor por defecto**. En tabla **clientes** se añade `semanas_entre_evaluaciones` (nullable) para que, en casos particulares (p. ej. plan de alimentación que requiere evaluación cada 2 semanas), el coach pueda definir un intervalo distinto por cliente; si el cliente no tiene valor, se usa el del coach. Una sola migración de config con todos los campos; migración en clientes para la columna opcional; endpoint de configuración y actualización de cliente que permitan editar ambos.

---

## Orden sugerido de implementación

1. **Nuevo ingreso:** Migración `pendiente_activacion` en clientes; setear en registro y en activar; ClienteResource y filtro en index; contador en dashboard; UI (badge, filtro, estadística en PerfilView).
2. **Actualizar rutina/dieta:** Migración `pendiente_actualizacion` en clientes; actualizar cliente en renovar() y en futura pasarela/reactivación; ClienteResource; endpoint marcar actualizado (sobre cliente); UI en UsuariosView y ClienteDetalleModal.
3. **Margen para evaluación y tabla de configuración:** Crear tabla `configuraciones_coach` con coach_id único y campos: nombre_cuenta, banco, clave, semanas_entre_evaluaciones (valor por defecto del coach). En **clientes**, columna `semanas_entre_evaluaciones` (nullable) para override por cliente (ej. 2 o 4 semanas según plan alimentación u otra situación). Lógica "5 días antes" usando para cada cliente `cliente.semanas_entre_evaluaciones ?? configuracion.semanas_entre_evaluaciones`. Endpoint configuración + edición de cliente para el campo opcional; contador y endpoint lista "próximo a evaluación"; UI (config por defecto en Perfil/configuración, campo por cliente en detalle cliente; tarjeta + modal). Opcional badge en UsuariosView.

---

## Pasarela de pago (futuro)

- **Alta de cliente:** Igual que con transferencia: cuenta inactiva y en el cliente `pendiente_activacion = true` hasta que el coach active.
- **Renovación de suscripción:** Cualquier flujo que extienda `fecha_fin` de una suscripción (webhook de pago, etc.) debe setear en el **cliente** asociado `pendiente_actualizacion => true`, para que el coach vea el aviso y marque como actualizado cuando termine de actualizar rutina/dieta.
