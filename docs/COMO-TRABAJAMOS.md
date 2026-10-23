# Cómo trabajamos los cuatro integrantes

[Índice](README.md) · [Registrar pruebas](pruebas/README.md) · [Actualizar documentación](guia-mantenimiento-documentacion.md)

**Vigente como proceso propuesto desde 2026-10-21.** Scrum organiza el trabajo; Kanban permite ver su flujo. Ratificación del equipo, horarios, capacidad y aceptación de invitación al repositorio: [EP-01/03/11](academico/evidencias-pendientes.md). No acredita aplicación histórica.

## Fuentes que debemos consultar

| Pregunta | Fuente principal |
|---|---|
| ¿Qué necesita el beneficiario? | [61 RF](requisitos/rf-fuente-y-cobertura.md), [17 RNF](requisitos/rnf-fuente-y-cobertura.md); Charter pendiente. |
| ¿Qué resultado entregaremos? | [Product Backlog: historias y CA](scrum/product-backlog.md). |
| ¿Quién hace qué y cuál es su estado? | Alcance/horas propuestas en [tareas](scrum/tareas.md); estado operativo en [Project privado](https://github.com/users/SaitAlfre/projects/1/views/2). |
| ¿Qué semana y horas se acordaron? | [Calendario/capacidad](scrum/calendario-y-capacidad.md); [S06 candidato](scrum/sprint-6.md). |
| ¿Qué responsabilidad tiene cada uno? | [Roles](scrum/roles.md). |
| ¿Cómo comprobamos y cerramos? | [Pruebas](pruebas/README.md), [DoD](scrum/definition-of-done.md), [evidencias](evidencias/README.md). |
| ¿Qué cambió técnicamente? | [Arquitectura y decisiones](tecnica/arquitectura-datos-y-sincronizacion.md), [mantenimiento](guia-mantenimiento-documentacion.md). |

## De la necesidad al incremento

| Etapa | Ejemplo aplicado y salida |
|---|---|
| Necesidad | La planta necesita distinguir leche anotada de leche recibida digitalmente. PO recoge contexto del beneficiario con fecha real. |
| RF/RNF | Localizar RF-SIN-01/02/03 y RNF-02; no inventar un nuevo requisito si ya existe. |
| Historia | HU-04-A expresa el resultado para el acopiador, con valor y CA observables. |
| Refinamiento | Developers y PO aclaran UUID, respuesta perdida y destino oficial; dividir lo que no pueda demostrarse en una semana. |
| Priorización | PO ordena este riesgo frente a mapas/avisos, con dependencias explícitas y MoSCoW propuesto. |
| Sprint Planning | Equipo confirma horas; Developers seleccionan el corte y acuerdan Goal/plan, sin usar puntos hipotéticos como capacidad. |
| Tareas | Registrar preparación, construcción/corrección, pruebas y revisión necesarias con responsable y estimación, no solo escribir la HU. |
| Desarrollo | Autor realiza cambios en la rama acordada y documenta contrato/estados afectados. |
| Revisión | Otro integrante inspecciona PR y coherencia; autor corrige lo observado. |
| Pruebas | Ejecutante conserva EXE de CP-002/007, versión y obtenido; si falla, BUG y nueva regresión después de corregir. |
| Incremento | Developers comprueban CA y DoD, incluida integración/despliegue; solo entonces HU Done. |
| Sprint Review | Inspeccionar el corte utilizable y registrar feedback real; PO adapta backlog. |
| Retrospectiva | Usar datos del flujo y decidir 1–3 mejoras con responsable/fecha; revisar su cumplimiento la siguiente vez. |

Estas etapas pueden iterarse: pruebas y revisión acompañan el desarrollo; no son fases rígidas de cascada al final.

## Antes de empezar

1. Leer el Goal del sprint. Si todavía es propuesta, confirmar selección y disponibilidad en Planning; una fecha actual no implica sprint adoptado.
2. Consultar el orden del PO y la historia/CA. Ejemplo: HU-04-A busca confirmar recepción sin duplicar litros.
3. Verificar dependencias, datos, permisos, entorno y plataforma. No asumir URL configurada en iOS ni primer login offline.
4. Developers eligen/desglosan tareas, acuerdan responsable y revisor; SM facilita. No tomar una tarea solo por su urgencia de auditoría.
5. Registrar tarea estable, vínculo HU/CA/RNF, horas y sprint. Pasar a To Do solo tras selección; a In Progress al comenzar realmente y si hay WIP.
6. Crear rama cuando el equipo ejecute el [flujo Git](scrum/flujo-git-y-revision.md). El 23-10 se publicaron `develop` y la rama de cierre; el [PR #2](https://github.com/SaitAlfre/APP-MOVIL/pull/2) está abierto hacia develop, con revisión solicitada a `dubcek-leonel`. No integrar sin aprobación real.

## Durante el trabajo

- Quien realiza la tarea actualiza estado, bloqueo y evidencia; cada integrante documenta su propio cambio.
- En Daily, Developers inspeccionan Goal, flujo y obstáculos; no es reporte al SM. Resolver detalles aparte.
- Si aparece un requisito nuevo, PO ordena el backlog; Developers adaptan el plan sin comprometer Goal ni bajar DoD. Registrar cambio de alcance.
- Vincular commits reales por hash y PR, sin retrofechar. Cambios API/datos/sync requieren actualizar contratos y casos afectados.
- Si falta una regla, dato o entorno, marcar bloqueo con motivo, responsable de ayuda y próximo seguimiento. La tarjeta cuenta en WIP.

## Al terminar una tarea

1. Comprobar su salida concreta; una preparación documental no equivale a ejecutar su protocolo.
2. Cuando el equipo ejecute pruebas, conservar caso y ejecución separados, con versión, entorno y resultado real, incluso Failed/Blocked.
3. Registrar evidencia saneada; no subir tokens, usuarios reales, DNI ni contactos de productores.
4. Preparar PR con [plantilla](../.github/PULL_REQUEST_TEMPLATE.md), enlazar HU/tarea/RF/RNF y solicitar revisión de otro integrante.
5. Pasar a Review solo con trabajo disponible para inspección. Si hay correcciones, volver a In Progress conservando historial.
6. La HU solo pasa a Done al cumplir todos los CA y la [DoD](scrum/definition-of-done.md). Código integrado, capturas o aprobación del PO por sí solos no bastan.

## Durante cada semana

- **Planning (~2 h):** PO explica valor; equipo confirma horas; Developers seleccionan historias, fijan plan y dependencias; registrar Goal y selección real.
- **Daily (15 min):** adaptar el plan hacia Goal; guardar solo acuerdos/bloqueos útiles con participantes reales.
- **Review (~1 h):** inspeccionar incremento utilizable con evidencia y feedback. Mostrar brechas como brechas; adaptar backlog.
- **Retro (~45 min):** preparar, recopilar, analizar, decidir 1–3 acciones con responsable/fecha y cerrar. Revisar acciones anteriores.
- Detalle de entradas/salidas y plantilla común en [eventos](scrum/eventos-y-registros.md). No copiar diez agendas vacías en sprints.

## Ejemplo completo: de historia a funcionalidad terminada

**EJEMPLO OPERATIVO PROPUESTO; no ocurrió como secuencia comprobada.**

RF-SIN-01/02/03 → HU-04-A → acordar CA y política de destinos → Poker y capacidad → selección en S06 si cabe → T-24/41/51/52/53/55 → To Do → al comenzar, In Progress → desarrollar/corregir → ejecutar CP-002/007 en versión identificada → registrar EXE real y BUG si falla → PR/revisión por compañero → integración y despliegue a pruebas → comprobar CA y DoD → Done → Review y feedback → Retro.

**Lo real disponible al 22-10:** archivos/commits de [T-04/18](scrum/tareas.md), suites automatizadas completas e integración con servidor local, builds y [despliegue local verificado](pruebas/ejecuciones/resultados-2026-10-22.md). **Lo pendiente:** selección y acuerdos del equipo, aprobación de PR, CA que requieren dispositivo/regla ratificada y aceptación del beneficiario. Si falta cualquiera, conservar estado y pendiente; no completar este ejemplo con resultados ficticios.

## Antes de proponer un PR

Ejecutar `python3 docs/herramientas/verificar_documentacion.py --export-kanban`, revisar su salida y los cambios derivados; seguir los [comandos de pruebas](pruebas/README.md). Si un control falla, corregir y conservar ambos resultados. Los resultados del 22-10 son referencia, no sustituyen volver a probar los cambios futuros.

## Verificación automática disponible

Usar los [comandos reproducibles](herramientas/README.md) antes del PR. Las [ejecuciones nuevas](pruebas/ejecuciones/automaticas/README.md) actualizan el índice desde recibos reales; no actualizan Done, votos o reuniones. Consultar el [cierre técnico](pruebas/cobertura-y-cierre.md) para distinguir controles comprobados de aceptación pendiente.

## Tablero operativo desde el 23-10-2026

- Abrir el [Project privado](https://github.com/users/SaitAlfre/projects/1/views/2). Los cuatro tienen acceso al Project según la [tabla canónica de cuentas](scrum/roles.md#cuentas-github-y-accesos). `jhosuemedina03` debe aceptar su invitación al repositorio para disponer de escritura allí.
- Buscar el ID de la historia o tarea. Hay 41 historias y 72 tareas nuevas, con enlaces a su fuente principal y relaciones padre/subtarea cuando existe una HU operativa correspondiente. Los 19 aportes históricos no se recrearon como trabajo nuevo.
- Estado inicial real: Backlog. No mover a To Do hasta Planning; no asignar Sprint candidato como compromiso confirmado. Los responsables escritos son propuestas, no asignaciones GitHub ratificadas.
- Registrar cambios de estado, bloqueos y enlaces a EXE/PR en el Project; actualizar los criterios/alcance solo en su documento canónico. La [instantánea](kanban/project-operativo.json) acredita la puesta en marcha, no reemplaza el estado actual.
- El [PR #2](https://github.com/SaitAlfre/APP-MOVIL/pull/2) tiene CI satisfactorio; su aprobación independiente, la integración y los CA completos siguen pendientes. Ninguna HU está certificada Done.
