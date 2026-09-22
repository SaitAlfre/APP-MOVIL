package pe.ecolecta

import android.app.Application
import org.koin.android.ext.koin.androidContext
import org.koin.dsl.module
import pe.ecolecta.data.auth.FirebaseAuthAnonimoProvider
import pe.ecolecta.data.local.DatabaseDriverFactory
import pe.ecolecta.data.repository.RutaAcopioRepositoryFirebase
import pe.ecolecta.di.iniciarKoin
import pe.ecolecta.domain.DeviceIdProvider
import pe.ecolecta.domain.DeviceIdProviderAndroid
import pe.ecolecta.domain.IdentidadRemotaProvider
import pe.ecolecta.domain.LocationProvider
import pe.ecolecta.domain.SeguimientoController
import pe.ecolecta.domain.SeguimientoControllerAndroid
import pe.ecolecta.domain.repository.RutaAcopioRepository
import pe.ecolecta.presentation.qr.ExportadorQr
import qrgenerator.AppContext

class EcolectaApp : Application() {
    override fun onCreate() {
        super.onCreate()
        AppContext.apply { set(applicationContext) }
        iniciarKoin {
            androidContext(this@EcolectaApp)
            modules(
                module {
                    single { DatabaseDriverFactory(androidContext()) }
                    single<DeviceIdProvider> { DeviceIdProviderAndroid(androidContext()) }
                    single { ExportadorQr(androidContext()) }
                    single { LocationProvider(androidContext()) }
                    single<SeguimientoController> { SeguimientoControllerAndroid(androidContext()) }
                    // Firebase: solo Android (ver shared/build.gradle.kts). Nunca en el módulo común
                    // para que iOS siga compilando sin el SDK nativo de Firebase.
                    // RutaAcopioRepositoryFirebase recibe la MISMA instancia de IdentidadRemotaProvider
                    // que usa Perfil — el candado contra sesiones anónimas duplicadas solo protege si
                    // todos los llamadores pasan por ese único punto (ver FirebaseAuthAnonimoProvider).
                    single<IdentidadRemotaProvider> {
                        if (BuildConfig.LOCAL_PREVIEW) pe.ecolecta.data.auth.IdentidadRemotaProviderPendiente()
                        else FirebaseAuthAnonimoProvider()
                    }
                    single<RutaAcopioRepository> {
                        if (BuildConfig.LOCAL_PREVIEW) pe.ecolecta.data.repository.RutaAcopioRepositoryPendiente()
                        else RutaAcopioRepositoryFirebase(get())
                    }
                },
            )
        }
    }
}
