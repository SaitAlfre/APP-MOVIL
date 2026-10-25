# S06 — 2026-10-22 → 2026-10-29

**PLAN EDITABLE / PENDIENTE DE VALIDACIÓN**, actualizado 2026-10-22. No es Planning celebrado ni compromiso real. Horario propuesto jueves 18:00→18:00, America/Lima; [calendario y discrepancias](calendario-y-capacidad.md).

## Sprint Goal y corte candidato

**Goal propuesto:** Que el acopiador pueda distinguir recepción confirmada de una entrega pendiente y reintentar su UUID sin duplicar litros en el destino oficial.

**Candidatas principales:** [HU-04-A](product-backlog.md#hu-04-a).
**Opcionales:** [HU-13](product-backlog.md#hu-13). Puntos por historia solo en el catálogo, hipotéticos y pendientes de Poker. **SP comprometidos: N/D**; no sumar padres ni tareas.

**Justificación:** reducir riesgo de pérdidas/duplicados en el flujo central existente antes de ampliar mapas y módulos. Selección y alcance definitivos corresponden al Planning con disponibilidad real.

**Dependencias/entrada:** HU-01/02/03-A/09 como precondiciones de sesión y datos; código existente no prueba su DoD. Primero comprobar esas precondiciones. Definir destino oficial/respuesta y mensaje de confirmación parcial Android. iOS remoto no entra sin configuración/validación.

## Sprint Backlog candidato, fuente de tareas

No se copian sus estados, responsables u horas: cada ID enlaza al registro canónico.

[T-20](tareas.md#t-20) · [T-24](tareas.md#t-24) · [T-41](tareas.md#t-41) · [T-51](tareas.md#t-51) · [T-52](tareas.md#t-52) · [T-53](tareas.md#t-53) · [T-54](tareas.md#t-54) · [T-55](tareas.md#t-55) · [T-56](tareas.md#t-56).

**Trabajo opcional no incluido en el presupuesto base:** [T-25](tareas.md#t-25), [T-42](tareas.md#t-42). Solo seleccionar si resulta necesario y cabe; si desplaza el Goal, renegociar alcance.

**Resumen derivado de tareas propuestas:** 14 h totales; Sait Alfredo 2 h; Medina Paye 5 h; Dubcek Leonel 5 h; Quispe Valencia 2 h. Son hipótesis iniciales, no disponibilidad ni esfuerzo realizado. La fuente es [tareas](tareas.md); recalcular este resumen si cambia el plan.

**Capacidad:** N/D; completar acta real con horas de cada integrante, eventos, obligaciones, reserva y revisor. [Tabla canónica](calendario-y-capacidad.md#capacidad-real-a-confirmar-en-cada-planning). No asumir que el ejemplo de 11.2 h del grupo cubre a cada persona. La propuesta técnica asigna 5 h a cada Developer tras reducir T-52 a 1 h de repetición y registro, pues ya hay procedimiento ejecutado. Cabe solo en el ejemplo ilustrativo de 5.6 h/persona; la capacidad real sigue N/D. Las 4 h de coordinación/documentación de PO y SM también requieren disponibilidad separada. No se declara compromiso real ni conversión SP→horas.

## Incremento esperado, evidencia y riesgos

HU-04-A completa: estado visible, intento automático/manual, misma identidad y conservación ante falta de respuesta, con DoD. Si no cabe, dividir el corte con el PO antes de seleccionar; no afirmar que probar un caso entrega toda la historia.

**Protocolo propuesto:** CP-001 como precondición y CP-002/007 para confirmación/reintento; negativos de sesión si bloquean el corte. Ver [casos y ejecuciones](../pruebas/README.md).
**Evidencias a obtener:** CA/resultados/hash, PR aprobado por otro integrante, integración y despliegue a pruebas, documentación y controles RNF relevantes; [DoD](definition-of-done.md). Código anterior no sustituye nueva comprobación.

**Estado actual:** plan sin adopción confirmada; ninguna tarea ni historia se declara iniciada o Done por este archivo. Nueva implementación que resulte necesaria exige tarea/estimación, no queda implícitamente realizada.

## Eventos, Review y retrospectiva

Aplicar [agenda común](eventos-y-registros.md) y [plantilla real](../plantillas/acta-evento-scrum.md). Planning de apertura 2026-10-22; Review/Retro de cierre 2026-10-29, horarios en calendario. Daily reales se registran cuando ocurren, sin fabricar siete actas.

**Resultado Review:** EVIDENCIA PENDIENTE (fecha real, asistentes, versión, Goal, demostración, feedback y adaptación).
**Goal alcanzado / incremento Done:** PENDIENTE DE VALIDACIÓN, no resultado esperado marcado como logrado.
**Retrospectiva:** EVIDENCIA PENDIENTE; seguir cinco fases y revisar acciones anteriores. Una mejora candidata, no acordada:

| Formato | Acción propuesta | Responsable propuesto | Fecha propuesta posterior a Retro | Evidencia |
|---|---|---|---|---|
| Start | Revisar si el límite global de dos tareas activas evitó acumulación; usar movimientos reales, sin estimar cycle time desde commits. | Quispe Valencia Erick Maycol | 2026-10-30 | EVIDENCIA PENDIENTE |

Antes de elegirla, usar datos reales y conservar 1–3 acciones acordadas con dueño/fecha. Esta fila no acredita una reunión futura ni su aprobación.

**Entregables:** EA-03 plan/sprint; EA-04 evidencia técnica; EA-06 eventos/validación.

## Insumo real de preparación, 2026-10-22

Se ejecutaron pruebas JVM, API/PHP y Firebase, build web y APK localPreview; [resultados y límites](../pruebas/ejecuciones/resultados-2026-10-22.md). No son un Planning ni aceptación de HU-04-A. Android 16 emulado también comprobó arranque, login offline, persistencia y reconexión con pérdida de respuesta (EXE-21/22/25/26). Son datos ficticios; no hardware de campo, iOS ni aceptación del beneficiario.
