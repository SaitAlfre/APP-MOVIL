# Metodología: Scrum con prácticas Kanban

[Proceso breve](../proceso-scrum-del-proyecto.md) · [Fuentes docentes](../academico/fuentes-docentes.md)

## Elección y alcance

**Metodología principal: Scrum; prácticas complementarias: Kanban.** Decisión documental aprobada por el usuario; acuerdos operativos del equipo **PENDIENTES DE VALIDACIÓN**. No existe evidencia suficiente para afirmar que todo el desarrollo previo se gestionó formalmente así.

Scrum aporta objetivos semanales, responsabilidades, eventos y los tres artefactos. Product Backlog se compromete con el **Product Goal**; Sprint Backlog con el **Sprint Goal**; el incremento con la **Definition of Done**. Cada incremento debe ser utilizable y cumplir calidad aunque la Review todavía no ocurra.

**Product Goal:** registros de acopio y calidad trazables, persistentes sin red y sincronizados con control de errores, con consulta útil para la planta y productores de Huata. Producción responde a la fuente empresarial y a la prioridad docente; alcance comercial y eventos requieren refinamiento.

El producto existente combina móvil multiplataforma y panel/API; la incertidumbre está en validar calidad, consistencia y valor real. Un equipo de cuatro puede inspeccionar avances semanales, ajustar alcance y limitar trabajo simultáneo. El costo de los eventos debe descontarse de la disponibilidad real.

## Comparación aplicada

| Criterio | Scrum | Kanban | XP |
|---|---|---|---|
| Organización | Objetivos y ciclos fijos; tres responsabilidades. | Flujo visual continuo; políticas explícitas. | Prácticas técnicas de calidad y feedback frecuente. |
| Para cuatro integrantes | Viable con responsabilidades claras y participación técnica acordada. | Sencillo para visualizar pocos trabajos activos. | Útil en revisión y pruebas; requiere disciplina técnica. |
| Cambios | Refinamiento; renegociar alcance sin poner en riesgo Goal; PO puede cancelar si obsoleto. | Reordenar pendientes y tirar trabajo cuando hay capacidad. | Feedback y cambios pequeños sustentados por pruebas. |
| Entregas | Incrementos Done dentro del sprint; Review inspecciona valor. | Flujo y tiempos visibles. | Integración frecuente de cambios pequeños. |
| Evidencia académica | Goals, backlogs, actas y resultados. | Capturas y registros de flujo. | PR/revisión y resultados reales de unitarias/integración. |
| Decisión | Marco del curso y del proyecto. | Columnas, límites WIP, bloqueo y medición del flujo. | Prácticas técnicas propuestas; no afirmar adopción integral. |

## Reglas operativas

- Sprint de una semana, jueves→jueves. El Sprint es uno de los cinco eventos.
- PO ordena por valor/riesgo/dependencias. Developers eligen cantidad y tareas según capacidad real.
- Planning explica por qué, qué y cómo. Daily inspecciona progreso hacia Goal; no es reporte al SM.
- Review inspecciona incremento y adapta Product Backlog. Retrospective inspecciona proceso y decide 1–3 mejoras.
- Los puntos son tamaño relativo de HU; horas son estimaciones de tareas. No se convierten con una tarifa fija.
- Una historia que cruza semanas conserva ID; no obtiene puntos terminados varias veces.
- Hecho exige DoD. Falta de evidencia no se interpreta como fallo de aplicación ni como aprobación.
- Kanban: HU como agrupación y tareas ejecutables como unidad WIP, con políticas de [WIP](../kanban/wip-limits.md). Los bloqueos siguen contando.
- El tablero remoto aún no se acredita. Se propone GitHub Projects como fuente operativa única al adoptarlo; estos Markdown respaldan el diseño.

## Evidencia histórica y representación académica

Los 22 commits prueban cambios versionados en siete fechas. No prueban Planning, Daily, Poker, Review ni aprobación del beneficiario. Se conserva la antigua segmentación de tres tramos en [antecedentes](../antecedentes/README.md).

S01–S04 son ventanas de **análisis retrospectivo** apoyado en Git, sin eventos Scrum acreditados. S05 es preparación documental; S06 en adelante son planes propuestos. Los dos sprints académicos simulados del TXT son una representación distinta, con fechas por confirmar; F5 OpenUP es referencia académica adicional, sin cambio automático de metodología. No se rellenan actas con asistentes ficticios. Delta es un ejercicio docente separado de las métricas del producto.

## Proceso de trabajo y revisión

La guía [Cómo trabajamos](../COMO-TRABAJAMOS.md) explica el recorrido necesidad→RF→HU→refinamiento→Planning→tarea→desarrollo→revisión/pruebas→incremento→Review→Retro. El [flujo Git](flujo-git-y-revision.md) conserva hechos y propuesta develop/main; no se duplican aquí sus instrucciones. [Calendario](calendario-y-capacidad.md) mantiene discrepancias y capacidad.
