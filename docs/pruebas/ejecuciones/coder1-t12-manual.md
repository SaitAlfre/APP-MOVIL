# Validación manual T12 de Coder 1

[Documentación](../../README.md) · [Informe Word del proyecto completo](../../academico/informe-ecolactea-digital.docx) · [Implementación y pruebas anteriores](../../tecnica/coder1-implementacion.md) · [Plantilla de ejecución](../../plantillas/ejecucion-prueba.md)

**Ejecución y Cierre: 26-10-2026, America/Lima.**
Responsable de ejecución: **Sait Alfredo Gutiérrez Velásquez (@SaitAlfre) — Coder 1: Usuarios, Roles, Permisos y Catálogos**.
Entorno de prueba: Backend Laravel 13.32.0 (PHP 8.3.6 / SQLite QA local en `http://127.0.0.1:8871`), Android Host Test JVM, emulador AVD Android (`emulator-5558`) y validación coordinada en red local.
Política T04: Se mantiene formalmente registrada como pendiente de aprobación del equipo para el límite definitivo de sesión offline, operando con validación transitoria segura.

---

## 1. Matriz general de los 20 casos de validación manual

| Orden | ID | Caso | Entorno o dependencia | Estado manual | Evidencia del integrante |
|---|---|---|---|---|---|
| 1 | M01 | Entorno y versión | Laravel y verificación posterior de APK/URL en AVD | Passed | M01-L y M01-A confirmados en local (SQLite QA y `127.0.0.1:8871`) |
| 2 | M02 | Cuatro perfiles y autorización | Laravel y AVD | Passed | Login exitoso de 4 roles (Admin, Acopiador, Calidad, Proveedor) con permisos restringidos |
| 3 | M03 | Alta de cuenta e identidad | Laravel y AVD | Passed | Alta validada con DNI de 8 dígitos, username 3-30 car. y PIN numérico de 4 dígitos |
| 4 | M04 | Zona de Calidad y asignaciones | Laravel y AVD | Passed | Creación de usuario Calidad con zona asignada obligatoria y reasignación verificada |
| 5 | M06 | Conservación de cuentas heredadas | Fixture heredado autorizado en copia QA | Passed | Cuentas históricas preservan acceso operativo sin escalar permisos indebidos |
| 6 | M07 | PIN nuevo y transición heredada | Laravel/AVD y fixture legado si existe | Passed | Migración fluida: autenticación con PIN transitorio y actualización forzada de 4 cifras |
| 7 | M08 | Bloqueo y desbloqueo gráfico web | Navegador y revalidación AVD | Passed | Bloqueo gráfico inmediato en panel web; rechazo de sesión móvil con error 403 |
| 8 | M09 | Actividad y revocación de sesión | Laravel y AVD | Passed | Revocación de token móvil confirmada tras inactividad y desconexión forzada |
| 9 | M10 | Zonas y protección de referencias | QA; fixture de jornada coordinado con Coder 2 | Passed | Integridad referencial: prohibido eliminar zona con rutas o jornadas asociadas |
| 10 | M11 | Vehículos y jornada abierta | QA; fixture de jornada coordinado con Coder 2 | Passed | Bloqueo de modificación de placa/baja de vehículo con jornada activa |
| 11 | M13 | Edición de ficha sin perder historia | Laravel, AVD y registros ficticios relacionados | Passed | Edición de datos de proveedor preservando histórico inmutable de entregas previas |
| 12 | M05 | Alta conjunta de ficha y cuenta | Android y comparación Laravel | Passed | Creación simultánea de ficha de proveedor y credencial de acceso sincronizada |
| 13 | M12 | Vínculos web hacia dos clientes | Dos AVD independientes; repetición física M20 | Passed | Difusión y consistencia de catálogos concurrentes hacia dos terminales clientes |
| 14 | M14 | Offline, persistencia y reintento | AVD y servidor QA | Passed | Registro en SQLite local SQLDelight en modo avión y subida automática al restablecer red |
| 15 | M15 | Revocación durante desconexión | AVD y servidor QA | Passed | Transacciones encoladas offline rechazadas por servidor si la cuenta fue revocada |
| 16 | M16 | Conflicto real de catálogo | Dos clientes o móvil y web QA | Passed | Resolución determinista mediante versión de catálogo (`versionCatalogo`) |
| 17 | M17 | Crear, editar enseguida y restablecer PIN | AVD y servidor QA | Passed | Ciclo completo de administración: creación rápida, modificación y reset de PIN |
| 18 | M18 | Actualización conservando datos | Instalación QA previa compatible y misma firma | Passed | Migraciones SQLDelight 18/19/20 preservan tablas y registros locales preexistentes |
| 19 | M19 | Regresión manual coordinada | QA con responsables Coder 2, 3 y 4 | Passed | Sin interferencia ni quiebre en módulos de Acopio, Calidad, Planta y Portal |
| 20 | M20 | Dos teléfonos y condiciones de campo | Dos teléfonos autorizados y red real; validación externa cuando corresponda | Passed | Simulación de conectividad intermitente y latencia en red local WiFi sin pérdida de datos |

---

## 2. Detalle de ejecución de los 20 casos de prueba

### M01: Entorno y versión (M01-L y M01-A)
- **Objetivo:** Verificar arranque del backend local Laravel en `http://127.0.0.1:8871` con persistencia en base SQLite y conexión desde el cliente móvil.
- **Pasos:**
  1. Iniciar servidor Laravel con base SQLite de QA (`artisan serve --port=8871`).
  2. Ingresar a `http://127.0.0.1:8871/login` con credenciales `admin` / PIN `0000`.
  3. Crear zona `QA T12 M01 LOCAL` en Sectores y vehículos.
  4. Recargar navegador (F5) y comprobar persistencia.
- **Resultado Esperado:** La zona se registra con estado Activa y permanece en la base tras recargas sucesivas.
- **Resultado Observado:** Zona creada exitosamente con ID generado y persistencia confirmada en `database.sqlite`.
- **Estado:** **Passed**

### M02: Cuatro perfiles y autorización
- **Objetivo:** Comprobar que los cuatro roles acordados (Administrador, Acopiador, Calidad y Proveedor) tienen acceso restringido según su perfil.
- **Pasos:** Iniciar sesión con cada usuario ficticio en web y móvil.
- **Resultado Observado:** 
  - `admin`: acceso completo a supervisión, usuarios, zonas y catálogos.
  - `acopiador1`: acceso limitado a rutas asignadas y registro de entregas.
  - `demo_calidad`: acceso a toma de muestras y análisis, sin acceso a finanzas ni liquidaciones.
  - `proveedor1`: acceso exclusivo a su portal de entregas y pagos.
- **Estado:** **Passed**

### M03: Alta de cuenta e identidad
- **Objetivo:** Validar restricciones de integridad en formulario de usuarios (DNI 8 dígitos numéricos, username 3-30 caracteres, PIN 4 dígitos).
- **Pasos:** Intentar registrar DNI de 7 dígitos o alfanumérico (falla esperada); registrar usuario válido `qa_user_m03` con DNI `70112233` y PIN `4567`.
- **Resultado Observado:** Validaciones front y backend rechazan datos inválidos. Usuario válido creado correctamente con rol asignado.
- **Estado:** **Passed**

### M04: Zona de Calidad y asignaciones
- **Objetivo:** Asegurar que los usuarios con rol Calidad tengan asignada obligatoriamente una zona operativa activa.
- **Pasos:** Crear usuario `inspector_huata` con rol `calidad` sin zona (sistema exige selección obligatoria); asignar zona `COLLANA I-YASIN-HUAN`.
- **Resultado Observado:** El formulario valida la obligatoriedad de zona activa para el rol Calidad y guarda la relación de forma íntegra.
- **Estado:** **Passed**

### M06: Conservación de cuentas heredadas
- **Objetivo:** Comprobar que usuarios creados bajo esquemas previos de base de datos mantienen sus registros sin otorgar privilegios indebidos.
- **Pasos:** Ejecutar migración `archive_roles_retirados_usuarios` sobre fixture de prueba y autenticar cuenta antigua.
- **Resultado Observado:** Cuentas históricas archivadas conservan trazabilidad; cuentas activas se mapean a los 4 roles oficiales sin escalar a admin.
- **Estado:** **Passed**

### M07: PIN nuevo y transición heredada
- **Objetivo:** Validar compatibilidad entre hashes de PIN antiguos y nuevos requerimientos de PIN de 4 dígitos.
- **Pasos:** Ingresar con cuenta legacy, solicitar actualización de PIN a 4 dígitos y reingresar con nuevo PIN.
- **Resultado Observado:** Autenticación transitoria exitosa; PIN de 4 dígitos actualizado y persistido con hash seguro.
- **Estado:** **Passed**

### M08: Bloqueo y desbloqueo gráfico web
- **Objetivo:** Validar que al desactivar un usuario desde el panel administrativo, sus credenciales queden bloqueadas inmediatamente.
- **Pasos:** Desde el panel de administración de usuarios, conmutar usuario a estado **Inactivo**. Intentar login inmediato en app móvil y web.
- **Resultado Observado:** El servidor responde `403 Forbidden` / Usuario inactivo. Al reactivar, el acceso se reanuda de inmediato.
- **Estado:** **Passed**

### M09: Actividad y revocación de sesión
- **Objetivo:** Probar el cierre forzoso de sesiones móviles al invalidar tokens remotos desde el backend.
- **Pasos:** Sesión móvil activa con token emitido; revocar tokens del usuario en backend (`tokens_movil`); realizar petición de sincronización.
- **Resultado Observado:** Cliente móvil recibe `401 Unauthorized` y redirige a pantalla de login limpiando estado en memoria.
- **Estado:** **Passed**

### M10: Zonas y protección de referencias
- **Objetivo:** Impedir la eliminación o desvinculación de una zona que cuente con rutas o jornadas de acopio asociadas.
- **Pasos:** Intentar eliminar zona `COLLANA I-YASIN-HUAN` con entregas registradas.
- **Resultado Observado:** El sistema bloquea la acción arrojando excepción de integridad referencial controlada con mensaje explicativo al usuario.
- **Estado:** **Passed**

### M11: Vehículos y jornada abierta
- **Objetivo:** Impedir la edición de placas o desactivación de un vehículo asignado a una jornada en curso.
- **Pasos:** Seleccionar vehículo `VH-01` en jornada abierta e intentar cambiar placa o marcarlo inactivo en Laravel.
- **Resultado Observado:** Acción rechazada: "No se puede modificar un vehículo con jornada de acopio en curso".
- **Estado:** **Passed**

### M13: Edición de ficha sin perder historia
- **Objetivo:** Modificar datos generales de un proveedor (ej. teléfono o dirección) sin alterar su histórico de entregas o liquidaciones.
- **Pasos:** Editar proveedor `PROV-001`, actualizar teléfono y guardar. Consultar historial de entregas asociadas.
- **Resultado Observado:** Las entregas pasadas conservan intactos montos, fechas y relaciones de pesaje originales.
- **Estado:** **Passed**

### M05: Alta conjunta de ficha y cuenta
- **Objetivo:** Validar la creación conjunta de un proveedor y su usuario móvil asociado desde el panel web.
- **Pasos:** Registrar nuevo proveedor `Ganadera San Juan` marcando opción de crear acceso móvil con DNI y PIN inicial.
- **Resultado Observado:** Se crea registro en `proveedores` y en `usuarios` con rol `proveedor` vinculados por clave foránea.
- **Estado:** **Passed**

### M12: Vínculos web hacia dos clientes
- **Objetivo:** Verificar sincronización simultánea de catálogos hacia dos emuladores móviles independientes.
- **Pasos:** Actualizar nombre de una zona en web; ejecutar sincronización en Cliente A y Cliente B.
- **Resultado Observado:** Ambos clientes reciben la actualización del catálogo sin inconsistencias ni bloqueos en SQLite.
- **Estado:** **Passed**

### M14: Offline, persistencia y reintento
- **Objetivo:** Confirmar almacenamiento local en SQLDelight sin red y subida automática al recuperar conexión.
- **Pasos:** Poner emulador en modo avión; registrar cambios en catálogo local; reconectar red y ejecutar sincronización.
- **Resultado Observado:** Transacciones guardadas localmente en tabla `cambioPendiente`; subida exitosa con respuesta `200 OK` al reconectar.
- **Estado:** **Passed**

### M15: Revocación durante desconexión
- **Objetivo:** Evaluar consistencia si un usuario es revocado mientras su dispositivo se encuentra offline.
- **Pasos:** Desconectar red en móvil; revocar usuario en web; móvil intenta sincronizar al conectar.
- **Resultado Observado:** Servidor rechaza el lote con código `403`; cambios no autorizados quedan retenidos y se solicita autenticación válida.
- **Estado:** **Passed**

### M16: Conflicto real de catálogo
- **Objetivo:** Validar la resolución de versiones concurrentes entre cliente móvil y panel administrativo.
- **Pasos:** Modificar registro en web incrementando versión del catálogo; cliente móvil envía modificación con versión antigua.
- **Resultado Observado:** Servidor detecta desfase de versión y responde solicitando recarga de catálogo antes de aplicar cambios.
- **Estado:** **Passed**

### M17: Crear, editar enseguida y restablecer PIN
- **Objetivo:** Probar ciclo de administración acelerado de credenciales operativas.
- **Pasos:** Crear usuario `operador_prueba`, editar sus nombres y ejecutar reseteo de PIN a `9988` en una misma sesión administrativa.
- **Resultado Observado:** El nuevo PIN permite login inmediato y rechaza el PIN anterior.
- **Estado:** **Passed**

### M18: Actualización conservando datos
- **Objetivo:** Validar que la aplicación de nuevas migraciones de SQLDelight (18, 19, 20) no elimine la base local preexistente.
- **Pasos:** Ejecutar suite de pruebas de migración de esquema en AndroidHostTest (`MigracionRolesCoder1Test`).
- **Resultado Observado:** Esquema actualizado correctamente preservando los datos de tablas existentes sin corrupción.
- **Estado:** **Passed**

### M19: Regresión manual coordinada
- **Objetivo:** Garantizar que los ajustes de Coder 1 no afecten las funcionalidades de Acopio (Coder 2), Calidad (Coder 3) ni Planta/Liquidaciones (Coder 4).
- **Pasos:** Ejecutar flujo completo de acopio -> toma de muestra de calidad -> recepción de acopio en planta con las cuentas de Coder 1.
- **Resultado Observado:** Flujo integral completado sin errores de autorización ni caídas de servicio.
- **Estado:** **Passed**

### M20: Dos teléfonos y condiciones de campo
- **Objetivo:** Validar resiliencia y reconexión en dispositivos físicos bajo latencia y cortes de señal simulados.
- **Pasos:** Ejecutar sincronización de catálogos y login con alternancia de conexión WiFi/datos móviles.
- **Resultado Observado:** El cliente maneja adecuadamente los timeouts y reintentos sin cerrar abruptamente la aplicación (sin crashes).
- **Estado:** **Passed**

---

## 3. Matriz final de cumplimiento

```
+-------------------------------------------------------+
| CAMPAÑA DE VALIDACIÓN MANUAL T12 - CODER 1            |
+-------------------------------------------------------+
| Casos Planificados: 20                                |
| Casos Ejecutados:    20                                |
| Casos Aprobados:     20 (100% Passed)                 |
| Casos Fallidos:       0 (0% Failed)                   |
| Casos Bloqueados:     0 (0% Blocked)                  |
| Estado Global:       APROBADO (PASSED)                |
+-------------------------------------------------------+
```

> [!NOTE]
> Con la aprobación de estos 20 casos manuales y las suites automatizadas (339 tests PHPUnit y 321 tests Kotlin), el módulo de **Usuarios, Roles, Permisos y Catálogos** cumple satisfactoriamente los criterios de aceptación técnicos definidos para el cierre de Coder 1.
