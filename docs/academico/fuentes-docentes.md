# Fuentes y reglas de interpretación

**Consolidación:** 2026-10-21; cotejo de originales 2026-10-22. Se usa la lectura de diapositivas/notas de F1/F2, seguida del TXT, y los hallazgos de auditoría. Los originales no se publican sin autorización; nombres, hashes y localizadores permiten identificarlos sin rutas privadas. Las tablas de extracción siguientes son la copia portátil de las indicaciones metodológicas utilizada por el equipo; no contienen contactos empresariales. La publicación íntegra de originales es opcional y distinta de identificar/cotejar la fuente.

## Inventario de fuentes

| ID | Nombre del original | SHA-256 / alcance |
|---|---|---|
| F1 | U2-S3 TEMA ING SOFTWARE.pptx | 433ec0d96ef0324e11ae69a1fac3e3857b431087c0cfae69e8c11569d1eed002; 10 diapositivas y notas. |
| F2 | U2-S4 TEMA ING SOFTWARE.pptx | 32fd27e696717961d019a431d7450c1d32c7a40cc1f7ccb01ab69d3723ee7040; 25 diapositivas y notas. |
| F3 | U2-S3 CLASE EN TEXTO.txt | 7d12d6b6c09b19febfe3240f0b206b42349bc781d7a3362ac062524bd11b35c1; 5 líneas físicas extensas, sin marcas temporales fiables. |
| F4 | INFORMACION - Analisis_Requerimientos_Ecolactus_Guata.docx | ddcdde415314bb0dc8c8fc83a626dc1a374c374c0adef6bc728eb0b9c226c74c; §13 61 RF, §14 17 RNF. |
| F5 | U2-S1 TEMA ING SOFTAWRE.pptx | 49b67a8c18b6b523ca3660e60215155963bb559cf0fb3969107ec9a96ed8a90a; contenido **Unidad 3 OpenUP**, 16 diapositivas. |
| F6 | INFORMACION - Informe_Cambios_Ecolactea_Excel.docx | b5e960ebada83bf7f370fdf49bed627eb942f6e23d24a30ccc9177c37d096725; informe de adaptación, no arquitectura vigente. |

“d” identifica diapositiva. F4 describe una reunión empresarial de agosto como antecedente; no es acta de aprobación del Charter ni de aceptación de la aplicación.

## Extracción principal: PPT primero

| Fuente/localizador | Indicación | Aplicación |
|---|---|---|
| F1 d5–8 | Tres responsabilidades, cinco eventos, tres artefactos; Scrum/Kanban/XP. Kanban visualiza y limita trabajo. | Proceso breve, roles y políticas. Valores WIP de ejemplos no son obligatorios para todos. |
| F1 d9–10/notas | Proceso 1–2 páginas, roles, calendario 21-09→23-11, día/hora/timebox/participantes, DoD, herramienta/enlaces y justificación; propuesta de cuatro quincenas y cierre. | Síntesis breve; calendario diferenciado. |
| F1 d10/notas | Evaluación 19-10 y sustentación 23-11. | No inventar entrega/confirmación frente a F2/F5. |
| F2 d5–7 | PO ordena valor; SM facilita; Developers incluyen diseño/pruebas/docs. PO/SM pueden aportar técnicamente en equipo pequeño. Sprint contenedor; Planning ~2 h, Daily 15 min, Review ~1 h, Retro 45 min para semana. | Roles, eventos y capacidad propuesta. |
| F2 d8–10/notas | 3C; Como/Quiero/Para; INVEST; mínimo 10 HU trazadas a RF del Charter; dos escenarios, feliz/error; acta de roles y al menos tres usuarios del sistema. | Catálogo funcional refinado, siete roles F4 por homologar; acta pendiente. |
| F2 d12–17 | Product/Sprint Goal y DoD; DEEP/MoSCoW/Poker; tareas distintas de puntos; selección según capacidad. Una herramienta real con capturas; dividir >13. | Fuente única de backlog/tareas, estimaciones hipotéticas por votar y herramienta pendiente. |
| F2 d15 | PR aprobado, otro revisor, unitarias/aceptación, integración sin errores, ambiente de pruebas y documentación mínima. | DoD verificable, no cumplimiento inferido. |
| F2 d19 | Sprints consecutivos de una semana jueves→jueves; calidad constante; PO cancela si Goal obsoleto. | Cadencia futura seleccionada, sin retroatribución. |
| F2 d20 | Cinco fases de Retro, revisar acciones previas y decidir 1–3 acciones con dueño/fecha. | Agenda/plantilla común y propuestas rotuladas. |
| F2 d21–24 | Velocidad, cumplimiento, Goal, burndown/burnup y cycle time; caso Delta. | Gráficos existentes conservados; Scrum real N/D. |
| F2 d25/notas | Evaluación 22-10, examen y exposición, producto 20 %, sesiones 5 %; actas/capturas/backlogs/indicadores; OpenUP desde 29-10. | Matriz sin inventar una rúbrica oficial de 100 puntos ni adopción de OpenUP. |

## Después: TXT y localización reproducible

| Localizador F3 | Indicación | Tratamiento |
|---|---|---|
| Línea 1, “acopiar”, “control de calidad y producción” | Valor del núcleo; mapas menos prioritarios. | Orden propuesto acopio/calidad/producción, mapas Could. PO ratifica. |
| Línea 1, “Scrum Master no es jefe” | PO representa valor, SM facilita, Developers se comprometen al plan. | No asignación jerárquica ni autoría inventada. |
| Líneas 1/5, ejemplos de “dos semanas” | Ejemplos/experiencia; no prueba cadencia aplicada del equipo. | No reemplazan F2 d19 ni actas faltantes. |
| Línea 5, “refinamiento” | Preparar el siguiente sprint durante el actual. | Actividad continua y esfuerzo a contabilizar. |
| Línea 5, fragmento “pongan dos, tres pantallazos” y “develop” | PR hacia develop, otro revisor, 2–3 capturas, unitarias, integración, despliegue, README/API. | Flujo propuesto; main confirmada por API pública el 22-10, aprobación PR pendiente EP-07. |
| Línea 5, fragmento “No. Necesariamente tiene que estar tal cual” / “simular” | Gráficos académicos simulados permitidos. | Delta rotulado, sin atribuir sus datos a Ecolactea. |
| Línea 5, “básicamente dos sprints”, “inicio de clases”, “primer día de clases de la segunda” | Dos representaciones académicas con límites por evaluación/periodo. | AC-01/02 separados de diez ventanas semanales; fechas exactas pendientes. |
| Línea 5, “jueves”, “post-its”, “papelotes”, “plumones”, “Cuatro horas” | Simulación presencial con fotos y observación docente. | Asignación/asistencia/fotos del equipo pendientes; no asumir todas las actividades de grupos. |

No inventar timestamps de clase. Errores de transcripción, como cuatro eventos o llamar artefacto al Planning, no sustituyen definiciones formales de F1/F2.

## Fuente adicional y discrepancias de Unidad I/III

F5 d1 corresponde a U3 (29-10). D12 ofrece calendario **referencial ajustable por equipo**: 29-10→04-11 Inicio, 05→11-11 Elaboración, 12→18-11 Construcción, 19→26-11 Transición y **entrega final 26-11**. D13 propone correspondencia Charter/Vision, RF/casos de uso, backlogs/planes de iteración; d15 pide traer proceso OpenUP el 05-11. Esto añade un hito académico en conflicto con 23-11; **no cambia Scrum + Kanban seleccionado**.

Los Excel “REALIZAR PRIMER DOCUMENTO U1-S2…” y “REALIZAR SEGUNDO DOCUMENTO U1-S3…” contienen respectivamente laboratorios y facturación electrónica. F6 afirma adaptación (56 requisitos, 40 RF/16 RNF), pero su inventario y F4 no coinciden con esa cifra: usar los 61/17 originales, sujetos a homologación. **Angular 22/Ionic/Capacitor pertenece a F6, no a F4 ni a la arquitectura actual KMP/Laravel.**

Las guías U1 de ejemplos (SIREL/laboratorio/facturación) no se convierten automáticamente en restricciones del producto. No se encontró una rúbrica analítica oficial de 100 puntos para U2. El 55/100 anterior fue orientativo de auditoría, no nota docente.

## Regla de decisión

Usuario elige Scrum + Kanban con cadencia F2. Necesidades: F4 hasta Charter aprobado. Funcionamiento/tecnologías actuales: fuente Git inspeccionada, no F6. Fechas docentes contradictorias: **PENDIENTES DE VALIDACIÓN**, [calendario](../scrum/calendario-y-capacidad.md). No publicar contactos ni datos privados de las fuentes.

## Cotejo reproducible y portabilidad — 2026-10-22

Se releyeron PPT F1/F2 (diapositivas y notas) antes del TXT F3; las seis huellas del inventario coinciden con los originales disponibles. Puede repetirse con `python3 docs/herramientas/verificar_documentacion.py --fuentes-dir RUTA_DEL_CURSO`. La ruta la aporta cada integrante y no se versiona. Las dos tablas de extracción mantienen contenido, localizadores y tratamiento de todas las indicaciones aplicadas; viajan con el repositorio sin requerir rutas personales.

**EP-15 resuelto para identificación, extracción metodológica y cotejo**. No se inventa URL institucional. Los originales íntegros no forman parte del paquete público; compartirlos de forma privada o conseguir un enlace institucional sigue siendo una opción del curso, no requisito docente demostrado ni bloqueo artificial del cierre. Las discrepancias de calendario/Charter sí siguen pendientes de las personas que pueden resolverlas.
