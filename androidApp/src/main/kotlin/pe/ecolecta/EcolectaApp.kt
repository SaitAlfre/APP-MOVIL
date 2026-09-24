package pe.ecolecta

import android.app.Application
import org.koin.android.ext.koin.androidContext
import org.koin.dsl.module
import pe.ecolecta.data.auth.FirebaseAuthAnonimoProvider
import pe.ecolecta.data.local.DatabaseDriverFactory
import pe.ecolecta.data.repository.RegistroAcopioRemotoRepositoryFirebase
import pe.ecolecta.di.iniciarKoin
import pe.ecolecta.domain.DeviceIdProvider
import pe.ecolecta.domain.DeviceIdProviderAndroid
import pe.ecolecta.domain.IdentidadRemotaProvider
import pe.ecolecta.domain.repository.RegistroAcopioRemotoRepository
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
                    // Firebase: solo Android (ver shared/build.gradle.kts). Nunca en el módulo común
                    // para que iOS siga compilando sin el SDK nativo de Firebase.
                    // RegistroAcopioRemotoRepositoryFirebase recibe la MISMA instancia de
                    // IdentidadRemotaProvider que usa Perfil — el candado contra sesiones anónimas
                    // duplicadas solo protege si todos pasan por ese único punto (ver FirebaseAuthAnonimoProvider).
                    single<IdentidadRemotaProvider> {
                        if (BuildConfig.LOCAL_PREVIEW) pe.ecolecta.data.auth.IdentidadRemotaProviderPendiente()
                        else FirebaseAuthAnonimoProvider()
                    }
                    // Vista local (-PlocalPreview=true, sin google-services.json): sin sincronización
                    // remota; la interfaz lo muestra como "Guardado en este celular".
                    single<RegistroAcopioRemotoRepository> {
                        if (BuildConfig.LOCAL_PREVIEW) pe.ecolecta.data.repository.RegistroAcopioRemotoRepositoryPendiente()
                        else RegistroAcopioRemotoRepositoryFirebase(get())
                    }
                },
            )
        }
    }
}
