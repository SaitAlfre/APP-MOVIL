package pe.ecolecta.di

import org.koin.dsl.module
import pe.ecolecta.data.local.DatabaseDriverFactory
import pe.ecolecta.domain.DeviceIdProvider
import pe.ecolecta.domain.DeviceIdProviderIOS

/** Punto de entrada llamado desde Swift (`KoinIOSKt.iniciarKoinIOS()`) al arrancar la app. */
fun iniciarKoinIOS() {
    iniciarKoin {
        modules(
            module {
                single { DatabaseDriverFactory() }
                single<DeviceIdProvider> { DeviceIdProviderIOS() }
            },
        )
    }
}
