package pe.ecolecta.di

import app.cash.sqldelight.db.SqlDriver
import kotlinx.coroutines.Dispatchers
import org.koin.core.context.startKoin
import org.koin.core.module.dsl.viewModel
import org.koin.dsl.KoinAppDeclaration
import org.koin.dsl.module
import pe.ecolecta.data.local.DatabaseDriverFactory
import pe.ecolecta.data.local.DatabaseSeeder
import pe.ecolecta.data.local.db.EcolectaDatabase
import pe.ecolecta.data.remote.crearHttpClient
import pe.ecolecta.data.repository.SqlDelightAuditoriaRepository
import pe.ecolecta.data.repository.SqlDelightControlCalidadRepository
import pe.ecolecta.data.repository.SqlDelightEntregaRepository
import pe.ecolecta.data.repository.SqlDelightJornadaRepository
import pe.ecolecta.data.repository.SqlDelightProveedorRepository
import pe.ecolecta.data.repository.SqlDelightSesionRepository
import pe.ecolecta.data.repository.SqlDelightTrasladoRepository
import pe.ecolecta.data.repository.SqlDelightUsuarioRepository
import pe.ecolecta.data.repository.SqlDelightVehiculoRepository
import pe.ecolecta.data.repository.SqlDelightZonaRepository
import pe.ecolecta.data.security.InMemoryJornadaEnCursoRepository
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.RelojSistema
import pe.ecolecta.domain.repository.AuditoriaRepository
import pe.ecolecta.domain.repository.ControlCalidadRepository
import pe.ecolecta.domain.repository.EntregaRepository
import pe.ecolecta.domain.repository.JornadaEnCursoRepository
import pe.ecolecta.domain.repository.JornadaRepository
import pe.ecolecta.domain.repository.ProveedorRepository
import pe.ecolecta.domain.repository.SesionRepository
import pe.ecolecta.domain.repository.TrasladoRepository
import pe.ecolecta.domain.repository.UsuarioRepository
import pe.ecolecta.domain.repository.VehiculoRepository
import pe.ecolecta.domain.repository.ZonaRepository
import pe.ecolecta.domain.security.Pbkdf2PinHasher
import pe.ecolecta.domain.security.PinHasher
import pe.ecolecta.domain.usecase.auditoria.ListarAuditoriaUseCase
import pe.ecolecta.domain.usecase.auth.CerrarSesionUseCase
import pe.ecolecta.domain.usecase.auth.LoginOfflineUseCase
import pe.ecolecta.domain.usecase.auth.ObtenerSesionUseCase
import pe.ecolecta.domain.usecase.auth.SeleccionarRolUseCase
import pe.ecolecta.domain.usecase.conflicto.ListarConflictosUseCase
import pe.ecolecta.domain.usecase.conflicto.ResolverConflictoUseCase
import pe.ecolecta.domain.usecase.dashboard.ObtenerResumenAdminUseCase
import pe.ecolecta.domain.usecase.entrega.AnularEntregaUseCase
import pe.ecolecta.domain.usecase.entrega.CorregirEntregaUseCase
import pe.ecolecta.domain.usecase.entrega.ListarEntregasUseCase
import pe.ecolecta.domain.usecase.entrega.ObservarEntregasDeJornadaUseCase
import pe.ecolecta.domain.usecase.entrega.ObservarEntregasUseCase
import pe.ecolecta.domain.usecase.entrega.ObtenerEntregaUseCase
import pe.ecolecta.domain.usecase.entrega.RegistrarEntregaUseCase
import pe.ecolecta.domain.usecase.entrega.RegistrarLoteUseCase
import pe.ecolecta.domain.usecase.jornada.AbrirJornadaUseCase
import pe.ecolecta.domain.usecase.jornada.CerrarJornadaUseCase
import pe.ecolecta.domain.usecase.jornada.ListarJornadasUseCase
import pe.ecolecta.domain.usecase.jornada.ObservarJornadasUseCase
import pe.ecolecta.domain.usecase.jornada.ObtenerJornadaEnCursoUseCase
import pe.ecolecta.domain.usecase.jornada.ObtenerJornadaUseCase
import pe.ecolecta.domain.usecase.jornada.ReanudarJornadaSiExisteUseCase
import pe.ecolecta.domain.usecase.proveedor.ActualizarProveedorUseCase
import pe.ecolecta.domain.usecase.proveedor.CrearProveedorUseCase
import pe.ecolecta.domain.usecase.proveedor.EscanearQrProveedorUseCase
import pe.ecolecta.domain.usecase.proveedor.ListarProveedoresPorZonaUseCase
import pe.ecolecta.domain.usecase.proveedor.ListarProveedoresUseCase
import pe.ecolecta.domain.usecase.proveedor.ObtenerPerfilProveedorUseCase
import pe.ecolecta.domain.usecase.proveedor.ObtenerProveedorAsociadoUseCase
import pe.ecolecta.domain.usecase.proveedor.ObtenerProveedorUseCase
import pe.ecolecta.domain.usecase.sync.ObtenerIdentidadRemotaUseCase
import pe.ecolecta.domain.usecase.sync.ObtenerColaSyncUseCase
import pe.ecolecta.domain.usecase.traslado.AutorizarTrasladoUseCase
import pe.ecolecta.domain.usecase.traslado.CrearTrasladoUseCase
import pe.ecolecta.domain.usecase.traslado.ListarTrasladosUseCase
import pe.ecolecta.domain.usecase.traslado.RechazarTrasladoUseCase
import pe.ecolecta.domain.usecase.usuario.CambiarPinUsuarioUseCase
import pe.ecolecta.domain.usecase.usuario.CrearUsuarioUseCase
import pe.ecolecta.domain.usecase.usuario.ListarUsuariosUseCase
import pe.ecolecta.domain.usecase.usuario.ObtenerUsuarioUseCase
import pe.ecolecta.domain.usecase.vehiculo.ActualizarVehiculoUseCase
import pe.ecolecta.domain.usecase.vehiculo.CrearVehiculoUseCase
import pe.ecolecta.domain.usecase.vehiculo.ListarVehiculosUseCase
import pe.ecolecta.domain.usecase.vehiculo.ObtenerVehiculoUseCase
import pe.ecolecta.domain.usecase.zona.ActualizarZonaUseCase
import pe.ecolecta.domain.usecase.zona.CrearZonaUseCase
import pe.ecolecta.domain.usecase.zona.ListarZonasUseCase
import pe.ecolecta.domain.usecase.zona.ObtenerZonaUseCase
import pe.ecolecta.presentation.admin.auditoria.AuditoriaViewModel
import pe.ecolecta.presentation.admin.conflictos.ConflictosViewModel
import pe.ecolecta.presentation.admin.dashboard.AdminDashboardViewModel
import pe.ecolecta.presentation.admin.entregas.EntregaDetalleViewModel
import pe.ecolecta.presentation.admin.entregas.EntregasViewModel
import pe.ecolecta.presentation.admin.jornadas.JornadaDetalleViewModel
import pe.ecolecta.presentation.admin.jornadas.JornadasViewModel
import pe.ecolecta.presentation.admin.proveedores.ProveedorFormViewModel
import pe.ecolecta.presentation.admin.proveedores.ProveedoresViewModel
import pe.ecolecta.presentation.admin.traslados.TrasladosViewModel
import pe.ecolecta.presentation.admin.usuarios.UsuarioFormViewModel
import pe.ecolecta.presentation.admin.usuarios.UsuariosViewModel
import pe.ecolecta.presentation.admin.vehiculos.VehiculoFormViewModel
import pe.ecolecta.presentation.admin.vehiculos.VehiculosViewModel
import pe.ecolecta.presentation.admin.zonas.ZonaFormViewModel
import pe.ecolecta.presentation.admin.zonas.ZonasViewModel
import pe.ecolecta.presentation.acopiador.entregas.EntregasDelDiaViewModel
import pe.ecolecta.presentation.acopiador.home.AcopiadorHomeViewModel
import pe.ecolecta.presentation.acopiador.lista.ListaProveedoresViewModel
import pe.ecolecta.presentation.acopiador.lote.LoteViewModel
import pe.ecolecta.presentation.acopiador.nav.AcopiadorBadgeViewModel
import pe.ecolecta.presentation.acopiador.onboarding.SeleccionZonaVehiculoViewModel
import pe.ecolecta.presentation.acopiador.perfil.PerfilViewModel
import pe.ecolecta.presentation.acopiador.qr.EscanearQrViewModel
import pe.ecolecta.presentation.acopiador.registro.RegistroEntregaViewModel
import pe.ecolecta.presentation.acopiador.sincronizacion.SincronizacionViewModel
import pe.ecolecta.presentation.auth.LoginViewModel
import pe.ecolecta.presentation.auth.SeleccionRolViewModel
import pe.ecolecta.presentation.calidad.CalidadViewModel

val dataModule = module {
    single<pe.ecolecta.domain.repository.PortalProveedorRepository> { pe.ecolecta.data.repository.PortalProveedorRepository(get()) }
    viewModel { pe.ecolecta.presentation.proveedor.PortalProveedorViewModel(get(), get(), get(), get(), get(), get(), get(), get(), get(), get(), get(), get(), get()) }
    single<SqlDriver> { get<DatabaseDriverFactory>().crearDriver() }
    single { EcolectaDatabase(get()) }
    single { DatabaseSeeder(get(), get(), get()) }
    single { crearHttpClient() }

    single<UsuarioRepository> { SqlDelightUsuarioRepository(get(), Dispatchers.Default) }
    single<ZonaRepository> { SqlDelightZonaRepository(get(), Dispatchers.Default) }
    single<VehiculoRepository> { SqlDelightVehiculoRepository(get(), Dispatchers.Default) }
    single<ProveedorRepository> { SqlDelightProveedorRepository(get(), Dispatchers.Default) }
    single<TrasladoRepository> { SqlDelightTrasladoRepository(get(), Dispatchers.Default) }
    single<JornadaRepository> { SqlDelightJornadaRepository(get(), Dispatchers.Default) }
    single<EntregaRepository> { SqlDelightEntregaRepository(get(), Dispatchers.Default) }
    single<AuditoriaRepository> { SqlDelightAuditoriaRepository(get(), Dispatchers.Default) }
    single<ControlCalidadRepository> { SqlDelightControlCalidadRepository(get(), Dispatchers.Default) }
    single<SesionRepository> { SqlDelightSesionRepository(get(), get(), Dispatchers.Default) }
    single<JornadaEnCursoRepository> { InMemoryJornadaEnCursoRepository() }
    single<pe.ecolecta.domain.repository.CuentasRepository> { pe.ecolecta.data.repository.SqlDelightCuentasRepository(get(), Dispatchers.Default) }
    single<pe.ecolecta.domain.repository.GestionPortalRepository> { pe.ecolecta.data.repository.SqlDelightGestionPortalRepository(get(), Dispatchers.Default) }
    single<pe.ecolecta.domain.repository.ComunicadoRepository> { pe.ecolecta.data.repository.SqlDelightComunicadoRepository(get(), Dispatchers.Default) }
    single<pe.ecolecta.domain.repository.AlertaDescartadaRepository> { pe.ecolecta.data.repository.SqlDelightAlertaDescartadaRepository(get(), Dispatchers.Default) }
    single<pe.ecolecta.domain.repository.SinRecojoRepository> { pe.ecolecta.data.repository.SqlDelightSinRecojoRepository(get(), Dispatchers.Default) }
    single<pe.ecolecta.domain.repository.RegistroRecibidoRepository> { pe.ecolecta.data.repository.SqlDelightRegistroRecibidoRepository(get(), Dispatchers.Default) }
    // RegistroAcopioRemotoRepository, IdentidadRemotaProvider y ServidorWebRepository se registran por
    // plataforma (EcolectaApp.kt / KoinIOS.kt): Firebase es Android-only y la URL del panel web se fija
    // al compilar (BuildConfig), nunca en el módulo común.
}

val domainModule = module {
    single<Reloj> { RelojSistema() }
    single<PinHasher> { Pbkdf2PinHasher() }

    factory { LoginOfflineUseCase(get(), get(), get(), get(), get()) }
    factory { SeleccionarRolUseCase(get()) }
    factory { CerrarSesionUseCase(get()) }
    factory { ObtenerSesionUseCase(get()) }

    factory { CrearUsuarioUseCase(get(), get(), get()) }
    factory { pe.ecolecta.domain.usecase.usuario.ReglasCuenta(get(), get(), get(), get()) }
    factory { pe.ecolecta.domain.usecase.usuario.GuardarCuentaUseCase(get(), get(), get(), get(), get(), get(), get(), get()) }
    factory { pe.ecolecta.domain.usecase.usuario.CambiarEstadoCuentaUseCase(get(), get(), get(), get(), get()) }
    factory { pe.ecolecta.domain.usecase.usuario.DesbloquearCuentaUseCase(get(), get()) }
    factory { ListarUsuariosUseCase(get()) }
    factory { ObtenerUsuarioUseCase(get()) }
    factory { CambiarPinUsuarioUseCase(get(), get(), get()) }

    factory { CrearZonaUseCase(get(), get()) }
    factory { ListarZonasUseCase(get()) }
    factory { ObtenerZonaUseCase(get()) }
    factory { ActualizarZonaUseCase(get(), get(), get()) }

    factory { CrearVehiculoUseCase(get()) }
    factory { ListarVehiculosUseCase(get()) }
    factory { ObtenerVehiculoUseCase(get()) }
    factory { ActualizarVehiculoUseCase(get(), get()) }

    factory { CrearProveedorUseCase(get(), get()) }
    factory { ListarProveedoresUseCase(get()) }
    factory { ListarProveedoresPorZonaUseCase(get()) }
    factory { ObtenerProveedorUseCase(get()) }
    factory { ActualizarProveedorUseCase(get(), get()) }

    factory { EscanearQrProveedorUseCase(get()) }
    factory { ObtenerProveedorAsociadoUseCase(get()) }
    factory { ObtenerPerfilProveedorUseCase(get()) }

    factory { CrearTrasladoUseCase(get(), get(), get()) }
    factory { ListarTrasladosUseCase(get()) }
    factory { AutorizarTrasladoUseCase(get(), get(), get()) }
    factory { RechazarTrasladoUseCase(get(), get(), get()) }

    factory { ListarJornadasUseCase(get()) }
    factory { ObservarJornadasUseCase(get()) }
    factory { ObtenerJornadaUseCase(get()) }
    factory { ObtenerJornadaEnCursoUseCase(get()) }
    factory { AbrirJornadaUseCase(get(), get(), get()) }
    factory { CerrarJornadaUseCase(get(), get(), get(), get(), get()) }
    factory { ReanudarJornadaSiExisteUseCase(get(), get()) }
    single { pe.ecolecta.domain.ConfiguracionJornada() }
    factory { pe.ecolecta.domain.usecase.jornada.ResolverInicioAcopiadorUseCase(get(), get(), get(), get()) }
    factory { pe.ecolecta.domain.usecase.jornada.ObtenerJornadaTerminadaHoyUseCase(get(), get()) }
    factory { pe.ecolecta.domain.usecase.jornada.ReabrirJornadaUseCase(get(), get(), get(), get(), get(), get(), get()) }

    factory { ListarEntregasUseCase(get()) }
    factory { ObservarEntregasDeJornadaUseCase(get()) }
    factory { ObservarEntregasUseCase(get()) }
    factory { ObtenerEntregaUseCase(get()) }
    factory { pe.ecolecta.domain.usecase.entrega.ReglaEdicionEntrega(get()) }
    factory { CorregirEntregaUseCase(get(), get(), get(), get()) }
    factory { AnularEntregaUseCase(get(), get(), get(), get()) }
    factory { RegistrarEntregaUseCase(get(), get(), get(), get()) }
    factory { RegistrarLoteUseCase(get(), get(), get(), get()) }

    factory { ListarConflictosUseCase(get()) }
    factory { ResolverConflictoUseCase(get(), get(), get()) }

    factory { ListarAuditoriaUseCase(get()) }

    factory { ObtenerResumenAdminUseCase(get(), get(), get(), get(), get()) }
    factory { pe.ecolecta.domain.usecase.admin.ObservarAlertasAdminUseCase(get(), get(), get(), get(), get(), get(), get(), get()) }
    factory { pe.ecolecta.domain.usecase.admin.OcultarAlertaUseCase(get(), get()) }
    factory { pe.ecolecta.domain.usecase.admin.ResolverSolicitudUseCase(get(), get(), get()) }
    factory { pe.ecolecta.domain.usecase.admin.ObservarLiquidacionesUseCase(get(), get(), get()) }
    factory { pe.ecolecta.domain.usecase.admin.AprobarLiquidacionUseCase(get(), get(), get(), get(), get()) }
    factory { pe.ecolecta.domain.usecase.admin.MarcarLiquidacionPagadaUseCase(get(), get(), get(), get()) }
    factory { pe.ecolecta.domain.usecase.admin.PublicarComunicadoUseCase(get(), get()) }
    factory { ObtenerColaSyncUseCase(get()) }

    factory { ObtenerIdentidadRemotaUseCase(get()) }
    // single: su Mutex evita que el ciclo periódico y "Sincronizar ahora" envíen lo mismo a la vez.
    single { pe.ecolecta.domain.usecase.sync.SincronizarRegistrosAcopioUseCase(get(), get(), get(), get(), get(), get(), get()) }
    factory<pe.ecolecta.domain.usecase.sync.PreparadorEntregaServidor> {
        pe.ecolecta.domain.usecase.sync.PreparadorEntregaServidorLocal(get(), get(), get(), get(), get(), get())
    }
    // single: guarda el último motivo de enlace fallido por usuario y lanza el enlace en el ámbito de la app.
    single {
        val sincronizar = get<pe.ecolecta.domain.usecase.sync.SincronizarRegistrosAcopioUseCase>()
        pe.ecolecta.domain.usecase.sync.VincularServidorUseCase(
            get(), { sincronizar() }, kotlinx.coroutines.CoroutineScope(kotlinx.coroutines.SupervisorJob() + Dispatchers.Default),
        )
    }
    factory { pe.ecolecta.domain.usecase.acopio.MarcarSinRecojoUseCase(get(), get(), get(), get(), get(), get()) }
    factory { pe.ecolecta.domain.usecase.acopio.DeshacerSinRecojoUseCase(get(), get(), get(), get()) }
}

val presentationModule = module {
    viewModel { LoginViewModel(loginOfflineUseCase = get(), seleccionarRolUseCase = get(), vincularServidor = get()) }
    viewModel {
        CalidadViewModel(
            repository = get(),
            listarProveedoresUseCase = get(),
            obtenerSesionUseCase = get(),
            reloj = get(),
            listarZonasUseCase = get(),
        )
    }
    viewModel { (usuarioId: String) ->
        SeleccionRolViewModel(usuarioId = usuarioId, obtenerUsuarioUseCase = get(), seleccionarRolUseCase = get())
    }

    viewModel { AdminDashboardViewModel(obtenerResumenAdminUseCase = get(), observarLiquidaciones = get(), obtenerSesion = get()) }
    viewModel { pe.ecolecta.presentation.admin.alertas.AdminAlertasViewModel(get(), get(), get(), get(), get(), get(), get(), get()) }
    viewModel { pe.ecolecta.presentation.admin.reportes.AdminReportesViewModel(get(), get(), get(), get(), get(), get(), get(), get(), get()) }
    viewModel { pe.ecolecta.presentation.admin.perfil.AdminPerfilViewModel(get(), get()) }
    viewModel { pe.ecolecta.presentation.admin.supervision.AdminSupervisionViewModel(get(), get(), get(), get(), get()) }

    viewModel { UsuariosViewModel(get(), get(), get(), get(), get(), get(), get(), get()) }
    viewModel { (id: String?, fichaId: String?) -> UsuarioFormViewModel(id, fichaId, get(), get(), get(), get(), get(), get(), get(), get()) }

    viewModel { ZonasViewModel(get(), get(), get(), get(), get()) }
    viewModel { (id: String?) -> ZonaFormViewModel(id, get(), get(), get()) }

    viewModel { VehiculosViewModel(get(), get(), get(), get()) }
    viewModel { (id: String?) -> VehiculoFormViewModel(id, get(), get(), get()) }

    viewModel { ProveedoresViewModel(get(), get(), get()) }
    viewModel { (id: String) -> ProveedorFormViewModel(id, get(), get(), get(), get()) }

    viewModel {
        TrasladosViewModel(
            listarTrasladosUseCase = get(),
            listarProveedoresUseCase = get(),
            listarZonasUseCase = get(),
            crearTrasladoUseCase = get(),
            autorizarTrasladoUseCase = get(),
            rechazarTrasladoUseCase = get(),
            obtenerSesionUseCase = get(),
        )
    }

    viewModel {
        JornadasViewModel(
            observarJornadasUseCase = get(),
            observarEntregasUseCase = get(),
            listarUsuariosUseCase = get(),
            listarZonasUseCase = get(),
            listarVehiculosUseCase = get(),
            reloj = get(),
        )
    }
    viewModel { (id: String) ->
        JornadaDetalleViewModel(
            jornadaId = id,
            obtenerJornadaUseCase = get(),
            observarEntregasDeJornadaUseCase = get(),
            listarProveedoresUseCase = get(),
            obtenerUsuario = get(),
            obtenerZona = get(),
            obtenerVehiculo = get(),
        )
    }

    viewModel { EntregasViewModel(observarEntregasUseCase = get(), listarProveedoresUseCase = get()) }
    viewModel { (id: String) ->
        EntregaDetalleViewModel(
            entregaId = id,
            obtenerEntregaUseCase = get(),
            obtenerProveedorUseCase = get(),
            corregirEntregaUseCase = get(),
            anularEntregaUseCase = get(),
            obtenerSesionUseCase = get(),
            reglaEdicion = get(),
            listarAuditoria = get(),
            listarUsuarios = get(),
        )
    }

    viewModel {
        ConflictosViewModel(
            listarConflictosUseCase = get(),
            listarProveedoresUseCase = get(),
            resolverConflictoUseCase = get(),
            obtenerSesionUseCase = get(),
        )
    }

    viewModel { AuditoriaViewModel(listarAuditoriaUseCase = get(), listarUsuarios = get(), listarProveedores = get(), observarEntregas = get()) }

    viewModel {
        SeleccionZonaVehiculoViewModel(
            listarZonasUseCase = get(),
            listarVehiculosUseCase = get(),
            abrirJornadaUseCase = get(),
            obtenerSesionUseCase = get(),
            reloj = get(),
            cuentas = get(),
            cerrarSesionUseCase = get(),
        )
    }

    viewModel {
        AcopiadorBadgeViewModel(
            obtenerSesionUseCase = get(),
            obtenerColaSyncUseCase = get(),
        )
    }

    viewModel {
        AcopiadorHomeViewModel(
            obtenerJornadaEnCursoUseCase = get(),
            observarEntregasDeJornadaUseCase = get(),
            obtenerColaSyncUseCase = get(),
            listarProveedoresPorZonaUseCase = get(),
            obtenerSesionUseCase = get(),
            corregirEntregaUseCase = get(),
            anularEntregaUseCase = get(),
            listarZonasUseCase = get(),
            listarVehiculosUseCase = get(),
            cerrarJornadaUseCase = get(),
            obtenerJornadaTerminadaHoyUseCase = get(),
            reabrirJornadaUseCase = get(),
            cerrarSesionUseCase = get(),
        )
    }

    viewModel { (proveedorIdPreseleccionado: String?) ->
        RegistroEntregaViewModel(
            obtenerJornadaEnCursoUseCase = get(),
            listarProveedoresPorZonaUseCase = get(),
            listarEntregasUseCase = get(),
            registrarEntregaUseCase = get(),
            corregirEntregaUseCase = get(),
            obtenerSesionUseCase = get(),
            listarZonasUseCase = get(),
            proveedorIdPreseleccionado = proveedorIdPreseleccionado,
        )
    }

    viewModel {
        EscanearQrViewModel(
            escanearQrProveedorUseCase = get(),
            obtenerJornadaEnCursoUseCase = get(),
            listarZonasUseCase = get(),
        )
    }

    viewModel {
        LoteViewModel(
            obtenerJornadaEnCursoUseCase = get(),
            listarProveedoresPorZonaUseCase = get(),
            registrarLoteUseCase = get(),
            obtenerSesionUseCase = get(),
        )
    }

    viewModel {
        EntregasDelDiaViewModel(
            obtenerJornadaEnCursoUseCase = get(),
            observarEntregasDeJornadaUseCase = get(),
            listarProveedoresPorZonaUseCase = get(),
        )
    }

    viewModel {
        ListaProveedoresViewModel(
            obtenerJornadaEnCursoUseCase = get(),
            obtenerSesionUseCase = get(),
            listarProveedoresPorZonaUseCase = get(),
            observarEntregasUseCase = get(),
            sinRecojoRepository = get(),
            listarZonasUseCase = get(),
            marcarSinRecojoUseCase = get(),
            deshacerSinRecojoUseCase = get(),
            sincronizar = get(),
            reloj = get(),
        )
    }

    viewModel {
        SincronizacionViewModel(
            obtenerColaSyncUseCase = get(),
            obtenerSesionUseCase = get(),
            observarEntregasUseCase = get(),
            listarProveedoresUseCase = get(),
            sincronizarRegistros = get(),
            vincularServidor = get(),
        )
    }

    viewModel {
        PerfilViewModel(
            obtenerSesionUseCase = get(),
            obtenerJornadaEnCursoUseCase = get(),
            listarZonasUseCase = get(),
            listarVehiculosUseCase = get(),
            obtenerColaSyncUseCase = get(),
            cerrarSesionUseCase = get(),
            cerrarJornadaUseCase = get(),
            obtenerIdentidadRemotaUseCase = get(),
            cambiarPinUsuarioUseCase = get(),
            servidorWeb = get(),
        )
    }

}

/**
 * Panel web según la URL fijada al compilar: vacía = [pe.ecolecta.data.remote.ServidorWebNoConfigurado]
 * (la app lo dice y no finge enviar). Se arma aquí porque el cliente HTTP (Ktor) es interno de `shared`.
 */
fun moduloServidorWeb(urlBase: String, nombreDispositivo: String, io: kotlinx.coroutines.CoroutineDispatcher) = module {
    single<pe.ecolecta.domain.repository.ServidorWebRepository> {
        if (urlBase.isBlank()) {
            pe.ecolecta.data.remote.ServidorWebNoConfigurado()
        } else {
            pe.ecolecta.data.remote.ServidorWebRepositoryKtor(urlBase, get(), get(), get(), get(), io, nombreDispositivo)
        }
    }
}

fun iniciarKoin(configuracionAdicional: KoinAppDeclaration? = null) {
    val koinApp = startKoin {
        configuracionAdicional?.invoke(this)
        modules(dataModule, domainModule, presentationModule)
    }
    koinApp.koin.get<DatabaseSeeder>().sembrarSiEsNecesario()
}
