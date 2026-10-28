# Alcance y limites

Documentacion Markdown vigente y entradas principales de arquitectura; excluye capturas, antecedentes, plantillas, documentos binarios y recibos automaticos; no es indice completo del codigo.

Tokens semanticos: N/D; los ceros tecnicos del generador no representan consumo nulo.

## Limites de integridad del mapa

El pase AST contiene 140 referencias a simbolos sin definicion en los nueve archivos seleccionados. Graphify las representa como referencias sin verificar; este mapa no prueba sus implementaciones. La vista simple agrupa enlaces repetidos entre los mismos nodos (por ejemplo, muchas citas del backlog al documento de tareas). La extraccion original completa, con sus localizadores, se conserva en EXTRACTION_AUDIT.json y el diagnostico en GRAPH_HEALTH.md. No se descartan las fuentes originales.

# Graph Report - APP-MOVIL-SAITxd  (2026-10-26)

## Corpus Check
- 63 files · ~78,618 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 585 nodes · 1508 edges · 12 communities (8 shown, 4 thin omitted)
- Extraction: 100% EXTRACTED · 0% INFERRED · 0% AMBIGUOUS · INFERRED: 2 edges (avg confidence: 0.85)
- Token cost: N/D (el anfitrion no expone contadores; no equivale a cero)

## Community Hubs (Navigation)
- Requisitos e historias
- Evidencias y validacion academica
- Planificacion y trazabilidad
- Arquitectura y contratos Coder 1
- Inicio y configuracion de plataformas
- Pruebas y defectos documentados
- Portal del proveedor
- Producto y validacion manual

## God Nodes (most connected - your core abstractions)
1. `Requisitos funcionales: fuente y cobertura` - 67 edges
2. `Product Backlog — catálogo canónico` - 57 edges
3. `Inventario documental y preparación para GitHub` - 24 edges
4. `Requisitos no funcionales — controles trazables` - 23 edges
5. `Documentación de Ecolactea Digital` - 22 edges
6. `HU-01 — Acceder según perfil y ruta` - 19 edges
7. `HU-03-A — Registrar una entrega offline` - 18 edges
8. `Matriz de cumplimiento académico y operativo` - 16 edges
9. `HU-04-A — Reintentar entregas con confirmación remota` - 16 edges
10. `Consolidación documental y cierre técnico — 22-10-2026` - 15 edges

## Surprising Connections (you probably didn't know these)
- `Inventario documental y preparación para GitHub` --references--> `Ecolactea Digital`  [EXTRACTED]
  docs/academico/archivos-documentacion.md → README.md
- `Ecolactea Digital` --references--> `Validación manual T12 de Coder 1`  [EXTRACTED]
  README.md → docs/pruebas/ejecuciones/coder1-t12-manual.md
- `iniciarKoinIOS()` --calls--> `moduloServidorWeb()`  [INFERRED]
  shared/src/iosMain/kotlin/pe/ecolecta/di/KoinIOS.kt → shared/src/commonMain/kotlin/pe/ecolecta/di/Koin.kt
- `iniciarKoinIOS()` --calls--> `iniciarKoin()`  [INFERRED]
  shared/src/iosMain/kotlin/pe/ecolecta/di/KoinIOS.kt → shared/src/commonMain/kotlin/pe/ecolecta/di/Koin.kt
- `Inventario documental y preparación para GitHub` --references--> `Acceso y datos del entorno de pruebas`  [EXTRACTED]
  docs/academico/archivos-documentacion.md → docs/pruebas/acceso-y-datos-de-prueba.md

## Import Cycles
- None detected.

## Communities (12 total, 4 thin omitted)

### Community 0 - "Requisitos e historias"
Cohesion: 0.05
Nodes (124): Decision 25-10: Acopiador, Calidad, Administrador y Proveedor, Requisitos funcionales: fuente y cobertura, RF-ACO-01 — Registrar cada entrega: proveedor, fecha/hora, cantidad de litros y observación., RF-ACO-02 — Asociar automáticamente acopiador, unidad/vehículo y ruta según la sesión del acopiador., RF-ACO-03 — Registrar litros por recipiente (tacho) y validar coherencia (ej. aviso si un tacho supera su capacidad configurada, tipo 34 L)., RF-ACO-04 — Permitir registrar a los 5-6 proveedores que entregan juntos en un mismo punto (modo rápido/lote)., RF-ACO-05 — Registrar sin internet y dejar cada registro en estado Pendiente., RF-ACO-06 — Consulta del total de litros por día, proveedor, ruta y acopiador. (+116 more)

### Community 2 - "Evidencias y validacion academica"
Cohesion: 0.07
Nodes (67): Inventario documental y preparación para GitHub, Exclusiones de publicacion, Inventario documental publicable, Verificacion documental reproducible, Caso Delta simulado, Cumplimiento Delta 84.04 por ciento, Caso Delta — indicadores y retrospectiva académica, Velocidad Delta 19.75 SP (+59 more)

### Community 3 - "Planificacion y trazabilidad"
Cohesion: 0.08
Nodes (48): CP, EXE, BUG y EV son registros distintos, Cómo documentar pruebas web y móviles, Documentación de Ecolactea Digital, Fuentes canonicas y Project operativo, Hitos docentes 23/26 de noviembre pendientes de confirmar, Capacidad real pendiente de confirmacion, Calendario y capacidad — fuente principal, Definition of Done — estándar del incremento (+40 more)

### Community 5 - "Arquitectura y contratos Coder 1"
Cohesion: 0.08
Nodes (22): Usuarios, zonas y vehículos, Administrador móvil: nota histórica, Ficha productiva y cuenta vinculada, Supervisión y administración móvil, Arquitectura y sincronización de Ecolactea, Contratos Coder1 del 25/26-10, iOS: remoto y OCR pendientes, API Laravel como destino oficial (+14 more)

### Community 6 - "Inicio y configuracion de plataformas"
Cohesion: 0.08
Nodes (8): EcolectaApp, EstadoInicio, ERROR, LISTO, PREPARANDO, iniciarKoin(), moduloServidorWeb(), iniciarKoinIOS()

### Community 7 - "Pruebas y defectos documentados"
Cohesion: 0.19
Nodes (13): BUG-001 expectativas PHP, BUG-002 ANR Android, BUG-003 autor vacio retenido, Defectos e incidencias, Fecha UTC Lima por validar, Ejecuciones reales, Ejecuciones 22 octubre conservadas, Recibos posteriores separados (+5 more)

### Community 8 - "Portal del proveedor"
Cohesion: 0.31
Nodes (9): Acceso a perfiles de prueba, Datos ficticios en entorno aislado, Acceso y datos del entorno de pruebas, Revision de secretos de demostracion pendiente, Consultas propias de entregas, calidad y pagos, Portal móvil del proveedor, localPreview aislado de Firebase normal, Alcance remoto posterior a nota original (+1 more)

### Community 9 - "Producto y validacion manual"
Cohesion: 0.39
Nodes (7): Validación manual T12 de Coder 1, T04 limite offline pendiente, T12 Coder1 20 Passed declarados, T12 pruebas coordinadas y dispositivos declarados, Arquitectura movil KMP y backend Laravel, Cuatro perfiles oficiales, Ecolactea Digital

## Knowledge Gaps
- **3 isolated node(s):** `PREPARANDO`, `LISTO`, `ERROR`
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 205 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **4 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Requisitos funcionales: fuente y cobertura` connect `Requisitos e historias` to `Planificacion y trazabilidad`, `Pruebas y defectos documentados`?**
  _High betweenness centrality (0.020) - this node is a cross-community bridge._
- **What connects `PREPARANDO`, `LISTO`, `ERROR` to the rest of the system?**
  _3 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Requisitos e historias` be split into smaller, more focused modules?**
  _Cohesion score 0.05236845394325709 - nodes in this community are weakly interconnected._
- **Why does `Product Backlog — catálogo canónico` connect `Requisitos e historias` to `Planificacion y trazabilidad`, `Pruebas y defectos documentados`?**
  _High betweenness centrality (0.020) - this node is a cross-community bridge._
- **Should `Referencias de inyeccion compartida` be split into smaller, more focused modules?**
  _Cohesion score 0.018018018018018018 - nodes in this community are weakly interconnected._
- **Should `Evidencias y validacion academica` be split into smaller, more focused modules?**
  _Cohesion score 0.06741250717154332 - nodes in this community are weakly interconnected._
- **Should `Planificacion y trazabilidad` be split into smaller, more focused modules?**
  _Cohesion score 0.07792207792207792 - nodes in this community are weakly interconnected._