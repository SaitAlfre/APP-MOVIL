# Defectos e incidencias

**Estado vigente 23-10:** commits/push autorizados. BUG-002 y BUG-003 se guardaron como commits técnicos locales; PR/aprobación independiente todavía pendientes. Los registros del 22-10 siguientes conservan sus condiciones originales. [Revisión preparada](../../scrum/flujo-git-y-revision.md#solicitud-de-revisión-preparada--2026-10-23).

## BUG-001 — expectativas PHP desactualizadas respecto al menú y rutas

| Campo | Registro real |
|---|---|
| Origen / caso | EXE-20261022-06; CP-AUTO-02; HU-08 como contexto técnico, no fallo de sus CA aprobado. |
| Severidad / prioridad | Media: impide regresión verde, no demuestra defecto funcional. Prioridad técnica alta para completar esta comprobación autorizada; no es orden del PO. |
| Fecha / reportante | 2026-10-22; agente técnico autorizado. |
| Plataforma / versión / entorno | PHP 8.3.6, PHPUnit 12.5.35, SQLite en memoria, copia aislada; base 05743b9. |
| Precondiciones | Vista de paginación restaurada y GD disponible en el runtime temporal. Sin datos productivos. |
| Reproducción | 1. Instalar lockfile en copia aislada. 2. Ejecutar PHPUnit. 3. Comparar cuatro aserciones fallidas de navegación/pantallas/rutas con commit 52dab50. |
| Esperado | Los tests verifican el comportamiento intencional existente: menú actualizado y redirección de pestaña rutas al mapa. |
| Obtenido | Tests aún esperaban Design System, nombre antiguo de menú y 200 en la URL que ahora redirige; catálogo de rutas se comprobaba en pantalla anterior. |
| Evidencia | [EXE-06 fallida y EXE-09/11 de regresión](../ejecuciones/resultados-2026-10-22.md); rutas/hash de tres tests y SHA-256 del parche en JSON. |
| Corrección | Tres tests existentes actualizados para validar menú vigente, redirección y pantalla del mapa. No se cambió implementación funcional, CA de negocio ni dependencias. |
| Responsable / estado | Corrección realizada por agente autorizado en rama local; **regresión Passed, revisión independiente pendiente**. No se presenta como defecto cerrado por el equipo. |
| Tarea / PR | Insumo de T-52/55; sin atribuir transición ni autoría estudiantil. No hay PR nuevo: commits/publicación no autorizados. |
| Regresión | EXE-09: 32/32; EXE-11: 289/289, 1574 aserciones; EXE-10 Pint passed. |
| Fecha de cierre | EVIDENCIA PENDIENTE: revisión por otro integrante. |

## Incidentes del montaje, resueltos

EXE-02: vista excluida al copiar; restaurada. EXE-03: DNS del sandbox; build reintentado con red autorizada. EXE-06: GD ausente; extensión añadida solo al runtime temporal. EXE-13: fixture de liquidación ausente; creado en SQLite temporal. Se conservan los fallos originales; no son reglas empresariales corregidas. Android estándar requiere google-services.json (EXE-07); preview ya pasó sin inventarlo.

OBS-01 y el alcance residual de OBS-02 siguen como [observaciones estáticas de alcance empresarial](../../auditoria-documentacion.md#observaciones-tecnicas-abiertas), sin corrección especulativa. Un resultado verde de las reglas implementadas no homologa su correspondencia con F4. Para nuevas incidencias usar la [plantilla](../../plantillas/reporte-defecto.md).

## BUG-002 — ANR al sembrar cuentas en el hilo principal Android

| Campo | Registro real |
|---|---|
| Origen / caso | EXE-17/18, CP-AUTO-07; HU-01/CA-HU-01-01 como contexto de acceso, insumo T-33/52, EA-04. |
| Severidad / prioridad | Alta: impedía completar el primer arranque en el emulador. Prioridad técnica alta en esta autorización; no prioridad ratificada por el PO. |
| Fecha / reportante | 2026-10-22; agente técnico autorizado. |
| Plataforma / versión | Android 16/API 36 emulado; APK localPreview base 05743b9 + tres tests PHP antes de corregir; datos de sistema nuevos, sin producción. |
| Reproducción | 1. Instalar preview en imagen temporal nueva. 2. Abrir MainActivity. 3. Consultar ApplicationExitInfo/dropbox. Se repitió dos veces. |
| Esperado / obtenido | Mostrar acceso sin bloquear Application.onCreate / proceso termina por ANR failed to complete startup. Traza main deriva PBKDF2 dentro de DatabaseSeeder/iniciarKoin. |
| Evidencia | [Traza mínima auténtica](../evidencias/BUG-002-arranque-android.log), EXE-17/18; original completo temporal con huella en extracto. |
| Corrección / responsable | Agente autorizado: EcolectaApp prepara Koin/datos en Dispatchers.Default; MainActivity espera estado listo, con preparación/error. Solo dos archivos; no cambia hashes, iteraciones, datos, reglas ni dependencias. |
| Estado / PR | Corregido y regresión Passed; revisión independiente PENDIENTE. Sin PR ni commit nuevo por instrucción. No atribuir autoría o reunión al equipo. |
| Regresión | EXE-19 falló por imports no expuestos; se adaptó a widgets existentes. EXE-20 build Passed; 21 acceso visible; 22 login offline; 23 JVM 256/256; 25 persistencia; 26 pérdida de respuesta/reintento. |
| Cierre | EVIDENCIA PENDIENTE de revisión por otro integrante. No se marca cerrado por autoaprobación. |

EXE-24 es incidente del arnés (Back antes de guardar), corregido en EXE-25; no se atribuye a pérdida de datos de la aplicación. La ejecución de red usó ERROR tras 503 del proxy y SYNCED solo tras confirmación, sin reetiquetar el resultado.

## BUG-003 — descarte local de un cambio válido sin autor

| Campo | Registro real |
|---|---|
| Contexto / trazabilidad | RNF-02 → HU-10 / CA-HU-10-01/02 → T-41/52 como insumo técnico → EA-04; no tarea seleccionada en Planning. |
| Reproducción / esperado | Crear zona local sin sesión y ejecutar envío general. Debe conservarse hasta contar con autor válido; no inventar credenciales ni contabilizar recepción remota. |
| Obtenido inicial | `usuarioId` vacío entraba en `completar` y eliminaba la fila de cola sin transmitir. La nueva regresión falló 9/10 antes del parche. |
| Corrección / autor real | Agente técnico autorizado,22-10 Lima: separar cuerpo nulo de autor vacío; este último pasa por `fallar` no definitivo, queda PENDING con motivo y no suma enviados. Edición posterior por administrador válido puede transmitirlo. |
| Archivos | [Caso de uso](../../../shared/src/commonMain/kotlin/pe/ecolecta/domain/usecase/sync/EnviarCambiosServidorUseCase.kt), [tres regresiones](../../../shared/src/androidHostTest/kotlin/pe/ecolecta/data/CambiosPanelWebTest.kt). |
| Verificación | [Recibos reales](../ejecuciones/automaticas/README.md): EXE-20261023T030536Z-jvm-6b 243b Failed 9/10 → EXE-20261023T030710Z-jvm-e 50898 Passed 10/10; suites completas 259/259 posteriores. |
| Alcance residual | Los cuerpos nulos/no aplicables siguen descartándose sin contarse como envío. No se declara toda OBS-02 ni HU-10 cerrada; exclusiones/estados requieren homologación. |
| Estado / revisión | Corregido y regresión Passed; **revisión independiente/PR EVIDENCIA PENDIENTE**. Sin commit nuevo ni autoaprobación. |

## Observación de calendario del panel — OBS-03

El recorrido UI del 22-10 Lima mostró dashboard/reportes con 23-10 UTC y formulario de calidad con 22-10 Lima. `web/config/app.php` defineUTC. Es una discrepancia de presentación/oráculo que puede afectar el concepto de “del día”; **PENDIENTE DE VALIDACIÓN** de la regla de fecha local y migración/almacenamiento antes de cambiar globalmente zona horaria. No se cambió la fecha del sistema ni se inventó una fecha de negocio.

## Incidentes de las nuevas herramientas

[Intentos de preparación](../ejecuciones/automaticas/incidencias-montaje/README.md) y [todos los recibos](../ejecuciones/automaticas/README.md): PHP empezó antes del manifest Vite en el primer montaje; la secuencia se corrigió y 289/289 pasó. Node usó reporter spec, no TAP; el lector acepta ambos y conserva el intento Blocked. El detector de rutas distinguió después una carpeta fuente `home` de una ruta personal; regresión específica añadida. Los selectores de accesibilidad dentro de Instrumentation no reconocieron campos visibles; no son defectos funcionales confirmados. No ocultar estos fallos del arnés ni convertirlos en reuniones o velocidad Scrum.
