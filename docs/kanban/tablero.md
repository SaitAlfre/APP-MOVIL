# Kanban integrado con Scrum

**Estado real, 23-10-2026:** [Project privado operativo](https://github.com/users/SaitAlfre/projects/1/views/2), vinculado a APP-MOVIL. Se verificó `SaitAlfre` con permisos de administración/escritura. Existen 113 issues únicos: 41 historias y 72 tareas en Backlog inicial, sin transiciones históricas inventadas. [IDs/URLs reales e instantánea](project-operativo.json). Acceso al Project configurado para los cuatro; capturas de distintos días, adopción e invitación de colaboración al repositorio por aceptar siguen pendientes (EP-11).

GitHub Projects es la fuente operativa del estado. El [registro de tareas](../scrum/tareas.md) mantiene alcance/horas y el [backlog](../scrum/product-backlog.md) los criterios: no duplicarlos en los issues. La instantánea conserva IDs y relaciones al crear el tablero; no es otro estado editable. Accesos confirmados del Project: propietario y tres compañeros con Write. La [tabla canónica de cuentas y accesos](../scrum/roles.md#cuentas-github-y-accesos) distingue el permiso del Project de la invitación pendiente al repositorio de `jhosuemedina03`.

## Columnas, entrada y salida

| Columna | Significado / entrada | Salida / quién actualiza |
|---|---|---|
| Backlog | Trabajo identificado y ordenado por valor; puede requerir refinamiento. | A To Do tras selección real en Planning y CA/dependencias disponibles. Autor/Developers actualizan; PO ordena. |
| To Do | Tarea seleccionada para Goal, responsable y salida acordados; aún no iniciada. | Autor pasa a In Progress al comenzar y si hay WIP. |
| In Progress | Trabajo efectivamente iniciado, incluida corrección. | Autor pasa a Review cuando existe trabajo inspeccionable, PR o salida documental, con evidencia pertinente. |
| Review | Revisión real solicitada y artefacto disponible; incluye espera de comprobaciones de cierre. | Revisor registra observaciones; autor vuelve a In Progress si corrige. A Done solo tras requisitos aplicables. |
| Done | Salida de tarea contrastada; HU solo si CA y DoD completos. | Autor registra evidencia/fecha y revisor; no automatizar “merge = HU Done”. |

**Prioridad** (MoSCoW/orden del PO), **estado** y **bloqueo** son campos distintos. HU-04/13 necesitan inspección prioritaria, pero eso no las coloca en Review. Instantánea inicial del 23-10: todas las tarjetas en Backlog, sin selección ni compromiso de Sprint acreditados. No hay tarjetas Done acreditadas.

## WIP y bloqueos

La [política canónica de WIP](wip-limits.md) mantiene los máximos por columna, el límite activo total y sus excepciones. Consultarla antes de iniciar una tarea; las bloqueadas cuentan y las HU padre no se contabilizan otra vez. Al alcanzar el límite, ayudar a revisar/desbloquear. PO/SM que colaboren técnicamente no añaden capacidad automática.

Registrar bloqueo en la misma tarjeta: motivo, desde cuándo, dependencia/EP, persona que puede ayudar y próxima revisión. No mover a una columna que borre el estado real; desbloquear antes de empezar más.

## Procedimiento de mantenimiento; configuración inicial ya ejecutada

1. Reutilizar el Project enlazado arriba y consultar sus IDs antes de crear tarjetas. La autenticación ya fue comprobada; no crear otro tablero para representar el mismo trabajo.
2. Crear vista Board con Status: Backlog, To Do, In Progress, Review, Done.
3. Campos: tipo HU/tarea/bug; ID; HU padre; RF/RNF/CA; responsable; prioridad; sprint/iteración semanal; horas de tarea; bloqueo/motivo; PR; evidencia; inicio/fin.
4. Crear Issue de historia con enlace al catálogo; tareas como Issues vinculados o checklist con ID. **Solo las tareas ejecutables cuentan para WIP**; HU padre resume y no se cuenta de nuevo.
5. En Planning seleccionar historias y tareas, asignar sprint y To Do; no importar estados de auditoría como progreso real.
6. Cada autor actualiza transiciones reales. El revisor enlaza aprobación/resultados; cerrar HU según DoD, sin cierre automático por merge.
7. Capturar tablero al inicio, otro día de trabajo y cierre, con fecha/URL; exportar datos para métricas. [Capturas](capturas/README.md).

## Ejemplo de tarjeta, no tarjeta existente

- ID T-41; HU-04-A; CA-HU-04-A-01/02/03; RF-SIN-01/02/03; RNF-02.
- Sprint candidato S06; responsable propuesto Medina Paye Jhosue Daniel; 2 h iniciales; prioridad por ratificar con PO.
- Estado real no confirmado; To Do solo después de selección. Caso CP-002/007; PR y ejecución por registrar cuando existan.

Su ID se localiza en el [Project real](https://github.com/users/SaitAlfre/projects/1/views/2) y en la [instantánea](project-operativo.json). Los valores anteriores son propuestas; no acreditan selección ni ejecución. El [flujo Git](../scrum/flujo-git-y-revision.md) explica ramas y revisión.

## Antecedente: export local verificado, 2026-10-22

[Configuración JSON](configuracion-propuesta.json) y [113 tarjetas propuestas CSV](importacion-propuesta.csv): 41 HU y 72 tareas futuras; los 19 aportes históricos no se importan como trabajo nuevo. Regeneración: `python3 docs/herramientas/verificar_documentacion.py --export-kanban`. Fuentes únicas: backlog/tareas. Todas las filas parten de **Backlog propuesto**, no movimientos reales ni compromiso pasado. Los nombres de responsables son propuestas del equipo; no se inventan usuarios GitHub.

**Aplicación al recuperar acceso:** revisar Projects existentes en la [página del repositorio](https://github.com/SaitAlfre/APP-MOVIL/projects) y del propietario; reutilizar el adecuado, registrar URL aquí. Crear/ajustar manualmente los cinco Status/campos del JSON; vincular Issues/tareas y Sprint candidato del CSV. CSV es un formato de intercambio, **no se afirma importación CSV nativa de GitHub Projects**. Crear items manualmente o con API autenticada revisando ID existente antes de crear. Confirmar usuarios/iteraciones antes de asignar. Capturar estado del día y siguientes días reales; no fabricar capturas históricas.

Comprobado localmente: IDs únicos, referencias a fuentes existentes, columnas, SP solo en HU, horas solo en tareas, 113 filas, WIP en tareas y ausencia de secretos. No se habilitó automatización de Done por merge ni recurso remoto. Aún no es tablero operativo: esa parte necesita sesión autenticada y ratificación/uso del equipo.

## Antecedente previo a la conexión: evidencia técnica adicional

Los [controles del agente](../scrum/tareas.md#controles-técnicos-adicionales-ejecutados-por-el-agente) y [recibos](../pruebas/ejecuciones/automaticas/README.md) son trabajo real, sin inventar transiciones en GitHub Projects. El CSV mantiene113 tarjetas de propuesta en Backlog; no se importa como historial ni como Done. Autenticación GitHub sigue pendiente.

## Corte del 23-10 anterior a la autorización de cuenta

API pública y `git ls-remote` confirman solo main y ningún PR abierto. HTTPS no puede obtener identidad de publicación y la clave SSH del agente devuelve `Permission denied (publickey)` tras verificar el servidor. Plugin GitHub instalado oficialmente el 23-10; conexión de cuenta y permisos aún sin verificar, pendiente de autorización del usuario en GitHub. No se creó un Project alternativo ni se duplicaron las 113 tarjetas propuestas. Al autenticarse, consultar primero Projects existentes y conservar sus IDs antes de crear/actualizar.

## Puesta en marcha comprobada el 23-10

Se consultaron Projects e issues existentes antes de crear: no había ninguno. Se configuraron cinco Status y la vista Board, con prioridad y bloqueo separados y puntos/horas en campos distintos. Los nombres y sprints del CSV se trasladaron como **propuestas**, sin asignaciones supuestas. Las subtareas se vincularon a historias reales cuando corresponde; los trabajos transversales HU-14/HU-15/RNF conservan su referencia documental sin inventar HU adicionales.

El [PR #2](https://github.com/SaitAlfre/APP-MOVIL/pull/2) conserva los cuatro commits publicados y solicita revisión a `dubcek-leonel`; no equivale a aprobación ni a Done. El Project sigue privado. WIP se aplica como política del equipo, no como un límite automático del producto. Los JSON/CSV de propuesta anteriores siguen siendo semillas reproducibles, no el estado actual.

Configuración comprobada: APP-MOVIL como repositorio predeterminado; desactivados Auto-close issue, Item added to project, Item closed, Pull request linked to issue y Pull request merged. Solo Auto-add sub-issues to project permanece activo. Cerrar o fusionar por sí solo no cambia automáticamente las historias a Done.

Identidades de los cuatro confirmadas por el propietario y acceso al Project verificado. Queda aceptar la invitación al repositorio de `jhosuemedina03` y que el equipo ratifique roles, capacidad, estimaciones y selección. Los registros de distintos días deben capturarse cuando realmente ocurran.
