# Ecolactea Digital: contexto para continuar

Instantanea del 26-10-2026, America/Lima. Repositorio local: `C:/Users/ASUS/Documents/APP-MOVIL-SAITxd`. Revision y alcance exactos en `scope.json`.

## Objetivo

Conservar el acopio y la calidad de leche trazables en Huata, Puno, aun sin conexion; confirmar la recepcion remota y ofrecer consultas y liquidaciones a productores. Fuente: `docs/scrum/product-backlog.md`, Product Goal. El nombre Gradle es EcolectaHuata y el producto se documenta como Ecolactea Digital.

## Arquitectura observada

- `shared/`: Kotlin Multiplatform y Compose, casos de uso, repositorios, Koin, SQLDelight/SQLite y cliente Ktor.
- `androidApp/`: entrada Android y configuracion; Firebase puede ser un destino adicional cuando esta configurado.
- `web/`: Laravel, API movil, panel Blade y persistencia Eloquent. El panel es la fuente oficial descrita para sincronizacion.
- `iosApp/` y `shared/src/iosMain/`: estructura iOS existente; la documentacion describe limitaciones de configuracion remota y validacion. No asumir paridad por compartir Kotlin.
- Flujo de referencia: captura local -> persistencia -> pendiente -> envio/reintento -> confirmacion remota. Guardado local no equivale a recepcion en el servidor.

Fuente principal: `docs/tecnica/arquitectura-datos-y-sincronizacion.md`; entradas de DI y rutas incluidas en el grafo. Las versiones exactas deben comprobarse en los manifiestos antes de implementar.

## Areas funcionales

Cuatro roles oficiales: Administrador (`admin`), Acopiador (`acopiador`), Calidad (`calidad`) y Proveedor (`proveedor`). Los modulos abarcan cuentas/permisos, catalogos de proveedores/zonas/vehiculos, jornadas y entregas, calidad/OCR, sincronizacion, reportes/liquidaciones, mapas, portal del proveedor y comunicados.

El alcance Coder 1 corresponde a cuentas, roles, permisos, proveedores, zonas, vehiculos, asignaciones y sus secciones de autenticacion/sincronizacion. Reglas de calculo de otros modulos y nuevas delegaciones de permisos requieren sus fuentes y acuerdos propios. Fuente: `docs/tecnica/coder1-implementacion.md`, secciones A y B.

## Estado y pendientes documentados

- Corte Coder 1 del 25/26-10: 10 tareas completadas y 2 pendientes en el alcance tecnico local de su diagnostico. No equivale a historias completas Done.
- Sesion y revocacion conocidas, PIN nuevos, conservacion de legados, asignaciones, conflictos de catalogo y proteccion Android tienen implementacion/pruebas descritas.
- La politica de vigencia offline y parte de los recorridos graficos/manuales y comprobaciones en hardware/campo permanecen pendientes segun el corte. Consultar T04/T12 y su tabla vigente antes de actuar.
- Documentos mas antiguos describen pendientes de Charter, reglas/criterios, acuerdos del equipo, Revision/PR, beneficiario, evidencias academicas e iOS. Son registros fechados; comprobar el estado actual antes de afirmar que siguen abiertos.
- Los ultimos commits locales incluyen integracion de login/migracion/arranque API y precios por temporada/inicio de proveedor. El mapa documental no es una auditoria exhaustiva de esos cambios recientes.

Fuentes: `docs/tecnica/coder1-implementacion.md`, `docs/academico/evidencias-pendientes.md`, `docs/pruebas/cobertura-y-cierre.md` y el historial local.

## Como retomaremos el trabajo

1. Consultar este contexto y `GRAPH_REPORT.md`; usar `graphify query`, `explain` o `path` para encontrar fuentes.
2. Abrir la historia/criterios del backlog, la tarea y el contrato tecnico correspondientes. Verificar fuente y fecha frente al codigo actual.
3. Acordar el siguiente cambio concreto con el usuario. La seleccion documental no constituye seleccion de sprint ni autorizacion de despliegue.
4. Implementar dentro del modulo correspondiente, comprobar sus pruebas y actualizar el conocimiento cuando cambien contratos o comportamiento.

## Alcance del mapa

Documentacion Markdown vigente y nueve entradas de arquitectura: 63 archivos (capa semantica del 26-10). Se excluyen capturas, recibos automaticos, antecedentes, plantillas y documentos binarios. No se ha ejecutado ninguna suite del producto para producir este mapa.

Ampliacion del 28-10-2026: extraccion completa de codigo por AST local (989 archivos, commit `487f8058`) unida al grafo documental. Los documentos creados o modificados despues del 26-10, como los tres nuevos de `docs/tecnica/` sobre estados de acceso, registro unificado de proveedores y roles personalizados, no tienen nodos semanticos. Tras cambios de codigo, `graphify update .` refresca solo la parte de codigo; la documentacion modificada requiere lectura semantica aparte. Las pruebas citadas son evidencias existentes con fecha y entorno propios.

`graph.json` contiene conexiones EXTRACTED, INFERRED o AMBIGUOUS con sus fuentes. Los nodos destacados representan conectividad en esta seleccion, no una prioridad de negocio. Uso de tokens del anfitrion: N/D, no cero.
