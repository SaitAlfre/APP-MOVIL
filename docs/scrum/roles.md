# Equipo, responsabilidades y autores Git

[Índice](../README.md) · [Metodología](metodologia.md)

## Integrantes oficiales y asignación propuesta

| Integrante | Responsabilidad Scrum | Funciones y evidencia esperada |
|---|---|---|
| Sait Alfredo Gutierres Velasquez | Product Owner — [Asignación propuesta] | Visión, valor y orden del backlog; aclarar criterios; recoger validación del beneficiario. Evidencia: Product Goal, decisiones y observaciones con fecha real. |
| Quispe Valencia Erick Maycol | Scrum Master — [Asignación propuesta] | Facilitar aprendizaje de Scrum, eventos útiles y remoción de impedimentos. Evidencia: acciones de desbloqueo y acuerdos de mejora; no dirige como jefe ni asigna trabajo unilateralmente. |
| Medina Paye Jhosue Daniel | Developer — [Asignación propuesta] | Referente sugerido móvil/UI, acopio, cliente de sincronización y verificación en dispositivo; también revisa, documenta e integra. Evidencia: cambios/PR y resultados realmente obtenidos. |
| Dubcek Leonel | Developer — [Asignación propuesta] | Referente sugerido API/datos, calidad, reportes y revisión técnica; también apoya móvil, pruebas y documentación. Evidencia: código revisado, integración y resultados auténticos. |

Los dos referentes técnicos se proponen para distribuir la verificación futura, sin limitar colaboración ni atribuir responsabilidad histórica. PO y SM pueden contribuir técnicamente como Developers si el equipo acuerda disponibilidad y responsabilidades; su título no añade automáticamente capacidad al WIP.

## Cuentas GitHub y accesos

**Cuentas identificadas explícitamente por el propietario el 23-10-2026 y verificadas en GitHub.** Esta confirmación identifica a los integrantes; la ratificación de responsabilidades Scrum de la sección anterior sigue pendiente (EP-01).

| Integrante | Cuenta GitHub | Repositorio APP-MOVIL | Project privado |
|---|---|---|---|
| Sait Alfredo Gutierres Velasquez | [SaitAlfre](https://github.com/SaitAlfre) | Propietario; administración y escritura verificadas. | Propietario. |
| Quispe Valencia Erick Maycol | [Valencia-19](https://github.com/Valencia-19) | Colaborador con escritura verificada. | Write verificado. |
| Medina Paye Jhosue Daniel | [jhosuemedina03](https://github.com/jhosuemedina03) | Invitación de colaboración enviada; **ACEPTACIÓN PENDIENTE**. Todavía no acredita escritura. | Write concedido y verificado en Manage access. |
| Dubcek Leonel | [dubcek-leonel](https://github.com/dubcek-leonel) | Colaborador con escritura verificada. | Write verificado. |

[Project del equipo](https://github.com/users/SaitAlfre/projects/1/views/2) · [Aceptar invitación al repositorio](https://github.com/SaitAlfre/APP-MOVIL/invitations).

La aceptación solo puede realizarla `jhosuemedina03` con su cuenta. La consulta posterior debe acreditar que figura como colaborador con escritura antes de dar por cerrado ese paso. No se duplicaron invitaciones. La [instantánea de recursos y actualización fechada de accesos](../kanban/project-operativo.json) conserva el corte anterior. La revisión del [PR #2](https://github.com/SaitAlfre/APP-MOVIL/pull/2) sigue solicitada a `dubcek-leonel`, sin aprobación registrada.

## Colaboración durante un sprint

Los cuatro colaboran para alcanzar el Sprint Goal. PO aporta valor y prioridades; Developers deciden cómo entregar y adaptan el plan; Scrum Master ayuda a que el proceso y los impedimentos sean visibles. La calidad y la creación de un incremento utilizable corresponden colectivamente a Developers. El feedback del beneficiario se registra cuando ocurre, sin presentarlo como un nombramiento del PO o aprobación histórica.

| Actividad | Responsabilidad principal propuesta | Participación compartida / evidencia |
|---|---|---|
| Requerimientos y relación con beneficiario | Sait Alfredo Gutierres Velasquez | Todos aclaran dudas; acta auténtica y requerimiento rastreable. |
| Priorización | Sait Alfredo Gutierres Velasquez | Developers aportan riesgo, dependencias y tamaño. |
| Planificación | Scrum Team; selección y plan de Developers | SM facilita; PO explica valor; todos confirman disponibilidad real. |
| Desarrollo | Medina Paye Jhosue Daniel y Dubcek Leonel | Apoyo técnico de PO/SM solo si se acuerda; tareas y cambios reales. |
| Integración | Developers | Autor y compañero revisan; evidencia de versión integrada y resultado. |
| Verificación | Developers | PO ayuda a contrastar valor; no sustituye pruebas técnicas por aceptación informal. |
| Documentación | Todos según su trabajo | PO cuida requerimientos, SM proceso y Developers arquitectura/resultados. |
| Revisión de incremento | Scrum Team y beneficiario disponible | Registrar criterios observados y cambios al backlog. |
| Impedimentos | Developers los hacen visibles; SM ayuda a removerlos | Responsable concreto acordado por caso y fecha real de seguimiento. |
| Mejora continua | Scrum Team | Retrospectiva con acción verificable, no declaraciones reconstruidas. |

## Correspondencias con los metadatos Git

| Nombre oficial | Nombre observado en Git | Evidencia y límite |
|---|---|---|
| Sait Alfredo Gutierres Velasquez | Sait Alfredo Gutierrez Velasquez | Coincidencia de nombre con diferencia Gutierres/Gutierrez. Se conserva la ortografía oficial proporcionada; identidad y forma definitiva pendientes de ratificación. |
| Quispe Valencia Erick Maycol | Erick Maycol Quispe Valencia | Mismos componentes del nombre en otro orden; correspondencia propuesta, no nombramiento histórico de SM. |
| Medina Paye Jhosue Daniel | No se identificó una firma atribuible de forma suficiente | No atribuir commits ajenos ni concluir ausencia de colaboración. El equipo puede aportar evidencia propia. |
| Dubcek Leonel | Dubcek Leonel; también aparece AWA Dubcek | El merge af6ed93 usa AWA Dubcek. Ambos nombres comparten el mismo correo de autor en los metadatos examinados; es evidencia de correspondencia de configuración, no prueba independiente de identidad personal. |

Se observaron tres correos de autor distintos y cuatro nombres visibles en el periodo. No se publican direcciones personales. La tabla de [commits](evidencias-commits.md) conserva literalmente cada nombre Git. Firmar un commit no demuestra haber sido el único implementador, el responsable completo de la historia, un asistente a una reunión ni un revisor.

## Participación y registro

En Planning participa el Scrum Team. El Daily pertenece a Developers; SM facilita si hace falta y PO aporta aclaraciones sin convertirlo en reporte al jefe. Review incluye actores disponibles y trabajo realmente inspeccionado. Retrospective incluye al Scrum Team y deja una acción concreta. La presencia de cuatro nombres en esta documentación identifica al equipo oficial, no constituye lista de asistencia de eventos anteriores.

## Ratificación requerida por la guía docente

F2 d10 pide asignar responsabilidades y registrarlas en acta. La tabla anterior es **PENDIENTE DE VALIDACIÓN**, no un nombramiento histórico. Crear el acta solo cuando se acuerde, con fecha real, forma de aprobación, disponibilidad y obligaciones de los cuatro integrantes (EP-01).

Los roles de usuarios del producto —administrador, acopiador, responsable de calidad y productor como perfiles candidatos— se distinguen de PO, SM y Developers. F4 describe más responsabilidades de negocio; su consolidación con los perfiles del código **PENDIENTE DE VALIDACIÓN**. No se afirma equivalencia de permisos sin comprobarla.

[Eventos y campos de actas](eventos-y-registros.md) · [Capacidad y horarios](calendario-y-capacidad.md).

## Actualización distribuida de evidencias

Cada autor mantiene su tarea/tarjeta y documentación del cambio; cada ejecutante registra su EXE real; otro integrante revisa. Developers verifican CA y DoD del incremento; PO ordena y recoge feedback; SM facilita sin asignar unilateralmente ni convertirse en documentador único. [Guía práctica](../COMO-TRABAJAMOS.md) y [mantenimiento](../guia-mantenimiento-documentacion.md).
