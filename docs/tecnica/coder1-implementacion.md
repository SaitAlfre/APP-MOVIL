# Coder 1: implementación, pruebas y comprobación manual

[Índice](../README.md) · [Contrato técnico](arquitectura-datos-y-sincronizacion.md#contratos-coder1-2026-10-25) · [RF originales](../requisitos/rf-fuente-y-cobertura.md)

**Implementación y pruebas: 25 y 26-10-2026, America/Lima; cierre de acuerdos del 26-10.** Trabajo local sobre `056bcb9cd0de60f081b533a496cde013d8c76127`. Alcance exclusivo: usuarios, roles, proveedores, zonas, vehículos, asignaciones y sus secciones de autenticación/sincronización. No acredita aprobación del beneficiario, despliegue, revisión de un compañero ni Definition of Done de historias completas.

## A. Resumen de implementación

- Decisión oficial del equipo aplicada: **Acopiador, Calidad, Administrador y Proveedor**. Asistente, Producción, Despacho, Recepción, Liquidaciones y Consulta dejan de ser asignables por formulario y API. La propuesta anterior de conservar perfiles adicionales queda sustituida; los requisitos originales y documentos históricos mantienen su fecha.
- Decisiones del equipo del 26-10: altas y restablecimientos con **PIN de cuatro dígitos**; acceso heredado con PIN de 5–8 hasta restablecimiento manual, sin cambiar hashes existentes. **Plazo máximo offline pendiente por decisión expresa: no se aplica un límite nuevo.**
- Acuerdos adicionales del equipo aplicados: **un rol en altas nuevas**, combinaciones heredadas sin conversión; **DNI personal de ocho dígitos y usuario de 3–30** (`a–z`, `0–9`, punto, guion bajo). Calidad nueva o reasignada requiere zona activa; una asignación heredada sin cambios se conserva al editar otro campo. Calidad gestiona calidad/sanciones y consulta recepción, entregas, reportes y comunicados; se retira lectura de producción/liquidaciones, incluidos KPI y datos privados API. Admin conserva esas funciones.
- Se alinean máximos de catálogos y capacidad finita mínima 0,01 L ya exigidos por Laravel; se eliminan truncamientos de teléfono/documento de proveedor y placa. Un DNI legado sin cambios no impide corregir otro dato. La ficha móvil ya no repite la unidad «L». El acuerdo final extiende los mismos límites al alta conjunta ficha/cuenta: código 50, nombre 150, documento 20, teléfono 30, dirección 255, 1–1.000 tachos y capacidad finita ≥0,01 L sin máximo nuevo. El documento productivo no sustituye al DNI personal. No se truncan campos para ocultar errores.
- Las sesiones persistidas se revalidan contra cuenta, bloqueo, rol y revocación conocida. El inicio online no acepta una denegación del servidor como si fuera falta de internet. El cierre elimina primero la sesión local y luego intenta revocar la remota.
- Zona de trabajo de la cuenta y propietario/responsable del proveedor incorporados al contrato. Se conservan cambios pendientes y se corrige la desvinculación explícita de una ficha. El renombrado de código por ID ya existía: se añadió regresión, sin atribuirlo a una corrección nueva.
- Laravel protege la desactivación de zonas con referencias activas y vehículos con jornadas abiertas. Las consultas a jornadas no modifican la lógica de Coder 2.
- Ediciones C1 con revisión base detectan conflictos y conservan el pendiente. La prueba ampliada encontró además que crear y editar enseguida podía quedar en conflicto por faltar la revisión inicial. El ACK ahora devuelve la revisión dentro de la transacción; Android guarda esa base y conserva una edición posterior mientras confirma la anterior. No se desactiva el 409 ni se fuerza una versión remota.
- Android cifra PIN pendientes y tokens mediante Android Keystore/AES-GCM. El hash sigue sirviendo para autenticación local. No se cifra toda la base ni se acredita eliminación forense de texto que hubiera existido antes.
- Migraciones preparadas y ensayadas con archivo de roles originales, sin borrar cuentas ni convertirlas a Administrador. No ejecutadas sobre bases reales.

### Matriz funcional vigente

| Función | Necesidad y decisión | Canal / límite |
|---|---|---|
| Cuenta y ficha productiva separadas | Mantener: autenticar no equivale a identificar una unidad productora. Existen fichas sin acceso digital. | Web administra ambas; móvil conserva vínculo autorizado. No fusionar por nombres. |
| Administrador | Mantener administración y permisos existentes. | Android y web; nunca conversión automática. |
| Acopiador | Mantener perfil operativo y mapas asignados. | Android y mapas web; sin administración de catálogos. |
| Calidad | Mantener perfil y permisos existentes de calidad/lectura. | Android y web; sin permisos nuevos de recepción, producción o pagos. |
| Proveedor | Mantener cuenta vinculada a ficha propia. | Móvil/API propios; administrable desde web, sin panel de personal. Portal web propio corresponde a Coder 4. |
| Roles retirados | Archivar asociaciones y retirar autorización según decisión oficial. | Cuentas/relaciones conservadas. Sin perfil oficial requieren asignación explícita. |
| Recepción, producción, inventario, ventas y liquidaciones | Conservar funcionalidades y acceso existente de Administrador. | Delegación a otro perfil: PENDIENTE DE VALIDACIÓN con Coder 3/4, sin equivalencias inventadas. |
| Varios roles | Un rol por cuenta nueva, aprobado por el equipo. | Combinaciones heredadas conservadas hasta revisión manual; cambiar exige escoger un rol, sin conversión automática. |
| Bloqueo y desactivación | Mantener estados distintos: seguridad frente a suspensión operativa. | Offline solo se aplica revocación remota ya conocida. Plazo máximo sin renovar: pendiente. |
| Edición móvil y web | Mantener ambos canales por necesidad offline y administración central. | Proteger pendientes y detectar conflictos. |
| Zonas/vehículos | Mantener historial mediante estados y guardas referenciales. | No convertir zona en ruta ni modificar jornadas. |
| PIN e identidad | Nuevos/restablecidos: cuatro dígitos; login conserva los PIN anteriores de 5–8 hasta cambio manual. | DNI personal ocho y usuario 3–30; Calidad nueva/reasignada requiere zona activa. Legados sin cambios conservados. |

Identificadores API: `acopiador`, `calidad`, `admin`, `proveedor`. Kotlin usa `ACOPIADOR`, `CALIDAD`, `ADMIN`, `PROVEEDOR` mediante conversión explícita; sin alias de roles retirados.

## B. Estado de las 12 tareas del diagnóstico

Los estados corresponden a los criterios del diagnóstico, con alcance local explícito. **10 Completadas y 2 Pendientes**; ninguna historia se certifica Done ni se acredita aprobación externa. Completada significa el alcance técnico local de esta tabla, no aprobación del beneficiario, migración productiva o validación de campo. El corte anterior de 8/4 queda conservado en sus recibos históricos; esta tabla incorpora los acuerdos posteriores.

### Objetivo, problema, solución y archivos por tarea

Las rutas completas y hashes de todos los archivos están en el inventario de C. En la columna de archivos se indican las piezas principales, sin atribuir cambios nuevos a comportamientos que ya existían.

| Tarea / objetivo | Problema observado | Solución implementada / archivos principales |
|---|---|---|
| T01 Matriz de roles/plataformas | Roles web retirados no equivalían a perfiles móviles; permisos transversales sin ratificar. | Solo cuatro oficiales; sin conversiones. `Rol.php`, `permisos.php`, formularios web, validación API, seeder C1. Matriz ratificada por el equipo en esta conversación; filtros de permisos en dashboard/exportación C1, sin cambiar cálculos C3/C4. |
| T02 Revalidar sesión local | Una sesión persistida podía sobrevivir a cambios de cuenta/rol. | `SqlDelightSesionRepository.kt`, `IniciarSesionUseCase.kt`, `LoginOfflineUseCase.kt`, `SeleccionarRolUseCase.kt`, `CerrarSesionUseCase.kt`, consultas/DI/Ktor. Denegación conocida sin fallback offline. |
| T03 Unificar PIN | Alta web 4–8 y entrada Android cuatro; cuentas heredadas incompatibles. | Política aprobada cuatro nuevos, transición 5–8. `LoginScreen.kt`, `ValidaDatosUsuario.php`, `UsuarioController.php`, `MovilController.php`, formularios/mensajes PHP; login verifica hash anterior. |
| T04 Bloqueo/vigencia offline | No existía plazo offline aprobado; privilegio desconocidamente revocado no puede detectarse sin red. | Bloqueo/revocación conocida y renovación segura en auth/Ktor/repositorio de sesión; cierre local primero. No se inventa TTL. |
| T05 Desactivar zonas sin romper referencias | Web permitía desactivar una zona todavía en uso. | `CambiarEstadoZonaUseCase.php`, repositorio/contrato de zona, `ZonaController.php`, sección C1 API: guardas y transacción. Solo consulta jornadas C2. |
| T06 Desactivar vehículos libres | Faltaba la misma guarda en web/API para jornada abierta. | `CambiarEstadoVehiculoUseCase.php`, repositorio/contrato, controlador y sección C1 API. Sin cambiar reglas de jornadas. |
| T07 Conservar asignaciones | Zona de cuenta no viajaba; un vínculo null podía permanecer. | `DatosServidor.kt`, `SqlDelightDatosServidorRepository.kt`, DTO/casos/repositorio usuario PHP, exportación y aplicación C1. Presencia explícita distingue omisión/null; pendientes protegidos. |
| T08 Homologar campos | Responsable no viajaba; entradas se truncaban; faltaban límites y edición de DNI legado. | Modelos C1 `Proveedor/Usuario/Zona/Vehiculo.kt`, VMs de sus formularios, `ActualizarProveedorUseCase.kt`, `GestionUsuariosUseCases.kt`, validación de identidad PHP, formulario de alta conjunta, `Proveedor.php`, DTO/repo/formulario proveedor; etiqueta C1 en `AdminSupervisionScreen.kt`. |
| T09 Preparar migración segura | Retirar asociaciones podía perder su procedencia o conceder perfiles incorrectos. | `SchemaCuentasSeguras.kt`, migraciones SQLDelight 18/19/20 y Laravel 25-10 (zona/archivo/dueno). Archiva originales, conserva IDs/relaciones/hash; reversión protegida. |
| T10 Controlar edición concurrente | Clientes podían sobrescribir catálogos sin detectar base antigua. | `RevisionCatalogo.php`, `versionCatalogo.sq`, cola/repositorios/Ktor/controlador C1. ACK atómico `{id, revision}`; conserva edición posterior y mantiene el 409 frente a cambios de otro cliente. Métodos nuevos con compatibilidad predeterminada; otros módulos conservan su camino de envío. |
| T11 Proteger PIN/tokens Android | Secretos recuperables sin protección específica. | `ProtectorSecretos.kt`, `ProtectorSecretosKeystore.kt`, `ProteccionCredencialesLocales.kt`, `EcolectaApp.kt`, cola/Ktor/DI: AES-GCM contextual y conversión transaccional. |
| T12 Acreditar recorrido Android/web | Build y casos de uso no acreditaban pulsaciones gráficas. | `VerificacionCoder1Instrumentada.kt`, nuevo `InterfazCoder1.kt`, selección QA en Gradle. Capturas/recibos reales, sin manipular Android Studio ni producción. |

### Resultados por plataforma y estado

Los nombres de suites siguientes se localizan en los recibos PHP/JVM de D; los IDs NAT son antecedentes del 25 y UI/INT del 26.

| Tarea | Pruebas / resultado | Android | Laravel | Sincronización | Estado |
|---|---|---|---|---|---|
| T01 | `RolesCompatiblesCoder1Test`, `CompatibilidadFormulariosCoder1Test`; UI cuatro perfiles aprobada. | Cuatro perfiles y menú propio observados. | Cuatro asignables; Calidad/Acopiador reciben 403 en Usuarios; Proveedor no entra al panel. | Identificadores oficiales en ambos. Lectura Calidad restringida según ratificación; datos de Proveedor solo para su rol. Homologación del Charter externa sigue pendiente. | **Completada** |
| T02 | `SesionPersistidaTest`, `AccesoRemotoCoder1Test`, `CerrarSesionUseCaseTest`, NAT-07 y UI cuatro perfiles aprobados. | Persiste/reabre y revoca estado conocido; fallos de almacenamiento no anuncian éxito. | Denegación de sesión inválida/cuenta bloqueada probada. | Recepción de cambio invalida confianza; desconexión genuina conserva política actual. | **Completada** |
| T03 | Compatibilidad PHP, caso PIN legado JVM y UI de cuatro dígitos aprobados. | Entrada 4–8 para login; altas/restablecimientos cuatro. PIN legado probado contra hash local. | Rechaza altas/cambios de 3, 5, 8, 9 y letras; el dominio también rechaza saltos finales y dígitos no ASCII; login heredado hasta cambio manual probado en SQLite. | Hash anterior no se altera al acceder. No se hizo cambio de credencial real. | **Completada** |
| T04 | Auth/refresh/offline, API bloqueada y prueba gráfica de bloqueo; resultados en D. | Denegación remota no abre sesión; cierre local seguro. | Bloqueo gráfico real; desbloqueo por caso de uso para restaurar fixture y controles server probados. | Revocación desconocida sin red sigue siendo límite. Equipo aplazó expresamente el TTL. | **Pendiente** |
| T05 | `ProteccionCatalogosCoder1Test` y rechazo web con referencias, aprobados. | Casos de uso guardan referencias; alta/validación gráfica de zona aprobada. | Rechaza proveedores/cuentas/jornadas activas; admite libre. | API rechaza sin cambios parciales. Recorrido gráfico de todos los estados no agotado. | **Completada** |
| T06 | `ProteccionCatalogosCoder1Test` y regresiones, aprobados. | Alta/validación gráfica de vehículo aprobada. | Jornada abierta rechazada; libre permitido en pruebas aisladas. | Guarda API bajo transacción. Prueba de campo no ejecutada. | **Completada** |
| T07 | `AsignacionZonaCoder1Test`, `CatalogosDescargaCoder1Test`, NAT-03, INT web→Android y tres fases en dos AVD aprobados. | Zona/vínculo conservados; dos AVD independientes del 26 más el antecedente de dos BD en uno. | Zona Calidad visible y conservada al editar nombre; ficha conserva cuenta. | Reasignación, null explícito y restauración confirmados en **dos AVD independientes**, misma ficha13 sin duplicado. Hardware físico no probado. | **Completada** |
| T08 | Suites de compatibilidad, campos, descarga y UI aprobadas. | Sin truncar contacto/placa; ficha editada y enviada; DNI legado sin cambio conservado. | Mismos topes de catálogo y mínimo de capacidad; teléfono web visible en Android. | Responsable, código por ID y vínculo conservados. Identidad personal, zona Calidad, multirrol y ficha conjunta homologados conforme a los acuerdos explícitos; pruebas de límites y legados. | **Completada** |
| T09 | `RolesCompatiblesCoder1Test`, `MigracionRolesCoder1Test` aprobados; `foreign_key_check` vacío e `integrity_check=ok`. | Esquemas ficticios 8/10; archivo previo a borrado histórico. | Conserva cuentas/relaciones; reversión y detención si hay cambios posteriores probadas. | Sin equivalencias ni migración real. Objetivo: preparación ensayada. | **Completada** |
| T10 | `ConcurrenciaCatalogosCoder1Test`, descarga pendiente y NAT-05 aprobados. | Conserva pendiente/error al recibir conflicto. | 409 para base obsoleta; mismo estado repetido idempotente. | Solo clientes con control de versión; no promete protección a versiones antiguas. | **Completada** |
| T11 | `CredencialesProtegidasCoder1Test` y NAT-01 aprobados. | Keystore real, contexto/nonce, conversión y fallo cerrado. | Autenticación API y revocación conservadas; el cifrado local Android no cambia el token que verifica Laravel. | No publica secretos; no acredita iOS ni borrado forense de backups. | **Completada** |
| T12 | Grupos de 10 y 7 checks de formularios/perfiles/estados/ida-vuelta; un INT repetido; bloqueo antecedente y NAT en D. | AVD real, pulsaciones por accesibilidad y persistencia comprobadas. | Navegación C1, roles, edición, zona, permisos y bloqueo con datos ficticios; desbloqueo gráfico pendiente. | Alta/edición/PIN y estados de cuenta, zona y vehículo libres ya probados gráficamente; faltan desbloqueo gráfico y credencial manual web, hardware/campo y recorridos restantes con referencias. | **Pendiente** |

### Matriz de campos y presencia

| Dato | Tratamiento aplicado | Pendiente |
|---|---|---|
| Usuario–zona | FK nullable; `zonaId` + `asignacionZonaIncluida` distinguen campo ausente de desvinculación. Zona válida solo para Acopiador/Calidad. | Nuevas Calidad y cambios de zona/rol requieren zona activa. Asignación heredada sin cambios conservada. Zona no es ruta. |
| Ficha–cuenta | Null remoto explícito desvincula salvo cambio local pendiente; un ID sin resolver no borra por suposición. | Recorrido manual con dos dispositivos físicos. |
| Propietario/responsable `dueno` | Opcional hasta 150 caracteres; presencia explícita permite vaciar; omisión de cliente antiguo conserva valor. | Nomenclatura empresarial. No equivale al titular de cuenta ni nombre de finca. |
| Código proveedor | Renombrado por ID remoto preservado, sin duplicar ficha. | No es username ni se deduplica por nombres. |
| PIN | Cuatro al asignar/restablecer; 5–8 solo acceso heredado hasta cambio manual. Hash local y secreto pendiente protegido Android. | Plazo offline expresamente diferido; no se aplica un límite nuevo. |
| Documento / username | Acuerdo aplicado: DNI personal 8 ASCII, usuario 3–30 minúsculas/números/punto/guion bajo. | Identidad heredada sin cambios preservada; no se permite asignar una identidad nueva inválida. |
| Catálogos | Proveedor: 50/150/20/30/255; responsable 150; zona/vehículo nombre 100, placa 20. Capacidad finita ≥0,01 L. | No cambia cálculos de acopio. Alta conjunta y catálogo usan los límites aprobados, también 1–1.000 tachos; ficha documental ≤20 no equivale al DNI personal. |

## C. Archivos y conservación de datos

El [inventario de cambios del corte anterior al 26-10](../pruebas/evidencias/coder1-2026-10-26/archivos-cambiados.json) enumera cada ruta, estado Git, SHA-256 actual, motivo y alcance. Incluye archivos nuevos que `git diff --stat` no enumera. Para las fuentes de este cierre y el delta de esta continuación, consultar el [inventario final](../pruebas/evidencias/coder1-2026-10-26-cierre/archivos-cambiados.json); no se reescriben hashes de evidencias previas. No se añaden APK, bases de datos, `.env`, dependencias descargadas ni credenciales.

En archivos compartidos los cambios se limitan a cuentas/catálogos/auth: secciones C1 de Ktor, DI, exportación/aplicación de cambios, asignaciones y sesión. Los tests existentes de otros módulos ajustan preparación de credenciales/esquema o expectativas de roles retirados por decisión oficial; sus reglas productivas no se modifican. La regresión completa no acredita todos sus flujos.

### Migración y reversión preparadas

1. Antes de un entorno real: inventariar cuentas afectadas/vínculos, guardar respaldo privado y ensayar sobre copia. No se inspeccionó la base productiva; no se inventa cantidad de cuentas afectadas.
2. Laravel archiva roles originales en `archivo_roles_usuarios`, conserva cuenta/hash/zona/ficha, retiene solo roles oficiales existentes y revoca sesiones. Sin perfil oficial se requiere asignación explícita; nunca Admin automático.
3. La reversión PHP se detiene si existen asignaciones posteriores que pudiera sobrescribir. Conserva el archivo. Los tests ensayan reversión y detención segura.
4. Móvil usa `SchemaCuentasSeguras` antes del borrado histórico de `10.sqm`, sin reescribirlo. Migraciones 18/19/20 agregan verificación, archivo y revisiones. Los tests restauran asociaciones exactas solo en BD ficticia: no es una orden productiva ni autoriza roles retirados en el binario actual.
5. Asociaciones borradas por una versión anterior no se reconstruyen sin respaldo. Un rollback real necesita copia compatible y conciliación de cambios posteriores.

## D. Pruebas ejecutadas y resultados reales

Copia aislada: `/tmp/ecolactea-coder1-c8fmp23t/project`. PHP 8.3.6, Laravel 13.32.0, Java 21, SQLite ficticio; AVD Android 16 `Galaxy_S25_Android_16`, serial `emulator-5554`. PHPUnit usó SQLite `:memory:`; navegación/integración otra BD ficticia en `/tmp`. Puertos 8765/8871 locales. No se escribió en Heroku ni Firebase real.

| Comprobación final | Resultado | Evidencia |
|---|---|---|
| PHP completo | **323 aprobadas, 0 fallidas, 0 omitidas; 1907 aserciones** | [Casos](../pruebas/evidencias/coder1-2026-10-25/phpunit.json), [salida](../pruebas/evidencias/coder1-2026-10-25/phpunit.txt) |
| shared/JVM completo | **306 aprobadas, 0 fallidas, 0 omitidas** | [Casos](../pruebas/evidencias/coder1-2026-10-25/kotlin.json), [salida](../pruebas/evidencias/coder1-2026-10-25/kotlin.txt). Incluye 6 casos HTTP locales; no todos los 306 son tests de red. |
| APK preview + instrumentación | **BUILD SUCCESSFUL** | [Recibo](../pruebas/evidencias/coder1-2026-10-25/build.json), [salida](../pruebas/evidencias/coder1-2026-10-25/build.txt). Build no equivale a suite de tests. |
| Android nativo | **7 comprobaciones aprobadas, 0 fallidas** | [Recibo y comandos](../pruebas/evidencias/coder1-2026-10-25/android-nativo.json) |
| Herramientas documentales | **27 tests aprobados** | [Salida](../pruebas/evidencias/coder1-2026-10-25/herramientas.txt); incluye rechazo de hash histórico alterado y ruta externa. |
| Pint, diff, enlaces y revisión de archivos | Resultados en cierre | [Comprobaciones](../pruebas/evidencias/coder1-2026-10-25/cierre.json) |
| Logcat acotado al PID preview | Sin líneas en AndroidRuntime:E/EcolectaInicio:E después del arranque | [Consulta](../pruebas/evidencias/coder1-2026-10-25/logcat.json). No prueba ausencia de todos los errores durante toda la sesión. |

Los resultados de la tabla anterior son **antecedentes del 25-10**, conservados sin sustituir sus recibos. Se ejecutaron estas regresiones después de las correcciones del 26:

| Ejecución del 26-10 | Resultado real | Evidencia |
|---|---|---|
| PHP completo | **329 aprobadas, 0 fallidas, 0 omitidas; 1.989 aserciones** | [Casos](../pruebas/evidencias/coder1-2026-10-26/phpunit.json), [salida](../pruebas/evidencias/coder1-2026-10-26/phpunit.txt) |
| shared/JVM completo | **313 aprobadas, 0 fallidas, 0 omitidas**, incluidas seis integraciones HTTP locales | [Casos](../pruebas/evidencias/coder1-2026-10-26/kotlin.json), [salida](../pruebas/evidencias/coder1-2026-10-26/kotlin.txt) |
| APK y test APK | BUILD SUCCESSFUL; no son tests | [Recibo](../pruebas/evidencias/coder1-2026-10-26/build.json), [salida](../pruebas/evidencias/coder1-2026-10-26/build.txt) |
| GUI Android | **10 aprobadas, 0 fallidas**; dos checks de integración y ocho de UI, sin sumar repeticiones | [Recibo](../pruebas/evidencias/coder1-2026-10-26/android-interfaz.json) |
| GUI bloqueo Android | **2 aprobadas, 0 fallidas**: rechazo y restauración Admin | [Recibo](../pruebas/evidencias/coder1-2026-10-26/android-bloqueo.json) |
| Asignaciones en dos AVD | Tres fases aprobadas en ambos: asignación, desvinculación explícita y restauración; ficha única | [Comparación y recibos](../pruebas/evidencias/coder1-2026-10-26/android-dos-dispositivos.json), [salida](../pruebas/evidencias/coder1-2026-10-26/android-dos-dispositivos.txt) |
| Navegador local | Edición, alta zona, cuatro perfiles, 403, bloqueo/desbloqueo y proveedor Android visibles | [Registro y hashes de capturas](../pruebas/evidencias/coder1-2026-10-26/navegador.json) |
| Herramientas, enlaces, Pint y diff | Ver recibo de cierre; advertencias históricas no son pruebas de la app | [Cierre](../pruebas/evidencias/coder1-2026-10-26/cierre.json) |

La prueba GUI usa accesibilidad de **la propia app preview** para pulsar controles y escribir datos; no se operaron ventanas de Android Studio. Se instalaron APK con `-r`, sin limpiar el AVD. Las dos BD del NAT-03 son el antecedente de un solo AVD. El 26 se añadió un segundo AVD temporal limpio con userdata/SQLite propias: ambos recibieron y conservaron zona, vínculo y responsable en tres fases. Se restauró el fixture y se detuvo solo el AVD temporal, conservando el original. No equivale a dos teléfonos físicos. Se mantuvo la sincronización automática normal y el envío explícito del test filtró únicamente IDs nuevos C1. No se resolvieron artificialmente pendientes de C2/C4.

Los fallos intermedios se conservan en [intentos](../pruebas/evidencias/coder1-2026-10-26/intentos.json): fixture de medianoche, filtro Gradle incorrecto, nulabilidad del test y sincronización/caché del controlador de UI. No se cuentan como éxitos. Los 323/306 anteriores tampoco se suman a 329/313 como si fueran casos distintos. La suite compartida corresponde a las fuentes Kotlin actuales y la PHP incluye el endurecimiento final del patrón de PIN. Después se añadieron modos QA de bloqueo/dos AVD y documentación; la APK de la aplicación mantuvo el mismo SHA en todas esas comprobaciones. El SHA del APK está en los recibos Android y los hashes de fuentes en el inventario.

El verificador documental conserva pruebas históricas contra el hash Git exacto y advierte su alcance; no atribuye esas pruebas al código nuevo. No se alteran resultados anteriores para hacerlos pasar.

### Cierre de acuerdos y regresión final del 26-10

Los cortes anteriores se conservan como antecedentes. La copia anterior `/tmp/ecolactea-coder1-c8fmp23t` ya no estaba disponible; se creó **otra copia desechable** `/tmp/ecolactea-c1-cierre-ei2ci5pu/project`, PHP 8.3.6/Laravel 13.32.0, Java 21, backend SQLite ficticio y AVD Android 16 `Coder1_Cierre_26`/`emulator-5558`. No se tocó una base real ni se aplicaron migraciones al entorno productivo.

| Comprobación final después de los acuerdos | Resultado | Evidencia |
|---|---|---|
| Laravel completo | **339 aprobadas, 0 fallidas, 0 omitidas; 2.087 aserciones** | [Casos](../pruebas/evidencias/coder1-2026-10-26-cierre/phpunit.json), [salida](../pruebas/evidencias/coder1-2026-10-26-cierre/phpunit.txt) |
| shared/JVM completo | **321 aprobadas, 0 fallidas, 0 omitidas**, seis casos HTTP locales incluidos | [Casos](../pruebas/evidencias/coder1-2026-10-26-cierre/kotlin.json), [salida](../pruebas/evidencias/coder1-2026-10-26-cierre/kotlin.txt) |
| APK + instrumentación | BUILD SUCCESSFUL; APK funcional idéntica antes y después de ajustar la espera de capturas | [Recibo](../pruebas/evidencias/coder1-2026-10-26-cierre/build.json), [salida](../pruebas/evidencias/coder1-2026-10-26-cierre/build.txt) |
| Android formularios y perfiles | **10 checks aprobados** | [Recibo](../pruebas/evidencias/coder1-2026-10-26-cierre/android-interfaz.json) |
| Android alta/edición/PIN/estados | **7 checks aprobados** | [Recibo](../pruebas/evidencias/coder1-2026-10-26-cierre/android-cierre.json) |
| Nativo: Keystore, cuatro roles, límites de ficha, offline y conflicto | **6 checks aprobados** + **1 de reapertura** | [Nativo](../pruebas/evidencias/coder1-2026-10-26-cierre/android-nativo.json), [reapertura](../pruebas/evidencias/coder1-2026-10-26-cierre/android-reapertura.json) |
| Vite | Build web correcto, sin cambiar dependencias; JS/CSS no se modificaron después | [Recibo](../pruebas/evidencias/coder1-2026-10-26-cierre/web-build.json), [salida](../pruebas/evidencias/coder1-2026-10-26-cierre/web-build.txt) |
| Navegador | Cuatro roles/radio, Calidad sin KPI privados ni módulos retirados y rechazo directo 403; bloqueo ficticio | [Registro y límites](../pruebas/evidencias/coder1-2026-10-26-cierre/navegador.json) |
| Bloqueo Android antes del ACK final | **2 checks aprobados**; se conserva su SHA propio | [Antecedente](../pruebas/evidencias/coder1-2026-10-26-cierre/android-bloqueo-anterior.json) |
| Herramientas documentales | **27 tests aprobados** | [Salida](../pruebas/evidencias/coder1-2026-10-26-cierre/herramientas.txt) |
| Enlaces, Pint, Git y fuentes | Resultado en recibo final | [Comprobaciones](../pruebas/evidencias/coder1-2026-10-26-cierre/cierre.json) |

Los grupos de 10 y 7 comparten `INT-C1-web-a-android`: **16 IDs distintos**, no 17 pruebas únicas. Los NAT usan casos de uso nativos y dos SQLite dentro del AVD; los UI sí pulsan controles mediante accesibilidad. Ninguno acreditaoperación manual de integrantes ni hardware físico. Las seis integraciones JVM HTTP usan exclusivamente localhost/SQLite ficticio, con sus precondiciones; no son 321 pruebas de red. APK/runner y capturas se identifican por SHA en los recibos.

La nueva GUI creó y editó una cuenta, restableció su PIN a cuatro, desactivó/reactivó cuenta/zona/vehículo libre y confirmó el estado remoto: PIN anterior rechazado, nuevo aceptado, cuenta única al reintentar. El NAT-03 envió y descargó una ficha con código 50, nombre 150, documento 20, teléfono 30, dirección 255, 1.000 tachos y capacidad 200,01 L, conservando el DNI personal de ocho. El NAT-05 mantiene el conflicto real entre clientes, sin sobrescribir ni borrar el pendiente. Las capturas de listas pueden volver al principio al salir de edición; el estado exacto se comprueba en SQLite y en la respuesta HTTP, no se infiere de una tarjeta distinta visible. El indicador de revisión conserva un conflicto ficticio previo: no acredita una cola global vacía.

Se conserva el [fallo real anterior al ACK](../pruebas/evidencias/coder1-2026-10-26-cierre/intento-ack-sin-revision.json) y el [registro de intentos](../pruebas/evidencias/coder1-2026-10-26-cierre/intentos.json). La solución guarda la revisión confirmada incluso si existe una edición posterior; el borrado sigue condicionado a `marcado_en`. Tests PHP/JVM verifican ACK/409 y conservación de la edición posterior. No se borraron los errores de fixtures anteriores para aparentar una cola limpia.

El navegador no pudo resolver el diálogo JavaScript de desbloqueo: ese paso sigue pendiente. El fixture se restauró con `DesbloquearUsuarioUseCase`, comprobando la ruta exacta de SQLite temporal ([recibo](../pruebas/evidencias/coder1-2026-10-26-cierre/restauracion-fixture.txt)); esto **no** se acredita como GUI. Alta/restablecimiento manual web con una nueva credencial debe completarlo un integrante. No hay aprobaciones, PR ni despliegues inventados.

Las salidas completas registran advertencias de compilación/deprecación y la advertencia opcional de fuentes del build web; no se presentan como builds sin avisos. Vite se compiló en la copia local; las modificaciones posteriores de Blade no requieren cambiar sus dependencias. Comandos efectivos de PHPUnit/Gradle/ADB en los recibos: sustituir `/tmp` y preparar nuevamente dependencias/fixtures si la carpeta se limpia. Las variables de integración no deben apuntar nunca a una base real.

### Comandos de los cortes anteriores

Los recibos contienen comandos exactos y casos individuales. En la copia aislada, con `PHPRC` del runtime temporal, `JAVA_HOME` y SDK configurados:

```bash
# Dentro de web/, únicamente con BD desechable.
APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= SESSION_DRIVER=array CACHE_STORE=array MAIL_MAILER=array /tmp/ecolactea-coder1-c8fmp23t/runtime/bin/php vendor/bin/phpunit --colors=never

# Dentro de la copia, con Laravel local iniciado y variables de integración.
bash ./gradlew --offline --no-daemon --no-configuration-cache -PlocalPreview=true -Pecolecta.servidorUrl=http://10.0.2.2:8871 :shared:testAndroidHostTest --rerun-tasks --console=plain

bash ./gradlew --offline --no-daemon --no-configuration-cache -PlocalPreview=true -Pecolecta.servidorUrl=http://10.0.2.2:8871 -Pecolecta.qa.coder1=true :androidApp:assembleDebug :androidApp:assembleDebugAndroidTest --console=plain
```

Integraciones: `ECOLECTA_SERVIDOR_PRUEBA=http://127.0.0.1:8765`, `ECOLECTA_SERVIDOR_BD` con la ruta ficticia y `ECOLECTA_PIN_ADMIN` inyectado al proceso, nunca credenciales productivas. Su precondición de liquidación se preparó solo en esa BD sin cambiar código C4. Sin estas precondiciones no afirmar que los casos HTTP se ejecutaron. Las rutas `/tmp` no son configuración permanente.

### Instrumentación nativa

| ID | Qué se comprobó | Límite |
|---|---|---|
| NAT-C1-01 | Keystore real: cifrar/abrir, nonce aleatorio y rechazo de contexto equivocado. | No iOS, hardware físico ni restauración de backups. |
| NAT-C1-02 | Enum móvil de cuatro roles. | Autorización concreta se prueba también en PHP/JVM; no todo recorrido UI. |
| NAT-C1-03 | Crear zona/vehículo/tres cuentas/ficha en BD nativa, enviar con Ktor a Laravel y descargar a otra BD nativa con dueño/vínculos. | Dos BD independientes en un AVD, no dos dispositivos físicos. |
| NAT-C1-04 | Puerto inaccesible conserva pendiente; recuperación envía una vez; nuevo intento envía cero. | No prueba red de campo. |
| NAT-C1-05 | Segundo cliente edita; primero con revisión antigua conserva pendiente y recibe conflicto. | No mezcla valores ni decide cuál es correcto. |
| NAT-C1-06 | Login y selección Admin con DI real; capturas de login/panel. | Invocación por casos de uso; sin pulsaciones manuales. |
| NAT-C1-07 | Otro proceso restaura sesión Admin. | No reinicio físico ni certificación de formularios. |

Capturas: [login](../pruebas/evidencias/coder1-2026-10-25/android-login.png), [panel](../pruebas/evidencias/coder1-2026-10-25/android-administrador.png), [reapertura](../pruebas/evidencias/coder1-2026-10-25/android-reapertura.png), [cuatro roles web](../pruebas/evidencias/coder1-2026-10-25/web-cuatro-roles.png), [altas Android visibles en web](../pruebas/evidencias/coder1-2026-10-25/web-altas-android.png), [zona Calidad](../pruebas/evidencias/coder1-2026-10-25/web-calidad-zona.png), [zona protegida](../pruebas/evidencias/coder1-2026-10-25/web-zona-protegida.png). Solo datos ficticios. Reapertura muestra sincronización en curso y pendientes demo ajenos a C1: no acredita resolver esas colas.

## E. Mini tutoriales de comprobación manual

Estos pasos son instrucciones para comprobación pendiente, no actas ya realizadas. Usar únicamente preview/servidor local con datos ficticios. No borrar datos del AVD ni migrar una BD real.

### Preparación

1. Android Studio → Device Manager → un AVD con **solo datos ficticios**, instalar el preview dirigido a `http://10.0.2.2:8871`, sin Firebase. Este cierre usó el AVD temporal `Coder1_Cierre_26` (`emulator-5558`, sin ventana), no modificó userdata del Galaxy anterior. Los controles se probaron por instrumentación; un integrante debe repetir manualmente las rutas. La URL por defecto del proyecto sigue siendo remota: no compilar pruebas sin override local.
2. Navegador → `http://127.0.0.1:8871`. Cuenta local ficticia del seeder: `admin`, PIN `0000`; no reutilizar en producción.
3. Altas nativas existentes: `qa_c1_ed120b_0` Acopiador, `_1` Calidad y `_2` Proveedor, PIN ficticio `1357`. No recrearlas ni convertirlas a Admin. Ficha `QA-ED120B`, zona de servidor `QA SEGUNDO qa_c1_ed120b`.
4. Se apagó normalmente solo el AVD temporal propio al terminar, sin borrar userdata ([recibo](../pruebas/evidencias/coder1-2026-10-26-cierre/avd-cierre.json)). Con el ejecutable `emulator` del SDK en PATH, puede reabrirse mientras exista la carpeta con `ANDROID_AVD_HOME=/tmp/ecolactea-c1-cierre-ei2ci5pu/avd emulator -avd Coder1_Cierre_26 -port 5558 -no-snapshot -gpu software`. Si el servidor temporal deja de responder, preparar otra copia aislada/semillas, no usar producción. Registrar pasos, esperado/obtenido y captura con la [plantilla existente](../plantillas/ejecucion-prueba.md).

### T01 — Cuatro roles y permisos

1. Web, `admin` ficticio → Usuarios y roles → Nuevo usuario/Editar: solo Acopiador, Calidad, Administrador y Proveedor.
2. Android, cerrar sesión e ingresar por separado con `_0`, `_1`, `_2` y `admin`; observar su menú propio. Los accesos de cuatro perfiles sí se ejecutaron gráficamente.
3. Web Calidad/Acopiador → intentar `/admin/usuarios`: debe responder 403; Proveedor debe ser rechazado por el panel de personal. No usar token Admin para atribuir permisos a otro perfil.
4. API debe rechazar `asistente`/otros retirados, no convertirlos. Comparar rol exportado/móvil. Calidad solo gestiona calidad/sanciones y consulta recepción, entregas, reportes y comunicados; producción/liquidaciones deben dar 403 y sus KPI no deben aparecer. Una delegación adicional sí necesita aprobación.

### T02 — Sesión persistida y revocación conocida

1. Android, `_0` ficticio → ingresar, cerrar/reabrir **sin borrar datos**: conserva sesión autorizada.
2. Web Admin → desactivar esa cuenta de prueba; móvil conectado → sincronizar/revalidar: no debe conservar acceso ni interpretar denegación como desconexión.
3. Reactivar en web para continuar; repetir login y comprobar cuenta/rol correctos. La reapertura del proceso y denegaciones están probadas; registrar la propia repetición con captura.

### T03 — PIN aprobado y transición heredada

1. Web Admin → Usuarios y roles → Nuevo usuario, o Android Perfil → Usuarios y roles → Nueva cuenta; el integrante crea una cuenta **ficticia** con PIN de cuatro dígitos y confirmación.
2. Intentar tres/cinco dígitos o letras al asignar/restablecer: debe rechazarse sin crear cuenta ni cambiar su hash.
3. En copia de pruebas con cuenta heredada preexistente de 5–8, ingresar con su PIN original: ambos login conservan acceso hasta que Admin lo restablezca manualmente a cuatro. Después debe fallar el anterior y funcionar el nuevo; descargar/revalidar móvil.
4. No crear un PIN largo por el formulario nuevo ni cambiar credenciales reales. La prueba heredada PHP/JVM está ejecutada; el restablecimiento manual desde navegador queda a cargo del integrante que introduce la credencial.

### T04 — Bloqueo, reconexión y límite offline

1. Android `_1` ficticio → ingresar. Web Admin → buscarla → Bloquear → motivo de prueba → Confirmar bloqueo.
2. Online: cerrar/reintentar y revalidar; no debe abrir sesión. Se ejecutó bloqueo web y rechazo gráfico Android. Web → Desbloquear al terminar (el integrante confirma el diálogo JavaScript).
3. En AVD de pruebas, iniciar antes con cuenta habilitada, desconectar/reabrir y conservar cambios pendientes; reconectar y aplicar bloqueo/cambio de rol conocido. El test nativo previo usa puerto local inaccesible y reintento, no modo avión real.
4. El controlador del navegador no pudo resolver la confirmación JavaScript de desbloqueo. Para no dejar bloqueado el fixture se ejecutó `DesbloquearUsuarioUseCase` con Artisan, verificando ruta exacta de SQLite ficticio y username; después se observó Activo en web. **No acredita desbloqueo gráfico**, pendiente de repetición por un integrante.
5. **No esperar expiración a las 24 h:** el equipo pidió dejar pendiente el plazo. Una revocación desconocida no puede aplicarse estando totalmente offline. Registrar hora/estado/red, sin afirmar una política inexistente.

### T05 — Zonas con referencias

1. Android Admin → Perfil → Zonas y rutas → Nueva zona: vacío debe mostrar error; `QA ZONA MANUAL 26` debe guardarse. Este alta/rechazo gráfico ya fue ejecutado con nombre único.
2. Web → Sectores y vehículos → Zonas: después de sincronizar debe verse el mismo nombre, sin duplicado al reintentar.
3. Con zona ficticia referenciada por proveedor/cuenta activa o jornada abierta, intentar desactivar: debe rechazarse conservando estado. Una zona libre sí se desactiva. No cerrar ni alterar jornadas reales para forzar la prueba.

### T06 — Vehículos con jornada abierta

1. Android Admin → Perfil → Vehículos → Nuevo vehículo: vacío debe rechazarse; ingresar `Vehículo ficticio manual`, placa `QA-M26` y Registrar vehículo.
2. Sincronizar y comprobar nombre/placa en web → Sectores y vehículos → Vehículos. No debe truncar una placa válida de hasta 20 caracteres.
3. Vehículo libre: desactivar/activar debe persistir. Con jornada abierta **solo fixture aislado coordinado con Coder 2**: debe rechazar desactivar. Guarda automatizada ejecutada; recorrido gráfico de todos esos estados pendiente.

### T07 — Asignaciones y vínculos

1. Web Admin → editar `_1`: conservar Calidad y seleccionar zona activa; guardar. Android → Perfil → Usuarios y roles → cuenta: descargar y comparar misma zona.
2. Con ficha ficticia `QA-ED120B`, comprobar vínculo `_2`; desvincular explícitamente en copia aislada sin pendientes y descargar: debe quedar libre, no reasignarse por nombre. Reasignar explícitamente para restaurar fixture.
3. Repetir en dos dispositivos y registrar ambos IDs/resultados. El 26 se ejecutaron tres fases en `emulator-5554` y `emulator-5556`, con userdata/SQLite independientes: estado inicial, zona reasignada y vínculo null, restauración. Se comprobó ficha única y mismos datos en ambos. **Falta hardware físico/campo**, no esta comprobación entre dos AVD.
4. Si existe edición pendiente, una descarga no debe borrarla ni robar el vínculo; verificar cola/conflicto y no forzar borrado.

### T08 — Campos y proveedor

1. Web Admin → Proveedores → `QA-ED120B` → Editar: poner teléfono `+51 900 000 026`, guardar; Android → Inicio → Proveedores → buscar código → ficha → Editar datos del proveedor: debe verse completo.
2. Android → propietario/responsable `Responsable ficticio UI26` → Guardar cambios → sincronizar. Web debe mostrarlo con la misma cuenta vinculada; este ida/vuelta fue ejecutado y capturado.
3. Rechazar capacidad 0/negativa/no finita, textos fuera de topes y código duplicado; no crear otra ficha al renombrar su código por ID. La unidad L debe aparecer una vez.
4. Una cuenta con DNI legado sin cambio debe permitir editar nombre conservándolo. Cambiar a un nuevo DNI inválido debe rechazarse. Crear una cuenta con DNI personal de 8, username 3–30 y un rol. Calidad exige zona activa. Ficha productiva admite documento de hasta 20; comprobar tachos 1.000 válidos/1.001 rechazados, capacidad 0,01 válida y NaN/infinito rechazados. Combinaciones e identidad heredadas se conservan si no cambian.

### T09 — Migración sin perder cuentas

1. **No usar el panel ni una BD real para ensayar migraciones.** En copia aislada, usar los fixtures históricos ficticios de `MigracionRolesCoder1Test`/`RolesCompatiblesCoder1Test`.
2. Ejecutar sus tests; comparar antes/después IDs, hashes, zona, ficha y archivo de roles. `foreign_key_check` vacío e `integrity_check=ok`; ningún rol retirado se vuelve Admin.
3. Reversión PHP: recupera asociación original solo si no pisa una asignación posterior; si la hay debe detenerse. Binario actual no vuelve a autorizar roles retirados.
4. Abrir las cuentas preservadas con Admin, asignar perfil oficial únicamente tras decisión funcional y descargar al móvil. Censo/backup/reparto reales siguen pendientes; registros borrados por una versión anterior necesitan respaldo.

### T10 — Dos ediciones del mismo catálogo

1. Android Admin → editar proveedor ficticio, dejar pendiente en cliente aislado sin red. Web Admin → editar el mismo campo y guardar.
2. Recuperar red/enviar: debe informar conflicto 409 y conservar pendiente, sin reemplazar silenciosamente el valor web. Inspeccionar cola/detalle, no borrar SQLite.
3. Comparar ambas versiones y decidir explícitamente la corrección; reintentar un estado ya aceptado no debe duplicar. NAT-05 y suites automatizadas acreditan el conflicto; versiones antiguas sin control de revisión no tienen esa garantía.

### T11 — Secretos protegidos Android

1. En el preview con Admin ficticio, crear/restablecer PIN de cuenta de prueba dejando envío pendiente; no mostrar PIN/token en capturas o logs.
2. Ejecutar `CredencialesProtegidasCoder1Test` y NAT-C1-01 con entorno QA: las filas recuperables deben tener envoltura cifrada, abrir solo con clave/contexto correcto y rechazar otro contexto; hash local sigue distinto del PIN.
3. Sincronizar y comprobar confirmación de envío/cierre de sesión. No exportar base ni claves a Git. Esta prueba requiere herramientas de test, no hay un botón «ver secretos» en producto; iOS/backup físico no están acreditados.

### T12 — Recorrido funcional y registro

1. Iniciar servidor local/preview sin Firebase; ejecutar en AVD login → Perfil/Usuarios, Zonas, Vehículos e Inicio/Proveedores con Admin ficticio.
2. Repetir menús con `_0`, `_1`, `_2`, rechazos y bloqueo; guardar capturas, esperado/obtenido y entorno mediante la plantilla de ejecución.
3. Confirmar datos web→Android→web y reintento sin duplicados. El cierre agregó alta/edición/PIN de cuenta, desactivar/reactivar cuenta/zona/vehículo libre y comparación web, con recibos reales.
4. **Falta** desbloqueo gráfico web y alta/restablecimiento manual web, recorridos con todas las referencias, hardware físico y campo. No afirmar validación del beneficiario, revisión de compañero ni DoD por compilar o por pasar la suite.

## F. Problemas encontrados y correcciones

| Problema | Corrección y comprobación |
|---|---|
| Sesión sobrevivía a cambios conocidos; fallos de selección/almacenamiento podían anunciar éxito. | Revalidación observable, flag y Result seguro; tests de auth/sesión y reapertura nativa. |
| Denegación remota podía confundirse con falta de red. | Fallback solo por desconexión, no 401/bloqueo/perfil retirado; acceso/auditoría probados. |
| Zona web sin guardas y posibles cambios parciales API. | Casos de uso y transacción; tests de rechazo y captura web. |
| Test de flash dependía de varias requests sintéticas. | Aserción exacta de error/estado y ejecución real en navegador. |
| Tests previos esperaban roles que el equipo retiró. | Fixtures/aserciones ajustados a decisión y nuevos tests de rechazo/Admin conservado. No se quitaron reglas de negocio para ocultar fallos. |
| Fixtures de esquemas antiguos conservaron columnas nuevas duplicadas. | Preparación del fixture corregida, migraciones históricas intactas; suite completa aprobada. |
| Hash de PIN en UI y test asíncrono sin esperar final. | Dispatchers.Default y espera observable; no se redujo coste ni validación. |
| Integración esperaba liquidación ficticia ausente. | Precondición solo en SQLite de pruebas, sin cambio C4. |
| Suite HTTP encontró servidor local detenido. | Reinicio local y repetición completa: 306/306 sin omitidas. Intento fallido conservado en `/tmp`. |
| Secretos recuperables persistían sin protección específica. | Cifrado Android transaccional/fallo cerrado y Keystore real probado. Sin afirmación de borrado físico de backups. |

### Incidencias de la continuación del 26-10

| Incidencia real | Corrección y evidencia |
|---|---|
| PIN 4 frente a 4–8 | Decisión expresa cuatro nuevos + transición heredada; PHP y JVM finales aprobados. |
| Truncamiento de teléfono/placa y DNI legado bloqueando edición ajena | Se permite entrada sin recorte, se valida límite de dominio y se conserva documento sin cambio; regresiones nuevas aprobadas. |
| Capacidad NaN/infinita no transportable | Rechazo en modelos C1 de ambas plataformas; mínimo ya exigido por web. |
| El patrón PHP con `$` aceptaba un salto final en el dominio | Se usaron anclas absolutas `\A[0-9]{4}\z`, con prueba directa del caso de uso; no se cambia la compatibilidad de login. Suite PHP final 329/329. |
| Ficha mostraba «80,0 L L» | Se quitó solo la segunda unidad de la etiqueta C1; no cálculos de acopio. |
| Fixture de calidad «hace una hora» cruzaba medianoche Lima | Solo fecha del fixture pasó a `now()` para que represente el mismo día; aserciones y negocio C3 intactos. Suite final 329/329. |
| Filtro Gradle usado como regex y test con DNI nullable | Se usaron filtros de clase admitidos y `checkNotNull` en fixture; suite final 313/313 sin omitir casos. |
| Controlador gráfico leía caché/terminaba antes de completar scroll; proveedor abría detalle, no edición | Se refrescó caché de accesibilidad, se espera estado real y se pulsa Editar datos del proveedor. No se cambió la app para ocultar esos fallos del test. GUI final 10/10. |

### Incidencias del cierre de acuerdos

- Los fixtures positivos que cambiaban a Calidad necesitaron una zona activa; el test antiguo de acceso a liquidación cambió a 403 por el acuerdo explícito, conservando las pruebas de Admin.
- La preparación de copia omitió vistas `resources/views/vendor` versionadas y al runtime le faltaba GD. Se corrigió solo el entorno de pruebas. El texto previo de Admin se conservó tras detectar una regresión de presentación.
- El tope de 1.000 tachos ya estaba en la API, pero el formulario web solo exigía cantidad positiva; se aplicó el tope aprobado a ambos. El alta conjunta tenía límites menores y recortaba campos: contrato aprobado aplicado mediante la validación común de `Proveedor`, sin convertir DNI de cuenta en documento de ficha.
- Los primeros intentos GUI no esperaban animación/persistencia: se corrigió el test. Después apareció el fallo real del ACK, corregido y reproducido con éxito en el recibo final.

### Capturas gráficas del 26-10

[Cuatro roles web y cuenta restaurada](../pruebas/evidencias/coder1-2026-10-26/web-cuenta-restaurada.png) · [Proveedor Android visible en Laravel](../pruebas/evidencias/coder1-2026-10-26/web-proveedor-android.png) · [Formulario de cuenta rechazado](../pruebas/evidencias/coder1-2026-10-26/coder1-ui-cuenta-rechazada.png) · [Zona creada Android](../pruebas/evidencias/coder1-2026-10-26/coder1-ui-zona-creada.png) · [Vehículo creado Android](../pruebas/evidencias/coder1-2026-10-26/coder1-ui-vehiculo-creado.png) · [Bloqueo rechazado en Android](../pruebas/evidencias/coder1-2026-10-26/coder1-ui-cuenta-bloqueada.png) · [Primer AVD](../pruebas/evidencias/coder1-2026-10-26/android-avd-uno.png) · [Segundo AVD](../pruebas/evidencias/coder1-2026-10-26/android-avd-dos.png). Resto de capturas y hashes en los recibos de D.

Capturas del cierre: [cuenta ficticia creada](../pruebas/evidencias/coder1-2026-10-26-cierre/coder1-ui-cuenta-creada.png), [Calidad Android](../pruebas/evidencias/coder1-2026-10-26-cierre/coder1-ui-calidad.png), [Calidad web sin módulos financieros](../pruebas/evidencias/coder1-2026-10-26-cierre/web-calidad-dashboard.png), [rechazo web de producción](../pruebas/evidencias/coder1-2026-10-26-cierre/web-calidad-produccion-403.png). Se corrigió la espera de captura del arnés y se repitieron los recorridos; no se retocaron imágenes ni se convirtió una captura transitoria en evidencia de otro estado.

## G. Comparación y condición de terminado

| Área | Android probado | Laravel probado | Límite pendiente |
|---|---|---|---|
| Roles/acceso | Cuatro perfiles y un rol en nuevas cuentas; combinaciones antiguas conservadas. | Matriz Calidad aprobada, rutas y datos/KPI filtrados; Admin conserva módulos. | Homologación del Charter y cualquier delegación nueva; sin perfiles retirados asignables. |
| PIN | Cuatro nuevos/restablecidos, entrada 5–8 heredada; cambio gráfico Android y nuevo/antiguo PIN contra Laravel comprobados. | Nuevos/restablecidos cuatro; login heredado y cambio manual probados en SQLite. | No se cambiaron credenciales reales; el ejercicio gráfico de restablecimiento queda al integrante. |
| Offline | Pendientes y sesión, puerto inaccesible/reintento, conflicto nativo. | Refresh, bloqueo y denegación de tokens probados. | Plazo máximo diferido por el equipo; revocaciones desconocidas sin conexión y campo. |
| Catálogos | Alta/edición/PIN de cuenta, estados cuenta/zona/vehículo libre y ficha con límites acordados comprobados. | Datos de cuenta editada y fichas conservados al sincronizar; límites y conflictos probados. | Desbloqueo gráfico/credencial manual web y recorridos restantes con referencias, teléfonos y campo. |
| Asignaciones/migración | Zona/vínculo/archivo y dos BD nativas. | FK/archivo/reversión protegida y guardas. | Dos AVD independientes probados; teléfonos físicos/censo/backup y ensayo real antes de distribuir. |
| Otros módulos | Regresión compartida completa aprobada. | Regresión completa aprobada; Admin conserva acceso. | Aceptación funcional de Coders 2/3/4 y beneficiario; las suites no cubren cada flujo posible. |

**Conclusión: Coder 1 todavía no cumple integralmente su condición de terminado.** Se demostró coincidencia de usuarios/catálogos en los casos sincronizados y restricciones de los cuatro perfiles, con **10 tareas completadas en su alcance técnico local y 2 pendientes: T04 y T12**. T01 y T08 incorporan ahora las decisiones ratificadas por el equipo. Continúan sin evidencia hardware físico/campo, homologación del Charter por beneficiario y el desbloqueo gráfico web; el TTL sigue aplazado expresamente. No se certifican historias Done, PR aprobado ni despliegue.

## H. Pendientes y decisiones vigentes

1. **APROBADO por el equipo en esta conversación:** cuatro roles; un rol nuevo por cuenta y combinaciones heredadas hasta revisión manual; Calidad restringida a los módulos indicados en A; Admin conserva sus funciones.
2. **APROBADO — identidad y fichas:** DNI personal 8; username 3–30; zona activa para Calidad nueva/reasignada; ficha con topes 50/150/20/30/255, tachos 1–1.000, capacidad finita ≥0,01 L. Datos heredados no se convierten automáticamente. PIN nuevo/restablecido 4; login legado 5–8 hasta cambio manual.
3. **T04 — PENDIENTE DE VALIDACIÓN:** plazo offline diferido de nuevo por decisión expresa. Sin TTL nuevo; revocación conocida se aplica, la desconocida no puede detectarse sin red. No se impuso 24 horas.
4. **T12 — EVIDENCIA PENDIENTE:** desbloqueo gráfico web y alta/restablecimiento manual web por integrante; teléfonos físicos y campo con red intermitente. Los tests gráficos automatizados y los tests de servidor no se presentan como ejecución manual por el equipo.
5. **Validación externa:** homologar RF-USU-02/Charter con beneficiario/docente y confirmar cualquier delegación adicional a Coders 3/4. No se inventa aprobación ni se eliminan sus funciones; regresión técnica no sustituye aceptación funcional.
6. **Distribución:** censo privado, respaldo/ensayo con copia real y versión coordinada API/app con ACK de revisión. No ejecutar migraciones en real ni publicar bases/credenciales. Conflictos previos necesitan revisión; no se limpian ni resuelven automáticamente.
7. iOS/Keychain y Firebase normal no quedan acreditados por el preview Android/Laravel.

**Git:** solo cambios locales; sin staging, commits, push, PR, merges ni cambios remotos. Inventario y comprobaciones en C/D. Se conservan evidencias del 25, fuentes Scrum/Kanban y requisitos originales; este informe no crea otro backlog ni ceremonias ficticias.
