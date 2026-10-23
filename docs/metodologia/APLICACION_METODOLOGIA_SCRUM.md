# Aplicación de la metodología Scrum en el desarrollo del proyecto APP MOVIL

> **Borrador anterior conservado:** el paquete vigente se encuentra en [docs/README.md](../README.md). Este archivo proponía un Sprint 1 para el 19–26 de octubre y usa identificadores anteriores; se conserva como antecedente, fuera del catálogo vigente HU-01 a HU-15. Sus propuestas no acreditan tareas o reuniones históricas. La planificación siguiente debe volver a acordarse con el equipo.

**Versión:** borrador de implementación y documentación, 19 de octubre de 2026  
**Fecha límite:** lunes 26 de octubre de 2026  
**Estado:** plan propuesto; las ceremonias y evidencias indicadas como futuras aún deben realizarse. No se presentan reuniones, pruebas, commits ni PRs planificados como si ya ocurrieran.

> **Metodología principal:** Scrum. El tablero de columnas es solo una herramienta para visualizar el Sprint Backlog; no se propone Kanban como metodología.

## 1. Datos generales y análisis del repositorio

### 1.1 Datos generales

| Campo | Información |
|---|---|
| Nombre del proyecto | APP MOVIL / Ecolecta Huata |
| Integrantes | Equipo de 4; completar con los nombres reales. |
| Repositorio | https://github.com/SaitAlfre/APP-MOVIL |
| Objetivo del sistema | Apoyar el acopio y la operación de leche mediante una app móvil y un panel web para proveedores, acopiadores, técnicos de calidad y administración. |
| Tipo de producto | Aplicación móvil multiplataforma con API y panel web administrativo. |
| Estado observado | Varias funcionalidades implementadas, persistencia local, sincronización móvil-web y pruebas versionadas. No se evaluó preparación para producción. |

### 1.2 Alcance temporal de la revisión

La copia local apunta al commit 05743b97f2e5835250e223eabc09f9924b7524bb, fechado el 12 de octubre de 2026. Tiene 27 commits en el historial local, tres identidades de autor y un merge identificado como PR #1. Los refs disponibles localmente muestran main, origin/main y origin/HEAD.

No se pudo consultar GitHub en vivo en esta revisión: falló la resolución de github.com y las páginas del repositorio no cargaron. Por eso, el estado actual de Issues, PRs, revisiones, Projects, milestones y ramas del servidor queda **no evidenciado en el repositorio local revisado**. Se requiere verificarlos desde GitHub antes de crear artefactos para evitar duplicados. La identidad de tres autores en el historial local no permite concluir que el cuarto integrante no haya colaborado; confirmar su cuenta de GitHub y forma de contribución.

### 1.3 Tecnologías y funcionalidades observadas

| Componente | Evidencia observada |
|---|---|
| App Android/iOS | Kotlin Multiplatform, Compose Multiplatform, estructura shared, androidApp e iosApp. |
| Datos locales | SQLDelight/SQLite y migraciones de base local. |
| Comunicación | Cliente Ktor y API Laravel autenticada mediante tokens; sincronización de datos/cambios y entregas. |
| Backend/panel web | Laravel, PHP 8.3, Eloquent, rutas web y API, vistas administrativas. |
| Funciones móviles | Perfiles de administrador, acopiador, técnico de calidad y proveedor; jornadas/entregas, calidad, QR, reclamos, solicitudes de traslado y pagos. |
| Calidad | OCR/escáner de comprobante en Android; el documento de calidad describe ingreso manual en iOS. |
| Otras integraciones | Reglas y pruebas de Firestore; proceso web descrito por web/Procfile para Heroku. |
| Pruebas versionadas | 48 archivos Kotlin, 32 PHP y un archivo de pruebas de reglas Firebase. Son conteos de archivos, no cobertura ni prueba de que pasen. No ejecuté pruebas en esta revisión. |
| Documentación | README raíz genérico en inglés y documentación específica de administración, calidad y portal del proveedor. |

### 1.4 Evidencia de proceso existente y faltante

| Área | Evidencia observada | Estado |
|---|---|---|
| Incrementos | Los mensajes de commits describen módulos y cambios en diferentes fechas | Evidencia parcial de desarrollo incremental, no de Scrum |
| Ramas | Solo refs locales main, origin/main, origin/HEAD | La rama develop no está evidenciada localmente; consultar GitHub antes de crearla |
| Pull Requests | El historial tiene un merge de PR #1 | Existe al menos una integración por PR; revisión/flujo habitual no evidenciado |
| Issues/backlog | No se pudo consultar la lista remota; no hay backlog de Scrum versionado en la copia | No evidenciado; crear Product Backlog con Issues |
| Scrum | No encontré Product Goal, Sprint Goal, actas, registro Daily, Review, Retrospective ni Definition of Done | No evidenciado; iniciar registro desde hoy, sin inventar eventos anteriores |
| Tablero/milestones | No evidenciados en los archivos locales; Projects no se pudo consultar en vivo | Verificar GitHub y crear si faltan |
| CI/CD | No se encontró .github con workflows versionados | CI de GitHub Actions no evidenciada. web/Procfile por sí solo no es CI/CD |
| Pruebas | Hay pruebas Kotlin, PHP y Firebase en el árbol | La ejecución/resultado no evidenciado; ejecutar y registrar en el sprint |

**Conclusión del proceso actual:** no se evidencia Scrum en el repositorio. El historial aparenta desarrollo iterativo e incremental informal. Para afirmar que el equipo aplicó Scrum desde el sprint, deben existir artefactos y evidencias reales de los eventos, del backlog, del incremento y de la inspección/adaptación.

### 1.5 Inconsistencia entre documentación y código que afecta el backlog

PROVEEDOR_MOVIL_FIGMA.md afirma que no hay API móvil conectada para pagos, reclamos, traslados o comunicados. En el código revisado sí hay una ruta de consulta de liquidaciones del proveedor, una ruta genérica de cambios que acepta reclamos, y lógica para exportar/importar comunicados. En cambio, el mapeo móvil de solicitudes solo genera payload para solicitudes tipo RECLAMO; no incluye evidencia fotográfica en ese payload. La exportación consultada no incluye un flujo de retorno del estado administrativo del reclamo al proveedor.

HU-001 se centra en un comportamiento menor y verificable: después de una respuesta exitosa del servidor, la app debe distinguir el reclamo recibido por el panel de un reclamo aún pendiente de envío. El trabajo debe confirmar primero el estado real en un entorno de prueba. No actualizar la documentación hasta observar el resultado.

Referencias de código: web/routes/api.php; web/app/Application/Movil/AplicarCambioMovilUseCase.php; web/app/Application/Movil/ExportarDatosMovilQuery.php; shared/src/commonMain/kotlin/pe/ecolecta/data/remote/ServidorWebRepositoryKtor.kt; shared/src/commonMain/kotlin/pe/ecolecta/data/repository/SqlDelightCambiosLocalesRepository.kt; shared/src/commonMain/kotlin/pe/ecolecta/presentation/proveedor/PortalSolicitudes.kt.

## 2. Metodología Scrum

### 2.1 Por qué Scrum

Scrum es adecuado para el equipo porque el proyecto tiene módulos móviles y web conectados, dependencias técnicas y funcionalidades que necesitan verificarse con usuarios y pruebas. Un sprint corto permite seleccionar un objetivo, limitar el trabajo comprometido, producir un incremento integrado y revisar si los criterios se cumplieron.

El tablero de columnas propuesto visualiza el Sprint Backlog. No cambia la metodología: el trabajo se planifica y revisa mediante los roles, eventos, artefactos y compromisos de Scrum.

### 2.2 Pilares y valores

| Pilar | Aplicación al proyecto |
|---|---|
| Transparencia | Issues con criterios visibles; tablero actualizado; resultados de prueba y bloqueos registrados con honestidad. |
| Inspección | Daily sobre el avance hacia el Sprint Goal; PRs; pruebas; Sprint Review del incremento. |
| Adaptación | Ajustar tareas durante el sprint sin cambiar el Sprint Goal; reordenar Product Backlog después de Review/Retro. |

| Valor Scrum | Conducta observable |
|---|---|
| Compromiso | Cada Developers acuerda un plan realista y avisa temprano si una dependencia amenaza el Sprint Goal. |
| Foco | El sprint trabaja sobre HU-001; no se inician otras historias por conveniencia mientras la seleccionada siga incompleta. |
| Apertura | Mostrar errores, trabajo pendiente y resultados fallidos sin ocultarlos. |
| Respeto | Revisar el trabajo de manera profesional y pedir ayuda sin culpabilizar. |
| Coraje | Corregir una implementación o reducir alcance cuando la evidencia muestra que no cumple el criterio. |

Scrum se elige como marco principal. GitHub Projects/Trello puede visualizar su Sprint Backlog. Pruebas automatizadas, revisión de código e integración frecuente son prácticas de desarrollo que apoyan el incremento; no sustituyen los eventos y responsabilidades Scrum.

### 2.3 Comparación contextual de enfoques

Esta comparación responde a la evaluación inicial de alternativas. La decisión del proyecto queda fijada en **Scrum como única metodología principal**, conforme al requisito del curso. La tabla no propone sustituirlo: el tablero de columnas solo visualiza el Sprint Backlog.

| Enfoque | Facilidad de adopción | Adecuación para 4 | Entregas frecuentes | Cambios | Documentación | Herramientas | Dificultad universitaria | Posición para este proyecto |
|---|---|---|---|---|---|---|---|---|
| Scrum | Media: requiere aprender roles, eventos y artefactos | Alta | Alta, al cerrar cada sprint | Alta entre sprints; el Sprint Goal da foco durante el sprint | Media: backlog, objetivo, eventos y Definition of Done | GitHub Issues/Projects, PRs y actas | Media | **Principal y obligatoria** |
| Kanban | Alta para visualizar trabajo | Alta | Continua | Alta | Baja | Tablero | Baja | Solo tablero visual del Sprint Backlog; no metodología del equipo |
| XP | Media | Media/alta si se puede practicar programación en pareja y pruebas frecuentes | Alta | Alta | Baja/media | Git, CI y herramientas de prueba | Media/alta por disciplina técnica sostenida | No se adopta como marco principal |
| OpenUP | Media/baja por su cantidad de roles y artefactos | Media/baja para un equipo pequeño y plazo corto | Media | Media/alta | Alta | Repositorio documental y modelado | Alta | No se adopta como marco principal |
| Iterativo incremental | Alta como estrategia de construcción | Alta | Alta | Alta | Variable | Git y tablero opcional | Baja/media | Es una característica del ciclo de entrega que Scrum ya permite; no reemplaza Scrum |
| Cascada | Alta si requisitos fueran estables | Baja ante incertidumbre y validación frecuente | Baja: entrega principal tardía | Baja | Alta al inicio | Gestión documental | Media | No se adopta como marco principal |

La opción coherente con el mandato del curso es aplicar Scrum y entregar un incremento pequeño al final del Sprint 1. Se pueden usar prácticas de ingeniería como pruebas y revisión de código, sin presentarlas como una segunda metodología.

### 2.4 Artefactos y compromisos Scrum

- **Product Backlog — Product Goal:** lista ordenada de trabajo del producto y objetivo de producto acordado por el PO.
- **Sprint Backlog — Sprint Goal:** objetivo del sprint, historias elegidas y plan de Developers.
- **Increment — Definition of Done:** resultado integrado que cumple los criterios y el estándar de calidad acordado.

Product Goal propuesto para revisar con el PO: **“Permitir que los participantes del acopio registren y consulten información confiable desde el móvil, con sincronización trazable hacia el panel administrativo.”**

## 3. Roles Scrum del equipo

En Scrum existen tres responsabilidades: Product Owner, Scrum Master y Developers. Para un equipo universitario de cuatro personas se propone una persona como PO, una como Scrum Master y dos Developers. El PO y el Scrum Master pueden apoyar tareas técnicas si tienen las competencias, pero el accountability de cada rol debe quedar claro. Completar nombres reales en Planning.

| Integrante | Accountability/rol | Responsabilidades | Evidencias | Participación en eventos |
|---|---|---|---|---|
| [Integrante 1] | Product Owner | Representar necesidades de usuario/cliente; ordenar el Product Backlog; aclarar valor y criterios; decidir aceptación de historias | Product Goal, prioridades, criterios, decisiones y observaciones | Participa en Planning, aclara valor; en Review inspecciona el incremento y adapta el Product Backlog |
| [Integrante 2] | Scrum Master | Facilitar Scrum; ayudar al equipo a eliminar impedimentos; asegurar eventos útiles y time-boxed; no dirigir como jefe ni asignar trabajo individual | Actas, impedimentos, acuerdos de proceso y mejora | Facilita Planning/Daily/Review/Retro; ayuda a adaptar el proceso y protege el foco |
| [Integrante 3] | Developer — móvil/UI | Diseñar e implementar cambios Kotlin/Compose; apoyar pruebas en Android/iOS; revisar código de un compañero | Commits, PR, pruebas y capturas reales | Estima y construye el incremento; participa en todos los eventos |
| [Integrante 4] | Developer — API/datos/QA | Implementar o validar Laravel/API/datos; diseñar casos de prueba; revisar código; apoyar documentación técnica | PR, log de prueba, evidencia de integración y documentación | Estima y construye el incremento; participa en todos los eventos |

Los Developers son responsables colectivamente del plan del Sprint Backlog, calidad, adaptación diaria y creación de un Increment utilizable. PO y Scrum Master no son jefes de los Developers.

**Distribución de especialidades dentro de los Developers:** la propuesta asigna móvil/UI al Developer 1 y API/datos/QA al Developer 2 para este sprint acotado. Diseño UI/UX, base de datos, pruebas, documentación y control de versiones son responsabilidades de trabajo que se acuerdan entre los Developers según las tareas; no son roles Scrum adicionales. El Scrum Master mantiene actas y facilita el tablero como evidencia del proceso, y el PO valida el valor y los criterios con el representante de usuario disponible.

## 4. Product Backlog priorizado

**Estado de la tabla:** propuestas para discutir en Planning/refinamiento; no son Issues creados ni trabajo realizado. HU-001 es la única propuesta para el Sprint 1. Puntos de Planning Poker son tentativos hasta que el equipo vote.

### 4.1 Resumen de prioridad

| Prioridad | ID | Título | Valor | Riesgo técnico | Puntos tentativos | Responsable sugerido | Estado propuesto |
|---|---|---|---|---|---:|---|---|
| Alta | HU-001 | Mostrar confirmación real de recepción del reclamo | Alto: el proveedor sabe si el panel lo recibió | Medio: estado de outbox y estado de revisión están unidos | 5 | Developer móvil + Developer QA | Pendiente; candidata a Sprint 1 |
| Alta | HU-002 | Enviar reclamo de forma idempotente al panel | Alto: el reclamo llega a administración | Medio: depende de sincronizar antes la entrega | 5 | Developer API + móvil | Pendiente/verificar |
| Alta | HU-003 | Consultar resolución del reclamo desde móvil | Alto: seguimiento del proveedor | Alto: no se evidencia retorno del estado | 8 | Developers móvil/API | Pendiente |
| Alta | HU-004 | Sincronizar solicitud de traslado con el panel | Alto: traslado evaluable por administración | Alto: entidad no aparece en mapping móvil revisado | 8 | Developers API/móvil | Pendiente |
| Media | HU-005 | Enviar foto de evidencia del reclamo | Medio/alto: apoya evaluación | Alto: almacenamiento, permisos y payload | 8 | Developers móvil/API | Pendiente |
| Alta | HU-006 | Consultar liquidaciones oficiales del servidor | Alto: muestra pagos reales | Medio: ruta/cliente existen; integración real requiere validar | 3 | Developer móvil + QA | Pendiente/verificar |
| Media | HU-007 | Recibir comunicados del panel | Medio: acceso a avisos | Bajo/medio: ruta de datos e importación existen; verificar | 3 | Developer móvil + QA | Pendiente/verificar |
| Alta | HU-008 | Registrar entrega offline y sincronizarla | Alto: continuidad del acopio | Alto: duplicados, conflictos y conectividad | 5 | Developers móvil/API | Pendiente/verificar |
| Media | HU-009 | Verificar lectura OCR del comprobante de calidad | Medio: reduce transcripción manual | Medio: OCR/dispositivo, plataforma y permisos | 5 | Developer móvil + QA | Pendiente/verificar |

### 4.2 Historias detalladas

#### HU-001 — Mostrar confirmación real de recepción del reclamo

**Historia:** Como proveedor, quiero distinguir un reclamo pendiente de envío de uno recibido por el panel, para saber si administración ya puede revisarlo.

**Descripción funcional:** la solicitud local inicia como pendiente de envío. Solo tras respuesta exitosa del servidor debe mostrarse “Recibido por el panel; pendiente de revisión”. Un error de red o una respuesta que deje el envío en espera no puede mostrar recepción confirmada. Mantener separado el estado de transporte del estado de revisión administrativa para no ocultar reclamos pendientes en la bandeja del administrador.

**Criterios de aceptación:**
- [ ] Un reclamo recién guardado muestra “Pendiente de envío”.
- [ ] Un 2xx del endpoint cambia y persiste el estado de transporte como recibido.
- [ ] Sin conexión/espera mantiene el estado pendiente de envío.
- [ ] Un rechazo definitivo muestra error y no marca recepción.
- [ ] El estado y la etiqueta sobreviven al cerrar/reabrir la app.
- [ ] El estado administrativo sigue siendo pendiente hasta que un administrador lo resuelva.
- [ ] Pruebas cubren éxito, falta de conexión/espera y rechazo.

**Prioridad:** alta. **Puntos Planning Poker:** 5 tentativos. **Valor:** transparencia de una solicitud importante. **Riesgo:** mezclar estado de transporte con estado de aprobación. **Responsable:** Developer móvil; revisión de Developer API/QA. **Estado:** propuesta para seleccionar en Sprint 1, todavía no acordada.  
**Tareas:** rastrear completar/fallar en outbox; agregar estado de envío persistente separado; actualizar etiqueta y texto; pruebas; PR hacia develop; demo.  
**Evidencia esperada:** Issue, PR aprobada, salida de pruebas, captura de estados pendiente/recibido/error y comentario del PO en Review.

#### HU-002 — Enviar reclamo de forma idempotente al panel

**Historia:** Como proveedor, quiero que mi reclamo asociado a una entrega llegue al panel cuando la entrega esté sincronizada, para que administración pueda revisarlo.

**Descripción funcional:** verificar el flujo móvil-servidor que genera el payload de RECLAMO y la regla del backend que espera la entrega reclamada.

**Criterios de aceptación:** el reclamo conserva su UUID; si la entrega aún no existe en el panel no se informa como recibido; al estar disponible se guarda con proveedor/entrega correctos; un reintento no crea duplicado; el resultado se registra.

**Prioridad:** alta. **Estimación:** 5 puntos. **Valor:** gestión de reclamos trazable. **Riesgo:** orden de sincronización de entrega y reclamo. **Responsable:** Developer API con apoyo móvil. **Estado:** pendiente/verificar; hay código para el flujo.  
**Tareas:** revisar payload y reglas; probar entrega disponible/no disponible; comprobar duplicados; corregir pruebas/código si falla.  
**Evidencia:** Issue, PR si cambia código, pruebas reales y captura de administración con datos ficticios.

#### HU-003 — Consultar resolución del reclamo desde móvil

**Historia:** Como proveedor, quiero ver si el administrador atendió, aprobó o rechazó mi reclamo, para conocer el resultado.

**Descripción funcional:** comprobar qué respuesta remota recibe el proveedor tras una resolución administrativa; la exportación de datos revisada no incluye reclamos.

**Criterios de aceptación:** solo se muestran solicitudes del proveedor autenticado; estado coincide con el panel; se distingue “recibido pendiente de revisión” de “resuelto”; el estado se conserva al reiniciar.

**Prioridad:** alta. **Estimación:** 8 puntos. **Valor:** cerrar el ciclo de comunicación. **Riesgo:** contrato de API y aislamiento por proveedor. **Responsable:** Developers móvil/API. **Estado:** pendiente/no evidenciado como flujo de retorno.  
**Tareas:** definir contrato; implementar exportación/importación si falta; mapear estados; pruebas de autorización y estados.  
**Evidencia:** contrato, PR(s), tests, capturas de un caso por estado.

#### HU-004 — Sincronizar solicitud de traslado con el panel

**Historia:** Como proveedor, quiero enviar una solicitud de traslado al panel, para que administración evalúe cambiar mi zona.

**Criterios de aceptación:** payload incluye proveedor, origen, zona destino y motivo; la zona no cambia antes de autorización; solicitud llega una vez; proveedor ve el resultado solo cuando se confirme.

**Prioridad:** alta. **Estimación:** 8 puntos. **Valor:** autoservicio del proveedor. **Riesgo:** alta dependencia API y estados. **Responsable:** Developers móvil/API. **Estado:** pendiente; el mapeo revisado no incluye solicitudes tipo TRASLADO.  
**Tareas:** definir contrato/estados, ruta y permisos, sincronización, auditoría, pruebas de aprobación/rechazo.  
**Evidencia:** Issue, PR, prueba de seguridad/estados y capturas.

#### HU-005 — Enviar foto de evidencia del reclamo

**Historia:** Como proveedor, quiero adjuntar una foto que administración pueda consultar junto con el reclamo, para explicar mejor el caso.

**Criterios de aceptación:** foto se asocia al UUID; se restringe tamaño/tipo; no se publica a usuarios no autorizados; error de red mantiene la evidencia local y no muestra envío exitoso.

**Prioridad:** media. **Estimación:** 8 puntos. **Valor:** evidencia para evaluación. **Riesgo:** almacenamiento/seguridad y sincronización. **Responsable:** Developers móvil/API. **Estado:** pendiente; el modelo local contiene evidencia pero el payload revisado no la transmite.  
**Tareas:** acordar almacenamiento/contrato, enviar archivo o referencia segura, permisos, pruebas de tamaño y error.  
**Evidencia:** decisión técnica, PR, test de acceso, captura anonimizada.

#### HU-006 — Consultar liquidaciones oficiales del servidor

**Historia:** Como proveedor, quiero consultar las liquidaciones publicadas para mi cuenta, para revisar montos y estado de pago.

**Criterios de aceptación:** muestra solo cuenta autenticada; total/estado/fecha coinciden con servidor; distingue carga, error y vacío; no inventa datos.

**Prioridad:** alta. **Estimación:** 3 puntos. **Valor:** información financiera correcta. **Riesgo:** medio. **Responsable:** Developer móvil + QA. **Estado:** pendiente de verificación; ruta y cliente existen aunque la documentación del portal lo contradice.  
**Tareas:** integrar cuenta de prueba; ejecutar consulta; verificar privacidad; actualizar doc si el comportamiento confirmado difiere.  
**Evidencia:** resultado real, prueba de aislamiento, captura sin datos personales.

#### HU-007 — Recibir comunicados del panel

**Historia:** Como proveedor, quiero ver comunicados vigentes del panel, para enterarme de avisos operativos.

**Criterios de aceptación:** se muestran solo comunicados publicados para audiencia; retiro en panel elimina/oculta tras sincronizar; texto, autor y fecha corresponden al servidor.

**Prioridad:** media. **Estimación:** 3 puntos. **Valor:** comunicación operativa. **Riesgo:** bajo/medio. **Responsable:** Developer móvil + QA. **Estado:** pendiente de verificación; hay lógica de exportación/importación.  
**Tareas:** probar publicar, sincronizar y retirar; revisar UI y estados; registrar evidencia.  
**Evidencia:** prueba de integración y capturas fechadas.

#### HU-008 — Registrar entrega offline y sincronizarla

**Historia:** Como acopiador, quiero registrar entregas sin conexión y sincronizarlas al recuperarla, para continuar el acopio en zonas con conectividad variable.

**Criterios de aceptación:** persistencia local; envío una sola vez; datos de proveedor/jornada/litros correctos; conflicto/rechazo visible y recuperable.

**Prioridad:** alta. **Estimación:** 5 puntos. **Valor:** operación continua. **Riesgo:** concurrencia, idempotencia y conectividad. **Responsable:** Developers móvil/API. **Estado:** pendiente de prueba; existe código y pruebas versionadas.  
**Tareas:** ejecutar pruebas relevantes; probar red desconectada/reconectada; verificar conflicto y duplicado; corregir si falla.  
**Evidencia:** log de pruebas, PR si hay ajuste, captura móvil/panel con datos ficticios.

#### HU-009 — Verificar lectura OCR de comprobante de calidad

**Historia:** Como técnico de calidad, quiero escanear y revisar los campos de un comprobante antes de guardarlos, para reducir errores de transcripción.

**Criterios de aceptación:** no reconocidos quedan vacíos, nunca como cero; valores dudosos se pueden corregir; ingreso manual disponible; en Android se valida dispositivo; comportamiento de iOS se describe correctamente.

**Prioridad:** media. **Estimación:** 5 puntos. **Valor:** velocidad/calidad del registro. **Riesgo:** OCR, cámara y diferencias de plataforma. **Responsable:** Developer móvil + QA. **Estado:** pendiente de verificación manual; parser/tests existen.  
**Tareas:** correr pruebas del parser; probar escaneo Android con comprobante de prueba; comprobar corrección manual; registrar limitaciones iOS.  
**Evidencia:** logs, captura/video, comprobante ficticio, documentación.

### 4.3 Priorización y Planning Poker

Ordenar por valor al usuario, riesgo de integración y dependencias. HU-001 se propone primero porque tiene alcance estrecho y hace visible una discrepancia de estado. HU-002/003 dependen de la integración móvil-API; HU-004/005 son mayores por requerir contrato/almacenamiento.

En Planning Poker, cada persona vota en privado con escala 1, 2, 3, 5, 8, 13; se revelan los votos a la vez. Quienes eligieron extremos explican dependencias y riesgos; Developers vuelven a votar hasta tener consenso suficiente. Los puntos miden tamaño relativo, no horas ni rendimiento individual. Los valores de este borrador son solo una propuesta; reemplazar con el voto real en Planning.

## 5. Sprint 1 — Sprint Backlog

### 5.1 Propuesta de Sprint

| Campo | Definición propuesta |
|---|---|
| Nombre | Sprint 1 — Estado verificable de reclamos |
| Inicio | Lunes 19 de octubre de 2026 |
| Cierre | Lunes 26 de octubre de 2026 |
| Duración | Una semana calendario |
| Sprint Goal | Al cierre, el proveedor distingue con claridad un reclamo pendiente de envío de uno confirmado por el panel, sin mostrar éxito cuando no hubo respuesta exitosa; el cambio queda integrado, probado y demostrado. |
| Incremento esperado | Estado de envío persistente y visible para reclamos; la revisión administrativa conserva su propio estado. PR aprobada hacia develop, pruebas aplicables registradas y demo. |
| Historias seleccionadas | HU-001 solamente, tras aceptación del equipo en Planning |
| Estimación | 5 puntos tentativos; confirmar con Planning Poker real |
| Capacidad | No hay velocidad histórica verificable; comprometer una sola historia acotada |

La historia toca un flujo existente: la cola marca los cambios como completados tras respuesta exitosa, pero la solicitud local puede conservar PENDIENTE_ENVIO; la pantalla traduce los estados que no son finales a “Pendiente de revisión”. El sprint separa estado de transporte y estado administrativo para no afirmar una recepción inexistente. Antes de implementar, comprobar si el código más reciente del servidor local coincide con esta lectura.

### 5.2 Sprint Backlog: tareas técnicas

| ID | Tarea | Responsable propuesto | Estimación de tiempo | Criterio de salida |
|---|---|---|---:|---|
| T1 | Trazar creación, outbox, éxito/reintento/error, persistencia y etiqueta actual | Developer móvil + QA | 1–2 h | Flujo dibujado en Issue; casos de prueba acordados |
| T2 | Agregar/usar un estado de transporte persistente separado del estado de revisión | Developer móvil | 2–3 h | Éxito 2xx guarda “recibido”; error no lo guarda |
| T3 | Actualizar lista de solicitudes para mostrar “Pendiente de envío”, “Recibido; pendiente de revisión” y estados administrativos sin ambigüedad | Developer móvil | 1–2 h | Etiquetas verificadas contra cada estado |
| T4 | Añadir pruebas de éxito, no conexión/espera, rechazo y persistencia | Developer QA + autor | 2–3 h | Resultado real documentado; todas las pruebas aplicables pasan |
| T5 | Abrir PR a develop, revisar por otro integrante, integrar y preparar demo | Autor + revisor + PO | 1–2 h | PR aprobada/integrada; PO verifica criterios |

Estimación de esfuerzo de equipo: aproximadamente 7–12 horas técnicas repartidas durante la semana, además de eventos Scrum. Confirmar disponibilidad y puntos en Planning; si el equipo no puede completar el alcance, recortar criterios sin cambiar el Sprint Goal o escoger una historia menor.

### 5.3 Compromisos por persona

| Integrante | Compromiso propuesto |
|---|---|
| PO [nombre] | Aclarar estados y criterios; confirmar que el texto da valor al proveedor; inspeccionar HU-001 en Review. No marcar aceptación antes de la demo. |
| Scrum Master [nombre] | Facilitar eventos; mantener visible el bloqueo de entorno; ayudar a removerlo; comprobar que se realicen review/retro antes de entrega. |
| Developer móvil [nombre] | Trazar y desarrollar el estado persistente/UI; abrir PR con Issue relacionado. |
| Developer API/QA [nombre] | Validar respuestas del servidor/errores; añadir o revisar pruebas; revisar PR sin aprobar su propio código. |

Los Developers estiman y deciden cómo realizar el trabajo. Los nombres y disponibilidad se completan tras acuerdo real.

### 5.4 Definition of Ready y Definition of Done

**Ready (acuerdo del equipo para tomar trabajo):** historia tiene usuario/valor, criterios verificables, dependencias, datos de prueba, responsable y tamaño estimado.

**Done (compromiso de calidad del Increment):**
- criterios de aceptación cumplidos y demostrables;
- pruebas aplicables ejecutadas y resultado anotado;
- PR revisada por alguien distinto al autor;
- integración a develop comprobada;
- documentación actualizada y coherente;
- capturas/logs sin datos personales ni credenciales;
- PO inspeccionó el resultado en Sprint Review.

## 6. Eventos Scrum y evidencias

Las fechas siguientes son **propuestas**. Una acta solo existe después de realizar el evento.

### Sprint Planning

- **Fecha:** lunes 19 de octubre.
- **Duración:** 60 minutos.
- **Participantes:** PO, Scrum Master y los dos Developers.
- **Objetivo:** acordar el Sprint Goal, seleccionar HU-001 tras refinarla y crear el plan de Developers.
- **Preguntas:** ¿Por qué este sprint es valioso? ¿Qué se puede completar? ¿Cómo se realizará el trabajo?
- **Evidencia:** acta con nombres/asistencia, valor, Sprint Goal, historia y criterios seleccionados, votos Planning Poker, plan técnico, riesgos, captura inicial del board y enlace al Milestone/Issue.

### Daily Scrum

- **Frecuencia:** cada día laborable del sprint; 10–15 minutos máximo. Fechas sugeridas: martes 20 a viernes 23 y lunes 26 antes de Review. No programar trabajo de sábado/domingo; si el equipo decide trabajar esos días, realizar Daily también esos días.
- **Participantes:** Developers; PO y Scrum Master pueden asistir, pero el evento es de Developers. Scrum Master facilita si el equipo lo necesita.
- **Formato sugerido:** ¿Qué hice ayer hacia el Sprint Goal? ¿Qué haré hoy? ¿Qué impedimentos tengo?
- **Evidencia:** comentario/acta breve con fecha, asistentes y bloqueo; captura de llamada solo si se hizo una llamada real. Si no hay llamada, registrar actualización asíncrona y nombrarla como tal.

### Sprint Review

- **Fecha propuesta:** lunes 26 de octubre antes de entregar.
- **Duración:** 30–40 minutos.
- **Participantes:** Scrum Team; docente/usuario invitado si está disponible.
- **Objetivo:** inspeccionar el Increment con el PO y adaptar el Product Backlog.
- **Demo:** crear/observar reclamo con estado pendiente; simular o usar respuesta exitosa controlada y mostrar confirmación; mostrar que sin conexión/rechazo no se reporta recibido. Usar datos ficticios.
- **Evidencia:** acta, demo/captura real, PR, criterios cumplidos/pendientes, resultado de pruebas y observación del PO.

### Sprint Retrospective

- **Fecha propuesta:** lunes 26 de octubre, después de Review y antes de enviar documento.
- **Duración:** 20–30 minutos.
- **Participantes:** Scrum Team.
- **Preguntas:** qué salió bien, qué fue difícil, qué mantener, qué dejar de hacer y qué acción concreta probar en Sprint 2.
- **Evidencia:** acta con al menos una acción, responsable y fecha de revisión. No reconstruir retro retrospectivamente si no se llevó a cabo.

## 7. Tablero del Sprint Backlog

Crear en GitHub Projects. El tablero es una vista de trabajo para Scrum, no una segunda metodología.

| Columna | Qué tarjeta corresponde | Quién actualiza | Evidencia al mover |
|---|---|---|---|
| Product Backlog | Historias ordenadas no seleccionadas | PO mantiene orden con participación del equipo | Issue con valor, criterios y prioridad |
| Sprint Backlog / Por hacer | HU-001 y tareas acordadas en Planning | Developers actualizan al tomar trabajo | Issue asociado al Milestone Sprint 1 |
| En desarrollo | Trabajo en curso | Developer responsable | Rama y commits con enlace al Issue |
| En revisión / Pull Request | Código listo para revisión | Autor abre PR; revisor la toma | PR vinculada, checklist y revisión |
| En pruebas | Cambio aprobado o listo para comprobar | Developer/QA | Log de prueba, entorno y criterios verificados |
| Terminado | Done según acuerdo, integrado a develop y aceptado en Review | Developer actualiza; PO inspecciona aceptación | PR integrada, pruebas y criterios cumplidos |

Regla práctica de flujo: no mover a “Terminado” solo por terminar el código; bloquear la tarjeta si no hay entorno o dependencia y registrar quién la desbloquea. Usar etiquetas para tipo/prioridad y columnas como estado visible; evitar estados contradictorios.

## 8. GitHub como evidencia de Scrum

### 8.1 Preparación

1. Consultar el GitHub actual para no duplicar trabajo. La copia analizada solo evidencia main; develop no está en sus refs locales.
2. Si develop tampoco existe en GitHub, crearla desde la main remota y protegerla. Esta acción requiere quien administre el repo; no se realizó en esta revisión.
3. Crear Milestone **Sprint 1 — Estado verificable de reclamos**, con fechas 19–26 oct y objetivo.
4. Crear GitHub Project con las seis columnas anteriores y ligar Issues.
5. Crear un Issue por historia o tarea técnica, con checklist de criterios y responsable.
6. Usar labels solicitadas: scrum, sprint-1, feature, bug, documentation, high-priority, in-progress, review, testing, done. Las columnas deben seguir siendo la fuente del estado para no contradecir etiquetas.
7. Rama desde develop: feature/hu-001-estado-reclamo-proveedor.
8. PR de feature a develop; solicitud de revisión a otro integrante; no autoaprobar. Merge solo cuando pruebas y checklist estén completos.
9. Después de Sprint Review, cualquier release validada puede integrarse de develop a main con PR separado, si el equipo necesita conservar main como rama estable.
10. Ejecutar y adjuntar resultados reales de pruebas. Comandos existentes que se pueden evaluar: desde la raíz, ./gradlew :shared:testAndroidHostTest -PlocalPreview=true; backend, desde web, composer test. Confirmar dependencias/configuración primero. No usar la vista local como evidencia de sincronización real sin verificar qué servicios utiliza.

### 8.2 Ejemplos concretos

**Issue:** HU-001 — Mostrar recepción confirmada del reclamo por el panel

**Descripción:**

Como proveedor, quiero distinguir un reclamo pendiente de envío de uno recibido por el panel, para saber si administración puede revisarlo.

Criterios:
- [ ] Nuevo reclamo: estado “Pendiente de envío”.
- [ ] Respuesta exitosa del servidor: estado persistido “Recibido por el panel; pendiente de revisión”.
- [ ] Sin conexión, espera o error: no marcar como recibido.
- [ ] Al reiniciar la app el estado continúa correcto.
- [ ] El estado de transporte no elimina el estado administrativo pendiente.

Prioridad: Alta. Estimación por Planning Poker: 5 puntos propuestos. Milestone: Sprint 1.  
Responsable: [Developer móvil]. Revisión: [otro integrante].  
Dependencias: ambiente de prueba y cuenta/proveedor ficticio.

**Rama:** feature/hu-001-estado-reclamo-proveedor

**Commit sugerido:** feat(proveedor): distinguir reclamo enviado de pendiente #<número>

Usar solo si el commit refleja ese cambio real y sustituir el número por el Issue existente.

**PR:** HU-001: distingue reclamo enviado de pendiente

**Descripción de PR:**
- Closes #<número>
- Cambio real: [describir archivos/función]
- Criterios probados: [marcar solo los verificados]
- Pruebas ejecutadas: [comando, fecha, entorno y salida]
- Capturas: [enlace]
- Riesgos/pendientes: [lista]
- Revisor: [integrante distinto del autor]

**Comentario de revisión de ejemplo:**

“Revisé que la respuesta exitosa actualice el estado de envío y que el fallo de red no lo marque como recibido. Revisé además la persistencia tras reiniciar. La prueba [nombre] se ejecutó el [fecha] y obtuvo [resultado]. [Apruebo / solicito cambios porque…]”

No copiarlo como aprobación sin realizar revisión.

### 8.3 Capturas que deben tomar

- Project antes y después del sprint, con fecha visible si la interfaz la muestra.
- Milestone y objetivo/fechas.
- Issue HU-001 con criterios y checklist.
- Planning Poker real (foto de tarjetas/votos o registro agregado, sin atribuir votos no emitidos).
- PR con checks, diff y aprobación real.
- Salida de pruebas.
- App mostrando estado pendiente y recibido con datos ficticios.
- Sprint Review: demo o captura del incremento.
- Retrospective con acción acordada.
- Métricas y tablero al cierre.

Guardar capturas en docs/metodologia/evidencias/2026-10-19_a_2026-10-26/ y nombrarlas descriptivamente, por ejemplo board-inicio-2026-10-19.png. Esa carpeta no representa evidencias hasta que se guarden capturas auténticas.

## 9. Documento que revisará el docente

El título y las secciones finales serán:

1. Datos generales: proyecto, integrantes, roles, repo, tecnologías y objetivo.
2. Metodología Scrum: justificación, pilares y valores.
3. Roles Scrum: integrantes, responsabilidades y evidencias.
4. Product Backlog: historias priorizadas, criterios de valor/riesgo y Planning Poker.
5. Sprint Planning: Sprint Goal, fechas, selección, estimación, compromisos y acta.
6. Daily Scrum: formato y registros diarios reales.
7. Desarrollo e incremento: ramas, Issues, PRs, revisión, commits, pruebas e incremento.
8. Sprint Review: fecha, demo, historias completadas/pendientes, observaciones y acta.
9. Sprint Retrospective: fortalezas, dificultades, acciones concretas y acta.
10. Métricas Scrum: planificadas/terminadas, puntos, Issues, PRs, burndown y velocidad.
11. Evidencias: nombre, herramienta, fecha, responsable y ubicación.
12. Conclusiones: aplicación real, dificultades, beneficios observados y mejora siguiente.

Completar los resultados después de cada evento. No declarar que se aplicó Scrum correctamente basándose solo en este plan.

## 10. Métricas y burndown

| Métrica | Fuente | Regla |
|---|---|---|
| Historias planificadas vs terminadas | Milestone/Project | Contar historias aceptadas en Planning y Done según DoD |
| Puntos comprometidos vs completados | Issues | Usar estimación acordada por Developers; no comparar personas |
| Issues cerrados | GitHub | Cerrar solo si se cumple el criterio |
| PRs creadas/revisadas/aprobadas/mergeadas | GitHub | Enlazar cada PR al Issue y registrar estados reales |
| Pruebas ejecutadas/pasadas/fallidas/no ejecutadas | Actions o log | No declarar “pasa” si no se ejecutó |
| Velocidad | Sprint cerrado | Primer sprint da referencia débil, no una promesa |
| Burn-down | Trabajo restante por día | Registrar puntos o esfuerzo restante con una escala constante; anotar fecha y fuente |

**Cómo hacer el burndown:** eje horizontal = días laborables del sprint; eje vertical = puntos restantes de las historias comprometidas o esfuerzo restante definido por Developers. Registrar al final de cada día cuánto trabajo queda, no cuánto tiempo ya se consumió. Dibujar línea ideal desde 5 hasta 0 y línea real solo con datos diarios. HU-001 tiene 5 puntos: si se usa puntos de historia, la línea puede quedarse en 5 hasta que la historia quede Done y después bajar a 0; no inventar puntos fraccionarios para que el gráfico se vea uniforme. Adjuntar una tabla de datos usada para el gráfico.

## 11. Evidencias: estado y ubicación

| Evidencia | Estado a la fecha de este borrador | Acción/ubicación |
|---|---|---|
| Historial local | Existe, hasta commit local del 12 oct | Enlace al repositorio/commit; no equivale a Sprint 1 |
| PR #1 mergeada | Existe en historial local | Citar como antecedente, no como PR del sprint actual |
| Tests fuente | Existen archivos de prueba | Registrar resultado nuevo al ejecutar |
| Issues/Project/Milestone | No evidenciado en copia; remoto no consultado | Crear/verificar en GitHub hoy |
| Sprint Planning | No realizada en esta revisión | Acta real en docs/metodologia/evidencias/.../planning.md |
| Daily Scrum | No evidenciado | Registrar del 20–23 y 26; no reconstruir |
| HU-001 PR y revisión | Pendiente | GitHub PR hacia develop |
| Sprint Review/Retro | Pendiente | Actas reales y demo el 26 |
| Métricas/burndown | No evidenciadas | Capturar datos desde Issues/PR/Project al cierre |
| Documento académico | Este archivo es borrador | Completar con nombres, enlaces y resultados reales |

Índice de evidencia requerido para cada archivo: **nombre; herramienta; fecha real; responsable; ubicación o URL; criterio que demuestra**. No guardar credenciales ni datos personales reales en capturas.

### Registro de evidencias para completar

La fecha de la propuesta no equivale a fecha de realización. Completar responsable, fecha y enlace solo al generar cada evidencia auténtica.

| N.° | Evidencia | Herramienta | Fecha real | Responsable | Ubicación/URL | Estado |
|---:|---|---|---|---|---|---|
| 1 | Snapshot del repositorio y tecnologías | Git local / GitHub | Revisión: 2026-10-19; HEAD observado: 2026-10-12 | [completar] | Repositorio y hash indicado arriba | Existe como análisis local |
| 2 | Historial de commits y merge PR #1 | Git local / GitHub | Hasta 2026-10-12 en la copia | [completar] | Repositorio / pestaña Commits | Antecedente, no evidencia del Sprint 1 |
| 3 | Acta de Sprint Planning | Reunión real + Markdown | [completar después de reunión] | Scrum Master [nombre] | `docs/metodologia/evidencias/.../planning.md` | Pendiente |
| 4 | Issue HU-001, Milestone y Sprint Backlog inicial | GitHub Issues/Projects | [completar después de crearlos] | PO / Developers | [enlaces] | Pendiente de verificar/crear |
| 5 | Registros Daily Scrum | Reunión o registro asíncrono | [fecha real de cada registro] | Developers; facilita Scrum Master | `docs/metodologia/evidencias/.../daily-YYYY-MM-DD.md` | Pendiente |
| 6 | PR, revisión y merge del incremento | GitHub | [fecha real] | Autor y revisor distinto | [URL PR a develop] | Pendiente |
| 7 | Resultado de pruebas aplicables | Terminal / GitHub Actions | [fecha real de ejecución] | Developer que ejecuta | [log o URL de Actions] | Pendiente; tests fuente existen, ejecución no verificada |
| 8 | Capturas/demo del incremento | App móvil / captura de pantalla | [fecha real] | Developer de demo | `docs/metodologia/evidencias/.../` | Pendiente |
| 9 | Acta de Sprint Review | Reunión real + Markdown | [completar después de reunión] | Scrum Master [nombre] | `docs/metodologia/evidencias/.../review.md` | Pendiente |
| 10 | Acta de Sprint Retrospective | Reunión real + Markdown | [completar después de reunión] | Scrum Master [nombre] | `docs/metodologia/evidencias/.../retrospective.md` | Pendiente |
| 11 | Métricas y burndown basados en datos diarios | GitHub Project + hoja/Markdown | [fecha de cierre] | Developers / Scrum Master | [archivo o enlace] | Pendiente |

## 12. Cronograma día por día hasta lunes 26

Las horas son estimaciones para organizarse; ajustarlas a los horarios reales del equipo. Hoy, 19 de octubre, es el inicio propuesto, no una Planning que ya haya ocurrido.

| Fecha | Actividad Scrum | Responsable sugerido | Herramienta | Evidencia a generar | Tiempo |
|---|---|---|---|---|---:|
| Lun 19 oct | Sprint Planning: acordar Goal, capacidad, HU-001, puntos y plan; crear/confirmar develop, Milestone, Project e Issue | Los 4; SM facilita, PO prioriza | GitHub, llamada | Acta, Issue, milestone y captura inicial, solo después de hacerlos | 60–90 min |
| Mar 20 oct | Daily; rastrear estado actual del reclamo y dependencias del entorno | Developers | GitHub/IDE | Registro Daily real; comentario técnico en Issue | 15 min Daily + 1–2 h |
| Mié 21 oct | Daily; desarrollar estado de envío persistente y UI | Developers móvil | IDE, GitHub | Rama/commits reales, tarjeta actualizada | 15 min + 2–3 h |
| Jue 22 oct | Daily; pruebas de éxito, espera y rechazo; abrir PR temprana | Developers | IDE/Actions/GitHub | PR borrador o lista; logs reales | 15 min + 2–3 h |
| Vie 23 oct | Daily; revisión por compañero y correcciones; congelar nuevas funciones | Developers | GitHub | Revisión real, criterios marcados y bloqueos | 15 min + 1–2 h |
| Sáb 24 oct | Sin actividad planificada; solo si el equipo decide trabajar, registrar trabajo real y hacer Daily | Voluntario/equipo | GitHub | Sin evidencia si no se trabajó; si sí, registro real | 0 h planificadas |
| Dom 25 oct | Buffer documental opcional; no añadir alcance | PO + SM | Markdown/Docs | Revisar enlaces/estructura, no inventar resultados | 0–30 min |
| Lun 26 oct | Daily breve; demo, Sprint Review, Retrospective, métricas, cerrar sprint y entregar documento | Los 4 | App, GitHub, Markdown/PDF | Actas, demo, estado real, enlace/confirmación de entrega | 60–90 min + entrega |

Si el build habitual está bloqueado por configuración Firebase indicada como faltante en el documento del proveedor, confirmarlo el 19 y elegir un entorno permitido que pruebe realmente el comportamiento. No declarar prueba de sincronización remota usando una compilación local que no se conectó al servicio.

## 13. Conclusiones del sprint

Completar después del 26:

- ¿Se realizaron Planning, Dailies, Review y Retrospective? Incluir fechas y enlaces.
- ¿Se cumplió el Sprint Goal? [Sí/parcial/no] y evidencia.
- ¿Qué incremento quedó integrado a develop? [PR/hash, criterios cumplidos].
- ¿Qué quedó pendiente y por qué? [dependencias/bloqueos].
- ¿Qué adaptación concreta acordó el equipo para el siguiente sprint?
- Evaluación del equipo: [observaciones reales, no respuestas preparadas de antemano].

## 14. Plantillas listas para copiar

### 14.1 Acta de Sprint Planning

**Fecha/hora real:**  
**Asistentes:**  
**Sprint:** Sprint 1 — [fechas]  
**Product Goal:**  
**¿Por qué este sprint es valioso?:**  
**Sprint Goal acordado:**  
**¿Qué se puede completar?:**  
**Historias seleccionadas:**  
**Estimaciones Planning Poker:** [votos/consenso, no nombres si quieren anonimizar]  
**¿Cómo se realizará el trabajo?:**  
**Sprint Backlog/tareas:**  
**Capacidad y disponibilidad confirmadas:**  
**Riesgos/dependencias:**  
**Definition of Done:**  
**Decisiones y responsable del acta:**  

### 14.2 Registro Daily Scrum

**Fecha:**  
**Participantes / modalidad:**  
**Ayer avancé hacia el Sprint Goal:**  
**Hoy haré:**  
**Impedimentos:**  
**Issue/tarjeta que actualicé:**  
**Acción de desbloqueo, responsable y fecha:**  

### 14.3 Acta Sprint Review

**Fecha y asistentes:**  
**Sprint Goal:**  
**Incremento demostrado:**  
**Issue/PR/commit:**  
**Criterios cumplidos y evidencia:**  
**Criterios pendientes y motivo:**  
**Pruebas realmente ejecutadas y resultado:**  
**Observaciones del PO/usuario/docente:**  
**Cambios al Product Backlog:**  
**Capturas/enlaces:**  

### 14.4 Acta Sprint Retrospective

**Fecha y asistentes:**  
**Qué salió bien:**  
**Qué fue difícil:**  
**Qué mantener:**  
**Qué dejar de hacer:**  
**Qué probar en Sprint 2:**  
**Acción, responsable y fecha:**  

### 14.5 Historia de usuario

**ID/título:**  
**Como [rol], quiero [acción], para [beneficio].**  
**Descripción funcional:**  
**Estado verificado en el producto:**  
**Criterios de aceptación:**
- [ ]  
- [ ]  
- [ ]  

**Prioridad:**  
**Votos Planning Poker/estimación acordada:**  
**Valor para usuario:**  
**Riesgo técnico/dependencias:**  
**Responsable sugerido:**  
**Tareas técnicas:**  
**Estado Scrum actual:**  
**Evidencia esperada:**  
**Enlace a Issue/PR/resultado real:**  

### 14.6 Product Backlog

| Orden | ID | Historia | Prioridad | Valor | Riesgo/dependencias | Puntos | Estado | Issue |
|---:|---|---|---|---|---|---:|---|---|
| 1 | HU-001 | Estado real de envío de reclamo | Alta | Alto | Outbox y estado de revisión | 5 tentativos | Pendiente; candidata a Sprint 1 | [crear/enlace] |
| 2 | HU-002 | Envío idempotente de reclamo | Alta | Alto | Entrega sincronizada | 5 tentativos | Pendiente/verificar | [enlace] |
| 3 | HU-003 | Consultar resolución del reclamo | Alta | Alto | API de lectura | 8 tentativos | Pendiente | [enlace] |
| 4 | HU-004 | Sincronizar traslado | Alta | Alto | API y auditoría | 8 tentativos | Pendiente | [enlace] |
| 5 | HU-005 | Enviar evidencia fotográfica | Media | Medio/alto | Almacenamiento seguro | 8 tentativos | Pendiente | [enlace] |
| 6 | HU-006 | Consultar liquidaciones | Alta | Alto | Prueba de integración | 3 tentativos | Pendiente/verificar | [enlace] |
| 7 | HU-007 | Recibir comunicados | Media | Medio | Sincronización | 3 tentativos | Pendiente/verificar | [enlace] |
| 8 | HU-008 | Sincronizar entrega offline | Alta | Alto | Conflictos/conectividad | 5 tentativos | Pendiente/verificar | [enlace] |
| 9 | HU-009 | Validar OCR de calidad | Media | Medio | Cámara/plataforma | 5 tentativos | Pendiente/verificar | [enlace] |

### 14.7 Sprint Backlog

| ID tarea | Historia | Tarea | Responsable | Estimación horas | Estado | Evidencia |
|---|---|---|---|---:|---|---|
| T1 | HU-001 | Trazar éxito/reintento/error | [Developer] | [ ] | Pendiente | [enlace] |
| T2 | HU-001 | Persistir estado de transporte por separado | [Developer] | [ ] | Pendiente | [PR] |
| T3 | HU-001 | Actualizar UI de solicitudes | [Developer] | [ ] | Pendiente | [captura] |
| T4 | HU-001 | Probar éxito, espera, rechazo y persistencia | [Developer] | [ ] | Pendiente | [log] |
| T5 | HU-001 | Revisar PR y preparar demo | [autor/revisor/PO] | [ ] | Pendiente | [enlace] |

### 14.8 Planning Poker

| Historia | Voto Developer 1 | Voto Developer 2 | Consenso Developers | Motivo de diferencia |
|---|---:|---:|---:|---:|---|
| HU-001 | [ ] | [ ] | [ ] | [ ] |

Los Developers acuerdan la estimación. PO aporta aclaraciones de valor y alcance; Scrum Master facilita el proceso. Si el equipo acuerda que PO o Scrum Master también aportan trabajo como Developers, su voto se registra como parte de Developers y no por su otra responsabilidad.

### 14.9 Métricas Scrum

| Métrica | Planificado | Real | Fuente/fecha | Nota |
|---|---:|---:|---|---|
| Historias del Sprint | [ ] | [ ] | Milestone/Project | |
| Historias Done | [ ] | [ ] | Issues + DoD | |
| Puntos comprometidos/completados | [ ] | [ ] | Planning/Review | |
| Issues cerrados | [ ] | [ ] | GitHub | |
| PRs creadas/revisadas/aprobadas/mergeadas | [ ] | [ ] | GitHub | |
| Pruebas planificadas/ejecutadas/pasadas/fallidas | [ ] | [ ] | Actions/logs | |
| Sprint Goal | N/A | [cumplido/parcial/no] | Review | |
| Velocidad del sprint | [ ] | [ ] | Puntos Done | Primera observación; no comparar personas |

### 14.10 Burn-down

Registrar cada día laborable el trabajo restante de las historias comprometidas. Mantener la misma unidad (puntos restantes o horas restantes de tareas). No cambiar la unidad a mitad del sprint.

| Día | Restante ideal (puntos) | Restante real (puntos) | Fuente/nota |
|---|---:|---:|---|
| Inicio | 5 | 5 | Planning, si se confirma HU-001 |
| Mar 20 | [ ] | [ ] | |
| Mié 21 | [ ] | [ ] | |
| Jue 22 | [ ] | [ ] | |
| Vie 23 | [ ] | [ ] | |
| Lun 26 | 0 | [ ] | Solo 0 si HU-001 está Done |

Graficar días en eje horizontal y trabajo restante en eje vertical. La línea ideal es referencia; la real se calcula desde la tabla. Si el equipo no actualizó un día, dejar el dato vacío o “No medido”, no estimarlo retrospectivamente.

## Resumen ejecutivo y pasos inmediatos

1. La copia local revisada corresponde a APP MOVIL / Ecolecta Huata, una aplicación móvil multiplataforma con API Laravel y panel web.
2. Hay módulos y pruebas fuente versionados; su ejecución y preparación de producción no se verificaron.
3. El historial muestra cambios incrementales y un merge de PR, pero no evidencia suficiente de Scrum formal.
4. Issues, Projects, milestones, ramas remotas y PRs actuales no se pudieron comprobar porque GitHub no cargó.
5. El curso exige Scrum como marco principal; el tablero solo visualizará el Sprint Backlog.
6. Se propone Sprint 1 del 19 al 26 de octubre con HU-001 y un incremento pequeño sobre el estado de envío de reclamos.
7. Los roles se proponen como un PO, un Scrum Master y dos Developers; deben acordarse y completar nombres reales.
8. No se han inventado actas, votos, pruebas, commits ni aprobaciones; están marcados como pendientes hasta realizarlos.

**Metodología recomendada:** Scrum, porque permite fijar un objetivo corto, entregar un incremento inspeccionable y dejar evidencias de planificación, revisión y adaptación con cuatro integrantes.

**Acciones para esta semana:**

1. **Lun 19:** consultar GitHub, acordar nombres/roles, celebrar Planning, validar HU-001 y sus puntos; crear/confirmar `develop`, Issue, Milestone y Project si no existen.
2. **Mar 20:** reproducir el flujo actual de reclamos y acordar casos de aceptación y Definition of Done.
3. **Mié 21–jue 22:** implementar el alcance seleccionado y registrar commits; abrir PR hacia `develop` cuando esté revisable.
4. **Vie 23:** revisión real por compañero, ejecutar pruebas aplicables y corregir hallazgos.
5. **Lun 26:** mostrar el incremento, celebrar Review y Retrospective, completar métricas con datos reales y entregar el documento con enlaces/capturas.

Si un evento no se realiza o una prueba no corre, registrar el hecho y el impedimento, dejar el resultado como pendiente y evitar reportarlo como evidencia cumplida.

