# Reconstrucción retrospectiva de Scrum — Ecolactea Digital

> **Antecedente conservado:** consultar [docs/README.md](../README.md) para el paquete vigente de Scrum con prácticas Kanban, catálogo HU-01 a HU-15 y trazabilidad revisada. Los IDs y simulaciones de este borrador son históricos y no se incorporan como registros de eventos al paquete vigente.

**Periodo solicitado:** 14 de septiembre al 19 de octubre de 2026  
**Corte de revisión:** 19 de octubre de 2026  
**Repositorio revisado:** `SaitAlfre/APP-MOVIL`  
**Estado de este documento:** reconstrucción retrospectiva basada en el historial local; no es evidencia contemporánea de que se celebraran eventos Scrum.

> **Nota de integridad académica:** el historial permite comprobar cambios de código, fechas y autores de commits. No permite afirmar que se hicieron Sprint Planning, Daily Scrum, Sprint Review, retrospectivas, estimaciones o validaciones con el beneficiario. Por eso, las ceremonias de ejemplo están marcadas como **simulación didáctica**, las conclusiones inferidas como **reconstrucción**, y los hechos sin respaldo como **no evidenciados**. No se han creado commits ni reuniones ficticias.

## 1. Metodología seleccionada

Se propone **Scrum** como metodología principal para documentar el trabajo del equipo de cuatro personas. El producto contiene una app multiplataforma, persistencia local, sincronización con un panel web y módulos que se benefician de revisión frecuente con el beneficiario. El requisito offline y la integración entre móvil y servidor implican incertidumbre técnica; trabajar hacia un Sprint Goal y revisar incrementos pequeños facilita detectar problemas de sincronización y ajustar prioridades.

La elección de Scrum en esta documentación es una propuesta para ordenar el trabajo y los siguientes ciclos. **No significa que Scrum haya sido aplicado formalmente durante el periodo reconstruido.** El repositorio contiene señales de desarrollo incremental, integración móvil-web, cambios por módulo y pruebas fuente, pero no contiene evidencia suficiente de los artefactos y eventos Scrum.

### Por qué encaja con el proyecto

- **Incrementos demostrables:** acopio, calidad, reportes y sincronización pueden revisarse en entregas pequeñas.
- **Adaptación:** las reglas de operación de la planta y el comportamiento offline se pueden ajustar luego de observar la app con el beneficiario.
- **Transparencia:** el Product Backlog, Sprint Goal, criterios y estado de pruebas pueden quedar visibles en Issues y documentos.
- **Equipo pequeño:** con cuatro personas se pueden cubrir PO, Scrum Master y Developers, manteniendo los nombres y responsabilidades reales por confirmar.

## 2. Alcance y calidad de la reconstrucción

La copia local apunta al commit `05743b97f2e5835250e223eabc09f9924b7524bb`, del 12 de octubre de 2026. En el rango solicitado el historial contiene **22 commits**, con fechas de commit observadas el 17, 20, 22, 23 y 24 de septiembre, y el 5 y 12 de octubre. No hay commits fechados entre el 14 y el 16 de septiembre ni entre el 6 y el 11 de octubre. Esto no demuestra que el equipo no trabajara en esos días; solo significa que ese trabajo no aparece como commit en la copia examinada.

La historia local muestra `main`, `origin/main` y `origin/HEAD`; se ve un merge cuyo mensaje identifica PR #1. No se encontró evidencia local de issues, milestones, un tablero de sprint o actas. El estado remoto de GitHub no se verificó en esta reconstrucción.

El historial tiene tres correos de autor distintos y cuatro nombres visibles, pues un merge aparece bajo `AWA Dubcek`. Se observan cambios atribuidos a Sait Alfredo Gutierrez Velasquez, Dubcek Leonel y Erick Maycol Quispe Valencia. Esto **no confirma** cómo se distribuyeron los cuatro integrantes del equipo ni quién asumió cada responsabilidad. Completar el cuarto integrante y los roles con información real del grupo.

En los archivos del repo aparece el nombre **Ecolecta Huata / Ecolecta**. Este documento usa **Ecolactea Digital** porque así lo indicó el equipo; confirmar con el docente cuál es el nombre oficial que debe figurar en la entrega.

### Etiquetas usadas en las tablas

| Etiqueta | Significado |
|---|---|
| **VERIFICADO** | Observable en el historial o los archivos de la copia local. |
| **RECONSTRUIDO** | Agrupación retrospectiva de cambios para explicar una posible secuencia de trabajo; no equivale a planificación contemporánea. |
| **SIMULACIÓN DIDÁCTICA** | Ejemplo hipotético de una ceremonia; no registrar como reunión efectuada ni como asistencia real. |
| **NO EVIDENCIADO** | No se encontró respaldo en la copia revisada; debe comprobarse con el equipo o el beneficiario. |

## 3. Product Backlog inicial reconstruido

Este backlog se deriva de los módulos observables y de las necesidades expresadas por el equipo. No hay evidencia de que existiera con este orden el 14 de septiembre. Las prioridades y criterios siguientes son una propuesta para que el PO y los Developers la validen.

| Orden | ID | Historia de usuario | Prioridad | Criterios de aceptación resumidos | Estado según repo al corte |
|---:|---|---|---|---|---|
| 1 | PB-01 | Como acopiador, quiero registrar entregas por proveedor y jornada aun sin internet, para continuar el acopio en planta o ruta. | Alta | La entrega se guarda localmente; muestra su estado de sincronización; una reconexión permite reintentar sin duplicar. | Hay módulos de acopio y almacenamiento/sincronización local. La prueba de operación offline en dispositivo real no está evidenciada. |
| 2 | PB-02 | Como administrador, quiero recibir los cambios del móvil y ver su estado, para mantener los datos del panel y de la app coherentes. | Alta | El servidor acepta cambios autorizados; un error no aparece como sincronización exitosa; el reintento conserva la identidad del registro. | Los commits del 24-sep muestran integración app-panel y casos de prueba fuente. Ejecución de pruebas no comprobada aquí. |
| 3 | PB-03 | Como técnico de calidad, quiero registrar y consultar análisis por proveedor, para evaluar la leche acopiada. | Alta | El técnico identifica proveedor/zona; registra parámetros y observaciones; el panel muestra el análisis asociado. | Módulo móvil/web y cambios de calidad visibles en commits del 20 y 24-sep; escáner LACTOMAT en commit del 05-oct. |
| 4 | PB-04 | Como responsable de planta, quiero consultar reportes de acopio y liquidaciones, para revisar volúmenes y pagos. | Alta | Los totales tienen periodo explícito; el reporte permite distinguir datos reales de ejemplos; la liquidación se vincula al proveedor. | Vistas y lógica de reportes/liquidaciones aparecen en el proyecto. Validación de cifras con el beneficiario no evidenciada. |
| 5 | PB-05 | Como usuario, quiero recibir alertas y comunicados relevantes, para enterarme de cambios operativos. | Media | Se identifica el tipo de aviso, fecha y estado; al abrirlo se conserva el registro fuente. | La documentación `ADMIN_MOVIL.md` describe alertas y comunicados. Una función de notificación push no está evidenciada por esta revisión. |
| 6 | PB-06 | Como equipo de planta, quiero registrar reuniones y acuerdos operativos, para coordinar el trabajo y conservar decisiones. | Media | Se registra fecha, participantes, acuerdos y responsable de acción; los datos quedan consultables. | **No evidenciado en el repo revisado.** Confirmar si es un módulo requerido o un entregable de gestión académica. |
| 7 | PB-07 | Como beneficiario de la planta, quiero probar los flujos de acopio, calidad y reportes, para confirmar que representan el trabajo real de Huata. | Alta | El beneficiario observa una demo; sus comentarios, aceptación y cambios acordados se registran con fecha real. | **No evidenciado en el repo revisado.** Se necesita acta o validación auténtica, no una simulación. |
| 8 | PB-08 | Como administrador, quiero visualizar rutas y zonas de acopio, para organizar el recojo y revisar recorridos. | Media | Se pueden consultar y guardar rutas; la información de demostración se distingue de la real. | Mapas por sector y ruta demo aparecen en commits del 05-oct y ajustes del 12-oct. |

Las estimaciones en puntos no se reconstruyen porque no hay votos de Planning Poker en el repositorio. Si se requieren, obtenerlos ahora de los Developers y etiquetarlos como estimación retrospectiva, con su fecha real.

## 4. Ventanas de trabajo agrupadas como sprints retrospectivos

Las fechas de abajo son intervalos analíticos que encajan en el periodo pedido. Los objetivos y Sprint Backlogs son **reconstruidos a partir de commits**; no prueban que el equipo los acordara antes de programar. El último intervalo es parcial al corte del 19 de octubre.

### Sprint retrospectivo 1 — Base funcional e integración

| Campo | Reconstrucción |
|---|---|
| Fechas del intervalo | 14–27 de septiembre de 2026 |
| Sprint Goal reconstruido | Consolidar los flujos móviles de acopio y calidad, avanzar el panel administrativo y comenzar la integración entre app y servidor. |
| Responsables | Autores verificables de los commits listados abajo; la asignación de roles Scrum no está evidenciada. |
| Entregable verificable | Cambios de código de app/panel, integración y fuentes de prueba incluidas en los commits. No equivale a aceptación del beneficiario ni a release validado. |

**Sprint Backlog reconstruido**

| Tarea reconstruida | Resultado observable | Autor de commit visible / evidencia |
|---|---|---|
| Actualizar módulos y estructura del producto | Cambios de módulos en app y panel | Sait Alfredo Gutierrez Velasquez — `1fb3dab` (17-sep) |
| Desarrollar el flujo móvil de control de calidad y rediseñar el panel | Cambios en calidad móvil y panel | Dubcek Leonel — `6104b68` (20-sep) |
| Ajustar la interfaz del panel y los módulos de acopio/administración | Rediseño web y actualización de módulos móviles | Dubcek Leonel — `5af8d7d`, `e30a743` (23-sep) |
| Organizar lista de acopio por zona/ciclo y tiempo local | Cambios en pantallas del acopiador | Sait Alfredo Gutierrez Velasquez — `2b7a212`, `a3375b7` (24-sep) |
| Integrar app, panel, QR y envío de cambios | Cliente móvil, sincronización, datos de proveedor y pruebas fuente | Sait Alfredo Gutierrez Velasquez — `5c1bcf8`, `193578b` (24-sep) |
| Preparar conexión web y probar sincronización/datos | Configuración Heroku, autenticación/conexión y pruebas fuente | Dubcek Leonel — `02fdd80`, `3d853e2`, `c52cad4`, `138ea22`, `036475d`, `dcb6ebc`, `50972a4` (24-sep) |
| Integrar un cambio mediante PR | El historial local registra un merge con referencia a PR #1 | `af6ed93` (24-sep); el commit `260f98d` también registra merge de `origin/main` |

**Sprint Review — reconstrucción, no acta:** el historial muestra cambios en app, panel y sincronización; la rama local conserva un merge con referencia a PR #1. No hay demo, lista de criterios aceptados, comentario del PO ni validación del beneficiario en la copia revisada. Resultado académico: incremento de software verificable, aceptación funcional **no evidenciada**.

**Retrospectiva — simulación didáctica:**

- Posible fortaleza a inspeccionar: hubo integración app-panel y se añadieron pruebas fuente durante el trabajo.
- Riesgo a discutir con el equipo: muchos cambios de varios módulos aparecen concentrados en commits del 24 de septiembre, lo que dificulta relacionar cada cambio con una historia o criterio.
- Acción propuesta para el siguiente sprint: separar Issues por historia/tarea, enlazar PR y mantener evidencia de pruebas. **No es una decisión de retrospectiva real.**

### Sprint retrospectivo 2 — Calidad y rutas

| Campo | Reconstrucción |
|---|---|
| Fechas del intervalo | 28 de septiembre–11 de octubre de 2026 |
| Sprint Goal reconstruido | Mejorar la captura de calidad y apoyar la planificación de rutas de acopio con mapas. |
| Responsables | Erick Maycol Quispe Valencia figura como autor de los dos commits del intervalo observados. Esto no demuestra asignación Scrum ni autoría exclusiva del módulo. |
| Entregable verificable | Escáner LACTOMAT, pruebas fuente de calidad, mapa de rutas por sector y ruta de demostración en el código. |

**Sprint Backlog reconstruido**

| Tarea reconstruida | Resultado observable | Autor / evidencia |
|---|---|---|
| Añadir escáner de comprobante LACTOMAT al flujo de calidad | Cambios de parser, escáner Android y pruebas fuente | Erick Maycol Quispe Valencia — `991a970` (05-oct) |
| Añadir mapas de rutas por sector y ruta demo | Controlador, modelo/rutas, vistas y seeder de demostración | Erick Maycol Quispe Valencia — `9e3ae54` (05-oct) |

**Sprint Review — reconstrucción, no acta:** el diff muestra escaneo y mapas, pero no hay evidencia de demo, pruebas ejecutadas, aprobación del PO ni validación de ruta con el beneficiario. La existencia de pruebas fuente no demuestra que hayan pasado.

**Retrospectiva — simulación didáctica:**

- Pregunta para el equipo: ¿el escaneo reduce tiempo y errores en el comprobante real de Huata?
- Riesgo observado: la captura OCR y las rutas requieren comprobación en dispositivos y datos de campo; el repositorio por sí solo no confirma ese uso.
- Acción propuesta: registrar casos de prueba en Android y una sesión de validación de mapa con el beneficiario, con fecha y participantes reales. No se afirma que ocurriera.

### Sprint retrospectivo 3 — Seguridad y ajuste de mapas (parcial)

| Campo | Reconstrucción |
|---|---|
| Fechas del intervalo | 12–19 de octubre de 2026; ventana parcial al corte de esta revisión. |
| Sprint Goal reconstruido | Mejorar el flujo de rutas y la seguridad de las sesiones móviles mediante refresh tokens. |
| Responsables | Erick Maycol Quispe Valencia aparece como autor de los commits del 12-oct. |
| Entregable verificable | Ajustes del armado de rutas y cambios de refresh tokens/sincronización, con pruebas fuente añadidas o modificadas en el commit. |

**Sprint Backlog reconstruido**

| Tarea reconstruida | Resultado observable | Autor / evidencia |
|---|---|---|
| Simplificar armado de rutas y navegación web | Refactor del controlador, rutas y mapa | Erick Maycol Quispe Valencia — `52dab50` (12-oct) |
| Incorporar refresh tokens y ajustar sincronización/mapas | Cambios móviles, API, persistencia de token y pruebas fuente | Erick Maycol Quispe Valencia — `05743b9` (12-oct) |

**Sprint Review — reconstrucción, no acta:** los cambios del 12 de octubre son verificables en el repo. A fecha de corte no hay acta, demo, criterios de aceptación ni resultado de pruebas ejecutadas que permitan declararlo incremento aceptado.

**Retrospectiva — simulación didáctica:**

- Punto por discutir: cómo verificar renovación de token, expiración y sincronización ante pérdida de red.
- Riesgo: concluir que una prueba pasa solo porque existe un archivo de test.
- Acción propuesta: ejecutar el conjunto aplicable en un entorno conocido y guardar comando, fecha, resultado y versión; realizar revisión con PO/beneficiario antes de dar por cerrado el incremento.

## 5. Daily Scrum: simulaciones didácticas

No hay registros Daily, listas de asistencia ni notas de reunión en la copia revisada. Las siguientes líneas son **ejemplos hipotéticos**, no declaraciones de que el equipo se reuniera. La columna “actividad observable” se limita a cambios que aparecen en el historial en esa fecha.

| Fecha de ejemplo | Sprint | Ayer | Hoy / actividad observable usada para el ejemplo | Impedimentos |
|---|---|---|---|---|
| 2026-09-20 | 1 | No reconstruible con el historial | El commit `6104b68` registra cambios de calidad móvil y panel ese día; un Daily real habría actualizado el Issue relacionado. | No evidenciados. |
| 2026-09-24 | 1 | No reconstruible por participante | Varios commits registran integración y sincronización app-panel; este es un resumen del historial, no una intervención de un integrante. | No evidenciados. |
| 2026-10-05 | 2 | No reconstruible con el historial | Los commits `991a970` y `9e3ae54` registran escáner LACTOMAT y mapas de rutas. | No evidenciados. |
| 2026-10-12 | 3 parcial | No reconstruible con el historial | Los commits `52dab50` y `05743b9` registran ajustes de mapas, tokens y sincronización. | No evidenciados. |

**Formato para sustituir estos ejemplos por registros auténticos futuros:** fecha y hora; participantes reales; Sprint Goal; avance hacia el objetivo; plan de las próximas 24 horas; impedimentos y responsable de removerlos; Issue actualizado. No recrear conversaciones pasadas de memoria como si fueran actas contemporáneas.

## 6. Roles para el equipo de cuatro

La identidad y asignación de roles del equipo no está documentada en el repo. Esta distribución es una **propuesta para los siguientes sprints**; completar nombres y aceptación real del grupo.

| Responsabilidad Scrum | Asignación sugerida | Trabajo/evidencia |
|---|---|---|
| Product Owner | Integrante 1: [nombre por confirmar] | Representar necesidades del beneficiario; ordenar el Product Backlog; acordar criterios; inspeccionar incrementos. Evidencia: backlog priorizado y registro auténtico de feedback. |
| Scrum Master | Integrante 2: [nombre por confirmar] | Facilitar eventos, hacer visibles impedimentos y ayudar a que Scrum se entienda; no ser jefe ni asignar tareas individuales. Evidencia: invitaciones/actas reales y acciones de mejora. |
| Developers | Integrantes 3 y 4: [nombres por confirmar] | Construir el incremento; estimar; cubrir móvil, API/datos, pruebas, UI y documentación según habilidades. Evidencia: Issues, PRs, pruebas ejecutadas y demo. |

El beneficiario no se asigna automáticamente como PO: el PO es parte del Scrum Team y representa necesidades; el beneficiario puede participar en refinamiento o Review si acepta la invitación. Su participación y comentarios solo se documentan cuando ocurran realmente.

## 7. Evidencia de commits y límites

Los hash cortos siguientes y sus fechas proceden del historial local. Los mensajes se mantienen como aparecen en Git. Son evidencia de cambios versionados, no de planificación Scrum ni de pruebas exitosas.

| Fecha | Commit | Autor visible | Mensaje |
|---|---|---|---|
| 2026-09-17 | `1fb3dab` | Sait Alfredo Gutierrez Velasquez | Actualizacion del proyecto y modulos |
| 2026-09-20 | `6104b68` | Dubcek Leonel | Modulo de control de calidad movil y rediseno del panel web |
| 2026-09-22 | `a9c1883` | Sait Alfredo Gutierrez Velasquez | Update project files |
| 2026-09-23 | `5af8d7d` | Dubcek Leonel | Rediseño del panel web con el mockup Lumen de Figma |
| 2026-09-23 | `e30a743` | Dubcek Leonel | Actualización de la app móvil: módulos de acopio y administración |
| 2026-09-24 | `2b7a212` | Sait Alfredo Gutierrez Velasquez | Lista de acopio por zona y ciclo; retiro del seguimiento GPS |
| 2026-09-24 | `a3375b7` | Sait Alfredo Gutierrez Velasquez | Hora de Perú en pantallas del acopiador y estado real del servidor en Perfil |
| 2026-09-24 | `5c1bcf8` | Sait Alfredo Gutierrez Velasquez | Sincronización app ↔ panel web, pagos del proveedor y QR compartible |
| 2026-09-24 | `02fdd80` | Dubcek Leonel | Preparar aplicación web para Heroku |
| 2026-09-24 | `193578b` | Sait Alfredo Gutierrez Velasquez | Sincronización de entregas: cuentas sin enlazar, remitente y límites separados |
| 2026-09-24 | `3d853e2` | Dubcek Leonel | Confiar en el proxy HTTPS de Heroku |
| 2026-09-24 | `af6ed93` | AWA Dubcek | Merge pull request #1 from SaitAlfre/sincronizacion-entregas-enlace |
| 2026-09-24 | `260f98d` | Dubcek Leonel | Merge remote-tracking branch 'origin/main' |
| 2026-09-24 | `c52cad4` | Dubcek Leonel | Conectar APK a Heroku y mostrar estado real del enlace |
| 2026-09-24 | `138ea22` | Dubcek Leonel | Agregar escenario peruano de prueba para los módulos del panel |
| 2026-09-24 | `036475d` | Dubcek Leonel | Sincronizar datos del panel web hacia la app móvil |
| 2026-09-24 | `dcb6ebc` | Dubcek Leonel | Enviar al panel web los cambios hechos en la app móvil |
| 2026-09-24 | `50972a4` | Dubcek Leonel | Rehacer el control de calidad de la web igual al de la app móvil |
| 2026-10-05 | `991a970` | Erick Maycol Quispe Valencia | Agregar escaner LACTOMAT para control de calidad |
| 2026-10-05 | `9e3ae54` | Erick Maycol Quispe Valencia | Agregar mapas de rutas por sector y ruta de demostracion |
| 2026-10-12 | `52dab50` | Erick Maycol Quispe Valencia | Simplificar armado de rutas y limpiar navegacion web |
| 2026-10-12 | `05743b9` | Erick Maycol Quispe Valencia | Agregar refresh tokens seguros y corregir sincronizacion y mapas |

### Qué cuenta como evidencia y qué queda pendiente

| Elemento | Estado al corte del 2026-10-19 | Evidencia que se debe generar sin retrofechar |
|---|---|---|
| Commits | **Verificado:** 22 commits en el rango; último commit observado el 12-oct. | Enlaces a hashes en GitHub si el remoto está disponible. |
| Integración por PR | **Verificado parcialmente:** hay un merge con referencia PR #1 en el log local. | Consultar PR remoto para conocer conversación, revisores, checks y fecha de merge. |
| Pruebas | **Verificado:** hay archivos de pruebas en el historial/diff. **No evidenciado:** ejecución y resultado al corte de esta revisión. | Ejecutar pruebas hoy, registrar commit, comando, entorno, fecha y salida real. |
| Offline | **Verificado parcialmente:** arquitectura local y sincronización/cambios pendientes aparecen en código. **No evidenciado:** prueba en campo de pérdida/restablecimiento de red ni validación del beneficiario. | Registrar una prueba reproducible offline/online con datos de prueba. |
| Reuniones, Daily, Planning, Review y Retro | **No evidenciado** en la copia. | Realizar desde hoy en adelante y fechar actas con la fecha real; para el pasado, mantenerlo como reconstrucción. |
| Issues, milestones y Projects | **No evidenciado** localmente; remoto no comprobado. | Revisar GitHub antes de crear nuevos para evitar duplicados; crear lo que falte con enlaces a este documento. |
| Validación del beneficiario | **No evidenciado.** | Coordinar demo/entrevista real, solicitar permiso y registrar participantes, fecha, comentarios y acciones. |

## 8. Alineación con entregables académicos

La rúbrica específica del curso no está incluida en el repo. Esta matriz alinea la documentación con los módulos que indicó el equipo y con pruebas que podrían demostrarse. Ajustar nombres a la guía entregada por el docente.

| Entregable | Módulo/criterio | Estado real que se puede sostener | Evidencia o acción pendiente |
|---|---|---|---|
| Aplicación móvil multiplataforma | Acopio y operación offline | Hay código de app y almacenamiento/sincronización local. La prueba real de desconexión no está demostrada por este documento. | Demo Android/iOS disponible; capturar ciclo offline→online con datos ficticios. |
| Control de calidad | Captura móvil, panel y escáner LACTOMAT | Cambios de calidad visibles en commits; pruebas fuente existen. Resultado de ejecución y exactitud del OCR no evidenciados. | Ejecutar pruebas; probar comprobante ficticio; registrar corrección manual. |
| Reportes | Volúmenes y pagos/liquidaciones | Hay módulo web/documentación de reportes y pagos. Exactitud confirmada por beneficiario no evidenciada. | Validar periodo, litros y cálculos con conjunto autorizado por la planta. |
| Reuniones | Agenda/acuerdos operativos | No se encontró módulo de reuniones en la búsqueda local de código/documentación revisada. | Confirmar si es función del producto o si el curso pide actas de equipo. No describirlo como implementado sin prueba. |
| Notificaciones | Alertas/comunicados | La documentación describe bandeja de alertas y comunicados; notificaciones push no evidenciadas. | Precisar alcance requerido y demostrar solo el canal que realmente existe. |
| Gestión Scrum | Backlog y sprints | Reconstrucción analítica creada ahora; no demuestra Scrum histórico. | Acordar Product Goal, roles, Sprint Goal y DoD reales para el siguiente Sprint. |
| Validación con beneficiario | Usabilidad y reglas de trabajo de Huata | No evidenciada en la copia. | Acta auténtica, feedback y cambios derivados con fechas actuales. |
| Calidad de software | Pruebas y revisión | Fuentes de pruebas y merge PR #1 están en el historial. No hay resultados de CI ni ejecución confirmados aquí. | Ejecutar y adjuntar log; revisión de compañero en PR real. |

## 9. Uso correcto de esta reconstrucción

1. Conservar los timestamps originales de Git y citar los hashes reales.
2. Presentar los sprints como **agrupación retrospectiva** si se mantiene esta estructura; no afirmar que fueron planificados en esas fechas sin actas contemporáneas.
3. Mantener las Daily, Reviews y Retrospectives marcadas como simulación mientras no se realice una reunión real.
4. Si los cuatro integrantes recuerdan trabajo adicional, añadirlo en una sección separada con autoría y fuente (“recuerdo del equipo”, fecha de reconstrucción), sin atribuirle una fecha falsa.
5. Realizar desde el 19 de octubre un ciclo Scrum auténtico: Planning con nombres reales, Sprint Backlog, Daily real o transparencia sobre su formato, Review, Retrospective y feedback del beneficiario cuando esté disponible.
6. No usar estimaciones, validaciones, resultados de pruebas o aprobaciones simuladas como evidencia académica de ejecución.

## 10. Conclusión

La evidencia disponible muestra un producto avanzado con actividad incremental: acopio móvil, calidad, integración con panel, sincronización, despliegue preparado, mapas de ruta y refresh tokens. El repositorio no prueba que el equipo aplicara Scrum entre el 14 de septiembre y el 19 de octubre; tampoco prueba que no hubiera coordinación fuera de Git. La forma honesta de entregar este material es como **reconstrucción retrospectiva con sprints analíticos**, acompañada por commits auténticos y por la declaración explícita de las ceremonias, estimaciones y validaciones que no se pueden verificar.
