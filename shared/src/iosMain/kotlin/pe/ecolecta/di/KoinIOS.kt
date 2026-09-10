package pe.ecolecta.di

import org.koin.dsl.module
import pe.ecolecta.data.auth.IdentidadRemotaProviderPendiente
import pe.ecolecta.data.local.DatabaseDriverFactory
import pe.ecolecta.data.repository.RutaAcopioRepositoryPendiente
import pe.ecolecta.domain.DeviceIdProvider
import pe.ecolecta.domain.DeviceIdProviderIOS
import pe.ecolecta.domain.IdentidadRemotaProvider
import pe.ecolecta.domain.LocationProvider
import pe.ecolecta.domain.SeguimientoController
import pe.ecolecta.domain.SeguimientoControllerIOS
import pe.ecolecta.domain.repository.RutaAcopioRepository
import pe.ecolecta.presentation.qr.ExportadorQr

/** Punto de entrada llamado desde Swift (`KoinIOSKt.iniciarKoinIOS()`) al arrancar la app. */
fun iniciarKoinIOS() {
    iniciarKoin {
        modules(
            module {
                single { DatabaseDriverFactory() }
                single<DeviceIdProvider> { DeviceIdProviderIOS() }
                single { ExportadorQr() }
                single { LocationProvider() }
                single<SeguimientoController> { SeguimientoControllerIOS() }
                // Sin Firebase en iOS en esta versión (ver shared/build.gradle.kts): seguimiento
                // remoto siempre "no conectado", nunca datos ficticios.
                single<RutaAcopioRepository> { RutaAcopioRepositoryPendiente() }
                single<IdentidadRemotaProvider> { IdentidadRemotaProviderPendiente() }
            },
        )
    }
}
