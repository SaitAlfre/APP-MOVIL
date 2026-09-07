package pe.ecolecta

import android.app.Application
import org.koin.android.ext.koin.androidContext
import org.koin.dsl.module
import pe.ecolecta.data.local.DatabaseDriverFactory
import pe.ecolecta.di.iniciarKoin
import pe.ecolecta.domain.DeviceIdProvider
import pe.ecolecta.domain.DeviceIdProviderAndroid

class EcolectaApp : Application() {
    override fun onCreate() {
        super.onCreate()
        iniciarKoin {
            androidContext(this@EcolectaApp)
            modules(
                module {
                    single { DatabaseDriverFactory(androidContext()) }
                    single<DeviceIdProvider> { DeviceIdProviderAndroid(androidContext()) }
                },
            )
        }
    }
}
