# Estados de cuenta y estado productivo de proveedores (T-93)

Trazabilidad: RF-USU-01 / RF-USU-04 → [HU-24](../scrum/product-backlog.md#hu-24) ([CA-HU-24-01](../scrum/product-backlog.md#ca-hu-24-01), [CA-HU-24-02](../scrum/product-backlog.md#ca-hu-24-02)) y [HU-13](../scrum/product-backlog.md#hu-13) ([CA-HU-13-02](../scrum/product-backlog.md#ca-hu-13-02)) → [T-93](../scrum/tareas.md#t-93) → código y pruebas de esta nota → evidencia de ejecución local abajo.

**Estado:** implementado y probado en el entorno local el 28-10-2026; sin commit ni despliegue. No cumple todavía la Definition of Done: faltan revisión de par, compilación frontend (`npm run build`), pruebas JavaScript (`node --test tests/js`) en un equipo con Node.js y recorrido manual en navegador y en dispositivo Android.

## Problemas resueltos

1. **Editar proveedor no guardaba el estado.** El selector de estado estaba en un segundo formulario (`PATCH /admin/proveedores/{id}/estado`) y «Guardar cambios» solo enviaba el `PUT` de datos: el mensaje de éxito aparecía y el estado seguía en Activo.
2. **Desactivar y Bloquear** en Usuarios y accesos tenían el mismo efecto visible y confundían.
3. La cuenta, el bloqueo automático por intentos y el estado productivo de la ficha se mostraban mezclados.

## Proveedores: un solo «Guardar cambios»

- El selector de estado se asocia al formulario principal (`form="..."`), sin formularios anidados, y conserva su lugar en la tarjeta lateral. Se retiró el botón «Actualizar estado».
- `UpdateProveedorRequest` valida `estado` (`activo|suspendido|retirado`) y `motivo_estado`.
- `ActualizarProveedorUseCase` bloquea la fila y, dentro de una sola `DB::transaction`, actualiza los datos y llama a `CambiarEstadoProveedorUseCase`. Si el cambio de estado falla, se revierten también los datos y su auditoría.
- Al elegir Suspendido o Retirado, `resources/js/estado-proveedor.js` pide confirmación con los textos acordados y permite escribir el motivo; Cancelar o Escape restauran el valor anterior y no envían nada.
- La auditoría (`AuditarCambiosOperativos`) ahora incluye DNI, teléfono, dirección y dueño, y registra el motivo del cambio de estado.
- La ruta `PATCH .../estado` se conserva para consumidores existentes y también acepta `motivo_estado`.

## Usuarios y accesos: Suspender acceso / Restaurar acceso

| Situación de la cuenta | Menú de tres puntos |
|---|---|
| Acceso habilitado | Restablecer PIN · Suspender acceso |
| Suspendida (manual o heredada `inactivo`) | Restablecer PIN · Restaurar acceso |

El lápiz «Editar usuario» queda fuera del menú.

- Endpoint único `POST /admin/usuarios/{id}/acceso` → `GestionarAccesoUsuarioUseCase`.
- **Suspender:** motivo obligatorio; marca la suspensión manual con actor, fecha y motivo; invalida sesiones web y tokens móviles; audita. No suspende al último administrador disponible.
- **Restaurar:** reactiva cuentas heredadas `inactivo` y quita la suspensión manual, pero **conserva** `bloqueado_hasta` e `intentos_fallidos` (bloqueo automático por seguridad) y no toca la ficha productiva. Los tokens anteriores ya fueron revocados: hace falta una autenticación nueva.
- **Restablecer PIN:** exactamente 4 dígitos y confirmación (`pin_confirmation`); no cambia el estado de la cuenta y el PIN no se audita.
- Las rutas `bloquear`, `desbloquear` y `PATCH estado` siguen existiendo por compatibilidad.

## Tres conceptos que el backend no mezcla

| Concepto | Dato | Quién lo cambia | Efecto |
|---|---|---|---|
| Suspensión administrativa | `usuarios.bloqueado_manualmente` (o `activo = false` heredado) | Administrador en Usuarios y accesos | Sin login web ni móvil; tokens revocados |
| Bloqueo automático de seguridad | `usuarios.bloqueado_hasta`, `intentos_fallidos` | Intentos fallidos de PIN | Temporal; restaurar no lo elimina |
| Estado productivo | `proveedores.estado` | Administrador en Proveedores | Restringe operaciones nuevas, no la cuenta |

`Usuario::estadoAdministrativo()` y el componente `x-ui.estado-cuenta` calculan lo que se muestra a partir de estos datos.

## Coordinación entre módulos

- **Suspender acceso de una cuenta con rol Proveedor (ajuste del 28-10-2026, a pedido del Product Owner):** en la misma transacción se suspende la ficha vinculada por `usuario_id` (nunca por nombre o DNI), se guarda `proveedores.suspension_origen = acceso_cuenta` y se audita la ficha con el motivo, el actor y la fecha. Sin login, sin tokens válidos y sin entregas nuevas para esa ficha.
  - Ficha **Retirada:** conserva Retirado (tiene prioridad). Ficha **ya Suspendida** por otra causa: se conserva y su origen no cambia. Aviso en pantalla en ambos casos.
  - Cuenta Proveedor **sin ficha:** solo se suspende la cuenta; la auditoría de la cuenta registra `ficha_productiva: sin_ficha`. No se crean datos.
  - Cuentas Administrador, Acopiador o Calidad: ninguna ficha cambia.
- **Restaurar acceso:** reactiva la ficha solo si está Suspendida con origen `acceso_cuenta`. Una suspensión independiente (hecha en Proveedores) o histórica sin origen conocido (anterior a la columna) se conserva y se muestra un aviso. El retiro y el bloqueo temporal se conservan.
- Cambiar el estado de la ficha en Proveedores (o desde la app de administración) borra el origen: la decisión pasa a ser independiente y «Restaurar acceso» ya no la revierte. Guardar los datos sin cambiar el estado conserva el origen.
- La migración `2026_10_28_210102_add_suspension_origen_to_proveedores_table` solo agrega una columna anulable; las fichas existentes quedan con origen nulo y no se reactivan automáticamente.
- Las rutas heredadas `bloquear`, `desbloquear` y `PATCH usuarios/{id}/estado` (sin uso en la interfaz) no sincronizan la ficha.
- **Ficha suspendida o retirada, cuenta activa:** la cuenta no cambia. Login y renovación devuelven `accesoOperativoProveedor = false` y `estadoProductivoProveedor`. Puede consultar sus propios datos y liquidaciones; los traslados y reclamos nuevos responden 403 `proveedor_no_activo`. Las entregas nuevas para una ficha no activa se rechazan (403 `proveedor_no_activo`); las ya registradas se conservan y se siguen pudiendo actualizar.
- **Cuenta con otros roles** (p. ej. Proveedor + Acopiador): el retiro de la ficha no limita sus otros roles.
- **Reactivar la ficha** no restaura una cuenta suspendida ni elimina bloqueos.
- En Proveedores se muestra el estado productivo y el de la cuenta vinculada (Sin cuenta móvil, Cuenta activa, Cuenta suspendida, Bloqueo temporal, Acceso operativo restringido…).
- En la tabla de Usuarios y accesos (ajuste del 28-10-2026), la columna Estado muestra solo el estado de la cuenta con etiquetas cortas: **Activo**, **Suspendido** o **Bloqueado temporalmente**. El tipo, el motivo y la fecha de la suspensión, o la hora en que termina el bloqueo, aparecen en un tooltip con texto equivalente para lectores de pantalla. La ficha productiva no ocupa la columna: se abre con «Ver ficha de proveedor» en el menú ⋮ (solo para quien puede ver Proveedores) o con «Ver ficha productiva» en Editar usuario, donde también se ve su restricción.
- Pestañas: **Todos | Activos | Suspendidos**. «Activos» son las cuentas habilitadas administrativamente, incluidas las que tienen un bloqueo temporal (se distinguen por la etiqueta). Esas cuentas se buscan con **Filtros → Seguridad → Bloqueo temporal** (`bloqueo=temporal`), que también encuentra cuentas suspendidas que además tienen un bloqueo temporal. Los enlaces antiguos `?estado=bloqueado` siguen funcionando y se convierten en ese filtro.

## Estado enlazado en ambas direcciones (ajuste del 28-10-2026)

Por pedido del Product Owner, la ficha y su cuenta de proveedor cambian juntas en las dos direcciones. Esto **reemplaza la regla anterior**, según la cual cambiar la ficha no tocaba la cuenta y un proveedor retirado podía iniciar sesión para consultar su historial. Se aplica a cuentas cuyo **único** rol es Proveedor; una cuenta con otros roles conserva esos accesos y solo pierde las operaciones de proveedor.

| Acción | Efecto |
|---|---|
| Ficha → Suspendido o Retirado (formulario, `PATCH estado` o app de administración) | La cuenta queda Suspendida con `usuarios.suspension_origen = ficha_proveedor`, motivo, responsable y fecha; se revocan sus sesiones y tokens. |
| Ficha → Activo | La cuenta se restaura si se suspendió por el enlace (origen de la cuenta `ficha_proveedor` u origen de la ficha `acceso_cuenta`). Una suspensión directa se conserva. |
| Cuenta → Suspender acceso | La ficha queda Suspendida (ya existía). |
| Cuenta → Restaurar acceso | La ficha se reactiva si se suspendió por el enlace. Si la ficha está **Retirada**, se rechaza con un mensaje: primero se reactiva la ficha. |
| Vincular o crear la cuenta de una ficha no activa | La cuenta adopta la suspensión. |

**Implementación:**
- `SincronizarCuentaConFichaUseCase`, invocado desde `CambiarEstadoProveedorUseCase` dentro de la misma transacción, y desde `VincularUsuarioProveedorUseCase`.
- La migración `2026_10_28_230433_add_suspension_origen_to_usuarios_table`, solo aditiva, se aplicó en la base local con respaldo.
- Las pantallas avisan del efecto antes de confirmar y después de guardar.

**Verificación:**
- `EstadoEnlazadoUsuarioProveedorTest` (8 pruebas), incluido el rollback si falla la cuenta, y PHPUnit completo **603/603**.
- Flujo HTTP real sobre una copia de la base local con datos ficticios: suspender, reactivar, retirar y restaurar desde cada módulo, con ambos listados y la API móvil coherentes en los 7 pasos.

## Android y modo sin conexión

- `ServidorWebRepositoryKtor.verificar`: un 401, o un 403 con código de cuenta (`cuenta_bloqueada`, `cuenta_suspendida`, `perfil_no_disponible`, `token_invalido`), borra el token, marca la cuenta local como «requiere verificación» y cierra la sesión local, **sin borrar la cola de pendientes**. Así no hay acceso offline después de una revocación conocida.
- Un 403 productivo (`proveedor_no_activo`) se informa como rechazo del servidor: no cierra la sesión ni se trata como falta de red.
- `IniciarSesionUseCase`: si el servidor rechaza por cuenta, no se recurre al login offline.
- **Sin Internet:** el celular no puede saber de una suspensión remota. Se conserva el comportamiento offline; al reconectar, la primera petición recibe el 401/403 y aplica lo anterior. No se fijó un tiempo máximo offline porque no hay decisión empresarial.

## Pruebas

| Prueba | Qué acredita |
|---|---|
| `web/tests/Feature/EstadosIntegradosTest.php` | Transiciones Activo→Suspendido→Retirado→Activo con datos, rollback, PATCH heredado, suspensión/restauración, último admin, PIN, combinaciones cuenta/ficha, otros roles, menús |
| `web/tests/Feature/SincronizacionMovilTest.php` | Entrega nueva rechazada para ficha no activa; tras «Suspender acceso» se rechazan entregas nuevas sin duplicar las ya enviadas, y tras «Restaurar acceso» se admiten |
| `web/tests/Feature/SuspensionConjuntaProveedorTest.php` | Suspensión conjunta, filtros de ambos listados, ficha ajena intacta, sin ficha, otros roles, Retirado, suspensión independiente e histórica, cambio manual de estado, rollback ante error y textos de los modales |
| `shared/.../RevocacionSesionHttpTest.kt` | 401/403 de cuenta invalidan la sesión local y conservan pendientes; 403 productivo no cierra sesión; sin red se conserva todo |
| `shared/.../EstadosAccesoPanelWebTest.kt` | Cliente Kotlin real contra Laravel aislado: login, suspensión desde el panel, rechazo de login y token, bloqueo del login offline, pendientes conservados, restauración con autenticación nueva |
| `web/tests/js/estado-proveedor.test.mjs` | Confirmaciones del selector de estado (no ejecutada en esta sesión: falta Node.js) |

## Evidencia de ejecución local (28-10-2026)

- PHPUnit completo: **572 pruebas, 572 aprobadas** (4121 aserciones), incluidos el ajuste de presentación de Usuarios y accesos y la suspensión conjunta.
- Kotlin `:shared:testAndroidHostTest`: **349 pruebas, 0 fallos, 9 omitidas** (las omitidas requieren servidor o credenciales externas). Sin cambios de código Kotlin en la suspensión conjunta.
- `EstadosAccesoPanelWebTest` ejecutada aparte contra Laravel con una base SQLite desechable y cuentas ficticias de `MovilDemoSeeder`: **aprobada**; se comprobó en la base el registro de auditoría de la suspensión.
- `EstadosAccesoPanelWebTest` repetida tras la suspensión conjunta, con una base desechable que incluye la nueva migración: **aprobada**. La base confirmó que la ficha de `prov_faon_01` se suspendió con origen `acceso_cuenta` y volvió a Activo al restaurar, con dos auditorías de la ficha (suspensión y restauración).
- Pint: sin observaciones.
- **No ejecutado:** `npm run build` y `node --test tests/js` (Node.js no disponible en el entorno); recorrido manual en navegador y Android.

## Incidente local del 28-10-2026: migración pendiente

- **Síntoma:** «Suspender acceso» y el alta de proveedores mostraban «No se pudo guardar la operación».
- **Causa verificada** (reproducida sobre una copia de la base local): la migración `add_suspension_origen_to_proveedores_table` no se había aplicado. Las consultas fallaban con `no such column: suspension_origen` y `table proveedores has no column named suspension_origen`.
- **Corrección:** se respaldó la base local (`web/database/database.sqlite.respaldo-antes-suspension-origen-20261028`, ignorada por git) y se aplicó la migración, que solo agrega una columna. El alta de fichas ya no escribe el origen; solo se borra al cambiar el estado de una ficha existente. El log `Error de persistencia` incluye ahora el mensaje del motor (`detalle`), sin la consulta SQL ni sus valores.
- **Verificación:** flujo HTTP real sobre una copia de la base migrada. Funcionaron el alta con cuenta móvil, la suspensión y restauración conjuntas, el alta sin cuenta, el rechazo de un DNI duplicado, el QR en PNG y la revocación en la API (token 401, renovación 401, login 403). Las pruebas `test_esquema_sin_migrar_…` y `test_el_alta_de_una_ficha_no_escribe_…` cubren la regresión. PHPUnit completo: 574/574.
- **Consulta DNI:** el código está completo y probado con respuestas simuladas (`ConsultaDniProveedorTest`). Está deshabilitada porque `DECOLECTA_TOKEN` está vacío en `web/.env`; no hay caché de configuración.

## Decisiones empresariales pendientes

- Qué hacer con una entrega registrada offline para un proveedor que fue suspendido antes de que se sincronizara: el servidor la rechaza (`proveedor_no_activo`) y el celular la conserva en estado de error, sin reenviarla sola. Falta decidir si, tras «Restaurar acceso», se reenvía manualmente o se revisa en el panel.

- Tiempo máximo de uso sin conexión antes de exigir revalidación.
