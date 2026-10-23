# Definition of Done — estándar del incremento

**Propuesta de adopción:** 2026-10-21. Fuente F2 d12/15 y F3 línea 5. Ratificación del equipo: EP-01. Estos controles son exigencias del proceso; **no constan como cumplidos** por escribir esta lista.

## Tres niveles diferentes

- **CA de HU:** comportamiento de negocio observable; vive en [backlog](product-backlog.md).
- **Salida de tarea:** trabajo técnico/documental concreto; vive en [tareas](tareas.md). Cerrar “preparar un protocolo” no valida el producto.
- **DoD global:** calidad mínima del incremento, aplicable junto a sus CA. No puede omitirse para sumar puntos o presentar Done.

## Checklist global por historia y versión

| ID | Control obligatorio | Evidencia necesaria | Estado real del conjunto auditado |
|---|---|---|---|
| DOD-01 | Implementación completa del corte y todos sus CA satisfechos. | CA → caso → ejecución con hash/plataforma/resultado. | EVIDENCIA PENDIENTE. |
| DOD-02 | Revisión por otro integrante, observaciones resueltas y PR aprobado. | URL, autor, revisor distinto, aprobación y 2–3 capturas saneadas según F3. | [PR #2](https://github.com/SaitAlfre/APP-MOVIL/pull/2) abierto y revisión solicitada a `dubcek-leonel`; aprobación independiente todavía EVIDENCIA PENDIENTE (EP-07). Merge histórico no prueba aprobación. |
| DOD-03 | Unitarias pertinentes satisfactorias y pruebas de aceptación del corte. | Reporte fechado con comando/selección, resumen/exit status, versión y entorno. | 22-10: JVM 259/259 ampliado (corte original256), PHP 289/289, Firestore 9/9. Aceptación completa por corte/dispositivo y reglas aún EP-08/12; no marcar toda DOD-03 cumplida. |
| DOD-04 | Integración en rama acordada sin errores. | Hash integrado, build/checks y resultado verificable; identificar destino. | Integración Ktor→API→SQLite real y builds web/preview Passed (22-10); integración del parche en rama destino/PR sigue EP-09. |
| DOD-05 | Despliegue en ambiente de pruebas y comprobación de versión. | Entorno autorizado, fecha/hash, resultado de verificación, sin secretos. | 22-10: copia web desplegada temporalmente en loopback, HTTP 200, CP-003 y cinco integraciones reales; versión/entorno en EXE-12/14/15. Cumple para ese ambiente local, no despliegue remoto. |
| DOD-06 | Documentación mínima vigente: uso/README o API, CA y cambio técnico. | Archivos relativos en el PR; límites Android/iOS y reglas claras. | Esta consolidación aporta documentos; revisión por incremento pendiente. |
| DOD-07 | Evidencias trazables y RNF relevantes comprobados. | Índice EV, ejecuciones y permisos/offline/integridad según el corte. | EP-08/12, sin certificación global. |

## Orden para cerrar

Autor termina trabajo y pruebas locales → PR a destino acordado → otro integrante revisa/aprueba → integrar y comprobar build → desplegar a pruebas y comprobar versión → completar CA/DoD → cerrar HU y registrar resultado para Review. Si se prueba un build de rama antes de integrar, registrar ambos hashes y repetir controles afectados; no exigir una autoaprobación para romper un bloqueo del flujo.

Developers responden colectivamente de la calidad; autor aporta evidencia y compañero la contrasta. PO confirma valor/alcance, sin sustituir revisión o tests. SM facilita seguimiento, sin ser aprobador universal.

## Faltantes, excepciones y bloqueos

Si falta un criterio obligatorio, **la HU no es Done ni suma velocidad**. Registrar control, motivo, responsable y próximo paso; mantener trabajo en su fase real y marcado bloqueado. Un control no ejecutado es Not Run; falta de entorno que impide intentarlo es Blocked cuando se registre la condición real.

Una excepción documenta quién decide, fecha, alcance, riesgo y alternativa; **no se convierte en cumplimiento completo** de la DoD docente. Para una tarea puramente documental, justificar unitarias “no aplica” por su naturaleza; eso no permite cerrar el incremento funcional asociado sin pruebas. No debilitar DoD durante un sprint.

No hay historias con Done acreditado al 22-10; esto no afirma ausencia de funcionalidad implementada.

**Evidencia técnica vigente:** [resultados reales del 22-10](../pruebas/ejecuciones/resultados-2026-10-22.md). Un control puede cumplirse para esa versión/entorno sin cerrar todos los demás. No se omite PR aprobado ni se usa el agente como revisor independiente de su propio cambio.

**Regresiones ampliadas:** [cobertura/checklists](../pruebas/cobertura-y-cierre.md), [recibos posteriores](../pruebas/ejecuciones/automaticas/README.md). No se omite revisión independiente ni se transforma el agente en aprobador de su propio trabajo.

**Corte posterior a la publicación, 23-10:** cuatro commits publicados y CI documental remoto satisfactorio; no se integraron todavía en develop/main ni se recibió aprobación independiente. Siguen **0 de 41 historias certificadas Done**. El Project operativo no sustituye aceptación, revisión ni validación del beneficiario.
