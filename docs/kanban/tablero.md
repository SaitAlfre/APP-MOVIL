# Kanban integrado con Scrum

**Estado real:** tablero operativo/URL **EVIDENCIA PENDIENTE (EP-11)**. El 22-10 la vista pública mostró 0 abiertos/0 cerrados y Sign in; no revela Projects privados. No hay sesión/token GitHub disponible. No se creó ningún recurso remoto. Este archivo define políticas; **no contiene un historial de movimientos ficticio**.

GitHub Projects es la herramienta propuesta. El [registro de tareas](../scrum/tareas.md) es fuente provisional del estado. Al confirmar un Project, registrar URL y fecha aquí; usarlo como fuente operativa y conservar un export fechado en tareas. No mantener ambos manualmente.

## Columnas, entrada y salida

| Columna | Significado / entrada | Salida / quién actualiza |
|---|---|---|
| Backlog | Trabajo identificado y ordenado por valor; puede requerir refinamiento. | A To Do tras selección real en Planning y CA/dependencias disponibles. Autor/Developers actualizan; PO ordena. |
| To Do | Tarea seleccionada para Goal, responsable y salida acordados; aún no iniciada. | Autor pasa a In Progress al comenzar y si hay WIP. |
| In Progress | Trabajo efectivamente iniciado, incluida corrección. | Autor pasa a Review cuando existe trabajo inspeccionable, PR o salida documental, con evidencia pertinente. |
| Review | Revisión real solicitada y artefacto disponible; incluye espera de comprobaciones de cierre. | Revisor registra observaciones; autor vuelve a In Progress si corrige. A Done solo tras requisitos aplicables. |
| Done | Salida de tarea contrastada; HU solo si CA y DoD completos. | Autor registra evidencia/fecha y revisor; no automatizar “merge = HU Done”. |

**Prioridad** (MoSCoW/orden del PO), **estado** y **bloqueo** son campos distintos. HU-04/13 necesitan inspección prioritaria, pero eso no las coloca en Review. Estado inicial de historias/tareas: no confirmado; Backlog propuesto. No hay tarjetas operativas Done acreditadas.

## WIP y bloqueos

La [política canónica de WIP](wip-limits.md) mantiene los máximos por columna, el límite activo total y sus excepciones. Consultarla antes de iniciar una tarea; las bloqueadas cuentan y las HU padre no se contabilizan otra vez. Al alcanzar el límite, ayudar a revisar/desbloquear. PO/SM que colaboren técnicamente no añaden capacidad automática.

Registrar bloqueo en la misma tarjeta: motivo, desde cuándo, dependencia/EP, persona que puede ayudar y próxima revisión. No mover a una columna que borre el estado real; desbloquear antes de empezar más.

## Configuración reproducible preparada

1. Confirmar si ya existe Project y pedir su URL/export; reutilizarlo si cumple. El usuario ya autorizó configurarlo sin duplicar; actualmente falta autenticación con acceso al repositorio.
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

No hay enlace operativo verificado para esta tarjeta. El [flujo Git](../scrum/flujo-git-y-revision.md) explica ramas y revisión.

## Export local verificado, 2026-10-22

[Configuración JSON](configuracion-propuesta.json) y [113 tarjetas propuestas CSV](importacion-propuesta.csv): 41 HU y 72 tareas futuras; los 19 aportes históricos no se importan como trabajo nuevo. Regeneración: `python3 docs/herramientas/verificar_documentacion.py --export-kanban`. Fuentes únicas: backlog/tareas. Todas las filas parten de **Backlog propuesto**, no movimientos reales ni compromiso pasado. Los nombres de responsables son propuestas del equipo; no se inventan usuarios GitHub.

**Aplicación al recuperar acceso:** revisar Projects existentes en la [página del repositorio](https://github.com/SaitAlfre/APP-MOVIL/projects) y del propietario; reutilizar el adecuado, registrar URL aquí. Crear/ajustar manualmente los cinco Status/campos del JSON; vincular Issues/tareas y Sprint candidato del CSV. CSV es un formato de intercambio, **no se afirma importación CSV nativa de GitHub Projects**. Crear items manualmente o con API autenticada revisando ID existente antes de crear. Confirmar usuarios/iteraciones antes de asignar. Capturar estado del día y siguientes días reales; no fabricar capturas históricas.

Comprobado localmente: IDs únicos, referencias a fuentes existentes, columnas, SP solo en HU, horas solo en tareas, 113 filas, WIP en tareas y ausencia de secretos. No se habilitó automatización de Done por merge ni recurso remoto. Aún no es tablero operativo: esa parte necesita sesión autenticada y ratificación/uso del equipo.

## Evidencia técnica adicional

Los [controles del agente](../scrum/tareas.md#controles-técnicos-adicionales-ejecutados-por-el-agente) y [recibos](../pruebas/ejecuciones/automaticas/README.md) son trabajo real, sin inventar transiciones en GitHub Projects. El CSV mantiene113 tarjetas de propuesta en Backlog; no se importa como historial ni como Done. Autenticación GitHub sigue pendiente.

## Comprobación de acceso del 23-10

API pública y `git ls-remote` confirman solo main y ningún PR abierto. HTTPS no puede obtener identidad de publicación y la clave SSH del agente devuelve `Permission denied (publickey)` tras verificar el servidor. Plugin GitHub instalado oficialmente el 23-10; conexión de cuenta y permisos aún sin verificar, pendiente de autorización del usuario en GitHub. No se creó un Project alternativo ni se duplicaron las 113 tarjetas propuestas. Al autenticarse, consultar primero Projects existentes y conservar sus IDs antes de crear/actualizar.
