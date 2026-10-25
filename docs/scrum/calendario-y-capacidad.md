# Calendario y capacidad — fuente principal

**Actualizado:** 2026-10-21. **Cadencia elegida:** Scrum semanal jueves→jueves de F2 d19 con Kanban. **Adopción operativa propuesta:** S06, 2026-10-22 18:00, America/Lima. Inicio, horarios y capacidad no ratificados; no se afirman eventos pasados.

## Fechas y naturaleza de cada intervalo

| ID | Intervalo jueves→jueves | Naturaleza y referencia |
|---|---|---|
| S01 | 2026-09-17→09-24 | [Ventana analítica histórica](sprint-1.md). |
| S02 | 2026-09-24→10-01 | [Ventana analítica histórica](sprint-2.md). |
| S03 | 2026-10-01→10-08 | [Ventana analítica histórica](sprint-3.md). |
| S04 | 2026-10-08→10-15 | [Ventana analítica histórica](sprint-4.md). |
| S05 | 2026-10-15→10-22 | [Preparación documental](sprint-5.md); sin adopción acreditada. |
| S06 | 2026-10-22→10-29 | [Primer sprint candidato](sprint-6.md), no compromiso confirmado. |
| S07 | 2026-10-29→11-05 | [Previsión calidad](sprint-7.md). |
| S08 | 2026-11-05→11-12 | [Previsión pagos/reportes](sprint-8.md). |
| S09 | 2026-11-12→11-19 | [Previsión producción](sprint-9.md). |
| S10 | 2026-11-19→11-26 | [Previsión validación/cierre](sprint-10.md). |

Los conteos Git usan ventanas **[jueves 00:00, jueves siguiente 00:00)** para análisis; no son el horario operativo propuesto de **18:00→18:00**. Los 13 commits del 24-09 son anteriores a 18:00: no trasladar los conteos 5/13/2/2 a supuestos sprints históricos de esa hora. El corte Git es 19-10; S05 contiene solo un tramo parcial de datos, no cero productividad.

Se conservan [tramo 1](../antecedentes/tramo-git-1.md) 14–27/09 (18 commits), [tramo 2](../antecedentes/tramo-git-2.md) 28/09–11/10 (2), [tramo 3](../antecedentes/tramo-git-3.md) 12–19/10 (2). Del 14–16/09 no hay commits seleccionados; el producto tenía historia anterior.

## Agenda común propuesta

| Evento | Día/hora America/Lima | Duración |
|---|---|---|
| Sprint | Jueves 18:00→jueves siguiente 18:00 | 1 semana |
| Planning | Jueves de apertura 18:00–20:00 | ~2 h |
| Daily | Lunes–viernes 08:00–08:15 | 15 min |
| Review | Jueves de cierre 16:00–17:00 | ~1 h |
| Retrospective | Jueves de cierre 17:00–17:45 | ~45 min |

Participantes, entradas y salidas: [eventos](eventos-y-registros.md). Refinamiento durante el sprint según necesidad; contabilizar su esfuerzo. Horarios deben compatibilizarse con clases y disponibilidad; no son citas confirmadas.

## Capacidad real a confirmar en cada Planning

| Integrante | Responsabilidad propuesta | Horas disponibles de la semana | Eventos / otras obligaciones / refinamiento | Horas técnicas netas | Confirmación |
|---|---|---|---|---|---|
| Sait Alfredo Gutierres Velasquez | PO; técnica solo si acuerda | N/D | N/D | N/D | EP-01 |
| Quispe Valencia Erick Maycol | SM; técnica solo si acuerda | N/D | N/D | N/D | EP-01 |
| Medina Paye Jhosue Daniel | Developer móvil | N/D | N/D | N/D | EP-01 |
| Dubcek Leonel | Developer API/datos | N/D | N/D | N/D | EP-01 |

Copiar la disponibilidad **real de la semana** al acta de Planning, enlazarla desde el sprint y ajustar tareas por persona, dependencias y revisor. N/D no es cero. PO/SM no añaden capacidad técnica automáticamente.

**Fórmula:** neto = disponibilidad − eventos − obligaciones no incluidas − refinamiento; reserva acordada; selección <= neto útil por persona. Registrar interrupciones, cambios y su efecto sin reescribir el compromiso inicial.

**EJERCICIO SIMULADO / REPRESENTACIÓN ACADÉMICA:** 2 Developers ×12 h brutas; por persona 2 h Planning +5×0.25 h Daily +1 h Review +0.75 h Retro =5 h. Quedan 7 h cada uno, 14 h grupo; reserva ilustrativa 20 % =2.8 h; útil 11.2 h grupo /5.6 h por persona. No es disponibilidad confirmada ni conversión de SP a horas. No incluye trabajo extra de refinamiento si lo hubiera.

## Discrepancias docentes y decisiones documentales

| ID | Fuentes y diferencia | Tratamiento actual | Decisión humana pendiente |
|---|---|---|---|
| CAL-01 | F1 d9–10/notas: cuatro quincenas desde 21-09 y cierre; F2 d19: semanas jueves→jueves. | Cadencia futura semanal elegida por usuario/F2; quincenas quedan como propuesta alternativa de F1. | Docente ratifica calendario aplicable al curso. |
| CAL-02 | F1 notas: evaluación 19-10; F2 d25: 22-10. | Mantener ambas; no simular entrega/aprobación el 19 ni afirmar evaluación ya realizada. | Confirmar fecha y acuse EP-03/13. |
| CAL-03 | F3 línea 5 solicita dos sprints simulados: inicio de clases→evaluación y primer día de la segunda unidad/periodo→evaluación. | Son **dos ejercicios académicos**, no estos diez intervalos ni tres tramos Git. Límites exactos PENDIENTES DE VALIDACIÓN. | Confirmar qué clase/unidad y fechas significa el docente. No crear actas ficticias. |
| CAL-04 | F1: hito/sustentación 23-11. F5 d12: entrega final 26-11, calendario referencial de OpenUP U3. | Horizonte visible hasta 23-11 y límite semanal S10 26-11; ahora se reconoce además el hito explícito referencial de F5. | Docente confirma 23/26 y si son entregas distintas. Ninguna es final definitiva aquí. |
| CAL-05 | F2 notas anuncia OpenUP 29-10; F5 se titula U3 aunque su nombre de archivo dice U2-S1. | **Scrum + Kanban se mantiene** por decisión del usuario. F5 se registra como fuente académica adicional, no adopción automática. | Confirmar si se exige artefacto U3 adicional y su correspondencia. |
| CAL-06 | F1 portada/agenda 21/28-09, F2 24-09/01-10 y ejemplos quincenales del TXT. | No atribuir fechas exactas de eventos reales ni reconciliar artificialmente. | Confirmación docente EP-03. |

Un periodo académico 14-09→19-10 dura más de un mes; no tratarlo como un Sprint real compatible con F2 por el mero nombre del ejercicio.

## Dos ejercicios académicos del TXT

| Representación | Límite descrito por F3, no fecha confirmada | Evidencia requerida |
|---|---|---|
| AC-01 | Inicio de clases → evaluación del primer periodo referido. | Delimitación docente y simulación rotulada, no actas históricas. |
| AC-02 | Primer día de clases del segundo periodo/unidad referido → evaluación correspondiente. | Misma distinción; no equiparar automáticamente con S06–10. |

El [caso Delta](../academico/ejercicio-delta.md) es otro ejercicio con sus propios datos. EP-03 conserva la consulta necesaria; ninguna confirmación se inventó.
