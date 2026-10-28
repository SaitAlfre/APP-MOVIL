# Contexto del proyecto

Este repositorio contiene Ecolactea Digital / EcolectaHuata. Para orientarse, leer `graphify-out/PROJECT_CONTEXT.md` y `graphify-out/GRAPH_REPORT.md`; el alcance y la revision de la instantanea estan en `graphify-out/scope.json`.

El grafo inicial cubre documentacion vigente y entradas principales de arquitectura; no representa todo el codigo. Para preguntas del proyecto, consultar primero el grafo con `graphify query`, `explain` o `path` y despues verificar las fuentes pertinentes en el estado actual. Si el grafo no cubre una pieza, inspeccionar esa pieza directamente.

Conservar la distincion entre implementacion, pruebas locales, propuestas, aceptacion externa y Definition of Done. Priorizar fuentes recientes y explicitar el corte de documentos historicos. No convertir las propuestas del backlog en acuerdos o resultados ejecutados.

Antes de modificar `web/`, leer `web/AGENTS.md` y sus reglas aplicables. Para trabajar un cambio funcional, consultar la historia/criterios en `docs/scrum/product-backlog.md`, el contrato tecnico y las pruebas del area.

Al actualizar el grafo, preservar o declarar su alcance documental. Una extraccion completa de codigo amplia esta instantanea y debe identificarse como tal; `graphify update .` no reemplaza la lectura semantica de documentacion modificada.
