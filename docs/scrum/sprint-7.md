# S07 — 2026-10-29 → 2026-11-05

**PLAN EDITABLE / PENDIENTE DE VALIDACIÓN**, actualizado 2026-10-22. No es Planning celebrado ni compromiso real. Horario propuesto jueves 18:00→18:00, America/Lima; [calendario y discrepancias](calendario-y-capacidad.md).

## Sprint Goal y corte candidato

**Goal propuesto:** Conservar un análisis de calidad identificable por productor/muestra y consultable en su origen acordado.

**Candidatas principales:** [HU-05-A](product-backlog.md#hu-05-a).
**Opcionales:** [HU-05-B](product-backlog.md#hu-05-b), [HU-06](product-backlog.md#hu-06). Puntos por historia solo en el catálogo, hipotéticos y pendientes de Poker. **SP comprometidos: N/D**; no sumar padres ni tareas.

**Justificación:** previsión de valor del producto, a reordenar con el PO usando resultados del sprint anterior. Selección y alcance definitivos corresponden al Planning con disponibilidad real.

**Dependencias/entrada:** Sesión/permisos, productor, unidades/campos y datos autorizados. Evaluación automática depende de tabla de parámetros ratificada; OCR es opcional Android y no se incluye implícitamente.

## Sprint Backlog candidato, fuente de tareas

No se copian sus estados, responsables u horas: cada ID enlaza al registro canónico.

[T-43](tareas.md#t-43) · [T-83](tareas.md#t-83) · [T-84](tareas.md#t-84) · [T-85](tareas.md#t-85).

**Trabajo opcional no incluido en el presupuesto base:** [T-26](tareas.md#t-26), [T-27](tareas.md#t-27), [T-44](tareas.md#t-44). Solo seleccionar si resulta necesario y cabe; si desplaza el Goal, renegociar alcance.

**Resumen derivado de tareas propuestas:** 6 h totales; Dubcek Leonel 5 h; Medina Paye 1 h. Son hipótesis iniciales, no disponibilidad ni esfuerzo realizado. La fuente es [tareas](tareas.md); recalcular este resumen si cambia el plan.

**Capacidad:** N/D; completar acta real con horas de cada integrante, eventos, obligaciones, reserva y revisor. [Tabla canónica](calendario-y-capacidad.md#capacidad-real-a-confirmar-en-cada-planning). No asumir que el ejemplo de 11.2 h del grupo cubre a cada persona. Las tareas de construcción, revisión, pruebas e integración ya están desglosadas; ratificar alcance, estimaciones y disponibilidad antes de comprometer. Si no cabe, reducir el corte sin omitir DoD.

## Incremento esperado, evidencia y riesgos

Análisis guardado con campos y trazabilidad de HU-05-A; solo declarar incremento tras CA y DoD. El backlog candidato incluye implementación, revisión e integración/despliegue local; su ejecución no está acreditada.

**Protocolo propuesto:** CP-004; ampliar a persistencia sin red y origen lote/directo según CT-RNF-01/17. Ver [casos y ejecuciones](../pruebas/README.md).
**Evidencias a obtener:** CA/resultados/hash, PR aprobado por otro integrante, integración y despliegue a pruebas, documentación y controles RNF relevantes; [DoD](definition-of-done.md). Código anterior no sustituye nueva comprobación.

**Estado actual:** plan sin adopción confirmada; ninguna tarea ni historia se declara iniciada o Done por este archivo. Nueva implementación que resulte necesaria exige tarea/estimación, no queda implícitamente realizada.

## Eventos, Review y retrospectiva

Aplicar [agenda común](eventos-y-registros.md) y [plantilla real](../plantillas/acta-evento-scrum.md). Planning de apertura 2026-10-29; Review/Retro de cierre 2026-11-05, horarios en calendario. Daily reales se registran cuando ocurren, sin fabricar siete actas.

**Resultado Review:** EVIDENCIA PENDIENTE (fecha real, asistentes, versión, Goal, demostración, feedback y adaptación).
**Goal alcanzado / incremento Done:** PENDIENTE DE VALIDACIÓN, no resultado esperado marcado como logrado.
**Retrospectiva:** EVIDENCIA PENDIENTE; seguir cinco fases y revisar acciones anteriores. Una mejora candidata, no acordada:

| Formato | Acción propuesta | Responsable propuesto | Fecha propuesta posterior a Retro | Evidencia |
|---|---|---|---|---|
| Start | Revisar errores reales de captura y ajustar un campo/validación prioritaria, después de tener observaciones. | Medina Paye Jhosue Daniel | 2026-11-06 | EVIDENCIA PENDIENTE |

Antes de elegirla, usar datos reales y conservar 1–3 acciones acordadas con dueño/fecha. Esta fila no acredita una reunión futura ni su aprobación.

**Entregables:** EA-03 plan/sprint; EA-04 evidencia técnica; EA-06 eventos/validación.
