# Inventario documental y preparación para GitHub

**Estado vigente 23-10:** commits y publicación autorizados; correcciones y pruebas separadas en tres commits locales, documentación/CI se preparan en un commit propio. La publicación sigue bloqueada por autenticación, no por falta de autorización. Consultar el [flujo y solicitud de revisión](../scrum/flujo-git-y-revision.md) y el [control vigente](../pruebas/control-publicacion.json). Los inventarios anteriores se conservan como cortes históricos.

**Corte previo del 22-10:** el paquete revisado tenía 117 archivos: 112 documentales/de apoyo y cinco técnicos. Posteriormente se preparó ese conjunto en el índice Git, sin commits ni push. La reorganización documental posterior cambió las rutas de cinco Markdown y actualizó el README principal; ese manifiesto y staging previos ya no representan todos los cambios actuales.

**Alcance del corte de reorganización anterior:** solo documentación, entonces sin cambiar código ni índice. **Alcance vigente autorizado:** correcciones, pruebas y automatización con preparación revisada del staging, manteniendo la prohibición de commit/push. No reutilizar el listado temporal anterior. Los dos ZIP siguen excluidos y las evidencias de pruebas conservan sus huellas.

Comparación histórica, anterior a la reorganización, con la instantánea de inicio del 22-10: 29 documentales actualizados, 44 creados y 39 conservados en el paquete público. Los archivos nuevos incluyen evidencia auténtica, scripts y export propuesto; no son nuevas funcionalidades. Fuente histórica Git: los 22 commits seleccionados siguen intactos.

## Documentación y artefactos publicables

| Archivo actual | Procedencia / cambio documental |
|---|---|
| [README.md](../../README.md) | Entrada principal actualizada; antes era una plantilla de Kotlin. |
| [docs/tecnica/administracion-movil.md](../tecnica/administracion-movil.md) | Traslado de ADMIN_MOVIL.md; nota funcional con versión. |
| [docs/tecnica/calidad-movil.md](../tecnica/calidad-movil.md) | Traslado de MODULO_CALIDAD_MOVIL.md; nota funcional con versión. |
| [docs/tecnica/portal-proveedor-movil.md](../tecnica/portal-proveedor-movil.md) | Traslado de PROVEEDOR_MOVIL_FIGMA.md; límites remotos desactualizados aclarados, PIN retirado. |
| [docs/antecedentes/prompt-web-ecolecta.md](../antecedentes/prompt-web-ecolecta.md) | Traslado de PROMPT_WEB_ECOLECTA.md; cuerpo histórico conservado. |
| [docs/pruebas/acceso-y-datos-de-prueba.md](../pruebas/acceso-y-datos-de-prueba.md) | Sustituye CREDENCIALES_PRUEBA_MOVIL.md; perfiles/datos conservados, sin valores de PIN. |
| [.github/PULL_REQUEST_TEMPLATE.md](../../.github/PULL_REQUEST_TEMPLATE.md) | Conservado |
| [docs/COMO-TRABAJAMOS.md](../COMO-TRABAJAMOS.md) | Actualizado |
| [docs/README.md](../README.md) | Actualizado |
| [docs/academico/archivos-documentacion.md](archivos-documentacion.md) | Actualizado |
| [docs/academico/ejercicio-delta.md](ejercicio-delta.md) | Conservado |
| [docs/academico/entregables-y-validacion.md](entregables-y-validacion.md) | Actualizado |
| [docs/academico/evidencias-pendientes.md](evidencias-pendientes.md) | Actualizado |
| [docs/academico/fuentes-docentes.md](fuentes-docentes.md) | Actualizado |
| [docs/academico/graficos/datos-metricas.json](graficos/datos-metricas.json) | Conservado |
| [docs/academico/graficos/delta-burndown.png](graficos/delta-burndown.png) | Conservado |
| [docs/academico/graficos/delta-burndown.svg](graficos/delta-burndown.svg) | Conservado |
| [docs/academico/graficos/delta-burnup.png](graficos/delta-burnup.png) | Conservado |
| [docs/academico/graficos/delta-burnup.svg](graficos/delta-burnup.svg) | Conservado |
| [docs/academico/graficos/delta-cumplimiento.png](graficos/delta-cumplimiento.png) | Conservado |
| [docs/academico/graficos/delta-cumplimiento.svg](graficos/delta-cumplimiento.svg) | Conservado |
| [docs/academico/graficos/delta-velocidad.png](graficos/delta-velocidad.png) | Conservado |
| [docs/academico/graficos/delta-velocidad.svg](graficos/delta-velocidad.svg) | Conservado |
| [docs/academico/graficos/ecolactea-commits-semana.png](graficos/ecolactea-commits-semana.png) | Conservado |
| [docs/academico/graficos/ecolactea-commits-semana.svg](graficos/ecolactea-commits-semana.svg) | Conservado |
| [docs/academico/graficos/pruebas-reales-2026-10-22.png](graficos/pruebas-reales-2026-10-22.png) | Creado |
| [docs/academico/graficos/pruebas-reales-2026-10-22.svg](graficos/pruebas-reales-2026-10-22.svg) | Creado |
| [docs/academico/matriz-cumplimiento.md](matriz-cumplimiento.md) | Actualizado |
| [docs/antecedentes/.gitignore](../antecedentes/.gitignore) | Creado |
| [docs/antecedentes/README.md](../antecedentes/README.md) | Conservado |
| [docs/antecedentes/tramo-git-1.md](../antecedentes/tramo-git-1.md) | Conservado |
| [docs/antecedentes/tramo-git-2.md](../antecedentes/tramo-git-2.md) | Conservado |
| [docs/antecedentes/tramo-git-3.md](../antecedentes/tramo-git-3.md) | Conservado |
| [docs/auditoria-documentacion.md](../auditoria-documentacion.md) | Actualizado |
| [docs/evidencias/README.md](../evidencias/README.md) | Actualizado |
| [docs/guia-mantenimiento-documentacion.md](../guia-mantenimiento-documentacion.md) | Actualizado |
| [docs/herramientas/generar_grafico_pruebas.py](../herramientas/generar_grafico_pruebas.py) | Creado |
| [docs/herramientas/proxy_respuesta_perdida.py](../herramientas/proxy_respuesta_perdida.py) | Creado |
| [docs/herramientas/verificar_documentacion.py](../herramientas/verificar_documentacion.py) | Creado |
| [docs/kanban/capturas/README.md](../kanban/capturas/README.md) | Conservado |
| [docs/kanban/configuracion-propuesta.json](../kanban/configuracion-propuesta.json) | Creado |
| [docs/kanban/importacion-propuesta.csv](../kanban/importacion-propuesta.csv) | Creado |
| [docs/kanban/tablero.md](../kanban/tablero.md) | Actualizado |
| [docs/kanban/wip-limits.md](../kanban/wip-limits.md) | Conservado |
| [docs/metodologia/APLICACION_METODOLOGIA_SCRUM.md](../metodologia/APLICACION_METODOLOGIA_SCRUM.md) | Conservado |
| [docs/metodologia/RECONSTRUCCION_RETROSPECTIVA_ECOLACTEA_DIGITAL.md](../metodologia/RECONSTRUCCION_RETROSPECTIVA_ECOLACTEA_DIGITAL.md) | Conservado |
| [docs/plantillas/acta-evento-scrum.md](../plantillas/acta-evento-scrum.md) | Conservado |
| [docs/plantillas/caso-de-prueba.md](../plantillas/caso-de-prueba.md) | Conservado |
| [docs/plantillas/ejecucion-prueba.md](../plantillas/ejecucion-prueba.md) | Conservado |
| [docs/plantillas/historia-usuario.md](../plantillas/historia-usuario.md) | Conservado |
| [docs/plantillas/reporte-defecto.md](../plantillas/reporte-defecto.md) | Conservado |
| [docs/plantillas/tarea.md](../plantillas/tarea.md) | Conservado |
| [docs/proceso-scrum-del-proyecto.md](../proceso-scrum-del-proyecto.md) | Conservado |
| [docs/pruebas/README.md](../pruebas/README.md) | Actualizado |
| [docs/pruebas/casos/ejemplos-ecolactea.md](../pruebas/casos/ejemplos-ecolactea.md) | Actualizado |
| [docs/pruebas/defectos/README.md](../pruebas/defectos/README.md) | Actualizado |
| [docs/pruebas/ejecuciones/README.md](../pruebas/ejecuciones/README.md) | Actualizado |
| [docs/pruebas/ejecuciones/resultados-2026-10-22.json](../pruebas/ejecuciones/resultados-2026-10-22.json) | Creado |
| [docs/pruebas/ejecuciones/resultados-2026-10-22.md](../pruebas/ejecuciones/resultados-2026-10-22.md) | Creado |
| [docs/pruebas/evidencias/BUG-002-arranque-android.log](../pruebas/evidencias/BUG-002-arranque-android.log) | Creado |
| [docs/pruebas/evidencias/CP-001-guardado-offline.png](../pruebas/evidencias/CP-001-guardado-offline.png) | Creado |
| [docs/pruebas/evidencias/CP-001-reabierto-offline.png](../pruebas/evidencias/CP-001-reabierto-offline.png) | Creado |
| [docs/pruebas/evidencias/CP-002-007-confirmado.png](../pruebas/evidencias/CP-002-007-confirmado.png) | Creado |
| [docs/pruebas/evidencias/CP-003-acceso-local.jpg](../pruebas/evidencias/CP-003-acceso-local.jpg) | Creado |
| [docs/pruebas/evidencias/CP-003-login-rechazado.jpg](../pruebas/evidencias/CP-003-login-rechazado.jpg) | Creado |
| [docs/pruebas/evidencias/CP-007-respuesta-perdida.png](../pruebas/evidencias/CP-007-respuesta-perdida.png) | Creado |
| [docs/pruebas/evidencias/CP-ANDROID-inicio.png](../pruebas/evidencias/CP-ANDROID-inicio.png) | Creado |
| [docs/pruebas/evidencias/CP-ANDROID-preparacion.png](../pruebas/evidencias/CP-ANDROID-preparacion.png) | Creado |
| [docs/pruebas/evidencias/EXE-20261022-01.log](../pruebas/evidencias/EXE-20261022-01.log) | Creado |
| [docs/pruebas/evidencias/EXE-20261022-02.log](../pruebas/evidencias/EXE-20261022-02.log) | Creado |
| [docs/pruebas/evidencias/EXE-20261022-03.log](../pruebas/evidencias/EXE-20261022-03.log) | Creado |
| [docs/pruebas/evidencias/EXE-20261022-04.log](../pruebas/evidencias/EXE-20261022-04.log) | Creado |
| [docs/pruebas/evidencias/EXE-20261022-05.log](../pruebas/evidencias/EXE-20261022-05.log) | Creado |
| [docs/pruebas/evidencias/EXE-20261022-06.log](../pruebas/evidencias/EXE-20261022-06.log) | Creado |
| [docs/pruebas/evidencias/EXE-20261022-07.log](../pruebas/evidencias/EXE-20261022-07.log) | Creado |
| [docs/pruebas/evidencias/EXE-20261022-08.log](../pruebas/evidencias/EXE-20261022-08.log) | Creado |
| [docs/pruebas/evidencias/EXE-20261022-09.log](../pruebas/evidencias/EXE-20261022-09.log) | Creado |
| [docs/pruebas/evidencias/EXE-20261022-10.log](../pruebas/evidencias/EXE-20261022-10.log) | Creado |
| [docs/pruebas/evidencias/EXE-20261022-11.log](../pruebas/evidencias/EXE-20261022-11.log) | Creado |
| [docs/pruebas/evidencias/EXE-20261022-12.log](../pruebas/evidencias/EXE-20261022-12.log) | Creado |
| [docs/pruebas/evidencias/EXE-20261022-13.log](../pruebas/evidencias/EXE-20261022-13.log) | Creado |
| [docs/pruebas/evidencias/EXE-20261022-15.log](../pruebas/evidencias/EXE-20261022-15.log) | Creado |
| [docs/pruebas/evidencias/EXE-20261022-16.log](../pruebas/evidencias/EXE-20261022-16.log) | Creado |
| [docs/pruebas/evidencias/EXE-20261022-17.log](../pruebas/evidencias/EXE-20261022-17.log) | Creado |
| [docs/pruebas/evidencias/EXE-20261022-18.log](../pruebas/evidencias/EXE-20261022-18.log) | Creado |
| [docs/pruebas/evidencias/EXE-20261022-19.log](../pruebas/evidencias/EXE-20261022-19.log) | Creado |
| [docs/pruebas/evidencias/EXE-20261022-20.log](../pruebas/evidencias/EXE-20261022-20.log) | Creado |
| [docs/pruebas/evidencias/EXE-20261022-21.log](../pruebas/evidencias/EXE-20261022-21.log) | Creado |
| [docs/pruebas/evidencias/EXE-20261022-22.log](../pruebas/evidencias/EXE-20261022-22.log) | Creado |
| [docs/pruebas/evidencias/EXE-20261022-23.log](../pruebas/evidencias/EXE-20261022-23.log) | Creado |
| [docs/pruebas/evidencias/EXE-20261022-24.log](../pruebas/evidencias/EXE-20261022-24.log) | Creado |
| [docs/pruebas/evidencias/EXE-20261022-25.log](../pruebas/evidencias/EXE-20261022-25.log) | Creado |
| [docs/pruebas/evidencias/EXE-20261022-26.log](../pruebas/evidencias/EXE-20261022-26.log) | Creado |
| [docs/pruebas/evidencias/README.md](../pruebas/evidencias/README.md) | Actualizado |
| [docs/requisitos/rf-fuente-y-cobertura.md](../requisitos/rf-fuente-y-cobertura.md) | Actualizado |
| [docs/requisitos/rnf-fuente-y-cobertura.md](../requisitos/rnf-fuente-y-cobertura.md) | Actualizado |
| [docs/scrum/calendario-y-capacidad.md](../scrum/calendario-y-capacidad.md) | Conservado |
| [docs/scrum/definition-of-done.md](../scrum/definition-of-done.md) | Actualizado |
| [docs/scrum/eventos-y-registros.md](../scrum/eventos-y-registros.md) | Conservado |
| [docs/scrum/evidencias-commits.md](../scrum/evidencias-commits.md) | Conservado |
| [docs/scrum/flujo-git-y-revision.md](../scrum/flujo-git-y-revision.md) | Actualizado |
| [docs/scrum/metodologia.md](../scrum/metodologia.md) | Conservado |
| [docs/scrum/metricas.md](../scrum/metricas.md) | Actualizado |
| [docs/scrum/product-backlog.md](../scrum/product-backlog.md) | Actualizado |
| [docs/scrum/roles.md](../scrum/roles.md) | Conservado |
| [docs/scrum/sprint-1.md](../scrum/sprint-1.md) | Conservado |
| [docs/scrum/sprint-10.md](../scrum/sprint-10.md) | Conservado |
| [docs/scrum/sprint-2.md](../scrum/sprint-2.md) | Conservado |
| [docs/scrum/sprint-3.md](../scrum/sprint-3.md) | Conservado |
| [docs/scrum/sprint-4.md](../scrum/sprint-4.md) | Conservado |
| [docs/scrum/sprint-5.md](../scrum/sprint-5.md) | Conservado |
| [docs/scrum/sprint-6.md](../scrum/sprint-6.md) | Actualizado |
| [docs/scrum/sprint-7.md](../scrum/sprint-7.md) | Actualizado |
| [docs/scrum/sprint-8.md](../scrum/sprint-8.md) | Actualizado |
| [docs/scrum/sprint-9.md](../scrum/sprint-9.md) | Actualizado |
| [docs/scrum/tareas.md](../scrum/tareas.md) | Actualizado |
| [docs/scrum/trazabilidad.md](../scrum/trazabilidad.md) | Actualizado |
| [docs/tecnica/arquitectura-datos-y-sincronizacion.md](../tecnica/arquitectura-datos-y-sincronizacion.md) | Actualizado |

## Cambios técnicos previos, conservados sin modificación en la reorganización

| Archivo | Cambio y comprobación |
|---|---|
| [androidApp/src/main/kotlin/pe/ecolecta/EcolectaApp.kt](../../androidApp/src/main/kotlin/pe/ecolecta/EcolectaApp.kt) | Arranque fuera de main y espera antes de crear App; ANR corregido, build/acceso/offline/sync Android y JVM 256/256 Passed. Revisión independiente pendiente. |
| [androidApp/src/main/kotlin/pe/ecolecta/MainActivity.kt](../../androidApp/src/main/kotlin/pe/ecolecta/MainActivity.kt) | Arranque fuera de main y espera antes de crear App; ANR corregido, build/acceso/offline/sync Android y JVM 256/256 Passed. Revisión independiente pendiente. |
| [web/tests/Feature/AdminNavegacionYPermisosTest.php](../../web/tests/Feature/AdminNavegacionYPermisosTest.php) | Expectativas menú/rutas actualizadas según 52dab50; PHP 289/289 y Pint Passed. Revisión independiente pendiente. |
| [web/tests/Feature/AdminPantallasTest.php](../../web/tests/Feature/AdminPantallasTest.php) | Expectativas menú/rutas actualizadas según 52dab50; PHP 289/289 y Pint Passed. Revisión independiente pendiente. |
| [web/tests/Feature/AdminZonasRutasVehiculosTest.php](../../web/tests/Feature/AdminZonasRutasVehiculosTest.php) | Expectativas menú/rutas actualizadas según 52dab50; PHP 289/289 y Pint Passed. Revisión independiente pendiente. |

Se corrigió el arranque Android por ANR; no se cambiaron reglas empresariales, criptografía, esquema/migraciones, dependencias ni lockfiles. La rama local es `cierre/scrum-kanban-pruebas-20261022`; HEAD base `05743b97f2e5835250e223eabc09f9924b7524bb`. Las huellas de los cinco archivos y del parche constan en el [JSON de ejecuciones](../pruebas/ejecuciones/resultados-2026-10-22.json).

## Exclusiones concretas

- `docs/antecedentes/documentacion-previa-2026-10-19.zip` y `documentacion-previa-consolidacion-2026-10-21.zip`: respaldos intactos, fuera del paquete. El segundo conserva rutas anteriores; no publicarlo. `.gitignore` local del directorio previene su agregado normal.
- `.env`, bases SQLite temporales o reales, cookies/tokens, credenciales, runtimes descargados, vendor/node_modules, builds/APK y logs completos de instalación: no se agregan.
- Originales empresariales/PPT/TXT no se copian al repositorio. Se publican identificación, huellas y extracción metodológica sin contactos.

## Comprobaciones y preparación reproducibles

~~~bash
python3 docs/herramientas/verificar_documentacion.py --export-kanban --json /tmp/ecolactea-documentacion.json
python3 docs/herramientas/verificar_documentacion.py --lista-publicable > /tmp/ecolactea-publicable.txt
git status --short --branch
git diff --check
git diff --stat
git diff --cached --name-only
~~~

`--lista-publicable` imprime solo las rutas anteriores, sin ZIP. Fue comprobada localmente; no modifica Git. El verificador valida enlaces/anclas, archivos, referencias/IDs, tablas activas, escala/políticas, calendario, campos de plantillas, export Kanban y huellas de logs/capturas/tests. Con `--fuentes-dir` coteja F1…F6. No sustituye revisión humana ni acredita CA no probados. Las dos advertencias del borrador congelado quedan documentadas en antecedentes.

**Actualización de la autorización vigente:** el manifiesto ahora incluye README de raíz, altas y bajas por traslado, workflows, herramientas y archivos técnicos nuevos, incluidos los assets de instrumentación. El staging se prepara únicamente desde ese listado revisado; no hay commit/push. El inventario anterior conserva el corte de organización previo; el [control de publicación](../pruebas/control-publicacion.json) enumera el paquete actual y sus comprobaciones. No restaurar documentos originales con PIN desde respaldos o rutas eliminadas.

No usar agregado global para incluir archivos ajenos al paquete. Los resultados del 22-10 no son una aprobación del PR ni aceptación del beneficiario. [Cierre y límites](../auditoria-documentacion.md) · [Matriz completa](matriz-cumplimiento.md).


## Organización revisada el 22-10-2026

No se encontraron Markdown idénticos. Los sprints comparten una estructura, pero conservan objetivos, fechas y selección propios; se mantienen como documentos separados por esa función. Se conservaron las fuentes de historias, tareas, sprints, requisitos y evidencias; los índices y las vistas derivadas enlazan a ellas. Las notas funcionales de raíz se ubicaron en `tecnica/`, el prompt en `antecedentes/` y el acceso sin secretos en `pruebas/`. Se preservan los borradores, resultados, gráficos y tramos históricos. No se crearon documentos para simular práctica Scrum ni para reducir artificialmente el número de archivos.

Los valores de demostración retirados de Markdown siguen presentes en versiones previas y en seeders. Esta organización no rota secretos ni remedia su uso eventual en un entorno real; consultar el [pendiente de acceso](../pruebas/acceso-y-datos-de-prueba.md#pendiente-antes-de-utilizar-un-entorno-real).

## Ampliación técnica y automatización

- BUG-003: un caso de uso compartido y tres regresiones JVM; sin reglas nuevas de negocio.
- Instrumentación Android: runner/fixtures de test y dos dependencias de test con versiones existentes; no cambia el APK de producción con hooks de QA.
- Herramientas de ejecución/recibos/validación, tests y workflow documental; [guía](../herramientas/README.md).
- Nuevas EXE, capturas UI/Android, logs saneados y revisión de 41 HU/17 RNF; [cierre](../pruebas/cobertura-y-cierre.md).

Los cinco cambios técnicos del corte anterior permanecen íntegros; sus huellas originales se cotejan. Los recibos nuevos incluyen la versión del árbol ampliado. Revisión independiente y publicación aún pendientes, sin autoaprobación. La ejecución remota de Actions no se declara realizada.
