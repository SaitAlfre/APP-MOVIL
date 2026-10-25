# Flujo Git y revisión para cuatro integrantes

**Estado vigente, 23-10-2026:** autenticación confirmada como `SaitAlfre`; `develop` y `cierre/scrum-kanban-pruebas-20261022` publicadas sin force. [PR #2](https://github.com/SaitAlfre/APP-MOVIL/pull/2) abierto hacia develop, con revisión solicitada a `dubcek-leonel` y CI remoto satisfactorio. Main permanece en `05743b97f2e5835250e223eabc09f9924b7524bb`. Integración pendiente de aprobación real de otro integrante. Los cuatro commits locales se conservan con sus hashes; las secciones fechadas siguientes son cortes históricos anteriores a esta publicación.

## Hechos verificados — 2026-10-21

HEAD local y refs main/origin/main/origin/HEAD: **05743b97f2e5835250e223eabc09f9924b7524bb**. Solo está confirmada la rama local main; no se hizo fetch. No hay develop en las referencias locales examinadas; eso no demuestra ausencia remota.

El historial conserva el merge de PR #1. La consulta pública a [Pull requests](https://github.com/SaitAlfre/APP-MOVIL/pulls) muestra 0 abiertos y 1 cerrado; no prueba revisión aprobada. Consulta pública de ramas/Projects no recuperable: **operación, ramas remotas actuales y aprobaciones PENDIENTES DE VALIDACIÓN**. Sin workflow CI versionado identificado. docs/ permanece sin seguimiento; esta consolidación no preparó staging.

## Flujo solicitado y estrategia propuesta

F3 línea 5 pide PR hacia develop y revisión de otro integrante; F2 DoD exige integración sin errores y PR aprobado. Propuesta para adoptar al acordarlo el equipo:

1. Mantener main como versión estable.
2. Tras confirmar que no hay una develop existente que deba continuarse, preparar develop desde la base acordada y autorizar su publicación. **No se creó en esta tarea.**
3. Ramas cortas por tarea: feature/HU-04-A-T-41-reintento, fix/BUG-001-reintento, docs/T-54-proceso.
4. PR feature→develop; revisor distinto del autor, CA/pruebas/docs vinculados. Integrar solo tras resolver observaciones y confirmar pruebas pertinentes.
5. PR develop→main para incremento candidato, con revisión y resultados de integración; desplegar a pruebas y completar DoD. Si ambiente de pruebas usa develop, identificar explícitamente el hash y posterior integración final.
6. Mientras develop no se acuerde, cualquier uso directo de PR a main debe registrarse como flujo real y desviación docente pendiente; no describirlo como cumplimiento de F3.

No cambiar de estrategia a mitad del trabajo sin registrar motivo/fecha y efecto en evidencias. No crear ramas paralelas por integrante sin tarea.

## Comandos ilustrativos, no ejecutados

Después de ratificar base y verificar cambios locales, un integrante puede crear su rama sobre develop existente:

```bash
git switch develop
git switch -c feature/HU-04-A-T-41-reintento
```

Si develop aún no existe, su creación local desde main requiere comprobar la base real con el equipo; no usar este ejemplo como prueba de una rama creada. Actualizar desde el remoto y resolver conflictos son actividades del equipo fuera de esta consolidación. No copiar comandos de publicación sin revisar el contenido que se agregará.

## PR y revisión

- Título: “HU-04-A / T-41: conservar estado ante respuesta perdida”.
- Cuerpo: [plantilla PR](../../.github/PULL_REQUEST_TEMPLATE.md), enlaces a historia, CA, RF/RNF, cambios, resultados reales, riesgos y documentación.
- Autor mantiene evidencia y responde observaciones. Revisor comprueba regla/datos, pruebas de error/permisos/offline si aplica y coherencia de plataforma.
- Si autor API es Dubcek, Medina puede revisar ese PR; si autor móvil es Medina, Dubcek revisa. PO/SM pueden revisar cuando tienen competencia y disponibilidad acordadas; no se asume.
- Capturas F3: 2–3 imágenes útiles del PR/revisión/resultado con fecha y sin secretos. El URL y metadatos del PR son referencia principal.
- Una aprobación no sustituye build/integración/despliegue; [DoD](definition-of-done.md) se verifica por historia y versión.
- Commits: mensajes que indiquen propósito y HU/T-XX; no reescribir firmas o fechas para aparentar Scrum histórico. Hash real se vincula cuando exista.

Protección de rama y automatizaciones pueden proponerse, pero no están configuradas por estos documentos. No se modificaron workflows ni políticas remotas.

## Actualización comprobada — 2026-10-22

Los hechos del 21-10 anteriores se conservan como corte histórico. Hoy se creó la rama local autorizada `cierre/scrum-kanban-pruebas-20261022` desde el mismo HEAD. Cambiaron tres tests PHP y dos archivos Android de arranque por ANR; resultados y huellas en [ejecuciones](../pruebas/ejecuciones/resultados-2026-10-22.md). Sin staging, commit, fetch, push ni ramas remotas nuevas. main/origin permanecen sin cambios.

API pública: solo rama main; PR #1 cerrado/fusionado el 24-09; `/pulls/1/reviews` devolvió lista vacía. **No hay aprobación formal acreditada**. Se preparó configuración Projects, pero no existe sesión/token disponible para comprobar privados o crear recursos. El usuario autorizó configurarlo si hay acceso: la limitación actual es autenticación, no una nueva solicitud de permiso. El revisor real y publicación del parche siguen externos al cierre técnico.

## Solicitud de revisión preparada — 2026-10-23

**Antecedente anterior a autenticar:** el contenido siguiente se materializó en el [PR #2](https://github.com/SaitAlfre/APP-MOVIL/pull/2). La revisión ya está solicitada; su aprobación sigue pendiente.

**Título del PR:** Ecolactea: correcciones verificadas y Scrum con Kanban.

**Origen → destino:** `cierre/scrum-kanban-pruebas-20261022` → `develop`.

**Estado:** contenido listo; PR remoto **EVIDENCIA PENDIENTE por autenticación**, solicitud aún no enviada a personas. El PR de promoción `develop` → `main` corresponde después de revisión/integración, no antes como PR vacío.

### Cambio para inspeccionar

- BUG-002: inicio Android fuera del hilo principal, espera hasta datos listos; se mantienen los hashes y reglas existentes.
- BUG-003: conservar PENDING cuando falta cuenta autora; tres regresiones verifican conservación, reenvío tras edición autorizada y ausencia de envío de una fila inexistente.
- Tres tests PHP ajustados al menú/redirección existentes; instrumentación Android limitada a preview y emulador desechable.
- Documentación Scrum principal/Kanban complementario, recibos reales y CI documental con solo lectura.

### Pruebas que acompañan al PR

| Alcance | Evidencia real | Resultado / límite |
|---|---|---|
| JVM y cinco integraciones reales | [Recibo JVM](../pruebas/ejecuciones/automaticas/EXE-20261023T041855Z-jvm-56eff2/resultado.json) |259/259; árbol fuente identificado por SHA-256. |
| Regresión PHP | [Recibo PHP](../pruebas/ejecuciones/automaticas/EXE-20261023T041842Z-php-42fc2c/resultado.json) |289/289. |
| Firestore | [Recibo Firestore](../pruebas/ejecuciones/automaticas/EXE-20261023T041850Z-firestore-946701/resultado.json) |9/9 en emulador, sin proyecto productivo. |
| Android | [Recibo instrumentado](../pruebas/ejecuciones/automaticas/EXE-20261023T041640Z-android-ui-60b48a/resultado.json) |5/5: UI, OCR sintético y persistencia/reapertura offline; no cámara o hardware físico. |
| Documentación y ejecutores | [Índice de ejecuciones](../pruebas/ejecuciones/automaticas/README.md) | Comandos/hashes y URLs corregidos; fallos anteriores conservados y regresión ampliada. |

Las pruebas son del árbol fuente anterior a los commits; sus bytes coinciden con las correcciones que se preparan para revisión. No se cambia retroactivamente la versión de un recibo.

### Solicitud exacta al integrante revisor

Elegir un integrante distinto del autor autenticado del PR, con acceso y competencia para revisar. Su usuario GitHub **PENDIENTE DE VALIDACIÓN**; no se inventa asignación ni autoaprobación.

1. Inspeccionar los cambios de hilos/Koin, pérdida de cambios y rutas de prueba; confirmar las reglas intencionales con el equipo.
2. Contrastar recibos/logs con casos y código; repetir los controles afectados con la [guía ejecutable](../herramientas/README.md).
3. Registrar observaciones y resolución; emitir aprobación real en el PR cuando corresponda. Adjuntar las 2–3 capturas docentes con fecha real.
4. Tras aprobación, integrar en develop, comprobar CI/build/despliegue y abrir promoción a main. Solo certificar HU después de CA y DoD completos.

**Checklist disponible para quien revise:** documentación/recibos y tests técnicos preparados; revisión independiente, aprobación remota, integración destino, aceptación completa y feedback siguen sin marcar. Los [14 requisitos parciales](../academico/matriz-cumplimiento.md) mantienen la aprobación exacta y persona requerida; las solicitudes a docente/beneficiario están preparadas, no enviadas.

### Commits técnicos locales conservados

| Hash local | Alcance |
|---|---|
| `c9ace988ca444be42df228b1bd1b2bd78b6ad39d` | BUG-002: preparación Android y espera de inicio. |
| `e01d8c633f46f1d87a44f9bbaba6a1de02f401ef` | BUG-003 y tres regresiones; HU-10/RNF-02, insumo T-41/52. |
| `8674b53756ec9c765dc6de763ea7af32efe956ad` | Instrumentación OCR/offline y ajustes de tres tests PHP. |

Atribución real: Codex como agente autorizado, sin identidad estudiantil inventada. El commit documental se identifica mediante `git log` para no escribir su propio hash dentro del contenido que lo genera. Los 22 commits históricos siguen íntegros. No hay URL de PR/tablero ni commits publicados acreditados hasta autenticar y comprobar la respuesta remota.

## Publicación real y siguiente paso del equipo

- Publicados: `c9ace98`, `e01d8c6`, `8674b53` y `0673c90`; no se alteraron los 22 commits históricos. La actualización posterior de enlaces operativos se identifica mediante Git, sin escribir su propio hash.
- [CI del push inicial](https://github.com/SaitAlfre/APP-MOVIL/actions/runs/39975262694) y [CI del PR inicial](https://github.com/SaitAlfre/APP-MOVIL/actions/runs/39975391012): satisfactorios para `0673c90`; comprobar también el último commit del PR.
- Revisor solicitado: `dubcek-leonel`, colaborador real con escritura. No se inventó una respuesta, aprobación o reunión.
- Después de su aprobación y de resolver observaciones, integrar en develop, comprobar el incremento y abrir PR develop→main. No abrir un PR vacío ni promover a main antes de esa revisión.
- [Tablero privado](https://github.com/users/SaitAlfre/projects/1/views/2) e [IDs operativos](../kanban/project-operativo.json). [Cuentas y accesos](roles.md#cuentas-github-y-accesos) identificados por el propietario: los cuatro tienen acceso al Project; la invitación de `jhosuemedina03` al repositorio sigue pendiente de aceptación. No se asignaron tareas ni se ratificaron responsabilidades por esta confirmación de cuentas.
