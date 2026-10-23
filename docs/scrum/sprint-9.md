# S09 — 2026-11-12 → 2026-11-19

**PLAN EDITABLE / PENDIENTE DE VALIDACIÓN**, actualizado 2026-10-22. No es Planning celebrado ni compromiso real. Horario propuesto jueves 18:00→18:00, America/Lima; [calendario y discrepancias](calendario-y-capacidad.md).

## Sprint Goal y corte candidato

**Goal propuesto:** Conservar un lote de producción con litros, producto, unidades y consulta trazable.

**Candidatas principales:** [HU-16-A](product-backlog.md#hu-16-a).
**Opcionales:** [HU-16-B](product-backlog.md#hu-16-b), [HU-16-C](product-backlog.md#hu-16-c). Puntos por historia solo en el catálogo, hipotéticos y pendientes de Poker. **SP comprometidos: N/D**; no sumar padres ni tareas.

**Justificación:** previsión de valor del producto, a reordenar con el PO usando resultados del sprint anterior. Selección y alcance definitivos corresponden al Planning con disponibilidad real.

**Dependencias/entrada:** Permisos producción, origen de litros y unidades por producto acordados. Referencia de rendimiento no se asume 11–12 como umbral real; ventas/stock son cortes posteriores.

## Sprint Backlog candidato, fuente de tareas

No se copian sus estados, responsables u horas: cada ID enlaza al registro canónico.

[T-22](tareas.md#t-22) · [T-47](tareas.md#t-47) · [T-48](tareas.md#t-48) · [T-89](tareas.md#t-89) · [T-90](tareas.md#t-90) · [T-91](tareas.md#t-91).

**Trabajo opcional no incluido en el presupuesto base:** [T-30](tareas.md#t-30). Solo seleccionar si resulta necesario y cabe; si desplaza el Goal, renegociar alcance.

**Resumen derivado de tareas propuestas:** 7.5 h totales; Dubcek Leonel 5.5 h; Medina Paye 2 h. Son hipótesis iniciales, no disponibilidad ni esfuerzo realizado. La fuente es [tareas](tareas.md); recalcular este resumen si cambia el plan.

**Capacidad:** N/D; completar acta real con horas de cada integrante, eventos, obligaciones, reserva y revisor. [Tabla canónica](calendario-y-capacidad.md#capacidad-real-a-confirmar-en-cada-planning). No asumir que el ejemplo de 11.2 h del grupo cubre a cada persona. Las tareas de construcción, revisión, pruebas e integración ya están desglosadas; ratificar alcance, estimaciones y disponibilidad antes de comprometer. Si no cabe, reducir el corte sin omitir DoD.

## Incremento esperado, evidencia y riesgos

Registro/consulta de HU-16-A con DoD; comparación y destino solo si refinados, votados y caben.

**Protocolo propuesto:** Crear caso de lote válido y cero litros; rendimiento solo tras tabla autorizada. Ver [casos y ejecuciones](../pruebas/README.md).
**Evidencias a obtener:** CA/resultados/hash, PR aprobado por otro integrante, integración y despliegue a pruebas, documentación y controles RNF relevantes; [DoD](definition-of-done.md). Código anterior no sustituye nueva comprobación.

**Estado actual:** plan sin adopción confirmada; ninguna tarea ni historia se declara iniciada o Done por este archivo. Nueva implementación que resulte necesaria exige tarea/estimación, no queda implícitamente realizada.

## Eventos, Review y retrospectiva

Aplicar [agenda común](eventos-y-registros.md) y [plantilla real](../plantillas/acta-evento-scrum.md). Planning de apertura 2026-11-12; Review/Retro de cierre 2026-11-19, horarios en calendario. Daily reales se registran cuando ocurren, sin fabricar siete actas.

**Resultado Review:** EVIDENCIA PENDIENTE (fecha real, asistentes, versión, Goal, demostración, feedback y adaptación).
**Goal alcanzado / incremento Done:** PENDIENTE DE VALIDACIÓN, no resultado esperado marcado como logrado.
**Retrospectiva:** EVIDENCIA PENDIENTE; seguir cinco fases y revisar acciones anteriores. Una mejora candidata, no acordada:

| Formato | Acción propuesta | Responsable propuesto | Fecha propuesta posterior a Retro | Evidencia |
|---|---|---|---|---|
| Start | Usar la diferencia observada entre lote/cálculo/destino para elegir un corte pequeño del backlog. | Dubcek Leonel | 2026-11-20 | EVIDENCIA PENDIENTE |

Antes de elegirla, usar datos reales y conservar 1–3 acciones acordadas con dueño/fecha. Esta fila no acredita una reunión futura ni su aprobación.

**Entregables:** EA-03 plan/sprint; EA-04 evidencia técnica; EA-06 eventos/validación.
