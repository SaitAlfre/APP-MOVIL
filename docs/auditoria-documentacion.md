# Consolidación documental y cierre técnico — 22-10-2026

**Registro de un corte histórico.** Conserva lo comprobado antes de la preparación Git y reorganización posteriores del 22-10. Sus conteos y frases sobre staging describen ese momento; las fuentes operativas actuales están en el [índice](README.md#fuentes-principales). No se actualizan sus resultados para aparentar nuevas pruebas.

**Registro histórico de la consolidación del 21-10 (hasta “Cierre vigente”):** los conteos, restricciones y ausencia de pruebas siguientes describen esa etapa, no los resultados del 22-10. Se conserva el registro previo sin reescribirlo.

**Fecha del antecedente:** 2026-10-21. Alcance: docs/ y plantilla Markdown de PR. Punto de partida: auditoría anterior (50 archivos físicos/38 Markdown, 17 entradas, 50 tareas, 61 RF/17 RNF, 22 commits, 5 gráficos, 36 requisitos y 15 EP). El 55/100 previo fue orientativo, no rúbrica ni nota oficial.

## Correcciones aplicadas

| Hallazgo anterior | Corrección documental | Fuente principal |
|---|---|---|
| Historias con resultados independientes | 41 HU funcionales; conservar padres, separar HU-14 habilitador y HU-15 investigación. | [Backlog](scrum/product-backlog.md) |
| INVEST automático y Entonces administrativos | 86 CA y 82 escenarios observables; condiciones INVEST individuales explícitas. | Backlog |
| Siete RF sin historia y RNF sin trazabilidad | 61 RF con CA y 17 controles RNF, tareas/protocolos y límites. | [RF](requisitos/rf-fuente-y-cobertura.md), [RNF](requisitos/rnf-fuente-y-cobertura.md) |
| Tareas/estados repetidos | Registro único de 82 tareas; sprints enlazan, horas separadas de SP. | [Tareas](scrum/tareas.md) |
| Pasado/ejercicio/plan mezclados | S01–04 análisis Git; S05 preparación; S06–10 planes; AC-01/02 separados. | [Calendario](scrum/calendario-y-capacidad.md) |
| 26-11 tratado solo como límite matemático | Reconocer también entrega referencial F5; 23/26 pendiente del docente. | Calendario y [fuentes](academico/fuentes-docentes.md) |
| Urgencia confundida con Review | Quitar estados operativos no comprobados; políticas de entrada/salida y WIP. | [Kanban](kanban/tablero.md) |
| Diez bloques de ceremonias genéricas | Agenda/plantilla común; resultados de cada sprint pendientes, propuestas rotuladas. | [Eventos](scrum/eventos-y-registros.md) |
| Falta de mantenimiento distribuido | Guía para cuatro integrantes, 13 pasos, seis plantillas y PR documental. | [Cómo trabajamos](COMO-TRABAJAMOS.md), [guía](guia-mantenimiento-documentacion.md) |
| Casos confundidos con resultados | Siete CP Not Run, índices de EXE/BUG/EV sin ejecuciones ficticias. | [Pruebas](pruebas/README.md) |
| Angular/Ionic atribuidos a F4 | Corregir: referencia en F6, distinto del KMP/Laravel actual. | [Arquitectura](tecnica/arquitectura-datos-y-sincronizacion.md) |
| Producción/ventas sin hash de apoyo | Verificar cambios 1fb3dab/6104b68 y documentar alcance parcial. | Arquitectura y [commits](scrum/evidencias-commits.md) |
| “Solo fuentes” de pruebas | Reconocer actividad de emulador en log, sin declarar suite aprobada. | [EV-003](evidencias/README.md#ev-003) |
| Rutas privadas y respaldos duplicados | Rutas activas relativas; ZIP conservados y excluidos del paquete público recomendado. | [Publicación](academico/archivos-documentacion.md) |

<a id="observaciones-tecnicas-abiertas"></a>
## Observaciones técnicas abiertas

**Hallazgos de lectura estática, no bugs reproducidos ni pruebas ejecutadas.**

- **OBS-01 — sanciones:** SancionController propone descuentos fijos 30.50/15 sujetos a aprobación; GenerarLiquidacion suma descuentos aprobados. Esto no acredita RF-CAL-06/RF-PAG-03 por porcentaje, reincidencia, semana, retiro y capacitación sin descuento en acidez. HU-21/EP-02/12 requieren homologación de regla y posterior comprobación.
- **OBS-02 — cola general:** Hallazgo inicial: EnviarCambiosServidorUseCase completaba elementos con cuerpo nulo o usuario vacío sin transmitir. BUG-003 corrigió el caso de autor vacío, con regresión; los cuerpos nulos/no aplicables siguen sin transmitirse ni contarse como enviados. “Completado localmente” no significa “recibido por panel”. HU-10/DT-01 requieren distinguir exclusión/confirmación; no confundir con el motor dedicado de entregas.
- **OBS-03 — plataformas:** iOS mantiene URL vacía/destinos no configurados y captura manual; no se acredita paridad Android/iOS.
- **OBS-04 — clientes/precios:** tipos de cliente en VentaController difieren de F4; selección automática de tarifa/minimum stock no acreditados completamente. HU-17-B/C y EP-02 abiertos.

Referencias concretas al código y decisiones DT: [arquitectura](tecnica/arquitectura-datos-y-sincronizacion.md). No se corrigió software.

## Pendientes reales

[EP-01…15](academico/evidencias-pendientes.md): ratificación, Charter/reglas, calendario, prioridad/Poker, eventos, PR, pruebas, integración, despliegue, Project, beneficiario, aula, fotos y fuentes autorizadas. Las salidas documentales corregidas no cierran esos hechos.

## Verificaciones estáticas de cierre

| Comprobación | Resultado |
|---|---|
| Enlaces/anchors locales | 1326 revisados; 0 rotos. No se certifica disponibilidad de todos los enlaces externos. |
| RF/RNF originales | 61 RF y 17 RNF; textos/prioridades preservados; todos tienen relación documental, aceptación pendiente. |
| Historias y criterios | 41 HU funcionales, 86 CA y 82 Gherkin; IDs únicos, sin criterios o dependencias huérfanos ni ciclos. |
| Tareas | 82 IDs únicos; 50 IDs previos preservados y 32 nuevos; tareas/horas/estado en fuente única. |
| Casos de ejemplo | 7 CP, todos Not Run; no se crearon EXE Passed ni bugs reproducidos. |
| Matriz y EP | 36 filas académicas de línea base y 15 EP; niveles documental/práctico separados. |
| Calendario | 10 intervalos consecutivos de 7 días jueves→jueves; pasado/plan/ejercicios separados. |
| Tablas vigentes | Sin irregularidades de columnas. Errata del borrador congelado conservada y descrita en antecedentes. |
| Preservación | 17 archivos congelados/activos gráficos/respaldo comprobados por hash; sin cambios. Las 22 filas de commits son idénticas. |
| Privacidad del paquete textual | Sin coincidencias de rutas locales, correos, claves privadas, tokens GitHub, secretos asignados o teléfonos identificados en los patrones revisados. ZIP excluidos. |
| Proceso breve | Exportación de control de 2 páginas A4 y revisión visual de ambas; Markdown depende del renderizador. |
| Código y Git | Huella de todos los archivos versionados fuera del alcance documental idéntica; HEAD/refs intactos y diff versionado vacío. |
| Operaciones no realizadas | No pruebas de aplicación, staging, commits, push, ramas/remotos ni Project creados. |

Huella SHA-256 de comparación del contenido versionado fuera del alcance documental: **1789ba83508763390a7493cecc12bd71bfbd470a541669f707438094e7c810c7**. Se comparó antes/después; no es un hash de build ni una ejecución de pruebas.

Inventario: 70 archivos del alcance, 57 Markdown; 33 actualizados, 19 Markdown nuevos y un respaldo nuevo, 17 conservados. Publicación recomendada: 68 archivos, excluyendo ambos ZIP. [Lista completa](academico/archivos-documentacion.md).


## Paginación de control

El proceso breve tiene 479 palabras por conteo de espacios. Exportación de control **2 páginas A4**, márgenes laterales/superior 16 mm e inferior 20 mm, DejaVu Sans 10 pt/13 y tablas 9.1 pt/11.8. PDF/PNG internos de control, sin agregarlos como entregable al repositorio. Markdown no tiene páginas fijas; el formato del aula puede requerir ajuste al exportar. La comprobación no acredita entrega académica.

## Límites del resultado

Documentación preparada para trabajar, **no metodología históricamente aplicada ni práctica completamente acreditada**. La matriz distingue los niveles; no se emite nota oficial. No se crearon recursos GitHub ni se contactó al beneficiario/docente.

Fuente técnica de base: HEAD 05743b97f2e5835250e223eabc09f9924b7524bb; periodo de 22 commits conservado. Solo se realizaron lecturas y verificaciones documentales/estáticas; no pruebas de aplicación.

## Cierre vigente — 2026-10-22

La nueva autorización permitió pruebas seguras y correcciones limitadas. Se completaron las acciones controlables; el registro anterior de “sin pruebas/código” dejó de ser el estado actual. No se creó otra auditoría paralela.

| Trabajo ejecutado | Evidencia y resultado |
|---|---|
| Suites, integración y regresión | [26 intentos conservados](pruebas/ejecuciones/resultados-2026-10-22.md); final JVM 256/256, PHP 289/289 (1574 aserciones), Firestore 9/9, sin omitidos. |
| Builds/servicio/UI | Web Vite y APK localPreview Passed; servidor loopback con SQLite nueva, HTTP 200, login/logout CP-003 Passed. Servidor propio detenido al acabar y puerto sin respuesta, fecha en JSON. Estándar Android necesita configuración Firebase real; Android emulado: arranque/login offline/CP-001/002/007 Passed; iOS no disponible. |
| Cambio técnico acotado | Tres tests PHP corregidos según 52dab50 (Pint Passed) y dos archivos Android por ANR confirmado. Preparación fuera de main y pantalla de espera antes de App; sin cambios criptográficos, empresariales, esquema o dependencias. BUG-001/002 con regresión Passed y revisión independiente pendiente. |
| Backlogs candidatos completos | 91 tareas únicas: 19 aportes históricos/72 propuestas. T-83…91 completan construcción/revisión/integración de S07–09; S06 corregido a 14 h hipotéticas, capacidad real aún N/D. |
| Configuración Kanban | JSON/CSV derivados: 113 tarjetas propuestas, cinco columnas y WIP. Sin sesión GitHub no se crearon recursos ni se confirmaron Projects privados. |
| Matriz | 18 parciales iniciales: R13/25/26/36 completos documentalmente; otros 14 con dependencia externa precisa. Total documental 21 Cumple/14 Parcial/1 académico; global 5/30/1. No es nota oficial. |
| Fuentes y privacidad | F1…F6 cotejadas por hash; extractos portátiles identificados. Logs saneados y capturas con datos ficticios revisadas; ZIP históricos excluidos del paquete público. |
| Antecedentes | Dos borradores, tres tramos, cinco gráficos y datos previos, y ambos ZIP preservados por hash; 22 commits/filas sin alteraciones. |
| Git | Rama local autorizada cierre/scrum-kanban-pruebas-20261022, HEAD 05743b9. main/origin intactos; sin staging, commits, push, ramas remotas ni cambios productivos. |

### Comprobación reproducible

Se ejecutó `python3 docs/herramientas/verificar_documentacion.py --export-kanban --fuentes-dir RUTA_DEL_CURSO`. Primeras ejecuciones detectaron datos de rutas/correo en logs de preparación; se retiraron esos logs de instalación del paquete y se sanearon rutas en evidencias de tests, sin cambiar resultados. Las reejecuciones pasaron. La versión ampliada también comprueba continuidad jueves→jueves, escala de puntos, campos de plantillas, consistencia CSV y SHA-256 de evidencias. Los originales completos permanecen temporales, no se publican.

**Resultado documental final y conteos:** se registra en el JSON de ejecuciones bajo `documentary_check` tras la última comprobación. Solo dos advertencias conocidas del borrador congelado (líneas 635/636); 0 errores activos. No se usa el script para afirmar reuniones, revisión humana o aceptación del beneficiario.

### Alcance que permanece externo

Acuerdos de los cuatro integrantes, Charter/reglas/fechas del docente, Poker/horas/selección, ceremonias reales, aprobación por otro revisor y posterior publicación, sesión GitHub para Projects, hardware Android de campo para compatibilidad/consumo, entorno iOS con Xcode, inspección del beneficiario, capturas de distintos días y acuse/exposición del curso. [EP actualizados](academico/evidencias-pendientes.md) identifica qué parte ya está resuelta. Los planes de funcionalidades futuras no se presentan como trabajo terminado ni como fallos nuevos de esta consolidación.

[Inventario publicable exacto y comparación con línea base](academico/archivos-documentacion.md). Las comprobaciones de preservación/Git se realizan nuevamente al cerrar; no se hace commit ni push.

**Ampliación del cierre:** KVM y Android 16 emulado sí estuvieron disponibles con permisos autorizados. Se ejecutaron arranque, login offline, guardar/reabrir 20 L y pérdida real de respuesta tras persistencia; mismo UUID, una fila/20 L y confirmación posterior. EXE-17/18 descubrieron ANR y EXE-20…26 comprobaron su corrección. Los intentos de arnés 16/24 y compilación 19 se conservan; 26 intentos en total. Emulador, proxy y backend propios detenidos al cierre final; no quedan servicios de prueba activos.

**Comprobación final:** 117 archivos publicables (112 documentales/de apoyo +5 técnicos), 1527 enlaces/anclas locales comprobados, 0 errores activos y 2 advertencias de tablas del borrador congelado. 18 antecedentes/recursos previos y las 22 filas Git preservados por hash; textos/prioridades de 61 RF/17 RNF intactos. Únicos cambios versionados: dos archivos Android de arranque y tres tests PHP; 0 staged, commits, push, recursos remotos o cambios productivos. La huella del parche y de cada archivo probado está en el JSON.

## Continuación técnica autorizada

El usuario autorizó ahora implementación, pruebas y preparación del staging, manteniendo la prohibición de commit/push/producción. El [cierre por 41 HU y 17 RNF](pruebas/cobertura-y-cierre.md) y los [recibos posteriores](pruebas/ejecuciones/automaticas/README.md) complementan este corte; no se sobrescriben resultados anteriores ni se afirma adopción Scrum por generar documentos.

## Actualización de autorización y correcciones del 23-10

Se autorizaron commits/push con revisión. Tres commits técnicos locales conservan el árbol probado; main remota no cambió. Se restauraron comandos `python3` y hashes/enlaces que habían quedado fragmentados por espacios en documentos activos; la comprobación ampliada detectó el fallo antes de corregirlo y la regresión pasó. Los antecedentes y logs originales no se modificaron. Plugin GitHub instalado; OAuth/permisos pendientes de comprobar. La solicitud de revisión concreta se mantiene en el flujo Git, sin aprobación simulada.
