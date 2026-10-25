# Mantener la documentación al cambiar el software

**Responsable:** autor del cambio; revisión por compañero. PO cuida valor/requisitos; SM facilita consistencia y seguimiento; nadie concentra toda la documentación. Fecha de actualización: 2026-10-23.

## Ciclo de cambio en 13 pasos

| Paso | Acción concreta en Ecolactea | Quién / cuándo | Fuente a actualizar |
|---|---|---|---|
| 1 | Identificar necesidad, corrección, plataforma y riesgo; describir antes/después. | Autor, al detectar cambio. | Tarea o defecto, no un acta histórica. |
| 2 | Localizar RF/RNF, HU y CA afectados. Si es nuevo, proponer ID sin reutilizar otro. | Autor con PO, antes de seleccionar. | Requisitos y Product Backlog. |
| 3 | Ajustar CA y Gherkin; acordar regla de negocio y conservar motivo/fecha de cambio. | PO y Developers, refinamiento. | Product Backlog; casos derivados. |
| 4 | Registrar tareas pequeñas y responsable real; separar SP/horas. | Developers, antes de trabajar. | Tareas. |
| 5 | Vincular tarea al sprint y tarjeta; comprobar dependencias/WIP. | Autor, al seleccionar/iniciar. | Sprint y fuente operativa de estados. |
| 6 | Desarrollar/corregir en rama del flujo acordado. | Autor técnico, durante tarea. | Código y referencias; conservar el alcance del cambio. El 22-10 se corrigieron tres tests PHP y el arranque Android por ANR (dos archivos); sin reglas de negocio alteradas. |
| 7 | Cuando se ejecute, registrar caso, versión, entorno y pasos. | Ejecutante, al probar. | Nueva ejecución; no sobrescribir otra. |
| 8 | Conservar resultado observado y crear defecto con reproducción si corresponde. | Ejecutante, inmediatamente. | Ejecución, evidencia saneada y BUG. |
| 9 | Vincular hash real y PR con HU/tarea y resultados. | Autor, al preparar PR. | PR y evidencia; no inventar enlaces. |
| 10 | Solicitar revisión por otro integrante; registrar cambios pedidos y aprobación real. | Autor/revisor, antes de integrar. | PR, comentarios y evidencia requerida por F3. |
| 11 | Aplicar CA y DoD; si falta control, registrar bloqueo/pendiente y no cerrar HU. | Developers con revisor, al cerrar. | Fuente del estado y checklist DoD versionada. |
| 12 | Actualizar contrato, regla, datos, plataforma y guía afectados en el mismo PR. | Autor; compañero revisa. | Documentación técnica/funcional correspondiente. |
| 13 | Registrar resultado verificable para Review y cambios al backlog. | Autor y PO; SM facilita evento. | Review real, evidencias y backlog. |

## Qué documento cambia según el trabajo

| Cambio | Actualización mínima | Vistas derivadas a revisar |
|---|---|---|
| Regla de calidad/precio/sanción | RF/decisión autorizada → CA y ejemplos de HU → caso de prueba. | Trazabilidad, sprint si cambia alcance, OBS-01. |
| API/token | Contrato de [arquitectura](tecnica/arquitectura-datos-y-sincronizacion.md), CA, caso de API/permiso. | Consumidores Android/iOS/web, riesgos y DoD. |
| Base/migración | Modelo y compatibilidad/migración/retorno; auditoría e impacto offline. | Cola, importación/exportación, casos de regresión. |
| Sincronización | Entidad, ID, estado, confirmación, reintento, conflicto y destino. | HU-04/10, RNF-01/02/06/07, CP-002/007. |
| UI Android/iOS/web | Flujo visible, plataforma/versiones soportadas y mensajes. | CA, accesibilidad/usabilidad, casos y restricciones. |
| Defecto | BUG enlazado a ejecución, tarea correctiva, PR y nueva regresión. | CA solo si había ambigüedad; no cambiar esperado para ocultar fallo. |
| Versión | Resumen de cambios, hashes/PR, resultados y límites del incremento. | Evidencia de integración/despliegue y Review; no repetir todos los commits históricos. |

## Fuente única y vistas derivadas

Requisitos: RF/RNF; historias: Product Backlog; responsables: roles; alcance/horas de tareas: registro; estado operativo: Project privado y snapshots fechados; fechas/capacidad: calendario; evidencia: índice; resultados: ejecuciones; arquitectura/decisiones: documento técnico y decisiones fechadas en él. [Mapa completo](README.md#fuentes-principales).

No repetir el estado de una HU en sprint, matriz y tablero. Allí enlazar a la fuente. Si cambia un ID o vínculo, actualizar la vista de trazabilidad; no cambiar los IDs históricos. Si cambia solo una ejecución, conservar el caso y agregar otra ejecución.

## Ubicación y cambios de nombre

El [mapa de carpetas y fuentes](README.md#donde-mantener-cada-documento) determina dónde mantener un documento. Antes de crear otro, buscar si su contenido pertenece a una fuente existente. Las notas de módulos explican el uso de una versión; no duplican requisitos, historias ni contratos de API.

Al mover un Markdown: actualizar sus enlaces relativos y todos los enlaces entrantes; revisar también la raíz, índices y plantillas. No trasladar evidencias de una ejecución ni modificar borradores congelados para uniformar nombres. Mantener notas históricas fechadas y enlazar la fuente vigente cuando una afirmación haya quedado desactualizada. Comprobar las anclas del README principal además del verificador de `docs/`.

## Convenciones y revisión

- IDs: RF/RNF originales; HU-05-A y CA-HU-05-A-01 para hojas; T-XX; CP-XXX; EXE-AAAAmmdd-NN; BUG-XXX; EV-XXX. Fecha real ISO y zona America/Lima.
- Usar plantillas de [historia](plantillas/historia-usuario.md), [tarea](plantillas/tarea.md), [evento](plantillas/acta-evento-scrum.md), [caso](plantillas/caso-de-prueba.md), [ejecución](plantillas/ejecucion-prueba.md) y [defecto](plantillas/reporte-defecto.md).
- No editar resultados históricos; una rectificación lleva fecha/motivo y conserva el registro previo.
- Revisar enlaces relativos, IDs, oráculos autorizados, versión/plataforma, datos ficticios y alcance antes del PR.
- No publicar credenciales, tokens, DNI, contactos o capturas sin autorización. Ver [publicación](academico/archivos-documentacion.md).
- La revisión documental es parte del cambio; no sustituye pruebas del producto.

## Verificación automática obligatoria antes de revisión

~~~bash
python3 docs/herramientas/verificar_documentacion.py --export-kanban --json /tmp/ecolactea-documentacion.json
~~~

Requiere Python 3, Git y objetos de los 22 commits disponibles. Sin red ni credenciales. El export CSV/JSON se deriva de historias/tareas y no representa movimientos reales; no editarlo a mano. Para cotejar originales añadir `--fuentes-dir` con la carpeta del curso: compara las seis huellas sin copiar documentos empresariales. Revisar errores, resolverlos y repetir. Las dos advertencias de tablas pertenecen a un borrador congelado, con errata explícita; no se altera su antecedente.

La comprobación cubre archivos requeridos, enlaces/anclas, tablas activas, IDs/dependencias, CA/tareas/RF/RNF, estados permitidos de matriz, EP y patrones sensibles. No puede confirmar consentimiento, reuniones, calidad semántica, reglas empresariales o capturas; el compañero debe revisarlos. Tras cambiar resultados, actualizar su JSON canónico, informe, gráfico y DoD pertinentes. No sumar reintentos ni editar resultados de intentos anteriores.

## Resultados derivados

Los [ejecutores](herramientas/README.md) conservan nuevas EXE y regeneran únicamente su índice/JSON desde recibos con huella. Actualizar historias/tareas/DoD solo con el alcance comprobado; la CI documental está preparada, no ejecutada remotamente.
