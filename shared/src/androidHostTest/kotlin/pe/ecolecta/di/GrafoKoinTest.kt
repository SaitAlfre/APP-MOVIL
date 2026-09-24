package pe.ecolecta.di

import app.cash.sqldelight.db.SqlDriver
import app.cash.sqldelight.driver.jdbc.sqlite.JdbcSqliteDriver
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.test.StandardTestDispatcher
import kotlinx.coroutines.test.resetMain
import kotlinx.coroutines.test.setMain
import org.koin.core.context.startKoin
import org.koin.core.context.stopKoin
import org.koin.dsl.module
import pe.ecolecta.data.auth.IdentidadRemotaProviderPendiente
import pe.ecolecta.data.local.db.EcolectaDatabase
import pe.ecolecta.data.repository.RegistroAcopioRemotoRepositoryPendiente
import pe.ecolecta.domain.DeviceIdProvider
import pe.ecolecta.domain.IdentidadRemotaProvider
import pe.ecolecta.domain.repository.RegistroAcopioRemotoRepository
import pe.ecolecta.domain.usecase.acopio.DeshacerSinRecojoUseCase
import pe.ecolecta.domain.usecase.acopio.MarcarSinRecojoUseCase
import pe.ecolecta.domain.usecase.auth.CerrarSesionUseCase
import pe.ecolecta.domain.usecase.jornada.CerrarJornadaUseCase
import pe.ecolecta.domain.usecase.jornada.ReabrirJornadaUseCase
import pe.ecolecta.domain.usecase.sync.SincronizarRegistrosAcopioUseCase
import pe.ecolecta.presentation.acopiador.home.AcopiadorHomeViewModel
import pe.ecolecta.presentation.acopiador.lista.ListaProveedoresViewModel
import pe.ecolecta.presentation.acopiador.onboarding.SeleccionZonaVehiculoViewModel
import pe.ecolecta.presentation.acopiador.perfil.PerfilViewModel
import pe.ecolecta.presentation.acopiador.sincronizacion.SincronizacionViewModel
import pe.ecolecta.presentation.proveedor.PortalProveedorViewModel
import kotlin.test.AfterTest
import kotlin.test.BeforeTest
import kotlin.test.Test
import kotlin.test.assertNotNull
import kotlin.test.assertSame

/**
 * Resuelve con el grafo REAL de Koin (dataModule/domainModule/presentationModule) todo lo que esta
 * versión agregó o cambió al retirar el GPS, con una base SQLite en memoria y la plataforma sin
 * Firebase. Un parámetro de constructor mal cableado fallaría aquí y no en el teléfono.
 */
class GrafoKoinTest {
    @BeforeTest fun preparar() { Dispatchers.setMain(StandardTestDispatcher()) }
    @AfterTest fun limpiar() { stopKoin(); Dispatchers.resetMain() }

    @Test
    fun `el grafo resuelve la lista de acopio, la sincronizacion y el portal sin el gps`() {
        val koin = startKoin {
            allowOverride(true)
            modules(
                dataModule, domainModule, presentationModule,
                module {
                    single<SqlDriver> { JdbcSqliteDriver(JdbcSqliteDriver.IN_MEMORY).also { EcolectaDatabase.Schema.create(it) } }
                    single<DeviceIdProvider> { object : DeviceIdProvider { override fun obtenerId() = "prueba" } }
                    single<IdentidadRemotaProvider> { IdentidadRemotaProviderPendiente() }
                    single<RegistroAcopioRemotoRepository> { RegistroAcopioRemotoRepositoryPendiente() }
                },
            )
        }.koin

        assertNotNull(koin.get<MarcarSinRecojoUseCase>())
        assertNotNull(koin.get<DeshacerSinRecojoUseCase>())
        assertNotNull(koin.get<CerrarJornadaUseCase>())
        assertNotNull(koin.get<ReabrirJornadaUseCase>())
        assertNotNull(koin.get<CerrarSesionUseCase>())
        assertSame(koin.get<SincronizarRegistrosAcopioUseCase>(), koin.get<SincronizarRegistrosAcopioUseCase>(), "debe ser single (un solo candado)")
        assertNotNull(koin.get<ListaProveedoresViewModel>())
        assertNotNull(koin.get<PortalProveedorViewModel>())
        assertNotNull(koin.get<SincronizacionViewModel>())
        assertNotNull(koin.get<AcopiadorHomeViewModel>())
        assertNotNull(koin.get<PerfilViewModel>())
        assertNotNull(koin.get<SeleccionZonaVehiculoViewModel>())
    }
}
