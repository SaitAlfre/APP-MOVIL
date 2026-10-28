# Registro unificado de proveedores (T-92)

Trazabilidad: RF-USU-04 / RF-USU-05 → [HU-24](../scrum/product-backlog.md#hu-24) → [CA-HU-24-03](../scrum/product-backlog.md#ca-hu-24-03) → [T-92](../scrum/tareas.md#t-92) → código y pruebas de esta nota → evidencia de ejecución local abajo.

**Estado:** implementado y probado en el entorno local el 28-10-2026. Sin commit ni despliegue. No cumple todavía la Definition of Done: faltan revisión de par, compilación frontend (`npm run build`) y recorrido manual en navegador de escritorio y móvil.

## Problema resuelto

Antes existían dos altas independientes: *Usuarios y accesos → Nuevo usuario → Proveedor* creaba solo la cuenta y *Proveedores → Nuevo proveedor* creaba solo la ficha. Ahora ambas entradas abren el mismo formulario (`admin.proveedores.create`) y usan una única lógica de negocio.

## Experiencia según el origen (ajuste del 28-10-2026)

- **Usuarios y accesos → Nuevo usuario → Proveedor:** sin casilla. Elegir el rol Proveedor ya implica crear su acceso: se muestran directamente usuario, PIN y confirmación, y el servidor las exige (`origen=usuarios`). Al guardar se crean la ficha y la cuenta, vinculadas.
- **Proveedores → Nuevo proveedor:** la ficha (código y QR) se registra siempre. El acceso personal es opcional mediante el botón secundario «Habilitar acceso móvil», que despliega las credenciales; «Registrar sin acceso móvil» lo vuelve a ocultar. No hay casilla junto a los campos.
- Mismo formulario (`admin.proveedores.form`), mismo request y mismo `RegistrarProveedorUseCase`; solo cambia el valor del campo oculto `crear_cuenta`.
- Aparecer en la app y poder iniciar sesión son cosas distintas: la sincronización (`/api/movil/datos`) entrega todas las fichas a administrador, acopiador y calidad con `usuarioId` nulo cuando no tienen cuenta; solo quien tiene cuenta puede iniciar sesión.

## Arquitectura

| Pieza | Responsabilidad |
|---|---|
| `Application/Proveedores/AnalizarRegistroProveedorUseCase` | Clasifica un DNI frente a fichas y cuentas (`SituacionRegistroProveedor`). Es la única fuente de reglas de duplicados. |
| `Application/Proveedores/RegistrarProveedorUseCase` | Registro único. Reutiliza `CrearProveedorUseCase`, `CrearUsuarioUseCase` y `VincularUsuarioProveedorUseCase` dentro de `DB::transaction`. También crea la cuenta de una ficha existente (`crearCuentaParaFicha`). |
| `VincularUsuarioProveedorUseCase` | Ahora exige que el DNI coincida y que la ficha no tenga otra cuenta. Aplica también a la sincronización móvil. |
| `ProveedorController::store/crearCuenta` | Entrada HTTP; `StoreProveedorRequest` valida usuario y PIN solo cuando se pide una cuenta nueva. |
| `UsuarioController::create/store` | El rol Proveedor redirige al formulario unificado; ya no se crean cuentas de proveedor sueltas. |
| `TransaccionAdministrativa` | Se extendió el cerrojo existente de `usuarios` a altas y vinculaciones de proveedores para serializar solicitudes concurrentes. Las restricciones únicas (`proveedores.dni`, `proveedores.usuario_id`, `usuarios.username`) son la última barrera. |
| `proveedores:conciliar-cuentas` (`routes/console.php`) | Revisión de registros históricos; vincula solo parejas fiables con `--vincular --actor=ID` y confirmación. |

## Situaciones del DNI

| Situación | Resultado |
|---|---|
| Nuevo | Ficha nueva; cuenta opcional con rol Proveedor, vinculada. |
| Ficha sin cuenta | No crea otra ficha. Con confirmación `vincular_ficha` crea la cuenta y la vincula; conserva código, QR, zona y entregas. |
| Cuenta Proveedor sin ficha | Con confirmación `asociar_cuenta` crea la ficha y la asocia; no pide credenciales ni crea otra cuenta. |
| Ficha y cuenta sin vincular | Con confirmación `vincular_existentes` las vincula sin modificar datos. |
| Ya vinculado | Bloquea y enlaza a la ficha existente. |
| Cuenta vinculada a otra ficha | Bloquea una segunda vinculación. |
| Conflicto de DNI (ficha vinculada a cuenta con otro DNI, o varias cuentas con el mismo DNI) | Bloquea; requiere corrección por un administrador. |
| Cuenta de otro rol | Bloquea; no cambia rol ni permisos. |

El servidor revalida la situación al guardar: una confirmación que ya no corresponde se rechaza.

## Permisos y seguridad

- Crear cuentas de proveedor: solo administrador oficial con `usuarios.gestionar` (misma regla que Usuarios y accesos). Un perfil con `proveedores.crear` registra solo la ficha.
- Vincular registros existentes: `proveedores.editar`, como la vinculación manual previa.
- PIN de exactamente 4 dígitos, almacenado con el hash del modelo; no se reenvía a la vista ni se audita. La auditoría registra el alta de la cuenta (`roles=proveedor`) y la vinculación (`usuario_id` de la ficha).
- El estado productivo de la ficha no modifica el estado de la cuenta.

## Política de identidad al editar

- El DNI de una ficha vinculada y el de su cuenta no se pueden cambiar mientras estén vinculadas; primero se desvincula o se corrigen ambos.
- A una cuenta vinculada no se le puede quitar el rol Proveedor sin desvincularla.
- Los nombres son independientes (ficha productiva y nombre de la cuenta) y no se sobrescriben; si difieren, ambas pantallas muestran el aviso.
- La edición de la ficha muestra la cuenta vinculada y su enlace, o permite crearla, o vincular una cuenta Proveedor con el mismo DNI. La edición de la cuenta muestra la ficha asociada o el enlace para registrarla.

## Compatibilidad móvil

La sincronización de la app (`AplicarCambioMovilUseCase`) reutiliza la misma vinculación: un enlace ficha–cuenta con DNI distinto ahora se rechaza con 422 y mensaje explícito. La cuenta creada desde el formulario inicia sesión en `/api/movil/sesion` y consulta sus liquidaciones (probado). No se modificó código Kotlin.

## Registros existentes

Revisión de solo lectura ejecutada sobre la base SQLite local el 28-10-2026 (`php artisan proveedores:conciliar-cuentas`): 0 fichas vinculadas, 6 fichas sin cuenta, 4 cuentas de proveedor sin ficha, 0 coincidencias fiables. No se modificó ningún registro.

## Evidencia de ejecución local (28-10-2026)

- `php artisan test --compact tests/Feature/RegistroUnificadoProveedoresTest.php`: 28 pruebas aprobadas tras el ajuste por origen (antes 24), incluidas las de ambos flujos, listados y sincronización de fichas sin cuenta.
- Suite PHPUnit completa tras el ajuste: 543 pruebas, 540 aprobadas. `AdminAcopioCoherenteTest::test_cierre_admin_se_exporta_y_envio_antiguo_no_reabre` es intermitente (falló 2 de 5 repeticiones con el mismo código; recorre jornadas, no proveedores). Además, 2 fallos **preexistentes** (ya fallaban antes del cambio, en la línea base de 515 pruebas): `AcuerdosFinalesCoder1Test::test_ficha_admite_limites_acordados_y_rechaza_excesos_en_web_y_api` (DNI no numérico) y `RolesCompatiblesCoder1Test::test_formularios_solo_ofrecen_cuatro_roles_sin_etiquetas_obsoletas`.
- Pruebas actualizadas por el cambio de regla (DNI coincidente para vincular, alta de Proveedor solo por el formulario unificado): `ProveedorVinculacionTest`, `RolesConfigurablesTest`, `AltaUsuariosDinamicaTest`, `CambiosMovilTest`, `CamposProveedorCoder1Test`.
- **No ejecutado:** `npm run build` y pruebas JS (`node --test`), porque Node no está instalado en el entorno del agente; pruebas Kotlin (`:shared:testAndroidHostTest` falla al configurar: no hay Android SDK); recorrido manual en navegador y dispositivo.
