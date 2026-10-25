# Índice de evidencias reales y pendientes

Este índice localiza evidencia; **no inventa aprobaciones ni resultados**. Actualizado 2026-10-22. Cada nueva EV requiere fecha real, autor/ejecutante, alcance, HU/CA/tarea, versión/plataforma/entorno, archivo/URL y límite. Resultados de pruebas viven en EXE; estados en tareas/Project, sin duplicarlos aquí.

<a id="ev-001"></a>
## EV-001 — historial Git

[22 commits completos](../scrum/evidencias-commits.md), 14-09→19-10, siete fechas, tres tramos 18/2/2. Hashes/autores/mensajes reales; no prueba esfuerzo, reuniones o aprobación. HEAD inspeccionado 05743b97f2e5835250e223eabc09f9924b7524bb.

<a id="ev-002"></a>
## EV-002 — archivos y cambios técnicos

[T-01…19](../scrum/tareas.md), [arquitectura](../tecnica/arquitectura-datos-y-sincronizacion.md) y referencias adicionales de producción/venta/recepción/traslado. Apoyo estático, no aceptación ni ejecución. Fuentes de pruebas: 48 Kotlin Test.kt, 32 PHP Test.php, un Firebase test.mjs; no cantidad de casos ni cobertura.

<a id="ev-003"></a>
## EV-003 — log parcial del emulador

[firestore-debug.log](../../firebase-tests/firestore-debug.log), inicio registrado el 24-09-2026 00:34:09 (zona del log no explicitada), WebSocket/servidor iniciado y actividad posterior.

SHA-256 del archivo inspeccionado: **9c92dc6fdf8b5f1df859af55aa78295980bfb5026417994435be037bff51e7df**. Es un archivo versionado observado en HEAD; no se asume que todas las líneas compartan el hash probado de una suite.

[registros-acopio.rules.test.mjs](../../firebase-tests/registros-acopio.rules.test.mjs) contiene assertSucceeds y assertFails. Las denegaciones del log pueden ser casos negativos esperados; no concluir fallo global. Falta resumen de ejecución, selección, estado de salida y versión evaluada para acreditar éxito. Ese log histórico se conserva intacto. El 22-10 sí se ejecutó un emulador aislado con 9/9 Passed: ver EV-006; el archivo histórico no se usa para acreditar ese resultado.

<a id="ev-004"></a>
## EV-004 — observación pública de GitHub

Consulta del 2026-10-21 a [Pull requests](https://github.com/SaitAlfre/APP-MOVIL/pulls): 0 abiertos/1 cerrado, coherente con merge #1 local. No acredita revisión aprobada ni checks. Projects y ramas no se recuperaron en consulta pública; no se afirma inexistencia ni operación. [Flujo Git](../scrum/flujo-git-y-revision.md).

<a id="ev-005"></a>
## EV-005 — documentación y gráficos

Markdown de proceso y cinco gráficos conservados: cuatro Delta académicos y uno de actividad Git. [Datos/interpretaciones](../scrum/metricas.md). Son evidencia de elaboración documental, no de ceremonias, pruebas o métricas Scrum reales.

## Evidencia que aún no existe en este paquete

PR aprobado/capturas, aceptación completa en dispositivos/reglas ratificadas, actas/eventos, votos Poker, Project autenticado/capturas de distintos días, beneficiario y acuse de aula: [EP-01…15](../academico/evidencias-pendientes.md). No crear EV con un URL supuesto o una captura simulada sin etiqueta.

## Agregar evidencia nueva

1. Asignar siguiente EV, registrar contexto/versión y resultado o limitación.
2. Guardar prueba en [ejecuciones](../pruebas/ejecuciones/README.md), imagen/log saneado en su carpeta y enlazarlo, sin duplicar el resultado.
3. Vincular HU/CA/tarea/PR/entregable; compañero revisa.
4. Si es representación académica, rotular; si falta respaldo mantener EVIDENCIA PENDIENTE. Si hay rectificación, conservar original y motivo.

No se copian fuentes empresariales con contactos. [Publicación y respaldos](../academico/archivos-documentacion.md).

<a id="ev-006"></a>
## EV-006 — pruebas y despliegue local reales del 22-10

[26 intentos con comando, fecha, base/parche y resultado](../pruebas/ejecuciones/resultados-2026-10-22.md); JSON conserva clases/tests, huellas y entorno. Último JVM 256/256, PHP 289/289, Firestore 9/9; builds web/preview, acceso web y despliegue loopback temporal comprobados. Ejecutó el agente autorizado, sin atribución a integrantes/beneficiario. Relación HU/CA/tarea/EA-04 en [trazabilidad](../scrum/trazabilidad.md). Aceptación completa y PR aprobado no se infieren.

<a id="ev-007"></a>
## EV-007 — comprobación y configuración local reproducibles

[Verificador](../herramientas/verificar_documentacion.py), [JSON Kanban](../kanban/configuracion-propuesta.json) y [CSV](../kanban/importacion-propuesta.csv): 41 HU/72 tareas propuestas, sin movimientos inventados. Se ejecutó y corrigió el escaneo: 0 errores activos; fuentes F1…F6 coinciden por hash. La [auditoría fechada](../auditoria-documentacion.md) registra el cierre y límites. No acredita Project remoto operativo ni acuerdos del equipo.

**Rectificación de EV-004, 22-10:** API pública recuperada: solo main; PR #1 fusionado y 0 revisiones formales. Projects público 0/0 y Sign in, sin acceso a privados. Datos mínimos en el JSON de EV-006. Las observaciones del 21-10 siguen siendo un corte anterior, no el estado final de consultas.

<a id="ev-008"></a>
## EV-008 — Android real emulado y corrección ANR

EXE-17/18 reprodujeron ANR; [extracto causal](../pruebas/evidencias/BUG-002-arranque-android.log). Dos archivos Android corregidos en rama local, sin cambiar PBKDF2/reglas. EXE-20 compila, 21 muestra acceso, 22 autentica offline, 23 repite 256 JVM, 25 conserva UUID/contexto/20 L/PENDING tras force-stop, 26 pierde respuesta 201 y confirma después sin duplicar una fila/20 L. [Informe/JSON y capturas](../pruebas/ejecuciones/resultados-2026-10-22.md). No son reuniones ni aceptación humana. Backend, proxy y emulador propios detenidos al cierre.
