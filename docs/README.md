# Documentación de Ecolactea Digital

[Inicio del repositorio](../README.md)

**Actualización:** 2026-10-22. **Scrum como marco principal + prácticas Kanban.** El proceso está preparado para adopción; los acuerdos del equipo y buena parte de su ejecución **no están acreditados**. Código presente no significa Done. Corte de evidencia histórica Git: 2026-10-19.

## Empezar aquí

1. [Cómo trabajamos](COMO-TRABAJAMOS.md): qué hace cada integrante antes, durante y después de una tarea.
2. [Proceso breve del proyecto](proceso-scrum-del-proyecto.md): síntesis del entregable docente.
3. [S06 candidato](scrum/sprint-6.md): primer plan de adopción, sujeto a capacidad/selección reales.
4. [Guía de mantenimiento](guia-mantenimiento-documentacion.md): documentar cada cambio en 13 pasos.
5. [Pruebas](pruebas/README.md): casos, ejecuciones y defectos separados, con resultados reales y límites por plataforma.

<a id="fuentes-principales"></a>
## Fuentes principales

| Información / pregunta | Fuente única | Vistas que enlazan o se derivan |
|---|---|---|
| Requisitos originales y controles RNF | [61 RF](requisitos/rf-fuente-y-cobertura.md), [17 RNF](requisitos/rnf-fuente-y-cobertura.md) | Trazabilidad y matriz; Charter aprobado pendiente. |
| Historias, CA, prioridad, SP y estado HU | [Product Backlog](scrum/product-backlog.md) | 41 historias funcionales/86 CA/82 Gherkin propuestos; padres conservados. |
| Responsabilidades | [Roles](scrum/roles.md) | Proceso/guía resumen; acta pendiente. |
| Tareas, responsable, horas y estado | [Registro de tareas](scrum/tareas.md) | 91 IDs: 19 aportes históricos y 72 propuestas; sprints enlazan. |
| Estado futuro en herramienta | [Política Kanban](kanban/tablero.md) | Project aún no confirmado; al adoptar, tareas será export fechado, no segundo estado editable. |
| Fechas, discrepancias y capacidad | [Calendario](scrum/calendario-y-capacidad.md) | Las fechas se reflejan en cada sprint; horas reales N/D. |
| Objetivo, alcance y selección de cada sprint | [S01…S10](#planificacion-y-calidad), un archivo por sprint | Cada sprint enlaza a HU/tareas; no copiar CA, responsables ni estados. |
| Evidencias | [Índice EV](evidencias/README.md) | [22 commits](scrum/evidencias-commits.md), pruebas/PR/eventos cuando existan. |
| Resultados de pruebas | [Ejecuciones](pruebas/ejecuciones/README.md) | Casos definen esperado; BUG conserva fallo; 26 intentos reales del 22-10, conservando fallos. |
| Arquitectura, contratos y decisiones | [Documento técnico](tecnica/arquitectura-datos-y-sincronizacion.md) | DT/OBS, enlaces al código y mantenimiento. |
| Eventos y formato | [Agenda y registros](scrum/eventos-y-registros.md) | [Plantilla común](plantillas/acta-evento-scrum.md), sin diez bloques repetidos. |

<a id="planificacion-y-calidad"></a>
## Planificación y calidad

[Metodología y comparación](scrum/metodologia.md) · [Definition of Done](scrum/definition-of-done.md) · [Flujo Git/PR](scrum/flujo-git-y-revision.md) · [Trazabilidad](scrum/trazabilidad.md) · [WIP](kanban/wip-limits.md) · [Métricas](scrum/metricas.md).

Histórico analítico: [S01](scrum/sprint-1.md), [S02](scrum/sprint-2.md), [S03](scrum/sprint-3.md), [S04](scrum/sprint-4.md). Preparación: [S05](scrum/sprint-5.md). Planes: [S06](scrum/sprint-6.md), [S07](scrum/sprint-7.md), [S08](scrum/sprint-8.md), [S09](scrum/sprint-9.md), [S10](scrum/sprint-10.md).

<a id="plantillas-minimas"></a>
## Plantillas mínimas

[Historia](plantillas/historia-usuario.md) · [Tarea](plantillas/tarea.md) · [Evento](plantillas/acta-evento-scrum.md) · [Caso](plantillas/caso-de-prueba.md) · [Ejecución](plantillas/ejecucion-prueba.md) · [Defecto](plantillas/reporte-defecto.md) · [Pull Request](../.github/PULL_REQUEST_TEMPLATE.md).

<a id="documentacion-tecnica-y-preparacion-de-pruebas"></a>
## Documentación técnica y preparación de pruebas

- [Arquitectura, API, datos y sincronización](tecnica/arquitectura-datos-y-sincronizacion.md): fuente técnica principal y límites por plataforma.
- Notas funcionales con versión: [administración móvil](tecnica/administracion-movil.md), [calidad móvil](tecnica/calidad-movil.md) y [portal del proveedor](tecnica/portal-proveedor-movil.md). Conservan descripciones útiles de versiones anteriores; no son requisitos ni resultados de pruebas nuevos.
- [Acceso y datos ficticios](pruebas/acceso-y-datos-de-prueba.md): preparar cuentas por perfil sin publicar secretos.
- Para ejecutar o repetir una comprobación, usar la [guía de pruebas](pruebas/README.md); para registrar el obtenido, una [nueva ejecución](plantillas/ejecucion-prueba.md). Los archivos de evidencia no se mueven ni se sobrescriben.

## Entrega académica y continuidad

[Fuentes docentes](academico/fuentes-docentes.md) · [Entregables y validación](academico/entregables-y-validacion.md) · [Matriz de cumplimiento](academico/matriz-cumplimiento.md) · [EP-01…15](academico/evidencias-pendientes.md) · [Delta y cuatro gráficos académicos](academico/ejercicio-delta.md) · [Inventario/publicación](academico/archivos-documentacion.md) · [Registro del cierre técnico del 22-10](auditoria-documentacion.md).

## Antecedentes, no proceso vigente

[Índice histórico](antecedentes/README.md) conserva tres tramos, dos borradores y respaldos previos. Consultar sus erratas/límites antes de usarlos. No corregir datos históricos para aparentar reuniones. Documentos vigentes anteriores a esta consolidación también quedaron respaldados.

Los [resultados del 22-10](pruebas/ejecuciones/resultados-2026-10-22.md) conservan pruebas reales, fallos y límites. El registro de cierre describe lo verificado en ese momento; no es una segunda fuente de estados operativos. La preparación Git posterior y esta reorganización no acreditan publicación, reuniones ni nuevas pruebas de la aplicación.

<a id="donde-mantener-cada-documento"></a>
## Dónde mantener cada documento

| Ubicación | Uso |
|---|---|
| `README.md` de la raíz | Presentación y entrada principal; enlaza a las guías. |
| `docs/README.md` | Índice y mapa de fuentes; no otro backlog. |
| `requisitos/`, `scrum/`, `kanban/` | Fuentes de requisitos, planificación/proceso y políticas del flujo. |
| `tecnica/` | Arquitectura y notas funcionales con versión y límites. |
| `pruebas/`, `evidencias/` | Procedimientos, resultados y registro de evidencias. |
| `academico/` | Fuentes del docente, ejercicio, entregables y cumplimiento académico. |
| `plantillas/` | Formatos reutilizables; un formato no acredita una actividad realizada. |
| `antecedentes/` y borradores enlazados | Prompts/notas históricas y tramos Git. No se usan como fuente vigente. |

Usar nombres descriptivos en minúsculas con guiones para nuevos documentos. Los nombres antiguos de los borradores congelados se conservan por trazabilidad. Reubicar solo cuando mejore la consulta, actualizar los enlaces afectados y seguir la [guía de mantenimiento](guia-mantenimiento-documentacion.md).

## Comprobación reproducible

~~~bash
python3 docs/herramientas/verificar_documentacion.py --export-kanban
~~~

Verifica estructura, enlaces/anclas, IDs, relaciones, commits, matriz y patrones sensibles; regenera [CSV](kanban/importacion-propuesta.csv) y [configuración](kanban/configuracion-propuesta.json) derivados para el tablero propuesto. No acredita reuniones, DoD o aceptación humana.

## Verificaciones ejecutables

[Herramientas y comandos reproducibles](herramientas/README.md) · [Nuevos recibos reales](pruebas/ejecuciones/automaticas/README.md) · [Cobertura y cierre técnico](pruebas/cobertura-y-cierre.md).
