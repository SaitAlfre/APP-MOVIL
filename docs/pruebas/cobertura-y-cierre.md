# Cobertura comprobada y cierre técnico del 22-10, hora Lima

**Actualización 23-10:** los cambios técnicos ya tienen commits locales. El árbol fuente coincide por SHA-256 con las suites completas y Android instrumentado aquí referenciados; no se atribuyen ejecuciones nuevas a integrantes ni se sustituyen los recibos antiguos. La certificación de las 41 HU continúa bloqueada por DOD-02 y los CA indicados. [Solicitud de revisión preparada](../scrum/flujo-git-y-revision.md#solicitud-de-revisión-preparada--2026-10-23).

[Resultados nuevos y huellas](ejecuciones/automaticas/README.md) · [Ejecuciones originales](ejecuciones/resultados-2026-10-22.md) · [DoD](../scrum/definition-of-done.md) · [EP-01…15](../academico/evidencias-pendientes.md)

**Ejecutante:** agente técnico autorizado. Base 05743b9 más árbol fuente identificado en cada recibo; sin commit nuevo. Las ejecuciones después de 00:00 UTC del 23 corresponden todavía al 22 en Lima. No se retrofechan ceremonias ni pruebas.

## Resultado y límites

Se ejecutaron suites completas del contrato implementado: JVM 259/259 (incluye 5 integraciones Ktor→API→SQLite), PHP 289/289 y Firestore 9/9. Builds web/preview satisfactorios. El índice anterior conserva cada intento y su estado; usar la última ejecución de cada suite, sin sumar reintentos. Las pruebas instrumentadas y UI se detallan en sus recibos. **0 de 41 HU con DoD integral acreditada**: aprobación independiente/PR y aceptación completa siguen pendientes. Esto no equivale a ausencia de funcionalidades.

## Revisión de las 41 historias

Esta tabla es una vista de verificación, no otra fuente de historias, prioridad, puntos, responsables o estado. Esos datos siguen únicamente en el [backlog](../scrum/product-backlog.md), [tareas](../scrum/tareas.md) y RF/RNF. Los nombres de clase indican relación técnica, **no cobertura de todos los CA de la HU**. Los casos individuales ejecutados están en los recibos JVM/PHP. En todas las filas falta DOD-02 y comprobar los CA acordados; no cerrar por una suite verde.

| HU canónica | Evidencia técnica relacionada ejecutada | Bloqueo específico adicional / alcance |
|---|---|---|
| [HU-01](../scrum/product-backlog.md#hu-01) | LoginOfflineUseCaseTest, MovilAuthenticationTest, AuthenticationTest | Homologación de 7 perfiles y restricciones web/móvil; PR/CA completos. |
| [HU-02](../scrum/product-backlog.md#hu-02) | CrearProveedorUseCaseTest, ActualizarProveedorUseCaseTest, ProveedorManagementTest | Alcance de productores y CA ratificados. |
| [HU-03-A](../scrum/product-backlog.md#hu-03-a) | RegistrarEntregaUseCaseTest, PersistenciaListaAcopioTest; CP-001; NAT-04/05 | Pruebas físicas/campo y PR; no se repite como Not Run lo ya ejecutado. |
| [HU-03-B](../scrum/product-backlog.md#hu-03-b) | RegistrarLoteUseCaseTest, CicloYListaAcopioTest | Protocolo de lote/punto y CA ratificados. |
| [HU-04-A](../scrum/product-backlog.md#hu-04-a) | SincronizacionPanelWebTest, IntegracionPanelWebTest, SincronizacionMovilTest; CP-002/007 | PR aprobado e incorporación del parche a destino; aceptación empresarial. |
| [HU-04-B](../scrum/product-backlog.md#hu-04-b) | ResolverConflictoUseCaseTest, CambiosMovilTest | Precedencia exacta entre dispositivos debe homologarse antes de cambiar política. |
| [HU-05-A](../scrum/product-backlog.md#hu-05-a) | ControlCalidadTest, DatosMovilTest; UI-WEB-03/04 | Contrato muestra/equipo/origen/unidad; no aceptación sanitaria. |
| [HU-05-B](../scrum/product-backlog.md#hu-05-b) | ControlCalidadTest, AdminZonasVehiculosCalidadProduccionLiquidacionesTest | Umbrales y vigencias de calidad acordados. |
| [HU-05-C](../scrum/product-backlog.md#hu-05-c) | CalidadVisitasTest, LoginCalidadFaonTest, AdminNavegacionYPermisosTest | Permisos exactos del plan inopinado; pruebas actuales no certifican no divulgación global. |
| [HU-05-D](../scrum/product-backlog.md#hu-05-d) | CalidadVisitasTest | Alcance visitas/capacitación y datos autorizados. |
| [HU-06](../scrum/product-backlog.md#hu-06) | ControlCalidadTest; NAT-02/03 con motor ML Kit real | Fotografía/cámara y comprobantes físicos acordados; imágenes actuales son sintéticas. |
| [HU-07-A](../scrum/product-backlog.md#hu-07-a) | ReporteDashboardTest, AdminDashboardTest; UI-WEB-09/10 | Homologación de fecha UTC/Lima y alcance del informe. |
| [HU-07-B](../scrum/product-backlog.md#hu-07-b) | ControlCalidadTest, AdminZonasVehiculosCalidadProduccionLiquidacionesTest | Contenido de informe de calidad por ratificar. |
| [HU-07-C](../scrum/product-backlog.md#hu-07-c) | AdminLiquidacionDetalleTest; UI-WEB-08B cálculo 30×1.70=51 | Tarifa/vigencia por proveedor y caso sin precio; fixture 170 no es cálculo probado. |
| [HU-07-D](../scrum/product-backlog.md#hu-07-d) | AuditoriaSeguridadTest, AdminLiquidacionDetalleTest | Corrección de pagos y permisos empresariales ratificados; no pago real ejecutado. |
| [HU-07-E](../scrum/product-backlog.md#hu-07-e) | AdminLiquidacionDetalleTest | Impresora física/formato autorizado; window.print no acredita térmica. |
| [HU-08](../scrum/product-backlog.md#hu-08) | AdminZonasRutasVehiculosTest; UI-WEB-06/07 | Caso de productor sin coordenadas y disponibilidad/proveedor de mapas; sin recorrido físico. |
| [HU-09](../scrum/product-backlog.md#hu-09) | DatosPanelWebTest, DatosMovilTest | Alcance completo de catálogo por rol/plataforma ratificado. |
| [HU-10](../scrum/product-backlog.md#hu-10) | CambiosPanelWebTest, CambiosMovilTest; BUG-003 | Exclusiones de cuerpos nulos y estados; no se inventa autor. OBS-02 parcial. |
| [HU-11-A](../scrum/product-backlog.md#hu-11-a) | PortalProveedorTest, PortalProveedorViewModelTest, ObtenerPerfilProveedorUseCaseTest | Privacidad/calidad visible entre productores por ratificar. |
| [HU-11-B](../scrum/product-backlog.md#hu-11-b) | PortalProveedorTest, DatosMovilTest | Alcance de pagos y aceptación del productor. |
| [HU-11-C](../scrum/product-backlog.md#hu-11-c) | ReglaEdicionEntregaTest, CorregirEntregaUseCaseTest | Regla de reclamo del día, autorización y fecha local por homologar. |
| [HU-12-A](../scrum/product-backlog.md#hu-12-a) | DatosMovilTest, ControlCalidadTest | Notificación individual y consentimiento/visibilidad por rol ratificados. |
| [HU-12-B](../scrum/product-backlog.md#hu-12-b) | DatosMovilTest, CambiosMovilTest | Alcance destinatarios/recepción de comunicado; no se envían mensajes a personas. |
| [HU-13](../scrum/product-backlog.md#hu-13) | RefreshSesionPanelWebTest, RefreshTokenMovilTest; UI-WEB-11 | PR/revisión y paridad iOS; no tokens reales publicados. |
| [HU-15-A](../scrum/product-backlog.md#hu-15-a) | AdminModulesTest, DatosMovilTest | Delimitar reunión/evento y asistencia con beneficiario; no acta ficticia. |
| [HU-16-A](../scrum/product-backlog.md#hu-16-a) | RegistrarLoteUseCaseTest, AdminProduccionModuleTest, PlantaRecetasInventarioTest | Campos exactos y fórmula de costo acordados. |
| [HU-16-B](../scrum/product-backlog.md#hu-16-b) | AdminProduccionModuleTest, PlantaRecetasInventarioTest | Rendimiento/merma y umbral ratificados. |
| [HU-16-C](../scrum/product-backlog.md#hu-16-c) | AdminProduccionModuleTest | Destino de lote y protocolo confirmado. |
| [HU-17-A](../scrum/product-backlog.md#hu-17-a) | PlantaRecetasInventarioTest, AdminFigmaModulesTest | CA/stock y aceptación de flujo completo. |
| [HU-17-B](../scrum/product-backlog.md#hu-17-b) | PlantaRecetasInventarioTest | Tipos de cliente y precio/vigencia ratificados. |
| [HU-17-C](../scrum/product-backlog.md#hu-17-c) | PlantaRecetasInventarioTest | Mínimos/alerta acordados. |
| [HU-17-D](../scrum/product-backlog.md#hu-17-d) | ReporteDashboardTest, AdminFigmaModulesTest | Fórmula de rentabilidad/costo y alcance de informe. |
| [HU-17-E](../scrum/product-backlog.md#hu-17-e) | PlantaRecetasInventarioTest | Campos de despacho y responsable ratificados. |
| [HU-18](../scrum/product-backlog.md#hu-18) | AutorizarTrasladoUseCaseTest, SincronizacionMovilTest | Anticipación, permisos y devolución de traslado acordados. |
| [HU-19](../scrum/product-backlog.md#hu-19) | ControlCalidadTest | Regla de medición no confirmada y mensaje aceptado. |
| [HU-20](../scrum/product-backlog.md#hu-20) | Sin prueba térmica física en este entorno | Impresora/batería/formato y dos copias físicas; falta hardware. |
| [HU-21](../scrum/product-backlog.md#hu-21) | AdminZonasVehiculosCalidadProduccionLiquidacionesTest | OBS-01: importes fijos implementados no equivalen a porcentajes/reincidencia F4. |
| [HU-22](../scrum/product-backlog.md#hu-22) | DatosMovilTest, PortalProveedorTest | Política de privacidad entre productores; no publicarla por suposición. |
| [HU-23](../scrum/product-backlog.md#hu-23) | AdminRecepcionModuleTest, SincronizacionMovilTest | Conciliación campo/planta y futura medición caudalímetro acordadas. |
| [HU-24](../scrum/product-backlog.md#hu-24) | AdminNavegacionYPermisosTest, AdminModulesTest | Permisos y vigencia de configuración ratificados. |

## Checklist de controles

| Control | Estado comprobado / límite |
|---|---|
| Desarrollo | BUG-001/002 previos conservados; BUG-003 corregido con regresión roja→verde. No cambian reglas de negocio no ratificadas. |
| Pruebas | Suites y UI reales; no reemplazar CP-004/005/006 Blocked por aceptación completa. Se preservan fallos anteriores. |
| Integración |5 integraciones reales y builds locales Passed; integración/PR a rama acordada pendiente. |
| Documentación |41 HU,86 CA,82 escenarios,91 tareas,61 RF y 17 RNF; enlaces/IDs/fuentes/recibos verificables por herramienta. Resultado final en control de publicación. |
| Calidad | Logs saneados, huellas, aislamiento, entradas OCR rotuladas, sin producción. PR y validación sanitaria pendientes. |
| DOD-01 | Parcial: comportamientos comprobados, no todos los CA homologados. |
| DOD-02 | Pendiente: no revisión por otro integrante ni aprobación PR nueva. |
| DOD-03 | Parcial: unitarias/integraciones verdes; aceptación/plataformas/campo incompletos. |
| DOD-04 | Parcial: integración técnica local; no commit/merge a destino por instrucción. |
| DOD-05 | Cumple en entorno local temporal ficticio; no servicio permanente ni cloud. |
| DOD-06 | Artefactos y límites documentados; revisión por incremento pendiente. |
| DOD-07 | Parcial: evidencias técnicas trazadas; RNF completos no certificados. |

## Revisión de los 17 RNF

Se conservan textos, controles y estados en la [fuente RNF](../requisitos/rnf-fuente-y-cobertura.md). Resultado de esta ejecución, sin sustituir criterios:

| RNF | Verificación posible realizada y límite restante |
|---|---|
| RNF-01/02 | Acopio/PENDING, reapertura y reintento/UUID comprobados; BUG-003 conserva cambio sin autor. No certifica todo calidad offline ni política de conflictos. |
| RNF-03/04 | Suites comprueban reglas/casos funcionales; no representan disponibilidad 04:30–05:30,390 proveedores/4 actores en campo, batería o percentiles. El entorno emulado no acredita esos RNF; faltan umbrales/hardware/protocolo acordados. |
| RNF-05 | Android API 36 x 86_64 emulado verificado; no teléfonos gama baja/media físicos ni iOS/Xcode. |
| RNF-06/07 | Autenticación, permisos, revocación/corrección y Firestore probados según contrato existente. Matriz de roles y auditoría integral empresarial por homologar. |
| RNF-08/15 | Pruebas actuales de permisos no equivalen a autorización de visibilidad del control inopinado o entre productores. Falta política del beneficiario. |
| RNF-09/10 | Capturas UI y OCR sintético disponibles; no sustituyen frío/guantes/campo o impresión térmica física. |
| RNF-11 | Parámetros/cálculo existentes ejercitados. Tarifas/vigencias empresariales no ratificadas. |
| RNF-12 | Arquitectura mantiene entrada manual→caso de uso→repositorio; una futura lectura de caudalímetro debe convertirse en entrada del mismo caso de uso, sin afirmar integración de hardware existente. Contrato IoT físico queda Fase 2 propuesta. |
| RNF-13 | Verificador, recibos, pruebas de herramientas y CI documental preparados; revisión por compañero y ejecución remota aún pendientes. |
| RNF-14 | Backup/restauración SQLite sintética comprobada con integridad y 20L; prueba de mecanismo, no copia real servidor privado/nube, retención anual ni dimensionamiento 5TB. Sin credenciales de infraestructura no se configura un destino inventado. |
| RNF-16 | Recorrido de acceso/calidad/mapa/liquidaciones/reportes y captura Android en español. No se certifica 100% de pantallas/errores/iOS. |
| RNF-17 | Análisis ficticio guardado con fecha/proveedor/valores y pruebas actuales; muestra/lote/versión parámetros y validación sanitaria aún pendientes. |

## Pendientes genuinos

Se mantienen EP-01…07 y EP-11…14 por acuerdos/votos/ceremonias/roles, docente, Charter, autenticación Projects, revisión independiente, beneficiario y aula. EP-08/09 tienen evidencia técnica ampliada; EP-10/15 resueltos solo en su alcance declarado. No hay HU Done, velocidad Scrum ni movimientos históricos inventados. No se requiere repetir las pruebas ya ejecutadas para resolver un pendiente humano; repetir controles afectados al cambiar código/contrato.

Las divergencias CAL-01…06 siguen **PENDIENTE DE VALIDACIÓN**; se conserva jueves→jueves deF2 y no se inventa nueva fecha final. Scrum es el marco principal documentado y Kanban su complemento; adopción efectiva del equipo todavía no acreditada.

## Resultado Android definitivo

[EXE-20261023T041640Z-android-ui-60b48a](ejecuciones/automaticas/EXE-20261023T041640Z-android-ui-60b48a/README.md): **5/5 Passed**. NAT-01 exige los tres textos del formulario en XML UI real y captura; NAT-02/03 ejecutan ML Kit con comprobantes sintéticos; NAT-04/05 verifican20L/PENDING y el mismo UUID tras cierre/reapertura sin WiFi/datos/túnel ADB. Es Android API 36 emulado, no teléfono físico ni cámara. Los [intentos y limitaciones del arnés](ejecuciones/automaticas/incidencias-montaje/README.md) se conservan.

La UI web tiene11 comprobaciones Passed y2 incidencias Blocked del arnés conservadas; no se declara toda su ejecución Passed ocultando esas incidencias. Los flujos funcionales continuaron y sus resultados individuales son observables. [Recibo UI web](ejecuciones/automaticas/EXE-20261023T040523Z-web-ui-observada/README.md).
