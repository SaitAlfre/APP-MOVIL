package pe.ecolecta.domain.usecase.sync

import pe.ecolecta.domain.IdentidadRemotaProvider

/** UID anónimo de Firebase de este celular: se muestra en Perfil para vincularlo en Firebase Console. */
class ObtenerIdentidadRemotaUseCase(private val identidadRemotaProvider: IdentidadRemotaProvider) {
    suspend operator fun invoke(): String? = identidadRemotaProvider.obtenerUidAnonimo()
}
