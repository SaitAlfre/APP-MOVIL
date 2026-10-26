package pe.ecolecta.di

import org.koin.dsl.module
import pe.ecolecta.data.auth.IdentidadRemotaProviderPendiente
import pe.ecolecta.data.local.DatabaseDriverFactory
import pe.ecolecta.data.repository.RegistroAcopioRemotoRepositoryPendiente
import pe.ecolecta.domain.DeviceIdProvider
import pe.ecolecta.domain.DeviceIdProviderIOS
import pe.ecolecta.domain.IdentidadRemotaProvider
import pe.ecolecta.domain.repository.RegistroAcopioRemotoRepository
import pe.ecolecta.presentation.qr.ExportadorQr

/** Punto de entrada llamado desde Swift (`KoinIOSKt.iniciarKoinIOS()`) al arrancar la app. */
fun iniciarKoinIOS() {
    iniciarKoin {
        modules(
            module {
                single { DatabaseDriverFactory() }
                single<DeviceIdProvider> { DeviceIdProviderIOS() }
                single { ExportadorQr() }
                single<RegistroAcopioRemotoRepository> {
                    pe.ecolecta.data.repository.RegistroAcopioRemotoRepositoryApi(
                        get(), get(), get(), get(), get(), get(), get(), get(), get(),
                        estado = { get<pe.ecolecta.domain.usecase.sync.CoordinadorSync>().estado },
                    )
                }
                single<IdentidadRemotaProvider> { IdentidadRemotaProviderPendiente() }
            },
            // Comparte la misma API oficial que Android.
            moduloServidorWeb(urlBase = "https://ecolecta-5cfec46d4968.herokuapp.com", nombreDispositivo = "iOS", io = kotlinx.coroutines.Dispatchers.Default),
        )
    }
}
