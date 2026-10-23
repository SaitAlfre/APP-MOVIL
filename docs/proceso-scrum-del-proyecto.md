# Proceso Scrum del proyecto — Ecolactea Digital

**Versión 2026-10-23.** Proceso propuesto, ratificación del equipo pendiente. Base: F1 d9–10, F2 y F3 ([fuentes](academico/fuentes-docentes.md)). No acredita Scrum histórico.

## Propósito y elección

**Product Goal:** acopio y calidad trazables en Huata, conservados sin conexión y sincronizados con confirmación, con información útil para planta/productores; incorporar producción por cortes verificables.

**Scrum es el marco principal, con prácticas Kanban.** Para cuatro estudiantes y un producto avanzado con incertidumbre de calidad/sincronización, permite inspeccionar valor semanal y limitar trabajo simultáneo.

| Enfoque | Uso en Ecolactea |
|---|---|
| Scrum | Goals, backlogs, incremento, responsabilidades, eventos y DoD; organiza inspección/adaptación. |
| Kanban | Visualizar tareas, bloqueos y WIP; complementa los sprints. |
| XP | Revisión, unitarias e integración frecuente como prácticas propuestas; no se declara XP/TDD histórico. |

## Roles propuestos

| Integrante | Responsabilidad |
|---|---|
| Sait Alfredo Gutierres Velasquez | PO: valor, orden y vínculo con beneficiario. |
| Quispe Valencia Erick Maycol | SM: facilitar eventos, remover obstáculos y mejorar proceso. |
| Medina Paye Jhosue Daniel | Developer: referente móvil/offline, pruebas y revisión. |
| Dubcek Leonel | Developer: referente API/datos, pruebas y revisión. |

Developers seleccionan/desglosan; SM no es jefe. Cada autor documenta su cambio y otro lo revisa. PO/SM pueden aportar técnicamente con capacidad acordada. Acta/disponibilidad: EVIDENCIA PENDIENTE.

## Calendario y eventos — America/Lima

Intervalos: 17–24/09, 24/09–01/10, 01–08/10, 08–15/10, 15–22/10, **22–29/10**, 29/10–05/11, 05–12/11, 12–19/11, 19–26/11. Los primeros cuatro son análisis Git; S05 preparación; adopción futura propuesta en S06. Mantener visible el hito **23/11** dentro de S10.

| Evento | Agenda propuesta | Timebox / participantes |
|---|---|---|
| Sprint | Jueves 18:00→jueves 18:00 | 1 semana; Scrum Team |
| Planning | Apertura jueves 18:00–20:00 | ~2 h; Scrum Team |
| Daily | Lunes–viernes 08:00–08:15 | 15 min; Developers |
| Review | Cierre jueves 16:00–17:00 | ~1 h; equipo y beneficiario disponible |
| Retrospective | Cierre jueves 17:00–17:45 | ~45 min; Scrum Team |

Refinar durante el Sprint. Retro: cinco fases y 1–3 acciones con dueño/fecha. Fechas **PENDIENTES DE VALIDACIÓN**: quincenas F1 vs semanas F2; evaluación 19/22-10; dos simulaciones del TXT; hito 23-11 vs entrega referencial OpenUP 26-11. Scrum + Kanban se mantiene; [detalle/capacidad](scrum/calendario-y-capacidad.md). Tres tramos Git 18/2/2 se conservan como antecedentes.

## Flujo, Done y herramienta

Necesidad→RF/RNF→HU/CA→refinamiento/MoSCoW/Poker→Planning según horas reales→tareas→desarrollo→revisión/pruebas→incremento→Review→Retro. SP y horas se separan; votos/capacidad reales pendientes.

**DoD:** CA satisfechos, implementación completa, PR aprobado por otro integrante, unitarias/aceptación satisfactorias, integración sin errores, despliegue a pruebas, documentación y evidencia versionada. Todo control sin respaldo permanece pendiente; código no equivale a Done. [Checklist](scrum/definition-of-done.md).

Herramienta operativa desde el 23-10: [GitHub Projects privado](https://github.com/users/SaitAlfre/projects/1/views/2), 41 historias y 72 tareas identificadas; [instantánea](kanban/project-operativo.json). Acceso del cuarto integrante y adopción del equipo pendientes. Columnas Backlog→To Do→In Progress→Review→Done. WIP propuesto: 2 por columna activa y 2 en total, incluidas bloqueadas; tareas como unidad, sin duplicar HU padre. [Políticas](kanban/tablero.md). [Repositorio](https://github.com/SaitAlfre/APP-MOVIL).

Solo main local confirmada; feature→develop→main es [flujo propuesto](scrum/flujo-git-y-revision.md). [Backlog](scrum/product-backlog.md), [guía práctica](COMO-TRABAJAMOS.md) y [matriz académica](academico/matriz-cumplimiento.md) enlazan evidencia y pendientes.
