# Alcance y limites

Instantanea combinada al 28-10-2026 (America/Lima), commit `487f8058`. Detalle en `scope.json`.

- **Codigo (extraccion completa, 28-10):** AST local de 989 archivos de `shared/`, `androidApp/`, `iosApp/`, `web/` (app, rutas, pruebas, migraciones, vistas Blade), `scripts/` y `firebase-tests/`. Excluye lo ignorado por git (`vendor`, `node_modules`, `build`), `.npmrc` y 2 archivos `.sql`. Sin etiquetado LLM: cada comunidad se nombra por su nodo central.
- **Documentacion (capa semantica, 26-10):** la seleccion de 63 archivos de la instantanea anterior se conserva. Documentos creados o modificados despues del 26-10 no tienen nodos semanticos; leerlos directamente.
- `GRAPH_HEALTH.md`, `graph-health.json` y `EXTRACTION_AUDIT.json` corresponden a la instantanea documental del 26-10.
- Las conexiones del grafo indican estructura (contiene, llama, importa, referencia), no prueban comportamiento. Verificar en las fuentes.

# Graph Report - APP-MOVIL-SAITxd  (2026-10-28)

## Corpus Check
- cluster-only mode — file stats not available

## Summary
- 8273 nodes · 22451 edges · 501 communities (176 shown, 325 thin omitted)
- Extraction: 97% EXTRACTED · 3% INFERRED · 0% AMBIGUOUS · INFERRED: 755 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `487f8058`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Componentes.kt
- Illuminate\Http\Request
- SyncState
- ProveedorRepositoryInterface
- DateTimeImmutable
- AdminTexto
- Proveedor
- InicioAcopiadorTest.kt
- AdminUi.kt
- TestCase
- Pantalla
- OperacionAdminUseCases.kt
- Auditoria
- Proveedor
- PersistenciaListaAcopioTest.kt
- Illuminate\Support\Facades\DB
- Usuario
- UsuarioRepository
- LoteProduccion
- Zona
- ObtenerSesionUseCase
- EloquentEntregaRepository.php
- ProductoRepositoryInterface
- docs/academico/archivos-documentacion.md
- docs/scrum/product-backlog.md
- ProveedorRepository
- docs/requisitos/rf-fuente-y-cobertura.md
- CalidadVisitasScreen.kt
- ServidorWebRepositoryKtor.kt
- .`calidad_faon con PIN 8642 inicia sesion y carga el panel de Tecnico de calidad`
- DomainException
- AnalisisCalidad
- JornadaRepository
- Sesion
- TrasladoZona
- Mappers.kt
- PagoProveedor
- web.php
- App.kt
- AdminSupervisionState
- RegistrarProveedorUseCase
- Koin.kt
- QrEscaneableTest.kt
- Illuminate\Pagination\LengthAwarePaginator
- ExportarDatosMovilQuery.php
- Zona
- CalidadVisitasViewModel.kt
- .crear
- VehiculoRepositoryInterface
- docs/README.md
- RegistroUnificadoProveedoresTest
- TokenMovil
- docs/tecnica/arquitectura-datos-y-sincronizacion.md
- JornadaRepositoryInterface
- SqlDelightUsuarioRepository
- FakeUsuarioRepository
- InterfazCoder1
- SesionServidor
- MarcaSinRecojo
- ProveedorShell
- SqlDelightCambiosLocalesRepository.kt
- ReabrirJornadaUseCaseTest.kt
- ObservarEntregasDeJornadaUseCase
- Entrega
- Illuminate\Database\Schema\Blueprint
- EntregaDetalleViewModel
- EloquentRecepcionAcopioRepository.php
- ServidorWebRepository
- Receta
- DatosPanelWebTest.kt
- InicioAcopiadorTest
- FakeJornadaRepository
- MapaRutaController.php
- ImportacionController.php
- Sancion
- FakeEntregaRepository
- RolesConfigurablesTest
- CicloJornadaAcopiadorTest
- Zona
- Theme.kt
- AcopiadorHomeViewModel
- Eloquent/Usuario.php
- SqlDelightEntregaRepository
- SincronizacionMovilTest
- bootstrap/app.php
- docs/scrum/tareas.md
- RegistroAcopioCompartido
- ExportadorQr.ios.kt
- VerificacionesTest
- VehiculoRepository
- SincronizacionPanelWebTest
- UsuarioFormViewModel
- EstadoControlCalidad
- .roundTrip
- ComponentesUiTest
- .crear
- CoordinadorSync
- PortalProveedorViewModelTest
- EloquentUsuarioRepository.php
- GuardarMapaRutaRequest.php
- EstadoEnlazadoUsuarioProveedorTest
- PerfilViewModel
- FilaAcopio
- EscanerLactomat.android.kt
- docs/pruebas/ejecuciones/resultados-2026-10-22.md
- ProveedorManagementTest
- verificaciones.yml
- CambiosMovilTest
- web/package.json
- CerrarSesionUseCase
- RefreshTokenMovilTest
- SinRecojo
- EstadoCalidad
- PlantaCatalogosYVentasTest
- ProtectorSecretosKeystore.kt
- Illuminate\Database\Migrations\Migration
- Illuminate\Support\Facades\Schema
- SinConexionRemotaException
- RegistroEntregaViewModel
- SqlDelightDatosServidorRepository
- SincronizacionViewModel
- SinRecojoYSincronizacionTest
- RecetasCompartidasInventarioCompradoresTest
- iniciarMapasRutas
- AdminDashboardPanelTest.php
- PlantaRecetasInventarioTest
- verificar_documentacion.py
- CatalogosDescargaCoder1Test.kt
- construirFilasAcopio
- .parsear
- LoteViewModel
- UsuariosViewModel
- ReglaEdicionEntregaTest
- ejecutar_verificaciones.py
- ejecutar_android_instrumentada.py
- SqlDelightProveedorRepository
- AltaProveedorTest
- AuditoriaSeguridadTest
- VerificacionInstrumentada.kt
- EcolectaApp.kt
- ExportadorQr.android.kt
- EventoRegistrosRemotos
- ListaProveedoresViewModel
- AdminProveedoresPanelTest
- AdminUsuariosModuleTest
- ConsultaDniProveedorTest
- KoinIOS.kt
- ListaAcopio.kt
- EstadosIntegradosTest
- RolesSinPerfilBaseTest
- MainActivity.kt
- SqlDelightJornadaRepository
- PortalProveedorState
- EntregaDetalleUiState
- Liquidacion
- AdminLiquidacionDetalleTest
- AdminProduccionModuleTest
- AltaUsuariosDinamicaTest
- ListadosUsuariosProveedoresTest
- MovilAuthenticationTest
- SuspensionConjuntaProveedorTest
- docs/COMO-TRABAJAMOS.md
- CredencialesProtegidasCoder1Test.kt
- roles.js
- EntregaInvalidaException
- JornadaEnCursoRepository
- JornadasUiState
- composer.json
- proxy_respuesta_perdida.py
- test_verificaciones.py
- firebase-tests/package.json
- verificar-portal-proveedor.mjs
- ContentView.swift
- VehiculoFormViewModel
- proveedores.js
- RutasProveedoresUsabilidadTest
- VistaProveedoresTarjetasTest.php
- RefreshSesionPanelWebTest
- EstadoRapidoTablasTest
- TrasladosViewModel
- AdminPerfilScreen.kt
- ReaperturaJornadaException
- ConflictosViewModel
- PreparadorEntregaServidorLocal
- ProveedoresViewModel
- TrasladosUiState
- AdminNavegacionYPermisosTest
- ProveedorInvalidoException
- PreciosProveedor
- CompatibilidadFormulariosCoder1Test
- ProveedoresFiltrosTest
- app.js
- MapaAcopio
- AdminRecepcionModuleTest
- describir
- ZonaRepository
- EloquentLiquidacionRepository.php
- gestion.js
- TarifasTemporadaTest
- docs/pruebas/ejecuciones/coder1-t12-manual.md
- EstadoServidor
- RevocacionSesionHttpTest
- SinRecojoException
- ChipSync
- FakeSinRecojoRepository
- ReanudarJornadaSiExisteUseCaseTest
- ControlCalidadRepository
- ZonaRepository
- ControlCalidadRepositoryInterface
- LiquidacionInvalidaException
- require-dev
- scripts
- DatosMovilTest
- iOSApp
- PortalMiCiclo.kt
- navegacion.test.mjs
- UsuarioFormUiState
- ControlCalidadInvalidoException
- 2026_09_24_055320_create_materiales_planta_tables.php
- 2026_09_24_120000_create_sincronizacion_movil_tables.php
- 2026_09_24_200426_create_analisis_calidad_table.php
- 2026_10_25_231000_archive_roles_retirados_usuarios.php
- 2026_10_28_023504_crear_recetas_compartidas.php
- proveedor-qr.test.mjs
- AppServiceProvider
- DeviceIdProviderAndroid
- ListarAuditoriaUseCase
- EstadoEscaneoQr
- PlataformaProveedor.kt
- ExportadorQr.kt
- PlataformaProveedor.ios.kt
- config
- 2026_09_20_165811_ampliar_auditorias_y_versionar_sesiones.php
- 2026_09_24_190253_add_uuid_movil_a_calidad_y_reclamos.php
- 2026_10_26_021440_complete_portal_proveedor_requests.php
- 2026_10_28_014353_separar_catalogo_y_precios_por_comprador.php
- AdminDashboardTest
- proveedores/index.blade.php
- DatabaseDriverFactory.android.kt
- ConexionPortal
- 0001_01_01_000000_create_users_table.php
- 0001_01_01_000002_create_jobs_table.php
- 2026_09_20_130000_create_lotes_produccion_table.php
- 2026_10_26_142531_create_tarifas_leche_table.php
- 2026_10_28_040918_create_perfiles_roles_table.php
- DatabaseDriverFactory.ios.kt
- etiquetaSyncAcopiador
- .aplicarRoles
- RegistroAcopioRemotoRepository
- require
- 0001_01_01_000001_create_cache_table.php
- 2026_09_11_121638_add_usuario_id_a_proveedores.php
- 2026_09_20_140000_add_bloqueo_manual_a_usuarios_table.php
- 2026_10_12_124943_agregar_resumen_recorrido_a_rutas.php
- 2026_10_25_232000_add_dueno_to_proveedores_table.php
- 2026_10_28_210102_add_suspension_origen_to_proveedores_table.php
- 2026_10_28_224001_make_base_nullable_in_perfiles_roles_table.php
- 2026_10_28_230433_add_suspension_origen_to_usuarios_table.php
- inventario/index.blade.php
- usuarios/index.blade.php
- CalidadBackHandler.android.kt
- HttpClientFactory.ios.kt
- HttpClientFactory.android.kt
- RandomBytes.android.kt
- Conflicto
- EntregasDelDiaUiState
- 2026_09_10_201132_create_zonas_table.php
- 2026_09_10_201133_create_admin_users_table.php
- 2026_09_10_201135_create_proveedores_table.php
- 2026_09_10_204415_create_vehiculos_table.php
- 2026_09_10_204416_create_jornadas_table.php
- 2026_09_11_113742_drop_admin_users_table.php
- 2026_09_11_184332_create_liquidaciones_table.php
- 2026_10_25_215612_create_solicitudes_traslado_table.php
- 2026_10_26_033649_create_sin_recojos_table.php
- recetas/form.blade.php
- formulario.blade.php
- usuarios/form.blade.php
- ventas/index.blade.php
- admin.blade.php
- ZonaOcupadaException
- SolicitadorPermisoAlmacenamiento.kt
- RelojLima
- CalidadBackHandler.ios.kt
- SolicitadorPermisoAlmacenamiento.ios.kt
- acopio/index.blade.php
- historial/index.blade.php
- produccion/index.blade.php
- proveedores/form.blade.php
- proveedores/show.blade.php
- formularios.blade.php
- roles/index.blade.php
- acopiadores/index.blade.php
- auditoria/index.blade.php
- auditoria/show.blade.php
- calidad/create.blade.php
- calidad/index.blade.php
- calidad/show.blade.php
- compradores/index.blade.php
- comunicados/index.blade.php
- configuracion/index.blade.php
- dashboard/index.blade.php
- design-system/index.blade.php
- entregas/index.blade.php
- importaciones/index.blade.php
- preview.blade.php
- jornadas/create.blade.php
- jornadas/index.blade.php
- jornadas/show.blade.php
- liquidaciones/create.blade.php
- liquidaciones/index.blade.php
- liquidaciones/show.blade.php
- tarifas.blade.php
- productos/form.blade.php
- productos/index.blade.php
- productos/show.blade.php
- recepcion/index.blade.php
- recetas/index.blade.php
- reportes/index.blade.php
- roles/form.blade.php
- seleccionar.blade.php
- sanciones/index.blade.php
- vehiculos/form.blade.php
- zonas/form.blade.php
- zonas-vehiculos/index.blade.php
- mapa.blade.php
- mapas.blade.php
- login.blade.php

## God Nodes (most connected - your core abstractions)
1. `Usuario` - 166 edges
2. `TestCase` - 122 edges
3. `SyncState` - 100 edges
4. `Reloj` - 96 edges
5. `Proveedor` - 94 edges
6. `EntregaRepositoryInterface` - 82 edges
7. `ProveedorRepositoryInterface` - 82 edges
8. `Pantalla` - 81 edges
9. `Controller` - 70 edges
10. `UsuarioRepositoryInterface` - 69 edges

## Surprising Connections (you probably didn't know these)
- `Inventario documental y preparación para GitHub` --references--> `Ecolactea Digital`  [EXTRACTED]
  docs/academico/archivos-documentacion.md → README.md
- `AppAndroidPreview()` --calls--> `App()`  [EXTRACTED]
  androidApp/src/main/kotlin/pe/ecolecta/MainActivity.kt → shared/src/commonMain/kotlin/pe/ecolecta/presentation/App.kt
- `iOSApp` --implements--> `App\\`  [EXTRACTED]
  iosApp/iosApp/iOSApp.swift → web/composer.json
- `Ecolactea Digital` --references--> `Validación manual T12 de Coder 1`  [EXTRACTED]
  README.md → docs/pruebas/ejecuciones/coder1-t12-manual.md
- `CoordinadorSyncTest` --calls--> `Usuario`  [INFERRED]
  shared/src/androidHostTest/kotlin/pe/ecolecta/data/CoordinadorSyncTest.kt → shared/src/commonMain/kotlin/pe/ecolecta/domain/model/Usuario.kt

## Import Cycles
- None detected.

## Communities (501 total, 325 thin omitted)

### Community 0 - "Componentes.kt"
Cohesion: 0.03
Nodes (99): CicloAcopio, etiqueta(), AcopiadorShell(), PantallaApilada(), PantallaConPestanas(), ChipSyncAcopiador(), EntregasDelDiaScreen(), FilaEntrega() (+91 more)

### Community 1 - "Illuminate\Http\Request"
Cohesion: 0.02
Nodes (46): AnularEntregaUseCase, CorregirEntregaUseCase, RegistrarEntregaUseCase, ActualizarZonaUseCase, CambiarEstadoZonaUseCase, CrearZonaUseCase, AcopiadorController, CalidadController (+38 more)

### Community 2 - "SyncState"
Cohesion: 0.02
Nodes (51): DatabaseSeeder, fechaAcopioDe(), hoyAcopio(), plusDays(), DeviceIdProvider, nuevoId(), AccionAuditoria, ACTUALIZAR (+43 more)

### Community 3 - "ProveedorRepositoryInterface"
Cohesion: 0.03
Nodes (54): ActualizarProveedorUseCase, {closure#1}(), AnalizarRegistroProveedorUseCase, CambiarEstadoProveedorUseCase, {closure#1}(), {closure#1}(), ConciliarCuentasProveedoresUseCase, ConsultarDniUseCase (+46 more)

### Community 4 - "DateTimeImmutable"
Cohesion: 0.03
Nodes (25): ObtenerResumenJornadaUseCase, CalcularImporteTemporadas, CancelarLoteProduccionUseCase, CrearLoteProduccionUseCase, FinalizarLoteProduccionUseCase, {closure#1}(), ListarAcopiosRecientesUseCase, {closure#1}() (+17 more)

### Community 5 - "AdminTexto"
Cohesion: 0.08
Nodes (70): ContenidoAdmin(), accion(), AccionesAlerta, AdminAlertasScreen(), color(), etiqueta(), icono(), recordarAccionesAlerta() (+62 more)

### Community 6 - "Proveedor"
Cohesion: 0.02
Nodes (26): {closure#1}(), {closure#3}(), {closure#4}(), ConsultarHojaRutaQuery, {closure#10}(), {closure#15}(), AuditarCambiosOperativos, Jornada (+18 more)

### Community 7 - "InicioAcopiadorTest.kt"
Cohesion: 0.05
Nodes (25): IntegracionPanelWebTest, InMemoryJornadaEnCursoRepository, JornadaDelDiaCerradaException, Jornada, Rol, ACOPIADOR, ADMIN, CALIDAD (+17 more)

### Community 8 - "AdminUi.kt"
Cohesion: 0.04
Nodes (19): AvatarCuenta(), color(), EtiquetaRol(), fondo(), icono(), LoginScreen(), etiquetaDeRol(), iconoDeRol() (+11 more)

### Community 9 - "TestCase"
Cohesion: 0.03
Nodes (16): Auditoria, AccesoAdminRecuperacionTest, AdminModulesTest, AdminPantallasTest, AdminPestanasModulosTest, AsignacionZonaCoder1Test, AuthenticationTest, CamposProveedorCoder1Test (+8 more)

### Community 10 - "Pantalla"
Cohesion: 0.02
Nodes (71): AcopiadorBottomNav(), PestanaAcopiador, ENTREGAS, JORNADA, PERFIL, PROVEEDORES, SINCRONIZACION, AdminBottomNav() (+63 more)

### Community 11 - "OperacionAdminUseCases.kt"
Cohesion: 0.04
Nodes (37): AlertaAdmin, Comunicado, EstadoLiquidacion, APROBADA, EN_CURSO, PAGADA, POR_APROBAR, EstadoSolicitud (+29 more)

### Community 12 - "Auditoria"
Cohesion: 0.04
Nodes (20): {closure#1}(), ResultadoAccesoUsuario, {closure#2}(), ValidaDatosUsuario, AccionAuditoria, AccesoFallido, Actualizar, Anular (+12 more)

### Community 13 - "Proveedor"
Cohesion: 0.04
Nodes (20): AnalisisRegistroProveedor, ResultadoEscaneoQr, SituacionRegistroProveedor, ConflictoDni, CuentaDeOtroRol, CuentaSinFicha, CuentaVinculadaAOtraFicha, FichaSinCuenta (+12 more)

### Community 14 - "PersistenciaListaAcopioTest.kt"
Cohesion: 0.05
Nodes (13): EstadosAccesoPanelWebTest, DeviceIdProvider, abrir(), `actualizar la version anterior de acopio agrega verificacion sin borrar cuentas`(), limpiar(), GrafoKoinTest, DeviceIdProvider, SchemaCuentasSeguras (+5 more)

### Community 15 - "Illuminate\Support\Facades\DB"
Cohesion: 0.03
Nodes (6): {closure#1}(), EscenarioPeruDemoSeeder, PlantaComercialCasosPruebaSeeder, ProduccionCasosPruebaSeeder, ProduccionCasosPruebaTest, UsabilidadWebCompletaTest

### Community 16 - "Usuario"
Cohesion: 0.07
Nodes (17): GenerarLiquidacionUseCase, AplicarCambioMovilUseCase, {closure#1}(), {closure#21}(), {closure#23}(), {closure#24}(), {closure#1}(), {closure#2}() (+9 more)

### Community 17 - "UsuarioRepository"
Cohesion: 0.04
Nodes (25): Usuario, Ninguno, PinesParaServidor, UsuarioRepository, bytesAleatorios(), ParHashPin, PinHasher, IniciarSesionUseCase (+17 more)

### Community 18 - "LoteProduccion"
Cohesion: 0.05
Nodes (17): GestionIngredientes, IniciarLoteProduccionUseCase, EstadoLoteProduccion, Borrador, Cancelado, EnProceso, Finalizado, LoteProduccionInvalidoException (+9 more)

### Community 19 - "Zona"
Cohesion: 0.04
Nodes (17): {closure#1}(), {closure#2}(), Vehiculo, Zona, VehiculoFactory, ZonaFactory, DatabaseSeeder, MovilDemoSeeder (+9 more)

### Community 20 - "ObtenerSesionUseCase"
Cohesion: 0.11
Nodes (8): ObtenerSesionUseCase, ObtenerJornadaEnCursoUseCase, ListarProveedoresUseCase, ListarUsuariosUseCase, ListarZonasUseCase, AcopiadorBadgeViewModel, EscanearQrViewModel, cargaSegura()

### Community 21 - "EloquentEntregaRepository.php"
Cohesion: 0.04
Nodes (14): {closure#1}(), ResultadoRegistroEntrega, Entrega, EntregaInvalidaException, {closure#10}(), {closure#1}(), {closure#14}(), {closure#2}() (+6 more)

### Community 22 - "ProductoRepositoryInterface"
Cohesion: 0.06
Nodes (15): ActualizarProductoUseCase, CambiarEstadoProductoUseCase, CrearProductoUseCase, ListarProductosUseCase, ProductoInvalidoException, Producto, ProductoRepositoryInterface, {closure#1}() (+7 more)

### Community 23 - "docs/academico/archivos-documentacion.md"
Cohesion: 0.08
Nodes (52): Inventario documental y preparación para GitHub, Exclusiones de publicacion, Inventario documental publicable, Verificacion documental reproducible, Caso Delta simulado, Cumplimiento Delta 84.04 por ciento, Caso Delta — indicadores y retrospectiva académica, Velocidad Delta 19.75 SP (+44 more)

### Community 24 - "docs/scrum/product-backlog.md"
Cohesion: 0.11
Nodes (62): RF-PRO-04 — Asignación de cada proveedor a una ruta/zona y reasignación cuando se traslada., RF-PRO-05 — Historial del proveedor: entregas, litros, calidad, sanciones, pagos y reclamos., Requisitos no funcionales — controles trazables, RNF-01 — Offline-first: toda la funcionalidad de acopio y calidad debe funcionar sin internet en las zonas de acopio; la app se diseña "sin conexión primero"., RNF-02 — Sincronización confiable: cola de sincronización con reintentos, sin pérdida de datos; los registros nunca se eliminan por fallo de red., RNF-03 — Rendimiento: permitir registrar a un proveedor en pocos segundos (hay 5-6 proveedores esperando juntos); bajo consumo de batería y datos., RNF-04 — Disponibilidad: operar en el horario de acopio (≈4:30-5:30 a.m.) sin caídas; soportar 390 proveedores, 4 acopiadores concurrentes y 11,500 L/día (tope 18,500)., RNF-05 — Compatibilidad: funcionar en celulares Android de gama baja/media y con señal intermitente. (+54 more)

### Community 25 - "ProveedorRepository"
Cohesion: 0.05
Nodes (9): ProveedorSesionException, SinProveedorAsociado, SinRolProveedor, ProveedorRepository, ObtenerPerfilProveedorUseCase, ObtenerProveedorAsociadoUseCase, FakeProveedorRepository, CrearProveedorUseCaseTest (+1 more)

### Community 26 - "docs/requisitos/rf-fuente-y-cobertura.md"
Cohesion: 0.06
Nodes (62): Decision 25-10: Acopiador, Calidad, Administrador y Proveedor, Requisitos funcionales: fuente y cobertura, RF-ACO-01 — Registrar cada entrega: proveedor, fecha/hora, cantidad de litros y observación., RF-ACO-02 — Asociar automáticamente acopiador, unidad/vehículo y ruta según la sesión del acopiador., RF-ACO-03 — Registrar litros por recipiente (tacho) y validar coherencia (ej. aviso si un tacho supera su capacidad configurada, tipo 34 L)., RF-ACO-04 — Permitir registrar a los 5-6 proveedores que entregan juntos en un mismo punto (modo rápido/lote)., RF-ACO-05 — Registrar sin internet y dejar cada registro en estado Pendiente., RF-ACO-06 — Consulta del total de litros por día, proveedor, ruta y acopiador. (+54 more)

### Community 27 - "CalidadVisitasScreen.kt"
Cohesion: 0.08
Nodes (45): BotonPrimarioCalidad(), CalidadBackHandler(), CalidadBottomNav(), CalidadShell(), ChipSyncCalidad(), color(), controlesDeHoy(), Dato() (+37 more)

### Community 28 - "ServidorWebRepositoryKtor.kt"
Cohesion: 0.07
Nodes (11): LiquidacionServidor, RespuestaCambio, RespuestaError, RespuestaLiquidaciones, RespuestaSesion, ServidorWebNoConfigurado, ServidorWebRepositoryKtor, SolicitudRefresh (+3 more)

### Community 29 - ".`calidad_faon con PIN 8642 inicia sesion y carga el panel de Tecnico de calidad`"
Cohesion: 0.04
Nodes (14): SeleccionarRolUseCase, VincularServidorUseCase, ObtenerUsuarioUseCase, Ingresar, LoginUiEvent, LoginUiState, PinCambia, UsernameCambia (+6 more)

### Community 30 - "DomainException"
Cohesion: 0.05
Nodes (12): AutenticarOperadorUseCase, {closure#1}(), {closure#2}(), CerrarSesionOperadorUseCase, CuentaBloqueadaException, CuentaInactivaException, OperadorAuthenticatorInterface, OperadorSesion (+4 more)

### Community 31 - "AnalisisCalidad"
Cohesion: 0.06
Nodes (11): {closure#1}(), {closure#4}(), GuardarAnalisisCalidadUseCase, EstadoAnalisis, Aprobado, Observado, Rechazado, Repetir (+3 more)

### Community 32 - "JornadaRepository"
Cohesion: 0.05
Nodes (10): RegistroAcopioRemotoRepositoryApi, JornadaRepository, CuentasRepository, ZonaRepository, ListarJornadasUseCase, ActualizarZonaUseCase, CrearZonaUseCase, ConProveedoresActivos (+2 more)

### Community 33 - "Sesion"
Cohesion: 0.06
Nodes (8): CoordinadorSyncTest, SesionRepository, Panel, SesionPersistidaTest, Sesion, SesionRepository, claveSesionDe(), ClaveSesionTest

### Community 34 - "TrasladoZona"
Cohesion: 0.06
Nodes (14): SqlDelightTrasladoRepository, EstadoTraslado, AUTORIZADO, PENDIENTE, RECHAZADO, TrasladoZona, TrasladoRepository, MotivoObligatorio (+6 more)

### Community 35 - "Mappers.kt"
Cohesion: 0.09
Nodes (7): EntidadCambio, marcarCambio(), tieneCambioPendiente(), aDominio(), enumDeColumna(), SqlDelightAuditoriaRepository, SqlDelightVehiculoRepository

### Community 36 - "PagoProveedor"
Cohesion: 0.05
Nodes (9): PortalProveedorRepository, SqlDelightAlertaDescartadaRepository, SqlDelightComunicadoRepository, SqlDelightCuentasRepository, SqlDelightGestionPortalRepository, PagoProveedor, SolicitudProveedor, validarReclamo() (+1 more)

### Community 38 - "App.kt"
Cohesion: 0.06
Nodes (16): AdminShell(), pestanaDe(), App(), pantallaInicialAcopiador(), rememberSesionViewModelStoreOwner(), ViewModelStoreOwner, ConAlcancePorPantalla(), ViewModelStoreOwner (+8 more)

### Community 39 - "AdminSupervisionState"
Cohesion: 0.06
Nodes (8): aDominio(), SqlDelightControlCalidadRepository, DatosVisitaCalidad, AdminSupervisionState, AdminSupervisionViewModel, filtrarControlesAdmin(), AdminSupervisionTest, ControlCalidadRepository

### Community 40 - "RegistrarProveedorUseCase"
Cohesion: 0.09
Nodes (10): ActorRegistroProveedor, CredencialesCuentaMovil, DatosProveedor, {closure#1}(), {closure#2}(), RegistrarProveedorUseCase, ResultadoRegistroProveedor, RegistroProveedorException (+2 more)

### Community 41 - "Koin.kt"
Cohesion: 0.05
Nodes (8): ObservarEntregasUseCase, ObservarJornadasUseCase, ListarVehiculosUseCase, SeleccionZonaVehiculoUiState, SeleccionZonaVehiculoViewModel, AuditoriaViewModel, EntregasViewModel, JornadasViewModel

### Community 42 - "QrEscaneableTest.kt"
Cohesion: 0.08
Nodes (13): QrEscaneableTest, generarQrProveedor(), leerQrProveedor(), PorCodigo, PorIdAntiguo, ReferenciaQrProveedor, Encontrado, EscanearQrProveedorUseCase (+5 more)

### Community 43 - "Illuminate\Pagination\LengthAwarePaginator"
Cohesion: 0.06
Nodes (12): ListarAuditoriaUseCase, ListarLiquidacionesUseCase, MarcarLiquidacionPagadaUseCase, LiquidacionRepositoryInterface, AuditoriaController, LiquidacionController, buscarPorId(), {closure#7}() (+4 more)

### Community 44 - "ExportarDatosMovilQuery.php"
Cohesion: 0.07
Nodes (10): {closure#11}(), {closure#12}(), {closure#13}(), {closure#14}(), {closure#16}(), {closure#18}(), {closure#8}(), RevisionCatalogo (+2 more)

### Community 45 - "Zona"
Cohesion: 0.10
Nodes (6): CambiosPanelWebTest, PanelFalso, Panel, SqlDelightZonaRepository, Zona, EnviarCambiosServidorUseCase

### Community 46 - "CalidadVisitasViewModel.kt"
Cohesion: 0.06
Nodes (17): ControlCalidadRepository, ObtenerResumenAdminUseCase, ResumenAdmin, AdminDashboardUiState, AdminDashboardViewModel, EscaneoPendiente, fechaHoraEpoch(), horaDe() (+9 more)

### Community 47 - ".crear"
Cohesion: 0.09
Nodes (3): CalidadVisitasTest, ZonaRepository, MemoriaCalidad

### Community 48 - "VehiculoRepositoryInterface"
Cohesion: 0.09
Nodes (9): ActualizarVehiculoUseCase, CambiarEstadoVehiculoUseCase, CrearVehiculoUseCase, VehiculoInvalidoException, Vehiculo, VehiculoRepositoryInterface, {closure#1}(), {closure#2}() (+1 more)

### Community 49 - "docs/README.md"
Cohesion: 0.15
Nodes (29): CP, EXE, BUG y EV son registros distintos, Cómo documentar pruebas web y móviles, Documentación de Ecolactea Digital, Fuentes canonicas y Project operativo, Hitos docentes 23/26 de noviembre pendientes de confirmar, Capacidad real pendiente de confirmacion, Calendario y capacidad — fuente principal, Definition of Done — estándar del incremento (+21 more)

### Community 50 - "RegistroUnificadoProveedoresTest"
Cohesion: 0.08
Nodes (4): ProveedorQr, RegistroUnificadoProveedoresTest, ExampleTest, ProveedorQrTest

### Community 51 - "TokenMovil"
Cohesion: 0.07
Nodes (7): ExportarDatosMovilQuery, MovilController, AutenticarTokenMovil, {closure#1}(), {closure#2}(), TokenMovil, RolesCompatiblesCoder1Test

### Community 52 - "docs/tecnica/arquitectura-datos-y-sincronizacion.md"
Cohesion: 0.08
Nodes (31): Acceso a perfiles de prueba, Datos ficticios en entorno aislado, Acceso y datos del entorno de pruebas, Revision de secretos de demostracion pendiente, Usuarios, zonas y vehículos, Administrador móvil: nota histórica, Ficha productiva y cuenta vinculada, Supervisión y administración móvil (+23 more)

### Community 53 - "JornadaRepositoryInterface"
Cohesion: 0.08
Nodes (9): AbrirJornadaUseCase, CerrarJornadaUseCase, ListarJornadasUseCase, ObtenerJornadaEnCursoUseCase, Jornada, JornadaRepositoryInterface, {closure#1}(), {closure#2}() (+1 more)

### Community 54 - "SqlDelightUsuarioRepository"
Cohesion: 0.08
Nodes (3): MigracionRolesCoder1Test, SqlDelightSesionRepository, SqlDelightUsuarioRepository

### Community 55 - "FakeUsuarioRepository"
Cohesion: 0.09
Nodes (3): FakeUsuarioRepository, CrearUsuarioUseCaseTest, LoginOfflineUseCaseTest

### Community 56 - "InterfazCoder1"
Cohesion: 0.15
Nodes (4): InterfazCoder1, CambiosLocalesRepository, CambiosLocalesRepository, VerificacionCoder1Instrumentada

### Community 57 - "SesionServidor"
Cohesion: 0.09
Nodes (5): AccesoRemotoCoder1Test, ServidorWebRepository, ServidorWebRepository, ServidorWebRepository, SesionServidor

### Community 58 - "MarcaSinRecojo"
Cohesion: 0.10
Nodes (9): aDominio(), SqlDelightSinRecojoRepository, MarcaSinRecojo, MotivoSinRecojo, NO_SE_PUDO_LLEGAR, OTRO, PROVEEDOR_NO_DISPONIBLE, SIN_LECHE (+1 more)

### Community 59 - "ProveedorShell"
Cohesion: 0.17
Nodes (27): CalidadProveedor(), etiquetaCalidad(), etiquetaPago(), PagosProveedor(), BotonProveedor(), CabeceraProveedor(), CargandoProveedor(), decimalProveedor() (+19 more)

### Community 60 - "SqlDelightCambiosLocalesRepository.kt"
Cohesion: 0.09
Nodes (6): SqlDelightCambiosLocalesRepository, CambioParaServidor, CambiosLocalesRepository, ConfirmacionCatalogo, EstadoCambiosServidor, avisoCambiosServidor()

### Community 61 - "ReabrirJornadaUseCaseTest.kt"
Cohesion: 0.11
Nodes (6): ConfiguracionJornada, LoginOfflineUseCase, CredencialAdmin, ReabrirJornadaUseCase, ReabrirJornadaUseCaseTest, AuditoriaRepository

### Community 62 - "ObservarEntregasDeJornadaUseCase"
Cohesion: 0.06
Nodes (11): ObservarEntregasDeJornadaUseCase, ObtenerJornadaUseCase, ObtenerZonaUseCase, EntregasDelDiaViewModel, JornadaDetalleUiState, JornadaDetalleViewModel, ZonaFila, ZonaFormUiState (+3 more)

### Community 63 - "Entrega"
Cohesion: 0.08
Nodes (8): CalificarEntregasConAnalisis, {closure#1}(), {closure#2}(), {closure#1}(), ControlCalidad, {closure#1}(), Entrega, EntregaFactory

### Community 64 - "Illuminate\Database\Schema\Blueprint"
Cohesion: 0.09
Nodes (24): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}() (+16 more)

### Community 65 - "EntregaDetalleViewModel"
Cohesion: 0.07
Nodes (5): ObtenerProveedorUseCase, Carga, EntregaDetalleViewModel, ProveedorFormUiState, ProveedorFormViewModel

### Community 66 - "EloquentRecepcionAcopioRepository.php"
Cohesion: 0.08
Nodes (6): RecepcionInvalidaException, RecepcionAcopio, {closure#1}(), {closure#3}(), {closure#9}(), EloquentRecepcionAcopioRepository

### Community 67 - "ServidorWebRepository"
Cohesion: 0.08
Nodes (5): InicializadorUnico, EntregaParaServidor, ServidorWebRepository, SincronizarDatosServidorUseCase, InicializadorUnicoTest

### Community 68 - "Receta"
Cohesion: 0.11
Nodes (10): {closure#1}(), {closure#2}(), {closure#3}(), GestionRecetas, {closure#2}(), RecetaController, Material, Receta (+2 more)

### Community 69 - "DatosPanelWebTest.kt"
Cohesion: 0.16
Nodes (21): datos(), `lo que se cambia en la web aparece en el celular sin duplicar`(), `precios recibidos sobreviven a recrear el repositorio y vacio explicito retira tarifas`(), `un solo login conecta la cuenta conocida y conserva el acceso offline sin guardar el PIN`(), DeviceIdProvider, `una cuenta creada en la web entra con su PIN y queda guardada para entrar sin conexion`(), DeviceIdProvider, `una entrega que el celular aun no envio no se pisa con la copia del panel`() (+13 more)

### Community 71 - "FakeJornadaRepository"
Cohesion: 0.10
Nodes (3): FakeJornadaRepository, DesactivacionZonaVehiculoTest, ReglasCuentaTest

### Community 72 - "MapaRutaController.php"
Cohesion: 0.11
Nodes (11): {closure#1}(), SincronizarEntregaMovilUseCase, EntregaMovilRechazadaException, {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#6}() (+3 more)

### Community 73 - "ImportacionController.php"
Cohesion: 0.09
Nodes (5): {closure#1}(), ImportacionController, Importacion, PrecioLitro, SemanaOperativa

### Community 74 - "Sancion"
Cohesion: 0.10
Nodes (3): Sancion, AdminFigmaModulesTest, PortalProveedorCoder4Test

### Community 75 - "FakeEntregaRepository"
Cohesion: 0.09
Nodes (4): ItemLote, RegistrarLoteUseCase, FakeEntregaRepository, RegistrarLoteUseCaseTest

### Community 77 - "CicloJornadaAcopiadorTest"
Cohesion: 0.09
Nodes (7): ModalidadEntrega, ENTREGA_DIRECTA, MEDIANTE_ACOPIADOR, TRASLADO, RegistroEntregaUiState, ResolverConflictoUseCaseTest, CicloJornadaAcopiadorTest

### Community 78 - "Zona"
Cohesion: 0.10
Nodes (3): ZonaInvalidaException, Zona, EloquentZonaRepository

### Community 79 - "Theme.kt"
Cohesion: 0.10
Nodes (10): AdminCalidadScreen(), AdminProveedorDetalleScreen(), DetalleCalidadAdmin(), EstadoAnalisis(), PaletaEcolecta, EcolectaTheme(), esquemaClaro(), esquemaOscuro() (+2 more)

### Community 80 - "AcopiadorHomeViewModel"
Cohesion: 0.07
Nodes (8): InicioAcopiador, JornadaAbierta, JornadaTerminadaHoy, ObtenerJornadaTerminadaHoyUseCase, ResolverInicioAcopiadorUseCase, SeleccionarZonaVehiculo, SinZonaActiva, AcopiadorHomeViewModel

### Community 81 - "Eloquent/Usuario.php"
Cohesion: 0.08
Nodes (4): {closure#2}(), User, UserFactory, UsuarioFactory

### Community 84 - "bootstrap/app.php"
Cohesion: 0.11
Nodes (10): EnsureCuentaActiva, EnsurePermiso, EnsureRol, TransaccionAdministrativa, {closure#1}(), {closure#3}(), {closure#4}(), {closure#5}() (+2 more)

### Community 85 - "docs/scrum/tareas.md"
Cohesion: 0.12
Nodes (17): HU-05-A: Análisis de calidad trazable, Parámetros y origen de muestra por ratificar, S07: plan candidato, no adoptado, HU-07-C: Liquidación base semanal, S08: plan candidato, no adoptado, Precio, vigencia y semana jueves–miércoles, HU-16-A: Lote de producción mínimo, Origen de litros y unidades acordados (+9 more)

### Community 86 - "RegistroAcopioCompartido"
Cohesion: 0.10
Nodes (11): aDominio(), SqlDelightRegistroRecibidoRepository, aCompartido(), aEntrega(), combinarRegistrosProveedor(), RegistroAcopioCompartido, RegistrosProveedor, TipoRegistroCompartido (+3 more)

### Community 87 - "ExportadorQr.ios.kt"
Cohesion: 0.09
Nodes (3): bytesAleatorios(), aUIImage(), ExportadorQr

### Community 89 - "VehiculoRepository"
Cohesion: 0.10
Nodes (8): VehiculoRepository, ActualizarVehiculoUseCase, CrearVehiculoUseCase, ObtenerVehiculoUseCase, NombreVacio, PlacaDuplicada, PlacaVacia, VehiculoInvalidoException

### Community 90 - "SincronizacionPanelWebTest"
Cohesion: 0.22
Nodes (5): ResultadoSincronizacion, SincronizarRegistrosAcopioUseCase, mensajeSincronizacion(), SincronizacionPanelWebTest, FakeRegistroAcopioRemoto

### Community 92 - "EstadoControlCalidad"
Cohesion: 0.09
Nodes (14): ParametroCalidad, permiteNegativo(), valores(), ControlCalidad, EstadoControlCalidad, APROBADO, OBSERVADO, RECHAZADO (+6 more)

### Community 93 - ".roundTrip"
Cohesion: 0.12
Nodes (6): DeviceIdProvider, CambiarEstadoCuentaUseCase, DatosCuenta, FichaNueva, GuardarCuentaUseCase, ReglasCuenta

### Community 96 - "CoordinadorSync"
Cohesion: 0.11
Nodes (8): deHex(), HmacSha256, Pbkdf2, rotr(), Sha256, CoordinadorSync, EstadoSincronizacion, ResultadoCambios

### Community 98 - "EloquentUsuarioRepository.php"
Cohesion: 0.09
Nodes (5): {closure#1}(), {closure#10}(), {closure#2}(), {closure#7}(), EloquentUsuarioRepository

### Community 99 - "GuardarMapaRutaRequest.php"
Cohesion: 0.10
Nodes (8): {closure#1}(), ListarDirectorioUsuarios, {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), GuardarMapaRutaRequest, LoginRequest

### Community 101 - "PerfilViewModel"
Cohesion: 0.09
Nodes (3): ObtenerIdentidadRemotaUseCase, PerfilUiState, PerfilViewModel

### Community 102 - "FilaAcopio"
Cohesion: 0.12
Nodes (19): AvanceDelDia, EstadoRecojo, PENDIENTE, REGISTRADO, SIN_RECOJO, FilaAcopio, estadoDeHoy(), filtrarFilas() (+11 more)

### Community 104 - "docs/pruebas/ejecuciones/resultados-2026-10-22.md"
Cohesion: 0.15
Nodes (18): CP-001 002 003 007 Passed local, CP-004 005 006 Blocked integral, CP-007 idempotencia con respuesta perdida, Casos Ecolactea — especificación y estado al 22-10, Android instrumentado 5 de 5, Cobertura tecnica y DoD separadas, Cobertura comprobada y cierre técnico del 22-10, hora Lima, RNF pendientes de campo y reglas (+10 more)

### Community 106 - "verificaciones.yml"
Cohesion: 0.09
Nodes (22): concurrency, cancel-in-progress, group, runs-on, steps, timeout-minutes, jobs, documentacion (+14 more)

### Community 108 - "web/package.json"
Cohesion: 0.12
Nodes (21): concurrently, @laravel/multiplex, laravel-vite-plugin, tailwindcss, @tailwindcss/vite, vite, devDependencies, concurrently@^10.0.3 (+13 more)

### Community 109 - "CerrarSesionUseCase"
Cohesion: 0.13
Nodes (6): InMemorySesionRepository, CerrarSesionUseCase, CerrarSesionUseCaseTest, ServidorWebRepository, ServidorWebRepository, ServidorWebRepository

### Community 110 - "RefreshTokenMovilTest"
Cohesion: 0.15
Nodes (3): DatosSegurosAuditoria, {closure#1}(), RefreshTokenMovilTest

### Community 111 - "SinRecojo"
Cohesion: 0.14
Nodes (4): {closure#17}(), SinRecojo, AdminAcopioCoherenteTest, SinRecojoMovilTest

### Community 112 - "EstadoCalidad"
Cohesion: 0.16
Nodes (7): ControlCalidad, EstadoCalidad, Aprobado, Observado, Rechazado, {closure#2}(), EloquentControlCalidadRepository

### Community 114 - "ProtectorSecretosKeystore.kt"
Cohesion: 0.13
Nodes (4): ProtectorSecretosPrueba, ProtectorSecretosKeystore, NoDisponible, SecretoLocalNoDisponibleException

### Community 115 - "Illuminate\Database\Migrations\Migration"
Cohesion: 0.09
Nodes (6): {closure#1}(), {closure#1}(), {closure#1}(), {closure#1}(), {closure#1}(), {closure#2}()

### Community 116 - "Illuminate\Support\Facades\Schema"
Cohesion: 0.09
Nodes (6): {closure#1}(), {closure#1}(), {closure#1}(), {closure#1}(), {closure#1}(), {closure#2}()

### Community 117 - "SinConexionRemotaException"
Cohesion: 0.13
Nodes (4): Canal, SinConexionRemotaException, InicioSesion, FakeServidorWeb

### Community 120 - "SincronizacionViewModel"
Cohesion: 0.16
Nodes (6): ObtenerColaSyncUseCase, ResumenColaSync, EntregaPendiente, SincronizacionUiState, SincronizacionViewModel, SincronizacionUiStateTest

### Community 123 - "iniciarMapasRutas"
Cohesion: 0.22
Nodes (20): coordenadaValida(), firmaParadas(), iniciarMapasRutas(), closeEditor(), draftChanged(), fillProviders(), invalidatePath(), manualSummary() (+12 more)

### Community 126 - "verificar_documentacion.py"
Cohesion: 0.25
Nodes (12): anchors(), artifact_matches(), export_kanban(), historical_source_errors(), http_url_error(), main(), parse_catalog(), publication_files() (+4 more)

### Community 127 - "CatalogosDescargaCoder1Test.kt"
Cohesion: 0.22
Nodes (14): asignacionPendienteSeConservaYSeIncluyeEnElEnvio(), asignacionRemotaExplicitaSeGuardaYUnServidorAnteriorNoLaBorra(), cerrar(), datos(), descargarNoAdelantaLaRevisionBaseDeUnaEdicionPendiente(), desvincularEnServidorLimpiaSoloLaFichaConfirmadaYRepetirNoDuplica(), editarDocumentoLegadoSinCambiarloConservaCuentaYCola(), DeviceIdProvider (+6 more)

### Community 128 - "construirFilasAcopio"
Cohesion: 0.22
Nodes (6): cicloAcopioDe(), construirFilasAcopio(), CicloYListaAcopioTest, DeviceIdProvider, entregaDePrueba(), proveedorDePrueba()

### Community 129 - ".parsear"
Cohesion: 0.23
Nodes (3): normalizarValorCalidad(), ParserComprobanteLactomat, ControlCalidadTest

### Community 130 - "LoteViewModel"
Cohesion: 0.14
Nodes (3): FilaLote, LoteUiState, LoteViewModel

### Community 131 - "UsuariosViewModel"
Cohesion: 0.14
Nodes (8): CuentaFila, FiltroEstadoCuenta, ACTIVAS, ATENCION, INACTIVAS, TODAS, UsuariosUiState, UsuariosViewModel

### Community 133 - "ejecutar_verificaciones.py"
Cohesion: 0.18
Nodes (6): copy_project(), isolated_copy(), junit_counts(), make_command(), node_counts(), prepare()

### Community 134 - "ejecutar_android_instrumentada.py"
Cohesion: 0.25
Nodes (14): main(), adb(), command(), instrument(), digest(), execute(), main(), now() (+6 more)

### Community 138 - "VerificacionInstrumentada.kt"
Cohesion: 0.17
Nodes (3): VerificacionInstrumentada, LineaTextoComprobante, ordenarTextoComprobante()

### Community 139 - "EcolectaApp.kt"
Cohesion: 0.14
Nodes (7): EcolectaApp, EstadoInicio, ERROR, LISTO, PREPARANDO, iniciarKoin(), moduloServidorWeb()

### Community 141 - "EventoRegistrosRemotos"
Cohesion: 0.12
Nodes (6): EventoRegistrosRemotos, NoDisponible, Recibidos, RegistroAcopioRemotoRepository, SinConexion, FakeRegistroRecibidoRepository

### Community 146 - "KoinIOS.kt"
Cohesion: 0.15
Nodes (5): IdentidadRemotaProviderPendiente, DatabaseDriverFactory, IdentidadRemotaProvider, iniciarKoinIOS(), DeviceIdProviderIOS

### Community 147 - "ListaAcopio.kt"
Cohesion: 0.24
Nodes (13): aRecojoDelDia(), aSinRecojoDelDia(), construirDiasDelCiclo(), DiaAcopio, EstadoSincronizacion, EN_ESTE_CELULAR, ERROR, PENDIENTE (+5 more)

### Community 152 - "PortalProveedorState"
Cohesion: 0.27
Nodes (4): pagosVisibles(), ultimaLiquidacionEmitida(), PortalProveedorState, PortalProveedorTest

### Community 153 - "EntregaDetalleUiState"
Cohesion: 0.19
Nodes (5): DialogoEntrega, ANULAR, CORREGIR, EntregaDetalleUiState, EntregaDetalleEnvioTest

### Community 154 - "Liquidacion"
Cohesion: 0.20
Nodes (6): {closure#1}(), {closure#1}(), EstadoLiquidacion, Pagada, Pendiente, Liquidacion

### Community 161 - "docs/COMO-TRABAJAMOS.md"
Cohesion: 0.26
Nodes (12): Cómo trabajamos los cuatro integrantes, Flujo de trabajo propuesto, GitHub Projects fuente operativa, HU-04-A confirmacion sin duplicados, Mantener la documentación al cambiar el software, Fuente unica y vistas derivadas, Mantenimiento documental en 13 pasos, Verificacion antes de revision (+4 more)

### Community 162 - "CredencialesProtegidasCoder1Test.kt"
Cohesion: 0.20
Nodes (6): cerrar(), conversionAnteriorEsIdempotenteYConservaErroresYVigencias(), cuenta(), pinSeCifraEnReposoSeAbreSoloParaElEnvioYSeEliminaAlConfirmar(), ProteccionCredencialesLocales, ProtectorSecretos

### Community 163 - "roles.js"
Cohesion: 0.23
Nodes (10): accionesAdmitidas, accionesQueRequierenVer, cambiarColumnaPermisos(), estadoColumnaPermisos(), etiquetasAcciones, iniciarRoles(), mensajeFiltroVacio(), obtenerColumna() (+2 more)

### Community 164 - "EntregaInvalidaException"
Cohesion: 0.13
Nodes (12): ConflictoPendiente, EntregaInvalidaException, LitrosNoPositivos, LoteVacio, MotivoObligatorio, NoEsConflicto, ProveedorNoActivo, SemanaLiquidada (+4 more)

### Community 166 - "JornadasUiState"
Cohesion: 0.16
Nodes (6): EstadoFilaJornada, ABIERTA, CERRADA, NO_INICIADA, FilaJornada, JornadasUiState

### Community 167 - "composer.json"
Cohesion: 0.13
Nodes (14): autoload-dev, psr-4, description, extra, laravel, keywords, dont-discover, license (+6 more)

### Community 168 - "proxy_respuesta_perdida.py"
Cohesion: 0.16
Nodes (3): main(), event(), handle_request()

### Community 169 - "test_verificaciones.py"
Cohesion: 0.15
Nodes (3): main(), font(), text()

### Community 170 - "firebase-tests/package.json"
Cohesion: 0.18
Nodes (13): description, devDependencies, firebase@^12.19.0, @firebase/rules-unit-testing@^5.0.2, firebase-tools@^15.29.0, name, private, scripts (+5 more)

### Community 171 - "verificar-portal-proveedor.mjs"
Cohesion: 0.15
Nodes (6): entrega(), sinRecojo(), base, db, file, folder

### Community 172 - "ContentView.swift"
Cohesion: 0.18
Nodes (7): ComposeView, ContentView, .body, .body, Shared, SwiftUI, UIKit

### Community 173 - "VehiculoFormViewModel"
Cohesion: 0.15
Nodes (5): VehiculoFila, VehiculoFormUiState, VehiculoFormViewModel, VehiculosUiState, VehiculosViewModel

### Community 174 - "proveedores.js"
Cohesion: 0.27
Nodes (9): confirmacionEstadoProveedor(), iniciarEstadoProveedor(), iniciarConsultaDni(), iniciarEstadoRapido(), iniciarProveedores(), iniciarRegistroProveedor(), recuperarDatosRegistrados(), iniciarUsuario() (+1 more)

### Community 180 - "AdminPerfilScreen.kt"
Cohesion: 0.30
Nodes (5): AdminPerfilScreen(), AdminPerfilUiState, AdminPerfilViewModel, ListaAccesos(), ListaDatos()

### Community 181 - "ReaperturaJornadaException"
Cohesion: 0.17
Nodes (10): AutorizacionInvalida, FueraDePlazo, MotivoObligatorio, NoEncontrada, NoEsDeHoy, NoPertenece, OtraJornadaAbierta, ReaperturaJornadaException (+2 more)

### Community 184 - "ProveedoresViewModel"
Cohesion: 0.18
Nodes (3): ProveedoresUiState, ProveedoresViewModel, ProveedorFila

### Community 185 - "TrasladosUiState"
Cohesion: 0.18
Nodes (5): Autorizar, Crear, DialogoTraslado, Rechazar, TrasladosUiState

### Community 187 - "ProveedorInvalidoException"
Cohesion: 0.18
Nodes (8): CapacidadInvalida, CodigoDuplicado, CodigoVacio, DniDuplicado, DniVacio, NombresVacios, ProveedorInvalidoException, TachosInvalidos

### Community 188 - "PreciosProveedor"
Cohesion: 0.31
Nodes (3): PreciosProveedor, TarifaLeche, TarifaLecheTest

### Community 191 - "app.js"
Cohesion: 0.33
Nodes (7): iniciarPagina(), iniciarDirectorioUsuarios(), iniciarEditorFormulario(), protegerCambiosFormulario(), bindThemeToggle(), choices, defaults

### Community 194 - "describir"
Cohesion: 0.22
Nodes (3): AuditoriaUiState, RegistroAuditoria, describir()

### Community 196 - "EloquentLiquidacionRepository.php"
Cohesion: 0.29
Nodes (3): {closure#1}(), {closure#2}(), EloquentLiquidacionRepository

### Community 197 - "gestion.js"
Cohesion: 0.40
Nodes (6): crearExcel(), exportarRegistros(), xml(), iniciarGestion(), iniciarMenusAcciones(), posicionMenuAcciones()

### Community 199 - "docs/pruebas/ejecuciones/coder1-t12-manual.md"
Cohesion: 0.39
Nodes (7): Validación manual T12 de Coder 1, T04 limite offline pendiente, T12 Coder1 20 Passed declarados, T12 pruebas coordinadas y dispositivos declarados, Arquitectura movil KMP y backend Laravel, Cuatro perfiles oficiales, Ecolactea Digital

### Community 200 - "EstadoServidor"
Cohesion: 0.25
Nodes (5): AL_DIA, EstadoServidor, PorEnviar, SIN_SINCRONIZACION, EstadoServidorPerfilTest

### Community 202 - "SinRecojoException"
Cohesion: 0.22
Nodes (8): JornadaCerrada, JornadaNoEncontrada, JornadaNoPertenece, MarcaNoEncontrada, ProveedorFueraDeZona, SinRecojoException, YaMarcado, YaTieneEntrega

### Community 203 - "ChipSync"
Cohesion: 0.42
Nodes (4): ChipSync(), colorSync(), etiquetaCortaSync(), etiquetaSync()

### Community 210 - "require-dev"
Cohesion: 0.22
Nodes (9): require-dev, fakerphp/faker, laravel/boost, laravel/pail, laravel/pao, laravel/pint, mockery/mockery, nunomaduro/collision (+1 more)

### Community 211 - "scripts"
Cohesion: 0.22
Nodes (9): scripts, dev, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, pre-package-uninstall, setup (+1 more)

### Community 213 - "iOSApp"
Cohesion: 0.25
Nodes (6): iOSApp, autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 214 - "PortalMiCiclo.kt"
Cohesion: 0.50
Nodes (4): colorDia(), MiCicloProveedor(), TarjetaHoyProveedor(), textoDiaProveedor()

### Community 216 - "UsuarioFormUiState"
Cohesion: 0.25
Nodes (4): ModoProveedor, EXISTENTE, NUEVO, UsuarioFormUiState

### Community 218 - "2026_09_24_055320_create_materiales_planta_tables.php"
Cohesion: 0.25
Nodes (5): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}()

### Community 219 - "2026_09_24_120000_create_sincronizacion_movil_tables.php"
Cohesion: 0.25
Nodes (5): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}()

### Community 220 - "2026_09_24_200426_create_analisis_calidad_table.php"
Cohesion: 0.29
Nodes (5): {closure#1}(), {closure#2}(), {closure#3}(), migrarControlesExistentes(), up()

### Community 221 - "2026_10_25_231000_archive_roles_retirados_usuarios.php"
Cohesion: 0.32
Nodes (4): {closure#1}(), {closure#3}(), {closure#4}(), revocarSesiones()

### Community 222 - "2026_10_28_023504_crear_recetas_compartidas.php"
Cohesion: 0.25
Nodes (4): {closure#1}(), {closure#2}(), {closure#3}(), {closure#5}()

### Community 223 - "proveedor-qr.test.mjs"
Cohesion: 0.46
Nodes (5): iniciarQrProveedor(), nombreArchivoQr(), obtenerPngQr(), entorno(), png

### Community 227 - "EstadoEscaneoQr"
Cohesion: 0.33
Nodes (5): Error, Escaneando, EscanearQrUiState, EstadoEscaneoQr, Resultado

### Community 228 - "PlataformaProveedor.kt"
Cohesion: 0.52
Nodes (5): BrilloProveedor(), FotoAdjuntaProveedor(), recordarCompartirProveedor(), recordarFotoProveedor(), recordarGuardarComprobante()

### Community 230 - "PlataformaProveedor.ios.kt"
Cohesion: 0.52
Nodes (5): BrilloProveedor(), FotoAdjuntaProveedor(), recordarCompartirProveedor(), recordarFotoProveedor(), recordarGuardarComprobante()

### Community 231 - "config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 232 - "2026_09_20_165811_ampliar_auditorias_y_versionar_sesiones.php"
Cohesion: 0.29
Nodes (4): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}()

### Community 233 - "2026_09_24_190253_add_uuid_movil_a_calidad_y_reclamos.php"
Cohesion: 0.29
Nodes (4): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}()

### Community 234 - "2026_10_26_021440_complete_portal_proveedor_requests.php"
Cohesion: 0.29
Nodes (4): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}()

### Community 235 - "2026_10_28_014353_separar_catalogo_y_precios_por_comprador.php"
Cohesion: 0.29
Nodes (4): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}()

### Community 237 - "proveedores/index.blade.php"
Cohesion: 0.33
Nodes (5): admin.proveedores.estado-modal, admin.proveedores.tabla, admin.proveedores.tarjetas, admin.proveedores.qr-modal, layouts.admin

### Community 239 - "ConexionPortal"
Cohesion: 0.33
Nodes (6): ConexionPortal, CONECTANDO, EN_LINEA, NO_CONFIGURADA, NO_DISPONIBLE, SIN_CONEXION

### Community 240 - "0001_01_01_000000_create_users_table.php"
Cohesion: 0.33
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 241 - "0001_01_01_000002_create_jobs_table.php"
Cohesion: 0.33
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 243 - "2026_10_26_142531_create_tarifas_leche_table.php"
Cohesion: 0.33
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 244 - "2026_10_28_040918_create_perfiles_roles_table.php"
Cohesion: 0.33
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 250 - "require"
Cohesion: 0.40
Nodes (5): require, endroid/qr-code, laravel/framework, laravel/tinker, php

### Community 259 - "inventario/index.blade.php"
Cohesion: 0.50
Nodes (3): admin.inventario._formularios-ingredientes, admin.inventario._materiales, layouts.admin

### Community 260 - "usuarios/index.blade.php"
Cohesion: 0.50
Nodes (3): admin.usuarios.acciones, admin.usuarios.modales, layouts.admin

## Knowledge Gaps
- **270 isolated node(s):** `PREPARANDO`, `LISTO`, `ERROR`, `UIKit`, `LitrosNoPositivos` (+265 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 2346 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **325 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `AplicarCambioMovilUseCase` connect `Usuario` to `Eloquent/Usuario.php`, `Illuminate\Http\Request`, `TokenMovil`, `Illuminate\Support\Facades\DB`?**
  _High betweenness centrality (0.280) - this node is a cross-community bridge._
- **What connects `PREPARANDO`, `LISTO`, `ERROR` to the rest of the system?**
  _270 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Componentes.kt` be split into smaller, more focused modules?**
  _Cohesion score 0.03294573643410853 - nodes in this community are weakly interconnected._
- **Why does `MovilController` connect `TokenMovil` to `Usuario`, `Illuminate\Http\Request`, `Eloquent/Usuario.php`?**
  _High betweenness centrality (0.115) - this node is a cross-community bridge._
- **Should `Illuminate\Http\Request` be split into smaller, more focused modules?**
  _Cohesion score 0.023155044008124577 - nodes in this community are weakly interconnected._
- **Why does `Usuario` connect `Usuario` to `Illuminate\Http\Request`, `ProveedorRepositoryInterface`, `Proveedor`, `TestCase`, `AuditoriaSeguridadTest`, `Illuminate\Support\Facades\DB`, `AdminProveedoresPanelTest`, `AdminUsuariosModuleTest`, `Zona`, `EstadosIntegradosTest`, `RolesSinPerfilBaseTest`, `AdminLiquidacionDetalleTest`, `AdminProduccionModuleTest`, `DomainException`, `AnalisisCalidad`, `MovilAuthenticationTest`, `SuspensionConjuntaProveedorTest`, `Illuminate\Pagination\LengthAwarePaginator`, `ExportarDatosMovilQuery.php`, `VistaProveedoresTarjetasTest.php`, `EstadoRapidoTablasTest`, `TokenMovil`, `RegistroUnificadoProveedoresTest`, `AdminNavegacionYPermisosTest`, `Entrega`, `AdminRecepcionModuleTest`, `TarifasTemporadaTest`, `MapaRutaController.php`, `Sancion`, `Eloquent/Usuario.php`, `SincronizacionMovilTest`, `EloquentUsuarioRepository.php`, `GuardarMapaRutaRequest.php`, `EstadoEnlazadoUsuarioProveedorTest`, `ProveedorManagementTest`, `CambiosMovilTest`, `AdminDashboardTest`, `RefreshTokenMovilTest`, `SinRecojo`, `AdminDashboardPanelTest.php`?**
  _High betweenness centrality (0.113) - this node is a cross-community bridge._
- **Should `SyncState` be split into smaller, more focused modules?**
  _Cohesion score 0.023727324085117067 - nodes in this community are weakly interconnected._