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
                // Sin Firebase en iOS en esta versión (ver shared/build.gradle.kts): los registros quedan
                // "guardados en este celular", nunca se finge que están sincronizados.
                single<RegistroAcopioRemotoRepository> { RegistroAcopioRemotoRepositoryPendiente() }
                single<IdentidadRemotaProvider> { IdentidadRemotaProviderPendiente() }
            },
            // Sin URL de panel web en iOS en esta versión: se muestra "no configurado", sin fingir envíos.
            moduloServidorWeb(urlBase = "", nombreDispositivo = "iOS", io = kotlinx.coroutines.Dispatchers.Default),
        )
    }
}
